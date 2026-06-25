<!--
  pages/shared/Activity.vue — full activity log with filters.

  Replaces _shared/templates/activity.php. Used by all portals. Filters
  scope by module, severity, and date range. The events array is
  paginated server-side.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Communication"
      title="Activity Log"
      :subtitle="`${pagination?.total ?? events.length} event${(pagination?.total ?? events.length) === 1 ? '' : 's'} across your portal.`"
    >
      <template #actions>
        <button
          v-if="unreadCount > 0"
          type="button"
          class="btn btn-outline"
          @click="markAllRead"
        >
          <AegisIcon name="check" :size="13" />
          <span>Mark all read</span>
        </button>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="activity"        :value="pagination?.total ?? events.length" label="Total events" />
      <AegisStatChip icon="bell"            :value="unreadCount"  label="Unread" bg-color="var(--icon-bg-red)"  icon-color="var(--red-dark)" />
      <AegisStatChip icon="alert-triangle"  :value="criticalCount" label="Critical" bg-color="var(--icon-bg-orange)" icon-color="var(--orange-dark)" />
    </div>

    <!-- Filters -->
    <div class="activity-filters">
      <div class="activity-filter-group">
        <label class="form-label-inline">Module</label>
        <select v-model="filters.module" class="form-input" @change="apply">
          <option value="">All</option>
          <option value="message">Messages</option>
          <option value="task">Tasks</option>
          <option value="document">Documents</option>
          <option value="vault">Vault</option>
          <option value="incident">Incidents</option>
          <option value="payment">Payments</option>
          <option value="compliance">Compliance</option>
        </select>
      </div>
      <div class="activity-filter-group">
        <label class="form-label-inline">Severity</label>
        <select v-model="filters.severity" class="form-input" @change="apply">
          <option value="">All</option>
          <option value="info">Info</option>
          <option value="warning">Warning</option>
          <option value="error">Error</option>
          <option value="critical">Critical</option>
        </select>
      </div>
    </div>

    <!-- Feed -->
    <ActivityFeed :events="events" />

    <AegisPagination
      v-if="pagination"
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
import { reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import ActivityFeed from '@/components/features/ActivityFeed.vue'
import AegisPagination from '@/components/ui/AegisPagination.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  events:     { type: Array,  default: () => [] },
  pagination: { type: Object, default: null },
  filters:    { type: Object, default: () => ({ module: '', severity: '' }) },
})

const toast = useToast()
const filters = reactive({ ...props.filters })

const unreadCount   = computed(() => props.events.filter((e) => !e.read_at).length)
const criticalCount = computed(() => props.events.filter((e) => e.severity === 'critical').length)

function apply() {
  router.get(window.location.pathname, filters, {
    preserveScroll: true,
    preserveState: true,
    replace: true,
  })
}

function goPage(page) {
  router.get(window.location.pathname, { ...filters, page }, {
    preserveScroll: true,
    preserveState: true,
  })
}

function markAllRead() {
  router.post(route('activity.mark-all-read'), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('All events marked as read.'),
  })
}
</script>
