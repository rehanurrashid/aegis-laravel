# Aegis × Stripe — Lifecycle Fixes (Settings.vue wiring)

**Session:** 2026-07-08
**Approach:** Every subscription/billing action is now wired inside `Settings.vue` — no separate `/finances/billing-portal` route. The Stripe Billing Portal opens via a button in the Billing panel. Everything else (cancel, resume, swap, MAAT toggle, payment methods) is fully custom UI backed by dedicated endpoints.

---

## Files Changed

| File | Change |
|---|---|
| `resources/views/app.blade.php` | Exposes `CS_BUSINESS_MONTHLY/ANNUAL` and `MAAT_MONTHLY/ANNUAL` price IDs; removed stale `SERVICES_ADDON` / `MAAT_ADDON` |
| `config/aegis.php` | Added MAAT prices to `stripe_price_to_tier` map |
| `resources/js/pages/auth/OnboardingPayment.vue` | Added `cs_business-monthly/annual` to `resolveStripePrice()` |
| `app/Services/SubscriptionService.php` | **Full rewrite** — fixed MAAT to add subscription items in Stripe; correct proration (`swapAndInvoice` / `noProrate`); added `changePlan()`, `billingPortalUrl()`, `setDefaultPaymentMethod()`, `removePaymentMethod()`, `getFullSubscriptionData()` (fetches invoice history, payment methods, current period, upcoming invoice — all from live Stripe API) |
| `app/Http/Controllers/Auth/OnboardingController.php` | MAAT toggle passes billing period so correct Stripe price is added at signup |
| `app/Http/Controllers/Provider/SettingsController.php` | Added: `billingPortal`, `swapPlan`, `cancelPlan`, `resumePlan`, `toggleMaat`, `storePaymentMethod`, `setDefaultPaymentMethod`, `removePaymentMethod` |
| `app/Listeners/StripeEventListener.php` | Added handlers for `payment_method.attached/detached`, `invoice.upcoming`; MAAT-item sync on `subscription.updated`; verbose logging on tier mismatches |
| `routes/web.php` | New routes under `provider.settings.*` for all subscription and payment actions |
| `resources/js/pages/provider/Settings.vue` | **`panel-subscription` (billing) and `panel-billing` (invoices) fully wired** — dynamic plan, cycle toggle preview, cancel modal, resume banner, past-due warning, MAAT toggle, live Stripe invoice list with PDF download, live payment methods with default/remove |

---

## What the User Sees Now

### Settings → Subscription & Plan (`panel-subscription`)

- Header badge shows their current tier
- Current-plan band: real price + billing cycle + next invoice date — pulled from Stripe
- **If subscription is cancelled but in grace period** → shows amber banner with reactivate button
- **If `past_due`** → red banner prompting card update
- Monthly/annual toggle — flipping it re-enables the "Switch to X" button on the current tier
- Two plan cards (Access / Practice) with dynamic pricing per the toggle
- Buttons auto-detect: "Upgrade to this plan" / "Switch to this plan" / "Switch to annual" / "Your current plan"
- MAAT addon card — shows current state; "Add MAAT" only enabled if on Practice tier; "Remove MAAT" if active
- Cancel button opens `AegisModal` confirmation with actual period-end date

### Settings → Billing & Invoices (`panel-billing`)

- "Manage in Stripe" button in header → opens Stripe Billing Portal
- Live payment methods from Stripe (brand + last4 + expiry) with Default badge / Set Default / Remove buttons
- Empty state if no card on file → links to billing portal to add one
- Live invoice history from Stripe (last 12) with amount, status, PDF download link
- Empty state before first invoice

---

## Route Map

All under prefix `/provider`:

| Method | Path | Name | Purpose |
|---|---|---|---|
| GET | `/settings/billing-portal` | `provider.settings.billing.portal` | Redirect to Stripe-hosted portal |
| POST | `/settings/subscription/swap` | `provider.settings.subscription.swap` | Upgrade or downgrade (auto-detected) |
| POST | `/settings/subscription/cancel` | `provider.settings.subscription.cancel` | Cancel at period end |
| POST | `/settings/subscription/resume` | `provider.settings.subscription.resume` | Reactivate during grace |
| POST | `/settings/subscription/maat` | `provider.settings.subscription.maat` | Toggle MAAT addon |
| POST | `/settings/payment-method` | `provider.settings.payment.store` | Add card (`pm_xxx` from Stripe Elements) |
| POST | `/settings/payment-method/default` | `provider.settings.payment.default` | Set default card |
| DELETE | `/settings/payment-method` | `provider.settings.payment.remove` | Remove card |

---

## Test Scenarios (After Deploy)

| Scenario | Expected |
|---|---|
| Log in as `p_sarah` (Practice tier), go to Settings → Subscription | Header shows "Continuity Practice"; band shows current price + next invoice date |
| Click "Cancel Plan" | Modal opens with actual period-end date |
| Confirm cancel | Banner appears: "Subscription ending on X — Reactivate" |
| Click "Reactivate" | Banner disappears, back to normal |
| Toggle Monthly ↔ Annual, click "Switch to annual" on Practice | POST to `swap`, `swapAndInvoice()` runs, prorated charge |
| Click "Switch to this plan" on Access (currently on Practice) | POST to `swap`, `noProrate()->swap()`, effective next cycle |
| Click "Add MAAT Service" (on Practice) | POST to `maat`, MAAT price added to subscription, appears on same invoice |
| Click "Remove MAAT" | POST to `maat` with enable=false, MAAT removed |
| Go to Billing & Invoices | Live payment methods, live invoices from Stripe, PDF download links work |
| Click "Manage in Stripe" | Redirects to Stripe-hosted billing portal |

---

## Deploy Checklist

### 1. `.env` — Confirmed live values

Your dev `.env` already has all 10 price IDs — no changes needed.

Production `.env` (currently commented) — uncomment and fill live values when going live:
```
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...   (from live webhook endpoint)
```

### 2. Stripe Dashboard → Settings → Billing → Customer Portal

Configure the portal features you want customers to have access to:
- ✅ Customers can update payment method
- ✅ Customers can update billing address
- ✅ Customers can view invoice history
- ✅ Customers can cancel subscriptions → **"At end of billing period"** (matches grace flow)
- ⚠️ Customers can switch plans → Optional; you already have this in your Settings UI
- Return URL: `https://aegis.devlet.tech/provider/settings?section=billing`

### 3. Webhook — Ensure These Events Are Selected

Add these events if not already in your webhook endpoint config:

```
✓ invoice.payment_succeeded
✓ invoice.payment_failed
✓ invoice.upcoming                ← NEW (renewal reminders)
✓ customer.subscription.created
✓ customer.subscription.updated
✓ customer.subscription.deleted
✓ payment_intent.succeeded
✓ payment_intent.payment_failed
✓ payment_method.attached         ← NEW
✓ payment_method.detached         ← NEW
✓ charge.refunded
✓ transfer.paid
✓ transfer.failed
```

### 4. Redeploy

```bash
git pull
composer install --no-dev
php artisan migrate
php artisan config:clear
php artisan queue:restart
npm ci
npm run build
```

---

## Lifecycle Coverage

| Scenario | Status |
|---|---|
| New subscription (all tiers × monthly/annual) | ✅ |
| Subscribe with MAAT | ✅ Now bills through Stripe |
| Upgrade Access → Practice | ✅ Prorated charge, immediate |
| Downgrade Practice → Access | ✅ Next-cycle, no refund |
| Monthly → Annual swap | ✅ Prorated charge, immediate |
| Annual → Monthly swap | ✅ Next-cycle, no refund |
| Add MAAT to existing sub | ✅ |
| Remove MAAT | ✅ |
| Cancel subscription | ✅ (period-end grace) |
| Reactivate in grace | ✅ |
| Auto-renewal succeed/fail | ✅ Webhook logs + emails |
| Update card on file | ✅ Via billing portal or Stripe Elements |
| View invoices with PDF | ✅ Live from Stripe |
| Set default card | ✅ |
| Remove card | ✅ |
| Renewal reminder (7 days out) | ⚠️ Logged; email template TODO |
| Payment method webhook sync | ✅ |

---

## Still TODO (Non-Blocking)

- **CS Business onboarding UI** — Backend & config are ready. Missing: `cs_business` tier option in `OnboardingPlan.vue`
- **BP + CS Settings** — Same pattern needs applying to their Settings.vue files (backend routes are equivalent — just needs the panel HTML + wire-up)
- **Renewal reminder email** — `invoice.upcoming` webhook fires and logs; needs email template and event dispatch
- **Add Card modal in Billing panel** — Currently the empty state and pmBusy path both link to Stripe Portal; a self-serve add-card modal with Stripe Elements can be added later if wanted

---

## Test Cards (Sandbox)

| Card | Simulates |
|---|---|
| `4242 4242 4242 4242` | Success |
| `4000 0000 0000 0002` | Card declined |
| `4000 0025 0000 3155` | 3D Secure required |
| `4000 0000 0000 9995` | Insufficient funds |

Any future expiry, any CVC, any ZIP.
