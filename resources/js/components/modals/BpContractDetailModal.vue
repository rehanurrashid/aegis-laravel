<!--
  BpContractDetailModal.vue — read-only contract detail view for the BP portal.

  Wave 1: Foundation — shows contract terms, milestone list, escrow state,
  signature status. Sign + Fund actions wired in Wave 4 when full EscrowService
  backend is available.

  Props:
    contract  Object | null  — shaped by ContractService::getForBp()
-->
<template>
  <AegisModal
    :model-value="!!contract"
    title="Contract details"
    size="xl"
    @update:model-value="onClose"
  >
    <div v-if="contract" class="bp-contract-detail">

      <!-- Header -->
      <div class="contract-detail-header">
        <div>
          <div class="contract-detail-eyebrow">Contract</div>
          <div class="contract-detail-title">{{ contract.title }}</div>
          <div class="contract-detail-client">
            <AegisIcon name="user" :size="13" />
            {{ contract.client_name }}
          </div>
        </div>
        <AegisBadge :label="statusLabel(contract.status)" :variant="statusVariant(contract.status)" />
      </div>

      <!-- Key info grid -->
      <div class="contract-detail-grid">
        <div class="contract-detail-item">
          <div class="contract-detail-label">Payment type</div>
          <div class="contract-detail-value">
            {{ contract.payment_type === 'milestone' ? 'Milestone-based' : 'One-time' }}
          </div>
        </div>
        <div class="contract-detail-item">
          <div class="contract-detail-label">Contract value</div>
          <div class="contract-detail-value">{{ pricing.formatCents(contract.amount_cents) }}</div>
        </div>
        <div class="contract-detail-item">
          <div class="contract-detail-label">Signed</div>
          <div class="contract-detail-value">{{ contract.signed_at ?? '—' }}</div>
        </div>
        <div v-if="contract.completed_at" class="contract-detail-item">
          <div class="contract-detail-label">Completed</div>
          <div class="contract-detail-value">{{ contract.completed_at }}</div>
        </div>
      </div>

      <!-- Escrow summary (active contracts) -->
      <div
        v-if="['active', 'pending_funding', 'pending_signature'].includes(contract.status)"
        class="contract-escrow-summary"
      >
        <div class="contract-escrow-summary-title">
          <AegisIcon name="shield-check" :size="14" />
          Escrow balance
        </div>
        <div class="contract-escrow-summary-grid">
          <div class="contract-escrow-summary-item">
            <div class="contract-escrow-summary-label">Total value</div>
            <div class="contract-escrow-summary-value">{{ pricing.formatCents(contract.amount_cents) }}</div>
          </div>
          <div class="contract-escrow-summary-item">
            <div class="contract-escrow-summary-label">Funded in escrow</div>
            <div class="contract-escrow-summary-value contract-escrow-funded">
              {{ pricing.formatCents(contract.escrow_funded_cents ?? 0) }}
            </div>
          </div>
          <div class="contract-escrow-summary-item">
            <div class="contract-escrow-summary-label">Released to you</div>
            <div class="contract-escrow-summary-value contract-escrow-released">
              {{ pricing.formatCents(contract.escrow_released_cents ?? 0) }}
            </div>
          </div>
          <div class="contract-escrow-summary-item">
            <div class="contract-escrow-summary-label">Still held</div>
            <div class="contract-escrow-summary-value">
              {{ pricing.formatCents((contract.escrow_funded_cents ?? 0) - (contract.escrow_released_cents ?? 0)) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Pending signature notice -->
      <div v-if="contract.status === 'pending_signature'" class="contract-detail-notice contract-detail-notice-gold">
        <AegisIcon name="file-pen" :size="14" />
        <span>This contract is awaiting signature from both parties before work can begin.</span>
      </div>

      <!-- Pending funding notice -->
      <div v-if="contract.status === 'pending_funding'" class="contract-detail-notice contract-detail-notice-blue">
        <AegisIcon name="hourglass" :size="14" />
        <span>Contract is fully signed and awaiting funding by the provider before milestones unlock.</span>
      </div>

      <!-- Milestones list (if present) -->
      <div v-if="contract.milestones && contract.milestones.length" class="contract-detail-section">
        <div class="contract-detail-section-title">Milestones</div>
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Title</th>
              <th>Amount</th>
              <th>Due</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(m, i) in contract.milestones" :key="m.id">
              <td class="data-table-muted">{{ i + 1 }}</td>
              <td class="data-table-primary">{{ m.title }}</td>
              <td>{{ pricing.formatCents(m.amount_cents) }}</td>
              <td>{{ m.due_at ?? '—' }}</td>
              <td><AegisBadge :label="msStatusLabel(m.status)" :variant="msStatusVariant(m.status)" /></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Contract terms (if available) -->
      <div v-if="contract.terms" class="contract-detail-section">
        <div class="contract-detail-section-title">Contract terms</div>
        <div class="contract-terms-text">{{ contract.terms }}</div>
      </div>

      <!-- Cancel notice for cancelled contracts -->
      <div v-if="contract.status === 'cancelled' && contract.cancel_reason" class="contract-detail-notice contract-detail-notice-red">
        <AegisIcon name="x-circle" :size="14" />
        <span><strong>Cancelled:</strong> {{ contract.cancel_reason }}</span>
      </div>
    </div>

    <template #footer>
      <a
        v-if="contract"
        :href="route('bp.milestones.index')"
        class="btn btn-outline"
      >
        <AegisIcon name="flag-2" :size="13" />
        View milestones
      </a>
      <button type="button" class="btn btn-ghost" @click="onClose">
        Close
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { usePricingStore } from '@/stores/pricing'

defineProps({
  contract: { type: Object, default: null },
})

const emit    = defineEmits(['update:modelValue'])
const pricing = usePricingStore()

function onClose() { emit('update:modelValue', null) }

function statusLabel(s) {
  return {
    draft:             'Draft',
    pending_signature: 'Awaiting Signature',
    pending_funding:   'Awaiting Funding',
    active:            'Active',
    completed:         'Completed',
    cancelled:         'Cancelled',
    disputed:          'Disputed',
  }[s] ?? s
}

function statusVariant(s) {
  return {
    draft:             'neutral',
    pending_signature: 'gold',
    pending_funding:   'blue',
    active:            'green',
    completed:         'blue',
    cancelled:         'neutral',
    disputed:          'red',
  }[s] ?? 'neutral'
}

function msStatusLabel(s) {
  return {
    pending:            'Pending',
    pending_funding:    'Awaiting Funding',
    funded:             'Funded',
    in_progress:        'In Progress',
    submitted:          'Under Review',
    revision_requested: 'Revision Requested',
    approved:           'Approved',
    released:           'Paid',
    paid:               'Paid',
    disputed:           'Disputed',
    refunded:           'Refunded',
  }[s] ?? s
}

function msStatusVariant(s) {
  return {
    pending:            'gold',
    pending_funding:    'neutral',
    funded:             'blue',
    in_progress:        'blue',
    submitted:          'blue',
    revision_requested: 'gold',
    approved:           'green',
    released:           'green',
    paid:               'green',
    disputed:           'red',
    refunded:           'neutral',
  }[s] ?? 'neutral'
}
</script>
