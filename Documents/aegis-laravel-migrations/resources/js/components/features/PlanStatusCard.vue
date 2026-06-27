<!--
  PlanStatusCard.vue — continuity plan + steward summary on the
  Provider Dashboard.

  Shows plan status pill, designated Continuity Steward, primary Support
  Steward, vault attestation chip, and the "Activate Continuity Support"
  button (only visible when the practitioner is authorized to initiate
  an activation — typically when not already in an active incident).
-->
<template>
  <AegisCard title="Continuity Plan &amp; Continuity Steward">
    <template #actions>
      <a :href="planHref" class="btn btn-sm btn-outline">
        <span>Manage</span>
        <AegisIcon name="arrow-right" :size="13" />
      </a>
    </template>

    <!-- Status row -->
    <div class="plan-status-row">
      <AegisBadge
        :label="statusLabel"
        :variant="statusVariant"
        :icon="statusIcon"
      />
      <div v-if="plan?.last_attested_at" class="plan-attestation">
        <AegisIcon name="check-badge" :size="14" />
        <span>Plan attested {{ activity.timeAgo(plan.last_attested_at) }}</span>
      </div>
    </div>

    <!-- Stewards -->
    <div class="plan-stewards">
      <div class="plan-steward-row">
        <div class="plan-steward-role">Continuity Steward</div>
        <div v-if="primaryCs" class="plan-steward-name">
          <div class="plan-steward-avatar">{{ initials(primaryCs.display_name) }}</div>
          <span>{{ primaryCs.display_name }}</span>
        </div>
        <div v-else class="plan-steward-empty">Not yet designated</div>
      </div>

      <div class="plan-steward-row">
        <div class="plan-steward-role">Primary Support Steward</div>
        <div v-if="primarySs" class="plan-steward-name">
          <div class="plan-steward-avatar">{{ initials(primarySs.display_name) }}</div>
          <span>{{ primarySs.display_name }}</span>
        </div>
        <div v-else class="plan-steward-empty">Not yet designated</div>
      </div>
    </div>

    <!-- Vault attestation chip -->
    <div v-if="vaultAttested" class="plan-vault-chip">
      <AegisIcon name="vault" :size="14" />
      <span>Vault sealed · {{ vaultItemCount }} item{{ vaultItemCount === 1 ? '' : 's' }}</span>
    </div>

    <template #footer>
      <button
        v-if="canActivate"
        type="button"
        class="btn btn-danger"
        @click="onActivate"
      >
        <AegisIcon name="alert-triangle" :size="14" />
        <span>Activate Continuity Support</span>
      </button>
      <a v-else :href="planHref" class="btn btn-outline btn-sm">
        Review plan
      </a>
    </template>
  </AegisCard>
</template>

<script setup>
import { computed } from 'vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import { useModal } from '@/composables/useModal'
import { useActivity } from '@/composables/useActivity'

const props = defineProps({
  plan:           { type: Object, default: null },
  primaryCs:      { type: Object, default: null },
  primarySs:      { type: Object, default: null },
  vaultAttested:  { type: Boolean, default: false },
  vaultItemCount: { type: Number,  default: 0 },
  canActivate:    { type: Boolean, default: false },
})

const { openModal } = useModal()
const activity = useActivity()

const STATUS_META = {
  draft:        { label: 'Draft',           variant: 'neutral', icon: 'edit' },
  active:       { label: 'Active',          variant: 'green',   icon: 'check-circle' },
  attested:     { label: 'Plan attested',   variant: 'gold',    icon: 'check-badge' },
  incomplete:   { label: 'Plan incomplete', variant: 'orange',  icon: 'alert-circle' },
  incident:     { label: 'Incident active', variant: 'red',     icon: 'alert-triangle' },
}

const statusKey     = computed(() => props.plan?.status ?? 'draft')
const statusLabel   = computed(() => STATUS_META[statusKey.value]?.label   ?? 'Draft')
const statusVariant = computed(() => STATUS_META[statusKey.value]?.variant ?? 'neutral')
const statusIcon    = computed(() => STATUS_META[statusKey.value]?.icon    ?? 'edit')

const planHref = computed(() => {
  try { return route('provider.plan') } catch { return '#' }
})

function initials(name) {
  if (!name) return '·'
  return name.split(/\s+/).map((n) => n[0]).slice(0, 2).join('').toUpperCase()
}

function onActivate() {
  // Activation goes through a guarded modal (defined per-portal in
  // the Continuity Plan page); open by canonical id.
  openModal('activateSuccessionModal')
}
</script>
