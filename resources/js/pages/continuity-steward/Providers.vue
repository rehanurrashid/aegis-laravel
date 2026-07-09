<!--
  pages/continuity-steward/Providers.vue — practitioners served by this CS.

  Routes used:
    cs.providers.plan             GET  /providers/{provider}/plan          (Open button)
    cs.providers.accept           POST /providers/invitations/{invitation}/accept
    cs.providers.decline          POST /providers/invitations/{invitation}/decline
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="My Work"
      title="My Practitioners"
      :subtitle="`${providers.length} practitioner${providers.length === 1 ? '' : 's'} under your stewardship.`"
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="users" :value="providers.length" label="Active" />
      <AegisStatChip icon="hourglass" :value="pending.length" label="Pending" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
      <AegisStatChip icon="alert-triangle" :value="incidentCount" label="In active incident" bg-color="var(--icon-bg-red)" icon-color="var(--red-dark)" />
    </div>

    <AegisCard title="Practitioners" v-if="providers.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Practitioner</th>
            <th>Status</th>
            <th>Last attestation</th>
            <th>Vault</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in providers" :key="p.id">
            <td class="data-table-primary">
              <a :href="profileHref(p.slug, 'provider')">{{ p.display_name }}</a>
            </td>
            <td>
              <AegisBadge
                :label="p.incident_active ? 'Incident' : 'Active'"
                :variant="p.incident_active ? 'red' : 'green'"
              />
            </td>
            <td>{{ p.last_attested_at ? activity.timeAgo(p.last_attested_at) : 'Never' }}</td>
            <td>{{ p.vault_count }} items</td>
            <td>
              <a :href="route('cs.providers.plan', { provider: p.id })" class="btn btn-sm btn-outline">Open</a>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisCard v-if="pending.length" title="Pending designations">
      <ul class="pending-list">
        <li v-for="p in pending" :key="p.id" class="pending-row">
          <div>
            <div class="pending-name">{{ p.display_name }}</div>
            <div class="pending-meta">
              <span v-if="p.role">Role: {{ p.role }} · </span>
              Requested {{ p.requested_at ? activity.timeAgo(p.requested_at) : 'recently' }}
            </div>
          </div>
          <div class="pending-actions">
            <button type="button" class="btn btn-sm btn-primary" :disabled="busy === p.id" @click="respond(p, 'accept')">
              {{ busy === p.id ? '…' : 'Accept' }}
            </button>
            <button type="button" class="btn btn-sm btn-ghost btn-danger-ghost" :disabled="busy === p.id" @click="respond(p, 'decline')">
              Decline
            </button>
          </div>
        </li>
      </ul>
    </AegisCard>

    <AegisEmptyState
      v-if="!providers.length && !pending.length"
      icon="users"
      title="No practitioners yet"
      description="Practitioners who designate you appear here."
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisStatChip   from '@/components/ui/AegisStatChip.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useActivity } from '@/composables/useActivity'
import { useProfileRoute } from '@/composables/useProfileRoute'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  providers: { type: Array, default: () => [] },
  pending:   { type: Array, default: () => [] },
})

const activity = useActivity()
const { profileHref } = useProfileRoute()
const toast = useToast()

const busy = ref(null)

const incidentCount = computed(() => props.providers.filter((p) => p.incident_active).length)

function respond(p, action) {
  busy.value = p.id
  router.post(
    route(`cs.providers.${action}`, { invitation: p.id }),
    {},
    {
      preserveScroll: true,
      onSuccess: () => toast.success(`Designation ${action === 'accept' ? 'accepted' : 'declined'}.`),
      onFinish:  () => { busy.value = null },
    }
  )
}
</script>

<style scoped>
.pending-list    { display: flex; flex-direction: column; gap: 8px; list-style: none; padding: 0; margin: 0; }
.pending-row     { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); }
.pending-name    { font-size: 13.5px; font-weight: 600; color: var(--text); }
.pending-meta    { font-size: 12px; color: var(--text-3); }
.pending-actions { display: flex; gap: 8px; }
</style>
