<!--
  ConnectionRequestModal.vue — centralized "Send Connection Request" modal.

  Replaces the inline connect AegisModal block in Network.vue and any other
  page that needs to send a network connection request (e.g. ProviderProfile
  or Search Providers cards).

  Opens via:  openModal('connectionRequestModal')
  Preselect:  set the `recipientId` / `recipientName` / `recipientRole` props
              before openModal() — usually via a reactive target object owned
              by the parent page.

  Submits to: provider.network.connect
  Emits:      sent — parent can refresh or toast further
-->
<template>
  <AegisModal
    :model-value="isOpen('connectionRequestModal').value"
    title="Send Connection Request"
    subtitle="Introduce yourself and explain why you'd like to connect"
    size="md"
    @update:model-value="onUpdateOpen"
  >
    <!-- Target -->
    <div v-if="recipientName" class="alert alert-info" style="margin-bottom:16px">
      <AegisIcon name="user-plus" :size="16" />
      <div>
        <strong>{{ recipientName }}</strong>
        <span v-if="recipientRole"> · {{ recipientRole }}</span>
      </div>
    </div>

    <!-- Note -->
    <div class="form-group">
      <label class="form-label">Message (optional)</label>
      <textarea
        v-model="form.note"
        class="form-textarea"
        rows="4"
        placeholder="Introduce yourself, share your specialties, or explain why you'd like to connect…"
      ></textarea>
      <div v-if="form.errors.note" class="form-error">{{ form.errors.note }}</div>
      <div class="form-hint">Kept private between you and the recipient.</div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="form.processing" @click="onCancel">Cancel</button>
      <button type="button" class="btn btn-primary" :disabled="form.processing || !recipientId" @click="submit">
        <AegisIcon name="send" :size="14" />
        {{ form.processing ? 'Sending…' : 'Send Request' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { watch }     from 'vue'
import { useForm }   from '@inertiajs/vue3'
import { route }     from 'ziggy-js'
import AegisIcon     from '@/components/ui/AegisIcon.vue'
import AegisModal    from '@/components/ui/AegisModal.vue'
import { useModal }  from '@/composables/useModal'
import { useToast }  from '@/composables/useToast'

const props = defineProps({
  recipientId:   { type: [Number, String], default: null },
  recipientName: { type: String,           default: '' },
  recipientRole: { type: String,           default: '' },
})
const emit = defineEmits(['sent'])

const { isOpen, closeModal } = useModal()
const toast = useToast()

const form = useForm({
  to_user_id: props.recipientId ?? '',
  note:       '',
})

// Keep the form's target in sync when the parent retargets before open
watch(() => props.recipientId, (v) => { form.to_user_id = v ?? '' })

function onUpdateOpen(open) {
  if (!open) closeModal('connectionRequestModal')
}

function onCancel() {
  closeModal('connectionRequestModal')
}

function submit() {
  if (!props.recipientId) {
    toast.error('No recipient selected.')
    return
  }
  form.post(route('provider.network.connect'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Connection request sent!')
      emit('sent')
      closeModal('connectionRequestModal')
      setTimeout(() => form.reset('note'), 200)
    },
    onError: () => {
      toast.error('Could not send the request. Please try again.')
    },
  })
}
</script>
