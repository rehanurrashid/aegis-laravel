<!--
  pages/auth/ResetPassword.vue — set a new password with the reset token.
  Route: POST password.update  Props: token, email
  Layout: self-contained split-panel (no AuthLayout)
-->
<template>
  <Head title="Set New Password — Aegis" />

  <div class="ob-layout">
    <div class="ob-panel-left">
      <img src="/aegis-bg.svg" class="ob-panel-left-bg" alt="" aria-hidden="true" />
      <div class="ob-brand-logo"><span class="ob-brand-logo-text">Aegis</span></div>
      <div class="ob-panel-left-content">
        <div class="ob-panel-left-eyebrow">Account Recovery</div>
        <h1 class="ob-panel-left-title">Create a strong new password.</h1>
        <p class="ob-panel-left-body">Choose a password that's unique to Aegis and difficult to guess. A strong password keeps your continuity plan and client data secure.</p>
        <div class="ob-panel-features">
          <div class="ob-panel-feature">
            <div class="ob-panel-feature-icon"><AegisIcon name="lock" :size="15" /></div>
            <div class="ob-panel-feature-text"><strong>8+ characters minimum</strong>Mix letters, numbers, and symbols for maximum security.</div>
          </div>
          <div class="ob-panel-feature">
            <div class="ob-panel-feature-icon"><AegisIcon name="shield-check" :size="15" /></div>
            <div class="ob-panel-feature-text"><strong>Single-use link</strong>This reset link expires once used or after 2 hours.</div>
          </div>
          <div class="ob-panel-feature">
            <div class="ob-panel-feature-icon"><AegisIcon name="key" :size="15" /></div>
            <div class="ob-panel-feature-text"><strong>All sessions cleared</strong>Existing sessions are signed out automatically.</div>
          </div>
        </div>
      </div>
      <div class="ob-panel-left-footer"><p>© {{ year }} Aegis Platform. All rights reserved.</p></div>
    </div>

    <div class="ob-panel-right">
      <div class="ob-panel-right-inner">
        <div class="ob-step-header">
          <div class="ob-step-eyebrow">Account Recovery</div>
          <h2 class="ob-step-title">Set a new password</h2>
          <p class="ob-step-subtitle">Choose a strong password for your Aegis account.</p>
        </div>

        <form @submit.prevent="submit" novalidate>
          <div class="form-group">
            <label class="form-label" for="rp-email">Email Address</label>
            <input id="rp-email" v-model="form.email" type="email" class="form-input" :class="{ 'is-error': form.errors.email }" autocomplete="email" placeholder="your@email.com" />
            <div v-if="form.errors.email" class="form-error">{{ form.errors.email }}</div>
          </div>

          <div class="form-group">
            <label class="form-label" for="rp-password">New Password</label>
            <div class="rp-input-wrap">
              <input id="rp-password" v-model="form.password" :type="showPassword ? 'text' : 'password'" class="form-input rp-has-toggle" :class="{ 'is-error': form.errors.password }" autocomplete="new-password" placeholder="Create a strong password" autofocus @input="checkStrength" />
              <button type="button" class="rp-toggle" :data-tooltip="showPassword ? 'Hide password' : 'Show password'" @click="showPassword = !showPassword">
                <AegisIcon :name="showPassword ? 'eye-off' : 'eye'" :size="15" />
              </button>
            </div>
            <div v-if="form.errors.password" class="form-error">{{ form.errors.password }}</div>
            <div class="rp-reqs">
              <div class="rp-req" :class="{ valid: reqs.length, invalid: form.password && !reqs.length }">
                <AegisIcon :name="reqs.length ? 'check-circle' : 'circle'" :size="11" /> 8+ characters
              </div>
              <div class="rp-req" :class="{ valid: reqs.uppercase, invalid: form.password && !reqs.uppercase }">
                <AegisIcon :name="reqs.uppercase ? 'check-circle' : 'circle'" :size="11" /> Uppercase
              </div>
              <div class="rp-req" :class="{ valid: reqs.number, invalid: form.password && !reqs.number }">
                <AegisIcon :name="reqs.number ? 'check-circle' : 'circle'" :size="11" /> Number
              </div>
              <div class="rp-req" :class="{ valid: reqs.special, invalid: form.password && !reqs.special }">
                <AegisIcon :name="reqs.special ? 'check-circle' : 'circle'" :size="11" /> Special character
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="rp-confirm">Confirm New Password</label>
            <div class="rp-input-wrap">
              <input id="rp-confirm" v-model="form.password_confirmation" :type="showConfirm ? 'text' : 'password'" class="form-input rp-has-toggle" :class="{ 'is-error': passwordMismatch }" autocomplete="new-password" placeholder="Re-enter your password" />
              <button type="button" class="rp-toggle" :data-tooltip="showConfirm ? 'Hide password' : 'Show password'" @click="showConfirm = !showConfirm">
                <AegisIcon :name="showConfirm ? 'eye-off' : 'eye'" :size="15" />
              </button>
            </div>
            <div v-if="passwordMismatch" class="form-error">
              <AegisIcon name="x-circle" :size="11" /> Passwords do not match
            </div>
          </div>

          <div v-if="form.errors.token" class="rp-alert">
            <AegisIcon name="alert-circle" :size="15" /><span>{{ form.errors.token }}</span>
          </div>

          <button type="submit" class="btn btn-primary ob-btn-full" :disabled="form.processing || passwordMismatch || !allReqsMet">
            {{ form.processing ? 'Saving…' : 'Save New Password' }}
          </button>
        </form>

        <p class="ob-altline"><a :href="route('login')" class="ob-altline-link">Back to Sign In</a></p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'

const year = new Date().getFullYear()
const props = defineProps({ token: { type: String, required: true }, email: { type: String, default: '' } })
const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' })
const showPassword = ref(false)
const showConfirm  = ref(false)
const reqs = reactive({ length: false, uppercase: false, number: false, special: false })
const allReqsMet   = computed(() => Object.values(reqs).every(Boolean))
const passwordMismatch = computed(() => form.password_confirmation.length > 0 && form.password !== form.password_confirmation)

function checkStrength() {
  const pw = form.password
  reqs.length = pw.length >= 8; reqs.uppercase = /[A-Z]/.test(pw)
  reqs.number = /[0-9]/.test(pw); reqs.special = /[^A-Za-z0-9]/.test(pw)
}
function submit() {
  form.post(route('password.update'), { onFinish: () => form.reset('password', 'password_confirmation') })
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
.ob-step-header { margin-bottom:28px; }
.ob-step-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.8px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.ob-step-title { font-family:var(--font-serif); font-size:clamp(22px,2vw + 0.6rem,30px); font-weight:700; color:var(--text); line-height:1.22; margin-bottom:8px; }
.ob-step-subtitle { font-size:13.5px; color:var(--text-2); line-height:1.55; }
.rp-input-wrap { position:relative; }
.rp-has-toggle { padding-right:42px; }
.rp-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; padding:4px; cursor:pointer; color:var(--text-2); display:flex; align-items:center; transition:color var(--transition); }
.rp-toggle:hover { color:var(--gold-dark); }
.rp-reqs { display:flex; flex-wrap:wrap; gap:6px 14px; margin-top:10px; }
.rp-req { display:inline-flex; align-items:center; gap:5px; font-size:11px; color:var(--text-3); transition:color var(--transition); }
.rp-req.valid { color:var(--green); }
.rp-req.invalid { color:var(--red); }
.rp-alert { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:var(--radius-sm); margin-bottom:16px; font-size:13px; background:rgba(220,53,53,0.06); border:1px solid rgba(220,53,53,0.2); color:var(--red); box-shadow:var(--shadow-sm); }
.btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:11px 22px; font-family:var(--font-sans); font-size:13px; font-weight:700; border-radius:var(--radius-full); border:1.5px solid transparent; cursor:pointer; transition:all var(--transition); -webkit-appearance:none; outline:none; }
.btn-primary { background:var(--primary); color:var(--text-inverted); border-color:var(--primary); }
.btn-primary:hover:not(:disabled) { background:var(--primary-mid); border-color:var(--primary-mid); }
.btn-primary:disabled { opacity:0.5; cursor:not-allowed; }
.ob-btn-full { width:100%; }
.ob-altline { text-align:center; margin-top:20px; font-size:12.5px; color:var(--text-2); }
.ob-altline-link { color:var(--gold-dark); text-decoration:none; font-weight:600; }
.ob-altline-link:hover { text-decoration:underline; }
</style>
