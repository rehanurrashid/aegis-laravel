<!--
  AddCardModal.vue — reusable native "Add card" modal for all three portals.

  Props:
    modelValue          v-model open/closed
    setupIntentRoute    Ziggy route name for POST that returns client_secret
                        (e.g. 'provider.settings.payment.setup-intent',
                         'cs.settings.payment.setup-intent',
                         'bp.settings.payment.setup-intent')
    storeRoute          Ziggy route name for POST that persists the PaymentMethod id
                        (e.g. 'provider.settings.payment.store')

  Flow:
    1. Modal opens → POST setupIntentRoute → receives client_secret + stripe_key
    2. Load Stripe.js from CDN (once); mount Elements with clientSecret
    3. Split card fields (cardNumber, cardExpiry, cardCvc) rendered inline
    4. On submit → stripe.confirmSetup() → returns PaymentMethod id
    5. POST storeRoute with { payment_method_id, set_default: true }
    6. Server persists (mirrors to stripe_payment_method_id — from P0)
    7. Close modal, refresh via Inertia reload
-->
<template>
  <AegisModal :model-value="modelValue" title="Add a card" size="md" @update:model-value="(v) => $emit('update:modelValue', v)">
    <div class="add-card-body">
      <p class="add-card-lede">
        Your card is captured directly by Stripe. Aegis never sees the raw number.
      </p>

      <div v-if="loading" class="add-card-loading">
        <div class="spinner-dot"></div>
        <span>Initializing secure card entry…</span>
      </div>

      <div v-else-if="loadError" class="add-card-error">
        <AegisIcon name="alert-triangle" :size="16" />
        <span>{{ loadError }}</span>
      </div>

      <div v-else class="add-card-form">
        <div class="form-group">
          <label class="form-label">Card number</label>
          <div id="acm-card-number" class="stripe-field"></div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Expiry</label>
            <div id="acm-card-expiry" class="stripe-field"></div>
          </div>
          <div class="form-group">
            <label class="form-label">CVC</label>
            <div id="acm-card-cvc" class="stripe-field"></div>
          </div>
        </div>
        <div v-if="cardError" class="add-card-error">
          <AegisIcon name="alert-triangle" :size="14" />
          <span>{{ cardError }}</span>
        </div>
      </div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline btn-sm" :disabled="submitting" @click="close">Cancel</button>
      <button type="button" class="btn btn-primary btn-sm" :disabled="loading || !!loadError || submitting" @click="submit">
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

const loading    = ref(false)
const submitting = ref(false)
const loadError  = ref(null)
const cardError  = ref(null)

let stripe     = null
let elements   = null
let cardNumber = null
let cardExpiry = null
let cardCvc    = null
let clientSecret = null

// Reset when modal opens
watch(() => props.modelValue, async (isOpen) => {
  if (!isOpen) return
  loading.value = true
  loadError.value = null
  cardError.value = null

  try {
    // 1. Request SetupIntent from server
    const resp = await window.axios.post(route(props.setupIntentRoute))
    clientSecret = resp.data?.client_secret
    const stripeKey = resp.data?.stripe_key
    if (!clientSecret || !stripeKey) {
      throw new Error('Missing client secret or Stripe key from server.')
    }

    // 2. Ensure Stripe.js is loaded
    await ensureStripeJs()
    stripe = window.Stripe(stripeKey)

    // 3. Build Elements instance (no DOM access yet)
    elements = stripe.elements({
      clientSecret,
      appearance: { theme: 'stripe' },
    })

    cardNumber = elements.create('cardNumber', { showIcon: true })
    cardExpiry = elements.create('cardExpiry')
    cardCvc    = elements.create('cardCvc')

    // 4. Reveal the form (clears the spinner), then wait for Vue to paint the
    //    mount-target divs before calling .mount() on each element.
    loading.value = false
    await nextTick()

    cardNumber.mount('#acm-card-number')
    cardExpiry.mount('#acm-card-expiry')
    cardCvc.mount('#acm-card-cvc')

    cardNumber.on('change', (e) => { cardError.value = e.error?.message ?? null })
  } catch (e) {
    loadError.value = e.message || 'Could not initialize card entry.'
  } finally {
    // Guard: only clear loading if an error path left it true
    loading.value = false
  }
})

function ensureStripeJs() {
  return new Promise((resolve, reject) => {
    if (window.Stripe) return resolve()
    const existing = document.querySelector('script[src="https://js.stripe.com/v3/"]')
    if (existing) {
      existing.addEventListener('load', () => resolve())
      existing.addEventListener('error', () => reject(new Error('Stripe.js failed to load.')))
      return
    }
    const s = document.createElement('script')
    s.src = 'https://js.stripe.com/v3/'
    s.async = true
    s.onload  = () => resolve()
    s.onerror = () => reject(new Error('Stripe.js failed to load. Check network / ad-blocker.'))
    document.head.appendChild(s)
  })
}

async function submit() {
  if (!stripe || !cardNumber || submitting.value) return
  submitting.value = true
  cardError.value = null

  try {
    const { setupIntent, error } = await stripe.confirmCardSetup(clientSecret, {
      payment_method: { card: cardNumber },
    })
    if (error) {
      cardError.value = error.message
      submitting.value = false
      return
    }
    if (!setupIntent?.payment_method) {
      cardError.value = 'Setup completed but no payment method was returned.'
      submitting.value = false
      return
    }

    // Persist server-side. storeRoute already mirrors to stripe_payment_method_id (P0).
    router.post(route(props.storeRoute), {
      payment_method_id: setupIntent.payment_method,
      set_default:       true,
    }, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Card saved.')
        emit('saved')
        emit('update:modelValue', false)
      },
      onError: (errs) => {
        cardError.value = errs?.payment ?? errs?.payment_method_id ?? 'Server rejected the card. Please try again.'
        submitting.value = false
      },
      onFinish: () => { submitting.value = false },
    })
  } catch (e) {
    cardError.value = e.message
    submitting.value = false
  }
}

function close() {
  emit('update:modelValue', false)
  cardNumber?.destroy()
  cardExpiry?.destroy()
  cardCvc?.destroy()
  cardNumber = cardExpiry = cardCvc = null
  elements = null
  clientSecret = null
}
</script>

<style scoped>
.add-card-body      { display: flex; flex-direction: column; gap: 14px; }
.add-card-lede      { font-size: 13px; color: var(--text-3); margin: 0; }
.add-card-loading   { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--text-3); padding: 20px 0; }
.add-card-error     { display: flex; align-items: center; gap: 8px; padding: 10px 12px; background: rgba(220,38,38,.06); color: var(--red); border-radius: var(--radius-sm); font-size: 13px; }
.add-card-form      { display: flex; flex-direction: column; }
.form-group         { display: flex; flex-direction: column; gap: 6px; }
.form-label         { font-size: 11px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--text-2); }
.form-row           { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.stripe-field       { padding: 12px 13px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); background: var(--surface); min-height: 42px; }
.stripe-field:focus-within { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(196,169,106,.18); }
.spinner-dot        { width: 12px; height: 12px; border-radius: 50%; background: var(--gold-dark); animation: pulse 1.2s ease-in-out infinite; }
@keyframes pulse    { 0%, 100% { opacity: .3 } 50% { opacity: 1 } }
</style>
