<template>
  <AppLayout :user="user" portal="support_steward" activePage="important-documents" pageTitle="Important Documents" :has-emergency="true">

    <!-- HEADER -->
    <div class="id-header">
      <div>
        <div class="id-eyebrow">Support Steward</div>
        <h1 class="id-title">Important Documents</h1>
        <p class="id-sub">{{ pendingCount }} plans need your awareness review</p>
      </div>
      <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
    </div>

    <!-- STAT CARDS -->
    <div class="id-stats">
      <div class="id-stat"><div class="id-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div class="id-stat-num">{{ plans.length }}</div><div class="id-stat-label">Plans Visible</div></div>
      <div class="id-stat"><div class="id-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="id-stat-num">0</div><div class="id-stat-label">Awareness Confirmed</div></div>
      <div class="id-stat"><div class="id-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div class="id-stat-num">{{ pendingCount }}</div><div class="id-stat-label">Awareness Pending</div></div>
      <div class="id-stat"><div class="id-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div><div class="id-stat-num">3</div><div class="id-stat-label">Reference Docs</div></div>
    </div>

    <!-- MAIN TABS -->
    <div class="id-tabs">
      <button type="button" class="id-tab" :class="{ active: mainTab === 'library' }" @click="mainTab = 'library'"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Plan Library <span class="id-tab-count">{{ plans.length }}</span></button>
      <button type="button" class="id-tab" :class="{ active: mainTab === 'reference' }" @click="mainTab = 'reference'"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Reference Documents</button>
    </div>

    <!-- ════ PLAN LIBRARY ════ -->
    <template v-if="mainTab === 'library'">
      <div class="id-filters">
        <div class="id-search">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input v-model="search" placeholder="Search plans or practitioners..." />
        </div>
        <select v-model="practFilter" class="form-select id-select"><option>All Practitioners</option><option>Dr. Rachel Okafor</option><option>Dr. Michael Torres</option><option>Dr. Sarah Johnson</option></select>
        <select v-model="statusFilter" class="form-select id-select"><option>All Statuses</option><option>Acknowledged</option><option>Awareness Pending</option></select>
        <select v-model="sortBy" class="form-select id-select"><option>Most Recent</option><option>Oldest</option><option>Name</option></select>
      </div>

      <div class="id-subtabs">
        <button v-for="s in subTabs" :key="s.key" type="button" class="id-subtab" :class="{ active: subTab === s.key }" @click="subTab = s.key">{{ s.label }}<span v-if="s.count" class="id-subtab-count">{{ s.count }}</span></button>
      </div>

      <div v-for="p in visiblePlans" :key="p.name" class="id-plan-card">
        <div class="id-plan-top">
          <div class="id-plan-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
          <div class="id-plan-info">
            <div class="id-plan-name">{{ p.name }}</div>
            <div class="id-plan-meta">{{ p.specialty }} · {{ p.version }} · Signed {{ p.signed }}</div>
            <div class="id-plan-chips">
              <span class="id-chip neutral"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Awareness Pending</span>
              <span class="id-chip blue">{{ p.role }}</span>
            </div>
          </div>
        </div>
        <div class="id-plan-banner">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          <span>You haven't confirmed awareness of this plan yet. Reading it ensures you're ready to respond when this practitioner needs your support.</span>
        </div>
        <div class="id-plan-foot">
          <button type="button" class="btn btn-gold btn-sm" @click="openAck(p)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Acknowledge Awareness</button>
          <button type="button" class="btn-icon" data-tip="View"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
          <button type="button" class="btn-icon" data-tip="Download" @click="triggerDownload"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></button>
          <button type="button" class="btn-icon" data-tip="Acknowledgment History" @click="showHistory = true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></button>
        </div>
      </div>
    </template>

    <!-- ════ REFERENCE DOCUMENTS ════ -->
    <template v-else>
      <div class="id-info-banner">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <span>These documents are maintained by Aegis and available to all Support Stewards as standard reference materials. They are read-only.</span>
      </div>
      <div class="id-ref-grid">
        <div v-for="doc in referenceDocs" :key="doc.title" class="id-ref-card">
          <div class="id-ref-head"><div class="id-ref-ico" v-html="doc.icon"></div><span class="id-ref-tag">{{ doc.tag }}</span></div>
          <div class="id-ref-title">{{ doc.title }}</div>
          <div class="id-ref-desc">{{ doc.desc }}</div>
          <div class="id-ref-foot">
            <div class="id-ref-actions">
              <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
              <button type="button" class="btn-icon" @click="triggerDownload"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></button>
            </div>
            <span class="id-ref-ver">{{ doc.version }}</span>
          </div>
        </div>
      </div>
    </template>

    <!-- ACKNOWLEDGE AWARENESS MODAL -->
    <Modal v-model="showAck" title="Acknowledge Awareness" :subtitle="ackPlan ? 'Continuity Plan · ' + ackPlan.name + ' · ' + ackPlan.version : ''">
      <div class="id-callout">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <span>By confirming awareness, you're letting the practitioner and their Continuity Steward know that you've read the current version of this plan and understand what you'd need to do if called upon.</span>
      </div>
      <div class="id-field">
        <label class="id-field-label">Note <span class="id-opt">(optional)</span></label>
        <textarea v-model="ackNote" class="id-textarea" rows="3" placeholder="Anything worth noting — questions, context, or a simple confirmation that you've reviewed the plan."></textarea>
        <div class="id-field-help">Your note is visible to the practitioner and their Continuity Steward.</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showAck = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showAck = false"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Confirm Awareness</button>
      </template>
    </Modal>

    <!-- ACKNOWLEDGMENT HISTORY MODAL -->
    <Modal v-model="showHistory" title="Acknowledgment History" subtitle="All stewards on this plan">
      <div class="id-hist-list">
        <div v-for="s in stewards" :key="s.name" class="id-hist-row">
          <span class="id-hist-dot"></span>
          <div class="id-hist-info">
            <div class="id-hist-name">{{ s.name }}<span v-if="s.you" class="id-hist-you"> (you)</span></div>
            <div class="id-hist-role">{{ s.role }} · </div>
          </div>
          <span class="id-hist-badge">Pending</span>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showHistory = false">Close</button>
      </template>
    </Modal>

    <!-- DOWNLOAD TOAST -->
    <Teleport to="body">
      <div v-if="downloading" class="id-toast">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        Preparing download...
        <button type="button" class="id-toast-x" @click="downloading = false"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
      </div>
    </Teleport>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
import Modal from '../../Components/Modal.vue';
defineProps({ user: Object });

const mainTab = ref('library');
const search = ref('');
const practFilter = ref('All Practitioners');
const statusFilter = ref('All Statuses');
const sortBy = ref('Most Recent');
const subTab = ref('all');

const plans = [
  { name: 'Dr. Rachel Okafor', specialty: 'Anxiety, OCD, CBT', version: 'v1', signed: 'Jan 20, 2026', status: 'pending', role: 'Alternate Support Steward', newVersion: false },
  { name: 'Dr. Michael Torres', specialty: 'Adult Psychiatry, Mood Disorders', version: 'v1', signed: 'Nov 8, 2025', status: 'pending', role: 'Primary Support Steward', newVersion: false },
  { name: 'Dr. Sarah Johnson', specialty: 'Trauma & EMDR, Family Systems', version: 'v2', signed: 'Jun 15, 2024', status: 'pending', role: 'Primary Support Steward', newVersion: true },
];

const pendingCount = computed(() => plans.filter(p => p.status === 'pending').length);

const subTabs = computed(() => [
  { key: 'all', label: 'All Plans', count: 0 },
  { key: 'acknowledged', label: 'Acknowledged', count: 0 },
  { key: 'pending', label: 'Awareness Pending', count: plans.filter(p => p.status === 'pending').length },
  { key: 'new', label: 'New Version', count: 0 },
]);

const visiblePlans = computed(() => {
  let list = plans;
  if (subTab.value === 'acknowledged') list = list.filter(p => p.status === 'acknowledged');
  else if (subTab.value === 'pending') list = list.filter(p => p.status === 'pending');
  else if (subTab.value === 'new') list = list.filter(p => p.newVersion);
  if (practFilter.value !== 'All Practitioners') list = list.filter(p => p.name === practFilter.value);
  if (search.value.trim()) { const q = search.value.toLowerCase(); list = list.filter(p => p.name.toLowerCase().includes(q) || p.specialty.toLowerCase().includes(q)); }
  return list;
});

const iA = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
const referenceDocs = [
  { tag: 'GUIDE', title: 'Support Steward Field Guide', desc: 'How to report a critical incident, coordinate with the Continuity Steward, and what to expect at each step.', version: 'V2026.2', icon: `<svg viewBox="0 0 24 24" ${iA}><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>` },
  { tag: 'FORM', title: 'Critical Incident Report Template', desc: 'The standard form fields for reporting an incident, with example narratives and contact-attempt logging.', version: 'V2026.1', icon: `<svg viewBox="0 0 24 24" ${iA}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>` },
  { tag: 'POLICY', title: 'Privacy & Confidentiality Policy', desc: 'How practitioner information is protected and what a Support Steward may and may not access.', version: 'V2026.1', icon: `<svg viewBox="0 0 24 24" ${iA}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
];

const stewards = [
  { name: 'Dr. Thomas Chen', role: 'Continuity Steward · Alternate', you: false },
  { name: 'Dr. Sarah Johnson', role: 'Continuity Steward · Primary', you: false },
  { name: 'Marcus Chen', role: 'Continuity Steward · Primary', you: false },
  { name: 'Linda Johnson', role: 'Support Steward · Primary', you: true },
];

const showAck = ref(false);
const ackPlan = ref(null);
const ackNote = ref('');
function openAck(p) { ackPlan.value = p; ackNote.value = ''; showAck.value = true; }

const showHistory = ref(false);

const downloading = ref(false);
let dlTimer = null;
function triggerDownload() {
  downloading.value = true;
  if (dlTimer) clearTimeout(dlTimer);
  dlTimer = setTimeout(() => { downloading.value = false; }, 2500);
}
</script>

<style scoped>
/* ── SS IMPORTANT DOCUMENTS ── */
.id-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:24px 30px; margin-bottom:18px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.id-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.id-title { font-family:var(--font-serif); font-size:30px; font-weight:600; letter-spacing:-0.4px; color:var(--text); margin:0; line-height:1.05; }
.id-sub { font-size:13.5px; color:var(--text-3); margin-top:8px; }

.id-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.id-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 18px; box-shadow:var(--shadow-xs); display:grid; grid-template-columns:auto 1fr; grid-template-rows:auto auto; column-gap:12px; align-items:center; }
.id-stat-ico { grid-row:1 / 3; width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.id-stat-num { font-family:var(--font-serif); font-size:24px; font-weight:700; line-height:1; color:var(--text); }
.id-stat-label { font-size:11.5px; color:var(--text-3); margin-top:3px; }

.id-tabs { display:flex; align-items:center; gap:2px; padding:5px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-full); margin-bottom:16px; box-shadow:var(--shadow-xs); flex-wrap:wrap; }
.id-tab { display:inline-flex; align-items:center; gap:8px; padding:9px 18px; font-size:13px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.id-tab:hover { color:var(--text); }
.id-tab.active { background:var(--gold-dark); color:var(--text-inverted); }
.id-tab-count { font-size:10px; font-weight:700; min-width:18px; height:18px; padding:0 5px; border-radius:var(--radius-full); background:rgba(255,255,255,.25); display:inline-flex; align-items:center; justify-content:center; }
.id-tab:not(.active) .id-tab-count { background:var(--surface-3); color:var(--text-3); }

.id-filters { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
.id-search { position:relative; flex:1; min-width:220px; }
.id-search svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.id-search input { width:100%; padding:9px 13px 9px 36px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; }
.id-search input:focus { border-color:var(--gold-dark); }
.id-search input::placeholder { color:var(--text-4); }
.id-select { width:auto; min-width:150px; }

.id-subtabs { display:inline-flex; align-items:center; gap:2px; padding:4px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-full); margin-bottom:18px; flex-wrap:wrap; }
.id-subtab { display:inline-flex; align-items:center; gap:7px; padding:8px 16px; font-size:12.5px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.id-subtab:hover { color:var(--text); }
.id-subtab.active { background:var(--gold-dark); color:var(--text-inverted); }
.id-subtab-count { font-size:10px; font-weight:700; min-width:16px; height:16px; padding:0 5px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); display:inline-flex; align-items:center; justify-content:center; }
.id-subtab.active .id-subtab-count { background:rgba(255,255,255,.25); color:var(--text-inverted); }

.id-plan-card { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); padding:18px 22px; margin-bottom:14px; }
.id-plan-top { display:flex; align-items:flex-start; gap:14px; }
.id-plan-ico { width:40px; height:40px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.id-plan-info { flex:1; min-width:0; }
.id-plan-name { font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); line-height:1.2; }
.id-plan-meta { font-size:12.5px; color:var(--text-4); margin-top:3px; }
.id-plan-chips { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
.id-chip { display:inline-flex; align-items:center; gap:4px; font-size:9.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:3px 9px; border-radius:var(--radius-full); }
.id-chip.neutral { background:var(--surface-3); color:var(--text-3); }
.id-chip.blue { background:rgba(80,120,190,.12); color:#4a6ba8; }
.id-plan-banner { display:flex; align-items:flex-start; gap:9px; margin-top:14px; padding:11px 14px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); font-size:12px; color:var(--text-3); line-height:1.5; }
.id-plan-banner svg { color:var(--text-4); flex-shrink:0; margin-top:1px; }
.id-plan-foot { display:flex; align-items:center; gap:8px; margin-top:14px; }

.id-info-banner { display:flex; align-items:flex-start; gap:10px; font-size:12.5px; color:var(--text-2); line-height:1.55; background:rgba(80,120,190,.06); border:1px solid rgba(80,120,190,.18); border-radius:var(--radius); padding:13px 16px; margin-bottom:18px; }
.id-info-banner svg { color:#5078be; flex-shrink:0; margin-top:1px; }
.id-ref-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
.id-ref-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); padding:18px 20px; display:flex; flex-direction:column; }
.id-ref-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.id-ref-ico { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; }
.id-ref-ico :deep(svg) { width:18px; height:18px; }
.id-ref-tag { font-size:9px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; padding:3px 9px; border-radius:var(--radius-full); background:var(--green-light); color:var(--green-dark); }
.id-ref-title { font-family:var(--font-serif); font-size:15.5px; font-weight:700; color:var(--text); line-height:1.3; margin-bottom:8px; }
.id-ref-desc { font-size:12.5px; color:var(--text-3); line-height:1.6; flex:1; margin-bottom:16px; }
.id-ref-foot { display:flex; align-items:center; justify-content:space-between; gap:10px; }
.id-ref-actions { display:flex; gap:8px; }
.id-ref-ver { font-size:10px; font-weight:600; padding:3px 9px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); }

/* modals */
.id-callout { display:flex; align-items:flex-start; gap:10px; padding:13px 16px; background:rgba(80,120,190,.06); border:1px solid rgba(80,120,190,.18); border-radius:var(--radius); margin-bottom:18px; font-size:12.5px; color:var(--text-2); line-height:1.55; }
.id-callout svg { color:#5078be; flex-shrink:0; margin-top:1px; }
.id-field-label { display:block; font-size:12px; font-weight:600; color:var(--text-2); margin-bottom:7px; }
.id-opt { font-weight:400; color:var(--text-4); }
.id-textarea { display:block; width:100%; padding:11px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; resize:vertical; min-height:84px; line-height:1.55; font-family:var(--font-sans); }
.id-textarea:focus { border-color:var(--gold-dark); }
.id-textarea::placeholder { color:var(--text-4); }
.id-field-help { font-size:11.5px; color:var(--text-4); margin-top:8px; }

.id-hist-list { display:flex; flex-direction:column; }
.id-hist-row { display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid var(--border); }
.id-hist-row:last-child { border-bottom:none; }
.id-hist-dot { width:8px; height:8px; border-radius:50%; background:var(--border-dark); flex-shrink:0; }
.id-hist-info { flex:1; min-width:0; }
.id-hist-name { font-size:13.5px; font-weight:700; color:var(--text); }
.id-hist-you { font-size:11.5px; font-weight:600; color:var(--gold-dark); }
.id-hist-role { font-size:12px; color:var(--text-4); margin-top:1px; }
.id-hist-badge { font-size:9.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:3px 9px; border-radius:var(--radius-full); background:var(--badge-bg-gold); color:var(--gold-dark); flex-shrink:0; }

.id-toast { position:fixed; right:24px; bottom:24px; z-index:1000; display:flex; align-items:center; gap:10px; padding:13px 16px; background:var(--text); color:var(--text-inverted); border-radius:var(--radius); box-shadow:var(--shadow-lg); font-size:13px; font-weight:600; }
.id-toast svg:first-child { color:var(--text-inverted); opacity:.7; }
.id-toast-x { background:none; border:none; color:var(--text-inverted); opacity:.6; cursor:pointer; display:inline-flex; padding:0; margin-left:4px; }
.id-toast-x:hover { opacity:1; }

@media (max-width:1000px) { .id-ref-grid { grid-template-columns:1fr 1fr; } }
@media (max-width:900px) { .id-stats { grid-template-columns:1fr 1fr; } .id-header { flex-direction:column; } }
@media (max-width:640px) { .id-ref-grid { grid-template-columns:1fr; } }
</style>
