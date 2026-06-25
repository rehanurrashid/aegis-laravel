<!--
  pages/business-partner/EditProfile.vue — BP/agency profile editor.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="My Profile"
      title="Edit Profile"
      :subtitle="auth.isAgency
        ? 'Your agency profile — visible to practitioners hiring teams.'
        : 'Your freelancer profile — visible to practitioners hiring solo specialists.'"
    />

    <form @submit.prevent="submit">
      <AegisCard :title="auth.isAgency ? 'Agency' : 'Identity'">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">{{ auth.isAgency ? 'Agency name' : 'Display name' }}</label>
            <input v-model="form.display_name" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Tagline</label>
            <input v-model="form.headline" class="form-input" placeholder="e.g. Bookkeeping for solo therapists" />
          </div>
        </div>
        <div v-if="auth.isAgency" class="form-group">
          <label class="form-label">Team size</label>
          <input v-model.number="form.team_size" type="number" min="1" class="form-input" />
        </div>
      </AegisCard>

      <AegisCard title="Services">
        <div class="form-group">
          <label class="form-label">Categories</label>
          <input v-model="form.categories" class="form-input" placeholder="Bookkeeping, marketing, billing — comma-separated" />
        </div>
        <div class="form-group">
          <label class="form-label">Tools</label>
          <input v-model="form.tools" class="form-input" placeholder="QuickBooks, Wave, Stripe, …" />
        </div>
      </AegisCard>

      <AegisCard title="Pricing">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Hourly rate ($)</label>
            <input v-model.number="form.hourly_rate_input" type="number" min="0" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Min engagement ($)</label>
            <input v-model.number="form.min_engagement_input" type="number" min="0" class="form-input" />
          </div>
        </div>
      </AegisCard>

      <AegisCard title="About">
        <textarea v-model="form.bio" class="form-input" rows="8" />
      </AegisCard>

      <div class="form-actions-bar">
        <button type="submit" class="btn btn-primary" :disabled="form.processing">Save changes</button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const props = defineProps({ profile: { type: Object, required: true } })
const auth = useAuthStore()
const toast = useToast()

const form = useForm({
  ...props.profile,
  hourly_rate_input:    props.profile.hourly_rate_cents ? props.profile.hourly_rate_cents / 100 : null,
  min_engagement_input: props.profile.min_engagement_cents ? props.profile.min_engagement_cents / 100 : null,
})

watch(() => form.hourly_rate_input,    (v) => { form.hourly_rate_cents    = v ? Math.round(v * 100) : null })
watch(() => form.min_engagement_input, (v) => { form.min_engagement_cents = v ? Math.round(v * 100) : null })

function submit() { form.put(route('bp.profile.update'), { preserveScroll: true, onSuccess: () => toast.success('Profile saved.') }) }
</script>
