# Continuity Steward Portal — Dynamic-Wiring Blueprint

**Status:** as of merge of `aegis-backend-global.zip` + `aegis-public-pages.zip`.
**Scope:** the CS portal only (`/continuity-steward-portal/*.php`).
**Goal:** turn each page's hardcoded mock arrays into reads from `_shared/models.php` helpers.
**Counterpart docs:** `PROVIDER-PORTAL-WIRING-BLUEPRINT.md`, plus SS and BP versions.

---

## How to use this document

Each page below has four sections:

1. **Bootstrap** — require + identity lines.
2. **Currently static / hardcoded** — what the live page renders today.
3. **Wire to** — model helper(s) and `users` columns that should drive each piece.
4. **Notes / open questions** — fields with no model yet, or decisions needed.

---

## Foundation already in place (CS-relevant)

After the merge, you have:

- `users` rows with `cs_account_type='business'` or `'invited'`, `cs_public` toggle (only used for `business` type), `stripe_connected`, `verified`, `maat_addon`.
- `continuity_plans` table (one per practitioner).
- `plan_stewards(plan_id, steward_id, steward_type, role, vault_access_level, countersigned_at, certification_at, certification_note, status)` — note the actual columns: `role` (primary/alternate), `status` (active/pending/...), and `steward_type` is the literal string `'continuity_steward'` or `'support_steward'`, not just `'continuity'`.
- `plan_tasks(plan_id, incident_type, assigned_to, ...)` — `assigned_to` is one of `'continuity_steward'`, `'support_steward'`, or `'practitioner'`.
- `critical_incidents` with the lifecycle: `reported_by_ss_id` → `verified_by_cs_id` → `closed_by_user_id`.
- `incident_tasks` (per-incident execution checklist).
- `vault_items` with zones (standard / credentials / emergency / roster). Vault is locked to a CS unless `aegis_is_vault_unlocked_for_cs($cs_id, $practitioner_id)` returns true (i.e. an active verified incident exists).
- `message_threads`, `messages`, `activity_events` (same as provider portal).

CS-relevant helpers already in `models.php`:

- `aegis_get_providers_for_steward($steward_id, 'continuity_steward')` — returns all practitioners this CS is on as Continuity Steward, joined with `continuity_plans` (`plan_id`, `plan_status`, `annual_review_date`, `steward_role`, `certification_at`, `vault_access_level`).
- `aegis_get_incidents_for_cs($cs_id)` — incidents where `verified_by_cs_id = ?` OR pending verification by this CS.
- `aegis_get_active_incident_for_practitioner($pid)` — single active incident for a practitioner, if any.
- `aegis_get_incident($incident_id)`, `aegis_get_incident_tasks($incident_id)`.
- `aegis_verify_incident($incident_id, $cs_id, $uploaded_docs, $notes)` — marks incident verified and unlocks vault.
- `aegis_complete_incident_task($task_id, $note)`, `aegis_close_incident($id, $user_id, $summary)`.
- `aegis_steward_certify($plan_id, $steward_id, $note)` — CS countersigns a plan.
- `aegis_get_plan_stewards($plan_id)`, `aegis_get_plan_tasks($plan_id, $incident_type, $assigned_to)`.
- `aegis_is_vault_unlocked_for_cs($cs_id, $pid)` and `aegis_get_vault_items($pid, $zone)`.
- `aegis_get_threads_for_user($uid)`, `aegis_get_messages($thread_id)`, `aegis_send_message($thread_id, $sender, $body)`.
- `aegis_get_activity($uid, $limit, $filters)`, `aegis_log_activity(...)`.
- `aegis_profile_data($u)`, `aegis_profile_stats($u)`, `aegis_profile_sections($u)`, `aegis_edit_profile_groups($u)`.
- `aegis_cs_public_url($u)` returns null unless `cs_account_type='business' AND cs_public=1`.

---

## Cross-page conventions

Every CS-portal page should start with the same lines:

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared/db.php';
require_once __DIR__ . '/../_shared/models.php';
require_once __DIR__ . '/../_shared/icons.php';

$current_user = aegis_current_user();
if (!$current_user || !aegis_user_has_role($current_user['id'], 'continuity_steward')) {
    header('Location: /onboarding/signin.html?return=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
$active_page = '<page-key>';

// Useful CS-wide derived state
$cs_account_type = $current_user['cs_account_type'] ?? 'invited';
$is_invited_cs   = ($cs_account_type === 'invited');
$is_business_cs  = ($cs_account_type === 'business');
$can_invite_more = $is_business_cs;   // Invited CSs are locked to one practitioner
```

Two tier-gating rules to remember on every page:

- **Invited CS** (`cs_account_type='invited'`): connected to exactly one practitioner. The CS portal pages that show "all my providers" should still work — they just show one. Inviting a second triggers an upgrade modal.
- **Business CS** (`cs_account_type='business'`): full multi-practitioner caseload, can opt into a public profile.

The sidebar role badge, hero copy, and any "upgrade" CTAs all need to read these flags.

---

## Page 1 — `index.php` (CS Dashboard)

### Bootstrap
Standard. `$active_page = 'dashboard';`

### Currently static
- "Welcome back, Marcus Chen" hero
- 4 metric tiles: Active Providers, Pending Verifications, Open Tasks, Incidents Resolved
- "My Active Incidents" panel (mock list)
- "Provider Caseload" mini-list
- "Pending Plan Countersignatures" panel
- Recent Activity feed
- "Capacity" widget (e.g. 15/20 slots)

### Wire to

| Display element | Source |
|---|---|
| Hero name + creds | `$current_user['display_name']`, `$current_user['credentials']` |
| Avatar initials | `$current_user['avatar_initials']` |
| Active Providers metric | `count(aegis_get_providers_for_steward($uid, 'continuity_steward'))` |
| Active Incidents metric | `count(array_filter(aegis_get_incidents_for_cs($uid), fn($i) => $i['status'] === 'active'))` |
| Pending Verifications metric | `count(array_filter(aegis_get_incidents_for_cs($uid), fn($i) => $i['status'] === 'pending_verification'))` |
| Open Tasks metric | sum of `count(aegis_get_incident_tasks($incident['id']))` for each active incident, filtered to those not completed |
| Active Incidents list | `aegis_get_incidents_for_cs($uid)` filtered to `status IN ('active','pending_verification')` — for each, render practitioner name (from joined user), incident type via `aegis_incident_label($type)`, reported_at via `aegis_time_ago()` |
| Provider Caseload mini | `aegis_get_providers_for_steward($uid, 'continuity_steward')` — first 5 — render name, organization, plan_status, annual_review_date, link to `/steward/<my-slug>` for share or `providers.php?id=<pid>` for detail |
| Pending Countersignatures | `aegis_get_providers_for_steward($uid, 'continuity_steward')` filtered to where the join row has `certification_at IS NULL` — these are plans this CS hasn't yet countersigned |
| Recent Activity feed | `aegis_get_activity($uid, 5)` |
| Unread badge | `aegis_get_unread_activity_count($uid)` |
| Capacity widget | `count(active_providers)` / `users.cs_capacity_max` (column doesn't exist — see Notes) |
| Public profile mini-card with "View public" button | `aegis_cs_public_url($current_user)` — show only when not null; otherwise show "Enable public profile in settings" link |
| Stripe Connected pill | `$current_user['stripe_connected']` |
| Maat-Certified pill | `$current_user['maat_addon']` (or `verified`, depending on what the badge means) |

### Notes / open questions
- **Capacity max not stored.** The public CS profile renders `15 of 20 slots filled` from a hardcoded value. Either add `users.cs_capacity_max` column, or compute "active count + 5" as a placeholder. Recommend adding the column with default 20.
- **Invited CS dashboard variant.** When `$is_invited_cs`, the metric tiles should hide "Active Providers" (always 1) and replace with "Plan Status" or "Annual Review Due". Render an "Upgrade to Business CS" CTA.
- **Recent Activity event types** for CS context: `incident_reported`, `incident_verified`, `incident_closed`, `attestation`, `vault_access`, `message`. Filter accordingly when populating the activity tab.

---

## Page 2 — `providers.php` (My Providers / Caseload)

### Currently static
Hardcoded list of provider cards with mock plan status, last review date, "View Plan" buttons.

### Wire to

```php
$providers = aegis_get_providers_for_steward($current_user['id'], 'continuity_steward');
```

| Display element | Source |
|---|---|
| Provider card name + creds | from joined `users` row |
| Provider avatar initials | `$p['avatar_initials']` |
| Plan status pill | `$p['plan_status']` |
| Annual review date | `$p['annual_review_date']` — show in green if >60 days out, orange if 30-60, red if ≤30 |
| Steward role pill | `$p['steward_role']` (`primary` / `alternate`) |
| Vault access level | `$p['vault_access_level']` (`full_read` / `metadata_only` / etc.) |
| Certification status | `$p['certification_at']` is null → "Pending Countersignature" pill; non-null → "Certified <date>" |
| "View Public Profile" button | `aegis_practitioner_public_url($p)` — null when practitioner has `practitioner_public=0`, hide button in that case |
| "View Plan" button | links to `plan-detail.php?plan_id=<id>` |
| "Message" button | links to `messages.php?to=<practitioner_id>` (or starts a thread if none exists) |
| Empty state | When `$providers` is empty: CTA to "Wait for a practitioner to designate you" or for Business CS, an "Invite a practitioner" flow |

### Provider detail (when clicking a card)
Either an inline modal or `providers.php?id=<pid>` rendering:

| Section | Source |
|---|---|
| Practitioner profile summary | `aegis_get_user($pid)` + selected fields |
| Their continuity plan | `aegis_get_plan_by_practitioner($pid)` |
| Active incident config (which 7 types are enabled) | `aegis_get_plan_incident_configs($plan_id)` |
| Tasks I'm responsible for | `aegis_get_plan_tasks($plan_id, null, 'continuity_steward')` |
| Vault status | `aegis_is_vault_unlocked_for_cs($uid, $pid)` — if locked, show "Unlocks during verified incident" |
| Other stewards on this plan | `aegis_get_plan_stewards($plan_id)` minus current_user |

### Notes
- For Invited CS, `$providers` will always be 0 or 1. Page should show a single big card instead of a grid.

---

## Page 3 — `tasks.php` (CS Task List)

### Currently static
Hardcoded list of "tasks I need to do" — countersign plan, verify incident, complete incident task, etc.

### Wire to
The task list is **derived from multiple sources** (it's a synthesized inbox, not a single table). The helper to add:

```php
function aegis_cs_open_tasks(string $cs_id): array {
    $tasks = [];

    // 1. Plans awaiting countersignature
    foreach (aegis_get_providers_for_steward($cs_id, 'continuity_steward') as $p) {
        if (empty($p['certification_at'])) {
            $tasks[] = [
                'type' => 'countersign_plan',
                'title' => "Countersign {$p['display_name']}'s Continuity Plan",
                'priority' => 'high',
                'due' => null,
                'href' => "plan-detail.php?plan_id={$p['plan_id']}",
            ];
        }
    }

    // 2. Incidents awaiting verification
    foreach (aegis_get_incidents_for_cs($cs_id) as $inc) {
        if ($inc['status'] === 'pending_verification') {
            $tasks[] = [
                'type' => 'verify_incident',
                'title' => "Verify " . aegis_incident_label($inc['incident_type']) . " incident",
                'priority' => 'critical',
                'due' => null,
                'href' => "incident-detail.php?id={$inc['id']}",
            ];
        }
    }

    // 3. Open incident tasks assigned to this CS
    foreach (aegis_get_incidents_for_cs($cs_id) as $inc) {
        if ($inc['status'] === 'active') {
            foreach (aegis_get_incident_tasks($inc['id']) as $t) {
                if ($t['assigned_to'] === 'continuity_steward' && empty($t['completed_at'])) {
                    $tasks[] = [
                        'type' => 'incident_task',
                        'title' => $t['task_description'] ?? $t['description'],
                        'priority' => 'high',
                        'due' => $t['due_at'] ?? null,
                        'href' => "incident-detail.php?id={$inc['id']}#task-{$t['id']}",
                    ];
                }
            }
        }
    }

    // 4. Annual reviews coming up
    foreach (aegis_get_providers_for_steward($cs_id, 'continuity_steward') as $p) {
        if ($p['annual_review_date']) {
            $days = (strtotime($p['annual_review_date']) - time()) / 86400;
            if ($days <= 30) {
                $tasks[] = [
                    'type' => 'annual_review',
                    'title' => "Annual review with {$p['display_name']}",
                    'priority' => $days <= 7 ? 'high' : 'medium',
                    'due' => $p['annual_review_date'],
                    'href' => "plan-detail.php?plan_id={$p['plan_id']}",
                ];
            }
        }
    }

    return $tasks;
}
```

### Display
| Element | Source |
|---|---|
| Task list | `aegis_cs_open_tasks($uid)` |
| Filter chips (All / Critical / High / Annual Reviews / etc.) | filter `$tasks` by `priority` or `type` |
| Task badges | `priority` field |
| Empty state | "No open tasks. Nice work." |

### Notes
- Order tasks: `verify_incident` first (critical), then anything overdue, then by `due` ascending, then anything else.

---

## Page 4 — `incidents.php` / `emergency.php` / Verify Cockpit

### Currently static
This is **the** critical-path page. Currently mostly hardcoded but the helper layer is mature.

### Wire to

```php
$active_incidents  = array_filter(aegis_get_incidents_for_cs($uid), fn($i) => $i['status'] === 'active');
$pending_verifications = array_filter(aegis_get_incidents_for_cs($uid), fn($i) => $i['status'] === 'pending_verification');
$past_incidents    = array_filter(aegis_get_incidents_for_cs($uid), fn($i) => in_array($i['status'], ['closed','resolved']));
```

### Tabs
1. **Pending Verification** — `$pending_verifications`. Each card shows: practitioner name, incident type, who reported (SS), reported_at, narrative, "Verify Now" button → opens **verify cockpit**.
2. **Active Incidents** — `$active_incidents`. Each shows: practitioner, incident type, verified_at, % of incident_tasks complete, "Open" button.
3. **Past Incidents** — `$past_incidents`. Read-only audit log.

### Verify Cockpit (modal or full page)
A multi-step flow per `aegis_verify_incident()`:

| Step | Source / Action |
|---|---|
| Practitioner & incident summary | `aegis_get_incident($id)` + `aegis_get_user($incident['practitioner_id'])` |
| Incident narrative | `$incident['report_narrative']` |
| Contact attempts log | `$incident['contact_attempts']` (JSON column) |
| Required documents | `aegis_incident_types()[$incident['incident_type']]['default_docs']` |
| Upload form for verification docs | POSTs to `aegis_verify_incident($id, $cs_id, $uploaded_docs, $notes)` |
| Once verified | Vault unlocks (`aegis_is_vault_unlocked_for_cs` returns true), incident_tasks are auto-created from `plan_tasks`, status flips to `'active'` |

### Active incident detail
| Element | Source |
|---|---|
| Task checklist | `aegis_get_incident_tasks($incident_id)` |
| Per-task complete button | `aegis_complete_incident_task($task_id, $note)` |
| Vault link | `vault.php?practitioner_id=<pid>` (only works while incident active) |
| "Close incident" button | `aegis_close_incident($id, $cs_id, $summary)` once all tasks done |

### Notes
- This is the highest-stakes page in the entire CS portal. Every state change should `aegis_log_activity()` so the audit trail is complete.

---

## Page 5 — `vault.php` (Document Vault, gated read-only)

### Currently static
Possibly hardcoded list of vault items.

### Wire to

```php
$pid = $_GET['practitioner_id'] ?? null;
if (!$pid || !aegis_is_vault_unlocked_for_cs($current_user['id'], $pid)) {
    // Show locked state
    $items = [];
    $locked = true;
} else {
    $locked = false;
    $items = [
        'standard'    => aegis_get_vault_items($pid, 'standard'),
        'credentials' => aegis_get_vault_items($pid, 'credentials'),
        'emergency'   => aegis_get_vault_items($pid, 'emergency'),
        'roster'      => aegis_get_vault_items($pid, 'roster'),
    ];
}
```

| Display element | Source |
|---|---|
| Practitioner name in header | `aegis_get_user($pid)['display_name']` |
| Lock state banner | `$locked` — "Vault locked. Will unlock when an incident is verified." |
| Tabs (4 zones) | `$items[$zone]` |
| Item card | title, file type, uploaded_at — and a "Download" button that hits the existing file route |
| Access level pill | from `plan_stewards.vault_access_level` for this CS-practitioner pairing |

### Notes
- CS portal has read-only access — no upload here. Uploads happen in the practitioner's portal.
- Roster items have stricter access (clinical names) — consider gating roster behind a higher `vault_access_level`.

---

## Page 6 — `agreements.php` (Important Documents)

### Currently static
Hardcoded list of agreement cards.

### Wire to

| Element | Source |
|---|---|
| Per-practitioner Continuity Plan agreements | for each `$p` in `aegis_get_providers_for_steward($uid, 'continuity_steward')`: render a card with practitioner name, plan_status, certification_at, signature info via `aegis_get_attestation_states($p['plan_id'])` |
| Steward Agreement (mine) | per-plan, the row in `plan_stewards` where `steward_id = $current_user['id']` — has `countersigned_at`, `certification_at`, `certification_note` |
| "Sign now" button | for plans where `certification_at IS NULL` → opens countersign flow, calls `aegis_steward_certify($plan_id, $cs_id, $note)` |
| Stripe Connect agreement | placeholder row when `stripe_connected=1` |
| Maat Certification | placeholder when `maat_addon=1` |

### Notes
- Each row = one practitioner relationship, not one global document.
- For Invited CS this list always has at most one entry.

---

## Page 7 — `messages.php`

Identical pattern to provider portal:

| Element | Source |
|---|---|
| Thread list | `aegis_get_threads_for_user($uid)` |
| Thread → other person info | join in helper or call `aegis_get_user($other_id)` |
| Messages | `aegis_get_messages($thread_id)` |
| Send | `aegis_send_message($thread_id, $uid, $body)` |
| Right pane partner card | links to `aegis_practitioner_public_url($other_user)` for practitioners, or no link if other party is SS |

### Notes
- Same Phone/Video Call removal as provider portal — keep only Send + Attach.

---

## Page 8 — `activity.php` (Activity Log)

```php
$events = aegis_get_activity($current_user['id'], 50, $filters);
```

Same pattern as provider portal. Filter chips: `incident_*`, `attestation`, `vault_access`, `message`, `plan_*`.

---

## Page 9 — `profile.php` (read-only)

```php
$profile = aegis_profile_data($current_user);  // works for any role
$stats   = aegis_profile_stats($current_user);
$sections = aegis_profile_sections($current_user);
```

| Element | Source |
|---|---|
| Hero name + creds | `$profile['identity']` |
| Maat-Certified pill | `$current_user['maat_addon']` |
| Stripe-Connected pill | `$current_user['stripe_connected']` |
| Aegis-Verified pill | `$current_user['verified']` |
| Capacity meter | (when added to schema) `count(active providers) / users.cs_capacity_max` |
| Stat strip | `$stats` |
| All sections | iterate `$sections` |
| "View Public Profile" button | `aegis_cs_public_url($current_user)` — render only when not null. When `$is_invited_cs`, hide entirely (Invited CSs are never public). |
| "Edit Profile" button | links to `edit-profile.php` |

### Notes
- The CS profile page should clearly indicate **public/private state**. If `cs_account_type='invited'`, render an info banner: "You are an Invited Continuity Steward. Your profile is not public. Upgrade to a Business CS account to make your profile discoverable."
- If `cs_account_type='business' AND cs_public=0`, render: "Your public profile is hidden. Toggle it on in Edit Profile to be discoverable at /steward/<your-slug>."

---

## Page 10 — `edit-profile.php`

```php
$groups = aegis_edit_profile_groups($current_user);
```

Same pattern as provider's edit-profile, with these CS-specific fields to add:

| Field | Column | UI |
|---|---|---|
| Show CS profile publicly | `cs_public` | Toggle (only render when `cs_account_type='business'`; hide entirely for Invited CSs) |
| CS account type | `cs_account_type` | Read-only with upgrade CTA when `'invited'` |
| Slug | `slug` | Read-only with edit-once warning |
| Stripe Connect | `stripe_connected` | Status pill + "Connect Stripe" button (out of scope to wire today) |
| Maat Add-on | `maat_addon` | Status pill |
| CS capacity (max practitioners) | `cs_capacity_max` (new column) | Number input, default 20 |

### POST handler
Whitelist updateable columns from `aegis_edit_profile_groups()` plus the CS-specific ones above. Run `UPDATE users SET … WHERE id = ?`. Toast on success. `aegis_log_activity()` on important changes (e.g., toggling `cs_public`).

### Notes
- Invited CS upgrade flow: a "Convert to Business CS" button → modal explaining pricing → on confirm, set `cs_account_type='business'` and trigger Stripe checkout (out of scope).

---

## Page 11 — `overview.php` (Start Here)

```php
$ov = aegis_overview_data($current_user);
```

Helper already returns CS-specific copy when called with a CS user. Iterate the four content arrays. Same pattern as provider portal.

---

## Page 12 — `settings.php`

Same shape as provider portal:

| Tab | Source |
|---|---|
| Profile summary | read-only from `aegis_profile_data()` |
| Account & Login | `email`, `last_login`, password change form |
| Notifications | new `user_preferences` table needed |
| Privacy / Visibility | links to edit-profile for `cs_public` toggle |
| Subscription / Billing | `cs_account_type`, Stripe details, upgrade CTA for Invited CS |
| Account actions | `aegis_deactivate_user($uid)` |

---

## Page 13 — `executors.php` (the "I'm Continuity Steward For" page)

This is the read-only flipside of providers.php for context — same data, different framing. If you already have it as a separate page, wire it the same way as `providers.php`. Otherwise, you can drop it and rely on `providers.php` alone.

---

## Page 14 — `plan-detail.php` (per-practitioner plan view)

When clicking a provider, this is the deep view.

```php
$plan = aegis_get_plan($_GET['plan_id']);
$practitioner = aegis_get_user($plan['practitioner_id']);
$incident_configs = aegis_get_plan_incident_configs($plan['id']);
$stewards = aegis_get_plan_stewards($plan['id']);
$my_tasks = aegis_get_plan_tasks($plan['id'], null, 'continuity_steward');
$attestations = aegis_get_attestation_states($plan['id']);
```

| Element | Source |
|---|---|
| Plan signed date | `$plan['signed_at']` |
| Plan version | `$plan['plan_version']` |
| Annual review date | `$plan['annual_review_date']` |
| Practitioner signature info | `$plan['signature_name']`, `$plan['signature_title']` |
| Activated incident types | filter `$incident_configs` to `enabled=1` |
| Co-stewards on this plan | `$stewards` minus current_user |
| My tasks per incident type | group `$my_tasks` by `incident_type` |
| Audit trail | `$attestations` |
| Countersign action | when `certification_at IS NULL` → `aegis_steward_certify($plan_id, $cs_id, $note)` |
| Trigger Activation Drill | (internal flow — not yet a helper) |

---

## Implementation order (recommended)

### Phase 1 — pure model-to-view wiring
1. `overview.php` — `aegis_overview_data()`.
2. `profile.php` — `aegis_profile_data()` + sections + stats.
3. `activity.php` — `aegis_get_activity()`.
4. `messages.php` — threads + messages.
5. `agreements.php` — derive from `aegis_get_providers_for_steward()` + `aegis_get_attestation_states()`.
6. `providers.php` — `aegis_get_providers_for_steward()`.
7. `index.php` (Dashboard) — combine.
8. Sidebar role badge + topbar avatar — read `$current_user`.

### Phase 2 — task/incident flows
9. `tasks.php` — write `aegis_cs_open_tasks()` synthesizer.
10. `incidents.php` and Verify Cockpit — `aegis_get_incidents_for_cs()` + verify flow.
11. `vault.php` — gated reads via `aegis_is_vault_unlocked_for_cs()`.
12. `plan-detail.php` — per-plan deep view.

### Phase 3 — model extensions
13. `edit-profile.php` — extend `aegis_edit_profile_groups()` with CS-specific fields, POST handler, add `users.cs_capacity_max` column.
14. `settings.php` — read-only summary first; preferences/sessions later.

### Phase 4 — defer
15. Stripe Connect flow.
16. Maat Add-on management UI.
17. Invited → Business upgrade flow with payment.

---

## Cross-cutting CS items

### Tier-gating rules to apply everywhere
- Hide `providers.php` "Invite a Practitioner" button for Invited CS.
- Hide `cs_public` toggle in edit-profile for Invited CS.
- Hide `aegis_cs_public_url()` references in profile/dashboard/sidebar for Invited CS (the helper already returns null for them, so this is mostly a "don't render the empty button" check).
- Show "Upgrade to Business CS" CTA on dashboard, profile, and any place a feature is gated.

### Capacity max
Add `cs_capacity_max INTEGER DEFAULT 20` to `users` schema. Update seed.json. Surface as an editable field.

### Vault gating
Always gate vault reads through `aegis_is_vault_unlocked_for_cs()`. Don't trust client-side hints. Roster zone is double-gated — only show clinical-name items if `vault_access_level >= 'full_read'`.

---

## Helper additions needed (CS-specific)

| Helper | Returns | Used by |
|---|---|---|
| `aegis_cs_open_tasks($cs_id)` | array of synthesized task items | tasks.php |
| `aegis_unread_count($thread_id, $uid)` | int | messages.php (shared with provider) |
| `aegis_search_practitioners_for_cs($query)` | users array | providers.php Invite flow (Business CS only) |
| `aegis_invite_practitioner_to_cs($cs_id, $practitioner_email, $message)` | invite token | invite flow |
| `aegis_deactivate_user($uid)` | void | settings.php |

---

## What this document doesn't cover

- Stripe webhook handling for `stripe_connected` toggling.
- Maat certification verification (the "Maat-Certified" badge implies an off-platform certification process).
- Audit-log export / compliance reporting.
- The "Annual Drill" simulation flow.
- Data-residency / multi-region routing.
