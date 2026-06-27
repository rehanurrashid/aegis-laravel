<template>
  <AppLayout :user="user" portal="continuity_steward" activePage="important-documents" pageTitle="Important Documents">

    <!-- ═══ HEADER ═══ -->
    <div class="id-header">
      <div>
        <div class="id-eyebrow">Continuity Steward</div>
        <h1 class="id-title">Important Documents</h1>
        <p class="id-sub">View, sign, manage, and store continuity plans and supporting documents.</p>
      </div>
      <div class="id-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <button type="button" class="btn btn-primary btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Request Document</button>
      </div>
    </div>

    <!-- ═══ STAT CARDS ═══ -->
    <div class="id-stats">
      <div class="id-stat">
        <div class="id-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
        <div class="id-stat-num">{{ stats.awaiting }}</div>
        <div class="id-stat-label">Awaiting Countersignature</div>
      </div>
      <div class="id-stat">
        <div class="id-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg></div>
        <div class="id-stat-num">{{ stats.signed }}</div>
        <div class="id-stat-label">Signed Plans</div>
      </div>
      <div class="id-stat">
        <div class="id-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div class="id-stat-num">{{ stats.reference }}</div>
        <div class="id-stat-label">Reference Documents</div>
      </div>
      <div class="id-stat">
        <div class="id-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="id-stat-num">{{ stats.certPending }}</div>
        <div class="id-stat-label">Certification Pending</div>
      </div>
    </div>

    <!-- ═══ MAIN TABS ═══ -->
    <div class="id-tabs">
      <button v-for="t in tabs" :key="t.key" type="button" class="id-tab" :class="{ active: activeTab === t.key }" @click="activeTab = t.key">
        <span class="id-tab-ico" v-html="t.icon"></span>
        {{ t.label }}
      </button>
    </div>

    <!-- ═══ FILTER ROW (inbox + signed only) ═══ -->
    <div v-if="activeTab !== 'reference'" class="id-filters">
      <div class="id-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" placeholder="Search plans or practitioners..." />
      </div>
      <select v-model="statusFilter" class="form-select id-select"><option>All Statuses</option><option>Certified</option><option>Cert. Pending</option></select>
      <select v-model="sortBy" class="form-select id-select"><option>Most Recent</option><option>Oldest</option><option>Name (A–Z)</option></select>
    </div>

    <!-- ═══ COUNTERSIGNATURE INBOX ═══ -->
    <div v-if="activeTab === 'inbox'" class="id-empty">
      <div class="id-empty-ico"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
      <div class="id-empty-title">You're all caught up</div>
      <div class="id-empty-sub">No plans are currently awaiting your countersignature. When a practitioner designates you and signs their plan, it will appear here.</div>
    </div>

    <!-- ═══ SIGNED PLANS ═══ -->
    <div v-else-if="activeTab === 'signed'">
      <div class="id-subtabs">
        <button v-for="s in signedSubtabs" :key="s.key" type="button" class="id-subtab" :class="{ active: signedTab === s.key }" @click="signedTab = s.key">{{ s.label }}</button>
      </div>

      <div v-for="plan in visibleSignedPlans" :key="plan.provider" class="id-plan-card">
        <div class="id-plan-rail"></div>
        <div class="id-plan-main">
          <div class="id-plan-top">
            <div class="id-plan-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
            <div class="id-plan-info">
              <div class="id-plan-name">{{ plan.provider }}</div>
              <div class="id-plan-meta">{{ plan.specialty }} · Countersigned {{ plan.countersigned }}</div>
              <div class="id-plan-chips">
                <span class="id-chip chip-green"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Countersigned</span>
                <span class="id-chip chip-blue">Alternate CS</span>
                <span class="id-chip chip-neutral"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Certification Pending</span>
              </div>
            </div>
          </div>
          <div class="id-plan-banner">Your certification of the task list for this plan is still pending. Please review your tasks and certify when ready.</div>
          <div class="id-plan-foot">
            <button type="button" class="btn-icon" data-tip="View"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            <button type="button" class="btn-icon" data-tip="Download"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></button>
            <button type="button" class="btn-icon" data-tip="History"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></button>
            <button type="button" class="btn-icon id-icon-gold" data-tip="Certify"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></button>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ REFERENCE DOCUMENTS ═══ -->
    <div v-else>
      <div class="id-info-banner">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <span>These documents are maintained by Aegis and are available to all Continuity Stewards as standard reference materials. They are read-only and cannot be modified.</span>
      </div>
      <div class="id-ref-grid">
        <div v-for="doc in referenceDocs" :key="doc.title" class="id-ref-card">
          <div class="id-ref-head">
            <div class="id-ref-ico" v-html="doc.icon"></div>
            <span class="id-ref-tag">{{ doc.tag }}</span>
          </div>
          <div class="id-ref-title">{{ doc.title }}</div>
          <div class="id-ref-desc">{{ doc.desc }}</div>
          <div class="id-ref-foot">
            <div class="id-ref-actions">
              <button type="button" class="btn-icon" data-tip="View"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
              <button type="button" class="btn-icon" data-tip="Download"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></button>
            </div>
            <span class="id-ref-ver">{{ doc.version }}</span>
          </div>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
defineProps({ user: Object });

const stats = { awaiting: 0, signed: 1, reference: 3, certPending: 1 };

const search = ref('');
const statusFilter = ref('All Statuses');
const sortBy = ref('Most Recent');

const activeTab = ref('inbox');
const tabs = [
  { key: 'inbox', label: 'Countersignature Inbox', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2h6a1 1 0 0 1 1 1v2H8V3a1 1 0 0 1 1-1z"/><rect x="4" y="4" width="16" height="18" rx="2"/></svg>' },
  { key: 'signed', label: 'Signed Plans', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>' },
  { key: 'reference', label: 'Reference Documents', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>' },
];

const signedTab = ref('all');
const signedSubtabs = [
  { key: 'all', label: 'All Document Types' },
  { key: 'certified', label: 'Certified' },
  { key: 'pending', label: 'Cert. Pending' },
];

const signedPlans = [
  { provider: 'Dr. Sarah Johnson', specialty: 'Trauma & EMDR, Family Systems', countersigned: 'Feb 16, 2026', status: 'pending' },
];
const visibleSignedPlans = computed(() => {
  // Card appears under every Signed Plans sub-tab; only search narrows it.
  let list = signedPlans;
  if (search.value.trim()) { const q = search.value.toLowerCase(); list = list.filter(p => p.provider.toLowerCase().includes(q)); }
  return list;
});

const iAttr = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
const referenceDocs = [
  { tag: 'BAA', title: 'Business Associate Agreement (HIPAA BAA)', desc: 'Standard BAA required when you access protected health information on behalf of a practitioner. Review before beginning any active incident engagement.', version: 'V2026.1', icon: `<svg viewBox="0 0 24 24" ${iAttr}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
  { tag: 'MOU', title: 'Continuity Steward MOU Template', desc: 'Standard Memorandum of Understanding between a Continuity Steward and a practitioner. Use this as the baseline when establishing a new stewardship relationship.', version: 'V2026.1', icon: `<svg viewBox="0 0 24 24" ${iAttr}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>` },
  { tag: 'FORMS', title: 'Aegis Sample Forms Library', desc: 'A collection of vetted forms for client notification, practice closure, records transfer authorization, and licensing board reporting. Organized by incident type.', version: 'V2026.3', icon: `<svg viewBox="0 0 24 24" ${iAttr}><path d="M9 2h6a1 1 0 0 1 1 1v2H8V3a1 1 0 0 1 1-1z"/><rect x="4" y="4" width="16" height="18" rx="2"/></svg>` },
  { tag: 'GUIDE', title: 'Critical Incident Response Guide', desc: 'Step-by-step protocol covering all seven critical incident types. Reference this guide when initiating a continuity response for any practitioner in your care.', version: 'V2026.2', icon: `<svg viewBox="0 0 24 24" ${iAttr}><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>` },
];
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════
   CS — IMPORTANT DOCUMENTS
   ════════════════════════════════════════════════════════════════ */

/* HEADER */
.id-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:24px 30px; margin-bottom:18px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.id-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.id-title { font-family:var(--font-serif); font-size:30px; font-weight:600; letter-spacing:-0.4px; color:var(--text); margin:0; line-height:1.05; }
.id-sub { font-size:13.5px; color:var(--text-3); margin-top:8px; }
.id-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; }

/* STAT CARDS */
.id-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.id-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 18px; box-shadow:var(--shadow-xs); display:grid; grid-template-columns:auto 1fr; grid-template-rows:auto auto; column-gap:12px; align-items:center; transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease; }
.id-stat:hover { transform:translateY(-2px); box-shadow:var(--shadow-sm); border-color:var(--surface-4); }
.id-stat-ico { grid-row:1 / 3; width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.id-stat-num { font-family:var(--font-serif); font-size:24px; font-weight:700; line-height:1; color:var(--text); }
.id-stat-label { font-size:11.5px; color:var(--text-3); margin-top:3px; }

/* MAIN TABS (full-width segmented bar) */
.id-tabs { display:flex; align-items:center; gap:2px; padding:5px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-full); margin-bottom:16px; box-shadow:var(--shadow-xs); flex-wrap:wrap; }
.id-tab { display:inline-flex; align-items:center; gap:8px; padding:9px 18px; font-size:13px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.id-tab:hover { color:var(--text); }
.id-tab.active { background:var(--gold-dark); color:var(--text-inverted); }
.id-tab-ico { display:inline-flex; }
.id-tab-ico :deep(svg) { width:14px; height:14px; }

/* FILTERS */
.id-filters { display:flex; gap:10px; margin-bottom:18px; flex-wrap:wrap; }
.id-search { position:relative; flex:1; min-width:220px; }
.id-search svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.id-search input { width:100%; padding:9px 13px 9px 36px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.id-search input:focus { border-color:var(--gold-dark); }
.id-search input::placeholder { color:var(--text-4); }
.id-select { width:auto; min-width:150px; }

/* SUB-TABS */
.id-subtabs { display:inline-flex; align-items:center; gap:2px; padding:4px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-full); margin-bottom:16px; flex-wrap:wrap; }
.id-subtab { padding:8px 16px; font-size:12.5px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.id-subtab:hover { color:var(--text); }
.id-subtab.active { background:var(--gold-dark); color:var(--text-inverted); }

/* SIGNED PLAN CARD */
.id-plan-card { display:flex; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; margin-bottom:12px; }
.id-plan-rail { width:4px; background:var(--green); flex-shrink:0; }
.id-plan-main { flex:1; min-width:0; padding:18px 20px 14px; }
.id-plan-top { display:flex; align-items:flex-start; gap:14px; }
.id-plan-ico { width:40px; height:40px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.id-plan-info { flex:1; min-width:0; }
.id-plan-name { font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); line-height:1.2; }
.id-plan-meta { font-size:12.5px; color:var(--text-3); margin-top:3px; }
.id-plan-chips { display:flex; gap:7px; flex-wrap:wrap; margin-top:10px; }
.id-chip { display:inline-flex; align-items:center; gap:4px; font-size:9.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:3px 9px; border-radius:var(--radius-full); }
.id-chip.chip-green { background:var(--green-light); color:var(--green-dark); }
.id-chip.chip-blue { background:rgba(80,120,190,.12); color:#4a6ba8; }
.id-chip.chip-neutral { background:var(--surface-3); color:var(--text-3); }
.id-plan-banner { margin-top:14px; padding:11px 14px; background:var(--badge-bg-gold); border:1px solid rgba(192,154,82,.22); border-radius:var(--radius); font-size:12px; color:var(--text-2); line-height:1.5; }
.id-plan-foot { display:flex; align-items:center; gap:8px; margin-top:14px; }
.id-icon-gold { background:var(--gold-dark) !important; color:var(--text-inverted) !important; border-color:var(--gold-dark) !important; }

/* INFO BANNER */
.id-info-banner { display:flex; align-items:flex-start; gap:10px; font-size:12.5px; color:var(--text-2); line-height:1.55; background:rgba(80,120,190,.06); border:1px solid rgba(80,120,190,.18); border-radius:var(--radius); padding:13px 16px; margin-bottom:18px; }
.id-info-banner svg { color:#5078be; flex-shrink:0; margin-top:1px; }

/* REFERENCE GRID */
.id-ref-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
.id-ref-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); padding:18px 20px; display:flex; flex-direction:column; transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease; }
.id-ref-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-sm); border-color:var(--surface-4); }
.id-ref-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.id-ref-ico { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.id-ref-ico :deep(svg) { width:18px; height:18px; }
.id-ref-tag { font-size:9px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; padding:3px 9px; border-radius:var(--radius-full); background:var(--green-light); color:var(--green-dark); }
.id-ref-title { font-family:var(--font-serif); font-size:15.5px; font-weight:700; color:var(--text); line-height:1.3; margin-bottom:8px; }
.id-ref-desc { font-size:12.5px; color:var(--text-3); line-height:1.6; flex:1; margin-bottom:16px; }
.id-ref-foot { display:flex; align-items:center; justify-content:space-between; gap:10px; }
.id-ref-actions { display:flex; gap:8px; }
.id-ref-ver { font-size:10px; font-weight:600; padding:3px 9px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); }

/* EMPTY STATE */
.id-empty { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); padding:60px 24px; text-align:center; }
.id-empty-ico { width:54px; height:54px; border-radius:50%; background:var(--badge-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; }
.id-empty-title { font-family:var(--font-serif); font-size:19px; font-weight:600; color:var(--text); margin-bottom:6px; }
.id-empty-sub { font-size:13px; color:var(--text-4); line-height:1.6; max-width:480px; margin:0 auto; }

@media (max-width:1000px) { .id-ref-grid { grid-template-columns:1fr 1fr; } }
@media (max-width:900px) { .id-stats { grid-template-columns:1fr 1fr; } .id-header { flex-direction:column; } }
@media (max-width:640px) { .id-ref-grid { grid-template-columns:1fr; } }
</style>
