<!--
  pages/provider/Finances.vue — billing + plan + invoices.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Account"
      title="Finances"
      subtitle="Your subscription, payment methods, and recent activity."
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="dollar" :value="pricing.formatCents(monthlyCents)" label="Monthly" />
      <AegisStatChip icon="calendar" :value="nextBillLabel" label="Next bill" bg-color="var(--icon-bg-blue)" icon-color="var(--blue-dark)" />
      <AegisStatChip icon="receipt"  :value="invoices.length" label="Invoices on file" />
    </div>

    <div class="dashboard-grid">
      <AegisCard title="Current plan">
        <div class="plan-summary">
          <div class="plan-summary-name">
            {{ auth.isAccessTier ? 'Continuity Access' : 'Continuity Practice' }}
          </div>
          <div class="plan-summary-price">
            {{ pricing.formatCents(monthlyCents) }} <span>/ month</span>
          </div>
          <ul class="plan-summary-features">
            <li><AegisIcon name="check" :size="13" /> Up to {{ planFeatures.cs }} Continuity Steward(s)</li>
            <li><AegisIcon name="check" :size="13" /> Up to {{ planFeatures.ss }} Support Stewards</li>
            <li v-if="planFeatures.servicesMode"><AegisIcon name="check" :size="13" /> Services discoverability</li>
            <li><AegisIcon name="check" :size="13" /> Activity audit trail + vault</li>
          </ul>
        </div>
        <template #footer>
          <button
            v-if="auth.isAccessTier"
            type="button"
            class="btn btn-primary"
            @click="openModal('upgradeModal')"
          >
            Upgrade to Practice
          </button>
          <button v-else type="button" class="btn btn-ghost" @click="manageBilling">
            Manage billing
          </button>
        </template>
      </AegisCard>

      <AegisCard title="Payment method">
        <div v-if="paymentMethod" class="pm-row">
          <AegisIcon name="credit-card" :size="20" />
          <div>
            <div class="pm-brand">{{ paymentMethod.brand }} ···· {{ paymentMethod.last4 }}</div>
            <div class="pm-exp">Expires {{ paymentMethod.exp_month }}/{{ paymentMethod.exp_year }}</div>
          </div>
          <button type="button" class="btn btn-ghost btn-sm" @click="openModal('updateCardModal')">Update</button>
        </div>
        <AegisEmptyState v-else icon="credit-card" title="No card on file" description="Add one to continue billing automatically." />
      </AegisCard>
    </div>

    <AegisCard title="Invoices">
      <table v-if="invoices.length" class="data-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Amount</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="i in invoices" :key="i.id">
            <td>{{ i.issued_at }}</td>
            <td>{{ i.description }}</td>
            <td>{{ pricing.formatCents(i.amount_cents) }}</td>
            <td><AegisBadge :label="i.status" :variant="invStatusVariant(i.status)" /></td>
            <td>
              <a :href="i.pdf_url" class="btn btn-ghost btn-sm">
                <AegisIcon name="download" :size="13" /> PDF
              </a>
            </td>
          </tr>
        </tbody>
      </table>
      <AegisEmptyState v-else icon="receipt" title="No invoices yet" />
    </AegisCard>

    <AegisUpgradeModal />
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import AegisUpgradeModal from '@/components/ui/AegisUpgradeModal.vue'
import { useModal } from '@/composables/useModal'
import { useAuthStore } from '@/stores/auth'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  paymentMethod: { type: Object, default: null },
  invoices:      { type: Array,  default: () => [] },
  nextBillLabel: { type: String, default: '—' },
})

const { openModal } = useModal()
const auth = useAuthStore()
const pricing = usePricingStore()

const monthlyCents = computed(() => auth.isAccessTier ? pricing.tiers.access.monthly : pricing.tiers.practice.monthly)
const planFeatures = computed(() => auth.isAccessTier ? pricing.tiers.access : pricing.tiers.practice)

function invStatusVariant(s) { return { paid: 'green', open: 'gold', overdue: 'red' }[s] ?? 'neutral' }
function manageBilling() { window.location.href = route('billing.portal') }
</script>
