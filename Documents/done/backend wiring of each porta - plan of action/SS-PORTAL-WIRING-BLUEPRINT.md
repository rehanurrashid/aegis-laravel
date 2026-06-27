# Support Steward Portal — Dynamic-Wiring Blueprint

**Status:** as of merge of `aegis-backend-global.zip` + `aegis-public-pages.zip`.
**Scope:** the SS portal only (`/support-steward-portal/*.php`).
**Goal:** turn each page's hardcoded mock arrays into reads from `_shared/models.php` helpers.
**Counterpart docs:** `PROVIDER-PORTAL-WIRING-BLUEPRINT.md`, `CS-PORTAL-WIRING-BLUEPRINT.md`, `BP-PORTAL-WIRING-BLUEPRINT.md`.

---

## How the SS role differs (read this first)

The Support Steward's job is narrower than the Continuity Steward's:

- **No public profile.** SS has no `ss_public` column. SS profiles are never reachable at any `/steward/<slug>` URL. Designation happens via the practitioner's portal directly, not via a discoverable directory.
- **No vault access.** SS never reads vault items. Vault unlock during an incident is a CS-only privilege.
- **No countersignature.** SS does not countersign the Continuity Plan the way a CS does — but they DO certify their own task list (`plan_stewards.certification_at`) when they accept the role.
- **Triggers but does not verify.** SS *reports* a critical incident (`aegis_trigger_incident`); CS *verifies* it (`aegis_verify_incident`). After verification SS can complete tasks assigned to them and contact people on the call list.
- **No financial relationship.** SS roles are typically friend/family/colleague — no fees, no Stripe Connect, no invoices. The portal hides finance pages entirely.

This means the SS portal is **the smallest of the four**. Roughly half the pages compared to CS, and most of them are read-only or limited write.

---

## Foundation already in place (SS-relevant)

After the merge, you have:

- `users` rows where `role='support_steward'` (Linda, James in seed).
- `plan_stewards` rows where `steward_type='support_steward'`. Columns: `role` (primary/alternate), `vault_access_level` (typically `metadata_only` or null for SS), `countersigned_at`, `certification_at`, `certification_note`, `status`.
- `plan_tasks(plan_id, incident_type, assigned_to, ...)` — `assigned_to='support_steward'` rows are this SS's responsibilities. Multiple tasks per incident type (death, long_term_incapacitation, etc.).
- `critical_incidents` — the `reported_by_ss_id` column anchors SS-triggered incidents.
- `incident_tasks` — when an incident becomes active (after CS verification), one row per `plan_tasks` row gets created so each can be checked off individually.
- `message_threads`, `messages`, `activity_events`.

SS-relevant helpers already in `models.php`:

- `aegis_get_providers_for_steward($steward_id, 'support_steward')` — returns practitioners this SS is on.
- `aegis_get_incidents_for_ss($ss_id)` — incidents this SS has reported.
- `aegis_get_active_incident_for_practitioner($pid)` — for the SS to know "is there an active incident I should be helping with?"
- `aegis_get_incident($incident_id)`, `aegis_get_incident_tasks($incident_id)`.
- `aegis_trigger_incident($plan_id, $ss_id, $incident_type, $narrative, $contact_attempts)` — SS opens an incident.
- `aegis_complete_incident_task($task_id, $note)` — once incident is verified.
- `aegis_steward_certify($plan_id, $steward_id, $note)` — works for SS too (they certify their own task list).
- `aegis_get_plan_stewards($plan_id)`, `aegis_get_plan_tasks($plan_id, $incident_type, 'support_steward')`.
- `aegis_get_threads_for_user`, `aegis_get_messages`, `aegis_send_message`.
- `aegis_get_activity`, `aegis_log_activity`.
- `aegis_profile_data`, `aegis_profile_stats`, `aegis_profile_sections`, `aegis_edit_profile_groups`.

---

## Cross-page conventions

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared/db.php';
require_once __DIR__ . '/../_shared/models.php';
require_once __DIR__ . '/../_shared/icons.php';

$current_user = aegis_current_user();
if (!$current_user || !aegis_user_has_role($current_user['id'], 'support_steward')) {
    header('Location: /onboarding/signin.html?return=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
$active_page = '<page-key>';

// SS-specific: figure out if there's an emergency in progress across any of my providers
$has_emergency = false;
foreach (aegis_get_providers_for_steward($current_user['id'], 'support_steward') as $p) {
    if (aegis_get_active_incident_for_practitioner($p['id'])) {
        $has_emergency = true;
        break;
    }
}
```

The sidebar's red emergency badge (`'!'`) reads from `$has_emergency`. Set this in every page bootstrap so the sidebar reflects current state.

---

## Page 1 — `index.php` (SS Dashboard)

### Bootstrap
Standard. `$active_page = 'dashboard';`

### Currently static
- Welcome banner with name
- Big red "Trigger Critical Incident" CTA at the top (or banner if active emergency exists)
- "My Providers" mini-list
- "Open Tasks" panel
- Recent Activity

### Wire to

| Display element | Source |
|---|---|
| Hero name + creds | `$current_user['display_name']`, `$current_user['credentials']` |
| Avatar initials | `$current_user['avatar_initials']` |
| Active Emergency banner (red, top of page) | `$has_emergency` boolean from bootstrap. When true: render details of the most recent active incident with "Open" button → `incident-detail.php` |
| "Trigger Critical Incident" CTA | always visible — opens trigger flow (modal or `emergency.php`) |
| My Providers count | `count(aegis_get_providers_for_steward($uid, 'support_steward'))` |
| Open Tasks count | sum across active incidents of unfinished `incident_tasks` where `assigned_to='support_steward'` |
| Pending Certifications metric | `count(plan_stewards rows where steward_id=$uid AND certification_at IS NULL)` |
| Provider mini-list | `aegis_get_providers_for_steward($uid, 'support_steward')` — name, plan_status, quick "Trigger" button per provider |
| Recent Activity | `aegis_get_activity($uid, 5)` |
| "Aegis-Verified" pill | `$current_user['verified']` |

### Notes
- The whole tone of this dashboard is "ready when needed." Should feel calmer than the CS dashboard, with one obvious red action when needed.
- For SSes serving only one practitioner (typical case), the Provider mini-list is just one card. Render it with extra context — "your designated practitioner."

---

## Page 2 — `providers.php` (My Providers)

### Currently static
Hardcoded provider cards.

### Wire to

```php
$providers = aegis_get_providers_for_steward($current_user['id'], 'support_steward');
```

| Display element | Source |
|---|---|
| Card name + creds | from joined `users` row |
| Avatar | `$p['avatar_initials']` |
| Plan status pill | `$p['plan_status']` |
| Steward role pill | `$p['steward_role']` (primary / alternate) |
| Certification status | `$p['certification_at']` null = "Awaiting your certification" |
| Active incident indicator | `aegis_get_active_incident_for_practitioner($p['id'])` — render red dot if not null |
| "Trigger Incident" button per provider | opens trigger flow scoped to this practitioner |
| "View Plan Tasks" button | `tasks.php?plan_id=<id>` |
| "Message Practitioner" button | `messages.php?to=<pid>` |
| "Message my CS counterpart" button | resolve other steward via `aegis_get_plan_stewards($plan_id)`, find the primary `continuity_steward`, link to messages |

### Notes
- An SS should see who the CS is for each plan they're on — useful context for emergencies.

---

## Page 3 — `tasks.php` (Plan Tasks I'm Responsible For)

### Currently static
Hardcoded task list.

### Wire to

This page shows the SS what they'd need to do **if** an incident occurred — preparation reading, not active execution.

```php
$rows = [];
foreach (aegis_get_providers_for_steward($current_user['id'], 'support_steward') as $p) {
    $tasks = aegis_get_plan_tasks($p['plan_id'], null, 'support_steward');
    foreach ($tasks as $t) {
        $rows[] = $t + [
            'practitioner_name' => $p['display_name'],
            'practitioner_id'   => $p['id'],
        ];
    }
}
```

### Display
| Element | Source |
|---|---|
| Group by practitioner | `practitioner_name` |
| Within group, group by incident type | `incident_type`, label via `aegis_incident_label()` |
| Task description | `$t['task_description']` or `$t['description']` |
| "Mark Reviewed" toggle | per-task acknowledgment, writes to `plan_stewards.certification_at` for the whole task list (not per-task — confirm in seed structure) |
| Filter by incident type | dropdown |
| Filter by practitioner | dropdown (only useful when SS has multiple providers) |

### Notes
- The SS has zero ability to *modify* these tasks. The practitioner sets them in `edit-profile`/plan-builder.
- "Certification" works at the *plan-stewards-row* level (one cert per (plan, steward) pair). Once SS certifies, all tasks across all incident types are considered acknowledged.

---

## Page 4 — `emergency.php` (Trigger / Active Incident Detail)

This is the single most important SS page. Two states:

### State A — No active incident: Trigger flow

```php
$active_incident = null;
foreach (aegis_get_providers_for_steward($current_user['id'], 'support_steward') as $p) {
    $i = aegis_get_active_incident_for_practitioner($p['id']);
    if ($i) { $active_incident = $i; break; }
}
```

When `$active_incident` is null:

| Element | Source / Action |
|---|---|
| "Select Practitioner" | dropdown of `aegis_get_providers_for_steward($uid, 'support_steward')` |
| "Select Incident Type" | dropdown of `aegis_incident_types()` — show label, mark optin types differently |
| "Narrative" textarea | free text — what happened |
| "Contact attempts" log | repeatable input rows: who you tried to reach, when, outcome |
| "Trigger" button (red, scary, with confirm modal) | `aegis_trigger_incident($plan_id, $ss_id, $type, $narrative, $contact_attempts)` |

After trigger succeeds: incident enters `pending_verification` status. Page redirects to State B for that incident.

### State B — Active or pending incident: detail view

```php
$incident = aegis_get_incident($_GET['id']);
$tasks    = aegis_get_incident_tasks($incident['id']);
$practitioner = aegis_get_user($incident['practitioner_id']);
```

| Element | Source |
|---|---|
| Big red status banner | `$incident['status']` — "Pending CS Verification" / "Active" / "Closed" |
| Practitioner name + photo | `$practitioner` |
| Incident type | `aegis_incident_label($incident['incident_type'])` |
| Reported at | `aegis_time_ago($incident['reported_at'])` |
| Narrative | `$incident['report_narrative']` |
| Contact attempts | `$incident['contact_attempts']` (JSON, render as a list) |
| CS verification status | `$incident['verified_at']` null vs. set; if set, show `verified_by_cs_id` name |
| Verification docs (read-only) | `$incident['verification_docs']` |
| My tasks (filtered to support_steward) | `array_filter($tasks, fn($t) => $t['assigned_to'] === 'support_steward')` |
| Per-task complete button | `aegis_complete_incident_task($task_id, $note)` (only enabled while incident is active) |
| Other stewards' progress | render co-stewards from `aegis_get_plan_stewards($incident['plan_id'])` with their task completion % |

### Notes
- The trigger button needs strong confirmation UX — ideally type-to-confirm. This is a high-stakes action and false triggers have real consequences.
- After trigger, all parties (practitioner, CS, other SS) get an `activity_event` and a notification. Make sure `aegis_log_activity()` runs on every state change.

---

## Page 5 — `executors.php` (Continuity Stewards on My Plans)

### Currently static
Hardcoded list of CSes I work with.

### Wire to

```php
$counterparts = [];
foreach (aegis_get_providers_for_steward($current_user['id'], 'support_steward') as $p) {
    foreach (aegis_get_plan_stewards($p['plan_id']) as $s) {
        if ($s['steward_type'] === 'continuity_steward') {
            $counterparts[] = $s + [
                'practitioner_name' => $p['display_name'],
                'practitioner_id'   => $p['id'],
            ];
        }
    }
}
```

| Element | Source |
|---|---|
| CS card | name, organization, role (primary/alternate) |
| Practitioner this CS represents | `practitioner_name` |
| "View CS Public Profile" button | `aegis_cs_public_url($cs_user)` — null if Invited CS or `cs_public=0` |
| "Message" button | `messages.php?to=<cs_id>` |
| Phone / Email contact | only show when populated; respect privacy |

### Notes
- This is read-only. SSes don't designate or remove CSes — that's the practitioner's role.

---

## Page 6 — `agreements.php` (Important Documents — read-only)

### Currently static
Hardcoded agreement cards.

### Wire to

| Element | Source |
|---|---|
| Per-practitioner Plan summary | for each `aegis_get_providers_for_steward($uid, 'support_steward')` row, render a card with `plan_status`, `signed_at` from `aegis_get_plan($p['plan_id'])` |
| My Steward Agreement (per plan) | the `plan_stewards` row where `steward_id=$uid` — render `countersigned_at`, `certification_at`, `certification_note` |
| "Certify my task list" button | for plans where `certification_at IS NULL` → modal to acknowledge, calls `aegis_steward_certify($plan_id, $ss_id, $note)` |
| Audit log per plan | `aegis_get_attestation_states($plan_id)` |

### Notes
- SS can view but not modify the practitioner's plan. They can only certify their own acceptance.

---

## Page 7 — `messages.php`

Same shape as provider/CS portals.

| Element | Source |
|---|---|
| Thread list | `aegis_get_threads_for_user($uid)` |
| Messages | `aegis_get_messages($thread_id)` |
| Send | `aegis_send_message($thread_id, $uid, $body)` |
| Right pane partner card | when other party is a practitioner: link to `aegis_practitioner_public_url()`. When CS: link to `aegis_cs_public_url()` if available. When another SS: no public link. |

### Notes
- During an active incident, surface a banner at top: "Active incident with Dr. Sarah Johnson — open detail" linking to `emergency.php?id=<id>`.

---

## Page 8 — `activity.php` (Activity Log)

```php
$events = aegis_get_activity($current_user['id'], 50, $filters);
```

Same pattern. SS-relevant filter chips: `incident_reported`, `incident_verified`, `incident_closed`, `task_completed`, `attestation`, `message`.

---

## Page 9 — `profile.php` (read-only)

```php
$profile  = aegis_profile_data($current_user);
$stats    = aegis_profile_stats($current_user);
$sections = aegis_profile_sections($current_user);
```

| Element | Source |
|---|---|
| Hero name + creds | `$profile['identity']` |
| "Aegis Verified" pill | `$current_user['verified']` |
| Stat strip | `$stats` (will return SS-flavored stats — providers count, plan count, etc.) |
| All sections | iterate `$sections` |
| **No "View Public Profile" button** | SSes have no public profile, full stop. |
| "Edit Profile" button | links to `edit-profile.php` |

### Notes
- Make sure the page does NOT render an `aegis_cs_public_url()` or `aegis_practitioner_public_url()` button. SSes never have one.
- Add a banner: "Your Support Steward profile is private. Practitioners designate you directly — there is no public discovery for SSes."

---

## Page 10 — `edit-profile.php`

```php
$groups = aegis_edit_profile_groups($current_user);
```

Same pattern as provider's edit-profile, but **no public-visibility toggles** to add. SS-specific fields:

| Field | Column | UI |
|---|---|---|
| Display name, email, phone, bio, location | (existing user columns) | standard |
| Notification preferences for emergencies | new `user_preferences` rows | toggles |
| Backup contact (next of kin / alternate) | new `users.backup_contact_*` columns or separate table | (out of scope?) |

### POST handler
Whitelist updateable columns from `aegis_edit_profile_groups()`. Run `UPDATE users SET … WHERE id = ?`.

### Notes
- An important UX choice: should an SS be able to *resign* from a plan via this page? Probably yes — add a "Resign from <Plan Name>" action that flips `plan_stewards.status='resigned'` and notifies the practitioner.

---

## Page 11 — `overview.php` (Start Here)

```php
$ov = aegis_overview_data($current_user);
```

Helper returns SS-flavored copy. Iterate the four content arrays.

### Notes
- The content focus should be: what an SS does, when they're called on, what NOT to do (don't verify yourself; that's the CS's job).

---

## Page 12 — `settings.php`

Same shape as the others, with these specific fields:

| Tab | Source |
|---|---|
| Profile summary | read-only from `aegis_profile_data()` |
| Account & Login | email, last_login, password change |
| Notifications | new `user_preferences` table — emergency alert preferences are CRITICAL for SS, surface them prominently |
| Privacy | (limited — no public profile to toggle) |
| My Designations | list of `(practitioner, role, status)` from `plan_stewards` for current SS — with "Resign" buttons |
| Account actions | `aegis_deactivate_user($uid)` |

### Notes
- Emergency notification preferences should default to "all channels enabled" and require explicit confirmation if SS tries to disable any. SSes need to be reachable.

---

## Implementation order (recommended)

### Phase 1 — pure model-to-view wiring
1. `overview.php` — `aegis_overview_data()`.
2. `profile.php` — `aegis_profile_data()` + sections + stats.
3. `activity.php` — `aegis_get_activity()`.
4. `messages.php` — threads + messages.
5. `agreements.php` — `aegis_get_providers_for_steward()` + `aegis_get_attestation_states()`.
6. `providers.php` — `aegis_get_providers_for_steward()` + active-incident probe.
7. `tasks.php` — synthesized from `aegis_get_plan_tasks()` per provider.
8. `executors.php` — derived from `aegis_get_plan_stewards()` for each plan.
9. `index.php` — combine.
10. Sidebar — `$current_user` and `$has_emergency`.

### Phase 2 — emergency lifecycle
11. `emergency.php` State A (Trigger flow) — `aegis_trigger_incident()`.
12. `emergency.php` State B (Active incident detail) — `aegis_get_incident()` + `aegis_complete_incident_task()`.

### Phase 3 — model extensions
13. `edit-profile.php` — extend `aegis_edit_profile_groups()`, add Resign-from-plan flow, POST handler.
14. `settings.php` — read-only first; preferences/sessions later.

### Phase 4 — defer
15. Notification routing (push/SMS for emergencies — out of scope, but the data model should be ready).
16. Backup/next-of-kin contact field.

---

## Cross-cutting SS rules

- **Never render `aegis_cs_public_url()` or `aegis_practitioner_public_url()` buttons inside the SS portal except when displaying *other* people** (CS counterparts, practitioners they steward). The current SS user themselves never has a public link.
- **Never render upload UI** for vault — SSes don't have access.
- **Never render countersign UI for the *plan*** — SSes only certify their own task list, not the plan.
- **Always surface the global emergency state** in the topbar/sidebar so SSes can react fast.
- **Trigger button must require strong confirmation** — modal with type-to-confirm, irreversible warning.

---

## Helper additions needed (SS-specific)

| Helper | Returns | Used by |
|---|---|---|
| `aegis_unread_count($thread_id, $uid)` | int | messages.php (shared) |
| `aegis_resign_from_plan($plan_id, $steward_id, $reason)` | void | edit-profile / settings — flips `plan_stewards.status='resigned'`, logs activity, notifies practitioner |
| `aegis_deactivate_user($uid)` | void | settings.php (shared) |
| `aegis_user_emergency_preferences($uid)` | array | settings.php notifications tab |
| `aegis_set_user_preference($uid, $key, $value)` | void | settings.php save |

---

## What this document doesn't cover

- Push-notification routing for emergency alerts.
- SMS / phone-call escalation for SSes who don't respond to in-app notifications.
- Two-factor confirmation when triggering an incident (consider for high-risk types).
- Cooldown / rate limiting on incident triggers (preventing accidental floods).
- Multi-language SS workflows (Spanish, etc.).
