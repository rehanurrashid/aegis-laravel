<!--
  pages/provider/EditProfile.vue — practitioner profile editor.

  Replaces provider-portal/edit-profile.php. Six form sections:
    1. Identity (name, headline, credentials)
    2. Practice (specialty, modalities, languages)
    3. Location (city, state, in-person availability)
    4. Visibility (public profile toggle, network discoverability)
    5. Contact (email, phone, website)
    6. About (longform bio)
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="My Practice"
      title="Edit Profile"
      subtitle="Your public profile reflects how the network sees you."
    >
      <template #actions>
        <a :href="route('public.provider', { slug: form.slug })" class="btn btn-outline">
          <AegisIcon name="external-link" :size="13" />
          <span>View public profile</span>
        </a>
      </template>
    </AegisHeroBanner>

    <form @submit.prevent="submit">
      <AegisCard title="Identity">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Display name <span class="req">*</span></label>
            <input v-model="form.display_name" type="text" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Credentials</label>
            <input v-model="form.credentials" type="text" class="form-input" placeholder="e.g. PsyD, LMFT" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Headline</label>
          <input
            v-model="form.headline"
            type="text"
            class="form-input"
            placeholder="e.g. Trauma-informed therapist for adults"
          />
        </div>
      </AegisCard>

      <AegisCard title="Practice">
        <div class="form-group">
          <label class="form-label">Specialty</label>
          <input v-model="form.specialty" type="text" class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label">Modalities</label>
          <input
            v-model="form.modalities"
            type="text"
            class="form-input"
            placeholder="EMDR, IFS, CBT — comma-separated"
          />
        </div>
        <div class="form-group">
          <label class="form-label">Languages</label>
          <input
            v-model="form.languages"
            type="text"
            class="form-input"
            placeholder="English, Spanish — comma-separated"
          />
        </div>
      </AegisCard>

      <AegisCard title="Location">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">City</label>
            <input v-model="form.city" type="text" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">State</label>
            <input v-model="form.state" type="text" class="form-input" />
          </div>
        </div>
        <AegisToggle
          v-model="form.in_person"
          label="Available for in-person sessions"
          description="Shows your city on the public profile."
        />
        <AegisToggle
          v-model="form.telehealth"
          label="Available via telehealth"
        />
      </AegisCard>

      <AegisCard title="Visibility">
        <AegisToggle
          v-model="form.public_profile"
          label="Public profile is published"
          description="When off, your profile is only visible to your stewards."
        />
        <AegisToggle
          v-model="form.network_accepting"
          label="Accepting new referrals"
        />
      </AegisCard>

      <AegisCard title="Contact">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Email</label>
            <input v-model="form.contact_email" type="email" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input v-model="form.contact_phone" type="tel" class="form-input" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Website</label>
          <input v-model="form.website" type="url" class="form-input" placeholder="https://" />
        </div>
      </AegisCard>

      <AegisCard title="About">
        <div class="form-group">
          <textarea
            v-model="form.bio"
            class="form-input"
            rows="8"
            placeholder="A short, plain-language description of your practice."
          ></textarea>
        </div>
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

const props = defineProps({
  profile: { type: Object, required: true },
})

const toast = useToast()
const form = useForm({ ...props.profile })

function submit() {
  form.put(route('provider.profile.update'), {
    preserveScroll: true,
    onSuccess: () => toast.success('Profile saved.'),
  })
}
</script>
