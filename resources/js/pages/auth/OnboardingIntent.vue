<!--
  pages/auth/OnboardingIntent.vue — pre-signup intent capture (2 steps).

  Step 1: Role selection  — "Who are you joining as?"
  Step 2: Use-case        — "What brings you to Aegis?" (multi-select checkboxes,
                            options differ per role, matching onboarding.php step6)

  Support Steward skips step 2 (no use-cases) and goes straight to Register.

  Route: GET onboarding.intent → POST onboarding.intent.store
  Stores: { intent, use_cases: string[] } in session → user_meta on register
-->
<template>
  <Head title="Get Started — Aegis" />

  <div class="ob-layout">
    <!-- ══ LEFT PANEL ══ -->
    <div class="ob-panel-left">
      <img src="/aegis-bg.svg" class="ob-panel-left-bg" alt="" aria-hidden="true" />
      <div class="ob-brand-logo"><span class="ob-brand-logo-text">Aegis</span></div>
      <div class="ob-panel-left-content">
        <div class="ob-panel-left-eyebrow">Practitioner Platform</div>
        <h1 class="ob-panel-left-title">Protect Your Practice. Connect Your Network.</h1>
        <p class="ob-panel-left-body">Aegis is built for health and wellness practitioners — and the professionals who support them. Choose the role that fits how you'll use the platform.</p>
        <div class="ob-panel-features">
          <div class="ob-panel-feature">
            <div class="ob-panel-feature-icon"><AegisIcon name="shield" :size="15" /></div>
            <div class="ob-panel-feature-text"><strong>Continuity of Care Planning</strong>Emergency preparedness and practice succession built in.</div>
          </div>
          <div class="ob-panel-feature">
            <div class="ob-panel-feature-icon"><AegisIcon name="users" :size="15" /></div>
            <div class="ob-panel-feature-text"><strong>Professional Network</strong>Connect with practitioners, stewards, and business partners.</div>
          </div>
          <div class="ob-panel-feature">
            <div class="ob-panel-feature-icon"><AegisIcon name="lock" :size="15" /></div>
            <div class="ob-panel-feature-text"><strong>Secure &amp; HIPAA-aware</strong>Built with privacy and compliance at the core.</div>
          </div>
        </div>
        <!-- Step progress pips -->
        <div class="ob-progress-track">
          <div class="ob-progress-pip" :class="{ active: step >= 1, done: step > 1 }" />
          <div class="ob-progress-pip" :class="{ active: step >= 2, done: step > 2 }" v-if="showStep2" />
        </div>
      </div>
      <div class="ob-panel-left-footer"><p>© {{ year }} Aegis Platform. All rights reserved.</p></div>
    </div>

    <!-- ══ RIGHT PANEL ══ -->
    <div class="ob-panel-right">
      <div class="ob-panel-right-inner">

        <!-- ── STEP 1: Role selection ── -->
        <transition name="ob-step">
          <div v-if="step === 1" key="step1">
            <div class="ob-step-header">
              <div class="ob-step-eyebrow">Step 1 of {{ showStep2 ? '2' : '1' }}</div>
              <h2 class="ob-step-title">Who are you joining as?</h2>
              <p class="ob-step-subtitle">Choose the role that best describes how you'll use Aegis.</p>
            </div>

            <div class="role-cards">
              <button
                v-for="r in roles"
                :key="r.value"
                type="button"
                class="role-card"
                :class="{ 'is-selected': form.intent === r.value }"
                @click="selectRole(r.value)"
              >
                <div class="role-card-icon"><AegisIcon :name="r.icon" :size="20" /></div>
                <div class="role-card-body">
                  <div class="role-card-title">{{ r.label }}</div>
                  <div class="role-card-desc">{{ r.description }}</div>
                  <div class="oi-role-badge" :class="r.badgeVariant">
                    <AegisIcon name="check" :size="9" />{{ r.badge }}
                  </div>
                </div>
                <div class="oi-role-action"><AegisIcon name="chevron-right" :size="16" /></div>
              </button>
            </div>

            <div v-if="fieldError('intent')" class="form-error">{{ fieldError('intent') }}</div>

            <button
              type="button"
              class="btn btn-primary ob-btn-full"
              :disabled="!form.intent"
              @click="nextStep"
            >Continue</button>

            <p class="ob-altline">Already have an account? <a :href="route('login')" class="ob-altline-link">Sign In</a></p>
          </div>
        </transition>

        <!-- ── STEP 2: What brings you to Aegis? ── -->
        <transition name="ob-step">
          <div v-if="step === 2" key="step2">
            <button type="button" class="ob-back-link" @click="step = 1">
              <AegisIcon name="arrow-left" :size="14" /> Back
            </button>

            <div class="ob-step-header">
              <div class="ob-step-eyebrow">Step 2 of 2</div>
              <h2 class="ob-step-title">What brings you to Aegis?</h2>
              <p class="ob-step-subtitle">Select all that apply. This helps personalise your dashboard and recommendations.</p>
            </div>

            <form @submit.prevent="submit" novalidate>
              <div class="ob-purpose-list">
                <label
                  v-for="uc in currentUseCases"
                  :key="uc.value"
                  class="ob-purpose-item"
                  :class="{ 'is-selected': form.use_cases.includes(uc.value) }"
                >
                  <input
                    type="checkbox"
                    class="ob-purpose-check"
                    :value="uc.value"
                    :checked="form.use_cases.includes(uc.value)"
                    @change="toggleUseCase(uc.value)"
                  />
                  <span class="ob-purpose-label">{{ uc.label }}</span>
                </label>
              </div>

              <button
                type="submit"
                class="btn btn-primary ob-btn-full"
                :disabled="form.processing"
              >
                <AegisIcon v-if="form.processing" name="refresh-cw" :size="13" class="ob-spin" />
                {{ form.processing ? 'Saving…' : 'Continue to Registration' }}
              </button>

              <p class="ob-skip-link" @click="submit">Skip this step</p>
            </form>
          </div>
        </transition>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { useVuelidate } from '@vuelidate/core'
import { required, helpers } from '@vuelidate/validators'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const year  = new Date().getFullYear()
const step  = ref(1)

// ── Roles ───────────────────────────────────────────────────────────
const roles = [
  {
    value: 'provider',
    label: 'Practitioner Partner',
    icon:  'activity',
    description: 'Doctor, therapist, specialist, and health professionals building and protecting their practice.',
    badge: 'Subscription required',
    badgeVariant: '',
  },
  {
    value: 'business_partner',
    label: 'Business Partner',
    icon:  'briefcase',
    description: 'Billing, legal, consultants, and business service providers working with health professionals.',
    badge: 'Subscription required',
    badgeVariant: '',
  },
  {
    value: 'continuity_steward',
    label: 'Continuity Steward',
    icon:  'shield',
    description: 'Practice succession specialists and licensed professionals supporting practitioner continuity.',
    badge: 'Free via invitation',
    badgeVariant: 'free',
  },
  {
    value: 'support_steward',
    label: 'Support Steward',
    icon:  'heart',
    description: 'Administrative staff, personal representatives, and designated support for practitioners.',
    badge: 'Invitation only',
    badgeVariant: 'free',
  },
]

// ── Use-case options per role — matching onboarding.php step6 exactly ─
const useCasesByRole = {
  provider: [
    { value: 'continuity',         label: 'Continuity of Care Emergency Planning (Continuity Plan, Continuity Steward, Support Steward Preparation)' },
    { value: 'practice_mgmt',      label: 'Practice Management (tracking training, renewals)' },
    { value: 'network',            label: 'Network Development' },
    { value: 'care_coord',         label: 'Care Coordination' },
    { value: 'innovation',         label: 'Innovation and Practice Development (connect, learn, partner, expand scope)' },
    { value: 'resources',          label: 'Resource Access' },
  ],
  business_partner: [
    { value: 'offer_services',     label: 'Offer practice support services to health practitioners' },
    { value: 'grow_client_base',   label: 'Grow my client base as a Practitioner Partner' },
    { value: 'long_term_contracts',label: 'Establish long-term service contracts' },
  ],
  continuity_steward: [
    { value: 'succession',         label: 'Provide practice succession planning services' },
    { value: 'emergency_planning', label: 'Support continuity of care emergency planning' },
    { value: 'cs_network',         label: 'Connect with other Continuity Stewards' },
    { value: 'multi_practitioner', label: 'Manage and support multiple practitioner accounts' },
  ],
  // Support Steward has no use-case step — flows direct to register
}

const currentUseCases = computed(() => useCasesByRole[form.intent] ?? [])
const showStep2       = computed(() => form.intent && form.intent !== 'support_steward')

// ── Form ────────────────────────────────────────────────────────────
const form = useForm({
  intent:    '',
  use_cases: [],   // array — serialized to JSON in user_meta on register
})

// ── Vuelidate ────────────────────────────────────────────────────────
const rules = computed(() => ({
  intent: { required: helpers.withMessage('Select a role to continue.', required) },
}))
const v$ = useVuelidate(rules, form, { $scope: false })

function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message
  if (form.errors[field]) return form.errors[field]
  return null
}

// ── Interactions ────────────────────────────────────────────────────
function selectRole(value) {
  form.intent    = value
  form.use_cases = []   // reset on role change
  v$.value.$reset()
}

async function nextStep() {
  const valid = await v$.value.$validate()
  if (!valid) { toast.error('Please select a role to continue.'); return }

  // SS skips use-case step — submit directly
  if (form.intent === 'support_steward') {
    submit()
    return
  }

  step.value = 2
}

function toggleUseCase(value) {
  const idx = form.use_cases.indexOf(value)
  if (idx === -1) {
    form.use_cases = [...form.use_cases, value]
  } else {
    form.use_cases = form.use_cases.filter(v => v !== value)
  }
}

function submit() {
  form.post(route('onboarding.intent.store'), {
    onError: () => toast.error('Something went wrong. Please try again.'),
  })
}
</script>

<style scoped>
.ob-layout { display:flex; width:100%; height:100vh; overflow:hidden; }

/* ── Left panel ── */
.ob-panel-left { width:42%; background:#1a140d; position:relative; display:flex; flex-direction:column; justify-content:space-between; padding:clamp(20px,4vh,48px) clamp(28px,4vw,52px); overflow:hidden; flex-shrink:0; height:100vh; }
.ob-panel-left-bg { position:absolute; top:0; left:0; right:0; bottom:0; width:100%; height:100%; object-fit:cover; object-position:top center; pointer-events:none; z-index:0; }
.ob-brand-logo { position:relative; z-index:1; }
.ob-brand-logo-text { font-family:var(--font-serif); font-size:26px; font-weight:700; color:var(--text-inverted); letter-spacing:-0.5px; line-height:1; }
.ob-panel-left-content { position:relative; z-index:1; flex:1; display:flex; flex-direction:column; justify-content:center; padding:clamp(12px,2.5vh,40px) 0; min-height:0; overflow:hidden; }
.ob-panel-left-eyebrow { font-size:10px; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,0.65); margin-bottom:clamp(8px,1.5vh,16px); }
.ob-panel-left-title { font-family:var(--font-serif); font-size:clamp(22px,2.2vw + 0.6rem,34px); font-weight:700; color:var(--text-inverted); line-height:1.22; margin-bottom:clamp(10px,1.8vh,20px); }
.ob-panel-left-body { font-size:13px; color:rgba(255,255,255,0.78); line-height:1.65; max-width:340px; }
.ob-panel-features { display:flex; flex-direction:column; gap:clamp(8px,1.4vh,14px); margin-top:clamp(16px,2.8vh,36px); }
.ob-panel-feature { display:flex; align-items:flex-start; gap:12px; }
.ob-panel-feature-icon { width:28px; height:28px; background:rgba(196,169,106,0.15); border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; color:var(--gold-dark); flex-shrink:0; }
.ob-panel-feature-text { font-size:12px; color:rgba(255,255,255,0.72); line-height:1.5; }
.ob-panel-feature-text strong { color:rgba(255,255,255,0.92); display:block; margin-bottom:1px; }
.ob-panel-left-footer { position:relative; z-index:1; }
.ob-panel-left-footer p { font-size:11px; color:rgba(255,255,255,0.38); line-height:1.5; }

/* Progress pips */
.ob-progress-track { display:flex; gap:5px; align-items:center; margin-top:clamp(16px,2.5vh,32px); }
.ob-progress-pip { height:3px; border-radius:2px; background:rgba(255,255,255,0.2); flex:1; transition:background 0.3s; }
.ob-progress-pip.active { background:var(--gold-light); }
.ob-progress-pip.done { background:rgba(255,255,255,0.55); }

/* ── Right panel ── */
.ob-panel-right { flex:1; display:flex; flex-direction:column; overflow-y:auto; background-color:var(--surface); }
.ob-panel-right-inner { flex:1; display:flex; flex-direction:column; justify-content:center; padding:clamp(32px,5vh,64px) clamp(28px,5vw,72px); max-width:560px; width:100%; margin:0 auto; }

/* Step transition */
.ob-step-enter-active { transition:opacity 0.22s ease, transform 0.22s ease; }
.ob-step-enter-from { opacity:0; transform:translateY(10px); }

/* ── Step header ── */
.ob-step-header { margin-bottom:28px; }
.ob-step-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.8px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.ob-step-title { font-family:var(--font-serif); font-size:clamp(22px,2vw + 0.6rem,30px); font-weight:700; color:var(--text); line-height:1.22; margin-bottom:8px; }
.ob-step-subtitle { font-size:13px; color:var(--text-2); line-height:1.55; }

/* ── Back link ── */
.ob-back-link { display:inline-flex; align-items:center; gap:6px; background:none; border:none; cursor:pointer; font-size:12px; font-weight:600; color:var(--text-2); font-family:var(--font-sans); margin-bottom:24px; padding:0; transition:color var(--transition); }
.ob-back-link:hover { color:var(--gold-dark); }

/* ── Role cards (step 1) ── */
.role-cards { display:flex; flex-direction:column; gap:10px; margin-bottom:20px; }
.role-card { display:flex; align-items:center; gap:14px; width:100%; padding:16px 18px; border:1px solid var(--border); border-radius:var(--radius); background:var(--surface); cursor:pointer; text-align:left; transition:all var(--transition); -webkit-appearance:none; }
.role-card:hover { border-color:var(--gold); background:rgba(196,169,106,0.04); }
.role-card.is-selected { border-color:var(--gold-dark); background:rgba(196,169,106,0.06); }
.role-card-icon { width:40px; height:40px; background:rgba(196,169,106,0.1); border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; color:var(--gold-dark); flex-shrink:0; }
.role-card.is-selected .role-card-icon { background:rgba(196,169,106,0.18); }
.role-card-body { flex:1; min-width:0; }
.role-card-title { font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px; }
.role-card-desc { font-size:12px; color:var(--text-2); line-height:1.4; }
.oi-role-badge { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:600; padding:3px 9px; border-radius:var(--radius-full); margin-top:7px; background:rgba(196,169,106,0.1); color:var(--gold-dark); border:1px solid rgba(196,169,106,0.25); }
.oi-role-badge.free { background:rgba(76,175,125,0.1); color:var(--green); border-color:rgba(76,175,125,0.25); }
.oi-role-action { flex-shrink:0; display:flex; align-items:center; color:var(--text-4); transition:color var(--transition); }
.role-card:hover .oi-role-action, .role-card.is-selected .oi-role-action { color:var(--gold-dark); }

/* ── Purpose / use-case checkboxes (step 2) ── */
.ob-purpose-list { display:flex; flex-direction:column; gap:8px; margin-bottom:24px; }
.ob-purpose-item {
  display:flex; align-items:flex-start; gap:12px;
  border:1px solid var(--border); border-radius:var(--radius);
  padding:13px 16px; cursor:pointer; transition:all var(--transition);
  background:var(--surface);
}
.ob-purpose-item:hover { border-color:var(--gold-light); background:var(--surface-2); }
.ob-purpose-item.is-selected { border-color:var(--gold-dark); background:rgba(196,169,106,0.05); }
.ob-purpose-check {
  -webkit-appearance:none; appearance:none;
  width:16px; height:16px; min-width:16px;
  border:1px solid var(--border-dark); border-radius:4px;
  background:var(--surface); cursor:pointer; margin-top:1px;
  transition:all var(--transition); position:relative;
}
.ob-purpose-check:checked {
  background:var(--primary); border-color:var(--primary);
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpolyline points='2,6 5,9 10,3' fill='none' stroke='white' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat:no-repeat; background-position:center; background-size:11px;
}
.ob-purpose-label { font-size:13px; color:var(--text-2); line-height:1.5; cursor:pointer; }
.ob-purpose-item.is-selected .ob-purpose-label { color:var(--text); }

/* Skip link */
.ob-skip-link { text-align:center; margin-top:14px; font-size:12px; color:var(--text-4); cursor:pointer; }
.ob-skip-link:hover { color:var(--text-2); text-decoration:underline; }

/* ── CTA button ── */
.ob-btn-full { width:100%; padding:12px 22px; border-radius:var(--radius-full); font-size:13px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:6px; }
.ob-btn-full.btn-primary { background:var(--primary); border:1px solid var(--primary); color:var(--text-inverted); border-radius:var(--radius-full); padding:12px 22px; font-size:13px; font-weight:700; width:100%; display:inline-flex; align-items:center; justify-content:center; gap:6px; }
.ob-btn-full.btn-primary:hover:not(:disabled) { background:var(--primary-mid); border-color:var(--primary-mid); }
.ob-btn-full.btn-primary:disabled { opacity:0.5; cursor:not-allowed; }
.ob-spin { animation:ob-spin 0.8s linear infinite; }
@keyframes ob-spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }

/* ── Alt line ── */
.ob-altline { text-align:center; margin-top:20px; font-size:12px; color:var(--text-2); }
.ob-altline-link { color:var(--gold-dark); text-decoration:none; font-weight:600; }
.ob-altline-link:hover { text-decoration:underline; }
</style>
