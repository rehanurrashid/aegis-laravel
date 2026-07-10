<!--
  SettingsStripeConnect.vue — shared across Provider, CS (Business), BP portals.

  Props:
    connected       Boolean — props.user.stripe_connected
    onboardRoute    String  — e.g. 'provider.settings.connect.onboard'
    portalRoute     String  — e.g. 'provider.settings.billing.portal'
    description     String  — portal-specific description of what Connect enables
-->
<template>
  <div class="st-card">
    <div class="st-card-head">
      <div class="st-card-head-l">
        <span class="st-card-ico"><AegisIcon name="link" :size="17" /></span>
        <div>
          <div class="st-card-title">Stripe Connect</div>
          <div class="st-card-sub">Receive payments via your connected Stripe account</div>
        </div>
      </div>
      <span v-if="connected" class="ssc-status-connected">
        <AegisIcon name="check" :size="13" /> Connected
      </span>
    </div>

    <div class="st-card-body">
      <div class="ssc-card">
        <div class="ssc-inner">
          <div class="ssc-icon"><AegisIcon name="credit-card" :size="22" /></div>
          <div class="ssc-body">
            <div class="ssc-title">Stripe Connect Express</div>
            <div class="ssc-desc">{{ description }}</div>
            <div class="ssc-actions">
              <template v-if="connected">
                <a :href="route(portalRoute)" class="btn btn-outline" target="_blank">
                  <AegisIcon name="external-link" :size="12" /> Stripe Dashboard
                </a>
                <a :href="route(onboardRoute)" class="btn btn-outline">
                  <AegisIcon name="refresh-cw" :size="12" /> Reconnect
                </a>
              </template>
              <template v-else>
                <a :href="route(onboardRoute)" class="btn btn-primary">
                  <AegisIcon name="external-link" :size="13" /> Connect Stripe Account
                </a>
                <span class="ssc-hint">You'll be redirected to Stripe to complete setup</span>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  connected:    { type: Boolean, default: false },
  onboardRoute: { type: String, required: true },
  portalRoute:  { type: String, required: true },
  description:  { type: String, default: 'Connect your Stripe account to receive payments. Funds go directly to your bank account — Aegis never holds your money.' },
})
</script>

<style scoped>
.ssc-status-connected { font-size: 12px; font-weight: 600; color: var(--green-dark); background: var(--green-light); border: 1px solid rgba(34,197,94,0.35); padding: 3px 10px; border-radius: var(--radius-full); display: inline-flex; align-items: center; gap: 5px; }
.ssc-card  { border: 1px solid var(--badge-border-gold); border-radius: var(--radius-lg); background: var(--icon-bg-gold); overflow: hidden; }
.ssc-inner { display: flex; gap: 16px; padding: 18px 20px; align-items: flex-start; }
.ssc-icon  { width: 44px; height: 44px; border-radius: var(--radius); background: var(--gold-dark); color: var(--text-inverted); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ssc-body  { flex: 1; min-width: 0; }
.ssc-title { font-family: var(--font-serif); font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
.ssc-desc  { font-size: 13px; color: var(--text-2); line-height: 1.5; margin-bottom: 12px; }
.ssc-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.ssc-hint  { font-size: 12px; color: var(--text-4); }
</style>
