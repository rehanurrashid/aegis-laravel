<!--
  pages/business-partner/Finances.vue — earnings + payout summary.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Money"
      title="Finances"
      subtitle="Earnings, payouts, and account standing."
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="dollar" :value="pricing.formatCents(summary.lifetime_cents)" label="Lifetime earned" />
      <AegisStatChip icon="trending-up" :value="pricing.formatCents(summary.year_cents)" label="This year" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="hourglass" :value="pricing.formatCents(summary.pending_cents)" label="In escrow" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
      <AegisStatChip icon="bank" :value="summary.payout_method ?? 'Not set'" label="Payout method" bg-color="var(--icon-bg-blue)" icon-color="var(--blue-dark)" />
    </div>

    <div class="dashboard-grid">
      <AegisCard title="Payout schedule">
        <ul class="payout-list">
          <li v-for="p in payouts" :key="p.id" class="payout-row">
            <div>
              <div class="payout-amount">{{ pricing.formatCents(p.amount_cents) }}</div>
              <div class="payout-meta">{{ p.scheduled_for }} via {{ p.method }}</div>
            </div>
            <AegisBadge :label="p.status" :variant="{ scheduled: 'gold', paid: 'green', failed: 'red' }[p.status] ?? 'neutral'" />
          </li>
        </ul>
        <AegisEmptyState v-if="!payouts.length" icon="bank" title="No payouts scheduled" />
      </AegisCard>

      <AegisCard title="Quick links">
        <ul class="quick-links">
          <li><a :href="route('bp.invoices')">View invoices <AegisIcon name="arrow-right" :size="13" /></a></li>
          <li><a :href="route('bp.payment-setup')">Manage payout method <AegisIcon name="arrow-right" :size="13" /></a></li>
          <li><a :href="route('bp.contracts')">Active contracts <AegisIcon name="arrow-right" :size="13" /></a></li>
        </ul>
      </AegisCard>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { usePricingStore } from '@/stores/pricing'

defineProps({
  summary: { type: Object, default: () => ({ lifetime_cents: 0, year_cents: 0, pending_cents: 0, payout_method: null }) },
  payouts: { type: Array,  default: () => [] },
})
const pricing = usePricingStore()
</script>
