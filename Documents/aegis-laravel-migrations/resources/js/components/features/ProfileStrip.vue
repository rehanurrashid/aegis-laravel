<!--
  ProfileStrip.vue — compact profile header.

  Replaces profile_strip.php — used at the top of public profile pages
  and on the "About me" section of dashboards. Shows avatar, name, role
  badge, location, and an optional CTA slot (Send referral, Message, etc.).

  The "verified" check-badge appears only when person.verified_at is set.
-->
<template>
  <header class="profile-strip">
    <div class="profile-strip-avatar">
      <img
        v-if="person.avatar_url"
        :src="person.avatar_url"
        :alt="person.display_name"
      />
      <span v-else>{{ initials }}</span>
    </div>

    <div class="profile-strip-body">
      <div class="profile-strip-name-row">
        <h1 class="profile-strip-name">{{ person.display_name }}</h1>
        <AegisIcon
          v-if="person.verified_at"
          name="check-badge"
          :size="16"
          class="profile-strip-verified"
          aria-label="Verified"
        />
      </div>

      <div v-if="person.headline" class="profile-strip-headline">
        {{ person.headline }}
      </div>

      <div class="profile-strip-meta">
        <AegisBadge :label="roleLabel" :variant="roleVariant" />
        <span v-if="locationLine" class="profile-strip-location">
          <AegisIcon name="map-pin" :size="12" />
          <span>{{ locationLine }}</span>
        </span>
      </div>
    </div>

    <div v-if="$slots.actions" class="profile-strip-actions">
      <slot name="actions" />
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'

const props = defineProps({
  person: { type: Object, required: true },
  kind:   { type: String, default: 'provider', validator: (v) => ['provider','cs','ss','business'].includes(v) },
})

const ROLE_LABEL = {
  provider: 'Practitioner',
  cs:       'Continuity Steward',
  ss:       'Support Steward',
  business: 'Business Partner',
}
const ROLE_VARIANT = {
  provider: 'gold',
  cs:       'blue',
  ss:       'teal',
  business: 'purple',
}

const roleLabel   = computed(() => ROLE_LABEL[props.kind])
const roleVariant = computed(() => ROLE_VARIANT[props.kind])

const initials = computed(() => {
  const n = props.person?.display_name ?? ''
  return n.split(/\s+/).map((s) => s[0]).slice(0, 2).join('').toUpperCase() || '·'
})

const locationLine = computed(() => {
  const p = props.person
  if (!p) return ''
  return [p.city, p.state].filter(Boolean).join(', ')
})
</script>
