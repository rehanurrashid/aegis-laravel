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
          <a :href="route('home')" class="signin-left-footer-link">About</a>
          &nbsp;·&nbsp;
          <a href="mailto:support@maatpracticefirm.com" class="signin-left-footer-link">Contact</a>
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

          <!-- Flash / validation error -->
          <div v-if="$page.props.flash?.error" class="signin-alert-error">
            <AegisIcon name="alert-circle" :size="14" />
            <span>{{ $page.props.flash.error }}</span>
          </div>

          <form class="signin-form" @submit.prevent="submitSignin" novalidate>

            <div class="form-group">
              <label class="form-label" for="email">Email Address</label>
              <input
                id="email"
                v-model="loginForm.email"
                type="email"
                class="form-input"
                :class="{ 'is-error': loginForm.errors.email }"
                placeholder="your@email.com"
                autocomplete="email"
                autofocus
                @input="loginForm.clearErrors('email')"
              />
              <div v-if="loginForm.errors.email" class="form-error">{{ loginForm.errors.email }}</div>
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
                  :class="{ 'is-error': loginForm.errors.password }"
                  placeholder="Enter your password"
                  autocomplete="current-password"
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
              <div v-if="loginForm.errors.password" class="form-error">{{ loginForm.errors.password }}</div>
            </div>

            <div class="auth-remember">
              <input
                id="remember"
                v-model="loginForm.remember"
                type="checkbox"
                class="auth-checkbox"
              />
              <label for="remember" class="auth-checkbox-label">Keep me signed in for 30 days</label>
            </div>

            <button
              type="submit"
              class="btn btn-primary btn-block"
              :disabled="loginForm.processing"
            >{{ loginForm.processing ? 'Signing in…' : 'Sign In' }}</button>

          </form>

          <div class="signin-divider">or continue with</div>

          <div class="signin-sso-grid">
            <button class="signin-sso-btn" type="button" disabled data-tooltip="Coming soon">
              <!-- Google wordmark SVG — brand asset, inline required -->
              <svg viewBox="0 0 24 24" fill="none" width="16" height="16" aria-hidden="true">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
              </svg>
              Google
            </button>
            <button class="signin-sso-btn" type="button" disabled data-tooltip="Coming soon">
              <!-- Microsoft brand grid — brand asset, inline required -->
              <svg viewBox="0 0 24 24" fill="none" width="16" height="16" aria-hidden="true">
                <rect x="1"  y="1"  width="10" height="10" fill="#F25022"/>
                <rect x="13" y="1"  width="10" height="10" fill="#7FBA00"/>
                <rect x="1"  y="13" width="10" height="10" fill="#00A4EF"/>
                <rect x="13" y="13" width="10" height="10" fill="#FFB900"/>
              </svg>
              Microsoft
            </button>
          </div>

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
                  :class="{ 'is-error': resetForm.errors.email }"
                  placeholder="your@email.com"
                  autocomplete="email"
                />
                <div v-if="resetForm.errors.email" class="form-error">{{ resetForm.errors.email }}</div>
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
              style="margin-top: 1rem;"
              @click="showSignin"
            >Back to Sign In</button>
          </div>

        </div><!-- /forgot+success view -->

      </div>
    </div><!-- /signin-panel-right -->

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'

// ── Local state ────────────────────────────────────────────────────────
const activeView   = ref('signin')   // 'signin' | 'forgot' | 'success'
const showPassword = ref(false)
const resetEmailSent = ref('')

// ── Forms ──────────────────────────────────────────────────────────────
const loginForm = useForm({
  email:    '',
  password: '',
  remember: false,
})

const resetForm = useForm({
  email: '',
})

// ── Panel transitions ──────────────────────────────────────────────────
function showForgot() {
  // Pre-populate forgot email from whatever the user typed in sign-in
  resetForm.email = loginForm.email
  activeView.value = 'forgot'
}

function showSignin() {
  activeView.value = 'signin'
  resetForm.reset()
  resetEmailSent.value = ''
}

// ── Submit: sign in ────────────────────────────────────────────────────
function submitSignin() {
  loginForm.post(route('login.store'), {
    onFinish: () => loginForm.reset('password'),
  })
}

// ── Submit: reset link ─────────────────────────────────────────────────
function submitReset() {
  resetForm.post(route('password.email'), {
    onSuccess: () => {
      resetEmailSent.value = resetForm.email
      activeView.value = 'success'
    },
  })
}
</script>

<style scoped>
/* ══════════════════════════════════════
   SPLIT-PANEL SHELL
══════════════════════════════════════ */
.signin-layout {
  display: flex;
  width: 100%;
  min-height: 100vh;
  overflow: hidden;
}

/* ──────────────── LEFT PANEL ──────────────── */
.signin-panel-left {
  width: 42%;
  background: #1a140d;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 48px 52px;
  overflow: hidden;
  flex-shrink: 0;
  height: 100vh;
}

.signin-panel-left-bg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  pointer-events: none;
  z-index: 0;
}

.signin-brand {
  position: relative;
  z-index: 1;
}

.signin-brand-name {
  font-family: var(--font-serif);
  font-size: 26px;
  font-weight: 700;
  color: var(--text-inverted);
  letter-spacing: -0.5px;
  line-height: 1;
}

.signin-panel-left-content {
  position: relative;
  z-index: 1;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 40px 0;
}

.signin-left-eyebrow {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.88);
  margin-bottom: 16px;
}

.signin-left-title {
  font-family: var(--font-serif);
  font-size: 36px;
  font-weight: 700;
  color: var(--text-inverted);
  line-height: 1.22;
  margin-bottom: 20px;
}

.signin-left-body {
  font-size: 13.5px;
  color: rgba(255, 255, 255, 0.78);
  line-height: 1.65;
  max-width: 340px;
  margin-bottom: 40px;
}

/* Trust items */
.signin-trust-items {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.signin-trust-item {
  display: flex;
  align-items: center;
  gap: 14px;
}

.signin-trust-icon {
  width: 36px;
  height: 36px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: rgba(255, 255, 255, 0.85);
}

.signin-trust-text {
  font-size: 12.5px;
  color: rgba(255, 255, 255, 0.72);
  line-height: 1.45;
}

.signin-trust-text strong {
  display: block;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.90);
  font-size: 12px;
  margin-bottom: 1px;
}

/* Testimonial */
.signin-testimonial {
  margin-top: 44px;
  padding: 20px 22px;
  background: rgba(255, 255, 255, 0.07);
  border-radius: var(--radius);
  border: 1px solid rgba(255, 255, 255, 0.12);
  position: relative;
}

.signin-testimonial::before {
  content: '"';
  position: absolute;
  top: -6px;
  left: 18px;
  font-family: var(--font-serif);
  font-size: 48px;
  color: rgba(255, 255, 255, 0.2);
  line-height: 1;
}

.signin-testimonial-text {
  font-size: 12.5px;
  color: rgba(255, 255, 255, 0.75);
  line-height: 1.6;
  font-style: italic;
  margin-bottom: 12px;
  padding-top: 8px;
}

.signin-testimonial-author {
  font-size: 11px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.55);
  letter-spacing: 0.3px;
}

/* Left footer */
.signin-panel-left-footer {
  position: relative;
  z-index: 1;
}

.signin-panel-left-footer p {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.45);
  line-height: 1.5;
}

.signin-left-footer-link {
  color: rgba(255, 255, 255, 0.55);
  text-decoration: underline;
}

/* ──────────────── RIGHT PANEL ──────────────── */
.signin-panel-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  overflow-x: hidden;
  background-color: var(--surface);
  height: 100vh;
}

.signin-panel-right-inner {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 52px 64px;
  max-width: 520px;
  width: 100%;
  margin: 0 auto;
  min-height: 100vh;
}

.signin-view {
  width: 100%;
}

/* Form header */
.signin-form-header {
  margin-bottom: 36px;
}

.signin-form-eyebrow {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.8px;
  text-transform: uppercase;
  color: var(--gold-dark);
  margin-bottom: 8px;
}

.signin-form-title {
  font-family: var(--font-serif);
  font-size: 28px;
  font-weight: 700;
  color: var(--text);
  line-height: 1.2;
  margin-bottom: 8px;
}

.signin-form-subtitle {
  font-size: 13.5px;
  color: var(--text-2);
  line-height: 1.55;
}

/* Error alert */
.signin-alert-error {
  display: flex;
  align-items: flex-start;
  gap: 9px;
  background: var(--red-light);
  border: 1.5px solid rgba(224, 92, 92, 0.25);
  border-left: 3px solid var(--red);
  border-radius: var(--radius-sm);
  padding: 12px 14px;
  margin-bottom: 20px;
  font-size: 12.5px;
  color: var(--red);
  line-height: 1.5;
}

/* Password toggle */
.signin-password-wrap {
  position: relative;
}

.signin-password-toggle {
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

.signin-password-toggle:hover {
  color: var(--gold-dark);
}

/* Divider */
.signin-divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 22px 0;
  font-size: 11px;
  font-weight: 600;
  color: var(--text-2);
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.signin-divider::before,
.signin-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--border);
}

/* SSO grid */
.signin-sso-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 4px;
}

.signin-sso-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 14px;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--surface);
  font-family: var(--font-sans);
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text-2);
  cursor: pointer;
  transition: all var(--transition);
  text-decoration: none;
}

.signin-sso-btn:not(:disabled):hover {
  border-color: var(--gold-light);
  background: var(--surface-2);
  color: var(--text);
}

.signin-sso-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

/* Back link */
.signin-back-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-2);
  background: none;
  border: none;
  cursor: pointer;
  text-decoration: none;
  margin-bottom: 28px;
  padding: 0;
  transition: color var(--transition);
}

.signin-back-link:hover {
  color: var(--gold-dark);
}

/* Match PHP .remember-row margin-bottom — closes gap between checkbox and Sign In button */
.signin-view .auth-remember {
  margin-bottom: 24px;
}

/* ── "Forgot password?" button reset ──────────────────────────────────
   form-label-link is applied to a <button> here — strip browser defaults
   so it renders identically to the PHP <a class="forgot-link">
────────────────────────────────────────────────────────────────────── */
button.form-label-link {
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  font-family: var(--font-sans);
  /* color, size, weight inherited from .form-label-link in app.css */
}

/* ── Sign In / Send Reset Link buttons ────────────────────────────────
   The login page uses pill-shaped, dark-background primary buttons —
   different from the global gold btn-primary used in portals.
   Scoped overrides apply only inside this component.
────────────────────────────────────────────────────────────────────── */
.signin-view .btn-primary {
  background: var(--primary);
  border-color: var(--primary);
  color: var(--text-inverted);
  border-radius: var(--radius-full);
  padding: 12px 22px;
  font-size: 13px;
  font-weight: 700;
  border: 1.5px solid var(--primary);
}

.signin-view .btn-primary:hover:not(:disabled) {
  background: var(--primary-mid);
  border-color: var(--primary-mid);
  transform: none;
}

/* ── "Back to Sign In" outline button ─────────────────────────────────
   Also needs pill shape to match PHP source (.btn-outline in login.php)
────────────────────────────────────────────────────────────────────── */
.signin-view .btn-outline {
  border-radius: var(--radius-full);
  padding: 12px 22px;
  font-size: 13px;
  font-weight: 700;
  border: 1.5px solid var(--border-dark);
}

.signin-view .btn-outline:hover {
  border-color: var(--gold);
  color: var(--gold-dark);
  background: transparent;
}

/* Reset hint */
.signin-reset-hint {
  text-align: center;
  margin-top: 16px;
  font-size: 12px;
  color: var(--text-2);
  line-height: 1.5;
}

/* Success box */
.signin-success-box {
  text-align: center;
  padding: 32px 0;
}

.signin-success-ring {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: rgba(76, 175, 125, 0.1);
  border: 2px solid rgba(76, 175, 125, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  color: var(--green);
}

.signin-success-title {
  font-family: var(--font-serif);
  font-size: 20px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 8px;
}

.signin-success-body {
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.6;
}

.signin-success-body strong {
  color: var(--text);
  font-weight: 600;
}

/* ──────────────── FORM (right-panel shared) ──────────────── */
.signin-form {
  width: 100%;
}

/* ──────────────── RESPONSIVE ──────────────── */
@media (max-width: 900px) {
  .signin-panel-left {
    width: 38%;
    padding: 36px 32px;
  }
  .signin-panel-right-inner {
    padding: 40px 40px;
  }
}

@media (max-width: 720px) {
  .signin-layout {
    flex-direction: column;
    height: auto;
    min-height: 100vh;
    overflow: visible;
  }
  .signin-panel-left {
    width: 100%;
    height: auto;
    padding: 32px 28px;
  }
  .signin-panel-left-content {
    padding: 24px 0;
  }
  .signin-trust-items,
  .signin-testimonial {
    display: none;
  }
  .signin-panel-right {
    height: auto;
    overflow: visible;
  }
  .signin-panel-right-inner {
    padding: 36px 24px;
    min-height: auto;
    justify-content: flex-start;
  }
  .signin-sso-grid {
    grid-template-columns: 1fr;
  }
}
</style>
