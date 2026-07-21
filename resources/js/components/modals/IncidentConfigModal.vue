<!--
  modals/IncidentConfigModal.vue — configure one incident type.
  Tabs: SS Tasks · CS Tasks · Documentation · Authorized Stewards
-->
<template>
  <AegisModal :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" size="xl" :title="`Configure: ${incidentType?.label ?? ''}`">

    <!-- Enable toggle -->
    <div class="m-section" style="margin-bottom:16px">
      <div class="setting-row" style="margin-top:0">
        <div class="setting-info">
          <div class="setting-label">Enable this incident type</div>
          <div class="setting-desc">
            <span v-if="incidentType?.is_optin">Opt-in — requires your explicit consent.</span>
            <span v-else>Always-on incident type.</span>
          </div>
        </div>
        <span :data-tooltip="!incidentType?.is_optin ? 'This incident type is always required and cannot be disabled' : undefined">
          <AegisToggle :model-value="isActive" :disabled="!incidentType?.is_optin" @update:model-value="handleActiveToggle" />
        </span>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs-primary" style="margin-bottom:16px">
      <button v-for="tab in tabs" :key="tab.key" type="button"
        class="tab-primary" :class="{ active: activeTab === tab.key }"
        @click="activeTab = tab.key">
        <AegisIcon :name="tab.icon" :size="13" />
        {{ tab.label }}
        <span v-if="tab.count !== undefined" class="count-badge" style="margin-left:4px">{{ tab.count }}</span>
      </button>
    </div>

    <!-- Tab: SS Tasks -->
    <div v-show="activeTab === 'ss'">
      <div ref="ssTaskListEl" class="task-list" style="margin-bottom:8px">
        <div v-for="(t, i) in ssTasks" :key="t._key ?? i" class="task-item draggable-item">
          <AegisIcon name="menu" :size="13" style="color:var(--text-4);flex-shrink:0;cursor:grab" class="drag-handle" />
          <span style="flex:1;font-size:13px;color:var(--text);font-weight:500">{{ t.title }}</span>
          <span v-if="t.timeline" style="font-size:11px;color:var(--text-3);font-weight:600;padding:3px 8px;background:var(--surface-2);border-radius:var(--radius-xs);white-space:nowrap">{{ t.timeline }}</span>
          <button type="button" class="btn-icon" data-tooltip="Remove"
            @click="confirmAction('Remove this task from the plan?', () => ssTasks.splice(i,1), { title: 'Remove Task', btnLabel: 'Remove', type: 'danger' })">
            <AegisIcon name="trash" :size="13" />
          </button>
        </div>
        <div v-if="!ssTasks.length" style="font-size:12px;color:var(--text-4);padding:8px 0;font-style:italic">No SS tasks yet. Add one below.</div>
      </div>
      <div style="display:flex;gap:8px;padding-top:4px;align-items:center">
        <input v-model="newSsTask" class="form-input" type="text" placeholder="Add Support Steward task…" style="flex:1;min-width:0" @keydown.enter.prevent="addTask('ss')" />
        <div style="width:130px;flex-shrink:0">
          <select v-model="newSsTimeline" class="form-select" style="width:100%">
            <option value="">Timeline…</option>
            <option v-for="tl in timelineOptions" :key="tl" :value="tl">{{ tl }}</option>
          </select>
        </div>
        <button type="button" class="btn btn-outline" style="flex-shrink:0" @click="addTask('ss')">Add</button>
      </div>
    </div>

    <!-- Tab: CS Tasks -->
    <div v-show="activeTab === 'cs'">
      <div ref="csTaskListEl" class="task-list" style="margin-bottom:8px">
        <div v-for="(t, i) in csTasks" :key="t._key ?? i" class="task-item draggable-item">
          <AegisIcon name="menu" :size="13" style="color:var(--text-4);flex-shrink:0;cursor:grab" class="drag-handle" />
          <span style="flex:1;font-size:13px;color:var(--text);font-weight:500">{{ t.title }}</span>
          <span v-if="t.timeline" style="font-size:11px;color:var(--text-3);font-weight:600;padding:3px 8px;background:var(--surface-2);border-radius:var(--radius-xs);white-space:nowrap">{{ t.timeline }}</span>
          <button type="button" class="btn-icon" data-tooltip="Remove"
            @click="confirmAction('Remove this task from the plan?', () => csTasks.splice(i,1), { title: 'Remove Task', btnLabel: 'Remove', type: 'danger' })">
            <AegisIcon name="trash" :size="13" />
          </button>
        </div>
        <div v-if="!csTasks.length" style="font-size:12px;color:var(--text-4);padding:8px 0;font-style:italic">No CS tasks yet. Add one below.</div>
      </div>
      <div style="display:flex;gap:8px;padding-top:4px;align-items:center">
        <input v-model="newCsTask" class="form-input" type="text" placeholder="Add Continuity Steward task…" style="flex:1;min-width:0" @keydown.enter.prevent="addTask('cs')" />
        <div style="width:130px;flex-shrink:0">
          <select v-model="newCsTimeline" class="form-select" style="width:100%">
            <option value="">Timeline…</option>
            <option v-for="tl in timelineOptions" :key="tl" :value="tl">{{ tl }}</option>
          </select>
        </div>
        <button type="button" class="btn btn-outline" style="flex-shrink:0" @click="addTask('cs')">Add</button>
      </div>
    </div>

    <!-- Tab: Documentation -->
    <div v-show="activeTab === 'docs'">
      <div style="display:flex;flex-direction:column;gap:8px;margin-top:4px">
        <label v-for="doc in docOptions" :key="doc.value" class="form-check" style="padding:10px 14px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-sm)">
          <input type="checkbox" :value="doc.value" v-model="selectedDocs" />
          <span class="form-check-label">
            <span style="font-weight:600;font-size:13px">{{ doc.label }}</span>
          </span>
        </label>
      </div>
    </div>

    <!-- Tab: Authorized Stewards -->
    <div v-show="activeTab === 'stewards'">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:4px">
        <div>
          <div style="font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--text-3);margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid var(--border)">Support Stewards</div>
          <div v-if="ssList.length" style="display:flex;flex-direction:column;gap:6px">
            <label v-for="s in ssList" :key="s.steward_id" class="form-check" style="padding:8px 12px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-sm)">
              <input type="checkbox" :value="s.steward_id" v-model="authSsIds" />
              <span class="form-check-label" style="display:flex;align-items:center;gap:8px;flex:1">
                <span style="width:20px;height:20px;border-radius:50%;background:var(--text-3);color:#fff;font-size:6px;font-weight:700;font-family:var(--font-serif);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;padding:0">
                  <img v-if="s.avatar_url" :src="s.avatar_url" style="width:100%;height:100%;object-fit:cover" />
                  <span v-else>{{ s.avatar_initials }}</span>
                </span>
                <span style="font-weight:600;font-size:13px;color:var(--text)">{{ s.display_name }}</span>
                <span style="margin-left:auto;font-size:10px;color:var(--text-4);font-weight:600;text-transform:capitalize">{{ s.role }}</span>
              </span>
            </label>
          </div>
          <p v-else style="font-size:12px;color:var(--text-4);margin:0">
            No SS assigned. <a :href="route('provider.ss.index')" style="color:var(--gold-dark)">Add one →</a>
          </p>
        </div>
        <div>
          <div style="font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--text-3);margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid var(--border)">Continuity Stewards</div>
          <div v-if="csList.length" style="display:flex;flex-direction:column;gap:6px">
            <label v-for="s in csList" :key="s.steward_id" class="form-check" style="padding:8px 12px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-sm)">
              <input type="checkbox" :value="s.steward_id" v-model="authCsIds" />
              <span class="form-check-label" style="display:flex;align-items:center;gap:8px;flex:1">
                <span style="width:20px;height:20px;border-radius:50%;background:var(--gold-dark);color:#fff;font-size:6px;font-weight:700;font-family:var(--font-serif);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;padding:0">
                  <img v-if="s.avatar_url" :src="s.avatar_url" style="width:100%;height:100%;object-fit:cover" />
                  <span v-else>{{ s.avatar_initials }}</span>
                </span>
                <span style="font-weight:600;font-size:13px;color:var(--text)">{{ s.display_name }}</span>
                <span style="margin-left:auto;font-size:10px;color:var(--text-4);font-weight:600;text-transform:capitalize">{{ s.role }}</span>
              </span>
            </label>
          </div>
          <p v-else style="font-size:12px;color:var(--text-4);margin:0">
            No CS assigned. <a :href="route('provider.stewards.index')" style="color:var(--gold-dark)">Add one →</a>
          </p>
        </div>
      </div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="$emit('update:modelValue', false)">Cancel</button>
      <button type="button" class="btn btn-primary"  :disabled="submitting" @click="submit">
        <span v-if="submitting" class="spinner spinner-sm" />
        <AegisIcon v-else name="check" :size="14" />
        {{ submitting ? 'Saving…' : 'Save Configuration' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed, watch, nextTick, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AegisToggle from '@/components/ui/AegisToggle.vue'
import { useConfirm } from '@/composables/useConfirm'

const props = defineProps({
  modelValue:   { type: Boolean, required: true },
  incidentType: { type: Object,  default: null },
  config:       { type: Object,  default: null },
  stewards:     { type: Array,   default: () => [] },
  tasks:        { type: Array,   default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'update-config'])
const { confirmAction } = useConfirm()

const docOptions = [
  { value: 'death_certificate',      label: 'Death Certificate' },
  { value: 'doctors_note',           label: "Doctor's Note / Medical Certificate" },
  { value: 'medical_documentation',  label: 'Medical Documentation' },
  { value: 'hospitalization_record', label: 'Hospitalization Record' },
  { value: 'leave_documentation',    label: 'Leave / Absence Documentation' },
  { value: 'police_report',          label: 'Police Report' },
  { value: 'legal_documentation',    label: 'Legal Documentation / Court Order' },
  { value: 'insurance_documentation',label: 'Insurance Documentation' },
  { value: 'government_id',          label: 'Government-Issued ID / Identification' },
  { value: 'power_of_attorney',      label: 'Power of Attorney' },
  { value: 'other',                  label: 'Other' },
]

const timelineOptions = [
  'Within 24 hrs','Within 48 hrs','Within 72 hrs',
  'Day 1','Day 1–2','Day 1–7','Week 1','Week 2',
  'Day 7–30','Day 30–60','Day 60–90','Ongoing',
]

const ssList = computed(() => props.stewards.filter(s => s.steward_type === 'support_steward'))
const csList = computed(() => props.stewards.filter(s => s.steward_type === 'continuity_steward'))

const activeTab = ref('ss')
const tabs = computed(() => [
  { key: 'ss',      label: 'SS Tasks',      icon: 'user-check', count: ssTasks.value.length },
  { key: 'cs',      label: 'CS Tasks',      icon: 'shield',     count: csTasks.value.length },
  { key: 'docs',    label: 'Documentation', icon: 'file-text',  count: selectedDocs.value.length },
  { key: 'stewards',label: 'Auth Stewards', icon: 'users',      count: authSsIds.value.length + authCsIds.value.length },
])

// Local editable state — synced from props when incidentType changes
const isActive     = ref(false)
const selectedDocs = ref([])
const authSsIds    = ref([])
const authCsIds    = ref([])
const ssTasks      = ref([])
const csTasks      = ref([])

const newSsTask = ref(''); const newSsTimeline = ref('')
const newCsTask = ref(''); const newCsTimeline = ref('')

let _keyCounter = 0
function withKey(t) { return { ...t, _key: t.id ?? ('new_' + ++_keyCounter) } }

function seedFromProps() {
  activeTab.value    = 'ss'
  isActive.value     = !!(props.config?.is_active)
  selectedDocs.value = (props.config?.docs_required ?? []).slice()
  authSsIds.value    = (props.config?.authorized_ss_ids ?? []).slice()
  authCsIds.value    = (props.config?.authorized_cs_ids ?? []).slice()
  ssTasks.value = props.tasks.filter(t => t.assigned_to === 'support_steward').map(withKey)
  csTasks.value = props.tasks.filter(t => t.assigned_to === 'continuity_steward').map(withKey)
  nextTick(() => { initDrag('ss'); initDrag('cs') })
}

// Re-seed whenever modal opens (modelValue false→true) or switches incident type
watch(() => props.modelValue, (open) => { if (open) seedFromProps() }, { immediate: true })
watch(() => props.incidentType?.value, () => { if (props.modelValue) seedFromProps() })

// Keep isActive in sync if parent localConfigs changes while modal is open
// (e.g. grid toggle fired while modal was already open)
watch(() => props.config?.is_active, (val) => {
  if (props.modelValue) isActive.value = !!(val)
})

// Immediately POST when enable toggle is flipped inside the modal
function handleActiveToggle(val) {
  // Guard: always-on types cannot be disabled
  if (!props.incidentType?.is_optin && !val) return
  isActive.value = val
  if (!props.incidentType) return
  // Optimistically notify parent so grid syncs immediately
  emit('update-config', { incident_type: props.incidentType.value, is_active: val })
  router.post(route('provider.plan.incident-config'), {
    incident_type:     props.incidentType.value,
    is_active:         val,
    is_optin:          props.incidentType.is_optin,
    docs_required:     selectedDocs.value,
    authorized_ss_ids: authSsIds.value,
    authorized_cs_ids: authCsIds.value,
  }, { preserveState: true, preserveScroll: true })
}

// ── Drag & Drop (vanilla, no extra deps) ─────────────────────────────
const ssTaskListEl = ref(null)
const csTaskListEl = ref(null)
let dragCleanups = []

function initDrag(role) {
  const el = role === 'ss' ? ssTaskListEl.value : csTaskListEl.value
  if (!el) return
  const list = role === 'ss' ? ssTasks : csTasks

  let dragging = null, startY = 0, startIdx = 0, placeholder = null

  function getItems() { return [...el.querySelectorAll('.draggable-item')] }

  function onMousedown(e) {
    const handle = e.target.closest('.drag-handle')
    if (!handle) return
    const item = handle.closest('.draggable-item')
    if (!item) return
    e.preventDefault()
    dragging = item
    startY = e.clientY
    startIdx = getItems().indexOf(item)
    item.style.opacity = '0.5'
    placeholder = document.createElement('div')
    placeholder.style.cssText = `height:${item.offsetHeight}px;border:2px dashed var(--gold-dark);border-radius:var(--radius-sm);margin-bottom:6px;background:var(--icon-bg-gold);`
    item.parentNode.insertBefore(placeholder, item.nextSibling)
    document.addEventListener('mousemove', onMousemove)
    document.addEventListener('mouseup', onMouseup)
  }

  function onMousemove(e) {
    if (!dragging) return
    const items = getItems().filter(i => i !== dragging)
    for (const target of items) {
      const rect = target.getBoundingClientRect()
      const mid  = rect.top + rect.height / 2
      if (e.clientY < mid) { el.insertBefore(placeholder, target); break }
      else if (target === items[items.length - 1]) { el.appendChild(placeholder) }
    }
  }

  function onMouseup() {
    if (!dragging) return
    const items = getItems()
    const newIdx = [...el.children].filter(c => c !== dragging).indexOf(placeholder)
    dragging.style.opacity = ''
    placeholder.remove()
    placeholder = null
    const arr = list.value.splice(startIdx, 1)[0]
    const insertAt = Math.max(0, Math.min(newIdx, list.value.length))
    list.value.splice(insertAt, 0, arr)
    dragging = null
    document.removeEventListener('mousemove', onMousemove)
    document.removeEventListener('mouseup', onMouseup)
  }

  el.addEventListener('mousedown', onMousedown)
  dragCleanups.push(() => el.removeEventListener('mousedown', onMousedown))
}

watch(activeTab, (tab) => {
  nextTick(() => {
    if (tab === 'ss') initDrag('ss')
    if (tab === 'cs') initDrag('cs')
  })
})

onUnmounted(() => { dragCleanups.forEach(fn => fn()); dragCleanups = [] })

function addTask(role) {
  const title    = role === 'ss' ? newSsTask.value.trim()    : newCsTask.value.trim()
  const timeline = role === 'ss' ? newSsTimeline.value       : newCsTimeline.value
  if (!title) return
  if (role === 'ss') { ssTasks.value.push(withKey({ title, timeline })); newSsTask.value = ''; newSsTimeline.value = '' }
  else               { csTasks.value.push(withKey({ title, timeline })); newCsTask.value = ''; newCsTimeline.value = '' }
  nextTick(() => { role === 'ss' ? initDrag('ss') : initDrag('cs') })
}

const submitting = ref(false)

function submit() {
  submitting.value = true

  const allNewSs = ssTasks.value.filter(t => !t.id).map((t, i) => ({ title: t.title, timeline: t.timeline, assigned_to: 'support_steward',    sort_order: i }))
  const allNewCs = csTasks.value.filter(t => !t.id).map((t, i) => ({ title: t.title, timeline: t.timeline, assigned_to: 'continuity_steward', sort_order: i }))
  const newTasks = [...allNewSs, ...allNewCs]

  // Step 1: save config
  // Optimistically notify parent so grid reflects change immediately
  emit('update-config', {
    incident_type:     props.incidentType?.value,
    is_active:         isActive.value,
    docs_required:     selectedDocs.value,
    authorized_ss_ids: authSsIds.value,
    authorized_cs_ids: authCsIds.value,
  })
  router.post(route('provider.plan.incident-config'), {
    incident_type:     props.incidentType?.value,
    is_active:         isActive.value,
    docs_required:     selectedDocs.value,
    authorized_ss_ids: authSsIds.value,
    authorized_cs_ids: authCsIds.value,
  }, { preserveState: true, preserveScroll: true, onFinish: () => {
    if (!newTasks.length) {
      submitting.value = false
      emit('update:modelValue', false)
      return
    }
    // Step 2: post new tasks sequentially
    let remaining = newTasks.length
    newTasks.forEach(t => {
      router.post(route('provider.plan.tasks.store'), {
        title:       t.title,
        timeline:    t.timeline,
        assigned_to: t.assigned_to,
        sort_order:  t.sort_order,
        is_custom:   1,
      }, { preserveState: true, preserveScroll: true, onFinish: () => {
        remaining--
        if (remaining === 0) {
          submitting.value = false
          emit('update:modelValue', false)
        }
      }})
    })
  }})
}
</script>

<style scoped>

.m-section-title { display:flex;align-items:center;gap:8px;font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--text-3);padding-bottom:6px;border-bottom:1px solid var(--border); }
.count-badge { background:var(--badge-bg-gold);color:var(--gold-dark);padding:2px 7px;font-size:10px;border-radius:var(--radius-full);font-weight:700; }
.task-list { display:flex;flex-direction:column;gap:6px; }
.task-item { display:flex;align-items:center;gap:10px;padding:9px 12px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-sm); }
.setting-row { display:flex;align-items:center;justify-content:space-between;gap:16px;padding:10px 0; }
.setting-info { flex:1; }
.setting-label { font-size:13px;font-weight:600;color:var(--text); }
.setting-desc { font-size:12px;color:var(--text-3);margin-top:2px; }
</style>
