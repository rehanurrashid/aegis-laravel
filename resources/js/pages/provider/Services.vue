<!--
  pages/provider/Services.vue — Provider Integrative Services marketplace.
  100% parity with services.php. 4 tabs: Listings / Requests / Bookings / Settings.
  Design pass only — backend props stubbed for Prompt 2.
-->
<template>
  <AppLayout>
    <Head title="My Services — Aegis" />

    <!-- ── HERO ──────────────────────────────────────────────────── -->
    <AegisHeroBanner
      eyebrow="Provider Services"
      title="My Services"
      subtitle="Offer supervision, consultation, training, and practice continuity services &amp; more to other providers on the Aegis network."
      quiet
    >
      <template #meta>
        <span class="hero-meta-item">
          <AegisIcon name="clock" :size="14" />
          Services Mode: {{ props.servicesMode ? 'Active' : 'Inactive' }}
        </span>
        <span v-if="props.servicesMode" class="hero-meta-item">
          <AegisIcon name="arrow-right" :size="14" />
          Discoverable in Provider Search
        </span>
        <span v-if="props.heroRating !== '—'" class="hero-meta-item">
          <AegisIcon name="star" :size="14" />
          {{ props.heroRating }} Rating
        </span>
      </template>
      <template #actions>
        <a :href="route('provider.activity') + '?module=services'" class="btn-hero-ghost is-on-light">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
        <button type="button" class="btn-hero-solid is-on-light" @click="modals.create = true">
          <AegisIcon name="plus" :size="14" /> Add New Service
        </button>
      </template>
    </AegisHeroBanner>

    <!-- ── STAT CHIPS ────────────────────────────────────────────── -->
    <div class="stat-chips-row">
      <AegisStatChip icon="grid" :value="stats?.active_listings ?? 0" label="Active Listings" />
      <AegisStatChip icon="bell" :value="stats?.pending_requests ?? 0" label="Pending Requests" />
      <AegisStatChip icon="calendar" :value="stats?.sessions ?? 0" label="Sessions This Month" />
      <AegisStatChip icon="dollar" :value="stats?.revenue_label ?? '$0'" label="Revenue This Month" />
    </div>

    <!-- ── TABS ──────────────────────────────────────────────────── -->
    <div class="tabs-segmented">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        class="tab-pill"
        :class="{ active: activeTab === tab.key }"
        @click="activeTab = tab.key"
      >
        <AegisIcon :name="tab.icon" :size="12" />
        {{ tab.label }}
        <span v-if="tab.count != null" class="badge-pill">
          {{ tab.count }}
        </span>
      </button>
    </div>

    <!-- ══════════════════════════════════════════
         TAB 1: MY LISTINGS
    ══════════════════════════════════════════ -->
    <div v-show="activeTab === 'listings'">

      <!-- Toolbar -->
      <div class="svc-toolbar">
        <div class="search-wrap">
          <AegisIcon name="search" :size="15" />
          <input
            v-model="listingSearch"
            type="text"
            class="form-control"
            placeholder="Search your service listings…"
          >
        </div>
        <select v-model="listingTypeFilter" class="form-select">
          <option value="">All Types</option>
          <option>Clinical Supervision</option>
          <option>Consultation</option>
          <option>Training / Workshop</option>
          <option>Professional Coaching</option>
          <option>Practice Continuity Services</option>
          <option>Other</option>
        </select>
        <select v-model="listingStatusFilter" class="form-select">
          <option value="">All Statuses</option>
          <option>Active</option>
          <option>Draft</option>
          <option>Paused</option>
          <option>Archived</option>
        </select>
      </div>

      <!-- Service Cards Grid -->
      <div class="services-grid">
        <div
          v-for="s in filteredListings"
          :key="s.id"
          class="card"
          @click="setActiveService(s)"
        >
          <div class="card-header" style="align-items:flex-start;">
            <div class="svc-type-icon">
              <AegisIcon :name="s.type_icon || 'briefcase'" :size="22" />
            </div>
            <div style="flex:1;min-width:0;">
              <div class="card-title">{{ s.title }}</div>
              <div class="card-subtitle">{{ s.service_type }}</div>
            </div>
            <AegisBadge
              :label="s.status === 'active' ? 'Active' : s.status === 'paused' ? 'Paused' : 'Draft'"
              :variant="s.status === 'active' ? 'green' : s.status === 'paused' ? 'gold' : 'neutral'"
            />
          </div>
          <div class="card-body">
            <div class="svc-description">{{ s.description }}</div>
            <div v-if="s.meta?.length" class="svc-meta-row">
              <div v-for="(m, i) in s.meta" :key="i" class="svc-meta-item">
                <AegisIcon :name="m.icon || 'check'" :size="13" />
                {{ m.text }}
              </div>
            </div>
            <div class="svc-price-row">
              <div class="svc-price">{{ s.price }}</div>
              <div v-if="s.price_unit" class="svc-price-unit">{{ s.price_unit }}</div>
              <div v-if="s.price_note" class="svc-price-note">{{ s.price_note }}</div>
            </div>
          </div>
          <div v-if="s.metrics?.length" class="svc-card-metrics">
            <div v-for="(mt, i) in s.metrics" :key="i" class="svc-metric">
              <div class="svc-metric-val">{{ mt.val }}</div>
              <div class="svc-metric-label">{{ mt.label }}</div>
            </div>
          </div>
          <div class="card-footer">
            <template v-if="s.status === 'draft'">
              <button class="btn-icon" data-tooltip="Edit Draft" @click.stop="setActiveService(s); modals.edit = true">
                <AegisIcon name="pencil" :size="14" />
              </button>
              <button class="btn-icon" data-tooltip="Publish" @click.stop="setActiveService(s); modals.publish = true">
                <AegisIcon name="check" :size="14" />
              </button>
              <button
                class="btn-icon btn-icon-danger"
                data-tooltip="Delete Draft"
                @click.stop="setActiveService(s); confirmAction({ title: 'Delete Draft', message: 'This will permanently delete \'' + s.title + '\'. This cannot be undone.', btnLabel: 'Delete', type: 'danger' }, () => deleteServiceFromCard())"
              >
                <AegisIcon name="trash" :size="14" />
              </button>
            </template>
            <template v-else>
              <button class="btn-icon" data-tooltip="Edit Listing" @click.stop="setActiveService(s); modals.edit = true">
                <AegisIcon name="pencil" :size="14" />
              </button>
              <button class="btn-icon" data-tooltip="Preview" @click.stop="openPreview(s)">
                <AegisIcon name="eye" :size="14" />
              </button>
              <button class="btn-icon" data-tooltip="View Bookings" @click.stop="activeTab = 'bookings'">
                <AegisIcon name="calendar" :size="14" />
              </button>
              <button v-if="s.status === 'paused'" class="btn-icon" data-tooltip="Resume Listing" @click.stop="setActiveService(s); confirmAction({ title: 'Resume Listing', message: 'Resume this listing and make it visible to clients again?', btnLabel: 'Resume', type: 'primary' }, () => resumeService())">
                <AegisIcon name="arrow-right" :size="14" />
              </button>
              <button v-else class="btn-icon" data-tooltip="Pause Listing" @click.stop="setActiveService(s); modals.pause = true">
                <AegisIcon name="pause" :size="14" />
              </button>
            </template>
          </div>
        </div>

        <!-- Add New Card -->
        <div class="upload-zone" style="min-height:280px;border-radius:var(--radius-lg);cursor:pointer;" @click="modals.create = true">
          <div class="upload-zone-icon"><AegisIcon name="plus" :size="28" /></div>
          <div class="upload-zone-title">Add New Service</div>
          <div class="upload-zone-sub">Supervision, consultation, training, coaching &amp; more</div>
        </div>
      </div>

      <!-- Empty state when no listings match filter -->
      <AegisEmptyState
        v-if="!filteredListings.length && listings.length"
        icon="search"
        title="No listings match your filters"
        subtitle="Try adjusting your search or filter criteria."
      />
      <AegisEmptyState
        v-else-if="!listings.length"
        icon="briefcase"
        title="No Service Listings Yet"
        subtitle="Add your first service to start receiving requests from providers on the network."
      >
        <template #action>
          <button class="btn btn-primary" @click="modals.create = true">
            <AegisIcon name="plus" :size="13" /> Add New Service
          </button>
        </template>
      </AegisEmptyState>
    </div>

    <!-- ══════════════════════════════════════════
         TAB 2: SERVICE REQUESTS
    ══════════════════════════════════════════ -->
    <div v-show="activeTab === 'requests'">

      <div class="page-note">
        <AegisIcon name="users" :size="14" />
        <span>Other practitioners on the network have requested access to your listed services. Review, accept, counter-propose, or dismiss each request below.</span>
      </div>

      <div v-if="newRequests.length" class="alert alert-info" style="margin-bottom:20px;">
        <div class="alert-icon"><AegisIcon name="info" :size="16" /></div>
        <div class="alert-content">
          You have <strong>{{ newRequests.length }} pending service request{{ newRequests.length === 1 ? '' : 's' }}</strong> that need a response. Requests auto-expire after 72 hours if not accepted.
        </div>
      </div>

      <div class="section-header">
        <div class="section-title">
          Pending Requests
          <span class="section-badge">{{ newRequests.length }}</span>
        </div>
      </div>

      <div class="requests-list">
        <AegisEmptyState
          v-if="!newRequests.length"
          icon="inbox"
          title="No Pending Requests"
          subtitle="New service requests from providers will appear here."
        />
        <div
          v-for="r in newRequests"
          :key="r.id"
          class="card request-card new"
          style="padding:16px 20px;display:flex;align-items:center;flex-wrap:wrap;gap:16px;"
          @click="setActiveRequest(r)"
        >
          <div class="avatar avatar-md">{{ r.requester_avatar || initials(r.requester_name) }}</div>
          <div class="req-info">
            <component
            :is="r.requester_slug ? 'a' : 'div'"
            :href="r.requester_slug ? route('public.provider', { slug: r.requester_slug }) : undefined"
            class="req-name"
            :class="{ 'link-name': r.requester_slug }"
          >{{ r.requester_name }}</component>
            <div class="req-detail">{{ r.requester_detail }}</div>
          </div>
          <div class="req-service">
            <div class="req-service-name">{{ r.service_title }}</div>
            <div class="req-service-type">{{ r.request_type }}</div>
          </div>
          <div style="min-width:120px;color:var(--text-3);font-size:12px;font-weight:600;">
            <div style="font-weight:700;font-size:13px;color:var(--text);margin-bottom:2px;">Requested date</div>
            {{ r.requested_date_label }}
          </div>
          <div class="req-date">{{ r.time_label }}</div>
          <div class="req-actions">
            <button
              class="btn-icon"
              :data-tooltip="`Message ${r.requester_name}`"
              :disabled="msgLoading === r.requester_id"
              @click.stop="openConversation(r.requester_id)"
            >
              <AegisIcon name="message" :size="14" />
            </button>
            <button class="btn-icon" data-tooltip="Counter Propose" @click.stop="setActiveRequest(r); modals.counter = true">
              <AegisIcon name="refresh" :size="14" />
            </button>
            <button
              class="btn-icon"
              data-tooltip="Dismiss"
              @click.stop="setActiveRequest(r); dismissForm.reason = ''; dismissForm.otherReason = ''; modals.dismiss = true"
            >
              <AegisIcon name="x" :size="14" />
            </button>
            <button class="btn btn-primary btn-sm" @click.stop="setActiveRequest(r); modals.accept = true">
              <AegisIcon name="check" :size="13" /> Accept
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- ══════════════════════════════════════════
         TAB 3: BOOKINGS & SESSIONS
    ══════════════════════════════════════════ -->
    <div v-show="activeTab === 'bookings'">

      <div class="svc-toolbar">
        <div class="search-wrap">
          <AegisIcon name="search" :size="15" />
          <input v-model="bookingSearch" type="text" class="form-control" placeholder="Search sessions or providers…">
        </div>
        <select v-model="bookingServiceFilter" class="form-select">
          <option value="">All Services</option>
          <option>Individual Supervision</option>
          <option>Group Supervision</option>
          <option>Consultation</option>
          <option>Training / Workshop</option>
          <option>Practice Continuity Services</option>
        </select>
        <select v-model="bookingDateRange" class="form-select">
          <option value="this_month">This Month</option>
          <option value="last_month">Last Month</option>
          <option value="last_60">Last 60 Days</option>
          <option value="last_90">Last 90 Days</option>
          <option value="this_year">This Year</option>
          <option value="all">All Time</option>
        </select>
      </div>

      <div class="card card-flush">
        <div class="card-header">
          <div class="card-title" style="display:flex;align-items:center;gap:8px;">
            <AegisIcon name="calendar" :size="16" />
            Sessions — {{ bookingPeriodLabel }}
          </div>
          <div style="display:flex;gap:8px;">
            <AegisBadge :label="`${stats?.sessions ?? 0} this month`" variant="gold" />
            <AegisBadge :label="(stats?.revenue_label ?? '$0') + ' earned'" variant="green" />
          </div>
        </div>
        <div class="card-body">
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr>
                  <th>Provider</th>
                  <th>Service</th>
                  <th>Date &amp; Time</th>
                  <th>Timezone</th>
                  <th>Duration</th>
                  <th>Amount</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!bookings.length">
                  <td colspan="7" style="text-align:center;color:var(--text-4);padding:24px;">No sessions found.</td>
                </tr>
                <tr v-for="b in bookings" :key="b.id" @click="setActiveBooking(b)">
                  <td>
                    <div class="td-provider">
                      <div class="avatar avatar-sm">{{ b.provider_avatar || initials(b.provider_name) }}</div>
                      <div>
                        <a v-if="b.provider_slug" :href="route('public.provider', { slug: b.provider_slug })" class="td-name link-name">{{ b.provider_name }}</a>
                        <div v-else class="td-name">{{ b.provider_name }}</div>
                        <div class="td-cred">{{ b.provider_credentials }}</div>
                      </div>
                    </div>
                  </td>
                  <td>{{ b.service_title }}</td>
                  <td>{{ b.datetime_label }}</td>
                  <td style="font-size:11px;font-weight:600;color:var(--text-3);">{{ b.timezone ? b.timezone.replace('America/','').replace('_',' ') : '—' }}</td>
                  <td>{{ b.duration_label }}</td>
                  <td style="font-weight:700;">{{ b.amount }}</td>
                  <td><AegisBadge :label="statusLabel(b.status)" :variant="statusVariant(b.status)" /></td>
                  <td>
                    <div class="req-actions">
                      <button
                        class="btn-icon"
                        data-tooltip="Session Notes"
                        @click.stop="setActiveBooking(b); modals.sessionNotes = true"
                      >
                        <AegisIcon name="file-text" :size="14" />
                      </button>
                      <button
                        v-if="b.status === 'scheduled'"
                        class="btn-icon"
                        data-tooltip="Cancel Session"
                        @click.stop="setActiveBooking(b); modals.cancelSession = true"
                      >
                        <AegisIcon name="x" :size="14" />
                      </button>
                      <button
                        v-if="b.status === 'completed' && b.amount_cents > 0"
                        class="btn-icon"
                        data-tooltip="View Invoice"
                        @click.stop="setActiveBooking(b); modals.invoice = true"
                      >
                        <AegisIcon name="dollar-sign" :size="14" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <AegisPagination
            :current-page="bookingPage"
            :total-pages="bookingPageCount"
            :total="stats?.total_sessions ?? bookings.length"
            :from="bookings.length ? (bookingPage - 1) * 10 + 1 : 0"
            :to="Math.min(bookingPage * 10, stats?.total_sessions ?? bookings.length)"
            :show-meta="true"
            @change="bookingPage = $event"
          />
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════
         TAB 4: MY REQUESTS
    ══════════════════════════════════════════ -->
    <div v-show="activeTab === 'outgoing'">

      <!-- ── SECTION A: Sessions I booked (I am the client) ─────────── -->
      <div class="section-header" style="margin-top:0;">
        <div class="section-title">
          My Booked Sessions
          <span class="section-badge">{{ clientSessions.length }}</span>
        </div>
      </div>
      <div class="page-note" style="margin-bottom:16px;">
        <AegisIcon name="users" :size="14" />
        <span>Sessions you have scheduled with other practitioners. Once a session takes place, confirm it here to release payment to the provider.</span>
      </div>

      <div v-if="!clientSessions.length" style="margin-bottom:24px;">
        <AegisEmptyState icon="calendar" title="No booked sessions" message="When a provider accepts your request and books a session, it will appear here." />
      </div>

      <div v-else class="card card-flush" style="margin-bottom:28px;">
        <div class="card-body">
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr>
                  <th>Provider</th>
                  <th>Service</th>
                  <th>Scheduled</th>
                  <th>Amount</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="cs in clientSessions" :key="cs.id">
                  <td>
                    <div class="td-provider">
                      <div class="avatar avatar-sm">{{ cs.practitioner_avatar || '?' }}</div>
                      <div>
                        <a v-if="cs.practitioner_slug" :href="route('public.provider', { slug: cs.practitioner_slug })" class="td-name link-name">{{ cs.practitioner_name }}</a>
                        <div v-else class="td-name">{{ cs.practitioner_name }}</div>
                        <div class="td-cred">{{ cs.practitioner_detail }}</div>
                      </div>
                    </div>
                  </td>
                  <td>{{ cs.service_title }}</td>
                  <td>{{ cs.datetime_label }}</td>
                  <td style="font-weight:700;">{{ cs.amount }}</td>
                  <td><AegisBadge :label="statusLabel(cs.status)" :variant="statusVariant(cs.status)" /></td>
                  <td>
                    <div class="req-actions">
                      <button
                        v-if="cs.status === 'scheduled'"
                        class="btn btn-success btn-sm"
                        @click.stop="activeClientSession = cs; modals.completeSession = true"
                      >
                        <AegisIcon name="check" :size="13" /> Confirm &amp; Pay
                      </button>
                      <button
                        v-else-if="cs.status === 'completed' && cs.amount_cents > 0"
                        class="btn-icon"
                        data-tooltip="View Invoice"
                        @click.stop="activeClientSession = cs; modals.clientInvoice = true"
                      >
                        <AegisIcon name="file-text" :size="14" />
                      </button>
                      <span v-else class="td-sub">—</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ── SECTION B: Requests I sent to others ────────────────────── -->
      <div class="section-header">
        <div class="section-title">
          My Service Requests
          <span class="section-badge">{{ props.outgoingRequests.length }}</span>
        </div>
        <div class="section-subtitle">Requests you have sent to other providers — pending, accepted, declined, or withdrawn.</div>
      </div>

      <div v-if="!props.outgoingRequests.length">
        <AegisEmptyState icon="send" title="No outgoing requests" message="When you request a service from another provider, it will appear here." />
      </div>

      <div v-else class="card card-flush">
        <div class="card-body">
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr>
                  <th>Provider</th>
                  <th>Service</th>
                  <th>Type</th>
                  <th>Date Sent</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in props.outgoingRequests" :key="r.id">
                  <td>
                    <div class="td-provider">
                      <div class="avatar avatar-sm">{{ r.provider_avatar || '?' }}</div>
                      <div>
                        <a v-if="r.provider_slug" :href="route('public.provider', { slug: r.provider_slug })" class="td-name link-name">{{ r.provider_name }}</a>
                        <div v-else class="td-name">{{ r.provider_name }}</div>
                        <div class="td-cred">{{ r.provider_detail }}</div>
                      </div>
                    </div>
                  </td>
                  <td>{{ r.service_title }}</td>
                  <td>{{ r.request_type }}</td>
                  <td>
                    <div>{{ r.sent_date_label }}</div>
                    <div class="td-sub">{{ r.time_label }}</div>
                  </td>
                  <td>
                    <AegisBadge :label="statusLabel(r.status)" :variant="statusVariant(r.status)" />
                    <div v-if="r.response_note" class="td-sub" style="margin-top:4px;">{{ r.response_note }}</div>
                  </td>
                  <td>
                    <div class="req-actions">
                      <button
                        v-if="r.status === 'new'"
                        class="btn-icon"
                        data-tooltip="Withdraw Request"
                        @click.stop="withdrawOutgoingRequest(r.id)"
                      >
                        <AegisIcon name="x" :size="14" />
                      </button>
                      <span v-else class="td-sub">—</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

        <!-- ══════════════════════════════════════════
         TAB 5: SETTINGS
    ══════════════════════════════════════════ -->
    <div v-show="activeTab === 'settings'">

      <div class="settings-grid">

        <!-- Services Settings (links to Account Settings) -->
        <div class="card" style="grid-column:1/-1;">
          <div class="card-header is-settings">
            <div>
              <div class="card-title" style="display:flex;align-items:center;gap:8px;">
                <AegisIcon name="settings" :size="16" /> Services Settings
              </div>
              <div class="card-subtitle">Visibility, booking preferences, and payment — managed in Account Settings</div>
            </div>
            <a :href="'/provider/settings?tab=services-mode'" class="btn btn-primary btn-sm">
              <AegisIcon name="settings" :size="13" /> Open Settings
            </a>
          </div>
          <div class="card-body" style="padding:0;">
            <div class="setting-row" style="padding:14px 20px;border-bottom:1px solid var(--border);">
              <div class="setting-info">
                <div class="setting-label" style="display:flex;align-items:center;gap:7px;">
                  <AegisIcon name="eye" :size="14" /> Visibility &amp; Mode
                </div>
                <div class="setting-desc">Services Mode · Show in search · Accept requests · Show pricing</div>
              </div>
              <a :href="'/provider/settings?tab=services-mode'" class="btn btn-outline btn-sm">
                <AegisIcon name="chevron-right" :size="13" /> Edit
              </a>
            </div>
            <div class="setting-row" style="padding:14px 20px;border-bottom:1px solid var(--border);">
              <div class="setting-info">
                <div class="setting-label" style="display:flex;align-items:center;gap:7px;">
                  <AegisIcon name="calendar" :size="14" /> Booking Preferences
                </div>
                <div class="setting-desc">Manual approval · Request expiry · Buffer between sessions</div>
              </div>
              <a :href="'/provider/settings?tab=services-mode'" class="btn btn-outline btn-sm">
                <AegisIcon name="chevron-right" :size="13" /> Edit
              </a>
            </div>
            <div class="setting-row" style="padding:14px 20px;">
              <div class="setting-info">
                <div class="setting-label" style="display:flex;align-items:center;gap:7px;">
                  <AegisIcon name="credit-card" :size="14" /> Payment &amp; Rates
                </div>
                <div class="setting-desc">Hourly rate · Payment method · Sliding scale</div>
              </div>
              <a :href="'/provider/settings?tab=services-mode'" class="btn btn-outline btn-sm">
                <AegisIcon name="chevron-right" :size="13" /> Edit
              </a>
            </div>
          </div>
        </div>

        <!-- Services Profile Bio -->
        <div class="card" style="grid-column:1/-1;">
          <div class="card-header is-settings">
            <div>
              <div class="card-title" style="display:flex;align-items:center;gap:8px;">
                <AegisIcon name="user" :size="16" />
                Services Profile
              </div>
              <div class="card-subtitle">This appears on your Services tab in provider search results</div>
            </div>
          </div>
          <div class="card-body">
            <div class="form-row" style="margin-bottom:16px;">
              <div class="form-group">
                <label class="form-label">Services Headline</label>
                <input v-model="profileForm.headline" class="form-input" type="text" placeholder="e.g. Board-Approved Clinical Supervisor | Trauma &amp; DBT Specialist">
              </div>
              <div class="form-group">
                <label class="form-label">Years of Experience</label>
                <input v-model.number="profileForm.years_experience" class="form-input" type="number" placeholder="14">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Services Bio</label>
              <textarea v-model="profileForm.bio" class="form-input"></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Specialties (shown as tags)</label>
              <div class="chip-list">
                <span v-for="(sp, i) in profileForm.specialties" :key="i" class="chip gold">
                  {{ sp }}
                  <button class="chip-remove" @click="removeSpecialty(i)">
                    <AegisIcon name="x" :size="10" />
                  </button>
                </span>
                <input
                  v-model="newSpecialty"
                  type="text"
                  class="form-control"
                  style="width:auto;min-width:140px;display:inline-flex;"
                  placeholder="Add specialty…"
                  @keydown.enter.prevent="addSpecialty"
                >
                <button type="button" class="btn btn-outline btn-sm" @click="addSpecialty">
                  <AegisIcon name="plus" :size="12" /> Add
                </button>
              </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:4px;">
              <button class="btn btn-outline btn-sm" @click="resetProfile">Cancel</button>
              <button class="btn btn-primary btn-sm" @click="saveProfile">
                <AegisIcon name="check" :size="13" /> Save Profile
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         MODALS
    ══════════════════════════════════════════════════════ -->

    <!-- Create Service Modal -->
    <AegisModal v-model="modals.create" title="Create New Service Listing" size="lg">
      <div class="modal-section-label">Service Type</div>
      <div class="pricing-options">
        <div
          v-for="opt in serviceTypeOptions"
          :key="opt.key"
          class="pricing-opt"
          :class="{ selected: createForm.category === opt.key }"
          @click="createForm.category = opt.key"
        >
          <div class="pricing-opt-label">
            <AegisIcon :name="opt.icon" :size="14" /> {{ opt.label }}
          </div>
          <div class="pricing-opt-desc">{{ opt.desc }}</div>
        </div>
      </div>

      <div class="modal-section-label">Service Details</div>
      <div class="form-group">
        <label class="form-label">Service Name <span style="color:var(--red);">*</span></label>
        <input
          v-model="createForm.title"
          class="form-input"
          :class="{ 'is-invalid': createV$.title.$error }"
          type="text"
          placeholder="e.g. Individual Clinical Supervision for Pre-Licensed Therapists"
          @blur="createV$.title.$touch()"
        >
        <div v-if="createV$.title.$error" class="form-error">{{ createFieldError('title') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Description <span style="color:var(--red);">*</span></label>
        <textarea
          v-model="createForm.description"
          class="form-input"
          :class="{ 'is-invalid': createV$.description.$error }"
          placeholder="Describe what you offer, who it's for, your approach, and any requirements…"
          @blur="createV$.description.$touch()"
        ></textarea>
        <div v-if="createV$.description.$error" class="form-error">{{ createFieldError('description') }}</div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Session Duration</label>
          <select v-model.number="createForm.duration_min" class="form-select">
            <option :value="30">30 minutes</option>
            <option :value="45">45 minutes</option>
            <option :value="50">50 minutes</option>
            <option :value="60">60 minutes</option>
            <option :value="75">75 minutes</option>
            <option :value="90">90 minutes</option>
            <option :value="120">2 hours</option>
            <option :value="150">2.5 hours</option>
            <option :value="180">3 hours</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Format</label>
          <select v-model="createForm.format" class="form-select">
            <option value="telehealth">Virtual only</option>
            <option value="in_person">In-person only</option>
            <option value="both">Virtual &amp; In-person</option>
          </select>
        </div>
      </div>

      <div class="modal-section-label">Pricing</div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Rate ($) <span style="color:var(--red);">*</span></label>
          <input
            v-model.number="createDollars"
            class="form-input"
            type="number"
            placeholder="150"
          >
        </div>
        <div class="form-group">
          <label class="form-label">Per</label>
          <select v-model="createForm.price_type" class="form-select">
            <option value="session">Session</option>
            <option value="hourly">Hour</option>
            <option value="fixed">Package / Fixed</option>
            <option value="inquiry">Contact for pricing</option>
          </select>
        </div>
      </div>
      <div class="setting-row" style="padding:8px 0;">
        <div class="setting-info">
          <div class="setting-label">Sliding Scale Available</div>
        </div>
        <AegisToggle v-model="createForm.sliding_scale" />
      </div>

      <div class="modal-section-label">Availability</div>
      <div class="form-row" style="margin-bottom:14px;">
        <div class="form-group">
          <label class="form-label">Availability</label>
          <select v-model="createForm.availability" class="form-select">
            <option value="open">Open — accepting requests</option>
            <option value="limited">Limited availability</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Availability Label (optional)</label>
          <input v-model="createForm.availability_label" class="form-input" type="text" placeholder="e.g. 3 spots left, By Request">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Specialties / Tags</label>
        <input v-model="createForm.tags" class="form-input" type="text" placeholder="e.g. Trauma, DBT, EMDR, Child Therapy (comma separated)">
      </div>

      <div class="modal-section-label">Capacity &amp; Requirements</div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Max Clients</label>
          <input v-model.number="createForm.max_clients" class="form-input" type="number" placeholder="Leave blank for unlimited">
        </div>
        <div class="form-group">
          <label class="form-label">Credentials Required</label>
          <select v-model="createForm.credentials_required" class="form-select">
            <option>None</option>
            <option>Pre-licensed only</option>
            <option>Licensed only</option>
            <option>Any license level</option>
          </select>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-outline" @click="modals.create = false">Cancel</button>
        <button class="btn btn-outline" @click="submitCreate('draft')">Save as Draft</button>
        <button class="btn btn-primary" @click="submitCreate('active')">
          <AegisIcon name="check" :size="14" /> Publish Listing
        </button>
      </template>
    </AegisModal>

    <!-- Edit Service Modal -->
    <AegisModal v-model="modals.edit" title="Edit Service Listing" size="lg">
      <div class="modal-section-label">Service Type</div>
      <div class="pricing-options">
        <div
          v-for="opt in serviceTypeOptions"
          :key="opt.key"
          class="pricing-opt"
          :class="{ selected: editForm.category === opt.key }"
          @click="editForm.category = opt.key"
        >
          <div class="pricing-opt-label">
            <AegisIcon :name="opt.icon" :size="14" /> {{ opt.label }}
          </div>
          <div class="pricing-opt-desc">{{ opt.desc }}</div>
        </div>
      </div>

      <div class="modal-section-label">Service Details</div>
      <div class="form-group">
        <label class="form-label">Service Name <span style="color:var(--red);">*</span></label>
        <input
          v-model="editForm.title"
          class="form-input"
          :class="{ 'is-invalid': editV$.title.$error }"
          type="text"
          @blur="editV$.title.$touch()"
        >
        <div v-if="editV$.title.$error" class="form-error">{{ editFieldError('title') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea v-model="editForm.description" class="form-input" style="min-height:90px;"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Session Duration</label>
          <select v-model.number="editForm.duration_min" class="form-select">
            <option :value="30">30 minutes</option>
            <option :value="45">45 minutes</option>
            <option :value="50">50 minutes</option>
            <option :value="60">60 minutes</option>
            <option :value="75">75 minutes</option>
            <option :value="90">90 minutes</option>
            <option :value="120">2 hours</option>
            <option :value="150">2.5 hours</option>
            <option :value="180">3 hours</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Format</label>
          <select v-model="editForm.format" class="form-select">
            <option value="telehealth">Virtual only</option>
            <option value="in_person">In-person only</option>
            <option value="both">Virtual &amp; In-person</option>
          </select>
        </div>
      </div>

      <div class="modal-section-label">Pricing</div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Rate ($)</label>
          <input v-model.number="editDollars" class="form-input" type="number" placeholder="150">
        </div>
        <div class="form-group">
          <label class="form-label">Per</label>
          <select v-model="editForm.price_type" class="form-select">
            <option value="session">Session</option>
            <option value="hourly">Hour</option>
            <option value="fixed">Package / Fixed</option>
            <option value="inquiry">Contact for pricing</option>
          </select>
        </div>
      </div>

      <div class="modal-section-label">Availability &amp; Status</div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Availability</label>
          <select v-model="editForm.availability" class="form-select">
            <option value="open">Open — accepting requests</option>
            <option value="limited">Limited availability</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Availability Label (optional)</label>
          <input v-model="editForm.availability_label" class="form-input" type="text" placeholder="e.g. 3 spots left">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Listing Status</label>
        <select v-model="editForm.status" class="form-select">
          <option value="active">Active</option>
          <option value="paused">Paused</option>
          <option value="draft">Draft</option>
          <option value="archived">Archived</option>
        </select>
      </div>

      <template #footer>
        <button class="btn btn-outline" @click="modals.edit = false">Cancel</button>
        <button class="btn btn-primary" @click="submitEdit">
          <AegisIcon name="check" :size="14" /> Save Changes
        </button>
      </template>
    </AegisModal>

    <!-- Accept Request Modal -->
    <AegisModal v-model="modals.accept" title="Accept Service Request" size="sm">
      <div class="alert alert-success" style="margin-bottom:18px;">
        <AegisIcon name="check" :size="16" />
        <span>Accepting will auto-generate a Service Agreement for both parties to sign digitally.</span>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Session Date <span style="color:var(--red);">*</span></label>
          <input v-model="acceptForm.session_date" class="form-input" type="date" :min="todayDate">
        </div>
        <div class="form-group">
          <label class="form-label">Session Time</label>
          <select v-model="acceptForm.session_time" class="form-select">
            <option value="09:00">9:00 AM</option>
            <option value="10:00">10:00 AM</option>
            <option value="11:00">11:00 AM</option>
            <option value="12:00">12:00 PM</option>
            <option value="13:00">1:00 PM</option>
            <option value="14:00">2:00 PM</option>
            <option value="15:00">3:00 PM</option>
            <option value="16:00">4:00 PM</option>
            <option value="17:00">5:00 PM</option>
            <option value="18:00">6:00 PM</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Timezone</label>
        <select v-model="acceptForm.timezone" class="form-select">
          <option value="America/New_York">Eastern Time (ET)</option>
          <option value="America/Chicago">Central Time (CT)</option>
          <option value="America/Denver">Mountain Time (MT)</option>
          <option value="America/Los_Angeles">Pacific Time (PT)</option>
          <option value="America/Anchorage">Alaska Time (AKT)</option>
          <option value="Pacific/Honolulu">Hawaii Time (HST)</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Session Format</label>
        <select v-model="acceptForm.format" class="form-select">
          <option>Virtual (Telehealth)</option>
          <option>In-person</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Note to Provider (optional)</label>
        <textarea v-model="acceptForm.note" class="form-input" style="min-height:70px;" placeholder="Any intake info, prep instructions, or a welcome message…"></textarea>
      </div>
      <div class="setting-row" style="padding:6px 0;">
        <div class="setting-info">
          <div class="setting-label">Set as recurring (ongoing relationship)</div>
        </div>
        <AegisToggle v-model="acceptForm.recurring" />
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.accept = false">Cancel</button>
        <button class="btn btn-primary" @click="submitAccept">
          <AegisIcon name="check" :size="14" /> Accept Request
        </button>
      </template>
    </AegisModal>

    <!-- Counter Propose Modal -->
    <AegisModal v-model="modals.counter" title="Counter Propose" size="sm">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Proposed Date</label>
          <input v-model="counterForm.proposed_date" class="form-input" type="date" :min="todayDate">
        </div>
        <div class="form-group">
          <label class="form-label">Proposed Time</label>
          <select v-model="counterForm.proposed_time" class="form-select">
            <option value="09:00">9:00 AM</option>
            <option value="10:00">10:00 AM</option>
            <option value="11:00">11:00 AM</option>
            <option value="12:00">12:00 PM</option>
            <option value="13:00">1:00 PM</option>
            <option value="14:00">2:00 PM</option>
            <option value="15:00">3:00 PM</option>
            <option value="16:00">4:00 PM</option>
            <option value="17:00">5:00 PM</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Message to Provider <span style="color:var(--red);">*</span></label>
        <textarea
          v-model="counterForm.message"
          class="form-input"
          :class="{ 'is-invalid': counterV$.message.$error }"
          placeholder="Explain your counter-proposal, alternative times, or any questions you have…"
          @blur="counterV$.message.$touch()"
        ></textarea>
        <div v-if="counterV$.message.$error" class="form-error">{{ counterFieldError('message') }}</div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.counter = false">Cancel</button>
        <button class="btn btn-primary" @click="submitCounter">
          <AegisIcon name="send" :size="14" /> Send Counter Proposal
        </button>
      </template>
    </AegisModal>

    <!-- Preview Listing Modal -->
    <AegisModal v-model="modals.preview" title="Listing Preview" size="sm">
      <p class="preview-hint">This is how your listing appears to other providers in search.</p>
      <div class="preview-svc-card">
        <!-- Header: icon + title + category -->
        <div class="preview-svc-head">
          <div class="preview-svc-icon">
            <AegisIcon :name="previewData.type_icon" :size="20" />
          </div>
          <div class="preview-svc-head-text">
            <div class="preview-svc-name">{{ previewData.title }}</div>
            <div class="preview-svc-category">{{ previewData.categoryLabel }}</div>
          </div>
        </div>
        <!-- Price -->
        <div class="preview-svc-price-row">
          <span class="preview-svc-price-amount">{{ previewData.price }}</span>
          <span v-if="previewData.price_unit" class="preview-svc-price-unit">/ {{ previewData.price_unit }}</span>
        </div>
        <!-- Description -->
        <div class="preview-svc-desc">{{ previewData.description }}</div>
        <!-- Meta pills -->
        <div class="preview-svc-meta">
          <span v-if="previewData.duration_min" class="preview-svc-pill">
            <AegisIcon name="clock" :size="11" />{{ previewData.duration_min }} min
          </span>
          <span v-if="previewData.format" class="preview-svc-pill">
            <AegisIcon name="monitor" :size="11" />{{ formatLabel(previewData.format) }}
          </span>
        </div>
        <!-- Footer: availability + CTA -->
        <div class="preview-svc-footer">
          <span class="preview-svc-avail" :class="previewData.availability">
            <AegisIcon name="circle-dot" :size="9" :filled="true" />
            {{ previewData.availability_label || (previewData.availability === 'limited' ? 'Limited Spots' : 'Slots Available') }}
          </span>
          <button class="preview-svc-req-btn btn btn-primary">
            <AegisIcon name="send" :size="12" /> Request
          </button>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.preview = false">Close</button>
        <button class="btn btn-primary" @click="modals.preview = false; modals.edit = true">
          <AegisIcon name="pencil" :size="14" /> Edit Listing
        </button>
      </template>
    </AegisModal>

    <!-- Manage Group Modal -->
    <AegisModal v-model="modals.manageGroup" title="Manage Group — Group Supervision" size="md">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div style="font-size:13px;font-weight:600;color:var(--text-2);">3 of 6 spots filled · Bi-weekly Monday · 90 min</div>
        <AegisBadge label="Active" variant="green" />
      </div>

      <div class="modal-section-label" style="margin-top:0;">Enrolled Providers</div>
      <div class="list-group" style="margin-bottom:14px;">
        <div v-for="member in groupMembers" :key="member.id" class="list-group-item">
          <div class="avatar avatar-sm">{{ member.initials }}</div>
          <div class="roster-info">
            <div class="roster-name">{{ member.name }}</div>
            <div class="roster-meta">{{ member.meta }}</div>
          </div>
          <AegisBadge label="Active" variant="green" />
          <button
            class="btn-icon"
            :data-tooltip="`Message ${member.first_name}`"
            :disabled="msgLoading === member.id"
            @click="openConversation(member.id)"
          >
            <AegisIcon name="message" :size="14" />
          </button>
          <button
            class="btn-icon btn-icon-danger"
            :data-tooltip="`Remove ${member.first_name} from group`"
            @click="confirmAction({ title: 'Remove Provider', btnLabel: 'Remove', type: 'danger' }, () => toast.info('Provider removed from group'))"
          >
            <AegisIcon name="trash" :size="14" />
          </button>
        </div>
      </div>

      <div class="modal-section-label">Open Spots ({{ 6 - groupMembers.length }} remaining)</div>
      <div class="alert alert-info">
        <AegisIcon name="info" :size="16" />
        <span>This group is discoverable in provider search. Providers can request to join and you'll approve each request manually.</span>
      </div>

      <div class="form-group" style="margin-top:14px;">
        <label class="form-label">Group Announcement (optional)</label>
        <textarea v-model="groupAnnouncement" class="form-input" style="min-height:70px;" placeholder="Send a message to all group members at once…"></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.manageGroup = false">Close</button>
        <button class="btn btn-outline" @click="sendGroupAnnouncement">
          <AegisIcon name="send" :size="13" /> Send Announcement
        </button>
        <button class="btn btn-primary" @click="modals.manageGroup = false; modals.edit = true">
          <AegisIcon name="pencil" :size="14" /> Edit Group Settings
        </button>
      </template>
    </AegisModal>

    <!-- Publish Listing Modal -->
    <AegisModal v-model="modals.publish" title="Publish Listing" size="sm">
      <div class="alert alert-success" style="margin-bottom:16px;">
        <AegisIcon name="check" :size="16" />
        <span>Your listing will be visible to providers in search immediately after publishing.</span>
      </div>
      <div class="setting-row" style="padding:6px 0;">
        <div class="setting-info">
          <div class="setting-label">Notify matching providers</div>
          <div class="setting-desc">Send a discovery alert to providers who match your specialty tags</div>
        </div>
        <AegisToggle v-model="publishForm.notify" />
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.publish = false">Cancel</button>
        <button class="btn btn-primary" @click="submitPublish">
          <AegisIcon name="check" :size="14" /> Publish Now
        </button>
      </template>
    </AegisModal>

    <!-- Reactivate Listing Modal -->
    <AegisModal v-model="modals.reactivate" title="Reactivate Listing" size="sm">
      <div class="alert alert-gold" style="margin-bottom:16px;">
        <AegisIcon name="alert-triangle" :size="16" />
        <span>This listing was paused. Reactivating will make it discoverable in provider search again.</span>
      </div>
      <div class="setting-row" style="padding:6px 0;">
        <div class="setting-info">
          <div class="setting-label">Restore previous availability</div>
          <div class="setting-desc">Re-enable your saved availability schedule for this listing</div>
        </div>
        <AegisToggle v-model="reactivateForm.restore_avail" />
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.reactivate = false">Cancel</button>
        <button class="btn btn-primary" @click="submitReactivate">
          <AegisIcon name="check" :size="14" /> Reactivate
        </button>
      </template>
    </AegisModal>

    <!-- Pause Listing Modal -->
    <AegisModal v-model="modals.pause" title="Pause Listing" size="sm">
      <div class="alert alert-warning" style="margin-bottom:16px;">
        <AegisIcon name="alert-triangle" :size="16" />
        <span>Pausing will hide this listing from provider search. Existing bookings and relationships are not affected.</span>
      </div>
      <div class="form-group">
        <label class="form-label">Reason for pausing (optional)</label>
        <select v-model="pauseForm.reason" class="form-select">
          <option value="">Select reason…</option>
          <option>At capacity — not accepting new clients</option>
          <option>Temporarily unavailable</option>
          <option>Updating listing details</option>
          <option>Other</option>
        </select>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.pause = false">Keep Active</button>
        <button class="btn btn-danger" @click="submitPause">
          <AegisIcon name="pause" :size="14" /> Pause Listing
        </button>
      </template>
    </AegisModal>

    <!-- Confirm Session & Release Payment Modal (CLIENT side) -->
    <AegisModal v-model="modals.completeSession" title="Confirm Session &amp; Release Payment" size="md">
      <template v-if="activeClientSession">

        <div class="page-note" style="margin-bottom:14px;">
          <AegisIcon name="info" :size="14" />
          <span>By confirming, you acknowledge the session took place and authorize payment to the provider.</span>
        </div>

        <div class="complete-session-summary">
          <div class="complete-session-row">
            <span class="complete-session-label">Provider</span>
            <span class="complete-session-value">{{ activeClientSession.practitioner_name }}</span>
          </div>
          <div class="complete-session-row">
            <span class="complete-session-label">Service</span>
            <span class="complete-session-value">{{ activeClientSession.service_title }}</span>
          </div>
          <div class="complete-session-row">
            <span class="complete-session-label">Scheduled</span>
            <span class="complete-session-value">{{ activeClientSession.datetime_label }}</span>
          </div>
          <div v-if="activeClientSession.amount_cents > 0" class="complete-session-row">
            <span class="complete-session-label">You will pay</span>
            <span class="complete-session-value complete-session-amount">{{ activeClientSession.amount }}</span>
          </div>
        </div>

        <div v-if="activeClientSession.amount_cents > 0" class="complete-session-payout">
          <div v-if="activeClientSession.practitioner_stripe_connected" class="payout-status payout-status--ready">
            <AegisIcon name="check-circle" :size="15" />
            <div>
              <div class="payout-status-title">Payment will be sent immediately</div>
              <div class="payout-status-desc">{{ activeClientSession.practitioner_name }} has a connected Stripe account. Funds transfer on confirmation.</div>
            </div>
          </div>
          <div v-else class="payout-status payout-status--warn">
            <AegisIcon name="alert-triangle" :size="15" />
            <div>
              <div class="payout-status-title">Provider payout pending</div>
              <div class="payout-status-desc">{{ activeClientSession.practitioner_name }} has not connected a Stripe account yet. Your payment will be held and released once they do.</div>
            </div>
          </div>
        </div>

        <p class="complete-session-confirm-text">
          This action is permanent. The session will be marked complete and the provider will be notified.
        </p>
      </template>

      <template #footer>
        <button class="btn btn-outline" @click="modals.completeSession = false">Cancel</button>
        <button class="btn btn-success" @click="submitCompleteSession">
          <AegisIcon name="check" :size="14" /> Confirm &amp; Release Payment
        </button>
      </template>
    </AegisModal>
    <!-- Invoice Modal -->
    <AegisModal v-model="modals.invoice" title="Session Invoice" size="sm">
      <template v-if="activeBooking">
        <div class="complete-session-summary">
          <div class="complete-session-row">
            <span class="complete-session-label">Provider</span>
            <span class="complete-session-value">{{ activeBooking.provider_name }}</span>
          </div>
          <div class="complete-session-row">
            <span class="complete-session-label">Service</span>
            <span class="complete-session-value">{{ activeBooking.service_title }}</span>
          </div>
          <div class="complete-session-row">
            <span class="complete-session-label">Date</span>
            <span class="complete-session-value">{{ activeBooking.datetime_label }}</span>
          </div>
          <div class="complete-session-row">
            <span class="complete-session-label">Duration</span>
            <span class="complete-session-value">{{ activeBooking.duration_label }}</span>
          </div>
          <div class="complete-session-row">
            <span class="complete-session-label">Amount</span>
            <span class="complete-session-value complete-session-amount">{{ activeBooking.amount }}</span>
          </div>
          <div class="complete-session-row">
            <span class="complete-session-label">Status</span>
            <span class="complete-session-value">
              <AegisBadge :label="statusLabel(activeBooking.status)" :variant="statusVariant(activeBooking.status)" />
            </span>
          </div>
        </div>
        <p class="complete-session-confirm-text" style="margin-top:12px;">
          This is a summary of the completed session. A full record is available in your
          <a :href="route('provider.finances.index')" class="link-inline">Finances</a> page.
        </p>
      </template>
      <template #footer>
        <button class="btn btn-outline" @click="modals.invoice = false">Close</button>
        <a :href="route('provider.finances.index')" class="btn btn-primary">
          <AegisIcon name="dollar-sign" :size="14" /> View in Finances
        </a>
      </template>
    </AegisModal>

    <!-- Client Invoice Modal (when I paid someone else) -->
    <AegisModal v-model="modals.clientInvoice" title="Session Receipt" size="sm">
      <template v-if="activeClientSession">
        <div class="complete-session-summary">
          <div class="complete-session-row">
            <span class="complete-session-label">Provider</span>
            <span class="complete-session-value">{{ activeClientSession.practitioner_name }}</span>
          </div>
          <div class="complete-session-row">
            <span class="complete-session-label">Service</span>
            <span class="complete-session-value">{{ activeClientSession.service_title }}</span>
          </div>
          <div class="complete-session-row">
            <span class="complete-session-label">Date</span>
            <span class="complete-session-value">{{ activeClientSession.datetime_label }}</span>
          </div>
          <div class="complete-session-row">
            <span class="complete-session-label">Amount Paid</span>
            <span class="complete-session-value complete-session-amount">{{ activeClientSession.amount }}</span>
          </div>
          <div class="complete-session-row">
            <span class="complete-session-label">Status</span>
            <span class="complete-session-value">
              <AegisBadge label="Completed" variant="green" />
            </span>
          </div>
        </div>
        <p class="complete-session-confirm-text" style="margin-top:12px;">
          Payment was sent to {{ activeClientSession.practitioner_name }}. A full record is in your
          <a :href="route('provider.finances.index')" class="link-inline">Finances</a> page.
        </p>
      </template>
      <template #footer>
        <button class="btn btn-outline" @click="modals.clientInvoice = false">Close</button>
        <a :href="route('provider.finances.index')" class="btn btn-primary">
          <AegisIcon name="dollar-sign" :size="14" /> View in Finances
        </a>
      </template>
    </AegisModal>

    <!-- Dismiss Request Modal -->
    <AegisModal v-model="modals.dismiss" title="Dismiss Request" size="sm">
      <div class="form-group">
        <label class="form-label">Reason for dismissing</label>
        <select v-model="dismissForm.reason" class="form-select">
          <option value="">Select a reason…</option>
          <option v-for="r in DISMISS_REASONS" :key="r" :value="r">{{ r }}</option>
        </select>
      </div>
      <div v-if="dismissForm.reason === 'Other'" class="form-group" style="margin-top:12px;">
        <label class="form-label">Please specify</label>
        <textarea v-model="dismissForm.otherReason" class="form-control" rows="3" placeholder="Briefly describe your reason…" />
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.dismiss = false">Cancel</button>
        <button class="btn btn-danger" :disabled="!dismissForm.reason" @click="submitDismiss">
          <AegisIcon name="x" :size="14" /> Dismiss Request
        </button>
      </template>
    </AegisModal>

    <!-- Cancel Session Modal -->
    <AegisModal v-model="modals.cancelSession" title="Cancel Session" size="sm">
      <div class="alert alert-warning" style="margin-bottom:16px;">
        <AegisIcon name="alert-triangle" :size="16" />
        <span>Cancelling will notify the provider immediately. This session is <strong>{{ activeBooking?.datetime_label }} — {{ activeBooking?.provider_name }}</strong>.</span>
      </div>
      <div class="form-group">
        <label class="form-label">Reason for cancellation <span style="color:var(--red);">*</span></label>
        <select
          v-model="cancelSessionForm.reason"
          class="form-select"
          :class="{ 'is-invalid': cancelV$.reason.$error }"
          @blur="cancelV$.reason.$touch()"
        >
          <option value="">Select reason…</option>
          <option>Provider unavailable — schedule conflict</option>
          <option>Provider requested cancellation</option>
          <option>Emergency or illness</option>
          <option>Rescheduling to a different time</option>
          <option>Ending the relationship</option>
          <option>Other</option>
        </select>
        <div v-if="cancelV$.reason.$error" class="form-error">{{ cancelFieldError('reason') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Note to provider (optional)</label>
        <textarea v-model="cancelSessionForm.note" class="form-input" style="min-height:70px;" placeholder="Let them know what happened and any next steps…"></textarea>
      </div>
      <div class="setting-row" style="padding:6px 0;">
        <div class="setting-info">
          <div class="setting-label">Offer to reschedule</div>
          <div class="setting-desc">Include a link so they can book another time</div>
        </div>
        <AegisToggle v-model="cancelSessionForm.offer_reschedule" />
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.cancelSession = false">Keep Session</button>
        <button class="btn btn-danger" @click="submitCancelSession">
          <AegisIcon name="x" :size="14" /> Cancel Session
        </button>
      </template>
    </AegisModal>

    <!-- Session Notes Modal -->
    <AegisModal v-model="modals.sessionNotes" title="Session Notes" size="md">
      <div style="margin-bottom:16px;">
        <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:2px;">{{ activeBooking?.provider_name }}</div>
        <div style="font-size:13px;font-weight:600;color:var(--text-3);">{{ activeBooking?.service_title }} · {{ activeBooking?.datetime_label }} · {{ activeBooking?.duration_label }} · {{ activeBooking?.amount }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Session Summary</label>
        <textarea v-model="notesForm.summary" class="form-input" style="min-height:100px;" placeholder="Key topics covered, progress notes, follow-up items…"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Action Items for Next Session</label>
        <textarea v-model="notesForm.action_items" class="form-input" style="min-height:70px;" placeholder="Tasks, readings, or goals to revisit…"></textarea>
      </div>
      <div class="setting-row" style="padding:6px 0;">
        <div class="setting-info">
          <div class="setting-label">Share summary with supervisee</div>
          <div class="setting-desc">They'll receive a read-only copy via Aegis messaging. Notes are otherwise private to you.</div>
        </div>
        <AegisToggle v-model="notesForm.share_with_supervisee" />
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.sessionNotes = false">Cancel</button>
        <button class="btn btn-primary" @click="submitNotes">
          <AegisIcon name="check" :size="14" /> Save Notes
        </button>
      </template>
    </AegisModal>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisToggle from '@/components/ui/AegisToggle.vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { useMessageButton } from '@/composables/useMessageButton'
import useVuelidate from '@vuelidate/core'
import { required, minLength } from '@vuelidate/validators'

// ── Props (stubbed for Prompt 2) ──────────────────────────────────────────
const props = defineProps({
  listings:          { type: Array,  default: () => [] },
  serviceRequests:   { type: Array,  default: () => [] },
  bookings:          { type: Array,  default: () => [] },
  stats:             { type: Object, default: () => ({ active_listings: 0, pending_requests: 0, sessions: 0, total_sessions: 0, revenue_label: '$0' }) },
  profileCompletion: { type: Number, default: 0 },
  servicesMode:      { type: Boolean, default: false },
  heroRating:        { type: String,  default: '—' },
  filters:           { type: Object, default: () => ({}) },
  outgoingRequests:  { type: Array,  default: () => [] },
  serviceProfile:    { type: Object, default: () => ({ headline: '', bio: '', years_experience: 0, specialties: [] }) },
  clientSessions:    { type: Array,  default: () => [] },
})

// ── Composables ───────────────────────────────────────────────────────────
const toast = useToast()
const { confirmAction } = useConfirm()
const { openConversation, loading: msgLoading } = useMessageButton()

// ── Tab state ─────────────────────────────────────────────────────────────
const VALID_TABS = ['listings','requests','outgoing','bookings','settings']
const activeTab = ref('listings')
onMounted(() => {
  const tab = new URLSearchParams(window.location.search).get('tab')
  if (tab && VALID_TABS.includes(tab)) activeTab.value = tab
})

const tabs = computed(() => [
  { key: 'listings',  label: 'My Listings',           icon: 'grid',     count: props.listings.length },
  { key: 'requests',  label: 'Service Requests',       icon: 'clock',    count: newRequests.value.length },
  { key: 'bookings',  label: 'Bookings & Sessions',    icon: 'calendar', count: props.stats?.sessions ?? props.bookings.length },
  { key: 'outgoing',  label: 'My Requests',             icon: 'send',     count: pendingOutgoing.value.length || null },
  { key: 'settings',  label: 'Settings',               icon: 'settings', count: null },
])

// ── Modal state ───────────────────────────────────────────────────────────
const modals = reactive({
  create: false, edit: false, accept: false, counter: false,
  preview: false, manageGroup: false, publish: false, reactivate: false,
  pause: false, cancelSession: false, sessionNotes: false,
  dismiss: false, completeSession: false, invoice: false, clientInvoice: false,
})

// ── Active item tracking ──────────────────────────────────────────────────
const activeService  = ref(null)
const activeRequest  = ref(null)
const activeBooking  = ref(null)
const activeClientSession = ref(null)

function setActiveService(s)  {
  activeService.value           = s
  editForm.title                = s.title ?? ''
  editForm.description          = s.description ?? ''
  editForm.category             = s.category ?? 'supervision'
  editForm.price_cents          = s.price_cents ?? null
  editForm.price_type           = s.price_type ?? 'session'
  editForm.duration_min         = s.duration_min ?? null
  editForm.format               = s.format ?? 'telehealth'
  editForm.availability         = s.availability ?? 'open'
  editForm.availability_label   = s.availability_label ?? ''
  editForm.status               = s.status ?? 'active'
  editDollars.value             = s.price_cents ? s.price_cents / 100 : null
}
function setActiveRequest(r)  {
  activeRequest.value = r
  acceptForm.session_date = r?.preferred_date ?? ''
  if (r?.preferred_time)     acceptForm.session_time = r.preferred_time
  if (r?.preferred_timezone) acceptForm.timezone     = r.preferred_timezone
}
function setActiveBooking(b)  { activeBooking.value = b }

// ── Listings tab — backend-driven search/filter ───────────────────────────
const listingSearch       = ref(props.filters?.q ?? '')
const listingTypeFilter   = ref(props.filters?.category ?? '')
const listingStatusFilter = ref(props.filters?.status ?? '')

// Listings are already filtered by backend; expose as-is
const filteredListings = computed(() => props.listings)

let searchTimer = null
function doSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    router.visit(route('provider.services.index'), {
      method: 'get',
      data: {
        q:        listingSearch.value || undefined,
        category: listingTypeFilter.value || undefined,
        status:   listingStatusFilter.value || undefined,
      },
      preserveState: true,
      preserveScroll: true,
      replace: true,
    })
  }, 350)
}

watch(listingSearch,       doSearch)
watch(listingTypeFilter,   doSearch)
watch(listingStatusFilter, doSearch)

// ── Requests tab ──────────────────────────────────────────────────────────
const newRequests     = computed(() => props.serviceRequests.filter(r => r.status === 'new'))
const historyRequests   = computed(() => props.serviceRequests.filter(r => r.status !== 'new'))
const pendingOutgoing   = computed(() => props.outgoingRequests.filter(r => r.status === 'new'))
const outgoingSearch    = ref('')
const filteredOutgoing  = computed(() => {
  const q = outgoingSearch.value.toLowerCase()
  if (!q) return props.outgoingRequests
  return props.outgoingRequests.filter(r =>
    r.provider_name.toLowerCase().includes(q) ||
    r.service_title.toLowerCase().includes(q)
  )
})

// ── Bookings tab ──────────────────────────────────────────────────────────
const bookingSearch        = ref('')
const bookingServiceFilter = ref('')
const bookingDateRange     = ref('this_month')
const bookingPage          = ref(1)
const bookingPageCount     = computed(() => Math.max(1, Math.ceil(props.bookings.length / 10)))

const bookingPeriodLabel = computed(() => {
  const map = { this_month: 'This Month', last_month: 'Last Month', last_60: 'Last 60 Days', last_90: 'Last 90 Days', this_year: 'This Year', all: 'All Time' }
  return map[bookingDateRange.value] ?? 'This Month'
})

// ── Preview modal ─────────────────────────────────────────────────────────
const previewData = reactive({
  title: '', description: '', price: '', price_unit: '',
  type_icon: 'briefcase', category: '', categoryLabel: '',
  duration_min: null, format: '', availability: 'open', availability_label: '',
})

const categoryLabelMap = {
  supervision: 'Supervision', consultation: 'Consultation',
  training: 'Training', coaching: 'Coaching',
  practice_continuity: 'Practice Continuity', other: 'Other',
}

function formatLabel(fmt) {
  if (!fmt) return ''
  if (fmt === 'telehealth')  return 'Telehealth'
  if (fmt === 'in_person')   return 'In-Person'
  if (fmt === 'both')        return 'Telehealth or In-Person'
  return fmt
}

function openPreview(s) {
  previewData.title              = s.title
  previewData.description        = s.description
  previewData.price              = s.price
  previewData.price_unit         = s.price_unit?.replace(/^\/\s*/, '') ?? 'session'
  previewData.type_icon          = s.type_icon || 'briefcase'
  previewData.category           = s.category ?? ''
  previewData.categoryLabel      = categoryLabelMap[s.category] ?? (s.service_type ?? '')
  previewData.duration_min       = s.duration_min ?? null
  previewData.format             = s.format ?? ''
  previewData.availability       = s.availability ?? 'open'
  previewData.availability_label = s.availability_label ?? ''
  modals.preview = true
}

// ── Service type options ──────────────────────────────────────────────────
const serviceTypeOptions = [
  { key: 'supervision',        label: 'Supervision',         icon: 'graduation-cap', desc: 'Individual or group' },
  { key: 'consultation',       label: 'Consultation',        icon: 'message',        desc: 'Case or peer' },
  { key: 'training',           label: 'Training',            icon: 'book-open',      desc: 'Workshop or series' },
  { key: 'coaching',           label: 'Coaching',            icon: 'leaf',           desc: 'Practice or career' },
  { key: 'practice_continuity',label: 'Practice Continuity', icon: 'shield',         desc: 'Continuity Steward / succession' },
  { key: 'other',              label: 'Other',               icon: 'sparkles',       desc: 'Custom service' },
]



// ── Create form + validation ──────────────────────────────────────────────
const createDollars = ref(null)
const createForm = reactive({
  category: 'supervision', title: '', description: '',
  duration_min: 60, format: 'telehealth', price_cents: null,
  price_type: 'session', sliding_scale: false,
  availability: 'open', availability_label: '',
  is_public: true,
})
watch(createDollars, (v) => { createForm.price_cents = v != null ? Math.round(Number(v) * 100) : null })

const createRules = {
  title:       { required },
  description: { required },
  // price_cents not required for draft
}
const createV$ = useVuelidate(createRules, createForm)

function createFieldError(field) {
  const e = createV$.value[field].$errors[0]
  return e?.$message ?? ''
}

async function submitCreate(status) {
  if (status === 'active') {
    const ok = await createV$.value.$validate()
    if (!ok) return
  }
  router.post(route('provider.services.store'), {
    title: createForm.title, description: createForm.description,
    category: createForm.category, price_cents: createForm.price_cents,
    price_type: createForm.price_type, duration_min: createForm.duration_min,
    format: createForm.format, availability: createForm.availability,
    availability_label: createForm.availability_label, is_public: createForm.is_public,
    status,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      modals.create = false
      toast.success(status === 'active' ? 'Listing published!' : 'Saved as draft.')
      createV$.value.$reset()
      createDollars.value = null
    },
  })
}



// ── Edit form + validation ────────────────────────────────────────────────
const editDollars = ref(null)
const editForm = reactive({
  title: '', description: '', category: 'supervision',
  price_cents: null, price_type: 'session',
  duration_min: null, format: 'telehealth',
  availability: 'open', availability_label: '',
  status: 'active',
})
watch(editDollars, (v) => { editForm.price_cents = v != null ? Math.round(Number(v) * 100) : null })

const editRules = { title: { required } }
const editV$ = useVuelidate(editRules, editForm)

function editFieldError(field) {
  const e = editV$.value[field].$errors[0]
  return e?.$message ?? ''
}

async function submitEdit() {
  const ok = await editV$.value.$validate()
  if (!ok) return
  if (!activeService.value?.id) { toast.error('No service selected.'); return }
  router.put(route('provider.services.update', { service: activeService.value.id }), {
    title: editForm.title, description: editForm.description,
    category: editForm.category, price_cents: editForm.price_cents,
    price_type: editForm.price_type, duration_min: editForm.duration_min,
    format: editForm.format, availability: editForm.availability,
    availability_label: editForm.availability_label, status: editForm.status,
  }, {
    preserveScroll: true,
    onSuccess: () => { modals.edit = false; toast.success('Changes saved!') },
  })
}

function deleteListing() {
  if (!activeService.value?.id) { toast.error('No service selected.'); return }
  confirmAction({ title: 'Delete Listing', btnLabel: 'Delete', type: 'danger' }, () => {
    router.delete(route('provider.services.destroy', { service: activeService.value.id }), {
      preserveScroll: true,
      onSuccess: () => { modals.edit = false; toast.info('Listing deleted.') },
    })
  })
}

function deleteServiceFromCard() {
  if (!activeService.value?.id) { toast.error('No service selected.'); return }
  router.delete(route('provider.services.destroy', { service: activeService.value.id }), {
    preserveScroll: true,
    onSuccess: () => toast.info('Service deleted.'),
  })
}

function resumeService() {
  if (!activeService.value?.id) return
  router.put(route('provider.services.update', { service: activeService.value.id }), { status: 'active' }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Listing resumed.'),
  })
}

// ── Accept form ───────────────────────────────────────────────────────────
const acceptForm = reactive({ session_date: '', session_time: '10:00', timezone: 'America/New_York', format: 'Virtual (Telehealth)', note: '', recurring: true })
const todayDate = new Date().toISOString().split('T')[0]

function submitAccept() {
  if (!activeRequest.value?.service_id || !activeRequest.value?.id) {
    toast.error('No request selected.'); return
  }
  if (!acceptForm.session_date) {
    toast.error('Please select a session date.'); return
  }
  router.post(route('provider.services.request.accept', { service: activeRequest.value.service_id, serviceRequest: activeRequest.value.id }), {
    session_date: acceptForm.session_date,
    session_time: acceptForm.session_time,
    timezone:     acceptForm.timezone,
    format:       acceptForm.format,
    note:         acceptForm.note,
    recurring:    acceptForm.recurring,
  }, {
    preserveScroll: true,
    onSuccess: () => { modals.accept = false; toast.success('Request accepted. The practitioner has been notified.') },
  })
}

function submitCompleteSession() {
  if (!activeClientSession.value?.id) return
  router.post(route('provider.services.session.complete', { session: activeClientSession.value.id }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      modals.completeSession = false
      const hasConnect = activeClientSession.value?.practitioner_stripe_connected
      const name = activeClientSession.value?.practitioner_name ?? 'the provider'
      toast.success(hasConnect
        ? 'Session confirmed. Payment sent to ' + name + '.'
        : 'Session confirmed. Payment will release once ' + name + ' connects their Stripe account.'
      )
      activeClientSession.value = null
    },
  })
}

function withdrawOutgoingRequest(id) {
  confirmAction({
    title: 'Withdraw Request',
    message: 'Withdraw this service request? The provider will be notified.',
    btnLabel: 'Withdraw',
    type: 'danger',
  }, () => {
    router.delete(route('provider.services.request.withdraw', { serviceRequest: id }), {
      preserveScroll: true,
      onSuccess: () => toast.info('Request withdrawn.'),
    })
  })
}

const dismissForm = reactive({ reason: '', otherReason: '' })
const DISMISS_REASONS = [
  'Scheduling conflict',
  'Outside my scope of practice',
  'Client is not a good fit',
  'Already at full capacity',
  'Request is incomplete or unclear',
  'Other',
  'Prefer not to say',
]

function submitDismiss() {
  if (!activeRequest.value?.id) return
  const req = activeRequest.value
  if (!req?.service_id) { toast.error('Cannot dismiss: request data missing.'); return }
  const reason = dismissForm.reason === 'Other'
    ? (dismissForm.otherReason.trim() || 'Other')
    : (dismissForm.reason || 'Dismissed by practitioner')
  router.post(route('provider.services.request.decline', { service: req.service_id, serviceRequest: req.id }), { reason }, {
    preserveScroll: true,
    onSuccess: () => { modals.dismiss = false; toast.info('Request dismissed.') },
  })
}

function dismissRequest(id) {
  const req = props.serviceRequests.find(r => r.id === id)
  if (!req?.service_id) { toast.error('Cannot dismiss: request data missing.'); return }
  router.post(route('provider.services.request.decline', { service: req.service_id, serviceRequest: id }), { reason: 'Dismissed by practitioner' }, {
    preserveScroll: true,
    onSuccess: () => toast.info('Request dismissed.'),
  })
}

// ── Counter form + validation ─────────────────────────────────────────────
const counterForm = reactive({ proposed_date: '', proposed_time: '10:00', message: '' })
const counterRules = { message: { required } }
const counterV$ = useVuelidate(counterRules, counterForm)
function counterFieldError(field) { return counterV$.value[field].$errors[0]?.$message ?? '' }

async function submitCounter() {
  const ok = await counterV$.value.$validate()
  if (!ok) return
  if (!activeRequest.value?.service_id || !activeRequest.value?.id) {
    toast.error('No request selected.'); return
  }
  router.post(route('provider.services.request.decline', {
    service: activeRequest.value.service_id,
    serviceRequest: activeRequest.value.id,
  }), {
    reason: `Counter proposal for ${counterForm.proposed_date} at ${counterForm.proposed_time} — ${counterForm.message}`,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      modals.counter = false
      toast.success('Counter proposal sent.')
      counterForm.proposed_date = ''
      counterForm.proposed_time = '10:00'
      counterForm.message = ''
      counterV$.value.$reset()
    },
  })
}

// ── Publish form ──────────────────────────────────────────────────────────
const publishForm = reactive({ notify: true })

function submitPublish() {
  if (!activeService.value?.id) { toast.error('No service selected.'); return }
  router.put(route('provider.services.update', { service: activeService.value.id }), { status: 'active' }, {
    preserveScroll: true,
    onSuccess: () => { modals.publish = false; toast.success('Listing published!') },
  })
}

// ── Reactivate form ───────────────────────────────────────────────────────
const reactivateForm = reactive({ restore_avail: true })

function submitReactivate() {
  if (!activeService.value?.id) return
  router.put(route('provider.services.update', { service: activeService.value.id }), { status: 'active' }, {
    preserveScroll: true,
    onSuccess: () => { modals.reactivate = false; toast.success('Listing reactivated!') },
  })
}

// ── Pause form ────────────────────────────────────────────────────────────
const pauseForm = reactive({ reason: '' })

function submitPause() {
  if (!activeService.value?.id) return
  router.put(route('provider.services.update', { service: activeService.value.id }), { status: 'paused', pause_reason: pauseForm.reason }, {
    preserveScroll: true,
    onSuccess: () => { modals.pause = false; toast.warning('Listing paused.') },
  })
}

// ── Cancel session form + validation ──────────────────────────────────────
const cancelSessionForm = reactive({ reason: '', note: '', offer_reschedule: true })
const cancelRules = { reason: { required } }
const cancelV$ = useVuelidate(cancelRules, cancelSessionForm)
function cancelFieldError(field) { return cancelV$.value[field].$errors[0]?.$message ?? '' }

async function submitCancelSession() {
  const ok = await cancelV$.value.$validate()
  if (!ok) return
  if (!activeBooking.value?.id) { toast.error('No session selected.'); return }
  router.post(route('provider.services.session.cancel', { session: activeBooking.value.id }), cancelSessionForm, {
    preserveScroll: true,
    onSuccess: () => { modals.cancelSession = false; toast.warning(`Session cancelled — ${activeBooking.value?.provider_name} notified.`) },
  })
}

// ── Session notes form ────────────────────────────────────────────────────
const notesForm = reactive({ summary: '', action_items: '', share_with_supervisee: false })

function submitNotes() {
  if (!activeBooking.value?.id) { toast.error('No session selected.'); return }
  router.post(route('provider.services.session.notes', { session: activeBooking.value.id }), notesForm, {
    preserveScroll: true,
    onSuccess: () => { modals.sessionNotes = false; toast.success('Notes saved.') },
  })
}

// ── Group management ──────────────────────────────────────────────────────
const groupMembers = computed(() => {
  if (!activeService.value) return []
  return props.bookings
    .filter(b => b.service_id === activeService.value?.id && b.status === 'scheduled')
    .map(b => ({
      id:         b.id,
      initials:   initials(b.provider_name),
      name:       b.provider_name,
      first_name: (b.provider_name ?? '').split(' ')[0],
      meta:       b.service_title,
    }))
})
const groupAnnouncement = ref('')

function sendGroupAnnouncement() {
  toast.success('Announcement sent to all members.')
  groupAnnouncement.value = ''
}

// ── Settings / Profile ────────────────────────────────────────────────────
const profileForm = reactive({
  headline:         props.serviceProfile?.headline ?? '',
  years_experience: props.serviceProfile?.years_experience ?? 0,
  bio:              props.serviceProfile?.bio ?? '',
  specialties:      [...(props.serviceProfile?.specialties ?? [])],
})
const newSpecialty = ref('')

function addSpecialty() {
  const v = newSpecialty.value.trim()
  if (!v) return
  profileForm.specialties.push(v)
  newSpecialty.value = ''
  toast.success('Specialty added.', 1500)
}

function removeSpecialty(i) {
  profileForm.specialties.splice(i, 1)
}

function saveProfile() {
  router.put(route('provider.profile.services'), {
    services:         profileForm.specialties,
    service_bio:      profileForm.bio,
    service_headline: profileForm.headline,
    years_experience: profileForm.years_experience,
  }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Services profile saved!'),
  })
}

function resetProfile() {
  profileForm.headline         = props.serviceProfile?.headline ?? ''
  profileForm.years_experience = props.serviceProfile?.years_experience ?? 0
  profileForm.bio              = props.serviceProfile?.bio ?? ''
  profileForm.specialties      = [...(props.serviceProfile?.specialties ?? [])]
  toast.info('Changes discarded.')
}

// ── Helpers ───────────────────────────────────────────────────────────────
function initials(name) {
  return (name || '').split(' ').slice(0, 2).map(p => p[0] ?? '').join('').toUpperCase() || '?'
}

function statusLabel(s) {
  return { completed: 'Completed', upcoming: 'Upcoming', cancelled: 'Cancelled', accepted: 'Accepted', declined: 'Declined', pending: 'Pending', new: 'New' }[s] ?? s
}

function statusVariant(s) {
  return { completed: 'green', upcoming: 'blue', cancelled: 'neutral', accepted: 'green', declined: 'neutral', pending: 'gold', new: 'gold' }[s] ?? 'neutral'
}
</script>

<style scoped>
/* ── TOOLBAR ── */
.complete-session-summary {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 14px 16px;
  margin-bottom: 14px;
}
.complete-session-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 12px;
  padding: 5px 0;
  border-bottom: 1px solid var(--border);
}
.complete-session-row:last-child { border-bottom: none; }
.complete-session-label { font-size: 12px; color: var(--text-4); flex-shrink: 0; }
.complete-session-value { font-size: 13px; font-weight: 600; color: var(--text); text-align: right; }
.complete-session-amount { font-size: 15px; color: var(--green); }
.complete-session-payout {
  margin-bottom: 14px;
}
.payout-status {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 14px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
}
.payout-status--ready {
  background: var(--green-bg, rgba(34,197,94,0.07));
  border-color: var(--green);
  color: var(--green);
}
.payout-status--ready .payout-status-title { color: var(--green); }
.payout-status--warn {
  background: var(--amber-bg, rgba(245,158,11,0.07));
  border-color: var(--gold);
  color: var(--gold-dark);
}
.payout-status--warn .payout-status-title { color: var(--gold-dark); }
.payout-status-title { font-size: 13px; font-weight: 700; margin-bottom: 2px; }
.payout-status-desc { font-size: 12px; color: var(--text-2); line-height: 1.5; }
.payout-status-desc .link-inline { color: var(--gold-dark); font-weight: 600; }
.complete-session-confirm-text {
  font-size: 12px;
  color: var(--text-3);
  line-height: 1.6;
  margin: 0;
}

.page-note {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-left: 3px solid var(--gold);
  border-radius: var(--radius);
  padding: 10px 14px;
  color: var(--text-2);
  font-size: 13px;
  line-height: 1.5;
  margin-bottom: 16px;
}
.page-note .aegis-icon { flex-shrink: 0; margin-top: 1px; color: var(--gold); }

.svc-toolbar {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: 10px;
  margin-bottom: 18px;
  align-items: center;
}
.svc-toolbar .search-wrap {
  grid-column: span 6;
  position: relative;
  min-width: 0;
}
.svc-toolbar > .form-select { grid-column: span 3; min-width: 0; }
.svc-toolbar > :deep(.ts-wrapper) { grid-column: span 3; min-width: 0; width: 100% !important; overflow: visible !important; }
.svc-toolbar :deep(.ts-wrapper .ts-control) { width: 100% !important; box-sizing: border-box !important; }
.svc-toolbar :deep(.ts-wrapper .ts-dropdown) { width: 100% !important; }
.svc-toolbar :deep(.ts-dropdown) { min-width: 160px; width: auto; }
.svc-toolbar .search-wrap :deep(svg),
.svc-toolbar .search-wrap .aegis-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-4);
  pointer-events: none;
}
.svc-toolbar .search-wrap .form-control { padding-left: 38px; width: 100%; }
@media (max-width: 720px) {
  .svc-toolbar { grid-template-columns: 1fr; }
  .svc-toolbar .search-wrap,
  .svc-toolbar > .form-select { grid-column: 1 / -1; }
}

/* ── SERVICE CARDS GRID ── */
.services-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 16px;
  margin-bottom: 22px;
}
/* push metrics + footer to bottom */
.services-grid .card { display: flex; flex-direction: column; }
.services-grid .card .card-body { flex: 1; }
.svc-type-icon {
  width: 48px; height: 48px;
  border-radius: var(--radius-sm);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
}
.svc-description {
  font-size: 13px; color: var(--text-2); line-height: 1.55;
  margin-bottom: 14px;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.svc-meta-row { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; }
.svc-meta-item {
  display: flex; align-items: center; gap: 5px;
  font-size: 12px; color: var(--text-3); font-weight: 600;
}
.svc-price-row {
  display: flex; align-items: baseline; gap: 6px;
  padding: 10px 14px;
  background: var(--surface-2);
  border-radius: var(--radius-sm);
}
.svc-price { font-family: var(--font-serif); font-size: 22px; font-weight: 700; color: var(--text); }
.svc-price-unit { font-size: 12px; color: var(--text-3); font-weight: 600; }
.svc-price-note { font-size: 11px; color: var(--text-4); margin-left: auto; font-weight: 600; }
.svc-card-metrics {
  display: grid; grid-template-columns: repeat(3,1fr);
  gap: 1px; background: var(--border);
  border-top: 1px solid var(--border);
}
.svc-metric { background: var(--surface-2); padding: 10px 14px; text-align: center; }
.svc-metric-val { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); }
.svc-metric-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-4); margin-top: 2px; }

/* ── section-badge: match badge-pill style ── */
.section-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 18px; height: 18px; padding: 0 5px;
  border-radius: 100px;
  font-size: 10px; font-weight: 700; line-height: 1;
  background: var(--surface-3); color: var(--text-3);
  text-transform: none; letter-spacing: 0;
  vertical-align: middle; margin-left: 6px;
}

/* ── TAB BADGE RED ALERT ── */
.badge-pill {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 18px; height: 18px; padding: 0 5px;
  border-radius: 100px;
  font-size: 10px; font-weight: 700; line-height: 1;
  background: var(--surface-3); color: var(--text-3);
}


/* ── REQUESTS LIST ── */
.requests-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 22px; }
.request-card.new { border-left: 3px solid var(--gold-dark); }
.req-info { flex: 1; min-width: 180px; }
.req-name { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 2px; }
.req-detail { font-size: 12px; color: var(--text-3); font-weight: 600; }
.req-service { flex: 1; min-width: 160px; }
.req-service-name { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 2px; }
.req-service-type { font-size: 10px; color: var(--text-4); text-transform: uppercase; letter-spacing: 0.4px; font-weight: 700; }
.req-date { font-size: 12px; color: var(--text-3); min-width: 100px; text-align: right; flex-shrink: 0; font-weight: 600; }
.req-actions { display: flex; gap: 8px; flex-shrink: 0; }

/* ── CARD FOOTER ── */
.card-footer { display: flex; gap: 6px; align-items: center; }

/* ── TABLE / CARD FLUSH ── */
.card-flush .card-body { padding: 0; }
.card-flush .table-wrap { border: none; border-radius: 0; box-shadow: none; }
.card-flush .pager { padding-left: 16px; padding-right: 16px; }
.td-provider { display: flex; align-items: center; gap: 10px; }
.td-name { font-weight: 700; color: var(--text); font-size: 13px; }
a.link-name, .req-name.link-name { color: var(--gold-dark); text-decoration: none; font-weight: 700; }
a.link-name:hover, .req-name.link-name:hover { text-decoration: underline; }
.td-cred { font-size: 11px; color: var(--text-3); font-weight: 600; }

/* ── TABLE CONTRAST ── */
.table-wrap { border-color: var(--border-dark); box-shadow: var(--shadow); background: var(--surface); }
.table-wrap .table thead tr { background: var(--surface-3); }
.table-wrap .table thead th {
  color: var(--text); font-weight: 700; font-size: 11px;
  letter-spacing: 0.5px; text-transform: uppercase;
  border-bottom: 1px solid var(--border-dark); padding: 11px 14px;
}
.table-wrap .table tbody tr { background: var(--surface); }
.table-wrap .table tbody tr:hover { background: var(--badge-bg-gold); }
.table-wrap .table tbody td { border-bottom: 1px solid var(--border); color: var(--text); }
.table-wrap .table tbody tr:last-child td { border-bottom: none; }

/* ── SETTINGS ── */
.settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 22px; }
.card .card-header.is-settings { background: var(--surface-2); }

/* ── AVAILABILITY GRID ── */
.avail-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
.avail-day {
  text-align: center; padding: 8px 4px;
  border-radius: var(--radius-sm); border: 1.5px solid var(--border);
  cursor: pointer; transition: background var(--transition);
  background: var(--surface);
}
.avail-day:hover         { background: var(--badge-bg-gold); }
.avail-day .day-name { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-4); margin-bottom: 4px; }
.avail-day .day-slots { font-size: 11px; color: var(--text-3); font-weight: 600; }
.avail-day.on            { background: var(--badge-bg-gold); }
.avail-day.on .day-name  { color: var(--gold-dark); }
.avail-day.on .day-slots { color: var(--gold-dark); font-weight: 700; }

/* ── PRICING OPTIONS ── */
.pricing-options { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; margin-bottom: 14px; }
.pricing-opt {
  border: 1.5px solid var(--border); border-radius: var(--radius);
  padding: 12px; text-align: center; cursor: pointer;
  transition: background var(--transition); background: var(--surface);
}
.pricing-opt:hover    { background: var(--badge-bg-gold); }
.pricing-opt.selected { background: var(--badge-bg-gold); }
.pricing-opt-label { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 3px; display: inline-flex; align-items: center; gap: 6px; }
.pricing-opt.selected .pricing-opt-label { color: var(--gold-dark); }
.pricing-opt-desc  { font-size: 11px; color: var(--text-3); font-weight: 600; }

/* ── ROSTER ── */
.roster-info { flex: 1; min-width: 0; }
.roster-name { font-size: 13px; font-weight: 700; color: var(--text); }
.roster-meta { font-size: 11px; color: var(--text-3); font-weight: 600; }

/* ── RESPONSIVE ── */
@media (max-width: 900px) {
  .services-grid { grid-template-columns: 1fr; }
  .settings-grid { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
  .avail-grid { grid-template-columns: repeat(4,1fr); }
  .pricing-options { grid-template-columns: 1fr 1fr; }
}

/* ── PREVIEW MODAL — pp-svc-card replica ── */
.preview-hint {
  font-size: 12.5px; font-weight: 600; color: var(--text-3);
  margin-bottom: 16px;
}
.preview-svc-card {
  display: flex; flex-direction: column;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}
.preview-svc-head {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 18px 18px 0;
}
.preview-svc-icon {
  width: 40px; height: 40px;
  border-radius: var(--radius);
  background: var(--badge-bg-gold); color: var(--gold-dark);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.preview-svc-head-text { min-width: 0; }
.preview-svc-name {
  font-family: var(--font-serif);
  font-size: 14px; font-weight: 700;
  color: var(--text); line-height: 1.3; margin-bottom: 2px;
}
.preview-svc-category {
  font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.7px;
  color: var(--gold-dark);
}
.preview-svc-price-row {
  display: flex; align-items: baseline; gap: 4px;
  padding: 10px 18px 0;
}
.preview-svc-price-amount {
  font-family: var(--font-serif);
  font-size: 22px; font-weight: 700;
  color: var(--text); line-height: 1;
}
.preview-svc-price-unit {
  font-size: 12px; font-weight: 600; color: var(--text-3);
}
.preview-svc-desc {
  font-size: 12.5px; color: var(--text-2);
  line-height: 1.6; padding: 10px 18px; flex: 1;
}
.preview-svc-meta {
  display: flex; flex-wrap: wrap; gap: 6px;
  padding: 0 18px 14px;
}
.preview-svc-pill {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 600; color: var(--text-3);
  background: var(--surface-2); border: 1px solid var(--border);
  padding: 3px 9px; border-radius: 100px;
}
.preview-svc-footer {
  display: flex; align-items: center; justify-content: space-between;
  gap: 8px; padding: 12px 18px;
  border-top: 1px solid var(--border);
  background: var(--surface-2);
}
.preview-svc-avail {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.5px;
}
.preview-svc-avail.open    { color: var(--green-dark, #2e7d32); }
.preview-svc-avail.limited { color: var(--gold-dark); }
.preview-svc-req-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 7px 16px; font-size: 12px; font-weight: 700;
  white-space: nowrap; flex-shrink: 0;
}
</style>