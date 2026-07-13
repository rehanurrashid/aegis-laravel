<!--
  modals/IncidentConfigModal.vue — configure one incident type.
  Usage: <IncidentConfigModal v-model="showIncidentConfig" :incident-type="..." ... />
-->
<template>
  <AegisModal :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" size="xl" :title="`Configure: ${incidentType?.label ?? ''}`">

    <!-- Enable toggle -->
    <div class="m-section" style="margin-bottom:16px">
      <div class="m-section-title">Incident Settings</div>
      <div class="setting-row" style="margin-top:10px">
        <div class="setting-info">
          <div class="setting-label">Enable this incident type</div>
          <div class="setting-desc">
            <span v-if="incidentType?.is_optin">Opt-in — requires your explicit consent.</span>
            <span v-else>Always-on incident type.</span>
          </div>
        </div>
        <AegisToggle v-model="isActive" />
      </div>
    </div>

    <!-- Docs required -->
    <div class="m-section" style="margin-bottom:16px">
      <div class="m-section-title">Documentation Required</div>
      <div style="display:flex;flex-direction:column;gap:6px;margin-top:10px">
        <label v-for="doc in docOptions" :key="doc.value" class="form-check">
          <input type="checkbox" :value="doc.value" v-model="selectedDocs" />
          <span class="form-check-label">{{ doc.label }}</span>
        </label>
      </div>
    </div>

    <!-- Authorization matrix -->
    <div class="m-section" style="margin-bottom:16px">
      <div class="m-section-title">Authorized Stewards</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:10px">
        <div>
          <div style="font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--text-3);margin-bottom:6px">Support Stewards</div>
          <div v-if="ssList.length" style="display:flex;flex-direction:column;gap:5px">
            <label v-for="s in ssList" :key="s.steward_id" class="form-check">
              <input type="checkbox" :value="s.steward_id" v-model="authSsIds" />
              <span class="form-check-label" style="display:flex;align-items:center;gap:8px">
                <span style="width:18px;height:18px;border-radius:50%;background:var(--text-3);color:#fff;font-size:9px;font-weight:700;font-family:var(--font-serif);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">{{ s.avatar_initials }}</span>
                {{ s.display_name }}
                <span style="margin-left:auto;font-size:10px;color:var(--text-4);font-weight:600;text-transform:capitalize">{{ s.role }}</span>
              </span>
            </label>
          </div>
          <p v-else style="font-size:12px;color:var(--text-4);margin:0">
            No SS assigned. <a :href="route('provider.ss.index')" style="color:var(--gold-dark)">Add one →</a>
          </p>
        </div>
        <div>
          <div style="font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--text-3);margin-bottom:6px">Continuity Stewards</div>
          <div v-if="csList.length" style="display:flex;flex-direction:column;gap:5px">
            <label v-for="s in csList" :key="s.steward_id" class="form-check">
              <input type="checkbox" :value="s.steward_id" v-model="authCsIds" />
              <span class="form-check-label" style="display:flex;align-items:center;gap:8px">
                <span style="width:18px;height:18px;border-radius:50%;background:var(--gold-dark);color:#fff;font-size:9px;font-weight:700;font-family:var(--font-serif);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">{{ s.avatar_initials }}</span>
                {{ s.display_name }}
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

    <!-- SS Tasks -->
    <div class="m-section" style="margin-bottom:16px">
      <div class="m-section-title">
        Support Steward Tasks <span class="count-badge">{{ ssTasks.length }}</span>
      </div>
      <div class="task-list" style="margin-top:8px">
        <div v-for="(t, i) in ssTasks" :key="i" class="task-item">
          <AegisIcon name="menu" :size="13" style="color:var(--text-4);flex-shrink:0;cursor:grab" />
          <span style="flex:1;font-size:13px;color:var(--text);font-weight:500">{{ t.title }}</span>
          <span v-if="t.timeline" style="font-size:11px;color:var(--text-3);font-weight:600;padding:3px 8px;background:var(--surface-2);border-radius:var(--radius-xs);white-space:nowrap">{{ t.timeline }}</span>
          <button type="button" class="btn-icon" data-tooltip="Remove" @click="ssTasks.splice(i,1)">
            <AegisIcon name="trash" :size="13" />
          </button>
        </div>
        <div v-if="!ssTasks.length" style="font-size:12px;color:var(--text-4);padding:8px 0;font-style:italic">No SS tasks yet.</div>
      </div>
      <div style="display:flex;gap:8px;padding-top:8px;align-items:center">
        <input v-model="newSsTask" class="form-input" type="text" placeholder="Add SS task…" style="flex:1;min-width:0" @keydown.enter.prevent="addTask('ss')" />
        <select v-model="newSsTimeline" class="form-select" style="width:140px;flex-shrink:0">
          <option value="">Timeline…</option>
          <option v-for="tl in timelineOptions" :key="tl" :value="tl">{{ tl }}</option>
        </select>
        <button type="button" class="btn btn-outline" @click="addTask('ss')">Add</button>
      </div>
    </div>

    <!-- CS Tasks -->
    <div class="m-section">
      <div class="m-section-title">
        Continuity Steward Tasks <span class="count-badge">{{ csTasks.length }}</span>
      </div>
      <div class="task-list" style="margin-top:8px">
        <div v-for="(t, i) in csTasks" :key="i" class="task-item">
          <AegisIcon name="menu" :size="13" style="color:var(--text-4);flex-shrink:0;cursor:grab" />
          <span style="flex:1;font-size:13px;color:var(--text);font-weight:500">{{ t.title }}</span>
          <span v-if="t.timeline" style="font-size:11px;color:var(--text-3);font-weight:600;padding:3px 8px;background:var(--surface-2);border-radius:var(--radius-xs);white-space:nowrap">{{ t.timeline }}</span>
          <button type="button" class="btn-icon" data-tooltip="Remove" @click="csTasks.splice(i,1)">
            <AegisIcon name="trash" :size="13" />
          </button>
        </div>
        <div v-if="!csTasks.length" style="font-size:12px;color:var(--text-4);padding:8px 0;font-style:italic">No CS tasks yet.</div>
      </div>
      <div style="display:flex;gap:8px;padding-top:8px;align-items:center">
        <input v-model="newCsTask" class="form-input" type="text" placeholder="Add CS task…" style="flex:1;min-width:0" @keydown.enter.prevent="addTask('cs')" />
        <select v-model="newCsTimeline" class="form-select" style="width:140px;flex-shrink:0">
          <option value="">Timeline…</option>
          <option v-for="tl in timelineOptions" :key="tl" :value="tl">{{ tl }}</option>
        </select>
        <button type="button" class="btn btn-outline" @click="addTask('cs')">Add</button>
      </div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="$emit('update:modelValue', false)">Cancel</button>
      <button type="button" class="btn btn-primary btn-spin" :disabled="submitting" @click="submit">
        <AegisIcon v-if="submitting" name="refresh-cw" :size="14" class="spin" />
        <AegisIcon v-else name="check" :size="14" />
        {{ submitting ? 'Saving…' : 'Save Configuration' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AegisToggle from '@/components/ui/AegisToggle.vue'

const props = defineProps({
  modelValue:   { type: Boolean, required: true },
  incidentType: { type: Object,  default: null },
  config:       { type: Object,  default: null },
  stewards:     { type: Array,   default: () => [] },
  tasks:        { type: Array,   default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const docOptions = [
  { value: 'death_certificate',     label: 'Death Certificate' },
  { value: 'medical_documentation', label: 'Medical Documentation' },
  { value: 'police_report',         label: 'Police Report' },
  { value: 'legal_documentation',   label: 'Legal Documentation' },
  { value: 'other',                 label: 'Other' },
]

const timelineOptions = [
  'Within 24 hrs','Within 48 hrs','Within 72 hrs',
  'Day 1','Day 1–2','Day 1–7','Week 1','Week 2',
  'Day 7–30','Day 30–60','Day 60–90','Ongoing',
]

const ssList = computed(() => props.stewards.filter(s => s.steward_type === 'support_steward'))
const csList = computed(() => props.stewards.filter(s => s.steward_type === 'continuity_steward'))

// Local editable state — synced from props when incidentType changes
const isActive    = ref(false)
const selectedDocs = ref([])
const authSsIds   = ref([])
const authCsIds   = ref([])
const ssTasks     = ref([])
const csTasks     = ref([])

const newSsTask = ref(''); const newSsTimeline = ref('')
const newCsTask = ref(''); const newCsTimeline = ref('')

watch(() => [props.incidentType, props.config, props.tasks, props.modelValue], () => {
  if (!props.modelValue) return
  isActive.value     = !!(props.config?.is_active)
  selectedDocs.value = props.config?.docs_required ?? []
  authSsIds.value    = props.config?.authorized_ss_ids ?? []
  authCsIds.value    = props.config?.authorized_cs_ids ?? []
  ssTasks.value = props.tasks.filter(t => t.assigned_to === 'support_steward').map(t => ({ ...t }))
  csTasks.value = props.tasks.filter(t => t.assigned_to === 'continuity_steward').map(t => ({ ...t }))
}, { immediate: true })

function addTask(role) {
  const title    = role === 'ss' ? newSsTask.value.trim()    : newCsTask.value.trim()
  const timeline = role === 'ss' ? newSsTimeline.value       : newCsTimeline.value
  if (!title) return
  if (role === 'ss') { ssTasks.value.push({ title, timeline }); newSsTask.value = ''; newSsTimeline.value = '' }
  else               { csTasks.value.push({ title, timeline }); newCsTask.value = ''; newCsTimeline.value = '' }
}

const submitting = ref(false)

async function submit() {
  submitting.value = true

  // 1. Save incident config
  await new Promise(resolve => {
    router.post(route('provider.plan.incident-config'), {
      incident_type:     props.incidentType?.value,
      is_active:         isActive.value,
      docs_required:     selectedDocs.value,
      authorized_ss_ids: authSsIds.value,
      authorized_cs_ids: authCsIds.value,
    }, { preserveScroll: true, onFinish: resolve })
  })

  // 2. Post new tasks (ones without an id)
  const newTasks = [
    ...ssTasks.value.filter(t => !t.id).map((t, i) => ({ ...t, assigned_to: 'support_steward',    sort_order: i })),
    ...csTasks.value.filter(t => !t.id).map((t, i) => ({ ...t, assigned_to: 'continuity_steward', sort_order: i })),
  ]
  for (const t of newTasks) {
    await new Promise(resolve => {
      router.post(route('provider.plan.tasks.store'), {
        title:       t.title,
        timeline:    t.timeline,
        assigned_to: t.assigned_to,
        sort_order:  t.sort_order,
        is_custom:   1,
      }, { preserveScroll: true, onFinish: resolve })
    })
  }

  submitting.value = false
  emit('update:modelValue', false)
}
</script>

<style scoped>
.m-section-title { display:flex;align-items:center;gap:8px;font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--text-3);padding-bottom:6px;border-bottom:1px solid var(--border); }
.count-badge { background:var(--badge-bg-gold);color:var(--gold-dark);padding:2px 7px;font-size:10px;border-radius:var(--radius-full);font-weight:700; }
.task-list { display:flex;flex-direction:column;gap:6px; }
.task-item { display:flex;align-items:center;gap:10px;padding:9px 12px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-sm); }
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin .7s linear infinite; }
</style>
