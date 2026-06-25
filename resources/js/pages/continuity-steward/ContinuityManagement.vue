<!--
  pages/continuity-steward/ContinuityManagement.vue — incident management hub.
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
        <button type="button" class="btn btn-outline" @click="openModal('escalateModal')">
          Escalate
        </button>
        <button type="button" class="btn btn-primary" @click="openModal('resolveModal')">
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

    <!-- Escalate -->
    <AegisConfirm
      :model-value="isOpen('escalateModal').value"
      title="Escalate to Support Steward"
      primary-label="Notify all Support Stewards"
      @update:model-value="(v) => !v && closeModal('escalateModal')"
      @confirm="escalate"
    >
      <p>This notifies all designated Support Stewards and creates priority tasks for each.</p>
    </AegisConfirm>

    <!-- Resolve -->
    <AegisConfirm
      :model-value="isOpen('resolveModal').value"
      title="Mark incident resolved?"
      primary-label="Yes, resolve incident"
      @update:model-value="(v) => !v && closeModal('resolveModal')"
      @confirm="resolve"
    >
      <p>Closes the incident and re-seals the vault. The full incident report is preserved.</p>
    </AegisConfirm>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisConfirm from '@/components/ui/AegisConfirm.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import ActivityFeed from '@/components/features/ActivityFeed.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'

const props = defineProps({
  activeIncident: { type: Object, default: null },
  roster:         { type: Array,  default: () => [] },
  checklist:      { type: Array,  default: () => [] },
  timeline:       { type: Array,  default: () => [] },
})

const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()
const activity = useActivity()

const hasActiveIncident = computed(() => !!props.activeIncident)

function escalate() {
  router.post(route('cs.continuity-management.escalate', { incident: props.activeIncident.id }), {}, {
    onSuccess: () => { toast.success('Support Stewards notified.'); closeModal('escalateModal') },
  })
}
function resolve() {
  router.post(route('cs.continuity-management.resolve', { incident: props.activeIncident.id }), {}, {
    onSuccess: () => { toast.success('Incident resolved.'); closeModal('resolveModal') },
  })
}
</script>
