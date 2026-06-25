<!--
  pages/provider/ContinuityPlan.vue — the master continuity plan view.

  Replaces provider-portal/continuity-plan.php. Shows plan status,
  attestation history, designated stewards, vault summary, and offers
  the Activate Continuity Support button. Activation opens the
  activateSuccessionModal (handled inline below).
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Continuity"
      title="Continuity Plan"
      subtitle="Your master plan for clinical succession and incident response."
    >
      <template #actions>
        <button type="button" class="btn btn-outline" @click="openModal('attestPlanModal')">
          <AegisIcon name="check-badge" :size="14" />
          <span>Attest plan</span>
        </button>
        <button
          v-if="canActivate"
          type="button"
          class="btn btn-danger"
          @click="openModal('activateSuccessionModal')"
        >
          <AegisIcon name="alert-triangle" :size="14" />
          <span>Activate</span>
        </button>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="check-badge"
        :value="plan.last_attested_at ? activity.timeAgo(plan.last_attested_at) : 'Never'"
        label="Last attested"
        bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="users-network" :value="continuityStewards.length" label="Continuity Stewards" />
      <AegisStatChip icon="users-line"    :value="supportStewards.length"    label="Support Stewards" bg-color="var(--icon-bg-blue)" icon-color="var(--blue-dark)" />
      <AegisStatChip icon="file-text"     :value="planSections.filter(s => s.complete).length + '/' + planSections.length"
        label="Sections complete"
        bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
    </div>

    <!-- Plan sections -->
    <AegisCard title="Plan sections">
      <ol class="plan-sections-list">
        <li
          v-for="section in planSections"
          :key="section.key"
          class="plan-section-row"
          :class="{ 'is-complete': section.complete }"
        >
          <div class="plan-section-status">
            <AegisIcon :name="section.complete ? 'check-circle' : 'circle'" :size="16" />
          </div>
          <div class="plan-section-body">
            <div class="plan-section-title">{{ section.title }}</div>
            <div class="plan-section-desc">{{ section.description }}</div>
          </div>
          <a :href="section.href" class="btn btn-ghost btn-sm">
            {{ section.complete ? 'Review' : 'Complete' }}
            <AegisIcon name="arrow-right" :size="13" />
          </a>
        </li>
      </ol>
    </AegisCard>

    <!-- Designated stewards strip -->
    <AegisCard title="Designated stewards">
      <div class="steward-card-grid">
        <StewardCard
          v-for="cs in continuityStewards"
          :key="cs.id"
          :steward="cs"
          kind="cs"
          @message="messageSteward(cs)"
          @remove="removeSteward(cs, 'cs')"
        />
        <StewardCard
          v-for="ss in supportStewards"
          :key="ss.id"
          :steward="ss"
          kind="ss"
          @message="messageSteward(ss)"
          @remove="removeSteward(ss, 'ss')"
        />
      </div>
    </AegisCard>

    <!-- Activate modal -->
    <AegisConfirm
      :model-value="isOpen('activateSuccessionModal').value"
      title="Activate Continuity Support"
      destructive
      primary-label="Yes, activate now"
      cancel-label="Cancel"
      @update:model-value="(v) => !v && closeModal('activateSuccessionModal')"
      @confirm="activate"
    >
      <p>
        Activating notifies all designated stewards immediately and opens
        the incident log. Your Continuity Steward begins the response
        workflow. This action is logged and cannot be undone silently.
      </p>
      <p><strong>Activate only in a real critical incident.</strong></p>
    </AegisConfirm>

    <!-- Attest modal -->
    <AegisConfirm
      :model-value="isOpen('attestPlanModal').value"
      title="Attest your plan"
      primary-label="Attest plan"
      @update:model-value="(v) => !v && closeModal('attestPlanModal')"
      @confirm="attestPlan"
    >
      <p>
        Attestation confirms your plan reflects your current practice
        and stewardship. Re-attest every quarter to keep the seal current.
      </p>
    </AegisConfirm>
  </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisConfirm from '@/components/ui/AegisConfirm.vue'
import StewardCard from '@/components/features/StewardCard.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'

defineProps({
  plan:                { type: Object, required: true },
  planSections:        { type: Array,  default: () => [] },
  continuityStewards:  { type: Array,  default: () => [] },
  supportStewards:     { type: Array,  default: () => [] },
  canActivate:         { type: Boolean, default: false },
})

const { openModal, closeModal, isOpen } = useModal()
const toast    = useToast()
const activity = useActivity()

function activate() {
  router.post(route('provider.plan.activate'), {}, {
    onSuccess: () => {
      toast.success('Continuity support activated. Stewards notified.')
      closeModal('activateSuccessionModal')
    },
  })
}

function attestPlan() {
  router.post(route('provider.plan.attest'), {}, {
    onSuccess: () => {
      toast.success('Plan attested.')
      closeModal('attestPlanModal')
    },
  })
}

function messageSteward(s) {
  router.visit(route('provider.messages', { with: s.id }))
}

function removeSteward(s, kind) {
  if (!window.confirm(`Remove ${s.display_name}?`)) return
  router.delete(
    kind === 'cs'
      ? route('provider.continuity-stewards.destroy', { steward: s.id })
      : route('provider.support-stewards.destroy', { steward: s.id }),
    { onSuccess: () => toast.success('Steward removed.') },
  )
}
</script>
