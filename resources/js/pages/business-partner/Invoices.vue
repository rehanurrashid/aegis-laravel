<!--
  pages/business-partner/Invoices.vue — invoices issued to clients.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Money"
      title="Invoices"
      :subtitle="`${pendingCount} pending · ${pricing.formatCents(outstandingTotal)} outstanding`"
    >
      <template #actions>
        <button type="button" class="btn btn-primary" @click="openModal('createInvoiceModal')">
          <AegisIcon name="plus" :size="14" />
          <span>Create invoice</span>
        </button>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="receipt" :value="invoices.length" label="Total invoices" />
      <AegisStatChip icon="hourglass" :value="pendingCount" label="Pending" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
      <AegisStatChip icon="check-circle" :value="paidCount" label="Paid" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="dollar" :value="pricing.formatCents(outstandingTotal)" label="Outstanding" />
    </div>

    <AegisCard v-if="invoices.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Invoice #</th>
            <th>Client</th>
            <th>Contract</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Issued</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="i in invoices" :key="i.id">
            <td class="data-table-primary">{{ i.number }}</td>
            <td>{{ i.client_name }}</td>
            <td>
              <a v-if="i.contract_id" :href="route('bp.contracts.show', { contract: i.contract_id })">
                {{ i.contract_title }}
              </a>
              <span v-else>—</span>
            </td>
            <td>{{ pricing.formatCents(i.amount_cents) }}</td>
            <td><AegisBadge :label="i.status" :variant="variant(i.status)" /></td>
            <td>{{ i.issued_at }}</td>
            <td>
              <a :href="i.pdf_url" class="btn btn-ghost btn-icon btn-sm" data-tip="PDF">
                <AegisIcon name="download" :size="13" />
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState v-else icon="receipt" title="No invoices yet" description="Once you bill a client, the invoice will appear here." />
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useModal } from '@/composables/useModal'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({ invoices: { type: Array, default: () => [] } })
const { openModal } = useModal()
const pricing = usePricingStore()

const pendingCount     = computed(() => props.invoices.filter((i) => i.status === 'pending' || i.status === 'open').length)
const paidCount        = computed(() => props.invoices.filter((i) => i.status === 'paid').length)
const outstandingTotal = computed(() => props.invoices.filter((i) => i.status !== 'paid' && i.status !== 'cancelled').reduce((s, i) => s + (i.amount_cents || 0), 0))

function variant(s) { return { paid: 'green', open: 'gold', pending: 'gold', overdue: 'red', cancelled: 'neutral' }[s] ?? 'neutral' }
</script>
