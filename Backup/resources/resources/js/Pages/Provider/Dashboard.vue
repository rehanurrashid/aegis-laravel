<template>
  <AppLayout :user="user" portal="practitioner" activePage="dashboard" pageTitle="Dashboard"
    :hasEmergency="hasEmergency" :unreadMessages="0" :unreadNotifs="2">
    <div class="page-body-inner">

      <!-- Emergency alert -->
      <div v-if="hasEmergency" class="alert alert-emergency" style="margin-bottom:18px">
        <div class="alert-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="alert-content">
          <div class="alert-title">Continuity Plan Active — Critical Incident</div>
          <div>A critical incident is currently being managed by your stewards.</div>
        </div>
      </div>

      <!-- GREETING -->
      <div class="dh-greet">
        <div>
          <div class="dh-greet-eyebrow">Good morning</div>
          <div class="dh-greet-title">{{ greetLead }} <em>{{ greetLast }},</em></div>
          <div class="dh-greet-sub">Your continuity plan is active and your practice is on track. You have 3 pending referrals that need your attention.</div>
        </div>
        <div class="dh-greet-meta">
          <div class="dh-greet-mcell">
            <div class="dh-greet-mlabel">Plan Status</div>
            <div class="dh-greet-mval ok">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
              Active
            </div>
          </div>
          <div class="dh-greet-mcell">
            <div class="dh-greet-mlabel">Practices</div>
            <div class="dh-greet-mval">8 providers</div>
          </div>
          <div class="dh-greet-mcell">
            <div class="dh-greet-mlabel">Avg Response</div>
            <div class="dh-greet-mval">23h</div>
          </div>
        </div>
      </div>

      <!-- OVERVIEW BANNER -->
      <div class="overview-banner">
        <div class="overview-icon-block">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        </div>
        <div class="overview-text">
          <div class="overview-title">Overview — Start Here</div>
          <div class="overview-desc">Key terms, your role on Aegis, and FAQs.</div>
        </div>
        <Link href="/provider/overview" class="btn btn-outline btn-sm" style="flex-shrink:0;white-space:nowrap;">
          View Overview
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </Link>
      </div>

      <!-- FINISH YOUR PROFILE -->
      <div class="profile-strip">
        <div class="profile-strip-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
        </div>
        <div class="profile-strip-text">
          <div class="profile-strip-title">Finish your profile</div>
          <div class="profile-strip-sub">Add credentials &amp; availability to improve discovery</div>
        </div>
        <div class="profile-strip-bar"><div class="profile-strip-bar-fill" style="width:70%"></div></div>
        <div class="profile-strip-pct">70%</div>
        <Link href="/provider/profile" class="btn btn-primary btn-sm" style="flex-shrink:0;white-space:nowrap;">
          Complete
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </Link>
      </div>

      <!-- CONTINUITY SECTION HEADER -->
      <div class="dh-sh">
        <div class="dh-sh-l">
          <div class="dh-sh-eyebrow">Continuity Plan</div>
          <div class="dh-sh-title">Continuity at the center</div>
        </div>
        <Link class="dh-sh-link" href="/provider/continuity-stewards">
          Manage stewards
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </Link>
      </div>

      <!-- CONTINUITY HERO -->
      <div class="dh-continuity">
        <div class="dh-cn-left">
          <div class="dh-cn-eyebrow">
            <span class="dh-cn-pulse"></span>
            Continuity Plan &middot; Active since June 2024
          </div>
          <div class="dh-cn-title">Your practice continues,<br>even when you can't.</div>
          <div class="dh-cn-desc">When circumstances change, your Continuity Plan helps you keep care connected for your clients, records remain supported, and your stewards know what to do.</div>

          <div class="dh-cn-stewards">
            <div class="dh-cn-stew">
              <div class="dh-cn-savatar">MC</div>
              <div class="dh-cn-stew-info">
                <div class="dh-cn-srole">Continuity Steward</div>
                <div class="dh-cn-sname">Marcus Chen</div>
              </div>
              <div class="dh-cn-stat">Active</div>
            </div>
            <div class="dh-cn-stew">
              <div class="dh-cn-savatar support">LR</div>
              <div class="dh-cn-stew-info">
                <div class="dh-cn-srole">Support Steward</div>
                <div class="dh-cn-sname">Dr. Laura Reyes</div>
              </div>
              <div class="dh-cn-stat pending">Pending</div>
            </div>
          </div>

          <div class="dh-cn-actions">
            <button class="btn btn-primary btn-sm" @click="open('executorPanel')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              View Plan Details
            </button>
            <Link href="/provider/continuity-stewards" class="btn btn-outline btn-sm">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit Stewards
            </Link>
            <button class="btn btn-sm dh-cn-activate" title="Activate Continuity Support" @click="open('activateSuccession')">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </button>
          </div>
        </div>

        <div class="dh-cn-right">
          <div class="dh-cn-rhead">
            <div>
              <div class="dh-cn-rtag">Annual review</div>
              <div class="dh-cn-due">
                Due Jun 15, 2026
                <small>Overdue &middot; last attested Jun 15, 2024</small>
              </div>
            </div>
            <span style="color:var(--gold-dark);flex-shrink:0;display:inline-flex;align-items:center;line-height:0">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </span>
          </div>

          <div class="dh-cn-bar">
            <div class="dh-cn-bar-fill"></div>
            <div class="dh-cn-bar-marker"></div>
          </div>
          <div class="dh-cn-bar-labels">
            <span>Last attested</span>
            <span>Today</span>
            <span>Due</span>
          </div>

          <div class="dh-cn-todos">
            <div class="dh-cn-todo done">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              Steward contact info verified
            </div>
            <div class="dh-cn-todo done">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              Practice information current
            </div>
            <div class="dh-cn-todo">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>
              Confirm Support Steward task list
            </div>
            <div class="dh-cn-todo">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>
              Attest Continuity Plan accuracy
            </div>
          </div>

          <button class="btn btn-outline btn-sm" style="align-self:flex-start;margin-top:8px" @click="open('annualReview')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M9 14l2 2 4-4"/></svg>
            Begin Annual Review
          </button>
        </div>
      </div>

      <!-- AT-A-GLANCE SECTION HEADER -->
      <div class="dh-sh">
        <div class="dh-sh-l">
          <div class="dh-sh-eyebrow">Overview</div>
          <div class="dh-sh-title">Practice at a glance</div>
        </div>
        <Link class="dh-sh-link" href="/provider/activity">
          View activity
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </Link>
      </div>

      <!-- AT-A-GLANCE GRID -->
      <div class="dh-glance">
        <div class="dh-gl-card">
          <div class="dh-gl-head">
            <div class="dh-gl-label">Referrals</div>
            <div class="dh-gl-icon">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
            </div>
          </div>
          <div class="dh-gl-val">{{ referralStats.total }}</div>
          <div class="dh-gl-sub"><strong>{{ referralStats.pending }} pending</strong></div>
        </div>

        <div class="dh-gl-card">
          <div class="dh-gl-head">
            <div class="dh-gl-label">Network</div>
            <div class="dh-gl-icon">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
          </div>
          <div class="dh-gl-val">{{ networkCount }}</div>
          <div class="dh-gl-sub">8 clinical &middot; 8 business</div>
        </div>

        <div class="dh-gl-card">
          <div class="dh-gl-head">
            <div class="dh-gl-label">CEUs</div>
            <div class="dh-gl-icon warn">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
          </div>
          <div class="dh-gl-val">18<small> / 30 hrs</small></div>
          <div class="dh-gl-sub"><strong class="warn-text">12 hrs left</strong> by Dec 31, 2025</div>
        </div>

        <div class="dh-gl-card">
          <div class="dh-gl-head">
            <div class="dh-gl-label">Avg Response</div>
            <div class="dh-gl-icon">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
          </div>
          <div class="dh-gl-val">23<small>h</small></div>
          <div class="dh-gl-sub">average referral response</div>
        </div>
      </div>

      <!-- CREDENTIALS SECTION HEADER -->
      <div class="dh-sh">
        <div class="dh-sh-l">
          <div class="dh-sh-eyebrow">Compliance &amp; insurance</div>
          <div class="dh-sh-title">Credentials &amp; coverage</div>
        </div>
        <a class="dh-sh-link" href="#" @click.prevent="open('addLicense')">
          Manage all
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
      </div>

      <!-- CREDENTIALS + ATTENTION 2-COL -->
      <div class="dh-cols">
        <!-- LEFT: Credentials list (dynamic) -->
        <div class="dh-cred-card">
          <div v-for="cred in credentials" :key="cred.id" class="dh-cred-row">
            <div class="dh-cred-icon" :class="cred.severity">
              <svg v-if="cred.category === 'insurance'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            </div>
            <div class="dh-cred-info">
              <div class="dh-cred-title">{{ cred.name }}</div>
              <div class="dh-cred-sub">{{ cred.subtitle || cred.issuer || (cred.category === 'insurance' ? 'Insurance policy' : 'Credential') }}</div>
            </div>
            <div class="dh-cred-meter">
              <div class="dh-cred-date" :class="cred.severity">{{ cred.expires_at || 'No expiry' }}</div>
              <div class="dh-cred-bar"><div class="dh-cred-bar-fill" :class="cred.severity"></div></div>
              <div class="dh-cred-days" :class="cred.severity">{{ credDaysText(cred) }}</div>
            </div>
            <div class="dh-cred-act">
              <button v-if="cred.severity === 'crit'" class="btn-icon-sm btn-icon-danger" title="Update / renew" @click="openRenewCredential(cred)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg></button>
              <button class="btn-icon-sm" title="View details" @click="openCredentialDetail(cred)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            </div>
          </div>

          <div v-if="!credentials.length" class="dh-cred-empty">
            <div class="dh-cred-empty-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
            <div class="dh-cred-empty-title">No credentials tracked yet</div>
            <div class="dh-cred-empty-sub">Add your licenses and insurance policies to track renewals and expiry.</div>
            <button class="btn btn-primary btn-sm" @click="openAddCredential('license')">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add credential
            </button>
          </div>

          <div v-if="credentials.length" class="dh-cred-foot">
            <span><strong>{{ credentialStats.tracked }}</strong> credential{{ credentialStats.tracked === 1 ? '' : 's' }} tracked<template v-if="credentialStats.attention"> &middot; <span class="crit-text">{{ credentialStats.attention }} need attention</span></template></span>
            <button class="btn btn-outline btn-sm" @click="openAddCredential('license')">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add credential
            </button>
          </div>
        </div>

        <!-- RIGHT: Attention + Quick actions -->
        <div>
          <div class="dh-attention">
            <div class="dh-att-head">
              <div class="dh-att-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
              <div class="dh-att-title">Needs attention</div>
              <div class="dh-att-count">{{ attentionCredentials.length + 2 }}</div>
            </div>
            <div class="dh-att-list">
              <div v-for="item in attentionCredentials" :key="item.id" class="dh-att-item">
                <div class="dh-att-bullet" :class="item.severity"></div>
                <div class="dh-att-text">
                  <div class="dh-att-h">{{ item.name }} renewal</div>
                  <div class="dh-att-d">{{ attnExpiry(item) }}</div>
                </div>
                <button class="btn btn-sm" :class="item.severity === 'crit' ? 'btn-primary' : 'btn-outline'" @click="openRenewCredential(item)">Renew</button>
              </div>
              <div class="dh-att-item">
                <div class="dh-att-bullet warn"></div>
                <div class="dh-att-text">
                  <div class="dh-att-h">Annual Continuity Plan review</div>
                  <div class="dh-att-d">Due <strong>Jun 15</strong> &middot; attest 8 items</div>
                </div>
                <button class="btn btn-outline btn-sm" @click="open('annualReview')">Review</button>
              </div>
              <div class="dh-att-item">
                <div class="dh-att-bullet warn"></div>
                <div class="dh-att-text">
                  <div class="dh-att-h">12 CEU hours required</div>
                  <div class="dh-att-d">Ethics credits by <strong>Dec 31</strong></div>
                </div>
                <button class="btn btn-outline btn-sm" @click="open('ceu')">Add CEU</button>
              </div>
            </div>
          </div>

          <div class="dh-quick">
            <div class="dh-quick-title">Quick actions</div>
            <div class="dh-quick-grid">
              <button class="btn btn-outline btn-sm" @click="open('newReferral')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                New referral
              </button>
              <Link href="/provider/vault" class="btn btn-outline btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Upload doc
              </Link>
              <Link href="/provider/continuity-stewards" class="btn btn-outline btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Schedule
              </Link>
              <Link href="/provider/messages" class="btn btn-outline btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Message
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- INTEGRATIVE NETWORK SECTION HEADER -->
      <div class="dh-sh">
        <div class="dh-sh-l">
          <div class="dh-sh-eyebrow">My network</div>
          <div class="dh-sh-title">Integrative network</div>
        </div>
        <Link class="dh-sh-link" href="/provider/network">
          View all
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </Link>
      </div>

      <!-- NETWORK SECTION -->
      <div class="network-carousel-section">
        <div class="nw-head" style="border:none;padding:0;margin-bottom:14px">
          <div class="tabs-segmented">
            <button class="tab-pill" :class="{ active: activeNwTab === 'clinical' }" @click="activeNwTab = 'clinical'">Integrative</button>
            <button class="tab-pill" :class="{ active: activeNwTab === 'business' }" @click="activeNwTab = 'business'">Business Partners</button>
          </div>
        </div>

        <div class="nw-grid">
          <!-- Clinical -->
          <template v-if="activeNwTab === 'clinical'">
            <div v-for="card in clinicalCards" :key="card.slug" class="nw-card" @click="go('/provider/network')">
              <div class="nw-top">
                <div class="nw-avatar">{{ card.initials }}</div>
                <div class="nw-info">
                  <div class="nw-name">{{ card.name }}</div>
                  <div class="nw-role">{{ card.role }}</div>
                </div>
                <div class="nw-tags">
                  <div class="nw-pill net" title="In Network"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg></div>
                  <div v-if="card.svc" class="nw-pill svc" title="Offers services on Aegis"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div>
                </div>
              </div>
              <div class="nw-meta">
                <span class="nw-meta-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> {{ card.location }}</span>
                <span class="nw-meta-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Replies in {{ card.response }}</span>
                <div class="nw-cta" @click.stop>
                  <button class="nw-btn" title="Send message" @click="go('/provider/messages')"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></button>
                  <button class="nw-btn primary" title="View profile" @click="go('/provider/network')"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></button>
                </div>
              </div>
            </div>
          </template>

          <!-- Business Partners -->
          <template v-else>
            <div v-for="card in businessCards" :key="card.slug" class="nw-card" @click="go('/provider/network')">
              <div class="nw-top">
                <div class="nw-avatar">{{ card.initials }}</div>
                <div class="nw-info">
                  <div class="nw-name">{{ card.name }}</div>
                  <div class="nw-role">{{ card.role }}</div>
                </div>
                <div class="nw-tags">
                  <span class="nw-tag">{{ card.tags }}</span>
                </div>
              </div>
              <div class="nw-meta">
                <span class="nw-meta-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> {{ card.location }}</span>
                <span class="nw-meta-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Replies in —</span>
                <div class="nw-cta" @click.stop>
                  <button class="nw-btn" title="Send message" @click="go('/provider/messages')"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></button>
                  <button class="nw-btn primary" title="View profile" @click="go('/provider/network')"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></button>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>

    </div>

    <!-- ═══════════════ MODALS ═══════════════ -->

    <!-- CONTINUITY PLAN — DETAILS -->
    <Modal :model-value="modal === 'executorPanel'" @update:model-value="closeModal" size="lg" title="Continuity Plan — Details">
      <div class="alert alert-success" style="margin-bottom:14px"><div class="alert-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg></div><div class="alert-content">Continuity Plan is active and up to date</div></div>

      <div class="section-label" style="margin-bottom:8px">Stewards</div>
      <div class="grid-2" style="gap:10px;margin-bottom:14px">
        <div class="exec-person-row" style="flex-direction:column;align-items:flex-start;gap:6px">
          <div style="display:flex;align-items:center;gap:10px;width:100%;flex-wrap:wrap">
            <div class="exec-avatar">MC</div>
            <div style="flex:1;min-width:0">
              <div style="font-weight:700;font-size:13px">Marcus Chen</div>
              <div style="font-size:11px;color:var(--text-3)">Principal, Chen Practice Solutions</div>
            </div>
            <span class="exec-role-chip">Continuity Steward</span>
            <span class="badge badge-green">Active</span>
          </div>
          <div style="font-size:11px;color:var(--text-3);width:100%">marcus@chenpracticesolutions.com · (510) 555-0189</div>
        </div>
        <div class="exec-person-row" style="flex-direction:column;align-items:flex-start;gap:6px">
          <div style="display:flex;align-items:center;gap:10px;width:100%;flex-wrap:wrap">
            <div class="exec-avatar">LR</div>
            <div style="flex:1;min-width:0">
              <div style="font-weight:700;font-size:13px">Dr. Laura Reyes</div>
              <div style="font-size:11px;color:var(--text-3)">Principal, Reyes Continuity</div>
            </div>
            <span class="exec-role-chip">Support Steward</span>
            <span class="badge badge-green">Monitoring</span>
          </div>
          <div style="font-size:11px;color:var(--text-3);width:100%">laura.reyes@reyescontinuity.net · (213) 555-0298</div>
        </div>
      </div>

      <div class="section-label" style="margin-bottom:8px">Continuity Plan Details</div>
      <div class="grid-2" style="gap:10px 14px;margin-bottom:0">
        <div class="cc-detail-row"><span class="cc-detail-label">Signed</span><span class="cc-detail-value">Jun 15, 2024</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Annual Review</span><span class="cc-detail-value" style="color:var(--orange-dark)">Due Jun 15, 2026</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Activation Trigger</span><span class="cc-detail-value">48-hr Absence</span></div>
        <div class="form-group" style="margin-bottom:0;display:flex;align-items:center;gap:10px">
          <label class="form-label" style="margin-bottom:0;flex-shrink:0;white-space:nowrap">Security Doc</label>
          <select class="form-select" style="flex:1;min-width:0">
            <option>None</option><option>Police Report</option><option>Doctor's Note</option><option>Death Certificate</option>
          </select>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" style="margin-right:auto" @click="open('annualReview')">Annual Review</button>
        <button class="btn btn-danger" @click="open('activateSuccession')">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Activate Continuity Support
        </button>
        <Link href="/provider/continuity-stewards" class="btn btn-primary">Manage Continuity Stewards <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></Link>
      </template>
    </Modal>

    <!-- ACTIVATE SUCCESSION -->
    <Modal :model-value="modal === 'activateSuccession'" @update:model-value="closeModal" size="lg"
      title="Activate Continuity Support" :header-style="{ background: 'var(--red-light)', borderBottom: '1px solid var(--red-light)' }" :title-style="{ color: 'var(--red)' }">
      <div style="background:var(--red-light);border:1px solid var(--border-dark);border-radius:var(--radius-lg);padding:11px 14px;margin-bottom:14px;font-size:13px;color:var(--text-2);line-height:1.55;">
        <strong style="color:var(--red);">This notifies your Continuity &amp; Support Stewards</strong> and initiates your Continuity Plan. Activate only during a genuine critical moment.
      </div>
      <div class="row-2" style="margin-bottom:12px">
        <div class="form-group">
          <label class="form-label">Incident Type <span class="req">*</span></label>
          <select class="form-select" v-model="succession.reason">
            <option value="">— Select reason —</option>
            <option value="death">Death</option>
            <option value="missing">Missing Person</option>
            <option value="incapacitation-short">Short-Term Incapacitation</option>
            <option value="incapacitation-long">Long-Term Incapacitation</option>
            <option value="natural-disaster">Natural Disaster</option>
            <option value="detainment">Detainment</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Supporting Documentation</label>
          <select class="form-select" v-model="succession.doc">
            <option value="none">None</option><option value="police-report">Police Report</option><option value="doctors-note">Doctor's Note</option><option value="death-certificate">Death Certificate</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Additional Notes (optional)</label>
        <textarea class="form-textarea" v-model="succession.notes" rows="2" placeholder="Provide any relevant context for your Continuity Steward…"></textarea>
      </div>
      <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:10px 14px;font-size:13px;color:var(--text-3);line-height:1.5;">
        <strong>Next:</strong> Marcus Chen &amp; Dr. Laura Reyes will be notified. Vault access opens per your Continuity Plan permissions.
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal">Cancel</button>
        <button class="btn btn-sm" style="background:var(--red);color:var(--text-inverted);border:none;display:inline-flex;align-items:center;gap:6px;" @click="confirmActivateSuccession">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Confirm Activation
        </button>
      </template>
    </Modal>

    <!-- ANNUAL REVIEW -->
    <Modal :model-value="modal === 'annualReview'" @update:model-value="closeModal" size="lg" title="Annual Continuity Plan Review">
      <div class="alert alert-warning" style="margin-bottom:18px;display:flex;align-items:center;gap:8px;"><span style="flex-shrink:0;display:inline-flex;align-items:center;line-height:0"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span> Annual review due June 15, 2025. Please verify all items and attest.</div>
      <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:14px">Please confirm the following items are current and accurate:</div>
      <div v-for="(item, i) in reviewItems" :key="i" class="review-item">
        <input type="checkbox" class="review-check" v-model="reviewChecks[i]" :id="'rv' + i">
        <div><label :for="'rv' + i" style="font-size:13px;font-weight:600;cursor:pointer">{{ item.label }}</label><div v-if="item.sub" style="font-size:12px;color:var(--text-3);margin-top:2px">{{ item.sub }}</div></div>
      </div>
      <div style="margin-top:18px">
        <label class="form-label">Additional Notes</label>
        <textarea class="form-textarea" v-model="reviewNotes" placeholder="Any changes or notes for this review period..."></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal">Cancel</button>
        <button class="btn btn-primary" @click="attestReview">Attest &amp; Submit Review</button>
      </template>
    </Modal>

    <!-- CEU -->
    <Modal :model-value="modal === 'ceu'" @update:model-value="closeModal" title="Continuing Education (CEU)">
      <div style="background:var(--surface-2);border-radius:var(--radius);padding:14px;margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
          <span style="font-size:13px;font-weight:700">2025 CEU Progress</span>
          <span style="font-size:13px;font-weight:700;color:var(--orange)">18 / 30 hrs</span>
        </div>
        <div class="progress-bar"><div class="progress-fill" style="width:60%;background:var(--orange)"></div></div>
        <div style="font-size:11px;color:var(--text-3);margin-top:5px">12 more hours needed · Deadline Dec 31, 2025</div>
      </div>

      <div style="font-size:13px;font-weight:700;margin-bottom:10px">CEU Requirements</div>
      <div style="font-size:11px;color:var(--text-3);margin-bottom:12px">Define the requirements you track against — jurisdiction, hours, cycle, and required types.</div>
      <div class="ceu-item" style="border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;margin-bottom:10px">
        <div>
          <div style="font-size:13px;font-weight:700">California — BBS (LMFT)</div>
          <div style="font-size:11px;color:var(--text-3);margin-top:2px">36 hours · Biannual · Due Dec 31, 2025</div>
          <div style="font-size:11px;color:var(--text-3);margin-top:2px">Required: Ethics (6), Law &amp; Ethics, Suicide Risk Assessment</div>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
          <button class="btn-icon" title="Edit requirement" @click="showToast('Edit requirement','info')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg></button>
          <button class="btn-icon btn-icon-danger" title="Remove" @click="removeCeuReq"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
        </div>
      </div>

      <div style="font-size:13px;font-weight:700;margin:18px 0 12px">Completed CEUs</div>
      <div class="ceu-item">
        <div><div style="font-size:13px;font-weight:600">Ethics in Mental Health Practice</div><div style="font-size:11px;color:var(--text-3)">Jan 15, 2025 · 3 hours · Synchronous</div></div>
        <span class="badge badge-green"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Verified</span>
      </div>
      <div class="ceu-item">
        <div><div style="font-size:13px;font-weight:600">Telehealth Best Practices</div><div style="font-size:11px;color:var(--text-3)">Nov 20, 2024 · 6 hours · Asynchronous</div></div>
        <span class="badge badge-green"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Verified</span>
      </div>

      <div style="margin-top:22px">
        <div style="font-size:13px;font-weight:700;margin-bottom:14px">Add CEU Credits</div>
        <div class="form-group">
          <label class="form-label">Course Name <span class="req">*</span></label>
          <input class="form-input" type="text" placeholder="e.g., Ethics in Telehealth 2025">
        </div>
        <div class="form-group">
          <label class="form-label">CEU Category <span class="req">*</span></label>
          <select class="form-select">
            <option value="">Select a category...</option>
            <option>Ethics</option><option>Supervision</option><option>Telehealth</option><option>Safety</option>
            <option>Cultural Competency</option><option>Suicide</option><option>General CEUs</option><option>Other</option>
          </select>
        </div>
        <div class="row-2">
          <div class="form-group">
            <label class="form-label">Date Completed</label>
            <input class="form-input" type="date">
          </div>
          <div class="form-group">
            <label class="form-label">Credit Hours <span class="req">*</span></label>
            <input class="form-input" type="number" min="1" max="40" placeholder="e.g., 3">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Upload Certificate</label>
          <DropZone v-model="dz.ceu" />
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal">Close</button>
        <button class="btn btn-primary" @click="showToast('CEU credits added!','success'); closeModal()">Add CEU Credits</button>
      </template>
    </Modal>

    <!-- ADD CREDENTIAL (license or insurance) -->
    <Modal :model-value="modal === 'addLicense'" @update:model-value="closeModal" :title="isInsuranceAdd ? 'Add Insurance Policy' : 'Add Credential'">
      <div class="form-group">
        <label class="form-label">Category <span class="req">*</span></label>
        <select class="form-select" v-model="credForm.category">
          <option value="license">License / Certification</option>
          <option value="insurance">Insurance Policy</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">{{ isInsuranceAdd ? 'Policy Type' : 'Credential Type' }} <span class="req">*</span></label>
        <select v-if="!isInsuranceAdd" class="form-select" v-model="credForm.cred_type">
          <option value="">Select a credential...</option>
          <optgroup label="Medical &amp; Prescribing"><option>MD</option><option>DO</option><option>ND</option><option>NP</option><option>PA</option></optgroup>
          <optgroup label="Mental Health"><option>LPC / LPCC</option><option>LCSW / LICSW</option><option>LMFT</option><option>ABPP</option></optgroup>
          <optgroup label="Therapy &amp; Specialty"><option>EMDR Certified</option><option>DBT Certified</option><option>CSE</option><option>CSC</option><option>CST</option></optgroup>
          <optgroup label="Addiction &amp; Behavioral"><option>CADC / ICADC</option></optgroup>
          <optgroup label="Nutrition &amp; Health"><option>RD / RDN</option><option>NBC-HWC</option></optgroup>
          <optgroup label="Other"><option>Medical / Clinical License</option><option>State License</option><option>DEA Registration</option><option>Licensed</option></optgroup>
        </select>
        <select v-else class="form-select" v-model="credForm.cred_type">
          <option value="">Select a policy type...</option>
          <option>Professional Liability (Malpractice)</option>
          <option>General Business Insurance</option>
          <option>Workers Compensation</option>
          <option>Cyber Liability</option>
          <option>Life Insurance</option>
          <option>Other</option>
        </select>
        <div v-if="credForm.errors.cred_type" class="form-error visible"><svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>{{ credForm.errors.cred_type }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Name / Title <span class="optional">(optional)</span></label>
        <input class="form-input" type="text" v-model="credForm.name" :placeholder="isInsuranceAdd ? 'e.g., Professional Liability' : 'e.g., Medical / Clinical License'">
      </div>

      <!-- License-specific -->
      <template v-if="!isInsuranceAdd">
        <div class="grid-2">
          <div class="form-group"><label class="form-label">License Number</label><input class="form-input" type="text" v-model="credForm.license_number" placeholder="e.g., NY-MD-12345"></div>
          <div class="form-group"><label class="form-label">Credential Number</label><input class="form-input" type="text" v-model="credForm.credential_number" placeholder="Optional"></div>
        </div>
        <div class="grid-2">
          <div class="form-group"><label class="form-label">Issuing State / Body</label><input class="form-input" type="text" v-model="credForm.issuer" placeholder="e.g., New York"></div>
          <div class="form-group"><label class="form-label">Issue Date</label><input class="form-input" type="date" v-model="credForm.issue_date"></div>
        </div>
      </template>
      <!-- Insurance-specific -->
      <template v-else>
        <div class="grid-2">
          <div class="form-group"><label class="form-label">Carrier / Provider</label><input class="form-input" type="text" v-model="credForm.issuer" placeholder="e.g., Medical Protective"></div>
          <div class="form-group"><label class="form-label">Policy Number</label><input class="form-input" type="text" v-model="credForm.policy_number" placeholder="e.g., POL-2024-XXXXX"></div>
        </div>
        <div class="grid-2">
          <div class="form-group"><label class="form-label">Coverage Amount</label><input class="form-input" type="text" v-model="credForm.coverage_amount" placeholder="e.g., $2,000,000"></div>
          <div class="form-group"><label class="form-label">Annual Premium</label><input class="form-input" type="text" v-model="credForm.annual_premium" placeholder="e.g., $4,200/year"></div>
        </div>
        <div class="form-group"><label class="form-label">Effective Date</label><input class="form-input" type="date" v-model="credForm.issue_date"></div>
      </template>

      <div class="form-group">
        <label class="form-label">Expiration Date</label>
        <input class="form-input" type="date" v-model="credForm.expires_at">
        <div v-if="credForm.errors.expires_at" class="form-error visible"><svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>{{ credForm.errors.expires_at }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Upload Document</label>
        <label class="dropzone is-compact">
          <div class="dz-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div>
          <div>
            <div class="dz-text"><strong>Click to upload</strong> or drag &amp; drop</div>
            <div class="dz-sub">PDF, JPG, PNG · up to 10MB</div>
            <div class="dz-filename" v-show="credForm.document"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><span>{{ credForm.document && credForm.document.name }}</span></div>
          </div>
          <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="onCredFile" style="display:none">
        </label>
        <div v-if="credForm.errors.document" class="form-error visible"><svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>{{ credForm.errors.document }}</div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal">Cancel</button>
        <button class="btn btn-primary" :disabled="credForm.processing" @click="submitAddCredential">{{ credForm.processing ? 'Saving…' : (isInsuranceAdd ? 'Add Policy' : 'Add Credential') }}</button>
      </template>
    </Modal>

    <!-- UPDATE / RENEW CREDENTIAL (shared by license + insurance) -->
    <Modal :model-value="modal === 'renewLicense' || modal === 'renewInsurance'" @update:model-value="closeModal" :title="selectedCred && selectedCred.category === 'insurance' ? 'Update Insurance Policy' : 'Update Credential'">
      <div v-if="selectedCred" class="alert alert-warning" style="margin-bottom:16px;display:flex;align-items:center;gap:8px;"><span style="flex-shrink:0;display:inline-flex;align-items:center;line-height:0"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span> {{ selectedCred.name }}{{ selectedCred.expires_at ? ' expires ' + selectedCred.expires_at : '' }}</div>
      <div class="grid-2">
        <div class="form-group"><label class="form-label">New Expiration Date</label><input class="form-input" type="date" v-model="updateForm.expires_at"></div>
        <div class="form-group"><label class="form-label">Confirmation / Reference #</label><input class="form-input" type="text" v-model="updateForm.reference_number" placeholder="e.g., RENEW-2025-XXXXX"></div>
      </div>
      <div v-if="selectedCred && selectedCred.category === 'insurance'" class="grid-2">
        <div class="form-group"><label class="form-label">Policy Number</label><input class="form-input" type="text" v-model="updateForm.policy_number" placeholder="If changed"></div>
        <div class="form-group"><label class="form-label">Coverage Amount</label><input class="form-input" type="text" v-model="updateForm.coverage_amount" placeholder="e.g., $2M / $4M"></div>
      </div>
      <div class="form-group">
        <label class="form-label">Upload Updated Document</label>
        <label class="dropzone is-compact">
          <div class="dz-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div>
          <div>
            <div class="dz-text"><strong>Click to upload</strong> or drag &amp; drop</div>
            <div class="dz-sub">PDF, JPG, PNG · up to 10MB</div>
            <div class="dz-filename" v-show="updateForm.document"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><span>{{ updateForm.document && updateForm.document.name }}</span></div>
          </div>
          <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="onUpdateFile" style="display:none">
        </label>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal">Cancel</button>
        <button class="btn btn-primary" :disabled="updateForm.processing" @click="submitRenewCredential">{{ updateForm.processing ? 'Saving…' : 'Save Update' }}</button>
      </template>
    </Modal>

    <!-- CREDENTIAL DETAIL (shared by license + insurance) -->
    <Modal :model-value="modal === 'licenseDetail' || modal === 'insuranceDetail'" @update:model-value="closeModal" :title="selectedCred && selectedCred.category === 'insurance' ? 'Insurance Policy Details' : 'Credential Details'">
      <div v-if="selectedCred" class="grid-2">
        <div class="cc-detail-row"><span class="cc-detail-label">Type</span><span class="cc-detail-value">{{ selectedCred.cred_type || selectedCred.name }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Name</span><span class="cc-detail-value">{{ selectedCred.name }}</span></div>
        <div v-if="selectedCred.category === 'insurance'" class="cc-detail-row"><span class="cc-detail-label">Carrier</span><span class="cc-detail-value">{{ selectedCred.issuer || '—' }}</span></div>
        <div v-else class="cc-detail-row"><span class="cc-detail-label">Issuer</span><span class="cc-detail-value">{{ selectedCred.issuer || '—' }}</span></div>
        <div v-if="selectedCred.category === 'insurance'" class="cc-detail-row"><span class="cc-detail-label">Policy #</span><span class="cc-detail-value">{{ selectedCred.policy_number || '—' }}</span></div>
        <div v-else class="cc-detail-row"><span class="cc-detail-label">License #</span><span class="cc-detail-value">{{ selectedCred.license_number || '—' }}</span></div>
        <div v-if="selectedCred.category === 'insurance'" class="cc-detail-row"><span class="cc-detail-label">Coverage</span><span class="cc-detail-value">{{ selectedCred.coverage_amount || '—' }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Status</span><span class="cc-detail-value" :style="{ color: selectedCred.severity === 'crit' ? 'var(--red)' : 'var(--green)' }">● {{ selectedCred.severity === 'crit' ? 'Action needed' : 'Active' }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Issued</span><span class="cc-detail-value">{{ selectedCred.issue_date || '—' }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Expires</span><span class="cc-detail-value">{{ selectedCred.expires_at || 'No expiry' }}</span></div>
        <div class="cc-detail-row" v-if="selectedCred.has_document"><span class="cc-detail-label">Document</span><a class="cc-detail-value" :href="`/provider/credentials/${selectedCred.id}/document`" style="color:var(--gold-dark)">{{ selectedCred.document_name || 'Download' }}</a></div>
      </div>
      <template #footer>
        <button class="btn btn-danger" style="margin-right:auto" @click="deleteCredential(selectedCred)">Remove</button>
        <button class="btn btn-outline" @click="closeModal">Close</button>
        <button class="btn btn-primary" @click="openRenewCredential(selectedCred)">Update Credential</button>
      </template>
    </Modal>

    <!-- SET REMINDER -->
    <Modal :model-value="modal === 'setReminder'" @update:model-value="closeModal" size="sm" title="Set Reminder">
      <div class="form-group">
        <label class="form-label">Item</label>
        <select class="form-select">
          <option>Medical License (NY) — Expires Jun 30, 2026</option>
          <option>State License (CA) — Expires Feb 28, 2025</option>
          <option>DEA Registration — Expires Dec 31, 2026</option>
          <option>Professional Liability — Expires Mar 15, 2025</option>
          <option>General Business — Expires Feb 28, 2026</option>
        </select>
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label class="form-label">Remind Me</label>
          <select class="form-select"><option>30 days before</option><option>60 days before</option><option selected>90 days before</option><option>120 days before</option><option>180 days before</option></select>
        </div>
        <div class="form-group">
          <label class="form-label">Repeat</label>
          <select class="form-select"><option selected>One-time</option><option>Weekly until acted on</option><option>Daily in final 7 days</option></select>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal">Cancel</button>
        <button class="btn btn-primary" @click="showToast('Reminder set','success'); closeModal()">Set Reminder</button>
      </template>
    </Modal>

    <!-- NEW REFERRAL (4-step wizard) -->
    <Modal :model-value="modal === 'newReferral'" @update:model-value="closeReferral" size="lg"
      title="Send Client Referral" subtitle="Refer a client to another practitioner in your network">
      <div class="modal-steps">
        <template v-for="s in 4" :key="s">
          <div class="modal-step" :class="{ active: nrf.step === s, done: nrf.step > s }" @click="nrfGoStep(s)">
            <div class="modal-step-num">{{ s }}</div>{{ ['Client','Practitioner','Notes','Review'][s-1] }}
          </div>
          <div v-if="s < 4" class="modal-step-divider"></div>
        </template>
      </div>

      <!-- STEP 1 -->
      <div v-show="nrf.step === 1">
        <div class="nrf-src-chooser">
          <div class="nrf-src-option" :class="{ selected: nrf.source === 'roster' }" @click="nrf.source = 'roster'">
            <div class="nrf-src-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
            <div class="nrf-src-title">Pull from Client Roster</div>
            <div class="nrf-src-sub">5 clients in your practice records</div>
          </div>
          <div class="nrf-src-option" :class="{ selected: nrf.source === 'manual' }" @click="nrf.source = 'manual'">
            <div class="nrf-src-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg></div>
            <div class="nrf-src-title">Enter Manually</div>
            <div class="nrf-src-sub">Client not in roster — enter anonymized identifier</div>
          </div>
        </div>

        <div v-show="nrf.source === 'roster'">
          <div class="form-group">
            <label class="form-label">Search Roster</label>
            <input class="form-input" type="text" v-model="nrf.search" placeholder="Type name, service, or location…" autocomplete="off">
          </div>
          <div class="nrf-roster-list">
            <div v-for="(c, i) in filteredRoster" :key="i" class="nrf-pt-row" :class="{ selected: nrf.selected === c.name }" @click="nrfSelectPatient(c)">
              <div class="nrf-pt-avatar">{{ c.av }}</div>
              <div class="nrf-pt-info">
                <div class="nrf-pt-name">{{ c.name }}</div>
                <div class="nrf-pt-meta">{{ c.meta }}</div>
                <div style="font-size:11px;color:var(--text-4);margin-top:2px;line-height:1.4">{{ c.notes }}</div>
              </div>
              <span class="nrf-pt-tag" :class="c.priority ? 'nrf-pt-tag-priority' : 'nrf-pt-tag-active'"><svg width="9" height="9" viewBox="0 0 24 24" fill="currentColor" stroke="none"><circle cx="12" cy="12" r="6"/></svg>{{ c.priority ? 'Priority' : 'Active' }}</span>
            </div>
          </div>
          <div v-if="!filteredRoster.length" class="nrf-empty" style="display:block">
            No matching clients. Try a different search, or use <a href="#" @click.prevent="nrf.source = 'manual'" class="nrf-link">manual entry</a>.
          </div>
          <div v-if="nrf.selected && nrf.source === 'roster'" class="nrf-selected">
            <div class="nrf-selected-title"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Client selected — fields pre-filled from roster</div>
            <div class="nrf-selected-body">{{ nrf.selected }} — {{ nrf.selectedMeta }}</div>
            <button @click="nrf.selected = ''; nrf.selectedMeta = ''" class="nrf-selected-clear" type="button">× Clear selection</button>
          </div>
        </div>

        <div v-show="nrf.source === 'manual'">
          <div class="nrf-hipaa-note"><strong>HIPAA reminder:</strong> use anonymized identifiers only (e.g. initials and age range). Do not include full names or DOBs.</div>
          <div class="form-group"><label class="form-label">Client Identifier <span class="req">*</span></label><input class="form-input" type="text" v-model="nrf.manualId" placeholder="e.g. A.M. — Generalized Anxiety"></div>
          <div class="form-group"><label class="form-label">Diagnosis / Reason for Care</label><input class="form-input" type="text" v-model="nrf.manualDx" placeholder="e.g. PTSD, Depression, Medication management"></div>
        </div>
      </div>

      <!-- STEP 2 -->
      <div v-show="nrf.step === 2">
        <div class="form-group" style="margin-bottom:16px">
          <label class="form-label">Referring To <span class="req">*</span></label>
          <select class="form-select" v-model="nrf.provider">
            <option value="">Select a provider in your network…</option>
            <option v-for="p in providers" :key="p" :value="p">{{ p }}</option>
          </select>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Specialty Needed</label>
            <select class="form-select" v-model="nrf.specialty">
              <option value="">Any specialty…</option>
              <option>Psychiatrist</option><option>Psychologist</option><option>Therapist / Counselor</option><option>Neurologist</option><option>Primary Care</option><option>Addiction Specialist</option><option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Coverage</label>
            <select class="form-select" v-model="nrf.coverage">
              <option value="">Select coverage…</option>
              <option>Self-pay</option><option>Blue Cross Blue Shield</option><option>Aetna</option><option>Cigna</option><option>UnitedHealthcare</option><option>Medicare</option><option>Medicaid</option><option value="other">Other (specify)</option>
            </select>
            <input v-show="nrf.coverage === 'other'" type="text" v-model="nrf.coverageOther" class="form-input" placeholder="Specify coverage or plan…" style="margin-top:8px">
          </div>
        </div>
      </div>

      <!-- STEP 3 -->
      <div v-show="nrf.step === 3">
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Reason for Referral <span class="req">*</span></label>
            <select class="form-select" v-model="nrf.reason">
              <option value="">Select reason…</option>
              <option>Specialist consultation</option><option>Medication management</option><option>Ongoing care continuity</option><option>Crisis intervention needed</option><option>Critical incident practice closure</option><option>Client preference</option><option value="other">Other (specify)</option>
            </select>
            <input v-show="nrf.reason === 'other'" type="text" v-model="nrf.reasonOther" class="form-input" placeholder="Specify reason…" style="margin-top:8px">
          </div>
          <div class="form-group">
            <label class="form-label">Urgency Level</label>
            <select class="form-select" v-model="nrf.urgency">
              <option value="routine">Routine (within 30 days)</option><option value="soon">Soon (48–72 hours)</option><option value="urgent">Urgent (within 24 hours)</option><option value="critical">Critical (immediate)</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Clinical Notes (optional)</label>
          <textarea class="form-textarea" v-model="nrf.notes" rows="3" placeholder="Relevant clinical context — diagnosis, medications, safety plan, next-step coordination…"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Attach Documents (optional)</label>
          <div class="nrf-dropzone" @click="$refs.nrfFile.click()">
            <input type="file" ref="nrfFile" multiple style="display:none" @change="nrfAttach($event)">
            <span class="nrf-dropzone-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></span>
            Click to attach clinical notes, safety plan, or release authorization
          </div>
          <div class="nrf-attach-list">{{ nrf.attachNames }}</div>
        </div>
      </div>

      <!-- STEP 4 -->
      <div v-show="nrf.step === 4">
        <div class="nrf-review">
          <div class="nrf-review-section"><div class="nrf-review-label">Client</div><div class="nrf-review-value">{{ reviewClient }}</div></div>
          <div class="nrf-review-section"><div class="nrf-review-label">Referring To</div><div class="nrf-review-value">{{ nrf.provider || '—' }}</div></div>
          <div class="nrf-review-section"><div class="nrf-review-label">Specialty</div><div class="nrf-review-value">{{ nrf.specialty || '—' }}</div></div>
          <div class="nrf-review-section"><div class="nrf-review-label">Reason</div><div class="nrf-review-value">{{ reviewReason }}</div></div>
          <div class="nrf-review-section"><div class="nrf-review-label">Urgency</div><div class="nrf-review-value">{{ nrf.urgency }}</div></div>
          <div class="nrf-review-section"><div class="nrf-review-label">Coverage</div><div class="nrf-review-value">{{ reviewCoverage }}</div></div>
          <div class="nrf-review-section nrf-review-section-full"><div class="nrf-review-label">Clinical Notes</div><div class="nrf-review-value">{{ nrf.notes || '—' }}</div></div>
        </div>
        <div class="nrf-hipaa-ack">
          <input type="checkbox" id="nrfHipaaAck" class="styled-chk" v-model="nrf.hipaa">
          <label for="nrfHipaaAck">I confirm this referral is HIPAA-compliant and the client has been notified per the Continuity Plan.</label>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-outline" @click="closeReferral">Cancel</button>
        <button class="btn btn-outline" @click="showToast('Draft saved','info')">Save as Draft</button>
        <div style="flex:1"></div>
        <button v-show="nrf.step > 1" class="btn btn-outline" @click="nrfGoStep(nrf.step - 1)">Back</button>
        <button v-show="nrf.step < 4" class="btn btn-primary" @click="nrfNext">Next</button>
        <button v-show="nrf.step === 4" class="btn btn-primary" @click="nrfSubmit">Send Referral</button>
      </template>
    </Modal>

    <!-- TOASTS -->
    <Teleport to="body">
      <div class="dh-toast-stack">
        <div v-for="t in toasts" :key="t.id" class="dh-toast" :class="t.type">
          <svg v-if="t.type === 'success'" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          <span>{{ t.msg }}</span>
        </div>
      </div>
    </Teleport>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router, useForm, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '../../Components/AppLayout.vue';
import Modal from '../../Components/Modal.vue';

const props = defineProps({
  user: { type: Object, default: () => ({ display_name: 'Dr. Sarah Johnson' }) },
  hasEmergency: { type: Boolean, default: false },
  credentials: { type: Array, default: () => [] },
  attentionCredentials: { type: Array, default: () => [] },
  credentialStats: { type: Object, default: () => ({ tracked: 0, attention: 0 }) },
  referralStats: { type: Object, default: () => ({ pending: 0, total: 0 }) },
  networkCount: { type: Number, default: 0 },
});

// ── Greeting (dynamic from the authenticated user) ──
const fullName = computed(() => (props.user?.display_name || 'Dr. Sarah Johnson').trim());
// "Dr. Sarah Johnson," → lead "Dr. Sarah" + italic last word "Johnson"
const greetLead = computed(() => { const p = fullName.value.split(/\s+/); return p.length > 1 ? p.slice(0, -1).join(' ') : p[0]; });
const greetLast = computed(() => { const p = fullName.value.split(/\s+/); return p.length > 1 ? p[p.length - 1] : ''; });

const activeNwTab = ref('clinical');

// ── Overview tabs → continuity setup pages (SPA navigation) ──
const overviewTabs = [
  { title: 'Profile', sub: 'Credentials & contact', href: '/provider/profile' },
  { title: 'Continuity Steward', sub: 'Licensed successor', href: '/provider/continuity-stewards' },
  { title: 'Support Steward', sub: 'Personal contact', href: '/provider/support-stewards' },
  { title: 'Continuity Plan', sub: 'Your master document', href: '/provider/continuity-plan' },
  { title: 'Document Vault', sub: 'Sensitive records', href: '/provider/vault' },
];

// ── Flash → toast ──
const page = usePage();
watch(() => page.props.flash, (flash) => {
  if (flash && flash.message) showToast(flash.message, flash.type || 'info');
}, { deep: true });

/* ── Network cards ── */
const clinicalCards = ref([
  { slug: 'dr-david-malik', initials: 'DM', name: 'Dr. David Malik', role: 'Psychiatrist', location: 'Brooklyn, NY', response: '2h', svc: true },
  { slug: 'dr-rachel-moore', initials: 'RM', name: 'Dr. Rachel Moore', role: 'Psychiatrist', location: 'Brooklyn, NY', response: '2h', svc: false },
  { slug: 'dr-lisa-chen', initials: 'LC', name: 'Dr. Lisa Chen', role: 'Psychologist', location: 'Manhattan, NY', response: '3h', svc: true },
  { slug: 'dr-sarah-nguyen', initials: 'SN', name: 'Dr. Sarah Nguyen', role: 'Psychologist', location: 'Manhattan, NY', response: '3h', svc: true },
  { slug: 'dr-patricia-monroe', initials: 'PM', name: 'Dr. Patricia Monroe', role: 'Child Psychiatrist', location: 'Manhattan, NY', response: '2h', svc: false },
  { slug: 'dr-anna-thompson', initials: 'AT', name: 'Dr. Anna Thompson', role: 'Child & Adolescent Psychiatrist', location: 'Manhattan, NY', response: '3h', svc: false },
]);
const businessCards = ref([
  { slug: 'karen-liu-esq', initials: 'KL', name: 'Karen Liu, Esq.', role: 'Freelancer', tags: 'legal, compliance, contracts', location: 'Manhattan, NY' },
  { slug: 'bill-garrett-cpa', initials: 'BG', name: 'Bill Garrett', role: 'Freelancer', tags: 'accounting, tax, bookkeeping', location: 'Manhattan, NY' },
  { slug: 'bright-practice-marketing', initials: 'BP', name: 'Bright Practice Marketing', role: 'Agency', tags: 'marketing', location: 'Brooklyn, NY' },
  { slug: 'medbill-solutions', initials: 'MS', name: 'MedBill Pro', role: 'Agency', tags: 'billing, claims, revenue-cycle', location: 'Remote / Manhattan, NY' },
]);

function go(href) { router.visit(href); }

/* ── Modal control ── */
const modal = ref(null);
function open(name) { modal.value = name; }
function closeModal() { modal.value = null; }

/* ── Toasts ── */
const toasts = ref([]);
let toastId = 0;
function showToast(msg, type = 'info') {
  const id = ++toastId;
  toasts.value.push({ id, msg, type });
  setTimeout(() => { toasts.value = toasts.value.filter((t) => t.id !== id); }, 3200);
}

/* ── Dropzone filenames ── */
const dz = reactive({ ceu: '', addLicense: '', renewLicense: '', addInsurance: '', renewInsurance: '' });

/* ── Credentials CRUD (real backend) ── */
const selectedCred = ref(null);

const credForm = useForm({
  category: 'license', cred_type: '', name: '', subtitle: '', issuer: '',
  license_number: '', credential_number: '', policy_number: '', coverage_amount: '',
  deductible: '', annual_premium: '', reference_number: '', issue_date: '', expires_at: '',
  notes: '', document: null,
});
const updateForm = useForm({
  expires_at: '', reference_number: '', coverage_amount: '', policy_number: '', document: null,
});

function openAddCredential(category = 'license') {
  credForm.reset();
  credForm.clearErrors();
  credForm.category = category;
  open('addLicense');
}
function submitAddCredential() {
  credForm.post('/provider/credentials', {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => { credForm.reset(); closeModal(); },
  });
}
function openCredentialDetail(cred) {
  selectedCred.value = cred;
  open(cred.category === 'insurance' ? 'insuranceDetail' : 'licenseDetail');
}
function openRenewCredential(cred) {
  selectedCred.value = cred;
  updateForm.reset();
  updateForm.clearErrors();
  updateForm.expires_at = cred.expires_at_input || '';
  open(cred.category === 'insurance' ? 'renewInsurance' : 'renewLicense');
}
function submitRenewCredential() {
  if (!selectedCred.value) return;
  updateForm
    .transform((d) => ({ ...d, _method: 'put' }))
    .post(`/provider/credentials/${selectedCred.value.id}`, {
      preserveScroll: true,
      forceFormData: true,
      onSuccess: () => { updateForm.reset(); closeModal(); },
    });
}
function deleteCredential(cred) {
  if (!cred || !window.confirm('Remove this credential? This cannot be undone.')) return;
  router.delete(`/provider/credentials/${cred.id}`, {
    preserveScroll: true,
    onSuccess: () => closeModal(),
  });
}
function onCredFile(e) { credForm.document = e.target.files[0] || null; }
function onUpdateFile(e) { updateForm.document = e.target.files[0] || null; }

function credDaysText(cred) {
  const d = cred.days_remaining;
  if (d === null || d === undefined) return 'No expiry';
  if (d <= 0) return 'Expired';
  if (d <= 30) return `${d} days · renew now`;
  return `${d} days remaining`;
}
function attnExpiry(item) {
  const d = item.days_remaining;
  if (d === null || d === undefined) return `Expires ${item.expires_at || '—'}`;
  if (d <= 0) return `Expired ${item.expires_at || ''}`.trim();
  return `Expires ${item.expires_at} · ${d} days left`;
}

const isInsuranceAdd = computed(() => credForm.category === 'insurance');

/* ── Activate succession ── */
const succession = reactive({ reason: '', doc: 'none', notes: '' });
function confirmActivateSuccession() {
  if (!succession.reason) { showToast('Please select an incident type.', 'info'); return; }
  showToast('Continuity Support activated — your stewards have been notified.', 'success');
  closeModal();
}

/* ── Annual review ── */
const reviewItems = [
  { label: 'Continuity Steward identity and contact information is current', sub: 'Marcus Chen · (510) 555-0189' },
  { label: 'Support Steward identity and contact information is current', sub: 'Dr. Laura Reyes · (213) 555-0298' },
  { label: 'Support Steward tasks are complete and accurate' },
  { label: 'Assigned Continuity Steward tasks are complete and accurate' },
  { label: 'Continuity Plan is accurate' },
  { label: 'Practice information is accurate' },
  { label: 'Support documentation is complete and accurate' },
  { label: 'Vault documentation is complete and accurate' },
];
const reviewChecks = reactive(reviewItems.map(() => false));
const reviewNotes = ref('');
function attestReview() {
  if (reviewChecks.some((c) => !c)) { showToast('Please confirm all items before attesting.', 'info'); return; }
  showToast('Annual review attested & submitted.', 'success');
  closeModal();
}

function removeCeuReq() {
  if (window.confirm('Remove this CEU requirement?')) showToast('Requirement removed', 'info');
}

/* ── New referral wizard ── */
const roster = [
  { av: 'CA', name: 'Client A.M.', meta: 'Weekly trauma therapy (EMDR) · San Francisco, CA', notes: 'High-acuity PTSD. Refer to EMDR-trained clinician only.', priority: true },
  { av: 'CR', name: 'Client R.S.', meta: 'Weekly individual therapy · San Francisco, CA', notes: 'Active SI history. Warm handoff required. No gap in care.', priority: true },
  { av: 'CN', name: 'Client N.P.', meta: 'Biweekly individual therapy · Daly City, CA', notes: 'Anxiety-focused CBT. Can accept any licensed therapist referral.', priority: false },
  { av: 'CB', name: 'Couple B.K.', meta: 'Biweekly couples therapy · Oakland, CA', notes: 'Stable. Can tolerate 4-6 week handoff window.', priority: false },
  { av: 'FD', name: 'Family D.T.', meta: 'Monthly family systems · San Francisco, CA', notes: 'Prefers female clinician. Spanish-speaking option requested.', priority: false },
];
const providers = ['Dr. Danial Malik', 'Dr. Rachel Moore', 'Dr. Lisa Chen', 'Dr. Sarah Nguyen', 'Dr. Patricia Monroe', 'Dr. Anna Thompson', 'Dr. James Park', 'Dr. Robert Miller'];
const nrf = reactive({
  step: 1, source: 'roster', search: '', selected: '', selectedMeta: '',
  manualId: '', manualDx: '', provider: '', specialty: '', coverage: '', coverageOther: '',
  reason: '', reasonOther: '', urgency: 'routine', notes: '', attachNames: '', hipaa: false,
});
const filteredRoster = computed(() => {
  const q = nrf.search.trim().toLowerCase();
  if (!q) return roster;
  return roster.filter((c) => (c.name + ' ' + c.meta).toLowerCase().includes(q));
});
function nrfSelectPatient(c) { nrf.selected = c.name; nrf.selectedMeta = c.meta; }
function nrfAttach(e) {
  const files = Array.from(e.target.files || []);
  nrf.attachNames = files.map((f) => f.name).join(', ');
}
const reviewClient = computed(() => nrf.source === 'roster' ? (nrf.selected || '—') : (nrf.manualId || '—'));
const reviewReason = computed(() => nrf.reason === 'other' ? (nrf.reasonOther || 'Other') : (nrf.reason || '—'));
const reviewCoverage = computed(() => nrf.coverage === 'other' ? (nrf.coverageOther || 'Other') : (nrf.coverage || '—'));
function nrfGoStep(n) { if (n >= 1 && n <= 4) nrf.step = n; }
function nrfNext() {
  if (nrf.step === 1) {
    if (nrf.source === 'roster' && !nrf.selected) { showToast('Select a client from your roster.', 'info'); return; }
    if (nrf.source === 'manual' && !nrf.manualId.trim()) { showToast('Enter a client identifier.', 'info'); return; }
  }
  if (nrf.step === 2 && !nrf.provider) { showToast('Choose a practitioner to refer to.', 'info'); return; }
  if (nrf.step === 3 && !nrf.reason) { showToast('Select a reason for the referral.', 'info'); return; }
  nrfGoStep(nrf.step + 1);
}
function nrfSubmit() {
  if (!nrf.hipaa) { showToast('Please confirm HIPAA compliance to send.', 'info'); return; }
  showToast('Referral sent successfully.', 'success');
  closeReferral();
}
function closeReferral() {
  closeModal();
  Object.assign(nrf, {
    step: 1, source: 'roster', search: '', selected: '', selectedMeta: '',
    manualId: '', manualDx: '', provider: '', specialty: '', coverage: '', coverageOther: '',
    reason: '', reasonOther: '', urgency: 'routine', notes: '', attachNames: '', hipaa: false,
  });
}
</script>

<script>
/* Lightweight dropzone component used by several modals (shows picked file name). */
export default {
  components: {
    DropZone: {
      props: { modelValue: { type: String, default: '' } },
      emits: ['update:modelValue'],
      methods: {
        pick(e) {
          const f = e.target.files && e.target.files[0];
          this.$emit('update:modelValue', f ? f.name : '');
        },
      },
      template: `
        <label class="dropzone is-compact">
          <div class="dz-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div>
          <div>
            <div class="dz-text"><strong>Click to upload</strong> or drag &amp; drop</div>
            <div class="dz-sub">PDF, JPG, PNG · up to 10MB</div>
            <div class="dz-filename" v-show="modelValue"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><span>{{ modelValue }}</span></div>
          </div>
          <input type="file" accept=".pdf,.jpg,.png" @change="pick" style="display:none">
        </label>`,
    },
  },
};
</script>

<style scoped>
.page-body-inner { /* inherits padding from parent .page-body */ }

/* Overview banner */
.overview-banner {
  margin-bottom: 20px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-left: 3px solid var(--gold-dark);
  border-radius: var(--radius-lg);
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: var(--shadow-sm);
}
.overview-icon-block {
  width: 40px; height: 40px;
  border-radius: var(--radius-sm);
  background: var(--icon-bg-gold);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  color: var(--gold-dark);
}
.overview-text { flex: 1; min-width: 0; }
.overview-title {
  font-family: var(--font-sans);
  font-size: 13px; font-weight: 700;
  color: var(--text);
  margin-bottom: 3px;
}
.overview-desc {
  font-family: var(--font-sans);
  font-size: 13px;
  color: var(--text-3);
  line-height: 1.5;
}

/* Overview tabs — quick nav cards to continuity setup pages */
.ov-tabs {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 22px;
}
.ov-tab {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 14px 16px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  text-decoration: none;
  box-shadow: var(--shadow-xs);
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease;
}
.ov-tab:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-sm);
  border-color: var(--surface-4);
  background: var(--surface);
}
.ov-tab-check {
  width: 26px; height: 26px;
  border-radius: 50%;
  background: var(--green-light);
  color: var(--green-dark);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.ov-tab-text { min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.ov-tab-title {
  font-family: var(--font-sans);
  font-size: 13px; font-weight: 700;
  color: var(--text); line-height: 1.2;
}
.ov-tab-sub { font-size: 11.5px; color: var(--gold-dark); line-height: 1.2; }

@media (max-width: 1100px) { .ov-tabs { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 640px) { .ov-tabs { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

/* ═══════════════════════════════════
   DASHBOARD dh-* CLASSES (from dashboard.php)
═══════════════════════════════════ */

/* Greeting */
.dh-greet {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 26px;
  align-items: end;
  padding: 6px 4px 26px;
  border-bottom: 1px solid var(--border);
  margin-bottom: 22px;
}
.dh-greet-eyebrow {
  font-size: 11px; font-weight: 600;
  letter-spacing: 1.6px; text-transform: uppercase;
  color: var(--gold-dark);
  margin-bottom: 10px;
  display: flex; align-items: center; gap: 9px;
}
.dh-greet-eyebrow::before {
  content: ""; width: 18px; height: 1px;
  background: var(--gold-dark);
}
.dh-greet-title {
  font-family: var(--font-serif);
  font-size: 38px; font-weight: 600;
  letter-spacing: -0.5px;
  line-height: 1.05;
  color: var(--text);
}
.dh-greet-title em { font-style: italic; color: var(--gold-dark); font-weight: 600; }
.dh-greet-sub {
  margin-top: 12px;
  font-size: 14px;
  color: var(--text-3);
  max-width: 560px;
  line-height: 1.55;
}
.dh-greet-meta {
  display: flex; align-items: center; gap: 0;
  padding: 14px 22px;
  border-radius: var(--radius-lg);
  background: var(--surface);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-xs);
}
.dh-greet-mcell {
  display: flex; flex-direction: column; gap: 2px;
  padding: 0 22px;
}
.dh-greet-mcell + .dh-greet-mcell { border-left: 1px solid var(--border); }
.dh-greet-mlabel {
  font-size: 10px; letter-spacing: 1px; text-transform: uppercase;
  color: var(--text-4); font-weight: 600;
}
.dh-greet-mval {
  font-family: var(--font-serif);
  font-size: 18px; font-weight: 600;
  color: var(--text);
  display: inline-flex; align-items: center; gap: 5px;
}
.dh-greet-mval.ok { color: var(--green-dark); }

/* Section header */
.dh-sh {
  display: flex; align-items: flex-end; justify-content: space-between;
  margin: 28px 0 14px; gap: 16px;
}
.dh-sh-l { display: flex; flex-direction: column; gap: 3px; }
.dh-sh-eyebrow {
  font-size: 10px; font-weight: 600;
  letter-spacing: 1.4px; text-transform: uppercase;
  color: var(--gold-dark);
}
.dh-sh-title {
  font-family: var(--font-serif);
  font-size: 20px; font-weight: 600;
  letter-spacing: -0.2px;
  color: var(--text);
}
.dh-sh-link {
  font-size: 12px; font-weight: 600;
  color: var(--text-3);
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 0;
  text-decoration: none;
  transition: color .18s ease;
}
.dh-sh-link:hover { color: var(--gold-dark); }

/* Continuity hero */
.dh-continuity {
  display: grid; grid-template-columns: 1.4fr 1fr;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  margin-bottom: 6px;
}
.dh-cn-left {
  padding: 26px 28px 24px;
  display: flex; flex-direction: column; gap: 16px;
  border-right: 1px solid var(--border);
  background: var(--surface-2);
}
.dh-cn-eyebrow {
  display: inline-flex; align-items: center; gap: 8px;
  font-size: 10px; font-weight: 700;
  letter-spacing: 1.4px; text-transform: uppercase;
  color: var(--gold-dark);
}
.dh-cn-pulse {
  width: 7px; height: 7px;
  border-radius: var(--radius-full);
  background: var(--green);
  animation: dh-pulse 2s infinite;
}
@keyframes dh-pulse {
  0%   { box-shadow: 0 0 0 0 rgba(76,175,125,0.5); }
  70%  { box-shadow: 0 0 0 8px transparent; }
  100% { box-shadow: 0 0 0 0 transparent; }
}
.dh-cn-title {
  font-family: var(--font-serif);
  font-size: 24px; font-weight: 600;
  letter-spacing: -0.3px; line-height: 1.15;
  color: var(--text);
}
.dh-cn-desc { font-size: 13px; color: var(--text-3); line-height: 1.6; max-width: 480px; }
.dh-cn-stewards { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 4px; }
.dh-cn-stew {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 12px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
}
.dh-cn-savatar {
  width: 36px; height: 36px;
  border-radius: var(--radius-sm);
  background: var(--gold-dark);
  color: var(--text-inverted);
  font-family: var(--font-serif);
  font-weight: 700; font-size: 13px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.dh-cn-savatar.support { background: var(--avatar-gradient-teal, #2e8a57); }
.dh-cn-stew-info { min-width: 0; flex: 1; }
.dh-cn-srole {
  font-size: 10px; font-weight: 700;
  letter-spacing: 1px; text-transform: uppercase;
  color: var(--gold-dark); margin-bottom: 2px;
}
.dh-cn-sname { font-size: 13px; font-weight: 600; line-height: 1.2; color: var(--text); }
.dh-cn-stat {
  margin-left: auto;
  font-size: 10px; font-weight: 600;
  color: var(--green-dark);
  display: inline-flex; align-items: center; gap: 4px;
}
.dh-cn-stat::before {
  content: ""; width: 5px; height: 5px;
  border-radius: var(--radius-full);
  background: var(--green);
}
.dh-cn-stat.pending { color: var(--orange-dark); }
.dh-cn-stat.pending::before { background: var(--orange); }
.dh-cn-actions { display: flex; gap: 8px; margin-top: 6px; flex-wrap: wrap; }
.dh-cn-activate {
  background: var(--red-light); color: var(--red); border-color: var(--fade-red, rgba(160,45,34,0.4));
  padding-left: 10px; padding-right: 10px;
}
.dh-cn-activate:hover { background: var(--red); color: #fff; border-color: var(--red); }
.dh-cn-right {
  padding: 26px 28px;
  display: flex; flex-direction: column; gap: 12px;
  background: var(--surface);
}
.dh-cn-rhead { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.dh-cn-rtag {
  font-size: 10px; font-weight: 700;
  letter-spacing: 1.2px; text-transform: uppercase;
  color: var(--orange-dark);
}
.dh-cn-due {
  font-family: var(--font-serif);
  font-size: 18px; font-weight: 600;
  line-height: 1.1; color: var(--text); margin-top: 3px;
}
.dh-cn-due small {
  display: block;
  font-family: var(--font-sans);
  font-size: 12px; font-weight: 600;
  color: var(--text-3); margin-top: 4px;
}
.dh-cn-bar {
  position: relative; height: 6px;
  background: var(--surface-3);
  border-radius: var(--radius-full);
  overflow: hidden; margin: 6px 0 4px;
}
.dh-cn-bar-fill {
  position: absolute; left: 0; top: 0; bottom: 0; width: 62%;
  background: var(--gold-dark);
  border-radius: var(--radius-full);
}
.dh-cn-bar-marker {
  position: absolute; left: 62%; top: -3px;
  width: 12px; height: 12px;
  border-radius: var(--radius-full);
  background: var(--orange);
  border: 2.5px solid var(--surface);
  box-shadow: var(--shadow-xs);
  transform: translateX(-50%);
}
.dh-cn-bar-labels { display: flex; justify-content: space-between; font-size: 10px; color: var(--text-4); margin-top: 6px; }
.dh-cn-todos { display: flex; flex-direction: column; gap: 6px; margin-top: 6px; }
.dh-cn-todo {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 12px;
  border-radius: var(--radius);
  background: var(--surface-2);
  border: 1px solid var(--border);
  font-size: 12px; color: var(--text-2);
  transition: border-color .18s ease, background .18s ease;
}
.dh-cn-todo:hover { border-color: var(--gold-dark); background: rgba(192,154,82,0.10); }
.dh-cn-todo.done { color: var(--text-4); text-decoration: line-through; background: transparent; }

/* At-a-glance */
.dh-glance {
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: 12px; margin-bottom: 6px;
}
.dh-gl-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 18px 20px;
  display: flex; flex-direction: column; gap: 14px;
  box-shadow: var(--shadow-xs);
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.dh-gl-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); border-color: var(--surface-4); }
.dh-gl-head { display: flex; align-items: center; justify-content: space-between; }
.dh-gl-label {
  font-size: 11px; font-weight: 600;
  letter-spacing: 1px; text-transform: uppercase;
  color: var(--text-4);
}
.dh-gl-icon {
  width: 30px; height: 30px;
  border-radius: var(--radius-sm);
  background: var(--icon-bg-gold);
  color: var(--gold-dark);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.dh-gl-icon.warn { background: var(--orange-light); color: var(--orange-dark); }
.dh-gl-val {
  font-family: var(--font-serif);
  font-size: 32px; font-weight: 600;
  line-height: 1; letter-spacing: -1px;
  color: var(--text);
}
.dh-gl-val small {
  font-family: var(--font-sans);
  font-size: 16px; font-weight: 600;
  color: var(--text-4);
}
.dh-gl-sub { font-size: 12px; color: var(--text-3); line-height: 1.4; }
.dh-gl-sub strong { color: var(--green-dark); font-weight: 600; }
.dh-gl-sub strong.warn-text { color: var(--orange-dark); }

/* Credentials 2-col */
.dh-cols {
  display: grid; grid-template-columns: 1.55fr 1fr;
  gap: 20px; margin-bottom: 6px; align-items: start;
}
.dh-cred-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-xs);
}
.dh-cred-row {
  display: flex; align-items: center; gap: 16px;
  padding: 14px 18px;
  border-bottom: 1px solid var(--border);
  transition: background .18s ease;
}
.dh-cred-row:last-of-type { border-bottom: none; }
.dh-cred-row:hover { background: var(--surface-2); }
.dh-cred-icon {
  width: 38px; height: 38px;
  border-radius: var(--radius);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  border: 1px solid transparent;
}
.dh-cred-icon.ok   { background: var(--green-light); color: var(--green-dark); border-color: var(--fade-green, rgba(76,175,125,0.2)); }
.dh-cred-icon.warn { background: var(--orange-light); color: var(--orange-dark); border-color: var(--fade-orange, rgba(232,169,74,0.2)); }
.dh-cred-icon.crit { background: var(--red-light); color: var(--red); border-color: var(--fade-red, rgba(160,45,34,0.2)); }
.dh-cred-info { min-width: 0; flex: 1; }
.dh-cred-title { font-size: 14px; font-weight: 600; color: var(--text); line-height: 1.2; }
.dh-cred-sub { font-size: 12px; color: var(--text-3); margin-top: 3px; }
.dh-cred-meter { display: flex; flex-direction: column; align-items: flex-end; gap: 5px; min-width: 150px; flex-shrink: 0; }
.dh-cred-date { font-family: var(--font-serif); font-size: 13px; font-weight: 600; color: var(--text); letter-spacing: 0.1px; }
.dh-cred-date.crit { color: var(--red); }
.dh-cred-date.warn { color: var(--orange-dark); }
.dh-cred-bar {
  width: 130px; height: 4px;
  border-radius: var(--radius-full);
  background: var(--surface-3);
  overflow: hidden;
  position: relative;
}
.dh-cred-bar-fill { position: absolute; left: 0; top: 0; bottom: 0; border-radius: var(--radius-full); }
.dh-cred-bar-fill.ok   { background: var(--green); width: 78%; }
.dh-cred-bar-fill.warn { background: var(--orange); width: 38%; }
.dh-cred-bar-fill.crit { background: var(--red); width: 8%; }
.dh-cred-days { font-size: 11px; font-weight: 600; color: var(--text-3); }
.dh-cred-days.ok   { color: var(--green-dark); }
.dh-cred-days.warn { color: var(--orange-dark); }
.dh-cred-days.crit { color: var(--red); font-weight: 600; }
.dh-cred-act { display: flex; gap: 6px; flex-shrink: 0; }
.dh-cred-foot {
  padding: 12px 18px;
  background: var(--surface-2);
  border-top: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
  font-size: 12px; color: var(--text-3);
  gap: 12px; flex-wrap: wrap;
}
.dh-cred-foot strong { color: var(--text); font-weight: 600; }
.dh-cred-foot .crit-text { color: var(--red); font-weight: 600; }

.dh-cred-empty { padding: 40px 24px; text-align: center; }
.dh-cred-empty-icon {
  width: 52px; height: 52px; margin: 0 auto 14px;
  border-radius: 50%; background: var(--icon-bg-gold); color: var(--gold-dark);
  display: flex; align-items: center; justify-content: center;
}
.dh-cred-empty-title { font-family: var(--font-serif); font-size: 16px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
.dh-cred-empty-sub { font-size: 12.5px; color: var(--text-3); line-height: 1.5; max-width: 320px; margin: 0 auto 16px; }

/* Attention card */
.dh-attention {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-xs);
}
.dh-att-head {
  display: flex; align-items: center; gap: 11px;
  padding: 16px 18px 12px;
  border-bottom: 1px solid var(--border);
}
.dh-att-icon {
  width: 30px; height: 30px;
  border-radius: var(--radius-sm);
  background: var(--icon-bg-gold);
  color: var(--gold-dark);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.dh-att-title {
  font-family: var(--font-serif);
  font-size: 16px; font-weight: 600;
  color: var(--text); flex: 1;
}
.dh-att-count {
  font-size: 11px; font-weight: 700;
  background: var(--red); color: var(--text-inverted);
  padding: 2px 9px;
  border-radius: var(--radius-full);
  font-family: var(--font-sans);
}
.dh-att-list { display: flex; flex-direction: column; }
.dh-att-item {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 18px;
  border-bottom: 1px solid var(--border);
  transition: background .18s ease;
}
.dh-att-item:last-of-type { border-bottom: none; }
.dh-att-item:hover { background: var(--surface-2); }
.dh-att-bullet { width: 8px; height: 8px; border-radius: var(--radius-full); flex-shrink: 0; }
.dh-att-bullet.crit { background: var(--red); }
.dh-att-bullet.warn { background: var(--orange); }
.dh-att-bullet.info { background: var(--blue); }
.dh-att-text { flex: 1; min-width: 0; }
.dh-att-h { font-size: 13px; font-weight: 600; color: var(--text); line-height: 1.3; }
.dh-att-d { font-size: 12px; color: var(--text-3); line-height: 1.5; margin-top: 2px; }
.dh-att-d strong { color: var(--text-2); font-weight: 600; }

/* Quick actions */
.dh-quick {
  margin-top: 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 16px 18px;
  box-shadow: var(--shadow-xs);
}
.dh-quick-title {
  font-family: var(--font-serif);
  font-size: 15px; font-weight: 600;
  color: var(--text);
  margin-bottom: 12px;
}
.dh-quick-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }

/* Network section */
.network-carousel-section {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); padding: 18px 24px 22px;
  margin-bottom: 22px; box-shadow: var(--shadow-sm);
}
.nw-head {
  display: flex; align-items: center; justify-content: space-between;
  gap: 14px; flex-wrap: wrap; margin-bottom: 16px;
}
.nw-grid {
  display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;
}
.nw-card {
  position: relative;
  display: flex; flex-direction: column; gap: 14px;
  padding: 18px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-xs);
  cursor: pointer;
  transition: box-shadow 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
}
.nw-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); border-color: var(--surface-4); }
.nw-top { display: flex; align-items: flex-start; gap: 12px; }
.nw-avatar {
  width: 48px; height: 48px;
  border-radius: var(--radius);
  background: var(--gold-dark);
  color: var(--text-inverted);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-serif); font-size: 16px; font-weight: 700;
  flex-shrink: 0;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.2);
}
.nw-info { min-width: 0; flex: 1; }
.nw-name {
  font-family: var(--font-serif); font-size: 15px; font-weight: 600;
  color: var(--text); line-height: 1.2;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.nw-role { font-size: 12px; color: var(--text-3); margin-top: 3px; }
.nw-tags { display: flex; gap: 5px; margin-left: auto; flex-shrink: 0; align-items: center; }
.nw-pill {
  width: 22px; height: 22px;
  border-radius: var(--radius-sm);
  display: inline-flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.nw-pill.net { background: var(--green-light); color: var(--green-dark); }
.nw-pill.svc { background: var(--badge-bg-gold); color: var(--gold-dark); }
.nw-tag {
  font-size: 10px; font-weight: 600;
  color: var(--text-3); background: var(--surface-3);
  border-radius: var(--radius-full); padding: 3px 9px;
  white-space: nowrap; max-width: 180px; overflow: hidden; text-overflow: ellipsis;
}
.nw-meta {
  display: flex; align-items: center; gap: 14px;
  font-size: 12px; color: var(--text-3);
  padding-top: 12px;
  border-top: 1px solid var(--border);
}
.nw-meta-item { display: inline-flex; align-items: center; gap: 5px; }
.nw-cta { margin-left: auto; display: flex; gap: 6px; flex-shrink: 0; }
.nw-btn {
  width: 28px; height: 28px;
  border-radius: var(--radius-sm);
  background: var(--surface-2);
  border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  color: var(--text-3);
  cursor: pointer;
  transition: border-color .18s ease, color .18s ease, background .18s ease;
  text-decoration: none;
}
.nw-btn:hover { background: var(--surface-3); border-color: rgba(160,129,62,0.5); color: var(--gold-dark); }
.nw-btn.primary { background: var(--gold-dark); color: var(--text-inverted); border-color: var(--gold-dark); }
.nw-btn.primary:hover { background: var(--primary-mid); border-color: var(--primary-mid); color: var(--text-inverted); }

/* Responsive */
@media (max-width: 1180px) {
  .dh-greet { grid-template-columns: 1fr; }
  .dh-glance { grid-template-columns: repeat(2, 1fr); }
  .dh-cols { grid-template-columns: 1fr; }
  .dh-continuity { grid-template-columns: 1fr; }
  .dh-cn-left { border-right: none; border-bottom: 1px solid var(--border); }
}
@media (max-width: 768px) {
  .nw-grid { grid-template-columns: 1fr; }
}
@media (max-width: 700px) {
  .dh-greet-title { font-size: 30px; }
  .dh-greet-meta { flex-wrap: wrap; }
  .dh-greet-mcell { padding: 6px 12px; }
  .dh-greet-mcell + .dh-greet-mcell { border-left: none; }
  .dh-glance { grid-template-columns: 1fr 1fr; }
  .dh-cred-row { flex-wrap: wrap; }
  .dh-cred-meter { min-width: 100%; align-items: flex-start; margin-top: 4px; }
}
</style>

<!-- Global modal-content helpers not present in _shared.css (apply to teleported modal bodies) -->
<style>
.exec-person-row { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--surface-2); }
.exec-avatar { width: 34px; height: 34px; border-radius: var(--radius-full); background: var(--gold-dark); color: var(--text-inverted); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
.exec-role-chip { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gold-dark); background: var(--badge-bg-gold); border: 1px solid var(--fade-gold, rgba(160,129,62,0.4)); border-radius: var(--radius-full); padding: 3px 9px; white-space: nowrap; }
.cc-detail-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 9px 12px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); }
.cc-detail-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-4); }
.cc-detail-value { font-size: 13px; font-weight: 600; color: var(--text); text-align: right; }
.review-item { display: flex; align-items: flex-start; gap: 11px; padding: 11px 0; border-bottom: 1px solid var(--border); }
.review-item:last-of-type { border-bottom: none; }
.review-check { width: 17px; height: 17px; flex-shrink: 0; margin-top: 1px; accent-color: var(--gold-dark); cursor: pointer; }
.ceu-item { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 8px; }
.styled-chk { width: 17px; height: 17px; accent-color: var(--gold-dark); cursor: pointer; flex-shrink: 0; }
.req { color: var(--red); margin-left: 2px; }
.optional { font-weight: 400; letter-spacing: 0; text-transform: none; color: var(--text-3); margin-left: 4px; }
.form-error { display: none; align-items: center; gap: 5px; font-size: 11px; color: var(--red); margin-top: 5px; }
.form-error.visible { display: flex; }
.form-error svg { width: 11px; height: 11px; stroke: currentColor; fill: none; flex-shrink: 0; }

/* Dropzone */
.dropzone { display: flex; align-items: center; gap: 12px; border: 1px dashed var(--border); border-radius: var(--radius-sm); padding: 14px 16px; cursor: pointer; transition: background .18s ease, border-color .18s ease; background: var(--surface); }
.dropzone:hover { background: var(--surface-2); border-color: var(--gold); }
.dropzone.is-compact { padding: 11px 14px; }
.dz-icon { width: 34px; height: 34px; border-radius: var(--radius-sm); background: var(--gold-dark); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.dz-text { font-size: 13px; color: var(--text-2); }
.dz-text strong { color: var(--gold-dark); font-weight: 700; }
.dz-sub { font-size: 11px; color: var(--text-4); margin-top: 2px; }
.dz-filename { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; color: var(--green-dark); font-weight: 600; margin-top: 4px; }

/* New referral modal */
#dh-noop {}
.nrf-src-chooser { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
.nrf-src-option { border: 1px solid var(--border); background: var(--surface); border-radius: var(--radius-sm); padding: 12px 14px; cursor: pointer; transition: box-shadow 180ms; }
.nrf-src-option:hover { box-shadow: var(--shadow-sm); }
.nrf-src-option.selected { border-color: var(--soft-gold, rgba(160,129,62,0.5)); background: var(--badge-bg-gold); }
.nrf-src-icon { margin-bottom: 6px; color: var(--text-3); display: inline-flex; align-items: center; line-height: 0; }
.nrf-src-option.selected .nrf-src-icon { color: var(--gold-dark); }
.nrf-src-title { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 3px; }
.nrf-src-sub { font-size: 12px; color: var(--text-3); line-height: 1.4; }
.nrf-roster-list { margin-bottom: 4px; }
.nrf-pt-row { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 6px; cursor: pointer; transition: box-shadow 180ms; }
.nrf-pt-row:hover { box-shadow: var(--shadow-sm); }
.nrf-pt-row.selected { border-color: var(--soft-gold, rgba(160,129,62,0.5)); background: var(--badge-bg-gold); }
.nrf-pt-avatar { width: 34px; height: 34px; border-radius: var(--radius-full); background: var(--gold-dark); color: var(--text-inverted); display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; }
.nrf-pt-info { flex: 1; min-width: 0; }
.nrf-pt-name { font-size: 13px; font-weight: 700; color: var(--text); }
.nrf-pt-meta { font-size: 12px; color: var(--text-3); }
.nrf-pt-tag { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: var(--radius-full); text-transform: uppercase; letter-spacing: 0.4px; flex-shrink: 0; }
.nrf-pt-tag-priority { background: var(--red-light); color: var(--red-dark); }
.nrf-pt-tag-active { background: var(--green-light); color: var(--green-dark); }
.nrf-empty { display: none; padding: 18px; text-align: center; background: var(--surface-2); border: 1px dashed var(--border); border-radius: var(--radius-sm); font-size: 13px; color: var(--text-3); }
.nrf-link { color: var(--gold-dark); font-weight: 700; text-decoration: none; }
.nrf-link:hover { text-decoration: underline; }
.nrf-selected { margin-top: 12px; background: var(--badge-bg-gold); border: 1px solid var(--soft-gold, rgba(160,129,62,0.5)); border-radius: var(--radius-sm); padding: 11px 14px; }
.nrf-selected-title { font-size: 12px; font-weight: 700; color: var(--gold-dark); margin-bottom: 5px; display: flex; align-items: center; gap: 5px; }
.nrf-selected-body { font-size: 13px; color: var(--text-2); line-height: 1.55; }
.nrf-selected-clear { background: none; border: none; font-size: 11px; color: var(--text-4); cursor: pointer; margin-top: 6px; padding: 0; text-decoration: underline; }
.nrf-hipaa-note { background: var(--blue-light); border-left: 3px solid var(--blue-dark); border-radius: var(--radius-sm); padding: 10px 14px; margin-bottom: 14px; font-size: 12px; color: var(--blue-dark); line-height: 1.5; }
.nrf-review { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 24px; margin-bottom: 14px; }
.nrf-review-section-full { grid-column: 1 / -1; }
.nrf-review-label { font-size: 11px; font-weight: 700; color: var(--text-3); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px; }
.nrf-review-value { font-size: 13px; font-weight: 600; color: var(--text); line-height: 1.4; padding-bottom: 8px; border-bottom: 1px solid var(--border); }
.nrf-review-section-full .nrf-review-value { font-weight: 500; color: var(--text-2); line-height: 1.55; }
.nrf-dropzone { border: 1px dashed var(--border); border-radius: var(--radius-sm); padding: 16px; text-align: center; font-size: 13px; color: var(--text-3); cursor: pointer; transition: background 180ms, box-shadow 180ms; }
.nrf-dropzone:hover { background: var(--surface-2); box-shadow: var(--shadow-sm); }
.nrf-dropzone-icon { display: inline-flex; align-items: center; line-height: 0; margin-bottom: 4px; }
.nrf-attach-list { margin-top: 8px; font-size: 12px; color: var(--text-3); }
.nrf-hipaa-ack { display: flex; align-items: flex-start; gap: 10px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 12px 14px; font-size: 13px; color: var(--text-2); line-height: 1.5; }
.nrf-hipaa-ack label { cursor: pointer; }

/* Toasts */
.dh-toast-stack { position: fixed; bottom: 22px; right: 22px; z-index: 4000; display: flex; flex-direction: column; gap: 10px; }
.dh-toast { display: flex; align-items: center; gap: 9px; padding: 11px 16px; border-radius: var(--radius); background: var(--text); color: var(--text-inverted); font-size: 13px; font-weight: 600; box-shadow: var(--shadow-lg); max-width: 360px; animation: dh-toast-in .22s ease both; }
.dh-toast.success { background: var(--green-dark); }
.dh-toast.info { background: var(--text); }
.dh-toast svg { flex-shrink: 0; }
@keyframes dh-toast-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>
