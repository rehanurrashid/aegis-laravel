# Aegis — Master Conversion Plan: Settings + Continuity Group

**Scope:** Provider Settings page (21 panels) + the 6 Provider Continuity pages — Finances, Continuity Plan, Continuity Stewards, Support Stewards, Important Documents, Vault.
**Sources:** PHP prototype (7 files, ~16k lines) as functional ground truth; Laravel backend; KB docs. Repo commit `032f87e` baseline.
**Revision:** 2 — updated with P0/P1 batch alignment, peer-payment wiring context, CS engagement contract workflow, dispute system, tier customization, W-9 decision.

---

## ⚠️ Standing findings (unchanged)

**1. Settings.vue on disk is a minimal stub.** The file is ~80L with 3 flat cards (Account, Notifications, Privacy). The PHP prototype has 21 panels with a full sidebar nav. The SettingsController already has 7 routes; ~8 more exist in the KB but are not yet in `web.php`'s provider block. This must be fully rebuilt before Continuity Group work.

> **Update (2026-07):** Settings.vue rebuild completed separately from this plan. Provider Settings is now 2000+ lines with all 21 panels, subscription mgmt, Stripe Connect onboarding, MFA, sessions, danger zone. Referenced here for context only — not part of this plan's scope.

**2. Continuity pages are design-only or broken.** `ContinuityPlan.vue` (644L), `ContinuityStewards.vue` (1177L), `SupportStewards.vue` (786L), and `ImportantDocuments.vue` (945L) declare only `defineProps({ user: Object })` and render hardcoded data — they ignore the Inertia props their controllers already send. `Vault.vue` (120L) references props and a route (`provider.vault.unseal`) that do not exist. `Finances.vue` (124L) is raw prototype HTML (inline `<svg>`, inline styles, wrong-cased import). Conversion = backend wiring for the four stubbed pages + full rebuild for Vault and Finances.

> **Update (2026-07):** `Finances.vue` **has been rebuilt** in the P0 batch. It is now 304 lines, uses Inertia props (`subscription`, `paymentMethods`, `csInvoices`, `bpInvoices`, `paymentHistory`, `earnings`, `totalSpendCents`, `outstandingCents`, `stripeConnected`), calls real routes (`provider.finances.cs-invoice.pay`, `provider.jobs.bp-invoice.pay`), and includes confirm modals for pay actions. Remaining Continuity pages: ContinuityPlan, ContinuityStewards, SupportStewards, ImportantDocuments, Vault — still legacy static.

**3. ~30 write actions have no route yet.** The thin controllers cover the happy-path spine. The prototype's richer surface has no route. Recommendation: wire the 🟡 set (method exists, just needs a route — cheap) plus a curated ❌ subset visible in the demo script. Defer the rest behind "coming soon" to avoid dead buttons.

---

## 🔗 STEP 0.5 — Alignment with P0 & P1 batches (NEW)

Before starting Continuity Group Vue work, the following upstream work has landed. Every page in this group must consume these, not re-implement:

### From P0 batch
| Feature | Where | Consumers in Continuity Group |
|---|---|---|
| **Stripe Connect Express onboarding** | `Provider/SettingsController::connectOnboard/connectReturn`, `CS/SettingsController::connectOnboard/connectReturn`, `BP/SettingsController::connectOnboard/connectReturn` | Finances checks `stripeConnected` prop before showing peer-payment CTAs |
| **`chargeProviderToCs` engine** | `PayoutService::chargeProviderToCs` (line 244) | Finances "Pay CS invoice" button |
| **`chargeProviderToBp` engine** | `PayoutService::chargeProviderToBp` (line 142) | Finances "Pay BP invoice" button + SupportServices milestone pay |
| **`releaseServiceSessionPayout` engine** | `PayoutService::releaseServiceSessionPayout` (line 432) | (Provider→Provider session flow — already wired) |
| **CS invoice CRUD** | `CS/InvoicesController::index/store/send/void` + `cs.invoices.*` routes | ContinuityStewards page: show "invoices from your CS" widget linking to Finances |
| **`stripe_payment_method_id` persistence** | `SubscriptionService::setDefaultPaymentMethod` + `OnboardingController::subscribe` + `Provider/SettingsController::storePaymentMethod` — all mirror the PM id to the peer-charge column | Finances always assumes card is set correctly |
| **CS Payouts + Provider spend rows** | `PractitionerPayment` + `CsPayout` created on every CS invoice pay | ContinuityStewards shows "recent payouts to Marcus" |
| **`cs_invoices.stripe_payment_intent_id/stripe_transfer_id`** | Migration `2026_07_09_000001_add_stripe_columns_to_cs_invoices` | Finances displays "paid via ····" line |

### From P1 batch
| Feature | Where | Consumers in Continuity Group |
|---|---|---|
| **`account.updated` webhook** | `StripeEventListener` match block | `stripeConnected` flips automatically; no polling |
| **CS `escalate()` route + method** | `cs.continuity.escalate` | Not directly touched by Provider pages, but referenced in the Provider incident-simulator UI |
| **CS Providers accept/decline routes** | `cs.providers.accept/decline` | Provider ContinuityStewards page must know that pending → active can happen at any time (poll or Inertia reload on visibility) |
| **BP contract/milestone/invoice field-shape fix** | `ContractService::getForBp`, `InvoiceService::getForBp`, `MilestonesController::index` | Finances "Recent BP contracts" section uses the same shape (payment_type, client_name, amount_cents) |

### 🔴 Non-negotiable naming (do not drift back)
- Money: always **`total_cents` / `amount_cents`** — never `amount_dollars` or bare `amount` on the wire.
- Provider is called **`practitioner_id`** on `cs_invoices`, `bp_invoices`, `bp_contracts`. Not `provider_id`.
- Continuity Steward: **`cs_id`**. Business Partner: **`bp_id`**.
- Payment types on `bp_contracts`: **`one_time | milestone`**. Not `fixed | hourly`.
- Milestone statuses: **`pending | submitted | approved | rejected | paid`**. Not `open | completed`.
- Invoice statuses: **`draft | sent | overdue | paid | void`**. Not `pending | open | cancelled`.

Any Vue page in this group that uses the wrong name is a bug — the P0/P1 field-mismatch cleanup is now the authoritative naming.

---

## 💸 STEP 0.6 — Payment context per Continuity page (NEW)

Each page's touch surface into the peer-payment layer (Stripe Connect destination charges — Aegis never holds funds).

### Finances — the money hub
**Reads:**
- Provider's own subscription state (SubscriptionService)
- Cards on file (`paymentMethods` — Cashier)
- Provider's `stripe_id` (customer), `stripe_payment_method_id` (default PM), `stripe_account_id` + `stripe_connected` (for services-mode earnings)
- All CS invoices billed to provider (`CsInvoice` where `practitioner_id = user->id`)
- All BP invoices billed to provider (`BpInvoice` where `practitioner_id = user->id`)
- Recent payment history (`PractitionerPayment`) — spend + service-session earnings
- All BP contracts (their totals, milestones, milestone-paid state) for spend summary

**Writes:**
- Pay CS invoice → `provider.finances.cs-invoice.pay` → `PayoutService::chargeProviderToCs`
- Pay BP invoice → `provider.jobs.bp-invoice.pay` → `PayoutService::chargeProviderToBp`
- (Cards, subscription, autopay all live under Settings billing panel — Finances just displays)

**Zero-hold rule visible in copy:** every peer-payment CTA must display "Funds transfer directly to <recipient> via Stripe Connect. Aegis does not hold or take a cut." This is a legal + trust requirement per attorney direction — escrow language is banned.

---

### Continuity Plan — money is indirect
**Reads:** tier caps (drive CS/SS count limits). Does not touch peer payments directly, but:
- The **agreed CS fee** for the plan lives on `plan_stewards.fee_cents` (proposed — see §CS Engagement Contract below). Displayed on the plan when reviewing "who executes and at what cost."
- The Professional Will UI includes payment-authorization language: "In the event of my incapacitation/death, my Continuity Steward is authorized to draw against the payment method on file up to the pre-agreed fee for services rendered."

**Writes:** Sign action must confirm the payment authorization above alongside incident/task confirmations. This is critical because in a death/incapacitation scenario the CS may need to charge without the Provider present — the signed Plan is the legal artifact enabling that.

---

### Continuity Stewards — where the CS engagement contract lives
**Reads:**
- Active + pending CS designations (`plan_stewards` where `steward_type = continuity_steward`)
- For each active CS: their `stripe_connected` flag (via User model) → show "Ready to receive payment" or "CS hasn't finished Stripe Connect setup yet"
- Recent invoices from CS (`CsInvoice` where `cs_id = this cs and practitioner_id = current user`)
- Recent payouts to CS (`CsPayout` for the same pair)

**Writes:**
- Designate CS — includes `agreed_fee_cents` and `payment_terms` fields when inviting a Business CS. Invited CS defaults to 0 (unpaid family/colleague).
- Update fee — `plan_stewards.fee_cents` may be amended (both parties sign amendment doc)
- Cancel designation — check for outstanding invoices; block cancellation if any invoice is `sent` or `overdue`.

**Gating rule:** if `plan_stewards.fee_cents > 0` and the CS's `stripe_connected != 1`, warn the Provider ("Marcus hasn't finished Stripe setup. Payment will queue until he completes onboarding.") Do not block designation — the CS may still act in an incident with unpaid volunteer intent.

---

### Support Stewards — no direct payment surface
SS are unpaid (family, office manager). No Stripe Connect onboarding, no invoicing. But:
- SS is a trigger for **auto-close verification** (see §CS Engagement Contract Workflow). If Provider is unavailable when tasks complete, SS receives the "verify closure" ping.
- SS activity is required to authorize the delayed charge (only if Provider is dead/incapacitated). In normal flow, Provider verifies closure themselves.

**Writes:** verification action on incident close (see below).

---

### Important Documents — the contractual layer
The Professional Will and CS engagement agreement live here as first-class documents.
**Reads:** documents by plan + counterparty.
**Writes:** sign/countersign chain. The signed CS engagement agreement is the artifact that establishes `plan_stewards.fee_cents` legally — if there's ever a dispute (see §Disputes), this document is Exhibit A.

**Rule:** any change to `plan_stewards.fee_cents` requires an amendment document signed by both parties before the number is persisted. Direct edit of the numeric field without an amendment doc is disallowed at the service layer.

---

### Vault — indirect payment surface
Vault stores financial documents (banking info, tax records, insurance policies) that the CS needs post-incident. Not a payment page itself, but:
- The CS engagement contract PDF is stored in `credentials` zone with `permitted_steward_ids` = [designated CS(s)]
- Provider's payment authorization document is stored in `emergency` zone

No writes affect Stripe. Vault access after incident is what unlocks the CS's ability to submit their post-incident invoice.

---

## 🤝 STEP 0.7 — CS Engagement Contract Workflow (NEW)

**Answers your Point 3:** yes we should model this as a real contract. Existing `plan_stewards` gives us the assignment; we need to add the fee + payment terms + auto-close/auto-invoice mechanics.

### Data model changes (migration required)

Add columns to `plan_stewards`:
```sql
ALTER TABLE plan_stewards
  ADD COLUMN fee_cents INT DEFAULT 0 AFTER responsibilities,
  ADD COLUMN payment_terms ENUM('on_close','net_30','net_60') DEFAULT 'on_close' AFTER fee_cents,
  ADD COLUMN auto_charge TINYINT(1) DEFAULT 0 AFTER payment_terms,
  ADD COLUMN engagement_document_id CHAR(36) NULL AFTER auto_charge;
```

Rationale:
- `fee_cents` = agreed compensation per activation (not per hour, not per month — a per-incident retainer). If the Provider's plan is never activated, the CS is never paid.
- `payment_terms` = when to invoice/charge. Default `on_close` (immediate on incident close) fits the vast majority of use cases; `net_30/net_60` are for institutional relationships.
- `auto_charge` = if `1` AND Provider has verified the closure OR is deceased AND has a card on file, fire the destination charge automatically without waiting for Provider action. Default `0` (requires manual Provider approval).
- `engagement_document_id` = FK to the countersigned engagement agreement in `continuity_documents`. Required if `fee_cents > 0`.

### Task completion + verification flow

Existing model already has:
- `incident_tasks` (one row per task assigned to the CS on incident activation)
- `IncidentService::activate()` generates them from `plan_tasks` on `activate`
- `IncidentService::close(summary)` marks incident closed (Provider or CS can call today)

New flow proposal:

```
[ INCIDENT ACTIVE ]
   │
   │  CS works through incident_tasks, marks each complete
   │  (existing: incident_tasks.completed_at)
   │
   │  When all tasks in the incident are completed:
   ▼
[ TASKS COMPLETE — VERIFICATION PENDING ]
   │
   │  System notifies Provider + Support Steward:
   │    "All continuity tasks are complete. Please verify closure."
   │  Both channels: notification + email.
   │
   │  Provider or SS clicks "Verify closure" → incident.verified_by_id set
   │  IF Provider is dead/incapacitated:
   │    Provider will not verify; SS's verification is sufficient
   │    (per Professional Will payment authorization)
   │
   │  If NOBODY verifies within 7 days:
   │    Auto-verify + auto-close (with prominent activity log note)
   ▼
[ INCIDENT CLOSED ]
   │
   │  IF plan_steward.fee_cents > 0:
   │    Auto-create CsInvoice for the pre-agreed fee
   │    Attach the incident_id + engagement_document_id for audit
   │
   │  IF plan_steward.auto_charge = 1 AND payment_terms = 'on_close'
   │  AND provider has a default PM AND CS has stripe_connected = 1:
   │    Immediately fire PayoutService::chargeProviderToCs
   │    Invoice status: draft → paid in one step
   │  ELSE:
   │    Invoice status: draft → sent (Provider pays manually via Finances)
   ▼
[ PAID / OUTSTANDING ]
```

### Who can mark tasks complete
- **CS only.** They executed them.

### Who can verify closure (in this order of preference)
1. **Provider** — normal flow, they're alive and available.
2. **Support Steward** — if Provider is unavailable within 72 hours of task-complete notification.
3. **System auto-verify** — if neither responds in 7 days.

### Who can *initiate* close
- **CS** — normal flow, after all tasks complete and verification received.
- **Provider** — can close any time (bypasses fee if `payment_terms = 'on_close'`, but respects `fee_cents` — the money is still owed for work done).
- **Admin** — dispute resolution scenario.

### Who **cannot** close
- **SS** — they can only *verify*, not *close*. Closing releases money; too much power for a family member without professional context.

### New routes needed
- `POST /continuity-steward/incidents/{incident}/verify-closure` — Provider OR SS calls this (Provider portal + SS portal both have the route)
- `POST /provider/incidents/{incident}/verify-closure` — same handler, provider path
- `POST /support-steward/incidents/{incident}/verify-closure` — same handler, SS path
- `POST /continuity-steward/incidents/{incident}/close-with-invoice` — CS-initiated close that atomically closes + generates invoice + optionally auto-charges

### New event
- `IncidentReadyForClosure` — fired when last task completed; triggers verification-request email to Provider + SS
- `IncidentAutoClosed` — fired when 7-day timer expires; triggers "auto-closed" email to all parties
- `CsInvoiceAutoGenerated` — fired on close-with-invoice; sends invoice email to Provider

### Backwards compatibility
- Existing incidents (verified_by_id = null) still supported — the flow is opt-in per `plan_steward.fee_cents > 0`. Zero-fee designations behave exactly as today.

---

## ⚖️ STEP 0.8 — Dispute System (NEW — answers your Point 7)

**Verdict:** yes, we need this. It's the standard peer-marketplace escape valve. Without it, Aegis becomes an unbounded liability when parties disagree.

### Scope
Disputes are opened by either party over:
1. **A specific invoice** (CS invoice, BP invoice) — recipient claims work not delivered, or payer claims payment unfair
2. **A specific payout** (BP milestone payment, service session) — either side claims fraud/error
3. **A CS engagement** (rare — usually the payment above covers it)

Aegis does NOT arbitrate outcome — we mediate + provide the audit trail. Actual money movement uses Stripe's dispute rails (refunds, ACH reversal). Aegis is the coordination + evidence layer.

### Data model (new tables)

```sql
CREATE TABLE disputes (
    id CHAR(36) PRIMARY KEY,
    disputer_id CHAR(36) NOT NULL,          -- who opened it
    respondent_id CHAR(36) NOT NULL,        -- other party
    subject_type VARCHAR(32) NOT NULL,      -- 'cs_invoice' | 'bp_invoice' | 'bp_payout' | 'session' | 'engagement'
    subject_id CHAR(36) NOT NULL,           -- morph to the record
    reason ENUM(
      'non_delivery',
      'quality_issue',
      'unauthorized_charge',
      'duplicate_charge',
      'wrong_amount',
      'other'
    ) NOT NULL,
    amount_disputed_cents INT NOT NULL DEFAULT 0,
    description TEXT NOT NULL,              -- disputer's opening statement
    status ENUM('open','under_review','awaiting_response','resolved','closed_no_action') DEFAULT 'open',
    resolution ENUM('refund_full','refund_partial','pay_full','pay_partial','no_action','stripe_dispute_escalated') NULL,
    resolution_cents INT NULL,              -- if refund_partial or pay_partial
    resolution_summary TEXT NULL,
    resolved_by CHAR(36) NULL,              -- admin user
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    respondent_replied_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    INDEX idx_disputer (disputer_id),
    INDEX idx_respondent (respondent_id),
    INDEX idx_subject (subject_type, subject_id),
    INDEX idx_status (status)
);

CREATE TABLE dispute_messages (
    id CHAR(36) PRIMARY KEY,
    dispute_id CHAR(36) NOT NULL,
    author_id CHAR(36) NOT NULL,
    author_role ENUM('disputer','respondent','admin') NOT NULL,
    body TEXT NOT NULL,
    attachment_url VARCHAR(500) NULL,       -- to a vault item or upload
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dispute (dispute_id, created_at)
);
```

### Workflow
```
[ OPEN ]  disputer files → email to respondent + admin
   │  respondent has 5 business days to reply
   ▼
[ AWAITING_RESPONSE ]
   │  respondent posts reply → status: under_review
   │  respondent doesn't reply in 5 days → status: under_review anyway (default judgment risk noted)
   ▼
[ UNDER_REVIEW ]  admin reviews thread, escalates if needed
   │
   ├─ Admin decides: refund_full → Stripe refund via chargeRefunded path
   ├─ Admin decides: refund_partial → Stripe partial refund
   ├─ Admin decides: pay_full → dismiss dispute, no money moves
   ├─ Admin decides: no_action → dispute frivolous, both parties notified
   └─ Admin decides: stripe_dispute_escalated → beyond our scope, party can file Stripe chargeback
   ▼
[ RESOLVED ]
   │
   ▼
[ CLOSED ]  (7 days after resolution, no further messages)
```

### Payment freeze rule
When a dispute opens against a `cs_invoice` or `bp_invoice` still in `sent`/`overdue`:
- Invoice status auto-changes to `disputed` (new status — needs to be added to `InvoiceStatus` enum: `draft | sent | overdue | disputed | paid | void`)
- Payer cannot pay through Finances until dispute is resolved

When a dispute opens against a `bp_payout` or `cs_payout` that already fired:
- No automatic reversal (money already moved via Stripe Connect)
- Admin can trigger Stripe `refunds->create` from admin dispute screen
- Payer notified: "You may also file a Stripe chargeback directly if unresolved"

### Portal touchpoints
- **Provider** — Finances: "Open dispute" button next to any paid invoice/payout within 60 days; "My disputes" tab
- **CS** — Finances: same button on invoices they issued; "My disputes" tab
- **BP** — Finances: same
- **Admin** — new `admin.disputes.*` routes; queue view, individual dispute detail with resolution actions
- **All parties** — email on open, on reply, on resolution

### What Aegis does NOT do
- Not a court. If admin resolution is unacceptable, party can pursue Stripe chargeback + civil suit.
- Not an escrow. We never hold funds; refunds go through Stripe's normal rails.
- Not lawyers. Resolution decisions are non-binding on external legal action.

**Implementation effort estimate:** ~2 days (migrations + models + service + Admin dashboard + inline "Open dispute" modals across Provider/CS/BP Finances).

---

## 📊 STEP 0.9 — Tier Limits Customization (NEW — answers your Point 6)

Current state: `config/aegis.php` hardcodes tier limits.

Change: swap each numeric limit to `env('TIER_LIMIT_…', default)` so ops/admin can tune without a deploy. Deploy this now (small change); shift to database-backed admin panel later.

Config diff (in `config/aegis.php`):
```php
'tier_limits' => [
    'access' => [
        'max_continuity_stewards' => (int) env('TIER_ACCESS_MAX_CS', 1),
        'max_support_stewards'    => (int) env('TIER_ACCESS_MAX_SS', 1),
        // (bools unchanged; can be envified later if needed)
    ],
    'practice' => [
        'max_continuity_stewards' => (int) env('TIER_PRACTICE_MAX_CS', 2),
        'max_support_stewards'    => (int) env('TIER_PRACTICE_MAX_SS', 4),
    ],
],
```

Default keeps behaviour identical. `.env` values override at runtime.

**Access `max_support_stewards` decision:** default `1` (matches config today). If Dr. Chapman confirms she meant `2`, flip via env: `TIER_ACCESS_MAX_SS=2`. Do NOT hardcode until she signs off.

Follow-up in later phase: move all of `tier_limits` into a `tier_configs` DB table with an admin UI. But env is enough for now.

---

## 🧾 STEP 0.10 — W-9 Gating Decision (NEW — answers your Point 2)

**Question:** do BP/CS need a verified W-9 on file before receiving payment?

**Analysis:**
- Aegis uses **Stripe Connect Express destination charges** — money moves directly from payer's Stripe customer to recipient's Stripe Connect account. Aegis never holds funds.
- Under IRS rules, when Aegis facilitates a payment via a Payment Settlement Entity (Stripe), **Stripe** — not Aegis — issues the 1099-K to the recipient if thresholds are met.
- Upwork requires W-9 because they route payments through their own accounts (they ARE a payment settlement entity for that flow). Aegis does not.
- Aegis's only tax exposure: if we ever paid a BP/CS directly from an Aegis account (not the case today).

**Recommendation: SOFT WARN, DO NOT HARD-BLOCK.**

Rationale:
1. Not legally required for the destination-charge flow (Stripe handles reporting).
2. Hard-block would break the family/colleague Invited-CS use case — a sister acting as an unpaid CS shouldn't need a W-9.
3. Best-practice audit trail — keep the current soft warning that fires an `ActivityService` warning log on payment-without-W-9. Admin can see the aggregate.
4. When a BP/CS crosses Stripe's 1099-K threshold (currently $600 for the tax year), Stripe collects the W-9 equivalent directly for their own reporting — Aegis isn't in that loop.

**Action:** keep the existing soft warn in `JobPostingsController::payBPInvoice` (already there). Add the same soft warn to `FinancesController::payCSInvoice` for symmetry. **Do not add a `TaxDocStatus::Required` gate.**

If Dr. Chapman later wants Aegis to also file its own 1099-NEC for BPs (independent contractor reporting for the platform's own tax records), we revisit. That's a business decision, not a payment blocker.

---

## 💳 STEP 0.11 — Native "Add Card" Flow (NEW — answers your Point 5)

**Clarification of the earlier concern:**

Cards live in two conceptual buckets:
1. **Subscription card** — pays Aegis for the user's own subscription. Handled by Cashier via `updateDefaultPaymentMethod`. Stored on the Stripe customer (`users.stripe_id`).
2. **Peer-payment card** — Provider uses it to pay BPs/CSs. Same Stripe customer, same PM id — but we also mirror it to `users.stripe_payment_method_id` so the peer-charge methods (`chargeProviderToBp/Cs`, `releaseServiceSessionPayout`) can find it without a Stripe roundtrip.

The mirroring is done at three points (all now correct after P0):
- `OnboardingController::subscribe()` on signup
- `SubscriptionService::setDefaultPaymentMethod()` when user picks a saved card as default
- `Provider/SettingsController::storePaymentMethod()` when user adds a new card

**Current UX gap (the "Add card" issue):**
In `Provider/Settings.vue` billing panel, the "Add card" button links to the **Stripe Customer Portal** (external). User leaves the app to add a card. Works, but breaks flow.

**"Native" would mean:** in-app modal with Stripe Elements (cardNumber/cardExpiry/cardCvc inputs, same as `OnboardingPayment.vue`) that captures the card and POSTs to `provider.settings.payment.store` — user never leaves the app.

**Scope:**
- Providers, Business CS, Business Partners all subscribe and need this. Same modal, three portals.
- Provider is the only one that also uses their card for peer payments — but that's already handled by the same PM save.
- Not urgent. The Stripe Portal path works today. This is a Q4 polish item.

**Decision:** defer to post-launch. Track as UX debt.

---

## STEP 0 — Settings (unchanged — see original)

Already delivered separately.

---

## STEP 1 — Continuity Group page purposes (unchanged — see original doc for full detail)

Six pages: Finances (DONE per P0), Continuity Plan, Continuity Stewards, Support Stewards, Important Documents, Vault.

---

## STEP 2 — Continuity Lifecycle Map (updated to include payment triggers)

```
[ FINANCES ] — subscription active (Access $29/mo · Practice $49/mo)
     │  sets tier caps (Access: 1 CS + 1 SS · Practice: 2 CS + 4 SS)
     │  card on file (used for both subscription AND peer payments)
     ▼
[ CONTINUITY PLAN ]  status: draft ──────────────┐
     │  configure incident types (3 default + 4 opt-in)
     │  define per-incident task lists            │
     │  Provider consents to payment authorization│
     ▼                                            │ (must exist to sign)
[ CONTINUITY STEWARDS ] designate CS → pending → active
     │  agree on fee_cents + payment_terms + auto_charge
     │  Business CS: verify stripe_connected = 1
     │  Invited CS: fee_cents = 0 (volunteer)
     │  authorization matrix (which CS per incident)
     └──────────────► back to PLAN: SIGN ◄────────┘
                        status: draft → ACTIVE
                        (needs ≥1 enabled config + ≥1 ACTIVE CS)
     ▼
[ SUPPORT STEWARDS ] designate SS → pending → active   (SS = incident trigger + payment verifier)
     ▼
[ IMPORTANT DOCUMENTS ] request → sign → countersign → fully_executed
     │                  (CS engagement agreement lives here — legally binds fee_cents)
     ▼
[ VAULT ] owner uploads freely; attest; SEALED to stewards
     │
     ▼
══════ CRITICAL INCIDENT ══════
 SS reports → CS verifies → activate():
   • generates incident_tasks from plan_tasks
   • fires VaultUnsealed → assigned stewards gain scoped read
 CS works through tasks, marks each complete →
   when all tasks complete:
     ─ IncidentReadyForClosure event → email Provider + SS
     ─ Provider verifies closure → close()
     ─ OR SS verifies closure (if Provider unavailable > 72h) → close()
     ─ OR system auto-closes after 7 days
 On close():
   • Re-seals vault
   • IF plan_steward.fee_cents > 0:
       auto-create CsInvoice
       IF auto_charge = 1 AND provider PM present AND CS stripe_connected:
         chargeProviderToCs() fires immediately → paid
       ELSE:
         invoice sent → provider pays via Finances (manual)
```

---

## STEP 3 — Recommended Conversion Order (updated)

| Order | Page | Why | Complexity | Demo priority |
|---|---|---|---|---|
| 0 | **Settings** | Done ✅ | — | — |
| 1 | **Finances** | Done ✅ (P0 batch) | — | — |
| 2 | **Continuity Plan** | Add `fee_cents` agreement UI in sign-ceremony. Core signing flow drives all downstream state. | High | Critical |
| 3 | **Continuity Stewards** | Add fee/payment-terms fields on designate + invite. Show CS Stripe Connect readiness. | High | Critical |
| 4 | **Support Stewards** | Mirrors CS with less. Add "verify closure" action to SS incident detail. | Medium | High |
| 5 | **Important Documents** | Add CS engagement agreement template with `fee_cents` merge tag. | Medium | High |
| 6 | **Vault** | Rebuild from broken stub. | Medium | Critical |

New parallel workstream (can start any time):
| Order | Feature | Complexity | Depends on |
|---|---|---|---|
| P2-A | Tier limits envification | Trivial | — |
| P2-B | Dispute system (data model + service + admin dashboard + inline "Open dispute" modals) | Medium (~2 days) | Finances (done) |
| P2-C | CS Engagement Contract migration + service methods + auto-close flow | Medium (~1.5 days) | Continuity Plan + CS pages |
| P2-D | Native "Add card" Stripe Elements modal | Small (~2 hr) | — (defer) |

---

## STEP 4 — Gaps + Risks (unchanged — see original doc)

Plus new items:
- **Escrow / dispute payment freeze** — `InvoiceStatus::Disputed` new enum case + migration to update ENUM
- **Incident auto-close scheduler** — cronjob checking incidents in `verification_pending` state older than 7 days, fires `IncidentAutoClosed`
- **Task completion tracking** — `incident_tasks.completed_at` already exists; need `IncidentService::maybeReadyForClosure(incident)` triggered on every task completion

---

## STEP 5 — Demo-readiness data (updated)

Additions to p_sarah seed:
- CS engagement with `cs_marcus`: `fee_cents = 25000` ($250 per activation), `payment_terms = 'on_close'`, `auto_charge = 0`
- Countersigned engagement document with the fee terms
- One resolved dispute (past example) in dispute_messages table for demo

---

## STEP 6 — Demo Scripts (unchanged — see original doc)

Add new beat to **Continuity Stewards** demo:
> "Marcus's agreed fee is $250 per activation. If an incident is verified and closed, that fee auto-invoices to me — I approve payment from Finances. If I'm deceased, my Support Steward Linda can verify closure on my behalf and the payment goes through automatically per the payment authorization I signed with the Plan."

Add new beat to **Vault** demo:
> "Vault seal isn't just about privacy — it's the mechanism that gates Marcus's ability to submit an invoice. He can't bill me for services until the incident is verified activated, meaning he actually did the work."

---

## Pre-flight checklist before writing Vue (updated)

### Settings
- Done ✅

### Continuity Group (unchanged foundations)
- [ ] Vault zones resolved to `standard/emergency/credentials/roster`
- [ ] SS tier cap locked to `4` (Practice) — confirm Access is 1 or 2 with client
- [ ] DocumentStatus enum reconciled to service strings
- [ ] `critical_incidents.verified_by_id` vs `verified_by_cs_id` column name verified
- [ ] 🟡 demo-path routes added
- [ ] Curated ❌ demo routes added
- [ ] Confirm domain seed rows exist
- [ ] Confirm `inc_sarah_active` toggles vault state

### Continuity Group — payment-context additions (NEW)
- [ ] Migration: `plan_stewards.fee_cents`, `payment_terms`, `auto_charge`, `engagement_document_id`
- [ ] Migration: `cs_invoices` (already added via P0) and `bp_invoices` — add `Disputed` to InvoiceStatus enum
- [ ] Migration: `disputes` + `dispute_messages` tables
- [ ] Migration: `incident_tasks.completed_at` — verify column exists (if not, add)
- [ ] `PayoutService::chargeProviderToCs` — verified working from P0 ✅
- [ ] `PayoutService::chargeProviderToBp` — verified working from P0 ✅
- [ ] `IncidentService::maybeReadyForClosure(incident)` — new method, checks all tasks complete → fires event
- [ ] `IncidentService::verifyClosure(incident, verifier)` — new method, callable from Provider or SS
- [ ] `IncidentService::closeWithInvoice(incident, closer)` — new method, auto-generates CsInvoice on close if fee_cents > 0
- [ ] Event: `IncidentReadyForClosure`, `IncidentAutoClosed`, `CsInvoiceAutoGenerated` — registered in `AppServiceProvider`
- [ ] Email templates: task-complete verification request, auto-close notice, auto-generated invoice notice
- [ ] Scheduler: `IncidentAutoCloseCheckJob` — runs hourly, closes stale verification-pending incidents
- [ ] Native peer-payment CTAs on Continuity Stewards page pointing to Finances
- [ ] Dispute open modal available from Provider/CS/BP Finances
- [ ] Admin disputes queue

---

## Revision log

- **Rev 1 (initial):** Base plan for Settings + 6 Continuity pages
- **Rev 2 (2026-07):** Aligned with P0/P1 batches; added Payment Context per page (§0.6); CS Engagement Contract Workflow (§0.7); Dispute System (§0.8); Tier Limits Envification (§0.9); W-9 Decision (§0.10); Native Add Card scope (§0.11); updated Lifecycle Map + Demo Scripts + Pre-flight checklist with payment items
