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
              </div>
            </div>
          </div>
          <div class="page-hero-actions">
            <template v-if="isOwner">
              <a :href="route('bp.profile.index')" class="btn-hero-solid is-on-light"><AegisIcon name="pencil" :size="14" /> Edit</a>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only" @click="copyShareLink" data-tooltip="Copy link" aria-label="Copy link"><AegisIcon name="link" :size="14" /></button>
            </template>
            <template v-else-if="isLoggedIn">
              <template v-if="isPendingReceived">
                <button type="button" class="btn-hero-solid is-on-light" :disabled="connectForm.processing" @click="acceptInbound"><AegisIcon name="check" :size="14" /> Accept Request</button>
                <button type="button" class="btn-hero-ghost is-on-light" :disabled="connectForm.processing" @click="declineInbound"><AegisIcon name="x" :size="14" /> Decline</button>
              </template>
              <button v-if="!isConnected && !isPendingSent && !isPendingReceived" type="button" class="btn-hero-ghost is-on-light" @click="showConnectModal = true"><AegisIcon name="user-plus" :size="14" /> Connect</button>
              <button v-if="isPendingSent" type="button" class="btn-hero-ghost is-on-light" @click="cancelConnect"><AegisIcon name="x" :size="14" /> Cancel Request</button>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only" data-tooltip="Message" :disabled="msgLoading === user.id" @click="openConversation(user.id)"><AegisIcon name="message" :size="14" /></button>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only" @click="copyShareLink" data-tooltip="Share" aria-label="Share"><AegisIcon name="link" :size="14" /></button>
            </template>
            <!-- Non-member: smart CTA + share only -->
            <template v-else>
              <a :href="memberCtaRoute" class="btn-hero-solid is-on-light">
                <AegisIcon :name="memberCtaIcon" :size="14" /> {{ memberCtaLabel }}
              </a>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only" @click="copyShareLink" data-tooltip="Share profile link" aria-label="Share">
                <AegisIcon name="link" :size="14" />
              </button>
            </template>
          </div>
        </div>
        <div class="hero-meta">
          <span v-if="user.location" class="hero-meta-item"><AegisIcon name="map-pin" :size="12" />{{ user.location }}</span>
          <span v-if="displayRating" class="hero-meta-item is-rating"><AegisIcon name="star" :size="12" class="aegis-icon-filled" />{{ displayRating }} <span class="rating-count">({{ reviewCount }} reviews)</span></span>
          <span class="hero-meta-item"><AegisIcon name="briefcase" :size="12" />{{ completedJobs }} projects</span>
          <span v-if="pmStats.response_time" class="hero-meta-item"><AegisIcon name="clock" :size="12" />~{{ pmStats.response_time }}</span>
          <span v-if="user.bp_team_size" class="hero-meta-item"><AegisIcon name="user" :size="12" />Team of {{ user.bp_team_size }}</span>
          <span v-if="hourlyRate" class="hero-meta-item"><AegisIcon name="dollar" :size="12" />{{ hourlyRate }}</span>
        </div>
      </div>

      <!-- ═══ STAT CHIPS ═══ -->
      <div class="stat-chips-row">
        <AegisStatChip icon="briefcase" :value="completedJobs || '—'" label="Projects Done" />
        <AegisStatChip icon="star"      :value="displayRating || '—'" label="Client Rating" />
        <AegisStatChip icon="clock"     :value="pmStats.response_time ? '~' + pmStats.response_time : '—'" label="Response Time" />
        <AegisStatChip icon="check"     :value="pmStats.on_time_rate ? pmStats.on_time_rate + '%' : '—'" label="On-Time Delivery" />
        <AegisStatChip icon="dollar"    :value="hourlyRate || '—'" label="Hourly Rate" />
      </div>

      <!-- ═══ ENGAGEMENT ACTIVITY TRACKER (below hero, logged-in non-owner) ═══ -->
      <div v-if="isVerifiedMember && !isOwner && allEngagements.length" class="bp-eng-tracker">
        <div class="bp-eng-tracker-head">
          <span class="bp-eng-tracker-label"><AegisIcon name="clock" :size="12" /> Your pending requests</span>
          <span>{{ allEngagements.length }} pending</span>
        </div>
        <div v-for="(eng, i) in allEngagements" :key="eng.id || i" class="bp-eng-row">
          <AegisIcon name="briefcase" :size="14" />
          <span class="bp-eng-type">{{ eng.label }}</span>
          <span class="bp-eng-status">{{ eng.status.charAt(0).toUpperCase() + eng.status.slice(1) }}</span>
          <span class="bp-eng-time">{{ eng.time }}</span>
          <button class="btn btn-outline btn-xs" @click="viewRequest(eng)"><AegisIcon name="eye" :size="11" /> View</button>
        </div>
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
                <button v-if="isVerifiedMember" :class="['btn', action.btnClass, 'btn-sm']" @click="action.handler">{{ action.btnLabel }}</button>
                <a v-else :href="memberCtaRoute" :class="['btn', action.btnClass, 'btn-sm']">
                  <AegisIcon :name="memberCtaIcon" :size="11" /> {{ memberCtaLabel }}
                </a>
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

          <div v-if="isVerifiedMember && !isOwner" class="pp-section">
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
            <a :href="memberCtaRoute" class="btn btn-outline btn-sm">{{ memberCtaLabel }} to view</a>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="star" :size="13" class="aegis-icon-filled aegis-icon-gold-dark" /> Client Reviews</div>
            <div v-if="reviews.length">
              <div v-for="rev in reviews" :key="rev.created_at" style="margin-bottom:12px;padding:14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm)">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                  <div style="font-size:13px;font-weight:700;color:var(--text)">{{ rev.name }}</div>
                  <div style="display:flex;gap:1px">
                    <AegisIcon v-for="i in rev.stars" :key="'f'+i" name="star" :size="13" class="aegis-icon-filled aegis-icon-gold-dark" />
                    <AegisIcon v-for="i in (5-rev.stars)" :key="'e'+i" name="star" :size="13" class="aegis-icon-muted" />
                  </div>
                </div>
                <div v-if="rev.headline" style="font-size:12px;font-weight:600;color:var(--text-2);margin-bottom:4px">{{ rev.headline }}</div>
                <div style="font-size:12px;color:var(--text-2);line-height:1.6;font-style:italic">{{ rev.quote }}</div>
                <div style="font-size:11px;color:var(--text-3);font-weight:600;margin-top:6px">{{ rev.eng_type ? rev.eng_type + ' · ' : '' }}{{ rev.meta }}</div>
              </div>
            </div>
            <div v-else style="font-size:13px;color:var(--text-3);padding:12px 0">No reviews yet.</div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:14px;font-size:12px;color:var(--text-3)">
              <span>Client Rating: <strong style="color:var(--gold-dark)">{{ displayRating ? displayRating + '/5.0' : 'No ratings yet' }}</strong>{{ reviewCount ? ' from ' + reviewCount + ' reviews' : '' }}</span>
              <button v-if="isVerifiedMember && !isOwner" class="btn btn-outline btn-sm" @click="openLeaveReviewModal"><AegisIcon name="plus" :size="13" /> Leave Review</button>
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
            <div v-if="!isLoggedIn" class="pp-info-row"><span class="pp-info-label">Contact Details</span><span class="pp-info-val" style="font-weight:600;color:var(--text-3)"><a :href="memberCtaRoute" class="pp-ext-link" style="padding:3px 8px">{{ memberCtaLabel }} to view</a></span></div>
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

          <div v-if="isVerifiedMember && !isOwner && isConnected" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="link" :size="13" class="aegis-icon-gold-dark" /> Connection Info</div>
            <div class="pp-info-row"><span class="pp-info-label">Status</span><span class="pp-info-val" style="color:var(--green-dark)">Connected</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Active Contracts</span><span class="pp-info-val">{{ bpStats.active_contracts || 0 }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Completed Projects</span><span class="pp-info-val">{{ bpStats.completed_contracts || 0 }}</span></div>
            <div class="pp-action-row">
              <button class="btn-icon btn-icon-danger" data-tooltip="Remove partner" @click="confirmRemovePartner"><AegisIcon name="trash" :size="14" /></button>
            </div>
          </div>

          <div v-else-if="isLoggedIn && !isOwner && isPendingSent" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="clock" :size="13" class="aegis-icon-gold-dark" /> Connection Pending</div>
            <p style="font-size:13px;color:var(--text-2);margin:0 0 12px">Your connection request is awaiting a response from {{ businessName }}.</p>
            <button class="btn btn-outline btn-sm btn-danger-ghost" @click="cancelConnect"><AegisIcon name="x" :size="13" /> Cancel Request</button>
          </div>

          <div v-else-if="isLoggedIn && !isOwner && isPendingReceived" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="user-plus" :size="13" class="aegis-icon-gold-dark" /> Connection Request</div>
            <p style="font-size:13px;color:var(--text-2);margin:0 0 12px"><strong>{{ businessName }}</strong> sent you a connection request.</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <button class="btn btn-primary btn-sm" :disabled="connectForm.processing" @click="acceptInbound"><AegisIcon name="check" :size="13" /> Accept</button>
              <button class="btn btn-outline btn-sm" :disabled="connectForm.processing" @click="declineInbound">Decline</button>
            </div>
          </div>

          <div v-else-if="isVerifiedMember && !isOwner" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="user-plus" :size="13" class="aegis-icon-gold-dark" /> Connect</div>
            <p style="font-size:13px;color:var(--text-2);margin:0 0 12px">Add {{ businessName }} to your network to message them and track engagements.</p>
            <button class="btn btn-primary btn-sm" @click="showConnectModal = true"><AegisIcon name="user-plus" :size="13" /> Send Connection Request</button>
          </div>

          <div v-else-if="!isLoggedIn" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="lock" :size="13" class="aegis-icon-gold-dark" /> Connection &amp; Contract Info</div>
            <p style="font-size:12px;color:var(--text-3);line-height:1.6;margin:0 0 10px">See active contracts, NDA/BAA status, and connection history with this partner.</p>
            <a :href="memberCtaRoute" class="btn btn-outline btn-sm">{{ memberCtaLabel }} to view</a>
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
    <template v-if="isVerifiedMember && !isOwner">

      <!-- Hire Modal -->
      <!-- Connect Modal -->
      <AegisModal v-model="showConnectModal" title="Send Connection Request" size="md">
        <div class="form-group">
          <label class="form-label">Message <span style="color:var(--text-4);font-weight:500">(optional)</span></label>
          <textarea class="form-textarea" rows="3"
            :placeholder="'Hi ' + businessName + ', I\'d love to connect and explore how we can work together...'"
            v-model="connectForm.message"></textarea>
        </div>
        <template #footer>
          <button class="btn btn-outline" @click="showConnectModal = false">Cancel</button>
          <button class="btn btn-primary" :disabled="connectForm.processing" @click="sendConnect">
            <AegisIcon name="user-plus" :size="13" />
            {{ connectForm.processing ? 'Sending…' : 'Send Request' }}
          </button>
        </template>
      </AegisModal>

      <!-- Hire / Engage Modal — centralized BpEngageModal -->
      <BpEngageModal
        v-model="showHireModal"
        :partner="{
          id:       user.id,
          name:     businessName,
          role:     primaryLabel,
          initials: avatarInitials,
          avatar_url: user.avatar_url,
          rating:   displayRating,
          verified: user.verified,
          rate:     hourlyRate,
        }"
        @submitted="submitHireRequest"
      />

      <EngagementRequestModal v-model="showRequestDetail" :request="activeEngagementRequest" />
      <BpQuoteModal
        v-model="showQuoteModal"
        :partner="{ id: user.id, display_name: businessName }"
        @submitted="refreshEngagements"
      />
      <BpScheduleModal
        v-model="showScheduleModal"
        :partner="{ id: user.id, display_name: businessName }"
        @submitted="refreshEngagements"
      />

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

      <!-- Leave Review Modal -->
      <AegisModal v-model="showReviewModal" title="Leave a Review" subtitle="Share your experience to help other clinicians evaluate fit" size="md">
        <div class="form-group"><label class="form-label">Business Partner</label><input type="text" class="form-input" :value="businessName" readonly></div>
        <div class="form-group">
          <label class="form-label">Your Rating <span class="req">*</span></label>
          <div class="rating-stars" role="radiogroup" aria-label="Star rating">
            <button v-for="i in 5" :key="i" type="button"
              :class="['rating-star', reviewForm.rating >= i ? 'is-on' : '']"
              @click="reviewForm.rating = i"
              :aria-label="i + (i === 1 ? ' star' : ' stars')"
            ><AegisIcon name="star" :size="22" :class="reviewForm.rating >= i ? 'aegis-icon-filled' : ''" /></button>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Headline <span style="color:var(--text-4);font-weight:500">(optional)</span></label><input type="text" class="form-input" placeholder="e.g. Saved us $14k in the first year" maxlength="80" v-model="reviewForm.headline"></div>
        <div class="form-group">
          <label class="form-label">Your Review <span class="req">*</span></label>
          <textarea class="form-textarea" rows="4" placeholder="What was the engagement like? Strengths, areas to improve, who is this partner best suited for?" maxlength="600" v-model="reviewForm.body"></textarea>
          <div class="form-hint">Visible to other clinicians — 600 chars max</div>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">Engagement Type</label>
            <select class="form-select" v-model="reviewForm.eng_type">
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
        <div v-if="reviewForm.errors.body || reviewForm.errors.rating" class="alert alert-danger">
          {{ reviewForm.errors.rating || reviewForm.errors.body }}
        </div>
        <template #footer>
          <button class="btn btn-outline" @click="showReviewModal = false">Cancel</button>
          <button class="btn btn-primary" :disabled="reviewForm.processing" @click="submitReview">
            <span class="btn-ico"><AegisIcon name="send" :size="13" /></span>
            {{ reviewForm.processing ? 'Submitting…' : 'Submit Review' }}
          </button>
        </template>
      </AegisModal>

    </template>
  </PublicLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import AegisModal from '@/components/ui/AegisModal.vue'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import AegisStatChip from '@/components/ui/AegisStatChip.vue'
import BpEngageModal from '@/components/modals/BpEngageModal.vue'
import BpQuoteModal from '@/components/modals/BpQuoteModal.vue'
import BpScheduleModal from '@/components/modals/BpScheduleModal.vue'
import EngagementRequestModal from '@/components/modals/EngagementRequestModal.vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { useMessageButton } from '@/composables/useMessageButton'

const props = defineProps({
  user:             { type: Object,  required: true },
  profileMeta:      { type: Object,  default: () => ({}) },
  viewerRole:       { type: String,  default: null },
  isOwner:          { type: Boolean, default: false },
  isLoggedIn:       { type: Boolean, default: false },
  connectionStatus: { type: String,  default: 'not-connected' },  // 'connected'|'pending-sent'|'pending-received'|'not-connected'
  connectionId:     { type: String,  default: null },
  pendingRequestId: { type: String,  default: null },
  bpStats:          { type: Object,  default: () => ({}) },
  reviews:            { type: Array,   default: () => [] },
  engagementRequests: { type: Array,   default: () => [] },
})

const page = usePage()

const authUser         = computed(() => page.props.auth?.user ?? null)
const isVerifiedMember = computed(() => {
  if (page.props.isVerifiedMember !== undefined) return !!page.props.isVerifiedMember
  return !!(authUser.value?.verified)
})

const memberCtaLabel = computed(() => {
  if (!authUser.value)           return 'Sign In'
  if (!authUser.value?.verified) return 'Verify Email'
  return 'Activate Plan'
})
const memberCtaRoute = computed(() => {
  if (!authUser.value)           return route('login')
  if (!authUser.value?.verified) return route('verification.notice')
  return route('onboarding.plan')
})
const memberCtaIcon = computed(() => {
  if (!authUser.value)           return 'lock'
  if (!authUser.value?.verified) return 'check-circle'
  return 'credit-card'
})
const toast = useToast()
const { confirmAction } = useConfirm()
const { openConversation, loading: msgLoading } = useMessageButton()

const pm        = computed(() => props.profileMeta ?? {})
const pmStats   = computed(() => pm.value.stats ?? {})
const pmCreds   = computed(() => pm.value.credentials ?? {})
const pmContact = computed(() => pm.value.contact_meta ?? {})

const businessName   = computed(() => props.user.bp_business_name ?? props.user.display_name ?? '')
const avatarInitials = computed(() => props.user.avatar_initials ?? businessName.value.slice(0, 2).toUpperCase() ?? '??')
const categories     = computed(() => props.user.bp_categories ?? [])
const primaryLabel   = computed(() => titleCase((categories.value[0] ?? 'Services').replace(/[_-]/g, ' ')))
const hourlyRate     = computed(() => props.bpStats.hourly_rate ?? (props.user.bp_hourly_rate_cents ? '$' + Math.round(props.user.bp_hourly_rate_cents / 100) + '/hr' : null))
const displayRating  = computed(() => props.bpStats.avg_rating ?? pmStats.value.avg_rating ?? null)
const reviewCount    = computed(() => props.bpStats.review_count ?? props.reviews.length ?? 0)
const completedJobs  = computed(() => props.bpStats.completed_contracts ?? pmStats.value.jobs_completed ?? 0)

function titleCase(str) { return str.replace(/\b\w/g, c => c.toUpperCase()) }

// ── Engagement tracker — loaded from DB via props, refreshed after each submit ─
// No local buffer needed. After each form submit we do a partial Inertia reload
// (only: ['engagementRequests']) so the prop updates without full page reload.
// On BusinessProfile only pending requests show — completed ones live in SupportServices.
const allEngagements    = computed(() => (props.engagementRequests ?? []).filter(r => r.status === 'pending'))

// Engagement request detail modal
const showRequestDetail       = ref(false)
const activeEngagementRequest = ref(null)
function viewRequest(r) { activeEngagementRequest.value = r; showRequestDetail.value = true }

function refreshEngagements() {
  router.reload({ only: ['engagementRequests'], preserveScroll: true })
}

// ── Connection state ──────────────────────────────────────────────────────
const connectionStatus  = ref(props.connectionStatus)
const connectionId      = ref(props.connectionId)
const pendingRequestId  = ref(props.pendingRequestId)

// Sync local refs when Inertia partial reloads update these props
watch(() => props.connectionStatus,  v => { connectionStatus.value  = v })
watch(() => props.connectionId,      v => { connectionId.value      = v })
watch(() => props.pendingRequestId,  v => { pendingRequestId.value  = v })
const isConnected       = computed(() => connectionStatus.value === 'connected')
const isPendingSent     = computed(() => connectionStatus.value === 'pending-sent')
const isPendingReceived = computed(() => connectionStatus.value === 'pending-received')

// ── Modals ────────────────────────────────────────────────────────────────
const showHireModal     = ref(false)
const showConnectModal  = ref(false)
const showQuoteModal    = ref(false)
const showScheduleModal = ref(false)
const showReviewModal   = ref(false)
const showContractModal = ref(false)

// ── Forms ─────────────────────────────────────────────────────────────────
const connectForm  = useForm({ message: '' })
const reviewForm   = useForm({ rating: 0, headline: '', body: '', eng_type: 'Fixed-Scope Project', duration: '1-3 months' })

// ── Actions ───────────────────────────────────────────────────────────────
function openHireModal()  { showHireModal.value = true }
function openRequestQuoteModal() { showQuoteModal.value = true }
function openScheduleModal()     { showScheduleModal.value = true }
function openLeaveReviewModal()  { reviewForm.reset(); showReviewModal.value = true }

function sendConnect() {
  connectForm.post(route('public.profile.connect', props.user.id), {
    preserveScroll: true,
    onSuccess: () => {
      connectionStatus.value = 'pending-sent'
      showConnectModal.value = false
      toast.success('Connection request sent.')
    },
  })
}

function acceptInbound() {
  if (!pendingRequestId.value) return
  connectForm.post(route('provider.network.accept', pendingRequestId.value), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Connected with ' + businessName.value + '.')
      // Reload only the connection props so connectionId is populated for disconnect
      router.reload({
        only: ['connectionStatus', 'connectionId', 'pendingRequestId'],
        preserveScroll: true,
      })
    },
    onError: () => toast.error('Could not accept request.'),
  })
}

function declineInbound() {
  if (!pendingRequestId.value) return
  confirmAction({ title: 'Decline Request', message: 'Decline connection request from ' + businessName.value + '?', btnLabel: 'Decline', type: 'danger' }, () => {
    connectForm.post(route('provider.network.decline', pendingRequestId.value), {
      preserveScroll: true,
      onSuccess: () => { connectionStatus.value = 'not-connected'; pendingRequestId.value = null; toast.info('Request declined.') },
    })
  })
}

function cancelConnect() {
  if (!pendingRequestId.value) return
  confirmAction({ title: 'Cancel Request', message: 'Cancel your connection request to ' + businessName.value + '?', btnLabel: 'Cancel Request', type: 'danger' }, () => {
    router.delete(route('public.profile.cancel-connect', pendingRequestId.value), {
      preserveScroll: true,
      onSuccess: () => { connectionStatus.value = 'not-connected'; pendingRequestId.value = null; toast.success('Request cancelled.') },
    })
  })
}

function confirmRemovePartner() {
  confirmAction({ title: 'Remove Partner', message: 'Remove ' + businessName.value + ' from your network? Active contracts remain in your history.', btnLabel: 'Remove', type: 'danger' }, () => {
    router.delete(route('public.profile.disconnect', connectionId.value), {
      preserveScroll: true,
      onSuccess: () => { connectionStatus.value = 'not-connected'; connectionId.value = null; toast.success('Partner removed.') },
    })
  })
}

function submitHireRequest(formData) {
  refreshEngagements()
}

function submitReview() {
  if (!reviewForm.rating) { toast.error('Please select a star rating.'); return }
  if (!reviewForm.body)   { toast.error('Please write a review.'); return }
  reviewForm.post(route('public.profile.bp-review', props.user.id), {
    preserveScroll: true,
    onSuccess: () => { showReviewModal.value = false; toast.success('Review submitted. Thank you!') },
  })
}

function copyShareLink() {
  navigator.clipboard?.writeText(window.location.href)
    .then(() => toast.success('Profile link copied'))
    .catch(() => toast.error('Could not copy link'))
}

// ── Engagement actions (Propose Contract removed — covered by hire workflow) ──
const engagementActions = computed(() => [
  { name: 'Hire / Engage',        desc: 'Start a new engagement or activate this partner for a specific project or hourly consultation.', avail: 'open',    availLabel: 'Available Now',   btnClass: 'btn-primary', btnLabel: 'Hire Now', handler: openHireModal },
  { name: 'Request a Quote',      desc: 'Describe your needs and request a formal quote or pricing proposal from this partner.',           avail: 'open',    availLabel: '24h Turnaround', btnClass: 'btn-outline', btnLabel: 'Request',  handler: openRequestQuoteModal },
  { name: 'Schedule Consultation',desc: 'Book a discovery call, strategy session, or consultation meeting with this partner.',             avail: 'limited', availLabel: 'Limited Slots',  btnClass: 'btn-outline', btnLabel: 'Book',     handler: openScheduleModal },
])

const howItWorks = [
  { title: 'Inquiry',            desc: 'Describe your need (free, no obligation).' },
  { title: 'Discovery Call',     desc: '30-min free call to scope the work.' },
  { title: 'Proposal',           desc: 'Custom quote within 48 hours.' },
  { title: 'Contract via Aegis', desc: 'Sign + start work, all tracked in-platform.' },
]
</script>
<style scoped>
.public-profile-wrap { max-width: 960px; margin: 0 auto; padding: var(--space-6) var(--space-4); }
/* 3 engagement action cards fill the grid row */
:deep(.pp-svc-grid) { grid-template-columns: repeat(3, 1fr); }
/* bpe-option — used by Schedule Consultation modal meeting-type picker */
.bpe-option-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 14px; }
.bpe-option { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--surface); font-size: 13px; font-weight: 600; color: var(--text); cursor: pointer; transition: border-color var(--transition), background var(--transition), box-shadow var(--transition); }
.bpe-option:hover { box-shadow: var(--shadow-sm); }
.bpe-option.selected { background: var(--badge-bg-gold); border-color: var(--gold-dark); color: var(--gold-dark); font-weight: 700; }
.bpe-option-icon { flex-shrink: 0; display: inline-flex; align-items: center; line-height: 0; color: var(--text-3); }
.bpe-option.selected .bpe-option-icon { color: var(--gold-dark); }
.bpe-option-sub { font-size: 11px; font-weight: 600; color: var(--text-4); margin-top: 2px; }
/* rating stars — filled via is-on class */
.rating-stars { display: inline-flex; gap: 4px; }
.rating-star { background: transparent; border: none; padding: 4px; color: var(--text-4); cursor: pointer; line-height: 0; transition: color 0.15s ease, transform 0.15s ease; }
.rating-star:hover,
.rating-star.is-on { color: var(--gold-dark); }
.rating-star.is-on .aegis-icon { fill: var(--gold-dark); stroke: var(--gold-dark); }
.rating-star:hover { transform: scale(1.08); }
/* engagement tracker */
.bp-eng-tracker { margin: 0 0 24px; border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
.bp-eng-tracker-head { display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; background: var(--surface-2); border-bottom: 1px solid var(--border); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-4); }
.bp-eng-tracker-label { display: inline-flex; align-items: center; gap: 5px; }
.bp-eng-row { display: flex; align-items: center; gap: 12px; padding: 10px 16px; border-bottom: 1px solid var(--border); font-size: 13px; }
.bp-eng-row:last-child { border-bottom: none; }
.bp-eng-type { flex: 1; font-weight: 600; color: var(--text); }
.bp-eng-status { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: var(--radius-full); background: var(--badge-bg-gold); color: var(--gold-dark); }
.bp-eng-time { font-size: 11px; color: var(--text-4); }
/* hero overrides */
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