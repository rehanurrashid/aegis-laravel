<!--
  AddCardModal.vue — reusable "Add card" modal for all three portals.
  Design mirrors OnboardingPayment.vue exactly (split fields, per-field errors,
  focus ring, no Stripe Link/Autofill button via disableLink: true).

  Props:
    modelValue          v-model open/closed
    setupIntentRoute    Ziggy route name that returns { client_secret, stripe_key }
    storeRoute          Ziggy route name that persists the PaymentMethod id

  Flow:
    1. Modal opens → POST setupIntentRoute → client_secret + stripe_key
    2. Ensure Stripe.js loaded; build Elements
    3. loading = false → nextTick → mount split fields into DOM
    4. submit → completeness check → stripe.confirmCardSetup → POST storeRoute
-->
<template>
  <AegisModal :model-value="modelValue" title="Add a card" size="md" @update:model-value="(v) => $emit('update:modelValue', v)">
    <div class="acm-body">
      <p class="acm-lede">
        Your card is captured directly by Stripe. Aegis never sees the raw number.
      </p>

      <!-- Loading -->
      <div v-if="loading" class="acm-loading">
        <div class="acm-spinner" />
        <span>Initializing secure card entry…</span>
      </div>

      <!-- Load error -->
      <div v-else-if="loadError" class="acm-error-banner">
        <AegisIcon name="alert-circle" :size="14" />
        {{ loadError }}
      </div>

      <!-- Card fields — same structure as OnboardingPayment -->
      <div v-else class="acm-card-fields">

        <!-- Card Number -->
        <div class="form-group acm-card-group">
          <label class="form-label acm-card-label">Card Number</label>
          <div
            id="acm-card-number"
            class="acm-card-input"
            :class="{ 'is-focused': focusedField === 'number', 'is-error': cardNumberError }"
          />
          <div v-if="cardNumberError" class="form-error acm-field-error">{{ cardNumberError }}</div>
        </div>

        <!-- Expiry + CVC row -->
        <div class="acm-card-row">
          <div class="form-group acm-card-group">
            <label class="form-label acm-card-label">Expiry Date</label>
            <div
              id="acm-card-expiry"
              class="acm-card-input"
              :class="{ 'is-focused': focusedField === 'expiry', 'is-error': cardExpiryError }"
            />
            <div v-if="cardExpiryError" class="form-error acm-field-error">{{ cardExpiryError }}</div>
          </div>
          <div class="form-group acm-card-group">
            <label class="form-label acm-card-label">CVC</label>
            <div
              id="acm-card-cvc"
              class="acm-card-input"
              :class="{ 'is-focused': focusedField === 'cvc', 'is-error': cardCvcError }"
            />
            <div v-if="cardCvcError" class="form-error acm-field-error">{{ cardCvcError }}</div>
          </div>
        </div>

        <!-- General error (incomplete on submit / server reject) -->
        <div v-if="cardError" class="acm-error-banner">
          <AegisIcon name="alert-circle" :size="14" />
          {{ cardError }}
        </div>

      </div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="submitting" @click="close">Cancel</button>
      <button type="button" class="btn btn-primary" :disabled="loading || !!loadError || submitting" @click="submit">
        <AegisIcon v-if="!submitting" name="lock" :size="12" />
        {{ submitting ? 'Saving…' : 'Save card' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import AegisModal from '@/components/ui/AegisModal.vue'
import AegisIcon  from '@/components/ui/AegisIcon.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue:       { type: Boolean, default: false },
  setupIntentRoute: { type: String,  required: true },
  storeRoute:       { type: String,  required: true },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const toast = useToast()

const loading      = ref(false)
const submitting   = ref(false)
const loadError    = ref(null)
const cardError    = ref(null)

// Per-field state — mirrors OnboardingPayment pattern
const focusedField   = ref('')          // 'number' | 'expiry' | 'cvc' | ''
const cardNumberError = ref('')
const cardExpiryError = ref('')
const cardCvcError    = ref('')

// Completion tracking (to guard submit)
let numberComplete = false
let expiryComplete = false
let cvcComplete    = false

let stripe       = null
let elements     = null
let cardNumber   = null
let cardExpiry   = null
let cardCvc      = null
let clientSecret = null

// ── Open / reset ──────────────────────────────────────────────────────
watch(() => props.modelValue, async (isOpen) => {
  if (!isOpen) return
  resetFieldState()
  loading.value  = true
  loadError.value = null
  cardError.value = null

  try {
    // 1. Fetch SetupIntent
    const resp = await window.axios.post(route(props.setupIntentRoute))
    clientSecret    = resp.data?.client_secret
    const stripeKey = resp.data?.stripe_key
    if (!clientSecret || !stripeKey) {
      throw new Error('Missing client secret or Stripe key from server.')
    }

    // 2. Ensure Stripe.js
    await ensureStripeJs()
    stripe = window.Stripe(stripeKey)

    // 3. Create elements (no DOM yet)
    elements = stripe.elements({ fonts: [] })

    cardNumber = elements.create('cardNumber', { showIcon: true, disableLink: true })
    cardExpiry = elements.create('cardExpiry')
    cardCvc    = elements.create('cardCvc')

    // 4. Show form, wait for paint, then mount
    loading.value = false
    await nextTick()

    cardNumber.mount('#acm-card-number')
    cardExpiry.mount('#acm-card-expiry')
    cardCvc.mount('#acm-card-cvc')

    // 5. Focus / blur
    cardNumber.on('focus', () => { focusedField.value = 'number' })
    cardNumber.on('blur',  () => { focusedField.value = '' })
    cardExpiry.on('focus', () => { focusedField.value = 'expiry' })
    cardExpiry.on('blur',  () => { focusedField.value = '' })
    cardCvc.on('focus',    () => { focusedField.value = 'cvc' })
    cardCvc.on('blur',     () => { focusedField.value = '' })

    // 6. Per-field change — inline errors, clear general error on fix
    cardNumber.on('change', (e) => {
      numberComplete        = e.complete ?? false
      cardNumberError.value = e.error?.message ?? ''
      if (!e.error) cardError.value = null
    })
    cardExpiry.on('change', (e) => {
      expiryComplete        = e.complete ?? false
      cardExpiryError.value = e.error?.message ?? ''
      if (!e.error) cardError.value = null
    })
    cardCvc.on('change', (e) => {
      cvcComplete        = e.complete ?? false
      cardCvcError.value = e.error?.message ?? ''
      if (!e.error) cardError.value = null
    })

  } catch (e) {
    loadError.value = e.message || 'Could not initialize card entry.'
    loading.value   = false
  }
})

// ── Stripe.js loader ──────────────────────────────────────────────────
function ensureStripeJs() {
  return new Promise((resolve, reject) => {
    if (window.Stripe) return resolve()
    const existing = document.querySelector('script[src="https://js.stripe.com/v3/"]')
    if (existing) {
      existing.addEventListener('load', resolve)
      existing.addEventListener('error', () => reject(new Error('Stripe.js failed to load.')))
      return
    }
    const s = document.createElement('script')
    s.src    = 'https://js.stripe.com/v3/'
    s.async  = true
    s.onload  = resolve
    s.onerror = () => reject(new Error('Stripe.js failed to load. Check network / ad-blocker.'))
    document.head.appendChild(s)
  })
}

// ── Submit ────────────────────────────────────────────────────────────
async function submit() {
  if (!stripe || !cardNumber || submitting.value) return

  // Completeness guard — shows the right per-field message
  if (!numberComplete) { cardNumberError.value = 'Your card number is incomplete.'; return }
  if (!expiryComplete) { cardExpiryError.value = 'Your card\'s expiration date is incomplete.'; return }
  if (!cvcComplete)    { cardCvcError.value    = 'Your card\'s security code is incomplete.'; return }

  submitting.value = true
  cardError.value  = null

  try {
    const { setupIntent, error } = await stripe.confirmCardSetup(clientSecret, {
      payment_method: { card: cardNumber },
    })

    if (error) {
      cardError.value  = error.message
      submitting.value = false
      return
    }
    if (!setupIntent?.payment_method) {
      cardError.value  = 'Setup completed but no payment method was returned.'
      submitting.value = false
      return
    }

    router.post(route(props.storeRoute), {
      payment_method_id: setupIntent.payment_method,
      set_default:       true,
    }, {
      preserveScroll: true,
      onSuccess: () => {
        // Server flash ('Payment method saved.') already triggers a toast via app.js — no second toast here.
        emit('saved')
        emit('update:modelValue', false)
      },
      onError: (errs) => {
        cardError.value  = errs?.payment ?? errs?.payment_method_id ?? 'Server rejected the card. Please try again.'
        submitting.value = false
      },
      onFinish: () => { submitting.value = false },
    })
  } catch (e) {
    cardError.value  = e.message
    submitting.value = false
  }
}

// ── Close / cleanup ───────────────────────────────────────────────────
function close() {
  emit('update:modelValue', false)
  destroyElements()
}

function destroyElements() {
  cardNumber?.destroy()
  cardExpiry?.destroy()
  cardCvc?.destroy()
  cardNumber = cardExpiry = cardCvc = null
  elements   = null
  clientSecret = null
  resetFieldState()
}

function resetFieldState() {
  focusedField.value    = ''
  cardNumberError.value = ''
  cardExpiryError.value = ''
  cardCvcError.value    = ''
  numberComplete = expiryComplete = cvcComplete = false
}
</script>

<style scoped>
.acm-body          { display: flex; flex-direction: column; gap: 14px; }
.acm-lede          { font-size: 13px; color: var(--text-3); margin: 0; }

/* Loading */
.acm-loading       { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--text-2); padding: 24px 0; }
.acm-spinner       { width: 20px; height: 20px; border: 1px solid var(--border); border-top-color: var(--gold); border-radius: var(--radius-full); animation: acm-spin 0.8s linear infinite; flex-shrink: 0; }
@keyframes acm-spin { to { transform: rotate(360deg); } }

/* Error banner */
.acm-error-banner  { display: flex; align-items: flex-start; gap: 8px; background: var(--red-light); border: 1px solid rgba(224,92,92,0.25); border-radius: var(--radius-sm); padding: 10px 14px; font-size: 13px; color: var(--red); line-height: 1.5; }

/* Card fields — matches ob-card-fields from OnboardingPayment */
.acm-card-fields   { display: flex; flex-direction: column; gap: 16px; }
.acm-card-row      { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.acm-card-group    { margin-bottom: 0; }
.acm-card-label    { font-size: 12px; font-weight: 600; color: var(--text-2); margin-bottom: 6px; display: block; }
.acm-card-input    { padding: 10px 12px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); transition: border-color 0.15s; min-height: 40px; }
.acm-card-input.is-focused { border-color: var(--gold-dark); }
.acm-card-input.is-error   { border-color: var(--red); }
.acm-field-error   { font-size: 11px; color: var(--red); margin-top: 4px; line-height: 1.4; }
</style>
