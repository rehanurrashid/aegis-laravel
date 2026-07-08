# Aegis — Payments & Finance System

**Last updated:** July 2026  
**Status:** Partially built. Core BP contract payment flow done. Several integration gaps remain before launch.

---

## 1. Architecture Principle

> **Aegis never holds funds.**

All money moves directly between parties via Stripe Connect. Aegis is the Stripe platform account — it facilitates transfers but its net balance is always $0 on contract/milestone payments.

---

## 2. Money Flows in Aegis

There are four distinct payment flows. Each is independent.

### Flow 1 — Provider Subscription (Aegis Revenue)
```
Provider's card → Stripe Billing → Aegis platform account
```
Providers pay Aegis directly for platform access. This IS Aegis revenue.

| Tier | Monthly | Annual |
|---|---|---|
| Continuity Access | $29/mo | $23/mo |
| Continuity Practice | $49/mo | $39/mo |
| + Services Mode add-on | +$19/mo | — |
| + MAAT Professional CS add-on | +$29/mo | — |

**Status:** ✅ `SubscriptionService` built. Stripe Billing price IDs configured. Webhook sync via `StripeEventListener`.

---

### Flow 2 — Provider → BP Contract/Milestone Payment
```
Provider's saved card (stripe_id + stripe_payment_method_id)
    ↓  PaymentIntent with transfer_data.destination
Aegis Platform ($0 net)
    ↓  Immediate automatic transfer
BP's Stripe Connect Express account (stripe_account_id)
    ↓
BP's bank account (on their payout schedule)
```

This uses Stripe **destination charges** — Provider is charged, 100% routes to BP immediately.

**Status:** ✅ Built and working in demo mode. See section 4 for what's missing for production.

**Key files:**
- `app/Services/PayoutService::chargeProviderToBp()` — core destination charge
- `app/Services/PayoutService::endContractAndRelease()` — one-time contract payment
- `app/Services/PayoutService::payMilestone()` — per-milestone payment
- `app/Http/Controllers/Provider/JobPostingsController::releasePayment()` — route handler
- `app/Http/Controllers/Provider/JobPostingsController::payMilestone()` — route handler
- `app/Listeners/StripeEventListener::handlePaymentIntentSucceeded()` — webhook confirmation
- `app/Listeners/StripeEventListener::handlePaymentIntentFailed()` — webhook failure handler

**DB tables:**
- `bp_payouts` — record per payment (`stripe_payment_intent_id`, `provider_id`, `bp_id`, `status`)
- `bp_milestones` — `status` transitions: pending → submitted → approved → paid
- `bp_contracts` — `payment_type`: `one_time` | `milestone`

**Webhook events needed in Stripe dashboard:**
- `payment_intent.succeeded`
- `payment_intent.payment_failed`

---

### Flow 3 — Provider → CS Invoice Payment
```
Provider's saved card
    ↓  Stripe charge
CS's Stripe Connect account
```

CS creates an invoice, sends it to the Provider. Provider pays it. Funds go direct to CS bank.

**Status:** ⚠️ `InvoiceService` built (create/send/void/refund/markPaid). **The actual Stripe charge on invoice payment is NOT wired** — `recordPayment()` takes a `stripeChargeId` param but nothing calls `PaymentIntents::create()` for CS invoices yet. Currently manual/stub only.

**Key files:**
- `app/Services/InvoiceService` — full CRUD
- `app/Models/CsInvoice`, `app/Models/BpInvoice`
- `database/migrations/*cs_invoices*`, `*bp_invoices*`

---

### Flow 4 — Client → Provider (Services Module)
```
Client's card (via Stripe)
    ↓  PaymentIntent
Provider's Stripe Connect account
```

When a client books a service session through the Services module and confirms completion, funds transfer to the Provider.

**Status:** ✅ `PayoutService::releaseServiceSessionPayout()` built. Uses platform transfer to `practitioner.stripe_account_id`. Triggered from `ServicesController::completeSession()`.

---

## 3. Stripe Objects Per User Role

| Role | Stripe object | Column on `users` | Used for |
|---|---|---|---|
| Provider | Customer | `stripe_id` | Being charged for subscriptions + BP/CS payments |
| Provider | Payment Method | `stripe_payment_method_id` | Default card for contract/milestone payments |
| Provider | Connected Account | `stripe_account_id` | Receiving client payments (Services module) |
| BP | Connected Account | `stripe_account_id` | Receiving contract/milestone payouts |
| CS | Connected Account | `stripe_account_id` | Receiving invoice payments |
| Aegis | Platform account | `STRIPE_SECRET` in `.env` | Facilitating all transfers |

---

## 4. What's Done vs What's Missing

### ✅ Done

| Feature | Where |
|---|---|
| Provider subscription billing (Stripe Billing) | `SubscriptionService` |
| BP contract one-time payment (destination charge) | `PayoutService::endContractAndRelease()` |
| BP milestone payment (destination charge) | `PayoutService::payMilestone()` |
| Milestone CRUD (add/approve/pay/delete) | `JobPostingsController` + `ContractModal.vue` |
| Webhook: `payment_intent.succeeded` → mark paid | `StripeEventListener` |
| Webhook: `payment_intent.payment_failed` → alert provider | `StripeEventListener` |
| Webhook: `transfer.paid/failed` → update payout status | `StripeEventListener` |
| Demo stub mode (acct_demo_* / cus_demo_* bypass) | `PayoutService::chargeProviderToBp()` |
| Activity log + notification fan-out for all payment events | `PayoutService`, `ActivityService` |
| Services module session payout (provider receives) | `PayoutService::releaseServiceSessionPayout()` |
| `bp_payouts` table with `provider_id`, `stripe_payment_intent_id` | Migration 2024_01_02_000002/004 |
| `stripe_id` + `stripe_payment_method_id` on users | Migration 2024_01_02_000004 |

---

### ❌ Not Built / Gaps

#### Gap 1 — Provider Stripe Connect onboarding (Settings → Billing)
Provider needs to save a card (`stripe_id` + `stripe_payment_method_id`) before they can pay BPs. The UI for this in Provider Settings is **not built**.

**What's needed:**
- Settings → Billing tab: add card via Stripe.js / Payment Element
- On save: create Stripe Customer (`customers.create`), attach PaymentMethod, store `stripe_id` + `stripe_payment_method_id` on user
- `ProviderSettingsController` or `ProviderFinancesController` route + handler

**Blocker for:** Flow 2 (BP contract payments) in production.

---

#### Gap 2 — BP Stripe Connect Express onboarding (Payment Setup)
BP needs a real `acct_xxx` connected account. The `PaymentSetupController` exists but only has a stub `connect()` method that manually sets `stripe_connect_account_id` — no real Stripe Express OAuth.

**What's needed:**
- `PayoutService::startConnectOnboarding()` → call `stripe->oauth->authorizeUrl()` or `stripe->accounts->create()` for Express
- Redirect BP to Stripe onboarding URL
- Webhook: `account.updated` → mark `stripe_connected = 1` when onboarding complete
- Show Connect status in BP Payment Setup page

**Blocker for:** Flow 2 (BP receives payment) and Flow 3 (CS receives payment) in production.

---

#### Gap 3 — CS Stripe Connect onboarding
Same as Gap 2 but for CS users. `PayoutService::startConnectOnboarding()` is referenced in `AEGIS_LARAVEL_STRUCTURE.md` as UC-CS-086 but the implementation is a stub.

**Blocker for:** Flow 3 (CS receives invoice payments) in production.

---

#### Gap 4 — CS invoice actual Stripe charge
When Provider pays a CS invoice, `InvoiceService::recordPayment()` is called with a `stripeChargeId` — but nothing creates the actual Stripe `PaymentIntent` first. The charge creation is missing.

**What's needed:**
- New method (or extend `InvoiceService`) to create a `PaymentIntent` with `transfer_data.destination = cs.stripe_account_id` when Provider pays a CS invoice
- Wire to Provider Finances → Invoices → Pay button

**Blocker for:** Flow 3 in production.

---

#### Gap 5 — BP invoice Stripe charge
Same as Gap 4 for BP invoices. BPs create invoices; Providers pay them. The charge creation is not wired.

**Blocker for:** BP invoice payment flow in production.

---

#### Gap 6 — Webhook: `account.updated` for Connect onboarding completion
When a BP or CS completes Stripe Express onboarding, Stripe fires `account.updated`. This is not handled in `StripeEventListener` — `stripe_connected` never gets set to `1` automatically.

**What's needed:**
- `handleAccountUpdated()` in `StripeEventListener`
- Set `stripe_connected = 1` when `charges_enabled = true` and `payouts_enabled = true`

---

#### Gap 7 — Refund flow
`InvoiceService::refund()` exists but doesn't call Stripe Refund API. `AdminPaymentService::refundPayment()` has a commented-out Stripe call.

---

#### Gap 8 — Admin payout oversight UI
`AdminPayoutService` is fully built (list/release/cancel/retry). The Admin portal page wiring these to a UI is not confirmed complete.

---

#### Gap 9 — W-9 / tax document collection from BPs
`BpTaxDocument` model and `TaxDocumentsController` exist. UI is built. But W-9 verification is not connected to payment gating — BPs can receive payments without a verified W-9.

---

## 5. Demo Mode Behavior

When user IDs contain demo stripe values, `chargeProviderToBp()` bypasses all guards and Stripe entirely:

| Value pattern | Behavior |
|---|---|
| `stripe_id` starts with `cus_demo_` | → stub, no real charge |
| `stripe_payment_method_id` starts with `pm_demo_` | → stub, no real charge |
| `stripe_account_id` starts with `acct_demo_` | → stub, no real transfer |

In stub mode: `stripe_payment_intent_id = pi_demo_xxx`, `status = paid`, no Stripe dashboard entry.

Demo users seeded with these values: `p_sarah`, `p_david`, `p_maria` (providers), `bp_acme`, `bp_jamal` (BPs), `bp_team_owner` (agency BP).

---

## 6. Production Readiness Checklist

Before real money can move:

- [ ] **Gap 1** — Provider card save UI (Settings → Billing)
- [ ] **Gap 2** — BP Stripe Connect Express onboarding
- [ ] **Gap 3** — CS Stripe Connect Express onboarding
- [ ] **Gap 4** — CS invoice charge creation
- [ ] **Gap 5** — BP invoice charge creation
- [ ] **Gap 6** — Webhook `account.updated` handler
- [ ] Add `payment_intent.succeeded` + `payment_intent.payment_failed` to Stripe webhook endpoint in dashboard
- [ ] Replace demo seed values with real Stripe test objects for QA
- [ ] `php artisan migrate` after deploying payment field migrations

---

## 7. Key `.env` Variables

```env
STRIPE_KEY=pk_test_...           # Publishable key (frontend Stripe.js)
STRIPE_SECRET=sk_test_...        # Secret key (all server-side API calls)
STRIPE_WEBHOOK_SECRET=whsec_...  # Webhook signature verification
```

Stripe CLI for local webhook forwarding:
```bash
./stripe listen --forward-to localhost:8000/stripe/webhook
```

---

## 8. DB Tables Reference

| Table | Purpose |
|---|---|
| `bp_payouts` | Every Provider→BP payment. Has `stripe_payment_intent_id`, `provider_id`, `milestone_id`, `contract_id` |
| `cs_payouts` | Provider→CS payouts |
| `bp_invoices` | BP-issued invoices to Providers |
| `bp_invoice_payments` | Payment records against BP invoices |
| `cs_invoices` | CS-issued invoices to Providers |
| `practitioner_payments` | Provider subscription + add-on payment history |
| `practitioner_payment_methods` | Saved cards per provider |
| `bp_milestones` | Milestone lifecycle tracking (`status`: pending/submitted/approved/paid) |
| `bp_contracts` | Contract record (`payment_type`: one_time/milestone, `status`: active/completed/cancelled) |

