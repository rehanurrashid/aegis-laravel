<!--
  pages/auth/OnboardingIntent.vue — pre-signup intent capture.

  Route: GET onboarding.intent → POST onboarding.intent.store
  Source: onboarding.php step 1 (role selection) — intent only, no account creation
  Layout: AuthLayout
-->
<template>
  <AuthLayout>
    <Head title="Get Started — Aegis" />

    <div class="auth-heading">
      <div class="auth-eyebrow">Practitioner Platform</div>
      <h1 class="auth-title">Who are you joining as?</h1>
      <p class="auth-subtitle">Choose the role that best describes how you'll use Aegis.</p>
    </div>

    <form @submit.prevent="submit" novalidate>
      <div class="role-cards">
        <button
          v-for="r in roles"
          :key="r.value"
          type="button"
          class="role-card"
          :class="{ 'is-selected': form.role === r.value }"
          @click="form.role = r.value"
        >
          <div class="role-card-icon">
            <AegisIcon :name="r.icon" :size="20" />
          </div>
          <div class="role-card-body">
            <div class="role-card-title">{{ r.label }}</div>
            <div class="role-card-desc">{{ r.description }}</div>
            <div class="oi-role-badge" :class="r.badgeVariant">
              <AegisIcon name="check" :size="9" />
              {{ r.badge }}
            </div>
          </div>
          <div class="oi-role-action">
            <AegisIcon name="chevron-right" :size="16" />
          </div>
        </button>
      </div>

      <div v-if="form.errors.role" class="form-error">{{ form.errors.role }}</div>

      <button
        type="submit"
        class="btn btn-primary btn-block"
        :disabled="!form.role || form.processing"
      >Continue</button>
    </form>

    <p class="auth-altline">
      Already have an account?
      <a :href="route('login')" class="auth-altline-link">Sign In</a>
    </p>

  </AuthLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'

const roles = [
  {
    value:        'practitioner',
    label:        'Practitioner Partner',
    icon:         'activity',
    description:  'Doctor, therapist, specialist, and health professionals building and protecting their practice.',
    badge:        'Subscription required',
    badgeVariant: '',
  },
  {
    value:        'business_partner',
    label:        'Business Partner',
    icon:         'briefcase',
    description:  'Billing, legal, consultants, and business service providers working with health professionals.',
    badge:        'Subscription required',
    badgeVariant: '',
  },
  {
    value:        'continuity_steward',
    label:        'Continuity Steward',
    icon:         'shield',
    description:  'Practice succession specialists and licensed professionals supporting practitioner continuity.',
    badge:        'Free via invitation',
    badgeVariant: 'free',
  },
  {
    value:        'support_steward',
    label:        'Support Steward',
    icon:         'heart',
    description:  'Administrative staff, personal representatives, and designated support for practitioners.',
    badge:        'Invitation only',
    badgeVariant: 'free',
  },
]

const form = useForm({ role: '' })

function submit() {
  form.post(route('onboarding.intent.store'))
}
</script>

<style scoped>
.oi-role-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: var(--radius-full);
  background: rgba(196, 169, 106, 0.1);
  color: var(--gold-dark);
  border: 1px solid rgba(196, 169, 106, 0.25);
  margin-top: 8px;
}

.oi-role-badge.free {
  background: rgba(76, 175, 125, 0.1);
  color: var(--green);
  border-color: rgba(76, 175, 125, 0.25);
}

.oi-role-action {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  padding-top: 10px;
  color: var(--text-4);
  transition: color var(--transition);
}

.role-card:hover .oi-role-action,
.role-card.is-selected .oi-role-action {
  color: var(--gold-dark);
}

.btn-primary {
  border-radius: var(--radius-full);
  padding: 11px 22px;
  background: var(--primary);
  color: var(--text-inverted);
  border: 1.5px solid var(--primary);
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-mid);
  border-color: var(--primary-mid);
}
</style>
