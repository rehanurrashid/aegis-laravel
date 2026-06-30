<!--
  pages/public/ProviderProfile.vue
  PHP parity: public/provider.php

  Viewer tiers:
    Anonymous    — hero, stats, all public sections; no contact, no connection info
    Logged-in    — + contact details, service requests, endorse, connection info
    Owner        — + edit button, profile visibility, private notes
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
              <div class="page-hero-eyebrow">Practitioner Profile</div>
              <h1 class="page-hero-title">{{ user.display_name }}{{ user.credentials ? ', ' + user.credentials : '' }}</h1>
              <div class="page-hero-sub">{{ specialtyLabel }} &nbsp;·&nbsp; {{ practiceName }}</div>
              <div class="hero-badges">
                <span v-if="pm.accepting_clients !== false" class="badge badge-green">
                  <span class="badge-dot"></span>Accepting Referrals
                </span>
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
            <!-- Owner -->
            <template v-if="isOwner">
              <a :href="route('provider.profile.index')" class="btn-hero-solid is-on-light">
                <AegisIcon name="pencil" :size="14" /> Edit
              </a>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only"
                      @click="copyShareLink" data-tooltip="Copy link" aria-label="Copy link">
                <AegisIcon name="link" :size="14" />
              </button>
            </template>

            <!-- Logged-in non-owner -->
            <template v-else-if="isLoggedIn">
              <button type="button" class="btn-hero-solid is-on-light" @click="openReferral">
                <AegisIcon name="refresh" :size="14" /> Refer
              </button>
              <button type="button" class="btn-hero-ghost is-on-light" @click="openServiceRequest('Appointment')">
                <AegisIcon name="calendar" :size="14" /> Schedule
              </button>
              <a :href="route('messages.index') + '?to=' + user.id"
                 class="btn-hero-ghost is-on-light is-icon-only" data-tooltip="Message" aria-label="Message">
                <AegisIcon name="message" :size="14" />
              </a>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only"
                      @click="copyShareLink" data-tooltip="Share" aria-label="Share">
                <AegisIcon name="link" :size="14" />
              </button>
            </template>

            <!-- Anonymous -->
            <template v-else>
              <a :href="route('login')" class="btn-hero-solid is-on-light">
                <AegisIcon name="message" :size="14" /> Sign In
              </a>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only"
                      @click="copyShareLink" data-tooltip="Share" aria-label="Share">
                <AegisIcon name="link" :size="14" />
              </button>
            </template>
          </div>
        </div>

        <div class="hero-meta">
          <a v-if="user.location" class="hero-meta-item"
             :href="'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(user.location)"
             target="_blank" rel="noopener" data-tooltip="View on a map" style="text-decoration:none;color:inherit">
            <AegisIcon name="map-pin" :size="12" />{{ user.location }}
          </a>
          <span v-if="pm.show_ratings && pm.rating" class="hero-meta-item is-rating">
            <AegisIcon name="star" :size="12" class="aegis-icon-filled" />
            {{ pm.rating }} <span class="rating-count">({{ pm.review_count }})</span>
          </span>
          <span v-if="pm.show_ref_stats" class="hero-meta-item">
            <AegisIcon name="refresh" :size="12" />{{ pm.stats.avg_response }} response
          </span>
          <span class="hero-meta-item"><AegisIcon name="monitor" :size="12" />{{ sessionFormat }}</span>
          <span v-if="pm.show_demographics && pm.languages?.length" class="hero-meta-item">
            <AegisIcon name="globe" :size="12" />{{ pm.languages.join(' · ') }}
          </span>
          <span v-if="pm.show_demographics && pm.identity?.pronouns" class="hero-meta-item">
            <AegisIcon name="user" :size="12" />{{ pm.identity.pronouns }}
          </span>
        </div>
      </div>

      <!-- ═══ STAT CHIPS ═══ -->
      <div class="stat-chips-row">
        <template v-if="pm.show_ref_stats">
          <AegisStatChip icon="share-tree" :value="pm.stats.referrals_exchanged" label="Referrals Exchanged" />
          <AegisStatChip icon="check" :value="pm.stats.acceptance_rate" label="Acceptance Rate" />
          <AegisStatChip icon="clock" :value="pm.stats.avg_response" label="Avg Response" />
        </template>
        <AegisStatChip v-if="pm.show_ratings && pm.rating" icon="star" :value="pm.rating" label="Overall Rating" />
        <AegisStatChip icon="users-network" :value="pm.stats.mutual_connections" label="Mutual Connections" />
        <AegisStatChip icon="user-check" :value="pm.stats.client_slots" label="Client Slots" />
      </div>

      <!-- ═══ SERVICES PANEL (services_mode only) ═══ -->
      <div v-if="user.services_mode && services.length" class="pp-svc-section">
        <div class="pp-svc-header">
          <div class="pp-svc-header-title">
            <AegisIcon name="briefcase" :size="16" class="aegis-icon-gold-dark" />Services
          </div>
          <span class="pp-svc-header-badge">Provider Services Mode</span>
        </div>
        <div class="pp-svc-body">
          <div class="pp-svc-intro">
            This provider offers licensed professional services to other providers — including supervision,
            consultation, and training. Provider-to-provider engagements, not client-facing services.
          </div>
          <div class="pp-svc-grid">
            <div v-for="svc in servicesWithLabels" :key="svc.id" class="pp-svc-card">
              <div class="pp-svc-card-top">
                <div class="pp-svc-card-name">{{ svc.title }}</div>
                <div class="pp-svc-card-price">{{ svc.rateAmount }}<span v-if="svc.rateUnit">{{ svc.rateUnit }}</span></div>
              </div>
              <div class="pp-svc-card-desc">{{ svc.description }}</div>
              <div class="pp-svc-card-meta">
                <span v-if="svc.duration_min"><AegisIcon name="clock" :size="11" />{{ svc.duration_min }} min</span>
                <span v-if="svc.format"><AegisIcon name="monitor" :size="11" />{{ formatLabel(svc.format) }}</span>
              </div>
              <div class="pp-svc-card-footer">
                <span class="pp-svc-card-avail" :class="svc.availability ?? 'open'">
                  <AegisIcon name="circle-dot" :size="9" class="aegis-icon-filled" />{{ svc.availability_label || ((svc.availability ?? 'open') === 'limited' ? 'Limited Spots' : 'Slots Available') }}
                </span>
                <template v-if="!isOwner">
                  <button v-if="isLoggedIn" class="btn btn-outline btn-sm"
                          @click="openServiceRequest(svc.title)">Request</button>
                  <a v-else :href="route('login')" class="btn btn-outline btn-sm">Sign in to Request</a>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ TWO-COLUMN GRID ═══ -->
      <div class="pp-grid">

        <!-- ─── LEFT ─── -->
        <div>

          <!-- About -->
          <div class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="user" :size="13" class="aegis-icon-gold-dark" />
              About {{ user.display_name }}
            </div>
            <p style="font-size:13px;color:var(--text-2);line-height:1.7;margin:0 0 12px">
              {{ user.bio ?? 'Bio not yet provided.' }}
            </p>
            <p v-if="pm.about_me_extended" style="font-size:13px;color:var(--text-2);line-height:1.7;margin:0 0 12px">
              {{ pm.about_me_extended }}
            </p>
            <p v-else style="font-size:13px;color:var(--text-2);line-height:1.7;margin:0 0 12px">
              An active Aegis network member, known for rapid response times and highly collaborative referral
              practices. Open to co-management and shared care plans across primary care, behavioral health,
              and specialty referrals.
            </p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
              <a href="#" class="pp-ext-link"><AegisIcon name="briefcase" :size="11" />LinkedIn</a>
              <a href="#" class="pp-ext-link"><AegisIcon name="globe" :size="11" />Practice Website</a>
              <a v-if="pm.affiliations?.publications" href="#" class="pp-ext-link">
                <AegisIcon name="clipboard" :size="11" />Publications ({{ pm.affiliations.publications }})
              </a>
            </div>
          </div>

          <!-- Specialties & Clinical Focus -->
          <div class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="clipboard" :size="13" class="aegis-icon-gold-dark" />
              Specialties &amp; Clinical Focus
            </div>
            <div class="pp-info-row">
              <span class="pp-info-label">Provider Type</span>
              <span class="pp-info-val">{{ user.credentials ?? '—' }} · {{ specialtyLabel }}</span>
            </div>
            <div class="pp-info-row">
              <span class="pp-info-label">Years in Practice</span>
              <span class="pp-info-val">{{ pm.years_in_practice ? pm.years_in_practice + '+ years' : '—' }}</span>
            </div>
            <div class="pp-info-row">
              <span class="pp-info-label">Practice Setting</span>
              <span class="pp-info-val">{{ practiceName }}</span>
            </div>
            <div class="pp-info-row">
              <span class="pp-info-label">Session Format</span>
              <span class="pp-info-val">{{ sessionFormat }}</span>
            </div>
            <div class="pp-info-row">
              <span class="pp-info-label">Session Length</span>
              <span class="pp-info-val">{{ pm.schedule?.session_length ?? '50 min standard' }}</span>
            </div>
            <div class="pp-info-row" style="border-bottom:none">
              <span class="pp-info-label">Co-Treatment</span>
              <span class="pp-info-val" style="color:var(--green-dark)">Open to collaborative care</span>
            </div>

            <div style="margin-top:14px" v-if="pm.specialties?.length || pm.approaches?.length">
              <template v-if="pm.specialties?.length">
                <div class="pp-section-eyebrow">Primary Specialties</div>
                <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:14px">
                  <span v-for="s in pm.specialties" :key="s" class="tag-chip">{{ s }}</span>
                </div>
              </template>
              <template v-if="pm.approaches?.length">
                <div class="pp-section-eyebrow">Treatment Approaches</div>
                <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:14px">
                  <span v-for="s in pm.approaches" :key="s" class="tag-chip">{{ s }}</span>
                </div>
              </template>
              <template v-if="pm.population_served?.length">
                <div class="pp-section-eyebrow">Population Served</div>
                <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:14px">
                  <span v-for="s in pm.population_served" :key="s" class="tag-chip">{{ s }}</span>
                </div>
              </template>
              <template v-if="pm.conditions?.length">
                <div class="pp-section-eyebrow">Conditions Treated</div>
                <div style="display:flex;gap:5px;flex-wrap:wrap">
                  <span v-for="s in pm.conditions" :key="s" class="tag-chip">{{ s }}</span>
                </div>
              </template>
            </div>
          </div>

          <!-- Collaboration & Referral Preferences -->
          <div class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="refresh" :size="13" class="aegis-icon-gold-dark" />
              Collaboration &amp; Referral Preferences
            </div>
            <div class="pp-info-row"><span class="pp-info-label">Referral Method</span><span class="pp-info-val">{{ collab.referral_method }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Preferred Handoff</span><span class="pp-info-val">{{ collab.preferred_handoff }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Co-Management</span><span class="pp-info-val" style="color:var(--green-dark)">{{ collab.co_management }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Shared Care Plan</span><span class="pp-info-val" style="color:var(--green-dark)">{{ collab.shared_care_plan }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Urgent Slots</span><span class="pp-info-val">{{ pm.schedule?.urgent_slots }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Crisis Coverage</span><span class="pp-info-val">{{ collab.crisis_coverage }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Best Contact Time</span><span class="pp-info-val">{{ collab.best_contact_time }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Best Contact Method</span><span class="pp-info-val">{{ collab.best_contact_method }}</span></div>
            <div class="pp-tip-box">
              <div class="pp-tip-title"><AegisIcon name="lightbulb" :size="12" />Referral Tips</div>
              <div class="pp-tip-body">{{ collab.referral_tips }}</div>
            </div>
          </div>

          <!-- Owner: Profile Visibility -->
          <div v-if="isOwner" class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="pencil" :size="13" class="aegis-icon-gold-dark" /> Profile Visibility
            </div>
            <p style="font-size:12px;color:var(--text-3);line-height:1.6;margin:0 0 10px">
              This is how your profile appears to anyone visiting
              <strong style="color:var(--text)">/provider/{{ user.slug }}</strong>.
              Your private notes, activity feed, and network connection details are
              <strong style="color:var(--green-dark)">never shown</strong> on the public page.
            </p>
            <a :href="route('provider.profile.index')" class="btn btn-outline btn-sm">Edit profile</a>
          </div>

          <!-- Owner: Private Notes -->
          <div v-if="isOwner" class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="pencil" :size="13" class="aegis-icon-gold-dark" /> Your Private Notes
            </div>
            <p style="font-size:11px;color:var(--text-4);font-style:italic;margin:0 0 10px">
              Visible only to you.
            </p>
            <div class="pp-note-box">
              Quarterly reminder: review insurance panel acceptance — Aetna re-credentialing window opens in March.
              <div class="pp-note-meta">Added Jan 12, 2025</div>
            </div>
            <div class="pp-note-box">
              Update headshot before May. Current photo is from 2022.
              <div class="pp-note-meta">Added Nov 3, 2024</div>
            </div>
            <textarea class="pp-note-edit" placeholder="Add a private note to yourself..." v-model="privateNote"></textarea>
            <button class="btn btn-primary btn-sm" style="margin-top:8px" @click="saveNote">Save Note</button>
          </div>

          <!-- Peer Reviews & Endorsements -->
          <div class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="star" :size="13" class="aegis-icon-filled aegis-icon-gold-dark" />
              Peer Reviews &amp; Endorsements
            </div>
            <template v-if="pm.reviews?.length">
              <div v-for="rev in pm.reviews" :key="rev.name"
                   style="margin-bottom:12px;padding:14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm)">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                  <div style="font-size:13px;font-weight:700;color:var(--text)">{{ rev.name }}</div>
                  <div style="display:flex;gap:1px">
                    <AegisIcon v-for="i in (rev.stars ?? 5)" :key="'f'+i" name="star" :size="13" class="aegis-icon-filled aegis-icon-gold-dark" />
                    <AegisIcon v-for="i in (5 - (rev.stars ?? 5))" :key="'e'+i" name="star" :size="13" class="aegis-icon-muted" />
                  </div>
                </div>
                <div style="font-size:12px;color:var(--text-2);line-height:1.6;font-style:italic">"{{ rev.quote }}"</div>
                <div style="font-size:11px;color:var(--text-3);font-weight:600;margin-top:6px">{{ rev.meta }}</div>
              </div>
            </template>
            <template v-else>
              <!-- Fallback static reviews when no dynamic reviews seeded -->
              <div v-for="rev in staticReviews" :key="rev.name"
                   style="margin-bottom:12px;padding:14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm)">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                  <div style="font-size:13px;font-weight:700;color:var(--text)">{{ rev.name }}</div>
                  <div style="display:flex;gap:1px">
                    <AegisIcon v-for="i in rev.stars" :key="'f'+i" name="star" :size="13" class="aegis-icon-filled aegis-icon-gold-dark" />
                    <AegisIcon v-for="i in (5 - rev.stars)" :key="'e'+i" name="star" :size="13" class="aegis-icon-muted" />
                  </div>
                </div>
                <div style="font-size:12px;color:var(--text-2);line-height:1.6;font-style:italic">"{{ rev.quote }}"</div>
                <div style="font-size:11px;color:var(--text-3);font-weight:600;margin-top:6px">{{ rev.meta }}</div>
              </div>
            </template>
            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:14px;font-size:12px;color:var(--text-3)">
              <span>Overall Peer Rating: <strong style="color:var(--gold-dark)">{{ pm.rating ?? '4.9' }}/5.0</strong> from {{ pm.review_count ?? 62 }} reviews</span>
              <button v-if="isLoggedIn && !isOwner" class="btn btn-outline btn-sm" @click="showEndorseModal = true">
                <AegisIcon name="plus" :size="13" /> Endorse
              </button>
            </div>
          </div>

        </div>

        <!-- ─── RIGHT ─── -->
        <div>

          <!-- Practice & Contact -->
          <div class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="map-pin" :size="13" class="aegis-icon-gold-dark" /> Practice &amp; Contact
            </div>
            <div class="pp-info-row"><span class="pp-info-label">Practice</span><span class="pp-info-val">{{ practiceName }}</span></div>
            <div v-if="user.location" class="pp-info-row">
              <span class="pp-info-label">Location</span>
              <span class="pp-info-val">
                <a :href="'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(user.location)"
                   target="_blank" rel="noopener" style="color:var(--gold-dark);text-decoration:none;display:inline-flex;align-items:center;gap:4px;white-space:nowrap"
                   data-tooltip="View on a map">
                  {{ user.location }} <AegisIcon name="external-link" :size="11" />
                </a>
              </span>
            </div>
            <!-- Contact details: logged-in only -->
            <template v-if="isLoggedIn">
              <div v-if="user.email" class="pp-info-row">
                <span class="pp-info-label">Email</span>
                <span class="pp-info-val"><a :href="'mailto:' + user.email">{{ user.email }}</a></span>
              </div>
              <div v-if="user.phone" class="pp-info-row">
                <span class="pp-info-label">Phone</span>
                <span class="pp-info-val">{{ user.phone }}</span>
              </div>
            </template>
            <template v-else>
              <div class="pp-info-row">
                <span class="pp-info-label">Contact Details</span>
                <span class="pp-info-val" style="font-weight:600;color:var(--text-3)">
                  <a :href="route('login')" class="pp-ext-link" style="padding:3px 8px">Sign in to view</a>
                </span>
              </div>
            </template>
            <div class="pp-info-row"><span class="pp-info-label">Sessions</span><span class="pp-info-val">{{ sessionFormat }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Records Exchange</span><span class="pp-info-val">Supported through secure methods</span></div>
          </div>

          <!-- Credentials & Licenses -->
          <div class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="shield" :size="13" class="aegis-icon-gold-dark" /> Credentials &amp; Licenses
            </div>
            <div class="pp-info-row"><span class="pp-info-label">Credentials</span><span class="pp-info-val">{{ user.credentials ?? '—' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Title</span><span class="pp-info-val">{{ user.title ?? '—' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Specialty</span><span class="pp-info-val">{{ user.specialty ?? '—' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">State Licenses</span><span class="pp-info-val">{{ creds.state_licenses }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Telehealth States</span><span class="pp-info-val">{{ creds.telehealth_states }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Education</span><span class="pp-info-val">{{ creds.education }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">License Status</span><span class="pp-info-val" style="color:var(--green-dark)">{{ creds.license_status }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Aegis Verified</span><span class="pp-info-val" :style="user.verified ? 'color:var(--green-dark)' : 'color:var(--text-4)'">{{ user.verified ? 'Verified' : 'Not Verified' }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Malpractice</span><span class="pp-info-val" style="color:var(--green-dark)">{{ creds.malpractice }}</span></div>
          </div>

          <!-- Insurance & Rates -->
          <div class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="credit-card" :size="13" class="aegis-icon-gold-dark" /> Insurance &amp; Rates
            </div>
            <div class="pp-section-eyebrow">Accepted Insurance</div>
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:14px">
              <span v-for="panel in pm.insurance_panels" :key="panel" class="tag-chip">{{ panel }}</span>
            </div>
            <div class="pp-info-row"><span class="pp-info-label">Self-Pay Rate</span><span class="pp-info-val">{{ fees.self_pay_rate }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Follow-Up Rate</span><span class="pp-info-val">{{ fees.follow_up_rate }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Telehealth Rate</span><span class="pp-info-val">{{ fees.telehealth_rate }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Sliding Scale</span><span class="pp-info-val" :style="fees.sliding_scale ? 'color:var(--green-dark)' : ''">{{ fees.sliding_scale ? 'Available' : 'Not Available' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Superbill</span><span class="pp-info-val" :style="fees.superbill ? 'color:var(--green-dark)' : ''">{{ fees.superbill ? 'Available' : 'Not Available' }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Telehealth</span><span class="pp-info-val" style="color:var(--green-dark)">All sessions available</span></div>
          </div>

          <!-- Languages & Cultural -->
          <div class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="globe" :size="13" class="aegis-icon-gold-dark" /> Languages &amp; Cultural Competencies
            </div>
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:12px">
              <span v-for="lang in pm.languages" :key="lang" class="tag-chip">{{ lang }}</span>
            </div>
            <div class="pp-section-eyebrow">Cultural Competencies</div>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <span v-for="c in culturalCompetencies" :key="c" class="pn-tag">{{ c }}</span>
            </div>
          </div>

          <!-- Availability & Scheduling -->
          <div class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="calendar" :size="13" class="aegis-icon-gold-dark" /> Availability &amp; Scheduling
            </div>
            <div class="pp-avail-grid">
              <div v-for="(time, day) in schedule.availability" :key="day"
                   :class="['pp-avail-day', time ? 'open' : 'closed']">
                {{ day }}<span class="pp-avail-time">{{ time ?? '—' }}</span>
              </div>
            </div>
            <div style="margin-top:12px;padding:10px 12px;background:var(--badge-bg-gold);border:1px solid var(--fade-gold);border-radius:var(--radius-sm)">
              <div style="font-size:12px;font-weight:700;color:var(--gold-dark)">Next available: {{ schedule.next_available }}</div>
              <div style="font-size:11px;color:var(--text-3);margin-top:2px">{{ schedule.waitlist_note }}</div>
            </div>
            <div class="pp-info-row" style="margin-top:10px"><span class="pp-info-label">New Client Wait</span><span class="pp-info-val">{{ schedule.new_client_wait }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Urgent Slots</span><span class="pp-info-val" style="color:var(--green-dark)">{{ schedule.urgent_slots }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Online Scheduling</span><span class="pp-info-val" :style="schedule.online_scheduling ? 'color:var(--green-dark)' : 'color:var(--text-4)'">{{ schedule.online_scheduling ? 'Available' : 'Not Available' }}</span></div>
            <div v-if="isLoggedIn && !isOwner" class="pp-action-row">
              <button class="btn-icon" data-tooltip="View calendar" @click="toast.info('Opening calendar...')">
                <AegisIcon name="calendar" :size="14" />
              </button>
              <button class="btn btn-primary btn-sm" @click="openServiceRequest('Appointment')">Request Slot</button>
            </div>
          </div>

          <!-- Provider Identity -->
          <div class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="globe" :size="13" class="aegis-icon-gold-dark" /> Provider Identity
            </div>
            <div class="pp-info-row"><span class="pp-info-label">Pronouns</span><span class="pp-info-val">{{ identity.pronouns }}</span></div>
            <div v-if="identity.ethnicity" class="pp-info-row"><span class="pp-info-label">Ethnicity</span><span class="pp-info-val">{{ identity.ethnicity }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">LGBTQ+ Affirming</span><span class="pp-info-val" :style="identity.lgbtq_affirming ? 'color:var(--green-dark)' : ''">{{ identity.lgbtq_affirming ? 'Yes' : 'No' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Faith-Sensitive Care</span><span class="pp-info-val" :style="identity.faith_sensitive ? 'color:var(--green-dark)' : ''">{{ identity.faith_sensitive ? 'Available' : 'Not specified' }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">ADA Accessible</span><span class="pp-info-val" :style="identity.ada_accessible ? 'color:var(--green-dark)' : ''">{{ identity.ada_accessible ? 'Fully Accessible' : 'Not specified' }}</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Gender Affirming</span><span class="pp-info-val" :style="identity.gender_affirming ? 'color:var(--green-dark)' : ''">{{ identity.gender_affirming ? 'Yes' : 'No' }}</span></div>
          </div>

          <!-- Affiliations & Memberships -->
          <div v-if="pm.affiliations?.hospital || pm.affiliations?.academic_apt || pm.affiliations?.apa_member" class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="award" :size="13" class="aegis-icon-gold-dark" /> Affiliations &amp; Memberships
            </div>
            <div v-if="pm.affiliations.hospital" class="pp-info-row"><span class="pp-info-label">Hospital Affil.</span><span class="pp-info-val">{{ pm.affiliations.hospital }}</span></div>
            <div v-if="pm.affiliations.academic_apt" class="pp-info-row"><span class="pp-info-label">Academic Appt.</span><span class="pp-info-val">{{ pm.affiliations.academic_apt }}</span></div>
            <div v-if="pm.affiliations.apa_member" class="pp-info-row"><span class="pp-info-label">APA Member</span><span class="pp-info-val" style="color:var(--green-dark)">Yes</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Group Practice</span><span class="pp-info-val">{{ practiceName }}</span></div>
            <template v-if="pm.affiliations.publications">
              <div class="pp-section-eyebrow" style="margin-top:14px">Research &amp; Publications</div>
              <div style="font-size:12px;color:var(--text-2);line-height:1.6">
                {{ pm.affiliations.publications }} peer-reviewed publications.
                Focus areas: trauma-informed care, EMDR outcomes, and integrated mental-health care models.
              </div>
            </template>
          </div>

          <!-- Logged-in: Connection Info -->
          <div v-if="isLoggedIn && !isOwner && pm.connection" class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="link" :size="13" class="aegis-icon-gold-dark" /> Connection Info
            </div>
            <div class="pp-info-row"><span class="pp-info-label">Connected Since</span><span class="pp-info-val">{{ pm.connection.connected_since }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Connection Type</span><span class="pp-info-val">{{ pm.connection.connection_type }}</span></div>
            <div v-if="pm.connection.last_interaction" class="pp-info-row"><span class="pp-info-label">Last Interaction</span><span class="pp-info-val">{{ pm.connection.last_interaction }}</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Mutual Connections</span><span class="pp-info-val">{{ pm.connection.mutual_connections }} shared in network</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Profile Completeness</span><span class="pp-info-val" style="color:var(--green-dark)">{{ pm.connection.profile_completeness }} Complete</span></div>
            <div class="pp-action-row">
              <button class="btn-icon btn-icon-danger" data-tooltip="Remove from network" @click="confirmRemove">
                <AegisIcon name="trash" :size="14" />
              </button>
            </div>
          </div>

          <!-- Logged-in but not connected (no connection record) -->
          <div v-else-if="isLoggedIn && !isOwner && !pm.connection" class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="link" :size="13" class="aegis-icon-gold-dark" /> Network Connection
            </div>
            <p style="font-size:13px;color:var(--text-2);line-height:1.6;margin:0 0 14px">
              You're not yet connected with {{ user.display_name }}. Add them to your clinical network to
              unlock referral tracking, shared care coordination, and connection history.
            </p>
            <button class="btn btn-outline btn-sm" @click="openReferral">
              <AegisIcon name="plus" :size="13" /> Connect
            </button>
          </div>

          <!-- Anonymous: locked Connection Info -->
          <div v-else-if="!isLoggedIn" class="pp-section">
            <div class="pp-section-title">
              <AegisIcon name="lock" :size="13" class="aegis-icon-gold-dark" /> Connection Info
            </div>
            <p style="font-size:12px;color:var(--text-3);line-height:1.6;margin:0 0 10px">
              See when you connected, your interaction history, and mutual connections in your network.
            </p>
            <a :href="route('login')" class="btn btn-outline btn-sm">Sign in to view</a>
          </div>

        </div>
      </div><!-- /pp-grid -->

    </div><!-- /public-profile-wrap -->

    <!-- ═══ MODALS (logged-in non-owner only) ═══ -->
    <template v-if="isLoggedIn && !isOwner">

      <!-- Centralized Referral Modal -->
      <ReferralModal v-model="showReferralModal" :roster="[]" :network="[]" />

      <!-- Service Request Modal -->
      <AegisModal v-model="showServiceRequestModal"
                  title="Request a Service"
                  subtitle="Request an appointment or specific service from this provider"
                  size="md">
        <div class="pp-tip-box" style="margin-top:0;display:flex;gap:10px;align-items:flex-start">
          <span style="flex-shrink:0;margin-top:1px;display:inline-flex;align-items:center;line-height:0">
            <AegisIcon name="info" :size="16" class="aegis-icon-gold-dark" />
          </span>
          <div class="pp-tip-body">Service requests are sent securely through Aegis. You'll receive a confirmation once the provider responds.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Service</label>
          <input type="text" class="form-input" :value="svcRequestForm.service" readonly>
        </div>
        <div class="form-group">
          <label class="form-label">From Provider</label>
          <input type="text" class="form-input" :value="user.display_name + (user.credentials ? ', ' + user.credentials : '')" readonly>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">Preferred Date <span class="req">*</span></label>
            <input type="date" class="form-input" v-model="svcRequestForm.date">
          </div>
          <div class="form-group">
            <label class="form-label">Preferred Time</label>
            <select class="form-select" v-model="svcRequestForm.time">
              <option>Morning (9am–12pm)</option>
              <option>Afternoon (12–5pm)</option>
              <option>Evening (5–8pm)</option>
              <option>Flexible</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Format</label>
          <select class="form-select" v-model="svcRequestForm.format">
            <option>Telehealth</option><option>In-Person</option><option>No preference</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes for Provider <span style="color:var(--text-4);font-weight:500">(optional)</span></label>
          <textarea class="form-textarea" rows="3" placeholder="Briefly describe what you'd like to discuss…" v-model="svcRequestForm.notes"></textarea>
        </div>
        <template #footer>
          <button class="btn btn-outline" @click="showServiceRequestModal = false">Cancel</button>
          <button class="btn btn-primary" @click="submitServiceRequest">
            <span class="btn-ico"><AegisIcon name="send" :size="13" /></span>Send Request
          </button>
        </template>
      </AegisModal>

      <!-- Endorse Modal -->
      <AegisModal v-model="showEndorseModal"
                  title="Endorse This Practitioner"
                  subtitle="Share your experience to help colleagues evaluate fit"
                  size="md">
        <div class="form-group">
          <label class="form-label">Practitioner</label>
          <input type="text" class="form-input" :value="user.display_name + (user.credentials ? ', ' + user.credentials : '')" readonly>
        </div>
        <div class="form-group">
          <label class="form-label">Your Rating <span class="req">*</span></label>
          <div class="rating-stars" role="radiogroup" aria-label="Star rating">
            <button v-for="i in 5" :key="i" type="button"
                    :class="['rating-star', endorseForm.rating >= i ? 'is-on' : '']"
                    @click="endorseForm.rating = i"
                    :aria-label="i + (i === 1 ? ' star' : ' stars')">
              <AegisIcon name="star" :size="22" />
            </button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Headline <span style="color:var(--text-4);font-weight:500">(optional)</span></label>
          <input type="text" class="form-input" placeholder="e.g. Responsive, thorough, easy referral partner" maxlength="80" v-model="endorseForm.headline">
        </div>
        <div class="form-group">
          <label class="form-label">Your Endorsement <span class="req">*</span></label>
          <textarea class="form-textarea" rows="4"
                    placeholder="What stood out about this practitioner? How were they to work with?"
                    maxlength="600" v-model="endorseForm.body"></textarea>
          <div class="form-hint">Visible to other practitioners on this profile · 600 chars max</div>
        </div>
        <div class="form-group">
          <label class="form-label">Connection Context</label>
          <select class="form-select" v-model="endorseForm.context">
            <option>Referral exchange</option>
            <option>Co-managed client</option>
            <option>Peer consultation</option>
            <option>Supervision</option>
            <option>Other</option>
          </select>
        </div>
        <template #footer>
          <button class="btn btn-outline" @click="showEndorseModal = false">Cancel</button>
          <button class="btn btn-primary" @click="submitEndorse">
            <span class="btn-ico"><AegisIcon name="send" :size="13" /></span>Submit Endorsement
          </button>
        </template>
      </AegisModal>

    </template>
  </PublicLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import ReferralModal from '@/components/modals/ReferralModal.vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  user:        { type: Object, required: true },
  profileMeta: { type: Object, default: () => ({}) },
  services:    { type: Array,  default: () => [] },
})

const page = usePage()
const toast = useToast()
const { confirmAction } = useConfirm()
const pricing = usePricingStore()

// Derive auth state from Inertia shared props — zero dependency on controller passing them
const authUser   = computed(() => page.props.auth?.user ?? null)
const isLoggedIn = computed(() => !!authUser.value)
const isOwner    = computed(() => isLoggedIn.value && authUser.value?.id === props.user?.id)

// ── Aliases for cleaner template access ───────────────────────────────
const pm       = computed(() => props.profileMeta ?? {})
const schedule = computed(() => pm.value.schedule  ?? {})
const identity = computed(() => pm.value.identity  ?? {})
const creds    = computed(() => pm.value.credentials ?? {})
const fees     = computed(() => pm.value.fees       ?? {})
const collab   = computed(() => pm.value.collab     ?? {})

// ── Derived ───────────────────────────────────────────────────────────
const specialtyLabel = computed(() => props.user.specialty ?? 'Clinical Practitioner')
const practiceName   = computed(() => props.user.organization ?? 'Independent Practice')
const sessionFormat  = computed(() => schedule.value.session_format ?? 'In-Person & Telehealth')
const acceptsTelehealth = computed(() => sessionFormat.value.toLowerCase().includes('telehealth'))
const avatarInitials = computed(() =>
  props.user.avatar_initials ?? props.user.display_name?.slice(0, 2).toUpperCase() ?? '??'
)

const culturalCompetencies = computed(() =>
  pm.value.identity?.lgbtq_affirming ? ['LGBTQ+ Affirming', 'Trauma-Informed', 'Multicultural', 'Faith-Sensitive'] : []
)

// ── Static fallback data ───────────────────────────────────────────────
const staticReviews = [
  { name: 'Dr. Sarah Chen, LCSW', stars: 5, quote: 'One of the most collaborative providers I\'ve worked with. Always keeps me in the loop on co-managed clients.', meta: 'Therapist · Connected since Feb 2024' },
  { name: 'Dr. James Okafor, MD', stars: 5, quote: 'Exceptional with treatment-resistant cases. My clients report feeling truly heard. Strong recommendation for complex needs.', meta: 'Primary Care · Connected since Aug 2024' },
  { name: 'Dr. Priya Nair, PhD',  stars: 4, quote: 'Very responsive via Aegis. Occasionally hard to reach by phone, but messages always get a same-day response.', meta: 'Neuropsychologist · Connected since Oct 2024' },
]

const servicesWithLabels = computed(() => props.services.map((svc) => {
  let rateAmount = 'Contact for pricing'
  let rateUnit = ''
  if (svc.price_type !== 'inquiry' && svc.price_cents) {
    const dollars = svc.price_cents / 100
    rateAmount = Number.isInteger(dollars) ? `$${dollars}` : pricing.formatCents(svc.price_cents)
    rateUnit = { fixed: '', hourly: '/hr', session: '/session' }[svc.price_type] ?? ''
  }
  return { ...svc, rateAmount, rateUnit }
}))

function formatLabel(format) {
  return { telehealth: 'Telehealth', in_person: 'In-Person', both: 'Telehealth or In-Person' }[format] ?? format
}

// ── Modal state ────────────────────────────────────────────────────────
const showReferralModal       = ref(false)
const showServiceRequestModal = ref(false)
const showEndorseModal        = ref(false)

const svcRequestForm = ref({ service: '', date: '', time: 'Flexible', format: 'Telehealth', notes: '' })
const endorseForm    = ref({ rating: 0, headline: '', body: '', context: 'Referral exchange' })
const privateNote    = ref('')

// ── Actions ───────────────────────────────────────────────────────────
function openReferral() { showReferralModal.value = true }

function openServiceRequest(serviceName) {
  svcRequestForm.value.service = serviceName
  showServiceRequestModal.value = true
}

function submitServiceRequest() {
  if (!svcRequestForm.value.date) {
    toast.error('Please select a preferred date.')
    return
  }
  showServiceRequestModal.value = false
  toast.success('Service request sent')
  svcRequestForm.value = { service: '', date: '', time: 'Flexible', format: 'Telehealth', notes: '' }
}

function submitEndorse() {
  if (!endorseForm.value.rating) {
    toast.error('Please select a star rating.')
    return
  }
  if (!endorseForm.value.body.trim()) {
    toast.error('Please write your endorsement.')
    return
  }
  showEndorseModal.value = false
  toast.success('Endorsement submitted')
  endorseForm.value = { rating: 0, headline: '', body: '', context: 'Referral exchange' }
}

function saveNote() {
  toast.success('Note saved')
}

function copyShareLink() {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(window.location.href)
      .then(() => toast.success('Profile link copied'))
      .catch(() => toast.error('Could not copy link'))
  } else {
    toast.success('Profile link copied')
  }
}

function confirmRemove() {
  confirmAction(
    'Remove this practitioner from your network? Active referrals will remain accessible.',
    () => toast.success('Removed from network'),
    { title: 'Remove from Network', btnLabel: 'Remove', type: 'danger' }
  )
}
</script>
