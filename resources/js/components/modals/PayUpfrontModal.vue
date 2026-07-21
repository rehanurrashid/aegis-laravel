<!--
  PayUpfrontModal.vue — client confirms and pays the upfront portion.
  Rev 4: Replaces PayDepositModal. Handles full_upfront (100%), split (configured %),
  and blocks full_on_completion (no upfront charge for that structure).

  Posts to: provider.services.session.upfront
  AegisModal, AegisIcon, AegisBadge globally registered.
-->
<template>
  <AegisModal
    :model-value="modelValue"
    :title="modalTitle"
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
        <div>Provider has not connected Stripe yet. Your payment will be queued and released once they complete account setup.</div>
      </div>

      <!-- Session summary ─────────────────────────────────────────── -->
      <div class="upfront-summary">
        <div class="upfront-row">
          <span class="upfront-label">Provider</span>
          <span class="upfront-value">{{ session.practitioner_name }}</span>
        </div>
        <div class="upfront-row">
          <span class="upfront-label">Service</span>
          <span class="upfront-value">{{ session.service_title }}</span>
        </div>
        <div class="upfront-row">
          <span class="upfront-label">Scheduled</span>
          <span class="upfront-value">{{ session.datetime_label || 'TBD' }}</span>
        </div>
        <div class="upfront-row">
          <span class="upfront-label">Agreed Rate</span>
          <span class="upfront-value">{{ session.amount }}</span>
        </div>
        <!-- Terms chip -->
        <div class="upfront-row" v-if="session.terms_summary">
          <span class="upfront-label">Payment Terms</span>
          <span class="upfront-value">
            <AegisBadge :label="session.terms_summary" variant="gold" />
          </span>
        </div>
        <div class="upfront-row upfront-row--highlight">
          <span class="upfront-label">{{ upfrontLabel }}</span>
          <span class="upfront-value upfront-amount">{{ session.expected_deposit_label }}</span>
        </div>
        <div v-if="hasCompletionDue" class="upfront-row upfront-row--sub">
          <span class="upfront-label">Completion payment</span>
          <span class="upfront-value">{{ session.expected_balance_label }}</span>
        </div>
      </div>

      <!-- Terms note from provider ───────────────────────────────── -->
      <div v-if="session.terms_note" class="upfront-terms-note">
        <AegisIcon name="info" :size="13" />
        <span>{{ session.terms_note }}</span>
      </div>

      <!-- Agreement checkbox ──────────────────────────────────────── -->
      <label class="upfront-agree">
        <input v-model="form.agree_terms" type="checkbox" class="upfront-check" />
        <span>
          I understand this payment routes directly to the provider via Stripe Connect.
          Aegis does not hold or escrow funds on my behalf.
          {{ isFullUpfront
            ? 'This is the full session payment.'
            : 'The remaining balance will be collected after the session is confirmed complete.' }}
        </span>
      </label>
      <div v-if="form.errors.agree_terms" class="form-error" style="margin-top:4px">
        {{ form.errors.agree_terms }}
      </div>
      <div v-if="form.errors.upfront" class="form-error" style="margin-top:4px">
        {{ form.errors.upfront }}
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
        {{ form.processing ? 'Processing…' : confirmLabel }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  session:    { type: Object,  default: null },
})

const emit = defineEmits(['update:modelValue', 'success'])

const toast = useToast()

const form = useForm({ agree_terms: false })

const structure     = computed(() => props.session?.payment_structure ?? 'split')
const isFullUpfront = computed(() => structure.value === 'full_upfront')
const pct           = computed(() => props.session?.upfront_percentage ?? 30)

const hasCompletionDue = computed(() => structure.value === 'split')

const modalTitle = computed(() => {
  if (isFullUpfront.value) return 'Pay Session in Full'
  return 'Pay Upfront Portion'
})

const upfrontLabel = computed(() => {
  if (isFullUpfront.value) return 'Due Now (full payment)'
  return `Due Now (${pct.value}% upfront)`
})

const confirmLabel = computed(() => {
  const amtLabel = props.session?.expected_deposit_label ?? 'amount'
  if (isFullUpfront.value) return `Pay ${amtLabel} (full amount)`
  return `Pay ${amtLabel} (${pct.value}% upfront)`
})

watch(() => props.modelValue, (open) => {
  if (!open) form.reset()
})

function submit() {
  if (!props.session) return
  if (!form.agree_terms) {
    toast.error('Please confirm you understand the payment terms.')
    return
  }
  form.post(route('provider.services.session.upfront', { session: props.session.id }), {
    preserveScroll: true,
    onSuccess: () => {
      emit('update:modelValue', false)
      emit('success')
      const msg = isFullUpfront.value
        ? 'Payment complete. Your session is confirmed.'
        : 'Upfront payment sent. Balance due after session.'
      toast.success(msg)
      form.reset()
    },
    onError: () => toast.error(form.errors.upfront ?? 'Payment failed. Please try again.'),
  })
}
</script>

<style scoped>
.upfront-summary {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px 14px;
  margin-bottom: 12px;
}
.upfront-row {
  display: flex; justify-content: space-between; align-items: baseline;
  gap: 10px; padding: 5px 0; border-bottom: 1px solid var(--border);
}
.upfront-row:last-child { border-bottom: none; }
.upfront-row--highlight { background: var(--badge-bg-gold); margin: 0 -14px; padding: 8px 14px; }
.upfront-row--sub { opacity: .7; }
.upfront-label { font-size: 12px; color: var(--text-3); font-weight: 600; flex-shrink: 0; }
.upfront-value { font-size: 13px; font-weight: 700; color: var(--text); text-align: right; }
.upfront-amount { font-size: 18px; color: var(--gold-dark); font-family: var(--font-serif, serif); }

.upfront-terms-note {
  display: flex; align-items: flex-start; gap: 6px;
  font-size: 12px; color: var(--text-3); background: var(--badge-bg-gold);
  border: 1px solid var(--gold); border-radius: var(--radius);
  padding: 8px 10px; margin-bottom: 12px; line-height: 1.5;
}

.upfront-agree {
  display: flex; align-items: flex-start; gap: 10px;
  font-size: 12px; color: var(--text-2); line-height: 1.55;
  cursor: pointer;
}
.upfront-check { margin-top: 2px; flex-shrink: 0; cursor: pointer; }
</style>
