<!--
  pages/business-partner/Milestones.vue — work milestones across contracts.
  Statuses from bp_milestones enum: pending | submitted | approved | rejected | paid.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Work"
      title="Milestones"
      :subtitle="`${pendingCount} pending · ${overdueCount} overdue`"
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="flag-2" :value="pendingCount" label="Pending" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
      <AegisStatChip icon="send" :value="submittedCount" label="Submitted" bg-color="var(--icon-bg-blue)" icon-color="var(--blue-dark)" />
      <AegisStatChip icon="check-circle" :value="paidCount" label="Paid" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="alert-triangle" :value="overdueCount" label="Overdue" bg-color="var(--icon-bg-red)" icon-color="var(--red-dark)" />
      <AegisStatChip icon="dollar" :value="pricing.formatCents(pendingValue)" label="Pending value" />
    </div>

    <AegisCard v-if="milestones.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Milestone</th>
            <th>Contract</th>
            <th>Due</th>
            <th>Amount</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in milestones" :key="m.id" :class="{ 'is-overdue': isOverdue(m) }">
            <td class="data-table-primary">{{ m.title }}</td>
            <td>{{ m.contract_title }}</td>
            <td>{{ m.due_at ? formatDue(m.due_at) : '—' }}</td>
            <td>{{ pricing.formatCents(m.amount_cents) }}</td>
            <td><AegisBadge :label="m.status" :variant="variant(m.status)" /></td>
            <td>
              <button
                v-if="m.status === 'pending'"
                type="button"
                class="btn btn-sm btn-primary"
                :disabled="busy === m.id"
                @click="submit(m)"
              >
                <AegisIcon name="send" :size="12" />
                {{ busy === m.id ? 'Submitting…' : 'Submit' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState v-else icon="flag-2" title="No milestones yet" description="Milestones appear as you sign contracts." />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisStatChip   from '@/components/ui/AegisStatChip.vue'
import AegisCard       from '@/components/ui/AegisCard.vue'
import AegisBadge      from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import AegisIcon       from '@/components/ui/AegisIcon.vue'
import { useToast }    from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({ milestones: { type: Array, default: () => [] } })
const toast = useToast()
const pricing = usePricingStore()

const busy = ref(null)

const pendingCount   = computed(() => props.milestones.filter((m) => m.status === 'pending').length)
const submittedCount = computed(() => props.milestones.filter((m) => m.status === 'submitted' || m.status === 'approved').length)
const paidCount      = computed(() => props.milestones.filter((m) => m.status === 'paid').length)
const overdueCount   = computed(() => props.milestones.filter((m) => isOverdue(m)).length)
const pendingValue   = computed(() => props.milestones.filter((m) => m.status === 'pending').reduce((s, m) => s + (m.amount_cents || 0), 0))

function isOverdue(m) { return m.status === 'pending' && m.due_at && new Date(m.due_at) < Date.now() }

function variant(s) {
  return {
    pending:   'gold',
    submitted: 'blue',
    approved:  'blue',
    paid:      'green',
    rejected:  'red',
  }[s] ?? 'neutral'
}

function formatDue(iso) {
  const d = new Date(iso)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function submit(m) {
  busy.value = m.id
  router.post(route('bp.milestones.submit', { milestone: m.id }), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Milestone submitted for review.'),
    onFinish:  () => { busy.value = null },
  })
}
</script>

<style scoped>
.is-overdue td { background: rgba(220, 38, 38, 0.05); }
</style>
