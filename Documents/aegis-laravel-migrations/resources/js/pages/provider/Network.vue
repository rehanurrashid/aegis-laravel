<!--
  pages/provider/Network.vue — clinical + business connections.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="My Practice"
      title="Network"
      subtitle="Your trusted clinical and business connections."
    >
      <template #actions>
        <button type="button" class="btn btn-outline" @click="openModal('searchNetworkModal')">
          <AegisIcon name="search-lg" :size="14" />
          <span>Find connections</span>
        </button>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="users-network" :value="clinical.length"  label="Clinical connections" />
      <AegisStatChip icon="briefcase"      :value="businessCount"    label="Business connections" bg-color="var(--icon-bg-blue)"  icon-color="var(--blue-dark)" />
      <AegisStatChip icon="hourglass"      :value="pending.length"   label="Pending"              bg-color="var(--icon-bg-gold)"  icon-color="var(--gold-dark)" />
    </div>

    <nav class="tab-strip" role="tablist">
      <button type="button" class="tab-btn" :class="{ 'is-active': tab === 'clinical' }" @click="tab = 'clinical'">
        <AegisIcon name="users-network" :size="14" /> Clinical
      </button>
      <button type="button" class="tab-btn" :class="{ 'is-active': tab === 'business' }" @click="tab = 'business'">
        <AegisIcon name="briefcase" :size="14" /> Business
      </button>
      <button type="button" class="tab-btn" :class="{ 'is-active': tab === 'pending' }" @click="tab = 'pending'">
        <AegisIcon name="hourglass" :size="14" /> Pending
      </button>
    </nav>

    <section v-show="tab === 'clinical'">
      <div v-if="clinical.length" class="network-grid">
        <article v-for="c in clinical" :key="c.id" class="network-card">
          <div class="network-card-head">
            <ProfileStrip :person="c" kind="provider" />
          </div>
          <div class="network-card-actions">
            <a :href="profileHref(c.slug, 'provider')" class="btn btn-sm btn-outline" data-tip="View profile">
              <AegisIcon name="external-link" :size="13" />
            </a>
            <button type="button" class="btn btn-sm btn-outline btn-icon" data-tip="Message" @click="msg(c)">
              <AegisIcon name="message" :size="13" />
            </button>
          </div>
        </article>
      </div>
      <AegisEmptyState v-else icon="users-network" title="No clinical connections yet" description="Add colleagues you trust for referrals and continuity." />
    </section>

    <section v-show="tab === 'business'">
      <div v-if="business.length" class="network-grid">
        <article v-for="b in business" :key="b.id" class="network-card">
          <ProfileStrip :person="b" kind="business" />
        </article>
      </div>
      <AegisEmptyState v-else icon="briefcase" title="No business connections yet" description="Bookkeepers, marketers, billers, and other operational partners appear here." />
    </section>

    <section v-show="tab === 'pending'">
      <AegisCard v-if="pending.length" title="Pending invitations">
        <ul class="pending-list">
          <li v-for="p in pending" :key="p.id" class="pending-row">
            <div>
              <div class="pending-name">{{ p.display_name }}</div>
              <div class="pending-meta">{{ p.email }} · {{ activity.timeAgo(p.invited_at) }}</div>
            </div>
            <div class="pending-actions">
              <button type="button" class="btn btn-sm btn-outline" @click="resend(p)">Resend</button>
              <button type="button" class="btn btn-sm btn-ghost btn-danger-ghost" @click="cancelInv(p)">Cancel</button>
            </div>
          </li>
        </ul>
      </AegisCard>
      <AegisEmptyState v-else icon="hourglass" title="No pending invitations" />
    </section>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import ProfileStrip from '@/components/features/ProfileStrip.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'
import { useProfileRoute } from '@/composables/useProfileRoute'

const props = defineProps({
  clinical: { type: Array, default: () => [] },
  business: { type: Array, default: () => [] },
  pending:  { type: Array, default: () => [] },
})

const { openModal } = useModal()
const toast = useToast()
const activity = useActivity()
const { profileHref } = useProfileRoute()

const tab = ref('clinical')
const businessCount = computed(() => props.business.length)

function msg(c)       { router.visit(route('provider.messages', { with: c.id })) }
function resend(p)    { router.post(route('provider.network.resend', { invitation: p.id }), {}, { onSuccess: () => toast.success('Invitation resent.') }) }
function cancelInv(p) { router.delete(route('provider.network.cancel-invite', { invitation: p.id }), { onSuccess: () => toast.success('Invitation cancelled.') }) }
</script>
