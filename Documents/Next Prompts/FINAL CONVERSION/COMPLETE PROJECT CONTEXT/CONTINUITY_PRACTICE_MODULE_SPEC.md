# Aegis — Continuity Practice Module: Complete Workflow & Plan of Action

**Scope:** The 5 remaining Provider Continuity pages — Continuity Plan, Continuity Stewards, Support Stewards, Important Documents, Vault.
**Anchor:** `CONTINUITY_GROUP_CONVERSION_PLAN.md` Rev 4 (repo `main @ 1405245`)
**Status:** All Chapman-side decisions closed. All backend services present. Ready to execute.
**Author:** Devlet LLC · Ready: 2026-07-12

---

## 0. Executive summary — decisions locked

Every architectural decision that could ripple across pages is resolved. There are **no blockers**. Two billing-adjacent items (cancel + re-subscribe founder status, onboarding/pricing presentation) are still open with Chapman, but neither touches this module.

### Chapman-confirmed inputs (from 2026-07-09 email)

| Decision | Value | Source |
|---|---|---|
| Signature mechanics on Continuity Plan | **Option B — single confirmation at end** | Chapman email 2026-07-09 |
| Access tier max Support Stewards | **2** (Chapman's revised packages doc) | `.env` `TIER_ACCESS_MAX_SS=2` |
| Access tier max Continuity Stewards | **2** | `.env` `TIER_ACCESS_MAX_CS=2` |
| Practice tier max CS / max SS | **2 / 4** | `.env` |
| Founding member perks | Applied in `config/aegis.php` (5,000 / 100 / 100) | Chapman email 2026-07-09 |
| W-9 gating | Soft warning only | Attorney-confirmed |
| Marketing ads | News page, MAAT-driven | Out of scope for this module |
| Vault zones | **standard / emergency / credentials / roster** | Rev 4 §0.6 + legacy prototype |
| Incident types | 3 default-on, 4 opt-in | `App\Enums\IncidentType` |

### Devlet-side calls (locked)

| Decision | Value | Rationale |
|---|---|---|
| Build order | **Plan → CS → SS → Documents → Vault** | Rev 4 §STEP 3 (workflow-first, HUB anchors sub-pages) |
| Incident-type configuration UI | **Accordion inside `ContinuityPlan.vue`** | Legacy pattern, no orphan sub-page |
| Plan tasks UI | **Inside each incident-type accordion** | Tasks are per-incident-type; keeps context |
| Steward authorization matrix UI | **Incident-first** — inside the accordion; read-only chips on steward cards | Aligns with incident configs; steward cards stay compact |
| CS countersignature | **Provider-side "pending CS signature" state only** — CS-portal countersign UI is separate work | Keeps this module scoped to Provider portal |
| Plan snapshot PDF generation | **Defer** — status chip only, no PDF service yet | Requires new PDF service; not blocking demo |
| Vault attestation | **One-shot** (single `vault_attested_at` timestamp) + optional note | Current schema is fine; per-zone attestation is over-engineering |

---

## 1. The canonical `planSections` list

These 8 sections drive the HUB's checklist, the "N/8 complete" chip, and the "can this plan be signed?" gate.

| # | Section key | Title | Completion rule | Blocks signing? |
|---|---|---|---|---|
| 1 | `practice_info` | Practice Info | `users.display_name`, `users.license_number`, `users.state`, `users.contact_phone` all non-null | ✅ Yes |
| 2 | `continuity_stewards` | Continuity Stewards | `plan_stewards.where(steward_type=continuity_steward, status=active).count() >= 1` | ✅ Yes |
| 3 | `support_stewards` | Support Stewards | `plan_stewards.where(steward_type=support_steward, status=active).count() >= 1` | ⚠️ Recommended, not required |
| 4 | `incident_types` | Incident Types | `plan_incident_configs.where(is_active=1).count() >= 1` (at minimum one of the 3 default-on types must remain enabled) | ✅ Yes |
| 5 | `response_tasks` | Response Tasks | For each enabled incident type: `plan_tasks.where(plan_id, incident_type_via_config).count() >= 1` | ✅ Yes |
| 6 | `vault` | Vault | `continuity_plans.vault_attested_at IS NOT NULL` | ✅ Yes |
| 7 | `documents` | Documents | If any `plan_stewards.fee_cents > 0` → CS engagement agreement `status=fully_executed` required. Otherwise section auto-complete. | ⚠️ Conditional (see rule) |
| 8 | `sign` | Sign Plan | `continuity_plans.signed_at IS NOT NULL` | — (terminal action) |

**Signing eligibility:** sections 1, 2, 4, 5, 6 must be complete; section 3 warns but doesn't block; section 7 conditional-blocks per fee rule.

**"N/8 complete" chip:** counts sections 1–7 (section 8 is the action, not a checklist item). Display as `{completed}/7` on the HUB stat chip.

---

## 2. Module-specific invariants (do not violate)

Additive to the global invariants in `AEGIS_VUE_RULES.md`.

| Invariant | Reason |
|---|---|
| Signing uses `SignPlanRequest` + `PlanService::sign()` — one call, Option B single confirm | Chapman-locked |
| Plan cannot be signed until sections 1, 2, 4, 5, 6 complete (+ conditional 7) | Prevents partial plans going active |
| `plan_stewards.fee_cents` displayed in currency via `formatMoney` — never raw cents | Money-on-wire rule |
| Vault zones are exactly `standard / emergency / credentials / roster` — migration/enum MUST match | Fix bundled in Chat 6 |
| Vault access gated by `EnsureIncidentActive` middleware for CS portal — Provider always has full access to own vault | Existing policy, don't touch |
| `plan_incident_configs.is_active` (NOT `enabled` — service uses `enabled` but column is `is_active`) | Schema drift — fix in Chat 2 |
| CS designation with `fee_cents > 0` requires Stripe Connect on the CS side — surface a readiness warning | Rev 4 §0.6 |
| Cannot remove a CS while outstanding invoices exist (`sent / overdue / disputed`) | Rev 4 §0.6 |
| Attestation is one-shot: single `vault_attested_at` on `continuity_plans` | Not per-zone |
| Every plan/steward/vault/document write fires an Event → fanout to stewards → activity log | Standard three-prompt Rule |

---

## 3. Build workflow

### The three-prompt system — mandatory for every page

Every page in this module is built in exactly three sequential passes, each governed by its own canonical prompt file in the repo. **Read the prompt file before executing that pass — it is the single source of truth for that pass, not this spec.**

| Pass | Prompt file | What it does |
|---|---|---|
| **P1 — Design** | `Documents/Next Prompts/FINAL CONVERSION/PROMPT_1_DESIGN.md` | Convert legacy PHP → Vue 3 + Inertia with 100% visual parity. Design + UI-target wiring only. No real backend data, no emails. |
| **P2 — Backend** | `Documents/Next Prompts/FINAL CONVERSION/PROMPT_2_BACKEND.md` | Make every prop dynamic, every form real, every list bound to controller. Fill backend gaps. Seed all UI states. |
| **P3 — Email/Notify/Log** | `Documents/Next Prompts/FINAL CONVERSION/PROMPT_3_EMAIL_NOTIFY_LOG.md` | For every write action: (1) trigger correct email, (2) write `notification` activity entry for other parties, (3) write `log` entry for actor. |

**Execution rules:**
- P1 must be fully complete and verified before P2 starts
- P2 must be fully complete and verified before P3 starts
- P1 input = legacy PHP file (uploaded) + existing Vue stub
- P2 input = P1 output Vue file + controller + service + FormRequest + migration
- P3 input = P2 output — wires onto the already-complete service layer
- Each pass ends with `php -l` on all touched PHP files + `node --check` on all touched Vue files
- Deliver one ZIP per pass OR one combined ZIP after P3 — per-page preference

### Chat topology

```
Chat 1 (this one)  →  Spec produced. No code.                               ✅ DONE
Chat 2  →  ContinuityPlan.vue         P1→P2→P3  + bundled fix D             ✅ DONE
Chat 3  →  ContinuityStewards.vue     P1→P2→P3  + bundled fixes B & C       ← NEXT
Chat 4  →  SupportStewards.vue        P1→P2→P3
Chat 5  →  ImportantDocuments.vue     P1→P2→P3
Chat 6  →  Vault.vue                  P1→P2→P3  + bundled fix A
Chat 7  →  CONTINUITY_PRACTICE_MODULE.md consolidation MD
```

### Standard sub-chat launch prompt

Paste as the **first message** of each sub-chat (swap bracketed values):

```
Context: Continuity Practice Module.

Read Documents/CONTINUITY_PRACTICE_MODULE_SPEC.md from the fresh clone — anchor doc.
Read Documents/Next Prompts/FINAL CONVERSION/PROMPT_1_DESIGN.md before P1.
Read Documents/Next Prompts/FINAL CONVERSION/PROMPT_2_BACKEND.md before P2.
Read Documents/Next Prompts/FINAL CONVERSION/PROMPT_3_EMAIL_NOTIFY_LOG.md before P3.

This chat builds [PAGE].vue only.
Legacy PHP source: [PAGE].php (uploaded).
Follow spec [CHAPTER] (§[N]). Bundled fixes: [FIX LIST or "none"].

Fresh clone first:
rm -rf aegis && git clone --depth=1 https://github.com/rehanurrashid/aegis-laravel.git aegis

Execute P1 → P2 → P3 in sequence, reading the prompt file before each pass.
Work autonomously unless a design decision needs my input.
Deliver one ZIP mirroring exact repo folder structure after P3 completes.
```

### Per-chat reference

| Chat | Page | Spec chapter | Prompt files | Legacy PHP to upload | Bundled fixes |
|---|---|---|---|---|---|
| 2 | `ContinuityPlan.vue` | §4 / Chapter A | P1, P2, P3 | `continuity-plan.php` | Fix D | ✅ DONE |
| 3 | `ContinuityStewards.vue` | §5 / Chapter B | P1, P2, P3 | `ContinuityStewards.php` | Fix B, Fix C | ← NEXT |
| 4 | `SupportStewards.vue` | §6 / Chapter C | P1, P2, P3 | `support-stewards.php` | none |
| 5 | `ImportantDocuments.vue` | §7 / Chapter D | P1, P2, P3 | `important-documents.php` | none |
| 6 | `Vault.vue` | §8 / Chapter E | P1, P2, P3 | `vault.php` | Fix A |
| 7 | Consolidation MD | §9 / Chapter F | — | — | — |

### Bundled code-hygiene fixes

| ID | Fix | Bundled into |
|---|---|---|
| **A** | Vault zone reconciliation: new migration `ALTER TABLE vault_items MODIFY zone ENUM('standard','emergency','credentials','roster')` + update `VaultZone` enum | Chat 6 (Vault) — step 1 before design |
| **B** | `StewardService::enforceTierLimits` — replace hardcoded caps with `config('aegis.tier_limits.{tier}.max_{type}_stewards')` reads | Chat 3 (CS) — step 0 |
| **C** | `config/aegis.php` fallback defaults on lines 304–305: change `env('TIER_ACCESS_MAX_CS', 1)` → `env('TIER_ACCESS_MAX_CS', 2)` and same for SS | Chat 3 (CS) — step 0 |
| **D** | `PlanService::configureIncidentType` writes `enabled`; schema column is `is_active`. Reconcile: rename service param OR add DB alias. Recommendation: **update service to write `is_active`** | Chat 2 (Plan) — step 0 |

### Per-chat prompt structure

Every sub-chat opens with this preamble (paste as the first message):

```
Context: Continuity Practice Module — see attached CONTINUITY_PRACTICE_MODULE_SPEC.md.
This chat builds <PAGE NAME> only, applying the three-prompt system (P1 design → P2 backend → P3 email/notify/log).

Fresh clone first. Follow spec Chapter <X>. Bundled fixes: <list or "none">.
Work autonomously through P1→P2→P3 unless a design decision needs my input.
Deliver as one ZIP mirroring exact repo folder structure at the end of P3.
```

---

## 4. Chapter A — `ContinuityPlan.vue` (Chat 2 · HUB)

### Scope
The master plan view. Renders 8-section checklist, aggregate stat chips, sign-ceremony (Option B), attest button, activate-succession trigger, and the incident-type configuration accordion (with per-incident authorization matrix + task list).

### Bundled fix
**Fix D** — reconcile `PlanService::configureIncidentType` writes vs `plan_incident_configs.is_active` schema. Do this first before any Vue work.

### Inertia props contract (from `ContinuityPlanController::index`)

```php
Inertia::render('Provider/ContinuityPlan', [
    'plan'            => $plan,                                    // ContinuityPlan model
    'planSections'    => $this->plans->computeSections($plan),     // NEW — see §1
    'tasks'           => PlanTask::where('plan_id', $plan->id)->orderBy('sort_order')->get(),
    'incidentConfigs' => PlanIncidentConfig::where('plan_id', $plan->id)->get(),
    'stewards'        => PlanSteward::where('plan_id', $plan->id)
                            ->whereIn('status', ['active', 'pending'])->get(),
    'documents'       => ContinuityDocument::where('plan_id', $plan->id)
                            ->where('status', 'fully_executed')->get(),
    'incidentTypes'   => collect(IncidentType::cases())->map(fn($t) => [
                            'value'     => $t->value,
                            'label'     => $t->label(),
                            'is_optin'  => $t->isOptIn(),
                        ]),
    'canSign'         => $this->plans->canSign($plan),              // NEW — bool
    'canActivate'     => $this->plans->canActivate($plan, $user),   // NEW — bool
    'tierLimits'      => config('aegis.tier_limits.' . $user->tier),
])
```

### New service methods (add to `PlanService`)

- `computeSections(ContinuityPlan $plan): array` — returns the 8-section shape defined in §1, each with `{key, title, complete, blocks_signing, href}`
- `canSign(ContinuityPlan $plan): bool` — sections 1,2,4,5,6 complete + section 7 conditional
- `canActivate(ContinuityPlan $plan, User $user): bool` — plan is `active` AND no open incident exists

### Routes to wire

| Method | Route | Name | Status |
|---|---|---|---|
| GET | `/continuity-plan` | `plan.index` | ✅ |
| POST | `/continuity-plan` | `plan.store` | ✅ |
| POST | `/continuity-plan/sign` | `plan.sign` | ✅ |
| POST | `/continuity-plan/attest` | `plan.attest` | ✅ |
| POST | `/continuity-plan/annual-review/start` | `plan.review.start` | ✅ |
| POST | `/continuity-plan/annual-review/complete` | `plan.review.complete` | ✅ |
| POST | `/continuity-plan/incident-config` | `plan.incident-config` | ❌ **NEW** — wire `PlanService::configureIncidentType` |
| POST | `/continuity-plan/tasks` | `plan.tasks.store` | ❌ **NEW** — wire `PlanService::addTask` |
| DELETE | `/continuity-plan/tasks/{task}` | `plan.tasks.destroy` | ❌ **NEW** — wire `PlanService::removeTask` |
| POST | `/continuity-plan/tasks/reorder` | `plan.tasks.reorder` | ❌ **NEW** — wire `PlanService::reorderTasks` |

### Modals to build (in `resources/js/components/modals/`)

| Modal | Purpose |
|---|---|
| `SignPlanModal.vue` | Option B — single confirmation with payment authorization language (per Rev 4 §0.6), lists CS + `fee_cents`, section-completion summary, typed name + title, submit |
| `AttestPlanModal.vue` | Vault attestation confirmation + optional note |
| `IncidentConfigModal.vue` | Configure one incident type: `is_active`, `docs_required` (array), `authorized_cs_ids`, `authorized_ss_ids`, task list |
| `AddPlanTaskModal.vue` | Add a task to an incident config: `title`, `assigned_to`, `timeline`, `sort_order` |
| `ActivateSuccessionConfirm.vue` | Reuses `AegisConfirm` (destructive) — no new modal needed |

### Sign-ceremony copy (Option B, Chapman-approved)

The modal's confirmation text (approximate):

> **Sign your Continuity Plan**
> By signing, I confirm:
> - This plan reflects my current practice and stewardship arrangements
> - I authorize my designated Continuity Steward(s) to execute the response tasks listed above during any verified critical incident
> - **I authorize payment of the agreed compensation to each CS as listed below**, drawn from my default payment method on file, when an incident is closed:
>   - [CS Name] · [$fee_cents/formatMoney] · [payment_terms]
>   - …
> - This plan supersedes any prior plan I have signed on Aegis
>
> Type your name: [__________]
> Type your title: [__________]
> [ ] I have read and agree to the above
> [ Sign & activate plan ]

### P1 design gates — follow `PROMPT_1_DESIGN.md` in full, then verify all pass
- 8-section checklist, ordered, complete/incomplete visual state, `href` to sub-page or accordion anchor
- 4 stat chips (Last attested, CS count, SS count, `{completed}/7` sections)
- Incident-type accordion — one row per type, opt-in toggle for the 4 opt-in types, click expands to config panel
- Config panel: `is_active` toggle, `docs_required` multi-select, authorization matrix (checkbox grid of CS × SS), task list with add/remove/reorder
- Sign button disabled unless `canSign` true, disabled tooltip explains why
- Activate button visible only if `canActivate` true
- Zero `modal-id=`, zero `<svg>`, zero hex, zero Tailwind, zero `is-invalid`

### P2 backend wiring — follow `PROMPT_2_BACKEND.md` in full
- All 4 new routes registered
- New service methods added
- `defineProps` matches controller exactly
- Every form uses `useForm()` + Vuelidate + `fieldError()` + `is-error`
- Task reorder uses drag-and-drop → POST array of IDs
- Seed states: draft plan, plan with 3/8 sections, plan with 7/8, signed plan, expired plan, plan pending-CS-countersignature

### P3 email + notify + log — follow `PROMPT_3_EMAIL_NOTIFY_LOG.md` in full
- `PlanSigned` event → fanout to all stewards (`emails.plan.02-plan-signed`)
- `VaultAttested` event → fanout to stewards
- `PlanTaskAdded`, `IncidentConfigUpdated` events → activity log entry for actor + notification to affected stewards
- Every write action logs via `ActivityService::log()` — positional args, `entryType` at pos 11, `actorId` at pos 12

### Verification gates
- `php -l` clean on all touched PHP files
- `node --check` clean on all Vue files
- `php artisan route:list --path=continuity-plan` shows 10 routes
- `php artisan tinker` → `app(\App\Services\PlanService::class)->computeSections($plan)` returns 8-item array
- Sign a plan → activity log has both provider log entry + steward notification entries; email dispatched

---

## 5. Chapter B — `ContinuityStewards.vue` (Chat 3)

### Scope
CS designation, invite/remove flows, per-CS fee display and amendment, per-incident authorization summary, tier-limit enforcement. Includes Stripe Connect readiness warnings for paid CS.

### Bundled fixes
- **Fix B** — replace hardcoded caps in `StewardService::enforceTierLimits` with `config('aegis.tier_limits')` reads
- **Fix C** — change `.env` default fallbacks in `config/aegis.php` lines 304–305 from `1` → `2`

Do both first, before any Vue work.

### Inertia props contract

```php
Inertia::render('Provider/ContinuityStewards', [
    'stewards'           => // active + pending CS with joined user data + fee fields
        PlanSteward::with('user')
            ->where('plan_id', $plan->id)
            ->where('steward_type', 'continuity_steward')
            ->whereIn('status', ['active', 'pending', 'invited'])
            ->get()->map(fn($s) => [
                'id' => $s->id, 'user' => $s->user, 'role' => $s->role, 'status' => $s->status,
                'fee_cents' => $s->fee_cents, 'payment_terms' => $s->payment_terms,
                'auto_charge' => $s->auto_charge,
                'engagement_document_id' => $s->engagement_document_id,
                'stripe_connected' => (bool) $s->user?->stripe_account_id,
                'has_outstanding_invoices' => // count sent/overdue/disputed invoices for this CS
            ]),
    'pendingInvitations' => // status=invited or pending
    'tierLimits'         => config('aegis.tier_limits.' . $user->tier),
    'incidentConfigs'    => // for authorization summary chips
    'candidateStewards'  => // Network CS + Business CS the practitioner can select from
])
```

### Routes to wire

| Method | Route | Name | Status |
|---|---|---|---|
| GET | `/continuity-stewards` | `stewards.index` | ✅ |
| POST | `/continuity-stewards/invite` | `stewards.invite` | ✅ |
| DELETE | `/continuity-stewards/{steward}` | `stewards.remove` | ✅ (guard against outstanding invoices in service) |
| POST | `/continuity-stewards/{steward}/authorize` | `stewards.authorize` | ✅ |
| POST | `/continuity-stewards/{steward}/update-fee` | `stewards.update-fee` | ❌ **NEW** — requires an engagement-agreement amendment |
| POST | `/continuity-stewards/{steward}/resend-invite` | `stewards.resend-invite` | ❌ **NEW** |
| DELETE | `/continuity-stewards/{steward}/cancel-invite` | `stewards.cancel-invite` | ❌ **NEW** |

### Modals to build

| Modal | Purpose |
|---|---|
| `DesignateCsModal.vue` | Select from network OR invite external email · role (primary/alternate) · `fee_cents` · `payment_terms` · `auto_charge` toggle · triggers engagement agreement creation |
| `AmendCsFeeModal.vue` | Fee change → creates amendment document that must be countersigned before new fee takes effect |
| `AuthorizationSummaryModal.vue` | Read-only per-CS view — which incident types they're authorized for (chips) |

### Service gaps

- `StewardService::updateFee(PlanSteward $steward, int $newFeeCents, string $paymentTerms): ContinuityDocument` — creates amendment doc, does NOT mutate `plan_stewards.fee_cents` until countersigned
- `StewardService::resendInvite(PlanSteward $steward)` — re-fires invite email, resets `invited_at`
- `StewardService::cancelInvite(PlanSteward $steward)` — status → `archived`
- Guard in `remove()`: throw `RuntimeException` if outstanding invoices exist

### P1 design gates — follow `PROMPT_1_DESIGN.md` in full, then verify all pass
- Tier-limit chip at top ("2 of 2 CS designated" — reads from `tierLimits`)
- Steward cards: avatar, name, role pill, status pill, fee card if `fee_cents > 0`, Stripe Connect readiness dot (green if connected, gold warning if not and `fee_cents > 0`)
- "Add CS" button disabled when at tier limit — tooltip explains upgrade path
- Authorization summary chips per steward: which incident types they can act on
- Remove action: `confirmAction` callback; if outstanding invoices → show error toast, don't call
- Zero design violations per `AEGIS_VUE_RULES.md`

### P2 backend wiring — follow `PROMPT_2_BACKEND.md` in full
- Full CRUD wired, `useForm()` on every modal
- Vuelidate: `fee_cents` (integer, min 0), `payment_terms` (in enum), email (valid), name (min 2)
- Seed states: 0 CS, 1 CS (at Access limit), 2 CS (at Practice limit), pending invite, CS with fee and Stripe Connect, CS with fee and NO Stripe Connect (warning state)

### P3 email + notify + log — follow `PROMPT_3_EMAIL_NOTIFY_LOG.md` in full
- `StewardDesignated` event → email invited CS (`emails.steward.10-cs-invited`)
- `CsEngagementAgreementCreated` → email CS awaiting countersign (`emails.docs.20-engagement-pending`)
- `CsFeeAmended` → email CS awaiting countersign
- `StewardRemoved` → email CS + fanout to other stewards
- Every action logged via `ActivityService::log()`

---

## 6. Chapter C — `SupportStewards.vue` (Chat 4)

### Scope
SS designation, invite/remove, "verify closure" action for the 72-hour Provider-unresponsive fallback flow. Structurally mirrors ContinuityStewards but simpler (no fee, no engagement agreement).

### Inertia props contract

```php
Inertia::render('Provider/SupportStewards', [
    'stewards'           => // active + pending SS
    'pendingInvitations' => // status=invited/pending
    'tierLimits'         => config('aegis.tier_limits.' . $user->tier),
    'candidateStewards'  => // Network SS candidates
])
```

### Routes to wire

| Method | Route | Name | Status |
|---|---|---|---|
| GET | `/support-stewards` | `ss.index` | ✅ |
| POST | `/support-stewards/invite` | `ss.invite` | ✅ |
| DELETE | `/support-stewards/{steward}` | `ss.remove` | ✅ |
| POST | `/support-stewards/{steward}/resend-invite` | `ss.resend-invite` | ❌ **NEW** — mirror CS |
| DELETE | `/support-stewards/{steward}/cancel-invite` | `ss.cancel-invite` | ❌ **NEW** — mirror CS |
| POST | `/support-stewards/{steward}/suspend` | `ss.suspend` | ❌ **NEW** |
| POST | `/support-stewards/{steward}/reinstate` | `ss.reinstate` | ❌ **NEW** |

**Note on "verify closure":** this action lives in the SS portal (`/support-steward/incidents/{incident}/verify-closure`), NOT the Provider portal. The Provider-side surface here is just a status chip indicating "SS closure-verification available after 72h" — no button.

### Modals to build

| Modal | Purpose |
|---|---|
| `DesignateSsModal.vue` | Select from network OR invite external email · role (primary/alternate) |
| `SuspendSsModal.vue` | Confirm suspension + reason field |

### Service gaps

Add mirror methods to `StewardService`:
- `resendInvite(PlanSteward $steward)`
- `cancelInvite(PlanSteward $steward)`
- `suspend(PlanSteward $steward, string $reason)`
- `reinstate(PlanSteward $steward)`

### P1/P2/P3 gates — follow PROMPT_1_DESIGN.md → PROMPT_2_BACKEND.md → PROMPT_3_EMAIL_NOTIFY_LOG.md in sequence
Same shape as ContinuityStewards, minus fee/engagement UI. Seed states: 0/1/max SS, invited SS, suspended SS.

---

## 7. Chapter D — `ImportantDocuments.vue` (Chat 5)

### Scope
Practice-level documents:
- **CS engagement agreements** (auto-created when CS designated with `fee_cents > 0`)
- **Fee amendments** (auto-created when fee changed)
- **BAA** (system-level, seeded per practitioner)
- **Practitioner-uploaded practice documents** (policies, templates)

Deferred (not this module): signed plan snapshot PDFs, Aegis sample forms library.

### Inertia props contract

```php
Inertia::render('Provider/ImportantDocuments', [
    'documents'         => // all continuity_documents for plan_id, grouped by doc_type
    'pendingSignatures' => // where status=pending_sign AND practitioner not yet signed
    'awaitingCounter'   => // where status=countersign_pending
    'fullyExecuted'     => // where status=fully_executed
    'stewards'          => // active CS list — for choosing counterparty on new doc
])
```

### Routes to wire

| Method | Route | Name | Status |
|---|---|---|---|
| GET | `/important-documents` | `documents.index` | ✅ |
| POST | `/important-documents/request` | `documents.request` | ✅ |
| POST | `/important-documents/{document}/sign` | `documents.sign` | ✅ |
| POST | `/important-documents/upload` | `documents.upload` | ❌ **NEW** — practitioner uploads own doc |
| POST | `/important-documents/{document}/remind` | `documents.remind` | ❌ **NEW** — wire `DocumentService::remind` |
| POST | `/important-documents/{document}/archive` | `documents.archive` | ❌ **NEW** |
| GET | `/important-documents/{document}/download` | `documents.download` | ❌ **NEW** |

### Modals to build

| Modal | Purpose |
|---|---|
| `RequestDocumentModal.vue` | Choose doc type (agreement / amendment / other) · title · counterparty (CS from list) · body/template |
| `UploadDocumentModal.vue` | AegisDropzone · title · category |
| `SignDocumentModal.vue` | Option-B single-confirm signature · typed name · IP captured server-side |
| `ViewDocumentModal.vue` | Read-only body view + signature history |

### CS engagement agreement template

When a CS is designated with `fee_cents > 0` in Chat 3, `StewardService` auto-creates a `ContinuityDocument`:

- `doc_type = 'cs_engagement_agreement'`
- `title = "CS Engagement Agreement — {CS name}"`
- Body includes fee, payment_terms, auto_charge, activation criteria
- `status = 'pending_sign'` (practitioner signs first, then CS countersigns)
- FK stored on `plan_stewards.engagement_document_id`

Practitioner sees it in `ImportantDocuments.vue` under "Pending your signature" — signs it — status → `countersign_pending` — appears in CS portal for countersign — status → `fully_executed`.

### P1/P2/P3 gates — follow PROMPT_1_DESIGN.md → PROMPT_2_BACKEND.md → PROMPT_3_EMAIL_NOTIFY_LOG.md in sequence

- Tabs: Pending / Awaiting countersign / Fully executed / Archived
- Table columns: title, doc_type badge, counterparty, status pill, signed date, actions
- "Request document" button — modal
- "Upload document" button — modal
- Actions per row: View, Sign (if pending), Remind (if awaiting counter), Download, Archive
- Seed: at least one doc in each status; one CS engagement agreement in `fully_executed` for `p_sarah`

---

## 8. Chapter E — `Vault.vue` (Chat 6)

### Scope
4-zone document vault. Sealed by default (Provider always has access to their own; CS access gated by active incident via `EnsureIncidentActive` middleware — that's out of scope for this Provider page). Attest button. Per-item CS permissions.

### Bundled fix (do first)
**Fix A** — Vault zone reconciliation:
1. New migration: `ALTER TABLE vault_items MODIFY zone ENUM('standard','emergency','credentials','roster')`
2. Update `App\Enums\VaultZone`:
   - `case Standard = 'standard'`
   - `case Emergency = 'emergency'`
   - `case Credentials = 'credentials'`
   - `case Roster = 'roster'`
3. Update `VaultZone::label()` and `isEncrypted()` accordingly
4. Verify `VaultController::index` return keys already match (they do)
5. Verify `VaultService::upload` allowed list already matches (it does)
6. Update seeders if any reference old zone names

### Inertia props contract (from existing `VaultController::index` — no changes needed)

```php
Inertia::render('Provider/Vault', [
    'zones' => [
        'credentials' => $items->where('zone', 'credentials')->values(),
        'roster'      => $items->where('zone', 'roster')->values(),
        'emergency'   => $items->where('zone', 'emergency')->values(),
        'standard'    => $items->where('zone', 'standard')->values(),
    ],
    'planStatus' => $plan?->status ?? 'none',
    'attestedAt' => $plan?->vault_attested_at,
    'totalCount' => $items->count(),
])
```

### Routes (all exist)

| Method | Route | Name |
|---|---|---|
| GET | `/vault` | `vault.index` ✅ |
| POST | `/vault/upload` | `vault.upload` ✅ |
| POST | `/vault/attest` | `vault.attest` ✅ |
| GET | `/vault/{item}/download` | `vault.download` ✅ |
| POST | `/vault/{item}/permissions` | `vault.permissions` ✅ |
| DELETE | `/vault/{item}` | `vault.destroy` ✅ |

### Modals to build

| Modal | Purpose |
|---|---|
| `UploadVaultItemModal.vue` | Zone selector · title · description · AegisDropzone |
| `AddCredentialModal.vue` | Credentials zone specific: username, password (encrypted server-side), URL |
| `AddRosterEntryModal.vue` | Roster zone specific: client name, priority, notes |
| `VaultItemPermissionsModal.vue` | Per-item: which CS gets access — checkbox list |
| `AttestVaultModal.vue` | Confirmation + optional note |

### P1 design gates — follow `PROMPT_1_DESIGN.md` in full, then verify all pass

- 4 zone cards, each with title/description/count/items list
- Zone-specific empty states with "add item" CTA
- Credentials zone: password fields masked with reveal toggle (Provider only)
- Roster zone: priority-sorted list
- Attest button in hero actions (disabled if no items)
- Zero design violations

### P2 backend wiring — follow `PROMPT_2_BACKEND.md` in full

- Attest button → `POST /vault/attest` → shows attested_at timestamp
- Upload → correct zone routing
- Delete → `confirmAction` callback
- Seed: `p_sarah` vault has items in all 4 zones + `vault_attested_at` set

### P3 email + notify + log — follow `PROMPT_3_EMAIL_NOTIFY_LOG.md` in full

- `VaultItemAdded` → activity log (silent to stewards; SS only sees vault when incident active)
- `VaultAttested` → fanout to all stewards (existing event fires from `PlanService::attestVault`)

---

## 9. Chapter F — Consolidation MD (Chat 7)

Chat 7 produces `CONTINUITY_PRACTICE_MODULE.md` — the canonical reference doc mirroring the shape of `SUPPORT_SERVICES_MODULE.md` and `AEGIS_CLINICAL_SERVICES_MODULE.md`.

### Structure (proposed)

1. **Overview** — module purpose, business context
2. **Data model** — every table + column touched: `continuity_plans`, `plan_stewards`, `plan_tasks`, `plan_incident_configs`, `vault_items`, `continuity_documents`, `document_signatures`
3. **Services** — every method signature: `PlanService`, `StewardService`, `VaultService`, `DocumentService`
4. **Controllers** — every route → controller method → service call
5. **Vue pages** — component tree per page, prop contract, modal inventory
6. **Events + emails** — every event, every listener, every blade template
7. **Activity log** — every action's log/notify entries
8. **Policies** — `ContinuityPlanPolicy`, `VaultPolicy`, `StewardPolicy`, `DocumentPolicy`
9. **Tier gating** — how config values flow to UI
10. **Demo scripts** — the beats to run in front of a client
11. **Gotchas** — module-specific invariants (from §2 above)
12. **Test matrix** — key user flows to smoke-test after any change

---

## 10. Pre-flight before Chat 2 opens

Verify each is true. Anything red → stop and fix before starting Chat 2.

| Check | Command |
|---|---|
| Repo is at expected commit | `cd aegis && git log -1 --oneline` — should be `8128ae6` or later |
| `.env` values correct | `grep TIER_ACCESS_MAX ~/aegis/.env` → both = 2 |
| Chapman decisions applied in config | `grep -A2 founding_member config/aegis.php` shows 5000/100/100 |
| Vault zone drift exists (fix A pending) | `grep 'documents\|instructions' database/migrations/2024_01_01_000019_create_vault_items_table.php` should still show old values |
| `PlanService` uses `enabled` (fix D pending) | `grep "'enabled'" app/Services/PlanService.php` returns a hit |
| Backend services intact | `wc -l app/Services/{Plan,Steward,Vault,Document}Service.php` |
| Modals directory | `ls resources/js/components/modals/ | wc -l` — matches expected count |

---

## 11. Success criteria for the whole module

Module is DONE when:

- [ ] All 5 pages built, no design violations, all forms Vuelidated
- [ ] All new routes registered, all controllers return declared props
- [ ] Every write action fires event → fanout → activity log entry (log + notification)
- [ ] Every event has an email template
- [ ] `p_sarah` demo user hits all seed states:
  - signed active plan with 8/8 sections, `vault_attested_at` set
  - 2 CS designated (1 with `fee_cents=25000`, `payment_terms=on_close`), 1 fully-executed engagement agreement
  - 2 SS designated
  - 3 default-on incident configs enabled with tasks assigned
  - vault items across all 4 zones
  - 1 pending CS invite showing correctly
  - 1 CS engagement doc in `pending_sign` state (for demo of practitioner sign flow)
- [ ] `p_david` demo user shows early-state flow: draft plan, 2/8 sections, no vault items
- [ ] Consolidation MD produced

---

## 12. Working style reminders (from `AEGIS-PROJECT-CONTEXT.md`)

- Fresh clone every chat: `rm -rf aegis && git clone --depth=1 github.com/rehanurrashid/aegis-laravel.git`
- Surgical `str_replace` over rewrites
- `php -l` every PHP file, `node --check` every JS file after edits
- ZIPs mirror exact repo folder structure
- `project_knowledge_search` over `/mnt/project`
- Read actual files via `bash_tool` when auditing — docs go stale within sessions
- Mirror Provider patterns on CS/SS/BP/Admin when unsure
- No false stops on "Continue" — run autonomously

---

## Appendix — canonical reference tables

### A1. Incident types (`App\Enums\IncidentType`)

| Value | Label | Default-on | Docs typically required |
|---|---|---|---|
| `death` | Death | ✅ | Death certificate |
| `incapacitation` | Incapacitation | ✅ | Medical documentation |
| `extended_absence` | Extended Absence | ✅ | — |
| `missing` | Missing | opt-in | Police report |
| `detainment` | Detainment | opt-in | Legal documentation |
| `natural_disaster` | Natural Disaster | opt-in | — |
| `geopolitical` | Geopolitical | opt-in | — |

### A2. Vault zones (post-Fix A)

| Zone | Description | Encrypted? |
|---|---|---|
| `standard` | Practice documents (policies, templates, agreements) | No |
| `emergency` | Emergency-only documents (unlock on incident verify) | No |
| `credentials` | Login credentials (encrypted at rest) | **Yes** |
| `roster` | Active client roster with priority | No |

### A3. Tier limits (env-driven, from `config/aegis.php`)

| Tier | max_CS | max_SS |
|---|---|---|
| Access | `TIER_ACCESS_MAX_CS` = 2 | `TIER_ACCESS_MAX_SS` = 2 |
| Practice | `TIER_PRACTICE_MAX_CS` = 2 | `TIER_PRACTICE_MAX_SS` = 4 |

### A4. Steward status enum (`plan_stewards.status`)

`invited · active · declined · request_incoming · archived · pending`

### A5. Document status enum (`continuity_documents.status`)

`draft · pending_sign · countersign_pending · fully_executed · release_pending · release_requested · archived`

### A6. Payment terms enum (`plan_stewards.payment_terms`)

`on_close · net_30 · net_60`

### A7. Plan status enum (`continuity_plans.status`)

`draft · pending_review · active · annual_review_due · expired`

---

*Spec Rev 2 — 2026-07-12. Validated against repo `8128ae6`. Three-prompt system added (§3). Any drift discovered during a sub-chat build must be fed back to the main planning chat for spec update before proceeding.*

---

## Quick reference — three prompt file paths

```
Documents/Next Prompts/FINAL CONVERSION/PROMPT_1_DESIGN.md
Documents/Next Prompts/FINAL CONVERSION/PROMPT_2_BACKEND.md
Documents/Next Prompts/FINAL CONVERSION/PROMPT_3_EMAIL_NOTIFY_LOG.md
Documents/CONTINUITY_PRACTICE_MODULE_SPEC.md   ← this file
```
