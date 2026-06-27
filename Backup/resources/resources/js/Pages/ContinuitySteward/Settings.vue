<template>
  <AppLayout :user="user" portal="continuity_steward" activePage="settings" pageTitle="Settings">

    <!-- ═══ HEADER ═══ -->
    <div class="st-header">
      <div>
        <div class="st-eyebrow">Continuity Steward Portal</div>
        <h1 class="st-title">Continuity Steward Settings</h1>
        <div class="st-meta">
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Marcus Chen</span>
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> 2FA Active</span>
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Last login Jun 22, 2026</span>
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
            <span class="st-nav-item-ico" v-html="it.icon"></span> {{ it.label }}
          </button>
        </template>
      </aside>

      <!-- ═══ PANEL ═══ -->
      <div class="st-panel">

        <!-- PROFILE & IDENTITY -->
        <template v-if="section === 'profile'">
          <div class="st-banner"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> <span><strong>Unified identity across all portals.</strong> Your photo, display name, email, and phone are shared across every portal you have access to. Changes in <strong>Edit Profile</strong> update your identity platform-wide.</span></div>
          <div class="st-card">
            <div class="st-card-head">
              <div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span><div><div class="st-card-title">Profile Summary</div><div class="st-card-sub">Your public-facing identity on Aegis</div></div></div>
              <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View Public Profile</button>
            </div>
            <div class="st-profile-card">
              <span class="st-av lg">MC</span>
              <div class="st-profile-info">
                <div class="st-profile-name">Marcus Chen</div>
                <div class="st-profile-role">Principal, Chen Practice Solutions · JD, LCSW</div>
                <div class="st-profile-contacts">
                  <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> marcus@chenpracticesolutions.com</span>
                  <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg> (510) 555-0189</span>
                  <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> Oakland, CA</span>
                </div>
              </div>
              <span class="st-bp-badge">CS</span>
            </div>
            <div class="st-card-foot"><a href="/continuity-steward/profile" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg> Edit Full Profile</a></div>
          </div>
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span><div><div class="st-card-title">Portal Access</div><div class="st-card-sub">Portals linked to your Aegis account</div></div></div></div>
            <div class="st-card-body">
              <div class="st-portal-row">
                <span class="st-portal-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                <div class="st-portal-info"><div class="st-portal-name">Continuity Steward Portal</div><div class="st-portal-sub">Managing continuity for assigned practitioners · Primary steward</div></div>
                <span class="st-badge-green">Active</span>
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
                <button type="button" class="btn btn-outline btn-sm">View Backup Codes</button>
              </div>
              <div class="st-2fa-row">
                <span class="st-2fa-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                <div class="st-2fa-info"><div class="st-2fa-name">SMS / Text Message</div><div class="st-2fa-sub">Receive a 6-digit code via text to (510) 555-0189</div></div>
                <button type="button" class="btn btn-gold btn-sm">Set Up</button>
              </div>
              <div class="st-2fa-row">
                <span class="st-2fa-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                <div class="st-2fa-info"><div class="st-2fa-name">Email Code</div><div class="st-2fa-sub">A code sent to marcus@chenpracticesolutions.com</div></div>
                <button type="button" class="btn btn-gold btn-sm">Set Up</button>
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
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span><div><div class="st-card-title">Notification Preferences</div><div class="st-card-sub">Delivery channels unified across portals. Per-category preferences apply to your Continuity Steward role.</div></div></div><div class="st-head-btns"><button type="button" class="st-link-btn">Mute All</button><button type="button" class="btn btn-outline btn-sm">Enable All</button></div></div>
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
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span><div><div class="st-card-title">Messaging Preferences</div><div class="st-card-sub">Control who can reach you and how you appear to assigned practitioners</div></div></div><a href="/continuity-steward/messages" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Open Chat</a></div>
            <div class="st-card-body">
              <div class="st-grid2">
                <div class="st-field"><label class="st-label">Who Can Message Me</label><select class="form-select" v-model="msg.who"><option>Assigned practitioners only</option><option>Anyone on Aegis</option><option>No one</option></select></div>
                <div class="st-field"><label class="st-label">My Status</label><select class="form-select" v-model="msg.status"><option>Available</option><option>Busy</option><option>Away</option></select></div>
              </div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Read Receipts</div><div class="st-toggle-sub">Show senders when you've read their messages</div></div><button type="button" class="toggle" :class="{ on: msg.readReceipts }" @click="msg.readReceipts = !msg.readReceipts"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Online Status</div><div class="st-toggle-sub">Show a presence indicator when you're active on Aegis</div></div><button type="button" class="toggle" :class="{ on: msg.onlineStatus }" @click="msg.onlineStatus = !msg.onlineStatus"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Critical Incident Thread Auto-Flag</div><div class="st-toggle-sub">Messages sent during an active incident are automatically marked as legal record</div></div><button type="button" class="toggle" :class="{ on: msg.autoFlag }" @click="msg.autoFlag = !msg.autoFlag"></button></div>
              <div class="st-field"><label class="st-label">Away Message</label><textarea class="st-input st-textarea" rows="3" v-model="msg.away" placeholder="e.g. 'I'm currently on leave. For urgent matters please contact my Support Steward.'"></textarea></div>
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
              <div class="st-field" style="margin-top:8px"><label class="st-label">Timezone</label><select class="form-select" v-model="appearance.tz"><option>Eastern Time (ET)</option><option>Central Time (CT)</option><option>Mountain Time (MT)</option><option>Pacific Time (PT)</option><option>UTC</option></select></div>
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

        <!-- CONTINUITY STEWARD SETTINGS -->
        <template v-else-if="section === 'csettings'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span><div><div class="st-card-title">Continuity Steward Settings</div><div class="st-card-sub">Preferences for your stewardship role and responsibilities</div></div></div></div>
            <div class="st-card-body">
              <div class="st-subhead" style="margin-bottom:4px">Role Visibility</div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Show Name on Provider Public Profile</div><div class="st-toggle-sub">Allow your name to appear as "Continuity Steward" on your providers' public Aegis pages</div></div><button type="button" class="toggle" :class="{ on: csettings.showName }" @click="csettings.showName = !csettings.showName"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Request Vault Access on Assignment</div><div class="st-toggle-sub">Automatically request emergency vault access when assigned to a new provider</div></div><button type="button" class="toggle" :class="{ on: csettings.vaultAccess }" @click="csettings.vaultAccess = !csettings.vaultAccess"></button></div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save CS Settings</button></div>
            </div>
          </div>
        </template>

        <!-- PRIVACY & VISIBILITY -->
        <template v-else-if="section === 'privacy'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span><div><div class="st-card-title">Privacy &amp; Visibility</div><div class="st-card-sub">Applies to your public Continuity Steward profile (Business CS accounts only)</div></div></div></div>
            <div class="st-card-body">
              <div class="st-vis-label">Overall Profile Visibility Level</div>
              <div class="st-vis-grid">
                <button v-for="v in visLevels" :key="v.key" type="button" class="st-vis-card" :class="{ active: privacy.level === v.key }" @click="privacy.level = v.key">
                  <span class="st-vis-ico" v-html="v.icon"></span>
                  <span class="st-vis-name">{{ v.name }}</span>
                  <span class="st-vis-desc">{{ v.desc }}</span>
                </button>
              </div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Appear in CS Search Results</div><div class="st-toggle-sub">Let practitioners find you when searching for a Continuity Steward</div></div><button type="button" class="toggle" :class="{ on: privacy.search }" @click="privacy.search = !privacy.search"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Show Location on Profile</div><div class="st-toggle-sub">Display your general city/region on your public CS profile</div></div><button type="button" class="toggle" :class="{ on: privacy.location }" @click="privacy.location = !privacy.location"></button></div>
              <div class="st-toggle-row"><div><div class="st-toggle-name">Show Credentials on Profile</div><div class="st-toggle-sub">Display your professional credentials and title publicly</div></div><button type="button" class="toggle" :class="{ on: privacy.credentials }" @click="privacy.credentials = !privacy.credentials"></button></div>
              <div class="st-card-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save Privacy Settings</button></div>
            </div>
          </div>
        </template>

        <!-- SUBSCRIPTION & BILLING -->
        <template v-else-if="section === 'billing'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></span><div><div class="st-card-title">Subscription &amp; Plan</div><div class="st-card-sub">2–40 practitioners</div></div></div><span class="st-badge-green"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:3px"><polyline points="20 6 9 17 4 12"/></svg>Active</span></div>
            <div class="st-card-body">
              <div class="st-plan-card">
                <span class="st-plan-current"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Current Plan</span>
                <div class="st-plan-name">Business CS</div>
                <div class="st-plan-price">$49<span>/mo</span> <span class="st-plan-alt-inline">or $429/yr</span></div>
                <div class="st-plan-desc">Independent paid account. Serve 2–40 practitioners. Public profile included.</div>
                <div class="st-plan-renew">Renews Apr 14, 2026</div>
              </div>
              <div class="st-included-head">What's included</div>
              <div class="st-included-grid">
                <span v-for="inc in included" :key="inc"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> {{ inc }}</span>
              </div>
              <div class="st-subhead" style="margin-top:24px;margin-bottom:10px">Payment method</div>
              <div class="st-pay-row">
                <span class="st-pay-badge">Card</span>
                <div class="st-pay-info"><div class="st-pay-num">•••• •••• •••• 4242</div><div class="st-pay-exp">Expires 12/27</div></div>
                <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg> Update</button>
                <button type="button" class="btn-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
              </div>
              <div class="st-note" style="margin-top:8px">Card details are stored securely via Stripe. Aegis never stores your full card number.</div>
              <div class="st-subhead" style="margin-top:22px;margin-bottom:6px">Recent invoices</div>
              <div class="st-stub" style="padding:4px 0">No invoices yet.</div>
              <div class="st-billing-foot">Need to cancel or change your plan? Contact <a href="mailto:support@maatpracticefirm.com" class="st-inline-link">support@maatpracticefirm.com</a></div>
            </div>
          </div>
        </template>

        <!-- ACCOUNT AND SERVICE CHANGES -->
        <template v-else-if="section === 'changes'">
          <div class="st-card">
            <div class="st-card-head"><div class="st-card-head-l"><span class="st-card-ico danger"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span><div><div class="st-card-title danger">Account and Service Changes</div><div class="st-card-sub">Irreversible operations — proceed with caution</div></div></div></div>
            <div class="st-card-body">
              <div class="st-danger-card">
                <div class="st-danger-title"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Account and Service Changes</div>
                <div class="st-danger-row"><div><div class="st-danger-name">Export All Data</div><div class="st-danger-sub">Download a complete copy of your Aegis data</div></div><button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export</button></div>
                <div class="st-danger-row"><div><div class="st-danger-name">Pause Stewardship</div><div class="st-danger-sub">Temporarily suspend your stewardship duties. Your practitioners will be notified.</div></div><button type="button" class="btn btn-outline btn-sm" @click="showPause = true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg> Pause</button></div>
                <div class="st-danger-row"><div><div class="st-danger-name">Deactivate Account</div><div class="st-danger-sub">Permanently deactivate your Continuity Steward account. All stewardship assignments will be released.</div></div><button type="button" class="btn btn-danger-outline btn-sm" @click="showDeactivate = true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Deactivate</button></div>
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

    <!-- ═══ DEACTIVATE MODAL ═══ -->
    <Modal v-model="showDeactivate" title="Deactivate Account" size="sm">
      <p class="st-deact-text">This will permanently deactivate your Continuity Steward account. All stewardship assignments will be released and practitioners notified.</p>
      <div class="st-deact-warn">This action cannot be easily reversed.</div>
      <div class="st-field" style="margin-top:16px"><label class="st-label">Type DEACTIVATE to confirm</label><input class="st-input" v-model="deactivateConfirm" placeholder="DEACTIVATE" /></div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showDeactivate = false">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" :disabled="deactivateConfirm !== 'DEACTIVATE'" @click="showDeactivate = false">Deactivate Account</button>
      </template>
    </Modal>

    <!-- ═══ PAUSE MODAL ═══ -->
    <Modal v-model="showPause" title="Pause Stewardship" size="sm">
      <p class="st-deact-text" style="margin-bottom:0">Your stewardship duties will be temporarily suspended and your assigned practitioners notified. You can resume at any time.</p>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showPause = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showPause = false">Pause Stewardship</button>
      </template>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
import Modal from '../../Components/Modal.vue';
defineProps({ user: Object });

const i = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
const phoneSvg = `<svg viewBox="0 0 24 24" ${i}><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>`;
const laptopSvg = `<svg viewBox="0 0 24 24" ${i}><rect x="2" y="4" width="20" height="13" rx="2"/><line x1="2" y1="21" x2="22" y2="21"/></svg>`;

const nav = [
  { group: 'Account', items: [
    { key: 'profile', label: 'Profile & Identity', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>` },
    { key: 'account', label: 'Account & Login', icon: `<svg viewBox="0 0 24 24" ${i}><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>` },
    { key: 'security', label: 'Security & 2FA', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
  ]},
  { group: 'Communications', items: [
    { key: 'notifications', label: 'Notifications', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>` },
    { key: 'messaging', label: 'Messaging', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>` },
    { key: 'email', label: 'Email Preferences', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>` },
  ]},
  { group: 'Continuity', items: [
    { key: 'csettings', label: 'Continuity Steward Settings', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
    { key: 'vault', label: 'Document Vault Access', icon: `<svg viewBox="0 0 24 24" ${i}><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>` },
  ]},
  { group: 'Platform', items: [
    { key: 'privacy', label: 'Privacy & Visibility', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
    { key: 'appearance', label: 'Appearance & Timezone', icon: `<svg viewBox="0 0 24 24" ${i}><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>` },
    { key: 'accessibility', label: 'Accessibility', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>` },
  ]},
  { group: 'Billing', items: [
    { key: 'billing', label: 'Subscription & Billing', icon: `<svg viewBox="0 0 24 24" ${i}><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>` },
  ]},
  { group: 'Account and Service Changes', items: [
    { key: 'changes', label: 'Account and Service Changes', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`, danger: true },
  ]},
];

const section = ref('profile');
const activeNavItem = computed(() => nav.flatMap(g => g.items).find(it => it.key === section.value) || {});
const stubSub = computed(() => `Manage your ${activeNavItem.value.label?.toLowerCase()} preferences`);

const showPw = ref(false);
const acct = reactive({ email: 'marcus@chenpracticesolutions.com', recovery: '', phone: '(510) 555-0189', handle: '@marcus.chen', current: 'placeholder1', newpw: '', confirmpw: '' });

const sessions = [
  { device: 'MacBook Pro — Chrome 121', meta: 'Last active just now · 192.168.1.45', current: true, mobile: false },
  { device: 'iPhone 15 Pro — Safari', meta: 'Last active 2h ago · 73.x.x.x', current: false, mobile: true },
  { device: 'Windows PC — Firefox 122', meta: 'Unrecognized location · Last active 3 days ago', current: false, mobile: false },
];

const securityAlerts = reactive([
  { name: 'Alert on New Login', sub: 'Receive email when a new device logs into your account', on: true },
  { name: 'Suspicious Activity Alerts', sub: 'Notify me of unusual login attempts or access from new locations', on: true },
  { name: 'Session Timeout (30 min inactivity)', sub: 'Automatically log out after 30 minutes of inactivity', on: true },
]);

const notif = reactive({ quietFrom: '22:00', quietTo: '08:00', digest: 'Daily (8 AM)', lead: '1 day before' });
const notifRows = reactive([
  { name: 'Incident Triggers', sub: 'When a critical incident is reported for one of my assigned providers', push: true, email: true, sms: true, inapp: true },
  { name: 'Plan Changes', sub: 'When a practitioner updates or amends their continuity plan', push: true, email: true, sms: false, inapp: true },
  { name: 'Attestation Requests', sub: 'When a pending plan attestation requires my sign-off', push: true, email: true, sms: true, inapp: true },
  { name: 'Agreement Alerts', sub: 'Signing requests, renewals, and expiry warnings on stewardship agreements', push: true, email: true, sms: false, inapp: true },
  { name: 'New Messages', sub: 'From assigned practitioners or Aegis support', push: true, email: false, sms: false, inapp: true },
  { name: 'Document Access', sub: 'When vault documents are accessed on behalf of a provider I steward', push: false, email: true, sms: false, inapp: true },
  { name: 'Coverage Activation', sub: 'When continuity coverage for one of my providers is formally activated', push: true, email: true, sms: true, inapp: true },
  { name: 'Check-in Reminders', sub: 'Scheduled reminders to review and certify practitioner plans', push: true, email: true, sms: false, inapp: true },
  { name: 'Network Updates', sub: 'New connection requests and approvals within the Aegis network', push: false, email: true, sms: false, inapp: true },
  { name: 'Platform Updates', sub: 'New Aegis features and maintenance windows', push: false, email: false, sms: false, inapp: false },
]);

const msg = reactive({ who: 'Assigned practitioners only', status: 'Available', readReceipts: true, onlineStatus: true, autoFlag: true, away: '' });

const email = reactive({ digest: 'Weekly' });
const emailToggles = reactive({
  weekly: { name: 'Weekly Platform Digest', sub: 'Summary of your stewardship activity and Aegis updates', on: true },
  activity: { name: 'Stewardship Activity Summary', sub: 'Digest of plan changes, attestations, and coverage events', on: true },
  product: { name: 'Product Updates', sub: 'New Aegis features, improvements, and release notes', on: false },
  unsub: { name: 'Unsubscribe from All Optional Emails', sub: 'Only transactional and security emails will be sent — notifications, alerts, invoices', on: false },
});

const appearance = reactive({ theme: 'gold', dark: false, tz: 'Eastern Time (ET)' });
const themes = [
  { key: 'gold', name: 'Aegis Gold', desc: 'Classic warm gold (default)', swatch: 'linear-gradient(135deg,#caa45a,#9a7b34)' },
  { key: 'dark', name: 'Gold Dark', desc: 'Deep rich gold palette', swatch: 'linear-gradient(135deg,#8a6a20,#3d2f12)' },
  { key: 'deep', name: 'Gold Deep', desc: 'Refined deep gold system', swatch: 'linear-gradient(135deg,#9c8a5e,#5c4a26)' },
];
const a11y = reactive({ font: 100, contrast: false, colorblind: false });

const csettings = reactive({ showName: true, vaultAccess: true });
const privacy = reactive({ level: 'public', search: true, location: true, credentials: true });
const visLevels = [
  { key: 'public', name: 'Public', desc: 'Visible to all practitioners on Aegis', icon: `<svg viewBox="0 0 24 24" ${i}><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>` },
  { key: 'network', name: 'Network', desc: 'Practitioners I am connected with only', icon: `<svg viewBox="0 0 24 24" ${i}><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>` },
  { key: 'unlisted', name: 'Unlisted', desc: 'Not shown in search — invite only', icon: `<svg viewBox="0 0 24 24" ${i}><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>` },
];

const included = [
  'View & countersign continuity plans',
  'Manage critical incidents',
  'Access document vault',
  'Secure encrypted messaging',
  'Task management & certification',
  'Full activity log visibility',
];

const showDeactivate = ref(false);
const deactivateConfirm = ref('');
const showPause = ref(false);
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
.st-bp-badge { font-size:9.5px; font-weight:700; letter-spacing:.5px; padding:3px 9px; border-radius:var(--radius-full); background:var(--badge-bg-gold); color:var(--gold-dark); flex-shrink:0; align-self:flex-start; }
.st-card-foot { display:flex; justify-content:flex-end; padding:14px 24px; border-top:1px solid var(--border); background:var(--surface-2); }

.st-portal-row { display:flex; align-items:center; gap:13px; border:1px solid var(--border); border-radius:var(--radius-lg); padding:14px 16px; }
.st-portal-ico { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.st-portal-info { flex:1; min-width:0; }
.st-portal-name { font-size:13.5px; font-weight:700; color:var(--text); }
.st-portal-sub { font-size:12px; color:var(--text-3); margin-top:2px; }
.st-info-band { display:flex; align-items:flex-start; gap:9px; font-size:12px; color:var(--text-3); background:var(--badge-bg-gold); border-radius:var(--radius); padding:12px 16px; margin-top:14px; line-height:1.5; }
.st-info-band svg { color:var(--gold-dark); flex-shrink:0; margin-top:1px; }
.st-info-band strong { color:var(--text-2); font-weight:700; }

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

.st-vis-label { font-size:12.5px; font-weight:600; color:var(--text-2); margin-bottom:12px; }
.st-theme-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
.st-theme-card { display:flex; flex-direction:column; text-align:left; padding:0; background:var(--surface); border:1.5px solid var(--border-dark); border-radius:var(--radius-lg); overflow:hidden; cursor:pointer; transition:all .15s ease; }
.st-theme-card:hover { border-color:var(--gold-dark); }
.st-theme-card.active { border-color:var(--gold-dark); box-shadow:0 0 0 2px var(--badge-bg-gold); }
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

/* danger card */
.st-danger-card { border:1px solid color-mix(in srgb, var(--red) 28%, transparent); background:var(--red-light); border-radius:var(--radius-lg); padding:20px 22px; }
.st-danger-title { display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; color:var(--red); margin-bottom:6px; }
.st-danger-row { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:16px 0; border-top:1px solid color-mix(in srgb, var(--red) 18%, transparent); }
.st-danger-name { font-size:13.5px; font-weight:700; color:var(--text); }
.st-danger-sub { font-size:12px; color:var(--text-3); margin-top:3px; line-height:1.45; }
.btn-danger-outline { background:var(--surface); border:1px solid color-mix(in srgb, var(--red) 45%, transparent); color:var(--red); }
.btn-danger-outline:hover { background:var(--red); color:var(--text-inverted); border-color:var(--red); }

/* modals */
.st-deact-text { font-size:13.5px; color:var(--text-2); line-height:1.6; margin:0 0 14px; }
.st-deact-warn { font-size:12.5px; font-weight:600; color:var(--red); background:var(--red-light); border:1px solid color-mix(in srgb, var(--red) 22%, transparent); border-radius:var(--radius); padding:11px 14px; }

@media (max-width:1000px) { .st-layout { grid-template-columns:1fr; } .st-nav { position:static; } .st-grid4 { grid-template-columns:1fr 1fr; } }
@media (max-width:700px) { .st-header { flex-direction:column; } .st-grid2, .st-grid4 { grid-template-columns:1fr; } .st-profile-card { flex-direction:column; align-items:flex-start; } .st-matrix { display:block; overflow-x:auto; } .st-theme-grid { grid-template-columns:1fr; } .st-vis-grid { grid-template-columns:1fr; } .st-included-grid { grid-template-columns:1fr; } }
</style>
