<!--
  pages/public/BusinessProfile.vue — public Business Partner profile.
  PHP source parity: public/business.php
  Visibility tiers: anonymous | logged-in | owner
-->
<template>
  <PublicLayout>
    <div class="public-profile-wrap">

      <!-- HERO -->
      <div class="hero-banner is-quiet">
        <div class="page-hero-inner">
          <div class="page-hero-left has-icon">
            <div class="page-hero-icon is-avatar" aria-hidden="true"
                 :style="user.avatar_url ? { backgroundImage: `url(${user.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}">
              <template v-if="!user.avatar_url">{{ avatarInitials }}</template>
            </div>
            <div class="page-hero-text">
              <div class="page-hero-eyebrow">Business Partner</div>
              <h1 class="page-hero-title">{{ businessName }}</h1>
              <div class="page-hero-sub">{{ primaryLabel }} &nbsp;·&nbsp; {{ user.bp_type === 'agency' ? 'Agency' : 'Freelancer' }}</div>
              <div class="hero-badges">
                <span class="badge badge-green"><span class="badge-dot"></span>Accepting Clients</span>
                <span v-if="user.verified" class="badge badge-blue"><AegisIcon name="check" :size="10" />Aegis Verified</span>
                <span class="badge badge-blue"><AegisIcon name="shield" :size="10" />BAA-Ready</span>
              </div>
            </div>
          </div>
          <div class="page-hero-actions">
            <template v-if="isOwner">
              <a :href="route('bp.profile.index')" class="btn-hero-solid is-on-light"><AegisIcon name="pencil" :size="14" /> Edit</a>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only" @click="copyShareLink" data-tooltip="Copy link" aria-label="Copy link"><AegisIcon name="link" :size="14" /></button>
            </template>
            <template v-else-if="isLoggedIn">
              <button type="button" class="btn-hero-solid is-on-light" @click="openHireModal"><AegisIcon name="check" :size="14" /> Hire</button>
              <button type="button" class="btn-hero-ghost is-on-light" @click="openRequestQuoteModal"><AegisIcon name="clipboard" :size="14" /> Quote</button>
              <a :href="route('messages.index') + '?to=' + user.id" class="btn-hero-ghost is-on-light is-icon-only" data-tooltip="Message" aria-label="Message"><AegisIcon name="message" :size="14" /></a>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only" @click="toast.success('Saved to favorites')" data-tooltip="Save" aria-label="Save"><AegisIcon name="star" :size="14" /></button>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only" @click="copyShareLink" data-tooltip="Share" aria-label="Share"><AegisIcon name="link" :size="14" /></button>
            </template>
            <template v-else>
              <a :href="route('login')" class="btn-hero-solid is-on-light"><AegisIcon name="briefcase" :size="14" /> Sign In</a>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only" @click="copyShareLink" data-tooltip="Share" aria-label="Share"><AegisIcon name="link" :size="14" /></button>
            </template>
          </div>
        </div>
        <div class="hero-meta">
          <span v-if="user.location" class="hero-meta-item"><AegisIcon name="map-pin" :size="12" />{{ user.location }}</span>
          <span class="hero-meta-item is-rating"><AegisIcon name="star" :size="12" class="aegis-icon-filled" />{{ pm.rating ?? '4.6' }} <span class="rating-count">({{ pm.review_count ?? 87 }})</span></span>
          <span class="hero-meta-item"><AegisIcon name="briefcase" :size="12" />{{ pmStats.jobs_completed ?? 42 }} projects</span>
          <span class="hero-meta-item"><AegisIcon name="clock" :size="12" />~{{ pmStats.response_time ?? '24h' }}</span>
          <span v-if="user.bp_team_size" class="hero-meta-item"><AegisIcon name="user" :size="12" />Team of {{ user.bp_team_size }}</span>
          <span class="hero-meta-item"><AegisIcon name="calendar" :size="12" />Mon-Fri 9-5 EST</span>
        </div>
      </div>

      <!-- ═══ STAT CHIPS ═══ -->
      <div class="stat-chips-row">
        <AegisStatChip icon="briefcase" :value="pmStats.jobs_completed ?? 42" label="Projects Done" />
        <AegisStatChip icon="star" :value="pm.rating ?? '4.6'" label="Client Rating" />
        <AegisStatChip icon="clock" :value="'~' + (pmStats.response_time ?? '24h')" label="Response Time" />
        <AegisStatChip icon="check" value="96%" label="On-Time Delivery" />
        <AegisStatChip icon="dollar" :value="hourlyRate + '/hr'" label="Hourly Rate" />
      </div>

      <!-- ENGAGEMENT ACTIONS PANEL (non-owner) -->
      <div v-if="!isOwner" class="pp-svc-section">
        <div class="pp-svc-header">
          <div class="pp-svc-header-title"><AegisIcon name="briefcase" :size="16" class="aegis-icon-gold-dark" />Engagement Actions</div>
          <span class="pp-svc-header-badge">Available Now</span>
        </div>
        <div class="pp-svc-body">
          <div class="pp-svc-intro">Take action with this partner — start a new engagement, draft a contract, request a quote, or schedule a consultation.</div>
          <div class="pp-svc-grid">
            <div v-for="action in engagementActions" :key="action.name" class="pp-svc-card">
              <div class="pp-svc-card-top"><div class="pp-svc-card-name">{{ action.name }}</div></div>
              <div class="pp-svc-card-desc">{{ action.desc }}</div>
              <div class="pp-svc-card-footer">
                <span :class="['pp-svc-card-avail', action.avail]"><AegisIcon name="circle-dot" :size="9" class="aegis-icon-filled" />{{ action.availLabel }}</span>
                <button v-if="isLoggedIn" :class="['btn', action.btnClass, 'btn-sm']" @click="action.handler">{{ action.btnLabel }}</button>
                <a v-else :href="route('login')" :class="['btn', action.btnClass, 'btn-sm']">Sign in</a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TWO-COLUMN GRID -->
      <div class="pp-grid">
        <!-- LEFT -->
        <div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="briefcase" :size="13" class="aegis-icon-gold-dark" /> About {{ businessName }}</div>
            <p style="font-size:13px;color:var(--text-2);line-height:1.7;margin:0 0 12px">{{ user.bio ?? 'A specialized service provider for healthcare practices. Years of experience supporting solo practitioners, group practices, and multi-location organizations across compliance, billing, and administrative operations.' }}</p>
            <p style="font-size:13px;color:var(--text-2);line-height:1.7;margin:0 0 12px">Direct experience with healthcare-specific tooling, EHR integrations, payer workflows, and the operational realities of running a clinical practice. Available for short-term projects, fixed-scope engagements, and hourly consultation.</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
              <a href="#" class="pp-ext-link"><AegisIcon name="briefcase" :size="11" />LinkedIn</a>
              <a href="#" class="pp-ext-link"><AegisIcon name="globe" :size="11" />Company Website</a>
              <a href="#" class="pp-ext-link"><AegisIcon name="clipboard" :size="11" />Portfolio &amp; Case Studies</a>
            </div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="clipboard" :size="13" class="aegis-icon-gold-dark" /> Services Offered</div>
            <div class="pp-section-eyebrow">Service Categories</div>
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:14px">
              <template v-if="!categories.length">
                <span v-for="s in ['Accounting','Tax Filing','Bookkeeping']" :key="s" class="tag-chip">{{ s }}</span>
              </template>
              <span v-else v-for="cat in categories" :key="cat" class="tag-chip">{{ titleCase(cat) }}</span>
            </div>
            <div class="pp-section-eyebrow">Industries Served</div>
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:14px">
              <span v-for="s in ['Solo Practitioners','Group Practices','Behavioral Health','Telehealth','Multi-State Operations']" :key="s" class="pn-tag">{{ s }}</span>
            </div>
            <div class="pp-section-eyebrow">Compliance &amp; Tooling</div>
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:14px">
              <span v-for="s in ['HIPAA','QuickBooks','Xero','Stripe','Practice Fusion','Epic / Cerner']" :key="s" class="tag-chip">{{ s }}</span>
            </div>
            <div class="pp-section-eyebrow">Specializations</div>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <span v-for="s in ['Insurance Reimbursement','Practice Setup','Compliance Audits','Multi-State Filing']" :key="s" class="tag-chip">{{ s }}</span>
            </div>
          </div>

          <div v-if="isOwner" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="pencil" :size="13" class="aegis-icon-gold-dark" /> Profile Visibility</div>
            <p style="font-size:12px;color:var(--text-3);line-height:1.6;margin:0 0 10px">This is your public listing. Practitioners use this page to evaluate you before engagement. Your active client list, contracts, shared documents, and earnings are <strong style="color:var(--green-dark)">never shown</strong> publicly.</p>
            <a :href="route('bp.profile.index')" class="btn btn-outline btn-sm">Edit profile</a>
          </div>

          <div v-if="isLoggedIn && !isOwner" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="bar-chart" :size="13" class="aegis-icon-gold-dark" /> Engagement Track Record</div>
            <div class="pp-metric-grid">
              <div class="pp-metric-tile green"><div class="pp-metric-tile-val">{{ pmStats.jobs_completed ?? 42 }}</div><div class="pp-metric-tile-lbl">Total Projects</div></div>
              <div class="pp-metric-tile blue"><div class="pp-metric-tile-val">96%</div><div class="pp-metric-tile-lbl">On-Time Delivery</div></div>
              <div class="pp-metric-tile gold"><div class="pp-metric-tile-val">2</div><div class="pp-metric-tile-lbl">Active Engagements</div></div>
            </div>
            <div class="pp-info-row"><span class="pp-info-label">Repeat Client Rate</span><span class="pp-info-val" style="color:var(--green-dark)">68% return for additional work</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Avg. Project Length</span><span class="pp-info-val">6-8 weeks</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Largest Engagement</span><span class="pp-info-val">$24,500 (multi-phase project)</span></div>
          </div>
          <div v-else-if="!isLoggedIn" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="lock" :size="13" class="aegis-icon-gold-dark" /> Engagement Track Record</div>
            <p style="font-size:12px;color:var(--text-3);line-height:1.6;margin:0 0 10px">On-time delivery rate, repeat-client stats, and engagement details are visible to signed-in network members.</p>
            <a :href="route('login')" class="btn btn-outline btn-sm">Sign in to view</a>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="star" :size="13" class="aegis-icon-filled aegis-icon-gold-dark" /> Client Reviews</div>
            <div v-for="rev in clientReviews" :key="rev.name" style="margin-bottom:12px;padding:14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm)">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                <div style="font-size:13px;font-weight:700;color:var(--text)">{{ rev.name }}</div>
                <div style="display:flex;gap:1px">
                  <AegisIcon v-for="i in rev.stars" :key="'f'+i" name="star" :size="13" class="aegis-icon-filled aegis-icon-gold-dark" />
                  <AegisIcon v-for="i in (5-rev.stars)" :key="'e'+i" name="star" :size="13" class="aegis-icon-muted" />
                </div>
              </div>
              <div style="font-size:12px;color:var(--text-2);line-height:1.6;font-style:italic">{{ rev.quote }}</div>
              <div style="font-size:11px;color:var(--text-3);font-weight:600;margin-top:6px">{{ rev.meta }}</div>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:14px;font-size:12px;color:var(--text-3)">
              Client Rating: <strong style="color:var(--gold-dark)">{{ pm.rating ?? '4.6' }}/5.0</strong> from {{ pm.review_count ?? 87 }} reviews
              <button v-if="isLoggedIn && !isOwner" class="btn btn-outline btn-sm" @click="openLeaveReviewModal"><AegisIcon name="plus" :size="13" /> Leave Review</button>
            </div>
          </div>

        </div>

        <!-- RIGHT -->
        <div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="map-pin" :size="13" class="aegis-icon-gold-dark" /> Contact &amp; Office</div>
            <div class="pp-info-row"><span class="pp-info-label">Business</span><span class="pp-info-val">{{ businessName }}</span></div>
            <div v-if="user.location" class="pp-info-row"><span class="pp-info-label">Location</span><span class="pp-info-val">{{ user.location }}</span></div>
            <div v-if="isLoggedIn && user.email" class="pp-info-row"><span class="pp-info-label">Email</span><span class="pp-info-val"><a :href="'mailto:'+user.email">{{ user.email }}</a></span></div>
            <div v-if="isLoggedIn && user.phone" class="pp-info-row"><span class="pp-info-label">Phone</span><span class="pp-info-val">{{ user.phone }}</span></div>
            <div v-if="!isLoggedIn" class="pp-info-row"><span class="pp-info-label">Contact Details</span><span class="pp-info-val" style="font-weight:600;color:var(--text-3)"><a :href="route('login')" class="pp-ext-link" style="padding:3px 8px">Sign in to view</a></span></div>
            <div class="pp-info-row"><span class="pp-info-label">Service Hours</span><span class="pp-info-val">{{ pmContact.service_hours ?? 'Mon-Fri 9 AM - 5 PM EST' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Response SLA</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmContact.response_sla ?? ('Within ' + (pmStats.response_time ?? '24h')) }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Preferred Channel</span><span class="pp-info-val">{{ pmContact.best_channel ?? 'Aegis Message' }}</span></div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="shield" :size="13" class="aegis-icon-gold-dark" /> Credentials &amp; Qualifications</div>
            <div class="pp-info-row"><span class="pp-info-label">Type</span><span class="pp-info-val">{{ user.bp_type === 'agency' ? 'Agency' : 'Freelancer' }}</span></div>
            <div v-if="user.bp_team_size" class="pp-info-row"><span class="pp-info-label">Team Size</span><span class="pp-info-val">{{ user.bp_team_size }} people</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Years in Business</span><span class="pp-info-val">{{ pmCreds.years_in_business ?? '12+ years' }}</span></div>
            <template v-if="user.bp_type !== 'agency'">
              <div class="pp-info-row"><span class="pp-info-label">License</span><span class="pp-info-val">{{ pmCreds.license ?? 'CPA - NY State' }}</span></div>
              <div class="pp-info-row"><span class="pp-info-label">License #</span><span class="pp-info-val" style="font-family:var(--font-mono,monospace);font-size:11px">{{ pmCreds.license_number ?? 'NY-CPA-094821' }}</span></div>
              <div class="pp-info-row"><span class="pp-info-label">Education</span><span class="pp-info-val">{{ pmCreds.education ?? 'NYU Stern (BBA, 2001)' }}</span></div>
            </template>
            <div class="pp-info-row"><span class="pp-info-label">HIPAA / BAA</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmCreds.hipaa_baa ?? 'Ready to sign' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Aegis Verified</span><span class="pp-info-val" :style="user.verified ? 'color:var(--green-dark)' : 'color:var(--text-4)'">{{ user.verified ? 'Verified' : 'Pending' }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Insurance</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmCreds.insurance ?? '$2M E&O' }}</span></div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="clipboard" :size="13" class="aegis-icon-gold-dark" /> How It Works</div>
            <div style="font-size:13px;color:var(--text-2);line-height:1.7">
              <div v-for="(step, i) in howItWorks" :key="i" class="pp-step-row" :style="i === howItWorks.length-1 ? 'margin-bottom:0' : ''">
                <div class="pp-step-num">{{ i + 1 }}</div>
                <div><strong>{{ step.title }}</strong> - {{ step.desc }}</div>
              </div>
            </div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="globe" :size="13" class="aegis-icon-gold-dark" /> Languages</div>
            <div style="display:flex;gap:5px;flex-wrap:wrap"><span class="tag-chip">English</span><span class="tag-chip">Spanish</span></div>
          </div>

          <div v-if="isLoggedIn && !isOwner" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="link" :size="13" class="aegis-icon-gold-dark" /> Connection Info</div>
            <div class="pp-info-row"><span class="pp-info-label">Connected Since</span><span class="pp-info-val">{{ pmConn.connected_since ?? 'January 2023' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Active Contract</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmConn.active_contract ?? 'Yes - Active Project' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">NDA Signed</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmConn.nda_signed ?? 'Yes' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">BAA Signed</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmConn.baa_signed ?? 'Yes (HIPAA)' }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Profile Completeness</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmConn.profile_completeness ?? '95%' }} Complete</span></div>
            <div class="pp-action-row">
              <button class="btn-icon btn-icon-danger" data-tooltip="Remove partner" @click="confirmRemovePartner"><AegisIcon name="trash" :size="14" /></button>
            </div>
          </div>
          <div v-else-if="!isLoggedIn" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="lock" :size="13" class="aegis-icon-gold-dark" /> Connection &amp; Contract Info</div>
            <p style="font-size:12px;color:var(--text-3);line-height:1.6;margin:0 0 10px">See active contracts, NDA/BAA status, and connection history with this partner.</p>
            <a :href="route('login')" class="btn btn-outline btn-sm">Sign in to view</a>
          </div>

          <div v-if="isOwner" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="pencil" :size="13" class="aegis-icon-gold-dark" /> Your Private Notes</div>
            <div class="pp-note-box">Reach-outs after Maat-certification renewal cycles convert best. Keep response SLA visible.<div class="pp-note-meta">Added Feb 2, 2025</div></div>
            <div class="pp-note-box">Group practices ask about multi-state filings on the discovery call. Pre-prep the NJ/NY/CT licensing checklist.<div class="pp-note-meta">Added Jan 14, 2025</div></div>
            <textarea class="pp-note-edit" placeholder="Add a private note about your practice or engagement workflow..."></textarea>
            <button class="btn btn-primary btn-sm" style="margin-top:8px" @click="toast.success('Note saved')">Save Note</button>
          </div>

        </div>
      </div>

    </div>

    <!-- MODALS (logged-in non-owner only) -->
    <template v-if="isLoggedIn && !isOwner">

      <!-- Hire Modal -->
      <AegisModal v-model="showHireModal" title="Hire / Engage Partner" subtitle="Start a new engagement or activate this partner for a specific project" size="lg">
        <div class="modal-steps">
          <div class="modal-step done"><div class="modal-step-num"><AegisIcon name="check" :size="12" /></div>Partner Selected</div>
          <div class="modal-step-divider"></div>
          <div class="modal-step active"><div class="modal-step-num">2</div>Engagement Type</div>
          <div class="modal-step-divider"></div>
          <div class="modal-step"><div class="modal-step-num">3</div>Scope &amp; Terms</div>
          <div class="modal-step-divider"></div>
          <div class="modal-step"><div class="modal-step-num">4</div>Confirm</div>
        </div>
        <div class="bpe-summary">
          <div class="bpe-summary-avatar">{{ avatarInitials }}</div>
          <div style="min-width:0;flex:1">
            <div class="bpe-summary-name">{{ businessName }}</div>
            <div class="bpe-summary-role">{{ primaryLabel }}</div>
            <div class="bpe-summary-meta">
              <span v-if="user.verified" class="badge badge-green"><AegisIcon name="check" :size="10" /> Verified</span>
              <span style="color:var(--gold-dark);font-weight:700;display:inline-flex;align-items:center;gap:4px"><AegisIcon name="star" :size="11" class="aegis-icon-filled aegis-icon-gold-dark" /> {{ pm.rating ?? '4.6' }}</span>
            </div>
          </div>
        </div>
        <div class="bpe-label">Select Engagement Type</div>
        <div class="bpe-option-row">
          <div v-for="opt in engagementTypes" :key="opt.label" :class="['bpe-option', hireForm.type === opt.label ? 'selected' : '']" @click="hireForm.type = opt.label">
            <span class="bpe-option-icon"><AegisIcon :name="opt.icon" :size="16" /></span>
            <div><div>{{ opt.label }}</div><div class="bpe-option-sub">{{ opt.sub }}</div></div>
          </div>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group"><label class="form-label">Start Date <span class="req">*</span></label><input type="date" class="form-input" v-model="hireForm.startDate"></div>
          <div class="form-group"><label class="form-label">Duration</label><input type="text" class="form-input" placeholder="e.g. 3 months / Ongoing" v-model="hireForm.duration"></div>
          <div class="form-group"><label class="form-label">Budget / Rate</label><input type="text" class="form-input" placeholder="e.g. $95/hr or $2,000 fixed" v-model="hireForm.budget"></div>
          <div class="form-group">
            <label class="form-label">Payment Terms</label>
            <select class="form-select" v-model="hireForm.paymentTerms">
              <option>Net 30</option><option>Net 15</option><option>Upon Completion</option><option>50% Upfront / 50% On Delivery</option><option>Hourly Time-Tracking</option>
            </select>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Scope of Work / Notes</label><textarea class="form-textarea" rows="3" placeholder="Describe what you need from this partner..." v-model="hireForm.notes"></textarea></div>
        <div class="pp-tip-box" style="display:flex;gap:10px;align-items:flex-start">
          <span style="flex-shrink:0;margin-top:1px;display:inline-flex;align-items:center;line-height:0"><AegisIcon name="check" :size="16" class="aegis-icon-gold-dark" /></span>
          <div class="pp-tip-body"><strong style="color:var(--gold-dark)">NDA &amp; BAA on file.</strong> This partner has signed all required agreements. An engagement confirmation will be sent via Aegis secure messaging.</div>
        </div>
        <template #footer>
          <button class="btn btn-outline" @click="showHireModal = false">Cancel</button>
          <button class="btn btn-outline" @click="openProposeContractModal"><AegisIcon name="file-text" :size="13" /> Add Formal Contract</button>
          <button class="btn btn-primary" @click="submitHire"><span class="btn-ico"><AegisIcon name="check" :size="13" /></span>Confirm Engagement</button>
        </template>
      </AegisModal>

      <!-- Propose Contract Modal -->
      <AegisModal v-model="showContractModal" title="Propose a Contract" subtitle="Draft a custom service contract with scope, terms, and payment schedule" size="lg">
        <div class="form-group"><label class="form-label">Partner</label><input type="text" class="form-input" :value="businessName" readonly></div>
        <div class="form-row form-row-2">
          <div class="form-group"><label class="form-label">Contract Title <span class="req">*</span></label><input type="text" class="form-input" placeholder="e.g. Annual Tax & Accounting Services 2025" v-model="contractForm.title"></div>
          <div class="form-group">
            <label class="form-label">Contract Type</label>
            <select class="form-select" v-model="contractForm.type">
              <option>Service Agreement</option><option>Statement of Work (SOW)</option><option>Hourly Services Agreement</option><option>Master Services Agreement (MSA)</option><option>Non-Disclosure Agreement (NDA)</option><option>Business Associate Agreement (BAA)</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Start Date <span class="req">*</span></label><input type="date" class="form-input" v-model="contractForm.startDate"></div>
          <div class="form-group"><label class="form-label">End Date / Duration</label><input type="text" class="form-input" placeholder="e.g. Dec 31, 2025 / 12 months" v-model="contractForm.endDate"></div>
          <div class="form-group"><label class="form-label">Contract Value</label><input type="text" class="form-input" placeholder="e.g. $4,000 fixed or $95/hr" v-model="contractForm.value"></div>
          <div class="form-group">
            <label class="form-label">Payment Schedule</label>
            <select class="form-select" v-model="contractForm.paymentSchedule">
              <option>Milestone-based</option><option>Hourly Time-Tracking</option><option>50% Upfront / 50% On Delivery</option><option>Upon Completion</option><option>Net 30</option>
            </select>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Scope of Services</label><textarea class="form-textarea" rows="3" placeholder="Detail the services, deliverables, and responsibilities..." v-model="contractForm.scope"></textarea></div>
        <div class="form-group"><label class="form-label">Special Terms <span style="color:var(--text-4);font-weight:500">(optional)</span></label><textarea class="form-textarea" rows="2" placeholder="Any special clauses, exclusivity, IP ownership, termination terms..." v-model="contractForm.specialTerms"></textarea></div>
        <div class="bpe-label">Include Standard Clauses</div>
        <div class="bpe-clause-grid">
          <label v-for="clause in standardClauses" :key="clause.label"><input type="checkbox" :checked="clause.checked"> {{ clause.label }}</label>
        </div>
        <div style="padding:12px 14px;background:var(--blue-light);border:1px solid var(--fade-blue);border-radius:var(--radius-sm);font-size:12px;color:var(--text-2);display:flex;gap:10px;align-items:flex-start">
          <span style="flex-shrink:0;margin-top:1px;display:inline-flex;align-items:center;line-height:0;color:var(--blue-dark)"><AegisIcon name="file-text" :size="16" /></span>
          <div><strong style="color:var(--blue-dark)">Aegis Contract Engine</strong> will generate a draft PDF. Both parties can review, negotiate, and e-sign within the platform.</div>
        </div>
        <template #footer>
          <button class="btn btn-outline" @click="showContractModal = false">Cancel</button>
          <button class="btn btn-outline" @click="toast.success('Draft saved')"><AegisIcon name="save" :size="13" /> Save Draft</button>
          <button class="btn btn-primary" @click="submitContract"><span class="btn-ico"><AegisIcon name="send" :size="13" /></span>Send Proposal</button>
        </template>
      </AegisModal>

      <!-- Request Quote Modal -->
      <AegisModal v-model="showQuoteModal" title="Request a Quote" subtitle="Describe your needs and request a formal quote from this partner" size="md">
        <div class="form-group"><label class="form-label">From Partner</label><input type="text" class="form-input" :value="businessName" readonly></div>
        <div class="form-group">
          <label class="form-label">Service Needed <span class="req">*</span></label>
          <select class="form-select" v-model="quoteForm.service">
            <option>Tax Preparation (one-time)</option><option>Bookkeeping Setup &amp; Cleanup</option><option>Payroll Setup</option><option>Practice Valuation</option><option>Financial Audit</option><option>Hourly Consultation</option><option>Other / Custom</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Practice Size</label>
          <select class="form-select" v-model="quoteForm.size">
            <option>Solo Practitioner</option><option>2-5 Providers</option><option>6-15 Providers</option><option>16+ Providers / Group Practice</option>
          </select>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group"><label class="form-label">Estimated Budget</label><input type="text" class="form-input" placeholder="e.g. $1,500-$3,000 fixed" v-model="quoteForm.budget"></div>
          <div class="form-group"><label class="form-label">Timeline</label><input type="text" class="form-input" placeholder="e.g. Start by March 2025" v-model="quoteForm.timeline"></div>
        </div>
        <div class="form-group"><label class="form-label">Additional Details <span style="color:var(--text-4);font-weight:500">(optional)</span></label><textarea class="form-textarea" rows="3" placeholder="Describe specific needs, pain points, or context..." v-model="quoteForm.notes"></textarea></div>
        <label style="display:flex;align-items:center;gap:10px;font-size:13px;line-height:1.5;cursor:pointer;margin-top:4px"><input type="checkbox" v-model="quoteForm.urgent"> Mark as urgent - request response within 48 hours</label>
        <template #footer>
          <button class="btn btn-outline" @click="showQuoteModal = false">Cancel</button>
          <button class="btn btn-primary" @click="submitQuote"><span class="btn-ico"><AegisIcon name="send" :size="13" /></span>Send Request</button>
        </template>
      </AegisModal>

      <!-- Schedule Consultation Modal -->
      <AegisModal v-model="showScheduleModal" title="Schedule a Consultation" subtitle="Book a discovery call, strategy session, or consultation meeting" size="md">
        <div class="form-group"><label class="form-label">Partner</label><input type="text" class="form-input" :value="businessName" readonly></div>
        <div class="bpe-label">Meeting Type</div>
        <div class="bpe-option-row" style="grid-template-columns:repeat(4,1fr)">
          <div v-for="mt in meetingTypes" :key="mt.label" :class="['bpe-option', scheduleForm.type === mt.label ? 'selected' : '']" @click="scheduleForm.type = mt.label" style="padding:10px 12px">
            <span class="bpe-option-icon"><AegisIcon :name="mt.icon" :size="16" /></span>
            <div>{{ mt.label }}</div>
          </div>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group"><label class="form-label">Preferred Date <span class="req">*</span></label><input type="date" class="form-input" v-model="scheduleForm.date"></div>
          <div class="form-group">
            <label class="form-label">Preferred Time</label>
            <select class="form-select" v-model="scheduleForm.time">
              <option>9:00 AM</option><option>10:00 AM</option><option>11:00 AM</option><option>1:00 PM</option><option>2:00 PM</option><option>3:00 PM</option><option>4:00 PM</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Duration</label>
            <select class="form-select" v-model="scheduleForm.duration">
              <option>15 minutes</option><option>30 minutes</option><option>45 minutes</option><option>1 hour</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Your Timezone</label>
            <select class="form-select" v-model="scheduleForm.tz">
              <option>EST (New York)</option><option>CST</option><option>MST</option><option>PST</option>
            </select>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Agenda / Topics to Discuss</label><textarea class="form-textarea" rows="2" placeholder="What do you want to cover?" v-model="scheduleForm.agenda"></textarea></div>
        <template #footer>
          <button class="btn btn-outline" @click="showScheduleModal = false">Cancel</button>
          <button class="btn btn-primary" @click="submitSchedule"><span class="btn-ico"><AegisIcon name="calendar" :size="13" /></span>Send Request</button>
        </template>
      </AegisModal>

      <!-- Leave Review Modal -->
      <AegisModal v-model="showReviewModal" title="Leave a Review" subtitle="Share your experience to help other clinicians evaluate fit" size="md">
        <div class="form-group"><label class="form-label">Business Partner</label><input type="text" class="form-input" :value="businessName" readonly></div>
        <div class="form-group">
          <label class="form-label">Your Rating <span class="req">*</span></label>
          <div class="rating-stars" role="radiogroup" aria-label="Star rating">
            <button v-for="i in 5" :key="i" type="button" :class="['rating-star', reviewForm.rating >= i ? 'is-on' : '']" @click="reviewForm.rating = i" :aria-label="i + (i === 1 ? ' star' : ' stars')"><AegisIcon name="star" :size="22" /></button>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Headline <span style="color:var(--text-4);font-weight:500">(optional)</span></label><input type="text" class="form-input" placeholder="e.g. Saved us $14k in the first year - great communication" maxlength="80" v-model="reviewForm.headline"></div>
        <div class="form-group">
          <label class="form-label">Your Review <span class="req">*</span></label>
          <textarea class="form-textarea" rows="4" placeholder="What was the engagement like? Strengths, areas to improve, who is this partner best suited for?" maxlength="600" v-model="reviewForm.body"></textarea>
          <div class="form-hint">Visible to other clinicians on this profile - 600 chars max</div>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">Engagement Type</label>
            <select class="form-select" v-model="reviewForm.engType">
              <option>Fixed-Scope Project</option><option>Hourly / Time-Based</option><option>Monthly Retainer</option><option>One-Time Consultation</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Duration</label>
            <select class="form-select" v-model="reviewForm.duration">
              <option>Less than 1 month</option><option>1-3 months</option><option>3-12 months</option><option>1+ year</option>
            </select>
          </div>
        </div>
        <template #footer>
          <button class="btn btn-outline" @click="showReviewModal = false">Cancel</button>
          <button class="btn btn-primary" @click="submitReview"><span class="btn-ico"><AegisIcon name="send" :size="13" /></span>Submit Review</button>
        </template>
      </AegisModal>

    </template>
  </PublicLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import AegisModal from '@/components/ui/AegisModal.vue'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const props = defineProps({
  user:       { type: Object,  required: true }
})

const page = usePage()
const toast = useToast()
const { confirmAction } = useConfirm()

// Derive auth state from Inertia shared props — zero dependency on controller passing them
const authUser   = computed(() => page.props.auth?.user ?? null)
const isLoggedIn = computed(() => !!authUser.value)
const isOwner    = computed(() => isLoggedIn.value && authUser.value?.id === props.user?.id)

const pm        = computed(() => props.user.profile_meta ?? {})
const pmStats   = computed(() => pm.value.stats ?? {})
const pmCreds   = computed(() => pm.value.credentials ?? {})
const pmContact = computed(() => pm.value.contact_meta ?? {})
const pmConn    = computed(() => pm.value.connection ?? {})

const businessName   = computed(() => props.user.bp_business_name ?? props.user.display_name ?? '')
const avatarInitials = computed(() => props.user.avatar_initials ?? businessName.value.slice(0, 2).toUpperCase() ?? '??')
const categories     = computed(() => props.user.bp_categories ?? [])
const primaryLabel   = computed(() => titleCase((categories.value[0] ?? 'Services').replace(/[_-]/g, ' ')))
const hourlyRate     = computed(() => props.user.bp_hourly_rate_cents ? '$' + Math.round(props.user.bp_hourly_rate_cents / 100) : '$95')

function titleCase(str) { return str.replace(/\b\w/g, c => c.toUpperCase()) }

const clientReviews = [
  { name: 'Dr. Sarah Johnson, LMFT', stars: 5, quote: 'Reliable and detail-oriented. Tax filings always come in early. Knows healthcare-specific deductions inside-out.', meta: 'Solo Practice - 2 years engagement' },
  { name: 'Lotus Group Practice',    stars: 4, quote: 'Great communicator. Helped us through a complex multi-state restructuring. A few minor scheduling hiccups but always made up for it.', meta: 'Group Practice (8 clinicians) - 1 year engagement' },
  { name: 'Dr. Marcus Chen, MD',     stars: 5, quote: 'Hands down the most knowledgeable healthcare-focused partner I\'ve worked with. Saved my practice $14k in the first year through better insurance contract negotiation.', meta: 'Solo Practice - 3 years engagement' },
]

const howItWorks = [
  { title: 'Inquiry',            desc: 'Describe your need (free, no obligation).' },
  { title: 'Discovery Call',     desc: '30-min free call to scope the work.' },
  { title: 'Proposal',           desc: 'Custom quote within 48 hours.' },
  { title: 'Contract via Aegis', desc: 'Sign + start work, all tracked in-platform.' },
]

const engagementActions = computed(() => [
  { name: 'Hire / Engage',        desc: 'Start a new engagement or activate this partner for a specific project or hourly consultation.', avail: 'open',    availLabel: 'Available Now',   btnClass: 'btn-primary', btnLabel: 'Hire Now', handler: openHireModal },
  { name: 'Propose Contract',     desc: 'Draft and send a custom service contract - set scope, timeline, payment terms, and deliverables.', avail: 'open', availLabel: 'Custom Terms',    btnClass: 'btn-outline', btnLabel: 'Draft',    handler: openProposeContractModal },
  { name: 'Request a Quote',      desc: 'Describe your needs and request a formal quote or pricing proposal from this partner.',           avail: 'open',    availLabel: '24h Turnaround', btnClass: 'btn-outline', btnLabel: 'Request',  handler: openRequestQuoteModal },
  { name: 'Schedule Consultation',desc: 'Book a discovery call, strategy session, or consultation meeting with this partner.',             avail: 'limited', availLabel: 'Limited Slots',  btnClass: 'btn-outline', btnLabel: 'Book',     handler: openScheduleModal },
])

const engagementTypes = [
  { label: 'Fixed-Scope Project', icon: 'clipboard', sub: 'One-time deliverable based' },
  { label: 'Hourly / Time-Based', icon: 'clock',     sub: 'Pay per hour worked' },
  { label: 'Consultation Only',   icon: 'credit-card', sub: 'Single advisory session' },
]
const meetingTypes = [
  { label: 'Video Call', icon: 'user' },
  { label: 'Phone Call', icon: 'phone' },
  { label: 'In-Person',  icon: 'map-pin' },
  { label: 'Aegis Chat', icon: 'message' },
]
const standardClauses = [
  { label: 'HIPAA / BAA Compliance', checked: true },
  { label: 'Confidentiality / NDA',  checked: true },
  { label: 'Termination Clause (30-day notice)', checked: true },
  { label: 'Exclusivity Clause',     checked: false },
  { label: 'Liability Limitation',   checked: true },
]

const showHireModal     = ref(false)
const showContractModal = ref(false)
const showQuoteModal    = ref(false)
const showScheduleModal = ref(false)
const showReviewModal   = ref(false)

const hireForm     = ref({ type: 'Fixed-Scope Project', startDate: '', duration: '', budget: '', paymentTerms: 'Net 30', notes: '' })
const contractForm = ref({ title: '', type: 'Service Agreement', startDate: '', endDate: '', value: '', paymentSchedule: 'Milestone-based', scope: '', specialTerms: '' })
const quoteForm    = ref({ service: 'Tax Preparation (one-time)', size: 'Solo Practitioner', budget: '', timeline: '', notes: '', urgent: false })
const scheduleForm = ref({ type: 'Video Call', date: '', time: '10:00 AM', duration: '30 minutes', tz: 'EST (New York)', agenda: '' })
const reviewForm   = ref({ rating: 0, headline: '', body: '', engType: 'Fixed-Scope Project', duration: '1-3 months' })

function openHireModal()            { showHireModal.value = true }
function openProposeContractModal() { showHireModal.value = false; showContractModal.value = true }
function openRequestQuoteModal()    { showQuoteModal.value = true }
function openScheduleModal()        { showScheduleModal.value = true }
function openLeaveReviewModal()     { reviewForm.value = { rating: 0, headline: '', body: '', engType: 'Fixed-Scope Project', duration: '1-3 months' }; showReviewModal.value = true }

function submitHire()     { showHireModal.value = false; toast.success('Engagement started - partner notified via Aegis') }
function submitContract() { showContractModal.value = false; toast.success('Contract proposal sent') }
function submitQuote()    { showQuoteModal.value = false; toast.success('Quote request sent') }
function submitSchedule() { showScheduleModal.value = false; toast.success('Consultation request sent') }
function submitReview()   { showReviewModal.value = false; toast.success('Review submitted') }

function copyShareLink() {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(window.location.href)
      .then(() => toast.success('Profile link copied'))
      .catch(() => toast.error('Could not copy link'))
  } else {
    toast.success('Profile link copied')
  }
}
function confirmRemovePartner() {
  confirmAction('Remove this business partner? Active contracts will remain accessible in your history.', () => toast.success('Partner removed'), { title: 'Remove Partner', btnLabel: 'Remove', type: 'danger' })
}
</script>

<style scoped>
.public-profile-wrap { max-width: 960px; margin: 0 auto; padding: var(--space-6) var(--space-4); }
.bpe-summary { display: flex; gap: 12px; align-items: center; padding: 14px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 18px; }
.bpe-summary-avatar { width: 48px; height: 48px; border-radius: var(--radius-sm); background: var(--gold-dark); color: var(--text-inverted); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; flex-shrink: 0; }
.bpe-summary-name { font-size: 14px; font-weight: 700; color: var(--text); }
.bpe-summary-role { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.bpe-summary-meta { display: flex; gap: 10px; margin-top: 5px; font-size: 11px; }
.bpe-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-4); margin-bottom: 8px; }
.bpe-option-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 14px; }
.bpe-option { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); background: var(--surface); font-size: 13px; font-weight: 600; color: var(--text); cursor: pointer; transition: box-shadow 180ms; }
.bpe-option:hover { box-shadow: var(--shadow-sm); }
.bpe-option.selected { background: var(--badge-bg-gold); border-color: transparent; color: var(--gold-dark); font-weight: 700; }
.bpe-option-icon { flex-shrink: 0; display: inline-flex; align-items: center; line-height: 0; color: var(--text-3); }
.bpe-option.selected .bpe-option-icon { color: var(--gold-dark); }
.bpe-option-sub { font-size: 11px; font-weight: 600; color: var(--text-4); margin-top: 2px; }
.bpe-option.selected .bpe-option-sub { color: var(--gold-dark); }
.bpe-clause-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
.bpe-clause-grid label { display: flex; align-items: center; gap: 10px; font-size: 13px; line-height: 1.5; cursor: pointer; }
.rating-stars { display: inline-flex; gap: 4px; }
.rating-star { background: transparent; border: none; padding: 4px; color: var(--text-4); cursor: pointer; line-height: 0; transition: color 0.15s ease, transform 0.15s ease; }
.rating-star:hover { transform: scale(1.08); }
.rating-star.is-on { color: var(--gold-dark); }
.hero-banner.is-quiet .page-hero-left.has-icon { gap: 20px; }
.hero-banner.is-quiet .page-hero-actions { gap: 8px; }
.hero-banner.is-quiet .hero-badges { flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none; gap: 6px; margin-top: 10px; }
.hero-banner.is-quiet .hero-badges::-webkit-scrollbar { display: none; }
.hero-banner.is-quiet .hero-badges .badge { font-size: 10px; padding: 3px 8px; letter-spacing: 0.1px; text-transform: none; font-weight: 600; border-radius: var(--radius-full); }
.hero-banner.is-quiet .badge-blue { background: var(--blue-light); border: 1px solid var(--fade-blue); color: var(--blue-dark); }
.hero-banner.is-quiet > .hero-meta { flex-wrap: nowrap; gap: 10px 14px; }
.hero-banner.is-quiet .hero-meta-item { white-space: nowrap; font-size: 11.5px; }
.btn-hero-ghost.is-icon-only,
.btn-hero-solid.is-icon-only { padding: 8px; width: 34px; height: 34px; justify-content: center; gap: 0; flex-shrink: 0; }
@media (max-width: 720px) {
  .bpe-option-row { grid-template-columns: 1fr; }
  .hero-banner.is-quiet .hero-badges,
  .hero-banner.is-quiet > .hero-meta { flex-wrap: wrap; overflow-x: visible; }
}
</style>
