<!--
  PayCompletionModal.vue — client confirms session happened and pays completion portion.
  Rev 4: Replaces PayBalanceModal. Handles all three structures:
    full_upfront      → "Confirm Session Complete" (no charge)
    split             → "Confirm & Pay Completion ({pct}%)"
    full_on_completion → "Confirm & Pay Session (full amount)"

  Posts to: provider.services.session.complete
  AegisModal, AegisIcon globally registered.
-->
<template>
  <AegisModal
    :model-value="modelValue"
    :title="modalTitle"
    size="md"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <template v-if="session">

      <!-- Info note (structure-aware) ────────────────────────────── -->
      <div class="alert alert-info" style="margin-bottom:16px">
        <AegisIcon name="info" :size="16" />
        <div>{{ infoNote }}</div>
      </div>

      <!-- Payment summary ─────────────────────────────────────────── -->
      <div class="comp-summary">
        <div class="comp-row">
          <span class="comp-label">Provider</span>
          <span class="comp-value">{{ session.practitioner_name }}</span>
        </div>
        <div class="comp-row">
          <span class="comp-label">Service</span>
          <span class="comp-value">{{ session.service_title }}</span>
        </div>
        <div class="comp-row">
          <span class="comp-label">Session Date</span>
          <span class="comp-value">{{ session.datetime_label || 'Completed' }}</span>
        </div>
        <!-- Terms chip -->
        <div v-if="session.terms_summary" class="comp-row">
          <span class="comp-label">Payment Terms</span>
          <span class="comp-value">
            <AegisBadge :label="session.terms_summary" variant="gold" />
          </span>
        </div>
        <!-- Already paid upfront (split) -->
        <div v-if="isSplit" class="comp-row comp-row--paid">
          <span class="comp-label">
            <AegisIcon name="check-circle" :size="13" />
            Upfront Already Paid ({{ session.upfront_percentage ?? 30 }}%)
          </span>
          <span class="comp-value">{{ session.expected_deposit_label }}</span>
        </div>
        <!-- Amount due -->
        <div v-if="!isFullUpfront" class="comp-row comp-row--due">
          <span class="comp-label">{{ dueLabel }}</span>
          <span class="comp-value comp-amount">{{ session.expected_balance_label }}</span>
        </div>
        <div class="comp-row comp-row--total">
          <span class="comp-label">Total</span>
          <span class="comp-value">{{ session.amount }}</span>
        </div>
      </div>

      <!-- Payout status ──────────────────────────────────────────── -->
      <div v-if="!isFullUpfront"
           class="comp-payout"
           :class="session.practitioner_stripe_connected ? 'is-ready' : 'is-pending'">
        <AegisIcon :name="session.practitioner_stripe_connected ? 'check-circle' : 'alert-triangle'" :size="15" />
        <div>
          <div class="payout-title">
            {{ session.practitioner_stripe_connected ? 'Payment will transfer immediately' : 'Provider payout queued' }}
          </div>
          <div class="payout-desc">
            {{ session.practitioner_stripe_connected
              ? session.practitioner_name + ' has Stripe Connect — funds transfer on confirmation.'
              : session.practitioner_name + ' has not connected Stripe. Your payment will be held until they complete account setup.'
            }}
          </div>
        </div>
      </div>

      <!-- Disclosure note ────────────────────────────────────────── -->
      <p class="comp-note">
        {{ isFullUpfront
          ? 'This action is permanent. The session will be marked complete and the provider will be notified. No additional charge applies.'
          : 'This action is permanent. The session will be marked complete and the provider will be notified. Payment routes directly to the provider via Stripe Connect.'
        }}
      </p>

      <div v-if="form.errors.session" class="form-error" style="margin-top:4px">{{ form.errors.session }}</div>

    </template>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="$emit('update:modelValue', false)">Cancel</button>
      <button
        type="button"
        class="btn btn-success"
        :disabled="form.processing"
        @click="submit"
      >
        <AegisIcon name="check" :size="13" />
        {{ form.processing ? 'Processing…' : confirmBtnLabel }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  session:    { type: Object,  default: null },
})

const emit = defineEmits(['update:modelValue', 'success'])

const toast = useToast()

const form = useForm({})

const structure     = computed(() => props.session?.payment_structure ?? 'split')
const isFullUpfront = computed(() => structure.value === 'full_upfront')
const isSplit       = computed(() => structure.value === 'split')

const modalTitle = computed(() => {
  if (isFullUpfront.value) return 'Confirm Session Complete'
  if (isSplit.value)       return 'Confirm & Pay Completion Portion'
  return 'Confirm & Pay Session'
})

const infoNote = computed(() => {
  if (isFullUpfront.value)  return 'By confirming, you acknowledge the session took place. No additional charge applies — you paid in full upfront.'
  if (isSplit.value)        return 'By confirming, you acknowledge the session took place and authorize the completion payment to the provider.'
  return 'By confirming, you acknowledge the session took place and authorize the full session payment to the provider.'
})

const dueLabel = computed(() => {
  const pct = props.session?.upfront_percentage ?? 30
  if (isSplit.value)  return `Completion Due Now (${100 - pct}%)`
  return 'Full Payment Due Now (100%)'
})

const confirmBtnLabel = computed(() => {
  if (isFullUpfront.value) return 'Confirm Session Complete'
  const amtLabel = props.session?.expected_balance_label ?? 'Amount'
  return `Confirm & Pay ${amtLabel}`
})

function submit() {
  if (!props.session) return
  form.post(route('provider.services.session.complete', { session: props.session.id }), {
    preserveScroll: true,
    onSuccess: () => {
      emit('update:modelValue', false)
      emit('success')
      const name = props.session.practitioner_name ?? 'the provider'
      if (isFullUpfront.value) {
        toast.success('Session confirmed and marked complete.')
      } else if (props.session.practitioner_stripe_connected) {
        toast.success(`Session confirmed. Payment sent to ${name}.`)
      } else {
        toast.success(`Session confirmed. Payment will be released once ${name} connects Stripe.`)
      }
    },
    onError: () => toast.error(form.errors.session ?? 'Could not confirm session. Please try again.'),
  })
}
</script>

<style scoped>
.comp-summary {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px 14px;
  margin-bottom: 14px;
}
.comp-row {
  display: flex; justify-content: space-between; align-items: center;
  gap: 10px; padding: 6px 0; border-bottom: 1px solid var(--border);
}
.comp-row:last-child { border-bottom: none; }
.comp-row--paid  { color: var(--green); }
.comp-row--due   { background: var(--badge-bg-gold); margin: 0 -14px; padding: 8px 14px; }
.comp-row--total { font-size: 13px; font-weight: 700; border-top: 2px solid var(--border-dark); margin-top: 4px; }
.comp-label {
  display: flex; align-items: center; gap: 5px;
  font-size: 12px; font-weight: 600; flex-shrink: 0;
}
.comp-value { font-size: 13px; font-weight: 700; color: var(--text); }
.comp-amount { font-size: 18px; color: var(--gold-dark); font-family: var(--font-serif, serif); }

.comp-payout {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 12px 14px; border-radius: var(--radius); border: 1px solid;
  margin-bottom: 12px;
}
.comp-payout.is-ready  { background: rgba(34,197,94,.07); border-color: var(--green); color: var(--green); }
.comp-payout.is-pending { background: rgba(245,158,11,.07); border-color: var(--gold); color: var(--gold-dark); }
.payout-title { font-size: 13px; font-weight: 700; margin-bottom: 2px; }
.payout-desc  { font-size: 12px; color: var(--text-2); line-height: 1.5; }

.comp-note { font-size: 12px; color: var(--text-3); line-height: 1.6; margin: 0; }
</style>
