<!--
  IncidentBanner.vue — red alert strip pinned below the header.

  Renders only when incident.hasEmergency is true. Routes to the active
  incident detail page in the user's portal (CS: Continuity Management,
  SS: Critical Incident Log, Provider: Continuity Plan).
-->
<template>
  <div v-if="incident.hasEmergency" class="incident-banner" role="alert">
    <div class="incident-banner-inner">
      <AegisIcon name="alert-triangle" :size="16" />
      <span class="incident-banner-text">
        <strong>Active Critical Incident</strong>
        — immediate attention required.
      </span>
      <a v-if="href !== '#'" :href="href" class="incident-banner-link">
        <span>View details</span>
        <AegisIcon name="arrow-right" :size="13" />
      </a>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useIncidentStore } from '@/stores/incident'
import { useAuthStore } from '@/stores/auth'

const incident = useIncidentStore()
const auth     = useAuthStore()

const href = computed(() => {
  const map = {
    provider:           'provider.plan',
    continuity_steward: 'cs.continuity-management',
    support_steward:    'ss.incident-log',
  }
  const name = map[auth.portal]
  if (!name) return '#'
  try { return route(name) } catch { return '#' }
})
</script>
