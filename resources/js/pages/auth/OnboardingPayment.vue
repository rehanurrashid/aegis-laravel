<!--
  pages/auth/OnboardingPayment.vue — payment step using Stripe Payment Element.

  Collects card details via Stripe's hosted Payment Element (no raw card data
  touches Aegis servers). On submit:
    1. stripe.confirmSetup() → creates PaymentMethod (pm_xxx)
    2. POST /onboarding/subscribe → attaches pm to user, creates subscription

  Flow: OnboardingPlan → [THIS PAGE] → Portal Dashboard
  Route: GET /onboarding/payment  (onboarding.payment)

  Requires: @stripe/stripe-js loaded via CDN (added to app.blade.php).
  Stripe key injected server-side via Inertia props.
-->
<template>
  <Head title="Payment — Aegis" />

  <div class="ob-layout">

    <!-- ══ LEFT PANEL ══ -->
    <div class="ob-panel-left">
      <img src="/aegis-bg.svg" class="ob-panel-left-bg" alt="" aria-hidden="true" />
      <div class="ob-brand-logo">
        <span class="ob-brand-logo-text">Aegis</span>
      </div>
      <div class="ob-panel-left-content">
        <div class="ob-panel-left-eyebrow">Final Step</div>
        <h1 class="ob-panel-left-title">Secure your subscription. Your practice is waiting.</h1>
        <p class="ob-panel-left-body">
          Your card is processed by Stripe — PCI Level 1 compliant. Aegis never stores raw card data.
        </p>
        <div class="ob-plan-summary-panel" v-if="plan">
          <div class="ob-psp-label">Your plan</div>
          <div class="ob-psp-name">{{ planDisplayName }}</div>
          <div class="ob-psp-price">{{ planDisplayPrice }}</div>
          <button type="button" class="ob-psp-change" @click="goBack">Change plan</button>
        </div>
        <!-- Progress pips: step 4 of 4 -->
        <div class="ob-progress-track">
          <div v-for="i in 4" :key="i" class="ob-progress-pip" :class="{ active: i === 4, done: i < 4 }" />
        </div>
      </div>
      <div class="ob-panel-left-footer">
        <p>© {{ year }} Aegis Platform. All rights reserved.</p>
      </div>
    </div>

    <!-- ══ RIGHT PANEL ══ -->
    <div class="ob-panel-right">
      <div class="ob-panel-right-inner">

        <button type="button" class="ob-back-link" @click="goBack">
          <AegisIcon name="chevron-left" :size="14" />
          Back to Plan Selection
        </button>

        <div class="ob-step-header">
          <div class="ob-step-eyebrow">Step 4 of 4 — Payment</div>
          <h2 class="ob-step-title">Payment Information</h2>
          <p class="ob-step-subtitle">Complete your subscription to unlock full platform access.</p>
        </div>

        <!-- Plan recap -->
        <div class="ob-plan-recap">
          <div>
            <div class="ob-plan-recap-name">{{ planDisplayName }}</div>
            <div class="ob-plan-recap-detail">{{ planBillingDetail }}</div>
          </div>
          <div class="ob-plan-recap-price">{{ planDisplayPrice }}</div>
        </div>

        <!-- Error banner -->
        <div v-if="errorMessage" class="ob-error-banner">
          <AegisIcon name="alert-circle" :size="14" />
          {{ errorMessage }}
        </div>

        <!-- Stripe Payment Element mount point -->
        <div class="ob-payment-element-wrap">
          <div id="payment-element" class="ob-payment-element">
            <!-- Stripe.js mounts here after init -->
            <div v-if="!stripeReady" class="ob-stripe-loading">
              <div class="ob-stripe-spinner" />
              Loading secure payment form…
            </div>
          </div>
        </div>

        <button
          type="button"
          class="btn btn-primary ob-btn-full"
          :disabled="!stripeReady || submitting"
          @click="submit"
        >
          <AegisIcon v-if="submitting" name="loader" :size="14" />
          <AegisIcon v-else name="lock" :size="14" />
          {{ submitting ? 'Processing…' : `Subscribe — ${planDisplayPrice}` }}
        </button>

        <div class="ob-secure-note">
          <AegisIcon name="shield-check" :size="11" />
          256-bit SSL encryption · PCI DSS compliant · Cancel anytime
        </div>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  role:         { type: String,  required: true },
  plan:         { type: Object,  required: true },  // { tier, billing, addons }
  clientSecret: { type: String,  required: true },  // SetupIntent client_secret
  stripeKey:    { type: String,  required: true },  // pk_test_xxx / pk_live_xxx
  pricing:      { type: Object,  required: true },
})

const toast       = useToast()
const year        = new Date().getFullYear()
const stripeReady = ref(false)
const submitting  = ref(false)
const errorMessage = ref('')

let stripe  = null
let elements = null
let paymentElement = null

// ── Plan display helpers ─────────────────────────────────────────────
const tierLabels = {
  access:   'Continuity Access',
  practice: 'Continuity Practice',
  monthly:  'Business Partner — Monthly',
  annual:   'Business Partner — Annual',
}

const planDisplayName = computed(() => tierLabels[props.plan?.tier] ?? 'Aegis Subscription')

const planDisplayPrice = computed(() => {
  const t = props.plan?.tier
  const b = props.plan?.billing
  if (t === 'access')   return b === 'annual' ? '$23/mo (billed annually)' : '$29/mo'
  if (t === 'practice') return b === 'annual' ? '$39/mo (billed annually)' : '$49/mo'
  if (t === 'monthly')  return '$69/mo'
  if (t === 'annual')   return '$690/yr'
  return '—'
})

const planBillingDetail = computed(() => {
  const b = props.plan?.billing
  if (b === 'annual') return 'Billed annually · save 20%'
  return 'Billed monthly · cancel anytime'
})

// ── Stripe price ID resolution ────────────────────────────────────────
// Resolves to the Stripe price_id from the Ziggy route env vars exposed
// through the window.__AEGIS_CONFIG__ object (set in app.blade.php).
// Falls back to env-named placeholder so validation fails gracefully if
// Stripe products haven't been created yet.
function resolveStripePrice() {
  const cfg    = window.__AEGIS_CONFIG__ ?? {}
  const t      = props.plan?.tier
  const b      = props.plan?.billing

  const map = {
    'access-monthly':   cfg.STRIPE_PRICE_ACCESS_MONTHLY,
    'access-annual':    cfg.STRIPE_PRICE_ACCESS_ANNUAL,
    'practice-monthly': cfg.STRIPE_PRICE_PRACTICE_MONTHLY,
    'practice-annual':  cfg.STRIPE_PRICE_PRACTICE_ANNUAL,
    'monthly-monthly':  cfg.STRIPE_PRICE_BP_MONTHLY,
    'annual-annual':    cfg.STRIPE_PRICE_BP_ANNUAL,
  }

  return map[`${t}-${b}`] ?? ''
}

// ── Stripe init ───────────────────────────────────────────────────────
onMounted(async () => {
  try {
    // Load Stripe.js — expects window.Stripe to be available via CDN script
    // in app.blade.php: <script src="https://js.stripe.com/v3/"></script>
    if (typeof window.Stripe === 'undefined') {
      throw new Error('Stripe.js not loaded. Add <script src="https://js.stripe.com/v3/"> to app.blade.php.')
    }

    stripe = window.Stripe(props.stripeKey)

    elements = stripe.elements({
      clientSecret: props.clientSecret,
      appearance: {
        theme: 'flat',
        variables: {
          colorPrimary:       '#c4a96a',
          colorBackground:    '#ffffff',
          colorText:          '#1e1c1a',
          colorDanger:        '#e05c5c',
          fontFamily:         'Inter, Helvetica Neue, Arial, sans-serif',
          spacingUnit:        '4px',
          borderRadius:       '10px',
          fontSizeBase:       '13px',
        },
        rules: {
          '.Input': {
            border:      '1px solid #e4dfd7',
            boxShadow:   'none',
            padding:     '10px 14px',
          },
          '.Input:focus': {
            border:      '1px solid #c4a96a',
            boxShadow:   '0 0 0 3px rgba(196,169,106,0.15)',
          },
          '.Label': {
            fontWeight:  '600',
            fontSize:    '12px',
            color:       '#3d3a36',
            marginBottom:'4px',
          },
        },
      },
    })

    paymentElement = elements.create('payment', {
      layout: { type: 'tabs', defaultCollapsed: false },
    })

    paymentElement.mount('#payment-element')

    paymentElement.on('ready', () => { stripeReady.value = true })
    paymentElement.on('change', (e) => {
      if (e.error) errorMessage.value = e.error.message
      else errorMessage.value = ''
    })
  } catch (err) {
    errorMessage.value = err.message ?? 'Could not load payment form. Please refresh.'
    console.error('[OnboardingPayment] Stripe init failed:', err)
  }
})

onUnmounted(() => {
  paymentElement?.unmount()
})

// ── Submit ─────────────────────────────────────────────────────────────
async function submit() {
  if (!stripe || !elements || submitting.value) return

  errorMessage.value = ''
  submitting.value   = true

  try {
    // Confirm the SetupIntent — this tokenizes the card → returns pm_xxx
    const { setupIntent, error } = await stripe.confirmSetup({
      elements,
      confirmParams: {
        // No return_url needed — we handle redirect server-side
      },
      redirect: 'if_required',
    })

    if (error) {
      errorMessage.value = error.message ?? 'Payment failed. Please try again.'
      submitting.value   = false
      return
    }

    const pmId    = setupIntent.payment_method
    const priceId = resolveStripePrice()

    if (!priceId) {
      errorMessage.value = 'Stripe products are not yet configured. Please contact support.'
      submitting.value   = false
      return
    }

    // POST to backend — attach card, create subscription
    const form = useForm({
      payment_method_id: pmId,
      price_id:          priceId,
      addons:            props.plan?.addons ?? [],
    })

    form.post(route('onboarding.subscribe'), {
      onError: (errors) => {
        const first = Object.values(errors)[0]
        errorMessage.value = first ?? 'Subscription failed. Please try again.'
        submitting.value   = false
      },
      onFinish: () => {
        submitting.value = false
      },
    })
  } catch (err) {
    errorMessage.value = 'An unexpected error occurred. Please try again.'
    submitting.value   = false
    console.error('[OnboardingPayment] submit error:', err)
  }
}

function goBack() {
  router.visit(route('onboarding.plan'))
}
</script>

<style scoped>
/* ══ SHELL ══ */
.ob-layout { display:flex; width:100%; height:100vh; overflow:hidden; }

/* ── LEFT ── */
.ob-panel-left { width:42%; background:#1a140d; position:relative; display:flex; flex-direction:column; justify-content:space-between; padding:clamp(20px,4vh,48px) clamp(28px,4vw,52px); overflow:hidden; flex-shrink:0; height:100vh; }
.ob-panel-left-bg { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; z-index:0; pointer-events:none; }
.ob-brand-logo { position:relative; z-index:1; }
.ob-brand-logo-text { font-family:var(--font-serif); font-size:26px; font-weight:700; color:var(--text-inverted); letter-spacing:-0.5px; line-height:1; }
.ob-panel-left-content { position:relative; z-index:1; flex:1; display:flex; flex-direction:column; justify-content:center; padding:clamp(12px,2.5vh,40px) 0; min-height:0; overflow:hidden; }
.ob-panel-left-eyebrow { font-size:10px; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,0.65); margin-bottom:clamp(8px,1.5vh,16px); }
.ob-panel-left-title { font-family:var(--font-serif); font-size:clamp(22px,2.2vw + 0.6rem,34px); font-weight:700; color:var(--text-inverted); line-height:1.22; margin-bottom:clamp(10px,1.8vh,20px); }
.ob-panel-left-body { font-size:13.5px; color:rgba(255,255,255,0.78); line-height:1.65; max-width:340px; margin-bottom:24px; }
.ob-progress-track { margin-top:clamp(14px,2.4vh,32px); display:flex; gap:5px; align-items:center; }
.ob-progress-pip { height:3px; flex:1; border-radius:var(--radius-sm); background:rgba(255,255,255,0.2); transition:all var(--transition); }
.ob-progress-pip.active { background:var(--gold-light); }
.ob-progress-pip.done { background:rgba(255,255,255,0.55); }
.ob-panel-left-footer { position:relative; z-index:1; }
.ob-panel-left-footer p { font-size:11px; color:rgba(255,255,255,0.45); line-height:1.5; }

/* Plan summary panel (left side) */
.ob-plan-summary-panel { background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.12); border-radius:var(--radius); padding:14px 16px; margin-top:4px; }
.ob-psp-label { font-size:9.5px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:rgba(255,255,255,0.5); margin-bottom:4px; }
.ob-psp-name { font-family:var(--font-serif); font-size:14px; font-weight:700; color:var(--text-inverted); margin-bottom:2px; }
.ob-psp-price { font-size:12.5px; color:var(--gold-light); margin-bottom:8px; }
.ob-psp-change { background:none; border:none; font-size:11.5px; color:rgba(255,255,255,0.55); text-decoration:underline; cursor:pointer; padding:0; font-family:var(--font-sans); }
.ob-psp-change:hover { color:var(--gold-light); }

/* ── RIGHT ── */
.ob-panel-right { flex:1; display:flex; flex-direction:column; overflow-y:auto; background-color:var(--surface); height:100vh; }
.ob-panel-right-inner { flex:1; display:flex; flex-direction:column; padding:clamp(32px,4vh,52px) clamp(28px,4vw,52px); max-width:560px; width:100%; margin:0 auto; }

.ob-back-link { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:600; color:var(--text-2); background:none; border:none; padding:6px 0; cursor:pointer; margin-bottom:28px; transition:color var(--transition); }
.ob-back-link:hover { color:var(--gold-dark); }

.ob-step-header { margin-bottom:20px; }
.ob-step-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.8px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.ob-step-title { font-family:var(--font-serif); font-size:clamp(22px,2vw + 0.6rem,28px); font-weight:700; color:var(--text); line-height:1.25; margin-bottom:8px; }
.ob-step-subtitle { font-size:13.5px; color:var(--text-2); line-height:1.55; }

/* Plan recap */
.ob-plan-recap { display:flex; justify-content:space-between; align-items:center; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); padding:14px 18px; margin-bottom:20px; }
.ob-plan-recap-name { font-family:var(--font-serif); font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px; }
.ob-plan-recap-detail { font-size:11.5px; color:var(--text-2); }
.ob-plan-recap-price { font-family:var(--font-serif); font-size:15px; font-weight:700; color:var(--gold-dark); }

/* Error */
.ob-error-banner { display:flex; align-items:flex-start; gap:8px; background:var(--red-light); border:1px solid rgba(224,92,92,0.25); border-radius:var(--radius-sm); padding:10px 14px; font-size:13px; color:var(--red); margin-bottom:16px; line-height:1.5; }

/* Stripe Payment Element */
.ob-payment-element-wrap { margin-bottom:20px; }
.ob-payment-element { min-height:160px; }
.ob-stripe-loading { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; padding:40px 20px; color:var(--text-2); font-size:12.5px; }
.ob-stripe-spinner { width:24px; height:24px; border:2px solid var(--border); border-top-color:var(--gold); border-radius:50%; animation:spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Buttons */
.btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:11px 22px; font-family:var(--font-sans); font-size:13px; font-weight:700; border-radius:var(--radius-full); border:1.5px solid transparent; cursor:pointer; transition:all var(--transition); -webkit-appearance:none; outline:none; }
.btn-primary { background:var(--primary); color:var(--text-inverted); border-color:var(--primary); }
.btn-primary:hover:not(:disabled) { background:var(--primary-mid); border-color:var(--primary-mid); }
.btn-primary:disabled { opacity:0.5; cursor:not-allowed; }
.ob-btn-full { width:100%; margin-bottom:12px; }

.ob-secure-note { display:flex; align-items:center; justify-content:center; gap:6px; font-size:11px; color:var(--text-4); }

/* Responsive */
@media (max-width:900px) { .ob-panel-left { width:36%; padding:36px 32px; } }
@media (max-width:720px) {
  .ob-layout { flex-direction:column; height:auto; min-height:100vh; overflow:visible; }
  .ob-panel-left { width:100%; height:auto; padding:32px 28px; }
  .ob-panel-right { height:auto; overflow:visible; }
  .ob-panel-right-inner { padding:32px 24px; }
}
</style>
