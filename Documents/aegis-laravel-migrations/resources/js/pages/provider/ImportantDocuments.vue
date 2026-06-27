<!--
  pages/provider/ImportantDocuments.vue — non-vault practice documents.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Continuity"
      title="Important Documents"
      subtitle="Practice documents that aren't client records — policies, agreements, templates."
    >
      <template #actions>
        <button type="button" class="btn btn-primary" @click="openModal('uploadDocModal')">
          <AegisIcon name="upload" :size="14" />
          <span>Upload document</span>
        </button>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="file-text" :value="documents.length" label="Documents" />
      <AegisStatChip icon="folder"    :value="categories.length" label="Categories" bg-color="var(--icon-bg-blue)" icon-color="var(--blue-dark)" />
    </div>

    <AegisCard v-if="documents.length" title="Practice documents">
      <table class="data-table">
        <thead>
          <tr>
            <th>Document</th>
            <th>Category</th>
            <th>Size</th>
            <th>Updated</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in documents" :key="d.id">
            <td class="data-table-primary">
              <AegisIcon name="file-text" :size="13" />
              {{ d.title }}
            </td>
            <td>{{ d.category ?? '—' }}</td>
            <td>{{ formatBytes(d.size_bytes) }}</td>
            <td>{{ activity.timeAgo(d.updated_at) }}</td>
            <td>
              <a :href="d.download_url" class="btn btn-ghost btn-icon btn-sm" data-tip="Download">
                <AegisIcon name="download" :size="13" />
              </a>
              <button type="button" class="btn btn-ghost btn-icon btn-sm btn-danger-ghost" data-tip="Delete" @click="del(d)">
                <AegisIcon name="trash" :size="13" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState
      v-else
      icon="file-text"
      title="No documents yet"
      description="Upload your practice policies, agreements, and templates."
    />

    <AegisModal
      :model-value="isOpen('uploadDocModal').value"
      title="Upload document"
      @update:model-value="(v) => !v && close()"
    >
      <form @submit.prevent="upload">
        <div class="form-group">
          <label class="form-label">Title</label>
          <input v-model="form.title" type="text" class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <input v-model="form.category" type="text" class="form-input" placeholder="Policy, Template, Reference…" />
        </div>
        <AegisDropzone @files="form.file = $event[0]" />
      </form>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="close">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="!form.file || form.processing" @click="upload">
          {{ form.processing ? 'Uploading…' : 'Upload' }}
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
import AegisDropzone from '@/components/ui/AegisDropzone.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'

defineProps({
  documents:  { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
})

const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()
const activity = useActivity()

const form = useForm({ title: '', category: '', file: null })

function formatBytes(b) {
  if (!b) return '—'
  if (b < 1024) return b + ' B'
  if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB'
  return (b / 1024 / 1024).toFixed(1) + ' MB'
}

function close() { closeModal('uploadDocModal'); setTimeout(() => form.reset(), 200) }
function upload() {
  form.post(route('provider.documents.store'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => { toast.success('Document uploaded.'); close() },
  })
}
function del(d) {
  if (!window.confirm(`Delete "${d.title}"?`)) return
  router.delete(route('provider.documents.destroy', { document: d.id }),
    { onSuccess: () => toast.success('Document deleted.') })
}
</script>
