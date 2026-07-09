# AEGIS_SETTINGS.md

> **Purpose:** Canonical reference for all Settings pages across every Aegis portal (Provider, CS, SS, BP).
> Records what each setting controls, where values are stored, backend endpoints, shared component status,
> and implementation notes. Use this file before building or converting any portal's Settings.vue.
>
> **Revision:** 3 — reflects P0 + P1 + Batch3 (dispute system, CS engagement contract, native Add Card, Stripe Connect Express onboarding across all 3 paid portals)
> **Last validated:** 2026-07-09 against `main @ 9351e14`
> **Companion docs:** `AEGIS_BILLING_LIFECYCLE.md` · `AEGIS_PAYMENTS_FINANCE.md` · `AEGIS-PROJECT-CONTEXT.md` · `CONTINUITY_GROUP_CONVERSION_PLAN.md` · `ENV_REFERENCE.md` · `AEGIS_CHAPMAN_PENDING_ITEMS.md`

**Legend:** ✅ Verified complete · ⚠️ Partial · ❌ Not implemented · 🆕 New in this revision

---

## Architecture Overview

Settings are persisted via three mechanisms:

| Mechanism | Used for | Table |
|---|---|---|
| `user_meta` key-value store | All preference toggles, availability, appearance, notification gates | `user_meta` (`meta_key`, `meta_value`) |
| `users` table columns | Email, phone, password, MFA status, `stripe_id`, `stripe_payment_method_id`, `stripe_account_id`, `stripe_connected`, `tier`, `services_mode` | `users` |
| Stripe / Cashier | Subscription plan, payment methods, invoices, Connect Express account | Stripe API + `subscriptions` table |

Route prefixes per portal: `provider.settings.*` · `cs.settings.*` · `ss.settings.*` · `bp.settings.*`

Controllers:
- `App\Http\Controllers\Provider\SettingsController` ✅ (fully wired)
- `App\Http\Controllers\ContinuitySteward\SettingsController` ✅ Rev 3 (was ❌ stub — closed by P0)
- `App\Http\Controllers\SupportSteward\SettingsController` ✅
- `App\Http\Controllers\BusinessPartner\SettingsController` ✅ Rev 3 (was ❌ stub — closed by P0)
- 🆕 `App\Http\Controllers\{Provider,ContinuitySteward,BusinessPartner}\PaymentMethodSetupController` — native Add Card SetupIntent endpoint
- Shared trait: `App\Http\Controllers\Concerns\CreatesSetupIntent`

Services: `ProfileService` (meta read/write) · `SubscriptionService` (billing) · `PayoutService` (BP/CS payout) · `ActivityService` (fan-out logs)

---

## Shared Component Map

These sections live at `resources/js/components/settings/`. Import them — do not rebuild.

| Component | Covers | Portals |
|-----------|--------|---------|
| `SettingsAccount.vue` | Email · phone · handle · change password · active sessions · revoke-all modal | All 4 |
| `SettingsSecurity.vue` | TOTP/email 2FA · backup codes · security alerts | All 4 |
| `SettingsNotifications.vue` | Quiet hours · 4-col digest prefs · notification table · `#extra-toggles` slot | All 4 |
| `SettingsAppearance.vue` | Theme swatches · dark mode · timezone select | All 4 |
| `SettingsMessaging.vue` | Who can message · status · read receipts · away text · `#extra-toggles` slot | CS · SS · BP |
| `SettingsEmailPrefs.vue` | Digest frequency · activity summary label · unsub-all | CS · SS · BP |
| `SettingsDangerZone.vue` | Export · pause · optional transfer (BP) · deactivate/delete | All 4 |
| `SettingsTierUpgradeModal.vue` | Provider Access→Practice upgrade with Stripe Elements | Provider |
| 🆕 `AddCardModal.vue` | Native Stripe Elements card capture | All 3 paid (Provider, CS Business, BP) |

### Route name pattern
| Portal | Example |
|--------|---------|
| Provider | `provider.settings.password` |
| Continuity Steward | `cs.settings.password` |
| Support Steward | `ss.settings.password` |
| Business Partner | `bp.settings.password` |

### Portal Settings page composition
| File | Shared components | Portal-specific panels (inline) |
|------|----------------------|----------------------------------|
| `provider/Settings.vue` | Account · Security · Notifications · Appearance · DangerZone | Profile · Billing/Invoices · Availability · Referrals · CS/SS/Vault/Agreement/Services/Privacy/Network prefs |
| `cs/Settings.vue` | Account · Security · Notifications · Appearance · Messaging · EmailPrefs · DangerZone | Profile · CS Role Settings · Document Vault Access · Privacy · 🆕 Billing (invited/business split) · 🆕 Payouts & Stripe Connect |
| `ss/Settings.vue` | Account · Security · Notifications · Appearance · Messaging · EmailPrefs · DangerZone | Profile · SS Role Settings · Agreements & Attestation · Privacy · Billing (no-cost notice) |
| `bp/Settings.vue` | Account · Security · Notifications · Appearance · Messaging · EmailPrefs · DangerZone | Profile · Business Account Settings · 🆕 Payouts & Stripe Connect · Privacy · 🆕 Billing (BP subscription monthly/annual toggle) |

### What stays portal-specific (do NOT extract)
| Panel | Portal(s) | Why |
|-------|----------|-----|
| Profile & Identity | All (separate) | Badge color, edit route, portal cross-links differ |
| Billing & Subscription | Provider | Full Stripe Cashier + tier plan selection |
| Payout / Stripe Connect | BP + CS | Different payout models |
| Services Mode toggle | Provider only | `services_mode` column |
| CS Steward Settings | CS only | Incident prefs |
| SS Steward Settings | SS only | Task management + incident reporting |
| Team / Agency Settings | BP only | Agency `bp_type` only |
| Billing — CS | CS only | Invited vs business split |
| Billing — SS | SS only | No-cost model |
| Billing — BP | BP only | Monthly/annual toggle |

---

## Panel 1 — Profile & Identity (`section = 'profile'`)

**Purpose:** Read-only summary. Links to Edit Profile.

| Element | Type | Backend |
|---|---|---|
| Profile photo + display name | Display | `users.display_name`, `users.avatar_url` |
| Title + credentials | Display | `users.title`, `users.credentials` |
| Email / phone / location | Display | Unified identity fields |
| Plan badge (Access / Practice / BP / CS-Business) | Display | `subscription.tier` from `SubscriptionService` |
| **View Public Profile** button | Link | Route: `public.<role>` |
| **Edit Full Profile** button | Link | Route: `{portal}.profile.index` |

### Portal Access sub-card ✅ WIRED (was ⚠️ in Rev 2)
| Portal | Visible when |
|---|---|
| Practitioner Portal | Always (current portal) |
| Continuity Steward Portal | `user.has_cs_portal === true` |
| Support Steward Portal | `user.has_ss_portal === true` |

**Rev 3 update:** `has_cs_portal` and `has_ss_portal` are now appended to `auth.user` prop via `HandleInertiaRequests.php` (line 136–139). Queries `user_role_assignments` table. `AppHeader.vue` portal switcher gates on the same flags. ✅

---

## Panel 2 — Account & Login (`section = 'account'`)

> **Shared component:** `SettingsAccount.vue`.

### 2a — Credentials ✅
| Field | Stored in | Route | Status |
|---|---|---|---|
| Primary Email | `users.email` | `PUT /settings/account` (bundle) | ✅ |
| Phone Number | `users.phone` | `PUT /settings/account` (bundle) | ✅ |
| Username / Handle | *(no `handle` column on users; phone editable only)* | — | Deferred |

### 2b — Change Password ✅
| Field | Route | Status |
|---|---|---|
| New Password (min 12 chars, uppercase+number+special) | `PUT /{portal}/settings/password` | ✅ `PasswordResetController::change` |

### 2c — Active Sessions ✅
`TrackUserSession` middleware upserts `user_sessions` on every auth request keyed by Laravel session ID. `UserSession` model captures `device_label`, `ip_address`, `user_agent`, `last_seen_at`. Display splits into "Mac — Chrome 124" format; current session shows gold icon + "This device" badge + "Active now".

| Action | Route | Status |
|---|---|---|
| Revoke all others | `DELETE /{portal}/settings/sessions` | ✅ |
| Revoke one | `DELETE /{portal}/settings/sessions/{session}` | ✅ |

---

## Panel 3 — Security & Two-Factor (`section = 'security'`) ✅ FULLY COMPLETE

> **Shared component:** `SettingsSecurity.vue`

Two 2FA methods:
- **TOTP** (authenticator app) via qrcodejs CDN (no npm)
- **Email OTP** — 6-digit via `SendEmailJob::dispatchSync()` (NOT `::dispatch()`)

Key model: `MfaToken` with fields `secret`, `method`, `recovery_codes`, `email_otp_hash`, `email_otp_expires_at`, `confirmed_at`, `disabled_at`.

Backup codes: 8 × 6-digit numeric, one-time use, stored as JSON in `mfa_tokens.recovery_codes`.

`MfaChallenge.vue` receives `mfaMethod` prop to render the right challenge form.

Password fields use `ob-password-wrap` / `ob-password-toggle` CSS classes (NOT `input-password-wrap` / `pw-toggle`).

Security alerts (login notification / session timeout) moved to Notifications panel — this panel is only 2FA + hint.

Routes: `POST /{portal}/settings/mfa/enable`, `.../enable-email`, `.../verify`, `.../verify-email`, `.../disable`, `GET .../backup-codes` — all wired via `MfaController`. ✅

---

## Panel 4 — Notification Preferences (`section = 'notifications'`) ✅

> **Shared component:** `SettingsNotifications.vue` — pass `notifCategories` prop for portal-specific gates.

Route: `PUT /{portal}/settings/notifications` → `updateNotifications`. Stored in `user_meta` keys.

### 4a — Category Channel Matrix
| Category | `user_meta` key group |
|---|---|
| Critical Incident Alerts | `notify_incident_{push\|email\|inapp}` |
| Referral Updates | `notify_referral_{push\|email\|inapp}` |
| Direct Messages | `notify_message_{push\|email\|inapp}` |
| Payments & Invoices | `notify_payment_{push\|email\|inapp}` |
| Continuity Plan Updates | `notify_plan_{push\|email\|inapp}` |
| Vault Activity | `notify_vault_{push\|email\|inapp}` |
| Network & Connections | `notify_network_{push\|email\|inapp}` |
| 🆕 Disputes | `notify_dispute_{push\|email\|inapp}` |
| 🆕 Steward Coordination | `notify_steward_{push\|email\|inapp}` |
| 🆕 Finance & Payouts | `notify_finance_{push\|email\|inapp}` |

**🆕 Rev 3 additions:** `notify_dispute`, `notify_steward`, `notify_finance` gate keys are consulted by `SendEmailNotificationListener::gate()` when firing the new event types added in batch3 (dispute open/reply/resolve, incident ready-for-closure, closure-verified, auto-close, cs-invoice-auto-generated).

### 4b — Security & Login Alerts
| Toggle | `user_meta` key | Default |
|---|---|---|
| Alert on new login | `notify_new_login` | `true` |
| Session timeout (30 min) | `session_timeout_enabled` | `true` |

---

## Panels 5–13 — Portal-specific pref panels ✅

_(Unchanged from Rev 2 — CS/SS/Vault/Agreement/Services/Privacy/Network/Referrals prefs all wired via `PUT /settings/preferences` bundled saves to `user_meta`.)_

---

## Panel 14 — Appearance & Timezone ✅

> **Shared component:** `SettingsAppearance.vue`

Route: `PUT /{portal}/settings/appearance`. Storage: `user_meta.appearance` (JSON).

- **Themes:** `gold` (default), `gold-dark`, `slate`
- **Dark mode:** `dark_mode` bool applies `data-mode="dark"` on `<html>`
- **Timezone:** default `America/New_York`, passed to `Carbon::setTimezone()` in emails

---

## Panel 15 — Subscription & Plan (`section = 'billing'`) ✅ ALL PORTALS

**Rev 3 status:** wired for Provider (Rev 2), CS Business (P0 — was ❌), BP (P0 — was ❌).

### Provider tier plans
| Plan | Monthly | Annual |
|---|---|---|
| Continuity Access | $29/mo | $23/mo ($276/yr) |
| Continuity Practice | $49/mo | $39/mo ($468/yr) |
| MAAT Add-on (Practice only) | +$29/mo | +$23/mo (+$276/yr) |

### BP tier
| Plan | Monthly | Annual |
|---|---|---|
| Business Partner | $69/mo | $57.50/mo ($690/yr) |

### CS Business tier
| Plan | Monthly | Annual |
|---|---|---|
| CS Business | $49/mo | ~$35.75/mo ($429/yr) |

### Actions per portal
| Portal | Route | Status |
|---|---|---|
| Provider swap | `POST /provider/settings/subscription/swap` | ✅ |
| Provider cancel | `POST /provider/settings/subscription/cancel` | ✅ |
| Provider resume | `POST /provider/settings/subscription/resume` | ✅ |
| Provider MAAT toggle | `POST /provider/settings/subscription/maat` | ✅ |
| 🆕 CS swap (monthly↔annual only, one tier) | `POST /continuity-steward/settings/subscription/swap` | ✅ |
| 🆕 CS cancel | `POST /continuity-steward/settings/subscription/cancel` | ✅ |
| 🆕 CS resume | `POST /continuity-steward/settings/subscription/resume` | ✅ |
| 🆕 BP swap | `POST /business-partner/settings/subscription/swap` | ✅ |
| 🆕 BP cancel | `POST /business-partner/settings/subscription/cancel` | ✅ |
| 🆕 BP resume | `POST /business-partner/settings/subscription/resume` | ✅ |

**Confirmation modals** (Vue): `confirmSwap`, `confirmCancel`, `confirmResume`, `confirmMaat`, `confirmCsSwap`, `confirmCsResume`, `confirmBpSwap`, `confirmBpResume`.

**Invited CS guard:** CS `Settings.vue` splits the billing panel by `cs_account_type`. Invited CS sees a "You are a free Invited Continuity Steward" notice; Business CS sees the full subscription panel.

---

## Panel 16 — Billing & Payment (`section = 'invoices'`) ✅ + 🆕 NATIVE ADD-CARD

### 16a — Payment Methods
| Action | Route | Status |
|---|---|---|
| Set default payment method | `POST /provider/settings/payment-method/default` | ✅ Provider only |
| Remove payment method | `DELETE /provider/settings/payment-method` | ✅ Provider only |
| Store payment method (native) | `POST /provider/settings/payment-method` | ✅ Provider ✅ mirrors to `stripe_payment_method_id` |
| 🆕 SetupIntent for native Add Card | `POST /provider/settings/payment-method/setup-intent` | ✅ Provider |
| 🆕 SetupIntent for native Add Card (CS) | `POST /continuity-steward/settings/payment-method/setup-intent` | ✅ |
| 🆕 SetupIntent for native Add Card (BP) | `POST /business-partner/settings/payment-method/setup-intent` | ✅ |
| Store payment method (native) — CS | `POST /continuity-steward/settings/payment-method` | ❌ Gap — see below |
| Store payment method (native) — BP | `POST /business-partner/settings/payment-method` | ❌ Gap — see below |
| Fallback: open Stripe Portal | `GET /{portal}/settings/billing-portal` | ✅ All 3 |

### 🆕 16b — Native Add Card (Stripe Elements)
`AddCardModal.vue` is a reusable component. Props:
- `modelValue` (v-model)
- `setupIntentRoute` — Ziggy route name that returns `client_secret` + `stripe_key`
- `storeRoute` — Ziggy route name that persists the PaymentMethod id

**Flow:**
1. Modal opens → POST `setupIntentRoute` → server returns `client_secret` + `stripe_key`
2. Stripe.js loads from CDN (once); mounts `cardNumber` / `cardExpiry` / `cardCvc` Elements
3. User enters card → `stripe.confirmCardSetup(clientSecret)` → returns `payment_method` id
4. Modal POSTs PM id to `storeRoute` with `set_default: true`
5. Server persists via Cashier + mirrors to `users.stripe_payment_method_id`

**Wired end-to-end for Provider.** ⚠️ CS + BP need `storePaymentMethod` handler methods (Gap 1 in `AEGIS_BILLING_LIFECYCLE.md`).

### 16c — Invoice History
Loaded from Stripe via `SubscriptionService::getFullSubscriptionData()`. Each row: date, description, amount, status pill, PDF link.

---

## Panel 17 — Integrations & Stripe Connect ✅

### 17a — 🆕 Stripe Connect Express Onboarding (all 3 paid portals)
**Rev 3 update** — previously stubs. Now real onboarding wired for BP, CS Business, and Provider (if Services Mode enabled).

| Portal | Onboard route | Return route |
|---|---|---|
| Provider | `GET /provider/settings/connect/onboard` | `GET /provider/settings/connect/return` |
| 🆕 CS Business | `GET /continuity-steward/settings/connect/onboard` | `GET /continuity-steward/settings/connect/return` |
| 🆕 BP | `GET /business-partner/settings/connect/onboard` | `GET /business-partner/settings/connect/return` |

**Flow:** Controller calls `stripe->accounts->create(['type' => 'express', 'country' => 'US', ...])` → gets `acct_*` ID → stores in `users.stripe_account_id` → creates `accountLinks` with type `account_onboarding` → redirects user to Stripe's hosted onboarding.

**Webhook confirmation:** `account.updated` handler (P1) flips `users.stripe_connected = 1` when `charges_enabled && payouts_enabled && details_submitted`. No polling needed.

**Fallback for existing accounts:** `GET /{portal}/settings/billing-portal` opens Stripe Customer Portal for card management.

### 17b — Other Integrations
Currently empty state. Future OAuth connectors will use `user_meta.integrations` JSON.

---

## Panel 18 — Account Actions (`section = 'changes'`) ✅

> **Shared component:** `SettingsDangerZone.vue`

| Action | Route | Status |
|---|---|---|
| Export data | `POST /{portal}/settings/export-data` | ✅ Queued job |
| Pause account | `POST /{portal}/settings/account/pause` | ✅ Uses `user_meta.account_paused` key (NOT `users.paused_at` — that's Cashier's) |
| Resume account | `POST /{portal}/settings/account/resume` | ✅ |
| Delete account | `DELETE /{portal}/settings/account` | ✅ |

---

## 🆕 Panel 19 — Disputes Access (`section = 'disputes'`)

**Rev 3 addition** — links to the portal's Disputes list from Settings.

Not a "settings" panel per se — just a navigation card:
- **My disputes** button → `{portal}.disputes.index`
- Small summary: "N active · N resolved"

**Rationale:** disputes are user-initiated, live inside the Money surface, but Settings is where users look for "everything I need to manage myself".

---

## 🆕 Notification categories from batch3

`SendEmailNotificationListener` reads notification gate meta keys before dispatching emails for the following event types:

| Event | Gate key |
|---|---|
| `IncidentReadyForClosure` | `notify_incident` |
| `IncidentClosureVerified` | `notify_incident` |
| `IncidentAutoClosed` | `notify_incident` |
| `CsInvoiceAutoGenerated` | `notify_finance` |
| `DisputeOpened` | `notify_dispute` |
| `DisputeReplied` | `notify_dispute` |
| `DisputeResolved` | `notify_dispute` |

If the user has `notify_{key}_email = false` in `user_meta`, they receive the in-app notification but no email.

⚠️ Email templates for these 7 events pending (see `AEGIS_BILLING_LIFECYCLE.md` §35 Gap 2). Listener no-ops silently on missing templates today.

---

## Rev 3 change summary

### ✅ Closed from Rev 2
- BP `SettingsController` — full billing methods added (P0)
- CS `SettingsController` — full billing methods added (P0)
- Real Stripe Connect Express onboarding wired for all 3 paid portals (was stubs — P0)
- `has_cs_portal` / `has_ss_portal` in Inertia shared props (was needed for portal switcher)
- `account.updated` webhook wired (was ⚠️ ignored — P1)
- Native "Add Card" flow — Provider end-to-end; CS + BP have SetupIntent endpoint (batch3)
- CS + BP subscription management modals

### ⚠️ Open items
- **Gap 1** — CS + BP `storePaymentMethod` methods not present (setup-intent works; save doesn't yet)
- **Gap 2** — 7 email blade templates pending
- **Gap 3** — Access tier `max_support_stewards` — env-tunable (`TIER_ACCESS_MAX_SS`), default 1; Dr. Chapman to confirm 1 vs 2

### 🆕 New in Rev 3
- 🆕 Native Add Card panel section (Stripe Elements modal, all 3 portals)
- 🆕 Stripe Connect Express onboarding sub-section (all 3 paid portals)
- 🆕 Notification categories: `notify_dispute`, `notify_finance`, `notify_steward`
- 🆕 Panel 19 — Disputes link
- 🆕 CS engagement contract billing panel (in CS Settings, shows fee per activation + payment terms + auto-charge toggle) — TBD, part of CS UI work in `CONTINUITY_GROUP_CONVERSION_PLAN.md`

---

## `user_meta` Reference (complete)

_(Unchanged large section — all preference keys.)_

Key naming rule: `notify_<category>_<channel>` where category is `incident|referral|message|payment|plan|vault|network|dispute|steward|finance` and channel is `push|email|inapp`. Also `notify_new_login`, `session_timeout_enabled`, `account_paused`, `services_mode`, `appearance` (JSON).

Access via `ProfileService::saveMeta()` / `getMeta()`. `UserMeta` model uses UUID PK — manual ID generation required on `create`: `'um_' . Str::lower(Str::random(12))`. `updateOrCreate` fails with no default for `id`.

---

*Rev 3 — validated against live repo commit `9351e14` on 2026-07-09*
*Rev 2 — July 2026 — shared component extraction + all 4 portal Settings.vue builds*
