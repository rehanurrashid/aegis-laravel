<!--
  SessionTable.vue — centralized session table with filter chips + search + pagination.

  Used in:
    - Finances.vue  (client sessions + provider sessions)
    - Services.vue  (Bookings & Sessions tab)

  Props:
    sessions     — raw session array (unfiltered)
    viewpoint    — 'client' | 'provider'
    emptyIcon    — AegisIcon name for empty state
    emptyTitle   — empty state title
    emptySubtitle— empty state subtitle
    showSearch   — show search input (default true)
    showNotes    — pass to SessionInvoiceCard (Services.vue only)
    showInvoice  — pass to SessionInvoiceCard (Services.vue only)
    showCancel   — pass to SessionInvoiceCard (Services.vue only)
    pageSize     — rows per page (default 8)

  Emits (forwarded from SessionInvoiceCard):
    pay-deposit, pay-balance, request-refund, escalate-refund,
    review-refund, open-notes, open-invoice, cancel-session
-->
<template>
  <div class="session-table-root">

    <!-- ── TOOLBAR: search + filter chips ──────────────────────── -->
    <div class="st-toolbar">
      <!-- Search -->
      <div v-if="showSearch" class="st-search-wrap">
        <AegisIcon name="search" :size="14" />
        <input
          v-model="search"
          type="text"
          class="form-control st-search"
          :placeholder="viewpoint === 'client' ? 'Search providers or services…' : 'Search clients or services…'"
        />
        <button v-if="search" type="button" class="st-search-clear" @click="search = ''">
          <AegisIcon name="x" :size="12" />
        </button>
      </div>

      <!-- Filter chips -->
      <nav class="tabs-segmented" role="tablist">
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
      :icon="emptyIcon"
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
              <th class="sic-th">{{ viewpoint === 'client' ? 'Provider' : 'Client' }}</th>
              <th class="sic-th">Status</th>
              <th class="sic-th"></th>
            </tr>
          </thead>
          <tbody>
            <SessionInvoiceCard
              v-for="ses in paged"
              :key="ses.id"
              :session="ses"
              :viewpoint="viewpoint"
              :show-notes="showNotes"
              :show-invoice="showInvoice"
              :show-cancel="showCancel"
              @pay-deposit="$emit('pay-deposit', ses)"
              @pay-balance="$emit('pay-balance', ses)"
              @request-refund="$emit('request-refund', ses)"
              @escalate-refund="$emit('escalate-refund', ses)"
              @review-refund="$emit('review-refund', ses)"
              @open-notes="$emit('open-notes', ses)"
              @open-invoice="$emit('open-invoice', ses)"
              @cancel-session="$emit('cancel-session', ses)"
            />
          </tbody>
        </table>
      </div>

      <AegisPagination
        v-if="filtered.length > pageSize"
        :current-page="currentPage"
        :total-pages="totalPages"
        :total="filtered.length"
        :from="(currentPage - 1) * pageSize + 1"
        :to="Math.min(currentPage * pageSize, filtered.length)"
        :show-meta="true"
        style="margin-top: 12px"
        @change="currentPage = $event"
      />
    </template>

  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import SessionInvoiceCard from '@/components/ui/SessionInvoiceCard.vue'

const props = defineProps({
  sessions:      { type: Array,   default: () => [] },
  viewpoint:     { type: String,  default: 'client' },
  emptyIcon:     { type: String,  default: 'calendar' },
  emptyTitle:    { type: String,  default: 'No sessions found' },
  emptySubtitle: { type: String,  default: '' },
  showSearch:    { type: Boolean, default: true },
  showNotes:     { type: Boolean, default: false },
  showInvoice:   { type: Boolean, default: false },
  showCancel:    { type: Boolean, default: false },
  pageSize:      { type: Number,  default: 8 },
})

defineEmits([
  'pay-deposit', 'pay-balance', 'request-refund', 'escalate-refund',
  'review-refund', 'open-notes', 'open-invoice', 'cancel-session',
])

// ── State ─────────────────────────────────────────────────────────────────────
const search      = ref('')
const activeFilter = ref('all')
const currentPage  = ref(1)

// Reset page when search/filter changes
watch([search, activeFilter], () => { currentPage.value = 1 })

// Also expose a reset method for parent to call when switching tabs
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
  return sessions.filter(s => {
    const name    = (s.client_name ?? s.practitioner_name ?? '').toLowerCase()
    const service = (s.service_title ?? '').toLowerCase()
    const invoice = (s.invoice_number ?? '').toLowerCase()
    return name.includes(lq) || service.includes(lq) || invoice.includes(lq)
  })
}

const filtered = computed(() => applySearch(applyFilter(props.sessions, activeFilter.value), search.value))

const paged = computed(() => {
  const start = (currentPage.value - 1) * props.pageSize
  return filtered.value.slice(start, start + props.pageSize)
})

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / props.pageSize)))

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

/* ── Toolbar ── */
.st-toolbar {
  display: flex; flex-direction: column; gap: 10px;
}

/* Search */
.st-search-wrap {
  position: relative; display: flex; align-items: center;
}
.st-search-wrap .aegis-icon:first-child {
  position: absolute; left: 11px; color: var(--text-4); pointer-events: none;
}
.st-search {
  padding-left: 34px !important;
  padding-right: 32px !important;
  width: 100%;
  font-size: 13px;
}
.st-search-clear {
  position: absolute; right: 9px;
  background: none; border: none; cursor: pointer;
  color: var(--text-4); display: flex; align-items: center;
  padding: 2px;
}
.st-search-clear:hover { color: var(--text); }

/* Table */
.sic-table-wrap { border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
.sic-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.sic-th {
  padding: 9px 12px; text-align: left;
  font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
  color: var(--text-4); background: var(--surface-2); border-bottom: 1px solid var(--border);
}
</style>
