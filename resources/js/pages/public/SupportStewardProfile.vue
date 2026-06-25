<!--
  pages/public/SupportStewardProfile.vue — unauthenticated SS profile.
-->
<template>
  <PublicLayout>
    <article class="public-profile">
      <header class="public-profile-head">
        <ProfileStrip :person="profile" kind="ss" large />
        <div class="public-profile-meta">
          <AegisBadge v-if="profile.accepting_new" label="Available" variant="green" />
          <AegisBadge v-if="profile.after_hours_ok" label="After-hours" variant="blue" />
        </div>
      </header>

      <section v-if="profile.bio" class="public-profile-section">
        <h2 class="public-profile-section-title">About</h2>
        <p class="public-profile-bio">{{ profile.bio }}</p>
      </section>

      <section class="public-profile-section">
        <h2 class="public-profile-section-title">Role</h2>
        <p class="public-profile-bio">
          A Support Steward handles non-clinical logistics — communications, billing handoffs,
          and operational continuity — alongside a designated Continuity Steward.
        </p>
        <dl class="public-profile-dl">
          <template v-if="profile.relationship">
            <dt>Relationship</dt><dd>{{ profile.relationship }}</dd>
          </template>
          <template v-if="profile.city">
            <dt>Based in</dt><dd>{{ profile.city }}{{ profile.state ? ', ' + profile.state : '' }}</dd>
          </template>
        </dl>
      </section>
    </article>
  </PublicLayout>
</template>

<script setup>
import PublicLayout from '@/layouts/PublicLayout.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import ProfileStrip from '@/components/features/ProfileStrip.vue'

defineProps({ profile: { type: Object, required: true } })
</script>
