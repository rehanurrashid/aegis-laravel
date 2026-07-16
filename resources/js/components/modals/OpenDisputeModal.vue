<!--
  OpenDisputeModal.vue — reusable modal for opening a dispute against
  an invoice / payout / session.

  Props:
    modelValue     v-model
    subject        Object with { type, id, amount_cents, label }
                   type: 'cs_invoice' | 'bp_invoice' | 'bp_payout' | 'bp_milestone' | 'session'
    postRoute      Ziggy route name that posts to the portal-specific
                   disputes.store (provider.disputes.store, cs.disputes.store, bp.disputes.store)
-->
<template>
  <AegisModal :model-value="modelValue" title="Open a dispute" size="md" @update:model-value="(v) => $emit('update:modelValue', v)">
    <div v-if="subject" class="dispute-subject">
      <div class="dispute-subject-label">Disputing</div>
      <div class="dispute-subject-value">{{ subject.label }}</div>
      <div class="dispute-subject-amount">{{ formatCents(subject.amount_cents) }}</div>
    </div>

    <div class="form-stack">
      <div class="form-group">
        <label class="form-label">Reason</label>
        <select v-model="form.reason" class="form-input">
          <option value="">— Select a reason —</option>
          <option value="non_delivery">Work not delivered</option>
          <option value="quality_issue">Quality issue</option>
          <option value="unauthorized_charge">Unauthorized charge</option>
          <option value="duplicate_charge">Duplicate charge</option>
          <option value="wrong_amount">Wrong amount</option>
          <option value="other">Other</option>
        </select>
        <div v-if="form.errors.reason" class="form-error">{{ form.errors.reason }}</div>
      </div>

      <div class="form-group">
        <label class="form-label">Amount you're disputing (USD)</label>
        <input v-model.number="amountDollars" type="number" step="0.01" min="0.01" :max="maxDollars" class="form-input" />
        <div class="form-hint">Up to {{ formatCents(subject?.amount_cents ?? 0) }} of the transaction.</div>
        <div v-if="form.errors.amount_disputed_cents" class="form-error">{{ form.errors.amount_disputed_cents }}</div>
      </div>

      <div class="form-group">
        <label class="form-label">What happened?</label>
        <textarea
          v-model="form.description"
          class="form-input"
          rows="5"
          maxlength="5000"
          placeholder="Explain the issue in your own words. Be specific — the other party and (if escalated) an Aegis admin will read this."
        ></textarea>
        <div class="form-hint">{{ form.description.length }} / 5000 characters · minimum 20</div>
        <div v-if="form.errors.description" class="form-error">{{ form.errors.description }}</div>
      </div>

      <div class="dispute-notice">
        <AegisIcon name="info" :size="14" />
        <div v-if="subject?.type === 'bp_milestone'">
          Opening a dispute freezes the escrow funds held for this milestone.
          Aegis will mediate — funds remain in Aegis escrow until resolved. Admin may fully
          release to the Business Partner, fully refund to you, or split the amount.
        </div>
        <div v-else>
          Opening a dispute freezes payment on the underlying invoice until it's resolved.
          Aegis mediates — we don't hold funds, and any refund goes through Stripe's normal rails.
        </div>
      </div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="$emit('update:modelValue', false)">Cancel</button>
      <button
        type="button"
        class="btn btn-danger"
        :disabled="form.processing || !canSubmit"
        @click="submit"
      >
        {{ form.processing ? 'Opening…' : 'Open dispute' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  subject:    { type: Object, default: null },  // { type, id, amount_cents, label }
  postRoute:  { type: String, required: true },
})
const emit = defineEmits(['update:modelValue', 'opened'])

const toast = useToast()

const form = useForm({
  subject_type:          '',
  subject_id:            '',
  reason:                '',
  amount_disputed_cents: 0,
  description:           '',
})

const amountDollars = ref(null)
const maxDollars    = computed(() => ((props.subject?.amount_cents ?? 0) / 100).toFixed(2))

watch(() => amountDollars.value, (v) => {
  form.amount_disputed_cents = v ? Math.round(Number(v) * 100) : 0
})

// Reset + prefill when modal opens
watch(() => props.modelValue, (isOpen) => {
  if (!isOpen) return
  form.reset()
  form.clearErrors()
  amountDollars.value = ((props.subject?.amount_cents ?? 0) / 100).toFixed(2)
  form.subject_type = props.subject?.type ?? ''
  form.subject_id   = props.subject?.id ?? ''
})

const canSubmit = computed(() =>
  !!form.subject_type && !!form.subject_id && !!form.reason &&
  form.amount_disputed_cents > 0 && form.description.length >= 20
)

function formatCents(c) {
  const n = Number(c ?? 0) / 100
  return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function submit() {
  form.post(route(props.postRoute), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Dispute opened.')
      emit('opened')
      emit('update:modelValue', false)
    },
  })
}
</script>

<style scoped>
.dispute-subject     { display: flex; flex-direction: column; gap: 4px; padding: 12px 14px; background: var(--surface-2); border-radius: var(--radius-sm); margin-bottom: 14px; border-left: 3px solid var(--gold); }
.dispute-subject-label  { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-3); font-weight: 600; }
.dispute-subject-value  { font-size: 14px; font-weight: 600; color: var(--text); }
.dispute-subject-amount { font-family: var(--font-serif); font-size: 18px; font-weight: 700; color: var(--gold-dark); }
.form-stack          { display: flex; flex-direction: column; gap: 14px; }
.form-group          { display: flex; flex-direction: column; gap: 6px; }
.form-label          { font-size: 11px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--text-2); }
.form-input          { padding: 10px 12px; font-size: 13.5px; color: var(--text); background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); font-family: inherit; }
.form-input:focus    { border-color: var(--gold); outline: none; box-shadow: 0 0 0 3px rgba(196,169,106,.18); }
.form-error          { font-size: 12px; color: var(--red); }
.form-hint           { font-size: 11px; color: var(--text-4); }
.dispute-notice      { display: flex; align-items: flex-start; gap: 8px; padding: 12px; background: rgba(59,130,246,.05); border-radius: var(--radius-sm); font-size: 12px; color: var(--text-2); line-height: 1.5; }
</style>
