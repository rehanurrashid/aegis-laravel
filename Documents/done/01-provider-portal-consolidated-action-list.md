# Aegis — Provider Portal: Consolidated Action List (Single Source of Truth)

**Purpose:** Reconcile the two planning docs into one list of what is *done* and what is *outstanding* on the Provider portal, with the goal that when CS / SS / Business portals are built, the Provider portal can communicate with them through the centralized SQLite backbone.

**Inputs reconciled:**
- `technical-changes.md` — written against the **old** portal (escrow present, 81 "Executor" hits, 226 "DSR" hits, no Builder, no dynamic data). Most of its work is now done.
- `aegis-provider-portal-communication-audit.md` — written against the **current** dynamic portal. Authoritative on what remains.

**How the two relate:** `technical-changes.md` describes the journey; the audit describes the destination reached so far. Where they conflict, the audit wins because it was run against the live code.

---

## 1. Reconciliation Summary

| Theme | technical-changes.md said | Current reality (audit) | Net status |
|---|---|---|---|
| File renames (`executors`→`continuity-stewards`, `dsr`→`support-stewards`, etc.) | Do first | Already done in build | ✅ Done |
| Terminology sweep (Professional Will, Executor, DSR, Escrow, KALINK, Patients) | Heaviest work (hundreds of hits) | Display strings swept; **internal modal IDs / CSS classes still say `addExecutorStep*`, `addDsr*`, `.dsr-*`** | ✅ Display done · ⚪ cosmetic IDs remain |
| Dynamic reads (replace mock arrays with model calls) | Many WIRE rows | Every critical page reads from shared helpers | ✅ Done |
| `continuity-plan.php` Builder exists | NEW FILE 1 | Exists, reads dynamically | ✅ Built (read) · ❌ does not persist |
| **Write / origination path** (Save, Sign, Designate, Authorize, Send) | Several WIRE-write rows + new model fns | **Almost entirely `showToast()` only** | ❌ **Outstanding — the core gap** |
| Model helper `aegis_get_invoices_for_practitioner` (M.5) | "new helper needed" | **Already exists** | ✅ Done |
| Schema changes S.1 / S.2 | Add `vault_items.required_for_incidents`, `users.payment_model` | Core comms need **no** schema change (audit) | 🔁 Reconciled — see §4 |

**One-line verdict:** the Provider portal *reads* the shared backbone correctly and is fully seeded, so other portals will see its data. It cannot yet *write* to the backbone, so it cannot originate the cross-portal flows. Closing the write path (below) is the entire remaining job for communication.

---

## 2. Per-File Action List (consolidated)

Legend: ✅ done · 🔌 wire-only (helper exists) · 🔧 build helper + wire · 🌱 seed/data (see Doc 2) · ⚪ optional cleanup

### `continuity-plan.php` — Builder (highest priority; the demo centerpiece)
- ✅ Reads plan, configs, stewards, tasks dynamically.
- 🔧 `saveDraft()` and **Finalize & Sign** are client-only. Build the write layer: `aegis_create_plan`/`aegis_save_plan_draft`, `aegis_save_plan_incident_config` (= old M.1; enable + docs_required + authorized SS/CS), `aegis_save_plan_task`/update/delete (= old M.2), `aegis_copy_tasks_between_stewards` (= old M.3).
- 🔌 Finalize → existing `aegis_practitioner_sign_plan`; then **fan-out** `aegis_log_activity` to practitioner + each assigned SS + each assigned CS (old rows 10.7 / NEW-FILE-1 fan-out).
- Endpoint: add `/_shared/save_plan.php` following the `save_pref.php` pattern.
- **Effect when done:** SS/CS task lists and dashboard certification chips become live, not seeded.

### `continuity-stewards.php`
- ✅ Reads CS designations.
- 🔧 Add-CS wizard, role change (primary/alternate/secondary), copy-tasks, countersignature resend persist nothing. Build `aegis_add_plan_steward` (no INSERT helper exists — only a certification UPDATE), `aegis_update_plan_steward_role`, `aegis_set_incident_authorization` (writes `authorized_cs_ids`), `aegis_copy_tasks_between_stewards`.
- 🔌 New-CS / resend → `aegis_log_activity`. Certify path already writes via `aegis_steward_certify`.
- ⚪ Rename internal IDs `addExecutorStep*` → `addCsStep*` (display already correct).
- 🌱 Several named CS in this file are unseeded (Thomas Chen, Laura Reyes, Amelia Rodriguez, Aisha Okonkwo) → 404. See Doc 2.

### `support-stewards.php`
- ✅ Reads SS designations.
- 🔧 Add-SS wizard, primary/alternate, per-incident **trigger** authorization, copy-tasks, suspend/reinstate/archive persist nothing. Build `aegis_add_plan_steward` (SS), role designation, `aegis_set_incident_authorization` (`authorized_ss_ids` — gates SS triggering in the SS portal), `aegis_copy_tasks_between_stewards`.
- 🔌 suspend/reinstate → `plan_stewards.status` update + `aegis_log_activity`.
- ⚪ Rename internal IDs `addDsr*`, classes `.dsr-*`.
- 🌱 Rachel Pham, Jordan Taylor, Brian Santos unseeded → 404. See Doc 2.

### `vault.php`
- ✅ Reads `vault_items` by zone.
- 🔌 Upload → existing `aegis_add_vault_item`.
- 🔧 Delete/update item helpers; **docs-required-per-incident** toggle. **Reconciliation:** store this on the existing `plan_incident_configs.docs_required` column (no schema change) rather than old S.1's new `vault_items.required_for_incidents` column. Drop old M.4/S.1 in favor of reading `aegis_get_plan_incident_configs`.

### `important-documents.php`
- ✅ Reads plan + stewards.
- 🔌 Signature/Finalize → `aegis_practitioner_sign_plan` (shared with Builder) + push signed-doc reference to the read-side view that SS/CS see.
- ⚪ Aegis Document Library is presentational — fine for demo.

### `messages.php`
- ✅ Reads threads/messages/contacts/files/links/media.
- 🔌 Compose/send/new-thread → existing `aegis_send_message` (+ create thread when absent) + activity event. Until then messages don't mirror to SS/CS.

### `activity.php`
- ✅ Reads unified feed + unread count.
- 🔌 Mark-read / mark-all-read → existing `aegis_mark_activity_read` / `aegis_mark_all_activity_read`. CSV export is a real download; PDF can stub.
- Verify (outside this export): header bell popup in `_shared/header.php` reads `aegis_get_activity` + `aegis_get_unread_activity_count` and persists mark-all-read.

### `finances.php`
- ✅ Reads invoices/contracts/payment methods/subscription. **`aegis_get_invoices_for_practitioner` already exists (old M.5 = done).**
- 🔧 Add/remove/set-default payment method (`practitioner_payment_methods` has no write helper): `aegis_add_payment_method` / `aegis_remove_payment_method` / `aegis_set_default_payment_method`.
- 🔧 4-option payment model persistence (old M.7 + S.2 `users.payment_model`). This is the **one schema add still justified** — see §4.
- 🔌 Invoice approval → flip `bp_invoices.status` so BP portal sees it. HOLD on financial copy still applies (structure only).

### `dashboard.php`
- ✅ Status chips read attestation states; network/referral stats dynamic.
- 🔌 "Activate Continuity Plan" / annual-review attest → plan update + `aegis_log_activity`; notif mark-all-read → `aegis_mark_all_activity_read`.
- 🌱 No `critical_incidents` seeded → active-incident banner never lights. Seed one (Doc 2).

### `edit-profile.php` — ✅ persists via `/_shared/save_profile.php`. 🔌 Add fan-out: on save, `aegis_log_activity` to each assigned SS/CS (old 4.2/4.3) so their feeds show the contact-info change.
### `settings.php` — ✅ persists via `/_shared/save_pref.php`. ⚪ optional: convert remaining editable identity fields to read-only Profile Summary (old 17.2) — display polish, not communication.
### `news.php` — ✅ fully persists. No change.
### `overview.php` · `job-postings.php` · `referrals.php` · `network.php` · `events.php` — ✅ read-dynamic, peripheral. 🌱 `referrals.php` and one finance link reference unseeded people (robert-miller, james-wilson) → 404. See Doc 2.

---

## 3. Model-Layer Helpers — Reconciled List (add to `models.php`)

| Helper | Old ref | Status | Notes |
|---|---|---|---|
| `aegis_create_plan` / `aegis_save_plan_draft` | — | ❌ build | INSERT draft `continuity_plans` |
| `aegis_save_plan_incident_config` | M.1 | ❌ build | upsert `plan_incident_configs` (enable, docs_required, authorized SS/CS) |
| `aegis_save_plan_task` / update / delete | M.2 | ❌ build | `plan_tasks` CRUD |
| `aegis_copy_tasks_between_stewards` | M.3 | ❌ build | primary→alternate duplication |
| `aegis_add_plan_steward` / `aegis_update_plan_steward_role` / `aegis_set_steward_vault_access` | (implied 8.x/9.x) | ❌ build | **`plan_stewards` has no INSERT helper today** |
| `aegis_set_incident_authorization` | 8.4 / 9.4 | ❌ build | writes `authorized_ss_ids` / `authorized_cs_ids` |
| `aegis_add_payment_method` / remove / set-default | — | ❌ build | `practitioner_payment_methods` CRUD |
| `aegis_practitioner_set_payment_model` | M.7 | ❌ build | needs `users.payment_model` (S.2) |
| `aegis_get_required_docs_for_incident_type` | M.4 | 🔁 drop | superseded by `plan_incident_configs.docs_required` read |
| `aegis_get_attestation_history` | M.6 | ⚪ optional | Tier-2 timeline; current single-state read is enough for demo |
| `aegis_get_invoices_for_practitioner` | M.5 | ✅ exists | already in `models.php` |
| `aegis_practitioner_sign_plan` / `aegis_steward_certify` / `aegis_add_vault_item` / `aegis_send_message` / `aegis_mark_activity_read` / `aegis_mark_all_activity_read` / `aegis_log_activity` / `aegis_trigger_incident` / `aegis_verify_incident` / `aegis_close_incident` | various | ✅ exist | **wire-only** — pages just need to call them |

---

## 4. Schema Changes — Reconciled

| Item | Old plan | Decision |
|---|---|---|
| `vault_items.required_for_incidents` (S.1) | add column | **Skip.** Use existing `plan_incident_configs.docs_required`. No migration. |
| `users.payment_model` (S.2) | add column | **Keep.** Needed only for the finances 4-option model. Idempotent `ALTER TABLE` in `aegis_init_schema` + try/catch in migrations. |
| `practitioner_finance_settings` table (S.3) | optional | Skip unless finances grows past one field. |
| **`plan_stewards` status / role CHECK widening** | *(not in either doc)* | **NEW — required.** Current CHECK = `status IN ('active','pending','suspended','released')`, `role IN ('primary','alternate','secondary')`. The Provider UI shows **declined** and **incoming-request** stewards, and the roster needs **archived**; CS/SS portals will need **tertiary**. SQLite cannot ALTER a CHECK → the table must be recreated (DB reset). Full detail in Doc 2 §6. |

**Net:** the cross-portal communication path needs **no schema change**. Only the finances 4-option (S.2) and the steward-state widening (Doc 2 §6) touch schema, and only the latter requires a reset.

---

## 5. Cross-Portal Contract (what each future portal will read from Provider writes)

Once the write path lands, this is the data contract the CS/SS/BP portals consume:

- **CS portal** reads: `plan_stewards` (type=continuity_steward) for designations/roles/countersignature; `plan_tasks` (assigned_to=continuity_steward) + `plan_incident_configs.authorized_cs_ids` for its My-Tasks; `critical_incidents` for Continuity Management; `vault_items` (gated) ; `activity_events` (user_id=cs).
- **SS portal** reads: `plan_stewards` (type=support_steward); `plan_tasks` (assigned_to=support_steward) + `authorized_ss_ids` (gates which incidents it may trigger); writes `critical_incidents` on trigger (Provider dashboard then reads it back).
- **BP portal** reads: `bp_jobs` / `bp_proposals` / `bp_contracts` / `bp_milestones` / `bp_invoices`; Provider `finances.php` invoice approval writes `bp_invoices.status` back.
- **All portals** read `message_threads`/`messages` and `activity_events` — so Provider `messages.php` send + every Provider action's `aegis_log_activity` are what make the other portals "light up."

---

## 6. Build Order (Provider-only, to unblock CS/SS/BP)

1. Schema: `users.payment_model` (S.2) + **recreate `plan_stewards` with widened CHECK** (Doc 2 §6) → reset DB.
2. Model write helpers (§3 "build" rows).
3. `continuity-plan.php` persistence + Finalize fan-out. *(unblocks SS/CS task lists + dashboard chips)*
4. `continuity-stewards.php` + `support-stewards.php` designation & authorization writes.
5. Wire-only quick wins: `vault.php` upload, `messages.php` send, `activity.php` mark-read, `dashboard.php` attest, `edit-profile.php` fan-out.
6. `finances.php` payment-method + 4-option + invoice-status flip.
7. Seed the data gaps in Doc 2 (people, steward states, one active + one closed incident).

After steps 2–4, "how do I know the portals are talking?" becomes a live demonstration.
