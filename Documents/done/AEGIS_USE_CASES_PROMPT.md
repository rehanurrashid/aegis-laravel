# Aegis — Comprehensive Use Cases Generation Prompt

## Step 0 — Setup (Do This First, Before Anything Else)

### A. Clone the latest repo

```bash
cd /home/claude
rm -rf aegis
git clone https://github.com/rehanurrashid/aegis.git
cd aegis
git log -1 --oneline   # confirm latest commit
php -l _shared/models.php   # confirm PHP env works
```

### B. Deep-read the codebase — in this exact order

Do NOT start generating use cases until you have completed every read below. This is mandatory — use cases must reflect what is actually built, not what you assume.

**1. Read project knowledge docs (use `project_knowledge_search` for each):**

| Doc | What to extract |
|---|---|
| `CENTRALIZED-SYSTEM.md` | Full `_shared/` file inventory, all 42 DB table names, all 21 write endpoints + their actions, all portal page lists, all architecture patterns |
| `AEGIS-PROJECT-CONTEXT.md` | All 5 role definitions, the full continuity plan lifecycle, pricing tiers + limits, all third-party integrations (Stripe Connect, Keeper, SES, AWS S3), demo user list, all confirmed client decisions |
| `Aegis_Global_Wiring_Prompt.md` | The full write-path pattern — how page-level JS → save_*.php → models_write.php → aegis_log_activity() fan-out works |
| `Aegis_Desing_Prompt_Short.md` | Design system rules — understand what UI components exist so use cases reference real UI elements |
| `ADMIN-PORTAL-SPEC.md` | All 6 admin pages, their read helpers, write endpoints, actions, and required schema |

**2. Read the live codebase files — inspect each one:**

```bash
# Schema — every table, every column, every FK, every migration
cat _shared/db.php

# All 204 read helpers — understand what data each portal page fetches
cat _shared/models.php

# All 122 write helpers — understand every state mutation
cat _shared/models_write.php

# Seed data — the demo dataset is the canonical example of every entity
cat data/seed.json

# All 21 write endpoints — actions, validation, fan-out
ls _shared/save_*.php
for f in _shared/save_*.php; do echo "=== $f ==="; cat "$f"; done

# Sidebar nav config — every page that exists per portal, exact labels
cat _shared/sidebar.php

# Models layer helpers (pricing, overview data, settings panels)
# Already covered in models.php above
```

**3. Read every portal page to understand what data it fetches and what actions it exposes:**

```bash
# Provider portal — 18 pages
for f in provider-portal/*.php; do echo "=== $f ==="; head -80 "$f"; done

# CS portal — 12 pages
for f in continuity-steward-portal/*.php; do echo "=== $f ==="; head -80 "$f"; done

# SS portal — 11 pages
for f in support-steward-portal/*.php; do echo "=== $f ==="; head -80 "$f"; done

# BP portal — 14 pages
for f in biz-portal/*.php; do echo "=== $f ==="; head -80 "$f"; done

# Shared templates (used by all portals)
cat _shared/templates/overview.php
cat _shared/templates/activity.php
cat _shared/templates/messages.php

# Shared modals
for f in _shared/modals/*.php; do echo "=== $f ==="; cat "$f"; done
```

**4. Verify your understanding before proceeding — answer these internally:**
- How many tables exist? Name them all.
- What are the 5 user roles and what portal does each map to?
- What is the exact write-path flow from button click to DB?
- How does `activity_events` fan-out work across portals?
- What does `seed.json` tell you about real data relationships?
- What are the 6 critical incident types?
- What are the pricing tiers and their limits?
- What integrations are live vs pending?

Only after completing all of the above — proceed to use case generation.

---

## Mission

Generate exhaustive, actor-driven use cases for every Aegis portal. The output will serve as the **authoritative specification** for database design before a single migration is written. Every table column, every foreign key, every meta key must trace back to a real use case. Nothing gets built that isn't in the use cases. Nothing in the use cases gets left out of the database.

---

## Context — What Aegis Is

Healthcare continuity SaaS with 5 portals sharing one database:

| Portal | Actor | Core Job |
|---|---|---|
| **Provider (Practitioner)** | Health/wellness practitioner | Build continuity plan, designate stewards, manage practice |
| **Continuity Steward (CS)** | Designated executor of the plan | Execute the plan when a critical incident occurs |
| **Support Steward (SS)** | Trusted personal contact (family/friend) | Report incidents, coordinate communication |
| **Business Partner (BP)** | Service provider to practitioners | Offer billing, legal, IT, and other practice services |
| **Admin** | Aegis platform operator | Manage users, packages, complaints, payments |

**The central workflow:** A Practitioner creates a Continuity Plan → designates a CS + SS → uploads documents to a sealed Vault → if a critical incident occurs (death, incapacitation, missing, detainment, natural disaster, geopolitical event) → SS reports it → CS verifies and activates → Vault unseals → tasks execute → plan closes.

**All portals communicate** via a shared `activity_events` table (cross-portal inbox). Every action with cross-portal impact writes an event row for each affected user.

---

## Use Case Format

For each use case follow this exact structure:

```
### UC-[PORTAL]-[NUMBER]: [Title]

**Actor:** [Who initiates]
**Trigger:** [What causes this]
**Preconditions:** [What must be true first]
**Main Flow:**
  1. ...
  2. ...
**Alternative Flows:**
  - [A1] If X → then Y
**Postconditions:** [What changed in the DB after this completes]
**Cross-portal impact:** [Which other portals see a change and what they see]
**Data touched:**
  - Tables: [list every table read or written]
  - Columns: [list specific columns set/read]
  - Meta keys (if applicable): [user_meta / plan_meta keys]
**Business rules:**
  - [Any limits, validations, tier restrictions]
```

---

## Database Design Philosophy (Read Before Generating)

### Meta Table Pattern (WordPress-style)

Use a `{entity}_meta` table for any attribute that is:
- Optional / sparse (not every record has it)
- Likely to expand over time
- Role-specific (practitioner has different attrs than CS)
- Configuration-like (preferences, flags, settings)

**Core tables + meta pairs to plan for:**

```
users               → user_meta          (meta_key, meta_value, meta_type)
continuity_plans    → plan_meta          (meta_key, meta_value)
critical_incidents  → incident_meta      (meta_key, meta_value)
vault_items         → vault_item_meta    (meta_key, meta_value)
bp_contracts        → contract_meta      (meta_key, meta_value)
complaints          → complaint_meta     (meta_key, meta_value)
```

**Columns that MUST stay on the main table** (indexed, queried, joined):
- IDs, foreign keys, status enums, timestamps, booleans used in WHERE clauses

**Columns that go in meta** (sparse, variable, rarely filtered):
- Profile extended fields (bio, specialties, certifications, languages)
- Preferences and settings
- Role-specific profile data (practitioner fee structure, CS emergency protocols)
- Any field that might need to grow without migration

### Scalability Rules
1. **Every status column** uses an ENUM or a `tinyint` lookup — no free-text statuses
2. **Every monetary value** stored in cents (integer) — no decimals
3. **Every timestamp** is UTC, stored as `timestamp` type
4. **Soft deletes** on all user-facing records (`deleted_at` nullable)
5. **All FKs indexed**
6. **Polymorphic relations** where one table relates to multiple other tables (e.g. `activity_events` links to plans, incidents, vault items, messages — use `linkable_type + linkable_id`)
7. **JSON columns only for truly unstructured data** (e.g. Stripe webhook payload, meta_value when storing arrays) — not as a shortcut for lazy schema design

---

## Portals to Cover — Use Cases Needed

### PORTAL 1 — PROVIDER (Practitioner)

Cover every feature visible in the sidebar and every action on every page:

**Pages:** Overview, Dashboard, Edit Profile, Continuity Plan (Builder), Continuity Stewards, Support Stewards, Network, My Services, Support & Services (Job Postings), Referrals, Document Vault, Important Documents, Messages, Activity Log, News & Events, Finances, Settings

**Minimum use cases to cover:**

```
Authentication & Onboarding
- UC-PRV-001: Practitioner registers (selects plan, enters profile)
- UC-PRV-002: Practitioner logs in
- UC-PRV-003: Practitioner upgrades from Access → Practice tier
- UC-PRV-004: Practitioner downgrades plan
- UC-PRV-005: Practitioner resets password

Profile Management
- UC-PRV-010: Practitioner completes basic profile (bio, location, contact)
- UC-PRV-011: Practitioner adds credentials/licenses
- UC-PRV-012: Practitioner sets specialties (from taxonomy)
- UC-PRV-013: Practitioner sets services offered (from taxonomy)
- UC-PRV-014: Practitioner sets therapeutic approaches/frameworks
- UC-PRV-015: Practitioner updates fee & insurance information
- UC-PRV-016: Practitioner sets availability and accepting-clients status
- UC-PRV-017: Practitioner enables/disables Integrative Business Services Mode
- UC-PRV-018: Practitioner previews public profile
- UC-PRV-019: Practitioner updates privacy settings
- UC-PRV-020: Practitioner adds/updates education & training

Continuity Plan
- UC-PRV-030: Practitioner creates new Continuity Plan (draft)
- UC-PRV-031: Practitioner configures which incident types are active
- UC-PRV-032: Practitioner sets required documentation per incident type
- UC-PRV-033: Practitioner adds tasks for Continuity Steward
- UC-PRV-034: Practitioner adds tasks for Support Steward
- UC-PRV-035: Practitioner sends plan for signature
- UC-PRV-036: Practitioner signs Continuity Plan
- UC-PRV-037: Practitioner views signed plan in Important Documents
- UC-PRV-038: Practitioner initiates Annual Re-Attestation
- UC-PRV-039: Practitioner completes Annual Re-Attestation checklist
- UC-PRV-040: Practitioner attests Vault is complete

Continuity Steward Management
- UC-PRV-050: Practitioner invites existing Aegis user as Primary CS
- UC-PRV-051: Practitioner invites external contact as CS (not yet on Aegis)
- UC-PRV-052: Practitioner designates Alternate CS
- UC-PRV-053: Practitioner sets per-incident CS authorization matrix
- UC-PRV-054: Practitioner copies tasks from Primary CS to Alternate CS
- UC-PRV-055: Practitioner views CS countersignature status
- UC-PRV-056: Practitioner removes a CS assignment

Support Steward Management
- UC-PRV-060: Practitioner designates Primary SS
- UC-PRV-061: Practitioner designates Alternate SS
- UC-PRV-062: Practitioner copies tasks from Primary SS to Alternate SS
- UC-PRV-063: Practitioner removes SS assignment

Document Vault
- UC-PRV-070: Practitioner uploads document to Vault (4 zones: credentials, client roster, emergency docs, standard records)
- UC-PRV-071: Practitioner sets vault permissions per document
- UC-PRV-072: Practitioner manages Client Roster (add/edit/mark Priority Response)
- UC-PRV-073: Practitioner marks client as discharged/closed
- UC-PRV-074: Practitioner attests Vault is complete (fans out to CS+SS)
- UC-PRV-075: Practitioner views Vault access log

Important Documents
- UC-PRV-080: Practitioner views all signed documents
- UC-PRV-081: Practitioner uploads additional support document
- UC-PRV-082: Practitioner requests document from CS/SS

Activation (Critical Incident from Provider side)
- UC-PRV-090: Practitioner self-reports a critical incident
- UC-PRV-091: Practitioner activates Continuity Support (selects incident type + documentation)

Network
- UC-PRV-100: Practitioner sends connection request to another provider
- UC-PRV-101: Practitioner accepts/declines incoming connection request
- UC-PRV-102: Practitioner removes network connection
- UC-PRV-103: Practitioner invites external provider to Aegis
- UC-PRV-104: Practitioner views recommended shadow providers (AI)
- UC-PRV-105: Practitioner saves a shadow provider
- UC-PRV-106: Practitioner configures AI shadow network settings
- UC-PRV-107: Practitioner browses Business Partners in network
- UC-PRV-108: Practitioner sends referral to network provider
- UC-PRV-109: Practitioner views referral details

My Services (IBS Mode)
- UC-PRV-120: Practitioner enables service mode
- UC-PRV-121: Practitioner creates a service offering
- UC-PRV-122: Practitioner edits/deactivates a service
- UC-PRV-123: Practitioner receives a service request from another provider
- UC-PRV-124: Practitioner accepts/declines service request
- UC-PRV-125: Practitioner books a session for a service
- UC-PRV-126: Practitioner views service booking history

Support & Services (Job Postings → Support Requests)
- UC-PRV-130: Practitioner creates a Support Request for a BP
- UC-PRV-131: Practitioner views submitted proposals on a request
- UC-PRV-132: Practitioner accepts a proposal → creates contract
- UC-PRV-133: Practitioner declines a proposal
- UC-PRV-134: Practitioner closes a Support Request
- UC-PRV-135: Practitioner manages active contract (view milestones, approve)
- UC-PRV-136: Practitioner pays a BP invoice

Finances
- UC-PRV-140: Practitioner views subscription status + invoices
- UC-PRV-141: Practitioner adds/updates payment method
- UC-PRV-142: Practitioner views MAAT CS Service payment model
- UC-PRV-143: Practitioner views CS service fee billing history

CEU Tracking
- UC-PRV-150: Practitioner adds a CEU entry (category, hours, sync/async, cycle)
- UC-PRV-151: Practitioner views CEU progress by jurisdiction
- UC-PRV-152: Practitioner sets CEU requirements for their license

Messages & Activity
- UC-PRV-160: Practitioner sends message to CS/SS/BP
- UC-PRV-161: Practitioner views activity feed (filtered by type)
- UC-PRV-162: Practitioner marks activity as read

Settings
- UC-PRV-170: Practitioner updates notification preferences
- UC-PRV-171: Practitioner changes password / enables 2FA
- UC-PRV-172: Practitioner requests account closure
- UC-PRV-173: Practitioner exports all data
```

---

### PORTAL 2 — CONTINUITY STEWARD (CS)

**Pages:** Overview, Dashboard, Edit Profile, My Tasks, My Practitioners (Providers), Continuity Management (active incidents), Important Documents, Document Vault, Messages, Activity Log, Finances, Settings

```
Onboarding
- UC-CS-001: CS registers via invitation link from Practitioner
- UC-CS-002: CS registers as Business CS (self-initiated)
- UC-CS-003: Invited CS upgrades to Business CS (paid plan)

Profile Management
- UC-CS-010: CS completes profile (bio, credentials, services offered)
- UC-CS-011: CS sets emergency protocols / continuity plan capabilities
- UC-CS-012: CS sets fee structure (retainer, activation fee, hourly, pro bono)
- UC-CS-013: CS sets service coverage areas
- UC-CS-014: CS updates availability

Provider Relationships
- UC-CS-020: CS views list of practitioners they serve
- UC-CS-021: CS views practitioner's Continuity Plan and documents
- UC-CS-022: CS countersigns the Continuity Plan
- UC-CS-023: CS requests role change with a practitioner
- UC-CS-024: CS views Vault attestation status per practitioner
- UC-CS-025: CS connects to new practitioner

My Tasks (Standby)
- UC-CS-030: CS views all standby tasks organized by practitioner
- UC-CS-031: CS marks a standby task complete
- UC-CS-032: CS adds a note to a task
- UC-CS-033: CS requests a task due-date extension
- UC-CS-034: CS flags a task issue
- UC-CS-035: CS completes Annual Re-Attestation (reviews responsibilities, confirms contact info current)

Continuity Management (Active Incident)
- UC-CS-040: CS receives incident alert from SS
- UC-CS-041: CS verifies the critical incident (reviews documentation)
- UC-CS-042: CS activates Continuity Support (unlocks vault, generates incident tasks)
- UC-CS-043: CS works through incident task list (mark complete, add notes)
- UC-CS-044: CS escalates to Aegis team
- UC-CS-045: CS adds update to incident timeline
- UC-CS-046: CS closes the incident
- UC-CS-047: CS views incident audit log

Document Vault (sealed / unsealed)
- UC-CS-050: CS views sealed vault (confirms nothing accessible in standby)
- UC-CS-051: CS accesses vault after verified incident (auto-unlocked)
- UC-CS-052: CS downloads specific vault document
- UC-CS-053: CS views vault access log

Important Documents
- UC-CS-060: CS views all plan documents per practitioner
- UC-CS-061: CS requests a new document
- UC-CS-062: CS uploads a support document
- UC-CS-063: CS re-attests annual agreement

Finances
- UC-CS-070: CS views service fee invoices per practitioner
- UC-CS-071: CS requests fee amendment
- UC-CS-072: CS connects payout account (Stripe Connect)
- UC-CS-073: CS views payout history
- UC-CS-074: CS sets fee type (retainer, activation, hourly, pro bono)
- UC-CS-075: CS views CS invoice history

Messages & Activity
- UC-CS-080: CS sends message to practitioner / SS / Aegis team
- UC-CS-081: CS views activity log (filters by practitioner / event type)

Settings
- UC-CS-090: CS updates notification preferences
- UC-CS-091: CS changes password / 2FA
- UC-CS-092: CS pauses CS status (temporary)
- UC-CS-093: CS resigns from a practitioner's plan
- UC-CS-094: CS closes account
```

---

### PORTAL 3 — SUPPORT STEWARD (SS)

**Pages:** Overview, Dashboard, Edit Profile, My Practitioners (Providers), Continuity Stewards (CSs I work with), My Tasks, Critical Incident Log, Important Documents, Messages, Activity Log, Settings

```
Onboarding
- UC-SS-001: SS registers via invitation link from Practitioner
- UC-SS-002: SS completes basic profile

Provider Relationships
- UC-SS-010: SS views list of practitioners they support
- UC-SS-011: SS views practitioner's Continuity Plan (read-only)
- UC-SS-012: SS acknowledges plan awareness
- UC-SS-013: SS views assigned tasks per practitioner
- UC-SS-014: SS views Vault attestation status per practitioner
- UC-SS-015: SS views assigned CS for each practitioner

My Tasks
- UC-SS-020: SS views standby task list (pre-populated from plan)
- UC-SS-021: SS marks a task complete with completion date
- UC-SS-022: SS adds a task note
- UC-SS-023: SS adds a custom task

Critical Incident Reporting
- UC-SS-030: SS reports a critical incident (selects type, enters details)
- UC-SS-031: SS uploads supporting documentation (death certificate, police report, etc.)
- UC-SS-032: SS views active incident dashboard
- UC-SS-033: SS views incident status updates from CS
- UC-SS-034: SS adds update to active incident
- UC-SS-035: SS contacts Aegis team via Escalate
- UC-SS-036: SS views closed incident history

Continuity Steward Coordination
- UC-SS-040: SS contacts assigned CS (via messages)
- UC-SS-041: SS views CS task progress on active incident
- UC-SS-042: SS views CS contact details

Important Documents
- UC-SS-050: SS views all documents shared with them
- UC-SS-051: SS downloads a document
- UC-SS-052: SS uploads a support document

Messages & Activity
- UC-SS-060: SS sends message to Practitioner / CS / Aegis team
- UC-SS-061: SS views activity log filtered by event type

Settings
- UC-SS-070: SS updates notification preferences
- UC-SS-071: SS changes password / 2FA
- UC-SS-072: SS closes account
```

---

### PORTAL 4 — BUSINESS PARTNER (BP)

**Pages:** Overview, Dashboard, Edit Profile, Find Jobs (Support Requests), Proposals, Contracts, Milestones, Invoices, Finances, Payment Setup, Team (Agency only), Messages, Activity Log, Settings

```
Onboarding
- UC-BP-001: BP registers as Freelancer
- UC-BP-002: BP registers as Agency
- UC-BP-003: BP completes company profile
- UC-BP-004: BP sets up Stripe Connect payout account

Profile Management
- UC-BP-010: BP sets services offered + fees
- UC-BP-011: BP sets business identity certifications (WBE, MBE, B-Corp, etc.)
- UC-BP-012: BP sets service coverage (state-by-state)
- UC-BP-013: BP sets industry specializations
- UC-BP-014: BP sets portfolio / case studies
- UC-BP-015: BP previews business profile visible to practitioners

Find Jobs (Support Requests)
- UC-BP-020: BP browses open Support Requests from practitioners
- UC-BP-021: BP filters requests (category, budget, type)
- UC-BP-022: BP saves a Support Request for later
- UC-BP-023: BP submits a proposal on a Support Request
- UC-BP-024: BP views proposal status (pending / accepted / declined)
- UC-BP-025: BP withdraws a proposal

Contracts
- UC-BP-030: BP receives accepted proposal → contract created
- UC-BP-031: BP views contract details (scope, billing type, timeline)
- UC-BP-032: BP updates contract status (active / completed)
- UC-BP-033: BP views contract milestones

Milestones
- UC-BP-040: BP creates a milestone on a contract
- UC-BP-041: BP submits milestone for review
- UC-BP-042: BP views milestone approval status
- UC-BP-043: BP marks milestone delivered

Invoices
- UC-BP-050: BP creates invoice (line items, amounts, related contract)
- UC-BP-051: BP sends invoice to practitioner
- UC-BP-052: BP views payment status (unpaid / paid / overdue)
- UC-BP-053: BP views invoice history
- UC-BP-054: BP voids an invoice

Finances
- UC-BP-060: BP views YTD earnings summary
- UC-BP-061: BP views revenue by month (chart)
- UC-BP-062: BP views payout history
- UC-BP-063: BP downloads tax documents

Payment Setup
- UC-BP-070: BP connects Stripe Express account
- UC-BP-071: BP views Stripe Connect status (connected / restricted / disconnected)
- UC-BP-072: BP updates banking/payout details

Team (Agency only)
- UC-BP-080: Agency owner invites team member
- UC-BP-081: Agency owner sets team member role/permissions
- UC-BP-082: Agency owner removes team member
- UC-BP-083: Team member logs in (scoped view)

Messages & Activity
- UC-BP-090: BP sends message to practitioner
- UC-BP-091: BP views activity log

Settings
- UC-BP-100: BP updates notification preferences
- UC-BP-101: BP changes password / 2FA
- UC-BP-102: BP closes account
- UC-BP-103: BP updates billing info
- UC-BP-104: BP changes plan (BP Basic → BP Pro or downgrade)
```

---

### PORTAL 5 — ADMIN

**Pages:** Dashboard, Packages, Users, Roles, Payments, Complaints

```
Dashboard
- UC-ADM-001: Admin views platform stats (total users per role, signups 30d, MRR, ARR, active plans, active incidents, open complaints)
- UC-ADM-002: Admin views signup trend chart by role
- UC-ADM-003: Admin views revenue trend (MRR over 12 months)
- UC-ADM-004: Admin views active incidents across platform
- UC-ADM-005: Admin views recent complaints queue

Packages
- UC-ADM-010: Admin views all subscription tiers (Access, Practice, BP Basic, BP Pro) with subscriber counts
- UC-ADM-011: Admin updates monthly/annual price for a tier
- UC-ADM-012: Admin toggles a feature flag for a tier (e.g. enable services mode for Access)
- UC-ADM-013: Admin sets tier limits (max CS count, max SS count, max team members)

Users
- UC-ADM-020: Admin searches users (by name, email, role, status)
- UC-ADM-021: Admin views full user profile + plan + assignments
- UC-ADM-022: Admin views user activity log
- UC-ADM-023: Admin locks a user account (with reason)
- UC-ADM-024: Admin unlocks a user account
- UC-ADM-025: Admin forces password reset for a user
- UC-ADM-026: Admin changes user role (with audit trail)
- UC-ADM-027: Admin deactivates a user account (soft delete)
- UC-ADM-028: Admin restores a deactivated account
- UC-ADM-029: Admin impersonates a user for debugging (dev only)

Roles & Permissions
- UC-ADM-030: Admin views all roles (system + custom) with user counts
- UC-ADM-031: Admin creates a custom role
- UC-ADM-032: Admin sets permissions for a custom role
- UC-ADM-033: Admin deletes a custom role (only if no users assigned)

Payments
- UC-ADM-040: Admin views full payment ledger (all portals)
- UC-ADM-041: Admin views failed payments queue
- UC-ADM-042: Admin retries a failed payment
- UC-ADM-043: Admin processes a refund (full or partial)
- UC-ADM-044: Admin views pending BP/CS payouts
- UC-ADM-045: Admin manually releases a payout
- UC-ADM-046: Admin views Stripe webhook event log

Complaints
- UC-ADM-050: Admin views all complaints (filtered by status, priority, category)
- UC-ADM-051: Admin views full complaint detail + reply thread
- UC-ADM-052: Admin assigns complaint to a staff member
- UC-ADM-053: Admin replies to a complaint (visible to user)
- UC-ADM-054: Admin adds internal note (admin-only, not visible to user)
- UC-ADM-055: Admin changes complaint status (open → in_progress → resolved → closed)
- UC-ADM-056: Admin escalates complaint to senior review
- UC-ADM-057: Admin views complaint resolution metrics
```

---

### CROSS-PORTAL USE CASES

These span multiple portals and define the communication layer:

```
Cross-Portal Fan-out
- UC-XP-001: Activity event created → fans out to all recipients simultaneously
- UC-XP-002: CS countersigns plan → Practitioner sees "Countersigned" chip on CS card
- UC-XP-003: Vault attested by Practitioner → CS and SS both see "Vault Attested [date]" chip
- UC-XP-004: SS reports incident → CS receives alert badge + notification
- UC-XP-005: CS verifies incident → Practitioner activity feed updated
- UC-XP-006: Incident activated → CS vault unlocks + incident tasks generated for CS
- UC-XP-007: Incident closed → All parties notified, vault re-sealed
- UC-XP-008: Practitioner signs plan → CS + SS both get "Plan Ready" notification
- UC-XP-009: CS completes task → Practitioner dashboard reflects completion
- UC-XP-010: BP proposal accepted → BP receives notification + contract auto-created
- UC-XP-011: BP invoice sent → Practitioner receives notification + invoice in Finances
- UC-XP-012: Practitioner pays invoice → BP receives payout notification
- UC-XP-013: CS requests fee amendment → Practitioner notified to review
- UC-XP-014: Annual Re-Attestation due → Practitioner, CS, SS all notified

Shared Data Reads
- UC-XP-020: CS portal reads Practitioner's plan tasks (assigned to CS)
- UC-XP-021: SS portal reads Practitioner's plan tasks (assigned to SS)
- UC-XP-022: CS portal reads Vault items (only when incident active)
- UC-XP-023: Admin portal reads all incidents across all practitioners
- UC-XP-024: Admin portal reads all activity_events (global audit)
- UC-XP-025: BP portal reads Support Requests posted by any Practitioner
- UC-XP-026: Public profile reads Practitioner data (specialty, availability, bio, services)
```

---

## Output Requirements

For every use case:

1. **Complete the use case template** (actor, trigger, preconditions, main flow, alternatives, postconditions, cross-portal impact, data touched, business rules)

2. **After completing all use cases for a portal, generate a Data Map table:**
```
| UC | Tables Written | Tables Read | Meta Keys Set | Cross-Portal Events |
|---|---|---|---|---|
| UC-PRV-030 | continuity_plans, plan_meta | users | plan_meta.draft_started_at | None |
```

3. **After ALL portals, generate the Master Entity List:**
```
| Entity | Main Table | Meta Table | Relates To | Owned By Portal |
|---|---|---|---|---|
| Continuity Plan | continuity_plans | plan_meta | users, plan_stewards, plan_tasks | Practitioner (read: CS, SS) |
```

4. **Final output — Database Design Recommendations:**
- Which columns belong on the main table vs meta
- Which entities need polymorphic relationships
- Which junction tables are needed
- Suggested indexes (beyond FKs)
- Enum values for every status column
- Any columns that appear across multiple use cases that should be centralized

---

## Working Rules

- **Complete Step 0 in full before generating a single use case** — every use case must be grounded in what is actually in the repo files and seed data, not assumptions
- **Use `project_knowledge_search`** to cross-reference `CENTRALIZED-SYSTEM.md` and `AEGIS-PROJECT-CONTEXT.md` any time you are unsure about a table name, column name, or portal behavior
- **Check `seed.json` for real data examples** — if you are unsure what fields a record has, look at the actual seed data for that entity (e.g. a `continuity_plans` entry shows you which columns are always populated vs sometimes null)
- **Check the relevant `save_*.php` endpoint** before writing the Data Touched section — the endpoint's action list tells you exactly which columns get written in that flow
- **Check `models.php`** before writing the Tables Read section — the read helper for that page tells you exactly which tables are joined
- **Do not invent features** not present in the existing system — if a page doesn't exist in a portal folder or isn't in the sidebar nav, don't create a use case for it
- **Do not skip the Data Touched section** — every table and column must be named explicitly, pulled from actual db.php schema
- **Cross-portal impact is mandatory** — every use case must declare what other portals see as a result, referencing real `activity_events` action strings from the codebase
- **Meta keys must be named** — not just "stored in user_meta" but the exact key name e.g. `user_meta: practitioner_specialties (JSON array)`
- **One portal at a time** — complete all use cases + Data Map for one portal before starting the next
- **Flag any contradiction** between portals — if two use cases update the same column differently, call it out explicitly
- **Business rules are not optional** — tier restrictions, limits, and validation rules belong in every use case and must match what's in `db.php` CHECK constraints and `models.php` tier-limit logic

---

## Start Here

**Step 0 first** — clone the repo and read every file as instructed above. Do not skip or abbreviate this step.

Once Step 0 is complete, confirm it by outputting a one-paragraph summary of what you found: how many tables, how many portal pages per portal, what the write-path looks like, and what the seed data tells you about real entity relationships.

Then begin with **Portal 1 — Provider**, starting with Authentication & Onboarding use cases (UC-PRV-001 through UC-PRV-005). Ground every field name and table reference in what you read from `db.php`, `models.php`, `models_write.php`, `save_*.php`, and `seed.json`. Complete each portal fully (all use cases + Data Map table) before moving to the next. Generate the Master Entity List and Database Design Recommendations last.
