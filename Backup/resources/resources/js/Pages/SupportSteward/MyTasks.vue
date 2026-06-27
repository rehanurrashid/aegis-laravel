<template>
  <AppLayout :user="user" portal="support_steward" activePage="my-tasks" pageTitle="My Tasks" :has-emergency="true">

    <!-- HEADER -->
    <div class="tk-header">
      <div>
        <div class="tk-eyebrow">Support Steward</div>
        <h1 class="tk-title">My Tasks</h1>
        <p class="tk-sub">{{ headerSub }}</p>
      </div>
      <div class="tk-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <button type="button" class="btn btn-primary btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Report Incident</button>
      </div>
    </div>

    <!-- STAT CARDS -->
    <div class="tk-stats">
      <div class="tk-stat"><div class="tk-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div><div class="tk-stat-num">4</div><div class="tk-stat-label">Open Tasks</div></div>
      <div class="tk-stat"><div class="tk-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div><div class="tk-stat-num">2</div><div class="tk-stat-label">In Progress</div></div>
      <div class="tk-stat"><div class="tk-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div><div class="tk-stat-num">0</div><div class="tk-stat-label">Blocked</div></div>
      <div class="tk-stat"><div class="tk-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div><div class="tk-stat-num">6</div><div class="tk-stat-label">Completed</div></div>
    </div>

    <!-- FILTERS -->
    <div class="tk-filters">
      <div class="tk-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" placeholder="Search tasks..." />
      </div>
      <select v-model="providerFilter" class="form-select tk-select"><option>All Providers</option><option>Dr. Michael Torres</option><option>Dr. Sarah Johnson</option></select>
      <select v-model="statusFilter" class="form-select tk-select"><option>All Statuses</option><option>Pending</option><option>In Progress</option><option>Completed</option></select>
      <select v-model="incidentFilter" class="form-select tk-select"><option>All Incidents</option><option>Short-Term Incapacitation</option><option>Natural Disaster</option></select>
    </div>

    <!-- TABS -->
    <div class="tk-tabs">
      <button v-for="t in tabs" :key="t.key" type="button" class="tk-tab" :class="{ active: tab === t.key }" @click="tab = t.key">
        {{ t.label }}
        <span class="tk-tab-count">{{ t.count }}</span>
      </button>
    </div>

    <!-- GROUPS -->
    <template v-for="section in sections" :key="section.label">
      <template v-if="section.groups.length">
        <div class="tk-section-label">{{ section.label }}</div>
        <div v-for="g in section.groups" :key="g.type + g.provider" class="tk-group">
          <div class="tk-group-head">
            <div class="tk-group-ico" :class="g.state === 'closed' ? 'ico-warn' : ''"><svg v-if="g.state === 'closed'" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div class="tk-group-info">
              <div class="tk-group-type">{{ g.type }}</div>
              <div class="tk-group-meta">{{ g.provider }} · <span :class="g.state === 'closed' ? 'closed' : 'active'">{{ g.state === 'closed' ? 'Incident closed' : 'Active incident' }}</span></div>
            </div>
            <span class="tk-group-count" :class="g.state === 'closed' ? 'cnt-neutral' : 'cnt-red'">{{ g.tasks.length }} {{ g.tasks.length === 1 ? 'Task' : 'Tasks' }}</span>
          </div>

          <div v-for="(task, ti) in g.tasks" :key="ti" class="tk-task" :class="{ done: task.status === 'completed' }" @click="openTask(task, g)">
            <div class="tk-task-ico"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div class="tk-task-info">
              <div class="tk-task-title" :class="{ done: task.status === 'completed' }">{{ task.title }}</div>
              <div class="tk-task-meta">{{ task.provider }}<template v-if="task.completed"> · Completed {{ task.completed }}</template></div>
            </div>
            <span class="tk-badge" :class="'b-' + task.status">{{ statusLabel(task.status) }}</span>
            <div class="tk-task-actions" @click.stop>
              <template v-if="task.status === 'completed'">
                <button type="button" class="btn-icon" @click="openTask(task, g)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
              </template>
              <template v-else>
                <button type="button" class="btn-icon" data-tip="Complete" @click="openComplete(task, g)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></button>
                <button type="button" class="btn-icon" data-tip="Details" @click="openTask(task, g)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                <button type="button" class="btn-icon" data-tip="Notify CS" @click="openNotify(task, g)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></button>
                <button type="button" class="btn-icon" data-tip="Request Extension" @click="openExtension(task, g)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></button>
              </template>
            </div>
          </div>
        </div>
      </template>
    </template>

    <!-- TASK DETAIL MODAL -->
    <Modal v-model="showTask" :title="selected ? selected.title : ''" :subtitle="selected ? selected.provider + ' · ' + selected.incidentType : ''">
      <template v-if="selected">
        <div class="tk-detail">
          <div class="tk-detail-row">
            <div><div class="tk-detail-label">Provider</div><div class="tk-detail-val">{{ selected.provider }}</div></div>
            <div><div class="tk-detail-label">Incident</div><div class="tk-detail-val">{{ selected.incidentType }}</div></div>
          </div>
          <div class="tk-detail-status"><div class="tk-detail-label">Status</div><span class="tk-badge" :class="'b-' + selected.status">{{ statusLabel(selected.status) }}</span></div>
        </div>
      </template>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showTask = false">Close</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showTask = false"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Mark Complete</button>
      </template>
    </Modal>

    <!-- NOTIFY CONTINUITY STEWARD MODAL -->
    <Modal v-model="showNotify" title="Notify Continuity Steward" :subtitle="selected ? selected.title : ''">
      <div class="tk-callout gold">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <div><strong>Let your Continuity Steward know</strong><span>Use this to raise visibility when you need additional support — your Continuity Steward will be notified and can follow up directly.</span></div>
      </div>
      <div class="tk-field">
        <label class="tk-field-label">Reason <span class="tk-req">*</span></label>
        <textarea v-model="notifyReason" class="tk-textarea" rows="4" placeholder="Briefly describe why you cannot complete this task and what support you need..."></textarea>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showNotify = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showNotify = false"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Send Notification</button>
      </template>
    </Modal>

    <!-- REQUEST EXTENSION MODAL -->
    <Modal v-model="showExtension" title="Request Extension" :subtitle="selected ? selected.title : ''">
      <div class="tk-field">
        <label class="tk-field-label">Requested completion date <span class="tk-req">*</span></label>
        <input v-model="extDate" type="date" class="tk-input" />
      </div>
      <div class="tk-field">
        <label class="tk-field-label">Reason for extension <span class="tk-req">*</span></label>
        <textarea v-model="extReason" class="tk-textarea" rows="4" placeholder="Briefly explain why more time is needed..."></textarea>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showExtension = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showExtension = false"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Submit Request</button>
      </template>
    </Modal>

    <!-- MARK COMPLETE MODAL -->
    <Modal v-model="showComplete" title="Mark Task Complete" :subtitle="selected ? selected.title : ''">
      <div class="tk-callout green">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <div><strong>Ready to mark this complete?</strong><span>Completing this task notifies your Continuity Steward and is logged to your activity record.</span></div>
      </div>
      <div class="tk-field">
        <label class="tk-field-label">Completion note <span class="tk-opt">(optional)</span></label>
        <textarea v-model="completeNote" class="tk-textarea" rows="3" placeholder="Any notes about how this was completed..."></textarea>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showComplete = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showComplete = false"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Mark Complete</button>
      </template>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
import Modal from '../../Components/Modal.vue';
defineProps({ user: Object });

const search = ref('');
const providerFilter = ref('All Providers');
const statusFilter = ref('All Statuses');
const incidentFilter = ref('All Incidents');
const tab = ref('all');

const allGroups = [
  {
    type: 'Short-Term Incapacitation', provider: 'Dr. Michael Torres', state: 'active', past: false,
    tasks: [
      { title: 'Confirm practitioner status and estimated return date', provider: 'Dr. Michael Torres', status: 'completed', completed: 'May 12, 2026', list: 'planning' },
      { title: 'Notify Continuity Steward with situation report', provider: 'Dr. Michael Torres', status: 'completed', completed: 'May 12, 2026', list: 'support' },
      { title: 'Verify current contact information for emergency roster', provider: 'Dr. Michael Torres', status: 'in_progress', list: 'planning' },
      { title: 'Acknowledge and review active continuity plan for Dr. Torres', provider: 'Dr. Michael Torres', status: 'pending', list: 'support' },
    ],
  },
  {
    type: 'Short-Term Incapacitation', provider: 'Dr. Sarah Johnson', state: 'active', past: false,
    tasks: [
      { title: 'Confirm medical situation with doctor or hospital', provider: 'Dr. Sarah Johnson', status: 'completed', completed: 'Apr 25, 2026', list: 'planning' },
      { title: 'Notify Continuity Steward with estimated duration', provider: 'Dr. Sarah Johnson', status: 'completed', completed: 'Apr 25, 2026', list: 'support' },
      { title: 'Document all contact attempts and timeline for incident record', provider: 'Dr. Sarah Johnson', status: 'in_progress', list: 'support' },
      { title: 'Complete post-incident follow-up debrief acknowledgment', provider: 'Dr. Sarah Johnson', status: 'pending', list: 'executor' },
    ],
  },
  {
    type: 'Natural Disaster', provider: 'Dr. Sarah Johnson', state: 'closed', past: true,
    tasks: [
      { title: 'Confirm Practitioner safety', provider: 'Dr. Sarah Johnson', status: 'completed', completed: 'Nov 8, 2025', list: 'planning' },
      { title: 'Assess impact on practice facility and records', provider: 'Dr. Sarah Johnson', status: 'completed', completed: 'Nov 9, 2025', list: 'support' },
    ],
  },
];

const tabs = computed(() => {
  const count = (list) => allGroups.reduce((n, g) => n + g.tasks.filter(t => list === 'all' || t.list === list).length, 0);
  return [
    { key: 'all', label: 'All Tasks', count: count('all') },
    { key: 'planning', label: 'Planning for Critical Moments Task List', count: count('planning') },
    { key: 'support', label: 'Support Steward Critical Moment Task List', count: count('support') },
    { key: 'executor', label: 'Executor Critical Moment Task List', count: count('executor') },
  ];
});

const headerSub = computed(() => {
  const map = { all: '4 open tasks across your providers', planning: 'Planning for Critical Moments Task List', support: 'Support Steward Critical Moment Task List', executor: 'Executor Critical Moment Task List' };
  return map[tab.value];
});

function taskMatches(t) {
  if (tab.value !== 'all' && t.list !== tab.value) return false;
  if (providerFilter.value !== 'All Providers' && t.provider !== providerFilter.value) return false;
  if (statusFilter.value !== 'All Statuses' && statusLabel(t.status) !== statusFilter.value) return false;
  if (search.value.trim() && !t.title.toLowerCase().includes(search.value.toLowerCase())) return false;
  return true;
}

const sections = computed(() => {
  const build = (past) => allGroups
    .filter(g => g.past === past)
    .map(g => ({ ...g, tasks: g.tasks.filter(taskMatches) }))
    .filter(g => {
      if (incidentFilter.value !== 'All Incidents' && g.type !== incidentFilter.value) return false;
      return g.tasks.length > 0;
    });
  return [
    { label: 'Active Incidents', groups: build(false) },
    { label: 'Past Incidents', groups: build(true) },
  ];
});

function statusLabel(s) { return s === 'in_progress' ? 'In Progress' : s === 'completed' ? 'Completed' : 'Pending'; }

const showTask = ref(false);
const showNotify = ref(false);
const showExtension = ref(false);
const showComplete = ref(false);
const selected = ref(null);
const notifyReason = ref('');
const extDate = ref('');
const extReason = ref('');
const completeNote = ref('');

function pick(task, group) { selected.value = { ...task, incidentType: group.type }; }
function openTask(task, group) { pick(task, group); showTask.value = true; }
function openNotify(task, group) { pick(task, group); notifyReason.value = ''; showNotify.value = true; }
function openExtension(task, group) { pick(task, group); extDate.value = ''; extReason.value = ''; showExtension.value = true; }
function openComplete(task, group) { pick(task, group); completeNote.value = ''; showComplete.value = true; }
</script>

<style scoped>
/* ── SS MY TASKS ── */
.tk-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:24px 30px; margin-bottom:18px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.tk-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.tk-title { font-family:var(--font-serif); font-size:30px; font-weight:600; letter-spacing:-0.4px; color:var(--text); margin:0; line-height:1.05; }
.tk-sub { font-size:13.5px; color:var(--text-3); margin-top:8px; }
.tk-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; }

.tk-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.tk-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 18px; box-shadow:var(--shadow-xs); display:grid; grid-template-columns:auto 1fr; grid-template-rows:auto auto; column-gap:12px; align-items:center; }
.tk-stat-ico { grid-row:1 / 3; width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.tk-stat-num { font-family:var(--font-serif); font-size:24px; font-weight:700; line-height:1; color:var(--text); }
.tk-stat-label { font-size:11.5px; color:var(--text-3); margin-top:3px; }

.tk-filters { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
.tk-search { position:relative; flex:1; min-width:200px; }
.tk-search svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.tk-search input { width:100%; padding:9px 13px 9px 36px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; }
.tk-search input:focus { border-color:var(--gold-dark); }
.tk-search input::placeholder { color:var(--text-4); }
.tk-select { width:auto; min-width:140px; }

.tk-tabs { display:flex; align-items:center; gap:2px; padding:4px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-full); margin-bottom:18px; flex-wrap:wrap; }
.tk-tab { display:inline-flex; align-items:center; gap:8px; padding:8px 16px; font-size:12.5px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.tk-tab:hover { color:var(--text); }
.tk-tab.active { background:var(--gold-dark); color:var(--text-inverted); }
.tk-tab-count { font-size:10px; font-weight:700; min-width:16px; height:16px; padding:0 5px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); display:inline-flex; align-items:center; justify-content:center; }
.tk-tab.active .tk-tab-count { background:rgba(255,255,255,.25); color:var(--text-inverted); }

.tk-section-label { font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--text-4); margin:18px 0 12px; }

.tk-group { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; margin-bottom:16px; }
.tk-group-head { display:flex; align-items:center; gap:12px; padding:15px 20px; border-bottom:1px solid var(--border); }
.tk-group-ico { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.tk-group-ico.ico-warn { background:rgba(160,45,34,.08); color:var(--red); }
.tk-group-info { flex:1; min-width:0; }
.tk-group-type { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.tk-group-meta { font-size:12px; color:var(--text-4); margin-top:1px; }
.tk-group-meta .active { color:var(--red); font-weight:600; }
.tk-group-meta .closed { color:var(--text-3); font-weight:600; }
.tk-group-count { font-size:10px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:4px 10px; border-radius:var(--radius-full); flex-shrink:0; }
.tk-group-count.cnt-red { background:var(--red-light); color:var(--red); }
.tk-group-count.cnt-neutral { background:var(--surface-3); color:var(--text-3); }

.tk-task { display:flex; align-items:center; gap:12px; padding:14px 20px; border-bottom:1px solid var(--border); cursor:pointer; transition:background .15s ease; }
.tk-task:last-child { border-bottom:none; }
.tk-task:hover { background:var(--surface-2); }
.tk-task.done { background:var(--surface-2); }
.tk-task-ico { width:30px; height:30px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.tk-task.done .tk-task-ico { background:var(--surface-3); color:var(--text-4); }
.tk-task-info { flex:1; min-width:0; }
.tk-task-title { font-size:13px; font-weight:600; color:var(--text); }
.tk-task-title.done { color:var(--text-4); text-decoration:line-through; font-weight:500; }
.tk-task-meta { font-size:11.5px; color:var(--text-4); margin-top:2px; }
.tk-badge { font-size:9.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:3px 10px; border-radius:var(--radius-full); flex-shrink:0; }
.tk-badge.b-completed { background:var(--green-light); color:var(--green-dark); }
.tk-badge.b-in_progress { background:rgba(80,120,190,.12); color:#4a6ba8; }
.tk-badge.b-pending { background:var(--badge-bg-gold); color:var(--gold-dark); }
.tk-task-actions { display:flex; gap:6px; flex-shrink:0; }

/* modal detail */
.tk-detail { display:flex; flex-direction:column; }
.tk-detail-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; padding-bottom:14px; border-bottom:1px solid var(--border); margin-bottom:14px; }
.tk-detail-label { font-size:9.5px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); margin-bottom:5px; }
.tk-detail-val { font-size:13.5px; font-weight:600; color:var(--text); }
.tk-detail-status .tk-detail-label { margin-bottom:7px; }

/* action modals */
.tk-callout { display:flex; align-items:flex-start; gap:11px; padding:14px 16px; border-radius:var(--radius); margin-bottom:18px; }
.tk-callout.gold { background:var(--badge-bg-gold); border:1px solid rgba(192,154,82,.25); }
.tk-callout.green { background:var(--green-light); border:1px solid rgba(46,138,87,.25); }
.tk-callout svg { flex-shrink:0; margin-top:2px; }
.tk-callout.gold svg { color:var(--gold-dark); }
.tk-callout.green svg { color:var(--green-dark); }
.tk-callout strong { display:block; font-size:13px; font-weight:700; color:var(--text); margin-bottom:3px; }
.tk-callout.gold strong { color:var(--gold-dark); }
.tk-callout.green strong { color:var(--green-dark); }
.tk-callout span { font-size:12.5px; color:var(--text-2); line-height:1.55; }
.tk-field { margin-bottom:16px; }
.tk-field:last-child { margin-bottom:0; }
.tk-field-label { display:block; font-size:12px; font-weight:600; color:var(--text-2); margin-bottom:7px; }
.tk-req { color:var(--red); }
.tk-opt { font-weight:400; color:var(--text-4); }
.tk-input { display:block; width:100%; padding:11px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; }
.tk-input:focus { border-color:var(--gold-dark); }
.tk-textarea { display:block; width:100%; padding:11px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; resize:vertical; min-height:90px; line-height:1.55; font-family:var(--font-sans); }
.tk-textarea:focus { border-color:var(--gold-dark); }
.tk-textarea::placeholder, .tk-input::placeholder { color:var(--text-4); }

@media (max-width:900px) { .tk-stats { grid-template-columns:1fr 1fr; } .tk-header { flex-direction:column; } }
</style>
