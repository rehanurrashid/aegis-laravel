<!--
  pages/auth/VerifyEmail.vue — post-registration email verification notice.
  Route: GET /email/verify  (verification.notice)
-->
<template>
  <Head title="Verify your email — Aegis" />

  <div class="ob-layout">
    <!-- Left panel (same as Register) -->
    <div class="ob-panel-left">
      <img src="/aegis-bg.svg" class="ob-panel-left-bg" alt="" aria-hidden="true" />
      <div class="ob-brand-logo">
        <span class="ob-brand-logo-text">Aegis</span>
      </div>
      <div class="ob-panel-left-content">
        <div class="ob-panel-left-eyebrow">Email Verification</div>
        <h1 class="ob-panel-left-title">One more step to protect your practice.</h1>
        <p class="ob-panel-left-body">
          Verifying your email keeps your Aegis account secure and ensures you receive
          important notifications about your continuity plan.
        </p>
      </div>
      <div class="ob-panel-left-footer">
        <p>© {{ year }} Aegis Platform. All rights reserved.</p>
      </div>
    </div>

    <!-- Right panel -->
    <div class="ob-panel-right">
      <div class="ob-panel-right-inner">

        <div class="verify-icon">
          <AegisIcon name="mail" :size="40" />
        </div>

        <div class="ob-step-header">
          <div class="ob-step-eyebrow">Check your inbox</div>
          <h2 class="ob-step-title">Verify your email address</h2>
          <p class="ob-step-subtitle">
            We sent a verification link to <strong>{{ email }}</strong>.<br />
            Click the link in that email to activate your account.
          </p>
        </div>

        <div v-if="flash.success" class="verify-success">
          <AegisIcon name="check-circle" :size="15" />
          {{ flash.success }}
        </div>

        <div v-if="errors.email" class="form-error">{{ errors.email }}</div>

        <button
          type="button"
          class="btn btn-primary ob-btn-full"
          :disabled="resendForm.processing || cooldown > 0"
          @click="resend"
        >
          <span v-if="cooldown > 0">Resend in {{ cooldown }}s</span>
          <span v-else-if="resendForm.processing">Sending…</span>
          <span v-else>Resend verification email</span>
        </button>

        <div class="ob-signin-row">
          <a :href="route('login')" class="ob-signin-link">Sign in with a different account</a>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onUnmounted } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'

const props  = defineProps({ email: String })
const page   = usePage()
const flash  = page.props.flash ?? {}
const errors = page.props.errors ?? {}
const year   = new Date().getFullYear()

const toast      = useToast()
const resendForm = useForm({})
const cooldown   = ref(0)
let timer        = null

function resend() {
  resendForm.post(route('verification.resend'), {
    onSuccess: () => { startCooldown(60); toast.success('Verification email sent.') },
    onError:   () => toast.error('Could not resend. Please try again shortly.'),
  })
}

function startCooldown(seconds) {
  cooldown.value = seconds
  timer = setInterval(() => {
    cooldown.value--
    if (cooldown.value <= 0) clearInterval(timer)
  }, 1000)
}

onUnmounted(() => clearInterval(timer))
</script>

<style scoped>
.ob-layout { display:flex; width:100%; min-height:100vh; overflow:hidden; }

.ob-panel-left {
  width:42%; background:#1a140d; position:relative; display:flex;
  flex-direction:column; justify-content:space-between;
  padding:clamp(20px,4vh,48px) clamp(28px,4vw,52px); overflow:hidden; flex-shrink:0;
}
.ob-panel-left-bg { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; z-index:0; pointer-events:none; }
.ob-brand-logo { position:relative; z-index:1; }
.ob-brand-logo-text { font-family:var(--font-serif); font-size:26px; font-weight:700; color:var(--text-inverted); }
.ob-panel-left-content { position:relative; z-index:1; flex:1; display:flex; flex-direction:column; justify-content:center; padding:clamp(12px,2.5vh,40px) 0; }
.ob-panel-left-eyebrow { font-size:10px; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,0.65); margin-bottom:clamp(8px,1.5vh,16px); }
.ob-panel-left-title { font-family:var(--font-serif); font-size:clamp(24px,2.4vw + 0.8rem,36px); font-weight:700; color:var(--text-inverted); line-height:1.22; margin-bottom:clamp(10px,1.8vh,20px); }
.ob-panel-left-body { font-size:13px; color:rgba(255,255,255,0.78); line-height:1.65; max-width:340px; }
.ob-panel-left-footer p { font-size:11px; color:rgba(255,255,255,0.45); line-height:1.5; position:relative; z-index:1; }

.ob-panel-right { flex:1; display:flex; flex-direction:column; overflow-y:auto; background-color:var(--surface); }
.ob-panel-right-inner { flex:1; display:flex; flex-direction:column; padding:52px 64px; max-width:520px; width:100%; margin:0 auto; }

.verify-icon { width:72px; height:72px; background:rgba(196,169,106,0.1); border-radius:var(--radius-lg); display:flex; align-items:center; justify-content:center; color:var(--gold-dark); margin-bottom:32px; }

.ob-step-header { margin-bottom:32px; }
.ob-step-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.8px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.ob-step-title { font-family:var(--font-serif); font-size:26px; font-weight:700; color:var(--text); line-height:1.25; margin-bottom:8px; }
.ob-step-subtitle { font-size:13px; color:var(--text-2); line-height:1.55; }
.ob-step-subtitle strong { color:var(--text); font-weight:600; }

.verify-success { display:flex; align-items:center; gap:8px; font-size:13px; color:var(--green); background:rgba(76,175,125,0.08); border:1px solid rgba(76,175,125,0.2); border-radius:var(--radius-sm); padding:10px 14px; margin-bottom:20px; }

.ob-btn-full { width:100%; padding:12px 22px; border-radius:var(--radius-full); font-size:13px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:6px; }

.ob-signin-row { text-align:center; margin-top:24px; font-size:12px; color:var(--text-2); }
.ob-signin-link { color:var(--gold-dark); text-decoration:none; font-weight:600; }
.ob-signin-link:hover { text-decoration:underline; }
</style>
