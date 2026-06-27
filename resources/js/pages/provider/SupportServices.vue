<template>
  <AppLayout :user="user" portal="practitioner" activePage="job-postings" pageTitle="Support & Services">

    <!-- HERO BANNER -->
    <div class="ss-hero">
      <div class="ss-hero-left">
        <div class="ss-hero-eyebrow">SUPPORT &amp; SERVICES</div>
        <h1 class="ss-hero-title">Request support and connect with business partners</h1>
        <p class="ss-hero-sub">Find business partners for billing, marketing, IT, and operations — vetted on Aegis with HIPAA-trained backgrounds.</p>
      </div>
      <div class="ss-hero-actions">
        <button class="btn btn-outline btn-sm ss-btn-icon" @click="showToast('Opening activity log','info')">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          Activity
        </button>
        <button class="btn btn-primary btn-sm ss-btn-icon" @click="openRequestModal">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Request Support
        </button>
      </div>
    </div>

    <!-- STAT CARDS -->
    <div class="ss-stats">
      <div class="ss-stat">
        <div class="ss-stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div>
        <div><div class="ss-stat-value">3</div><div class="ss-stat-label">Active Postings</div></div>
      </div>
      <div class="ss-stat">
        <div class="ss-stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div>
        <div><div class="ss-stat-value">6</div><div class="ss-stat-label">Total Applications</div></div>
      </div>
      <div class="ss-stat">
        <div class="ss-stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div><div class="ss-stat-value">4</div><div class="ss-stat-label">Awaiting Review</div></div>
      </div>
      <div class="ss-stat">
        <div class="ss-stat-icon ss-stat-icon-green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div><div class="ss-stat-value">2</div><div class="ss-stat-label">Hired</div></div>
      </div>
    </div>

    <!-- TABS -->
    <div class="ss-tabs">
      <button v-for="tab in tabs" :key="tab.id" class="ss-tab" :class="{ active: activeTab === tab.id }" @click="activeTab = tab.id">
        <svg v-html="tab.icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"></svg>
        {{ tab.label }}
        <span v-if="tab.count" class="ss-tab-count">{{ tab.count }}</span>
      </button>
    </div>

    <!-- MY POSTINGS TAB -->
    <div v-show="activeTab === 'postings'">
      <div class="ss-section-header">
        <div class="ss-section-title">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          My Support Requests
        </div>
        <div class="ss-section-actions">
          <button class="btn btn-outline btn-sm ss-btn-icon" @click="showToast('Opening templates','info')">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Use Template
          </button>
          <button class="btn btn-sm ss-btn-icon ss-request-btn" @click="openRequestModal">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Request Support
          </button>
        </div>
      </div>

      <!-- Filter pills -->
      <div class="ss-filters">
        <button v-for="fil in filters" :key="fil.id" class="ss-filter" :class="{ active: activeFilter === fil.id }" @click="activeFilter = fil.id">
          <span class="ss-filter-label">{{ fil.label }}</span>
          <span v-if="fil.count !== undefined" class="ss-filter-count">{{ fil.count }}</span>
        </button>
      </div>

      <!-- Jobs table -->
      <div class="ss-table-wrap">
        <table class="ss-table">
          <thead>
            <tr>
              <th>JOB TITLE</th>
              <th>CATEGORY</th>
              <th>BUDGET</th>
              <th>APPLICANTS</th>
              <th>STATUS</th>
              <th>ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="job in filteredJobs" :key="job.id" class="ss-row-clickable" @click="openEditModal(job)">
              <td>
                <div class="ss-job-title">{{ job.title }}</div>
                <div class="ss-job-meta">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                  {{ job.category }} · Posted {{ job.posted }} · {{ job.location }}
                </div>
              </td>
              <td class="ss-td-cat">{{ job.category }}</td>
              <td><span :class="job.status === 'ACTIVE' ? 'ss-td-budget-active' : 'ss-td-budget-plain'">{{ job.budget }}</span></td>
              <td class="ss-td-applicants">
                <div>{{ job.applicants }}</div>
                <div class="ss-applicant-sub">{{ job.newApplicants }}</div>
              </td>
              <td><span class="ss-status" :class="'ss-status-' + job.status.toLowerCase()">{{ job.status }}</span></td>
              <td>
                <div class="ss-actions">
                  <!-- Edit pencil — shown for DRAFT and ACTIVE only -->
                  <button v-if="job.status === 'DRAFT' || job.status === 'ACTIVE'" class="ss-act-btn" title="Edit" @click.stop="openEditModal(job)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <!-- DRAFT: green Publish button -->
                  <button v-if="job.status === 'DRAFT'" class="btn ss-publish-btn" @click.stop="publishJob(job)">Publish</button>
                  <!-- ACTIVE: people + red trash -->
                  <button v-if="job.status === 'ACTIVE'" class="ss-act-btn" title="View applicants" @click.stop="showToast('Viewing applicants for ' + job.title,'info')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                  </button>
                  <button v-if="job.status === 'ACTIVE'" class="ss-act-btn ss-act-danger" title="Pause / Remove" @click.stop="pauseJob(job)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                  </button>
                  <!-- PAUSED: only refresh -->
                  <button v-if="job.status === 'PAUSED'" class="ss-act-btn" title="Resume" @click.stop="resumeJob(job)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg>
                  </button>
                  <!-- CLOSED: people + refresh + eye -->
                  <button v-if="job.status === 'CLOSED'" class="ss-act-btn" title="View applicants" @click.stop="showToast('Viewing applicants','info')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                  </button>
                  <button v-if="job.status === 'CLOSED'" class="ss-act-btn" title="Reopen" @click.stop="showToast('Reopening ' + job.title,'info')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg>
                  </button>
                  <button v-if="job.status === 'CLOSED'" class="ss-act-btn" title="View posting" @click.stop="showToast('Viewing ' + job.title,'info')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="ss-pagination">
        <span class="ss-pagination-info">Showing {{ filteredJobs.length }} of {{ filteredJobs.length }} postings</span>
        <div class="ss-pagination-pages">
          <button class="ss-page-btn active">1</button>
          <button class="ss-page-btn ss-page-next" @click="showToast('Next page','info')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </button>
        </div>
      </div>
    </div>

    <!-- APPLICATIONS TAB -->
    <div v-show="activeTab === 'applications'">
      <!-- Header row -->
      <div class="ss-section-header" style="margin-bottom:12px;">
        <div class="ss-section-title">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          All Applications
        </div>
        <div class="ss-section-actions">
          <select class="ss-filter-select"><option>All Job Postings</option><option>HIPAA-Compliant IT Support Engineer</option><option>Medical Billing Specialist — Psychiatry Practice</option><option>Virtual Administrative Assistant</option></select>
          <select class="ss-filter-select"><option>All Statuses</option><option>PENDING</option><option>ACCEPTED</option><option>REJECTED</option></select>
        </div>
      </div>

      <!-- Unreviewed notice -->
      <div class="ss-unreviewed-notice">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        <span>You have <strong class="ss-notice-link">4 unreviewed applications</strong> awaiting your response.</span>
      </div>

      <!-- Applications table -->
      <div class="ss-table-wrap">
        <table class="ss-table">
          <thead>
            <tr>
              <th>APPLICANT</th>
              <th>APPLIED FOR</th>
              <th>RATE</th>
              <th>STATUS</th>
              <th>ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="app in applications" :key="app.id" class="ss-row-clickable" @click="openApplicantModal(app)">
              <td>
                <div class="ss-applicant-row">
                  <div class="ss-applicant-avatar" :style="{ background: app.avatarColor }">{{ app.initials }}</div>
                  <div>
                    <div class="ss-applicant-name">{{ app.name }}</div>
                    <div class="ss-applicant-type">{{ app.type }}</div>
                  </div>
                </div>
              </td>
              <td class="ss-td-cat">{{ app.appliedFor }}</td>
              <td><span class="ss-td-budget-active">{{ app.rate }}</span></td>
              <td>
                <span class="ss-status" :class="'ss-status-' + app.status.toLowerCase()">{{ app.status }}</span>
              </td>
              <td>
                <div class="ss-actions">
                  <button v-if="app.status === 'PENDING'" class="ss-act-btn ss-act-accept" title="Accept" @click.stop="quickAccept(app)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </button>
                  <button v-if="app.status === 'PENDING'" class="ss-act-btn ss-act-danger" title="Reject" @click.stop="openRejectPop(app)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  </button>
                  <button v-if="app.status === 'ACCEPTED'" class="ss-act-btn" title="View" @click.stop="openApplicantModal(app)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="ss-pagination">
        <span class="ss-pagination-info">Showing {{ applications.length }} of {{ applications.length }} applications</span>
        <div class="ss-pagination-pages">
          <button class="ss-page-btn active">1</button>
        </div>
      </div>
    </div>
    <div v-show="activeTab === 'pipeline'">
      <!-- Header -->
      <div class="ss-section-header" style="margin-bottom:16px;">
        <div class="ss-section-title">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="5" r="2"/><circle cx="5" cy="19" r="2"/><circle cx="19" cy="19" r="2"/><path d="M12 7v6M12 13l-6 4M12 13l6 4"/></svg>
          Hiring Pipeline
        </div>
        <div class="ss-section-actions">
          <select class="ss-filter-select"><option>All Active Jobs</option><option>HIPAA-Compliant IT Support Engineer</option><option>Medical Billing Specialist</option></select>
        </div>
      </div>

      <!-- Kanban columns -->
      <div class="hp-board">
        <div v-for="col in pipelineCols" :key="col.id" class="hp-col">
          <!-- Column header -->
          <div class="hp-col-header">
            <div class="hp-col-title-row">
              <span class="hp-col-icon" v-html="col.icon"></span>
              <span class="hp-col-name">{{ col.label }}</span>
            </div>
            <span class="hp-col-count">{{ pipelineCards(col.id).length }}</span>
          </div>
          <!-- Cards -->
          <div class="hp-cards">
            <div
              v-for="card in pipelineCards(col.id)"
              :key="card.id"
              class="hp-card"
              @click="openApplicantModal(card)"
            >
              <div class="hp-card-top">
                <div class="hp-avatar" :style="{ background: card.avatarColor }">{{ card.initials }}</div>
                <div class="hp-card-info">
                  <div class="hp-card-name">{{ card.name }}</div>
                  <div class="hp-card-role">{{ card.appliedFor }}</div>
                </div>
              </div>
              <div class="hp-card-bottom">
                <span class="hp-card-rating">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15 8.5 22 9.3 17 14 18.2 21 12 17.5 5.8 21 7 14 2 9.3 9 8.5 12 2"/></svg>
                  {{ card.rating }}
                </span>
                <span class="hp-card-rate">{{ card.rate }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-show="activeTab === 'hired'">
      <div class="ss-section-header" style="margin-bottom:16px;">
        <div class="ss-section-title">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          Hired Business Partners
        </div>
      </div>

      <div class="hired-grid">
        <div v-for="h in hiredPartners" :key="h.id" class="hired-card" @click="openContractModal(h)">
          <div class="hired-card-top">
            <div class="hired-avatar" :style="{ background: h.avatarColor }">{{ h.initials }}</div>
            <div class="hired-info">
              <div class="hired-name">{{ h.name }}</div>
              <div class="hired-role">{{ h.role }} · {{ h.type }}</div>
            </div>
          </div>
          <div class="hired-status-row">
            <span class="hired-badge">
              <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor" stroke="none"><circle cx="12" cy="12" r="10"/></svg>
              ACTIVE CONTRACT
            </span>
          </div>
          <div class="hired-actions">
            <button class="ss-act-btn" title="Message" @click.stop="goToMessages(h)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </button>
            <button class="ss-act-btn" title="View contract" @click.stop="openContractModal(h)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </button>
          </div>
        </div>
      </div>

      <div class="ss-pagination" style="margin-top:16px;">
        <span class="ss-pagination-info">Showing {{ hiredPartners.length }} of {{ hiredPartners.length }} hired partners</span>
        <div class="ss-pagination-pages">
          <button class="ss-page-btn active">1</button>
          <button class="ss-page-btn ss-page-next"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg></button>
        </div>
      </div>
    </div>

    <!-- Toast stack -->
    <Teleport to="body">
      <div class="dh-toast-stack">
        <div v-for="t in toasts" :key="t.id" class="dh-toast" :class="t.type">
          <svg v-if="t.type==='success'" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          <span>{{ t.msg }}</span>
        </div>
      </div>

      <!-- APPLICANT PROFILE MODAL -->
      <Transition name="ss-modal-fade">
        <div v-if="appModal.open" class="ss-modal-backdrop" @click.self="appModal.open=false">
          <div class="ss-modal ss-modal-wide" role="dialog">
            <div class="ss-modal-header">
              <div>
                <div class="ss-modal-title">Applicant Profile</div>
                <div class="ss-modal-sub">Reviewing for: {{ appModal.app?.appliedFor }}</div>
              </div>
              <button class="ss-modal-close" @click="appModal.open=false">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="ss-modal-body" v-if="appModal.app">
              <!-- Profile header -->
              <div class="ap-profile-head">
                <div class="ap-avatar-lg" :style="{ background: appModal.app.avatarColor }">{{ appModal.app.initials }}</div>
                <div class="ap-profile-info">
                  <div class="ap-name">{{ appModal.app.name }} <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></div>
                  <div class="ap-meta">{{ appModal.app.appliedFor }} · {{ appModal.app.type }}</div>
                  <div class="ap-tags-row">
                    <span class="ap-info-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> Miami, FL · Remote</span>
                    <span class="ap-info-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polygon points="12 2 15 8.5 22 9.3 17 14 18.2 21 12 17.5 5.8 21 7 14 2 9.3 9 8.5 12 2"/></svg> 4.9 (147 reviews)</span>
                    <span class="ap-info-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg> 92 jobs completed</span>
                    <span class="ap-info-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> HIPAA Certified</span>
                  </div>
                  <div class="ap-badges-row">
                    <span class="ap-badge ap-badge-gold">TOP RATED</span>
                    <span class="ap-badge ap-badge-green">FAST RESPONDER</span>
                    <span class="ap-badge ap-badge-blue">HIPAA</span>
                  </div>
                </div>
              </div>
              <!-- Stats row -->
              <div class="ap-stats-row">
                <div class="ap-stat"><div class="ap-stat-val">{{ appModal.app.rate }}</div><div class="ap-stat-lbl">Asking Rate</div></div>
                <div class="ap-stat"><div class="ap-stat-val">4.9</div><div class="ap-stat-lbl">Rating</div></div>
                <div class="ap-stat"><div class="ap-stat-val">92</div><div class="ap-stat-lbl">Jobs Done</div></div>
                <div class="ap-stat"><div class="ap-stat-val">10+yr</div><div class="ap-stat-lbl">Experience</div></div>
              </div>
              <!-- Cover Letter -->
              <div class="ap-section">
                <div class="ap-section-title"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Cover Letter / Proposal</div>
                <div class="ap-cover-text">"I've reviewed your posting and believe I'm an excellent fit for your psychiatric practice. I specialize in behavioral health billing, particularly CPT codes 90791–90853 and psychotherapy add-ons. I have 10+ years working with Athena, AdvancedMD, and Office Ally. My clean claim rate is consistently above 97%, and I handle full-cycle billing from charge entry to payment posting to appeal management.<br><br>I can start immediately and am happy to sign a BAA and NDA before any work begins. I'd love to schedule a 15-minute intro call to discuss your current AR situation."</div>
              </div>
              <!-- Application Status -->
              <div class="ap-section">
                <div class="ap-section-title"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Application Status</div>
                <div class="ap-status-btns">
                  <button class="ap-status-btn ap-status-review" @click="openMarkReviewedPop">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Mark Reviewed
                  </button>
                  <button class="ap-status-btn ap-status-shortlist" @click="openShortlistPop">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polygon points="12 2 15 8.5 22 9.3 17 14 18.2 21 12 17.5 5.8 21 7 14 2 9.3 9 8.5 12 2"/></svg>
                    Shortlist
                  </button>
                  <button class="ap-status-btn ap-status-schedule" @click="openSchedulePop">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Schedule Interview
                  </button>
                  <button class="ap-status-btn ap-status-reject" @click="openRejectPop(appModal.app)">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Reject
                  </button>
                </div>
                <div class="ap-notes-label">Private Notes on Applicant</div>
                <textarea class="ss-modal-textarea" v-model="appModal.notes" placeholder="Your private notes — great communicator, strong billing background…" rows="3"></textarea>
              </div>
            </div>
            <div class="ss-modal-footer">
              <button class="btn btn-outline btn-sm" @click="appModal.open=false">Close</button>
              <button class="ss-modal-close-posting-btn" @click="showToast('Message sent','success')">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Message
              </button>
              <button class="ss-modal-close-posting-btn" @click="openSchedulePop">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Schedule Interview
              </button>
              <button class="ss-modal-save-btn" @click="hireApplicant">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                Hire This Person
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- MARK REVIEWED mini-dialog -->
      <Transition name="ss-modal-fade">
        <div v-if="reviewPop.open" class="ss-modal-backdrop ss-modal-backdrop-top" @click.self="reviewPop.open=false">
          <div class="ss-modal ss-modal-sm">
            <div class="ss-modal-header">
              <div class="ss-modal-title">Mark as Reviewed</div>
              <button class="ss-modal-close" @click="reviewPop.open=false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="ss-modal-body">
              <div class="ap-pop-notice ap-pop-notice-blue">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>You're marking <strong>{{ appModal.app?.name }}</strong>'s application as reviewed. This moves it out of the "New" queue.</span>
              </div>
              <div class="ss-modal-field" style="margin-top:14px;">
                <label class="ss-modal-label">Quick Note (optional)</label>
                <textarea class="ss-modal-textarea" v-model="reviewPop.note" rows="3" placeholder="e.g., Strong background, follow up next week…"></textarea>
              </div>
            </div>
            <div class="ss-modal-footer">
              <button class="btn btn-outline btn-sm" @click="reviewPop.open=false">Cancel</button>
              <button class="ss-modal-save-btn" @click="confirmMarkReviewed">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                Confirm
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- SHORTLIST mini-dialog -->
      <Transition name="ss-modal-fade">
        <div v-if="shortlistPop.open" class="ss-modal-backdrop ss-modal-backdrop-top" @click.self="shortlistPop.open=false">
          <div class="ss-modal ss-modal-sm">
            <div class="ss-modal-header">
              <div class="ss-modal-title">Shortlist Applicant</div>
              <button class="ss-modal-close" @click="shortlistPop.open=false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="ss-modal-body">
              <div class="ap-pop-notice ap-pop-notice-gold">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polygon points="12 2 15 8.5 22 9.3 17 14 18.2 21 12 17.5 5.8 21 7 14 2 9.3 9 8.5 12 2"/></svg>
                <span>Adding <strong>{{ appModal.app?.name }}</strong> to your shortlist moves them to the Shortlisted column in the Hiring Pipeline.</span>
              </div>
              <div class="ss-modal-field" style="margin-top:14px;">
                <label class="ss-modal-label">Shortlist Note (optional)</label>
                <textarea class="ss-modal-textarea" v-model="shortlistPop.note" rows="3" placeholder="e.g., Top candidate — strong billing background, HIPAA certified…"></textarea>
              </div>
              <div class="ss-modal-field" style="margin-top:12px;">
                <label class="ss-modal-label">Priority</label>
                <select class="ss-modal-select" v-model="shortlistPop.priority">
                  <option>High — contact first</option>
                  <option>Medium</option>
                  <option>Low</option>
                </select>
              </div>
            </div>
            <div class="ss-modal-footer">
              <button class="btn btn-outline btn-sm" @click="shortlistPop.open=false">Cancel</button>
              <button class="ss-modal-save-btn" @click="confirmShortlist">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polygon points="12 2 15 8.5 22 9.3 17 14 18.2 21 12 17.5 5.8 21 7 14 2 9.3 9 8.5 12 2"/></svg>
                Shortlist
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- SCHEDULE INTERVIEW mini-dialog -->
      <Transition name="ss-modal-fade">
        <div v-if="schedulePop.open" class="ss-modal-backdrop ss-modal-backdrop-top" @click.self="schedulePop.open=false">
          <div class="ss-modal ss-modal-sm">
            <div class="ss-modal-header">
              <div>
                <div class="ss-modal-title">Schedule Interview</div>
                <div class="ss-modal-sub">With {{ appModal.app?.name }} · {{ appModal.app?.appliedFor }}</div>
              </div>
              <button class="ss-modal-close" @click="schedulePop.open=false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="ss-modal-body">
              <div class="ss-modal-field">
                <label class="ss-modal-label">Interview Type</label>
                <div class="ap-type-btns">
                  <button v-for="t in ['Video Call','Phone Call','In Person']" :key="t" class="ap-type-btn" :class="{ active: schedulePop.type === t }" @click="schedulePop.type = t">{{ t }}</button>
                </div>
              </div>
              <div class="ss-modal-row">
                <div class="ss-modal-field">
                  <label class="ss-modal-label">Date</label>
                  <input class="ss-modal-input" type="date" v-model="schedulePop.date">
                </div>
                <div class="ss-modal-field">
                  <label class="ss-modal-label">Time</label>
                  <input class="ss-modal-input" type="time" v-model="schedulePop.time">
                </div>
              </div>
              <div class="ss-modal-row">
                <div class="ss-modal-field">
                  <label class="ss-modal-label">Duration</label>
                  <select class="ss-modal-select" v-model="schedulePop.duration">
                    <option>30 minutes</option><option>45 minutes</option><option>60 minutes</option>
                  </select>
                </div>
                <div class="ss-modal-field">
                  <label class="ss-modal-label">Video Link (optional)</label>
                  <input class="ss-modal-input" v-model="schedulePop.link" placeholder="Zoom, Google Meet, Teams…">
                </div>
              </div>
              <div class="ss-modal-field">
                <label class="ss-modal-label">Notes to Applicant</label>
                <textarea class="ss-modal-textarea" v-model="schedulePop.notes" rows="3" placeholder="What to prepare, what to expect…"></textarea>
              </div>
            </div>
            <div class="ss-modal-footer">
              <button class="btn btn-outline btn-sm" @click="schedulePop.open=false">Cancel</button>
              <button class="ss-modal-save-btn" @click="confirmSchedule">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Send Invite
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- REJECT mini-dialog -->
      <Transition name="ss-modal-fade">
        <div v-if="rejectPop.open" class="ss-modal-backdrop ss-modal-backdrop-top" @click.self="rejectPop.open=false">
          <div class="ss-modal ss-modal-sm">
            <div class="ss-modal-header">
              <div class="ss-modal-title">Reject Application</div>
              <button class="ss-modal-close" @click="rejectPop.open=false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="ss-modal-body">
              <div class="ap-pop-notice ap-pop-notice-red">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span><strong>{{ rejectPop.name }}</strong> will be notified that their application was not selected. This cannot be undone.</span>
              </div>
              <div class="ss-modal-field" style="margin-top:14px;">
                <label class="ss-modal-label">Reason for Rejection <span style="color:var(--red)">*</span></label>
                <select class="ss-modal-select" v-model="rejectPop.reason">
                  <option value="">Select a reason…</option>
                  <option>Rate is outside our budget</option>
                  <option>Decided to go with another candidate</option>
                  <option>Position filled internally</option>
                  <option>Not enough experience</option>
                  <option>Other</option>
                </select>
              </div>
              <div class="ss-modal-field" style="margin-top:12px;">
                <label class="ss-modal-label">Message to Applicant (optional)</label>
                <textarea class="ss-modal-textarea" v-model="rejectPop.message" rows="3" placeholder="Thank you for applying. We've decided to move forward with another candidate…"></textarea>
              </div>
            </div>
            <div class="ss-modal-footer">
              <button class="btn btn-outline btn-sm" @click="rejectPop.open=false">Cancel</button>
              <button class="ss-modal-close-posting-btn" style="background:var(--red);color:#fff;border-color:var(--red);" @click="confirmReject">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Reject Application
              </button>
            </div>
          </div>
        </div>
      </Transition>
      <Transition name="ss-modal-fade">
        <div v-if="editModal.open" class="ss-modal-backdrop" @click.self="closeEditModal">
          <div class="ss-modal" role="dialog" aria-modal="true">
            <!-- Header -->
            <div class="ss-modal-header">
              <div>
                <div class="ss-modal-title">Edit Job Posting</div>
                <div class="ss-modal-sub">{{ editModal.title }} · {{ editModal.status }}</div>
              </div>
              <button class="ss-modal-close" @click="closeEditModal">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>

            <!-- Body -->
            <div class="ss-modal-body">
              <div class="ss-modal-row">
                <div class="ss-modal-field">
                  <label class="ss-modal-label">Job Title</label>
                  <input class="ss-modal-input" v-model="editModal.title" placeholder="e.g. Legal Counsel">
                </div>
                <div class="ss-modal-field">
                  <label class="ss-modal-label">Budget</label>
                  <input class="ss-modal-input" v-model="editModal.budget" placeholder="e.g. $200/hr">
                </div>
              </div>
              <div class="ss-modal-row">
                <div class="ss-modal-field">
                  <label class="ss-modal-label">Category</label>
                  <select class="ss-modal-select" v-model="editModal.category">
                    <option>Legal</option>
                    <option>Legal / Healthcare Law</option>
                    <option>Marketing</option>
                    <option>IT</option>
                    <option>Billing</option>
                    <option>Admin</option>
                    <option>HR</option>
                    <option>Finance</option>
                    <option>Operations</option>
                  </select>
                </div>
                <div class="ss-modal-field">
                  <label class="ss-modal-label">Status</label>
                  <select class="ss-modal-select" v-model="editModal.status">
                    <option>DRAFT</option>
                    <option>ACTIVE</option>
                    <option>PAUSED</option>
                    <option>CLOSED</option>
                  </select>
                </div>
              </div>
              <div class="ss-modal-field">
                <label class="ss-modal-label">Description</label>
                <textarea class="ss-modal-textarea" v-model="editModal.description" rows="5" placeholder="Describe the role, responsibilities, and requirements…"></textarea>
              </div>
            </div>

            <!-- Footer -->
            <div class="ss-modal-footer">
              <button class="btn btn-outline btn-sm" @click="closeEditModal">Cancel</button>
              <button class="ss-modal-close-posting-btn" @click="closePosting">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Close Posting
              </button>
              <button class="ss-modal-save-btn" @click="saveEditModal">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Changes
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- ACTIVE CONTRACT MODAL -->
      <Transition name="ss-modal-fade">
        <div v-if="contractModal.open" class="ss-modal-backdrop" @click.self="contractModal.open=false">
          <div class="ss-modal ss-modal-sm" role="dialog">
            <div class="ss-modal-header">
              <div>
                <div class="ss-modal-title">Active Contract</div>
                <div class="ss-modal-sub" style="display:flex;align-items:center;gap:6px;margin-top:4px;">
                  <span style="color:var(--gold-dark);font-weight:600;">{{ contractModal.name }}</span>
                  <span style="color:var(--text-4);">·</span>
                  <span>{{ contractModal.role }}</span>
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                  <span style="font-size:11px;color:var(--text-4);">Started {{ contractModal.started }}</span>
                </div>
              </div>
              <button class="ss-modal-close" @click="contractModal.open=false">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="ss-modal-body">
              <div class="contract-meta-grid">
                <div class="contract-meta-cell">
                  <div class="contract-meta-label">RATE</div>
                  <div class="contract-meta-val contract-meta-green">{{ contractModal.rate }}</div>
                </div>
                <div class="contract-meta-cell">
                  <div class="contract-meta-label">STATUS</div>
                  <div class="contract-meta-val contract-meta-green">Active</div>
                </div>
                <div class="contract-meta-cell">
                  <div class="contract-meta-label">STARTED</div>
                  <div class="contract-meta-val">{{ contractModal.started }}</div>
                </div>
                <div class="contract-meta-cell">
                  <div class="contract-meta-label">NEXT INVOICE</div>
                  <div class="contract-meta-val">{{ contractModal.nextInvoice }}</div>
                </div>
              </div>
              <div class="contract-scope">
                <span class="contract-scope-label">Scope of Work:</span> {{ contractModal.scope }}
              </div>
              <div class="contract-badges">
                <span class="contract-badge contract-badge-outline">NDA SIGNED</span>
                <span class="contract-badge contract-badge-outline">BAA SIGNED</span>
              </div>
            </div>
            <div class="ss-modal-footer">
              <button class="btn btn-outline btn-sm" @click="contractModal.open=false">Close</button>
              <button class="ss-modal-close-posting-btn" @click="showToast('Downloading PDF…','info')">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Download PDF
              </button>
              <button class="ss-modal-close-posting-btn" style="background:var(--red);color:#fff;border-color:var(--red);" @click="endContract">
                End Contract
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Components/AppLayout.vue';

defineProps({ user: Object });

// ── Toast ──
const toasts = ref([]);
let toastId = 0;
function showToast(msg, type = 'info') {
  const id = ++toastId;
  toasts.value.push({ id, msg, type });
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id); }, 3500);
}

// ── Tabs ──
const svgStr = (p) => p;
const tabs = [
  { id: 'postings',     label: 'My Postings',      count: 6,  icon: svgStr('<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>') },
  { id: 'applications', label: 'Applications',     count: 4,  icon: svgStr('<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>') },
  { id: 'pipeline',     label: 'Hiring Pipeline',  count: null, icon: svgStr('<circle cx="12" cy="5" r="2"/><circle cx="5" cy="19" r="2"/><circle cx="19" cy="19" r="2"/><path d="M12 7v6M12 13l-6 4M12 13l6 4"/>') },
  { id: 'hired',        label: 'Hired',            count: 2,  icon: svgStr('<polyline points="20 6 9 17 4 12"/>') },
];
const activeTab = ref('postings');

// ── Filters ──
const filters = [
  { id: 'all',    label: 'All',    count: 6 },
  { id: 'active', label: 'Active', count: 3 },
  { id: 'draft',  label: 'Draft',  count: 1 },
  { id: 'paused', label: 'Paused', count: 1 },
  { id: 'closed', label: 'Closed', count: 1 },
];
const activeFilter = ref('all');

// ── Jobs data ──
const jobs = ref([
  { id: 1, title: 'Legal Counsel – Healthcare Contracts',          category: 'Legal',     budget: '$200/hr',    applicants: 0, newApplicants: '0 new',   status: 'DRAFT',  posted: 'Jun 7, 2026',  location: 'Hybrid' },
  { id: 2, title: 'Practice Marketing Consultant',                  category: 'Marketing', budget: '$3,000/mo',  applicants: 0, newApplicants: '0 new',   status: 'ACTIVE', posted: 'Jun 5, 2026',  location: 'Remote' },
  { id: 3, title: 'HIPAA-Compliant IT Support Engineer',            category: 'IT',        budget: '$97/hr',     applicants: 2, newApplicants: '2 new',   status: 'ACTIVE', posted: 'Jun 3, 2026',  location: 'Remote' },
  { id: 4, title: 'Medical Billing Specialist — Psychiatry Practice', category: 'Billing', budget: '$2,500/mo',  applicants: 3, newApplicants: '2 new',   status: 'ACTIVE', posted: 'Jun 1, 2026',  location: 'Remote' },
  { id: 5, title: 'Front-Desk Coordinator',                         category: 'Admin',     budget: '$22/hr',     applicants: 0, newApplicants: 'paused',  status: 'PAUSED', posted: 'Dec 10, 2025', location: 'On site' },
  { id: 6, title: 'Virtual Administrative Assistant',               category: 'Admin',     budget: '$1,800/mo',  applicants: 1, newApplicants: '1 hired', status: 'CLOSED', posted: 'Nov 1, 2025',  location: 'Remote' },
]);

const filteredJobs = computed(() => {
  if (activeFilter.value === 'all') return jobs.value;
  return jobs.value.filter(j => j.status.toLowerCase() === activeFilter.value);
});

function publishJob(job) { job.status = 'ACTIVE'; showToast(job.title + ' published', 'success'); }
function pauseJob(job)   { job.status = 'PAUSED'; showToast(job.title + ' paused', 'info'); }
function resumeJob(job)  { job.status = 'ACTIVE'; showToast(job.title + ' resumed', 'success'); }

function openRequestModal() { showToast('Opening request support form', 'info'); }

// ── Edit modal ──
const editModal = ref({ open: false, id: null, title: '', budget: '', category: '', status: '', description: '' });

function openEditModal(job) {
  editModal.value = {
    open: true,
    id: job.id,
    title: job.title,
    budget: job.budget,
    category: job.category,
    status: job.status,
    description: job.description || '',
  };
}

function closeEditModal() { editModal.value.open = false; }

function saveEditModal() {
  const job = jobs.value.find(j => j.id === editModal.value.id);
  if (job) {
    job.title    = editModal.value.title;
    job.budget   = editModal.value.budget;
    job.category = editModal.value.category;
    job.status   = editModal.value.status;
    job.description = editModal.value.description;
  }
  closeEditModal();
  showToast('Changes saved', 'success');
}

function closePosting() {
  const job = jobs.value.find(j => j.id === editModal.value.id);
  if (job) job.status = 'CLOSED';
  closeEditModal();
  showToast('Posting closed', 'info');
}

// ── Hiring Pipeline ──
const pipelineCols = [
  { id: 'new',        label: 'NEW',        icon: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>' },
  { id: 'reviewing',  label: 'REVIEWING',  icon: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>' },
  { id: 'shortlisted',label: 'SHORTLISTED',icon: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polygon points="12 2 15 8.5 22 9.3 17 14 18.2 21 12 17.5 5.8 21 7 14 2 9.3 9 8.5 12 2"/></svg>' },
  { id: 'interview',  label: 'INTERVIEW',  icon: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>' },
  { id: 'hired',      label: 'HIRED',      icon: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>' },
];

const pipelineApplicants = ref([
  { id: 101, name: 'Kevin Osei',     initials: 'KO', avatarColor: '#a0813e', appliedFor: 'Credentialing Consultant', rate: '$55/hr',  rating: 4.9, stage: 'new' },
  { id: 102, name: 'Jae-Won Park',   initials: 'JP', avatarColor: '#4a6c8c', appliedFor: 'Practice Consultant',      rate: '$220/hr', rating: 4.8, stage: 'new' },
  { id: 103, name: 'Bright Minds',   initials: 'BM', avatarColor: '#4a7a6a', appliedFor: 'Admin Agency',             rate: '$35/hr',  rating: 4.6, stage: 'new' },
  { id: 104, name: 'Priya Singh',    initials: 'PS', avatarColor: '#8c5a8c', appliedFor: 'Billing Specialist',       rate: '$65/hr',  rating: 4.4, stage: 'new' },
  { id: 105, name: 'TechFlow LLC',   initials: 'TL', avatarColor: '#4a6c8c', appliedFor: 'IT Agency',                rate: '$180/hr', rating: 4.3, stage: 'new' },
  { id: 106, name: 'Carla Davis',    initials: 'CD', avatarColor: '#7a5c4a', appliedFor: 'Medical Coder',            rate: '$42/hr',  rating: 4.5, stage: 'new' },
  { id: 107, name: 'Riya Patel',     initials: 'RP', avatarColor: '#a0813e', appliedFor: 'Marketing Solopreneur',    rate: '$70/hr',  rating: 4.8, stage: 'reviewing' },
  { id: 108, name: 'CloudMed IT',    initials: 'CI', avatarColor: '#4a6c8c', appliedFor: 'IT Agency',                rate: '$200/hr', rating: 4.5, stage: 'reviewing' },
  { id: 109, name: 'Daniel Torres',  initials: 'DT', avatarColor: '#7a5c4a', appliedFor: 'Healthcare CPA',           rate: '$70/hr',  rating: 4.8, stage: 'reviewing' },
  { id: 110, name: 'Nova Agency',    initials: 'NA', avatarColor: '#4a7a6a', appliedFor: 'Marketing Agency',         rate: '$130/hr', rating: 4.4, stage: 'reviewing' },
  { id: 111, name: 'Marisol Vega',   initials: 'MV', avatarColor: '#a0813e', appliedFor: 'Billing Specialist',       rate: '$85/hr',  rating: 4.9, stage: 'shortlisted' },
  { id: 112, name: 'Lena Hoffmann',  initials: 'LH', avatarColor: '#8c5a4a', appliedFor: 'Brand Designer',           rate: '$90/hr',  rating: 4.7, stage: 'shortlisted' },
  { id: 113, name: 'Sandra Kim',     initials: 'SK', avatarColor: '#4a6c8c', appliedFor: 'Healthcare CPA',           rate: '$145/hr', rating: 4.7, stage: 'shortlisted' },
  { id: 114, name: 'Apex Billing',   initials: 'AB', avatarColor: '#4a7a6a', appliedFor: 'Billing Agency',           rate: '$150/hr', rating: 4.7, stage: 'interview' },
  { id: 115, name: 'StaffLink HR',   initials: 'SH', avatarColor: '#7a5c4a', appliedFor: 'HR Firm',                  rate: '$120/hr', rating: 4.8, stage: 'interview' },
  { id: 116, name: 'Janelle Torres', initials: 'JT', avatarColor: '#a0813e', appliedFor: 'Virtual Admin',            rate: '$22/hr',  rating: 4.6, stage: 'hired' },
]);

function pipelineCards(stageId) {
  return pipelineApplicants.value.filter(a => a.stage === stageId);
}
const applications = ref([
  { id: 1, name: 'Acme Practice Services', initials: 'AP', type: 'Agency',     avatarColor: '#a0813e', appliedFor: 'HIPAA-Compliant IT Support Engineer',            rate: '$85/hr',     status: 'PENDING' },
  { id: 2, name: 'TechCare Solutions',     initials: 'TC', type: 'Agency',     avatarColor: '#4a6c8c', appliedFor: 'HIPAA-Compliant IT Support Engineer',            rate: '$115/hr',    status: 'PENDING' },
  { id: 3, name: 'Acme Practice Services', initials: 'AP', type: 'Agency',     avatarColor: '#a0813e', appliedFor: 'Medical Billing Specialist — Psychiatry Practice', rate: '$2,500/mo',  status: 'PENDING' },
  { id: 4, name: 'MedBill Pro',            initials: 'MS', type: 'Agency',     avatarColor: '#4a7a6a', appliedFor: 'Medical Billing Specialist — Psychiatry Practice', rate: '$2,400/mo',  status: 'PENDING' },
  { id: 5, name: 'Jamal Washington',       initials: 'JW', type: 'Freelancer', avatarColor: '#6a4c8c', appliedFor: 'Medical Billing Specialist — Psychiatry Practice', rate: '$2,200/mo',  status: 'ACCEPTED' },
  { id: 6, name: 'Acme Practice Services', initials: 'AP', type: 'Agency',     avatarColor: '#a0813e', appliedFor: 'Virtual Administrative Assistant',                rate: '$1,800/mo',  status: 'ACCEPTED' },
]);

function quickAccept(app) { app.status = 'ACCEPTED'; showToast(app.name + ' accepted', 'success'); }

// ── Applicant profile modal ──
const appModal = ref({ open: false, app: null, notes: '' });
function openApplicantModal(app) { appModal.value = { open: true, app, notes: '' }; }
function hireApplicant() {
  if (appModal.value.app) appModal.value.app.status = 'ACCEPTED';
  appModal.value.open = false;
  showToast('Hired ' + appModal.value.app?.name, 'success');
}

// ── Mark Reviewed pop ──
const reviewPop = ref({ open: false, note: '' });
function openMarkReviewedPop() { reviewPop.value = { open: true, note: '' }; }
function confirmMarkReviewed() {
  reviewPop.value.open = false;
  showToast('Marked as reviewed', 'success');
}

// ── Shortlist pop ──
const shortlistPop = ref({ open: false, note: '', priority: 'High — contact first' });
function openShortlistPop() { shortlistPop.value = { open: true, note: '', priority: 'High — contact first' }; }
function confirmShortlist() {
  shortlistPop.value.open = false;
  showToast(appModal.value.app?.name + ' shortlisted', 'success');
}

// ── Schedule Interview pop ──
const schedulePop = ref({ open: false, type: 'Video Call', date: '', time: '10:00', duration: '30 minutes', link: '', notes: '' });
function openSchedulePop() { schedulePop.value = { open: true, type: 'Video Call', date: '', time: '10:00', duration: '30 minutes', link: '', notes: '' }; }
function confirmSchedule() {
  schedulePop.value.open = false;
  showToast('Interview invite sent', 'success');
}

// ── Reject pop ──
const rejectPop = ref({ open: false, name: '', appId: null, reason: '', message: '' });
function openRejectPop(app) {
  rejectPop.value = { open: true, name: app.name, appId: app.id, reason: '', message: '' };
}
function confirmReject() {
  const app = applications.value.find(a => a.id === rejectPop.value.appId);
  if (app) app.status = 'REJECTED';
  rejectPop.value.open = false;
  appModal.value.open = false;
  showToast(rejectPop.value.name + ' rejected', 'info');
}

// ── Hired partners ──
const hiredPartners = ref([
  {
    id: 1, name: 'Jamal Washington', initials: 'JW', type: 'Freelancer',
    avatarColor: '#6a4c8c',
    role: 'Medical Billing Specialist — Psychiatry Practice',
    rate: '$2,200/month', started: 'Feb 1, 2026', nextInvoice: 'Mar 1, 2026',
    scope: 'Full-cycle medical billing including charge entry, claim submission, insurance follow-up, denial management, payment posting, and monthly reporting.',
  },
  {
    id: 2, name: 'Acme Practice Services', initials: 'AP', type: 'Agency',
    avatarColor: '#a0813e',
    role: 'Virtual Administrative Assistant',
    rate: '$1,800/month', started: 'Jan 15, 2026', nextInvoice: 'Feb 15, 2026',
    scope: 'Scheduling, patient intake coordination, insurance verification, EHR data entry, and front-desk support for the practice.',
  },
]);

const contractModal = ref({ open: false, name: '', role: '', rate: '', started: '', nextInvoice: '', scope: '' });

function openContractModal(partner) {
  contractModal.value = {
    open: true,
    name: partner.name,
    role: partner.role,
    rate: partner.rate,
    started: partner.started,
    nextInvoice: partner.nextInvoice,
    scope: partner.scope,
  };
}

function endContract() {
  contractModal.value.open = false;
  showToast('Contract ended', 'info');
}

function goToMessages(partner) {
  router.visit('/provider/messages');
}
</script>

<style scoped>
/* Hero */
.ss-hero { display:flex; align-items:flex-start; justify-content:space-between; gap:20px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:24px 26px; margin-bottom:14px; box-shadow:var(--shadow-xs); flex-wrap:wrap; }
.ss-hero-left { min-width:0; }
.ss-hero-eyebrow { font-family:var(--font-sans); font-size:10px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--gold-dark); margin-bottom:6px; }
.ss-hero-title { font-family:var(--font-serif); font-size:24px; font-weight:700; color:var(--text); line-height:1.2; margin:0 0 8px; }
.ss-hero-sub { font-family:var(--font-sans); font-size:13px; color:var(--text-3); margin:0; line-height:1.5; }
.ss-hero-actions { display:flex; gap:8px; flex-shrink:0; align-items:center; }
.ss-btn-icon { display:inline-flex; align-items:center; gap:6px; }

/* Gold "Request Support" button */
.ss-request-btn {
  display:inline-flex; align-items:center; gap:6px;
  background:var(--gold-dark); color:#fff;
  border:1px solid var(--gold-dark);
  padding:6px 13px; font-size:12.5px; font-weight:600;
  border-radius:var(--radius-sm); cursor:pointer; transition:background 0.15s;
}
.ss-request-btn:hover { background:var(--gold); border-color:var(--gold); }

/* Stat cards */
.ss-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:14px; }
.ss-stat { display:flex; align-items:center; gap:14px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 18px; box-shadow:var(--shadow-xs); }
.ss-stat-icon { width:36px; height:36px; border-radius:8px; background:rgba(160,129,62,0.1); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ss-stat-icon-green { background:var(--green-light); color:var(--green-dark); }
.ss-stat-value { font-family:var(--font-serif); font-size:22px; font-weight:700; color:var(--text); line-height:1; }
.ss-stat-label { font-family:var(--font-sans); font-size:11px; color:var(--text-4); margin-top:3px; }

/* Tabs */
.ss-tabs { display:flex; align-items:center; gap:2px; border-bottom:1px solid var(--border); margin-bottom:18px; }
.ss-tab { display:inline-flex; align-items:center; gap:7px; padding:9px 14px; font-family:var(--font-sans); font-size:13px; font-weight:500; color:var(--text-3); background:transparent; border:none; border-bottom:2px solid transparent; cursor:pointer; transition:color 0.15s, border-color 0.15s; margin-bottom:-1px; }
.ss-tab:hover { color:var(--text); }
.ss-tab.active { color:var(--text); font-weight:600; border-bottom-color:var(--gold-dark); }
.ss-tab-count { font-size:11px; font-weight:700; background:var(--surface-3); color:var(--text-2); border-radius:99px; padding:1px 7px; }
.ss-tab.active .ss-tab-count { background:var(--gold-dark); color:#fff; }

/* Section header */
.ss-section-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
.ss-section-title { display:inline-flex; align-items:center; gap:8px; font-family:var(--font-sans); font-size:14px; font-weight:600; color:var(--text); }
.ss-section-actions { display:flex; gap:8px; }

/* Filter pill row — single rounded container */
.ss-filters {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  margin-bottom: 14px;
  background: #e8e2d8;
  border-radius: 99px;
  padding: 4px;
}
.ss-filter {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 6px 14px;
  font-family: var(--font-sans);
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  background: transparent;
  border: none;
  border-radius: 99px;
  cursor: pointer;
  transition: color 0.15s, background 0.15s;
  white-space: nowrap;
}
.ss-filter:hover { background: rgba(0,0,0,0.05); }
.ss-filter.active {
  background: var(--gold-dark);
  color: #fff;
}
.ss-filter.active:hover { background: var(--gold); }
.ss-filter-label { font-weight: 600; }
/* Count bubble */
.ss-filter-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  font-size: 11px;
  font-weight: 700;
  border-radius: 99px;
  background: #d4cdc3;
  color: var(--text-2);
  line-height: 1;
}
.ss-filter.active .ss-filter-count {
  background: rgba(255,255,255,0.30);
  color: #fff;
}

/* Table */
.ss-table-wrap { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-xs); margin-bottom:14px; }
.ss-table { width:100%; border-collapse:collapse; }
.ss-table thead tr { border-bottom:1px solid var(--border); background:var(--surface-2); }
.ss-table th { padding:10px 16px; font-family:var(--font-sans); font-size:10px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:var(--text-4); text-align:left; white-space:nowrap; }
.ss-table tbody tr { border-bottom:1px solid var(--border); transition:background 0.12s; }
.ss-table tbody tr:last-child { border-bottom:none; }
.ss-table tbody tr:hover { background:var(--surface-2); }
.ss-table td { padding:12px 16px; font-family:var(--font-sans); font-size:13px; color:var(--text); vertical-align:middle; }
.ss-job-title { font-weight:600; color:var(--text); margin-bottom:3px; }
.ss-job-meta { display:inline-flex; align-items:center; gap:5px; font-size:11px; color:var(--text-4); }
.ss-td-cat { color:var(--text-3); }
.ss-td-budget-active { font-weight:600; color:var(--green-dark); }
.ss-td-budget-plain  { font-weight:500; color:var(--text-2); }

/* Green Publish button */
.ss-publish-btn {
  padding:4px 11px; font-size:12px; font-weight:700;
  background:var(--green-dark); color:#fff;
  border:1px solid var(--green-dark); border-radius:6px;
  cursor:pointer; transition:background 0.15s; white-space:nowrap;
}
.ss-publish-btn:hover { background:var(--green); border-color:var(--green); }
.ss-td-applicants { font-size:13px; }
.ss-applicant-sub { font-size:11px; color:var(--text-4); margin-top:2px; }

/* Status badges */
.ss-status { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:700; padding:3px 9px; border-radius:99px; }
.ss-status::before { content:''; display:inline-block; width:6px; height:6px; border-radius:50%; }
.ss-status-active  { background:rgba(76,175,125,0.12); color:var(--green-dark); }
.ss-status-active::before  { background:var(--green-dark); }
.ss-status-draft   { background:var(--surface-3); color:var(--text-3); }
.ss-status-draft::before   { background:var(--text-4); }
.ss-status-paused  { background:rgba(232,169,74,0.15); color:var(--orange-dark); }
.ss-status-paused::before  { background:var(--orange); }
.ss-status-closed  { background:rgba(160,45,34,0.1); color:var(--red); }
.ss-status-closed::before  { background:var(--red); }

/* Action buttons */
.ss-actions { display:flex; align-items:center; gap:6px; }
.ss-act-btn { width:30px; height:30px; padding:0; display:inline-flex; align-items:center; justify-content:center; background:var(--surface); border:1px solid var(--border); border-radius:6px; cursor:pointer; color:var(--text-3); transition:all 0.15s; }
.ss-act-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); background:rgba(160,129,62,0.06); }
.ss-act-danger:hover { border-color:var(--red); color:var(--red); background:var(--red-light); }

/* Pagination */
.ss-pagination { display:flex; align-items:center; justify-content:space-between; padding:6px 2px; }
.ss-pagination-info { font-family:var(--font-sans); font-size:12px; color:var(--text-4); }
.ss-pagination-pages { display:flex; align-items:center; gap:4px; }
.ss-page-btn { width:30px; height:30px; padding:0; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--border); border-radius:6px; background:var(--surface); font-family:var(--font-sans); font-size:12px; font-weight:600; color:var(--text-3); cursor:pointer; transition:all 0.15s; }
.ss-page-btn.active { background:var(--text); color:#fff; border-color:var(--text); }
.ss-page-next { color:var(--text-3); }
.ss-page-next:hover { border-color:var(--gold-dark); color:var(--gold-dark); }

/* Empty states */
.ss-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; padding:60px 20px; color:var(--text-4); }
.ss-empty p { font-family:var(--font-sans); font-size:14px; margin:0; }

@media(max-width:860px) { .ss-stats { grid-template-columns:repeat(2,1fr); } }
@media(max-width:540px) { .ss-stats { grid-template-columns:1fr 1fr; } .ss-table th:nth-child(4), .ss-table td:nth-child(4) { display:none; } }

/* Row hover */
.ss-row-clickable { cursor: pointer; }

/* Modal backdrop — blurred */
.ss-modal-backdrop {
  position: fixed; inset: 0; z-index: 1000;
  background: rgba(30, 28, 26, 0.45);
  backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  padding: 20px;
}

/* Modal box */
.ss-modal {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  box-shadow: 0 24px 64px rgba(30,28,26,0.22);
  width: 100%; max-width: 580px;
  overflow: hidden;
}

/* Header */
.ss-modal-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  padding: 20px 22px 16px;
  border-bottom: 1px solid var(--border);
}
.ss-modal-title {
  font-family: var(--font-serif); font-size: 18px; font-weight: 700;
  color: var(--text); line-height: 1.2; margin-bottom: 3px;
}
.ss-modal-sub { font-family: var(--font-sans); font-size: 12px; color: var(--text-4); }
.ss-modal-close {
  width: 28px; height: 28px; padding: 0;
  display: inline-flex; align-items: center; justify-content: center;
  border: 1px solid var(--border); border-radius: 6px;
  background: var(--surface); color: var(--text-3); cursor: pointer;
  transition: all 0.15s; flex-shrink: 0;
}
.ss-modal-close:hover { border-color: var(--text); color: var(--text); }

/* Body */
.ss-modal-body { padding: 20px 22px; display: flex; flex-direction: column; gap: 16px; }
.ss-modal-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.ss-modal-field { display: flex; flex-direction: column; gap: 6px; }
.ss-modal-label {
  font-family: var(--font-sans); font-size: 12px; font-weight: 600;
  color: var(--text-2); text-transform: uppercase; letter-spacing: 0.04em;
}
.ss-modal-input,
.ss-modal-select,
.ss-modal-textarea {
  font-family: var(--font-sans); font-size: 13.5px; color: var(--text);
  background: var(--surface); border: 1.5px solid var(--border);
  border-radius: 8px; padding: 9px 12px;
  transition: border-color 0.15s, box-shadow 0.15s; outline: none; width: 100%;
}
.ss-modal-input:focus, .ss-modal-select:focus, .ss-modal-textarea:focus {
  border-color: var(--gold); box-shadow: 0 0 0 3px rgba(196,169,106,0.18);
}
.ss-modal-textarea { resize: vertical; min-height: 110px; line-height: 1.6; }
.ss-modal-select { appearance: none; -webkit-appearance: none; cursor: pointer; }

/* Footer */
.ss-modal-footer {
  display: flex; align-items: center; justify-content: flex-end;
  gap: 8px; padding: 14px 22px;
  border-top: 1px solid var(--border);
  background: var(--surface-2);
}
.ss-modal-close-posting-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 14px; font-family: var(--font-sans); font-size: 12.5px; font-weight: 600;
  background: var(--surface); color: var(--text-2);
  border: 1.5px solid var(--border); border-radius: var(--radius-sm);
  cursor: pointer; transition: all 0.15s;
}
.ss-modal-close-posting-btn:hover { border-color: var(--red); color: var(--red); background: var(--red-light); }
.ss-modal-save-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 16px; font-family: var(--font-sans); font-size: 12.5px; font-weight: 600;
  background: var(--gold-dark); color: #fff;
  border: 1px solid var(--gold-dark); border-radius: var(--radius-sm);
  cursor: pointer; transition: background 0.15s;
}
.ss-modal-save-btn:hover { background: var(--gold); border-color: var(--gold); }

/* Modal transition */
.ss-modal-fade-enter-active, .ss-modal-fade-leave-active { transition: opacity 0.2s ease; }
.ss-modal-fade-enter-active .ss-modal, .ss-modal-fade-leave-active .ss-modal { transition: transform 0.2s ease; }
.ss-modal-fade-enter-from, .ss-modal-fade-leave-to { opacity: 0; }
.ss-modal-fade-enter-from .ss-modal { transform: translateY(-12px) scale(0.98); }
.ss-modal-fade-leave-to .ss-modal { transform: translateY(6px) scale(0.98); }

/* ── Hiring Pipeline Kanban ── */
.hp-board { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; align-items:start; }
.hp-col { background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius-lg); padding:12px; min-height:200px; }
.hp-col-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
.hp-col-title-row { display:flex; align-items:center; gap:7px; }
.hp-col-icon { display:flex; align-items:center; color:var(--gold-dark); }
.hp-col-name { font-family:var(--font-sans); font-size:10.5px; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:var(--text-3); }
.hp-col-count { font-family:var(--font-sans); font-size:11px; font-weight:700; background:var(--surface-3); color:var(--text-2); border-radius:99px; padding:1px 8px; }
.hp-cards { display:flex; flex-direction:column; gap:8px; }
.hp-card { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:12px 13px; cursor:pointer; transition:box-shadow 0.15s, transform 0.15s; }
.hp-card:hover { box-shadow:var(--shadow-sm); transform:translateY(-2px); }
.hp-card-top { display:flex; align-items:flex-start; gap:10px; margin-bottom:10px; }
.hp-avatar { width:34px; height:34px; border-radius:50%; color:#fff; font-family:var(--font-sans); font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.hp-card-name { font-family:var(--font-sans); font-size:13px; font-weight:600; color:var(--text); line-height:1.3; }
.hp-card-role { font-family:var(--font-sans); font-size:11px; color:var(--text-4); margin-top:1px; }
.hp-card-bottom { display:flex; align-items:center; justify-content:space-between; }
.hp-card-rating { display:inline-flex; align-items:center; gap:4px; font-family:var(--font-sans); font-size:12px; color:var(--text-3); }
.hp-card-rating svg { color:var(--gold-dark); }
.hp-card-rate { font-family:var(--font-sans); font-size:12px; font-weight:600; color:var(--green-dark); }
@media(max-width:1000px) { .hp-board { grid-template-columns:repeat(3,1fr); } }
@media(max-width:680px)  { .hp-board { grid-template-columns:repeat(2,1fr); } }

/* ── Applications tab ── */
.ss-filter-select { font-family:var(--font-sans); font-size:12.5px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:var(--radius-sm); padding:6px 28px 6px 10px; cursor:pointer; outline:none; appearance:none; -webkit-appearance:none; }
.ss-unreviewed-notice { display:flex; align-items:center; gap:10px; padding:10px 14px; background:var(--blue-light); border:1px solid var(--soft-blue); border-radius:8px; margin-bottom:14px; font-family:var(--font-sans); font-size:13px; color:var(--blue-dark); }
.ss-notice-link { color:var(--gold-dark); text-decoration:underline; cursor:pointer; }
.ss-applicant-row { display:flex; align-items:center; gap:10px; }
.ss-applicant-avatar { width:34px; height:34px; border-radius:8px; color:#fff; font-family:var(--font-sans); font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ss-applicant-name { font-weight:600; font-size:13px; color:var(--text); }
.ss-applicant-type { font-size:11px; color:var(--text-4); margin-top:1px; }
.ss-act-accept:hover { border-color:var(--green-dark); color:var(--green-dark); background:var(--green-light); }
.ss-status-pending  { background:rgba(232,169,74,0.15); color:var(--orange-dark); }
.ss-status-pending::before  { background:var(--orange); }
.ss-status-rejected { background:var(--red-light); color:var(--red); }
.ss-status-rejected::before { background:var(--red); }
.ss-status-accepted { background:var(--green-light); color:var(--green-dark); }
.ss-status-accepted::before { background:var(--green-dark); }

/* ── Wide modal (applicant profile) ── */
.ss-modal-wide { max-width: 680px; }
.ss-modal-sm   { max-width: 480px; }
.ss-modal-backdrop-top { z-index: 1010; }

/* Applicant profile modal internals */
.ap-profile-head { display:flex; gap:16px; margin-bottom:16px; }
.ap-avatar-lg { width:56px; height:56px; border-radius:50%; color:#fff; font-family:var(--font-sans); font-size:18px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ap-profile-info { min-width:0; }
.ap-name { font-family:var(--font-serif); font-size:18px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:6px; margin-bottom:2px; }
.ap-meta { font-family:var(--font-sans); font-size:12px; color:var(--text-4); margin-bottom:8px; }
.ap-tags-row { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:8px; }
.ap-info-item { display:inline-flex; align-items:center; gap:4px; font-family:var(--font-sans); font-size:11.5px; color:var(--text-3); }
.ap-badges-row { display:flex; gap:6px; flex-wrap:wrap; }
.ap-badge { display:inline-flex; align-items:center; font-family:var(--font-sans); font-size:10px; font-weight:700; letter-spacing:0.05em; padding:2px 8px; border-radius:99px; border:1px solid; }
.ap-badge-gold  { background:rgba(160,129,62,0.1); color:var(--gold-dark); border-color:var(--fade-gold); }
.ap-badge-green { background:var(--green-light); color:var(--green-dark); border-color:var(--soft-green); }
.ap-badge-blue  { background:var(--blue-light);  color:var(--blue-dark);  border-color:var(--soft-blue); }

.ap-stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:16px; }
.ap-stat { background:var(--surface-2); border:1px solid var(--border); border-radius:10px; padding:12px 14px; text-align:center; }
.ap-stat-val { font-family:var(--font-serif); font-size:17px; font-weight:700; color:var(--text); }
.ap-stat-lbl { font-family:var(--font-sans); font-size:10.5px; color:var(--text-4); margin-top:3px; }

.ap-section { margin-bottom:16px; }
.ap-section:last-child { margin-bottom:0; }
.ap-section-title { display:flex; align-items:center; gap:7px; font-family:var(--font-sans); font-size:12px; font-weight:700; color:var(--text-2); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:10px; }
.ap-cover-text { font-family:var(--font-sans); font-size:13px; color:var(--text-2); line-height:1.7; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px 16px; }

.ap-status-btns { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; }
.ap-status-btn { display:inline-flex; align-items:center; gap:6px; padding:6px 13px; font-family:var(--font-sans); font-size:12.5px; font-weight:600; border-radius:99px; border:1.5px solid; cursor:pointer; transition:all 0.15s; }
.ap-status-review   { background:rgba(160,129,62,0.08); color:var(--gold-dark); border-color:var(--fade-gold); }
.ap-status-review:hover { background:var(--gold-dark); color:#fff; }
.ap-status-shortlist { background:rgba(160,129,62,0.08); color:var(--gold-dark); border-color:var(--fade-gold); }
.ap-status-shortlist:hover { background:var(--gold-dark); color:#fff; }
.ap-status-schedule { background:var(--blue-light); color:var(--blue-dark); border-color:var(--soft-blue); }
.ap-status-schedule:hover { background:var(--blue-dark); color:#fff; }
.ap-status-reject   { background:var(--red-light); color:var(--red); border-color:var(--soft-red); }
.ap-status-reject:hover { background:var(--red); color:#fff; }
.ap-notes-label { font-family:var(--font-sans); font-size:12px; font-weight:600; color:var(--text-2); margin-bottom:6px; }

/* Pop notice banners */
.ap-pop-notice { display:flex; align-items:flex-start; gap:9px; padding:10px 13px; border-radius:8px; font-family:var(--font-sans); font-size:13px; line-height:1.5; }
.ap-pop-notice svg { flex-shrink:0; margin-top:1px; }
.ap-pop-notice-blue { background:var(--blue-light); border:1px solid var(--soft-blue); color:var(--blue-dark); }
.ap-pop-notice-gold { background:rgba(160,129,62,0.08); border:1px solid var(--fade-gold); color:var(--gold-dark); }
.ap-pop-notice-red  { background:var(--red-light); border:1px solid var(--soft-red); color:var(--red); }

/* Interview type buttons */
.ap-type-btns { display:flex; gap:6px; flex-wrap:wrap; }
.ap-type-btn { padding:6px 14px; font-family:var(--font-sans); font-size:12.5px; font-weight:500; border:1.5px solid var(--border); border-radius:99px; background:var(--surface); color:var(--text-3); cursor:pointer; transition:all 0.15s; }
.ap-type-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.ap-type-btn.active { background:var(--gold-dark); color:#fff; border-color:var(--gold-dark); }

/* ── Hired tab ── */
.hired-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:14px; margin-bottom:6px; }
.hired-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px; cursor:pointer; transition:box-shadow 0.15s,transform 0.15s; box-shadow:var(--shadow-xs); }
.hired-card:hover { box-shadow:var(--shadow-sm); transform:translateY(-2px); }
.hired-card-top { display:flex; align-items:flex-start; gap:12px; margin-bottom:14px; }
.hired-avatar { width:40px; height:40px; border-radius:10px; color:#fff; font-family:var(--font-sans); font-size:14px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.hired-name { font-family:var(--font-sans); font-size:13.5px; font-weight:700; color:var(--text); line-height:1.3; margin-bottom:3px; }
.hired-role { font-family:var(--font-sans); font-size:11.5px; color:var(--text-4); line-height:1.4; }
.hired-status-row { margin-bottom:12px; }
.hired-badge { display:inline-flex; align-items:center; gap:5px; font-family:var(--font-sans); font-size:10.5px; font-weight:700; color:var(--green-dark); background:var(--green-light); border:1px solid var(--soft-green); border-radius:99px; padding:3px 9px; }
.hired-badge svg { color:var(--green-dark); }
.hired-actions { display:flex; gap:6px; padding-top:10px; border-top:1px solid var(--border); }

/* Contract modal internals */
.contract-meta-grid { display:grid; grid-template-columns:1fr 1fr; gap:1px; background:var(--border); border:1px solid var(--border); border-radius:10px; overflow:hidden; margin-bottom:14px; }
.contract-meta-cell { background:var(--surface-2); padding:12px 14px; }
.contract-meta-label { font-family:var(--font-sans); font-size:10px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:var(--text-4); margin-bottom:4px; }
.contract-meta-val { font-family:var(--font-sans); font-size:14px; font-weight:600; color:var(--text); }
.contract-meta-green { color:var(--green-dark); }
.contract-scope { font-family:var(--font-sans); font-size:13px; color:var(--text-2); line-height:1.6; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:12px 14px; margin-bottom:12px; }
.contract-scope-label { font-weight:600; color:var(--text); }
.contract-badges { display:flex; gap:8px; flex-wrap:wrap; }
.contract-badge { display:inline-flex; align-items:center; font-family:var(--font-sans); font-size:11px; font-weight:600; padding:4px 10px; border-radius:99px; }
.contract-badge-outline { border:1.5px solid var(--border-dark); color:var(--text-3); background:var(--surface); }
</style>
