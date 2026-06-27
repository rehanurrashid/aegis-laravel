<template>
  <AppLayout :user="user" portal="support_steward" activePage="activity" pageTitle="Activity Log" :has-emergency="true">

    <!-- ═══ HEADER ═══ -->
    <div class="al-header">
      <div>
        <div class="al-eyebrow">Support Steward Portal</div>
        <h1 class="al-title">Activity Log</h1>
        <p class="al-sub">Your reporting activity, plan acknowledgments, and coordination events</p>
      </div>
      <div class="al-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Mark All Read <span class="al-btn-count">{{ unreadCount }}</span></button>
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export</button>
      </div>
    </div>

    <!-- ═══ FILTERS ═══ -->
    <div class="al-filters">
      <div class="al-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" placeholder="Search activity..." />
      </div>
      <select v-model="category" class="form-select al-select"><option>All Categories</option><option>Critical Incident</option><option>Task</option><option>Compliance</option><option>Document</option><option>System</option></select>
      <select v-model="time" class="form-select al-select"><option>All Time</option><option>Today</option><option>This Week</option><option>This Month</option></select>
      <span class="al-count">{{ totalEvents }} events</span>
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="al-tabs">
      <button v-for="t in tabs" :key="t.key" type="button" class="al-tab" :class="{ active: activeTab === t.key }" @click="activeTab = t.key">
        <span v-if="t.icon" class="al-tab-ico" v-html="t.icon"></span>
        {{ t.label }}
        <span class="al-tab-count">{{ t.count }}</span>
      </button>
    </div>

    <!-- ═══ ACCESS NOTE ═══ -->
    <div class="al-note">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      <span><strong>Access event</strong> — any action that touches Protected Health Information. Access is logged for compliance.</span>
    </div>

    <!-- ═══ FEED ═══ -->
    <div class="al-group-label">Earlier <span class="al-group-count">{{ visibleEvents.length }} events</span></div>

    <div class="al-feed">
      <div v-for="ev in visibleEvents" :key="ev.hash" class="al-event" :class="'rail-' + ev.rail" role="button" tabindex="0" @click="openEvent(ev)" @keyup.enter="openEvent(ev)">
        <div class="al-icon" :class="'ico-' + ev.category" v-html="cats[ev.category].icon"></div>
        <div class="al-body">
          <div class="al-event-title">{{ ev.title }} <span v-if="ev.unread" class="al-unread-dot"></span></div>
          <div class="al-event-desc">{{ ev.desc }}</div>
          <div class="al-event-meta">
            <span class="al-badge" :class="'badge-' + ev.category">{{ cats[ev.category].label }}</span>
            <span v-if="ev.critical" class="al-critical">Critical</span>
            <span class="al-hash">{{ ev.hash }}</span>
          </div>
        </div>
        <span class="al-time">{{ ev.time }}</span>
      </div>
    </div>

    <!-- ═══ FOOTER ═══ -->
    <div class="al-foot">
      <span class="al-foot-count">Showing <strong>1–{{ visibleEvents.length }}</strong> of {{ totalEvents }} events</span>
      <div class="al-pager">
        <button type="button" class="al-pager-btn" disabled><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>
        <button type="button" class="al-pager-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
      </div>
    </div>

    <!-- ═══ EVENT DETAIL MODAL ═══ -->
    <Modal v-model="showDetail" :title="selectedEvent ? selectedEvent.title : ''">
      <template v-if="selectedEvent">
        <div class="al-detail">
          <div class="al-detail-row"><span class="al-detail-label">Date &amp; Time</span><span class="al-detail-val">Jun 12, 2026, {{ selectedEvent.time }}</span></div>
          <div class="al-detail-row"><span class="al-detail-label">Category</span><span class="al-detail-val">{{ cats[selectedEvent.category].label }}</span></div>
          <div class="al-detail-row"><span class="al-detail-label">Performed By</span><span class="al-detail-val">Linda Johnson (You)</span></div>
          <div class="al-detail-row"><span class="al-detail-label">Reference</span><span class="al-detail-val mono">{{ fullRef(selectedEvent.hash) }}</span></div>
          <div class="al-detail-row"><span class="al-detail-label">Description</span><span class="al-detail-val">{{ selectedEvent.desc }}</span></div>
        </div>
        <div class="al-session"><strong>Session Details:</strong> IP 192.168.1.42 · San Francisco, CA · Desktop · Chrome 124</div>
      </template>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export Entry</button>
        <button type="button" class="btn btn-outline btn-sm" @click="showDetail = false">Close</button>
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
const category = ref('All Categories');
const time = ref('All Time');
const activeTab = ref('all');

const sa = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
const cats = {
  compliance: { label: 'Compliance',        icon: `<svg viewBox="0 0 24 24" ${sa}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
  system:     { label: 'System',            icon: `<svg viewBox="0 0 24 24" ${sa}><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>` },
  critical:   { label: 'Critical Incident', icon: `<svg viewBox="0 0 24 24" ${sa}><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>` },
  task:       { label: 'Task',              icon: `<svg viewBox="0 0 24 24" ${sa}><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 9"/></svg>` },
  document:   { label: 'Document',          icon: `<svg viewBox="0 0 24 24" ${sa}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>` },
};

const events = [
  { title: 'Reported critical incident — Dr. Sarah Johnson', desc: 'Short-Term Incapacitation reported to Marcus Chen (CS). Awaiting verification.', category: 'critical', critical: true, hash: 'CCE63478-E8D', time: '8:20 AM', rail: 'red', unread: true },
  { title: 'Documentation attached — Dr. Sarah Johnson', desc: 'Hospital admission confirmation attached to the incident record.', category: 'document', critical: false, hash: '44F36F0E-A83', time: '9:20 AM', rail: 'none', unread: true },
  { title: 'Incident verified by CS — Dr. Sarah Johnson', desc: 'Marcus Chen verified the incident. Coverage activated and the task checklist generated.', category: 'critical', critical: true, hash: 'D4D08F41-3BC', time: '9:15 AM', rail: 'red', unread: false },
  { title: 'Note added to incident — Dr. Sarah Johnson', desc: 'Logged contact attempts and timeline for the permanent incident record.', category: 'critical', critical: false, hash: '004A7D9A-02C', time: '10:15 AM', rail: 'red', unread: false },
  { title: 'Reported critical incident — Dr. Michael Torres', desc: 'Short-Term Incapacitation reported to Dr. Sarah Johnson (CS). Awaiting verification.', category: 'critical', critical: true, hash: 'DD2EF9EF-A15', time: '7:45 AM', rail: 'red', unread: false },
  { title: 'In progress: Reschedule active caseload', desc: "Contacting clients to reschedule appointments for Dr. Sarah Johnson's practice.", category: 'task', critical: false, hash: '10E41CE6-94E', time: '12:00 PM', rail: 'gold', unread: false },
  { title: 'Completed: Notify Continuity Steward', desc: 'Situation report sent to the Continuity Steward per the standby task list.', category: 'task', critical: false, hash: '6E0F9053-2C3', time: '1:35 PM', rail: 'none', unread: true },
  { title: 'Flagged Continuity Steward as unresponsive', desc: 'Notified Dr. Sarah Johnson that her alternate CS (Dr. Amelia Rodriguez) was unreachable after three contact attempts.', category: 'system', critical: false, hash: 'F645DC4A-902', time: '2:22 PM', rail: 'gold', unread: false },
  { title: 'Acknowledged Continuity Plan — Dr. Sarah Johnson', desc: 'Confirmed awareness of the current plan version (v2) and your Support Steward responsibilities.', category: 'compliance', critical: false, hash: 'EB79589D-B05', time: '2:31 PM', rail: 'none', unread: true },
  { title: 'New Continuity Plan to acknowledge: Dr. Rachel Okafor', desc: 'You have been designated Alternate Support Steward. Review and acknowledge awareness of the plan.', category: 'compliance', critical: false, hash: 'B0965771-9AC', time: '2:05 PM', rail: 'none', unread: false },
  { title: 'Plan update available — Dr. Michael Torres', desc: 'A new version of the Continuity Plan needs your awareness review.', category: 'compliance', critical: false, hash: '89930CDC-F5A', time: '11:05 AM', rail: 'none', unread: false },
  { title: 'Completed: Confirm CS contact details', desc: 'Verified all Continuity Steward contact methods are current and saved.', category: 'task', critical: false, hash: '5694B329-ED4', time: '10:23 AM', rail: 'none', unread: true },
  { title: 'Acknowledged Support Steward MOU', desc: 'Memorandum of Understanding acknowledged and added to your record.', category: 'document', critical: false, hash: '248F3FE3-C94', time: '11:30 AM', rail: 'none', unread: true },
  { title: 'Profile updated', desc: 'Updated availability windows and emergency contact details.', category: 'system', critical: false, hash: 'C964DA91-A34', time: '2:00 PM', rail: 'none', unread: false },
  { title: 'Countersigned plan acknowledgment — Dr. Sarah Johnson', desc: 'Dr. Johnson and her Continuity Steward have been notified that you reviewed the plan.', category: 'compliance', critical: false, hash: '229FFD95-B10', time: '2:30 PM', rail: 'none', unread: true },
];

const totalEvents = events.length;

const tabs = computed(() => [
  { key: 'all', label: 'All', icon: '', count: totalEvents },
  { key: 'critical', label: 'Critical Incident', icon: cats.critical.icon, count: events.filter(e => e.category === 'critical').length },
  { key: 'tasks', label: 'Tasks', icon: cats.task.icon, count: events.filter(e => e.category === 'task').length },
  { key: 'compliance', label: 'Compliance', icon: cats.compliance.icon, count: events.filter(e => e.category === 'compliance').length },
  { key: 'documents', label: 'Documents', icon: cats.document.icon, count: events.filter(e => e.category === 'document').length },
]);

const tabCategoryMap = { critical: 'critical', tasks: 'task', compliance: 'compliance', documents: 'document' };
const visibleEvents = computed(() => {
  let list = events;
  if (activeTab.value !== 'all') list = list.filter(e => e.category === tabCategoryMap[activeTab.value]);
  if (category.value !== 'All Categories') list = list.filter(e => cats[e.category].label === category.value);
  if (search.value.trim()) { const q = search.value.toLowerCase(); list = list.filter(e => e.title.toLowerCase().includes(q) || e.desc.toLowerCase().includes(q)); }
  return list;
});

const unreadCount = computed(() => events.filter(e => e.unread).length);

// ── detail modal ──
const showDetail = ref(false);
const selectedEvent = ref(null);
function openEvent(ev) {
  selectedEvent.value = ev;
  showDetail.value = true;
}
function fullRef(hash) {
  const h = hash.replace(/[^a-z0-9]/gi, '').toLowerCase().padEnd(32, '0').slice(0, 32);
  return `${h.slice(0, 8)}-${h.slice(8, 12)}-${h.slice(12, 16)}-${h.slice(16, 20)}-${h.slice(20, 32)}`;
}
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════
   SS — ACTIVITY LOG
   ════════════════════════════════════════════════════════════════ */
.al-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:24px 30px; margin-bottom:16px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.al-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.al-title { font-family:var(--font-serif); font-size:30px; font-weight:600; letter-spacing:-0.4px; color:var(--text); margin:0; line-height:1.05; }
.al-sub { font-size:13.5px; color:var(--text-3); margin-top:8px; }
.al-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; }
.al-btn-count { font-size:10px; font-weight:700; padding:1px 7px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); }

.al-filters { display:flex; align-items:center; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.al-search { position:relative; flex:1; min-width:220px; }
.al-search svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.al-search input { width:100%; padding:10px 13px 10px 36px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.al-search input:focus { border-color:var(--gold-dark); }
.al-search input::placeholder { color:var(--text-4); }
.al-select { width:auto; min-width:150px; }
.al-count { font-size:12px; color:var(--text-4); white-space:nowrap; }

.al-tabs { display:inline-flex; align-items:center; gap:2px; padding:4px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-full); margin-bottom:14px; flex-wrap:wrap; }
.al-tab { display:inline-flex; align-items:center; gap:7px; padding:8px 16px; font-size:12.5px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.al-tab:hover { color:var(--text); }
.al-tab.active { background:var(--gold-dark); color:var(--text-inverted); }
.al-tab-ico { display:inline-flex; }
.al-tab-ico :deep(svg) { width:13px; height:13px; }
.al-tab-count { font-size:10px; font-weight:700; min-width:18px; height:18px; padding:0 5px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); display:inline-flex; align-items:center; justify-content:center; }
.al-tab.active .al-tab-count { background:rgba(255,255,255,.25); color:var(--text-inverted); }

.al-note { display:flex; align-items:center; gap:9px; padding:11px 16px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius-lg); margin-bottom:18px; font-size:12px; color:var(--text-3); }
.al-note svg { color:var(--gold-dark); flex-shrink:0; }
.al-note strong { color:var(--text-2); font-weight:700; }

.al-group-label { display:flex; align-items:center; gap:10px; font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--text-4); margin-bottom:12px; padding-bottom:10px; border-bottom:1px solid var(--border); }
.al-group-count { font-weight:600; padding:2px 8px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-4); letter-spacing:.5px; }

.al-feed { display:flex; flex-direction:column; gap:10px; }
.al-event { display:flex; gap:14px; padding:16px 20px; background:var(--surface); border:1px solid var(--border); border-left:3px solid transparent; border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); cursor:pointer; transition:box-shadow .18s ease,border-color .18s ease; }
.al-event:hover { box-shadow:var(--shadow-sm); border-color:var(--border-dark); }
.al-event.rail-red:hover { border-color:rgba(160,45,34,.35); }
.al-event.rail-gold { border-left-color:var(--gold-dark); }
.al-event.rail-red { border-left-color:var(--red); background:rgba(160,45,34,.05); border-color:rgba(160,45,34,.18); }
.al-icon { width:34px; height:34px; border-radius:50%; background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.al-icon :deep(svg) { width:15px; height:15px; }
.al-icon.ico-critical { background:var(--badge-bg-gold); color:var(--gold-dark); }
.al-icon.ico-task { background:var(--green-light); color:var(--green-dark); }
.al-icon.ico-compliance { background:var(--surface-3); color:var(--text-3); }
.al-icon.ico-document { background:var(--badge-bg-gold); color:var(--gold-dark); }
.al-body { flex:1; min-width:0; }
.al-event-title { font-size:13.5px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; }
.al-unread-dot { width:7px; height:7px; border-radius:50%; background:var(--gold-dark); flex-shrink:0; }
.al-event-desc { font-size:12.5px; color:var(--text-3); line-height:1.55; margin:4px 0 9px; }
.al-event-meta { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.al-badge { font-size:9.5px; font-weight:700; letter-spacing:.4px; padding:3px 9px; border-radius:var(--radius-full); }
.badge-compliance { background:var(--green-light); color:var(--green-dark); }
.badge-system     { background:var(--surface-3); color:var(--text-3); }
.badge-critical   { background:var(--red-light); color:var(--red-dark); }
.badge-task       { background:var(--green-light); color:var(--green-dark); }
.badge-document   { background:var(--badge-bg-gold); color:var(--gold-dark); }
.al-critical { font-size:9.5px; font-weight:800; letter-spacing:1px; text-transform:uppercase; color:var(--red); }
.al-hash { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:10.5px; color:var(--text-4); letter-spacing:.3px; }
.al-time { font-size:11.5px; color:var(--text-4); flex-shrink:0; white-space:nowrap; }

.al-foot { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-top:18px; }
.al-foot-count { font-size:12.5px; color:var(--text-3); }
.al-foot-count strong { color:var(--text); font-weight:600; }
.al-pager { display:flex; gap:6px; }
.al-pager-btn { width:32px; height:32px; border:1px solid var(--border-dark); border-radius:var(--radius); background:var(--surface); color:var(--text-3); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s ease; }
.al-pager-btn:hover:not(:disabled) { border-color:var(--gold-dark); color:var(--gold-dark); }
.al-pager-btn:disabled { opacity:.4; cursor:not-allowed; }

.al-detail { display:flex; flex-direction:column; }
.al-detail-row { display:flex; gap:16px; padding:11px 0; border-bottom:1px solid var(--border); }
.al-detail-row:first-child { padding-top:0; }
.al-detail-label { font-size:9.5px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); width:120px; flex-shrink:0; padding-top:1px; }
.al-detail-val { font-size:13px; color:var(--text); line-height:1.5; }
.al-detail-val.mono { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:12px; color:var(--text-2); }
.al-session { margin-top:16px; padding:12px 16px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); font-size:12px; color:var(--text-3); line-height:1.5; }
.al-session strong { color:var(--text-2); font-weight:700; }

@media (max-width:900px) { .al-header { flex-direction:column; } }
@media (max-width:520px) { .al-detail-row { flex-direction:column; gap:4px; } .al-detail-label { width:auto; } }
</style>
