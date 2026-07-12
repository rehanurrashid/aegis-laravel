<!--
  FundMilestoneModal.vue — Provider funds a single milestone into Aegis escrow.
  Used in per_milestone funding mode: provider funds one milestone at a time
  as they're ready to begin each deliverable.

  Posts to: provider.jobs.contract.milestones.fund
  Props:
    contract    Object | null
    milestone   Object | null
-->
<template>
  <AegisModal
    :model-value="!!(contract && milestone)"
    title="Fund milestone escrow"
    size="md"
    @update:model-value="onClose"
  >
    <div v-if="contract && milestone" class="fund-modal">

      <!-- Milestone summary -->
      <div class="fund-modal-summary">
        <div class="fund-modal-summary-row">
          <span class="fund-modal-summary-label">Milestone</span>
          <span class="fund-modal-summary-value">{{ milestone.title }}</span>
        </div>
        <div v-if="milestone.description" class="fund-modal-summary-row">
          <span class="fund-modal-summary-label">Description</span>
          <span class="fund-modal-summary-value fund-modal-description">{{ milestone.description }}</span>
        </div>
        <div v-if="milestone.due_at" class="fund-modal-summary-row">
          <span class="fund-modal-summary-label">Due date</span>
          <span class="fund-modal-summary-value">{{ milestone.due_at }}</span>
        </div>
        <div class="fund-modal-summary-row fund-modal-summary-row-bold">
          <span class="fund-modal-summary-label">Amount to hold in escrow</span>
          <span class="fund-modal-summary-value fund-modal-amount">
            {{ pricing.formatCents(milestone.amount_cents) }}
          </span>
        </div>
      </div>

      <!-- Escrow disclosure -->
      <div class="fund-modal-disclosure">
        <AegisIcon name="shield-check" :size="14" />
        <div class="fund-modal-disclosure-text">
          Funds are held by Aegis and released to
          <strong>{{ contract.bp?.display_name ?? 'the Business Partner' }}</strong>
          when you approve their submitted work, or automatically after
          {{ autoReleaseDays }} days if you do not respond. You can request a refund
          if there is a dispute.
        </div>
      </div>

      <!-- Payment method -->
      <div class="fund-modal-pm">
        <AegisIcon name="credit-card" :size="14" />
        <span v-if="hasPaymentMethod">Charging your saved payment method</span>
        <span v-else class="fund-modal-pm-missing">
          No payment method saved.
          <a :href="route('provider.finances.index')" class="link-btn">Add one in Finances →</a>
        </span>
      </div>

      <!-- Already funded guard -->
      <div v-if="alreadyFunded" class="alert-banner alert-banner-green">
        <AegisIcon name="check-circle" :size="14" />
        <span>This milestone is already funded. No additional charge needed.</span>
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
        {{ form.processing ? 'Processing…' : `Hold ${pricing.formatCents(milestone?.amount_cents)} in escrow` }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  contract:         { type: Object,  default: null },
  milestone:        { type: Object,  default: null },
  hasPaymentMethod: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const toast           = useToast()
const pricing         = usePricingStore()
const autoReleaseDays = 7

const form = useForm({})

const alreadyFunded = computed(() => {
  const s = props.milestone?.status
  return ['funded', 'in_progress', 'submitted', 'revision_requested', 'approved', 'released', 'paid'].includes(s)
})

const canFund = computed(() =>
  props.hasPaymentMethod && !alreadyFunded.value && !form.processing,
)

function onClose() {
  emit('update:modelValue', null)
}

function fund() {
  if (!canFund.value || !props.contract || !props.milestone) return
  form.post(route('provider.jobs.contract.milestones.fund', {
    contract:  props.contract.id,
    milestone: props.milestone.id,
  }), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(`${props.milestone.title} funded. Business Partner can now begin work.`)
      onClose()
    },
    onError: (e) => toast.error(e.milestone ?? e.contract ?? 'Funding failed.'),
  })
}
</script>
