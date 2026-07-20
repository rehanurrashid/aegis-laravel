<!--
  SettingsTierUpgradeModal.vue
  Upgrade prompt for Access-tier users hitting locked features.
  Uses AegisModal for global design compliance — no custom overlay.
  Emits @upgrade to navigate to billing section.
-->
<template>
  <AegisModal
    v-model="localShow"
    title="Upgrade to Continuity Practice"
    size="md"
  >
    <!-- Lock badge + description -->
    <div class="tum-header">
      <div class="tum-lock-icon">
        <AegisIcon name="lock" :size="20" />
      </div>
      <p class="tum-desc">
        Designed for practitioners seeking expanded continuity support, interdisciplinary collaboration, and practice coordination tools.
      </p>
    </div>

    <!-- Locked feature note -->
    <div v-if="lockedFeatureNote" class="alert alert-gold tum-feature-note">
      <div class="alert-icon"><AegisIcon name="lock" :size="15" /></div>
      <div class="alert-content">
        <strong>{{ lockedFeatureNote }}</strong> requires Continuity Practice.
      </div>
    </div>

    <!-- What's included -->
    <div class="tum-included">
      <div class="tum-included-label">Included with Continuity Practice:</div>
      <ul class="tum-feat-list">
        <li><AegisIcon name="check" :size="12" /> Full Integrative Network &amp; Referrals</li>
        <li><AegisIcon name="check" :size="12" /> My Services — practitioner-to-practitioner offerings</li>
        <li><AegisIcon name="check" :size="12" /> Support Requests &amp; Business Partnerships</li>
        <li><AegisIcon name="check" :size="12" /> Up to 2 Continuity Stewards (vs. 1 on Access)</li>
        <li><AegisIcon name="check" :size="12" /> Up to 4 Support Stewards (vs. 1 on Access)</li>
      </ul>
    </div>

    <!-- Price -->
    <div class="tum-price">
      <span class="tum-price-main">{{ pricingStore.formatCents(pricingStore.getTier('practice')?.monthly ?? 7900) }}<span class="tum-price-unit">/month</span></span>
      <span class="tum-price-alt">or {{ pricingStore.formatCents(pricingStore.getTier('practice')?.annual ?? 6600) }}/mo billed annually</span>
    </div>

    <!-- Footer -->
    <template #footer>
      <button type="button" class="btn btn-gold" @click="goToBilling">
        <AegisIcon name="arrow-right" :size="13" /> Upgrade Now
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed } from 'vue'
import { usePricingStore } from '@/stores/pricing'

const pricingStore = usePricingStore()

const props = defineProps({
  show:              { type: Boolean, default: false },
  lockedFeatureNote: { type: String,  default: '' },
})

const emit = defineEmits(['update:show', 'upgrade'])

const localShow = computed({
  get: () => props.show,
  set: (v) => emit('update:show', v),
})

function goToBilling() {
  emit('update:show', false)
  emit('upgrade')
}
</script>

<style scoped>
.tum-header      { display: flex; flex-direction: column; align-items: flex-start; gap: 10px; margin-bottom: 16px; }
.tum-lock-icon   { width: 44px; height: 44px; border-radius: 50%; background: var(--icon-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.tum-desc        { font-size: 13px; color: var(--text-2); line-height: 1.6; margin: 0; }
.tum-feature-note { margin-bottom: 16px; }
.tum-included    { margin-bottom: 16px; }
.tum-included-label { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
.tum-feat-list   { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 6px; }
.tum-feat-list li { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-2); }
.tum-feat-list li svg { color: var(--green-dark); flex-shrink: 0; }
.tum-price       { display: flex; align-items: baseline; gap: 8px; padding-top: 14px; border-top: 1px solid var(--border); }
.tum-price-main  { font-family: var(--font-serif); font-size: 22px; font-weight: 700; color: var(--text); }
.tum-price-unit  { font-family: var(--font-sans); font-size: 13px; font-weight: 600; color: var(--text-3); }
.tum-price-alt   { font-size: 12px; font-weight: 600; color: var(--text-3); }
</style>
