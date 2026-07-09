<!--
  pages/admin/Disputes.vue — dispute queue for Aegis admins.

  Props from Admin/DisputesController::index:
    disputes[]  { id, disputer, respondent, subject_type, subject_id,
                  reason_label, status, status_label, status_color,
                  amount_disputed_cents, opened_at, age_days }
    counts      { open, awaiting_response, under_review, resolved, closed_no_action }
    filter      current status filter (empty = all)
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Admin"
      title="Disputes"
      :subtitle="`${activeCount} active · ${resolvedCount} resolved`"
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="alert-triangle" :value="activeCount" label="Active" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
      <AegisStatChip icon="clock" :value="staleCount" label="Older than 5 days" bg-color="var(--icon-bg-red)" icon-color="var(--red-dark)" />
      <AegisStatChip icon="check-circle" :value="resolvedCount" label="Resolved" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="dollar" :value="formatCents(totalDisputedActive)" label="At stake (active)" />
    </div>

    <nav class="tab-strip">
      <a v-for="opt in filters" :key="opt.key"
         :href="opt.key ? route('admin.disputes.index', { status: opt.key }) : route('admin.disputes.index')"
         class="tab-btn"
         :class="{ 'is-active': filter === opt.key }">
        {{ opt.label }}<span v-if="counts?.[opt.key] || opt.key === ''"> ({{ opt.key === '' ? disputes.length : (counts?.[opt.key] ?? 0) }})</span>
      </a>
    </nav>

    <AegisCard v-if="disputes.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Disputer</th>
            <th>Respondent</th>
            <th>Subject</th>
            <th>Reason</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Age</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in disputes" :key="d.id" :class="{ 'is-stale': d.age_days > 5 && isActive(d.status) }">
            <td>{{ d.disputer }}</td>
            <td>{{ d.respondent }}</td>
            <td class="mono">{{ subjectLabel(d.subject_type) }}</td>
            <td>{{ d.reason_label }}</td>
            <td>{{ formatCents(d.amount_disputed_cents) }}</td>
            <td><AegisBadge :label="d.status_label" :variant="badgeVariant(d.status_color)" /></td>
            <td>{{ d.age_days ?? '—' }}d</td>
            <td>
              <a :href="route('admin.disputes.show', { dispute: d.id })" class="btn btn-sm btn-outline">Review</a>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState v-else icon="scale" title="No disputes" description="Nothing to review right now." />
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

const props = defineProps({
  disputes: { type: Array,  default: () => [] },
  counts:   { type: Object, default: () => ({}) },
  filter:   { type: String, default: '' },
})

const filters = [
  { key: '',                  label: 'All' },
  { key: 'open',              label: 'Open' },
  { key: 'awaiting_response', label: 'Awaiting response' },
  { key: 'under_review',      label: 'Under review' },
  { key: 'resolved',          label: 'Resolved' },
]

const isActive = (s) => ['open', 'awaiting_response', 'under_review'].includes(s)

const activeCount   = computed(() => props.disputes.filter((d) => isActive(d.status)).length)
const resolvedCount = computed(() => props.disputes.filter((d) => d.status === 'resolved').length)
const staleCount    = computed(() => props.disputes.filter((d) => isActive(d.status) && d.age_days > 5).length)
const totalDisputedActive = computed(() => props.disputes.filter((d) => isActive(d.status)).reduce((s, d) => s + (d.amount_disputed_cents || 0), 0))

function formatCents(c) {
  const n = Number(c ?? 0) / 100
  return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function badgeVariant(c) { return { gold: 'gold', blue: 'blue', green: 'green', red: 'red', gray: 'neutral' }[c] ?? 'neutral' }
function subjectLabel(t) {
  return { cs_invoice: 'CS invoice', bp_invoice: 'BP invoice', bp_payout: 'BP payout', session: 'Session', engagement: 'Engagement' }[t] ?? t
}
</script>

<style scoped>
.is-stale td { background: rgba(220,38,38,.04); }
.mono { font-family: monospace; font-size: 12px; }
</style>
