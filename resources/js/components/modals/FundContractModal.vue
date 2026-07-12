<!--
  FundContractModal.vue — Provider funds the full contract value into Aegis escrow.
  Used when funding_mode = 'full_upfront' OR when provider wants to pre-fund all milestones.

  Posts to: provider.jobs.contract.fund
  Props:
    contract  Object | null   — active contract row (from activeContracts prop)

  Behaviour:
  - Shows who the BP is, contract value, and current escrow balance
  - Explains escrow model with clear disclosure
  - Shows saved card (or warns if none)
  - Confirm button funds escrow via EscrowService::fundContract()
-->
<template>
  <AegisModal
    :model-value="!!contract"
    title="Fund contract escrow"
    size="md"
    @update:model-value="onClose"
  >
    <div v-if="contract" class="fund-modal">

      <!-- Contract summary -->
      <div class="fund-modal-summary">
        <div class="fund-modal-summary-row">
          <span class="fund-modal-summary-label">Contract</span>
          <span class="fund-modal-summary-value">{{ contract.title }}</span>
        </div>
        <div class="fund-modal-summary-row">
          <span class="fund-modal-summary-label">Business Partner</span>
          <span class="fund-modal-summary-value">{{ contract.bp?.display_name ?? '—' }}</span>
        </div>
        <div class="fund-modal-summary-row">
          <span class="fund-modal-summary-label">Total contract value</span>
          <span class="fund-modal-summary-value fund-modal-amount">{{ pricing.formatCents(contract.total_value_cents) }}</span>
        </div>
        <div v-if="alreadyFunded > 0" class="fund-modal-summary-row">
          <span class="fund-modal-summary-label">Already in escrow</span>
          <span class="fund-modal-summary-value fund-modal-amount-green">{{ pricing.formatCents(alreadyFunded) }}</span>
        </div>
        <div v-if="remainingCents > 0" class="fund-modal-summary-row fund-modal-summary-row-bold">
          <span class="fund-modal-summary-label">Amount to fund now</span>
          <span class="fund-modal-summary-value fund-modal-amount">{{ pricing.formatCents(remainingCents) }}</span>
        </div>
      </div>

      <!-- Escrow disclosure -->
      <div class="fund-modal-disclosure">
        <AegisIcon name="shield-check" :size="14" />
        <div class="fund-modal-disclosure-text">
          <strong>How escrow works:</strong> This amount will be charged to your saved payment
          method and held securely by Aegis. Funds are transferred to the Business Partner only
          after you approve their submitted work — or automatically after
          {{ autoReleaseDays }} days if you do not respond. If a dispute arises, Aegis mediates
          and may refund some or all funds to you.
        </div>
      </div>

      <!-- Payment method -->
      <div class="fund-modal-pm">
        <AegisIcon name="credit-card" :size="14" />
        <span v-if="hasPaymentMethod">
          Charging saved <strong>{{ cardBrand }} ···· {{ cardLast4 }}</strong>
        </span>
        <span v-else class="fund-modal-pm-missing">
          No payment method saved.
          <a :href="route('provider.settings.index') + '#payment-methods'" class="link-btn">Add one in Settings →</a>
        </span>
      </div>

      <!-- Already fully funded -->
      <div v-if="alreadyFullyFunded" class="alert-banner alert-banner-green">
        <AegisIcon name="check-circle" :size="14" />
        <span>This contract is already fully funded. No additional charge needed.</span>
      </div>

    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="form.processing" @click="onClose">
        Cancel
      </button>
      <button
        type="button"
        class="btn btn-primary"
        :disabled="form.processing || !canFund"
        @click="fund"
      >
        <AegisIcon v-if="form.processing" name="refresh-cw" :size="13" class="btn-spin" />
        {{ form.processing ? 'Processing…' : `Fund ${pricing.formatCents(remainingCents)} in escrow` }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  contract:          { type: Object,  default: null },
  hasPaymentMethod:  { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const toast          = useToast()
const pricing        = usePricingStore()
const page           = usePage()
const cardLast4      = computed(() => page.props.auth?.user?.pm_last4 ?? '····')
const cardBrand      = computed(() => {
  const b = page.props.auth?.user?.pm_brand
  return b ? b.charAt(0).toUpperCase() + b.slice(1) : 'Card'
})
const autoReleaseDays = 7

// useForm at top-level
const form = useForm({})

const alreadyFunded      = computed(() => (props.contract?.escrow_funded_cents ?? 0))
const remainingCents     = computed(() => Math.max(0, (props.contract?.total_value_cents ?? 0) - alreadyFunded.value))
const alreadyFullyFunded = computed(() => remainingCents.value === 0)
const canFund            = computed(() => props.hasPaymentMethod && !alreadyFullyFunded.value && !form.processing)

function onClose() {
  emit('update:modelValue', null)
}

function fund() {
  if (!canFund.value) return
  form.post(route('provider.jobs.contract.fund', { contract: props.contract.id }), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Contract funded. All milestones are now active.')
      onClose()
    },
    onError: (e) => toast.error(e.contract ?? 'Funding failed. Please try again.'),
  })
}
</script>
