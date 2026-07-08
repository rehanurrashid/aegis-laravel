<!--
  pages/auth/MfaChallenge.vue — TOTP verification on login.
  Route: POST mfa.challenge.store
  Layout: self-contained split-panel (no AuthLayout)
-->
<template>
  <Head title="Two-Factor Verification — Aegis" />

  <div class="ob-layout">
    <div class="ob-panel-left">
      <img src="/aegis-bg.svg" class="ob-panel-left-bg" alt="" aria-hidden="true" />
      <div class="ob-brand-logo"><span class="ob-brand-logo-text">Aegis</span></div>
      <div class="ob-panel-left-content">
        <div class="ob-panel-left-eyebrow">Security Verification</div>
        <h1 class="ob-panel-left-title">Two-factor authentication keeps your practice safe.</h1>
        <p class="ob-panel-left-body">Your account is protected by an additional layer of security. Open your authenticator app to retrieve the 6-digit code.</p>
        <div class="ob-panel-features">
          <div class="ob-panel-feature">
            <div class="ob-panel-feature-icon"><AegisIcon name="shield-check" :size="15" /></div>
            <div class="ob-panel-feature-text"><strong>Time-based codes</strong>Codes refresh every 30 seconds for maximum security.</div>
          </div>
          <div class="ob-panel-feature">
            <div class="ob-panel-feature-icon"><AegisIcon name="phone" :size="15" /></div>
            <div class="ob-panel-feature-text"><strong>Authenticator app required</strong>Use Google Authenticator, Authy, or any TOTP app.</div>
          </div>
        </div>
      </div>
      <div class="ob-panel-left-footer"><p>© {{ year }} Aegis Platform. All rights reserved.</p></div>
    </div>

    <div class="ob-panel-right">
      <div class="ob-panel-right-inner">
        <div class="mfa-icon"><AegisIcon name="shield-check" :size="36" /></div>
        <div class="ob-step-header">
          <div class="ob-step-eyebrow">Security Verification</div>
          <h2 class="ob-step-title">Two-Factor Verification</h2>
          <p class="ob-step-subtitle">Enter the 6-digit code from your authenticator app.</p>
        </div>

        <form @submit.prevent="submit" novalidate>
          <div class="form-group">
            <label class="form-label" for="mfa-code">Authentication Code</label>
            <input id="mfa-code" v-model="form.code" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" class="form-input mfa-code-input" :class="{ 'is-error': fieldError('code') }" autocomplete="one-time-code" autofocus placeholder="000000" @blur="v$.code.$touch()" />
            <div v-if="fieldError('code')" class="form-error">{{ fieldError('code') }}</div>
          </div>
          <button type="submit" class="btn btn-primary ob-btn-full" :disabled="form.processing">
            {{ form.processing ? 'Verifying…' : 'Verify Code' }}
          </button>
        </form>

        <p class="ob-altline">
          <button type="button" class="ob-altline-link" @click="logout">Sign in with a different account</button>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { useVuelidate } from '@vuelidate/core'
import { required, minLength, maxLength, numeric, helpers } from '@vuelidate/validators'
import { useToast } from '@/composables/useToast'

const toast   = useToast()
const year    = new Date().getFullYear()
const form    = useForm({ code: '' })

// ── Vuelidate ──────────────────────────────────────────────────────────
const rules = computed(() => ({
  code: {
    required:  helpers.withMessage('Authentication code is required.', required),
    minLength: helpers.withMessage('Code must be 6 digits.', minLength(6)),
    maxLength: helpers.withMessage('Code must be 6 digits.', maxLength(6)),
    numeric:   helpers.withMessage('Code must contain only digits.', numeric),
  },
}))
const v$ = useVuelidate(rules, form)

function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message
  if (form.errors[field]) return form.errors[field]
  return null
}

async function submit() {
  const valid = await v$.value.$validate()
  if (!valid) {
    toast.error('Please enter your 6-digit authentication code.')
    return
  }
  form.post(route('mfa.challenge.store'), {
    onSuccess: () => {
      // Toast is handled by AppLayout via flash — no duplicate needed here.
    },
    onError:  () => toast.error('Invalid code. Please try again.'),
    onFinish: () => { form.reset('code'); v$.value.$reset() },
  })
}
function logout() { router.post(route('logout')) }
</script>

<style scoped>
.ob-layout { display:flex; width:100%; height:100vh; overflow:hidden; }
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
.ob-panel-right { flex:1; display:flex; flex-direction:column; overflow-y:auto; background-color:var(--surface); }
.ob-panel-right-inner { flex:1; display:flex; flex-direction:column; justify-content:center; padding:clamp(32px,5vh,64px) clamp(28px,5vw,72px); max-width:480px; width:100%; margin:0 auto; }
.mfa-icon { width:72px; height:72px; background:rgba(196,169,106,0.1); border-radius:var(--radius-lg); display:flex; align-items:center; justify-content:center; color:var(--gold-dark); margin-bottom:28px; }
.ob-step-header { margin-bottom:28px; }
.ob-step-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.8px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.ob-step-title { font-family:var(--font-serif); font-size:clamp(22px,2vw + 0.6rem,30px); font-weight:700; color:var(--text); line-height:1.22; margin-bottom:8px; }
.ob-step-subtitle { font-size:13px; color:var(--text-2); line-height:1.55; }
.mfa-code-input { text-align:center; font-family:var(--font-serif); font-size:24px; font-weight:700; letter-spacing:10px; height:56px; }
.ob-btn-full { width:100%; padding:12px 22px; border-radius:var(--radius-full); font-size:13px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:6px; }
.ob-altline { text-align:center; margin-top:20px; font-size:12px; color:var(--text-2); }
.ob-altline-link { color:var(--gold-dark); text-decoration:none; font-weight:600; background:none; border:none; cursor:pointer; font-size:inherit; padding:0; }
.ob-altline-link:hover { text-decoration:underline; }
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
