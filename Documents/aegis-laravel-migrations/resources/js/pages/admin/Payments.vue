<!--
  pages/admin/Payments.vue — platform payment / billing oversight.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Admin"
      title="Payments"
      :subtitle="`${pricing.formatCents(summary.total_cents)} processed all-time`"
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="dollar" :value="pricing.formatCents(summary.mtd_cents)" label="This month" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="trending-up" :value="pricing.formatCents(summary.ytd_cents)" label="Year to date" />
      <AegisStatChip icon="alert-triangle" :value="summary.failed_count" label="Failed (30d)" bg-color="var(--icon-bg-red)" icon-color="var(--red-dark)" />
      <AegisStatChip icon="rotate-ccw" :value="summary.refunded_count" label="Refunded (30d)" bg-color="var(--icon-bg-orange)" icon-color="var(--orange-dark)" />
    </div>

    <div class="bp-filters">
      <input v-model="filters.q" type="text" class="form-input bp-filters-q" placeholder="Search by user or charge ID…" @keyup.enter="apply" />
      <select v-model="filters.status" class="form-input" @change="apply">
        <option value="">All statuses</option>
        <option value="succeeded">Succeeded</option>
        <option value="failed">Failed</option>
        <option value="refunded">Refunded</option>
      </select>
    </div>

    <AegisCard v-if="payments.data?.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>User</th>
            <th>Description</th>
            <th>Amount</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in payments.data" :key="p.id">
            <td>{{ activity.timeAgo(p.created_at) }}</td>
            <td class="data-table-primary">{{ p.user_name }}</td>
            <td>{{ p.description }}</td>
            <td>{{ pricing.formatCents(p.amount_cents) }}</td>
            <td><AegisBadge :label="p.status" :variant="{ succeeded: 'green', failed: 'red', refunded: 'orange' }[p.status] ?? 'neutral'" /></td>
            <td>
              <button
                v-if="p.status === 'succeeded'"
                type="button"
                class="btn btn-sm btn-ghost btn-danger-ghost"
                @click="refund(p)"
              >
                Refund
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <AegisPagination :links="payments.links" />
    </AegisCard>

    <AegisEmptyState v-else icon="receipt" title="No payments match" />
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import AegisPagination from '@/components/ui/AegisPagination.vue'
import { useActivity } from '@/composables/useActivity'
import { useToast } from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  summary:  { type: Object, default: () => ({ total_cents: 0, mtd_cents: 0, ytd_cents: 0, failed_count: 0, refunded_count: 0 }) },
  payments: { type: Object, default: () => ({ data: [], links: [] }) },
  filters:  { type: Object, default: () => ({ q: '', status: '' }) },
})

const activity = useActivity()
const toast = useToast()
const pricing = usePricingStore()
const filters = reactive({ ...props.filters })

function apply() {
  router.get(route('admin.payments'), filters, { preserveScroll: true, preserveState: true, replace: true, only: ['payments', 'filters', 'summary'] })
}
function refund(p) {
  if (!window.confirm(`Refund ${pricing.formatCents(p.amount_cents)} to ${p.user_name}?`)) return
  router.post(route('admin.payments.refund', { payment: p.id }), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Refund issued.'),
  })
}
</script>
