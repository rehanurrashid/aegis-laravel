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
              {{ subscription?.status === 'active'
                  ? (billingInterval === 'annual' ? 'Active — renews annually' : 'Active — renews monthly')
                  : subscription?.status === 'past_due'
                    ? 'Payment past due'
                    : subscription?.status || 'Inactive' }}
            </div>
          </div>
          <div class="sub-cart-item-price">
            {{ pricingStore.formatCents(basePriceCents) }}<span class="sub-cart-item-per">/mo</span>
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
          <div class="sub-cart-item-price">+{{ pricingStore.formatCents(maatCents) }}<span class="sub-cart-item-per">/mo</span></div>
          <AegisBadge label="Active" variant="gold" />
        </div>

        <!-- Practice CS Add-On -->
        <div v-if="subscription?.has_cs_addon" class="sub-cart-item sub-cart-item--addon">
          <div class="sub-cart-item-icon sub-cart-item-icon--gold"><AegisIcon name="users" :size="16" /></div>
          <div class="sub-cart-item-info">
            <div class="sub-cart-item-name">Practice CS Add-On</div>
            <div class="sub-cart-item-desc">Serve as CS for up to 43 practitioners</div>
          </div>
          <div class="sub-cart-item-price">+{{ pricingStore.formatCents(csAddonCents) }}<span class="sub-cart-item-per">/mo</span></div>
          <AegisBadge label="Active" variant="gold" />
        </div>

      </div>

      <!-- Total footer -->
      <div class="sub-cart-total">
        <div class="sub-cart-total-label">
          <AegisIcon name="credit-card" :size="13" />
          {{ billingInterval === 'annual' ? 'Billed annually to your default card' : 'Billed monthly to your default card' }}
        </div>
        <div class="sub-cart-total-amount">
          {{ pricingStore.formatCents(totalCents) }}<span class="sub-cart-item-per">/mo</span>
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
import { computed } from 'vue'
import SettingsSubscriptionInvoices from '@/components/settings/SettingsSubscriptionInvoices.vue'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  subscription:    { type: Object,  default: () => ({}) },
  invoices:        { type: Array,   default: () => [] },
  showManageLink:  { type: Boolean, default: true },
  showInvoices:    { type: Boolean, default: true },
  billingInterval: { type: String,  default: 'monthly' },
  pricing:         { type: Object,  default: () => ({}) },
})

const pricingStore = usePricingStore()

const isAnnual = computed(() => props.billingInterval === 'annual')

const basePriceCents = computed(() => {
  const tier = props.subscription?.tier
  if (tier === 'practice_business' || tier === 'practice_cs_addon') {
    // Practice base + CS Add-On as two separate amounts — never a bundled figure
    const practiceBase = isAnnual.value
      ? (pricingStore.getTier('practice')?.annual ?? 6583)
      : (pricingStore.getTier('practice')?.monthly ?? 7900)
    const csAddon = isAnnual.value
      ? (props.pricing?.practitioner?.practice_business?.annual_cents ?? 2083)
      : (props.pricing?.practitioner?.practice_business?.monthly_cents ?? 2500)
    return practiceBase + csAddon
  }
  const t = pricingStore.getTier(tier)
  return t?.monthly ?? 0
})

const maatCents   = computed(() =>
    isAnnual.value
        ? (props.pricing?.maat_addon?.annual_cents      ?? 2300)
        : (props.pricing?.maat_addon?.monthly_cents     ?? 2900)
)
const csAddonCents = computed(() =>
    isAnnual.value
        ? (props.pricing?.practice_cs_addon?.annual_cents  ?? 2083)
        : (props.pricing?.practice_cs_addon?.monthly_cents ?? 2500)
)

const totalCents = computed(() => {
  if (props.subscription?.next_invoice?.amount_cents) {
    return props.subscription.next_invoice.amount_cents
  }
  const tier = props.subscription?.tier
  let total = basePriceCents.value
  if (props.subscription?.has_maat_addon) total += maatCents.value
  // cs_addon already baked into basePriceCents for practice_business/practice_cs_addon
  if (props.subscription?.has_cs_addon && tier !== 'practice_business' && tier !== 'practice_cs_addon') {
    total += csAddonCents.value
  }
  return total
})

function formatSubscriptionDate(val) {
  if (!val) return ''
  const d = typeof val === 'number' ? new Date(val * 1000) : new Date(val)
  if (isNaN(d.getTime())) return ''
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<style scoped>
.sub-cart             { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); }
.sub-cart-head        { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 18px 22px; background: var(--badge-bg-gold); border-bottom: 1px solid var(--badge-border-gold); }
.sub-cart-head-left   { display: flex; align-items: center; gap: 12px; }
.sub-cart-logo        { width: 40px; height: 40px; border-radius: var(--radius); background: var(--gold-dark); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sub-cart-brand       { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); line-height: 1.2; }
.sub-cart-type        { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.sub-cart-items       { padding: 8px 0; }
.sub-cart-item        { display: flex; align-items: center; gap: 14px; padding: 14px 22px; border-bottom: 1px solid var(--border); }
.sub-cart-item:last-child { border-bottom: none; }
.sub-cart-item--addon { background: var(--surface-2); }
.sub-cart-item-icon   { width: 34px; height: 34px; border-radius: var(--radius-sm); background: var(--green-light); color: var(--green-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sub-cart-item-icon--gold { background: var(--badge-bg-gold); color: var(--gold-dark); }
.sub-cart-item-info   { flex: 1; min-width: 0; }
.sub-cart-item-name   { font-size: 14px; font-weight: 700; color: var(--text); }
.sub-cart-item-desc   { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.sub-cart-item-price  { font-family: var(--font-serif); font-size: 20px; font-weight: 700; color: var(--text); white-space: nowrap; margin-right: 10px; }
.sub-cart-item-per    { font-family: var(--font-sans); font-size: 12px; font-weight: 400; color: var(--text-3); }
.sub-cart-total       { display: flex; align-items: center; justify-content: space-between; padding: 14px 22px; background: var(--surface-2); border-top: 1px solid var(--border); }
.sub-cart-total-label { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-3); font-weight: 600; }
.sub-cart-total-amount { font-family: var(--font-serif); font-size: 22px; font-weight: 700; color: var(--text); }
.sub-cart-alert       { display: flex; align-items: center; gap: 8px; padding: 10px 22px; background: var(--orange-light); border-top: 1px solid var(--soft-gold); font-size: 12px; color: var(--orange-dark); font-weight: 600; }
</style>
