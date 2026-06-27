<template>
  <AppLayout :user="user" portal="practitioner" activePage="my-services" pageTitle="My Services">

    <!-- HERO BANNER -->
    <div class="ms-hero">
      <div class="ms-hero-left">
        <div class="ms-eyebrow">PROVIDER SERVICES</div>
        <h1 class="ms-title">My Services</h1>
        <p class="ms-sub">Offer supervision, consultation, training, and practice continuity services &amp; more to other providers on the Aegis network.</p>
        <div class="ms-hero-meta">
          <span class="ms-meta-item">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Services Mode: Active
          </span>
          <span class="ms-meta-item">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Discoverable In Provider Search
          </span>
          <span class="ms-meta-item">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polygon points="12 2 15 8.5 22 9.3 17 14 18.2 21 12 17.5 5.8 21 7 14 2 9.3 9 8.5 12 2"/></svg>
            4.8 / 5.0 Rating
          </span>
        </div>
      </div>
      <div class="ms-hero-actions">
        <button class="btn btn-outline btn-sm ms-btn-icon" @click="showToast('Opening activity log','info')">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          Activity
        </button>
        <button class="btn btn-primary btn-sm ms-btn-icon" @click="openAddModal">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add New Service
        </button>
      </div>
    </div>

    <!-- STAT CARDS -->
    <div class="ms-stats">
      <div class="ms-stat">
        <div class="ms-stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div>
        <div><div class="ms-stat-value">4</div><div class="ms-stat-label">Active Listings</div></div>
      </div>
      <div class="ms-stat">
        <div class="ms-stat-icon ms-stat-icon-gold"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div><div class="ms-stat-value">3</div><div class="ms-stat-label">Pending Requests</div></div>
      </div>
      <div class="ms-stat">
        <div class="ms-stat-icon ms-stat-icon-blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
        <div><div class="ms-stat-value">3</div><div class="ms-stat-label">Sessions This Month</div></div>
      </div>
      <div class="ms-stat">
        <div class="ms-stat-icon ms-stat-icon-green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        <div><div class="ms-stat-value">$525</div><div class="ms-stat-label">Revenue This Month</div></div>
      </div>
    </div>

    <!-- INNER TABS -->
    <div class="ms-tabs">
      <button class="ms-tab" :class="{ active: activeTab === 'listings' }" @click="activeTab = 'listings'">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        My Listings <span class="ms-tab-count">{{ services.filter(s=>s.status==='ACTIVE').length }}</span>
      </button>
      <button class="ms-tab" :class="{ active: activeTab === 'requests' }" @click="activeTab = 'requests'">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Service Requests <span class="ms-tab-count">3</span>
      </button>
      <button class="ms-tab" :class="{ active: activeTab === 'bookings' }" @click="activeTab = 'bookings'">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Bookings &amp; Sessions <span class="ms-tab-count">24</span>
      </button>
      <button class="ms-tab" :class="{ active: activeTab === 'settings' }" @click="activeTab = 'settings'">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Settings
      </button>
    </div>

    <!-- MY LISTINGS TAB -->
    <div v-show="activeTab === 'listings'">
      <div class="ms-grid">
        <div v-for="svc in services" :key="svc.id" class="ms-card" @click="openEditModal(svc)">
          <!-- Status badge -->
          <div class="ms-card-top">
            <div class="ms-card-type-icon">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" v-html="svc.typeIcon"></svg>
            </div>
            <span class="ms-status-badge" :class="'ms-status-' + svc.status.toLowerCase()">{{ svc.status }}</span>
          </div>
          <div class="ms-card-title">{{ svc.title }}</div>
          <div class="ms-card-category">{{ svc.category }}</div>
          <div class="ms-card-desc">{{ svc.desc }}</div>
          <!-- Details row -->
          <div class="ms-card-details">
            <span v-if="svc.duration" class="ms-detail-item">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              {{ svc.duration }}
            </span>
            <span v-if="svc.schedule" class="ms-detail-item">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              {{ svc.schedule }}
            </span>
            <span v-if="svc.format" class="ms-detail-item">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
              {{ svc.format }}
            </span>
            <span v-if="svc.spotsLeft" class="ms-detail-item ms-detail-green">{{ svc.spotsLeft }}</span>
            <span v-if="svc.pausedNote" class="ms-detail-item ms-detail-orange">{{ svc.pausedNote }}</span>
          </div>
          <!-- Price row -->
          <div class="ms-card-price-row">
            <span class="ms-card-price">{{ svc.price }}</span>
            <span v-if="svc.priceNote" class="ms-card-price-note">{{ svc.priceNote }}</span>
          </div>
          <!-- Stats -->
          <div class="ms-card-stats">
            <div v-for="stat in svc.stats" :key="stat.label" class="ms-card-stat">
              <div class="ms-card-stat-val">{{ stat.value }}</div>
              <div class="ms-card-stat-lbl">{{ stat.label }}</div>
            </div>
          </div>
          <!-- Action buttons -->
          <div class="ms-card-actions">
            <button class="ms-act-btn" title="Edit" @click.stop="openEditModal(svc)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            <button class="ms-act-btn" title="Preview" @click.stop="openPreviewModal(svc)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            <button class="ms-act-btn" title="View Bookings" @click.stop="activeTab = 'bookings'">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </button>
            <button v-if="svc.status !== 'PAUSED'" class="ms-act-btn ms-act-danger" title="Pause Listing" @click.stop="openPauseModal(svc)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </button>
            <button v-else class="ms-act-btn" title="More options" @click.stop="showToast('Options for ' + svc.title,'info')">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
            </button>
          </div>
        </div>

        <!-- Add New Service card -->
        <div class="ms-add-card" @click="openAddModal">
          <div class="ms-add-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          </div>
          <div class="ms-add-title">Add New Service</div>
          <div class="ms-add-sub">Supervision, consultation, training, coaching &amp; more</div>
        </div>
      </div>
    </div>

    <!-- SERVICE REQUESTS TAB -->
    <div v-show="activeTab === 'requests'">

      <!-- Notice banner -->
      <div class="sr-notice">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>You have <strong class="sr-notice-link">3 pending service requests</strong> that need a response. Requests auto-expire after 72 hours if not accepted.</span>
      </div>

      <!-- Pending Requests -->
      <div class="sr-section-header">
        <div class="sr-section-title">Pending Requests</div>
        <span class="sr-count-pill">{{ pendingServiceRequests.length }}</span>
      </div>

      <div class="sr-pending-list">
        <div v-for="req in pendingServiceRequests" :key="req.id" class="sr-pending-row">
          <div class="sr-pending-left">
            <div class="sr-req-avatar" :style="{ background: req.avatarColor }">{{ req.initials }}</div>
            <div class="sr-req-info">
              <div class="sr-req-name">{{ req.name }}</div>
              <div class="sr-req-role">{{ req.role }}</div>
            </div>
          </div>
          <div class="sr-pending-middle">
            <div class="sr-req-service">{{ req.service }}</div>
            <div class="sr-req-frequency">{{ req.frequency }}</div>
          </div>
          <div class="sr-pending-date">
            <div class="sr-req-date-label">Requested date</div>
            <div class="sr-req-date-val">{{ req.requestedDate }}</div>
          </div>
          <div class="sr-req-time-ago">{{ req.timeAgo }}</div>
          <div class="sr-req-actions">
            <button class="ms-act-btn" title="Message" @click="goToMessages(req)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </button>
            <button class="ms-act-btn" title="Counter Propose" @click="openCounterProposeModal(req)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg>
            </button>
            <button class="ms-act-btn ms-act-danger" title="Dismiss" @click="openDismissModal(req)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <button class="sr-accept-btn" @click="openAcceptModal(req)">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
              Accept
            </button>
          </div>
        </div>
        <div v-if="pendingServiceRequests.length === 0" class="ms-empty" style="padding:24px;">
          <p>No pending requests.</p>
        </div>
      </div>

      <!-- Activity -->
      <div class="sr-section-header" style="margin-top:24px;">
        <div class="sr-section-title">Activity</div>
      </div>
      <div class="bk-table-wrap">
        <table class="bk-table">
          <thead>
            <tr>
              <th>PROVIDER</th>
              <th>SERVICE</th>
              <th>TYPE</th>
              <th>DATE REQUESTED</th>
              <th>STATUS</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in requestActivity" :key="a.id">
              <td>
                <div class="bk-provider-row">
                  <div class="bk-avatar" :style="{ background: a.avatarColor }">{{ a.initials }}</div>
                  <div>
                    <div class="bk-provider-name">{{ a.provider }}</div>
                    <div class="bk-provider-role">{{ a.role }}</div>
                  </div>
                </div>
              </td>
              <td class="bk-td-service">{{ a.service }}</td>
              <td class="bk-td-service">{{ a.type }}</td>
              <td class="bk-td-date">{{ a.date }}</td>
              <td><span class="bk-status" :class="'bk-status-' + a.status.toLowerCase()">{{ a.status }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div v-show="activeTab === 'bookings'">
      <!-- Search + filter bar -->
      <div class="bk-bar">
        <div class="bk-search-wrap">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-4);pointer-events:none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input class="bk-search" placeholder="Search sessions or providers..." />
        </div>
        <select class="bk-select"><option>All Services</option><option>Individual Supervision</option><option>Group Supervision</option><option>Peer Consultation</option></select>
        <select class="bk-select"><option>This Month</option><option>Last Month</option><option>All Time</option></select>
      </div>

      <!-- Sessions table -->
      <div class="bk-table-wrap">
        <div class="bk-table-header">
          <div class="bk-table-title">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Sessions — June 2025
          </div>
          <div class="bk-table-meta">
            <span class="bk-sessions-badge">24 SESSIONS</span>
            <span class="bk-earned-badge">$3,280 EARNED</span>
          </div>
        </div>
        <table class="bk-table">
          <thead>
            <tr>
              <th>PROVIDER</th>
              <th>SERVICE</th>
              <th>DATE &amp; TIME</th>
              <th>DURATION</th>
              <th>AMOUNT</th>
              <th>STATUS</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="session in sessions" :key="session.id">
              <td>
                <div class="bk-provider-row">
                  <div class="bk-avatar" :style="{ background: session.avatarColor }">{{ session.initials }}</div>
                  <div>
                    <div class="bk-provider-name">{{ session.provider }}</div>
                    <div class="bk-provider-role">{{ session.providerRole }}</div>
                  </div>
                </div>
              </td>
              <td class="bk-td-service">{{ session.service }}</td>
              <td class="bk-td-date">{{ session.date }}</td>
              <td class="bk-td-dur">{{ session.duration }}</td>
              <td class="bk-td-amt">{{ session.amount }}</td>
              <td>
                <span class="bk-status" :class="'bk-status-' + session.status.toLowerCase()">{{ session.status }}</span>
              </td>
              <td>
                <button v-if="session.status === 'COMPLETED'" class="ms-act-btn" title="View notes" @click="openSessionNotesModal(session)">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </button>
                <button v-if="session.status === 'UPCOMING'" class="ms-act-btn ms-act-danger" title="Cancel" @click="showToast('Session cancelled','info')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="bk-table-footer">
          <span class="bk-footer-count">Showing {{ sessions.length }} of 3 sessions</span>
          <div class="bk-pagination">
            <button class="bk-page-btn active">1</button>
            <button class="bk-page-btn" @click="showToast('Page 2','info')">2</button>
            <button class="bk-page-btn" @click="showToast('Page 3','info')">3</button>
            <span class="bk-page-ellipsis">…</span>
            <button class="bk-page-btn" @click="showToast('Page 6','info')">6</button>
            <button class="bk-page-btn bk-page-next" @click="showToast('Next page','info')">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-show="activeTab === 'settings'">

      <!-- Profile completion banner -->
      <div class="st-completion-banner">
        <div class="st-completion-left">
          <div class="st-completion-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
          </div>
          <div>
            <div class="st-completion-title">Finish your profile</div>
            <div class="st-completion-sub">Add credentials &amp; availability to improve discovery</div>
          </div>
        </div>
        <div class="st-completion-right">
          <div class="st-bar-wrap">
            <div class="st-bar-track"><div class="st-bar-fill" style="width:78%"></div></div>
            <span class="st-bar-pct">78%</span>
          </div>
          <button class="sr-accept-btn" @click="showToast('Opening profile completion','info')">
            Complete
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </button>
        </div>
      </div>

      <!-- Services Settings card -->
      <div class="st-card" style="margin-bottom:14px;">
        <div class="st-card-header">
          <div class="st-card-header-left">
            <div class="st-card-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></div>
            <div>
              <div class="st-card-title">Services Settings</div>
              <div class="st-card-sub">Visibility, booking preferences, and payment — managed in Account Settings</div>
            </div>
          </div>
          <button class="sr-accept-btn" @click="showToast('Opening account settings','info')">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83"/></svg>
            Open Settings
          </button>
        </div>

        <!-- Visibility & Mode -->
        <div class="st-setting-row">
          <div class="st-setting-left">
            <div class="st-setting-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></div>
            <div>
              <div class="st-setting-name">Visibility &amp; Mode</div>
              <div class="st-setting-desc">Services Mode · Show in search · Accept requests · Show pricing</div>
            </div>
          </div>
          <button class="st-edit-btn" @click="showToast('Editing visibility settings','info')">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
          </button>
        </div>

        <!-- Booking Preferences -->
        <div class="st-setting-row">
          <div class="st-setting-left">
            <div class="st-setting-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
            <div>
              <div class="st-setting-name">Booking Preferences</div>
              <div class="st-setting-desc">Manual approval · Request expiry · Buffer between sessions</div>
            </div>
          </div>
          <button class="st-edit-btn" @click="showToast('Editing booking preferences','info')">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
          </button>
        </div>

        <!-- Payment & Rates -->
        <div class="st-setting-row" style="border-bottom:none;">
          <div class="st-setting-left">
            <div class="st-setting-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
            <div>
              <div class="st-setting-name">Payment &amp; Rates</div>
              <div class="st-setting-desc">Hourly rate · Payment method · Sliding scale</div>
            </div>
          </div>
          <button class="st-edit-btn" @click="showToast('Editing payment settings','info')">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
          </button>
        </div>
      </div>

      <!-- Services Profile card -->
      <div class="st-card">
        <div class="st-card-header" style="border-bottom:1px solid var(--border);padding-bottom:14px;margin-bottom:16px;">
          <div class="st-card-header-left">
            <div class="st-card-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            <div>
              <div class="st-card-title">Services Profile</div>
              <div class="st-card-sub">This appears on your Services tab in provider search results</div>
            </div>
          </div>
        </div>

        <div class="st-profile-grid">
          <div class="ms-modal-field" style="grid-column:span 2;">
            <label class="ms-modal-label">Services Headline</label>
            <input class="ms-modal-input" v-model="settingsProfile.headline" />
          </div>
          <div class="ms-modal-field">
            <label class="ms-modal-label">Years of Experience</label>
            <input class="ms-modal-input" v-model="settingsProfile.years" type="number" min="0" />
          </div>
        </div>

        <div class="ms-modal-field" style="margin-top:14px;">
          <label class="ms-modal-label">Services Bio</label>
          <textarea class="ms-modal-textarea" v-model="settingsProfile.bio" rows="4"></textarea>
        </div>

        <div class="ms-modal-field" style="margin-top:14px;">
          <label class="ms-modal-label">Specialties (shown as tags)</label>
          <div class="st-tags-row">
            <span v-for="(tag, i) in settingsProfile.specialties" :key="tag" class="st-tag">
              {{ tag }}
              <button class="st-tag-remove" @click="settingsProfile.specialties.splice(i,1)">×</button>
            </span>
            <input class="st-tag-input" v-model="newSpecialty" placeholder="Add specialty..." @keyup.enter="addSpecialty" />
            <button class="st-add-btn" @click="addSpecialty">+ Add</button>
          </div>
        </div>

        <div class="st-profile-footer">
          <button class="btn btn-outline btn-sm" @click="showToast('Changes discarded','info')">Cancel</button>
          <button class="ms-save-btn" @click="saveProfile">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
            Save Profile
          </button>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <Teleport to="body">

      <!-- EDIT SERVICE MODAL -->
      <Transition name="ms-modal-fade">
        <div v-if="editModal.open" class="ms-modal-backdrop" @click.self="editModal.open=false">
          <div class="ms-modal">
            <div class="ms-modal-header">
              <div class="ms-modal-title">Edit Service Listing</div>
              <button class="ms-modal-close" @click="editModal.open=false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="ms-modal-body">
              <div class="ms-modal-field">
                <label class="ms-modal-label">Service Name <span style="color:var(--red)">*</span></label>
                <input class="ms-modal-input" v-model="editModal.title" />
              </div>
              <div class="ms-modal-field">
                <label class="ms-modal-label">Description</label>
                <textarea class="ms-modal-textarea" v-model="editModal.desc" rows="4"></textarea>
              </div>
              <div class="ms-modal-row">
                <div class="ms-modal-field">
                  <label class="ms-modal-label">Rate ($)</label>
                  <input class="ms-modal-input" v-model="editModal.rate" placeholder="150" />
                </div>
                <div class="ms-modal-field">
                  <label class="ms-modal-label">Session Duration</label>
                  <select class="ms-modal-select" v-model="editModal.duration">
                    <option>30 minutes</option><option>45 minutes</option><option>50 minutes</option><option>60 minutes</option><option>90 minutes</option><option>Async</option>
                  </select>
                </div>
              </div>
              <div class="ms-modal-field">
                <label class="ms-modal-label">Service Status</label>
                <select class="ms-modal-select" v-model="editModal.status">
                  <option>Active</option><option>Paused</option><option>Draft</option>
                </select>
              </div>
            </div>
            <div class="ms-modal-footer">
              <button class="btn btn-outline btn-sm" @click="editModal.open=false">Cancel</button>
              <button class="ms-delete-btn" @click="deleteService">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                Delete Listing
              </button>
              <button class="ms-save-btn" @click="saveEdit">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Changes
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- PREVIEW MODAL -->
      <Transition name="ms-modal-fade">
        <div v-if="previewModal.open" class="ms-modal-backdrop" @click.self="previewModal.open=false">
          <div class="ms-modal">
            <div class="ms-modal-header">
              <div class="ms-modal-title">Listing Preview</div>
              <button class="ms-modal-close" @click="previewModal.open=false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="ms-modal-body">
              <p class="ms-preview-notice">This is how your listing appears to other providers in search.</p>
              <div class="ms-preview-provider-card">
                <div class="ms-preview-provider-top">
                  <div class="ms-preview-avatar">DR</div>
                  <div class="ms-preview-provider-info">
                    <div class="ms-preview-provider-name">Dr. Sarah Reynolds, PhD, LCSW</div>
                    <div class="ms-preview-provider-role">Clinical Psychologist · Houston, TX</div>
                    <div class="ms-preview-tags">
                      <span class="nw-pcard-tag">Trauma</span>
                      <span class="nw-pcard-tag">DBT</span>
                      <span class="nw-pcard-tag">EMDR</span>
                      <span class="nw-pcard-tag">Complex PTSD</span>
                    </div>
                  </div>
                  <span class="ms-preview-available">AVAILABLE</span>
                </div>
                <div class="ms-preview-service-block" v-if="previewModal.svc">
                  <div class="ms-preview-service-name">{{ previewModal.svc.title }}</div>
                  <div class="ms-preview-service-desc">{{ previewModal.svc.desc }}</div>
                  <div class="ms-preview-service-meta">
                    <span>⏱ {{ previewModal.svc.duration }}</span>
                    <span>★ 4.9 (38 reviews)</span>
                    <span>Virtual only</span>
                  </div>
                  <div class="ms-preview-service-footer">
                    <span class="ms-preview-price">{{ previewModal.svc.price }}<span style="font-size:13px;font-weight:400;color:var(--text-4)"> / session</span></span>
                    <button class="ms-request-btn" @click="showToast('Service requested','success')">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg>
                      Request Service
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div class="ms-modal-footer">
              <button class="btn btn-outline btn-sm" @click="previewModal.open=false">Close</button>
              <button class="ms-save-btn" @click="previewModal.open=false; openEditModal(previewModal.svc)">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit Listing
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- SESSION NOTES MODAL -->
      <Transition name="ms-modal-fade">
        <div v-if="sessionNotesModal.open" class="ms-modal-backdrop" @click.self="sessionNotesModal.open=false">
          <div class="ms-modal">
            <div class="ms-modal-header">
              <div class="ms-modal-title">Session Notes</div>
              <button class="ms-modal-close" @click="sessionNotesModal.open=false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="ms-modal-body">
              <div class="sn-session-chip" v-if="sessionNotesModal.session">
                <strong>{{ sessionNotesModal.session.provider }}, {{ sessionNotesModal.session.providerRole }}</strong>
                <span>{{ sessionNotesModal.session.service }} · {{ sessionNotesModal.session.date }} · {{ sessionNotesModal.session.duration }} · {{ sessionNotesModal.session.amount }}</span>
              </div>
              <div class="ms-modal-field">
                <label class="ms-modal-label">Session Summary</label>
                <textarea class="ms-modal-textarea" v-model="sessionNotesModal.summary" rows="4" placeholder="Key topics covered, progress notes, follow-up items…"></textarea>
              </div>
              <div class="ms-modal-field">
                <label class="ms-modal-label">Action Items for Next Session</label>
                <textarea class="ms-modal-textarea" v-model="sessionNotesModal.actions" rows="3" placeholder="Tasks, readings, or goals to revisit…"></textarea>
              </div>
              <div class="sn-share-row">
                <div>
                  <div class="sn-share-title">Share summary with supervisee</div>
                  <div class="sn-share-sub">They'll receive a copy of these notes via Aegis messaging</div>
                </div>
                <label class="bp-toggle">
                  <input type="checkbox" v-model="sessionNotesModal.share">
                  <span class="bp-toggle-track"><span class="bp-toggle-thumb"></span></span>
                </label>
              </div>
            </div>
            <div class="ms-modal-footer">
              <button class="btn btn-outline btn-sm" @click="sessionNotesModal.open=false">Cancel</button>
              <button class="ms-save-btn" @click="saveSessionNotes">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                Save Notes
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- COUNTER PROPOSE MODAL -->
      <Transition name="ms-modal-fade">
        <div v-if="counterProposeModal.open" class="ms-modal-backdrop" @click.self="counterProposeModal.open=false">
          <div class="ms-modal ms-modal-sm">
            <div class="ms-modal-header">
              <div class="ms-modal-title">Counter Propose</div>
              <button class="ms-modal-close" @click="counterProposeModal.open=false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="ms-modal-body">
              <div class="ms-modal-field">
                <label class="ms-modal-label">Proposed Date &amp; Time</label>
                <input class="ms-modal-input" type="datetime-local" v-model="counterProposeModal.datetime" />
              </div>
              <div class="ms-modal-field">
                <label class="ms-modal-label">Message to Provider <span style="color:var(--red)">*</span></label>
                <textarea class="ms-modal-textarea" v-model="counterProposeModal.message" rows="4" placeholder="Explain your counter-proposal, alternative times, or any questions you have…"></textarea>
              </div>
            </div>
            <div class="ms-modal-footer">
              <button class="btn btn-outline btn-sm" @click="counterProposeModal.open=false">Cancel</button>
              <button class="ms-save-btn" @click="sendCounterProposal">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Send Counter Proposal
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- DISMISS MODAL -->
      <Transition name="ms-modal-fade">
        <div v-if="dismissModal.open" class="ms-modal-backdrop" @click.self="dismissModal.open=false">
          <div class="ms-modal ms-modal-sm">
            <div class="ms-modal-header">
              <div class="ms-modal-title">Dismiss Request</div>
              <button class="ms-modal-close" @click="dismissModal.open=false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="ms-modal-body">
              <p style="font-family:var(--font-sans);font-size:14px;color:var(--text-2);margin:0;">Dismiss this request?</p>
            </div>
            <div class="ms-modal-footer">
              <button class="btn btn-outline btn-sm" @click="dismissModal.open=false">Cancel</button>
              <button class="ms-pause-confirm-btn" @click="confirmDismiss">Dismiss</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- ACCEPT SERVICE REQUEST MODAL -->
      <Transition name="ms-modal-fade">
        <div v-if="acceptModal.open" class="ms-modal-backdrop" @click.self="acceptModal.open=false">
          <div class="ms-modal ms-modal-sm">
            <div class="ms-modal-header">
              <div class="ms-modal-title">Accept Service Request</div>
              <button class="ms-modal-close" @click="acceptModal.open=false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="ms-modal-body">
              <div class="accept-notice">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Accepting will auto-generate a <strong>Service Agreement</strong> for both parties to sign digitally.</span>
              </div>
              <div class="ms-modal-field">
                <label class="ms-modal-label">Confirm Session Date &amp; Time</label>
                <input class="ms-modal-input" type="datetime-local" v-model="acceptModal.datetime" />
              </div>
              <div class="ms-modal-field">
                <label class="ms-modal-label">Session Format</label>
                <select class="ms-modal-select" v-model="acceptModal.format">
                  <option>Virtual (Telehealth)</option>
                  <option>In-Person</option>
                  <option>Phone</option>
                  <option>Hybrid</option>
                </select>
              </div>
              <div class="ms-modal-field">
                <label class="ms-modal-label">Note to Provider (optional)</label>
                <textarea class="ms-modal-textarea" v-model="acceptModal.note" rows="3" placeholder="Any intake info, prep instructions, or a welcome message…"></textarea>
              </div>
              <div class="sn-share-row">
                <div>
                  <div class="sn-share-title">Set as recurring (ongoing relationship)</div>
                </div>
                <label class="bp-toggle">
                  <input type="checkbox" v-model="acceptModal.recurring">
                  <span class="bp-toggle-track"><span class="bp-toggle-thumb"></span></span>
                </label>
              </div>
            </div>
            <div class="ms-modal-footer">
              <button class="btn btn-outline btn-sm" @click="acceptModal.open=false">Cancel</button>
              <button class="ms-save-btn" @click="confirmAccept">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                Accept &amp; Send Agreement
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- PAUSE LISTING MODAL -->
      <Transition name="ms-modal-fade">
        <div v-if="pauseModal.open" class="ms-modal-backdrop" @click.self="pauseModal.open=false">
          <div class="ms-modal ms-modal-sm">
            <div class="ms-modal-header">
              <div class="ms-modal-title">Pause Listing</div>
              <button class="ms-modal-close" @click="pauseModal.open=false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="ms-modal-body">
              <div class="ms-pause-notice">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0;margin-top:1px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>Pausing will hide this listing from provider search. Existing bookings and relationships are not affected.</span>
              </div>
              <div class="ms-modal-field" style="margin-top:14px;">
                <label class="ms-modal-label">Reason for pausing (optional)</label>
                <select class="ms-modal-select" v-model="pauseModal.reason">
                  <option value="">Select reason…</option>
                  <option>Taking a break</option>
                  <option>At capacity</option>
                  <option>Updating listing details</option>
                  <option>Seasonal pause</option>
                  <option>Other</option>
                </select>
              </div>
            </div>
            <div class="ms-modal-footer">
              <button class="btn btn-outline btn-sm" @click="pauseModal.open=false">Keep Active</button>
              <button class="ms-pause-confirm-btn" @click="confirmPause">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                Pause Listing
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <div class="dh-toast-stack">
        <div v-for="t in toasts" :key="t.id" class="dh-toast" :class="t.type">
          <svg v-if="t.type==='success'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span>{{ t.msg }}</span>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';

defineProps({ user: Object });

const activeTab = ref('listings');

const toasts = ref([]);
let toastId = 0;
function showToast(msg, type = 'info') {
  const id = ++toastId;
  toasts.value.push({ id, msg, type });
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id); }, 3500);
}

function openAddModal() { showToast('Opening Add New Service form', 'info'); }

// ── Edit modal ──
const editModal = ref({ open:false, id:null, title:'', desc:'', rate:'', duration:'50 minutes', status:'Active' });
function openEditModal(svc) {
  editModal.value = { open:true, id:svc.id, title:svc.title, desc:svc.desc, rate:svc.price.replace('$',''), duration:svc.duration||'50 minutes', status:svc.status==='ACTIVE'?'Active':'Paused' };
}
function saveEdit() {
  const svc = services.value.find(s => s.id === editModal.value.id);
  if (svc) { svc.title = editModal.value.title; svc.desc = editModal.value.desc; svc.price = '$' + editModal.value.rate; }
  editModal.value.open = false;
  showToast('Changes saved', 'success');
}
function deleteService() {
  services.value = services.value.filter(s => s.id !== editModal.value.id);
  editModal.value.open = false;
  showToast('Listing deleted', 'info');
}

// ── Preview modal ──
const previewModal = ref({ open:false, svc:null });
function openPreviewModal(svc) { previewModal.value = { open:true, svc }; }

// ── Pause modal ──
const pauseModal = ref({ open:false, svc:null, reason:'' });
function openPauseModal(svc) { pauseModal.value = { open:true, svc, reason:'' }; }
function confirmPause() {
  if (pauseModal.value.svc) pauseModal.value.svc.status = 'PAUSED';
  pauseModal.value.open = false;
  showToast('Listing paused', 'info');
}

// ── Session Notes modal ──
const sessionNotesModal = ref({ open:false, session:null, summary:'', actions:'', share:false });
function openSessionNotesModal(session) {
  sessionNotesModal.value = { open:true, session, summary:'', actions:'', share:false };
}
function saveSessionNotes() {
  sessionNotesModal.value.open = false;
  showToast('Session notes saved', 'success');
}

// ── Service Requests data ──
const pendingServiceRequests = ref([
  { id:1, initials:'AM', name:'Dr. Aisha Morales, LCSW',   role:'Licensed 2 yrs · Trauma Therapist · Dallas, TX',  avatarColor:'#a0813e', service:'Individual Clinical Supervision', frequency:'ONGOING · EVERY 2 WEEKS', requestedDate:'Tues, Jun 18 at 10am', timeAgo:'2 hrs ago' },
  { id:2, initials:'JP', name:'Jordan Park, LPC-Associate', role:'Pre-licensed · Child Therapist · Austin, TX',       avatarColor:'#6a4c8c', service:'Individual Clinical Supervision', frequency:'ONGOING · WEEKLY',        requestedDate:'Fri, Jun 21 at 2pm',   timeAgo:'Yesterday' },
  { id:3, initials:'SR', name:'Dr. Sofia Reyes, PhD',       role:'Licensed 6 yrs · Neuropsychologist · Houston, TX', avatarColor:'#4a7a6a', service:'Peer Consultation — Complex Cases', frequency:'ONE-TIME · 75 MIN',      requestedDate:'Mon, Jun 17 — flexible', timeAgo:'2 days ago' },
]);

const requestActivity = ref([
  { id:1, initials:'KL', provider:'Keisha Lewis, LMFT',  role:'Family Therapist',  avatarColor:'#a0813e', service:'Individual Supervision', type:'Ongoing',    date:'Jun 10, 2025', status:'ACCEPTED' },
  { id:2, initials:'MN', provider:'Marcus Nguyen, LCSW', role:'Trauma Therapist',  avatarColor:'#6a4c8c', service:'Group Supervision',      type:'Bi-weekly',  date:'Jun 3, 2025',  status:'ACCEPTED' },
  { id:3, initials:'TC', provider:'Tanya Chen, LPCC',    role:'Grief Counselor',   avatarColor:'#4a7a6a', service:'Peer Consultation',       type:'One-time',   date:'May 28, 2025', status:'DECLINED' },
]);

import { router } from '@inertiajs/vue3';
function goToMessages(req) { router.visit('/provider/messages'); }

// ── Counter Propose modal ──
const counterProposeModal = ref({ open:false, req:null, datetime:'', message:'' });
function openCounterProposeModal(req) { counterProposeModal.value = { open:true, req, datetime:'', message:'' }; }
function sendCounterProposal() {
  counterProposeModal.value.open = false;
  showToast('Counter proposal sent', 'success');
}

// ── Dismiss modal ──
const dismissModal = ref({ open:false, req:null });
function openDismissModal(req) { dismissModal.value = { open:true, req }; }
function confirmDismiss() {
  pendingServiceRequests.value = pendingServiceRequests.value.filter(r => r.id !== dismissModal.value.req?.id);
  dismissModal.value.open = false;
  showToast('Request dismissed', 'info');
}

// ── Accept modal ──
const acceptModal = ref({ open:false, req:null, datetime:'2025-06-18T10:00', format:'Virtual (Telehealth)', note:'', recurring:true });
function openAcceptModal(req) { acceptModal.value = { open:true, req, datetime:'2025-06-18T10:00', format:'Virtual (Telehealth)', note:'', recurring:true }; }
function confirmAccept() {
  pendingServiceRequests.value = pendingServiceRequests.value.filter(r => r.id !== acceptModal.value.req?.id);
  acceptModal.value.open = false;
  showToast('Request accepted — service agreement sent', 'success');
}

// ── Bookings sessions data ──
const sessions = ref([
  { id:1, provider:'Dr. Aisha Morales', providerRole:'LCSW',  initials:'AM', avatarColor:'#a0813e', service:'Individual Supervision', date:'Jun 14, 10:00 AM', duration:'50 min', amount:'$150', status:'COMPLETED' },
  { id:2, provider:'Keisha Lewis',       providerRole:'LMFT',  initials:'KL', avatarColor:'#6a4c8c', service:'Individual Supervision', date:'Jun 13, 2:00 PM',  duration:'50 min', amount:'$150', status:'COMPLETED' },
  { id:3, provider:'Group Supervision',  providerRole:'3 providers', initials:'GRP', avatarColor:'#4a7a6a', service:'Group Supervision', date:'Jun 10, 11:00 AM', duration:'90 min', amount:'$225', status:'COMPLETED' },
  { id:4, provider:'Marcus Nguyen',      providerRole:'LCSW',  initials:'MN', avatarColor:'#7a5c4a', service:'Individual Supervision', date:'Jun 18, 10:00 AM', duration:'50 min', amount:'$150', status:'UPCOMING' },
]);

const services = ref([
  {
    id: 1, status: 'ACTIVE',
    typeIcon: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    title: 'Individual Clinical Supervision',
    category: 'Clinical Supervision',
    desc: 'Individual supervision for post-graduate therapists seeking licensure (LCSW, LPC, MFT). Focus on case conceptualization, ethics, and clinical documentation.',
    duration: '50 min/session', schedule: 'Tues, Thurs, Fri', format: 'Virtual only',
    price: '$150', priceNote: '/ session', priceSub: 'Sliding scale available',
    stats: [
      { value: '8',   label: 'ACTIVE CLIENTS' },
      { value: '42',  label: 'TOTAL SESSIONS' },
      { value: '4.9★', label: 'RATING' },
    ],
  },
  {
    id: 2, status: 'ACTIVE',
    typeIcon: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    title: 'Group Supervision (3–6 providers)',
    category: 'Group Supervision',
    desc: 'Bi-weekly peer consultation group for licensed therapists. Trauma-informed approach, case discussion and peer learning.',
    duration: '90 min/session', schedule: 'Bi-weekly Mon', spotsLeft: '3/6 spots filled',
    price: '$75', priceNote: '/ session / provider', priceSub: '3 spots open',
    stats: [
      { value: '3',   label: 'ENROLLED' },
      { value: '16',  label: 'SESSIONS DONE' },
      { value: '4.8★', label: 'RATING' },
    ],
  },
  {
    id: 3, status: 'ACTIVE',
    typeIcon: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    title: 'Peer Consultation — Complex Cases',
    category: 'Consultation',
    desc: 'One-time or ongoing consultation for complex clinical cases. Specialties: trauma, dissociation, dual diagnosis, and treatment-resistant presentations.',
    duration: '50 or 75 min', schedule: 'Flexible scheduling',
    price: '$120', priceNote: '/ 50 min', priceSub: '$160 / 75 min',
    stats: [
      { value: '12',  label: 'CONSULTATIONS' },
      { value: '9',   label: 'UNIQUE PROVIDERS' },
      { value: '5.0★', label: 'RATING' },
    ],
  },
  {
    id: 4, status: 'ACTIVE',
    typeIcon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
    title: 'Case Review & Documentation Support',
    category: 'Consultation',
    desc: 'Asynchronous review of treatment plans and clinical documentation for quality assurance and compliance.',
    duration: 'Async', schedule: '48-hr turnaround',
    price: '$90', priceNote: '/ review', priceSub: '48-hr turnaround',
    stats: [
      { value: '5',   label: 'THIS MONTH' },
      { value: '31',  label: 'TOTAL' },
      { value: '4.9★', label: 'RATING' },
    ],
  },
  {
    id: 5, status: 'PAUSED',
    typeIcon: '<path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2 3 6 3s6-2 6-3v-5"/>',
    title: 'Trauma-Informed Care Workshop',
    category: 'Training',
    desc: 'Half-day workshop on trauma-informed clinical practice for groups and agencies. CE credits available.',
    duration: '4 hours', schedule: 'Up to 20 attendees',
    price: '$450', priceNote: '/ workshop', pausedNote: 'Paused for Q3',
    stats: [
      { value: '6',   label: 'DELIVERED' },
      { value: '112', label: 'ATTENDEES' },
      { value: '4.7★', label: 'RATING' },
    ],
  },
]);

// (pauseService replaced by openPauseModal + confirmPause above)

// ── Settings tab ──
const settingsProfile = ref({
  headline: 'Board-Approved Clinical Supervisor | Trauma & DBT Specialist',
  years: '14',
  bio: 'I offer clinical supervision, peer consultation, and specialized training to support therapists in building confidence and competence. My approach is collaborative, strengths-based, and rooted in evidence-based practice.',
  specialties: ['Trauma', 'DBT', 'Complex PTSD', 'Personality Disorders'],
});
const newSpecialty = ref('');
function addSpecialty() {
  const val = newSpecialty.value.trim();
  if (val && !settingsProfile.value.specialties.includes(val)) {
    settingsProfile.value.specialties.push(val);
  }
  newSpecialty.value = '';
}
function saveProfile() { showToast('Services profile saved', 'success'); }
</script>

<style scoped>
/* Hero */
.ms-hero { display:flex; align-items:flex-start; justify-content:space-between; gap:20px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:22px 26px; margin-bottom:14px; box-shadow:var(--shadow-xs); flex-wrap:wrap; }
.ms-eyebrow { font-family:var(--font-sans); font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--gold-dark); margin-bottom:5px; }
.ms-title { font-family:var(--font-serif); font-size:26px; font-weight:700; color:var(--text); margin:0 0 6px; }
.ms-sub { font-family:var(--font-sans); font-size:12.5px; color:var(--text-3); margin:0 0 10px; line-height:1.5; max-width:580px; }
.ms-hero-meta { display:flex; flex-wrap:wrap; gap:14px; }
.ms-meta-item { display:inline-flex; align-items:center; gap:5px; font-family:var(--font-sans); font-size:12px; color:var(--text-4); }
.ms-hero-actions { display:flex; gap:8px; flex-shrink:0; align-items:center; }
.ms-btn-icon { display:inline-flex; align-items:center; gap:6px; }

/* Stat cards */
.ms-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:14px; }
.ms-stat { display:flex; align-items:center; gap:14px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:16px 18px; box-shadow:var(--shadow-xs); }
.ms-stat-icon { width:36px; height:36px; border-radius:8px; background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ms-stat-icon-gold  { background:rgba(160,129,62,.1);  color:var(--gold-dark); }
.ms-stat-icon-blue  { background:var(--blue-light);    color:var(--blue-dark); }
.ms-stat-icon-green { background:var(--green-light);   color:var(--green-dark); }
.ms-stat-value { font-family:var(--font-serif); font-size:22px; font-weight:700; color:var(--text); line-height:1; }
.ms-stat-label { font-family:var(--font-sans); font-size:11px; color:var(--text-4); margin-top:3px; }

/* Tabs */
.ms-tabs { display:flex; align-items:center; gap:2px; border-bottom:1px solid var(--border); margin-bottom:18px; }
.ms-tab { display:inline-flex; align-items:center; gap:7px; padding:9px 14px; font-family:var(--font-sans); font-size:13px; font-weight:500; color:var(--text-3); background:transparent; border:none; border-bottom:2px solid transparent; cursor:pointer; transition:color .15s,border-color .15s; margin-bottom:-1px; }
.ms-tab:hover { color:var(--text); }
.ms-tab.active { color:var(--text); font-weight:600; border-bottom-color:var(--gold-dark); }
.ms-tab-count { font-size:11px; font-weight:700; background:var(--surface-3); color:var(--text-2); border-radius:99px; padding:1px 7px; }
.ms-tab.active .ms-tab-count { background:var(--gold-dark); color:#fff; }

/* Service cards grid */
.ms-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }

/* Service card */
.ms-card { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:16px 15px; display:flex; flex-direction:column; transition:box-shadow .15s; }
.ms-card:hover { box-shadow:var(--shadow-sm); }
.ms-card-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.ms-card-type-icon { color:var(--text-4); display:flex; align-items:center; }
.ms-status-badge { font-size:10px; font-weight:700; letter-spacing:.05em; padding:2px 8px; border-radius:99px; }
.ms-status-active { background:rgba(76,175,125,.12); color:var(--green-dark); border:1px solid var(--soft-green); }
.ms-status-paused { background:rgba(232,169,74,.12); color:var(--orange-dark); border:1px solid var(--soft-orange,rgba(232,169,74,.4)); }
.ms-card-title    { font-family:var(--font-sans); font-size:13.5px; font-weight:700; color:var(--text); line-height:1.3; margin-bottom:2px; }
.ms-card-category { font-size:11.5px; color:var(--text-4); margin-bottom:10px; }
.ms-card-desc     { font-size:12px; color:var(--text-3); line-height:1.55; margin-bottom:12px; flex:1; }
.ms-card-details  { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px; }
.ms-detail-item   { display:inline-flex; align-items:center; gap:4px; font-size:11px; color:var(--text-4); background:var(--surface-2); border-radius:99px; padding:2px 8px; }
.ms-detail-green  { color:var(--green-dark); background:var(--green-light); }
.ms-detail-orange { color:var(--orange-dark); background:var(--orange-light,rgba(232,169,74,.12)); }
.ms-card-price-row { display:flex; align-items:baseline; gap:5px; margin-bottom:4px; }
.ms-card-price    { font-family:var(--font-serif); font-size:20px; font-weight:700; color:var(--text); }
.ms-card-price-note { font-size:12px; color:var(--text-4); }
.ms-card-stats { display:flex; gap:0; border-top:1px solid var(--border); border-bottom:1px solid var(--border); margin:10px 0; }
.ms-card-stat { flex:1; text-align:center; padding:8px 4px; }
.ms-card-stat:not(:last-child) { border-right:1px solid var(--border); }
.ms-card-stat-val { font-family:var(--font-sans); font-size:13.5px; font-weight:700; color:var(--text); }
.ms-card-stat-lbl { font-size:9px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--text-4); margin-top:2px; }
.ms-card-actions { display:flex; gap:5px; padding-top:8px; }
.ms-act-btn { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:6px; border:1px solid var(--border); background:var(--surface); color:var(--text-3); cursor:pointer; transition:all .15s; }
.ms-act-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.ms-act-danger:hover { border-color:var(--red); color:var(--red); background:var(--red-light); }

/* Add New Service dashed card */
.ms-add-card { background:var(--surface-2); border:2px dashed var(--border); border-radius:12px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; padding:40px 20px; cursor:pointer; transition:border-color .15s,background .15s; text-align:center; min-height:200px; }
.ms-add-card:hover { border-color:var(--gold-dark); background:rgba(160,129,62,.04); }
.ms-add-icon { width:44px; height:44px; border-radius:50%; background:var(--gold-dark); color:#fff; display:flex; align-items:center; justify-content:center; }
.ms-add-title { font-family:var(--font-sans); font-size:14px; font-weight:700; color:var(--text); }
.ms-add-sub { font-size:12px; color:var(--text-4); }

/* Empty states */
.ms-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; padding:60px 20px; color:var(--text-4); }
.ms-empty p { font-family:var(--font-sans); font-size:14px; margin:0; color:var(--text-3); }

@media(max-width:1200px) { .ms-grid { grid-template-columns:repeat(3,1fr); } .ms-stats { grid-template-columns:repeat(2,1fr); } }
@media(max-width:860px)  { .ms-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:540px)  { .ms-grid { grid-template-columns:1fr; } }

/* ── Bookings table ────────────────────────────────────────── */
.bk-bar { display:flex; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.bk-search-wrap { position:relative; flex:1; min-width:220px; }
.bk-search { width:100%; padding:8px 12px 8px 32px; font-family:var(--font-sans); font-size:13px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:var(--radius-sm,8px); outline:none; box-sizing:border-box; }
.bk-search:focus { border-color:var(--gold-dark); box-shadow:0 0 0 3px rgba(160,129,62,.14); }
.bk-search::placeholder { color:var(--text-4); }
.bk-select { font-family:var(--font-sans); font-size:12.5px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:var(--radius-sm,8px); padding:7px 28px 7px 10px; cursor:pointer; outline:none; appearance:none; -webkit-appearance:none; }
.bk-table-wrap { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); overflow:hidden; box-shadow:var(--shadow-xs); }
.bk-table-header { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid var(--border); background:var(--surface-2); }
.bk-table-title { display:inline-flex; align-items:center; gap:7px; font-family:var(--font-sans); font-size:14px; font-weight:600; color:var(--text); }
.bk-table-meta { display:flex; gap:8px; }
.bk-sessions-badge { font-size:11px; font-weight:700; padding:3px 9px; border-radius:99px; background:var(--surface-3); color:var(--text-2); }
.bk-earned-badge   { font-size:11px; font-weight:700; padding:3px 9px; border-radius:99px; background:rgba(76,175,125,.12); color:var(--green-dark); }
.bk-table { width:100%; border-collapse:collapse; }
.bk-table thead tr { background:var(--surface-2); border-bottom:1px solid var(--border); }
.bk-table th { padding:10px 16px; font-family:var(--font-sans); font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--text-4); text-align:left; white-space:nowrap; }
.bk-table tbody tr { border-bottom:1px solid var(--border); transition:background .12s; }
.bk-table tbody tr:last-child { border-bottom:none; }
.bk-table tbody tr:hover { background:var(--surface-2); }
.bk-table td { padding:12px 16px; font-family:var(--font-sans); font-size:13px; color:var(--text); vertical-align:middle; }
.bk-provider-row { display:flex; align-items:center; gap:10px; }
.bk-avatar { width:32px; height:32px; border-radius:8px; color:#fff; font-family:var(--font-sans); font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.bk-provider-name { font-weight:600; font-size:13px; color:var(--text); }
.bk-provider-role { font-size:11px; color:var(--text-4); margin-top:1px; }
.bk-td-service { color:var(--text-3); }
.bk-td-date { white-space:nowrap; color:var(--text-3); }
.bk-td-dur  { color:var(--text-3); }
.bk-td-amt  { font-weight:600; color:var(--text); }
.bk-status { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; padding:3px 9px; border-radius:99px; }
.bk-status-completed { background:rgba(76,175,125,.12); color:var(--green-dark); }
.bk-status-upcoming  { background:rgba(74,144,196,.12); color:var(--blue-dark); }
.bk-table-footer { display:flex; align-items:center; justify-content:space-between; padding:12px 18px; border-top:1px solid var(--border); background:var(--surface-2); }
.bk-footer-count { font-size:12px; color:var(--text-4); }
.bk-pagination { display:flex; align-items:center; gap:4px; }
.bk-page-btn { width:28px; height:28px; padding:0; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--border); border-radius:6px; background:var(--surface); font-family:var(--font-sans); font-size:12px; font-weight:600; color:var(--text-3); cursor:pointer; transition:all .15s; }
.bk-page-btn.active { background:var(--text); color:#fff; border-color:var(--text); }
.bk-page-btn:hover:not(.active) { border-color:var(--gold-dark); color:var(--gold-dark); }
.bk-page-ellipsis { font-size:12px; color:var(--text-4); padding:0 2px; }
.bk-page-next { color:var(--text-3); }

/* ── Modals ────────────────────────────────────────────────── */
.ms-modal-backdrop { position:fixed; inset:0; z-index:1000; background:rgba(30,28,26,.45); backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center; padding:20px; }
.ms-modal { background:var(--surface); border:1px solid var(--border); border-radius:14px; box-shadow:0 24px 64px rgba(30,28,26,.2); width:100%; max-width:520px; overflow:hidden; }
.ms-modal-sm { max-width:460px; }
.ms-modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 22px 14px; border-bottom:1px solid var(--border); }
.ms-modal-title { font-family:var(--font-serif); font-size:17px; font-weight:700; color:var(--text); }
.ms-modal-close { width:28px; height:28px; padding:0; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--border); border-radius:6px; background:var(--surface); color:var(--text-3); cursor:pointer; transition:all .15s; }
.ms-modal-close:hover { border-color:var(--text); color:var(--text); }
.ms-modal-body { padding:18px 22px; display:flex; flex-direction:column; gap:14px; }
.ms-modal-field { display:flex; flex-direction:column; gap:5px; }
.ms-modal-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.ms-modal-label { font-family:var(--font-sans); font-size:11.5px; font-weight:600; color:var(--text-2); text-transform:uppercase; letter-spacing:.04em; }
.ms-modal-input,.ms-modal-select,.ms-modal-textarea { font-family:var(--font-sans); font-size:13.5px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:8px; padding:9px 12px; outline:none; transition:border-color .15s,box-shadow .15s; width:100%; box-sizing:border-box; }
.ms-modal-input:focus,.ms-modal-select:focus,.ms-modal-textarea:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(196,169,106,.18); }
.ms-modal-textarea { resize:vertical; min-height:90px; line-height:1.6; }
.ms-modal-select { appearance:none; -webkit-appearance:none; cursor:pointer; }
.ms-modal-footer { display:flex; align-items:center; justify-content:flex-end; gap:8px; padding:14px 22px; border-top:1px solid var(--border); background:var(--surface-2); }
.ms-delete-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-family:var(--font-sans); font-size:12.5px; font-weight:600; background:var(--red-light); color:var(--red); border:1.5px solid var(--soft-red,rgba(160,45,34,.4)); border-radius:var(--radius-sm,8px); cursor:pointer; transition:all .15s; }
.ms-delete-btn:hover { background:var(--red); color:#fff; }
.ms-save-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; font-family:var(--font-sans); font-size:12.5px; font-weight:600; background:var(--gold-dark); color:#fff; border:1px solid var(--gold-dark); border-radius:var(--radius-sm,8px); cursor:pointer; transition:background .15s; }
.ms-save-btn:hover { background:var(--gold); border-color:var(--gold); }

/* Preview modal */
.ms-preview-notice { font-family:var(--font-sans); font-size:12.5px; color:var(--text-3); margin-bottom:2px; }
.ms-preview-provider-card { border:1px solid var(--border); border-radius:10px; overflow:hidden; }
.ms-preview-provider-top { display:flex; align-items:flex-start; gap:12px; padding:14px 16px; border-bottom:1px solid var(--border); background:var(--surface-2); }
.ms-preview-avatar { width:40px; height:40px; border-radius:10px; background:var(--gold-dark); color:#fff; font-family:var(--font-sans); font-size:13px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ms-preview-provider-info { flex:1; min-width:0; }
.ms-preview-provider-name { font-family:var(--font-sans); font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px; }
.ms-preview-provider-role { font-size:11.5px; color:var(--text-4); margin-bottom:6px; }
.ms-preview-tags { display:flex; flex-wrap:wrap; gap:4px; }
.ms-preview-available { font-size:10px; font-weight:700; color:var(--green-dark); background:var(--green-light); border:1px solid var(--soft-green); border-radius:99px; padding:2px 8px; flex-shrink:0; align-self:flex-start; }
.ms-preview-service-block { padding:14px 16px; }
.ms-preview-service-name { font-family:var(--font-sans); font-size:14px; font-weight:700; color:var(--text); margin-bottom:6px; }
.ms-preview-service-desc { font-size:12.5px; color:var(--text-3); line-height:1.55; margin-bottom:10px; }
.ms-preview-service-meta { display:flex; gap:14px; font-size:12px; color:var(--text-4); margin-bottom:12px; }
.ms-preview-service-footer { display:flex; align-items:center; justify-content:space-between; padding-top:10px; border-top:1px solid var(--border); }
.ms-preview-price { font-family:var(--font-serif); font-size:22px; font-weight:700; color:var(--text); }
.ms-request-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-family:var(--font-sans); font-size:12.5px; font-weight:600; background:var(--gold-dark); color:#fff; border:none; border-radius:var(--radius-sm,8px); cursor:pointer; transition:background .15s; }
.ms-request-btn:hover { background:var(--gold); }

/* Pause modal */
.ms-pause-notice { display:flex; align-items:flex-start; gap:9px; padding:10px 13px; background:rgba(232,169,74,.1); border:1px solid rgba(232,169,74,.4); border-radius:8px; font-family:var(--font-sans); font-size:12.5px; color:var(--orange-dark); line-height:1.5; }
.ms-pause-confirm-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-family:var(--font-sans); font-size:12.5px; font-weight:600; background:var(--red); color:#fff; border:1px solid var(--red); border-radius:var(--radius-sm,8px); cursor:pointer; transition:background .15s; }
.ms-pause-confirm-btn:hover { background:var(--red-dark); }

/* Modal transitions */
.ms-modal-fade-enter-active, .ms-modal-fade-leave-active { transition:opacity .2s ease; }
.ms-modal-fade-enter-active .ms-modal, .ms-modal-fade-leave-active .ms-modal { transition:transform .2s ease; }
.ms-modal-fade-enter-from, .ms-modal-fade-leave-to { opacity:0; }
.ms-modal-fade-enter-from .ms-modal { transform:translateY(-10px) scale(0.98); }

/* Clickable cards */
.ms-card { cursor:pointer; }

/* ── Service Requests tab ───────────────────────────────────── */
.sr-notice { display:flex; align-items:center; gap:9px; padding:10px 14px; background:var(--blue-light); border:1px solid var(--soft-blue); border-radius:8px; margin-bottom:18px; font-family:var(--font-sans); font-size:13px; color:var(--blue-dark); }
.sr-notice-link { color:var(--gold-dark); text-decoration:underline; cursor:pointer; }
.sr-section-header { display:flex; align-items:center; gap:8px; margin-bottom:10px; }
.sr-section-title { font-family:var(--font-sans); font-size:14px; font-weight:700; color:var(--text); }
.sr-count-pill { font-size:11px; font-weight:700; background:var(--gold-dark); color:#fff; border-radius:99px; padding:1px 8px; }
.sr-pending-list { display:flex; flex-direction:column; gap:0; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); overflow:hidden; box-shadow:var(--shadow-xs); margin-bottom:4px; }
.sr-pending-row { display:flex; align-items:center; gap:14px; padding:14px 18px; border-bottom:1px solid var(--border); flex-wrap:wrap; transition:background .12s; }
.sr-pending-row:last-child { border-bottom:none; }
.sr-pending-row:hover { background:var(--surface-2); }
.sr-pending-left { display:flex; align-items:center; gap:10px; min-width:200px; flex:1; }
.sr-req-avatar { width:36px; height:36px; border-radius:50%; color:#fff; font-family:var(--font-sans); font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.sr-req-name { font-family:var(--font-sans); font-size:13px; font-weight:700; color:var(--text); margin-bottom:1px; }
.sr-req-role { font-size:11px; color:var(--text-4); }
.sr-pending-middle { min-width:180px; flex:1; }
.sr-req-service { font-family:var(--font-sans); font-size:13px; font-weight:600; color:var(--text); margin-bottom:2px; }
.sr-req-frequency { font-size:10.5px; font-weight:700; letter-spacing:.05em; color:var(--text-4); text-transform:uppercase; }
.sr-pending-date { min-width:140px; flex-shrink:0; }
.sr-req-date-label { font-size:10px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--text-4); margin-bottom:2px; }
.sr-req-date-val { font-size:12.5px; font-weight:500; color:var(--text-2); }
.sr-req-time-ago { font-size:11.5px; color:var(--text-4); min-width:70px; text-align:right; flex-shrink:0; }
.sr-req-actions { display:flex; align-items:center; gap:6px; flex-shrink:0; }
.sr-accept-btn { display:inline-flex; align-items:center; gap:5px; padding:6px 14px; font-family:var(--font-sans); font-size:12.5px; font-weight:700; background:var(--gold-dark); color:#fff; border:1px solid var(--gold-dark); border-radius:var(--radius-sm,8px); cursor:pointer; transition:background .15s; white-space:nowrap; }
.sr-accept-btn:hover { background:var(--gold); border-color:var(--gold); }

/* ── Session Notes modal ───────────────────────────────────── */
.sn-session-chip { background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px 13px; font-family:var(--font-sans); display:flex; flex-direction:column; gap:3px; }
.sn-session-chip strong { font-size:13px; font-weight:700; color:var(--text); }
.sn-session-chip span { font-size:12px; color:var(--text-4); }
.sn-share-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:4px 0; }
.sn-share-title { font-family:var(--font-sans); font-size:13px; font-weight:600; color:var(--text); margin-bottom:2px; }
.sn-share-sub   { font-size:11.5px; color:var(--text-4); }

/* ── Activity table status ─────────────────────────────────── */
.bk-status-accepted { background:rgba(76,175,125,.12); color:var(--green-dark); }
.bk-status-declined { background:var(--red-light); color:var(--red); }

/* ── Accept modal notice ───────────────────────────────────── */
.accept-notice { display:flex; align-items:flex-start; gap:9px; padding:10px 13px; background:var(--green-light); border:1px solid var(--soft-green); border-radius:8px; font-family:var(--font-sans); font-size:12.5px; color:var(--green-dark); line-height:1.5; }

/* ── Settings tab ──────────────────────────────────────────── */
.st-completion-banner { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 20px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); margin-bottom:14px; box-shadow:var(--shadow-xs); flex-wrap:wrap; }
.st-completion-left { display:flex; align-items:center; gap:12px; min-width:0; }
.st-completion-icon { width:34px; height:34px; border-radius:8px; background:var(--surface-3); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; color:var(--gold-dark); flex-shrink:0; }
.st-completion-title { font-family:var(--font-sans); font-size:13.5px; font-weight:600; color:var(--text); }
.st-completion-sub   { font-family:var(--font-sans); font-size:12px; color:var(--text-4); margin-top:2px; }
.st-completion-right { display:flex; align-items:center; gap:14px; flex-shrink:0; }
.st-bar-wrap { display:flex; align-items:center; gap:9px; }
.st-bar-track { width:130px; height:5px; border-radius:99px; background:var(--border); overflow:hidden; }
.st-bar-fill  { height:100%; border-radius:99px; background:var(--gold-dark); }
.st-bar-pct   { font-family:var(--font-sans); font-size:12px; font-weight:600; color:var(--text-2); }

.st-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:18px 22px; box-shadow:var(--shadow-xs); }
.st-card-header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
.st-card-header-left { display:flex; align-items:center; gap:10px; }
.st-card-icon { width:32px; height:32px; border-radius:8px; background:var(--surface-3); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; color:var(--gold-dark); flex-shrink:0; }
.st-card-title { font-family:var(--font-sans); font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px; }
.st-card-sub   { font-family:var(--font-sans); font-size:12px; color:var(--text-4); }

.st-setting-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 0; border-bottom:1px solid var(--border); flex-wrap:wrap; }
.st-setting-left { display:flex; align-items:flex-start; gap:10px; flex:1; min-width:0; }
.st-setting-icon { color:var(--text-4); display:flex; align-items:center; margin-top:1px; flex-shrink:0; }
.st-setting-name { font-family:var(--font-sans); font-size:13px; font-weight:600; color:var(--text); margin-bottom:2px; }
.st-setting-desc { font-size:12px; color:var(--text-4); }
.st-edit-btn { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; font-family:var(--font-sans); font-size:12px; font-weight:600; color:var(--text-2); background:var(--surface); border:1.5px solid var(--border); border-radius:6px; cursor:pointer; transition:all .15s; white-space:nowrap; }
.st-edit-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }

.st-profile-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.st-profile-grid .ms-modal-field:first-child { grid-column:span 1; }

.st-tags-row { display:flex; flex-wrap:wrap; align-items:center; gap:6px; }
.st-tag { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; font-family:var(--font-sans); font-size:12px; font-weight:600; background:rgba(160,129,62,.1); color:var(--gold-dark); border:1px solid var(--fade-gold); border-radius:99px; }
.st-tag-remove { background:none; border:none; cursor:pointer; color:var(--gold-dark); font-size:14px; line-height:1; padding:0; display:flex; align-items:center; }
.st-tag-remove:hover { color:var(--red); }
.st-tag-input { flex:1; min-width:120px; padding:5px 10px; font-family:var(--font-sans); font-size:12.5px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:6px; outline:none; }
.st-tag-input:focus { border-color:var(--gold-dark); }
.st-add-btn { display:inline-flex; align-items:center; gap:4px; padding:5px 12px; font-family:var(--font-sans); font-size:12px; font-weight:600; color:var(--text-2); background:var(--surface); border:1.5px solid var(--border); border-radius:6px; cursor:pointer; transition:all .15s; white-space:nowrap; }
.st-add-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.st-profile-footer { display:flex; justify-content:flex-end; gap:8px; padding-top:16px; margin-top:6px; border-top:1px solid var(--border); }</style>
