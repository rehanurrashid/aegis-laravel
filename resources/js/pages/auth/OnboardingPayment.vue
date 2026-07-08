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
        <!-- Stripe split card fields — full brand control -->
        <div class="ob-card-fields-wrap">
          <div v-if="!stripeReady" class="ob-stripe-loading">
            <div class="ob-stripe-spinner" />
            Loading secure payment form…
          </div>

          <div v-show="stripeReady" class="ob-card-fields">
            <!-- Card Number -->
            <div class="form-group ob-card-group">
              <label class="form-label ob-card-label">Card Number</label>
              <div ref="cardNumberEl" id="card-number" class="ob-card-input"
                :class="{ 'is-focused': focusedField === 'number', 'is-error': cardNumberError }" />
              <div v-if="cardNumberError" class="form-error ob-field-error">{{ cardNumberError }}</div>
            </div>

            <!-- Expiry + CVC row -->
            <div class="ob-card-row">
              <div class="form-group ob-card-group">
                <label class="form-label ob-card-label">Expiry Date</label>
                <div ref="cardExpiryEl" id="card-expiry" class="ob-card-input"
                  :class="{ 'is-focused': focusedField === 'expiry', 'is-error': cardExpiryError }" />
                <div v-if="cardExpiryError" class="form-error ob-field-error">{{ cardExpiryError }}</div>
              </div>
              <div class="form-group ob-card-group">
                <label class="form-label ob-card-label">CVC</label>
                <div ref="cardCvcEl" id="card-cvc" class="ob-card-input"
                  :class="{ 'is-focused': focusedField === 'cvc', 'is-error': cardCvcError }" />
                <div v-if="cardCvcError" class="form-error ob-field-error">{{ cardCvcError }}</div>
              </div>
            </div>
          </div>
        </div>

        <button
          type="button"
          class="btn btn-primary ob-btn-full"
          :disabled="!stripeReady || submitting"
          @click="submit"
        >
          <AegisIcon v-if="submitting" name="refresh-cw" :size="14" class="ob-spin" />
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
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  role:         { type: String,  required: true },
  plan:         { type: Object,  required: true },  // { tier, billing, addons }
  clientSecret: { type: String,  required: true },  // SetupIntent client_secret
  stripeKey:    { type: String,  required: true },  // pk_test_xxx / pk_live_xxx
  pricing:      { type: Object,  required: true },
})

const toast       = useToast()
const page        = usePage()
const year        = new Date().getFullYear()
const stripeReady    = ref(false)
const submitting     = ref(false)
const errorMessage   = ref('')
const focusedField   = ref('')   // 'number' | 'expiry' | 'cvc' | ''

// Per-field inline errors
const cardNumberError = ref('')
const cardExpiryError = ref('')
const cardCvcError    = ref('')

// Wrapper div refs
const cardNumberEl = ref(null)
const cardExpiryEl = ref(null)
const cardCvcEl    = ref(null)

let stripe       = null
let elements     = null
let cardNumber   = null
let cardExpiry   = null
let cardCvc      = null

// useForm at top level — never inside functions
const subscribeForm = useForm({
  payment_method_id: '',
  price_id:          '',
  addons:            [],
})

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
    // in app.blade.php: <script src="https://js.stripe.com/v3/"><\/script>
    if (typeof window.Stripe === 'undefined') {
      throw new Error('Stripe.js not loaded. Add Stripe CDN script tag to app.blade.php.')
    }

    stripe = window.Stripe(props.stripeKey)

    elements = stripe.elements({
      clientSecret: props.clientSecret,
      appearance: {
        theme: 'flat',
        variables: {
          // Aegis brand tokens (hex — Stripe API does not accept CSS vars)
          colorPrimary:         '#a0813e',  // --gold-dark
          colorBackground:      '#ffffff',  // --surface
          colorText:            '#1e1c1a',  // --text
          colorTextSecondary:   '#6b6560',  // --text-2
          colorTextPlaceholder: '#b5afa8',  // --text-4
          colorDanger:          '#c85c42',  // --red (Aegis brand red)
          colorSuccess:         '#3a7d5c',  // --green
          fontFamily:           'Inter, Helvetica Neue, Arial, sans-serif',
          spacingUnit:          '4px',
          borderRadius:         '8px',
          fontSizeBase:         '13px',
          fontWeightNormal:     '500',
          fontWeightMedium:     '600',
          fontWeightBold:       '700',
        },
        rules: {
          // Base input — 1px border, no shadow
          '.Input': {
            border:           '1px solid #e4dfd7',
            boxShadow:        'none',
            padding:          '9px 13px',
            fontSize:         '13px',
            color:            '#1e1c1a',
            backgroundColor:  '#ffffff',
          },
          // Focus — gold-dark border, no ring
          '.Input:focus': {
            border:           '1px solid #a0813e',
            boxShadow:        'none',
            outline:          'none',
          },
          // Error — Aegis red, 1px, no shadow
          '.Input--invalid': {
            border:           '1px solid #c85c42',
            boxShadow:        'none',
            color:            '#1e1c1a',
          },
          '.Input--invalid:focus': {
            border:           '1px solid #c85c42',
            boxShadow:        'none',
          },
          // Error message text
          '.Error': {
            fontSize:         '11px',
            color:            '#c85c42',
            marginTop:        '4px',
          },
          // Labels
          '.Label': {
            fontWeight:       '600',
            fontSize:         '12px',
            color:            '#3d3a36',
            marginBottom:     '5px',
            letterSpacing:    '0',
          },
          // Dropdown / select — Aegis surface style
          '.DropdownItem': {
            fontSize:         '13px',
            color:            '#1e1c1a',
            backgroundColor:  '#ffffff',
            padding:          '8px 13px',
          },
          '.DropdownItem:hover': {
            backgroundColor:  '#f5f2ed',
            color:            '#a0813e',
          },
          '.DropdownItem--highlight': {
            backgroundColor:  '#f5f2ed',
            color:            '#a0813e',
          },
          // Tab buttons (payment method selector)
          '.Tab': {
            border:           '1px solid #e4dfd7',
            boxShadow:        'none',
            backgroundColor:  '#ffffff',
            fontSize:         '13px',
            fontWeight:       '600',
            color:            '#6b6560',
          },
          '.Tab:hover': {
            border:           '1px solid #a0813e',
            color:            '#1e1c1a',
            boxShadow:        'none',
          },
          '.Tab--selected': {
            border:           '1px solid #a0813e',
            color:            '#1e1c1a',
            backgroundColor:  '#fdf9f3',
            boxShadow:        'none',
          },
          '.Tab--selected:focus': {
            boxShadow:        'none',
            outline:          'none',
          },
          // Checkbox
          '.Checkbox': {
            border:           '1px solid #cec8be',
          },
          '.Checkbox--checked': {
            backgroundColor:  '#a0813e',
            border:           '1px solid #a0813e',
          },
        },
      },
    })

    // Individual card elements — no Link/tabs/wallets/external methods
    const cardStyle = {
      base: {
        color:        '#1e1c1a',
        fontFamily:   'Inter, -apple-system, sans-serif',
        fontSize:     '14px',
        lineHeight:   '44px',
        fontWeight:   '400',
        iconColor:    '#a0928a',
        '::placeholder': { color: '#b0a89e' },
      },
      invalid: {
        color:     '#e53e3e',
        iconColor: '#e53e3e',
      },
    }

    elements = stripe.elements()
    cardNumber = elements.create('cardNumber', { style: cardStyle, showIcon: true, placeholder: '1234 5678 9012 3456' })
    cardExpiry = elements.create('cardExpiry', { style: cardStyle, placeholder: 'MM / YY' })
    cardCvc    = elements.create('cardCvc',    { style: cardStyle, placeholder: 'CVC' })

    cardNumber.mount('#card-number')
    cardExpiry.mount('#card-expiry')
    cardCvc.mount('#card-cvc')

    let nR = false, eR = false, cR = false
    const chk = () => { if (nR && eR && cR) stripeReady.value = true }
    cardNumber.on('ready',  () => { nR = true; chk() })
    cardExpiry.on('ready',  () => { eR = true; chk() })
    cardCvc.on('ready',     () => { cR = true; chk() })

    // Focus / blur
    cardNumber.on('focus', () => { focusedField.value = 'number' })
    cardNumber.on('blur',  () => { focusedField.value = '' })
    cardExpiry.on('focus', () => { focusedField.value = 'expiry' })
    cardExpiry.on('blur',  () => { focusedField.value = '' })
    cardCvc.on('focus',    () => { focusedField.value = 'cvc' })
    cardCvc.on('blur',     () => { focusedField.value = '' })

    // Change — per-field errors + banner clear on fix
    cardNumber.on('change', (e) => {
      cardNumberError.value = e.error?.message ?? ''
      if (!e.error) errorMessage.value = ''
    })
    cardExpiry.on('change', (e) => {
      cardExpiryError.value = e.error?.message ?? ''
      if (!e.error) errorMessage.value = ''
    })
    cardCvc.on('change', (e) => {
      cardCvcError.value = e.error?.message ?? ''
      if (!e.error) errorMessage.value = ''
    })
  } catch (err) {
    errorMessage.value = err.message ?? 'Could not load payment form. Please refresh.'
    console.error('[OnboardingPayment] Stripe init failed:', err)
  }
})

onUnmounted(() => {
  cardNumber?.unmount()
  cardExpiry?.unmount()
  cardCvc?.unmount()
})

// ── Submit ─────────────────────────────────────────────────────────────
async function submit() {
  if (!stripe || !elements || submitting.value) return

  errorMessage.value = ''
  submitting.value   = true

  try {
    const { setupIntent, error } = await stripe.confirmCardSetup(
      props.clientSecret,
      { payment_method: { card: cardNumber } }
    )

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
    subscribeForm.payment_method_id = pmId
    subscribeForm.price_id          = priceId
    subscribeForm.addons            = props.plan?.addons ?? []

    subscribeForm.post(route('onboarding.subscribe'), {
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
function switchAccount() { router.post(route('logout')) }
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
.ob-panel-left-body { font-size:13px; color:rgba(255,255,255,0.78); line-height:1.65; max-width:340px; margin-bottom:24px; }
.ob-progress-track { margin-top:clamp(14px,2.4vh,32px); display:flex; gap:5px; align-items:center; }
.ob-progress-pip { height:3px; flex:1; border-radius:var(--radius-sm); background:rgba(255,255,255,0.2); transition:all var(--transition); }
.ob-progress-pip.active { background:var(--gold-light); }
.ob-progress-pip.done { background:rgba(255,255,255,0.55); }
.ob-panel-left-footer { position:relative; z-index:1; }
.ob-panel-left-footer p { font-size:11px; color:rgba(255,255,255,0.45); line-height:1.5; }

/* Plan summary panel (left side) */
.ob-plan-summary-panel { background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.12); border-radius:var(--radius); padding:14px 16px; margin-top:4px; position:relative; z-index:2; }
.ob-psp-label { font-size:10px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:rgba(255,255,255,0.5); margin-bottom:4px; }
.ob-psp-name { font-family:var(--font-serif); font-size:14px; font-weight:700; color:var(--text-inverted); margin-bottom:2px; }
.ob-psp-price { font-size:12px; color:var(--gold-light); margin-bottom:8px; }
.ob-psp-change { background:none; border:none; font-size:12px; color:rgba(255,255,255,0.55); text-decoration:underline; cursor:pointer; padding:0; font-family:var(--font-sans); position:relative; z-index:2; display:block; }
.ob-psp-change:hover { color:var(--gold-light); }

/* ── RIGHT ── */
.ob-panel-right { flex:1; display:flex; flex-direction:column; overflow-y:auto; background-color:var(--surface); height:100vh; }
.ob-panel-right-inner { flex:1; display:flex; flex-direction:column; padding:clamp(32px,4vh,52px) clamp(28px,4vw,52px); max-width:560px; width:100%; margin:0 auto; }

.ob-back-link { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:600; color:var(--text-2); background:none; border:none; padding:6px 0; cursor:pointer; margin-bottom:28px; transition:color var(--transition); }
.ob-back-link:hover { color:var(--gold-dark); }

.ob-step-header { margin-bottom:20px; }
.ob-step-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.8px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.ob-step-title { font-family:var(--font-serif); font-size:clamp(22px,2vw + 0.6rem,28px); font-weight:700; color:var(--text); line-height:1.25; margin-bottom:8px; }
.ob-step-subtitle { font-size:13px; color:var(--text-2); line-height:1.55; }

/* Plan recap */
.ob-plan-recap { display:flex; justify-content:space-between; align-items:center; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); padding:14px 18px; margin-bottom:20px; }
.ob-plan-recap-name { font-family:var(--font-serif); font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px; }
.ob-plan-recap-detail { font-size:12px; color:var(--text-2); }
.ob-plan-recap-price { font-family:var(--font-serif); font-size:15px; font-weight:700; color:var(--gold-dark); }

/* Error */
.ob-error-banner { display:flex; align-items:flex-start; gap:8px; background:var(--red-light); border:1px solid rgba(224,92,92,0.25); border-radius:var(--radius-sm); padding:10px 14px; font-size:13px; color:var(--red); margin-bottom:16px; line-height:1.5; }

/* Stripe Payment Element */
.ob-card-fields-wrap { margin-bottom:24px; }
.ob-card-fields { display:flex; flex-direction:column; gap:16px; }
.ob-card-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.ob-card-group { margin-bottom:0; padding-bottom:0; }
.ob-card-label { font-size:12px; font-weight:600; color:var(--text-2); margin-bottom:6px; display:block; }
.ob-card-input {
  height: 44px;
  padding: 0 12px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  transition: border-color 0.15s;
}
.ob-card-input.is-focused {
  border-color: var(--gold-dark);
  box-shadow: 0 0 0 3px rgba(160,129,62,0.12);
}
.ob-card-input.is-error { border-color: var(--red); }
.ob-card-input .StripeElement { width: 100%; }
.ob-field-error { font-size: 11px; color: var(--red); margin-top: 4px; line-height: 1.4; }
.ob-stripe-loading { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; padding:40px 20px; color:var(--text-2); font-size:12px; }
.ob-stripe-spinner { width:24px; height:24px; border:2px solid var(--border); border-top-color:var(--gold); border-radius:var(--radius-full); animation:spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Buttons */
.ob-btn-full { width:100%; padding:12px 22px; border-radius:var(--radius-full); font-size:13px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:6px; }

.ob-secure-note { display:flex; align-items:center; justify-content:center; gap:6px; font-size:11px; color:var(--text-4); margin-top:16px; }

/* Responsive */
@media (max-width:900px) { .ob-panel-left { width:36%; padding:36px 32px; } }
@media (max-width:720px) {
  .ob-layout { flex-direction:column; height:auto; min-height:100vh; overflow:visible; }
  .ob-panel-left { width:100%; height:auto; padding:32px 28px; }
  .ob-panel-right { height:auto; overflow:visible; }
  .ob-panel-right-inner { padding:32px 24px; }
}
/* ── Auth CTA buttons — black bg, white text, pill shape ─────────────── */
.ob-btn-full.btn-primary {
  background: var(--primary);
  border: 1px solid var(--primary);
  color: var(--text-inverted);
  border-radius: var(--radius-full);
  padding: 12px 22px;
  font-size: 13px;
  font-weight: 700;
  width: 100%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}
.ob-btn-full.btn-primary:hover:not(:disabled) {
  background: var(--primary-mid);
  border-color: var(--primary-mid);
}
.ob-btn-full.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.ob-switch-account-row { text-align:center; margin-top:12px; }
.ob-switch-account-link { background:none; border:none; cursor:pointer; font-size:12px; color:rgba(255,255,255,0.35); font-family:var(--font-sans); padding:0; }
.ob-switch-account-link:hover { color:rgba(255,255,255,0.65); text-decoration:underline; }
</style>
