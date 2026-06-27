# ADMIN-PORTAL-SPEC.md

> **Status:** Build-ready specification, use-case-grounded.
> **Source of truth:** `AEGIS_USE_CASES_OUTPUT.md` (validated, commit `bbac3bc`) — 41 `UC-ADM-*` + 3 admin-impacting `UC-XP-*`.
> **Schema baseline verified against** `_shared/db.php` @ `bbac3bc`.
> **Build state:** All 6 pages **greenfield** — no `admin-portal/` directory, no `save_admin_*.php`, no `roles`/`role_permissions` tables exist yet.
>
> Rule applied throughout: **use cases win over prior spec text.** Where a UC's data shape disagreed with the live schema, the live schema is cited and the gap flagged `[SCHEMA GAP]`. The previously-referenced `ADMIN-PORTAL-SPEC.md` was not available for diff; this document is authored fresh from the use cases and is authoritative.

---

## 0. Coverage Map

| Page | Use Cases | New endpoint(s) |
|---|---|---|
| `dashboard.php` | UC-ADM-001 → 005; reads UC-XP-024, UC-XP-025 | — (read-only) |
| `packages.php` | UC-ADM-010 → 013 | `save_admin_package.php` |
| `users.php` | UC-ADM-020 → 029 | `save_admin_user.php` |
| `roles.php` | UC-ADM-030 → 033 | `save_admin_role.php` |
| `payments.php` | UC-ADM-040 → 046 | `save_admin_payment.php` |
| `complaints.php` | UC-ADM-050 → 057; serves UC-XP-015 | `save_admin_complaint.php` |
| `help-articles.php` *(new section §7)* | UC-ADM-058 → 060 | `save_admin_help.php` |
| `incidents.php` *(new section §8, optional)* | UC-XP-024, UC-XP-025 | — (read-only) |

**Admin-impacting cross-portal UCs:** UC-XP-015 (support reply admin→user, served by complaints), UC-XP-024 (admin reads all incidents), UC-XP-025 (admin reads all `activity_events` — global audit).

---

## 1. Auth, Conventions & Shared Plumbing

### 1.1 Auth gate — `aegis_require_admin()` *(to add to `models.php`)*

Every admin page's first executable lines after the shared `require_once`:

```php
$current_user = aegis_current_user();
aegis_require_admin($current_user);   // 302 → /login.php if not admin; 403 page if logged-in non-admin
```

```php
/**
 * Hard gate for all /admin-portal/ pages and save_admin_*.php endpoints.
 * Admin is a *role* in the user_roles table (role = 'admin'), NOT a users.role value.
 *
 * @param array|null $user  Row from aegis_current_user()
 * @return void  Halts (header 302 / 403) when the user is not an admin.
 */
function aegis_require_admin(?array $user): void
```

> **Correction vs UC text:** UC-ADM auth note says it checks `user_roles.role_name='admin'`. The real column is `user_roles.role` (CHECK list already includes `'admin'` — verified in `db.php`). Implementation must query `role`, not `role_name`. No schema change needed for the gate itself.

Check logic: `aegis_user_has_role($user['id'], 'admin')` (existing helper) — returns bool against `user_roles`.

### 1.2 Write-path contract (identical to the four portals)

```
page-local aPost(action,payload)
  → POST /_shared/save_admin_<domain>.php  (JSON body {action, ...})
  → aegis_require_admin() re-check (never trust the page gate alone)
  → action whitelist (switch/case)
  → aegis_admin_*() write helper in models_write.php  (parameterised SQL)
  → aegis_log_activity(...) fan-out  (only where a non-admin portal must see the effect)
  → aegis_admin_audit(...)           (ALWAYS — every admin mutation is audited)
  → JSON {ok:bool, message:string}
  → toast + reload
```

Two cross-cutting helpers every admin write calls:

```php
/**
 * Append an immutable audit row for any admin mutation.
 * @return string  audit row id
 */
function aegis_admin_audit(
    string $admin_id,
    string $action,               // e.g. 'lock_user','refund_payment'
    ?string $target_user_id = null,
    ?string $target_type = null,  // 'user'|'payment'|'role'|'complaint'|'package'
    ?string $target_id = null,
    array $meta = []              // JSON-encoded into admin_audit_log.meta_json
): string
```

```php
/**
 * Admin-scoped activity fan-out. NOTE: activity_events.portal CHECK currently
 * permits only the four end-user portals — see [SCHEMA GAP §9.A]. Until the
 * CHECK is widened, admin→user notifications (UC-XP-015) are written with the
 * RECIPIENT's portal, not 'admin'. Admin-internal events live in admin_audit_log.
 */
```

### 1.3 Design / icons

CSS variables only (`var(--gold-dark)`, `var(--red)`, `var(--badge-bg-gold)` …); no hex literals except `#fff`. Every icon via `aegis_icon('<key>', <size>)` — never inline SVG. Canonical keys already in `icons.php`: `grid`, `users`, `credit-card`, `shield-check`, `alert-triangle`, `chart-trend`, `receipt`, `lock`, `dollar`, `pencil`, `trash`, `plus`, `check`, `x`, `search`, `settings`, `help-circle`. If a new key is needed (e.g. `refund`, `gavel`) add it to `icons.php` first.

---

## 2. Dashboard — `admin-portal/dashboard.php`

**Covers:** UC-ADM-001, 002, 003, 004, 005. Read-only (no write endpoint).

### 2.1 Read helpers (add to `models.php`)

```php
/**
 * UC-ADM-001 — platform headline stats.
 * @return array{
 *   users_by_role: array<string,int>,   // practitioner/continuity_steward/support_steward/business_partner/admin
 *   signups_30d: int,
 *   mrr_cents: int, arr_cents: int,
 *   active_plans: int,
 *   active_incidents: int,
 *   open_complaints: int,
 *   pending_tickets: int
 * }
 */
function aegis_admin_platform_stats(): array
// Reads: users (GROUP BY role via user_roles), continuity_plans (status='active'),
//        critical_incidents (status IN reported/verified/active), complaints (status!='closed'),
//        practitioner_payments + cs_invoices + bp_invoices (MRR aggregation).
```

```php
/**
 * UC-ADM-002 — signup trend by role, bucketed by day for the window.
 * @return array<int, array{day:string, role:string, count:int}>
 */
function aegis_admin_signup_trend(int $days = 30): array
// SELECT substr(created_at,1,10) AS day, (SELECT role FROM user_roles WHERE user_id=users.id AND is_default=1) AS role,
//        COUNT(*) FROM users WHERE created_at >= date('now', ?) GROUP BY day, role ORDER BY day.
```

```php
/**
 * UC-ADM-003 — MRR by calendar month for the trailing window.
 * @return array<int, array{month:string, mrr_cents:int}>
 */
function aegis_admin_revenue_trend(int $months = 12): array
// Aggregates practitioner_payments.amount, cs_invoices, bp_invoices grouped by substr(paid_at,1,7).
```

```php
/**
 * UC-ADM-004 — active incidents for the oversight strip.
 * @return array<int, array{id,incident_type,practitioner_id,practitioner_name,status,created_at}>
 */
function aegis_admin_active_incidents(): array
// critical_incidents JOIN users WHERE status IN ('reported','verified','active') ORDER BY created_at DESC.
```

```php
/**
 * UC-ADM-005 — most recent complaints for the queue widget.
 * @return array<int, array{id,subject,submitter_name,category,priority,status,created_at}>
 */
function aegis_admin_recent_complaints(int $limit = 10): array
```

### 2.2 UI specification

- **Topbar:** "Platform Administration" label, environment chip (`PROD` / `STAGING`), admin name + role pill.
- **Stat grid (UC-ADM-001):** 8 stat chips — Total Users (with per-role breakdown sub-line), Signups (30d), MRR, ARR, Active Plans, Active Incidents, Open Complaints, Pending Tickets. All chips use `var(--badge-bg-gold)` + `var(--gold-dark)` uniformly (no per-icon color variants).
- **Signup trend chart (UC-ADM-002):** line/area chart, one series per role, 30-day x-axis.
- **Revenue trend chart (UC-ADM-003):** 12-month MRR bar/line.
- **Active incidents strip (UC-ADM-004):** red-accented cards (`var(--red)`), each linking to `users.php?id=<practitioner>` and the incident detail; empty state "No active incidents."
- **Recent complaints queue (UC-ADM-005):** compact table (Subject · Submitter · Priority · Status · Age), row → `complaints.php?id=<id>`.

### 2.3 Business rules

- Stats are read-live (no caching layer specced); heavy aggregations may be memoised per-request only.
- MRR counts only **active** subscriptions; failed/cancelled excluded.
- Counts respect soft-deletes: deactivated users excluded from "Total Users" active counts but shown in a separate "deactivated" tally.

---

## 3. Packages — `admin-portal/packages.php`

**Covers:** UC-ADM-010, 011, 012, 013. **Endpoint:** `save_admin_package.php`.

### 3.1 Schema — `[SCHEMA GAP]` `package_overrides`

Tiers currently live as **constants** in `_shared/pricing_data.php` (`aegis_pricing()`), not in the DB. To let admin override price/flags/limits at runtime, a `package_overrides` table is required (full SQL in §9). Until it exists, packages.php is **read-only** against `pricing_data.php`.

> Pricing source of truth = `pricing_data.php`: Access $29/mo ($23 annual), Practice $49/mo ($39 annual), MAAT add-on +$29/mo (requires Practice), Business CS $49/mo, Business Partner $69/mo. (Note: `AEGIS-PROJECT-CONTEXT.md §5` lists older $39/$79 figures — **`pricing_data.php` wins**.)

### 3.2 Read helper

```php
/**
 * UC-ADM-010 — every tier with live subscriber count, merging pricing_data
 * constants with any package_overrides row.
 * @return array<int, array{
 *   tier:string, label:string,
 *   price_monthly_cents:int, price_annual_cents:int,   // override if present, else constant
 *   subscriber_count:int,
 *   feature_flags:array<string,bool>, limits:array<string,int>,
 *   overridden:bool
 * }>
 */
function aegis_admin_packages(): array
// COUNT(users) GROUP BY tier (practitioner tiers) + cs_account_type + bp baseline.
```

### 3.3 Write endpoint `save_admin_package.php` — actions

| Action | UC | Helper | SQL |
|---|---|---|---|
| `update_price` | UC-ADM-011 | `aegis_admin_set_package_price()` | upsert `package_overrides(tier, price_monthly_cents, price_annual_cents, effective_at)` |
| `toggle_feature` | UC-ADM-012 | `aegis_admin_set_package_feature()` | upsert `package_overrides.feature_flags` (JSON merge of one key) |
| `set_limits` | UC-ADM-013 | `aegis_admin_set_package_limits()` | upsert `package_overrides.limits` (JSON) |

```php
function aegis_admin_set_package_price(string $admin_id, string $tier, int $monthly_cents, int $annual_cents): bool
// INSERT INTO package_overrides(...) ON CONFLICT(tier) DO UPDATE SET price_monthly_cents=?, price_annual_cents=?, effective_at=CURRENT_TIMESTAMP;
// then aegis_admin_audit($admin_id,'update_price','package',$tier).
function aegis_admin_set_package_feature(string $admin_id, string $tier, string $flag_key, bool $on): bool
function aegis_admin_set_package_limits(string $admin_id, string $tier, array $limits): bool
```

**Fan-out:** none to end-user portals (price changes apply at next Stripe sync). Audit row always written.
**`feature_flags` shape:** `{services_mode_allowed, maat_addon_allowed, job_postings_allowed, referrals_allowed, …}`.
**`limits` shape:** `{max_cs, max_ss, max_team_members, max_vault_items, …}` — mirrors the tier gates already enforced in the Provider portal (e.g. Access vault cap 25).

### 3.4 UI

Tier cards (one per package), each: name, live monthly/annual price (editable → `update_price` modal), subscriber count chip, feature-flag toggle list (`toggle_feature`), limits table (`set_limits` modal). "Overridden" badge when a `package_overrides` row exists. Stripe sync note: "Price changes take effect for new/renewing subscriptions; existing subscriptions reprice at renewal."

### 3.5 Business rules

- MAAT add-on price/flag is editable but **requires Practice** — packages.php must surface this dependency.
- Limit reductions cannot retroactively delete user data; they gate new additions only.

---

## 4. Users — `admin-portal/users.php`

**Covers:** UC-ADM-020 → 029 (+ White-Glove flows). **Endpoint:** `save_admin_user.php`.

### 4.1 Schema gaps

`users` is missing several columns these UCs require:

| Column | UC | Status |
|---|---|---|
| `locked_at` TEXT | UC-ADM-023/024 | `[SCHEMA GAP]` |
| `locked_reason` TEXT | UC-ADM-023 | `[SCHEMA GAP]` |
| `deactivated_at` TEXT | UC-ADM-027/028 | `[SCHEMA GAP]` |
| `failed_login_count` INTEGER | UC-PRV-002 (lockout) | `[SCHEMA GAP]` |
| `mfa_enabled` INTEGER, `mfa_secret` TEXT | UC-PRV-002 A3 | `[SCHEMA GAP]` |

Supporting gap tables: `admin_audit_log` (all actions), `password_reset_tokens` (UC-ADM-025). All in §9.

### 4.2 Read helpers

```php
/**
 * UC-ADM-020 — paged/filtered user search.
 * @param array{q?:string, role?:string, status?:string} $filters  status ∈ active|locked|deactivated
 * @return array<int, array{id,display_name,email,role,tier,status,created_at,last_login}>
 */
function aegis_admin_search_users(array $filters, int $limit = 50, int $offset = 0): array

/**
 * UC-ADM-021 — full profile bundle for one user.
 * @return array{
 *   user:array, roles:array<string>, plan:?array, stewards:array,
 *   incidents:array, payments:array, last_activity:?string
 * }
 */
function aegis_admin_user_detail(string $user_id): array
// Reads users, user_roles, continuity_plans, plan_stewards, critical_incidents, practitioner_payments.

/**
 * UC-ADM-022 — target user's activity log (admin view of any user's feed).
 * @return array<int, array{event_type,title,description,created_at,severity}>
 */
function aegis_admin_user_activity(string $user_id, int $limit = 100): array
// activity_events WHERE user_id = ? ORDER BY created_at DESC.
```

### 4.3 Write endpoint `save_admin_user.php` — actions

| Action | UC | Helper | SQL / effect | Fan-out |
|---|---|---|---|---|
| `lock_user` | UC-ADM-023 | `aegis_admin_lock_user()` | `UPDATE users SET locked_at=CURRENT_TIMESTAMP, locked_reason=? WHERE id=?` | recipient-portal `account`/`warning` event |
| `unlock_user` | UC-ADM-024 | `aegis_admin_unlock_user()` | `UPDATE users SET locked_at=NULL, locked_reason=NULL WHERE id=?` | — |
| `force_password_reset` | UC-ADM-025 | `aegis_admin_force_pw_reset()` | INSERT `password_reset_tokens(user_id,token,expires_at)`; SES email *(pending A.3)* | — |
| `change_role` | UC-ADM-026 | `aegis_admin_change_role()` | upsert `user_roles`; update `users.role` default | recipient `account` event |
| `deactivate_user` | UC-ADM-027 | `aegis_admin_deactivate_user()` | `UPDATE users SET deactivated_at=CURRENT_TIMESTAMP WHERE id=?` | — |
| `restore_user` | UC-ADM-028 | `aegis_admin_restore_user()` | `UPDATE users SET deactivated_at=NULL WHERE id=?` | — |
| `impersonate` | UC-ADM-029 | `aegis_admin_impersonate()` | **non-prod only**; sets impersonation session marker | — |

```php
function aegis_admin_lock_user(string $admin_id, string $user_id, string $reason): bool
// UPDATE users SET locked_at=CURRENT_TIMESTAMP, locked_reason=? WHERE id=?;
// aegis_log_activity($user_id, <recipient portal>, 'account','warning','admin','locked', 'Account locked', $reason);
// aegis_admin_audit($admin_id,'lock_user','user',$user_id,[ 'reason'=>$reason ]);
function aegis_admin_unlock_user(string $admin_id, string $user_id): bool
function aegis_admin_force_pw_reset(string $admin_id, string $user_id): bool
function aegis_admin_change_role(string $admin_id, string $user_id, string $new_role): bool
function aegis_admin_deactivate_user(string $admin_id, string $user_id): bool
function aegis_admin_restore_user(string $admin_id, string $user_id): bool
function aegis_admin_impersonate(string $admin_id, string $user_id): bool   // guarded by AEGIS_ENV !== 'production'
```

### 4.4 UI

- **Search bar + filters:** text (name/email), role dropdown, status dropdown (active/locked/deactivated). Results table: Name · Email · Role(s) · Tier · Status chip · Last login · Actions.
- **Status chips:** active = `var(--badge-bg-green)`-equivalent neutral, locked = `var(--red)`, deactivated = grey.
- **Detail drawer (UC-ADM-021):** identity card, role badges, plan summary, steward assignments, incident history, payment list, "View activity log" (UC-ADM-022).
- **Action menu per row:** Lock/Unlock (reason modal), Force password reset (confirm), Change role (select + audit-reason modal), Deactivate/Restore (confirm). **Impersonate** appears **only when `AEGIS_ENV !== 'production'`**.

### 4.5 Business rules

- `lock_user` blocks login at next request (middleware checks `locked_at`); UC-PRV-002 lockout (5 failed logins) sets the same column automatically.
- `change_role` is **always** audited with the acting admin + before/after roles.
- `deactivate_user` is a soft delete (`deactivated_at`); data retained; user excluded from all public/search reads.
- `impersonate` hard-blocked in production; every impersonation writes an `admin_audit_log` row with `action='impersonate'`.
- Admin cannot lock/deactivate/role-change **their own** admin account (self-lockout guard).

---

## 5. Roles & Permissions — `admin-portal/roles.php`

**Covers:** UC-ADM-030, 031, 032, 033. **Endpoint:** `save_admin_role.php`.

### 5.1 Schema gaps — `[SCHEMA GAP]` `roles`, `role_permissions`

Neither table exists. `user_roles` (exists) maps **users → role slug**; it is *not* a definitions table. `roles` defines assignable roles (system + custom); `role_permissions` holds per-role permission grants. Full SQL in §9.

### 5.2 Read helper

```php
/**
 * UC-ADM-030 — all roles with assigned-user counts and permission sets.
 * @return array<int, array{
 *   id:string, name:string, system_role:bool,
 *   user_count:int, permissions:array<string,bool>
 * }>
 */
function aegis_admin_roles(): array
// roles LEFT JOIN role_permissions, COUNT(user_roles) per role.name.
```

### 5.3 Write endpoint `save_admin_role.php` — actions

| Action | UC | Helper | SQL |
|---|---|---|---|
| `create_role` | UC-ADM-031 | `aegis_admin_create_role()` | INSERT `roles(id,name,system_role=0)` |
| `set_permissions` | UC-ADM-032 | `aegis_admin_set_role_permissions()` | upsert `role_permissions(role_id, permission_key, granted)` per key |
| `delete_role` | UC-ADM-033 | `aegis_admin_delete_role()` | DELETE `roles` + `role_permissions` (guarded) |

```php
function aegis_admin_create_role(string $admin_id, string $name): string   // returns role id; audited
function aegis_admin_set_role_permissions(string $admin_id, string $role_id, array $perms): bool
// foreach perm: INSERT INTO role_permissions(role_id,permission_key,granted) ON CONFLICT(role_id,permission_key) DO UPDATE SET granted=?;
function aegis_admin_delete_role(string $admin_id, string $role_id): bool
// Precondition enforced: COUNT(user_roles WHERE role = role.name) = 0; refuse otherwise.
```

### 5.4 UI

Role list table: Name · System/Custom badge · User count · Permission summary · Actions. "Create role" modal (name). Per-role permission editor: grouped permission-key checkboxes (`granted` toggles → `set_permissions`). Delete disabled (with tooltip "N users assigned") unless `user_count === 0`. System roles (`admin`, `practitioner`, `continuity_steward`, `support_steward`, `business_partner`) are **non-deletable** and name-locked.

### 5.5 Business rules

- `system_role=1` rows cannot be renamed or deleted.
- `delete_role` requires **zero** assigned users (UC-ADM-033 precondition) — enforced in the helper, not just the UI.
- Permission keys are a fixed vocabulary seeded with the `roles` migration (e.g. `users.manage`, `payments.refund`, `packages.edit`, `complaints.respond`, `roles.manage`, `incidents.view`).

---

## 6. Payments — `admin-portal/payments.php`

**Covers:** UC-ADM-040 → 046. **Endpoint:** `save_admin_payment.php`.

### 6.1 Schema gaps

| Object | UC | Status |
|---|---|---|
| `stripe_webhook_events` table | UC-ADM-046 | `[SCHEMA GAP]` |
| `cs_payouts` table | UC-ADM-044 | `[SCHEMA GAP]` (BP payouts exist as `bp_payouts`; CS payout equivalent does not) |
| `admin_audit_log` | UC-ADM-042/043/045 | `[SCHEMA GAP]` |

Existing payment tables (verified): `practitioner_payments` (id, practitioner_id, amount, currency, status, payment_method_label, paid_at, created_at), `cs_invoices`, `bp_invoices`, `bp_payouts` (id, bp_id, amount, currency, status ∈ pending/in_transit/paid/failed/cancelled, stripe_payout_id, scheduled_at, paid_at).

### 6.2 Read helpers

```php
/**
 * UC-ADM-040 — unified ledger across all payment surfaces.
 * @param array{type?:string, status?:string, from?:string, to?:string} $filters
 * @return array<int, array{id,source,party_id,party_name,amount_cents,currency,status,created_at}>
 *   source ∈ practitioner_payment|cs_invoice|bp_invoice|bp_payout
 */
function aegis_admin_payment_ledger(array $filters, int $limit = 100): array

/**
 * UC-ADM-041 — failed payments queue.
 * @return array<int, array{...}>   // ledger rows WHERE status='failed'
 */
function aegis_admin_failed_payments(): array

/**
 * UC-ADM-044 — pending payouts (BP now; CS pending the cs_payouts table).
 * @return array{bp:array<int,array>, cs:array<int,array>}
 */
function aegis_admin_pending_payouts(): array
// bp_payouts WHERE status='pending'; cs_payouts WHERE status='pending' [SCHEMA GAP until table added].

/**
 * UC-ADM-046 — Stripe webhook event log.
 * @return array<int, array{id,event_type,stripe_event_id,payload_summary,received_at,processed:bool}>
 */
function aegis_admin_webhook_events(int $limit = 100): array   // [SCHEMA GAP] stripe_webhook_events
```

### 6.3 Write endpoint `save_admin_payment.php` — actions

| Action | UC | Helper | Effect |
|---|---|---|---|
| `retry_payment` | UC-ADM-042 | `aegis_admin_retry_payment()` | Stripe retry call; webhook updates row; audit |
| `refund_payment` | UC-ADM-043 | `aegis_admin_refund_payment()` | Stripe refund (full/partial); update row; audit |
| `release_payout` | UC-ADM-045 | `aegis_admin_release_payout()` | `UPDATE bp_payouts SET status='in_transit'/'paid'`; Stripe transfer; audit |

```php
function aegis_admin_retry_payment(string $admin_id, string $payment_id): bool
function aegis_admin_refund_payment(string $admin_id, string $payment_id, int $amount_cents, string $reason): bool
// Stripe refund; UPDATE <payment table> SET status='refunded'/'partially_refunded'; aegis_admin_audit(...,'refund_payment',...,['amount'=>$amount_cents,'reason'=>$reason]).
function aegis_admin_release_payout(string $admin_id, string $payout_id): bool
// UPDATE bp_payouts SET status='in_transit', scheduled_at=CURRENT_TIMESTAMP WHERE id=?; Stripe transfer; audit.
```

**Fan-out:** payout release notifies the BP recipient (`payment` event in `business_partner` portal). Refund notifies the payer's portal. All three actions **block on Stripe Connect** being live (currently pending — flag in UI).

### 6.4 UI

Tabs: **Ledger** (UC-ADM-040, filterable table), **Failed** (UC-ADM-041, retry/refund actions), **Payouts** (UC-ADM-044/045, release action), **Webhooks** (UC-ADM-046, read-only event log). Refund modal: amount (full default, partial allowed) + reason (required, audited). Stripe-pending banner across the page until Connect is configured.

### 6.5 Business rules

- Refund amount ≤ original; partial refunds set `partially_refunded`.
- `release_payout` only valid for `status='pending'`.
- Every money-moving action is audited with admin id, amount, and reason.
- Webhook log is **append-only** and read-only from the admin UI.

---

## 7. Complaints — `admin-portal/complaints.php`

**Covers:** UC-ADM-050 → 057; **serves UC-XP-015** (admin reply → user support page). **Endpoint:** `save_admin_complaint.php`.

### 7.1 Schema — mostly present; one gap

`complaints` (verified): id, submitter_id, subject, body, category (default `support_ticket`), submission_channel (default `ticket`), status CHECK(`open`,`in_progress`,`resolved`,`closed`), priority (default `normal`, free TEXT), assigned_to, created_at, resolved_at.
`complaint_replies` (verified): id, complaint_id, author_id, body, is_internal (default 0), created_at.

| Column | UC | Status |
|---|---|---|
| `complaints.escalated_at` TEXT | UC-ADM-056 | `[SCHEMA GAP]` (priority='urgent' works today; the timestamp does not) |

> `submission_channel` already supports the support-ticket + feedback unification (UC-ADM-050 filters on it). Categories include `support_ticket`, `feedback`, `complaint`.

### 7.2 Read helpers

```php
/**
 * UC-ADM-050 — filtered complaint list.
 * @param array{status?:string, priority?:string, category?:string, channel?:string} $filters
 * @return array<int, array{id,subject,submitter_name,category,submission_channel,priority,status,assigned_to_name,created_at}>
 */
function aegis_admin_complaints(array $filters, int $limit = 100): array

/**
 * UC-ADM-051 — one complaint + full reply thread (internal + public).
 * @return array{complaint:array, replies:array<int,array{author_name,body,is_internal,created_at}>}
 */
function aegis_admin_complaint_detail(string $complaint_id): array

/**
 * UC-ADM-057 — resolution metrics.
 * @return array{avg_first_reply_hours:float, avg_resolution_hours:float, by_category:array<string,array>}
 */
function aegis_admin_complaint_metrics(): array
```

### 7.3 Write endpoint `save_admin_complaint.php` — actions

| Action | UC | Helper | SQL | Fan-out |
|---|---|---|---|---|
| `assign` | UC-ADM-052 | `aegis_admin_assign_complaint()` | `UPDATE complaints SET assigned_to=?` | — |
| `reply` (public) | UC-ADM-053 | `aegis_admin_reply_complaint()` | INSERT `complaint_replies(is_internal=0)` | **submitter** `account` event (UC-XP-015) |
| `reply` (internal) | UC-ADM-054 | `aegis_admin_reply_complaint()` w/ `is_internal=1` | INSERT `complaint_replies(is_internal=1)` | none (never shown to submitter) |
| `set_status` | UC-ADM-055 | `aegis_admin_set_complaint_status()` | `UPDATE complaints SET status=?, resolved_at=?` | submitter on resolve |
| `escalate` | UC-ADM-056 | `aegis_admin_escalate_complaint()` | `UPDATE complaints SET priority='urgent', escalated_at=CURRENT_TIMESTAMP` | — |

```php
function aegis_admin_assign_complaint(string $admin_id, string $complaint_id, string $staff_user_id): bool
function aegis_admin_reply_complaint(string $admin_id, string $complaint_id, string $body, bool $is_internal): string
// INSERT complaint_replies(...); if !is_internal: aegis_log_activity(<submitter>, <submitter portal>, 'account','info','support','reply','Support replied', ...). audited.
function aegis_admin_set_complaint_status(string $admin_id, string $complaint_id, string $status): bool
// Valid transitions: open→in_progress→resolved→closed; sets resolved_at on 'resolved'.
function aegis_admin_escalate_complaint(string $admin_id, string $complaint_id): bool   // [SCHEMA GAP] escalated_at
```

### 7.4 UI

Master-detail: left list (filters: status, priority, category, channel), right detail (thread with public vs `is_internal` styling, reply composer with internal/public toggle, assign dropdown, status select, escalate button). Metrics strip (UC-ADM-057) at top: avg first-reply, avg resolution, counts by category.

### 7.5 Business rules

- `is_internal=1` replies are **never** returned to the submitter's support page reads.
- Status transitions are forward-ordered; `resolved` stamps `resolved_at`.
- `assign` target must hold an admin/staff role.
- Public reply (UC-XP-015) surfaces in the submitter's existing support page + bell.

---

## 8. New Sections Surfaced by the Use Cases

### 8.1 Help Articles — `admin-portal/help-articles.php`  *(UC-ADM-058, 059, 060)*

**Endpoint:** `save_admin_help.php`. Schema **already exists** (`help_articles`: id, category, title, body, role_visibility default `all`, sort_order, published default 1, created_at, updated_at) — **no gap**.

```php
/** UC-ADM-058 list/detail. @return array<int,array{id,category,title,role_visibility,sort_order,published,updated_at}> */
function aegis_admin_help_articles(): array
```

| Action | UC | Helper | SQL |
|---|---|---|---|
| `create` / `update` | UC-ADM-058 | `aegis_admin_save_help_article()` | INSERT/UPDATE `help_articles(category,title,body,role_visibility,sort_order,published)` |
| `set_published` | UC-ADM-059 | `aegis_admin_set_help_published()` | `UPDATE help_articles SET published=? ` |
| `reorder` | UC-ADM-060 | `aegis_admin_reorder_help()` | `UPDATE help_articles SET sort_order=?` per id |

**UI:** category-grouped article list with drag-reorder (`reorder`), publish toggle (`set_published`), editor modal (title, category, role_visibility select, body, published). `role_visibility` controls which portal Help/Support pages surface the article.
**Fan-out:** none (content publishing, not a user notification).

### 8.2 Incident Oversight & Global Audit — `admin-portal/incidents.php` *(optional, UC-XP-024, UC-XP-025)*

Read-only admin surface satisfying the cross-portal admin reads:

```php
/** UC-XP-024 — every incident across all practitioners. */
function aegis_admin_all_incidents(array $filters = []): array
// critical_incidents JOIN users; filters status/date.
/** UC-XP-025 — global activity_events audit stream (platform-wide). */
function aegis_admin_global_activity(array $filters = [], int $limit = 200): array
// activity_events (all users) ORDER BY created_at DESC; filter by portal/event_type/severity/date.
```

**UI:** incident table (status, practitioner, CS/SS, timeline link) + global audit log viewer (filter by portal, event_type, severity, date range). No writes. This is distinct from `admin_audit_log` (admin *actions*) — UC-XP-025 is the **platform** event stream.

---

## 9. Schema Migration v16 — consolidated, idempotent

Append to `aegis_migrate_schema()` (in-place ADD COLUMNs) and `aegis_init_schema()` (new CREATE TABLEs). After applying, run `/reset.php?token=aegis-demo-reset` **only** for the `activity_events` CHECK widening (§9.A) — SQLite cannot ALTER a CHECK in place. All other statements are idempotent and apply without reset.

### 9.A `activity_events.portal` CHECK widening — `[SCHEMA GAP]` (requires reset)

```sql
-- activity_events.portal currently CHECK IN ('provider','continuity_steward','support_steward','business_partner').
-- Admin fan-out (UC-XP-015 admin→user already uses the RECIPIENT portal, so this is only
-- needed if admin-targeted events are introduced). SQLite cannot ALTER a CHECK constraint;
-- to add 'admin', edit the CREATE TABLE in aegis_init_schema() and recreate via reset.php:
--   portal TEXT NOT NULL CHECK (portal IN
--     ('provider','continuity_steward','support_steward','business_partner','admin'))
```

### 9.B In-place ALTER TABLE additions (nullable — no reset required)

```sql
ALTER TABLE users      ADD COLUMN locked_at TEXT;
ALTER TABLE users      ADD COLUMN locked_reason TEXT;
ALTER TABLE users      ADD COLUMN deactivated_at TEXT;
ALTER TABLE users      ADD COLUMN failed_login_count INTEGER DEFAULT 0;
ALTER TABLE users      ADD COLUMN mfa_enabled INTEGER DEFAULT 0;
ALTER TABLE users      ADD COLUMN mfa_secret TEXT;
ALTER TABLE complaints ADD COLUMN escalated_at TEXT;
```

### 9.C New tables (CREATE TABLE IF NOT EXISTS — full SQL)

```sql
CREATE TABLE IF NOT EXISTS roles (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    system_role INTEGER NOT NULL DEFAULT 0,
    description TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id TEXT NOT NULL,
    permission_key TEXT NOT NULL,
    granted INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (role_id, permission_key),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS package_overrides (
    tier TEXT PRIMARY KEY,
    price_monthly_cents INTEGER,
    price_annual_cents INTEGER,
    feature_flags TEXT DEFAULT '{}',   -- JSON
    limits TEXT DEFAULT '{}',          -- JSON
    effective_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_audit_log (
    id TEXT PRIMARY KEY,
    admin_id TEXT NOT NULL,
    action TEXT NOT NULL,
    target_user_id TEXT,
    target_type TEXT,                  -- user|payment|role|complaint|package
    target_id TEXT,
    meta_json TEXT DEFAULT '{}',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_admin_audit_admin  ON admin_audit_log(admin_id, created_at);
CREATE INDEX IF NOT EXISTS idx_admin_audit_target ON admin_audit_log(target_type, target_id);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    token TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    expires_at TEXT NOT NULL,
    used_at TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_pwreset_user ON password_reset_tokens(user_id);

CREATE TABLE IF NOT EXISTS stripe_webhook_events (
    id TEXT PRIMARY KEY,
    stripe_event_id TEXT UNIQUE,
    event_type TEXT NOT NULL,
    payload_json TEXT,
    processed INTEGER NOT NULL DEFAULT 0,
    received_at TEXT DEFAULT CURRENT_TIMESTAMP,
    processed_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_webhook_type ON stripe_webhook_events(event_type, received_at);

CREATE TABLE IF NOT EXISTS cs_payouts (
    id TEXT PRIMARY KEY,
    cs_id TEXT NOT NULL,
    amount REAL NOT NULL DEFAULT 0,
    currency TEXT DEFAULT 'USD',
    status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','in_transit','paid','failed','cancelled')),
    description TEXT,
    stripe_payout_id TEXT,
    scheduled_at TEXT,
    paid_at TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cs_id) REFERENCES users(id)
);
```

### 9.D Seed — system roles + permission vocabulary + admin demo user

```sql
INSERT OR IGNORE INTO roles (id, name, system_role, description) VALUES
 ('role_admin',       'admin',              1, 'Platform administrator'),
 ('role_pract',       'practitioner',       1, 'Practitioner / Partner'),
 ('role_cs',          'continuity_steward', 1, 'Continuity Steward'),
 ('role_ss',          'support_steward',    1, 'Support Steward'),
 ('role_bp',          'business_partner',   1, 'Business Partner');

-- Permission vocabulary (granted to admin by default)
INSERT OR IGNORE INTO role_permissions (role_id, permission_key, granted) VALUES
 ('role_admin','users.manage',1),('role_admin','users.impersonate',1),
 ('role_admin','payments.view',1),('role_admin','payments.refund',1),('role_admin','payouts.release',1),
 ('role_admin','packages.edit',1),('role_admin','roles.manage',1),
 ('role_admin','complaints.respond',1),('role_admin','complaints.assign',1),
 ('role_admin','incidents.view',1),('role_admin','audit.view',1),('role_admin','help.manage',1);

-- Admin demo user
INSERT OR IGNORE INTO users (id, role, display_name, email, slug, created_at)
 VALUES ('admin_root', 'admin', 'Aegis Admin', 'admin@maatpracticefirm.com', 'aegis-admin', CURRENT_TIMESTAMP);
INSERT OR IGNORE INTO user_roles (user_id, role, is_default, enabled_at)
 VALUES ('admin_root', 'admin', 1, CURRENT_TIMESTAMP);
```

> Demo access: `?as=admin_root` (host-gated, same as other demo users). Add `admin_root` to `demo.php` launcher + `seed.json`.

### 9.E Schema-gap summary table

| Object | Type | UCs | Reset needed |
|---|---|---|---|
| `users.locked_at/locked_reason/deactivated_at/failed_login_count/mfa_enabled/mfa_secret` | column | ADM-023/24/27/28, PRV-002 | No (in-place) |
| `complaints.escalated_at` | column | ADM-056 | No |
| `roles`, `role_permissions` | table | ADM-030→033 | No |
| `package_overrides` | table | ADM-011→013 | No |
| `admin_audit_log` | table | all admin writes | No |
| `password_reset_tokens` | table | ADM-025, PRV-005 | No |
| `stripe_webhook_events` | table | ADM-046 | No |
| `cs_payouts` | table | ADM-044/045 | No |
| `activity_events.portal += 'admin'` | CHECK | XP fan-out to admin | **Yes** (recreate) |

---

## 10. Build Wave Plan

Mirrors the Provider four-pass method (centralize → design → wire → tone), sequenced so schema lands before any wiring.

| Wave | Scope | UC IDs | Depends on |
|---|---|---|---|
| **A0 — Schema v16** | Apply §9 migration; add `aegis_require_admin()`, `aegis_admin_audit()`; seed roles + `admin_root`; add to `demo.php`/`seed.json` | — | — |
| **A1 — Chrome + Dashboard** | `admin-portal/` sidebar/header, dashboard read helpers + UI | ADM-001→005 | A0 |
| **A2 — Users** | `save_admin_user.php` + 7 helpers + search/detail/activity reads + UI | ADM-020→029 | A0 |
| **A3 — Complaints** | `save_admin_complaint.php` + helpers + master-detail UI; wires UC-XP-015 reply→user | ADM-050→057, XP-015 | A0 |
| **A4 — Payments** | `save_admin_payment.php` + ledger/failed/payouts/webhooks reads + UI | ADM-040→046 | A0; **Stripe Connect live** |
| **A5 — Roles** | `save_admin_role.php` + helpers + permission editor UI | ADM-030→033 | A0 |
| **A6 — Packages** | `save_admin_package.php` + override helpers + tier-card UI | ADM-010→013 | A0 |
| **A7 — Help Articles** | `save_admin_help.php` + CRUD/reorder/publish UI | ADM-058→060 | A0 (schema already present) |
| **A8 — Incident Oversight (optional)** | `incidents.php` global incident + audit reads | XP-024, XP-025 | A0 |
| **A9 — Tone pass** | MA'AT voice across all admin copy | all | A1→A8 |

**Critical-path note:** A4 (Payments) is blocked by Stripe Connect setup (currently pending) — schedule after Connect is live, or build read-only ledger first and gate the write actions behind a feature flag. Every other wave is unblocked once A0 lands.

**Total:** 41 `UC-ADM-*` + 3 admin-impacting `UC-XP-*` across 6 core pages + 2 new sections (Help Articles, Incident Oversight), 7 new tables, 7 new columns, 1 CHECK widening.

---

# Appendix Z — UC Reconciliation with Laravel Closure Pass

This spec was authored against the legacy PHP/SQLite stack. The Laravel 11 migration ran a "closure pass" that finalized all `UC-ADM-*` numbering. Some UC IDs used in this spec map to slightly different IDs in `AEGIS_USE_CASES_OUTPUT.md` and `AEGIS_LARAVEL_STRUCTURE.md`. The **functionality and routes are identical**; only the citation numbers shift.

Use this table when cross-referencing the spec to the Laravel codebase.

## Z.1 — UC ID Crosswalk

| This spec cites | Laravel doc canonical | Functionality | Why the shift |
|---|---|---|---|
| UC-ADM-022 (impersonate) | **UC-ADM-029** | Admin impersonates user (dev only) | Closure pass renumbered for sequential ordering |
| UC-ADM-040 (ledger view) | **UC-ADM-070** | Admin views payment transactions list | Closure pass formalized as new UC range |
| UC-ADM-041 (failed payments) | **UC-ADM-074** | Admin views failed payments queue | Closure pass formalized |
| UC-ADM-042 (retry payment) | *(no canonical UC)* | Admin retries a failed payment via Stripe | Retry is a sub-action of UC-ADM-074; not split as its own UC in the Laravel doc — treat as part of failed payments UI |
| UC-ADM-043 (refund) | **UC-ADM-072** | Admin refunds a payment | Closure pass formalized |
| UC-ADM-044 (payouts view) | **UC-ADM-044** ✅ | Admin views pending payouts | Same — no change |
| UC-ADM-045 (release payout) | **UC-ADM-045** ✅ | Admin manually releases payout | Same — no change |
| UC-ADM-046 (webhook log) | **UC-ADM-046** ✅ | Admin views Stripe webhook event log | Same — no change |
| UC-ADM-050 (complaints list) | **UC-ADM-050** ✅ | Admin views all complaints | Same — no change |
| UC-ADM-051 (complaint detail) | **UC-ADM-051** ✅ | Admin views complaint detail + reply thread | Same — no change |
| UC-ADM-052 (assign complaint) | **UC-ADM-052** ✅ | Admin assigns complaint to staff | Same — no change |
| UC-ADM-053 (reply to complaint) | **UC-ADM-053** ✅ | Admin replies to complaint | Same — no change |
| UC-ADM-054 (internal note) | **UC-ADM-054** ✅ | Admin adds internal note | Same — no change |
| UC-ADM-055 (change status) | **UC-ADM-055** ✅ | Admin changes complaint status | Same — no change |
| UC-ADM-056 (escalate) | **UC-ADM-056** ✅ | Admin escalates complaint | Same — no change |
| UC-ADM-057 (metrics) | **UC-ADM-057** ✅ | Admin views complaint resolution metrics | Same — no change |
| UC-ADM-058 (help articles upsert) | **UC-ADM-058** ✅ | Admin creates/edits help article | Same — no change |
| UC-ADM-059 (publish article) | **UC-ADM-059** ✅ | Admin publishes/unpublishes | Same — no change |
| UC-ADM-060 (reorder) | **UC-ADM-060** ✅ | Admin reorders articles | Same — no change |
| *(implicit in §6.5)* | **UC-ADM-064** *(new in closure)* | Admin changes complaint priority | Was unstated; closure pass formalized the action with its own UC |
| *(implicit in §7.5)* | **UC-ADM-068** *(new in closure)* | Admin views all incidents (cross-portal) | Was unstated; closure pass formalized |
| *(implicit in §6.5)* | **UC-ADM-071** *(new in closure)* | Admin views payment detail | Was implicit drill-down from ledger row; closure pass formalized |
| *(implicit in §6.5)* | **UC-ADM-073** *(new in closure)* | Admin views Stripe metrics snapshot (MRR/ARR/churn) | Was implicit dashboard widget; closure pass formalized |

## Z.2 — What this means for implementation

**No code or UI changes needed** in any admin page based on this spec. The page-by-page behavior, helper signatures, write endpoints, and UI sections all remain authoritative as-written.

**When wiring up Laravel:**
- Controller methods that handle these actions should cite the canonical Laravel UC ID (right column) in their docblock and routes.
- Service methods listed in `AEGIS_LARAVEL_STRUCTURE.md §3` (e.g. `AdminPaymentService::refund`, `AdminComplaintService::changePriority`) are the canonical implementations — call those rather than the legacy PHP helpers named here.
- The schema-gap items flagged in this spec (`stripe_webhook_events`, `package_overrides`, `roles`, `role_permissions`, `help_articles`, `complaints.priority`) **all exist** as proper tables/columns in the Laravel migrations. The `[SCHEMA GAP]` markers in this document are historical — leave them as-is for archive value, but they no longer apply.

## Z.3 — New tables introduced by closure pass

Two new tables that weren't in this spec but are now part of the canonical schema:

| Table | Purpose | Admin relevance |
|---|---|---|
| `activity_event_reads` | Per-user read receipts for activity feed | Admin's own activity feed unread badge works against this table |
| `provider_checkins` *(renamed from `ss_provider_checkins`)* | Polymorphic CS + SS check-ins | New column `steward_type` (`cs|ss`) — Admin's user-detail view should show both types if surfacing check-in history |

Neither table requires a new admin page; both surface through existing pages (Activity widget; Users → detail → "Stewardship history" tab if/when built).

## Z.4 — Reconciliation status

After applying this crosswalk and updating in-code citations:

- ✅ Zero phantom UC IDs
- ✅ Zero `[SCHEMA GAP]` items remaining open
- ✅ All 41 `UC-ADM-*` use cases have a canonical Laravel implementation
- ✅ All 22 closure-pass items (including UC-ADM-064, 068, 070, 071, 072, 073, 074, 045) are formally documented

This spec remains valid as a UI + business-rule reference. For canonical naming, defer to `AEGIS_LARAVEL_STRUCTURE.md` and `AEGIS_USE_CASES_OUTPUT.md`.
