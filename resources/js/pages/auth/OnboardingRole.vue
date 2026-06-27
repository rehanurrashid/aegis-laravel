<!--
  pages/auth/OnboardingRole.vue — confirm role from session, proceed to register.
  Route: GET onboarding.role
  Layout: self-contained split-panel (no AuthLayout)
-->
<template>
  <Head title="Confirm Your Role — Aegis" />

  <div class="ob-layout">
    <div class="ob-panel-left">
      <img src="/aegis-bg.svg" class="ob-panel-left-bg" alt="" aria-hidden="true" />
      <div class="ob-brand-logo"><span class="ob-brand-logo-text">Aegis</span></div>
      <div class="ob-panel-left-content">
        <div class="ob-panel-left-eyebrow">Practitioner Platform</div>
        <h1 class="ob-panel-left-title">You're one step away from protecting your practice.</h1>
        <p class="ob-panel-left-body">Confirm your role and create your account. You can always update your profile and add additional context after signing up.</p>
        <div class="ob-panel-features">
          <div class="ob-panel-feature">
            <div class="ob-panel-feature-icon"><AegisIcon name="shield" :size="15" /></div>
            <div class="ob-panel-feature-text"><strong>Continuity Plan</strong>Start building your practice protection plan immediately.</div>
          </div>
          <div class="ob-panel-feature">
            <div class="ob-panel-feature-icon"><AegisIcon name="users" :size="15" /></div>
            <div class="ob-panel-feature-text"><strong>Invite Your Network</strong>Add stewards and business partners after account creation.</div>
          </div>
        </div>
      </div>
      <div class="ob-panel-left-footer"><p>© {{ year }} Aegis Platform. All rights reserved.</p></div>
    </div>

    <div class="ob-panel-right">
      <div class="ob-panel-right-inner">
        <div class="ob-step-header">
          <div class="ob-step-eyebrow">Almost There</div>
          <h2 class="ob-step-title">Confirm Your Role</h2>
          <p class="ob-step-subtitle">You selected <strong>{{ roleLabel }}</strong>. Proceed to create your account.</p>
        </div>

        <div class="or-role-card">
          <div class="or-role-icon"><AegisIcon :name="roleIcon" :size="24" /></div>
          <div class="or-role-info">
            <div class="or-role-name">{{ roleLabel }}</div>
            <div class="or-role-desc">{{ roleDescription }}</div>
          </div>
        </div>

        <a :href="route('register')" class="btn btn-primary ob-btn-full">
          Continue to Create Account
          <AegisIcon name="arrow-right" :size="14" />
        </a>
        <button type="button" class="btn btn-outline ob-btn-full" style="margin-top:10px;" @click="goBack">Change Role</button>

        <p class="ob-altline">Already have an account? <a :href="route('login')" class="ob-altline-link">Sign In</a></p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'

const year = new Date().getFullYear()
const page = usePage()
const role = computed(() => page.props.intent?.role ?? '')
const roleMap = {
  practitioner:       { label: 'Practitioner Partner',  icon: 'activity',  desc: 'Building and protecting your healthcare practice.' },
  business_partner:   { label: 'Business Partner',       icon: 'briefcase', desc: 'Offering professional services to health practitioners.' },
  continuity_steward: { label: 'Continuity Steward',     icon: 'shield',    desc: 'Supporting practitioner continuity and succession planning.' },
  support_steward:    { label: 'Support Steward',        icon: 'heart',     desc: 'Providing administrative support for a practitioner.' },
}
const roleLabel       = computed(() => roleMap[role.value]?.label ?? 'Unknown')
const roleIcon        = computed(() => roleMap[role.value]?.icon  ?? 'user')
const roleDescription = computed(() => roleMap[role.value]?.desc  ?? '')
function goBack() { router.visit(route('onboarding.intent')) }
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
.ob-step-header { margin-bottom:24px; }
.ob-step-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.8px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.ob-step-title { font-family:var(--font-serif); font-size:clamp(22px,2vw + 0.6rem,30px); font-weight:700; color:var(--text); line-height:1.22; margin-bottom:8px; }
.ob-step-subtitle { font-size:13.5px; color:var(--text-2); line-height:1.55; }
.ob-step-subtitle strong { color:var(--text); font-weight:600; }
.or-role-card { display:flex; align-items:center; gap:16px; border:1.5px solid var(--border); border-left:3px solid var(--gold); border-radius:var(--radius); padding:18px 20px; background:var(--surface-2); margin-bottom:24px; }
.or-role-icon { width:48px; height:48px; background:rgba(196,169,106,0.1); border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--gold-dark); }
.or-role-name { font-family:var(--font-serif); font-size:15px; font-weight:700; color:var(--text); margin-bottom:3px; }
.or-role-desc { font-size:12px; color:var(--text-2); line-height:1.5; }
.btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:11px 22px; font-family:var(--font-sans); font-size:13px; font-weight:700; border-radius:var(--radius-full); border:1.5px solid transparent; text-decoration:none; cursor:pointer; transition:all var(--transition); -webkit-appearance:none; outline:none; }
.btn-primary { background:var(--primary); color:var(--text-inverted); border-color:var(--primary); }
.btn-primary:hover { background:var(--primary-mid); border-color:var(--primary-mid); }
.btn-outline { background:transparent; color:var(--text-2); border-color:var(--border-dark); }
.btn-outline:hover { border-color:var(--gold); color:var(--gold-dark); }
.ob-btn-full { width:100%; }
.ob-altline { text-align:center; margin-top:20px; font-size:12.5px; color:var(--text-2); }
.ob-altline-link { color:var(--gold-dark); text-decoration:none; font-weight:600; }
.ob-altline-link:hover { text-decoration:underline; }
</style>
