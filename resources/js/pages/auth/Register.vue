<!--
  pages/auth/Register.vue — complete multi-step onboarding for all Aegis roles.

  Step map (role-conditional):
    Step 0 — Role Selection (all)
    Step 1 — Role sub-path
              · Practitioner       → skip (no sub-path)
              · Business Partner   → BP type (Agency / Freelancer)
              · Continuity Steward → CS pathway (Business CS / Invited CS)
              · Support Steward    → Invite-only gate (blocked)
    Step 2 — Account creation form (role-conditional fields)
              · Invited CS also shows invitation code field
    ——————
    (After submit → email verify → onboarding/plan → onboarding/payment for paid roles)

  Route: POST register.store
-->
<template>
  <Head title="Create Account — Aegis" />

  <!-- Who We Serve modal -->
  <AegisModal v-model="showWws" title="Who We Serve" size="md">
    <div v-for="section in wwsSections" :key="section.title" class="wws-section">
      <div class="wws-section-title">{{ section.title }}</div>
      <ul class="wws-list">
        <li v-for="item in section.items" :key="item">{{ item }}</li>
      </ul>
    </div>
  </AegisModal>

  <div class="ob-layout">

    <!-- ══════════════ LEFT PANEL ══════════════ -->
    <div class="ob-panel-left">
      <img src="/aegis-bg.svg" class="ob-panel-left-bg" alt="" aria-hidden="true" />
      <div class="ob-brand-logo">
        <span class="ob-brand-logo-text">Aegis</span>
      </div>
      <div class="ob-panel-left-content">
        <div class="ob-panel-left-eyebrow">{{ leftEyebrow }}</div>
        <h1 class="ob-panel-left-title">{{ leftTitle }}</h1>
        <p class="ob-panel-left-body">{{ leftBody }}</p>
        <div class="ob-panel-features">
          <div v-for="f in leftFeatures" :key="f.icon" class="ob-panel-feature">
            <div class="ob-panel-feature-icon">
              <AegisIcon :name="f.icon" :size="15" />
            </div>
            <div class="ob-panel-feature-text">
              <strong>{{ f.title }}</strong>
              {{ f.desc }}
            </div>
          </div>
        </div>
        <!-- Progress pips: shows active step -->
        <div class="ob-progress-track">
          <div
            v-for="i in totalSteps"
            :key="i"
            class="ob-progress-pip"
            :class="{ active: i - 1 === currentStep, done: i - 1 < currentStep }"
          />
        </div>
      </div>
      <div class="ob-panel-left-footer">
        <p>
          © {{ year }} Aegis Platform. All rights reserved.<br />
          <button type="button" class="ob-footer-link" @click="showWws = true">Who We Serve</button>
          &nbsp;·&nbsp;
          <a :href="route('about')" class="ob-footer-link">About</a>
          &nbsp;·&nbsp;
          <a :href="route('pricing')" class="ob-footer-link">Pricing</a>
        </p>
      </div>
    </div>

    <!-- ══════════════ RIGHT PANEL ══════════════ -->
    <div class="ob-panel-right">
      <div class="ob-panel-right-inner">

        <!-- ══ STEP 0: ROLE SELECTION ══ -->
        <div v-show="currentStep === 0" class="ob-step">
          <div class="ob-step-header">
            <div class="ob-step-eyebrow">Step 1 of {{ totalSteps }} — Choose Your Role</div>
            <h2 class="ob-step-title">Select Your Role</h2>
            <p class="ob-step-subtitle">Choose the role that best describes how you'll use Aegis. This personalizes your onboarding and dashboard.</p>
          </div>

          <div class="ob-role-cards">
            <div
              v-for="r in roles"
              :key="r.value"
              class="ob-role-card"
              :class="{ selected: form.role === r.value }"
              @click="selectRole(r.value)"
            >
              <div class="ob-role-card-icon">
                <AegisIcon :name="r.icon" :size="20" />
              </div>
              <div class="ob-role-card-body">
                <div class="ob-role-card-title">{{ r.label }}</div>
                <div class="ob-role-card-desc">{{ r.desc }}</div>
                <div class="ob-role-card-badge" :class="r.badgeVariant">
                  <AegisIcon name="check" :size="9" />{{ r.badge }}
                </div>
              </div>
              <div class="ob-role-card-action">
                <AegisIcon name="chevron-right" :size="16" />
              </div>
            </div>
          </div>

          <div v-if="fieldError('role')" class="form-error">{{ fieldError('role') }}</div>

          <button
            type="button"
            class="btn btn-primary ob-btn-full"
            :disabled="!form.role"
            @click="advanceFromRole"
          >Continue</button>

          <div class="ob-signin-row">Already have an account? <a :href="route('login')" class="ob-signin-link">Sign In</a></div>
        </div>

        <!-- ══ STEP 1a: CS PATHWAY ══ -->
        <div v-show="currentStep === 1 && form.role === 'continuity_steward'" class="ob-step">
          <button type="button" class="ob-back-link" @click="currentStep = 0">
            <AegisIcon name="chevron-left" :size="14" /> Back
          </button>
          <div class="ob-step-header">
            <div class="ob-step-eyebrow">Step 2 of {{ totalSteps }} — CS Pathway</div>
            <h2 class="ob-step-title">Continuity Steward Pathway</h2>
            <p class="ob-step-subtitle">Select how you are joining Aegis as a Continuity Steward.</p>
          </div>

          <div class="ob-role-cards">
            <div
              class="ob-role-card"
              :class="{ selected: form.cs_path === 'business' }"
              @click="form.cs_path = 'business'"
            >
              <div class="ob-role-card-icon"><AegisIcon name="briefcase" :size="20" /></div>
              <div class="ob-role-card-body">
                <div class="ob-role-card-title">Business CS — Independent Account</div>
                <div class="ob-role-card-desc">Paid account ($49/mo or $429/yr). Serve 2–40 practitioners. Public profile included. Proactively invite practitioners to your network.</div>
                <div class="ob-role-card-badge">
                  <AegisIcon name="check" :size="9" />$49/mo · Subscription required
                </div>
              </div>
              <div class="ob-role-card-action"><AegisIcon name="chevron-right" :size="16" /></div>
            </div>

            <div
              class="ob-role-card"
              :class="{ selected: form.cs_path === 'invited' }"
              @click="form.cs_path = 'invited'"
            >
              <div class="ob-role-card-icon"><AegisIcon name="user-check" :size="20" /></div>
              <div class="ob-role-card-body">
                <div class="ob-role-card-title">Invited CS — Linked to One Practitioner</div>
                <div class="ob-role-card-desc">Free account. You've been designated by a practitioner. Serves only your linked practitioner. No public profile. Upgrade anytime to Business CS.</div>
                <div class="ob-role-card-badge free">
                  <AegisIcon name="check" :size="9" />Free via invitation
                </div>
              </div>
              <div class="ob-role-card-action"><AegisIcon name="chevron-right" :size="16" /></div>
            </div>
          </div>

          <button
            type="button"
            class="btn btn-primary ob-btn-full"
            :disabled="!form.cs_path"
            @click="currentStep = 2"
          >Continue</button>
        </div>

        <!-- ══ STEP 1b: BP TYPE ══ -->
        <div v-show="currentStep === 1 && form.role === 'business_partner'" class="ob-step">
          <button type="button" class="ob-back-link" @click="currentStep = 0">
            <AegisIcon name="chevron-left" :size="14" /> Back
          </button>
          <div class="ob-step-header">
            <div class="ob-step-eyebrow">Step 2 of {{ totalSteps }} — Business Type</div>
            <h2 class="ob-step-title">Your Business Type</h2>
            <p class="ob-step-subtitle">This determines your dashboard layout and contract workflow. Pricing is the same for both types.</p>
          </div>

          <div class="ob-role-cards">
            <div
              class="ob-role-card"
              :class="{ selected: form.bp_type === 'freelancer' }"
              @click="form.bp_type = 'freelancer'"
            >
              <div class="ob-role-card-icon"><AegisIcon name="user" :size="20" /></div>
              <div class="ob-role-card-body">
                <div class="ob-role-card-title">Freelancer</div>
                <div class="ob-role-card-desc">Solo practitioner. Personal SSN + 1099. Personal availability calendar. Work submitted directly.</div>
                <div class="ob-role-card-badge"><AegisIcon name="check" :size="9" />Solo · $69/mo</div>
              </div>
              <div class="ob-role-card-action"><AegisIcon name="chevron-right" :size="16" /></div>
            </div>

            <div
              class="ob-role-card"
              :class="{ selected: form.bp_type === 'agency' }"
              @click="form.bp_type = 'agency'"
            >
              <div class="ob-role-card-icon"><AegisIcon name="users" :size="20" /></div>
              <div class="ob-role-card-body">
                <div class="ob-role-card-title">Agency</div>
                <div class="ob-role-card-desc">Multi-person firm. EIN + team management. Assign team members per milestone. Owner reviews before submission.</div>
                <div class="ob-role-card-badge"><AegisIcon name="check" :size="9" />Team · $69/mo</div>
              </div>
              <div class="ob-role-card-action"><AegisIcon name="chevron-right" :size="16" /></div>
            </div>
          </div>

          <button
            type="button"
            class="btn btn-primary ob-btn-full"
            :disabled="!form.bp_type"
            @click="currentStep = 2"
          >Continue</button>
        </div>

        <!-- ══ STEP 1c: SS GATE (invite-only wall) ══ -->
        <div v-show="currentStep === 1 && form.role === 'support_steward'" class="ob-step">
          <button type="button" class="ob-back-link" @click="currentStep = 0">
            <AegisIcon name="chevron-left" :size="14" /> Back
          </button>
          <div class="ob-step-header">
            <div class="ob-step-eyebrow">Support Steward Access</div>
            <h2 class="ob-step-title">Invitation Required</h2>
          </div>

          <div class="ob-invite-gate">
            <div class="ob-invite-gate-icon">
              <AegisIcon name="mail" :size="32" />
            </div>
            <div class="ob-invite-gate-body">
              <p>Support Steward accounts are <strong>invitation-only</strong> — there is no public signup.</p>
              <p class="ob-gate-para">Support Stewards are part of a Practitioner's operational team, handling administrative, monitoring, and coordination tasks under their authorization.</p>
              <p class="ob-gate-para">For HIPAA compliance and audit integrity, the practitioner who's bringing you on must define your role and access permissions before your account can be created.</p>
              <p class="ob-gate-para"><strong>Expecting an invitation?</strong> Check your email (including spam). If you haven't received one, ask your practitioner to send it from their Support Stewards page.</p>
              <p class="ob-gate-para"><strong>Looking for a different role?</strong> If you're an independent professional serving multiple practitioners, you're likely looking for the <a href="#" class="ob-gate-link" @click.prevent="selectRole('continuity_steward'); currentStep = 0">Continuity Steward</a> role.</p>
            </div>
          </div>

          <button type="button" class="btn btn-outline ob-btn-full" @click="currentStep = 0">Choose a different role</button>
        </div>

        <!-- ══ STEP 2: ACCOUNT CREATION ══ -->
        <div v-show="currentStep === 2" class="ob-step">
          <button type="button" class="ob-back-link" @click="backFromAccount">
            <AegisIcon name="chevron-left" :size="14" /> Back
          </button>

          <div class="ob-step-header">
            <div class="ob-step-eyebrow">Step {{ totalSteps > 2 ? '2' : '2' }} of {{ totalSteps }} — Create Account</div>
            <h2 class="ob-step-title">Create Your Account</h2>
            <p class="ob-step-subtitle">{{ roleSubtitle }}</p>
          </div>

          <!-- Role summary chip -->
          <div class="ob-role-summary">
            <AegisIcon :name="roleIcon" :size="14" />
            {{ roleSummaryLabel }}
            <button type="button" class="ob-role-summary-change" @click="currentStep = 0">Change</button>
          </div>

          <form @submit.prevent="submit" novalidate>

            <div class="form-group">
              <label class="form-label" for="display_name">Full Name</label>
              <input
                id="display_name"
                v-model="form.display_name"
                type="text"
                class="form-input"
                :class="{ 'is-error': fieldError('display_name') }"
                autocomplete="name"
                autofocus
                placeholder="Enter your full name"
                @blur="v$.display_name.$touch()"
              />
              <div v-if="fieldError('display_name')" class="form-error">{{ fieldError('display_name') }}</div>
            </div>

            <div class="form-group">
              <label class="form-label" for="reg-email">Email Address</label>
              <input
                id="reg-email"
                v-model="form.email"
                type="email"
                class="form-input"
                :class="{ 'is-error': fieldError('email') }"
                autocomplete="email"
                placeholder="your@email.com"
                @blur="v$.email.$touch()"
              />
              <div v-if="fieldError('email')" class="form-error">{{ fieldError('email') }}</div>
            </div>

            <div class="form-group">
              <label class="form-label" for="reg-password">Password</label>
              <div class="ob-password-wrap">
                <input
                  id="reg-password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  class="form-input"
                  :class="{ 'is-error': fieldError('password') }"
                  autocomplete="new-password"
                  placeholder="Create a strong password"
                  @input="checkPasswordStrength"
                  @blur="v$.password.$touch()"
                />
                <button type="button" class="ob-password-toggle" @click="showPassword = !showPassword">
                  <AegisIcon :name="showPassword ? 'eye-off' : 'eye'" :size="15" />
                </button>
              </div>
              <div v-if="fieldError('password')" class="form-error">{{ fieldError('password') }}</div>
              <div class="ob-password-reqs">
                <div class="ob-req-item" :class="{ valid: reqs.length, invalid: form.password && !reqs.length }">
                  <AegisIcon :name="reqs.length ? 'check-circle' : 'x-circle'" :size="11" />8+ characters
                </div>
                <div class="ob-req-item" :class="{ valid: reqs.uppercase, invalid: form.password && !reqs.uppercase }">
                  <AegisIcon :name="reqs.uppercase ? 'check-circle' : 'x-circle'" :size="11" />Uppercase
                </div>
                <div class="ob-req-item" :class="{ valid: reqs.number, invalid: form.password && !reqs.number }">
                  <AegisIcon :name="reqs.number ? 'check-circle' : 'x-circle'" :size="11" />Number
                </div>
                <div class="ob-req-item" :class="{ valid: reqs.special, invalid: form.password && !reqs.special }">
                  <AegisIcon :name="reqs.special ? 'check-circle' : 'x-circle'" :size="11" />Special char
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="reg-confirm">Confirm Password</label>
              <div class="ob-password-wrap">
                <input
                  id="reg-confirm"
                  v-model="form.password_confirmation"
                  :type="showConfirm ? 'text' : 'password'"
                  class="form-input"
                  :class="{ 'is-error': fieldError('password_confirmation') }"
                  autocomplete="new-password"
                  placeholder="Re-enter your password"
                  @blur="v$.password_confirmation.$touch()"
                />
                <button type="button" class="ob-password-toggle" @click="showConfirm = !showConfirm">
                  <AegisIcon :name="showConfirm ? 'eye-off' : 'eye'" :size="15" />
                </button>
              </div>
              <div v-if="fieldError('password_confirmation')" class="form-error">{{ fieldError('password_confirmation') }}</div>
            </div>

            <!-- Invitation code — Invited CS only -->
            <div v-if="form.role === 'continuity_steward' && form.cs_path === 'invited'" class="form-group">
              <label class="form-label" for="invitation_code">Invitation Code</label>
              <input
                id="invitation_code"
                v-model="form.invitation_code"
                type="text"
                class="form-input"
                :class="{ 'is-error': fieldError('invitation_code') }"
                placeholder="Enter the code from your practitioner's invitation email"
                @blur="v$.invitation_code.$touch()"
              />
              <div v-if="fieldError('invitation_code')" class="form-error">{{ fieldError('invitation_code') }}</div>
              <div class="form-hint">Don't have a code? Ask your practitioner to invite you from their Support Stewards or Continuity Stewards page.</div>
            </div>

            <label class="auth-remember">
              <input v-model="agreeTerms" type="checkbox" class="auth-checkbox" />
              <span class="auth-checkbox-label">I have read and agree to the Terms of Service and Privacy Policy.</span>
            </label>

            <label class="auth-remember ob-terms-row">
              <input v-model="emailOptIn" type="checkbox" class="auth-checkbox" />
              <span class="auth-checkbox-label">
                I agree to receive platform updates and communications
                <small>You can unsubscribe at any time</small>
              </span>
            </label>

            <button
              type="submit"
              class="btn btn-primary ob-btn-full"
              :disabled="form.processing || !agreeTerms"
            >{{ form.processing ? 'Creating Account…' : 'Create Account' }}</button>

          </form>

          <div class="ob-signin-row">Already have an account? <a :href="route('login')" class="ob-signin-link">Sign In</a></div>
        </div>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { useVuelidate } from '@vuelidate/core'
import { required, email, minLength, maxLength, sameAs, requiredIf, helpers } from '@vuelidate/validators'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const currentStep  = ref(0)
const showWws      = ref(false)
const showPassword = ref(false)
const showConfirm  = ref(false)
const agreeTerms   = ref(false)
const emailOptIn   = ref(false)
const year         = new Date().getFullYear()

const reqs = reactive({ length: false, uppercase: false, number: false, special: false })

const form = useForm({
  display_name:          '',
  email:                 '',
  password:              '',
  password_confirmation: '',
  role:                  '',
  bp_type:               null,
  cs_path:               null,
  invitation_code:       '',
})

// ── Role definitions ──────────────────────────────────────────────────
const roles = [
  {
    value: 'practitioner',
    label: 'Practitioner Partner',
    icon:  'activity',
    desc:  'Doctor, therapist, specialist, and health professionals building and protecting their practice.',
    badge: 'Subscription required',
    badgeVariant: '',
  },
  {
    value: 'business_partner',
    label: 'Business Partner',
    icon:  'briefcase',
    desc:  'Billing, legal, consultants, and business service providers working with health professionals.',
    badge: 'Subscription required',
    badgeVariant: '',
  },
  {
    value: 'continuity_steward',
    label: 'Continuity Steward',
    icon:  'shield',
    desc:  'Practice succession specialists and licensed professionals supporting practitioner continuity.',
    badge: 'Free via invitation or $49/mo',
    badgeVariant: 'mixed',
  },
  {
    value: 'support_steward',
    label: 'Support Steward',
    icon:  'heart',
    desc:  'Administrative staff, personal representatives, and designated support for practitioners.',
    badge: 'Invitation only · Free',
    badgeVariant: 'free',
  },
]

// ── Step count: 3 steps for roles with a sub-path, 2 for direct (practitioner) ──
const totalSteps = computed(() => {
  if (!form.role) return 3
  if (form.role === 'practitioner') return 2
  return 3
})

// ── Left panel dynamic content ────────────────────────────────────────
const panelContent = {
  default: {
    eyebrow:  'Practitioner Platform',
    title:    'Protect Your Practice. Connect Your Network.',
    body:     'Aegis is built for health and wellness practitioners — and the professionals who support them.',
    features: [
      { icon: 'shield', title: 'Continuity of Care Planning', desc: 'Emergency preparedness and practice succession built in.' },
      { icon: 'users',  title: 'Professional Network',        desc: 'Connect with practitioners, stewards, and business partners.' },
      { icon: 'lock',   title: 'Secure & HIPAA-Ready',        desc: 'End-to-end encrypted messaging and document management.' },
    ],
  },
  practitioner: {
    eyebrow:  'Practitioner Partner',
    title:    'Your practice has a plan. Your patients have protection.',
    body:     'The Continuity Plan Builder takes 15 minutes. Once signed, your designated stewards know exactly what to do.',
    features: [
      { icon: 'shield-check', title: 'Continuity Plan Builder',  desc: 'Build your plan once. Your stewards execute it when needed.' },
      { icon: 'users',        title: 'Designate Stewards',       desc: 'Assign Continuity and Support Stewards with precise permissions.' },
      { icon: 'lock-3',       title: 'Secure Document Vault',    desc: 'Store credentials, client rosters, and sensitive documents.' },
    ],
  },
  business_partner: {
    eyebrow:  'Business Partner',
    title:    'Connect with practitioners who need your expertise.',
    body:     'The Aegis marketplace connects you directly with healthcare practitioners. Proposals, contracts, and payouts — all in one place.',
    features: [
      { icon: 'search',   title: 'Browse Job Postings',     desc: 'Browse practitioner job postings and send proposals.' },
      { icon: 'receipt',  title: 'Milestone Payments',      desc: 'Stripe Connect direct payouts. Aegis never holds funds.' },
      { icon: 'briefcase',title: 'Agency or Freelancer',    desc: 'Pick the account type that fits how you work.' },
    ],
  },
  continuity_steward: {
    eyebrow:  'Continuity Steward',
    title:    'You\'re the backup plan. Aegis is your command center.',
    body:     'When a critical incident occurs, Aegis surfaces everything you need: the plan, the task list, and the vault.',
    features: [
      { icon: 'clipboard-list', title: 'Task Management',     desc: 'Pre-written task lists activate the moment you verify an incident.' },
      { icon: 'lock',           title: 'Vault Access',        desc: 'Sealed until you verify. Then everything you need, nothing you don\'t.' },
      { icon: 'shield',         title: 'Aegis Verified',      desc: 'Build trust with practitioners through credential verification.' },
    ],
  },
  support_steward: {
    eyebrow:  'Support Steward',
    title:    'You watch. You trigger. Aegis does the rest.',
    body:     'Your role is the most important in the early moments: you\'re the one who notices something is wrong.',
    features: [
      { icon: 'bell',    title: 'Monitor & Trigger',    desc: 'Spot trouble and file a critical incident report in minutes.' },
      { icon: 'users',   title: 'Coordinate Response',  desc: 'Your report wakes up the Continuity Steward immediately.' },
      { icon: 'activity',title: 'Audit Trail',          desc: 'Every action is logged for legal and estate use.' },
    ],
  },
}

const leftEyebrow = computed(() => (panelContent[form.role] ?? panelContent.default).eyebrow)
const leftTitle   = computed(() => (panelContent[form.role] ?? panelContent.default).title)
const leftBody    = computed(() => (panelContent[form.role] ?? panelContent.default).body)
const leftFeatures= computed(() => (panelContent[form.role] ?? panelContent.default).features)

// ── Role metadata ──────────────────────────────────────────────────────
const roleIconMap = {
  practitioner: 'activity', business_partner: 'briefcase',
  continuity_steward: 'shield', support_steward: 'heart',
}
const roleIcon = computed(() => roleIconMap[form.role] ?? 'user')

const roleSummaryLabel = computed(() => {
  if (form.role === 'practitioner')       return 'Practitioner Partner'
  if (form.role === 'business_partner')   return `Business Partner — ${form.bp_type === 'agency' ? 'Agency' : 'Freelancer'}`
  if (form.role === 'continuity_steward') return `Continuity Steward — ${form.cs_path === 'invited' ? 'Invited (Free)' : 'Business ($49/mo)'}`
  if (form.role === 'support_steward')    return 'Support Steward'
  return ''
})

const roleSubtitle = computed(() => ({
  practitioner:       'Licensed clinician building and protecting their practice.',
  business_partner:   'Professional services provider for healthcare practitioners.',
  continuity_steward: form.cs_path === 'invited'
    ? 'You\'ve been invited by a practitioner. Enter your invitation code below.'
    : 'Independent paid CS account. Serve 2–40 practitioners.',
  support_steward:    'Monitoring and reporting role for a practitioner.',
}[form.role] ?? ''))

// ── Step navigation ────────────────────────────────────────────────────
function selectRole(value) {
  form.role     = value
  form.bp_type  = null
  form.cs_path  = null
}

function advanceFromRole() {
  if (!form.role) return
  v$.value.role.$touch()
  if (v$.value.role.$error) return
  // Practitioner skips sub-path step
  if (form.role === 'practitioner') {
    currentStep.value = 2
  } else {
    currentStep.value = 1
  }
}

function backFromAccount() {
  if (form.role === 'practitioner') {
    currentStep.value = 0
  } else {
    currentStep.value = 1
  }
}

// ── Validation ─────────────────────────────────────────────────────────
const rules = computed(() => ({
  display_name: {
    required: helpers.withMessage('Full name is required.', required),
    max:      helpers.withMessage('Name must be 100 characters or less.', maxLength(100)),
  },
  email: {
    required: helpers.withMessage('Email is required.', required),
    email:    helpers.withMessage('Enter a valid email address.', email),
  },
  password: {
    required:  helpers.withMessage('Password is required.', required),
    min:       helpers.withMessage('Password must be at least 8 characters.', minLength(8)),
    uppercase: helpers.withMessage('Password must contain an uppercase letter.', helpers.regex(/[A-Z]/)),
    number:    helpers.withMessage('Password must contain a number.',            helpers.regex(/[0-9]/)),
    special:   helpers.withMessage('Password must contain a special character.', helpers.regex(/[^A-Za-z0-9]/)),
  },
  password_confirmation: {
    required: helpers.withMessage('Please confirm your password.', required),
    sameAs:   helpers.withMessage('Passwords do not match.', sameAs(computed(() => form.password))),
  },
  role: {
    required: helpers.withMessage('Select a role to continue.', required),
  },
  invitation_code: {
    requiredIf: helpers.withMessage(
      'Invitation code is required for invited Continuity Stewards.',
      requiredIf(() => form.role === 'continuity_steward' && form.cs_path === 'invited')
    ),
  },
}))

const v$ = useVuelidate(rules, form)

function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message
  if (form.errors[field]) return form.errors[field]
  return null
}

function checkPasswordStrength() {
  const pw = form.password
  reqs.length    = pw.length >= 8
  reqs.uppercase = /[A-Z]/.test(pw)
  reqs.number    = /[0-9]/.test(pw)
  reqs.special   = /[^A-Za-z0-9]/.test(pw)
}

// ── Submit ─────────────────────────────────────────────────────────────
async function submit() {
  const valid = await v$.value.$validate()
  if (!valid) {
    toast.error('Please fix the highlighted fields.')
    return
  }
  if (!agreeTerms.value) {
    toast.error('You must agree to the Terms of Service.')
    return
  }

  form.post(route('register.store'), {
    onSuccess: () => {
      router.visit(route('verification.notice'), { replace: true })
    },
    onError: () => toast.error('Could not create account. Please check the form.'),
    onFinish: () => {
      form.reset('password', 'password_confirmation')
      v$.value.$reset()
    },
  })
}

// ── Who We Serve sections ──────────────────────────────────────────────
const wwsSections = [
  {
    title: 'Health & Wellbeing Practitioners',
    items: [
      'Therapists, Mental Health Counselors, Psychologists, Psychiatrists',
      'Clinical Social Workers, Marriage & Family Therapists',
      'Prescribers (MD, DO, NP, PA), Nutritionists, Dietitians',
      'Massage Therapists, Functional Medicine Providers, Chiropractors',
      'Doulas, Midwives, Health Coaches, Wellness Coaches, Yoga Therapists',
      'Art, Music, Dance/Movement, and Recreational Therapists',
    ],
  },
  {
    title: 'Business Partners',
    items: [
      'Accounting & Billing specialists',
      'Credentialing consultants and Legal advisors',
      'Marketing, Web Design, and IT Support professionals',
    ],
  },
  {
    title: 'Continuity Stewards',
    items: [
      'Estate lawyers specializing in practice succession',
      'Licensed professionals with practitioner expertise',
    ],
  },
  {
    title: 'Support Stewards',
    items: [
      'Designated Support Representatives',
      'Administrative staff and personal assistants',
    ],
  },
]
</script>

<style scoped>
/* ══ SHELL ══ */
.ob-layout { display:flex; width:100%; height:100vh; overflow:hidden; }

/* ── LEFT PANEL ── */
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
.ob-footer-link { color:rgba(255,255,255,0.55); text-decoration:underline; background:none; border:none; padding:0; cursor:pointer; font-size:11px; font-family:var(--font-sans); }

/* ── RIGHT PANEL ── */
.ob-panel-right { flex:1; display:flex; flex-direction:column; overflow-y:auto; overflow-x:hidden; background-color:var(--surface); height:100vh; }
.ob-panel-right-inner { flex:1; display:flex; flex-direction:column; padding:48px 52px; max-width:620px; width:100%; margin:0 auto; }
.ob-step { width:100%; }

/* ── STEP HEADER ── */
.ob-step-header { margin-bottom:28px; }
.ob-step-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.8px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.ob-step-title { font-family:var(--font-serif); font-size:clamp(22px,2vw + 0.6rem,28px); font-weight:700; color:var(--text); line-height:1.25; margin-bottom:8px; }
.ob-step-subtitle { font-size:13px; color:var(--text-2); line-height:1.55; }

/* ── BACK LINK ── */
.ob-back-link { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:600; color:var(--text-2); background:none; border:none; padding:6px 0; cursor:pointer; margin-bottom:28px; transition:color var(--transition); }
.ob-back-link:hover { color:var(--gold-dark); }

/* ── ROLE CARDS ── */
.ob-role-cards { display:flex; flex-direction:column; gap:12px; margin-bottom:24px; }
.ob-role-card { border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px 20px; cursor:pointer; transition:all var(--transition); background:var(--surface); display:flex; align-items:flex-start; gap:16px; }
.ob-role-card:hover { border-color:var(--gold); transform:translateY(-1px); }
.ob-role-card.selected { border-color:var(--gold); background:rgba(196,169,106,0.04); }
.ob-role-card-icon { width:42px; height:42px; background:rgba(196,169,106,0.1); border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--gold-dark); transition:all var(--transition); }
.ob-role-card.selected .ob-role-card-icon { background:var(--gold-dark); color:var(--text-inverted); }
.ob-role-card-body { flex:1; min-width:0; }
.ob-role-card-title { font-family:var(--font-serif); font-size:15px; font-weight:700; color:var(--text); margin-bottom:3px; }
.ob-role-card-desc { font-size:12px; color:var(--text-2); line-height:1.5; margin-bottom:8px; }
.ob-role-card-badge { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:600; padding:3px 9px; border-radius:var(--radius-full); background:rgba(196,169,106,0.1); color:var(--gold-dark); border:1px solid rgba(196,169,106,0.25); }
.ob-role-card-badge.free { background:rgba(76,175,125,0.1); color:var(--green); border-color:rgba(76,175,125,0.25); }
.ob-role-card-badge.mixed { background:rgba(196,169,106,0.06); color:var(--text-2); border-color:var(--border); }
.ob-role-card-action { flex-shrink:0; display:flex; align-items:center; padding-top:8px; color:var(--text-4); transition:color var(--transition); }
.ob-role-card:hover .ob-role-card-action, .ob-role-card.selected .ob-role-card-action { color:var(--gold-dark); }

/* ── SS GATE ── */
.ob-invite-gate { background:var(--surface-2); border:1px solid var(--border); border-left:2px solid var(--gold); border-radius:var(--radius-lg); padding:22px 24px; margin-bottom:24px; display:flex; gap:20px; align-items:flex-start; }
.ob-invite-gate-icon { width:56px; height:56px; background:rgba(196,169,106,0.1); border-radius:var(--radius); display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--gold-dark); }
.ob-invite-gate-body { flex:1; font-size:13px; color:var(--text-2); line-height:1.65; }
.ob-invite-gate-body strong { color:var(--text); }
.ob-gate-link { color:var(--gold-dark); text-decoration:none; font-weight:600; }
.ob-gate-link:hover { text-decoration:underline; }

/* ── ROLE SUMMARY CHIP ── */
.ob-role-summary { display:inline-flex; align-items:center; gap:8px; background:rgba(196,169,106,0.07); border:1px solid rgba(196,169,106,0.25); border-radius:var(--radius-full); padding:6px 12px 6px 10px; font-size:12px; color:var(--text-2); margin-bottom:22px; }
.ob-role-summary-change { background:none; border:none; font-size:11px; font-weight:600; color:var(--gold-dark); cursor:pointer; padding:0 0 0 6px; font-family:var(--font-sans); text-decoration:underline; }

/* ── FORM ── */
.ob-password-wrap { position:relative; }
.ob-password-wrap .form-input { padding-right:42px; }
.ob-password-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; padding:4px; cursor:pointer; color:var(--text-2); display:flex; align-items:center; transition:color var(--transition); }
.ob-password-toggle:hover { color:var(--gold-dark); }
.ob-password-reqs { display:flex; flex-wrap:wrap; gap:6px 14px; margin-top:10px; }
.ob-req-item { display:inline-flex; align-items:center; gap:5px; font-size:11px; color:var(--text-4); transition:color var(--transition); }
.ob-req-item.valid { color:var(--green); }
.ob-req-item.invalid { color:var(--red); }
.form-hint { font-size:12px; color:var(--text-4); margin-top:5px; line-height:1.5; }
.auth-remember { display:flex; align-items:flex-start; gap:10px; margin-bottom:10px; cursor:pointer; }
.auth-checkbox { margin-top:2px; flex-shrink:0; }
.auth-checkbox-label { font-size:12px; color:var(--text-2); line-height:1.5; }
.ob-terms-row { margin-bottom:22px; }
.ob-terms-row .auth-checkbox-label small { display:block; font-size:11px; color:var(--text-4); margin-top:2px; }

/* ── BUTTONS ── */
.ob-btn-full { width:100%; padding:12px 22px; border-radius:var(--radius-full); font-size:13px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:6px; }

/* ── SIGN-IN ROW ── */
.ob-signin-row { text-align:center; margin-top:24px; font-size:12px; color:var(--text-2); }
.ob-signin-link { color:var(--gold-dark); text-decoration:none; font-weight:600; }
.ob-signin-link:hover { text-decoration:underline; }

/* ── WHO WE SERVE MODAL ── */
.wws-section { margin-bottom:20px; }
.wws-section-title { font-size:11px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.wws-list { list-style:none; padding:0; margin:0; }
.wws-list li { font-size:12px; color:var(--text-2); padding:4px 0 4px 14px; position:relative; line-height:1.5; }
.wws-list li::before { content:'·'; position:absolute; left:0; color:var(--gold); font-weight:700; }

/* ── RESPONSIVE ── */
@media (max-height: 680px) { .ob-panel-features { display:none; } }
@media (max-width: 900px) { .ob-panel-left { width:36%; padding:36px 32px; } .ob-panel-right-inner { padding:40px 36px; } }
@media (max-width: 720px) {
  .ob-layout { flex-direction:column; height:auto; min-height:100vh; overflow:visible; }
  .ob-panel-left { width:100%; height:auto; padding:32px 28px; }
  .ob-panel-features { display:none; }
  .ob-panel-right { height:auto; overflow:visible; }
  .ob-panel-right-inner { padding:32px 24px; }
}
.ob-gate-para { margin-top:12px; }
</style>
