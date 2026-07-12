<!--
  MilestoneRefundModal.vue — Provider requests escrow refund on a milestone
  that has NOT yet been submitted by the BP (no dispute needed).

  Allowed statuses: pending, funded, in_progress
  For submitted milestones → use OpenDisputeModal instead (dispute path).

  Posts to: provider.jobs.contract.milestones.refund

  Props:
    contract   Object | null
    milestone  Object | null
-->
<template>
  <AegisModal
    :model-value="!!(contract && milestone && canRefund)"
    title="Refund milestone escrow"
    size="md"
    @update:model-value="onClose"
  >
    <div v-if="contract && milestone" class="ms-refund-modal">

      <!-- Summary -->
      <div class="ms-refund-summary">
        <div class="ms-refund-summary-row">
          <span class="ms-refund-label">Milestone</span>
          <span class="ms-refund-value">{{ milestone.title }}</span>
        </div>
        <div class="ms-refund-summary-row">
          <span class="ms-refund-label">Business Partner</span>
          <span class="ms-refund-value">{{ contract.bp?.display_name ?? '—' }}</span>
        </div>
        <div class="ms-refund-summary-row ms-refund-summary-row-bold">
          <span class="ms-refund-label">Escrow held</span>
          <span class="ms-refund-value ms-refund-amount">
            {{ pricing.formatCents(milestone.funded_cents || milestone.amount_cents) }}
          </span>
        </div>
      </div>

      <!-- Status notice -->
      <div v-if="milestone.funded_cents > 0" class="alert-banner alert-banner-warning">
        <AegisIcon name="shield-check" :size="14" />
        <span>
          These funds are currently held in Aegis escrow. Requesting a refund will return
          <strong>{{ pricing.formatCents(milestone.funded_cents) }}</strong>
          to your payment method.
        </span>
      </div>
      <div v-else class="alert-banner alert-banner-blue">
        <AegisIcon name="info" :size="14" />
        <span>
          This milestone has not been funded yet. No charge will occur — the milestone status
          will simply be reset to pending.
        </span>
      </div>

      <!-- Reason -->
      <div class="form-group">
        <label class="form-label" for="ms-refund-reason">
          Reason for refund <span class="req">*</span>
        </label>
        <textarea
          id="ms-refund-reason"
          v-model="form.reason"
          class="form-textarea"
          :class="{ 'is-error': fieldError('reason') }"
          rows="3"
          placeholder="Why are you requesting this refund? The Business Partner will be notified."
          @blur="v$.reason.$touch()"
        />
        <div v-if="fieldError('reason')" class="form-error">{{ fieldError('reason') }}</div>
      </div>

      <!-- Submitted milestone warning -->
      <div v-if="!canRefund" class="alert-banner alert-banner-warning">
        <AegisIcon name="alert-triangle" :size="14" />
        <span>
          This milestone has been submitted. Use the
          <strong>Review</strong> action to reject it and open a dispute instead.
        </span>
      </div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="form.processing" @click="onClose">
        Cancel
      </button>
      <button
        type="button"
        class="btn btn-danger"
        :disabled="form.processing || !canSubmit || !canRefund"
        @click="refund"
      >
        <AegisIcon v-if="form.processing" name="refresh-cw" :size="13" class="btn-spin" />
        {{ form.processing ? 'Processing…' : milestone?.funded_cents > 0 ? `Refund ${pricing.formatCents(milestone.funded_cents)}` : 'Reset milestone' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed } from 'vue'
import { useForm }    from '@inertiajs/vue3'
import useVuelidate   from '@vuelidate/core'
import { required, minLength, maxLength } from '@vuelidate/validators'
import { useToast }   from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  contract:  { type: Object, default: null },
  milestone: { type: Object, default: null },
})

const emit    = defineEmits(['update:modelValue'])
const toast   = useToast()
const pricing = usePricingStore()

const form = useForm({ reason: '' })

// ── Validation ────────────────────────────────────────────────────────────────
const rules = {
  reason: { required, minLength: minLength(5), maxLength: maxLength(500) },
}
const v$ = useVuelidate(rules, form)

function fieldError(f) {
  const e = v$.value[f]?.$errors
  return e?.length ? e[0].$message : null
}

// ── Computed ──────────────────────────────────────────────────────────────────
const canRefund = computed(() => {
  const s = props.milestone?.status
  return ['pending', 'funded', 'in_progress', 'pending_funding'].includes(s)
})

const canSubmit = computed(() =>
  form.reason.trim().length >= 5 && !form.processing,
)

// ── Actions ───────────────────────────────────────────────────────────────────
function onClose() {
  emit('update:modelValue', null)
  setTimeout(() => { form.reset(); v$.value.$reset() }, 200)
}

async function refund() {
  const valid = await v$.value.$validate()
  if (!valid) return

  form.post(route('provider.jobs.contract.milestones.refund', {
    contract:  props.contract.id,
    milestone: props.milestone.id,
  }), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Refund processed. Funds returned to your payment method.')
      onClose()
    },
    onError: (e) => toast.error(e.milestone ?? 'Refund failed.'),
  })
}
</script>

<style scoped>
/* ── Modal wrapper ───────────────────────────────────────────────── */
.ms-refund-modal {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* ── Summary card ────────────────────────────────────────────────── */
.ms-refund-summary {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
}
.ms-refund-summary-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
  padding: 11px 16px;
  border-bottom: 1px solid var(--border);
}
.ms-refund-summary-row:last-child { border-bottom: none; }
.ms-refund-summary-row-bold {
  background: var(--surface-3);
}
.ms-refund-label {
  font-family: var(--font-sans);
  font-size: 12px;
  color: var(--text-4);
  flex-shrink: 0;
}
.ms-refund-value {
  font-family: var(--font-sans);
  font-size: 13px;
  font-weight: 500;
  color: var(--text);
  text-align: right;
  word-break: break-word;
  min-width: 0;
}
.ms-refund-amount {
  font-family: var(--font-sans);
  font-size: 20px;
  font-weight: 700;
  color: var(--red);
}

/* ── Alert banners ───────────────────────────────────────────────── */
.alert-banner {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 14px;
  border-radius: var(--radius-sm);
  border-left: 3px solid transparent;
  font-family: var(--font-sans);
  font-size: 13px;
  line-height: 1.5;
}
.alert-banner > :deep(svg) { flex-shrink: 0; margin-top: 1px; }

.alert-banner-warning {
  background: rgba(160,129,62,0.07);
  border-left-color: var(--gold);
  color: var(--gold-dark);
}
.alert-banner-blue {
  background: var(--blue-light);
  border-left-color: var(--blue);
  color: var(--blue-dark);
}

/* ── Form ────────────────────────────────────────────────────────── */
.req {
  color: var(--red);
  margin-left: 2px;
}
</style>
