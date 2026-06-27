<!--
  pages/shared/Activity.vue — full activity log with filters.
  Used by all portals. Filters scope by module and severity.
  Events are paginated server-side via ActivityController::index().
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      quiet
      eyebrow="Communication"
      title="Activity Log"
      :subtitle="`${pagination?.total ?? events.length} event${(pagination?.total ?? events.length) === 1 ? '' : 's'} across your portal.`"
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
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip
        icon="activity"
        :value="pagination?.total ?? events.length"
        label="Total events"
      />
      <AegisStatChip
        icon="bell"
        :value="unreadCount"
        label="Unread"
        bg-color="var(--icon-bg-red)"
        icon-color="var(--red-dark)"
      />
      <AegisStatChip
        icon="alert-triangle"
        :value="criticalCount"
        label="Critical"
        bg-color="var(--badge-bg-orange)"
        icon-color="var(--orange-dark)"
      />
    </div>

    <!-- Filters -->
    <div class="activity-filters-bar">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Module</label>
          <select v-model="localFilters.module" class="form-input" @change="applyFilters">
            <option value="">All modules</option>
            <option value="message">Messages</option>
            <option value="task">Tasks</option>
            <option value="document">Documents</option>
            <option value="vault">Vault</option>
            <option value="incident">Incidents</option>
            <option value="payment">Payments</option>
            <option value="compliance">Compliance</option>
            <option value="account">Account</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Severity</label>
          <select v-model="localFilters.severity" class="form-input" @change="applyFilters">
            <option value="">All severities</option>
            <option value="info">Info</option>
            <option value="warning">Warning</option>
            <option value="error">Error</option>
            <option value="critical">Critical</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Read status</label>
          <select v-model="localFilters.unread" class="form-input" @change="applyFilters">
            <option value="">All</option>
            <option value="1">Unread only</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Event feed -->
    <div class="activity-feed">
      <template v-if="events.length">
        <div
          v-for="event in events"
          :key="event.id"
          class="activity-item"
          :class="[activity.severityClass(event.severity), { 'is-unread': !event.read_at }]"
          @click="markRead(event)"
        >
          <div class="activity-item-icon">
            <AegisIcon :name="iconFor(event)" :size="16" />
          </div>
          <div class="activity-item-body">
            <div class="activity-item-title">{{ event.title }}</div>
            <div v-if="event.description" class="activity-item-desc">{{ event.description }}</div>
            <div class="activity-item-time">{{ activity.timeAgo(event.created_at) }}</div>
          </div>
          <span v-if="!event.read_at" class="activity-unread-dot" aria-label="Unread"></span>
        </div>
      </template>

      <div v-else class="empty-state">
        <div class="empty-state-icon">
          <AegisIcon name="activity" :size="28" />
        </div>
        <div class="empty-state-title">No activity found</div>
        <div class="empty-state-sub">Try adjusting your filters or check back later.</div>
      </div>
    </div>

    <!-- Pagination -->
    <AegisPagination
      v-if="pagination && pagination.last_page > 1"
      :current-page="pagination.current_page"
      :total-pages="pagination.last_page"
      :total="pagination.total"
      :from="pagination.from"
      :to="pagination.to"
      @change="goPage"
    />
  </AppLayout>
</template>

<script setup>
import { reactive, computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisStatChip from '@/components/ui/AegisStatChip.vue'
import AegisPagination from '@/components/ui/AegisPagination.vue'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'

const props = defineProps({
  events:      { type: Array,  default: () => [] },
  pagination:  { type: Object, default: null },
  filters:     { type: Object, default: () => ({ module: '', severity: '', unread: '' }) },
  unreadCount: { type: Number, default: 0 },
})

const toast = useToast()
const activity = useActivity()
const markingAll = ref(false)

const localFilters = reactive({
  module:   props.filters?.module   ?? '',
  severity: props.filters?.severity ?? '',
  unread:   props.filters?.unread   ?? '',
})

const criticalCount = computed(() => props.events.filter((e) => e.severity === 'critical').length)

function iconFor(event) {
  if (event.icon) return event.icon
  if (event.severity === 'critical') return 'alert-triangle'
  if (event.module) return activity.iconForEventType(event.module)
  return 'dot'
}

function applyFilters() {
  const query = {}
  if (localFilters.module)   query.module   = localFilters.module
  if (localFilters.severity) query.severity = localFilters.severity
  if (localFilters.unread)   query.unread   = localFilters.unread

  router.get(window.location.pathname, query, {
    preserveScroll: true,
    preserveState:  true,
    replace:        true,
  })
}

function goPage(page) {
  const query = {}
  if (localFilters.module)   query.module   = localFilters.module
  if (localFilters.severity) query.severity = localFilters.severity
  if (localFilters.unread)   query.unread   = localFilters.unread
  query.page = page

  router.get(window.location.pathname, query, {
    preserveScroll: true,
    preserveState:  true,
  })
}

function markRead(event) {
  if (event.read_at) return
  router.post(route('activity.read', event.id), {}, {
    preserveScroll: true,
    preserveState:  true,
  })
}

function markAllRead() {
  markingAll.value = true
  router.post(route('activity.mark-all-read'), {}, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('All events marked as read.')
      markingAll.value = false
    },
    onError: () => { markingAll.value = false },
  })
}
</script>

<style scoped>
.activity-filters-bar {
  padding: 1rem 0;
  border-bottom: 1px solid var(--border);
  margin-bottom: 1rem;
}

.activity-item {
  cursor: pointer;
  transition: background var(--transition-fast);
}
.activity-item:hover {
  background: var(--surface-2);
}

.activity-unread-dot {
  width: 8px;
  height: 8px;
  border-radius: var(--radius-full);
  background: var(--gold-dark);
  flex-shrink: 0;
  align-self: center;
}
</style>
