<!--
  pages/provider/Dashboard.vue — Practitioner Portal home.

  Replaces provider-portal/dashboard.php. Shows continuity plan status,
  designated stewards, vault attestation chip, plus stat strip and
  recent activity widget. The "Activate Continuity Support" CTA is
  gated by canActivate (no active incident already in flight).
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Welcome back"
      :title="`Hello, ${auth.user?.first_name ?? 'Practitioner'}.`"
      subtitle="Your practice's continuity at a glance."
    >
      <template #actions>
        <a :href="route('provider.plan')" class="btn btn-outline">
          <AegisIcon name="shield-check" :size="14" />
          <span>View plan</span>
        </a>
        <button
          type="button"
          class="btn btn-primary"
          @click="upgrade.requiresPractice(() => openModal('referralModal'))"
        >
          <AegisIcon name="share-tree" :size="14" />
          <span>Send referral</span>
        </button>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="users-network" :value="stats.continuity_stewards" label="Continuity Stewards" />
      <AegisStatChip icon="users-line"    :value="stats.support_stewards"    label="Support Stewards" bg-color="var(--icon-bg-blue)"  icon-color="var(--blue-dark)" />
      <AegisStatChip icon="vault"         :value="stats.vault_items"         label="Vault items"       bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="share-tree"    :value="stats.pending_referrals"   label="Pending referrals" bg-color="var(--icon-bg-orange)" icon-color="var(--orange-dark)" />
    </div>

    <div class="dashboard-grid">
      <PlanStatusCard
        :plan="plan"
        :primary-cs="primaryCs"
        :primary-ss="primarySs"
        :vault-attested="vaultAttested"
        :vault-item-count="stats.vault_items"
        :can-activate="canActivate"
      />

      <AegisCard title="Recent Activity">
        <template #actions>
          <a :href="route('provider.activity')" class="btn btn-ghost btn-sm">
            View all
            <AegisIcon name="arrow-right" :size="13" />
          </a>
        </template>
        <ActivityFeed :events="recentEvents" compact />
      </AegisCard>
    </div>

    <ReferralModal :roster="roster" :network="network" />
    <AegisUpgradeModal />
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisUpgradeModal from '@/components/ui/AegisUpgradeModal.vue'
import PlanStatusCard from '@/components/features/PlanStatusCard.vue'
import ActivityFeed from '@/components/features/ActivityFeed.vue'
import ReferralModal from '@/components/modals/ReferralModal.vue'
import { useAuthStore } from '@/stores/auth'
import { useModal } from '@/composables/useModal'
import { useUpgrade } from '@/composables/useUpgrade'

defineProps({
  stats:        { type: Object, default: () => ({ continuity_stewards: 0, support_stewards: 0, vault_items: 0, pending_referrals: 0 }) },
  plan:         { type: Object, default: null },
  primaryCs:    { type: Object, default: null },
  primarySs:    { type: Object, default: null },
  vaultAttested:{ type: Boolean, default: false },
  recentEvents: { type: Array,  default: () => [] },
  roster:       { type: Array,  default: () => [] },
  network:      { type: Array,  default: () => [] },
  canActivate:  { type: Boolean, default: false },
})

const auth = useAuthStore()
const { openModal } = useModal()
const upgrade = useUpgrade()
</script>
