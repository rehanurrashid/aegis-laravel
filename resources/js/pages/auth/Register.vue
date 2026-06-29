<!--
  pages/auth/Register.vue — create a new Aegis account.

  Layout: None — self-contained split-panel shell (AuthLayout cannot host this design)
  Source: onboarding.php — left panel + step 1 (role) + step 3 (account creation)
  Route:  POST register.store
-->
<template>
  <Head title="Create Account — Aegis" />

  <!-- Who We Serve modal -->
  <AegisModal v-model="showWws" title="Who We Serve" size="md">
    <div class="wws-section">
      <div class="wws-section-title">Health &amp; Wellbeing Practitioners</div>
      <ul class="wws-list">
        <li>Therapists, Mental Health Counselors, Psychologists, Psychiatrists</li>
        <li>Clinical Social Workers, Marriage &amp; Family Therapists</li>
        <li>Prescribers (MD, DO, NP, PA), Nutritionists, Dietitians</li>
        <li>Massage Therapists, Functional Medicine Providers, Chiropractors</li>
        <li>Doulas (Birth, Postpartum, End of Life, Full Spectrum), Midwives</li>
        <li>Health Coaches, Wellness Coaches, Yoga Therapists</li>
        <li>Art, Music, Dance/Movement, and Recreational Therapists</li>
      </ul>
    </div>
    <div class="wws-section">
      <div class="wws-section-title">Business Partners</div>
      <ul class="wws-list">
        <li>Accounting &amp; Billing specialists</li>
        <li>Credentialing consultants and Legal advisors</li>
        <li>Marketing, Web Design, and IT Support professionals</li>
      </ul>
    </div>
    <div class="wws-section">
      <div class="wws-section-title">Continuity Stewards</div>
      <ul class="wws-list">
        <li>Estate lawyers specializing in practice succession</li>
        <li>Licensed professionals with practitioner expertise</li>
      </ul>
    </div>
    <div class="wws-section">
      <div class="wws-section-title">Support Steward</div>
      <ul class="wws-list">
        <li>Designated Support Representatives</li>
        <li>Administrative staff and personal assistants</li>
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
        <div class="ob-panel-left-eyebrow">Practitioner Platform</div>
        <h1 class="ob-panel-left-title">Protect Your Practice. Connect Your Network.</h1>
        <p class="ob-panel-left-body">Aegis is a comprehensive platform designed to streamline referrals, manage professional networks, and facilitate secure communication between practitioners and their partners.</p>

        <div class="ob-panel-features">
          <div class="ob-panel-feature">
            <div class="ob-panel-feature-icon">
              <AegisIcon name="shield" :size="15" />
            </div>
            <div class="ob-panel-feature-text">
              <strong>Continuity of Care Planning</strong>
              Emergency preparedness and practice succession built in.
            </div>
          </div>
          <div class="ob-panel-feature">
            <div class="ob-panel-feature-icon">
              <AegisIcon name="users" :size="15" />
            </div>
            <div class="ob-panel-feature-text">
              <strong>Professional Network</strong>
              Connect with practitioners, Continuity Stewards, and business partners.
            </div>
          </div>
          <div class="ob-panel-feature">
            <div class="ob-panel-feature-icon">
              <AegisIcon name="lock" :size="15" />
            </div>
            <div class="ob-panel-feature-text">
              <strong>Secure &amp; HIPAA-Ready</strong>
              End-to-end encrypted messaging and document management.
            </div>
          </div>
        </div>

        <!-- Progress pips -->
        <div class="ob-progress-track">
          <div
            v-for="i in 2"
            :key="i"
            class="ob-progress-pip"
            :class="{
              'active': i - 1 === currentStep,
              'done':   i - 1 < currentStep,
            }"
          />
        </div>
      </div>

      <div class="ob-panel-left-footer">
        <p>
          © {{ year }} Aegis Platform. All rights reserved.<br />
          <button type="button" class="ob-footer-link" @click="showWws = true">Who We Serve</button>
          &nbsp;·&nbsp;
          <a :href="route('about')"   class="ob-footer-link">About</a>
          &nbsp;·&nbsp;
          <a :href="route('pricing')" class="ob-footer-link">Pricing</a>
          &nbsp;·&nbsp;
          <a :href="route('contact')" class="ob-footer-link">Contact</a>
        </p>
      </div>
    </div>

    <!-- ══════════════ RIGHT PANEL ══════════════ -->
    <div class="ob-panel-right">
      <div class="ob-panel-right-inner">

        <!-- ══ STEP 0: ROLE SELECTION ══ -->
        <div v-show="currentStep === 0" class="ob-step">
          <div class="ob-step-header">
            <div class="ob-step-eyebrow">Step 1 of 2</div>
            <h2 class="ob-step-title">Select Your Role</h2>
            <p class="ob-step-subtitle">Choose the role that best describes how you'll use Aegis. This personalizes your onboarding and dashboard.</p>
          </div>

          <div class="ob-role-cards">

            <div
              class="ob-role-card"
              :class="{ selected: form.role === 'practitioner' }"
              @click="selectRole('practitioner')"
            >
              <div class="ob-role-card-icon">
                <AegisIcon name="activity" :size="20" />
              </div>
              <div class="ob-role-card-body">
                <div class="ob-role-card-title">Practitioner Partner</div>
                <div class="ob-role-card-desc">Doctor, therapist, specialist, and health professionals building and protecting their practice.</div>
                <div class="ob-role-card-badge">
                  <AegisIcon name="check" :size="9" />
                  Subscription required
                </div>
              </div>
              <div class="ob-role-card-action">
                <AegisIcon name="chevron-right" :size="16" />
              </div>
            </div>

            <div
              class="ob-role-card"
              :class="{ selected: form.role === 'business_partner' }"
              @click="selectRole('business_partner')"
            >
              <div class="ob-role-card-icon">
                <AegisIcon name="briefcase" :size="20" />
              </div>
              <div class="ob-role-card-body">
                <div class="ob-role-card-title">Business Partner</div>
                <div class="ob-role-card-desc">Billing, legal, consultants, and business service providers working with health professionals.</div>
                <div class="ob-role-card-badge">
                  <AegisIcon name="check" :size="9" />
                  Subscription required
                </div>
              </div>
              <div class="ob-role-card-action">
                <AegisIcon name="chevron-right" :size="16" />
              </div>
            </div>

            <div
              class="ob-role-card"
              :class="{ selected: form.role === 'continuity_steward' }"
              @click="selectRole('continuity_steward')"
            >
              <div class="ob-role-card-icon">
                <AegisIcon name="shield" :size="20" />
              </div>
              <div class="ob-role-card-body">
                <div class="ob-role-card-title">Continuity Steward</div>
                <div class="ob-role-card-desc">Practice succession specialists and licensed professionals supporting practitioner continuity.</div>
                <div class="ob-role-card-badge free">
                  <AegisIcon name="check" :size="9" />
                  Free via invitation
                </div>
              </div>
              <div class="ob-role-card-action">
                <AegisIcon name="chevron-right" :size="16" />
              </div>
            </div>

            <div
              class="ob-role-card"
              :class="{ selected: form.role === 'support_steward' }"
              @click="selectRole('support_steward')"
            >
              <div class="ob-role-card-icon">
                <AegisIcon name="heart" :size="20" />
              </div>
              <div class="ob-role-card-body">
                <div class="ob-role-card-title">Support Steward</div>
                <div class="ob-role-card-desc">Administrative staff, personal representatives, and designated support for practitioners.</div>
                <div class="ob-role-card-badge free">
                  <AegisIcon name="check" :size="9" />
                  Invitation only
                </div>
              </div>
              <div class="ob-role-card-action">
                <AegisIcon name="chevron-right" :size="16" />
              </div>
            </div>

          </div>

          <!-- Business Partner type -->
          <div v-if="form.role === 'business_partner'" class="ob-bp-type">
            <label class="form-label">Business type</label>
            <div class="tabs-segmented" style="margin-bottom: 0; margin-top: 6px;">
              <button
                type="button"
                class="tab-pill"
                :class="{ active: form.bp_type === 'freelancer' }"
                @click="form.bp_type = 'freelancer'"
              >Freelancer</button>
              <button
                type="button"
                class="tab-pill"
                :class="{ active: form.bp_type === 'agency' }"
                @click="form.bp_type = 'agency'"
              >Agency</button>
            </div>
            <div v-if="fieldError('bp_type')" class="form-error">{{ fieldError('bp_type') }}</div>
          </div>

          <div v-if="fieldError('role')" class="form-error">{{ fieldError('role') }}</div>

          <button
            type="button"
            class="btn btn-primary ob-btn-full"
            :disabled="!canAdvance"
            @click="currentStep = 1"
          >Continue</button>

          <div class="ob-signin-row">Already have an account? <a :href="route('login')" class="ob-signin-link">Sign In</a></div>
        </div>

        <!-- ══ STEP 1: ACCOUNT CREATION ══ -->
        <div v-show="currentStep === 1" class="ob-step">
          <button type="button" class="ob-back-link" @click="currentStep = 0">
            <AegisIcon name="chevron-left" :size="14" />
            Back to Role Selection
          </button>

          <div class="ob-step-header">
            <div class="ob-step-eyebrow">Step 2 of 2</div>
            <h2 class="ob-step-title">Create Your Account</h2>
            <p class="ob-step-subtitle">{{ roleSubtitle }}</p>
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
                  @blur="v$.password.$touch()"
                  @input="checkPasswordStrength"
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

// UX password strength indicator — visual only, mirrors Vuelidate rules
const reqs = reactive({ length: false, uppercase: false, number: false, special: false })

const form = useForm({
  display_name:          '',
  email:                 '',
  password:              '',
  password_confirmation: '',
  role:                  '',
  bp_type:               null,
})

// ── Vuelidate rules ───────────────────────────────────────────────────
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
    number:    helpers.withMessage('Password must contain a number.', helpers.regex(/[0-9]/)),
    special:   helpers.withMessage('Password must contain a special character.', helpers.regex(/[^A-Za-z0-9]/)),
  },
  password_confirmation: {
    required: helpers.withMessage('Please confirm your password.', required),
    sameAs:   helpers.withMessage('Passwords do not match.', sameAs(computed(() => form.password))),
  },
  role: {
    required: helpers.withMessage('Select a role to continue.', required),
  },
  bp_type: {
    requiredIf: helpers.withMessage(
      'Choose freelancer or agency.',
      requiredIf(() => form.role === 'business_partner')
    ),
  },
}))

const v$ = useVuelidate(rules, form)

// ── Unified error helper ──────────────────────────────────────────────
function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message
  if (form.errors[field]) return form.errors[field]
  return null
}

const roleSubtitle = computed(() => ({
  practitioner:       'Join as a Practitioner Partner',
  business_partner:   'Join as a Business Partner',
  continuity_steward: 'Join as a Continuity Steward (Business Account)',
  support_steward:    'Join as a Support Steward',
}[form.role] ?? 'Join the Aegis platform to connect, collaborate, and grow.'))

const canAdvance = computed(() => {
  if (!form.role) return false
  if (form.role === 'business_partner' && !form.bp_type) return false
  return true
})

function selectRole(value) {
  form.role    = value
  form.bp_type = null
  v$.value.role.$touch()
}

function checkPasswordStrength() {
  const pw = form.password
  reqs.length    = pw.length >= 8
  reqs.uppercase = /[A-Z]/.test(pw)
  reqs.number    = /[0-9]/.test(pw)
  reqs.special   = /[^A-Za-z0-9]/.test(pw)
}

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
    onError:   () => toast.error('Could not create account. Please check the form.'),
    onFinish:  () => {
      form.reset('password', 'password_confirmation')
      v$.value.$reset()
    },
  })
}
</script>

<style scoped>
/* ══════════════════════════════════════
   SPLIT-PANEL SHELL
══════════════════════════════════════ */
.ob-layout {
  display: flex;
  width: 100%;
  height: 100vh;
  overflow: hidden;
}

/* ──────────── LEFT PANEL ──────────── */
.ob-panel-left {
  width: 42%;
  background: #1a140d;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: clamp(20px, 4vh, 48px) clamp(28px, 4vw, 52px);
  overflow: hidden;
  flex-shrink: 0;
  height: 100vh;
}

.ob-panel-left-bg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  pointer-events: none;
  z-index: 0;
}

.ob-brand-logo {
  position: relative;
  z-index: 1;
}

.ob-brand-logo-text {
  font-family: var(--font-serif);
  font-size: 26px;
  font-weight: 700;
  color: var(--text-inverted);
  letter-spacing: -0.5px;
  line-height: 1;
}

.ob-panel-left-content {
  position: relative;
  z-index: 1;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: clamp(12px, 2.5vh, 40px) 0;
  min-height: 0;
  overflow: hidden;
}

.ob-panel-left-eyebrow {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.65);
  margin-bottom: clamp(8px, 1.5vh, 16px);
}

.ob-panel-left-title {
  font-family: var(--font-serif);
  font-size: clamp(24px, 2.4vw + 0.8rem, 36px);
  font-weight: 700;
  color: var(--text-inverted);
  line-height: 1.22;
  margin-bottom: clamp(10px, 1.8vh, 20px);
}

.ob-panel-left-body {
  font-size: 13.5px;
  color: rgba(255, 255, 255, 0.78);
  line-height: 1.65;
  max-width: 340px;
}

/* Features */
.ob-panel-features {
  display: flex;
  flex-direction: column;
  gap: clamp(8px, 1.4vh, 14px);
  margin-top: clamp(16px, 2.8vh, 36px);
}

.ob-panel-feature {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.ob-panel-feature-icon {
  width: 32px;
  height: 32px;
  background: rgba(255, 255, 255, 0.12);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: rgba(255, 255, 255, 0.85);
}

.ob-panel-feature-text {
  font-size: 12.5px;
  color: rgba(255, 255, 255, 0.75);
  line-height: 1.5;
}

.ob-panel-feature-text strong {
  display: block;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.92);
  font-size: 12px;
  margin-bottom: 1px;
}

/* Progress pips */
.ob-progress-track {
  margin-top: clamp(14px, 2.4vh, 32px);
  display: flex;
  gap: 5px;
  align-items: center;
}

.ob-progress-pip {
  height: 3px;
  flex: 1;
  border-radius: var(--radius-sm);
  background: rgba(255,255,255,0.2);
  transition: all var(--transition);
}

.ob-progress-pip.active { background: var(--gold-light); }
.ob-progress-pip.done   { background: rgba(255, 255, 255, 0.55); }

/* Footer */
.ob-panel-left-footer {
  position: relative;
  z-index: 1;
}

.ob-panel-left-footer p {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.45);
  line-height: 1.5;
}

.ob-footer-link {
  color: rgba(255, 255, 255, 0.55);
  text-decoration: underline;
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  font-size: 11px;
  font-family: var(--font-sans);
}

/* ──────────── RIGHT PANEL ──────────── */
.ob-panel-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  overflow-x: hidden;
  background-color: var(--surface);
  height: 100vh;
}

.ob-panel-right-inner {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 48px 52px;
  max-width: 620px;
  width: 100%;
  margin: 0 auto;
}

.ob-step {
  width: 100%;
}

/* ──────────── STEP HEADER ──────────── */
.ob-step-header {
  margin-bottom: 32px;
}

.ob-step-eyebrow {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.8px;
  text-transform: uppercase;
  color: var(--gold-dark);
  margin-bottom: 8px;
}

.ob-step-title {
  font-family: var(--font-serif);
  font-size: 26px;
  font-weight: 700;
  color: var(--text);
  line-height: 1.25;
  margin-bottom: 8px;
}

.ob-step-subtitle {
  font-size: 13.5px;
  color: var(--text-2);
  line-height: 1.55;
}

/* ──────────── BACK LINK ──────────── */
.ob-back-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-2);
  background: none;
  border: none;
  padding: 6px 0;
  cursor: pointer;
  margin-bottom: 28px;
  transition: color var(--transition);
}

.ob-back-link:hover { color: var(--gold-dark); }

/* ──────────── ROLE CARDS ──────────── */
.ob-role-cards {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 28px;
}

.ob-role-card {
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px 22px;
  cursor: pointer;
  transition: all var(--transition);
  background: var(--surface);
  display: flex;
  align-items: flex-start;
  gap: 18px;
}

.ob-role-card:hover {
  border-color: var(--gold);
  transform: translateY(-1px);
}

.ob-role-card.selected {
  border-color: var(--gold);
  background: rgba(196, 169, 106, 0.04);
}

.ob-role-card-icon {
  width: 44px;
  height: 44px;
  background: rgba(196, 169, 106, 0.1);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--gold-dark);
  transition: all var(--transition);
}

.ob-role-card.selected .ob-role-card-icon {
  background: var(--gold-dark);
  color: var(--text-inverted);
}

.ob-role-card-body {
  flex: 1;
  min-width: 0;
}

.ob-role-card-title {
  font-family: var(--font-serif);
  font-size: 16px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 3px;
}

.ob-role-card-desc {
  font-size: 12px;
  color: var(--text-2);
  line-height: 1.5;
  margin-bottom: 10px;
}

.ob-role-card-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: var(--radius-full);
  background: rgba(196, 169, 106, 0.1);
  color: var(--gold-dark);
  border: 1px solid rgba(196, 169, 106, 0.25);
}

.ob-role-card-badge.free {
  background: rgba(76, 175, 125, 0.1);
  color: var(--green);
  border-color: rgba(76, 175, 125, 0.25);
}

.ob-role-card-action {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  padding-top: 10px;
  color: var(--text-4);
  transition: color var(--transition);
}

.ob-role-card:hover .ob-role-card-action,
.ob-role-card.selected .ob-role-card-action {
  color: var(--gold-dark);
}

/* ──────────── BP TYPE CHIPS ──────────── */
.ob-bp-type {
  padding: 16px;
  background: var(--surface-2);
  border-radius: var(--radius-sm);
  margin-bottom: 18px;
}

/* ──────────── FORM ELEMENTS ──────────── */

/* Password */
.ob-password-wrap { position: relative; }
.ob-password-wrap .form-input { padding-right: 42px; }

.ob-password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  padding: 4px;
  cursor: pointer;
  color: var(--text-2);
  display: flex;
  align-items: center;
  transition: color var(--transition);
}

.ob-password-toggle:hover { color: var(--gold-dark); }

/* Password requirements */
.ob-password-reqs {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 14px;
  margin-top: 10px;
}

.ob-req-item {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: var(--text-4);
  transition: color var(--transition);
}

.ob-req-item.valid   { color: var(--green); }
.ob-req-item.invalid { color: var(--red); }

/* Terms/opt-in checkbox rows */
.ob-terms-row { margin-bottom: 22px; }

/* Override auth-remember for the register page — align-items:center keeps
   single-line labels on the midline; for multi-line labels flex-start is used */
.auth-remember {
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 10px;
  cursor: pointer;
}

.auth-checkbox {
  margin-top: 2px;   /* nudge checkbox down to align with first text line */
  flex-shrink: 0;
}
.ob-terms-row .auth-checkbox-label small {
  display: block;
  font-size: 11px;
  color: var(--text-4);
  margin-top: 2px;
}

/* ──────────── BUTTONS ──────────── */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 11px 22px;
  font-family: var(--font-sans);
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.2px;
  border-radius: var(--radius-full);
  border: 1.5px solid transparent;
  cursor: pointer;
  transition: all var(--transition);
  text-decoration: none;
  white-space: nowrap;
  -webkit-appearance: none;
  outline: none;
}

.btn-primary {
  background: var(--primary);
  color: var(--text-inverted);
  border-color: var(--primary);
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-mid);
  border-color: var(--primary-mid);
}

.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.ob-btn-full { width: 100%; }

/* ──────────── BOTTOM SIGN-IN ROW ──────────── */
.ob-signin-row {
  text-align: center;
  margin-top: 24px;
  font-size: 12.5px;
  color: var(--text-2);
}

.ob-signin-link {
  color: var(--gold-dark);
  text-decoration: none;
  font-weight: 600;
}

.ob-signin-link:hover { text-decoration: underline; }

/* ──────────── WHO WE SERVE MODAL ──────────── */
.wws-section { margin-bottom: 20px; }

.wws-section-title {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--gold-dark);
  margin-bottom: 8px;
}

.wws-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.wws-list li {
  font-size: 12.5px;
  color: var(--text-2);
  padding: 4px 0 4px 14px;
  position: relative;
  line-height: 1.5;
}

.wws-list li::before {
  content: '·';
  position: absolute;
  left: 0;
  color: var(--gold);
  font-weight: 700;
}

/* ──────────── RESPONSIVE ──────────── */

/* Short viewports: drop features first. Progress pips are tiny
   and kept (functional flow indicator). These come before the
   width queries so width-based stacking can still override. */
@media (max-height: 680px) {
  .ob-panel-features { display: none; }
}

@media (max-width: 900px) {
  .ob-panel-left { width: 36%; padding: 36px 32px; }
  .ob-panel-right-inner { padding: 40px 36px; }
  .ob-panel-left-title { font-size: 28px; }
}

@media (max-width: 720px) {
  .ob-layout { flex-direction: column; height: auto; min-height: 100vh; overflow: visible; }
  .ob-panel-left { width: 100%; height: auto; padding: 32px 28px; }
  .ob-panel-left-content { padding: 24px 0; }
  .ob-panel-features { display: none; }
  .ob-panel-right { height: auto; overflow: visible; }
  .ob-panel-right-inner { padding: 32px 24px; }
}
</style>
