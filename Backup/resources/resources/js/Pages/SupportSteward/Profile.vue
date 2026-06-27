<template>
  <AppLayout :user="user" portal="support_steward" activePage="profile" :page-title="mode === 'edit' ? 'Edit Profile' : 'My Profile'" :has-emergency="true">

    <!-- ════════════════ READ-ONLY PROFILE ════════════════ -->
    <template v-if="mode === 'view'">
      <div class="pf-header">
        <div class="pf-header-main">
          <div class="pf-avatar">LJ</div>
          <div class="pf-header-info">
            <div class="pf-eyebrow">Support Steward</div>
            <h1 class="pf-name">Linda Johnson</h1>
            <div class="pf-role">Practice Administrator · Lotus Psychology Group</div>
            <div class="pf-chips">
              <span class="pf-chip green"><span class="pf-dot"></span> Active</span>
              <span class="pf-chip blue">Primary SS</span>
              <span class="pf-chip neutral"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Private profile</span>
            </div>
          </div>
          <div class="pf-header-actions">
            <button type="button" class="btn btn-primary btn-sm" @click="mode = 'edit'"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg> Edit Profile</button>
            <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></button>
          </div>
        </div>
        <div class="pf-header-meta">
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> San Francisco, CA</span>
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> w/ Dr. Sarah Johnson</span>
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> 1.6 hr avg</span>
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Signed Feb 14, 2026</span>
        </div>
      </div>

      <div class="pf-stats">
        <div class="pf-stat"><div class="pf-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div><div class="pf-stat-num">1</div><div class="pf-stat-label">Activations</div></div>
        <div class="pf-stat"><div class="pf-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div><div class="pf-stat-num">100%</div><div class="pf-stat-label">On-Time Rate</div></div>
        <div class="pf-stat"><div class="pf-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div><div class="pf-stat-num">47</div><div class="pf-stat-label">Tasks Done</div></div>
        <div class="pf-stat"><div class="pf-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div class="pf-stat-num">0</div><div class="pf-stat-label">Missed SLAs</div></div>
      </div>

      <div class="pf-private">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <div><strong>Private Support Steward profile</strong><span>This page is visible only to <strong>Dr. Sarah Johnson</strong> and Linda Johnson. It is not listed in search results and cannot be accessed by anyone outside that relationship.</span></div>
      </div>

      <div class="pf-grid">
        <div class="pf-col">
          <div class="pf-card">
            <div class="pf-card-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> About Linda</div>
            <div class="pf-card-body"><p class="pf-text">I am Sarah's sister and have been running the administrative side of her practice for 8 years. I handle scheduling, intake, insurance, and day-to-day operations. If anything feels off, I am usually the first to notice.</p></div>
          </div>

          <div class="pf-card">
            <div class="pf-card-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Relationship &amp; Agreement</div>
            <div class="pf-card-body">
              <div class="pf-row"><span>Linked Provider</span><strong>Dr. Sarah Johnson</strong></div>
              <div class="pf-row"><span>Plan Role</span><strong>Primary Support Steward</strong></div>
              <div class="pf-row"><span>Status</span><strong class="ok">Active</strong></div>
              <div class="pf-row"><span>Agreement Signed</span><strong>Feb 14, 2026</strong></div>
              <div class="pf-row"><span>Plan Certified</span><strong class="ok">Feb 14, 2026</strong></div>
              <div class="pf-note"><div class="pf-note-label">Certification Note</div><div class="pf-note-text">"I understand my role and have reviewed all task lists."</div></div>
            </div>
          </div>

          <div class="pf-card">
            <div class="pf-card-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Availability</div>
            <div class="pf-card-body">
              <div class="pf-sub">Weekly Schedule &amp; Response Readiness</div>
              <div class="pf-days">
                <div v-for="d in availabilityView" :key="d.day" class="pf-day">
                  <div class="pf-day-name">{{ d.day }}</div>
                  <div class="pf-day-slot" :class="d.state">{{ d.hours }}</div>
                </div>
              </div>
              <div class="pf-avail-foot">
                <div class="pf-legend"><span class="lg avail">Available</span><span class="lg partial">Partial</span><span class="lg off">Off</span></div>
                <span class="pf-247"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> 24/7 Critical Incident Response</span>
              </div>
            </div>
          </div>
        </div>

        <div class="pf-col">
          <div class="pf-card">
            <div class="pf-card-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> Contact</div>
            <div class="pf-card-body">
              <div class="pf-row"><span>Location</span><strong>San Francisco, CA</strong></div>
              <div class="pf-row"><span>Email</span><strong class="gold">linda.johnson@gmail.com</strong></div>
              <div class="pf-row"><span>Phone</span><strong>(415) 555-0142</strong></div>
            </div>
          </div>

          <div class="pf-card">
            <div class="pf-card-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg> Affiliation</div>
            <div class="pf-card-body">
              <div class="pf-row"><span>Organization</span><strong>Lotus Psychology Group</strong></div>
              <div class="pf-row"><span>Role</span><strong>Practice Administrator</strong></div>
            </div>
          </div>

          <div class="pf-card">
            <div class="pf-card-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Work Context</div>
            <div class="pf-card-body">
              <div class="pf-row"><span>Tenure</span><strong>14 months</strong></div>
              <div class="pf-row"><span>Verified</span><strong class="ok">Identity confirmed by Provider</strong></div>
              <div class="pf-row"><span>Time Zone</span><strong>Pacific (UTC-8)</strong></div>
              <div class="pf-row"><span>Vault Access</span><strong>None (by SS role design)</strong></div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ════════════════ EDIT PROFILE ════════════════ -->
    <template v-else>
      <div class="ep-header">
        <div class="ep-header-l">
          <div class="ep-header-eyebrow">Support Steward Profile</div>
          <h1 class="ep-title">Edit Profile</h1>
          <div class="ep-subtitle">Linda Johnson · You reviewed your Continuity Stewards roster · Lotus Psychology Group</div>
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
              <span v-if="item.status === 'done'" class="ep-nav-status done"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
              <span v-else class="ep-nav-status todo"></span>
            </button>
          </template>
        </aside>

        <section class="ep-panel">
          <!-- IDENTITY -->
          <div v-show="section === 'identity'">
            <div class="ep-info-banner" style="margin-top:0;margin-bottom:18px;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
              <span><strong>Unified across all portals</strong> — Your display name, email, and phone apply to every portal you have access to. Changes here update your identity platform-wide.</span>
            </div>

            <div class="ep-card">
              <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div><div><div class="ep-card-title">Profile Photo</div><div class="ep-card-sub">Shown to assigned practitioners and the Continuity Steward</div></div></div>
              <div class="ep-card-body">
                <div class="ep-photo-row">
                  <div class="ep-photo-av">LJ</div>
                  <div class="ep-photo-info">
                    <div class="ep-photo-name">Linda Johnson</div>
                    <div class="ep-photo-note">No photo uploaded — initials shown as placeholder</div>
                    <div class="ep-photo-actions"><button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Upload Photo</button><button type="button" class="btn btn-outline btn-sm">Remove</button></div>
                  </div>
                </div>
                <div class="ep-field"><label class="ep-label">Display Initials</label><input class="ep-input" value="LJ" maxlength="2" /><div class="ep-help">Max 2 characters — used in your avatar circle</div></div>
              </div>
            </div>

            <div class="ep-card">
              <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div><div class="ep-card-title">Basic Information</div><div class="ep-card-sub">Shared with your practitioner and the Continuity Steward</div></div></div>
              <div class="ep-card-body">
                <div class="ep-form-row">
                  <div class="ep-field"><label class="ep-label">First Name *</label><input class="ep-input" value="Linda" /></div>
                  <div class="ep-field"><label class="ep-label">Last Name *</label><input class="ep-input" value="Johnson" /></div>
                </div>
                <div class="ep-field"><label class="ep-label">Role / Relationship to Practitioner *</label><input class="ep-input" value="You reviewed your Continuity Stewards roster" /></div>
                <div class="ep-field"><label class="ep-label">Organization / Affiliation <span class="ep-opt">(Optional)</span></label><input class="ep-input" value="Lotus Psychology Group" /></div>
                <div class="ep-field">
                  <label class="ep-label">Bio / How I Can Help *</label>
                  <textarea class="ep-input ep-textarea" rows="3" maxlength="1000" v-model="bio"></textarea>
                  <div class="ep-textarea-foot"><span class="ep-count">{{ bio.length }} / 1000</span></div>
                  <div class="ep-help">Describe your relationship to the practitioner, your availability, and how you can help during a critical incident.</div>
                </div>
              </div>
            </div>

            <div class="ep-card">
              <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div><div><div class="ep-card-title">Contact &amp; Location</div><div class="ep-card-sub">How the Continuity Steward reaches you during a critical incident</div></div></div>
              <div class="ep-card-body">
                <div class="ep-form-row">
                  <div class="ep-field"><label class="ep-label">City</label><input class="ep-input" value="San Francisco" /></div>
                  <div class="ep-field"><label class="ep-label">State</label><input class="ep-input" value="CA" /></div>
                  <div class="ep-field"><label class="ep-label">Phone *</label><input class="ep-input" value="(415) 555-0142" /></div>
                  <div class="ep-field"><label class="ep-label">Alternate Phone <span class="ep-opt">(Optional)</span></label><input class="ep-input" placeholder="(XXX) XXX-XXXX" /></div>
                </div>
                <div class="ep-field"><label class="ep-label">Email *</label><input class="ep-input" value="linda.johnson@gmail.com" /></div>
                <div class="ep-field">
                  <label class="ep-label">Languages Spoken</label>
                  <div class="ep-tag-pills"><button v-for="lang in languageOptions" :key="lang" type="button" class="ep-tag-pill" :class="{ active: languages.includes(lang) }" @click="toggleArr(languages, lang)">{{ lang }}</button></div>
                  <div class="ep-add-row"><input class="ep-input" v-model="newLanguage" placeholder="Add other language..." @keyup.enter="addLanguage" /><button type="button" class="ep-add-btn" @click="addLanguage"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add</button></div>
                </div>
              </div>
            </div>
          </div>

          <!-- ABOUT & BIO -->
          <div v-show="section === 'about'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg></div><div><div class="ep-card-title">About &amp; Bio</div><div class="ep-card-sub">Your story — shown to the practitioner and Continuity Steward</div></div></div>
            <div class="ep-card-body">
              <div class="ep-field"><label class="ep-label">Bio / How I Can Help *</label><textarea class="ep-input ep-textarea ep-textarea-lg" maxlength="1000" v-model="bio"></textarea><div class="ep-textarea-foot"><span class="ep-help" style="margin-top:0;">Describe your relationship, availability, and how you can help during a critical incident.</span><span class="ep-count">{{ bio.length }} / 1000</span></div></div>
            </div>
          </div>

          <!-- CONTACT INFO -->
          <div v-show="section === 'contact'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div><div><div class="ep-card-title">Contact Information</div><div class="ep-card-sub">How practitioners and the Continuity Steward can reach you</div></div></div>
            <div class="ep-card-body">
              <div class="ep-form-row">
                <div class="ep-field"><label class="ep-label">Primary Phone (24/7) *</label><input class="ep-input" value="(415) 555-0142" /></div>
                <div class="ep-field"><label class="ep-label">Alternate Phone</label><input class="ep-input" placeholder="(000) 000-0000" /></div>
                <div class="ep-field"><label class="ep-label">Email Address *</label><input class="ep-input" value="linda.johnson@gmail.com" /></div>
                <div class="ep-field"><label class="ep-label">Preferred Contact Method</label><select class="form-select"><option>Phone</option><option>Email</option><option>In-app Messages</option></select></div>
              </div>
              <div class="ep-field"><label class="ep-label">Street Address <span class="ep-opt">(Optional)</span></label><input class="ep-input" placeholder="Street, Suite" /></div>
              <div class="ep-form-row ep-row-3">
                <div class="ep-field"><label class="ep-label">City</label><input class="ep-input" value="San Francisco" /></div>
                <div class="ep-field"><label class="ep-label">State</label><input class="ep-input" value="CA" /></div>
                <div class="ep-field"><label class="ep-label">Timezone</label><select class="form-select"><option>PST (UTC-8)</option><option>MST (UTC-7)</option><option>CST (UTC-6)</option><option>EST (UTC-5)</option></select></div>
              </div>
            </div>
          </div>

          <!-- WORK HISTORY -->
          <div v-show="section === 'work'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div><div><div class="ep-card-title">Work History</div><div class="ep-card-sub">Your role and experience supporting this practice</div></div></div>
            <div class="ep-card-body">
              <div class="ep-form-row">
                <div class="ep-field"><label class="ep-label">Organization</label><input class="ep-input" value="Lotus Psychology Group" /></div>
                <div class="ep-field"><label class="ep-label">Role / Title</label><input class="ep-input" value="Practice Administrator" /></div>
                <div class="ep-field"><label class="ep-label">Years in Role</label><input class="ep-input" value="8 years" /></div>
                <div class="ep-field"><label class="ep-label">Tenure with Practitioner</label><input class="ep-input" value="14 months" /></div>
              </div>
              <button type="button" class="ep-add-btn ep-add-block"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Work History</button>
            </div>
          </div>

          <!-- BACKGROUND & SKILLS -->
          <div v-show="section === 'skills'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div><div><div class="ep-card-title">Background &amp; Skills</div><div class="ep-card-sub">Relevant experience that helps during a critical incident</div></div></div>
            <div class="ep-card-body">
              <div class="ep-subhead-sm">Operational Skills</div>
              <div class="ep-tag-pills"><button v-for="s in skillOptions" :key="s" type="button" class="ep-tag-pill" :class="{ active: skills.includes(s) }" @click="toggleArr(skills, s)">{{ s }}</button></div>
              <div class="ep-subhead-sm" style="margin-top:18px;">Background</div>
              <div class="ep-tag-pills"><button v-for="b in bgOptions" :key="b" type="button" class="ep-tag-pill" :class="{ active: backgrounds.includes(b) }" @click="toggleArr(backgrounds, b)">{{ b }}</button></div>
            </div>
          </div>

          <!-- DESIGNATION INFO -->
          <div v-show="section === 'designation'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div><div class="ep-card-title">Designation Info</div><div class="ep-card-sub">Your Support Steward designation and agreement details</div></div></div>
            <div class="ep-card-body">
              <div class="ep-info-table">
                <div class="ep-info-row"><span class="ep-info-label">Linked Provider</span><span class="ep-info-val">Dr. Sarah Johnson</span></div>
                <div class="ep-info-row"><span class="ep-info-label">Plan Role</span><span class="ep-info-val">Primary Support Steward <span class="ep-tag-badge">Primary SS</span></span></div>
                <div class="ep-info-row"><span class="ep-info-label">Status</span><span class="ep-info-val ok">Active</span></div>
                <div class="ep-info-row"><span class="ep-info-label">Agreement Signed</span><span class="ep-info-val">Feb 14, 2026</span></div>
                <div class="ep-info-row"><span class="ep-info-label">Vault Access</span><span class="ep-info-val">None (by SS role design)</span></div>
              </div>
              <div class="ep-form-row">
                <div class="ep-field"><label class="ep-label">Plan Role</label><select class="form-select"><option>Primary Support Steward</option><option>Alternate Support Steward</option></select></div>
                <div class="ep-field"><label class="ep-label">Status</label><select class="form-select"><option>Active</option><option>Standby</option><option>Inactive</option></select></div>
              </div>
              <div class="ep-toggle-row"><div class="ep-toggle-info"><strong>Incident Notifications</strong><span>Alerts when an incident is reported or status changes</span></div><button type="button" class="toggle" :class="{ on: designation.notifs }" @click="designation.notifs = !designation.notifs"></button></div>
            </div>
          </div>

          <!-- EMERGENCY CONTACT -->
          <div v-show="section === 'emergency'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico warn"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div><div><div class="ep-card-title">Emergency Contact</div><div class="ep-card-sub">Required for profile completion — only used in crisis situations</div></div><div class="ep-req-label">Required for 100%</div></div>
            <div class="ep-card-body">
              <div class="ep-warn-banner"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><span><strong>This information is private.</strong> It is only accessible to Aegis admins and is never shared with practitioners.</span></div>
              <div class="ep-form-row">
                <div class="ep-field"><label class="ep-label">Contact Name *</label><input class="ep-input" placeholder="Full name" /></div>
                <div class="ep-field"><label class="ep-label">Relationship *</label><select class="form-select"><option>Select relationship</option><option>Spouse / Partner</option><option>Parent</option><option>Sibling</option><option>Friend</option><option>Other</option></select></div>
                <div class="ep-field"><label class="ep-label">Phone Number *</label><input class="ep-input" placeholder="(000) 000-0000" /></div>
                <div class="ep-field"><label class="ep-label">Email Address <span class="ep-opt">(Optional)</span></label><input class="ep-input" placeholder="email@example.com" /></div>
              </div>
            </div>
          </div>

          <!-- AVAILABILITY -->
          <div v-show="section === 'availability'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="ep-card-title">Availability Windows</div><div class="ep-card-sub">Days and hours you can be reached for urgent matters</div></div></div>
            <div class="ep-card-body">
              <div class="ep-field"><label class="ep-label">Available Days</label><div class="ep-days"><button v-for="d in days" :key="d" type="button" class="ep-day" :class="{ active: availableDays.includes(d) }" @click="toggleArr(availableDays, d)">{{ d }}</button></div></div>
              <div class="ep-form-row">
                <div class="ep-field"><label class="ep-label">Response Time SLA</label><select class="form-select"><option>Within 2 hours</option><option>Within 4 business hours</option><option>Within 24 hours</option></select></div>
                <div class="ep-field"><label class="ep-label">Timezone</label><select class="form-select"><option>Pacific Time (PST)</option><option>Mountain Time (MST)</option><option>Central Time (CST)</option><option>Eastern Time (EST)</option></select></div>
              </div>
              <div class="ep-toggle-row"><div class="ep-toggle-info"><strong>After-Hours Alerts</strong><span>Available for critical incident alerts outside business hours</span></div><button type="button" class="toggle" :class="{ on: availability2.afterHours }" @click="availability2.afterHours = !availability2.afterHours"></button></div>
              <div class="ep-toggle-row"><div class="ep-toggle-info"><strong>24/7 Critical Incident Response</strong><span>Reachable any time when an incident is active</span></div><button type="button" class="toggle" :class="{ on: availability2.always }" @click="availability2.always = !availability2.always"></button></div>
            </div>
          </div>

          <!-- PREFERENCES -->
          <div v-show="section === 'preferences'" class="ep-card">
            <div class="ep-card-head"><div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></div><div><div class="ep-card-title">Preferences</div><div class="ep-card-sub">Notifications, language, and portal display preferences</div></div></div>
            <div class="ep-card-body">
              <div class="ep-subhead">Notification Settings</div>
              <div class="ep-toggle-row" v-for="pref in prefRows" :key="pref.key"><div class="ep-toggle-info"><strong>{{ pref.label }}</strong><span>{{ pref.desc }}</span></div><button type="button" class="toggle" :class="{ on: prefs[pref.key] }" @click="prefs[pref.key] = !prefs[pref.key]"></button></div>
              <div class="ep-form-row" style="margin-top:20px;">
                <div class="ep-field"><label class="ep-label">Language</label><select class="form-select"><option>English (US)</option><option>Español</option><option>Français</option></select></div>
                <div class="ep-field"><label class="ep-label">Date Format</label><select class="form-select"><option>MM/DD/YYYY</option><option>DD/MM/YYYY</option><option>YYYY-MM-DD</option></select></div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <div class="ep-savebar">
        <div class="ep-savebar-status"><span class="ep-savebar-dot"></span> <strong>Unsaved changes</strong> · Changes save to your Aegis Support Steward profile</div>
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
    { key: 'identity', label: 'Identity', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>` },
    { key: 'about', label: 'About & Bio', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>` },
    { key: 'contact', label: 'Contact Info', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>` },
    { key: 'work', label: 'Work History', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>` },
    { key: 'skills', label: 'Background & Skills', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>` },
    { key: 'designation', label: 'Designation Info', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>` },
    { key: 'emergency', label: 'Emergency Contact', status: 'todo', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>` },
  ] },
  { label: 'Settings', items: [
    { key: 'availability', label: 'Availability', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>` },
    { key: 'preferences', label: 'Preferences', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>` },
  ] },
];

const bio = ref('');
const languageOptions = ['English', 'Spanish', 'Mandarin Chinese', 'French', 'Arabic', 'Hindi', 'Portuguese', 'Russian', 'Korean', 'Japanese', 'Vietnamese', 'Tagalog', 'Italian', 'German'];
const languages = reactive(['Korean']);
const newLanguage = ref('');
function addLanguage() { const v = newLanguage.value.trim(); if (v && !languages.includes(v)) languages.push(v); newLanguage.value = ''; }

const skillOptions = ['Scheduling & Intake', 'Insurance & Billing', 'Patient Communication', 'Records Management', 'Vendor Coordination', 'HR / Staffing'];
const skills = reactive(['Scheduling & Intake', 'Insurance & Billing']);
const bgOptions = ['Practice Administrator', 'Office Manager', 'Family Member', 'Medical Assistant', 'Bookkeeper', 'Notary Public'];
const backgrounds = reactive(['Practice Administrator', 'Family Member']);

const designation = reactive({ notifs: true });

const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const availableDays = reactive(['Mon', 'Tue', 'Wed', 'Thu', 'Fri']);
const availability2 = reactive({ afterHours: true, always: true });

const prefRows = [
  { key: 'critical', label: 'Critical Incident Alerts', desc: 'Immediate notifications when an incident is reported' },
  { key: 'assignment', label: 'Assignment Updates', desc: 'Designation changes and status updates' },
  { key: 'tasks', label: 'Task Reminders', desc: 'Standby task due dates' },
  { key: 'email', label: 'Email', desc: 'Receive notifications by email' },
  { key: 'sms', label: 'SMS / Text', desc: 'Receive notifications via text message' },
  { key: 'inapp', label: 'In-App', desc: 'Receive notifications inside Aegis' },
];
const prefs = reactive({ critical: true, assignment: true, tasks: true, email: true, sms: false, inapp: true });

const availabilityView = [
  { day: 'Mon', hours: '9-5', state: 'avail' },
  { day: 'Tue', hours: '9-5', state: 'avail' },
  { day: 'Wed', hours: '9-5', state: 'avail' },
  { day: 'Thu', hours: '9-5', state: 'avail' },
  { day: 'Fri', hours: '9-1', state: 'partial' },
  { day: 'Sat', hours: 'Off', state: 'off' },
  { day: 'Sun', hours: 'Off', state: 'off' },
];
</script>

<style scoped>
/* ════════════════ READ-ONLY PROFILE ════════════════ */
.pf-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:26px 30px; margin-bottom:16px; box-shadow:var(--shadow-sm); }
.pf-header-main { display:flex; align-items:flex-start; gap:22px; }
.pf-avatar { width:80px; height:80px; border-radius:50%; background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-size:26px; font-weight:700; flex-shrink:0; }
.pf-header-info { flex:1; min-width:0; }
.pf-eyebrow { font-size:11px; font-weight:700; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:6px; }
.pf-name { font-family:var(--font-serif); font-size:32px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.pf-role { font-size:14px; color:var(--text-3); margin-top:6px; }
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
.pf-header-meta span:last-child svg { color:var(--green-dark); }

.pf-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.pf-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px 20px; box-shadow:var(--shadow-xs); display:grid; grid-template-columns:auto 1fr; grid-template-rows:auto auto; column-gap:14px; align-items:center; }
.pf-stat-ico { grid-row:1 / 3; width:36px; height:36px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.pf-stat-num { font-family:var(--font-serif); font-size:24px; font-weight:700; line-height:1; color:var(--text); }
.pf-stat-label { font-size:12px; color:var(--text-3); margin-top:3px; }

.pf-private { display:flex; align-items:flex-start; gap:11px; padding:13px 18px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius-lg); margin-bottom:18px; }
.pf-private svg { color:var(--gold-dark); flex-shrink:0; margin-top:2px; }
.pf-private strong { display:block; font-size:12.5px; font-weight:700; color:var(--text); margin-bottom:2px; }
.pf-private span { font-size:12px; color:var(--text-3); line-height:1.55; }
.pf-private span strong { display:inline; color:var(--text-2); }

.pf-grid { display:grid; grid-template-columns:1fr 360px; gap:16px; align-items:start; }
.pf-col { display:flex; flex-direction:column; gap:16px; }
.pf-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; }
.pf-card-head { display:flex; align-items:center; gap:9px; padding:15px 20px; border-bottom:1px solid var(--border); font-size:14px; font-weight:700; color:var(--text); }
.pf-card-head svg { color:var(--gold-dark); }
.pf-card-body { padding:18px 20px; }
.pf-text { font-size:13px; color:var(--text-2); line-height:1.7; margin:0; }
.pf-row { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:9px 0; border-bottom:1px solid var(--border); font-size:13px; color:var(--text-3); }
.pf-row:last-child { border-bottom:none; }
.pf-row strong { color:var(--text); font-weight:600; text-align:right; }
.pf-row strong.ok { color:var(--green-dark); }
.pf-row strong.gold { color:var(--gold-dark); }
.pf-note { margin-top:14px; padding:13px 16px; background:var(--badge-bg-gold); border-left:3px solid var(--gold-dark); border-radius:var(--radius); }
.pf-note-label { font-size:9.5px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); margin-bottom:5px; }
.pf-note-text { font-size:12.5px; color:var(--text-2); font-style:italic; }
.pf-sub { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); margin-bottom:12px; }
.pf-days { display:grid; grid-template-columns:repeat(7,1fr); gap:8px; }
.pf-day { text-align:center; }
.pf-day-name { font-size:9.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--text-4); margin-bottom:6px; }
.pf-day-slot { padding:11px 0; font-size:12px; font-weight:600; border-radius:var(--radius); }
.pf-day-slot.avail { background:var(--green-light); color:var(--green-dark); }
.pf-day-slot.partial { background:var(--badge-bg-gold); color:var(--orange-dark); }
.pf-day-slot.off { background:var(--surface-3); color:var(--text-4); }
.pf-avail-foot { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:16px; flex-wrap:wrap; }
.pf-legend { display:flex; gap:14px; }
.pf-legend .lg { display:inline-flex; align-items:center; gap:6px; font-size:11px; color:var(--text-3); }
.pf-legend .lg::before { content:''; width:7px; height:7px; border-radius:50%; }
.pf-legend .lg.avail::before { background:var(--green); }
.pf-legend .lg.partial::before { background:var(--orange); }
.pf-legend .lg.off::before { background:var(--border-dark); }
.pf-247 { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; color:var(--text-3); }
.pf-247 svg { color:var(--gold-dark); }

/* ════════════════ EDIT PROFILE ════════════════ */
.ep-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:26px 30px; margin-bottom:16px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.ep-header-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.ep-title { font-family:var(--font-serif); font-size:34px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.ep-subtitle { font-size:13.5px; color:var(--text-3); margin-top:8px; }
.ep-meta { display:flex; align-items:center; gap:18px; margin-top:12px; }
.ep-meta span { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; color:var(--text-4); }
.ep-meta span:first-child svg { color:var(--green-dark); }
.ep-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; }

.cs-profile-banner { margin-bottom:22px; background:linear-gradient(95deg,var(--badge-bg-gold) 0%,var(--surface) 80%); border:1px solid rgba(192,154,82,.28); border-radius:var(--radius-lg); padding:14px 20px; display:flex; align-items:center; gap:16px; box-shadow:var(--shadow-sm); }
.cs-profile-icon { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cs-profile-body { flex:1; min-width:0; }
.cs-profile-title { font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px; }
.cs-profile-sub { font-size:12px; color:var(--text-3); }
.cs-profile-progress { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.cs-profile-bar { width:120px; height:5px; background:var(--surface-3); border-radius:var(--radius-full); overflow:hidden; }
.cs-profile-bar-fill { height:100%; background:linear-gradient(90deg,var(--gold),var(--gold-dark)); border-radius:var(--radius-full); }
.cs-profile-pct { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.cs-profile-cta { flex-shrink:0; white-space:nowrap; }

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
.ep-nav-status.todo { width:7px; height:7px; background:var(--orange-dark); margin:0 4px; }

.ep-panel { display:flex; flex-direction:column; gap:18px; }
.ep-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; }
.ep-card + .ep-card { margin-top:18px; }
.ep-card-head { display:flex; align-items:center; gap:14px; padding:22px 26px; background:var(--surface-2); border-bottom:1px solid var(--border); }
.ep-card-head-ico { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ep-card-head-ico.warn { background:rgba(181,99,10,.12); color:var(--orange-dark); }
.ep-card-title { font-family:var(--font-serif); font-size:18px; font-weight:600; color:var(--text); }
.ep-card-sub { font-size:12.5px; color:var(--text-3); margin-top:2px; }
.ep-card-body { padding:24px 26px; }
.ep-req-label { margin-left:auto; font-size:9.5px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--gold-dark); flex-shrink:0; }
.ep-subhead { font-size:13px; font-weight:700; color:var(--text); margin-bottom:8px; }
.ep-subhead-sm { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); padding-bottom:8px; border-bottom:1px solid var(--border); margin-bottom:12px; }
.ep-field { margin-bottom:18px; }
.ep-field:last-child { margin-bottom:0; }
.ep-form-row { display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:4px; }
.ep-form-row.ep-row-3 { grid-template-columns:1fr 1fr 1fr; }
.ep-label { display:block; font-size:12px; font-weight:600; color:var(--text-2); margin-bottom:7px; }
.ep-opt { font-weight:400; color:var(--text-4); }
.ep-input { display:block; width:100%; padding:10px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.ep-input:focus { border-color:var(--gold-dark); }
.ep-input::placeholder { color:var(--text-4); }
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
.ep-toggle-row { display:flex; align-items:center; justify-content:space-between; gap:20px; padding:14px 0; border-bottom:1px solid var(--border); }
.ep-toggle-row:last-child { border-bottom:none; }
.ep-toggle-info strong { display:block; font-size:13px; font-weight:600; color:var(--text); margin-bottom:2px; }
.ep-toggle-info span { font-size:12px; color:var(--text-3); }
.toggle { position:relative; width:42px; height:24px; flex-shrink:0; border:none; padding:0; border-radius:var(--radius-full); background:var(--border-dark); cursor:pointer; transition:background var(--transition); }
.toggle::after { content:''; position:absolute; width:18px; height:18px; top:50%; left:3px; background:var(--surface); border-radius:var(--radius-full); transform:translateY(-50%); transition:transform var(--transition); box-shadow:0 1px 4px rgba(44,34,24,0.22); }
.toggle.on { background:var(--gold-dark); }
.toggle.on::after { transform:translate(18px,-50%); }
.ep-info-table { border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; margin-bottom:22px; background:var(--surface-2); }
.ep-info-row { display:flex; align-items:center; gap:16px; padding:12px 16px; border-bottom:1px solid var(--border); }
.ep-info-row:last-child { border-bottom:none; }
.ep-info-label { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); width:180px; flex-shrink:0; }
.ep-info-val { font-size:13px; font-weight:600; color:var(--text); display:inline-flex; align-items:center; gap:8px; }
.ep-info-val.ok { color:var(--green-dark); }
.ep-tag-badge { font-size:9px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; padding:2px 8px; border-radius:var(--radius-full); background:var(--badge-bg-gold); color:var(--gold-dark); }
.ep-days { display:flex; gap:8px; flex-wrap:wrap; }
.ep-day { width:54px; padding:12px 0; font-size:12px; font-weight:600; text-align:center; border:1px solid var(--border-dark); border-radius:var(--radius); background:var(--surface); color:var(--text-4); cursor:pointer; transition:all .15s ease; }
.ep-day:hover { border-color:var(--gold-dark); }
.ep-day.active { background:var(--gold-dark); border-color:var(--gold-dark); color:var(--text-inverted); }
.ep-tag-pills { display:flex; gap:8px; flex-wrap:wrap; }
.ep-tag-pill { padding:8px 14px; font-size:12.5px; font-weight:500; color:var(--text-2); border:1px solid var(--border-dark); border-radius:var(--radius-full); background:var(--surface); cursor:pointer; transition:all .15s ease; }
.ep-tag-pill:hover { border-color:var(--gold-dark); }
.ep-tag-pill.active { background:var(--gold-dark); border-color:var(--gold-dark); color:var(--text-inverted); font-weight:600; }
.ep-add-row { display:flex; gap:10px; margin-top:12px; }
.ep-add-row .ep-input { flex:1; }
.ep-add-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 14px; font-size:12px; font-weight:600; color:var(--text-3); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.ep-add-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.ep-add-block { margin-top:14px; }
.ep-info-banner { display:flex; align-items:flex-start; gap:10px; font-size:12.5px; color:var(--text-2); line-height:1.55; background:rgba(80,120,190,.06); border:1px solid rgba(80,120,190,.18); border-radius:var(--radius); padding:13px 16px; }
.ep-info-banner svg { color:#5078be; flex-shrink:0; margin-top:1px; }
.ep-info-banner strong { color:var(--text); }
.ep-warn-banner { display:flex; align-items:flex-start; gap:10px; font-size:12.5px; color:var(--text-2); line-height:1.55; background:rgba(181,99,10,.07); border:1px solid rgba(181,99,10,.22); border-radius:var(--radius); padding:13px 16px; margin-bottom:22px; }
.ep-warn-banner svg { color:var(--orange-dark); flex-shrink:0; margin-top:1px; }
.ep-warn-banner strong { color:var(--orange-dark); }
.ep-savebar { position:sticky; bottom:0; z-index:20; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 24px; background:var(--surface); border-top:1px solid var(--border); box-shadow:0 -4px 16px rgba(44,34,24,.06); margin:0 -4px; }
.ep-savebar-status { font-size:12.5px; color:var(--text-3); display:inline-flex; align-items:center; gap:8px; }
.ep-savebar-status strong { color:var(--orange-dark); font-weight:700; }
.ep-savebar-dot { width:7px; height:7px; border-radius:50%; background:var(--orange-dark); flex-shrink:0; }

@media (max-width:1000px) { .pf-grid { grid-template-columns:1fr; } .ep-layout { grid-template-columns:1fr; } .ep-nav { position:static; } }
@media (max-width:900px) { .pf-stats { grid-template-columns:1fr 1fr; } .pf-header-main { flex-direction:column; } .ep-form-row, .ep-form-row.ep-row-3 { grid-template-columns:1fr; } .ep-header { flex-direction:column; } }
</style>
