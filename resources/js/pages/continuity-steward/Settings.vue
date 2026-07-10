<template>
  <AppLayout :user="user" portal="continuity_steward" activePage="settings" pageTitle="Settings">
    <AegisHeroBanner eyebrow="Continuity Steward" title="Continuity Steward Settings" quiet>
      <template #actions>
        <a :href="route('cs.activity') + '?event_type=account'" class="btn-hero-ghost is-on-light">
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
              :style="it.danger ? 'color:var(--red)' : ''" @click="section = it.key">
              <span class="s-nav-icon"><AegisIcon :name="it.icon" :size="15" /></span>{{ it.label }}
            </button>
          </div>
        </template>
      </div>
      <div class="settings-content">
        <div v-show="section === 'profile'" class="settings-panel">
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
              <a :href="route('cs.profile.index')" class="btn btn-primary btn-sm"><AegisIcon name="edit" :size="13" /> Edit Full Profile</a>
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
                      <span style="display:flex;align-items:center;gap:4px"><AegisIcon name="mail" :size="12" /> {{ user?.email ?? ''  }}</span>
                    </div>
                  </div>
                  <span class="badge badge-blue">CS</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-show="section === 'account'" class="settings-panel">
          <SettingsAccount
            :user="user"
            :sessions="sessions"
            update-password-route="cs.settings.password"
            update-account-route="cs.settings.account"
            revoke-session-route="cs.settings.sessions.revoke"
            revoke-all-route="cs.settings.sessions.revoke-all"
          />
        </div>
        <div v-show="section === 'security'" class="settings-panel">
          <SettingsSecurity enable-mfa-route="cs.settings.mfa.enable" disable-mfa-route="cs.settings.mfa.disable" verify-mfa-route="cs.settings.mfa.verify"
            backup-codes-route="cs.settings.mfa.backup-codes"
            enable-email-mfa-route="cs.settings.mfa.enable-email"
            verify-email-mfa-route="cs.settings.mfa.verify-email"
            :mfa-method="mfaMethod" :mfa-enabled="mfaEnabled"
            :user-email="user?.email ?? ''" />
        </div>
        <div v-show="section === 'notifications'" class="settings-panel">
          <SettingsNotifications update-route="cs.settings.notifications"
            :saved-prefs="meta?.notify_prefs ?? {}"
            :saved-categories="meta?.notify_categories ?? []" subtitle="Delivery channels unified across portals. Per-category preferences apply to your Continuity Steward role." :notif-categories="csNotifCategories" />
        </div>
        <div v-show="section === 'messaging'" class="settings-panel">
          <SettingsMessaging update-route="cs.settings.messaging" messages-route="cs.messages" subtitle="Control who can reach you and how you appear to assigned practitioners" :meta="meta">
            <template #extra-toggles>
              <div class="toggle-row">
                <div class="toggle-info"><div class="toggle-label">Critical Incident Thread Auto-Flag</div><div class="toggle-desc">Messages sent during an active incident are automatically marked as legal record</div></div>
                <button type="button" class="toggle on" aria-pressed="true"></button>
              </div>
            </template>
          </SettingsMessaging>
        </div>
        <div v-show="section === 'email-prefs'" class="settings-panel">
          <SettingsEmailPrefs update-route="cs.settings.email-prefs" activity-label="Stewardship Activity Summary" activity-desc="Digest of plan changes, attestations, and coverage events" :meta="meta" />
        </div>
        <div v-show="section === 'cs-steward'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="shield" :size="16" /></div>
                <div><div class="card-title">Continuity Steward Settings</div><div class="card-subtitle">Preferences for your stewardship role and responsibilities</div></div>
              </div>
            </div>
            <div class="card-body">
              <div class="section-label">Role Visibility</div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Show Name on Provider Public Profile</div><div class="toggle-desc">Allow your name to appear as "Continuity Steward" on your providers' public Aegis pages</div></div><button type="button" class="toggle" :class="{ on: csRolePrefs.showOnProfile }" @click="csRolePrefs.showOnProfile = !csRolePrefs.showOnProfile" :aria-pressed="csRolePrefs.showOnProfile"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Request Vault Access on Assignment</div><div class="toggle-desc">Automatically request emergency vault access when assigned to a new provider</div></div><button type="button" class="toggle" :class="{ on: csRolePrefs.vaultAccess }" @click="csRolePrefs.vaultAccess = !csRolePrefs.vaultAccess" :aria-pressed="csRolePrefs.vaultAccess"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px"><button type="button" class="btn btn-primary" @click="saveRolePrefs"><AegisIcon name="check" :size="16" /> Save CS Settings</button></div>
            </div>
          </div>
        </div>
        <div v-show="section === 'documents-s'" class="settings-panel">
          <div class="card">
            <div class="card-header">
              <div class="card-title-group">
                <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="lock" :size="16" /></div>
                <div><div class="card-title">Document Vault Access</div><div class="card-subtitle">Notification preferences for vault activity on providers you steward</div></div>
              </div>
            </div>
            <div class="card-body">
              <div class="alert alert-info" style="margin-bottom:16px"><div class="alert-icon"><AegisIcon name="info" :size="16" /></div><div class="alert-content" style="font-size:12px">Your vault access level is set by each practitioner individually. Access to sealed zones is only released when a critical incident is verified.</div></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Notify Me When I Access a Vault</div><div class="toggle-desc">Receive a confirmation log entry whenever you open or download a document from a provider's vault</div></div><button type="button" class="toggle" :class="{ on: vaultNotifyPrefs.notifyAccess }" @click="vaultNotifyPrefs.notifyAccess = !vaultNotifyPrefs.notifyAccess" :aria-pressed="vaultNotifyPrefs.notifyAccess"></button></div>
              <div class="toggle-row"><div class="toggle-info"><div class="toggle-label">Notify Me on Emergency Vault Unlock</div><div class="toggle-desc">Alert me immediately when an emergency vault becomes accessible during an active incident</div></div><button type="button" class="toggle" :class="{ on: vaultNotifyPrefs.notifyUnlock }" @click="vaultNotifyPrefs.notifyUnlock = !vaultNotifyPrefs.notifyUnlock" :aria-pressed="vaultNotifyPrefs.notifyUnlock"></button></div>
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px"><button type="button" class="btn btn-primary" @click="saveVaultPrefs"><AegisIcon name="check" :size="13" /> Save</button></div>
            </div>
          </div>
        </div>
        <div v-show="section === 'privacy'" class="settings-panel">
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
              <div class="btn-group" style="justify-content:flex-end;margin-top:16px"><button type="button" class="btn btn-primary" @click="savePrivacy"><AegisIcon name="check" :size="13" /> Save Privacy Settings</button></div>
            </div>
          </div>
        </div>
        <div v-show="section === 'appearance'" class="settings-panel">
          <SettingsAppearance update-route="cs.settings.appearance" :meta="meta" />
        </div>
        <!-- BILLING — Business CS only / Invited CS sees no-cost notice -->
        <div v-show="section === 'billing'" class="settings-panel">

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
                <div class="alert-content"><strong>Full Continuity Steward access — at no cost.</strong><br>Covered by your linked provider's Aegis subscription.</div>
              </div>
              <div v-if="user?.linked_provider_name" style="background:var(--icon-bg-gold);border:1px solid var(--badge-border-gold);border-radius:var(--radius);padding:16px 18px;margin-bottom:18px;display:flex;align-items:center;gap:14px">
                <div class="stat-chip-icon" style="width:40px;height:40px;flex-shrink:0;background:var(--gold-dark);color:var(--text-inverted)"><AegisIcon name="user" :size="16" /></div>
                <div style="flex:1">
                  <div style="font-size:11px;text-transform:uppercase;color:var(--text-3);margin-bottom:3px">Your Linked Provider</div>
                  <div style="font-family:var(--font-serif);font-size:17px;font-weight:700;color:var(--text)">{{ user.linked_provider_name }}</div>
                </div>
                <a :href="route('cs.providers.index')" class="btn btn-outline btn-sm"><AegisIcon name="external-link" :size="13" /> View Provider</a>
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
              <a :href="route('cs.settings.billing.portal')" class="btn btn-outline btn-sm" target="_blank"><AegisIcon name="external-link" :size="13" /> Manage in Stripe</a>
            </div>
            <div class="st-card-body">
              <template v-if="sub.on_grace_period">
                <div class="st-perks-band" style="border-color:var(--orange);background:var(--orange-light);">
                  <AegisIcon name="alert-triangle" :size="16" style="color:var(--orange)" />
                  <div style="font-size:13px;color:var(--text-2)">Subscription cancelled — access ends { formatDate(sub.ends_at) }. <button type="button" class="btn btn-gold btn-sm" @click="resumeCsPlan" :disabled="planBusy">Reactivate</button></div>
                </div>
              </template>
              <template v-else-if="subStatus === 'past_due'">
                <div class="st-perks-band" style="border-color:var(--red-light);background:var(--surface-2);">
                  <AegisIcon name="alert-triangle" :size="16" style="color:var(--red)" />
                  <div style="font-size:13px;color:var(--text-2)">Payment failed — <a :href="route('cs.settings.billing.portal')" style="color:var(--red)">update your payment method</a>.</div>
                </div>
              </template>
              <div v-if="subStatus !== 'none'" class="st-current-plan">
                <div class="st-current-meta">Business CS — { sub.current_billing === 'annual' ? '$35.75/mo (billed $429/yr)' : '$49/mo' }</div>
                <div v-if="sub.current_period" class="st-current-meta" style="font-size:12px;color:var(--text-3)">Current period: { formatDate(sub.current_period.start) } → { formatDate(sub.current_period.end) }</div>
              </div>
              <div v-if="subStatus !== 'none'" class="st-cycle-toggle">
                <span :class="{ active: !billingAnnual }">Monthly</span>
                <button type="button" class="toggle" :class="{ on: billingAnnual }" @click="billingAnnual = !billingAnnual" :aria-pressed="billingAnnual"></button>
                <span :class="{ active: billingAnnual }">Annual <span class="st-save-pill">Save ~27%</span></span>
              </div>
              <div v-if="subStatus !== 'none'" style="margin:12px 0">
                <button v-if="billingAnnual !== (sub.current_billing === 'annual')" type="button" class="btn btn-gold btn-sm" @click="swapCsPlan" :disabled="planBusy || !csPriceId">
                  Switch to { billingAnnual ? 'Annual' : 'Monthly' } billing
                </button>
              </div>
              <div v-if="(subStatus === 'active' || subStatus === 'trialing')" style="display:flex;align-items:center;justify-content:space-between;padding-top:8px;border-top:1px solid var(--border);margin-top:8px">
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
                      <td><span v-if="inv.status === 'paid'" style="color:var(--green);font-weight:600;display:inline-flex;align-items:center;gap:4px"><AegisIcon name="check" :size="13" />Paid</span><span v-else style="color:var(--text-3)">{{ inv.status }}</span></td>
                      <td><a v-if="inv.pdf_url" :href="inv.pdf_url" target="_blank" class="btn btn-ghost btn-xs" data-tooltip="Download PDF"><AegisIcon name="download" :size="14" /></a></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- BUSINESS CS: Stripe Connect account (receive payouts from providers) -->
          <div class="st-card" style="margin-top:14px">
            <div class="st-card-head">
              <div class="st-card-head-l">
                <span class="st-card-ico"><AegisIcon name="credit-card" :size="17" /></span>
                <div><div class="st-card-title">Payouts &amp; Stripe Connect</div><div class="st-card-sub">How you receive practitioner invoice payments</div></div>
              </div>
            </div>
            <div class="st-card-body">
              <div class="stripe-status" :class="stripeReady ? 'is-connected' : 'is-disconnected'" style="display:flex;align-items:center;gap:12px;padding:14px;background:var(--surface-2);border-radius:var(--radius);border:1px solid var(--border)">
                <AegisIcon :name="stripeReady ? 'check-circle' : 'alert-triangle'" :size="20" :style="stripeReady ? 'color:var(--green-dark)' : 'color:var(--gold-dark)'" />
                <div style="flex:1">
                  <div style="font-size:14px;font-weight:700;color:var(--text)">{ stripeReady ? 'Stripe Connect ready' : 'Stripe Connect required' }</div>
                  <div style="font-size:12px;color:var(--text-3);margin-top:2px">{ stripeReady ? 'Practitioner invoice payments transfer directly to your connected Stripe account. Aegis never holds funds.' : 'Connect your Stripe account to receive invoice payments from practitioners you steward.' }</div>
                </div>
                <a v-if="!stripeReady" :href="route('cs.settings.connect.onboard')" class="btn btn-primary btn-sm">Connect Stripe</a>
                <a v-else :href="route('cs.settings.connect.onboard')" class="btn btn-outline btn-sm">Reconfigure</a>
              </div>
            </div>
          </div>

          <!-- CS Plan Swap Confirmation -->
          <AegisModal v-model="confirmCsSwap" title="Confirm Billing Change" size="md">
            <div style="margin-bottom:16px">
              <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                <div style="flex:1;padding:12px 14px;border:1px solid var(--border);border-radius:var(--radius);background:var(--surface-2)">
                  <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-3);margin-bottom:4px">Current</div>
                  <div style="font-family:var(--font-serif);font-size:14px;font-weight:700;color:var(--text)">Business CS — { sub.current_billing === 'annual' ? 'Annual' : 'Monthly' }</div>
                  <div style="font-size:12px;color:var(--text-3);margin-top:2px">{ sub.current_billing === 'annual' ? '$35.75/mo (billed $429/yr)' : '$49/mo' }</div>
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
            <p style="font-size:14px;color:var(--text);margin-bottom:12px">Your Business CS subscription will end <strong>{{ formatDate(sub.current_period?.end) || 'at the end of the current period' }}</strong>.</p>
            <template #footer>
              <button type="button" class="btn btn-outline btn-sm" @click="confirmCsCancel = false">Keep Subscription</button>
              <button type="button" class="btn btn-danger btn-sm" @click="cancelCsPlan" :disabled="planBusy">Cancel Subscription</button>
            </template>
          </AegisModal>
        </div>

        <!-- PAYMENT METHODS -->
        <div v-show="section === 'payment_methods'" class="settings-panel">
          <div class="st-card">
            <div class="st-card-head">
              <div class="st-card-head-l">
                <span class="st-card-ico"><AegisIcon name="credit-card" :size="17" /></span>
                <div><div class="st-card-title">Payment Methods</div><div class="st-card-sub">Cards used to fund your Aegis subscription</div></div>
              </div>
              <button type="button" class="btn btn-dark" @click="stShowAddCard = true">
                <AegisIcon name="plus" :size="12" /> Add Method
              </button>
            </div>
            <div class="st-card-body">
              <div class="alert alert-info" style="margin-bottom:16px;">
                <div class="alert-icon"><AegisIcon name="shield" :size="18" /></div>
                <div class="alert-content">
                  <div class="alert-title">One Card, All Payments</div>
                  <div>Your active payment method funds your Aegis subscription. Aegis never sees or stores your full card number.</div>
                </div>
              </div>
              <AegisEmptyState v-if="!paymentMethods.length" icon="credit-card" title="No payment methods" description="Add a card to fund your Aegis subscription." style="padding:24px 0;" />
              <div v-else>
                <div v-for="pm in paymentMethods" :key="pm.id" class="pm-card" :class="{ default: pm.is_default }">
                  <div class="pm-logo"><AegisIcon :name="pm.method_type === 'bank' ? 'building' : 'credit-card'" :size="20" /></div>
                  <div class="pm-info">
                    <div class="pm-name">
                      {{ (pm.brand || 'card').toUpperCase() }} ···· {{ pm.last4 }}
                      <AegisBadge v-if="pm.is_default" label="Default" variant="gold" style="margin-left:6px;" />
                    </div>
                    <div class="pm-meta">{{ pm.exp_month ? 'Expires ' + pm.exp_month + '/' + pm.exp_year : 'On file' }}</div>
                  </div>
                  <div class="pm-card-btns">
                    <template v-if="!pm.is_default">
                      <button type="button" class="btn-icon btn-icon-sm" data-tooltip="Set as default" @click="stSetDefaultPm(pm)"><AegisIcon name="check" :size="12" /></button>
                      <button type="button" class="btn-icon btn-icon-sm btn-icon-danger" data-tooltip="Remove" @click="stOpenRemove(pm)"><AegisIcon name="trash" :size="12" /></button>
                    </template>
                    <AegisIcon v-else name="shield-check" :size="16" class="pm-default-icon" data-tooltip="Default" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- SUBSCRIPTION INVOICES -->
        <div v-show="section === 'subscription_invoices'" class="settings-panel">
          <div class="st-card">
            <div class="st-card-head">
              <div class="st-card-head-l">
                <span class="st-card-ico"><AegisIcon name="file-text" :size="17" /></span>
                <div><div class="st-card-title">Subscription Invoices</div><div class="st-card-sub">Your Aegis billing history</div></div>
              </div>
              <AegisBadge :label="subscriptionInvoices.length + ' invoice' + (subscriptionInvoices.length === 1 ? '' : 's')" variant="neutral" />
            </div>
            <div class="st-card-body" style="padding:0;">
              <table v-if="subscriptionInvoices.length" class="table sub-invoice-table" style="margin:0;">
                <thead><tr><th style="padding-left:20px;">Date</th><th>Plan</th><th>Amount</th><th>Status</th><th style="padding-right:20px;"></th></tr></thead>
                <tbody>
                  <tr v-for="inv in subscriptionInvoices" :key="inv.id" class="sub-inv-row" @click="stOpenSubInv(inv)">
                    <td style="padding-left:20px;" class="tx-date">{{ stFormatDate(inv.date || inv.created) }}</td>
                    <td><div class="sub-inv-product">{{ inv.product_name || 'Aegis Subscription' }}</div><div class="sub-inv-desc">#{{ inv.number || inv.id }}</div></td>
                    <td style="font-weight:700;white-space:nowrap;">{{ stFormatCents(inv.amount_cents) }}</td>
                    <td><AegisBadge :label="inv.status" :variant="stStatusVariant(inv.status)" /></td>
                    <td style="padding-right:20px;text-align:right;"><button type="button" class="btn-icon btn-icon-sm" data-tooltip="View" @click.stop="stOpenSubInv(inv)"><AegisIcon name="eye" :size="12" /></button></td>
                  </tr>
                </tbody>
              </table>
              <AegisEmptyState v-else icon="file-text" title="No invoices yet" description="Invoices appear after your first billing cycle." style="padding:32px 0;" />
            </div>
          </div>
        </div>

        <!-- STRIPE CONNECT -->
        <div v-show="section === 'stripe_connect'" class="settings-panel">
          <div class="st-card">
            <div class="st-card-head">
              <div class="st-card-head-l">
                <span class="st-card-ico"><AegisIcon name="link" :size="17" /></span>
                <div><div class="st-card-title">Stripe Connect</div><div class="st-card-sub">Receive practitioner invoice payments</div></div>
              </div>
              <span v-if="stStripeConnected" class="app-status-connected" style="font-size:12px;"><AegisIcon name="check" :size="13" /> Connected</span>
            </div>
            <div class="st-card-body">
              <div class="stripe-setup-card">
                <div class="stripe-setup-inner">
                  <div class="stripe-setup-icon"><AegisIcon name="credit-card" :size="22" /></div>
                  <div class="stripe-setup-body">
                    <div class="stripe-setup-title">Stripe Connect Express</div>
                    <div class="stripe-setup-desc">Connect your Stripe account to receive invoice payments from practitioners you steward. Funds go directly to your bank — Aegis never holds your money.</div>
                    <div class="stripe-setup-actions">
                      <template v-if="stStripeConnected">
                        <a :href="route('cs.settings.billing.portal')" class="btn btn-outline" target="_blank"><AegisIcon name="external-link" :size="12" /> Stripe Dashboard</a>
                        <a :href="route('cs.settings.connect.onboard')" class="btn btn-outline"><AegisIcon name="refresh-cw" :size="12" /> Reconnect</a>
                      </template>
                      <template v-else>
                        <a :href="route('cs.settings.connect.onboard')" class="btn btn-primary"><AegisIcon name="external-link" :size="13" /> Connect Stripe Account</a>
                        <span style="font-size:12px;color:var(--text-4);">You'll be redirected to Stripe to complete setup</span>
                      </template>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-show="section === 'danger'" class="settings-panel">
          <SettingsDangerZone
            delete-route="cs.settings.account.delete"
            pause-route="cs.settings.account.pause"
            resume-route="cs.settings.account.resume"
            export-route="cs.settings.account.export"
            :initial-paused="isAccountPaused"
          />
        </div>
      </div>
    </div>
  <!-- PM modals -->
  <AddCardModal v-model="stShowAddCard" setup-intent-route="cs.settings.payment.setup-intent" store-route="cs.settings.payment.store" />
  <AegisModal v-model="stShowRemove" title="Remove Payment Method" size="sm">
    <p style="font-size:13px;color:var(--text-2);">Remove this card? If it's your only card, subscription renewal will fail.</p>
    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="stRemovingCard" @click="stShowRemove = false">Cancel</button>
      <button type="button" class="btn btn-danger"  :disabled="stRemovingCard" @click="stDoRemove">
        <AegisIcon v-if="stRemovingCard" name="refresh-cw" :size="13" class="st-spin" />
        <AegisIcon v-else name="trash" :size="13" />
        {{ stRemovingCard ? 'Removing…' : 'Remove' }}
      </button>
    </template>
  </AegisModal>
  <AegisModal v-model="stSubInvOpen" title="Subscription Invoice" size="lg">
    <div v-if="stActiveSubInv" class="sub-inv-modal">
      <div class="sim-header">
        <div class="sim-logo"><AegisIcon name="star" :size="20" /></div>
        <div class="sim-brand"><div class="sim-from">Aegis Platform</div><div class="sim-sub">Continuity Steward Subscription</div></div>
        <div class="sim-status-block"><AegisBadge :label="stActiveSubInv.status" :variant="stStatusVariant(stActiveSubInv.status)" /><div class="sim-date">{{ stFormatDate(stActiveSubInv.date || stActiveSubInv.created) }}</div></div>
      </div>
      <div class="sim-number-row"><span class="sim-number-label">Invoice #</span><span class="sim-number">{{ stActiveSubInv.number || stActiveSubInv.id }}</span></div>
      <div class="sim-items"><div class="sim-item"><div class="sim-item-icon"><AegisIcon name="check-circle" :size="15" /></div><div class="sim-item-name">{{ stActiveSubInv.product_name || 'Aegis Subscription' }}</div><div class="sim-item-price">{{ stFormatCents(stActiveSubInv.amount_cents) }}</div></div></div>
      <div class="sim-totals"><div class="sim-total-row"><span>Subtotal</span><span>{{ stFormatCents(stActiveSubInv.amount_cents) }}</span></div><div class="sim-total-row sim-total-row--main"><span>Total paid</span><span>{{ stFormatCents(stActiveSubInv.amount_cents) }}</span></div></div>
      <div class="sim-fine-print"><AegisIcon name="shield" :size="12" /> Charged to your default card. Aegis never stores your full card number.</div>
    </div>
    <template #footer>
      <a v-if="stActiveSubInv?.pdf_url" :href="stActiveSubInv.pdf_url" target="_blank" class="btn btn-ghost"><AegisIcon name="download" :size="12" /> PDF</a>
      <a v-if="stActiveSubInv?.hosted_url" :href="stActiveSubInv.hosted_url" target="_blank" class="btn btn-outline"><AegisIcon name="external-link" :size="12" /> View on Stripe</a>
      <button v-if="!stActiveSubInv?.hosted_url && !stActiveSubInv?.pdf_url" type="button" class="btn btn-outline" @click="stSubInvOpen = false">Close</button>
    </template>
  </AegisModal>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast }   from '@/composables/useToast';
import { useConfirm } from '@/composables/useConfirm';
import AddCardModal   from '@/components/modals/AddCardModal.vue';
import AppLayout              from '@/layouts/AppLayout.vue';
import SettingsAccount        from '@/components/settings/SettingsAccount.vue';
import SettingsSecurity       from '@/components/settings/SettingsSecurity.vue';
import SettingsNotifications  from '@/components/settings/SettingsNotifications.vue';
import SettingsAppearance     from '@/components/settings/SettingsAppearance.vue';
import SettingsMessaging      from '@/components/settings/SettingsMessaging.vue';
import SettingsEmailPrefs     from '@/components/settings/SettingsEmailPrefs.vue';
import SettingsDangerZone     from '@/components/settings/SettingsDangerZone.vue';

const props = defineProps({
  user:         { type: Object,  default: () => ({}) },
  meta:         { type: Object,  default: () => ({}) },
  mfaEnabled:   { type: Boolean, default: false },
  mfaMethod:    { type: String,   default: '' },
  sessions:     { type: Array,   default: () => [] },
  subscription:   { type: Object,  default: () => null },
  paymentMethods: { type: Array,   default: () => [] },
  pricing:        { type: Object,  default: () => ({}) },
});

const toast = useToast();
const { confirmAction } = useConfirm();

// ── Payment Methods ────────────────────────────────────────────────────────────
const stActivePm      = ref(null);
const stRemovingCard  = ref(false);
const stShowAddCard   = ref(false);
const stShowRemove    = ref(false);
const stActiveSubInv  = ref(null);
const stSubInvOpen    = ref(false);

const subscriptionInvoices = computed(() => sub.value.invoices ?? []);
const stStripeConnected    = computed(() => !!(props.user?.stripe_connected));

function stSetDefaultPm(pm) {
  confirmAction(
    `Set ${(pm.brand || 'card').toUpperCase()} ···· ${pm.last4} as your default payment method?`,
    () => router.post(route('cs.settings.payment.default'), { payment_method_id: pm.id }, {
      preserveScroll: true,
      onSuccess: () => toast.success('Default payment method updated.'),
      onError:   () => toast.error('Could not update default.'),
    })
  );
}
function stOpenRemove(pm) { stActivePm.value = pm; stShowRemove.value = true; }
function stDoRemove() {
  if (!stActivePm.value || stRemovingCard.value) return;
  stRemovingCard.value = true;
  router.delete(route('cs.settings.payment.remove'), {
    data: { payment_method_id: stActivePm.value.id },
    preserveScroll: true,
    onSuccess: () => { stShowRemove.value = false; toast.info('Payment method removed.'); },
    onError:   () => toast.error('Could not remove payment method.'),
    onFinish:  () => { stRemovingCard.value = false; },
  });
}
function stOpenSubInv(inv) { stActiveSubInv.value = inv; stSubInvOpen.value = true; }
function stFormatCents(c) { return '$' + (Number(c ?? 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
function stFormatDate(d) { if (!d) return '—'; if (typeof d === 'number') return new Date(d * 1000).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); return d; }
function stStatusVariant(s) { return { paid: 'green', sent: 'blue', open: 'blue', active: 'green', trialing: 'blue', past_due: 'gold', draft: 'neutral', void: 'neutral', canceled: 'neutral', overdue: 'red' }[s] ?? 'neutral'; }
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
const stripeReady      = computed(() => {
  const acct = props.user?.stripe_account_id;
  return !!acct && !String(acct).startsWith('acct_demo_') && !!props.user?.stripe_connected;
});
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

const section = ref('profile');
const isInvitedCs = computed(() => !props.user?.cs_account_type || props.user.cs_account_type === 'invited' || !!props.user.linked_provider_id);
const displayName = computed(() => props.user?.display_name || 'Marcus Webb');
const initials    = computed(() => displayName.value.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase());

const i = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
const nav = [
  { group: 'Account', items: [
    { key: 'profile',    label: 'Profile & Identity',  icon: 'user' },
    { key: 'account',    label: 'Account & Login',     icon: 'lock' },
    { key: 'security',   label: 'Security & 2FA',      icon: 'shield' },
    { key: 'messaging',  label: 'Messaging',           icon: 'mail' },
  ]},
  { group: 'Steward Role', items: [
    { key: 'cs-steward',  label: 'CS Role Settings',      icon: 'lock' },
    { key: 'privacy',     label: 'Privacy & Visibility',  icon: 'settings' },
    { key: 'billing',             label: 'Subscription & Plan',    icon: 'star' },
    { key: 'payment_methods',     label: 'Payment Methods',          icon: 'credit-card' },
    { key: 'subscription_invoices', label: 'Subscription Invoices',  icon: 'file-text' },
    { key: 'stripe_connect',      label: 'Stripe Connect',           icon: 'link' },
  ]},
];

const csNotifCategories = [
  { key: 'incident',    label: 'Incident Triggers',    desc: 'When a critical incident is reported for one of my assigned providers',    push: true,  email: true, inapp: true  },
  { key: 'plan',        label: 'Plan Changes',          desc: 'When a practitioner updates or amends their continuity plan',               push: true,  email: true, inapp: true  },
  { key: 'attestation', label: 'Attestation Requests', desc: 'When a pending plan attestation requires my sign-off',                      push: true,  email: true, inapp: true  },
  { key: 'agreements',  label: 'Agreement Alerts',     desc: 'Signing requests, renewals, and expiry warnings on stewardship agreements', push: true,  email: true, inapp: true  },
  { key: 'messages',    label: 'New Messages',         desc: 'From assigned practitioners or Aegis support',                              push: true,  email: false, inapp: true  },
  { key: 'docs',        label: 'Document Access',      desc: 'When vault documents are accessed on behalf of a provider I steward',       push: false, email: true, inapp: true  },
  { key: 'coverage',    label: 'Coverage Activation',  desc: 'When continuity coverage for one of my providers is formally activated',    push: true,  email: true, inapp: true  },
  { key: 'checkin',     label: 'Check-in Reminders',   desc: 'Scheduled reminders to review and certify practitioner plans',             push: true,  email: true, inapp: true  },
];

const csRolePrefs      = reactive({ showOnProfile: true, vaultAccess: true });
const vaultNotifyPrefs = reactive({ notifyAccess: true, notifyUnlock: true });
const csPrivacy        = reactive({ level: 'public', search: true, location: true, creds: true });

const privacyLevels = [
  { key: 'public',  name: 'Public',   desc: 'Visible to all practitioners on Aegis', icon: 'eye'  },
  { key: 'network', name: 'Network',  desc: 'Practitioners I am connected with only', icon: 'link' },
  { key: 'private', name: 'Unlisted', desc: 'Not shown in search — invite only',      icon: 'lock' },
];

function saveRolePrefs() {
  router.put(route('cs.settings.role-prefs'), {
    show_on_profile: csRolePrefs.showOnProfile,
    vault_access:    csRolePrefs.vaultAccess,
  }, { preserveScroll: true, onSuccess: () => toast.success('CS settings saved.') });
}
function saveVaultPrefs() {
  router.put(route('cs.settings.vault-prefs'), {
    notify_access: vaultNotifyPrefs.notifyAccess,
    notify_unlock: vaultNotifyPrefs.notifyUnlock,
  }, { preserveScroll: true, onSuccess: () => toast.success('Vault preferences saved.') });
}
function savePrivacy() {
  router.put(route('cs.settings.privacy'), {
    level:    csPrivacy.level,
    search:   csPrivacy.search,
    location: csPrivacy.location,
    creds:    csPrivacy.creds,
  }, { preserveScroll: true, onSuccess: () => toast.success('Privacy settings saved.') });
}

onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  const tab    = params.get('tab');
  const anchor = params.get('anchor');
  if (tab) section.value = tab;
  if (anchor) {
    nextTick(() => {
      setTimeout(() => {
        const el = document.getElementById('settings-anchor-' + anchor);
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' });
          el.style.transition = 'background 0.3s';
          el.style.background = 'var(--icon-bg-gold)';
          setTimeout(() => { el.style.background = ''; }, 1200);
        }
      }, 150);
    });
  }
});
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

/* ── Shared PM / invoice styles ── */
.pm-card { display:flex;align-items:center;gap:14px;padding:14px 18px;border:1px solid var(--border);border-radius:var(--radius-lg);margin-bottom:10px;background:var(--surface);box-shadow:var(--shadow-sm); }
.pm-card.default { border-color:var(--gold-dark);background:var(--badge-bg-gold); }
.pm-logo { width:48px;height:32px;border-radius:var(--radius-sm);background:var(--surface-2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.pm-info { flex:1; }
.pm-name { font-size:13px;font-weight:700;color:var(--text); }
.pm-meta { font-size:12px;color:var(--text-3);margin-top:2px; }
.pm-card-btns { display:flex;gap:6px;align-items:center; }
.pm-default-icon { color:var(--gold-dark);flex-shrink:0; }
.sub-invoice-table .sub-inv-row { cursor:pointer; }
.sub-invoice-table .sub-inv-row:hover td { background:var(--surface-2); }
.sub-inv-product { font-size:13px;font-weight:600;color:var(--text); }
.sub-inv-desc { font-size:11px;color:var(--text-4);margin-top:2px;font-family:var(--font-mono,monospace); }
.tx-date { font-size:13px;color:var(--text-2);white-space:nowrap; }
.sub-inv-modal { display:flex;flex-direction:column;gap:0; }
.sim-header { display:flex;align-items:center;gap:14px;padding:18px 20px;background:var(--badge-bg-gold);border:1px solid var(--badge-border-gold);border-radius:var(--radius);margin-bottom:16px; }
.sim-logo { width:44px;height:44px;border-radius:var(--radius);background:var(--gold-dark);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.sim-brand { flex:1; }
.sim-from { font-family:var(--font-serif);font-size:16px;font-weight:700;color:var(--text); }
.sim-sub { font-size:12px;color:var(--text-3);margin-top:2px; }
.sim-status-block { text-align:right;flex-shrink:0; }
.sim-date { font-size:12px;color:var(--text-3);margin-top:5px; }
.sim-number-row { display:flex;align-items:center;gap:10px;margin-bottom:14px; }
.sim-number-label { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-4); }
.sim-number { font-family:var(--font-mono,monospace);font-size:13px;font-weight:600;color:var(--text-2);background:var(--surface-2);padding:3px 10px;border-radius:var(--radius-sm);border:1px solid var(--border); }
.sim-items { border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:14px; }
.sim-item { display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--surface); }
.sim-item-icon { width:32px;height:32px;border-radius:var(--radius-sm);background:var(--green-light);color:var(--green-dark);display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.sim-item-name { flex:1;font-size:14px;font-weight:600;color:var(--text); }
.sim-item-price { font-family:var(--font-serif);font-size:16px;font-weight:700;color:var(--text);white-space:nowrap; }
.sim-totals { border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:14px; }
.sim-total-row { display:flex;justify-content:space-between;align-items:center;padding:10px 16px;font-size:13px;color:var(--text-2);border-bottom:1px solid var(--border); }
.sim-total-row:last-child { border-bottom:none; }
.sim-total-row--main { font-family:var(--font-serif);font-size:16px;font-weight:700;color:var(--text);background:var(--surface-2);padding:14px 16px; }
.sim-fine-print { display:flex;align-items:center;gap:8px;font-size:11px;color:var(--text-4);padding:10px 14px;background:var(--surface-2);border-radius:var(--radius-sm);border:1px solid var(--border);line-height:1.5; }
.stripe-setup-card { border:1px solid var(--badge-border-gold);border-radius:var(--radius-lg);background:var(--icon-bg-gold);overflow:hidden; }
.stripe-setup-inner { display:flex;gap:16px;padding:18px 20px;align-items:flex-start; }
.stripe-setup-icon { width:44px;height:44px;border-radius:var(--radius);background:var(--gold-dark);color:var(--text-inverted);display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.stripe-setup-body { flex:1;min-width:0; }
.stripe-setup-title { font-family:var(--font-serif);font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px; }
.stripe-setup-desc { font-size:13px;color:var(--text-2);line-height:1.5;margin-bottom:12px; }
.stripe-setup-actions { display:flex;align-items:center;gap:12px;flex-wrap:wrap; }
.app-status-connected { font-size:12px;font-weight:600;color:var(--green-dark);background:var(--green-light);border:1px solid rgba(34,197,94,.35);padding:3px 10px;border-radius:var(--radius-full);display:inline-flex;align-items:center;gap:5px; }
.st-spin { animation:st-spin-kf 0.7s linear infinite;display:inline-block; }
@keyframes st-spin-kf { to { transform:rotate(360deg); } }
</style>