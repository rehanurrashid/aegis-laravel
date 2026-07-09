<!--
  pages/provider/Disputes.vue — the Practitioner's dispute inbox.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Money"
      title="Disputes"
      :subtitle="`${activeCount} active · ${resolvedCount} resolved`"
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="alert-triangle" :value="activeCount" label="Active" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
      <AegisStatChip icon="check-circle" :value="resolvedCount" label="Resolved" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="scale" :value="filedByMe" label="Opened by you" />
      <AegisStatChip icon="mail" :value="filedAgainstMe" label="Opened against you" />
    </div>

    <AegisCard v-if="disputes.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Subject</th>
            <th>Reason</th>
            <th>Amount</th>
            <th>Role</th>
            <th>Status</th>
            <th>Opened</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in disputes" :key="d.id">
            <td class="mono">{{ subjectLabel(d.subject_type) }} · {{ d.subject_id.slice(0, 8) }}…</td>
            <td>{{ d.reason_label }}</td>
            <td>{{ formatCents(d.amount_disputed_cents) }}</td>
            <td><span :class="`role-pill role-${d.role}`">{{ d.role }}</span></td>
            <td><AegisBadge :label="d.status_label" :variant="statusVariant(d.status)" /></td>
            <td>{{ d.opened_at }}</td>
            <td>
              <a :href="route('cs.disputes.show', { dispute: d.id })" class="btn btn-sm btn-outline">Open</a>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState v-else icon="scale" title="No disputes"
      description="If a peer transaction goes wrong, you can open a dispute from Finances." />
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisStatChip   from '@/components/ui/AegisStatChip.vue'
import AegisCard       from '@/components/ui/AegisCard.vue'
import AegisBadge      from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'

const props = defineProps({ disputes: { type: Array, default: () => [] } })

const isActive = (s) => ['open', 'awaiting_response', 'under_review'].includes(s)
const activeCount     = computed(() => props.disputes.filter((d) => isActive(d.status)).length)
const resolvedCount   = computed(() => props.disputes.filter((d) => d.status === 'resolved').length)
const filedByMe       = computed(() => props.disputes.filter((d) => d.role === 'disputer').length)
const filedAgainstMe  = computed(() => props.disputes.filter((d) => d.role === 'respondent').length)

function formatCents(c) { return '$' + (Number(c) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
function statusVariant(s) { return { open: 'gold', awaiting_response: 'gold', under_review: 'blue', resolved: 'green', closed_no_action: 'neutral' }[s] ?? 'neutral' }
function subjectLabel(t) { return { cs_invoice: 'CS invoice', bp_invoice: 'BP invoice', bp_payout: 'BP payout', session: 'Session' }[t] ?? t }
</script>

<style scoped>
.mono { font-family: monospace; font-size: 12px; }
.role-pill { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; padding: 2px 8px; border-radius: 10px; font-weight: 600; }
.role-disputer   { background: rgba(220,38,38,.1); color: var(--red-dark); }
.role-respondent { background: rgba(59,130,246,.1); color: var(--blue-dark); }
</style>
