<!--
  AegisDropzone.vue — drag-and-drop / click-to-browse file upload zone.

  Behaviour:
  • Drop zone is ALWAYS visible — files never hide it.
  • Dropping/selecting files APPENDS when multiple=true, REPLACES when multiple=false.
  • Each file shows a pill: type icon + name + size + × remove.
  • Removing all files emits @files([]) so parent can clear form field.
  • Emits @files with full current File array after every change.

  Usage:
    <AegisDropzone accept=".pdf,.jpg" hint="PDF or JPG, max 10 MB" @files="form.document = $event[0]" />
    <AegisDropzone :multiple="true" @files="form.attachments = $event" />
-->
<template>
  <div class="adz-root" :class="{ 'adz--disabled': disabled }">

    <!-- Hidden file input — lives OUTSIDE the click zone to prevent event bubbling re-open -->
    <input
      ref="fileInput"
      type="file"
      class="aegis-dropzone-input"
      :accept="accept"
      :multiple="multiple"
      :disabled="disabled"
      @change="onChange"
    />

    <!-- Drop / browse zone — always rendered -->
    <div
      class="aegis-dropzone"
      :class="{ 'is-dragging': dragging }"
      @dragover.prevent="onDragOver"
      @dragleave.prevent="onDragLeave"
      @drop.prevent="onDrop"
      @click="onZoneClick"
      role="button"
      tabindex="0"
      @keydown.enter.prevent="onZoneClick"
      @keydown.space.prevent="onZoneClick"
    >
      <div class="aegis-dropzone-content">
        <AegisIcon name="upload" :size="22" />
        <div class="aegis-dropzone-title">
          {{ files.length ? 'Drop more or' : 'Drop files here or' }}
          <span class="aegis-dropzone-pick">browse</span>
        </div>
        <div class="aegis-dropzone-hint">{{ hint }}</div>
      </div>
    </div>

    <!-- File pills — rendered below the zone -->
    <ul v-if="files.length" class="adz-file-list">
      <li v-for="(f, i) in files" :key="f.name + f.size + i" class="adz-file-item" @click.stop>
        <div class="adz-file-icon">
          <AegisIcon :name="iconFor(f)" :size="15" />
        </div>
        <div class="adz-file-info">
          <span class="adz-file-name">{{ f.name }}</span>
          <span class="adz-file-size">{{ sizeLabel(f.size) }}</span>
        </div>
        <button
          type="button"
          class="adz-file-remove"
          data-tooltip="Remove"
          @click.stop="remove(i)"
        >
          <AegisIcon name="x" :size="12" />
        </button>
      </li>
    </ul>

    <!-- Over-size rejection notice -->
    <p v-if="rejections.length" class="adz-rejected">
      <AegisIcon name="alert-triangle" :size="12" />
      Too large (max {{ maxSizeMb }} MB): {{ rejections.join(', ') }}
    </p>

  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  accept:    { type: String,  default: '' },
  multiple:  { type: Boolean, default: false },
  disabled:  { type: Boolean, default: false },
  maxSizeMb: { type: Number,  default: 25 },
  hint:      { type: String,  default: 'PDF, JPG, PNG up to 25 MB' },
})

const emit = defineEmits(['files', 'rejected'])

const dragging   = ref(false)
const fileInput  = ref(null)
const files      = ref([])
const rejections = ref([])

function onDragOver()  { if (!props.disabled) dragging.value = true }
function onDragLeave() { dragging.value = false }
function onDrop(e)     { dragging.value = false; if (!props.disabled) process(Array.from(e.dataTransfer?.files ?? [])) }
function onZoneClick() { if (!props.disabled) fileInput.value?.click() }
function onChange(e)   { process(Array.from(e.target.files ?? [])); e.target.value = '' }

function remove(i) {
  files.value.splice(i, 1)
  emit('files', [...files.value])
}

function process(incoming) {
  const maxBytes = props.maxSizeMb * 1024 * 1024
  const accepted = []
  const rejected = []

  for (const f of incoming) {
    f.size > maxBytes ? rejected.push(f) : accepted.push(f)
  }

  rejections.value = rejected.map(f => f.name)

  if (!accepted.length) {
    if (rejected.length) emit('rejected', rejected)
    return
  }

  if (props.multiple) {
    // Append, skip exact duplicates by name+size
    const seen = new Set(files.value.map(f => f.name + f.size))
    for (const f of accepted) {
      if (!seen.has(f.name + f.size)) { files.value.push(f); seen.add(f.name + f.size) }
    }
  } else {
    files.value = [accepted[0]]
  }

  emit('files', [...files.value])
  if (rejected.length) emit('rejected', rejected)
}

function iconFor(f) {
  const ext = (f.name.split('.').pop() ?? '').toLowerCase()
  if (ext === 'pdf')                                   return 'file-text'
  if (['doc', 'docx'].includes(ext))                  return 'file-pen'
  if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(ext)) return 'file'
  return 'file'
}

function sizeLabel(b) {
  if (b < 1024)        return b + ' B'
  if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB'
  return (b / 1024 / 1024).toFixed(1) + ' MB'
}
</script>

<style scoped>
.adz-root      { display: flex; flex-direction: column; gap: 8px; }
.aegis-dropzone-input { position: absolute; width: 0; height: 0; opacity: 0; pointer-events: none; overflow: hidden; }
.adz--disabled { opacity: 0.55; pointer-events: none; }

.adz-file-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }

.adz-file-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  transition: border-color var(--transition);
}
.adz-file-item:hover { border-color: var(--border-dark); }

.adz-file-icon {
  width: 30px; height: 30px;
  border-radius: var(--radius-sm);
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.adz-file-info  { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1px; }
.adz-file-name  { font-size: 12px; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.adz-file-size  { font-size: 10px; color: var(--text-4); font-weight: 500; }

.adz-file-remove {
  width: 22px; height: 22px;
  border-radius: var(--radius-full);
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--text-4);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; flex-shrink: 0;
  transition: background var(--transition), color var(--transition), border-color var(--transition);
}
.adz-file-remove:hover { background: var(--red-light); color: var(--red-dark); border-color: var(--soft-red); }

.adz-rejected {
  display: flex; align-items: center; gap: 5px;
  font-size: 11px; color: var(--red-dark); margin: 0;
}
</style>
