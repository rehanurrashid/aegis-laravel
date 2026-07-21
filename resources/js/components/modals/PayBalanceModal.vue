<!--
  PayBalanceModal.vue — client confirms session happened and pays the 70% balance.

  Shows: deposit already paid, balance due, payout status.
  This is the "Confirm & Pay Balance" action — replaces old "Confirm & Pay" single-charge.

  Posts to: provider.services.session.complete
  AegisModal, AegisIcon globally registered.
-->
<template>
  <AegisModal
    :model-value="modelValue"
    title="Confirm Session &amp; Pay Balance"
    size="md"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <template v-if="session">

      <!-- Info note ──────────────────────────────────────────────── -->
      <div class="alert alert-info" style="margin-bottom:16px">
        <AegisIcon name="info" :size="16" />
        <div>
          By confirming, you acknowledge the session took place and authorize
          the final 70% payment to the provider.
        </div>
      </div>

      <!-- Payment summary ─────────────────────────────────────────── -->
      <div class="balance-summary">
        <div class="balance-row">
          <span class="balance-label">Provider</span>
          <span class="balance-value">{{ session.practitioner_name }}</span>
        </div>
        <div class="balance-row">
          <span class="balance-label">Service</span>
          <span class="balance-value">{{ session.service_title }}</span>
        </div>
        <div class="balance-row">
          <span class="balance-label">Session Date</span>
          <span class="balance-value">{{ session.datetime_label || 'Completed' }}</span>
        </div>
        <div class="balance-row balance-row--paid">
          <span class="balance-label">
            <AegisIcon name="check-circle" :size="13" />
            Deposit Already Paid (30%)
          </span>
          <span class="balance-value">{{ session.expected_deposit_label }}</span>
        </div>
        <div class="balance-row balance-row--due">
          <span class="balance-label">Balance Due Now (70%)</span>
          <span class="balance-value balance-amount">{{ session.expected_balance_label }}</span>
        </div>
        <div class="balance-row balance-row--total">
          <span class="balance-label">Total Agreed Rate</span>
          <span class="balance-value">{{ session.amount }}</span>
        </div>
      </div>

      <!-- Payout status ──────────────────────────────────────────── -->
      <div
        class="balance-payout"
        :class="session.practitioner_stripe_connected ? 'is-ready' : 'is-pending'"
      >
        <AegisIcon
          :name="session.practitioner_stripe_connected ? 'check-circle' : 'alert-triangle'"
          :size="15"
        />
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

      <p class="balance-note">
        This action is permanent. The session will be marked complete and the provider will be notified.
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
        <span v-if="form.processing" class="spinner spinner-sm" />
        {{ form.processing ? 'Processing…' : `Confirm & Pay ${session?.expected_balance_label ?? 'Balance'}` }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
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

function submit() {
  if (!props.session) return
  form.post(route('provider.services.session.complete', { session: props.session.id }), {
    preserveScroll: true,
    onSuccess: () => {
      emit('update:modelValue', false)
      emit('success')
      const connected = props.session.practitioner_stripe_connected
      const name      = props.session.practitioner_name ?? 'the provider'
      toast.success(connected
        ? `Session confirmed. Payment sent to ${name}.`
        : `Session confirmed. Payment will be released once ${name} connects Stripe.`
      )
    },
    onError: () => toast.error(form.errors.session ?? 'Could not confirm session. Please try again.'),
  })
}
</script>

<style scoped>
.balance-summary {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px 14px;
  margin-bottom: 14px;
}
.balance-row {
  display: flex; justify-content: space-between; align-items: center;
  gap: 10px; padding: 6px 0; border-bottom: 1px solid var(--border);
}
.balance-row:last-child { border-bottom: none; }
.balance-row--paid  { color: var(--green); }
.balance-row--due   { background: var(--badge-bg-gold); margin: 0 -14px; padding: 8px 14px; }
.balance-row--total { font-size: 13px; font-weight: 700; border-top: 2px solid var(--border-dark); margin-top: 4px; }
.balance-label {
  display: flex; align-items: center; gap: 5px;
  font-size: 12px; font-weight: 600; flex-shrink: 0;
}
.balance-value { font-size: 13px; font-weight: 700; color: var(--text); }
.balance-amount { font-size: 18px; color: var(--gold-dark); font-family: var(--font-serif, serif); }

.balance-payout {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 12px 14px; border-radius: var(--radius); border: 1px solid;
  margin-bottom: 12px;
}
.balance-payout.is-ready  { background: rgba(34,197,94,.07); border-color: var(--green); color: var(--green); }
.balance-payout.is-pending { background: rgba(245,158,11,.07); border-color: var(--gold); color: var(--gold-dark); }
.payout-title { font-size: 13px; font-weight: 700; margin-bottom: 2px; }
.payout-desc  { font-size: 12px; color: var(--text-2); line-height: 1.5; }

.balance-note {
  font-size: 12px; color: var(--text-3); line-height: 1.6; margin: 0;
}
</style>
