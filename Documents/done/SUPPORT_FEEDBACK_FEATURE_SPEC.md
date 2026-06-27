# Aegis — Support & Feedback Feature Design

**Purpose:** Native (non-third-party) Support & Feedback system built into the centralized `_shared/` system. Surfaces in every portal (Practitioner, CS, SS, BP) so users can submit help tickets and feedback. All submissions land in the Admin portal's `complaints.php` for MAAT team triage.

---

## Architecture Decision

**Recommendation: New sidebar item + shared template** (NOT merged into `messages.php`).

Reasoning:
- Messages is for **peer-to-peer** communication (Practitioner ↔ CS, BP ↔ Practitioner)
- Support is for **user ↔ MAAT-staff** (one-directional escalation with reply thread)
- Different access model — support tickets need admin routing, internal notes, status changes
- Mixing them in `messages.php` would clutter the inbox with system tickets

---

## Sidebar Placement

Add a new sidebar item under **Account** group (or **Communication** depending on visual balance) — same label across all 4 portals:

```
─ Account ─
  Settings
  Support  ← NEW
```

OR keep it bottom-aligned with Settings for low-frequency reach. Final decision based on visual review with Carizma.

---

## File Structure (centralized)

```
_shared/templates/
└── support.php            ← NEW — shared template for Support & Feedback page

_shared/
├── save_support.php       ← NEW — write endpoint (create_ticket, send_feedback, reply, etc.)
└── models_write.php       ← Add helpers:
                              aegis_create_support_ticket()
                              aegis_send_feedback()
                              aegis_reply_to_ticket()

_shared/models.php         ← Add helpers:
                              aegis_get_user_support_tickets()
                              aegis_get_ticket_thread()
                              aegis_get_user_feedback_history()

Each portal:
└── support.php            ← THIN STUB (3-liner) that includes _shared/templates/support.php
```

**Stub example (provider-portal/support.php):**
```php
<?php declare(strict_types=1);
require_once __DIR__ . '/../_shared/templates/support.php';
```

Mirror this stub in `continuity-steward-portal/`, `support-steward-portal/`, `biz-portal/`.

---

## Page Layout (`_shared/templates/support.php`)

```
┌─────────────────────────────────────────────────────┐
│  Eyebrow:    Support                                │
│  Title:      Support & Feedback                     │
│  Subtitle:   Submit a ticket, share feedback,       │
│              or browse common questions.            │
│  CTA:        [+ New Support Ticket]                 │
└─────────────────────────────────────────────────────┘

Tab Strip:
┌────────────────┬───────────────┬───────────────────┐
│ My Tickets (3) │ Feedback      │ Help Center       │
└────────────────┴───────────────┴───────────────────┘

[TAB 1: MY TICKETS]
┌─────────────────────────────────────────────────────┐
│ Ticket cards (status pill, subject, last reply,     │
│  unread badge, click → opens detail modal)          │
└─────────────────────────────────────────────────────┘

[TAB 2: FEEDBACK]
┌─────────────────────────────────────────────────────┐
│ Free-form feedback composer:                        │
│  Subject  | _______________                         │
│  Category | [General · Feature · Bug · Praise]      │
│  Message  | [textarea]                              │
│  [Send Feedback]                                    │
│                                                     │
│ My feedback history (read-only thread)              │
└─────────────────────────────────────────────────────┘

[TAB 3: HELP CENTER]
┌─────────────────────────────────────────────────────┐
│ Curated FAQ list (managed in admin portal,          │
│   pulled from a `help_articles` table or markdown)  │
│ Search bar                                          │
└─────────────────────────────────────────────────────┘
```

### Ticket detail modal (opens from tab 1)

- Ticket header (subject, category, status pill, priority)
- Reply thread (oldest → newest)
- Compose reply textarea
- Status reference (no edit — admin owns status changes)
- "Mark as resolved" button (user can self-close)

---

## 3-Channel Feedback System

Carizma's email specified 3 channels — implementation:

### Channel 1: Always-visible Feedback Button

A small floating button (bottom-right of viewport, every portal page). Click opens a lightweight modal with:
- Single textarea: "Share your thoughts"
- Optional rating (1–5 stars)
- Submit button

Wire in `_shared/_shared.js` as `openFeedbackModal()` so it's universal. The modal markup lives in `_shared/page_foot.php` so every page includes it without per-page work.

### Channel 2: Contextual Questionnaires

Triggers after first-time completion of key workflows:
- After signing first Continuity Plan
- After designating first Continuity Steward
- After completing first Annual Re-Attestation
- After receiving first referral
- After 30 days of active use

Implementation: a `user_meta` flag (`first_completion_*_seen`) gates the modal showing. The modal is the same `support.php` feedback composer pre-filled with a context tag.

### Channel 3: Open Free-form

The Feedback tab on `support.php` itself. No trigger — user-initiated.

---

## Schema Required

```sql
-- Already specified in ADMIN-PORTAL-SPEC.md
CREATE TABLE IF NOT EXISTS complaints (
    id TEXT PRIMARY KEY,
    submitter_id TEXT NOT NULL,
    subject TEXT NOT NULL,
    body TEXT NOT NULL,
    category TEXT,                       -- 'support_ticket', 'feedback', 'feature_request', 'bug', 'praise'
    submission_channel TEXT,             -- 'button', 'questionnaire', 'freeform', 'ticket'
    status TEXT NOT NULL CHECK (status IN ('open','in_progress','resolved','closed')),
    priority TEXT DEFAULT 'normal',
    assigned_to TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    resolved_at TEXT,
    FOREIGN KEY (submitter_id) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS complaint_replies (
    id TEXT PRIMARY KEY,
    complaint_id TEXT NOT NULL,
    author_id TEXT NOT NULL,
    body TEXT NOT NULL,
    is_internal INTEGER DEFAULT 0,        -- 1 = admin-only note, hidden from submitter
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id),
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- New table for FAQs
CREATE TABLE IF NOT EXISTS help_articles (
    id TEXT PRIMARY KEY,
    category TEXT,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    role_visibility TEXT,                 -- 'all', 'practitioner', 'cs', 'ss', 'bp' (comma-separated)
    sort_order INTEGER DEFAULT 0,
    published INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);
```

**Extend `complaints.category` enum** to include feedback types (`feedback`, `feature_request`, `bug`, `praise`) — not just support tickets. Same table, same admin view, filtered by category.

**Add `submission_channel` column** to track which entry-point captured the submission (analytics).

---

## Write Actions

`_shared/save_support.php` action whitelist:

| Action | Description |
|---|---|
| `create_ticket` | New support ticket from Support page |
| `send_feedback` | Free-form feedback (from button, questionnaire, or tab) |
| `reply_ticket` | User replies to admin in an open ticket |
| `close_own_ticket` | User self-closes their ticket |
| `submit_questionnaire` | First-completion contextual survey response |

Each fires `aegis_log_activity()` for the submitter (so it appears in their activity feed) AND for the assigned admin (or all admins if unassigned).

---

## Admin Side (in `admin-portal/complaints.php`)

Already specified in `ADMIN-PORTAL-SPEC.md`. Add to that spec:
- Filter by `submission_channel` (Button / Questionnaire / Freeform / Ticket)
- Filter by `category` extended to include `feedback`, `feature_request`, `bug`, `praise`
- Internal feedback dashboard view = the existing complaints listing with a "Feedback only" filter pre-applied

This replaces the old `internal feedback dashboard` deliverable (B2.5) — it's the same page with smart filtering.

---

## Build Effort

| Task | Effort |
|---|---|
| `_shared/templates/support.php` (3 tabs + ticket modal + composer) | 3–4 hrs |
| `_shared/save_support.php` + write helpers | 2 hrs |
| 4 portal stubs | 15 min |
| Feedback Button + modal in `_shared/page_foot.php` + `_shared.js` | 1.5 hrs |
| First-completion questionnaire triggers (5 contextual ones) | 2 hrs |
| Schema additions + seed data | 1 hr |
| Help Center FAQ rendering | 1.5 hrs |
| Sidebar nav entries across 4 portals | 30 min |
| **Total** | **~12 hours** |

---

## Open Questions for Carizma

1. **Sidebar placement:** Bottom of Account group, or its own "Help" group above Settings?
2. **Feedback Button:** Visible on every page, or only after onboarding completes (first 7 days)?
3. **Help Center FAQ source:** Managed in admin portal (CRUD), or static markdown files in repo?
4. **User self-close own ticket:** Allowed, or admin-only?
5. **SLA visibility:** Show users an "expected response within 24 hours" message?

---

*This is a self-contained feature build. Reads `complaints` table that's already specified in `ADMIN-PORTAL-SPEC.md` — no schema conflicts.*
