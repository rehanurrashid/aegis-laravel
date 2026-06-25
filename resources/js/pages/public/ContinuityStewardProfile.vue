<!--
  pages/public/ContinuityStewardProfile.vue — unauthenticated CS profile.
-->
<template>
  <PublicLayout>
    <article class="public-profile">
      <header class="public-profile-head">
        <ProfileStrip :person="profile" kind="cs" large />
        <div class="public-profile-meta">
          <AegisBadge v-if="profile.accepting_new" label="Accepting designations" variant="green" />
          <AegisBadge :label="`${profile.current_load}/${profile.capacity_max} capacity`" variant="blue" />
        </div>
      </header>

      <section v-if="profile.bio" class="public-profile-section">
        <h2 class="public-profile-section-title">About</h2>
        <p class="public-profile-bio">{{ profile.bio }}</p>
      </section>

      <section class="public-profile-section">
        <h2 class="public-profile-section-title">Stewardship</h2>
        <dl class="public-profile-dl">
          <template v-if="profile.coverage_area">
            <dt>Coverage</dt><dd>{{ profile.coverage_area }}</dd>
          </template>
          <template v-if="profile.city">
            <dt>Based in</dt><dd>{{ profile.city }}{{ profile.state ? ', ' + profile.state : '' }}</dd>
          </template>
          <template v-if="profile.credentials">
            <dt>Credentials</dt><dd>{{ profile.credentials }}</dd>
          </template>
        </dl>
      </section>

      <section class="public-profile-section">
        <h2 class="public-profile-section-title">About MAAT Continuity Stewardship</h2>
        <p class="public-profile-bio">
          A Continuity Steward is a clinician who steps in to receive active clients in the
          event of a critical incident affecting a colleague's practice. Stewardship is a
          formal, attested role within the MAAT Practice Firm network.
        </p>
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
