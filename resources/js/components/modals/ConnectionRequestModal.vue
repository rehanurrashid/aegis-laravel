<template>
  <AegisModal
    :model-value="isOpen('connectionRequestModal').value"
    title="Send Connection Request"
    subtitle="Introduce yourself and explain why you'd like to connect"
    size="md"
    @update:model-value="(v) => { if (!v) closeModal('connectionRequestModal') }"
  >
    <div v-if="recipientName" class="alert alert-info" style="margin-bottom:16px">
      <AegisIcon name="user-plus" :size="16" />
      <div>
        <strong>{{ recipientName }}</strong>
        <span v-if="recipientRole"> · {{ recipientRole }}</span>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Message <span class="form-hint" style="display:inline">(optional)</span></label>
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
      <button type="button" class="btn btn-outline" :disabled="form.processing" @click="closeModal('connectionRequestModal')">Cancel</button>
      <button type="button" class="btn btn-primary" :disabled="form.processing || !recipientId" @click="submit">
        <AegisIcon name="send" :size="14" />
        <span v-if="form.processing" class="spinner spinner-sm" />
        {{ form.processing ? 'Sending…' : 'Send Request' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { watch }    from 'vue'
import { useForm }  from '@inertiajs/vue3'
import AegisIcon    from '@/components/ui/AegisIcon.vue'
import AegisModal   from '@/components/ui/AegisModal.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  recipientId:   { type: [Number, String], default: null },
  recipientName: { type: String,           default: '' },
  recipientRole: { type: String,           default: '' },
})
const emit = defineEmits(['sent'])

const { isOpen, closeModal } = useModal()
const toast = useToast()

const form = useForm({ to_user_id: '', note: '' })

watch(() => props.recipientId, (v) => { form.to_user_id = v ?? '' }, { immediate: true })

function submit() {
  if (!props.recipientId) { toast.error('No recipient selected.'); return }
  form.post(route('provider.network.connect'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Connection request sent!')
      emit('sent')
      closeModal('connectionRequestModal')
      setTimeout(() => form.reset('note'), 200)
    },
    onError: () => toast.error('Could not send the request. Please try again.'),
  })
}
</script>
