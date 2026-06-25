<!--
  pages/continuity-steward/Finances.vue — CS plan (Business CS) + invoices.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Account"
      title="Finances"
      subtitle="Your Continuity Steward subscription and earnings."
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="dollar"  :value="pricing.formatCents(monthlyCents)" label="Monthly plan" />
      <AegisStatChip icon="users"   :value="practitionerCount" label="Practitioners served" bg-color="var(--icon-bg-blue)" icon-color="var(--blue-dark)" />
      <AegisStatChip icon="receipt" :value="invoices.length" label="Invoices on file" />
    </div>

    <AegisCard title="Plan">
      <div class="plan-summary">
        <div class="plan-summary-name">
          {{ planLabel }}
        </div>
        <div class="plan-summary-price">{{ pricing.formatCents(monthlyCents) }} / month</div>
        <ul class="plan-summary-features">
          <li v-if="isFree"><AegisIcon name="check" :size="13" /> Serve 1 invited practitioner</li>
          <li v-else>
            <AegisIcon name="check" :size="13" /> Serve 2–40 practitioners
          </li>
          <li><AegisIcon name="check" :size="13" /> Full CS Portal access</li>
        </ul>
      </div>
      <template #footer>
        <button v-if="isFree" type="button" class="btn btn-primary" @click="openModal('upgradeCSModal')">
          Upgrade to Business CS
        </button>
      </template>
    </AegisCard>

    <AegisCard title="Invoices">
      <table v-if="invoices.length" class="data-table">
        <thead><tr><th>Date</th><th>Description</th><th>Amount</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <tr v-for="i in invoices" :key="i.id">
            <td>{{ i.issued_at }}</td>
            <td>{{ i.description }}</td>
            <td>{{ pricing.formatCents(i.amount_cents) }}</td>
            <td><AegisBadge :label="i.status" :variant="{ paid: 'green', open: 'gold', overdue: 'red' }[i.status] ?? 'neutral'" /></td>
            <td><a :href="i.pdf_url" class="btn btn-ghost btn-sm">PDF</a></td>
          </tr>
        </tbody>
      </table>
      <AegisEmptyState v-else icon="receipt" title="No invoices yet" />
    </AegisCard>

    <UpgradeCSModal />
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import UpgradeCSModal from '@/components/modals/UpgradeCSModal.vue'
import { useModal } from '@/composables/useModal'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  plan:              { type: String, default: 'free' },
  invoices:          { type: Array,  default: () => [] },
  practitionerCount: { type: Number, default: 0 },
})

const { openModal } = useModal()
const pricing = usePricingStore()

const isFree = computed(() => props.plan === 'free')
const planLabel = computed(() => isFree.value ? 'Free Invited Account' : 'MAAT Business CS Plan')
const monthlyCents = computed(() => isFree.value ? 0 : 4900)
</script>
