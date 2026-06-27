<template>
  <AppLayout portal="business_partner" page-title="Dashboard">

    <!-- ═══ OVERDUE ALERT ═══ -->
    <div class="bp-alert">
      <svg class="bp-alert-ico" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <div class="bp-alert-body">
        <div class="bp-alert-title">1 milestone overdue</div>
        <div class="bp-alert-sub">Audit Report for Dr. Priya Raman was due Jun 5. Submit now to keep the contract on track.</div>
      </div>
      <button type="button" class="btn btn-emergency btn-sm">Submit Now</button>
    </div>

    <!-- ═══ GREETING ═══ -->
    <div class="dh-greet">
      <div>
        <div class="dh-greet-eyebrow">{{ greeting }}</div>
        <div class="dh-greet-title">Acme Practice <em>Services,</em></div>
        <div class="dh-greet-sub">You have <strong style="color:var(--text-2);">3 active practitioner partners</strong> and 2 proposals awaiting decision. Your team has capacity for new work.</div>
      </div>
      <div class="dh-greet-meta">
        <div class="dh-greet-mcell"><div class="dh-greet-mlabel">Active Partners</div><div class="dh-greet-mval ok">3 partners</div></div>
        <div class="dh-greet-mcell"><div class="dh-greet-mlabel">Monthly Revenue</div><div class="dh-greet-mval"><span :class="{ blur: !showMoney }">{{ money(18400) }}</span> <button type="button" class="bp-eye" @click="showMoney = !showMoney"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div>
        <div class="dh-greet-mcell"><div class="dh-greet-mlabel">Team Utilisation</div><div class="dh-greet-mval">75%</div></div>
      </div>
    </div>

    <!-- ═══ OVERVIEW BANNER ═══ -->
    <div style="margin-bottom:14px;background:var(--surface);border:1px solid var(--border);border-left:3px solid var(--gold-dark);border-radius:var(--radius-lg);padding:16px 20px;display:flex;align-items:center;gap:16px;box-shadow:var(--shadow-sm);">
      <div style="width:38px;height:38px;border-radius:var(--radius-sm);background:var(--icon-bg-gold);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--gold-dark);">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:2px;">Overview — Start Here</div>
        <div style="font-size:12px;color:var(--text-3);line-height:1.5;">Key terms, your role as a Business Partner on Aegis, and FAQs.</div>
      </div>
      <a href="/business-partner/overview" class="btn btn-outline btn-sm" style="flex-shrink:0;white-space:nowrap;">View Overview <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></a>
    </div>

    <!-- ═══ FINISH PROFILE BANNER ═══ -->
    <div class="cs-profile-banner">
      <div class="cs-profile-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
      <div class="cs-profile-body"><div class="cs-profile-title">Finish your profile</div><div class="cs-profile-sub">Add credentials &amp; availability to improve discovery</div></div>
      <div class="cs-profile-progress"><div class="cs-profile-bar"><div class="cs-profile-bar-fill" :style="{ width: profilePct + '%' }"></div></div><span class="cs-profile-pct">{{ profilePct }}%</span></div>
      <a href="/business-partner/profile" class="btn btn-primary btn-sm cs-profile-cta">Complete <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>

    <!-- ═══ BUSINESS AT A GLANCE ═══ -->
    <div class="dh-sh">
      <div class="dh-sh-l"><div class="dh-sh-eyebrow">This month</div><div class="dh-sh-title">Business at a glance</div></div>
      <a class="dh-sh-link" href="/business-partner/activity">View activity <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
    <div class="dh-glance">
      <div class="dh-gl-card">
        <div class="dh-gl-head"><div class="dh-gl-label">Active Contracts</div><div class="dh-gl-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div></div>
        <div class="dh-gl-val">3</div>
        <div class="dh-gl-sub">across 3 practitioner partners</div>
      </div>
      <div class="dh-gl-card">
        <div class="dh-gl-head"><div class="dh-gl-label">Open Proposals</div><div class="dh-gl-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div></div>
        <div class="dh-gl-val">2</div>
        <div class="dh-gl-sub"><span class="warn">2 awaiting</span> practitioner decision</div>
      </div>
      <div class="dh-gl-card">
        <div class="dh-gl-head"><div class="dh-gl-label">Milestones Due</div><div class="dh-gl-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div></div>
        <div class="dh-gl-val">2</div>
        <div class="dh-gl-sub"><span class="crit">1 overdue</span> · 1 due this week</div>
      </div>
      <div class="dh-gl-card">
        <div class="dh-gl-head"><div class="dh-gl-label">Partner Rating</div><div class="dh-gl-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div></div>
        <div class="dh-gl-val">4.8 <span class="dh-gl-of">/5</span></div>
        <div class="dh-gl-sub">Based on 12 reviews</div>
      </div>
    </div>

    <!-- ═══ CONTENT GRID ═══ -->
    <div class="bp-grid">
      <div class="bp-main">
        <!-- ACTIVE CONTRACTS -->
        <div class="dh-sh" style="margin-top:6px;">
          <div class="dh-sh-l"><div class="dh-sh-eyebrow">Your Work</div><div class="dh-sh-title">Active Contracts</div></div>
          <a class="dh-sh-link" href="/business-partner/contracts">View all <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div v-for="c in contracts" :key="c.name + c.project" class="bp-contract" :class="{ overdue: c.status === 'overdue' }">
          <div class="bp-contract-rail" :class="c.status === 'overdue' ? 'rail-red' : 'rail-green'"></div>
          <div class="bp-contract-body">
            <div class="bp-contract-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div>
            <div class="bp-contract-info">
              <div class="bp-contract-type">{{ c.type }}</div>
              <div class="bp-contract-name">{{ c.name }}</div>
              <div class="bp-contract-project">{{ c.project }}</div>
              <div class="bp-contract-meta"><span class="bp-owner-av">{{ c.ownerInitials }}</span> Agency Owner · <span :class="c.status === 'overdue' ? 'due-red' : 'due-green'"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> {{ c.due }}</span></div>
            </div>
            <div class="bp-contract-right">
              <span class="bp-status" :class="c.status === 'overdue' ? 'st-red' : 'st-green'">{{ c.status === 'overdue' ? 'Overdue' : 'On Track' }}</span>
              <div class="bp-contract-amt"><span :class="{ blur: !showMoney }">{{ c.amount }}</span></div>
              <div class="bp-contract-actions">
                <template v-if="c.status === 'overdue'">
                  <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></button>
                  <button type="button" class="btn-icon btn-icon-danger"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></button>
                </template>
                <template v-else>
                  <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></button>
                  <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></button>
                </template>
              </div>
            </div>
          </div>
        </div>

        <!-- PROPOSALS AWAITING -->
        <div class="dh-sh">
          <div class="dh-sh-l"><div class="dh-sh-eyebrow">Pipeline</div><div class="dh-sh-title">Proposals Awaiting Decision</div></div>
          <a class="dh-sh-link" href="/business-partner/proposals">View all <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div v-for="p in proposals" :key="p.role" class="bp-proposal">
          <div class="bp-prop-av">{{ p.initials }}</div>
          <div class="bp-prop-info">
            <div class="bp-prop-role">{{ p.role }}</div>
            <div class="bp-prop-name">{{ p.client }}</div>
            <div class="bp-prop-meta">Submitted {{ p.submitted }} · <span :class="{ blur: !showMoney }">{{ p.amount }}</span></div>
          </div>
          <span class="bp-status st-neutral">Under Review</span>
          <div class="bp-prop-actions">
            <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></button>
          </div>
        </div>

        <!-- NEW JOBS -->
        <div class="dh-sh">
          <div class="dh-sh-l"><div class="dh-sh-eyebrow">Marketplace</div><div class="dh-sh-title">New Jobs Matching Your Services</div></div>
          <a class="dh-sh-link" href="/business-partner/find-jobs">Browse all <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div v-for="j in jobs" :key="j.title" class="bp-job">
          <div class="bp-job-ico" v-html="j.icon"></div>
          <div class="bp-job-info">
            <div class="bp-job-title">{{ j.title }}</div>
            <div class="bp-job-client">{{ j.client }} — Remote</div>
            <div class="bp-job-meta"><span :class="{ blur: !showMoney }">{{ j.amount }}</span> · {{ j.billing }}</div>
            <div class="bp-job-tags"><span v-for="t in j.tags" :key="t" class="bp-job-tag">{{ t }}</span></div>
          </div>
          <div class="bp-job-actions">
            <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            <button type="button" class="btn btn-gold btn-sm">Send Proposal</button>
          </div>
        </div>
      </div>

      <!-- ASIDE -->
      <div class="bp-aside">
        <!-- TEAM CAPACITY -->
        <div class="bp-card">
          <div class="bp-card-head"><span class="bp-card-title">Team Capacity</span><button type="button" class="btn btn-outline btn-xs"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Member</button></div>
          <div class="bp-card-body">
            <div class="bp-cap-top"><span class="bp-cap-num">6 <small>of 8 slots</small></span><span class="bp-cap-pct">75%</span></div>
            <div class="bp-cap-bar"><div class="bp-cap-fill" style="width:75%"></div></div>
            <div class="bp-cap-note"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> 2 open slots — ready for new partners</div>
            <div class="bp-members">
              <div v-for="m in team" :key="m.name" class="bp-member">
                <span class="bp-member-dot" :class="m.status === 'active' ? 'dot-green' : 'dot-grey'"></span>
                <div class="bp-member-info"><div class="bp-member-name">{{ m.name }}</div><div class="bp-member-sub">{{ m.projects }}</div></div>
                <span class="bp-member-stat" :class="m.status === 'active' ? 'ms-active' : 'ms-idle'">{{ m.status === 'active' ? 'Active' : 'Idle' }}</span>
              </div>
            </div>
          </div>
          <a href="/business-partner/team" class="bp-card-foot">Manage Team <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></a>
        </div>

        <!-- UPCOMING MILESTONES -->
        <div class="bp-card">
          <div class="bp-card-head"><span class="bp-card-title">Upcoming Milestones</span></div>
          <div class="bp-card-body">
            <div class="bp-ms-row">
              <div class="bp-ms-dot"></div>
              <div class="bp-ms-info"><div class="bp-ms-title">Phase 3: Migration, training, &amp; launch</div><div class="bp-ms-sub">Dr. Sarah Johnson · <span class="due-red">OVERDUE</span></div></div>
              <a href="#" class="bp-ms-cta">Submit Now</a>
            </div>
          </div>
          <a href="/business-partner/milestones" class="bp-card-foot">View All Milestones <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></a>
        </div>

        <!-- THIS MONTH -->
        <div class="bp-card">
          <div class="bp-card-head"><span class="bp-card-title">This Month</span><button type="button" class="bp-eye" @click="showMoney = !showMoney"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
          <div class="bp-card-body">
            <div class="bp-fin-row"><span>Invoiced</span><strong :class="{ blur: !showMoney }">{{ money(14200) }}</strong></div>
            <div class="bp-fin-row"><span>Received</span><strong class="ok" :class="{ blur: !showMoney }">{{ money(11800) }}</strong></div>
            <div class="bp-fin-row"><span>Outstanding</span><strong class="warn" :class="{ blur: !showMoney }">{{ money(2400) }}</strong></div>
          </div>
          <a href="/business-partner/finances" class="bp-card-foot">View Finances <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></a>
        </div>

        <!-- NOTIFICATIONS -->
        <div class="bp-card">
          <div class="bp-card-head"><span class="bp-card-title">Recent Notifications</span></div>
          <div class="bp-notifs">
            <div v-for="(n, i) in notifications" :key="i" class="bp-notif">
              <span class="bp-notif-dot" :class="'nd-' + n.type"></span>
              <div class="bp-notif-body"><div class="bp-notif-text"><strong>{{ n.title }}</strong> {{ n.text }}</div><div class="bp-notif-time">{{ n.time }}</div></div>
            </div>
          </div>
          <a href="/business-partner/activity" class="bp-card-foot">View All Notifications <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></a>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '@/Components/AppLayout.vue'

const profilePct = 78
const showMoney = ref(true)
function money(n) { return '$' + n.toLocaleString() }

const hour = new Date().getHours()
const greeting = hour < 12 ? 'Good morning' : (hour < 17 ? 'Good afternoon' : 'Good evening')

const contracts = [
  { type: 'Fixed-Price Project', name: 'Dr. Sarah Johnson', project: 'SimplePractice migration & setup', ownerInitials: 'DJ', due: 'Was due May 10', status: 'overdue', amount: '$4,800' },
  { type: 'Fixed-Price Project', name: 'Elena Vasquez', project: 'EHR Migration & Training', ownerInitials: 'EV', due: 'Due Jun 30', status: 'ontrack', amount: '$12,000' },
  { type: 'Hourly', name: 'Dr. Sarah Johnson', project: 'Medical Billing Retainer — 2026', ownerInitials: 'DJ', due: 'Due Dec 31', status: 'ontrack', amount: '$145/hr' },
]

const proposals = [
  { initials: 'SJ', role: 'HIPAA-Compliant IT Support Engineer', client: 'Dr. Sarah Johnson', submitted: '148 days ago', amount: '$6,500' },
  { initials: 'SJ', role: 'Medical Billing Specialist — Psychiatry Practice', client: 'Dr. Sarah Johnson', submitted: '155 days ago', amount: '$5,200' },
]

const ji = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'
const jobs = [
  { title: 'Practice Operations Consultant', client: 'Dr. Priya Mehta', amount: '$85/hr', billing: 'Hourly', tags: ['consulting'], icon: `<svg viewBox="0 0 24 24" ${ji}><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>` },
  { title: 'HIPAA Compliance Audit', client: 'James Okafor', amount: '$3,500', billing: 'Fixed Price', tags: ['HIPAA', 'compliance', 'audit'], icon: `<svg viewBox="0 0 24 24" ${ji}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
  { title: 'Patient Intake Automation & CRM Setup', client: 'Dr. Thomas Nguyen', amount: '$4,200', billing: 'Fixed Price', tags: ['EHR', 'HIPAA IT', 'tech support'], icon: `<svg viewBox="0 0 24 24" ${ji}><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>` },
]

const team = [
  { name: 'Riley Chen', projects: '2 projects', status: 'active' },
  { name: 'Marcus T.', projects: '2 projects', status: 'active' },
  { name: 'Sam Lee', projects: '0 projects', status: 'idle' },
  { name: 'Jordan K.', projects: '1 project', status: 'active' },
]

const notifications = [
  { type: 'gold', title: 'Proposal withdrawn: EHR Setup & Staff Training —', text: 'You withdrew your proposal and Elena Vasquez was notified.', time: 'Jun 14' },
  { type: 'blue', title: 'Invitation sent to david.wu@email.com —', text: 'A team invitation was sent for the EMR Specialist role in th…', time: 'Jun 14' },
  { type: 'blue', title: 'Proposal declined: Practice Website Redesign & SEO —', text: 'Dr. Anna Thompson selected another partner whose timeline st…', time: 'Jun 13' },
  { type: 'blue', title: 'Job saved: HIPAA Compliance Audit —', text: 'You bookmarked this job for later review.', time: 'Jun 13' },
]
</script>
<style scoped>
/* ── BP DASHBOARD ── */
.bp-alert { display:flex; align-items:center; gap:14px; padding:14px 22px; background:rgba(160,45,34,.06); border:1px solid rgba(160,45,34,.20); border-left:4px solid var(--red); border-radius:var(--radius-lg); margin-bottom:18px; }
.bp-alert-ico { color:var(--red); flex-shrink:0; }
.bp-alert-body { flex:1; min-width:0; }
.bp-alert-title { font-size:13.5px; font-weight:700; color:var(--red); }
.bp-alert-sub { font-size:12.5px; color:var(--text-2); margin-top:2px; }
.btn-emergency { background:var(--red); color:var(--text-inverted); border:none; flex-shrink:0; }
.btn-emergency:hover { background:var(--red-dark); }

.cs-profile-banner { margin-bottom:24px; background:linear-gradient(95deg,var(--badge-bg-gold) 0%,var(--surface) 80%); border:1px solid rgba(192,154,82,.28); border-radius:var(--radius-lg); padding:14px 20px; display:flex; align-items:center; gap:16px; box-shadow:var(--shadow-sm); }
.cs-profile-icon { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cs-profile-body { flex:1; min-width:0; }
.cs-profile-title { font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px; }
.cs-profile-sub { font-size:12px; color:var(--text-3); line-height:1.5; }
.cs-profile-progress { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.cs-profile-bar { width:120px; height:5px; background:var(--surface-3); border-radius:var(--radius-full); overflow:hidden; }
.cs-profile-bar-fill { height:100%; background:linear-gradient(90deg,var(--gold),var(--gold-dark)); border-radius:var(--radius-full); }
.cs-profile-pct { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.cs-profile-cta { flex-shrink:0; white-space:nowrap; }

.dh-greet { display:grid; grid-template-columns:1fr auto; gap:26px; align-items:end; padding:6px 4px 26px; border-bottom:1px solid var(--border); margin-bottom:22px; }
.dh-greet-eyebrow { font-size:11px; font-weight:600; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:10px; display:flex; align-items:center; gap:9px; }
.dh-greet-eyebrow::before { content:""; width:18px; height:1px; background:var(--gold-dark); }
.dh-greet-title { font-family:var(--font-serif); font-size:36px; font-weight:600; letter-spacing:-0.5px; line-height:1.05; color:var(--text); }
.dh-greet-title em { font-style:italic; color:var(--gold-dark); font-weight:600; }
.dh-greet-sub { margin-top:12px; font-size:14px; color:var(--text-3); max-width:560px; line-height:1.55; }
.dh-greet-meta { display:flex; align-items:center; gap:0; padding:14px 8px; border-radius:var(--radius-lg); background:var(--surface); border:1px solid var(--border); box-shadow:var(--shadow-xs); }
.dh-greet-mcell { display:flex; flex-direction:column; gap:4px; padding:0 22px; }
.dh-greet-mcell+.dh-greet-mcell { border-left:1px solid var(--border); }
.dh-greet-mlabel { font-size:9.5px; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); font-weight:600; }
.dh-greet-mval { font-family:var(--font-serif); font-size:17px; font-weight:600; color:var(--text); display:inline-flex; align-items:center; gap:6px; }
.dh-greet-mval.ok { color:var(--green-dark); }
.bp-eye { background:none; border:none; color:var(--text-4); cursor:pointer; display:inline-flex; padding:0; }
.bp-eye:hover { color:var(--gold-dark); }
.blur { filter:blur(5px); user-select:none; }

.dh-sh { display:flex; align-items:flex-end; justify-content:space-between; margin:28px 0 14px; gap:16px; }
.dh-sh-l { display:flex; flex-direction:column; gap:3px; }
.dh-sh-eyebrow { font-size:10px; font-weight:600; letter-spacing:1.4px; text-transform:uppercase; color:var(--gold-dark); }
.dh-sh-title { font-family:var(--font-serif); font-size:20px; font-weight:600; letter-spacing:-0.2px; color:var(--text); }
.dh-sh-link { font-size:12px; font-weight:600; color:var(--text-3); display:inline-flex; align-items:center; gap:6px; padding:6px 0; text-decoration:none; transition:color .18s ease; }
.dh-sh-link:hover { color:var(--gold-dark); }

.dh-glance { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
.dh-gl-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px 20px; display:flex; flex-direction:column; gap:14px; box-shadow:var(--shadow-xs); transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease; }
.dh-gl-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-sm); border-color:var(--surface-4); }
.dh-gl-head { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.dh-gl-label { font-size:11px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); }
.dh-gl-icon { width:30px; height:30px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.dh-gl-val { font-family:var(--font-serif); font-size:32px; font-weight:600; line-height:1; letter-spacing:-1px; color:var(--text); }
.dh-gl-of { font-size:15px; color:var(--text-4); }
.dh-gl-sub { font-size:12px; color:var(--text-3); }
.dh-gl-sub .warn { color:var(--orange-dark); font-weight:600; }
.dh-gl-sub .crit { color:var(--red); font-weight:600; }

.bp-grid { display:grid; grid-template-columns:1fr 320px; gap:24px; align-items:start; }
.bp-main { display:flex; flex-direction:column; }
.bp-aside { display:flex; flex-direction:column; gap:16px; }

/* contracts */
.bp-contract { display:flex; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; margin-bottom:10px; }
.bp-contract.overdue { background:rgba(160,45,34,.02); }
.bp-contract-rail { width:4px; flex-shrink:0; }
.bp-contract-rail.rail-red { background:var(--red); }
.bp-contract-rail.rail-green { background:var(--green); }
.bp-contract-body { flex:1; min-width:0; display:flex; align-items:flex-start; gap:14px; padding:16px 18px; }
.bp-contract-ico { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.bp-contract-info { flex:1; min-width:0; }
.bp-contract-type { font-size:9.5px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--gold-dark); }
.bp-contract-name { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); margin-top:3px; }
.bp-contract-project { font-size:12.5px; color:var(--text-3); margin-top:1px; }
.bp-contract-meta { display:flex; align-items:center; gap:6px; font-size:11.5px; color:var(--text-4); margin-top:8px; flex-wrap:wrap; }
.bp-owner-av { width:18px; height:18px; border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); display:inline-flex; align-items:center; justify-content:center; font-size:8px; font-weight:700; }
.due-red { color:var(--red); font-weight:600; display:inline-flex; align-items:center; gap:4px; }
.due-green { color:var(--green-dark); font-weight:600; display:inline-flex; align-items:center; gap:4px; }
.bp-contract-right { display:flex; flex-direction:column; align-items:flex-end; gap:8px; flex-shrink:0; }
.bp-status { font-size:9.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:3px 9px; border-radius:var(--radius-full); }
.bp-status.st-red { background:var(--red-light); color:var(--red); }
.bp-status.st-green { background:var(--green-light); color:var(--green-dark); }
.bp-status.st-neutral { background:var(--surface-3); color:var(--text-3); }
.bp-contract-amt { font-family:var(--font-serif); font-size:15px; font-weight:700; color:var(--text); }
.bp-contract-actions, .bp-prop-actions { display:flex; gap:6px; }

/* proposals */
.bp-proposal { display:flex; align-items:center; gap:14px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); padding:16px 18px; margin-bottom:10px; }
.bp-prop-av { width:36px; height:36px; border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:12px; flex-shrink:0; }
.bp-prop-info { flex:1; min-width:0; }
.bp-prop-role { font-size:9.5px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--gold-dark); }
.bp-prop-name { font-family:var(--font-serif); font-size:14px; font-weight:600; color:var(--text); margin-top:2px; }
.bp-prop-meta { font-size:11.5px; color:var(--text-4); margin-top:3px; }

/* jobs */
.bp-job { display:flex; align-items:flex-start; gap:14px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); padding:16px 18px; margin-bottom:10px; }
.bp-job-ico { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.bp-job-ico :deep(svg) { width:16px; height:16px; }
.bp-job-info { flex:1; min-width:0; }
.bp-job-title { font-family:var(--font-serif); font-size:14.5px; font-weight:600; color:var(--text); }
.bp-job-client { font-size:12px; color:var(--gold-dark); margin-top:2px; }
.bp-job-meta { font-size:12px; color:var(--text-3); margin-top:4px; }
.bp-job-tags { display:flex; gap:6px; flex-wrap:wrap; margin-top:8px; }
.bp-job-tag { font-size:10px; font-weight:600; padding:2px 9px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); }
.bp-job-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; }

/* aside cards */
.bp-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; }
.bp-card-head { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:15px 18px; border-bottom:1px solid var(--border); }
.bp-card-title { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.bp-card-body { padding:16px 18px; }
.bp-card-foot { display:flex; align-items:center; justify-content:center; gap:6px; padding:12px 18px; border-top:1px solid var(--border); font-size:12.5px; font-weight:600; color:var(--text-2); text-decoration:none; transition:color .15s ease; }
.bp-card-foot:hover { color:var(--gold-dark); }
.btn-xs { padding:4px 10px; font-size:11px; }

.bp-cap-top { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:8px; }
.bp-cap-num { font-family:var(--font-serif); font-size:22px; font-weight:700; color:var(--text); }
.bp-cap-num small { font-family:var(--font-sans); font-size:12px; font-weight:400; color:var(--text-4); }
.bp-cap-pct { font-size:13px; font-weight:700; color:var(--gold-dark); }
.bp-cap-bar { height:6px; background:var(--surface-3); border-radius:var(--radius-full); overflow:hidden; }
.bp-cap-fill { height:100%; background:var(--gold-dark); border-radius:var(--radius-full); }
.bp-cap-note { display:flex; align-items:center; gap:6px; font-size:11.5px; color:var(--green-dark); margin:10px 0 14px; }
.bp-members { display:flex; flex-direction:column; gap:11px; }
.bp-member { display:flex; align-items:center; gap:10px; }
.bp-member-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.bp-member-dot.dot-green { background:var(--green); }
.bp-member-dot.dot-grey { background:var(--border-dark); }
.bp-member-info { flex:1; min-width:0; }
.bp-member-name { font-size:13px; font-weight:600; color:var(--text); }
.bp-member-sub { font-size:11px; color:var(--text-4); }
.bp-member-stat { font-size:9px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:2px 8px; border-radius:var(--radius-full); }
.ms-active { background:var(--green-light); color:var(--green-dark); }
.ms-idle { background:var(--surface-3); color:var(--text-4); }

.bp-ms-row { display:flex; align-items:flex-start; gap:10px; }
.bp-ms-dot { width:8px; height:8px; border-radius:50%; background:var(--red); flex-shrink:0; margin-top:5px; }
.bp-ms-info { flex:1; min-width:0; }
.bp-ms-title { font-size:13px; font-weight:600; color:var(--text); line-height:1.4; }
.bp-ms-sub { font-size:11.5px; color:var(--text-4); margin-top:3px; }
.due-red { color:var(--red); font-weight:700; }
.bp-ms-cta { font-size:11.5px; font-weight:700; color:var(--red); text-decoration:none; flex-shrink:0; }
.bp-ms-cta:hover { text-decoration:underline; }

.bp-fin-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:8px 0; font-size:13px; color:var(--text-3); }
.bp-fin-row strong { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.bp-fin-row strong.ok { color:var(--green-dark); }
.bp-fin-row strong.warn { color:var(--orange-dark); }

.bp-notifs { padding:6px 0; }
.bp-notif { display:flex; gap:10px; padding:11px 18px; border-bottom:1px solid var(--border); }
.bp-notif:last-child { border-bottom:none; }
.bp-notif-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; margin-top:5px; }
.nd-gold { background:var(--gold-dark); }
.nd-blue { background:#5078be; }
.bp-notif-body { flex:1; min-width:0; }
.bp-notif-text { font-size:12px; color:var(--text-3); line-height:1.5; }
.bp-notif-text strong { color:var(--text); font-weight:600; }
.bp-notif-time { font-size:10.5px; color:var(--text-4); margin-top:3px; }

@media (max-width:1180px) { .dh-greet { grid-template-columns:1fr; } .dh-glance { grid-template-columns:repeat(2,1fr); } .bp-grid { grid-template-columns:1fr; } }
@media (max-width:700px) { .dh-greet-title { font-size:28px; } .dh-glance { grid-template-columns:1fr 1fr; } .bp-contract-body { flex-wrap:wrap; } }
</style>
