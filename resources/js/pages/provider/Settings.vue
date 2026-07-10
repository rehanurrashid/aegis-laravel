<template>
  <AppLayout :user="user" portal="practitioner" activePage="settings" pageTitle="Settings">

    <AegisHeroBanner eyebrow="Provider Portal" title="Account Settings" quiet>
      <template #actions>
        <a :href="route('provider.activity') + '?event_type=account'" class="btn-hero-ghost is-on-light">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
      </template>
    </AegisHeroBanner>

    <div class="settings-layout">

      <!-- SIDEBAR NAV -->
      <div class="settings-sidebar">
        <template v-for="grp in nav" :key="grp.group">
          <div class="page-sidebar-group">
            <div class="page-sidebar-label">{{ grp.group }}</div>
            <button v-for="it in grp.items" :key="it.key" type="button"
              class="page-sidebar-item" :class="{ active: section === it.key, 'is-locked': it.lockedForAccess && isAccessTier }"
              :style="it.danger ? 'color:var(--red)' : ''" @click="navClick(it)">
              <span class="page-sidebar-icon"><AegisIcon :name="it.icon" :size="15" /></span>
              {{ it.label }}
              <span v-if="it.badge" class="page-sidebar-badge">{{ it.badge }}</span>
            </button>
          </div>
        </template>
      </div>

      <div class="settings-content" ref="settingsContent">

        <!-- PROFILE & IDENTITY -->
        <div v-show="section === 'profile'" class="settings-panel active">
          <div class="alert alert-info" style="margin-bottom:16px">
            <div class="alert-icon"><AegisIcon name="users" :size="18" /></div>
            <div class="alert-content"><strong>Unified identity across all portals.</strong> Your photo, display name, email, and phone are shared across every portal you have access to — Practitioner, Continuity Steward, and Support Steward. Changes in <strong>Edit Profile</strong> update your identity platform-wide.</div>
          </div>
          <div class="card" style="margin-bottom:16px">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="user" :size="16" /></div>
                <div><div class="card-title">Profile Summary</div><div class="card-subtitle">Your public-facing identity on Aegis</div></div>
              </div>
              <a :href="route('public.provider', { slug: user?.slug ?? '' })" class="btn btn-outline btn-sm" target="_blank"><AegisIcon name="eye" :size="13" /> View Public Profile</a>
            </div>
            <div class="card-body">
              <div class="list-group">
                <div class="list-group-item" style="gap:14px">
                  <div
                    class="avatar avatar-lg avatar-gold"
                    :style="user?.avatar_url ? { backgroundImage: `url(${user.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center', color: 'transparent' } : {}"
                  >{{ user?.avatar_url ? '' : initials }}</div>
                  <div style="flex:1">
                    <div style="font-family:var(--font-serif);font-size:16px;font-weight:700;color:var(--text)">{{ displayName }}</div>
                    <div style="font-size:13px;color:var(--text-3);margin-top:2px">{{ user?.title ?? '' }}{{ user?.credentials ? ' · ' + user.credentials : '' }}</div>
                    <div style="font-size:12px;color:var(--text-4);margin-top:4px;display:flex;gap:14px;flex-wrap:wrap">
                      <span style="display:flex;align-items:center;gap:4px"><AegisIcon name="mail" :size="12" /> {{ user?.email ?? '' }}</span>
                      <span v-if="user?.phone" style="display:flex;align-items:center;gap:4px"><AegisIcon name="phone" :size="12" /> {{ user.phone }}</span>
                      <span v-if="user?.location" style="display:flex;align-items:center;gap:4px"><AegisIcon name="map-pin" :size="12" /> {{ user.location }}</span>
                    </div>
                  </div>
                  <span class="badge" :class="user?.tier === 'practice' ? 'badge-gold' : 'badge-blue'">{{ tierLabel }}</span>
                </div>
              </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end">
              <a :href="route('provider.profile.index')" class="btn btn-primary btn-sm"><AegisIcon name="edit" :size="13" /> Edit Full Profile</a>
            </div>
          </div>
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="shield" :size="16" /></div>
                <div><div class="card-title">Portal Access</div><div class="card-subtitle">Portals linked to your Aegis account</div></div>
              </div>
            </div>
            <div class="card-body" style="padding:0">
              <div class="list-group">
                <div class="list-group-item">
                  <div style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark);display:flex;align-items:center;justify-content:center;flex-shrink:0"><AegisIcon name="user" :size="16" /></div>
                  <div style="flex:1">
                    <div style="font-size:13px;font-weight:700;color:var(--text)">Practitioner Portal</div>
                    <div style="font-size:12px;color:var(--text-3)">Profile settings, referrals, continuity plan — {{ tierLabel }}</div>
                  </div>
                  <span v-if="currentPortal === 'provider'" class="badge badge-gold">Current</span>
                  <span v-else-if="isAccountPaused" class="badge badge-orange">Paused</span>
                  <span v-else class="badge badge-green">Active</span>
                  <a :href="route('provider.profile.index')" class="btn-icon" data-tooltip="Edit practitioner profile"><AegisIcon name="edit" :size="14" /></a>
                </div>
                <div v-if="user?.has_cs_portal" class="list-group-item">
                  <div style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-blue,var(--surface-2));color:var(--blue-dark,var(--text-2));display:flex;align-items:center;justify-content:center;flex-shrink:0"><AegisIcon name="shield-check" :size="16" /></div>
                  <div style="flex:1">
                    <div style="font-size:13px;font-weight:700;color:var(--text)">Continuity Steward Portal</div>
                    <div style="font-size:12px;color:var(--text-3)">Emergency response, client continuity oversight</div>
                  </div>
                  <span v-if="currentPortal === 'continuity_steward'" class="badge badge-gold">Current</span>
                  <span v-else class="badge badge-blue">Active</span>
                  <a :href="route('cs.dashboard')" class="btn-icon" data-tooltip="Open CS Portal"><AegisIcon name="external-link" :size="14" /></a>
                </div>
                <div v-if="user?.has_ss_portal" class="list-group-item">
                  <div style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-teal,var(--surface-2));color:var(--teal-dark,var(--text-2));display:flex;align-items:center;justify-content:center;flex-shrink:0"><AegisIcon name="users" :size="16" /></div>
                  <div style="flex:1">
                    <div style="font-size:13px;font-weight:700;color:var(--text)">Support Steward Portal</div>
                    <div style="font-size:12px;color:var(--text-3)">Administrative and operational support for providers</div>
                  </div>
                  <span v-if="currentPortal === 'support_steward'" class="badge badge-gold">Current</span>
                  <span v-else class="badge badge-teal">Active</span>
                  <a :href="route('ss.dashboard')" class="btn-icon" data-tooltip="Open SS Portal"><AegisIcon name="external-link" :size="14" /></a>
                </div>
              </div>
              <div style="padding:12px 18px;border-top:1px solid var(--border)">
                <div class="alert alert-gold" style="margin:0">
                  <div class="alert-icon"><AegisIcon name="info" :size="16" /></div>
                  <div class="alert-content" style="font-size:12px">Each portal has its own <strong>Edit Profile</strong> page for role-specific information. Photo, name, email, and phone are always shared.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ACCOUNT & LOGIN -->
        <div v-show="section === 'account'" class="settings-panel">
          <SettingsAccount
            :user="user"
            :sessions="sessions"
            update-password-route="provider.settings.password"
            update-account-route="provider.settings.account"
            revoke-session-route="provider.settings.sessions.revoke"
            revoke-all-route="provider.settings.sessions.revoke-all"
          />
        </div>

        <!-- SECURITY & 2FA -->
        <div v-show="section === 'security'" class="settings-panel">
          <SettingsSecurity
            enable-mfa-route="provider.settings.mfa.enable"
            disable-mfa-route="provider.settings.mfa.disable"
            verify-mfa-route="provider.settings.mfa.verify"
            backup-codes-route="provider.settings.mfa.backup-codes"
            enable-email-mfa-route="provider.settings.mfa.enable-email"
            verify-email-mfa-route="provider.settings.mfa.verify-email"
            :mfa-method="mfaMethod"
            :mfa-enabled="mfaEnabled"
            :user-email="user?.email ?? ''"
          />
          <div class="card" style="margin-top:16px">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="bell" :size="16" /></div>
                <div><div class="card-title">Security &amp; Login Alerts</div><div class="card-subtitle">Get notified about account access and session activity</div></div>
              </div>
            </div>
            <div class="card-body">
              <div v-for="al in securityAlerts" :key="al.key" class="toggle-row"><div class="toggle-info"><div class="toggle-label">{{ al.name }}</div><div class="toggle-desc">{{ al.sub }}</div></div><button type="button" class="toggle" :class="{ on: al.on }" @click="al.on = !al.on" :aria-pressed="al.on"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-primary btn-sm" :disabled="securityAlertSaving" @click="saveSecurityAlerts">
                  <AegisIcon name="check" :size="13" /> Save
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- NOTIFICATIONS -->
        <div v-show="section === 'notifications'" class="settings-panel">
          <SettingsNotifications
            update-route="provider.settings.notifications"
            subtitle="Delivery channels are unified across all portals."
            :notif-categories="notifCategories"
            :saved-prefs="meta?.notify_prefs ?? {}"
            :saved-categories="meta?.notify_categories ?? []"
          />
        </div>


        <!-- AVAILABILITY -->
        <div v-show="section === 'availability'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="calendar" :size="16" /></div>
                <div><div class="card-title">Availability &amp; Hours</div><div class="card-subtitle">Set your weekly schedule and client availability</div></div>
              </div>
            </div>
            <div class="card-body">
              <div class="avail-schedule">
                <div class="avail-schedule-header">
                  <span></span>
                  <span></span>
                  <span class="avail-col-label">From</span>
                  <span class="avail-col-label">To</span>
                </div>
                <div v-for="day in weekDays" :key="day.key" class="avail-day-row" :class="{ 'avail-day-off': !day.on }">
                  <button type="button" class="toggle" :class="{ on: day.on }" @click="day.on = !day.on" :aria-pressed="day.on"></button>
                  <span class="avail-day-name">{{ day.label }}</span>
                  <select class="form-select form-select-sm" v-model="day.from" :disabled="!day.on">
                    <option v-for="t in timeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                  </select>
                  <select class="form-select form-select-sm" v-model="day.to" :disabled="!day.on">
                    <option v-for="t in timeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                  </select>
                </div>
              </div>
              <div style="margin-top:20px;max-width:315px">
                <label class="form-label">Timezone</label>
                <select class="form-select" v-model="availTimezone">
                  <option value="America/New_York">Eastern Time (ET)</option>
                  <option value="America/Chicago">Central Time (CT)</option>
                  <option value="America/Denver">Mountain Time (MT)</option>
                  <option value="America/Los_Angeles">Pacific Time (PT)</option>
                  <option value="America/Phoenix">Arizona (no DST)</option>
                  <option value="Pacific/Honolulu">Hawaii (HST)</option>
                  <option value="America/Anchorage">Alaska (AKST)</option>
                  <option value="UTC">UTC</option>
                </select>
              </div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-primary btn-sm" :disabled="availSaving" @click="saveAvailability">
                  <AegisIcon name="check" :size="13" /> Save
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- REFERRAL PREFERENCES -->
        <div v-show="section === 'referrals'" class="settings-panel">
          <!-- ACCESS TIER GATE -->
          <div v-if="isAccessTier" class="settings-tier-gate">
            <div class="settings-tier-gate-inner">
              <div class="settings-tier-gate-icon"><AegisIcon name="lock" :size="22" /></div>
              <div class="settings-tier-gate-title">Referrals require Continuity Practice</div>
              <div class="settings-tier-gate-body">Send and receive patient referrals, access the full Integrative Network, and manage referral preferences — all included in Continuity Practice.</div>
              <button type="button" class="btn btn-gold" @click="section = 'billing'"><AegisIcon name="star" :size="14" /> Upgrade to Continuity Practice</button>
            </div>
          </div>
          <div :class="{ 'settings-tier-blurred': isAccessTier }">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="refresh" :size="16" /></div>
                <div><div class="card-title">Referral Preferences</div><div class="card-subtitle">How you send and receive referrals on Aegis</div></div>
              </div>
            </div>
            <div class="card-body">
              <div class="section-label">Incoming Referrals</div>
              <div v-for="row in referralIncoming" :key="row.key" class="toggle-row"><div class="toggle-info"><div class="toggle-label">{{ row.label }}</div><div class="toggle-desc">{{ row.desc }}</div></div><button type="button" class="toggle" :class="{ on: referralPrefs[row.key] }" @click="referralPrefs[row.key] = !referralPrefs[row.key]" :aria-pressed="referralPrefs[row.key]"></button></div>

              <div class="btn-group" style="justify-content:flex-end;margin-top:16px"><button type="button" class="btn btn-primary" @click="saveReferralPrefs"><AegisIcon name="check" :size="14" /> Save</button></div>
            </div>
          </div>
          </div><!-- end tier-blurred -->
        </div>

        <!-- CONTINUITY STEWARD SETTINGS -->
        <div v-show="section === 'csettings'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="shield" :size="16" /></div>
                <div><div class="card-title">Continuity Steward Settings</div><div class="card-subtitle">Preferences for your CS relationship and plan reminders</div></div>
              </div>
              <a :href="route('provider.stewards.index')" class="btn btn-outline btn-sm"><AegisIcon name="users" :size="13" /> Manage CS</a>
            </div>
            <div class="card-body">
              <div class="section-label">CS Permissions</div>
              <div v-for="row in csToggles" :key="row.key" class="toggle-row"><div class="toggle-info"><div class="toggle-label">{{ row.label }}</div><div class="toggle-desc">{{ row.desc }}</div></div><button type="button" class="toggle" :class="{ on: csPrefs[row.key] }" @click="csPrefs[row.key] = !csPrefs[row.key]" :aria-pressed="csPrefs[row.key]"></button></div>
              <div class="section-label" style="margin-top:20px">Steward Notifications</div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Annual CS Check-In Reminder</div><div class="toggle-desc">Remind me to verify CS availability and re-attest my Continuity Plan every 12 months</div></div><button type="button" class="toggle" :class="{ on: csPrefs.annualReminder }" @click="csPrefs.annualReminder = !csPrefs.annualReminder" :aria-pressed="csPrefs.annualReminder"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Notify CS on Plan Changes</div><div class="toggle-desc">Automatically notify your Continuity Steward when you update or amend your Continuity Plan</div></div><button type="button" class="toggle" :class="{ on: csPrefs.notifyOnChange }" @click="csPrefs.notifyOnChange = !csPrefs.notifyOnChange" :aria-pressed="csPrefs.notifyOnChange"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-primary btn-sm" :disabled="csSettingsSaving" @click="saveCsSettings">
                  <AegisIcon name="check" :size="13" /> Save
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- SUPPORT STEWARD SETTINGS -->
        <div v-show="section === 'ssettings'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="users" :size="16" /></div>
                <div><div class="card-title">Support Steward Settings</div><div class="card-subtitle">Preferences for your SS relationship and notifications</div></div>
              </div>
              <a :href="route('provider.ss.index')" class="btn btn-outline btn-sm"><AegisIcon name="users" :size="13" /> Manage SS</a>
            </div>
            <div class="card-body">
              <div class="section-label">SS Notification Alerts</div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Notify SS on Critical Incident</div><div class="toggle-desc">Your Support Steward is automatically notified when a critical incident is declared</div></div><button type="button" class="toggle" :class="{ on: ssPrefs.notifyIncident }" @click="ssPrefs.notifyIncident = !ssPrefs.notifyIncident" :aria-pressed="ssPrefs.notifyIncident"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Notify SS on Plan Changes</div><div class="toggle-desc">Automatically notify your Support Steward when you update or amend your Continuity Plan</div></div><button type="button" class="toggle" :class="{ on: ssPrefs.notifyChange }" @click="ssPrefs.notifyChange = !ssPrefs.notifyChange" :aria-pressed="ssPrefs.notifyChange"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">SS Annual Attestation Reminder</div><div class="toggle-desc">Remind me to verify SS contact info and re-confirm permissions annually</div></div><button type="button" class="toggle" :class="{ on: ssPrefs.annualAttest }" @click="ssPrefs.annualAttest = !ssPrefs.annualAttest" :aria-pressed="ssPrefs.annualAttest"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-primary btn-sm" :disabled="ssSettingsSaving" @click="saveSsSettings">
                  <AegisIcon name="check" :size="13" /> Save
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- DOCUMENT VAULT ACCESS -->
        <div v-show="section === 'vault'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="lock" :size="16" /></div>
                <div><div class="card-title">Document Vault Access</div><div class="card-subtitle">Control alerts for vault activity and emergency access</div></div>
              </div>
              <a :href="route('provider.vault.index')" class="btn btn-outline btn-sm"><AegisIcon name="lock" :size="13" /> Open Vault</a>
            </div>
            <div class="card-body">
              <div class="alert alert-info" style="margin-bottom:16px"><div class="alert-icon"><AegisIcon name="info" :size="16" /></div><div class="alert-content" style="font-size:12px">Steward vault access levels are managed per-steward in <a :href="route('provider.vault.index')" style="color:var(--gold-dark);font-weight:700">Document Vault →</a></div></div>
              <div class="section-label">Vault Alerts</div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Vault Access Alert</div><div class="toggle-desc">Receive an alert when a steward views or downloads a document from your vault</div></div><button type="button" class="toggle" :class="{ on: vaultPrefs.notifyAccess }" @click="vaultPrefs.notifyAccess = !vaultPrefs.notifyAccess" :aria-pressed="vaultPrefs.notifyAccess"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Emergency Vault Unlock Alert</div><div class="toggle-desc">Alert me immediately if the Emergency Vault is accessed during an active incident</div></div><button type="button" class="toggle" :class="{ on: vaultPrefs.notifyUnlock }" @click="vaultPrefs.notifyUnlock = !vaultPrefs.notifyUnlock" :aria-pressed="vaultPrefs.notifyUnlock"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-primary btn-sm" :disabled="vaultSettingsSaving" @click="saveVaultSettings">
                  <AegisIcon name="check" :size="13" /> Save
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- AGREEMENTS & CONTRACTS -->
        <div v-show="section === 'agreements'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="file-text" :size="16" /></div>
                <div><div class="card-title">Agreements &amp; Contracts</div><div class="card-subtitle">Status of your steward agreements and alert preferences</div></div>
              </div>
              <a :href="route('provider.documents.index')" class="btn btn-outline btn-sm"><AegisIcon name="file-text" :size="13" /> Manage Agreements</a>
            </div>
            <div class="card-body">
              <div class="section-label">Active Agreements</div>
              <div v-if="activeAgreements.length === 0" style="padding:18px 0;display:flex;align-items:center;gap:10px;color:var(--text-3)">
                <AegisIcon name="file-text" :size="16" />
                <span style="font-size:13px">No active steward agreements found.</span>
              </div>
              <div v-for="ag in activeAgreements" :key="ag.title" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid var(--border)"><div><div style="font-size:13px;font-weight:700;color:var(--text)">{{ ag.title }}</div><div style="font-size:12px;color:var(--text-3);margin-top:2px">{{ ag.meta }}</div></div><span class="badge badge-green" style="flex-shrink:0">Active</span></div>
              <div class="section-label" style="margin-top:20px">Agreement Alerts</div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Agreement Expiry Reminder</div><div class="toggle-desc">Notify me 30 days before any steward agreement or attestation is due</div></div><button type="button" class="toggle" :class="{ on: agreementPrefs.expiryReminder }" @click="agreementPrefs.expiryReminder = !agreementPrefs.expiryReminder" :aria-pressed="agreementPrefs.expiryReminder"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Agreement Countersigned</div><div class="toggle-desc">Alert me when a steward signs or countersigns an agreement sent by you</div></div><button type="button" class="toggle" :class="{ on: agreementPrefs.notifyCountersign }" @click="agreementPrefs.notifyCountersign = !agreementPrefs.notifyCountersign" :aria-pressed="agreementPrefs.notifyCountersign"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-primary btn-sm" :disabled="agreementSettingsSaving" @click="saveAgreementSettings">
                  <AegisIcon name="check" :size="13" /> Save
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- MY SERVICES SETTINGS -->
        <div v-show="section === 'myservices'" class="settings-panel">
          <!-- ACCESS TIER GATE -->
          <div v-if="isAccessTier" class="settings-tier-gate">
            <div class="settings-tier-gate-inner">
              <div class="settings-tier-gate-icon"><AegisIcon name="lock" :size="22" /></div>
              <div class="settings-tier-gate-title">My Services requires Continuity Practice</div>
              <div class="settings-tier-gate-body">Offer supervision, consultation, training, and other peer services through Aegis. Enables the My Services sidebar and the Integrative Business Services badge on your profile.</div>
              <button type="button" class="btn btn-gold" @click="section = 'billing'"><AegisIcon name="star" :size="14" /> Upgrade to Continuity Practice</button>
            </div>
          </div>
          <div :class="{ 'settings-tier-blurred': isAccessTier }">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="briefcase" :size="16" /></div>
                <div><div class="card-title">My Services Settings</div><div class="card-subtitle">Integrative Business Services mode and service offering preferences</div></div>
              </div>
              <a :href="route('provider.services.index')" class="btn btn-outline btn-sm"><AegisIcon name="briefcase" :size="13" /> My Services</a>
            </div>
            <div class="card-body">
              <div class="section-label" id="settings-anchor-services-mode">Services Mode</div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Integrative Business Services Mode</div><div class="toggle-desc">Offer supervision, consultation, training, and other practitioner-to-practitioner services. Enables the <strong>My Services</strong> section in your sidebar and displays the <strong>Integrative Business Services</strong> badge on your profile.</div></div><button type="button" class="toggle" :class="{ on: servicesPrefs.mode }" @click="servicesPrefs.mode = !servicesPrefs.mode" :aria-pressed="servicesPrefs.mode"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Show Services on Public Profile</div><div class="toggle-desc">Display your service offerings on your public provider profile</div></div><button type="button" class="toggle" :class="{ on: servicesPrefs.showPublic }" @click="servicesPrefs.showPublic = !servicesPrefs.showPublic" :aria-pressed="servicesPrefs.showPublic"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Accept Booking Requests</div><div class="toggle-desc">Allow other providers and organizations to submit booking requests for your services</div></div><button type="button" class="toggle" :class="{ on: servicesPrefs.acceptBookings }" @click="servicesPrefs.acceptBookings = !servicesPrefs.acceptBookings" :aria-pressed="servicesPrefs.acceptBookings"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Show Pricing Publicly</div><div class="toggle-desc">Providers can see your rates before submitting a booking request</div></div><button type="button" class="toggle" :class="{ on: servicesPrefs.showPricing }" @click="servicesPrefs.showPricing = !servicesPrefs.showPricing" :aria-pressed="servicesPrefs.showPricing"></button></div>
              <div class="section-label">Visibility in Job Marketplace</div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Visible to Business Partners</div><div class="toggle-desc">Allow Business Partners to find and contact you about service contracts</div></div><button type="button" class="toggle" :class="{ on: servicesPrefs.bpDiscoverable }" @click="servicesPrefs.bpDiscoverable = !servicesPrefs.bpDiscoverable" :aria-pressed="servicesPrefs.bpDiscoverable"></button></div>
              <div class="section-label" id="settings-anchor-booking-preferences">Booking Preferences</div>
              <div class="form-row form-row-2" style="margin-top:6px">
                <div class="form-group"><label class="form-label">Booking Request Expiry</label><select class="form-select" v-model="servicesPrefs.bookingExpiry"><option value="24h">24 hours</option><option value="48h">48 hours</option><option value="72h">72 hours</option><option value="1week">1 week</option></select><div class="form-hint">Requests expire if not responded to within this window</div></div>
                <div class="form-group"><label class="form-label">Buffer Between Sessions</label><select class="form-select" v-model="servicesPrefs.sessionBuffer"><option value="none">None</option><option value="15min">15 min</option><option value="30min">30 min</option><option value="1hr">1 hour</option></select><div class="form-hint">Minimum gap between consecutive bookings</div></div>
              </div>
              <div class="section-label" id="settings-anchor-payment-rates">Payment &amp; Rates</div>
              <div class="form-group" style="max-width:200px">
                <label class="form-label">Default Hourly Rate</label>
                <div class="rate-input-wrap">
                  <span class="rate-currency">$</span>
                  <input class="form-input" type="number" v-model="servicesPrefs.hourlyRate" placeholder="0" min="0" />
                </div>
              </div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-primary btn-sm" :disabled="servicesSettingsSaving" @click="saveServicesSettings">
                  <AegisIcon name="check" :size="13" /> Save Services Settings
                </button>
              </div>
            </div>
          </div>
          </div><!-- end tier-blurred -->
        </div>

        <!-- PRIVACY & VISIBILITY -->
        <div v-show="section === 'privacy'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="shield" :size="16" /></div>
                <div><div class="card-title">Privacy &amp; Visibility</div><div class="card-subtitle">Controls your <strong>Practitioner Portal</strong> visibility.</div></div>
              </div>
            </div>
            <div class="card-body">
              <div style="margin-bottom:16px">
                <div class="form-label" style="margin-bottom:10px">Overall Profile Visibility Level</div>
                <div class="privacy-levels">
                  <div v-for="lvl in privacyLevels" :key="lvl.key" class="privacy-level-btn" :class="{ active: privacy.level === lvl.key }" @click="privacy.level = lvl.key">
                    <div class="privacy-level-icon" style="background:var(--surface-2);color:var(--text-2)"><AegisIcon :name="lvl.icon" :size="18" /></div>
                    <div class="privacy-level-name">{{ lvl.name }}</div>
                    <div class="privacy-level-desc">{{ lvl.desc }}</div>
                  </div>
                </div>
              </div>
              <div v-for="row in privacyToggles" :key="row.key" class="toggle-row"><div class="toggle-info"><div class="toggle-label">{{ row.label }}</div><div class="toggle-desc">{{ row.desc }}</div></div><button type="button" class="toggle" :class="{ on: privacy[row.key] }" @click="privacy[row.key] = !privacy[row.key]" :aria-pressed="privacy[row.key]"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-primary btn-sm" :disabled="privacySaving" @click="savePrivacySettings">
                  <AegisIcon name="check" :size="13" /> Save Privacy Settings
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- NETWORK SETTINGS -->
        <div v-show="section === 'network'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="users" :size="16" /></div>
                <div><div class="card-title">Network &amp; Connection Settings</div><div class="card-subtitle">Manage how you connect with other providers</div></div>
              </div>
              <a :href="route('provider.network.index')" class="btn btn-outline btn-sm"><AegisIcon name="users" :size="13" /> Open Network</a>
            </div>
            <div class="card-body">
              <div class="section-label">Connection Preferences</div>
              <div v-for="row in networkConnection" :key="row.key" class="toggle-row"><div class="toggle-info"><div class="toggle-label">{{ row.label }}</div><div class="toggle-desc">{{ row.desc }}</div></div><button type="button" class="toggle" :class="{ on: networkPrefs[row.key] }" @click="networkPrefs[row.key] = !networkPrefs[row.key]" :aria-pressed="networkPrefs[row.key]"></button></div>

              <div class="form-group" style="margin-top:20px"><label class="form-label">Geographic Focus for Suggestions</label><select class="form-select" v-model="networkPrefs.geoFocus"><option value="25mi">25 miles</option><option value="50mi">50 miles</option><option value="100mi">100 miles</option><option value="statewide">Statewide</option><option value="national">National</option></select><div class="form-hint" style="margin-top:4px">Aegis will prioritize providers in this range when suggesting connections</div></div>
              <div class="section-label" style="margin-top:20px">Network &amp; Updates</div>
              <div v-for="row in networkNotifs" :key="row.key" class="toggle-row"><div class="toggle-info"><div class="toggle-label">{{ row.label }}</div><div class="toggle-desc">{{ row.desc }}</div></div><button type="button" class="toggle" :class="{ on: networkPrefs[row.key] }" @click="networkPrefs[row.key] = !networkPrefs[row.key]" :aria-pressed="networkPrefs[row.key]"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-primary btn-sm" :disabled="networkSettingsSaving" @click="saveNetworkSettings">
                  <AegisIcon name="check" :size="14" /> Save
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- APPEARANCE & TIMEZONE -->
        <div v-show="section === 'appearance'" class="settings-panel">
          <SettingsAppearance update-route="provider.settings.appearance" :meta="meta" />
        </div>



        <!-- SUBSCRIPTION & PLAN (billing) - KEPT AS-IS, wired to Stripe -->
        <div v-show="section === 'billing'" class="settings-panel">
          <div class="st-card">
            <div class="st-card-head">
              <div class="st-card-head-l">
                <span class="st-card-ico"><AegisIcon name="star" :size="17" /></span>
                <div><div class="st-card-title">Subscription &amp; Plan</div><div class="st-card-sub">Your current Aegis plan and available upgrades</div></div>
              </div>
              <span class="st-bp-badge">{{ currentTierLabel }}</span>
            </div>
            <div class="st-card-body">

              <!-- ── Quick actions — link out to Finances ─────────────────── -->
              <div class="st-billing-shortcuts">
                <a :href="route('provider.finances.index') + '?tab=methods'" class="st-shortcut-btn">
                  <span class="st-shortcut-icon"><AegisIcon name="credit-card" :size="16" /></span>
                  <div>
                    <div class="st-shortcut-label">Payment Methods</div>
                    <div class="st-shortcut-sub">Add or change cards</div>
                  </div>
                  <AegisIcon name="chevron-right" :size="13" class="st-shortcut-arrow" />
                </a>
                <a :href="route('provider.finances.index') + '?tab=subscription'" class="st-shortcut-btn">
                  <span class="st-shortcut-icon"><AegisIcon name="file-text" :size="16" /></span>
                  <div>
                    <div class="st-shortcut-label">Invoice History</div>
                    <div class="st-shortcut-sub">View & download receipts</div>
                  </div>
                  <AegisIcon name="chevron-right" :size="13" class="st-shortcut-arrow" />
                </a>
                <a :href="route('provider.finances.index') + '?tab=integrations'" class="st-shortcut-btn">
                  <span class="st-shortcut-icon"><AegisIcon name="link" :size="16" /></span>
                  <div>
                    <div class="st-shortcut-label">Stripe Connect</div>
                    <div class="st-shortcut-sub">Receive service payments</div>
                  </div>
                  <AegisIcon name="chevron-right" :size="13" class="st-shortcut-arrow" />
                </a>
                <a :href="route('provider.finances.index') + '?tab=history'" class="st-shortcut-btn">
                  <span class="st-shortcut-icon"><AegisIcon name="clock" :size="16" /></span>
                  <div>
                    <div class="st-shortcut-label">Transactions</div>
                    <div class="st-shortcut-sub">Full payment history</div>
                  </div>
                  <AegisIcon name="chevron-right" :size="13" class="st-shortcut-arrow" />
                </a>
              </div>

              <!-- Founding Member perk banner — first 100 practitioners -->
              <div v-if="user?.is_founding_member && subStatus !== 'none'" class="st-founding-banner">
                <AegisIcon name="star" :size="16" />
                <span><strong>Founding Member perks active</strong> — 2 additional Continuity Stewards free for life · 1 marketing ad/yr free (first 100 providers)</span>
              </div>

              <!-- Current plan band — dynamic from backend -->
              <div class="st-current-band">
                <div>
                  <div class="st-current-name">{{ currentPlanLine }}</div>
                  <div class="st-current-meta">{{ billingMetaLine }}</div>
                </div>
                <template v-if="subStatus === 'none'">
                  <button type="button" class="btn btn-gold" @click="goToPricing">Choose a Plan</button>
                </template>
                <template v-else-if="subOnGracePeriod">
                  <button type="button" class="btn btn-gold" @click="resumePlan" :disabled="planBusy"><AegisIcon name="refresh" :size="13" /> Reactivate</button>
                </template>
                <template v-else>
                  <button type="button" class="btn btn-outline" @click="confirmCancel = true" :disabled="planBusy">Cancel Plan</button>
                </template>
              </div>

              <!-- Grace period banner -->
              <div v-if="subOnGracePeriod" class="st-perks-band" style="border-color:var(--red-light);background:var(--icon-bg-red,var(--surface-2));">
                <AegisIcon name="info" :size="14" />
                <span><strong>Subscription ending</strong> — your plan will end on {{ formatDate(sub.ends_at) }}. Reactivate any time before then to keep your access.</span>
              </div>

              <!-- Past-due banner -->
              <div v-else-if="subStatus === 'past_due'" class="st-perks-band" style="border-color:var(--red-light);background:var(--icon-bg-red,var(--surface-2));">
                <AegisIcon name="info" :size="14" />
                <span><strong>Payment failed</strong> — please update your payment method to avoid interruption.</span>
              </div>

              <!-- Monthly/Annual toggle -->
              <div class="st-cycle-toggle" v-if="subStatus !== 'none'">
                <span :class="{ active: !billingAnnualView }">Monthly</span>
                <button type="button" class="toggle" :class="{ on: billingAnnualView }" @click="billingAnnualView = !billingAnnualView"></button>
                <span :class="{ active: billingAnnualView }">Annual <span class="st-save-pill">Save 20%</span></span>
              </div>

              <!-- Plan grid -->
              <div class="st-plan-grid">
                <!-- Access -->
                <div class="st-plan-tier" :class="{ current: currentTier === 'access' }">
                  <span v-if="currentTier === 'access'" class="st-plan-tier-badge">
                    <AegisIcon name="check" :size="11" /> Current Plan
                  </span>
                  <div class="st-plan-tier-name">Continuity Access</div>
                  <div class="st-plan-tier-price">${{ billingAnnualView ? 23 : 29 }}<span>/mo</span></div>
                  <div class="st-plan-tier-alt">{{ billingAnnualView ? 'billed $276/yr' : 'or $276/yr (save 20%)' }}</div>
                  <div class="st-plan-feats">
                    <span v-for="f in accessFeatures" :key="f"><AegisIcon name="check" :size="13" /> {{ f }}</span>
                  </div>
                  <button v-if="currentTier === 'access' && !isSwapAllowed('access')" type="button" class="btn btn-outline st-plan-cta" disabled>
                    Your current plan
                  </button>
                  <button v-else type="button" class="btn btn-outline st-plan-cta" @click="swapPlan('access')" :disabled="planBusy || !accessPriceId">
                    {{ currentTier === 'practice' ? 'Downgrade to Access' : swapButtonLabel('access') }}
                  </button>
                </div>

                <!-- Practice -->
                <div class="st-plan-tier" :class="{ current: currentTier === 'practice' }">
                  <span v-if="currentTier === 'practice'" class="st-plan-tier-badge">
                    <AegisIcon name="check" :size="11" /> Current Plan
                  </span>
                  <div class="st-plan-tier-name">Continuity Practice</div>
                  <div class="st-plan-tier-price">${{ billingAnnualView ? 39 : 49 }}<span>/mo</span></div>
                  <div class="st-plan-tier-alt">{{ billingAnnualView ? 'billed $468/yr' : 'or $468/yr (save 20%)' }}</div>
                  <div class="st-plan-feats">
                    <span v-for="f in practiceFeatures" :key="f"><AegisIcon name="check" :size="13" /> {{ f }}</span>
                  </div>
                  <button v-if="currentTier === 'practice' && !isSwapAllowed('practice')" type="button" class="btn btn-outline st-plan-cta" disabled>
                    Your current plan
                  </button>
                  <button v-else type="button" class="btn btn-gold st-plan-cta" @click="swapPlan('practice')" :disabled="planBusy || !practicePriceId">
                    {{ currentTier === 'access' ? 'Upgrade to Practice' : swapButtonLabel('practice') }}
                  </button>
                </div>
              </div>

              <!-- Add-ons — MAAT (requires Practice) -->
              <div class="st-included-head" style="margin-top:24px">Add-ons</div>
              <div class="st-note" style="margin-top:-4px;margin-bottom:14px">Stack on top of your base plan. Add or remove at any time.</div>
              <div class="st-addon-card">
                <span class="st-card-ico"><AegisIcon name="shield" :size="17" /></span>
                <div class="st-addon-body">
                  <div class="st-addon-head">
                    <div class="st-addon-name">MAAT Professional CS <span class="st-addon-tag">MAAT Add-On</span></div>
                    <div class="st-addon-price">
                      +<strong>${{ maatBillingAnnual ? 23 : 29 }}</strong>/mo
                      <div class="st-addon-billed">{{ maatBillingAnnual ? 'billed $276/yr' : 'or $276/yr (save 20%)' }}</div>
                    </div>
                  </div>
                  <div class="st-addon-desc">A licensed, insured professional Continuity Steward — certified by MAAT — designated to your practice. Emergency response within 4 hours, annual recertification included.</div>
                  <div class="st-addon-feats">
                    <span v-for="f in maatFeatures" :key="f"><AegisIcon name="check" :size="12" /> {{ f }}</span>
                  </div>
                  <!-- MAAT locked alert — shown when on Access tier -->
                  <div v-if="currentTier !== 'practice'" class="alert alert-warning" style="margin-bottom:12px;margin-top:4px;">
                    <div class="alert-icon"><AegisIcon name="lock" :size="16" /></div>
                    <div class="alert-content">
                      <div class="alert-title">Requires Continuity Practice</div>
                      <div>The MAAT Professional CS add-on is only available on the Continuity Practice plan. Upgrade your plan above to unlock this add-on.</div>
                    </div>
                  </div>
                  <div class="st-addon-foot">
                    <button v-if="hasMaat" type="button" class="btn btn-outline" @click="toggleMaat(false)" :disabled="maatBusy">Remove MAAT</button>
                    <button
                      v-else
                      type="button"
                      class="btn btn-gold"
                      @click="toggleMaat(true)"
                      :disabled="maatBusy || currentTier !== 'practice'"
                      :data-tooltip="currentTier !== 'practice' ? 'Upgrade to Continuity Practice to add MAAT' : null"
                    >
                      <AegisIcon name="shield" :size="13" /> Add MAAT Service
                    </button>
                    <span v-if="currentTier === 'practice'" class="st-addon-req">Available with your plan</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ── PLAN SWAP CONFIRMATION ──────────────────────────────────── -->
          <AegisModal v-model="confirmSwap" title="Confirm Plan Change" size="md">
            <div style="margin-bottom:16px">
              <!-- Direction badge -->
              <div style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;padding:4px 12px;border-radius:var(--radius-full);margin-bottom:16px;"
                :style="pendingSwap.direction === 'upgrade' ? 'background:var(--green-light);color:var(--green-dark)' : pendingSwap.direction === 'downgrade' ? 'background:var(--orange-light);color:var(--orange-dark,#b45309)' : 'background:var(--surface-2);color:var(--text-2)'">
                <AegisIcon :name="pendingSwap.direction === 'upgrade' ? 'trending-up' : pendingSwap.direction === 'downgrade' ? 'trending-up' : 'refresh'" :size="12" />
                {{ pendingSwap.direction === 'upgrade' ? 'Upgrade' : pendingSwap.direction === 'downgrade' ? 'Downgrade' : 'Billing Change' }}
              </div>

              <!-- Current → New -->
              <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                <div style="flex:1;padding:12px 14px;border:1px solid var(--border);border-radius:var(--radius);background:var(--surface-2)">
                  <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-3);margin-bottom:4px">Current</div>
                  <div style="font-family:var(--font-serif);font-size:15px;font-weight:700;color:var(--text)">{{ currentTierLabel }}</div>
                  <div style="font-size:12px;color:var(--text-3);margin-top:2px">{{ currentBillingIsAnnual ? 'Annual billing' : 'Monthly billing' }}</div>
                </div>
                <AegisIcon name="arrow-right" :size="18" style="color:var(--text-3);flex-shrink:0" />
                <div style="flex:1;padding:12px 14px;border:1px solid var(--gold-dark);border-radius:var(--radius);background:var(--icon-bg-gold)">
                  <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-3);margin-bottom:4px">New Plan</div>
                  <div style="font-family:var(--font-serif);font-size:15px;font-weight:700;color:var(--text)">{{ pendingSwap.label }}</div>
                  <div style="font-size:12px;color:var(--gold-dark);margin-top:2px;font-weight:600">{{ pendingSwap.priceLine }}</div>
                </div>
              </div>

              <!-- Proration / timing note -->
              <div style="padding:12px 14px;border-radius:var(--radius);background:var(--surface-2);border:1px solid var(--border);font-size:13px;color:var(--text-2);line-height:1.6;display:flex;align-items:center;gap:8px;">
                <span style="flex-shrink:0;color:var(--gold-dark);display:flex;"><AegisIcon name="info" :size="14" /></span>
                <span>{{ pendingSwap.note }}</span>
              </div>
            </div>
            <template #footer>
              <button type="button" class="btn btn-outline" @click="confirmSwap = false">Go Back</button>
              <button type="button" :class="pendingSwap.direction === 'downgrade' ? 'btn btn-outline' : 'btn btn-gold'" @click="doSwapPlan" :disabled="planBusy">
                <AegisIcon :name="pendingSwap.direction === 'downgrade' ? 'trending-down' : 'check'" :size="13" />
                {{ pendingSwap.direction === 'downgrade' ? 'Confirm Downgrade' : 'Confirm Change' }}
              </button>
            </template>
          </AegisModal>

          <!-- ── REACTIVATE CONFIRMATION ────────────────────────────────── -->
          <AegisModal v-model="confirmResume" title="Reactivate Subscription" size="sm">
            <p style="font-size:14px;color:var(--text-2);margin-bottom:14px">
              Your <strong>{{ currentTierLabel }}</strong> subscription will continue as normal. You will be charged on your next billing date.
            </p>
            <div style="padding:10px 14px;border-radius:var(--radius);background:var(--green-light);border:1px solid var(--green-dark);font-size:13px;color:var(--green-dark);margin-bottom:4px">
              <AegisIcon name="check" :size="13" style="margin-right:4px" /> Your access will be fully restored immediately.
            </div>
            <template #footer>
              <button type="button" class="btn btn-outline" @click="confirmResume = false">Cancel</button>
              <button type="button" class="btn btn-gold" @click="doResumePlan" :disabled="planBusy">
                <AegisIcon name="refresh" :size="13" /> Reactivate
              </button>
            </template>
          </AegisModal>

          <!-- ── MAAT ADD CONFIRMATION ─────────────────────────────────── -->
          <AegisModal v-model="confirmMaat" :title="pendingMaat.enable ? 'Add MAAT Professional CS Service' : 'Remove MAAT Service'" size="md">
            <template v-if="pendingMaat.enable">
              <div style="margin-bottom:14px;padding:14px;border-radius:var(--radius);background:var(--icon-bg-gold);border:1px solid var(--badge-border-gold)">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                  <AegisIcon name="shield" :size="20" style="color:var(--gold-dark);flex-shrink:0" />
                  <div>
                    <div style="font-family:var(--font-serif);font-size:15px;font-weight:700;color:var(--text)">MAAT Professional CS Service</div>
                    <div style="font-size:12px;color:var(--text-3)">+${{ maatBillingAnnual ? 23 : 29 }}/mo added to your subscription</div>
                  </div>
                </div>
                <ul style="margin:0 0 0 14px;font-size:13px;color:var(--text-2);line-height:1.8">
                  <li v-for="f in maatFeatures" :key="f">{{ f }}</li>
                </ul>
              </div>
              <p style="font-size:13px;color:var(--text-2);line-height:1.6;margin-bottom:0">
                <AegisIcon name="info" :size="13" style="color:var(--gold-dark);vertical-align:middle;margin-right:4px" />
                The MAAT add-on will be billed on the same cycle as your base plan. A prorated charge will be applied today.
              </p>
            </template>
            <template v-else>
              <p style="font-size:14px;color:var(--text-2);margin-bottom:14px">You are about to remove the <strong>MAAT Professional CS Service</strong> add-on.</p>
              <div style="padding:12px 14px;border-radius:var(--radius);background:var(--orange-light);border:1px solid var(--border-dark);font-size:13px;color:var(--text-2);line-height:1.6">
                <AegisIcon name="alert-triangle" :size="13" style="color:var(--orange);vertical-align:middle;margin-right:4px" />
                The MAAT Continuity Steward assignment will be removed. Your MAAT CS will be notified. The remove takes effect immediately.
              </div>
            </template>
            <template #footer>
              <button type="button" class="btn btn-outline" @click="confirmMaat = false">Go Back</button>
              <button v-if="pendingMaat.enable" type="button" class="btn btn-gold" @click="doToggleMaat" :disabled="maatBusy">
                <AegisIcon name="shield" :size="13" /> Add MAAT Service
              </button>
              <button v-else type="button" class="btn btn-danger" @click="doToggleMaat" :disabled="maatBusy">
                Remove MAAT
              </button>
            </template>
          </AegisModal>

          <!-- Cancel confirmation modal -->
          <AegisModal v-model="confirmCancel" title="Cancel your subscription?" size="sm">
            <p style="font-size:14px;color:var(--text);margin-bottom:12px">Your subscription will remain active until <strong>{{ formatDate(sub.current_period?.end) || 'the end of the current period' }}</strong>. After that, you'll lose access to your portal.</p>
            <p style="font-size:13px;color:var(--text-3);margin-bottom:0">You can reactivate any time before then.</p>
            <template #footer>
              <button type="button" class="btn btn-outline" @click="confirmCancel = false">Keep Subscription</button>
              <button type="button" class="btn btn-danger" @click="cancelPlan" :disabled="planBusy">Cancel Subscription</button>
            </template>
          </AegisModal>

          <!-- Change Plan / Upgrade modal — opened from sidebar Upgrade ↗ link -->
          <AegisModal v-model="modals.showUpgrade" title="Change Plan" size="sm">
            <p style="font-size:14px;color:var(--text-2);margin-bottom:14px">Upgrades take effect immediately. Downgrades to a reduced service package take effect at the start of the next billing cycle.</p>
            <div style="background:var(--surface-2);border-radius:var(--radius);padding:14px;font-size:13px;color:var(--text-2);">
              To learn more about available service packages or discuss custom support needs, contact
              <a href="mailto:support@aegis.com" style="color:var(--gold-dark);">support@aegis.com</a>.
            </div>
            <template #footer>
              <button type="button" class="btn btn-outline" @click="modals.showUpgrade = false">Cancel</button>
              <button type="button" class="btn btn-primary" @click="modals.showUpgrade = false; section = 'billing'">
                View Plans
              </button>
            </template>
          </AegisModal>
        </div>


        <!-- BILLING & INVOICES (invoices) - KEPT AS-IS, wired to Stripe -->
        <!-- ACCOUNT ACTIONS (danger) -->
        <div v-show="section === 'changes'" class="settings-panel">
          <SettingsDangerZone
            delete-route="provider.settings.account.delete"
            pause-route="provider.settings.account.pause"
            resume-route="provider.settings.account.resume"
            export-route="provider.settings.account.export"
            :initial-paused="isAccountPaused"
          />
        </div>

      </div><!-- end .settings-content -->
    </div><!-- end .settings-layout -->

    <!-- MODALS -->












  <!-- Tier upgrade modal for gated sections -->
  <SettingsTierUpgradeModal
    v-model:show="showTierModal"
    :locked-feature-note="tierModalFeature"
    @upgrade="section = 'billing'"
  />

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useVuelidate } from '@vuelidate/core';
import { required, email, minLength, sameAs, helpers } from '@vuelidate/validators';
import { useToast } from '@/composables/useToast';
import AppLayout           from '@/layouts/AppLayout.vue';
import SettingsAccount      from '@/components/settings/SettingsAccount.vue';
import SettingsSecurity     from '@/components/settings/SettingsSecurity.vue';
import SettingsNotifications from '@/components/settings/SettingsNotifications.vue';
import SettingsAppearance   from '@/components/settings/SettingsAppearance.vue';
import SettingsDangerZone   from '@/components/settings/SettingsDangerZone.vue';

const props = defineProps({
  user:             { type: Object,  default: () => ({}) },
  meta:             { type: Object,  default: () => ({}) },
  mfaEnabled:       { type: Boolean, default: false },
  mfaMethod:        { type: String,  default: '' },
  sessions:         { type: Array,   default: () => [] },
  subscription:     { type: Object,  default: () => ({}) },
  pricing:          { type: Object,  default: () => ({}) },
  activeAgreements: { type: Array,   default: () => [] },
});

const toast = useToast();

// ─── Nav ───────────────────────────────────────────────────────────────────────
const i = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
const nav = [
  { group: 'Account', items: [
    { key: 'profile',      label: 'Profile & Identity',          icon: 'user' },
    { key: 'account',      label: 'Account & Login',             icon: 'lock' },
    { key: 'security',     label: 'Security & 2FA',              icon: 'shield' },
  ]},
  { group: 'Communications', items: [
    { key: 'notifications', label: 'Notifications', badge: 2,   icon: 'bell' },


  ]},
  { group: 'Schedule', items: [
    { key: 'availability', label: 'Availability & Hours',  icon: 'calendar' },
  ]},
  { group: 'Operations', items: [
    { key: 'csettings',    label: 'Continuity Steward Settings', icon: 'shield' },
    { key: 'ssettings',    label: 'Support Steward Settings',    icon: 'users' },
    { key: 'vault',        label: 'Document Vault Access',       icon: 'lock' },
    { key: 'agreements',   label: 'Agreements & Contracts',      icon: 'file-text' },
  ]},
  { group: 'Platform', items: [
    { key: 'myservices',    label: 'My Services Settings',       icon: 'grid',  lockedForAccess: true },
    { key: 'privacy',       label: 'Privacy & Visibility',       icon: 'shield' },
    { key: 'network',       label: 'Network Settings',           icon: 'network' },
    { key: 'appearance',    label: 'Appearance',                 icon: 'settings' },

  ]},
  { group: 'Plan', items: [
    { key: 'billing',  label: 'Plan & Billing', icon: 'star' },
  ]},

  { group: 'Account Closure & Data Management', items: [
    { key: 'changes', label: 'Account Actions', danger: true, icon: 'alert-triangle' },
  ]},
];
const section = ref('profile');

// ─── Profile ──────────────────────────────────────────────────────────────────
const _inertiaPage    = usePage();
const isAccountPaused = computed(() => _inertiaPage.props.auth?.user?.is_paused ?? false);
const currentPortal   = computed(() => _inertiaPage.props.auth?.portal ?? 'provider');
const displayName = computed(() => props.user?.display_name || 'Dr. Sarah Johnson');
const initials    = computed(() => displayName.value.replace(/^Dr\.?\s+/i, '').split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase());
const tierLabel   = computed(() => props.user?.tier === 'practice' ? 'Continuity Practice' : 'Continuity Access');
const isAccessTier = computed(() => (props.user?.tier ?? (sub.value.tier)) === 'access');
const showTierModal  = ref(false);
const tierModalFeature = ref('');

function openTierModal(featureName) {
  tierModalFeature.value = featureName;
  showTierModal.value = true;
}

const settingsContent = ref(null);

function navClick(item) {
  if (item.locked && isAccessTier.value) {
    openTierModal(item.label);
    return;
  }
  section.value = item.key;
  nextTick(() => {
    const el = settingsContent.value;
    if (!el) return;
    const top = el.getBoundingClientRect().top + window.scrollY - 80; // 64px topbar + 16px breathing room
    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
  });
}

// ─── Account form ─────────────────────────────────────────────────────────────
const showPw = ref(false);
const acct   = reactive({ email: props.user?.email || '', recovery: '', phone: props.user?.phone || '', handle: '@dr.sarah.johnson', current: '', newpw: '', confirmpw: '' });
const rules  = computed(() => ({
  email:     { required: helpers.withMessage('Email is required.', required), email: helpers.withMessage('Enter a valid email.', email) },
  newpw:     { minLength: helpers.withMessage('Minimum 12 characters.', minLength(12)) },
  confirmpw: { sameAs: helpers.withMessage('Passwords must match.', sameAs(computed(() => acct.newpw))) },
}));
const v$ = useVuelidate(rules, acct);
function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message;
  return null;
}
async function savePassword() {
  const valid = await v$.value.$validate();
  if (!valid) { toast.error('Please fix the highlighted fields.'); return; }
  toast.success('Password updated.');
  v$.value.$reset();
}

// ─── Sessions — read from props (passed by SettingsController::index()) ─────
const sessions = computed(() => props.sessions ?? []);

// ─── Security ─────────────────────────────────────────────────────────────────
const tfaCode    = ref('');
const tfaOptions = reactive([
  { key: 'authenticator', name: 'Authenticator App',  icon: 'phone',   desc: 'Google Authenticator, Authy, or any TOTP app — most secure', active: true  },

  { key: 'email',         name: 'Email Code',         icon: 'mail',    desc: `A code sent to your primary email`, active: false },
]);
function selectTfa(tfa) { tfaOptions.forEach(t => t.active = t.key === tfa.key); }
const backupCodes = [
  { value: 'KL-4J8M-92WX', used: false }, { value: 'KL-7P2N-56TQ', used: false },
  { value: 'KL-3R9K-81ZV', used: false }, { value: 'KL-6C4H-27YB', used: false },
  { value: 'KL-1M5F-49LD', used: true  }, { value: 'KL-8A3E-65RP', used: false },
];
const _securityMeta = props.meta?.notify_security ?? {};
const securityAlerts = reactive([
  { name: 'Alert on New Login',                  sub: 'Receive email when a new device logs into your account', key: 'alertOnNewLogin', on: _securityMeta.alertOnNewLogin ?? true },
  { name: 'Session Timeout (30 min inactivity)', sub: 'Automatically log out after 30 minutes of inactivity',   key: 'sessionTimeout',  on: _securityMeta.sessionTimeout  ?? true },
]);

// ─── Notifications ─────────────────────────────────────────────────────────────
const notifPrefs = reactive({ quietFrom: '22:00', quietTo: '08:00', digest: 'daily', reminderLead: '1day' });
const notifCategories = reactive([
  { key: 'critical',  label: 'Critical Incident Alerts', desc: 'Emergency activations, CS/SS verifications', push: true,  email: true, inapp: true  },
  { key: 'referrals', label: 'Referral Updates',         desc: 'New, accepted, and declined referrals',      push: true,  email: true, inapp: true  },
  { key: 'messages',  label: 'Direct Messages',          desc: 'New messages from providers and stewards',   push: true,  email: true, inapp: true  },
  { key: 'payments',  label: 'Payments & Invoices',      desc: 'Subscription renewals and payment activity', push: false, email: true, inapp: true  },
  { key: 'plan',      label: 'Continuity Plan Updates',  desc: 'Plan reviews, attestations, and amendments', push: true,  email: true, inapp: true  },
  { key: 'vault',     label: 'Vault Activity',           desc: 'Document uploads, access, and unlocks',      push: false, email: true, inapp: true  },
  { key: 'network',   label: 'Network & Connections',    desc: 'New connections and BP activity',            push: false, email: false, inapp: true  },
]);
function setAllNotifs(val) { notifCategories.forEach(c => { c.push = val; c.email = val; c.inapp = val; }); }

// Per-section save functions wired to dedicated routes
const securityAlertSaving  = ref(false);
const csSettingsSaving     = ref(false);
const ssSettingsSaving     = ref(false);
const vaultSettingsSaving  = ref(false);
const agreementSettingsSaving = ref(false);
const networkSettingsSaving   = ref(false);

function saveSecurityAlerts() {
  securityAlertSaving.value = true;
  const payload = {};
  securityAlerts.forEach(a => { payload[a.key] = a.on; });
  router.put(route('provider.settings.security-alerts'), payload, {
    preserveScroll: true,
    onSuccess: () => toast.success('Security alerts saved.'),
    onError:   () => toast.error('Could not save.'),
    onFinish:  () => { securityAlertSaving.value = false; },
  });
}
function saveCsSettings() {
  csSettingsSaving.value = true;
  router.put(route('provider.settings.cs-settings'), {
    activation:     csPrefs.activation,
    annualReminder: csPrefs.annualReminder,
    notifyOnChange: csPrefs.notifyOnChange,
  }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Continuity Steward settings saved.'),
    onError:   () => toast.error('Could not save.'),
    onFinish:  () => { csSettingsSaving.value = false; },
  });
}
function saveSsSettings() {
  ssSettingsSaving.value = true;
  router.put(route('provider.settings.ss-settings'), {
    notifyIncident: ssPrefs.notifyIncident,
    notifyChange:   ssPrefs.notifyChange,
    annualAttest:   ssPrefs.annualAttest,
  }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Support Steward settings saved.'),
    onError:   () => toast.error('Could not save.'),
    onFinish:  () => { ssSettingsSaving.value = false; },
  });
}
function saveVaultSettings() {
  vaultSettingsSaving.value = true;
  router.put(route('provider.settings.vault-alerts'), {
    notifyAccess: vaultPrefs.notifyAccess,
    notifyUnlock: vaultPrefs.notifyUnlock,
  }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Vault alert settings saved.'),
    onError:   () => toast.error('Could not save.'),
    onFinish:  () => { vaultSettingsSaving.value = false; },
  });
}
function saveAgreementSettings() {
  agreementSettingsSaving.value = true;
  router.put(route('provider.settings.agreement-alerts'), {
    expiryReminder:    agreementPrefs.expiryReminder,
    notifyCountersign: agreementPrefs.notifyCountersign,
  }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Agreement alert settings saved.'),
    onError:   () => toast.error('Could not save.'),
    onFinish:  () => { agreementSettingsSaving.value = false; },
  });
}
function saveNetworkSettings() {
  networkSettingsSaving.value = true;
  router.put(route('provider.settings.network-settings'), {
    requireApproval:  networkPrefs.requireApproval,
    dataUse:          networkPrefs.dataUse,
    hideFromBP:       networkPrefs.hideFromBP,
    connectionAlerts: networkPrefs.connectionAlerts,
    weeklyDigest:     networkPrefs.weeklyDigest,
    featureUpdates:   networkPrefs.featureUpdates,
    geoFocus:         networkPrefs.geoFocus,
  }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Network settings saved.'),
    onError:   () => toast.error('Could not save.'),
    onFinish:  () => { networkSettingsSaving.value = false; },
  });
}

// ─── Messaging ─────────────────────────────────────────────────────────────────
const messaging = reactive({ who: 'connected', status: 'available', readReceipts: true, onlineStatus: true, awayText: '' });

// ─── Email prefs ────────────────────────────────────────────────────────────────
const emailPrefs = reactive({ digestFreq: 'weekly', digest: true, referralSummary: true, productUpdates: false, unsubAll: false });
const emailToggles = [
  { key: 'digest',          label: 'Weekly Platform Digest',              desc: 'Summary of network activity, referrals, and Aegis updates' },
  { key: 'referralSummary', label: 'Referral Activity Summary',           desc: 'Digest of your referral in/outbox — new, accepted, and closed' },
  { key: 'productUpdates',  label: 'Product Updates',                     desc: 'New Aegis features, improvements, and release notes' },
  { key: 'unsubAll',        label: 'Unsubscribe from All Optional Emails', desc: 'Only transactional and security emails will be sent' },
];

// ─── Availability ───────────────────────────────────────────────────────────────
// ─── Availability & Hours ────────────────────────────────────────────────────────
// Hydrate from meta.availability (saved as { mon: { on, from, to }, ... })
const _savedHours  = props.meta?.availability ?? {};
const _savedAppearance = props.meta?.appearance ?? {};
const availTimezone = ref(_savedAppearance.timezone ?? 'America/New_York');
const _dayDefaults = {
  mon: { on: true,  from: '09:00', to: '17:00' },
  tue: { on: true,  from: '09:00', to: '17:00' },
  wed: { on: true,  from: '09:00', to: '17:00' },
  thu: { on: true,  from: '09:00', to: '17:00' },
  fri: { on: true,  from: '09:00', to: '15:00' },
  sat: { on: false, from: '10:00', to: '13:00' },
  sun: { on: false, from: '10:00', to: '13:00' },
};
const weekDays = reactive([
  { key: 'mon', label: 'Monday',    ...(_dayDefaults.mon), ...(_savedHours.mon ?? {}) },
  { key: 'tue', label: 'Tuesday',   ...(_dayDefaults.tue), ...(_savedHours.tue ?? {}) },
  { key: 'wed', label: 'Wednesday', ...(_dayDefaults.wed), ...(_savedHours.wed ?? {}) },
  { key: 'thu', label: 'Thursday',  ...(_dayDefaults.thu), ...(_savedHours.thu ?? {}) },
  { key: 'fri', label: 'Friday',    ...(_dayDefaults.fri), ...(_savedHours.fri ?? {}) },
  { key: 'sat', label: 'Saturday',  ...(_dayDefaults.sat), ...(_savedHours.sat ?? {}) },
  { key: 'sun', label: 'Sunday',    ...(_dayDefaults.sun), ...(_savedHours.sun ?? {}) },
]);

const availSaving = ref(false);
function saveAvailability() {
  availSaving.value = true;
  // Build hours map keyed by day key
  const hours = Object.fromEntries(
    weekDays.map(d => [d.key, { on: d.on, from: d.from, to: d.to }])
  );
  // Also save timezone alongside availability
  router.put(route('provider.profile.availability'), { hours, timezone: availTimezone.value }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Availability saved.'); },
    onError:   () => { toast.error('Could not save availability.'); },
    onFinish:  () => { availSaving.value = false; },
  });
}

// ─── Time options for availability hour selects ─────────────────────────────────
const timeOptions = (() => {
  const opts = [];
  for (let h = 0; h < 24; h++) {
    for (const m of [0, 30]) {
      const hh = String(h).padStart(2, '0');
      const mm = String(m).padStart(2, '0');
      const val = `${hh}:${mm}`;
      const period = h < 12 ? 'AM' : 'PM';
      const h12 = h === 0 ? 12 : h > 12 ? h - 12 : h;
      opts.push({ value: val, label: `${h12}:${mm} ${period}` });
    }
  }
  return opts;
})();

// ─── Referral prefs ─────────────────────────────────────────────────────────────
const referralPrefs    = reactive({ accepting: true, autoAccept: false, suggestAlts: true, autoArchive: true });
const referralIncoming = [
  { key: 'accepting', label: 'Currently Accepting Referrals', desc: 'When off, your profile shows "Not Accepting Referrals"' },
];


// ─── CS / SS / Vault / Agreement prefs ──────────────────────────────────────────
const _csMeta = props.meta?.notify_cs ?? {};
const csPrefs = reactive({
  activation:     _csMeta.activation     ?? true,
  annualReminder: _csMeta.annualReminder ?? true,
  notifyOnChange: _csMeta.notifyOnChange ?? true,
});
const csToggles = [
  { key: 'activation', label: 'Emergency CS Activation', desc: 'Allow your CS to activate your Continuity Plan with Aegis admin verification during a critical incident' },
];
const csNotifToggles = [
  { key: 'annualReminder', label: 'Annual CS Check-In Reminder', desc: 'Remind me to verify CS availability and re-attest my Continuity Plan every 12 months', model: 'csPrefs' },
  { key: 'notifyOnChange', label: 'Notify CS on Plan Changes',   desc: 'Automatically notify your Continuity Steward when you update or amend your Continuity Plan', model: 'csPrefs' },
];
const _ssMeta = props.meta?.notify_ss ?? {};
const ssPrefs = reactive({
  notifyIncident: _ssMeta.notifyIncident ?? true,
  notifyChange:   _ssMeta.notifyChange   ?? true,
  annualAttest:   _ssMeta.annualAttest   ?? true,
});
const ssNotifToggles = [
  { key: 'notifyIncident', label: 'Notify SS on Critical Incident', desc: 'Your Support Steward is automatically notified when a critical incident is declared', model: 'ssPrefs' },
  { key: 'notifyChange',   label: 'Notify SS on Plan Changes',      desc: 'Automatically notify your Support Steward when you update or amend your Continuity Plan', model: 'ssPrefs' },
  { key: 'annualAttest',   label: 'SS Annual Attestation Reminder', desc: 'Remind me to verify SS contact info and re-confirm permissions annually', model: 'ssPrefs' },
];
const _vaultMeta = props.meta?.notify_vault ?? {};
const vaultPrefs = reactive({
  notifyAccess: _vaultMeta.notifyAccess ?? true,
  notifyUnlock: _vaultMeta.notifyUnlock ?? true,
});
const activeAgreements = computed(() => props.activeAgreements ?? []);
const _agreementMeta = props.meta?.notify_agreements ?? {};
const agreementPrefs = reactive({
  expiryReminder:    _agreementMeta.expiryReminder    ?? true,
  autoRenew:         false,
  notifyCountersign: _agreementMeta.notifyCountersign ?? true,
});
const agreementNotifToggles = [
  { key: 'expiryReminder',    label: 'Agreement Expiry Reminder (30 days)', desc: 'Notify me 30 days before any steward agreement or attestation is due', model: 'agreementPrefs' },
  { key: 'notifyCountersign', label: 'Agreement Countersigned',             desc: 'Alert me when a steward signs or countersigns an agreement sent by you', model: 'agreementPrefs' },
];

// ─── My Services prefs ───────────────────────────────────────────────────────────
const _svcMeta = props.meta?.services_prefs ?? {};
const servicesPrefs = reactive({
  mode:            _svcMeta.mode            ?? false,
  showPublic:      _svcMeta.showPublic      ?? true,
  acceptBookings:  _svcMeta.acceptBookings  ?? true,
  showPricing:     _svcMeta.showPricing     ?? true,
  bpDiscoverable:  _svcMeta.bpDiscoverable  ?? true,
  bookingExpiry:   _svcMeta.bookingExpiry   ?? '48h',
  sessionBuffer:   _svcMeta.sessionBuffer   ?? '30min',
  hourlyRate:      _svcMeta.hourlyRate      ?? '',
});
const servicesSettingsSaving = ref(false);
function saveServicesSettings() {
  servicesSettingsSaving.value = true;
  router.put(route('provider.settings.services-settings'), {
    mode:           servicesPrefs.mode,
    showPublic:     servicesPrefs.showPublic,
    acceptBookings: servicesPrefs.acceptBookings,
    showPricing:    servicesPrefs.showPricing,
    bpDiscoverable: servicesPrefs.bpDiscoverable,
    bookingExpiry:  servicesPrefs.bookingExpiry,
    sessionBuffer:  servicesPrefs.sessionBuffer,
    hourlyRate:     servicesPrefs.hourlyRate,
  }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Services settings saved.'),
    onError:   () => toast.error('Could not save services settings.'),
    onFinish:  () => { servicesSettingsSaving.value = false; },
  });
}

// ─── Privacy ──────────────────────────────────────────────────────────────────────
const _privacyMeta = props.meta?.privacy_prefs ?? {};
const privacy = reactive({
  level:        _privacyMeta.level        ?? 'network',
  search:       _privacyMeta.search       ?? true,
  networkShow:  _privacyMeta.networkShow  ?? true,
  ratings:      _privacyMeta.ratings      ?? true,
  location:     _privacyMeta.location     ?? true,
  referralStats:_privacyMeta.referralStats ?? true,
  demographics: _privacyMeta.demographics ?? true,
});
const privacySaving = ref(false);
function savePrivacySettings() {
  privacySaving.value = true;
  router.put(route('provider.settings.privacy-settings'), {
    level:         privacy.level,
    search:        privacy.search,
    networkShow:   privacy.networkShow,
    ratings:       privacy.ratings,
    location:      privacy.location,
    referralStats: privacy.referralStats,
    demographics:  privacy.demographics,
  }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Privacy settings saved.'),
    onError:   () => toast.error('Could not save privacy settings.'),
    onFinish:  () => { privacySaving.value = false; },
  });
}
const privacyLevels = [
  { key: 'public',  name: 'Public',  desc: 'Visible to all Aegis providers', icon: 'eye'     },
  { key: 'network', name: 'Network', desc: 'Connected providers only',        icon: 'network' },
  { key: 'private', name: 'Private', desc: 'Invitation only',                 icon: 'lock'    },
];
const privacyToggles = [
  { key: 'search',       label: 'Appear in Provider Search',           desc: 'Let other providers find you when searching by specialty' },
  { key: 'networkShow',  label: 'Show in Integrative Care Network',    desc: 'Allow Aegis to surface your profile in the Integrative Care Network for suggestions' },
  { key: 'ratings',      label: 'Show Ratings & Reviews Publicly',     desc: 'Display your peer review scores on your public profile' },
  { key: 'location',     label: 'Show Location (City/State Only)',      desc: 'Display your practice location. Exact address never shown.' },
  { key: 'referralStats',label: 'Share Referral Statistics Publicly',  desc: 'Display your response time and acceptance rate on your public provider profile' },
  { key: 'demographics', label: 'Show Demographics on Public Profile', desc: 'Display language, specialty, and practice location on your public-facing profile' },
];

// ─── Network prefs ────────────────────────────────────────────────────────────────
const _networkMeta = props.meta?.notify_network ?? {};
const networkPrefs = reactive({
  requireApproval: _networkMeta.requireApproval ?? false,
  icnMatching: true, mutualConnections: true,
  dataUse:          _networkMeta.dataUse          ?? true,
  hideFromBP:       _networkMeta.hideFromBP       ?? false,
  connectionAlerts: _networkMeta.connectionAlerts ?? true,
  weeklyDigest:     _networkMeta.weeklyDigest     ?? true,
  featureUpdates:   _networkMeta.featureUpdates   ?? false,
  geoFocus:         _networkMeta.geoFocus         ?? '50mi',
});
const networkConnection = [
  { key: 'dataUse',    label: 'Allow Use of My Data for Network Suggestions', desc: "Anonymized data helps improve Aegis's provider matching quality" },
  { key: 'hideFromBP', label: 'Hide From Business Network Search',           desc: 'Business Partners cannot find your profile without a direct invitation' },
];
const networkNotifs = [
  { key: 'connectionAlerts', label: 'New Connection Request Alerts', desc: 'Get notified when someone requests to join your network' },
  { key: 'weeklyDigest',     label: 'Weekly Network Digest Email',   desc: 'Summary of your network activity, new connections, and referral stats each week' },
  { key: 'featureUpdates',   label: 'New Aegis Features & Updates',  desc: 'Be the first to know about new platform features and improvements' },
];

// ─── Appearance ───────────────────────────────────────────────────────────────────
const appearance = reactive({ theme: 'gold', darkMode: false, timezone: 'America/New_York' });
const themes = [
  { key: 'gold',      label: 'Aegis Gold',  desc: 'Classic warm gold (default)', swatch: 'linear-gradient(135deg,var(--gold-dark) 0%,var(--gold) 100%)' },
  { key: 'gold-dark', label: 'Gold Dark',   desc: 'Deep rich gold palette',       swatch: 'linear-gradient(135deg,var(--gold-dark) 0%,var(--gold-dark) 100%)' },
  { key: 'slate',     label: 'Slate Blue',  desc: 'Cool professional slate tone',  swatch: 'linear-gradient(135deg,var(--blue-dark) 0%,var(--blue) 100%)' },
];

// ─── Accessibility ────────────────────────────────────────────────────────────────
const a11y = reactive({ fontSize: 100, highContrast: false, colorBlind: false });

// ─── Billing / subscription (wired to backend) ────────────────────────────────────
const sub               = computed(() => props.subscription || {});
const subStatus         = computed(() => sub.value.status || 'none');
const subOnGracePeriod  = computed(() => !!sub.value.on_grace_period);
const currentTier       = computed(() => sub.value.tier || props.user?.tier || null);
const hasMaat           = computed(() => !!sub.value.has_maat_addon);
const prices            = computed(() => sub.value.prices || {});
const stripeInvoices    = computed(() => sub.value.invoices || []);
const stripePaymentMethods = computed(() => sub.value.payment_methods || []);
const currentBillingIsAnnual = computed(() => {
  const p = sub.value.price_id;
  return p === prices.value.access_annual || p === prices.value.practice_annual;
});
const billingAnnualView = ref(false);
const accessPriceId     = computed(() => billingAnnualView.value ? prices.value.access_annual   : prices.value.access_monthly);
const practicePriceId   = computed(() => billingAnnualView.value ? prices.value.practice_annual : prices.value.practice_monthly);
const maatBillingAnnual = computed(() => currentBillingIsAnnual.value);
const currentTierLabel  = computed(() => ({ access: 'Continuity Access', practice: 'Continuity Practice' }[currentTier.value] || 'No active plan'));
const currentPlanLine   = computed(() => {
  if (subStatus.value === 'none') return 'No active subscription';
  const monthly = currentTier.value === 'access' ? 29 : 49;
  const annualMo = currentTier.value === 'access' ? 23 : 39;
  const rate = currentBillingIsAnnual.value ? annualMo : monthly;
  return `${currentTierLabel.value} — $${rate}/mo (${currentBillingIsAnnual.value ? 'annual' : 'monthly'})`;
});
const billingMetaLine = computed(() => {
  if (subStatus.value === 'none') return 'Choose a plan to unlock the full platform.';
  const period = sub.value.current_period, next = sub.value.next_invoice;
  const parts = [];
  if (period?.start && period?.end) parts.push(`Billing cycle: ${formatDate(period.start)} – ${formatDate(period.end)}`);
  if (next?.amount_cents != null && next?.date) parts.push(`Next invoice: $${(next.amount_cents / 100).toFixed(2)} on ${formatDate(next.date)}`);
  return parts.join(' · ') || 'Billing details will appear after your first cycle.';
});
const isSwapAllowed = (tier) => tier !== currentTier.value || billingAnnualView.value !== currentBillingIsAnnual.value;
const swapButtonLabel = (tier) => {
  if (tier === currentTier.value) return billingAnnualView.value !== currentBillingIsAnnual.value ? (billingAnnualView.value ? 'Switch to annual' : 'Switch to monthly') : 'Your current plan';
  const tierOrder = ['access', 'practice'];
  const isUpgrade = tierOrder.indexOf(tier) > tierOrder.indexOf(currentTier.value);
  return isUpgrade ? 'Upgrade to this plan' : 'Downgrade to this plan';
};
const planBusy = ref(false); const maatBusy = ref(false); const pmBusy = ref(false);
const confirmCancel  = ref(false);
const confirmResume  = ref(false);
// Plan swap confirmation
const pendingSwap    = reactive({ tier: '', priceId: '', label: '', direction: '', priceLine: '', note: '' });
const confirmSwap    = ref(false);
// MAAT confirmation
const pendingMaat    = reactive({ enable: false });
const confirmMaat    = ref(false);
const accessFeatures   = ['Limited dashboard view','1 Continuity Steward included','2 Support Stewards included','Core continuity planning','Continuity Plan','Document Vault','Integrative Care Network (limited)'];
const practiceFeatures = ['Full dashboard access','Up to 2 Continuity Stewards','2 Support Stewards included','All continuity features','Continuity Plan','Full Document Vault','Full Integrative Care Network','Integrative Care Network Matching','Business Partners access'];
const maatFeatures     = ['Licensed & insured CS, certified by MAAT','4-hour emergency response guarantee','Annual CS recertification included'];
function swapPlan(tier) {
  const priceId = tier === 'access' ? accessPriceId.value : practicePriceId.value;
  if (!priceId) { toast.error('Price ID not configured.'); return; }

  const tierLabels = { access: 'Continuity Access', practice: 'Continuity Practice' };
  const tierPrices = { access: billingAnnualView.value ? 23 : 29, practice: billingAnnualView.value ? 39 : 49 };
  const billingLabel = billingAnnualView.value ? 'billed annually' : 'billed monthly';

  // Detect direction
  const tierOrder = ['access', 'practice'];
  const currentIdx = tierOrder.indexOf(currentTier.value);
  const newIdx     = tierOrder.indexOf(tier);
  let direction = 'switch';
  if (tier === currentTier.value) {
    direction = billingAnnualView.value ? 'switch-annual' : 'switch-monthly';
  } else if (newIdx > currentIdx) {
    direction = 'upgrade';
  } else {
    direction = 'downgrade';
  }

  pendingSwap.tier     = tier;
  pendingSwap.priceId  = priceId;
  pendingSwap.label    = tierLabels[tier];
  pendingSwap.direction = direction;
  pendingSwap.priceLine = `$${tierPrices[tier]}/mo — ${billingLabel}`;
  pendingSwap.note     = direction === 'upgrade'
    ? 'The prorated difference will be charged to your card today.'
    : direction === 'downgrade'
      ? 'The new price takes effect at the start of your next billing cycle. No refund is issued for the remainder of this cycle.'
      : direction === 'switch-annual'
        ? 'Switching to annual billing saves you 20%. The change takes effect at your next billing cycle.'
        : 'Switching to monthly billing. The change takes effect at your next billing cycle.';
  confirmSwap.value = true;
}

function doSwapPlan() {
  confirmSwap.value = false;
  planBusy.value = true;
  router.post(route('provider.settings.subscription.swap'), { price_id: pendingSwap.priceId }, {
    preserveScroll: true,
    onSuccess: () => toast.success(
      pendingSwap.direction === 'upgrade' ? 'Plan upgraded! Changes are effective immediately.' :
      pendingSwap.direction === 'downgrade' ? 'Plan change scheduled for next billing cycle.' :
      'Billing cycle updated.'
    ),
    onError: (errors) => toast.error(errors.subscription ?? 'Could not change plan.'),
    onFinish: () => { planBusy.value = false; },
  });
}
function cancelPlan() {
  planBusy.value = true;
  router.post(route('provider.settings.subscription.cancel'), {}, { preserveScroll: true, onFinish: () => { planBusy.value = false; confirmCancel.value = false; } });
}
function resumePlan() {
  confirmResume.value = true;
}

function doResumePlan() {
  confirmResume.value = false;
  planBusy.value = true;
  router.post(route('provider.settings.subscription.resume'), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Subscription reactivated! Your plan continues as before.'),
    onError: (errors) => toast.error(errors.subscription ?? 'Could not reactivate subscription.'),
    onFinish: () => { planBusy.value = false; },
  });
}
function toggleMaat(enable) {
  pendingMaat.enable = enable;
  confirmMaat.value  = true;
}

function doToggleMaat() {
  const enable = pendingMaat.enable;
  confirmMaat.value = false;
  maatBusy.value = true;
  router.post(route('provider.settings.subscription.maat'), { enable }, {
    preserveScroll: true,
    onSuccess: () => toast.success(enable
      ? 'MAAT Professional CS Service added to your subscription.'
      : 'MAAT Professional CS Service removed.'
    ),
    onError: (errors) => toast.error(errors.maat ?? 'Could not update MAAT add-on.'),
    onFinish: () => { maatBusy.value = false; },
  });
}
function setDefaultPm(pmId) {
  pmBusy.value = true;
  router.post(route('provider.settings.payment.default'), { payment_method_id: pmId }, { preserveScroll: true, onFinish: () => { pmBusy.value = false; } });
}
function removePm(pmId) {
  pmBusy.value = true;
  router.delete(route('provider.settings.payment.remove'), { data: { payment_method_id: pmId }, preserveScroll: true, onFinish: () => { pmBusy.value = false; } });
}
function goToPricing() { router.visit(route('onboarding.plan')); }
function formatDate(input) {
  if (!input) return '';
  const d = typeof input === 'number' ? new Date(input * 1000) : new Date(input);
  if (isNaN(d.getTime())) return '';
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
function formatCardBrand(brand) { return brand ? brand.charAt(0).toUpperCase() + brand.slice(1) : 'Card'; }

// ─── Financial toggles (invoices panel) ───────────────────────────────────────────
const _billingMeta = props.meta?.notify_billing ?? {};
const financial = reactive({ autoPay: true, requireApproval: true, spendLimit: false, invoiceEmails: _billingMeta.invoiceEmails ?? true });

// ─── Integrations ─────────────────────────────────────────────────────────────────

const integrationOptions = [
  { name: 'Epic EHR',       desc: 'Client records sync',      icon: 'activity'    },
  { name: 'Zoom Health',    desc: 'HIPAA video sessions',      icon: 'monitor'     },
  { name: 'DocuSign',       desc: 'E-signature',               icon: 'file-text'   },
  { name: 'Stripe Connect', desc: 'Payment processing',        icon: 'credit-card' },
];
const apiKeys = reactive([
  { name: 'Production Key',  value: 'kl_prod_••••••••••••••••••••••3f8a' },
  { name: 'Development Key', value: 'kl_dev_••••••••••••••••••••••9c21'  },
]);
const webhook = reactive({ url: '' });
const webhookEvents = reactive([
  { name: 'Referral Received',             on: true  },
  { name: 'Agreement Signed',              on: true  },
  { name: 'New Connection',                on: false },
  { name: 'Document Uploaded',             on: false },
  { name: 'Continuity Steward Activated',  on: false },
  { name: 'Client Alert',                  on: true  },
]);
const newKeyName = ref('');

// ─── Modals ───────────────────────────────────────────────────────────────────────
const modals = reactive({

  showUpgrade: false,
});

// ─── URL param routing (?tab=billing&upgrade=1) ───────────────────────────────
onMounted(() => {
  const params  = new URLSearchParams(window.location.search)
  const tab     = params.get('tab') || params.get('section')
  const anchor  = params.get('anchor')
  const upgrade = params.get('upgrade')
  if (tab) section.value = tab
  if (upgrade === '1') {
    section.value = 'billing'
    modals.showUpgrade = true
  }
  // Scroll to a sub-section anchor after the panel renders
  if (anchor) {
    nextTick(() => {
      setTimeout(() => {
        const el = document.getElementById('settings-anchor-' + anchor)
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' })
          // Highlight briefly
          el.style.transition = 'background 0.3s'
          el.style.background = 'var(--icon-bg-gold)'
          setTimeout(() => { el.style.background = '' }, 1200)
        }
      }, 150) // small delay for panel to render
    })
  }
})
// Sync TomSelect disabled state when a day is toggled on/off
watch(
  () => weekDays.map(d => d.on),
  () => {
    nextTick(() => {
      document.querySelectorAll('.avail-day-row select').forEach(el => {
        const ts = el.tomselect;
        if (!ts) return;
        el.disabled ? ts.disable() : ts.enable();
      });
    });
  }
);

const pauseForm    = reactive({ until: '', reason: 'leave', message: '' });
const transferForm = reactive({ provider: '', scope: 'active' });
const deleteForm   = reactive({ transferTo: '', confirm: '' });
function confirmDelete() {
  if (deleteForm.confirm !== 'DELETE MY ACCOUNT') return;
  router.delete(route('provider.settings.account.delete'), { data: { password: '' } });
}
const exportOptions = reactive([
  { key: 'notifications', label: 'Notification preferences', checked: true  },
  { key: 'privacy',       label: 'Privacy & visibility settings', checked: true  },
  { key: 'availability',  label: 'Availability schedule', checked: true  },
  { key: 'referrals',     label: 'Referral preferences', checked: false },
  { key: 'integrations',  label: 'Integrations config',  checked: false },
]);

function saveReferralPrefs() {
  router.put(route('provider.settings.referral'), {
    accepting:   referralPrefs.accepting,
    autoAccept:  referralPrefs.autoAccept,
    suggestAlts: referralPrefs.suggestAlts,
    autoArchive: referralPrefs.autoArchive,
  }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Referral preferences saved.'),
    onError:   () => toast.error('Could not save referral preferences.'),
  });
}
</script>

<style scoped>
.settings-layout { display: grid; grid-template-columns: 240px 1fr; gap: 22px; align-items: start; padding-bottom: 40px; }

/* SIDEBAR */
/* settings-sidebar: wrapper kept; nav uses global .page-sidebar-* classes */
.settings-sidebar { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); position: sticky; top: 80px; }

/* PANELS */
.settings-content { min-width: 0; }
.settings-panel { display: block; }

/* PRIVACY LEVELS */
.privacy-levels { display: flex; gap: 10px; }
.privacy-level-btn { flex: 1; padding: 14px 10px; border: 1px solid var(--border); border-radius: var(--radius); text-align: center; cursor: pointer; transition: all var(--transition); background: var(--surface); }
.privacy-level-btn:hover { border-color: var(--gold-dark); }
.privacy-level-btn.active { border-color: var(--gold-dark); background: var(--icon-bg-gold); }
.privacy-level-icon { width: 36px; height: 36px; border-radius: var(--radius); margin: 0 auto 8px; display: flex; align-items: center; justify-content: center; }
.privacy-level-name { font-size: 13px; font-weight: 700; color: var(--text); }
.privacy-level-desc { font-size: 11px; color: var(--text-3); margin-top: 2px; line-height: 1.4; }

/* 2FA OPTIONS */
.twofa-option { display: flex; align-items: center; gap: 14px; padding: 14px 16px; border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 10px; cursor: pointer; transition: all var(--transition); }
.twofa-option:hover { border-color: var(--gold-dark); }
.twofa-option.active { border-color: var(--gold-dark); background: var(--icon-bg-gold); }
.twofa-icon { width: 40px; height: 40px; border-radius: var(--radius); background: var(--icon-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.twofa-info { flex: 1; }
.twofa-name { font-size: 14px; font-weight: 600; color: var(--text); }
.twofa-desc { font-size: 12px; color: var(--text-3); }

/* SESSION ITEMS */
.session-item { display: flex; align-items: center; gap: 14px; padding: 13px 16px; border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 10px; background: var(--surface); }
.session-item:last-child { margin-bottom: 0; }
.session-icon { width: 38px; height: 38px; border-radius: var(--radius); background: var(--surface-3); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--text-3); }
.session-info { flex: 1; }
.session-device { font-size: 14px; font-weight: 600; color: var(--text); }
.session-meta { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.session-current { font-size: 11px; font-weight: 700; color: var(--green); background: var(--green-light); padding: 2px 8px; border-radius: var(--radius-full); margin-left: 8px; }

/* NOTIFICATION TABLE */
.notif-table { width: 100%; border-collapse: collapse; }
.notif-table th { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-3); padding: 0 12px 10px; text-align: center; }
.notif-table th:first-child { text-align: left; padding-left: 0; }
.th-icon { display: inline-flex; align-items: center; gap: 5px; }
.notif-table td { padding: 11px 12px; border-top: 1px solid var(--border); text-align: center; }
.notif-table td:first-child { text-align: left; padding-left: 0; }
.notif-cat-label { font-weight: 600; color: var(--text); font-size: 13px; }
.notif-cat-desc { font-size: 12px; color: var(--text-3); margin-top: 1px; }

/* PERMISSION CHIPS */
.permission-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 4px; }
.perm-chip { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid var(--border); border-radius: var(--radius); font-size: 13px; color: var(--text-2); cursor: pointer; transition: all var(--transition); background: var(--surface); }
.perm-chip:hover { border-color: var(--gold-dark); color: var(--text); }
.perm-chip.on { border-color: var(--gold-dark); background: var(--icon-bg-gold); color: var(--gold-dark); font-weight: 600; }
.perm-dot { width: 8px; height: 8px; border-radius: var(--radius-full); background: var(--border-dark); transition: var(--transition); flex-shrink: 0; }
.perm-chip.on .perm-dot { background: var(--gold-dark); }

/* API KEY ROWS */
.api-key-row { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 10px; background: var(--surface-2); }
.api-key-name { font-size: 13px; font-weight: 600; color: var(--text); flex: 1; }
.api-key-value { font-size: 12px; font-family: monospace; color: var(--text-3); background: var(--surface-3); padding: 4px 10px; border-radius: var(--radius-sm); letter-spacing: 1px; }

/* INTEGRATIONS */
.app-status-connected { font-size: 12px; font-weight: 600; color: var(--green); background: var(--green-light); padding: 3px 10px; border-radius: var(--radius-full); display: inline-flex; align-items: center; gap: 5px; }

/* DANGER ZONE */
.danger-zone { border: 1px solid color-mix(in srgb, var(--red) 28%, transparent); border-radius: var(--radius-lg); padding: 20px 24px; background: var(--red-light); }
.danger-zone-title { font-size: 14px; font-weight: 700; color: var(--red); margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.danger-action { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 0; border-top: 1px solid color-mix(in srgb, var(--red) 18%, transparent); }
.danger-action:first-of-type { border-top: none; }
.danger-action-info { flex: 1; }
.danger-action-label { font-size: 14px; font-weight: 600; color: var(--text); }
.danger-action-desc { font-size: 12px; color: var(--text-3); margin-top: 2px; }

/* ACCESSIBILITY */
.font-slider { width: 100%; margin-top: 10px; }
input[type=range] { -webkit-appearance: none; width: 100%; height: 4px; border-radius: var(--radius-sm); background: var(--border); outline: none; }
input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; width: 18px; height: 18px; border-radius: var(--radius-full); background: var(--gold-dark); cursor: pointer; }
.a11y-option { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--border); }
.a11y-option:last-child { border-bottom: none; }
.a11y-icon { width: 36px; height: 36px; border-radius: var(--radius-sm); background: var(--icon-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.a11y-info { flex: 1; }
.a11y-label { font-size: 14px; font-weight: 600; color: var(--text); }
.a11y-desc { font-size: 12px; color: var(--text-3); margin-top: 1px; }

/* BILLING st-* classes (preserved from wired build) */
.st-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); margin-bottom: 16px; overflow: hidden; }
.st-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 18px 20px; border-bottom: 1px solid var(--border); }
.st-card-head-l { display: flex; align-items: center; gap: 10px; min-width: 0; }
.st-card-ico { width: 34px; height: 34px; border-radius: var(--radius-sm); background: var(--icon-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.st-card-title { font-size: 15px; font-weight: 700; color: var(--text); font-family: var(--font-serif); }
.st-card-sub { font-size: 12px; color: var(--text-3); margin-top: 1px; }
.st-card-body { padding: 20px; }
.st-bp-badge { font-size: 11px; font-weight: 700; color: var(--gold-dark); background: var(--icon-bg-gold); padding: 3px 10px; border-radius: var(--radius-full); white-space: nowrap; border: 1px solid var(--badge-border-gold); }
.st-current-band { display: flex; align-items: center; justify-content: space-between; gap: 14px; background: var(--icon-bg-gold); border: 1px solid var(--badge-border-gold); border-radius: var(--radius); padding: 14px 18px; margin-bottom: 18px; }
.st-current-name { font-family: var(--font-serif); font-size: 18px; font-weight: 700; color: var(--text); }
.st-current-meta { font-size: 13px; color: var(--text-3); margin-top: 3px; }
.st-perks-band { display: flex; align-items: flex-start; gap: 10px; background: var(--icon-bg-gold); border: 1px solid var(--badge-border-gold); border-radius: var(--radius); padding: 10px 14px; margin-bottom: 16px; font-size: 13px; color: var(--text-2); line-height: 1.5; }
.st-founding-banner { display: flex; align-items: center; gap: 10px; background: var(--icon-bg-gold); border: 1px solid var(--icon-bg-gold); border-radius: var(--radius); padding: 10px 14px; margin-bottom: 16px; font-size: 13px; color: var(--text-2); }
.st-upgrade-cta { display: flex; align-items: center; justify-content: space-between; gap: 14px; background: var(--surface-2); border: 1px solid var(--primary); border-radius: var(--radius); padding: 14px 18px; margin-bottom: 18px; }
.st-upgrade-cta-title { font-family: var(--font-serif); font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 3px; }
.st-upgrade-cta-sub { font-size: 13px; color: var(--text-3); }
.st-cycle-toggle { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 18px; font-size: 13px; font-weight: 600; color: var(--text-3); }
.st-cycle-toggle .active { color: var(--text-2); }
.st-save-pill { background: var(--green-light); color: var(--green-dark); font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: var(--radius-full); }
.st-plan-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; max-width: 680px; margin: 0 auto; }
.st-plan-tier { border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px; text-align: center; position: relative; background: var(--surface); transition: border-color var(--transition); }
.st-plan-tier.current { border-color: var(--gold-dark); background: var(--icon-bg-gold); }
.st-plan-tier-badge { display: inline-flex; align-items: center; gap: 5px; background: var(--gold-dark); color: var(--text-inverted); font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: var(--radius-full); letter-spacing: 0.5px; margin-bottom: 10px; }
.st-plan-tier-name { font-family: var(--font-serif); font-size: 18px; font-weight: 700; color: var(--text); }
.st-plan-tier-price { font-size: 26px; font-weight: 700; color: var(--gold-dark); margin: 8px 0 4px; }
.st-plan-tier-price span { font-size: 13px; color: var(--text-3); font-weight: 600; }
.st-plan-tier-alt { font-size: 11px; color: var(--text-4); margin-bottom: 12px; }
.st-plan-feats { font-size: 12px; color: var(--text-2); line-height: 2; margin-top: 12px; text-align: left; display: flex; flex-direction: column; gap: 2px; }
.st-plan-feats span { display: flex; align-items: center; gap: 6px; }
.st-plan-cta { width: 100%; margin-top: 14px; }
.st-included-head { font-size: 11px; font-weight: 700; letter-spacing: 0.7px; text-transform: uppercase; color: var(--text-4); }
.st-note { font-size: 12.5px; color: var(--text-3); }
.st-addon-card { display: flex; align-items: flex-start; gap: 16px; padding: 18px; border: 1px solid var(--badge-border-gold); border-radius: var(--radius-lg); background: var(--icon-bg-gold); }
.st-addon-body { flex: 1; min-width: 0; }
.st-addon-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 8px; }
.st-addon-name { font-family: var(--font-serif); font-size: 15px; font-weight: 700; color: var(--text); }
.st-addon-tag { font-size: 10px; font-weight: 700; letter-spacing: 0.5px; background: var(--gold-dark); color: var(--text-inverted); padding: 2px 7px; border-radius: var(--radius-full); vertical-align: middle; margin-left: 6px; }
.st-addon-price { text-align: right; flex-shrink: 0; font-family: var(--font-serif); font-size: 18px; font-weight: 700; color: var(--text); }
.st-addon-billed { font-size: 11px; color: var(--text-4); font-family: var(--font-sans); font-weight: 400; margin-top: 2px; }
.st-addon-desc { font-size: 12.5px; color: var(--text-3); margin-bottom: 10px; }
.st-addon-feats { font-size: 12px; color: var(--text-2); display: flex; flex-wrap: wrap; gap: 4px 18px; margin-bottom: 14px; }
.st-addon-feats span { display: inline-flex; align-items: center; gap: 5px; }
.st-addon-foot { display: flex; align-items: center; gap: 12px; }
.st-addon-req { font-size: 11px; color: var(--text-4); }
.st-toggle-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 14px 0; border-bottom: 1px solid var(--border); }
.st-toggle-row:last-of-type { border-bottom: none; }
.st-toggle-name { font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 2px; }
.st-toggle-sub { font-size: 12px; color: var(--text-3); line-height: 1.5; }
.st-subhead { font-size: 11px; font-weight: 700; letter-spacing: 0.7px; text-transform: uppercase; color: var(--text-4); margin-bottom: 12px; }
.st-divider { border: none; border-top: 1px solid var(--border); margin: 18px 0; }
.st-pay-row { display: flex; align-items: center; gap: 12px; }
.st-pay-ico { width: 34px; height: 34px; border-radius: var(--radius-sm); background: var(--surface-3); color: var(--text-3); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.st-pay-info { flex: 1; min-width: 0; }
.st-pay-num { font-size: 13px; font-weight: 700; color: var(--text); }
.st-pay-exp { font-size: 12px; color: var(--text-3); margin-top: 1px; }
.st-badge-green { font-size: 12px; font-weight: 600; color: var(--green); background: var(--green-light); padding: 3px 10px; border-radius: var(--radius-full); display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
.st-link-btn { background: none; border: none; cursor: pointer; font-size: 13px; color: var(--text-3); padding: 0; }
.st-remove { color: var(--red); }
.st-invoice-table { width: 100%; border-collapse: collapse; }
.st-invoice-table th { text-align: left; font-size: 9.5px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: var(--text-4); padding: 10px 8px; border-bottom: 1px solid var(--border); }
.st-invoice-table td { font-size: 13px; color: var(--text-2); padding: 14px 8px; border-bottom: 1px solid var(--border); }
.st-invoice-table tr:last-child td { border-bottom: none; }
.st-inv-date { font-weight: 700; color: var(--text); white-space: nowrap; }
.st-inv-amount { font-weight: 700; color: var(--text); }
.st-inv-status { display: inline-flex; align-items: center; gap: 5px; font-size: 12.5px; font-weight: 600; color: var(--green-dark); }
.st-inv-dl { margin-left: 14px; }
.st-qr-box { display: flex; flex-direction: column; align-items: center; gap: 10px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 18px; }
.st-qr-ico { width: 46px; height: 46px; border-radius: var(--radius); background: var(--surface-3); color: var(--text-3); display: flex; align-items: center; justify-content: center; }
.st-qr-cap { font-size: 12px; color: var(--text-4); }
.st-qr-secret { font-family: ui-monospace, monospace; font-size: 13px; font-weight: 700; letter-spacing: 2px; color: var(--text-2); }
.st-otp { text-align: center; font-family: ui-monospace, monospace; letter-spacing: 8px; font-size: 16px; }
.st-codes-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.st-code { font-family: ui-monospace, monospace; font-size: 13px; font-weight: 600; letter-spacing: .5px; color: var(--text-2); background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 11px 14px; text-align: center; }
.st-code.used { color: var(--text-4); text-decoration: line-through; }
.st-key-warn { display: flex; align-items: flex-start; gap: 9px; font-size: 12.5px; color: var(--gold-dark); background: var(--badge-bg-gold); border: 1px solid color-mix(in srgb, var(--gold-dark) 22%, transparent); border-radius: var(--radius); padding: 13px 15px; line-height: 1.5; margin-bottom: 16px; }
.st-export-list { margin: 0; padding-left: 20px; }
.st-export-list li { font-size: 13px; color: var(--text-2); line-height: 1.9; }

/* SECTION LABELS — consistent spacing everywhere */
.section-label { font-size: 10px; font-weight: 700; letter-spacing: 0.9px; text-transform: uppercase; color: var(--text-4); margin: 22px 0 8px; padding-bottom: 6px; border-bottom: 1px solid var(--border); }

/* TOGGLE ROW — unified design matching st-toggle-row */
.toggle-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 13px 0; border-bottom: 1px solid var(--border); }
.toggle-row:last-of-type { border-bottom: none; }
.toggle-info { flex: 1; min-width: 0; }
.toggle-label { font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 2px; }
.toggle-desc { font-size: 12px; color: var(--text-3); line-height: 1.5; }
.toggle-row .toggle { flex-shrink: 0; margin-top: 2px; }

/* INTEGRATIONS GRID */
.integrations-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
.integration-card { border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 16px; background: var(--surface); transition: border-color var(--transition), box-shadow var(--transition); display: flex; flex-direction: column; gap: 6px; }
.integration-card:hover { border-color: var(--gold-dark); }
.integration-card.connected { border-color: var(--green); background: var(--green-light); }
.integration-card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
.integration-logo { width: 38px; height: 38px; border-radius: var(--radius); background: var(--surface-2); display: flex; align-items: center; justify-content: center; color: var(--text-2); flex-shrink: 0; }
.integration-card.connected .integration-logo { background: var(--surface); }
.integration-name { font-size: 13px; font-weight: 700; color: var(--text); }
.integration-desc { font-size: 12px; color: var(--text-3); line-height: 1.4; flex: 1; }
.integration-actions { margin-top: 6px; }

/* STRIPE SETUP CARD */
.stripe-setup-card { border: 1px solid var(--badge-border-gold); border-radius: var(--radius-lg); background: var(--icon-bg-gold); overflow: hidden; }
.stripe-setup-inner { display: flex; gap: 16px; padding: 18px 20px; align-items: flex-start; }
.stripe-setup-icon { width: 44px; height: 44px; border-radius: var(--radius); background: var(--gold-dark); color: var(--text-inverted); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stripe-setup-body { flex: 1; min-width: 0; }
.stripe-setup-title { font-family: var(--font-serif); font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
.stripe-setup-desc { font-size: 13px; color: var(--text-2); line-height: 1.5; margin-bottom: 12px; }
.stripe-setup-connected { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.stripe-setup-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

/* ── Availability schedule grid ─────────────────────────────────────────────── */
.avail-schedule {
  margin-bottom: 20px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
}
.avail-schedule-header,
.avail-day-row {
  display: grid;
  grid-template-columns: 42px 110px 1fr 1fr;
  align-items: center;
  gap: 0 12px;
  padding: 0 16px;
}
.avail-schedule-header {
  padding-top: 8px;
  padding-bottom: 8px;
  background: var(--surface-secondary);
  border-bottom: 1px solid var(--border);
}
.avail-col-label {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--text-secondary);
}
.avail-day-row {
  padding-top: 10px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--border);
  transition: background 0.15s;
}
.avail-day-row:last-child { border-bottom: none; }
.avail-day-row:hover { background: var(--surface-hover); }
.avail-day-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  transition: color 0.15s;
}
.avail-day-off .avail-day-name { color: var(--text-3); }
.avail-day-off select { opacity: 0.4; pointer-events: none; }

/* NAV BADGE — gold design */
.s-nav-badge { margin-left: auto; background: var(--icon-bg-gold); color: var(--gold-dark); border: 1px solid var(--badge-border-gold); font-size: 10px; padding: 1px 7px; border-radius: var(--radius-full); font-weight: 700; }

/* SECTION LABEL — no top margin when first child of card-body */
.card-body > .section-label:first-child,
.st-card-body > .section-label:first-child { margin-top: 0; }

/* RATE / PAYMENT BLOCK */
.rate-payment-block { display: flex; gap: 12px; margin-top: 6px; align-items: flex-start; }
.rate-field { flex: 1; }
.rate-input-wrap { position: relative; }
.rate-currency { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 14px; color: var(--text-3); font-weight: 600; pointer-events: none; }
.rate-input-wrap .form-input { padding-left: 26px; }
.rate-payment-info { flex: 1; display: flex; align-items: flex-start; gap: 12px; padding: 12px 16px; background: var(--icon-bg-gold); border: 1px solid var(--badge-border-gold); border-radius: var(--radius-lg); color: var(--gold-dark); }

/* INTEGRATIONS EMPTY STATE */
.integrations-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 36px 20px; text-align: center; color: var(--text-3); gap: 0; }

/* INVOICE LIST */
.inv-empty { display: flex; align-items: center; gap: 12px; padding: 20px; background: var(--surface-2); border-radius: var(--radius); color: var(--text-3); font-size: 13px; }
.inv-list { display: flex; flex-direction: column; gap: 0; }
.inv-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 0; border-bottom: 1px solid var(--border); }
.inv-row:last-child { border-bottom: none; }
.inv-row-left { flex: 1; min-width: 0; }
.inv-date { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-4); margin-bottom: 3px; }
.inv-desc { font-size: 13px; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.inv-row-right { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
.inv-amount { font-size: 15px; font-weight: 700; color: var(--text); font-family: var(--font-serif); }
.inv-status-wrap { display: flex; align-items: center; gap: 8px; }
.inv-status-paid { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; color: var(--green-dark); background: var(--green-light); padding: 3px 9px; border-radius: var(--radius-full); }
.inv-status-open { font-size: 11px; font-weight: 700; color: var(--gold-dark); background: var(--icon-bg-gold); padding: 3px 9px; border-radius: var(--radius-full); }
.inv-status-other { font-size: 11px; color: var(--text-3); font-weight: 600; }
.inv-dl { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: var(--radius); background: var(--surface-2); color: var(--text-3); transition: all var(--transition); }
.inv-dl:hover { background: var(--surface-3); color: var(--text); }

/* Billing table — matches PHP settings.php #panel-subscription design */
.billing-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.billing-table thead tr { border-bottom: 1px solid var(--border); }
.billing-table th { padding: 8px 12px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-4); white-space: nowrap; }
.billing-table th:last-child { width: 40px; }
.billing-table td { padding: 12px 12px; color: var(--text-2); vertical-align: middle; border-bottom: 1px solid var(--border); }
.billing-table tbody tr:last-child td { border-bottom: none; }
.billing-table tbody tr:hover td { background: var(--surface-2); }
.billing-table td:first-child { white-space: nowrap; font-size: 12px; color: var(--text-3); }
.billing-table td:nth-child(3) { font-family: var(--font-serif); font-weight: 700; color: var(--text); white-space: nowrap; }
.billing-table td:last-child { text-align: center; }

.st-pm-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
.st-pm-empty { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--text-3); padding: 12px 0; }
.st-pm-list { display: flex; flex-direction: column; gap: 0; }
.st-pm-list .st-pay-row { margin-bottom: 0; padding: 10px 0; border-bottom: 1px solid var(--border); }
.st-pm-list .st-pay-row:last-child { border-bottom: none; }

/* RESPONSIVE */
@media (max-width: 1000px) { .settings-layout { grid-template-columns: 1fr; } .settings-sidebar { position: static; } }
@media (max-width: 700px) { .st-plan-grid { grid-template-columns: 1fr; } .privacy-levels { flex-direction: column; } .notif-table { display: block; overflow-x: auto; } .rate-payment-block { flex-direction: column; } }

/* ── Tier gate overlay ──────────────────────────────────────────────────── */
.settings-tier-gate {
  position: absolute;
  inset: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255,255,255,0.85);
  backdrop-filter: blur(2px);
  border-radius: var(--radius-lg);
  padding: 24px;
}
.settings-tier-gate-inner {
  text-align: center;
  max-width: 360px;
}
.settings-tier-gate-icon {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: var(--icon-bg-gold);
  color: var(--gold-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 14px;
}
.settings-tier-gate-title {
  font-family: var(--font-serif);
  font-size: 17px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 8px;
}
.settings-tier-gate-body {
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.6;
  margin-bottom: 16px;
}
.settings-tier-blurred {
  filter: blur(4px);
  pointer-events: none;
  user-select: none;
  opacity: 0.45;
}
.settings-panel { position: relative; }
/* Locked nav item */
.page-sidebar-item.is-locked { opacity: 0.6; }
.page-sidebar-item.is-locked:hover { opacity: 0.8; }
.s-nav-lock {
  margin-left: auto;
  color: var(--gold-dark);
  display: inline-flex;
  align-items: center;
}
/* ── Billing quick-action shortcuts ── */
.st-billing-shortcuts {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 22px;
}
.st-shortcut-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  text-decoration: none;
  color: var(--text-2);
  transition: border-color var(--transition), background var(--transition), box-shadow var(--transition);
}
.st-shortcut-btn:hover {
  border-color: var(--gold-dark);
  background: var(--badge-bg-gold);
  color: var(--text);
  box-shadow: var(--shadow-sm);
}
.st-shortcut-icon {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-sm);
  background: var(--surface);
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--gold-dark);
}
.st-shortcut-btn:hover .st-shortcut-icon {
  background: var(--gold-dark);
  color: var(--text-inverted);
  border-color: var(--gold-dark);
}
.st-shortcut-label { font-size: 13px; font-weight: 600; color: var(--text); line-height: 1.2; }
.st-shortcut-sub   { font-size: 11px; color: var(--text-3); margin-top: 2px; }
.st-shortcut-arrow { margin-left: auto; color: var(--text-4); flex-shrink: 0; }
</style>
