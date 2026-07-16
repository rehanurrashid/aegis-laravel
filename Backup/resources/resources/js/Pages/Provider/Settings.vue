<template>
  <AppLayout :user="user" portal="practitioner" activePage="settings" pageTitle="Settings">

    <!-- ═══ HEADER ═══ -->
    <div class="st-header">
      <div>
        <div class="st-eyebrow">Provider Portal</div>
        <h1 class="st-title">Account Settings</h1>
        <div class="st-meta">
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> {{ displayName }}</span>
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Continuity Practice · 2FA Active</span>
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Last login Jun 23, 2026</span>
        </div>
      </div>
      <div class="st-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export</button>
        <button type="button" class="btn btn-primary btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save All</button>
      </div>
    </div>

    <div class="st-layout">
      <!-- ═══ NAV ═══ -->
      <aside class="st-nav">
        <div class="st-nav-head"><span class="st-nav-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span> Settings</div>
        <template v-for="grp in nav" :key="grp.group">
          <div class="st-nav-group">{{ grp.group }}</div>
          <button v-for="it in grp.items" :key="it.key" type="button" class="st-nav-item" :class="{ active: section === it.key, danger: it.danger }" @click="section = it.key">
            <span class="st-nav-item-ico" v-html="it.icon"></span> <span class="st-nav-item-label">{{ it.label }}</span>
            <span v-if="it.badge" class="st-nav-badge">{{ it.badge }}</span>
          </button>
        </template>
      </aside>

      <!-- ═══ PANEL ═══ -->
      <div class="st-panel">

        <!-- PROFILE & IDENTITY -->
        <template v-if="section === 'profile'">
          <div class="st-banner"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> <span><strong>Unified identity across all portals.</strong> Your photo, display name, email, and phone are shared across every portal you have access to — Practitioner, Continuity Steward, and Support Steward. Changes in <strong>Edit Profile</strong> update your identity platform-wide.</span></div>
          <div class="st-card">
            <div class="st-card-head">
              <div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span><div><div class="st-card-title">Profile Summary</div><div class="st-card-sub">Your public-facing identity on Aegis</div></div></div>
              <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View Public Profile</button>
            </div>
            <div class="st-profile-card">
              <span class="st-av lg">{{ initials }}</span>
              <div class="st-profile-info">
                <div class="st-profile-name">{{ displayName }}</div>
                <div class="st-profile-role">Licensed Marriage &amp; Family Therapist · PhD, LMFT</div>
                <div class="st-profile-contacts">
                  <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> {{ acct.email }}</span>
                  <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg> {{ acct.phone }}</span>
                  <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> San Francisco, CA</span>
                </div>
              </div>
              <span class="st-bp-badge">Continuity Practice</span>
            </div>
            <div class="st-card-foot"><a href="/provider/profile" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg> Edit Full Profile</a></div>
          </div>
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span><div><div class="st-card-title">Portal Access</div><div class="st-card-sub">Portals linked to your Aegis account</div></div></div></div>
            <div class="st-card-body">
              <div class="st-portal-row">
                <span class="st-portal-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                <div class="st-portal-info"><div class="st-portal-name">Practitioner Portal</div><div class="st-portal-sub">Profile settings, referrals, continuity plan — Continuity Practice</div></div>
                <span class="st-badge-green">Active</span>
                <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></button>
              </div>
              <div class="st-portal-row" style="margin-top:10px">
                <span class="st-portal-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                <div class="st-portal-info"><div class="st-portal-name">Continuity Steward Portal</div><div class="st-portal-sub">Managing continuity for assigned practitioners</div></div>
                <span class="st-badge-blue">CS Active</span>
                <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></button>
              </div>
              <div class="st-info-band"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg> Each portal has its own <strong>Edit Profile</strong> page for role-specific information (credentials, services, bio). Photo, name, email, and phone are always shared.</div>
            </div>
          </div>
        </template>

        <!-- ACCOUNT & LOGIN -->
        <template v-else-if="section === 'account'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><div><div class="st-card-title">Account &amp; Login</div><div class="st-card-sub">Email, password, and login credentials</div></div></div></div>
            <div class="st-card-body">
              <div class="st-banner blue"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg> <span><strong>Unified credentials</strong> — Your email, phone, and password are shared across all portals. Changes here apply everywhere on your Aegis account.</span></div>
              <div class="st-grid2">
                <div class="st-field"><label class="st-label">Primary Email <span class="st-req">*</span></label><input class="st-input" v-model="acct.email" /><div class="st-note green"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Verified · Used for login and important notices</div></div>
                <div class="st-field"><label class="st-label">Recovery Email</label><input class="st-input" v-model="acct.recovery" placeholder="Secondary email..." /><div class="st-note">Used if you lose access to your primary email</div></div>
                <div class="st-field"><label class="st-label">Phone Number</label><input class="st-input" v-model="acct.phone" /></div>
                <div class="st-field"><label class="st-label">Username / Handle</label><input class="st-input" v-model="acct.handle" /><div class="st-note">Your public @handle on Aegis</div></div>
              </div>
              <div class="st-divider"></div>
              <div class="st-subhead">Change Password</div>
              <div class="st-field"><label class="st-label">Current Password</label><div class="st-input-wrap"><input class="st-input" :type="showPw ? 'text' : 'password'" v-model="acct.current" /><button type="button" class="st-eye" @click="showPw = !showPw"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div></div>
              <div class="st-grid2">
                <div class="st-field"><label class="st-label">New Password</label><input class="st-input" type="password" v-model="acct.newpw" placeholder="Min 12 characters..." /></div>
                <div class="st-field"><label class="st-label">Confirm New Password</label><input class="st-input" type="password" v-model="acct.confirmpw" placeholder="Repeat new password..." /></div>
              </div>
              <div class="st-note">Password must be 12+ characters, include uppercase, number, and special character.</div>
              <div class="st-card-actions"><button type="button" class="btn btn-outline btn-sm">Cancel</button><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Update Password</button></div>
            </div>
          </div>
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></span><div><div class="st-card-title">Active Sessions</div><div class="st-card-sub">Devices currently logged into your account</div></div></div><button type="button" class="btn btn-danger btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Revoke All</button></div>
            <div class="st-card-body">
              <div v-for="(s, idx) in sessions" :key="idx" class="st-session">
                <span class="st-session-ico" v-html="s.mobile ? phoneSvg : laptopSvg"></span>
                <div class="st-session-info"><div class="st-session-name">{{ s.device }} <span v-if="s.current" class="st-current">Current</span></div><div class="st-session-meta">{{ s.meta }}</div></div>
                <button v-if="!s.current" type="button" class="btn-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
              </div>
            </div>
          </div>
        </template>

        <!-- SECURITY & 2FA -->
        <template v-else-if="section === 'security'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span><div><div class="st-card-title">Security &amp; Two-Factor Authentication</div><div class="st-card-sub">Unified across all portals — 2FA and sessions apply to your entire Aegis account</div></div></div><span class="st-badge-green">2FA Enabled</span></div>
            <div class="st-card-body">
              <div class="st-2fa-row active">
                <span class="st-2fa-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg></span>
                <div class="st-2fa-info"><div class="st-2fa-name">Authenticator App <span class="st-2fa-active">✓ Active</span></div><div class="st-2fa-sub">Google Authenticator, Authy, or any TOTP app — most secure</div></div>
                <button type="button" class="btn btn-outline btn-sm" @click="showBackup = true">View Backup Codes</button>
              </div>
              <div class="st-2fa-row">
                <span class="st-2fa-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                <div class="st-2fa-info"><div class="st-2fa-name">SMS / Text Message</div><div class="st-2fa-sub">Receive a 6-digit code via text to {{ acct.phone }}</div></div>
                <button type="button" class="btn btn-gold btn-sm" @click="show2fa = true">Set Up</button>
              </div>
              <div class="st-2fa-row">
                <span class="st-2fa-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                <div class="st-2fa-info"><div class="st-2fa-name">Email Code</div><div class="st-2fa-sub">A code sent to {{ acct.email }}</div></div>
                <button type="button" class="btn btn-gold btn-sm" @click="show2fa = true">Set Up</button>
              </div>
              <div class="st-divider"></div>
              <div class="st-subhead">Security Alerts</div>
              <div v-for="(a, idx) in securityAlerts" :key="idx" class="st-toggle-row">
                <div><div class="st-toggle-name">{{ a.name }}</div><div class="st-toggle-sub">{{ a.sub }}</div></div>
                <button type="button" class="toggle" :class="{ on: a.on }" @click="a.on = !a.on"></button>
              </div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save Security Settings</button></div>
            </div>
          </div>
        </template>

        <!-- NOTIFICATIONS -->
        <template v-else-if="section === 'notifications'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span><div><div class="st-card-title">Notification Preferences</div><div class="st-card-sub">Delivery channels unified across portals. Per-category preferences apply to your Practitioner role.</div></div></div><div class="st-head-btns"><button type="button" class="st-link-btn">Mute All</button><button type="button" class="btn btn-outline btn-sm">Enable All</button></div></div>
            <div class="st-card-body">
              <div class="st-grid4">
                <div class="st-field"><label class="st-label">Quiet Hours From</label><input class="st-input" type="time" v-model="notif.quietFrom" /></div>
                <div class="st-field"><label class="st-label">Quiet Hours To</label><input class="st-input" type="time" v-model="notif.quietTo" /></div>
                <div class="st-field"><label class="st-label">Notification Digest</label><select class="form-select" v-model="notif.digest"><option>Daily (8 AM)</option><option>Weekly</option><option>Real-time</option></select></div>
                <div class="st-field"><label class="st-label">Event Reminder Lead Time</label><select class="form-select" v-model="notif.lead"><option>1 day before</option><option>2 hours before</option><option>1 week before</option></select></div>
              </div>
              <table class="st-matrix">
                <thead><tr><th>Category</th><th class="c"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg> Push</th><th class="c"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> Email</th><th class="c"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> SMS</th><th class="c"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg> In-App</th></tr></thead>
                <tbody>
                  <tr v-for="row in notifRows" :key="row.name">
                    <td><div class="st-mx-name">{{ row.name }}</div><div class="st-mx-sub">{{ row.sub }}</div></td>
                    <td class="c"><input type="checkbox" class="st-check" v-model="row.push" /></td>
                    <td class="c"><input type="checkbox" class="st-check" v-model="row.email" /></td>
                    <td class="c"><input type="checkbox" class="st-check" v-model="row.sms" /></td>
                    <td class="c"><input type="checkbox" class="st-check" v-model="row.inapp" /></td>
                  </tr>
                </tbody>
              </table>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save Preferences</button></div>
            </div>
          </div>
        </template>

        <!-- MESSAGING -->
        <template v-else-if="section === 'messaging'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span><div><div class="st-card-title">Messaging Preferences</div><div class="st-card-sub">Control who can reach you and how you appear across the Aegis network</div></div></div><a href="/provider/messages" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Open Chat</a></div>
            <div class="st-card-body">
              <div class="st-grid2">
                <div class="st-field"><label class="st-label">Who Can Message Me</label><select class="form-select" v-model="msg.who"><option>Connected providers only</option><option>Anyone on Aegis</option><option>No one</option></select></div>
                <div class="st-field"><label class="st-label">My Status</label><select class="form-select" v-model="msg.status"><option>Available</option><option>Busy</option><option>Away</option></select></div>
              </div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Read Receipts</div><div class="st-toggle-sub">Show senders when you've read their messages</div></div><button type="button" class="toggle" :class="{ on: msg.readReceipts }" @click="msg.readReceipts = !msg.readReceipts"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Online Status</div><div class="st-toggle-sub">Show a presence indicator when you're active on Aegis</div></div><button type="button" class="toggle" :class="{ on: msg.onlineStatus }" @click="msg.onlineStatus = !msg.onlineStatus"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Critical Incident Thread Auto-Flag</div><div class="st-toggle-sub">Messages sent during an active incident are automatically marked as legal record</div></div><button type="button" class="toggle" :class="{ on: msg.autoFlag }" @click="msg.autoFlag = !msg.autoFlag"></button></div>
              <div class="st-field"><label class="st-label">Away Message</label><textarea class="st-input st-textarea" rows="3" v-model="msg.away" placeholder="e.g. 'I'm currently on leave. For urgent matters please contact my Continuity Steward.'"></textarea></div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save</button></div>
            </div>
          </div>
        </template>

        <!-- EMAIL PREFERENCES -->
        <template v-else-if="section === 'email'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span><div><div class="st-card-title">Email Preferences</div><div class="st-card-sub">Manage your digest frequency and optional email updates</div></div></div></div>
            <div class="st-card-body">
              <div class="st-field"><label class="st-label">Digest Frequency</label><select class="form-select" v-model="email.digest"><option>Weekly</option><option>Daily</option><option>Monthly</option><option>Never</option></select></div>
              <div v-for="(t, k) in emailToggles" :key="k" class="st-toggle-row"><div><div class="st-toggle-name">{{ t.name }}</div><div class="st-toggle-sub">{{ t.sub }}</div></div><button type="button" class="toggle" :class="{ on: t.on }" @click="t.on = !t.on"></button></div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save</button></div>
            </div>
          </div>
        </template>

        <!-- AVAILABILITY & HOURS -->
        <template v-else-if="section === 'availability'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><div><div class="st-card-title">Availability &amp; Hours</div><div class="st-card-sub">Set your weekly schedule and client availability</div></div></div><div class="st-head-inline"><span class="st-inline-toggle-label">Accepting New Clients</span><button type="button" class="toggle" :class="{ on: availability.acceptingClients }" @click="availability.acceptingClients = !availability.acceptingClients"></button></div></div>
            <div class="st-card-body">
              <table class="st-hours">
                <thead><tr><th>Day</th><th class="c">Active</th><th>Opens</th><th>Closes</th></tr></thead>
                <tbody>
                  <tr v-for="d in availability.days" :key="d.day">
                    <td class="st-hours-day">{{ d.day }}</td>
                    <td class="c"><input type="checkbox" class="st-check" v-model="d.active" /></td>
                    <template v-if="d.active">
                      <td><input class="st-input" type="time" v-model="d.opens" /></td>
                      <td><input class="st-input" type="time" v-model="d.closes" /></td>
                    </template>
                    <template v-else>
                      <td class="st-hours-closed" colspan="2">Closed</td>
                    </template>
                  </tr>
                </tbody>
              </table>
              <div class="st-divider"></div>
              <div class="st-grid2">
                <div class="st-field"><label class="st-label">Telehealth States Licensed</label><input class="st-input" v-model="availability.states" placeholder="NY, NJ, CT, PA" /><div class="st-note">Comma-separated state codes</div></div>
                <div class="st-field"><label class="st-label">Next Available Appointment</label><input class="st-input" type="date" v-model="availability.nextAppt" /></div>
              </div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save</button></div>
            </div>
          </div>
        </template>

        <!-- REFERRAL PREFERENCES -->
        <template v-else-if="section === 'referrals'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg></span><div><div class="st-card-title">Referral Preferences</div><div class="st-card-sub">How you send and receive referrals on Aegis</div></div></div></div>
            <div class="st-card-body">
              <div class="st-subhead" style="margin-bottom:2px">Incoming Referrals</div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Currently Accepting Referrals</div><div class="st-toggle-sub">When off, your profile shows "Not Accepting Referrals" and senders cannot submit to you</div></div><button type="button" class="toggle" :class="{ on: referralPrefs.accepting }" @click="referralPrefs.accepting = !referralPrefs.accepting"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Auto-Accept from Connected Providers</div><div class="st-toggle-sub">Skip manual review for referrals from providers already in your Integrative Care Network</div></div><button type="button" class="toggle" :class="{ on: referralPrefs.autoAccept }" @click="referralPrefs.autoAccept = !referralPrefs.autoAccept"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Suggest Alternatives When Declining</div><div class="st-toggle-sub">Aegis will suggest compatible providers in your network when you decline a referral</div></div><button type="button" class="toggle" :class="{ on: referralPrefs.suggestAlternatives }" @click="referralPrefs.suggestAlternatives = !referralPrefs.suggestAlternatives"></button></div>
              <div class="st-subhead" style="margin:18px 0 2px">Outgoing Referrals</div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Auto-Archive After 5 Business Days</div><div class="st-toggle-sub">Unanswered referrals are automatically archived after the Aegis standard response window</div></div><button type="button" class="toggle" :class="{ on: referralPrefs.autoArchive }" @click="referralPrefs.autoArchive = !referralPrefs.autoArchive"></button></div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save</button></div>
            </div>
          </div>
        </template>

        <!-- CONTINUITY STEWARD SETTINGS -->
        <template v-else-if="section === 'csettings'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span><div><div class="st-card-title">Continuity Steward Settings</div><div class="st-card-sub">Preferences for your CS relationship and plan reminders</div></div></div><a href="/provider/continuity-stewards" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Manage CS</a></div>
            <div class="st-card-body">
              <div class="st-toggle-row"><div><div class="st-toggle-name">Emergency CS Activation</div><div class="st-toggle-sub">Allow your CS to activate your Continuity Plan with Aegis admin verification during a critical incident</div></div><button type="button" class="toggle" :class="{ on: csSettings.emergencyActivation }" @click="csSettings.emergencyActivation = !csSettings.emergencyActivation"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Annual CS Check-In Reminder</div><div class="st-toggle-sub">Remind me to verify CS availability and re-attest my Continuity Plan every 12 months</div></div><button type="button" class="toggle" :class="{ on: csSettings.annualCheckIn }" @click="csSettings.annualCheckIn = !csSettings.annualCheckIn"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Notify CS on Plan Changes</div><div class="st-toggle-sub">Automatically notify your Continuity Steward when you update or amend your Continuity Plan</div></div><button type="button" class="toggle" :class="{ on: csSettings.notifyOnChanges }" @click="csSettings.notifyOnChanges = !csSettings.notifyOnChanges"></button></div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save</button></div>
            </div>
          </div>
        </template>

        <!-- SUPPORT STEWARD SETTINGS -->
        <template v-else-if="section === 'ssettings'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span><div><div class="st-card-title">Support Steward Settings</div><div class="st-card-sub">Preferences for your SS relationship and notifications</div></div></div><a href="/provider/support-stewards" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Manage SS</a></div>
            <div class="st-card-body">
              <div class="st-toggle-row"><div><div class="st-toggle-name">Notify SS on Critical Incident</div><div class="st-toggle-sub">Your Support Steward is automatically notified when a critical incident is declared</div></div><button type="button" class="toggle" :class="{ on: ssSettings.notifyIncident }" @click="ssSettings.notifyIncident = !ssSettings.notifyIncident"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Notify SS on Plan Changes</div><div class="st-toggle-sub">Automatically notify your Support Steward when you update or amend your Continuity Plan</div></div><button type="button" class="toggle" :class="{ on: ssSettings.notifyChanges }" @click="ssSettings.notifyChanges = !ssSettings.notifyChanges"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">SS Annual Attestation Reminder</div><div class="st-toggle-sub">Remind me to verify SS contact info and re-confirm permissions annually</div></div><button type="button" class="toggle" :class="{ on: ssSettings.annualAttestation }" @click="ssSettings.annualAttestation = !ssSettings.annualAttestation"></button></div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save</button></div>
            </div>
          </div>
        </template>

        <!-- DOCUMENT VAULT ACCESS -->
        <template v-else-if="section === 'vault'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><div><div class="st-card-title">Document Vault</div><div class="st-card-sub">Notification preferences for your vault activity</div></div></div><a href="/provider/vault" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Open Vault</a></div>
            <div class="st-card-body">
              <div class="st-banner blue" style="margin-bottom:6px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg> <span>Steward vault access levels (Emergency Only, Full Read, No Access) are managed per-steward in <a href="/provider/vault" class="st-inline-link">Document Vault →</a></span></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Notify Me on Vault Access</div><div class="st-toggle-sub">Receive an alert when a steward views or downloads a document from your vault</div></div><button type="button" class="toggle" :class="{ on: vaultNotif.onAccess }" @click="vaultNotif.onAccess = !vaultNotif.onAccess"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Notify Me on Emergency Vault Unlock</div><div class="st-toggle-sub">Alert me immediately if the Emergency Vault is accessed during an active incident</div></div><button type="button" class="toggle" :class="{ on: vaultNotif.onEmergency }" @click="vaultNotif.onEmergency = !vaultNotif.onEmergency"></button></div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save</button></div>
            </div>
          </div>
        </template>

        <!-- AGREEMENTS & CONTRACTS -->
        <template v-else-if="section === 'agreements'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><div><div class="st-card-title">Agreements &amp; Contracts</div><div class="st-card-sub">Status of your steward agreements and notification preferences</div></div></div><a href="/provider/important-documents" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Manage Agreements</a></div>
            <div class="st-card-body">
              <div class="st-subhead" style="margin-bottom:12px">Active Agreements</div>
              <div v-for="ag in agreementsList" :key="ag.name" class="st-agreement-row">
                <div class="st-agreement-info"><div class="st-agreement-name">{{ ag.name }}</div><div class="st-agreement-sub">{{ ag.meta }}</div></div>
                <span class="st-badge-green">Active</span>
              </div>
              <div class="st-divider"></div>
              <div class="st-subhead" style="margin-bottom:2px">Notifications</div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Expiry Reminder (30 days before)</div><div class="st-toggle-sub">Notify me 30 days before any steward agreement or attestation is due</div></div><button type="button" class="toggle" :class="{ on: agreementsNotif.expiryReminder }" @click="agreementsNotif.expiryReminder = !agreementsNotif.expiryReminder"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Auto-Renew Annual Agreements</div><div class="st-toggle-sub">Automatically renew Continuity Steward and Support Steward agreements before expiry</div></div><button type="button" class="toggle" :class="{ on: agreementsNotif.autoRenew }" @click="agreementsNotif.autoRenew = !agreementsNotif.autoRenew"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Notify Me When Agreements Are Countersigned</div><div class="st-toggle-sub">Alert me when a steward signs or countersigns an agreement sent by you</div></div><button type="button" class="toggle" :class="{ on: agreementsNotif.notifyCountersign }" @click="agreementsNotif.notifyCountersign = !agreementsNotif.notifyCountersign"></button></div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save</button></div>
            </div>
          </div>
        </template>

        <!-- MY SERVICES SETTINGS -->
        <template v-else-if="section === 'myservices'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></span><div><div class="st-card-title">My Services Settings</div><div class="st-card-sub">Integrative Business Services mode and service offering preferences</div></div></div><a href="/provider/my-services" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg> My Services</a></div>
            <div class="st-card-body">
              <div class="st-subhead" style="margin-bottom:2px">Services Mode</div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Integrative Business Services Mode</div><div class="st-toggle-sub">Offer supervision, consultation, training, and other practitioner-to-practitioner services. This feature enables the <strong>My Services</strong> section in your sidebar and displays the <strong>Integrative Business Services</strong> badge on your profile.</div></div><button type="button" class="toggle" :class="{ on: myServices.mode }" @click="myServices.mode = !myServices.mode"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Show Services on Public Profile</div><div class="st-toggle-sub">Display your service offerings on your public provider profile</div></div><button type="button" class="toggle" :class="{ on: myServices.showPublic }" @click="myServices.showPublic = !myServices.showPublic"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Accept Booking Requests</div><div class="st-toggle-sub">Allow other providers and organizations to submit booking requests for your services</div></div><button type="button" class="toggle" :class="{ on: myServices.acceptBookings }" @click="myServices.acceptBookings = !myServices.acceptBookings"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Auto-Confirm Bookings from Connected Providers</div><div class="st-toggle-sub">Skip the approval step for booking requests from providers already in your Integrative Care Network</div></div><button type="button" class="toggle" :class="{ on: myServices.autoConfirm }" @click="myServices.autoConfirm = !myServices.autoConfirm"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Show Pricing Publicly</div><div class="st-toggle-sub">Providers can see your rates before submitting a booking request</div></div><button type="button" class="toggle" :class="{ on: myServices.showPricing }" @click="myServices.showPricing = !myServices.showPricing"></button></div>
              <div class="st-divider"></div>
              <div class="st-subhead" style="margin-bottom:2px">Visibility in Job Marketplace</div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Visible to Business Partners</div><div class="st-toggle-sub">Allow Business Partners to find and contact you about service contracts</div></div><button type="button" class="toggle" :class="{ on: myServices.visibleToBP }" @click="myServices.visibleToBP = !myServices.visibleToBP"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Accept Direct Job Proposals</div><div class="st-toggle-sub">Allow Business Partners to submit job proposals directly to your profile</div></div><button type="button" class="toggle" :class="{ on: myServices.acceptProposals }" @click="myServices.acceptProposals = !myServices.acceptProposals"></button></div>
              <div class="st-divider"></div>
              <div class="st-subhead" style="margin-bottom:2px">Booking Preferences</div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Sliding Scale Available</div><div class="st-toggle-sub">Offer reduced rates for providers who need flexible pricing</div></div><button type="button" class="toggle" :class="{ on: myServices.slidingScale }" @click="myServices.slidingScale = !myServices.slidingScale"></button></div>
              <div class="st-grid2" style="margin-top:16px">
                <div class="st-field"><label class="st-label">Booking Request Expiry</label><select class="form-select" v-model="myServices.bookingExpiry"><option>24 hours</option><option>48 hours</option><option>72 hours</option><option>1 week</option></select><div class="st-note">Requests expire if not responded to within this window</div></div>
                <div class="st-field"><label class="st-label">Buffer Between Sessions</label><select class="form-select" v-model="myServices.buffer"><option>No buffer</option><option>15 min</option><option>30 min</option><option>60 min</option></select><div class="st-note">Minimum gap between consecutive bookings</div></div>
              </div>
              <div class="st-divider"></div>
              <div class="st-subhead" style="margin-bottom:2px">Payment &amp; Rates</div>
              <div class="st-grid2" style="margin-top:16px">
                <div class="st-field"><label class="st-label">Default Hourly Rate (USD)</label><input class="st-input" type="number" v-model="myServices.hourlyRate" placeholder="150" /><div class="st-note">Shown on your public profile when Services mode is on</div></div>
                <div class="st-field"><label class="st-label">Payment Method</label><select class="form-select" v-model="myServices.paymentMethod"><option>Stripe (Direct Transfer)</option><option>PayPal</option><option>Invoice (Net 30)</option></select></div>
              </div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save Services Settings</button></div>
            </div>
          </div>
        </template>

        <!-- PRIVACY & VISIBILITY -->
        <template v-else-if="section === 'privacy'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span><div><div class="st-card-title">Privacy &amp; Visibility</div><div class="st-card-sub">Controls your <strong>Practitioner Portal</strong> visibility. Each portal has its own privacy settings scoped to that role.</div></div></div></div>
            <div class="st-card-body">
              <div class="st-vis-label">Overall Profile Visibility Level</div>
              <div class="st-vis-grid">
                <button v-for="v in visLevels" :key="v.key" type="button" class="st-vis-card" :class="{ active: privacy.level === v.key }" @click="privacy.level = v.key">
                  <span class="st-vis-ico" v-html="v.icon"></span>
                  <span class="st-vis-name">{{ v.name }}</span>
                  <span class="st-vis-desc">{{ v.desc }}</span>
                </button>
              </div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Appear in Provider Search</div><div class="st-toggle-sub">Let other providers find you when searching by specialty</div></div><button type="button" class="toggle" :class="{ on: privacy.search }" @click="privacy.search = !privacy.search"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Show in Integrative Care Network</div><div class="st-toggle-sub">Allow Aegis to surface your profile in the Integrative Care Network for suggestions</div></div><button type="button" class="toggle" :class="{ on: privacy.icn }" @click="privacy.icn = !privacy.icn"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Show Ratings &amp; Reviews Publicly</div><div class="st-toggle-sub">Display your peer review scores on your public profile</div></div><button type="button" class="toggle" :class="{ on: privacy.ratings }" @click="privacy.ratings = !privacy.ratings"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Show Location (City/State Only)</div><div class="st-toggle-sub">Display your practice location. Exact address never shown.</div></div><button type="button" class="toggle" :class="{ on: privacy.location }" @click="privacy.location = !privacy.location"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Share Referral Statistics Publicly</div><div class="st-toggle-sub">Display your response time and acceptance rate on your public provider profile</div></div><button type="button" class="toggle" :class="{ on: privacy.referralStats }" @click="privacy.referralStats = !privacy.referralStats"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Show Demographics on Public Profile</div><div class="st-toggle-sub">Display language, specialty, and practice location on your public-facing profile</div></div><button type="button" class="toggle" :class="{ on: privacy.demographics }" @click="privacy.demographics = !privacy.demographics"></button></div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save Privacy Settings</button></div>
            </div>
          </div>
        </template>

        <!-- NETWORK SETTINGS -->
        <template v-else-if="section === 'network'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg></span><div><div class="st-card-title">Network &amp; Connection Settings</div><div class="st-card-sub">Manage how you connect with other providers</div></div></div><a href="/provider/network" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Open Network</a></div>
            <div class="st-card-body">
              <div class="st-subhead" style="margin-bottom:2px">Connection Preferences</div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Require Manual Approval for Connections</div><div class="st-toggle-sub">Review and approve all connection requests before they join your network</div></div><button type="button" class="toggle" :class="{ on: network.manualApproval }" @click="network.manualApproval = !network.manualApproval"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Integrative Care Network Matching</div><div class="st-toggle-sub">Allow Aegis to suggest compatible providers based on your specialty and care patterns</div></div><button type="button" class="toggle" :class="{ on: network.icnMatching }" @click="network.icnMatching = !network.icnMatching"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Allow Use of My Data for Network Suggestions</div><div class="st-toggle-sub">Anonymized data helps improve Aegis's provider matching quality</div></div><button type="button" class="toggle" :class="{ on: network.dataUse }" @click="network.dataUse = !network.dataUse"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Show Mutual Connections</div><div class="st-toggle-sub">Display shared connections when viewing other provider profiles</div></div><button type="button" class="toggle" :class="{ on: network.mutualConnections }" @click="network.mutualConnections = !network.mutualConnections"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Hide From Business Network Search</div><div class="st-toggle-sub">Business Partners cannot find your profile without a direct invitation</div></div><button type="button" class="toggle" :class="{ on: network.hideFromBP }" @click="network.hideFromBP = !network.hideFromBP"></button></div>
              <div class="st-divider"></div>
              <div class="st-subhead" style="margin-bottom:2px">Network Notifications</div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">New Connection Request Alerts</div><div class="st-toggle-sub">Get notified when someone requests to join your network</div></div><button type="button" class="toggle" :class="{ on: network.requestAlerts }" @click="network.requestAlerts = !network.requestAlerts"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Weekly Network Digest Email</div><div class="st-toggle-sub">Summary of your network activity, new connections, and referral stats each week</div></div><button type="button" class="toggle" :class="{ on: network.weeklyDigest }" @click="network.weeklyDigest = !network.weeklyDigest"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">New Aegis Features &amp; Updates</div><div class="st-toggle-sub">Be the first to know about new platform features and improvements</div></div><button type="button" class="toggle" :class="{ on: network.featureUpdates }" @click="network.featureUpdates = !network.featureUpdates"></button></div>
              <div class="st-field" style="margin-top:16px"><label class="st-label">Geographic Focus for Suggestions</label><select class="form-select" v-model="network.geoFocus"><option>10 miles</option><option>25 miles</option><option>50 miles</option><option>100 miles</option><option>Statewide</option><option>Nationwide</option></select><div class="st-note">Aegis will prioritize providers in this range when suggesting connections</div></div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save</button></div>
            </div>
          </div>
        </template>

        <!-- APPEARANCE & TIMEZONE -->
        <template v-else-if="section === 'appearance'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span><div><div class="st-card-title">Appearance &amp; Timezone</div><div class="st-card-sub">Unified across all portals — theme and timezone apply everywhere on your account.</div></div></div></div>
            <div class="st-card-body">
              <div class="st-vis-label">Color Theme</div>
              <div class="st-theme-grid">
                <button v-for="t in themes" :key="t.key" type="button" class="st-theme-card" :class="{ active: appearance.theme === t.key }" @click="appearance.theme = t.key">
                  <span class="st-theme-swatch" :style="{ background: t.swatch }"></span>
                  <span class="st-theme-body"><span class="st-theme-name">{{ t.name }}</span><span class="st-theme-desc">{{ t.desc }}</span></span>
                </button>
              </div>
              <div class="st-divider"></div>
              <div class="st-subhead">Display</div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Dark Mode</div><div class="st-toggle-sub">Switch to dark surfaces across all portals</div></div><button type="button" class="toggle" :class="{ on: appearance.dark }" @click="appearance.dark = !appearance.dark"></button></div>
              <div class="st-field" style="margin-top:8px"><label class="st-label">Timezone</label><select class="form-select" v-model="appearance.tz"><option>Pacific Time (PT)</option><option>Mountain Time (MT)</option><option>Central Time (CT)</option><option>Eastern Time (ET)</option><option>UTC</option></select></div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save Appearance</button></div>
            </div>
          </div>
        </template>

        <!-- ACCESSIBILITY -->
        <template v-else-if="section === 'accessibility'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span><div><div class="st-card-title">Accessibility</div><div class="st-card-sub">Make Aegis work better for your needs</div></div></div></div>
            <div class="st-card-body">
              <div class="st-a11y-row">
                <span class="st-a11y-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>
                <div class="st-a11y-info"><div class="st-toggle-name">Font Size</div><div class="st-toggle-sub">Adjust interface text size (current: {{ a11y.font }}%)</div><input type="range" min="80" max="140" step="10" v-model="a11y.font" class="st-range" /></div>
              </div>
              <div class="st-a11y-row">
                <span class="st-a11y-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg></span>
                <div class="st-a11y-info"><div class="st-toggle-name">High Contrast Mode</div><div class="st-toggle-sub">Increase contrast for better readability</div></div>
                <button type="button" class="toggle" :class="{ on: a11y.contrast }" @click="a11y.contrast = !a11y.contrast"></button>
              </div>
              <div class="st-a11y-row">
                <span class="st-a11y-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span>
                <div class="st-a11y-info"><div class="st-toggle-name">Color Blind Friendly Mode</div><div class="st-toggle-sub">Replace red/green indicators with shapes and patterns</div></div>
                <button type="button" class="toggle" :class="{ on: a11y.colorblind }" @click="a11y.colorblind = !a11y.colorblind"></button>
              </div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save</button></div>
            </div>
          </div>
        </template>

        <!-- SUBSCRIPTION & PLAN -->
        <template v-else-if="section === 'billing'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></span><div><div class="st-card-title">Subscription &amp; Plan</div><div class="st-card-sub">Your current Aegis plan and available upgrades</div></div></div><span class="st-bp-badge">Continuity Practice</span></div>
            <div class="st-card-body">
              <div class="st-current-band">
                <div>
                  <div class="st-current-name">Continuity Practice — $49/mo</div>
                  <div class="st-current-meta">Billing cycle: Feb 1 – Mar 1, 2026 · Next invoice: $49 on Mar 1</div>
                </div>
                <button type="button" class="btn btn-outline btn-sm">Cancel Plan</button>
              </div>
              <div class="st-perks-band"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> <span><strong>Founding Member perks active</strong> — 2 additional Continuity Stewards free for life · 2 marketing ads/yr free (first 100 providers)</span></div>
              <div class="st-cycle-toggle">
                <span :class="{ active: !billing.annual }">Monthly</span>
                <button type="button" class="toggle" :class="{ on: billing.annual }" @click="billing.annual = !billing.annual"></button>
                <span :class="{ active: billing.annual }">Annual <span class="st-save-pill">Save 20%</span></span>
              </div>
              <div class="st-plan-grid">
                <div class="st-plan-tier">
                  <div class="st-plan-tier-name">Continuity Access</div>
                  <div class="st-plan-tier-price">$29<span>/mo</span></div>
                  <div class="st-plan-tier-alt">or $276/yr (save 20%)</div>
                  <div class="st-plan-feats">
                    <span v-for="f in accessFeatures" :key="f"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> {{ f }}</span>
                  </div>
                  <button type="button" class="btn btn-outline btn-sm st-plan-cta">Switch to this plan</button>
                </div>
                <div class="st-plan-tier current">
                  <span class="st-plan-tier-badge"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Current Plan</span>
                  <div class="st-plan-tier-name">Continuity Practice</div>
                  <div class="st-plan-tier-price">$49<span>/mo</span></div>
                  <div class="st-plan-tier-alt">or $468/yr (save 20%)</div>
                  <div class="st-plan-feats">
                    <span v-for="f in practiceFeatures" :key="f"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> {{ f }}</span>
                  </div>
                  <div class="st-plan-cta-current">Your current plan</div>
                </div>
              </div>
              <div class="st-included-head" style="margin-top:24px">Add-ons</div>
              <div class="st-note" style="margin-top:-4px;margin-bottom:14px">Stack on top of your base plan. Add or remove at any time.</div>
              <div class="st-addon-card">
                <span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                <div class="st-addon-body">
                  <div class="st-addon-head"><div class="st-addon-name">MAAT Professional CS <span class="st-addon-tag">MAAT Add-On</span></div><div class="st-addon-price">+<strong>$23</strong>/mo<div class="st-addon-billed">billed $276/yr</div></div></div>
                  <div class="st-addon-desc">A licensed, insured professional Continuity Steward — certified by MAAT — designated to your practice. Emergency response within 4 hours, annual recertification included.</div>
                  <div class="st-addon-feats">
                    <span v-for="f in maatFeatures" :key="f"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> {{ f }}</span>
                  </div>
                  <div class="st-addon-foot">
                    <button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Add MAAT Service</button>
                    <span class="st-addon-req">Requires Continuity Practice</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- BILLING & INVOICES -->
        <template v-else-if="section === 'invoices'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></span><div><div class="st-card-title">Billing &amp; Payment</div><div class="st-card-sub">Payment methods and invoice history</div></div></div><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Card</button></div>
            <div class="st-card-body">
              <div class="st-subhead" style="margin-bottom:2px">Financial Controls</div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Auto-Pay for Retainer Contracts</div><div class="st-toggle-sub">Automatically process monthly retainer payments via your payment method on file</div></div><button type="button" class="toggle" :class="{ on: financial.autoPay }" @click="financial.autoPay = !financial.autoPay"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Require Approval for Invoices Over $500</div><div class="st-toggle-sub">Flag invoices above this threshold for manual review before payment</div></div><button type="button" class="toggle" :class="{ on: financial.requireApproval }" @click="financial.requireApproval = !financial.requireApproval"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Monthly Spend Limit Alert</div><div class="st-toggle-sub">Notify me when monthly Business Partner spending exceeds my set limit</div></div><button type="button" class="toggle" :class="{ on: financial.spendLimit }" @click="financial.spendLimit = !financial.spendLimit"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Invoice Email Notifications</div><div class="st-toggle-sub">Send email confirmation for every invoice generated or paid</div></div><button type="button" class="toggle" :class="{ on: financial.invoiceEmails }" @click="financial.invoiceEmails = !financial.invoiceEmails"></button></div>
              <div class="st-divider"></div>
              <div class="st-subhead" style="margin-bottom:12px">Payment Methods</div>
              <div v-for="(pm, idx) in paymentMethods" :key="idx" class="st-pay-row" style="margin-bottom:10px">
                <span class="st-pay-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></span>
                <div class="st-pay-info"><div class="st-pay-num" style="font-family:var(--font-sans);letter-spacing:0;font-weight:700">{{ pm.label }}</div><div class="st-pay-exp">{{ pm.meta }}</div></div>
                <span v-if="pm.default" class="st-badge-green"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:3px"><polyline points="20 6 9 17 4 12"/></svg>Default</span>
                <button v-else type="button" class="btn btn-outline btn-sm">Set Default</button>
                <button type="button" class="st-link-btn st-remove">Remove</button>
              </div>
              <div class="st-divider"></div>
              <div class="st-subhead" style="margin-bottom:12px">Invoice History</div>
              <table class="st-invoice-table">
                <thead><tr><th>Date</th><th>Description</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                  <tr v-for="inv in invoices" :key="inv.date">
                    <td class="st-inv-date">{{ inv.date }}</td>
                    <td>{{ inv.desc }}</td>
                    <td class="st-inv-amount">{{ inv.amount }}</td>
                    <td><span class="st-inv-status"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Paid</span><button type="button" class="btn-icon st-inv-dl"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></button></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </template>

        <!-- INTEGRATIONS & APPS -->
        <template v-else-if="section === 'integrations'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></span><div><div class="st-card-title">Integrations &amp; Connected Apps</div><div class="st-card-sub">Third-party apps connected to your Aegis account</div></div></div><button type="button" class="btn btn-gold btn-sm" @click="showAddIntegration = true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Integration</button></div>
            <div class="st-card-body">
              <div v-for="(app, idx) in integrations" :key="app.name" class="st-integration-row" :class="{ 'no-border': idx === 0 }">
                <span class="st-integration-ico" v-html="app.icon"></span>
                <div class="st-integration-info"><div class="st-integration-name">{{ app.name }}</div><div class="st-integration-sub">{{ app.desc }}</div></div>
                <template v-if="app.connected"><span class="st-badge-green"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:3px"><polyline points="20 6 9 17 4 12"/></svg>Connected</span><button type="button" class="st-link-btn st-remove">Disconnect</button></template>
                <button v-else type="button" class="btn btn-gold btn-sm">Connect</button>
              </div>
            </div>
          </div>
        </template>

        <!-- API & WEBHOOKS -->
        <template v-else-if="section === 'api'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg></span><div><div class="st-card-title">API &amp; Webhooks</div><div class="st-card-sub">Developer access and third-party event hooks</div></div></div><button type="button" class="btn btn-gold btn-sm" @click="showCreateKey = true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> New API Key</button></div>
            <div class="st-card-body">
              <p class="st-api-intro">API keys allow external systems to interact with your Aegis account programmatically. Keep keys secret — do not share them.</p>
              <div v-for="key in apiKeys" :key="key.name" class="st-key-row">
                <div class="st-key-name">{{ key.name }}</div>
                <code class="st-key-value">{{ key.value }}</code>
                <button type="button" class="btn-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                <button type="button" class="btn-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>
                <button type="button" class="btn-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
              </div>
              <div class="st-divider"></div>
              <div class="st-subhead">Webhooks</div>
              <div class="st-field"><label class="st-label">Webhook Endpoint URL</label><input class="st-input" v-model="webhook.url" placeholder="https://your-system.com/aegis-webhook" /></div>
              <div class="st-vis-label" style="margin-top:6px">Events to Send</div>
              <div class="st-events-grid">
                <button v-for="ev in webhookEvents" :key="ev.name" type="button" class="st-event-chip" :class="{ active: ev.on }" @click="ev.on = !ev.on">
                  <span class="st-event-dot"></span> {{ ev.name }}
                </button>
              </div>
              <div class="st-card-actions"><button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Send Test Event</button><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save Webhook</button></div>
            </div>
          </div>
        </template>

        <!-- ACCOUNT ACTIONS -->
        <template v-else-if="section === 'changes'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico danger"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span><div><div class="st-card-title danger">Account Actions</div><div class="st-card-sub">Irreversible operations — proceed with caution</div></div></div></div>
            <div class="st-card-body">
              <div class="st-danger-card">
                <div class="st-danger-title"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Account Closure &amp; Data Management</div>
                <div class="st-danger-row"><div><div class="st-danger-name">Export All Data</div><div class="st-danger-sub">Download a complete copy of all your Aegis data (HIPAA compliant export)</div></div><button type="button" class="btn btn-outline btn-sm" @click="showExport = true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export</button></div>
                <div class="st-danger-row"><div><div class="st-danger-name">Pause Account</div><div class="st-danger-sub">Temporarily suspend your account. You won't appear in searches or receive referrals.</div></div><button type="button" class="btn btn-outline btn-sm" @click="showPause = true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Pause Account</button></div>
                <div class="st-danger-row"><div><div class="st-danger-name">Transfer Practice Records</div><div class="st-danger-sub">Transfer all client records and history to another Aegis provider</div></div><button type="button" class="btn btn-outline btn-sm" @click="showTransfer = true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> Transfer</button></div>
                <div class="st-danger-row"><div><div class="st-danger-name danger">Delete Account Permanently</div><div class="st-danger-sub">Permanently delete your Aegis account. This cannot be undone. All data will be erased after 30 days.</div></div><button type="button" class="btn btn-danger btn-sm" @click="showDelete = true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg> Delete Account</button></div>
              </div>
            </div>
          </div>
        </template>

        <!-- STUB FALLBACK -->
        <template v-else>
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico" :class="{ danger: activeNavItem.danger }" v-html="activeNavItem.icon"></span><div><div class="st-card-title" :class="{ danger: activeNavItem.danger }">{{ activeNavItem.label }}</div><div class="st-card-sub">{{ stubSub }}</div></div></div></div>
            <div class="st-card-body"><div class="st-stub">Settings for this section will appear here.</div></div>
          </div>
        </template>

      </div>
    </div>

    <!-- ═══ SET UP 2FA MODAL ═══ -->
    <Modal v-model="show2fa" title="Set Up Two-Factor Auth" size="sm">
      <p class="st-modal-text">Scan the QR code with your authenticator app, then enter the 6-digit code below to verify.</p>
      <div class="st-qr-box">
        <span class="st-qr-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><line x1="14" y1="14" x2="14" y2="14.01"/><line x1="21" y1="14" x2="21" y2="14.01"/><line x1="14" y1="21" x2="14" y2="21.01"/><line x1="21" y1="21" x2="21" y2="21.01"/><line x1="17.5" y1="17.5" x2="17.5" y2="17.51"/></svg></span>
        <div class="st-qr-cap">QR code would appear here</div>
        <div class="st-qr-secret">{{ totpSecret }}</div>
      </div>
      <div class="st-field" style="margin-bottom:0"><label class="st-label">Verification Code</label><input class="st-input st-otp" v-model="otpCode" maxlength="6" placeholder="1 2 3 4 5 6" /></div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="show2fa = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="show2fa = false">Verify &amp; Enable</button>
      </template>
    </Modal>

    <!-- ═══ BACKUP CODES MODAL ═══ -->
    <Modal v-model="showBackup" title="Backup Codes" size="sm">
      <p class="st-modal-text">Store these in a secure place. Each code can only be used once. Generate new ones if you've used most of them.</p>
      <div class="st-codes-grid">
        <span v-for="c in backupCodes" :key="c.code" class="st-code" :class="{ used: c.used }">{{ c.code }}<template v-if="c.used"> (used)</template></span>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copy All</button>
        <button type="button" class="btn btn-gold btn-sm">Regenerate</button>
        <button type="button" class="btn btn-outline btn-sm" @click="showBackup = false">Close</button>
      </template>
    </Modal>

    <!-- ═══ ADD INTEGRATION MODAL ═══ -->
    <Modal v-model="showAddIntegration" title="Add Integration" size="sm">
      <div class="st-integration-grid">
        <button v-for="opt in integrationOptions" :key="opt.name" type="button" class="st-integration-opt">
          <span class="st-integration-ico" v-html="opt.icon"></span>
          <span class="st-integration-opt-body"><span class="st-integration-opt-name">{{ opt.name }}</span><span class="st-integration-opt-desc">{{ opt.desc }}</span></span>
        </button>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showAddIntegration = false">Close</button>
      </template>
    </Modal>

    <!-- ═══ CREATE API KEY MODAL ═══ -->
    <Modal v-model="showCreateKey" title="Create API Key" size="sm">
      <div class="st-field"><label class="st-label">Key Name</label><input class="st-input" v-model="newKey.name" placeholder="e.g. My EHR Integration" /></div>
      <div class="st-field"><label class="st-label">Environment</label><select class="form-select" v-model="newKey.env"><option>Production</option><option>Development</option></select></div>
      <div class="st-field" style="margin-bottom:0"><label class="st-label">Expiry</label><select class="form-select" v-model="newKey.expiry"><option>30 days</option><option>90 days</option><option>1 year</option><option>Never</option></select></div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showCreateKey = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="generateKey">Generate Key</button>
      </template>
    </Modal>

    <!-- ═══ EXPORT ALL DATA MODAL ═══ -->
    <Modal v-model="showExport" title="Export All Data" size="sm">
      <p class="st-modal-text">Your HIPAA-compliant data export will be prepared and emailed to you within 24 hours. It includes:</p>
      <ul class="st-export-list">
        <li v-for="item in exportItems" :key="item">{{ item }}</li>
      </ul>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showExport = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showExport = false">Request Export</button>
      </template>
    </Modal>

    <!-- ═══ PAUSE MODAL ═══ -->
    <Modal v-model="showPause" title="Pause Account" size="sm">
      <p class="st-modal-text" style="margin-bottom:0">Your account will be temporarily suspended — you won't appear in searches or receive referrals, and your continuity steward will be notified. You can resume at any time.</p>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showPause = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showPause = false">Pause Account</button>
      </template>
    </Modal>

    <!-- ═══ TRANSFER PRACTICE RECORDS MODAL ═══ -->
    <Modal v-model="showTransfer" title="Transfer Practice Records" size="sm">
      <p class="st-modal-text">Transfer your client list and associated records to another Aegis provider. All clients will be notified per HIPAA requirements.</p>
      <div class="st-field"><label class="st-label">Receiving Provider</label><input class="st-input" v-model="transfer.provider" placeholder="Search by name or NPI..." /></div>
      <div class="st-field" style="margin-bottom:0"><label class="st-label">Transfer Scope</label><select class="form-select" v-model="transfer.scope"><option>All active clients</option><option>Selected clients only</option><option>Inactive/archived records only</option></select></div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showTransfer = false">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" @click="showTransfer = false">Initiate Transfer</button>
      </template>
    </Modal>

    <!-- ═══ DELETE ACCOUNT MODAL ═══ -->
    <Modal v-model="showDelete" title="Delete Account Permanently" size="sm">
      <p class="st-modal-text">This will permanently delete your Aegis account. Your continuity plan and assignments will be released and your steward notified. All data will be erased after 30 days.</p>
      <div class="st-deact-warn">This action cannot be undone.</div>
      <div class="st-field" style="margin-top:16px"><label class="st-label">Type DELETE to confirm</label><input class="st-input" v-model="deleteConfirm" placeholder="DELETE" /></div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showDelete = false">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" :disabled="deleteConfirm !== 'DELETE'" @click="showDelete = false">Delete Account</button>
      </template>
    </Modal>

    <!-- ═══ NEW API KEY RESULT MODAL ═══ -->
    <Modal v-model="showNewKey" title="Your New API Key" size="sm">
      <div class="st-key-warn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> <span>Copy this key now. You won't be able to view it again after closing this dialog.</span></div>
      <code class="st-newkey-value">{{ generatedKey }}</code>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showNewKey = false">Done</button>
        <button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copy Key</button>
      </template>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
import Modal from '../../Components/Modal.vue';
const props = defineProps({ user: Object });

const i = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
const phoneSvg = `<svg viewBox="0 0 24 24" ${i}><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>`;
const laptopSvg = `<svg viewBox="0 0 24 24" ${i}><rect x="2" y="4" width="20" height="13" rx="2"/><line x1="2" y1="21" x2="22" y2="21"/></svg>`;

const displayName = computed(() => props.user?.display_name || 'Dr. Sarah Johnson');
const initials = computed(() => displayName.value.replace(/^Dr\.?\s+/i, '').split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase());

const nav = [
  { group: 'Account', items: [
    { key: 'profile', label: 'Profile & Identity', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>` },
    { key: 'account', label: 'Account & Login', icon: `<svg viewBox="0 0 24 24" ${i}><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>` },
    { key: 'security', label: 'Security & 2FA', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
  ]},
  { group: 'Communications', items: [
    { key: 'notifications', label: 'Notifications', badge: 2, icon: `<svg viewBox="0 0 24 24" ${i}><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>` },
    { key: 'messaging', label: 'Messaging', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>` },
    { key: 'email', label: 'Email Preferences', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>` },
  ]},
  { group: 'Clinical', items: [
    { key: 'availability', label: 'Availability & Hours', icon: `<svg viewBox="0 0 24 24" ${i}><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>` },
    { key: 'referrals', label: 'Referral Preferences', icon: `<svg viewBox="0 0 24 24" ${i}><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>` },
  ]},
  { group: 'Operations', items: [
    { key: 'csettings', label: 'Continuity Steward Settings', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
    { key: 'ssettings', label: 'Support Steward Settings', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>` },
    { key: 'vault', label: 'Document Vault Access', icon: `<svg viewBox="0 0 24 24" ${i}><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>` },
    { key: 'agreements', label: 'Agreements & Contracts', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>` },
  ]},
  { group: 'Platform', items: [
    { key: 'myservices', label: 'My Services Settings', icon: `<svg viewBox="0 0 24 24" ${i}><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>` },
    { key: 'privacy', label: 'Privacy & Visibility', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
    { key: 'network', label: 'Network Settings', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>` },
    { key: 'appearance', label: 'Appearance & Timezone', icon: `<svg viewBox="0 0 24 24" ${i}><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>` },
    { key: 'accessibility', label: 'Accessibility', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>` },
  ]},
  { group: 'Billing', items: [
    { key: 'billing', label: 'Subscription & Plan', icon: `<svg viewBox="0 0 24 24" ${i}><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>` },
    { key: 'invoices', label: 'Billing & Invoices', icon: `<svg viewBox="0 0 24 24" ${i}><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>` },
  ]},
  { group: 'Developer', items: [
    { key: 'integrations', label: 'Integrations & Apps', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>` },
    { key: 'api', label: 'API & Webhooks', icon: `<svg viewBox="0 0 24 24" ${i}><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>` },
  ]},
  { group: 'Account Closure & Data Management', items: [
    { key: 'changes', label: 'Account Actions', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`, danger: true },
  ]},
];

const section = ref('profile');
const activeNavItem = computed(() => nav.flatMap(g => g.items).find(it => it.key === section.value) || {});
const stubSub = computed(() => `Manage your ${activeNavItem.value.label?.toLowerCase()} preferences`);

const showPw = ref(false);
const acct = reactive({ email: 'sarah.johnson@lotuspsychology.com', recovery: '', phone: '(415) 555-0124', handle: '@dr.sarah.johnson', current: 'placeholder1', newpw: '', confirmpw: '' });

const sessions = [
  { device: 'MacBook Pro — Chrome 121', meta: 'New York, NY · Last active just now · 192.168.1.45', current: true, mobile: false },
  { device: 'iPhone 15 Pro — Safari', meta: 'New York, NY · Last active 2h ago · 73.x.x.x', current: false, mobile: true },
  { device: 'Windows PC — Firefox 122', meta: 'Unrecognized location · Last active 3 days ago', current: false, mobile: false },
];

const securityAlerts = reactive([
  { name: 'Alert on New Login', sub: 'Receive email when a new device logs into your account', on: true },
  { name: 'Suspicious Activity Alerts', sub: 'Notify me of unusual login attempts or access from new locations', on: true },
  { name: 'Session Timeout (30 min inactivity)', sub: 'Automatically log out after 30 minutes of inactivity', on: true },
]);

// 2FA + backup code modals
const show2fa = ref(false);
const showBackup = ref(false);
const otpCode = ref('');
const totpSecret = 'JBSW Y3DP EHPK 3PXP';
const backupCodes = [
  { code: 'KL-4J8M-92WX', used: false },
  { code: 'KL-7P2N-56TQ', used: false },
  { code: 'KL-3R9K-81ZV', used: false },
  { code: 'KL-6C4H-27YB', used: false },
  { code: 'KL-1M5F-49LD', used: true },
  { code: 'KL-8A3E-65RP', used: false },
];

const notif = reactive({ quietFrom: '22:00', quietTo: '08:00', digest: 'Daily (8 AM)', lead: '1 day before' });
const notifRows = reactive([
  { name: 'New Referrals', sub: 'When you receive a referral request', push: true, email: true, sms: true, inapp: true },
  { name: 'Referral Updates', sub: 'Status changes on existing referrals', push: true, email: true, sms: false, inapp: true },
  { name: 'New Messages', sub: 'From other providers or Support Stewards', push: true, email: false, sms: false, inapp: true },
  { name: 'Agreement Alerts', sub: 'Signing requests and expiry warnings', push: true, email: true, sms: false, inapp: true },
  { name: 'Continuity Steward Events', sub: 'Activation, agreement, check-ins', push: true, email: true, sms: true, inapp: true },
  { name: 'Document Access', sub: 'When someone views your vault docs', push: false, email: false, sms: false, inapp: true },
  { name: 'Network Updates', sub: 'New connection requests and approvals', push: false, email: true, sms: false, inapp: true },
  { name: 'News & Resources', sub: 'New posts in your feed, replies to your comments, and poll results', push: false, email: true, sms: false, inapp: true },
  { name: 'Events & CEUs', sub: 'New events in your categories, RSVP confirmations, and reminders', push: true, email: true, sms: false, inapp: true },
  { name: 'Client Alerts', sub: 'Crisis flags and portal messages', push: true, email: true, sms: true, inapp: true },
  { name: 'Billing & Invoices', sub: 'Payment confirmations and failed charges', push: false, email: true, sms: false, inapp: true },
  { name: 'Platform Updates', sub: 'New features and maintenance windows', push: false, email: false, sms: false, inapp: false },
]);

const msg = reactive({ who: 'Connected providers only', status: 'Available', readReceipts: true, onlineStatus: true, autoFlag: true, away: '' });

const email = reactive({ digest: 'Weekly' });
const emailToggles = reactive({
  weekly: { name: 'Weekly Platform Digest', sub: 'Summary of network activity, referrals, and Aegis updates', on: true },
  activity: { name: 'Referral Activity Summary', sub: 'Digest of your referral in/outbox — new, accepted, and closed', on: true },
  product: { name: 'Product Updates', sub: 'New Aegis features, improvements, and release notes', on: false },
  unsub: { name: 'Unsubscribe from All Optional Emails', sub: 'Only transactional and security emails will be sent — notifications, alerts, invoices', on: false },
});

const availability = reactive({
  acceptingClients: true,
  days: [
    { day: 'Mon', active: true, opens: '09:00', closes: '17:00' },
    { day: 'Tue', active: true, opens: '09:00', closes: '17:00' },
    { day: 'Wed', active: true, opens: '10:00', closes: '18:00' },
    { day: 'Thu', active: true, opens: '09:00', closes: '17:00' },
    { day: 'Fri', active: true, opens: '09:00', closes: '14:00' },
    { day: 'Sat', active: false, opens: '', closes: '' },
    { day: 'Sun', active: false, opens: '', closes: '' },
  ],
  states: 'NY, NJ, CT, PA',
  nextAppt: '2026-03-05',
});

const referralPrefs = reactive({
  accepting: true,
  autoAccept: false,
  suggestAlternatives: true,
  autoArchive: true,
});

const csSettings = reactive({ emergencyActivation: true, annualCheckIn: true, notifyOnChanges: true });
const ssSettings = reactive({ notifyIncident: true, notifyChanges: true, annualAttestation: true });
const vaultNotif = reactive({ onAccess: true, onEmergency: true });

const agreementsList = [
  { name: 'Continuity Steward Agreement', meta: 'Marcus Chen — Signed Jun 15, 2024 · Reviewed annually' },
  { name: 'Support Steward Agreement', meta: 'Linda Johnson — Signed Oct 5, 2024 · Annual re-attestation required' },
];
const agreementsNotif = reactive({ expiryReminder: true, autoRenew: false, notifyCountersign: true });

const myServices = reactive({
  mode: true,
  showPublic: true,
  acceptBookings: true,
  autoConfirm: false,
  showPricing: true,
  visibleToBP: true,
  acceptProposals: false,
  slidingScale: true,
  bookingExpiry: '48 hours',
  buffer: '30 min',
  hourlyRate: 150,
  paymentMethod: 'Stripe (Direct Transfer)',
});

const appearance = reactive({ theme: 'gold', dark: false, tz: 'Pacific Time (PT)' });
const themes = [
  { key: 'gold', name: 'Aegis Gold', desc: 'Classic warm gold (default)', swatch: 'linear-gradient(135deg,#caa45a,#9a7b34)' },
  { key: 'dark', name: 'Gold Dark', desc: 'Deep rich gold palette', swatch: 'linear-gradient(135deg,#8a6a20,#3d2f12)' },
  { key: 'deep', name: 'Gold Deep', desc: 'Refined deep gold system', swatch: 'linear-gradient(135deg,#9c8a5e,#5c4a26)' },
];
const a11y = reactive({ font: 100, contrast: false, colorblind: false });

const privacy = reactive({ level: 'network', search: true, icn: true, ratings: true, location: true, referralStats: true, demographics: true });
const visLevels = [
  { key: 'public', name: 'Public', desc: 'Visible to all Aegis providers', icon: `<svg viewBox="0 0 24 24" ${i}><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>` },
  { key: 'network', name: 'Network', desc: 'Connected providers only', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>` },
  { key: 'private', name: 'Private', desc: 'Invitation only', icon: `<svg viewBox="0 0 24 24" ${i}><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>` },
];

const network = reactive({
  manualApproval: false,
  icnMatching: true,
  dataUse: true,
  mutualConnections: true,
  hideFromBP: false,
  requestAlerts: true,
  weeklyDigest: true,
  featureUpdates: false,
  geoFocus: '50 miles',
});

const billing = reactive({ annual: false });
const accessFeatures = [
  'Limited dashboard view',
  '1 Continuity Steward included',
  '2 Support Stewards included',
  'Core continuity planning',
  'Continuity Plan',
  'Document Vault',
  'Integrative Care Network (limited)',
];
const practiceFeatures = [
  'Full dashboard access',
  'Up to 2 Continuity Stewards',
  '2 Support Stewards included',
  'All continuity features',
  'Continuity Plan',
  'Full Document Vault',
  'Full Integrative Care Network',
  'Integrative Care Network Matching',
  'Business Partners access',
];
const maatFeatures = [
  'Licensed & insured CS, certified by MAAT',
  '4-hour emergency response guarantee',
  'Annual CS recertification included',
];

const financial = reactive({ autoPay: true, requireApproval: true, spendLimit: false, invoiceEmails: true });
const paymentMethods = [
  { label: 'Visa ending in 4242', meta: 'Expires 09/28 · Default payment method', default: true },
  { label: 'Mastercard ending in 8743', meta: 'Expires 03/27 · Backup card', default: false },
];
const invoices = [
  { date: 'Feb 1, 2026', amount: '$49.00', desc: 'Continuity Practice — February' },
  { date: 'Jan 1, 2026', amount: '$49.00', desc: 'Continuity Practice — January' },
  { date: 'Dec 1, 2025', amount: '$49.00', desc: 'Continuity Practice — December' },
];

const cardSvg = `<svg viewBox="0 0 24 24" ${i}><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>`;
const calSvg = `<svg viewBox="0 0 24 24" ${i}><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>`;
const docSvg = `<svg viewBox="0 0 24 24" ${i}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>`;
const pulseSvg = `<svg viewBox="0 0 24 24" ${i}><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>`;

const showAddIntegration = ref(false);
const showCreateKey = ref(false);
const integrations = [
  { name: 'Google Calendar', desc: 'Sync availability and appointments automatically', connected: true, icon: calSvg },
  { name: 'SimplePractice EHR', desc: 'Sync client records and session notes', connected: true, icon: docSvg },
  { name: 'DrFirst (e-Prescribing)', desc: 'Send prescriptions electronically', connected: true, icon: pulseSvg },
  { name: 'Zoom for Healthcare', desc: 'HIPAA-compliant video sessions', connected: false, icon: pulseSvg },
  { name: 'DocuSign', desc: 'Advanced e-signatures for agreements', connected: false, icon: docSvg },
  { name: 'Stripe Connect', desc: 'Accept payments from Business Partners and process service contracts via Stripe Connect', connected: false, icon: cardSvg },
];
const integrationOptions = [
  { name: 'Epic EHR', desc: 'Client records sync', icon: pulseSvg },
  { name: 'Zoom Health', desc: 'HIPAA video sessions', icon: pulseSvg },
  { name: 'DocuSign', desc: 'E-signature', icon: docSvg },
  { name: 'Stripe Connect', desc: 'Payment processing for service contracts and retainers', icon: cardSvg },
];

const apiKeys = [
  { name: 'Production Key', value: 'k1_prod_••••••••••••••••••3f8a' },
  { name: 'Development Key', value: 'k1_dev_••••••••••••••••••9c21' },
];
const webhook = reactive({ url: '' });
const webhookEvents = reactive([
  { name: 'Referral Received', on: true },
  { name: 'Agreement Signed', on: true },
  { name: 'New Connection', on: false },
  { name: 'Document Uploaded', on: false },
  { name: 'Continuity Steward Activated', on: false },
  { name: 'Client Alert', on: true },
]);
const newKey = reactive({ name: '', env: 'Production', expiry: '1 year' });

const showPause = ref(false);
const showExport = ref(false);
const exportItems = [
  'Full profile and demographics',
  'Client records (de-identified as required)',
  'Referral history',
  'Agreements and contracts',
  'Document vault (encrypted zip)',
  'Network and communication logs',
];
const showTransfer = ref(false);
const transfer = reactive({ provider: '', scope: 'All active clients' });
const showDelete = ref(false);
const deleteConfirm = ref('');

const showNewKey = ref(false);
const generatedKey = 'k1_prod_aB3dEf7gHiJkLmNoPqRsTuVwXyZ1234567890abcdef';
function generateKey() {
  showCreateKey.value = false;
  showNewKey.value = true;
}
</script>

<style scoped>
.st-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:24px 30px; margin-bottom:16px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.st-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.st-title { font-family:var(--font-serif); font-size:30px; font-weight:600; letter-spacing:-0.4px; color:var(--text); margin:0; line-height:1.05; }
.st-meta { display:flex; align-items:center; gap:18px; flex-wrap:wrap; margin-top:10px; }
.st-meta span { display:inline-flex; align-items:center; gap:6px; font-size:12px; color:var(--text-3); }
.st-meta svg { color:var(--text-4); }
.st-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; }

.st-layout { display:grid; grid-template-columns:248px 1fr; gap:16px; align-items:start; }
.st-nav { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:8px; box-shadow:var(--shadow-xs); position:sticky; top:16px; }
.st-nav-head { display:flex; align-items:center; gap:9px; font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); padding:12px 14px 14px; }
.st-nav-ico { width:30px; height:30px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; }
.st-nav-group { font-size:9.5px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); padding:14px 14px 7px; }
.st-nav-item { display:flex; align-items:center; gap:11px; width:100%; text-align:left; padding:9px 14px; font-size:12.5px; font-weight:500; color:var(--text-2); background:none; border:none; border-left:2.5px solid transparent; border-radius:0 var(--radius) var(--radius) 0; cursor:pointer; transition:all .14s ease; }
.st-nav-item:hover { background:var(--surface-2); }
.st-nav-item.active { background:var(--badge-bg-gold); border-left-color:var(--gold-dark); color:var(--gold-dark); font-weight:600; }
.st-nav-item.danger { color:var(--red); }
.st-nav-item-ico { display:inline-flex; color:var(--text-4); }
.st-nav-item.active .st-nav-item-ico { color:var(--gold-dark); }
.st-nav-item.danger .st-nav-item-ico { color:var(--red); }
.st-nav-item-ico :deep(svg) { width:15px; height:15px; }
.st-nav-item-label { flex:1; min-width:0; }
.st-nav-badge { font-size:10px; font-weight:700; min-width:18px; height:18px; padding:0 5px; border-radius:var(--radius-full); background:var(--red); color:#fff; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }

.st-panel { min-width:0; }
.st-banner { display:flex; align-items:flex-start; gap:10px; font-size:12.5px; color:#3b6fb5; background:#e9f0f9; border:1px solid #cfdef0; border-radius:var(--radius); padding:13px 16px; margin-bottom:16px; line-height:1.5; }
.st-banner svg { flex-shrink:0; margin-top:1px; }
.st-banner strong { font-weight:700; }
.st-banner.blue { margin-bottom:20px; }

.st-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); margin-bottom:16px; overflow:hidden; }
.st-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; padding:20px 24px; border-bottom:1px solid var(--border); }
.st-card-head-l { display:flex; align-items:flex-start; gap:12px; }
.st-card-ico { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.st-card-ico.danger { background:var(--red-light); color:var(--red); }
.st-card-ico :deep(svg) { width:16px; height:16px; }
.st-card-title { font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); }
.st-card-title.danger { color:var(--red); }
.st-card-sub { font-size:12px; color:var(--text-3); margin-top:3px; }
.st-head-btns { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.st-link-btn { font-size:12.5px; font-weight:600; color:var(--text-3); background:none; border:none; cursor:pointer; }
.st-card-body { padding:22px 24px; }

.st-profile-card { display:flex; align-items:center; gap:16px; border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px 20px; margin:22px 24px; }
.st-av { border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); display:inline-flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; flex-shrink:0; }
.st-av.lg { width:54px; height:54px; font-size:18px; }
.st-profile-info { flex:1; min-width:0; }
.st-profile-name { font-size:16px; font-weight:700; color:var(--text); }
.st-profile-role { font-size:12.5px; color:var(--text-3); margin-top:2px; }
.st-profile-contacts { display:flex; align-items:center; gap:18px; flex-wrap:wrap; margin-top:9px; }
.st-profile-contacts span { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; color:var(--text-3); }
.st-profile-contacts svg { color:var(--gold-dark); }
.st-bp-badge { font-size:9.5px; font-weight:700; letter-spacing:.5px; padding:3px 9px; border-radius:var(--radius-full); background:var(--badge-bg-gold); color:var(--gold-dark); flex-shrink:0; align-self:flex-start; text-transform:uppercase; }
.st-card-foot { display:flex; justify-content:flex-end; padding:14px 24px; border-top:1px solid var(--border); background:var(--surface-2); }

.st-portal-row { display:flex; align-items:center; gap:13px; border:1px solid var(--border); border-radius:var(--radius-lg); padding:14px 16px; }
.st-portal-ico { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.st-portal-info { flex:1; min-width:0; }
.st-portal-name { font-size:13.5px; font-weight:700; color:var(--text); }
.st-portal-sub { font-size:12px; color:var(--text-3); margin-top:2px; }
.st-info-band { display:flex; align-items:flex-start; gap:9px; font-size:12px; color:var(--text-3); background:var(--badge-bg-gold); border-radius:var(--radius); padding:12px 16px; margin-top:14px; line-height:1.5; }
.st-info-band svg { color:var(--gold-dark); flex-shrink:0; margin-top:1px; }
.st-info-band strong { color:var(--text-2); font-weight:700; }

.st-agreement-row { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px 0; border-top:1px solid var(--border); }
.st-agreement-row:first-of-type { border-top:none; }
.st-agreement-info { flex:1; min-width:0; }
.st-agreement-name { font-size:13.5px; font-weight:700; color:var(--text); }
.st-agreement-sub { font-size:12px; color:var(--text-3); margin-top:2px; }

.st-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:4px; }
.st-grid4 { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:22px; }
.st-field { margin-bottom:14px; }
.st-label { display:block; font-size:12px; font-weight:600; color:var(--text-2); margin-bottom:7px; }
.st-req { color:var(--red); }
.st-input { display:block; width:100%; padding:10px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.st-input:focus { border-color:var(--gold-dark); }
.st-input::placeholder { color:var(--text-4); }
.st-textarea { resize:vertical; min-height:84px; line-height:1.55; font-family:var(--font-sans); }
.st-input-wrap { position:relative; }
.st-eye { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--text-4); cursor:pointer; padding:4px; }
.st-note { font-size:11px; color:var(--text-4); margin-top:6px; }
.st-note.green { color:var(--green-dark); display:flex; align-items:center; gap:5px; }
.st-divider { height:1px; background:var(--border); margin:18px 0; }
.st-subhead { font-size:13.5px; font-weight:700; color:var(--text); margin-bottom:14px; }
.st-card-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:18px; padding-top:16px; border-top:1px solid var(--border); }

.st-session { display:flex; align-items:center; gap:13px; border:1px solid var(--border); border-radius:var(--radius); padding:13px 16px; margin-bottom:10px; }
.st-session:last-child { margin-bottom:0; }
.st-session-ico { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.st-session-ico :deep(svg) { width:16px; height:16px; }
.st-session-info { flex:1; min-width:0; }
.st-session-name { font-size:13px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; }
.st-current { font-size:9.5px; font-weight:700; padding:2px 8px; border-radius:var(--radius-full); background:var(--green-light); color:var(--green-dark); }
.st-session-meta { font-size:11.5px; color:var(--text-4); margin-top:2px; }

.st-badge-green { font-size:10px; font-weight:700; letter-spacing:.5px; padding:5px 12px; border-radius:var(--radius-full); background:var(--green-light); color:var(--green-dark); flex-shrink:0; }
.st-badge-blue { font-size:10px; font-weight:700; letter-spacing:.5px; padding:5px 12px; border-radius:var(--radius-full); background:#e3edfa; color:#3b6fb5; flex-shrink:0; }

.st-2fa-row { display:flex; align-items:center; gap:14px; padding:15px 16px; border:1px solid var(--border); border-radius:var(--radius-lg); margin-bottom:12px; }
.st-2fa-row.active { background:var(--surface-2); }
.st-2fa-ico { width:36px; height:36px; border-radius:50%; background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.st-2fa-row.active .st-2fa-ico { background:var(--badge-bg-gold); color:var(--gold-dark); }
.st-2fa-info { flex:1; min-width:0; }
.st-2fa-name { font-size:13.5px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; }
.st-2fa-active { font-size:11px; font-weight:600; color:var(--green-dark); }
.st-2fa-sub { font-size:12px; color:var(--text-3); margin-top:2px; }

.st-toggle-row { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:15px 0; border-top:1px solid var(--border); }
.st-toggle-row:first-of-type { border-top:none; }
.st-toggle-name { font-size:13.5px; font-weight:700; color:var(--text); }
.st-toggle-sub { font-size:12px; color:var(--text-3); margin-top:2px; }

.st-matrix { width:100%; border-collapse:collapse; }
.st-matrix th { text-align:left; font-size:9.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--text-4); padding:10px 8px; border-bottom:1px solid var(--border); }
.st-matrix th.c, .st-matrix td.c { text-align:center; width:70px; }
.st-matrix th.c { white-space:nowrap; }
.st-matrix th.c svg { vertical-align:-1px; margin-right:3px; }
.st-matrix td { padding:14px 8px; border-bottom:1px solid var(--border); }
.st-mx-name { font-size:13px; font-weight:700; color:var(--text); }
.st-mx-sub { font-size:11.5px; color:var(--text-3); margin-top:3px; line-height:1.45; }
.st-check { width:17px; height:17px; accent-color:var(--gold-dark); cursor:pointer; }

.st-head-inline { display:flex; align-items:center; gap:11px; flex-shrink:0; }
.st-inline-toggle-label { font-size:12.5px; font-weight:600; color:var(--text-2); }

.st-hours { width:100%; border-collapse:collapse; }
.st-hours th { text-align:left; font-size:9.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--text-4); padding:10px 8px; border-bottom:1px solid var(--border); }
.st-hours th.c, .st-hours td.c { text-align:center; width:64px; }
.st-hours td { padding:9px 8px; border-bottom:1px solid var(--border); vertical-align:middle; }
.st-hours tr:last-child td { border-bottom:none; }
.st-hours-day { font-size:13px; font-weight:700; color:var(--text); width:80px; }
.st-hours .st-input { padding:8px 11px; }
.st-hours-closed { font-size:12.5px; color:var(--text-4); font-style:italic; }

.st-vis-label { font-size:12.5px; font-weight:600; color:var(--text-2); margin-bottom:12px; }
.st-theme-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
.st-theme-card { display:flex; flex-direction:column; text-align:left; padding:0; background:var(--surface); border:1.5px solid var(--border-dark); border-radius:var(--radius-lg); overflow:hidden; cursor:pointer; transition:all .15s ease; }
.st-theme-card:hover { border-color:var(--gold-dark); }
.st-theme-card.active { border-color:var(--gold-dark); box-shadow:0 0 0 1px var(--badge-bg-gold); }
.st-theme-swatch { display:block; height:54px; }
.st-theme-body { padding:12px 14px; }
.st-theme-name { display:block; font-size:13px; font-weight:700; color:var(--text); }
.st-theme-desc { display:block; font-size:11px; color:var(--text-3); margin-top:3px; }

.st-vis-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:6px; }
.st-vis-card { display:flex; flex-direction:column; align-items:center; text-align:center; gap:8px; padding:20px 16px; background:var(--surface); border:1.5px solid var(--border-dark); border-radius:var(--radius-lg); cursor:pointer; transition:all .15s ease; }
.st-vis-card:hover { border-color:var(--gold-dark); }
.st-vis-card.active { border-color:var(--gold-dark); background:var(--badge-bg-gold); }
.st-vis-ico { color:var(--text-3); }
.st-vis-card.active .st-vis-ico { color:var(--gold-dark); }
.st-vis-ico :deep(svg) { width:22px; height:22px; }
.st-vis-name { font-size:14px; font-weight:700; color:var(--text); }
.st-vis-desc { font-size:11.5px; color:var(--text-3); line-height:1.45; }

.st-a11y-row { display:flex; align-items:flex-start; gap:14px; padding:16px 0; border-top:1px solid var(--border); }
.st-a11y-row:first-of-type { border-top:none; }
.st-a11y-ico { width:32px; height:32px; border-radius:var(--radius-sm); background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px; }
.st-a11y-info { flex:1; min-width:0; }
.st-range { width:100%; margin-top:12px; accent-color:var(--gold-dark); }

.st-stub { font-size:13px; color:var(--text-4); padding:16px 0; }

/* billing */
.st-plan-card { border:1.5px solid var(--gold-dark); border-radius:var(--radius-lg); padding:22px 24px; background:var(--surface-2); }
.st-plan-current { display:inline-flex; align-items:center; gap:6px; font-size:10px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:5px 13px; border-radius:var(--radius-full); background:var(--gold-dark); color:var(--text-inverted); margin-bottom:12px; }
.st-plan-name { font-family:var(--font-serif); font-size:20px; font-weight:600; color:var(--text); }
.st-plan-price { font-family:var(--font-serif); font-size:28px; font-weight:700; color:var(--gold-dark); margin-top:8px; }
.st-plan-price span { font-size:15px; font-weight:600; color:var(--text-3); }
.st-plan-alt-inline { font-family:var(--font-sans); font-size:13px; font-weight:500; color:var(--text-3); }
.st-plan-desc { font-size:12.5px; color:var(--text-2); margin-top:10px; line-height:1.5; }
.st-plan-renew { font-size:12px; color:var(--text-4); margin-top:8px; }
.st-included-head { font-size:14px; font-weight:700; color:var(--text); margin:24px 0 12px; }
.st-included-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px 24px; }
.st-included-grid span { display:flex; align-items:center; gap:9px; font-size:12.5px; color:var(--text-2); }
.st-included-grid svg { color:var(--green-dark); flex-shrink:0; }
.st-pay-row { display:flex; align-items:center; gap:13px; border:1px solid var(--border); border-radius:var(--radius); padding:13px 16px; }
.st-pay-badge { font-size:9.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; padding:5px 10px; border-radius:var(--radius-sm); background:var(--surface-3); color:var(--text-3); flex-shrink:0; }
.st-pay-info { flex:1; min-width:0; }
.st-pay-num { font-size:13px; font-weight:700; color:var(--text); font-family:ui-monospace,monospace; letter-spacing:1px; }
.st-pay-exp { font-size:11.5px; color:var(--text-4); margin-top:2px; }
.st-billing-foot { font-size:12.5px; color:var(--text-3); margin-top:16px; }
.st-inline-link { color:var(--gold-dark); font-weight:600; text-decoration:none; }

.st-current-band { display:flex; align-items:center; justify-content:space-between; gap:16px; border:1px solid var(--border-dark); border-radius:var(--radius-lg); padding:16px 20px; background:var(--surface-2); margin-bottom:14px; }
.st-current-name { font-family:var(--font-serif); font-size:17px; font-weight:600; color:var(--text); }
.st-current-meta { font-size:12px; color:var(--text-3); margin-top:4px; }
.st-perks-band { display:flex; align-items:flex-start; gap:10px; font-size:12.5px; color:var(--gold-dark); background:var(--badge-bg-gold); border-radius:var(--radius); padding:13px 16px; line-height:1.5; margin-bottom:20px; }
.st-perks-band svg { flex-shrink:0; margin-top:1px; }
.st-perks-band strong { font-weight:700; }
.st-cycle-toggle { display:flex; align-items:center; justify-content:center; gap:12px; margin-bottom:18px; }
.st-cycle-toggle span { font-size:13px; font-weight:600; color:var(--text-4); display:inline-flex; align-items:center; gap:8px; }
.st-cycle-toggle span.active { color:var(--text); }
.st-save-pill { font-size:10px; font-weight:700; padding:2px 8px; border-radius:var(--radius-full); background:var(--green-light); color:var(--green-dark); }
.st-plan-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.st-plan-tier { position:relative; border:1.5px solid var(--border-dark); border-radius:var(--radius-lg); padding:24px 22px; text-align:center; }
.st-plan-tier.current { border-color:var(--gold-dark); background:var(--surface-2); box-shadow:0 0 0 1px var(--badge-bg-gold); }
.st-plan-tier-badge { position:absolute; top:-11px; left:50%; transform:translateX(-50%); display:inline-flex; align-items:center; gap:5px; font-size:9.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:5px 12px; border-radius:var(--radius-full); background:var(--gold-dark); color:var(--text-inverted); white-space:nowrap; }
.st-plan-tier-name { font-family:var(--font-serif); font-size:18px; font-weight:600; color:var(--text); }
.st-plan-tier-price { font-family:var(--font-serif); font-size:32px; font-weight:700; color:var(--gold-dark); margin-top:10px; }
.st-plan-tier-price span { font-size:15px; font-weight:600; color:var(--text-3); }
.st-plan-tier-alt { font-size:11.5px; color:var(--text-4); margin-top:4px; }
.st-plan-feats { display:flex; flex-direction:column; gap:10px; text-align:left; margin:20px 0; }
.st-plan-feats span { display:flex; align-items:center; gap:9px; font-size:12.5px; color:var(--text-2); }
.st-plan-feats svg { color:var(--green-dark); flex-shrink:0; }
.st-plan-cta { width:100%; justify-content:center; }
.st-plan-cta-current { font-size:12.5px; font-weight:700; color:var(--gold-dark); text-align:center; padding:9px 0; }
.st-addon-card { display:flex; gap:14px; border:1.5px solid var(--gold-dark); border-radius:var(--radius-lg); padding:18px 20px; background:var(--surface-2); }
.st-addon-body { flex:1; min-width:0; }
.st-addon-head { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; }
.st-addon-name { font-size:14px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:9px; flex-wrap:wrap; }
.st-addon-tag { font-size:9px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:3px 8px; border-radius:var(--radius-full); background:var(--gold-dark); color:var(--text-inverted); }
.st-addon-price { font-size:13px; color:var(--text-3); text-align:right; flex-shrink:0; }
.st-addon-price strong { font-size:18px; font-weight:700; color:var(--gold-dark); }
.st-addon-billed { font-size:10.5px; color:var(--text-4); margin-top:2px; }
.st-addon-desc { font-size:12.5px; color:var(--text-2); line-height:1.55; margin:8px 0 12px; }
.st-addon-feats { display:flex; flex-wrap:wrap; gap:8px 20px; margin-bottom:14px; }
.st-addon-feats span { display:inline-flex; align-items:center; gap:7px; font-size:12px; color:var(--text-2); }
.st-addon-feats svg { color:var(--green-dark); flex-shrink:0; }
.st-addon-foot { display:flex; align-items:center; gap:14px; }
.st-addon-req { font-size:11.5px; color:var(--text-4); }

.st-pay-ico { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.st-remove { color:var(--red); }
.st-invoice-table { width:100%; border-collapse:collapse; }
.st-invoice-table th { text-align:left; font-size:9.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--text-4); padding:10px 8px; border-bottom:1px solid var(--border); }
.st-invoice-table td { font-size:13px; color:var(--text-2); padding:14px 8px; border-bottom:1px solid var(--border); }
.st-invoice-table tr:last-child td { border-bottom:none; }
.st-inv-date { font-weight:700; color:var(--text); white-space:nowrap; }
.st-inv-amount { font-weight:700; color:var(--text); }
.st-inv-status { display:inline-flex; align-items:center; gap:5px; font-size:12.5px; font-weight:600; color:var(--green-dark); }
.st-inv-dl { margin-left:14px; }

.st-integration-row { display:flex; align-items:center; gap:14px; padding:16px 0; border-top:1px solid var(--border); }
.st-integration-row.no-border { border-top:none; padding-top:4px; }
.st-integration-ico { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.st-integration-ico :deep(svg) { width:17px; height:17px; }
.st-integration-info { flex:1; min-width:0; }
.st-integration-name { font-size:13.5px; font-weight:700; color:var(--text); }
.st-integration-sub { font-size:12px; color:var(--text-3); margin-top:2px; }
.st-integration-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.st-integration-opt { display:flex; align-items:center; gap:12px; text-align:left; padding:16px; background:var(--surface); border:1.5px solid var(--border-dark); border-radius:var(--radius-lg); cursor:pointer; transition:all .15s ease; }
.st-integration-opt:hover { border-color:var(--gold-dark); background:var(--surface-2); }
.st-integration-opt-body { min-width:0; }
.st-integration-opt-name { display:block; font-size:13.5px; font-weight:700; color:var(--text); }
.st-integration-opt-desc { display:block; font-size:11.5px; color:var(--text-3); margin-top:2px; }

.st-api-intro { font-size:12.5px; color:var(--text-3); line-height:1.55; margin:0 0 16px; }
.st-key-row { display:flex; align-items:center; gap:10px; border:1px solid var(--border); border-radius:var(--radius-lg); padding:14px 16px; margin-bottom:10px; }
.st-key-name { font-size:13px; font-weight:700; color:var(--text); flex-shrink:0; }
.st-key-value { flex:1; min-width:0; font-family:ui-monospace,monospace; font-size:12.5px; color:var(--text-3); background:var(--surface-2); border-radius:var(--radius-sm); padding:7px 11px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.st-events-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
.st-event-chip { display:flex; align-items:center; gap:8px; padding:11px 14px; font-size:12.5px; font-weight:600; color:var(--text-3); background:var(--surface); border:1.5px solid var(--border-dark); border-radius:var(--radius); cursor:pointer; transition:all .15s ease; }
.st-event-chip:hover { border-color:var(--gold-dark); }
.st-event-chip.active { border-color:var(--gold-dark); background:var(--badge-bg-gold); color:var(--gold-dark); }
.st-event-dot { width:7px; height:7px; border-radius:50%; background:var(--text-4); flex-shrink:0; }
.st-event-chip.active .st-event-dot { background:var(--gold-dark); }

/* danger card */
.st-danger-card { border:1px solid color-mix(in srgb, var(--red) 28%, transparent); background:var(--red-light); border-radius:var(--radius-lg); padding:20px 22px; }
.st-danger-title { display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; color:var(--red); margin-bottom:6px; }
.st-danger-row { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:16px 0; border-top:1px solid color-mix(in srgb, var(--red) 18%, transparent); }
.st-danger-name { font-size:13.5px; font-weight:700; color:var(--text); }
.st-danger-name.danger { color:var(--red); }
.st-danger-sub { font-size:12px; color:var(--text-3); margin-top:3px; line-height:1.45; }
.btn-danger-outline { background:var(--surface); border:1px solid color-mix(in srgb, var(--red) 45%, transparent); color:var(--red); }
.btn-danger-outline:hover { background:var(--red); color:var(--text-inverted); border-color:var(--red); }

/* modals */
.st-modal-text { font-size:13.5px; color:var(--text-2); line-height:1.6; margin:0 0 16px; }
.st-deact-warn { font-size:12.5px; font-weight:600; color:var(--red); background:var(--red-light); border:1px solid color-mix(in srgb, var(--red) 22%, transparent); border-radius:var(--radius); padding:11px 14px; }
.st-export-list { margin:0; padding-left:20px; }
.st-export-list li { font-size:13px; color:var(--text-2); line-height:1.9; }
.st-key-warn { display:flex; align-items:flex-start; gap:9px; font-size:12.5px; color:var(--gold-dark); background:var(--badge-bg-gold); border:1px solid color-mix(in srgb, var(--gold-dark) 22%, transparent); border-radius:var(--radius); padding:13px 15px; line-height:1.5; margin-bottom:16px; }
.st-key-warn svg { flex-shrink:0; margin-top:1px; }
.st-newkey-value { display:block; font-family:ui-monospace,monospace; font-size:13px; color:var(--text); background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); padding:13px 15px; word-break:break-all; }
.st-qr-box { display:flex; flex-direction:column; align-items:center; gap:10px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius-lg); padding:24px; margin-bottom:18px; }
.st-qr-ico { width:46px; height:46px; border-radius:var(--radius); background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; }
.st-qr-cap { font-size:12px; color:var(--text-4); }
.st-qr-secret { font-family:ui-monospace,monospace; font-size:13px; font-weight:700; letter-spacing:2px; color:var(--text-2); }
.st-otp { text-align:center; font-family:ui-monospace,monospace; letter-spacing:8px; font-size:16px; }
.st-codes-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.st-code { font-family:ui-monospace,monospace; font-size:13px; font-weight:600; letter-spacing:.5px; color:var(--text-2); background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); padding:11px 14px; text-align:center; }
.st-code.used { color:var(--text-4); text-decoration:line-through; }

@media (max-width:1000px) { .st-layout { grid-template-columns:1fr; } .st-nav { position:static; } .st-grid4 { grid-template-columns:1fr 1fr; } }
@media (max-width:700px) { .st-header { flex-direction:column; } .st-grid2, .st-grid4 { grid-template-columns:1fr; } .st-profile-card { flex-direction:column; align-items:flex-start; } .st-matrix { display:block; overflow-x:auto; } .st-theme-grid { grid-template-columns:1fr; } .st-vis-grid { grid-template-columns:1fr; } .st-included-grid { grid-template-columns:1fr; } .st-codes-grid { grid-template-columns:1fr; } .st-plan-grid { grid-template-columns:1fr; } .st-current-band { flex-direction:column; align-items:flex-start; } .st-addon-card { flex-direction:column; } .st-integration-grid { grid-template-columns:1fr; } .st-events-grid { grid-template-columns:1fr 1fr; } .st-invoice-table { display:block; overflow-x:auto; } }
</style>
