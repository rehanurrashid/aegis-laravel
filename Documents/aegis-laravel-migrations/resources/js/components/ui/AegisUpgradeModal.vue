<!--
  AegisUpgradeModal.vue — universal tier-gate.

  Opened by useUpgrade().openUpgradeModal() / requiresPractice() whenever
  an Access-tier user attempts a Practice-tier action. Two-step layout:
    Step 1: Plan review (current vs Practice)
    Step 2: Payment (card fields)

  Submits via xPost-equivalent: useForm() → POST /billing/upgrade →
  server flips tier, fans out activity, redirects with a flash toast.

  Visual fidelity mirrors _shared/modals/upgrade_cs_modal.php but is
  parameterized for the Access → Practice transition, not the CS upgrade.
  CS-specific upgrades live in components/modals/UpgradeCSModal.vue.
-->
<template>
  <AegisModal
    :model-value="isOpen('upgradeModal').value"
    :title="step === 1 ? 'Upgrade to Continuity Practice' : 'Complete Your Upgrade'"
    size="md"
    @update:model-value="onUpdateOpen"
  >
    <!-- Step indicator -->
    <div class="upgrade-steps">
      <div class="upgrade-step" :class="{ 'is-active': step === 1, 'is-done': step > 1 }">
        <div class="upgrade-step-num">1</div>
        <div class="upgrade-step-lbl">Plan</div>
      </div>
      <div class="upgrade-step-line"></div>
      <div class="upgrade-step" :class="{ 'is-active': step === 2 }">
        <div class="upgrade-step-num">2</div>
        <div class="upgrade-step-lbl">Payment</div>
      </div>
    </div>

    <!-- ── STEP 1: Plan review ──────────────────────────────────────── -->
    <div v-if="step === 1" class="upgrade-pane">

      <!-- Current plan -->
      <div class="upgrade-current-plan">
        <div class="upgrade-section-eyebrow">Current Plan</div>
        <div class="upgrade-current-plan-row">
          <div>
            <div class="upgrade-current-plan-name">{{ access.name }}</div>
            <div class="upgrade-current-plan-sub">
              {{ access.maxCs }} Continuity Steward · {{ access.maxSs }} Support Stewards
            </div>
          </div>
          <span class="badge badge--neutral">
            {{ pricing.formatCents(access.monthly) }}/mo
          </span>
        </div>
      </div>

      <!-- Practice plan card -->
      <div class="upgrade-plan-card">
        <div class="upgrade-plan-recommended">RECOMMENDED</div>
        <div class="upgrade-section-eyebrow">Aegis Plan</div>
        <div class="upgrade-plan-header">
          <div>
            <div class="upgrade-plan-name">{{ practice.name }}</div>
            <div class="upgrade-plan-tag">{{ practice.tagline }}</div>
          </div>
          <div class="upgrade-plan-price-wrap">
            <div class="upgrade-plan-price">
              {{ pricing.formatCents(practice.monthly) }}<span class="upgrade-plan-price-sub">/mo</span>
            </div>
            <div class="upgrade-plan-price-alt">
              or {{ pricing.formatCents(practice.annual) }}/mo billed annually
            </div>
          </div>
        </div>
        <div v-for="feat in practice.includes" :key="feat" class="upgrade-feat">
          <AegisIcon name="check" :size="13" />
          <span>{{ feat }}</span>
        </div>
      </div>

      <div class="upgrade-fineprint">
        Your existing data and stewards are preserved. Cancel any time.
      </div>
    </div>

    <!-- ── STEP 2: Payment ──────────────────────────────────────────── -->
    <div v-else class="upgrade-pane">
      <!-- Order summary -->
      <div class="upgrade-order-summary">
        <div>
          <div class="upgrade-order-name">{{ practice.name }}</div>
          <div class="upgrade-order-sub">
            Billed {{ form.billing_cycle === 'annual' ? 'annually' : 'monthly' }} · Cancel any time
          </div>
        </div>
        <div class="upgrade-order-price">
          {{ pricing.formatCents(form.billing_cycle === 'annual' ? practice.annual * 12 : practice.monthly) }}
          <span class="upgrade-order-price-sub">/{{ form.billing_cycle === 'annual' ? 'yr' : 'mo' }}</span>
        </div>
      </div>

      <!-- Billing cycle toggle -->
      <div class="form-group">
        <label class="form-label">Billing cycle</label>
        <div class="seg-toggle">
          <button
            type="button"
            class="seg-toggle-btn"
            :class="{ 'is-active': form.billing_cycle === 'monthly' }"
            @click="form.billing_cycle = 'monthly'"
          >Monthly</button>
          <button
            type="button"
            class="seg-toggle-btn"
            :class="{ 'is-active': form.billing_cycle === 'annual' }"
            @click="form.billing_cycle = 'annual'"
          >Annual <span class="seg-toggle-save">save 20%</span></button>
        </div>
      </div>

      <!-- Card -->
      <div class="form-group">
        <label class="form-label" for="upg-card">Card number</label>
        <input
          id="upg-card"
          v-model="form.card_number"
          type="text"
          inputmode="numeric"
          autocomplete="cc-number"
          class="form-input"
          placeholder="1234 5678 9012 3456"
        />
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="upg-exp">Expiration</label>
          <input
            id="upg-exp"
            v-model="form.card_exp"
            type="text"
            autocomplete="cc-exp"
            class="form-input"
            placeholder="MM / YY"
          />
        </div>
        <div class="form-group">
          <label class="form-label" for="upg-cvc">CVC</label>
          <input
            id="upg-cvc"
            v-model="form.card_cvc"
            type="text"
            inputmode="numeric"
            autocomplete="cc-csc"
            class="form-input"
            placeholder="123"
          />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="upg-name">Name on card</label>
        <input
          id="upg-name"
          v-model="form.card_name"
          type="text"
          autocomplete="cc-name"
          class="form-input"
          placeholder="Full name"
        />
      </div>

      <div v-if="form.errors.payment" class="form-error">{{ form.errors.payment }}</div>
    </div>

    <template #footer>
      <button
        v-if="step === 1"
        type="button"
        class="btn btn-outline"
        @click="onClose"
      >Not now</button>
      <button
        v-else
        type="button"
        class="btn btn-outline"
        :disabled="form.processing"
        @click="step = 1"
      >Back</button>

      <button
        v-if="step === 1"
        type="button"
        class="btn btn-primary"
        @click="step = 2"
      >
        <span>Continue</span>
        <AegisIcon name="arrow-right" :size="14" />
      </button>
      <button
        v-else
        type="button"
        class="btn btn-primary"
        :disabled="form.processing || !canSubmit"
        @click="submit"
      >
        {{ form.processing ? 'Processing…' : `Pay ${pricing.formatCents(amountDue)} & upgrade` }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'

const { isOpen, closeModal } = useModal()
const toast   = useToast()
const pricing = usePricingStore()

const access   = pricing.getTier('access')
const practice = pricing.getTier('practice')

const step = ref(1)

const form = useForm({
  plan: 'practice',
  billing_cycle: 'monthly',
  card_number: '',
  card_exp: '',
  card_cvc: '',
  card_name: '',
})

const canSubmit = computed(() =>
  form.card_number.trim().length >= 12 &&
  form.card_exp.trim().length >= 4 &&
  form.card_cvc.trim().length >= 3 &&
  form.card_name.trim().length >= 2,
)

const amountDue = computed(() =>
  form.billing_cycle === 'annual' ? practice.annual * 12 : practice.monthly,
)

function onUpdateOpen(value) {
  if (!value) onClose()
}

function onClose() {
  closeModal('upgradeModal')
  // Reset to step 1 next time it opens
  setTimeout(() => { step.value = 1 }, 200)
}

function submit() {
  form.post(route('billing.upgrade'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Welcome to Continuity Practice.')
      form.reset('card_number', 'card_exp', 'card_cvc', 'card_name')
      onClose()
    },
    onError: () => {
      toast.error('Payment could not be completed. Check the card details.')
    },
  })
}
</script>
