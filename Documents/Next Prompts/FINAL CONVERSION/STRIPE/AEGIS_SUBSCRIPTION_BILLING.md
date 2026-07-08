# Aegis — Subscription Billing System

**Last updated:** July 2026
**Scope:** How Aegis charges Providers for platform access. Money flows INTO Aegis/MAAT's Stripe account. Separate from peer-to-peer payments (Provider→BP, Client→Provider) which are covered in `AEGIS_PAYMENTS_FINANCE.md`.

---

## 1. The Core Distinction

There are **two completely separate Stripe integrations** in Aegis:

| System | Package | Direction | Destination | Purpose |
|---|---|---|---|---|
| **Subscription Billing** | Laravel Cashier | Provider → Aegis | MAAT's Stripe account | Platform access fees |
| **Connect Payouts** | Stripe PHP SDK | Provider → BP / Client → Provider | Third-party connected accounts | Peer-to-peer payments |

**This document covers Subscription Billing only.**

Subscription billing uses **Laravel Cashier** (which wraps Stripe Billing). The `STRIPE_KEY` / `STRIPE_SECRET` in `.env` are MAAT's platform Stripe account credentials — all subscription revenue lands here.

---

## 2. Pricing Tiers

Defined in `resources/js/stores/pricing.js` (frontend) and `config/aegis.php` (backend).

| Tier | Monthly | Annual (billed/mo) | Services Mode | Max CS | Max SS |
|---|---|---|---|---|---|
| **Continuity Access** | $29/mo | $23/mo | ❌ | 1 | 2 |
| **Continuity Practice** | $49/mo | $39/mo | ✅ | 2 | 4 |

### Add-ons (on top of any tier)

| Add-on | Price | What it unlocks | Column |
|---|---|---|---|
| Services Mode | +$19/mo | Service listings, discoverability, session booking | `users.services_mode = 1` |
| MAAT Professional CS | +$29/mo | Access to MAAT's own CS staff network | `users.maat_addon = 1` |

### Price ID → Tier mapping

Defined in `config/aegis.php` under `stripe_price_to_tier`. Must be populated with real Stripe price IDs before subscriptions can work:

```php
// config/aegis.php
'stripe_price_to_tier' => [
    env('STRIPE_PRICE_ACCESS_MONTHLY')   => 'access',
    env('STRIPE_PRICE_ACCESS_ANNUAL')    => 'access',
    env('STRIPE_PRICE_PRACTICE_MONTHLY') => 'practice',
    env('STRIPE_PRICE_PRACTICE_ANNUAL')  => 'practice',
],
```

---

## 3. Package: Laravel Cashier

**Installed:** `laravel/cashier: ^15.0` in `composer.json`.

Cashier adds the `Billable` trait to the `User` model, which enables:
- `$user->newSubscription()` — create a subscription
- `$user->subscription()` — get current subscription
- `$user->paymentMethods()` — list saved cards
- `$user->updateDefaultPaymentMethod()` — swap default card
- Built-in webhook handling via `WebhookController`

**Cashier tables created by its own migrations:**
- `subscriptions` — one row per active subscription
- `subscription_items` — price line items per subscription

**User model** has `use Billable` (from `Laravel\Cashier\Billable`) — confirmed in `app/Models/User.php`.

---

## 4. Subscription Flow (How It Works)

```
Provider opens Settings → Billing
    ↓
Enters card details (Stripe.js / Payment Element)
    ↓
Frontend calls Stripe.js → creates PaymentMethod (pm_xxx)
    ↓
Inertia POST → SettingsController::addPaymentMethod()
    ↓
SubscriptionService::addPaymentMethod($user, $pmId)
    → $user->updateDefaultPaymentMethod($pmId)
    → stores stripe_id (cus_xxx) + stripe_payment_method_id on user
    → creates PractitionerPaymentMethod record
    ↓
Provider clicks "Subscribe" / "Upgrade"
    ↓
Inertia POST → SettingsController or FinancesController
    ↓
SubscriptionService::subscribe($user, $stripePriceId, $paymentMethod)
    → $user->newSubscription('default', $priceId)->create($pmId)
    → Stripe charges card → subscription starts
    → Cashier writes to `subscriptions` table
    ↓
Stripe webhook fires → StripeEventListener handles it
    → customer.subscription.updated → syncs tier to users.tier
    → invoice.payment_succeeded → logs to activity feed
    → invoice.payment_failed → critical notification to provider
```

---

## 5. Key Files

### Backend

| File | Role |
|---|---|
| `app/Services/SubscriptionService.php` | All subscription operations — subscribe, upgrade, downgrade, cancel, reactivate, add/remove add-ons |
| `app/Http/Controllers/Provider/FinancesController.php` | Finances page — shows subscription status, payment methods, invoice history |
| `app/Http/Controllers/Provider/SettingsController.php` | Settings → Billing — add card, cancel, autopay toggle |
| `app/Listeners/StripeEventListener.php` | Handles all Stripe webhooks — subscription lifecycle + payment events |
| `config/aegis.php` | `stripe_price_to_tier` map, tier feature flags, pricing config |
| `config/cashier.php` | Cashier config — webhook path, currency, model |
| `app/Models/PractitionerPayment.php` | Record of every charge against a Provider (subscription + add-ons) |
| `app/Models/PractitionerPaymentMethod.php` | Saved cards per provider |

### Frontend

| File | Role |
|---|---|
| `resources/js/pages/provider/Finances.vue` | Billing dashboard — plan summary, payment method, invoice history |
| `resources/js/stores/pricing.js` | Pinia store — tier definitions, pricing, `formatCents()` |

---

## 6. SubscriptionService Methods

| Method | What it does | UC |
|---|---|---|
| `subscribe($user, $priceId, $pmId)` | Create new subscription via Cashier | UC-PRV-003 |
| `upgrade($user, $newPriceId)` | Swap price (Access → Practice), proration applied | UC-PRV-003 |
| `downgrade($user, $newPriceId)` | Swap price (Practice → Access), takes effect next cycle | UC-PRV-004 |
| `cancel($user, $reason)` | Schedule cancellation at period end | UC-PRV-145 |
| `reactivate($user)` | Resume cancelled subscription on grace period | UC-PRV-145 |
| `addServicesMode($user)` | Add +$19/mo add-on, set `services_mode = 1` | UC-PRV-017 |
| `removeServicesMode($user)` | Remove add-on | UC-PRV-017 |
| `addMaatAddon($user)` | Add +$29/mo MAAT CS add-on, set `maat_addon = 1` | UC-PRV-210 |
| `removeMaatAddon($user)` | Remove add-on | UC-PRV-211 |
| `addPaymentMethod($user, $pmId)` | Attach new card, set as default if first | UC-PRV-141 |
| `setAutopay($user, $bool)` | Toggle `user_meta.autopay_enabled` | UC-PRV-144 |
| `getStatus($user)` | Return subscription status, tier, grace period, price_id | — |
| `upgradeCsToBusiness($cs)` | CS tier upgrade — first Stripe charge | UC-CS-114 |

---

## 7. Webhook Events Handled

All webhooks arrive at `POST /stripe/webhook` → routed through Cashier's `WebhookController` → fires `WebhookReceived` event → `StripeEventListener::handle()`.

| Stripe event | Handler | What it does |
|---|---|---|
| `invoice.payment_succeeded` | `handlePaymentSucceeded()` | Logs to activity feed, Cashier syncs subscription |
| `invoice.payment_failed` | `handlePaymentFailed()` | Critical notification to provider: "Update your payment method" |
| `customer.subscription.created` | `handleSubscriptionCreated()` | Cashier handles DB sync |
| `customer.subscription.updated` | `handleSubscriptionUpdated()` | Syncs `users.tier` based on current price ID via `stripe_price_to_tier` map |
| `customer.subscription.deleted` | `handleSubscriptionCancelled()` | Warning notification to user, Cashier marks subscription ended |
| `payment_intent.succeeded` | `handlePaymentIntentSucceeded()` | For peer-to-peer payouts (not subscription) |
| `payment_intent.payment_failed` | `handlePaymentIntentFailed()` | For peer-to-peer payouts (not subscription) |

---

## 8. Database Tables

| Table | Managed by | Purpose |
|---|---|---|
| `subscriptions` | Laravel Cashier | Active subscriptions per user (`stripe_status`, `stripe_price`, `ends_at`) |
| `subscription_items` | Laravel Cashier | Price line items per subscription |
| `practitioner_payments` | Aegis | Record of every charge: kind = `subscription` / `maat_addon` / `cs_fee` / `bp_invoice` / `refund` |
| `practitioner_payment_methods` | Aegis | Saved cards: `stripe_payment_method_id`, `last4`, `brand`, `is_default` |
| `users.stripe_id` | Cashier + Aegis | Stripe Customer ID (`cus_xxx`) — links user to Stripe customer object |
| `users.stripe_payment_method_id` | Aegis | Default card for BP contract payments (separate from subscription card) |
| `users.tier` | Aegis | `access` / `practice` — synced from webhook |
| `users.services_mode` | Aegis | `1` if Services add-on active |
| `users.maat_addon` | Aegis | `1` if MAAT Professional CS add-on active |

---

## 9. Environment Variables

```env
# ── Stripe platform keys (MAAT's account — subscription revenue goes here) ──
STRIPE_KEY=pk_test_51OCuB1Hnj73y5cBf...       # Publishable key (Stripe.js frontend)
STRIPE_SECRET=sk_test_51OCuB1Hnj73y5cBf...    # Secret key (all server-side API calls)
STRIPE_WEBHOOK_SECRET=whsec_3cc3bfc30283...   # Webhook signature verification

# ── Connect (peer-to-peer) ──
STRIPE_CONNECT_CLIENT_ID=ca_xxx               # Used for BP/CS Connect Express onboarding

# ── Subscription Price IDs (create these in Stripe Dashboard → Products) ──
STRIPE_PRICE_ACCESS_MONTHLY=price_xxx         # $29/mo — Continuity Access
STRIPE_PRICE_ACCESS_ANNUAL=price_xxx          # $23/mo billed annually — Continuity Access
STRIPE_PRICE_PRACTICE_MONTHLY=price_xxx       # $49/mo — Continuity Practice
STRIPE_PRICE_PRACTICE_ANNUAL=price_xxx        # $39/mo billed annually — Continuity Practice
STRIPE_PRICE_SERVICES_ADDON=price_xxx         # +$19/mo — Services Mode
STRIPE_PRICE_MAAT_ADDON=price_xxx             # +$29/mo — MAAT Professional CS
```

---

## 10. What's Been Done (Subscription Billing)

| Item | Status |
|---|---|
| `laravel/cashier` installed | ✅ In `composer.json` |
| `config/services.php` — stripe keys configured | ✅ Done in this build |
| `config/cashier.php` — webhook path, currency | ✅ Exists |
| `SubscriptionService` — all methods stubbed | ✅ |
| `StripeEventListener` — subscription webhooks handled | ✅ |
| `Billable` trait on User model | ✅ |
| `stripe_id` column on users | ✅ (Migration 2024_01_02_000004) |
| `FinancesController` — show subscription status + payment methods | ✅ |
| `Finances.vue` — billing dashboard page | ✅ |
| `pricing.js` Pinia store — tier definitions + formatting | ✅ |
| `PractitionerPayment` model + migration | ✅ |
| `PractitionerPaymentMethod` model + migration | ✅ |
| Webhook route `POST /stripe/webhook` | ✅ Via Cashier's WebhookController |
| `StripeWebhookEvent` log model | ✅ |
| Demo seed data (`practitioner_payments` table) | ✅ PayoutSeeder |
| Stripe CLI configured locally (`./stripe login`) | ✅ Authenticated as Maatpracticefirm |
| Local webhook forwarding | ✅ `./stripe listen --forward-to localhost:8000/stripe/webhook` |
| `STRIPE_WEBHOOK_SECRET` in `.env` | ✅ `whsec_3cc3bfc30283...` |

---

## 11. What's NOT Done (Subscription Billing Gaps)

### Gap S1 — Stripe Products + Price IDs not created
The `config/aegis.php` `stripe_price_to_tier` map needs real Stripe price IDs. Currently the map is empty/placeholder — `tier` sync from webhook won't work until these exist.

**Action:** Go to Stripe Dashboard (test mode) → Products → Create:
- Product: "Continuity Access" → Add price: $29/mo recurring + $23/mo annual
- Product: "Continuity Practice" → Add price: $49/mo recurring + $39/mo annual
- Product: "Services Mode Add-on" → Add price: $19/mo
- Product: "MAAT Professional CS" → Add price: $29/mo

Copy each `price_xxx` ID → add to `.env` → update `config/aegis.php`.

### Gap S2 — Settings → Billing card-save UI not fully wired
`FinancesController::storePaymentMethod()` exists and calls `$user->updateDefaultPaymentMethod()`. But the frontend Stripe.js Payment Element that creates the `pm_xxx` token is not integrated — the Finances.vue "Update card" modal is a placeholder.

**What's needed:**
- Load Stripe.js (`@stripe/stripe-js`) in the frontend
- Mount `PaymentElement` in the update card modal
- On submit: call `stripe.confirmSetup()` → get `pm_xxx` → POST to `provider.finances.payment-method`

### Gap S3 — Subscribe/Upgrade/Downgrade UI not wired
`SubscriptionService` methods exist. The "Upgrade to Practice" and "Manage billing" buttons in `Finances.vue` open modals but don't POST to the backend.

**What's needed:**
- `Finances.vue` upgrade modal → POST to `provider.finances.subscribe` with `price_id`
- `SettingsController` or `FinancesController` route → calls `SubscriptionService::subscribe()`
- Route: `POST /provider/finances/subscribe` → `FinancesController::subscribe()`

### Gap S4 — Add-on toggle not wired
"Add Services Mode" and "Add MAAT Professional CS" toggles exist in UI but POST endpoints not confirmed wired.

**What's needed:** Routes + controller methods → `SubscriptionService::addServicesMode()` / `addMaatAddon()`

### Gap S5 — `stripe_price_to_tier` not in `config/aegis.php`
The `StripeEventListener::handleSubscriptionUpdated()` reads `config("aegis.stripe_price_to_tier.{$priceId}")` — if this key doesn't exist in `config/aegis.php`, tier sync silently fails on every subscription update webhook.

**Action:** Verify `config/aegis.php` has the `stripe_price_to_tier` array key, even if populated with placeholder values for now.

### Gap S6 — Subscription webhook events not registered in Stripe Dashboard
The `stripe listen` CLI forwards all events locally. But in production (`aegis.devlet.tech`), the Stripe Dashboard webhook endpoint must explicitly list which events to send.

**Required events to add in Stripe Dashboard → Webhooks → your endpoint:**
```
invoice.payment_succeeded
invoice.payment_failed
customer.subscription.created
customer.subscription.updated
customer.subscription.deleted
payment_intent.succeeded
payment_intent.payment_failed
account.updated
charge.refunded
transfer.paid
transfer.failed
```

---

## 12. How to Set Up Stripe Products (Step by Step)

For sandbox testing:

1. Go to **dashboard.stripe.com** → make sure **Test mode** is on
2. Click **Product catalog** → **+ Add product**
3. Create "Continuity Access":
   - Name: `Continuity Access`
   - Pricing model: Recurring
   - Add price 1: $29.00 / month → copy `price_xxx` → set as `STRIPE_PRICE_ACCESS_MONTHLY`
   - Add price 2: $23.00 / month, billed annually ($276/yr) → copy → `STRIPE_PRICE_ACCESS_ANNUAL`
4. Repeat for "Continuity Practice" ($49/$39)
5. Repeat for add-ons ($19, $29 — recurring monthly only)
6. Update `.env` with all 6 price IDs
7. Update `config/aegis.php`:
```php
'stripe_price_to_tier' => [
    env('STRIPE_PRICE_ACCESS_MONTHLY')   => 'access',
    env('STRIPE_PRICE_ACCESS_ANNUAL')    => 'access',
    env('STRIPE_PRICE_PRACTICE_MONTHLY') => 'practice',
    env('STRIPE_PRICE_PRACTICE_ANNUAL')  => 'practice',
],
```
8. Run `php artisan config:clear`

---

## 13. Production Readiness Checklist (Subscription Billing)

- [ ] **Gap S1** — Create Stripe Products + Price IDs (6 prices total)
- [ ] **Gap S1** — Populate `STRIPE_PRICE_*` vars in `.env` (production + staging)
- [ ] **Gap S1** — Populate `stripe_price_to_tier` in `config/aegis.php`
- [ ] **Gap S2** — Wire Stripe.js Payment Element in Finances.vue card modal
- [ ] **Gap S3** — Wire Subscribe / Upgrade / Downgrade to backend routes
- [ ] **Gap S4** — Wire add-on toggles to `SubscriptionService` routes
- [ ] **Gap S5** — Verify `config/aegis.php` has `stripe_price_to_tier` key
- [ ] **Gap S6** — Register all 11 webhook event types in Stripe Dashboard for production endpoint
- [ ] Test full subscribe flow in Stripe test mode before go-live
- [ ] Confirm `php artisan cashier:publish` has been run (publishes Cashier migrations)
- [ ] Confirm Cashier migrations ran: `subscriptions` + `subscription_items` tables exist

---

## 14. Stripe CLI Reference (Local Development)

```bash
# Authenticate (one-time — already done, expires after 90 days)
./stripe login

# Forward webhooks to local server (keep running during dev/testing)
./stripe listen --forward-to localhost:8000/stripe/webhook

# Trigger a test subscription event manually
./stripe trigger invoice.payment_succeeded
./stripe trigger customer.subscription.updated

# Create a test customer + subscription via CLI
./stripe customers create --email="test@example.com"
./stripe subscriptions create --customer=cus_xxx --items[0][price]=price_xxx
```

---

## 15. Relationship to AEGIS_PAYMENTS_FINANCE.md

| Topic | Covered here | Covered in AEGIS_PAYMENTS_FINANCE.md |
|---|---|---|
| Provider pays Aegis for platform access | ✅ | ❌ |
| Provider pays BP for contract/milestone work | ❌ | ✅ |
| Client pays Provider for service sessions | ❌ | ✅ |
| Provider pays CS invoices | ❌ | ✅ |
| Laravel Cashier / Stripe Billing | ✅ | Brief mention only |
| Stripe Connect Express (peer accounts) | Brief mention only | ✅ |
| Demo stub mode for peer payments | ❌ | ✅ |
| Subscription price IDs + tier mapping | ✅ | ❌ |
