<!--
  pages/provider/Referrals.vue — sent + received referrals (Practice tier).
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="My Practice"
      title="Referrals"
      subtitle="Clients you've sent and received via your clinical network."
    >
      <template #actions>
        <button type="button" class="btn btn-primary" @click="upgrade.requiresPractice(() => openModal('referralModal'))">
          <AegisIcon name="share-tree" :size="14" />
          <span>Send referral</span>
        </button>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="share-tree" :value="sent.length"     label="Sent" />
      <AegisStatChip icon="inbox"      :value="received.length" label="Received" bg-color="var(--icon-bg-blue)"  icon-color="var(--blue-dark)" />
      <AegisStatChip icon="check-badge" :value="acceptedCount"  label="Accepted" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="hourglass"   :value="pendingCount"   label="Pending"  bg-color="var(--icon-bg-gold)"  icon-color="var(--gold-dark)" />
    </div>

    <nav class="tab-strip" role="tablist">
      <button type="button" class="tab-btn" :class="{ 'is-active': tab === 'sent' }" @click="tab = 'sent'">
        <AegisIcon name="share-tree" :size="14" /> Sent ({{ sent.length }})
      </button>
      <button type="button" class="tab-btn" :class="{ 'is-active': tab === 'received' }" @click="tab = 'received'">
        <AegisIcon name="inbox" :size="14" /> Received ({{ received.length }})
      </button>
    </nav>

    <section v-show="tab === 'sent'">
      <ReferralTable v-if="sent.length" :rows="sent" direction="sent" />
      <AegisEmptyState v-else icon="share-tree" title="No referrals sent" description="When you refer a client, the activity appears here." />
    </section>
    <section v-show="tab === 'received'">
      <ReferralTable v-if="received.length" :rows="received" direction="received" @respond="respond" />
      <AegisEmptyState v-else icon="inbox" title="No referrals received" description="Colleagues' referrals will land in this list." />
    </section>

    <ReferralModal :roster="roster" :network="network" />
    <AegisUpgradeModal />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, h } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import AegisUpgradeModal from '@/components/ui/AegisUpgradeModal.vue'
import ReferralModal from '@/components/modals/ReferralModal.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { useUpgrade } from '@/composables/useUpgrade'

// Tiny inline table component to keep this page self-contained.
import { defineComponent } from 'vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'

const ReferralTable = defineComponent({
  props: { rows: Array, direction: String },
  emits: ['respond'],
  setup(props, { emit }) {
    return () => h('table', { class: 'data-table' }, [
      h('thead', null, h('tr', null, [
        h('th', 'Client'),
        h('th', props.direction === 'sent' ? 'To' : 'From'),
        h('th', 'Urgency'),
        h('th', 'Status'),
        h('th', ''),
      ])),
      h('tbody', null, props.rows.map((r) => h('tr', { key: r.id }, [
        h('td', { class: 'data-table-primary' }, r.client_name),
        h('td', props.direction === 'sent' ? (r.recipient_name ?? '—') : (r.sender_name ?? '—')),
        h('td', null, h(AegisBadge, { label: r.urgency, variant: r.urgency === 'urgent' ? 'red' : r.urgency === 'soon' ? 'orange' : 'neutral' })),
        h('td', null, h(AegisBadge, { label: r.status, variant: { accepted: 'green', declined: 'red', pending: 'gold' }[r.status] ?? 'neutral' })),
        h('td', null, props.direction === 'received' && r.status === 'pending'
          ? [
              h('button', { class: 'btn btn-sm btn-primary', onClick: () => emit('respond', r, 'accept') }, 'Accept'),
              h('button', { class: 'btn btn-sm btn-ghost btn-danger-ghost', onClick: () => emit('respond', r, 'decline') }, 'Decline'),
            ]
          : null,
        ),
      ]))),
    ])
  },
})

const props = defineProps({
  sent:     { type: Array, default: () => [] },
  received: { type: Array, default: () => [] },
  roster:   { type: Array, default: () => [] },
  network:  { type: Array, default: () => [] },
})

const { openModal } = useModal()
const toast = useToast()
const upgrade = useUpgrade()

const tab = ref('sent')
const acceptedCount = computed(() => [...props.sent, ...props.received].filter((r) => r.status === 'accepted').length)
const pendingCount  = computed(() => [...props.sent, ...props.received].filter((r) => r.status === 'pending').length)

function respond(r, action) {
  router.post(route(`provider.referrals.${action}`, { referral: r.id }), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success(`Referral ${action === 'accept' ? 'accepted' : 'declined'}.`),
  })
}
</script>
