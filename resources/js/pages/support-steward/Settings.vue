<template>
  <AppLayout :user="user" portal="support_steward" activePage="settings" pageTitle="Settings">
    <AegisHeroBanner eyebrow="Support Steward" title="Support Steward Settings" quiet>
      <template #actions>
        <a :href="route(\'ss.activity\') + \'?event_type=account\'" class="btn-hero-ghost is-on-light">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
      </template>
    </AegisHeroBanner>
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
              <span class="s-nav-icon"><AegisIcon :name="it.icon" :size="15" /></span>{{ it.label }}
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
              <a :href="route(\'ss.profile.edit\')" class="btn btn-primary btn-sm"><AegisIcon name="edit" :size="13" /> Edit Full Profile</a>
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
                    <div style="font-size:12px;color:var(--text-4);margin-top:4px;display:flex;gap:14px;flex-wrap:wrap">
                      <span style="display:flex;align-items:center;gap:4px"><AegisIcon name="mail" :size="12" /> {{ user?.email ?? \'\'  }}</span>
                    </div>
                  </div>
                  <span class="badge badge-blue">SS</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-show="section === \'account\'" class="settings-panel">
          <SettingsAccount
            :user="user"
            :sessions="sessions"
            update-password-route="ss.settings.password"
            update-account-route="ss.settings.account"
            revoke-session-route="ss.settings.sessions.revoke"
            revoke-all-route="ss.settings.sessions.revoke-all"
          />
        </div>

        <div v-show="section === \'security\'" class="settings-panel">
          <SettingsSecurity enable-mfa-route="ss.settings.mfa.enable" disable-mfa-route="ss.settings.mfa.disable" verify-mfa-route="ss.settings.mfa.verify"
            backup-codes-route="ss.settings.mfa.backup-codes"
            enable-email-mfa-route="ss.settings.mfa.enable-email"
            verify-email-mfa-route="ss.settings.mfa.verify-email"
            :mfa-method="mfaMethod" :mfa-enabled="mfaEnabled"
            :user-email="user?.email ?? ''" />
        </div>

        <div v-show="section === \'notifications\'" class="settings-panel">
          <SettingsNotifications update-route="ss.settings.notifications"
            :saved-prefs="meta?.notify_prefs ?? {}"
            :saved-categories="meta?.notify_categories ?? []" subtitle="Delivery channels unified across portals. Per-category preferences apply to your Support Steward role." :notif-categories="ssNotifCategories" />
        </div>

        <div v-show="section === \'messaging\'" class="settings-panel">
          <SettingsMessaging update-route="ss.settings.messaging" messages-route="ss.messages.index" subtitle="Control who can reach you and how you appear to assigned practitioners" :meta="meta" />
        </div>

        <div v-show="section === \'email-prefs\'" class="settings-panel">
          <SettingsEmailPrefs update-route="ss.settings.email-prefs" activity-label="Assignment Activity Summary" activity-desc="Digest of task updates, role changes, and document access events" :meta="meta" />
        </div>

        <div v-show="section === \'ss-steward\'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="users" :size="16" /></div>
                <div><div class="card-title">Support Steward Settings</div><div class="card-subtitle">Preferences for your support role and responsibilities</div></div>
              </div>
            </div>
            <div class="card-body">
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Show Name on Provider Public Profile</div><div class="toggle-desc">Allow your name to appear as "Support Steward" on your providers\' public Aegis pages</div></div><button type="button" class="toggle" :class="{ on: ssRolePrefs.showOnProfile }" @click="ssRolePrefs.showOnProfile = !ssRolePrefs.showOnProfile" :aria-pressed="ssRolePrefs.showOnProfile"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px"><button type="button" class="btn btn-primary" @click="toast.success(\'SS settings saved.\')"><AegisIcon name="check" :size="16" /> Save SS Settings</button></div>
            </div>
          </div>
        </div>

        <div v-show="section === \'agreements-s\'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="file-text" :size="16" /></div>
                <div><div class="card-title">Agreements &amp; Attestation</div><div class="card-subtitle">Your active steward agreements and attestation status</div></div>
              </div>
              <a :href="route(\'ss.documents.index\')" class="btn btn-outline btn-sm"><AegisIcon name="file-text" :size="13" /> View Agreements</a>
            </div>
            <div class="card-body">
              <div class="section-label">Agreement Status</div>
              <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid var(--border)">
                <div><div style="font-size:13px;font-weight:700;color:var(--text)">Support Steward Agreement</div><div style="font-size:12px;color:var(--text-3);margin-top:2px">Dr. Sarah Johnson — Signed Feb 1, 2026 · Annual re-attestation required</div></div>
                <span class="badge badge-green" style="flex-shrink:0">Signed</span>
              </div>
              <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0">
                <div><div style="font-size:13px;font-weight:700;color:var(--text)">Annual Attestation</div><div style="font-size:12px;color:var(--text-3);margin-top:2px">Due June 30, 2026 — confirm tasks and responsibilities are current</div></div>
                <span class="badge badge-orange" style="flex-shrink:0">Pending</span>
              </div>
              <div class="section-label" style="margin-top:20px">Notifications</div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Expiry Reminder (30 days before)</div><div class="toggle-desc">Notify me 30 days before any agreement or attestation is due</div></div><button type="button" class="toggle" :class="{ on: agreementPrefs.expiryReminder }" @click="agreementPrefs.expiryReminder = !agreementPrefs.expiryReminder" :aria-pressed="agreementPrefs.expiryReminder"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Notify Me When New Agreements Arrive</div><div class="toggle-desc">Alert me when a practitioner sends a new or amended agreement for my signature</div></div><button type="button" class="toggle" :class="{ on: agreementPrefs.incomingNotify }" @click="agreementPrefs.incomingNotify = !agreementPrefs.incomingNotify" :aria-pressed="agreementPrefs.incomingNotify"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-outline btn-sm" @click="toast.success(\'Preferences saved.\')"><AegisIcon name="check" :size="13" /> Save Preferences</button>
                <a :href="route(\'ss.documents.index\')" class="btn btn-primary"><AegisIcon name="edit" :size="13" /> Complete Attestation</a>
              </div>
            </div>
          </div>
        </div>

        <div v-show="section === \'privacy\'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="shield" :size="16" /></div>
                <div><div class="card-title">Privacy &amp; Visibility</div><div class="card-subtitle">Support Steward profiles are private by design — not publicly searchable on Aegis</div></div>
              </div>
            </div>
            <div class="card-body">
              <div class="alert alert-info" style="margin-bottom:16px"><div class="alert-icon"><AegisIcon name="info" :size="16" /></div><div class="alert-content" style="font-size:12px">Your profile is only visible to the practitioners who have designated you as their Support Steward. It does not appear in any public directory or search.</div></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Show Location to My Practitioners</div><div class="toggle-desc">Display your general city/region to practitioners you support</div></div><button type="button" class="toggle" :class="{ on: ssPrivacy.location }" @click="ssPrivacy.location = !ssPrivacy.location" :aria-pressed="ssPrivacy.location"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Show Contact Details to My Practitioners</div><div class="toggle-desc">Let your assigned practitioners see your phone and email</div></div><button type="button" class="toggle" :class="{ on: ssPrivacy.contact }" @click="ssPrivacy.contact = !ssPrivacy.contact" :aria-pressed="ssPrivacy.contact"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px"><button type="button" class="btn btn-primary" @click="toast.success(\'Privacy settings saved.\')"><AegisIcon name="check" :size="13" /> Save Privacy Settings</button></div>
            </div>
          </div>
        </div>

        <div v-show="section === \'appearance\'" class="settings-panel">
          <SettingsAppearance update-route="ss.settings.appearance" :meta="meta" />
        </div>

        <div v-show="section === \'billing\'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="star" :size="16" /></div>
                <div><div class="card-title">Subscription &amp; Plan</div><div class="card-subtitle">Your access is covered by your linked provider</div></div>
              </div>
              <span class="badge badge-green" style="font-size:12px;display:inline-flex;align-items:center;gap:5px"><AegisIcon name="check" :size="12" /> Active — No Cost</span>
            </div>
            <div class="card-body">
              <div class="alert alert-info" style="margin-bottom:18px"><div class="alert-icon"><AegisIcon name="shield" :size="18" /></div><div class="alert-content"><strong>You have full Support Steward access — at no cost.</strong><br>Your portal is fully covered by your linked provider\'s Aegis subscription. You do not manage or pay for a separate plan.</div></div>
              <div v-if="user?.linked_provider_name" style="background:var(--icon-bg-gold);border:1px solid var(--badge-border-gold);border-radius:var(--radius);padding:16px 18px;display:flex;align-items:center;gap:14px">
                <div class="stat-chip-icon" style="width:40px;height:40px;flex-shrink:0;background:var(--gold-dark);color:var(--text-inverted)"><AegisIcon name="user" :size="16" /></div>
                <div style="flex:1"><div style="font-size:11px;text-transform:uppercase;color:var(--text-3);margin-bottom:3px">Your Linked Provider</div><div style="font-family:var(--font-serif);font-size:17px;font-weight:700">{{ user.linked_provider_name }}</div></div>
                <a :href="route(\'ss.providers.index\')" class="btn btn-outline btn-sm"><AegisIcon name="external-link" :size="13" /> View Provider</a>
              </div>
            </div>
          </div>
        </div>

        <div v-show="section === \'danger\'" class="settings-panel">
          <SettingsDangerZone
            delete-route="ss.settings.account.delete"
            pause-route="ss.settings.account.pause"
            resume-route="ss.settings.account.resume"
            export-route="ss.settings.account.export"
            :is-paused="isAccountPaused"
          />
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
  user:       { type: Object,  default: () => ({}) },
  meta:       { type: Object,  default: () => ({}) },
  mfaEnabled: { type: Boolean, default: false },
});

const toast = useToast();
// Sessions — passed from controller via props
const sessions = computed(() => props.sessions ?? []);
const section = ref(\'profile\');
const displayName = computed(() => props.user?.display_name || \'Linda Torres\');
const initials    = computed(() => displayName.value.split(\' \').map(p => p[0]).join(\'\').slice(0, 2).toUpperCase());

const i = \'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"\';
const nav = [
  { group: \'Account\', items: [
    { key: \'profile\',  label: \'Profile & Identity\', icon: 'lock' },
    { key: \'security\', label: \'Security & 2FA\',     icon: 'bell' },
    { key: \'messaging\',     label: \'Messaging\',         icon: 'mail' },
  ]},
  { group: \'Steward Role\', items: [
    { key: \'ss-steward\',   label: \'SS Role Settings\',      icon: 'file-text' },
    { key: \'privacy\',      label: \'Privacy & Visibility\',  icon: 'settings' },
    { key: \'billing\',    label: \'Subscription & Plan\',  icon: 'alert-triangle' },
  ]},
];

const ssNotifCategories = [
  { key: \'incidents\',   label: \'Incident Reports\',     desc: \'When a critical incident is reported for a practitioner I support\',   push: true,  email: true, inapp: true  },
  { key: \'attestation\', label: \'Attestation Requests\', desc: \'When a practitioner needs my attestation on their plan\',              push: true,  email: true, inapp: true  },
  { key: \'changes\',     label: \'Change Requests\',      desc: \'When a practitioner requests a plan update I need to act on\',          push: true,  email: true, inapp: true  },
  { key: \'coverage\',    label: \'Coverage Activation\',  desc: \'When continuity coverage for a provider I support is activated\',       push: true,  email: true, inapp: true  },
  { key: \'roles\',       label: \'Role Changes\',         desc: \'When my steward role or permissions are updated\',                      push: true,  email: true, inapp: true  },
  { key: \'docs\',        label: \'Document Access\',      desc: \'When administrative documents are accessed on behalf of a provider\',   push: false, email: true, inapp: true  },
  { key: \'messages\',    label: \'New Messages\',         desc: \'From assigned practitioners or Aegis support\',                         push: true,  email: false, inapp: true  },
  { key: \'network\',     label: \'Network Updates\',      desc: \'New connection requests and approvals within the Aegis network\',        push: false, email: true, inapp: true  },
];

const ssRolePrefs    = reactive({ showOnProfile: true });
const ssPrivacy      = reactive({ location: true, contact: true });
const agreementPrefs = reactive({ expiryReminder: true, incomingNotify: true });
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
@media (max-width: 1000px) { .settings-layout { grid-template-columns: 1fr; } .settings-sidebar { position: static; } }
</style>
