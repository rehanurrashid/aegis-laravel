<!--
  pages/auth/Login.vue — sign in.

  Route: GET /login (login) · POST /login (login.store)
  Source: login.php — full split-panel conversion
  Layout: None (self-contained split-panel shell; AuthLayout cannot host this design)

  Panels:
    signin  — default
    forgot  — forgot-password inline form
    success — reset-link-sent confirmation
-->
<template>
  <Head title="Sign In — Aegis" />

  <div class="signin-layout">

    <!-- ════════════════════════════
         LEFT PANEL
    ════════════════════════════ -->
    <div class="signin-panel-left">
      <img src="/aegis-bg.svg" class="signin-panel-left-bg" alt="" aria-hidden="true" />


      <div class="signin-brand">
        <span class="signin-brand-name">Aegis</span>
      </div>

      <div class="signin-panel-left-content">
        <div class="signin-left-eyebrow">Welcome Back</div>
        <h1 class="signin-left-title">Your Practice, Protected.</h1>
        <p class="signin-left-body">
          Sign in to access your Aegis dashboard — manage your network, coordinate care,
          and keep your practice running smoothly.
        </p>

        <div class="signin-trust-items">
          <div class="signin-trust-item">
            <div class="signin-trust-icon">
              <AegisIcon name="shield" :size="16" />
            </div>
            <div class="signin-trust-text">
              <strong>HIPAA-Compliant</strong>
              All data encrypted end-to-end, every session.
            </div>
          </div>
          <div class="signin-trust-item">
            <div class="signin-trust-icon">
              <AegisIcon name="lock" :size="16" />
            </div>
            <div class="signin-trust-text">
              <strong>Two-Factor Authentication</strong>
              Extra security on every login.
            </div>
          </div>
          <div class="signin-trust-item">
            <div class="signin-trust-icon">
              <AegisIcon name="clock" :size="16" />
            </div>
            <div class="signin-trust-text">
              <strong>Always Available</strong>
              99.9% uptime — access when you need it most.
            </div>
          </div>
        </div>

        <div class="signin-testimonial">
          <p class="signin-testimonial-text">
            Aegis gave me complete peace of mind. Knowing my patients are protected if
            something happens to me is invaluable.
          </p>
          <div class="signin-testimonial-author">Dr. Sarah M. — Licensed Clinical Psychologist</div>
        </div>
      </div>

      <div class="signin-panel-left-footer">
        <p>
          © 2025 Aegis Platform. All rights reserved.<br />
          <a :href="route('about')"   class="signin-left-footer-link">About</a>
          &nbsp;·&nbsp;
          <a :href="route('pricing')" class="signin-left-footer-link">Pricing</a>
          &nbsp;·&nbsp;
          <a :href="route('contact')" class="signin-left-footer-link">Contact</a>
        </p>
      </div>
    </div>

    <!-- ════════════════════════════
         RIGHT PANEL
    ════════════════════════════ -->
    <div class="signin-panel-right">
      <div class="signin-panel-right-inner">

        <!-- ══ SIGN IN PANEL ══ -->
        <div v-show="activeView === 'signin'" class="signin-view">

          <div class="signin-form-header">
            <div class="signin-form-eyebrow">Secure Sign In</div>
            <h2 class="signin-form-title">Sign in to Aegis</h2>
            <p class="signin-form-subtitle">Enter your credentials to access your dashboard.</p>
          </div>

          <!-- Validation error — only from current form submission, never stale flash -->
          <div v-if="loginForm.errors.email || loginForm.errors.password" class="signin-alert-error">
            <AegisIcon name="alert-circle" :size="14" />
            <span>{{ loginForm.errors.email || loginForm.errors.password }}</span>
          </div>

          <form class="signin-form" @submit.prevent="submitSignin" autocomplete="on" novalidate>

            <div class="form-group">
              <label class="form-label" for="email">Email Address</label>
              <input
                id="email"
                v-model="loginForm.email"
                type="email"
                class="form-input"
                :class="{ 'is-error': loginFieldError('email') }"
                placeholder="your@email.com"
                autocomplete="email"
                autofocus
                @blur="v$login.email.$touch()"
                @input="loginForm.clearErrors('email')"
              />
              <div v-if="loginFieldError('email')" class="form-error">{{ loginFieldError('email') }}</div>
            </div>

            <div class="form-group">
              <div class="form-label-row">
                <label class="form-label" for="password">Password</label>
                <button
                  type="button"
                  class="form-label-link"
                  @click="showForgot"
                >Forgot password?</button>
              </div>
              <div class="signin-password-wrap">
                <input
                  id="password"
                  v-model="loginForm.password"
                  :type="showPassword ? 'text' : 'password'"
                  class="form-input"
                  :class="{ 'is-error': loginFieldError('password') }"
                  placeholder="Enter your password"
                  :autocomplete="rememberCredentials ? 'current-password' : 'off'"
                  @blur="v$login.password.$touch()"
                  @input="loginForm.clearErrors('password')"
                />
                <button
                  type="button"
                  class="signin-password-toggle"
                  :aria-label="showPassword ? 'Hide password' : 'Show password'"
                  @click="showPassword = !showPassword"
                >
                  <AegisIcon :name="showPassword ? 'eye-off' : 'eye'" :size="15" />
                </button>
              </div>
              <div v-if="loginFieldError('password')" class="form-error">{{ loginFieldError('password') }}</div>
            </div>

            <div class="auth-remember">
              <input
                id="remember-credentials"
                v-model="rememberCredentials"
                type="checkbox"
                class="auth-checkbox"
              />
              <label for="remember-credentials" class="auth-checkbox-label">Remember my credentials</label>
            </div>

            <button
              type="submit"
              class="btn btn-primary btn-block"
              :disabled="loginForm.processing"
            >{{ loginForm.processing ? 'Signing in…' : 'Sign In' }}</button>

          </form>

          <!-- SSO temporarily hidden — backend has no OAuth providers wired.
               When SSO is greenlit, uncomment this block and add the
               corresponding routes + OAuthController.
          <div class="signin-divider">or continue with</div>

          <div class="signin-sso-grid">
            <button class="signin-sso-btn" type="button" disabled data-tooltip="Coming soon">
              &lt;!&ndash; Google wordmark SVG &mdash; brand asset, inline required &ndash;&gt;
              <svg viewBox="0 0 24 24" fill="none" width="16" height="16" aria-hidden="true">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
              </svg>
              Google
            </button>
            <button class="signin-sso-btn" type="button" disabled data-tooltip="Coming soon">
              &lt;!&ndash; Microsoft brand grid &mdash; brand asset, inline required &ndash;&gt;
              <svg viewBox="0 0 24 24" fill="none" width="16" height="16" aria-hidden="true">
                <rect x="1"  y="1"  width="10" height="10" fill="#F25022"/>
                <rect x="13" y="1"  width="10" height="10" fill="#7FBA00"/>
                <rect x="1"  y="13" width="10" height="10" fill="#00A4EF"/>
                <rect x="13" y="13" width="10" height="10" fill="#FFB900"/>
              </svg>
              Microsoft
            </button>
          </div>
          -->

          <p class="auth-altline">
            Don't have an account?
            <a :href="route('register')" class="auth-altline-link">Create Account</a>
          </p>

        </div><!-- /signin-view -->

        <!-- ══ FORGOT PASSWORD PANEL ══ -->
        <div v-show="activeView === 'forgot' || activeView === 'success'" class="signin-view">

          <button
            type="button"
            class="signin-back-link"
            @click="showSignin"
          >
            <AegisIcon name="chevron-left" :size="14" />
            Back to Sign In
          </button>

          <!-- Forgot form -->
          <div v-show="activeView === 'forgot'">
            <div class="signin-form-header">
              <div class="signin-form-eyebrow">Account Recovery</div>
              <h2 class="signin-form-title">Reset Password</h2>
              <p class="signin-form-subtitle">Enter your email address and we'll send you a link to reset your password.</p>
            </div>

            <form @submit.prevent="submitReset" novalidate>
              <div class="form-group">
                <label class="form-label" for="forgot-email">Email Address</label>
                <input
                  id="forgot-email"
                  v-model="resetForm.email"
                  type="email"
                  class="form-input"
                  :class="{ 'is-error': resetFieldError('email') }"
                  placeholder="your@email.com"
                  autocomplete="email"
                  @blur="v$reset.email.$touch()"
                />
                <div v-if="resetFieldError('email')" class="form-error">{{ resetFieldError('email') }}</div>
              </div>

              <button
                type="submit"
                class="btn btn-primary btn-block"
                :disabled="resetForm.processing"
              >{{ resetForm.processing ? 'Sending…' : 'Send Reset Link' }}</button>

              <p class="signin-reset-hint">
                We'll send a password reset link to your registered email address. Check your spam folder if you don't see it.
              </p>
            </form>
          </div>

          <!-- Reset success -->
          <div v-show="activeView === 'success'" class="signin-success-box">
            <div class="signin-success-ring">
              <AegisIcon name="check" :size="26" />
            </div>
            <div class="signin-success-title">Check Your Email</div>
            <p class="signin-success-body">
              We've sent a password reset link to
              <strong>{{ resetEmailSent }}</strong>.<br /><br />
              The link will expire in 30 minutes. Check your spam folder if you don't see it.
            </p>
            <button
              type="button"
              class="btn btn-outline btn-block"
             
              @click="showSignin"
            >Back to Sign In</button>
          </div>

        </div><!-- /forgot+success view -->

      </div>
    </div><!-- /signin-panel-right -->

  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { useVuelidate } from '@vuelidate/core'
import { required, email, minLength, helpers } from '@vuelidate/validators'
import { useToast } from '@/composables/useToast'

const toast = useToast()

// ── Local state ────────────────────────────────────────────────────────
const activeView     = ref('signin')   // 'signin' | 'forgot' | 'success'
const showPassword   = ref(false)
const resetEmailSent = ref('')

// Persist "remember credentials" preference across visits
const rememberCredentials = ref(
  localStorage.getItem('aegis_remember_credentials') !== 'false'
)
watch(rememberCredentials, (val) => {
  localStorage.setItem('aegis_remember_credentials', String(val))
})

// ── Forms ──────────────────────────────────────────────────────────────
const loginForm = useForm({
  email:    '',
  password: '',
})

const resetForm = useForm({
  email: '',
})

// ── Client-side validation — login form ───────────────────────────────
const loginRules = computed(() => ({
  email: {
    required: helpers.withMessage('Email is required.', required),
    email:    helpers.withMessage('Enter a valid email address.', email),
  },
  password: {
    required: helpers.withMessage('Password is required.', required),
    min:      helpers.withMessage('Password must be at least 8 characters.', minLength(8)),
  },
}))
const v$login = useVuelidate(loginRules, loginForm)

// ── Client-side validation — reset form ──────────────────────────────
const resetRules = computed(() => ({
  email: {
    required: helpers.withMessage('Email is required.', required),
    email:    helpers.withMessage('Enter a valid email address.', email),
  },
}))
const v$reset = useVuelidate(resetRules, resetForm)

// ── Unified error helper ──────────────────────────────────────────────
function loginFieldError(field) {
  if (v$login.value[field]?.$error) return v$login.value[field].$errors[0]?.$message
  if (loginForm.errors[field]) return loginForm.errors[field]
  return null
}
function resetFieldError(field) {
  if (v$reset.value[field]?.$error) return v$reset.value[field].$errors[0]?.$message
  if (resetForm.errors[field]) return resetForm.errors[field]
  return null
}

// ── Panel transitions ──────────────────────────────────────────────────
function showForgot() {
  resetForm.email = loginForm.email
  v$reset.value.$reset()
  activeView.value = 'forgot'
}

function showSignin() {
  activeView.value = 'signin'
  resetForm.reset()
  v$reset.value.$reset()
  resetEmailSent.value = ''
}

// ── Submit: sign in ────────────────────────────────────────────────────

async function submitSignin() {
  const valid = await v$login.value.$validate()
  if (!valid) {
    toast.error('Please fix the highlighted fields.')
    return
  }

  // Capture credentials before form resets — needed for credential save after success
  const savedEmail    = loginForm.email
  const savedPassword = loginForm.password

  loginForm.post(route('login.store'), {
    onSuccess: () => {
      // Toast is handled by AppLayout via flash — no duplicate needed here.

      // Tell the browser to save credentials via the Credential Management API.
      // This is required because Inertia uses fetch(), not a real form POST,
      // so the browser never auto-detects a login to trigger its save prompt.
      if (rememberCredentials.value && window.PasswordCredential) {
        const cred = new window.PasswordCredential({
          id:       savedEmail,
          password: savedPassword,
          name:     savedEmail,
        })
        navigator.credentials.store(cred).catch(() => {
          // Silently ignore — credential storage is best-effort
        })
      }
    },
    onError: (errors) => {
      if (errors && Object.keys(errors).length > 0) {
        toast.error('Invalid credentials. Please try again.')
        loginForm.reset('password')
      }
    },
    onFinish: () => {},
  })
}

// ── Submit: reset link ─────────────────────────────────────────────────
async function submitReset() {
  const valid = await v$reset.value.$validate()
  if (!valid) {
    toast.error('Please enter a valid email address.')
    return
  }
  resetForm.post(route('password.email'), {
    onSuccess: () => {
      resetEmailSent.value = resetForm.email
      activeView.value = 'success'
    },
    onError: () => toast.error('Could not send reset link. Please try again.'),
  })
}
</script>

<!-- All signin-panel-* CSS lives in resources/css/app.css -->
