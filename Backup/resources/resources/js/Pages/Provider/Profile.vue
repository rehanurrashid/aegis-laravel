<template>
  <AppLayout :user="user" portal="practitioner" activePage="profile" pageTitle="Edit Profile">
    <!-- PROFILE EDIT BANNER -->
    <div class="ppc-card">
      <div class="ppc-main">
        <div class="ppc-identity">
          <div class="ppc-eyebrow">PRACTITIONER PROFILE</div>
          <div class="ppc-name">Edit Profile</div>
          <div class="ppc-meta-line">
            {{ f.firstName || 'First' }} {{ f.lastName || 'Last' }}<span v-if="f.credentials">, {{ f.credentials }}</span><span v-if="f.practice" class="ppc-dot"> · </span><span v-if="f.practice">{{ f.practice }}</span>
          </div>
          <div class="ppc-saved-row">
            <span class="ppc-saved-item">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
              Saved 2h ago
            </span>
            <span class="ppc-saved-item">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Last reviewed Apr 28
            </span>
          </div>
        </div>

        <div class="ppc-actions">
          <button class="btn btn-outline btn-sm ppc-action-btn" @click="showToast('Opening activity log','info')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Activity
          </button>
          <button class="btn btn-outline btn-sm ppc-action-btn" @click="showToast('Opening public profile preview','info')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            Preview
          </button>
          <button class="btn btn-primary btn-sm ppc-action-btn ppc-save-btn" @click="saveChanges">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Changes
          </button>
        </div>
      </div>
    </div>

    <!-- PROFILE COMPLETION BANNER -->
    <div class="ep-completion-banner">
      <div class="ep-completion-left">
        <div class="ep-completion-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
        </div>
        <div>
          <div class="ep-completion-title">Finish your profile</div>
          <div class="ep-completion-sub">
            <template v-if="profileCompletion.remaining === 0">Your profile is complete!</template>
            <template v-else>{{ profileCompletion.remaining }} item{{ profileCompletion.remaining === 1 ? '' : 's' }} remaining — {{ profileCompletion.hint }}</template>
          </div>
        </div>
      </div>
      <div class="ep-completion-right">
        <div class="ep-completion-bar-wrap">
          <div class="ep-completion-bar-track">
            <div class="ep-completion-bar-fill" :style="{ width: profileCompletion.pct + '%' }"></div>
          </div>
          <span class="ep-completion-pct">{{ profileCompletion.pct }}%</span>
        </div>
        <button class="ep-completion-btn" @click="activeSection = 'basic-info'">
          Complete <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </button>
      </div>
    </div>

    <div class="ep-layout">
      <!-- LEFT NAV -->
      <aside class="ep-nav">
        <div class="ep-nav-section-label">Profile</div>
        <a v-for="s in sections" :key="s.id" class="ep-nav-item" :class="{ active: activeSection === s.id }" href="#" @click.prevent="activeSection = s.id">
          <span class="ep-nav-icon" v-html="s.icon"></span>
          <span class="ep-nav-label">{{ s.label }}</span>
        </a>
      </aside>

      <!-- RIGHT CONTENT -->
      <div class="ep-content">

        <!-- ══ BASIC INFO ══ -->
        <div v-show="activeSection === 'basic-info'" class="ep-pane">

          <!-- Unified portals notice -->
          <div class="ep-unified-notice">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span><strong>Unified across all portals</strong> — Your photo, display name, email, and phone apply to every portal you have access to (Practitioner, Continuity Steward, Support Steward). Changes here update your identity platform-wide.</span>
          </div>

          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg></div>
              <div><div class="ep-card-title">Profile Photo</div><div class="ep-card-sub">Shown to network partners and on your public profile</div></div>
            </div>
            <div class="ep-card-body">
              <div class="ep-photo-row">
                <div class="ep-avatar">
                  <img v-if="photoUrl" :src="photoUrl" alt="Profile photo" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
                  <template v-else>{{ initials }}</template>
                </div>
                <div class="ep-photo-actions">
                  <input ref="photoInput" type="file" accept="image/jpeg,image/png" style="display:none" @change="handlePhotoSelected">
                  <button class="btn btn-outline btn-sm" :disabled="photoUploading" @click="photoInput.click()">
                    {{ photoUploading ? 'Uploading…' : 'Upload Photo' }}
                  </button>
                  <button class="btn btn-ghost btn-sm" :disabled="!photoUrl || photoRemoving" @click="handleRemovePhoto">
                    {{ photoRemoving ? 'Removing…' : 'Remove' }}
                  </button>
                  <div class="ep-hint">JPG or PNG · up to 5MB · square recommended</div>
                </div>
              </div>
            </div>
          </section>

          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></div>
              <div><div class="ep-card-title">Basic Information</div><div class="ep-card-sub">Appears on your Aegis public profile</div></div>
            </div>
            <div class="ep-card-body">
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">First Name <span class="ep-req">*</span></label><input class="form-input" v-model="f.firstName"></div>
                <div class="form-group"><label class="form-label">Last Name <span class="ep-req">*</span></label><input class="form-input" v-model="f.lastName"></div>
              </div>
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">Credentials / Suffix</label><input class="form-input" v-model="f.credentials" placeholder="e.g., MD, PhD, LCSW"></div>
                <div class="form-group"><label class="form-label">Professional Title <span class="ep-req">*</span></label><input class="form-input" v-model="f.title" placeholder="e.g., Psychiatrist"></div>
              </div>
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">Practice / Affiliation</label><input class="form-input" v-model="f.practice"></div>
                <div class="form-group"><label class="form-label">Years in Business</label><input class="form-input" type="number" min="0" v-model="f.yearsBusiness"></div>
              </div>
              <div class="form-group"><label class="form-label">Professional Bio <span class="ep-req">*</span></label><textarea class="form-textarea" rows="4" v-model="f.bio" placeholder="Tell partners about your practice, focus areas, and approach…"></textarea></div>
            </div>
          </section>

          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
              <div><div class="ep-card-title">Contact &amp; Location</div><div class="ep-card-sub">Primary practice location shown to network partners</div></div>
            </div>
            <div class="ep-card-body">
              <div class="form-group"><label class="form-label">Practice Address <span class="ep-req">*</span></label><input class="form-input" v-model="f.address"></div>
              <div class="form-row-3">
                <div class="form-group"><label class="form-label">City <span class="ep-req">*</span></label><input class="form-input" v-model="f.city"></div>
                <div class="form-group"><label class="form-label">State <span class="ep-req">*</span></label><input class="form-input" v-model="f.state"></div>
                <div class="form-group"><label class="form-label">ZIP <span class="ep-req">*</span></label><input class="form-input" v-model="f.zip"></div>
              </div>
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">Phone <span class="ep-req">*</span></label><input class="form-input" v-model="f.phone"></div>
                <div class="form-group"><label class="form-label">Fax <span class="ep-opt">(Optional)</span></label><input class="form-input" v-model="f.fax"></div>
              </div>
              <div class="form-group"><label class="form-label">Email <span class="ep-req">*</span></label><input class="form-input" type="email" v-model="f.email"></div>
              <div class="form-group">
                <label class="form-label">Website <span class="ep-opt">(Optional)</span></label>
                <div class="ep-input-prefix"><span class="ep-input-prefix-label">https://</span><input class="form-input" v-model="f.website" placeholder="yoursite.com"></div>
              </div>
              <div class="form-group">
                <label class="form-label">Languages Spoken</label>
                <div class="ep-tags">
                  <div v-for="lang in languages" :key="lang" class="ep-tag" :class="{ active: f.languages.includes(lang) }" @click="toggleArr(f.languages, lang)">{{ lang }}</div>
                </div>
              </div>
              <div class="form-group"><label class="form-label">Timezone <span class="ep-req">*</span></label>
                <select class="form-select" v-model="f.timezone"><option>Eastern (ET)</option><option>Central (CT)</option><option>Mountain (MT)</option><option>Pacific (PT)</option></select>
              </div>
            </div>
          </section>

          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
              <div><div class="ep-card-title">Accepting Status</div><div class="ep-card-sub">Manage your visibility and contact preferences on Aegis.</div></div>
            </div>
            <div class="ep-card-body">
              <label v-for="t in acceptingToggles" :key="t.key" class="ep-toggle-row">
                <span class="ep-toggle-label">{{ t.label }}</span>
                <span class="ep-switch" :class="{ on: f.accepting[t.key] }" @click="f.accepting[t.key] = !f.accepting[t.key]"><span class="ep-switch-dot"></span></span>
              </label>
              <div class="form-group" style="margin-top:14px">
                <label class="form-label">Service Format</label>
                <div class="ep-tags">
                  <div v-for="fmt in serviceFormats" :key="fmt" class="ep-tag" :class="{ active: f.formats.includes(fmt) }" @click="toggleArr(f.formats, fmt)">{{ fmt }}</div>
                </div>
              </div>
            </div>
          </section>
        </div>

        <!-- ══ PROFESSIONAL ══ -->
        <div v-show="activeSection === 'professional'" class="ep-pane">
          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
              <div><div class="ep-card-title">License</div><div class="ep-card-sub">Add all active state licenses. Primary license appears first.</div></div>
            </div>
            <div class="ep-card-body">
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">License Number</label><input class="form-input" placeholder="e.g., NY-MD-12345"></div>
                <div class="form-group"><label class="form-label">State <span class="ep-req">*</span></label><input class="form-input"></div>
              </div>
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">Issue Date</label><input class="form-input" type="date"></div>
                <div class="form-group"><label class="form-label">Expiration Date <span class="ep-req">*</span></label><input class="form-input" type="date"></div>
              </div>
              <div class="form-group"><label class="form-label">License Document</label><div class="ep-dropzone"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Click to upload · PDF, JPG, PNG</div></div>
              <button class="btn btn-outline btn-sm"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add another license</button>
            </div>
          </section>

          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2"/></svg></div>
              <div><div class="ep-card-title">Credentials &amp; Certifications</div><div class="ep-card-sub">Primary credential type and board certifications</div></div>
            </div>
            <div class="ep-card-body">
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">Credential Type <span class="ep-req">*</span></label><select class="form-select"><option>MD</option><option>DO</option><option>PhD</option><option>PsyD</option><option>LCSW</option><option>LMFT</option><option>NP</option></select></div>
                <div class="form-group"><label class="form-label">Board Certification</label><input class="form-input" placeholder="e.g., ABPN"></div>
              </div>
              <div class="form-group"><label class="form-label">Other Certifications <span class="ep-opt">(Optional)</span></label><input class="form-input"></div>
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">Credential / License Number</label><input class="form-input"></div>
                <div class="form-group"><label class="form-label">Date of Licensure / Certification</label><input class="form-input" type="date"></div>
              </div>
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">NPI Number <span class="ep-opt">(Optional)</span></label><input class="form-input"></div>
                <div class="form-group"><label class="form-label">CAQH ID <span class="ep-opt">(Optional)</span></label><input class="form-input"></div>
              </div>
              <div class="form-row-3">
                <div class="form-group"><label class="form-label">DEA Registration</label><input class="form-input"></div>
                <div class="form-group"><label class="form-label">DEA Expiration Date</label><input class="form-input" type="date"></div>
                <div class="form-group"><label class="form-label">Years in Practice <span class="ep-req">*</span></label><input class="form-input" type="number" min="0"></div>
              </div>
            </div>
          </section>

          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
              <div><div class="ep-card-title">Liability Insurance</div><div class="ep-card-sub">Malpractice certificate — boosts profile trust score</div></div>
            </div>
            <div class="ep-card-body">
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">Insurance Provider</label><input class="form-input"></div>
                <div class="form-group"><label class="form-label">Policy Number</label><input class="form-input"></div>
              </div>
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">Coverage Amount</label><input class="form-input" placeholder="e.g., $2M / $4M"></div>
                <div class="form-group"><label class="form-label">Policy Expiration</label><input class="form-input" type="date"></div>
              </div>
              <div class="form-group"><label class="form-label">Certificate of Insurance</label><div class="ep-dropzone"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Click to upload · PDF, JPG, PNG</div></div>
            </div>
          </section>

          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2 3 6 3s6-2 6-3v-5"/></svg></div>
              <div><div class="ep-card-title">Education &amp; Training</div><div class="ep-card-sub">Academic degrees, residencies, fellowships and continuing education</div></div>
            </div>
            <div class="ep-card-body">
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">Degree / Credential</label><input class="form-input"></div>
                <div class="form-group"><label class="form-label">Field of Study</label><input class="form-input"></div>
              </div>
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">Institution</label><input class="form-input"></div>
                <div class="form-group"><label class="form-label">Duration</label><input class="form-input" placeholder="e.g., 2010 – 2014"></div>
              </div>
              <button class="btn btn-outline btn-sm"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add education</button>
            </div>
          </section>
        </div>

        <!-- ══ SPECIALTIES ══ -->
        <div v-show="activeSection === 'specialties'" class="ep-pane ep-pane-specialties">
          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polygon points="12 2 15 8.5 22 9.3 17 14 18.2 21 12 17.5 5.8 21 7 14 2 9.3 9 8.5 12 2"/></svg></div>
              <div><div class="ep-card-title">Services &amp; Specialties</div><div class="ep-card-sub">Select all that apply. You may also add custom entries.</div></div>
            </div>
            <div class="ep-card-body">
              <div v-for="(cat, index) in specialtyCategories" :key="cat.name" style="margin-bottom: 24px; padding-top: 24px;" :style="index > 0 ? 'border-top: 1px solid var(--border);' : ''">
                <div class="form-label" :data-collapsed="collapsedSections['specialties-' + cat.name]" @click="toggleSection('specialties-' + cat.name)">{{ cat.name }}</div>
                <div v-show="!collapsedSections['specialties-' + cat.name]" style="margin-top: 8px;">
                  <div class="ep-tags">
                    <div v-for="s in cat.items" :key="s" class="ep-tag" :class="{ active: f.specialties.includes(s) }" @click="toggleArr(f.specialties, s)">{{ s }}</div>
                  </div>
                </div>
              </div>
              <div style="margin-bottom: 24px; padding-top: 24px; border-top: 1px solid var(--border);">
                <div class="form-label form-label-no-arrow" :data-collapsed="collapsedSections['specialties-Custom']" @click="toggleSection('specialties-Custom')">CUSTOM</div>
                <div v-show="!collapsedSections['specialties-Custom']" style="margin-top: 8px;">
                  <div class="ep-tags">
                    <div v-for="s in getCustomItems('specialties')" :key="s" class="ep-tag" :class="{ active: f.specialties.includes(s) }" @click="toggleArr(f.specialties, s)">{{ s }}</div>
                  </div>
                </div>
              </div>
              <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border);">
                <div class="form-label form-label-no-arrow" style="margin-bottom: 8px;">ADD CUSTOM SERVICE</div>
                <div style="display: flex; gap: 8px; align-items: center;">
                  <input type="text" v-model="customInputs.specialties" placeholder="Enter a service not listed..." class="form-input" style="flex: 1;" @keyup.enter="addCustomItem('specialties')">
                  <button class="btn btn-outline btn-sm" @click="addCustomItem('specialties')">+ Add</button>
                </div>
              </div>
            </div>
          </section>
          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>
              <div><div class="ep-card-title">Approaches &amp; Frameworks</div><div class="ep-card-sub">Methods and frameworks you use</div></div>
            </div>
            <div class="ep-card-body">
              <div v-for="(cat, index) in approachCategories" :key="cat.name" style="margin-bottom: 24px; padding-top: 24px;" :style="index > 0 ? 'border-top: 1px solid var(--border);' : ''">
                <div class="form-label" :data-collapsed="collapsedSections['approaches-' + cat.name]" @click="toggleSection('approaches-' + cat.name)">{{ cat.name }}</div>
                <div v-show="!collapsedSections['approaches-' + cat.name]" style="margin-top: 8px;">
                  <div class="ep-tags">
                    <div v-for="a in cat.items" :key="a" class="ep-tag" :class="{ active: f.approaches.includes(a) }" @click="toggleArr(f.approaches, a)">{{ a }}</div>
                  </div>
                </div>
              </div>
              <div style="margin-bottom: 24px; padding-top: 24px; border-top: 1px solid var(--border);">
                <div class="form-label form-label-no-arrow" @click="toggleSection('approaches-Custom')">CUSTOM</div>
                <div v-show="!collapsedSections['approaches-Custom']" style="margin-top: 8px;">
                  <div class="ep-tags">
                    <div v-for="a in getCustomItems('approaches')" :key="a" class="ep-tag" :class="{ active: f.approaches.includes(a) }" @click="toggleArr(f.approaches, a)">{{ a }}</div>
                  </div>
                </div>
              </div>
              <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border);">
                <div class="form-label form-label-no-arrow" style="margin-bottom: 8px;">ADD CUSTOM APPROACH</div>
                <div style="display: flex; gap: 8px; align-items: center;">
                  <input type="text" v-model="customInputs.approaches" placeholder="Enter an approach not listed..." class="form-input" style="flex: 1;" @keyup.enter="addCustomItem('approaches')">
                  <button class="btn btn-outline btn-sm" @click="addCustomItem('approaches')">+ Add</button>
                </div>
              </div>
            </div>
          </section>
        </div>

        <!-- ══ FEES & INSURANCE ══ -->
        <div v-show="activeSection === 'insurance'" class="ep-pane">
          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
              <div><div class="ep-card-title">Insurance Accepted</div><div class="ep-card-sub">Select all plans you are in-network for</div></div>
            </div>
            <div class="ep-card-body">
              <div v-for="group in insurerGroups" :key="group.name" class="ins-group">
                <div class="ins-group-label">{{ group.name }}</div>
                <div class="ins-grid">
                  <div
                    v-for="plan in group.plans"
                    :key="plan"
                    class="ins-option"
                    :class="{ selected: f.insurers.includes(plan) }"
                    @click="toggleArr(f.insurers, plan)"
                  >
                    <span class="ins-radio" :class="{ checked: f.insurers.includes(plan) }">
                      <span class="ins-radio-dot"></span>
                    </span>
                    <span class="ins-label-text">{{ plan }}</span>
                  </div>
                </div>
              </div>
              <!-- Custom / not listed -->
              <div class="ins-custom-row">
                <input
                  v-model="insurerInput"
                  class="form-input"
                  placeholder="Add insurance not listed…"
                  @keyup.enter="addCustomInsurer"
                >
                <button class="btn btn-outline btn-sm ins-add-btn" @click="addCustomInsurer">+ Add</button>
              </div>
              <div v-if="f.customInsurers && f.customInsurers.length" class="ins-group" style="margin-top:16px;">
                <div class="ins-group-label">CUSTOM</div>
                <div class="ins-grid">
                  <div
                    v-for="plan in f.customInsurers"
                    :key="plan"
                    class="ins-option"
                    :class="{ selected: f.insurers.includes(plan) }"
                    @click="toggleArr(f.insurers, plan)"
                  >
                    <span class="ins-radio" :class="{ checked: f.insurers.includes(plan) }">
                      <span class="ins-radio-dot"></span>
                    </span>
                    <span class="ins-label-text">{{ plan }}</span>
                  </div>
                </div>
              </div>
            </div>
          </section>
          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
              <div><div class="ep-card-title">Out-of-Pocket Fees</div><div class="ep-card-sub">Shared with Continuity Stewards and providers to facilitate referrals.</div></div>
            </div>
            <div class="ep-card-body">
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">Session or Meeting Length <span class="ep-req">*</span></label><select class="form-select"><option>30 min</option><option>45 min</option><option>50 min</option><option>60 min</option></select></div>
                <div class="form-group"><label class="form-label">Rate <span class="ep-req">*</span></label><input class="form-input" placeholder="$"></div>
              </div>
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">Initial Evaluation Rate</label><input class="form-input" placeholder="$"></div>
                <div class="form-group"><label class="form-label">Medication Mgmt (15 min)</label><input class="form-input" placeholder="$"></div>
              </div>
              <label class="ep-toggle-row"><span class="ep-toggle-label">Sliding Scale Available?</span><span class="ep-switch" :class="{ on: f.slidingScale }" @click="f.slidingScale = !f.slidingScale"><span class="ep-switch-dot"></span></span></label>
              <div v-show="f.slidingScale" class="form-row-2">
                <div class="form-group"><label class="form-label">Sliding Scale Min</label><input class="form-input" placeholder="$"></div>
                <div class="form-group"><label class="form-label">Sliding Scale Max</label><input class="form-input" placeholder="$"></div>
              </div>
              <label class="ep-toggle-row"><span class="ep-toggle-label">Package / Bundle Available?</span><span class="ep-switch" :class="{ on: f.packageAvail }" @click="f.packageAvail = !f.packageAvail"><span class="ep-switch-dot"></span></span></label>
              <div v-show="f.packageAvail">
                <div class="form-group"><label class="form-label">Package Description</label><input class="form-input"></div>
                <div class="form-group"><label class="form-label">Package Rate</label><input class="form-input" placeholder="$"></div>
              </div>
              <div class="form-group"><label class="form-label">Additional Notes <span class="ep-opt">(Optional)</span></label><textarea class="form-textarea" rows="2"></textarea></div>
            </div>
          </section>
        </div>

        <!-- ══ NETWORK PREFERENCES ══ -->
        <div v-show="activeSection === 'network'" class="ep-pane">
          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="5" r="2"/><circle cx="5" cy="19" r="2"/><circle cx="19" cy="19" r="2"/><path d="M12 7v6M12 13l-6 4M12 13l6 4"/></svg></div>
              <div>
                <div class="ep-card-title">Interdisciplinary Network</div>
                <div class="ep-card-sub">Establish a care network with professionals from various fields for quick referrals and coordinated, holistic support. Form partnerships ahead of time to address future needs.</div>
              </div>
            </div>
            <div class="ep-card-body">
              <div class="ep-tags">
                <div
                  v-for="role in networkRoleOptions"
                  :key="role"
                  class="ep-tag ep-tag-pill"
                  :class="{ active: f.networkRoles.includes(role) }"
                  @click="toggleArr(f.networkRoles, role)"
                >{{ role }}</div>
              </div>
            </div>
          </section>
        </div>

        <!-- ══ DEMOGRAPHICS ══ -->
        <div v-show="activeSection === 'demographics'" class="ep-pane">
          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg></div>
              <div><div class="ep-card-title">Provider Identity &amp; Demographics</div><div class="ep-card-sub">Optional — helps patients find culturally aligned providers</div></div>
            </div>
            <div class="ep-card-body">

              <!-- Privacy notice -->
              <div class="demo-notice">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>This information is entirely optional and private by default. You control what appears publicly.</span>
              </div>

              <!-- Pronouns -->
              <div class="demo-group">
                <div class="demo-group-label">Pronouns</div>
                <div class="ep-tags">
                  <div v-for="opt in demoOptions.pronouns" :key="opt" class="ep-tag ep-tag-pill" :class="{ active: f.demo.pronouns.includes(opt) }" @click="toggleArr(f.demo.pronouns, opt)">{{ opt }}</div>
                </div>
              </div>

              <!-- Race / Ethnicity -->
              <div class="demo-group">
                <div class="demo-group-label">Race / Ethnicity</div>
                <div class="ep-tags">
                  <div v-for="opt in demoOptions.raceEthnicity" :key="opt" class="ep-tag ep-tag-pill" :class="{ active: f.demo.raceEthnicity.includes(opt) }" @click="toggleArr(f.demo.raceEthnicity, opt)">{{ opt }}</div>
                </div>
              </div>

              <!-- LGBTQ+ Identity -->
              <div class="demo-group">
                <div class="demo-group-label">LGBTQ+ Identity</div>
                <div class="ep-tags">
                  <div v-for="opt in demoOptions.lgbtq" :key="opt" class="ep-tag ep-tag-pill" :class="{ active: f.demo.lgbtq.includes(opt) }" @click="toggleArr(f.demo.lgbtq, opt)">{{ opt }}</div>
                </div>
              </div>

              <!-- Parenting Status -->
              <div class="demo-group">
                <div class="demo-group-label">Parenting Status</div>
                <div class="ep-tags">
                  <div v-for="opt in demoOptions.parentingStatus" :key="opt" class="ep-tag ep-tag-pill" :class="{ active: f.demo.parentingStatus.includes(opt) }" @click="toggleArr(f.demo.parentingStatus, opt)">{{ opt }}</div>
                </div>
              </div>

              <!-- Religious / Spiritual Orientation -->
              <div class="demo-group">
                <div class="demo-group-label">Religious / Spiritual Orientation</div>
                <div class="ep-tags">
                  <div v-for="opt in demoOptions.religion" :key="opt" class="ep-tag ep-tag-pill" :class="{ active: f.demo.religion.includes(opt) }" @click="toggleArr(f.demo.religion, opt)">{{ opt }}</div>
                </div>
              </div>

              <!-- Veteran Status -->
              <div class="demo-group">
                <div class="demo-group-label">Veteran Status</div>
                <div class="ep-tags">
                  <div v-for="opt in demoOptions.veteranStatus" :key="opt" class="ep-tag ep-tag-pill" :class="{ active: f.demo.veteranStatus.includes(opt) }" @click="toggleArr(f.demo.veteranStatus, opt)">{{ opt }}</div>
                </div>
              </div>

              <!-- Clinical Supervision Status -->
              <div class="demo-group">
                <div class="demo-group-label">Clinical Supervision Status</div>
                <div class="ep-tags">
                  <div v-for="opt in demoOptions.clinicalSupervision" :key="opt" class="ep-tag ep-tag-pill" :class="{ active: f.demo.clinicalSupervision.includes(opt) }" @click="toggleArr(f.demo.clinicalSupervision, opt)">{{ opt }}</div>
                </div>
              </div>

            </div>
          </section>
        </div>

        <!-- ══ AVAILABILITY ══ -->
        <div v-show="activeSection === 'availability'" class="ep-pane">
          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
              <div><div class="ep-card-title">Operating Hours</div><div class="ep-card-sub">When are you available to see patients and accept referrals?</div></div>
            </div>
            <div class="ep-card-body">
              <div class="form-group">
                <label class="form-label">Available Days <span class="ep-req">*</span></label>
                <div class="ep-tags">
                  <div v-for="d in days" :key="d" class="ep-tag" :class="{ active: f.days.includes(d) }" @click="toggleArr(f.days, d)">{{ d }}</div>
                </div>
              </div>
              <div class="form-row-3">
                <div class="form-group"><label class="form-label">Start Time <span class="ep-req">*</span></label><input class="form-input" type="time" v-model="f.start"></div>
                <div class="form-group"><label class="form-label">End Time <span class="ep-req">*</span></label><input class="form-input" type="time" v-model="f.end"></div>
                <div class="form-group"><label class="form-label">Timezone <span class="ep-req">*</span></label><select class="form-select"><option>Eastern (ET)</option><option>Central (CT)</option><option>Mountain (MT)</option><option>Pacific (PT)</option></select></div>
              </div>
              <div class="form-group"><label class="form-label">Typical Response Time</label><select class="form-select"><option>Within a few hours</option><option>Same day</option><option>Within 24 hours</option><option>1–2 business days</option></select></div>
            </div>
          </section>
          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg></div>
              <div><div class="ep-card-title">Telehealth Settings</div><div class="ep-card-sub">Configure your telehealth availability and platform</div></div>
            </div>
            <div class="ep-card-body">
              <div class="form-row-2">
                <div class="form-group"><label class="form-label">Telehealth Platform</label><select class="form-select"><option>Aegis Secure Video</option><option>Zoom for Healthcare</option><option>Doxy.me</option><option>SimplePractice</option></select></div>
                <div class="form-group"><label class="form-label">Telehealth States</label><input class="form-input" placeholder="e.g., NY, NJ, CT"></div>
              </div>
            </div>
          </section>
        </div>

        <!-- ══ COLLABORATION ══ -->
        <div v-show="activeSection === 'collaboration'" class="ep-pane">
          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
              <div><div class="ep-card-title">Collaboration & Referral Preferences</div><div class="ep-card-sub">How you prefer to collaborate with other providers and receive referrals.</div></div>
            </div>
            <div class="ep-card-body">
              <div class="form-group">
                <label class="form-label">Referral Preferences</label>
                <div class="ep-tags">
                  <div class="ep-tag" :class="{ active: f.referralPrefs?.includes('Warm handoff') }" @click="toggleArr(f.referralPrefs || (f.referralPrefs = []), 'Warm handoff')">Warm handoff</div>
                  <div class="ep-tag" :class="{ active: f.referralPrefs?.includes('No gap in care') }" @click="toggleArr(f.referralPrefs || (f.referralPrefs = []), 'No gap in care')">No gap in care</div>
                  <div class="ep-tag" :class="{ active: f.referralPrefs?.includes('Comprehensive notes') }" @click="toggleArr(f.referralPrefs || (f.referralPrefs = []), 'Comprehensive notes')">Comprehensive notes</div>
                  <div class="ep-tag" :class="{ active: f.referralPrefs?.includes('Release of info required') }" @click="toggleArr(f.referralPrefs || (f.referralPrefs = []), 'Release of info required')">Release of info required</div>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Collaboration Style</label>
                <div class="ep-tags">
                  <div class="ep-tag" :class="{ active: f.collabStyle?.includes('Case consultation') }" @click="toggleArr(f.collabStyle || (f.collabStyle = []), 'Case consultation')">Case consultation</div>
                  <div class="ep-tag" :class="{ active: f.collabStyle?.includes('Co-treatment') }" @click="toggleArr(f.collabStyle || (f.collabStyle = []), 'Co-treatment')">Co-treatment</div>
                  <div class="ep-tag" :class="{ active: f.collabStyle?.includes('Supervision') }" @click="toggleArr(f.collabStyle || (f.collabStyle = []), 'Supervision')">Supervision</div>
                </div>
              </div>
            </div>
          </section>
        </div>

        <!-- ══ TECHNOLOGY ══ -->
        <div v-show="activeSection === 'technology'" class="ep-pane">
          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg></div>
              <div><div class="ep-card-title">Technology & Data</div><div class="ep-card-sub">EHR systems, data sharing preferences, and security protocols.</div></div>
            </div>
            <div class="ep-card-body">
              <div class="form-group"><label class="form-label">EHR / Practice Management System</label><input class="form-input" placeholder="e.g., Epic, Cerner, SimplePractice" v-model="f.ehrSystem"></div>
              <div class="form-group">
                <label class="form-label">Data Sharing Preferences</label>
                <div class="ep-tags">
                  <div class="ep-tag" :class="{ active: f.dataSharing?.includes('Secure message only') }" @click="toggleArr(f.dataSharing || (f.dataSharing = []), 'Secure message only')">Secure message only</div>
                  <div class="ep-tag" :class="{ active: f.dataSharing?.includes('EHR integration') }" @click="toggleArr(f.dataSharing || (f.dataSharing = []), 'EHR integration')">EHR integration</div>
                  <div class="ep-tag" :class="{ active: f.dataSharing?.includes('Fax') }" @click="toggleArr(f.dataSharing || (f.dataSharing = []), 'Fax')">Fax</div>
                  <div class="ep-tag" :class="{ active: f.dataSharing?.includes('No sharing') }" @click="toggleArr(f.dataSharing || (f.dataSharing = []), 'No sharing')">No sharing</div>
                </div>
              </div>
            </div>
          </section>
        </div>

        <!-- ══ CULTURAL ══ -->
        <div v-show="activeSection === 'cultural'" class="ep-pane">
          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10"/></svg></div>
              <div><div class="ep-card-title">Language & Cultural Competence</div><div class="ep-card-sub">Cultural competencies beyond languages.</div></div>
            </div>
            <div class="ep-card-body">
              <div class="form-group">
                <label class="form-label">Cultural Competencies</label>
                <div class="ep-tags">
                  <div class="ep-tag" :class="{ active: f.cultural?.includes('LGBTQ+ affirming') }" @click="toggleArr(f.cultural || (f.cultural = []), 'LGBTQ+ affirming')">LGBTQ+ affirming</div>
                  <div class="ep-tag" :class="{ active: f.cultural?.includes('Kink-aware') }" @click="toggleArr(f.cultural || (f.cultural = []), 'Kink-aware')">Kink-aware</div>
                  <div class="ep-tag" :class="{ active: f.cultural?.includes('Religious/spiritual sensitivity') }" @click="toggleArr(f.cultural || (f.cultural = []), 'Religious/spiritual sensitivity')">Religious/spiritual sensitivity</div>
                  <div class="ep-tag" :class="{ active: f.cultural?.includes('Immigrant/refugee experience') }" @click="toggleArr(f.cultural || (f.cultural = []), 'Immigrant/refugee experience')">Immigrant/refugee experience</div>
                </div>
              </div>
            </div>
          </section>
        </div>

        <!-- ══ CRISIS ══ -->
        <div v-show="activeSection === 'crisis'" class="ep-pane">
          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
              <div><div class="ep-card-title">Crisis Stability</div><div class="ep-card-sub">How you manage crises and continuity of care.</div></div>
            </div>
            <div class="ep-card-body">
              <label class="ep-toggle-row"><span class="ep-toggle-label">Has crisis plan in place</span><span class="ep-switch" :class="{ on: f.crisisPlan }" @click="f.crisisPlan = !f.crisisPlan"><span class="ep-switch-dot"></span></span></label>
              <label class="ep-toggle-row"><span class="ep-toggle-label">Accepts crisis referrals</span><span class="ep-switch" :class="{ on: f.crisisReferrals }" @click="f.crisisReferrals = !f.crisisReferrals"><span class="ep-switch-dot"></span></span></label>
              <div class="form-group"><label class="form-label">Crisis Contact Info</label><input class="form-input" placeholder="Phone number or crisis resource" v-model="f.crisisContact"></div>
            </div>
          </section>
        </div>

        <!-- ══ ETHICAL ══ -->
        <div v-show="activeSection === 'ethical'" class="ep-pane">
          <section class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
              <div><div class="ep-card-title">Ethical Lenses & Workstyle</div><div class="ep-card-sub">Your ethical framework and work preferences.</div></div>
            </div>
            <div class="ep-card-body">
              <div class="form-group">
                <label class="form-label">Ethical Frameworks</label>
                <div class="ep-tags">
                  <div class="ep-tag" :class="{ active: f.ethical?.includes('Non-maleficence') }" @click="toggleArr(f.ethical || (f.ethical = []), 'Non-maleficence')">Non-maleficence</div>
                  <div class="ep-tag" :class="{ active: f.ethical?.includes('Beneficence') }" @click="toggleArr(f.ethical || (f.ethical = []), 'Beneficence')">Beneficence</div>
                  <div class="ep-tag" :class="{ active: f.ethical?.includes('Autonomy') }" @click="toggleArr(f.ethical || (f.ethical = []), 'Autonomy')">Autonomy</div>
                  <div class="ep-tag" :class="{ active: f.ethical?.includes('Justice') }" @click="toggleArr(f.ethical || (f.ethical = []), 'Justice')">Justice</div>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Work Style</label>
                <div class="ep-tags">
                  <div class="ep-tag" :class="{ active: f.workstyle?.includes('Collaborative') }" @click="toggleArr(f.workstyle || (f.workstyle = []), 'Collaborative')">Collaborative</div>
                  <div class="ep-tag" :class="{ active: f.workstyle?.includes('Independent') }" @click="toggleArr(f.workstyle || (f.workstyle = []), 'Independent')">Independent</div>
                  <div class="ep-tag" :class="{ active: f.workstyle?.includes('Structured') }" @click="toggleArr(f.workstyle || (f.workstyle = []), 'Structured')">Structured</div>
                  <div class="ep-tag" :class="{ active: f.workstyle?.includes('Flexible') }" @click="toggleArr(f.workstyle || (f.workstyle = []), 'Flexible')">Flexible</div>
                </div>
              </div>
            </div>
          </section>
        </div>

      </div>
    </div>

    <!-- SAVE BAR -->
    <div class="ep-save-bar">
      <div class="ep-save-meta"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Changes are saved to your Aegis profile</div>
      <div class="ep-save-actions">
        <button class="btn btn-outline btn-sm" @click="discardChanges">Discard</button>
        <button class="btn btn-primary btn-sm" @click="saveChanges">Save Changes</button>
      </div>
    </div>

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
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Components/AppLayout.vue';

const props = defineProps({
    user: { type: Object, default: () => ({}) },
    profile_photo_url: { type: String, default: null },
});

// ── Photo upload/remove ──
const photoInput = ref(null);
const photoUrl = ref(props.profile_photo_url);
const photoUploading = ref(false);
const photoRemoving = ref(false);

function handlePhotoSelected(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Basic client-side size check (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showToast('File must be 5MB or smaller', 'error');
        e.target.value = '';
        return;
    }

    // Optimistic preview
    photoUrl.value = URL.createObjectURL(file);

    const form = new FormData();
    form.append('photo', file);

    photoUploading.value = true;
    router.post('/provider/profile/photo', form, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: (page) => {
            // Re-read the URL from updated props after Inertia reloads
            photoUrl.value = page.props.profile_photo_url ?? photoUrl.value;
            showToast('Photo updated', 'success');
        },
        onError: () => {
            photoUrl.value = props.profile_photo_url;
            showToast('Upload failed — check file type and size', 'error');
        },
        onFinish: () => {
            photoUploading.value = false;
            if (photoInput.value) photoInput.value.value = '';
        },
    });
}

function handleRemovePhoto() {
    if (!photoUrl.value) return;
    photoRemoving.value = true;
    router.delete('/provider/profile/photo', {
        preserveScroll: true,
        onSuccess: () => {
            photoUrl.value = null;
            showToast('Photo removed', 'success');
        },
        onError: () => showToast('Could not remove photo', 'error'),
        onFinish: () => { photoRemoving.value = false; },
    });
}

const icon = (p) => `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round">${p}</svg>`;
const sections = [
  { id: 'basic-info', label: 'Basic Info', icon: icon('<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>') },
  { id: 'professional', label: 'Professional', icon: icon('<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>') },
  { id: 'specialties', label: 'Specialties', icon: icon('<polygon points="12 2 15 8.5 22 9.3 17 14 18.2 21 12 17.5 5.8 21 7 14 2 9.3 9 8.5 12 2"/>') },
  { id: 'insurance', label: 'Fees & Insurance', icon: icon('<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>') },
  { id: 'network', label: 'Network Preferences', icon: icon('<circle cx="12" cy="5" r="2"/><circle cx="5" cy="19" r="2"/><circle cx="19" cy="19" r="2"/><path d="M12 7v6M12 13l-6 4M12 13l6 4"/>') },
  { id: 'collaboration', label: 'Collaboration', icon: icon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>') },
  { id: 'technology', label: 'Technology', icon: icon('<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/>') },
  { id: 'cultural', label: 'Cultural Competence', icon: icon('<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10"/>') },
  { id: 'crisis', label: 'Crisis Stability', icon: icon('<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>') },
  { id: 'ethical', label: 'Ethics & Workstyle', icon: icon('<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>') },
  { id: 'demographics', label: 'Demographics', icon: icon('<circle cx="9" cy="7" r="4"/><path d="M2 21v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1"/>') },
  { id: 'availability', label: 'Availability', icon: icon('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>') },
];
const activeSection = ref('basic-info');
const collapsedSections = ref({});
const customInputs = ref({});
const insurerInput = ref('');

function toggleSection(sectionName) {
  collapsedSections.value[sectionName] = !collapsedSections.value[sectionName];
}

function addCustomInsurer() {
  const val = insurerInput.value.trim();
  if (!val) return;
  if (!f.customInsurers) f.customInsurers = [];
  if (!f.customInsurers.includes(val)) {
    f.customInsurers.push(val);
  }
  if (!f.insurers.includes(val)) {
    f.insurers.push(val);
  }
  insurerInput.value = '';
}

function addCustomItem(type) {
  const inputValue = customInputs.value[type];
  if (inputValue && inputValue.trim()) {
    const customArr = type === 'specialties' ? f.customSpecialties : f.customApproaches;
    const mainArr = type === 'specialties' ? f.specialties : f.approaches;
    if (!customArr.includes(inputValue.trim())) {
      customArr.push(inputValue.trim());
    }
    if (!mainArr.includes(inputValue.trim())) {
      mainArr.push(inputValue.trim());
    }
    customInputs.value[type] = '';
  }
}

function getAllPredefinedItems(type) {
  const categories = type === 'specialties' ? specialtyCategories : approachCategories;
  let items = [];
  categories.forEach(cat => items = items.concat(cat.items));
  return items;
}

function getCustomItems(type) {
  return type === 'specialties' ? f.customSpecialties : f.customApproaches;
}

const languages = ['English', 'Spanish', 'Mandarin Chinese', 'French', 'Arabic', 'Hindi', 'Portuguese', 'Russian', 'Korean', 'Japanese'];
const serviceFormats = ['In-person', 'Telehealth', 'Hybrid', 'Home visits'];
const specialtyCategories = [
  { name: 'SERVICES', items: ['Comprehensive assessment (in-depth)', 'Brief evaluation/consultation', 'Holistic health practices', 'Biofeedback & bioenergetics', 'Wellness assessment', 'Sports/performance enhancement', 'Health & wellness consultation/support', 'Workplace wellbeing consulting', 'EAP consultations', 'Cultural responsiveness consulting', 'Care coordination & professional guidance', 'Clinical supervision', 'Career advisory & strategy session'] },
  { name: 'OFFICIALLY LICENSED', items: ['Massage Therapy', 'Licensed Midwifery', 'Personal training', 'Nutritional counseling', 'Yoga & yoga therapy', 'Acupuncture', 'Birth doula support', 'Prenatal yoga', 'Sleep assessments & solutions', 'Sleep training', 'Sleep hygiene & Circadian rhythm education'] },
  { name: 'SPECIALTY FOCUS', items: ['Herbal consultations', 'Homeopathic remedies', 'Energy work (Reiki, Shamanic healing)', 'Gut health assessment', 'Medication evaluation', 'Precision (Omics-Based) Wellness Services'] },
  { name: 'PSYCHOTHERAPY & THERAPIES', items: ['Integrative mental health therapy', 'Life transitions', 'Career & work support', 'Relationship challenges', 'Lifestyle & habits', 'Wellness & health coaching', 'Stress reduction & burnout recovery', 'Family support', 'Parenting & children', 'Children & adolescents support', 'Anxiety & worry', 'Grief/loss', 'Social anxiety', 'Obsessive Compulsive Disorder (OCD)', 'PTSD & trauma', 'Bipolar disorder', 'Eating disorders', 'Addiction & substance use', 'Eating issues & body image', 'Identity exploration', 'Self-worth & acceptance', 'Psychiatric & psychological support', 'Somatic & sensory-based therapies'] },
  { name: 'MODALITIES & SUPPLEMENTS', items: ['Medication management', 'Ketamine (esketamine, ketamine)', 'Other psychedelic & spirit', 'Other support', 'Integrative & cultural therapies', 'TRAUMA support', 'Self-harm & suicidal ideations', 'Mental health & substance', 'Chronic health conditions & management', 'Cancer support', 'Diabetes & blood sugar', 'Heart & cardiovascular health', 'Hormonal health', 'Nutrition & dietary support', 'Functional medicine options', 'Gut & digestive health', 'Menstrual & menstrual health', 'Pregnancy & postpartum health', 'Sports & performance nutrition', 'Preventative care & longevity', 'Brain health & cognitive support', 'Mood daily & sleep', 'Energy & spiritual care', 'Hormone support & education', 'Personalized education & support', 'Birth support', 'Postpartum support', 'Reproductive & prenatal health', 'ADHD, play & autistic support', 'Autism-aligned approaches', 'Trauma-informed approaches', 'Diversity, equity & inclusion', 'Corporate & workplace wellbeing'] },
];
const approachCategories = [
  { name: 'CLINICAL', items: ['Cognitive Behavioral Therapy (CBT)', 'Dialectical Behavior Therapy', 'Acceptance and Commitment Therapy', 'Family Therapy', 'Gestalt therapy', 'Jungian therapy', 'Existential therapy', 'Narrative Therapy', 'Psychodynamic Therapy', 'EMDR', 'Attachment-based therapy', 'Dialectical oriented therapy', 'Internal Family Systems', 'Psychology Therapy'] },
  { name: 'NUTRITION & DIETETICS', items: ['Nutrition therapy (therapeutic nutrition)', 'Nutritional Nutritional Healing', 'Behavioral Change Counseling', 'Intuitive Eating', 'Nutritional Access', 'Anti-dietary/intuition', 'Functional Nutrition (Functional Nutrition)', 'Ayurvedic nutrition/dosha', 'Genomic Nutrition & CBT Coaching', 'Gene Nutrition & Eating', 'Bioindividual Nutrition', 'Nutritional Psychiatry'] },
  { name: 'FUNCTIONAL MEDICINE', items: ['The Institute for Functional Medicine', 'Personalized medicine', 'Detox & body cleansing', 'Preventative health', 'Gut health & microbiota/imbalances', 'Autoimmune conditions', 'Long-term COVID approach', 'Keto & Paleo', 'Nutrition', 'Lifestyle & longevity', 'Heart, endocrine & hormones'] },
  { name: 'PSYCHIATRY', items: ['Psychopharmacology', 'Medication Management', 'Controlled Therapies & Ketamine', 'Evidence-Based Prescribing', 'Treatment-Resistant Psychiatric', 'Long-Acting Injectable Therapy', 'Anti-Cancer Approach (BCMA, etc.) Ketamine', 'Collaborative Care Model', 'Psychiatric Assessment & Care Planning'] },
];
const insurerGroups = [
  {
    name: 'COMMERCIAL HEALTH PLANS',
    plans: ['Aetna', 'Cigna / Evernorth', 'UnitedHealthcare', 'Humana', 'Kaiser Permanente', 'Oscar Health', 'Molina Healthcare', 'WellCare', 'Centene'],
  },
  {
    name: 'BLUE CROSS BLUE SHIELD',
    plans: ['Anthem BCBS', 'BCBS (State / Regional)', 'Blue Shield of California', 'Premera Blue Cross', 'Regence BlueCross'],
  },
  {
    name: 'GOVERNMENT PROGRAMS',
    plans: ['Medicare', 'Medicaid', 'TRICARE', 'VA Community Care', 'CHIP'],
  },
  {
    name: 'MARKETPLACE / SPECIALTY',
    plans: ['EAP', 'Optum / Optum Health', 'Magellan Health', 'ValueOptions / Beacon', 'Multiplan / PHCS', 'Out-of-Network (OON)'],
  },
];
const demoOptions = {
  pronouns:          ['She / Her', 'He / Him', 'They / Them', 'Ze / Zir', 'Any Pronouns', 'Prefer Not to Disclose'],
  raceEthnicity:     ['American Indian or Alaska Native', 'Asian', 'Black or African American', 'Hispanic or Latino/a/x', 'Middle Eastern or North African', 'White or Caucasian', 'Multiracial or Biracial', 'Prefer Not to Say'],
  lgbtq:             ['LGBTQ+ Identifying Provider', 'LGBTQ+ Affirming (Ally)', 'Not Disclosed'],
  parentingStatus:   ['Parent', 'Not a Parent', 'Prefer Not to Disclose'],
  religion:          ['Christian', 'Jewish', 'Muslim', 'Buddhist', 'Hindu', 'Spiritual (Non-Religious)', 'Secular / Non-Religious', 'Prefer Not to Disclose'],
  veteranStatus:     ['Military Veteran', 'Active Duty Military', 'Veteran-Affirming (Non-Veteran)', 'Not Applicable'],
  clinicalSupervision: ['Yes — I Provide Clinical Supervision', 'No — I Do Not Provide Supervision', 'Approved Supervisor (Licensed)', 'Accepting Supervisees', 'Not Accepting Supervisees'],
};
const networkRoleOptions = [
  'Psychotherapist & Psychologist', 'Psychiatrist', 'Movement / Dance Specialist',
  'Coach (lifestyle, career)', 'Behavioral Therapist', 'Massage Therapist', 'Acupuncturist',
  'Functional Medicine Practitioner', 'Holistic Nutrition Practitioner',
  'Certified Diabetes Educator (CDE)', 'Hypnotherapist', 'Energy Healing Practitioner',
  'Homeopath', 'Herbalist', 'Somatic Practitioner', 'Ayurveda Practitioner',
  'Certified Nurse-Midwife (CNM)', 'Doula', 'Sleep Specialist', 'Genetic Counselor',
  'Personal Trainer',
];

const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const acceptingToggles = [
  { key: 'clients', label: 'Accepting New Clients?' },
  { key: 'referrals', label: 'Accepting New Referrals?' },
  { key: 'supervisees', label: 'Accepting Supervisees?' },
  { key: 'continuity', label: 'Accepting Provider Continuity Clients?' },
];

const nameParts = (props.user?.display_name || 'Sarah Johnson').replace(/^Dr\.?\s+/i, '').split(' ');

// Load from localStorage or use defaults, always merge with defaultProfile so new keys exist
const savedProfile = localStorage.getItem('providerProfile');
const defaultProfile = {
  firstName: nameParts[0] || '', lastName: nameParts.slice(1).join(' ') || '',
  credentials: props.user?.credentials || '', title: props.user?.specialty || 'Psychiatrist',
  practice: props.user?.organization || '', yearsBusiness: '', bio: props.user?.bio || '',
  address: '', city: '', state: '', zip: '', phone: props.user?.phone || '', fax: '',
  email: props.user?.email || '', website: '', timezone: 'Eastern (ET)',
  languages: ['English'], formats: ['In-person', 'Telehealth'],
  accepting: { clients: true, referrals: true, supervisees: false, continuity: true },
  specialties: ['Anxiety', 'Depression'], approaches: ['CBT', 'EMDR'], insurers: ['Aetna', 'Cigna / Evernorth'],
  slidingScale: false, packageAvail: false, aiShadow: true,
  days: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'], start: '09:00', end: '17:00',
  referralPrefs: [], collabStyle: [], ehrSystem: '', dataSharing: [],
  cultural: [], crisisPlan: false, crisisReferrals: false, crisisContact: '',
  ethical: [], workstyle: [],
  customSpecialties: [], customApproaches: [], customInsurers: [], networkRoles: [],
  demo: {
    pronouns: [], raceEthnicity: [], lgbtq: [],
    parentingStatus: [], religion: [], veteranStatus: [],
    clinicalSupervision: [],
  },
};
const f = reactive(savedProfile ? { ...defaultProfile, ...JSON.parse(savedProfile) } : defaultProfile);

// Deep-clone helper
function deepClone(obj) { return JSON.parse(JSON.stringify(obj)); }

// Snapshot at page-load (or after each save) — used for Discard
let initialState = deepClone(f);

function fullAssign(target, source) {
  // Remove keys not in source
  for (const key of Object.keys(target)) {
    if (!(key in source)) delete target[key];
  }
  // Assign all source keys
  for (const key in source) {
    if (Array.isArray(source[key])) {
      target[key] = [...source[key]];
    } else if (source[key] && typeof source[key] === 'object') {
      if (!target[key] || typeof target[key] !== 'object') target[key] = {};
      fullAssign(target[key], source[key]);
    } else {
      target[key] = source[key];
    }
  }
}

function saveChanges() {
  localStorage.setItem('providerProfile', JSON.stringify(f));
  initialState = deepClone(f);
  showToast('Profile saved', 'success');
}

function discardChanges() {
  fullAssign(f, initialState);
  showToast('Changes discarded', 'info');
}

const initials = computed(() => ((f.firstName[0] || '') + (f.lastName[0] || '')) || 'SJ');

// Profile completion — drives the banner below the edit header
const profileCompletion = computed(() => {
  const checks = [
    { done: !!photoUrl.value,              label: 'a profile photo' },
    { done: !!f.firstName?.trim(),         label: 'your first name' },
    { done: !!f.lastName?.trim(),          label: 'your last name' },
    { done: !!f.title?.trim(),             label: 'a professional title' },
    { done: !!f.bio?.trim(),               label: 'a professional bio' },
    { done: !!f.phone?.trim(),             label: 'a phone number' },
    { done: !!f.email?.trim(),             label: 'an email address' },
    { done: f.specialties?.length > 0,     label: 'at least one specialty' },
    { done: f.insurers?.length > 0,        label: 'insurance accepted' },
    { done: !!f.address?.trim(),           label: 'a practice address' },
  ];
  const total = checks.length;
  const done  = checks.filter(c => c.done).length;
  const remaining = total - done;
  const pct = Math.round((done / total) * 100);
  const incomplete = checks.filter(c => !c.done).map(c => c.label);
  const hint = incomplete.length ? 'add ' + incomplete.slice(0, 2).join(' and ') : '';
  return { pct, remaining, hint };
});

function toggleArr(arr, val) {
  const i = arr.indexOf(val);
  if (i === -1) arr.push(val); else arr.splice(i, 1);
}

function selectAllCategory(type, items) {
  const arr = type === 'specialties' ? f.specialties : f.approaches;
  items.forEach(item => {
    if (!arr.includes(item)) arr.push(item);
  });
}

function deselectAllCategory(type, items) {
  const arr = type === 'specialties' ? f.specialties : f.approaches;
  items.forEach(item => {
    const i = arr.indexOf(item);
    if (i !== -1) arr.splice(i, 1);
  });
}

const toasts = ref([]);
let tid = 0;
function showToast(msg, type = 'info') {
  const id = ++tid;
  toasts.value.push({ id, msg, type });
  setTimeout(() => { toasts.value = toasts.value.filter((t) => t.id !== id); }, 3000);
}
</script>

<style scoped>
/* ── Profile Edit Banner ──────────────────────────────────────── */
.ppc-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  margin-bottom: 12px;
  box-shadow: var(--shadow-xs);
}

.ppc-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  padding: 18px 22px;
}

/* Identity block */
.ppc-identity { min-width: 0; }

.ppc-eyebrow {
  font-family: var(--font-sans);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--gold-dark);
  margin-bottom: 3px;
}

.ppc-name {
  font-family: var(--font-serif);
  font-size: 22px;
  font-weight: 700;
  color: var(--text);
  line-height: 1.2;
  margin-bottom: 3px;
}

.ppc-meta-line {
  font-family: var(--font-sans);
  font-size: 13px;
  color: var(--text-3);
  margin-bottom: 8px;
}

.ppc-dot { color: var(--text-4); }

.ppc-saved-row {
  display: flex;
  align-items: center;
  gap: 16px;
}

.ppc-saved-item {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-family: var(--font-sans);
  font-size: 12px;
  color: var(--text-4);
}

/* Action buttons */
.ppc-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.ppc-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.ppc-save-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* ── Stat cards row — separate individual cards ───────────────── */
.ppc-stats {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 10px;
  margin-bottom: 18px;
}

.ppc-stat {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 16px 18px;
  cursor: pointer;
  transition: background 0.15s, transform 0.18s cubic-bezier(.22,.68,0,1.2), box-shadow 0.18s;
  box-shadow: var(--shadow-xs);
}

.ppc-stat:hover {
  background: var(--surface-2);
  transform: translateY(-3px);
  box-shadow: var(--shadow-sm);
}

.ppc-stat-value {
  font-family: var(--font-serif);
  font-size: 20px;
  font-weight: 700;
  color: var(--text);
  line-height: 1.2;
  display: flex;
  align-items: center;
  gap: 3px;
  margin-bottom: 4px;
}

.ppc-stat-label {
  font-family: var(--font-sans);
  font-size: 9.5px;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: var(--text-4);
}

.ppc-stat-green .ppc-stat-value { color: var(--green-dark); }
.ppc-stat-gold  .ppc-stat-value { color: var(--gold-dark); }

@media (max-width: 860px) {
  .ppc-stats { grid-template-columns: repeat(3, 1fr); }
  .ppc-stat:nth-child(3) { border-right: none; }
  .ppc-stat:nth-child(4), .ppc-stat:nth-child(5), .ppc-stat:nth-child(6) {
    border-top: 1px solid var(--border);
  }
}

@media (max-width: 540px) {
  .ppc-stats { grid-template-columns: repeat(2, 1fr); }
  .ppc-main  { flex-wrap: wrap; }
}

/* ── Completion banner ────────────────────────────────────────── */
.ep-completion-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 13px 18px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  margin-bottom: 14px;
  flex-wrap: wrap;
}
.ep-completion-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
.ep-completion-icon {
  flex-shrink: 0; width: 32px; height: 32px; border-radius: 8px;
  background: var(--surface-3); border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center; color: var(--gold-dark);
}
.ep-completion-title { font-family: var(--font-sans); font-size: 13px; font-weight: 600; color: var(--text); }
.ep-completion-sub   { font-family: var(--font-sans); font-size: 12px; color: var(--text-4); margin-top: 2px; }
.ep-completion-right { display: flex; align-items: center; gap: 14px; flex-shrink: 0; }
.ep-completion-bar-wrap { display: flex; align-items: center; gap: 9px; }
.ep-completion-bar-track {
  width: 130px; height: 5px; border-radius: 99px;
  background: var(--border); overflow: hidden;
}
.ep-completion-bar-fill {
  height: 100%; border-radius: 99px;
  background: var(--gold-dark); transition: width 0.4s ease;
}
.ep-completion-pct { font-family: var(--font-sans); font-size: 12px; font-weight: 600; color: var(--text-2); white-space: nowrap; }
.ep-completion-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 13px; font-family: var(--font-sans); font-size: 12px; font-weight: 600;
  background: var(--gold-dark); color: var(--text-inverted);
  border: none; border-radius: var(--radius-sm); cursor: pointer;
  white-space: nowrap; transition: background 0.15s;
}
.ep-completion-btn:hover { background: var(--gold); }

/* ── Unified portals notice (inside Basic Info pane) ─────────── */
.ep-unified-notice {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 11px 14px;
  background: var(--blue-light);
  border: 1px solid var(--soft-blue);
  border-radius: 8px;
  margin-bottom: 16px;
  font-family: var(--font-sans);
  font-size: 13px;
  color: var(--blue-dark);
  line-height: 1.5;
}
.ep-unified-notice svg { flex-shrink: 0; margin-top: 1px; }

.ep-layout { display: grid; grid-template-columns: 232px 1fr; gap: 22px; align-items: start; margin-bottom: 16px; }

/* Left nav rail */
.ep-nav {
  position: sticky; top: 84px;
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); padding: 12px; box-shadow: var(--shadow-xs);
  display: flex; flex-direction: column; gap: 2px;
}
.ep-nav-section-label { font-size: 9.5px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: var(--text-4); padding: 8px 10px 4px; }
.ep-nav-item {
  display: flex; align-items: center; gap: 11px;
  padding: 9px 11px; border-radius: var(--radius); cursor: pointer;
  font-size: 13px; font-weight: 500; color: var(--text-2); text-decoration: none;
  transition: background var(--transition), color var(--transition);
}
.ep-nav-item:hover { background: var(--surface-2); color: var(--text); }
.ep-nav-item.active { background: var(--badge-bg-gold); color: var(--gold-dark); font-weight: 700; }
.ep-nav-icon { display: inline-flex; color: currentColor; flex-shrink: 0; }
.ep-nav-icon :deep(svg) { display: block; }

/* Cards */
.ep-pane { display: flex; flex-direction: column; gap: 18px; }
.ep-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-xs); overflow: hidden; }
.ep-card-header { display: flex; align-items: center; gap: 13px; padding: 18px 22px; border-bottom: 1px solid var(--border); background: var(--surface-2); }
.ep-card-icon { width: 38px; height: 38px; flex-shrink: 0; border-radius: var(--radius-sm); background: var(--icon-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; }
.ep-card-title { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); }
.ep-card-sub { font-size: 12px; color: var(--text-3); margin-top: 2px; line-height: 1.4; }
.ep-card-body { padding: 20px 22px; }

/* Form layout helpers */
.form-group { margin-bottom: 16px; }
.form-group:last-child { margin-bottom: 0; }
.form-label { display: flex; align-items: center; font-size: 10.5px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: #333333; margin-bottom: 6px; }
.ep-pane-specialties .form-label { cursor: pointer; }
.ep-pane-specialties .form-label::before { content: '▼'; margin-right: 4px; font-size: 9px; }
.ep-pane-specialties .form-label[data-collapsed="true"]::before { content: '▶'; }
.form-label-no-arrow { cursor: default; }
.form-label-no-arrow::before { display: none; }
.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
.ep-req { color: var(--red); }
.ep-opt { font-weight: 400; letter-spacing: 0; text-transform: none; color: var(--text-4); }
.ep-hint { font-size: 11px; color: var(--text-4); margin-top: 6px; }

/* Photo */
.ep-photo-row { display: flex; align-items: center; gap: 18px; }
.ep-avatar { width: 72px; height: 72px; border-radius: 50%; background: var(--gold-dark); color: #fff; font-family: var(--font-serif); font-size: 26px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ep-photo-actions { display: flex; flex-direction: column; gap: 8px; align-items: flex-start; }

/* Website prefix */
.ep-input-prefix { display: flex; align-items: stretch; border: 1.5px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; }
.ep-input-prefix:focus-within { border-color: var(--gold); box-shadow: var(--focus-ring); }
.ep-input-prefix-label { display: flex; align-items: center; padding: 0 11px; background: var(--surface-2); color: var(--text-3); font-size: 13px; border-right: 1px solid var(--border); }
.ep-input-prefix :deep(.form-input) { border: none; border-radius: 0; box-shadow: none; }

/* Tags */
.ep-tags { display: flex; flex-wrap: wrap; gap: 8px; }
.ep-tag { font-size: 12.5px; font-weight: 500; color: var(--text-2); background: #ffffff; border: 1.5px solid #d4c3a8; border-radius: 9999px; padding: 6px 16px; cursor: pointer; transition: all var(--transition); user-select: none; }
.ep-tag:hover { border-color: #a0813e; }
.ep-tag.active { background: #a0813e; border-color: #a0813e; color: #ffffff; font-weight: 600; }

/* Toggle rows */
.ep-toggle-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--surface-3); }
.ep-toggle-row:last-child { border-bottom: none; }
.ep-toggle-label { font-size: 13px; font-weight: 500; color: var(--text-2); }
.ep-switch { width: 40px; height: 22px; border-radius: var(--radius-full); background: var(--border-dark); position: relative; cursor: pointer; transition: background var(--transition); flex-shrink: 0; }
.ep-switch.on { background: var(--gold); }
.ep-switch-dot { position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; border-radius: 50%; background: #fff; transition: transform var(--transition); box-shadow: var(--shadow-xs); }
.ep-switch.on .ep-switch-dot { transform: translateX(18px); }

/* Dropzone */
.ep-dropzone { display: flex; align-items: center; justify-content: center; gap: 9px; border: 1.5px dashed var(--border-dark); border-radius: var(--radius-sm); padding: 16px; font-size: 12.5px; color: var(--text-3); cursor: pointer; transition: all var(--transition); }
.ep-dropzone:hover { border-color: var(--gold); background: var(--surface-2); color: var(--gold-dark); }

/* Save bar */
.ep-save-bar { position: sticky; bottom: 0; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 22px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow); flex-wrap: wrap; }
.ep-save-meta { display: inline-flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--text-3); }
.ep-save-actions { display: flex; gap: 10px; }

/* Toasts */
.dh-toast-stack { position: fixed; bottom: 22px; right: 22px; z-index: 4000; display: flex; flex-direction: column; gap: 10px; }
.dh-toast { display: flex; align-items: center; gap: 9px; padding: 11px 16px; border-radius: var(--radius); background: var(--text); color: var(--text-inverted); font-size: 13px; font-weight: 600; box-shadow: var(--shadow-lg); max-width: 360px; }
.dh-toast.success { background: var(--green-dark); }
.dh-toast svg { flex-shrink: 0; }

/* Responsive */
@media (max-width: 980px) {
  .ep-layout { grid-template-columns: 1fr; }
  .ep-nav { position: static; flex-direction: row; flex-wrap: wrap; }
  .ep-nav-section-label { display: none; }
  .ep-nav-item { flex: 0 0 auto; }
}
@media (max-width: 640px) {
  .form-row-2, .form-row-3 { grid-template-columns: 1fr; }
}
</style>
