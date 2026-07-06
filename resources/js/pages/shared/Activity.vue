<!--
  pages/shared/Activity.vue — Activity Log used by all five portals.
  Mirrors legacy PHP activity template: hero + stat chips · quick-filter pills ·
  date-grouped event list with category sidebar · detail modal · audit-export modal.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      quiet
      eyebrow="Communication"
      title="Activity Log"
      :subtitle="`${totalCount} event${totalCount === 1 ? '' : 's'} across your portal.`"
    >
      <template #actions>
        <button
          v-if="unreadCount > 0"
          type="button"
          class="btn-hero-ghost is-on-light"
          :disabled="markingAll"
          @click="markAllRead"
        >
          <AegisIcon name="check" :size="14" />
          Mark all read
        </button>
        <button
          type="button"
          class="btn-hero-ghost is-on-light"
          @click="modals.export = true"
        >
          <AegisIcon name="download" :size="14" />
          Export
        </button>
      </template>
    </AegisHeroBanner>

    <!-- ── Stat chips ──────────────────────────────────── -->
    <div class="stat-chips-row">
      <AegisStatChip
        icon="activity"
        :value="totalCount"
        label="Total events"
      />
      <AegisStatChip
        icon="bell"
        :value="unreadCount"
        label="Unread"
      />
      <AegisStatChip
        icon="alert-triangle"
        :value="criticalCount"
        label="Critical"
      />
    </div>

    <!-- ── Entry-type toggle (All / My Activity / Notifications) ── -->
    <div class="tabs-segmented" role="tablist" aria-label="Filter by entry type">
      <button
        type="button"
        class="tab-pill"
        role="tab"
        :class="{ active: !localFilters.entry_type }"
        :aria-selected="!localFilters.entry_type"
        @click="setEntryType('')"
      >
        <AegisIcon name="activity" :size="12" />
        All
      </button>
      <button
        type="button"
        class="tab-pill"
        role="tab"
        :class="{ active: localFilters.entry_type === 'log' }"
        :aria-selected="localFilters.entry_type === 'log'"
        @click="setEntryType('log')"
      >
        <AegisIcon name="user" :size="12" />
        My Activity
        <span v-if="logCount > 0" class="badge-pill">{{ logCount }}</span>
      </button>
      <button
        type="button"
        class="tab-pill"
        role="tab"
        :class="{ active: localFilters.entry_type === 'notification' }"
        :aria-selected="localFilters.entry_type === 'notification'"
        @click="setEntryType('notification')"
      >
        <AegisIcon name="bell" :size="12" />
        Notifications
        <span v-if="notificationCount > 0" class="badge-pill">{{ notificationCount }}</span>
      </button>
    </div>

    <!-- ── Search bar ───────────────────────────────────── -->
    <div class="activity-toolbar">
      <div class="activity-search">
        <AegisIcon name="search" :size="14" />
        <input
          v-model="searchQuery"
          type="text"
          class="activity-search-input"
          placeholder="Search events…"
        />
      </div>
      <div v-if="localFilters.module" class="activity-module-badge">
        <AegisIcon name="filter" :size="11" />
        <span>{{ localFilters.module }}</span>
        <button type="button" class="activity-module-clear" data-tooltip="Clear module filter"
          @click="localFilters.module = ''; pushQuery({})">
          <AegisIcon name="x" :size="11" />
        </button>
      </div>
    </div>

    <!-- ── Two-column shell ────────────────────────────── -->
    <div class="activity-shell">

      <!-- Left: category sidebar -->
      <aside class="activity-categories">
        <div class="activity-categories-head">Categories</div>
        <button
          v-for="cat in categoryCounts"
          :key="cat.key || 'all'"
          type="button"
          class="activity-cat-item"
          :class="{ 'is-active': localFilters.event_type === cat.key }"
          @click="setEventType(cat.key)"
        >
          <span class="activity-cat-label">
            <AegisIcon :name="cat.icon" :size="14" />
            {{ cat.label }}
          </span>
          <span class="activity-cat-count">{{ cat.count }}</span>
        </button>
      </aside>

      <!-- Right: event feed -->
      <section class="activity-main">
        <template v-if="hasAnyVisibleEvents">
          <div
            v-for="group in dateGroups"
            v-show="group.events.length"
            :key="group.key"
            class="activity-date-group"
          >
            <div class="activity-date-label">
              {{ group.label }}
              <span class="activity-date-count">{{ group.events.length }}</span>
            </div>

            <div
              v-for="event in group.events"
              :key="event.id"
              class="activity-item"
              :class="{
                'is-important':    event.important,
                'is-critical':     event.event_type === 'incident',
                'is-unread':       !event.read_at,
                'is-notification': event.entry_type === 'notification',
                'is-log':          event.entry_type === 'log',
              }"
              @click="openDetail(event)"
            >
              <div class="activity-icon-wrap">
                <AegisIcon :name="event.icon" :size="16" />
              </div>
              <div class="activity-body">
                <div class="activity-title">
                  <span v-if="event.entry_type === 'notification' && event.actor" class="activity-actor">
                    <strong>{{ event.actor.display_name }}</strong>
                    <span class="activity-actor-sep">·</span>
                  </span>
                  {{ event.title }}
                </div>
                <div v-if="event.description" class="activity-desc">{{ event.description }}</div>
                <div class="activity-meta">
                  <span
                    v-if="event.entry_type === 'notification'"
                    class="activity-badge notification-pill"
                    data-tooltip="A party other than you triggered this event"
                  >Notification</span>
                  <span
                    v-else-if="event.entry_type === 'log'"
                    class="activity-badge log-pill"
                    data-tooltip="Your own action, recorded to your log"
                  >My Log</span>
                  <span class="activity-badge" :class="event.badge_class">{{ event.badge_label }}</span>
                  <span v-if="event.severity === 'critical'" class="activity-badge critical-incident">Critical</span>
                  <span v-else-if="event.severity === 'warning'" class="activity-badge financial">Warning</span>
                </div>
              </div>
              <span class="activity-time">{{ activity.timeAgo(event.created_at) }}</span>
              <span v-if="!event.read_at" class="activity-unread-dot" aria-label="Unread"></span>
            </div>
          </div>
        </template>

        <div v-else class="empty-state">
          <div class="empty-state-icon">
            <AegisIcon name="activity" :size="28" />
          </div>
          <div class="empty-state-title">No activity found</div>
          <div class="empty-state-sub">Try adjusting your filters or check back later.</div>
        </div>

        <AegisPagination
          v-if="pagination && pagination.last_page > 1"
          :current-page="pagination.current_page"
          :total-pages="pagination.last_page"
          :total="pagination.total"
          :from="pagination.from"
          :to="pagination.to"
          @change="goPage"
        />
      </section>
    </div>

    <!-- ── Detail Modal ──────────────────────────────── -->
    <AegisModal
      v-model="modals.detail"
      :title="detailEvent?.title || 'Activity Detail'"
      size="md"
    >
      <div v-if="detailEvent" class="activity-detail">
        <div class="activity-detail-row">
          <span class="activity-detail-label">Type</span>
          <span class="activity-detail-val">{{ detailEvent.badge_label }}</span>
        </div>
        <div class="activity-detail-row">
          <span class="activity-detail-label">Entry</span>
          <span class="activity-detail-val">
            {{ detailEvent.entry_type === 'notification' ? 'Notification (from another party)' : 'My activity log' }}
          </span>
        </div>
        <div v-if="detailEvent.actor" class="activity-detail-row">
          <span class="activity-detail-label">Triggered by</span>
          <span class="activity-detail-val">{{ detailEvent.actor.display_name }}</span>
        </div>
        <div class="activity-detail-row">
          <span class="activity-detail-label">Module</span>
          <span class="activity-detail-val">{{ detailEvent.module || '—' }}</span>
        </div>
        <div class="activity-detail-row">
          <span class="activity-detail-label">Severity</span>
          <span class="activity-detail-val">{{ detailEvent.severity || 'info' }}</span>
        </div>
        <div class="activity-detail-row">
          <span class="activity-detail-label">Action</span>
          <span class="activity-detail-val">{{ detailEvent.action || '—' }}</span>
        </div>
        <div class="activity-detail-row">
          <span class="activity-detail-label">Logged</span>
          <span class="activity-detail-val">{{ detailEvent.created_at }}</span>
        </div>
        <div class="activity-detail-row">
          <span class="activity-detail-label">Description</span>
          <span class="activity-detail-val">{{ detailEvent.description || '—' }}</span>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-ghost" @click="modals.detail = false">Close</button>
      </template>
    </AegisModal>

    <!-- ── Export Audit Modal ────────────────────────── -->
    <AegisModal
      v-model="modals.export"
      title="Export Audit Log"
      size="md"
    >
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Date from</label>
          <input v-model="exportForm.from" type="date" class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label">Date to</label>
          <input v-model="exportForm.to" type="date" class="form-input" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Format</label>
          <select v-model="exportForm.format" class="form-select">
            <option value="pdf">PDF — Full formatted report</option>
            <option value="csv">CSV — Spreadsheet data</option>
            <option value="json">JSON — Machine-readable</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Reason for export <span class="text-danger">*</span></label>
          <select v-model="exportForm.reason" class="form-select">
            <option value="">Select reason…</option>
            <option value="annual_review">Annual compliance review</option>
            <option value="legal">Legal / attorney request</option>
            <option value="regulatory">Regulatory audit</option>
            <option value="internal">Internal review</option>
            <option value="incident">Critical incident documentation</option>
            <option value="other">Other</option>
          </select>
        </div>
      </div>
      <p class="form-hint">The export action itself will be logged as an audit event.</p>
      <template #footer>
        <button type="button" class="btn btn-ghost" @click="modals.export = false">Cancel</button>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="!exportForm.reason"
          @click="submitExport"
        >
          <AegisIcon name="download" :size="12" />
          Export
        </button>
      </template>
    </AegisModal>
  </AppLayout>
</template>

<script setup>
import { reactive, computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisStatChip from '@/components/ui/AegisStatChip.vue'
import AegisPagination from '@/components/ui/AegisPagination.vue'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'

const props = defineProps({
  events:            { type: Array,  default: () => [] },
  grouped:           { type: Object, default: () => ({ today: [], week: [], month: [] }) },
  pagination:        { type: Object, default: null },
  totalCount:        { type: Number, default: 0 },
  filters:           { type: Object, default: () => ({ module: '', severity: '', unread: '', event_type: '', entry_type: '' }) },
  unreadCount:       { type: Number, default: 0 },
  notificationCount: { type: Number, default: 0 },
  logCount:          { type: Number, default: 0 },
  criticalCount:     { type: Number, default: 0 },
  categoryCounts:    { type: Array,  default: () => [] },
})

const toast      = useToast()
const activity   = useActivity()
const markingAll = ref(false)
const searchQuery = ref('')
const activeQuick = ref('all')

const modals = reactive({
  detail: false,
  export: false,
})
const detailEvent = ref(null)

const page = usePage()

// Portal-aware route names — resolves correct prefixed route for all portals
const portalRoutePrefix = computed(() => {
  const portal = page.props.auth?.portal ?? 'provider'
  return {
    provider: 'provider',
    cs:       'cs',
    ss:       'ss',
    bp:       'bp',
    admin:    'admin',
  }[portal] ?? 'provider'
})

function activityRoute(name) {
  // Shared routes (mark-all-read, export, read) live in the shared group
  // Index route is portal-prefixed
  const shared = ['activity.mark-all-read', 'activity.export', 'activity.read']
  if (shared.includes(name) || name.includes('.read') || name.includes('mark-all')) {
    return route(name)
  }
  // 'activity.index' → use portal-prefixed 'provider.activity' etc.
  const portalName = portalRoutePrefix.value + '.activity'
  try { return route(portalName) } catch { return route('activity.index') }
}

const localFilters = reactive({
  module:     props.filters?.module     ?? '',
  severity:   props.filters?.severity   ?? '',
  unread:     props.filters?.unread     ?? '',
  event_type: props.filters?.event_type ?? '',
  entry_type: props.filters?.entry_type ?? '',
})

const quickFilters = [
  { key: 'all',       label: 'All',          icon: 'inbox',           filter: {} },
  { key: 'important', label: 'Important',    icon: 'star',            filter: { severity: 'critical' } },
  { key: 'incidents', label: 'Incidents',    icon: 'alert-triangle',  filter: { event_type: 'incident' } },
  { key: 'messages',  label: 'Messages',     icon: 'message-square',  filter: { event_type: 'message' } },
  { key: 'support',   label: 'Support',      icon: 'life-buoy',       filter: { event_type: 'support' } },
  { key: 'tasks',     label: 'Tasks',        icon: 'check-circle',    filter: { event_type: 'task' } },
  { key: 'events',    label: 'Events',       icon: 'calendar',        filter: { event_type: 'event' } },
  { key: 'news',      label: 'News',         icon: 'megaphone',       filter: { event_type: 'news' } },
  { key: 'unread',    label: 'Unread',       icon: 'bell',            filter: { unread: '1' } },
]

const dateGroups = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  const filterFn = (e) => {
    if (!q) return true
    return `${e.title} ${e.description ?? ''}`.toLowerCase().includes(q)
  }
  return [
    { key: 'today', label: 'Today',           events: (props.grouped?.today ?? []).filter(filterFn) },
    { key: 'week',  label: 'This Week',       events: (props.grouped?.week  ?? []).filter(filterFn) },
    { key: 'month', label: 'Earlier',         events: (props.grouped?.month ?? []).filter(filterFn) },
  ]
})

const hasAnyVisibleEvents = computed(() =>
  dateGroups.value.some(g => g.events.length > 0)
)

function applyQuickFilter(q) {
  activeQuick.value = q.key
  const query = { ...q.filter }
  if (localFilters.event_type && !('event_type' in q.filter) && q.key !== 'all') {
    query.event_type = localFilters.event_type
  }
  pushQuery(query)
}

function setEventType(et) {
  localFilters.event_type = et
  activeQuick.value = 'all'
  pushQuery({ event_type: et })
}

function setEntryType(et) {
  localFilters.entry_type = et
  activeQuick.value = 'all'
  pushQuery({ entry_type: et })
}

function pushQuery(extra = {}) {
  const query = {}
  for (const [k, v] of Object.entries({ ...localFilters, ...extra })) {
    if (v !== '' && v !== null && v !== undefined) query[k] = v
  }
  // Always carry module filter from URL so it survives navigation
  if (localFilters.module && !query.module) query.module = localFilters.module
  router.get(activityRoute('activity.index'), query, {
    preserveScroll: true,
    preserveState:  true,
    replace:        true,
  })
}

function goPage(pg) {
  const query = { page: pg }
  for (const [k, v] of Object.entries(localFilters)) {
    if (v) query[k] = v
  }
  router.get(activityRoute('activity.index'), query, {
    preserveScroll: true,
    preserveState:  true,
  })
}

function openDetail(event) {
  detailEvent.value = event
  modals.detail = true
  if (!event.read_at) {
    router.post(route('activity.read', { event: event.id }), {}, {
      preserveScroll: true,
      preserveState:  true,
    })
  }
}

function markAllRead() {
  markingAll.value = true
  router.post(route('activity.mark-all-read'), {}, {
    preserveScroll: true,
    preserveState:  false,   // must be false — need fresh unreadCount + read_at from server
    onSuccess: () => {
      toast.success('All events marked as read.')
      markingAll.value = false
    },
    onError: () => {
      toast.error('Could not mark all read.')
      markingAll.value = false
    },
  })
}

const exportForm = reactive({
  from:   '',
  to:     new Date().toISOString().slice(0, 10),
  format: 'pdf',
  reason: '',
})

function submitExport() {
  if (!exportForm.reason) {
    toast.error('Please select a reason.')
    return
  }

  // Build URL with query params and trigger download via hidden anchor.
  // PDF returns printable HTML in a new tab; CSV / JSON stream as file download.
  const params = new URLSearchParams()
  params.set('format', exportForm.format)
  if (exportForm.from)   params.set('from',   exportForm.from)
  if (exportForm.to)     params.set('to',     exportForm.to)
  if (exportForm.reason) params.set('reason', exportForm.reason)
  if (localFilters.event_type) params.set('event_type', localFilters.event_type)

  const url = route('activity.export') + '?' + params.toString()

  const a = document.createElement('a')
  a.href = url
  if (exportForm.format === 'pdf') {
    a.target = '_blank'
    a.rel = 'noopener'
  }
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)

  modals.export = false
  toast.success(
    exportForm.format === 'pdf'
      ? 'Print dialog opened in a new tab — choose “Save as PDF”.'
      : 'Export downloaded — this action has been logged.'
  )
}
</script>

<style scoped>
/* ── Notification vs Log visual distinction ─────── */
/* Global .tabs-segmented + .tab-pill do the tab styling. These
   scoped rules only handle the per-item accent in the feed. */
.activity-item.is-notification { border-left: 3px solid var(--gold-dark); }
.activity-item.is-log          { border-left: 3px solid var(--border); }
.activity-actor      { color: var(--text-2); }
.activity-actor strong { color: var(--text); font-weight: 700; }
.activity-actor-sep  { margin: 0 6px; color: var(--text-4); }
.activity-badge.notification-pill {
  color: var(--gold-dark);
  background: var(--badge-bg-gold);
}
.activity-badge.log-pill {
  color: var(--text-3);
  background: var(--surface-2);
}

/* Nudge the segmented tab row into the toolbar rhythm */
.tabs-segmented { margin-top: 20px; margin-bottom: 8px; }

/* ── Toolbar (quick filter pills + search) ───────── */
.activity-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 1.25rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}
.activity-search {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 0.75rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  color: var(--text-3);
  min-width: 220px;
}
.activity-search-input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 13px;
  color: var(--text);
  font-family: inherit;
}
.activity-search-input::placeholder { color: var(--text-4); }

/* ── Shell (sidebar + main) ──────────────────────── */
.activity-shell {
  display: grid;
  grid-template-columns: 220px 1fr;
  gap: 1.5rem;
  align-items: start;
}

/* Categories sidebar */
.activity-categories {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 0.75rem;
  box-shadow: var(--shadow-sm);
  position: sticky;
  top: 1rem;
}
.activity-categories-head {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  color: var(--text-4);
  padding: 0.25rem 0.5rem 0.625rem;
}
.activity-cat-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  width: 100%;
  padding: 0.475rem 0.625rem;
  background: transparent;
  border: 0;
  border-radius: var(--radius-sm);
  font-size: 13px;
  color: var(--text-2);
  cursor: pointer;
  transition: background var(--transition-fast), color var(--transition-fast);
  text-align: left;
}
.activity-cat-item:hover {
  background: var(--surface-2);
  color: var(--text);
}
.activity-cat-item.is-active {
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
  font-weight: 600;
}
.activity-cat-label {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
.activity-cat-count {
  font-size: 11px;
  font-weight: 700;
  background: var(--surface-2);
  color: var(--text-3);
  padding: 0.125rem 0.5rem;
  border-radius: var(--radius-full);
  border: 1px solid var(--border);
}
.activity-cat-item.is-active .activity-cat-count {
  background: var(--surface);
  color: var(--gold-dark);
  border-color: var(--soft-gold);
}

/* Main column */
.activity-main { min-width: 0; }

/* Date group */
.activity-date-group { margin-bottom: 1.75rem; }
.activity-date-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.7px;
  color: var(--text-4);
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.activity-date-label::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--border);
}
.activity-date-count {
  font-size: 10px;
  font-weight: 700;
  background: var(--surface-3);
  color: var(--text-4);
  padding: 0.125rem 0.5rem;
  border-radius: var(--radius-full);
}

/* Activity item */
.activity-item {
  display: flex;
  gap: 0.875rem;
  padding: 0.875rem 1rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface);
  margin-bottom: 0.5rem;
  cursor: pointer;
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
  position: relative;
}
.activity-item:hover {
  border-color: var(--gold-light);
  box-shadow: var(--shadow-sm);
}
.activity-item.is-important { border-left: 3px solid var(--gold-dark); }
.activity-item.is-critical {
  border-left: 3px solid var(--red);
  background: var(--red-light);
}
.activity-icon-wrap {
  width: 38px;
  height: 38px;
  border-radius: var(--radius-full);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 0.125rem;
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
}
.activity-body { flex: 1; min-width: 0; }
.activity-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 0.2rem;
}
.activity-desc {
  font-size: 12px;
  color: var(--text-3);
  line-height: 1.55;
  margin-bottom: 0.4rem;
}
.activity-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  align-items: center;
}
.activity-badge {
  font-size: 11px;
  font-weight: 700;
  padding: 0.125rem 0.6rem;
  border-radius: var(--radius-full);
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  border: 1px solid transparent;
}
.activity-badge.critical-incident { background: var(--red-light); color: var(--red); border-color: var(--red-light); }
.activity-badge.vault             { background: var(--purple-light); color: var(--purple); border-color: var(--purple-light); }
.activity-badge.task              { background: var(--green-light); color: var(--green-dark); border-color: var(--green-light); }
.activity-badge.message           { background: var(--blue-light); color: var(--blue); border-color: var(--blue-light); }
.activity-badge.financial         { background: var(--orange-light); color: var(--orange-dark); border-color: var(--orange-light); }
.activity-badge.login             { background: var(--surface-2); color: var(--text-4); border-color: var(--border); }
.activity-badge.compliance        { background: var(--teal-light); color: var(--teal-dark); border-color: var(--teal-light); }
.activity-badge.referral          { background: var(--purple-light); color: var(--purple); border-color: var(--purple-light); }
.activity-badge.system            { background: var(--surface-2); color: var(--text-4); border-color: var(--border); }
.activity-badge.document          { background: var(--badge-bg-gold); color: var(--gold-dark); border-color: var(--badge-bg-gold); }
.activity-badge.news              { background: var(--blue-light); color: var(--blue-dark); border-color: var(--blue-light); }
.activity-badge.event             { background: var(--green-light); color: var(--green-dark); border-color: var(--green-light); }
.activity-badge.job-postings      { background: var(--badge-bg-gold); color: var(--gold-dark); border-color: var(--badge-bg-gold); }

.activity-time {
  font-size: 11px;
  color: var(--text-4);
  flex-shrink: 0;
  padding-top: 0.125rem;
}
.activity-unread-dot {
  width: 8px;
  height: 8px;
  border-radius: var(--radius-full);
  background: var(--gold-dark);
  flex-shrink: 0;
  align-self: center;
}

/* Detail modal */
.activity-detail-row {
  display: flex;
  gap: 0.5rem;
  padding: 0.575rem 0;
  border-bottom: 1px solid var(--border);
  font-size: 13px;
}
.activity-detail-row:last-child { border-bottom: none; }
.activity-detail-label {
  font-weight: 700;
  color: var(--text-3);
  width: 130px;
  flex-shrink: 0;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}
.activity-detail-val { color: var(--text-2); flex: 1; }

@media (max-width: 980px) {
  .activity-shell { grid-template-columns: 1fr; }
  .activity-categories { position: static; }
}

/* ── Module filter badge ───────────────────────────── */
.activity-module-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 8px 3px 7px;
  background: var(--badge-bg-gold);
  border: 1px solid var(--soft-gold);
  border-radius: var(--radius-full);
  font-size: 11px;
  font-weight: 700;
  color: var(--gold-dark);
  white-space: nowrap;
}
.activity-module-clear {
  display: inline-flex;
  align-items: center;
  background: transparent;
  border: none;
  cursor: pointer;
  color: var(--gold-dark);
  padding: 0;
  opacity: 0.7;
}
.activity-module-clear:hover { opacity: 1; }
</style>