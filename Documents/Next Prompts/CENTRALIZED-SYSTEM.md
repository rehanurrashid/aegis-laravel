# Aegis — Centralized System Reference

**Last updated:** June 19, 2026 · **Schema version:** v15 · **Repo:** [rehanurrashid/aegis](https://github.com/rehanurrashid/aegis)

This document is the authoritative inventory of the `_shared/` system and how every portal page wires into it. **Read this before writing or editing code** — it tells you which helper to call, which endpoint to POST to, and which partial to include rather than reinventing.

---

## Table of Contents

1. [Repo Anatomy](#1-repo-anatomy)
2. [The `_shared/` Inventory](#2-the-shared-inventory)
3. [Architecture Patterns](#3-architecture-patterns)
4. [Schema & Database](#4-schema--database)
5. [Portal Pages — File-by-File](#5-portal-pages--file-by-file)
6. [Worked Examples](#6-worked-examples)
7. [Conventions & Gotchas](#7-conventions--gotchas)

---

## 1. Repo Anatomy

```
aegis/
├── _shared/                       ← shared system (48 files)
│   ├── modals/                    ← cross-portal modals (4 files)
│   ├── templates/                 ← cross-portal page templates (3 files)
│   └── [44 .php files]
├── _shared.css                    ← single design-system stylesheet (170 KB)
├── _shared.js                     ← single client-side bundle (50 KB)
│
├── provider-portal/               ← 18 pages — Practitioner
├── continuity-steward-portal/     ← 12 pages — Continuity Steward
├── support-steward-portal/        ← 11 pages — Support Steward
├── biz-portal/                    ← 14 pages — Business Partner
├── admin-portal/                  ← 6 pages — Admin (greenfield, currently empty)
│
├── public/                        ← public profile pages (4 files)
│   ├── provider.php
│   ├── continuity_steward.php
│   ├── support_steward.php
│   └── business.php
│
├── Documents/                     ← project docs + MAAT Guidelines PDF
├── data/                          ← runtime data
│   ├── seed.json                  ← demo data source of truth
│   └── aegis.sqlite               ← runtime DB (auto-rebuilt from seed)
├── backup/                        ← rolling backups
│
├── demo.php                       ← demo control panel (mode switcher)
├── login.php                      ← auth landing
├── onboarding.php                 ← signup wizard
├── pricing.php                    ← marketing pricing page
├── reset.php                      ← GET /reset.php?token=aegis-demo-reset
├── router.php                     ← PHP built-in server router
├── aegis-bg.svg                   ← brand background pattern
├── aegis-favicon.svg              ← brand favicon
└── .htaccess                      ← rewrite rules for clean URLs
```

**Key principle:** Each portal folder contains only thin page-level files (~50–2000 lines each). All business logic, chrome, design tokens, write handlers, and shared partials live in `_shared/`. A typical portal page is essentially: `require '_shared/page_head.php' → page body → require '_shared/page_foot.php'`.

---

## 2. The `_shared/` Inventory

48 files total. Grouped by purpose below.

### 2.1 Chrome & Layout (8 files)

These render the consistent shell around every authenticated page. Order of inclusion matters — see [§3.1](#31-chrome-order).

| File | Lines | Purpose |
|---|---|---|
| `page_head.php` | 47 | DOCTYPE, `<head>`, meta tags, `_shared.css` import. **Never write your own DOCTYPE.** |
| `page_foot.php` | 25 | `</body>`, `_shared.js` import, footer scripts. |
| `page.php` | 30 | Bootstrap wrapper — sets `$current_user`, role checks, redirects. |
| `theme_loader.php` | 140 | Loads CSS variables based on `?as=` demo flag + tier. |
| `sidebar.php` | 864 | **Universal sidebar.** Auto-detects portal folder, renders role-specific nav. Handles `?tier=`, `?services=`, `?invited=`, `?emergency=` demo flags. |
| `header.php` | 1541 | Top bar — search, notifications dropdown, user menu, brand pill. Universal across portals. |
| `layout.php` | 380 | Page-body wrapper, content grid, sidebar overlay for mobile. |
| `demo_switcher.php` | 130 | Bottom-right demo widget — switch between `p_sarah` / `cs_marcus` / `ss_linda` / `bp_acme` / `bp_jamal`. |

### 2.2 Core Data Layer (4 files)

The single source of truth for data access. Page-level files **never** write raw SQL — they call helpers from these files.

| File | Lines | Purpose |
|---|---|---|
| `db.php` | 1061 | PDO connection, full schema (42 tables, see [§4](#4-schema--database)), migration runner. Loaded by `page.php`. |
| `seed.php` | 1155 | Reads `data/seed.json`, hydrates DB. Idempotent — safe to call repeatedly. Triggered by `reset.php`. |
| `models.php` | 6471 | **Read path — 204 helpers.** All `aegis_get_*`, `aegis_list_*`, `aegis_count_*` functions. Also contains `aegis_overview_data()`, `aegis_pricing_*()`, and role-specific data builders. |
| `models_write.php` | 5117 | **Write path — 122 helpers.** All `aegis_create_*`, `aegis_save_*`, `aegis_update_*`, `aegis_log_activity` functions. Every state-mutating action lives here. |

### 2.3 Write Endpoints (21 files)

All client-side `fetch()` calls POST to these JSON endpoints. Each follows the same shape: auth check → action whitelist → call `models_write.php` helper → return `{ok:true, ...}` or `{ok:false, error:'...'}`.

| Endpoint | Domain | Key actions |
|---|---|---|
| `save_activity.php` | Activity feed | `mark_read`, `dismiss`, `filter` |
| `save_certify.php` | Steward certification | `certify_tasks`, `flag_issue`, `request_change` |
| `save_document.php` | Important Documents | `request_doc`, `sign_doc`, `archive_doc` |
| `save_event.php` | News & Events | `rsvp`, `comment`, `react`, `post_event` |
| `save_finance.php` | Provider finances | `set_payment_method`, `update_payment_model` |
| `save_incident.php` | Critical Incidents | `report_incident`, `verify_incident`, `escalate`, `close_incident`, `add_update` (the largest write file at 776 lines) |
| `save_invoice.php` | BP invoicing | `create_invoice`, `mark_paid`, `void` |
| `save_job.php` | Support Requests | `create_job`, `close_job`, `submit_proposal`, `accept_proposal` |
| `save_message.php` | Messaging | `send_message`, `mark_read`, `archive_thread`, `star` |
| `save_network.php` | Network/Referrals | `accept_request`, `decline_request`, `invite_provider` |
| `save_payment.php` | Payment processing | `attach_card`, `initiate_payout`, `request_refund` |
| `save_plan.php` | Continuity Plan | `save_draft`, `finalize_sign`, `set_authorization`, `add_task`, `attest_vault`, `begin_annual_review`, 11 total actions |
| `save_pref.php` | User preferences | `update_pref`, `update_notification` |
| `save_profile.php` | Profile editing | `update_basic`, `update_specialty`, `update_credential`, etc. |
| `save_referral.php` | Referrals | `send_referral`, `accept_referral`, `complete_referral` |
| `save_service.php` | My Services | `create_service`, `request_service`, `book_session` |
| `save_ss_provider.php` | SS provider mgmt | `add_provider`, `acknowledge_plan` |
| `save_steward.php` | CS/SS designation | `add_steward`, `request_role_change`, `set_authorization`, `copy_tasks` |
| `save_task.php` | Plan/Incident tasks | `update_task`, `complete_task`, `add_note` |
| `save_team.php` | BP Agency team | `invite_member`, `update_role`, `remove_member` |
| `save_vault.php` | Document Vault | `upload`, `set_permissions`, `seal`, `unseal_on_incident` |
| `webhook_stripe.php` | Stripe webhooks | Inbound Stripe events (charge.succeeded, etc.) |

### 2.4 Shared Templates (3 files)

These are full page bodies included by thin role-specific stubs. Same template renders correctly for any portal — context is detected via `$current_user['role']`.

| File | Lines | Used by |
|---|---|---|
| `templates/overview.php` | 600 | `provider-portal/overview.php`, `continuity-steward-portal/overview.php`, `support-steward-portal/overview.php`, `biz-portal/overview.php` |
| `templates/activity.php` | 700 | `*/activity.php` across all 4 portals |
| `templates/messages.php` | 2100 | `*/messages.php` across all 4 portals |

A typical stub is a 3-liner:
```php
<?php declare(strict_types=1);
require_once __DIR__ . '/../_shared/templates/overview.php';
```

### 2.5 Shared Modals (4 files)

Cross-portal modals — included where needed.

| File | Used by |
|---|---|
| `modals/job_detail_modal.php` | Provider job-postings, BP find-jobs |
| `modals/proposal_modal.php` | Provider job-postings, BP proposals |
| `modals/referral_modal.php` | Provider referrals, CS providers (40 KB — the universal referral compose UI) |
| `modals/upgrade_cs_modal.php` | Provider continuity-stewards page (CS designation flow) |

### 2.6 Helpers & UI Utilities (6 files)

| File | Purpose |
|---|---|
| `icons.php` | **`aegis_icon($name, $size)`** — the canonical icon helper. ~200 named SVGs. Never write inline `<svg>`. |
| `ui.php` | Misc UI helpers (badge renderers, status chips, time-ago strings) |
| `activity_body.php` | Reusable activity-feed row renderer |
| `profile_strip.php` | Compact profile-header strip (used in CS/SS provider cards) |
| `pricing_data.php` | Single source of truth for tier pricing (Continuity Access $39 / Continuity Practice $79) |
| `public_chrome.php` | Public-page chrome + CSS for `/public/*.php` pages (no auth required) |
| `public_profile.php` | Public profile renderer included by all 4 `/public/<role>.php` pages |

### 2.7 Cross-cutting Singletons (2 files)

| File | Purpose |
|---|---|
| `_shared.css` (root) | **Single design-system stylesheet.** 170 KB. All CSS variables (`--gold-dark`, `--surface-2`, etc.), every component class (`.btn`, `.hero-banner`, `.card`, `.modal-overlay`, `.toggle`, etc.). No portal has its own CSS. |
| `_shared.js` (root) | **Single client-side bundle.** 50 KB. `openModal()`, `closeModal()`, `showToast()`, `confirmAction()`, `_showUpgradeModal()`, `viewPartyProfile()`, `navigateTo()`, demo-flag persistence, Dropzone init, scroll indicators. No portal has its own JS bundle. |

---

## 3. Architecture Patterns

### 3.1 Chrome Order

Every authenticated portal page MUST follow this include order:

```
page_head.php       ← DOCTYPE, <head>, CSS link
theme_loader.php    ← CSS variables for current tier/demo flags
sidebar.php         ← left rail navigation
<div class="sidebar-overlay"></div>
<div class="main-content">
  header.php        ← top bar
  <div class="page-body">
    [PAGE BODY GOES HERE]
  </div>
</div>
page_foot.php       ← </body>, JS bundle
```

**Common mistakes:** writing your own `<!DOCTYPE html>`, importing CSS twice, swapping `sidebar.php` and `header.php` order.

### 3.2 Write Path

Every state-mutating action flows through 4 layers:

```
[1] Page-local fetch wrapper      provider-portal/dashboard.php
       ↓ POST to /_shared/save_<domain>.php
[2] Shared write endpoint          _shared/save_plan.php
       ↓ calls helper
[3] Write helper                   _shared/models_write.php → aegis_practitioner_attest_vault()
       ↓ UPDATE + fan-out
[4] Activity log                   aegis_log_activity() → activity_events row
       ↓ on success
[5] Client                         showToast() + reload
```

**Rule:** Page files never UPDATE / INSERT directly. They POST to a shared endpoint.

### 3.3 Data Display Pattern

Never echo PHP values inside `<script>` blocks. Use `data-*` attributes:

```php
<?php $payload = json_encode($data, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS); ?>
<div id="myData" data-payload="<?= htmlspecialchars($payload, ENT_QUOTES) ?>"></div>
<script>
  const data = JSON.parse(document.getElementById('myData').dataset.payload);
</script>
```

`JSON_HEX_TAG` is the permanent fix for `</script>` injection.

### 3.4 Cross-Portal Communication via Activity Events

Portals don't talk to each other directly. Every write helper with cross-portal impact calls `aegis_log_activity()` with one row per recipient. Example: when a Practitioner attests their Vault, the helper logs:
- 1 row for the Practitioner (`portal='provider'`)
- N rows for assigned CSs (`portal='continuity_steward'`)
- M rows for assigned SSs (`portal='support_steward'`)

Each recipient's activity feed picks up their row.

### 3.5 Demo Flag Persistence

`?as=p_sarah` · `?tier=access|practice` · `?services=0|1` · `?invited=1` · `?emergency=1`

These persist through navigation via `_shared.js` which appends them to every `<a>` and form submission. Reset via `demo.php` or `/reset.php?token=aegis-demo-reset`.

---

## 4. Schema & Database

**Schema version: v15** · 42 tables · SQLite via PDO · Migrations applied idempotently via `aegis_migrate_schema()` in `db.php`.

### Core tables

| Table | Purpose |
|---|---|
| `users` | All accounts (practitioner, continuity_steward, support_steward, business_partner). Role-specific columns coexist. |
| `user_roles` | Many-to-many for users with multiple role types |
| `user_sessions` | Auth session tokens |
| `user_preferences` | Per-user prefs (theme, notifications, language) |

### Continuity Plan tables

| Table | Purpose |
|---|---|
| `continuity_plans` | One per practitioner — status, version, signed_at, vault_attested_at (v15), annual_review_date |
| `plan_stewards` | Junction — links CSs and SSs to a plan with role (primary/alternate) |
| `plan_tasks` | Standby preparation tasks (the "before" list) |
| `plan_incident_configs` | Per-incident-type config — which incidents are enabled, required docs, authorized CS IDs |
| `critical_incidents` | Reported incidents — type, status (active/closed), reporter, verifier |
| `incident_tasks` | Auto-generated tasks for an active incident (the "during" list) |

> **Important:** `plan_tasks` and `incident_tasks` are semantically different — never query as one list.

### Vault & Documents

| Table | Purpose |
|---|---|
| `vault_items` | Provider's sealed documents — credentials, client roster, emergency docs |

### Communication

| Table | Purpose |
|---|---|
| `activity_events` | The cross-portal feed. Schema: `user_id, portal, event_type, event_subtype, action, title, description, link_id, link_type, related_user_id` |
| `message_threads` / `messages` | Messaging |
| `network_connections` / `network_requests` | Network graph |
| `shadow_connections` | AI shadow-network matches |
| `referrals` | Referral records with PHI in metadata |

### Business Partner (5 tables + 5 supporting)

`bp_jobs`, `bp_proposals`, `bp_contracts`, `bp_milestones`, `bp_invoices` + `bp_invoice_line_items`, `bp_invoice_payments`, `bp_payouts`, `bp_tax_documents`, `bp_team_members`, `bp_team_invitations`, `bp_saved_jobs`.

### News, Finance, etc.

`news_posts`, `news_events`, `news_comments`, `news_reactions`, `news_poll_votes`, `news_trending_topics`, `news_library_items`, `practitioner_payment_methods`, `practitioner_payments`, `cs_invoices`, `ss_provider_checkins`, `ss_provider_notes`.

### Reset

```
GET /reset.php?token=aegis-demo-reset
```

Drops + recreates DB from `seed.json`. **Required after schema changes (additions are non-destructive via `ALTER TABLE ADD COLUMN`).**

---

## 5. Portal Pages — File-by-File

### 5.1 Provider Portal (18 pages)

```
provider-portal/
├── overview.php             ← stub → _shared/templates/overview.php
├── dashboard.php            ← main landing (CEU, License, Insurance, Continuity Plan card, Activate modal)
├── edit-profile.php         ← all profile editing (Specialties/Services/Approaches/Credentials taxonomies)
├── continuity-plan.php      ← Plan Builder wizard
├── continuity-stewards.php  ← CS designation, role mgmt, per-incident authorization matrix
├── support-stewards.php     ← SS designation
├── network.php              ← 3-tab: Integrative Care Network · Business Partners · Referrals
├── services.php             ← My Services + Find Provider
├── job-postings.php         ← Support & Services (Support Requests)
├── referrals.php            ← Incoming + outgoing referrals
├── vault.php                ← 4-zone Vault + Client Roster + Vault Attestation
├── important-documents.php  ← Plan + addendums + agreements list
├── messages.php             ← stub → _shared/templates/messages.php
├── activity.php             ← stub → _shared/templates/activity.php
├── news.php                 ← Events & Trainings, News, Library
├── events.php               ← Events & Trainings detail
├── finances.php             ← Subscription, payouts, MAAT CS Service payment model
└── settings.php             ← Settings (Subscription · Privacy · Notifications · Account Closure)
```

### 5.2 Continuity Steward Portal (12 pages)

```
continuity-steward-portal/
├── overview.php
├── dashboard.php
├── edit-profile.php
├── providers.php             ← list of providers I serve (with Vault attestation chip)
├── my-tasks.php              ← organized by provider: Standby + Incident tasks
├── continuity-management.php ← active incident management (formerly "emergency management")
├── important-documents.php
├── vault.php                 ← sealed vault, unsealed on verified incident
├── messages.php
├── activity.php
├── finances.php              ← service fee billing, fee amendments
└── settings.php
```

### 5.3 Support Steward Portal (11 pages)

```
support-steward-portal/
├── overview.php
├── dashboard.php
├── edit-profile.php
├── providers.php             ← list of providers I support (with Vault attestation chip)
├── continuity-stewards.php   ← CSs I work with
├── my-tasks.php
├── critical-incident-log.php ← formerly emergency log
├── important-documents.php
├── messages.php
├── activity.php
└── settings.php
```

### 5.4 Business Partner Portal (14 pages)

```
biz-portal/
├── overview.php              ← stub
├── dashboard.php
├── edit-profile.php
├── find-jobs.php             ← browse Support Requests from Practitioners
├── proposals.php             ← my submitted proposals
├── contracts.php             ← active contracts
├── milestones.php            ← contract milestones + deliverables
├── invoices.php              ← issue invoices, track payments
├── finances.php              ← YTD, revenue charts, tax docs
├── payment-setup.php         ← Stripe Connect onboarding
├── team.php                  ← (Agency BPs only) team member management
├── messages.php              ← stub
├── activity.php              ← stub
└── settings.php
```

Sidebar auto-hides `team.php` for Freelancer BPs (`users.bp_type = 'freelancer'`).

### 5.5 Admin Portal (6 pages — greenfield)

Sourced from the Vue sidebar component (`p === 'admin'` branch). Folder is `admin-portal/` with `.htaccess` rewrite mapping `/admin/<page>` → `admin-portal/<page>.php` for clean URLs.

```
admin-portal/
├── dashboard.php     ← Platform stats: signups, active plans, MRR, open complaints, active incidents
├── packages.php      ← Subscription tier config — prices, feature flags, per-tier subscriber counts
├── users.php         ← Search/edit any user, lock/unlock, role change, force password reset, deactivate
├── roles.php         ← Roles & permissions matrix (system roles read-only + custom roles)
├── payments.php      ← Payment ledger, failed-payment queue, pending payouts, Stripe webhook log
└── complaints.php    ← Customer complaint management — assign, reply, status changes (badge = open count)
```

**Sidebar sections (matches Vue config exactly):**
- `Main` → dashboard
- `Management` → packages, users, roles
- `Finance` → payments
- `Support` → complaints *(badge driven by `user.open_complaints_count`)*

**Access guard — add to `_shared/models.php`:**
```php
function aegis_require_admin(): array {
    $user = aegis_current_user();
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        header('Location: /login.php?next=' . urlencode($_SERVER['REQUEST_URI'])); exit;
    }
    return $user;
}
```
Use at top of every admin page: `$current_user = aegis_require_admin();`

**New write endpoints needed (none exist yet):**

| Endpoint | Actions |
|---|---|
| `_shared/save_admin_user.php` | `lock_account`, `unlock_account`, `force_reset_password`, `change_role`, `deactivate_account`, `restore_account` |
| `_shared/save_admin_package.php` | `update_pricing`, `toggle_feature`, `set_max_count` |
| `_shared/save_admin_role.php` | `create_role`, `update_role_permissions`, `delete_role` |
| `_shared/save_admin_payment.php` | `process_refund`, `retry_failed_payment`, `release_payout` |
| `_shared/save_admin_complaint.php` | `assign`, `reply`, `change_status`, `add_internal_note`, `escalate` |

**New schema needed (v16):**

```sql
-- User account state
ALTER TABLE users ADD COLUMN locked_at TEXT;
ALTER TABLE users ADD COLUMN locked_reason TEXT;
ALTER TABLE users ADD COLUMN deactivated_at TEXT;

-- Admin action audit trail
CREATE TABLE IF NOT EXISTS admin_audit_log (
    id TEXT PRIMARY KEY,
    admin_id TEXT NOT NULL,
    action TEXT NOT NULL,         -- 'lock_user', 'change_role', 'process_refund', etc.
    target_type TEXT NOT NULL,    -- 'user', 'package', 'payment', 'complaint'
    target_id TEXT NOT NULL,
    reason TEXT,
    metadata TEXT,                -- JSON for additional context
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- Package tier overrides (editor for pricing_data.php)
CREATE TABLE IF NOT EXISTS package_overrides (
    id TEXT PRIMARY KEY,
    tier TEXT NOT NULL UNIQUE,    -- 'access', 'practice', 'bp_basic', 'bp_pro'
    monthly_cents INTEGER,
    annual_cents INTEGER,
    feature_flags TEXT,           -- JSON
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_by TEXT
);

-- Custom role definitions + permissions
CREATE TABLE IF NOT EXISTS roles (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    label TEXT NOT NULL,
    is_system INTEGER DEFAULT 0,  -- 1 for built-in roles (read-only in UI)
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id TEXT NOT NULL,
    permission_key TEXT NOT NULL, -- '<domain>.<action>' e.g. 'vault.unseal', 'users.lock'
    granted INTEGER DEFAULT 1,
    PRIMARY KEY (role_id, permission_key),
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Customer complaints
CREATE TABLE IF NOT EXISTS complaints (
    id TEXT PRIMARY KEY,
    submitter_id TEXT NOT NULL,
    subject TEXT NOT NULL,
    body TEXT NOT NULL,
    category TEXT,                -- 'billing', 'account', 'technical', 'service', 'other'
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
    is_internal INTEGER DEFAULT 0, -- 1 = admin-only note, hidden from user
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id),
    FOREIGN KEY (author_id) REFERENCES users(id)
);
```

**Seed additions needed:**
- 1 admin user: `admin_root` (Name: "Aegis Admin", role: `admin`)
- 3–5 sample complaints across statuses
- Demo flag: `?as=admin_root` in `demo.php` switcher
- PHP sidebar admin branch (port Vue config into `_shared/sidebar.php`)

**Estimated build effort:** 8–12 hrs across 6 waves (schema → dashboard → users → complaints → packages/roles → payments → seed/demo).

### 5.6 Public Pages (no auth)

```
public/
├── provider.php           ← https://aegis.devlet.tech/public/provider.php?slug=sarah-johnson
├── continuity_steward.php
├── support_steward.php
└── business.php
```

All 4 include `_shared/public_chrome.php` + `_shared/public_profile.php` and pull data via `aegis_get_user_by_slug()`.

---

## 6. Worked Examples

### 6.1 Adding a New Portal Page

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared/page.php';   // sets $current_user, loads DB

$current_user = aegis_current_user('p_sarah');
if (!$current_user || $current_user['role'] !== 'practitioner') {
    header('Location: /reset.php?token=aegis-demo-reset'); exit;
}
$active_page = 'my-new-page';   // matches sidebar nav key

// Fetch read-side data
$data = aegis_get_something_for_practitioner($current_user['id']);
?>
<?php require_once __DIR__ . '/../_shared/page_head.php'; ?>
<?php require_once __DIR__ . '/../_shared/theme_loader.php'; ?>
<?php require_once __DIR__ . '/../_shared/sidebar.php'; ?>
<div class="sidebar-overlay"></div>
<div class="main-content">
  <?php require_once __DIR__ . '/../_shared/header.php'; ?>
  <div class="page-body">

    <div class="hero-banner is-quiet">
      <div class="page-hero-inner">
        <div>
          <div class="page-hero-eyebrow">Section Name</div>
          <div class="page-hero-title">My New Page</div>
          <div class="page-hero-sub">Brief description.</div>
        </div>
      </div>
    </div>

    <!-- Your content -->

  </div>
</div>
<?php require_once __DIR__ . '/../_shared/page_foot.php'; ?>
```

### 6.2 Adding a New Write Action

**(a)** Add helper to `_shared/models_write.php`:

```php
function aegis_my_new_action(string $user_id, string $payload): bool {
    $db = aegis_db();
    $db->prepare('UPDATE users SET some_col = ? WHERE id = ?')
       ->execute([$payload, $user_id]);

    aegis_log_activity(
        $user_id, 'provider', 'profile', 'info', 'settings',
        'my_action', 'Action title', 'Description...'
    );
    return true;
}
```

**(b)** Add action case to existing or new `_shared/save_<domain>.php`:

```php
case 'my_new_action': {
    $payload = (string)($data['payload'] ?? '');
    $ok = aegis_my_new_action($pid, $payload);
    echo json_encode(['ok' => $ok]);
    break;
}
```

**(c)** Call from page-local JS:

```javascript
function submitMyAction() {
  fetch('/_shared/save_profile.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ action: 'my_new_action', payload: 'value' })
  })
  .then(r => r.json())
  .then(d => {
    if (d.ok) { showToast('Done!', 'success'); setTimeout(() => location.reload(), 700); }
    else { showToast(d.error || 'Failed', 'error'); }
  });
}
```

### 6.3 Cross-Portal Activity Fan-out

```php
function aegis_practitioner_attest_vault(string $plan_id, string $note = ''): bool {
    $db = aegis_db();
    $plan = aegis_get_plan($plan_id);

    $db->prepare('UPDATE continuity_plans SET vault_attested_at = ? WHERE id = ?')
       ->execute([aegis_now(), $plan_id]);

    // Self-log
    aegis_log_activity($plan['practitioner_id'], 'provider', 'document', 'info',
        'vault', 'vault_attestation', 'Vault attested', '...',
        $plan_id, 'continuity_plan');

    // Fan-out to every assigned steward
    foreach (aegis_get_plan_stewards($plan_id) as $s) {
        $portal = $s['steward_type'] === 'continuity_steward'
            ? 'continuity_steward' : 'support_steward';
        aegis_log_activity($s['steward_id'], $portal, 'document', 'info',
            'providers', 'vault_attestation',
            'Practitioner attested Vault is complete', '...',
            $plan_id, 'continuity_plan', $plan['practitioner_id']);
    }
    return true;
}
```

---

## 7. Conventions & Gotchas

### Must-do

- **`aegis_icon($name, $size)`** for every icon. Never inline `<svg>`.
- **CSS variables only.** No hex literals in PHP. Pull from `_shared.css`.
- **`htmlspecialchars()`** every user-supplied string before echoing.
- **`JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS`** for any JSON in `data-*` attributes.
- **`viewPartyProfile(name, kind, slug)`** for opening any party profile from any page. Kinds: `provider | steward | ss | business`.
- **`openModal('modalId')` / `closeModal('modalId')`** — never `display:none` directly.
- **`showToast(msg, level)`** — levels: `success | error | info | warning`.
- **`function_exists()` guards** before declaring write helpers (multi-include safety).
- **`php -l <file>`** before delivering — lint must be clean.

### Must-not

- **Never write `<!DOCTYPE html>`** — `page_head.php` owns this.
- **Never echo PHP inside `<script>`** — use `data-*` attrs.
- **Never put dynamic PHP into `onclick="…"` JS args** — use `data-*` + `this.dataset.x`.
- **Never duplicate write logic** — every state change goes through `models_write.php`.
- **Never query `plan_tasks` and `incident_tasks` as one flat list** — different semantics.
- **Never use `!important`** except scoped library overrides (Dropzone, etc.).
- **Never use `btn-gold`** — use `.btn.btn-primary`.
- **Never use `page-hero-eyebrow`** outside the canonical hero block.
- **Never store hex literals.** Variables only.

### Modal title rule

```html
<!-- ✓ correct -->
<div class="modal-title">Attest Vault is Complete</div>

<!-- ✗ wrong (no icons in modal title) -->
<div class="modal-title"><?= aegis_icon('shield', 16) ?> Attest Vault</div>
```

### Stat chip rule

```html
<!-- ✓ Stat chips ALWAYS sibling of .hero-banner, never inside -->
<div class="hero-banner is-quiet">...</div>
<div class="stat-chips-row">
  <div class="stat-chip">
    <div class="stat-chip-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)">
      <?= aegis_icon('shield', 18) ?>
    </div>
    <div>...</div>
  </div>
</div>
```

### Upload rule

Every file upload uses `.aegis-dropzone` + Dropzone CDN (init in `_shared.js`).

---

## Appendix — Key Numbers at a Glance

| Metric | Value |
|---|---|
| `_shared/` files | 48 |
| `models.php` read helpers | 204 |
| `models_write.php` write helpers | 122 |
| Shared write endpoints (`save_*.php`) | 21 |
| Shared templates | 3 (overview, activity, messages) |
| Shared modals | 4 |
| Database tables | 42 existing + 6 planned (admin v16) |
| Schema version | v15 (v16 planned for admin build) |
| Provider portal pages | 18 |
| CS portal pages | 12 |
| SS portal pages | 11 |
| BP portal pages | 14 |
| Admin portal pages | 6 (greenfield — dashboard, packages, users, roles, payments, complaints) |
| Public profile pages | 4 |
| Stylesheet size | ~170 KB (one file) |
| JS bundle size | ~50 KB (one file) |

---

*This document is generated/maintained from the live repo. Refresh whenever `_shared/` grows or schema bumps.*
