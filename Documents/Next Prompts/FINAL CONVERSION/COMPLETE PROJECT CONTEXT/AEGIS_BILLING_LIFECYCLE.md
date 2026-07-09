# Aegis — Complete Billing, Onboarding & System Reference (Canonical)

**Status:** Re-validated against live repo `main @ 9351e14` on 2026-07-09
**Previous validation:** `2cd19de` on 2026-07-08
**Revision:** 3 — reflects P0 + P1 + Batch3 batches, dispute system, CS engagement contract
**Companion docs:** `AEGIS-PROJECT-CONTEXT.md`, `AEGIS_PAYMENTS_FINANCE.md`, `AEGIS_SETTINGS.md`, `CONTINUITY_GROUP_CONVERSION_PLAN.md`, `ENV_REFERENCE.md`

**Purpose:** Everything a developer or Claude needs to understand Aegis subscription billing end-to-end — five roles, who pays, registration → onboarding → subscribe, provider/CS/BP plan management, webhook handling, tier limits, and MAAT add-on. Peer-to-peer money (Provider→BP, Provider→CS, Client→Provider) lives in `AEGIS_PAYMENTS_FINANCE.md`. Dispute system + CS engagement contract lifecycle lives in `CONTINUITY_GROUP_CONVERSION_PLAN.md` §0.7–0.8.

**Legend:** ✅ Verified complete · ⚠️ Partial · ❌ Not yet implemented · 🐛 Bug found · 🆕 New in this revision

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
20. Post-Signup Management (BP & CS)
21. The Webhook Engine
22. Proration Rules
23. The MAAT Add-on
24. 🆕 Native "Add Card" Flow (Stripe Elements)

**PART E — FEATURE GATING**
25. Tier-Based Feature Locks (Access vs Practice)
26. 🆕 Tier Limits Envification
27. Role-Based Route Gating (Middleware Map)
28. Frontend Lock Mechanism (Upgrade Modal)
29. Page-Level Access Matrix

**PART F — 🆕 PEER-PAYMENT INTEGRATION**
30. Cross-Reference to `AEGIS_PAYMENTS_FINANCE.md`
31. 🆕 CS Engagement Contract Billing Path
32. 🆕 Dispute System — Invoice Freeze Interaction

**PART G — OPERATIONS**
33. Full Lifecycle Coverage Matrix
34. Known Bugs & Discrepancies Found
35. Remaining Gaps
36. Stack & Architecture
37. File Map
38. Deployment Checklist
39. Appendix — Test Cards, Demo Users & Prototype vs Laravel

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

**🆕 Rev 3 additions:**
- **CS engagement contracts** — pre-agreed fee per activation stored on `plan_stewards.fee_cents`, with auto-invoice on incident close
- **Dispute system** — either party may open a dispute against an invoice/payout; the invoice freezes to `disputed` status until an admin resolves; refunds route through Stripe's rails

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

_(Role descriptions §2.1–2.5 unchanged — see prior revision.)_

## 3. Role Sub-Types & Paths ✅ VERIFIED

_(Unchanged. `cs_account_type` = business/invited/enterprise; `bp_type` = agency/freelancer; SS has no sub-type.)_

## 4. Multi-Role Identity & The Three-Tier Visibility Rule ✅ VERIFIED

Multi-role is fully wired.
- `AppHeader.vue` portal switcher gates CS/SS entries on `user.has_cs_portal` / `user.has_ss_portal` ✅
- `HandleInertiaRequests` middleware (line 136–139) queries `UserRoleAssignment` and injects both flags into `auth.user` on every Inertia request ✅ (was ⚠️ in Rev 2)
- `SettingsController::index()` reads these flags for the Portal Access panel ✅

## 5. The Continuity-Plan Lifecycle (Core Workflow)

_(Unchanged — see AEGIS-PROJECT-CONTEXT.md §5 for full lifecycle detail.)_

**🆕 Money-side integration point:** if the CS designation has `plan_stewards.fee_cents > 0`, incident close now triggers the CS engagement contract workflow (§31 below).

## 6. The Seven Critical-Incident Types

_(Unchanged — see AEGIS-PROJECT-CONTEXT.md §6.)_

## 7. The Five Portals ✅ VERIFIED

All five route groups exist in `routes/web.php` with correct middleware stacks:
- Provider: `auth, verified.email, subscription.active, role:practitioner, check.locked`
- CS: `auth, verified.email, subscription.active, role:continuity_steward, check.locked`
- SS: `auth, verified.email, role:support_steward, check.locked` *(no subscription gate — free)*
- BP: `auth, verified.email, subscription.active, role:business_partner, check.locked`
- Admin: `auth, role:admin` (via `EnsureAdminRole`)

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

`paid_roles` in `config/aegis.php`: `['practitioner', 'business_partner', 'continuity_steward_business']` ✅

## 9. The Complete Registration → Onboarding Flow ✅ VERIFIED

```
/register → RegisterController::store()
    → AuthService::register()
        → users row created (role, tier, cs_account_type, verified=0)
    → email verification sent → redirect: verification.notice

/email/verify/{id}/{hash} → VerifyEmailController
    → users.verified = 1
    → free roles → portal dashboard
    → paid roles → onboarding.intent

/onboarding → showIntent() / submitIntent()   → stores use-case in session
/onboarding/role  → showRole() (if role not yet set)
/onboarding/plan  → showPlan()   → Access/Practice/BP/CS plan cards + monthly/annual toggle
/onboarding/payment → showPayment()  → creates SetupIntent → OnboardingPayment.vue (Stripe Elements)

POST /onboarding/subscribe → subscribe()
    → Attaches PaymentMethod as default
    → Mirrors PM to users.stripe_payment_method_id (P0)
    → SubscriptionService::subscribe() → creates Stripe subscription
    → If wantsMaat: toggleMaatAddon(enable=true)
    → Syncs users.tier
    → Redirect → portal dashboard
```

All controllers verified ✅. Demo `stripe_id` clearing works ✅. MAAT wiring at subscribe time ✅. `stripe_payment_method_id` mirror confirmed at all three write sites ✅.

## 10–12. Email verification / Login redirect / Account lock ✅

_(Unchanged — see prior revision.)_

---

# PART C — THE MONEY

## 13. The Two Stripe Integrations ✅ VERIFIED

### Integration 1 — Cashier (Subscriptions)
- Laravel Cashier `^16.6` managing `subscriptions` + `subscription_items`
- **Who:** Practitioners, Business Partners, Business CS
- **Flow:** Aegis collects recurring revenue → funds go to Aegis Stripe account
- UUID migrations correct ✅

### Integration 2 — Stripe Connect (Marketplace Payouts)
- **Who:** BPs (receive contract/milestone payments), Providers with Services Mode (receive client session payments), Business CS (receive engagement fees)
- **Flow:** Payer pays → Aegis creates destination charge → funds land in recipient's connected account. Aegis nets $0.
- Connect account: `users.stripe_account_id` + `users.stripe_connected` tinyint
- 🆕 **`account.updated` webhook now handled** ✅ — `stripe_connected` flips automatically when `charges_enabled && payouts_enabled && details_submitted`

## 14–17. Packages / Schema / Pricing / Price IDs ✅ VERIFIED

- Cashier `^16.6` + Stripe PHP `^17.3` ✅
- `UserTier::monthlyCents()` corrected to $29/$49 ✅ (was 🐛 in Rev 2)
- All 10 price IDs env-driven, injected via `#aegis-config` ✅
- `stripe_price_to_tier` map drives webhook tier sync ✅

_(Full pricing tables and price ID matrix — see prior revision. No changes.)_

---

# PART D — LIFECYCLE

## 18. Onboarding Flow (First Subscription) ✅ FULLY WIRED

_(Unchanged — see prior revision.)_

## 19. Post-Signup Management (Provider) ✅ FULLY WIRED

| Action | Route | Status |
|---|---|---|
| View plan + invoices | `GET /provider/settings` | ✅ |
| Upgrade/downgrade | `POST .../subscription/swap` | ✅ |
| Cancel at period end | `POST .../subscription/cancel` | ✅ |
| Resume in grace | `POST .../subscription/resume` | ✅ |
| Toggle MAAT | `POST .../subscription/maat` | ✅ |
| Set default PM | `POST .../payment-method/default` | ✅ |
| Remove PM | `DELETE .../payment-method` | ✅ |
| Store PM (native) | `POST .../payment-method` | ✅ mirrors to `stripe_payment_method_id` |
| 🆕 SetupIntent (native add-card) | `POST .../payment-method/setup-intent` | ✅ NEW — returns `client_secret` for `AddCardModal.vue` |
| Open Stripe portal | `GET .../billing-portal` | ✅ |
| Delete account | `DELETE .../account` | ✅ |

## 20. Post-Signup Management (BP & CS)

**Rev 3 status — resolved from Rev 2's ❌ CONFIRMED GAP to ✅ MOSTLY WIRED.**

### Business Partner
- `BP/SettingsController` methods present ✅: `swapPlan`, `cancelPlan`, `resumePlan`, `billingPortal`, `connectOnboard`, `connectReturn`
- Real Stripe Connect Express onboarding (`accounts->create` + `accountLinks->create`) ✅ (P0 batch)
- Routes wired ✅: `bp.settings.subscription.swap/cancel/resume`, `bp.settings.billing.portal`, `bp.settings.connect.onboard/return`
- `resources/js/pages/business-partner/Settings.vue` billing panel present ✅
- 🆕 `bp.settings.payment.setup-intent` route exists ✅ (batch3)

### Continuity Steward (Business)
- `CS/SettingsController` methods present ✅: `swapPlan`, `cancelPlan`, `resumePlan`, `billingPortal`, `connectOnboard`, `connectReturn`
- Routes wired ✅: `cs.settings.subscription.swap/cancel/resume`, `cs.settings.billing.portal`, `cs.settings.connect.onboard/return`
- `resources/js/pages/continuity-steward/Settings.vue` billing panel + Stripe Connect card present ✅
- 🆕 `cs.settings.payment.setup-intent` route exists ✅ (batch3)
- **Invited CS guard:** Settings.vue splits billing panel by `cs_account_type` — Invited CS never sees billing UI ✅

**⚠️ Only remaining gap for both:** `storePaymentMethod` method not yet added to `CS/SettingsController` and `BP/SettingsController`. The `AddCardModal.vue` component and setup-intent endpoint both exist, but there's no target route for the modal to POST the finalized PaymentMethod ID. Provider `storePaymentMethod` works. See §35 Gap 1.

## 21. The Webhook Engine ✅ COMPLETE

**Handler:** `app/Listeners/StripeEventListener.php`, registered via Cashier's `WebhookReceived` event.

| Stripe Event | Handler | Status |
|---|---|---|
| `invoice.payment_succeeded` | `handlePaymentSucceeded` | ✅ |
| `invoice.payment_failed` | `handlePaymentFailed` | ✅ ActivityService + email |
| `invoice.upcoming` | `handleInvoiceUpcoming` | ✅ (belt-and-suspenders; `SubscriptionRenewalCheckJob` is primary) |
| `customer.subscription.created` | `handleSubscriptionCreated` | ✅ |
| `customer.subscription.updated` | `handleSubscriptionUpdated` | ✅ syncs `users.tier` |
| `customer.subscription.deleted` | `handleSubscriptionCancelled` | ✅ template `gaps/68` |
| `payment_method.attached` | `handlePaymentMethodAttached` | ✅ |
| `payment_method.detached` | `handlePaymentMethodDetached` | ✅ |
| `charge.refunded` | `handleChargeRefunded` | ✅ |
| `transfer.created` | `handleTransferCreated` | ✅ |
| `transfer.paid` | `handleTransferPaid` | ✅ template `bp/48` |
| `transfer.failed` | `handleTransferFailed` | ✅ |
| `payment_intent.succeeded` | `handlePaymentIntentSucceeded` | ✅ |
| `payment_intent.payment_failed` | `handlePaymentIntentFailed` | ✅ |
| 🆕 `account.updated` | `handleAccountUpdated` | ✅ WIRED (P1) — flips `stripe_connected` |

**Renewal reminder email:** Rev 3 status **✅ WIRED**. `SubscriptionRenewalUpcoming` event registered in `AppServiceProvider` (line 122); `SendEmailNotificationListener` has import + match case + private handler + template `emails/admin/55-renewal-upcoming.blade.php`. Was Rev 2 Gap 3 — now closed.

## 22–23. Proration + MAAT Add-on ✅

_(Unchanged. `changePlan()` detects direction via `pricePerDay()`; MAAT gated on Practice tier; `MaatAddonChanged` → template `gaps/69`.)_

## 24. 🆕 Native "Add Card" Flow (Stripe Elements)

**Rev 3 addition** — replaces the "Add card → leaves the app to Stripe Portal" flow with an in-app modal.

**Flow:**
1. User clicks "Add card" in Settings billing panel
2. `AddCardModal.vue` mounts → `POST {portal}.settings.payment.setup-intent` → server returns `client_secret` + `stripe_key`
3. Stripe.js loads from CDN (once); `stripe.elements()` mounts `cardNumber` / `cardExpiry` / `cardCvc` fields
4. User enters card → `stripe.confirmCardSetup(client_secret)` returns `payment_method` id
5. Modal POSTs PM id to `{portal}.settings.payment.store` with `set_default: true`
6. Server persists PM (Cashier `updateDefaultPaymentMethod`) + mirrors to `stripe_payment_method_id`

**Files:**
- Trait: `app/Http/Controllers/Concerns/CreatesSetupIntent.php` — `createSetupIntent()` method
- Portal controllers: `Provider/PaymentMethodSetupController.php`, `ContinuitySteward/PaymentMethodSetupController.php`, `BusinessPartner/PaymentMethodSetupController.php`
- Vue: `resources/js/components/modals/AddCardModal.vue`
- Routes: `{portal}.settings.payment.setup-intent` — all three portals

**Gap:** CS + BP `storePaymentMethod` methods still missing (§35 Gap 1). Provider works end-to-end.

**Fallback:** Stripe Customer Portal link still works as fallback in all three portals.

---

# PART E — FEATURE GATING

## 25. Tier-Based Feature Locks (Access vs Practice) ✅

_(Unchanged.)_

## 26. 🆕 Tier Limits Envification

**Rev 3 change** — tier steward caps are now `.env`-configurable so ops can tune without a deploy.

| `config/aegis.php` key | env var | Default |
|---|---|---|
| `tier_limits.access.max_continuity_stewards` | `TIER_ACCESS_MAX_CS` | 1 |
| `tier_limits.access.max_support_stewards` | `TIER_ACCESS_MAX_SS` | 1 |
| `tier_limits.practice.max_continuity_stewards` | `TIER_PRACTICE_MAX_CS` | 2 |
| `tier_limits.practice.max_support_stewards` | `TIER_PRACTICE_MAX_SS` | 4 |

**Pending Dr. Chapman confirmation:** `TIER_ACCESS_MAX_SS` — she has verbally referenced "2 SS on Access" but hasn't formally signed off. If confirmed, set `.env` to `TIER_ACCESS_MAX_SS=2`. Do NOT hardcode. See `AEGIS_CHAPMAN_PENDING_ITEMS.md`.

## 27–29. Middleware / Upgrade Modal / Access Matrix ✅

_(Unchanged. `EnsureRole`, `EnsureSubscriptionActive`, `EnsureServicesMode`, `CheckAccountLocked`, `EnsureAdminRole`, `EnsureEmailVerified` all verified. `AegisUpgradeModal.vue`, `UpgradeCSModal.vue`, `usePortal().requiresTier()`, `useUpgrade().requiresPractice()` all verified.)_

---

# PART F — 🆕 PEER-PAYMENT INTEGRATION

## 30. Cross-Reference to `AEGIS_PAYMENTS_FINANCE.md`

Peer-to-peer money (Provider→BP, Provider→CS, Client→Provider) is a separate integration from subscription billing. Refer to `AEGIS_PAYMENTS_FINANCE.md` for complete peer-payment coverage.

**Overlap points that matter for subscription billing:**
- **Same card** — Provider's `stripe_payment_method_id` funds BOTH subscription renewal AND peer payments to BPs/CSs. If the card is invalid, both flows fail.
- **Same `stripe_id`** — same Stripe customer for both flows.
- **Different `stripe_account_id`** — BPs and Business CS have their own Connect Express account (as recipients). Providers may have one too if `services_mode=1`.

## 31. 🆕 CS Engagement Contract Billing Path

**Rev 3 addition** — the CS engagement contract sits at the intersection of subscription (the CS pays Aegis) and peer payments (the Provider pays the CS).

**Data model** — `plan_stewards` migration (`2026_07_10_000001`):
- `fee_cents` — agreed compensation per incident activation
- `payment_terms` — `on_close | net_30 | net_60`
- `auto_charge` — boolean; if true, Provider's card charged automatically on incident close
- `engagement_document_id` — FK to countersigned agreement in `continuity_documents`

**Auto-invoice flow** on incident close:
1. CS marks all incident tasks complete
2. `IncidentService::maybeReadyForClosure()` fires `IncidentReadyForClosure` event → email Provider + SS
3. Provider (or SS as fallback after 72h) verifies closure via `verifyClosure()`
4. CS calls `closeWithInvoice()`:
   - Closes the incident (reseals vault)
   - If `fee_cents > 0`, auto-generates `CsInvoice` (status `sent`)
   - If `auto_charge && payment_terms='on_close'` and both parties Stripe-ready, immediately fires `chargeProviderToCs()`
   - Otherwise Provider pays manually via Finances page
5. Auto-close scheduler (`IncidentAutoCloseCheckJob`, hourly): if 7 days pass with no verification, system auto-verifies and closes

**Env timing knobs:**
- `CS_INCIDENT_AUTOCLOSE_DAYS` (default 7)
- `CS_INCIDENT_SS_FALLBACK_HOURS` (default 72 — reserved)

**Events wired (batch3):**
- `IncidentReadyForClosure`, `IncidentClosureVerified`, `IncidentAutoClosed`, `CsInvoiceAutoGenerated` — all registered on `SendEmailNotificationListener` + `ActivityFanoutListener`

**Pending:** Email blade templates for the 4 events. Wiring is complete; templates come in a later batch. Emails will use `template` key like `emails.incident.30-ready-for-closure`.

## 32. 🆕 Dispute System — Invoice Freeze Interaction

**Rev 3 addition** — either party can open a dispute against a paid or sent invoice/payout/session. The invoice moves to a new `disputed` status, blocking further payment attempts until the dispute is resolved.

**Interaction with subscription billing:**
- **Subscription invoices** (Provider→Aegis) are NOT subject to the dispute system. Those go through Stripe's chargeback rails directly. Aegis's dispute system is peer-to-peer only.
- **Peer invoices** (Provider→CS, Provider→BP, milestones, sessions) freeze to `disputed` when a dispute is opened. Payment attempts return "This invoice is under dispute" error.

**Full dispute lifecycle** — see `CONTINUITY_GROUP_CONVERSION_PLAN.md` §0.8. This doc covers only the subscription-billing impact.

---

# PART G — OPERATIONS

## 33. Full Lifecycle Coverage Matrix

| Stage | Rev 3 Status | Notes |
|---|---|---|
| Registration (all roles) | ✅ | `RegisterController` + `AuthService::register()` |
| Email verification | ✅ | `EnsureEmailVerified` middleware |
| Onboarding intent/role/plan | ✅ | `OnboardingController` |
| Payment capture (Stripe Elements) | ✅ | `OnboardingPayment.vue` |
| Subscription creation + tier sync | ✅ | `OnboardingController::subscribe()` |
| MAAT add-on at signup | ✅ | Wired in `subscribe()` |
| Login redirect logic | ✅ | 3-step priority chain |
| Provider plan management | ✅ | `Provider/SettingsController` all methods |
| BP plan management | ✅ Rev 3 | Was ❌ in Rev 2 — closed by P0 |
| CS plan management | ✅ Rev 3 | Was ❌ in Rev 2 — closed by P0 |
| BP Stripe Connect Express onboarding | ✅ Rev 3 | Was ❌ (stub) in Rev 2 — closed by P0 |
| CS Stripe Connect Express onboarding | ✅ Rev 3 | Was ❌ in Rev 2 — closed by P0 |
| Webhook processing | ✅ | All events handled |
| Renewal reminder email | ✅ Rev 3 | Was ⚠️ in Rev 2 — closed |
| Plan upgrade/downgrade emails | ✅ | |
| Cancellation email | ✅ | |
| MAAT change email | ✅ | |
| Payment failed notification | ✅ | |
| Payout released email | ✅ | |
| Account delete | ✅ | |
| Account lock/unlock | ✅ | |
| `account.updated` webhook | ✅ Rev 3 | Was ⚠️ in Rev 2 — closed by P1 |
| Native add-card modal (Provider) | ✅ Rev 3 | Was ⚠️ in Rev 2 — closed by batch3 |
| Native add-card modal (CS/BP) | ⚠️ Rev 3 | SetupIntent + modal exist; `storePaymentMethod` in CS/BP controllers missing |
| Tier limits envified | ✅ Rev 3 | 4 new env vars, defaults unchanged |
| `has_cs_portal` / `has_ss_portal` in Inertia props | ✅ Rev 3 | Was ⚠️ in Rev 2 — closed |
| `UserTier::monthlyCents` correct | ✅ Rev 3 | Was 🐛 in Rev 2 — fixed |
| CS engagement contract auto-invoice | ✅ Rev 3 | New — batch3 |
| Dispute system | ✅ Rev 3 | New — batch3 |
| Dispute email templates | ⚠️ Rev 3 | Listener wired, blade templates pending |
| Incident closure email templates | ⚠️ Rev 3 | Listener wired, blade templates pending |
| Founding member perks | ❌ | Awaiting Dr. Chapman answers on 4 questions |

## 34. Known Bugs & Discrepancies

**Rev 2 bug fixes shipped in Rev 3:**
- 🐛→✅ `UserTier::monthlyCents()` corrected to $29/$49
- 🐛→✅ `AppHeader.vue` portal switcher now role-gated
- 🐛→✅ `has_cs_portal`/`has_ss_portal` in Inertia shared props
- 🐛→✅ `SubscriptionRenewalUpcoming` event registered + listener + template
- 🐛→✅ BP Settings dead route references replaced
- 🐛→✅ Field-name mismatches (`practitioner_id`, `total_cents`, `payment_type`, `payable`) across BP + CS Vue pages
- 🐛→✅ CS accept/decline route missing
- 🐛→✅ Provider Finances.vue was static HTML — rebuilt as real Inertia component

**Rev 3 open bugs:** none critical.

## 35. Remaining Gaps

### Gap 1 — CS + BP `storePaymentMethod` methods (MEDIUM PRIORITY)
**Scope:** `AddCardModal.vue` component ready and setup-intent endpoints work for all 3 portals, but only Provider has a `storePaymentMethod` controller method to receive the finalized PaymentMethod id.

**Where to fix:**
1. Add `storePaymentMethod(Request)` to `BP/SettingsController` mirroring `Provider/SettingsController::storePaymentMethod` (validate `payment_method_id`, attach via Cashier, mirror to `stripe_payment_method_id`)
2. Add same method to `CS/SettingsController` (only for `cs_account_type=business`)
3. Register `bp.settings.payment.store` and `cs.settings.payment.store` routes
4. Wire the routes as `storeRoute` prop when mounting `AddCardModal` in BP + CS Settings.vue billing panels

**Est:** ~30 mins.

### Gap 2 — Email blade templates for 7 new events (MEDIUM PRIORITY)
Rev 3 batch3 wired 7 new events to `SendEmailNotificationListener` with `template` keys pointing to paths that don't yet exist:
- `emails.incident.30-ready-for-closure`
- `emails.incident.31-closure-verified`
- `emails.incident.32-auto-closed`
- `emails.cs.60-auto-invoice-generated`
- `emails.disputes.70-opened`
- `emails.disputes.71-replied`
- `emails.disputes.72-resolved`

**Fix:** create the 7 blade files per `EMAIL_TEMPLATES_PROMPT.md` conventions. The listener silently no-ops on missing templates today, so this isn't blocking — just means users don't get emails for these events yet.

**Est:** ~2 hours.

### Gap 3 — "Open dispute" button placement in Finances tables (LOW PRIORITY)
`OpenDisputeModal.vue` component + all backend routes are ready. The button that opens the modal isn't yet wired into Provider Finances, CS Invoices, and BP Invoices tables.

**Fix:** import `OpenDisputeModal`, add `<button @click="openDispute(row)">` next to paid/sent invoices, wire route names.

**Est:** ~30 mins per portal (3 portals).

### Gap 4 — Continuity Group Provider Vue pages (SEPARATE WORKSTREAM)
`ContinuityPlan.vue`, `ContinuityStewards.vue`, `SupportStewards.vue`, `ImportantDocuments.vue`, `Vault.vue` — still legacy static prototypes. See `CONTINUITY_GROUP_CONVERSION_PLAN.md` for the plan.

### Gap 5 — Founding Member perks (BLOCKED ON DR. CHAPMAN)
Config exists (`founding_member` section). No DB column or assignment logic. See `AEGIS_CHAPMAN_PENDING_ITEMS.md`.

### Gap 6 — Access tier `max_support_stewards` (BLOCKED ON DR. CHAPMAN)
`.env`-tunable now. Default 1. Dr. Chapman has verbally suggested 2 but needs confirmation.

## 36. Stack & Architecture

_(Unchanged. PHP 8.2, Laravel 12, Vue 3/Inertia, MySQL 8, Cashier 16.6, Stripe PHP 17.3. 77+ models, 127 migrations (up from 123 with batch3's 4 new), 58 enums (up from 55 with 3 new dispute enums), 14 domains + new dispute domain.)_

## 37. File Map (Rev 3 additions/changes only)

### New backend files (batch3)
| File | Purpose |
|---|---|
| `app/Enums/{DisputeStatus,DisputeReason,DisputeResolution}.php` | Dispute enums |
| `app/Enums/InvoiceStatus.php` (updated) | Added `Disputed` case + `isPayable()` helper |
| `app/Models/{Dispute,DisputeMessage}.php` | Dispute models |
| `app/Models/PlanSteward.php` (updated) | Added CS engagement fields |
| `app/Services/DisputeService.php` | `open/reply/resolve/listForUser/listForAdmin` |
| `app/Services/IncidentService.php` (updated) | Added `completeTask/maybeReadyForClosure/verifyClosure/closeWithInvoice/autoClose` |
| `app/Events/Dispute/{DisputeOpened,DisputeReplied,DisputeResolved}.php` | Dispute events |
| `app/Events/Incident/{IncidentReadyForClosure,IncidentClosureVerified,IncidentAutoClosed}.php` | CS engagement events |
| `app/Events/Cs/CsInvoiceAutoGenerated.php` | Auto-invoice event |
| `app/Jobs/IncidentAutoCloseCheckJob.php` | Hourly auto-close scheduler |
| `app/Http/Controllers/{Provider,ContinuitySteward,BusinessPartner,Admin}/DisputesController.php` | 4 dispute controllers |
| `app/Http/Controllers/Concerns/CreatesSetupIntent.php` | Reusable SetupIntent trait |
| `app/Http/Controllers/{Provider,CS,BP}/PaymentMethodSetupController.php` | 3 native add-card controllers |

### New frontend files (batch3)
| File | Purpose |
|---|---|
| `resources/js/components/modals/AddCardModal.vue` | Native Stripe Elements card capture |
| `resources/js/components/modals/OpenDisputeModal.vue` | Reusable dispute open form |
| `resources/js/pages/{admin,provider,cs,bp}/Disputes.vue` | 4 dispute list pages |
| `resources/js/pages/{admin,provider,cs,bp}/DisputeDetail.vue` | 4 dispute detail pages |

### New migrations (batch3)
| Migration | Purpose |
|---|---|
| `2026_07_09_000001_add_stripe_columns_to_cs_invoices` | P0 — `stripe_payment_intent_id`, `stripe_transfer_id` on `cs_invoices` |
| `2026_07_10_000001_add_cs_engagement_fields_to_plan_stewards` | CS engagement contract fields |
| `2026_07_10_000002_create_disputes_table` | Dispute records |
| `2026_07_10_000003_create_dispute_messages_table` | Dispute thread |
| `2026_07_10_000004_add_disputed_to_invoice_statuses` | Extend ENUMs on both invoice tables |

## 38. Deployment Checklist

### Stripe Dashboard
1. **Products** — 10 prices created (per `AEGIS_STRIPE_SETUP.md`)
2. **Billing Portal** — enable update card, view invoices, cancel at period end, switch plans. Return URL: `https://aegis.devlet.tech/provider/settings?section=billing`
3. **Webhook** — events (🆕 `account.updated` added):
   ```
   invoice.payment_succeeded   invoice.payment_failed   invoice.upcoming
   customer.subscription.created   customer.subscription.updated   customer.subscription.deleted
   payment_intent.succeeded   payment_intent.payment_failed
   payment_method.attached   payment_method.detached
   charge.refunded   transfer.created   transfer.paid   transfer.failed
   account.updated
   ```
4. Copy signing secret → `.env` `STRIPE_WEBHOOK_SECRET`

### Server
```bash
git pull
composer install --no-dev
php artisan migrate           # 4 new migrations in batch3
php artisan config:clear
php artisan queue:restart
npm ci && npm run build

# Optional .env tuning (defaults keep current behaviour):
# TIER_ACCESS_MAX_SS=2 (if Dr. Chapman confirms)
# CS_INCIDENT_AUTOCLOSE_DAYS=7
# DISPUTE_RESPONDENT_REPLY_DAYS=5

# Ensure cron runs the scheduler (adds IncidentAutoCloseCheckJob hourly):
* * * * * cd /path/to/aegis && php artisan schedule:run >> /dev/null 2>&1
```

### Pre-launch — remaining tasks
1. Add `storePaymentMethod` methods to CS + BP `SettingsController` (§35 Gap 1) — 30 min
2. Create 7 blade templates for CS engagement + dispute emails (§35 Gap 2) — 2 hr
3. Wire "Open dispute" buttons in Finances tables (§35 Gap 3) — 1.5 hr
4. Confirm `TIER_ACCESS_MAX_SS` with Dr. Chapman — pending
5. Continuity Group Vue rebuild — separate workstream per `CONTINUITY_GROUP_CONVERSION_PLAN.md`

## 39. Appendix — Test Cards, Demo Users & Prototype vs Laravel

_(Test cards + demo users unchanged. `p_sarah` real Stripe sub `sub_1Tr3QvHnj73y5cBfBd6U6JCv`; `p_rehan` fake `sub_demo_rehan_practice`. All demo passwords `Demo1234!`. Stripe account `acct_1OCuB1Hnj73y5cBf`.)_

---

## Quick-Fix Summary (Prioritised) — Rev 3

| Priority | Gap | File | Est time |
|---|---|---|---|
| 🔴 P1 | CS + BP `storePaymentMethod` methods | CS/BP `SettingsController` | 30 min |
| 🟡 P2 | 7 email blade templates | `resources/views/emails/{incident,cs,disputes}/*` | 2 hr |
| 🟡 P2 | "Open dispute" buttons in Finances tables | Provider/CS/BP `Finances.vue` + `Invoices.vue` | 1.5 hr |
| 🟡 P2 | Continuity Group Vue rebuild | 5 legacy Provider pages | Separate workstream |
| 🟢 P3 | Confirm `TIER_ACCESS_MAX_SS` | Dr. Chapman call | 5 min once decided |
| 🟢 P3 | Founding Member perks | Requires 4 product answers | TBD |

---

*Rev 3 — validated against live repo commit `9351e14` on 2026-07-09*
*Rev 2 — commit `2cd19de` on 2026-07-08*
