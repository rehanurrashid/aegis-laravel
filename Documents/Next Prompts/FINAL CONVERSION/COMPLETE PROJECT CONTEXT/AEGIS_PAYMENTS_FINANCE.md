# Aegis — Payments & Finance System (Peer-to-Peer Transfers)

**Status:** Validated against live repo `main @ 6cfabb4` on 2026-07-08
**Companion docs:** `AEGIS_BILLING_LIFECYCLE.md` (Provider→Aegis subscriptions) · `AEGIS-PROJECT-CONTEXT.md` (project overview)

**Scope:** This document covers the **peer-to-peer money flows** — money that moves *between users* (Provider→BP, Client→Provider, Provider→CS), all via Stripe Connect. It is deliberately **separate** from subscription billing (Provider→Aegis), which lives in `AEGIS_BILLING_LIFECYCLE.md`. The two are different Stripe integrations: subscriptions use **Laravel Cashier**; peer transfers use the **Stripe PHP SDK** directly.

> **Naming clarification (important):** In the Provider portal UI, the BP marketplace is labelled **"Support Services"** (route prefix `/provider/support-services`, controller `JobPostingsController`). This is where a practitioner hires Business Partners. **"My Services"** (route prefix `/provider/services`, controller `ServicesController`) is the separate clinical-services module where a practitioner *sells* sessions to clients. Do not confuse either with the Support Steward role or the help-desk `SupportService` (complaints/tickets) — those are unrelated to payments.

---

## 1. Architecture Principle

> **Aegis never holds funds.**

All peer-to-peer money moves directly between parties via **Stripe Connect destination charges**. Aegis is the Stripe *platform* account — it facilitates the transfer but its net balance is always **$0** on contract, milestone, and session payments. Escrow language was removed per attorney direction; funds are never parked in an Aegis-controlled balance.

Two Stripe transfer mechanisms are used:
- **Destination charge** (`transfer_data.destination`) — charge the payer and route 100% to the recipient's connected account in one atomic operation. Used for all contract, milestone, and session payments. This is the preferred, $0-net pattern.
- **Platform transfer** (`transfers->create`) — a separate transfer from the platform balance to a connected account. Used only by the generic `PayoutService::release()` path (admin-driven manual payouts). Requires funds already in the platform balance, so it is a fallback, not the main flow.

---

## 2. The Four Money Flows

Aegis has four distinct payment flows. Each is independent.

| # | Flow | Direction | Mechanism | Covered in |
|---|---|---|---|---|
| 1 | **Provider Subscription** | Provider → Aegis | Cashier (Stripe Billing) | `AEGIS_BILLING_LIFECYCLE.md` |
| 2 | **Provider → BP** (Support Services) | Provider → BP | Destination charge | This doc §3 |
| 3 | **Provider → CS** (invoice) | Provider → CS | Destination charge | This doc §4 |
| 4 | **Client → Provider** (My Services) | Client → Provider | Destination charge | This doc §5 |

Flow 1 is Aegis revenue and lives in the billing doc. Flows 2–4 are peer transfers and are the subject of this document.

---

## 3. Flow 2 — Provider → BP (Support Services / Job Postings)

The Upwork-style marketplace. A practitioner posts a job under **Support Services**, BPs submit proposals, the practitioner accepts one → a contract is created → payment is released either as one lump sum or milestone-by-milestone.

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

### Status: BUILT & working (demo + real-Stripe code paths)

**Workflow (milestone contract):**
1. Provider posts job → `POST /provider/support-services` (`JobPostingsController@store` → `BpJobService`)
2. BP submits proposal → `ProposalService`
3. Provider accepts proposal → contract created (`ContractService::create`, `payment_type = milestone`)
4. Both parties sign → contract `active` (`ContractService::sign`)
5. Provider adds milestones → `POST .../contracts/{contract}/milestones` (`ContractService` / `ContractModal.vue`)
6. BP submits milestone work → `ContractService::submitMilestone` (`status: pending → submitted`)
7. Provider approves → `POST .../milestones/{milestone}/approve` (`ContractService::approveMilestone`, `status: approved`)
8. Provider pays → `POST .../milestones/{milestone}/pay` (`JobPostingsController@payMilestone` → **`PayoutService::payMilestone`** → **`PayoutService::chargeProviderToBp`**)
9. Destination charge fires → `BpPayout` row created (`status` from Stripe) → milestone `status: paid`, `paid_at` set, `payout_id` linked
10. `PayoutReleased` event → `SendEmailNotificationListener` emails BP the `bp/40-payout-released` template

**Workflow (one-time contract):** identical through step 4, then Provider clicks "Release Payment" → `POST .../contracts/{contract}/release-payment` (`JobPostingsController@releasePayment` → **`PayoutService::endContractAndRelease`** → **`chargeProviderToBp`**). Contract → `completed`. (Guarded: blocked if milestones exist — use milestone flow instead.)

**The core charge** — `PayoutService::chargeProviderToBp()`:
- Demo detection: if `stripe_id` starts `cus_demo_`, `stripe_payment_method_id` starts `pm_demo_`, or BP `stripe_account_id` starts `acct_demo_` → returns a stub `pi_demo_*` marked paid, no real Stripe call.
- Guards: throws if Provider has no `stripe_id`/`stripe_payment_method_id`, or if BP's `stripe_account_id` isn't a real `acct_*`.
- No Stripe secret configured → returns `pi_stub_*` pending.
- Live: `paymentIntents->create` with `confirm:true`, `transfer_data.destination`, `on_behalf_of`, metadata. Catches `CardException`, `InvalidRequestException`, `AuthenticationException` with user-facing messages.

**Key files:** `PayoutService::chargeProviderToBp/endContractAndRelease/payMilestone`, `JobPostingsController::releasePayment/payMilestone/approveMilestone`, `ContractService`, `ContractModal.vue`, `StripeEventListener::handlePaymentIntentSucceeded/Failed`.

**DB tables:** `bp_payouts` (`stripe_payment_intent_id`, `provider_id`, `bp_id`, `contract_id`, `milestone_id`, `status`), `bp_milestones` (`status`: pending→submitted→approved→paid), `bp_contracts` (`payment_type`: one_time|milestone).

**Webhook confirmation:** `payment_intent.succeeded` → marks `BpPayout` paid + notifies both parties; `payment_intent.payment_failed` → marks failed + critical alert to Provider.

---

## 4. Flow 3 — Provider → CS (Invoice Payment)

A Continuity Steward creates an invoice for services rendered to a practitioner; the practitioner pays it; funds go direct to the CS's connected account.

### Money path (intended)
```
Provider's saved card
    ↓  PaymentIntent { transfer_data.destination = CS acct }
Aegis Platform ($0 net)
    ↓
CS's Stripe Connect account (users.stripe_account_id)
```

### Status: PARTIALLY built — charge creation NOT wired (GAP)

**What exists:**
- `CsInvoice` / `CsPayout` models + migrations
- `PayoutService::createCsPayout()` — creates a `CsPayout` DB row (no Stripe charge)
- `PayoutService::release()` — generic payout release using a **platform transfer** (`transfers->create`), used by the admin-driven path; requires platform-balance funds
- `PayoutService::getPendingCs()` — lists pending CS payouts

**What's missing (Gap 4):** There is **no Provider→CS destination-charge method** equivalent to `chargeProviderToBp`. When a Provider pays a CS invoice, nothing calls `paymentIntents->create()` with the CS account as destination. `createCsPayout` only writes a DB row; `release()` uses a platform transfer (wrong mechanism for a $0-net peer payment and requires pre-funded balance). No `/provider/.../cs-invoice/pay` route exists.

---

## 5. Flow 4 — Client → Provider (My Services / Clinical Sessions)

The clinical-services module. A practitioner (with Practice tier + `services_mode=1`) lists services; a client requests one; the practitioner accepts and books a session; when the **client confirms completion**, payment transfers to the practitioner.

### Money path
```
Client's saved card (users.stripe_id + users.stripe_payment_method_id)
    ↓  PaymentIntent { confirm:true, transfer_data.destination = Provider acct }
Aegis Platform ($0 net)
    ↓
Provider's Stripe Connect account (users.stripe_account_id)
```

### Status: BUILT & working

**Workflow:**
1. Provider creates service → `POST /provider/services` (`ServiceService::create`) — gated by `services.mode` middleware (Practice tier + `services_mode=1`)
2. Client submits request → `ServiceService::submitRequest` (`ServiceRequest`)
3. Provider accepts → `ServiceService::acceptRequest`
4. Session booked → `ServiceService::bookSession` (`ServiceSession`, `amount_cents` set)
5. **Client confirms completion** → `POST /provider/services/sessions/{session}/complete` (`ServicesController@completeSession` → `ServiceService::completeSession`)
   - Guard: only `session.client_id` may confirm; only `scheduled` sessions
   - Creates a `PractitionerPayment` row (`kind = service_session`, status pending)
   - Calls **`PayoutService::releaseServiceSessionPayout`** → destination charge (client card → provider account)
   - Stamps `stripe_payment_intent_id` back onto the session for webhook lookup
   - Fires `SessionCompleted` event; logs activity + notifications to both parties
6. Webhook `payment_intent.succeeded` confirms the charge

Note: the session-complete route is deliberately **outside** the `services.mode` middleware group — any authenticated provider who booked a session *as a client* can confirm it, even without services mode themselves.

**`PayoutService::releaseServiceSessionPayout`** mirrors `chargeProviderToBp`: demo detection (`cus_demo_`/`pm_demo_`/`acct_demo_`), production guards (client card + provider `acct_*`), stub-pending when no Stripe key, live destination charge with failure handling (marks payment `failed` + critical alert on exception).

**Key files:** `ServiceService::completeSession`, `PayoutService::releaseServiceSessionPayout`, `ServicesController::completeSession`, `StripeEventListener::handlePaymentIntentSucceeded`.

---

## 6. Stripe Objects Per Role

| Role | Stripe object | Column on `users` | Used for |
|---|---|---|---|
| Provider | Customer | `stripe_id` | Charged for subscriptions + BP/CS payments |
| Provider | Payment Method | `stripe_payment_method_id` | Default card for contract/milestone/session payments |
| Provider | Connected Account | `stripe_account_id` | Receiving client payments (My Services) |
| Client (a Provider acting as client) | Customer + PM | `stripe_id` + `stripe_payment_method_id` | Charged for booked sessions |
| BP | Connected Account | `stripe_account_id` (also legacy `stripe_connect_account_id`) | Receiving contract/milestone payouts |
| CS | Connected Account | `stripe_account_id` | Receiving invoice payments |
| Aegis | Platform account | `STRIPE_SECRET` in `.env` | Facilitating all transfers |

> **Column caveat:** `chargeProviderToBp` and `releaseServiceSessionPayout` read `users.stripe_payment_method_id` directly. The BP Payment Setup stub writes `users.stripe_connect_account_id` while the payment code reads `users.stripe_account_id` — verify these are the same column or reconciled before production (see Gap 2).

---

## 7. What's Done vs What's Missing

### Done

| Feature | Where |
|---|---|
| Provider→BP one-time contract payment (destination charge) | `PayoutService::endContractAndRelease` |
| Provider→BP milestone payment (destination charge) | `PayoutService::payMilestone` |
| Milestone lifecycle (submit/approve/pay/CRUD) | `ContractService` + `JobPostingsController` + `ContractModal.vue` |
| Client→Provider session payout (My Services) | `PayoutService::releaseServiceSessionPayout` + `ServiceService::completeSession` |
| Core destination-charge engine with demo/stub/live paths | `PayoutService::chargeProviderToBp` |
| Webhook `payment_intent.succeeded` → mark paid + notify | `StripeEventListener::handlePaymentIntentSucceeded` |
| Webhook `payment_intent.payment_failed` → alert | `StripeEventListener::handlePaymentIntentFailed` |
| Webhook `transfer.paid` / `transfer.failed` → payout status | `StripeEventListener` |
| Demo stub bypass (`cus_demo_*`/`pm_demo_*`/`acct_demo_*`) | `PayoutService`, seeders |
| Activity log + notification fan-out on all payment events | `PayoutService`, `ActivityService` |
| Provider card-save backend (Cashier customer + PM) | `SettingsController::storePaymentMethod` |
| `bp_payouts` / `cs_payouts` tables | Migrations `2024_01_02_000002/004` |
| Admin payout oversight service (list/release/cancel/retry) | `AdminPayoutService` |
| Generic platform-transfer release path | `PayoutService::release` |

### Gaps

#### Gap 1 — Provider card link to peer-payment column (PARTIALLY resolved)
`SettingsController::storePaymentMethod` now creates the Stripe customer and attaches a payment method via Cashier (`updateDefaultPaymentMethod` / `addPaymentMethod`). **However**, it sets the Cashier default but does **not** write `users.stripe_payment_method_id` — the exact column `chargeProviderToBp` / `releaseServiceSessionPayout` read. So subscription billing works after card-save, but a real (non-demo) BP/CS/session charge would still hit the "No payment method on file" guard.
**Fix:** in `storePaymentMethod`, also persist `$user->stripe_payment_method_id = $data['payment_method_id']` (and `stripe_id` if not already set). ~15 min.
**Blocks:** real-money Flows 2 & 4 for non-demo providers.

#### Gap 2 — BP Stripe Connect Express onboarding (STUB)
`BusinessPartner\PaymentSetupController::connect()` just accepts a manually-typed `account_id` and stores it in `users.stripe_connect_account_id`. No real Express OAuth / AccountLink.
**Fix:** implement `PayoutService::startConnectOnboarding()` → `stripe->accountLinks->create()` (or `oauth->authorizeUrl`) for Express, redirect BP to Stripe, and reconcile the stored column name with `stripe_account_id` used by the charge code.
**Blocks:** real-money Flow 2 (BP receives).

#### Gap 3 — CS Stripe Connect Express onboarding (STUB)
Same as Gap 2 for CS. `startConnectOnboarding()` referenced as UC-CS-086 but not implemented.
**Blocks:** real-money Flow 3 (CS receives).

#### Gap 4 — CS invoice actual Stripe charge (NOT WIRED)
No Provider→CS destination-charge method exists. `createCsPayout` only writes a DB row; `release()` uses a platform transfer (wrong mechanism, needs pre-funded balance). No provider-side "pay CS invoice" route.
**Fix:** add `PayoutService::chargeProviderToCs()` mirroring `chargeProviderToBp` (destination = CS `stripe_account_id`), plus a `POST /provider/finances/cs-invoices/{invoice}/pay` route + handler.
**Blocks:** real-money Flow 3.

#### Gap 5 — BP invoice actual Stripe charge (NOT WIRED)
`InvoiceService::recordPayment()` takes a `stripeChargeId` param but nothing creates the `PaymentIntent` first. BPs can create/send invoices (`/business-partner/invoices/{invoice}/send`), but there is no Provider-side pay route that fires a charge.
**Fix:** create a `PaymentIntent` (destination = BP account) when a Provider pays a BP invoice, then call `recordPayment()` with the real charge ID. Add the provider-side pay route.
**Blocks:** BP invoice payment flow (distinct from contract/milestone, which DO work).

#### Gap 6 — Webhook `account.updated` handler (MISSING)
`StripeEventListener` has no `account.updated` case. When a BP/CS finishes Express onboarding, `stripe_connected` never flips to `1` automatically.
**Fix:** add `handleAccountUpdated()` → set `stripe_connected = 1` when `charges_enabled && payouts_enabled`; register `account.updated` in the match block and the Stripe dashboard webhook.
**Blocks:** automated Connect-completion detection for Flows 2, 3, 4.

#### Gap 7 — Refund Stripe API call (COMMENTED OUT)
`AdminPaymentService::refundPayment()` has the `$stripe->refunds->create(...)` call commented out; it only flips DB status. `InvoiceService::refund()` similarly updates status without a Stripe call.
**Fix:** uncomment / implement the `refunds->create` call against `stripe_payment_intent_id`.

#### Gap 8 — Admin payout oversight UI (SERVICE DONE, UI UNCONFIRMED)
`AdminPayoutService` + `AdminPaymentService` are fully built (ledger, failed payments, pending payouts, retry, refund, manual release). The Admin portal Vue pages wiring these to a UI are not confirmed complete.

#### Gap 9 — W-9 / tax-doc gating (NOT ENFORCED)
`TaxDocumentService` (upload/verify/issue1099) + `BpTaxDocument` model + UI exist, but a verified W-9 is **not** a precondition for a BP receiving payment. BPs can be paid without one.
**Fix:** gate `chargeProviderToBp` (or payout release) on `BpTaxDocument` verified status if required for compliance.

---

## 8. Demo Mode Behavior

When user Stripe IDs carry demo prefixes, the charge methods bypass Stripe entirely and stub the result as paid:

| Value pattern | Effect |
|---|---|
| `stripe_id` starts `cus_demo_` | stub, no real charge |
| `stripe_payment_method_id` starts `pm_demo_` | stub, no real charge |
| `stripe_account_id` starts `acct_demo_` | stub, no real transfer |

In stub mode: `stripe_payment_intent_id = pi_demo_*`, `status = paid`, no Stripe dashboard entry. Seeded demo users with these values: `p_sarah`, `p_david`, `p_maria` (providers), `bp_acme`, `bp_jamal`, `bp_team_owner` (BPs). Confirmed in `UserSeeder`, `ServiceSeeder`, `InvoiceSeeder`.

---

## 9. Webhook Events (Peer Payments)

Handled in `StripeEventListener`:

| Event | Handler | Action |
|---|---|---|
| `payment_intent.succeeded` | `handlePaymentIntentSucceeded` | Mark `BpPayout`/`PractitionerPayment` paid, notify both parties |
| `payment_intent.payment_failed` | `handlePaymentIntentFailed` | Mark failed + critical alert to payer |
| `transfer.created` | `handleTransferCreated` | Log |
| `transfer.paid` | `handleTransferPaid` | Mark `BpPayout`/`CsPayout` paid |
| `transfer.failed` | `handleTransferFailed` | Mark payouts failed |
| `charge.refunded` | `handleChargeRefunded` | Log |
| `account.updated` | **MISSING (Gap 6)** | should flip `stripe_connected` |

(Subscription webhooks — `invoice.*`, `customer.subscription.*`, `payment_method.*` — are documented in `AEGIS_BILLING_LIFECYCLE.md`.)

**Dashboard events to register for peer payments:** `payment_intent.succeeded`, `payment_intent.payment_failed`, `transfer.created`, `transfer.paid`, `transfer.failed`, `charge.refunded`, and (once Gap 6 is built) `account.updated`.

---

## 10. Production Readiness Checklist

Before real peer money can move:

- [ ] **Gap 1** — Persist `stripe_payment_method_id` in `storePaymentMethod` (link card-save to peer-charge column)
- [ ] **Gap 2** — BP Stripe Connect Express onboarding (real AccountLink/OAuth) + column reconciliation
- [ ] **Gap 3** — CS Stripe Connect Express onboarding
- [ ] **Gap 4** — `chargeProviderToCs()` + CS invoice pay route
- [ ] **Gap 5** — BP invoice charge creation + Provider pay route
- [ ] **Gap 6** — `account.updated` webhook handler → auto-set `stripe_connected`
- [ ] **Gap 7** — Un-stub refund (`refunds->create`)
- [ ] **Gap 8** — Confirm Admin payout oversight UI wiring
- [ ] **Gap 9** — Decide whether W-9 gates BP payouts; enforce if so
- [ ] Register peer-payment events in the Stripe dashboard webhook endpoint
- [ ] Replace demo seed Stripe values with real test objects for QA
- [ ] `php artisan migrate` after deploying payment-field migrations

---

## 11. Key `.env` Variables

```env
STRIPE_KEY=pk_test_...           # Publishable key (frontend Stripe.js)
STRIPE_SECRET=sk_test_...        # Secret key (all server-side API calls)
STRIPE_WEBHOOK_SECRET=whsec_...  # Webhook signature verification
```
`config('services.stripe.secret')` gates all live charges — if unset, the charge methods return stub/pending results instead of throwing.

Local webhook forwarding:
```bash
./stripe listen --forward-to localhost:8000/stripe/webhook
```

---

## 12. DB Tables Reference

| Table | Purpose |
|---|---|
| `bp_payouts` | Every Provider→BP payment. `stripe_payment_intent_id`, `stripe_transfer_id`, `provider_id`, `bp_id`, `contract_id`, `milestone_id`, `status`, `released_at` |
| `cs_payouts` | Provider→CS payout records |
| `bp_invoices` / `bp_invoice_line_items` / `bp_invoice_payments` | BP-issued invoices to Providers + line items + payment records |
| `cs_invoices` | CS-issued invoices to Providers |
| `practitioner_payments` | Provider subscription + add-on + **service-session** payment history (`kind`: subscription/maat_addon/cs_fee/bp_invoice/service_session/refund) |
| `practitioner_payment_methods` | Saved cards per provider (`is_default`, `label`) |
| `bp_milestones` | Milestone lifecycle (`status`: pending/submitted/approved/paid, `payout_id`) |
| `bp_contracts` | Contract record (`payment_type`: one_time/milestone, `status`: active/completed/cancelled) |
| `bp_tax_documents` | W-9 / 1099 documents per BP (`status`: pending/verified) |
| `service_sessions` | Clinical session records (`amount_cents`, `stripe_payment_intent_id`, `status`) |
| `stripe_webhook_events` | Idempotent webhook log (shared with subscription flow) |

---

## 13. Cross-Reference

| Topic | Document |
|---|---|
| Provider→Aegis subscriptions, tiers, MAAT, onboarding, billing UI | `AEGIS_BILLING_LIFECYCLE.md` |
| Roles, portals, continuity workflow, schema, integrations | `AEGIS-PROJECT-CONTEXT.md` |
| Peer-to-peer transfers (this doc) | `AEGIS_PAYMENTS_FINANCE.md` |

---

**End of payments & finance reference.**
