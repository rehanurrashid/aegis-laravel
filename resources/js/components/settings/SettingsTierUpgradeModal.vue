<!--
  SettingsTierUpgradeModal.vue
  Shown when an Access-tier user clicks a locked settings panel nav item.
  Mirrors the pasted _tierUpgradeModal HTML exactly, converted to Vue.
  Emits @upgrade to navigate to billing section.
-->
<template>
  <Teleport to="body">
    <div
      v-if="show"
      role="dialog"
      aria-modal="true"
      aria-label="Upgrade your plan"
      style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.45);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;padding:20px;"
      @click.self="close"
    >
      <div style="background:var(--surface);border-radius:var(--radius-xl);max-width:440px;width:100%;padding:28px 28px 24px;box-shadow:0 20px 60px rgba(0,0,0,0.18);position:relative;">
        <!-- Close -->
        <button
          aria-label="Close"
          @click="close"
          style="position:absolute;top:14px;right:14px;width:28px;height:28px;border-radius:6px;border:none;background:var(--surface-3,var(--surface-2));cursor:pointer;font-size:16px;color:var(--text-3);display:flex;align-items:center;justify-content:center;"
        >×</button>

        <!-- Lock icon -->
        <div style="width:44px;height:44px;border-radius:50%;background:rgba(196,169,106,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
          <AegisIcon name="lock" :size="20" style="color:var(--gold-dark)" />
        </div>

        <!-- Title -->
        <div style="font-family:var(--font-serif);font-size:18px;font-weight:700;color:var(--text);margin-bottom:8px;">
          Upgrade to Continuity Practice
        </div>

        <!-- Description -->
        <div style="font-size:13px;color:var(--text-2);line-height:1.6;margin-bottom:14px;">
          Designed for practitioners seeking expanded continuity support, interdisciplinary collaboration, and practice coordination tools.
        </div>

        <!-- Feature note for locked section -->
        <div v-if="lockedFeatureNote" style="font-size:13px;color:var(--text-2);line-height:1.6;margin-bottom:14px;padding:10px 12px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius);">
          <AegisIcon name="lock" :size="13" style="color:var(--gold-dark);margin-right:4px;vertical-align:middle;" />
          <strong>{{ lockedFeatureNote }}</strong> requires Continuity Practice.
        </div>

        <!-- What's included -->
        <div style="font-size:13px;color:var(--text-2);line-height:1.6;margin-bottom:16px;">
          <div style="font-weight:700;color:var(--text);margin-bottom:6px;">Included with Continuity Practice:</div>
          <ul style="margin:0 0 0 16px;display:flex;flex-direction:column;gap:4px;">
            <li>Full Integrative Network &amp; Referrals</li>
            <li>My Services — practitioner-to-practitioner offerings</li>
            <li>Support Requests &amp; Business Partnerships</li>
            <li>Up to 2 Continuity Stewards (vs. 1 on Access)</li>
            <li>Up to 4 Support Stewards (vs. 1 on Access)</li>
          </ul>
        </div>

        <!-- Price -->
        <div style="font-family:var(--font-serif);font-size:20px;font-weight:700;color:var(--text);margin-bottom:18px;">
          $49<span style="font-family:var(--font-sans);font-size:13px;font-weight:600;color:var(--text-3);">/month</span>
          <span style="font-size:12px;font-weight:600;color:var(--text-3);margin-left:8px;">or $39/mo billed annually</span>
        </div>

        <!-- CTAs -->
        <div style="display:flex;gap:10px;">
          <button
            type="button"
            @click="goToBilling"
            style="flex:1;display:inline-flex;align-items:center;justify-content:center;padding:10px 18px;border-radius:var(--radius);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-sans);font-size:13px;font-weight:700;border:none;cursor:pointer;text-decoration:none;"
          >Upgrade Now →</button>
          <button
            type="button"
            @click="close"
            style="padding:10px 16px;border-radius:var(--radius);border:1px solid var(--border);background:none;font-family:var(--font-sans);font-size:13px;font-weight:600;color:var(--text-3);cursor:pointer;"
          >Not now</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
const props = defineProps({
  show:              { type: Boolean, default: false },
  lockedFeatureNote: { type: String,  default: '' },
  billingSection:    { type: String,  default: 'billing' },
});

const emit = defineEmits(['update:show', 'upgrade']);

function close() {
  emit('update:show', false);
}

function goToBilling() {
  close();
  emit('upgrade');
}
</script>
