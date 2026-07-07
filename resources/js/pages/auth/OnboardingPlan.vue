<!--
  pages/auth/OnboardingPlan.vue — post-email-verification plan selection.

  Shown to paid roles after email verification:
    · Practitioner       → Access ($29) vs Practice ($49), monthly or annual, optional MAAT add-on
    · Business CS        → Single plan ($49/mo or $429/yr)
    · Business Partner   → Monthly ($69) vs Annual ($690/yr)

  Free roles (Invited CS, SS) are never routed here — VerifyEmailController
  sends them directly to their portal dashboard.

  Route: GET /onboarding/plan  (onboarding.plan)
  Next:  POST /onboarding/plan → redirect to OnboardingPayment
-->
<template>
  <Head title="Choose Your Plan — Aegis" />

  <div class="ob-layout">

    <!-- ══ LEFT PANEL ══ -->
    <div class="ob-panel-left">
      <img src="/aegis-bg.svg" class="ob-panel-left-bg" alt="" aria-hidden="true" />
      <div class="ob-brand-logo"><span class="ob-brand-logo-text">Aegis</span></div>
      <div class="ob-panel-left-content">
        <div class="ob-panel-left-eyebrow">{{ leftEyebrow }}</div>
        <h1 class="ob-panel-left-title">{{ leftTitle }}</h1>
        <p class="ob-panel-left-body">{{ leftBody }}</p>
        <div class="ob-panel-features">
          <div v-for="f in leftFeatures" :key="f.icon" class="ob-panel-feature">
            <div class="ob-panel-feature-icon"><AegisIcon :name="f.icon" :size="15" /></div>
            <div class="ob-panel-feature-text"><strong>{{ f.title }}</strong>{{ f.desc }}</div>
          </div>
        </div>
        <!-- Progress: step 3 of 4 for paid roles -->
        <div class="ob-progress-track">
          <div v-for="i in 4" :key="i" class="ob-progress-pip" :class="{ active: i === 3, done: i < 3 }" />
        </div>
      </div>
      <div class="ob-panel-left-footer"><p>© {{ year }} Aegis Platform. All rights reserved.</p></div>
    </div>

    <!-- ══ RIGHT PANEL ══ -->
    <div class="ob-panel-right">
      <div class="ob-panel-right-inner">

        <div class="ob-step-header">
          <div class="ob-step-eyebrow">Step 3 of 4 — Choose Your Plan</div>
          <h2 class="ob-step-title">{{ stepTitle }}</h2>
          <p class="ob-step-subtitle">{{ stepSubtitle }}</p>
        </div>

        <!-- Flash from email verification -->
        <div v-if="flash.success" class="alert alert-success" style="margin-bottom:20px;">
          <div class="alert-icon"><AegisIcon name="check-circle" :size="15" /></div>
          <div class="alert-content">{{ flash.success }}</div>
        </div>

        <!-- ────────────────────────────────────────────────────────────
             PRACTITIONER — Access vs Practice
        ──────────────────────────────────────────────────────────────── -->
        <template v-if="isPractitioner">

          <!-- Billing cycle toggle -->
          <div class="ob-billing-toggle">
            <button type="button" class="ob-billing-btn" :class="{ active: billing === 'monthly' }" @click="billing = 'monthly'">Monthly</button>
            <button type="button" class="ob-billing-btn" :class="{ active: billing === 'annual' }" @click="billing = 'annual'">
              Annual <span class="ob-save-badge">Save 20%</span>
            </button>
          </div>

          <div class="ob-plan-grid">
            <!-- Access -->
            <div class="ob-plan-card" :class="{ selected: selectedTier === 'access' }" @click="selectedTier = 'access'">
              <div class="ob-plan-card-name">Continuity Access</div>
              <div class="ob-plan-card-price">
                <span class="ob-price-amount">${{ billing === 'annual' ? p.practitioner.access.annual_monthly : p.practitioner.access.monthly }}</span>
                <span class="ob-price-period">/mo</span>
              </div>
              <div v-if="billing === 'annual'" class="ob-plan-card-note">Billed ${{ p.practitioner.access.annual_total }}/year · save 20%</div>
              <div class="ob-plan-card-desc">Essential continuity for solo practitioners</div>
              <ul class="ob-plan-features">
                <li v-for="f in p.practitioner.access.features" :key="f">
                  <AegisIcon name="check" :size="11" />{{ f }}
                </li>
              </ul>
              <div v-if="p.practitioner.access.locked.length" class="ob-plan-limits">
                <div v-for="l in p.practitioner.access.locked" :key="l" class="ob-plan-limit-item">
                  <AegisIcon name="lock" :size="10" />{{ l }}
                </div>
              </div>
              <button type="button" class="btn ob-plan-btn" :class="selectedTier === 'access' ? 'btn-primary' : 'btn-outline'">
                {{ selectedTier === 'access' ? '✓ Selected' : 'Select Access' }}
              </button>
            </div>

            <!-- Practice (recommended) -->
            <div class="ob-plan-card recommended" :class="{ selected: selectedTier === 'practice' }" @click="selectedTier = 'practice'">
              <div class="ob-plan-badge">Recommended</div>
              <div class="ob-plan-card-name">Continuity Practice</div>
              <div class="ob-plan-card-price">
                <span class="ob-price-amount">${{ billing === 'annual' ? p.practitioner.practice.annual_monthly : p.practitioner.practice.monthly }}</span>
                <span class="ob-price-period">/mo</span>
              </div>
              <div v-if="billing === 'annual'" class="ob-plan-card-note">Billed ${{ p.practitioner.practice.annual_total }}/year · save 20%</div>
              <div class="ob-plan-card-desc">Full toolkit for active practices</div>
              <ul class="ob-plan-features">
                <li v-for="f in p.practitioner.practice.features" :key="f">
                  <AegisIcon name="check" :size="11" />{{ f }}
                </li>
              </ul>
              <button type="button" class="btn ob-plan-btn" :class="selectedTier === 'practice' ? 'btn-primary' : 'btn-outline'">
                {{ selectedTier === 'practice' ? '✓ Selected' : 'Select Practice' }}
              </button>
            </div>
          </div>

          <!-- MAAT add-on (Practice only) -->
          <div class="ob-maat-addon" :class="{ 'ob-maat-addon--locked': selectedTier !== 'practice' }">
            <div class="ob-maat-header">
              <div class="ob-maat-icon"><AegisIcon name="shield" :size="18" /></div>
              <div>
                <div class="ob-maat-title">MAAT Professional Continuity Steward Service</div>
                <div class="ob-maat-price">
                  +${{ billing === 'annual' ? p.maat.annual_monthly : p.maat.monthly }}/mo
                  <span v-if="billing === 'annual'"> · Billed +${{ p.maat.annual_total }}/yr</span>
                  <span v-else> · Save 20% with annual</span>
                </div>
              </div>
            </div>
            <p class="ob-maat-desc">A MAAT-certified, licensed, and insured CS designated to your practice. Emergency response within 4 hours of incident trigger.</p>
            <label class="ob-checkbox-row" :class="{ 'ob-checkbox-row--disabled': selectedTier !== 'practice' }">
              <input
                v-model="addMaat"
                type="checkbox"
                class="ob-checkbox"
                :disabled="selectedTier !== 'practice'"
              />
              <span class="ob-checkbox-label">
                Add MAAT Professional CS to my plan
                <small v-if="selectedTier !== 'practice'">Available with Continuity Practice</small>
                <small v-else>You can remove this add-on at any time from settings</small>
              </span>
            </label>
          </div>

          <!-- Combo price callout when both selected -->
          <div v-if="selectedTier === 'practice' && addMaat" class="ob-combo-callout">
            <AegisIcon name="dollar-sign" :size="13" />
            <span>
              Practice + MAAT:
              <strong>${{ billing === 'annual' ? p.combo_annual_monthly : p.combo_monthly }}/mo</strong>
              <span v-if="billing === 'annual'"> (billed ${{ p.combo_annual_total }}/yr)</span>
            </span>
          </div>

        </template>

        <!-- ────────────────────────────────────────────────────────────
             BUSINESS PARTNER — Monthly vs Annual
        ──────────────────────────────────────────────────────────────── -->
        <template v-else-if="isBP">
          <div class="ob-plan-grid">
            <div class="ob-plan-card" :class="{ selected: selectedTier === 'monthly' }" @click="selectedTier = 'monthly'; billing = 'monthly'">
              <div class="ob-plan-card-name">Monthly</div>
              <div class="ob-plan-card-price">
                <span class="ob-price-amount">${{ p.bp.monthly }}</span>
                <span class="ob-price-period">/mo</span>
              </div>
              <div class="ob-plan-card-desc">Flexible · cancel anytime</div>
              <ul class="ob-plan-features">
                <li v-for="f in p.bp.features" :key="f"><AegisIcon name="check" :size="11" />{{ f }}</li>
              </ul>
              <button type="button" class="btn ob-plan-btn" :class="selectedTier === 'monthly' ? 'btn-primary' : 'btn-outline'">
                {{ selectedTier === 'monthly' ? '✓ Selected' : 'Select Monthly' }}
              </button>
            </div>

            <div class="ob-plan-card recommended" :class="{ selected: selectedTier === 'annual' }" @click="selectedTier = 'annual'; billing = 'annual'">
              <div class="ob-plan-badge">Best Value</div>
              <div class="ob-plan-card-name">Annual</div>
              <div class="ob-plan-card-price">
                <span class="ob-price-amount">${{ p.bp.annual_total_display }}</span>
                <span class="ob-price-period">/yr</span>
              </div>
              <div class="ob-plan-card-note">≈ ${{ p.bp.annual_monthly }}/mo · save 2 months</div>
              <div class="ob-plan-card-desc">Best value for committed partners</div>
              <ul class="ob-plan-features">
                <li v-for="f in p.bp.features" :key="f"><AegisIcon name="check" :size="11" />{{ f }}</li>
              </ul>
              <button type="button" class="btn ob-plan-btn" :class="selectedTier === 'annual' ? 'btn-primary' : 'btn-outline'">
                {{ selectedTier === 'annual' ? '✓ Selected' : 'Select Annual' }}
              </button>
            </div>
          </div>
        </template>

        <!-- ────────────────────────────────────────────────────────────
             BUSINESS CS — Single tier picker
        ──────────────────────────────────────────────────────────────── -->
        <template v-else-if="isBusinessCS">
          <div class="ob-billing-toggle">
            <button type="button" class="ob-billing-btn" :class="{ active: billing === 'monthly' }" @click="billing = 'monthly'">Monthly</button>
            <button type="button" class="ob-billing-btn" :class="{ active: billing === 'annual' }" @click="billing = 'annual'">
              Annual <span class="ob-save-badge">Save ~27%</span>
            </button>
          </div>

          <div class="ob-cs-plan-card">
            <div class="ob-plan-badge-inline">Business CS Account</div>
            <div class="ob-plan-card-price">
              <span class="ob-price-amount">${{ billing === 'annual' ? p.cs_business.annual_monthly : p.cs_business.monthly }}</span>
              <span class="ob-price-period">/mo</span>
            </div>
            <div v-if="billing === 'annual'" class="ob-plan-card-note">Billed ${{ p.cs_business.annual_total }}/year · save ~27%</div>
            <div class="ob-plan-card-desc">{{ p.cs_business.tagline }}</div>
            <ul class="ob-plan-features">
              <li v-for="f in p.cs_business.features" :key="f"><AegisIcon name="check" :size="11" />{{ f }}</li>
            </ul>
          </div>
        </template>

        <!-- CTA -->
        <button
          type="button"
          class="btn btn-primary ob-btn-full"
          :disabled="!canProceed || form.processing"
          @click="submit"
        >
          <span v-if="form.processing">Saving…</span>
          <span v-else style="display:inline-flex;align-items:center;gap:6px;">Continue to Payment <AegisIcon name="arrow-right" :size="13" /></span>
        </button>

        <div class="ob-secure-note">
          <AegisIcon name="lock" :size="11" />
          Secured with SSL &nbsp;·&nbsp; Cancel anytime &nbsp;·&nbsp; No hidden fees
        </div>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  role:    { type: String, required: true },
  cs_path: { type: String, default: null },
  pricing: { type: Object, required: true },
  flash:   { type: Object, default: () => ({}) },
})

const toast = useToast()
const year  = new Date().getFullYear()

const isPractitioner = computed(() => props.role === 'practitioner')
const isBP           = computed(() => props.role === 'business_partner')
const isBusinessCS   = computed(() => props.role === 'continuity_steward' && props.cs_path === 'business')

// Plan state
const selectedTier = ref(isPractitioner.value ? 'practice' : (isBP.value ? 'monthly' : 'business_cs'))
const billing      = ref('monthly')
const addMaat      = ref(false)

// ── Pricing helpers (cents → dollars) ─────────────────────────────────
const p = computed(() => {
  const pricing = props.pricing
  const toD = (c) => Math.round((c ?? 0) / 100)

  return {
    practitioner: {
      access: {
        monthly:       toD(pricing?.practitioner?.access?.monthly_cents),
        annual_monthly:toD(pricing?.practitioner?.access?.annual_cents),
        annual_total:  toD(pricing?.practitioner?.access?.annual_total_cents),
        features:      pricing?.practitioner?.access?.features ?? [],
        locked:        pricing?.practitioner?.access?.locked ?? [],
      },
      practice: {
        monthly:       toD(pricing?.practitioner?.practice?.monthly_cents),
        annual_monthly:toD(pricing?.practitioner?.practice?.annual_cents),
        annual_total:  toD(pricing?.practitioner?.practice?.annual_total_cents),
        features:      pricing?.practitioner?.practice?.features ?? [],
        locked:        [],
      },
    },
    maat: {
      monthly:        toD(pricing?.maat_addon?.monthly_cents),
      annual_monthly: toD(pricing?.maat_addon?.annual_cents),
      annual_total:   toD(pricing?.maat_addon?.annual_total_cents),
    },
    combo_monthly:         toD((pricing?.practitioner?.practice?.monthly_cents ?? 0) + (pricing?.maat_addon?.monthly_cents ?? 0)),
    combo_annual_monthly:  toD((pricing?.practitioner?.practice?.annual_cents ?? 0) + (pricing?.maat_addon?.annual_cents ?? 0)),
    combo_annual_total:    toD((pricing?.practitioner?.practice?.annual_total_cents ?? 0) + (pricing?.maat_addon?.annual_total_cents ?? 0)),
    bp: {
      monthly:           toD(pricing?.business_partner?.monthly_cents),
      annual_monthly:    toD(pricing?.business_partner?.annual_cents),
      annual_total_display: toD(pricing?.business_partner?.annual_total_cents),
      features:          pricing?.business_partner?.features ?? [],
    },
    cs_business: {
      monthly:       toD(pricing?.continuity_steward_business?.monthly_cents),
      annual_monthly:toD(pricing?.continuity_steward_business?.annual_cents),
      annual_total:  toD(pricing?.continuity_steward_business?.annual_total_cents),
      tagline:       pricing?.continuity_steward_business?.tagline ?? '',
      features:      pricing?.continuity_steward_business?.features ?? [],
    },
  }
})

// ── Left panel content by role ─────────────────────────────────────────
const leftEyebrow = computed(() => ({
  practitioner:       'Almost There',
  business_partner:   'Almost There',
  continuity_steward: 'Almost There',
}[props.role] ?? 'Almost There'))

const leftTitle = computed(() => ({
  practitioner:       'Your email is verified. Now protect your practice.',
  business_partner:   'Your email is verified. Connect with practitioners.',
  continuity_steward: 'Your email is verified. Join the network.',
}[props.role] ?? 'Choose your plan to get started.'))

const leftBody = computed(() => ({
  practitioner:       'Choose the plan that fits your practice today. Upgrade or downgrade anytime from your billing settings.',
  business_partner:   'Get listed in the Aegis directory and start receiving practitioner job postings. Cancel anytime.',
  continuity_steward: 'Your Business CS account gives you a public profile, up to 40 practitioners, and direct payouts via Stripe.',
}[props.role] ?? ''))

const leftFeatures = computed(() => ({
  practitioner: [
    { icon: 'shield-check', title: 'Cancel anytime', desc: 'No contract. Cancel or pause from settings.' },
    { icon: 'lock',         title: 'Secured by Stripe', desc: 'PCI-compliant. Your card never touches our servers.' },
    { icon: 'refresh-cw',   title: 'Upgrade freely', desc: 'Prorated immediately — pay only for what you use.' },
  ],
  business_partner: [
    { icon: 'search',   title: 'Browse jobs immediately', desc: 'Start sending proposals the day you sign up.' },
    { icon: 'receipt',  title: 'Stripe Connect payouts', desc: 'Aegis never holds funds. Direct to your bank.' },
    { icon: 'users',    title: 'Agency or Freelancer', desc: 'Same price, different dashboard experience.' },
  ],
  continuity_steward: [
    { icon: 'shield',    title: 'Public profile',     desc: 'Listed at /steward/<you>. Discoverable by practitioners.' },
    { icon: 'users',     title: 'Up to 40 practitioners', desc: 'Serve a full caseload. Enterprise available for 41+.' },
    { icon: 'credit-card', title: 'Stripe Connect', desc: 'Receive CS fee payouts directly to your bank.' },
  ],
}[props.role] ?? []))

const stepTitle = computed(() => ({
  practitioner:       'Choose Your Continuity Plan',
  business_partner:   'Choose Your Business Partner Plan',
  continuity_steward: 'Business CS Subscription',
}[props.role] ?? 'Choose Your Plan'))

const stepSubtitle = computed(() => ({
  practitioner:       'Both plans include the Continuity Plan Builder and core tools. Upgrade anytime.',
  business_partner:   'Both billing options include all Business Partner features. Save with annual.',
  continuity_steward: 'One plan covers all Business CS features. Save ~27% with annual billing.',
}[props.role] ?? ''))

// ── Proceed guard ──────────────────────────────────────────────────────
const canProceed = computed(() => {
  if (isPractitioner.value) return !!selectedTier.value
  if (isBP.value)           return !!selectedTier.value
  if (isBusinessCS.value)   return true  // single plan, always ready
  return false
})

// ── Form ───────────────────────────────────────────────────────────────
const form = useForm({ tier: '', billing: '', addons: [] })

function submit() {
  if (!canProceed.value) {
    toast.error('Please select a plan to continue.')
    return
  }

  const tier   = isBusinessCS.value ? 'business_cs' : selectedTier.value
  const addons = []
  if (addMaat.value && selectedTier.value === 'practice') addons.push('maat')

  form.tier    = tier
  form.billing = billing.value
  form.addons  = addons

  form.post(route('onboarding.plan.store'), {
    onError: () => toast.error('Something went wrong. Please try again.'),
  })
}
</script>

<style scoped>
.ob-layout { display:flex; width:100%; height:100vh; overflow:hidden; }
.ob-panel-left { width:42%; background:#1a140d; position:relative; display:flex; flex-direction:column; justify-content:space-between; padding:clamp(20px,4vh,48px) clamp(28px,4vw,52px); overflow:hidden; flex-shrink:0; height:100vh; }
.ob-panel-left-bg { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; object-position:center top; pointer-events:none; z-index:0; }
.ob-brand-logo { position:relative; z-index:1; }
.ob-brand-logo-text { font-family:var(--font-serif); font-size:26px; font-weight:700; color:var(--text-inverted); letter-spacing:-0.5px; line-height:1; }
.ob-panel-left-content { position:relative; z-index:1; flex:1; display:flex; flex-direction:column; justify-content:center; padding:clamp(12px,2.5vh,40px) 0; min-height:0; overflow:hidden; }
.ob-panel-left-eyebrow { font-size:10px; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,0.65); margin-bottom:clamp(8px,1.5vh,16px); }
.ob-panel-left-title { font-family:var(--font-serif); font-size:clamp(22px,2.2vw + 0.6rem,34px); font-weight:700; color:var(--text-inverted); line-height:1.22; margin-bottom:clamp(10px,1.8vh,20px); }
.ob-panel-left-body { font-size:13px; color:rgba(255,255,255,0.78); line-height:1.65; max-width:340px; }
.ob-panel-features { display:flex; flex-direction:column; gap:clamp(8px,1.4vh,14px); margin-top:clamp(16px,2.8vh,36px); }
.ob-panel-feature { display:flex; align-items:flex-start; gap:14px; }
.ob-panel-feature-icon { width:32px; height:32px; background:rgba(255,255,255,0.12); border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; flex-shrink:0; color:rgba(255,255,255,0.85); }
.ob-panel-feature-text { font-size:12px; color:rgba(255,255,255,0.75); line-height:1.5; }
.ob-panel-feature-text strong { display:block; font-weight:600; color:rgba(255,255,255,0.92); font-size:12px; margin-bottom:1px; }
.ob-progress-track { margin-top:clamp(14px,2.4vh,32px); display:flex; gap:5px; align-items:center; }
.ob-progress-pip { height:3px; flex:1; border-radius:var(--radius-sm); background:rgba(255,255,255,0.2); transition:all var(--transition); }
.ob-progress-pip.active { background:var(--gold-light); }
.ob-progress-pip.done { background:rgba(255,255,255,0.55); }
.ob-panel-left-footer { position:relative; z-index:1; }
.ob-panel-left-footer p { font-size:11px; color:rgba(255,255,255,0.45); line-height:1.5; }
.ob-panel-right { flex:1; display:flex; flex-direction:column; overflow-y:auto; overflow-x:hidden; background-color:var(--surface); height:100vh; }
.ob-panel-right-inner { flex:1; display:flex; flex-direction:column; padding:clamp(32px,4vh,52px) clamp(32px,4vw,60px); max-width:680px; width:100%; margin:0 auto; }
.ob-step-header { margin-bottom:28px; }
.ob-step-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.8px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.ob-step-title { font-family:var(--font-serif); font-size:clamp(22px,2vw + 0.6rem,28px); font-weight:700; color:var(--text); line-height:1.25; margin-bottom:8px; }
.ob-step-subtitle { font-size:13px; color:var(--text-2); line-height:1.55; }
.ob-billing-toggle { display:flex; width:fit-content; align-items:center; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius-full); padding:4px; gap:0; margin-bottom:20px; }
.ob-billing-btn { background:transparent; border:none; font-family:var(--font-sans); font-size:12px; font-weight:600; padding:7px 18px; border-radius:var(--radius-full); cursor:pointer; color:var(--text-2); transition:all var(--transition); display:flex; align-items:center; gap:6px; }
.ob-billing-btn.active { background:var(--gold-dark); color:var(--text-inverted); }
.ob-save-badge { font-size:10px; font-weight:700; color:var(--gold-dark); }
.ob-billing-btn.active .ob-save-badge { color:var(--text-inverted); }
.ob-plan-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:20px; }
.ob-plan-card { border:1px solid var(--border); border-radius:var(--radius-lg); padding:22px 20px; cursor:pointer; transition:all var(--transition); background:var(--surface); position:relative; display:flex; flex-direction:column; }
.ob-plan-card:hover { border-color:var(--gold-dark); transform:translateY(-2px); box-shadow:var(--shadow); }
.ob-plan-card.selected { border-color:var(--gold-dark); background:rgba(196,169,106,0.06); }
.ob-plan-card.recommended { border-color:var(--gold-light); }
.ob-plan-badge { position:absolute; top:-10px; left:50%; transform:translateX(-50%); background:var(--gold-dark); color:var(--text-inverted); font-size:10px; font-weight:700; padding:3px 12px; border-radius:var(--radius-sm); letter-spacing:0.8px; text-transform:uppercase; white-space:nowrap; }
.ob-plan-badge-inline { display:inline-flex; align-items:center; background:var(--gold-dark); color:var(--text-inverted); font-size:10px; font-weight:700; padding:4px 12px; border-radius:var(--radius-sm); letter-spacing:0.8px; text-transform:uppercase; margin-bottom:12px; }
.ob-plan-card-name { font-family:var(--font-serif); font-size:15px; font-weight:700; color:var(--text); margin-bottom:8px; }
.ob-plan-card-price { display:flex; align-items:baseline; gap:2px; margin-bottom:4px; }
.ob-price-amount { font-family:var(--font-serif); font-size:28px; font-weight:700; color:var(--text); }
.ob-price-period { font-size:13px; color:var(--text-2); }
.ob-plan-card-note { font-size:11px; color:var(--gold-dark); font-weight:600; margin-bottom:4px; }
.ob-plan-card-desc { font-size:12px; color:var(--text-2); line-height:1.5; margin-bottom:14px; }
.ob-plan-features { list-style:none; padding:0; margin:0 0 10px; display:flex; flex-direction:column; gap:5px; }
.ob-plan-features li { display:flex; align-items:center; gap:7px; font-size:12px; color:var(--text-2); line-height:1.4; }
.ob-plan-limits { margin-bottom:14px; display:flex; flex-direction:column; gap:4px; }
.ob-plan-limit-item { display:flex; align-items:center; gap:6px; font-size:11px; color:var(--text-4); }
.ob-plan-btn { width:100%; margin-top:auto; }
.ob-cs-plan-card { border:1px solid var(--gold); border-radius:var(--radius-lg); padding:24px; background:rgba(196,169,106,0.04); margin-bottom:20px; }
.ob-maat-addon { background:rgba(196,169,106,0.04); border:1px solid rgba(196,169,106,0.2); border-radius:var(--radius-lg); padding:16px 18px; margin-bottom:16px; transition:opacity var(--transition); }
.ob-maat-addon--locked { opacity:0.55; }
.ob-maat-header { display:flex; align-items:flex-start; gap:12px; margin-bottom:8px; }
.ob-maat-icon { width:36px; height:36px; background:var(--gold-dark); border-radius:var(--radius-full); display:flex; align-items:center; justify-content:center; color:var(--text-inverted); flex-shrink:0; }
.ob-maat-title { font-size:13px; font-weight:700; color:var(--text); margin-bottom:3px; }
.ob-maat-price { font-size:12px; color:var(--gold-dark); font-weight:600; }
.ob-maat-desc { font-size:12px; color:var(--text-2); line-height:1.55; margin-bottom:12px; }
.ob-checkbox-row { display:flex; align-items:flex-start; gap:10px; cursor:pointer; }
.ob-checkbox-row--disabled { cursor:not-allowed; }
.ob-checkbox { width:15px; height:15px; flex-shrink:0; margin-top:2px; accent-color:var(--gold-dark); }
.ob-checkbox-label { font-size:12px; color:var(--text); line-height:1.5; }
.ob-checkbox-label small { display:block; font-size:11px; color:var(--text-4); margin-top:2px; }
.ob-combo-callout { display:flex; align-items:center; gap:8px; background:rgba(196,169,106,0.08); border:1px solid rgba(196,169,106,0.25); border-radius:var(--radius-sm); padding:10px 14px; font-size:12px; color:var(--text-2); margin-bottom:16px; }
.ob-btn-full { width:100%; padding:12px 22px; border-radius:var(--radius-full); font-size:13px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:6px; }
.ob-secure-note { display:flex; align-items:center; justify-content:center; gap:6px; font-size:12px; color:var(--text-4); margin-top:16px; }
@media (max-width:720px) { .ob-layout { flex-direction:column; height:auto; min-height:100vh; overflow:visible; } .ob-panel-left { width:100%; height:auto; padding:32px 28px; } .ob-panel-right { height:auto; overflow:visible; } .ob-panel-right-inner { padding:32px 24px; } .ob-plan-grid { grid-template-columns:1fr; } }
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
</style>
