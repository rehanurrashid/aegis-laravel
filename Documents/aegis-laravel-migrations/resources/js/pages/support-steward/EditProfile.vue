<!--
  pages/support-steward/EditProfile.vue — SS profile editor.
-->
<template>
  <AppLayout>
    <AegisHeroBanner eyebrow="My Profile" title="Edit Profile" subtitle="How practitioners discover you as a Support Steward." />

    <form @submit.prevent="submit">
      <AegisCard title="Identity">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Display name</label>
            <input v-model="form.display_name" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Relationship to practitioner</label>
            <input v-model="form.relationship" class="form-input" placeholder="Spouse, attorney, office manager…" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Headline</label>
          <input v-model="form.headline" class="form-input" placeholder="e.g. Office manager for 8+ years" />
        </div>
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
        <AegisToggle v-model="form.after_hours_ok" label="Available for after-hours alerts" />
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
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisToggle from '@/components/ui/AegisToggle.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({ profile: { type: Object, required: true } })
const toast = useToast()
const form = useForm({ ...props.profile })

function submit() { form.put(route('ss.profile.update'), { preserveScroll: true, onSuccess: () => toast.success('Profile saved.') }) }
</script>
