# Business Partner Portal — Dynamic-Wiring Blueprint

**Status:** as of merge of `aegis-backend-global.zip` + `aegis-public-pages.zip`.
**Scope:** the BP portal only (`/biz-portal/*.php`).
**Goal:** turn each page's hardcoded mock arrays into reads from `_shared/models.php` helpers.
**Counterpart docs:** `PROVIDER-PORTAL-WIRING-BLUEPRINT.md`, `CS-PORTAL-WIRING-BLUEPRINT.md`, `SS-PORTAL-WIRING-BLUEPRINT.md`.

---

## How the BP portal differs (read this first)

The BP portal is the **most data-mature** of the four — full schema already in place, seeded with realistic rows:

- `bp_jobs(id, practitioner_id, title, category, description, budget_type, budget_amount, currency, location_pref, status, proposals_count, posted_at, closes_at)` — 4 rows seeded
- `bp_proposals(id, job_id, bp_id, cover_letter, proposed_rate, proposed_rate_type, proposed_timeline, status, submitted_at, responded_at)` — 4 rows seeded
- `bp_contracts(id, job_id, proposal_id, practitioner_id, bp_id, title, scope, billing_type, total_amount, hourly_rate, currency, status, start_date, end_date, created_at)` — 2 rows seeded
- `bp_milestones(id, contract_id, title, description, amount, currency, due_date, completed_at, status, sort_order, created_at)` — 3 rows seeded
- `bp_invoices(id, invoice_number, contract_id, milestone_id, practitioner_id, bp_id, amount, currency, status, issued_at, due_at, paid_at, notes)` — 3 rows seeded

Plus BP-specific user columns: `bp_type` (`agency` / `freelancer`), `bp_business_name`, `bp_team_size`, `bp_hourly_rate`, `bp_categories` (JSON).

This means almost every BP page can be wired *immediately* — no schema work required for Phase 1.

---

## Foundation already in place

BP-relevant helpers already in `models.php`:

- `aegis_get_open_jobs($category=null, $limit=50)` — all open practitioner-posted jobs.
- `aegis_get_new_jobs_for_bp($bp_id, $limit=50)` — jobs matching this BP's categories that they haven't proposed on.
- `aegis_get_proposals_for_bp($bp_id, $status=null)` — all this BP's proposals.
- `aegis_get_contracts_for_bp($bp_id, $status=null)` — all this BP's contracts.
- `aegis_get_milestones_for_contract($contract_id)` — milestones for one contract.
- `aegis_get_milestones_for_bp($bp_id, $status=null)` — flat list across all this BP's contracts.
- `aegis_get_invoices_for_bp($bp_id, $status=null)` — invoices.
- `aegis_count_bp_badges($bp_id)` — returns counts for sidebar badges (new jobs, pending proposals, active contracts, overdue milestones, pending invoices).
- `aegis_get_bp_earnings($bp_id)` — returns earnings summary (this month, last month, YTD, all-time).
- `aegis_get_threads_for_user`, `aegis_get_messages`, `aegis_send_message`.
- `aegis_get_activity`, `aegis_log_activity`.
- `aegis_profile_data`, `aegis_profile_stats`, `aegis_profile_sections`, `aegis_edit_profile_groups`.
- `aegis_bp_public_url($u)` — returns null unless `business_partner_public=1`.

---

## Cross-page conventions

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared/db.php';
require_once __DIR__ . '/../_shared/models.php';
require_once __DIR__ . '/../_shared/icons.php';

$current_user = aegis_current_user();
if (!$current_user || !aegis_user_has_role($current_user['id'], 'business_partner')) {
    header('Location: /onboarding/signin.html?return=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
$active_page = '<page-key>';

// BP-specific derived state
$bp_type = $current_user['bp_type'] ?? 'freelancer';   // 'agency' | 'freelancer'
$is_agency = ($bp_type === 'agency');
$badges = aegis_count_bp_badges($current_user['id']);
// Used by sidebar.php — pass these in:
$new_jobs           = $badges['new_jobs']          ?? 0;
$active_contracts   = $badges['active_contracts']  ?? 0;
$pending_proposals  = $badges['pending_proposals'] ?? 0;
$overdue_milestones = $badges['overdue_milestones']?? 0;
$pending_invoices   = $badges['pending_invoices']  ?? 0;
$unread_notifs      = $badges['unread_notifs']     ?? 0;

// Revenue privacy toggle (lives in cookie or user_preferences)
$revenue_visible = ($_COOKIE['bp_revenue_visible'] ?? '1') === '1';
```

The sidebar already supports the badge variables and the agency-vs-freelancer branch (Team Management nav appears only when `$is_agency`). Pass `$bp_type` via query string `?type=agency` to preserve across links during demo. Once auth is wired, drop the query-string approach and rely on the user row.

The **revenue privacy toggle** is a BP-specific UX that blurs all `$` figures site-wide when off. Useful for screen-sharing. Implement as a small `.bp-money` CSS class with `filter: blur()` toggled by a body-level data attribute.

---

## Page 1 — `index.php` (BP Dashboard)

### Bootstrap
Standard. `$active_page = 'dashboard';`

### Currently static
- Welcome banner with name
- 4 metric cards: Active Contracts, Monthly Revenue, Open Proposals, New Job Matches
- Active Contracts table
- New Job Matches mini-list
- Recent Activity feed
- "My Profile" card

### Wire to

| Display element | Source |
|---|---|
| Hero name | `$current_user['display_name']` (or `bp_business_name` if set) |
| Avatar | `$current_user['avatar_initials']` |
| Type badge | `bp_type` — "Agency Account" or "Freelancer Account" |
| Active Contracts metric | `count(aegis_get_contracts_for_bp($uid, 'active'))` |
| Monthly Revenue metric | `aegis_get_bp_earnings($uid)['this_month']` — wrap in `.bp-money` for privacy toggle |
| Open Proposals metric | `count(aegis_get_proposals_for_bp($uid, 'pending'))` |
| New Job Matches metric | `count(aegis_get_new_jobs_for_bp($uid))` |
| Active Contracts table | `aegis_get_contracts_for_bp($uid, 'active')` — render: title, practitioner name (join `users` on `practitioner_id`), `total_amount`, `status` pill, link to `contracts.php?id=<id>` |
| Pending Milestones panel | `aegis_get_milestones_for_bp($uid, 'pending')` first 5 |
| Pending Invoices panel | `aegis_get_invoices_for_bp($uid, 'pending')` first 5 |
| New Job Matches mini | `aegis_get_new_jobs_for_bp($uid, 5)` — practitioner name, title, budget, "Submit Proposal" button |
| Recent Activity | `aegis_get_activity($uid, 5)` |
| My Profile mini-card | profile completeness % + "View Public Profile" button → `aegis_bp_public_url($current_user)` (null if not toggled public) |
| Verified pill | `$current_user['verified']` |
| Revenue toggle (top-right) | client-side cookie + body data-attr |

### Notes / open questions
- **Monthly Revenue calculation:** make sure `aegis_get_bp_earnings()` returns an array keyed by `this_month`, `last_month`, `ytd`, `all_time`. Verify the helper's actual return shape and adjust accordingly.
- **"Aegis Verified" badge** — same audit fix as provider portal: only show when `$current_user['verified']=1`. Drop "Top Rated" if it appears.

---

## Page 2 — `find-jobs.php` (Job Board)

### Currently static
Mock job cards.

### Wire to

```php
$category_filter = $_GET['category'] ?? null;
$jobs = aegis_get_open_jobs($category_filter, 50);
```

| Display element | Source |
|---|---|
| Job cards | iterate `$jobs` |
| Card title, description, budget | `$j['title']`, truncated `description`, `budget_type` + `budget_amount` |
| Practitioner name + avatar | join `users` on `$j['practitioner_id']` (extend helper or call `aegis_get_user($j['practitioner_id'])` per row — n+1 OK at this scale) |
| Posted at | `aegis_time_ago($j['posted_at'])` |
| Closes at | format `$j['closes_at']` |
| Proposals count | `$j['proposals_count']` |
| "Submit Proposal" button | opens proposal modal (POSTs to a new helper — see below) |
| Filter by category | dropdown of distinct `bp_jobs.category` values; pass via `$_GET['category']` |
| Search by keyword | client-side filter on visible cards (or server-side `LIKE` query — extend helper) |
| "Already proposed" indicator | check `aegis_get_proposals_for_bp($uid)` for any proposal where `job_id=$j['id']` |

### Submit Proposal flow (new helper needed)

```php
function aegis_submit_proposal(string $job_id, string $bp_id, string $cover_letter,
                                float $rate, string $rate_type, string $timeline): string {
    $id = 'prop_' . uniqid();
    aegis_db()->prepare(
      "INSERT INTO bp_proposals (id, job_id, bp_id, cover_letter, proposed_rate,
                                  proposed_rate_type, proposed_timeline, status, submitted_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', datetime('now'))"
    )->execute([$id, $job_id, $bp_id, $cover_letter, $rate, $rate_type, $timeline]);

    aegis_db()->prepare("UPDATE bp_jobs SET proposals_count = proposals_count + 1 WHERE id = ?")
              ->execute([$job_id]);

    aegis_log_activity($bp_id, 'proposal_submitted', "Submitted proposal for: <job title>", ...);
    return $id;
}
```

### Notes
- The "New Jobs for BP" filter (`aegis_get_new_jobs_for_bp`) is for the dashboard widget. The full `find-jobs.php` page should show all open jobs regardless of match.

---

## Page 3 — `proposals.php` (My Proposals)

### Wire to

```php
$status = $_GET['status'] ?? null;   // pending|accepted|declined|withdrawn|null=all
$proposals = aegis_get_proposals_for_bp($current_user['id'], $status);
```

| Display element | Source |
|---|---|
| Tabs (All / Pending / Viewed / Accepted / Declined) | filter by `status` |
| Per-row job title + practitioner | join `bp_jobs` and `users` (extend helper or n+1) |
| Submitted at | `aegis_time_ago($p['submitted_at'])` |
| Proposed rate | `$p['proposed_rate']` + `$p['proposed_rate_type']` (hourly/fixed) — wrap in `.bp-money` |
| Status pill | colored by `$p['status']` |
| "View Details" → modal | render `cover_letter`, `proposed_timeline`, full job details |
| "Withdraw" button (if status=pending) | UPDATE proposals SET status='withdrawn' (new helper: `aegis_withdraw_proposal($id, $bp_id)`) |
| Empty state | "No proposals yet — browse jobs" with link to `find-jobs.php` |

### Notes
- Proposal status transitions are practitioner-driven (they accept/decline). The BP can only withdraw a pending proposal.

---

## Page 4 — `contracts.php` (My Contracts)

### Wire to

```php
$status = $_GET['status'] ?? null;
$contracts = aegis_get_contracts_for_bp($current_user['id'], $status);
```

| Display element | Source |
|---|---|
| Tabs (Active / Completed / Pending Acceptance / All) | filter by `status` |
| Card title + practitioner | join `users` |
| Billing type | `$c['billing_type']` (`hourly` / `milestone` / `fixed`) |
| Total amount | `$c['total_amount']` (or hourly rate) — wrap in `.bp-money` |
| Date range | `$c['start_date']` to `$c['end_date']` |
| Progress bar | for milestone contracts: count completed / total milestones |
| Status pill | colored by `$c['status']` |
| "View Details" → page or modal | render full contract |

### Contract detail page (`contracts.php?id=<id>`)

```php
$contract = ...;  // single SELECT
$milestones = aegis_get_milestones_for_contract($contract['id']);
$invoices = array_filter(aegis_get_invoices_for_bp($current_user['id']),
                          fn($i) => $i['contract_id'] === $contract['id']);
$practitioner = aegis_get_user($contract['practitioner_id']);
```

| Element | Source |
|---|---|
| Header: title, practitioner, status | `$contract`, `$practitioner` |
| Scope of work | `$contract['scope']` |
| Billing details | `$contract['billing_type']`, `$contract['total_amount']`, `$contract['hourly_rate']` |
| Milestone timeline | iterate `$milestones`, render with `status` pill (pending/in_progress/completed/paid), per-milestone "Submit for Approval" button when `status='in_progress'` |
| Invoices for this contract | iterate `$invoices` |
| "Message Client" button | `messages.php?to=<practitioner_id>` |
| "Submit Final Deliverable" button | when all milestones paid |

### Notes
- Helper to add: `aegis_get_contract($id)` returning a single row joined with practitioner.
- Helper to add: `aegis_submit_milestone_for_approval($milestone_id)` flips status to `pending_approval` and notifies the practitioner.

---

## Page 5 — `milestones.php`

### Wire to

```php
$status = $_GET['status'] ?? null;
$milestones = aegis_get_milestones_for_bp($current_user['id'], $status);
```

| Display element | Source |
|---|---|
| Tabs (Upcoming / In Progress / Pending Approval / Completed / All) | filter |
| Per-row contract title | join `bp_contracts` (extend helper) |
| Per-row practitioner | from joined contract |
| Title + description | `$m['title']`, `$m['description']` |
| Amount | `$m['amount']` — wrap in `.bp-money` |
| Due date | `$m['due_date']` — overdue if past and `completed_at IS NULL` |
| Status pill | colored by `$m['status']` |
| "Submit for Approval" button | `aegis_submit_milestone_for_approval($id)` (new helper) |
| "View Contract" link | `contracts.php?id=<contract_id>` |
| Empty state | "No milestones yet — accept a milestone-based contract" |

---

## Page 6 — `finances.php`

### Wire to

```php
$earnings = aegis_get_bp_earnings($current_user['id']);
// Expected shape: ['this_month' => N, 'last_month' => N, 'ytd' => N, 'all_time' => N,
//                  'by_month' => [ 'Jan 2025' => 1500, ... ]]
```

| Display element | Source |
|---|---|
| Big revenue cards (this month, YTD, all-time) | `$earnings['this_month']`, `['ytd']`, `['all_time']` — wrap in `.bp-money` |
| Revenue chart | `$earnings['by_month']` — bar or line chart |
| Earnings by practitioner table | derive from invoices: GROUP BY practitioner_id, SUM(amount) WHERE status='paid' |
| Earnings by contract type | derive from contracts JOIN invoices |
| Payout history table | new model needed — see Notes |
| Tax docs section | placeholder until tax doc model exists |

### Notes
- **Payout model not yet built.** `aegis_get_bp_earnings` returns earnings (paid invoices). Payouts (Aegis → BP bank account) are a separate Stripe-driven concept. Defer the Payout History panel; show "Tracked once Stripe Connect is enabled" placeholder.
- **Privacy toggle** affects this page heavily — every `$` figure should have `.bp-money`.
- **Verify the actual return shape** of `aegis_get_bp_earnings()` before wiring; adjust array keys to match.

---

## Page 7 — `invoices.php`

### Wire to

```php
$status = $_GET['status'] ?? null;
$invoices = aegis_get_invoices_for_bp($current_user['id'], $status);
```

| Display element | Source |
|---|---|
| Tabs (All / Paid / Pending / Overdue) | filter |
| Per-row invoice number | `$i['invoice_number']` |
| Per-row practitioner | join `users` on `practitioner_id` |
| Per-row contract | join `bp_contracts` if `contract_id` set |
| Amount | `$i['amount']` — wrap in `.bp-money` |
| Issued / Due / Paid dates | `$i['issued_at']`, `$i['due_at']`, `$i['paid_at']` |
| Status pill | colored by `$i['status']` (paid/pending/overdue) — overdue computed as `due_at < now AND paid_at IS NULL` |
| "View Invoice" → modal | full details with notes |
| "Download PDF" | (out of scope — placeholder) |
| "Send Reminder" button | for overdue, opens compose modal pre-filled, sends Aegis message |
| **"Create Invoice" button (prominent)** | opens new-invoice modal — see below |

### Create Invoice flow (new helper needed)

```php
function aegis_create_invoice(string $bp_id, string $practitioner_id,
                               ?string $contract_id, ?string $milestone_id,
                               float $amount, string $currency, string $due_at,
                               string $notes): string {
    // generate next invoice_number per BP
    // INSERT INTO bp_invoices ... status='pending'
}
```

### Notes
- Audit feedback: "Service → Line Item rename" — when adding line items to an invoice, label the input "Line Item" not "Service".
- Audit feedback: "Net 30 hint" — show "Net 30 standard" near the due date input.
- Audit feedback: "Aegis-facilitates-payments comment block" — show a banner: "Aegis facilitates the messaging and contract — payment is between you and the practitioner directly (or via Stripe Connect when enabled)."

---

## Page 8 — `payment-setup.php`

### Currently static
Bank account form, payout method config.

### Wire to

This page is the Stripe Connect onboarding screen. Wire to:

| Display element | Source |
|---|---|
| Stripe Connect status | `$current_user['stripe_connected']` |
| "Connect Stripe" button | when not connected — kicks off Stripe OAuth (out of scope to implement, but the button is wired) |
| Bank account preview | when connected — read from Stripe API (out of scope) |
| Tax form upload | (out of scope) |

### Notes
- Defer real implementation. Just wire the connection-status pill and the call-to-action button.
- Until Stripe is wired, the rest of the portal should still work — `bp_invoices.status='paid'` is a manual flip the practitioner does, simulating off-platform payment.

---

## Page 9 — `provider-directory.php`

### Currently static
List of practitioners.

### Wire to

```php
$practitioners = aegis_get_users_by_role('practitioner');
$practitioners = array_filter($practitioners, fn($p) => !empty($p['practitioner_public']));
```

| Display element | Source |
|---|---|
| Provider cards | iterate `$practitioners` |
| Card name, creds, specialty, location | from user row |
| "View Public Profile" button | `aegis_practitioner_public_url($p)` |
| "Send Message" button | `messages.php?to=<id>` |
| Filters | by specialty, location, services_mode |

### Notes
- **Filter to public-only.** Don't show practitioners with `practitioner_public=0`.
- Eventually replace with a connection model — a BP "saves" a practitioner to their network.

---

## Page 10 — `messages.php`

Same shape as the other portals.

| Element | Source |
|---|---|
| Thread list | `aegis_get_threads_for_user($uid)` |
| Messages | `aegis_get_messages($thread_id)` |
| Send | `aegis_send_message($thread_id, $uid, $body)` |
| Right pane partner card | links to `aegis_practitioner_public_url()` for practitioners |

### Notes
- Audit feedback: remove Phone/Video Call buttons. Keep only Send + Attach.
- Audit feedback: "Filter by Topic" label rename for the filter dropdown.

---

## Page 11 — `notifications.php` (Activity Log)

```php
$events = aegis_get_activity($current_user['id'], 50, $filters);
$unread = aegis_get_unread_activity_count($current_user['id']);
```

BP-relevant filter chips: `proposal_*`, `contract_*`, `milestone_*`, `invoice_*`, `message`, `job_match`.

---

## Page 12 — `profile.php` (read-only)

```php
$profile  = aegis_profile_data($current_user);
$stats    = aegis_profile_stats($current_user);
$sections = aegis_profile_sections($current_user);
```

| Element | Source |
|---|---|
| Hero name | `bp_business_name` if set, else `display_name` |
| Type pill | `bp_type` |
| Verified pill | `verified` |
| Stat strip | `$stats` (BP-flavored: contracts, projects, rating, response time) |
| All sections | iterate `$sections` |
| Services Offered tag list | parse `bp_categories` JSON |
| "View Public Profile" button | `aegis_bp_public_url($current_user)` — render only when not null |
| Profile completeness widget | `$profile['completion']` |

### Notes
- The page should clearly state public/private state. If `business_partner_public=0`, render a banner: "Your public profile is hidden. Toggle on in Edit Profile to be discoverable at /business/<your-slug>."

---

## Page 13 — `edit-profile.php`

```php
$groups = aegis_edit_profile_groups($current_user);
```

BP-specific fields to add to the helper:

| Field | Column | UI |
|---|---|---|
| Show BP profile publicly | `business_partner_public` | Toggle |
| BP type | `bp_type` | Read-only or admin-only edit |
| Business name | `bp_business_name` | Text |
| Team size (agency only) | `bp_team_size` | Number |
| Hourly rate | `bp_hourly_rate` | Number with $ |
| Categories | `bp_categories` | Multi-select (stored as JSON) |
| Slug | `slug` | Read-only with edit-once warning |

### POST handler
Whitelist updateable columns. Run `UPDATE users SET … WHERE id = ?`. Toast on success.

### Notes
- Audit feedback: "Company Description rename" — use whatever specific copy was finalized.
- Audit feedback: "manual-entry services & fees" — services are now a free-text list per BP, not a fixed dropdown.
- Audit feedback: "free-text specializations" — same.
- Audit feedback: "50-state grid" — service-coverage UI as checkbox grid of US states.

---

## Page 14 — `settings.php`

| Tab | Source |
|---|---|
| Profile summary | `aegis_profile_data()` |
| Account & Login | email, last_login, password change |
| Notifications | new `user_preferences` table |
| Privacy / Visibility | links to edit-profile for `business_partner_public` |
| Subscription / Billing | `bp_type`, plan tier (if BPs have tiers) |
| Account actions | pause / deactivate |
| Integrations | Stripe Connect status, API tokens (out of scope) |

---

## Page 15 — `team.php` (agency only)

Render only when `$is_agency`. Shows team members with roles.

### Currently static
Hardcoded team list.

### Wire to

**No `bp_team_members` table exists.** Recommended:

```sql
CREATE TABLE bp_team_members (
  id TEXT PRIMARY KEY,
  bp_id TEXT NOT NULL,                 -- the agency
  user_id TEXT,                        -- nullable; if user has Aegis account
  email TEXT NOT NULL,
  full_name TEXT,
  role TEXT NOT NULL,                  -- admin/manager/specialist/viewer
  department TEXT,
  status TEXT DEFAULT 'invited',       -- invited/active/disabled
  invited_at TEXT,
  joined_at TEXT
);
```

Helpers: `aegis_get_team_members($bp_id)`, `aegis_invite_team_member(...)`, `aegis_assign_team_role(...)`, `aegis_remove_team_member(...)`.

### Notes
- This page is hidden in the sidebar for freelancer accounts via the existing `if ($is_agency)` branch.
- Defer the implementation until the rest of the agency-mode features are in.

---

## Page 16 — `overview.php` (Start Here)

```php
$ov = aegis_overview_data($current_user);
```

Helper returns BP-flavored copy.

---

## Implementation order (recommended)

### Phase 1 — pure model-to-view wiring (fastest wins, no schema work)
1. `overview.php` — `aegis_overview_data()`.
2. `profile.php` — `aegis_profile_data()` + sections + stats.
3. `notifications.php` — `aegis_get_activity()`.
4. `messages.php` — threads + messages.
5. `find-jobs.php` — `aegis_get_open_jobs()`.
6. `proposals.php` — `aegis_get_proposals_for_bp()`.
7. `contracts.php` — `aegis_get_contracts_for_bp()`.
8. `milestones.php` — `aegis_get_milestones_for_bp()`.
9. `invoices.php` — `aegis_get_invoices_for_bp()`.
10. `finances.php` — `aegis_get_bp_earnings()`.
11. `provider-directory.php` — `aegis_get_users_by_role('practitioner')` filtered to public.
12. `index.php` — combine all the above.
13. Sidebar badges — `aegis_count_bp_badges()`.

### Phase 2 — write actions
14. Submit Proposal — new `aegis_submit_proposal()`.
15. Withdraw Proposal — new `aegis_withdraw_proposal()`.
16. Submit Milestone for Approval — new `aegis_submit_milestone_for_approval()`.
17. Create Invoice — new `aegis_create_invoice()`.
18. `edit-profile.php` — extend `aegis_edit_profile_groups()` with BP-specific fields, POST handler.

### Phase 3 — model extensions
19. Payouts table + helpers (when Stripe Connect is in scope).
20. `bp_team_members` table + helpers (agency-only `team.php`).

### Phase 4 — defer
21. `payment-setup.php` Stripe Connect flow.
22. Tax doc upload + storage.
23. Subscription/billing tier model for BPs.

---

## Cross-cutting BP rules

### Revenue privacy toggle
- Wrap **every** `$` figure in `<span class="bp-money">$1,234</span>`.
- Toggle in topbar persists via cookie `bp_revenue_visible`.
- CSS rule: `body[data-revenue-hidden="1"] .bp-money { filter: blur(6px); user-select: none; }`.
- The rule applies to dashboard, contracts, milestones, invoices, finances. Not to forms (so the BP can still see what they're typing).

### Agency vs Freelancer branching
- Sidebar already branches via `$is_agency` (Team Management nav appears).
- Also affects: hero copy ("Agency Account" / "Freelancer Account"), edit-profile fields (Team Size only for agency), team.php (agency only), settings tabs (agency adds "Team Permissions").
- Use the `?type=agency` query string only during demo. After auth wiring, drop it and rely on the user row.

### Audit fixes (do alongside Phase 1)
| # | Item | Page | Fix |
|---|------|------|-----|
| - | Top Rated badge removed | dashboard, profile | grep & remove |
| - | Aegis Verified criteria | (data) | use `users.verified` only |
| - | Phone/Video Call buttons removed | messages | grep & remove |
| - | Filter by Topic label | messages | string replace |
| - | Service → Line Item rename | invoices | string replace in create-invoice modal |
| - | Net 30 hint | invoices | add helper text |
| - | Aegis facilitates payments banner | invoices | add comment block |
| - | Demographics → Identity (4-group checklist) | settings | restructure |
| - | Manual-entry services + free-text specializations | edit-profile | dropdown → multi-tag input |
| - | 50-state grid | edit-profile | checkbox grid |
| - | Job Posting metrics duplicated | (in BP context, "duplicate cards" check) | drop one set |

---

## Helper additions needed (BP-specific)

| Helper | Returns | Used by |
|---|---|---|
| `aegis_submit_proposal($job_id, $bp_id, $cover, $rate, $type, $timeline)` | proposal id | find-jobs.php |
| `aegis_withdraw_proposal($proposal_id, $bp_id)` | void | proposals.php |
| `aegis_get_contract($id)` | row joined with practitioner | contracts.php detail |
| `aegis_submit_milestone_for_approval($milestone_id)` | void | milestones.php |
| `aegis_create_invoice(...)` | invoice id + invoice_number | invoices.php |
| `aegis_unread_count($thread_id, $uid)` | int | messages.php (shared) |
| `aegis_get_team_members($bp_id)` | rows | team.php (Phase 3) |
| `aegis_invite_team_member($bp_id, $email, $role)` | invite token | team.php (Phase 3) |
| `aegis_deactivate_user($uid)` | void | settings.php (shared) |

---

## What this document doesn't cover

- Stripe Connect OAuth flow.
- Stripe webhook → invoice status sync.
- Tax document storage and 1099 generation.
- BP subscription tiers (do BPs pay for the platform? unclear).
- Multi-currency support (everything assumes USD via `bp_*.currency` columns).
- Provider rating / review system (beyond the seed reviews on public profiles).
- Job alerts / saved searches with email notifications.
