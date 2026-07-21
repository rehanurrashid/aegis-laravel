<!--
  PayDepositModal.vue — client confirms and pays the 30% session deposit.

  Shows: provider name, service, agreed amount, deposit amount, Stripe Connect status.
  Client must tick "agree_terms" before paying.

  Posts to: provider.services.session.deposit
  AegisModal, AegisIcon, AegisBadge globally registered.
-->
<template>
  <AegisModal
    :model-value="modelValue"
    title="Pay Session Deposit"
    size="sm"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <template v-if="session">

      <!-- Connect status alert ───────────────────────────────────── -->
      <div
        v-if="session.practitioner_stripe_connected"
        class="alert alert-success"
        style="margin-bottom:16px"
      >
        <AegisIcon name="check-circle" :size="16" />
        <div>Payment routes directly to the provider's Stripe account — no intermediary holds your money.</div>
      </div>
      <div v-else class="alert alert-warning" style="margin-bottom:16px">
        <AegisIcon name="alert-triangle" :size="16" />
        <div>Provider has not connected Stripe yet. Your deposit will be queued and released once they complete account setup.</div>
      </div>

      <!-- Session summary ─────────────────────────────────────────── -->
      <div class="deposit-summary">
        <div class="deposit-row">
          <span class="deposit-label">Provider</span>
          <span class="deposit-value">{{ session.practitioner_name }}</span>
        </div>
        <div class="deposit-row">
          <span class="deposit-label">Service</span>
          <span class="deposit-value">{{ session.service_title }}</span>
        </div>
        <div class="deposit-row">
          <span class="deposit-label">Scheduled</span>
          <span class="deposit-value">{{ session.datetime_label || 'TBD' }}</span>
        </div>
        <div class="deposit-row">
          <span class="deposit-label">Agreed Rate</span>
          <span class="deposit-value">{{ session.amount }}</span>
        </div>
        <div class="deposit-row deposit-row--highlight">
          <span class="deposit-label">Deposit Due Now (30%)</span>
          <span class="deposit-value deposit-amount">{{ session.expected_deposit_label }}</span>
        </div>
        <div class="deposit-row deposit-row--sub">
          <span class="deposit-label">Balance Due at Completion (70%)</span>
          <span class="deposit-value">{{ session.expected_balance_label }}</span>
        </div>
      </div>

      <!-- Terms checkbox ─────────────────────────────────────────── -->
      <label class="deposit-terms">
        <input
          v-model="form.agree_terms"
          type="checkbox"
          class="deposit-check"
        />
        <span>
          I understand this deposit is non-refundable except via an approved refund request.
          By paying, I confirm my intent to attend the scheduled session.
        </span>
      </label>
      <div v-if="form.errors.agree_terms" class="form-error" style="margin-top:4px">
        {{ form.errors.agree_terms }}
      </div>
      <div v-if="form.errors.deposit" class="form-error" style="margin-top:4px">
        {{ form.errors.deposit }}
      </div>

    </template>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="$emit('update:modelValue', false)">Cancel</button>
      <button
        type="button"
        class="btn btn-primary"
        :disabled="form.processing || !form.agree_terms"
        @click="submit"
      >
        <AegisIcon name="credit-card" :size="13" />
        <span v-if="form.processing" class="spinner spinner-sm" />
        {{ form.processing ? 'Processing…' : `Pay ${session?.expected_deposit_label ?? 'Deposit'}` }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  session:    { type: Object,  default: null },
})

const emit = defineEmits(['update:modelValue', 'success'])

const toast = useToast()

// useForm at top level
const form = useForm({
  agree_terms: false,
})

// Reset checkbox when modal closes
watch(() => props.modelValue, (open) => {
  if (!open) form.reset()
})

function submit() {
  if (!props.session) return
  if (!form.agree_terms) {
    toast.error('Please confirm you understand the deposit policy.')
    return
  }
  form.post(route('provider.services.session.deposit', { session: props.session.id }), {
    preserveScroll: true,
    onSuccess: () => {
      emit('update:modelValue', false)
      emit('success')
      toast.success('Deposit paid. Your session is confirmed.')
      form.reset()
    },
    onError: () => toast.error(form.errors.deposit ?? 'Deposit payment failed. Please try again.'),
  })
}
</script>

<style scoped>
.deposit-summary {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px 14px;
  margin-bottom: 14px;
}
.deposit-row {
  display: flex; justify-content: space-between; align-items: baseline;
  gap: 10px; padding: 5px 0; border-bottom: 1px solid var(--border);
}
.deposit-row:last-child { border-bottom: none; }
.deposit-row--highlight { background: var(--badge-bg-gold); margin: 0 -14px; padding: 8px 14px; }
.deposit-row--sub { opacity: .7; }
.deposit-label { font-size: 12px; color: var(--text-3); font-weight: 600; flex-shrink: 0; }
.deposit-value { font-size: 13px; font-weight: 700; color: var(--text); text-align: right; }
.deposit-amount { font-size: 18px; color: var(--gold-dark); font-family: var(--font-serif, serif); }

.deposit-terms {
  display: flex; align-items: flex-start; gap: 10px;
  font-size: 12px; color: var(--text-2); line-height: 1.55;
  cursor: pointer;
}
.deposit-check { margin-top: 2px; flex-shrink: 0; cursor: pointer; }
</style>
