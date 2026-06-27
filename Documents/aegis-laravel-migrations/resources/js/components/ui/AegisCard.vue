<!--
  AegisCard.vue — standard card wrapper.

  Uses .card / .card-header / .card-body / .card-footer from _shared.css.
  Header is composed of three pieces:
    • `title` prop → rendered as .card-title (plain text)
    • optional `eyebrow` prop above the title
    • `actions` slot on the right (buttons, links)

  All sections are optional — passing only the default slot renders a
  bare card body. For "quiet" cards (subdued surfaces), set quiet=true.
-->
<template>
  <section class="card" :class="cardClass">
    <header
      v-if="title || $slots.actions || eyebrow"
      class="card-header"
    >
      <div class="card-header-text">
        <div v-if="eyebrow" class="card-header-eyebrow">{{ eyebrow }}</div>
        <h2 v-if="title" class="card-title">{{ title }}</h2>
      </div>
      <div v-if="$slots.actions" class="card-header-actions">
        <slot name="actions" />
      </div>
    </header>

    <div class="card-body">
      <slot />
    </div>

    <footer v-if="$slots.footer" class="card-footer">
      <slot name="footer" />
    </footer>
  </section>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title:   { type: String, default: '' },
  eyebrow: { type: String, default: '' },
  quiet:   { type: Boolean, default: false },
  flush:   { type: Boolean, default: false }, // remove body padding
})

const cardClass = computed(() => ({
  'card--quiet': props.quiet,
  'card--flush': props.flush,
}))
</script>
