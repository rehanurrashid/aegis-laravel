<!--
  pages/public/ProviderProfile.vue — unauthenticated practitioner profile.
-->
<template>
  <PublicLayout>
    <article class="public-profile">
      <header class="public-profile-head">
        <ProfileStrip :person="profile" kind="provider" large />
        <div class="public-profile-meta">
          <AegisBadge v-if="profile.in_person" label="In-person" variant="green" />
          <AegisBadge v-if="profile.telehealth" label="Telehealth" variant="blue" />
          <AegisBadge v-if="profile.network_accepting" label="Accepting referrals" variant="gold" />
        </div>
      </header>

      <section v-if="profile.bio" class="public-profile-section">
        <h2 class="public-profile-section-title">About</h2>
        <p class="public-profile-bio">{{ profile.bio }}</p>
      </section>

      <section v-if="profile.specialty || profile.modalities" class="public-profile-section">
        <h2 class="public-profile-section-title">Practice</h2>
        <dl class="public-profile-dl">
          <template v-if="profile.specialty">
            <dt>Specialty</dt><dd>{{ profile.specialty }}</dd>
          </template>
          <template v-if="profile.modalities">
            <dt>Modalities</dt><dd>{{ profile.modalities }}</dd>
          </template>
          <template v-if="profile.languages">
            <dt>Languages</dt><dd>{{ profile.languages }}</dd>
          </template>
          <template v-if="profile.city">
            <dt>Location</dt><dd>{{ profile.city }}{{ profile.state ? ', ' + profile.state : '' }}</dd>
          </template>
        </dl>
      </section>

      <section v-if="services.length" class="public-profile-section">
        <h2 class="public-profile-section-title">Services</h2>
        <div class="services-grid">
          <article v-for="s in services" :key="s.id" class="service-card">
            <div class="service-card-head">
              <div class="service-card-title">{{ s.title }}</div>
              <div class="service-card-rate" v-if="s.rate_cents">{{ pricing.formatCents(s.rate_cents) }} / {{ s.unit }}</div>
            </div>
            <p class="service-card-desc">{{ s.description }}</p>
          </article>
        </div>
      </section>

      <section class="public-profile-section">
        <h2 class="public-profile-section-title">Continuity</h2>
        <p class="public-profile-bio">
          This practitioner participates in MAAT's continuity network — clients are protected by a designated
          Continuity Steward in the event of disruption.
        </p>
      </section>

      <footer class="public-profile-cta">
        <a v-if="profile.contact_email" :href="`mailto:${profile.contact_email}`" class="btn btn-primary">
          <AegisIcon name="mail" :size="14" />
          <span>Contact {{ profile.first_name }}</span>
        </a>
        <a v-if="profile.website" :href="profile.website" target="_blank" rel="noopener" class="btn btn-outline">
          <AegisIcon name="external-link" :size="14" />
          <span>Visit website</span>
        </a>
      </footer>
    </article>
  </PublicLayout>
</template>

<script setup>
import PublicLayout from '@/layouts/PublicLayout.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import ProfileStrip from '@/components/features/ProfileStrip.vue'
import { usePricingStore } from '@/stores/pricing'

defineProps({
  profile:  { type: Object, required: true },
  services: { type: Array,  default: () => [] },
})
const pricing = usePricingStore()
</script>
