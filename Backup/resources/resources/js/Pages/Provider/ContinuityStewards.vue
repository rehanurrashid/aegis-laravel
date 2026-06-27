<template>
  <AppLayout :user="user" portal="practitioner" activePage="continuity-stewards" pageTitle="Continuity Stewards">

    <!-- HERO BANNER -->
    <div class="cs-hero">
      <div class="cs-hero-left">
        <div class="cs-eyebrow">CONTINUITY PLANNING</div>
        <h1 class="cs-title">Continuity Stewards</h1>
        <p class="cs-sub">Identify and support the trusted individuals who will carry out your Continuity Plan. Keeping agreements current helps maintain alignment and readiness over time.</p>
      </div>
      <div class="cs-hero-actions">
        <button class="btn btn-outline btn-sm cs-btn-icon" @click="showToast('Opening activity log','info')">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          Activity
        </button>
        <button class="cs-activate-btn" @click="activateModalOpen = true">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Activate Continuity Support
        </button>
        <button class="btn btn-primary btn-sm cs-btn-icon" @click="showPlanLimitToast">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Continuity Steward
        </button>
      </div>
    </div>

    <!-- ANNUAL REVIEW BANNER -->
    <div class="cs-review-banner">
      <div class="cs-review-left">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div>
          <div class="cs-review-title">Annual Review Due — Jun 15, 2025</div>
          <div class="cs-review-sub">Your Continuity Plan requires an annual attestation. Please confirm all Continuity Steward details and procedures are current.</div>
        </div>
      </div>
      <div class="cs-review-actions">
        <button class="cs-review-btn" @click="reviewModalOpen = true">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          Complete Review Now
        </button>
        <button class="btn btn-outline btn-sm cs-btn-icon" @click="remindModalOpen = true">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          Remind Me Later
        </button>
      </div>
    </div>

    <!-- PLAN LIMIT NOTICE -->
    <div class="cs-plan-notice">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span>Your <strong>Continuity Practice</strong> plan includes up to <strong>2 Continuity Stewards</strong> · 5 of 2 in use (limit reached).</span>
    </div>

    <!-- STAT CARDS -->
    <div class="cs-stats">
      <div class="cs-stat">
        <div class="cs-stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div><div class="cs-stat-val">2</div><div class="cs-stat-lbl">Active Continuity Stewards</div></div>
      </div>
      <div class="cs-stat">
        <div class="cs-stat-icon cs-stat-gold"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.93 12"/><path d="M10.59 6.59a9.5 9.5 0 0 1 9.09 2.09"/></svg></div>
        <div><div class="cs-stat-val">1</div><div class="cs-stat-lbl">Pending Invitation</div></div>
      </div>
      <div class="cs-stat">
        <div class="cs-stat-icon cs-stat-blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg></div>
        <div><div class="cs-stat-val">1</div><div class="cs-stat-lbl">I'm Continuity Steward For</div></div>
      </div>
      <div class="cs-stat">
        <div class="cs-stat-icon cs-stat-green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div><div class="cs-stat-val">On Track</div><div class="cs-stat-lbl">Annual Review</div></div>
      </div>
    </div>

    <!-- TABS -->
    <div class="cs-tabs">
      <button class="cs-tab" :class="{ active: activeTab === 'mine' }" @click="activeTab = 'mine'">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        My Continuity Stewards <span class="cs-tab-count">3</span>
      </button>
      <button class="cs-tab" :class="{ active: activeTab === 'pending' }" @click="activeTab = 'pending'">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Pending <span class="cs-tab-count">2</span>
      </button>
      <button class="cs-tab" :class="{ active: activeTab === 'for' }" @click="activeTab = 'for'">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
        I'm Continuity Steward For <span class="cs-tab-count">1</span>
      </button>
    </div>

    <!-- MY CONTINUITY STEWARDS -->
    <div v-show="activeTab === 'mine'">
      <p class="cs-tab-desc">Trusted individuals who will manage your practice in an emergency or upon your incapacity.</p>

      <div v-for="s in stewards" :key="s.name" class="cs-card">
        <div class="cs-card-header">
          <div class="cs-card-avatar">{{ s.initials }}</div>
          <div class="cs-card-identity">
            <div class="cs-card-name-row">
              <span class="cs-card-name">{{ s.name }}</span>
              <span class="cs-role-badge">{{ s.roleLabel }}</span>
              <span class="cs-active-badge">● ACTIVE</span>
              <span class="cs-signed-badge">✓ COUNTERSIGNED</span>
            </div>
            <div class="cs-card-org">{{ s.org }}</div>
            <div class="cs-card-meta">
              <span class="cs-meta-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07"/></svg> {{ s.phone }}</span>
              <span class="cs-meta-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> {{ s.email }}</span>
              <span class="cs-meta-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg> Signed {{ s.signed }}</span>
              <span class="cs-meta-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Review Due: {{ s.reviewDue }}</span>
            </div>
          </div>
        </div>

        <div class="cs-responsibilities">
          <div class="cs-resp-label">AUTHORIZED RESPONSIBILITIES{{ s.backup ? ' (BACKUP)' : '' }}</div>
          <div v-for="r in s.responsibilities" :key="r.text" class="cs-resp-row">
            <div class="cs-resp-icon" :style="{ color: r.color }">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" v-html="r.icon"></svg>
            </div>
            <span class="cs-resp-text">{{ r.text }}</span>
          </div>
        </div>

        <div class="cs-card-actions">
          <button class="cs-act-btn" title="View Agreement" @click="openModal('agreement', s)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></button>
          <button class="cs-act-btn" title="Edit" @click="openModal('edit', s)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
          <button class="cs-act-btn cs-act-btn-msg" title="Message" @click="goToMessage(s)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></button>
          <button class="cs-act-btn" title="Annual Review" @click="openModal('annual', s)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></button>
          <button class="cs-act-btn" title="Change Role" @click="openModal('role', s)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></button>
          <button class="cs-act-btn" title="Vault Access" @click="openModal('vault', s)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></button>
          <button class="cs-act-btn" title="Manage Authorizations" @click="openModal('auth', s)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></button>
          <button class="cs-act-btn cs-act-danger" title="Remove" @click="openModal('remove', s)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>
        </div>
      </div>
    </div>

    <!-- PENDING TAB -->
    <div v-show="activeTab === 'pending'">

      <!-- Incoming Connection Requests -->
      <div class="pnd-section-row">
        <div>
          <div class="pnd-section-title">Incoming Connection Requests</div>
          <div class="pnd-section-sub">Continuity Stewards who have proactively invited you to connect. Review their profile and accept or decline.</div>
        </div>
        <span class="pnd-new-badge">1 NEW</span>
      </div>
      <div v-for="req in incomingRequests" :key="req.id" class="pnd-card">
        <div class="pnd-card-top">
          <div class="pnd-avatar" :style="{ background: req.avatarColor }">{{ req.initials }}</div>
          <div class="pnd-info">
            <div class="pnd-name-row">
              <span class="pnd-name">{{ req.name }}</span>
              <span class="pnd-cs-badge">⊙ CS-INITIATED REQUEST</span>
              <span class="pnd-aegis-badge">● AEGIS CERTIFIED CS</span>
            </div>
            <div class="pnd-org">{{ req.org }}</div>
            <div class="pnd-meta-row">
              <span class="pnd-meta-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Request sent {{ req.date }}</span>
              <span class="pnd-meta-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg> Currently serving {{ req.serving }} providers</span>
            </div>
            <div class="pnd-plan-notice">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              If you accept, you will still need to select a paid plan and complete the agreement wizard.
            </div>
          </div>
        </div>
        <div class="pnd-actions">
          <button class="cs-attest-btn" @click="openPndModal('accept', req)">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
            Accept &amp; Start Agreement
          </button>
          <button class="cs-act-btn" title="Preview agreement" @click="openPndModal('preview-incoming', req)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
          <button class="cs-act-btn cs-act-danger" title="Decline" @click="openPndModal('decline', req)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
      </div>

      <!-- Outgoing Invitations -->
      <div class="pnd-section-row" style="margin-top:24px;">
        <div>
          <div class="pnd-section-title">Outgoing Invitations</div>
          <div class="pnd-section-sub">Invitations you've sent that are awaiting a response.</div>
        </div>
      </div>
      <div v-for="inv in outgoingInvitations" :key="inv.id" class="pnd-card">
        <div class="pnd-card-top">
          <div class="pnd-avatar" :style="{ background: inv.avatarColor }">{{ inv.initials }}</div>
          <div class="pnd-info" style="flex:1;">
            <div class="pnd-name-row">
              <span class="pnd-name">{{ inv.name }}</span>
              <span v-if="inv.status==='pending'" class="pnd-pending-badge">⏱ PENDING RESPONSE</span>
              <span v-if="inv.role" class="pnd-role-badge-sm">{{ inv.role }}</span>
              <span v-if="inv.status==='declined'" class="pnd-declined-badge">✕ DECLINED</span>
            </div>
            <div class="pnd-org">{{ inv.org }}</div>
            <div class="pnd-meta-row">
              <span v-if="inv.email" class="pnd-meta-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> {{ inv.email }}</span>
              <span v-if="inv.invited" class="pnd-meta-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Invited: {{ inv.invited }}</span>
              <span v-if="inv.expires" class="pnd-meta-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Expires: {{ inv.expires }}</span>
              <span v-if="inv.declineNote" class="pnd-meta-item" style="color:var(--text-3);">{{ inv.declineNote }}</span>
            </div>
          </div>
          <div v-if="inv.status==='pending'" class="pnd-sent-ago">
            <button class="cs-act-btn" title="Resend invitation" @click="openPndModal('resend', inv)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
            <div class="pnd-sent-label">Sent {{ inv.sentAgo }}</div>
          </div>
        </div>
        <div v-if="inv.status==='pending'" class="pnd-actions">
          <button class="cs-act-btn" title="Resend" @click="openPndModal('resend', inv)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          </button>
          <button class="cs-act-btn" title="Preview agreement" @click="openPndModal('preview', inv)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
          <button class="cs-act-btn cs-act-danger" title="Cancel" @click="showToast('Invitation cancelled','info')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
      </div>
    </div>

    <!-- STEWARD FOR TAB -->
    <div v-show="activeTab === 'for'">

      <!-- Info banner -->
      <div class="sfor-banner">
        <div class="sfor-banner-left">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          <div>
            <div class="sfor-banner-title">You are a Continuity Steward for <strong>1 provider.</strong></div>
            <div class="sfor-banner-sub">Run annual reviews, open the provider's vault, and complete your CS tasks directly from this tab. Open the full CS Portal for the unified caseload dashboard.</div>
          </div>
        </div>
        <button class="btn btn-outline btn-sm sfor-portal-btn" @click="showToast('Opening CS Portal','info')">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Open CS Portal
        </button>
      </div>

      <!-- Stats -->
      <div class="cs-stats" style="margin-bottom:14px;">
        <div class="cs-stat">
          <div class="cs-stat-icon cs-stat-gold"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
          <div><div class="cs-stat-val">1</div><div class="cs-stat-lbl">Active Agreements</div></div>
        </div>
        <div class="cs-stat">
          <div class="cs-stat-icon cs-stat-blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
          <div><div class="cs-stat-val">Nov 9</div><div class="cs-stat-lbl">Next Review Due</div></div>
        </div>
        <div class="cs-stat">
          <div class="cs-stat-icon" style="background:rgba(160,45,34,.1);color:var(--red);"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
          <div><div class="cs-stat-val">1</div><div class="cs-stat-lbl">Active Incidents</div></div>
        </div>
        <div class="cs-stat">
          <div class="cs-stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
          <div><div class="cs-stat-val">1</div><div class="cs-stat-lbl">Vaults Accessible</div></div>
        </div>
      </div>

      <!-- Providers list -->
      <div class="sfor-section-title">Providers I'm Stewarding</div>
      <div v-for="p in stewardingFor" :key="p.name" class="sfor-provider-card" @click="showToast('Opening provider portal','info')">
        <div class="sfor-provider-left">
          <div class="pnd-avatar" :style="{ background: p.avatarColor }">{{ p.initials }}</div>
          <div>
            <div class="sfor-provider-name-row">
              <span class="sfor-provider-name">{{ p.name }}</span>
              <span class="sfor-role-badge">⊙ {{ p.role }}</span>
              <span class="sfor-active-badge">● ACTIVE</span>
            </div>
            <div class="sfor-provider-meta">{{ p.org }} · {{ p.location }} · Review due {{ p.reviewDue }}</div>
          </div>
        </div>
        <button class="sfor-arrow-btn" @click.stop="showToast('Opening provider portal','info')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </button>
      </div>
    </div>

    <!-- NOTIFY ME SECTION -->
    <div class="cs-notify-section" v-show="activeTab === 'mine'">
      <div class="cs-notify-header">Notify Me</div>
      <div class="cs-notify-sub">Choose when Aegis should let you know about activity involving your Continuity Stewards.</div>
      <div class="cs-notify-list">
        <div v-for="n in notifyToggles" :key="n.label" class="cs-notify-row">
          <span class="cs-notify-label">{{ n.label }}</span>
          <label class="cs-toggle">
            <input type="checkbox" v-model="n.on">
            <span class="cs-toggle-track" :class="{ on: n.on }"><span class="cs-toggle-thumb"></span></span>
          </label>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <Teleport to="body">

      <!-- ACTIVATE CONTINUITY SUPPORT MODAL -->
      <Transition name="cs-modal-fade">
        <div v-if="activateModalOpen" class="cs-modal-backdrop" @click.self="activateModalOpen=false">
          <div class="cs-modal">
            <div class="cs-modal-header cs-modal-header-red">
              <div class="cs-modal-title" style="color:#fff;">Activate Continuity Support</div>
              <button class="cs-modal-close" style="border-color:rgba(255,255,255,.3);background:transparent;color:#fff;" @click="activateModalOpen=false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="cs-modal-body">
              <div class="cs-activate-warning">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0;margin-top:1px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span><strong>This action initiates the full continuity support process.</strong> All designated Continuity Stewards will be notified, the Aegis team will be alerted, vault access will unlock per your Continuity Plan, and the continuity support process will begin. This cannot easily be undone.</span>
              </div>
              <div class="cs-edit-field">
                <label class="cs-edit-label">Incident Type <span style="color:var(--red)">*</span></label>
                <select class="cs-edit-input" v-model="activateForm.incidentType">
                  <option value="">— Select Incident Type —</option>
                  <option>Death</option>
                  <option>Short-Term Incapacitation</option>
                  <option>Long-Term Incapacitation</option>
                  <option>Missing Person</option>
                  <option>Natural Disaster</option>
                  <option>Voluntary Retirement</option>
                  <option>License Suspension</option>
                  <option>Other</option>
                </select>
              </div>
              <div class="cs-edit-field">
                <label class="cs-edit-label">Who is activating this? <span style="color:var(--red)">*</span></label>
                <input class="cs-edit-input" v-model="activateForm.activatedBy" />
              </div>
              <div class="cs-edit-field">
                <label class="cs-edit-label">Additional Context</label>
                <textarea class="cs-review-textarea" v-model="activateForm.context" rows="4" placeholder="Describe the situation to help your Continuity Stewards understand what happened and what to do first…"></textarea>
              </div>
              <div class="cs-edit-field">
                <label class="cs-edit-label">Confirm by typing "ACTIVATE CONTINUITY SUPPORT" <span style="color:var(--red)">*</span></label>
                <input class="cs-edit-input cs-activate-confirm-input" v-model="activateForm.confirm" placeholder="Type exact phrase to confirm" />
              </div>
            </div>
            <div class="cs-modal-footer">
              <button class="btn btn-outline btn-sm" @click="activateModalOpen=false">Cancel — Not a Critical Incident</button>
              <button class="cs-activate-confirm-btn" :disabled="activateForm.confirm !== 'ACTIVATE CONTINUITY SUPPORT' || !activateForm.incidentType" @click="confirmActivate">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                Activate Continuity Support
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- VIEW AGREEMENT MODAL -->
      <Transition name="cs-modal-fade">
        <div v-if="modal.type==='agreement'" class="cs-modal-backdrop" @click.self="modal.type=null">
          <div class="cs-modal cs-modal-lg">
            <div class="cs-modal-header">
              <div class="cs-modal-title">Continuity Plan — {{ modal.steward?.name }}</div>
              <button class="cs-modal-close" @click="modal.type=null"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="cs-modal-body">
              <div class="cs-agr-status"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg> Agreement #CS-2024-001 · Active · Both parties signed · Last reviewed June 15, 2024</div>
              <div class="cs-agr-doc">
                <div class="cs-agr-heading">Aegis CONTINUITY PLAN</div>
                <div class="cs-agr-meta"><strong>Provider:</strong> Dr. Sarah Johnson, MD — Psychiatrist, New York, NY</div>
                <div class="cs-agr-meta"><strong>Primary Continuity Steward:</strong> {{ modal.steward?.name }} — {{ modal.steward?.org }}</div>
                <div class="cs-agr-meta"><strong>Agreement Date:</strong> June 15, 2024 | <strong>Next Review:</strong> June 15, 2025</div>
                <div class="cs-agr-divider"></div>
                <p class="cs-agr-para"><strong>Section 1. Purpose.</strong> This agreement establishes {{ modal.steward?.name }} as the designated Primary Continuity Steward for the professional practice of Dr. Sarah Johnson in the event of death, incapacitation, disability, voluntary retirement, license suspension, or any other event preventing the Provider from practicing.</p>
                <p class="cs-agr-para"><strong>Section 2. Triggering Events.</strong> This agreement activates upon confirmed incapacitation as verified by Aegis emergency protocols or a designated medical authority.</p>
                <p class="cs-agr-para"><strong>Section 3. Authorized Responsibilities.</strong> Client notification; client record transfer (HIPAA-compliant); insurance billing transition; licensing board notification; Document Vault access; professional network notification.</p>
                <p class="cs-agr-para"><strong>Section 4. Compensation.</strong> No compensation — mutual professional agreement.</p>
                <p class="cs-agr-para"><strong>Section 5. Annual Review.</strong> Both parties agree to review and re-attest this agreement annually.</p>
                <div class="cs-agr-signatures">
                  <div class="cs-agr-sig"><span class="cs-agr-sig-badge">SIGNED</span> Dr. Sarah Johnson — Signed digitally, June 15, 2024</div>
                  <div class="cs-agr-sig"><span class="cs-agr-sig-badge">SIGNED</span> {{ modal.steward?.name }} — Signed digitally, June 15, 2024</div>
                </div>
              </div>
            </div>
            <div class="cs-modal-footer">
              <button class="btn btn-outline btn-sm" @click="modal.type=null">Close</button>
              <button class="btn btn-outline btn-sm cs-btn-icon" @click="showToast('Downloading PDF','info')"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Download PDF</button>
              <button class="cs-attest-btn" @click="reviewModalOpen=true; modal.type=null"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg> Start Annual Review</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- EDIT MODAL -->
      <Transition name="cs-modal-fade">
        <div v-if="modal.type==='edit'" class="cs-modal-backdrop" @click.self="modal.type=null">
          <div class="cs-modal">
            <div class="cs-modal-header">
              <div class="cs-modal-title">Edit Continuity Steward — {{ modal.steward?.name }}</div>
              <button class="cs-modal-close" @click="modal.type=null"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="cs-modal-body">
              <div class="cs-edit-notice"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>Changes to name, email, or role will generate an amendment notice sent to the Continuity Steward for acknowledgment.</span></div>
              <div class="cs-edit-field"><label class="cs-edit-label">Full Name</label><input class="cs-edit-input" v-model="editForm.name"></div>
              <div class="cs-edit-field"><label class="cs-edit-label">Job Title / Role</label><input class="cs-edit-input" v-model="editForm.title"></div>
              <div class="cs-edit-field"><label class="cs-edit-label">Phone</label><input class="cs-edit-input" v-model="editForm.phone"></div>
              <div class="cs-edit-field"><label class="cs-edit-label">Email</label><input class="cs-edit-input" v-model="editForm.email"></div>
              <div class="cs-edit-field"><label class="cs-edit-label">Organization / Practice</label><input class="cs-edit-input" v-model="editForm.org"></div>
              <div class="cs-edit-field"><label class="cs-edit-label">Emergency Phone (24-hr contact)</label><input class="cs-edit-input" v-model="editForm.emergencyPhone" placeholder="Alternate contact for emergencies"></div>
              <div class="cs-edit-field"><label class="cs-edit-label">Notes</label><textarea class="cs-review-textarea" v-model="editForm.notes" placeholder="Any updates or context…" rows="3"></textarea></div>
            </div>
            <div class="cs-modal-footer">
              <button class="btn btn-outline btn-sm" @click="modal.type=null">Cancel</button>
              <button class="cs-attest-btn" @click="showToast('Changes saved','success'); modal.type=null"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg> Save Changes</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- ANNUAL REVIEW (card button) MODAL — reuses reviewModalOpen -->

      <!-- CHANGE ROLE MODAL -->
      <Transition name="cs-modal-fade">
        <div v-if="modal.type==='role'" class="cs-modal-backdrop" @click.self="modal.type=null">
          <div class="cs-modal">
            <div class="cs-modal-header">
              <div class="cs-modal-title">Change Continuity Steward Role</div>
              <button class="cs-modal-close" @click="modal.type=null"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="cs-modal-body">
              <div v-if="modal.steward" class="cs-role-card">
                <div class="cs-card-avatar" style="width:36px;height:36px;font-size:12px;">{{ modal.steward.initials }}</div>
                <div><div class="cs-card-name" style="font-size:13.5px;">{{ modal.steward.name }}</div><div style="font-size:12px;color:var(--text-4);">Currently: {{ modal.steward.roleLabel.replace('_',' ') }}</div></div>
              </div>
              <div class="cs-edit-notice cs-edit-notice-gold"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0;margin-top:1px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><span>Changing the role will update the agreement and send an amendment to both parties for acknowledgment.</span></div>
              <div class="cs-edit-field"><label class="cs-edit-label">New Role</label>
                <select class="cs-edit-input" v-model="roleForm.newRole"><option>Primary Continuity Steward</option><option>Alternate Continuity Steward</option><option>Backup Continuity Steward</option></select>
              </div>
              <div class="cs-edit-field"><label class="cs-edit-label">Reason for Change (Optional)</label><input class="cs-edit-input" v-model="roleForm.reason" placeholder="e.g., Swapping roles with Priya Raman for scheduling reasons"></div>
            </div>
            <div class="cs-modal-footer">
              <button class="btn btn-outline btn-sm" @click="modal.type=null">Cancel</button>
              <button class="cs-attest-btn" @click="showToast('Role change confirmed','success'); modal.type=null">Confirm Role Change</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- VAULT ACCESS MODAL -->
      <Transition name="cs-modal-fade">
        <div v-if="modal.type==='vault'" class="cs-modal-backdrop" @click.self="modal.type=null">
          <div class="cs-modal cs-modal-lg">
            <div class="cs-modal-header">
              <div class="cs-modal-title">Manage Document Vault Access — {{ modal.steward?.name }}</div>
              <button class="cs-modal-close" @click="modal.type=null"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="cs-modal-body">
              <div class="cs-edit-notice"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>Control what documents this Continuity Steward can access and when. Vault access is unlocked automatically when continuity support is activated.</span></div>
              <div class="cs-edit-label" style="margin-bottom:8px;">Access Level</div>
              <div v-for="opt in vaultOptions" :key="opt.value" class="cs-vault-option" :class="{ selected: vaultForm.level === opt.value }" @click="vaultForm.level = opt.value">
                <div class="cs-vault-radio"><span class="cs-vault-dot" v-if="vaultForm.level === opt.value"></span></div>
                <div><div class="cs-vault-opt-label">{{ opt.label }}</div><div class="cs-vault-opt-sub">{{ opt.sub }}</div></div>
              </div>
              <div class="cs-edit-label" style="margin:14px 0 8px;">Documents in Vault</div>
              <div v-for="doc in vaultDocs" :key="doc.name" class="cs-vault-doc-row">
                <div class="cs-vault-doc-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                <div class="cs-vault-doc-info"><div class="cs-vault-doc-name">{{ doc.name }}</div><div class="cs-vault-doc-meta">{{ doc.meta }}</div></div>
                <span class="cs-vault-doc-status" :class="doc.status === 'ACCESSIBLE' ? 'cs-vault-accessible' : 'cs-vault-missing'">{{ doc.status }}</span>
              </div>
              <div style="display:flex;justify-content:flex-end;margin-top:8px;"><button class="btn btn-outline btn-sm cs-btn-icon" @click="showToast('Opening Document Vault','info')"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Open Document Vault</button></div>
            </div>
            <div class="cs-modal-footer">
              <button class="btn btn-outline btn-sm" @click="modal.type=null">Cancel</button>
              <button class="cs-attest-btn" @click="showToast('Access settings saved','success'); modal.type=null"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg> Save Access Settings</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- MANAGE AUTHORIZATIONS MODAL -->
      <Transition name="cs-modal-fade">
        <div v-if="modal.type==='auth'" class="cs-modal-backdrop" @click.self="modal.type=null">
          <div class="cs-modal cs-modal-lg">
            <div class="cs-modal-header">
              <div><div class="cs-modal-title">Manage Authorizations</div><div class="cs-modal-sub">Choose which incident types {{ modal.steward?.name }} may verify and close.</div></div>
              <button class="cs-modal-close" @click="modal.type=null"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="cs-modal-body">
              <div class="cs-edit-notice"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>Authorization controls which critical incidents this steward is permitted to verify on your behalf. Disabled incident types are hidden from the steward's portal entirely.</span></div>
              <div v-for="inc in authIncidents" :key="inc.label" class="cs-auth-inc-row" :class="{ 'cs-auth-checked': inc.on }">
                <input type="checkbox" v-model="inc.on" style="cursor:pointer;accent-color:var(--gold-dark);width:15px;height:15px;flex-shrink:0;">
                <div><div class="cs-auth-inc-name">{{ inc.label }}</div><div class="cs-auth-inc-sub">{{ inc.on ? 'Enabled in your plan' : 'Disabled in your plan' }}</div></div>
              </div>
            </div>
            <div class="cs-modal-footer">
              <button class="btn btn-outline btn-sm" @click="modal.type=null">Cancel</button>
              <button class="cs-attest-btn" @click="showToast('Authorizations saved','success'); modal.type=null"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg> Save Authorizations</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- REMOVE MODAL -->
      <Transition name="cs-modal-fade">
        <div v-if="modal.type==='remove'" class="cs-modal-backdrop" @click.self="modal.type=null">
          <div class="cs-modal">
            <div class="cs-modal-header">
              <div class="cs-modal-title">Remove Continuity Steward</div>
              <button class="cs-modal-close" @click="modal.type=null"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="cs-modal-body">
              <div class="cs-remove-warning"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0;margin-top:1px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><span><strong>Warning:</strong> Removing a Continuity Steward voids the existing agreement. This action is permanent. The Continuity Steward will be notified by email.</span></div>
              <div v-if="modal.steward" class="cs-remove-card">
                <div class="cs-card-avatar" style="width:36px;height:36px;font-size:12px;">{{ modal.steward.initials }}</div>
                <div><div class="cs-card-name" style="font-size:13.5px;">{{ modal.steward.name }}</div><div style="font-size:12px;color:var(--text-4);">{{ modal.steward.roleLabel }} · Agreement signed June 15, 2024</div></div>
              </div>
              <div class="cs-edit-field"><label class="cs-edit-label">Reason for Removal <span style="color:var(--red)">*</span></label>
                <select class="cs-edit-input" v-model="removeForm.reason"><option value="">— Select Reason —</option><option>No longer available</option><option>Role conflict</option><option>Mutual agreement</option><option>Replaced by another steward</option><option>Other</option></select>
              </div>
              <div class="cs-edit-field"><label class="cs-edit-label">Additional Notes</label><textarea class="cs-review-textarea" v-model="removeForm.notes" placeholder="Optional context about this removal…" rows="3"></textarea></div>
              <div class="cs-edit-field"><label class="cs-edit-label">Confirm by typing "REMOVE" <span style="color:var(--red)">*</span></label><input class="cs-edit-input cs-remove-confirm-input" v-model="removeForm.confirm" placeholder="Type REMOVE to confirm"></div>
            </div>
            <div class="cs-modal-footer">
              <button class="btn btn-outline btn-sm" @click="modal.type=null">Cancel</button>
              <button class="cs-remove-btn" :disabled="removeForm.confirm !== 'REMOVE'" @click="confirmRemove">Remove Continuity Steward</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- PENDING TAB MODALS -->

      <!-- RESEND INVITATION -->
      <Transition name="cs-modal-fade">
        <div v-if="pndModal.type==='resend'" class="cs-modal-backdrop" @click.self="pndModal.type=null">
          <div class="cs-modal">
            <div class="cs-modal-header"><div class="cs-modal-title">Resend Invitation</div><button class="cs-modal-close" @click="pndModal.type=null"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
            <div class="cs-modal-body">
              <div class="pnd-modal-person-card">
                <div class="pnd-avatar" :style="{ background: pndModal.item?.avatarColor }">{{ pndModal.item?.initials }}</div>
                <div><div class="cs-card-name" style="font-size:13.5px;">{{ pndModal.item?.name }}</div><div style="font-size:12px;color:var(--text-4);">Originally invited {{ pndModal.item?.invited }}</div></div>
              </div>
              <div class="cs-edit-field"><label class="cs-edit-label">New Expiration Date</label><input class="cs-edit-input" v-model="resendForm.expiry" /></div>
              <div class="cs-edit-field"><label class="cs-edit-label">Optional Follow-up Message</label><textarea class="cs-review-textarea" v-model="resendForm.message" rows="4" placeholder="Hi Thomas, just following up on my Continuity Steward Invitation..."></textarea></div>
            </div>
            <div class="cs-modal-footer">
              <button class="btn btn-outline btn-sm" @click="pndModal.type=null">Cancel</button>
              <button class="cs-attest-btn" @click="showToast('Invitation resent','success'); pndModal.type=null"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Resend Invitation</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- PREVIEW AGREEMENT (outgoing) -->
      <Transition name="cs-modal-fade">
        <div v-if="pndModal.type==='preview'" class="cs-modal-backdrop" @click.self="pndModal.type=null">
          <div class="cs-modal">
            <div class="cs-modal-header"><div class="cs-modal-title">Agreement Preview — {{ pndModal.item?.name }}</div><button class="cs-modal-close" @click="pndModal.type=null"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
            <div class="cs-modal-body">
              <div class="cs-edit-notice"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><span>This agreement has been sent but not yet countersigned by {{ pndModal.item?.name }}.</span></div>
              <div class="cs-agr-doc">
                <div class="cs-agr-heading">Aegis CONTINUITY PLAN — PENDING SIGNATURE</div>
                <div class="cs-agr-meta"><strong>Provider:</strong> Dr. Sarah Johnson, MD — Psychiatrist</div>
                <div class="cs-agr-meta"><strong>Proposed Continuity Steward:</strong> {{ pndModal.item?.name }} — {{ pndModal.item?.role }}</div>
                <div class="cs-agr-meta"><strong>Sent:</strong> {{ pndModal.item?.invited }} | <strong>Expires:</strong> {{ pndModal.item?.expires }}</div>
                <div class="cs-agr-divider"></div>
                <div class="cs-agr-signatures">
                  <div class="cs-agr-sig"><span class="cs-agr-sig-badge">SIGNED</span> Dr. Sarah Johnson — Signed digitally, {{ pndModal.item?.invited }}</div>
                  <div class="cs-agr-sig"><span class="pnd-pending-badge" style="font-size:9.5px;padding:2px 7px;">PENDING</span> {{ pndModal.item?.name }} — Awaiting signature…</div>
                </div>
              </div>
            </div>
            <div class="cs-modal-footer">
              <button class="btn btn-outline btn-sm" @click="pndModal.type=null">Close</button>
              <button class="cs-attest-btn" @click="openPndModal('resend', pndModal.item); pndModal.type=null"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Resend</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- DECLINE REQUEST -->
      <Transition name="cs-modal-fade">
        <div v-if="pndModal.type==='decline'" class="cs-modal-backdrop" @click.self="pndModal.type=null">
          <div class="cs-modal cs-modal-sm">
            <div class="cs-modal-header"><div class="cs-modal-title">Decline Request</div><button class="cs-modal-close" @click="pndModal.type=null"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
            <div class="cs-modal-body"><p style="font-family:var(--font-sans);font-size:14px;color:var(--text-2);margin:0;">Decline this connection request?</p></div>
            <div class="cs-modal-footer">
              <button class="btn btn-outline btn-sm" @click="pndModal.type=null">Cancel</button>
              <button class="cs-remove-btn" @click="incomingRequests = incomingRequests.filter(r=>r.id!==pndModal.item?.id); pndModal.type=null; showToast('Request declined','info')">Decline</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- ACCEPT & START AGREEMENT (role step-up wizard) -->
      <Transition name="cs-modal-fade">
        <div v-if="pndModal.type==='accept'" class="cs-modal-backdrop" @click.self="pndModal.type=null">
          <div class="cs-modal cs-modal-lg">
            <div class="cs-modal-header"><div class="cs-modal-title">Add Continuity Steward — Role Step-up</div><button class="cs-modal-close" @click="pndModal.type=null"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
            <!-- wizard steps -->
            <div class="pnd-wizard-steps">
              <span v-for="(step,i) in wizardSteps" :key="step" class="pnd-wizard-step" :class="{ active: i===1, done: i===0 }">
                <span class="pnd-step-num">{{ i+1 }}</span> {{ step }}
              </span>
            </div>
            <div class="cs-modal-body">
              <div v-if="pndModal.item" class="pnd-modal-person-card" style="margin-bottom:16px;">
                <div class="pnd-avatar" :style="{ background: pndModal.item.avatarColor }">{{ pndModal.item.initials }}</div>
                <div><div class="cs-card-name" style="font-size:13.5px;">{{ pndModal.item.name }}</div><div style="font-size:12px;color:var(--text-4);">{{ pndModal.item.org }}</div></div>
              </div>
              <div class="cs-edit-label" style="margin-bottom:10px;">Continuity Steward Role <span style="color:var(--red)">*</span></div>
              <div v-for="opt in roleOptions" :key="opt.value" class="cs-vault-option" :class="{ selected: acceptForm.role === opt.value }" @click="acceptForm.role = opt.value" style="margin-bottom:8px;">
                <div class="cs-vault-radio"><span class="cs-vault-dot" v-if="acceptForm.role === opt.value"></span></div>
                <div><div class="cs-vault-opt-label">{{ opt.label }}</div><div class="cs-vault-opt-sub">{{ opt.sub }}</div></div>
              </div>
              <div class="cs-edit-field" style="margin-top:14px;"><label class="cs-edit-label">Compensation / Agreement Terms (Optional)</label><textarea class="cs-review-textarea" v-model="acceptForm.terms" rows="3" placeholder="e.g., Reciprocal Continuity Plan, hourly compensation, or 'No compensation — mutual professional agreement'"></textarea></div>
            </div>
            <div class="cs-modal-footer">
              <button class="btn btn-outline btn-sm" @click="pndModal.type=null">← Back</button>
              <button class="cs-attest-btn" @click="showToast('Opening agreement wizard…','info'); pndModal.type=null">Next: Approved Critical Incidents →</button>
            </div>
          </div>
        </div>
      </Transition>

      <Transition name="cs-modal-fade">
        <div v-if="reviewModalOpen" class="cs-modal-backdrop" @click.self="reviewModalOpen=false">
          <div class="cs-modal">
            <div class="cs-modal-header">
              <div class="cs-modal-title">Annual Continuity Steward Review — 2025</div>
              <button class="cs-modal-close" @click="reviewModalOpen=false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="cs-modal-body">
              <!-- Notice -->
              <div class="cs-review-notice">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0;margin-top:1px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>Annual review due <strong>June 15, 2025</strong>. Confirm all Continuity Steward details and procedures are current.</span>
              </div>

              <!-- Availability Confirmation -->
              <div class="cs-review-section-label">Continuity Steward Availability Confirmation</div>
              <div class="cs-review-toggles">
                <div v-for="item in reviewChecks.availability" :key="item.label" class="cs-review-toggle-row">
                  <div>
                    <div class="cs-review-toggle-label">{{ item.label }}</div>
                    <div class="cs-review-toggle-sub">{{ item.sub }}</div>
                  </div>
                  <label class="cs-toggle">
                    <input type="checkbox" v-model="item.on">
                    <span class="cs-toggle-track" :class="{ on: item.on }"><span class="cs-toggle-thumb"></span></span>
                  </label>
                </div>
              </div>

              <!-- Practice Information -->
              <div class="cs-review-section-label" style="margin-top:16px;">Practice Information</div>
              <div class="cs-review-toggles">
                <div v-for="item in reviewChecks.practice" :key="item.label" class="cs-review-toggle-row">
                  <div>
                    <div class="cs-review-toggle-label">{{ item.label }}</div>
                    <div v-if="item.sub" class="cs-review-toggle-sub">{{ item.sub }}</div>
                  </div>
                  <label class="cs-toggle">
                    <input type="checkbox" v-model="item.on">
                    <span class="cs-toggle-track" :class="{ on: item.on }"><span class="cs-toggle-thumb"></span></span>
                  </label>
                </div>
              </div>

              <!-- Review Notes -->
              <div class="cs-review-section-label" style="margin-top:16px;">Review Notes / Changes Since Last Year</div>
              <textarea class="cs-review-textarea" v-model="reviewNotes" placeholder="Document any personnel changes, updated responsibilities, or other relevant changes…" rows="4"></textarea>
            </div>
            <div class="cs-modal-footer">
              <button class="btn btn-outline btn-sm" @click="reviewModalOpen=false">Cancel</button>
              <button class="btn btn-outline btn-sm" @click="showToast('Draft saved','success'); reviewModalOpen=false">Save Draft</button>
              <button class="cs-attest-btn" @click="showToast('Review completed & attested','success'); reviewModalOpen=false">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                Attest &amp; Complete Review
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- REMIND ME LATER MODAL -->
      <Transition name="cs-modal-fade">
        <div v-if="remindModalOpen" class="cs-modal-backdrop" @click.self="remindModalOpen=false">
          <div class="cs-modal cs-modal-sm">
            <div class="cs-modal-header">
              <div class="cs-modal-title">Remind Me Later</div>
              <button class="cs-modal-close" @click="remindModalOpen=false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="cs-modal-body">
              <div class="cs-remind-notice">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0;margin-top:1px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>Annual reviews are required for your continuity plan to remain compliant.</span>
              </div>
              <div class="cs-remind-field">
                <label class="cs-remind-label">Remind me in</label>
                <select class="cs-remind-select" v-model="remindDays">
                  <option>3 days</option>
                  <option>1 week</option>
                  <option>2 weeks</option>
                  <option>1 month</option>
                </select>
              </div>
            </div>
            <div class="cs-modal-footer">
              <button class="btn btn-outline btn-sm" @click="remindModalOpen=false">Cancel</button>
              <button class="cs-attest-btn" @click="showToast('Reminder set for ' + remindDays,'success'); remindModalOpen=false">Set Reminder</button>
            </div>
          </div>
        </div>
      </Transition>

      <div class="dh-toast-stack">
        <div v-for="t in toasts" :key="t.id" class="dh-toast" :class="t.type">
          <svg v-if="t.type==='success'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          <svg v-else-if="t.type==='plan-limit'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span>{{ t.msg }}</span>
          <button v-if="t.type==='plan-limit'" class="cs-toast-dismiss" @click="toasts = toasts.filter(x=>x.id!==t.id)">×</button>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Components/AppLayout.vue';

defineProps({ user: { type: Object, default: () => ({}) } });

// ── Declare all data refs FIRST to avoid TDZ ──
const toasts = ref([]);
let tid = 0;
function showToast(msg, type = 'info') {
  const id = ++tid;
  toasts.value.push({ id, msg, type });
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id); }, 3500);
}

const teamMembers = [
  { initials:'MC', name:'Marcus Chen',     avatarColor:'#a0813e', certified:true,  roleLabel:'PRIMARY CS'   },
  { initials:'TC', name:'Dr. Thomas Chen', avatarColor:'#6a4c8c', certified:false, roleLabel:'ALTERNATE CS' },
  { initials:'LJ', name:'Linda Johnson',   avatarColor:'#4a7a6a', certified:true,  roleLabel:'PRIMARY SS'   },
  { initials:'BS', name:'Brian Santos',    avatarColor:'#7a5c4a', certified:false, roleLabel:'ALTERNATE SS' },
];

const ssMembers = teamMembers.filter(m => m.roleLabel.includes('SS'));
const csMembers = teamMembers.filter(m => m.roleLabel.includes('CS'));
const extraSSMembers = [
  { name:'Rachel Pham',   role:'Alternate' },
  { name:'Jordan Taylor', role:'Alternate' },
  { name:'Brian Santos',  role:'Alternate' },
];
const extraCSMembers = [
  { name:'Dr. Laura Reyes',      role:'Secondary' },
  { name:'Dr. Amelia Rodriguez', role:'Secondary' },
];

const resp = (icon, text, color = 'var(--gold-dark)') => ({ icon, text, color });

const stewards = ref([
  {
    initials:'MC', name:'Marcus Chen, JD, LCSW', roleLabel:'PRIMARY CONTINUITY STEWARD',
    org:'Principal, Chen Practice Solutions · Aegis Certified Continuity Steward',
    phone:'(510) 555-0189', email:'marcus@chenpracticesolutions.com',
    signed:'Feb 15, 2026', reviewDue:'Feb 15, 2027', backup:false,
    responsibilities:[
      resp('<path d="M22 16.92v3a2 2 0 0 1-2.18 2"/>', 'Notify clients of practice closure or transition', 'var(--gold-dark)'),
      resp('<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>', 'Secure and transfer all patient records within 72 hrs (HIPAA-compliant)', 'var(--blue-dark)'),
      resp('<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>', 'Cancel or transition insurance billing and outstanding claims', 'var(--green-dark)'),
      resp('<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>', 'Notify state licensing board and malpractice carrier within 30 days', 'var(--gold-dark)'),
      resp('<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>', 'Access Document Vault using emergency credentials on file', 'var(--text-4)'),
      resp('<circle cx="12" cy="5" r="3"/><circle cx="5" cy="19" r="3"/><circle cx="19" cy="19" r="3"/><path d="M12 8v6M12 14l-6 4M12 14l6 4"/>', 'Notify referral partners and professional Aegis network', 'var(--blue-dark)'),
    ],
  },
  {
    initials:'PR', name:'Dr. Priya Raman, PsyD', roleLabel:'ALTERNATE CONTINUITY STEWARD',
    org:'Clinical Director · Continuity Partners · Aegis Certified Continuity Steward',
    phone:'(415) 555-0167', email:'priya.raman@continuitypartners.net',
    signed:'Feb 16, 2026', reviewDue:'Feb 16, 2027', backup:true,
    responsibilities:[
      resp('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>', 'Assume all primary Continuity Steward duties if the primary is unavailable', 'var(--gold-dark)'),
      resp('<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>', 'Clinically supervise patient care transition to appropriate providers', 'var(--green-dark)'),
      resp('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>', 'Co-sign any clinical documentation required during transition', 'var(--blue-dark)'),
    ],
  },
]);
const activeTab = ref('mine');
const reviewModalOpen   = ref(false);
const remindModalOpen   = ref(false);
const activateModalOpen = ref(false);
const reviewNotes = ref('');
const remindDays  = ref('3 days');

const activateForm = reactive({ incidentType:'', activatedBy:'Dr. Sarah Johnson (self — planned transition)', context:'', confirm:'' });

function showPlanLimitToast() {
  const id = ++tid;
  toasts.value.push({ id, msg: "You've reached the maximum of 2 Continuity Stewards on the Continuity Practice plan. Contact support to discuss enterprise options.", type:'plan-limit' });
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id); }, 6000);
}

function confirmActivate() {
  if (activateForm.confirm !== 'ACTIVATE CONTINUITY SUPPORT' || !activateForm.incidentType) return;
  activateModalOpen.value = false;
  showToast('Continuity Support activated — all stewards have been notified', 'success');
}

// ── Card action modals ──
const modal = reactive({ type: null, steward: null });
const editForm   = reactive({ name:'', title:'', phone:'', email:'', org:'', emergencyPhone:'', notes:'' });
const roleForm   = reactive({ newRole:'Primary Continuity Steward', reason:'' });
const removeForm = reactive({ reason:'', notes:'', confirm:'' });
const vaultForm  = reactive({ level:'emergency' });

const vaultOptions = [
  { value:'emergency', label:'Emergency Only (Recommended)', sub:'Vault unlocks automatically when continuity support is activated.' },
  { value:'full',      label:'Full Read Access (Now)',       sub:'Continuity Steward can view documents at any time.' },
  { value:'none',      label:'No Access',                    sub:'Emergency credentials must be shared separately.' },
];
const vaultDocs = [
  { name:'Sensitive Information & Credentials (Vault)', meta:'Stored in your Document Vault · sealed until activation', status:'ACCESSIBLE' },
  { name:'Malpractice Insurance Policy',                meta:'Uploaded Sep 2024 · Expires Dec 2025',                    status:'ACCESSIBLE' },
  { name:'Practice Financials & Billing Contacts',      meta:'Not yet uploaded',                                        status:'MISSING' },
];
const authIncidents = reactive([
  { label:'Death',                                  on:true  },
  { label:'Short-Term Incapacitation',              on:true  },
  { label:'Long-Term Incapacitation',               on:true  },
  { label:'Missing Person',                         on:true  },
  { label:'Detainment',                             on:false },
  { label:'Natural Disaster',                       on:true  },
  { label:'Geopolitical or Conflict Related Events',on:false },
  { label:'Pre-Mature Labor',                       on:true  },
]);

function openModal(type, steward) {
  modal.type    = type;
  modal.steward = steward;
  if (type === 'edit') {
    editForm.name          = steward.name;
    editForm.title         = steward.org.split('·')[0]?.trim() || '';
    editForm.phone         = steward.phone;
    editForm.email         = steward.email;
    editForm.org           = steward.org.split('·')[1]?.trim() || steward.org;
    editForm.emergencyPhone = '';
    editForm.notes         = '';
  }
  if (type === 'role') { roleForm.newRole = steward.roleLabel.includes('PRIMARY') ? 'Primary Continuity Steward' : 'Alternate Continuity Steward'; roleForm.reason = ''; }
  if (type === 'remove') { removeForm.reason=''; removeForm.notes=''; removeForm.confirm=''; }
  if (type === 'annual') { reviewModalOpen.value = true; modal.type = null; }
}
function confirmRemove() {
  if (removeForm.confirm !== 'REMOVE') return;
  stewards.value = stewards.value.filter(s => s.name !== modal.steward?.name);
  modal.type = null;
  showToast('Continuity Steward removed', 'info');
}

// ── I'm Steward For data ──
const stewardingFor = ref([
  { initials:'MT', name:'Dr. Michael Torres, MD', avatarColor:'#a0813e', role:'PRIMARY CS', org:'Bay Area Psychiatry Associates', location:'Oakland, CA', reviewDue:'Nov 8, 2026' },
]);

// ── Pending tab data ──
const incomingRequests = ref([
  { id:1, initials:'LR', name:'Dr. Laura Reyes, LCSW', org:'Principal, Reyes Continuity · Reyes Continuity · Aegis Certified Continuity Steward', avatarColor:'#a0813e', date:'Jan 5, 2026', serving:2 },
]);
const outgoingInvitations = ref([
  { id:1, initials:'TC', name:'Dr. Thomas Chen, LCSW', org:'Founder, Chen Continuity Group · Chen Continuity Group · Aegis Certified Continuity Steward', avatarColor:'#6a4c8c', role:'Alternate Continuity Steward', email:'thomas@chencontinuity.com', invited:'Jan 10, 2026', expires:'Feb 10, 2026', sentAgo:'163 days ago', status:'pending' },
  { id:2, initials:'AR', name:'Dr. Amelia Rodriguez, PsyD', org:'', avatarColor:'#4a7a6a', status:'declined', declineNote:'Declined Nov 28, 2025 · Reason: "Currently at capacity for Continuity Steward commitments"' },
]);

// Pending tab modals
const pndModal   = reactive({ type:null, item:null });
const resendForm = reactive({ expiry:'30 days from today', message:'' });
const acceptForm = reactive({ role:'primary', terms:'' });

const wizardSteps = ['Find Person','Role Step-up','Approved Critical Incidents','Responsibilities','Send Agreement'];
const roleOptions = [
  { value:'primary',   label:'Primary Continuity Steward',   sub:'First in line. Full authority to act. Best for a trusted colleague or administrator who is highly available.' },
  { value:'support',   label:'Support Continuity Steward',   sub:'Works alongside the Primary, sharing responsibilities or specific tasks during a critical moment, as you define them in the agreement.' },
  { value:'alternate', label:'Alternate Continuity Steward', sub:'Steps in if the Primary is unreachable. Good for a clinically licensed colleague who can carry the practice through a gap.' },
];

function openPndModal(type, item) {
  pndModal.type = type;
  pndModal.item = item;
  if (type === 'resend') { resendForm.expiry = '30 days from today'; resendForm.message = ''; }
  if (type === 'accept') { acceptForm.role = 'primary'; acceptForm.terms = ''; }
}

function goToMessage(steward) {
  router.visit('/provider/messages');
}

const reviewChecks = reactive({
  availability: [
    { label:'Marcus Chen (Primary) still agrees to serve',  sub:'Has confirmed availability verbally or in writing',  on:true  },
    { label:'Dr. Priya Raman (Support) still agrees to serve', sub:'No conflicts or changes to availability',         on:true  },
    { label:'Contact information is current for all Continuity Stewards', sub:'Phone, email, and address verified',  on:false },
  ],
  practice: [
    { label:'Document Vault contains current client roster',          sub:'',  on:true  },
    { label:'Malpractice insurance policy is current and on file',    sub:'',  on:true  },
    { label:'Practice billing info and payer contacts documented',    sub:'',  on:false },
    { label:'Client transition protocol is documented and accessible',sub:'',  on:true  },
    { label:'Continuity Steward Vault access credentials have been updated', sub:'', on:false },
  ],
});

const notifyToggles = reactive([
  { label:'Annual Re-Attestation is complete',                on:true  },
  { label:'A Continuity Steward requests changes',            on:true  },
  { label:'A Continuity Steward updates their information',   on:true  },
  { label:'Roles or permissions change',                      on:true  },
  { label:'Important Documents are accessed',                 on:true  },
  { label:'A Continuity Steward is added, removed, or updated', on:true },
  { label:'A critical incident is reported',                  on:true  },
  { label:'A Continuity Response is activated',               on:true  },
]);
</script>

<style scoped>
/* Hero */
.cs-hero { display:flex; align-items:flex-start; justify-content:space-between; gap:20px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:22px 26px; margin-bottom:12px; box-shadow:var(--shadow-xs); flex-wrap:wrap; }
.cs-eyebrow { font-family:var(--font-sans); font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--gold-dark); margin-bottom:5px; }
.cs-title { font-family:var(--font-serif); font-size:26px; font-weight:700; color:var(--text); margin:0 0 6px; }
.cs-sub { font-family:var(--font-sans); font-size:12.5px; color:var(--text-3); margin:0; line-height:1.5; max-width:600px; }
.cs-hero-actions { display:flex; gap:8px; flex-shrink:0; align-items:center; flex-wrap:wrap; }
.cs-btn-icon { display:inline-flex; align-items:center; gap:6px; }
.cs-activate-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-family:var(--font-sans); font-size:12.5px; font-weight:700; background:var(--red); color:#fff; border:none; border-radius:var(--radius-sm,8px); cursor:pointer; transition:background .15s; }
.cs-activate-btn:hover { background:var(--red-dark); }

/* Review banner */
.cs-review-banner { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:14px 18px; background:rgba(232,169,74,.08); border:1px solid rgba(232,169,74,.4); border-radius:var(--radius-lg,14px); margin-bottom:10px; flex-wrap:wrap; }
.cs-review-left { display:flex; align-items:flex-start; gap:10px; min-width:0; color:var(--orange-dark); }
.cs-review-title { font-family:var(--font-sans); font-size:13.5px; font-weight:700; color:var(--orange-dark); margin-bottom:2px; }
.cs-review-sub   { font-size:12px; color:var(--orange-dark); opacity:.85; line-height:1.5; }
.cs-review-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; }
.cs-review-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; font-family:var(--font-sans); font-size:12.5px; font-weight:700; background:var(--gold-dark); color:#fff; border:none; border-radius:var(--radius-sm,8px); cursor:pointer; transition:background .15s; }
.cs-review-btn:hover { background:var(--gold); }

/* Plan limit notice */
.cs-plan-notice { display:flex; align-items:center; gap:9px; padding:10px 14px; background:var(--blue-light); border:1px solid var(--soft-blue); border-radius:8px; margin-bottom:12px; font-family:var(--font-sans); font-size:12.5px; color:var(--blue-dark); }

/* Stat cards */
.cs-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:14px; }
.cs-stat { display:flex; align-items:center; gap:14px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:16px 18px; box-shadow:var(--shadow-xs); }
.cs-stat-icon { width:36px; height:36px; border-radius:8px; background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cs-stat-gold  { background:rgba(160,129,62,.1); color:var(--gold-dark); }
.cs-stat-blue  { background:var(--blue-light); color:var(--blue-dark); }
.cs-stat-green { background:var(--green-light); color:var(--green-dark); }
.cs-stat-val { font-family:var(--font-serif); font-size:22px; font-weight:700; color:var(--text); line-height:1; }
.cs-stat-lbl { font-family:var(--font-sans); font-size:11px; color:var(--text-4); margin-top:3px; }

/* Tabs */
.cs-tabs { display:flex; align-items:center; gap:2px; border-bottom:1px solid var(--border); margin-bottom:14px; }
.cs-tab { display:inline-flex; align-items:center; gap:7px; padding:9px 14px; font-family:var(--font-sans); font-size:13px; font-weight:500; color:var(--text-3); background:transparent; border:none; border-bottom:2px solid transparent; cursor:pointer; transition:color .15s,border-color .15s; margin-bottom:-1px; }
.cs-tab:hover { color:var(--text); }
.cs-tab.active { color:var(--text); font-weight:600; border-bottom-color:var(--gold-dark); }
.cs-tab-count { font-size:11px; font-weight:700; background:var(--surface-3); color:var(--text-2); border-radius:99px; padding:1px 7px; }
.cs-tab.active .cs-tab-count { background:var(--gold-dark); color:#fff; }
.cs-tab-desc { font-family:var(--font-sans); font-size:13px; color:var(--text-3); margin-bottom:16px; }

/* Steward card */
.cs-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:20px 22px; margin-bottom:14px; box-shadow:var(--shadow-xs); }
.cs-card-header { display:flex; align-items:flex-start; gap:14px; margin-bottom:16px; }
.cs-card-avatar { width:48px; height:48px; border-radius:10px; background:var(--gold-dark); color:#fff; font-family:var(--font-sans); font-size:15px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cs-card-identity { min-width:0; flex:1; }
.cs-card-name-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:3px; }
.cs-card-name { font-family:var(--font-sans); font-size:14.5px; font-weight:700; color:var(--text); }
.cs-role-badge { font-size:9.5px; font-weight:700; letter-spacing:.06em; padding:2px 8px; border-radius:99px; background:rgba(160,129,62,.1); color:var(--gold-dark); border:1px solid var(--fade-gold); }
.cs-active-badge { font-size:10px; font-weight:700; color:var(--green-dark); background:var(--green-light); border-radius:99px; padding:2px 8px; }
.cs-signed-badge { font-size:10px; font-weight:700; color:var(--green-dark); background:var(--green-light); border-radius:99px; padding:2px 8px; }
.cs-card-org { font-size:12px; color:var(--text-4); margin-bottom:6px; }
.cs-card-meta { display:flex; flex-wrap:wrap; gap:14px; }
.cs-meta-item { display:inline-flex; align-items:center; gap:5px; font-size:12px; color:var(--text-4); }

/* Responsibilities */
.cs-responsibilities { background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px 16px; margin-bottom:14px; }
.cs-resp-label { font-family:var(--font-sans); font-size:9.5px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-4); margin-bottom:10px; }
.cs-resp-row { display:flex; align-items:flex-start; gap:9px; padding:7px 0; border-bottom:1px solid var(--border); }
.cs-resp-row:last-child { border-bottom:none; padding-bottom:0; }
.cs-resp-icon { width:20px; height:20px; border-radius:50%; background:var(--surface-3); display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; }
.cs-resp-text { font-size:12.5px; color:var(--text-2); line-height:1.5; }

/* Card action buttons */
.cs-card-actions { display:flex; gap:6px; padding-top:10px; border-top:1px solid var(--border); }
.cs-act-btn { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:6px; border:1px solid var(--border); background:var(--surface); color:var(--text-3); cursor:pointer; transition:all .15s; }
.cs-act-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.cs-act-danger:hover { border-color:var(--red); color:var(--red); background:var(--red-light); }
/* Message button — larger square with cream bg matching image */
.cs-act-btn-msg { width:36px; height:36px; border-radius:10px; background:var(--surface-2); border:1px solid var(--border); color:var(--text-2); }
.cs-act-btn-msg:hover { background:var(--surface-3); border-color:var(--gold-dark); color:var(--gold-dark); }

/* Notify Me */
.cs-notify-section { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:20px 22px; margin-bottom:14px; box-shadow:var(--shadow-xs); margin-top:4px; }
.cs-notify-header { font-family:var(--font-sans); font-size:15px; font-weight:700; color:var(--text); margin-bottom:4px; }
.cs-notify-sub    { font-size:12.5px; color:var(--text-4); margin-bottom:16px; }
.cs-notify-list { display:flex; flex-direction:column; }
.cs-notify-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 0; border-bottom:1px solid var(--border); }
.cs-notify-row:last-child { border-bottom:none; }
.cs-notify-label { font-family:var(--font-sans); font-size:13px; color:var(--text-2); }

/* Toggle (reuse ContinuityPlan style) */
.cs-toggle { position:relative; display:inline-block; width:40px; height:22px; flex-shrink:0; }
.cs-toggle input { opacity:0; width:0; height:0; }
.cs-toggle-track { position:absolute; inset:0; background:var(--border-dark); border-radius:99px; cursor:pointer; transition:background .2s; }
.cs-toggle-track.on { background:var(--gold-dark); }
.cs-toggle-thumb { position:absolute; width:18px; height:18px; left:2px; top:2px; background:#fff; border-radius:50%; transition:transform .2s; }
.cs-toggle-track.on .cs-toggle-thumb { transform:translateX(18px); }

/* Empty states */
.cs-empty { display:flex; flex-direction:column; align-items:center; gap:12px; padding:50px 20px; color:var(--text-4); }
.cs-empty p { font-family:var(--font-sans); font-size:14px; margin:0; color:var(--text-3); }

@media(max-width:860px) { .cs-stats { grid-template-columns:repeat(2,1fr); } }

/* ── Modals ──────────────────────────────────────────────────── */
.cs-modal-backdrop { position:fixed; inset:0; z-index:1000; background:rgba(30,28,26,.45); backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center; padding:20px; }
.cs-modal { background:var(--surface); border:1px solid var(--border); border-radius:14px; box-shadow:0 24px 64px rgba(30,28,26,.2); width:100%; max-width:560px; max-height:90vh; display:flex; flex-direction:column; overflow:hidden; }
.cs-modal-sm { max-width:420px; }
.cs-modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 22px 14px; border-bottom:1px solid var(--border); flex-shrink:0; }
.cs-modal-title { font-family:var(--font-serif); font-size:17px; font-weight:700; color:var(--text); }
.cs-modal-close { width:28px; height:28px; padding:0; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--border); border-radius:6px; background:var(--surface); color:var(--text-3); cursor:pointer; transition:all .15s; flex-shrink:0; }
.cs-modal-close:hover { border-color:var(--text); color:var(--text); }
.cs-modal-body { padding:18px 22px; overflow-y:auto; display:flex; flex-direction:column; gap:14px; flex:1; }
.cs-modal-footer { display:flex; align-items:center; justify-content:flex-end; gap:8px; padding:14px 22px; border-top:1px solid var(--border); background:var(--surface-2); flex-shrink:0; }
.cs-attest-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; font-family:var(--font-sans); font-size:12.5px; font-weight:600; background:var(--gold-dark); color:#fff; border:1px solid var(--gold-dark); border-radius:var(--radius-sm,8px); cursor:pointer; transition:background .15s; }
.cs-attest-btn:hover { background:var(--gold); border-color:var(--gold); }

/* Review modal */
.cs-review-notice { display:flex; align-items:flex-start; gap:9px; padding:10px 13px; background:rgba(232,169,74,.08); border:1px solid rgba(232,169,74,.4); border-radius:8px; font-family:var(--font-sans); font-size:12.5px; color:var(--orange-dark); line-height:1.5; }
.cs-review-section-label { font-family:var(--font-sans); font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--text-3); }
.cs-review-toggles { display:flex; flex-direction:column; gap:1px; }
.cs-review-toggle-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:11px 14px; background:var(--surface); border:1px solid var(--border); border-radius:6px; margin-top:4px; }
.cs-review-toggle-label { font-family:var(--font-sans); font-size:13px; font-weight:500; color:var(--text); margin-bottom:1px; }
.cs-review-toggle-sub { font-size:11.5px; color:var(--text-4); }
.cs-review-textarea { width:100%; padding:10px 12px; font-family:var(--font-sans); font-size:13px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:8px; outline:none; resize:vertical; min-height:80px; line-height:1.6; box-sizing:border-box; transition:border-color .15s; }
.cs-review-textarea:focus { border-color:var(--gold-dark); }
.cs-review-textarea::placeholder { color:var(--text-4); }

/* Remind modal */
.cs-remind-notice { display:flex; align-items:flex-start; gap:9px; padding:10px 13px; background:rgba(232,169,74,.08); border:1px solid rgba(232,169,74,.4); border-radius:8px; font-family:var(--font-sans); font-size:12.5px; color:var(--orange-dark); line-height:1.5; }
.cs-remind-field { display:flex; flex-direction:column; gap:6px; }
.cs-remind-label { font-family:var(--font-sans); font-size:12px; font-weight:600; color:var(--text-2); }
.cs-remind-select { width:100%; padding:9px 12px; font-family:var(--font-sans); font-size:13.5px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:8px; outline:none; cursor:pointer; appearance:none; -webkit-appearance:none; transition:border-color .15s; }
.cs-remind-select:focus { border-color:var(--gold-dark); }

/* ── Activate Continuity Support modal ─────────────────────── */
.cs-modal-header-red { background:var(--red); border-bottom-color:var(--red-dark); }
.cs-activate-warning { display:flex; align-items:flex-start; gap:9px; padding:10px 13px; background:var(--red-light); border:1px solid var(--soft-red,rgba(160,45,34,.4)); border-radius:8px; font-family:var(--font-sans); font-size:12.5px; color:var(--red); line-height:1.5; }
.cs-activate-confirm-input:focus { border-color:var(--red); box-shadow:0 0 0 3px rgba(160,45,34,.12); }
.cs-activate-confirm-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; font-family:var(--font-sans); font-size:12.5px; font-weight:600; background:var(--red); color:#fff; border:1px solid var(--red); border-radius:var(--radius-sm,8px); cursor:pointer; transition:background .15s; }
.cs-activate-confirm-btn:hover:not(:disabled) { background:var(--red-dark); }
.cs-activate-confirm-btn:disabled { opacity:.45; cursor:not-allowed; }

/* ── Plan-limit toast ────────────────────────────────────────── */
.dh-toast.plan-limit { background:var(--text); border-left:4px solid var(--blue); max-width:400px; align-items:flex-start; }
.cs-toast-dismiss { margin-left:auto; background:none; border:none; color:var(--text-4); cursor:pointer; font-size:16px; line-height:1; padding:0 0 0 8px; flex-shrink:0; }
.cs-toast-dismiss:hover { color:#fff; }

/* ── I'm Steward For tab ─────────────────────────────────────── */
.sfor-banner { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:14px 18px; background:var(--blue-light); border:1px solid var(--soft-blue); border-radius:var(--radius-lg,14px); margin-bottom:12px; flex-wrap:wrap; }
.sfor-banner-left { display:flex; align-items:flex-start; gap:10px; min-width:0; color:var(--blue-dark); flex:1; }
.sfor-banner-title { font-family:var(--font-sans); font-size:13.5px; font-weight:600; color:var(--blue-dark); margin-bottom:3px; }
.sfor-banner-sub { font-size:12px; color:var(--blue-dark); opacity:.85; line-height:1.5; }
.sfor-portal-btn { display:inline-flex; align-items:center; gap:5px; flex-shrink:0; }
.sfor-section-title { font-family:var(--font-sans); font-size:14px; font-weight:700; color:var(--text); margin-bottom:10px; }
.sfor-provider-card { display:flex; align-items:center; justify-content:space-between; gap:12px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:14px 18px; margin-bottom:8px; cursor:pointer; transition:box-shadow .15s,border-color .15s; box-shadow:var(--shadow-xs); }
.sfor-provider-card:hover { box-shadow:var(--shadow-sm); border-color:var(--gold-dark); }
.sfor-provider-left { display:flex; align-items:center; gap:12px; min-width:0; }
.sfor-provider-name-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:3px; }
.sfor-provider-name { font-family:var(--font-sans); font-size:13.5px; font-weight:700; color:var(--text); }
.sfor-role-badge   { font-size:10px; font-weight:700; color:var(--gold-dark); background:rgba(160,129,62,.1); border:1px solid var(--fade-gold); border-radius:99px; padding:2px 8px; }
.sfor-active-badge { font-size:10px; font-weight:700; color:var(--green-dark); background:var(--green-light); border-radius:99px; padding:2px 8px; }
.sfor-provider-meta { font-size:12px; color:var(--text-4); }
.sfor-arrow-btn { width:30px; height:30px; border-radius:6px; border:1px solid var(--border); background:var(--surface); color:var(--text-3); display:inline-flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0; transition:all .15s; }
.sfor-arrow-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }

/* ── Pending tab ─────────────────────────────────────────────── */
.pnd-section-row  { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:12px; gap:10px; }
.pnd-section-title { font-family:var(--font-sans); font-size:14px; font-weight:700; color:var(--text); margin-bottom:3px; }
.pnd-section-sub   { font-size:12px; color:var(--text-4); }
.pnd-new-badge { font-size:11px; font-weight:700; color:var(--gold-dark); background:rgba(160,129,62,.1); border:1px solid var(--fade-gold); border-radius:99px; padding:2px 10px; flex-shrink:0; }
.pnd-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:16px 20px; margin-bottom:10px; box-shadow:var(--shadow-xs); }
.pnd-card-top { display:flex; align-items:flex-start; gap:14px; margin-bottom:12px; }
.pnd-avatar { width:42px; height:42px; border-radius:10px; color:#fff; font-family:var(--font-sans); font-size:14px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.pnd-info { flex:1; min-width:0; }
.pnd-name-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:3px; }
.pnd-name  { font-family:var(--font-sans); font-size:14px; font-weight:700; color:var(--text); }
.pnd-org   { font-size:12px; color:var(--text-4); margin-bottom:6px; }
.pnd-cs-badge      { font-size:10px; font-weight:700; color:var(--text-3); background:var(--surface-3); border-radius:99px; padding:2px 8px; }
.pnd-aegis-badge   { font-size:10px; font-weight:700; color:var(--green-dark); background:var(--green-light); border-radius:99px; padding:2px 8px; }
.pnd-pending-badge { font-size:10px; font-weight:700; color:var(--orange-dark); background:rgba(232,169,74,.12); border-radius:99px; padding:2px 8px; }
.pnd-role-badge-sm { font-size:10px; font-weight:700; color:var(--gold-dark); background:rgba(160,129,62,.1); border:1px solid var(--fade-gold); border-radius:99px; padding:2px 8px; }
.pnd-declined-badge { font-size:10px; font-weight:700; color:var(--red); background:var(--red-light); border-radius:99px; padding:2px 8px; }
.pnd-meta-row { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:8px; }
.pnd-meta-item { display:inline-flex; align-items:center; gap:5px; font-size:12px; color:var(--text-4); }
.pnd-plan-notice { display:flex; align-items:center; gap:8px; padding:8px 12px; background:var(--blue-light); border:1px solid var(--soft-blue); border-radius:7px; font-size:12px; color:var(--blue-dark); margin-top:4px; }
.pnd-actions { display:flex; align-items:center; gap:6px; padding-top:10px; border-top:1px solid var(--border); flex-wrap:wrap; }
.pnd-sent-ago { display:flex; flex-direction:column; align-items:flex-end; gap:4px; flex-shrink:0; }
.pnd-sent-label { font-size:11px; color:var(--text-4); white-space:nowrap; }
.pnd-modal-person-card { display:flex; align-items:center; gap:12px; padding:12px 14px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; }
.pnd-wizard-steps { display:flex; align-items:center; gap:4px; padding:12px 22px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
.pnd-wizard-step { display:inline-flex; align-items:center; gap:5px; font-family:var(--font-sans); font-size:11px; font-weight:500; color:var(--text-4); padding:4px 8px; }
.pnd-wizard-step.done   { color:var(--green-dark); }
.pnd-wizard-step.active { color:var(--text); font-weight:700; }
.pnd-step-num { width:18px; height:18px; border-radius:50%; background:var(--surface-3); display:inline-flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; }
.pnd-wizard-step.done   .pnd-step-num { background:var(--green-dark); color:#fff; }
.pnd-wizard-step.active .pnd-step-num { background:var(--gold-dark); color:#fff; }

.cs-modal-fade-enter-active, .cs-modal-fade-leave-active { transition:opacity .2s ease; }
.cs-modal-fade-enter-active .cs-modal, .cs-modal-fade-leave-active .cs-modal { transition:transform .2s ease; }
.cs-modal-fade-enter-from, .cs-modal-fade-leave-to { opacity:0; }
.cs-modal-fade-enter-from .cs-modal { transform:translateY(-10px) scale(0.98); }

/* ── Card modal shared ────────────────────────────────────────── */
.cs-modal-lg { max-width:640px; }
.cs-modal-sub { font-family:var(--font-sans); font-size:12px; color:var(--text-4); margin-top:2px; }

/* Agreement */
.cs-agr-status { display:flex; align-items:center; gap:7px; padding:8px 12px; background:var(--green-light); border:1px solid var(--soft-green); border-radius:8px; font-size:12.5px; color:var(--green-dark); margin-bottom:4px; }
.cs-agr-doc { border:1px solid var(--border); border-radius:8px; padding:20px 22px; background:var(--surface); }
.cs-agr-heading { font-family:var(--font-serif); font-size:15px; font-weight:700; color:var(--text); text-align:center; margin-bottom:12px; letter-spacing:.04em; }
.cs-agr-meta { font-size:12.5px; color:var(--text-2); margin-bottom:4px; line-height:1.5; }
.cs-agr-divider { height:1px; background:var(--border); margin:12px 0; }
.cs-agr-para { font-size:12.5px; color:var(--text-2); line-height:1.65; margin-bottom:10px; }
.cs-agr-signatures { display:flex; flex-direction:column; gap:6px; margin-top:14px; padding-top:12px; border-top:1px solid var(--border); }
.cs-agr-sig { display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--text-2); }
.cs-agr-sig-badge { font-size:9.5px; font-weight:700; padding:2px 7px; background:var(--green-light); color:var(--green-dark); border-radius:4px; }

/* Edit form */
.cs-edit-notice { display:flex; align-items:flex-start; gap:8px; padding:9px 12px; background:var(--blue-light); border:1px solid var(--soft-blue); border-radius:8px; font-size:12.5px; color:var(--blue-dark); line-height:1.5; }
.cs-edit-notice-gold { background:rgba(232,169,74,.08); border-color:rgba(232,169,74,.4); color:var(--orange-dark); }
.cs-edit-field { display:flex; flex-direction:column; gap:5px; }
.cs-edit-label { font-family:var(--font-sans); font-size:11.5px; font-weight:600; color:var(--text-2); }
.cs-edit-input { padding:9px 12px; font-family:var(--font-sans); font-size:13.5px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:8px; outline:none; transition:border-color .15s; width:100%; box-sizing:border-box; }
.cs-edit-input:focus { border-color:var(--gold-dark); box-shadow:0 0 0 3px rgba(160,129,62,.14); }

/* Role card */
.cs-role-card { display:flex; align-items:center; gap:12px; padding:12px 14px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; }
.cs-remove-card { display:flex; align-items:center; gap:12px; padding:12px 14px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; }

/* Vault access */
.cs-vault-option { display:flex; align-items:flex-start; gap:12px; padding:12px 14px; border:1.5px solid var(--border); border-radius:8px; cursor:pointer; transition:border-color .15s,background .15s; margin-bottom:6px; }
.cs-vault-option.selected { border-color:var(--gold-dark); background:rgba(160,129,62,.05); }
.cs-vault-radio { width:16px; height:16px; border-radius:50%; border:1.5px solid var(--border-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px; }
.cs-vault-option.selected .cs-vault-radio { border-color:var(--gold-dark); }
.cs-vault-dot { width:8px; height:8px; border-radius:50%; background:var(--gold-dark); }
.cs-vault-opt-label { font-size:13px; font-weight:600; color:var(--text); margin-bottom:2px; }
.cs-vault-opt-sub   { font-size:12px; color:var(--text-4); }
.cs-vault-doc-row { display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--border); border-radius:8px; margin-bottom:5px; }
.cs-vault-doc-icon { color:var(--text-4); flex-shrink:0; }
.cs-vault-doc-info { flex:1; min-width:0; }
.cs-vault-doc-name { font-size:13px; font-weight:600; color:var(--text); }
.cs-vault-doc-meta { font-size:11.5px; color:var(--text-4); margin-top:1px; }
.cs-vault-accessible { font-size:10.5px; font-weight:700; color:var(--green-dark); background:var(--green-light); border-radius:99px; padding:2px 8px; flex-shrink:0; }
.cs-vault-missing    { font-size:10.5px; font-weight:700; color:var(--text-3); background:var(--surface-3); border-radius:99px; padding:2px 8px; flex-shrink:0; }

/* Auth incidents */
.cs-auth-inc-row { display:flex; align-items:flex-start; gap:10px; padding:10px 14px; border:1px solid var(--border); border-radius:8px; margin-bottom:5px; cursor:pointer; transition:background .12s; }
.cs-auth-inc-row.cs-auth-checked { background:rgba(160,129,62,.04); border-color:var(--fade-gold); }
.cs-auth-inc-name { font-size:13px; font-weight:500; color:var(--text); }
.cs-auth-inc-sub  { font-size:11.5px; color:var(--text-4); margin-top:1px; }

/* Remove */
.cs-remove-warning { display:flex; align-items:flex-start; gap:8px; padding:9px 12px; background:var(--red-light); border:1px solid var(--soft-red,rgba(160,45,34,.4)); border-radius:8px; font-size:12.5px; color:var(--red); line-height:1.5; }
.cs-remove-confirm-input:focus { border-color:var(--red); box-shadow:0 0 0 3px rgba(160,45,34,.12); }
.cs-remove-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; font-family:var(--font-sans); font-size:12.5px; font-weight:600; background:var(--red); color:#fff; border:1px solid var(--red); border-radius:var(--radius-sm,8px); cursor:pointer; transition:background .15s; }
.cs-remove-btn:disabled { opacity:.45; cursor:not-allowed; }
.cs-remove-btn:not(:disabled):hover { background:var(--red-dark); }
</style>
