<!--
  pages/business-partner/Team.vue — agency team members (agency BPs only).
  Freelancer BPs see a soft-upgrade prompt to convert to agency.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="My Agency"
      title="Team"
      :subtitle="auth.isAgency ? 'Members of your agency.' : 'Convert to an agency to add team members.'"
    >
      <template #actions>
        <button
          v-if="auth.isAgency"
          type="button"
          class="btn btn-primary"
          @click="openModal('inviteMemberModal')"
        >
          <AegisIcon name="user-plus" :size="14" />
          <span>Invite member</span>
        </button>
      </template>
    </AegisHeroBanner>

    <template v-if="auth.isAgency">
      <div class="stat-chips-row">
        <AegisStatChip icon="users" :value="members.length" label="Members" />
        <AegisStatChip icon="hourglass" :value="pending.length" label="Pending invites" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
      </div>

      <AegisCard v-if="members.length" title="Members">
        <table class="data-table">
          <thead><tr><th>Name</th><th>Role</th><th>Email</th><th>Joined</th><th></th></tr></thead>
          <tbody>
            <tr v-for="m in members" :key="m.id">
              <td class="data-table-primary">{{ m.display_name }}</td>
              <td><AegisBadge :label="m.role" variant="blue" /></td>
              <td>{{ m.email }}</td>
              <td>{{ activity.timeAgo(m.joined_at) }}</td>
              <td>
                <button type="button" class="btn btn-sm btn-ghost btn-danger-ghost" @click="remove(m)" v-if="m.id !== auth.user?.id">
                  Remove
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </AegisCard>

      <AegisCard v-if="pending.length" title="Pending invitations">
        <ul class="pending-list">
          <li v-for="p in pending" :key="p.id" class="pending-row">
            <div>
              <div class="pending-name">{{ p.email }}</div>
              <div class="pending-meta">Invited {{ activity.timeAgo(p.invited_at) }}</div>
            </div>
            <div class="pending-actions">
              <button type="button" class="btn btn-sm btn-outline" @click="resend(p)">Resend</button>
              <button type="button" class="btn btn-sm btn-ghost btn-danger-ghost" @click="cancelInv(p)">Cancel</button>
            </div>
          </li>
        </ul>
      </AegisCard>

      <AegisModal
        :model-value="isOpen('inviteMemberModal').value"
        title="Invite team member"
        @update:model-value="(v) => !v && close()"
      >
        <form @submit.prevent="invite">
          <div class="form-group">
            <label class="form-label">Email <span class="req">*</span></label>
            <input v-model="inviteForm.email" type="email" required class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Role</label>
            <select v-model="inviteForm.role" class="form-input">
              <option value="member">Member</option>
              <option value="manager">Manager</option>
              <option value="owner">Owner</option>
            </select>
          </div>
        </form>
        <template #footer>
          <button type="button" class="btn btn-outline" @click="close">Cancel</button>
          <button type="button" class="btn btn-primary" :disabled="inviteForm.processing" @click="invite">
            {{ inviteForm.processing ? 'Sending…' : 'Send invitation' }}
          </button>
        </template>
      </AegisModal>
    </template>

    <AegisEmptyState
      v-else
      icon="users"
      title="Team is an agency feature"
      description="Convert your account to an agency to add members and accept team-scale contracts."
    >
      <template #action>
        <a :href="route('bp.profile')" class="btn btn-primary">Convert to agency</a>
      </template>
    </AegisEmptyState>
  </AppLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useAuthStore } from '@/stores/auth'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'

defineProps({
  members: { type: Array, default: () => [] },
  pending: { type: Array, default: () => [] },
})

const auth = useAuthStore()
const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()
const activity = useActivity()

const inviteForm = useForm({ email: '', role: 'member' })

function close() { closeModal('inviteMemberModal'); setTimeout(() => inviteForm.reset(), 200) }
function invite() {
  inviteForm.post(route('bp.team.invite'), {
    preserveScroll: true,
    onSuccess: () => { toast.success('Invitation sent.'); close() },
  })
}
function remove(m) {
  if (!window.confirm(`Remove ${m.display_name}?`)) return
  router.delete(route('bp.team.destroy', { member: m.id }), { onSuccess: () => toast.success('Member removed.') })
}
function resend(p)    { router.post(route('bp.team.resend', { invitation: p.id }), {}, { onSuccess: () => toast.success('Invitation resent.') }) }
function cancelInv(p) { router.delete(route('bp.team.cancel-invite', { invitation: p.id }), { onSuccess: () => toast.success('Invitation cancelled.') }) }
</script>
