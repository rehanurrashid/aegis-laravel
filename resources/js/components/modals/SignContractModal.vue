<!--
  SignContractModal.vue — Electronic signature modal for both parties.
  Used by Provider (via ContractModal) and BP (via BpContractDetailModal in Wave 4).

  Posts to:
    Provider: provider.jobs.contract.sign
    BP:       bp.contracts.sign

  Props:
    contract   Object | null  — contract row
    portal     'provider' | 'business_partner'
-->
<template>
  <AegisModal
    :model-value="!!(contract && !alreadySigned)"
    title="Sign contract"
    size="lg"
    @update:model-value="onClose"
  >
    <div v-if="contract" class="sign-modal">

      <!-- Contract terms viewer -->
      <div class="sign-modal-terms-wrap">
        <div class="sign-modal-terms-label">Contract terms</div>
        <div class="sign-modal-terms-body">
          <pre class="sign-modal-terms-text">{{ contract.terms ?? 'Terms will be provided by Aegis upon contract creation.' }}</pre>
        </div>
        <div class="sign-modal-terms-scroll-hint">Scroll to read all terms before signing</div>
      </div>

      <!-- Rev 2: Committed payment terms panel -->
      <div v-if="contract.payment_structure" class="sign-committed-terms">
        <div class="sign-committed-terms-header">
          <AegisIcon name="credit-card" :size="13" />
          <span>Committed payment terms</span>
          <AegisBadge :label="termsSummary" variant="gold" />
        </div>
        <div class="sign-committed-terms-grid">
          <div class="sign-ct-row">
            <span class="sign-ct-label">Structure</span>
            <span class="sign-ct-value">{{ termsSummary }}</span>
          </div>
          <div v-if="contract.upfront_cents > 0" class="sign-ct-row">
            <span class="sign-ct-label">Upfront charge at signing</span>
            <span class="sign-ct-value sign-ct-amount">{{ formatCents(contract.upfront_cents) }}</span>
          </div>
          <div v-if="contract.remaining_cents > 0" class="sign-ct-row">
            <span class="sign-ct-label">Remaining</span>
            <span class="sign-ct-value">{{ formatCents(contract.remaining_cents) }}</span>
          </div>
          <div class="sign-ct-row sign-ct-row-total">
            <span class="sign-ct-label">Contract total</span>
            <span class="sign-ct-value sign-ct-total">{{ formatCents(contract.total_value_cents) }}</span>
          </div>
        </div>
        <div v-if="contract.terms_note" class="sign-ct-note">{{ contract.terms_note }}</div>
        <div v-if="isSecondSigner && contract.upfront_cents > 0" class="sign-ct-auth-notice">
          <AegisIcon name="alert-triangle" :size="13" />
          By signing as the second party, you authorize an immediate charge of
          <strong>{{ formatCents(contract.upfront_cents) }}</strong> to your payment method.
          This payment routes directly to the other party's Stripe account — Aegis does not hold funds.
        </div>
      </div>

      <!-- Signature fields -->
      <div class="section-divider">Electronic signature</div>

      <div class="form-group">
        <label class="form-label" for="sig-name">
          Type your full legal name to sign <span class="req">*</span>
        </label>
        <input
          id="sig-name"
          v-model="form.name"
          type="text"
          class="form-input"
          :class="{ 'is-error': fieldError('name') }"
          :placeholder="signerName"
          maxlength="120"
          autocomplete="name"
          @blur="v$.name.$touch()"
        />
        <div v-if="fieldError('name')" class="form-error">{{ fieldError('name') }}</div>
        <div class="form-hint">Must match your registered name: {{ signerName }}</div>
      </div>

      <!-- Agreement checkbox -->
      <div class="form-group">
        <label class="sign-modal-agree-label">
          <input
            v-model="form.agreed"
            type="checkbox"
            class="sign-modal-checkbox"
            @change="v$.agreed.$touch()"
          />
          <span>
            I have read and agree to the contract terms above, the
            <a href="/terms" target="_blank" class="link-btn">Aegis Terms of Service</a>,
            and understand that by clicking <strong>Sign contract</strong> I am creating a
            legally binding electronic signature.
          </span>
        </label>
        <div v-if="fieldError('agreed')" class="form-error">{{ fieldError('agreed') }}</div>
      </div>

      <!-- Signature status of both parties -->
      <div class="sign-modal-status">
        <div class="sign-modal-status-item" :class="{ signed: contract.provider_has_signed }">
          <AegisIcon :name="contract.provider_has_signed ? 'check-circle' : 'circle'" :size="14" />
          <span>Provider: {{ contract.provider_has_signed ? 'Signed' : 'Awaiting signature' }}</span>
        </div>
        <div class="sign-modal-status-item" :class="{ signed: contract.bp_has_signed }">
          <AegisIcon :name="contract.bp_has_signed ? 'check-circle' : 'circle'" :size="14" />
          <span>Business Partner: {{ contract.bp_has_signed ? 'Signed' : 'Awaiting signature' }}</span>
        </div>
      </div>

    </div>

    <!-- Signed success state -->
    <div v-if="signed" class="sign-success">
      <AegisIcon name="check-circle" :size="24" />
      <div class="sign-success-title">You've signed the contract.</div>
      <div class="sign-success-desc">
        {{ bothSigned
          ? 'Both parties have signed. The contract is now fully executed and active. Payment has been initiated per committed terms.'
          : 'Awaiting the other party\u2019s signature.' }}
      </div>
    </div>

    <template #footer>
      <!-- After signing: show PDF link -->
      <a
        v-if="signed"
        :href="pdfUrl"
        target="_blank"
        rel="noopener"
        class="btn btn-outline"
      >
        <AegisIcon name="download" :size="13" />
        Download signed copy
      </a>
      <button v-if="signed" type="button" class="btn btn-ghost" @click="onClose">
        Close
      </button>

      <!-- Before signing -->
      <template v-if="!signed">
        <button type="button" class="btn btn-outline" :disabled="form.processing" @click="onClose">
          Cancel
        </button>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="form.processing || !canSign"
          @click="sign"
        >
          <span v-if="form.processing" class="spinner spinner-sm" />
          {{ form.processing ? 'Signing…' : 'Sign contract' }}
        </button>
      </template>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import useVuelidate from '@vuelidate/core'
import { required, minLength, sameAs } from '@vuelidate/validators'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  contract: { type: Object,  default: null },
  portal:   { type: String,  default: 'provider' },  // 'provider' | 'business_partner'
})

const emit = defineEmits(['update:modelValue'])

const page  = usePage()
const toast = useToast()

const form = useForm({
  name:   '',
  agreed: false,
})

// ── Computed ─────────────────────────────────────────────────────────────────
const signerName = computed(() => page.props.auth?.user?.display_name ?? '')

const alreadySigned = computed(() => {
  if (!props.contract) return false
  return props.portal === 'provider'
    ? props.contract.provider_has_signed
    : props.contract.bp_has_signed
})

// Rev 2 — payment terms helpers
const termsSummary = computed(() => {
  const s = props.contract?.payment_structure
  const pct = props.contract?.upfront_percentage ?? 0
  if (!s) return 'Legacy escrow contract'
  const labels = {
    full_upfront:  '100% upfront',
    split:         `${pct}% upfront + ${100 - pct}% completion`,
    per_milestone: 'Per milestone',
    on_completion: 'Pay on completion',
  }
  return labels[s] ?? s
})

// True if the current user would be the second signer (triggering payment)
const isSecondSigner = computed(() => {
  const c = props.contract
  if (!c) return false
  if (props.portal === 'provider') return !c.provider_has_signed && c.bp_has_signed
  return !c.bp_has_signed && c.provider_has_signed
})

function formatCents(cents) {
  if (!cents) return '$0.00'
  return '$' + Number(cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// ── Vuelidate ─────────────────────────────────────────────────────────────────
const rules = {
  name:   { required, minLength: minLength(2) },
  agreed: { required, mustBeTrue: (v) => v === true },
}
const v$ = useVuelidate(rules, form, { $scope: false })

function fieldError(f) {
  const e = v$.value[f]?.$errors
  return e?.length ? e[0].$message : null
}

const canSign = computed(() =>
  form.name.trim().length >= 2 &&
  form.agreed === true &&
  !form.processing,
)

// ── Actions ───────────────────────────────────────────────────────────────────
const signed     = ref(false)
const bothSigned = ref(false)

const pdfUrl = computed(() => {
  if (!props.contract) return '#'
  const base = window.location.origin
  return props.portal === 'provider'
    ? `${base}/provider/support-services/contracts/${props.contract.id}/pdf`
    : `${base}/business/contracts/${props.contract.id}/pdf`
})

function onClose() {
  emit('update:modelValue', null)
  setTimeout(() => { form.reset(); v$.value.$reset(); signed.value = false }, 200)
}

async function sign() {
  const valid = await v$.value.$validate()
  v$.value.$touch()
  if (!valid) return

  const routeName = props.portal === 'provider'
    ? 'provider.jobs.contract.sign'
    : 'bp.contracts.sign'

  form.post(route(routeName, { contract: props.contract.id }), {
    preserveScroll: true,
    onSuccess: (page) => {
      toast.success('Contract signed successfully.')
      signed.value = true
      // If both parties have now signed, the page will reload with fully_executed=true
      const contracts = page?.props?.activeContracts ?? page?.props?.contracts ?? []
      const updated = contracts.find?.((c) => c.id === props.contract.id)
      if (updated?.fully_executed) bothSigned.value = true
    },
    onError: (e) => toast.error(e.contract ?? 'Could not sign contract.'),
  })
}
</script>

<style scoped>
/* ── Committed terms panel (Rev 2) ─────────────────────────────── */
.sign-committed-terms {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  margin-bottom: 4px;
}
.sign-committed-terms-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: var(--surface-3);
  border-bottom: 1px solid var(--border);
  font-size: 12px;
  font-weight: 600;
  color: var(--text-3);
}
.sign-committed-terms-grid { padding: 2px 0; }
.sign-ct-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 12px;
  padding: 9px 14px;
  border-bottom: 1px solid var(--border);
}
.sign-ct-row:last-child { border-bottom: none; }
.sign-ct-row-total { background: var(--surface-3); }
.sign-ct-label  { font-size: 12px; color: var(--text-4); flex-shrink: 0; }
.sign-ct-value  { font-size: 13px; font-weight: 500; color: var(--text); text-align: right; }
.sign-ct-amount { color: var(--gold-dark); font-weight: 700; }
.sign-ct-total  { font-size: 15px; font-weight: 700; color: var(--text); }
.sign-ct-note {
  padding: 10px 14px;
  font-size: 12px;
  color: var(--text-3);
  border-top: 1px solid var(--border);
  font-style: italic;
}
.sign-ct-auth-notice {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  padding: 12px 14px;
  background: rgba(160,129,62,0.06);
  border-top: 1px solid var(--border);
  font-size: 12px;
  color: var(--gold-dark);
  line-height: 1.5;
}
.sign-ct-auth-notice strong { color: var(--text); }
</style>
