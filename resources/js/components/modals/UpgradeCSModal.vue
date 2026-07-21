<!--
  UpgradeCSModal.vue — CS-specific Business CS Plan upgrade.

  Replaces the CS-specific flow in _shared/modals/upgrade_cs_modal.php.
  This is DIFFERENT from AegisUpgradeModal (which handles Provider's
  Access → Practice tier change). This modal moves a free invited CS
  account to the paid MAAT Professional Continuity Steward plan,
  unlocking invite-others, multi-practitioner stewardship, and full
  CS Portal features.

  Triggered by useModal().openModal('upgradeCSModal') from any CS-portal
  page that gates a paid feature.
-->
<template>
  <AegisModal
    :model-value="isOpen('upgradeCSModal').value"
    :title="step === 1 ? 'Upgrade Your Account' : 'Complete Your Upgrade'"
    size="md"
    @update:model-value="onUpdateOpen"
  >
    <div class="modal-subtitle">Unlock full stewardship capabilities</div>

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

    <!-- ── STEP 1 — Plan review ─────────────────────────────────── -->
    <div v-if="step === 1">
      <!-- Current plan -->
      <div class="upgrade-current-plan">
        <div class="upgrade-section-eyebrow">Current Plan</div>
        <div class="upgrade-current-plan-row">
          <div>
            <div class="upgrade-current-plan-name">Free Invited Account</div>
            <div class="upgrade-current-plan-sub">Locked to 1 practitioner</div>
          </div>
          <AegisBadge label="$0/mo" variant="green" />
        </div>
      </div>

      <!-- Business CS plan -->
      <div class="upgrade-plan-card">
        <div class="upgrade-plan-recommended">RECOMMENDED</div>
        <div class="upgrade-section-eyebrow">Business CS Plan</div>
        <div class="upgrade-plan-header">
          <div>
            <div class="upgrade-plan-name">MAAT Professional Continuity Steward</div>
            <div class="upgrade-plan-tag">Serve 2–40 practitioners · Send invitations</div>
          </div>
          <div class="upgrade-plan-price-wrap">
            <div class="upgrade-plan-price">$49<span class="upgrade-plan-price-sub">/mo</span></div>
            <div class="upgrade-plan-price-alt">or $429/yr (save 27%)</div>
          </div>
        </div>

        <div class="upgrade-feat"><AegisIcon name="check" :size="13" /> Invite unlimited practitioners via secure link</div>
        <div class="upgrade-feat"><AegisIcon name="check" :size="13" /> Serve 2–40 practitioners from one account</div>
        <div class="upgrade-feat"><AegisIcon name="check" :size="13" /> Full CS Portal access — all features</div>
        <div class="upgrade-feat"><AegisIcon name="check" :size="13" /> Priority onboarding and support</div>
      </div>

      <div class="upgrade-fineprint">
        Your existing connection and data are preserved. Cancel any time.
      </div>
    </div>

    <!-- ── STEP 2 — Payment ──────────────────────────────────────── -->
    <div v-else>
      <!-- Order summary -->
      <div class="upgrade-order-summary">
        <div>
          <div class="upgrade-order-name">Business CS Plan</div>
          <div class="upgrade-order-sub">Billed monthly · Cancel any time</div>
        </div>
        <div class="upgrade-order-price">
          $49<span class="upgrade-order-price-sub">/mo</span>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="cs-card">Card number</label>
        <input
          id="cs-card"
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
          <label class="form-label" for="cs-exp">Expiration</label>
          <input
            id="cs-exp"
            v-model="form.card_exp"
            type="text"
            autocomplete="cc-exp"
            class="form-input"
            placeholder="MM / YY"
          />
        </div>
        <div class="form-group">
          <label class="form-label" for="cs-cvc">CVC</label>
          <input
            id="cs-cvc"
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
        <label class="form-label" for="cs-name">Name on card</label>
        <input
          id="cs-name"
          v-model="form.card_name"
          type="text"
          autocomplete="cc-name"
          class="form-input"
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
      >Continue</button>
      <button
        v-else
        type="button"
        class="btn btn-primary"
        :disabled="form.processing || !canSubmit"
        @click="submit"
      >
          <span v-if="form.processing" class="spinner spinner-sm" />
          {{ form.processing ? 'Processing…' : 'Pay $49 & upgrade' }}
        </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import AegisBadge from '@/components/ui/AegisBadge.vue'

const { isOpen, closeModal } = useModal()
const toast = useToast()

const step = ref(1)

const form = useForm({
  plan: 'business_cs',
  card_number: '',
  card_exp:    '',
  card_cvc:    '',
  card_name:   '',
})

const canSubmit = computed(() =>
  form.card_number.trim().length >= 12 &&
  form.card_exp.trim().length >= 4 &&
  form.card_cvc.trim().length >= 3 &&
  form.card_name.trim().length >= 2,
)

function onUpdateOpen(v) { if (!v) onClose() }

function onClose() {
  closeModal('upgradeCSModal')
  setTimeout(() => { step.value = 1 }, 200)
}

function submit() {
  form.post(route('cs.upgrade'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Welcome to the Business CS Plan.')
      form.reset('card_number', 'card_exp', 'card_cvc', 'card_name')
      onClose()
    },
    onError: () => toast.error('Payment could not be completed.'),
  })
}
</script>
