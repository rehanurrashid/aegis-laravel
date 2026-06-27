<!--
  pages/provider/SupportStewards.vue — Support Steward designations.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Continuity"
      title="Support Stewards"
      subtitle="The non-clinical partner who coordinates logistics during an incident."
    >
      <template #actions>
        <button type="button" class="btn btn-primary" @click="openModal('inviteSsModal')">
          <AegisIcon name="user-plus" :size="14" />
          <span>Invite Steward</span>
        </button>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="users-line" :value="active.length"  label="Active Stewards" />
      <AegisStatChip icon="mail-open"  :value="pending.length" label="Pending invites" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
    </div>

    <AegisCard v-if="active.length" title="Designated Stewards">
      <div class="steward-card-grid">
        <StewardCard v-for="s in active" :key="s.id" :steward="s" kind="ss"
          @message="msg(s)" @remove="remove(s)" />
      </div>
    </AegisCard>

    <AegisCard v-if="pending.length" title="Pending invitations">
      <div class="steward-card-grid">
        <StewardCard v-for="s in pending" :key="s.id" :steward="{ ...s, status: 'invited' }" kind="ss"
          @resend="resend(s)" @cancel="cancel(s)" />
      </div>
    </AegisCard>

    <AegisEmptyState
      v-if="!active.length && !pending.length"
      icon="users-line"
      title="No Support Steward yet"
      description="A Support Steward handles non-clinical logistics so your CS can focus on clients."
    >
      <template #action>
        <button type="button" class="btn btn-primary" @click="openModal('inviteSsModal')">
          Invite Steward
        </button>
      </template>
    </AegisEmptyState>

    <AegisModal
      :model-value="isOpen('inviteSsModal').value"
      title="Invite a Support Steward"
      @update:model-value="(v) => !v && closeModal('inviteSsModal')"
    >
      <form @submit.prevent="invite">
        <div class="form-group">
          <label class="form-label">Email <span class="req">*</span></label>
          <input v-model="inviteForm.email" type="email" required class="form-input" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">First name</label>
            <input v-model="inviteForm.first_name" type="text" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Last name</label>
            <input v-model="inviteForm.last_name" type="text" class="form-input" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Personal note</label>
          <textarea v-model="inviteForm.note" class="form-input" rows="4"></textarea>
        </div>
      </form>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="closeModal('inviteSsModal')">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="inviteForm.processing" @click="invite">
          {{ inviteForm.processing ? 'Sending…' : 'Send invitation' }}
        </button>
      </template>
    </AegisModal>
  </AppLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import StewardCard from '@/components/features/StewardCard.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'

defineProps({ active: { type: Array, default: () => [] }, pending: { type: Array, default: () => [] } })

const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()
const inviteForm = useForm({ email: '', first_name: '', last_name: '', note: '' })

function invite() {
  inviteForm.post(route('provider.support-stewards.invite'), {
    preserveScroll: true,
    onSuccess: () => { toast.success('Invitation sent.'); inviteForm.reset(); closeModal('inviteSsModal') },
  })
}
function msg(s)    { router.visit(route('provider.messages', { with: s.id })) }
function remove(s) {
  if (!window.confirm(`Remove ${s.display_name}?`)) return
  router.delete(route('provider.support-stewards.destroy', { steward: s.id }),
    { onSuccess: () => toast.success('Steward removed.') })
}
function resend(s) { router.post(route('provider.support-stewards.resend', { invitation: s.id }), {}, { onSuccess: () => toast.success('Invitation resent.') }) }
function cancel(s) { router.delete(route('provider.support-stewards.cancel-invite', { invitation: s.id }), { onSuccess: () => toast.success('Invitation cancelled.') }) }
</script>
