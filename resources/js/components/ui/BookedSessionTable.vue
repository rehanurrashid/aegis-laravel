<!--
  BookedSessionTable.vue — session table for "Sessions I've Booked" (client viewpoint).

  Mirrors SessionTable.vue but:
    - viewpoint is always 'client'
    - Supports server-side pagination via :meta + @page-change
      (Services.vue uses server-side; Finances.vue uses client-side)
    - Client actions: pay-deposit, pay-balance, request-refund, escalate-refund
    - No show-notes / show-invoice / show-cancel (those are provider-only)

  Props:
    sessions     — session array (current page if server-side, full array if client-side)
    meta         — optional server-side pagination meta { current_page, last_page, total, per_page }
    emptyTitle   — empty state title
    emptySubtitle— empty state subtitle
    showSearch   — show search input (default true, only works client-side)
    pageSize     — rows per page for client-side pagination (default 8)

  Emits:
    pay-deposit, pay-balance, request-refund, escalate-refund
    page-change(n) — emitted when using server-side pagination (meta provided)

  Slots:
    empty — action slot inside empty state (e.g. "Browse Services" button)
-->
<template>
  <div class="session-table-root">

    <!-- ── TOOLBAR: search + filter chips ──────────────────────── -->
    <div class="st-toolbar">
      <!-- Search (only meaningful for client-side mode) -->
      <div v-if="showSearch && !isServerSide" class="st-search-wrap">
        <AegisIcon name="search" :size="14" />
        <input
          v-model="search"
          type="text"
          class="form-control st-search"
          placeholder="Search providers or services…"
        />
        <button v-if="search" type="button" class="st-search-clear" @click="search = ''">
          <AegisIcon name="x" :size="12" />
        </button>
      </div>

      <!-- Filter chips -->
      <nav class="tabs-segmented" role="tablist" style="width:fit-content;max-width:100%">
        <button
          v-for="chip in filterChips"
          :key="chip.value"
          type="button"
          role="tab"
          class="tab-pill"
          :class="{ active: activeFilter === chip.value }"
          :aria-selected="activeFilter === chip.value"
          @click="activeFilter = chip.value; currentPage = 1"
        >
          {{ chip.label }}
          <span v-if="chip.count > 0" class="sessions-chip-count">{{ chip.count }}</span>
        </button>
      </nav>
    </div>

    <!-- ── EMPTY STATES ─────────────────────────────────────────── -->
    <AegisEmptyState
      v-if="!sessions.length"
      icon="calendar"
      :title="emptyTitle"
      :subtitle="emptySubtitle"
    >
      <template v-if="$slots.empty" #action><slot name="empty" /></template>
    </AegisEmptyState>

    <AegisEmptyState
      v-else-if="!filtered.length"
      icon="filter"
      title="No sessions match this filter"
      subtitle="Try selecting a different filter or clearing the search."
    />

    <!-- ── TABLE ────────────────────────────────────────────────── -->
    <template v-else>
      <div class="sic-table-wrap">
        <table class="sic-table">
          <thead>
            <tr>
              <th class="sic-th">Provider</th>
              <th class="sic-th">Status</th>
              <th class="sic-th"></th>
            </tr>
          </thead>
          <tbody>
            <SessionInvoiceCard
              v-for="ses in paged"
              :key="ses.id"
              :session="ses"
              viewpoint="client"
              @pay-deposit="$emit('pay-deposit', ses)"
              @pay-balance="$emit('pay-balance', ses)"
              @request-refund="$emit('request-refund', ses)"
              @escalate-refund="$emit('escalate-refund', ses)"
            />
          </tbody>
        </table>
      </div>

      <!-- Server-side pagination (Services.vue mode) -->
      <AegisPagination
        v-if="isServerSide && meta.last_page > 1"
        :current-page="meta.current_page ?? 1"
        :total-pages="meta.last_page ?? 1"
        :total="meta.total ?? sessions.length"
        :from="sessions.length ? ((meta.current_page - 1) * meta.per_page) + 1 : 0"
        :to="Math.min(meta.current_page * meta.per_page, meta.total ?? sessions.length)"
        :show-meta="true"
        style="margin-top:12px"
        @change="$emit('page-change', $event)"
      />

      <!-- Client-side pagination (Finances.vue mode) -->
      <AegisPagination
        v-else-if="!isServerSide && filtered.length > pageSize"
        :current-page="currentPage"
        :total-pages="totalPages"
        :total="filtered.length"
        :from="(currentPage - 1) * pageSize + 1"
        :to="Math.min(currentPage * pageSize, filtered.length)"
        :show-meta="true"
        style="margin-top:12px"
        @change="currentPage = $event"
      />
    </template>

  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import SessionInvoiceCard from '@/components/ui/SessionInvoiceCard.vue'

const props = defineProps({
  sessions:      { type: Array,  default: () => [] },
  meta:          { type: Object, default: null },   // null = client-side pagination
  emptyTitle:    { type: String, default: 'No booked sessions' },
  emptySubtitle: { type: String, default: '' },
  showSearch:    { type: Boolean, default: true },
  pageSize:      { type: Number,  default: 8 },
})

defineEmits(['pay-deposit', 'pay-balance', 'request-refund', 'escalate-refund', 'page-change'])

// Server-side mode when meta is provided
const isServerSide = computed(() => !!props.meta)

// ── State ─────────────────────────────────────────────────────────────────────
const search       = ref('')
const activeFilter = ref('all')
const currentPage  = ref(1)

watch([search, activeFilter], () => { currentPage.value = 1 })

defineExpose({
  reset() {
    search.value       = ''
    activeFilter.value = 'all'
    currentPage.value  = 1
  },
})

// ── Computed ──────────────────────────────────────────────────────────────────
function applyFilter(sessions, filter) {
  if (filter === 'all')         return sessions
  if (filter === 'deposit_due') return sessions.filter(s => s.payment_status === 'unpaid'       && s.status === 'scheduled')
  if (filter === 'balance_due') return sessions.filter(s => s.payment_status === 'deposit_paid' && s.status === 'scheduled')
  if (filter === 'paid')        return sessions.filter(s => s.payment_status === 'paid')
  if (filter === 'refunded')    return sessions.filter(s => ['refunded', 'partially_refunded'].includes(s.payment_status))
  return sessions
}

function applySearch(sessions, q) {
  if (!q.trim()) return sessions
  const lq = q.toLowerCase()
  return sessions.filter(s =>
    (s.practitioner_name ?? '').toLowerCase().includes(lq)
    || (s.service_title ?? '').toLowerCase().includes(lq)
    || (s.invoice_number ?? '').toLowerCase().includes(lq)
  )
}

// In server-side mode, filter/search on the current page only
const filtered = computed(() =>
  isServerSide.value
    ? props.sessions   // server handles filtering; client chips are visual only
    : applySearch(applyFilter(props.sessions, activeFilter.value), search.value)
)

const paged = computed(() => {
  if (isServerSide.value) return filtered.value
  const start = (currentPage.value - 1) * props.pageSize
  return filtered.value.slice(start, start + props.pageSize)
})

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / props.pageSize)))

// Counts always come from the full sessions prop
const filterChips = computed(() => {
  const s = props.sessions
  return [
    { value: 'all',         label: 'All' },
    { value: 'deposit_due', label: 'Deposit Due',  count: s.filter(x => x.payment_status === 'unpaid'       && x.status === 'scheduled').length },
    { value: 'balance_due', label: 'Balance Due',  count: s.filter(x => x.payment_status === 'deposit_paid' && x.status === 'scheduled').length },
    { value: 'paid',        label: 'Paid',         count: s.filter(x => x.payment_status === 'paid').length },
    { value: 'refunded',    label: 'Refunded',     count: s.filter(x => ['refunded', 'partially_refunded'].includes(x.payment_status)).length },
  ]
})
</script>

<style scoped>
.session-table-root { display: flex; flex-direction: column; gap: 12px; }

.st-toolbar { display: flex; flex-direction: column; gap: 10px; }

.st-search-wrap { position: relative; display: flex; align-items: center; }
.st-search-wrap .aegis-icon:first-child {
  position: absolute; left: 11px; color: var(--text-4); pointer-events: none;
}
.st-search { padding-left: 34px !important; padding-right: 32px !important; width: 100%; font-size: 13px; }
.st-search-clear {
  position: absolute; right: 9px;
  background: none; border: none; cursor: pointer;
  color: var(--text-4); display: flex; align-items: center; padding: 2px;
}
.st-search-clear:hover { color: var(--text); }

.sic-table-wrap { border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
.sic-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.sic-th {
  padding: 9px 12px; text-align: left;
  font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
  color: var(--text-4); background: var(--surface-2); border-bottom: 1px solid var(--border);
}
</style>
