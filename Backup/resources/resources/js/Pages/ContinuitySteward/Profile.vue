<template>
  <AppLayout :user="user" portal="continuity_steward" activePage="profile" pageTitle="Edit Profile">

    <!-- ═══ HEADER CARD ═══ -->
    <div class="ep-header">
      <div class="ep-header-l">
        <h1 class="ep-title">Edit Profile</h1>
        <div class="ep-subtitle">Dr. Priya Raman, PsyD · Continuity Partners</div>
        <div class="ep-meta">
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Saved 2h ago</span>
          <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Last reviewed Apr 28</span>
        </div>
      </div>
      <div class="ep-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <button type="button" class="btn btn-outline btn-sm ep-btn-muted"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Preview</button>
        <button type="button" class="btn btn-primary btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save Changes</button>
      </div>
    </div>

    <!-- ═══ FINISH PROFILE BANNER ═══ -->
    <div class="cs-profile-banner">
      <div class="cs-profile-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
      <div class="cs-profile-body">
        <div class="cs-profile-title">Finish your profile</div>
        <div class="cs-profile-sub">Add credentials &amp; availability to improve discovery</div>
      </div>
      <div class="cs-profile-progress">
        <div class="cs-profile-bar"><div class="cs-profile-bar-fill" :style="{ width: profilePct + '%' }"></div></div>
        <span class="cs-profile-pct">{{ profilePct }}%</span>
      </div>
      <button type="button" class="btn btn-primary btn-sm cs-profile-cta">Complete <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></button>
    </div>

    <!-- ═══ LAYOUT: NAV + PANEL ═══ -->
    <div class="ep-layout">
      <!-- NAV -->
      <aside class="ep-nav">
        <template v-for="group in navGroups" :key="group.label">
          <div class="ep-nav-group">{{ group.label }}</div>
          <button
            v-for="item in group.items" :key="item.key"
            type="button"
            class="ep-nav-item"
            :class="{ active: activeSection === item.key }"
            @click="activeSection = item.key"
          >
            <span class="ep-nav-ico" v-html="item.icon"></span>
            <span class="ep-nav-label">{{ item.label }}</span>
            <span v-if="item.status === 'done'" class="ep-nav-status done"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
            <span v-else class="ep-nav-status todo"></span>
          </button>
        </template>
      </aside>

      <!-- PANEL -->
      <section class="ep-panel">

        <!-- ── CONTACT INFORMATION ── -->
        <div v-show="activeSection === 'contact'" class="ep-card">
          <div class="ep-card-head">
            <div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
            <div><div class="ep-card-title">Contact Information</div><div class="ep-card-sub">How practitioners and Aegis admins can reach you</div></div>
          </div>
          <div class="ep-card-body">
            <div class="ep-form-row">
              <div class="ep-field"><label class="ep-label">Primary Phone (24/7) *</label><input class="ep-input" value="(415) 555-0167" /></div>
              <div class="ep-field"><label class="ep-label">Backup / Alternate Phone</label><input class="ep-input" placeholder="(000) 000-0000" /></div>
              <div class="ep-field"><label class="ep-label">Email Address *</label><input class="ep-input" value="priya.raman@continuitypartners.net" /></div>
              <div class="ep-field"><label class="ep-label">Website <span class="ep-opt">(Optional)</span></label><div class="ep-input-prefix"><span>https://</span><input class="ep-input" placeholder="yoursite.com" /></div></div>
            </div>
            <div class="ep-field"><label class="ep-label">Street Address *</label><input class="ep-input" placeholder="456 Park Avenue, Suite 1800" /></div>
            <div class="ep-form-row ep-row-3">
              <div class="ep-field"><label class="ep-label">City *</label><input class="ep-input" value="San Francisco" /></div>
              <div class="ep-field"><label class="ep-label">State *</label><select class="form-select"><option>California</option><option>New York</option><option>Texas</option></select></div>
              <div class="ep-field"><label class="ep-label">Timezone</label><select class="form-select"><option>PST (UTC-8)</option><option>MST (UTC-7)</option><option>CST (UTC-6)</option><option>EST (UTC-5)</option></select></div>
            </div>
            <div class="ep-field">
              <label class="ep-label">Languages Spoken</label>
              <div class="ep-tag-pills">
                <button v-for="lang in languageOptions" :key="lang" type="button" class="ep-tag-pill" :class="{ active: languages.includes(lang) }" @click="toggleArr(languages, lang)">{{ lang }}</button>
              </div>
              <div class="ep-add-row">
                <input class="ep-input" v-model="newLanguage" placeholder="Add other language..." @keyup.enter="addLanguage" />
                <button type="button" class="ep-add-btn" @click="addLanguage"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add</button>
              </div>
            </div>
          </div>
        </div>

        <!-- ── CERTIFICATIONS ── -->
        <div v-show="activeSection === 'certifications'">
          <!-- Professional Licenses -->
          <div class="ep-card">
            <div class="ep-card-head">
              <div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
              <div><div class="ep-card-title">Professional Licenses</div><div class="ep-card-sub">Bar admissions, professional licenses, and active credentials</div></div>
            </div>
            <div class="ep-card-body">
              <div class="ep-license-card">
                <div class="ep-license-head">
                  <div class="ep-license-name"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Primary License <span class="ep-license-badge">CA · Active</span></div>
                  <button type="button" class="ep-add-btn"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg> Archive</button>
                </div>
                <div class="ep-form-row">
                  <div class="ep-field"><label class="ep-label">License Type / Name</label><input class="ep-input" value="PhD Clinical Psychology" /></div>
                  <div class="ep-field"><label class="ep-label">License Number</label><input class="ep-input" value="CA-Psy-22841" /></div>
                  <div class="ep-field"><label class="ep-label">Issuing State / Jurisdiction</label><input class="ep-input" placeholder="e.g. New York" /></div>
                  <div class="ep-field"><label class="ep-label">Expiration Date</label><input class="ep-input" type="date" /></div>
                </div>
              </div>
              <button type="button" class="ep-add-btn ep-add-block"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Another License</button>
            </div>
          </div>

          <!-- Certifications & Training -->
          <div class="ep-card">
            <div class="ep-card-head">
              <div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
              <div><div class="ep-card-title">Certifications &amp; Training</div><div class="ep-card-sub">Continuity-specific certifications and continuing education</div></div>
            </div>
            <div class="ep-card-body">
              <div class="ep-subhead-sm">Continuity Steward Certifications</div>
              <div class="ep-tag-pills">
                <button v-for="c in csCertOptions" :key="c" type="button" class="ep-tag-pill" :class="{ active: csCerts.includes(c) }" @click="toggleArr(csCerts, c)">{{ c }}</button>
              </div>
              <div class="ep-subhead-sm" style="margin-top:18px;">Professional Background</div>
              <div class="ep-tag-pills">
                <button v-for="b in profBgOptions" :key="b" type="button" class="ep-tag-pill" :class="{ active: profBg.includes(b) }" @click="toggleArr(profBg, b)">{{ b }}</button>
              </div>
              <div class="ep-subhead-sm" style="margin-top:18px;">Liability &amp; Insurance</div>
              <div class="ep-form-row">
                <div class="ep-field"><label class="ep-label">E&amp;O Insurance Provider</label><input class="ep-input" value="$2M" /></div>
                <div class="ep-field"><label class="ep-label">Coverage Amount</label><input class="ep-input" value="$2M Coverage" /></div>
              </div>
            </div>
          </div>

          <!-- Education & Training -->
          <div class="ep-card">
            <div class="ep-card-head">
              <div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></div>
              <div><div class="ep-card-title">Education &amp; Training</div><div class="ep-card-sub">Academic degrees, professional training, and continuing education</div></div>
            </div>
            <div class="ep-card-body">
              <div class="ep-form-row">
                <div class="ep-field"><label class="ep-label">Highest Degree Conferred</label><select class="form-select"><option>Doctoral Degree</option><option>Master's Degree</option><option>Bachelor's Degree</option></select></div>
                <div class="ep-field"><label class="ep-label">Degree Specifications</label><input class="ep-input" value="PhD Clinical Psychology" /></div>
                <div class="ep-field"><label class="ep-label">Institution</label><input class="ep-input" placeholder="School or program name" /></div>
                <div class="ep-field"><label class="ep-label">Duration</label><input class="ep-input" placeholder="e.g. 3 years" /></div>
              </div>
              <button type="button" class="ep-add-btn ep-add-block"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Education / Training</button>
            </div>
          </div>
        </div>

        <!-- ── AEGIS VERIFICATION ── -->
        <div v-show="activeSection === 'verification'" class="ep-card">
          <div class="ep-card-head">
            <div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
            <div><div class="ep-card-title">Aegis Verification</div><div class="ep-card-sub">Submit documents to confirm your identity with Aegis</div></div>
          </div>
          <div class="ep-card-body">
            <div class="ep-verified-card">
              <div class="ep-verified-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg></div>
              <div class="ep-verified-body">
                <div class="ep-verified-title">Aegis Verified</div>
                <div class="ep-verified-desc">Your identity, credentials, and professional standing have been reviewed and approved by the Aegis Trust &amp; Safety team. The badge displays on your profile and is visible to all practitioners.</div>
                <div class="ep-verified-meta">
                  <div><div class="ep-vm-label">Verified On</div><div class="ep-vm-val">Jan 15, 2026</div></div>
                  <div><div class="ep-vm-label">Valid Through</div><div class="ep-vm-val">Jan 15, 2027</div></div>
                  <div><div class="ep-vm-label">Verified By</div><div class="ep-vm-val">Aegis Trust &amp; Safety</div></div>
                </div>
                <div class="ep-verified-actions">
                  <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View Details</button>
                  <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg> Request Document Refresh</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── SERVICE AREAS ── -->
        <div v-show="activeSection === 'service-areas'" class="ep-card">
          <div class="ep-card-head">
            <div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></div>
            <div><div class="ep-card-title">Service Areas</div><div class="ep-card-sub">Geographic coverage and virtual service capabilities</div></div>
          </div>
          <div class="ep-card-body">
            <div class="ep-field">
              <label class="ep-label">Primary Service States <span class="ep-req">*</span></label>
              <div class="ep-states">
                <button v-for="st in stateOptions" :key="st" type="button" class="ep-state" :class="{ active: states.includes(st) }" @click="toggleArr(states, st)">{{ st }}</button>
              </div>
              <div class="ep-help">Select all states where you provide Continuity Steward services.</div>
            </div>
            <div class="ep-form-row">
              <div class="ep-field"><label class="ep-label">On-Site Response Time <span class="ep-req">*</span></label><select class="form-select"><option>Within 24 hours</option><option>Within 48 hours</option><option>Within 72 hours</option></select></div>
              <div class="ep-field"><label class="ep-label">Maximum Caseload</label><select class="form-select"><option>7–10 concurrent cases</option><option>3–6 concurrent cases</option><option>11–20 concurrent cases</option></select></div>
            </div>
            <div class="ep-field">
              <label class="ep-label">Critical Incident Response Availability</label>
              <div class="ep-radio-group">
                <button type="button" class="ep-radio" :class="{ active: incidentAvail === '247' }" @click="incidentAvail = '247'"><span class="ep-radio-dot"></span> 24/7 Critical Incident Response</button>
                <button type="button" class="ep-radio" :class="{ active: incidentAvail === 'business' }" @click="incidentAvail = 'business'"><span class="ep-radio-dot"></span> Business Hours Only</button>
              </div>
            </div>
            <div class="ep-field">
              <label class="ep-label">Virtual Services Available?</label>
              <div class="ep-radio-group">
                <button type="button" class="ep-radio" :class="{ active: virtualServices === 'yes' }" @click="virtualServices = 'yes'"><span class="ep-radio-dot"></span> Yes</button>
                <button type="button" class="ep-radio" :class="{ active: virtualServices === 'no' }" @click="virtualServices = 'no'"><span class="ep-radio-dot"></span> No</button>
              </div>
            </div>
          </div>
        </div>

        <!-- ── EMERGENCY CONTACT ── -->
        <div v-show="activeSection === 'emergency'" class="ep-card">
          <div class="ep-card-head">
            <div class="ep-card-head-ico warn"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
            <div><div class="ep-card-title">Emergency Contact</div><div class="ep-card-sub">Required for profile completion — only used in crisis situations</div></div>
            <div class="ep-req-label">Required for 100%</div>
          </div>
          <div class="ep-card-body">
            <div class="ep-warn-banner">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
              <span><strong>This information is private.</strong> It is only accessible to Aegis admins and is never shared with practitioners.</span>
            </div>
            <div class="ep-form-row">
              <div class="ep-field"><label class="ep-label">Contact Name *</label><input class="ep-input" placeholder="Full name" /></div>
              <div class="ep-field"><label class="ep-label">Relationship *</label><select class="form-select"><option>Select relationship</option><option>Spouse / Partner</option><option>Parent</option><option>Sibling</option><option>Friend</option><option>Colleague</option><option>Other</option></select></div>
              <div class="ep-field"><label class="ep-label">Phone Number *</label><input class="ep-input" placeholder="(000) 000-0000" /></div>
              <div class="ep-field"><label class="ep-label">Email Address <span class="ep-opt">(Optional)</span></label><input class="ep-input" placeholder="email@example.com" /></div>
            </div>
          </div>
        </div>

        <!-- ── CONTINUITY PLAN ── -->
        <div v-show="activeSection === 'continuity-plan'" class="ep-card">
          <div class="ep-card-head">
            <div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
            <div><div class="ep-card-title">Continuity Plan Framework</div><div class="ep-card-sub">Define your standard operating procedures for critical incidents</div></div>
          </div>
          <div class="ep-card-body">
            <div class="ep-note">Providers are responsible for approving all continuity tasks. These responsibilities are defined in each Provider's Continuity Plan and will show on dashboards once the plan is finalized. The list below should match the responsibilities given to you in your assigned Provider's Plan.</div>

            <div class="ep-cp-divider">I. Active Practice Continuity Steward Services</div>
            <div v-for="cat in cpActive" :key="cat.n" class="ep-cp-cat">
              <div class="ep-cp-cat-head"><span class="ep-cp-num">{{ cat.n }}</span> {{ cat.title }}</div>
              <div class="ep-cp-items">
                <label v-for="(task, i) in cat.tasks" :key="i" class="ep-cp-item">
                  <input type="checkbox" checked />
                  <span class="ep-cp-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                  <span class="ep-cp-task">{{ task }}</span>
                </label>
                <button type="button" class="ep-add-btn"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add item</button>
              </div>
            </div>

            <div class="ep-cp-divider">II. Retirement Continuity Steward</div>
            <div v-for="cat in cpRetirement" :key="cat.n" class="ep-cp-cat">
              <div class="ep-cp-cat-head"><span class="ep-cp-num">{{ cat.n }}</span> {{ cat.title }}</div>
              <div class="ep-cp-items">
                <label v-for="(task, i) in cat.tasks" :key="i" class="ep-cp-item">
                  <input type="checkbox" checked />
                  <span class="ep-cp-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                  <span class="ep-cp-task">{{ task }}</span>
                </label>
                <button type="button" class="ep-add-btn"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add item</button>
              </div>
            </div>
          </div>
        </div>

        <!-- ── STEWARD SETTINGS ── -->
        <div v-show="activeSection === 'steward-settings'" class="ep-card">
          <div class="ep-card-head">
            <div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
            <div><div class="ep-card-title">Account &amp; Tier</div><div class="ep-card-sub">Your Continuity Steward account type and acceptance settings</div></div>
          </div>
          <div class="ep-card-body">
            <div class="ep-info-table">
              <div class="ep-info-row"><span class="ep-info-label">Account Type</span><span class="ep-info-val">Invited <span class="ep-tag-badge">Invited</span></span></div>
              <div class="ep-info-row"><span class="ep-info-label">Aegis Verified</span><span class="ep-info-val ok">Verified</span></div>
              <div class="ep-info-row"><span class="ep-info-label">Practitioners Served</span><span class="ep-info-val">11</span></div>
              <div class="ep-info-row"><span class="ep-info-label">Background Check</span><span class="ep-info-val">Passed (Nov 2024)</span></div>
            </div>
            <div class="ep-form-row">
              <div class="ep-field"><label class="ep-label">Account Type</label><select class="form-select"><option>Invited (Free)</option><option>Business CS</option></select></div>
              <div class="ep-field"><label class="ep-label">Accepting New Practitioners?</label><select class="form-select"><option>Yes — Open to New Assignments</option><option>No — At Capacity</option></select></div>
              <div class="ep-field"><label class="ep-label">Caseload Limit</label><select class="form-select"><option>20 practitioners</option><option>10 practitioners</option><option>40 practitioners</option></select></div>
              <div class="ep-field"><label class="ep-label">Response Time SLA</label><select class="form-select"><option>Within 4 business hours</option><option>Within 8 business hours</option><option>Within 24 hours</option></select></div>
            </div>
            <div class="ep-toggle-row">
              <div class="ep-toggle-info"><strong>Assignment Notifications</strong><span>New assignments, removals, and status changes</span></div>
              <button type="button" class="toggle" :class="{ on: settings.assignmentNotifs }" @click="settings.assignmentNotifs = !settings.assignmentNotifs" :aria-pressed="settings.assignmentNotifs"></button>
            </div>
            <div class="ep-toggle-row">
              <div class="ep-toggle-info"><strong>Plan Review Reminders</strong><span>Renewal due dates and signature requests</span></div>
              <button type="button" class="toggle" :class="{ on: settings.planReminders }" @click="settings.planReminders = !settings.planReminders" :aria-pressed="settings.planReminders"></button>
            </div>
          </div>
        </div>

        <!-- ── FEE STRUCTURE ── -->
        <div v-show="activeSection === 'fee-structure'" class="ep-card">
          <div class="ep-card-head">
            <div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
            <div><div class="ep-card-title">Fee Structure</div><div class="ep-card-sub">Retainer bands and hourly rates shared with practitioners reviewing your profile</div></div>
          </div>
          <div class="ep-card-body">
            <div class="ep-form-row">
              <div class="ep-field"><label class="ep-label">Annual Retainer (low end)</label><div class="ep-input-prefix"><span>$</span><input class="ep-input" value="2,800–5,200" /></div></div>
              <div class="ep-field"><label class="ep-label">Annual Retainer (high end)</label><div class="ep-input-prefix"><span>$</span><input class="ep-input" value="6000" /></div></div>
              <div class="ep-field"><label class="ep-label">Hourly Rate</label><div class="ep-input-prefix"><span>$</span><input class="ep-input" value="145" /></div></div>
              <div class="ep-field"><label class="ep-label">Activation Fee (critical incident)</label><div class="ep-input-prefix"><span>$</span><input class="ep-input" value="2500" /></div></div>
            </div>
            <div class="ep-hr"></div>
            <div class="ep-field"><label class="ep-label">Payment Terms</label><input class="ep-input" value="Net 15 · ACH or Wire" /></div>
          </div>
        </div>

        <!-- ── AVAILABILITY ── -->
        <div v-show="activeSection === 'availability'" class="ep-card">
          <div class="ep-card-head">
            <div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div><div class="ep-card-title">Availability Windows</div><div class="ep-card-sub">Days and hours you can be reached for urgent stewardship matters</div></div>
          </div>
          <div class="ep-card-body">
            <div class="ep-field">
              <label class="ep-label">Available Days</label>
              <div class="ep-days">
                <button v-for="d in days" :key="d" type="button" class="ep-day" :class="{ active: availableDays.includes(d) }" @click="toggleArr(availableDays, d)">{{ d }}</button>
              </div>
            </div>
            <div class="ep-form-row">
              <div class="ep-field"><label class="ep-label">Response Time SLA</label><select class="form-select"><option>Within 4 business hours</option><option>Within 8 business hours</option><option>Within 24 hours</option></select></div>
              <div class="ep-field"><label class="ep-label">Timezone</label><select class="form-select"><option>Pacific Time (PST)</option><option>Mountain Time (MST)</option><option>Central Time (CST)</option><option>Eastern Time (EST)</option></select></div>
            </div>
            <div class="ep-toggle-row">
              <div class="ep-toggle-info"><strong>After-Hours Alerts</strong><span>Available for critical incident alerts outside business hours</span></div>
              <button type="button" class="toggle" :class="{ on: availability.afterHours }" @click="availability.afterHours = !availability.afterHours" :aria-pressed="availability.afterHours"></button>
            </div>
            <div class="ep-toggle-row">
              <div class="ep-toggle-info"><strong>Weekend Emergency Response</strong><span>Available for emergency response on weekends</span></div>
              <button type="button" class="toggle" :class="{ on: availability.weekend }" @click="availability.weekend = !availability.weekend" :aria-pressed="availability.weekend"></button>
            </div>
          </div>
        </div>

        <!-- ── PREFERENCES ── -->
        <div v-show="activeSection === 'preferences'" class="ep-card">
          <div class="ep-card-head">
            <div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></div>
            <div><div class="ep-card-title">Preferences</div><div class="ep-card-sub">Notifications, language, and portal display preferences</div></div>
          </div>
          <div class="ep-card-body">
            <div class="ep-subhead">Notification Settings</div>
            <div class="ep-toggle-row" v-for="pref in prefRows" :key="pref.key">
              <div class="ep-toggle-info"><strong>{{ pref.label }}</strong><span>{{ pref.desc }}</span></div>
              <button type="button" class="toggle" :class="{ on: prefs[pref.key] }" @click="prefs[pref.key] = !prefs[pref.key]" :aria-pressed="prefs[pref.key]"></button>
            </div>
            <div class="ep-form-row" style="margin-top:20px;">
              <div class="ep-field"><label class="ep-label">Language</label><select class="form-select"><option>English (US)</option><option>English (UK)</option><option>Español</option><option>Français</option></select></div>
              <div class="ep-field"><label class="ep-label">Date Format</label><select class="form-select"><option>MM/DD/YYYY</option><option>DD/MM/YYYY</option><option>YYYY-MM-DD</option></select></div>
            </div>
            <div class="ep-info-banner">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
              <span>Password, two-factor authentication, and account deactivation are managed in <a href="/continuity-steward/settings">Account Settings</a>.</span>
            </div>
          </div>
        </div>

        <!-- ── IDENTITY ── -->
        <div v-show="activeSection === 'identity'">
          <div class="ep-info-banner" style="margin-top:0;margin-bottom:18px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span><strong>Unified across all portals</strong> — Your display name, email, and phone apply to every portal you have access to. Changes here update your identity platform-wide.</span>
          </div>

          <!-- Profile Photo -->
          <div class="ep-card">
            <div class="ep-card-head">
              <div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div>
              <div><div class="ep-card-title">Profile Photo</div><div class="ep-card-sub">Shown to the practitioner who designated you</div></div>
            </div>
            <div class="ep-card-body">
              <div class="ep-photo-row">
                <div class="ep-photo-av">PR</div>
                <div class="ep-photo-info">
                  <div class="ep-photo-name">Dr. Priya Raman, <span>PsyD</span></div>
                  <div class="ep-photo-note">No photo uploaded — initials shown as placeholder</div>
                  <div class="ep-photo-actions">
                    <button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Upload Photo</button>
                    <button type="button" class="btn btn-outline btn-sm">Remove</button>
                  </div>
                </div>
              </div>
              <div class="ep-field">
                <label class="ep-label">Display Initials</label>
                <input class="ep-input" value="PR" maxlength="2" />
                <div class="ep-help">Max 2 characters — used in your avatar circle</div>
              </div>
            </div>
          </div>

          <!-- Basic Information -->
          <div class="ep-card">
            <div class="ep-card-head">
              <div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
              <div><div class="ep-card-title">Basic Information</div><div class="ep-card-sub">Appears on your Aegis Steward profile</div></div>
            </div>
            <div class="ep-card-body">
              <div class="ep-form-row">
                <div class="ep-field"><label class="ep-label">First Name *</label><input class="ep-input" value="Dr." /></div>
                <div class="ep-field"><label class="ep-label">Last Name *</label><input class="ep-input" value="Priya Raman" /></div>
              </div>
              <div class="ep-field"><label class="ep-label">Credentials / Suffix <span class="ep-opt">(Optional)</span></label><input class="ep-input" value="PsyD" /></div>
              <div class="ep-field"><label class="ep-label">Professional Title *</label><input class="ep-input" value="Reviewed Continuity Plan — Dr. Sarah Johnson" /></div>
              <div class="ep-field"><label class="ep-label">Organization / Firm</label><input class="ep-input" value="Continuity Partners" /></div>
              <div class="ep-field">
                <label class="ep-label">Professional Bio *</label>
                <textarea class="ep-input ep-textarea" rows="3" maxlength="1000" v-model="bio"></textarea>
                <div class="ep-textarea-foot"><span class="ep-count">{{ bio.length }} / 1000</span></div>
                <div class="ep-help">2–4 sentences. Focus on your stewardship philosophy and practitioner experience.</div>
              </div>
            </div>
          </div>

          <!-- Contact & Location -->
          <div class="ep-card">
            <div class="ep-card-head">
              <div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
              <div><div class="ep-card-title">Contact &amp; Location</div><div class="ep-card-sub">Visible to assigned practitioners and Aegis staff</div></div>
            </div>
            <div class="ep-card-body">
              <div class="ep-form-row">
                <div class="ep-field"><label class="ep-label">City</label><input class="ep-input" value="San Francisco" /></div>
                <div class="ep-field"><label class="ep-label">State</label><input class="ep-input" value="CA" /></div>
                <div class="ep-field"><label class="ep-label">Phone *</label><input class="ep-input" value="(415) 555-0167" /></div>
                <div class="ep-field"><label class="ep-label">Fax <span class="ep-opt">(Optional)</span></label><input class="ep-input" placeholder="(XXX) XXX-XXXX" /></div>
              </div>
              <div class="ep-field"><label class="ep-label">Email *</label><input class="ep-input" value="priya.raman@continuitypartners.net" /></div>
              <div class="ep-field"><label class="ep-label">Website <span class="ep-opt">(Optional)</span></label><div class="ep-input-prefix"><span>https://</span><input class="ep-input" placeholder="yoursite.com" /></div></div>
              <div class="ep-field">
                <label class="ep-label">Languages Spoken</label>
                <div class="ep-tag-pills">
                  <button v-for="lang in languageOptions" :key="lang" type="button" class="ep-tag-pill" :class="{ active: languages.includes(lang) }" @click="toggleArr(languages, lang)">{{ lang }}</button>
                </div>
                <div class="ep-add-row">
                  <input class="ep-input" v-model="newLanguage" placeholder="Add other language..." @keyup.enter="addLanguage" />
                  <button type="button" class="ep-add-btn" @click="addLanguage"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── ABOUT & BIO ── -->
        <div v-show="activeSection === 'about'" class="ep-card">
          <div class="ep-card-head">
            <div class="ep-card-head-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg></div>
            <div><div class="ep-card-title">About &amp; Bio</div><div class="ep-card-sub">Your professional story — shown to practitioners, support stewards, and administrators on Aegis</div></div>
          </div>
          <div class="ep-card-body">
            <div class="ep-field">
              <label class="ep-label">Professional Bio *</label>
              <textarea class="ep-input ep-textarea ep-textarea-lg" maxlength="1200" v-model="bio"></textarea>
              <div class="ep-textarea-foot"><span class="ep-help" style="margin-top:0;">Highlight your experience with practice continuity and Critical Incident response.</span><span class="ep-count">{{ bio.length }} / 1200</span></div>
            </div>
          </div>
        </div>

      </section>
    </div>

    <!-- ═══ STICKY SAVE BAR ═══ -->
    <div class="ep-savebar">
      <div class="ep-savebar-status"><span class="ep-savebar-dot"></span> <strong>Unsaved changes</strong> · Changes save to your Aegis Steward profile</div>
      <button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Save &amp; Publish</button>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
defineProps({ user: Object });

const profilePct = 78;
const activeSection = ref('identity');
const bio = ref('Clinical psychologist and founder of Continuity Partners. Specialized in compassionate patient handoffs during practice transitions.');

function toggleArr(arr, val) {
  const list = Array.isArray(arr) ? arr : arr.value;
  const i = list.indexOf(val);
  if (i === -1) list.push(val); else list.splice(i, 1);
}

const sIcon = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
const navGroups = [
  {
    label: 'Profile',
    items: [
      { key: 'identity', label: 'Identity', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>` },
      { key: 'about', label: 'About & Bio', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>` },
      { key: 'contact', label: 'Contact Information', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>` },
      { key: 'certifications', label: 'Certifications', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>` },
      { key: 'verification', label: 'Aegis Verification', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
      { key: 'emergency', label: 'Emergency Contact', status: 'todo', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>` },
    ],
  },
  {
    label: 'Settings',
    items: [
      { key: 'service-areas', label: 'Service Areas', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>` },
      { key: 'continuity-plan', label: 'Continuity Plan', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>` },
      { key: 'steward-settings', label: 'Steward Settings', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>` },
      { key: 'fee-structure', label: 'Fee Structure', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>` },
      { key: 'availability', label: 'Availability', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>` },
      { key: 'preferences', label: 'Preferences', status: 'done', icon: `<svg viewBox="0 0 24 24" ${sIcon}><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>` },
    ],
  },
];


// ── Contact Information ──
const languageOptions = ['English', 'Spanish', 'Mandarin Chinese', 'French', 'Arabic', 'Hindi', 'Portuguese', 'Russian', 'Korean', 'Japanese', 'Vietnamese', 'Tagalog', 'Italian', 'German'];
const languages = reactive(['English', 'Hindi']);
const newLanguage = ref('');
function addLanguage() {
  const v = newLanguage.value.trim();
  if (v && !languages.includes(v)) languages.push(v);
  newLanguage.value = '';
}

// ── Certifications ──
const csCertOptions = ['MAAT Certified Continuity Steward', 'Aegis Platform Certified', 'CPF — Certified Professional Fiduciary', 'HIPAA Compliance Certified', 'Healthcare Power of Attorney Specialist'];
const csCerts = reactive(['MAAT Certified Continuity Steward']);
const profBgOptions = ['Estate Planning Attorney', 'Licensed Clinical Social Worker', 'Healthcare Administrator', 'CPA / Financial Advisor', 'Risk Management Specialist', 'Notary Public'];
const profBg = reactive([]);

// ── Service Areas ──
const stateOptions = ['AL','AK','AZ','AR','CA','CO','CT','DE','DC','FL','GA','HI','ID','IL','IN','IA','KS','KY','LA','ME','MD','MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VT','VA','WA','WV','WI','WY','PR','VI','GU','AS','MP'];
const states = reactive([]);
const incidentAvail = ref('247');
const virtualServices = ref('yes');

// ── Preferences ──
const prefRows = [
  { key: 'critical', label: 'Critical Incident Alerts', desc: 'Immediate notifications for active Critical Incidents' },
  { key: 'assignment', label: 'Assignment Updates', desc: 'New assignments, removals, and status changes' },
  { key: 'agreement', label: 'Agreement Reminders', desc: 'Renewal due dates and signature requests' },
  { key: 'system', label: 'System Announcements', desc: 'Platform updates and maintenance notices' },
  { key: 'email', label: 'Email', desc: 'Receive notifications by email' },
  { key: 'sms', label: 'SMS / Text', desc: 'Receive notifications via text message' },
  { key: 'inapp', label: 'In-App', desc: 'Receive notifications inside Aegis' },
];
const prefs = reactive({ critical: false, assignment: true, agreement: true, system: true, email: true, sms: false, inapp: true });

// ── Availability ──
const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const availableDays = reactive(['Mon', 'Tue', 'Wed', 'Thu', 'Fri']);
const availability = reactive({ afterHours: true, weekend: true });

// ── Steward Settings ──
const settings = reactive({ assignmentNotifs: true, planReminders: true });

// ── Continuity Plan ──
const cpActive = [
  { n: 1, title: 'Immediate Practice Stabilization (Temporary or Sudden Incapacity)', tasks: [
    'Email notification to clients', 'Phone notification to clients', 'Coordinate rescheduling of appointments',
    'Freeze scheduling of new appointments', 'Provide referrals to active clients', 'Email notification to supervisees',
    'Phone notification to supervisees', 'Share Board Supervision Record with Supervisee', 'Supervisee referral to an alternative supervisor',
    'Add automatic email response (away message)', 'Update phone message', 'Redirect phone messages',
    'Post public-facing notice', 'Physical note on the office door', 'Notify Critical Incident contacts',
    'Cover cost of business expenses for the designated period',
  ] },
  { n: 2, title: 'Regulatory Compliance (Non-Substance)', tasks: [
    'Notification of Licensure Board', 'Notification of Oversight Body of Business License',
    'Notification of Professional Liability insurance', 'Notification of Other Oversight Clinical Bodies (Close NPI, Notify CAQH, Notify Insurance Panels)',
  ] },
  { n: 3, title: 'Controlled Substance Compliance', tasks: [
    'Notification of DEA (if prescribing provider)', 'Controlled substance inventory', 'Secure medication samples', 'Secure prescription pads',
  ] },
  { n: 4, title: 'Medical Record Management (Active Practice Closure or Transition)', tasks: [
    'Secure medical records of current clients', 'Secure medical records of past clients', 'Transfer medical records to new providers',
    'Provide the client with notice of record transfer', 'Retain medical records for the legal duration',
    'Record destruction in accordance with professional standards and legal timeline',
  ] },
  { n: 5, title: 'Financial & Administrative Management', tasks: [
    'Finalize practice billing', 'Secure tax documentation',
    'Close Merchant Service Accounts (EHR, Zoom, cloud storage, domain cancellation/transfer, directory removal)',
    'Remove staff access to information',
  ] },
  { n: 6, title: 'Business & Public Presence Management', tasks: [
    'Update social media profiles', 'Delete social media profiles', 'Update webpage', 'Delete webpage',
    'Notification of contracted business entities (training clients, consulting clients, business partners)',
    'Notification to the landlord related to the office lease',
  ] },
  { n: 7, title: 'Full Practice Closure (Permanent Incapacity or Death)', tasks: [
    'Close practice — umbrella category activating the appropriate services above',
  ] },
];
const cpRetirement = [
  { n: 8, title: 'Retirement — Record Storage & Post-Practice Management', tasks: [
    'Record storage', 'Assumes responsibility for the records upon death or incapacity after retirement',
    'Record storage in accordance with professional standards and legal timeline',
    'Response to record request and information',
    'Record destruction in accordance with professional standards and legal timeline',
  ] },
];
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════
   CS EDIT PROFILE
   ════════════════════════════════════════════════════════════════ */

/* HEADER CARD */
.ep-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:26px 30px; margin-bottom:16px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.ep-title { font-family:var(--font-serif); font-size:34px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.ep-subtitle { font-size:13.5px; color:var(--text-3); margin-top:8px; }
.ep-meta { display:flex; align-items:center; gap:18px; margin-top:12px; }
.ep-meta span { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; color:var(--text-4); }
.ep-meta span:first-child svg { color:var(--green-dark); }
.ep-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; }
.ep-btn-muted { color:var(--text-4); }

/* FINISH PROFILE BANNER */
.cs-profile-banner { margin-bottom:22px; background:linear-gradient(95deg,var(--badge-bg-gold) 0%,var(--surface) 80%); border:1px solid rgba(192,154,82,.28); border-radius:var(--radius-lg); padding:14px 20px; display:flex; align-items:center; gap:16px; box-shadow:var(--shadow-sm); }
.cs-profile-icon { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cs-profile-body { flex:1; min-width:0; }
.cs-profile-title { font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px; }
.cs-profile-sub { font-size:12px; color:var(--text-3); line-height:1.5; }
.cs-profile-progress { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.cs-profile-bar { width:120px; height:5px; background:var(--surface-3); border-radius:var(--radius-full); overflow:hidden; }
.cs-profile-bar-fill { height:100%; background:linear-gradient(90deg,var(--gold),var(--gold-dark)); border-radius:var(--radius-full); }
.cs-profile-pct { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.cs-profile-cta { flex-shrink:0; white-space:nowrap; }

/* LAYOUT */
.ep-layout { display:grid; grid-template-columns:240px 1fr; gap:20px; align-items:start; padding-bottom:80px; }

/* NAV */
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

/* PANEL + CARDS */
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

/* form fields */
.ep-field { margin-bottom:18px; }
.ep-field:last-child { margin-bottom:0; }
.ep-form-row { display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:4px; }
.ep-form-row.ep-row-3 { grid-template-columns:1fr 1fr 1fr; }
.ep-label { display:block; font-size:12px; font-weight:600; color:var(--text-2); margin-bottom:7px; }
.ep-opt { font-weight:400; color:var(--text-4); text-transform:none; letter-spacing:0; }
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

/* toggle rows */
.ep-toggle-row { display:flex; align-items:center; justify-content:space-between; gap:20px; padding:14px 0; border-bottom:1px solid var(--border); }
.ep-toggle-row:last-child { border-bottom:none; }
.ep-toggle-info strong { display:block; font-size:13px; font-weight:600; color:var(--text); margin-bottom:2px; }
.ep-toggle-info span { font-size:12px; color:var(--text-3); }

/* button.toggle visual */
.toggle { position:relative; width:42px; height:24px; flex-shrink:0; border:none; padding:0; border-radius:var(--radius-full); background:var(--border-dark); cursor:pointer; transition:background var(--transition); }
.toggle::after { content:''; position:absolute; width:18px; height:18px; top:50%; left:3px; background:var(--surface); border-radius:var(--radius-full); transform:translateY(-50%); transition:transform var(--transition); box-shadow:0 1px 4px rgba(44,34,24,0.22); }
.toggle.on { background:var(--gold-dark); }
.toggle.on::after { transform:translate(18px,-50%); }

/* info table */
.ep-info-table { border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; margin-bottom:22px; background:var(--surface-2); }
.ep-info-row { display:flex; align-items:center; gap:16px; padding:12px 16px; border-bottom:1px solid var(--border); }
.ep-info-row:last-child { border-bottom:none; }
.ep-info-label { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); width:180px; flex-shrink:0; }
.ep-info-val { font-size:13px; font-weight:600; color:var(--text); display:inline-flex; align-items:center; gap:8px; }
.ep-info-val.ok { color:var(--green-dark); }
.ep-tag-badge { font-size:9px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; padding:2px 8px; border-radius:var(--radius-full); background:var(--badge-bg-gold); color:var(--gold-dark); }

/* day pills */
.ep-days { display:flex; gap:8px; flex-wrap:wrap; }
.ep-day { width:54px; padding:12px 0; font-size:12px; font-weight:600; text-align:center; border:1px solid var(--border-dark); border-radius:var(--radius); background:var(--surface); color:var(--text-4); cursor:pointer; transition:all .15s ease; }
.ep-day:hover { border-color:var(--gold-dark); }
.ep-day.active { background:var(--gold-dark); border-color:var(--gold-dark); color:var(--text-inverted); }

/* state pills */
.ep-states { display:grid; grid-template-columns:repeat(auto-fit, minmax(54px, 1fr)); gap:8px; }
.ep-state { padding:11px 0; font-size:12px; font-weight:600; text-align:center; border:1px solid var(--border-dark); border-radius:var(--radius); background:var(--surface); color:var(--text-2); cursor:pointer; transition:all .15s ease; }
.ep-state:hover { border-color:var(--gold-dark); }
.ep-state.active { background:var(--gold-dark); border-color:var(--gold-dark); color:var(--text-inverted); }

/* radio pills */
.ep-radio-group { display:flex; gap:10px; flex-wrap:wrap; }
.ep-radio { display:inline-flex; align-items:center; gap:9px; padding:11px 16px; font-size:13px; font-weight:500; color:var(--text-2); border:1px solid var(--border-dark); border-radius:var(--radius); background:var(--surface); cursor:pointer; transition:all .15s ease; }
.ep-radio:hover { border-color:var(--gold-dark); }
.ep-radio.active { border-color:var(--gold-dark); background:var(--badge-bg-gold); color:var(--gold-dark); font-weight:600; }
.ep-radio-dot { width:15px; height:15px; border-radius:50%; border:1px solid var(--border-dark); flex-shrink:0; position:relative; transition:border-color .15s ease; }
.ep-radio.active .ep-radio-dot { border-color:var(--gold-dark); }
.ep-radio.active .ep-radio-dot::after { content:''; position:absolute; inset:3px; border-radius:50%; background:var(--gold-dark); }

/* selectable tag pills */
.ep-tag-pills { display:flex; gap:8px; flex-wrap:wrap; }
.ep-tag-pill { padding:8px 14px; font-size:12.5px; font-weight:500; color:var(--text-2); border:1px solid var(--border-dark); border-radius:var(--radius-full); background:var(--surface); cursor:pointer; transition:all .15s ease; }
.ep-tag-pill:hover { border-color:var(--gold-dark); }
.ep-tag-pill.active { background:var(--gold-dark); border-color:var(--gold-dark); color:var(--text-inverted); font-weight:600; }

/* add row / button */
.ep-add-row { display:flex; gap:10px; margin-top:12px; }
.ep-add-row .ep-input { flex:1; }
.ep-add-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 14px; font-size:12px; font-weight:600; color:var(--text-3); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.ep-add-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.ep-add-block { margin-top:14px; }

/* notes / banners */
.ep-note { font-size:12.5px; color:var(--text-2); line-height:1.6; background:rgba(80,120,190,.06); border:1px solid rgba(80,120,190,.18); border-radius:var(--radius); padding:13px 16px; margin-bottom:22px; }
.ep-info-banner { display:flex; align-items:flex-start; gap:10px; font-size:12.5px; color:var(--text-2); line-height:1.55; background:rgba(80,120,190,.06); border:1px solid rgba(80,120,190,.18); border-radius:var(--radius); padding:13px 16px; margin-top:22px; }
.ep-info-banner svg { color:#5078be; flex-shrink:0; margin-top:1px; }
.ep-info-banner a { color:var(--gold-dark); font-weight:600; }
.ep-warn-banner { display:flex; align-items:flex-start; gap:10px; font-size:12.5px; color:var(--text-2); line-height:1.55; background:rgba(181,99,10,.07); border:1px solid rgba(181,99,10,.22); border-radius:var(--radius); padding:13px 16px; margin-bottom:22px; }
.ep-warn-banner svg { color:var(--orange-dark); flex-shrink:0; margin-top:1px; }
.ep-warn-banner strong { color:var(--orange-dark); }

/* verified card */
.ep-verified-card { display:flex; gap:16px; padding:22px; border:1px solid rgba(46,138,87,.3); background:linear-gradient(135deg, rgba(46,138,87,.07), rgba(46,138,87,.02)); border-radius:var(--radius-lg); }
.ep-verified-ico { width:42px; height:42px; border-radius:var(--radius-sm); background:var(--green-light); color:var(--green-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ep-verified-body { flex:1; min-width:0; }
.ep-verified-title { font-family:var(--font-serif); font-size:17px; font-weight:700; color:var(--text); margin-bottom:6px; }
.ep-verified-desc { font-size:12.5px; color:var(--text-2); line-height:1.6; }
.ep-verified-meta { display:flex; gap:40px; flex-wrap:wrap; margin:18px 0; }
.ep-vm-label { font-size:9.5px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); margin-bottom:4px; }
.ep-vm-val { font-size:13px; font-weight:600; color:var(--text); }
.ep-verified-actions { display:flex; gap:8px; flex-wrap:wrap; }

/* license card */
.ep-license-card { border:1px solid rgba(192,154,82,.30); background:var(--badge-bg-gold); border-radius:var(--radius-lg); padding:18px 20px; }
.ep-license-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; }
.ep-license-name { display:inline-flex; align-items:center; gap:8px; font-size:13.5px; font-weight:700; color:var(--text); }
.ep-license-name svg { color:var(--gold-dark); }
.ep-license-badge { font-size:10px; font-weight:700; padding:2px 9px; border-radius:var(--radius-full); background:var(--green-light); color:var(--green-dark); }

/* continuity plan */
.ep-cp-divider { font-family:var(--font-serif); font-size:14px; font-weight:700; color:var(--gold-dark); border-left:3px solid var(--gold-dark); padding:6px 0 6px 12px; margin:8px 0 14px; }
.ep-cp-cat { border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; margin-bottom:12px; }
.ep-cp-cat-head { display:flex; align-items:center; gap:10px; padding:12px 16px; background:var(--surface-2); border-bottom:1px solid var(--border); font-size:13.5px; font-weight:700; color:var(--text); }
.ep-cp-num { width:22px; height:22px; border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ep-cp-items { padding:8px 16px 12px; }
.ep-cp-item { display:flex; align-items:center; gap:10px; padding:6px 0; cursor:pointer; }
.ep-cp-item input { position:absolute; opacity:0; width:0; height:0; }
.ep-cp-check { width:16px; height:16px; border-radius:4px; border:1px solid var(--border-dark); display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; color:transparent; background:var(--surface); transition:all .12s ease; }
.ep-cp-item input:checked + .ep-cp-check { background:var(--gold-dark); border-color:var(--gold-dark); color:var(--text-inverted); }
.ep-cp-task { font-size:12.5px; color:var(--text-2); line-height:1.4; }

/* textarea + char count */
.ep-textarea { resize:vertical; min-height:96px; line-height:1.55; font-family:var(--font-sans); }
.ep-textarea-lg { min-height:200px; }
.ep-textarea-foot { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:8px; }
.ep-count { font-size:11px; color:var(--text-4); margin-left:auto; flex-shrink:0; }

/* profile photo */
.ep-photo-row { display:flex; align-items:center; gap:18px; padding:16px 18px; border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--surface-2); margin-bottom:18px; }
.ep-photo-av { width:62px; height:62px; border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-size:22px; font-weight:700; flex-shrink:0; }
.ep-photo-info { flex:1; min-width:0; }
.ep-photo-name { font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); }
.ep-photo-name span { font-size:13px; color:var(--text-3); font-weight:400; }
.ep-photo-note { font-size:12px; color:var(--text-4); margin:3px 0 10px; }
.ep-photo-actions { display:flex; gap:8px; flex-wrap:wrap; }

/* sticky save bar */
.ep-savebar { position:sticky; bottom:0; z-index:20; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 24px; background:var(--surface); border-top:1px solid var(--border); box-shadow:0 -4px 16px rgba(44,34,24,.06); margin:0 -4px; }
.ep-savebar-status { font-size:12.5px; color:var(--text-3); display:inline-flex; align-items:center; gap:8px; }
.ep-savebar-status strong { color:var(--orange-dark); font-weight:700; }
.ep-savebar-dot { width:7px; height:7px; border-radius:50%; background:var(--orange-dark); flex-shrink:0; }

@media (max-width:900px) {
  .ep-layout { grid-template-columns:1fr; }
  .ep-nav { position:static; }
  .ep-form-row, .ep-form-row.ep-row-3 { grid-template-columns:1fr; }
  .ep-header { flex-direction:column; }
}
</style>
