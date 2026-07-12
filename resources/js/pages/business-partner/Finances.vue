<!--
  pages/business-partner/Finances.vue — earnings, escrow, and payout history.

  Wave 1 fixes:
  - Props now match FinancesController output: summary{} + payouts[] + stripe_connected
  - bp.payment-setup (non-existent) → bp.payment.index
  - Redundant global component local imports removed
  - Stripe Connect status indicator added (warn if not connected = payments blocked)
  - Full payout history table replacing the minimal "quick links" shell
  - "In escrow" chip shows pending milestone values held in Aegis balance
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Money"
      title="Finances"
      subtitle="Earnings, escrow, and payout history."
    />

    <!-- Stripe Connect warning banner -->
    <div v-if="!stripeConnected" class="alert-banner alert-banner-warning">
      <AegisIcon name="alert-triangle" :size="16" />
      <span>
        Your Stripe Connect account is not active. Payments cannot be released to you until you complete onboarding.
        <a :href="route('bp.payment.index')" class="alert-link">Set up payouts →</a>
      </span>
    </div>

    <div class="stat-chips-row">
      <AegisStatChip
        icon="dollar"
        :value="pricing.formatCents(summary.lifetime_cents)"
        label="Lifetime earned"
      />
      <AegisStatChip
        icon="trending-up"
        :value="pricing.formatCents(summary.year_cents)"
        label="This year"
        bg-color="var(--icon-bg-green)"
        icon-color="var(--green-dark)"
      />
      <AegisStatChip
        icon="shield-check"
        :value="pricing.formatCents(summary.pending_cents)"
        label="In escrow (pending)"
        bg-color="var(--icon-bg-gold)"
        icon-color="var(--gold-dark)"
      />
      <AegisStatChip
        icon="bank"
        :value="summary.payout_method ?? 'Not connected'"
        label="Payout method"
        :bg-color="stripeConnected ? 'var(--icon-bg-blue)' : 'var(--icon-bg-red)'"
        :icon-color="stripeConnected ? 'var(--blue-dark)' : 'var(--red-dark)'"
      />
    </div>

    <!-- Quick links -->
    <div class="dashboard-grid">
      <AegisCard title="Quick links">
        <ul class="quick-links">
          <li>
            <a :href="route('bp.invoices.index')">
              View invoices
              <AegisIcon name="arrow-right" :size="13" />
            </a>
          </li>
          <li>
            <a :href="route('bp.payment.index')">
              Manage payout method
              <AegisIcon name="arrow-right" :size="13" />
            </a>
          </li>
          <li>
            <a :href="route('bp.contracts.index')">
              Active contracts
              <AegisIcon name="arrow-right" :size="13" />
            </a>
          </li>
          <li>
            <a :href="route('bp.tax.index')">
              Tax documents (W-9 / 1099)
              <AegisIcon name="arrow-right" :size="13" />
            </a>
          </li>
        </ul>
      </AegisCard>

      <!-- Escrow note -->
      <AegisCard title="About escrow">
        <p class="card-body-text">
          Aegis holds milestone payments in escrow on your behalf. Funds are released
          to your Stripe Connect account when a provider approves your submitted work,
          or automatically after {{ autoReleaseDays }} days if the provider does not respond.
        </p>
        <p class="card-body-text card-body-text-secondary">
          If a dispute arises, Aegis mediates and releases or refunds accordingly.
          You can open a dispute from the Milestones page.
        </p>
      </AegisCard>
    </div>

    <!-- Payout history -->
    <AegisCard title="Payout history" class="payout-history-card">
      <AegisEmptyState
        v-if="!payouts.length"
        icon="bank"
        title="No payouts yet"
        description="Completed milestones will appear here once released."
      />

      <table v-else class="data-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Paid at</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in payouts" :key="p.id">
            <td>{{ p.created_at ?? '—' }}</td>
            <td class="data-table-primary">{{ p.description || 'Milestone payment' }}</td>
            <td>{{ pricing.formatCents(p.amount_cents) }}</td>
            <td>
              <AegisBadge
                :label="payoutStatusLabel(p.status)"
                :variant="payoutStatusVariant(p.status)"
              />
            </td>
            <td>
              <span v-if="p.refunded_at" class="payout-refunded-note">
                Refunded {{ p.refunded_at }} ({{ pricing.formatCents(p.refunded_cents) }})
              </span>
              <span v-else>{{ p.paid_at ?? '—' }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { computed } from 'vue'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  summary: {
    type: Object,
    default: () => ({ lifetime_cents: 0, year_cents: 0, pending_cents: 0, payout_method: null }),
  },
  payouts:          { type: Array,   default: () => [] },
  stripe_connected: { type: Boolean, default: false },
})

const stripeConnected = computed(() => props.stripe_connected)
const pricing         = usePricingStore()
const autoReleaseDays = 7 // matches MILESTONE_AUTO_RELEASE_DAYS env

function payoutStatusLabel(s) {
  return { pending: 'Pending', in_transit: 'In Transit', paid: 'Paid', failed: 'Failed', cancelled: 'Cancelled' }[s] ?? s
}
function payoutStatusVariant(s) {
  return { pending: 'gold', in_transit: 'blue', paid: 'green', failed: 'red', cancelled: 'neutral' }[s] ?? 'neutral'
}
</script>
