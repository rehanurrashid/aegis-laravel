<template>
  <AppLayout :user="user" portal="business_partner" activePage="proposals" pageTitle="Proposals">

    <!-- ═══ HEADER ═══ -->
    <div class="pr-header">
      <div class="pr-header-l">
        <h1 class="pr-title">Proposals</h1>
        <div class="pr-subtitle">Track every proposal you've sent to Practitioner Partners.</div>
        <div class="pr-tagline"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Agency Partner</div>
      </div>
      <div class="pr-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <a href="/business-partner/find-jobs" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg> Find Jobs</a>
      </div>
    </div>

    <!-- ═══ STATS ═══ -->
    <div class="pr-stats">
      <div class="pr-stat"><div class="pr-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div><div><div class="pr-stat-num">5</div><div class="pr-stat-label">Total Submitted</div></div></div>
      <div class="pr-stat"><div class="pr-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="pr-stat-num">2</div><div class="pr-stat-label">Pending Decision</div></div></div>
      <div class="pr-stat"><div class="pr-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div><div><div class="pr-stat-num">1</div><div class="pr-stat-label">Accepted</div></div></div>
      <div class="pr-stat"><div class="pr-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="pr-stat-num">$2,057</div><div class="pr-stat-label">Avg Proposed Value</div></div></div>
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="pr-tabs">
      <button v-for="t in tabs" :key="t.key" type="button" class="pr-tab" :class="{ active: activeTab === t.key }" @click="activeTab = t.key">{{ t.label }} <span v-if="t.count" class="pr-tab-count">{{ t.count }}</span></button>
    </div>

    <!-- ═══ FILTER BAR ═══ -->
    <div class="pr-filterbar">
      <div class="pr-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" placeholder="Search by job title or practitioner..." />
      </div>
      <select class="form-select"><option>All Categories</option><option>Medical Billing</option><option>Technology</option><option>Compliance</option></select>
      <select class="form-select"><option>All Engagement</option><option>Fixed Price</option><option>Hourly</option></select>
      <select class="form-select"><option>All Time</option><option>This Week</option><option>This Month</option></select>
      <select class="form-select"><option>Sort: Newest</option><option>Sort: Oldest</option><option>Sort: Value</option></select>
    </div>

    <!-- ═══ SAVED JOBS TAB ═══ -->
    <div v-if="activeTab === 'saved'" class="pr-list">
      <div v-for="s in savedJobs" :key="s.id" class="pr-card rail-neutral">
        <div class="pr-card-body">
          <div class="pr-card-top">
            <span class="pr-av">{{ s.initials }}</span>
            <div class="pr-card-who"><div class="pr-card-name">{{ s.name }}</div><div class="pr-card-org">{{ s.org }}</div></div>
            <span class="pr-date">{{ s.date }}</span>
          </div>
          <div class="pr-card-jobline"><span class="pr-card-jobtitle">{{ s.title }}</span><span class="pr-cat">{{ s.category }}</span></div>
          <div class="pr-card-money"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> <strong>{{ s.amount }}</strong> <span class="pr-money-sub">{{ s.engagement }}</span></div>
        </div>
        <div class="pr-card-foot">
          <div class="pr-foot-l"><button type="button" class="btn-icon" @click="openJob(s)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
          <button type="button" class="btn btn-gold btn-sm" @click="openJob(s)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Send Proposal</button>
        </div>
      </div>
    </div>

    <!-- ═══ PROPOSAL LIST ═══ -->
    <div v-else class="pr-list">
      <div v-for="p in filteredProposals" :key="p.id" class="pr-card" :class="'rail-' + statusColor(p.status)">
        <div class="pr-card-body">
          <div class="pr-card-top">
            <span class="pr-av">{{ p.initials }}</span>
            <div class="pr-card-who"><div class="pr-card-name">{{ p.name }}</div><div class="pr-card-org">{{ p.org }}</div></div>
            <div class="pr-card-tr">
              <span class="pr-badge" :class="'st-' + statusColor(p.status)">{{ p.statusLabel }}</span>
              <span class="pr-date">{{ p.date }}</span>
            </div>
          </div>
          <div class="pr-card-jobline"><span class="pr-card-jobtitle">{{ p.title }}</span><span class="pr-cat">{{ p.category }}</span></div>
          <p class="pr-card-desc">{{ p.coverLetter }}</p>
          <div class="pr-card-money"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> <strong>{{ p.amount }}</strong> <span class="pr-money-sub">{{ p.rateLabel }} · {{ p.engagement }}</span></div>
          <div class="pr-card-meta"><span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> {{ p.timeline }}</span><span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg> {{ p.proposalsCount }} proposals</span></div>
        </div>
        <div class="pr-card-foot">
          <div class="pr-foot-l">
            <button type="button" class="btn-icon" title="Job details" @click="openJob(p)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></button>
            <button type="button" class="btn-icon" title="View proposal" @click="openProposal(p)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
          </div>
          <div class="pr-foot-r" v-if="p.status === 'submitted' || p.status === 'review'">
            <button type="button" class="btn-icon" title="Edit proposal" @click="openProposal(p)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></button>
            <button type="button" class="btn-icon danger" title="Withdraw" @click="openWithdraw(p)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
          </div>
        </div>
      </div>
      <div v-if="!filteredProposals.length" class="pr-empty">No proposals in this view.</div>
    </div>

    <!-- ═══ JOB DETAILS MODAL ═══ -->
    <Modal v-model="showJob" title="Job Details" size="lg">
      <div class="pr-det-head">
        <div class="pr-det-title">{{ jobSel.title }}</div>
        <div class="pr-det-amt-wrap"><span class="pr-det-amt">{{ jobSel.amount }}</span><span class="pr-det-engage">{{ jobSel.engagement }}</span></div>
      </div>
      <div class="pr-det-sub">{{ jobSel.name }} · {{ jobSel.org }}</div>

      <div class="pr-modal-section">Job Overview</div>
      <div class="pr-det-overview">
        <div class="pr-det-ov"><div class="pr-det-ov-label">Location</div><div class="pr-det-ov-val">remote</div></div>
        <div class="pr-det-ov"><div class="pr-det-ov-label">Engagement</div><div class="pr-det-ov-val">{{ jobSel.engagement }}</div></div>
        <div class="pr-det-ov"><div class="pr-det-ov-label">Proposals</div><div class="pr-det-ov-val">{{ jobSel.proposalsCount }} received</div></div>
        <div class="pr-det-ov"><div class="pr-det-ov-label">Posted</div><div class="pr-det-ov-val">{{ jobSel.posted }}</div></div>
      </div>

      <div class="pr-modal-section">Full Description</div>
      <p class="pr-det-desc">{{ jobSel.fullDesc }}</p>

      <div class="pr-modal-section">Skills Required</div>
      <div class="pr-pills"><span class="pr-cat" v-if="jobSel.category">{{ jobSel.category }}</span></div>

      <div class="pr-modal-section">About the Provider</div>
      <div class="pr-det-provider">
        <span class="pr-av lg">{{ jobSel.initials }}</span>
        <div>
          <div class="pr-det-prov-name">{{ jobSel.name }} · {{ jobSel.org }}</div>
          <div class="pr-det-prov-meta">{{ jobSel.org }} · {{ jobSel.providerLoc }}</div>
          <div class="pr-det-prov-rating">☆ — rating</div>
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showJob = false">Close</button>
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg> Save Job</button>
        <button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Send Proposal</button>
      </template>
    </Modal>

    <!-- ═══ PROPOSAL DETAILS MODAL ═══ -->
    <Modal v-model="showProp" title="Proposal Details" size="lg">
      <div class="pr-pd-title">{{ propSel.title }}</div>
      <div class="pr-pd-sub">{{ propSel.name }}</div>
      <div class="pr-pd-table">
        <div class="pr-pd-row"><div class="pr-pd-k">Status</div><div class="pr-pd-v">{{ propSel.statusLabel }}</div></div>
        <div class="pr-pd-row"><div class="pr-pd-k">Submitted</div><div class="pr-pd-v">{{ propSel.date }}</div></div>
        <div class="pr-pd-row"><div class="pr-pd-k">Proposed</div><div class="pr-pd-v">{{ propSel.amount }} · {{ propSel.rateLabel }}</div></div>
        <div class="pr-pd-row"><div class="pr-pd-k">Engagement</div><div class="pr-pd-v">{{ propSel.engagement }}</div></div>
        <div class="pr-pd-row"><div class="pr-pd-k">Timeline</div><div class="pr-pd-v">{{ propSel.timeline }}</div></div>
      </div>
      <div class="pr-modal-section">Cover Letter</div>
      <p class="pr-det-desc">{{ propSel.coverLetter }}</p>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showProp = false">Close</button>
      </template>
    </Modal>

    <!-- ═══ WITHDRAW MODAL ═══ -->
    <Modal v-model="showWithdraw" title="Withdraw proposal" size="sm">
      <p class="pr-withdraw-text">Withdraw this proposal? The practitioner will be notified and this cannot be undone.</p>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showWithdraw = false">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" @click="showWithdraw = false">Withdraw</button>
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
const activeTab = ref('submitted');

const tabs = [
  { key: 'submitted', label: 'Submitted', count: 1 },
  { key: 'review', label: 'Under Review', count: 1 },
  { key: 'accepted', label: 'Accepted', count: 1 },
  { key: 'declined', label: 'Declined', count: 1 },
  { key: 'withdrawn', label: 'Withdrawn', count: 1 },
  { key: 'saved', label: 'Saved Jobs', count: 4 },
];

function statusColor(s) { return s === 'submitted' ? 'blue' : s === 'review' ? 'gold' : s === 'accepted' ? 'green' : s === 'declined' ? 'red' : 'neutral'; }

const proposals = [
  { id: 1, initials: 'SJ', name: 'Dr. Sarah Johnson', org: 'Lotus Psychology Group', status: 'submitted', statusLabel: 'Submitted', date: 'Jan 18', title: 'Medical Billing Specialist — Psychiatry Practice', category: 'Medical Billing', coverLetter: 'Acme Practice Services has 8 years billing solo therapy practices.', amount: '$2,500', rateLabel: 'fixed price', engagement: 'Fixed Price', timeline: '2 weeks onboarding', proposalsCount: 18, posted: 'Jun 1', providerLoc: 'San Francisco, CA', fullDesc: 'Seeking an experienced medical billing specialist to handle end-to-end billing for a busy psychiatry practice in San Francisco. Responsibilities include claims submission, follow-up on denials, credentialing support, and monthly reporting. Must be familiar with mental health billing codes (CPT 90791, 90837, etc.) and have experience with SimplePractice or Kareo. HIPAA BAA required.' },
  { id: 2, initials: 'TN', name: 'Dr. Thomas Nguyen', org: 'Midtown Internal Medicine', status: 'review', statusLabel: 'Under Review', date: 'Jan 15', title: 'Patient Intake Automation & CRM Setup', category: 'Technology', coverLetter: 'We specialize in SimplePractice automation and CRM integrations for medical practices.', amount: '$2,600', rateLabel: 'fixed price', engagement: 'Fixed Price', timeline: '3 weeks', proposalsCount: 7, posted: 'Jun 11', providerLoc: 'Midtown, NY', fullDesc: 'Internal medicine practice in Manhattan needs help automating the patient intake workflow. Scope: set up intake forms in SimplePractice, configure automated appointment reminders, and build a lightweight CRM pipeline.' },
  { id: 3, initials: 'JO', name: 'James Okafor', org: 'Okafor LMFT Practice', status: 'accepted', statusLabel: 'Accepted', date: 'Jan 9', title: 'HIPAA Compliance Audit', category: 'Compliance', coverLetter: 'Our team has completed 40+ HIPAA audits for small behavioral health practices.', amount: '$1,200', rateLabel: 'fixed price', engagement: 'Fixed Price', timeline: '2 weeks', proposalsCount: 2, posted: 'Jun 13', providerLoc: 'Bronx, NY', fullDesc: 'Need a thorough HIPAA compliance audit for a small LMFT practice. Deliverables: gap analysis report, policy recommendations, updated Notice of Privacy Practices, and a staff training session.' },
  { id: 4, initials: 'AT', name: 'Dr. Anna Thompson', org: 'Anna Thompson Practice', status: 'declined', statusLabel: 'Declined', date: 'Jan 4', title: 'Practice Website Redesign & SEO', category: 'Marketing', coverLetter: 'We build HIPAA-friendly practice websites with healthcare-focused SEO.', amount: '$4,000', rateLabel: 'fixed price', engagement: 'Fixed Price', timeline: '6 weeks', proposalsCount: 7, posted: 'Jun 8', providerLoc: 'Brooklyn, NY', fullDesc: 'Looking for a web designer and SEO specialist to redesign our child and adolescent psychiatry practice website. Must be familiar with healthcare compliance (HIPAA-friendly contact forms).' },
  { id: 5, initials: 'MT', name: 'Dr. Michael Torres', org: 'Bay Area Psychiatry Associates', status: 'withdrawn', statusLabel: 'Withdrawn', date: 'Dec 28', title: 'Medical Billing Specialist Needed', category: 'Medical Billing', coverLetter: 'Withdrawn — capacity reallocated to another engagement.', amount: '$1,100', rateLabel: 'fixed price', engagement: 'Fixed Price', timeline: '4 weeks', proposalsCount: 4, posted: 'Jun 10', providerLoc: 'Oakland, CA', fullDesc: 'Seeking a billing specialist for a busy psychiatry practice in Oakland. Must have experience with mental health CPT codes, insurance credentialing, and denial management.' },
];

const savedJobs = [
  { id: 's1', initials: 'JO', name: 'James Okafor', org: 'Okafor LMFT Practice', date: 'Jun 13', title: 'HIPAA Compliance Audit', category: 'Compliance', amount: '$1,200', engagement: 'Fixed Price', proposalsCount: 2, posted: 'Jun 13', providerLoc: 'Bronx, NY', fullDesc: 'Need a thorough HIPAA compliance audit for a small LMFT practice. Deliverables: gap analysis report, policy recommendations, updated Notice of Privacy Practices.' },
  { id: 's2', initials: 'EV', name: 'Elena Vasquez', org: 'Elena Vasquez Practice', date: 'Jun 11', title: 'EHR Setup & Staff Training', category: 'Technology', amount: '$2,200', engagement: 'Fixed Price', proposalsCount: 5, posted: 'Jun 11', providerLoc: 'Manhattan, NY', fullDesc: 'Somatic therapy practice needs help migrating from paper records to a cloud EHR, SimplePractice preferred.' },
  { id: 's3', initials: 'DV', name: 'Dr. Diana Vasquez', org: 'Dr. Diana Vasquez Practice', date: 'Jun 10', title: 'EMDR Practice - Superbill & Out-of-Network Billing', category: 'Billing', amount: '$65/hr', engagement: 'Hourly', proposalsCount: 3, posted: 'Jun 10', providerLoc: 'Houston, TX', fullDesc: 'Clinical psychologist in Houston with a full out-of-network practice. Need a billing specialist to create professional superbill templates.' },
  { id: 's4', initials: 'RM', name: 'Dr. Rachel Moore', org: 'Manhattan Medical Group', date: 'Jun 6', title: 'Group Practice Legal Setup - PLLC & Partnership', category: 'Legal', amount: '$3,500', engagement: 'Fixed Price', proposalsCount: 2, posted: 'Jun 6', providerLoc: 'New York, NY', fullDesc: 'Three therapists forming a group PLLC in New York. Need a healthcare attorney to draft PLLC operating agreement and revenue-sharing structure.' },
];

const filteredProposals = computed(() => {
  return proposals.filter(p => {
    if (p.status !== activeTab.value) return false;
    if (search.value) {
      const q = search.value.toLowerCase();
      if (!p.title.toLowerCase().includes(q) && !p.name.toLowerCase().includes(q)) return false;
    }
    return true;
  });
});

// ── modals ──
const showJob = ref(false);
const jobSel = ref({});
function openJob(item) { jobSel.value = item; showJob.value = true; }

const showProp = ref(false);
const propSel = ref({});
function openProposal(item) { propSel.value = item; showProp.value = true; }

const showWithdraw = ref(false);
function openWithdraw(item) { propSel.value = item; showWithdraw.value = true; }
</script>

<style scoped>
.pr-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:30px 34px; margin-bottom:18px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.pr-title { font-family:var(--font-serif); font-size:34px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.pr-subtitle { font-size:13.5px; color:var(--text-3); margin-top:9px; }
.pr-tagline { display:inline-flex; align-items:center; gap:6px; font-size:12px; color:var(--text-4); margin-top:11px; }
.pr-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; }

.pr-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
.pr-stat { display:flex; align-items:center; gap:14px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px 22px; box-shadow:var(--shadow-xs); }
.pr-stat-ico { width:36px; height:36px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.pr-stat-num { font-family:var(--font-serif); font-size:24px; font-weight:700; color:var(--text); line-height:1; }
.pr-stat-label { font-size:11.5px; font-weight:500; color:var(--text-3); margin-top:6px; }

.pr-tabs { display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:16px; }
.pr-tab { display:inline-flex; align-items:center; gap:7px; padding:8px 16px; font-size:13px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; }
.pr-tab:hover { color:var(--gold-dark); }
.pr-tab.active { background:var(--gold-dark); color:var(--text-inverted); }
.pr-tab-count { display:inline-flex; align-items:center; justify-content:center; min-width:18px; height:18px; padding:0 5px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); font-size:10.5px; font-weight:700; }
.pr-tab.active .pr-tab-count { background:rgba(255,255,255,.25); color:var(--text-inverted); }

.pr-filterbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.pr-search { display:flex; align-items:center; gap:8px; flex:1; min-width:240px; padding:10px 15px; background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius-full); color:var(--text-4); }
.pr-search input { flex:1; border:none; outline:none; background:none; font-size:13px; color:var(--text); }
.pr-filterbar .form-select { width:auto; min-width:130px; border-radius:var(--radius-full); }

.pr-list { display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:start; }
.pr-card { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--border-dark); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; display:flex; flex-direction:column; transition:transform .18s ease,box-shadow .18s ease; }
.pr-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-sm); }
.pr-card.rail-blue { border-left-color:#3b6fb5; }
.pr-card.rail-gold { border-left-color:var(--gold-dark); }
.pr-card.rail-green { border-left-color:var(--green); }
.pr-card.rail-red { border-left-color:var(--red); }
.pr-card.rail-neutral { border-left-color:var(--border-dark); }
.pr-card-body { padding:18px 20px; flex:1; }
.pr-card-top { display:flex; align-items:flex-start; gap:11px; }
.pr-av { width:36px; height:36px; border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:12px; flex-shrink:0; }
.pr-av.lg { width:44px; height:44px; font-size:15px; }
.pr-card-who { flex:1; min-width:0; }
.pr-card-name { font-size:13px; font-weight:700; color:var(--text); }
.pr-card-org { font-size:12px; color:var(--text-3); margin-top:2px; }
.pr-card-tr { display:flex; flex-direction:column; align-items:flex-end; gap:5px; flex-shrink:0; }
.pr-badge { font-size:9.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; padding:3px 10px; border-radius:var(--radius-full); }
.pr-badge.st-blue { background:#e3edf9; color:#3b6fb5; }
.pr-badge.st-gold { background:var(--badge-bg-gold); color:var(--gold-dark); }
.pr-badge.st-green { background:var(--green-light); color:var(--green-dark); }
.pr-badge.st-red { background:var(--red-light); color:var(--red); }
.pr-badge.st-neutral { background:var(--surface-3); color:var(--text-3); }
.pr-date { font-size:11px; color:var(--text-4); }
.pr-card-jobline { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:14px; }
.pr-card-jobtitle { font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); }
.pr-cat { font-size:9.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:3px 9px; border-radius:var(--radius-full); background:#e3edf9; color:#3b6fb5; }
.pr-card-desc { font-size:12.5px; color:var(--text-3); line-height:1.6; margin:10px 0 0; }
.pr-card-money { display:flex; align-items:center; gap:7px; margin-top:13px; font-size:12px; color:var(--text-4); }
.pr-card-money svg { color:var(--gold-dark); }
.pr-card-money strong { font-family:var(--font-serif); font-size:18px; font-weight:700; color:var(--text); }
.pr-money-sub { font-size:12px; color:var(--text-3); }
.pr-card-meta { display:flex; align-items:center; gap:18px; flex-wrap:wrap; margin-top:11px; }
.pr-card-meta span { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; color:var(--text-3); }
.pr-card-meta svg { color:var(--text-4); }
.pr-card-foot { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 20px; border-top:1px solid var(--border); background:var(--surface-2); }
.pr-foot-l, .pr-foot-r { display:flex; align-items:center; gap:6px; }
.btn-icon.danger:hover { color:var(--red); border-color:var(--red); }
.pr-empty { grid-column:1/-1; text-align:center; padding:50px 20px; color:var(--text-4); font-size:13px; }

/* shared modal sections */
.pr-modal-section { font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--gold-dark); margin:20px 0 12px; }
.pr-det-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; }
.pr-det-title { font-family:var(--font-serif); font-size:20px; font-weight:600; color:var(--text); line-height:1.2; }
.pr-det-amt-wrap { display:flex; flex-direction:column; align-items:flex-end; gap:4px; flex-shrink:0; }
.pr-det-amt { font-family:var(--font-serif); font-size:20px; font-weight:700; color:var(--text); }
.pr-det-engage { font-size:9.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--gold-dark); background:var(--badge-bg-gold); padding:3px 9px; border-radius:var(--radius-full); }
.pr-det-sub { font-size:13px; color:var(--text-3); margin-top:5px; }
.pr-det-overview { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; }
.pr-det-ov { border:1px solid var(--border); border-radius:var(--radius); padding:12px 14px; background:var(--surface-2); }
.pr-det-ov-label { font-size:9.5px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--text-4); }
.pr-det-ov-val { font-size:13px; font-weight:600; color:var(--text); margin-top:5px; }
.pr-det-desc { font-size:13px; color:var(--text-2); line-height:1.7; margin:0; }
.pr-pills { display:flex; gap:7px; flex-wrap:wrap; min-height:4px; }
.pr-det-provider { display:flex; align-items:center; gap:14px; border:1px solid var(--border); border-radius:var(--radius-lg); padding:14px 18px; background:var(--surface-2); }
.pr-det-prov-name { font-size:14px; font-weight:700; color:var(--text); }
.pr-det-prov-meta { font-size:12px; color:var(--text-3); margin-top:3px; }
.pr-det-prov-rating { font-size:12px; color:var(--text-4); margin-top:4px; }

/* proposal details table */
.pr-pd-title { font-family:var(--font-serif); font-size:19px; font-weight:600; color:var(--text); }
.pr-pd-sub { font-size:13px; color:var(--text-3); margin-top:4px; margin-bottom:8px; }
.pr-pd-table { border-top:1px solid var(--border); }
.pr-pd-row { display:grid; grid-template-columns:160px 1fr; gap:16px; padding:14px 2px; border-bottom:1px solid var(--border); }
.pr-pd-k { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); }
.pr-pd-v { font-size:13.5px; color:var(--text); }

.pr-withdraw-text { font-size:13.5px; color:var(--text-2); line-height:1.6; margin:0; }

@media (max-width:1000px) { .pr-stats { grid-template-columns:repeat(2,1fr); } .pr-list { grid-template-columns:1fr; } .pr-det-overview { grid-template-columns:1fr 1fr; } }
@media (max-width:700px) { .pr-header { flex-direction:column; } .pr-stats { grid-template-columns:1fr; } .pr-pd-row { grid-template-columns:1fr; gap:4px; } }
</style>
