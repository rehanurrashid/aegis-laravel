<!--
  ReviewRefundRequestModal.vue — provider reviews and approves or denies a refund request.

  Shows: client name, service, reason, refund type, amount.
  Provider can approve (Stripe refund fires) or deny with a mandatory note.

  Posts to: provider.services.refund.approve | provider.services.refund.deny
  AegisModal, AegisIcon, AegisBadge globally registered.
-->
<template>
  <AegisModal
    :model-value="modelValue"
    title="Review Refund Request"
    size="md"
    @update:model-value="close"
  >
    <template v-if="refundRequest">

      <!-- Request summary ─────────────────────────────────────────── -->
      <div class="rrr-summary">
        <div class="rrr-row">
          <span class="rrr-label">From</span>
          <span class="rrr-value">{{ refundRequest.requested_by_name }}</span>
        </div>
        <div class="rrr-row">
          <span class="rrr-label">Service</span>
          <span class="rrr-value">{{ refundRequest.service_title }}</span>
        </div>
        <div class="rrr-row">
          <span class="rrr-label">Session Date</span>
          <span class="rrr-value">{{ refundRequest.session_date }}</span>
        </div>
        <div class="rrr-row">
          <span class="rrr-label">Refund Requested</span>
          <span class="rrr-value rrr-amount">{{ refundRequest.amount_requested }}</span>
        </div>
        <div class="rrr-row">
          <span class="rrr-label">Refund Type</span>
          <span class="rrr-value">{{ refundRequest.refund_type_label }}</span>
        </div>
        <div class="rrr-row">
          <span class="rrr-label">Reason</span>
          <span class="rrr-value">{{ reasonLabel }}</span>
        </div>
        <div v-if="refundRequest.reason_detail" class="rrr-row rrr-row--detail">
          <span class="rrr-label">Detail</span>
          <span class="rrr-value">{{ refundRequest.reason_detail }}</span>
        </div>
        <div v-if="refundRequest.is_overdue" class="rrr-row rrr-row--overdue">
          <span class="rrr-label"></span>
          <span class="rrr-value">
            <AegisIcon name="alert-triangle" :size="13" />
            Response overdue — client may escalate to a dispute at any time.
          </span>
        </div>
      </div>

      <!-- Refund impact note ──────────────────────────────────────── -->
      <div class="alert alert-warning" style="margin-bottom:14px">
        <AegisIcon name="alert-triangle" :size="15" />
        <div>
          Approving will issue a Stripe refund of <strong>{{ refundRequest.amount_requested }}</strong>
          from your connected Stripe account back to the client's original payment method.
          This action is permanent.
        </div>
      </div>

      <!-- DENY FORM — shown when denying ─────────────────────────── -->
      <div v-if="activeAction === 'deny'" class="rrr-deny-form">
        <div class="form-group">
          <label class="form-label">
            Response to Client <span style="color:var(--red)">*</span>
          </label>
          <textarea
            v-model="denyForm.note"
            class="form-input"
            :class="{ 'is-error': fieldError('note') }"
            rows="4"
            placeholder="Explain why you're declining the refund. The client will see this message and may escalate to a dispute if unsatisfied."
            @blur="v$.note.$touch()"
          ></textarea>
          <div v-if="fieldError('note')" class="form-error">{{ fieldError('note') }}</div>
        </div>
      </div>

    </template>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="close">Cancel</button>

      <!-- Deny flow -->
      <template v-if="activeAction === 'deny'">
        <button type="button" class="btn btn-outline" @click="activeAction = null">Back</button>
        <button
          type="button"
          class="btn btn-danger"
          :disabled="denyForm.processing"
          @click="submitDeny"
        >
          <AegisIcon name="x" :size="13" />
          {{ denyForm.processing ? 'Submitting…' : 'Confirm Denial' }}
        </button>
      </template>

      <!-- Default buttons -->
      <template v-else>
        <button
          type="button"
          class="btn btn-outline btn-danger-outline"
          @click="activeAction = 'deny'"
        >
          <AegisIcon name="x" :size="13" />
          Deny Request
        </button>
        <button
          type="button"
          class="btn btn-success"
          :disabled="approveForm.processing"
          @click="submitApprove"
        >
          <AegisIcon name="check" :size="13" />
          {{ approveForm.processing ? 'Processing…' : `Approve Refund ${refundRequest?.amount_requested ?? ''}` }}
        </button>
      </template>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import useVuelidate from '@vuelidate/core'
import { required, helpers } from '@vuelidate/validators'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue:     { type: Boolean, default: false },
  refundRequest:  { type: Object,  default: null },
})

const emit = defineEmits(['update:modelValue', 'success'])

const toast = useToast()

const activeAction = ref(null) // null | 'deny'

const approveForm = useForm({})
const denyForm    = useForm({ note: '' })

const v$ = useVuelidate(
  { note: { required: helpers.withMessage('Please provide a response to the client.', required) } },
  denyForm,
  { $scope: false }
)
function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message ?? ''
  return denyForm.errors[field] ?? ''
}

const reasonLabel = computed(() => {
  const map = {
    session_did_not_occur:          'Session did not occur',
    provider_no_show:               'Provider did not show up',
    quality_issue:                  'Service quality issue',
    duplicate_charge:               'Duplicate charge',
    session_cancelled_by_provider:  'Session cancelled by provider',
    other:                          'Other',
  }
  return map[props.refundRequest?.reason] ?? props.refundRequest?.reason ?? '—'
})

watch(() => props.modelValue, (open) => {
  if (!open) {
    activeAction.value = null
    denyForm.reset()
    v$.value.$reset()
  }
})

function close() {
  emit('update:modelValue', false)
}

function submitApprove() {
  if (!props.refundRequest) return
  approveForm.post(route('provider.services.refund.approve', { refund: props.refundRequest.id }), {
    preserveScroll: true,
    onSuccess: () => {
      close()
      emit('success')
      toast.success(`Refund approved. ${props.refundRequest.amount_requested} will be returned to the client.`)
    },
    onError: () => toast.error('Could not process the refund. Please try again.'),
  })
}

async function submitDeny() {
  const ok = await v$.value.$validate()
  v$.value.$touch()
  if (!ok) return
  denyForm.post(route('provider.services.refund.deny', { refund: props.refundRequest.id }), {
    preserveScroll: true,
    onSuccess: () => {
      close()
      emit('success')
      toast.info('Refund denied. Client has been notified and may escalate if needed.')
    },
    onError: () => toast.error('Could not submit denial. Please try again.'),
  })
}
</script>

<style scoped>
.rrr-summary {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px 14px;
  margin-bottom: 14px;
}
.rrr-row {
  display: flex; justify-content: space-between; align-items: flex-start;
  gap: 10px; padding: 5px 0; border-bottom: 1px solid var(--border);
  font-size: 13px;
}
.rrr-row:last-child { border-bottom: none; }
.rrr-row--detail { align-items: flex-start; }
.rrr-row--overdue { color: var(--gold-dark); }
.rrr-label { color: var(--text-3); font-weight: 600; flex-shrink: 0; width: 130px; }
.rrr-value { font-weight: 700; color: var(--text); text-align: right; display: flex; align-items: center; gap: 5px; flex-wrap: wrap; justify-content: flex-end; }
.rrr-amount { font-size: 16px; color: var(--gold-dark); font-family: var(--font-serif, serif); }

.rrr-deny-form { margin-top: 4px; }

.btn-danger-outline {
  border-color: var(--red);
  color: var(--red);
}
.btn-danger-outline:hover {
  background: rgba(239,68,68,.07);
}
</style>
