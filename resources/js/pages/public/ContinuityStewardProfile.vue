<!--
  pages/public/ContinuityStewardProfile.vue — public Continuity Steward profile.
  PHP source parity: public/continuity_steward.php
  Visibility tiers: anonymous | logged-in | owner
-->
<template>
  <PublicLayout>
    <div class="public-profile-wrap">

      <!-- ═══ HERO ═══ -->
      <div class="hero-banner is-quiet">
        <div class="page-hero-inner">
          <div class="page-hero-left has-icon">
            <div class="page-hero-icon is-avatar" aria-hidden="true"
                 :style="user.avatar_url ? { backgroundImage: `url(${user.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}">
              <template v-if="!user.avatar_url">{{ avatarInitials }}</template>
            </div>
            <div class="page-hero-text">
              <div class="page-hero-eyebrow">Continuity Steward</div>
              <h1 class="page-hero-title">{{ user.display_name }}{{ user.credentials ? ', ' + user.credentials : '' }}</h1>
              <div class="page-hero-sub">{{ organization }}</div>
              <div class="hero-badges">
                <span class="badge badge-green"><span class="badge-dot"></span>Accepting</span>
                <span v-if="user.verified" class="badge badge-blue"><AegisIcon name="check" :size="10" />Aegis Verified</span>
                <span v-if="user.stripe_connected" class="badge badge-green"><AegisIcon name="credit-card" :size="10" />Payment Verified</span>
              </div>
            </div>
          </div>
          <div class="page-hero-actions">
            <template v-if="isOwner">
              <a :href="route('cs.profile.index')" class="btn-hero-solid is-on-light"><AegisIcon name="pencil" :size="14" /> Edit</a>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only" @click="copyShareLink" data-tooltip="Copy link" aria-label="Copy link"><AegisIcon name="link" :size="14" /></button>
            </template>
            <template v-else-if="isLoggedIn">
              <button type="button" class="btn-hero-solid is-on-light" @click="openInquireModal"><AegisIcon name="briefcase" :size="14" /> Inquire</button>
              <button type="button" class="btn-hero-ghost is-on-light" @click="openDesignateModal"><AegisIcon name="shield" :size="14" /> Designate</button>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only" data-tooltip="Message" :disabled="msgLoading === user.id" @click="openConversation(user.id)"><AegisIcon name="message" :size="14" /></button>
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
          <span class="hero-meta-item is-rating"><AegisIcon name="star" :size="12" class="aegis-icon-filled" />{{ pm.rating ?? '4.9' }} <span class="rating-count">({{ pm.review_count ?? 38 }})</span></span>
          <span class="hero-meta-item"><AegisIcon name="user" :size="12" />{{ pmStats.practitioners_served ?? 18 }} served</span>
          <span class="hero-meta-item"><AegisIcon name="clock" :size="12" />{{ pmStats.years_experience ?? 14 }} yr exp</span>
          <span class="hero-meta-item"><AegisIcon name="alert-triangle" :size="12" />{{ pmStats.incidents_supported ?? 12 }} incidents</span>
          <span class="hero-meta-item"><AegisIcon name="refresh" :size="12" />4hr SLA</span>
        </div>
      </div>

      <!-- ═══ CAPACITY METER ═══ -->
      <div class="pp-capacity-card">
        <div style="width:48px;height:48px;border-radius:var(--radius);background:var(--badge-bg-gold);color:var(--gold-dark);display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <AegisIcon name="user" :size="22" class="aegis-icon-gold-dark" />
        </div>
        <div class="pp-capacity-info">
          <div class="pp-capacity-title">Currently Accepting Practitioners</div>
          <div class="pp-capacity-sub">Active caseload as a designated Continuity Steward</div>
          <div class="pp-capacity-bar"><div class="pp-capacity-fill" :style="'width:'+slotsPct+'%'"></div></div>
          <div class="pp-capacity-stat">
            <strong>{{ slotsFilled }} of {{ slotsTotal }}</strong> slots filled · <span style="color:var(--green-dark)">{{ slotsTotal - slotsFilled }} openings available</span>
          </div>
        </div>
        <template v-if="!isOwner">
          <button v-if="isLoggedIn" class="btn btn-primary btn-sm" @click="openReserveSlotModal">Reserve a Slot</button>
          <a v-else :href="route('login')" class="btn btn-primary btn-sm">Reserve a Slot</a>
        </template>
      </div>

      <!-- ═══ STAT CHIPS ═══ -->
      <div class="stat-chips-row">
        <AegisStatChip icon="users" :value="pmStats.practitioners_served ?? 18" label="Practitioners Served" />
        <AegisStatChip icon="check" :value="pmStats.incidents_supported ?? 12" label="Incidents Supported" />
        <AegisStatChip icon="star" :value="pm.rating ?? '4.9'" label="Steward Rating" />
        <AegisStatChip icon="clock" :value="pmStats.years_experience ?? 14" label="Years Experience" />
        <AegisStatChip icon="briefcase" :value="pmFees.annual_hours ?? '480 hr/yr'" label="Annual Capacity" />
      </div>

      <!-- ═══ TWO-COLUMN GRID ═══ -->
      <div class="pp-grid">
        <!-- LEFT -->
        <div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="user" :size="13" class="aegis-icon-gold-dark" /> About {{ user.display_name }}</div>
            <p style="font-size:13px;color:var(--text-2);line-height:1.7;margin:0 0 12px">{{ user.bio ?? 'Continuity Steward serving healthcare and behavioral-health practitioners. Maat-certified for emergency continuity protocols, document-vault execution, and patient-roster handoff during a verified critical incident.' }}</p>
            <p style="font-size:13px;color:var(--text-2);line-height:1.7;margin:0 0 12px">Specializes in solo practitioner and small-group continuity planning across multi-state behavioral health, telehealth providers, and hybrid clinical settings. Authorized to execute on all 7 standard incident types under the Aegis Continuity Plan framework.</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
              <a href="#" class="pp-ext-link"><AegisIcon name="briefcase" :size="11" />LinkedIn</a>
              <a href="#" class="pp-ext-link"><AegisIcon name="globe" :size="11" />Practice Website</a>
              <a href="#" class="pp-ext-link"><AegisIcon name="clipboard" :size="11" />Maat Certificate</a>
            </div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="clipboard" :size="13" class="aegis-icon-gold-dark" /> Areas of Stewardship Expertise</div>
            <div class="pp-section-eyebrow">Core Competencies</div>
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:14px">
              <span v-for="s in ['Practice Closure','Patient Continuity','Records Custody','Billing Wind-Down','Critical Incident Response','Crisis Management','Regulatory Compliance','OOO & Coverage Planning']" :key="s" class="tag-chip">{{ s }}</span>
            </div>
            <div class="pp-section-eyebrow">Legal &amp; Compliance</div>
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:14px">
              <span v-for="s in ['HIPAA','Medical Records Handling','42 CFR Part 2','State Licensure Wind-Down','Estate & Probate']" :key="s" class="tag-chip">{{ s }}</span>
            </div>
            <div class="pp-section-eyebrow">Practitioner Types Served</div>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <span v-for="s in ['Solo MD Practices','Therapists / LCSW','Group Practices','Multi-State Behavioral Health','Telehealth Providers','Specialty Practices']" :key="s" class="pn-tag">{{ s }}</span>
            </div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="shield" :size="13" class="aegis-icon-gold-dark" /> Continuity Plan Framework</div>
            <p style="font-size:12px;color:var(--text-3);line-height:1.6;margin:0 0 12px">Activation scenarios this Steward is authorized to execute under a designated Continuity Plan.</p>
            <div v-for="inc in incidentTypes" :key="inc" class="pp-info-row">
              <span class="pp-info-label">{{ inc }}</span><span class="pp-info-val" style="color:var(--green-dark)">Authorized</span>
            </div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Voluntary Release / Retirement</span><span class="pp-info-val" style="color:var(--green-dark)">Authorized</span></div>
          </div>

          <div v-if="isOwner" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="pencil" :size="13" class="aegis-icon-gold-dark" /> Profile Visibility</div>
            <p style="font-size:12px;color:var(--text-3);line-height:1.6;margin:0 0 10px">This is your public Continuity Steward profile. Practitioners use this page to evaluate you before designation. Active practitioner caseload, your private notes, and your engagement history are <strong style="color:var(--green-dark)">never shown</strong> publicly.</p>
            <a :href="route('cs.profile.index')" class="btn btn-outline btn-sm">Edit profile</a>
          </div>

          <div v-if="isLoggedIn" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="bar-chart" :size="13" class="aegis-icon-gold-dark" /> Performance &amp; Outcome Metrics</div>
            <div class="pp-metric-grid">
              <div class="pp-metric-tile green"><div class="pp-metric-tile-val">98%</div><div class="pp-metric-tile-lbl">SLA Met (4hr Response)</div></div>
              <div class="pp-metric-tile blue"><div class="pp-metric-tile-val">{{ pmStats.incidents_supported ?? 12 }}</div><div class="pp-metric-tile-lbl">Critical Incidents Handled</div></div>
              <div class="pp-metric-tile gold"><div class="pp-metric-tile-val">100%</div><div class="pp-metric-tile-lbl">Annual Reviews On-Time</div></div>
            </div>
            <div class="pp-info-row"><span class="pp-info-label">Avg. Time to Verification</span><span class="pp-info-val">2.4 hours</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Patient Roster Handoffs</span><span class="pp-info-val" style="color:var(--green-dark)">100% completed within 14 days</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Records Custody Transfers</span><span class="pp-info-val" style="color:var(--green-dark)">12 completed</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Billing Wind-Downs</span><span class="pp-info-val">8 completed</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Compliance Audits</span><span class="pp-info-val" style="color:var(--green-dark)">2 passed (Maat audit)</span></div>
          </div>
          <div v-else class="pp-section">
            <div class="pp-section-title"><AegisIcon name="lock" :size="13" class="aegis-icon-gold-dark" /> Performance Metrics</div>
            <p style="font-size:12px;color:var(--text-3);line-height:1.6;margin:0 0 10px">SLA performance, incident handling stats, and audit compliance are visible to verified Aegis network members.</p>
            <a :href="route('login')" class="btn btn-outline btn-sm">Sign in to view</a>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="star" :size="13" class="aegis-icon-filled aegis-icon-gold-dark" /> Practitioner Reviews</div>
            <div v-for="rev in practitionerReviews" :key="rev.name" style="margin-bottom:12px;padding:14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm)">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                <div style="font-size:13px;font-weight:700;color:var(--text)">{{ rev.name }}</div>
                <div style="display:flex;gap:1px">
                  <AegisIcon v-for="i in 5" :key="i" name="star" :size="13" class="aegis-icon-filled aegis-icon-gold-dark" />
                </div>
              </div>
              <div style="font-size:12px;color:var(--text-2);line-height:1.6;font-style:italic">{{ rev.quote }}</div>
              <div style="font-size:11px;color:var(--text-3);font-weight:600;margin-top:6px">{{ rev.meta }}</div>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:14px;font-size:12px;color:var(--text-3)">
              Steward Rating: <strong style="color:var(--gold-dark)">{{ pm.rating ?? '4.9' }}/5.0</strong> from {{ pm.review_count ?? 38 }} practitioners
              <button v-if="isLoggedIn && !isOwner" class="btn btn-outline btn-sm" @click="openEndorseModal"><AegisIcon name="plus" :size="13" /> Endorse</button>
            </div>
          </div>

        </div>

        <!-- RIGHT -->
        <div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="map-pin" :size="13" class="aegis-icon-gold-dark" /> Contact &amp; Office</div>
            <div class="pp-info-row"><span class="pp-info-label">Organization</span><span class="pp-info-val">{{ organization }}</span></div>
            <div v-if="user.location" class="pp-info-row"><span class="pp-info-label">Location</span><span class="pp-info-val">{{ user.location }}</span></div>
            <div v-if="isLoggedIn && user.email" class="pp-info-row"><span class="pp-info-label">Email</span><span class="pp-info-val"><a :href="'mailto:'+user.email">{{ user.email }}</a></span></div>
            <div v-if="isLoggedIn && user.phone" class="pp-info-row"><span class="pp-info-label">24/7 Phone</span><span class="pp-info-val">{{ user.phone }}</span></div>
            <div v-if="!isLoggedIn" class="pp-info-row"><span class="pp-info-label">Contact Details</span><span class="pp-info-val" style="font-weight:600;color:var(--text-3)"><a :href="route('login')" class="pp-ext-link" style="padding:3px 8px">Sign in to view</a></span></div>
            <div class="pp-info-row"><span class="pp-info-label">Best Channel</span><span class="pp-info-val">{{ pmContact.best_channel ?? 'Aegis Message' }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Response SLA</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmContact.response_sla ?? 'Within 4 business hours' }}</span></div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="shield" :size="13" class="aegis-icon-gold-dark" /> Credentials &amp; Qualifications</div>
            <div class="pp-info-row"><span class="pp-info-label">Maat Certification</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmCreds.maat_status ?? 'Active · Renewed Mar 2025' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Highest Degree</span><span class="pp-info-val">{{ pmCreds.highest_degree ?? 'JD · MBA' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Bar License</span><span class="pp-info-val">{{ pmCreds.bar_license ?? 'NY · Active' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">License #</span><span class="pp-info-val" style="font-family:var(--font-mono,monospace);font-size:11px">{{ pmCreds.license_number ?? 'NY-Bar-948210' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Background Check</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmCreds.background_check ?? 'Passed (Jan 2025)' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">E&amp;O Insurance</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmCreds.eo_insurance ?? '$2M Coverage' }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">HIPAA Training</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmCreds.hipaa_training ?? 'Annual · Current' }}</span></div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="credit-card" :size="13" class="aegis-icon-gold-dark" /> Fee Structure</div>
            <div class="pp-info-row"><span class="pp-info-label">Annual Retainer</span><span class="pp-info-val">{{ pmFees.retainer_band ?? '$3,500–$6,000 / yr' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Hourly (Active Incident)</span><span class="pp-info-val">{{ pmFees.hourly_rate ?? '$165' }} / hr</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Standby Fee</span><span class="pp-info-val">{{ pmFees.standby_fee ?? 'Included in retainer' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Annual Plan Review</span><span class="pp-info-val">{{ pmFees.annual_review ?? 'Included' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Payment Terms</span><span class="pp-info-val">{{ pmFees.payment_terms ?? 'Net 15 · ACH or Wire' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Billed To</span><span class="pp-info-val">{{ pmFees.billed_to ?? 'Practitioner / Estate' }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Stripe Connect</span><span class="pp-info-val" :style="user.stripe_connected ? 'color:var(--green-dark)' : 'color:var(--text-4)'">{{ user.stripe_connected ? 'Enabled' : 'Not configured' }}</span></div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="globe" :size="13" class="aegis-icon-gold-dark" /> Service Coverage</div>
            <div class="pp-info-row"><span class="pp-info-label">Service Mode</span><span class="pp-info-val">Remote &amp; On-site (NY/NJ/CT)</span></div>
            <div class="pp-info-row"><span class="pp-info-label">On-site Response</span><span class="pp-info-val">Within 24 hr (tri-state)</span></div>
            <div class="pp-info-row"><span class="pp-info-label">24/7 Availability</span><span class="pp-info-val" style="color:var(--green-dark)">Yes · On-call rotation</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Time Zones Covered</span><span class="pp-info-val">EST · CST</span></div>
            <div class="pp-section-eyebrow" style="margin-top:14px">Licensed States</div>
            <div class="pp-state-grid">
              <div v-for="s in ['NY','NJ','CT','PA','MA','FL','CA','TX','IL']" :key="s" class="pp-state-pill">{{ s }}</div>
            </div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="globe" :size="13" class="aegis-icon-gold-dark" /> Languages</div>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <span class="tag-chip">English</span><span class="tag-chip">Spanish</span><span class="tag-chip">Mandarin</span>
            </div>
          </div>

          <div v-if="isLoggedIn && !isOwner" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="link" :size="13" class="aegis-icon-gold-dark" /> Connection Info</div>
            <div class="pp-info-row"><span class="pp-info-label">Connected Since</span><span class="pp-info-val">{{ pmConn.connected_since ?? 'January 2024' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Connection Type</span><span class="pp-info-val">{{ pmConn.connection_type ?? 'Designated Steward' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Plan Status</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmConn.plan_status ?? 'Active · Reviewed Feb 2025' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Last Interaction</span><span class="pp-info-val">{{ pmConn.last_interaction ?? 'Feb 8, 2025' }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Profile Completeness</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmConn.profile_completeness ?? '96%' }} Complete</span></div>
            <div class="pp-action-row">
              <button class="btn-icon btn-icon-danger" data-tooltip="Remove designation" @click="confirmRemoveDesignation"><AegisIcon name="trash" :size="14" /></button>
            </div>
          </div>
          <div v-else-if="!isLoggedIn" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="lock" :size="13" class="aegis-icon-gold-dark" /> Connection Info</div>
            <p style="font-size:12px;color:var(--text-3);line-height:1.6;margin:0 0 10px">See when this Steward joined Aegis, your designation status, and your shared engagement history.</p>
            <a :href="route('login')" class="btn btn-outline btn-sm">Sign in to view</a>
          </div>

          <div v-if="isOwner" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="pencil" :size="13" class="aegis-icon-gold-dark" /> Your Private Notes</div>
            <div class="pp-note-box">Practitioners typically reach out after a referral or annual plan-review reminder. Keep my SLA banner crisp on the public page — it's the deciding factor for many.<div class="pp-note-meta">Added Feb 2, 2025</div></div>
            <div class="pp-note-box">Group practices need extra scope confirmation in the first call (multi-state licensure questions are common). Block 45 mins not 30.<div class="pp-note-meta">Added Jan 14, 2025</div></div>
            <textarea class="pp-note-edit" placeholder="Add a private note about your practice or designation flow…"></textarea>
            <button class="btn btn-primary btn-sm" style="margin-top:8px" @click="toast.success('Note saved')">Save Note</button>
          </div>

        </div>
      </div>

    </div>

    <!-- ═══ MODALS ═══ -->
    <template v-if="isLoggedIn && !isOwner">

      <!-- Inquire Modal -->
      <AegisModal v-model="showInquireModal" title="Inquire About Availability" subtitle="Confirm capacity and SLA fit before formally designating" size="md">
        <div class="pp-tip-box" style="margin-top:0;display:flex;gap:10px;align-items:flex-start">
          <span style="flex-shrink:0;margin-top:1px;display:inline-flex;align-items:center;line-height:0"><AegisIcon name="info" :size="16" class="aegis-icon-gold-dark" /></span>
          <div class="pp-tip-body">An inquiry lets you confirm capacity and SLA fit before formally designating. Response within 4 business hours.</div>
        </div>
        <div class="form-group"><label class="form-label">Continuity Steward</label><input type="text" class="form-input" :value="user.display_name + (user.credentials ? ', ' + user.credentials : '')" readonly></div>
        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">Practice Type <span class="req">*</span></label>
            <select class="form-select" v-model="inquireForm.practiceType">
              <option>Solo Practitioner</option><option>Group Practice (2–5)</option><option>Group Practice (6+)</option><option>Telehealth</option><option>Multi-State</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Preferred Start <span class="req">*</span></label><input type="date" class="form-input" v-model="inquireForm.startDate"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Engagement Type</label>
          <select class="form-select" v-model="inquireForm.engagementType">
            <option>Annual Continuity Retainer</option><option>One-Time Plan Setup</option><option>Active Incident (Critical)</option><option>Coverage / Out-of-Office</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Notes <span style="color:var(--text-4);font-weight:500">(optional)</span></label><textarea class="form-textarea" rows="3" placeholder="Briefly describe your practice context, urgency, or any specific questions…" v-model="inquireForm.notes"></textarea></div>
        <template #footer>
          <button class="btn btn-outline" @click="showInquireModal = false">Cancel</button>
          <button class="btn btn-primary" @click="submitInquire"><span class="btn-ico"><AegisIcon name="send" :size="13" /></span>Send Inquiry</button>
        </template>
      </AegisModal>

      <!-- Designate Modal -->
      <AegisModal v-model="showDesignateModal" title="Designate as Continuity Steward" subtitle="Authorize this Steward to execute on your Continuity Plan" size="lg">
        <div class="modal-steps">
          <div class="modal-step done"><div class="modal-step-num"><AegisIcon name="check" :size="12" /></div>Steward Selected</div>
          <div class="modal-step-divider"></div>
          <div class="modal-step active"><div class="modal-step-num">2</div>Plan Details</div>
          <div class="modal-step-divider"></div>
          <div class="modal-step"><div class="modal-step-num">3</div>Authorization</div>
          <div class="modal-step-divider"></div>
          <div class="modal-step"><div class="modal-step-num">4</div>Confirm</div>
        </div>
        <div class="pp-tip-box" style="margin-top:0;display:flex;gap:10px;align-items:flex-start">
          <span style="flex-shrink:0;margin-top:1px;display:inline-flex;align-items:center;line-height:0"><AegisIcon name="shield" :size="16" class="aegis-icon-gold-dark" /></span>
          <div class="pp-tip-body">Designating a Continuity Steward authorizes them to execute on your Continuity Plan in the event of a verified critical incident. The Steward must accept and a digital authorization is countersigned via Aegis.</div>
        </div>
        <div class="form-group"><label class="form-label">Continuity Steward</label><input type="text" class="form-input" :value="user.display_name + (user.credentials ? ', ' + user.credentials : '')" readonly></div>
        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">Plan Type <span class="req">*</span></label>
            <select class="form-select" v-model="designateForm.planType">
              <option>Annual Continuity Plan</option><option>Multi-Year Continuity Plan</option><option>Critical Incident Coverage Only</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Plan Effective Date <span class="req">*</span></label><input type="date" class="form-input" v-model="designateForm.effectiveDate"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Authorized Incident Types</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:13px;line-height:1.5">
            <label v-for="inc in allIncidentTypes" :key="inc" style="display:flex;align-items:center;gap:10px;line-height:1.5;cursor:pointer"><input type="checkbox" checked> {{ inc }}</label>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Notes for Steward <span style="color:var(--text-4);font-weight:500">(optional)</span></label><textarea class="form-textarea" rows="3" placeholder="Practice particulars, multi-state nuances, or special instructions…" v-model="designateForm.notes"></textarea></div>
        <label style="display:flex;align-items:flex-start;gap:10px;font-size:13px;line-height:1.5;cursor:pointer;margin-top:4px"><input type="checkbox" v-model="designateForm.authorized" style="margin-top:2px"> I authorize this Steward to execute on the selected incident types under the Aegis Continuity Plan framework.</label>
        <template #footer>
          <button class="btn btn-outline" @click="showDesignateModal = false">Cancel</button>
          <button class="btn btn-primary" @click="submitDesignate"><span class="btn-ico"><AegisIcon name="shield" :size="13" /></span>Send Designation</button>
        </template>
      </AegisModal>

      <!-- Reserve Slot Modal -->
      <AegisModal v-model="showReserveModal" title="Reserve a Caseload Slot" subtitle="Hold a caseload slot for up to 14 days while you finalize" size="md">
        <div class="pp-tip-box" style="margin-top:0;display:flex;gap:10px;align-items:flex-start">
          <span style="flex-shrink:0;margin-top:1px;display:inline-flex;align-items:center;line-height:0"><AegisIcon name="info" :size="16" class="aegis-icon-gold-dark" /></span>
          <div class="pp-tip-body">Reserving a slot holds capacity for up to 14 days while you finalize your designation. Subject to Steward acceptance.</div>
        </div>
        <div class="form-group"><label class="form-label">Continuity Steward</label><input type="text" class="form-input" :value="user.display_name + (user.credentials ? ', ' + user.credentials : '')" readonly></div>
        <div class="form-row form-row-2">
          <div class="form-group"><label class="form-label">Hold Until <span class="req">*</span></label><input type="date" class="form-input" v-model="reserveForm.holdUntil"></div>
          <div class="form-group">
            <label class="form-label">Practice Size</label>
            <select class="form-select" v-model="reserveForm.size">
              <option>Solo</option><option>2–5 clinicians</option><option>6+ clinicians</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Reason for Reservation</label>
          <select class="form-select" v-model="reserveForm.reason">
            <option>Awaiting board / partner approval</option><option>Currently evaluating multiple Stewards</option><option>Plan documents in progress</option><option>Other</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Notes <span style="color:var(--text-4);font-weight:500">(optional)</span></label><textarea class="form-textarea" rows="3" placeholder="Anything the Steward should know while holding your slot…" v-model="reserveForm.notes"></textarea></div>
        <template #footer>
          <button class="btn btn-outline" @click="showReserveModal = false">Cancel</button>
          <button class="btn btn-primary" @click="submitReserve"><span class="btn-ico"><AegisIcon name="check" :size="13" /></span>Reserve Slot</button>
        </template>
      </AegisModal>

      <!-- Endorse Modal -->
      <AegisModal v-model="showEndorseModal" title="Endorse This Steward" subtitle="Share your experience to help colleagues evaluate fit" size="md">
        <div class="form-group"><label class="form-label">Continuity Steward</label><input type="text" class="form-input" :value="user.display_name + (user.credentials ? ', ' + user.credentials : '')" readonly></div>
        <div class="form-group">
          <label class="form-label">Your Rating <span class="req">*</span></label>
          <div class="rating-stars" role="radiogroup" aria-label="Star rating">
            <button v-for="i in 5" :key="i" type="button" :class="['rating-star', endorseForm.rating >= i ? 'is-on' : '']" @click="endorseForm.rating = i" :aria-label="i + (i === 1 ? ' star' : ' stars')"><AegisIcon name="star" :size="22" /></button>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Headline <span style="color:var(--text-4);font-weight:500">(optional)</span></label><input type="text" class="form-input" placeholder="e.g. Responsive, well-prepared, easy to work with" maxlength="80" v-model="endorseForm.headline"></div>
        <div class="form-group">
          <label class="form-label">Your Endorsement <span class="req">*</span></label>
          <textarea class="form-textarea" rows="4" placeholder="What stood out about this Steward? How did they support your practice?" maxlength="600" v-model="endorseForm.body"></textarea>
          <div class="form-hint">Visible to other practitioners on this profile · 600 chars max</div>
        </div>
        <div class="form-group">
          <label class="form-label">Engagement Context</label>
          <select class="form-select" v-model="endorseForm.context">
            <option>Continuity Plan setup</option><option>Annual retainer</option><option>Active incident handoff</option><option>Out-of-office coverage</option><option>Other</option>
          </select>
        </div>
        <template #footer>
          <button class="btn btn-outline" @click="showEndorseModal = false">Cancel</button>
          <button class="btn btn-primary" @click="submitEndorse"><span class="btn-ico"><AegisIcon name="send" :size="13" /></span>Submit Endorsement</button>
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
import { useMessageButton } from '@/composables/useMessageButton'

const props = defineProps({
  user:       { type: Object,  required: true }
})

const page = usePage()
const toast = useToast()
const { confirmAction } = useConfirm()
const { openConversation, loading: msgLoading } = useMessageButton()

// Derive auth state from Inertia shared props — zero dependency on controller passing them
const authUser   = computed(() => page.props.auth?.user ?? null)
const isLoggedIn = computed(() => !!authUser.value)
const isOwner    = computed(() => isLoggedIn.value && authUser.value?.id === props.user?.id)

const pm         = computed(() => props.user.profile_meta ?? {})
const pmStats    = computed(() => pm.value.stats ?? {})
const pmFees     = computed(() => pm.value.fees ?? {})
const pmCreds    = computed(() => pm.value.credentials ?? {})
const pmContact  = computed(() => pm.value.contact_meta ?? {})
const pmConn     = computed(() => pm.value.connection ?? {})
const pmCapacity = computed(() => pm.value.capacity ?? {})

const organization = computed(() => props.user.organization ?? 'Independent Continuity Steward')
const avatarInitials = computed(() => props.user.avatar_initials ?? props.user.display_name?.slice(0, 2).toUpperCase() ?? '??')

const slotsFilled = computed(() => pmCapacity.value.slots_filled ?? 15)
const slotsTotal  = computed(() => pmCapacity.value.slots_total ?? 20)
const slotsPct    = computed(() => Math.round((slotsFilled.value / Math.max(slotsTotal.value, 1)) * 100))

const incidentTypes = ['Death', 'Long-Term Incapacitation', 'Short-Term Absence', 'Missing Person', 'Natural Disaster', 'Detainment']
const allIncidentTypes = [...incidentTypes, 'Voluntary Release / Retirement']

const practitionerReviews = [
  { name: 'Dr. Sarah Chen, LCSW', quote: 'Genuinely takes the time to understand the practice. When my partner had a medical emergency, the handoff was seamless and my patients felt cared for.', meta: 'Practitioner since 2022 · Critical incident handled Jan 2024' },
  { name: 'Dr. James Okafor, MD', quote: 'Knowledgeable about multi-state licensure shutdowns. Annual review check-ins are thorough and don\'t waste my time.', meta: 'Practitioner since 2023' },
  { name: 'Dr. Priya Nair, PhD',  quote: 'Top-tier communication. Always available within the SLA window. The Continuity Plan signing process was much simpler than I expected.', meta: 'Practitioner since 2024' },
]

const showInquireModal  = ref(false)
const showDesignateModal = ref(false)
const showReserveModal  = ref(false)
const showEndorseModal  = ref(false)

const inquireForm  = ref({ practiceType: 'Solo Practitioner', startDate: '', engagementType: 'Annual Continuity Retainer', notes: '' })
const designateForm = ref({ planType: 'Annual Continuity Plan', effectiveDate: '', notes: '', authorized: false })
const reserveForm  = ref({ holdUntil: '', size: 'Solo', reason: 'Plan documents in progress', notes: '' })
const endorseForm  = ref({ rating: 0, headline: '', body: '', context: 'Continuity Plan setup' })

function openInquireModal()  { showInquireModal.value = true }
function openDesignateModal() { showDesignateModal.value = true }
function openReserveSlotModal() { showReserveModal.value = true }
function openEndorseModal()  { endorseForm.value = { rating: 0, headline: '', body: '', context: 'Continuity Plan setup' }; showEndorseModal.value = true }

function submitInquire()   { showInquireModal.value = false; toast.success('Inquiry sent') }
function submitDesignate() { showDesignateModal.value = false; toast.success('Designation request sent') }
function submitReserve()   { showReserveModal.value = false; toast.success('Slot reserved — Steward notified') }
function submitEndorse()   { showEndorseModal.value = false; toast.success('Endorsement submitted') }

function copyShareLink() {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(window.location.href)
      .then(() => toast.success('Profile link copied'))
      .catch(() => toast.error('Could not copy link'))
  } else {
    toast.success('Profile link copied')
  }
}
function confirmRemoveDesignation() {
  confirmAction('Remove this Steward designation? This cannot be undone.', () => toast.success('Designation removed'), { title: 'Remove Designation', btnLabel: 'Remove', type: 'danger' })
}
</script>

<style scoped>
.public-profile-wrap { max-width: 960px; margin: 0 auto; padding: var(--space-6) var(--space-4); }
.pp-capacity-bar { height: 8px; background: var(--surface-3); border-radius: var(--radius-full); overflow: hidden; margin: 8px 0 6px; }
.pp-capacity-fill { height: 100%; background: var(--gold-dark); border-radius: var(--radius-full); transition: width 0.4s ease; }
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
.rating-stars { display: inline-flex; gap: 4px; }
.rating-star { background: transparent; border: none; padding: 4px; color: var(--text-4); cursor: pointer; line-height: 0; transition: color 0.15s ease, transform 0.15s ease; }
.rating-star:hover { transform: scale(1.08); }
.rating-star.is-on { color: var(--gold-dark); }
@media (max-width: 720px) {
  .hero-banner.is-quiet .hero-badges,
  .hero-banner.is-quiet > .hero-meta { flex-wrap: wrap; overflow-x: visible; }
}
</style>
