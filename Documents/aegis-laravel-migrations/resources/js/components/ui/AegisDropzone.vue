<!--
  AegisDropzone.vue — drag-and-drop file upload zone.

  Uses the .aegis-dropzone class family from _shared.css. Emits the raw
  File objects up to the parent; the parent decides how to upload them
  (multipart POST via useForm() typically).

  Props:
    accept     — comma-separated MIME types or extensions (e.g., 'image/*,.pdf')
    multiple   — allow multi-select
    maxSize    — max bytes per file (default 25MB)
    hint       — small text under the icon
-->
<template>
  <div
    class="aegis-dropzone"
    :class="{ 'is-dragging': dragging, 'is-disabled': disabled }"
    @dragover.prevent="onDragOver"
    @dragleave.prevent="onDragLeave"
    @drop.prevent="onDrop"
    @click="onPickClick"
    role="button"
    tabindex="0"
    @keydown.enter.prevent="onPickClick"
    @keydown.space.prevent="onPickClick"
  >
    <input
      ref="fileInput"
      type="file"
      class="aegis-dropzone-input"
      :accept="accept"
      :multiple="multiple"
      :disabled="disabled"
      @change="onChange"
    />

    <div class="aegis-dropzone-content">
      <AegisIcon name="upload" :size="24" />
      <div class="aegis-dropzone-title">
        Drop files here or <span class="aegis-dropzone-pick">browse</span>
      </div>
      <div v-if="hint" class="aegis-dropzone-hint">{{ hint }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  accept:   { type: String,  default: '' },
  multiple: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  maxSize:  { type: Number,  default: 25 * 1024 * 1024 }, // 25 MB
  hint:     { type: String,  default: 'PDF, DOCX, PNG, JPG up to 25 MB' },
})

const emit = defineEmits(['files', 'rejected'])

const dragging  = ref(false)
const fileInput = ref(null)

function onDragOver() {
  if (!props.disabled) dragging.value = true
}

function onDragLeave() {
  dragging.value = false
}

function onDrop(e) {
  dragging.value = false
  if (props.disabled) return
  const files = Array.from(e.dataTransfer?.files ?? [])
  emitFiles(files)
}

function onPickClick() {
  if (!props.disabled) fileInput.value?.click()
}

function onChange(e) {
  const files = Array.from(e.target.files ?? [])
  emitFiles(files)
  // Reset so picking the same file again triggers `change`.
  e.target.value = ''
}

function emitFiles(files) {
  const accepted = []
  const rejected = []

  for (const file of files) {
    if (file.size > props.maxSize) {
      rejected.push({ file, reason: 'oversize' })
      continue
    }
    accepted.push(file)
  }

  if (accepted.length) emit('files', accepted)
  if (rejected.length) emit('rejected', rejected)
}
</script>
