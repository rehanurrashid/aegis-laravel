<!--
  SecurityChecklistStrip.vue — account security progress strip.

  Renders only when pct < 100. Accent colour shifts from gold (warning)
  to red (critical) when pct < 50. Used on Provider Dashboard and
  Provider Settings (Security & 2FA section).

  Usage:
    <SecurityChecklistStrip
      :pct="securityCompletion"
      :subtitle="`${securityItemsRemaining} items remaining — ${securityNextItem}`"
      :edit-href="route('provider.settings.security')"
    />
-->
<template>
  <div v-if="pct < 100" class="security-strip" :class="`security-strip--${severity}`">
    <div class="security-strip-icon">
      <AegisIcon name="shield" :size="16" />
    </div>
    <div class="security-strip-text">
      <div class="security-strip-title">Secure your account</div>
      <div class="security-strip-sub">{{ subtitle }}</div>
    </div>
    <div class="security-strip-bar">
      <div class="security-strip-bar-fill" :style="{ width: pct + '%' }"></div>
    </div>
    <div class="security-strip-pct">{{ pct }}%</div>
    <a class="btn btn-primary btn-sm" :href="editHref" style="flex-shrink:0;white-space:nowrap">
      Review Security <AegisIcon name="arrow-right-line" :size="12" />
    </a>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  pct:      { type: Number, default: 0 },
  subtitle: { type: String, default: 'Complete security setup to protect your account' },
  editHref: { type: String, default: '#' },
})

const severity = computed(() => props.pct < 50 ? 'critical' : 'warning')
</script>

<style scoped>
.security-strip {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 18px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  border-left-width: 3px;
  margin-bottom: 18px;
  background: var(--surface);
}

/* warning — gold accent */
.security-strip--warning {
  border-left-color: var(--gold-dark);
}
.security-strip--warning .security-strip-icon {
  background: var(--icon-bg-gold);
  color: var(--gold-dark);
}
.security-strip--warning .security-strip-bar-fill {
  background: var(--gold-dark);
}
.security-strip--warning .security-strip-pct {
  color: var(--gold-dark);
}

/* critical — red accent */
.security-strip--critical {
  border-left-color: var(--red);
}
.security-strip--critical .security-strip-icon {
  background: var(--red-light, #fef2f2);
  color: var(--red);
}
.security-strip--critical .security-strip-bar-fill {
  background: var(--red);
}
.security-strip--critical .security-strip-pct {
  color: var(--red);
}

.security-strip-icon {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.security-strip-text {
  flex: 1;
  min-width: 0;
}
.security-strip-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
  line-height: 1.3;
}
.security-strip-sub {
  font-size: 12px;
  color: var(--text-3);
  margin-top: 2px;
}

.security-strip-bar {
  width: 80px;
  height: 4px;
  background: var(--surface-3);
  border-radius: var(--radius-full);
  flex-shrink: 0;
  overflow: hidden;
}
.security-strip-bar-fill {
  height: 100%;
  border-radius: var(--radius-full);
  transition: width 0.3s ease;
}

.security-strip-pct {
  font-size: 13px;
  font-weight: 700;
  min-width: 36px;
  text-align: right;
  flex-shrink: 0;
}
</style>
