# Aegis — Complete Billing, Onboarding & System Reference (Canonical)

**Status:** Validated against live repo `main @ 26ea36a` on 2026-07-08
**Companion doc:** `AEGIS-PROJECT-CONTEXT.md` (original project handoff with portal page inventory, schema detail, and decision log)

**Purpose:** Everything a developer or Claude needs to understand Aegis end-to-end — what it is, who the roles are, the continuity-plan workflow, how someone registers and pays, which features are locked for which role, and how ongoing billing is managed. Treat this file + `AEGIS-PROJECT-CONTEXT.md` as the complete project reference.

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
29. Known Bugs Fixed
30. Remaining Gaps & Exactly Where To Fix Them
31. Stack & Architecture
32. File Map
33. Deployment Checklist
34. Appendix — Test Cards, Demo Users & Prototype vs Laravel

---
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

## 2. The Five Roles

Role stored in `users.role` (enum) + `user_role_assignments` join row (multi-role support).

| Role | Enum value | Portal prefix | Dashboard route | Pays? |
|---|---|---|---|---|
| **Practitioner** (Provider) | `practitioner` | `/provider` | `provider.dashboard` | Always |
| **Continuity Steward** (CS) | `continuity_steward` | `/continuity-steward` | `cs.dashboard` | Business only |
| **Support Steward** (SS) | `support_steward` | `/support-steward` | `ss.dashboard` | Never (free) |
| **Business Partner** (BP) | `business_partner` | `/business-partner` | `bp.dashboard` | Always |
| **Admin** | `admin` | `/admin` | `admin.dashboard` | N/A |

### 2.1 Practitioner (Provider)
Licensed clinician — the portal owner. Builds the plan, designates stewards, uploads credentials to the vault.
- **Verb:** Designates, Configures, Attests. **Must NOT:** Trigger their own critical incident.
- **Subscription:** Access ($29) or Practice ($49). **Public profile:** Yes (`/provider/<slug>`).

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
- **Subscription:** $69/mo or $690/yr. **Public profile:** Yes (`/business/<slug>`).
- **Special:** BP does NOT see the cross-portal switcher — BPs don't bridge into continuity portals.

### 2.5 Anonymous
No login. Sees only public profile sections + sign-in CTAs.

## 3. Role Sub-Types & Paths

### Continuity Steward — `cs_path` / `cs_account_type`

| Path | Account type | Pays? | Public profile | Scope |
|---|---|---|---|---|
| `business` | `business` | $49/mo or $429/yr | Yes | 2–40 practitioners |
| `invited` | `invited` | Free | No | 1 inviting practitioner |
| (manual) | `enterprise` | Custom quote | Yes | 41+ (mailto, no automation) |

Invited CS registers with a required **invitation code**, is covered by the inviting practitioner, sees only their one practitioner, no Stripe/finance block. Enterprise CS is a `mailto:` route — no automated subscription. `UserRole` enum: `.label()`, `.portal()`, `.routePrefix()`, `.middleware()`, `.fromPortal()`. CS sub-type in `CsAccountType` enum: `invited`, `business`, `enterprise`.

### Business Partner — `bp_type`

| Type | Description | Special module |
|---|---|---|
| `agency` | Multi-person firm | Team Management (member per milestone) |
| `freelancer` | Solo operator | No team module; personal 1099/SSN |

Same subscription; difference is UI/module scope.

### Support Steward — no sub-type
Invitation-only. Registration shows an invite-gate wall (blocked). No self-serve signup, no payment.

## 4. Multi-Role Identity & The Three-Tier Visibility Rule

### Multi-role (Marcus Chen pattern)
One `users` row can hold multiple `user_role_assignments`. Marcus Chen is both `continuity_steward` and `business_partner`:
- `/steward/marcus-chen` resolves (Business CS)
- `/business/marcus-chen` resolves (BP)
- `/provider/marcus-chen` → 404 (no practitioner role)

The URL prefix decides which face to render. The header portal switcher shows only the portals the user has. BP-only users don't see the switcher (BP doesn't bridge to continuity).

### Three-tier visibility (all public profiles)
| Tier | Condition | Sees |
|---|---|---|
| Anonymous | No login | Public sections + sign-in CTAs |
| Logged-in (any role) | Any signed-in user | Full profile: contact, metrics, activity |
| Owner | Viewing own profile | Adds Edit + Visibility panel |

Once signed in, **role doesn't matter for browsing** — CS and BP viewing the same Provider profile see identical content. Role gating happens at slug-resolution (Invited CS are not publicly resolvable), not at view-render.

## 5. The Continuity-Plan Lifecycle (Core Workflow)

Four phases: **Setup → Standby → Activation → Closure**, with attestation loops that gate phase advancement.

### Phase 1 — Setup
The **Continuity Plan Builder** is a 7-row × N-column grid (row = incident type, column = config). Per incident type the practitioner sets: authorized SS (who can trigger), authorized CS (who can verify/execute), documentation required (e.g. death certificate), CS task list, SS task list. Data flows from `continuity_plans` + `plan_incident_configs` + `plan_tasks`; on save, CS/SS task pages and vault zones update automatically.

**Stewards designate & sign:**
1. Practitioner signs the plan (e-signature)
2. Plan → CS for countersignature (CS "Important Documents" page)
3. CS countersigns → plan **Active**
4. SS receives read-only copy
5. Annual review re-attestation every year

**Three attestation loops** (tracked as dashboard chips; missing ones block active status):
- Practitioner attests info is accurate
- CS certifies tasks (whole-list + optional per-task exception flag — Carizma's decision)
- SS certifies tasks

### Phase 2 — Standby
Practitioner uses the platform normally; SS monitors; CS waits. **Vault sealed** (Standard Documents zone only). Every action logs to `activity_events`. `?emergency=false` previews this state.

### Phase 3 — Activation
1. **SS triggers** — Critical Incident Log, picks incident type (authorized types only), writes narrative + contact-attempt log. Creates `critical_incidents` row (`status=reported`).
2. **CS receives alert** — email + in-portal banner. Views SS report + doc checklist + provider contacts. Vault still sealed.
3. **CS verifies** — legal acknowledgement modal (approved Notice text + False Reporting Warning), uploads required docs, activates.
4. **System fires:** timestamps `verified_at/verified_by`; **unlocks vault** (Emergency + Roster + Credentials); **auto-generates `incident_tasks`** (clones matching `plan_tasks`); notifies SS + emergency contacts + alternates; writes activity events across portals; sets `?emergency=true`.
5. **CS executes** — works the task list. Every check-off logged. Vault read-only: clipboard auto-clear, 8-sec password masking, every view/download/reveal logged.

### Phase 4 — Closure
CS submits Final Completion Report → `status=closed`, closure summary → SS log, next-of-kin notified, vault re-sealed, audit-ready PDF generated.

### Cross-portal communication
Three shared records: `continuity_plans` (practitioner authors; SS+CS read), `critical_incidents` (SS triggers; CS updates), `activity_events` (unified feed — header bell + activity page). Messaging is direct, thread mirrored to both inboxes. Continuity Contacts pinned atop every inbox.

## 6. The Seven Critical-Incident Types

`IncidentType` enum (`app/Enums/IncidentType.php`):

| Type | Enum value | Always-on? |
|---|---|---|
| Death | `death` | Always |
| Incapacitation (short) | `incapacitation` | Always |
| Extended Absence / Long-term | `extended_absence` | Always |
| Missing Person | `missing` | Opt-in |
| Detainment | `detainment` | Opt-in |
| Natural Disaster | `natural_disaster` | Opt-in |
| Geopolitical / Conflict | `geopolitical` | Opt-in |

The first three are always applicable. The four opt-in types must be explicitly enabled in the plan builder. No freeform types.

## 7. The Five Portals

### Provider Portal (`/provider`)
Where all data originates.
```
Main:         Overview · Dashboard · My Profile
My Practice:  Job Postings · Referrals · Integrative Network · My Services
Continuity:   Continuity Plan (Builder) · Continuity Stewards · Support Stewards ·
              Important Documents · Document Vault · Finances
Communication: Messages · Activity Log
Explore:      News & Resources · Events
Account:      Settings
```
Key pages: the **Continuity Plan Builder** (7-row config grid — the single most important page in Aegis), CS/SS designation wizards (4-step and 3-step), 4-zone Document Vault (Standard/Emergency/Roster/Credentials), Settings (subscription + billing management). Finances: Stripe Connect direct — MAAT never holds funds; escrow language removed per attorney direction.

Access-tier restrictions: `referrals` + `services` shown locked (click → upgrade modal); `job-postings` hidden entirely. Dashboard: plan readiness ring (signed/CS-certified/SS-certified chips), license/insurance countdowns, CEU tracker, action-required panel, network carousel.

### Continuity Steward Portal (`/continuity-steward`)
Dormant in standby; central during incidents.
```
Main:             Overview · Dashboard · My Profile
My Work:          My Tasks · My Providers · Important Documents · Finances
Critical Incident: Continuity Management (verify cockpit) · Document Vault
Communication:    Messages · Activity Log
Account:          Settings
```
**Path-aware rendering:** every page detects Invited CS (4-signal composite: `?invited=true` → `cs_path=invited` → `cs_account_type=invited` → non-empty `linked_provider_id`) and hides multi-practitioner UI. **Continuity Management** is the verify cockpit — enforces documentation-required uploads before verification proceeds. Invited CS finances show "covered by your linked practitioner" card; Business CS sees full Stripe/invoices block.

### Support Steward Portal (`/support-steward`)
The day-to-day monitor; triggers incidents.
```
Main:      Overview · Dashboard · My Profile
Plans:     My Providers
Activation: My Tasks · Important Documents · Continuity Stewards (read-only) · Critical Incident Log
Communication: Messages · Activity Log
Account:   Settings
```
**Critical Incident Log** is the trigger page: incident-type dropdown (opt-in types disabled if practitioner hasn't enabled them), narrative report, contact-attempt log, doc upload. Free role — no billing in Settings.

### Business Partner Portal (`/business-partner`)
Upwork-style marketplace, independent of continuity.
```
Main:      Overview · Dashboard · My Profile
Work:      Find Jobs · Contracts · Proposals · Milestones
Financial: Finances · Invoices · Payment Setup
Communication: Messages · Notifications
Account:   Settings
Team:      Team Management (Agency only)
```
Payments via Stripe Connect destination charges. Agency adds Team Management (member per milestone, owner-review-then-submit or grant direct-submit). Finances shows **payout** data (Connect earnings), not subscription.

### Admin Portal (`/admin`)
User management, password resets, platform oversight. Free role. Full implementation ongoing. Middleware: `auth, verified.email, admin`.

---
---

# PART B — IDENTITY & ACCESS

## 8. Who Pays, Who's Free

The "needs a subscription" rule is duplicated in **three** places that must stay in sync:
1. `OnboardingController::requiresSubscription()`
2. `EnsureSubscriptionActive` middleware
3. `LoginController::resolvePostLoginDestination()`

All encode:
```
needsSubscription =
       role == 'practitioner'
    OR role == 'business_partner'
    OR (role == 'continuity_steward' AND cs_account_type == 'business')
```
Invited CS, SS, Admin — free, skip all subscription gates. `config/aegis.php` → `paid_roles`: `practitioner`, `business_partner`, `continuity_steward_business`.

## 9. The Complete Registration → Onboarding Flow

`resources/js/pages/auth/Register.vue` is a multi-step wizard; step count varies by role.

```
STEP 0: Role selection
  4 cards with pricing badges:
    Practitioner        → "Subscription required"
    Business Partner    → "Subscription required"
    Continuity Steward  → "Free via invitation or $49/mo"
    Support Steward     → "Invitation only · Free"

STEP 1: Sub-path (role-conditional)
    Practitioner        → SKIP (no sub-path)
    Continuity Steward  → Business CS ($49) vs Invited CS (free + code field)
    Business Partner    → Agency vs Freelancer
    Support Steward     → INVITE GATE WALL (blocked, can't proceed past here)

STEP 2: Use-case ("What brings you to Aegis?")
    Multi-select checkboxes → stored as JSON in user_meta.
    (SS skips — gated by the wall in step 1.)

STEP 3: Account creation form
    display_name, email, password+confirm, phone
    + hidden: role, bp_type, cs_path, invitation_code
    + Terms checkbox, email opt-in
    → POST /register

RegisterController@store → AuthService::register()
    · users row: id = 'ae_' + Str::lower(Str::random(12))
    · role, cs_account_type (from cs_path), bp_type set
    · tier = 'access' for practitioner, else null
    · verified = 0, slug auto-generated
    · user_role_assignments row (is_default=1)
    · ~18 notify_* meta rows seeded
    · Auth::login($user) + VerifyEmailController::sendVerificationEmail()
    → Inertia::location(verification.notice)

    ═══ EMAIL VERIFICATION GATE ═══  (§10)

    Paid roles  → /onboarding/plan
    Free roles  → portal dashboard (skip billing entirely)

    ═══ PLAN → PAYMENT → SUBSCRIBE ═══  (§18)
```

**Step counts** (`Register.vue`): SS = 2 steps (role → gate wall), Practitioner = 3 (role → use-case → account), CS/BP = 4 (role → sub-path → use-case → account).

**Validation** (`RegisterRequest`):
- Core (all roles): `display_name`, `email` (unique), `password` (min 8, confirmed), `phone` (nullable)
- `role` — required, in the 4 self-serve roles (admin can't self-register)
- `bp_type` — required if BP, in [freelancer, agency]
- `cs_path` — required if CS, in [business, invited]
- `invitation_code` — required if CS + invited path
- `tier` — optional, in [access, practice] (fast-path/demo)

## 10. Email Verification Gate

After registration the user is logged in but `verified = 0`. Every portal route group carries `verified.email` (`EnsureEmailVerified`). Until verified:
- `/onboarding/*` and all dashboard routes redirect to `verification.notice`
- `GET /email/verify/{id}/{hash}` (signed URL) flips `users.verified = 1`
- Resend at `POST /email/verification-notification`
- Once verified: paid roles → `/onboarding/plan`, free roles → dashboard

## 11. Login Redirect Logic

`LoginController::resolvePostLoginDestination()` — 3-step decision on every login:
```
1. if (!verified)                                   → verification.notice
2. if (needsSubscription && !hasActiveSub)          → onboarding.plan
3. else                                             → portal dashboard for their role
```
"Active sub" = `stripe_status` in `[active, trialing, past_due]`. A returning user who abandoned onboarding lands back on the plan picker. Also enforces: rate limiting (5 attempts/IP+email), lock check, deactivation check, failed-login auto-lock (5 fails → `locked_at`).

## 12. Account Lock / Deactivation

`CheckAccountLocked` (alias `check.locked`) on every authenticated portal route:
- `locked_at` set → force logout, redirect to login with `locked_reason`
- `deactivated_at` set → force logout, "account deactivated"
- Auth-flow routes (login, logout, password reset, MFA) exempted so recovery still works
- Self-service closure: `SettingsController@deleteAccount` sets `deactivated_at`, deletes tokens, logs out

---
---

# PART C — THE MONEY

## 13. The Two Stripe Integrations

| System | Package | Money Direction | Lands In | Covered Here? |
|---|---|---|---|---|
| **Subscription Billing** | Laravel Cashier | Customer → Aegis | MAAT's Stripe account | This doc |
| **Connect Payouts** | Stripe PHP SDK | Provider→BP, Client→Provider | Third-party connected accounts | `AEGIS_PAYMENTS_FINANCE.md` |

`STRIPE_KEY`/`STRIPE_SECRET` are MAAT's platform credentials — subscription revenue lands there. Connect payouts flow *through* Aegis via destination charges (Aegis nets $0). `StripeEventListener` handles both event families; this doc covers only the subscription half.

## 14. Package & Infrastructure Layer

| Component | Value | Status |
|---|---|---|
| `laravel/cashier` | `^16.6` | OK |
| `stripe/stripe-php` | `^17.3` | OK |
| `User` model | `use Billable` trait | OK (`User.php:23`) |
| `CashierServiceProvider` | Registered | OK (`bootstrap/providers.php:7`) |
| Webhook route | `POST /stripe/webhook` (Cashier auto) | OK |
| Listener binding | `WebhookReceived` → `StripeEventListener` | OK (`AppServiceProvider:163`) |
| `config/cashier.php` | Keys + path=`stripe` | OK |

Webhook chain:
```
Stripe → POST /stripe/webhook
  → Cashier VerifyWebhookSignature (validates STRIPE_WEBHOOK_SECRET)
  → Cashier WebhookController@handleWebhook
  → Laravel\Cashier\Events\WebhookReceived::dispatch($payload)
  → AppServiceProvider binds → StripeEventListener::handle()
  → logs to stripe_webhook_events + routes per type
```

## 15. Database Schema (Billing Tables)

### `subscriptions` (Cashier-managed, UUID-patched)
```
id             bigint PK
user_id        VARCHAR(255)    ← patched for Aegis UUID PKs
type           string          (always 'default')
stripe_id      string unique   (sub_xxx)
stripe_status  string          (active|trialing|past_due|canceled|...)
stripe_price   string          (price_xxx — BASE plan price only)
quantity       integer
trial_ends_at  timestamp
ends_at        timestamp       (grace period marker when cancelled)
```
Four migrations shaped this: `2024_01_03_000001` (wrong polymorphic schema) → `2026_07_07_092246` (recreate with `user_id`) → `100000` (widen to VARCHAR for UUID) → `100001` (drop `billable_*` cols). On a fresh `migrate` the table ends correct. **Why it matters:** Cashier v16 queries by `user_id` = `users.id` which is UUID `CHAR(36)`; the default Cashier `foreignId()` is BIGINT and can't hold it.

### `subscription_items`
One row per price on a subscription. Base plan = one item; MAAT add-on = a second item on the same subscription. Has `stripe_price`, `stripe_product`, `meter_id`, `meter_event_name` (v16 metered columns).

### `users` — billing-relevant columns
| Column | Type | Notes |
|---|---|---|
| `stripe_id` | string(64) | Stripe Customer ID (`cus_xxx`) |
| `pm_type` | string(50) | Default card brand |
| `pm_last_four` | string(4) | Default card last 4 |
| `trial_ends_at` | timestamp | Trial marker (unused) |
| `tier` | **enum('access','practice')** | **Practitioner-only. Cannot hold BP/CS tiers.** |
| `maat_addon` | tinyint | 1 if MAAT active |
| `services_mode` | tinyint | 1 if services mode enabled |

**`tier` enum is the key constraint.** Only `access`/`practice`. BP and CS "tier" lives in `role`/`cs_account_type`/`bp_type`, NOT `tier`. Writing `tier='business_partner'` or `'cs_business'` corrupts the row (see §29 Bug #3).

### `stripe_webhook_events`
Every webhook logged with UUID PK, idempotent by `stripe_event_id`. Prevents double-processing on Stripe retries.

## 16. The Pricing Model (All Roles)

Source of truth: `config/aegis.php` → `pricing` (cents). Confirmed June 2026.

### Practitioner tiers

| Tier | Monthly | Annual (per mo) | Annual total | Unlocks |
|---|---|---|---|---|
| **Continuity Access** | $29 | $23 | $276/yr | 1 CS, 1 SS, Vault, all incident types. Referrals + Services LOCKED. Job Postings HIDDEN. |
| **Continuity Practice** | $49 | $39 | $468/yr | 2 CS, 4 SS, Referrals, Services Mode, full Network, Job Postings. |

Annual saves 20%.

### Add-on — MAAT Professional CS Service

| | Monthly | Annual (per mo) | Annual total |
|---|---|---|---|
| **MAAT** | +$29 | +$23 | +$276/yr |

Requires Practice. MAAT-certified, licensed, insured CS — 4-hour emergency response, annual recertification.

### Business Partner

| | Monthly | Annual |
|---|---|---|
| **BP** | $69/mo | $690/yr |

Covers both Agency and Freelancer.

### Business Continuity Steward

| | Monthly | Annual | Scope |
|---|---|---|---|
| Business CS | $49/mo | $429/yr (~27% save) | 2–40 practitioners |
| Enterprise CS | Custom quote | — | 41+ (mailto) |
| Invited CS | Free | — | Covered by practitioner |

### Support Steward — Always free, invitation-only.

### Founding Member perks (awaiting final scoping)
First 100 providers: 2 additional CS free for life, 2 marketing ads/yr free. UI band exists in Settings; not yet wired to billing logic.

## 17. The Price ID System (env → config → frontend)

Three layers must agree or subscriptions break.

### Layer 1 — `.env` (10 IDs, all present in sandbox)
```
STRIPE_PRICE_ACCESS_MONTHLY        = price_1TqSldHnj73y5cBf...
STRIPE_PRICE_ACCESS_ANNUAL         = price_1TqSpNHnj73y5cBf...
STRIPE_PRICE_PRACTICE_MONTHLY      = price_1TqSraHnj73y5cBf...
STRIPE_PRICE_PRACTICE_ANNUAL       = price_1TqSrxHnj73y5cBf...
STRIPE_PRICE_BP_MONTHLY            = price_1TqSsGHnj73y5cBf...
STRIPE_PRICE_BP_ANNUAL             = price_1TqSt2Hnj73y5cBf...
STRIPE_PRICE_CS_BUSINESS_MONTHLY   = price_1TqStZHnj73y5cBf...
STRIPE_PRICE_CS_BUSINESS_ANNUAL    = price_1TqSu2Hnj73y5cBf...
STRIPE_PRICE_MAAT_MONTHLY          = price_1TqSuSHnj73y5cBf...
STRIPE_PRICE_MAAT_ANNUAL           = price_1TqSuoHnj73y5cBf...
```
Live-mode IDs are commented out in `.env` ready for the production switch.

### Layer 2 — `config/aegis.php` → `stripe_price_to_tier`
Reverse map (price ID → internal tier) used by webhooks:
```php
ACCESS_MONTHLY/ANNUAL      => 'access',
PRACTICE_MONTHLY/ANNUAL    => 'practice',
BP_MONTHLY/ANNUAL          => 'business_partner',
CS_BUSINESS_MONTHLY/ANNUAL => 'cs_business',
MAAT_MONTHLY/ANNUAL        => 'maat_addon',
```

### Layer 3 — `app.blade.php` → `window.__AEGIS_CONFIG__`
All 10 IDs injected into the JS global. `resolveStripePrice()` in `OnboardingPayment.vue` reads them:
```
'access-monthly'      → ACCESS_MONTHLY
'access-annual'       → ACCESS_ANNUAL
'practice-monthly'    → PRACTICE_MONTHLY
'practice-annual'     → PRACTICE_ANNUAL
'monthly-monthly'     → BP_MONTHLY   (BP tier value IS "monthly")
'annual-annual'       → BP_ANNUAL    (BP tier value IS "annual")
'cs_business-monthly' → CS_BUSINESS_MONTHLY
'cs_business-annual'  → CS_BUSINESS_ANNUAL
```
Key = `${tier}-${billing}`. The tier value submitted from `OnboardingPlan.vue` MUST match these keys exactly (Bug #2 was CS submitting `business_cs`; map expected `cs_business` — now fixed).

---
---

# PART D — LIFECYCLE

## 18. Onboarding Flow (First Subscription) — Step by Step

```
GET /onboarding/plan → OnboardingPlan.vue
  Role-branched UI (isPractitioner / isBP / isBusinessCS):
    Practitioner  — Access vs Practice + monthly/annual + optional MAAT
    BP            — Monthly vs Annual
    CS Business   — single plan + monthly/annual
  submit() → form { tier, billing, addons[] }
        ↓ POST /onboarding/plan
OnboardingController@storePlan
  · Validates tier/billing/addons
  · session: onboarding_plan = { tier, billing, addons, role }
  → /onboarding/payment
        ↓ GET /onboarding/payment
OnboardingController@showPayment
  · Ensures Stripe customer exists (creates + clears fake demo cus_* IDs)
  · createSetupIntent() → client_secret
  · Renders OnboardingPayment.vue { clientSecret, stripeKey, plan }
  · Any Stripe error → redirect back with actionable message (no 500)
        ↓ (user enters card)
OnboardingPayment.vue
  · Stripe.js split card elements (disableLink: true)
  · stripe.confirmCardSetup(clientSecret, cardNumber) → pm_xxx
  · resolveStripePrice() → price_xxx from window.__AEGIS_CONFIG__
  · POST /onboarding/subscribe { payment_method_id, price_id, addons }
        ↓
OnboardingController@subscribe
  · updateDefaultPaymentMethod(pm_xxx)
  · SubscriptionService::subscribe(user, price, pm)
      → user->newSubscription('default', price)->create(pm)
  · If addons includes 'maat':
      billing = session onboarding_plan.billing
      toggleMaatAddon(user, true, billing)
      → adds MAAT price as 2nd subscription_item (same invoice)
  · Syncs users.tier (GUARDED: only writes 'access' or 'practice')
  · Fires welcome email, clears session
  → portal dashboard
        ↓ (Stripe fires webhooks in parallel)
StripeEventListener
  · customer.subscription.created → Cashier syncs DB
  · customer.subscription.updated → tier + MAAT state synced
  · invoice.payment_succeeded     → PaymentReceived → email
```

**Free roles** (Invited CS, SS): `showPlan` calls `requiresSubscription()` → false → redirects straight to dashboard. They never see plan/payment pages.

**Files:** `OnboardingPlan.vue`, `OnboardingPayment.vue`, `OnboardingController`, `SubscriptionService`.

## 19. Post-Signup Management (Provider)

Fully built in `resources/js/pages/provider/Settings.vue`, backed by `SettingsController`. Data for both panels comes from `SubscriptionService::getFullSubscriptionData()` — makes live Stripe API calls on every Settings page load.

### "Subscription & Plan" panel (`section === 'billing'`)

| Action | Route | Service call |
|---|---|---|
| View plan + next invoice | — | `getFullSubscriptionData` (live Stripe) |
| Preview annual vs monthly | — | client-side toggle |
| Upgrade Access→Practice | `POST settings/subscription/swap` | `changePlan` → `upgrade` → `swapAndInvoice` |
| Downgrade Practice→Access | same | `changePlan` → `downgrade` → `noProrate()->swap` |
| Switch monthly↔annual | same | `changePlan` (auto-detect direction) |
| Add MAAT | `POST settings/subscription/maat` | `toggleMaatAddon(true)` |
| Remove MAAT | same | `toggleMaatAddon(false)` |
| Cancel | `POST settings/subscription/cancel` | `cancel()` (period-end grace) |
| Reactivate (grace period) | `POST settings/subscription/resume` | `reactivate()` |

Smart states: cancelled-in-grace → amber banner with real end-date + Reactivate; `past_due` → red banner; plan buttons auto-label "Upgrade to this plan" / "Switch to this plan" / "Switch to annual" / "Your current plan"; MAAT "Add" only enabled on Practice tier.

### "Billing & Invoices" panel (`section === 'invoices'`)

| Action | Route |
|---|---|
| Open Stripe billing portal | `GET settings/billing-portal` |
| Set default card | `POST settings/payment-method/default` |
| Remove card | `DELETE settings/payment-method` |
| View live invoices (last 12) + PDF download | — (from `getFullSubscriptionData`) |

The Stripe Billing Portal is the fallback for anything not in custom UI.

### Full route map (Provider)
```
GET    /provider/settings/billing-portal        → provider.settings.billing.portal
POST   /provider/settings/subscription/swap     → provider.settings.subscription.swap
POST   /provider/settings/subscription/cancel   → provider.settings.subscription.cancel
POST   /provider/settings/subscription/resume   → provider.settings.subscription.resume
POST   /provider/settings/subscription/maat     → provider.settings.subscription.maat
POST   /provider/settings/payment-method        → provider.settings.payment.store
POST   /provider/settings/payment-method/default → provider.settings.payment.default
DELETE /provider/settings/payment-method        → provider.settings.payment.remove
```

## 20. Post-Signup Management (BP & CS) — The Gap

**BP and CS Business can subscribe during onboarding, but have zero subscription-management UI afterward.**
- `business-partner/Settings.vue` — 70-line stub. Account + notifications only. No billing panel.
- `continuity-steward/Settings.vue` — 57-line stub. Same.
- Their `FinancesController@index` shows **payout data** (Connect earnings), NOT their subscription.
- No cancel/swap/resume routes exist for BP or CS.

The backend `SubscriptionService` is fully role-agnostic. Closing the gap = copy Provider controller methods + Vue panel. See §30 for exact instructions.

## 21. The Webhook Engine

`StripeEventListener::handle()` — logs every event to `stripe_webhook_events` (idempotent), then routes:

| Event | Handler | Action |
|---|---|---|
| `invoice.payment_succeeded` | `handlePaymentSucceeded` | `PaymentReceived` event → email |
| `invoice.payment_failed` | `handlePaymentFailed` | Dunning notification + `PaymentFailed` event |
| `invoice.upcoming` | `handleInvoiceUpcoming` | Logs (renewal email = TODO §30 Gap 3) |
| `customer.subscription.created` | `handleSubscriptionCreated` | No-op (Cashier syncs DB) |
| `customer.subscription.updated` | `handleSubscriptionUpdated` | Sync `users.tier` (guarded to access/practice) + MAAT state |
| `customer.subscription.deleted` | `handleSubscriptionCancelled` | Cancellation activity log |
| `payment_method.attached` | `handlePaymentMethodAttached` | Sync `pm_type`/`pm_last_four` |
| `payment_method.detached` | `handlePaymentMethodDetached` | Clear them |
| `payment_intent.succeeded` | `handlePaymentIntentSucceeded` | **Connect** — mark `BpPayout` paid |
| `payment_intent.payment_failed` | `handlePaymentIntentFailed` | **Connect** — mark failed + alert |
| `charge.refunded` | `handleChargeRefunded` | Log |
| `transfer.paid` | `handleTransferPaid` | **Connect** — mark payouts paid |
| `transfer.failed` | `handleTransferFailed` | **Connect** — mark payouts failed |

**MAAT auto-sync:** if MAAT is added/removed via the Stripe Portal (outside the app), `handleSubscriptionUpdated` scans all subscription items for a MAAT price ID and keeps `users.maat_addon` in sync.

## 22. Proration Rules

| Change | Method | Stripe behavior |
|---|---|---|
| Upgrade (Access→Practice, monthly→annual) | `upgrade()` → `swapAndInvoice()` | Prorated charge **immediately** |
| Downgrade (Practice→Access, annual→monthly) | `downgrade()` → `noProrate()->swap()` | New price **next cycle**, no refund |

`changePlan()` auto-detects direction by normalizing both prices to **per-day cost** (`pricePerDay()` helper in `SubscriptionService`), so annual $468/yr ≈ $1.28/day vs monthly $49 ≈ $1.63/day. Annual→monthly correctly counts as a downgrade in value (no immediate charge), even though the monthly sticker looks higher.

## 23. The MAAT Add-on

Not a separate subscription — a second **line item** on the practitioner's `default` subscription (same invoice as the base plan).
- `toggleMaatAddon(user, true, billing)` → `$sub->addPrice($maatPriceId)` guarded by `hasPrice()` to prevent duplicates
- Removal loops both monthly + annual MAAT prices, removes whichever is present
- Billing period auto-matched to base plan (annual base → annual MAAT)
- Gated to Practice tier — Access users see "Requires Continuity Practice"
- Sets `users.maat_addon = 1/0`, fires `MaatAddonChanged` event

**Onboarding path:** `OnboardingController@subscribe` adds MAAT immediately after creating the base subscription.
**Post-signup path:** `SettingsController@toggleMaat` → same service method.

---
---

# PART E — FEATURE GATING

## 24. Tier-Based Feature Locks (Access vs Practice)

Source: `config/aegis.php` → `tier_limits`.

| Capability | Access tier | Practice tier |
|---|---|---|
| Max Continuity Stewards | 1 | 2 |
| Max Support Stewards | 1 | 4 |
| Referrals | **LOCKED** (shown, click → upgrade) | ✅ |
| Services Mode | **LOCKED** (shown, click → upgrade) | ✅ |
| Integrative Network search | Limited results | Full |
| Job Postings | **HIDDEN** (not rendered) | ✅ |

**Three lock states:**
- **Locked** — page/button visible but greyed; clicking opens the upgrade modal
- **Hidden** — not rendered at all for Access tier
- **Limited** — visible but reduced results/scope

## 25. Role-Based Route Gating (Middleware Map)

```
Provider    auth, verified.email, subscription.active, role:practitioner, check.locked
CS          auth, verified.email, subscription.active, role:continuity_steward, check.locked
SS          auth, verified.email, role:support_steward, check.locked
BP          auth, verified.email, subscription.active, role:business_partner, check.locked
Admin       auth, verified.email, admin
Public      auth, verified.email, subscription.active
Onboarding  auth  (no verified.email — those pages ARE the verification flow)
```

**Key nuances:**
- CS group carries `subscription.active`, but `EnsureSubscriptionActive` internally exempts Invited CS (`cs_account_type != 'business'`) — Invited CS pass through free.
- SS group deliberately omits `subscription.active`.
- Services routes are wrapped in a nested `services.mode` group (`EnsureServicesMode`): requires `tier=practice` AND `services_mode=1`. The session-complete route is OUTSIDE this group — any provider who booked a session can confirm it.

**Middleware aliases** (`bootstrap/app.php`):
```
role              → EnsureRole
admin             → EnsureAdminRole
plan.active       → EnsurePlanActive
incident.active   → EnsureIncidentActive
services.mode     → EnsureServicesMode
check.locked      → CheckAccountLocked
verified.email    → EnsureEmailVerified
subscription.active → EnsureSubscriptionActive
demo              → ImpersonateForDemo
```

## 26. Frontend Lock Mechanism (Upgrade Modal)

Composable `usePortal()` (`resources/js/composables/usePortal.js`) provides `requiresTier(tier, fn?)`:
```js
function requiresTier(tier, fn = null) {
    if (tier === 'practice' && auth.isAccessTier) {
        ui.openModal('upgradeModal')   // blocks + shows upgrade CTA
        return false
    }
    if (typeof fn === 'function') fn()
    return true
}
```
Usage: `portal.requiresTier('practice', () => doThing())` — the gated function only runs if the tier passes. `useUpgrade()` composable exposes `openUpgradeModal()` directly. `AegisUpgradeModal` is globally registered and driven by the Pinia `ui` store.

## 27. Page-Level Access Matrix (Provider Portal)

Per `AEGIS-PROJECT-CONTEXT.md` §8.7 (confirmed in `config/aegis.php`):

| Pages | Access tier | Practice tier |
|---|---|---|
| Dashboard, Profile, Network, Continuity Plan, CS/SS management, Vault, Finances, Messages, Activity, Settings | ✅ | ✅ |
| Referrals, Services | LOCKED (shown greyed) | ✅ |
| Job Postings | HIDDEN (not rendered) | ✅ |

Lock flag drives both the greyed visual treatment and the click interceptor → `requiresTier('practice')` → upgrade modal.

---
---

# PART F — OPERATIONS

## 28. Full Lifecycle Coverage Matrix

| Scenario | Practitioner | BP | CS Business |
|---|---|---|---|
| Register + role/path select | ✅ | ✅ | ✅ |
| Use-case step | ✅ | ✅ | ✅ |
| Email verification gate | ✅ | ✅ | ✅ |
| First subscription (monthly) | ✅ | ✅ | ✅ (fixed) |
| First subscription (annual) | ✅ | ✅ | ✅ (fixed) |
| Subscribe with MAAT | ✅ | N/A | N/A |
| Upgrade tier | ✅ | N/A (one tier) | N/A (one tier) |
| Downgrade tier | ✅ | N/A | N/A |
| Switch monthly↔annual | ✅ | ❌ no UI | ❌ no UI |
| Add/remove MAAT post-signup | ✅ | N/A | N/A |
| Cancel subscription | ✅ | ❌ no UI | ❌ no UI |
| Reactivate in grace | ✅ | ❌ no UI | ❌ no UI |
| Update payment method | ✅ | ❌ no UI | ❌ no UI |
| View invoices + PDF download | ✅ | ❌ no UI | ❌ no UI |
| Auto-renewal success/fail (webhook) | ✅ | ✅ | ✅ |
| Payment-method webhook sync | ✅ | ✅ | ✅ |
| Renewal reminder email (7d) | ⚠️ logged, no email | ⚠️ | ⚠️ |

Backend `SubscriptionService` is role-agnostic. Gap is purely frontend UI + routes for BP/CS.

## 29. Known Bugs Fixed

**Bug #2 (FIXED)** — `OnboardingPlan.vue@submit()` submitted `tier='business_cs'`, but `resolveStripePrice()` keyed the map `cs_business`. CS Business checkout produced an empty price ID → subscription silently failed.
Fix: changed submit to `tier = 'cs_business'`.

**Bug #3 (FIXED)** — `handleSubscriptionUpdated` wrote `$user->update(['tier' => $tier])` for any tier, including `business_partner` and `cs_business`. But `users.tier` is `enum('access','practice')`. On strict MySQL this throws; on non-strict it silently corrupts the row on every BP/CS subscription-update webhook.
Fix: guarded to `in_array($tier, ['access', 'practice'])`.

**Bug #1 (non-bug)** — CS Business onboarding was flagged missing in an old audit; it IS built in `OnboardingPlan.vue` (branches on `isBusinessCS`). It just carried Bug #2.

## 30. Remaining Gaps & Exactly Where To Fix Them

### Gap 1 — BP subscription management UI (HIGH)
**Where:** `resources/js/pages/business-partner/Settings.vue` (70-line stub)

Add:
1. A "Subscription & Billing" `AegisCard` showing current plan, next invoice, cancel/resume, monthly↔annual swap, "Manage in Stripe" link.
2. Routes in the `business-partner` group:
   ```php
   Route::get('/settings/billing-portal',      [BpSettingsController::class,'billingPortal'])->name('settings.billing.portal');
   Route::post('/settings/subscription/cancel',[BpSettingsController::class,'cancelPlan'])->name('settings.subscription.cancel');
   Route::post('/settings/subscription/resume',[BpSettingsController::class,'resumePlan'])->name('settings.subscription.resume');
   Route::post('/settings/subscription/swap',  [BpSettingsController::class,'swapPlan'])->name('settings.subscription.swap');
   ```
3. Copy Provider's `billingPortal`, `cancelPlan`, `resumePlan`, `swapPlan` methods into `BusinessPartner/SettingsController` — 1:1 copy, service is role-agnostic.
4. `index()` passes `subscription => $this->subscriptions->getFullSubscriptionData($user)`.

Est: ~2 hrs.

### Gap 2 — CS Business subscription management UI (HIGH)
**Where:** `resources/js/pages/continuity-steward/Settings.vue` (57-line stub)

Same as Gap 1. One tier (swap = monthly↔annual only). **Guard:** only show billing panel if `cs_account_type === 'business'` — Invited CS must never see a billing panel.

Est: ~1.5 hrs.

### Gap 3 — Renewal reminder email (LOW)
**Where:** `app/Listeners/StripeEventListener.php` → `handleInvoiceUpcoming()` (currently logs only)

Add:
1. Create `App\Events\Business\SubscriptionRenewalUpcoming(User $user, int $amountCents, ?int $dueTimestamp)`
2. Create email template per `EMAIL_TEMPLATES_PROMPT.md`
3. Dispatch event from `handleInvoiceUpcoming()`
4. Register listener in `AppServiceProvider`

Est: ~1 hr.

### Gap 4 — Enterprise CS provisioning (product decision)
`mailto:` only. Intentional; leave unless automation wanted.

### Gap 5 — Native "Add Card" modal in Provider Billing panel (OPTIONAL)
Empty state currently links to Stripe Portal. `storePaymentMethod` route + service method already exist — only a Stripe-Elements modal is missing.

### Gap 6 — Founding Member perks (product decision)
UI band exists in Settings; not wired to billing logic. Needs scoping from Carizma.

## 31. Stack & Architecture

- **Backend:** PHP 8.2, Laravel 12, MySQL 8 — 77 models, 123 migrations, 55 enums across 14 domains
- **Frontend:** Vue 3, Inertia.js, Pinia, Vuelidate
- **Payments:** Laravel Cashier 16.6 (subscriptions) + Stripe PHP SDK 17.3 (Connect payouts)
- **Design system:** CSS variables only (no Tailwind, no hex literals, no inline SVGs). `AegisIcon` for all icons. Globally registered: `AegisModal`, `AegisStatChip`, `AegisBadge`, `AegisPagination`, `AegisHeroBanner`.
- **Universal conventions:** UUID `CHAR(36)` PKs (`ae_`-prefixed), money always integer cents, soft deletes on user-facing tables, `authorize()` returns `true` in FormRequests (real auth at Policy level).
- **Write-path:** Vue form → Inertia POST → FormRequest → Controller → Service → `ActivityService::log()` fan-out → toast + reload. Events fire from Services (never Controllers). `>3 recipients` → `ActivityFanoutJob`.

## 32. File Map

### Backend
| File | Role |
|---|---|
| `app/Services/SubscriptionService.php` | Engine: subscribe, upgrade, downgrade, changePlan, cancel, cancelNow, reactivate, getStatus, getFullSubscriptionData, toggleMaatAddon, billingPortalUrl, setDefaultPaymentMethod, removePaymentMethod |
| `app/Services/AuthService.php` | register(), completeOnboarding() |
| `app/Http/Controllers/Auth/RegisterController.php` | show / store |
| `app/Http/Requests/Auth/RegisterRequest.php` | Registration validation |
| `app/Http/Controllers/Auth/LoginController.php` | Login + resolvePostLoginDestination |
| `app/Http/Controllers/Auth/OnboardingController.php` | showIntent, showRole, submitIntent, complete, showPlan, storePlan, showPayment, subscribe |
| `app/Http/Controllers/Auth/VerifyEmailController.php` | Email verification |
| `app/Http/Controllers/Provider/SettingsController.php` | index + all subscription/payment methods |
| `app/Http/Controllers/BusinessPartner/SettingsController.php` | ⚠️ No subscription methods yet |
| `app/Http/Controllers/ContinuitySteward/SettingsController.php` | ⚠️ No subscription methods yet |
| `app/Listeners/StripeEventListener.php` | All webhook handlers (subscription + Connect) |
| `app/Http/Middleware/EnsureSubscriptionActive.php` | Subscription gate (exempts free roles) |
| `app/Http/Middleware/EnsureEmailVerified.php` | Email gate |
| `app/Http/Middleware/EnsureServicesMode.php` | Services tier gate (Practice + services_mode=1) |
| `app/Http/Middleware/CheckAccountLocked.php` | Lock / deactivation gate |
| `app/Http/Middleware/EnsureRole.php` | Role gate |
| `config/aegis.php` | pricing, stripe_price_to_tier, paid_roles, tier_limits |
| `config/cashier.php` | Cashier keys + webhook path |

### Frontend
| File | Role |
|---|---|
| `resources/js/pages/auth/Register.vue` | Multi-step registration wizard |
| `resources/js/pages/auth/OnboardingPlan.vue` | Plan selection (role-branched) |
| `resources/js/pages/auth/OnboardingPayment.vue` | Stripe card capture + resolveStripePrice() |
| `resources/js/pages/provider/Settings.vue` | Full subscription + billing management |
| `resources/js/pages/business-partner/Settings.vue` | ⚠️ Stub — no billing panel |
| `resources/js/pages/continuity-steward/Settings.vue` | ⚠️ Stub — no billing panel |
| `resources/js/composables/usePortal.js` | requiresTier() tier gate |
| `resources/js/composables/useUpgrade.js` | openUpgradeModal() |
| `resources/views/app.blade.php` | Injects all price IDs to window.__AEGIS_CONFIG__ |

### Migrations (billing-relevant)
| Migration | Purpose |
|---|---|
| `2024_01_01_000001` | users (tier enum, role, cs_account_type, bp_type, maat_addon, services_mode) |
| `2024_01_02_000004` | stripe_id, stripe_payment_method_id |
| `2024_01_03_000001` | pm_type, pm_last_four, trial_ends_at |
| `2026_07_07_092246–49` | subscriptions + subscription_items (UUID-correct) |
| `2026_07_07_100000–01` | UUID + billable-column fixes |
| `2024_01_01_000068` | stripe_webhook_events |

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
   charge.refunded   transfer.paid   transfer.failed   account.updated
   ```
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
| User | Role / Tier |
|---|---|
| `p_sarah` | Practitioner — Practice tier |
| `p_david` | Practitioner — Access tier |
| `p_maria` | Practitioner — Practice, services mode on |
| `cs_marcus` | Continuity Steward (Business CS) |
| `ss_linda` | Support Steward |
| `bp_acme` | Business Partner (Agency) |
| `bp_jamal` | Business Partner (Freelancer) |
| `admin_root` | Admin |

State flags: `?as=<user_id>` (impersonate), `?emergency=true/false` (incident state), `?invited=true` (force Invited CS view). Persisted in session.

### Prototype vs Laravel — What Changed
`AEGIS-PROJECT-CONTEXT.md` was written against the original PHP/SQLite prototype. When the two sources conflict, the Laravel app + `config/aegis.php` win.

| Aspect | Prototype (legacy) | Current Laravel app |
|---|---|---|
| Database | SQLite, 16 tables | MySQL, 70+ tables (77 models) |
| User IDs | `p_sarah`, `cs_marcus` strings | UUID `CHAR(36)`, `ae_`-prefixed |
| Portals | `/provider-portal/`, `.php` pages | `/provider`, Vue/Inertia SPA |
| Access price | $39/mo | **$29/mo** (confirmed June 2026) |
| Practice price | $79/mo | **$49/mo** (confirmed June 2026) |
| Billing page | `finances.php` | `Settings.vue` (billing + invoices panels) |
| Cashier version | ^15 | ^16.6 |
| Services Mode | Standalone +$19 add-on | Folded into Practice tier |

---

**End of canonical billing, onboarding & system reference.**
