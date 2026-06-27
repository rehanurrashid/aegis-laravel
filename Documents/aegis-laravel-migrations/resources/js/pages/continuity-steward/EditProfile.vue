<!--
  pages/continuity-steward/EditProfile.vue — CS profile editor.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="My Practice"
      title="Edit Profile"
      subtitle="How practitioners see you when designating their Continuity Steward."
    />

    <form @submit.prevent="submit">
      <AegisCard title="Identity">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Display name</label>
            <input v-model="form.display_name" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Credentials</label>
            <input v-model="form.credentials" class="form-input" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Headline</label>
          <input v-model="form.headline" class="form-input" placeholder="e.g. Group practice owner with 12 years' experience" />
        </div>
      </AegisCard>

      <AegisCard title="Stewardship capacity">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Max practitioners served</label>
            <input v-model.number="form.capacity_max" type="number" min="1" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Current load</label>
            <input :value="form.current_load" disabled class="form-input" />
          </div>
        </div>
        <AegisToggle v-model="form.accepting_new" label="Accepting new designations" />
      </AegisCard>

      <AegisCard title="Coverage">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">City</label>
            <input v-model="form.city" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">State</label>
            <input v-model="form.state" class="form-input" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Coverage area</label>
          <input v-model="form.coverage_area" class="form-input" placeholder="Statewide, regional, telehealth-only" />
        </div>
      </AegisCard>

      <AegisCard title="About">
        <textarea v-model="form.bio" class="form-input" rows="8" />
      </AegisCard>

      <div class="form-actions-bar">
        <button type="submit" class="btn btn-primary" :disabled="form.processing">
          {{ form.processing ? 'Saving…' : 'Save changes' }}
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisToggle from '@/components/ui/AegisToggle.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({ profile: { type: Object, required: true } })
const toast = useToast()
const form = useForm({ ...props.profile })

function submit() {
  form.put(route('cs.profile.update'), { preserveScroll: true, onSuccess: () => toast.success('Profile saved.') })
}
</script>
