<template>
  <AppLayout :user="user" portal="business_partner" activePage="profile" :page-title="mode === 'edit' ? 'Edit Profile' : 'My Profile'">

    <!-- ════════════════ READ-ONLY PROFILE ════════════════ -->
    <template v-if="mode === 'view'">
      <div class="pf-header">
        <div class="pf-header-main">
          <div class="pf-avatar">AP</div>
          <div class="pf-header-info">
            <div class="pf-eyebrow">Business Partner</div>
            <h1 class="pf-name">Acme Practice Services</h1>
            <div class="pf-role">Billing · Agency</div>
            <div class="pf-chips">
              <span class="pf-chip green"><span class="pf-dot"></span> Accepting Clients</span>
              <span class="pf-chip blue"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Aegis Verified</span>
              <span class="pf-chip neutral"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> BAA-Ready</span>
            </div>
          </div>
          <div class="pf-header-actions">
            <button type="button" class="btn btn-primary btn-sm" @click="mode = 'edit'"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg> Edit</button>
            <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></button>
          </div>
        </div>
        <div class="pf-header-meta">
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> San Francisco, CA</span>
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> 4.8 <span class="pf-muted">(54)</span></span>
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg> 38 projects</span>
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> ~18h</span>
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg> Team of 8</span>
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Mon–Fri 9–5 EST</span>
        </div>
      </div>

      <div class="pf-bigstats">
        <div class="pf-bigstat"><div class="pf-bs-num">38</div><div class="pf-bs-label">Projects Done</div></div>
        <div class="pf-bigstat"><div class="pf-bs-num">4.8 <span class="pf-bs-star">★</span></div><div class="pf-bs-label">Client Rating</div></div>
        <div class="pf-bigstat"><div class="pf-bs-num ok">~18h</div><div class="pf-bs-label">Response Time</div></div>
        <div class="pf-bigstat"><div class="pf-bs-num blue">96%</div><div class="pf-bs-label">On-Time Delivery</div></div>
        <div class="pf-bigstat"><div class="pf-bs-num blue">$145<span class="pf-bs-unit">/hr</span></div><div class="pf-bs-label">Hourly Rate</div></div>
      </div>

      <div class="cs-profile-banner">
        <div class="cs-profile-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
        <div class="cs-profile-body"><div class="cs-profile-title">Finish your profile</div><div class="cs-profile-sub">Add credentials &amp; availability to improve discovery</div></div>
        <div class="cs-profile-progress"><div class="cs-profile-bar"><div class="cs-profile-bar-fill" :style="{ width: profilePct + '%' }"></div></div><span class="cs-profile-pct">{{ profilePct }}%</span></div>
        <button type="button" class="btn btn-primary btn-sm cs-profile-cta" @click="mode = 'edit'">Complete <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></button>
      </div>

      <div class="pf-grid">
        <div class="pf-col">
          <div class="pf-card">
            <div class="pf-card-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg> About Acme Practice Services</div>
            <div class="pf-card-body">
              <p class="pf-text">Full-service practice operations agency. We handle billing, compliance, and tech for solo and small group mental health practices nationwide.</p>
              <p class="pf-text" style="margin-top:12px;">Direct experience with healthcare-specific tooling, EHR integrations, payer workflows, and the operational realities of running a clinical practice. Available for short-term projects, fixed-scope engagements, and hourly consultation.</p>
              <div class="pf-links">
                <a href="#" class="pf-link"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg> LinkedIn</a>
                <a href="#" class="pf-link"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg> Company Website</a>
                <a href="#" class="pf-link"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Portfolio &amp; Case Studies</a>
              </div>
            </div>
          </div>

          <div class="pf-card">
            <div class="pf-card-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Services Offered</div>
            <div class="pf-card-body">
              <div v-for="grp in serviceGroups" :key="grp.label" class="pf-pillgroup">
                <div class="pf-pg-label">{{ grp.label }}</div>
                <div class="pf-pills"><span v-for="p in grp.items" :key="p" class="pf-pill" :class="grp.variant">{{ p }}</span></div>
              </div>
            </div>
          </div>

          <div class="pf-card">
            <div class="pf-card-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg> Profile Visibility</div>
            <div class="pf-card-body">
              <p class="pf-text">This is your public listing, visible at <strong>/business/acme-practice-services</strong>. Practitioners use this page to evaluate you before engagement. Your active client list, contracts, shared documents, and earnings are <strong style="color:var(--green-dark);">never shown</strong> publicly.</p>
              <button type="button" class="btn btn-outline btn-sm" style="margin-top:14px;" @click="mode = 'edit'">Edit profile</button>
            </div>
          </div>

          <div class="pf-card">
            <div class="pf-card-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> Client Reviews</div>
            <div class="pf-card-body">
              <div v-for="r in reviews" :key="r.name" class="pf-review">
                <div class="pf-review-top"><span class="pf-review-name">{{ r.name }}</span><span class="pf-stars">{{ r.stars }}</span></div>
                <p class="pf-review-text">"{{ r.text }}"</p>
                <div class="pf-review-meta">{{ r.meta }}</div>
              </div>
              <div class="pf-review-foot"><span>Client Rating:</span><strong class="gold">4.8/5.0</strong><span class="pf-review-count">from 54 reviews</span></div>
            </div>
          </div>
        </div>

        <div class="pf-col">
          <div class="pf-card">
            <div class="pf-card-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> Contact &amp; Office</div>
            <div class="pf-card-body">
              <div class="pf-row"><span>Business</span><strong>Acme Practice Services</strong></div>
              <div class="pf-row"><span>Location</span><strong>San Francisco, CA</strong></div>
              <div class="pf-row"><span>Email</span><strong class="gold">hello@acmepractice.io</strong></div>
              <div class="pf-row"><span>Phone</span><strong>(415) 555-0234</strong></div>
              <div class="pf-row"><span>Service Hours</span><strong>Mon–Fri 8 AM – 6 PM PST</strong></div>
              <div class="pf-row"><span>Response SLA</span><strong class="ok">Within 18 hours</strong></div>
              <div class="pf-row"><span>Preferred Channel</span><strong>Aegis Message</strong></div>
            </div>
          </div>

          <div class="pf-card">
            <div class="pf-card-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Credentials &amp; Qualifications</div>
            <div class="pf-card-body">
              <div class="pf-row"><span>Type</span><strong>Agency</strong></div>
              <div class="pf-row"><span>Team Size</span><strong>8 people</strong></div>
              <div class="pf-row"><span>Years in Business</span><strong>8+ years</strong></div>
              <div class="pf-row"><span>HIPAA / BAA</span><strong class="ok">Signed BAA on file</strong></div>
              <div class="pf-row"><span>Aegis Verified</span><strong class="ok">Verified</strong></div>
              <div class="pf-row"><span>Insurance</span><strong class="ok">$3M E&amp;O</strong></div>
            </div>
          </div>

          <div class="pf-card">
            <div class="pf-card-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> How It Works</div>
            <div class="pf-card-body">
              <div v-for="(s, i) in howItWorks" :key="i" class="pf-hiw"><span class="pf-hiw-num">{{ i + 1 }}</span><div class="pf-hiw-text"><strong>{{ s.title }}</strong> — {{ s.desc }}</div></div>
            </div>
          </div>

          <div class="pf-card">
            <div class="pf-card-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg> Languages</div>
            <div class="pf-card-body"><div class="pf-pills"><span class="pf-pill">English</span><span class="pf-pill">Spanish</span></div></div>
          </div>

          <div class="pf-card">
            <div class="pf-card-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg> Your Private Notes</div>
            <div class="pf-card-body">
              <div v-for="n in privateNotes" :key="n.added" class="pf-note">
                <div class="pf-note-text">{{ n.text }}</div>
                <div class="pf-note-added">Added {{ n.added }}</div>
              </div>
              <textarea v-model="newNote" class="pf-note-input" rows="2" placeholder="Add a private note about your practice or engagement workflow..."></textarea>
              <button type="button" class="btn btn-gold btn-sm" style="margin-top:10px;">Save Note</button>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ════════════════ EDIT PROFILE ════════════════ -->
    <template v-else>
      <div class="ep-header">
        <div class="ep-header-l">
          <div class="ep-header-eyebrow">Business Profile</div>
          <h1 class="ep-title">Edit Business Profile</h1>
          <div class="ep-subtitle">Acme Practice Services · Acme Practice Services LLC · San Francisco, CA</div>
          <div class="ep-meta">
            <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Saved 2h ago</span>
            <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Last reviewed Apr 28</span>
          </div>
        </div>
        <div class="ep-header-actions">
          <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
          <button type="button" class="btn btn-outline btn-sm" @click="mode = 'view'"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Preview</button>
          <button type="button" class="btn btn-primary btn-sm" @click="mode = 'view'"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save Changes</button>
        </div>
      </div>

      <div class="cs-profile-banner">
        <div class="cs-profile-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
        <div class="cs-profile-body"><div class="cs-profile-title">Finish your profile</div><div class="cs-profile-sub">Add credentials &amp; availability to improve discovery</div></div>
        <div class="cs-profile-progress"><div class="cs-profile-bar"><div class="cs-profile-bar-fill" :style="{ width: profilePct + '%' }"></div></div><span class="cs-profile-pct">{{ profilePct }}%</span></div>
        <button type="button" class="btn btn-primary btn-sm cs-profile-cta">Complete <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></button>
      </div>

      <div class="ep-layout">
        <aside class="ep-nav">
          <template v-for="group in navGroups" :key="group.label">
            <div class="ep-nav-group">{{ group.label }}</div>
            <button v-for="item in group.items" :key="item.key" type="button" class="ep-nav-item" :class="{ active: section === item.key }" @click="section = item.key">
              <span class="ep-nav-ico" v-html="item.icon"></span>
              <span class="ep-nav-label">{{ item.label }}</span>
              <span class="ep-nav-status done"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
            </button>
          </template>
        </aside>

        <section class="ep-panel">
          <!-- IDENTITY -->
          <div v-show="section === 'identity'">
            <div class="ep-card">
              <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div><div><div class="ep-card-title">Business Logo / Photo</div><div class="ep-card-sub">Shown on your public business profile and in the marketplace directory</div></div></div>
              <div class="ep-card-body">
                <div class="ep-photo-row">
                  <div class="ep-photo-av">AP</div>
                  <div class="ep-photo-info">
                    <div class="ep-photo-name">Acme Practice Services</div>
                    <div class="ep-photo-note">No logo uploaded — initials shown as placeholder</div>
                    <div class="ep-photo-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Upload Logo</button><button type="button" class="btn btn-outline btn-sm">Remove</button></div>
                  </div>
                </div>
                <div class="ep-field"><label class="ep-label">Display Initials</label><input class="ep-input" value="AP" maxlength="2" /><div class="ep-help">Max 2 characters — shown in avatar circles across Aegis</div></div>
              </div>
            </div>

            <div class="ep-card">
              <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div><div class="ep-card-title">Basic Information</div><div class="ep-card-sub">Appears on your public Aegis business profile</div></div></div>
              <div class="ep-card-body">
                <div class="ep-form-row">
                  <div class="ep-field"><label class="ep-label">First Name *</label><input class="ep-input" value="Acme" /></div>
                  <div class="ep-field"><label class="ep-label">Last Name *</label><input class="ep-input" value="Practice Services" /></div>
                </div>
                <div class="ep-field"><label class="ep-label">Credentials / Suffix <span class="ep-opt">(Optional)</span></label><input class="ep-input" placeholder="e.g. CPA, JD, MBA" /></div>
                <div class="ep-field"><label class="ep-label">Business Name *</label><input class="ep-input" value="Acme Practice Services LLC" /></div>
                <div class="ep-field"><label class="ep-label">Title / Role</label><input class="ep-input" value="Practice Operations Agency" /></div>
                <div class="ep-field">
                  <label class="ep-label">Business Bio *</label>
                  <textarea class="ep-input ep-textarea" rows="3" maxlength="1000" v-model="bio"></textarea>
                  <div class="ep-textarea-foot"><span class="ep-count">{{ bio.length }} / 1000</span></div>
                  <div class="ep-help">Describe your firm, specializations, and why practitioners choose you.</div>
                </div>
              </div>
            </div>

            <div class="ep-card">
              <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div><div><div class="ep-card-title">Contact &amp; Location</div><div class="ep-card-sub">Visible to practitioners in the Aegis marketplace</div></div></div>
              <div class="ep-card-body">
                <div class="ep-form-row">
                  <div class="ep-field"><label class="ep-label">City</label><input class="ep-input" value="San Francisco" /></div>
                  <div class="ep-field"><label class="ep-label">State</label><input class="ep-input" value="CA" /></div>
                  <div class="ep-field"><label class="ep-label">Phone *</label><input class="ep-input" value="(415) 555-0234" /></div>
                  <div class="ep-field"><label class="ep-label">Email *</label><input class="ep-input" value="hello@acmepractice.io" /></div>
                </div>
                <div class="ep-field"><label class="ep-label">Website <span class="ep-opt">(Optional)</span></label><div class="ep-input-prefix"><span>https://</span><input class="ep-input" placeholder="yourcompany.com" /></div></div>
                <div class="ep-field">
                  <label class="ep-label">Languages Supported</label>
                  <div class="ep-tag-pills"><button v-for="lang in languageOptions" :key="lang" type="button" class="ep-tag-pill" :class="{ active: languages.includes(lang) }" @click="toggleArr(languages, lang)">{{ lang }}</button></div>
                  <div class="ep-add-row"><input class="ep-input" v-model="newLanguage" placeholder="Add other language..." @keyup.enter="addLanguage" /><button type="button" class="ep-add-btn" @click="addLanguage"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add</button></div>
                </div>
              </div>
            </div>
          </div>

          <!-- ABOUT & BIO -->
          <div v-show="section === 'about'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg></div><div><div class="ep-card-title">About &amp; Bio</div><div class="ep-card-sub">Your firm's story — shown to practitioners evaluating you</div></div></div>
            <div class="ep-card-body">
              <div class="ep-field"><label class="ep-label">Business Bio *</label><textarea class="ep-input ep-textarea ep-textarea-lg" maxlength="1000" v-model="bio"></textarea><div class="ep-textarea-foot"><span class="ep-help" style="margin-top:0;">Describe your firm, specializations, and why practitioners choose you.</span><span class="ep-count">{{ bio.length }} / 1000</span></div></div>
            </div>
          </div>

          <!-- CONTACT INFO -->
          <div v-show="section === 'contact'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div><div><div class="ep-card-title">Contact Information</div><div class="ep-card-sub">How practitioners reach you in the marketplace</div></div></div>
            <div class="ep-card-body">
              <div class="ep-form-row">
                <div class="ep-field"><label class="ep-label">Primary Phone *</label><input class="ep-input" value="(415) 555-0234" /></div>
                <div class="ep-field"><label class="ep-label">Email Address *</label><input class="ep-input" value="hello@acmepractice.io" /></div>
                <div class="ep-field"><label class="ep-label">Service Hours</label><input class="ep-input" value="Mon–Fri 8 AM – 6 PM PST" /></div>
                <div class="ep-field"><label class="ep-label">Response SLA</label><select class="form-select"><option>Within 18 hours</option><option>Within 4 hours</option><option>Within 24 hours</option><option>Within 48 hours</option></select></div>
              </div>
              <div class="ep-field"><label class="ep-label">Preferred Channel</label><select class="form-select"><option>Aegis Message</option><option>Email</option><option>Phone</option></select></div>
              <div class="ep-field"><label class="ep-label">Website <span class="ep-opt">(Optional)</span></label><div class="ep-input-prefix"><span>https://</span><input class="ep-input" placeholder="yourcompany.com" /></div></div>
            </div>
          </div>

          <!-- SERVICES OFFERED -->
          <div v-show="section === 'services'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div><div class="ep-card-title">Services Offered</div><div class="ep-card-sub">What you do — shown on your public profile</div></div></div>
            <div class="ep-card-body">
              <div class="ep-subhead-sm">Service Categories</div>
              <div class="ep-tag-pills"><button v-for="s in catOptions" :key="s" type="button" class="ep-tag-pill" :class="{ active: categories.includes(s) }" @click="toggleArr(categories, s)">{{ s }}</button></div>
              <div class="ep-subhead-sm" style="margin-top:18px;">Industries Served</div>
              <div class="ep-tag-pills"><button v-for="s in industryOptions" :key="s" type="button" class="ep-tag-pill" :class="{ active: industries.includes(s) }" @click="toggleArr(industries, s)">{{ s }}</button></div>
              <div class="ep-subhead-sm" style="margin-top:18px;">Compliance &amp; Tooling</div>
              <div class="ep-tag-pills"><button v-for="s in toolingOptions" :key="s" type="button" class="ep-tag-pill" :class="{ active: tooling.includes(s) }" @click="toggleArr(tooling, s)">{{ s }}</button></div>
              <div class="ep-subhead-sm" style="margin-top:18px;">Specializations</div>
              <div class="ep-tag-pills"><button v-for="s in specOptions" :key="s" type="button" class="ep-tag-pill" :class="{ active: specializations.includes(s) }" @click="toggleArr(specializations, s)">{{ s }}</button></div>
            </div>
          </div>

          <!-- CERTIFICATIONS -->
          <div v-show="section === 'certifications'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div><div><div class="ep-card-title">Credentials &amp; Qualifications</div><div class="ep-card-sub">Verification, insurance, and compliance standing</div></div></div>
            <div class="ep-card-body">
              <div class="ep-info-table">
                <div class="ep-info-row"><span class="ep-info-label">Type</span><span class="ep-info-val">Agency</span></div>
                <div class="ep-info-row"><span class="ep-info-label">Aegis Verified</span><span class="ep-info-val ok">Verified</span></div>
                <div class="ep-info-row"><span class="ep-info-label">HIPAA / BAA</span><span class="ep-info-val ok">Signed BAA on file</span></div>
                <div class="ep-info-row"><span class="ep-info-label">Insurance</span><span class="ep-info-val">$3M E&amp;O</span></div>
              </div>
              <div class="ep-form-row">
                <div class="ep-field"><label class="ep-label">Team Size</label><input class="ep-input" value="8 people" /></div>
                <div class="ep-field"><label class="ep-label">Years in Business</label><input class="ep-input" value="8+ years" /></div>
                <div class="ep-field"><label class="ep-label">E&amp;O Insurance Provider</label><input class="ep-input" value="$3M E&O" /></div>
                <div class="ep-field"><label class="ep-label">BAA Status</label><select class="form-select"><option>Signed BAA on file</option><option>BAA pending</option><option>Not required</option></select></div>
              </div>
            </div>
          </div>

          <!-- SERVICES & FEES -->
          <div v-show="section === 'fees'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="ep-card-title">Services &amp; Fees</div><div class="ep-card-sub">Rate bands shown to practitioners reviewing your profile</div></div></div>
            <div class="ep-card-body">
              <div class="ep-form-row">
                <div class="ep-field"><label class="ep-label">Hourly Rate</label><div class="ep-input-prefix"><span>$</span><input class="ep-input" value="145" /></div></div>
                <div class="ep-field"><label class="ep-label">Project Minimum</label><div class="ep-input-prefix"><span>$</span><input class="ep-input" value="2,500" /></div></div>
                <div class="ep-field"><label class="ep-label">Retainer (monthly)</label><div class="ep-input-prefix"><span>$</span><input class="ep-input" value="3,000" /></div></div>
                <div class="ep-field"><label class="ep-label">Preferred Billing</label><select class="form-select"><option>Fixed-price or Hourly</option><option>Fixed-price only</option><option>Hourly only</option><option>Milestone</option></select></div>
              </div>
              <div class="ep-hr"></div>
              <div class="ep-field"><label class="ep-label">Payment Terms</label><input class="ep-input" value="Net 15 · Stripe Connect" /></div>
            </div>
          </div>

          <!-- SERVICE COVERAGE -->
          <div v-show="section === 'coverage'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></div><div><div class="ep-card-title">Service Coverage</div><div class="ep-card-sub">Where you operate and how you deliver</div></div></div>
            <div class="ep-card-body">
              <div class="ep-field">
                <label class="ep-label">States Served <span class="ep-req">*</span></label>
                <div class="ep-states"><button v-for="st in stateOptions" :key="st" type="button" class="ep-state" :class="{ active: states.includes(st) }" @click="toggleArr(states, st)">{{ st }}</button></div>
                <div class="ep-help">Select all states where you provide services. Most BP services are remote.</div>
              </div>
              <div class="ep-form-row">
                <div class="ep-field"><label class="ep-label">Delivery</label><select class="form-select"><option>Remote</option><option>On-site</option><option>Hybrid</option></select></div>
                <div class="ep-field"><label class="ep-label">Max Concurrent Clients</label><select class="form-select"><option>8 practitioners</option><option>4 practitioners</option><option>12 practitioners</option><option>Unlimited</option></select></div>
              </div>
            </div>
          </div>

          <!-- PORTFOLIO -->
          <div v-show="section === 'portfolio'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div><div><div class="ep-card-title">Portfolio</div><div class="ep-card-sub">Links and case studies that showcase your work</div></div></div>
            <div class="ep-card-body">
              <div class="ep-field"><label class="ep-label">LinkedIn</label><div class="ep-input-prefix"><span>https://</span><input class="ep-input" placeholder="linkedin.com/company/..." /></div></div>
              <div class="ep-field"><label class="ep-label">Company Website</label><div class="ep-input-prefix"><span>https://</span><input class="ep-input" placeholder="acmepractice.io" /></div></div>
              <div class="ep-field"><label class="ep-label">Portfolio / Case Studies</label><div class="ep-input-prefix"><span>https://</span><input class="ep-input" placeholder="acmepractice.io/case-studies" /></div></div>
              <button type="button" class="ep-add-btn ep-add-block"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Link</button>
            </div>
          </div>

          <!-- PAYOUTS & BILLING -->
          <div v-show="section === 'payouts'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div><div><div class="ep-card-title">Payouts &amp; Billing</div><div class="ep-card-sub">How you get paid for completed work</div></div></div>
            <div class="ep-card-body">
              <div class="ep-info-table">
                <div class="ep-info-row"><span class="ep-info-label">Stripe Connect</span><span class="ep-info-val ok">Connected</span></div>
                <div class="ep-info-row"><span class="ep-info-label">Payout Account</span><span class="ep-info-val">•••• 4821 · Chase</span></div>
                <div class="ep-info-row"><span class="ep-info-label">1099 Reporting</span><span class="ep-info-val">Enabled</span></div>
                <div class="ep-info-row"><span class="ep-info-label">Tax ID (EIN)</span><span class="ep-info-val">••-•••4567</span></div>
              </div>
              <div class="ep-form-row">
                <div class="ep-field"><label class="ep-label">Payout Schedule</label><select class="form-select"><option>Daily (Stripe default)</option><option>Weekly</option><option>Monthly</option></select></div>
                <div class="ep-field"><label class="ep-label">Default Currency</label><select class="form-select"><option>USD ($)</option><option>CAD ($)</option></select></div>
              </div>
              <button type="button" class="btn btn-outline btn-sm ep-add-block">Manage in Stripe Connect</button>
            </div>
          </div>
        </section>
      </div>

      <div class="ep-savebar">
        <div class="ep-savebar-status"><span class="ep-savebar-dot"></span> <strong>Unsaved changes</strong> · Changes save to your Aegis business profile</div>
        <button type="button" class="btn btn-gold btn-sm" @click="mode = 'view'"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save &amp; Publish</button>
      </div>
    </template>

  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
defineProps({ user: Object });

const mode = ref('view');
const profilePct = 78;
const section = ref('identity');
function toggleArr(arr, val) { const i = arr.indexOf(val); if (i === -1) arr.push(val); else arr.splice(i, 1); }

const sIcon = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
const navGroups = [
  { label: 'Profile', items: [
    { key: 'identity', label: 'Identity', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>` },
    { key: 'about', label: 'About & Bio', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>` },
    { key: 'contact', label: 'Contact Info', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>` },
    { key: 'services', label: 'Services Offered', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>` },
    { key: 'certifications', label: 'Certifications', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
    { key: 'fees', label: 'Services & Fees', icon: `<svg viewBox="0 0 24 24" ${sIcon}><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>` },
    { key: 'coverage', label: 'Service Coverage', icon: `<svg viewBox="0 0 24 24" ${sIcon}><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>` },
    { key: 'portfolio', label: 'Portfolio', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>` },
  ] },
  { label: 'Business', items: [
    { key: 'payouts', label: 'Payouts & Billing', icon: `<svg viewBox="0 0 24 24" ${sIcon}><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>` },
  ] },
];

const bio = ref('Full-service practice operations agency. We handle billing, compliance, and tech for solo and small group mental health practices nationwide.');
const languageOptions = ['English', 'Spanish', 'Mandarin Chinese', 'French', 'Arabic', 'Hindi', 'Portuguese', 'Russian', 'Korean', 'Japanese', 'Vietnamese', 'Tagalog', 'Italian', 'German'];
const languages = reactive(['English']);
const newLanguage = ref('');
function addLanguage() { const v = newLanguage.value.trim(); if (v && !languages.includes(v)) languages.push(v); newLanguage.value = ''; }

const catOptions = ['Billing', 'Compliance', 'Technology', 'Consulting', 'Legal', 'Marketing'];
const categories = reactive(['Billing', 'Compliance', 'Technology']);
const industryOptions = ['Solo Practitioners', 'Group Practices', 'Behavioral Health', 'Telehealth', 'Multi-State Operations'];
const industries = reactive(['Solo Practitioners', 'Group Practices', 'Behavioral Health', 'Telehealth', 'Multi-State Operations']);
const toolingOptions = ['HIPAA', 'QuickBooks', 'Xero', 'Stripe', 'Practice Fusion', 'Epic / Cerner', 'SimplePractice'];
const tooling = reactive(['HIPAA', 'QuickBooks', 'Xero', 'Stripe', 'Practice Fusion', 'Epic / Cerner']);
const specOptions = ['Insurance Reimbursement', 'Practice Setup', 'Compliance Audits', 'Multi-State Filing', 'Credentialing'];
const specializations = reactive(['Insurance Reimbursement', 'Practice Setup', 'Compliance Audits', 'Multi-State Filing']);

const stateOptions = ['AL','AK','AZ','AR','CA','CO','CT','DE','DC','FL','GA','HI','ID','IL','IN','IA','KS','KY','LA','ME','MD','MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VT','VA','WA','WV','WI','WY'];
const states = reactive(['CA', 'NY', 'TX', 'NJ', 'CT']);

// ── read-only view data ──
const serviceGroups = [
  { label: 'Service Categories', variant: 'neutral', items: ['Billing', 'Compliance', 'Technology'] },
  { label: 'Industries Served', variant: 'gold', items: ['Solo Practitioners', 'Group Practices', 'Behavioral Health', 'Telehealth', 'Multi-State Operations'] },
  { label: 'Compliance & Tooling', variant: 'neutral', items: ['HIPAA', 'QuickBooks', 'Xero', 'Stripe', 'Practice Fusion', 'Epic / Cerner'] },
  { label: 'Specializations', variant: 'gold', items: ['Insurance Reimbursement', 'Practice Setup', 'Compliance Audits', 'Multi-State Filing'] },
];
const reviews = [
  { name: 'Dr. Sarah Johnson, LMFT', stars: '★★★★★', text: 'Reliable and detail-oriented. Tax filings always come in early. Knows healthcare-specific deductions inside-out.', meta: 'Solo Practice · 2 years engagement' },
  { name: 'Lotus Group Practice', stars: '★★★★☆', text: 'Great communicator. Helped us through a complex multi-state restructuring. A few minor scheduling hiccups but always made up for it.', meta: 'Group Practice (8 clinicians) · 1 year engagement' },
  { name: 'Dr. Marcus Chen, MD', stars: '★★★★★', text: 'Hands down the most knowledgeable healthcare-focused partner I\'ve worked with. Saved my practice $14k in the first year through better insurance contract negotiation.', meta: 'Solo Practice · 3 years engagement' },
];
const howItWorks = [
  { title: 'Inquiry', desc: 'Describe your need (free, no obligation).' },
  { title: 'Discovery Call', desc: '30-min free call to scope the work.' },
  { title: 'Proposal', desc: 'Custom quote within 48 hours.' },
  { title: 'Contract via Aegis', desc: 'Sign + start work, all tracked in-platform.' },
];
const privateNotes = [
  { text: 'Reach-outs after Maat-certification renewal cycles convert best. Keep response SLA visible — it\'s the headline practitioners actually use to compare partners.', added: 'Feb 2, 2025' },
  { text: 'Group practices ask about multi-state filings on the discovery call. Pre-prep the NJ/NY/CT licensing checklist for first session.', added: 'Jan 14, 2025' },
];
const newNote = ref('');
</script>

<style scoped>
/* ════════════════ READ-ONLY PROFILE ════════════════ */
.pf-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:26px 30px; margin-bottom:16px; box-shadow:var(--shadow-sm); }
.pf-header-main { display:flex; align-items:flex-start; gap:22px; }
.pf-avatar { width:74px; height:74px; border-radius:50%; background:var(--badge-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-size:24px; font-weight:700; flex-shrink:0; }
.pf-header-info { flex:1; min-width:0; }
.pf-eyebrow { font-size:11px; font-weight:700; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:6px; }
.pf-name { font-family:var(--font-serif); font-size:30px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.pf-role { font-size:13.5px; color:var(--text-3); margin-top:6px; }
.pf-chips { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
.pf-chip { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:700; padding:4px 11px; border-radius:var(--radius-full); }
.pf-chip.green { background:var(--green-light); color:var(--green-dark); }
.pf-chip.blue { background:rgba(80,120,190,.12); color:#4a6ba8; }
.pf-chip.neutral { background:var(--surface-3); color:var(--text-3); }
.pf-dot { width:6px; height:6px; border-radius:50%; background:var(--green); }
.pf-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; }
.pf-header-meta { display:flex; align-items:center; gap:20px; flex-wrap:wrap; margin-top:18px; padding-top:18px; border-top:1px solid var(--border); }
.pf-header-meta span { display:inline-flex; align-items:center; gap:6px; font-size:12.5px; color:var(--text-3); }
.pf-header-meta svg { color:var(--gold-dark); }
.pf-muted { color:var(--text-4); }

.pf-bigstats { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:16px; }
.pf-bigstat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px 20px; box-shadow:var(--shadow-xs); text-align:center; }
.pf-bs-num { font-family:var(--font-serif); font-size:24px; font-weight:700; color:var(--text); line-height:1; }
.pf-bs-num.ok { color:var(--green-dark); }
.pf-bs-num.blue { color:#4a6ba8; }
.pf-bs-star { color:var(--gold-dark); }
.pf-bs-unit { font-size:13px; color:var(--text-4); }
.pf-bs-label { font-size:10px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--text-4); margin-top:8px; }

.cs-profile-banner { margin-bottom:18px; background:linear-gradient(95deg,var(--badge-bg-gold) 0%,var(--surface) 80%); border:1px solid rgba(192,154,82,.28); border-radius:var(--radius-lg); padding:14px 20px; display:flex; align-items:center; gap:16px; box-shadow:var(--shadow-sm); }
.cs-profile-icon { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cs-profile-body { flex:1; min-width:0; }
.cs-profile-title { font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px; }
.cs-profile-sub { font-size:12px; color:var(--text-3); }
.cs-profile-progress { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.cs-profile-bar { width:120px; height:5px; background:var(--surface-3); border-radius:var(--radius-full); overflow:hidden; }
.cs-profile-bar-fill { height:100%; background:linear-gradient(90deg,var(--gold),var(--gold-dark)); border-radius:var(--radius-full); }
.cs-profile-pct { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.cs-profile-cta { flex-shrink:0; white-space:nowrap; }

.pf-grid { display:grid; grid-template-columns:1fr 380px; gap:16px; align-items:start; }
.pf-col { display:flex; flex-direction:column; gap:16px; }
.pf-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; }
.pf-card-head { display:flex; align-items:center; gap:9px; padding:15px 20px; border-bottom:1px solid var(--border); font-size:14px; font-weight:700; color:var(--text); }
.pf-card-head svg { color:var(--gold-dark); }
.pf-card-body { padding:18px 20px; }
.pf-text { font-size:13px; color:var(--text-2); line-height:1.7; margin:0; }
.pf-text strong { color:var(--text); }
.pf-links { display:flex; gap:8px; flex-wrap:wrap; margin-top:16px; }
.pf-link { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:600; color:var(--text-2); padding:7px 12px; border:1px solid var(--border-dark); border-radius:var(--radius); text-decoration:none; transition:all .15s ease; }
.pf-link:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.pf-pillgroup { margin-bottom:16px; }
.pf-pillgroup:last-child { margin-bottom:0; }
.pf-pg-label { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); margin-bottom:9px; }
.pf-pills { display:flex; gap:7px; flex-wrap:wrap; }
.pf-pill { font-size:12px; font-weight:600; padding:5px 12px; border-radius:var(--radius-full); border:1px solid var(--border-dark); background:var(--surface); color:var(--text-2); }
.pf-pill.gold { border-color:rgba(192,154,82,.4); color:var(--gold-dark); }
.pf-review { padding:14px 0; border-bottom:1px solid var(--border); }
.pf-review:first-child { padding-top:0; }
.pf-review-top { display:flex; align-items:center; justify-content:space-between; gap:10px; }
.pf-review-name { font-size:13.5px; font-weight:700; color:var(--text); }
.pf-stars { font-size:13px; color:var(--gold-dark); letter-spacing:1px; }
.pf-review-text { font-size:12.5px; color:var(--text-2); line-height:1.6; font-style:italic; margin:8px 0; }
.pf-review-meta { font-size:11px; color:var(--text-4); }
.pf-review-foot { display:flex; align-items:center; gap:10px; padding-top:14px; font-size:12.5px; color:var(--text-3); }
.pf-review-foot strong.gold { color:var(--gold-dark); font-family:var(--font-serif); font-size:14px; }
.pf-review-count { margin-left:auto; color:var(--text-4); }
.pf-row { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:9px 0; border-bottom:1px solid var(--border); font-size:13px; color:var(--text-3); }
.pf-row:last-child { border-bottom:none; }
.pf-row strong { color:var(--text); font-weight:600; text-align:right; }
.pf-row strong.ok { color:var(--green-dark); }
.pf-row strong.gold { color:var(--gold-dark); }
.pf-hiw { display:flex; gap:11px; padding:8px 0; }
.pf-hiw-num { width:22px; height:22px; border-radius:50%; background:var(--badge-bg-gold); color:var(--gold-dark); font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.pf-hiw-text { font-size:12.5px; color:var(--text-3); line-height:1.5; }
.pf-hiw-text strong { color:var(--text); }
.pf-note { background:var(--badge-bg-gold); border-left:3px solid var(--gold-dark); border-radius:var(--radius); padding:12px 14px; margin-bottom:10px; }
.pf-note-text { font-size:12.5px; color:var(--text-2); line-height:1.55; }
.pf-note-added { font-size:9.5px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--text-4); margin-top:8px; }
.pf-note-input { width:100%; padding:11px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; resize:vertical; min-height:64px; line-height:1.5; font-family:var(--font-sans); margin-top:4px; }
.pf-note-input:focus { border-color:var(--gold-dark); }

/* ════════════════ EDIT PROFILE (shared ep-*) ════════════════ */
.ep-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:26px 30px; margin-bottom:16px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.ep-header-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.ep-title { font-family:var(--font-serif); font-size:34px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.ep-subtitle { font-size:13.5px; color:var(--text-3); margin-top:8px; }
.ep-meta { display:flex; align-items:center; gap:18px; margin-top:12px; }
.ep-meta span { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; color:var(--text-4); }
.ep-meta span:first-child svg { color:var(--green-dark); }
.ep-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; }

.ep-layout { display:grid; grid-template-columns:240px 1fr; gap:20px; align-items:start; padding-bottom:80px; }
.ep-nav { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:14px 12px; box-shadow:var(--shadow-xs); position:sticky; top:16px; }
.ep-nav-group { font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--text-4); padding:8px 10px 6px; }
.ep-nav-group:not(:first-child) { margin-top:8px; border-top:1px solid var(--border); padding-top:14px; }
.ep-nav-item { width:100%; display:flex; align-items:center; gap:10px; padding:9px 10px; border:none; background:none; border-radius:var(--radius); cursor:pointer; text-align:left; color:var(--text-2); transition:background .15s ease,color .15s ease; margin-bottom:1px; }
.ep-nav-item:hover { background:var(--surface-2); }
.ep-nav-item.active { background:var(--surface-3); }
.ep-nav-item.active .ep-nav-label { color:var(--gold-dark); font-weight:700; }
.ep-nav-item.active .ep-nav-ico { color:var(--gold-dark); }
.ep-nav-ico { width:16px; height:16px; flex-shrink:0; color:var(--text-4); display:inline-flex; }
.ep-nav-ico :deep(svg) { width:16px; height:16px; }
.ep-nav-label { flex:1; font-size:13px; font-weight:500; }
.ep-nav-status { width:16px; height:16px; border-radius:50%; flex-shrink:0; display:inline-flex; align-items:center; justify-content:center; }
.ep-nav-status.done { background:var(--gold-dark); color:var(--text-inverted); }

.ep-panel { display:flex; flex-direction:column; gap:18px; }
.ep-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; }
.ep-card + .ep-card { margin-top:18px; }
.ep-card-head { display:flex; align-items:center; gap:14px; padding:22px 26px; background:var(--surface-2); border-bottom:1px solid var(--border); }
.ep-card-head-ico { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ep-card-title { font-family:var(--font-serif); font-size:18px; font-weight:600; color:var(--text); }
.ep-card-sub { font-size:12.5px; color:var(--text-3); margin-top:2px; }
.ep-card-body { padding:24px 26px; }
.ep-subhead-sm { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); padding-bottom:8px; border-bottom:1px solid var(--border); margin-bottom:12px; }
.ep-field { margin-bottom:18px; }
.ep-field:last-child { margin-bottom:0; }
.ep-form-row { display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:4px; }
.ep-label { display:block; font-size:12px; font-weight:600; color:var(--text-2); margin-bottom:7px; }
.ep-opt { font-weight:400; color:var(--text-4); }
.ep-req { color:var(--red); }
.ep-input { display:block; width:100%; padding:10px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.ep-input:focus { border-color:var(--gold-dark); }
.ep-input::placeholder { color:var(--text-4); }
.ep-input-prefix { display:flex; align-items:center; border:1px solid var(--border-dark); border-radius:var(--radius); overflow:hidden; background:var(--surface); }
.ep-input-prefix:focus-within { border-color:var(--gold-dark); }
.ep-input-prefix span { padding:0 12px; color:var(--text-4); font-size:13px; border-right:1px solid var(--border); align-self:stretch; display:flex; align-items:center; background:var(--surface-2); white-space:nowrap; }
.ep-input-prefix .ep-input { border:none; }
.ep-hr { height:1px; background:var(--border); margin:6px 0 22px; }
.ep-help { font-size:12px; color:var(--text-3); margin-top:10px; }
.ep-textarea { resize:vertical; min-height:96px; line-height:1.55; font-family:var(--font-sans); }
.ep-textarea-lg { min-height:200px; }
.ep-textarea-foot { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:8px; }
.ep-count { font-size:11px; color:var(--text-4); margin-left:auto; flex-shrink:0; }
.ep-photo-row { display:flex; align-items:center; gap:18px; padding:16px 18px; border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--surface-2); margin-bottom:18px; }
.ep-photo-av { width:62px; height:62px; border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-size:22px; font-weight:700; flex-shrink:0; }
.ep-photo-info { flex:1; min-width:0; }
.ep-photo-name { font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); }
.ep-photo-note { font-size:12px; color:var(--text-4); margin:3px 0 10px; }
.ep-photo-actions { display:flex; gap:8px; flex-wrap:wrap; }
.ep-info-table { border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; margin-bottom:22px; background:var(--surface-2); }
.ep-info-row { display:flex; align-items:center; gap:16px; padding:12px 16px; border-bottom:1px solid var(--border); }
.ep-info-row:last-child { border-bottom:none; }
.ep-info-label { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); width:180px; flex-shrink:0; }
.ep-info-val { font-size:13px; font-weight:600; color:var(--text); display:inline-flex; align-items:center; gap:8px; }
.ep-info-val.ok { color:var(--green-dark); }
.ep-tag-pills { display:flex; gap:8px; flex-wrap:wrap; }
.ep-tag-pill { padding:8px 14px; font-size:12.5px; font-weight:500; color:var(--text-2); border:1px solid var(--border-dark); border-radius:var(--radius-full); background:var(--surface); cursor:pointer; transition:all .15s ease; }
.ep-tag-pill:hover { border-color:var(--gold-dark); }
.ep-tag-pill.active { background:var(--gold-dark); border-color:var(--gold-dark); color:var(--text-inverted); font-weight:600; }
.ep-states { display:grid; grid-template-columns:repeat(auto-fit, minmax(54px, 1fr)); gap:8px; }
.ep-state { padding:11px 0; font-size:12px; font-weight:600; text-align:center; border:1px solid var(--border-dark); border-radius:var(--radius); background:var(--surface); color:var(--text-2); cursor:pointer; transition:all .15s ease; }
.ep-state:hover { border-color:var(--gold-dark); }
.ep-state.active { background:var(--gold-dark); border-color:var(--gold-dark); color:var(--text-inverted); }
.ep-add-row { display:flex; gap:10px; margin-top:12px; }
.ep-add-row .ep-input { flex:1; }
.ep-add-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 14px; font-size:12px; font-weight:600; color:var(--text-3); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.ep-add-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.ep-add-block { margin-top:14px; }
.ep-savebar { position:sticky; bottom:0; z-index:20; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 24px; background:var(--surface); border-top:1px solid var(--border); box-shadow:0 -4px 16px rgba(44,34,24,.06); margin:0 -4px; }
.ep-savebar-status { font-size:12.5px; color:var(--text-3); display:inline-flex; align-items:center; gap:8px; }
.ep-savebar-status strong { color:var(--orange-dark); font-weight:700; }
.ep-savebar-dot { width:7px; height:7px; border-radius:50%; background:var(--orange-dark); flex-shrink:0; }

@media (max-width:1100px) { .pf-bigstats { grid-template-columns:repeat(3,1fr); } }
@media (max-width:1000px) { .pf-grid { grid-template-columns:1fr; } .ep-layout { grid-template-columns:1fr; } .ep-nav { position:static; } }
@media (max-width:900px) { .pf-bigstats { grid-template-columns:1fr 1fr; } .pf-header-main { flex-direction:column; } .ep-form-row { grid-template-columns:1fr; } .ep-header { flex-direction:column; } }
</style>
