<!--
  SubscriptionPlanCard.vue — shared cart-style plan summary card.

  Used in:
    - resources/js/pages/provider/Finances.vue  (showManageLink=true,  showInvoices=true)
    - resources/js/pages/provider/Settings.vue  (showManageLink=false, showInvoices=true)

  Props:
    subscription    Object  — from SubscriptionService::getFullSubscriptionData()
    invoices        Array   — from subscription.invoices
    showManageLink  Boolean — show "Manage Plan" button linking to Settings billing section
    showInvoices    Boolean — show invoice history below the cart
-->
<template>
  <div>
    <!-- ── Cart-style plan card ── -->
    <div class="sub-cart" style="margin-bottom:18px;">

      <!-- Header strip -->
      <div class="sub-cart-head">
        <div class="sub-cart-head-left">
          <div class="sub-cart-logo"><AegisIcon name="star" :size="18" /></div>
          <div>
            <div class="sub-cart-brand">Aegis Platform</div>
            <div class="sub-cart-type">Practice Continuity Subscription</div>
          </div>
        </div>
        <a
          v-if="showManageLink"
          :href="route('provider.settings.index') + '?section=billing'"
          class="btn btn-outline"
        >
          <AegisIcon name="external-link" :size="12" /> Manage Plan
        </a>
      </div>

      <!-- Line items -->
      <div class="sub-cart-items">

        <!-- Base plan -->
        <div class="sub-cart-item">
          <div class="sub-cart-item-icon"><AegisIcon name="check-circle" :size="16" /></div>
          <div class="sub-cart-item-info">
            <div class="sub-cart-item-name">
              {{ subscription?.tier === 'practice'
                  ? 'Continuity Practice'
                  : subscription?.tier === 'access'
                    ? 'Continuity Access'
                    : 'Aegis Plan' }}
            </div>
            <div class="sub-cart-item-desc">
              {{ subscription?.billing_interval === 'year'
                  ? 'Active — renews annually'
                  : subscription?.status === 'active'
                    ? 'Active — renews monthly'
                    : subscription?.status === 'past_due'
                      ? 'Payment past due'
                      : subscription?.status || 'Inactive' }}
            </div>
          </div>
          <div class="sub-cart-item-price">
            {{ subscription?.tier === 'practice' ? '$79' : subscription?.tier === 'access' ? '$39' : '—' }}
            <span class="sub-cart-item-per">/mo</span>
          </div>
          <AegisBadge
            :label="subscription?.status || 'inactive'"
            :variant="subscription?.status === 'active' ? 'green' : subscription?.status === 'past_due' ? 'red' : 'neutral'"
          />
        </div>

        <!-- MAAT Add-On -->
        <div v-if="subscription?.has_maat_addon" class="sub-cart-item sub-cart-item--addon">
          <div class="sub-cart-item-icon sub-cart-item-icon--gold"><AegisIcon name="shield" :size="16" /></div>
          <div class="sub-cart-item-info">
            <div class="sub-cart-item-name">MAAT Professional CS Add-on</div>
            <div class="sub-cart-item-desc">Certified Continuity Steward · 4-hr emergency response</div>
          </div>
          <div class="sub-cart-item-price">+$29<span class="sub-cart-item-per">/mo</span></div>
          <AegisBadge label="Active" variant="gold" />
        </div>

        <!-- Practice CS Add-On -->
        <div v-if="subscription?.has_cs_addon" class="sub-cart-item sub-cart-item--addon">
          <div class="sub-cart-item-icon sub-cart-item-icon--gold"><AegisIcon name="users" :size="16" /></div>
          <div class="sub-cart-item-info">
            <div class="sub-cart-item-name">Practice CS Add-On</div>
            <div class="sub-cart-item-desc">Serve as CS for up to 43 practitioners</div>
          </div>
          <div class="sub-cart-item-price">+$25<span class="sub-cart-item-per">/mo</span></div>
          <AegisBadge label="Active" variant="gold" />
        </div>

      </div>

      <!-- Total footer -->
      <div class="sub-cart-total">
        <div class="sub-cart-total-label">
          <AegisIcon name="credit-card" :size="13" />
          {{ subscription?.billing_interval === 'year' ? 'Billed annually to your default card' : 'Billed monthly to your default card' }}
        </div>
        <div class="sub-cart-total-amount">
          <template v-if="subscription?.next_invoice?.amount_cents">
            ${{ (subscription.next_invoice.amount_cents / 100).toFixed(2) }}
          </template>
          <template v-else>
            {{
              subscription?.tier === 'practice'
                ? (subscription?.has_maat_addon && subscription?.has_cs_addon ? '$133'
                   : subscription?.has_maat_addon ? '$108'
                   : subscription?.has_cs_addon   ? '$104'
                   : '$79')
                : subscription?.tier === 'access' ? '$39' : '—'
            }}
          </template>
          <span class="sub-cart-item-per">/mo</span>
        </div>
      </div>

      <!-- Grace / past-due alert -->
      <div v-if="subscription?.ends_at" class="sub-cart-alert">
        <AegisIcon name="alert-triangle" :size="14" />
        Subscription ends {{ formatSubscriptionDate(subscription.ends_at) }} — reactivate in Settings to maintain access.
      </div>

    </div>

    <!-- ── Invoice history ── -->
    <div v-if="showInvoices" style="margin-top:18px">
      <SettingsSubscriptionInvoices
        :invoices="invoices"
        portal-label="Practice Continuity Subscription"
      />
    </div>
  </div>
</template>

<script setup>
import SettingsSubscriptionInvoices from '@/components/settings/SettingsSubscriptionInvoices.vue'

defineProps({
  subscription:    { type: Object,  default: () => ({}) },
  invoices:        { type: Array,   default: () => [] },
  showManageLink:  { type: Boolean, default: true },
  showInvoices:    { type: Boolean, default: true },
})

function formatSubscriptionDate(val) {
  if (!val) return ''
  const d = typeof val === 'number' ? new Date(val * 1000) : new Date(val)
  if (isNaN(d.getTime())) return ''
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>
