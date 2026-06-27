<!--
  AegisPagination.vue — pagination controls.

  Designed to drop in over Laravel's standard paginator metadata:
  { current_page, last_page, per_page, total, from, to, links }.
  Pass the whole paginator meta as `paginator` or the raw fields.

  Emits `change(page)` for SPA-style pagination via Inertia.router.get(...)
  Use `as-links` mode to render anchors via Ziggy.
-->
<template>
  <nav v-if="totalPages > 1" class="pagination" aria-label="Pagination">
    <button
      type="button"
      class="pagination-btn"
      :disabled="currentPage <= 1"
      @click="go(currentPage - 1)"
    >
      <AegisIcon name="chevron-left" :size="14" />
      <span>Previous</span>
    </button>

    <ol class="pagination-pages">
      <li v-for="p in visiblePages" :key="p.key" class="pagination-page">
        <span v-if="p.gap" class="pagination-gap">…</span>
        <button
          v-else
          type="button"
          class="pagination-page-btn"
          :class="{ 'is-active': p.value === currentPage }"
          @click="go(p.value)"
        >{{ p.value }}</button>
      </li>
    </ol>

    <button
      type="button"
      class="pagination-btn"
      :disabled="currentPage >= totalPages"
      @click="go(currentPage + 1)"
    >
      <span>Next</span>
      <AegisIcon name="chevron-right" :size="14" />
    </button>

    <div v-if="showMeta && total > 0" class="pagination-meta">
      {{ from }}–{{ to }} of {{ total }}
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  currentPage: { type: Number, default: 1 },
  totalPages:  { type: Number, default: 1 },
  total:       { type: Number, default: 0 },
  from:        { type: Number, default: 0 },
  to:          { type: Number, default: 0 },
  showMeta:    { type: Boolean, default: true },
})

const emit = defineEmits(['change'])

function go(page) {
  if (page < 1 || page > props.totalPages || page === props.currentPage) return
  emit('change', page)
}

// Build a "1 … 4 5 [6] 7 8 … 20" page list.
const visiblePages = computed(() => {
  const out = []
  const cur = props.currentPage
  const last = props.totalPages

  const window = new Set([1, last, cur - 1, cur, cur + 1])
  const sorted = [...window].filter((n) => n >= 1 && n <= last).sort((a, b) => a - b)

  let prev = 0
  for (const n of sorted) {
    if (n - prev > 1) out.push({ key: `gap-${n}`, gap: true })
    out.push({ key: `p-${n}`, value: n })
    prev = n
  }
  return out
})
</script>
