<template>
  <div class="signin-layout">

    <!-- ════════════════════════════ LEFT PANEL ════════════════════════════ -->
    <div class="panel-left">
      <img src="/onboarding/aegis-bg.svg" class="panel-left-bg" alt="" aria-hidden="true">

      <div class="brand-logo">
        <span class="brand-logo-text">Aegis</span>
      </div>

      <div class="panel-left-content">
        <div class="panel-left-eyebrow">Welcome Back</div>
        <h1 class="panel-left-title">Your Practice, Protected.</h1>
        <p class="panel-left-body">Sign in to access your Aegis dashboard — manage your network, coordinate care, and keep your practice running smoothly.</p>

        <div class="trust-items">
          <div class="trust-item">
            <div class="trust-icon">
              <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div class="trust-text">
              <strong>HIPAA-Compliant</strong>
              All data encrypted end-to-end, every session.
            </div>
          </div>
          <div class="trust-item">
            <div class="trust-icon">
              <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div class="trust-text">
              <strong>Two-Factor Authentication</strong>
              Extra security on every login.
            </div>
          </div>
          <div class="trust-item">
            <div class="trust-icon">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="trust-text">
              <strong>Always Available</strong>
              99.9% uptime — access when you need it most.
            </div>
          </div>
        </div>

        <div class="testimonial">
          <p class="testimonial-text">Aegis gave me complete peace of mind. Knowing my patients are protected if something happens to me is invaluable.</p>
          <div class="testimonial-author">Dr. Sarah M. — Licensed Clinical Psychologist</div>
        </div>
      </div>

      <div class="panel-left-footer">
        <p>© 2025 Aegis Platform. All rights reserved.<br>
          <a href="#" @click.prevent>Privacy Policy</a> &nbsp;·&nbsp; <a href="#" @click.prevent>Terms of Service</a>
        </p>
      </div>
    </div>

    <!-- ════════════════════════════ RIGHT PANEL ════════════════════════════ -->
    <div class="panel-right">
      <div class="panel-right-inner">

        <!-- ══ SIGN IN PANEL ══ -->
        <div class="signin-panel" :class="{ hidden: showForgot }">

          <div class="signin-header">
            <div class="signin-eyebrow">Secure Sign In</div>
            <h2 class="signin-title">Sign in to Aegis</h2>
            <p class="signin-subtitle">Enter your credentials to access your dashboard.</p>
          </div>

          <!-- Error alert -->
          <div class="alert-error" :class="{ visible: errorMsg }">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <span>{{ errorMsg }}</span>
          </div>

          <!-- Form -->
          <form @submit.prevent="submit" novalidate>
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input
                v-model="form.email"
                type="email"
                class="form-control"
                :class="{ error: form.errors.email }"
                placeholder="your@email.com"
                autocomplete="email"
                @input="clearError"
              />
            </div>

            <div class="form-group">
              <div class="forgot-row">
                <label class="form-label" style="margin-bottom: 0;">Password</label>
                <a href="#" class="forgot-link" @click.prevent="showForgotPanel">Forgot password?</a>
              </div>
              <div class="password-wrap">
                <input
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  class="form-control"
                  :class="{ error: form.errors.password }"
                  placeholder="Enter your password"
                  autocomplete="current-password"
                  @input="clearError"
                />
                <button type="button" class="password-toggle" @click="showPassword = !showPassword" tabindex="-1">
                  <svg v-if="!showPassword" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg v-else viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
            </div>

            <div class="remember-row">
              <input type="checkbox" id="rememberMe" v-model="form.remember">
              <label class="remember-label" for="rememberMe">Keep me signed in for 30 days</label>
            </div>

            <button type="submit" class="btn btn-primary btn-full" :class="{ 'btn-loading': form.processing }" :disabled="form.processing">Sign In</button>
          </form>

          <div class="divider-text">or continue with</div>

          <div class="sso-grid">
            <button type="button" class="sso-btn">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
              </svg>
              Google
            </button>
            <button type="button" class="sso-btn">
              <svg viewBox="0 0 24 24" fill="none">
                <rect x="1" y="1" width="10" height="10" fill="#F25022"/>
                <rect x="13" y="1" width="10" height="10" fill="#7FBA00"/>
                <rect x="1" y="13" width="10" height="10" fill="#00A4EF"/>
                <rect x="13" y="13" width="10" height="10" fill="#FFB900"/>
              </svg>
              Microsoft
            </button>
          </div>

          <div class="signup-row">
            Don't have an account? <a href="/register">Create Account</a>
          </div>

        </div>

        <!-- ══ FORGOT PASSWORD PANEL ══ -->
        <div class="forgot-panel" :class="{ active: showForgot }">

          <a href="#" class="back-link" @click.prevent="backToSignin">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Sign In
          </a>

          <div class="signin-header">
            <div class="signin-eyebrow">Account Recovery</div>
            <h2 class="signin-title">Reset Password</h2>
            <p class="signin-subtitle">Enter your email address and we'll send you a link to reset your password.</p>
          </div>

          <!-- Forgot form -->
          <div v-if="!resetSuccess">
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input
                v-model="forgotEmail"
                type="email"
                class="form-control"
                :class="{ error: forgotError }"
                placeholder="your@email.com"
                autocomplete="email"
              />
            </div>

            <button class="btn btn-primary btn-full" :class="{ 'btn-loading': forgotLoading }" :disabled="forgotLoading" @click="submitReset">Send Reset Link</button>

            <div style="text-align: center; margin-top: 16px; font-size: 12px; color: var(--text-2); line-height: 1.5;">
              We'll send a password reset link to your registered email address. Check your spam folder if you don't see it.
            </div>
          </div>

          <!-- Reset success -->
          <div v-else>
            <div class="success-box">
              <div class="success-ring">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </div>
              <div class="success-title">Check Your Email</div>
              <p class="success-body">We've sent a password reset link to <strong style="color: var(--text); font-weight: 600;">{{ forgotEmail }}</strong>.<br><br>The link will expire in 30 minutes. Check your spam folder if you don't see it.</p>
            </div>
            <button class="btn btn-outline btn-full" style="margin-top: 12px;" @click="backToSignin">Back to Sign In</button>
          </div>

        </div>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const showForgot = ref(false);
const showPassword = ref(false);
const errorMsg = ref('');
const forgotEmail = ref('');
const forgotError = ref(false);
const forgotLoading = ref(false);
const resetSuccess = ref(false);

function submit() {
  errorMsg.value = '';
  form.post('/login', {
    onError: (errors) => {
      errorMsg.value = errors.email || errors.password || 'Invalid email or password. Please try again.';
    },
  });
}

function clearError() {
  errorMsg.value = '';
}

function showForgotPanel() {
  forgotEmail.value = form.email || '';
  showForgot.value = true;
}

function backToSignin() {
  showForgot.value = false;
  resetSuccess.value = false;
  forgotError.value = false;
}

function submitReset() {
  forgotError.value = false;
  if (!forgotEmail.value.trim()) {
    forgotError.value = true;
    return;
  }
  forgotLoading.value = true;
  setTimeout(() => {
    forgotLoading.value = false;
    resetSuccess.value = true;
  }, 1000);
}
</script>

<style scoped>
/* ══════════════════════════════════════════════════
   LAYOUT — SPLIT SCREEN
   Tokens (--gold, --primary, --surface*, --radius*, fonts) come from
   the global Aegis design system in resources/css/app.css.
══════════════════════════════════════════════════ */
.signin-layout {
  position: fixed;
  inset: 0;
  display: flex;
  width: 100%;
  height: 100vh;
  overflow: hidden;
  background-color: var(--surface-2);
  font-family: var(--font-sans);
  color: var(--text);
  line-height: 1.5;
}

/* LEFT PANEL */
.panel-left {
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

.panel-left-bg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  pointer-events: none;
  z-index: 0;
}

.brand-logo {
  position: relative;
  z-index: 1;
}

.brand-logo-text {
  font-family: var(--font-serif);
  font-size: 26px;
  font-weight: 700;
  color: var(--text-inverted);
  letter-spacing: -0.5px;
  line-height: 1;
}

.panel-left-content {
  position: relative;
  z-index: 1;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 40px 0;
}

.panel-left-eyebrow {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: rgba(255,255,255,0.88);
  margin-bottom: 16px;
}

.panel-left-title {
  font-family: var(--font-serif);
  font-size: 36px;
  font-weight: 700;
  color: var(--text-inverted);
  line-height: 1.22;
  margin-bottom: 20px;
}

.panel-left-body {
  font-size: 13.5px;
  color: rgba(255,255,255,0.78);
  line-height: 1.65;
  max-width: 340px;
  margin-bottom: 40px;
}

/* Trust badges */
.trust-items {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.trust-item {
  display: flex;
  align-items: center;
  gap: 14px;
}

.trust-icon {
  width: 36px;
  height: 36px;
  background: rgba(255,255,255,0.1);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.trust-icon svg {
  width: 16px;
  height: 16px;
  stroke: rgba(255,255,255,0.85);
  fill: none;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 1.8;
}

.trust-text {
  font-size: 12.5px;
  color: rgba(255,255,255,0.72);
  line-height: 1.45;
}

.trust-text strong {
  display: block;
  font-weight: 600;
  color: rgba(255,255,255,0.90);
  font-size: 12px;
  margin-bottom: 1px;
}

/* Testimonial */
.testimonial {
  margin-top: 44px;
  padding: 20px 22px;
  background: rgba(255,255,255,0.07);
  border-radius: var(--radius);
  border: 1px solid rgba(255,255,255,0.12);
  position: relative;
}

.testimonial::before {
  content: '"';
  position: absolute;
  top: -6px;
  left: 18px;
  font-family: var(--font-serif);
  font-size: 48px;
  color: rgba(255,255,255,0.2);
  line-height: 1;
}

.testimonial-text {
  font-size: 12.5px;
  color: rgba(255,255,255,0.75);
  line-height: 1.6;
  font-style: italic;
  margin-bottom: 12px;
  padding-top: 8px;
}

.testimonial-author {
  font-size: 11px;
  font-weight: 600;
  color: rgba(255,255,255,0.55);
  letter-spacing: 0.3px;
}

/* Footer */
.panel-left-footer {
  position: relative;
  z-index: 1;
}

.panel-left-footer p {
  font-size: 11px;
  color: rgba(255,255,255,0.45);
  line-height: 1.5;
}

.panel-left-footer a {
  color: rgba(255,255,255,0.55);
  text-decoration: underline;
}

/* ══════════════════════════════════════════════════
   RIGHT PANEL
══════════════════════════════════════════════════ */
.panel-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  overflow-x: hidden;
  background-color: var(--surface);
  height: 100vh;
}

.panel-right-inner {
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

/* ══════════════════════════════════════════════════
   SIGN IN FORM
══════════════════════════════════════════════════ */
.signin-header { margin-bottom: 36px; }

.signin-eyebrow {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.8px;
  text-transform: uppercase;
  color: var(--gold-dark);
  margin-bottom: 8px;
}

.signin-title {
  font-family: var(--font-serif);
  font-size: 28px;
  font-weight: 700;
  color: var(--text);
  line-height: 1.2;
  margin-bottom: 8px;
}

.signin-subtitle {
  font-size: 13.5px;
  color: var(--text-2);
  line-height: 1.55;
}

/* Form */
.form-group { margin-bottom: 18px; }

.form-label {
  display: block;
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: var(--text-2);
  margin-bottom: 6px;
}

.form-control {
  display: block;
  width: 100%;
  padding: 11px 13px;
  font-family: var(--font-sans);
  font-size: 13px;
  font-weight: 500;
  color: var(--text);
  background-color: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  transition: border-color var(--transition), box-shadow var(--transition);
  -webkit-appearance: none;
  appearance: none;
  outline: none;
}

.form-control::placeholder { color: var(--text-4); font-weight: 400; }

.form-control:focus {
  border-color: var(--gold);
  box-shadow: var(--focus-ring);
}

.form-control.error {
  border-color: var(--red);
  box-shadow: 0 0 0 3px rgba(224,92,92,0.10);
}

/* Password wrapper */
.password-wrap { position: relative; }

.password-toggle {
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

.password-toggle:hover { color: var(--gold-dark); }
.password-toggle svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.8; }

/* Forgot password */
.forgot-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}

.forgot-link {
  font-size: 12px;
  color: var(--gold-dark);
  text-decoration: none;
  font-weight: 500;
  transition: color var(--transition);
  cursor: pointer;
}

.forgot-link:hover { text-decoration: underline; }

/* Remember me */
.remember-row {
  display: flex;
  align-items: center;
  gap: 9px;
  margin-bottom: 24px;
}

.remember-row input[type="checkbox"] {
  -webkit-appearance: none;
  appearance: none;
  width: 16px;
  height: 16px;
  border: 1px solid var(--border-dark);
  border-radius: 4px;
  background: var(--surface);
  cursor: pointer;
  flex-shrink: 0;
  transition: all var(--transition);
}

.remember-row input[type="checkbox"]:checked {
  background: var(--gold);
  border-color: var(--gold);
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpolyline points='2,6 5,9 10,3' fill='none' stroke='white' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: center;
  background-size: 11px;
}

.remember-label {
  font-size: 12.5px;
  color: var(--text-2);
  cursor: pointer;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 22px;
  font-family: var(--font-sans);
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.2px;
  border-radius: var(--radius-full);
  border: 1px solid transparent;
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

.btn-primary:hover { background: var(--primary-mid); }

.btn-outline {
  background: transparent;
  color: var(--text-2);
  border-color: var(--border-dark);
}

.btn-outline:hover { border-color: var(--gold); color: var(--gold-dark); }

.btn-full { width: 100%; }

/* Error alert */
.alert-error {
  background: var(--red-light);
  border: 1px solid rgba(224,92,92,0.25);
  border-left: 3px solid var(--red);
  border-radius: var(--radius-sm);
  padding: 12px 14px;
  margin-bottom: 20px;
  display: none;
  align-items: flex-start;
  gap: 9px;
  font-size: 12.5px;
  color: var(--red);
  line-height: 1.5;
}

.alert-error.visible { display: flex; }
.alert-error svg { width: 14px; height: 14px; stroke: currentColor; flex-shrink: 0; margin-top: 1px; fill: none; stroke-linecap: round; stroke-linejoin: round; stroke-width: 2; }

/* Divider */
.divider-text {
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

.divider-text::before,
.divider-text::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--border);
}

/* SSO buttons */
.sso-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 4px;
}

.sso-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 14px;
  border: 1px solid var(--border);
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

.sso-btn:hover {
  border-color: var(--gold-light);
  background: var(--surface-2);
  color: var(--text);
}

.sso-btn svg { width: 16px; height: 16px; flex-shrink: 0; }

/* Bottom row */
.signup-row {
  text-align: center;
  margin-top: 28px;
  font-size: 12.5px;
  color: var(--text-2);
}

.signup-row a {
  color: var(--gold-dark);
  font-weight: 600;
  text-decoration: none;
}

.signup-row a:hover { text-decoration: underline; }

/* Forgot password panel */
.forgot-panel {
  display: none;
  animation: fadeIn 0.25s ease both;
}

.forgot-panel.active { display: block; }
.signin-panel { animation: fadeIn 0.25s ease both; }
.signin-panel.hidden { display: none; }

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-2);
  text-decoration: none;
  margin-bottom: 28px;
  transition: color var(--transition);
  cursor: pointer;
}

.back-link:hover { color: var(--gold-dark); }

/* Success state */
.success-box {
  text-align: center;
  padding: 32px 0;
}

.success-ring {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: rgba(76,175,125,0.1);
  border: 2px solid rgba(76,175,125,0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}

.success-ring svg {
  width: 26px;
  height: 26px;
  stroke: var(--green);
  fill: none;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 2;
}

.success-title {
  font-family: var(--font-serif);
  font-size: 20px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 8px;
}

.success-body {
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.6;
}

/* ══════════════════════════════════════════════════
   LOADING SPINNER
══════════════════════════════════════════════════ */
.btn-loading {
  position: relative;
  color: transparent !important;
  pointer-events: none;
}

.btn-loading::after {
  content: '';
  position: absolute;
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ══════════════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════════════ */
@media (max-width: 900px) {
  .panel-left { width: 38%; padding: 36px 32px; }
  .panel-right-inner { padding: 40px 40px; }
}

@media (max-width: 720px) {
  .signin-layout { position: static; flex-direction: column; height: auto; min-height: 100vh; overflow: visible; }
  .panel-left { width: 100%; height: auto; padding: 32px 28px; }
  .panel-left-content { padding: 24px 0; }
  .trust-items, .testimonial { display: none; }
  .panel-right { height: auto; overflow: visible; }
  .panel-right-inner { padding: 36px 24px; min-height: auto; justify-content: flex-start; }
  .sso-grid { grid-template-columns: 1fr; }
}
</style>
