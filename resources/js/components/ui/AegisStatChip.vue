<!--
  AegisStatChip.vue — a single stat chip (icon + value + label).

  RULE: ALWAYS placed inside a .stat-chips-row that is a SIBLING of the
  .hero-banner — never inside the hero.

  Color tokens default to gold; pass icon backgrounds and stroke colors
  using CSS variables ONLY (never hex literals).
-->
<template>
  <div class="stat-chip" :data-tooltip="tooltip || null">
    <div
      class="stat-chip-icon"
      :style="{ background: bgColor, color: iconColor }"
    >
      <AegisIcon :name="icon" :size="18" />
    </div>
    <div class="stat-chip-content">
      <div class="stat-chip-value">{{ value }}</div>
      <div class="stat-chip-label">{{ label }}</div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  icon:      { type: String, required: true },
  value:     { type: [String, Number], required: true },
  label:     { type: String, required: true },
  bgColor:   { type: String, default: 'var(--icon-bg-gold)' },
  iconColor: { type: String, default: 'var(--gold-dark)' },
  tooltip:   { type: String, default: '' },
})
</script>
