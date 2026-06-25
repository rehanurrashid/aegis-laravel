<!--
  pages/auth/OnboardingRole.vue — confirm role from session, proceed to register.

  Route: GET onboarding.role → POST onboarding.complete
  Source: onboarding.php step 1 continuation
  Session: onboarding_intent.role set by OnboardingController::submitIntent()
  Layout: AuthLayout
-->
<template>
  <AuthLayout>
    <Head title="Confirm Your Role" />

    <div class="auth-heading">
      <div class="auth-eyebrow">Almost There</div>
      <h1 class="auth-title">Confirm Your Role</h1>
      <p class="auth-subtitle">You selected <strong>{{ roleLabel }}</strong>. Proceed to create your account.</p>
    </div>

    <div class="or-role-summary">
      <div class="or-role-icon">
        <AegisIcon :name="roleIcon" :size="24" />
      </div>
      <div class="or-role-info">
        <div class="or-role-name">{{ roleLabel }}</div>
        <div class="or-role-desc">{{ roleDescription }}</div>
      </div>
    </div>

    <a :href="route('register')" class="btn btn-primary btn-block or-cta">
      Continue to Create Account
    </a>

    <button
      type="button"
      class="btn btn-outline btn-block"
      style="margin-top: 10px;"
      @click="goBack"
    >
      Change Role
    </button>

    <p class="auth-altline">
      Already have an account?
      <a :href="route('login')" class="auth-altline-link">Sign In</a>
    </p>

  </AuthLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'

const page = usePage()
const role = computed(() => page.props.intent?.role ?? '')

const roleMap = {
  practitioner:       { label: 'Practitioner Partner',  icon: 'activity',  desc: 'Building and protecting your practice.' },
  business_partner:   { label: 'Business Partner',       icon: 'briefcase', desc: 'Offering services to health professionals.' },
  continuity_steward: { label: 'Continuity Steward',     icon: 'shield',    desc: 'Supporting practitioner continuity planning.' },
  support_steward:    { label: 'Support Steward',        icon: 'heart',     desc: 'Administrative support for a practitioner.' },
}

const roleLabel       = computed(() => roleMap[role.value]?.label       ?? 'Unknown')
const roleIcon        = computed(() => roleMap[role.value]?.icon        ?? 'user')
const roleDescription = computed(() => roleMap[role.value]?.desc        ?? '')

function goBack() {
  router.visit(route('onboarding.intent'))
}
</script>

<style scoped>
.or-role-summary {
  display: flex;
  align-items: center;
  gap: 16px;
  border: 1.5px solid var(--border);
  border-left: 3px solid var(--gold);
  border-radius: var(--radius);
  padding: 18px 20px;
  background: var(--surface-2);
  margin-bottom: 24px;
}

.or-role-icon {
  width: 48px;
  height: 48px;
  background: rgba(196, 169, 106, 0.1);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--gold-dark);
}

.or-role-name {
  font-family: var(--font-serif);
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 3px;
}

.or-role-desc {
  font-size: 12px;
  color: var(--text-2);
  line-height: 1.5;
}

.btn-primary,
.btn-outline {
  border-radius: var(--radius-full);
  padding: 11px 22px;
  border: 1.5px solid transparent;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
  transition: all var(--transition);
}

.btn-primary {
  background: var(--primary);
  color: var(--text-inverted);
  border-color: var(--primary);
}

.btn-primary:hover {
  background: var(--primary-mid);
  border-color: var(--primary-mid);
}

.btn-outline {
  background: transparent;
  color: var(--text-2);
  border-color: var(--border-dark);
}

.btn-outline:hover {
  border-color: var(--gold);
  color: var(--gold-dark);
}
</style>
