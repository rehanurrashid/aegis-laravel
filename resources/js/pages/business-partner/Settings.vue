<template>
  <AppLayout :user="user" portal="business_partner" activePage="settings" pageTitle="Settings">
    <AegisHeroBanner eyebrow="Business Partner" title="Business Account" quiet>
      <template #actions>
        <a :href="route(\'bp.activity\') + \'?event_type=account\'" class="btn-hero-ghost is-on-light">
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
            update-account-route="bp.settings.account"
            revoke-session-route="bp.settings.sessions.revoke"
            revoke-all-route="bp.settings.sessions.revoke-all"
          />
        </div>

        <div v-show="section === \'security\'" class="settings-panel">
          <SettingsSecurity enable-mfa-route="bp.settings.mfa.enable" disable-mfa-route="bp.settings.mfa.disable" verify-mfa-route="bp.settings.mfa.verify"
            backup-codes-route="bp.settings.mfa.backup-codes"
            enable-email-mfa-route="bp.settings.mfa.enable-email"
            verify-email-mfa-route="bp.settings.mfa.verify-email"
            :mfa-method="mfaMethod" :mfa-enabled="mfaEnabled"
            :user-email="user?.email ?? ''" />
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

        <!-- BILLING — BP subscription (wired to Stripe) -->
        <div v-show="section === 'billing'" class="settings-panel">
          <div class="st-card">
            <div class="st-card-head">
              <div class="st-card-head-l">
                <span class="st-card-ico"><AegisIcon name="star" :size="17" /></span>
                <div><div class="st-card-title">Subscription &amp; Plan</div><div class="st-card-sub">Your current Aegis Business Partner plan</div></div>
              </div>
              <div style="display:flex;gap:8px">
                <a :href="route('bp.settings.billing.portal')" class="btn btn-outline btn-sm" target="_blank"><AegisIcon name="external-link" :size="13" /> Manage in Stripe</a>
              </div>
            </div>
            <div class="st-card-body">

              <!-- No subscription -->
              <template v-if="subStatus === 'none'">
                <div class="alert alert-info" style="margin-bottom:18px">
                  <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
                  <div class="alert-content">No active subscription found. Your account may be in grace period or payment failed. Contact <a href="mailto:support@maatpracticefirm.com" style="color:var(--gold-dark)">support@maatpracticefirm.com</a> for help.</div>
                </div>
              </template>

              <!-- Grace period -->
              <template v-else-if="sub.on_grace_period">
                <div class="st-perks-band" style="border-color:var(--orange);background:var(--orange-light);">
                  <div style="display:flex;align-items:center;gap:10px">
                    <AegisIcon name="alert-triangle" :size="18" style="color:var(--orange)" />
                    <div>
                      <div style="font-size:13px;font-weight:700;color:var(--text)">Your subscription was cancelled</div>
                      <div style="font-size:12px;color:var(--text-2)">Access ends {{ formatDate(sub.ends_at) }}. Reactivate before then to keep your account.</div>
                    </div>
                    <button type="button" class="btn btn-gold btn-sm" @click="resumeBpPlan" :disabled="planBusy">Reactivate</button>
                  </div>
                </div>
              </template>

              <!-- Past due -->
              <template v-else-if="subStatus === 'past_due'">
                <div class="st-perks-band" style="border-color:var(--red-light);background:var(--icon-bg-red,var(--surface-2));">
                  <AegisIcon name="alert-triangle" :size="16" style="color:var(--red)" />
                  <div style="font-size:13px;color:var(--text-2)">Payment failed — please <a :href="route('bp.settings.billing.portal')" style="color:var(--red)">update your payment method</a>.</div>
                </div>
              </template>

              <!-- Active -->
              <div v-if="subStatus !== 'none'" class="st-current-plan">
                <div class="st-current-meta">Business Partner Professional &mdash; {{ sub.current_billing === 'annual' ? '$57.50/mo (billed $690/yr)' : '$69/mo' }}</div>
                <div v-if="sub.current_period" class="st-current-meta" style="font-size:12px;color:var(--text-3)">
                  Current period: {{ formatDate(sub.current_period.start) }} → {{ formatDate(sub.current_period.end) }}
                </div>
              </div>

              <!-- Billing toggle -->
              <div v-if="subStatus !== 'none'" class="st-cycle-toggle">
                <span :class="{ active: !billingAnnual }">Monthly</span>
                <button type="button" class="toggle" :class="{ on: billingAnnual }" @click="billingAnnual = !billingAnnual" :aria-pressed="billingAnnual"></button>
                <span :class="{ active: billingAnnual }">Annual <span class="st-save-pill">Save 2 months</span></span>
              </div>

              <!-- Single BP plan card -->
              <div v-if="subStatus !== 'none'" class="st-plan-grid" style="max-width:380px;margin:0 auto">
                <div class="st-plan-tier current">
                  <span class="st-plan-tier-badge"><AegisIcon name="check" :size="11" /> Your Plan</span>
                  <div class="st-plan-tier-name">Business Partner</div>
                  <div class="st-plan-tier-price">${{ billingAnnual ? '57.50' : '69' }}<span>/mo</span></div>
                  <div class="st-plan-tier-alt">{{ billingAnnual ? 'billed $690/yr (save 2 months)' : 'or $690/yr (save 2 months)' }}</div>
                  <ul class="st-plan-feats">
                    <li v-for="f in bpFeatures" :key="f"><AegisIcon name="check" :size="12" /> {{ f }}</li>
                  </ul>
                  <button
                    v-if="billingAnnual !== (sub.current_billing === 'annual')"
                    type="button" class="btn btn-gold btn-sm st-plan-cta"
                    @click="swapBpPlan" :disabled="planBusy || !bpPriceId"
                  >
                    Switch to {{ billingAnnual ? 'Annual' : 'Monthly' }} billing
                  </button>
                  <button v-else type="button" class="btn btn-outline btn-sm st-plan-cta" disabled>
                    Your current plan
                  </button>
                </div>
              </div>

              <div v-if="subStatus !== 'none'" class="st-divider"></div>

              <!-- Cancel plan -->
              <div v-if="subStatus === 'active' || subStatus === 'trialing'" style="display:flex;align-items:center;justify-content:space-between;padding-top:8px">
                <div style="font-size:13px;color:var(--text-3)">Want to cancel? Your access continues until the end of the billing period.</div>
                <button type="button" class="btn btn-outline btn-sm" style="color:var(--red);border-color:var(--red)" @click="confirmBpCancel = true" :disabled="planBusy">Cancel Plan</button>
              </div>

              <!-- Invoice history -->
              <div v-if="stripeInvoices.length > 0">
                <div class="st-divider"></div>
                <div class="st-subhead" style="margin-bottom:12px">Invoice History</div>
                <table class="billing-table">
                  <thead><tr><th>Date</th><th>Amount</th><th>Status</th><th></th></tr></thead>
                  <tbody>
                    <tr v-for="inv in stripeInvoices" :key="inv.id">
                      <td>{{ formatDate(inv.paid_at || inv.created) }}</td>
                      <td>{{ formatCents(inv.amount_cents) }}</td>
                      <td><span v-if="inv.status === 'paid'" style="color:var(--green);font-weight:600;display:inline-flex;align-items:center;gap:4px"><AegisIcon name="check" :size="13" />Paid</span><span v-else style="color:var(--text-3)">{{ inv.status }}</span></td>
                      <td><a v-if="inv.pdf_url" :href="inv.pdf_url" target="_blank" class="btn btn-ghost btn-xs" data-tooltip="Download PDF"><AegisIcon name="download" :size="14" /></a></td>
                    </tr>
                  </tbody>
                </table>
              </div>

            </div>
          </div>

          <!-- Cancel confirmation modal -->
          <!-- BP Plan Swap Confirmation -->
          <AegisModal v-model="confirmBpSwap" title="Confirm Billing Change" size="md">
            <div style="margin-bottom:16px">
              <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                <div style="flex:1;padding:12px 14px;border:1px solid var(--border);border-radius:var(--radius);background:var(--surface-2)">
                  <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-3);margin-bottom:4px">Current</div>
                  <div style="font-family:var(--font-serif);font-size:14px;font-weight:700;color:var(--text)">Business Partner — {{ sub.current_billing === 'annual' ? 'Annual' : 'Monthly' }}</div>
                  <div style="font-size:12px;color:var(--text-3);margin-top:2px">{{ sub.current_billing === 'annual' ? '$57.50/mo (billed $690/yr)' : '$69/mo' }}</div>
                </div>
                <AegisIcon name="arrow-right" :size="16" style="color:var(--text-3);flex-shrink:0" />
                <div style="flex:1;padding:12px 14px;border:1px solid var(--gold-dark);border-radius:var(--radius);background:var(--icon-bg-gold)">
                  <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-3);margin-bottom:4px">New</div>
                  <div style="font-family:var(--font-serif);font-size:14px;font-weight:700;color:var(--text)">{{ pendingBpSwap.label }}</div>
                  <div style="font-size:12px;color:var(--gold-dark);margin-top:2px;font-weight:600">{{ pendingBpSwap.priceLine }}</div>
                </div>
              </div>
              <div style="padding:12px 14px;border-radius:var(--radius);background:var(--surface-2);border:1px solid var(--border);font-size:13px;color:var(--text-2);line-height:1.6">
                <AegisIcon name="info" :size="13" style="color:var(--gold-dark);vertical-align:middle;margin-right:4px" />
                {{ pendingBpSwap.note }}
              </div>
            </div>
            <template #footer>
              <button type="button" class="btn btn-outline btn-sm" @click="confirmBpSwap = false">Go Back</button>
              <button type="button" class="btn btn-gold btn-sm" @click="doBpSwap" :disabled="planBusy">
                <AegisIcon name="check" :size="13" /> Confirm Change
              </button>
            </template>
          </AegisModal>

          <!-- BP Reactivate Confirmation -->
          <AegisModal v-model="confirmBpResume" title="Reactivate Subscription" size="sm">
            <p style="font-size:14px;color:var(--text-2);margin-bottom:14px">Your <strong>Business Partner</strong> subscription will continue as normal. You will be charged on your next billing date.</p>
            <div style="padding:10px 14px;border-radius:var(--radius);background:var(--green-light);border:1px solid var(--green-dark);font-size:13px;color:var(--green-dark)">
              <AegisIcon name="check" :size="13" style="margin-right:4px" /> Your Business Partner portal access will be fully restored immediately.
            </div>
            <template #footer>
              <button type="button" class="btn btn-outline btn-sm" @click="confirmBpResume = false">Cancel</button>
              <button type="button" class="btn btn-gold btn-sm" @click="doBpResume" :disabled="planBusy">
                <AegisIcon name="refresh" :size="13" /> Reactivate
              </button>
            </template>
          </AegisModal>

          <AegisModal v-model="confirmBpCancel" title="Cancel your subscription?" size="sm">
            <p style="font-size:14px;color:var(--text);margin-bottom:12px">Your subscription will remain active until <strong>{{ formatDate(sub.current_period?.end) || 'the end of the current period' }}</strong>. After that you'll lose access to your BP portal.</p>
            <p style="font-size:13px;color:var(--text-3)">You can reactivate any time before the period ends.</p>
            <template #footer>
              <button type="button" class="btn btn-outline btn-sm" @click="confirmBpCancel = false">Keep Subscription</button>
              <button type="button" class="btn btn-danger btn-sm" @click="cancelBpPlan" :disabled="planBusy">Cancel Subscription</button>
            </template>
          </AegisModal>
        </div>

        <div v-show="section === 'danger'" class="settings-panel">
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
  mfaMethod:    { type: String,   default: '' },
  sessions:     { type: Array,   default: () => [] },
  subscription: { type: Object,  default: () => ({}) },
  pricing:      { type: Object,  default: () => ({}) },
});

const toast = useToast();
// Sessions — passed from controller via props
const sessions = computed(() => props.sessions ?? []);
const section     = ref(\'profile\');
const billingAnnual = ref(false);
const sub                  = computed(() => props.subscription ?? {});
const subStatus            = computed(() => sub.value.status || 'none');
const prices               = computed(() => sub.value.prices ?? {});
const bpMonthlyId          = computed(() => prices.value.bp_monthly ?? null);
const bpAnnualId           = computed(() => prices.value.bp_annual  ?? null);
const bpPriceId            = computed(() => billingAnnual.value ? bpAnnualId.value : bpMonthlyId.value);
const stripeInvoices        = computed(() => sub.value.invoices        ?? []);
const stripePaymentMethods  = computed(() => sub.value.payment_methods ?? []);
const planBusy              = ref(false);
const confirmBpCancel  = ref(false);
const confirmBpResume  = ref(false);
const pendingBpSwap    = reactive({ label: '', priceLine: '', note: '' });
const confirmBpSwap    = ref(false);

function formatDate(ts) {
  if (!ts) return '';
  const d = typeof ts === 'number' ? new Date(ts * 1000) : new Date(ts);
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
function formatCents(cents) { return '$' + (cents / 100).toFixed(2); }
function formatCardBrand(b) { return b ? b.charAt(0).toUpperCase() + b.slice(1) : 'Card'; }

function swapBpPlan() {
  if (!bpPriceId.value) { toast.error('Price not configured.'); return; }
  pendingBpSwap.label     = 'Business Partner — ' + (billingAnnual.value ? 'Annual' : 'Monthly');
  pendingBpSwap.priceLine = billingAnnual.value ? '$57.50/mo (billed $690/yr)' : '$69/mo';
  pendingBpSwap.note      = billingAnnual.value
    ? 'Switching to annual billing saves you the equivalent of 2 months. The change takes effect at your next billing cycle.'
    : 'Switching to monthly billing. The change takes effect at your next billing cycle.';
  confirmBpSwap.value = true;
}

function doBpSwap() {
  confirmBpSwap.value = false;
  planBusy.value = true;
  router.post(route('bp.settings.subscription.swap'), { price_id: bpPriceId.value }, {
    preserveScroll: true,
    onSuccess: () => toast.success(billingAnnual.value ? 'Switched to annual billing!' : 'Switched to monthly billing!'),
    onError: (errors) => toast.error(errors.subscription ?? 'Could not update plan.'),
    onFinish: () => { planBusy.value = false; },
  });
}
function cancelBpPlan() {
  planBusy.value = true;
  confirmBpCancel.value = false;
  router.post(route('bp.settings.subscription.cancel'), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Subscription will end at the current billing period.'),
    onError: (errors) => toast.error(errors.subscription ?? 'Could not cancel.'),
    onFinish: () => { planBusy.value = false; },
  });
}
function resumeBpPlan() {
  confirmBpResume.value = true;
}

function doBpResume() {
  confirmBpResume.value = false;
  planBusy.value = true;
  router.post(route('bp.settings.subscription.resume'), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Subscription reactivated! Your plan continues as before.'),
    onError: (errors) => toast.error(errors.subscription ?? 'Could not reactivate.'),
    onFinish: () => { planBusy.value = false; },
  });
}

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
  { key: \'proposals\',  label: \'Job Proposal Activity\',  desc: \'When a practitioner responds to, accepts, or declines a proposal\',          push: true,  email: true, inapp: true  },
  { key: \'milestones\', label: \'Contract Milestones\',    desc: \'When a contract milestone is reached, due, or requires action\',               push: true,  email: true, inapp: true  },
  { key: \'payments\',   label: \'Invoice & Payments\',     desc: \'Payment confirmations, failed charges, and upcoming invoices\',                push: false, email: true, inapp: true  },
  { key: \'messages\',   label: \'New Messages\',           desc: \'When a practitioner or Aegis support sends you a message\',                    push: true,  email: false, inapp: true  },
  { key: \'agreements\', label: \'Agreement Alerts\',       desc: \'Signing requests, renewals, and expiry warnings on business agreements\',      push: true,  email: true, inapp: true  },
  { key: \'network\',    label: \'Network Updates\',        desc: \'New connection requests and approvals from practitioners\',                    push: false, email: true, inapp: true  },
  { key: \'jobs\',       label: \'Job Posting Activity\',   desc: \'Applications, views, and status changes on your active job postings\',         push: true,  email: true, inapp: true  },
  { key: \'platform\',   label: \'Platform Updates\',       desc: \'New Aegis features and maintenance windows\',                                  push: false, email: false, inapp: false },
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
