<!--
  modals/IncidentConfigModal.vue
  Configure one incident type: is_active toggle, docs_required, authorization
  matrix, plus task management (add/remove/reorder) via AddPlanTaskModal.
  Props: incidentType {value, label, is_optin}, config {}, stewards []
-->
<template>
  <AegisModal modal-id="incidentConfigModal" size="xl" :title="`Configure: ${incidentType?.label ?? ''}`" subtitle="Set documentation requirements, authorized stewards, and task lists.">
    <template #body>
      <!-- Enable toggle -->
      <div class="m-section">
        <div class="m-section-title">Incident Settings</div>
        <div class="setting-row">
          <div class="setting-info">
            <div class="setting-label">Enable this incident type</div>
            <div class="setting-desc">
              <span v-if="incidentType?.is_optin">Opt-in — requires your explicit consent. Enable only if relevant to your practice.</span>
              <span v-else>Always-on incident type. Disabling removes it from your active plan.</span>
            </div>
          </div>
          <AegisToggle v-model="form.is_active" />
        </div>
      </div>

      <!-- Docs required -->
      <div class="m-section">
        <div class="m-section-title">Documentation Required</div>
        <div style="display:flex;flex-direction:column;gap:6px">
          <label v-for="doc in docOptions" :key="doc.value" class="form-check">
            <input type="checkbox" :value="doc.value" v-model="form.docs_required" />
            <span class="form-check-label">{{ doc.label }}</span>
          </label>
        </div>
      </div>

      <!-- Authorization matrix -->
      <div class="m-section">
        <div class="m-section-title">Authorized Stewards</div>
        <div class="col-2">
          <div>
            <div class="col-sub-label" style="font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--text-3);margin-bottom:6px">Support Stewards</div>
            <div v-if="ssList.length" style="display:flex;flex-direction:column;gap:5px">
              <label v-for="s in ssList" :key="s.steward_id" class="form-check">
                <input type="checkbox" :value="s.steward_id" v-model="form.authorized_ss_ids" />
                <span class="form-check-label" style="display:flex;align-items:center;gap:8px">
                  <span class="avatar avatar-xs" style="background:var(--text-3);color:#fff;font-size:9px;font-family:var(--font-serif);font-weight:600;width:18px;height:18px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">{{ s.avatar_initials }}</span>
                  {{ s.display_name }}
                  <span style="margin-left:auto;font-size:10px;color:var(--text-4);font-weight:600;text-transform:capitalize">{{ s.role }}</span>
                </span>
              </label>
            </div>
            <p v-else style="font-size:12px;color:var(--text-4);margin:0">
              No SS assigned. <a :href="route('ss.index')" style="color:var(--gold-dark)">Add one →</a>
            </p>
          </div>
          <div>
            <div class="col-sub-label" style="font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--text-3);margin-bottom:6px">Continuity Stewards</div>
            <div v-if="csList.length" style="display:flex;flex-direction:column;gap:5px">
              <label v-for="s in csList" :key="s.steward_id" class="form-check">
                <input type="checkbox" :value="s.steward_id" v-model="form.authorized_cs_ids" />
                <span class="form-check-label" style="display:flex;align-items:center;gap:8px">
                  <span class="avatar avatar-xs" style="background:var(--gold-dark);color:#fff;font-size:9px;font-family:var(--font-serif);font-weight:600;width:18px;height:18px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">{{ s.avatar_initials }}</span>
                  {{ s.display_name }}
                  <span style="margin-left:auto;font-size:10px;color:var(--text-4);font-weight:600;text-transform:capitalize">{{ s.role }}</span>
                </span>
              </label>
            </div>
            <p v-else style="font-size:12px;color:var(--text-4);margin:0">
              No CS assigned. <a :href="route('stewards.index')" style="color:var(--gold-dark)">Add one →</a>
            </p>
          </div>
        </div>
      </div>

      <!-- Task lists — SS -->
      <div class="m-section">
        <div class="m-section-title">
          <span>Support Steward Tasks <span class="count-badge">{{ ssTasks.length }}</span></span>
        </div>
        <div class="task-list">
          <div v-for="(t, i) in ssTasks" :key="t.id ?? i" class="task-item">
            <span class="task-grip" style="color:var(--text-4);flex-shrink:0;cursor:grab">
              <AegisIcon name="grip-vertical" :size="13" />
            </span>
            <span class="task-title">{{ t.title }}</span>
            <span v-if="t.timeline" class="task-time">{{ t.timeline }}</span>
            <button type="button" class="task-trash" data-tooltip="Remove task" @click="removeTask('ss', i)">
              <AegisIcon name="trash" :size="13" />
            </button>
          </div>
          <div v-if="!ssTasks.length" style="font-size:12px;color:var(--text-4);padding:8px 0;font-style:italic">No SS tasks yet.</div>
        </div>
        <div class="task-add" style="display:flex;gap:8px;padding-top:8px;align-items:center">
          <input v-model="newSsTask" class="form-input" type="text" placeholder="Add SS task…" style="flex:1;min-width:0" @keydown.enter.prevent="quickAddTask('ss')" />
          <select v-model="newSsTimeline" class="form-select" style="width:140px;flex-shrink:0">
            <option value="">Timeline…</option>
            <option v-for="tl in timelineOptions" :key="tl" :value="tl">{{ tl }}</option>
          </select>
          <button type="button" class="btn btn-outline" style="white-space:nowrap" @click="quickAddTask('ss')">Add</button>
        </div>
      </div>

      <!-- Task lists — CS -->
      <div class="m-section">
        <div class="m-section-title">
          <span>Continuity Steward Tasks <span class="count-badge">{{ csTasks.length }}</span></span>
        </div>
        <div class="task-list">
          <div v-for="(t, i) in csTasks" :key="t.id ?? i" class="task-item">
            <span class="task-grip" style="color:var(--text-4);flex-shrink:0;cursor:grab">
              <AegisIcon name="grip-vertical" :size="13" />
            </span>
            <span class="task-title">{{ t.title }}</span>
            <span v-if="t.timeline" class="task-time">{{ t.timeline }}</span>
            <button type="button" class="task-trash" data-tooltip="Remove task" @click="removeTask('cs', i)">
              <AegisIcon name="trash" :size="13" />
            </button>
          </div>
          <div v-if="!csTasks.length" style="font-size:12px;color:var(--text-4);padding:8px 0;font-style:italic">No CS tasks yet.</div>
        </div>
        <div class="task-add" style="display:flex;gap:8px;padding-top:8px;align-items:center">
          <input v-model="newCsTask" class="form-input" type="text" placeholder="Add CS task…" style="flex:1;min-width:0" @keydown.enter.prevent="quickAddTask('cs')" />
          <select v-model="newCsTimeline" class="form-select" style="width:140px;flex-shrink:0">
            <option value="">Timeline…</option>
            <option v-for="tl in timelineOptions" :key="tl" :value="tl">{{ tl }}</option>
          </select>
          <button type="button" class="btn btn-outline" style="white-space:nowrap" @click="quickAddTask('cs')">Add</button>
        </div>
      </div>
    </template>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="$emit('close')">Cancel</button>
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
import { useForm, router } from '@inertiajs/vue3'
import AegisToggle from '@/components/ui/AegisToggle.vue'

const props = defineProps({
  incidentType: { type: Object, default: null },     // {value, label, is_optin}
  config:       { type: Object, default: null },     // current PlanIncidentConfig row
  stewards:     { type: Array,  default: () => [] }, // all plan stewards
  tasks:        { type: Array,  default: () => [] }, // all plan tasks
  planId:       { type: String, default: null },
})

const emit = defineEmits(['close'])

const docOptions = [
  { value: 'death_certificate',   label: 'Death Certificate' },
  { value: 'medical_documentation', label: 'Medical Documentation' },
  { value: 'police_report',       label: 'Police Report' },
  { value: 'legal_documentation', label: 'Legal Documentation' },
  { value: 'other',               label: 'Other' },
]

const timelineOptions = [
  'Within 24 hrs', 'Within 48 hrs', 'Within 72 hrs',
  'Day 1', 'Day 1–2', 'Day 1–7', 'Week 1', 'Week 2',
  'Day 7–30', 'Day 30–60', 'Day 60–90', 'Ongoing',
]

const ssList = computed(() => props.stewards.filter(s => s.steward_type === 'support_steward'))
const csList = computed(() => props.stewards.filter(s => s.steward_type === 'continuity_steward'))

// Local task lists (editable in modal, submitted separately per task on save)
const ssTasks = ref([])
const csTasks = ref([])

const newSsTask     = ref('')
const newSsTimeline = ref('')
const newCsTask     = ref('')
const newCsTimeline = ref('')

watch(() => [props.incidentType, props.tasks], () => {
  if (!props.incidentType) return
  const typeVal = props.incidentType.value
  ssTasks.value = props.tasks
    .filter(t => t.incident_type === typeVal && t.assigned_to === 'support_steward')
    .map(t => ({ ...t }))
  csTasks.value = props.tasks
    .filter(t => t.incident_type === typeVal && t.assigned_to === 'continuity_steward')
    .map(t => ({ ...t }))
}, { immediate: true })

const form = useForm({
  incident_type:      computed(() => props.incidentType?.value ?? ''),
  is_active:         !!(props.config?.is_active),
  docs_required:     props.config?.docs_required ?? [],
  authorized_ss_ids: props.config?.authorized_ss_ids ?? [],
  authorized_cs_ids: props.config?.authorized_cs_ids ?? [],
})

// Re-sync form when config changes (incident switches)
watch(() => props.config, (c) => {
  form.is_active          = !!(c?.is_active)
  form.docs_required      = c?.docs_required ?? []
  form.authorized_ss_ids  = c?.authorized_ss_ids ?? []
  form.authorized_cs_ids  = c?.authorized_cs_ids ?? []
}, { immediate: true })

const submitting = ref(false)

function quickAddTask(role) {
  const title    = role === 'ss' ? newSsTask.value.trim()    : newCsTask.value.trim()
  const timeline = role === 'ss' ? newSsTimeline.value       : newCsTimeline.value
  if (!title) return
  const list = role === 'ss' ? ssTasks : csTasks
  list.value.push({ title, timeline, assigned_to: role === 'ss' ? 'support_steward' : 'continuity_steward', incident_type: props.incidentType?.value })
  if (role === 'ss') { newSsTask.value = ''; newSsTimeline.value = '' }
  else               { newCsTask.value = ''; newCsTimeline.value = '' }
}

function removeTask(role, idx) {
  if (role === 'ss') ssTasks.value.splice(idx, 1)
  else               csTasks.value.splice(idx, 1)
}

async function submit() {
  submitting.value = true

  // 1) Save incident config (is_active, docs, authorizations)
  await new Promise((resolve) => {
    router.post(route('plan.incident-config'), {
      incident_type:      props.incidentType?.value,
      is_active:          form.is_active,
      docs_required:      form.docs_required,
      authorized_ss_ids:  form.authorized_ss_ids,
      authorized_cs_ids:  form.authorized_cs_ids,
    }, { preserveScroll: true, onFinish: resolve })
  })

  // 2) Remove old tasks for this type, then add new ones (brute-force replace via individual POSTs)
  const allNew = [
    ...ssTasks.value.map((t, i) => ({ ...t, assigned_to: 'support_steward',    incident_type: props.incidentType?.value, sort_order: i })),
    ...csTasks.value.map((t, i) => ({ ...t, assigned_to: 'continuity_steward', incident_type: props.incidentType?.value, sort_order: i })),
  ]
  // Only post tasks that don't already have a server ID (new ones)
  const newTasks = allNew.filter(t => !t.id)
  for (const t of newTasks) {
    await new Promise((resolve) => {
      router.post(route('plan.tasks.store'), {
        title:         t.title,
        timeline:      t.timeline,
        incident_type: t.incident_type,
        assigned_to:   t.assigned_to,
        sort_order:    t.sort_order,
        is_custom:     1,
      }, { preserveScroll: true, onFinish: resolve })
    })
  }

  submitting.value = false
  emit('close')
}
</script>

<style scoped>
.m-section { display: flex; flex-direction: column; gap: 12px; }
.m-section-title {
  display: flex; align-items: center; justify-content: space-between;
  font-size: 10px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase;
  color: var(--text-3); padding-bottom: 6px; border-bottom: 1px solid var(--border);
}
.count-badge { background: var(--badge-bg-gold); color: var(--gold-dark); padding: 2px 7px; font-size: 10px; border-radius: var(--radius-full); font-weight: 700; }
.col-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.task-list { display: flex; flex-direction: column; gap: 6px; }
.task-item { display: flex; align-items: center; gap: 10px; padding: 9px 12px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); }
.task-item:hover .task-trash { opacity: 1; }
.task-title { flex: 1; font-size: 13px; color: var(--text); font-weight: 500; }
.task-time  { font-size: 11px; color: var(--text-3); font-weight: 600; padding: 3px 8px; background: var(--surface-2); border-radius: var(--radius-xs); white-space: nowrap; }
.task-trash { width: 26px; height: 26px; border-radius: var(--radius-xs); color: var(--text-4); display: inline-flex; align-items: center; justify-content: center; opacity: 0; transition: opacity .15s, background .15s, color .15s; background: none; border: none; cursor: pointer; flex-shrink: 0; }
.task-trash:hover { background: var(--red-light); color: var(--red-dark); opacity: 1; }
.task-grip { display: flex; align-items: center; flex-shrink: 0; }
</style>
