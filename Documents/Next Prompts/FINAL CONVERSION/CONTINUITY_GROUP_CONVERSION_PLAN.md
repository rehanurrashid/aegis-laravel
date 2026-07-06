# Aegis — Continuity Group Conversion Plan

**Scope:** The 6 Provider-portal Continuity pages — Continuity Plan, Continuity Stewards, Support Stewards, Important Documents, Vault, Finances.
**Sources analysed:** PHP prototype (6 files, 13,395 lines) as functional ground truth; Laravel backend (`ContinuityPlanController`, `ContinuityStewardController`, `SupportStewardController`, `VaultController`, `FinancesController`, `DocumentsController`; `PlanService` / `StewardService` / `VaultService` / `DocumentService` / `IncidentService` / `SubscriptionService`); enums, models, routes; KB (`Key Terms & Responsibilities`, `AEGIS_LARAVEL_STRUCTURE`, pricing store, seeders). Repo commit `032f87e`.

---

## ⚠️ Two findings that reframe the effort

**1. The "converted" pages are design-only, not wired.** `ContinuityPlan.vue` (644L), `ContinuityStewards.vue` (1177L), `SupportStewards.vue` (786L), and `ImportantDocuments.vue` (945L) each declare only `defineProps({ user: Object })` and render **hardcoded** data — they ignore the Inertia props their controllers already send. `Vault.vue` (120L) is wired to props (`counts/items/unsealedNow`) and a route (`provider.vault.unseal`) that **do not exist**. `Finances.vue` (124L) is raw prototype HTML (inline `<svg>`, inline styles, wrong-cased `../../Components/AppLayout.vue` import). So "conversion" = **backend wiring (Prompt 2)**, not design — plus a full rebuild of Vault and Finances.

**2. The prototype defines ~44 write actions; the Laravel provider layer routes ~14.** The thin controllers cover the happy-path spine (create/sign/attest/review, invite/remove/authorize, upload/attest/permissions, request/sign, payment-method). The prototype's richer surface — role changes, resend, vault-access levels, document amend/renew/terminate/dispute, invoice approve/reject, autopay, contract status, pay-model — has **no route yet**. See the coverage matrix in Step 4. Decide per-action: wire for demo, or defer.

---

## STEP 1 — Purpose of each page

### 1. Continuity Plan
**What it does.** The Practitioner's "Professional Will" builder. Enable the critical-incident types to cover, define per-incident task lists, record signature identity, then **sign** to activate. Once active, it drives steward responsibilities, vault gating, and incident task generation.

**Lifecycle position.** Root of the chain (after an active subscription). **`PlanService::sign()` refuses unless ≥1 incident config is enabled AND ≥1 *active* Continuity Steward exists** — so CS designation precedes signing (inverts the naive "sign then designate" order). There is no separate "signed" vs "attested" status: signing sets `status=active`; attestation is a `vault_attested_at` timestamp on the already-active plan.

**Prototype surface.** Modals: `finalizeModal` (sign ceremony), `rowEditorModal` (task editor), `toggleConfirmModal` (enable/disable incident), `annualModal` (annual review), `howItWorksModal`. Actions: `save_draft`/`draft`/`delete_draft`, `save_incident_config`, `add_task`/`replace_tasks`, `finalize_sign`/`sign`, `attest_vault`, `begin_annual_review`.

**Key data.** `plan.status` (PlanStatus enum), incident configs, task lists (by incident × assignee role), designated stewards, documents.

**Dependencies.** Requires: active subscription; ≥1 active CS *to sign*. Unlocks: authorization matrix, incident task generation, vault attestation. Affects: every other page.

**States.** No plan → Draft (editable; sign gated) → Active → Annual Review Due → Expired.

---

### 2. Continuity Stewards
**What it does.** Designate the professional(s) who execute the plan during a critical moment (facilitate referrals, coordinate care, manage records/closure). Handles internal/external invitation, the pending→active lifecycle, tier caps, and the **per-incident authorization matrix** (which CS may verify/close which incident types).

**Lifecycle position.** Needs a plan (draft suffices). **Gates plan signing.** The authorization matrix feeds `IncidentService::activate()` task assignment.

**Prototype surface (richest page).** Modals include `addExecutorIncidentsModal`, `inviteExternalModal`, `csAuthMatrixModal`, `changeRoleModal`, `activateSuccessionModal` (alternate promotion), `grantVaultModal`, `removeExecutorModal`, `requestReleaseModal`, `resendInviteModal`, `cancelInviteModal`, `editExecutorModal`, `snoozeReviewModal`, `annualReviewModal`, `auditLogModal`, `viewResponsibilitiesModal`, `viewAgreementModal`, `previewAgreementModal`, `csProfilePreviewModal`. Actions: `add_steward`, `invite_external`, `remove`, `set_authorization`, `update_role`, `resend_invite`, `set_vault_access`/`grant_access`.

**Key data.** Active/pending/incoming/declined CS partitions; tier limits (Access = 1 CS, Practice = 2 CS); auth-by-incident-type map; "serving as CS for others" metrics.

**Dependencies.** Requires: plan draft. Unlocks: plan signing; incident task assignment. Affects: Plan (sign gate), Vault (CS unseals), Documents (CS countersigns), Incidents (CS verifies).

**States.** Empty → has pending → at cap (upgrade prompt) → has active (+ matrix).

---

### 3. Support Stewards
**What it does.** Designate the *relational* trusted person (friend/family) who activates the plan and **reports** a critical incident. Simpler than CS — no authorization matrix, no countersigning. Shares `StewardService` with `steward_type = support_steward`.

**Lifecycle position.** Needs a plan. SS is the human trigger: only an assigned SS (or the Practitioner) can `report()` an incident.

**Prototype surface.** Modals: `addDsrStep1Modal` (add flow), `editDsrModal`, `editPermissionsModal`, `changeDsrRoleModal`, `suspendDsrModal`/`reinstateModal`, `removeDsrModal`, `resendInviteModal`/`cancelInviteModal`/`editInviteModal`, `renewAgreementModal`, `taskDetailModal`/`taskCompletionModal`/`delegateTaskModal`/`transferTaskModal`, `emergencyDsrModal`, `dsrGuideModal`, `onboardingChecklistModal`, `notifSettingsModal`, `flagActionModal`. Note the prototype gives SS more status states than CS: active / suspended / pending / declined / archived.

**Key data.** SS roster (active + suspended); invites (pending + declined + archived); tier cap (Access = 2 SS).

**Dependencies.** Requires: plan. Unlocks: incident reporting. Affects: Incidents, notifications.

**States.** Empty → pending → active → suspended → at cap.

---

### 4. Important Documents
**What it does.** Manage supplemental agreements that augment the plan (role expectations, operating instructions, BAAs, NDAs). Documents move through **request → sign → countersign → fully executed**.

**Lifecycle position.** Meaningful once stewards exist (counterparties). Not a hard downstream gate, but part of a "complete" plan and the annual-review checklist.

**Prototype surface.** Modals: `newAgreementModal`/`addDocumentModal`, `templateModal` (apply template), `signatureModal`/`sendForSignatureModal`/`signSuccessModal`, `amendmentModal`, `renewalModal`, `terminateModal`, `disputeModal`, `accessRevocationModal`, `baaModal`, `ndaAttachModal`, `triPartyViewModal`, `agreementActionsModal`, `exportModal`, `draftSaveModal`, `viewAgreementModal`. Actions: `send_for_signature`, `sign`, `countersign`, `amend`, `renew`, `apply_template`, `set_status`, `request_release`.

**Key data.** Documents for plan; pending-signature subset; document stat chips.

**Dependencies.** Requires: plan; stewards for countersignature. Affects: annual-review completeness.

**States.** Empty → pending signatures → countersign pending → fully executed → archived.

---

### 5. Vault
**What it does.** The Practitioner's secure repository for what stewards need in a crisis — credentials, insurance, practice records, client roster. **The owner uploads freely at any time.** "Sealed" governs *who else* can read: per glossary and `VaultService::getContents()`, stewards get access **only after a verified critical incident is activated**, and only if assigned + active. The Practitioner also **attests** the vault.

**Lifecycle position.** Fed continuously once a plan exists. Unsealing is **implicit** — driven by `IncidentService::activate()` firing `VaultUnsealed`, not a button the Practitioner presses. `Vault.vue`'s "unseal ceremony" is fictional and must be removed.

**Zone taxonomy — RESOLVED.** Three conflicting sets exist (enum: `credentials/roster/documents/instructions`; controller+prototype: `standard/emergency/credentials/roster`; component validator: `sealed/unsealed/plan/archive`). The **prototype and controller agree on `standard / emergency / credentials / roster`** — that is runtime truth. Align the enum and the `VaultZone.vue` component to it.

**Prototype surface.** Modals: `uploadModal`/`uploadEmergencyModal`, `addCredentialModal`, `addClientModal`/`editClientModal`/`viewClientModal` (roster), `docDetailModal`, `shareModal`, `vaultPermissionsModal`/`addPermissionModal`/`editPermissionModal` (per-steward access), `vaultAttestModal`, `sendReminderModal`, `deleteConfirmModal`. Actions: `add_item`/`add_credential`/`add_client`, `update_item`, `delete_item`, `set_permissions`/`update_access`, `share`, `attest_vault`, `send_reminder`.

**Real controller props.** `zones` (`credentials/roster/emergency/standard`), `planStatus`, `attestedAt`, `totalCount`.

**States.** Sealed (default, no active incident) → Unsealed (active incident + assigned steward) → Attested badge.

---

### 6. Finances
**What it does.** Subscription/billing home: current tier, payment methods, invoice/payment history, CS service-fee invoices (Stripe Connect — Aegis never holds funds), and the Access→Practice **upgrade** entry point.

**Lifecycle position.** The **gate**. Active subscription is precondition for the whole group; tier sets steward caps and services-mode.

**Prototype surface.** Modals: `addPaymentModal`/`editCardModal`/`removeCardModal`, `autoPaySettingsModal`, `approveInvoiceModal`/`rejectInvoiceModal`, `payArrangementModal`, `changePayModelModal`, `viewInvoice`/`viewReceiptModal`/`viewContractModal`, `hireBPModal`, `cancelBPContractModal`/`cancelExecutorContractModal`, `executorDetailModal`, `disputeModal`, `exportModal`. Actions: `add_payment_method`, `set_default_method`, `remove_payment_method`, `set_autopay`, `approve_invoice`/`set_invoice_status`, `set_contract_status`, `set_steward_payment_model`.

**Real controller props.** `subscription` (`getStatus()`), `paymentMethods`, `invoiceHistory` (PractitionerPayment), `csServiceFees` (BpInvoice).

**States.** No subscription → active Access → active Practice → grace period.

---

## STEP 2 — Continuity Lifecycle Map

```
[ FINANCES ] — subscription active (Access $29/mo · Practice $49/mo)
     │  sets tier caps (Access: 1 CS + 2 SS · Practice: 2 CS + SS-cap)
     ▼
[ CONTINUITY PLAN ]  status: draft ──────────────┐
     │  configure incident types (3 default + 4 opt-in)
     │  define per-incident task lists            │
     ▼                                            │ (must exist to sign)
[ CONTINUITY STEWARDS ] designate CS → pending → active
     │  authorization matrix (which CS per incident)
     └──────────────► back to PLAN: SIGN ◄────────┘
                        status: draft → ACTIVE
                        (needs ≥1 enabled config + ≥1 ACTIVE CS)
     ▼
[ SUPPORT STEWARDS ] designate SS → pending → active   (SS = incident trigger)
     ▼
[ IMPORTANT DOCUMENTS ] request → sign → countersign → fully_executed
     ▼
[ VAULT ] owner uploads freely; attest; SEALED to stewards
     │
     ▼
══════ CRITICAL INCIDENT ══════
 SS reports → CS verifies → activate():
   • generates incident_tasks from plan_tasks (match incident_type + assigned_to role)
   • fires VaultUnsealed → assigned stewards gain scoped read
   • ungated fan-out to all stewards + Practitioner
 close() → re-seals vault
```

**Corrections vs. naive assumptions:** CS must be **active before** the plan can be signed; there is no distinct "signed"→"attested" status (attestation is a timestamp on the active plan); vault unseal is **incident-driven and implicit**, not a Practitioner action.

**Critical demo path:** Finances(Practice) → Plan(draft: configs+tasks) → CS(active) → sign Plan(active) → SS(active) → Documents(executed) → Vault(sealed, attested) → simulate incident → Vault unsealed + tasks generated.

---

## STEP 3 — Recommended Conversion Order

| Order | Page | Why | Complexity | Demo priority |
|---|---|---|---|---|
| 1 | **Finances** | Subscription gate + tier caps everything reads. On-disk file is a throwaway stub; a clean wired reference exists in KB to mirror. | Medium | Low (technical) |
| 2 | **Continuity Plan** | Core; status/config/task/sign drive all downstream state. Highest logic density. | High | Critical |
| 3 | **Continuity Stewards** | Gates signing; owns authorization matrix. Wire before finalizing plan-sign UX. | High | Critical |
| 4 | **Support Stewards** | Mirrors CS with less (no matrix/countersign). Fast once CS pattern is set. | Medium | High |
| 5 | **Important Documents** | Needs stewards as counterparties; sign/countersign chain. | Medium | High |
| 6 | **Vault** | Most dramatic (sealed→unsealed), but the drama lives on the CS side. Provider side is upload/attest/permissions. Do last, after incident wiring is settled. | Medium | Critical |

---

## STEP 4 — Gaps + Risks

### Backend completeness
- **PlanService** — complete: `createDraft, updateSection, addTask, removeTask, reorderTasks, configureIncidentType, sign, attestVault, beginAnnualReview, completeAnnualReview, submitForReview, publishVersion, getForPractitioner`.
- **StewardService** — complete: `designate, inviteExternal, accept, decline, resign, remove, copyTasks (no-op by design), setAuthorization, requestRoleChange, activateAlternate, getForPlan, getPendingInvitations`.
- **VaultService** — present but **diverges from `AEGIS_LARAVEL_STRUCTURE.md`**. Doc spec lists `getForSteward/reveal/signedUrl/vault_access-on-plan_stewards`; actual code uses `getForPractitioner/getContents/signedDownloadUrl/permitted_steward_ids JSON`. **Code is truth.**
- **IncidentService** — `activate()` correctly generates `incident_tasks` and fires `VaultUnsealed`. ⚠️ This repo references `$incident->verified_by_id` while the KB copy uses `verified_by_cs_id` — **verify the real column on `critical_incidents`.**

### 🔴 Three conflicts to settle before wiring
1. **Vault zones** — resolve to **`standard / emergency / credentials / roster`** (prototype + controller). Fix the enum and `VaultZone.vue` validator to match.
2. **SS tier cap** — `StewardService` = Practice **unlimited**; `pricing.js` = Practice `maxSs: 4`; controller sends `max_ss` for Access only. Pick Practice SS cap (recommend 4 to match pricing) and align all three.
3. **Document statuses** — `DocumentStatus` enum (`draft/countersign/active/archived/release_pending`) vs `DocumentService` raw strings (`pending_sign/countersign_pending/fully_executed`). Model cast will mismatch. Reconcile enum ↔ service.

### 🔴 Prototype-action → Laravel-route coverage matrix
Legend: ✅ routed · 🟡 service/method exists but **not routed** · ❌ no backend.

| Prototype action | Page | Laravel status |
|---|---|---|
| save_draft / create draft | Plan | ✅ `plan.store` |
| delete_draft | Plan | ❌ |
| save_incident_config | Plan | 🟡 `configureIncident()` exists, no route |
| add_task / replace_tasks / remove_task | Plan | 🟡 methods exist, no routes |
| finalize_sign / sign | Plan | ✅ `plan.sign` |
| attest_vault | Plan/Vault | ✅ `plan.attest` / `vault.attest` |
| begin_annual_review / complete | Plan | ✅ `review.start` / `review.complete` |
| add_steward / invite_external | CS/SS | ✅ `stewards.invite` / `ss.invite` |
| remove steward | CS/SS | ✅ `stewards.remove` / `ss.remove` |
| set_authorization (matrix) | CS | ✅ `stewards.authorize` |
| update_role / change role | CS/SS | ❌ (`requestRoleChange()` exists, no route) |
| activate succession (alternate) | CS | ❌ (`activateAlternate()` exists, no route) |
| resend_invite / cancel_invite | CS/SS | ❌ |
| suspend / reinstate | SS | ❌ |
| grant_access / revoke / set_vault_access (levels) | CS/Vault | ❌ (only `permissions` = permitted IDs) |
| share vault item | Vault | 🟡 `share()` exists, no route |
| add_credential / add_client / add_item | Vault | 🟡 only generic `vault.upload` |
| update_item / delete_item | Vault | 🟡 `destroy` only; no update |
| set_permissions | Vault | ✅ `vault.permissions` |
| request document / send_for_signature | Docs | ✅ `documents.request` |
| sign | Docs | ✅ `documents.sign` |
| countersign | Docs | ❌ (provider side; `countersign()` exists) |
| amend / renew / terminate / apply_template / set_status | Docs | ❌ |
| request_release | Docs | ❌ (`requestRelease()` exists, no route) |
| add_payment_method | Finances | ✅ `finances.payment.store` |
| set_default_method / remove_payment_method | Finances | ❌ |
| set_autopay | Finances | ❌ |
| approve_invoice / reject / set_invoice_status | Finances | ❌ |
| set_contract_status | Finances | ❌ |
| set_steward_payment_model | Finances | ❌ |

**Recommendation:** For demo readiness, wire the 🟡 (route the existing methods — cheap) and a curated ❌ subset that appears in the demo script (role change, resend invite, countersign, vault-item update/delete, invoice approve). Defer the rest (autopay, contracts, pay-model, dispute, terminate) behind "coming soon" or hidden actions to avoid dead buttons.

### Vue wiring debt (per page)
| Page | On-disk | Debt |
|---|---|---|
| ContinuityPlan | 644L hardcoded, `props:{user}` | Consume `plan/tasks/incidentConfigs/stewards/documents`; wire sign/attest/review/task/config via `useForm` |
| ContinuityStewards | 1177L hardcoded | Consume `stewards/pendingInvitations/tierLimits`; wire invite/remove/authorize (+ role/resend if scoped) |
| SupportStewards | 786L hardcoded | Consume `stewards/pendingInvitations`; wire designate/remove |
| ImportantDocuments | 945L hardcoded | Consume `documents/pendingSignatures`; wire request/sign (+ countersign) |
| Vault | 120L **broken** | Rebuild against `zones/planStatus/attestedAt/totalCount` + real routes; drop fake unseal ceremony |
| Finances | 124L **raw HTML** | Full replace; mirror the clean KB reference (`AegisHeroBanner/AegisCard/pricing.formatCents/upgradeModal`) |

No Echo/websockets needed for this group — fan-out is activity-feed + email.

### Demo-readiness data
- **p_sarah must have:** Practice tier + card on file; plan `plan_sarah` **active/signed**, 3 incidents enabled + task list; `cs_marcus` active CS + 1 pending CS invite; `ss_linda` active SS; one `fully_executed` + one `pending_sign` document; vault items across all 4 zones + `vault_attested_at` set. `ActivitySeeder` references `plan_sarah` heavily — **confirm the domain rows (plan, plan_stewards, incident configs, tasks, documents, vault_items) are actually seeded**, not just the activity log.
- **Incident sim:** `?emergency=true/false` to flip vault sealed↔unsealed for the demo beat; confirm `inc_sarah_active` exists and toggles state.
- **Reset:** `php artisan migrate:fresh --seed` (full) or targeted `db:seed --class=…` for the continuity domain.

### Tier gating
- **Access $29/mo:** 1 CS, 2 SS, vault, attestation, activity feed. No services/network/referrals.
- **Practice $49/mo:** 2 CS, SS-cap (settle conflict #2), + services discoverability, job postings, network/referrals.
- **Upgrade prompts:** Continuity Stewards (CS cap), Support Stewards (SS cap), Finances (upgrade card). Use `AegisUpgradeModal` (provider Access→Practice) — distinct from `UpgradeCSModal` (CS account plan).

---

## STEP 5 — Demo Scripts (~2 min each)

### Finances
Start: p_sarah on Practice, Visa ···4242, 2 paid invoices, 1 CS service fee.
1. Show active Practice plan + monthly amount. 2. Payment method + invoice history. 3. CS service-fee line — "what Sarah pays her steward via Stripe Connect; Aegis never touches the money." 4. (Optional) upgrade modal.
**Value:** *"One subscription runs your entire continuity infrastructure."*
**Q:** "Does Aegis hold my payments?" → "No — Stripe Connect Express; funds move directly between parties."

### Continuity Plan
Start: active signed plan, 3 incidents, populated tasks.
1. Status banner ("Active — signed by Sarah Johnson"). 2. Walk enabled incidents (3 default) + note 4 opt-in. 3. Open a task list — "exactly what your steward will do." 4. Show attest + review actions.
**Value:** *"Your whole continuity apparatus in one signed, versioned document."*
**Q:** "What if I forget to review it?" → "It auto-flags Annual Review Due a year after signing."

### Continuity Stewards
Start: `cs_marcus` active + 1 pending invite.
1. Active roster + authorization matrix (who's cleared for which incident). 2. Pending invite — resend/revoke. 3. Try a 2nd CS on Access → upgrade prompt.
**Value:** *"You choose exactly who acts, and for which kind of crisis."*
**Q:** "Can I have a backup?" → "Yes — alternates, and Practice allows two Continuity Stewards."

### Support Stewards
Start: `ss_linda` active.
1. Linda as active SS. 2. She's the relational trigger — can report an incident. 3. Invite/pending flow.
**Value:** *"A trusted person can trigger your plan the moment something happens."*
**Q:** "Does my Support Steward see my records?" → "Only scoped access, and only after a verified incident is activated."

### Important Documents
Start: 1 fully-executed doc, 1 pending signature.
1. Executed doc — sign→countersign chain with names/timestamps. 2. Pending doc — action needed. 3. Feeds the annual-review check.
**Value:** *"Every agreement is signed, countersigned, and audit-ready."*
**Q:** "Are these legally binding?" → "They capture signature identity, IP, and timestamp; enforceability is per your jurisdiction and counsel."

### Vault
Start: items across all 4 zones, `vault_attested_at` set, no active incident (sealed).
1. Owner view — items by zone + attested badge. 2. Sealing: "stewards can't read this right now." 3. **The beat:** `?emergency=true` (or run incident sim) → CS Vault view → items readable, access logged. 4. Return to sealed after close.
**Value:** *"Everything a steward needs, locked until the moment it's truly needed."*
**Q:** "Who can open the vault?" → "Only an assigned steward, only during a verified active incident — every access is logged."

---

## Pre-flight checklist before writing Vue
1. Settle the 3 conflicts (vault zones → `standard/emergency/credentials/roster`; SS cap; document statuses).
2. Confirm `critical_incidents` verified-by column name.
3. Confirm continuity domain rows for `plan_sarah` are seeded (not just activity log).
4. Decide the routed vs deferred action set from the coverage matrix (route the 🟡 methods first — cheapest wins).
5. Wire in Step-3 order, starting with Finances (mirror the KB reference, discard the on-disk stub).
