<!--
  RequestRefundModal.vue — client opens a refund request for a clinical session.

  Available refund types depend on payment_status (enforced in SessionRefundService).
  Vuelidate validates reason + reason_detail (if 'other').

  Posts to: provider.services.session.refund.store
  AegisModal, AegisIcon, AegisBadge globally registered.
-->
<template>
  <AegisModal
    :model-value="modelValue"
    title="Request a Refund"
    size="md"
    @update:model-value="close"
  >
    <template v-if="session">

      <!-- Session context bar ────────────────────────────────────── -->
      <div class="rr-context">
        <div class="rr-context-left">
          <div class="rr-context-name">{{ session.practitioner_name }}</div>
          <div class="rr-context-service">{{ session.service_title }}</div>
        </div>
        <div class="rr-context-right">
          <AegisBadge :label="session.payment_status_label" :variant="session.payment_status_variant" />
        </div>
      </div>

      <!-- Available refund types ─────────────────────────────────── -->
      <div class="form-group">
        <label class="form-label">Refund Amount <span style="color:var(--red)">*</span></label>
        <div class="rr-type-options">
          <label
            v-for="opt in availableTypes"
            :key="opt.value"
            class="rr-type-option"
            :class="{ 'is-selected': form.refund_type === opt.value }"
          >
            <input
              v-model="form.refund_type"
              type="radio"
              :value="opt.value"
              class="rr-type-radio"
              @change="v$.refund_type.$touch()"
            />
            <div class="rr-type-body">
              <div class="rr-type-label">{{ opt.label }}</div>
              <div class="rr-type-amount">{{ opt.amount }}</div>
              <div class="rr-type-desc">{{ opt.desc }}</div>
            </div>
          </label>
        </div>
        <div v-if="fieldError('refund_type')" class="form-error">{{ fieldError('refund_type') }}</div>
      </div>

      <!-- Reason dropdown ────────────────────────────────────────── -->
      <div class="form-group">
        <label class="form-label">Reason <span style="color:var(--red)">*</span></label>
        <select
          v-model="form.reason"
          class="form-select"
          :class="{ 'is-error': fieldError('reason') }"
          @blur="v$.reason.$touch()"
          @change="v$.reason.$touch()"
        >
          <option value="">Select a reason…</option>
          <option value="session_did_not_occur">Session did not occur</option>
          <option value="provider_no_show">Provider did not show up</option>
          <option value="quality_issue">Service quality issue</option>
          <option value="duplicate_charge">Duplicate charge</option>
          <option value="session_cancelled_by_provider">Session cancelled by provider</option>
          <option value="other">Other</option>
        </select>
        <div v-if="fieldError('reason')" class="form-error">{{ fieldError('reason') }}</div>
      </div>

      <!-- Detail (required if 'other') ────────────────────────────── -->
      <div class="form-group">
        <label class="form-label">
          Additional Detail
          <span v-if="form.reason === 'other'" style="color:var(--red)">*</span>
          <span v-else style="color:var(--text-4)"> (optional)</span>
        </label>
        <textarea
          v-model="form.reason_detail"
          class="form-input"
          :class="{ 'is-error': fieldError('reason_detail') }"
          rows="3"
          placeholder="Briefly describe what happened…"
          @blur="v$.reason_detail.$touch()"
        ></textarea>
        <div v-if="fieldError('reason_detail')" class="form-error">{{ fieldError('reason_detail') }}</div>
      </div>

      <!-- Process note ───────────────────────────────────────────── -->
      <div class="alert alert-info" style="margin:0">
        <AegisIcon name="info" :size="15" />
        <div>
          The provider has <strong>{{ replyDays }} days</strong> to respond.
          If denied, you may escalate to a formal dispute.
          Refunds pull from the provider's Stripe Connect account back to your original payment method.
        </div>
      </div>

      <div v-if="form.errors.refund" class="form-error" style="margin-top:8px">{{ form.errors.refund }}</div>

    </template>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="close">Cancel</button>
      <button
        type="button"
        class="btn btn-danger"
        :disabled="form.processing"
        @click="submit"
      >
        <AegisIcon name="arrow-left" :size="13" />
        <span v-if="form.processing" class="spinner spinner-sm" />
        {{ form.processing ? 'Submitting…' : 'Submit Refund Request' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import useVuelidate from '@vuelidate/core'
import { required, helpers, requiredIf } from '@vuelidate/validators'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  session:    { type: Object,  default: null },
})

const emit = defineEmits(['update:modelValue', 'success'])

const toast = useToast()

const REPLY_DAYS_DEFAULT = 5
const replyDays = REPLY_DAYS_DEFAULT

// useForm at top level
const form = useForm({
  refund_type:   '',
  reason:        '',
  reason_detail: '',
})

// Vuelidate
const rules = computed(() => ({
  refund_type: { required: helpers.withMessage('Please select a refund amount.', required) },
  reason:      { required: helpers.withMessage('Please select a reason.', required) },
  reason_detail: {
    requiredIfOther: helpers.withMessage(
      'Please describe the reason.',
      requiredIf(() => form.reason === 'other')
    ),
  },
}))
const v$ = useVuelidate(rules, form, { $scope: false })

function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message ?? ''
  return form.errors[field] ?? ''
}

// Available refund types based on payment_status
const availableTypes = computed(() => {
  const s = props.session
  if (!s) return []

  const depositAmt  = s.expected_deposit_label  ?? s.deposit_label  ?? '$0'
  const balanceAmt  = s.expected_balance_label   ?? s.balance_label  ?? '$0'
  const totalAmt    = s.amount ?? '$0'

  const all = [
    {
      value:  'deposit_only',
      label:  `Upfront only (${s.upfront_percentage ?? 30}%)`,
      amount: depositAmt,
      desc:   'Refund only the upfront portion paid at booking.',
      statuses: ['deposit_paid', 'paid', 'partially_refunded'],
    },
    {
      value:  'balance_only',
      label:  `Completion only (${100 - (s.upfront_percentage ?? 30)}%)`,
      amount: balanceAmt,
      desc:   'Refund only the completion payment.',
      statuses: ['paid', 'partially_refunded'],
    },
    {
      value:  'full',
      label:  'Full refund (100%)',
      amount: totalAmt,
      desc:   'Refund the full session amount.',
      statuses: ['deposit_paid', 'paid'],
    },
  ]

  const ps = s.payment_status ?? ''
  return all.filter(opt => opt.statuses.includes(ps))
})

// Reset when modal closes
watch(() => props.modelValue, (open) => {
  if (!open) {
    form.reset()
    v$.value.$reset()
  }
  // Auto-select if only one option
  if (open && availableTypes.value.length === 1) {
    form.refund_type = availableTypes.value[0].value
  }
})

function close() {
  emit('update:modelValue', false)
}

async function submit() {
  const ok = await v$.value.$validate()
  v$.value.$touch()
  if (!ok) return

  form.post(route('provider.services.session.refund.store', { session: props.session.id }), {
    preserveScroll: true,
    onSuccess: () => {
      close()
      emit('success')
      toast.success('Refund request submitted. The provider has been notified.')
    },
    onError: () => toast.error(form.errors.refund ?? 'Could not submit refund request.'),
  })
}
</script>

<style scoped>
.rr-context {
  display: flex; justify-content: space-between; align-items: center;
  background: var(--surface-2); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 10px 14px;
  margin-bottom: 16px;
}
.rr-context-name    { font-size: 14px; font-weight: 700; color: var(--text); }
.rr-context-service { font-size: 12px; color: var(--text-3); font-weight: 600; margin-top: 1px; }

.rr-type-options { display: flex; flex-direction: column; gap: 8px; margin-bottom: 4px; }
.rr-type-option {
  display: flex; align-items: flex-start; gap: 10px;
  border: 1px solid var(--border); border-radius: var(--radius);
  padding: 12px 14px; cursor: pointer;
  transition: border-color var(--transition), background var(--transition);
}
.rr-type-option:hover    { background: var(--badge-bg-gold); }
.rr-type-option.is-selected { border-color: var(--gold); background: var(--badge-bg-gold); }
.rr-type-radio   { margin-top: 3px; flex-shrink: 0; cursor: pointer; }
.rr-type-body    { flex: 1; }
.rr-type-label   { font-size: 13px; font-weight: 700; color: var(--text); }
.rr-type-amount  { font-size: 16px; font-weight: 700; color: var(--gold-dark); font-family: var(--font-serif, serif); margin: 2px 0; }
.rr-type-desc    { font-size: 11px; color: var(--text-3); }
</style>
