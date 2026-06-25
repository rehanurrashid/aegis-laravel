<!--
  pages/business-partner/Settings.vue — BP account preferences.
-->
<template>
  <AppLayout>
    <AegisHeroBanner eyebrow="Account" title="Settings" subtitle="Login, notifications, and account preferences." />

    <AegisCard title="Account">
      <form @submit.prevent="saveAccount">
        <div class="form-group">
          <label class="form-label">Email</label>
          <input v-model="accountForm.email" type="email" class="form-input" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Current password</label>
            <input v-model="accountForm.current_password" type="password" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">New password</label>
            <input v-model="accountForm.password" type="password" class="form-input" />
          </div>
        </div>
        <button type="submit" class="btn btn-primary" :disabled="accountForm.processing">Save account</button>
      </form>
    </AegisCard>

    <AegisCard title="Notifications">
      <AegisToggle v-model="notifForm.notify_new_jobs"      label="New jobs matching my services" />
      <AegisToggle v-model="notifForm.notify_proposals"     label="Proposal status updates" />
      <AegisToggle v-model="notifForm.notify_milestones"    label="Milestone approvals and feedback" />
      <AegisToggle v-model="notifForm.notify_invoices_paid" label="Invoice payments" />
      <AegisToggle v-model="notifForm.notify_messages"      label="New messages" />
      <div class="form-actions-bar">
        <button type="button" class="btn btn-primary" :disabled="notifForm.processing" @click="saveNotif">Save notifications</button>
      </div>
    </AegisCard>

    <AegisCard title="Discoverability">
      <AegisToggle v-model="visibilityForm.discoverable" label="My profile is discoverable to practitioners" />
      <AegisToggle v-model="visibilityForm.accepting_work" label="Accepting new contracts" />
      <div class="form-actions-bar">
        <button type="button" class="btn btn-primary" :disabled="visibilityForm.processing" @click="saveVisibility">Save visibility</button>
      </div>
    </AegisCard>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisToggle from '@/components/ui/AegisToggle.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  account:    { type: Object, default: () => ({ email: '' }) },
  notif:      { type: Object, default: () => ({}) },
  visibility: { type: Object, default: () => ({}) },
})

const toast = useToast()
const accountForm    = useForm({ email: props.account.email, current_password: '', password: '' })
const notifForm      = useForm({ ...props.notif })
const visibilityForm = useForm({ ...props.visibility })

function saveAccount()    { accountForm.put(route('bp.settings.account'),       { preserveScroll: true, onSuccess: () => toast.success('Account updated.') }) }
function saveNotif()      { notifForm.put(route('bp.settings.notif'),           { preserveScroll: true, onSuccess: () => toast.success('Notifications updated.') }) }
function saveVisibility() { visibilityForm.put(route('bp.settings.visibility'), { preserveScroll: true, onSuccess: () => toast.success('Visibility updated.') }) }
</script>
