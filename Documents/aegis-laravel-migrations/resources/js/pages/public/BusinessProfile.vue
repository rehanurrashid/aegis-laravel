<!--
  pages/public/BusinessProfile.vue — unauthenticated BP/agency profile.
-->
<template>
  <PublicLayout>
    <article class="public-profile">
      <header class="public-profile-head">
        <ProfileStrip :person="profile" kind="business" large />
        <div class="public-profile-meta">
          <AegisBadge :label="profile.is_agency ? 'Agency' : 'Freelancer'" variant="blue" />
          <AegisBadge v-if="profile.accepting_work" label="Accepting work" variant="green" />
        </div>
      </header>

      <section v-if="profile.bio" class="public-profile-section">
        <h2 class="public-profile-section-title">About</h2>
        <p class="public-profile-bio">{{ profile.bio }}</p>
      </section>

      <section class="public-profile-section">
        <h2 class="public-profile-section-title">Services</h2>
        <dl class="public-profile-dl">
          <template v-if="profile.categories">
            <dt>Categories</dt><dd>{{ profile.categories }}</dd>
          </template>
          <template v-if="profile.tools">
            <dt>Tools</dt><dd>{{ profile.tools }}</dd>
          </template>
          <template v-if="profile.hourly_rate_cents">
            <dt>Hourly rate</dt><dd>{{ pricing.formatCents(profile.hourly_rate_cents) }} / hr</dd>
          </template>
          <template v-if="profile.min_engagement_cents">
            <dt>Minimum engagement</dt><dd>{{ pricing.formatCents(profile.min_engagement_cents) }}</dd>
          </template>
          <template v-if="profile.is_agency && profile.team_size">
            <dt>Team size</dt><dd>{{ profile.team_size }}</dd>
          </template>
        </dl>
      </section>

      <section v-if="testimonials.length" class="public-profile-section">
        <h2 class="public-profile-section-title">What clients say</h2>
        <ul class="public-testimonial-list">
          <li v-for="t in testimonials" :key="t.id" class="public-testimonial-row">
            <blockquote>{{ t.quote }}</blockquote>
            <cite>— {{ t.author }}</cite>
          </li>
        </ul>
      </section>
    </article>
  </PublicLayout>
</template>

<script setup>
import PublicLayout from '@/layouts/PublicLayout.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import ProfileStrip from '@/components/features/ProfileStrip.vue'
import { usePricingStore } from '@/stores/pricing'

defineProps({
  profile:      { type: Object, required: true },
  testimonials: { type: Array,  default: () => [] },
})
const pricing = usePricingStore()
</script>
