<!--
  pages/admin/Payouts.vue — BP & CS payout oversight for admin.
  Routes: admin.payouts.index / admin.payouts.cancel / admin.payouts.retry
  Props:  pending (Collection), all (Collection), filters
-->
<template>
  <AppLayout>
    <AegisHeroBanner eyebrow="Admin" title="Payouts" :subtitle="`${pending.length} pending payout${pending.length !== 1 ? 's' : ''}`">
      <template #actions>
        <a :href="route('admin.payouts.index')" class="btn btn-outline btn-sm">
          <AegisIcon name="refresh" :size="13" /> Refresh
        </a>
      </template>
    </AegisHeroBanner>

    <!-- Pending payouts -->
    <div v-if="pending.length" class="card" style="margin-bottom:20px">
      <div class="card-header">
        <div class="card-title-group">
          <div class="stat-chip-icon" style="background:var(--icon-bg-orange);color:var(--orange-dark)">
            <AegisIcon name="clock" :size="16" />
          </div>
          <div>
            <div class="card-title">Pending Payouts</div>
            <div class="card-subtitle">Awaiting Stripe confirmation</div>
          </div>
        </div>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Created</th>
              <th>Provider → BP</th>
              <th>Contract</th>
              <th>Amount</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in pending" :key="p.id">
              <td>{{ activity.timeAgo(p.created_at) }}</td>
              <td class="data-table-primary">{{ p.provider_name ?? p.provider_id }} → {{ p.bp_name ?? p.bp_id }}</td>
              <td>{{ p.contract_id ?? '—' }}</td>
              <td>{{ pricing.formatCents(p.amount_cents) }}</td>
              <td><AegisBadge label="Pending" variant="orange" /></td>
              <td style="display:flex;gap:6px">
                <button type="button" class="btn btn-sm btn-ghost" @click="retry(p)">Retry</button>
                <button type="button" class="btn btn-sm btn-ghost btn-danger-ghost" @click="cancel(p)">Cancel</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div v-else class="card" style="margin-bottom:20px">
      <div class="card-body">
        <AegisEmptyState icon="check" title="No pending payouts" subtitle="All payouts have been processed." />
      </div>
    </div>

    <!-- All payouts with filter -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">All Payouts</div>
        <select v-model="statusFilter" class="form-input" style="width:160px" @change="apply">
          <option value="">All statuses</option>
          <option value="pending">Pending</option>
          <option value="paid">Paid</option>
          <option value="failed">Failed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div class="card-body" style="padding:0">
        <table v-if="all.length" class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Provider → BP</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Stripe PI</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in all" :key="p.id">
              <td>{{ activity.timeAgo(p.created_at) }}</td>
              <td class="data-table-primary">{{ p.provider_name ?? p.provider_id }} → {{ p.bp_name ?? p.bp_id }}</td>
              <td>{{ pricing.formatCents(p.amount_cents) }}</td>
              <td>
                <AegisBadge :label="p.status"
                  :variant="{ paid: 'green', pending: 'orange', failed: 'red', cancelled: 'neutral' }[p.status] ?? 'neutral'" />
              </td>
              <td style="font-size:12px;color:var(--text-3);font-family:monospace">{{ p.stripe_payment_intent_id ?? '—' }}</td>
            </tr>
          </tbody>
        </table>
        <AegisEmptyState v-else icon="receipt" title="No payouts found" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import { useActivity } from '@/composables/useActivity'
import { useToast } from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  pending: { type: Array, default: () => [] },
  all:     { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({ status: '' }) },
})

const activity = useActivity()
const toast    = useToast()
const pricing  = usePricingStore()
const statusFilter = ref(props.filters.status ?? '')

function apply() {
  router.get(route('admin.payouts.index'), { status: statusFilter.value },
    { preserveScroll: true, preserveState: true, replace: true })
}

function retry(p) {
  if (!window.confirm(`Retry payout of ${pricing.formatCents(p.amount_cents)}?`)) return
  router.post(route('admin.payouts.retry', { payout: p.id }), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Payout retry queued.'),
  })
}

function cancel(p) {
  if (!window.confirm(`Cancel this payout of ${pricing.formatCents(p.amount_cents)}? Funds will not be transferred.`)) return
  router.post(route('admin.payouts.cancel', { payout: p.id }), { reason: 'Admin cancelled' }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Payout cancelled.'),
  })
}
</script>
