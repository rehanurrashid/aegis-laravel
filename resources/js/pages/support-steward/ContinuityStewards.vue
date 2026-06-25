<!--
  pages/support-steward/ContinuityStewards.vue — CS partners list.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Activation"
      title="Continuity Stewards"
      :subtitle="`${csList.length} CS partner${csList.length === 1 ? '' : 's'} on your active plans.`"
    />

    <AegisCard v-if="csList.length" title="Your Continuity Steward partners">
      <div class="steward-card-grid">
        <StewardCard
          v-for="cs in csList"
          :key="cs.id"
          :steward="cs"
          kind="cs"
          :can-remove="false"
          @message="msg(cs)"
        />
      </div>
    </AegisCard>

    <AegisEmptyState v-else icon="users-network" title="No CS partners yet" description="When you're designated alongside a Continuity Steward, they appear here." />
  </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import StewardCard from '@/components/features/StewardCard.vue'

defineProps({ csList: { type: Array, default: () => [] } })

function msg(cs) { router.visit(route('ss.messages', { with: cs.id })) }
</script>
