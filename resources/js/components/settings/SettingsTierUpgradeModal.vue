<!--
  SettingsTierUpgradeModal.vue
  Upgrade prompt for Access-tier users hitting locked features.
-->
<template>
  <AegisModal
    v-model="localShow"
    title="Upgrade to Continuity Practice"
    size="md"
  >
    <!-- Top row: icon + desc side by side -->
    <div class="tum-top">
      <div class="tum-lock-icon">
        <AegisIcon name="lock" :size="20" />
      </div>
      <div class="tum-top-body">
        <p class="tum-desc">Designed for practitioners seeking expanded continuity support, interdisciplinary collaboration, and practice coordination tools.</p>
        <div v-if="lockedFeatureNote" class="tum-gate-note">
          <AegisIcon name="lock" :size="12" />
          <span><strong>{{ lockedFeatureNote }}</strong> requires Continuity Practice.</span>
        </div>
      </div>
    </div>

    <!-- Features — 2 columns -->
    <div class="tum-included">
      <div class="tum-included-label">Included with Continuity Practice:</div>
      <ul class="tum-feat-list">
        <li><AegisIcon name="check" :size="12" /> Full Integrative Network &amp; Referrals</li>
        <li><AegisIcon name="check" :size="12" /> My Services — peer-to-peer offerings</li>
        <li><AegisIcon name="check" :size="12" /> Support Requests &amp; Business Partners</li>
        <li><AegisIcon name="check" :size="12" /> Up to 2 Continuity Stewards (vs. 1)</li>
        <li><AegisIcon name="check" :size="12" /> Up to 4 Support Stewards (vs. 1)</li>
      </ul>
    </div>

    <!-- Price row -->
    <div class="tum-price-row">
      <div class="tum-price">
        <span class="tum-price-main">$49<span class="tum-price-unit">/mo</span></span>
        <span class="tum-price-or">or</span>
        <span class="tum-price-alt">$39/mo billed annually</span>
      </div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-gold" @click="goToBilling">
        <AegisIcon name="arrow-right" :size="13" /> Upgrade Now
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed } from 'vue'

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
/* Top row */
.tum-top        { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 14px; }
.tum-lock-icon  { width: 40px; height: 40px; border-radius: 50%; background: var(--icon-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px; }
.tum-top-body   { flex: 1; min-width: 0; }
.tum-desc       { font-size: 13px; color: var(--text-2); line-height: 1.55; margin: 0 0 8px; }
.tum-gate-note  { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--gold-dark); background: var(--badge-bg-gold); border: 1px solid var(--badge-border-gold); border-radius: var(--radius-sm); padding: 5px 10px; }
.tum-gate-note svg { flex-shrink: 0; }

/* Features */
.tum-included       { margin-bottom: 14px; }
.tum-included-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: var(--text-4); margin-bottom: 8px; }
.tum-feat-list      { margin: 0; padding: 0; list-style: none; display: grid; grid-template-columns: 1fr 1fr; gap: 6px 12px; }
.tum-feat-list li   { display: flex; align-items: flex-start; gap: 7px; font-size: 12px; color: var(--text-2); line-height: 1.45; }
.tum-feat-list li svg { color: var(--green-dark); flex-shrink: 0; margin-top: 2px; }

/* Price */
.tum-price-row  { display: flex; align-items: center; padding-top: 12px; border-top: 1px solid var(--border); }
.tum-price      { display: flex; align-items: baseline; gap: 6px; }
.tum-price-main { font-family: var(--font-serif); font-size: 20px; font-weight: 700; color: var(--text); }
.tum-price-unit { font-family: var(--font-sans); font-size: 12px; font-weight: 600; color: var(--text-3); }
.tum-price-or   { font-size: 12px; color: var(--text-4); }
.tum-price-alt  { font-size: 12px; font-weight: 600; color: var(--text-3); }
</style>
