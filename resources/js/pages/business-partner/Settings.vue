<template>
  <AppLayout :user="user" portal="business_partner" activePage="settings" pageTitle="Settings">
    <AegisHeroBanner eyebrow="Business Partner" title="Business Account" quiet />
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
            <div class="alert-content"><strong>Unified identity across all portals.</strong> Your display name, email, and phone are shared across every portal you have access to.</div>
          </div>
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="briefcase" :size="16" /></div>
                <div><div class="card-title">Profile Summary</div><div class="card-subtitle">Your public-facing identity on Aegis</div></div>
              </div>
              <a :href="route(\'bp.profile.edit\')" class="btn btn-primary btn-sm"><AegisIcon name="edit" :size="13" /> Edit Full Profile</a>
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
                  <span class="badge badge-gold">BP</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-show="section === \'account\'" class="settings-panel">
          <SettingsAccount
            :user="user"
            :sessions="sessions"
            update-password-route="bp.settings.password"
            revoke-session-route="bp.settings.sessions.revoke"
            revoke-all-route="bp.settings.sessions.revoke-all"
          />
        </div>

        <div v-show="section === \'security\'" class="settings-panel">
          <SettingsSecurity enable-mfa-route="bp.settings.mfa.enable" disable-mfa-route="bp.settings.mfa.disable" verify-mfa-route="bp.settings.mfa.verify" :mfa-enabled="mfaEnabled" />
        </div>

        <div v-show="section === \'notifications\'" class="settings-panel">
          <SettingsNotifications update-route="bp.settings.notifications"
            :saved-prefs="meta?.notify_prefs ?? {}"
            :saved-categories="meta?.notify_categories ?? []" subtitle="Choose how you receive alerts about your business activity on Aegis." :notif-categories="bpNotifCategories" />
        </div>

        <div v-show="section === \'messaging\'" class="settings-panel">
          <SettingsMessaging update-route="bp.settings.messaging"
            :meta="meta" messages-route="bp.messages.index" subtitle="Control how practitioners can reach your business on Aegis" :who-options="[\'Practitioners I\'m connected with\']" :meta="meta" />
        </div>

        <div v-show="section === \'email-prefs\'" class="settings-panel">
          <SettingsEmailPrefs update-route="bp.settings.email-prefs" activity-label="Business Activity Summary" activity-desc="Digest of proposal responses, contract milestones, and invoice activity" :meta="meta" />
        </div>

        <!-- BP BUSINESS ACCOUNT SETTINGS -->
        <div v-show="section === \'bp-business\'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="briefcase" :size="16" /></div>
                <div><div class="card-title">Business Account Settings</div><div class="card-subtitle">Control how your business appears and operates on Aegis</div></div>
              </div>
            </div>
            <div class="card-body">
              <div class="form-group" style="margin-bottom:14px">
                <label class="form-label">Business Type</label>
                <select class="form-select" v-model="bpBizPrefs.businessType">
                  <option value="agency">Agency / Organization</option>
                  <option value="freelancer">Freelancer / Individual</option>
                </select>
              </div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Visible in Provider Network</div><div class="toggle-desc">Allow practitioners to discover your business in the Aegis network search</div></div><button type="button" class="toggle" :class="{ on: bpBizPrefs.networkVisible }" @click="bpBizPrefs.networkVisible = !bpBizPrefs.networkVisible" :aria-pressed="bpBizPrefs.networkVisible"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Accepting Job Proposals</div><div class="toggle-desc">Allow practitioners to send direct job proposals to your business</div></div><button type="button" class="toggle" :class="{ on: bpBizPrefs.proposalsOpen }" @click="bpBizPrefs.proposalsOpen = !bpBizPrefs.proposalsOpen" :aria-pressed="bpBizPrefs.proposalsOpen"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px"><button type="button" class="btn btn-primary" @click="toast.success(\'Business settings saved.\')"><AegisIcon name="check" :size="16" /> Save Business Settings</button></div>
            </div>
          </div>
        </div>

        <!-- BP PAYOUT / STRIPE CONNECT -->
        <div v-show="section === \'bp-payout\'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="credit-card" :size="16" /></div>
                <div><div class="card-title">Payout &amp; Stripe Connect</div><div class="card-subtitle">Manage your bank connection and payout schedule</div></div>
              </div>
            </div>
            <div class="card-body">
              <div class="stripe-status" :class="user?.stripe_account_id && !user.stripe_account_id.startsWith(\'acct_demo\') ? \'is-connected\' : \'is-disconnected\'">
                <div style="flex:1">
                  <div style="font-size:14px;font-weight:700;color:var(--text)">Stripe Connect</div>
                  <div style="font-size:12px;color:var(--text-3);margin-top:2px">{{ user?.stripe_account_id && !user.stripe_account_id.startsWith(\'acct_demo\') ? \'Payouts go to your connected bank account within 2 business days.\' : \'Connect Stripe to receive payouts from practitioners.\' }}</div>
                </div>
                <span class="badge" :class="user?.stripe_account_id && !user.stripe_account_id.startsWith(\'acct_demo\') ? \'badge-green\' : \'badge-gray\'">{{ user?.stripe_account_id && !user.stripe_account_id.startsWith(\'acct_demo\') ? \'Connected\' : \'Not Connected\' }}</span>
                <a v-if="user?.stripe_account_id && !user.stripe_account_id.startsWith(\'acct_demo\')" :href="route(\'bp.settings.stripe.dashboard\')" class="btn btn-outline btn-sm" target="_blank">Manage</a>
                <a v-else :href="route(\'bp.settings.stripe.connect\')" class="btn btn-primary btn-sm">Connect Stripe</a>
              </div>
              <div class="form-group" style="margin-bottom:14px;margin-top:18px">
                <label class="form-label">Payout Schedule</label>
                <select class="form-select" v-model="bpPayoutPrefs.schedule">
                  <option value="daily">Daily</option>
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                </select>
                <div class="form-hint">Controls how often accumulated earnings are swept to your bank.</div>
              </div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px"><button type="button" class="btn btn-primary" @click="toast.success(\'Payout settings saved.\')"><AegisIcon name="check" :size="16" /> Save Payout Settings</button></div>
            </div>
          </div>
        </div>

        <!-- PRIVACY -->
        <div v-show="section === \'privacy\'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="shield" :size="16" /></div>
                <div><div class="card-title">Privacy &amp; Visibility</div><div class="card-subtitle">Control how your business profile appears to practitioners on Aegis</div></div>
              </div>
            </div>
            <div class="card-body">
              <div style="margin-bottom:16px">
                <div class="form-label" style="margin-bottom:10px">Overall Profile Visibility Level</div>
                <div class="privacy-levels">
                  <div v-for="lvl in privacyLevels" :key="lvl.key" class="privacy-level-btn" :class="{ active: bpPrivacy.level === lvl.key }" @click="bpPrivacy.level = lvl.key">
                    <div class="privacy-level-icon" style="background:var(--surface-2);color:var(--text-2)"><AegisIcon :name="lvl.icon" :size="18" /></div>
                    <div class="privacy-level-name">{{ lvl.name }}</div>
                    <div class="privacy-level-desc">{{ lvl.desc }}</div>
                  </div>
                </div>
              </div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Appear in Business Partner Search</div><div class="toggle-desc">Let practitioners find your business when searching for services</div></div><button type="button" class="toggle" :class="{ on: bpPrivacy.search }" @click="bpPrivacy.search = !bpPrivacy.search" :aria-pressed="bpPrivacy.search"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Show Location on Profile</div><div class="toggle-desc">Display your business city/region to Practitioner Partners</div></div><button type="button" class="toggle" :class="{ on: bpPrivacy.location }" @click="bpPrivacy.location = !bpPrivacy.location" :aria-pressed="bpPrivacy.location"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Services and Fees Visible to Practitioner Partners</div><div class="toggle-desc">Display your Services and Fees to Practitioner Partners</div></div><button type="button" class="toggle" :class="{ on: bpPrivacy.rates }" @click="bpPrivacy.rates = !bpPrivacy.rates" :aria-pressed="bpPrivacy.rates"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px"><button type="button" class="btn btn-primary" @click="toast.success(\'Privacy settings saved.\')"><AegisIcon name="check" :size="13" /> Save Privacy Settings</button></div>
            </div>
          </div>
        </div>

        <div v-show="section === \'appearance\'" class="settings-panel">
          <SettingsAppearance update-route="bp.settings.appearance" :meta="meta" />
        </div>

        <!-- BILLING — BP has its own subscription -->
        <div v-show="section === \'billing\'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="star" :size="16" /></div>
                <div><div class="card-title">Subscription &amp; Plan</div><div class="card-subtitle">Your current Aegis Business Partner plan</div></div>
              </div>
              <span class="badge badge-gold" style="font-size:12px">BP Professional</span>
            </div>
            <div class="card-body">
              <div style="background:var(--icon-bg-gold);border:1px solid var(--badge-border-gold);border-radius:var(--radius);padding:14px 18px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between">
                <div>
                  <div style="font-family:var(--font-serif);font-size:18px;font-weight:700;color:var(--text)">Business Partner Professional — ${{ billingAnnual ? 49 : 59 }}/mo</div>
                  <div style="font-size:13px;color:var(--text-3);margin-top:3px">{{ subscription?.billing_meta ?? \'Billing cycle: Feb 1 – Mar 1, 2026\' }}</div>
                </div>
                <button type="button" class="btn btn-outline btn-sm" @click="modals.cancelSub = true">Cancel Plan</button>
              </div>
              <div style="display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:18px;font-size:13px;font-weight:600;color:var(--text-3)">
                <span :style="billingAnnual ? \'\'  : \'color:var(--text-2)\'">Monthly</span>
                <button type="button" class="toggle" :class="{ on: billingAnnual }" @click="billingAnnual = !billingAnnual" :aria-pressed="billingAnnual"></button>
                <span :style="billingAnnual ? \'color:var(--text-2)\' : \'\'">Annual <span style="background:var(--green-light);color:var(--green-dark);font-size:10px;font-weight:700;padding:2px 7px;border-radius:var(--radius-full);">Save 17%</span></span>
              </div>
              <div style="border:1px solid var(--gold-dark);border-radius:var(--radius-lg);padding:20px;text-align:center;background:var(--icon-bg-gold);max-width:460px;margin:0 auto 20px">
                <div style="display:inline-flex;align-items:center;gap:5px;background:var(--gold-dark);color:var(--text-inverted);font-size:10px;font-weight:700;padding:3px 10px;border-radius:var(--radius-full);margin-bottom:10px"><AegisIcon name="check" :size="11" /> CURRENT PLAN</div>
                <div style="font-family:var(--font-serif);font-size:18px;font-weight:700;color:var(--text)">Business Partner Professional</div>
                <div style="font-size:26px;font-weight:700;color:var(--gold-dark);margin:8px 0 4px">${{ billingAnnual ? 49 : 59 }}<span style="font-size:13px;color:var(--text-3);font-weight:600">/mo</span></div>
                <div style="font-size:11px;color:var(--text-4);margin-bottom:12px">{{ billingAnnual ? \'billed $588/yr\' : \'or $588/yr (save 17%)\' }}</div>
              </div>
              <div class="section-label">What\'s included</div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:12.5px;color:var(--text-2)">
                <span style="display:inline-flex;align-items:center;gap:6px"><AegisIcon name="check" :size="12" /> Marketplace &amp; provider directory</span>
                <span style="display:inline-flex;align-items:center;gap:6px"><AegisIcon name="check" :size="12" /> Job postings &amp; applicant management</span>
                <span style="display:inline-flex;align-items:center;gap:6px"><AegisIcon name="check" :size="12" /> Contract creation &amp; tracking</span>
                <span style="display:inline-flex;align-items:center;gap:6px"><AegisIcon name="check" :size="12" /> Stripe Connect payouts</span>
                <span style="display:inline-flex;align-items:center;gap:6px"><AegisIcon name="check" :size="12" /> Milestones &amp; invoicing</span>
                <span style="display:inline-flex;align-items:center;gap:6px"><AegisIcon name="check" :size="12" /> Secure encrypted messaging</span>
              </div>
            </div>
          </div>
          <AegisModal v-model="modals.cancelSub" title="Cancel Subscription?" size="md">
            <p style="font-size:14px;color:var(--text-2);margin-bottom:14px">Your plan will remain active until the end of the current billing cycle. After that your account will be deactivated.</p>
            <div class="form-group"><label class="form-label">Reason for cancelling</label><select class="form-select" v-model="cancelForm.reason"><option value="expensive">Too expensive</option><option value="inactive">No longer active on Aegis</option><option value="switching">Switching platform</option><option value="other">Other</option></select></div>
            <template #footer>
              <button type="button" class="btn btn-outline btn-sm" @click="modals.cancelSub = false">Keep Plan</button>
              <button type="button" class="btn btn-danger btn-sm" @click="modals.cancelSub = false; toast.info(\'Subscription cancelled.\')">Cancel Subscription</button>
            </template>
          </AegisModal>
        </div>

        <div v-show="section === \'danger\'" class="settings-panel">
          <SettingsDangerZone
            title="Account Closure &amp; Data Management"
            pause-label="Pause Account"
            pause-desc="Temporarily suspend your business account. All active contracts will be flagged."
            deactivate-label="Deactivate Account"
            deactivate-desc="Permanently deactivate this Business Partner account. All active contracts will be flagged for termination."
            deactivate-button-label="Deactivate Account"
            :show-transfer="true"
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
  user:         { type: Object,  default: () => ({}) },
  meta:         { type: Object,  default: () => ({}) },
  mfaEnabled:   { type: Boolean, default: false },
  sessions:     { type: Array,   default: () => [] },
  subscription: { type: Object,  default: () => ({}) },
});

const toast = useToast();
const section     = ref(\'profile\');
const billingAnnual = ref(false);
const displayName = computed(() => props.user?.display_name || \'TechCare Solutions\');
const initials    = computed(() => displayName.value.split(\' \').map(p => p[0]).join(\'\').slice(0, 2).toUpperCase());

const i = \'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"\';
const nav = [
  { group: \'Account\', items: [
    { key: \'profile\',  label: \'Profile & Identity\', icon: 'lock' },
    { key: \'security\', label: \'Security & 2FA\',     icon: 'bell' },
    { key: \'messaging\',     label: \'Messaging\',         icon: 'mail' },
  ]},
  { group: \'Business\', items: [
    { key: \'bp-business\', label: \'Business Account\',    icon: 'credit-card' },
    { key: \'privacy\',     label: \'Privacy & Visibility\',  icon: 'settings' },
    { key: \'billing\',    label: \'Subscription & Plan\',  icon: 'alert-triangle' },
  ]},
];

const bpNotifCategories = [
  { key: \'proposals\',  label: \'Job Proposal Activity\',  desc: \'When a practitioner responds to, accepts, or declines a proposal\',          push: true,  email: true,  sms: false, inapp: true  },
  { key: \'milestones\', label: \'Contract Milestones\',    desc: \'When a contract milestone is reached, due, or requires action\',               push: true,  email: true,  sms: false, inapp: true  },
  { key: \'payments\',   label: \'Invoice & Payments\',     desc: \'Payment confirmations, failed charges, and upcoming invoices\',                push: false, email: true,  sms: false, inapp: true  },
  { key: \'messages\',   label: \'New Messages\',           desc: \'When a practitioner or Aegis support sends you a message\',                    push: true,  email: false, sms: false, inapp: true  },
  { key: \'agreements\', label: \'Agreement Alerts\',       desc: \'Signing requests, renewals, and expiry warnings on business agreements\',      push: true,  email: true,  sms: false, inapp: true  },
  { key: \'network\',    label: \'Network Updates\',        desc: \'New connection requests and approvals from practitioners\',                    push: false, email: true,  sms: false, inapp: true  },
  { key: \'jobs\',       label: \'Job Posting Activity\',   desc: \'Applications, views, and status changes on your active job postings\',         push: true,  email: true,  sms: false, inapp: true  },
  { key: \'platform\',   label: \'Platform Updates\',       desc: \'New Aegis features and maintenance windows\',                                  push: false, email: false, sms: false, inapp: false },
];

const bpBizPrefs   = reactive({ businessType: \'agency\', networkVisible: true, proposalsOpen: true });
const bpPayoutPrefs = reactive({ schedule: \'weekly\' });
const bpPrivacy    = reactive({ level: \'public\', search: true, location: true, rates: false });
const modals       = reactive({ cancelSub: false });
const cancelForm   = reactive({ reason: \'expensive\' });

const privacyLevels = [
  { key: \'public\',  name: \'Public\',   desc: \'Visible to all Practitioner Partners on Aegis\', icon: \'eye\'  },
  { key: \'network\', name: \'Network\',  desc: \'Only Practitioner Partners I am connected with\', icon: \'link\' },
  { key: \'private\', name: \'Unlisted\', desc: \'Not shown in search — direct link only\',          icon: \'lock\' },
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
.stripe-status { display: flex; align-items: center; gap: 14px; padding: 16px; border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 16px; }
.stripe-status.is-connected    { border-color: var(--green-dark); background: var(--green-light); }
.stripe-status.is-disconnected { border-color: var(--border-dark); background: var(--surface-2); }
@media (max-width: 1000px) { .settings-layout { grid-template-columns: 1fr; } .settings-sidebar { position: static; } }
</style>
