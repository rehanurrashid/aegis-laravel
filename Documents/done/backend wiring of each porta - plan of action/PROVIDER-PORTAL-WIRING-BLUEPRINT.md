# Provider Portal — Dynamic-Wiring Blueprint

**Status:** as of merge of `aegis-backend-global.zip` + `aegis-public-pages.zip`.
**Scope:** the provider portal only (`/provider-portal/*.php`). CS, SS, BP portals are listed at the end as a follow-up.
**Goal:** turn each page's hardcoded mock arrays into reads from the existing helpers in `_shared/models.php`.

---

## How to use this document

Each page below has four sections:

1. **Bootstrap** — the require + identity lines that go at the top.
2. **Currently static / hardcoded** — what the live page renders from inline arrays today.
3. **Wire to** — the model helper(s) and `users` columns that should drive each piece.
4. **Notes / open questions** — fields that have no model yet, or decisions to make.

Apply pages in the order listed. Earlier pages establish patterns (sidebar viewer, hero pills, helper conventions) that later pages reuse.

---

## Foundation already in place

After the merge, you have:

- `users` table with: `id, role, display_name, credentials, email, phone, avatar_initials, title, organization, specialty, location, bio, slug, slug_locked_at, practitioner_public, cs_public, business_partner_public, tier, services_mode, maat_addon, cs_account_type, stripe_connected, verified, invited_by_id, about_me, bp_*, created_at, last_login`.
- `user_roles` join table (multi-role per human).
- 60 helpers in `_shared/models.php`.
- Tables: `users, user_roles, continuity_plans, plan_stewards, plan_tasks, plan_incident_configs, critical_incidents, incident_tasks, vault_items, message_threads, messages, activity_events, bp_jobs, bp_proposals, bp_contracts, bp_milestones, bp_invoices`.

Demo session: a logged-in viewer is identified by the `aegis_uid` cookie, resolved by `aegis_current_user()`. For demo, set the cookie to `p_sarah` to log in as her.

---

## Cross-page conventions

Every provider-portal page should start with the same five lines:

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared/db.php';
require_once __DIR__ . '/../_shared/models.php';
require_once __DIR__ . '/../_shared/icons.php';

$current_user = aegis_current_user();
if (!$current_user || !aegis_user_has_role($current_user['id'], 'practitioner')) {
    header('Location: /onboarding/signin.html?return=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
$active_page = '<page-key>';   // for sidebar.php highlight
```

Then `_shared/header.php` and `_shared/sidebar.php` both have access to `$current_user` for hero/avatar/role rendering.

The sidebar's user block (avatar, name, "Psychiatrist, MD · NY") should read from `$current_user`:

```php
<div class="sidebar-user-avatar"><?= htmlspecialchars($current_user['avatar_initials']) ?></div>
<div class="sidebar-user-name"><?= htmlspecialchars($current_user['display_name']) ?></div>
<div class="sidebar-user-role">
  <?= htmlspecialchars($current_user['title'] ?? '') ?>
  <?php if (!empty($current_user['location'])): ?>· <?= htmlspecialchars(preg_replace('/^.+,\s*/', '', $current_user['location'])) ?><?php endif; ?>
</div>
```

The topbar's profile chip should do the same.

---

## Page 1 — `dashboard.php` (Dashboard)

### Bootstrap
Standard. `$active_page = 'dashboard';`

### Currently static
- Welcome banner: "Welcome back, Dr. Sarah Johnson"
- 4 metric cards: Active Referrals (3), Network Connections (47), Patient Roster (124), Continuity Plan Status (✓ Complete)
- "My Continuity Plan" card with: signed date, expiration, all 4 stewards listed
- "Recent Activity" feed (5 hardcoded rows)
- "My Network" mini-list (Bill Garrett, Karen Liu, ...) with the **bug** that Bill Garrett opens a Provider Profile modal
- "News & Resources" preview cards
- "Profile completeness" widget

### Wire to

| Display element | Source |
|---|---|
| Welcome / hero name | `$current_user['display_name']` |
| Avatar initials | `$current_user['avatar_initials']` |
| "Aegis Verified" badge | `$current_user['verified']` (don't show "Top Rated" — see audit) |
| Plan card → signed date | `aegis_get_plan_by_practitioner($uid)['signed_at']` |
| Plan card → annual review | `aegis_get_plan_by_practitioner($uid)['annual_review_date']` |
| Plan card → status pill | `aegis_get_plan_by_practitioner($uid)['status']` |
| Plan card → steward chips | `aegis_get_plan_stewards($plan_id)` — render each as a chip with `display_name`, `role_in_plan`, `assignment_status` |
| Plan card → "X of 7 incidents enabled" | `aegis_get_plan_incident_configs($plan_id)` — count where `enabled=1` |
| Active Referrals count | (no model yet — see Notes) |
| Network connections count | (no model yet) |
| Patient roster count | `count(aegis_get_vault_items($uid, 'roster'))` |
| Recent Activity feed | `aegis_get_activity($uid, 5)` — each row already has `event_type, title, body, created_at`; render with `aegis_time_ago()` |
| Recent Activity unread badge | `aegis_get_unread_activity_count($uid)` |
| News & Resources cards | (static for now — see Notes) |
| Profile completeness % | `aegis_profile_data($current_user)['completion']['percent']` |
| Network preview | (no helper yet — see Notes) |
| Bill Garrett card link | When the network preview is wired, **link to `aegis_bp_public_url($bp_user)`** for Business Partners and `aegis_practitioner_public_url($p_user)` for Practitioners. This fixes the audit bug. |

### Notes / open questions
- **Referrals model not built.** A `referrals` table doesn't exist. Either create one (`referrals(id, from_id, to_id, patient_initials, status, created_at)`) or leave the metric mocked with a `// TODO` until later.
- **Network connections model not built.** Same — add a `connections(user_a, user_b, type, since)` table or leave mock.
- **News articles model not built.** Static for now; wire later when a CMS/content table is added.

---

## Page 2 — `profile.php` (My Profile, read-only view)

### Bootstrap
Standard. `$active_page = 'profile';`

### Currently static
The whole page — hero, badges, stat strip, all sections — is hardcoded markup mirroring the modal sample.

### Wire to

The model already returns everything assembled:

```php
$profile = aegis_profile_data($current_user);
// Returns: ['identity' => [...], 'completion' => [...], 'stats' => [...],
//          'sections' => [...], 'role_summary' => [...]]
```

| Display element | Source |
|---|---|
| Hero name + creds | `$profile['identity']['display_name']`, `$profile['identity']['credentials']` |
| Hero role line | `$current_user['title']` + ' · ' + `$current_user['organization']` |
| Aegis Verified pill | `$current_user['verified']` |
| Plan tier pill | `$current_user['tier']` (`practice` / `essentials` / `continuity_access`) |
| Stat strip (4 cells) | `aegis_profile_stats($current_user)` — already returns `[{icon, value, label, tone}, ...]`, just iterate |
| All profile sections | `aegis_profile_sections($current_user)` — returns `[{icon, title, rows: [{label, value}], items: [...]}]`, render with the same `pp-section`/`pp-info-row` markup |
| Profile completeness widget | `$profile['completion']` — has `percent`, `missing_items` |
| Hero buttons | `aegis_practitioner_public_url($current_user)` powers the "View Public Profile" button. Edit Profile → `edit-profile.php`. Share Profile uses the public URL too. |
| "View as practitioner / steward" tab (multi-role) | If `aegis_user_roles($uid)` returns more than one role, render a role switcher; else hide it |

### Notes / open questions
- The page currently has all section markup inline. After wiring, almost all `<div class="pp-section">…</div>` blocks should disappear and be replaced by a single `foreach ($profile['sections'] as $section)` loop. Big diff.
- **Public profile preview button**: when `practitioner_public=1`, link to `aegis_practitioner_public_url($current_user)`. When 0, show "Public profile is hidden — toggle on in Edit Profile" with a link.

---

## Page 3 — `edit-profile.php`

### Bootstrap
Standard. `$active_page = 'profile';` (shares the sidebar item).

### Currently static
Tabbed editor with hardcoded fields per tab.

### Wire to

The model returns the full edit-form schema:

```php
$groups = aegis_edit_profile_groups($current_user);
// Returns 6 groups: Personal Identity, About & Bio, Contact Information,
//                  Clinical Specialty, Plan & Add-ons, Preferences
// Each: ['icon' => 'profile', 'title' => '...', 'fields' => [
//          ['name' => 'display_name', 'label' => '...', 'type' => 'text',
//           'value' => '...', 'required' => true], ...
//        ]]
```

Render with a single nested loop. Each field type (`text`, `textarea`, `select`, `toggle`) maps to a small render helper.

**New visibility-flag fields to add** (don't exist in `aegis_edit_profile_groups` yet — they need to be added to the helper):

| Field | Column | UI |
|---|---|---|
| Show my Practitioner profile publicly | `practitioner_public` | Toggle switch |
| Practitioner public URL | `slug` | Read-only (with "Edit slug" link if `slug_locked_at` is null) |
| Show my Continuity Steward profile publicly | `cs_public` | Toggle (only render if user has `continuity_steward` role AND `cs_account_type='business'`) |
| Show my Business Partner profile publicly | `business_partner_public` | Toggle (only render if user has `business_partner` role) |
| Services Mode | `services_mode` | Toggle (controls Clinical Services Panel on public page) |

### Form submission
- POST handler at the top of the file, `if ($_SERVER['REQUEST_METHOD'] === 'POST')`.
- Whitelist updateable columns (NEVER trust raw `$_POST` — only allow the columns from `aegis_edit_profile_groups`).
- Run an `UPDATE users SET … WHERE id = ?` via PDO prepared statement.
- Re-render with a success toast and an optional `aegis_log_activity($uid, 'profile_updated', '...', ...)` call.
- If the user changes `practitioner_public` 0→1, redirect to `aegis_practitioner_public_url()` with a "preview your public profile" toast.

### Notes / open questions
- **Slug editing.** Today `aegis_claim_slug()` is locked once `slug_locked_at` is set. Decision: should practitioners be able to edit it ever? Recommend "yes once, with a confirmation modal warning that all old links break."
- **Avatar upload.** Currently just initials. If photo upload is in scope, add a `users.avatar_url` column.

---

## Page 4 — `referrals.php`

### Currently static
Mock referrals list, mock detail modal with `Diagnosis` and `Clinical Notes` (which the audit said should be `Notes` only and `Diagnosis` should go).

### Wire to
**No `referrals` table exists.** Three options:

1. **Build the model now.** Add table:
   ```sql
   CREATE TABLE referrals (
     id TEXT PRIMARY KEY,
     from_practitioner_id TEXT NOT NULL,
     to_practitioner_id TEXT NOT NULL,
     patient_initials TEXT,
     reason TEXT,
     notes TEXT,
     status TEXT DEFAULT 'pending', -- pending|accepted|declined|completed
     created_at TEXT, accepted_at TEXT, declined_at TEXT
   );
   ```
   Then helpers: `aegis_get_referrals_sent($uid)`, `aegis_get_referrals_received($uid)`, `aegis_create_referral(...)`, `aegis_update_referral_status(...)`. Seed 3-4 referrals into `seed.json`.

2. **Mock the table only** — define helpers that return canned arrays. Cheap, but creates a second source of truth.

3. **Defer until backend phase.** Keep static, mark with TODO, knock out the audit fixes (rename Clinical Notes → Notes, drop Diagnosis field, add SLA tooltip) on the static markup.

**Recommendation:** Option 1. The seed cost is small and it unblocks both the dashboard's Active Referrals count and the activity feed (`aegis_log_activity` on referral create/accept).

### Notes
- Audit items #4, #5, #6 (Diagnosis remove, Clinical Notes → Notes, SLA tooltip) apply here regardless of which option is chosen.
- "Send Referral" buttons in dashboard / network / public profile all point to the same modal — keep one modal, parameterize the recipient.

---

## Page 5 — `network.php`

### Currently static
Three tabs (Provider Network, Business Network, Shadow Network), each a hardcoded grid of cards. The "providerDetailModal" opens regardless of whether the card is a Practitioner or a Business Partner — that's the Bill Garrett bug from the audit.

### Wire to
**No `connections` table exists.** Same options as referrals.

For now, the cleanest interim fix is to **remove the modal entirely** and link cards directly to the public profile pages we just built:

```php
<a href="<?= htmlspecialchars(aegis_practitioner_public_url($p) ?? '#') ?>" class="provider-card">…</a>
```

For Business Partners:
```php
<a href="<?= htmlspecialchars(aegis_bp_public_url($bp) ?? '#') ?>" class="provider-card">…</a>
```

This single change kills the Bill Garrett bug because the URL routing decides the page (no more shared modal).

### Wire to (when connections model exists)

| Display element | Source |
|---|---|
| Provider Network tab | New `aegis_get_connections($uid, 'practitioner')` returning users joined to current_user via connections table |
| Business Network tab | `aegis_get_connections($uid, 'business_partner')` |
| Shadow Network tab | (still TBD — different model) |
| Card name + role | from joined `users` row |
| Card avatar | `avatar_initials` |
| Card "View" button | `aegis_practitioner_public_url()` / `aegis_bp_public_url()` |
| Card "Message" button | links to `messages.php?to=<id>` |
| "Add to Network" search | new helper `aegis_search_users($query, $role)` — filter to users with the right `*_public=1` flag |

### Notes
- The "Add Business Contact" modal can be deferred; the URL-based detail-page change alone fixes the bug.
- Shadow Network is a `tier='continuity_access'` concept — only that tier sees this tab.

---

## Page 6 — `services.php` (My Services & Fees)

### Currently static
Two-tab page: Clinical Services (provider-to-provider) and Business Services. All hardcoded service cards with prices.

### Wire to
**No `services` table exists.** Recommended schema:

```sql
CREATE TABLE provider_services (
  id TEXT PRIMARY KEY,
  practitioner_id TEXT NOT NULL,
  service_type TEXT NOT NULL,        -- 'clinical' or 'business'
  name TEXT NOT NULL,                -- 'Individual Supervision'
  description TEXT,
  price_amount REAL,
  price_unit TEXT,                   -- 'hour' / 'session' / 'person'
  duration_minutes INTEGER,
  delivery_mode TEXT,                -- 'in_person' / 'telehealth' / 'both'
  availability TEXT DEFAULT 'open',  -- 'open' / 'limited' / 'by_request' / 'closed'
  active INTEGER DEFAULT 1
);
```

Helpers: `aegis_get_services($uid, $type=null)`, `aegis_save_service($data)`, `aegis_delete_service($id)`.

This **directly powers** the Clinical Services Panel on the public provider profile, which currently shows hardcoded "Individual Supervision $150/hr" etc. cards.

### Page top-toggle
- The page's main toggle "Provider Services Mode: ON/OFF" writes to `users.services_mode`.
- When OFF, the public provider profile hides the Clinical Services Panel entirely (already implemented).

### Notes
- Pending audit items #7 ("My Services page — many sub-items pending") — gets resolved as part of building this model.

---

## Page 7 — `executors.php` (Continuity Stewards)

### Currently static
Hero with mock CS list. "I'm Executor For" tab if the user is also a CS.

### Wire to

```php
$plan = aegis_get_plan_by_practitioner($current_user['id']);
$stewards = $plan ? aegis_get_plan_stewards($plan['id'], 'continuity') : [];
```

| Display element | Source |
|---|---|
| Steward cards | `$stewards` — each has `display_name, credentials, organization, location, role_in_plan, assignment_status, avatar_initials` |
| Card "View Public Profile" button | `aegis_cs_public_url($steward)` (returns null if not eligible — show "Profile not yet published" pill instead) |
| Card "Message" button | `messages.php?to=<steward_id>` |
| Card status (Designated / Pending / Active) | `assignment_status` |
| "I'm Executor For" tab | `aegis_get_providers_for_steward($current_user['id'], 'continuity')` — only if user has `continuity_steward` role too |
| Empty state | When `$stewards` is empty: CTA "Designate a Continuity Steward" linking to a search/discovery flow |

### Notes
- Audit item: rename "Executors" → "Continuity Stewards" everywhere on this page (some labels still say "Executor" per old copy).

---

## Page 8 — `dsr.php` (Support Stewards)

### Wire to
Mirror of `executors.php` but with `'support'` filter:

```php
$plan = aegis_get_plan_by_practitioner($current_user['id']);
$ss   = $plan ? aegis_get_plan_stewards($plan['id'], 'support') : [];
```

Note: Support Stewards have **no public profile** (`ss_public` doesn't exist). So no "View Public Profile" button — only Message and contact details.

### Notes
- Continuity Access tier providers may not see this page. Gate with `if ($current_user['tier'] !== 'continuity_access')`.

---

## Page 9 — `agreements.php` (Important Documents)

### Currently static
Hardcoded list of contract/agreement cards.

### Wire to

| Display element | Source |
|---|---|
| Continuity Plan agreement | `aegis_get_plan_by_practitioner($uid)` — show signed date, signature name, "Active" pill, plan version |
| Steward agreements | `aegis_get_plan_stewards($plan_id)` — for each steward with `assignment_status='active'`, render an agreement card with their certification date |
| Annual review reminder | `$plan['annual_review_date']` — if within 30 days, show banner |
| "Sign now" buttons | for stewards where `assignment_status='pending'` — opens existing signing flow |
| Past attestations | `aegis_get_attestation_states($plan_id)` — has the audit trail of every signature event |

### Notes
- This page is critical for the Continuity Access tier — it's the main thing they do.

---

## Page 10 — `documents.php` (Document Vault)

### Currently static
Tabs for the four zones (Standard, Credentials, Emergency, Roster).

### Wire to

| Display element | Source |
|---|---|
| Items per zone tab | `aegis_get_vault_items($current_user['id'], $zone)` |
| Item card → title, type, uploaded date | from item rows |
| "Upload" button | POSTs to `aegis_add_vault_item([...])` |
| Roster tab — Patient/Client list (vault-based) | `aegis_get_vault_items($uid, 'roster')` |
| Item count badges per tab | `count(aegis_get_vault_items($uid, $zone))` |
| "Who can access" indicator | `aegis_is_vault_unlocked_for_cs($cs_id, $uid)` — show a list of authorized stewards if any |

### Notes
- The Roster tab replaces the old `patients.php` page (which was deleted per the audit). Roster items are vault items with `zone='roster'`.

---

## Page 11 — `finances.php`

### Currently static
Mock revenue chart, mock payout history.

### Wire to
**No invoice/payment model for practitioners.** The `bp_*` tables are for Business Partners only. For practitioners:

- Decision needed: are practitioners billed for their plan tier ($/month for Practice tier), or do they invoice patients through Aegis?
- If just plan billing: a `subscriptions(user_id, tier, status, next_charge_at, stripe_subscription_id)` table.
- If patient invoicing: a `practitioner_invoices(...)` table mirroring `bp_invoices`.

### Recommendation
Defer this page entirely until the billing model is decided with Carizma. Keep it as-is with a TODO. This is the biggest "don't make it dynamic yet" page on the list.

---

## Page 12 — `messages.php`

### Currently static
Existing 3-pane chat UI with hardcoded conversations.

### Wire to

| Display element | Source |
|---|---|
| Left pane — thread list | `aegis_get_threads_for_user($current_user['id'])` |
| Per-thread → other person's name & avatar | from joined `users` row in helper output (need to verify the helper returns this; if not, JOIN in helper) |
| Per-thread → last message preview | `last_message_body` (column may need adding) or `aegis_get_messages($thread_id)[last]` |
| Per-thread → unread count | new helper `aegis_unread_count($thread_id, $uid)` |
| Center pane — message stream | `aegis_get_messages($thread_id)` |
| Compose input → Send | `aegis_send_message($thread_id, $current_user['id'], $body)` |
| Right pane — partner profile mini-card | `aegis_get_user($other_id)` + link to their public profile via `aegis_practitioner_public_url()` etc. |

### Notes
- Audit said "remove Phone/Video Call buttons" on the BP messages page; same applies on the practitioner side. Keep only Send Message + Attach File.
- Search-by-topic filter is a frontend-only feature for now.

---

## Page 13 — `news.php`, `events.php`, `library.php`

### Currently static
Hardcoded article cards, event cards, video cards.

### Wire to
**No content model.** Defer all three to a later phase. Keep static. This is correct — content management can be its own initiative.

The audit's "Library video/tutorial catalog still placeholder" item is acknowledging exactly this state.

### Recommendation
Mark the three pages with a `// CONTENT-MODEL-PHASE` comment block and move on.

---

## Page 14 — `job-postings.php`

### Currently static
Job posting cards.

### Wire to

```php
$jobs = aegis_get_open_jobs(category: null, limit: 50);
```

Each job row has whatever's in `bp_jobs`. The rendering should be straightforward iteration.

| Display element | Source |
|---|---|
| Job cards | `aegis_get_open_jobs()` |
| Filter by category | pass `$category` parameter |
| "Apply / Submit Proposal" button | opens existing modal — POST handler creates a row in `bp_proposals` (helper TBD: `aegis_submit_proposal(...)`) |

### Notes
- **Open question:** is this page even needed in the practitioner portal? Job postings are primarily a Business Partner feature. The audit's #1 concern was "Job Posting metrics duplicated" — implying it exists on the BP dashboard already. Decision: hide this nav item from the practitioner sidebar unless the practitioner has the `business_partner` role too.

---

## Page 15 — `overview.php` (Start Here)

### Currently static
Hero, key terms, why Aegis, how to use, FAQ.

### Wire to

```php
$ov = aegis_overview_data($current_user);
// Returns: hero_eyebrow, hero_title, hero_sub, notice_text, terms, why,
//          how_steps, faqs
```

Already fully assembled by the helper. Just iterate the four content arrays. Almost no markup change needed beyond removing inline content and inserting `foreach`.

### Notes
- One of the easiest wins. Tackle this first as a warm-up page.

---

## Page 16 — `settings.php`

### Currently static
Tabbed settings page (Profile, Security, Notifications, Privacy, etc.).

### Wire to

| Tab | Source |
|---|---|
| Profile summary | `aegis_profile_data($current_user)['identity']` (read-only — direct edits go to `edit-profile.php`) |
| Account & Login | `$current_user['email']`, last_login, password change form (no model yet) |
| Notifications | new `user_preferences(user_id, key, value)` table needed for toggles |
| Privacy / Visibility | links to `edit-profile.php` for the public-profile toggles |
| Active sessions | (no model — defer) |
| Account actions (deactivate, delete) | new helper `aegis_deactivate_user($uid)` |

### Notes
- Audit item: rename "Demographics" tab to "Identity" with the 4-group identity checklist. Applies on this page.

---

## Page 17 — `activity.php` (Notifications / Activity Feed)

### Wire to

```php
$events = aegis_get_activity($current_user['id'], 50, $filters);
$unread = aegis_get_unread_activity_count($current_user['id']);
```

| Display element | Source |
|---|---|
| Feed rows | `$events` |
| Time-ago string | `aegis_time_ago($event['created_at'])` |
| Filter chips | pass `$filters = ['event_type' => 'attestation']` etc. to helper |
| Mark all read button | `aegis_mark_all_activity_read($uid)` |
| Mark one read | `aegis_mark_activity_read($event_id, $uid)` |

### Notes
- Already fully supported by the model. Should be a clean wire-up.

---

## Implementation order (recommended)

Cheap, dependency-free wins first; bigger structural changes last.

### Phase 1 — pure model-to-view wiring (no schema changes)
1. **`overview.php`** (warmup, tiny diff) — `aegis_overview_data()` already returns everything.
2. **`profile.php`** (read-only view) — `aegis_profile_data()` + `aegis_profile_sections()` + `aegis_profile_stats()`.
3. **`activity.php`** — `aegis_get_activity()` + `aegis_time_ago()`.
4. **`messages.php`** — `aegis_get_threads_for_user()` + `aegis_get_messages()` + `aegis_send_message()`.
5. **`documents.php`** — `aegis_get_vault_items()` per zone.
6. **`executors.php` + `dsr.php` + `agreements.php`** — all three driven by `aegis_get_plan_by_practitioner()` + `aegis_get_plan_stewards()`.
7. **`index.php` (dashboard)** — assemble from the helpers above; leave Active Referrals / Network Connections counts as TODO mocks.
8. **Sidebar + topbar + header** — read `$current_user` everywhere instead of hardcoded "Dr. Sarah Johnson".

### Phase 2 — model extensions for new domains
9. **`edit-profile.php`** — render `aegis_edit_profile_groups()` + add the 4 new visibility-flag fields to that helper + write the POST handler.
10. **`network.php`** — interim fix is just URL-redirect from cards to public pages (kills Bill Garrett bug). Full connections-table wiring later.
11. **`services.php`** — new `provider_services` table, helpers, POST handlers. Then the public profile's services panel reads from it instead of hardcoded cards.

### Phase 3 — bigger schema additions
12. **Referrals model** (table + helpers + seed) → `referrals.php` becomes dynamic, dashboard count works.
13. **Connections model** (table + helpers + seed) → `network.php` becomes fully dynamic.
14. **Job postings practitioner-side** (decision: hide unless dual role) — small.

### Phase 4 — defer
15. **`finances.php`** — needs Carizma decision on billing model.
16. **`news.php`, `events.php`, `library.php`** — content-management phase.
17. **`settings.php`** — preferences/sessions tables.

---

## Cross-cutting audit fixes (do alongside Phase 1)

These don't depend on data wiring and can be knocked out as you touch each page:

| # | Item | Page | Fix |
|---|------|------|-----|
| 1 | Delete "Top Rated" badge | dashboard, profile | grep & remove |
| 2 | Define "Aegis Verified" criteria | (data) | use `users.verified` column; show only when 1 |
| 3 | Bill Garrett opens BP profile | dashboard, network | link cards to `aegis_bp_public_url()` |
| 4 | "Diagnosis" field in referral details | referrals | remove from modal markup |
| 5 | "Clinical Notes" → "Notes" | referrals | string replace |
| 6 | "SLA" tooltip / rename | referrals | add `<span data-tip="...">` |
| 8 | Job Posting metrics duplicated | job-postings | drop one set of cards |
| 9 | Network has two banners | network | drop one banner |
| 10 | "Medical Licenses" → "Clinical License" | edit-profile | string replace |
| 11 | Library catalog placeholder | library | acknowledge in TODO comment |
| 12 | Telehealth states + Preview button verification | edit-profile | wire Preview to `aegis_practitioner_public_url()` |

---

## Helper additions needed

These functions don't exist yet but are referenced above. Add them to `_shared/models.php`:

| Helper | Returns | Used by |
|---|---|---|
| `aegis_unread_count($thread_id, $uid)` | int | messages.php sidebar |
| `aegis_search_users($query, $role)` | array of users | network.php Add modal |
| `aegis_get_services($uid, $type)` | services rows | services.php, public profile |
| `aegis_save_service(array $data)` | id | services.php POST |
| `aegis_get_referrals_sent/received($uid)` | rows | referrals.php |
| `aegis_get_connections($uid, $role)` | users array | network.php |
| `aegis_deactivate_user($uid)` | void | settings.php |
| `aegis_user_role_count($uid)` | int | profile.php role switcher visibility |

---

## Other portals — follow-up summaries

When the provider portal is done, repeat this exercise for:

- **`/continuity-steward-portal/*`** — primary helpers: `aegis_get_providers_for_steward($uid, 'continuity')`, `aegis_get_incidents_for_cs($uid)`, `aegis_steward_certify(...)`. Capacity meter on public profile mirrors the active-caseload count.
- **`/support-steward-portal/*`** — primary helpers: `aegis_get_providers_for_steward($uid, 'support')`, `aegis_get_incidents_for_ss($uid)`. No public profile (no `ss_public`).
- **`/biz-portal/*`** — already has the most mature model: `aegis_get_open_jobs`, `aegis_get_proposals_for_bp`, `aegis_get_contracts_for_bp`, `aegis_get_milestones_for_bp`, `aegis_get_invoices_for_bp`, `aegis_count_bp_badges`, `aegis_get_bp_earnings`. Wiring should be the easiest of all four portals.

---

## What this document doesn't cover

- **Frontend interactions** (modal triggers, tab state, scroll behavior) — leave as-is.
- **CSS** — none of these changes touch styling. The `pp-*` family stays where it is.
- **Auth model** — currently a cookie demo (`aegis_uid`). Real auth is out of scope here.
- **Actual route guards / role gates beyond `aegis_user_has_role()`** — add as needed but not enumerated.
- **Tests** — should be written as helpers are extended, but are not part of this blueprint.
