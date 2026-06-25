<!--
  pages/support-steward/Settings.vue — SS account preferences.
-->
<template>
  <AppLayout>
    <AegisHeroBanner eyebrow="Account" title="Settings" subtitle="Login, notifications, and availability." />

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

    <AegisCard title="Availability">
      <AegisToggle v-model="prefForm.accepting_new" label="Accepting new designations" />
      <AegisToggle v-model="prefForm.after_hours_alerts" label="After-hours alerts" />
      <div class="form-actions-bar">
        <button type="button" class="btn btn-primary" :disabled="prefForm.processing" @click="savePrefs">Save preferences</button>
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

const props = defineProps({ account: { type: Object, default: () => ({ email: '' }) }, prefs: { type: Object, default: () => ({}) } })
const toast = useToast()
const accountForm = useForm({ email: props.account.email, current_password: '', password: '' })
const prefForm    = useForm({ ...props.prefs })

function saveAccount() { accountForm.put(route('ss.settings.account'), { preserveScroll: true, onSuccess: () => toast.success('Account updated.') }) }
function savePrefs()   { prefForm.put(route('ss.settings.prefs'),       { preserveScroll: true, onSuccess: () => toast.success('Preferences updated.') }) }
</script>
