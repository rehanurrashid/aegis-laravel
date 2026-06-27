<!--
  AegisHeroBanner.vue — the standard page hero.

  RULE: stat chips go BESIDE this banner as a sibling .stat-chips-row,
  NEVER inside .hero-banner. The design system reserves the hero for
  eyebrow + title + subtitle + actions only.

  Use `quiet` for subdued banners (settings pages, secondary surfaces).
-->
<template>
  <section class="hero-banner" :class="{ 'is-quiet': quiet }">
    <div class="page-hero-inner">
      <div class="page-hero-text">
        <div v-if="eyebrow" class="page-hero-eyebrow">{{ eyebrow }}</div>
        <h1 class="page-hero-title">{{ title }}</h1>
        <p v-if="subtitle" class="page-hero-sub">{{ subtitle }}</p>
      </div>
      <div v-if="$slots.actions" class="page-hero-actions">
        <slot name="actions" />
      </div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  eyebrow:  { type: String, default: '' },
  title:    { type: String, required: true },
  subtitle: { type: String, default: '' },
  quiet:    { type: Boolean, default: false },
})
</script>
