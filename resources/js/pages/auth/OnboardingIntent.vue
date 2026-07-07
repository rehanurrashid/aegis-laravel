<!--
  pages/auth/OnboardingIntent.vue — pre-signup intent capture.
  Route: GET onboarding.intent → POST onboarding.intent.store
  Layout: self-contained split-panel (no AuthLayout)
-->
<template>
  <Head title="Get Started — Aegis" />

  <div class="ob-layout">
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
      </div>
      <div class="ob-panel-left-footer"><p>© {{ year }} Aegis Platform. All rights reserved.</p></div>
    </div>

    <div class="ob-panel-right">
      <div class="ob-panel-right-inner">
        <div class="ob-step-header">
          <div class="ob-step-eyebrow">Getting Started</div>
          <h2 class="ob-step-title">Who are you joining as?</h2>
          <p class="ob-step-subtitle">Choose the role that best describes how you'll use Aegis.</p>
        </div>

        <form @submit.prevent="submit" novalidate>
          <div class="role-cards">
            <button v-for="r in roles" :key="r.value" type="button" class="role-card" :class="{ 'is-selected': form.role === r.value }" @click="form.role = r.value">
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
          <div v-if="fieldError('role')" class="form-error">{{ fieldError('role') }}</div>
          <button type="submit" class="btn btn-primary ob-btn-full" :disabled="!form.role || form.processing">Continue</button>
        </form>

        <p class="ob-altline">Already have an account? <a :href="route('login')" class="ob-altline-link">Sign In</a></p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { useVuelidate } from '@vuelidate/core'
import { required, helpers } from '@vuelidate/validators'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const year  = new Date().getFullYear()

const roles = [
  { value: 'practitioner',       label: 'Practitioner Partner', icon: 'activity',  description: 'Doctor, therapist, specialist, and health professionals building and protecting their practice.',          badge: 'Subscription required', badgeVariant: '' },
  { value: 'business_partner',   label: 'Business Partner',      icon: 'briefcase', description: 'Billing, legal, consultants, and business service providers working with health professionals.',           badge: 'Subscription required', badgeVariant: '' },
  { value: 'continuity_steward', label: 'Continuity Steward',    icon: 'shield',    description: 'Practice succession specialists and licensed professionals supporting practitioner continuity.',           badge: 'Free via invitation',   badgeVariant: 'free' },
  { value: 'support_steward',    label: 'Support Steward',       icon: 'heart',     description: 'Administrative staff, personal representatives, and designated support for practitioners.',                badge: 'Invitation only',       badgeVariant: 'free' },
]

const form = useForm({ role: '' })

// ── Vuelidate ──────────────────────────────────────────────────────────
const rules = computed(() => ({
  role: {
    required: helpers.withMessage('Select a role to continue.', required),
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
    toast.error('Please select a role to continue.')
    return
  }
  form.post(route('onboarding.intent.store'), {
    onError: () => toast.error('Something went wrong. Please try again.'),
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
.ob-panel-left-body { font-size:13px; color:rgba(255,255,255,0.78); line-height:1.65; max-width:340px; }
.ob-panel-features { display:flex; flex-direction:column; gap:clamp(8px,1.4vh,14px); margin-top:clamp(16px,2.8vh,36px); }
.ob-panel-feature { display:flex; align-items:flex-start; gap:12px; }
.ob-panel-feature-icon { width:28px; height:28px; background:rgba(196,169,106,0.15); border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; color:var(--gold-dark); flex-shrink:0; }
.ob-panel-feature-text { font-size:12px; color:rgba(255,255,255,0.72); line-height:1.5; }
.ob-panel-feature-text strong { color:rgba(255,255,255,0.92); display:block; margin-bottom:1px; }
.ob-panel-left-footer { position:relative; z-index:1; }
.ob-panel-left-footer p { font-size:11px; color:rgba(255,255,255,0.38); line-height:1.5; }
.ob-panel-right { flex:1; display:flex; flex-direction:column; overflow-y:auto; background-color:var(--surface); }
.ob-panel-right-inner { flex:1; display:flex; flex-direction:column; justify-content:center; padding:clamp(32px,5vh,64px) clamp(28px,5vw,72px); max-width:560px; width:100%; margin:0 auto; }
.ob-step-header { margin-bottom:24px; }
.ob-step-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.8px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.ob-step-title { font-family:var(--font-serif); font-size:clamp(22px,2vw + 0.6rem,30px); font-weight:700; color:var(--text); line-height:1.22; margin-bottom:8px; }
.ob-step-subtitle { font-size:13px; color:var(--text-2); line-height:1.55; }
.role-cards { display:flex; flex-direction:column; gap:10px; margin-bottom:20px; }
.role-card { display:flex; align-items:center; gap:14px; width:100%; padding:16px 18px; border:1px solid var(--border); border-radius:var(--radius); background:var(--surface); cursor:pointer; text-align:left; transition:all var(--transition); -webkit-appearance:none; }
.role-card:hover { border-color:var(--gold); background:rgba(196,169,106,0.04); }
.role-card.is-selected { border-color:var(--gold); background:rgba(196,169,106,0.06); }
.role-card-icon { width:40px; height:40px; background:rgba(196,169,106,0.1); border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; color:var(--gold-dark); flex-shrink:0; }
.role-card.is-selected .role-card-icon { background:rgba(196,169,106,0.18); }
.role-card-body { flex:1; min-width:0; }
.role-card-title { font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px; }
.role-card-desc { font-size:12px; color:var(--text-2); line-height:1.4; }
.oi-role-badge { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:600; padding:3px 9px; border-radius:var(--radius-full); margin-top:7px; background:rgba(196,169,106,0.1); color:var(--gold-dark); border:1px solid rgba(196,169,106,0.25); }
.oi-role-badge.free { background:rgba(76,175,125,0.1); color:var(--green); border-color:rgba(76,175,125,0.25); }
.oi-role-action { flex-shrink:0; display:flex; align-items:center; color:var(--text-4); transition:color var(--transition); }
.role-card:hover .oi-role-action, .role-card.is-selected .oi-role-action { color:var(--gold-dark); }
.ob-btn-full { width:100%; padding:12px 22px; border-radius:var(--radius-full); font-size:13px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:6px; }
.ob-altline { text-align:center; margin-top:20px; font-size:12px; color:var(--text-2); }
.ob-altline-link { color:var(--gold-dark); text-decoration:none; font-weight:600; }
.ob-altline-link:hover { text-decoration:underline; }
</style>
