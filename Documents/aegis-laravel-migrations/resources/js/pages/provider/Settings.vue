<!--
  pages/provider/Settings.vue — account preferences.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Account"
      title="Settings"
      subtitle="Login, notifications, and privacy preferences."
    />

    <AegisCard title="Account">
      <form @submit.prevent="saveAccount">
        <div class="form-group">
          <label class="form-label">Email</label>
          <input v-model="accountForm.email" type="email" class="form-input" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Current password</label>
            <input v-model="accountForm.current_password" type="password" autocomplete="current-password" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">New password</label>
            <input v-model="accountForm.password" type="password" autocomplete="new-password" class="form-input" />
          </div>
        </div>
        <button type="submit" class="btn btn-primary" :disabled="accountForm.processing">
          {{ accountForm.processing ? 'Saving…' : 'Save account' }}
        </button>
      </form>
    </AegisCard>

    <AegisCard title="Notifications">
      <AegisToggle v-model="notifForm.notify_email"      label="Email notifications" />
      <AegisToggle v-model="notifForm.notify_critical"   label="Critical incidents only (after-hours)" />
      <AegisToggle v-model="notifForm.notify_referrals"  label="New referrals" />
      <AegisToggle v-model="notifForm.notify_messages"   label="New messages" />
      <AegisToggle v-model="notifForm.notify_attestation" label="Attestation reminders" />
      <div class="form-actions-bar">
        <button type="button" class="btn btn-primary" :disabled="notifForm.processing" @click="saveNotif">
          {{ notifForm.processing ? 'Saving…' : 'Save notifications' }}
        </button>
      </div>
    </AegisCard>

    <AegisCard title="Privacy">
      <AegisToggle v-model="privacyForm.profile_indexable" label="Allow search engines to index my public profile" />
      <AegisToggle v-model="privacyForm.show_email"        label="Show contact email on public profile" />
      <AegisToggle v-model="privacyForm.show_phone"        label="Show contact phone on public profile" />
      <div class="form-actions-bar">
        <button type="button" class="btn btn-primary" :disabled="privacyForm.processing" @click="savePrivacy">
          {{ privacyForm.processing ? 'Saving…' : 'Save privacy' }}
        </button>
      </div>
    </AegisCard>

    <AegisCard title="Danger zone" quiet>
      <button type="button" class="btn btn-outline btn-danger-outline" @click="openModal('deleteAccountModal')">
        Delete account
      </button>
    </AegisCard>

    <AegisConfirm
      :model-value="isOpen('deleteAccountModal').value"
      title="Delete your account?"
      destructive
      primary-label="Yes, delete permanently"
      @update:model-value="(v) => !v && closeModal('deleteAccountModal')"
      @confirm="deleteAccount"
    >
      <p>This permanently removes your profile, vault, and history. Your stewards will be notified.</p>
    </AegisConfirm>
  </AppLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisConfirm from '@/components/ui/AegisConfirm.vue'
import AegisToggle from '@/components/ui/AegisToggle.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  account:  { type: Object, default: () => ({ email: '' }) },
  notif:    { type: Object, default: () => ({}) },
  privacy:  { type: Object, default: () => ({}) },
})

const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()

const accountForm = useForm({ email: props.account.email, current_password: '', password: '' })
const notifForm   = useForm({ ...props.notif })
const privacyForm = useForm({ ...props.privacy })

function saveAccount()  { accountForm.put(route('provider.settings.account'),  { preserveScroll: true, onSuccess: () => toast.success('Account updated.') }) }
function saveNotif()    { notifForm.put(route('provider.settings.notif'),     { preserveScroll: true, onSuccess: () => toast.success('Notifications updated.') }) }
function savePrivacy()  { privacyForm.put(route('provider.settings.privacy'), { preserveScroll: true, onSuccess: () => toast.success('Privacy updated.') }) }
function deleteAccount(){ router.delete(route('provider.settings.destroy'),    { onSuccess: () => toast.success('Account deleted.') }) }
</script>
