<template>
  <AppLayout :user="user" portal="business_partner" activePage="milestones" pageTitle="Milestones">

    <!-- ═══ HEADER ═══ -->
    <div class="ms-header">
      <div class="ms-header-l">
        <h1 class="ms-title">Milestones</h1>
        <div class="ms-subtitle">Track deliverables, submit work, and get paid on approval</div>
      </div>
      <div class="ms-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <a href="/business-partner/find-jobs" class="btn btn-primary btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg> Find Jobs</a>
      </div>
    </div>

    <!-- ═══ STATS ═══ -->
    <div class="ms-stats">
      <div class="ms-stat"><div class="ms-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="ms-stat-num">1</div><div class="ms-stat-label">Due this week</div></div></div>
      <div class="ms-stat"><div class="ms-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="ms-stat-num">0</div><div class="ms-stat-label">Pending approval</div></div></div>
      <div class="ms-stat"><div class="ms-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div><div class="ms-stat-num">0</div><div class="ms-stat-label">Approved this month</div></div></div>
      <div class="ms-stat"><div class="ms-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="ms-stat-num">$500</div><div class="ms-stat-label">Pipeline value</div></div></div>
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="ms-tabs">
      <button v-for="t in tabs" :key="t.key" type="button" class="ms-tab" :class="{ active: activeTab === t.key }" @click="activeTab = t.key">{{ t.label }} <span v-if="t.count" class="ms-tab-count">{{ t.count }}</span></button>
    </div>

    <!-- ═══ FILTER BAR ═══ -->
    <div class="ms-filterbar">
      <div class="ms-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" placeholder="Search milestones..." />
      </div>
      <select class="form-select"><option>All contracts</option><option>SimplePractice migration &amp; setup</option><option>EHR Migration &amp; Training</option></select>
      <select class="form-select"><option>All practitioners</option><option>Dr. Sarah Johnson</option><option>Elena Vasquez</option></select>
      <select class="form-select"><option>Due date</option><option>Payment</option><option>Status</option></select>
    </div>

    <!-- ═══ MILESTONE GRID ═══ -->
    <div class="ms-grid">
      <div v-for="m in filteredMilestones" :key="m.id" class="ms-card" :class="'rail-' + m.rail">
        <div class="ms-card-body">
          <div class="ms-card-top">
            <span class="ms-av">{{ m.initials }}</span>
            <div class="ms-card-headinfo">
              <div class="ms-card-title">{{ m.title }}</div>
              <div class="ms-card-who">{{ m.contract }} · {{ m.practitioner }}</div>
            </div>
          </div>

          <div class="ms-meta-row">
            <span class="ms-badge" :class="'st-' + m.rail">{{ m.statusLabel }}</span>
            <span class="ms-meta-item"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> {{ m.overdue }}</span>
            <span class="ms-meta-item"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg> {{ m.type }}</span>
          </div>

          <div class="ms-amt">{{ m.amount }}</div>
          <div class="ms-payon">{{ m.payNote }}</div>

          <div class="ms-banner" :class="m.bannerType">
            <svg v-if="m.bannerType === 'green'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            {{ m.bannerText }}
          </div>

          <p class="ms-desc">{{ m.desc }}</p>
        </div>

        <div class="ms-card-foot">
          <div class="ms-foot-dates">
            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Due {{ m.due }}</span>
            <span v-if="m.completedDate"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> {{ m.completedDate }}</span>
          </div>
          <div class="ms-foot-actions">
            <button type="button" class="btn-icon" title="Documents" @click="openDetail(m)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></button>
            <button type="button" class="btn-icon" title="View detail" @click="openDetail(m)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            <button v-if="m.status === 'inprogress'" type="button" class="btn-icon ms-submit-btn" title="Submit work" @click="openSubmit(m)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></button>
            <button v-else type="button" class="btn-icon" title="Invoice"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></button>
          </div>
        </div>
      </div>
      <div v-if="!filteredMilestones.length" class="ms-empty">No milestones in this view.</div>
    </div>

    <!-- ═══ MILESTONE DETAIL MODAL ═══ -->
    <Modal v-model="showDetail" title="Milestone detail" size="lg">
      <div class="ms-det-grid">
        <div class="ms-det-box"><div class="ms-det-k">Milestone</div><div class="ms-det-v">{{ sel.title }}</div></div>
        <div class="ms-det-box"><div class="ms-det-k">Status</div><div class="ms-det-v">{{ sel.statusLabel }}</div></div>
        <div class="ms-det-box"><div class="ms-det-k">Payment</div><div class="ms-det-v">{{ sel.amount }}</div></div>
        <div class="ms-det-box"><div class="ms-det-k">Due date</div><div class="ms-det-v">{{ sel.dueIso }}</div></div>
        <div class="ms-det-box"><div class="ms-det-k">Engagement type</div><div class="ms-det-v">{{ sel.type }}</div></div>
        <div class="ms-det-box"><div class="ms-det-k">Practitioner</div><div class="ms-det-v">{{ sel.practitioner }}</div></div>
        <div class="ms-det-box span2"><div class="ms-det-k">Contract</div><div class="ms-det-v">{{ sel.contractFull }}</div></div>
        <div class="ms-det-box span2"><div class="ms-det-k">Description</div><div class="ms-det-v">{{ sel.desc }}</div></div>
      </div>
      <div v-if="sel.completedIso" class="ms-banner green ms-det-banner"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Completed on {{ sel.completedIso }}</div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showDetail = false">Close</button>
        <a href="/business-partner/contracts" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> View contract</a>
      </template>
    </Modal>

    <!-- ═══ SUBMIT WORK MODAL ═══ -->
    <Modal v-model="showSubmit" title="Submit work">
      <div class="ms-sub-jobcard">
        <div class="ms-sub-job-l">
          <div class="ms-sub-job-title">{{ sel.title }}</div>
          <div class="ms-sub-job-sub">{{ sel.contract }} · {{ sel.practitioner }}</div>
        </div>
        <div class="ms-sub-job-r">
          <span class="ms-sub-due">Due {{ sel.dueIso }}</span>
          <span class="ms-sub-amt">{{ sel.amount }}</span>
        </div>
      </div>

      <div class="ms-field"><label class="ms-label">Description <span class="ms-req">*</span></label><textarea class="ms-input ms-textarea" rows="3" v-model="form.desc" placeholder="Describe the work completed for this milestone..."></textarea></div>
      <div class="ms-field"><label class="ms-label">Completion date <span class="ms-req">*</span></label><input type="date" class="ms-input" v-model="form.completed" /></div>
      <div class="ms-field"><label class="ms-label">Hours spent</label><input class="ms-input" v-model="form.hours" placeholder="e.g. 18" /></div>
      <div class="ms-field"><label class="ms-label">Submitted by</label><select class="form-select" v-model="form.by"><option>Submitting as owner</option><option>Riley Chen</option><option>Marcus T.</option></select></div>
      <div class="ms-field"><label class="ms-label">External link</label><input class="ms-input" v-model="form.link" placeholder="Google Drive or Dropbox link" /></div>

      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showSubmit = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showSubmit = false"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Submit for review</button>
      </template>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
import Modal from '../../Components/Modal.vue';
defineProps({ user: Object });

const search = ref('');
const activeTab = ref('all');

const tabs = [
  { key: 'all', label: 'All', count: 3 },
  { key: 'duesoon', label: 'Due soon', count: 0 },
  { key: 'inprogress', label: 'In progress', count: 1 },
  { key: 'pending', label: 'Pending approval', count: 0 },
  { key: 'approved', label: 'Approved', count: 1 },
  { key: 'paid', label: 'Paid', count: 1 },
  { key: 'rejected', label: 'Rejected', count: 0 },
];

const milestones = [
  { id: 1, initials: 'SJ', title: 'Phase 1: Account setup & form templates', contract: 'SimplePractice migration & setup', contractFull: 'SimplePractice migration & setup · Lotus Psychology Group', practitioner: 'Dr. Sarah Johnson', status: 'paid', statusLabel: 'Paid', rail: 'green', overdue: 'Overdue by 89 days', type: 'Per-Deliverable / Milestone', amount: '$600', payNote: 'on approval', bannerType: 'green', bannerText: 'Payment received on Mar 23, 2026.', desc: 'Create SimplePractice account, configure intake forms, consent docs, treatment plan templates.', due: 'Mar 25, 2026', dueIso: '2026-03-25', completedDate: 'Mar 23, 2026', completedIso: '2026-03-23' },
  { id: 2, initials: 'SJ', title: 'Phase 2: Billing & Stripe integration', contract: 'SimplePractice migration & setup', contractFull: 'SimplePractice migration & setup · Lotus Psychology Group', practitioner: 'Dr. Sarah Johnson', status: 'approved', statusLabel: 'Approved', rail: 'green', overdue: 'Overdue by 68 days', type: 'Per-Deliverable / Milestone', amount: '$700', payNote: 'on approval', bannerType: 'green', bannerText: 'Approved on Apr 12, 2026. Invoice generated.', desc: 'Configure billing rules, sliding scale, OON claim flow. Connect Stripe Connect for client payments.', due: 'Apr 15, 2026', dueIso: '2026-04-15', completedDate: 'Apr 12, 2026', completedIso: '2026-04-12' },
  { id: 3, initials: 'SJ', title: 'Phase 3: Migration, training, & launch', contract: 'SimplePractice migration & setup', contractFull: 'SimplePractice migration & setup · Lotus Psychology Group', practitioner: 'Dr. Sarah Johnson', status: 'inprogress', statusLabel: 'In Progress', rail: 'red', overdue: 'Overdue by 43 days', type: 'Per-Deliverable / Milestone', amount: '$500', payNote: 'on approval', bannerType: 'red', bannerText: 'Overdue by 43 days — submit work to keep the engagement on track', desc: 'Migrate 45 existing clients, train Dr. Johnson and admin staff, go-live support.', due: 'May 10, 2026', dueIso: '2026-05-10', completedDate: '' },
];

const filteredMilestones = computed(() => {
  return milestones.filter(m => {
    if (activeTab.value !== 'all' && m.status !== activeTab.value) return false;
    if (search.value) {
      const q = search.value.toLowerCase();
      if (!m.title.toLowerCase().includes(q) && !m.contract.toLowerCase().includes(q) && !m.practitioner.toLowerCase().includes(q)) return false;
    }
    return true;
  });
});

// ── modals ──
const sel = ref({});
const showDetail = ref(false);
function openDetail(m) { sel.value = m; showDetail.value = true; }

const showSubmit = ref(false);
const form = reactive({ desc: '', completed: '', hours: '', by: 'Submitting as owner', link: '' });
function openSubmit(m) { sel.value = m; form.desc = ''; form.completed = ''; form.hours = ''; form.link = ''; showSubmit.value = true; }
</script>

<style scoped>
.ms-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:30px 34px; margin-bottom:18px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.ms-title { font-family:var(--font-serif); font-size:34px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.ms-subtitle { font-size:13.5px; color:var(--text-3); margin-top:9px; }
.ms-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; }

.ms-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
.ms-stat { display:flex; align-items:center; gap:14px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px 22px; box-shadow:var(--shadow-xs); }
.ms-stat-ico { width:36px; height:36px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ms-stat-num { font-family:var(--font-serif); font-size:24px; font-weight:700; color:var(--text); line-height:1; }
.ms-stat-label { font-size:11.5px; font-weight:500; color:var(--text-3); margin-top:6px; }

.ms-tabs { display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:16px; }
.ms-tab { display:inline-flex; align-items:center; gap:7px; padding:8px 16px; font-size:13px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; }
.ms-tab:hover { color:var(--gold-dark); }
.ms-tab.active { background:var(--gold-dark); color:var(--text-inverted); }
.ms-tab-count { display:inline-flex; align-items:center; justify-content:center; min-width:18px; height:18px; padding:0 5px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); font-size:10.5px; font-weight:700; }
.ms-tab.active .ms-tab-count { background:rgba(255,255,255,.25); color:var(--text-inverted); }

.ms-filterbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.ms-search { display:flex; align-items:center; gap:8px; flex:1; min-width:240px; padding:10px 15px; background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius-full); color:var(--text-4); }
.ms-search input { flex:1; border:none; outline:none; background:none; font-size:13px; color:var(--text); }
.ms-filterbar .form-select { width:auto; min-width:150px; border-radius:var(--radius-full); }

.ms-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:start; }
.ms-card { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--border-dark); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; display:flex; flex-direction:column; transition:transform .18s ease,box-shadow .18s ease; }
.ms-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-sm); }
.ms-card.rail-green { border-left-color:var(--green); }
.ms-card.rail-red { border-left-color:var(--red); }
.ms-card.rail-blue { border-left-color:#3b6fb5; }
.ms-card-body { padding:20px 22px; flex:1; }
.ms-card-top { display:flex; align-items:flex-start; gap:12px; }
.ms-av { width:38px; height:38px; border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:13px; flex-shrink:0; }
.ms-card-headinfo { flex:1; min-width:0; }
.ms-card-title { font-family:var(--font-serif); font-size:17px; font-weight:600; color:var(--text); line-height:1.25; }
.ms-card-who { font-size:12.5px; color:var(--gold-dark); margin-top:3px; }

.ms-meta-row { display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin-top:12px; }
.ms-badge { font-size:9.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; padding:3px 10px; border-radius:var(--radius-full); }
.ms-badge.st-green { background:var(--green-light); color:var(--green-dark); }
.ms-badge.st-red { background:#e3edf9; color:#3b6fb5; }
.ms-badge.st-blue { background:#e3edf9; color:#3b6fb5; }
.ms-meta-item { display:inline-flex; align-items:center; gap:6px; font-size:12px; color:var(--text-3); }
.ms-meta-item svg { color:var(--text-4); }

.ms-amt { font-family:var(--font-serif); font-size:26px; font-weight:700; color:var(--text); margin-top:16px; line-height:1; }
.ms-payon { font-size:12px; color:var(--text-3); margin-top:6px; }

.ms-banner { display:flex; align-items:center; gap:9px; font-size:12.5px; line-height:1.4; padding:11px 14px; border-radius:var(--radius); margin-top:16px; }
.ms-banner svg { flex-shrink:0; }
.ms-banner.green { background:var(--green-light); color:var(--green-dark); border:1px solid color-mix(in srgb, var(--green) 25%, transparent); }
.ms-banner.red { background:var(--red-light); color:var(--red-dark, var(--red)); border:1px solid color-mix(in srgb, var(--red) 25%, transparent); }
.ms-desc { font-size:12.5px; color:var(--text-3); line-height:1.6; margin:14px 0 0; }

.ms-card-foot { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:13px 22px; border-top:1px solid var(--border); background:var(--surface-2); flex-wrap:wrap; }
.ms-foot-dates { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.ms-foot-dates span { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; color:var(--green-dark); }
.ms-foot-dates svg { color:var(--text-4); }
.ms-foot-actions { display:flex; align-items:center; gap:5px; }
.ms-submit-btn { background:var(--gold-dark); border-color:var(--gold-dark); color:var(--text-inverted); }
.ms-submit-btn:hover { background:var(--gold-deep, var(--gold-dark)); }
.ms-empty { grid-column:1/-1; text-align:center; padding:50px 20px; color:var(--text-4); font-size:13px; }

/* detail modal */
.ms-det-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.ms-det-box { border:1px solid var(--border); border-radius:var(--radius); padding:14px 16px; background:var(--surface-2); }
.ms-det-box.span2 { grid-column:1/-1; }
.ms-det-k { font-size:9.5px; font-weight:700; letter-spacing:.9px; text-transform:uppercase; color:var(--text-4); }
.ms-det-v { font-size:13.5px; color:var(--text); margin-top:6px; line-height:1.5; }
.ms-det-banner { margin-top:14px; }

/* submit modal */
.ms-sub-jobcard { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius-lg); padding:14px 18px; margin-bottom:18px; }
.ms-sub-job-title { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.ms-sub-job-sub { font-size:12px; color:var(--text-3); margin-top:3px; }
.ms-sub-job-r { text-align:right; flex-shrink:0; }
.ms-sub-due { display:inline-block; font-size:9.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; color:var(--gold-dark); background:var(--badge-bg-gold); padding:3px 9px; border-radius:var(--radius-full); }
.ms-sub-amt { display:block; font-family:var(--font-serif); font-size:18px; font-weight:700; color:var(--text); margin-top:8px; }
.ms-field { margin-bottom:15px; }
.ms-field:last-of-type { margin-bottom:0; }
.ms-label { display:block; font-size:12.5px; font-weight:600; color:var(--text-2); margin-bottom:7px; }
.ms-req { color:var(--red); }
.ms-input { display:block; width:100%; padding:10px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.ms-input:focus { border-color:var(--gold-dark); }
.ms-input::placeholder { color:var(--text-4); }
.ms-textarea { resize:vertical; min-height:88px; line-height:1.55; font-family:var(--font-sans); }

@media (max-width:1000px) { .ms-stats { grid-template-columns:repeat(2,1fr); } .ms-grid { grid-template-columns:1fr; } .ms-det-grid { grid-template-columns:1fr; } }
@media (max-width:700px) { .ms-header { flex-direction:column; } .ms-stats { grid-template-columns:1fr; } }
</style>
