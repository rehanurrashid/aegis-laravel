<!--
  AegisIcon.vue — single source of truth for every icon used in the app.

  Replaces aegis_icon() from icons.php. The full SVG body registry is in
  ./icons.js (auto-ported, 192 keys). All icons are rendered as Lucide-style
  outline icons (24×24 viewBox, 1.75px stroke) unless `filled` is true.

  Props:
    name     — required, icon key (e.g., 'shield', 'bell', 'check-circle')
    size     — px size; default 16 (matches Aegis prototype convention)
    filled   — flip stroke→fill (for filled badge variants)

  Unknown keys fall back to 'dot' with a console warning in dev — matches
  the PHP fallback behavior so layouts never explode.
-->
<template>
  <svg
    class="aegis-icon"
    :class="filled ? 'aegis-icon-filled' : null"
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    :fill="filled ? 'currentColor' : 'none'"
    :stroke="filled ? 'none' : 'currentColor'"
    stroke-width="1.75"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    v-html="body"
  />
</template>

<script setup>
import { computed } from 'vue'
import { ICONS, ICON_ALIASES } from './icons.js'

const props = defineProps({
  name:   { type: String, required: true },
  size:   { type: [Number, String], default: 16 },
  filled: { type: Boolean, default: false },
})

const body = computed(() => {
  // direct hit
  const svg = ICONS[props.name]
  if (svg) return svg
  // semantic alias (vault→lock, incident→alert-triangle, …)
  const aliased = ICON_ALIASES[props.name]
  if (aliased && ICONS[aliased]) return ICONS[aliased]
  if (import.meta.env.DEV) {
    // eslint-disable-next-line no-console
    console.warn(`AegisIcon: unknown icon "${props.name}" — falling back to "dot"`)
  }
  return ICONS['dot']
})
</script>
