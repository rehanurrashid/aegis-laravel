<!--
  pages/provider/SupportServices.vue — "Support & Services" (job postings).
  Converted from job-postings.php. AEGIS_VUE_RULES.md governs every pattern here.

  Tabs: My Postings (full parity) · Applications · Hiring Pipeline · Hired
  (Applications / Pipeline / Hired are functional v1s wired to real data and
  real write routes; the rich modal-driven applicant workflows from the legacy
  page — Applicant Profile, Mark Reviewed, Shortlist, Reject, Schedule Interview,
  Contract view — ship in the next batch.)
-->
<template>
  <AppLayout>
    <AegisHeroBanner quiet eyebrow="Support &amp; Services" title="Support &amp; Services" subtitle="Find peer practitioners and business partners to strengthen your practice — all in one place.">
      <template #actions>
        <button class="btn-hero-ghost is-on-light" @click="router.visit(route('provider.activity') + '?event_type=job_postings')">
          <AegisIcon name="activity" :size="14" />
          Activity
        </button>
        <button class="btn-hero-solid is-on-light" @click="showPostJob = true">
          <AegisIcon name="plus" :size="14" />
          Request Support
        </button>
      </template>
    </AegisHeroBanner>

    <!-- KPI strip -->
    <div class="stat-chips-row" style="margin-top:18px;margin-bottom:18px">
      <div class="stat-chip">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="briefcase" :size="18" /></div>
        <div><div class="stat-chip-value">{{ stats.open }}</div><div class="stat-chip-label">Active Postings</div></div>
      </div>
      <div class="stat-chip">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="download" :size="18" /></div>
        <div><div class="stat-chip-value">{{ stats.total_proposals }}</div><div class="stat-chip-label">Total Applications</div></div>
      </div>
      <div class="stat-chip">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="clock" :size="18" /></div>
        <div><div class="stat-chip-value">{{ stats.pending_proposals }}</div><div class="stat-chip-label">Awaiting Review</div></div>
      </div>
      <!-- ── Top-level section switcher ──────────────────────────────────── -->
    <div class="ss-section-switcher" role="tablist">
      <button
        type="button" role="tab"
        class="ss-section-btn"
        :class="{ active: section === 'bp' }"
        @click="section = 'bp'"
      >
        <AegisIcon name="briefcase" :size="16" />
        <div>
          <div class="ss-section-title">Business Partner Support &amp; Services</div>
          <div class="ss-section-sub">IT, billing, compliance, marketing, legal &amp; more</div>
        </div>
      </button>
      <button
        v-if="isPracticeTier"
        type="button" role="tab"
        class="ss-section-btn"
        :class="{ active: section === 'ps' }"
        @click="section = 'ps'"
      >
        <AegisIcon name="users" :size="16" />
        <div>
          <div class="ss-section-title">Practitioner Support &amp; Services</div>
          <div class="ss-section-sub">Supervision, consultation, training, mentorship &amp; more</div>
        </div>
      </button>
      <div v-else class="ss-section-btn ss-section-btn--locked">
        <AegisIcon name="lock" :size="16" />
        <div>
          <div class="ss-section-title">Practitioner Support &amp; Services</div>
          <div class="ss-section-sub">Available on Practice plan — <a href="/provider/settings?section=billing&upgrade=1" class="link-gold">Upgrade</a></div>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════
         SECTION A: BUSINESS PARTNER SUPPORT & SERVICES
    ════════════════════════════════════════════════════════════════════ -->
    <div v-show="section === 'bp'">

    <div class="stat-chip">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="check" :size="18" /></div>
        <div><div class="stat-chip-value">{{ stats.hired }}</div><div class="stat-chip-label">Hired</div></div>
      </div>
    </div>

    <!-- TABS -->
    <div class="tabs-primary" role="tablist" style="margin-bottom:24px">
      <button class="tab-primary" :class="{ active: tab === 'my-postings' }" role="tab" :aria-selected="tab === 'my-postings'" @click="tab = 'my-postings'">
        <AegisIcon name="briefcase" :size="15" />
        My Postings <span class="tab-count">{{ jobs.length }}</span>
      </button>
      <button class="tab-primary" :class="{ active: tab === 'applications' }" role="tab" :aria-selected="tab === 'applications'" @click="tab = 'applications'">
        <AegisIcon name="download" :size="15" />
        Applications <span v-if="stats.pending_proposals" class="tab-count">{{ stats.pending_proposals }}</span>
      </button>
      <button class="tab-primary" :class="{ active: tab === 'pipeline' }" role="tab" :aria-selected="tab === 'pipeline'" @click="tab = 'pipeline'">
        <AegisIcon name="grid" :size="15" />
        Hiring Pipeline
      </button>
      <button class="tab-primary" :class="{ active: tab === 'hired' }" role="tab" :aria-selected="tab === 'hired'" @click="tab = 'hired'">
        <AegisIcon name="check" :size="15" />
        Hired <span v-if="stats.hired" class="tab-count">{{ stats.hired }}</span>
      </button>
      <button class="tab-primary" :class="{ active: tab === 'requests' }" role="tab" :aria-selected="tab === 'requests'" @click="tab = 'requests'">
        <AegisIcon name="briefcase" :size="13" />
        Requests <span v-if="engagements.length" class="tab-count">{{ engagements.length }}</span>
      </button>
      <button class="tab-primary" :class="{ active: tab === 'help' }" role="tab" :aria-selected="tab === 'help'" @click="tab = 'help'">
        <AegisIcon name="help-circle" :size="13" />
        How It Works
      </button>
    </div>

    <!-- ============================================================
         PANE 1: MY POSTINGS
    ============================================================ -->
    <div v-show="tab === 'my-postings'">
      <div class="section-header" style="margin-bottom:16px">
        <div class="section-title-h"><AegisIcon name="briefcase" :size="16" /> My Support Requests</div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-outline" @click="showTemplates = true">
            <AegisIcon name="download" :size="12" />
            Use Template
          </button>
          <button class="btn btn-primary" @click="showPostJob = true">
            <AegisIcon name="plus" :size="12" />
            Request Support
          </button>
        </div>
      </div>

      <div class="tabs-segmented" style="margin-top:16px;margin-bottom:16px">
        <button class="tab-pill" :class="{ active: postingFilter === 'all' }" @click="postingFilter = 'all'">All <span class="badge-pill">{{ jobs.length }}</span></button>
        <button class="tab-pill" :class="{ active: postingFilter === 'open' }" @click="postingFilter = 'open'">Active <span v-if="stats.open" class="badge-pill">{{ stats.open }}</span></button>
        <button class="tab-pill" :class="{ active: postingFilter === 'draft' }" @click="postingFilter = 'draft'">Draft <span v-if="stats.draft" class="badge-pill">{{ stats.draft }}</span></button>
        <button class="tab-pill" :class="{ active: postingFilter === 'paused' }" @click="postingFilter = 'paused'">Paused <span v-if="stats.paused" class="badge-pill">{{ stats.paused }}</span></button>
        <button class="tab-pill" :class="{ active: postingFilter === 'closed' }" @click="postingFilter = 'closed'">Closed <span v-if="stats.closed" class="badge-pill">{{ stats.closed }}</span></button>
      </div>

      <div class="jp-my-table">
        <div class="jp-my-table-head">
          <div>Request Title</div><div>Category</div><div>Budget</div>
          <div>Applicants</div><div>Status</div><div>Actions</div>
        </div>

        <AegisEmptyState v-if="!filteredJobs.length" icon="briefcase" title="No support requests yet" description="Submit your first request to start receiving proposals from vetted business partners.">
          <template #action>
            <button class="btn btn-primary" @click="showPostJob = true"><AegisIcon name="plus" :size="13" /> Request Support</button>
          </template>
        </AegisEmptyState>

        <div
          v-for="job in pagedJobs"
          :key="job.id"
          class="jp-my-row"
          :style="rowStyle(job.status)"
          @click="openEdit(job)"
        >
          <div>
            <div class="jp-my-title">{{ job.title }}</div>
            <div class="jp-my-sub"><AegisIcon name="briefcase" :size="11" /> {{ categoryLabel(job.category) }} · Posted {{ formatDate(job.posted_at) }} · {{ locationLabel(job.location_pref) }}</div>
          </div>
          <div><span class="tag-chip">{{ categoryLabel(job.category) }}</span></div>
          <div :style="{ fontSize: '13px', fontWeight: 700, color: val(job.status) === 'open' ? 'var(--green)' : 'var(--text-3)' }">{{ formatBudget(job) }}</div>
          <div>
            <div style="font-size:13px;font-weight:700;color:var(--text)">{{ proposalCount(job.id) }}</div>
            <div :style="{ fontSize: '11px', color: newProposalCount(job.id) > 0 ? 'var(--gold-dark)' : 'var(--text-3)' }">
              {{ newProposalCount(job.id) > 0 ? newProposalCount(job.id) + ' new' : '0 new' }}
            </div>
          </div>
          <div><span class="badge" :class="statusBadgeClass(job.status)"><AegisIcon name="dot" :size="8" :filled="true" /> {{ statusLabel(job.status) }}</span></div>
          <div style="display:flex;gap:5px" @click.stop>
            <button v-if="['open','draft','paused'].includes(val(job.status))" class="btn-icon" data-tooltip="Edit" @click="openEdit(job)"><AegisIcon name="pencil" :size="12" /></button>
            <button v-if="proposalCount(job.id) > 0" class="btn-icon" :data-tooltip="proposalCount(job.id) + ' applicants'" @click="openManageApps(job)"><AegisIcon name="users" :size="12" /></button>
            <button v-if="val(job.status) === 'open'" class="btn-icon btn-icon-danger" data-tooltip="Pause" @click="confirmPause(job)"><AegisIcon name="pause" :size="12" /></button>
            <button v-else-if="val(job.status) === 'draft'" class="btn btn-primary" @click="confirmPublish(job)">Publish</button>
            <button v-else-if="val(job.status) === 'paused'" class="btn-icon" data-tooltip="Resume posting" @click="confirmResume(job)"><AegisIcon name="refresh-cw" :size="12" /></button>
          </div>
        </div>
      </div>

      <div v-if="filteredJobs.length" class="pager">
        <div class="pager-info">
          Showing <strong>{{ (jobsPage - 1) * 5 + 1 }}–{{ Math.min(jobsPage * 5, filteredJobs.length) }}</strong>
          of {{ filteredJobs.length }} posting{{ filteredJobs.length !== 1 ? 's' : '' }}
        </div>
        <AegisPagination
          :current-page="jobsPage"
          :total-pages="jobsTotalPages"
          @change="jobsPage = $event"
        />
      </div>
    </div>

    <!-- ============================================================
         PANE 2: APPLICATIONS
    ============================================================ -->
    <div v-show="tab === 'applications'">
      <div class="section-header" style="margin-bottom:16px">
        <div class="section-title-h"><AegisIcon name="download" :size="16" /> Applications</div>
        <select v-model="applicationsJobFilter" class="form-select" style="width:240px;font-size:12px">
          <option value="">All Jobs</option>
          <option v-for="j in jobs" :key="j.id" :value="j.id">{{ j.title }}</option>
        </select>
      </div>

      <AegisEmptyState v-if="!filteredApplications.length" icon="download" title="No applications yet" description="Proposals from Business Partners will appear here as they apply to your postings." />

      <div v-else class="jp-my-table">
        <div class="jp-app-table-head">
          <div></div><div>Applicant</div><div>Applying For</div><div>Bid</div><div>Status</div><div>Actions</div>
        </div>
        <div v-for="p in pagedApplications" :key="p.id" class="jp-app-row" @click="openProfile(p)">
          <div class="jp-app-avatar" :style="avatarStyle(p.bp)">
            <template v-if="!p.bp?.avatar_url">{{ initials(p.bp?.display_name) }}</template>
          </div>
          <div>
            <div class="jp-app-name">{{ p.bp?.display_name ?? 'Business Partner' }}</div>
            <div class="jp-app-role">{{ bpTypeLabel(p.bp?.bp_type) }}</div>
          </div>
          <div style="font-size:12.5px;color:var(--text-2)">{{ jobTitle(p.job_id) }}</div>
          <div>
            <div style="font-size:13px;font-weight:700;color:var(--green)">{{ formatCents(p.proposed_rate_cents) }}</div>
            <AegisBadge
              v-if="p.payment_type"
              :label="p.payment_type === 'milestone' ? 'Milestone-based' : 'One-time Payment'"
              :variant="p.payment_type === 'milestone' ? 'blue' : 'gold'"
            />
          </div>
          <div><span class="badge" :class="proposalStatusBadgeClass(p)">{{ proposalStatusLabel(p) }}</span></div>
          <div style="display:flex;gap:5px" @click.stop>
            <button class="btn-icon" data-tooltip="View profile" @click="openProfile(p)"><AegisIcon name="eye" :size="12" /></button>
            <template v-if="val(p.status) === 'pending'">
              <button class="btn-icon" data-tooltip="Hire" @click="openHire(p)"><AegisIcon name="check" :size="12" /></button>
              <button class="btn-icon btn-icon-danger" data-tooltip="Reject" @click="openReject(p)"><AegisIcon name="x" :size="12" /></button>
            </template>
          </div>
        </div>
      </div>

      <div v-if="filteredApplications.length" class="pager">
        <div class="pager-info">
          Showing <strong>{{ (appsPage - 1) * 8 + 1 }}–{{ Math.min(appsPage * 8, filteredApplications.length) }}</strong>
          of {{ filteredApplications.length }} application{{ filteredApplications.length !== 1 ? 's' : '' }}
        </div>
        <AegisPagination
          :current-page="appsPage"
          :total-pages="appsTotalPages"
          @change="appsPage = $event"
        />
      </div>
    </div>

    <!-- ============================================================
         PANE 3: HIRING PIPELINE (kanban)
    ============================================================ -->
    <div v-show="tab === 'pipeline'">
      <div class="section-header" style="margin-bottom:16px">
        <div class="section-title-h"><AegisIcon name="grid" :size="16" /> Hiring Pipeline</div>
        <select v-model="pipelineJobFilter" class="form-select" style="width:240px;font-size:12px">
          <option value="">All Active Jobs</option>
          <option v-for="j in openJobs" :key="j.id" :value="j.id">{{ j.title }}</option>
        </select>
      </div>

      <div class="jp-kanban">
        <div v-for="col in kanbanColumns" :key="col.stage" class="jp-kanban-col">
          <div class="jp-kanban-col-header" :style="{ color: col.color }">
            <span>{{ col.label }}</span>
            <span class="jp-kanban-count">{{ pipelineGroup(col.stage).length }}</span>
          </div>
          <div>
            <div v-for="p in pipelineGroup(col.stage)" :key="p.id" class="jp-kanban-card" @click="openProfile(p)">
              <div class="jp-kanban-name">{{ p.bp?.display_name ?? 'Business Partner' }}</div>
              <div class="jp-kanban-role">{{ jobTitle(p.job_id) }}</div>
              <div class="jp-kanban-rate">{{ formatCents(p.proposed_rate_cents) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================
         PANE 4: HIRED
    ============================================================ -->
    <div v-show="tab === 'hired'">
      <div class="section-header" style="margin-bottom:16px">
        <div class="section-title-h"><AegisIcon name="check" :size="16" /> Hired Business Partners</div>
      </div>

      <BpFinanceTable
        :invoices="bpInvoices"
        :contracts="props.activeContracts"
        :invoices-by-contract="props.invoicesByContract"
        :escrow-summary="props.escrowSummary"
        :has-payment-method="hasPaymentMethod"
      />
    </div>

    <!-- ============================================================
         PANE 5: REQUESTS
    ============================================================ -->
    <div v-show="tab === 'requests'">
      <div class="section-header" style="margin-bottom:16px">
        <div class="section-title-h"><AegisIcon name="briefcase" :size="16" /> Engagement Requests</div>
        <span v-if="engagements.length" class="badge badge-blue">{{ engagements.length }} total</span>
      </div>

      <AegisEmptyState v-if="!engagements.length" icon="briefcase" title="No engagement requests yet" description="Hire, quote, and consultation requests you send from a partner's profile will appear here." />

      <template v-else>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Partner</th>
                <th>Type</th>
                <th>Details</th>
                <th>Status</th>
                <th>Submitted</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in pagedEngagements" :key="r.id">
                <!-- Partner — linked to public profile -->
                <td>
                  <div class="ert-partner-cell">
                    <div class="avatar avatar-sm avatar-gold" style="border-radius:var(--radius-sm);font-size:11px;font-weight:700;flex-shrink:0">
                      {{ r.bp?.avatar_initials ?? '??' }}
                    </div>
                    <div>
                      <a v-if="r.bp?.slug" :href="route('public.bp', r.bp.slug)" class="ert-partner-link" target="_blank">
                        {{ r.bp?.display_name ?? '—' }}
                      </a>
                      <span v-else class="ert-partner-name">{{ r.bp?.display_name ?? '—' }}</span>
                      <div class="ert-partner-type">{{ r.bp?.bp_type ? (r.bp.bp_type.charAt(0).toUpperCase() + r.bp.bp_type.slice(1)) : '' }}</div>
                    </div>
                  </div>
                </td>
                <!-- Type -->
                <td>
                  <span :class="['badge', r.type === 'hire' ? 'badge-gold' : r.type === 'quote' ? 'badge-blue' : 'badge-green']">
                    <AegisIcon :name="r.type === 'hire' ? 'briefcase' : r.type === 'quote' ? 'clipboard' : 'calendar'" :size="10" />
                    {{ r.type === 'hire' ? 'Engagement' : r.type === 'quote' ? 'Quote' : 'Consultation' }}
                  </span>
                </td>
                <!-- Details summary -->
                <td class="ert-details-cell">
                  <div class="ert-details-primary">{{ r.engagement_type || r.service || r.meeting_type || '—' }}</div>
                  <div v-if="r.start_date" class="ert-details-sub"><AegisIcon name="calendar" :size="10" /> {{ r.start_date }}</div>
                  <div v-if="r.budget" class="ert-details-sub"><AegisIcon name="dollar" :size="10" /> {{ r.budget }}</div>
                  <span v-if="r.urgent" class="badge badge-orange" style="font-size:10px;margin-top:3px">Urgent</span>
                </td>
                <!-- Status -->
                <td>
                  <span :class="['badge', r.status === 'pending' ? 'badge-gold' : r.status === 'accepted' ? 'badge-green' : 'badge-red']">
                    {{ r.status.charAt(0).toUpperCase() + r.status.slice(1) }}
                  </span>
                </td>
                <!-- Submitted -->
                <td class="ert-date-cell">{{ r.created_at }}</td>
                <!-- Actions -->
                <td>
                  <div class="ert-actions-cell">
                    <button class="btn-icon" data-tooltip="View details" @click="viewRequest(r)"><AegisIcon name="eye" :size="13" /></button>
                    <button class="btn-icon" data-tooltip="Message" :disabled="msgLoading === r.bp?.id" @click="openConversation(r.bp?.id)"><AegisIcon name="message-square" :size="13" /></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="totalRequestPages > 1" class="ert-pagination">
          <button class="btn btn-outline" :disabled="requestsPage === 1" @click="requestsPage--">
            <AegisIcon name="chevron-left" :size="13" /> Prev
          </button>
          <span class="ert-page-info">{{ requestsPage }} / {{ totalRequestPages }}</span>
          <button class="btn btn-outline" :disabled="requestsPage === totalRequestPages" @click="requestsPage++">
            Next <AegisIcon name="chevron-right" :size="13" />
          </button>
        </div>
      </template>
    </div>

    <!-- ============================================================
         PANE 6: HOW IT WORKS
    ============================================================ -->
    <div v-show="tab === 'help'" class="help-pane">

      <!-- Intro banner -->
      <div class="help-intro">
        <div class="help-intro-icon"><AegisIcon name="shield" :size="28" /></div>
        <div>
          <div class="help-intro-title">Support Services — How Requests Work</div>
          <div class="help-intro-sub">Support Services connects you with vetted Business Partners for billing, compliance, IT, marketing, legal, and operations. Payment goes directly to Business Partners — you only pay when work is approved.</div>
        </div>
      </div>

      <!-- Step-by-step flow -->
      <div class="help-section-label">End-to-End Workflow</div>

      <div class="help-steps">

        <div class="help-step">
          <div class="help-step-num">1</div>
          <div class="help-step-body">
            <div class="help-step-title">Post a Support Request</div>
            <div class="help-step-desc">Go to <strong>My Postings</strong> and click <em>Request Support</em>. Give the request a title, choose a category (Billing, IT, Marketing, etc.), set your budget, and pick a payment type.</div>
            <div class="help-callout help-callout-gold">
              <AegisIcon name="info" :size="13" style="flex-shrink:0;margin-top:2px" />
              <div><strong>Payment type matters.</strong> <em>One-time</em> means a single fixed payment at completion. <em>Milestone</em> breaks the project into paid checkpoints — safer for longer engagements. You also set default payment terms (upfront %, split, or per milestone) that Business Partners will see when applying.</div>
            </div>
            <div class="help-tags">
              <span class="help-tag"><AegisIcon name="briefcase" :size="11" /> My Postings tab</span>
              <span class="help-tag"><AegisIcon name="plus" :size="11" /> Request Support button</span>
            </div>
          </div>
        </div>

        <div class="help-step-connector"><AegisIcon name="arrow-down" :size="14" /></div>

        <div class="help-step">
          <div class="help-step-num">2</div>
          <div class="help-step-body">
            <div class="help-step-title">Publish &amp; Receive Applications</div>
            <div class="help-step-desc">Draft postings are invisible. Hit <em>Publish</em> to make your request live. Business Partners across Aegis can browse open requests and submit proposals with their bid, cover letter, and portfolio.</div>
            <div class="help-callout help-callout-blue">
              <AegisIcon name="bell" :size="13" style="flex-shrink:0;margin-top:2px" />
              <div>You'll receive an in-portal notification and email each time a new proposal arrives. The <em>Applications</em> tab shows a running count of unreviewed proposals.</div>
            </div>
            <div class="help-tags">
              <span class="help-tag"><AegisIcon name="download" :size="11" /> Applications tab</span>
              <span class="help-tag"><AegisIcon name="grid" :size="11" /> Hiring Pipeline tab</span>
            </div>
          </div>
        </div>

        <div class="help-step-connector"><AegisIcon name="arrow-down" :size="14" /></div>

        <div class="help-step">
          <div class="help-step-num">3</div>
          <div class="help-step-body">
            <div class="help-step-title">Review &amp; Move Applicants Through the Pipeline</div>
            <div class="help-step-desc">Use the <strong>Hiring Pipeline</strong> kanban to track each applicant. Stages move from New → Reviewing → Shortlisted → Interview → Hired. Private notes are only visible to you — never to the Business Partner.</div>
            <div class="help-grid-2">
              <div class="help-mini-card">
                <div class="help-mini-icon" style="background:var(--icon-bg-blue);color:var(--blue-dark)"><AegisIcon name="eye" :size="14" /></div>
                <div><strong>Mark Reviewed</strong><br>Opens the applicant's profile and moves them to Reviewing.</div>
              </div>
              <div class="help-mini-card">
                <div class="help-mini-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="star" :size="14" /></div>
                <div><strong>Shortlist</strong><br>Flags top candidates so they're easy to compare side-by-side.</div>
              </div>
              <div class="help-mini-card">
                <div class="help-mini-icon" style="background:var(--icon-bg-green);color:var(--green-dark)"><AegisIcon name="calendar" :size="14" /></div>
                <div><strong>Schedule Interview</strong><br>Coordinate a meeting date — logged against the proposal.</div>
              </div>
              <div class="help-mini-card">
                <div class="help-mini-icon" style="background:var(--icon-bg-red);color:var(--red-dark)"><AegisIcon name="x" :size="14" /></div>
                <div><strong>Decline</strong><br>Removes the applicant. They receive a polite decline notification.</div>
              </div>
            </div>
          </div>
        </div>

        <div class="help-step-connector"><AegisIcon name="arrow-down" :size="14" /></div>

        <div class="help-step">
          <div class="help-step-num">4</div>
          <div class="help-step-body">
            <div class="help-step-title">Hire &amp; Sign the Contract</div>
            <div class="help-step-desc">When you're ready, click <em>Hire</em> on any applicant. Aegis automatically declines all other proposals on that posting and creates a contract in <em>Pending Signature</em> status.</div>
            <div class="help-callout help-callout-green">
              <AegisIcon name="check-circle" :size="13" style="flex-shrink:0;margin-top:2px" />
              <div>Both parties must sign digitally before any funds are charged. The contract terms are locked at signature — neither party can alter them afterwards.</div>
            </div>
            <div class="help-tags">
              <span class="help-tag"><AegisIcon name="check" :size="11" /> Hired tab</span>
            </div>
          </div>
        </div>

        <div class="help-step-connector"><AegisIcon name="arrow-down" :size="14" /></div>

        <div class="help-step">
          <div class="help-step-num">5</div>
          <div class="help-step-body">
            <div class="help-step-title">Sign &amp; Pay — Direct to Business Partner</div>
            <div class="help-step-desc">Once both parties sign, payment fires automatically based on your committed terms. Funds route directly to the Business Partner's Stripe account — Aegis does not hold funds.</div>

            <div class="help-escrow-diagram">
              <div class="help-escrow-node help-escrow-node-provider">
                <AegisIcon name="credit-card" :size="16" />
                <span>Your Card</span>
              </div>
              <div class="help-escrow-arrow">
                <div class="help-escrow-arrow-line"></div>
                <AegisIcon name="chevron-right" :size="14" />
              </div>
              <div class="help-escrow-node help-escrow-node-aegis">
                <AegisIcon name="shield-check" :size="16" />
                <span>Stripe Connect</span>
                <span class="help-escrow-sub">Pass-through</span>
              </div>
              <div class="help-escrow-arrow">
                <div class="help-escrow-arrow-line"></div>
                <AegisIcon name="chevron-right" :size="14" />
              </div>
              <div class="help-escrow-node help-escrow-node-bp">
                <AegisIcon name="user" :size="16" />
                <span>Business Partner</span>
                <span class="help-escrow-sub">Direct to their account</span>
              </div>
            </div>

            <div class="help-grid-2" style="margin-top:16px">
              <div class="help-mini-card">
                <div class="help-mini-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="layers" :size="14" /></div>
                <div><strong>Per Milestone</strong><br>Payment fires automatically each time you approve a deliverable. You only pay for completed work.</div>
              </div>
              <div class="help-mini-card">
                <div class="help-mini-icon" style="background:var(--icon-bg-green);color:var(--green-dark)"><AegisIcon name="dollar" :size="14" /></div>
                <div><strong>Full Upfront</strong><br>Entire contract value charges at signing. Milestones are pre-paid — the Business Partner starts immediately.</div>
              </div>
              <div class="help-mini-card">
                <div class="help-mini-icon" style="background:var(--icon-bg-blue);color:var(--blue-dark)"><AegisIcon name="credit-card" :size="14" /></div>
                <div><strong>Split</strong><br>A percentage charges at signing, the remainder at completion. Balances risk for both parties.</div>
              </div>
              <div class="help-mini-card">
                <div class="help-mini-icon" style="background:var(--icon-bg-red);color:var(--red-dark)"><AegisIcon name="clock" :size="14" /></div>
                <div><strong>Pay on Completion</strong><br>Nothing charges until you mark the contract complete. Maximum provider control, higher BP trust required.</div>
              </div>
            </div>
          </div>
        </div>

        <div class="help-step-connector"><AegisIcon name="arrow-down" :size="14" /></div>

        <div class="help-step">
          <div class="help-step-num">6</div>
          <div class="help-step-body">
            <div class="help-step-title">Review Milestone Submissions</div>
            <div class="help-step-desc">When the Business Partner submits work, you'll receive a notification with a <em>Review</em> button in the Hired tab. You have three options:</div>
            <div class="help-review-options">
              <div class="help-review-row help-review-approve">
                <AegisIcon name="check-circle" :size="15" style="flex-shrink:0" />
                <div>
                  <div class="help-review-label">Approve &amp; Release Payment</div>
                  <div class="help-review-detail">Triggers an immediate direct payment to the Business Partner's Stripe account. The milestone is marked complete.</div>
                </div>
              </div>
              <div class="help-review-row help-review-revision">
                <AegisIcon name="refresh-cw" :size="15" style="flex-shrink:0" />
                <div>
                  <div class="help-review-label">Request Revision</div>
                  <div class="help-review-detail">Send the milestone back with written feedback. The Business Partner can revise and resubmit. No payment is made until you approve.</div>
                </div>
              </div>
              <div class="help-review-row help-review-dispute">
                <AegisIcon name="alert-triangle" :size="15" style="flex-shrink:0" />
                <div>
                  <div class="help-review-label">Reject &amp; Open Dispute</div>
                  <div class="help-review-detail">If the work cannot be resolved through revision, open a dispute. Aegis mediates and can facilitate refunds or partial payments.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="help-step-connector"><AegisIcon name="arrow-down" :size="14" /></div>

        <div class="help-step">
          <div class="help-step-num">7</div>
          <div class="help-step-body">
            <div class="help-step-title">Auto-Approve &amp; Timers</div>
            <div class="help-step-desc">If you don't review a submission within <strong>7 days</strong>, Aegis automatically approves the milestone and payment fires directly to the Business Partner's account. You'll receive a reminder at the 48-hour mark.</div>
            <div class="help-callout help-callout-orange">
              <AegisIcon name="clock" :size="13" style="flex-shrink:0;margin-top:2px" />
              <div><strong>Why auto-approve?</strong> It protects Business Partners from indefinite payment holds after completing legitimate work. Review submissions promptly to maintain full control over payment decisions.</div>
            </div>
          </div>
        </div>

        <div class="help-step-connector"><AegisIcon name="arrow-down" :size="14" /></div>

        <div class="help-step">
          <div class="help-step-num">8</div>
          <div class="help-step-body">
            <div class="help-step-title">Contract Completion &amp; Ratings</div>
            <div class="help-step-desc">Once all milestones are paid, the contract is marked <em>Completed</em>. You'll be prompted to leave a rating for the Business Partner — and they can rate you. Ratings are visible on public profiles and help the community identify top partners.</div>
            <div class="help-tags">
              <span class="help-tag help-tag-green"><AegisIcon name="star" :size="11" /> 1–5 star rating</span>
              <span class="help-tag help-tag-green"><AegisIcon name="check" :size="11" /> Communication, Quality, Timeliness</span>
            </div>
          </div>
        </div>

      </div><!-- /help-steps -->

      <!-- Requests tab explainer -->
      <div class="help-section-label" style="margin-top:40px">About the Requests Tab</div>
      <div class="help-card">
        <div class="help-card-icon"><AegisIcon name="briefcase" :size="20" /></div>
        <div>
          <div class="help-card-title">Engagement Requests You've Sent</div>
          <div class="help-card-desc">The <strong>Requests</strong> tab tracks outbound hire, quote, and consultation requests you send directly from a Business Partner's public profile. These are informal enquiries — they don't create a contract until both parties agree and sign.</div>
          <div class="help-grid-3" style="margin-top:14px">
            <div class="help-type-chip help-type-hire">
              <AegisIcon name="briefcase" :size="12" />
              <div>
                <div class="help-type-name">Engagement Request</div>
                <div class="help-type-detail">Propose a direct working arrangement outside of a public posting.</div>
              </div>
            </div>
            <div class="help-type-chip help-type-quote">
              <AegisIcon name="clipboard" :size="12" />
              <div>
                <div class="help-type-name">Quote Request</div>
                <div class="help-type-detail">Ask a partner for a formal price estimate before committing.</div>
              </div>
            </div>
            <div class="help-type-chip help-type-consult">
              <AegisIcon name="calendar" :size="12" />
              <div>
                <div class="help-type-name">Consultation Request</div>
                <div class="help-type-detail">Schedule a discovery call to assess fit before any contract.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Safety callout -->
      <div class="help-safety-banner">
        <div class="help-safety-icon"><AegisIcon name="shield" :size="24" /></div>
        <div>
          <div class="help-safety-title">Your Money is Protected at Every Stage</div>
          <div class="help-safety-body">Payments route directly to the Business Partner's Stripe account at the moment you approve — Aegis is never a holding party. If a dispute arises before payment, you can cancel the milestone at no charge. Once work is submitted and you're not satisfied, the dispute system triggers Aegis mediation to ensure fair resolution for both parties.</div>
        </div>
      </div>

      <!-- CTA -->
      <div style="text-align:center;margin-top:32px;padding-bottom:8px">
        <button class="btn btn-primary" @click="tab = 'my-postings'; showPostJob = true">
          <AegisIcon name="plus" :size="13" />
          Post Your First Support Request
        </button>
        <div style="font-family:var(--font-sans);font-size:12px;color:var(--text-4);margin-top:8px">Takes about 2 minutes. No charge until you hire.</div>
      </div>

    </div><!-- /help-pane -->

    <PostJobModal v-model="showPostJob" :prefill="postJobPrefill" @update:model-value="(v) => { if (!v) postJobPrefill = null }" />
    <ImportJobTemplatesModal v-model="showTemplates" @use="onUseTemplate" />
    <EditJobModal v-model="showEdit" :job="editingJob" />
    <ManageApplicationsModal
      v-model="showManageApps"
      :job="manageAppsJob"
      :proposals="manageAppsJob ? proposalsForJob(manageAppsJob.id) : []"
      @open-profile="openProfile"
      @view-pipeline="() => { showManageApps = false; tab = 'pipeline' }"
    />
    <ApplicantProfileModal
      v-model="showProfile"
      :proposal="activeProposal"
      :job-title="activeProposal ? jobTitle(activeProposal.job_id) : ''"
      :stats="activeProposal ? (bpStats[activeProposal.bp_id] || {}) : {}"
      :contract-proposal-ids="activeHiredContracts.map(c => c.proposal_id).filter(Boolean)"
      @reviewed="(p) => openStage(p, 'reviewed')"
      @shortlist="(p) => openStage(p, 'shortlisted')"
      @schedule="(p) => openSchedule(p)"
      @reject="(p) => openReject(p)"
      @hire="(p) => { showProfile = false; openHire(p) }"
    />
    <StageActionModal v-model="showStage" :proposal="activeProposal" :stage="stageMode" @done="showProfile = false" />
    <ScheduleInterviewModal v-model="showSchedule" :proposal="activeProposal" :job-title="activeProposal ? jobTitle(activeProposal.job_id) : ''" @done="showProfile = false" />
    <RejectModal v-model="showReject" :proposal="activeProposal" @done="showProfile = false" />
    <HireModal
      v-model="showHire"
      :proposal="activeProposal"
      :job="activeProposal ? jobs.find(j => j.id === activeProposal.job_id) : null"
      :job-title="activeProposal ? jobTitle(activeProposal.job_id) : ''"
      :stats="activeProposal ? (bpStats[activeProposal.bp_id] || {}) : {}"
      @done="() => { showProfile = false; showHire = false; showManageApps = false }"
    />
    <ContractModal
      v-model="showContract"
      :contract="activeContract"
      :milestones="activeContract ? (milestonesByContract?.[activeContract.id] ?? []) : []"
      :invoices="activeContract ? (invoicesByContract?.[activeContract.id] ?? []) : []"
      @leave-review="(c) => openReviewForContract(c)"
    />
    <EngagementRequestModal v-model="showRequestDetail" :request="activeEngagementRequest" />

    <!-- Review auto-trigger — opens when a completed contract has no review yet -->
    <ReviewContractModal
      v-model="showReview"
      :contract="reviewContract"
      post-route="provider.jobs.contract.review"
      dismiss-route="provider.jobs.contract.review.dismiss"
    />
    </div><!-- /section bp -->

    <!-- ════════════════════════════════════════════════════════════════════
         SECTION B: PRACTITIONER SUPPORT & SERVICES
    ════════════════════════════════════════════════════════════════════ -->
    <div v-show="section === 'ps' && isPracticeTier">

      <!-- Hero copy from Dr. Chapman -->
      <div class="ps-hero">
        <h2 class="ps-hero-title">Strengthen your practice and expand your expertise.</h2>
        <p class="ps-hero-desc">Connect with practitioners offering professional expertise that supports continuity, clinical practice, and professional development. Explore supervision, consultation, training, continuity stewardship, mentorship, and other specialty services designed to strengthen your practice, expand your expertise, and support the care you provide.</p>
      </div>

      <!-- Sub-tabs -->
      <div class="tabs-primary" role="tablist" style="margin-bottom:20px">
        <button class="tab-primary" :class="{ active: psTab === 'ps-browse' }" role="tab" @click="psTab = 'ps-browse'">
          <AegisIcon name="search" :size="14" /> Browse Services
        </button>
        <button class="tab-primary" :class="{ active: psTab === 'ps-requests' }" role="tab" @click="psTab = 'ps-requests'">
          <AegisIcon name="send" :size="14" />
          My Requests
          <span v-if="psPendingOutgoing()" class="tab-count">{{ psPendingOutgoing() }}</span>
        </button>
        <button class="tab-primary" :class="{ active: psTab === 'ps-bookings' }" role="tab" @click="psTab = 'ps-bookings'">
          <AegisIcon name="credit-card" :size="14" />
          My Bookings
          <span v-if="psClientBadge()" class="tab-count">{{ psClientBadge() }}</span>
        </button>
      </div>

      <!-- ── Browse ──────────────────────────────────────────────────────── -->
      <div v-show="psTab === 'ps-browse'">

        <!-- Category chips from Dr. Chapman's spec -->
        <div class="ps-category-chips">
          <button
            v-for="cat in psServiceCategories" :key="cat.value"
            type="button"
            class="ps-cat-chip"
            :class="{ active: psExploreFilters.category === cat.value }"
            @click="psExploreFilters.category = psExploreFilters.category === cat.value ? '' : cat.value; psDoSearch()"
          >{{ cat.label }}</button>
        </div>

        <!-- Filter bar -->
        <div class="explore-filter-bar" style="margin-bottom:16px">
          <div class="explore-filter-row explore-filter-row--search">
            <div class="explore-filter-search">
              <AegisIcon name="search" :size="14" />
              <input
                v-model="psExploreFilters.q"
                type="text"
                class="form-control"
                placeholder="Search services, providers, specialties…"
                @keydown.enter.prevent="psDoSearch"
              />
            </div>
          </div>
          <div class="explore-filter-row explore-filter-row--dropdowns">
            <div class="explore-filter-select-wrap" :class="{ 'has-value': psExploreFilters.format }">
              <AegisIcon name="monitor" :size="13" />
              <select v-model="psExploreFilters.format" class="form-select explore-filter-select" @change="psDoSearch">
                <option value="">Any Format</option>
                <option value="telehealth">Virtual</option>
                <option value="in_person">In-Person</option>
                <option value="both">Virtual &amp; In-Person</option>
              </select>
            </div>
            <div class="explore-filter-select-wrap" :class="{ 'has-value': psExploreFilters.availability }">
              <AegisIcon name="calendar" :size="13" />
              <select v-model="psExploreFilters.availability" class="form-select explore-filter-select" @change="psDoSearch">
                <option value="">Any Availability</option>
                <option value="open">Open — accepting</option>
                <option value="limited">Limited spots</option>
              </select>
            </div>
          </div>
          <div class="explore-filter-row explore-filter-row--meta">
            <span class="explore-count">{{ psExploreMeta.total ?? 0 }} result{{ (psExploreMeta.total ?? 0) !== 1 ? 's' : '' }}</span>
            <button
              v-if="psExploreFilters.q || psExploreFilters.category || psExploreFilters.format || psExploreFilters.availability"
              type="button" class="explore-clear-btn"
              @click="psClearFilters"
            >
              <AegisIcon name="x" :size="11" /> Clear filters
            </button>
          </div>
        </div>

        <AegisEmptyState
          v-if="!psExploreResults.length && !psExploreLoading"
          icon="search"
          title="No services found"
          subtitle="Try adjusting your filters or check back soon as more practitioners list their services."
        />

        <div class="explore-grid">
          <ServiceExploreCard
            v-for="svc in psExploreResults"
            :key="svc.id"
            :service="svc"
            @request="psOpenRequest(svc)"
          />
        </div>

        <div v-if="psExploreLoading" class="explore-loading">
          <span class="spinner spinner-sm" /> Loading more…
        </div>
        <div ref="psExploreSentinel" class="explore-sentinel" aria-hidden="true"></div>
        <div v-if="!psExploreLoading && psExploreMeta.current_page >= psExploreMeta.last_page && psExploreResults.length > 0" class="explore-end-note">
          <AegisIcon name="check-circle" :size="13" />
          {{ psExploreResults.length }} service{{ psExploreResults.length !== 1 ? 's' : '' }} shown
        </div>

        <ServiceRequestModal
          ref="psSvcModalRef"
          :provider-id="psSvcTarget.id"
          :provider-label="psSvcTarget.label"
        />
      </div>

      <!-- ── My Requests ────────────────────────────────────────────────── -->
      <div v-show="psTab === 'ps-requests'">
        <AegisEmptyState
          v-if="!props.psOutgoingRequests.length"
          icon="send"
          title="No requests sent"
          subtitle="Browse Practitioner Support & Services and send your first service request."
        >
          <button class="btn btn-primary" @click="psTab = 'ps-browse'">
            <AegisIcon name="search" :size="13" /> Browse Services
          </button>
        </AegisEmptyState>

        <template v-else>
          <div class="sic-table-wrap">
            <table class="sic-table">
              <thead>
                <tr>
                  <th class="sic-th">Provider</th>
                  <th class="sic-th">Service</th>
                  <th class="sic-th">Status</th>
                  <th class="sic-th"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="r in props.psOutgoingRequests"
                  :key="r.id"
                  class="orq-row"
                  :class="`orq-row--${r.status}`"
                  @click="psActiveOutgoing = r; psOutgoingDetail = true"
                >
                  <td class="sic-td orq-td--provider">
                    <div class="sic-party">
                      <div class="sic-avatar">
                        <span class="sic-avatar-initials">{{ r.provider_avatar || psInitials(r.provider_name) }}</span>
                      </div>
                      <div class="sic-party-info">
                        <a v-if="r.provider_slug" :href="`/public/provider/${r.provider_slug}`" class="sic-party-name" @click.stop>{{ r.provider_name }}</a>
                        <span v-else class="sic-party-name">{{ r.provider_name }}</span>
                        <span class="sic-date-sub">{{ r.provider_detail }}</span>
                      </div>
                    </div>
                  </td>
                  <td class="sic-td orq-td--service">
                    <div class="orq-service-title">{{ r.service_title }}</div>
                    <div class="sic-date-sub">{{ r.request_type }} · {{ r.time_label }}</div>
                  </td>
                  <td class="sic-td orq-td--status">
                    <AegisBadge :label="psStatusLabel(r.status)" :variant="psStatusVariant(r.status)" />
                  </td>
                  <td class="sic-td orq-td--actions">
                    <button type="button" class="btn-icon" @click.stop="psActiveOutgoing = r; psOutgoingDetail = true">
                      <AegisIcon name="chevron-right" :size="15" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- Outgoing detail modal -->
        <AegisModal v-model="psOutgoingDetail" title="Request Details" size="md">
          <template v-if="psActiveOutgoing">
            <div class="orq-modal-provider">
              <div class="orq-avatar orq-avatar--lg">
                <span>{{ psActiveOutgoing.provider_avatar || psInitials(psActiveOutgoing.provider_name) }}</span>
              </div>
              <div class="orq-modal-party-info">
                <a v-if="psActiveOutgoing.provider_slug" :href="`/public/provider/${psActiveOutgoing.provider_slug}`" class="orq-modal-name" target="_blank">{{ psActiveOutgoing.provider_name }}</a>
                <div v-else class="orq-modal-name">{{ psActiveOutgoing.provider_name }}</div>
                <div class="orq-modal-cred">{{ psActiveOutgoing.provider_detail }}</div>
              </div>
              <AegisBadge :label="psStatusLabel(psActiveOutgoing.status)" :variant="psStatusVariant(psActiveOutgoing.status)" style="margin-left:auto;flex-shrink:0" />
            </div>
            <div class="orq-modal-grid">
              <div class="orq-modal-row"><span class="orq-modal-label"><AegisIcon name="briefcase" :size="12" /> Service</span><span class="orq-modal-value">{{ psActiveOutgoing.service_title }}</span></div>
              <div class="orq-modal-row"><span class="orq-modal-label"><AegisIcon name="calendar" :size="12" /> Sent</span><span class="orq-modal-value">{{ psActiveOutgoing.sent_date_label }} ({{ psActiveOutgoing.time_label }})</span></div>
              <div v-if="psActiveOutgoing.responded_at" class="orq-modal-row"><span class="orq-modal-label"><AegisIcon name="check-circle" :size="12" /> Responded</span><span class="orq-modal-value">{{ psActiveOutgoing.responded_at }}</span></div>
            </div>
            <div v-if="psActiveOutgoing.message" class="orq-modal-block">
              <div class="orq-modal-block-label"><AegisIcon name="message-square" :size="13" /> Your message</div>
              <div class="orq-modal-block-body">{{ psActiveOutgoing.message }}</div>
            </div>
            <div v-if="psActiveOutgoing.response_note" class="orq-modal-block orq-modal-block--response">
              <div class="orq-modal-block-label"><AegisIcon name="corner-up-left" :size="13" /> Provider response</div>
              <div class="orq-modal-block-body">{{ psActiveOutgoing.response_note }}</div>
            </div>
            <div v-else-if="psActiveOutgoing.status === 'new'" class="orq-modal-pending">
              <AegisIcon name="clock" :size="14" /> Awaiting provider response — most providers respond within 72 hours.
            </div>
          </template>
          <template #footer>
            <button type="button" class="btn btn-outline" @click="psOutgoingDetail = false">Close</button>
            <button v-if="psActiveOutgoing?.status === 'new'" type="button" class="btn btn-danger" @click="psOutgoingDetail = false; psWithdraw(psActiveOutgoing.id)">
              <AegisIcon name="x" :size="13" /> Withdraw Request
            </button>
          </template>
        </AegisModal>
      </div>

      <!-- ── My Bookings ─────────────────────────────────────────────────── -->
      <div v-show="psTab === 'ps-bookings'">
        <BookedSessionTable
          :sessions="props.psClientSessions"
          :meta="props.psClientSessionsMeta"
          :show-invoice="true"
          empty-title="No booked sessions yet"
          empty-subtitle="Browse Practitioner Support & Services to book supervision, consultation, training and more."
          @pay-deposit="psActiveSession = $event; psModals.payUpfront = true"
          @pay-balance="psActiveSession = $event; psModals.payCompletion = true"
          @request-refund="psActiveSession = $event; psModals.requestRefund = true"
          @escalate-refund="psEscalateRefund($event)"
          @open-invoice="psActiveSession = $event"
          @page-change="psGoToClientPage"
        >
          <template #empty>
            <button class="btn btn-primary" @click="psTab = 'ps-browse'">
              <AegisIcon name="search" :size="13" /> Browse Services
            </button>
          </template>
        </BookedSessionTable>

        <!-- Payment modals -->
        <PayUpfrontModal    v-model="psModals.payUpfront"    :session="psActiveSession" @success="psActiveSession = null" />
        <PayCompletionModal v-model="psModals.payCompletion" :session="psActiveSession" @success="psActiveSession = null" />
        <PayDepositModal    v-model="psModals.payDeposit"    :session="psActiveSession" @success="psActiveSession = null" />
        <PayBalanceModal    v-model="psModals.payBalance"    :session="psActiveSession" @success="psActiveSession = null" />
        <RequestRefundModal v-model="psModals.requestRefund" :session="psActiveSession" @success="psActiveSession = null" />
        <ReviewRefundRequestModal v-model="psModals.reviewRefund" :refund-request="psActiveRefund" @success="psActiveRefund = null" />
      </div>

    </div><!-- /section ps -->

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { syncFormEnhancements } from '@/plugins/FormEnhancerPlugin'
import AppLayout from '@/layouts/AppLayout.vue'
import BookedSessionTable        from '@/components/ui/BookedSessionTable.vue'
import ServiceExploreCard        from '@/components/ui/ServiceExploreCard.vue'
import PayUpfrontModal           from '@/components/modals/PayUpfrontModal.vue'
import PayCompletionModal        from '@/components/modals/PayCompletionModal.vue'
import PayDepositModal           from '@/components/modals/PayDepositModal.vue'
import PayBalanceModal           from '@/components/modals/PayBalanceModal.vue'
import RequestRefundModal        from '@/components/modals/RequestRefundModal.vue'
import ReviewRefundRequestModal  from '@/components/modals/ReviewRefundRequestModal.vue'
import ServiceRequestModal       from '@/components/modals/ServiceRequestModal.vue'
import { useModal }              from '@/composables/useModal'
import { useInfiniteScroll }     from '@/composables/useInfiniteScroll'
import AegisPagination from '@/components/ui/AegisPagination.vue'
import PostJobModal from '@/components/modals/PostJobModal.vue'
import EditJobModal from '@/components/modals/EditJobModal.vue'
import ImportJobTemplatesModal from '@/components/modals/ImportJobTemplatesModal.vue'
import ManageApplicationsModal from '@/components/modals/ManageApplicationsModal.vue'
import ApplicantProfileModal from '@/components/modals/ApplicantProfileModal.vue'
import StageActionModal from '@/components/modals/StageActionModal.vue'
import ScheduleInterviewModal from '@/components/modals/ScheduleInterviewModal.vue'
import RejectModal from '@/components/modals/RejectModal.vue'
import HireModal from '@/components/modals/HireModal.vue'
import EngagementRequestModal from '@/components/modals/EngagementRequestModal.vue'
import ContractModal from '@/components/modals/ContractModal.vue'
import ReviewContractModal from '@/components/modals/ReviewContractModal.vue'
import BpFinanceTable from '@/components/ui/BpFinanceTable.vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { useMessageButton } from '@/composables/useMessageButton'

const props = defineProps({
  jobs:            { type: Array,  default: () => [] },
  proposalsByJob:  { type: Object, default: () => ({}) },
  activeContracts:       { type: Array,  default: () => [] },
  engagementRequests:    { type: Array,  default: () => [] },
  milestonesByContract:  { type: Object, default: () => ({}) },
  invoicesByContract:    { type: Object, default: () => ({}) },
  bpInvoices:            { type: Array,  default: () => [] },
  escrowSummary:         { type: Object, default: () => ({ total_held_cents: 0, total_unfunded_cents: 0, funded_count: 0, contracts_needing_funding: 0 }) },
  has_valid_default_pm:  { type: Boolean, default: false },
  bpStats:         { type: Object, default: () => ({}) },
  stats: {
    type: Object,
    default: () => ({ open: 0, draft: 0, paused: 0, filled: 0, closed: 0, total_jobs: 0, total_proposals: 0, pending_proposals: 0, hired: 0, total_spent_cents: 0, engagement_requests: 0 }),
  },
  // Practitioner Support & Services props (Practice tier only)
  psExploreResults:         { type: Array,   default: () => [] },
  psExploreMeta:            { type: Object,  default: () => ({ current_page: 1, last_page: 1, total: 0, per_page: 12 }) },
  psExploreFilters:         { type: Object,  default: () => ({}) },
  psOutgoingRequests:       { type: Array,   default: () => [] },
  psClientSessions:         { type: Array,   default: () => [] },
  psClientSessionsMeta:     { type: Object,  default: () => ({ current_page: 1, last_page: 1, total: 0, per_page: 10 }) },
  psIncomingRefundRequests: { type: Array,   default: () => [] },
  psTier:                   { type: String,  default: 'access' },
})

const toast = useToast()
const { confirmAction } = useConfirm()
const { openConversation, loading: msgLoading } = useMessageButton()
const { openModal } = useModal()

// ── Top-level section switcher ────────────────────────────────────────────────
// 'bp' = Business Partner Support & Services (all tiers)
// 'ps' = Practitioner Support & Services (Practice tier only)
const isPracticeTier = computed(() => props.psTier === 'practice')
const section = ref('bp')
onMounted(() => {
  const s = new URLSearchParams(window.location.search).get('section')
  if (s === 'ps' && isPracticeTier.value) section.value = 'ps'
})

const tab = ref('my-postings')
const hiredSubTab = ref('active')

watch(tab, () => syncFormEnhancements())
const engagements = computed(() => Array.isArray(props.engagementRequests) ? props.engagementRequests : [])

// ── Practitioner Support & Services state ─────────────────────────────────────
const psTab              = ref('ps-browse')
const psActiveOutgoing   = ref(null)
const psActiveSession    = ref(null)
const psOutgoingDetail   = ref(false)

const psModals = reactive({
  payUpfront: false, payCompletion: false,
  payDeposit: false, payBalance: false,
  requestRefund: false, reviewRefund: false,
})

const psActiveRefund     = ref(null)

// Explore
const psExploreFilters   = reactive({
  q: props.psExploreFilters?.q ?? '',
  category: props.psExploreFilters?.category ?? '',
  format: props.psExploreFilters?.format ?? '',
  availability: props.psExploreFilters?.availability ?? '',
  sort: props.psExploreFilters?.sort ?? 'newest',
})
const psExploreLoading   = ref(false)
const psExploreSentinel  = ref(null)
const psExploreMeta      = ref({ ...props.psExploreMeta })
const psExploreLocal     = ref([...props.psExploreResults])
watch(() => props.psExploreResults, v => { psExploreLocal.value = [...v] })
watch(() => props.psExploreMeta,    v => { psExploreMeta.value  = { ...v } })
const psExploreResults   = computed(() => psExploreLocal.value)

const psServiceCategories = [
  { value: 'supervision',         label: 'Clinical Supervision' },
  { value: 'consultation',        label: 'Consultation' },
  { value: 'training',            label: 'Training' },
  { value: 'coaching',            label: 'Coaching' },
  { value: 'practice_continuity', label: 'Practice Continuity' },
  { value: 'mentorship',          label: 'Mentorship' },
  { value: 'other',               label: 'Other' },
]

function psClearFilters() {
  Object.assign(psExploreFilters, { q: '', category: '', format: '', availability: '', sort: 'newest' })
  psDoSearch()
}
function psDoSearch() {
  psExploreLocal.value = []
  router.visit(route('jobs.index'), {
    method: 'get',
    data: {
      section: 'ps',
      ps_q:            psExploreFilters.q || undefined,
      ps_category:     psExploreFilters.category || undefined,
      ps_format:       psExploreFilters.format || undefined,
      ps_availability: psExploreFilters.availability || undefined,
      ps_sort:         psExploreFilters.sort !== 'newest' ? psExploreFilters.sort : undefined,
    },
    preserveState: true, preserveScroll: true, replace: true,
  })
}

const { observe: psObserve, disconnect: psDisconnect } = useInfiniteScroll(
  psExploreSentinel,
  psLoadMore,
  { canLoad: computed(() => (psExploreMeta.value.current_page ?? 1) < (psExploreMeta.value.last_page ?? 1)) }
)

async function psLoadMore() {
  if (psExploreLoading.value) return
  const nextPage = (psExploreMeta.value.current_page ?? 1) + 1
  if (nextPage > (psExploreMeta.value.last_page ?? 1)) return
  psExploreLoading.value = true
  try {
    const params = new URLSearchParams({
      page: String(nextPage),
      ...(psExploreFilters.q            && { q: psExploreFilters.q }),
      ...(psExploreFilters.category     && { category: psExploreFilters.category }),
      ...(psExploreFilters.format       && { format: psExploreFilters.format }),
      ...(psExploreFilters.availability && { availability: psExploreFilters.availability }),
      ...(psExploreFilters.sort !== 'newest' && { sort: psExploreFilters.sort }),
    })
    const res = await window.axios.get(route('jobs.ps.explore') + '?' + params.toString())
    psExploreLocal.value.push(...(res.data.results ?? []))
    psExploreMeta.value = {
      current_page: res.data.current_page,
      last_page:    res.data.last_page,
      total:        res.data.total,
      per_page:     psExploreMeta.value.per_page,
    }
  } catch {
    toast.error('Could not load more services.')
  } finally {
    psExploreLoading.value = false
  }
}

onMounted(() => psObserve())
onUnmounted(() => psDisconnect())

const psSvcModalRef = ref(null)
const psSvcTarget   = reactive({ id: '', label: '' })
function psOpenRequest(svc) {
  psSvcTarget.id    = svc.practitioner_id ?? ''
  psSvcTarget.label = svc.practitioner_name ?? ''
  psSvcModalRef.value?.preselect(svc.title ?? '')
  openModal('serviceRequestModal')
}

function psPendingOutgoing() { return (props.psOutgoingRequests ?? []).filter(r => r.status === 'new').length }
function psClientBadge() {
  const u = (props.psClientSessions ?? []).filter(s => s.can_pay_deposit).length
  const b = (props.psClientSessions ?? []).filter(s => s.can_pay_balance).length
  return u + b
}
function psStatusLabel(s) { return { completed: 'Completed', accepted: 'Accepted', declined: 'Declined', countered: 'Counter Sent', new: 'New', scheduled: 'Scheduled', withdrawn: 'Withdrawn' }[s] ?? s }
function psStatusVariant(s) { return { completed: 'green', accepted: 'green', declined: 'neutral', countered: 'blue', new: 'gold', scheduled: 'blue', withdrawn: 'neutral' }[s] ?? 'neutral' }
function psInitials(name) { return (name || '').split(' ').slice(0,2).map(p => p[0] ?? '').join('').toUpperCase() || '?' }
function psWithdraw(id) {
  confirmAction({ title: 'Withdraw Request', message: 'Withdraw this service request?', btnLabel: 'Withdraw', type: 'danger' }, () => {
    router.delete(route('provider.services.request.withdraw', { serviceRequest: id }), { preserveScroll: true, onSuccess: () => toast.info('Request withdrawn.') })
  })
}
function psGoToClientPage(page) {
  router.visit(route('jobs.index'), { data: { section: 'ps', ps_client_sessions_page: page }, preserveState: true, preserveScroll: true, replace: true })
}
function psEscalateRefund(ses) {
  const rr = (props.psIncomingRefundRequests ?? []).find(r => r.session_id === ses.id) || { id: ses.refund_request_id }
  if (!rr?.id) { toast.error('No denied refund request found.'); return }
  confirmAction({ title: 'Escalate to Dispute', message: 'This will open a formal dispute reviewed by Aegis admin.', btnLabel: 'Escalate', type: 'danger' }, () => {
    router.post(route('provider.services.refund.escalate', { refund: rr.id }), {}, {
      preserveScroll: true, onSuccess: () => toast.success('Escalated. Our team will review.'), onError: () => toast.error('Could not escalate.')
    })
  })
}

// BpFinanceTable: payment method flag — prefer the server-verified
// has_valid_default_pm prop (mirrors Finances), fall back to the local
// auth user column so the button still works if the controller is out of sync.
const page             = usePage()
const hasPaymentMethod = computed(() =>
  !!props.has_valid_default_pm || !!(page.props.auth?.user?.stripe_payment_method_id)
)

const showPostJob  = ref(false)
const showTemplates = ref(false)
const showEdit = ref(false)
const editingJob = ref(null)
const postJobPrefill = ref(null)

const showManageApps = ref(false)
const manageAppsJob = ref(null)

const showProfile = ref(false)
const showStage = ref(false)
const stageMode = ref('reviewed')
const showSchedule = ref(false)
const showReject = ref(false)
const showHire = ref(false)
// Store only the ID of the active proposal — the object itself is always
// derived live from proposalsByJob so it reflects Inertia page reloads.
const _activeProposalId = ref(null)
const activeProposal = computed(() => {
  if (!_activeProposalId.value) return null
  return allProposals.value.find(p => p.id === _activeProposalId.value) ?? null
})

const showContract = ref(false)
const activeContract = ref(null)

// ── Review modal — auto-opens on mount when a completed contract has no review yet ──
// (page = usePage() already declared above near hasPaymentMethod)
const showReview       = ref(false)
const reviewContract   = ref(null)

// Only open after a contract just completed — reads flash set by releasePayment / reviewMilestone
watch(
  () => page.props.flash?.review_contract_id,
  (contractId) => {
    if (!contractId) return
    const c = props.activeContracts.find(c => c.id === contractId)
    if (!c) return
    reviewContract.value = {
      id:                c.id,
      title:             c.title,
      counterparty_name: c.bp?.display_name ?? 'Business Partner',
    }
    showReview.value = true
  },
  { immediate: true }
)

// Unwrap backed enum values — Inertia may serialise them as {value:'x'} objects
// instead of plain strings depending on Laravel/Inertia version. Always use val()
// when comparing model enum fields (status, budget_type, etc.) in Vue.
const val = (v) => (v && typeof v === 'object' && 'value' in v) ? v.value : (v ?? '')

const openJobs = computed(() => props.jobs.filter(j => val(j.status) === 'open'))

const allProposals = computed(() => Object.values(props.proposalsByJob).flat())

const filteredJobs = computed(() => {
  if (postingFilter.value === 'all') return props.jobs
  if (postingFilter.value === 'closed') return props.jobs.filter(j => ['closed', 'cancelled', 'filled'].includes(val(j.status)))
  return props.jobs.filter(j => val(j.status) === postingFilter.value)
})

const filteredApplications = computed(() => {
  const list = applicationsJobFilter.value
    ? (props.proposalsByJob[applicationsJobFilter.value] || [])
    : allProposals.value
  return [...list].sort((a, b) => new Date(b.submitted_at) - new Date(a.submitted_at))
})

// ── Client-side pagination ────────────────────────────────────────────────────
const JOBS_PER_PAGE  = 5
const APPS_PER_PAGE  = 8

const jobsPage  = ref(1)
const appsPage  = ref(1)

// Reset to page 1 whenever filter changes
watch(postingFilter,         () => { jobsPage.value = 1 })
watch(applicationsJobFilter, () => { appsPage.value = 1 })

const jobsTotalPages  = computed(() => Math.max(1, Math.ceil(filteredJobs.value.length / JOBS_PER_PAGE)))
const appsTotalPages  = computed(() => Math.max(1, Math.ceil(filteredApplications.value.length / APPS_PER_PAGE)))

const pagedJobs = computed(() => {
  const start = (jobsPage.value - 1) * JOBS_PER_PAGE
  return filteredJobs.value.slice(start, start + JOBS_PER_PAGE)
})
const pagedApplications = computed(() => {
  const start = (appsPage.value - 1) * APPS_PER_PAGE
  return filteredApplications.value.slice(start, start + APPS_PER_PAGE)
})

const kanbanColumns = [
  { stage: 'new',         label: 'New',         icon: 'download',       color: 'var(--blue-dark)' },
  { stage: 'reviewed',    label: 'Reviewing',   icon: 'eye',            color: 'var(--orange)' },
  { stage: 'shortlisted', label: 'Shortlisted', icon: 'star',           color: 'var(--text)' },
  { stage: 'interview',   label: 'Interview',   icon: 'calendar',       color: 'var(--gold-dark)' },
  { stage: 'hired',       label: 'Hired',       icon: 'check',          color: 'var(--green)' },
  { stage: 'rejected',    label: 'Rejected',    icon: 'x',              color: 'var(--red)' },
]
const stageOrder = ['new', 'reviewed', 'shortlisted', 'interview', 'hired']

function pipelineGroup(stage) {
  const list = pipelineJobFilter.value
    ? (props.proposalsByJob[pipelineJobFilter.value] || [])
    : allProposals.value

  return list.filter(p => {
    const pStage  = p.pipeline_stage || 'new'
    const pStatus = val(p.status)

    // Withdrawn never appears anywhere
    if (pStatus === 'withdrawn') return false

    // Hired bucket: accepted status OR pipeline_stage=hired (always exclusive)
    if (stage === 'hired') return pStatus === 'accepted' || pStage === 'hired'

    // Accepted proposals ONLY go in hired — exclude from all other columns
    if (pStatus === 'accepted') return false

    // Rejected bucket: declined status OR pipeline_stage=rejected
    if (stage === 'rejected') return pStatus === 'declined' || pStage === 'rejected'

    // Declined/rejected proposals don't appear in active columns
    if (pStatus === 'declined' || pStage === 'rejected') return false

    // Active proposal — match by pipeline_stage
    return pStage === stage
  })
}

function nextStages(currentStage) {
  const idx = stageOrder.indexOf(currentStage)
  return stageOrder.slice(idx + 1).map(s => ({ stage: s, label: kanbanColumns.find(c => c.stage === s)?.label }))
}

function moveStage(proposal, stage) {
  if (stage === 'hired') {
    openHire(proposal)
    return
  }
  router.post(route('provider.jobs.proposal.stage', { job: proposal.job_id, proposal: proposal.id }), { pipeline_stage: stage }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Applicant moved.'),
    onError:   () => toast.error('Could not update applicant.'),
  })
}

// ── Lookups / formatting ─────────────────────────────────────────────
const categoryLabels = {
  billing: 'Billing', technology: 'IT', it: 'IT', marketing: 'Marketing', legal: 'Legal',
  admin: 'Admin', accounting: 'Accounting', compliance: 'Compliance', credentialing: 'Credentialing',
  consulting: 'Consulting', design: 'Design', hr: 'HR',
}
function categoryLabel(cat) { return categoryLabels[cat] || (cat ? cat.charAt(0).toUpperCase() + cat.slice(1) : '—') }
function locationLabel(loc) { return ({ remote: 'Remote', onsite: 'On-Site', hybrid: 'Hybrid' }[loc] || 'Remote') }
function bpTypeLabel(t) { return t ? t.charAt(0).toUpperCase() + t.slice(1) : 'Business Partner' }

function formatBudget(job) {
  if (!job.budget_amount_cents) return 'TBD'
  const amt = '$' + (job.budget_amount_cents / 100).toLocaleString()
  return job.budget_type === 'hourly' ? amt + '/hr' : amt + '/mo'
}
function formatCents(cents) {
  if (!cents) return '—'
  return '$' + (cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}

const statusLabels = { open: 'Active', draft: 'Draft', paused: 'Paused', closed: 'Closed', filled: 'Closed', cancelled: 'Closed' }
function statusLabel(s) { const v = val(s); return statusLabels[v] || v }
function statusBadgeClass(s) {
  return { open: 'badge-green', draft: 'badge-gray', paused: 'badge-orange', closed: 'badge-red', filled: 'badge-red', cancelled: 'badge-red' }[val(s)] || 'badge-gray'
}
function rowStyle(status) {
  const s = val(status)
  if (['draft', 'paused'].includes(s)) return { opacity: 0.75 }
  if (['filled', 'closed', 'cancelled'].includes(s)) return { opacity: 0.6 }
  return {}
}

function proposalsForJob(jobId) { return props.proposalsByJob[jobId] || [] }
function proposalCount(jobId) { return proposalsForJob(jobId).length }
function newProposalCount(jobId) { return proposalsForJob(jobId).filter(p => (p.pipeline_stage || 'new') === 'new').length }
function jobTitle(jobId) { return props.jobs.find(j => j.id === jobId)?.title ?? '—' }

function proposalStatus(p) { return val(p.status) }
function proposalStatusLabel(p) {
  const s = proposalStatus(p)
  const stage = p.pipeline_stage || 'new'
  if (s === 'accepted') return 'Hired'
  if (s === 'declined') return 'Declined'
  if (s === 'withdrawn') return 'Withdrawn'
  return ({ new: 'New', reviewed: 'Reviewing', shortlisted: 'Shortlisted', interview: 'Interview', hired: 'Hired' }[stage] || 'New')
}
function proposalStatusBadgeClass(p) {
  const s = proposalStatus(p)
  const stage = p.pipeline_stage || 'new'
  if (s === 'accepted') return 'badge-green'
  if (s === 'declined') return 'badge-red'
  if (s === 'withdrawn') return 'badge-gray'
  return ({ new: 'badge-gray', reviewed: 'badge-orange', shortlisted: 'badge-blue', interview: 'badge-gold', hired: 'badge-green' }[stage] || 'badge-gray')
}

const avatarPalette = ['var(--gold-dark)']
function initials(name) {
  if (!name) return 'BP'
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}
function avatarStyle(bp) {
  if (bp?.avatar_url) {
    return { backgroundImage: `url(${bp.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center' }
  }
  const i = (bp?.id?.charCodeAt(2) ?? 0) % avatarPalette.length
  return { background: avatarPalette[i] }
}

// ── Actions ───────────────────────────────────────────────────────────
function openEdit(job) {
  editingJob.value = job
  showEdit.value = true
}

function openManageApps(job) {
  manageAppsJob.value = job
  showManageApps.value = true
}

function setStatus(job, status, okMsg) {
  router.post(route('provider.jobs.status', job.id), { status }, {
    preserveScroll: true,
    onSuccess: () => toast.success(okMsg),
    onError:   () => toast.error('Could not update posting.'),
  })
}

function confirmPause(job) {
  confirmAction(
    { title: 'Pause Posting', message: 'Pause this posting? Business Partners will not see it until you resume.', confirmLabel: 'Pause', destructive: true },
    () => setStatus(job, 'paused', 'Posting paused.'),
  )
}

function confirmPublish(job) {
  confirmAction(
    { title: 'Publish Posting', message: `Publish "${job.title}"? It will become visible to all Business Partners on Aegis immediately.`, confirmLabel: 'Publish', destructive: false },
    () => setStatus(job, 'open', 'Posting published — live to Business Partners.'),
  )
}

function confirmResume(job) {
  confirmAction(
    { title: 'Resume Posting', message: `Resume "${job.title}"? Business Partners will be able to see and apply to it again.`, confirmLabel: 'Resume', destructive: false },
    () => setStatus(job, 'open', 'Posting resumed.'),
  )
}

// ── Applicant workflow ───────────────────────────────────────────────
function openProfile(proposal) {
  _activeProposalId.value = proposal.id
  showManageApps.value = false
  showProfile.value = true
}

function openStage(proposal, stage) {
  _activeProposalId.value = proposal.id
  stageMode.value = stage
  showStage.value = true
}

function openSchedule(proposal) {
  _activeProposalId.value = proposal.id
  showSchedule.value = true
}

function openReject(proposal) {
  _activeProposalId.value = proposal.id
  showReject.value = true
}

function openHire(proposal) {
  _activeProposalId.value = proposal.id
  showHire.value = true
}

function contractStatusLabel(c) {
  return {
    active:            'Active',
    pending_signature: 'Awaiting Signature',
    pending_funding:   'Awaiting Funding',
    completed:         'Completed',
    cancelled:         'Cancelled',
  }[val(c.status)] ?? val(c.status)
}

function contractStatusVariant(c) {
  return {
    active:            'green',
    pending_signature: 'gold',
    pending_funding:   'blue',
    completed:         'neutral',
    cancelled:         'red',
  }[val(c.status)] ?? 'neutral'
}

function openContract(c) {
  activeContract.value = c
  showContract.value = true
}

function openReviewForContract(c) {
  reviewContract.value = {
    id:                c.id,
    title:             c.title,
    counterparty_name: c.bp?.display_name ?? 'Business Partner',
  }
  showReview.value = true
}

function onUseTemplate(t) {
  postJobPrefill.value = t
  showPostJob.value = true
}
</script>

<style scoped>
/* ── Section switcher ───────────────────────────────────────────────────── */
.ss-section-switcher {
  display: flex;
  gap: 10px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}
.ss-section-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  border: 2px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface);
  cursor: pointer;
  transition: border-color var(--transition), background var(--transition);
  flex: 1;
  min-width: 240px;
  text-align: left;
}
.ss-section-btn.active {
  border-color: var(--gold);
  background: var(--badge-bg-gold);
}
.ss-section-btn--locked {
  opacity: .55;
  cursor: default;
}
.ss-section-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 2px;
}
.ss-section-sub {
  font-size: 11px;
  color: var(--text-3);
  line-height: 1.4;
}

/* ── Practitioner Support hero ──────────────────────────────────────────── */
.ps-hero {
  padding: 20px 0 12px;
}
.ps-hero-title {
  font-size: 18px;
  font-weight: 700;
  color: var(--text);
  margin: 0 0 8px;
  font-family: var(--font-serif, serif);
}
.ps-hero-desc {
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.6;
  max-width: 760px;
  margin: 0 0 16px;
}

/* ── Category chips ─────────────────────────────────────────────────────── */
.ps-category-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 12px;
}
.ps-cat-chip {
  display: inline-flex;
  align-items: center;
  padding: 5px 12px;
  font-size: 12px;
  font-weight: 600;
  border: 1px solid var(--border);
  border-radius: var(--radius-full);
  background: var(--surface);
  color: var(--text-2);
  cursor: pointer;
  transition: all var(--transition);
}
.ps-cat-chip:hover   { border-color: var(--gold); color: var(--gold-dark); }
.ps-cat-chip.active  { border-color: var(--gold); background: var(--badge-bg-gold); color: var(--gold-dark); }

/* ── My Postings table ───────────────────────────────────────────── */
.jp-my-table {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}
.jp-my-table-head {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr 120px;
  background: var(--surface-2);
  border-bottom: 1px solid var(--border);
  padding: 10px 20px;
  font-family: var(--font-sans);
  font-size: 10.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--text-4);
}
.jp-my-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr 120px;
  padding: 14px 20px;
  border-bottom: 1px solid var(--border);
  align-items: center;
  font-family: var(--font-sans);
  transition: background var(--transition);
  cursor: pointer;
}
.jp-my-row:last-child { border-bottom: none; }
.jp-my-row:hover { background: var(--surface-2); }
.jp-my-title {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 3px;
}
.jp-my-sub {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: var(--text-4);
}

/* ── Applications table ──────────────────────────────────────────── */
.jp-app-table-head {
  display: grid;
  grid-template-columns: 44px 2fr 1.2fr 1fr 1fr 110px;
  gap: 12px;
  padding: 10px 20px;
  background: var(--surface-2);
  border-bottom: 1px solid var(--border);
  font-family: var(--font-sans);
  font-size: 10.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--text-4);
}
.jp-app-row {
  display: grid;
  grid-template-columns: 44px 2fr 1.2fr 1fr 1fr 110px;
  gap: 12px;
  padding: 13px 20px;
  border-bottom: 1px solid var(--border);
  align-items: center;
  font-family: var(--font-sans);
  transition: background var(--transition);
  cursor: pointer;
}
.jp-app-row:hover { background: var(--surface-2); }
.jp-app-avatar {
  width: 38px;
  height: 38px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-sans);
  font-size: 12px;
  font-weight: 700;
  color: var(--text-inverted);
  flex-shrink: 0;
}
.jp-app-name { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 2px; }
.jp-app-role { font-size: 11px; color: var(--text-4); }

/* ── Hired cards grid ────────────────────────────────────────────── */
.jp-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(288px, 1fr));
  gap: 14px;
}

.jp-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 0;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: box-shadow var(--transition), transform var(--transition);
  cursor: pointer;
  position: relative;
}
.jp-card:hover {
  box-shadow: var(--shadow);
  transform: translateY(-2px);
}

/* Left accent stripe — replaces border-color hack */
.jp-card::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background: var(--border);
  border-radius: var(--radius-lg) 0 0 var(--radius-lg);
  transition: background var(--transition);
}
.jp-card.is-active::before { background: var(--green); }
.jp-card.is-closed::before { background: var(--border-dark); }
.jp-card.is-closed { opacity: 0.8; }

.jp-card-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 18px 14px 22px; /* extra-left for stripe */
  flex: 1;
}
.jp-card-logo {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-sm);
  font-family: var(--font-sans);
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
}
.jp-card-body { min-width: 0; flex: 1; }
.jp-card-title {
  font-family: var(--font-sans);
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 3px;
  line-height: 1.3;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.jp-card-practice {
  font-family: var(--font-sans);
  font-size: 12px;
  color: var(--text-4);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.jp-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 10px 18px 12px 22px;
  border-top: 1px solid var(--border);
  background: var(--surface-2);
}
.jp-card-footer-left {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  min-width: 0;
}
.jp-card-actions { display: flex; gap: 4px; }

/* Review state chips on closed cards */
.review-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: var(--radius-full);
  font-family: var(--font-sans);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.2px;
  border: 1px solid;
}
.review-chip-done {
  background: rgba(160,129,62,0.08);
  border-color: var(--gold);
  color: var(--gold-dark);
}
.review-chip-pending {
  background: var(--surface-3);
  border-color: var(--border-dark);
  color: var(--text-4);
}

/* ── Hiring Pipeline kanban ──────────────────────────────────────── */
.jp-kanban {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 10px;
  min-height: 320px;
}
.jp-kanban-col {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 12px 10px;
}
.jp-kanban-col-header {
  font-family: var(--font-sans);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.7px;
  padding: 2px 4px 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--border);
  margin-bottom: 8px;
}
.jp-kanban-count {
  background: var(--gold-dark);
  border-radius: var(--radius-full);
  padding: 1px 7px;
  font-size: 10px;
  font-weight: 700;
  color: #fff;
  line-height: 1.6;
}
.jp-kanban-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 10px 12px;
  margin-bottom: 6px;
  transition: box-shadow var(--transition), transform var(--transition);
  cursor: pointer;
}
.jp-kanban-card:hover { box-shadow: var(--shadow); transform: translateY(-1px); }
.jp-kanban-name { font-family: var(--font-sans); font-size: 12px; font-weight: 700; color: var(--text); margin-bottom: 2px; }
.jp-kanban-role { font-family: var(--font-sans); font-size: 11px; color: var(--text-4); margin-bottom: 2px; }
.jp-kanban-rate { font-family: var(--font-sans); font-size: 11px; font-weight: 700; color: var(--green-dark); }

/* ── Pager ───────────────────────────────────────────────────────── */
.pager {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--border);
}
.pager-info {
  font-family: var(--font-sans);
  font-size: 12px;
  color: var(--text-4);
}

/* ── Requests tab table ──────────────────────────────────────────── */
.ert-partner-cell  { display: flex; align-items: center; gap: 10px; }
.ert-partner-link  { font-family: var(--font-sans); font-size: 13px; font-weight: 600; color: var(--gold-dark); text-decoration: none; }
.ert-partner-link:hover { text-decoration: underline; }
.ert-partner-name  { font-family: var(--font-sans); font-size: 13px; font-weight: 600; color: var(--text); }
.ert-partner-type  { font-family: var(--font-sans); font-size: 11px; color: var(--text-4); margin-top: 2px; }
.ert-details-cell  { max-width: 200px; }
.ert-details-primary { font-family: var(--font-sans); font-size: 12px; font-weight: 600; color: var(--text); }
.ert-details-sub   { display: inline-flex; align-items: center; gap: 3px; font-family: var(--font-sans); font-size: 11px; color: var(--text-4); margin-top: 2px; }
.ert-date-cell     { font-family: var(--font-sans); font-size: 11px; color: var(--text-4); white-space: nowrap; }
.ert-actions-cell  { display: flex; gap: 6px; justify-content: flex-end; }
.ert-pagination    { display: flex; align-items: center; gap: 10px; justify-content: center; margin-top: 20px; }
.ert-page-info     { font-family: var(--font-sans); font-size: 12px; color: var(--text-3); font-weight: 600; min-width: 60px; text-align: center; }

/* ── Responsive ──────────────────────────────────────────────────── */
@media (max-width: 900px) {
  .jp-kanban { grid-template-columns: repeat(3, 1fr); }
  .jp-my-table-head, .jp-my-row { grid-template-columns: 2fr 1fr 1fr 90px; }
  .jp-my-table-head > *:nth-child(3),
  .jp-my-table-head > *:nth-child(4),
  .jp-my-row > *:nth-child(3),
  .jp-my-row > *:nth-child(4) { display: none; }
  .jp-grid { grid-template-columns: 1fr; }
}

/* ── Help / How It Works pane ────────────────────────────────────── */
.help-pane {
  max-width: 780px;
}

.help-intro {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 22px 24px;
  margin-bottom: 32px;
  box-shadow: var(--shadow-sm);
}
.help-intro-icon {
  width: 52px;
  height: 52px;
  border-radius: var(--radius);
  background: var(--icon-bg-gold);
  color: var(--gold-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.help-intro-title {
  font-family: var(--font-sans);
  font-size: 16px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 6px;
}
.help-intro-sub {
  font-family: var(--font-sans);
  font-size: 13px;
  color: var(--text-3);
  line-height: 1.55;
}

.help-section-label {
  font-family: var(--font-sans);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: var(--text-4);
  margin-bottom: 16px;
}

/* Steps */
.help-steps {
  display: flex;
  flex-direction: column;
}
.help-step {
  display: flex;
  gap: 18px;
  align-items: flex-start;
}
.help-step-num {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--gold-dark);
  color: #fff;
  font-family: var(--font-sans);
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 2px;
}
.help-step-body {
  flex: 1;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 18px 20px;
  box-shadow: var(--shadow-sm);
}
.help-step-title {
  font-family: var(--font-sans);
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 8px;
}
.help-step-desc {
  font-family: var(--font-sans);
  font-size: 13px;
  color: var(--text-3);
  line-height: 1.55;
  margin-bottom: 12px;
}

.help-step-connector {
  display: flex;
  justify-content: flex-start;
  padding-left: 9px;
  color: var(--border-dark);
  margin: 6px 0;
}

/* Callouts */
.help-callout {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  border-radius: var(--radius);
  padding: 10px 14px;
  font-family: var(--font-sans);
  font-size: 12.5px;
  line-height: 1.5;
  margin-bottom: 12px;
}
.help-callout-gold  { background: rgba(160,129,62,0.09); border-left: 3px solid var(--gold-dark); color: var(--text-2); }
.help-callout-blue  { background: var(--icon-bg-blue);   border-left: 3px solid var(--blue-dark); color: var(--text-2); }
.help-callout-green { background: var(--icon-bg-green);  border-left: 3px solid var(--green-dark); color: var(--text-2); }
.help-callout-orange { background: rgba(220,140,30,0.09); border-left: 3px solid var(--orange); color: var(--text-2); }

/* Tags */
.help-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
.help-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 9px;
  border-radius: var(--radius-full);
  background: var(--surface-2);
  border: 1px solid var(--border);
  font-family: var(--font-sans);
  font-size: 11px;
  font-weight: 600;
  color: var(--text-3);
}
.help-tag-green { border-color: var(--green); color: var(--green-dark); background: var(--icon-bg-green); }

/* 2-col mini cards */
.help-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 4px;
}
.help-grid-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 10px;
}
.help-mini-card {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px 14px;
  font-family: var(--font-sans);
  font-size: 12px;
  color: var(--text-3);
  line-height: 1.5;
}
.help-mini-icon {
  width: 30px;
  height: 30px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* Escrow diagram */
.help-escrow-diagram {
  display: flex;
  align-items: center;
  gap: 0;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 18px 20px;
  margin-bottom: 4px;
}
.help-escrow-node {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  font-family: var(--font-sans);
  font-size: 12px;
  font-weight: 700;
  color: var(--text-2);
  flex: 1;
  text-align: center;
}
.help-escrow-node-provider { color: var(--text-2); }
.help-escrow-node-aegis    { color: var(--gold-dark); }
.help-escrow-node-bp       { color: var(--green-dark); }
.help-escrow-sub {
  font-size: 10px;
  font-weight: 400;
  color: var(--text-4);
}
.help-escrow-arrow {
  display: flex;
  align-items: center;
  gap: 0;
  flex-shrink: 0;
  color: var(--border-dark);
}
.help-escrow-arrow-line {
  width: 32px;
  height: 2px;
  background: var(--border-dark);
}

/* Review options */
.help-review-options {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 4px;
}
.help-review-row {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  border-radius: var(--radius);
  padding: 12px 14px;
  font-family: var(--font-sans);
  font-size: 12.5px;
}
.help-review-approve { background: var(--icon-bg-green); color: var(--green-dark); }
.help-review-revision { background: var(--icon-bg-blue); color: var(--blue-dark); }
.help-review-dispute { background: rgba(210,60,60,0.07); color: var(--red-dark); }
.help-review-label {
  font-weight: 700;
  font-size: 13px;
  margin-bottom: 3px;
}
.help-review-detail {
  font-size: 12px;
  opacity: 0.85;
  line-height: 1.45;
}

/* Requests tab card */
.help-card {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px 22px;
  margin-bottom: 28px;
  box-shadow: var(--shadow-sm);
}
.help-card-icon {
  width: 44px;
  height: 44px;
  border-radius: var(--radius);
  background: var(--icon-bg-gold);
  color: var(--gold-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.help-card-title {
  font-family: var(--font-sans);
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 6px;
}
.help-card-desc {
  font-family: var(--font-sans);
  font-size: 13px;
  color: var(--text-3);
  line-height: 1.55;
}

/* Type chips */
.help-type-chip {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  border-radius: var(--radius);
  padding: 10px 12px;
  border: 1px solid;
  font-family: var(--font-sans);
}
.help-type-hire    { background: rgba(160,129,62,0.07); border-color: var(--gold); color: var(--gold-dark); }
.help-type-quote   { background: var(--icon-bg-blue); border-color: var(--blue-dark); color: var(--blue-dark); }
.help-type-consult { background: var(--icon-bg-green); border-color: var(--green); color: var(--green-dark); }
.help-type-name { font-size: 12px; font-weight: 700; margin-bottom: 3px; }
.help-type-detail { font-size: 11px; opacity: 0.8; line-height: 1.4; }

/* Safety banner */
.help-safety-banner {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  background: linear-gradient(135deg, rgba(160,129,62,0.06) 0%, rgba(160,129,62,0.02) 100%);
  border: 1px solid var(--gold);
  border-radius: var(--radius-lg);
  padding: 22px 24px;
  margin-bottom: 28px;
}
.help-safety-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  background: rgba(160,129,62,0.12);
  color: var(--gold-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.help-safety-title {
  font-family: var(--font-sans);
  font-size: 14px;
  font-weight: 700;
  color: var(--gold-dark);
  margin-bottom: 6px;
}
.help-safety-body {
  font-family: var(--font-sans);
  font-size: 13px;
  color: var(--text-3);
  line-height: 1.55;
}

@media (max-width: 680px) {
  .help-escrow-diagram { flex-direction: column; }
  .help-grid-2 { grid-template-columns: 1fr; }
  .help-grid-3 { grid-template-columns: 1fr; }
}
</style>
