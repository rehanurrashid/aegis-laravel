# Aegis — Payments & Finance System (Peer-to-Peer Transfers)

**Status:** Validated against live repo `main @ 9351e14` on 2026-07-09
**Previous validation:** `6cfabb4` on 2026-07-08
**Revision:** 3 — reflects P0 + P1 + Batch3, all 4 peer-payment flows now built end-to-end
**Companion docs:** `AEGIS_BILLING_LIFECYCLE.md` · `AEGIS-PROJECT-CONTEXT.md` · `AEGIS_SETTINGS.md` · `CONTINUITY_GROUP_CONVERSION_PLAN.md` · `ENV_REFERENCE.md`

**Scope:** Peer-to-peer money flows — money that moves **between users** (Provider→BP, Provider→CS, Client→Provider), all via Stripe Connect. Deliberately **separate** from subscription billing (Provider→Aegis), which lives in `AEGIS_BILLING_LIFECYCLE.md`. Two different Stripe integrations: subscriptions use **Laravel Cashier**; peer transfers use the **Stripe PHP SDK** directly.

**🆕 Rev 3 additions:** All 4 flows now have live destination-charge code paths. Real Stripe Connect Express onboarding wired for BP + CS + Provider (services mode). CS engagement contract auto-invoice flow built. Dispute system operational.

> **Naming clarification:** In the Provider portal UI, the BP marketplace is labelled **"Support Services"** (`/provider/support-services`, `JobPostingsController`) — hire Business Partners. **"My Services"** (`/provider/services`, `ServicesController`) is the clinical-services module — sell sessions to clients. Do not confuse with Support Steward role or the help-desk `SupportService` (complaints/tickets).

**Legend:** ✅ Verified complete · ⚠️ Partial · ❌ Not yet implemented · 🆕 New in this revision

---

## 1. Architecture Principle

> **Aegis never holds funds.**

All peer-to-peer money moves directly between parties via **Stripe Connect destination charges**. Aegis is the Stripe *platform* account — it facilitates the transfer but its net balance is always **$0** on contract, milestone, session, CS invoice, and BP invoice payments. Escrow language was removed per attorney direction; funds are never parked in an Aegis-controlled balance.

Two Stripe transfer mechanisms:
- **Destination charge** (`transfer_data.destination`) — charge the payer and route 100% to the recipient's connected account in one atomic operation. **Preferred pattern.** Used for all 4 peer flows.
- **Platform transfer** (`transfers->create`) — separate transfer from platform balance to a connected account. Fallback used only by generic `PayoutService::release()` (admin-driven manual payouts). Requires funds pre-existing in platform balance.

---

## 2. The Four Money Flows

| # | Flow | Direction | Mechanism | Covered in |
|---|---|---|---|---|
| 1 | **Provider Subscription** | Provider → Aegis | Cashier | `AEGIS_BILLING_LIFECYCLE.md` |
| 2 | **Provider → BP** (Support Services) | Provider → BP | Destination charge | §3 |
| 3 | **Provider → CS** (CS invoice or engagement auto-charge) | Provider → CS | Destination charge | §4 |
| 4 | **Client → Provider** (My Services) | Client → Provider | Destination charge | §5 |

Flow 1 is Aegis revenue and lives in the billing doc. Flows 2–4 are peer transfers.

---

## 3. Flow 2 — Provider → BP (Support Services / Job Postings) ✅

The Upwork-style marketplace. Practitioner posts a job under **Support Services**, BPs submit proposals, practitioner accepts → contract created → payment released as one lump or per-milestone.

### Money path
```
Provider's saved card (users.stripe_id + users.stripe_payment_method_id)
    ↓  PaymentIntent { confirm:true, transfer_data.destination = BP acct }
Aegis Platform account ($0 net)
    ↓  automatic destination transfer
BP's Stripe Connect Express account (users.stripe_account_id)
    ↓  BP's own payout schedule
BP's bank account
```

### Status: ✅ BUILT & WORKING (demo + real-Stripe code paths)

**Workflow (milestone contract):**
1. Provider posts job → `JobPostingsController@store`
2. BP submits proposal → `ProposalService`
3. Provider accepts proposal → contract created (`payment_type=milestone`)
4. Both parties sign → contract `active`
5. Provider adds milestones → `ContractService`
6. BP submits milestone work → `submitMilestone` (`pending → submitted`)
7. Provider approves → `approveMilestone`
8. Provider pays → `POST .../milestones/{milestone}/pay` → `PayoutService::payMilestone` → **`PayoutService::chargeProviderToBp`**
9. Destination charge fires → `BpPayout` row created → milestone `status: paid`, `paid_at` set
10. `PayoutReleased` event → `SendEmailNotificationListener` emails BP template `bp/40-payout-released`

**Workflow (one-time contract):** identical through step 4, then Provider clicks "Release Payment" → `endContractAndRelease` → `chargeProviderToBp`. Contract → `completed`.

**Core charge** — `PayoutService::chargeProviderToBp()`:
- Demo detection: `cus_demo_` / `pm_demo_` / `acct_demo_` prefixes → returns stub `pi_demo_*` paid, no real Stripe call
- Guards: throws if Provider lacks `stripe_id`/`stripe_payment_method_id`, or BP lacks real `acct_*`
- No Stripe secret configured → `pi_stub_*` pending
- Live: `paymentIntents->create` with `confirm:true`, `transfer_data.destination`, `on_behalf_of`, metadata

**Files:** `PayoutService::chargeProviderToBp/endContractAndRelease/payMilestone` · `JobPostingsController::releasePayment/payMilestone/approveMilestone` · `ContractService` · `StripeEventListener::handlePaymentIntentSucceeded/Failed`

**DB:** `bp_payouts` (`stripe_payment_intent_id`, `practitioner_id`, `bp_id`, `contract_id`, `milestone_id`, `status`) · `bp_milestones` (statuses `pending/submitted/approved/rejected/paid`) · `bp_contracts` (`payment_type: one_time|milestone`)

**Webhook confirmation:** `payment_intent.succeeded` marks paid + notifies; `payment_intent.payment_failed` marks failed + critical alert.

### 🆕 Rev 3 — W-9 soft-warn on payment
`JobPostingsController::payBPInvoice` and `Provider/FinancesController::payCSInvoice` both log a warning if paying a BP/CS without a verified W-9, but do NOT block payment. Per Rev 2 §0.10 (billing plan doc): Stripe Connect handles 1099-K reporting for destination charges; W-9 is not legally required for the peer-charge flow. Hard-blocking would break the Invited-CS-as-family use case.

### 🆕 Rev 3 — Disputed invoice blocks payment
If a Provider tries to pay an invoice with status `disputed`, `payCSInvoice` and BP invoice pay handlers return "This invoice is under dispute. Wait for the dispute to be resolved before paying." See §11 for the dispute integration.

---

## 4. Flow 3 — Provider → CS ✅

Rev 3 status **✅ BUILT & WORKING** (was ❌ NOT WIRED in Rev 2 — Gap 4 resolved by P0 batch).

Two entry points now exist:

### 4a. Manual CS Invoice Pay
CS creates an invoice → sends to Provider → Provider pays from Finances.

**Route:** `POST /provider/finances/cs-invoices/{invoice}/pay`
**Controller:** `Provider/FinancesController::payCSInvoice`
**Engine:** `PayoutService::chargeProviderToCs()`

### 4b. 🆕 CS Engagement Contract Auto-Invoice
When a CS designation has `plan_stewards.fee_cents > 0`, incident close auto-generates a `CsInvoice` and (if `auto_charge=1`) immediately charges it.

**Trigger:** `IncidentService::closeWithInvoice()`
**Engine:** same `PayoutService::chargeProviderToCs()`

### Money path (both)
```
Provider's saved card
    ↓  PaymentIntent { transfer_data.destination = CS acct }
Aegis Platform ($0 net)
    ↓
CS's Stripe Connect account (users.stripe_account_id)
```

**Core charge** — `PayoutService::chargeProviderToCs()` (line 244 of `PayoutService`):
- Same demo detection + guards + live-charge flow as `chargeProviderToBp`
- Metadata includes `cs_invoice_id`
- On success: `stripe_payment_intent_id` and `stripe_transfer_id` written back to `cs_invoices` (via P0 migration `2026_07_09_000001_add_stripe_columns_to_cs_invoices`)
- Fires `PractitionerPayment` (kind `cs_fee`) + `CsPayout` records

### 🆕 Rev 3 — CS Invoice CRUD (from CS side)
`CS/InvoicesController` has full CRUD: `index / store / send / void`. Routes registered:
- `GET /continuity-steward/invoices` → `cs.invoices.index`
- `POST /continuity-steward/invoices` → `cs.invoices.store`
- `POST /continuity-steward/invoices/{invoice}/send` → `cs.invoices.send`
- `POST /continuity-steward/invoices/{invoice}/void` → `cs.invoices.void`

**DB:** `cs_invoices` (`cs_id`, `practitioner_id`, `invoice_number`, `status`, `total_cents`, `currency`, 🆕 `stripe_payment_intent_id`, 🆕 `stripe_transfer_id`, `issued_at`, `due_at`, `paid_at`)

---

## 5. Flow 4 — Client → Provider (My Services / Clinical Sessions) ✅

Practitioner (with Practice tier + `services_mode=1`) lists services; client requests one; practitioner accepts → session booked; when client **confirms completion**, payment transfers to practitioner.

### Money path
```
Client's saved card (users.stripe_id + users.stripe_payment_method_id)
    ↓  PaymentIntent { confirm:true, transfer_data.destination = Provider acct }
Aegis Platform ($0 net)
    ↓
Provider's Stripe Connect account (users.stripe_account_id)
```

### Status: ✅ BUILT & WORKING

**Workflow:**
1. Provider creates service (gated by `services.mode` middleware — Practice tier + `services_mode=1`)
2. Client submits request
3. Provider accepts
4. Session booked (`ServiceSession`, `amount_cents` set)
5. **Client confirms completion** → `ServicesController::completeSession` → `ServiceService::completeSession` → `PayoutService::releaseServiceSessionPayout`
   - Guard: only `session.client_id` may confirm; only `scheduled` sessions
   - Creates `PractitionerPayment` (`kind=service_session`, pending)
   - Fires destination charge
   - Stamps `stripe_payment_intent_id` on session
   - `SessionCompleted` event; activity + notifications
6. Webhook `payment_intent.succeeded` confirms the charge

Note: session-complete route is deliberately **outside** `services.mode` middleware — any provider who booked a session **as a client** can confirm it, even without services mode themselves.

**`PayoutService::releaseServiceSessionPayout`** mirrors `chargeProviderToBp`: demo detection, production guards, stub-pending when no Stripe key, live destination charge with failure handling.

---

## 6. Stripe Objects Per Role ✅

| Role | Stripe object | Column on `users` | Used for |
|---|---|---|---|
| Provider | Customer | `stripe_id` | Charged for subscriptions + BP/CS payments |
| Provider | Payment Method | `stripe_payment_method_id` | Default card for contract/milestone/session/CS-invoice payments |
| Provider | Connected Account | `stripe_account_id` | Receiving client payments (My Services) |
| Client (a Provider acting as client) | Customer + PM | `stripe_id` + `stripe_payment_method_id` | Charged for booked sessions |
| BP | Connected Account | `stripe_account_id` | Receiving contract/milestone payouts |
| CS (Business) | Connected Account | `stripe_account_id` | Receiving invoice payments |
| Aegis | Platform account | `STRIPE_SECRET` in `.env` | Facilitating all transfers |

**🆕 Rev 3 — column reconciliation resolved:** Rev 2 warned about `stripe_connect_account_id` vs `stripe_account_id`. **Resolved:** the legacy stub column is gone (BP `PaymentSetupController` deleted); all portals now use `users.stripe_account_id` uniformly.

**🆕 Rev 3 — PM mirroring resolved (Rev 2 Gap 1):** All 3 write sites now mirror the PaymentMethod id to `users.stripe_payment_method_id`:
- `Provider/SettingsController::storePaymentMethod` (line 548)
- `SubscriptionService::setDefaultPaymentMethod` (line 382)
- `OnboardingController::subscribe` (line 246)

This unblocks real-money BP + CS + session charges for non-demo providers.

---

## 7. What's Done vs What's Missing

### ✅ Done (all Rev 2 gaps closed in Rev 3)

| Feature | Where |
|---|---|
| Provider→BP one-time contract payment | `PayoutService::endContractAndRelease` |
| Provider→BP milestone payment | `PayoutService::payMilestone` |
| Milestone lifecycle (submit/approve/pay) | `ContractService` + `JobPostingsController` |
| Client→Provider session payout | `PayoutService::releaseServiceSessionPayout` + `ServiceService::completeSession` |
| 🆕 Provider→CS invoice pay | `PayoutService::chargeProviderToCs` + `Provider/FinancesController::payCSInvoice` |
| 🆕 CS invoice CRUD | `CS/InvoicesController` (store/send/void) + routes |
| 🆕 CS engagement contract auto-invoice | `IncidentService::closeWithInvoice` |
| Core destination-charge engine | `PayoutService::chargeProviderToBp` |
| 🆕 BP Stripe Connect Express onboarding | `BP/SettingsController::connectOnboard/connectReturn` (real `accounts->create` + `accountLinks->create`) |
| 🆕 CS Stripe Connect Express onboarding | `CS/SettingsController::connectOnboard/connectReturn` |
| Provider Stripe Connect Express onboarding | `Provider/SettingsController` |
| Webhook `payment_intent.succeeded` | `StripeEventListener::handlePaymentIntentSucceeded` |
| Webhook `payment_intent.payment_failed` | `StripeEventListener::handlePaymentIntentFailed` |
| Webhook `transfer.paid/failed` | `StripeEventListener` |
| 🆕 Webhook `account.updated` | `StripeEventListener::handleAccountUpdated` (auto-flips `stripe_connected`) |
| Demo stub bypass (`cus_demo_*`/`pm_demo_*`/`acct_demo_*`) | `PayoutService`, seeders |
| Activity log + notification fan-out | `PayoutService`, `ActivityService` |
| 🆕 Provider card-save + mirror to `stripe_payment_method_id` | `SettingsController::storePaymentMethod` + `SubscriptionService::setDefaultPaymentMethod` + `OnboardingController::subscribe` |
| 🆕 Native "Add Card" (Stripe Elements) — Provider | `AddCardModal.vue` + `Provider/PaymentMethodSetupController` |
| `bp_payouts` / `cs_payouts` tables | Migrations `2024_01_02_000002/004` |
| 🆕 `cs_invoices.stripe_payment_intent_id/stripe_transfer_id` columns | Migration `2026_07_09_000001` |
| Admin payout oversight service | `AdminPayoutService` |
| Generic platform-transfer release path | `PayoutService::release` |
| 🆕 W-9 soft-warn on CS + BP payment | `payCSInvoice` + `payBPInvoice` |
| 🆕 Dispute system (data + service + admin dashboard) | `DisputeService` + 4 controllers + Vue pages |
| 🆕 Disputed invoice freeze | `InvoiceStatus::Disputed` + payment guards |

### ⚠️ Remaining gaps (Rev 3)

#### Gap 1 — CS + BP `storePaymentMethod` (MEDIUM PRIORITY)
`AddCardModal.vue` component + setup-intent endpoints exist for all 3 portals, but only Provider has a `storePaymentMethod` controller method. CS + BP subscribers can capture cards natively via the modal, but the finalize-and-attach step has no handler yet in their portals. **Workaround:** Stripe Portal (already linked) still works for card management.

**Fix:** Add `storePaymentMethod` to `BP/SettingsController` and `CS/SettingsController`; add `bp.settings.payment.store` and `cs.settings.payment.store` routes; wire the modal's `storeRoute` prop. ~30 min.

#### Gap 2 — Email blade templates for peer-payment events (LOW PRIORITY)
Batch3 wired new event handlers for CS engagement + dispute events. Blade templates for these 7 events don't exist yet (listener no-ops silently). Not blocking — see `AEGIS_BILLING_LIFECYCLE.md` §35 Gap 2 for full list.

#### Gap 3 — Refund Stripe API call (LOW PRIORITY)
`AdminPaymentService::refundPayment()` has `$stripe->refunds->create(...)` commented out for non-dispute refunds; only flips DB status. `DisputeService::issueStripeRefund()` DOES make the real Stripe call for dispute resolutions. **Fix:** uncomment in `AdminPaymentService` for admin-initiated manual refunds outside the dispute flow. ~15 min.

#### Gap 4 — Admin payout oversight UI (LOW PRIORITY)
`AdminPayoutService` + `AdminPaymentService` fully built (ledger, failed payments, pending payouts, retry, refund, manual release). Admin portal Vue pages wired via routes/controllers but end-to-end UI QA not yet done. Admin Disputes dashboard is fresh — has both queue view (`admin/Disputes.vue`) and detail/resolution page (`admin/DisputeDetail.vue`).

#### Gap 5 — W-9 hard-block toggle (BLOCKED ON DR. CHAPMAN)
Current implementation: soft-warn (log warning, allow payment). If Chapman wants hard-block for compliance/audit reasons, `payCSInvoice` and `payBPInvoice` need a check that returns 403 if W-9 unverified. See `AEGIS_CHAPMAN_PENDING_ITEMS.md`.

---

## 8. Demo Mode Behavior ✅

| Value pattern | Effect |
|---|---|
| `stripe_id` starts `cus_demo_` | stub, no real charge |
| `stripe_payment_method_id` starts `pm_demo_` | stub, no real charge |
| `stripe_account_id` starts `acct_demo_` | stub, no real transfer |

In stub mode: `stripe_payment_intent_id = pi_demo_*`, `status = paid`, no Stripe dashboard entry. Also `pi_stub_*` when Stripe secret missing entirely.

Seeded demo users with these values: `p_sarah` (real Stripe sub), `p_rehan` (all fake), `p_david`, `p_maria`, `cs_marcus`, `bp_acme`, `bp_jamal`. Confirmed in `UserSeeder`.

---

## 9. Webhook Events (Peer Payments) ✅

Handled in `StripeEventListener`:

| Event | Handler | Action |
|---|---|---|
| `payment_intent.succeeded` | `handlePaymentIntentSucceeded` | Mark `BpPayout`/`PractitionerPayment` paid, notify |
| `payment_intent.payment_failed` | `handlePaymentIntentFailed` | Mark failed + critical alert |
| `transfer.created` | `handleTransferCreated` | Log |
| `transfer.paid` | `handleTransferPaid` | Mark `BpPayout`/`CsPayout` paid |
| `transfer.failed` | `handleTransferFailed` | Mark failed |
| `charge.refunded` | `handleChargeRefunded` | Log |
| 🆕 `account.updated` | `handleAccountUpdated` | Flips `stripe_connected` when `charges_enabled && payouts_enabled && details_submitted` |

(Subscription webhooks — `invoice.*`, `customer.subscription.*`, `payment_method.*` — documented in `AEGIS_BILLING_LIFECYCLE.md`.)

**🆕 Dashboard events to register (add `account.updated`):**
```
payment_intent.succeeded
payment_intent.payment_failed
transfer.created
transfer.paid
transfer.failed
charge.refunded
account.updated
```

---

## 10. 🆕 CS Engagement Contract Flow

**Rev 3 addition** — models the pre-agreed CS fee as a real contract structure, with auto-invoice on incident close and optional auto-charge.

### Data model — `plan_stewards` (migration `2026_07_10_000001`)
| Column | Type | Purpose |
|---|---|---|
| `fee_cents` | int | Agreed fee per activation (0 = volunteer/invited CS) |
| `payment_terms` | enum | `on_close` (default) / `net_30` / `net_60` |
| `auto_charge` | bool | If true, immediately charge on incident close |
| `engagement_document_id` | UUID | FK to countersigned agreement in `continuity_documents` |

### Lifecycle
```
[ INCIDENT ACTIVE ]
  CS marks each incident_task complete via IncidentService::completeTask
  When all CS tasks complete → maybeReadyForClosure() fires IncidentReadyForClosure event
[ TASKS COMPLETE — VERIFICATION PENDING ]
  Provider (normal) or SS (fallback after 72h) calls IncidentService::verifyClosure
  OR system auto-verifies after CS_INCIDENT_AUTOCLOSE_DAYS (default 7) via IncidentAutoCloseCheckJob
[ VERIFIED ]
  CS calls IncidentService::closeWithInvoice
    → close() re-seals vault + fans out
    → if fee_cents > 0: auto-create CsInvoice (status: sent)
      → if auto_charge=1 AND payment_terms='on_close' AND both parties Stripe-ready:
           immediately fire chargeProviderToCs()
           on success: invoice → paid, write PractitionerPayment + CsPayout
           on failure: invoice remains sent (Provider pays manually via Finances)
      → CsInvoiceAutoGenerated event fires (email Provider)
[ INCIDENT CLOSED — INVOICE PAID OR OUTSTANDING ]
```

### Who can do what
| Action | CS | Provider | SS | System |
|---|---|---|---|---|
| Mark task complete | ✅ | ❌ | ❌ | ❌ |
| Verify closure | ❌ | ✅ | ✅ (after 72h) | ✅ (after 7d) |
| Close incident | ✅ (with invoice) | ✅ (bypass invoice) | ❌ | ✅ (auto-close) |

**Why SS cannot close:** verifying is family/staff-level authority; closing releases money → too much power without professional context.

### Env timing knobs
- `CS_INCIDENT_AUTOCLOSE_DAYS` (default 7) — days after "ready for closure" before system auto-closes
- `CS_INCIDENT_SS_FALLBACK_HOURS` (default 72 — reserved for planned UI copy)

### Events + listener wiring (batch3)
| Event | Registered in `AppServiceProvider` | `SendEmailNotificationListener` handler | Template (⚠️ pending) |
|---|---|---|---|
| `IncidentReadyForClosure` | ✅ | `incidentReadyForClosure` | `emails.incident.30-ready-for-closure` |
| `IncidentClosureVerified` | ✅ | `incidentClosureVerified` | `emails.incident.31-closure-verified` |
| `IncidentAutoClosed` | ✅ | `incidentAutoClosed` | `emails.incident.32-auto-closed` |
| `CsInvoiceAutoGenerated` | ✅ | `csInvoiceAutoGenerated` | `emails.cs.60-auto-invoice-generated` |

### Scheduler entry
`IncidentAutoCloseCheckJob` scheduled hourly in `routes/console.php`. Scans incidents with `[READY_FOR_CLOSURE]` marker in `summary`, no `verified_by_id`, and `updated_at < now - CS_INCIDENT_AUTOCLOSE_DAYS`. Calls `IncidentService::autoClose()`.

---

## 11. 🆕 Dispute System

**Rev 3 addition** — mediates peer-payment disputes without holding funds.

### Data model
- `disputes` — polymorphic subject (`cs_invoice | bp_invoice | bp_payout | session | engagement`), disputer + respondent + reason + amount + status + resolution
- `dispute_messages` — thread (author role: `disputer | respondent | admin`, body, attachment_url)
- Updated `InvoiceStatus` enum + migration to add `disputed` case to `cs_invoices.status` and `bp_invoices.status` ENUMs

### Lifecycle
```
[ open ]   Disputer files → email respondent + admin → status: awaiting_response
              Invoice auto-freezes to InvoiceStatus::Disputed (blocks pay)
[ awaiting_response ]  Respondent has DISPUTE_RESPONDENT_REPLY_DAYS (default 5) to reply
              Respondent posts reply → status: under_review
              Respondent doesn't reply in time → status: under_review anyway
[ under_review ]  Admin reviews thread, decides:
              refund_full → Stripe refund fires; invoice remains disputed
              refund_partial → partial refund fires
              pay_full → dismiss dispute, invoice unfreezes to sent
              pay_partial → partial payment stands, invoice unfreezes
              no_action → dismiss, invoice unfreezes
              stripe_dispute_escalated → beyond our scope; party pursues Stripe chargeback
[ resolved ]  Both parties emailed with outcome + resolution summary
[ closed_no_action ]  Auto-closed DISPUTE_CLOSE_AFTER_RESOLUTION_DAYS (default 7) after resolution
```

### Refund path
`DisputeService::issueStripeRefund()` calls `$stripe->refunds->create()` against the subject's `stripe_payment_intent_id`. Uses `requested_by_customer` reason; metadata includes `aegis_dispute_id`. Skips silently for `pi_demo_*` / `pi_stub_*` prefixes (demo mode). Failures logged; admin can retry.

### What Aegis does NOT do
- ❌ Not a court — if admin resolution unacceptable, party can pursue Stripe chargeback + civil suit
- ❌ Not an escrow — never holds funds; refunds go through Stripe's rails
- ❌ Not lawyers — resolution decisions non-binding on external legal action

### Portal touchpoints
| Portal | Route prefix | Vue pages |
|---|---|---|
| Provider | `provider.disputes.*` | `provider/Disputes.vue` + `provider/DisputeDetail.vue` |
| CS | `cs.disputes.*` | `continuity-steward/Disputes.vue` + `.../DisputeDetail.vue` |
| BP | `bp.disputes.*` | `business-partner/Disputes.vue` + `.../DisputeDetail.vue` |
| Admin | `admin.disputes.*` (queue + resolve) | `admin/Disputes.vue` + `admin/DisputeDetail.vue` |

Reusable `OpenDisputeModal.vue` component exists but not yet wired into Finances tables (see `AEGIS_BILLING_LIFECYCLE.md` §35 Gap 3).

### Env knobs
- `DISPUTE_RESPONDENT_REPLY_DAYS` (default 5)
- `DISPUTE_CLOSE_AFTER_RESOLUTION_DAYS` (default 7 — reserved for planned auto-close job)

### Events + templates (⚠️ pending)
| Event | Template |
|---|---|
| `DisputeOpened` | `emails.disputes.70-opened` |
| `DisputeReplied` | `emails.disputes.71-replied` |
| `DisputeResolved` | `emails.disputes.72-resolved` |

---

## 12. Production Readiness Checklist

**All Rev 2 blockers closed.** Remaining tasks:

- [ ] **Gap 1** — CS + BP `storePaymentMethod` methods (~30 min)
- [ ] **Gap 2** — 7 email blade templates (~2 hr)
- [ ] **Gap 3** — Un-stub non-dispute refund in `AdminPaymentService::refundPayment` (~15 min)
- [ ] **Gap 4** — Admin payout UI end-to-end QA
- [ ] **Gap 5** — W-9 hard-block decision from Dr. Chapman
- [ ] Wire "Open dispute" buttons in Finances tables (~1.5 hr)
- [ ] Register peer-payment events in Stripe dashboard webhook endpoint (now includes `account.updated`)
- [ ] Replace demo seed Stripe values with real test objects for QA
- [ ] `php artisan migrate` after deploying batch3 migrations
- [ ] Ensure cron runs `schedule:run` (adds hourly `IncidentAutoCloseCheckJob`)

---

## 13. Key `.env` Variables

```env
# Stripe (existing)
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# 🆕 CS engagement contract timing
CS_INCIDENT_AUTOCLOSE_DAYS=7
CS_INCIDENT_SS_FALLBACK_HOURS=72

# 🆕 Dispute system timing
DISPUTE_RESPONDENT_REPLY_DAYS=5
DISPUTE_CLOSE_AFTER_RESOLUTION_DAYS=7

# 🆕 Tier limits (see AEGIS_BILLING_LIFECYCLE.md §26)
TIER_ACCESS_MAX_CS=1
TIER_ACCESS_MAX_SS=1
TIER_PRACTICE_MAX_CS=2
TIER_PRACTICE_MAX_SS=4
```

`config('services.stripe.secret')` gates all live charges — if unset, charge methods return stub results instead of throwing. Full env reference in `ENV_REFERENCE.md`.

Local webhook forwarding:
```bash
./stripe listen --forward-to localhost:8000/stripe/webhook
```

---

## 14. DB Tables Reference

| Table | Purpose |
|---|---|
| `bp_payouts` | Every Provider→BP payment: `stripe_payment_intent_id`, `stripe_transfer_id`, `practitioner_id`, `bp_id`, `contract_id`, `milestone_id`, `status`, `released_at` |
| `cs_payouts` | Provider→CS payout records |
| `bp_invoices` / `bp_invoice_line_items` / `bp_invoice_payments` | BP-issued invoices + line items + payment records (🆕 `disputed` status) |
| `cs_invoices` | CS-issued invoices to Providers (🆕 `stripe_payment_intent_id`, `stripe_transfer_id`, `disputed` status) |
| `practitioner_payments` | Provider spend + earnings history (`kind`: subscription / maat_addon / cs_fee / bp_invoice / service_session / refund) |
| `practitioner_payment_methods` | Saved cards per provider (`is_default`, `label`) |
| `bp_milestones` | Milestone lifecycle (`status`: pending/submitted/approved/rejected/paid, `payout_id`) |
| `bp_contracts` | Contract record (`payment_type`: one_time/milestone, `status`: active/completed/cancelled) |
| `bp_tax_documents` | W-9 / 1099 documents per BP (`status`: pending/verified) |
| `service_sessions` | Clinical session records (`amount_cents`, `stripe_payment_intent_id`, `status`) |
| **🆕 `plan_stewards`** | CS/SS designations + 🆕 CS engagement fields (`fee_cents`, `payment_terms`, `auto_charge`, `engagement_document_id`) |
| **🆕 `disputes`** | Dispute records |
| **🆕 `dispute_messages`** | Dispute thread |
| `stripe_webhook_events` | Idempotent webhook log (shared with subscription flow) |

---

## 15. Cross-Reference

| Topic | Document |
|---|---|
| Provider→Aegis subscriptions, tiers, MAAT, onboarding, billing UI, deployment | `AEGIS_BILLING_LIFECYCLE.md` |
| Roles, portals, continuity workflow, schema, integrations | `AEGIS-PROJECT-CONTEXT.md` |
| Peer-to-peer transfers (this doc) | `AEGIS_PAYMENTS_FINANCE.md` |
| Settings UI shared components + per-portal panels | `AEGIS_SETTINGS.md` |
| CS engagement contract full workflow (product-side) | `CONTINUITY_GROUP_CONVERSION_PLAN.md` §0.7 |
| Dispute system design (product-side) | `CONTINUITY_GROUP_CONVERSION_PLAN.md` §0.8 |
| Runtime env constants | `ENV_REFERENCE.md` |
| Pending Dr. Chapman decisions | `AEGIS_CHAPMAN_PENDING_ITEMS.md` |

---

**End of payments & finance reference.**

*Rev 3 — validated against live repo commit `9351e14` on 2026-07-09*
*Rev 2 — commit `6cfabb4` on 2026-07-08*
