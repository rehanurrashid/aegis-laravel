<template>
  <AppLayout :user="user" portal="continuity_steward" activePage="settings" pageTitle="Settings">
    <AegisHeroBanner eyebrow="Continuity Steward" title="Continuity Steward Settings" quiet />
    <div class="settings-layout">
      <div class="settings-sidebar">
        <div class="settings-sidebar-header">
          <div class="settings-sidebar-header-icon"><AegisIcon name="settings" :size="16" /></div>
          <div><h3>Settings</h3></div>
        </div>
        <template v-for="grp in nav" :key="grp.group">
          <div class="settings-nav-group">
            <div class="settings-nav-label">{{ grp.group }}</div>
            <button v-for="it in grp.items" :key="it.key" type="button"
              class="settings-nav-item" :class="{ active: section === it.key }"
              :style="it.danger ? \'color:var(--red)\' : \'\'" @click="section = it.key">
              <span class="s-nav-icon" v-html="it.icon"></span>{{ it.label }}
            </button>
          </div>
        </template>
      </div>
      <div class="settings-content">
        <div v-show="section === \'profile\'" class="settings-panel">
          <div class="alert alert-info" style="margin-bottom:16px">
            <div class="alert-icon"><AegisIcon name="users" :size="18" /></div>
            <div class="alert-content"><strong>Unified identity across all portals.</strong> Your photo, display name, email, and phone are shared across every portal you have access to.</div>
          </div>
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="user" :size="16" /></div>
                <div><div class="card-title">Profile Summary</div><div class="card-subtitle">Your public-facing identity on Aegis</div></div>
              </div>
              <a :href="route(\'cs.profile.edit\')" class="btn btn-primary btn-sm"><AegisIcon name="edit" :size="13" /> Edit Full Profile</a>
            </div>
            <div class="card-body">
              <div class="list-group">
                <div class="list-group-item" style="gap:14px">
                  <div class="avatar avatar-lg avatar-gold">{{ initials }}</div>
                  <div style="flex:1">
                    <div style="font-family:var(--font-serif);font-size:16px;font-weight:700;color:var(--text)">{{ displayName }}</div>
                    <div style="font-size:12px;color:var(--text-4);margin-top:4px;display:flex;gap:14px;flex-wrap:wrap">
                      <span style="display:flex;align-items:center;gap:4px"><AegisIcon name="mail" :size="12" /> {{ user?.email ?? \'\'  }}</span>
                    </div>
                  </div>
                  <span class="badge badge-blue">CS</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-show="section === \'account\'" class="settings-panel">
          <SettingsAccount
            :user="user"
            :sessions="sessions"
            update-password-route="cs.settings.password"
            revoke-session-route="cs.settings.sessions.revoke"
            revoke-all-route="cs.settings.sessions.revoke-all"
          />
        </div>
        <div v-show="section === \'security\'" class="settings-panel">
          <SettingsSecurity enable-mfa-route="cs.settings.mfa.enable" disable-mfa-route="cs.settings.mfa.disable" verify-mfa-route="cs.settings.mfa.verify" :mfa-enabled="mfaEnabled" />
        </div>
        <div v-show="section === \'notifications\'" class="settings-panel">
          <SettingsNotifications update-route="cs.settings.notifications"
            :saved-prefs="meta?.notify_prefs ?? {}"
            :saved-categories="meta?.notify_categories ?? []" subtitle="Delivery channels unified across portals. Per-category preferences apply to your Continuity Steward role." :notif-categories="csNotifCategories" />
        </div>
        <div v-show="section === \'messaging\'" class="settings-panel">
          <SettingsMessaging update-route="cs.settings.messaging" messages-route="cs.messages.index" subtitle="Control who can reach you and how you appear to assigned practitioners" :meta="meta">
            <template #extra-toggles>
              <div class="toggle-row">
                <div class="toggle-info"><div class="toggle-label">Critical Incident Thread Auto-Flag</div><div class="toggle-desc">Messages sent during an active incident are automatically marked as legal record</div></div>
                <button type="button" class="toggle on" aria-pressed="true"></button>
              </div>
            </template>
          </SettingsMessaging>
        </div>
        <div v-show="section === \'email-prefs\'" class="settings-panel">
          <SettingsEmailPrefs update-route="cs.settings.email-prefs" activity-label="Stewardship Activity Summary" activity-desc="Digest of plan changes, attestations, and coverage events" :meta="meta" />
        </div>
        <div v-show="section === \'cs-steward\'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="shield" :size="16" /></div>
                <div><div class="card-title">Continuity Steward Settings</div><div class="card-subtitle">Preferences for your stewardship role and responsibilities</div></div>
              </div>
            </div>
            <div class="card-body">
              <div class="section-label">Role Visibility</div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Show Name on Provider Public Profile</div><div class="toggle-desc">Allow your name to appear as "Continuity Steward" on your providers\' public Aegis pages</div></div><button type="button" class="toggle" :class="{ on: csRolePrefs.showOnProfile }" @click="csRolePrefs.showOnProfile = !csRolePrefs.showOnProfile" :aria-pressed="csRolePrefs.showOnProfile"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Request Vault Access on Assignment</div><div class="toggle-desc">Automatically request emergency vault access when assigned to a new provider</div></div><button type="button" class="toggle" :class="{ on: csRolePrefs.vaultAccess }" @click="csRolePrefs.vaultAccess = !csRolePrefs.vaultAccess" :aria-pressed="csRolePrefs.vaultAccess"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px"><button type="button" class="btn btn-primary" @click="toast.success(\'CS settings saved.\')"><AegisIcon name="check" :size="16" /> Save CS Settings</button></div>
            </div>
          </div>
        </div>
        <div v-show="section === \'documents-s\'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="lock" :size="16" /></div>
                <div><div class="card-title">Document Vault Access</div><div class="card-subtitle">Notification preferences for vault activity on providers you steward</div></div>
              </div>
            </div>
            <div class="card-body">
              <div class="alert alert-info" style="margin-bottom:16px"><div class="alert-icon"><AegisIcon name="info" :size="16" /></div><div class="alert-content" style="font-size:12px">Your vault access level is set by each practitioner individually. Access to sealed zones is only released when a critical incident is verified.</div></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Notify Me When I Access a Vault</div><div class="toggle-desc">Receive a confirmation log entry whenever you open or download a document from a provider\'s vault</div></div><button type="button" class="toggle" :class="{ on: vaultNotifyPrefs.notifyAccess }" @click="vaultNotifyPrefs.notifyAccess = !vaultNotifyPrefs.notifyAccess" :aria-pressed="vaultNotifyPrefs.notifyAccess"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Notify Me on Emergency Vault Unlock</div><div class="toggle-desc">Alert me immediately when an emergency vault becomes accessible during an active incident</div></div><button type="button" class="toggle" :class="{ on: vaultNotifyPrefs.notifyUnlock }" @click="vaultNotifyPrefs.notifyUnlock = !vaultNotifyPrefs.notifyUnlock" :aria-pressed="vaultNotifyPrefs.notifyUnlock"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px"><button type="button" class="btn btn-primary" @click="toast.success(\'Vault settings saved.\')"><AegisIcon name="check" :size="13" /> Save</button></div>
            </div>
          </div>
        </div>
        <div v-show="section === \'privacy\'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="shield" :size="16" /></div>
                <div><div class="card-title">Privacy &amp; Visibility</div><div class="card-subtitle">Applies to your public Continuity Steward profile</div></div>
              </div>
            </div>
            <div class="card-body">
              <div style="margin-bottom:16px">
                <div class="form-label" style="margin-bottom:10px">Overall Profile Visibility Level</div>
                <div class="privacy-levels">
                  <div v-for="lvl in privacyLevels" :key="lvl.key" class="privacy-level-btn" :class="{ active: csPrivacy.level === lvl.key }" @click="csPrivacy.level = lvl.key">
                    <div class="privacy-level-icon" style="background:var(--surface-2);color:var(--text-2)"><AegisIcon :name="lvl.icon" :size="18" /></div>
                    <div class="privacy-level-name">{{ lvl.name }}</div>
                    <div class="privacy-level-desc">{{ lvl.desc }}</div>
                  </div>
                </div>
              </div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Appear in CS Search Results</div><div class="toggle-desc">Let practitioners find you when searching for a Continuity Steward</div></div><button type="button" class="toggle" :class="{ on: csPrivacy.search }" @click="csPrivacy.search = !csPrivacy.search" :aria-pressed="csPrivacy.search"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Show Location on Profile</div><div class="toggle-desc">Display your general city/region on your public CS profile</div></div><button type="button" class="toggle" :class="{ on: csPrivacy.location }" @click="csPrivacy.location = !csPrivacy.location" :aria-pressed="csPrivacy.location"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Show Credentials on Profile</div><div class="toggle-desc">Display your professional credentials and title publicly</div></div><button type="button" class="toggle" :class="{ on: csPrivacy.creds }" @click="csPrivacy.creds = !csPrivacy.creds" :aria-pressed="csPrivacy.creds"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px"><button type="button" class="btn btn-primary" @click="toast.success(\'Privacy settings saved.\')"><AegisIcon name="check" :size="13" /> Save Privacy Settings</button></div>
            </div>
          </div>
        </div>
        <div v-show="section === \'appearance\'" class="settings-panel">
          <SettingsAppearance update-route="cs.settings.appearance" :meta="meta" />
        </div>
        <div v-show="section === \'billing\'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="star" :size="16" /></div>
                <div><div class="card-title">Subscription &amp; Plan</div><div class="card-subtitle">{{ isInvitedCs ? \'Your access is covered by your linked provider\' : \'Business CS Plan\' }}</div></div>
              </div>
              <span class="badge badge-green" style="font-size:12px;display:inline-flex;align-items:center;gap:5px"><AegisIcon name="check" :size="12" /> Active{{ isInvitedCs ? \' — No Cost\' : \'\'  }}</span>
            </div>
            <div class="card-body">
              <div v-if="isInvitedCs">
                <div class="alert alert-info" style="margin-bottom:18px"><div class="alert-icon"><AegisIcon name="shield" :size="18" /></div><div class="alert-content"><strong>You have full Continuity Steward access — at no cost.</strong><br>Your portal is fully covered by your linked provider\'s Aegis subscription.</div></div>
                <div v-if="user?.linked_provider_name" style="background:var(--icon-bg-gold);border:1px solid var(--badge-border-gold);border-radius:var(--radius);padding:16px 18px;display:flex;align-items:center;gap:14px">
                  <div class="stat-chip-icon" style="width:40px;height:40px;flex-shrink:0;background:var(--gold-dark);color:#fff"><AegisIcon name="user" :size="16" /></div>
                  <div style="flex:1"><div style="font-size:11px;text-transform:uppercase;color:var(--text-3);margin-bottom:3px">Your Linked Provider</div><div style="font-family:var(--font-serif);font-size:17px;font-weight:700">{{ user.linked_provider_name }}</div></div>
                  <a :href="route(\'cs.providers.index\')" class="btn btn-outline btn-sm"><AegisIcon name="external-link" :size="13" /> View Provider</a>
                </div>
              </div>
              <div v-else>
                <div class="alert alert-info"><div class="alert-icon"><AegisIcon name="credit-card" :size="16" /></div><div class="alert-content" style="font-size:13px">To cancel or change your Business CS plan, contact <a href="mailto:support@maatpracticefirm.com" style="color:var(--gold-dark)">support@maatpracticefirm.com</a></div></div>
              </div>
            </div>
          </div>
        </div>
        <div v-show="section === \'danger\'" class="settings-panel">
          <SettingsDangerZone title="Account and Service Changes" pause-label="Pause Stewardship" pause-desc="Temporarily suspend your stewardship duties. Your practitioners will be notified." deactivate-label="Deactivate Account" deactivate-desc="Permanently deactivate your Continuity Steward account." deactivate-button-label="Deactivate" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from \'vue\';
import { useToast } from \'@/composables/useToast\';
import AppLayout              from \'@/layouts/AppLayout.vue\';
import SettingsAccount        from \'@/components/settings/SettingsAccount.vue\';
import SettingsSecurity       from \'@/components/settings/SettingsSecurity.vue\';
import SettingsNotifications  from \'@/components/settings/SettingsNotifications.vue\';
import SettingsAppearance     from \'@/components/settings/SettingsAppearance.vue\';
import SettingsMessaging      from \'@/components/settings/SettingsMessaging.vue\';
import SettingsEmailPrefs     from \'@/components/settings/SettingsEmailPrefs.vue\';
import SettingsDangerZone     from \'@/components/settings/SettingsDangerZone.vue\';

const props = defineProps({
  user:         { type: Object,  default: () => ({}) },
  meta:         { type: Object,  default: () => ({}) },
  mfaEnabled:   { type: Boolean, default: false },
  sessions:     { type: Array,   default: () => [] },
  subscription: { type: Object,  default: () => ({}) },
});

const toast = useToast();
const section = ref(\'profile\');
const isInvitedCs = computed(() => !props.user?.cs_account_type || props.user.cs_account_type === \'invited\' || !!props.user.linked_provider_id);
const displayName = computed(() => props.user?.display_name || \'Marcus Webb\');
const initials    = computed(() => displayName.value.split(\' \').map(p => p[0]).join(\'\').slice(0, 2).toUpperCase());

const i = \'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"\';
const nav = [
  { group: \'Account\', items: [
    { key: \'profile\',    label: \'Profile & Identity\',  icon: `<svg viewBox="0 0 24 24" ${i}><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>` },
    { key: \'account\',    label: \'Account & Login\',     icon: `<svg viewBox="0 0 24 24" ${i}><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>` },
    { key: \'security\',   label: \'Security & 2FA\',      icon: `<svg viewBox="0 0 24 24" ${i}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
  ]},
  { group: \'Communications\', items: [
    { key: \'notifications\', label: \'Notifications\',     icon: `<svg viewBox="0 0 24 24" ${i}><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>` },
    { key: \'messaging\',     label: \'Messaging\',         icon: `<svg viewBox="0 0 24 24" ${i}><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>` },
    { key: \'email-prefs\',   label: \'Email Preferences\', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>` },
  ]},
  { group: \'Steward Role\', items: [
    { key: \'cs-steward\',  label: \'CS Role Settings\',      icon: `<svg viewBox="0 0 24 24" ${i}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
    { key: \'documents-s\', label: \'Document Vault Access\', icon: `<svg viewBox="0 0 24 24" ${i}><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>` },
    { key: \'privacy\',     label: \'Privacy & Visibility\',  icon: `<svg viewBox="0 0 24 24" ${i}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
  ]},
  { group: \'Platform\', items: [
    { key: \'appearance\', label: \'Appearance & Timezone\', icon: `<svg viewBox="0 0 24 24" ${i}><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>` },
    { key: \'billing\',    label: \'Subscription & Plan\',  icon: `<svg viewBox="0 0 24 24" ${i}><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>` },
  ]},
  { group: \'Account Changes\', items: [
    { key: \'danger\', label: \'Account Actions\', danger: true, icon: `<svg viewBox="0 0 24 24" ${i}><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>` },
  ]},
];

const csNotifCategories = [
  { key: \'incident\',    label: \'Incident Triggers\',    desc: \'When a critical incident is reported for one of my assigned providers\',    push: true,  email: true,  sms: true,  inapp: true  },
  { key: \'plan\',        label: \'Plan Changes\',          desc: \'When a practitioner updates or amends their continuity plan\',               push: true,  email: true,  sms: false, inapp: true  },
  { key: \'attestation\', label: \'Attestation Requests\', desc: \'When a pending plan attestation requires my sign-off\',                      push: true,  email: true,  sms: true,  inapp: true  },
  { key: \'agreements\',  label: \'Agreement Alerts\',     desc: \'Signing requests, renewals, and expiry warnings on stewardship agreements\', push: true,  email: true,  sms: false, inapp: true  },
  { key: \'messages\',    label: \'New Messages\',         desc: \'From assigned practitioners or Aegis support\',                              push: true,  email: false, sms: false, inapp: true  },
  { key: \'docs\',        label: \'Document Access\',      desc: \'When vault documents are accessed on behalf of a provider I steward\',       push: false, email: true,  sms: false, inapp: true  },
  { key: \'coverage\',    label: \'Coverage Activation\',  desc: \'When continuity coverage for one of my providers is formally activated\',    push: true,  email: true,  sms: true,  inapp: true  },
  { key: \'checkin\',     label: \'Check-in Reminders\',   desc: \'Scheduled reminders to review and certify practitioner plans\',             push: true,  email: true,  sms: false, inapp: true  },
];

const csRolePrefs      = reactive({ showOnProfile: true, vaultAccess: true });
const vaultNotifyPrefs = reactive({ notifyAccess: true, notifyUnlock: true });
const csPrivacy        = reactive({ level: \'public\', search: true, location: true, creds: true });

const privacyLevels = [
  { key: \'public\',  name: \'Public\',   desc: \'Visible to all practitioners on Aegis\', icon: \'eye\'  },
  { key: \'network\', name: \'Network\',  desc: \'Practitioners I am connected with only\', icon: \'link\' },
  { key: \'private\', name: \'Unlisted\', desc: \'Not shown in search — invite only\',      icon: \'lock\' },
];
</script>

<style scoped>
.settings-layout { display: grid; grid-template-columns: 240px 1fr; gap: 22px; align-items: start; padding: 0 var(--page-x, 24px) 40px; }
.settings-sidebar { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); position: sticky; top: 80px; }
.settings-sidebar-header { padding: 18px 20px; border-bottom: 1px solid var(--border); background: var(--surface-2); display: flex; align-items: center; gap: 10px; }
.settings-sidebar-header-icon { width: 34px; height: 34px; border-radius: var(--radius); background: var(--icon-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.settings-sidebar-header h3 { font-family: var(--font-serif); font-size: 15px; font-weight: 700; color: var(--text); }
.settings-nav-group { padding: 6px 0; border-bottom: 1px solid var(--border); }
.settings-nav-group:last-child { border-bottom: none; }
.settings-nav-label { font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--text-4); padding: 4px 14px; }
.settings-nav-item { width: 100%; display: flex; align-items: center; gap: 8px; padding: 7px 14px; font-size: 13px; color: var(--text-2); cursor: pointer; border: none; background: none; border-left: 3px solid transparent; transition: all var(--transition); text-align: left; }
.settings-nav-item:hover { background: var(--surface-2); color: var(--text); }
.settings-nav-item.active { background: var(--icon-bg-gold); color: var(--gold-dark); border-left-color: var(--gold-dark); font-weight: 600; }
.s-nav-icon { width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.s-nav-icon :deep(svg) { width: 15px; height: 15px; }
.settings-content { min-width: 0; }
.settings-panel { display: block; }
.toggle-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 13px 0; border-bottom: 1px solid var(--border); }
.toggle-row:last-of-type { border-bottom: none; }
.toggle-info { flex: 1; min-width: 0; }
.toggle-label { font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 2px; }
.toggle-desc { font-size: 12px; color: var(--text-3); line-height: 1.5; }
.toggle-row .toggle { flex-shrink: 0; margin-top: 2px; }
.privacy-levels { display: flex; gap: 10px; }
.privacy-level-btn { flex: 1; padding: 14px 10px; border: 1px solid var(--border); border-radius: var(--radius); text-align: center; cursor: pointer; transition: all var(--transition); background: var(--surface); }
.privacy-level-btn.active { border-color: var(--gold-dark); background: var(--icon-bg-gold); }
.privacy-level-icon { width: 36px; height: 36px; border-radius: var(--radius); margin: 0 auto 8px; display: flex; align-items: center; justify-content: center; }
.privacy-level-name { font-size: 13px; font-weight: 700; color: var(--text); }
.privacy-level-desc { font-size: 11px; color: var(--text-3); margin-top: 2px; }
@media (max-width: 1000px) { .settings-layout { grid-template-columns: 1fr; } .settings-sidebar { position: static; } }
</style>
