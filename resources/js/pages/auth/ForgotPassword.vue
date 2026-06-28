<!--
  pages/auth/ForgotPassword.vue — request a password reset link.
  Route: POST password.email
  Layout: self-contained split-panel (no AuthLayout)
-->
<template>
  <Head title="Reset Password — Aegis" />

  <div class="ob-layout">
    <div class="ob-panel-left">
      <img src="/aegis-bg.svg" class="ob-panel-left-bg" alt="" aria-hidden="true" />
      <div class="ob-brand-logo"><span class="ob-brand-logo-text">Aegis</span></div>
      <div class="ob-panel-left-content">
        <div class="ob-panel-left-eyebrow">Account Recovery</div>
        <h1 class="ob-panel-left-title">Regain access to your practice.</h1>
        <p class="ob-panel-left-body">Enter your registered email and we'll send a secure link to reset your password. The link expires in 2 hours.</p>
        <div class="ob-panel-features">
          <div class="ob-panel-feature">
            <div class="ob-panel-feature-icon"><AegisIcon name="shield" :size="15" /></div>
            <div class="ob-panel-feature-text"><strong>Secure reset link</strong>Time-limited and single-use for your protection.</div>
          </div>
          <div class="ob-panel-feature">
            <div class="ob-panel-feature-icon"><AegisIcon name="lock" :size="15" /></div>
            <div class="ob-panel-feature-text"><strong>Your data stays safe</strong>No account changes until you confirm the new password.</div>
          </div>
        </div>
      </div>
      <div class="ob-panel-left-footer"><p>© {{ year }} Aegis Platform. All rights reserved.</p></div>
    </div>

    <div class="ob-panel-right">
      <div class="ob-panel-right-inner">

        <template v-if="sent">
          <div class="fp-success-icon"><AegisIcon name="mail" :size="36" /></div>
          <div class="ob-step-header">
            <div class="ob-step-eyebrow">Email Sent</div>
            <h2 class="ob-step-title">Check your inbox</h2>
            <p class="ob-step-subtitle">We sent a reset link to <strong>{{ submittedEmail }}</strong>. It expires in 2 hours. Check your spam folder if you don't see it.</p>
          </div>
          <a :href="route('login')" class="btn btn-primary ob-btn-full">Back to Sign In</a>
          <p class="ob-altline">Didn't receive it? <button type="button" class="ob-altline-link" @click="sent = false">Try again</button></p>
        </template>

        <template v-else>
          <div class="ob-step-header">
            <div class="ob-step-eyebrow">Account Recovery</div>
            <h2 class="ob-step-title">Reset your password</h2>
            <p class="ob-step-subtitle">Enter your email and we'll send you a link to create a new password.</p>
          </div>
          <form @submit.prevent="submit" novalidate>
            <div class="form-group">
              <label class="form-label" for="fp-email">Email Address</label>
              <input id="fp-email" v-model="form.email" type="email" class="form-input" :class="{ 'is-error': fieldError('email') }" autocomplete="email" autofocus placeholder="your@email.com" @blur="v$.email.$touch()" />
              <div v-if="fieldError('email')" class="form-error">{{ fieldError('email') }}</div>
            </div>
            <button type="submit" class="btn btn-primary ob-btn-full" :disabled="form.processing">
              {{ form.processing ? 'Sending…' : 'Send Reset Link' }}
            </button>
          </form>
          <p class="ob-altline">Remember your password? <a :href="route('login')" class="ob-altline-link">Sign in</a></p>
        </template>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { useVuelidate } from '@vuelidate/core'
import { required, email, helpers } from '@vuelidate/validators'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const year  = new Date().getFullYear()
const sent  = ref(false)
const submittedEmail = ref('')
const form  = useForm({ email: '' })

// ── Vuelidate ──────────────────────────────────────────────────────────
const rules = computed(() => ({
  email: {
    required: helpers.withMessage('Email is required.', required),
    email:    helpers.withMessage('Enter a valid email address.', email),
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
    toast.error('Please enter a valid email address.')
    return
  }
  submittedEmail.value = form.email
  form.post(route('password.email'), {
    onSuccess: () => { sent.value = true; v$.value.$reset() },
    onError:   () => toast.error('Could not send reset link. Please try again.'),
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
.ob-panel-left-body { font-size:13.5px; color:rgba(255,255,255,0.78); line-height:1.65; max-width:340px; }
.ob-panel-features { display:flex; flex-direction:column; gap:clamp(8px,1.4vh,14px); margin-top:clamp(16px,2.8vh,36px); }
.ob-panel-feature { display:flex; align-items:flex-start; gap:12px; }
.ob-panel-feature-icon { width:28px; height:28px; background:rgba(196,169,106,0.15); border-radius:6px; display:flex; align-items:center; justify-content:center; color:var(--gold); flex-shrink:0; }
.ob-panel-feature-text { font-size:12.5px; color:rgba(255,255,255,0.72); line-height:1.5; }
.ob-panel-feature-text strong { color:rgba(255,255,255,0.92); display:block; margin-bottom:1px; }
.ob-panel-left-footer { position:relative; z-index:1; }
.ob-panel-left-footer p { font-size:11px; color:rgba(255,255,255,0.38); line-height:1.5; }
.ob-panel-right { flex:1; display:flex; flex-direction:column; overflow-y:auto; background-color:var(--surface); }
.ob-panel-right-inner { flex:1; display:flex; flex-direction:column; justify-content:center; padding:clamp(32px,5vh,64px) clamp(28px,5vw,72px); max-width:480px; width:100%; margin:0 auto; }
.fp-success-icon { width:72px; height:72px; background:rgba(196,169,106,0.1); border-radius:var(--radius-lg); display:flex; align-items:center; justify-content:center; color:var(--gold-dark); margin-bottom:28px; }
.ob-step-header { margin-bottom:28px; }
.ob-step-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.8px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.ob-step-title { font-family:var(--font-serif); font-size:clamp(22px,2vw + 0.6rem,30px); font-weight:700; color:var(--text); line-height:1.22; margin-bottom:8px; }
.ob-step-subtitle { font-size:13.5px; color:var(--text-2); line-height:1.55; }
.ob-step-subtitle strong { color:var(--text); font-weight:600; }
.btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:11px 22px; font-family:var(--font-sans); font-size:13px; font-weight:700; border-radius:var(--radius-full); border:1.5px solid transparent; cursor:pointer; transition:all var(--transition); -webkit-appearance:none; outline:none; }
.btn-primary { background:var(--primary); color:var(--text-inverted); border-color:var(--primary); }
.btn-primary:hover:not(:disabled) { background:var(--primary-mid); border-color:var(--primary-mid); }
.btn-primary:disabled { opacity:0.5; cursor:not-allowed; }
.ob-btn-full { width:100%; margin-top:4px; }
.ob-altline { text-align:center; margin-top:20px; font-size:12.5px; color:var(--text-2); }
.ob-altline-link { color:var(--gold-dark); text-decoration:none; font-weight:600; background:none; border:none; cursor:pointer; font-size:inherit; padding:0; }
.ob-altline-link:hover { text-decoration:underline; }
</style>
