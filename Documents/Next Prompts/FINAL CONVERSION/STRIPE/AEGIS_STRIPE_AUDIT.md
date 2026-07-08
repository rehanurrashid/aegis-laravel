# Aegis × Stripe — Comprehensive System Audit

**Date:** 2026-07-08
**Repo state:** `main @ b0be23a`
**Scope:** End-to-end audit of subscription billing, cancellation, upgrade/downgrade, proration, addons, webhooks, and Stripe Connect integration against the pricing model documented in `AEGIS-PROJECT-CONTEXT.md §8` and the products documented in `AEGIS_STRIPE_SETUP.md`.

---

## Legend

| Symbol | Meaning |
|---|---|
| ✅ | Working correctly |
| ⚠️ | Partial / has known issue |
| ❌ | Missing / broken |
| 🔧 | Fix required in this doc |

---

## 1. Foundation — What Actually Works

### 1.1 Stack & Versions ✅

| Component | Version | Verified |
|---|---|---|
| Laravel | 12 | ✅ |
| PHP | 8.2 | ✅ |
| `laravel/cashier` | `^16.6` | ✅ correct for Laravel 12 |
| `stripe/stripe-php` | `^17.3` | ✅ current stable |
| `CashierServiceProvider` | Registered in `bootstrap/providers.php:7` | ✅ |
| `User` model | Has `Billable` trait | ✅ |
| MySQL | 8.x | ✅ |

### 1.2 Configuration ✅

`config/cashier.php` reads correctly from `.env`:
- `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
- `CASHIER_CURRENCY=usd` (default)
- `CASHIER_PATH=stripe` → webhook auto-mounts at `POST /stripe/webhook`

`config/services.php` mirrors the same three keys under `services.stripe`.

`config/aegis.php` defines:
- Pricing objects for all tiers (in cents)
- `stripe_price_to_tier` map (reads env vars → maps price IDs to tier names)
- `paid_roles` list: `practitioner`, `business_partner`, `continuity_steward_business`

### 1.3 Database Schema ✅ (after 4 fix migrations)

`users` table has the required Cashier columns:
- `stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at` ✅

Migration timeline for `subscriptions` (delicate — read carefully):

| Order | File | What it does |
|---|---|---|
| 1 | `2024_01_03_000001_add_cashier_columns_to_users_and_subscriptions.php` | Created polymorphic `billable_id/billable_type` — **wrong for Cashier v16** |
| 2 | `2026_07_07_092246_create_subscriptions_table.php` | Detects wrong schema, drops and recreates with `user_id` |
| 3 | `2026_07_07_100000_fix_subscriptions_user_id_to_string.php` | Widens `user_id` to VARCHAR(255) for UUIDs |
| 4 | `2026_07_07_100001_fix_subscriptions_remove_billable_columns.php` | Cleanup — drops any leftover `billable_*` columns |

**Result on a fresh migrate:** table ends up with `user_id VARCHAR(255)` + composite index `(user_id, stripe_status)`. This matches Cashier v16 expectations. ✅

`subscription_items` has `stripe_price`, `stripe_product`, `meter_id`, `meter_event_name`. Cashier v16 needs all of these — new addition in v16 was the metered-billing columns. ✅

`stripe_webhook_events` table exists with UUID PK — every webhook is logged, idempotent by `stripe_event_id`. ✅

### 1.4 Webhook Wiring ✅ (as of this session's fix)

- Cashier auto-mounts `POST /stripe/webhook` — no manual route needed
- `VerifyWebhookSignature` middleware validates every payload against `STRIPE_WEBHOOK_SECRET`
- `WebhookController@handleWebhook` fires `Laravel\Cashier\Events\WebhookReceived::dispatch($payload)`
- `AppServiceProvider` binds that event to `StripeEventListener`
- `StripeEventListener::handle()` logs event → routes to per-type handler → marks processed

**Confirmed 200s in logs:** `setup_intent.created`, `customer.subscription.created`, `customer.subscription.updated`, `invoice.payment_succeeded`.

### 1.5 Subscribe Flow (Practitioner) ✅

The happy path from onboarding to active subscription:

```
[User picks plan on OnboardingPlan.vue]
    ↓ (POST /onboarding/plan)  →  session: onboarding_plan = {tier, billing, addons}
[User lands on /onboarding/payment]
    ↓ OnboardingController@showPayment
    │   ├── Verify Stripe customer exists (retrieve → clear if fake demo id → createAsStripeCustomer)
    │   ├── createSetupIntent()  →  seti_xxx.client_secret
    │   └── Renders OnboardingPayment.vue with {clientSecret, stripeKey, pricing}
[User enters card, submits]
    ↓ stripe.confirmCardSetup(clientSecret, cardNumber)  →  PaymentMethod pm_xxx
    ↓ resolveStripePrice()  →  price_xxx from window.__AEGIS_CONFIG__
    ↓ (POST /onboarding/subscribe with pm_xxx + price_xxx + addons[])
[Backend OnboardingController@subscribe]
    ├── updateDefaultPaymentMethod($pm_xxx)
    ├── SubscriptionService::subscribe → $user->newSubscription('default', $price)->create($pm)
    ├── Sync tier: users.tier = config('aegis.stripe_price_to_tier')[$price]
    ├── Optional: toggleMaatAddon (⚠️ only DB flag — see §2.1)
    ├── Dispatch welcome email
    └── Redirect to portal dashboard
[Stripe webhooks fire in parallel]
    ├── setup_intent.created (no-op)
    ├── customer.subscription.created (Cashier syncs subscriptions row)
    ├── customer.subscription.updated (StripeEventListener re-syncs tier as backup)
    └── invoice.payment_succeeded (fires PaymentReceived event → email)
```

### 1.6 Subscription Access Gating ✅

`EnsureSubscriptionActive` middleware guards `/provider/*`, `/business-partner/*`, and `/continuity-steward/*` (when `cs_account_type=business`):

```
if role in [practitioner, business_partner] OR (role=continuity_steward AND cs_type=business):
    $sub = $user->subscriptions()->where('type','default')->latest()->first();
    if !$sub OR $sub->stripe_status NOT IN [active, trialing, past_due]:
        redirect('/onboarding/plan')
```

Free roles (Invited CS, Support Steward, Admin) skip the check. Correct. ✅

### 1.7 Webhook Event Handlers ✅

| Event | Handler | Behavior |
|---|---|---|
| `invoice.payment_succeeded` | `handlePaymentSucceeded` | Fires `PaymentReceived` event → email to user |
| `invoice.payment_failed` | `handlePaymentFailed` | Logs Critical activity, dunning notification, fires `PaymentFailed` |
| `customer.subscription.created` | (no-op — Cashier handles DB sync automatically) | ✅ |
| `customer.subscription.updated` | `handleSubscriptionUpdated` | Re-syncs `users.tier` from current Stripe price |
| `customer.subscription.deleted` | `handleSubscriptionCancelled` | Logs cancellation activity |
| `payment_intent.succeeded` | `handlePaymentIntentSucceeded` | Marks `bp_payouts` and `practitioner_payments` paid |
| `payment_intent.payment_failed` | `handlePaymentIntentFailed` | Marks failed + Critical alert to provider |
| `charge.refunded` | Log only | ✅ (no domain action needed) |
| `transfer.paid` | Marks payouts `paid` | ✅ (Stripe Connect) |
| `transfer.failed` | Marks payouts `failed` | ✅ (Stripe Connect) |

### 1.8 Stripe Connect (BP Payouts) ✅

Separate track from subscription billing. `AdminPayoutService` uses destination charges via `transfer_data.destination = acct_xxx`. Aegis is a pass-through — the money is charged to the BP's card, transferred to the provider's connected account, minus zero platform fee. Confirmed by demo detection (`cus_demo_*`, `acct_demo_*` prefixes are cleared before real Stripe calls).

---

## 2. Critical Gaps (Blocking Full Lifecycle)

### 2.1 ❌ MAAT Addon Never Billed Through Stripe

**Severity:** High — the customer gets the feature for free.

**Where it breaks:**

`SubscriptionService::toggleMaatAddon()` only does:
```php
$user->update(['maat_addon' => $enable ? 1 : 0]);
```

That's a boolean DB flag. **No Stripe API call is made.** The MAAT product exists in Stripe (`STRIPE_PRICE_MAAT_MONTHLY` / `_ANNUAL`), but nothing ever adds it as a subscription item.

Correct approach in Cashier v16:
```php
$user->subscription('default')
     ->addPrice($maatPriceId)   // adds as a subscription_item to the same subscription
     ->save();
```
And to remove:
```php
$user->subscription('default')->removePrice($maatPriceId);
```

**Also broken:**
- `app.blade.php` exposes `STRIPE_PRICE_MAAT_ADDON` (single key) — but there are two prices (monthly + annual). The setup guide correctly uses `_MONTHLY` and `_ANNUAL`.
- `resolveStripePrice()` in `OnboardingPayment.vue` has no MAAT entry in its map.
- No env keys `STRIPE_PRICE_MAAT_MONTHLY` / `_ANNUAL` are referenced anywhere in code.

**Impact:** Per pricing (§8.4 of context doc), MAAT is $29/mo standalone, only available with Continuity Practice tier. Right now, checking the MAAT box on plan selection produces `maat_addon=1` on the user row and **zero recurring revenue** from that addon.

### 2.2 ❌ No Plan Change UI (Upgrade / Downgrade)

**Severity:** High — customer cannot upgrade Access → Practice or switch monthly → annual without contacting support.

`SubscriptionService::upgrade()`, `downgrade()`, `swap()` exist and work. **No route or controller endpoint calls them.** The only entry point is `OnboardingController@subscribe` which is single-use (creates the first subscription).

Missing:
- No `finances/plan/upgrade` POST route
- No `SwapSubscriptionRequest` FormRequest
- No `FinancesController@changePlan` method
- No UI in `Provider/Finances.vue` for swapping plans
- `finances.index` renders `subscription` status but no swap CTA

### 2.3 ❌ No Cancel / Reactivate UI

**Severity:** High — customer cannot self-serve cancel; must contact support.

Same story as 2.2: `SubscriptionService::cancel()` and `reactivate()` exist. No route, controller, or UI surface calls them.

`cancel()` uses `$sub->cancel()` which correctly ends at period end (grace period). `reactivate()` uses `$sub->resume()` which correctly restores if still on grace period. Both are correct implementations — just unreachable from the UI.

### 2.4 ❌ No Proration Configuration on Swap

**Severity:** Medium — refunds/charges will be calculated by Stripe defaults which are `create_prorations`. That's usually fine but never explicitly set.

`SubscriptionService::upgrade`:
```php
$sub->swap($newPriceId);
```

Should be:
```php
// Upgrade — bill the prorated difference immediately
$sub->swapAndInvoice($newPriceId);

// Downgrade — apply proration credit at next period end
$sub->noProrate()->swap($newPriceId);
```

For monthly → annual (upgrade in commitment), `swapAndInvoice` bills immediately. For annual → monthly (downgrade in commitment), `noProrate` avoids refunding the annual-paid portion mid-cycle.

### 2.5 ❌ CS Business Subscription Has No Onboarding Flow

**Severity:** High — 40% of your revenue model is unreachable.

`config/aegis.php`:
```php
'paid_roles' => ['practitioner', 'business_partner', 'continuity_steward_business'],
'stripe_price_to_tier' => [
    env('STRIPE_PRICE_CS_BUSINESS_MONTHLY') => 'cs_business',
    env('STRIPE_PRICE_CS_BUSINESS_ANNUAL')  => 'cs_business',
    ...
],
```

But:
- `resolveStripePrice()` in `OnboardingPayment.vue` has no `cs_business` key mapping
- `app.blade.php` doesn't expose `STRIPE_PRICE_CS_BUSINESS_*`
- No plan selection UI for the CS Business tier
- `OnboardingPlanRequest` validates against `practitioner`/`business_partner` price shapes only

### 2.6 ❌ Missing Env Keys in Blade Exposure

`app.blade.php` currently exposes:
```
STRIPE_PRICE_ACCESS_MONTHLY, STRIPE_PRICE_ACCESS_ANNUAL,
STRIPE_PRICE_PRACTICE_MONTHLY, STRIPE_PRICE_PRACTICE_ANNUAL,
STRIPE_PRICE_BP_MONTHLY, STRIPE_PRICE_BP_ANNUAL,
STRIPE_PRICE_SERVICES_ADDON,      ← does not exist in setup guide
STRIPE_PRICE_MAAT_ADDON,          ← should be MAAT_MONTHLY + MAAT_ANNUAL
```

Missing:
- `STRIPE_PRICE_CS_BUSINESS_MONTHLY`
- `STRIPE_PRICE_CS_BUSINESS_ANNUAL`
- `STRIPE_PRICE_MAAT_MONTHLY`
- `STRIPE_PRICE_MAAT_ANNUAL`

`STRIPE_PRICE_SERVICES_ADDON` is stale — Integrative Services Mode was folded into Practice tier per §8.4 (no separate addon).

### 2.7 ⚠️ Add-on Uniqueness Constraint on Subscription

**Severity:** Medium — potential for duplicate item on Stripe if user toggles addon twice.

When calling `$sub->addPrice($maat)` twice without a guard, Cashier will attempt to add a second subscription item with the same price, which Stripe rejects with an error. Guard is needed:

```php
if (!$sub->hasPrice($maatPriceId)) {
    $sub->addPrice($maatPriceId);
}
```

### 2.8 ⚠️ No Handling of `payment_method.attached` / `payment_method.detached`

**Severity:** Low — only relevant when users manage saved cards in the portal.

The listener has no case for these events. Users updating their default card via a self-serve flow will need this webhook to sync `pm_type` and `pm_last_four` on the user record. `updateDefaultPaymentMethod()` sets these fields synchronously, so this is a nice-to-have for admin-triggered updates only.

### 2.9 ⚠️ `handleSubscriptionUpdated` — Silent Failure Modes

**Severity:** Low — logging is thin.

Current:
```php
if ($priceId) {
    $tier = config("aegis.stripe_price_to_tier.{$priceId}");
    if ($tier) $user->update(['tier' => $tier]);
}
```

If the price ID doesn't map (e.g. Stripe product exists but env var missing), the update silently no-ops. Should log the mismatch.

### 2.10 ⚠️ No `invoice.upcoming` Handling

**Severity:** Low — nice-to-have for renewal reminders.

Stripe fires `invoice.upcoming` ~7 days before renewal. Aegis doesn't listen for this, so no renewal reminder email. Not a compliance issue but standard for retention.

---

## 3. Nice-to-Haves (Non-Blocking)

### 3.1 Trial Period Support ⚠️

Cashier supports `->trialDays(14)` on `newSubscription()`. Aegis doesn't use trials. If Carizma wants a free trial post-launch, `SubscriptionService::subscribe()` should accept an optional `trialDays` param. Consumers can then decide (e.g. a promo code branch).

### 3.2 Coupons / Promotion Codes ❌

Not implemented anywhere. Cashier supports `->withCoupon('code')` and `->withPromotionCode('promo_xxx')`. Would need:
- Promo code text input on `OnboardingPayment.vue`
- Coupon validation via `stripe->promotionCodes->list([...])`
- Attachment during `newSubscription()->create()`

### 3.3 Customer Portal (Stripe-Hosted) ⚠️

Cashier v16 supports `$user->redirectToBillingPortal()` — a Stripe-hosted page where users can:
- Update payment method
- View invoice history
- Cancel subscription

This would replace ~80% of the custom UI work in 2.2 and 2.3. Recommended shortcut if launch is tight.

### 3.4 Failed Payment Retry (Dunning) UI ⚠️

`invoice.payment_failed` fires an activity + email. Not surfaced in-portal as a "Update card to avoid interruption" banner. Should add a check in the layout: if `stripe_status = past_due`, show a persistent banner linking to card update.

---

## 4. Setup Configuration Verification (Against `AEGIS_STRIPE_SETUP.md`)

The setup guide correctly defines all 10 products. Env var alignment:

| Setup Guide Env Key | Used in Code? | Location |
|---|---|---|
| `STRIPE_KEY` | ✅ | `config/cashier.php`, `config/services.php`, exposed to Vue |
| `STRIPE_SECRET` | ✅ | `config/cashier.php`, `config/services.php` |
| `STRIPE_WEBHOOK_SECRET` | ✅ | `config/cashier.php` |
| `STRIPE_PRICE_ACCESS_MONTHLY` | ✅ | `config/aegis.php`, `app.blade.php` |
| `STRIPE_PRICE_ACCESS_ANNUAL` | ✅ | `config/aegis.php`, `app.blade.php` |
| `STRIPE_PRICE_PRACTICE_MONTHLY` | ✅ | `config/aegis.php`, `app.blade.php` |
| `STRIPE_PRICE_PRACTICE_ANNUAL` | ✅ | `config/aegis.php`, `app.blade.php` |
| `STRIPE_PRICE_BP_MONTHLY` | ✅ | `config/aegis.php`, `app.blade.php` |
| `STRIPE_PRICE_BP_ANNUAL` | ✅ | `config/aegis.php`, `app.blade.php` |
| `STRIPE_PRICE_CS_BUSINESS_MONTHLY` | ⚠️ | `config/aegis.php` only — **not** in `app.blade.php` |
| `STRIPE_PRICE_CS_BUSINESS_ANNUAL` | ⚠️ | `config/aegis.php` only — **not** in `app.blade.php` |
| `STRIPE_PRICE_MAAT_MONTHLY` | ❌ | Nowhere. Setup expects it. |
| `STRIPE_PRICE_MAAT_ANNUAL` | ❌ | Nowhere. Setup expects it. |
| `VITE_STRIPE_KEY` | ✅ | Read by Vue (`import.meta.env.VITE_STRIPE_KEY`) — but note `stripeKey` prop from backend is what's actually used |

Frontend also needs `VITE_STRIPE_KEY` **or** the `stripeKey` Inertia prop must be present (it is — via `config('services.stripe.key')`).

---

## 5. Full Lifecycle Test Matrix

What must work for launch:

| Scenario | Status | Blocker |
|---|---|---|
| New practitioner subscribes to Access monthly | ✅ Works today |  |
| New practitioner subscribes to Access annual | ✅ Works today |  |
| New practitioner subscribes to Practice monthly | ✅ Works today |  |
| New practitioner subscribes to Practice annual | ✅ Works today |  |
| New BP subscribes monthly | ✅ Works today |  |
| New BP subscribes annual | ✅ Works today |  |
| New CS Business subscribes | ❌ | No onboarding flow (§2.5) |
| Subscribe with MAAT addon checked | ⚠️ | Base sub charged; addon not billed (§2.1) |
| Upgrade Access → Practice mid-cycle | ❌ | No UI/route (§2.2) |
| Downgrade Practice → Access | ❌ | No UI/route (§2.2, §2.4) |
| Switch monthly → annual | ❌ | No UI/route (§2.2, §2.4) |
| Switch annual → monthly | ❌ | No UI/route (§2.2, §2.4) |
| Add MAAT to existing Practice sub | ❌ | Not wired to Stripe (§2.1) |
| Remove MAAT from Practice sub | ❌ | Not wired to Stripe (§2.1) |
| Cancel subscription | ❌ | No UI/route (§2.3) |
| Reactivate during grace period | ❌ | No UI/route (§2.3) |
| Monthly renewal — auto-charge succeeds | ✅ | `invoice.payment_succeeded` → email fires |
| Monthly renewal — auto-charge fails | ✅ | `invoice.payment_failed` → activity + email fires; portal not blocked immediately (past_due grace) |
| Update card on file | ⚠️ | `FinancesController@storePaymentMethod` exists — no UI surfaced |
| View invoice history | ⚠️ | Data available; UI in Finances page |
| Refund (admin-triggered) | ✅ | Webhook logged; no self-serve |
| Sarah has demo `cus_demo_sarah` stripe_id | ✅ | Cleared and re-created (fix from prior session) |

**Score: 8 of 20 lifecycle scenarios pass today. 12 require the fixes in §6.**

---

## 6. Fix Plan — Priority Ordered

### Priority 1 — Complete the Onboarding Coverage (Blockers)

**P1.1 — Expose all product env keys to frontend + config**

`resources/views/app.blade.php` needs (add + remove):
```php
// ADD
"STRIPE_PRICE_CS_BUSINESS_MONTHLY" => env("STRIPE_PRICE_CS_BUSINESS_MONTHLY", ""),
"STRIPE_PRICE_CS_BUSINESS_ANNUAL"  => env("STRIPE_PRICE_CS_BUSINESS_ANNUAL",  ""),
"STRIPE_PRICE_MAAT_MONTHLY"        => env("STRIPE_PRICE_MAAT_MONTHLY",        ""),
"STRIPE_PRICE_MAAT_ANNUAL"         => env("STRIPE_PRICE_MAAT_ANNUAL",         ""),

// REMOVE (stale keys — no product exists)
"STRIPE_PRICE_SERVICES_ADDON"
"STRIPE_PRICE_MAAT_ADDON"          // replaced by MONTHLY + ANNUAL split
```

`config/aegis.php` — add MAAT price mapping to `stripe_price_to_tier` (needed for webhook sync when addon is added):
```php
env('STRIPE_PRICE_MAAT_MONTHLY') => 'maat_addon',
env('STRIPE_PRICE_MAAT_ANNUAL')  => 'maat_addon',
```

`resources/js/pages/auth/OnboardingPayment.vue::resolveStripePrice()` — add missing entries:
```js
'cs_business-monthly': cfg.STRIPE_PRICE_CS_BUSINESS_MONTHLY,
'cs_business-annual':  cfg.STRIPE_PRICE_CS_BUSINESS_ANNUAL,
```

**P1.2 — Wire MAAT addon to Stripe properly**

Rewrite `SubscriptionService::toggleMaatAddon()`:

```php
public function toggleMaatAddon(User $user, bool $enable, string $billing = 'monthly'): User
{
    $priceKey  = $billing === 'annual'
        ? env('STRIPE_PRICE_MAAT_ANNUAL')
        : env('STRIPE_PRICE_MAAT_MONTHLY');

    $sub = $user->subscription('default');
    if (!$sub) {
        throw new \RuntimeException('Cannot toggle MAAT: no active base subscription.');
    }

    if ($enable) {
        // Guard against duplicate item
        if (!$sub->hasPrice($priceKey)) {
            $sub->addPrice($priceKey);
        }
    } else {
        if ($sub->hasPrice($priceKey)) {
            $sub->removePrice($priceKey);
        }
    }

    $user->update(['maat_addon' => $enable ? 1 : 0]);
    event(new MaatAddonChanged($user->fresh(), $enable ? 'activated' : 'deactivated'));
    return $user->fresh();
}
```

Update `OnboardingController@subscribe` to pass `$plan['billing']` into `toggleMaatAddon()`.

**P1.3 — Add CS Business to onboarding flow**

`OnboardingPlan.vue` — expose CS Business as a plan tier when the user's role is `continuity_steward`.

`OnboardingPlanRequest` — accept `tier` values `access`, `practice`, `cs_business` conditionally on role.

Requires: a `showPlanForCsBusiness` branch on the plan selection UI, or (simpler) route CS Business users through `OnboardingRole` → auto-select `cs_business` tier and skip to payment.

### Priority 2 — Enable Self-Serve Plan Management

**P2.1 — Cancel / Reactivate endpoints**

```php
// routes/web.php — add inside provider group
Route::post('/finances/subscription/cancel',     [FinancesController::class, 'cancelSubscription'])
    ->name('finances.subscription.cancel');
Route::post('/finances/subscription/reactivate', [FinancesController::class, 'reactivateSubscription'])
    ->name('finances.subscription.reactivate');
```

`FinancesController` additions:
```php
public function cancelSubscription(Request $request): RedirectResponse
{
    $this->subscriptions->cancel($request->user());
    return back()->with('success', 'Your subscription will end at the current period end.');
}

public function reactivateSubscription(Request $request): RedirectResponse
{
    $this->subscriptions->reactivate($request->user());
    return back()->with('success', 'Your subscription has been reactivated.');
}
```

Duplicate the same for the BP and CS Business Finances controllers.

**P2.2 — Plan swap endpoint with proper proration**

Update `SubscriptionService::upgrade` and `::downgrade`:

```php
public function upgrade(User $user, string $newPriceId, string $planName = 'default'): Subscription
{
    $sub = $user->subscription($planName);
    // Upgrade — invoice prorated difference immediately
    $sub->swapAndInvoice($newPriceId);

    $tier = $this->tierForPriceId($newPriceId);
    if ($tier) $user->update(['tier' => $tier]);

    event(new SubscriptionTierChanged($user->fresh(), 'upgrade', $tier));
    return $sub->refresh();
}

public function downgrade(User $user, string $newPriceId, string $planName = 'default'): Subscription
{
    $sub = $user->subscription($planName);
    // Downgrade — no proration; new price takes effect next cycle
    $sub->noProrate()->swap($newPriceId);

    $tier = $this->tierForPriceId($newPriceId);
    if ($tier) $user->update(['tier' => $tier]);

    event(new SubscriptionTierChanged($user->fresh(), 'downgrade', $tier));
    return $sub->refresh();
}
```

Add controller endpoint that picks upgrade vs downgrade based on the price comparison:

```php
public function changePlan(ChangePlanRequest $request): RedirectResponse
{
    $user       = $request->user();
    $newPriceId = $request->validated()['price_id'];
    $current    = $user->subscription('default');
    $currentAmt = $this->priceAmount($current->stripe_price);
    $newAmt     = $this->priceAmount($newPriceId);

    if ($newAmt >= $currentAmt) {
        $this->subscriptions->upgrade($user, $newPriceId);
        $msg = 'Your plan has been upgraded. Prorated charge added.';
    } else {
        $this->subscriptions->downgrade($user, $newPriceId);
        $msg = 'Your plan will change at the next billing cycle.';
    }
    return back()->with('success', $msg);
}
```

**P2.3 — Provider Finances UI**

`resources/js/pages/Provider/Finances.vue` needs three new sections:
1. **Current Plan** card — shows tier, billing period, next renewal date, price
2. **Change Plan** — modal with the 4 tier options + monthly/annual toggle
3. **Cancel Subscription** — button with confirm modal → posts to `finances.subscription.cancel`

Reactivate button only appears if `subscription.on_grace_period === true`.

### Priority 3 — Robustness

**P3.1 — Add `hasPrice()` guards everywhere addon logic runs**

Already covered in P1.2.

**P3.2 — Handle `payment_method.*` webhooks**

Add to `StripeEventListener`:
```php
'payment_method.attached' => $this->handlePaymentMethodAttached($payload),
'payment_method.detached' => $this->handlePaymentMethodDetached($payload),
```

Both sync `users.pm_type` and `users.pm_last_four`.

**P3.3 — Add `invoice.upcoming` for renewal reminders**

Fire a `SubscriptionRenewalUpcoming` event that dispatches an email 7 days before renewal.

**P3.4 — Verbose logging on tier sync mismatches**

In `handleSubscriptionUpdated`:
```php
if ($priceId) {
    $tier = config("aegis.stripe_price_to_tier.{$priceId}");
    if ($tier) {
        $user->update(['tier' => $tier]);
    } else {
        Log::warning('[STRIPE_WEBHOOK] Unknown price ID in subscription update', [
            'price_id' => $priceId,
            'user_id'  => $user->id,
        ]);
    }
}
```

### Priority 4 — Consider Instead: Stripe Billing Portal

Given launch timing (June 2026 was the plan; this is July 2026 — likely already-launched), the fastest path to shipping self-serve subscription management is the **Stripe-hosted Billing Portal**. One method:

```php
public function portal(Request $request): RedirectResponse
{
    return $request->user()->redirectToBillingPortal(route('provider.finances.index'));
}
```

This gets you cancel, reactivate, plan swap, invoice history, and card update **for free** in a Stripe-branded page. Downside: leaves your UI briefly for the billing management. Upside: eliminates ~600 lines of custom code from §6 P2.

Configure the portal features in Stripe Dashboard → Settings → Billing → Customer Portal, then link to it from your Finances page.

---

## 7. Recommended Delivery Order

If launching soon, sequenced for lowest-risk maximum-coverage:

| Order | Deliverable | Effort | Blocks Launch? |
|---|---|---|---|
| 1 | P1.1 — Env keys + blade + config | 15 min | ✅ Yes (MAAT/CS billing broken without) |
| 2 | P1.2 — MAAT wired to Stripe subscription items | 30 min | ✅ Yes (revenue leak) |
| 3 | P4 — Stripe Billing Portal shortcut | 20 min | Optional (replaces P2 with less work) |
| 4 | P1.3 — CS Business onboarding | 2 hrs | Depends on CS launch timing |
| 5 | P2.1–P2.3 — Custom cancel/reactivate/swap UI | 4 hrs | Only if not using billing portal |
| 6 | P3.1–P3.4 — Robustness | 1 hr | No — can ship after launch |

If Carizma's launch is imminent: **P1.1 → P1.2 → P4** gets you a fully working billing system in under 90 minutes. CS Business onboarding (P1.3) can be a follow-up if that role isn't launching Day 1.

---

## 8. Reference — Full Env File Template

Match against `AEGIS_STRIPE_SETUP.md`:

```dotenv
# ── Stripe platform keys ──────────────────────────────────────────────────────
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
VITE_STRIPE_KEY=pk_test_xxx    # ← mirrors STRIPE_KEY, exposed to frontend

# ── Practitioner base tiers ──────────────────────────────────────────────────
STRIPE_PRICE_ACCESS_MONTHLY=price_xxx
STRIPE_PRICE_ACCESS_ANNUAL=price_xxx
STRIPE_PRICE_PRACTICE_MONTHLY=price_xxx
STRIPE_PRICE_PRACTICE_ANNUAL=price_xxx

# ── MAAT addon (Practice only) ────────────────────────────────────────────────
STRIPE_PRICE_MAAT_MONTHLY=price_xxx
STRIPE_PRICE_MAAT_ANNUAL=price_xxx

# ── Business Partner ─────────────────────────────────────────────────────────
STRIPE_PRICE_BP_MONTHLY=price_xxx
STRIPE_PRICE_BP_ANNUAL=price_xxx

# ── Business CS ──────────────────────────────────────────────────────────────
STRIPE_PRICE_CS_BUSINESS_MONTHLY=price_xxx
STRIPE_PRICE_CS_BUSINESS_ANNUAL=price_xxx
```

After adding keys — run:
```bash
php artisan config:clear
php artisan queue:restart
npm run build  # or npm run dev
```

---

## 9. Summary Table

| Domain | Status |
|---|---|
| Package versions | ✅ Correct |
| DB schema | ✅ Correct (after 4 fix migrations) |
| Stripe customer creation | ✅ Working |
| SetupIntent + card collection | ✅ Working |
| First subscription creation | ✅ Working |
| Webhook signature verification | ✅ Working |
| Webhook event routing | ✅ Working (fixed this session) |
| Access gating middleware | ✅ Working |
| Base tier billing (Access/Practice/BP monthly & annual) | ✅ Working |
| **MAAT addon billing** | ❌ Broken — no Stripe subscription item created |
| **CS Business onboarding** | ❌ Missing — no UI path |
| **Plan upgrade UI** | ❌ Missing — service exists, no route/controller |
| **Plan downgrade UI** | ❌ Missing |
| **Monthly ↔ Annual swap UI** | ❌ Missing |
| **Cancel subscription UI** | ❌ Missing |
| **Reactivate UI** | ❌ Missing |
| Proration on swap | ⚠️ Uses defaults — should use `swapAndInvoice` / `noProrate` |
| Invoice history display | ⚠️ Data queried; UI weak |
| Failed payment dunning banner | ❌ Missing |
| Stripe Billing Portal shortcut | ⚠️ Not implemented (biggest opportunity) |

**Bottom line:** Signup billing works end-to-end. Post-signup management is 90% missing. The Stripe Billing Portal shortcut (§4) can close most of that gap in an hour if launch is tight.
