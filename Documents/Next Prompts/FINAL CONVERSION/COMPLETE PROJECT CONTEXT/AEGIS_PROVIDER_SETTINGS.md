# AEGIS_PROVIDER_SETTINGS.md

> **Purpose:** Canonical reference for every setting in the Provider Portal Settings page. For each setting: what it controls, where the value is stored, how it affects platform behaviour, which backend endpoint persists it, and implementation status. Use this file to drive backend wiring for each panel.

**Last updated:** July 2026 — matches `Settings.vue` as of commit `2cd19de`

---

## Architecture Overview

Settings are persisted via two mechanisms:

| Mechanism | Used for | Table |
|---|---|---|
| `user_meta` key-value store | All preference toggles, availability, appearance | `user_meta` (key, value, type) |
| `users` table columns | Email, phone, password, MFA status | `users` |
| Stripe / Cashier | Subscription plan, payment methods, invoices | Stripe API + `subscriptions` table |

All settings routes are under the `provider.` Ziggy prefix (`/provider/settings/...`).

**Controller:** `App\Http\Controllers\Provider\SettingsController`
**Service:** `App\Services\ProfileService` (meta read/write), `App\Services\SubscriptionService` (billing)

---

## Panel 1 — Profile & Identity (`section = 'profile'`)

**Purpose:** Read-only summary of the user's unified Aegis identity. No settings are saved here — it links to Edit Profile.

| Element | Type | Purpose | Backend |
|---|---|---|---|
| Profile photo + display name | Display | Shows `users.display_name`, `users.avatar_url` | Read-only from `users` |
| Title + credentials | Display | Shows `users.title`, `users.credentials` | Read-only from `users` |
| Email / phone / location | Display | Shows unified identity fields | Read-only from `users` |
| Plan badge (Access / Practice) | Display | Shows current subscription tier | `subscription.tier` from `SubscriptionService` |
| **View Public Profile** button | Link | Opens `/public/provider/{slug}` in new tab | Route: `public.provider` |
| **Edit Full Profile** button | Link | Navigates to Edit Profile page | Route: `provider.profile.index` |

**Portal Access sub-card:**

| Portal | Visible when | Link |
|---|---|---|
| Practitioner Portal | Always (current portal) | `provider.profile.index` |
| Continuity Steward Portal | `user.has_cs_portal === true` | `cs.dashboard` |
| Support Steward Portal | `user.has_ss_portal === true` | `ss.dashboard` |

> **Implementation note:** `has_cs_portal` and `has_ss_portal` must be appended to the `user` prop in `HandleInertiaRequests.php` shared data. Query `user_role_assignments` table.

---

## Panel 2 — Account & Login (`section = 'account'`)

### 2a — Credentials

| Field | Stored in | Route | Controller method | Status |
|---|---|---|---|---|
| Primary Email | `users.email` | `PUT /settings/email` | `SettingsController::updateEmail` | ⚠ Not yet wired |
| Phone Number | `users.phone` | `PUT /settings/phone` | `SettingsController::updatePhone` | ⚠ Not yet wired |
| Username / Handle | `users.handle` or `user_meta.handle` | `PUT /settings/handle` | `SettingsController::updateHandle` | ⚠ Not yet wired |

**Email change flow:** Changing primary email must trigger a re-verification email to the new address. Do not update `users.email` directly — update a `pending_email` field and confirm on click.

### 2b — Change Password

| Field | Validation | Route | Controller method | Status |
|---|---|---|---|---|
| New Password (min 12 chars) | Required, min:12, regex uppercase+number+special | `PUT /provider/settings/password` | `PasswordResetController::change` | ✅ Wired |
| Confirm New Password | Must match new password | — | Client-side Vuelidate | ✅ |

**Note:** Current password is NOT requested (by design). Session authentication is sufficient.

### 2c — Active Sessions

| Feature | Purpose | Backend |
|---|---|---|
| List active sessions | Shows device name, IP, last active | Read from `sessions` table or Sanctum tokens |
| **Revoke All** button | Invalidates all other sessions | `DELETE /settings/sessions` → `SettingsController::revokeAllSessions` ⚠ Not yet wired |
| Per-session revoke | Invalidates one session | `DELETE /settings/sessions/{id}` ⚠ Not yet wired |

---

## Panel 3 — Security & Two-Factor Authentication (`section = 'security'`)

### 3a — 2FA Methods

| Method | Key | Purpose | Backend |
|---|---|---|---|
| Authenticator App (TOTP) | `authenticator` | Standard TOTP via Google Authenticator / Authy | `POST /settings/mfa/enable` → `MfaController::enable` ✅ |
| Email Code | `email` | 6-digit OTP to primary email | ⚠ Not yet wired separately |
| View Backup Codes | — | Shows one-time recovery codes | `GET /settings/mfa/backup-codes` ⚠ Not yet wired |
| Setup 2FA modal | — | QR code + TOTP verification | `POST /settings/mfa/verify` → `MfaController::verify` ✅ |

> **Note:** SMS 2FA removed intentionally. Only Authenticator App and Email OTP supported.

### 3b — Security Alerts

> Security alerts have been **moved to the Notifications panel** (section 4b). This panel now shows only 2FA methods + a hint directing users to Notifications. The `user_meta` keys and wiring below still apply.

| Toggle | `user_meta` key | Default | Effect |
|---|---|---|---|
| Alert on New Login | `notify_new_login` | `true` | Sends email to `users.email` on any new device login |
| Session Timeout (30 min inactivity) | `session_timeout_enabled` | `true` | Sets session `lifetime` to 30 min for this user |

**Route:** `PUT /provider/settings/notifications` → `SettingsController::updateNotifications`

---

## Panel 4 — Notification Preferences (`section = 'notifications'`)

**This is the single place for all notification-type toggles across the platform.** Toggles from CS Settings, SS Settings, Vault, Agreements, and Billing that were notification-related have all been consolidated here.

**Route:** `PUT /provider/settings/notifications` → `SettingsController::updateNotifications`
**Storage:** `user_meta` keys — see full key list in `user_meta` Reference section below.

### 4a — Category Channel Matrix

Each category has three delivery channel booleans: `push`, `email`, `inapp`.

| Category | `user_meta` key group | Triggered by | Effect |
|---|---|---|---|
| Critical Incident Alerts | `notify_incident_{push\|email\|inapp}` | Emergency activation, CS/SS verification | Fires `CriticalIncidentNotification` |
| Referral Updates | `notify_referral_{push\|email\|inapp}` | Referral created, accepted, declined | Fires `ReferralNotification` |
| Direct Messages | `notify_message_{push\|email\|inapp}` | New message in any thread | Fires `MessageNotification` |
| Payments & Invoices | `notify_payment_{push\|email\|inapp}` | Invoice paid, subscription renewed, payment failed | Fires `PaymentNotification` |
| Continuity Plan Updates | `notify_plan_{push\|email\|inapp}` | Plan created, amended, attested | Fires `ContinuityPlanNotification` |
| Vault Activity | `notify_vault_{push\|email\|inapp}` | Document uploaded, accessed, emergency unlock | Fires `VaultNotification` |
| Network & Connections | `notify_network_{push\|email\|inapp}` | New connection, BP activity | Fires `NetworkNotification` |

### 4b — Security & Login Alerts

| Toggle | `user_meta` key | Default | Effect |
|---|---|---|---|
| Alert on New Login | `notify_new_login` | `true` | Email on new device login |
| Session Timeout (30 min inactivity) | `session_timeout_enabled` | `true` | Auto-logout after 30 min inactivity |

### 4c — Steward Notifications *(moved here from CS/SS Settings panels)*

| Toggle | Model key | `user_meta` JSON path | Default | Effect |
|---|---|---|---|---|
| Annual CS Check-In Reminder | `csPrefs.annualReminder` | `cs_prefs.annual_cs_reminder` | `true` | Scheduled reminder at 12-month CS anniversary |
| Notify CS on Plan Changes | `csPrefs.notifyOnChange` | `cs_prefs.notify_cs_on_plan_change` | `true` | Fires `ContinuityPlanAmendedNotification` → CS user |
| Notify SS on Critical Incident | `ssPrefs.notifyIncident` | `ss_prefs.notify_ss_on_incident` | `true` | Fires `CriticalIncidentNotification` → SS user |
| Notify SS on Plan Changes | `ssPrefs.notifyChange` | `ss_prefs.notify_ss_on_plan_change` | `true` | Fires `ContinuityPlanAmendedNotification` → SS user |
| SS Annual Attestation Reminder | `ssPrefs.annualAttest` | `ss_prefs.annual_ss_attest_reminder` | `true` | Scheduled reminder at 12-month SS anniversary |

> **Wiring note:** These toggles write to `cs_prefs` and `ss_prefs` JSON blobs in `user_meta`. The save route should be `PUT /provider/settings/notifications` (unified) or separate `PUT /settings/cs-preferences` and `PUT /settings/ss-preferences`. Backend must handle both keys in a single save call since they appear in one panel.

### 4d — Document & Agreement Alerts *(moved here from Vault / Agreements panels)*

| Toggle | Model key | `user_meta` JSON path | Default | Effect |
|---|---|---|---|---|
| Vault Access Alert | `vaultPrefs.notifyAccess` | `vault_prefs.notify_on_access` | `true` | Fires `VaultAccessedNotification` → provider when steward views/downloads |
| Emergency Vault Unlock Alert | `vaultPrefs.notifyUnlock` | `vault_prefs.notify_on_emergency_unlock` | `true` | Fires `EmergencyVaultUnlockedNotification` → provider immediately |
| Agreement Expiry Reminder | `agreementPrefs.expiryReminder` | `agreement_prefs.expiry_reminder` | `true` | Scheduled job checks `agreements.expires_at`, fires 30 days before |
| Agreement Countersigned | `agreementPrefs.notifyCountersign` | `agreement_prefs.notify_countersign` | `true` | Fires when steward countersigns an agreement |

### 4e — Billing & Invoices *(moved here from Billing panel)*

| Toggle | Model key | `user_meta` key | Default | Effect |
|---|---|---|---|---|
| Invoice Email Notifications | `financial.invoiceEmails` | `financial_invoice_emails` | `true` | When `true`: Stripe webhook `invoice.paid` + `invoice.payment_failed` trigger email to provider |

### 4f — Network & Updates

| Toggle | `user_meta` key | Default | Effect |
|---|---|---|---|
| New Connection Request Alerts | `notify_connection_request` | `true` | Push + email when someone requests to connect |
| Weekly Network Digest Email | `notify_weekly_digest` | `true` | Weekly summary email every Monday 8 AM |
| New Aegis Features & Updates | `notify_product_updates` | `false` | Optional marketing/product update emails |

---

## Panel 5 — Availability & Hours (`section = 'availability'`)

**Route:** `PUT /provider/profile/availability` → `ProviderProfileController::updateAvailability`
**Storage:** `user_meta.availability` (JSON)

| Setting | `user_meta` path | Type | Effect |
|---|---|---|---|
| Day schedule (Mon–Sun on/off + time range) | `availability.hours.days[]` | JSON array | Shown on public profile; used by referral matching |
| Telehealth States Licensed | `availability.telehealth_states` | string (CSV) | Shown on public profile under "Licensed in" |
| Next Available Appointment | `availability.next_available` | date string | Shown on public profile as next open slot |

> **Note:** "Accepting New Clients" was removed from this panel. It is controlled by Referral Preferences → Currently Accepting Referrals (writes to `user_meta.referral_prefs.accepting`).

**JSON structure stored in `user_meta.availability`:**
```json
{
  "hours": {
    "days": [
      { "key": "mon", "on": true, "from": "09:00", "to": "17:00" }
    ]
  },
  "telehealth_states": "NY, NJ, CT",
  "next_available": "2026-07-15"
}
```

---

## Panel 6 — Referral Preferences (`section = 'referrals'`)

**Route:** `PUT /provider/settings/referral-preferences` → `SettingsController::updateReferralPrefs`
**Storage:** `user_meta.referral_prefs` (JSON)

| Setting | Key | Default | Effect |
|---|---|---|---|
| Currently Accepting Referrals | `accepting` | `true` | When `false`: public profile shows "Not Accepting Referrals" badge; referral form disabled for this provider; referral engine excludes this provider |

```json
{ "accepting": true }
```

---

## Panel 7 — Continuity Steward Settings (`section = 'csettings'`)

**Route:** `PUT /provider/settings/cs-preferences` → `SettingsController::updateCsPrefs`
**Storage:** `user_meta.cs_prefs` (JSON)

> **Restructured:** This panel now contains only the **behaviour setting** for CS. All notification toggles (Annual CS Check-In Reminder, Notify CS on Plan Changes) have been moved to the Notifications panel (section 4c). The panel shows a hint directing users there.

| Toggle | Key | Default | Effect |
|---|---|---|---|
| Emergency CS Activation | `allow_cs_activation` | `true` | When `true`: CS can declare a critical incident and activate the Continuity Plan on the provider's behalf after Aegis admin verification. When `false`: activation requires provider to initiate. |

```json
{
  "allow_cs_activation": true,
  "annual_cs_reminder": true,
  "notify_cs_on_plan_change": true
}
```

> The full `cs_prefs` JSON still includes all three keys — `annual_cs_reminder` and `notify_cs_on_plan_change` are written from the Notifications panel.

---

## Panel 8 — Support Steward Settings (`section = 'ssettings'`)

**Route:** `PUT /provider/settings/ss-preferences` → `SettingsController::updateSsPrefs`
**Storage:** `user_meta.ss_prefs` (JSON)

> **Restructured:** All toggles in this panel were notification-type and have been moved to Notifications (section 4c). The panel now shows only a link to Notifications and the Manage SS button. No behaviour-only settings remain here currently.

```json
{
  "notify_ss_on_incident": true,
  "notify_ss_on_plan_change": true,
  "annual_ss_attest_reminder": true
}
```

> All three keys are written from the Notifications panel (section 4c).

---

## Panel 9 — Document Vault (`section = 'vault'`)

**Route:** `PUT /provider/settings/vault-preferences` → `SettingsController::updateVaultPrefs`
**Storage:** `user_meta.vault_prefs` (JSON)

> **Restructured:** Both notification toggles (Vault Access Alert, Emergency Vault Unlock Alert) have been moved to Notifications (section 4d). The panel now shows only the info alert about per-steward access levels and the Open Vault link. No toggles remain here.

```json
{
  "notify_on_access": true,
  "notify_on_emergency_unlock": true
}
```

> Both keys are written from the Notifications panel (section 4d).

**Implementation dependencies:**
- `VaultController::download()` must check `vault_prefs.notify_on_access` and dispatch `VaultAccessedNotification` if `true`
- `IncidentController` must check `vault_prefs.notify_on_emergency_unlock` on emergency unlock

---

## Panel 10 — Agreements & Contracts (`section = 'agreements'`)

**Route:** `PUT /provider/settings/agreement-preferences` → `SettingsController::updateAgreementPrefs`
**Storage:** `user_meta.agreement_prefs` (JSON)

> **Restructured:** Both notification toggles (Expiry Reminder, Countersign Alert) have been moved to Notifications (section 4d). The panel now shows only the Active Agreements display and a pointer to Notifications.

### 10a — Active Agreements display
Read from `agreements` table filtered by `provider_id` and `status = active`. Display-only.

```json
{
  "expiry_reminder": true,
  "notify_countersign": true
}
```

> Both keys are written from the Notifications panel (section 4d).

---

## Panel 11 — My Services Settings (`section = 'myservices'`)

**Route:** `PUT /provider/settings/services-preferences` → `SettingsController::updateServicesPrefs`
**Storage:** `user_meta.services_prefs` (JSON)

### 11a — Services Mode

| Toggle | Key | Default | Platform Effect |
|---|---|---|---|
| Integrative Business Services Mode | `mode` | `false` | When `true`: unlocks "My Services" sidebar nav; shows "Integrative Business Services" badge on public profile; enables service creation, booking, BP discovery |
| Show Services on Public Profile | `show_public` | `true` | When `false`: services hidden from public profile even if mode is on |
| Accept Booking Requests | `accept_bookings` | `true` | When `false`: booking request form disabled on provider's services |
| Show Pricing Publicly | `show_pricing` | `true` | When `false`: rates shown as "Contact for pricing" |

### 11b — Visibility in Job Marketplace

| Toggle | Key | Default | Platform Effect |
|---|---|---|---|
| Visible to Business Partners | `bp_discoverable` | `true` | When `true`: provider appears in BP job marketplace search |

### 11c — Booking Preferences

| Setting | Key | Default | Options | Effect |
|---|---|---|---|---|
| Booking Request Expiry | `booking_expiry` | `48h` | `24h`, `48h`, `72h`, `1week` | Auto-decline requests after this window |
| Buffer Between Sessions | `session_buffer` | `30min` | `none`, `15min`, `30min`, `1hr` | Minimum gap enforced between sessions |

### 11d — Payment & Rates

| Setting | Key | Type | Effect |
|---|---|---|---|
| Default Hourly Rate | `hourly_rate` | integer (cents) | Shown on public profile when `mode = true`. Store as cents (e.g. `15000` = $150). |
| Payment | Always Stripe Connect | — | Fixed — Aegis uses destination charges only. |

```json
{
  "mode": false,
  "show_public": true,
  "accept_bookings": true,
  "show_pricing": true,
  "bp_discoverable": true,
  "booking_expiry": "48h",
  "session_buffer": "30min",
  "hourly_rate": 15000
}
```

---

## Panel 12 — Privacy & Visibility (`section = 'privacy'`)

**Route:** `PUT /provider/settings/privacy` → `SettingsController::updatePrivacy`
**Storage:** `user_meta.privacy_prefs` (JSON)

### 12a — Visibility Level

| Level | Key value | Effect |
|---|---|---|
| Public | `public` | Visible to all Aegis providers; appears in public search |
| Network | `network` | Visible only to connected providers |
| Private | `private` | Hidden from all search; invitation-only |

### 12b — Visibility Toggles

| Toggle | Key | Default | Effect |
|---|---|---|---|
| Appear in Provider Search | `search` | `true` | Excluded from referral search when `false` |
| Show in Integrative Care Network | `icn` | `true` | Excluded from ICN matching suggestions when `false` |
| Show Ratings & Reviews Publicly | `ratings` | `true` | Hides peer review section on public profile when `false` |
| Show Location (City/State Only) | `location` | `true` | Location hidden from public profile when `false` |
| Share Referral Statistics Publicly | `referral_stats` | `true` | Response time + acceptance rate hidden when `false` |
| Show Demographics on Public Profile | `demographics` | `true` | Language, specialty, location hidden when `false` |

```json
{
  "level": "network",
  "search": true,
  "icn": true,
  "ratings": true,
  "location": true,
  "referral_stats": true,
  "demographics": true
}
```

---

## Panel 13 — Network & Connection Settings (`section = 'network'`)

**Route:** `PUT /provider/settings/network-preferences` → `SettingsController::updateNetworkPrefs`
**Storage:** `user_meta.network_prefs` (JSON)

> **Note:** Network notification toggles (New Connection Alerts, Weekly Digest, Feature Updates) have been moved to the Notifications panel (section 4f). This panel contains only connection behaviour settings and geographic focus.

| Toggle / Setting | Key | Default | Effect |
|---|---|---|---|
| Allow Use of My Data for Network Suggestions | `data_use` | `true` | Anonymised specialty/location data fed into ICN matching algorithm |
| Hide From Business Network Search | `hide_from_bp` | `false` | When `true`: excluded from BP job marketplace provider search |
| Geographic Focus for Suggestions | `geo_focus` | `50mi` | ICN matching primary geographic filter for this provider |

```json
{
  "data_use": true,
  "hide_from_bp": false,
  "geo_focus": "50mi"
}
```

---

## Panel 14 — Appearance & Timezone (`section = 'appearance'`)

**Route:** `PUT /provider/settings/appearance` → `SettingsController::updateAppearance`
**Storage:** `user_meta.appearance` (JSON)

### 14a — Color Theme

| Theme | Key value | Description |
|---|---|---|
| Aegis Gold | `gold` | Classic warm gold (default) |
| Gold Dark | `gold-dark` | Deep rich gold palette |
| Slate Blue | `slate` | Cool professional slate |

### 14b — Dark Mode

| Toggle | Key | Default | Effect |
|---|---|---|---|
| Dark Mode | `dark_mode` | `false` | Applies `data-mode="dark"` to `<html>` |

### 14c — Timezone

| Setting | Key | Default | Effect |
|---|---|---|---|
| Timezone | `timezone` | `America/New_York` | All dates/times rendered in UI use this. Pass to `Carbon::setTimezone()` in notifications/emails. |

```json
{
  "theme": "gold",
  "dark_mode": false,
  "timezone": "America/New_York"
}
```

---

## Panel 15 — Subscription & Plan (`section = 'billing'`)

All wired. See `AEGIS_BILLING_LIFECYCLE.md` §19 for full detail.

| Plan | Monthly | Annual |
|---|---|---|
| Continuity Access | $29/mo | $23/mo ($276/yr) |
| Continuity Practice | $49/mo | $39/mo ($468/yr) |
| MAAT Add-on (requires Practice) | +$29/mo | +$23/mo (+$276/yr) |

| Action | Route | Status |
|---|---|---|
| Swap plan | `POST /settings/subscription/swap` | ✅ |
| Cancel plan | `POST /settings/subscription/cancel` | ✅ |
| Resume plan | `POST /settings/subscription/resume` | ✅ |
| Toggle MAAT | `POST /settings/subscription/maat` | ✅ |

---

## Panel 16 — Billing & Payment (`section = 'invoices'`)

> **Restructured:** "Invoice Email Notifications" toggle has been moved to Notifications panel (section 4e). The "Financial Controls" heading has been removed. This panel now contains only payment methods and invoice history.

### 16a — Payment Methods

| Action | Route | Status |
|---|---|---|
| Set default payment method | `POST /settings/payment-method/default` | ✅ |
| Remove payment method | `DELETE /settings/payment-method` | ✅ |
| Add payment method | `GET /settings/billing-portal` → Stripe portal | ✅ |

### 16b — Invoice History

Loaded from Stripe via `SubscriptionService::getFullSubscriptionData()`. Each row shows date, description, amount, status (pill badge), and PDF download link.

---

## Panel 17 — Integrations & Connected Apps (`section = 'integrations'`)

### 17a — Stripe Connect Setup

| State | Condition | UI |
|---|---|---|
| Not connected | `users.stripe_connect_id` null or `acct_demo_*` | "Connect Stripe Account" → `billingPortal` |
| Connected | Valid `stripe_connect_id` | Account ID + "Dashboard" link |

**Route:** `GET /settings/billing-portal` ✅

### 17b — Additional Integrations

Currently shows empty state. No hard-coded integrations. Future OAuth connectors will use:
- `user_meta.integrations` JSON: `{ provider, access_token (encrypted), connected_at }`
- `POST /settings/integrations/{provider}/connect`
- `DELETE /settings/integrations/{provider}`

---

## Panel 18 — Account Actions (`section = 'changes'`)

### 18a — Export All Data
| | |
|---|---|
| Action | Queues `ExportUserDataJob` — HIPAA-compliant ZIP emailed within 24 hours |
| Route | `POST /settings/export-data` → `SettingsController::exportData` |
| Status | ⚠ Not yet implemented |

### 18b — Pause Account
| | |
|---|---|
| Action | Sets `users.paused_until` + `users.pause_reason`; hides from search; shows "On Leave" banner |
| Route | `POST /settings/pause` → `SettingsController::pauseAccount` |
| Status | ⚠ Not yet implemented |

### 18c — Delete Account Permanently
| | |
|---|---|
| Action | Sets `users.deactivated_at = now()`, clears tokens, logs out |
| Route | `DELETE /settings/account` → `SettingsController::deleteAccount` |
| Status | ✅ Wired |

---

## Implementation Priority & Status

| Panel | Key Settings | Backend Status | Priority |
|---|---|---|---|
| Profile & Identity | Read-only display | ✅ Wired | — |
| Account & Login | Email, phone, handle, password | ⚠ Password ✅, Email/Phone/Handle ❌ | P1 |
| Security & 2FA | TOTP, backup codes, sessions | ⚠ MFA routes ✅, backup codes ❌, sessions ❌ | P1 |
| **Notification Preferences** | All channels + all steward/vault/agreement/billing notifs | ⚠ Partial — `notify_*` keys exist; steward/vault/agreement notif keys need adding to `updateNotifications` | **P1** |
| Availability & Hours | Schedule, telehealth states, next available | ✅ `profile.availability` route wired | P2 |
| Referral Preferences | Accepting toggle | ⚠ Exists in `network_prefs` via ProfileService | P2 |
| CS Settings | Emergency CS Activation only | ❌ `settings.cs-prefs` route missing | P2 |
| SS Settings | No UI toggles remain (all in Notifications) | ❌ `settings.ss-prefs` route missing (still needs to save from Notifications) | P2 |
| Document Vault | No UI toggles remain (all in Notifications) | ❌ `settings.vault-prefs` route missing (still needs to save from Notifications) | P2 |
| Agreement Preferences | No UI toggles remain (all in Notifications) | ❌ `settings.agreement-prefs` route missing (still needs to save from Notifications) | P3 |
| My Services Settings | Mode toggle, booking config, rates | ❌ Route missing | P2 |
| Privacy & Visibility | Visibility level, all toggles | ⚠ Exists via `profile.privacy` route | P2 |
| Network Settings | Data use, hide from BP, geo focus | ⚠ Exists via `profile.network` route | P3 |
| Appearance & Timezone | Theme, dark mode, timezone | ❌ Route missing | P3 |
| Subscription & Plan | Plan swap, cancel, resume, MAAT | ✅ Fully wired | — |
| Billing & Payment | Payment methods only (invoice email moved to Notifications) | ✅ Payment methods wired | — |
| Integrations | Stripe Connect portal link | ✅ Portal link wired | P3 |
| Account Actions | Pause, export, delete | ⚠ Delete ✅, Pause ❌, Export ❌ | P2 |

---

## `user_meta` Keys Reference

Complete flat list of all keys this settings page reads/writes, grouped by which panel owns them:

```
# ── Notification channels (Panel 4a) ──────────────────────────────────────
notify_incident_{push|email|inapp}
notify_referral_{push|email|inapp}
notify_message_{push|email|inapp}
notify_payment_{push|email|inapp}
notify_plan_{push|email|inapp}
notify_vault_{push|email|inapp}
notify_network_{push|email|inapp}

# ── Security alerts (Panel 4b) ────────────────────────────────────────────
notify_new_login                          ← boolean
session_timeout_enabled                   ← boolean

# ── Network & updates (Panel 4f) ─────────────────────────────────────────
notify_connection_request                 ← boolean
notify_weekly_digest                      ← boolean
notify_product_updates                    ← boolean

# ── Availability (Panel 5) ────────────────────────────────────────────────
availability                              ← JSON: { hours: { days[] }, telehealth_states, next_available }

# ── Referrals (Panel 6) ──────────────────────────────────────────────────
referral_prefs                            ← JSON: { accepting }

# ── CS prefs (Panel 7 + Notifications 4c) ────────────────────────────────
cs_prefs                                  ← JSON: {
                                               allow_cs_activation,       ← written from CS Settings panel
                                               annual_cs_reminder,        ← written from Notifications panel
                                               notify_cs_on_plan_change   ← written from Notifications panel
                                             }

# ── SS prefs (Notifications 4c only — no UI in SS Settings panel) ─────────
ss_prefs                                  ← JSON: {
                                               notify_ss_on_incident,     ← written from Notifications panel
                                               notify_ss_on_plan_change,  ← written from Notifications panel
                                               annual_ss_attest_reminder  ← written from Notifications panel
                                             }

# ── Vault prefs (Notifications 4d only — no UI in Vault panel) ────────────
vault_prefs                               ← JSON: {
                                               notify_on_access,          ← written from Notifications panel
                                               notify_on_emergency_unlock ← written from Notifications panel
                                             }

# ── Agreement prefs (Notifications 4d only — no UI in Agreements panel) ───
agreement_prefs                           ← JSON: {
                                               expiry_reminder,           ← written from Notifications panel
                                               notify_countersign         ← written from Notifications panel
                                             }

# ── Services (Panel 11) ──────────────────────────────────────────────────
services_prefs                            ← JSON: { mode, show_public, accept_bookings, show_pricing,
                                               bp_discoverable, booking_expiry, session_buffer, hourly_rate }

# ── Privacy (Panel 12) ───────────────────────────────────────────────────
privacy_prefs                             ← JSON: { level, search, icn, ratings, location, referral_stats, demographics }

# ── Network (Panel 13) ───────────────────────────────────────────────────
network_prefs                             ← JSON: { data_use, hide_from_bp, geo_focus }

# ── Appearance (Panel 14) ────────────────────────────────────────────────
appearance                                ← JSON: { theme, dark_mode, timezone }

# ── Billing (Notifications 4e only — no UI in Billing panel) ─────────────
financial_invoice_emails                  ← boolean (written from Notifications panel)
```

---

## Backend Routes to Add

Routes that exist in the UI but are not yet in `routes/web.php`:

```php
// In provider middleware group:

// Panel 2 — Account
Route::put('/settings/email',  [SettingsController::class, 'updateEmail'])->name('settings.email');
Route::put('/settings/phone',  [SettingsController::class, 'updatePhone'])->name('settings.phone');
Route::put('/settings/handle', [SettingsController::class, 'updateHandle'])->name('settings.handle');

// Panel 2 — Sessions
Route::delete('/settings/sessions',         [SettingsController::class, 'revokeAllSessions'])->name('settings.sessions.revoke-all');
Route::delete('/settings/sessions/{token}', [SettingsController::class, 'revokeSession'])->name('settings.sessions.revoke');

// Panel 3 — MFA backup codes
Route::get('/settings/mfa/backup-codes', [MfaController::class, 'backupCodes'])->name('settings.mfa.backup-codes');

// Panel 4 — Notifications (must now also accept cs_prefs, ss_prefs, vault_prefs,
//            agreement_prefs, financial_invoice_emails in the same request)
// Existing route: PUT /settings/notifications — extend its validated keys

// Panel 6 — Referral preferences
Route::put('/settings/referral-preferences', [SettingsController::class, 'updateReferralPrefs'])->name('settings.referral-prefs');

// Panel 7 — CS Settings (behaviour only)
Route::put('/settings/cs-preferences', [SettingsController::class, 'updateCsPrefs'])->name('settings.cs-prefs');

// Panel 8 — SS preferences (saved via Notifications route — no separate route needed
//            unless you want isolation. Recommended: add keys to updateNotifications)

// Panel 9 — Vault preferences (same — add to updateNotifications or:)
Route::put('/settings/vault-preferences', [SettingsController::class, 'updateVaultPrefs'])->name('settings.vault-prefs');

// Panel 10 — Agreement preferences (same — add to updateNotifications or:)
Route::put('/settings/agreement-preferences', [SettingsController::class, 'updateAgreementPrefs'])->name('settings.agreement-prefs');

// Panel 11 — Services preferences
Route::put('/settings/services-preferences', [SettingsController::class, 'updateServicesPrefs'])->name('settings.services-prefs');

// Panel 12 — Privacy
Route::put('/settings/privacy', [SettingsController::class, 'updatePrivacy'])->name('settings.privacy');

// Panel 13 — Network preferences
Route::put('/settings/network-preferences', [SettingsController::class, 'updateNetworkPrefs'])->name('settings.network-prefs');

// Panel 14 — Appearance
Route::put('/settings/appearance', [SettingsController::class, 'updateAppearance'])->name('settings.appearance');

// Panel 18 — Account actions
Route::post('/settings/export-data', [SettingsController::class, 'exportData'])->name('settings.export');
Route::post('/settings/pause',       [SettingsController::class, 'pauseAccount'])->name('settings.pause');
```

> **Key simplification vs previous version:** `settings.financial-controls` route is **removed** — Invoice Email Notifications is now saved via `PUT /settings/notifications` along with all other notification keys. `settings.ss-prefs`, `settings.vault-prefs`, and `settings.agreement-prefs` can optionally be merged into `updateNotifications` to keep the save call count down.

---

*Last updated: July 2026 — matches `Settings.vue` as of commit `2cd19de`*
