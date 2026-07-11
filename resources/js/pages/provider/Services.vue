<!--
  pages/provider/Services.vue — Wave 5 rebuild.

  Tabs: Explore | My Listings | Service Requests | Bookings & Sessions | My Requests | Settings

  Wave 5 additions:
    - "Explore" tab: filter sidebar + ServiceExploreCard grid + infinite scroll
    - "My Requests" → "My Booked Sessions" section uses SessionInvoiceCard (not table)
      with PayDepositModal / PayBalanceModal / RequestRefundModal / SessionInvoiceModal
    - "Bookings & Sessions" keeps table but adds payment_status badge + ReviewRefundRequestModal
    - Accept Request modal now includes CounterOfferInline for price negotiation
    - All old "Confirm & Pay" logic replaced by PayBalanceModal
    - Stats chip added for pending_refunds count

  Local imports (not globally registered):
    AegisToggle, AegisPagination, SessionInvoiceCard, ServiceExploreCard,
    CounterOfferInline, SessionInvoiceModal, PayDepositModal, PayBalanceModal,
    RequestRefundModal, ReviewRefundRequestModal
-->
<template>
  <AppLayout>
    <Head title="My Services — Aegis" />

    <!-- ── HERO ───────────────────────────────────────────────────────── -->
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

    <!-- ── STAT CHIPS ──────────────────────────────────────────────────── -->
    <div class="stat-chips-row">
      <AegisStatChip icon="grid"     :value="stats?.active_listings ?? 0"    label="Active Listings" />
      <AegisStatChip icon="bell"     :value="stats?.pending_requests ?? 0"   label="Pending Requests" />
      <AegisStatChip icon="calendar" :value="stats?.sessions ?? 0"           label="Sessions This Month" />
      <AegisStatChip icon="dollar"   :value="stats?.revenue_label ?? '$0'"   label="Revenue This Month" />
      <AegisStatChip v-if="(stats?.pending_refunds ?? 0) > 0" icon="alert-circle" :value="stats.pending_refunds" label="Refund Requests" />
    </div>

    <!-- ── SIDEBAR + CONTENT LAYOUT ────────────────────────────────────── -->
    <div class="svc-layout">

      <!-- LEFT NAV SIDEBAR -->
      <nav class="page-sidebar svc-sidebar" role="tablist" aria-label="Services sections">

        <div class="page-sidebar-group">
          <div class="page-sidebar-label">Discover</div>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'explore' }" @click="activeTab = 'explore'">
            <span class="page-sidebar-icon"><AegisIcon name="search" :size="15" /></span>
            Browse Services
          </button>
        </div>

        <div class="page-sidebar-group">
          <div class="page-sidebar-label">My Services</div>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'listings' }" @click="activeTab = 'listings'">
            <span class="page-sidebar-icon"><AegisIcon name="grid" :size="15" /></span>
            My Listings
            <span v-if="listings.length > 0" class="page-sidebar-badge">{{ listings.length }}</span>
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'requests' }" @click="activeTab = 'requests'">
            <span class="page-sidebar-icon"><AegisIcon name="clock" :size="15" /></span>
            Service Requests
            <span v-if="newRequests.length + incomingRefundRequests.filter(r => r.is_actionable).length > 0" class="page-sidebar-badge">{{ newRequests.length + incomingRefundRequests.filter(r => r.is_actionable).length }}</span>
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'bookings' }" @click="activeTab = 'bookings'">
            <span class="page-sidebar-icon"><AegisIcon name="calendar" :size="15" /></span>
            Bookings &amp; Sessions
            <span v-if="stats?.sessions > 0" class="page-sidebar-badge">{{ stats.sessions }}</span>
          </button>
        </div>

        <div class="page-sidebar-group">
          <div class="page-sidebar-label">As a Client</div>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'outgoing' }" @click="activeTab = 'outgoing'">
            <span class="page-sidebar-icon"><AegisIcon name="send" :size="15" /></span>
            My Requests
            <span v-if="pendingClientActions > 0" class="page-sidebar-badge">{{ pendingClientActions }}</span>
          </button>
        </div>

        <div class="page-sidebar-group">
          <div class="page-sidebar-label">Account</div>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'settings' }" @click="activeTab = 'settings'">
            <span class="page-sidebar-icon"><AegisIcon name="settings" :size="15" /></span>
            Settings
          </button>
        </div>

      </nav>

      <!-- CONTENT AREA -->
      <div class="svc-content">

    <!-- ══════════════════════════════════════════════════════════════════
         TAB 0: EXPLORE SERVICES
    ══════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'explore'">

      <div class="page-note" style="margin-bottom:16px">
        <AegisIcon name="search" :size="14" />
        <span>Browse clinical services offered by other practitioners — supervision, consultation, training, coaching, and more. All charges route directly to the provider's Stripe account.</span>
      </div>

      <!-- ── INLINE FILTER BAR ─────────────────────────────────────────── -->
      <div class="explore-filter-bar">

        <!-- Row 1: Search — full width -->
        <div class="explore-filter-row explore-filter-row--search">
          <div class="explore-filter-search">
            <AegisIcon name="search" :size="14" />
            <input
              v-model="exploreFilters.q"
              type="text"
              class="form-control"
              placeholder="Search services, providers, specialties…"
              @keydown.enter.prevent="doExploreSearch"
            />
          </div>
        </div>

        <!-- Row 2: Dropdowns -->
        <div class="explore-filter-row explore-filter-row--dropdowns">
          <div class="explore-filter-select-wrap" :class="{ 'has-value': exploreFilters.category }">
            <AegisIcon name="grid" :size="13" />
            <select v-model="exploreFilters.category" class="form-select explore-filter-select" @change="doExploreSearch">
              <option value="">All Categories</option>
              <option v-for="opt in serviceCategories" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>

          <div class="explore-filter-select-wrap" :class="{ 'has-value': exploreFilters.format }">
            <AegisIcon name="monitor" :size="13" />
            <select v-model="exploreFilters.format" class="form-select explore-filter-select" @change="doExploreSearch">
              <option value="">Any Format</option>
              <option value="telehealth">Virtual</option>
              <option value="in_person">In-Person</option>
              <option value="both">Virtual &amp; In-Person</option>
            </select>
          </div>

          <div class="explore-filter-select-wrap" :class="{ 'has-value': exploreFilters.availability }">
            <AegisIcon name="calendar" :size="13" />
            <select v-model="exploreFilters.availability" class="form-select explore-filter-select" @change="doExploreSearch">
              <option value="">Any Availability</option>
              <option value="open">Open — accepting</option>
              <option value="limited">Limited spots</option>
            </select>
          </div>
        </div>

        <!-- Row 3: Result count + clear -->
        <div class="explore-filter-row explore-filter-row--meta">
          <span class="explore-count">{{ exploreMeta.total ?? 0 }} result{{ (exploreMeta.total ?? 0) !== 1 ? 's' : '' }}</span>
          <button
            v-if="exploreFilters.q || exploreFilters.category || exploreFilters.format || exploreFilters.availability"
            type="button"
            class="explore-clear-btn"
            @click="clearExploreFilters"
          >
            <AegisIcon name="x" :size="11" /> Clear filters
          </button>
        </div>

      </div>

      <!-- Cards grid -->
      <AegisEmptyState
        v-if="!exploreResults.length && !exploreLoading"
        icon="search"
        title="No services found"
        subtitle="Try adjusting your filters or search terms."
      />

      <div class="explore-grid">
        <ServiceExploreCard
          v-for="svc in exploreResults"
          :key="svc.id"
          :service="svc"
          @request="openExploreRequest(svc)"
        />
      </div>

      <!-- Loading spinner -->
      <div v-if="exploreLoading" class="explore-loading">
        <AegisIcon name="loader" :size="20" />
        Loading more…
      </div>

      <!-- Sentinel for IntersectionObserver -->
      <div ref="exploreSentinel" class="explore-sentinel" aria-hidden="true"></div>

      <div v-if="!exploreLoading && exploreMeta.current_page >= exploreMeta.last_page && exploreResults.length > 0" class="explore-end-note">
        <AegisIcon name="check-circle" :size="13" />
        {{ exploreResults.length }} service{{ exploreResults.length !== 1 ? 's' : '' }} shown
      </div>

      <!-- ServiceRequestModal for explore submissions -->
      <ServiceRequestModal
        :service-id="activeExploreService?.id ?? ''"
        :service-title="activeExploreService?.title ?? ''"
        :provider-label="activeExploreService?.practitioner_name ?? ''"
      />
    </div>

    <!-- ══════════════════════════════════════════════════════════════════
         TAB 1: MY LISTINGS
    ══════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'listings'">

      <!-- Toolbar -->
      <div class="svc-toolbar">
        <div class="search-wrap">
          <AegisIcon name="search" :size="15" />
          <input v-model="listingSearch" type="text" class="form-control" placeholder="Search your service listings…">
        </div>
        <select v-model="listingTypeFilter" class="form-select">
          <option value="">All Types</option>
          <option v-for="c in serviceCategories" :key="c.value" :value="c.value">{{ c.label }}</option>
        </select>
        <select v-model="listingStatusFilter" class="form-select">
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="draft">Draft</option>
          <option value="paused">Paused</option>
          <option value="archived">Archived</option>
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
            <div class="svc-type-icon"><AegisIcon :name="s.type_icon || 'briefcase'" :size="22" /></div>
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
                <AegisIcon :name="m.icon || 'check'" :size="13" />{{ m.text }}
              </div>
            </div>
            <div class="svc-price-row">
              <div class="svc-price">{{ s.price }}</div>
              <div v-if="s.price_unit" class="svc-price-unit">{{ s.price_unit }}</div>
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
              <button class="btn-icon" data-tooltip="Edit Draft" @click.stop="setActiveService(s); modals.edit = true"><AegisIcon name="pencil" :size="14" /></button>
              <button class="btn-icon" data-tooltip="Publish" @click.stop="setActiveService(s); modals.publish = true"><AegisIcon name="check" :size="14" /></button>
              <button class="btn-icon btn-icon-danger" data-tooltip="Delete Draft" @click.stop="setActiveService(s); confirmAction({ title: 'Delete Draft', message: `Delete '${s.title}'? This cannot be undone.`, btnLabel: 'Delete', type: 'danger' }, () => deleteServiceFromCard())"><AegisIcon name="trash" :size="14" /></button>
            </template>
            <template v-else>
              <button class="btn-icon" data-tooltip="Edit Listing" @click.stop="setActiveService(s); modals.edit = true"><AegisIcon name="pencil" :size="14" /></button>
              <button class="btn-icon" data-tooltip="Preview" @click.stop="openPreview(s)"><AegisIcon name="eye" :size="14" /></button>
              <button class="btn-icon" data-tooltip="View Bookings" @click.stop="activeTab = 'bookings'"><AegisIcon name="calendar" :size="14" /></button>
              <button v-if="s.status === 'paused'" class="btn-icon" data-tooltip="Resume Listing" @click.stop="setActiveService(s); confirmAction({ title: 'Resume Listing', message: 'Resume and make this listing visible again?', btnLabel: 'Resume', type: 'primary' }, () => resumeService())"><AegisIcon name="arrow-right" :size="14" /></button>
              <button v-else class="btn-icon" data-tooltip="Pause Listing" @click.stop="setActiveService(s); modals.pause = true"><AegisIcon name="pause" :size="14" /></button>
            </template>
          </div>
        </div>

        <!-- Add new card -->
        <div class="upload-zone" style="min-height:280px;border-radius:var(--radius-lg);cursor:pointer;" @click="modals.create = true">
          <div class="upload-zone-icon"><AegisIcon name="plus" :size="28" /></div>
          <div class="upload-zone-title">Add New Service</div>
          <div class="upload-zone-sub">Supervision, consultation, training, coaching &amp; more</div>
        </div>
      </div>

      <AegisEmptyState v-if="!filteredListings.length && listings.length" icon="search" title="No listings match your filters" subtitle="Try adjusting your search or filter criteria." />
      <AegisEmptyState v-else-if="!listings.length" icon="briefcase" title="No Service Listings Yet" subtitle="Add your first service to start receiving requests from providers on the network.">
        <template #action>
          <button class="btn btn-primary" @click="modals.create = true"><AegisIcon name="plus" :size="13" /> Add New Service</button>
        </template>
      </AegisEmptyState>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════
         TAB 2: SERVICE REQUESTS (incoming — I am the provider)
    ══════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'requests'">
      <div class="page-note">
        <AegisIcon name="users" :size="14" />
        <span>Other practitioners have requested your services. Review, accept, counter-propose, or dismiss each request below.</span>
      </div>

      <div v-if="newRequests.length" class="alert alert-info" style="margin-bottom:20px">
        <div class="alert-icon"><AegisIcon name="info" :size="16" /></div>
        <div class="alert-content">
          You have <strong>{{ newRequests.length }} pending service request{{ newRequests.length === 1 ? '' : 's' }}</strong> that need a response. Requests auto-expire after 72 hours if not accepted.
        </div>
      </div>

      <!-- Pending refund requests from clients -->
      <template v-if="incomingRefundRequests.filter(r => r.is_actionable).length">
        <div class="section-header">
          <div class="section-title">
            Pending Refund Requests
            <span class="section-badge">{{ incomingRefundRequests.filter(r => r.is_actionable).length }}</span>
          </div>
        </div>
        <div class="refund-requests-list">
          <div v-for="rr in incomingRefundRequests.filter(r => r.is_actionable)" :key="rr.id" class="card refund-request-card">
            <div class="card-header">
              <div>
                <div class="card-title">{{ rr.requested_by_name }} — {{ rr.service_title }}</div>
                <div class="card-subtitle">{{ rr.refund_type_label }} · {{ rr.amount_requested }} · {{ rr.session_date }}</div>
              </div>
              <div style="display:flex;gap:8px;align-items:center">
                <AegisBadge :label="rr.status_label" :variant="rr.status_variant" />
                <AegisBadge v-if="rr.is_overdue" label="Overdue" variant="red" />
              </div>
            </div>
            <div class="card-footer">
              <button type="button" class="btn btn-outline" @click="activeRefundRequest = rr; modals.reviewRefund = true">
                <AegisIcon name="eye" :size="13" /> Review
              </button>
            </div>
          </div>
        </div>
      </template>

      <div class="section-header" :style="incomingRefundRequests.length ? 'margin-top:24px' : ''">
        <div class="section-title">Pending Requests<span class="section-badge">{{ newRequests.length }}</span></div>
      </div>

      <div class="requests-list">
        <AegisEmptyState v-if="!newRequests.length" icon="inbox" title="No Pending Requests" subtitle="New service requests from providers will appear here." />
        <div
          v-for="r in newRequests"
          :key="r.id"
          class="card request-card new"
          style="padding:16px 20px;display:flex;align-items:center;flex-wrap:wrap;gap:16px;"
          @click="setActiveRequest(r)"
        >
          <div class="avatar avatar-md">{{ r.requester_avatar || initials(r.requester_name) }}</div>
          <div class="req-info">
            <component :is="r.requester_slug ? 'a' : 'div'" :href="r.requester_slug ? route('public.provider', { slug: r.requester_slug }) : undefined" class="req-name" :class="{ 'link-name': r.requester_slug }">{{ r.requester_name }}</component>
            <div class="req-detail">{{ r.requester_detail }}</div>
          </div>
          <div class="req-service">
            <div class="req-service-name">{{ r.service_title }}</div>
            <div class="req-service-type">{{ r.request_type }}</div>
          </div>
          <div style="min-width:120px;color:var(--text-3);font-size:12px;font-weight:600">
            <div style="font-weight:700;font-size:13px;color:var(--text);margin-bottom:2px">Requested date</div>
            {{ r.requested_date_label }}
          </div>
          <div class="req-date">{{ r.time_label }}</div>
          <div class="req-actions">
            <button class="btn-icon" :data-tooltip="`Message ${r.requester_name}`" :disabled="msgLoading === r.requester_id" @click.stop="openConversation(r.requester_id)"><AegisIcon name="message" :size="14" /></button>
            <button class="btn-icon" data-tooltip="Counter Propose" @click.stop="setActiveRequest(r); modals.counter = true"><AegisIcon name="refresh" :size="14" /></button>
            <button class="btn-icon" data-tooltip="Dismiss" @click.stop="setActiveRequest(r); dismissForm.reason = ''; dismissForm.otherReason = ''; modals.dismiss = true"><AegisIcon name="x" :size="14" /></button>
            <button class="btn btn-primary" @click.stop="setActiveRequest(r); modals.accept = true"><AegisIcon name="check" :size="13" /> Accept</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════
         TAB 3: BOOKINGS & SESSIONS (I am the provider)
    ══════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'bookings'">

      <div class="svc-toolbar">
        <div class="search-wrap">
          <AegisIcon name="search" :size="15" />
          <input v-model="bookingSearch" type="text" class="form-control" placeholder="Search sessions or clients…">
        </div>
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
          <div class="card-title" style="display:flex;align-items:center;gap:8px">
            <AegisIcon name="calendar" :size="16" />
            Sessions — {{ bookingPeriodLabel }}
          </div>
          <div style="display:flex;gap:8px">
            <AegisBadge :label="`${stats?.sessions ?? 0} this month`" variant="gold" />
            <AegisBadge :label="(stats?.revenue_label ?? '$0') + ' earned'" variant="green" />
          </div>
        </div>
        <div class="card-body">
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr>
                  <th>Client</th>
                  <th>Service</th>
                  <th>Date &amp; Time</th>
                  <th>Duration</th>
                  <th>Amount</th>
                  <th>Session</th>
                  <th>Payment</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!bookings.length">
                  <td colspan="8" style="text-align:center;color:var(--text-4);padding:24px">No sessions found.</td>
                </tr>
                <tr v-for="b in bookings" :key="b.id" @click="setActiveBooking(b)">
                  <td>
                    <div class="td-provider">
                      <div class="avatar avatar-sm">{{ b.client_avatar || initials(b.client_name) }}</div>
                      <div>
                        <a v-if="b.client_slug" :href="route('public.provider', { slug: b.client_slug })" class="td-name link-name">{{ b.client_name }}</a>
                        <div v-else class="td-name">{{ b.client_name }}</div>
                        <div class="td-cred">{{ b.client_credentials }}</div>
                      </div>
                    </div>
                  </td>
                  <td>{{ b.service_title }}</td>
                  <td>{{ b.datetime_label }}</td>
                  <td>{{ b.duration_label }}</td>
                  <td style="font-weight:700">{{ b.amount }}</td>
                  <td><AegisBadge :label="statusLabel(b.status)" :variant="statusVariant(b.status)" /></td>
                  <td><AegisBadge :label="b.payment_status_label ?? 'Unpaid'" :variant="b.payment_status_variant ?? 'gold'" /></td>
                  <td>
                    <div class="req-actions">
                      <button class="btn-icon" data-tooltip="Session Notes" @click.stop="setActiveBooking(b); modals.sessionNotes = true"><AegisIcon name="file-text" :size="14" /></button>
                      <button class="btn-icon" data-tooltip="View Invoice" @click.stop="setActiveBooking(b); modals.providerInvoice = true"><AegisIcon name="dollar-sign" :size="14" /></button>
                      <button v-if="b.has_pending_refund_request" class="btn-icon" data-tooltip="Review Refund Request" @click.stop="activeRefundRequest = incomingRefundRequests.find(r => r.session_id === b.id); modals.reviewRefund = true">
                        <AegisIcon name="alert-circle" :size="14" />
                      </button>
                      <button v-if="b.status === 'scheduled'" class="btn-icon" data-tooltip="Cancel Session" @click.stop="setActiveBooking(b); modals.cancelSession = true"><AegisIcon name="x" :size="14" /></button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <AegisPagination
            :current-page="bookingsMeta.current_page ?? 1"
            :total-pages="bookingsMeta.last_page ?? 1"
            :total="bookingsMeta.total ?? bookings.length"
            :from="bookings.length ? ((bookingsMeta.current_page - 1) * bookingsMeta.per_page) + 1 : 0"
            :to="Math.min(bookingsMeta.current_page * bookingsMeta.per_page, bookingsMeta.total ?? bookings.length)"
            :show-meta="true"
            @change="goToBookingsPage"
          />
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════
         TAB 4: MY REQUESTS (I am the client — sessions I booked)
    ══════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'outgoing'">

      <!-- Section A: My Booked Sessions ──────────────────────────────── -->
      <div class="section-header" style="margin-top:0">
        <div class="section-title">
          My Booked Sessions
          <span class="section-badge">{{ clientSessions.length }}</span>
        </div>
        <div class="section-subtitle">Sessions you've requested from other practitioners. Pay deposit to confirm, then pay balance after the session.</div>
      </div>

      <AegisEmptyState
        v-if="!clientSessions.length"
        icon="calendar"
        title="No booked sessions"
        subtitle="Browse the Explore tab to find supervision, consultation, and other practitioner services."
        style="margin-bottom:24px"
      >
        <template #action>
          <button class="btn btn-primary" @click="activeTab = 'explore'">
            <AegisIcon name="search" :size="13" /> Browse Services
          </button>
        </template>
      </AegisEmptyState>

      <!-- SessionInvoiceCard for each client session -->
      <div v-for="ses in clientSessions" :key="ses.id" style="margin-bottom:0">
        <SessionInvoiceCard
          :session="ses"
          viewpoint="client"
          @pay-deposit="activeClientSession = ses; modals.payDeposit = true"
          @pay-balance="activeClientSession = ses; modals.payBalance = true"
          @view-invoice="activeClientSession = ses; modals.clientInvoice = true"
          @request-refund="activeClientSession = ses; modals.requestRefund = true"
          @escalate-refund="escalateRefund(ses)"
          @cancel-session="activeClientSession = ses; modals.cancelClientSession = true"
        />
      </div>

      <!-- Paginator for client sessions -->
      <AegisPagination
        v-if="clientSessionsMeta.last_page > 1"
        :current-page="clientSessionsMeta.current_page ?? 1"
        :total-pages="clientSessionsMeta.last_page ?? 1"
        :total="clientSessionsMeta.total ?? clientSessions.length"
        :from="clientSessions.length ? ((clientSessionsMeta.current_page - 1) * clientSessionsMeta.per_page) + 1 : 0"
        :to="Math.min(clientSessionsMeta.current_page * clientSessionsMeta.per_page, clientSessionsMeta.total)"
        :show-meta="true"
        style="margin-top:8px;margin-bottom:28px"
        @change="goToClientSessionsPage"
      />

      <!-- Section B: Outgoing service requests (not yet booked) ──────── -->
      <div class="section-header">
        <div class="section-title">
          My Service Requests
          <span class="section-badge">{{ props.outgoingRequests.length }}</span>
        </div>
        <div class="section-subtitle">Requests you have sent to other providers — pending, accepted, declined, or withdrawn.</div>
      </div>

      <AegisEmptyState v-if="!props.outgoingRequests.length" icon="send" title="No outgoing requests" subtitle="When you request a service from another provider, it will appear here." />

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
                    <div v-if="r.response_note" class="td-sub" style="margin-top:4px">{{ r.response_note }}</div>
                  </td>
                  <td>
                    <button v-if="r.status === 'new'" class="btn-icon" data-tooltip="Withdraw Request" @click.stop="withdrawOutgoingRequest(r.id)"><AegisIcon name="x" :size="14" /></button>
                    <span v-else class="td-sub">—</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════
         TAB 5: SETTINGS
    ══════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'settings'">
      <div class="settings-grid">
        <div class="card" style="grid-column:1/-1">
          <div class="card-header is-settings">
            <div>
              <div class="card-title" style="display:flex;align-items:center;gap:8px"><AegisIcon name="settings" :size="16" /> Services Settings</div>
              <div class="card-subtitle">Visibility, booking preferences, and payment — managed in Account Settings</div>
            </div>
            <a :href="route('provider.settings.index') + '?tab=myservices'" class="btn btn-primary">
              <AegisIcon name="settings" :size="13" /> Open Settings
            </a>
          </div>
          <div class="card-body" style="padding:0">
            <div class="setting-row" style="padding:14px 20px;border-bottom:1px solid var(--border)">
              <div class="setting-info">
                <div class="setting-label" style="display:flex;align-items:center;gap:7px"><AegisIcon name="eye" :size="14" /> Visibility &amp; Mode</div>
                <div class="setting-desc">Services Mode · Show in search · Accept requests · Show pricing</div>
              </div>
              <a :href="route('provider.settings.index') + '?tab=myservices&anchor=services-mode'" class="btn btn-outline"><AegisIcon name="chevron-right" :size="13" /> Edit</a>
            </div>
            <div class="setting-row" style="padding:14px 20px;border-bottom:1px solid var(--border)">
              <div class="setting-info">
                <div class="setting-label" style="display:flex;align-items:center;gap:7px"><AegisIcon name="calendar" :size="14" /> Booking Preferences</div>
                <div class="setting-desc">Manual approval · Request expiry · Buffer between sessions</div>
              </div>
              <a :href="route('provider.settings.index') + '?tab=myservices&anchor=booking-preferences'" class="btn btn-outline"><AegisIcon name="chevron-right" :size="13" /> Edit</a>
            </div>
            <div class="setting-row" style="padding:14px 20px">
              <div class="setting-info">
                <div class="setting-label" style="display:flex;align-items:center;gap:7px"><AegisIcon name="credit-card" :size="14" /> Payment &amp; Rates</div>
                <div class="setting-desc">Hourly rate · Payment method · Sliding scale · Deposit policy</div>
              </div>
              <a :href="route('provider.settings.index') + '?tab=myservices&anchor=payment-rates'" class="btn btn-outline"><AegisIcon name="chevron-right" :size="13" /> Edit</a>
            </div>
          </div>
        </div>

        <!-- Services Profile Bio -->
        <div class="card" style="grid-column:1/-1">
          <div class="card-header is-settings">
            <div>
              <div class="card-title" style="display:flex;align-items:center;gap:8px"><AegisIcon name="user" :size="16" /> Services Profile</div>
              <div class="card-subtitle">This appears on your Services tab in provider search results</div>
            </div>
          </div>
          <div class="card-body">
            <div class="form-row" style="margin-bottom:16px">
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
                  {{ sp }}<button class="chip-remove" @click="removeSpecialty(i)"><AegisIcon name="x" :size="10" /></button>
                </span>
                <input v-model="newSpecialty" type="text" class="form-control" style="width:auto;min-width:140px;display:inline-flex" placeholder="Add specialty…" @keydown.enter.prevent="addSpecialty">
                <button type="button" class="btn btn-outline" @click="addSpecialty"><AegisIcon name="plus" :size="12" /> Add</button>
              </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:4px">
              <button class="btn btn-outline" @click="resetProfile">Cancel</button>
              <button class="btn btn-primary" @click="saveProfile"><AegisIcon name="check" :size="13" /> Save Profile</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════
         MODALS
    ══════════════════════════════════════════════════════════════════ -->

    <!-- Wave 4 new modals ──────────────────────────────────────────────── -->
    <PayDepositModal
      v-model="modals.payDeposit"
      :session="activeClientSession"
      @success="activeClientSession = null"
    />
    <PayBalanceModal
      v-model="modals.payBalance"
      :session="activeClientSession"
      @success="activeClientSession = null"
    />
    <RequestRefundModal
      v-model="modals.requestRefund"
      :session="activeClientSession"
      @success="activeClientSession = null"
    />
    <SessionInvoiceModal
      v-model="modals.clientInvoice"
      :session="activeClientSession"
      viewpoint="client"
    />
    <SessionInvoiceModal
      v-model="modals.providerInvoice"
      :session="activeBooking"
      viewpoint="provider"
    />
    <ReviewRefundRequestModal
      v-model="modals.reviewRefund"
      :refund-request="activeRefundRequest"
      @success="activeRefundRequest = null"
    />

    <!-- Create Service Modal ───────────────────────────────────────────── -->
    <AegisModal v-model="modals.create" title="Create New Service Listing" size="lg">
      <div class="modal-section-label">Service Type</div>
      <div class="pricing-options">
        <div v-for="opt in serviceTypeOptions" :key="opt.key" class="pricing-opt" :class="{ selected: createForm.category === opt.key }" @click="createForm.category = opt.key">
          <div class="pricing-opt-label"><AegisIcon :name="opt.icon" :size="14" /> {{ opt.label }}</div>
          <div class="pricing-opt-desc">{{ opt.desc }}</div>
        </div>
      </div>
      <div class="modal-section-label">Service Details</div>
      <div class="form-group">
        <label class="form-label">Service Name <span style="color:var(--red)">*</span></label>
        <input v-model="createForm.title" class="form-input" :class="{ 'is-error': createFieldError('title') }" type="text" placeholder="e.g. Individual Clinical Supervision for Pre-Licensed Therapists" @blur="createV$.title.$touch()">
        <div v-if="createFieldError('title')" class="form-error">{{ createFieldError('title') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Description <span style="color:var(--red)">*</span></label>
        <textarea v-model="createForm.description" class="form-input" :class="{ 'is-error': createFieldError('description') }" placeholder="Describe what you offer, who it's for, your approach, and any requirements…" @blur="createV$.description.$touch()"></textarea>
        <div v-if="createFieldError('description')" class="form-error">{{ createFieldError('description') }}</div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Session Duration</label>
          <select v-model.number="createForm.duration_min" class="form-select">
            <option :value="30">30 minutes</option><option :value="45">45 minutes</option><option :value="50">50 minutes</option><option :value="60">60 minutes</option><option :value="75">75 minutes</option><option :value="90">90 minutes</option><option :value="120">2 hours</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Format</label>
          <select v-model="createForm.format" class="form-select">
            <option value="telehealth">Virtual only</option><option value="in_person">In-person only</option><option value="both">Virtual &amp; In-person</option>
          </select>
        </div>
      </div>
      <div class="modal-section-label">Pricing</div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Rate ($) <span style="color:var(--red)">*</span></label>
          <input v-model.number="createDollars" class="form-input" :class="{ 'is-error': createFieldError('price_cents') }" type="number" placeholder="150" @blur="createV$.price_cents?.$touch()">
          <div v-if="createFieldError('price_cents')" class="form-error">{{ createFieldError('price_cents') }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Per</label>
          <select v-model="createForm.price_type" class="form-select">
            <option value="session">Session</option><option value="hourly">Hour</option><option value="fixed">Package / Fixed</option><option value="inquiry">Contact for pricing</option>
          </select>
        </div>
      </div>
      <div class="setting-row" style="padding:8px 0">
        <div class="setting-info"><div class="setting-label">Sliding Scale Available</div></div>
        <AegisToggle v-model="createForm.sliding_scale" />
      </div>
      <div class="modal-section-label">Availability</div>
      <div class="form-row" style="margin-bottom:14px">
        <div class="form-group">
          <label class="form-label">Availability</label>
          <select v-model="createForm.availability" class="form-select"><option value="open">Open — accepting requests</option><option value="limited">Limited availability</option></select>
        </div>
        <div class="form-group">
          <label class="form-label">Availability Label (optional)</label>
          <input v-model="createForm.availability_label" class="form-input" type="text" placeholder="e.g. 3 spots left">
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="resetCreateModal">Cancel</button>
        <button class="btn btn-outline" @click="submitCreate('draft')">Save as Draft</button>
        <button class="btn btn-primary" @click="submitCreate('active')"><AegisIcon name="check" :size="14" /> Publish Listing</button>
      </template>
    </AegisModal>

    <!-- Edit Service Modal ─────────────────────────────────────────────── -->
    <AegisModal v-model="modals.edit" title="Edit Service Listing" size="lg">
      <div class="modal-section-label">Service Type</div>
      <div class="pricing-options">
        <div v-for="opt in serviceTypeOptions" :key="opt.key" class="pricing-opt" :class="{ selected: editForm.category === opt.key }" @click="editForm.category = opt.key">
          <div class="pricing-opt-label"><AegisIcon :name="opt.icon" :size="14" /> {{ opt.label }}</div>
          <div class="pricing-opt-desc">{{ opt.desc }}</div>
        </div>
      </div>
      <div class="modal-section-label">Service Details</div>
      <div class="form-group">
        <label class="form-label">Service Name <span style="color:var(--red)">*</span></label>
        <input v-model="editForm.title" class="form-input" :class="{ 'is-error': editV$.title.$error }" type="text" @blur="editV$.title.$touch()">
        <div v-if="editV$.title.$error" class="form-error">{{ editFieldError('title') }}</div>
      </div>
      <div class="form-group"><label class="form-label">Description</label><textarea v-model="editForm.description" class="form-input" style="min-height:90px"></textarea></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Session Duration</label><select v-model.number="editForm.duration_min" class="form-select"><option :value="30">30 min</option><option :value="45">45 min</option><option :value="50">50 min</option><option :value="60">60 min</option><option :value="90">90 min</option><option :value="120">2 hr</option></select></div>
        <div class="form-group"><label class="form-label">Format</label><select v-model="editForm.format" class="form-select"><option value="telehealth">Virtual only</option><option value="in_person">In-person only</option><option value="both">Both</option></select></div>
      </div>
      <div class="modal-section-label">Pricing</div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Rate ($)</label><input v-model.number="editDollars" class="form-input" type="number" placeholder="150"></div>
        <div class="form-group"><label class="form-label">Per</label><select v-model="editForm.price_type" class="form-select"><option value="session">Session</option><option value="hourly">Hour</option><option value="fixed">Package</option><option value="inquiry">Contact for pricing</option></select></div>
      </div>
      <div class="form-group"><label class="form-label">Listing Status</label><select v-model="editForm.status" class="form-select"><option value="active">Active</option><option value="paused">Paused</option><option value="draft">Draft</option><option value="archived">Archived</option></select></div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.edit = false">Cancel</button>
        <button class="btn btn-primary" @click="submitEdit"><AegisIcon name="check" :size="14" /> Save Changes</button>
      </template>
    </AegisModal>

    <!-- Accept Request Modal — now includes CounterOfferInline ──────────── -->
    <AegisModal v-model="modals.accept" title="Accept Service Request" size="sm">
      <div class="alert alert-success" style="margin-bottom:18px">
        <AegisIcon name="check" :size="16" />
        <span>Accepting will schedule the session. The client will be notified to pay their 30% deposit to confirm the booking.</span>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Session Date <span style="color:var(--red)">*</span></label><input v-model="acceptForm.session_date" class="form-input" type="date" :min="todayDate"></div>
        <div class="form-group"><label class="form-label">Session Time</label><select v-model="acceptForm.session_time" class="form-select"><option value="09:00">9:00 AM</option><option value="10:00">10:00 AM</option><option value="11:00">11:00 AM</option><option value="12:00">12:00 PM</option><option value="13:00">1:00 PM</option><option value="14:00">2:00 PM</option><option value="15:00">3:00 PM</option><option value="16:00">4:00 PM</option><option value="17:00">5:00 PM</option></select></div>
      </div>
      <div class="form-group"><label class="form-label">Timezone</label><select v-model="acceptForm.timezone" class="form-select"><option value="America/New_York">Eastern (ET)</option><option value="America/Chicago">Central (CT)</option><option value="America/Denver">Mountain (MT)</option><option value="America/Los_Angeles">Pacific (PT)</option></select></div>
      <div class="form-group"><label class="form-label">Format</label><select v-model="acceptForm.format" class="form-select"><option>Virtual (Telehealth)</option><option>In-person</option></select></div>
      <div class="form-group"><label class="form-label">Note to Client (optional)</label><textarea v-model="acceptForm.note" class="form-input" style="min-height:70px" placeholder="Intake info, prep instructions, or a welcome message…"></textarea></div>
      <!-- Wave 4: Price negotiation -->
      <CounterOfferInline
        :listing-price-cents="activeRequest?.service_price_cents ?? 0"
        :model-value="acceptForm.negotiated_amount_cents"
        @update:model-value="acceptForm.negotiated_amount_cents = $event"
      />
      <template #footer>
        <button class="btn btn-outline" @click="modals.accept = false">Cancel</button>
        <button class="btn btn-primary" @click="submitAccept"><AegisIcon name="check" :size="14" /> Accept Request</button>
      </template>
    </AegisModal>

    <!-- Counter Propose Modal ──────────────────────────────────────────── -->
    <AegisModal v-model="modals.counter" title="Counter Propose" size="sm">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Proposed Date</label><input v-model="counterForm.proposed_date" class="form-input" type="date" :min="todayDate"></div>
        <div class="form-group"><label class="form-label">Proposed Time</label><select v-model="counterForm.proposed_time" class="form-select"><option value="09:00">9:00 AM</option><option value="10:00">10:00 AM</option><option value="13:00">1:00 PM</option><option value="14:00">2:00 PM</option></select></div>
      </div>
      <div class="form-group">
        <label class="form-label">Message to Client <span style="color:var(--red)">*</span></label>
        <textarea v-model="counterForm.message" class="form-input" :class="{ 'is-error': counterV$.message.$error }" placeholder="Explain your counter-proposal, alternative times, or questions…" @blur="counterV$.message.$touch()"></textarea>
        <div v-if="counterV$.message.$error" class="form-error">{{ counterFieldError('message') }}</div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.counter = false">Cancel</button>
        <button class="btn btn-primary" @click="submitCounter"><AegisIcon name="send" :size="14" /> Send Counter Proposal</button>
      </template>
    </AegisModal>

    <!-- Dismiss Request Modal ──────────────────────────────────────────── -->
    <AegisModal v-model="modals.dismiss" title="Dismiss Request" size="sm">
      <div class="form-group">
        <label class="form-label">Reason for dismissing</label>
        <select v-model="dismissForm.reason" class="form-select"><option value="">Select a reason…</option><option v-for="r in DISMISS_REASONS" :key="r" :value="r">{{ r }}</option></select>
      </div>
      <div v-if="dismissForm.reason === 'Other'" class="form-group" style="margin-top:12px">
        <label class="form-label">Please specify</label>
        <textarea v-model="dismissForm.otherReason" class="form-control" rows="3" placeholder="Briefly describe your reason…" />
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.dismiss = false">Cancel</button>
        <button class="btn btn-danger" :disabled="!dismissForm.reason" @click="submitDismiss"><AegisIcon name="x" :size="14" /> Dismiss Request</button>
      </template>
    </AegisModal>

    <!-- Cancel Session Modal (provider cancels their own booked session) ── -->
    <AegisModal v-model="modals.cancelSession" title="Cancel Session" size="sm">
      <div class="alert alert-warning" style="margin-bottom:16px">
        <AegisIcon name="alert-triangle" :size="16" />
        <span>Cancelling will notify the client immediately. This session is <strong>{{ activeBooking?.datetime_label }} — {{ activeBooking?.client_name }}</strong>.</span>
      </div>
      <div class="form-group">
        <label class="form-label">Reason for cancellation <span style="color:var(--red)">*</span></label>
        <select v-model="cancelSessionForm.reason" class="form-select" :class="{ 'is-error': cancelV$.reason.$error }" @blur="cancelV$.reason.$touch()">
          <option value="">Select reason…</option>
          <option>Provider unavailable — schedule conflict</option><option>Emergency or illness</option><option>Rescheduling to a different time</option><option>Other</option>
        </select>
        <div v-if="cancelV$.reason.$error" class="form-error">{{ cancelFieldError('reason') }}</div>
      </div>
      <div class="form-group"><label class="form-label">Note to client (optional)</label><textarea v-model="cancelSessionForm.note" class="form-input" style="min-height:70px" placeholder="Let them know what happened…"></textarea></div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.cancelSession = false">Keep Session</button>
        <button class="btn btn-danger" @click="submitCancelSession"><AegisIcon name="x" :size="14" /> Cancel Session</button>
      </template>
    </AegisModal>

    <!-- Session Notes Modal ─────────────────────────────────────────────── -->
    <AegisModal v-model="modals.sessionNotes" title="Session Notes" size="md">
      <div style="margin-bottom:16px">
        <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:2px">{{ activeBooking?.client_name }}</div>
        <div style="font-size:13px;font-weight:600;color:var(--text-3)">{{ activeBooking?.service_title }} · {{ activeBooking?.datetime_label }} · {{ activeBooking?.duration_label }} · {{ activeBooking?.amount }}</div>
      </div>
      <div class="form-group"><label class="form-label">Session Summary</label><textarea v-model="notesForm.summary" class="form-input" style="min-height:100px" placeholder="Key topics covered, progress notes, follow-up items…"></textarea></div>
      <div class="form-group"><label class="form-label">Action Items for Next Session</label><textarea v-model="notesForm.action_items" class="form-input" style="min-height:70px" placeholder="Tasks, readings, or goals to revisit…"></textarea></div>
      <div class="setting-row" style="padding:6px 0">
        <div class="setting-info"><div class="setting-label">Share summary with client</div><div class="setting-desc">They'll receive a read-only copy via Aegis messaging.</div></div>
        <AegisToggle v-model="notesForm.share_with_supervisee" />
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.sessionNotes = false">Cancel</button>
        <button class="btn btn-primary" @click="submitNotes"><AegisIcon name="check" :size="14" /> Save Notes</button>
      </template>
    </AegisModal>

    <!-- Publish / Pause / Reactivate Modals ────────────────────────────── -->
    <AegisModal v-model="modals.publish" title="Publish Listing" size="sm">
      <div class="alert alert-success" style="margin-bottom:16px"><AegisIcon name="check" :size="16" /><span>Your listing will be visible to providers in search immediately after publishing.</span></div>
      <div class="setting-row" style="padding:6px 0"><div class="setting-info"><div class="setting-label">Notify matching providers</div><div class="setting-desc">Send a discovery alert to providers who match your specialty tags</div></div><AegisToggle v-model="publishForm.notify" /></div>
      <template #footer><button class="btn btn-outline" @click="modals.publish = false">Cancel</button><button class="btn btn-primary" @click="submitPublish"><AegisIcon name="check" :size="14" /> Publish Now</button></template>
    </AegisModal>

    <AegisModal v-model="modals.pause" title="Pause Listing" size="sm">
      <div class="alert alert-warning" style="margin-bottom:16px"><AegisIcon name="alert-triangle" :size="16" /><span>Pausing will hide this listing from provider search. Existing bookings are not affected.</span></div>
      <div class="form-group"><label class="form-label">Reason for pausing (optional)</label><select v-model="pauseForm.reason" class="form-select"><option value="">Select reason…</option><option>At capacity</option><option>Temporarily unavailable</option><option>Updating listing details</option><option>Other</option></select></div>
      <template #footer><button class="btn btn-outline" @click="modals.pause = false">Keep Active</button><button class="btn btn-danger" @click="submitPause"><AegisIcon name="pause" :size="14" /> Pause Listing</button></template>
    </AegisModal>

      </div><!-- /svc-content -->
    </div><!-- /svc-layout -->

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, nextTick, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisToggle from '@/components/ui/AegisToggle.vue'
import AegisPagination from '@/components/ui/AegisPagination.vue'
// Wave 4 components (local import required)
import SessionInvoiceCard        from '@/components/ui/SessionInvoiceCard.vue'
import ServiceExploreCard        from '@/components/ui/ServiceExploreCard.vue'
import CounterOfferInline        from '@/components/ui/CounterOfferInline.vue'
import SessionInvoiceModal       from '@/components/modals/SessionInvoiceModal.vue'
import PayDepositModal           from '@/components/modals/PayDepositModal.vue'
import PayBalanceModal           from '@/components/modals/PayBalanceModal.vue'
import RequestRefundModal        from '@/components/modals/RequestRefundModal.vue'
import ReviewRefundRequestModal  from '@/components/modals/ReviewRefundRequestModal.vue'
import ServiceRequestModal       from '@/components/modals/ServiceRequestModal.vue'
import { useModal }              from '@/composables/useModal'
import { useToast }              from '@/composables/useToast'
import { useConfirm }            from '@/composables/useConfirm'
import { useMessageButton }      from '@/composables/useMessageButton'
import { useInfiniteScroll }     from '@/composables/useInfiniteScroll'
import useVuelidate              from '@vuelidate/core'
import { required, maxLength, helpers } from '@vuelidate/validators'

// ── Props ─────────────────────────────────────────────────────────────────────
const props = defineProps({
  listings:               { type: Array,   default: () => [] },
  serviceRequests:        { type: Array,   default: () => [] },
  bookings:               { type: Array,   default: () => [] },
  bookingsMeta:           { type: Object,  default: () => ({ current_page: 1, last_page: 1, total: 0, per_page: 10 }) },
  clientSessions:         { type: Array,   default: () => [] },
  clientSessionsMeta:     { type: Object,  default: () => ({ current_page: 1, last_page: 1, total: 0, per_page: 10 }) },
  stats:                  { type: Object,  default: () => ({ active_listings: 0, pending_requests: 0, sessions: 0, total_sessions: 0, revenue_label: '$0', pending_refunds: 0 }) },
  profileCompletion:      { type: Number,  default: 0 },
  servicesMode:           { type: Boolean, default: false },
  heroRating:             { type: String,  default: '—' },
  filters:                { type: Object,  default: () => ({}) },
  outgoingRequests:       { type: Array,   default: () => [] },
  serviceProfile:         { type: Object,  default: () => ({ headline: '', bio: '', years_experience: 0, specialties: [] }) },
  // Wave 5 new props
  exploreResults:         { type: Array,   default: () => [] },
  exploreMeta:            { type: Object,  default: () => ({ current_page: 1, last_page: 1, total: 0, per_page: 12 }) },
  exploreFilters:         { type: Object,  default: () => ({}) },
  incomingRefundRequests: { type: Array,   default: () => [] },
})

// ── Composables ───────────────────────────────────────────────────────────────
const toast = useToast()
const { confirmAction } = useConfirm()
const { openConversation, loading: msgLoading } = useMessageButton()
const { openModal } = useModal()

// ── Tab state ─────────────────────────────────────────────────────────────────
const VALID_TABS = ['explore','listings','requests','outgoing','bookings','settings']
const activeTab  = ref('listings')
onMounted(() => {
  const tab = new URLSearchParams(window.location.search).get('tab')
  if (tab && VALID_TABS.includes(tab)) activeTab.value = tab
})

const tabs = computed(() => [
  { key: 'explore',   label: 'Browse Services',       icon: 'search',   count: null },
  { key: 'listings',  label: 'My Listings',           icon: 'grid',     count: props.listings.length },
  { key: 'requests',  label: 'Service Requests',      icon: 'clock',    count: newRequests.value.length + props.incomingRefundRequests.filter(r => r.is_actionable).length },
  { key: 'bookings',  label: 'Bookings & Sessions',   icon: 'calendar', count: props.stats?.sessions ?? null },
  { key: 'outgoing',  label: 'My Requests',           icon: 'send',     count: pendingClientActions.value || null },
  { key: 'settings',  label: 'Settings',              icon: 'settings', count: null },
])

// ── Modal state ───────────────────────────────────────────────────────────────
const modals = reactive({
  create: false, edit: false, accept: false, counter: false,
  preview: false, publish: false, pause: false,
  cancelSession: false, cancelClientSession: false,
  sessionNotes: false, dismiss: false,
  // Wave 4 new
  payDeposit: false, payBalance: false, requestRefund: false,
  clientInvoice: false, providerInvoice: false, reviewRefund: false,
})

// ── Active item tracking ──────────────────────────────────────────────────────
const activeService       = ref(null)
const activeRequest       = ref(null)
const activeBooking       = ref(null)
const activeClientSession = ref(null)
const activeRefundRequest = ref(null)
const activeExploreService = ref(null)

function setActiveService(s) {
  activeService.value = s
  editForm.title              = s.title ?? ''
  editForm.description        = s.description ?? ''
  editForm.category           = s.category ?? 'supervision'
  editForm.price_cents        = s.price_cents ?? null
  editForm.price_type         = s.price_type ?? 'session'
  editForm.duration_min       = s.duration_min ?? null
  editForm.format             = s.format ?? 'telehealth'
  editForm.availability       = s.availability ?? 'open'
  editForm.availability_label = s.availability_label ?? ''
  editForm.status             = s.status ?? 'active'
  editDollars.value           = s.price_cents ? s.price_cents / 100 : null
}
function setActiveRequest(r) {
  activeRequest.value = r
  acceptForm.session_date = r?.preferred_date ?? ''
  if (r?.preferred_time)     acceptForm.session_time = r.preferred_time
  if (r?.preferred_timezone) acceptForm.timezone     = r.preferred_timezone
  acceptForm.negotiated_amount_cents = null
}
function setActiveBooking(b) { activeBooking.value = b }

// ── Computed counts ───────────────────────────────────────────────────────────
const newRequests = computed(() => props.serviceRequests.filter(r => r.status === 'new'))
const pendingOutgoing = computed(() => props.outgoingRequests.filter(r => r.status === 'new'))
const pendingClientActions = computed(() => {
  const depositDue  = props.clientSessions.filter(s => s.can_pay_deposit).length
  const balanceDue  = props.clientSessions.filter(s => s.can_pay_balance).length
  return depositDue + balanceDue + pendingOutgoing.value.length
})

// ── Listing filters (backend-driven) ─────────────────────────────────────────
const listingSearch       = ref(props.filters?.q ?? '')
const listingTypeFilter   = ref(props.filters?.category ?? '')
const listingStatusFilter = ref(props.filters?.status ?? '')
const filteredListings    = computed(() => props.listings)

let listingSearchTimer = null
watch([listingSearch, listingTypeFilter, listingStatusFilter], () => {
  clearTimeout(listingSearchTimer)
  listingSearchTimer = setTimeout(() => {
    router.visit(route('provider.services.index'), {
      method: 'get',
      data: { q: listingSearch.value || undefined, category: listingTypeFilter.value || undefined, status: listingStatusFilter.value || undefined },
      preserveState: true, preserveScroll: true, replace: true,
    })
  }, 350)
})

// ── Bookings tab ──────────────────────────────────────────────────────────────
const bookingSearch    = ref('')
const bookingDateRange = ref('this_month')

const bookingPeriodLabel = computed(() =>
  ({ this_month: 'This Month', last_month: 'Last Month', last_60: 'Last 60 Days', last_90: 'Last 90 Days', this_year: 'This Year', all: 'All Time' }[bookingDateRange.value] ?? 'This Month')
)

function goToBookingsPage(page) {
  router.visit(route('provider.services.index'), {
    data: { bookings_page: page },
    preserveState: true, preserveScroll: true, replace: true,
  })
}

function goToClientSessionsPage(page) {
  router.visit(route('provider.services.index'), {
    data: { client_sessions_page: page },
    preserveState: true, preserveScroll: true, replace: true,
  })
}

// ── Explore tab ───────────────────────────────────────────────────────────────
const exploreFilters  = reactive({ q: props.exploreFilters?.q ?? '', category: props.exploreFilters?.category ?? '', format: props.exploreFilters?.format ?? '', availability: props.exploreFilters?.availability ?? '', sort: props.exploreFilters?.sort ?? 'newest' })
const exploreLoading  = ref(false)
const exploreSentinel = ref(null)
const exploreMeta     = ref({ ...props.exploreMeta })
const exploreResultsLocal = ref([...props.exploreResults])

watch(() => props.exploreResults, (v) => { exploreResultsLocal.value = [...v] })
watch(() => props.exploreMeta,    (v) => { exploreMeta.value = { ...v } })

const serviceCategories = [
  { value: 'supervision',         label: 'Supervision' },
  { value: 'consultation',        label: 'Consultation' },
  { value: 'training',            label: 'Training' },
  { value: 'coaching',            label: 'Coaching' },
  { value: 'practice_continuity', label: 'Practice Continuity' },
  { value: 'other',               label: 'Other' },
]
const sortOptions = [
  { value: 'newest',     label: 'Newest first' },
  { value: 'oldest',     label: 'Oldest first' },
  { value: 'price_asc',  label: 'Price: low → high' },
  { value: 'price_desc', label: 'Price: high → low' },
]

function toggleExploreCategory(val) {
  exploreFilters.category = exploreFilters.category === val ? '' : val
}

function clearExploreFilters() {
  Object.assign(exploreFilters, { q: '', category: '', format: '', availability: '', sort: 'newest' })
  doExploreSearch()
}

function doExploreSearch() {
  exploreResultsLocal.value = []
  router.visit(route('provider.services.index'), {
    method: 'get',
    data: { tab: 'explore', q: exploreFilters.q || undefined, category: exploreFilters.category || undefined, format: exploreFilters.format || undefined, availability: exploreFilters.availability || undefined, sort: exploreFilters.sort !== 'newest' ? exploreFilters.sort : undefined },
    preserveState: true, preserveScroll: true, replace: true,
  })
}

// Infinite scroll — loads next page via JSON and appends
const { observe: observeExplore, disconnect: disconnectExplore, isLoading: exploreScrollLoading } = useInfiniteScroll(
  exploreSentinel,
  loadMoreExplore,
  {
    canLoad: computed(() => (exploreMeta.value.current_page ?? 1) < (exploreMeta.value.last_page ?? 1)),
  }
)

async function loadMoreExplore() {
  if (exploreLoading.value) return
  const nextPage = (exploreMeta.value.current_page ?? 1) + 1
  if (nextPage > (exploreMeta.value.last_page ?? 1)) return
  exploreLoading.value = true
  try {
    const params = new URLSearchParams({
      page: String(nextPage),
      ...(exploreFilters.q         && { q: exploreFilters.q }),
      ...(exploreFilters.category  && { category: exploreFilters.category }),
      ...(exploreFilters.format    && { format: exploreFilters.format }),
      ...(exploreFilters.availability && { availability: exploreFilters.availability }),
      ...(exploreFilters.sort !== 'newest' && { sort: exploreFilters.sort }),
    })
    const response = await window.axios.get(route('provider.services.explore') + '?' + params.toString())
    exploreResultsLocal.value.push(...(response.data.results ?? []))
    exploreMeta.value = {
      current_page: response.data.current_page,
      last_page:    response.data.last_page,
      total:        response.data.total,
      per_page:     exploreMeta.value.per_page,
    }
  } catch {
    toast.error('Could not load more services. Please try again.')
  } finally {
    exploreLoading.value = false
  }
}

onMounted(() => observeExplore())
onUnmounted(() => disconnectExplore())

const exploreResults = computed(() => exploreResultsLocal.value)

function openExploreRequest(svc) {
  activeExploreService.value = svc
  openModal('serviceRequestModal')
}

// ── Create form ───────────────────────────────────────────────────────────────
const createDollars = ref(null)
const createForm = reactive({
  category: 'supervision', title: '', description: '',
  duration_min: 60, format: 'telehealth', price_cents: null,
  price_type: 'session', sliding_scale: false,
  availability: 'open', availability_label: '', is_public: true,
})
watch(createDollars, (v) => {
  createForm.price_cents = v != null ? Math.round(Number(v) * 100) : null
  createV$.value.price_cents?.$touch()
})
const createSubmitIntent = ref('draft')
const createRules = computed(() => {
  const isPublish = createSubmitIntent.value === 'active'
  return {
    title: { required: helpers.withMessage('Service name is required.', required), max: helpers.withMessage('Max 200 characters.', maxLength(200)) },
    description: isPublish ? { required: helpers.withMessage('Description is required to publish.', required), max: helpers.withMessage('Max 5000 characters.', maxLength(5000)) } : { max: helpers.withMessage('Max 5000 characters.', maxLength(5000)) },
    price_cents: isPublish ? { requiredIfPaid: helpers.withMessage('Enter a rate, or set pricing to "Contact for pricing".', helpers.withParams({ type: 'requiredIfPaid' }, (v) => createForm.price_type === 'inquiry' || (v !== null && v >= 0))) } : {},
  }
})
const createV$ = useVuelidate(createRules, createForm)
const createServerErrors = ref({})
function createFieldError(field) {
  if (createV$.value[field]?.$error) return createV$.value[field].$errors[0]?.$message ?? ''
  return createServerErrors.value[field] ?? ''
}
function resetCreateModal() {
  modals.create = false
  Object.assign(createForm, { category: 'supervision', title: '', description: '', duration_min: 60, format: 'telehealth', price_cents: null, price_type: 'session', sliding_scale: false, availability: 'open', availability_label: '', is_public: true })
  createDollars.value = null; createServerErrors.value = {}; createV$.value.$reset(); createSubmitIntent.value = 'draft'
}
async function submitCreate(status) {
  createSubmitIntent.value = status
  await nextTick()
  const ok = await createV$.value.$validate()
  if (!ok) { toast.error(status === 'active' ? 'Please fix the highlighted fields before publishing.' : 'Service name is required.'); return }
  router.post(route('provider.services.store'), { title: createForm.title, description: createForm.description, category: createForm.category, price_cents: createForm.price_cents, price_type: createForm.price_type, duration_min: createForm.duration_min, format: createForm.format, availability: createForm.availability, availability_label: createForm.availability_label, is_public: createForm.is_public, status }, {
    preserveScroll: true,
    onSuccess: () => { resetCreateModal(); toast.success(status === 'active' ? 'Listing published!' : 'Saved as draft.') },
    onError: (errors) => { createServerErrors.value = errors; toast.error('Please fix the highlighted fields.') },
  })
}

// ── Edit form ─────────────────────────────────────────────────────────────────
const editDollars = ref(null)
const editForm = reactive({ title: '', description: '', category: 'supervision', price_cents: null, price_type: 'session', duration_min: null, format: 'telehealth', availability: 'open', availability_label: '', status: 'active' })
watch(editDollars, (v) => { editForm.price_cents = v != null ? Math.round(Number(v) * 100) : null })
const editV$ = useVuelidate({ title: { required } }, editForm)
function editFieldError(field) { return editV$.value[field].$errors[0]?.$message ?? '' }
async function submitEdit() {
  const ok = await editV$.value.$validate()
  if (!ok) return
  if (!activeService.value?.id) { toast.error('No service selected.'); return }
  router.put(route('provider.services.update', { service: activeService.value.id }), { title: editForm.title, description: editForm.description, category: editForm.category, price_cents: editForm.price_cents, price_type: editForm.price_type, duration_min: editForm.duration_min, format: editForm.format, availability: editForm.availability, availability_label: editForm.availability_label, status: editForm.status }, {
    preserveScroll: true,
    onSuccess: () => { modals.edit = false; toast.success('Changes saved!') },
  })
}
function deleteServiceFromCard() {
  if (!activeService.value?.id) return
  router.delete(route('provider.services.destroy', { service: activeService.value.id }), { preserveScroll: true, onSuccess: () => toast.info('Service deleted.') })
}
function resumeService() {
  if (!activeService.value?.id) return
  router.put(route('provider.services.update', { service: activeService.value.id }), { status: 'active' }, { preserveScroll: true, onSuccess: () => toast.success('Listing resumed.') })
}

// ── Accept form (Wave 5: adds negotiated_amount_cents) ────────────────────────
const acceptForm = reactive({ session_date: '', session_time: '10:00', timezone: 'America/New_York', format: 'Virtual (Telehealth)', note: '', recurring: true, negotiated_amount_cents: null })
const todayDate  = new Date().toISOString().split('T')[0]

function submitAccept() {
  if (!activeRequest.value?.service_id || !activeRequest.value?.id) { toast.error('No request selected.'); return }
  if (!acceptForm.session_date) { toast.error('Please select a session date.'); return }
  router.post(route('provider.services.request.accept', { service: activeRequest.value.service_id, serviceRequest: activeRequest.value.id }), {
    session_date:             acceptForm.session_date,
    session_time:             acceptForm.session_time,
    timezone:                 acceptForm.timezone,
    format:                   acceptForm.format,
    note:                     acceptForm.note,
    recurring:                acceptForm.recurring,
    negotiated_amount_cents:  acceptForm.negotiated_amount_cents,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      modals.accept = false
      toast.success('Request accepted. The client will be notified to pay their deposit.')
    },
  })
}

// ── Refund escalation ─────────────────────────────────────────────────────────
function escalateRefund(ses) {
  const rr = props.incomingRefundRequests.find(r => r.session_id === ses.id)
    || { id: ses.refund_request_id }
  if (!rr?.id) { toast.error('No denied refund request found for this session.'); return }
  confirmAction({
    title:    'Escalate to Dispute',
    message:  'This will open a formal dispute that Aegis admin will review. Are you sure?',
    btnLabel: 'Escalate',
    type:     'danger',
  }, () => {
    router.post(route('provider.services.refund.escalate', { refund: rr.id }), {}, {
      preserveScroll: true,
      onSuccess: () => toast.success('Escalated to dispute. Our team will review.'),
      onError:   () => toast.error('Could not escalate. Please contact support.'),
    })
  })
}

// ── Dismiss / counter forms ───────────────────────────────────────────────────
const dismissForm = reactive({ reason: '', otherReason: '' })
const DISMISS_REASONS = ['Scheduling conflict','Outside my scope of practice','Client is not a good fit','Already at full capacity','Request is incomplete or unclear','Other','Prefer not to say']
function submitDismiss() {
  if (!activeRequest.value?.id) return
  const req = activeRequest.value
  if (!req?.service_id) { toast.error('Cannot dismiss: request data missing.'); return }
  const reason = dismissForm.reason === 'Other' ? (dismissForm.otherReason.trim() || 'Other') : (dismissForm.reason || 'Dismissed by practitioner')
  router.post(route('provider.services.request.decline', { service: req.service_id, serviceRequest: req.id }), { reason }, { preserveScroll: true, onSuccess: () => { modals.dismiss = false; toast.info('Request dismissed.') } })
}

const counterForm = reactive({ proposed_date: '', proposed_time: '10:00', message: '' })
const counterV$   = useVuelidate({ message: { required } }, counterForm)
function counterFieldError(field) { return counterV$.value[field].$errors[0]?.$message ?? '' }
async function submitCounter() {
  const ok = await counterV$.value.$validate()
  if (!ok) return
  if (!activeRequest.value?.service_id || !activeRequest.value?.id) { toast.error('No request selected.'); return }
  router.post(route('provider.services.request.decline', { service: activeRequest.value.service_id, serviceRequest: activeRequest.value.id }), {
    reason: `Counter proposal for ${counterForm.proposed_date} at ${counterForm.proposed_time} — ${counterForm.message}`,
  }, {
    preserveScroll: true,
    onSuccess: () => { modals.counter = false; toast.success('Counter proposal sent.'); counterForm.proposed_date = ''; counterForm.proposed_time = '10:00'; counterForm.message = ''; counterV$.value.$reset() },
  })
}

function withdrawOutgoingRequest(id) {
  confirmAction({ title: 'Withdraw Request', message: 'Withdraw this service request? The provider will be notified.', btnLabel: 'Withdraw', type: 'danger' }, () => {
    router.delete(route('provider.services.request.withdraw', { serviceRequest: id }), { preserveScroll: true, onSuccess: () => toast.info('Request withdrawn.') })
  })
}

// ── Publish / pause forms ─────────────────────────────────────────────────────
const publishForm  = reactive({ notify: true })
const pauseForm    = reactive({ reason: '' })
const reactivateForm = reactive({ restore_avail: true })

function submitPublish() {
  if (!activeService.value?.id) return
  router.put(route('provider.services.update', { service: activeService.value.id }), { status: 'active' }, { preserveScroll: true, onSuccess: () => { modals.publish = false; toast.success('Listing published!') } })
}
function submitPause() {
  if (!activeService.value?.id) return
  router.put(route('provider.services.update', { service: activeService.value.id }), { status: 'paused', pause_reason: pauseForm.reason }, { preserveScroll: true, onSuccess: () => { modals.pause = false; toast.warning('Listing paused.') } })
}

// ── Cancel session (provider side) ────────────────────────────────────────────
const cancelSessionForm = reactive({ reason: '', note: '', offer_reschedule: true })
const cancelV$  = useVuelidate({ reason: { required } }, cancelSessionForm)
function cancelFieldError(field) { return cancelV$.value[field].$errors[0]?.$message ?? '' }
async function submitCancelSession() {
  const ok = await cancelV$.value.$validate()
  if (!ok) return
  if (!activeBooking.value?.id) { toast.error('No session selected.'); return }
  router.post(route('provider.services.session.cancel', { session: activeBooking.value.id }), cancelSessionForm, {
    preserveScroll: true,
    onSuccess: () => { modals.cancelSession = false; toast.warning(`Session cancelled — ${activeBooking.value?.client_name} notified.`) },
  })
}

// ── Session notes ─────────────────────────────────────────────────────────────
const notesForm = reactive({ summary: '', action_items: '', share_with_supervisee: false })
function submitNotes() {
  if (!activeBooking.value?.id) { toast.error('No session selected.'); return }
  router.post(route('provider.services.session.notes', { session: activeBooking.value.id }), notesForm, { preserveScroll: true, onSuccess: () => { modals.sessionNotes = false; toast.success('Notes saved.') } })
}

// ── Settings profile ──────────────────────────────────────────────────────────
const profileForm  = reactive({ headline: props.serviceProfile?.headline ?? '', years_experience: props.serviceProfile?.years_experience ?? 0, bio: props.serviceProfile?.bio ?? '', specialties: [...(props.serviceProfile?.specialties ?? [])] })
const newSpecialty = ref('')
function addSpecialty()    { const v = newSpecialty.value.trim(); if (!v) return; profileForm.specialties.push(v); newSpecialty.value = '' }
function removeSpecialty(i) { profileForm.specialties.splice(i, 1) }
function saveProfile() {
  router.put(route('provider.profile.services'), { services: profileForm.specialties, service_bio: profileForm.bio, service_headline: profileForm.headline, years_experience: profileForm.years_experience }, { preserveScroll: true, onSuccess: () => toast.success('Services profile saved!') })
}
function resetProfile() {
  profileForm.headline = props.serviceProfile?.headline ?? ''; profileForm.years_experience = props.serviceProfile?.years_experience ?? 0
  profileForm.bio = props.serviceProfile?.bio ?? ''; profileForm.specialties = [...(props.serviceProfile?.specialties ?? [])]
  toast.info('Changes discarded.')
}

// ── Preview (kept for listings grid) ─────────────────────────────────────────
function openPreview(s) { setActiveService(s); /* Preview removed from Wave 5 — edit opens instead */ modals.edit = true }

// ── Helpers ───────────────────────────────────────────────────────────────────
function initials(name) { return (name || '').split(' ').slice(0, 2).map(p => p[0] ?? '').join('').toUpperCase() || '?' }
function statusLabel(s)   { return { completed: 'Completed', upcoming: 'Upcoming', cancelled: 'Cancelled', accepted: 'Accepted', declined: 'Declined', pending: 'Pending', new: 'New', scheduled: 'Scheduled', withdrawn: 'Withdrawn' }[s] ?? s }
function statusVariant(s) { return { completed: 'green', upcoming: 'blue', cancelled: 'neutral', accepted: 'green', declined: 'neutral', pending: 'gold', new: 'gold', scheduled: 'blue', withdrawn: 'neutral' }[s] ?? 'neutral' }

const serviceTypeOptions = [
  { key: 'supervision',         label: 'Supervision',         icon: 'graduation-cap', desc: 'Individual or group' },
  { key: 'consultation',        label: 'Consultation',        icon: 'message',        desc: 'Case or peer' },
  { key: 'training',            label: 'Training',            icon: 'book-open',      desc: 'Workshop or series' },
  { key: 'coaching',            label: 'Coaching',            icon: 'leaf',           desc: 'Practice or career' },
  { key: 'practice_continuity', label: 'Practice Continuity', icon: 'shield',         desc: 'Continuity Steward / succession' },
  { key: 'other',               label: 'Other',               icon: 'sparkles',       desc: 'Custom service' },
]
</script>

<style scoped>
/* ── SERVICES LAYOUT ── */
.svc-layout {
  display: flex;
  align-items: flex-start;
  gap: 22px;
}
.svc-sidebar {
  width: 210px;
  flex-shrink: 0;
  position: sticky;
  top: 80px;
}
.svc-content {
  flex: 1;
  min-width: 0;
}

/* ── EXPLORE FILTER BAR — 3-row layout ── */
.explore-filter-bar {
  display: flex;
  flex-direction: column;
  gap: 0;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  margin-bottom: 16px;
}

/* Each row gets a bottom divider except the last */
.explore-filter-row {
  display: flex;
  align-items: center;
  padding: 10px 14px;
  gap: 8px;
}
.explore-filter-row--search   { border-bottom: 1px solid var(--border); }
.explore-filter-row--dropdowns { border-bottom: 1px solid var(--border); gap: 8px; flex-wrap: wrap; padding: 10px 14px; }
.explore-filter-row--meta     { padding: 7px 14px; background: var(--surface-2); border-radius: 0 0 var(--radius-lg) var(--radius-lg); }

/* Row 1 — search full width */
.explore-filter-search {
  position: relative;
  flex: 1;
  width: 100%;
}
.explore-filter-search .aegis-icon {
  position: absolute; left: 10px; top: 50%;
  transform: translateY(-50%); color: var(--text-4); pointer-events: none;
}
.explore-filter-search .form-control { padding-left: 34px; width: 100%; }

/* Row 2 — dropdowns, each takes equal share */
.explore-filter-select-wrap {
  position: relative;
  display: flex;
  align-items: center;
  flex: 1;
  min-width: 130px;
}
.explore-filter-select-wrap .aegis-icon {
  position: absolute; left: 9px; top: 50%;
  transform: translateY(-50%); color: var(--text-4); pointer-events: none; z-index: 1;
}
.explore-filter-select {
  width: 100%;
  padding-left: 28px;
  padding-right: 10px;
  font-size: 12px;
  height: 34px;
  border-radius: var(--radius);
  cursor: pointer;
}
.explore-filter-select-wrap.has-value .explore-filter-select {
  border-color: var(--gold);
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
  font-weight: 600;
}
.explore-filter-select-wrap.has-value .aegis-icon { color: var(--gold-dark); }

/* Row 3 — meta: count left, clear right */
.explore-filter-row--meta { justify-content: space-between; }
.explore-count { font-size: 12px; font-weight: 600; color: var(--text-4); }
.explore-clear-btn {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 700; color: var(--text-3);
  background: var(--surface-2); border: 1px solid var(--border);
  border-radius: 100px; padding: 2px 9px; cursor: pointer;
  transition: all var(--transition);
}
.explore-clear-btn:hover { border-color: var(--gold); color: var(--gold-dark); }

.explore-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 14px;
  margin-bottom: 12px;
}
.explore-loading {
  display: flex; align-items: center; gap: 8px; justify-content: center;
  padding: 20px; color: var(--text-3); font-size: 13px; font-weight: 600;
}
.explore-sentinel { height: 1px; }
.explore-end-note {
  display: flex; align-items: center; gap: 6px; justify-content: center;
  padding: 12px; font-size: 12px; font-weight: 600; color: var(--text-4);
}

/* ── LISTINGS ── */
.services-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px; margin-bottom: 22px; }
.services-grid .card { display: flex; flex-direction: column; }
.services-grid .card .card-body { flex: 1; }
.svc-type-icon { width: 48px; height: 48px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--badge-bg-gold); color: var(--gold-dark); }
.svc-description { font-size: 13px; color: var(--text-2); line-height: 1.55; margin-bottom: 14px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.svc-meta-row { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; }
.svc-meta-item { display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--text-3); font-weight: 600; }
.svc-price-row { display: flex; align-items: baseline; gap: 6px; padding: 10px 14px; background: var(--surface-2); border-radius: var(--radius-sm); }
.svc-price      { font-family: var(--font-serif); font-size: 22px; font-weight: 700; color: var(--text); }
.svc-price-unit { font-size: 12px; color: var(--text-3); font-weight: 600; }
.svc-card-metrics { display: grid; grid-template-columns: repeat(3,1fr); gap: 1px; background: var(--border); border-top: 1px solid var(--border); }
.svc-metric { background: var(--surface-2); padding: 10px 14px; text-align: center; }
.svc-metric-val   { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); }
.svc-metric-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-4); margin-top: 2px; }
.card-footer { display: flex; gap: 6px; align-items: center; }

/* ── REQUESTS ── */
.refund-requests-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
.refund-request-card .card-footer { padding: 10px 16px; border-top: 1px solid var(--border); }
.requests-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 22px; }
.request-card.new { border-left: 3px solid var(--gold-dark); }
.req-info { flex: 1; min-width: 180px; }
.req-name   { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 2px; }
.req-detail { font-size: 12px; color: var(--text-3); font-weight: 600; }
.req-service { flex: 1; min-width: 160px; }
.req-service-name { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 2px; }
.req-service-type { font-size: 10px; color: var(--text-4); text-transform: uppercase; letter-spacing: 0.4px; font-weight: 700; }
.req-date { font-size: 12px; color: var(--text-3); min-width: 100px; text-align: right; flex-shrink: 0; font-weight: 600; }
.req-actions { display: flex; gap: 8px; flex-shrink: 0; align-items: center; }

/* ── SHARED TABLE ── */
.card-flush .card-body { padding: 0; }
.card-flush .table-wrap { border: none; border-radius: 0; box-shadow: none; }
.td-provider { display: flex; align-items: center; gap: 10px; }
.td-name     { font-weight: 700; color: var(--text); font-size: 13px; }
.td-cred     { font-size: 11px; color: var(--text-3); font-weight: 600; }
.td-sub      { font-size: 12px; color: var(--text-4); font-weight: 500; }
.link-name, a.link-name { color: var(--gold-dark); text-decoration: none; font-weight: 700; }
.link-name:hover { text-decoration: underline; }
.table-wrap { border-color: var(--border-dark); box-shadow: var(--shadow); background: var(--surface); }
.table-wrap .table thead tr { background: var(--surface-3); }
.table-wrap .table thead th { color: var(--text); font-weight: 700; font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase; border-bottom: 1px solid var(--border-dark); padding: 11px 14px; }
.table-wrap .table tbody tr { background: var(--surface); }
.table-wrap .table tbody tr:hover { background: var(--badge-bg-gold); }
.table-wrap .table tbody td { border-bottom: 1px solid var(--border); color: var(--text); }
.table-wrap .table tbody tr:last-child td { border-bottom: none; }

/* ── SECTION ── */
.section-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 100px; font-size: 10px; font-weight: 700; line-height: 1; background: var(--surface-3); color: var(--text-3); vertical-align: middle; margin-left: 6px; }
.badge-pill { display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 100px; font-size: 10px; font-weight: 700; line-height: 1; background: var(--surface-3); color: var(--text-3); }

/* ── SETTINGS ── */
.settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 22px; }
.card .card-header.is-settings { background: var(--surface-2); }

/* ── PRICING OPTIONS (create/edit modal) ── */
.pricing-options { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; margin-bottom: 14px; }
.pricing-opt { border: 1.5px solid var(--border); border-radius: var(--radius); padding: 12px; text-align: center; cursor: pointer; transition: background var(--transition); background: var(--surface); }
.pricing-opt:hover    { background: var(--badge-bg-gold); }
.pricing-opt.selected { background: var(--badge-bg-gold); }
.pricing-opt-label { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 3px; display: inline-flex; align-items: center; gap: 6px; }
.pricing-opt.selected .pricing-opt-label { color: var(--gold-dark); }
.pricing-opt-desc { font-size: 11px; color: var(--text-3); font-weight: 600; }

/* ── PAGE NOTE ── */
.page-note { display: flex; align-items: flex-start; gap: 8px; background: var(--surface-2); border: 1px solid var(--border); border-left: 3px solid var(--gold); border-radius: var(--radius); padding: 10px 14px; color: var(--text-2); font-size: 13px; line-height: 1.5; margin-bottom: 16px; }
.page-note .aegis-icon { flex-shrink: 0; margin-top: 1px; color: var(--gold); }

/* ── TOOLBAR ── */
.svc-toolbar { display: grid; grid-template-columns: repeat(12, 1fr); gap: 10px; margin-bottom: 18px; align-items: center; }
.svc-toolbar .search-wrap { grid-column: span 6; position: relative; min-width: 0; }
.svc-toolbar > .form-select { grid-column: span 3; min-width: 0; }
.svc-toolbar .search-wrap .aegis-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-4); pointer-events: none; }
.svc-toolbar .search-wrap .form-control { padding-left: 38px; width: 100%; }
@media (max-width: 720px) { .svc-toolbar { grid-template-columns: 1fr; } .svc-toolbar .search-wrap, .svc-toolbar > .form-select { grid-column: 1 / -1; } }
@media (max-width: 900px) { .services-grid { grid-template-columns: 1fr; } .settings-grid { grid-template-columns: 1fr; } }
@media (max-width: 600px) { .pricing-options { grid-template-columns: 1fr 1fr; } }

/* ── RESPONSIVE: svc-layout stacks on narrow screens ── */
@media (max-width: 860px) {
  .svc-layout { flex-direction: column; }
  .svc-sidebar {
    width: 100% !important;
    position: static;
    display: flex;
    flex-wrap: wrap;
    gap: 0;
    border-radius: var(--radius-lg);
  }
  .svc-sidebar .page-sidebar-group {
    display: flex;
    flex-wrap: wrap;
    padding: 4px 6px;
    border-bottom: none;
  }
  .svc-sidebar .page-sidebar-label { display: none; }
  .svc-sidebar .page-sidebar-item {
    width: auto;
    flex: 0 0 auto;
    border-left: none;
    border-radius: var(--radius-sm);
    padding: 6px 12px;
    font-size: 12px;
  }
  .svc-sidebar .page-sidebar-item.active::before { display: none; }
  .svc-sidebar .page-sidebar-icon  { display: none; }
  .explore-filter-bar { border-radius: var(--radius); }
  .explore-filter-row--dropdowns { gap: 6px; }
  .explore-filter-select-wrap { min-width: 120px; flex: 1 1 calc(50% - 6px); }
}
</style>
