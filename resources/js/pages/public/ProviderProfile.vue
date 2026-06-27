<!--
  pages/public/ProviderProfile.vue — public practitioner profile.
  PHP source parity: public/provider.php
  Visibility tiers: anonymous | logged-in | owner
-->
<template>
  <PublicLayout>
    <div class="public-profile-wrap">

      <!-- ═══ HERO ═══ -->
      <div class="hero-banner is-quiet">
        <div class="page-hero-inner">
          <div class="page-hero-left has-icon">
            <div class="page-hero-icon is-avatar" aria-hidden="true">{{ avatarInitials }}</div>
            <div class="page-hero-text">
              <div class="page-hero-eyebrow">Practitioner Profile</div>
              <h1 class="page-hero-title">{{ user.display_name }}{{ user.credentials ? ', ' + user.credentials : '' }}</h1>
              <div class="page-hero-sub">{{ specialtyLabel }} &nbsp;·&nbsp; {{ practiceName }}</div>
              <div class="hero-badges">
                <span class="badge badge-green"><span class="badge-dot"></span>Accepting Referrals</span>
                <span v-if="user.verified" class="badge badge-blue">
                  <AegisIcon name="check" :size="10" />Aegis Verified
                </span>
                <span v-if="user.services_mode" class="badge badge-gold">
                  <AegisIcon name="briefcase" :size="10" />Services Enabled
                </span>
                <span v-if="acceptsTelehealth" class="badge badge-blue">
                  <AegisIcon name="monitor" :size="10" />Telehealth
                </span>
                <span v-if="user.stripe_connected" class="badge badge-green">
                  <AegisIcon name="credit-card" :size="10" />Payment Verified
                </span>
              </div>
            </div>
          </div>
          <div class="page-hero-actions">
            <template v-if="isOwner">
              <a :href="route('provider.profile.index')" class="btn-hero-solid is-on-light">
                <AegisIcon name="pencil" :size="14" /> Edit
              </a>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only" @click="copyShareLink" data-tooltip="Copy link" aria-label="Copy link">
                <AegisIcon name="link" :size="14" />
              </button>
            </template>
            <template v-else-if="isLoggedIn">
              <button type="button" class="btn-hero-solid is-on-light" @click="openReferralModal">
                <AegisIcon name="refresh" :size="14" /> Refer
              </button>
              <button type="button" class="btn-hero-ghost is-on-light" @click="openServiceRequest('Appointment')">
                <AegisIcon name="calendar" :size="14" /> Schedule
              </button>
              <a :href="route('provider.messages.index') + '?to=' + user.id" class="btn-hero-ghost is-on-light is-icon-only" data-tooltip="Message" aria-label="Message">
                <AegisIcon name="message" :size="14" />
              </a>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only" @click="copyShareLink" data-tooltip="Share" aria-label="Share">
                <AegisIcon name="link" :size="14" />
              </button>
            </template>
            <template v-else>
              <a :href="route('login')" class="btn-hero-solid is-on-light">
                <AegisIcon name="message" :size="14" /> Sign In
              </a>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only" @click="copyShareLink" data-tooltip="Share" aria-label="Share">
                <AegisIcon name="link" :size="14" />
              </button>
            </template>
          </div>
        </div>

        <div class="hero-meta">
          <a v-if="user.location" class="hero-meta-item" :href="'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(user.location)" target="_blank" rel="noopener" data-tooltip="View on a map" style="text-decoration:none;color:inherit">
            <AegisIcon name="map-pin" :size="12" />{{ user.location }}
          </a>
          <span v-if="showRatings" class="hero-meta-item is-rating">
            <AegisIcon name="star" :size="12" class="aegis-icon-filled" />
            {{ pm.rating ?? '4.9' }} <span class="rating-count">({{ pm.review_count ?? 62 }})</span>
          </span>
          <span v-if="showRefStats" class="hero-meta-item">
            <AegisIcon name="refresh" :size="12" />{{ pmStats.avg_response ?? '1.8h' }} response
          </span>
          <span class="hero-meta-item"><AegisIcon name="monitor" :size="12" />In-Person · Telehealth</span>
          <span v-if="showDemographics" class="hero-meta-item"><AegisIcon name="globe" :size="12" />EN · ES</span>
          <span v-if="showDemographics" class="hero-meta-item"><AegisIcon name="user" :size="12" />{{ pmIdentity.pronouns ?? 'He/Him' }}</span>
        </div>
      </div>

      <!-- ═══ STAT ROW (6) ═══ -->
      <div class="pp-stat-row">
        <template v-if="showRefStats">
          <div class="pp-stat-box"><div class="pp-stat-val">14</div><div class="pp-stat-lbl">Referrals Exchanged</div></div>
          <div class="pp-stat-box"><div class="pp-stat-val" style="color:var(--green-dark)">92%</div><div class="pp-stat-lbl">Acceptance Rate</div></div>
          <div class="pp-stat-box"><div class="pp-stat-val">{{ pmStats.avg_response ?? '1.8h' }}</div><div class="pp-stat-lbl">Avg Response</div></div>
        </template>
        <div v-if="showRatings" class="pp-stat-box">
          <div class="pp-stat-val">{{ pm.rating ?? '4.9' }}
            <span style="display:inline-flex;vertical-align:middle;line-height:0;margin-left:2px"><AegisIcon name="star" :size="12" class="aegis-icon-filled aegis-icon-gold-dark" /></span>
          </div>
          <div class="pp-stat-lbl">Overall Rating</div>
        </div>
        <div class="pp-stat-box"><div class="pp-stat-val" style="color:var(--blue-dark)">7</div><div class="pp-stat-lbl">Mutual Connections</div></div>
        <div class="pp-stat-box"><div class="pp-stat-val" style="color:var(--gold-dark)">New</div><div class="pp-stat-lbl">Client Slots</div></div>
      </div>

      <!-- ═══ SERVICES PANEL ═══ -->
      <div v-if="user.services_mode" class="pp-svc-section">
        <div class="pp-svc-header">
          <div class="pp-svc-header-title">
            <AegisIcon name="briefcase" :size="16" class="aegis-icon-gold-dark" />Services
          </div>
          <span class="pp-svc-header-badge">Provider Services Mode</span>
        </div>
        <div class="pp-svc-body">
          <div class="pp-svc-intro">This provider offers licensed professional services to other providers — including supervision, consultation, and training. Provider-to-provider engagements, not client-facing services.</div>
          <div class="pp-svc-grid">
            <div v-for="svc in providerServices" :key="svc.name" class="pp-svc-card">
              <div class="pp-svc-card-top">
                <div class="pp-svc-card-name">{{ svc.name }}</div>
                <div class="pp-svc-card-price">{{ svc.price }}<span>{{ svc.unit }}</span></div>
              </div>
              <div class="pp-svc-card-desc">{{ svc.desc }}</div>
              <div class="pp-svc-card-meta">
                <span><AegisIcon name="clock" :size="11" />{{ svc.duration }}</span>
                <span><AegisIcon name="monitor" :size="11" />{{ svc.format }}</span>
              </div>
              <div class="pp-svc-card-footer">
                <span :class="['pp-svc-card-avail', svc.avail]">
                  <AegisIcon name="circle-dot" :size="9" class="aegis-icon-filled" />{{ svc.availLabel }}
                </span>
                <template v-if="!isOwner">
                  <button v-if="isLoggedIn" class="btn btn-outline btn-sm" @click="openServiceRequest(svc.name)">Request</button>
                  <a v-else :href="route('login')" class="btn btn-outline btn-sm">Sign in to Request</a>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ TWO-COLUMN GRID ═══ -->
      <div class="pp-grid">
        <!-- LEFT -->
        <div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="user" :size="13" class="aegis-icon-gold-dark" /> About {{ user.display_name }}</div>
            <p style="font-size:13px;color:var(--text-2);line-height:1.7;margin:0 0 12px">{{ user.bio ?? 'Bio not yet provided.' }}</p>
            <p style="font-size:13px;color:var(--text-2);line-height:1.7;margin:0 0 12px">An active Aegis network member, known for rapid response times and highly collaborative referral practices. Open to co-management and shared care plans across primary care, behavioral health, and specialty referrals.</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
              <a href="#" class="pp-ext-link"><AegisIcon name="briefcase" :size="11" />LinkedIn</a>
              <a href="#" class="pp-ext-link"><AegisIcon name="globe" :size="11" />Practice Website</a>
              <a href="#" class="pp-ext-link"><AegisIcon name="clipboard" :size="11" />Publications (14)</a>
            </div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="clipboard" :size="13" class="aegis-icon-gold-dark" /> Specialties &amp; Clinical Focus</div>
            <div class="pp-info-row"><span class="pp-info-label">Provider Type</span><span class="pp-info-val">{{ user.credentials ?? '—' }} · {{ specialtyLabel }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Years in Practice</span><span class="pp-info-val">10+ years</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Practice Setting</span><span class="pp-info-val">{{ practiceName }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Session Format</span><span class="pp-info-val">{{ sessionFormat }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Session Length</span><span class="pp-info-val">50 min standard</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Co-Treatment</span><span class="pp-info-val" style="color:var(--green-dark)">Open to collaborative care</span></div>
            <div style="margin-top:14px">
              <div class="pp-section-eyebrow">Primary Specialties</div>
              <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:14px">
                <span v-for="s in ['Anxiety','Depression','Trauma & PTSD','Family Systems']" :key="s" class="tag-chip">{{ s }}</span>
              </div>
              <div class="pp-section-eyebrow">Treatment Approaches</div>
              <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:14px">
                <span v-for="s in ['EMDR','IFS','CBT','Motivational Interviewing','Psychodynamic']" :key="s" class="tag-chip">{{ s }}</span>
              </div>
              <div class="pp-section-eyebrow">Population Served</div>
              <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:14px">
                <span v-for="s in ['Adults (18+)','Couples','Families','LGBTQ+ Affirming','First Responders']" :key="s" class="tag-chip">{{ s }}</span>
              </div>
              <div class="pp-section-eyebrow">Conditions Treated</div>
              <div style="display:flex;gap:5px;flex-wrap:wrap">
                <span v-for="s in ['MDD','GAD','Panic Disorder','Complex Trauma','Adjustment Disorders']" :key="s" class="tag-chip">{{ s }}</span>
              </div>
            </div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="refresh" :size="13" class="aegis-icon-gold-dark" /> Collaboration &amp; Referral Preferences</div>
            <div class="pp-info-row"><span class="pp-info-label">Referral Method</span><span class="pp-info-val">Aegis · Phone · Fax</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Preferred Handoff</span><span class="pp-info-val">Warm handoff (call ahead)</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Co-Management</span><span class="pp-info-val" style="color:var(--green-dark)">Yes — actively welcomes</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Shared Care Plan</span><span class="pp-info-val" style="color:var(--green-dark)">Shares clinical summaries</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Urgent Slots</span><span class="pp-info-val">Available · 2–3 day turnaround</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Crisis Coverage</span><span class="pp-info-val">Covered by on-call team</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Best Contact Time</span><span class="pp-info-val">10 AM – 1 PM PST</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Best Contact Method</span><span class="pp-info-val">Aegis Message</span></div>
            <div class="pp-tip-box">
              <div class="pp-tip-title"><AegisIcon name="lightbulb" :size="12" />Referral Tips</div>
              <div class="pp-tip-body">Include a brief summary when making referrals. Prefers warm handoffs when possible. Can receive shared records or documentation through secure methods.</div>
            </div>
          </div>

          <div v-if="isOwner" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="pencil" :size="13" class="aegis-icon-gold-dark" /> Profile Visibility</div>
            <p style="font-size:12px;color:var(--text-3);line-height:1.6;margin:0 0 10px">This is how your profile appears to anyone visiting <strong style="color:var(--text)">/provider/{{ user.slug }}</strong>. Your private notes, activity feed, and network connection details are <strong style="color:var(--green-dark)">never shown</strong> on the public page.</p>
            <a :href="route('provider.profile.index')" class="btn btn-outline btn-sm">Edit profile</a>
          </div>

          <div v-if="isOwner" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="pencil" :size="13" class="aegis-icon-gold-dark" /> Your Private Notes</div>
            <p style="font-size:11px;color:var(--text-4);font-style:italic;margin:0 0 10px">Visible only to you.</p>
            <div class="pp-note-box">Quarterly reminder: review insurance panel acceptance — Aetna re-credentialing window opens in March.<div class="pp-note-meta">Added Jan 12, 2025</div></div>
            <div class="pp-note-box">Update headshot before May. Current photo is from 2022.<div class="pp-note-meta">Added Nov 3, 2024</div></div>
            <textarea class="pp-note-edit" placeholder="Add a private note to yourself..."></textarea>
            <button class="btn btn-primary btn-sm" style="margin-top:8px" @click="toast.success('Note saved')">Save Note</button>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="star" :size="13" class="aegis-icon-filled aegis-icon-gold-dark" /> Peer Reviews &amp; Endorsements</div>
            <div v-for="rev in peerReviews" :key="rev.name" style="margin-bottom:12px;padding:14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm)">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                <div style="font-size:13px;font-weight:700;color:var(--text)">{{ rev.name }}</div>
                <div style="display:flex;gap:1px">
                  <AegisIcon v-for="i in rev.stars" :key="'f'+i" name="star" :size="13" class="aegis-icon-filled aegis-icon-gold-dark" />
                  <AegisIcon v-for="i in (5 - rev.stars)" :key="'e'+i" name="star" :size="13" class="aegis-icon-muted" />
                </div>
              </div>
              <div style="font-size:12px;color:var(--text-2);line-height:1.6;font-style:italic">{{ rev.quote }}</div>
              <div style="font-size:11px;color:var(--text-3);font-weight:600;margin-top:6px">{{ rev.meta }}</div>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:14px;font-size:12px;color:var(--text-3)">
              Overall Peer Rating: <strong style="color:var(--gold-dark)">{{ pm.rating ?? '4.9' }}/5.0</strong> from {{ pm.review_count ?? 62 }} reviews
              <button v-if="isLoggedIn && !isOwner" class="btn btn-outline btn-sm" @click="openEndorseModal">
                <AegisIcon name="plus" :size="13" /> Endorse
              </button>
            </div>
          </div>

        </div>

        <!-- RIGHT -->
        <div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="map-pin" :size="13" class="aegis-icon-gold-dark" /> Practice &amp; Contact</div>
            <div class="pp-info-row"><span class="pp-info-label">Practice</span><span class="pp-info-val">{{ practiceName }}</span></div>
            <div v-if="user.location" class="pp-info-row">
              <span class="pp-info-label">Location</span>
              <span class="pp-info-val"><a :href="'https://www.google.com/maps/search/?api=1&query='+encodeURIComponent(user.location)" target="_blank" rel="noopener" style="color:var(--gold-dark);text-decoration:none" data-tooltip="View on a map">{{ user.location }} <AegisIcon name="external-link" :size="11" /></a></span>
            </div>
            <div v-if="isLoggedIn && user.email" class="pp-info-row"><span class="pp-info-label">Email</span><span class="pp-info-val" style="font-size:11px"><a :href="'mailto:'+user.email">{{ user.email }}</a></span></div>
            <div v-if="isLoggedIn && user.phone" class="pp-info-row"><span class="pp-info-label">Phone</span><span class="pp-info-val">{{ user.phone }}</span></div>
            <div v-if="!isLoggedIn" class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Contact Details</span><span class="pp-info-val" style="font-size:11px;font-weight:600;color:var(--text-3)"><a :href="route('login')" class="pp-ext-link" style="padding:3px 8px">Sign in to view</a></span></div>
            <div class="pp-info-row"><span class="pp-info-label">Sessions</span><span class="pp-info-val">{{ sessionFormat }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Records Exchange</span><span class="pp-info-val">Supported through secure methods</span></div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="shield" :size="13" class="aegis-icon-gold-dark" /> Credentials &amp; Licenses</div>
            <div class="pp-info-row"><span class="pp-info-label">Credentials</span><span class="pp-info-val">{{ user.credentials ?? '—' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Title</span><span class="pp-info-val">{{ user.title ?? '—' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Specialty</span><span class="pp-info-val">{{ user.specialty ?? '—' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">State Licenses</span><span class="pp-info-val">{{ pmCreds.state_licenses ?? 'CA · NY · WA' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Telehealth States</span><span class="pp-info-val">{{ pmCreds.telehealth_states ?? 'CA · NY · WA · OR · NV' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Education</span><span class="pp-info-val">{{ pmCreds.education ?? 'UCSF · Stanford' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">License Status</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmCreds.license_status ?? 'Active · Expires Dec 2026' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Aegis Verified</span><span class="pp-info-val" :style="user.verified ? 'color:var(--green-dark)' : 'color:var(--text-4)'">{{ user.verified ? 'Verified' : 'Not Verified' }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Malpractice</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmCreds.malpractice ?? 'Verified' }}</span></div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="credit-card" :size="13" class="aegis-icon-gold-dark" /> Insurance &amp; Rates</div>
            <div class="pp-section-eyebrow">Accepted Insurance</div>
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:14px">
              <span v-for="panel in insurancePanels" :key="panel" class="tag-chip">{{ panel }}</span>
            </div>
            <div class="pp-info-row"><span class="pp-info-label">Self-Pay Rate</span><span class="pp-info-val">{{ pmFees.self_pay_rate ?? '$200 / 50 min' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Follow-Up Rate</span><span class="pp-info-val">{{ pmFees.follow_up_rate ?? '$160 / 30 min' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Telehealth Rate</span><span class="pp-info-val">$180 / 45 min</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Sliding Scale</span><span class="pp-info-val" style="color:var(--green-dark)">Available</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Superbill</span><span class="pp-info-val" style="color:var(--green-dark)">Available</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Telehealth</span><span class="pp-info-val" style="color:var(--green-dark)">All sessions available</span></div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="globe" :size="13" class="aegis-icon-gold-dark" /> Languages &amp; Cultural Competencies</div>
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:12px">
              <span class="tag-chip">English</span><span class="tag-chip">Spanish</span>
            </div>
            <div class="pp-section-eyebrow">Cultural Competencies</div>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <span v-for="c in ['LGBTQ+ Affirming','Trauma-Informed','Multicultural','Faith-Sensitive']" :key="c" class="pn-tag">{{ c }}</span>
            </div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="calendar" :size="13" class="aegis-icon-gold-dark" /> Availability &amp; Scheduling</div>
            <div class="pp-avail-grid">
              <div v-for="day in availDays" :key="day.label" :class="['pp-avail-day', day.open ? 'open' : 'closed']">
                {{ day.label }}<span class="pp-avail-time">{{ day.time }}</span>
              </div>
            </div>
            <div style="margin-top:12px;padding:10px 12px;background:var(--badge-bg-gold);border:1px solid var(--fade-gold);border-radius:var(--radius-sm)">
              <div style="font-size:12px;font-weight:700;color:var(--gold-dark)">Next available: {{ pmSchedule.next_available ?? 'Feb 24 · 10:00 AM' }}</div>
              <div style="font-size:11px;color:var(--text-3);margin-top:2px">{{ pmSchedule.waitlist_note ?? '~1–2 wk waitlist for new clients' }}</div>
            </div>
            <div class="pp-info-row" style="margin-top:10px"><span class="pp-info-label">New Client Wait</span><span class="pp-info-val">{{ pmSchedule.new_client_wait ?? '~1–2 weeks' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Urgent Slots</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmSchedule.urgent_slots ?? '2–3 day turnaround' }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Online Scheduling</span><span class="pp-info-val" style="color:var(--green-dark)">Available</span></div>
            <div v-if="isLoggedIn && !isOwner" class="pp-action-row">
              <button class="btn-icon" data-tooltip="View calendar" @click="toast.info('Opening calendar...')"><AegisIcon name="calendar" :size="14" /></button>
              <button class="btn btn-primary btn-sm" @click="openServiceRequest('Appointment')">Request Slot</button>
            </div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="globe" :size="13" class="aegis-icon-gold-dark" /> Provider Identity</div>
            <div class="pp-info-row"><span class="pp-info-label">Pronouns</span><span class="pp-info-val">{{ pmIdentity.pronouns ?? 'He/Him' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Ethnicity</span><span class="pp-info-val">{{ pmIdentity.ethnicity ?? 'South Asian' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">LGBTQ+ Affirming</span><span class="pp-info-val" style="color:var(--green-dark)">Yes</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Faith-Sensitive Care</span><span class="pp-info-val" style="color:var(--green-dark)">Available</span></div>
            <div class="pp-info-row"><span class="pp-info-label">ADA Accessible</span><span class="pp-info-val" style="color:var(--green-dark)">Fully Accessible</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Gender Affirming</span><span class="pp-info-val" style="color:var(--green-dark)">Yes</span></div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="award" :size="13" class="aegis-icon-gold-dark" /> Affiliations &amp; Memberships</div>
            <div class="pp-info-row"><span class="pp-info-label">Hospital Affil.</span><span class="pp-info-val">UCSF Medical Center</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Academic Appt.</span><span class="pp-info-val">Adj. Faculty, UCSF Psychiatry</span></div>
            <div class="pp-info-row"><span class="pp-info-label">APA Member</span><span class="pp-info-val" style="color:var(--green-dark)">Yes</span></div>
            <div class="pp-info-row"><span class="pp-info-label">EMDRIA Certified</span><span class="pp-info-val" style="color:var(--green-dark)">Yes</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Group Practice</span><span class="pp-info-val">{{ practiceName }}</span></div>
            <div class="pp-section-eyebrow" style="margin-top:14px">Research &amp; Publications</div>
            <div style="font-size:12px;color:var(--text-2);line-height:1.6">14 peer-reviewed publications. Focus areas: trauma-informed care, EMDR outcomes, and integrated mental-health care models.</div>
          </div>

          <div v-if="isLoggedIn && !isOwner" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="link" :size="13" class="aegis-icon-gold-dark" /> Connection Info</div>
            <div class="pp-info-row"><span class="pp-info-label">Connected Since</span><span class="pp-info-val">{{ pmConn.connected_since ?? 'September 2023' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Connection Type</span><span class="pp-info-val">{{ pmConn.connection_type ?? 'Mutual (Both Accepted)' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Last Interaction</span><span class="pp-info-val">{{ pmConn.last_interaction ?? 'Feb 10, 2025' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Mutual Connections</span><span class="pp-info-val">{{ pmConn.mutual_connections ?? 7 }} shared in network</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Profile Completeness</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pmConn.profile_completeness ?? '98%' }} Complete</span></div>
            <div class="pp-action-row">
              <button class="btn-icon btn-icon-danger" data-tooltip="Remove from network" @click="confirmRemoveFromNetwork"><AegisIcon name="trash" :size="14" /></button>
            </div>
          </div>
          <div v-else-if="!isLoggedIn" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="lock" :size="13" class="aegis-icon-gold-dark" /> Connection Info</div>
            <p style="font-size:12px;color:var(--text-3);line-height:1.6;margin:0 0 10px">See when you connected, your interaction history, and mutual connections in your network.</p>
            <a :href="route('login')" class="btn btn-outline btn-sm">Sign in to view</a>
          </div>

        </div>
      </div>

    </div>

    <!-- ═══ MODALS (logged-in non-owner only) ═══ -->
    <template v-if="isLoggedIn && !isOwner">

      <AegisModal v-model="showServiceRequestModal" title="Request a Service" subtitle="Request an appointment or specific service from this provider" size="md">
        <div class="pp-tip-box" style="margin-top:0;display:flex;gap:10px;align-items:flex-start">
          <span style="flex-shrink:0;margin-top:1px;display:inline-flex;align-items:center;line-height:0"><AegisIcon name="info" :size="16" class="aegis-icon-gold-dark" /></span>
          <div class="pp-tip-body">Service requests are sent securely through Aegis. You'll receive a confirmation once the provider responds.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Service</label>
          <input type="text" class="form-input" :value="serviceRequestForm.service" readonly>
        </div>
        <div class="form-group">
          <label class="form-label">From Provider</label>
          <input type="text" class="form-input" :value="user.display_name + (user.credentials ? ', ' + user.credentials : '')" readonly>
        </div>
        <div class="grid-2" style="gap:12px">
          <div class="form-group">
            <label class="form-label">Preferred Date <span class="req">*</span></label>
            <input type="date" class="form-input" v-model="serviceRequestForm.date">
          </div>
          <div class="form-group">
            <label class="form-label">Preferred Time</label>
            <select class="form-select" v-model="serviceRequestForm.time">
              <option>Morning (9am–12pm)</option><option>Afternoon (12–5pm)</option><option>Evening (5–8pm)</option><option>Flexible</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Format</label>
          <select class="form-select" v-model="serviceRequestForm.format">
            <option>Telehealth</option><option>In-Person</option><option>No preference</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes for Provider <span style="color:var(--text-4);font-weight:500">(optional)</span></label>
          <textarea class="form-textarea" rows="3" placeholder="Briefly describe what you'd like to discuss…" v-model="serviceRequestForm.notes"></textarea>
        </div>
        <template #footer>
          <button class="btn btn-outline" @click="showServiceRequestModal = false">Cancel</button>
          <button class="btn btn-primary" @click="submitServiceRequest"><span class="btn-ico"><AegisIcon name="send" :size="13" /></span>Send Request</button>
        </template>
      </AegisModal>

      <AegisModal v-model="showEndorseModal" title="Endorse This Practitioner" subtitle="Share your experience to help colleagues evaluate fit" size="md">
        <div class="form-group">
          <label class="form-label">Practitioner</label>
          <input type="text" class="form-input" :value="user.display_name + (user.credentials ? ', ' + user.credentials : '')" readonly>
        </div>
        <div class="form-group">
          <label class="form-label">Your Rating <span class="req">*</span></label>
          <div class="rating-stars" role="radiogroup" aria-label="Star rating">
            <button v-for="i in 5" :key="i" type="button" :class="['rating-star', endorseForm.rating >= i ? 'is-on' : '']" @click="endorseForm.rating = i" :aria-label="i + (i === 1 ? ' star' : ' stars')"><AegisIcon name="star" :size="22" /></button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Headline <span style="color:var(--text-4);font-weight:500">(optional)</span></label>
          <input type="text" class="form-input" placeholder="e.g. Responsive, thorough, easy referral partner" maxlength="80" v-model="endorseForm.headline">
        </div>
        <div class="form-group">
          <label class="form-label">Your Endorsement <span class="req">*</span></label>
          <textarea class="form-textarea" rows="4" placeholder="What stood out about this practitioner? How were they to work with?" maxlength="600" v-model="endorseForm.body"></textarea>
          <div class="form-hint">Visible to other practitioners on this profile · 600 chars max</div>
        </div>
        <div class="form-group">
          <label class="form-label">Connection Context</label>
          <select class="form-select" v-model="endorseForm.context">
            <option>Referral exchange</option><option>Co-managed client</option><option>Peer consultation</option><option>Supervision</option><option>Other</option>
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
import PublicLayout from '@/layouts/PublicLayout.vue'
import AegisModal from '@/components/ui/AegisModal.vue'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const props = defineProps({
  user:       { type: Object,  required: true },
  viewerRole: { type: String,  default: null },
  isOwner:    { type: Boolean, default: false },
  isLoggedIn: { type: Boolean, default: false },
})

const toast = useToast()
const { confirmAction } = useConfirm()

const pm         = computed(() => props.user.profile_meta ?? {})
const pmStats    = computed(() => pm.value.stats ?? {})
const pmSchedule = computed(() => pm.value.schedule ?? {})
const pmIdentity = computed(() => pm.value.identity ?? {})
const pmCreds    = computed(() => pm.value.credentials ?? {})
const pmFees     = computed(() => pm.value.fees ?? {})
const pmConn     = computed(() => pm.value.connection ?? {})

const specialtyLabel    = computed(() => props.user.specialty ?? 'Clinical Practitioner')
const practiceName      = computed(() => props.user.organization ?? 'Independent Practice')
const sessionFormat     = computed(() => pmSchedule.value.session_format ?? 'In-Person & Telehealth')
const acceptsTelehealth = computed(() => sessionFormat.value.toLowerCase().includes('telehealth'))
const avatarInitials    = computed(() => props.user.avatar_initials ?? props.user.display_name?.slice(0, 2).toUpperCase() ?? '??')
const insurancePanels   = computed(() => pm.value.insurance_panels ?? ['BCBS', 'Aetna', 'Cigna', 'United', 'Medicare'])
const showRatings       = computed(() => true)
const showRefStats      = computed(() => true)
const showDemographics  = computed(() => true)

const availDays = [
  { label: 'Mon', time: '9–6', open: true },
  { label: 'Tue', time: '9–6', open: true },
  { label: 'Wed', time: '9–6', open: true },
  { label: 'Thu', time: '9–6', open: true },
  { label: 'Fri', time: '9–3', open: true },
  { label: 'Sat', time: '—',  open: false },
  { label: 'Sun', time: '—',  open: false },
]

const peerReviews = [
  { name: 'Dr. Sarah Chen, LCSW', stars: 5, quote: 'One of the most collaborative providers I\'ve worked with. Always keeps me in the loop on co-managed clients.', meta: 'Therapist · Connected since Feb 2024' },
  { name: 'Dr. James Okafor, MD', stars: 5, quote: 'Exceptional with treatment-resistant cases. My clients report feeling truly heard. Strong recommendation for complex needs.', meta: 'Primary Care · Connected since Aug 2024' },
  { name: 'Dr. Priya Nair, PhD',  stars: 4, quote: 'Very responsive via Aegis. Occasionally hard to reach by phone, but messages always get a same-day response.', meta: 'Neuropsychologist · Connected since Oct 2024' },
]

const providerServices = [
  { name: 'Individual Supervision', price: '$150', unit: '/hr',      desc: 'One-on-one clinical supervision for licensed or pre-licensed therapists. Case consultation, skill development, and licensure support.', duration: '60 min', format: 'Telehealth or In-Person', avail: 'open',    availLabel: 'Slots Available' },
  { name: 'Peer Consultation',      price: '$120', unit: '/hr',      desc: 'Professional peer consultation on complex or challenging clinical cases. Collaborative, non-evaluative.',                                   duration: '60 min', format: 'Telehealth',          avail: 'open',    availLabel: 'Slots Available' },
  { name: 'Group Supervision',      price: '$65',  unit: '/person',  desc: 'Small-group format (3–6 providers). Weekly cohorts available. Ideal for group practices or training programs.',                       duration: '90 min', format: 'Telehealth',          avail: 'limited', availLabel: 'Limited Spots'  },
  { name: 'Training Workshop',      price: '$200', unit: '/session', desc: 'Structured psychoeducation or skills training for provider teams. Topics include trauma-informed care and co-management frameworks.', duration: '2 hr',   format: 'In-Person or Virtual', avail: 'limited', availLabel: 'By Request'     },
]

const showServiceRequestModal = ref(false)
const showEndorseModal        = ref(false)
const serviceRequestForm      = ref({ service: '', date: '', time: 'Flexible', format: 'Telehealth', notes: '' })
const endorseForm             = ref({ rating: 0, headline: '', body: '', context: 'Referral exchange' })

function openServiceRequest(serviceName) {
  serviceRequestForm.value.service = serviceName
  showServiceRequestModal.value = true
}
function openReferralModal() { toast.info('Opening referral form…') }
function openEndorseModal() {
  endorseForm.value = { rating: 0, headline: '', body: '', context: 'Referral exchange' }
  showEndorseModal.value = true
}
function submitServiceRequest() { showServiceRequestModal.value = false; toast.success('Service request sent') }
function submitEndorse() { showEndorseModal.value = false; toast.success('Endorsement submitted') }
function copyShareLink() {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(window.location.href)
      .then(() => toast.success('Profile link copied'))
      .catch(() => toast.error('Could not copy link'))
  } else {
    toast.success('Profile link copied')
  }
}
function confirmRemoveFromNetwork() {
  confirmAction('Remove this practitioner from your network? Active referrals will remain accessible.', () => toast.error('Removed from network'), { title: 'Remove from Network', btnLabel: 'Remove', type: 'danger' })
}
</script>

<style scoped>
.public-profile-wrap { max-width: 960px; margin: 0 auto; padding: var(--space-6) var(--space-4); }
.pp-stat-row { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; margin: 18px 0; }
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
  .pp-stat-row { grid-template-columns: repeat(3, 1fr); }
  .hero-banner.is-quiet .hero-badges,
  .hero-banner.is-quiet > .hero-meta { flex-wrap: wrap; overflow-x: visible; }
}
</style>
