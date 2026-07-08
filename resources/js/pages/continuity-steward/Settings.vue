<template>
  <AppLayout :user="user" portal="continuity_steward" activePage="settings" pageTitle="Settings">
    <AegisHeroBanner eyebrow="Continuity Steward" title="Continuity Steward Settings" quiet>
      <template #actions>
        <a :href="route(\'cs.activity\') + \'?event_type=account\'" class="btn-hero-ghost is-on-light">
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
              <a :href="route(\'cs.profile.edit\')" class="btn btn-primary btn-sm"><AegisIcon name="edit" :size="13" /> Edit Full Profile</a>
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
            update-account-route="cs.settings.account"
            revoke-session-route="cs.settings.sessions.revoke"
            revoke-all-route="cs.settings.sessions.revoke-all"
          />
        </div>
        <div v-show="section === \'security\'" class="settings-panel">
          <SettingsSecurity enable-mfa-route="cs.settings.mfa.enable" disable-mfa-route="cs.settings.mfa.disable" verify-mfa-route="cs.settings.mfa.verify"
            backup-codes-route="cs.settings.mfa.backup-codes"
            enable-email-mfa-route="cs.settings.mfa.enable-email"
            verify-email-mfa-route="cs.settings.mfa.verify-email"
            :mfa-method="mfaMethod" :mfa-enabled="mfaEnabled"
            :user-email="user?.email ?? ''" />
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
        <!-- BILLING — Business CS only / Invited CS sees no-cost notice -->
        <div v-show="section === \'billing\'" class="settings-panel">

          <!-- INVITED CS -->
          <div v-if="isInvitedCs" class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="star" :size="16" /></div>
                <div><div class="card-title">Subscription &amp; Plan</div><div class="card-subtitle">Your access is covered by your linked provider</div></div>
              </div>
              <span class="badge badge-green" style="font-size:12px;display:inline-flex;align-items:center;gap:5px"><AegisIcon name="check" :size="12" /> Active — No Cost</span>
            </div>
            <div class="card-body">
              <div class="alert alert-info" style="margin-bottom:18px">
                <div class="alert-icon"><AegisIcon name="shield" :size="18" /></div>
                <div class="alert-content"><strong>Full Continuity Steward access — at no cost.</strong><br>Covered by your linked provider\'s Aegis subscription.</div>
              </div>
              <div v-if="user?.linked_provider_name" style="background:var(--icon-bg-gold);border:1px solid var(--badge-border-gold);border-radius:var(--radius);padding:16px 18px;margin-bottom:18px;display:flex;align-items:center;gap:14px">
                <div class="stat-chip-icon" style="width:40px;height:40px;flex-shrink:0;background:var(--gold-dark);color:var(--text-inverted)"><AegisIcon name="user" :size="16" /></div>
                <div style="flex:1">
                  <div style="font-size:11px;text-transform:uppercase;color:var(--text-3);margin-bottom:3px">Your Linked Provider</div>
                  <div style="font-family:var(--font-serif);font-size:17px;font-weight:700;color:var(--text)">{{ user.linked_provider_name }}</div>
                </div>
                <a :href="route(\'cs.providers.index\')" class="btn btn-outline btn-sm"><AegisIcon name="external-link" :size="13" /> View Provider</a>
              </div>
              <div class="alert alert-gold" style="margin:0">
                <div class="alert-icon"><AegisIcon name="star" :size="16" /></div>
                <div class="alert-content" style="font-size:12.5px">Want to serve more practitioners? <strong>Upgrade to Business CS</strong> ($49/mo) to get a public profile and serve up to 40 practitioners. Contact <a href="mailto:support@maatpracticefirm.com" style="color:var(--gold-dark)">support@maatpracticefirm.com</a>.</div>
              </div>
            </div>
          </div>

          <!-- BUSINESS CS: full billing management -->
          <div v-else class="st-card">
            <div class="st-card-head">
              <div class="st-card-head-l">
                <span class="st-card-ico"><AegisIcon name="star" :size="17" /></span>
                <div><div class="st-card-title">Subscription &amp; Plan</div><div class="st-card-sub">Business Continuity Steward</div></div>
              </div>
              <a :href="route(\'cs.settings.billing.portal\')" class="btn btn-outline btn-sm" target="_blank"><AegisIcon name="external-link" :size="13" /> Manage in Stripe</a>
            </div>
            <div class="st-card-body">
              <template v-if="sub.on_grace_period">
                <div class="st-perks-band" style="border-color:var(--orange);background:var(--orange-light);">
                  <AegisIcon name="alert-triangle" :size="16" style="color:var(--orange)" />
                  <div style="font-size:13px;color:var(--text-2)">Subscription cancelled — access ends {{ formatDate(sub.ends_at) }}. <button type="button" class="btn btn-gold btn-sm" @click="resumeCsPlan" :disabled="planBusy">Reactivate</button></div>
                </div>
              </template>
              <template v-else-if="subStatus === \'past_due\'">
                <div class="st-perks-band" style="border-color:var(--red-light);background:var(--surface-2);">
                  <AegisIcon name="alert-triangle" :size="16" style="color:var(--red)" />
                  <div style="font-size:13px;color:var(--text-2)">Payment failed — <a :href="route(\'cs.settings.billing.portal\')" style="color:var(--red)">update your payment method</a>.</div>
                </div>
              </template>
              <div v-if="subStatus !== \'none\'" class="st-current-plan">
                <div class="st-current-meta">Business CS — {{ sub.current_billing === \'annual\' ? \'$35.75/mo (billed $429/yr)\' : \'$49/mo\' }}</div>
                <div v-if="sub.current_period" class="st-current-meta" style="font-size:12px;color:var(--text-3)">Current period: {{ formatDate(sub.current_period.start) }} → {{ formatDate(sub.current_period.end) }}</div>
              </div>
              <div v-if="subStatus !== \'none\'" class="st-cycle-toggle">
                <span :class="{ active: !billingAnnual }">Monthly</span>
                <button type="button" class="toggle" :class="{ on: billingAnnual }" @click="billingAnnual = !billingAnnual" :aria-pressed="billingAnnual"></button>
                <span :class="{ active: billingAnnual }">Annual <span class="st-save-pill">Save ~27%</span></span>
              </div>
              <div v-if="subStatus !== \'none\'" style="margin:12px 0">
                <button v-if="billingAnnual !== (sub.current_billing === \'annual\')" type="button" class="btn btn-gold btn-sm" @click="swapCsPlan" :disabled="planBusy || !csPriceId">
                  Switch to {{ billingAnnual ? \'Annual\' : \'Monthly\' }} billing
                </button>
              </div>
              <div v-if="(subStatus === \'active\' || subStatus === \'trialing\')" style="display:flex;align-items:center;justify-content:space-between;padding-top:8px;border-top:1px solid var(--border);margin-top:8px">
                <div style="font-size:13px;color:var(--text-3)">Need to cancel your Business CS plan?</div>
                <button type="button" class="btn btn-outline btn-sm" style="color:var(--red);border-color:var(--red)" @click="confirmCsCancel = true" :disabled="planBusy">Cancel Plan</button>
              </div>
              <div v-if="stripeInvoices.length > 0">
                <div class="st-divider"></div>
                <div class="st-subhead" style="margin-bottom:12px">Invoice History</div>
                <table class="billing-table">
                  <thead><tr><th>Date</th><th>Amount</th><th>Status</th><th></th></tr></thead>
                  <tbody>
                    <tr v-for="inv in stripeInvoices" :key="inv.id">
                      <td>{{ formatDate(inv.paid_at || inv.created) }}</td>
                      <td>{{ formatCents(inv.amount_cents) }}</td>
                      <td><span v-if="inv.status === \'paid\'" style="color:var(--green);font-weight:600;display:inline-flex;align-items:center;gap:4px"><AegisIcon name="check" :size="13" />Paid</span><span v-else style="color:var(--text-3)">{{ inv.status }}</span></td>
                      <td><a v-if="inv.pdf_url" :href="inv.pdf_url" target="_blank" class="btn btn-ghost btn-xs" data-tooltip="Download PDF"><AegisIcon name="download" :size="14" /></a></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- CS Plan Swap Confirmation -->
          <AegisModal v-model="confirmCsSwap" title="Confirm Billing Change" size="md">
            <div style="margin-bottom:16px">
              <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                <div style="flex:1;padding:12px 14px;border:1px solid var(--border);border-radius:var(--radius);background:var(--surface-2)">
                  <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-3);margin-bottom:4px">Current</div>
                  <div style="font-family:var(--font-serif);font-size:14px;font-weight:700;color:var(--text)">Business CS — {{ sub.current_billing === 'annual' ? 'Annual' : 'Monthly' }}</div>
                  <div style="font-size:12px;color:var(--text-3);margin-top:2px">{{ sub.current_billing === 'annual' ? '$35.75/mo (billed $429/yr)' : '$49/mo' }}</div>
                </div>
                <AegisIcon name="arrow-right" :size="16" style="color:var(--text-3);flex-shrink:0" />
                <div style="flex:1;padding:12px 14px;border:1px solid var(--gold-dark);border-radius:var(--radius);background:var(--icon-bg-gold)">
                  <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-3);margin-bottom:4px">New</div>
                  <div style="font-family:var(--font-serif);font-size:14px;font-weight:700;color:var(--text)">{{ pendingCsSwap.label }}</div>
                  <div style="font-size:12px;color:var(--gold-dark);margin-top:2px;font-weight:600">{{ pendingCsSwap.priceLine }}</div>
                </div>
              </div>
              <div style="padding:12px 14px;border-radius:var(--radius);background:var(--surface-2);border:1px solid var(--border);font-size:13px;color:var(--text-2);line-height:1.6">
                <AegisIcon name="info" :size="13" style="color:var(--gold-dark);vertical-align:middle;margin-right:4px" />
                {{ pendingCsSwap.note }}
              </div>
            </div>
            <template #footer>
              <button type="button" class="btn btn-outline btn-sm" @click="confirmCsSwap = false">Go Back</button>
              <button type="button" class="btn btn-gold btn-sm" @click="doCsSwap" :disabled="planBusy">
                <AegisIcon name="check" :size="13" /> Confirm Change
              </button>
            </template>
          </AegisModal>

          <!-- CS Reactivate Confirmation -->
          <AegisModal v-model="confirmCsResume" title="Reactivate Subscription" size="sm">
            <p style="font-size:14px;color:var(--text-2);margin-bottom:14px">Your <strong>Business CS</strong> subscription will continue as normal. You will be charged on your next billing date.</p>
            <div style="padding:10px 14px;border-radius:var(--radius);background:var(--green-light);border:1px solid var(--green-dark);font-size:13px;color:var(--green-dark)">
              <AegisIcon name="check" :size="13" style="margin-right:4px" /> Your CS portal access will be fully restored immediately.
            </div>
            <template #footer>
              <button type="button" class="btn btn-outline btn-sm" @click="confirmCsResume = false">Cancel</button>
              <button type="button" class="btn btn-gold btn-sm" @click="doCsResume" :disabled="planBusy">
                <AegisIcon name="refresh" :size="13" /> Reactivate
              </button>
            </template>
          </AegisModal>

          <AegisModal v-model="confirmCsCancel" title="Cancel your subscription?" size="sm">
            <p style="font-size:14px;color:var(--text);margin-bottom:12px">Your Business CS subscription will end <strong>{{ formatDate(sub.current_period?.end) || \'at the end of the current period\' }}</strong>.</p>
            <template #footer>
              <button type="button" class="btn btn-outline btn-sm" @click="confirmCsCancel = false">Keep Subscription</button>
              <button type="button" class="btn btn-danger btn-sm" @click="cancelCsPlan" :disabled="planBusy">Cancel Subscription</button>
            </template>
          </AegisModal>
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
  mfaMethod:    { type: String,   default: '' },
  sessions:     { type: Array,   default: () => [] },
  subscription: { type: Object,  default: () => null },
  pricing:      { type: Object,  default: () => ({}) },
});

const toast = useToast();
// Sessions — passed from controller via props
const sessions = computed(() => props.sessions ?? []);

// Billing state (Business CS only)
const billingAnnual    = ref(false);
const sub              = computed(() => props.subscription ?? {});
const subStatus        = computed(() => sub.value.status || 'none');
const prices           = computed(() => sub.value.prices ?? {});
const csMonthlyId      = computed(() => prices.value.cs_business_monthly ?? null);
const csAnnualId       = computed(() => prices.value.cs_business_annual  ?? null);
const csPriceId        = computed(() => billingAnnual.value ? csAnnualId.value : csMonthlyId.value);
const stripeInvoices   = computed(() => sub.value.invoices ?? []);
const planBusy         = ref(false);
const confirmCsCancel  = ref(false);
const confirmCsResume  = ref(false);
const pendingCsSwap    = reactive({ label: '', priceLine: '', note: '' });
const confirmCsSwap    = ref(false);

function formatDate(ts) {
  if (!ts) return '';
  const d = typeof ts === 'number' ? new Date(ts * 1000) : new Date(ts);
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
function formatCents(cents) { return '$' + (cents / 100).toFixed(2); }

function swapCsPlan() {
  if (!csPriceId.value) { toast.error('Price not configured.'); return; }
  pendingCsSwap.label     = 'Business CS — ' + (billingAnnual.value ? 'Annual' : 'Monthly');
  pendingCsSwap.priceLine = billingAnnual.value ? '$35.75/mo (billed $429/yr)' : '$49/mo';
  pendingCsSwap.note      = billingAnnual.value
    ? 'Switching to annual billing saves you ~27%. The change takes effect at your next billing cycle.'
    : 'Switching to monthly billing. The change takes effect at your next billing cycle.';
  confirmCsSwap.value = true;
}

function doCsSwap() {
  confirmCsSwap.value = false;
  planBusy.value = true;
  router.post(route('cs.settings.subscription.swap'), { price_id: csPriceId.value }, {
    preserveScroll: true,
    onSuccess: () => toast.success(billingAnnual.value ? 'Switched to annual billing!' : 'Switched to monthly billing!'),
    onError: (errors) => toast.error(errors.subscription ?? 'Could not update plan.'),
    onFinish: () => { planBusy.value = false; },
  });
}
function cancelCsPlan() {
  planBusy.value = true;
  confirmCsCancel.value = false;
  router.post(route('cs.settings.subscription.cancel'), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Subscription will end at the current billing period.'),
    onError: (errors) => toast.error(errors.subscription ?? 'Could not cancel.'),
    onFinish: () => { planBusy.value = false; },
  });
}
function resumeCsPlan() {
  confirmCsResume.value = true;
}

function doCsResume() {
  confirmCsResume.value = false;
  planBusy.value = true;
  router.post(route('cs.settings.subscription.resume'), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Subscription reactivated! Your plan continues as before.'),
    onError: (errors) => toast.error(errors.subscription ?? 'Could not reactivate.'),
    onFinish: () => { planBusy.value = false; },
  });
}

const section = ref(\'profile\');
const isInvitedCs = computed(() => !props.user?.cs_account_type || props.user.cs_account_type === \'invited\' || !!props.user.linked_provider_id);
const displayName = computed(() => props.user?.display_name || \'Marcus Webb\');
const initials    = computed(() => displayName.value.split(\' \').map(p => p[0]).join(\'\').slice(0, 2).toUpperCase());

const i = \'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"\';
const nav = [
  { group: \'Account\', items: [
    { key: \'profile\',    label: \'Profile & Identity\',  icon: 'lock' },
    { key: \'security\',   label: \'Security & 2FA\',      icon: 'bell' },
    { key: \'messaging\',     label: \'Messaging\',         icon: 'mail' },
  ]},
  { group: \'Steward Role\', items: [
    { key: \'cs-steward\',  label: \'CS Role Settings\',      icon: 'lock' },
    { key: \'privacy\',     label: \'Privacy & Visibility\',  icon: 'settings' },
    { key: \'billing\',    label: \'Subscription & Plan\',  icon: 'alert-triangle' },
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
