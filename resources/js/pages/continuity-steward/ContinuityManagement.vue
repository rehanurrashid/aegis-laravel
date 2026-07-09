<!--
  pages/continuity-steward/ContinuityManagement.vue — incident management hub.
  Actions post to cs.continuity.* routes (not cs.continuity-management.*):
    - escalate needs reason (min 5 chars)
    - close needs summary (min 10 chars)
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Critical Incident"
      title="Continuity Management"
      :subtitle="hasActiveIncident
        ? 'Active critical incident — coordinate response and notify stewards.'
        : 'Standby. No active incidents at this time.'"
    />

    <div v-if="hasActiveIncident" class="incident-strip-large">
      <div class="incident-strip-icon"><AegisIcon name="alert-triangle" :size="24" /></div>
      <div class="incident-strip-body">
        <div class="incident-strip-title">{{ activeIncident.title }}</div>
        <div class="incident-strip-meta">
          Practitioner: <strong>{{ activeIncident.provider_name }}</strong>
          · Activated {{ activity.timeAgo(activeIncident.activated_at) }}
        </div>
      </div>
      <div class="incident-strip-actions">
        <button type="button" class="btn btn-outline" @click="openEscalate = true">
          Escalate
        </button>
        <button type="button" class="btn btn-primary" @click="openResolve = true">
          Mark resolved
        </button>
      </div>
    </div>

    <div class="dashboard-grid">
      <AegisCard title="Roster">
        <ul v-if="roster.length" class="vault-item-list">
          <li v-for="r in roster" :key="r.id" class="vault-item-row">
            <AegisIcon name="user" :size="14" />
            <span>{{ r.client_name }}</span>
            <AegisBadge v-if="r.client_status === 'priority'" label="Priority" variant="red" />
          </li>
        </ul>
        <AegisEmptyState v-else icon="users" title="No roster items yet" />
      </AegisCard>

      <AegisCard title="Response checklist">
        <ul class="checklist">
          <li v-for="step in checklist" :key="step.key" class="checklist-row" :class="{ 'is-done': step.completed_at }">
            <AegisIcon :name="step.completed_at ? 'check-circle' : 'circle'" :size="16" />
            <span>{{ step.title }}</span>
          </li>
        </ul>
      </AegisCard>
    </div>

    <AegisCard title="Incident timeline">
      <ActivityFeed :events="timeline" />
    </AegisCard>

    <!-- Escalate modal -->
    <AegisModal v-model="openEscalate" title="Escalate to Support Stewards" size="md">
      <p style="font-size:13.5px;color:var(--text-2);margin-bottom:12px">
        This notifies all designated Support Stewards for this plan and creates priority tasks for each.
      </p>
      <div class="form-group">
        <label class="form-label">Reason for escalation</label>
        <textarea
          v-model="escalateReason"
          class="form-input"
          rows="4"
          minlength="5"
          maxlength="2000"
          placeholder="Explain what prompted this escalation…"
        ></textarea>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="openEscalate = false">Cancel</button>
        <button
          type="button"
          class="btn btn-primary btn-sm"
          :disabled="busy || (escalateReason?.length ?? 0) < 5"
          @click="escalate"
        >
          {{ busy ? 'Notifying…' : 'Notify Support Stewards' }}
        </button>
      </template>
    </AegisModal>

    <!-- Resolve modal -->
    <AegisModal v-model="openResolve" title="Mark incident resolved" size="md">
      <p style="font-size:13.5px;color:var(--text-2);margin-bottom:12px">
        Closes the incident and re-seals the vault. The full incident report is preserved for the practitioner and stewards.
      </p>
      <div class="form-group">
        <label class="form-label">Closing summary</label>
        <textarea
          v-model="resolveSummary"
          class="form-input"
          rows="4"
          minlength="10"
          maxlength="2000"
          placeholder="Summarize actions taken, outcomes, and any handoffs…"
        ></textarea>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="openResolve = false">Cancel</button>
        <button
          type="button"
          class="btn btn-primary btn-sm"
          :disabled="busy || (resolveSummary?.length ?? 0) < 10"
          @click="resolve"
        >
          {{ busy ? 'Closing…' : 'Close incident' }}
        </button>
      </template>
    </AegisModal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisCard       from '@/components/ui/AegisCard.vue'
import AegisBadge      from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import AegisIcon       from '@/components/ui/AegisIcon.vue'
import AegisModal      from '@/components/ui/AegisModal.vue'
import ActivityFeed    from '@/components/features/ActivityFeed.vue'
import { useToast }    from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'

const props = defineProps({
  activeIncident: { type: Object, default: null },
  roster:         { type: Array,  default: () => [] },
  checklist:      { type: Array,  default: () => [] },
  timeline:       { type: Array,  default: () => [] },
})

const toast    = useToast()
const activity = useActivity()

const openEscalate   = ref(false)
const openResolve    = ref(false)
const escalateReason = ref('')
const resolveSummary = ref('')
const busy           = ref(false)

const hasActiveIncident = computed(() => !!props.activeIncident)

function escalate() {
  if (!props.activeIncident) return
  busy.value = true
  router.post(
    route('cs.continuity.escalate', { incident: props.activeIncident.id }),
    { reason: escalateReason.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Support Stewards notified.')
        openEscalate.value = false
        escalateReason.value = ''
      },
      onFinish: () => { busy.value = false },
    }
  )
}

function resolve() {
  if (!props.activeIncident) return
  busy.value = true
  router.post(
    route('cs.continuity.close', { incident: props.activeIncident.id }),
    { summary: resolveSummary.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Incident closed.')
        openResolve.value = false
        resolveSummary.value = ''
      },
      onFinish: () => { busy.value = false },
    }
  )
}
</script>

<style scoped>
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-label { font-size: 11px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--text-2); }
.form-input { padding: 10px 12px; font-size: 13.5px; color: var(--text); background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: inherit; }
.form-input:focus { border-color: var(--gold); outline: none; box-shadow: 0 0 0 3px rgba(196,169,106,.18); }
</style>
