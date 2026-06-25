<!--
  pages/business-partner/Contracts.vue — active + completed contracts.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Work"
      title="Contracts"
      :subtitle="`${activeCount} active · ${completedCount} completed`"
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="agreement" :value="activeCount" label="Active" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="check-circle" :value="completedCount" label="Completed" />
      <AegisStatChip icon="dollar" :value="pricing.formatCents(totalEarned)" label="Total earned" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
    </div>

    <nav class="tab-strip">
      <button type="button" class="tab-btn" :class="{ 'is-active': tab === 'active' }" @click="tab = 'active'">
        Active ({{ activeCount }})
      </button>
      <button type="button" class="tab-btn" :class="{ 'is-active': tab === 'completed' }" @click="tab = 'completed'">
        Completed ({{ completedCount }})
      </button>
    </nav>

    <AegisCard v-if="visible.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Contract</th>
            <th>Client</th>
            <th>Engagement</th>
            <th>Amount</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in visible" :key="c.id">
            <td class="data-table-primary">
              <a :href="route('bp.contracts.show', { contract: c.id })">{{ c.title }}</a>
            </td>
            <td>{{ c.client_name }}</td>
            <td>{{ c.engagement === 'fixed' ? 'Fixed' : 'Hourly' }}</td>
            <td>{{ pricing.formatCents(c.amount_cents) }}</td>
            <td><AegisBadge :label="c.status" :variant="variant(c.status)" /></td>
            <td>
              <a :href="route('bp.contracts.show', { contract: c.id })" class="btn btn-sm btn-outline">Open</a>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState v-else icon="agreement" :title="tab === 'active' ? 'No active contracts' : 'No completed contracts'" />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({ contracts: { type: Array, default: () => [] } })
const pricing = usePricingStore()
const tab = ref('active')

const activeCount = computed(() => props.contracts.filter((c) => c.status === 'active').length)
const completedCount = computed(() => props.contracts.filter((c) => c.status === 'completed').length)
const totalEarned = computed(() => props.contracts.filter((c) => c.status === 'completed').reduce((s, c) => s + (c.amount_cents || 0), 0))

const visible = computed(() => props.contracts.filter((c) => c.status === tab.value))

function variant(s) { return { active: 'green', completed: 'blue', cancelled: 'neutral' }[s] ?? 'neutral' }
</script>
