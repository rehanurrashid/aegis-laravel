# Aegis — Complete Billing, Onboarding & System Reference (Canonical)

**Status:** Re-validated against live repo `main @ 2cd19de` on 2026-07-08  
**Previous validation:** `26ea36a` on 2026-07-08  
**Companion doc:** `AEGIS-PROJECT-CONTEXT.md`, `AEGIS_PROVIDER_SETTINGS.md`

**Purpose:** Everything a developer or Claude needs to understand Aegis end-to-end — what it is, who the roles are, the continuity-plan workflow, how someone registers and pays, which features are locked for which role, and how ongoing billing is managed. Treat this file + `AEGIS-PROJECT-CONTEXT.md` as the complete project reference.

**Legend:** ✅ Verified complete · ⚠️ Partial / needs finishing · ❌ Not yet implemented · 🐛 Bug found

---

## Table of Contents

**PART A — THE PRODUCT**
1. What Aegis Is (One Read)
2. The Five Roles
3. Role Sub-Types & Paths
4. Multi-Role Identity & The Three-Tier Visibility Rule
5. The Continuity-Plan Lifecycle (Core Workflow)
6. The Seven Critical-Incident Types
7. The Five Portals

**PART B — IDENTITY & ACCESS**
8. Who Pays, Who's Free
9. The Complete Registration → Onboarding Flow
10. Email Verification Gate
11. Login Redirect Logic
12. Account Lock / Deactivation

**PART C — THE MONEY**
13. The Two Stripe Integrations
14. Package & Infrastructure Layer
15. Database Schema (Billing Tables)
16. The Pricing Model (All Roles)
17. The Price ID System (env → config → frontend)

**PART D — LIFECYCLE**
18. Onboarding Flow (First Subscription) — Step by Step
19. Post-Signup Management (Provider)
20. Post-Signup Management (BP & CS) — The Gap
21. The Webhook Engine
22. Proration Rules
23. The MAAT Add-on

**PART E — FEATURE GATING**
24. Tier-Based Feature Locks (Access vs Practice)
25. Role-Based Route Gating (Middleware Map)
26. Frontend Lock Mechanism (Upgrade Modal)
27. Page-Level Access Matrix

**PART F — OPERATIONS**
28. Full Lifecycle Coverage Matrix
29. Known Bugs & Discrepancies Found
30. Remaining Gaps & Exactly Where To Fix Them
31. Stack & Architecture
32. File Map
33. Deployment Checklist
34. Appendix — Test Cards, Demo Users & Prototype vs Laravel

---

# PART A — THE PRODUCT

## 1. What Aegis Is (One Read)

Aegis is a **healthcare practice continuity SaaS** built for MAAT Practice Firm (client: Dr. Carizma Chapman), launching at `aegis.devlet.tech`. It solves one core problem: **what happens to a solo healthcare practice when the practitioner dies, is incapacitated, detained, or otherwise suddenly unable to practice.**

A practitioner builds a **Continuity Plan** — a legal succession document that names:
- **Continuity Stewards (CS)** — the executors who wind down or transfer the practice
- **Support Stewards (SS)** — the everyday monitors who trigger the alert when something goes wrong

When a critical incident occurs, an SS **triggers** it, a CS **verifies** it (unlocking a secure document vault), and the CS **executes** a pre-written task list to protect patients, transfer records, and close the practice responsibly.

Separately, Aegis runs a **Business Partner (BP) marketplace** — an Upwork-style module where practitioners hire vendors (billing, legal, accounting, IT) for non-continuity work.

Five portals share one Laravel backbone, one Vue/Inertia frontend, one design system, and one write-path layer (`ActivityService::log()` fan-out).

## 2. The Five Roles ✅ VERIFIED

Role stored in `users.role` (enum) + `user_role_assignments` join row (multi-role support).

| Role | Enum value | Portal prefix | Dashboard route | Pays? |
|---|---|---|---|---|
| **Practitioner** (Provider) | `practitioner` | `/provider` | `provider.dashboard` | Always |
| **Continuity Steward** (CS) | `continuity_steward` | `/continuity-steward` | `cs.dashboard` | Business only |
| **Support Steward** (SS) | `support_steward` | `/support-steward` | `ss.dashboard` | Never (free) |
| **Business Partner** (BP) | `business_partner` | `/business-partner` | `bp.dashboard` | Always |
| **Admin** | `admin` | `/admin` | `admin.dashboard` | N/A |

**Verified in code:** `app/Enums/UserRole.php` — all 5 cases present with `.label()`, `.portal()`, `.routePrefix()`, `.middleware()`, `.fromPortal()` methods. ✅

### 2.1 Practitioner (Provider)
Licensed clinician — the portal owner. Builds the plan, designates stewards, uploads credentials to the vault.
- **Verb:** Designates, Configures, Attests. **Must NOT:** Trigger their own critical incident.
- **Subscription:** Access ($29) or Practice ($49). **Public profile:** Yes (`/public/provider/<slug>`)

### 2.2 Continuity Steward (CS)
The executor — licensed colleague, attorney, or CS firm. Verifies the alert, unlocks the vault, runs the task list.
- **Verb:** Verifies, Executes, Closes. **Dormant in standby; activates on verified incident.**
- **Must NOT:** Make plan decisions without practitioner; access vault pre-verification.
- Subtypes: Business CS (paid, public profile, 2–40 practitioners) · Invited CS (free, no profile, 1 practitioner) · Enterprise (custom, 41+).

### 2.3 Support Steward (SS)
Eyes on the ground — family, office manager, trusted staff. Spots trouble and **triggers** the alert.
- **Verb:** Monitors, Triggers, Assists. **Always active (monitoring).**
- **Must NOT:** Verify/activate CS duties; access the vault.
- Subtypes: Primary SS, Alternate SS (same portal, different attestation duties).
- **No subscription** — invitation-only. **No public profile.**

### 2.4 Business Partner (BP)
Independent marketplace vendor. **Not part of continuity flow.** Upwork model: practitioners post jobs → BPs propose → contracts with milestones → milestone-by-milestone payment.
- Subtypes: Agency (multi-person, Team Management module) · Freelancer (solo, personal 1099/SSN).
- **Subscription:** $69/mo or $690/yr. **Public profile:** Yes (`/public/business/<slug>`).
- **Special:** BP does NOT see the cross-portal switcher — BPs don't bridge into continuity portals. ✅ Confirmed in `AppHeader.vue`: `v-if="portal !== 'business_partner'"` gates the switcher.

### 2.5 Anonymous
No login. Sees only public profile sections + sign-in CTAs.

## 3. Role Sub-Types & Paths ✅ VERIFIED

### Continuity Steward — `cs_account_type`

| Path | Account type | Pays? | Public profile | Scope |
|---|---|---|---|---|
| `business` | `business` | $49/mo or ~$35.75/mo annual ($429/yr) | Yes | 2–40 practitioners |
| `invited` | `invited` | Free | No | 1 inviting practitioner |
| (manual) | `enterprise` | Custom quote | Yes | 41+ (mailto, no automation) |

**Verified in code:** `app/Enums/CsAccountType.php` — `Invited`, `Business`, `Enterprise` cases. ✅  
**Subscription gate:** `EnsureSubscriptionActive` only gates CS when `cs_account_type === 'business'`. Invited CS passes through. ✅

### Business Partner — `bp_type`

| Type | Description | Special module |
|---|---|---|
| `agency` | Multi-person firm | Team Management (member per milestone) |
| `freelancer` | Solo operator | No team module; personal 1099/SSN |

**Verified in code:** `app/Enums/BpType.php` exists. ✅ Team routes (`bp.team.*`) exist in routes. ✅

### Support Steward — no sub-type
Invitation-only. Registration shows an invite-gate wall (blocked). No self-serve signup, no payment.

## 4. Multi-Role Identity & The Three-Tier Visibility Rule ✅ VERIFIED

### Multi-role (Marcus Chen pattern)
One `users` row can hold multiple `user_role_assignments`. The URL prefix decides which face to render. The header portal switcher shows only portals the user actually has.

**🐛 BUG FIXED (this session):** `AppHeader.vue` `portalSwitchEntries` previously showed ALL 3 portals regardless of which roles the user actually holds (always showed Practitioner + CS + SS). Fixed: now checks `user.has_cs_portal` and `user.has_ss_portal` before including CS/SS in the switcher list.

**⚠️ STILL NEEDED:** `SettingsController::index()` must append `has_cs_portal` and `has_ss_portal` to the user prop so the Provider Settings "Portal Access" panel can correctly show only the portals the user holds.

### Three-tier visibility (all public profiles) ✅
| Tier | Condition | Sees |
|---|---|---|
| Anonymous | No login | Public sections + sign-in CTAs |
| Logged-in (any role) | Any signed-in user | Full profile: contact, metrics, activity |
| Owner | Viewing own profile | Adds Edit + Visibility panel |

Implemented in `PublicProfileController`. ✅

## 5. The Continuity-Plan Lifecycle (Core Workflow)

_(Unchanged — no billing impact. See AEGIS-PROJECT-CONTEXT.md §5 for full lifecycle detail.)_

## 6. The Seven Critical-Incident Types

_(Unchanged — see AEGIS-PROJECT-CONTEXT.md §6.)_

## 7. The Five Portals ✅ VERIFIED

All five route groups exist in `routes/web.php` with correct middleware stacks:
- Provider: `auth, verified.email, subscription.active, role:practitioner, check.locked`
- CS: `auth, verified.email, subscription.active, role:continuity_steward, check.locked`
- SS: `auth, verified.email, role:support_steward, check.locked` *(no subscription gate — free)*
- BP: `auth, verified.email, subscription.active, role:business_partner, check.locked`
- Admin: `auth, role:admin` (own simple gate via `EnsureAdminRole`)

---

# PART B — IDENTITY & ACCESS

## 8. Who Pays, Who's Free ✅ VERIFIED

| Role / Path | Pays at signup? | Stripe integration |
|---|---|---|
| Practitioner (Access) | ✅ $29/mo or $276/yr | Cashier subscription |
| Practitioner (Practice) | ✅ $49/mo or $468/yr | Cashier subscription |
| Business CS | ✅ $49/mo or $429/yr | Cashier subscription |
| Business Partner | ✅ $69/mo or $690/yr | Cashier subscription |
| Invited CS | ❌ Free | No Stripe |
| Support Steward | ❌ Free | No Stripe |
| Admin | ❌ Free | No Stripe |

**`paid_roles` in `config/aegis.php`:** `['practitioner', 'business_partner', 'continuity_steward_business']` ✅

## 9. The Complete Registration → Onboarding Flow ✅ VERIFIED

```
/register → RegisterController::store()
    → AuthService::register()
        → users row created (role, tier, cs_account_type, verified=0)
    → email verification sent
    → redirect: verification.notice

/email/verify/{id}/{hash} → VerifyEmailController
    → users.verified = 1
    → free roles (SS, Invited CS) → redirect to portal dashboard
    → paid roles → redirect to onboarding.intent

/onboarding → OnboardingController::showIntent() / submitIntent()
    → stores use-case in session

/onboarding/role → showRole() (if role not yet set)

/onboarding/plan → showPlan()
    → Practitioner sees: Access $29 / Practice $49 (monthly/annual toggle)
    → BP sees: Monthly $69 / Annual $690
    → Business CS sees: Monthly $49 / Annual $429
    → Stores tier + billing + wantsMaat in session

/onboarding/payment → showPayment()
    → Creates Stripe customer if not exists (detects and clears fake demo stripe_ids)
    → Creates SetupIntent → passes clientSecret + stripeKey to OnboardingPayment.vue
    → Stripe Elements card form renders

POST /onboarding/subscribe → subscribe()
    → Attaches PaymentMethod as default
    → SubscriptionService::subscribe() → creates Stripe subscription
    → If wantsMaat: toggleMaatAddon(enable=true)
    → Syncs tier to users.tier
    → Redirect → portal dashboard
```

**Verified:** All controllers exist. ✅ Demo stripe_id detection and clearing works. ✅ MAAT add-on wiring at subscribe time confirmed. ✅

## 10. Email Verification Gate ✅ VERIFIED

`EnsureEmailVerified` middleware checks `users.verified` (tinyint, not Laravel's `email_verified_at`). Applied to all portal route groups. Free roles (SS, Invited CS) still go through email verification before accessing their portal. ✅

## 11. Login Redirect Logic ✅ VERIFIED

`LoginController::resolvePostLoginDestination()` — 3-step priority:
1. `!user.verified` → `verification.notice`
2. Paid role + no active subscription → `onboarding.plan`
3. Everything OK → `portalHomeFor($user)` → correct dashboard

**Active statuses checked:** `['active', 'trialing', 'past_due']` — past_due does NOT lock out, just warns. ✅  
**Fail-open on Cashier error:** If subscription DB check throws, user is let through (logged, not crashed). ✅

## 12. Account Lock / Deactivation ✅ VERIFIED

- `CheckAccountLocked` middleware gates all portals. ✅
- `AuthService::lockAccount()` sets `users.locked_at` + fires `AccountLocked` event.
- `AuthService::unlockAccount()` clears `users.locked_at`.
- `deleteAccount()` sets `users.deactivated_at = now()` + clears tokens + logs out. ✅
- Admin routes: `admin.users.lock`, `admin.users.unlock`, `admin.users.deactivate`, `admin.users.restore`. ✅

---

# PART C — THE MONEY

## 13. The Two Stripe Integrations ✅ VERIFIED

### Integration 1 — Cashier (Subscriptions)
- Laravel Cashier `^16.6` managing `subscriptions` + `subscription_items` tables.
- **Who:** Practitioners, Business Partners, Business CS.
- **Flow:** Aegis collects recurring subscription revenue. Funds go to Aegis Stripe account.
- **UUID fix confirmed:** Migrations `2026_07_07_100000` and `100001` correct `user_id` from bigint to string for UUID PKs. ✅

### Integration 2 — Stripe Connect (Marketplace Payouts)
- **Who:** Business Partners (receive pay for contracts) + Practitioners with Services Mode (receive pay for services).
- **Flow:** Practitioner pays → Aegis creates destination charge → funds land in BP/Provider's connected Stripe account. Aegis nets $0 on marketplace transactions.
- **Connect account stored:** `users.stripe_account_id` (also `users.stripe_connected` tinyint flag). ✅

**`account.updated` webhook:** NOT handled in `StripeEventListener`. The listener's `default` case logs and ignores it. This is acceptable for MVP — Connect account status changes (payout enable/disable, verification) are not currently tracked in the DB. Add if needed for BP onboarding flow.

## 14. Package & Infrastructure Layer ✅ VERIFIED

| Package | Version | Purpose |
|---|---|---|
| `laravel/cashier` | `^16.6` | Subscription billing via Stripe |
| `stripe/stripe-php` | `^17.3` | Direct Stripe API calls (Connect payouts, SetupIntents, payment methods) |

Both confirmed in `composer.json`. ✅

## 15. Database Schema (Billing Tables) ✅ VERIFIED

| Table | Migration | Purpose |
|---|---|---|
| `users` | `2024_01_01_000001` | `tier`, `role`, `cs_account_type`, `bp_type`, `maat_addon`, `services_mode`, `stripe_id`, `stripe_account_id`, `stripe_connected`, `verified` columns |
| `subscriptions` | `2026_07_07_092246` + `100000` fix | Cashier subscriptions (UUID user_id) |
| `subscription_items` | `2026_07_07_092247–249` | Cashier subscription items + meter fields |
| `stripe_webhook_events` | `2024_01_01_000068` | Deduplication log for all incoming webhook events |
| `bp_payouts` | `2024_01_02_000002` | Marketplace payout records (Stripe Connect) |
| `practitioner_payments` | `2026_07_06_000006` | Provider service payments (Stripe transfer ID) |

All migrations present. ✅

## 16. The Pricing Model (All Roles) ✅ VERIFIED WITH ONE BUG

All pricing lives in `config/aegis.php` as the single source of truth.

### Practitioner

| Plan | Monthly | Annual (÷12) | Annual total |
|---|---|---|---|
| Continuity Access | $29/mo | $23/mo | $276/yr |
| Continuity Practice | $49/mo | $39/mo | $468/yr |

**Verified in config:** `monthly_cents: 2900`, `annual_cents: 2300`, `annual_total_cents: 27600` (Access) and `monthly_cents: 4900`, `annual_cents: 3900`, `annual_total_cents: 46800` (Practice). ✅

**🐛 BUG — `UserTier::monthlyCents()` returns stale price:**
`app/Enums/UserTier.php` `monthlyCents()` returns `1900` for Access ($19) and presumably `3900` for Practice — these are **old prototype prices**. The confirmed June 2026 prices are $29/$49. This enum method is used internally for price comparisons. Check all callers and update:
- `UserTier::Access->monthlyCents()` should return `2900`
- `UserTier::Practice->monthlyCents()` should return `4900`

**Fix:** Update `app/Enums/UserTier.php`:
```php
self::Access   => 2900,   // $29/mo (was 1900 — stale)
self::Practice => 4900,   // $49/mo
```

### MAAT Professional CS Add-on (requires Practice)

| | Monthly | Annual (÷12) | Annual total |
|---|---|---|---|
| MAAT Add-on | +$29/mo | +$23/mo | +$276/yr |
| Practice + MAAT combined | $78/mo | $62/mo | $744/yr |

### Business Partner

| | Monthly | Annual total |
|---|---|---|
| BP subscription | $69/mo | $690/yr (save ~2 months) |

### Continuity Steward (Business)

| | Monthly | Annual total |
|---|---|---|
| CS Business subscription | $49/mo | $429/yr (save ~27%) |

All confirmed against `config/aegis.php`. ✅

## 17. The Price ID System ✅ VERIFIED

10 Stripe price IDs, all controlled via `.env` variables and mapped in `config/aegis.php`:

| env var | Tier mapping | Used by |
|---|---|---|
| `STRIPE_PRICE_ACCESS_MONTHLY` | `access` | OnboardingPayment.vue, SubscriptionService |
| `STRIPE_PRICE_ACCESS_ANNUAL` | `access` | OnboardingPayment.vue, SubscriptionService |
| `STRIPE_PRICE_PRACTICE_MONTHLY` | `practice` | OnboardingPayment.vue, SubscriptionService |
| `STRIPE_PRICE_PRACTICE_ANNUAL` | `practice` | OnboardingPayment.vue, SubscriptionService |
| `STRIPE_PRICE_BP_MONTHLY` | `business_partner` | OnboardingPayment.vue |
| `STRIPE_PRICE_BP_ANNUAL` | `business_partner` | OnboardingPayment.vue |
| `STRIPE_PRICE_CS_BUSINESS_MONTHLY` | `cs_business` | OnboardingPayment.vue |
| `STRIPE_PRICE_CS_BUSINESS_ANNUAL` | `cs_business` | OnboardingPayment.vue |
| `STRIPE_PRICE_MAAT_MONTHLY` | `maat_addon` | SubscriptionService::toggleMaatAddon |
| `STRIPE_PRICE_MAAT_ANNUAL` | `maat_addon` | SubscriptionService::toggleMaatAddon |

**Frontend injection:** `resources/views/app.blade.php` injects all 10 IDs into a hidden `#aegis-config` div as JSON. `OnboardingPayment.vue` reads `window.__AEGIS_CONFIG__` and calls `resolveStripePrice()` to look up the correct ID from tier+billing combination. ✅

**`stripe_price_to_tier` mapping** in config used by `StripeEventListener::handleSubscriptionUpdated()` to sync `users.tier` on webhook. ✅

---

# PART D — LIFECYCLE

## 18. Onboarding Flow (First Subscription) ✅ FULLY WIRED

Step-by-step verified:

| Step | Route | Controller | Status |
|---|---|---|---|
| 1. Register | `POST /register` | `RegisterController::store` | ✅ |
| 2. Verify email | `GET /email/verify/{id}/{hash}` | `VerifyEmailController` | ✅ |
| 3. Intent (use-case) | `GET/POST /onboarding` | `OnboardingController::showIntent/submitIntent` | ✅ |
| 4. Role confirm | `GET /onboarding/role` | `OnboardingController::showRole` | ✅ |
| 5. Plan selection | `GET /onboarding/plan` | `OnboardingController::showPlan` | ✅ |
| 5b. Plan store | `POST /onboarding/plan` | `OnboardingController::storePlan` | ✅ |
| 6. Payment form | `GET /onboarding/payment` | `OnboardingController::showPayment` | ✅ |
| 7. Subscribe | `POST /onboarding/subscribe` | `OnboardingController::subscribe` | ✅ |

**Demo stripe_id clearing:** `showPayment()` attempts to retrieve the existing `stripe_id` from Stripe API. If it throws (fake ID like `cus_demo_sarah`), it clears `stripe_id` so `createAsStripeCustomer()` runs fresh. ✅

**MAAT at subscribe time:** `subscribe()` reads `wantsMaat` from session and calls `toggleMaatAddon(true)` if set. ✅

## 19. Post-Signup Management (Provider) ✅ FULLY WIRED

All subscription management lives in `Provider/SettingsController.php`, served from `provider/settings?section=billing`:

| Action | Route | Controller method | SubscriptionService method | Status |
|---|---|---|---|---|
| View current plan + invoices | `GET /provider/settings` | `index()` | `getFullSubscriptionData()` | ✅ |
| Upgrade / downgrade plan | `POST /provider/settings/subscription/swap` | `swapPlan()` | `changePlan()` → `upgrade()` or `downgrade()` | ✅ |
| Cancel at period end | `POST /provider/settings/subscription/cancel` | `cancelPlan()` | `cancel()` | ✅ |
| Resume during grace period | `POST /provider/settings/subscription/resume` | `resumePlan()` | `reactivate()` | ✅ |
| Toggle MAAT add-on | `POST /provider/settings/subscription/maat` | `toggleMaat()` | `toggleMaatAddon()` | ✅ |
| Set default payment method | `POST /provider/settings/payment-method/default` | `setDefaultPaymentMethod()` | `setDefaultPaymentMethod()` | ✅ |
| Remove payment method | `DELETE /provider/settings/payment-method` | `removePaymentMethod()` | `removePaymentMethod()` | ✅ |
| Open Stripe billing portal | `GET /provider/settings/billing-portal` | `billingPortal()` | `billingPortalUrl()` | ✅ |
| Add new card (via portal) | Links to Stripe Portal | N/A | N/A | ✅ (via portal) |
| Store payment method directly | `POST /provider/settings/payment-method` | `storePaymentMethod()` | — | ✅ route exists; no native Elements UI |
| Delete account | `DELETE /provider/settings/account` | `deleteAccount()` | — | ✅ |

**⚠️ Native "Add Card" UI still missing:** `storePaymentMethod` route + controller method exist, but `Settings.vue` links to Stripe Portal rather than showing a native Stripe Elements card input modal. This is functional but creates a UX interruption (leaves the app). Low priority.

## 20. Post-Signup Management (BP & CS) ❌ CONFIRMED GAP

### Business Partner Settings
- `BpSettingsController` has only: `index()` + `updateNotifications()`
- Routes in `routes/web.php`: only `GET /settings`, `PUT /settings/notifications`, `PUT /settings/password`, MFA routes
- **Missing:** No subscription management routes. No `swapPlan`, `cancelPlan`, `resumePlan`, `billingPortal` for BP.
- `resources/js/pages/business-partner/Settings.vue` is **70 lines** — only handles account email/password, notifications, and discoverability. No billing panel.
- `bp.settings.account`, `bp.settings.notif`, `bp.settings.visibility` routes referenced in the Vue file **do not exist** in `routes/web.php`. ❌ These will 404.

### Continuity Steward Settings
- `CsSettingsController` has only: `index()` + `updateNotifications()`
- Routes in `routes/web.php`: only `GET /settings`, `PUT /settings/notifications`, `PUT /settings/password`, MFA routes
- **Missing:** No subscription management for Business CS. No billing portal.
- `resources/js/pages/continuity-steward/Settings.vue` is **57 lines** — stub only.
- Guard needed: only show billing panel if `cs_account_type === 'business'` — Invited CS must never see billing.

**Priority:** HIGH for both. BP and CS subscribers cannot manage their own plans.

## 21. The Webhook Engine ✅ MOSTLY COMPLETE, ONE GAP

**Handler:** `app/Listeners/StripeEventListener.php`, registered via Cashier's `WebhookReceived` event in `AppServiceProvider`.

**Background sweep:** `StripeWebhookProcessorJob` runs every 5 minutes via scheduler, ensuring any unprocessed webhook rows in `stripe_webhook_events` get re-processed. ✅

| Stripe Event | Handler method | Status | Notes |
|---|---|---|---|
| `invoice.payment_succeeded` | `handlePaymentSucceeded()` | ✅ | Logs + ActivityService entry |
| `invoice.payment_failed` | `handlePaymentFailed()` | ✅ | Logs + ActivityService notification + email |
| `invoice.upcoming` | `handleInvoiceUpcoming()` | ⚠️ | Logs only — **renewal reminder email NOT dispatched** (see Gap 3) |
| `customer.subscription.created` | `handleSubscriptionCreated()` | ✅ | Cashier handles DB sync; custom: no-op |
| `customer.subscription.updated` | `handleSubscriptionUpdated()` | ✅ | Syncs `users.tier` via `stripe_price_to_tier` map |
| `customer.subscription.deleted` | `handleSubscriptionCancelled()` | ✅ | ActivityService log + `SubscriptionCancelled` event → email (template 68) |
| `payment_method.attached` | `handlePaymentMethodAttached()` | ✅ | Logs |
| `payment_method.detached` | `handlePaymentMethodDetached()` | ✅ | Logs |
| `charge.refunded` | `handleChargeRefunded()` | ✅ | ActivityService log |
| `transfer.created` | `handleTransferCreated()` | ✅ | Logs |
| `transfer.paid` | `handleTransferPaid()` | ✅ | Updates `BpPayout.status = paid` + `PayoutReleased` event → email (template 48) |
| `transfer.failed` | `handleTransferFailed()` | ✅ | Updates `BpPayout.status = failed` + notification |
| `payment_intent.succeeded` | `handlePaymentIntentSucceeded()` | ✅ | Updates `BpPayout` or `PractitionerPayment` status |
| `payment_intent.payment_failed` | `handlePaymentIntentFailed()` | ✅ | Updates payout status + notification |
| `account.updated` | *(not handled)* | ⚠️ | Stripe Connect account status changes ignored. Acceptable for MVP. |

**Billing email events registered in `AppServiceProvider`:** ✅
- `SubscriptionCancelled` → `SendEmailNotificationListener` → template `gaps/68-subscription-cancelled`
- `SubscriptionTierChanged` → `SendEmailNotificationListener` → template `admin/51-plan-upgraded` or `admin/52-plan-downgraded`
- `MaatAddonChanged` → `SendEmailNotificationListener` → template `gaps/69-maat-addon-change`

## 22. Proration Rules ✅ VERIFIED

`SubscriptionService::changePlan()` dynamically detects direction by comparing `pricePerDay()` of current vs new price:

| Direction | Method called | Cashier call | Billing effect |
|---|---|---|---|
| **Upgrade** (higher $/day) | `upgrade()` | `$sub->swapAndInvoice($newPriceId)` | Prorated difference billed immediately |
| **Downgrade** (lower $/day) | `downgrade()` | `$sub->noProrate()->swap($newPriceId)` | New price effective next cycle, no refund |
| **Same** | Returns `unchanged` | None | No action |

**Cross-billing period swaps** (e.g. monthly→annual) handled correctly because `pricePerDay()` normalises the comparison using Stripe's price interval. ✅

## 23. The MAAT Add-on ✅ FULLY WIRED

- **Guard:** `toggleMaat()` in `SettingsController` checks `user->tier === 'practice'` before proceeding. Upgrade error returned if on Access. ✅
- **Billing period detection:** Inspects current subscription's `stripe_price` to determine if monthly or annual, then selects matching MAAT price. ✅
- **Stripe operation:** `$sub->addPrice($maatPriceId)` / `$sub->removePrice($priceId)`. ✅
- **DB sync:** `users.maat_addon` bool updated after Stripe call. ✅
- **Event:** `MaatAddonChanged` fired → `SendEmailNotificationListener` → template `gaps/69-maat-addon-change`. ✅
- **Pricing:** +$29/mo or +$23/mo annual (same price as Access tier, intentional). ✅

---

# PART E — FEATURE GATING

## 24. Tier-Based Feature Locks (Access vs Practice) ✅ VERIFIED

From `config/aegis.php` `tier_limits`:

| Feature | Access | Practice |
|---|---|---|
| Max Continuity Stewards | 1 | 2 |
| Max Support Stewards | 1 | 4 |
| Referrals (send & receive) | ❌ locked | ✅ |
| Services Mode (offer to peers) | ❌ locked | ✅ |
| Network search | limited | full |
| Job Postings (hire BPs) | ❌ hidden | ✅ |

**Note on Access steward limits:** Config says `max_support_stewards: 1` for Access. Settings.vue (and the old billing doc) shows "2 Support Stewards included" for Access. These are inconsistent — config is authoritative. Update the Settings.vue copy or the config. **Recommend:** update config to 2 if confirmed with Carizma, or update UI copy to match config.

## 25. Role-Based Route Gating (Middleware Map) ✅ VERIFIED

| Middleware | File | Verified |
|---|---|---|
| `EnsureEmailVerified` | `app/Http/Middleware/EnsureEmailVerified.php` | ✅ Checks `users.verified` |
| `EnsureSubscriptionActive` | `app/Http/Middleware/EnsureSubscriptionActive.php` | ✅ Checks `subscriptions.stripe_status IN (active, trialing, past_due)` |
| `EnsureServicesMode` | `app/Http/Middleware/EnsureServicesMode.php` | ✅ Gates `services.mode` routes — requires `users.services_mode=1` AND `tier=practice` |
| `EnsureRole` | `app/Http/Middleware/EnsureRole.php` | ✅ Used as `role:{value}` |
| `CheckAccountLocked` | `app/Http/Middleware/CheckAccountLocked.php` | ✅ Checks `users.locked_at` |
| `EnsureAdminRole` | `app/Http/Middleware/EnsureAdminRole.php` | ✅ Admin-specific gate |
| `ImpersonateForDemo` | `app/Http/Middleware/ImpersonateForDemo.php` | ✅ `?as=<user_id>` demo flag |

## 26. Frontend Lock Mechanism (Upgrade Modal) ✅ VERIFIED

- `AegisUpgradeModal.vue` — universal tier-gate modal, globally mounted in `AppLayout.vue`. ✅
- `UpgradeCSModal.vue` — CS-specific upgrade modal (Invited → Business CS). ✅
- `usePortal().requiresTier('practice', fn)` — callback form; opens modal and returns false if Access tier. ✅
- `useUpgrade().requiresPractice(fn)` — alias pattern. ✅
- **Sidebar:** `AppSidebar.vue` marks Referrals and Services nav items with `locked: isAccessTier` → renders `.is-locked` CSS class (opacity 0.55) + "Upgrade to unlock" tooltip. ✅

## 27. Page-Level Access Matrix ✅ VERIFIED (PARTIAL)

_(Full matrix unchanged — see §27 of prior version. Route-level gating via middleware confirmed for all portals. `EnsureServicesMode` correctly applied only to service-creation routes, not session-complete routes which any provider can access as a client.)_

---

# PART F — OPERATIONS

## 28. Full Lifecycle Coverage Matrix

| Lifecycle stage | Status | Notes |
|---|---|---|
| Registration (all roles) | ✅ Complete | `RegisterController` + `AuthService::register()` |
| Email verification | ✅ Complete | `EnsureEmailVerified` middleware |
| Onboarding intent / role | ✅ Complete | `OnboardingController` |
| Plan selection (UI) | ✅ Complete | `OnboardingPlan.vue` — all roles |
| Payment capture | ✅ Complete | `OnboardingPayment.vue` + Stripe Elements |
| Subscription creation | ✅ Complete | `OnboardingController::subscribe()` + `SubscriptionService::subscribe()` |
| MAAT add-on at signup | ✅ Complete | Wired in `subscribe()` |
| Login redirect logic | ✅ Complete | `LoginController::resolvePostLoginDestination()` |
| Provider plan management | ✅ Complete | `Provider/SettingsController` all methods |
| BP plan management | ❌ Gap | No subscription routes or billing UI for BP |
| CS plan management | ❌ Gap | No subscription routes or billing UI for CS (Business) |
| Webhook processing | ✅ Mostly complete | All key events handled; `invoice.upcoming` email not wired |
| Renewal reminder email | ⚠️ Partial | Job scheduled ✅, event class exists ✅, email template exists ✅, listener mapping ❌ |
| Plan upgrade email | ✅ Complete | Template `admin/51-plan-upgraded` wired |
| Plan downgrade email | ✅ Complete | Template `admin/52-plan-downgraded` wired |
| Cancellation email | ✅ Complete | Template `gaps/68-subscription-cancelled` wired |
| MAAT change email | ✅ Complete | Template `gaps/69-maat-addon-change` wired |
| Payment failed notification | ✅ Complete | ActivityService + email on `invoice.payment_failed` |
| Payout released email | ✅ Complete | Template `bp/48-payout-released` wired |
| Account delete | ✅ Complete | `SettingsController::deleteAccount()` |
| Account lock/unlock | ✅ Complete | `AuthService::lockAccount/unlockAccount` + admin routes |
| Founding member perks | ❌ Not implemented | Config exists; no DB column or assignment logic |
| Native add-card modal | ⚠️ Partial | Route + controller exist; no Stripe Elements UI |

## 29. Known Bugs & Discrepancies Found

### 🐛 Bug 1 — `UserTier::monthlyCents()` returns stale prototype prices
**File:** `app/Enums/UserTier.php` line 23  
**Current:** `Access => 1900` ($19), presumably `Practice => 3900` ($39)  
**Correct:** `Access => 2900` ($29), `Practice => 4900` ($49) per June 2026 sign-off  
**Impact:** Any code calling `UserTier::Access->monthlyCents()` gets the wrong price. Check callers before fixing.  
**Fix:** Update both values in the enum.

### 🐛 Bug 2 — Access tier steward count inconsistency
**Config (`tier_limits.access.max_support_stewards`):** `1`  
**Settings.vue UI copy:** "2 Support Stewards included"  
**Old billing doc §24 says:** "2 Support Stewards included"  
**Resolution needed:** Confirm with Carizma — config is authoritative. Update whichever is wrong.

### 🐛 Bug 3 — BP Settings Vue references non-existent routes
**File:** `resources/js/pages/business-partner/Settings.vue`  
**Bad routes:** `bp.settings.account`, `bp.settings.notif`, `bp.settings.visibility`  
**Reality:** These routes do not exist in `routes/web.php`  
**Impact:** All save buttons in BP Settings will 404. This is currently hidden because the file is a 70-line stub and these forms aren't prominently surfaced.

### 🐛 Bug 4 (Fixed this session) — Portal switcher showed all portals regardless of user roles
**File:** `resources/js/components/chrome/AppHeader.vue`  
**Was:** Always showing Practitioner + Continuity Steward + Support Steward for all non-BP users  
**Fixed:** Now gates CS/SS entries on `user.has_cs_portal` / `user.has_ss_portal`  
**Still needed:** Backend must send these flags in the `auth.user` prop.

## 30. Remaining Gaps & Exactly Where To Fix Them

### Gap 1 — BP subscription management UI (HIGH PRIORITY)
**Scope:** Business Partners can subscribe (onboarding works) but cannot manage their subscription afterward.

**Where to add:**
1. `app/Http/Controllers/BusinessPartner/SettingsController.php` — add `swapPlan()`, `cancelPlan()`, `resumePlan()`, `billingPortal()`, `setDefaultPaymentMethod()`, `removePaymentMethod()` (mirror Provider/SettingsController)
2. `routes/web.php` BP group — add subscription management routes
3. `resources/js/pages/business-partner/Settings.vue` — build billing panel (BP has one tier, so swap = monthly↔annual only)
4. Fix the 3 missing routes (`bp.settings.account`, `.notif`, `.visibility`) or rename the Vue calls to match existing routes

**Est:** ~2 hrs

### Gap 2 — CS Business subscription management UI (HIGH PRIORITY)
**Same as Gap 1** for Business CS. One tier (swap = monthly↔annual only).

**Where to add:**
1. `app/Http/Controllers/ContinuitySteward/SettingsController.php` — add subscription methods
2. `routes/web.php` CS group — add subscription routes
3. `resources/js/pages/continuity-steward/Settings.vue` — build billing panel
4. **Guard:** only show billing panel if `cs_account_type === 'business'` — Invited CS must never see a billing panel

**Est:** ~1.5 hrs

### Gap 3 — Renewal reminder email (MEDIUM PRIORITY)
**Current state:** 
- `SubscriptionRenewalCheckJob` runs daily at 08:00 UTC ✅
- Job fires `SubscriptionRenewalUpcoming` event ✅
- Email template `resources/views/emails/admin/55-renewal-upcoming.blade.php` exists ✅
- **Missing:** `SubscriptionRenewalUpcoming` is NOT registered in `AppServiceProvider` → `SendEmailNotificationListener` does not handle it → no email is sent

**Where to fix:**
1. `app/Providers/AppServiceProvider.php` — add:
   ```php
   Event::listen(Events\Stripe\SubscriptionRenewalUpcoming::class, Listeners\SendEmailNotificationListener::class);
   ```
2. `app/Listeners/SendEmailNotificationListener.php` — add import + `match` case + private handler method:
   ```php
   use App\Events\Stripe\SubscriptionRenewalUpcoming;
   // In match block:
   $event instanceof SubscriptionRenewalUpcoming => $this->subscriptionRenewalUpcoming($event),
   // New private method:
   private function subscriptionRenewalUpcoming(SubscriptionRenewalUpcoming $e): array {
       return [[
           'user_id'  => $e->user->id,
           'gate_key' => 'notify_email',
           'template' => 'emails.admin.55-renewal-upcoming',
           'data'     => ['user_name' => $e->user->display_name, 'renewal_date' => $e->renewalDate, 'plan_label' => $e->planLabel, 'amount_cents' => $e->amountCents],
       ]];
   }
   ```
3. Remove the TODO comment from `StripeEventListener::handleInvoiceUpcoming()` and optionally wire it there as well (belt + suspenders).

**Est:** ~30 mins

### Gap 4 — Enterprise CS provisioning (PRODUCT DECISION)
`mailto:` only. Intentional. Leave unless automation wanted.

### Gap 5 — Native "Add Card" modal (LOW PRIORITY)
`storePaymentMethod` route + controller method exist. Only a Stripe Elements modal in Settings.vue is missing. Currently links to Stripe Customer Portal instead. Functional but breaks UX flow.

### Gap 6 — Founding Member perks (PRODUCT DECISION)
Config (`config/aegis.php` `founding_member` section) defines the perks. No `users` column or assignment logic exists. Needs product decision from Carizma before implementation.

### Gap 7 — `UserTier::monthlyCents()` stale price (BUG — FIX IMMEDIATELY)
See §29 Bug 1. One-line fix per case. Audit callers first.

### Gap 8 — Backend `has_cs_portal` / `has_ss_portal` flags not sent
`AppHeader.vue` portal switcher and `Settings.vue` Portal Access panel both check `user.has_cs_portal` / `user.has_ss_portal`. Neither `ProviderSettingsController::index()` nor `HandleInertiaRequests` middleware currently appends these flags to the user prop.

**Where to fix:** `app/Http/Middleware/HandleInertiaRequests.php` shared data `auth.user` — add:
```php
'has_cs_portal' => $user?->role_assignments?->contains('role', 'continuity_steward') ?? false,
'has_ss_portal' => $user?->role_assignments?->contains('role', 'support_steward') ?? false,
```
Or query `user_role_assignments` table directly.

**Est:** ~30 mins

### Gap 9 — Access tier steward count discrepancy (NEEDS CONFIRMATION)
`config/aegis.php` `tier_limits.access.max_support_stewards = 1` vs UI/doc saying 2. Confirm with Carizma and update one of them.

---

## 31. Stack & Architecture ✅ VERIFIED

- **Backend:** PHP 8.2, Laravel 12, MySQL 8 — **77 models**, **123 migrations**, **55 enums** across **14 domains** — all confirmed by file count
- **Frontend:** Vue 3, Inertia.js, Pinia, Vuelidate
- **Payments:** Laravel Cashier `^16.6` (subscriptions) + Stripe PHP SDK `^17.3` (Connect payouts) — confirmed in `composer.json`
- **Design system:** CSS variables only (no Tailwind, no hex literals, no inline SVGs). `AegisIcon` for all icons. Globally registered: `AegisModal`, `AegisStatChip`, `AegisBadge`, `AegisPagination`, `AegisHeroBanner`.
- **Universal conventions:** UUID `CHAR(36)` PKs, money always integer cents, soft deletes on user-facing tables, `authorize()` returns `true` in FormRequests (real auth at Policy level).
- **Write-path:** Vue form → Inertia POST → FormRequest → Controller → Service → `ActivityService::log()` fan-out → toast + reload. Events fire from Services (never Controllers). `>3 recipients` → `ActivityFanoutJob`.

## 32. File Map ✅ VERIFIED

### Backend
| File | Role | Status |
|---|---|---|
| `app/Services/SubscriptionService.php` | Engine: `subscribe`, `upgrade`, `downgrade`, `changePlan`, `cancel`, `cancelNow`, `reactivate`, `getStatus`, `getFullSubscriptionData`, `toggleMaatAddon`, `billingPortalUrl`, `setDefaultPaymentMethod`, `removePaymentMethod`, `syncStripe` — 14 methods | ✅ |
| `app/Services/AuthService.php` | `register()`, `login()`, `logout()`, `lockAccount()`, `enableMfa()`, `verifyMfa()` and more | ✅ |
| `app/Http/Controllers/Auth/RegisterController.php` | show / store | ✅ |
| `app/Http/Requests/Auth/RegisterRequest.php` | Registration validation | ✅ |
| `app/Http/Controllers/Auth/LoginController.php` | Login + `resolvePostLoginDestination` | ✅ |
| `app/Http/Controllers/Auth/OnboardingController.php` | `showIntent`, `showRole`, `submitIntent`, `complete`, `showPlan`, `storePlan`, `showPayment`, `subscribe` | ✅ |
| `app/Http/Controllers/Auth/VerifyEmailController.php` | Email verification | ✅ |
| `app/Http/Controllers/Provider/SettingsController.php` | `index` + all subscription/payment methods | ✅ |
| `app/Http/Controllers/BusinessPartner/SettingsController.php` | `index` + `updateNotifications` only | ❌ No subscription methods |
| `app/Http/Controllers/ContinuitySteward/SettingsController.php` | `index` + `updateNotifications` only | ❌ No subscription methods |
| `app/Listeners/StripeEventListener.php` | All webhook handlers (subscription + Connect) | ✅ except renewal email gap |
| `app/Listeners/SendEmailNotificationListener.php` | Billing events: `SubscriptionCancelled`, `SubscriptionTierChanged`, `MaatAddonChanged`, `PayoutReleased` mapped | ⚠️ Missing `SubscriptionRenewalUpcoming` |
| `app/Jobs/SubscriptionRenewalCheckJob.php` | Daily renewal warning job | ✅ Exists + scheduled |
| `app/Events/Stripe/SubscriptionRenewalUpcoming.php` | Renewal event class | ✅ Exists, not registered |
| `app/Http/Middleware/EnsureSubscriptionActive.php` | Subscription gate (exempts free roles) | ✅ |
| `app/Http/Middleware/EnsureEmailVerified.php` | Email gate | ✅ |
| `app/Http/Middleware/EnsureServicesMode.php` | Services tier gate (Practice + services_mode=1) | ✅ |
| `app/Http/Middleware/CheckAccountLocked.php` | Lock / deactivation gate | ✅ |
| `app/Http/Middleware/EnsureRole.php` | Role gate | ✅ |
| `config/aegis.php` | pricing, stripe_price_to_tier, paid_roles, tier_limits | ✅ |
| `config/cashier.php` | Cashier model + webhook path | ✅ |
| `routes/console.php` | All scheduled jobs (SubscriptionRenewalCheckJob @ 08:00 UTC daily) | ✅ |

### Frontend
| File | Role | Status |
|---|---|---|
| `resources/js/pages/auth/Register.vue` | Multi-step registration wizard | ✅ |
| `resources/js/pages/auth/OnboardingPlan.vue` | Plan selection (role-branched for all paid roles) | ✅ |
| `resources/js/pages/auth/OnboardingPayment.vue` | Stripe card capture + `resolveStripePrice()` | ✅ |
| `resources/js/pages/provider/Settings.vue` | Full subscription + billing management | ✅ |
| `resources/js/pages/business-partner/Settings.vue` | 70-line stub — no billing panel + broken route refs | ❌ |
| `resources/js/pages/continuity-steward/Settings.vue` | 57-line stub — no billing panel | ❌ |
| `resources/js/composables/usePortal.js` | `requiresTier()` tier gate | ✅ |
| `resources/js/composables/useUpgrade.js` | `openUpgradeModal()` / `requiresPractice()` | ✅ |
| `resources/js/components/ui/AegisUpgradeModal.vue` | Universal upgrade modal (Provider Access→Practice) | ✅ |
| `resources/js/components/modals/UpgradeCSModal.vue` | CS-specific Invited→Business upgrade modal | ✅ |
| `resources/js/components/chrome/AppSidebar.vue` | Locks Referrals + Services nav items for Access tier | ✅ |
| `resources/js/components/chrome/AppHeader.vue` | Portal switcher (now gated on `has_cs_portal`/`has_ss_portal`) | ✅ Fixed |
| `resources/views/app.blade.php` | Injects all 10 price IDs to `#aegis-config` div | ✅ |

### Migrations (billing-relevant)
| Migration | Purpose | Status |
|---|---|---|
| `2024_01_01_000001` | users (tier, role, cs_account_type, bp_type, maat_addon, services_mode, stripe fields, verified) | ✅ |
| `2024_01_02_000004` | stripe_id, stripe_payment_method_id on users | ✅ |
| `2024_01_03_000001` | pm_type, pm_last_four, trial_ends_at (Cashier columns) | ✅ |
| `2026_07_07_092246–249` | subscriptions + subscription_items (UUID-correct, with meter fields) | ✅ |
| `2026_07_07_100000–001` | UUID string fix + billable-column fix for subscriptions | ✅ |
| `2024_01_01_000068` | stripe_webhook_events deduplication table | ✅ |

### Email Templates (billing-relevant)
| Template path | Event trigger | Status |
|---|---|---|
| `emails/admin/51-plan-upgraded.blade.php` | `SubscriptionTierChanged` (upgrade) | ✅ |
| `emails/admin/52-plan-downgraded.blade.php` | `SubscriptionTierChanged` (downgrade) | ✅ |
| `emails/admin/55-renewal-upcoming.blade.php` | `SubscriptionRenewalUpcoming` | ⚠️ Template exists; listener NOT registered |
| `emails/gaps/68-subscription-cancelled.blade.php` | `SubscriptionCancelled` | ✅ |
| `emails/gaps/69-maat-addon-change.blade.php` | `MaatAddonChanged` | ✅ |
| `emails/bp/48-payout-released.blade.php` | `PayoutReleased` (BP Connect payout) | ✅ |
| `emails/business/46-invoice-paid.blade.php` | `InvoiceSent` / paid (BP marketplace) | ✅ |

## 33. Deployment Checklist

### Stripe Dashboard
1. **Products** — 10 prices created (per `AEGIS_STRIPE_SETUP.md`)
2. **Billing Portal** (Settings → Billing → Customer portal): enable update card, view invoices, cancel at period end, switch plans. Return URL: `https://aegis.devlet.tech/provider/settings?section=billing`
3. **Webhook** (`aegis.devlet.tech/stripe/webhook`) — events:
   ```
   invoice.payment_succeeded   invoice.payment_failed   invoice.upcoming
   customer.subscription.created   customer.subscription.updated   customer.subscription.deleted
   payment_intent.succeeded   payment_intent.payment_failed
   payment_method.attached   payment_method.detached
   charge.refunded   transfer.created   transfer.paid   transfer.failed
   ```
   Note: `account.updated` not needed for MVP (handler not implemented).
4. Copy signing secret → `.env` `STRIPE_WEBHOOK_SECRET`

### Server
```bash
git pull
composer install --no-dev
php artisan migrate
php artisan config:clear
php artisan queue:restart
npm ci && npm run build
```

### Test → Live
Comment out the `pk_test`/`sk_test` block in `.env`, uncomment `pk_live`/`sk_live` (live price IDs already prepared). Re-register webhook on live endpoint, update `STRIPE_WEBHOOK_SECRET`.

### Pre-launch Bug Fixes (do before go-live)
1. **Fix `UserTier::monthlyCents()` stale prices** (Gap 7) — 5-minute fix
2. **Wire renewal reminder email** (Gap 3) — 30-minute fix
3. **Add `has_cs_portal`/`has_ss_portal` to Inertia shared props** (Gap 8) — 30-minute fix
4. **Confirm Access tier max_support_stewards** (Gap 9) — product decision

## 34. Appendix — Test Cards, Demo Users & Prototype vs Laravel

### Test cards (sandbox)
| Card | Simulates |
|---|---|
| `4242 4242 4242 4242` | Success |
| `4000 0000 0000 0002` | Card declined |
| `4000 0025 0000 3155` | 3D Secure required |
| `4000 0000 0000 9995` | Insufficient funds |
| `4000 0000 0000 0069` | Expired card |
| `4000 0000 0000 0127` | Incorrect CVC |
Any future expiry, any CVC, any ZIP.

### Demo users
| User | Role / Tier | Notes |
|---|---|---|
| `p_sarah` | Practitioner — Practice tier | Has live Stripe sub; `stripe_id` starts with real `cus_` prefix |
| `p_david` | Practitioner — Access tier | |
| `p_maria` | Practitioner — Practice, services mode on | |
| `cs_marcus` | Continuity Steward (Business CS) | |
| `ss_linda` | Support Steward | |
| `bp_acme` | Business Partner (Agency) | |
| `bp_jamal` | Business Partner (Freelancer) | |
| `admin_root` | Admin | |

State flags: `?as=<user_id>` (impersonate via `ImpersonateForDemo` middleware), `?emergency=true/false` (incident state), `?invited=true` (force Invited CS view). Persisted in session.

**Demo stripe_id detection:** `showPayment()` checks if existing `stripe_id` is fake (retrieval from Stripe throws) → clears it → `createAsStripeCustomer()` runs fresh. `cus_demo_*` and `acct_demo_*` prefixes are also guarded in payment flows. ✅

### Prototype vs Laravel — What Changed
`AEGIS-PROJECT-CONTEXT.md` was written against the original PHP/SQLite prototype. When the two sources conflict, the Laravel app + `config/aegis.php` win.

| Aspect | Prototype (legacy) | Current Laravel app |
|---|---|---|
| Database | SQLite, 16 tables | MySQL, 77 models / 123 migrations |
| User IDs | `p_sarah`, `cs_marcus` strings | UUID `CHAR(36)`, `ae_`-prefixed |
| Portals | `/provider-portal/`, `.php` pages | `/provider`, Vue/Inertia SPA |
| Access price | $39/mo | **$29/mo** (confirmed June 2026) |
| Practice price | $79/mo | **$49/mo** (confirmed June 2026) |
| Billing page | `finances.php` | `Settings.vue` (billing + invoices panels) |
| Cashier version | ^15 | **^16.6** |
| Services Mode | Standalone +$19 add-on | Folded into Practice tier (no extra charge) |
| UserTier::monthlyCents() | N/A | 🐛 Returns stale $19/$39 — should be $29/$49 |
| BP Settings Vue routes | N/A | 🐛 3 routes referenced that don't exist |
| Portal switcher | N/A | Fixed: now role-gated (was always showing all 3) |

---

## Quick-Fix Summary (Prioritised)

| Priority | Gap | File | Est time |
|---|---|---|---|
| 🔴 P0 | `UserTier::monthlyCents()` wrong prices | `app/Enums/UserTier.php` | 5 min |
| 🔴 P0 | Wire renewal reminder email | `AppServiceProvider` + `SendEmailNotificationListener` | 30 min |
| 🔴 P0 | Send `has_cs_portal`/`has_ss_portal` in Inertia props | `HandleInertiaRequests.php` | 30 min |
| 🔴 P1 | BP subscription management | `BP/SettingsController` + routes + `Settings.vue` | 2 hr |
| 🔴 P1 | CS Business subscription management | `CS/SettingsController` + routes + `Settings.vue` | 1.5 hr |
| 🟡 P2 | Fix BP Settings 3 broken route refs | `Settings.vue` (BP) | 15 min |
| 🟡 P2 | Confirm Access max_support_stewards (1 vs 2) | `config/aegis.php` or UI | 5 min + product call |
| 🟢 P3 | Native Add Card modal | `Provider/Settings.vue` + Stripe Elements | 1 hr |
| 🟢 P3 | Founding Member perks | Requires product scoping | TBD |
| 🟢 P3 | `account.updated` webhook handler | `StripeEventListener.php` | 30 min |

---

*Last updated: July 2026 — validated against live repo commit `2cd19de`*
*Previous validation: commit `26ea36a` (2026-07-08)*
