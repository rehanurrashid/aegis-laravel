# AEGIS_SETTINGS.md

> **Source of truth:** live repo `github.com/rehanurrashid/aegis-laravel @ 1405245`
> **Validated:** 2026-07-09 — every section derived from reading actual code, not prior docs.
> **Purpose:** canonical reference for all Settings pages across all 4 portals. Read this before building, converting, or extending any Settings.vue or SettingsController.

---

## Architecture

### Storage mechanisms

| Layer | Used for | Table / API |
|---|---|---|
| `user_meta` key-value store | All preference toggles, prefs, appearance, notification gates | `user_meta` (`meta_key`, `meta_value`) |
| `users` columns | Email, phone, password, MFA state, `stripe_id`, `stripe_payment_method_id`, `stripe_account_id`, `stripe_connected`, `tier`, `cs_account_type`, `bp_type` | `users` |
| `user_sessions` | Active session tracking | `user_sessions` |
| Stripe / Cashier | Subscription, payment methods, invoices, Connect accounts | Stripe API + `subscriptions` |

Access meta via `ProfileService::saveMeta()` / `getMeta()`. `UserMeta` uses UUID PKs — manual ID required on `create` (`'um_' . Str::lower(Str::random(12))`); `updateOrCreate` fails without a default.

### Controllers

| Portal | Controller | Lines |
|---|---|---|
| Provider | `App\Http\Controllers\Provider\SettingsController` | 648 |
| CS | `App\Http\Controllers\ContinuitySteward\SettingsController` | 434 |
| SS | `App\Http\Controllers\SupportSteward\SettingsController` | 297 |
| BP | `App\Http\Controllers\BusinessPartner\SettingsController` | 408 |

Additional controllers (MFA, Password, PaymentMethodSetup) are separate files, routed into the same settings prefix group.

### Vue pages

| Portal | File | Lines |
|---|---|---|
| Provider | `resources/js/pages/provider/Settings.vue` | 2068 |
| CS | `resources/js/pages/continuity-steward/Settings.vue` | 522 |
| SS | `resources/js/pages/support-steward/Settings.vue` | 297 |
| BP | `resources/js/pages/business-partner/Settings.vue` | 554 |

---

## Shared components (`resources/js/components/settings/`)

Eight shared components. Imported and used per-portal as documented below.

### Component prop contracts

**SettingsAccount**
```
user                 Object  (full user array from controller)
sessions             Array   (UserSession rows)
updatePasswordRoute  String  required
updateAccountRoute   String  required
revokeAllRoute       String  required
revokeSessionRoute   String  required
```
Internally uses `useForm()` for phone update (PUT `updateAccountRoute`) and password change (PUT `updatePasswordRoute`). Session revoke uses `router.delete`. Has its own `modals.revokeAll` — do NOT add a duplicate in the portal page.

**SettingsSecurity**
```
enableMfaRoute       String  required
disableMfaRoute      String  required
verifyMfaRoute       String  required
enableEmailMfaRoute  String  default ''
verifyEmailMfaRoute  String  default ''
backupCodesRoute     String  default ''
mfaEnabled           Boolean default false
mfaMethod            String  default ''   ('totp' | 'email' | '')
userEmail            String  default ''
```
Handles TOTP setup, Email OTP, backup codes display and regeneration — all fully wired. Do NOT add duplicate 2FA or backup-code modals in the portal page.

**SettingsNotifications**
```
updateRoute      String   required
subtitle         String   default 'Delivery channels are unified across all portals.'
notifCategories  Array    default []   (portal-specific category list)
savedPrefs       Object   default {}
savedCategories  Array    default []
extraPrefs       Object   default {}   (slot for portal-specific extra toggles)
```
Uses `router.put` internally. Categories passed in as a reactive array of `{ key, label, desc, push, email, inapp }`.

**SettingsAppearance**
```
updateRoute  String  required
meta         Object  default {}
```
Hydrates from `meta.appearance`. PUT `updateRoute`.

**SettingsMessaging**
```
updateRoute    String  required
messagesRoute  String  required
subtitle       String  default '...'
whoOptions     Array   default []
meta           Object  default {}
```
Supports `#extra-toggles` slot (CS uses it for "Critical Incident Thread Auto-Flag"). PUT `updateRoute`.

**SettingsEmailPrefs**
```
updateRoute    String  required
activityLabel  String  default 'Activity Summary'
activityDesc   String  default 'Digest of your platform activity'
meta           Object  default {}
```
PUT `updateRoute`.

**SettingsDangerZone**
```
deleteRoute    String  default 'provider.settings.account.delete'
pauseRoute     String  default 'provider.settings.account.pause'
resumeRoute    String  default 'provider.settings.account.resume'
exportRoute    String  default 'provider.settings.account.export'
initialPaused  Boolean default false
```
Reads live pause state from `usePage().props.auth.user.is_paused`. Handles export, pause, resume, and delete with real confirmation modals — all wired. Do NOT add duplicate pause/export/delete modals in the portal page.

**SettingsTierUpgradeModal** (Provider only)
```
show               Boolean default false
lockedFeatureNote  String  default ''
billingSection     String  default 'billing'
```

---

## Shared component usage per portal

| Component | Provider | CS | SS | BP |
|---|---|---|---|---|
| SettingsAccount | ✅ | ✅ | ✅ | ✅ |
| SettingsSecurity | ✅ | ✅ | ✅ | ✅ |
| SettingsNotifications | ✅ | ✅ | ✅ | ✅ |
| SettingsAppearance | ✅ | ✅ | ✅ | ✅ |
| SettingsMessaging | ✗ | ✅ | ✅ | ✅ |
| SettingsEmailPrefs | ✗ | ✅ | ✅ | ✅ |
| SettingsDangerZone | ✅ | ✅ | ✅ | ✅ |
| SettingsTierUpgradeModal | ✅ | ✗ | ✗ | ✗ |

Provider does NOT import SettingsMessaging or SettingsEmailPrefs — messaging and email prefs are either absent or handled inline in Provider's broader nav.

---

## Route map (all settings routes, verified from routes/web.php)

### Provider (36 routes under `provider.settings.*`)

| Route name | Verb | Controller method |
|---|---|---|
| `provider.settings.index` | GET | `index` |
| `provider.settings.account` | PUT | `updateAccount` |
| `provider.settings.password` | PUT | `change` (PasswordResetController) |
| `provider.settings.appearance` | PUT | `updateAppearance` |
| `provider.settings.notifications` | PUT | `updateNotifications` |
| `provider.settings.security-alerts` | PUT | `updateSecurityAlerts` |
| `provider.settings.cs-settings` | PUT | `updateCsSettings` |
| `provider.settings.ss-settings` | PUT | `updateSsSettings` |
| `provider.settings.vault-alerts` | PUT | `updateVaultAlerts` |
| `provider.settings.agreement-alerts` | PUT | `updateAgreementAlerts` |
| `provider.settings.network-settings` | PUT | `updateNetworkSettings` |
| `provider.settings.services-settings` | PUT | `updateServicesSettings` |
| `provider.settings.privacy-settings` | PUT | `updatePrivacySettings` |
| `provider.settings.sessions.revoke` | DELETE | `revokeSession` |
| `provider.settings.sessions.revoke-all` | DELETE | `revokeAllSessions` |
| `provider.settings.mfa.enable` | POST | `enable` (MfaController) |
| `provider.settings.mfa.enable-email` | POST | `enableEmail` |
| `provider.settings.mfa.verify` | POST | `verify` |
| `provider.settings.mfa.verify-email` | POST | `verifyEmail` |
| `provider.settings.mfa.disable` | POST | `disable` |
| `provider.settings.mfa.backup-codes` | GET | `backupCodes` |
| `provider.settings.payment.default` | POST | `setDefaultPaymentMethod` |
| `provider.settings.payment.remove` | DELETE | `removePaymentMethod` |
| `provider.settings.payment.setup-intent` | POST | `createSetupIntent` (PaymentMethodSetupController) |
| `provider.settings.payment.store` | POST | `storePaymentMethod` |
| `provider.settings.subscription.swap` | POST | `swapPlan` |
| `provider.settings.subscription.cancel` | POST | `cancelPlan` |
| `provider.settings.subscription.resume` | POST | `resumePlan` |
| `provider.settings.subscription.maat` | POST | `toggleMaat` |
| `provider.settings.billing.portal` | GET | `billingPortal` |
| `provider.settings.connect.onboard` | GET | `connectOnboard` |
| `provider.settings.connect.return` | GET | `connectReturn` |
| `provider.settings.account.pause` | POST | `pauseAccount` |
| `provider.settings.account.resume` | POST | `resumeAccount` |
| `provider.settings.account.export` | POST | `exportData` |
| `provider.settings.account.delete` | DELETE | `deleteAccount` |

### CS (29 routes under `cs.settings.*`)

| Route name | Verb | Controller method |
|---|---|---|
| `cs.settings.index` | GET | `index` |
| `cs.settings.account` | PUT | `updateAccount` |
| `cs.settings.password` | PUT | `change` |
| `cs.settings.appearance` | PUT | `updateAppearance` |
| `cs.settings.notifications` | PUT | `updateNotifications` |
| `cs.settings.messaging` | PUT | `updateMessaging` |
| `cs.settings.email-prefs` | PUT | `updateEmailPrefs` |
| `cs.settings.role-prefs` | PUT | `updateRolePrefs` |
| `cs.settings.vault-prefs` | PUT | `updateVaultPrefs` |
| `cs.settings.privacy` | PUT | `updatePrivacy` |
| `cs.settings.sessions.revoke` | DELETE | `revokeSession` |
| `cs.settings.sessions.revoke-all` | DELETE | `revokeAllSessions` |
| `cs.settings.mfa.enable` | POST | `enable` |
| `cs.settings.mfa.enable-email` | POST | `enableEmail` |
| `cs.settings.mfa.verify` | POST | `verify` |
| `cs.settings.mfa.verify-email` | POST | `verifyEmail` |
| `cs.settings.mfa.disable` | POST | `disable` |
| `cs.settings.mfa.backup-codes` | GET | `backupCodes` |
| `cs.settings.payment.setup-intent` | POST | `createSetupIntent` |
| `cs.settings.subscription.swap` | POST | `swapPlan` |
| `cs.settings.subscription.cancel` | POST | `cancelPlan` |
| `cs.settings.subscription.resume` | POST | `resumePlan` |
| `cs.settings.billing.portal` | GET | `billingPortal` |
| `cs.settings.connect.onboard` | GET | `connectOnboard` |
| `cs.settings.connect.return` | GET | `connectReturn` |
| `cs.settings.account.pause` | POST | `pauseAccount` |
| `cs.settings.account.resume` | POST | `resumeAccount` |
| `cs.settings.account.export` | POST | `exportData` |
| `cs.settings.account.delete` | DELETE | `deleteAccount` |

**Note:** `cs.settings.payment.store` is NOT registered — there is a `setup-intent` endpoint but no `storePaymentMethod` method on `CS/SettingsController`. The native Add Card flow cannot complete for CS users. (See Known Gaps.)

### SS (22 routes under `ss.settings.*`)

| Route name | Verb | Controller method |
|---|---|---|
| `ss.settings.index` | GET | `index` |
| `ss.settings.account` | PUT | `updateAccount` |
| `ss.settings.password` | PUT | `change` |
| `ss.settings.appearance` | PUT | `updateAppearance` |
| `ss.settings.notifications` | PUT | `updateNotifications` |
| `ss.settings.messaging` | PUT | `updateMessaging` |
| `ss.settings.email-prefs` | PUT | `updateEmailPrefs` |
| `ss.settings.role-prefs` | PUT | `updateRolePrefs` |
| `ss.settings.agreement-prefs` | PUT | `updateAgreementPrefs` |
| `ss.settings.privacy` | PUT | `updatePrivacy` |
| `ss.settings.sessions.revoke` | DELETE | `revokeSession` |
| `ss.settings.sessions.revoke-all` | DELETE | `revokeAllSessions` |
| `ss.settings.mfa.enable` | POST | `enable` |
| `ss.settings.mfa.enable-email` | POST | `enableEmail` |
| `ss.settings.mfa.verify` | POST | `verify` |
| `ss.settings.mfa.verify-email` | POST | `verifyEmail` |
| `ss.settings.mfa.disable` | POST | `disable` |
| `ss.settings.mfa.backup-codes` | GET | `backupCodes` |
| `ss.settings.account.pause` | POST | `pauseAccount` |
| `ss.settings.account.resume` | POST | `resumeAccount` |
| `ss.settings.account.export` | POST | `exportData` |
| `ss.settings.account.delete` | DELETE | `deleteAccount` |

**Note:** No subscription, payment, or Stripe Connect routes for SS — free role.

### BP (29 routes under `bp.settings.*`)

| Route name | Verb | Controller method |
|---|---|---|
| `bp.settings.index` | GET | `index` |
| `bp.settings.account` | PUT | `updateAccount` |
| `bp.settings.password` | PUT | `change` |
| `bp.settings.appearance` | PUT | `updateAppearance` |
| `bp.settings.notifications` | PUT | `updateNotifications` |
| `bp.settings.messaging` | PUT | `updateMessaging` |
| `bp.settings.email-prefs` | PUT | `updateEmailPrefs` |
| `bp.settings.business-prefs` | PUT | `updateBusinessPrefs` |
| `bp.settings.payout-prefs` | PUT | `updatePayoutPrefs` |
| `bp.settings.privacy` | PUT | `updatePrivacy` |
| `bp.settings.sessions.revoke` | DELETE | `revokeSession` |
| `bp.settings.sessions.revoke-all` | DELETE | `revokeAllSessions` |
| `bp.settings.mfa.enable` | POST | `enable` |
| `bp.settings.mfa.enable-email` | POST | `enableEmail` |
| `bp.settings.mfa.verify` | POST | `verify` |
| `bp.settings.mfa.verify-email` | POST | `verifyEmail` |
| `bp.settings.mfa.disable` | POST | `disable` |
| `bp.settings.mfa.backup-codes` | GET | `backupCodes` |
| `bp.settings.payment.setup-intent` | POST | `createSetupIntent` |
| `bp.settings.subscription.swap` | POST | `swapPlan` |
| `bp.settings.subscription.cancel` | POST | `cancelPlan` |
| `bp.settings.subscription.resume` | POST | `resumePlan` |
| `bp.settings.billing.portal` | GET | `billingPortal` |
| `bp.settings.connect.onboard` | GET | `connectOnboard` |
| `bp.settings.connect.return` | GET | `connectReturn` |
| `bp.settings.account.pause` | POST | `pauseAccount` |
| `bp.settings.account.resume` | POST | `resumeAccount` |
| `bp.settings.account.export` | POST | `exportData` |
| `bp.settings.account.delete` | DELETE | `deleteAccount` |

**Note:** Same as CS — `bp.settings.payment.store` is NOT registered. `storePaymentMethod` method is absent from `BP/SettingsController`. (See Known Gaps.)

---

## Inertia props passed to each portal

### Provider — `Inertia::render('Provider/Settings', [...])`

| Prop | Type | Source |
|---|---|---|
| `user` | array | `$user->toArray()` + computed fields below |
| `user.mfa_enabled` | bool | `$user->two_factor_enabled` |
| `user.mfa_method` | string | `$user->mfaToken?->method ?? ''` |
| `user.has_cs_portal` | bool | `user_meta` key `has_cs_portal === '1'` |
| `user.has_ss_portal` | bool | `user_meta` key `has_ss_portal === '1'` |
| `user.tier` | string\|null | `$user->tier?->value` |
| `user.is_founding_member` | bool | count of practitioners registered before this user < 100 |
| `meta` | array | all `user_meta` rows as `meta_key => typed_value` |
| `mfaEnabled` | bool | `$user->two_factor_enabled` |
| `mfaMethod` | string | `$user->mfaToken?->method ?? ''` |
| `sessions` | array | `UserSession` rows, formatted, sorted current-first |
| `subscription` | array | `SubscriptionService::getFullSubscriptionData($user)` |
| `pricing` | array | `config('aegis.pricing')` |
| `accountPaused` | bool | `user_meta.account_paused === '1'` |
| `activeAgreements` | array | Derived from `continuityPlans.stewards` — `[title, meta, status]` per active steward |

**Vue `defineProps`:**
```js
user, meta, mfaEnabled, mfaMethod, sessions, subscription, pricing, activeAgreements
```

### CS — `Inertia::render('ContinuitySteward/Settings', [...])`

| Prop | Type | Source |
|---|---|---|
| `user` | array | includes `cs_account_type`, `linked_provider_name/tier/tier_label`, `mfa_enabled`, `mfa_method` |
| `meta` | array | all `user_meta` |
| `mfaEnabled` | bool | |
| `mfaMethod` | string | |
| `sessions` | array | |
| `subscription` | array\|null | only populated when `cs_account_type === 'business'` |
| `pricing` | array | `config('aegis.pricing')` |

**Vue `defineProps`:**
```js
user, meta, mfaEnabled, mfaMethod, sessions, subscription, pricing
```

**CS Invited guard:** `isInvitedCs = computed(() => !user.cs_account_type || user.cs_account_type === 'invited' || !!user.linked_provider_id)` — billing panel only shown when `!isInvitedCs`.

### SS — `Inertia::render('SupportSteward/Settings', [...])`

| Prop | Type | Source |
|---|---|---|
| `user` | array | includes `linked_provider_name`, `linked_provider_tier_label`, `mfa_enabled`, `mfa_method` |
| `meta` | array | |
| `mfaEnabled` | bool | |
| `mfaMethod` | string | |
| `sessions` | array | |

**No subscription, no pricing prop.** SS is a free role.

**Vue `defineProps`:**
```js
user, meta, mfaEnabled
```

**Bug note:** SS Settings.vue has `const sessions = computed(() => props.sessions ?? [])` but `sessions` is not in `defineProps`. It resolves to an empty array always. The controller DOES pass `sessions` in the Inertia render. The fix is to add `sessions: { type: Array, default: () => [] }` to `defineProps`.

### BP — `Inertia::render('BusinessPartner/Settings', [...])`

| Prop | Type | Source |
|---|---|---|
| `user` | array | includes `bp_type`, `mfa_enabled`, `mfa_method` |
| `meta` | array | |
| `mfaEnabled` | bool | |
| `mfaMethod` | string | |
| `sessions` | array | |
| `subscription` | array | always populated (BP always pays) |
| `pricing` | array | |

**Vue `defineProps`:**
```js
user, meta, mfaEnabled, mfaMethod, sessions, subscription, pricing
```

---

## Portal nav structure (as coded)

### Provider nav
```
Account:
  profile     — Profile & Identity
  account     — Account & Login        → SettingsAccount
  security    — Security & 2FA         → SettingsSecurity + Security Alerts card
Communications:
  notifications — Notifications (badge:2) → SettingsNotifications
Schedule:
  availability  — Availability & Hours
Operations:
  csettings   — Continuity Steward Settings
  ssettings   — Support Steward Settings
  vault       — Document Vault Access
  agreements  — Agreements & Contracts
Platform:
  myservices  — My Services Settings   [lockedForAccess]
  privacy     — Privacy & Visibility
  network     — Network Settings
  appearance  — Appearance             → SettingsAppearance
  integrations — Integrations
Billing:
  billing     — Subscription & Plan
  invoices    — Billing & Invoices
Account Closure & Data Management:
  changes     — Account Actions        → SettingsDangerZone
```

### CS nav
```
Account:
  profile    — Profile & Identity
  security   — Security & 2FA          → SettingsSecurity
  messaging  — Messaging               → SettingsMessaging
Steward Role:
  cs-steward — CS Role Settings
  privacy    — Privacy & Visibility
  billing    — Subscription & Plan
```
**Note:** `account` nav item is missing from CS nav definition — SettingsAccount is rendered for `section === 'account'` but there is no nav button to reach it. The profile card links to `cs.profile.index`.

### SS nav
```
Account:
  profile    — Profile & Identity
  security   — Security & 2FA
  messaging  — Messaging
Steward Role:
  ss-steward    — SS Role Settings
  privacy       — Privacy & Visibility
  billing       — Subscription & Plan   (shows free-role notice — no paid sub for SS)
```

### BP nav
```
Account:
  profile    — Profile & Identity
  security   — Security & 2FA
  messaging  — Messaging
Business:
  bp-business — Business Account
  privacy     — Privacy & Visibility
  billing     — Subscription & Plan
```

---

## Panel-by-panel detail

### Unified: Profile & Identity (`section = 'profile'`)

All 4 portals show a Profile Summary card. Key differences:

| Portal | Badge | Edit Profile link | Extra |
|---|---|---|---|
| Provider | tier badge (Access/Practice) | `provider.profile.index` | Portal Access sub-card showing CS/SS portals if `user.has_cs_portal`/`user.has_ss_portal` |
| CS | `badge-blue` "CS" | `cs.profile.index` | — |
| SS | `badge-blue` "SS" | `ss.profile.index` | — |
| BP | `badge-gold` "BP" | `bp.profile.index` | — |

Provider Portal Access card links:
- CS portal: `cs.dashboard` (shown only when `user.has_cs_portal`)
- SS portal: `ss.dashboard` (shown only when `user.has_ss_portal`)

### Unified: Account & Login (`section = 'account'`) → `SettingsAccount`

| Portal | `updateAccountRoute` | `updatePasswordRoute` | `revokeAllRoute` | `revokeSessionRoute` |
|---|---|---|---|---|
| Provider | `provider.settings.account` | `provider.settings.password` | `provider.settings.sessions.revoke-all` | `provider.settings.sessions.revoke` |
| CS | `cs.settings.account` | `cs.settings.password` | `cs.settings.sessions.revoke-all` | `cs.settings.sessions.revoke` |
| SS | `ss.settings.account` | `ss.settings.password` | `ss.settings.sessions.revoke-all` | `ss.settings.sessions.revoke` |
| BP | `bp.settings.account` | `bp.settings.password` | `bp.settings.sessions.revoke-all` | `bp.settings.sessions.revoke` |

Only editable field: `phone`. Email is read-only (change via support). No `handle` column on users.
Password fields use CSS classes `ob-password-wrap` / `ob-password-toggle` (NOT `input-password-wrap` / `pw-toggle`).

### Unified: Security & 2FA (`section = 'security'`) → `SettingsSecurity`

| Portal | `enableMfaRoute` | `disableMfaRoute` | `verifyMfaRoute` | `enableEmailMfaRoute` | `verifyEmailMfaRoute` | `backupCodesRoute` |
|---|---|---|---|---|---|---|
| Provider | `provider.settings.mfa.enable` | `.disable` | `.verify` | `.enable-email` | `.verify-email` | `.backup-codes` |
| CS | `cs.settings.mfa.enable` | `.disable` | `.verify` | `.enable-email` | `.verify-email` | `.backup-codes` |
| SS | `ss.settings.mfa.enable` | `.disable` | `.verify` | `.enable-email` | `.verify-email` | `.backup-codes` |
| BP | `bp.settings.mfa.enable` | `.disable` | `.verify` | `.enable-email` | `.verify-email` | `.backup-codes` |

Two methods: TOTP (authenticator app, QR code via qrcodejs CDN) and Email OTP (via `SendEmailJob::dispatchSync()`). Backup codes: 8 × 6-digit numeric, one-time use, stored as JSON in `mfa_tokens.recovery_codes`.

**Provider-specific addition:** Security Alerts card rendered after `SettingsSecurity` in the same `section === 'security'` panel. Toggles saved via `router.put(route('provider.settings.security-alerts'), payload)`. CS/SS/BP do not have this inline card — their security alert preferences live in Notifications.

### Unified: Notifications (`section = 'notifications'`) → `SettingsNotifications`

| Portal | `updateRoute` | `notifCategories` |
|---|---|---|
| Provider | `provider.settings.notifications` | `notifCategories` reactive (7 categories: critical, referrals, messages, payments, plan, vault, network) |
| CS | `cs.settings.notifications` | `csNotifCategories` (8 categories: incident, plan, attestation, agreements, messages, docs, coverage, checkin) |
| SS | `ss.settings.notifications` | `ssNotifCategories` (8 categories: incidents, attestation, changes, coverage, roles, docs, messages, network) |
| BP | `bp.settings.notifications` | `bpNotifCategories` (8 categories: proposals, milestones, payments, messages, agreements, network, jobs, platform) |

### CS-specific: Messaging (`section = 'messaging'`) → `SettingsMessaging`

Uses `#extra-toggles` slot for "Critical Incident Thread Auto-Flag" toggle (currently hardcoded `on` with no save wired — UI only).

- `updateRoute`: `cs.settings.messaging` → `updateMessaging`
- `messagesRoute`: `cs.messages`

### CS-specific: CS Role Settings (`section = 'cs-steward'`)

Inline card. Two toggles: Show Name on Provider Public Profile, Request Vault Access on Assignment. Save via `router.put(route('cs.settings.role-prefs'))` → `updateRolePrefs`. Hydrated from `csRolePrefs` reactive.

### CS-specific: Document Vault Access (`section = 'documents-s'`)

Inline card showing vault access prefs. Save via `router.put(route('cs.settings.vault-prefs'))` → `updateVaultPrefs`.

### CS/SS/BP-specific: Privacy (`section = 'privacy'`)

| Portal | Route | Method |
|---|---|---|
| CS | `cs.settings.privacy` | `updatePrivacy` |
| SS | `ss.settings.privacy` | `updatePrivacy` |
| BP | `bp.settings.privacy` | `updatePrivacy` |

Provider privacy is section key `'privacy'` too, but route is `provider.settings.privacy-settings` → `updatePrivacySettings`.

### Provider-specific: Availability (`section = 'availability'`)

Week schedule (Mon–Sun, on/off, from/to times) + timezone select. Save: `router.put(route('provider.profile.availability'), { hours, timezone })`. Hydrates from `meta.availability`. Note: route is `provider.profile.availability` (profile namespace, not settings).

### Provider-specific: Referral Preferences (`section = 'referrals'`)

Tier-gated (Access tier sees an upgrade overlay). Has one toggle (accepting referrals). **No save route registered.** The "Save" button in the current codebase calls `toast.success('Referral preferences saved.')` — it is a fake save. No `referral-prefs` route exists. Do not rely on it. When a real route is added, it should POST to a new `provider.settings.referral-prefs` route.

### Provider-specific: CS/SS/Vault/Agreement Settings (Operations group)

Four separate inline panels, each with its own dedicated `router.put` call:

| Section | Route | Method |
|---|---|---|
| `csettings` | `provider.settings.cs-settings` | `updateCsSettings` |
| `ssettings` | `provider.settings.ss-settings` | `updateSsSettings` |
| `vault` | `provider.settings.vault-alerts` | `updateVaultAlerts` |
| `agreements` | `provider.settings.agreement-alerts` | `updateAgreementAlerts` |

CS settings toggles: CS Activation permission, Annual CS Check-In Reminder, Notify CS on Plan Changes.
SS settings toggles: Notify SS on Critical Incident, Notify SS on Plan Changes, SS Annual Attestation Reminder.
Vault alert toggles: Notify on Vault Access, Notify on Vault Unlock.
Agreement alert toggles: Agreement Expiry Reminder (30 days), Notify on Countersign.

### Provider-specific: My Services Settings (`section = 'myservices'`) — [lockedForAccess]

Gated to Practice tier. `router.put(route('provider.settings.services-settings'), payload)`. Toggles: mode/showPublic/acceptBookings/showPricing/bpDiscoverable. Form fields: bookingExpiry, sessionBuffer, hourlyRate. Hydrates from `meta.services_prefs`.

### Provider-specific: Privacy & Visibility (`section = 'privacy'`)

`router.put(route('provider.settings.privacy-settings'))`. Level picker (public/network/private) + 6 toggles. Hydrates from `meta.privacy_prefs`.

### Provider-specific: Network Settings (`section = 'network'`)

`router.put(route('provider.settings.network-settings'))`. Connection prefs, geo focus select, network notification toggles. Hydrates from `meta.notify_network`.

### Unified: Appearance (`section = 'appearance'`) → `SettingsAppearance`

| Portal | `updateRoute` |
|---|---|
| Provider | `provider.settings.appearance` |
| CS | `cs.settings.appearance` |
| SS | `ss.settings.appearance` |
| BP | `bp.settings.appearance` |

Three themes: `gold` (default), `gold-dark`, `slate`. Dark mode toggle. Timezone select (default `America/New_York`). Hydrates from `meta.appearance`. Provider also reads `meta.appearance.timezone` for the availability timezone select (shared field).

### Provider-specific: Subscription & Plan (`section = 'billing'`)

Fully wired to Stripe. Subscription data from `SubscriptionService::getFullSubscriptionData()` passed as `subscription` prop. Key reactive data computed from `sub` prop:

- `subStatus`: `active | past_due | none`
- `subOnGracePeriod`: `sub.on_grace_period`
- `currentTier`: `sub.tier || user.tier`
- `hasMaat`: `sub.has_maat_addon`
- `prices`: price IDs from `sub.prices` (env-injected via `#aegis-config`)

Plan actions:
- Swap (upgrade/downgrade/cycle change): shows `confirmSwap` modal → `doSwapPlan()` → POST `provider.settings.subscription.swap`
- Cancel: shows `confirmCancel` ref → `cancelPlan()` → POST `provider.settings.subscription.cancel`
- Resume: shows `confirmResume` ref → `doResumePlan()` → POST `provider.settings.subscription.resume`
- MAAT toggle: shows `confirmMaat` modal → `doToggleMaat()` → POST `provider.settings.subscription.maat`

Founding Member banner shown when `user.is_founding_member && subStatus !== 'none'`.

### CS-specific: Subscription & Plan (`section = 'billing'`)

Only shown for Business CS (`!isInvitedCs`). Invited CS sees a "free Invited CS" notice card instead. Subscription data from `subscription` prop (null for invited CS).

Actions:
- Swap: `confirmCsSwap` → POST `cs.settings.subscription.swap`
- Cancel: `confirmCsCancel` → POST `cs.settings.subscription.cancel`
- Resume: `confirmCsResume` → POST `cs.settings.subscription.resume`
- Billing portal: GET `cs.settings.billing.portal`
- Stripe Connect onboard: GET `cs.settings.connect.onboard`

### BP-specific: Subscription & Plan (`section = 'billing'`)

BP always has a paid subscription. Actions mirror CS (swap/cancel/resume). Stripe Connect onboard: GET `bp.settings.connect.onboard`.

### SS billing section

SS has `'billing'` as a nav key but it shows a "no subscription" / free-role notice card. No Stripe calls. No subscription prop passed from controller.

### Provider-specific: Billing & Invoices (`section = 'invoices'`)

Payment methods list from `sub.payment_methods`. Actions:
- Set default: `router.post(route('provider.settings.payment.default'), { payment_method_id })`
- Remove: `router.delete(route('provider.settings.payment.remove'), { data: { payment_method_id } })`
- Store (native add-card): POST `provider.settings.payment.store` → `storePaymentMethod` (mirrors to `stripe_payment_method_id`)
- Setup-intent (for native card modal): POST `provider.settings.payment.setup-intent`

Invoice history table: `sub.invoices` — `id, paid_at, product_name, description, amount_cents, status, pdf_url`.

### Provider-specific: Integrations (`section = 'integrations'`)

Stripe Connect card: shows connected account ID or "Connect Stripe Account" button linking to `provider.settings.connect.onboard`. `account.updated` webhook flips `stripe_connected` automatically. Billing portal link: `provider.settings.billing.portal`. Otherwise shows empty state (no third-party integrations yet).

CS and BP show their Stripe Connect card in their billing section, not a separate integrations panel.

### Unified: Account Actions (`section = 'changes'` / `'danger'`) → `SettingsDangerZone`

| Portal | Section key | `deleteRoute` | `pauseRoute` | `resumeRoute` | `exportRoute` |
|---|---|---|---|---|---|
| Provider | `changes` | `provider.settings.account.delete` | `provider.settings.account.pause` | `provider.settings.account.resume` | `provider.settings.account.export` |
| CS | `danger` | `cs.settings.account.delete` | `cs.settings.account.pause` | `cs.settings.account.resume` | `cs.settings.account.export` |
| SS | `danger` | `ss.settings.account.delete` | `ss.settings.account.pause` | `ss.settings.account.resume` | `ss.settings.account.export` |
| BP | `danger` | `bp.settings.account.delete` | `bp.settings.account.pause` | `bp.settings.account.resume` | `bp.settings.account.export` |

Pause state stored in `user_meta.account_paused` (NOT `users.paused_at` — that column belongs to Cashier). DangerZone reads live pause state from `usePage().props.auth.user.is_paused`.

**All four DangerZone actions are fully wired end-to-end** in all 4 portal controllers with real DB writes and activity logging.

---

## Hero banner `#actions` slot

All 4 portal Settings pages include an Activity link in the hero banner:

| Portal | Link href |
|---|---|
| Provider | `route('provider.activity') + '?event_type=account'` |
| CS | `route('cs.activity') + '?event_type=account'` |
| SS | `route('ss.activity') + '?event_type=account'` |
| BP | `route('bp.activity') + '?event_type=account'` |

---

## URL param routing (Provider only)

Provider Settings.vue supports query params on mount:

```js
?tab=billing        → sets section to 'billing'
?tab=invoices       → sets section to 'invoices'
?upgrade=1          → sets section to 'billing' AND opens modals.showUpgrade
?anchor=payment-rates → after panel renders, scrolls to #settings-anchor-payment-rates
```

---

## Known gaps (as of repo 1405245)

### Gap 1 — Provider Settings.vue has 9 orphan modals + 1 fake-save button

The following are left-behind pre-shared-component code. All 9 modal v-models exist in `modals` reactive but have **zero trigger points** (`modals.X = true` not called anywhere):

`revokeAll`, `setup2fa`, `viewBackup`, `exportSettings`, `exportData`, `pauseAccount`, `deleteAccount`, `addIntegration`, `newApiKey`

These duplicate functionality now covered by `SettingsAccount`, `SettingsSecurity`, and `SettingsDangerZone`. Also: Referral Prefs Save button at L250 calls `toast.success('Referral preferences saved.')` with no route call. None of these affect runtime (no trigger means no user-visible issue), but they are dead code and future confusion.

**Fix:** Delete all 9 orphan modal blocks, trim `modals` reactive to `{ showUpgrade: false }`, remove fake referral save button.

### Gap 2 — CS + BP `storePaymentMethod` route missing

`cs.settings.payment.setup-intent` and `bp.settings.payment.setup-intent` both exist and work. But `cs.settings.payment.store` and `bp.settings.payment.store` are not registered, and neither `CS/SettingsController` nor `BP/SettingsController` has a `storePaymentMethod` method. `AddCardModal.vue` cannot complete the save step for CS or BP.

**Fix:** Add `storePaymentMethod(Request $request): RedirectResponse` to both controllers (mirror the Provider one — validate `payment_method_id`, `updateDefaultPaymentMethod` via Cashier, mirror to `stripe_payment_method_id`). Register routes.

### Gap 3 — SS Settings.vue missing `sessions` in `defineProps`

SS `defineProps` does not declare `sessions`. `const sessions = computed(() => props.sessions ?? [])` resolves to `undefined ?? []` = `[]` always. The controller passes `sessions` correctly but Vue never receives it. SettingsAccount will always show "No active sessions."

**Fix:** Add `sessions: { type: Array, default: () => [] }` to SS `defineProps`.

### Gap 4 — CS nav missing 'account' item

CS nav has no button for `section === 'account'`. The SettingsAccount component renders when that section is active, but there is no way to navigate to it from the sidebar. Profile section links to profile edit, not account settings.

**Fix:** Add `{ key: 'account', label: 'Account & Login', icon: 'lock' }` to the CS nav Account group.

### Gap 5 — Referral Preferences: no backend route

No `provider.settings.referral-prefs` route exists. The referral toggles are clientside-only. The save button fires a fake toast. Not regressions from prior state — never had a route — but logged as a known pending item.

---

## `user_meta` keys used by settings

| Key | Type | Panel |
|---|---|---|
| `account_paused` | string `'1'`/`'0'` | Danger Zone — pause state |
| `pause_prefs` | JSON | Pause until/reason/message |
| `has_cs_portal` | string `'1'` | Profile — portal access card |
| `has_ss_portal` | string `'1'` | Profile — portal access card |
| `appearance` | JSON `{ theme, dark_mode, timezone }` | Appearance |
| `availability` | JSON `{ mon:{on,from,to}, ... }` | Provider availability |
| `privacy_prefs` | JSON | Provider privacy |
| `services_prefs` | JSON | Provider My Services |
| `notify_prefs` | JSON | Notifications legacy/extra |
| `notify_categories` | array | Notifications category channel states |
| `notify_security` | JSON `{ alertOnNewLogin, sessionTimeout }` | Provider security alerts |
| `notify_cs` | JSON `{ activation, annualReminder, notifyOnChange }` | Provider CS settings |
| `notify_ss` | JSON `{ notifyIncident, notifyChange, annualAttest }` | Provider SS settings |
| `notify_vault` | JSON `{ notifyAccess, notifyUnlock }` | Provider vault alerts |
| `notify_agreements` | JSON `{ expiryReminder, notifyCountersign }` | Provider agreement alerts |
| `notify_network` | JSON `{ requireApproval, dataUse, hideFromBP, connectionAlerts, weeklyDigest, featureUpdates, geoFocus }` | Provider network |
| `notify_billing` | JSON `{ invoiceEmails }` | Provider billing |
| `messaging_prefs` | JSON `{ who, status, readReceipts, onlineStatus, awayText }` | SettingsMessaging |
| `email_prefs` | JSON `{ digestFreq, digest, activityDigest, productUpdates, unsubAll }` | SettingsEmailPrefs |
| `cs_role_prefs` | JSON | CS role settings |
| `cs_vault_prefs` | JSON | CS vault preferences |
| `cs_assignment_role` | string | CS index (Primary/Alternate) |

---

*Validated against `github.com/rehanurrashid/aegis-laravel @ 1405245` on 2026-07-09.*
*Derived entirely from reading actual code — not prior documentation.*
