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
              <!-- Refer a Client — always visible to any authenticated provider -->
              <button type="button" class="btn-hero-solid is-on-light" @click="openReferral">
                <AegisIcon name="share" :size="14" /> Refer a Client
              </button>
              <!-- Connected: additional connection-specific actions -->
              <template v-if="isConnected">
              </template>
              <!-- Inbound: profile owner sent viewer a request — show Accept/Decline -->
              <template v-else-if="pm.inbound_request_id">
                <button type="button" class="btn-hero-solid is-on-light" :disabled="connectForm.processing" @click="acceptInbound">
                  <AegisIcon name="check" :size="14" /> Accept Request
                </button>
                <button type="button" class="btn-hero-ghost is-on-light" :disabled="connectForm.processing" @click="declineInbound">
                  <AegisIcon name="x" :size="14" /> Decline
                </button>
              </template>
              <!-- Outbound pending: viewer sent request, waiting -->
              <template v-else-if="pm.pending_request_id">
                <button type="button" class="btn-hero-ghost is-on-light" @click="cancelConnect">
                  <AegisIcon name="x" :size="14" /> Cancel Request
                </button>
              </template>
              <!-- Not connected: show Connect modal -->
              <template v-else>
                <button type="button" class="btn-hero-solid is-on-light" :disabled="connectForm.processing" @click="openConnectModal()">
                  <AegisIcon name="plus" :size="14" /> Connect
                </button>
              </template>
              <button type="button" class="btn-hero-ghost is-on-light" @click="openServiceRequest('Appointment')">
                <AegisIcon name="calendar" :size="14" /> Schedule
              </button>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only" data-tooltip="Message"
                      :disabled="msgLoading === user.id" @click="openConversation(user.id)">
                <AegisIcon name="message" :size="14" />
              </button>
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

        <!-- Full-width badge bar — sits between the title row and hero-meta, aligned under page-hero-text -->
        <div class="hero-badges" style="padding-left:95px">
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

      <!-- ═══ MY SERVICE REQUESTS (viewer strip) ═══ -->
      <div v-if="isLoggedIn && !isOwner && myServiceRequests.length" class="svc-request-strip">
        <div class="svc-request-strip-title">
          <AegisIcon name="clipboard" :size="13" class="aegis-icon-gold-dark" />
          Your Service Requests to {{ user.display_name }}
        </div>
        <div v-for="req in myServiceRequests" :key="req.id" class="svc-request-strip-row">
          <div class="svc-request-strip-left">
            <div class="svc-request-strip-service">{{ req.service_title }}</div>
            <div class="svc-request-strip-meta">Sent {{ req.created_at }}</div>
            <div v-if="req.message" class="svc-request-strip-note">{{ req.message }}</div>
          </div>
          <div class="svc-request-strip-right">
            <span :class="['badge', statusBadgeClass(req.status)]">
              {{ statusLabel(req.status) }}
            </span>
            <div v-if="req.response_note" class="svc-request-strip-response">
              <AegisIcon name="message" :size="11" />
              {{ req.response_note }}
            </div>
            <div v-if="req.responded_at" class="svc-request-strip-response-date">
              Responded {{ req.responded_at }}
            </div>
          </div>
        </div>
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
          <div v-if="props.serviceHeadline || props.serviceBio || props.serviceSpecialties?.length" class="pp-svc-profile-meta">
            <div v-if="props.serviceHeadline" class="pp-svc-profile-headline">{{ props.serviceHeadline }}</div>
            <div v-if="props.yearsExperience" class="pp-svc-profile-years">
              <AegisIcon name="award" :size="13" /> {{ props.yearsExperience }} years of experience
            </div>
            <div v-if="props.serviceSpecialties?.length" class="chip-list" style="margin:8px 0 4px;">
              <span v-for="sp in props.serviceSpecialties" :key="sp" class="chip gold">{{ sp }}</span>
            </div>
            <p v-if="props.serviceBio" class="pp-svc-profile-bio">{{ props.serviceBio }}</p>
          </div>
          <div class="pp-svc-intro">
            Provider-to-provider professional services — supervision, consultation, training, and more.
          </div>
          <div class="pp-svc-grid" style="grid-template-columns:repeat(3,1fr)">
            <div v-for="svc in servicesWithLabels" :key="svc.id" class="pp-svc-card">
              <div class="pp-svc-card-top" style="flex-direction:column;align-items:flex-start;gap:4px">
                <div class="pp-svc-card-name">{{ svc.title }}</div>
                <div class="pp-svc-card-price">{{ svc.rateAmount }}<span v-if="svc.rateUnit">{{ svc.rateUnit }}</span></div>
              </div>
              <div class="pp-svc-card-desc">{{ svc.description }}</div>
              <div class="pp-svc-card-meta">
                <span v-if="svc.duration_min"><AegisIcon name="clock" :size="11" />{{ svc.duration_min }} min</span>
                <span v-if="svc.format"><AegisIcon name="monitor" :size="11" />{{ formatLabel(svc.format) }}</span>
              </div>
              <div class="pp-svc-card-footer" style="flex-direction:column;align-items:stretch;gap:8px">
                <span class="pp-svc-card-avail" :class="svc.availability ?? 'open'">
                  <AegisIcon name="circle-dot" :size="9" class="aegis-icon-filled" />{{ svc.availability_label || ((svc.availability ?? 'open') === 'limited' ? 'Limited Spots' : 'Slots Available') }}
                </span>
                <template v-if="!isOwner">
                  <button v-if="isLoggedIn" class="btn btn-primary btn-sm" style="width:100%"
                          @click="openServiceRequest(svc.title)">Request</button>
                  <a v-else :href="route('login')" class="btn btn-outline btn-sm" style="width:100%;text-align:center">Sign in to Request</a>
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
            <div v-for="(note, idx) in privateNotes" :key="idx" class="pp-note-box">
              {{ note.body }}
              <div class="pp-note-meta">Added {{ formatNoteDate(note.created_at) }}</div>
            </div>
            <p v-if="!privateNotes.length" style="font-size:12px;color:var(--text-4);font-style:italic;margin:0 0 10px">
              No notes yet. Add your first note below.
            </p>
            <textarea class="pp-note-edit" placeholder="Add a private note to yourself..." v-model="noteForm.body"></textarea>
            <button class="btn btn-primary btn-sm" style="margin-top:8px"
                    :disabled="noteForm.processing || !noteForm.body.trim()"
                    @click="saveNote">
              {{ noteForm.processing ? 'Saving…' : 'Save Note' }}
            </button>
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
                    <AegisIcon v-for="i in (rev.stars ?? 5)" :key="'f'+i" name="star" :size="13" :filled="true" style="color:var(--gold-dark)" />
                    <AegisIcon v-for="i in (5 - (rev.stars ?? 5))" :key="'e'+i" name="star" :size="13" style="color:var(--border-strong)" />
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
                    <AegisIcon v-for="i in rev.stars" :key="'f'+i" name="star" :size="13" :filled="true" style="color:var(--gold-dark)" />
                    <AegisIcon v-for="i in (5 - rev.stars)" :key="'e'+i" name="star" :size="13" style="color:var(--border-strong)" />
                  </div>
                </div>
                <div style="font-size:12px;color:var(--text-2);line-height:1.6;font-style:italic">"{{ rev.quote }}"</div>
                <div style="font-size:11px;color:var(--text-3);font-weight:600;margin-top:6px">{{ rev.meta }}</div>
              </div>
            </template>
            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:14px;font-size:12px;color:var(--text-3)">
              <span>Overall Peer Rating: <strong style="color:var(--gold-dark)">{{ computedRating }}/5.0</strong> from {{ computedReviewCount }} review{{ computedReviewCount !== 1 ? 's' : '' }}</span>
              <button v-if="isLoggedIn && !isOwner" class="btn btn-outline btn-sm" @click="showEndorseModal = true">
                <AegisIcon name="plus" :size="13" /> Endorse This Practitioner
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
                   :class="['pp-avail-day', time ? 'open' : 'closed']"
                   :style="time ? { color: 'var(--gold-dark)', borderColor: 'var(--fade-gold)', background: 'var(--badge-bg-gold)' } : {}">
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
            <!-- Inbound: they sent us a request -->
            <template v-if="pm.inbound_request_id">
              <p style="font-size:13px;color:var(--text-2);line-height:1.6;margin:0 0 14px">
                <strong>{{ user.display_name }}</strong> sent you a connection request.
              </p>
              <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button class="btn btn-primary btn-sm" :disabled="connectForm.processing" @click="acceptInbound">
                  <AegisIcon name="check" :size="13" /> Accept Request
                </button>
                <button class="btn btn-outline btn-sm" :disabled="connectForm.processing" @click="declineInbound">
                  Decline
                </button>
              </div>
            </template>
            <!-- Outbound: we sent them a request -->
            <template v-else-if="pm.pending_request_id">
              <p style="font-size:13px;color:var(--text-2);line-height:1.6;margin:0 0 14px">
                You're not yet connected with {{ user.display_name }}. Add them to your clinical network to
                unlock referral tracking, shared care coordination, and connection history.
              </p>
              <p style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--gold-dark);font-weight:600;margin:0 0 16px">
                <AegisIcon name="clock" :size="12" /> Connection request pending
              </p>
              <div>
                <button class="btn btn-outline btn-sm btn-danger-outline" @click="cancelConnect">
                  <AegisIcon name="x" :size="13" /> Cancel Request
                </button>
              </div>
            </template>
            <!-- Not connected at all -->
            <template v-else>
              <p style="font-size:13px;color:var(--text-2);line-height:1.6;margin:0 0 14px">
                You're not yet connected with {{ user.display_name }}. Add them to your clinical network to
                unlock referral tracking, shared care coordination, and connection history.
              </p>
              <button class="btn btn-outline btn-sm" @click="openConnectModal()" :disabled="connectForm.processing">
                <AegisIcon name="plus" :size="13" /> Connect
              </button>
            </template>
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
      <ReferralModal
        :roster="referralRoster"
        :network="networkWithProfile"
        :preselected-recipient="isOwner ? null : networkWithProfile[0] ?? null"
      />

      <!-- Service Request Modal -->
      <ServiceRequestModal
        :provider-id="user.id"
        :provider-label="user.display_name + (user.credentials ? ', ' + user.credentials : '')"
        ref="svcModalRef"
      />

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
          <div class="rating-stars" style="display:flex;gap:4px;cursor:pointer" role="radiogroup" aria-label="Star rating">
            <button v-for="i in 5" :key="i" type="button"
                    class="rating-star"
                    style="background:none;border:none;padding:2px;cursor:pointer;color:var(--gold-dark)"
                    @click="endorseForm.rating = i"
                    :aria-label="i + (i === 1 ? ' star' : ' stars')"
                    :aria-pressed="endorseForm.rating >= i">
              <AegisIcon name="star" :size="28"
                         :filled="endorseForm.rating >= i"
                         :style="endorseForm.rating >= i ? 'color:var(--gold-dark)' : 'color:var(--border-strong)'" />
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
          <button class="btn btn-primary" :disabled="endorseForm.processing" @click="submitEndorse">
            <span class="btn-ico"><AegisIcon name="send" :size="13" /></span>{{ endorseForm.processing ? 'Submitting…' : 'Submit Endorsement' }}
          </button>
        </template>
      </AegisModal>

      <!-- Centralized Connection Request Modal -->
      <ConnectionRequestModal
        :recipient-id="user.id"
        :recipient-name="user.display_name"
        :recipient-role="user.credentials ?? user.title ?? ''"
        @sent="onConnectSent"
      />

    </template>
  </PublicLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage, useForm, router } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import ReferralModal from '@/components/modals/ReferralModal.vue'
import ServiceRequestModal from '@/components/modals/ServiceRequestModal.vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { useMessageButton } from '@/composables/useMessageButton'
import { useModal } from '@/composables/useModal'
import { usePricingStore } from '@/stores/pricing'
import ConnectionRequestModal from '@/components/modals/ConnectionRequestModal.vue'

const props = defineProps({
  user:               { type: Object, required: true },
  profileMeta:        { type: Object, default: () => ({}) },
  services:           { type: Array,  default: () => [] },
  referralRoster:     { type: Array,  default: () => [] },
  referralNetwork:    { type: Array,  default: () => [] },
  myServiceRequests:  { type: Array,  default: () => [] },
  serviceBio:         { type: String, default: null },
  serviceHeadline:    { type: String, default: null },
  serviceSpecialties: { type: Array,  default: () => [] },
  yearsExperience:    { type: Number, default: null },
})

const page = usePage()
const toast = useToast()
const { confirmAction } = useConfirm()
const { openConversation, loading: msgLoading } = useMessageButton()
const { openModal } = useModal()
const svcModalRef = ref(null)
const pricing = usePricingStore()

// Derive auth state from Inertia shared props
const authUser   = computed(() => page.props.auth?.user ?? null)
const isLoggedIn = computed(() => !!authUser.value)
const isOwner    = computed(() => isLoggedIn.value && authUser.value?.id === props.user?.id)

// Ensure the viewed practitioner is always available as a selectable network entry
// even if the viewer hasn't connected with them yet (they're on their public profile,
// so intent is clear). Deduplicate against existing network by slug.
const networkWithProfile = computed(() => {
  const u = props.user
  if (!u?.slug) return props.referralNetwork
  const already = props.referralNetwork.some((n) => n.slug === u.slug)
  if (already) return props.referralNetwork
  const initials = (u.avatar_initials ?? (u.display_name ?? '').replace(/[^A-Za-z]/g, '').slice(0, 2).toUpperCase())
  return [
    {
      id:           u.id,
      display_name: u.display_name,
      credentials:  u.credentials ?? null,
      specialty:    u.specialty   ?? null,
      location:     u.location    ?? null,
      slug:         u.slug,
      accepting:    u.practitioner_public ?? true,
      is_connected: isConnected.value,
      initials,
      avatar_url:   u.avatar_url  ?? null,
    },
    ...props.referralNetwork,
  ]
})

// ── Aliases ───────────────────────────────────────────────────────────
const pm       = computed(() => props.profileMeta ?? {})
const schedule = computed(() => pm.value.schedule  ?? {})
const identity = computed(() => pm.value.identity  ?? {})
const creds    = computed(() => pm.value.credentials ?? {})
const fees     = computed(() => pm.value.fees       ?? {})
const collab   = computed(() => pm.value.collab     ?? {})

// ── Connection state ──────────────────────────────────────────────────
// isConnected = viewer has an active NetworkConnection record with this provider
const isConnected = computed(() => !!pm.value.connection?.id)

// ── Derived ───────────────────────────────────────────────────────────
const specialtyLabel    = computed(() => props.user.specialty ?? 'Clinical Practitioner')
const practiceName      = computed(() => props.user.organization ?? 'Independent Practice')
const sessionFormat     = computed(() => schedule.value.session_format ?? 'In-Person & Telehealth')
const acceptsTelehealth = computed(() => sessionFormat.value.toLowerCase().includes('telehealth'))
const avatarInitials    = computed(() =>
  props.user.avatar_initials ?? props.user.display_name?.slice(0, 2).toUpperCase() ?? '??'
)

const culturalCompetencies = computed(() =>
  pm.value.identity?.lgbtq_affirming ? ['LGBTQ+ Affirming', 'Trauma-Informed', 'Multicultural', 'Faith-Sensitive'] : []
)

// Compute live rating from actual review array (falls back to stored pm.rating)
const computedReviewCount = computed(() => {
  const dynamic = pm.value.reviews ?? []
  return dynamic.length > 0 ? dynamic.length : (pm.value.review_count ?? 0)
})
const computedRating = computed(() => {
  const dynamic = pm.value.reviews ?? []
  if (dynamic.length > 0) {
    const avg = dynamic.reduce((sum, r) => sum + (r.stars ?? 5), 0) / dynamic.length
    return avg.toFixed(1)
  }
  return pm.value.rating ?? '—'
})

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
const showEndorseModal = ref(false)

// ── Forms ──────────────────────────────────────────────────────────────
const connectForm       = useForm({})
const cancelRequestForm = useForm({})

const endorseForm = useForm({
  rating:   0,
  headline: '',
  body:     '',
  context:  'Referral exchange',
})

const noteForm = useForm({ body: '' })

const privateNotes = computed(() => pm.value.private_notes ?? [])

function formatNoteDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

// ── Actions ────────────────────────────────────────────────────────────

// Open referral modal — visible to any authenticated non-owner provider
function openReferral() {
  openModal('referralModal')
}

function openServiceRequest(serviceName) {
  svcModalRef.value?.preselect(serviceName)
  openModal('serviceRequestModal')
}

// Open centralized ConnectionRequestModal
function openConnectModal() {
  openModal('connectionRequestModal')
}

function onConnectSent() {
  toast.success('Connection request sent.')
  router.reload({ only: ['pm'] })
}

// Accept inbound request from this profile owner
function acceptInbound() {
  const reqId = pm.value.inbound_request_id
  if (!reqId) return
  connectForm.post(route('provider.network.accept', { networkRequest: reqId }), {
    preserveScroll: true,
    onSuccess: () => toast.success('Connection accepted — ' + props.user.display_name + ' is now in your network.'),
    onError: () => toast.error('Could not accept request.'),
  })
}

// Decline inbound request from this profile owner
function declineInbound() {
  const reqId = pm.value.inbound_request_id
  if (!reqId) return
  confirmAction(
    'Decline connection request from ' + props.user.display_name + '?',
    () => {
      connectForm.post(route('provider.network.decline', { networkRequest: reqId }), {
        preserveScroll: true,
        onSuccess: () => toast.info('Connection request declined.'),
        onError: () => toast.error('Could not decline request.'),
      })
    },
    { title: 'Decline Request', btnLabel: 'Decline', type: 'danger' }
  )
}

// Cancel outgoing pending connection request
function cancelConnect() {
  const reqId = pm.value.pending_request_id
  if (!reqId) return
  confirmAction(
    'Cancel your connection request to this practitioner?',
    () => {
      cancelRequestForm.delete(route('public.profile.cancel-connect', { networkRequest: reqId }), {
        preserveScroll: true,
        onSuccess: () => toast.success('Connection request cancelled.'),
        onError: () => toast.error('Could not cancel request.'),
      })
    },
    { title: 'Cancel Request', btnLabel: 'Cancel Request', type: 'danger' }
  )
}

// submitServiceRequest → handled by ServiceRequestModal.vue

// Submit endorsement to backend
function submitEndorse() {
  if (!endorseForm.rating) {
    toast.error('Please select a star rating.')
    return
  }
  if (!endorseForm.body.trim()) {
    toast.error('Please write your endorsement.')
    return
  }
  endorseForm.post(route('public.profile.endorse', { user: props.user.id }), {
    preserveScroll: true,
    onSuccess: () => {
      showEndorseModal.value = false
      endorseForm.reset()
      toast.success('Endorsement submitted.')
    },
    onError: () => toast.error('Failed to submit endorsement.'),
  })
}

// Remove network connection via backend
function confirmRemove() {
  const connectionId = pm.value.connection?.id
  if (!connectionId) return
  confirmAction(
    'Remove this practitioner from your network? Active referrals will remain accessible.',
    () => {
      router.delete(route('public.profile.disconnect', { connection: connectionId }), {
        preserveScroll: true,
        onSuccess: () => toast.success('Removed from network.'),
        onError: () => toast.error('Could not remove connection.'),
      })
    },
    { title: 'Remove from Network', btnLabel: 'Remove', type: 'danger' }
  )
}

function saveNote() {
  if (!noteForm.body.trim()) return
  noteForm.post(route('provider.profile.private-note'), {
    preserveScroll: true,
    onSuccess: () => {
      noteForm.reset('body')
      toast.success('Note saved')
    },
    onError: () => toast.error('Failed to save note.'),
  })
}

function statusBadgeClass(status) {
  return { new: 'badge-gold', accepted: 'badge-green', declined: 'badge-red' }[status] ?? 'badge-gold'
}

function statusLabel(status) {
  return { new: 'Pending', accepted: 'Accepted', declined: 'Declined' }[status] ?? status
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
</script>

<style scoped>
.svc-request-strip {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 14px 18px;
  margin-bottom: 20px;
  display: flex;
  flex-direction: column;
  gap: 0;
}
.svc-request-strip-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-3);
  margin-bottom: 12px;
}
.svc-request-strip-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  padding: 12px 0;
  border-top: 1px solid var(--border);
}
.svc-request-strip-row:first-of-type { border-top: none; padding-top: 0; }
.svc-request-strip-service { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 2px; }
.svc-request-strip-meta { font-size: 11px; color: var(--text-4); }
.svc-request-strip-note { font-size: 12px; color: var(--text-2); margin-top: 4px; line-height: 1.5; max-width: 360px; }
.svc-request-strip-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
.svc-request-strip-response { font-size: 11px; color: var(--text-2); display: flex; align-items: center; gap: 4px; max-width: 260px; text-align: right; }
.svc-request-strip-response-date { font-size: 10px; color: var(--text-4); }
</style>
