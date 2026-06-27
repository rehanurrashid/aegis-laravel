<template>
  <AppLayout :user="user" portal="business_partner" activePage="find-jobs" pageTitle="Find Jobs">

    <!-- ═══ HEADER ═══ -->
    <div class="fj-header">
      <div class="fj-header-l">
        <h1 class="fj-title">Find Jobs</h1>
        <div class="fj-subtitle">Healthcare practitioners looking for agency services.</div>
      </div>
      <div class="fj-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg> Saved <span class="fj-badge">3</span></button>
        <button type="button" class="btn btn-primary btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> My Proposals</button>
      </div>
    </div>

    <!-- ═══ STATS ═══ -->
    <div class="fj-stats">
      <div class="fj-stat"><div class="fj-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div><div><div class="fj-stat-num">18</div><div class="fj-stat-label">Open Matches</div></div></div>
      <div class="fj-stat"><div class="fj-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></div><div><div class="fj-stat-num">3</div><div class="fj-stat-label">Saved</div></div></div>
      <div class="fj-stat"><div class="fj-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="fj-stat-num">$1,755</div><div class="fj-stat-label">Avg Budget</div></div></div>
      <div class="fj-stat"><div class="fj-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="fj-stat-num">2.4d</div><div class="fj-stat-label">Avg Response</div></div></div>
    </div>

    <!-- ═══ SAVED PIPELINE ═══ -->
    <div class="fj-section-eyebrow">Your Pipeline</div>
    <div class="fj-saved-head">Saved Jobs</div>
    <div class="fj-saved-strip">
      <div v-for="s in savedJobs" :key="s.title" class="fj-saved-card">
        <div class="fj-saved-title">{{ s.title }}</div>
        <div class="fj-saved-amt">{{ s.amount }}</div>
        <div class="fj-saved-meta">{{ s.meta }}</div>
        <div class="fj-saved-foot">
          <button type="button" class="btn-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
          <button type="button" class="btn-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></button>
          <button v-if="s.submitted" type="button" class="btn btn-outline btn-xs fj-saved-cta" disabled><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Submitted</button>
          <button v-else type="button" class="btn btn-gold btn-xs fj-saved-cta" @click="openProposal(s)">Propose</button>
        </div>
      </div>
    </div>

    <!-- ═══ FILTER BAR ═══ -->
    <div class="fj-filterbar">
      <div class="fj-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" placeholder="Search jobs..." />
      </div>
      <select class="form-select"><option>All Categories</option><option>Billing</option><option>Technology</option><option>Marketing</option><option>Compliance</option><option>Admin</option></select>
      <select class="form-select"><option>All Engagements</option><option>Fixed Price</option><option>Hourly</option><option>Per Month</option></select>
      <select class="form-select"><option>Any Location</option><option>Remote</option><option>On-site</option></select>
      <select class="form-select"><option>Newest First</option><option>Budget: High to Low</option><option>Fewest Proposals</option></select>
      <button type="button" class="fj-saved-toggle" :class="{ on: savedOnly }" @click="savedOnly = !savedOnly"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg> Saved only</button>
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="fj-tabs">
      <button v-for="t in tabs" :key="t.key" type="button" class="fj-tab" :class="{ active: activeTab === t.key }" @click="activeTab = t.key">{{ t.label }}</button>
    </div>

    <div class="fj-count">{{ filteredJobs.length }} jobs</div>

    <!-- ═══ JOB GRID ═══ -->
    <div class="fj-grid">
      <div v-for="j in filteredJobs" :key="j.id" class="fj-card">
        <div class="fj-card-top">
          <div class="fj-card-who">
            <span class="fj-av">{{ j.initials }}</span>
            <div class="fj-who-info"><div class="fj-who-name">{{ j.name }}</div><div class="fj-who-loc">{{ j.org }} - {{ j.location }}</div></div>
          </div>
          <span class="fj-card-date">{{ j.posted }}</span>
        </div>
        <div class="fj-card-title">{{ j.title }}</div>
        <span class="fj-cat">{{ j.category }}</span>
        <p class="fj-card-desc">{{ j.desc }} <a href="#" @click.prevent="openDetails(j)">more</a></p>
        <div class="fj-card-amt"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> <strong>{{ j.amount }}</strong> <span class="fj-rate">{{ j.rate }}</span></div>
        <div class="fj-card-props"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg> {{ j.proposals }} proposals</div>
        <div class="fj-card-foot">
          <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></button>
          <button type="button" class="btn btn-outline btn-sm fj-detail-btn" @click="openDetails(j)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View Details</button>
          <button v-if="j.submitted" type="button" class="fj-submitted"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Submitted</button>
          <button v-else type="button" class="btn btn-gold btn-sm" @click="openProposal(j)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Send Proposal</button>
        </div>
      </div>
    </div>

    <!-- ═══ SEND PROPOSAL MODAL ═══ -->
    <Modal v-model="showProposal" title="Send Proposal" size="lg">
      <div class="fj-modal-jobcard">
        <div class="fj-modal-job-title">{{ proposalJob.title }}</div>
        <div class="fj-modal-job-sub">{{ proposalJob.name }} - {{ proposalJob.location }}</div>
      </div>

      <div class="fj-modal-section">Rate &amp; Timeline</div>
      <div class="fj-modal-row4">
        <div class="ep-field"><label class="ep-label">Amount <span class="ep-req">*</span></label><div class="ep-input-prefix"><span>$</span><input class="ep-input" v-model="prop.amount" /></div></div>
        <div class="ep-field"><label class="ep-label">Rate Type</label><select class="form-select" v-model="prop.rateType"><option>Per Month</option><option>Fixed Price</option><option>Hourly</option><option>Per Milestone</option></select></div>
        <div class="ep-field"><label class="ep-label">Start Date</label><input type="date" class="ep-input" v-model="prop.start" /></div>
        <div class="ep-field"><label class="ep-label">Duration</label><select class="form-select" v-model="prop.duration"><option>Ongoing</option><option>Less than 1 month</option><option>1–3 months</option><option>3–6 months</option><option>6+ months</option></select></div>
      </div>

      <div class="fj-modal-section">Cover Letter</div>
      <div class="ep-field"><label class="ep-label">Why are you the right fit? <span class="ep-req">*</span></label><textarea class="ep-input ep-textarea" rows="4" v-model="prop.cover" placeholder="Introduce yourself, highlight relevant experience, and explain how you'll deliver results for this provider..."></textarea></div>

      <div class="fj-modal-section">Team Assignment</div>
      <div class="ep-field"><label class="ep-label">Assigned Team Member</label><select class="form-select" v-model="prop.team"><option value="">Select team member (optional)</option><option>Riley Chen</option><option>Marcus T.</option><option>Sam Lee</option><option>Jordan K.</option></select></div>

      <div class="fj-modal-section">Attachment <span class="fj-modal-optional">(Optional)</span></div>
      <div class="ep-field"><label class="ep-label">Portfolio / Case Study</label>
        <div class="fj-upload"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg><div class="fj-upload-text"><strong>Click to upload</strong> or drag &amp; drop</div><div class="fj-upload-hint">PDF, DOC, JPG, PNG · up to 10 MB</div></div>
      </div>

      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showProposal = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showProposal = false"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Submit Proposal</button>
      </template>
    </Modal>

    <!-- ═══ JOB DETAILS MODAL ═══ -->
    <Modal v-model="showDetails" title="Job Details" size="lg">
      <div class="fj-det-head">
        <div class="fj-det-title">{{ detailsJob.title }}</div>
        <div class="fj-det-amt-wrap"><span class="fj-det-amt">{{ detailsJob.amount }}</span><span class="fj-det-engage">{{ detailsJob.rate === 'Hourly' ? 'Hourly' : 'Fixed Price' }}</span></div>
      </div>
      <div class="fj-det-sub">{{ detailsJob.name }} - {{ detailsJob.location }}</div>

      <div class="fj-modal-section">Job Overview</div>
      <div class="fj-det-overview">
        <div class="fj-det-ov"><div class="fj-det-ov-label">Location</div><div class="fj-det-ov-val">{{ detailsJob.remote }}</div></div>
        <div class="fj-det-ov"><div class="fj-det-ov-label">Engagement</div><div class="fj-det-ov-val">{{ detailsJob.rate === 'Hourly' ? 'Hourly' : 'Fixed Price' }}</div></div>
        <div class="fj-det-ov"><div class="fj-det-ov-label">Proposals</div><div class="fj-det-ov-val">{{ detailsJob.proposals }} received</div></div>
        <div class="fj-det-ov"><div class="fj-det-ov-label">Posted</div><div class="fj-det-ov-val">{{ detailsJob.posted }}</div></div>
      </div>

      <div class="fj-modal-section">Full Description</div>
      <p class="fj-det-desc">{{ detailsJob.fullDesc || detailsJob.desc }}</p>

      <div class="fj-modal-section">Skills Required</div>
      <div class="fj-pills"><span class="fj-cat">{{ detailsJob.category }}</span></div>

      <div class="fj-modal-section">About the Provider</div>
      <div class="fj-det-provider">
        <span class="fj-av lg">{{ detailsJob.initials }}</span>
        <div class="fj-det-prov-info">
          <div class="fj-det-prov-name">{{ detailsJob.name }} - {{ detailsJob.location }}</div>
          <div class="fj-det-prov-meta">{{ detailsJob.location }} · {{ detailsJob.org }}</div>
          <div class="fj-det-prov-rating">☆ — rating</div>
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showDetails = false">Close</button>
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg> Save Job</button>
        <button type="button" class="btn btn-gold btn-sm" @click="detailsToProposal"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Send Proposal</button>
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
const savedOnly = ref(false);

const tabs = [
  { key: 'all', label: 'All' },
  { key: 'urgent', label: 'Urgent' },
  { key: 'new', label: 'New This Week' },
  { key: 'billing', label: 'Billing' },
  { key: 'it', label: 'IT & EHR' },
  { key: 'marketing', label: 'Marketing' },
  { key: 'admin', label: 'Admin' },
];

const savedJobs = [
  { title: 'HIPAA Compliance Audit', amount: '$1,200', meta: 'Jun 13 · 2 proposals', submitted: false },
  { title: 'HIPAA-Compliant IT Support Engineer', amount: '$97/hr', meta: 'Jun 2 · 24 proposals', submitted: true },
  { title: 'Medical Billing Specialist — Psychiatry Practice', amount: '$2,500', meta: 'Jun 2 · 18 proposals', submitted: true },
];

const jobs = [
  { id: 1, name: 'Dr. Priya Mehta', org: 'Dr. Priya Mehta Practice', location: 'Manhattan, NY', posted: 'Jun 13', title: 'Practice Operations Consultant', category: 'Consulting', desc: 'Functional medicine practice seeking a practice operations consultant to create SOPs for intake, billing, and follow-up workflows, evaluate current EHR vs switching, build a flex sc…', amount: '$180/hr', rate: 'Hourly', proposals: 2, urgent: true, new: true },
  { id: 2, name: 'James Okafor', org: 'James Okafor Practice', location: 'Bronx, NY', posted: 'Jun 13', title: 'HIPAA Compliance Audit', category: 'Compliance', desc: 'Need a thorough HIPAA compliance audit for a small LMFT practice. Deliverables: gap analysis report, policy recommendations, updated Notice of Privacy Practices, and a staff train…', amount: '$1,200', rate: 'Fixed Price', proposals: 2, submitted: true, new: true },
  { id: 3, name: 'Elena Vasquez', org: 'Elena Vasquez Practice', location: 'Manhattan, NY', posted: 'Jun 11', title: 'EHR Setup & Staff Training', category: 'Technology', desc: 'Somatic therapy practice needs help migrating from paper records to a cloud EHR, SimplePractice preferred. Scope includes data migration from 80 active client files, workflow setup…', amount: '$2,200', rate: 'Fixed Price', proposals: 5 },
  { id: 4, name: 'Dr. Thomas Nguyen', org: 'Midtown', location: 'Midtown, NY', posted: 'Jun 11', title: 'Patient Intake Automation & CRM Setup', category: 'Technology', desc: 'Internal medicine practice in Manhattan needs help automating the patient intake workflow. Scope: set up intake forms in SimplePractice, configure automated appointment reminders (…', amount: '$2,600', rate: 'Fixed Price', proposals: 7 },
  { id: 5, name: 'Dr. Diana Vasquez', org: 'Dr. Diana Vasquez Practice', location: 'Houston, TX', posted: 'Jun 10', title: 'EMDR Practice - Superbill & Out-of-Network Billing', category: 'Billing', desc: 'Clinical psychologist in Houston with a full out-of-network practice. Need a billing specialist to create professional superbill templates, set up a reimbursement education packet…', amount: '$65/hr', rate: 'Hourly', proposals: 3 },
  { id: 6, name: 'Dr. Michael Torres', org: 'Bay Area Psychiatry Associates', location: 'Oakland, CA', posted: 'Jun 10', title: 'Medical Billing Specialist Needed', category: 'Billing', desc: 'Seeking a billing specialist for a busy psychiatry practice in Oakland. Must have experience with mental health CPT codes (90791, 90834, 90837), insurance credentialing, and denial…', amount: '$1,100', rate: 'Fixed Price', proposals: 4, submitted: true },
  { id: 7, name: 'Naomi Williams', org: 'Naomi Williams Practice', location: 'Dallas, TX', posted: 'Jun 10', title: 'Virtual Admin Assistant - Bilingual EN/ES', category: 'Admin', desc: 'Trauma therapist in Dallas serving a primarily Spanish-speaking client base. Seeking a bilingual (English/Spanish) virtual admin to handle scheduling, intake calls, insurance verif…', amount: '$28/hr', rate: 'Hourly', proposals: 12 },
  { id: 8, name: 'Devon Hall', org: 'Devon Hall Practice', location: 'Harlem, NY', posted: 'Jun 9', title: 'Medicaid Billing - Substance Use Disorder Program', category: 'Billing', desc: 'Substance use counselor in Harlem running a Medicaid billing specialist for an outpatient SUD program. Must have experience with NY Medicaid billing (eMedNY), OASAS billing codes, a…', amount: '$1,000', rate: 'Fixed Price', proposals: 9 },
  { id: 9, name: 'Dr. Daniel Malik', org: 'Independent Practice', location: 'New York, NY', posted: 'Jun 9', title: 'Insurance Credentialing & Provider Enrollment', category: 'Credentialing', desc: 'Board-certified psychiatrist in NYC seeking a credentialing specialist to handle enrollment with Aetna, BCBS, United, Cigna, and Medicare. Must manage all application paperwork, fo…', amount: '$1,800', rate: 'Fixed Price', proposals: 3 },
  { id: 10, name: 'Dr. Amara Osei', org: 'Brooklyn', location: 'Brooklyn, NY', posted: 'Jun 8', title: 'Brand Identity & Logo Design', category: 'Design', desc: 'Clinical social worker in Brooklyn launching a group practice serving BIPOC communities. Seeking a designer to create a complete brand identity: logo, color palette, typography sys…', amount: '$1,600', rate: 'Fixed Price', proposals: 14 },
  { id: 11, name: 'Dr. Anna Thompson', org: 'Dr. Anna Thompson Practice', location: 'Brooklyn, NY', posted: 'Jun 8', title: 'Practice Website Redesign & SEO', category: 'Marketing', desc: 'Looking for a web designer and SEO specialist to redesign our child and adolescent psychiatry practice website. Must be familiar with healthcare compliance (HIPAA-friendly contact …', amount: '$4,000', rate: 'Fixed Price', proposals: 7, submitted: true },
  { id: 12, name: 'Dr. Patricia Monroe', org: 'Manhattan', location: 'Manhattan, NY', posted: 'Jun 8', title: 'Child Psychiatry - Annual HIPAA Risk Assessment', category: 'Compliance', desc: 'Child and adolescent psychiatry practice in Manhattan needs an annual HIPAA Security Risk Assessment (SRA) per CMS requirements. Deliverables: completed SRA documentation, risk mat…', amount: '$1,800', rate: 'Fixed Price', proposals: 4 },
  { id: 13, name: 'Dr. Lisa Chen', org: 'Brooklyn Wellness Collective', location: 'Brooklyn, NY', posted: 'Jun 7', title: 'Bookkeeping & QuickBooks Setup', category: 'Accounting', desc: 'CBT therapist in Brooklyn opening a bookkeeper to set up QuickBooks Online for the practice, categorize 12 months of transactions, reconcile accounts, and provide a monthly P&L. Mu…', amount: '$950', rate: 'Fixed Price', proposals: 6 },
  { id: 14, name: 'Dr. Raymond Olsen', org: 'Dr. Raymond Olsen Practice', location: 'Austin, TX', posted: 'Jun 7', title: 'Couples Therapy Practice - Google Ads Campaign', category: 'Marketing', desc: 'Couples and EFT therapist in Austin launching Google Ads for the first time. Need a healthcare marketing specialist to set up a Google Ads account, create 7–3 ad campaigns (couples…', amount: '$2,200', rate: 'Fixed Price', proposals: 8 },
  { id: 15, name: 'Dr. Rachel Moore', org: 'Manhattan Medical Group', location: 'New York, NY', posted: 'Jun 6', title: 'Group Practice Legal Setup - PLLC & Partnership', category: 'Legal', desc: 'Three therapists forming a group PLLC in New York. Need a healthcare attorney to draft PLLC operating agreement, revenue-sharing structure, buy-sell provisions, and client/provider…', amount: '$3,500', rate: 'Fixed Price', proposals: 2 },
  { id: 16, name: 'Dr. Rachel Okafor', org: 'North Bay Therapy Collective', location: 'Santa Rosa, CA', posted: 'Jun 6', title: 'HR & Staff Onboarding Support', category: 'HR', desc: 'Group therapy practice in Santa Rosa growing from solo to 3-provider group. Need HR support to draft employment contracts for two associate therapists, create an employee handbook,…', amount: '$2,400', rate: 'Fixed Price', proposals: 3 },
  { id: 17, name: 'Dr. Keisha Brooks', org: 'Dr. Keisha Brooks Practice', location: 'Jersey City, NJ', posted: 'Jun 5', title: 'Social Media Management - Naturopathic Practice', category: 'Marketing', desc: 'Naturopathic doctor in Jersey City seeking a social media manager to grow Instagram and Facebook presence. 3 posts/week across both platforms, monthly content calendar, story templ…', amount: '$1,200', rate: 'Fixed Price', proposals: 10 },
  { id: 18, name: 'Dr. Sarah Johnson', org: 'Lotus Psychology Group', location: 'San Francisco, CA', posted: 'Jun 5', title: 'Practice Marketing Consultant', category: 'Marketing', desc: 'Looking for a healthcare marketing consultant to improve our online presence and patient acquisition. Scope includes Google My Business optimization, SEO content strategy, Psycholo…', amount: '$3,000', rate: 'Fixed Price', proposals: 5 },
  { id: 19, name: 'Dr. Aisha Patel', org: 'Queens Family Therapy', location: 'Queens, NY', posted: 'Jun 4', title: 'Telehealth Platform Setup & Integration', category: 'Technology', desc: 'Family therapist in Queens transitioning to a hybrid practice model. Need a telehealth specialist to evaluate and implement a HIPAA-compliant video platform (Doxy.me or Zoom for He…', amount: '$1,400', rate: 'Fixed Price', proposals: 6 },
  { id: 20, name: 'Dr. Sarah Johnson', org: 'Lotus Psychology Group', location: 'San Francisco, CA', posted: 'Jun 2', title: 'HIPAA-Compliant IT Support Engineer', category: 'Technology', desc: 'Need ongoing IT support to maintain HIPAA-compliant infrastructure, EHR integrations, and telehealth systems. Must have healthcare IT experience and familiarity with HIPAA technica…', amount: '$97/hr', rate: 'Hourly', proposals: 24, submitted: true },
  { id: 21, name: 'Dr. Marcus Webb', org: 'Webb Counseling Services', location: 'New York, NY', posted: 'Jun 3', title: 'Substance Use Practice - Revenue Cycle Management', category: 'Billing', desc: 'Trauma and substance use practice seeking a full-cycle RCM partner. Scope: eligibility verification, prior authorizations (especially for IOP/PHP levels of care), charge capture, c…', fullDesc: 'Trauma and substance use practice seeking a full-cycle RCM partner. Scope: eligibility verification, prior authorizations (especially for IOP/PHP levels of care), charge capture, claims submission across commercial payers and Medicaid, A/R management, and monthly reporting.', amount: '$3,200', rate: 'Fixed Price', proposals: 0 },
  { id: 22, name: 'Dr. Sarah Johnson', org: 'Lotus Psychology Group', location: 'San Francisco, CA', posted: 'Jun 2', title: 'Medical Billing Specialist — Psychiatry Practice', category: 'Billing', desc: 'Seeking an experienced medical billing specialist to handle end-to-end billing for a busy psychiatry practice in San Francisco. Responsibilities include claims submission, follow-u…', amount: '$2,500', rate: 'Fixed Price', proposals: 18, submitted: true },
];
jobs.forEach(j => { j.initials = j.name.replace('Dr. ', '').split(' ').map(w => w[0]).slice(0, 2).join(''); j.remote = 'remote'; });

const catTabMap = { billing: ['Billing'], it: ['Technology'], marketing: ['Marketing'], admin: ['Admin'] };
const filteredJobs = computed(() => {
  return jobs.filter(j => {
    if (activeTab.value === 'urgent' && !j.urgent) return false;
    if (activeTab.value === 'new' && !j.new) return false;
    if (catTabMap[activeTab.value] && !catTabMap[activeTab.value].includes(j.category)) return false;
    if (savedOnly.value && !savedJobs.some(s => s.title === j.title)) return false;
    if (search.value) {
      const q = search.value.toLowerCase();
      if (!j.title.toLowerCase().includes(q) && !j.name.toLowerCase().includes(q) && !j.desc.toLowerCase().includes(q)) return false;
    }
    return true;
  });
});

// ── modals ──
const showProposal = ref(false);
const proposalJob = ref({});
const prop = reactive({ amount: '2500', rateType: 'Per Month', start: '', duration: 'Ongoing', cover: '', team: '' });
function openProposal(job) {
  proposalJob.value = { title: job.title, name: job.name || job.title, location: job.location || '' };
  prop.amount = (job.amount || '').replace(/[^0-9]/g, '') || '2500';
  showProposal.value = true;
}

const showDetails = ref(false);
const detailsJob = ref({});
function openDetails(job) { detailsJob.value = job; showDetails.value = true; }
function detailsToProposal() { const j = detailsJob.value; showDetails.value = false; openProposal(j); }
</script>

<style scoped>
.fj-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:24px 30px; margin-bottom:14px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.fj-title { font-family:var(--font-serif); font-size:32px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.fj-subtitle { font-size:13.5px; color:var(--text-3); margin-top:7px; }
.fj-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; }
.fj-badge { display:inline-flex; align-items:center; justify-content:center; min-width:17px; height:17px; padding:0 5px; border-radius:var(--radius-full); background:var(--gold-dark); color:var(--text-inverted); font-size:10px; font-weight:700; margin-left:4px; }

.fj-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:22px; }
.fj-stat { display:flex; align-items:center; gap:13px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 20px; box-shadow:var(--shadow-xs); }
.fj-stat-ico { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.fj-stat-num { font-family:var(--font-serif); font-size:21px; font-weight:700; color:var(--text); line-height:1; }
.fj-stat-label { font-size:11px; font-weight:600; letter-spacing:.4px; color:var(--text-4); margin-top:5px; }

.fj-section-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.4px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:6px; }
.fj-saved-head { font-family:var(--font-serif); font-size:18px; font-weight:600; color:var(--text); margin-bottom:12px; }
.fj-saved-strip { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:24px; }
.fj-saved-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:15px 17px; box-shadow:var(--shadow-xs); }
.fj-saved-title { font-size:13px; font-weight:700; color:var(--text); line-height:1.4; min-height:36px; }
.fj-saved-amt { font-family:var(--font-serif); font-size:17px; font-weight:700; color:var(--gold-dark); margin-top:6px; }
.fj-saved-meta { font-size:11px; color:var(--text-4); margin-top:3px; }
.fj-saved-foot { display:flex; align-items:center; gap:6px; margin-top:12px; }
.fj-saved-cta { margin-left:auto; }
.btn-xs { padding:5px 12px; font-size:11px; }

.fj-filterbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
.fj-search { display:flex; align-items:center; gap:8px; flex:1; min-width:200px; padding:9px 14px; background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); color:var(--text-4); }
.fj-search input { flex:1; border:none; outline:none; background:none; font-size:13px; color:var(--text); }
.fj-filterbar .form-select { width:auto; min-width:120px; }
.fj-saved-toggle { display:inline-flex; align-items:center; gap:6px; padding:9px 14px; font-size:12.5px; font-weight:600; color:var(--text-3); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); cursor:pointer; transition:all .15s ease; }
.fj-saved-toggle:hover { border-color:var(--gold-dark); }
.fj-saved-toggle.on { background:var(--gold-dark); border-color:var(--gold-dark); color:var(--text-inverted); }

.fj-tabs { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
.fj-tab { padding:7px 16px; font-size:12.5px; font-weight:600; color:var(--text-3); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; }
.fj-tab:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.fj-tab.active { background:var(--text); border-color:var(--text); color:var(--text-inverted); }
.fj-count { font-size:12.5px; color:var(--text-4); margin-bottom:14px; }

.fj-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.fj-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px 20px; box-shadow:var(--shadow-xs); display:flex; flex-direction:column; transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease; }
.fj-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-sm); border-color:var(--surface-4); }
.fj-card-top { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.fj-card-who { display:flex; align-items:center; gap:10px; min-width:0; }
.fj-av { width:32px; height:32px; border-radius:50%; background:var(--badge-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:11px; flex-shrink:0; }
.fj-av.lg { width:44px; height:44px; font-size:15px; }
.fj-who-info { min-width:0; }
.fj-who-name { font-size:12px; font-weight:700; color:var(--text); }
.fj-who-loc { font-size:11px; color:var(--text-4); }
.fj-card-date { font-size:11px; color:var(--text-4); flex-shrink:0; }
.fj-card-title { font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); margin-top:13px; line-height:1.25; }
.fj-cat { display:inline-block; align-self:flex-start; font-size:10.5px; font-weight:700; padding:3px 11px; border-radius:var(--radius-full); background:var(--badge-bg-gold); color:var(--gold-dark); margin-top:9px; }
.fj-card-desc { font-size:12.5px; color:var(--text-3); line-height:1.6; margin:11px 0 0; flex:1; }
.fj-card-desc a { color:var(--gold-dark); font-weight:600; text-decoration:none; }
.fj-card-amt { display:flex; align-items:center; gap:7px; margin-top:13px; font-size:12px; color:var(--text-4); }
.fj-card-amt svg { color:var(--gold-dark); }
.fj-card-amt strong { font-family:var(--font-serif); font-size:16px; font-weight:700; color:var(--text); }
.fj-rate { font-size:12px; color:var(--text-3); }
.fj-card-props { display:flex; align-items:center; gap:6px; font-size:11.5px; color:var(--text-4); margin-top:8px; }
.fj-card-foot { display:flex; align-items:center; gap:8px; margin-top:15px; padding-top:14px; border-top:1px solid var(--border); }
.fj-detail-btn { margin-right:auto; }
.fj-submitted { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; color:var(--green-dark); background:var(--green-light); border:none; border-radius:var(--radius); padding:7px 14px; cursor:default; }

/* modal */
.fj-modal-jobcard { background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius-lg); padding:14px 18px; margin-bottom:18px; }
.fj-modal-job-title { font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); }
.fj-modal-job-sub { font-size:12.5px; color:var(--text-3); margin-top:3px; }
.fj-modal-section { font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--gold-dark); margin:20px 0 12px; }
.fj-modal-section:first-of-type { margin-top:0; }
.fj-modal-optional { color:var(--text-4); font-weight:400; letter-spacing:.5px; }
.fj-modal-row4 { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
.fj-upload { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; padding:26px; border:1.5px dashed var(--border-dark); border-radius:var(--radius-lg); background:var(--surface-2); color:var(--gold-dark); cursor:pointer; transition:border-color .15s ease; }
.fj-upload:hover { border-color:var(--gold-dark); }
.fj-upload-text { font-size:13px; color:var(--text-2); }
.fj-upload-text strong { color:var(--gold-dark); }
.fj-upload-hint { font-size:11px; color:var(--text-4); }

/* details modal */
.fj-det-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; }
.fj-det-title { font-family:var(--font-serif); font-size:20px; font-weight:600; color:var(--text); line-height:1.2; }
.fj-det-amt-wrap { display:flex; flex-direction:column; align-items:flex-end; gap:4px; flex-shrink:0; }
.fj-det-amt { font-family:var(--font-serif); font-size:20px; font-weight:700; color:var(--gold-dark); }
.fj-det-engage { font-size:9.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--text-3); background:var(--surface-3); padding:3px 9px; border-radius:var(--radius-full); }
.fj-det-sub { font-size:13px; color:var(--text-3); margin-top:5px; }
.fj-det-overview { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; }
.fj-det-ov { border:1px solid var(--border); border-radius:var(--radius); padding:12px 14px; background:var(--surface-2); }
.fj-det-ov-label { font-size:9.5px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--text-4); }
.fj-det-ov-val { font-size:13px; font-weight:600; color:var(--text); margin-top:5px; }
.fj-det-desc { font-size:13px; color:var(--text-2); line-height:1.7; margin:0; }
.fj-pills { display:flex; gap:7px; flex-wrap:wrap; }
.fj-det-provider { display:flex; align-items:center; gap:14px; border:1px solid var(--border); border-radius:var(--radius-lg); padding:14px 18px; background:var(--surface-2); }
.fj-det-prov-name { font-size:14px; font-weight:700; color:var(--text); }
.fj-det-prov-meta { font-size:12px; color:var(--text-3); margin-top:3px; }
.fj-det-prov-rating { font-size:12px; color:var(--text-4); margin-top:4px; }

/* shared ep fields used in modal */
.ep-field { margin-bottom:0; }
.ep-label { display:block; font-size:12px; font-weight:600; color:var(--text-2); margin-bottom:7px; }
.ep-req { color:var(--red); }
.ep-input { display:block; width:100%; padding:10px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.ep-input:focus { border-color:var(--gold-dark); }
.ep-input::placeholder { color:var(--text-4); }
.ep-textarea { resize:vertical; min-height:96px; line-height:1.55; font-family:var(--font-sans); }
.ep-input-prefix { display:flex; align-items:center; border:1px solid var(--border-dark); border-radius:var(--radius); overflow:hidden; background:var(--surface); }
.ep-input-prefix:focus-within { border-color:var(--gold-dark); }
.ep-input-prefix span { padding:0 12px; color:var(--text-4); font-size:13px; align-self:stretch; display:flex; align-items:center; background:var(--surface-2); border-right:1px solid var(--border); }
.ep-input-prefix .ep-input { border:none; }

@media (max-width:1100px) { .fj-stats { grid-template-columns:repeat(2,1fr); } .fj-saved-strip { grid-template-columns:1fr; } .fj-grid { grid-template-columns:1fr; } .fj-modal-row4 { grid-template-columns:1fr 1fr; } .fj-det-overview { grid-template-columns:1fr 1fr; } }
@media (max-width:700px) { .fj-header { flex-direction:column; } .fj-modal-row4 { grid-template-columns:1fr; } }
</style>
