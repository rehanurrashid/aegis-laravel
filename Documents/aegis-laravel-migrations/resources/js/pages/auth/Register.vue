<!--
  pages/auth/Register.vue — create a new Aegis account.

  Route: POST register.store
  Controller validates: display_name, email, password (confirmed), role,
                        bp_type (required if role === business_partner)
  Source: onboarding.php step 1 (role) + account details step
  Layout: AuthLayout
-->
<template>
  <AuthLayout>
    <Head title="Create account" />

    <!-- Step indicator -->
    <div class="auth-steps">
      <div
        v-for="(step, i) in steps"
        :key="i"
        class="auth-step"
        :class="{
          'is-active':   currentStep === i,
          'is-complete': currentStep > i,
        }"
      >
        <div class="auth-step-dot">
          <AegisIcon v-if="currentStep > i" name="check" :size="11" />
          <span v-else>{{ i + 1 }}</span>
        </div>
        <span class="auth-step-label">{{ step.label }}</span>
      </div>
    </div>

    <!-- Step 0: Role selection -->
    <div v-if="currentStep === 0">
      <div class="auth-heading">
        <div class="auth-eyebrow">Step 1 of 2</div>
        <h1 class="auth-title">Select Your Role</h1>
        <p class="auth-subtitle">Choose the role that best describes how you will use Aegis.</p>
      </div>

      <div class="role-cards">
        <button
          v-for="r in roles"
          :key="r.value"
          type="button"
          class="role-card"
          :class="{ 'is-selected': form.role === r.value }"
          @click="selectRole(r.value)"
        >
          <div class="role-card-icon">
            <AegisIcon :name="r.icon" :size="20" />
          </div>
          <div class="role-card-body">
            <div class="role-card-title">{{ r.label }}</div>
            <div class="role-card-desc">{{ r.description }}</div>
          </div>
          <div class="role-card-check">
            <AegisIcon v-if="form.role === r.value" name="check-circle" :size="16" />
          </div>
        </button>
      </div>

      <div v-if="form.role === 'business_partner'" class="auth-bp-type">
        <label class="form-label">Business type</label>
        <div class="auth-option-chips">
          <button
            type="button"
            class="auth-option-chip"
            :class="{ 'is-selected': form.bp_type === 'freelancer' }"
            @click="form.bp_type = 'freelancer'"
          >Freelancer</button>
          <button
            type="button"
            class="auth-option-chip"
            :class="{ 'is-selected': form.bp_type === 'agency' }"
            @click="form.bp_type = 'agency'"
          >Agency</button>
        </div>
        <div v-if="form.errors.bp_type" class="form-error">{{ form.errors.bp_type }}</div>
      </div>

      <div v-if="form.errors.role" class="form-error">{{ form.errors.role }}</div>

      <button
        type="button"
        class="btn btn-primary btn-block"
        :disabled="!canAdvance"
        @click="currentStep = 1"
      >Continue</button>

      <p class="auth-altline">
        Already have an account?
        <a :href="route('login')" class="auth-altline-link">Sign in</a>
      </p>
    </div>

    <!-- Step 1: Account details -->
    <div v-if="currentStep === 1">
      <div class="auth-heading">
        <div class="auth-eyebrow">Step 2 of 2</div>
        <h1 class="auth-title">Create your account</h1>
        <p class="auth-subtitle">You will use these credentials to sign in.</p>
      </div>

      <form class="auth-form" @submit.prevent="submit">
        <div class="form-group">
          <label class="form-label" for="display_name">Full name</label>
          <input
            id="display_name"
            v-model="form.display_name"
            type="text"
            class="form-input"
            :class="{ 'is-error': form.errors.display_name }"
            autocomplete="name"
            autofocus
            placeholder="Dr. Jane Smith"
          />
          <div v-if="form.errors.display_name" class="form-error">{{ form.errors.display_name }}</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="reg-email">Email address</label>
          <input
            id="reg-email"
            v-model="form.email"
            type="email"
            class="form-input"
            :class="{ 'is-error': form.errors.email }"
            autocomplete="email"
            placeholder="your@email.com"
          />
          <div v-if="form.errors.email" class="form-error">{{ form.errors.email }}</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="reg-password">Password</label>
          <input
            id="reg-password"
            v-model="form.password"
            type="password"
            class="form-input"
            :class="{ 'is-error': form.errors.password }"
            autocomplete="new-password"
          />
          <div v-if="form.errors.password" class="form-error">{{ form.errors.password }}</div>
          <div class="form-hint">Minimum 8 characters.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="reg-confirm">Confirm password</label>
          <input
            id="reg-confirm"
            v-model="form.password_confirmation"
            type="password"
            class="form-input"
            autocomplete="new-password"
          />
        </div>

        <div class="auth-button-row">
          <button type="button" class="btn btn-outline" @click="currentStep = 0">Back</button>
          <button type="submit" class="btn btn-primary" :disabled="form.processing">
            {{ form.processing ? 'Creating account…' : 'Create account' }}
          </button>
        </div>
      </form>
    </div>

  </AuthLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'

const currentStep = ref(0)

const steps = [
  { label: 'Your role' },
  { label: 'Account' },
]

const roles = [
  {
    value:       'practitioner',
    label:       'Practitioner Partner',
    icon:        'briefcase-rx',
    description: 'Doctor, therapist, specialist, and health professionals building and protecting their practice.',
  },
  {
    value:       'continuity_steward',
    label:       'Continuity Steward',
    icon:        'shield-check',
    description: 'A trusted professional designated to manage a practitioner\'s continuity plan.',
  },
  {
    value:       'support_steward',
    label:       'Support Steward',
    icon:        'users-network',
    description: 'On-the-ground support for practitioners during critical incidents.',
  },
  {
    value:       'business_partner',
    label:       'Business Partner',
    icon:        'briefcase',
    description: 'Service providers, consultants, and agencies offering professional support to practitioners.',
  },
]

const form = useForm({
  display_name:          '',
  email:                 '',
  password:              '',
  password_confirmation: '',
  role:                  '',
  bp_type:               null,
})

const canAdvance = computed(() => {
  if (!form.role) return false
  if (form.role === 'business_partner' && !form.bp_type) return false
  return true
})

function selectRole(value) {
  form.role    = value
  form.bp_type = null
}

function submit() {
  form.post(route('register.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>
