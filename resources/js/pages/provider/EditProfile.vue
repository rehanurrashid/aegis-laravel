<!--
  pages/provider/EditProfile.vue — practitioner profile editor.
  Replicates edit-profile.php's 8-section in-page nav exactly:
    1. Basic Info   2. Professional   3. Specialties   4. Insurance & Fees
    5. Network      6. Licensing      7. Demographics  8. Availability

  Backend contract (verified against controllers/services/migrations):
    Section              | Route                              | Verb            | Backend
    ----------------------|-------------------------------------|-----------------|---------------------------------
    Identity/Contact     | provider.profile.basic              | PUT             | ProfileService::updateBasic (users table)
    Languages/Website    | provider.profile.languages           | PUT             | ProfileService::updateLanguagesAndWebsite (user_meta)
    Specialties          | provider.profile.specialties         | PUT             | ProfileService::updateSpecialties (user_meta)
    Services             | provider.profile.services            | PUT             | ProfileService::updateServices (user_meta)
    Approaches           | provider.profile.approaches          | PUT             | ProfileService::updateApproaches (user_meta)
    Licenses & Insurance | provider.credentials.store/update/destroy | POST/PUT/DELETE | ProviderCredentialController (provider_credentials table — real CRUD model)
    Fees & insurance     | provider.profile.fees                | PUT             | ProfileService::updateFees (user_meta)
    Licensed states      | provider.profile.licensed-states     | PUT             | ProfileService::updateLicensedStates (user_meta)
    Education            | provider.profile.education           | PUT             | ProfileService::updateEducation (user_meta)
    Network partners     | provider.profile.network-partners    | PUT             | ProfileService::updateNetworkPartners (user_meta)
    AI/Shadow settings   | provider.profile.ai-settings         | PUT             | ProfileService::updateAiSettings (user_meta)
    Demographics         | provider.profile.demographics        | PUT             | ProfileService::updateDemographics (user_meta)
    Availability         | provider.profile.availability        | PUT             | ProfileService::updateAvailability (user_meta)
    Visibility            | provider.profile.privacy             | PUT             | ProfileService::updatePrivacy (users table)

  License document upload uses <AegisDropzone> + forceFormData, posting
  directly to provider.credentials.store / .update (multipart, real file
  storage) — not a fake client-side base64 preview like the PHP prototype.
-->
<template>
  <AppLayout>

    <!-- ══════════════ HERO ══════════════ -->
    <div class="hero-banner is-quiet">
      <div class="page-hero-inner">
        <div class="page-hero-left">
          <div class="page-hero-eyebrow">Practitioner Profile</div>
          <div class="page-hero-title">Edit Profile</div>
          <div class="page-hero-sub">
            {{ user.display_name }}<template v-if="user.credentials">, {{ user.credentials }}</template>
            &nbsp;·&nbsp; {{ user.organization }}
          </div>
          <div class="hero-meta">
            <span v-if="user.updated_at" class="hero-meta-item">
              <AegisIcon name="check" :size="14" />
              Saved {{ lastSavedLabel }}
            </span>
          </div>
        </div>
        <div class="page-hero-actions">
          <a v-if="user.slug" :href="route('public.provider', { slug: user.slug })" class="btn-hero-ghost is-on-light">
            <AegisIcon name="eye" :size="14" />
            Preview
          </a>
        </div>
      </div>
    </div>

    <!-- ══════════════ PROFILE COMPLETION STRIP ══════════════ -->
    <ProfileCompletionStrip
      :pct="props.profileCompletion"
      :subtitle="props.profileCompletion >= 100
        ? 'Your profile is complete'
        : `${props.profileItemsRemaining} section${props.profileItemsRemaining !== 1 ? 's' : ''} remaining — complete your profile to improve discovery`"
    >
      <template #action>
        <button type="button" class="btn btn-primary" style="flex-shrink:0;white-space:nowrap" @click="scrollToFirstIncomplete">
          Complete <AegisIcon name="arrow-right-line" :size="12" />
        </button>
      </template>
    </ProfileCompletionStrip>

    <!-- ══════════════ TWO-COLUMN LAYOUT ══════════════ -->
    <div class="ep-layout">

      <!-- ─── LEFT NAV ─── -->
      <div class="page-sidebar ep-nav-wrap">
        <div class="page-sidebar-group">
          <div class="page-sidebar-label">Profile</div>
          <a v-for="item in mainNavItems" :key="item.key"
             class="page-sidebar-item" :class="{ active: activeSection === item.key }"
             href="#" @click.prevent="activeSection = item.key">
            <span class="page-sidebar-icon"><AegisIcon :name="item.icon" :size="15" /></span>
            <span style="flex:1;line-height:1.3;">{{ item.label }}</span>
            <span class="ep-nav-check">
              <AegisIcon
                :name="sectionCompletion[item.key] ? 'check-badge' : 'circle'"
                :size="15"
                :class="sectionCompletion[item.key] ? 'aegis-icon-filled' : 'aegis-icon-empty'"
              />
            </span>
          </a>
        </div>
      </div><!-- /.ep-nav-wrap -->

      <!-- ─── RIGHT CONTENT ─── -->
      <div>

        <!-- ══════════════ 1. BASIC INFO ══════════════ -->
        <div v-show="activeSection === 'basic-info'" class="ep-section active">

          <div class="alert alert-info" style="margin-bottom:20px">
            <div class="alert-icon"><AegisIcon name="users" :size="16" /></div>
            <div class="alert-content">
              <strong>Unified across all portals</strong> — Your photo, display name, email, and phone apply to every portal you have access to (Practitioner, Continuity Steward, Support Steward). Changes here update your identity platform-wide.
            </div>
          </div>

          <!-- Photo -->
          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="camera" :size="16" /></div>
                <div>
                  <div class="ep-card-title">Profile Photo</div>
                  <div class="ep-card-sub">Shown to network partners and on your public profile</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="ep-avatar-row">
                <div class="ep-avatar-preview" :style="user.avatar_url ? { backgroundImage: `url(${user.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}">
                  <template v-if="!user.avatar_url">{{ user.avatar_initials || initialsFromName }}</template>
                </div>
                <div class="ep-avatar-info">
                  <h4>{{ user.display_name }}<span v-if="user.credentials" style="font-family:var(--font-sans);font-size:13px;font-weight:600;color:var(--text-3)">, {{ user.credentials }}</span></h4>
                  <p>{{ user.avatar_url ? 'Photo uploaded' : 'No photo uploaded — initials shown as placeholder' }}</p>
                  <p style="font-size:11px;color:var(--text-4);margin-top:2px">JPG, PNG or WebP · Max 5 MB · Recommended 400×400 px</p>
                  <div class="ep-avatar-btns">
                    <button type="button" class="btn btn-primary" @click="modals.photoUpload = true">
                      <AegisIcon name="upload" :size="12" /> Upload Photo
                    </button>
                    <button v-if="user.avatar_url" type="button" class="btn btn-outline" @click="modals.removePhoto = true">Remove</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Basic Info -->
          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="user" :size="16" /></div>
                <div>
                  <div class="ep-card-title">Basic Information</div>
                  <div class="ep-card-sub">Appears on your Aegis public profile</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="form-row form-row-2">
                <div class="form-group">
                  <label class="form-label">First Name <span class="ep-label-req">*</span></label>
                  <input v-model="firstName" type="text"
                    :class="['form-input', { 'is-error': fieldError(vBasic, 'firstName') }]"
                    placeholder="First name"
                    @blur="vBasic.value.firstName.$touch()">
                  <div v-if="fieldError(vBasic, 'firstName')" class="form-error">{{ fieldError(vBasic, 'firstName') }}</div>
                </div>
                <div class="form-group">
                  <label class="form-label">Last Name <span class="ep-label-req">*</span></label>
                  <input v-model="lastName" type="text"
                    :class="['form-input', { 'is-error': fieldError(vBasic, 'lastName') }]"
                    placeholder="Last name"
                    @blur="vBasic.value.lastName.$touch()">
                  <div v-if="fieldError(vBasic, 'lastName')" class="form-error">{{ fieldError(vBasic, 'lastName') }}</div>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Credentials / Suffix</label>
                <input v-model="basicForm.credentials" type="text" class="form-input" placeholder="e.g. MD, PhD, LCSW, LMFT">
                <div class="form-help">Appears after your name: {{ user.display_name }}, <strong>{{ basicForm.credentials || 'MD' }}</strong></div>
              </div>

              <div class="form-group">
                <label class="form-label">Professional Title <span class="ep-label-req">*</span></label>
                <input v-model="basicForm.title" type="text"
                  :class="['form-input', { 'is-error': fieldError(vBasic, 'basicForm', 'title') }]"
                  placeholder="e.g. Board-Certified Psychiatrist"
                  @blur="vBasic.value.basicForm.title.$touch()">
                <div v-if="fieldError(vBasic, 'basicForm', 'title')" class="form-error">{{ fieldError(vBasic, 'basicForm', 'title') }}</div>
              </div>

              <div class="form-group">
                <label class="form-label">Practice / Affiliation</label>
                <input v-model="basicForm.organization" type="text" class="form-input">
              </div>

              <div class="form-group form-group-last">
                <label class="form-label">Professional Bio <span class="ep-label-req">*</span></label>
                <textarea v-model="basicForm.bio"
                  :class="['form-textarea', { 'is-error': fieldError(vBasic, 'basicForm', 'bio') }]"
                  rows="5" maxlength="500"
                  @blur="vBasic.value.basicForm.bio.$touch()"></textarea>
                <div class="form-char">{{ basicForm.bio.length }} / 500</div>
                <div v-if="fieldError(vBasic, 'basicForm', 'bio')" class="form-error">{{ fieldError(vBasic, 'basicForm', 'bio') }}</div>
                <div v-else class="form-help">Write a 2–4 sentence bio. Be specific about your approach and client population.</div>
              </div>

              <div class="form-actions-bar">
                <button type="button" class="btn btn-primary" :disabled="basicForm.processing" @click="submitBasic">
                  {{ basicForm.processing ? 'Saving…' : 'Save basic info' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Contact -->
          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="map-pin" :size="16" /></div>
                <div>
                  <div class="ep-card-title">Contact &amp; Location</div>
                  <div class="ep-card-sub">Primary practice location shown to network partners</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Location <span class="ep-label-req">*</span></label>
                <input v-model="basicForm.location" type="text"
                  :class="['form-input', { 'is-error': fieldError(vBasic, 'basicForm', 'location') }]"
                  placeholder="City, State"
                  @blur="vBasic.value.basicForm.location.$touch()">
                <div v-if="fieldError(vBasic, 'basicForm', 'location')" class="form-error">{{ fieldError(vBasic, 'basicForm', 'location') }}</div>
              </div>
              <div class="form-row form-row-2">
                <div class="form-group">
                  <label class="form-label">Phone <span class="ep-label-req">*</span></label>
                  <input v-model="basicForm.phone" type="tel"
                    :class="['form-input', { 'is-error': fieldError(vBasic, 'basicForm', 'phone') }]"
                    @blur="vBasic.value.basicForm.phone.$touch()">
                  <div v-if="fieldError(vBasic, 'basicForm', 'phone')" class="form-error">{{ fieldError(vBasic, 'basicForm', 'phone') }}</div>
                </div>
                <div class="form-group">
                  <label class="form-label">Avatar initials</label>
                  <input v-model="basicForm.avatar_initials" type="text" class="form-input" maxlength="4">
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input :value="user.email" type="email" class="form-input" disabled data-tooltip="Email is managed in Settings → Account">
              </div>
              <div class="form-group">
                <label class="form-label">Website <span class="ep-label-opt">(Optional)</span></label>
                <div :class="['ep-input-prefix', { 'is-error': fieldError(vWebsite, 'websiteForm', 'website') }]">
                  <span class="ep-input-prefix-label">https://</span>
                  <input v-model="websiteForm.website" type="text" class="form-input" placeholder="yoursite.com"
                    @blur="vWebsite.value.websiteForm.website.$touch()">
                </div>
                <div v-if="fieldError(vWebsite, 'websiteForm', 'website')" class="form-error">{{ fieldError(vWebsite, 'websiteForm', 'website') }}</div>
              </div>
              <div class="form-group form-group-last">
                <label class="form-label">Languages Spoken</label>
                <div class="ep-tags">
                  <div v-for="lang in languageOptions" :key="lang"
                       class="ep-tag" :class="{ active: websiteForm.languages.includes(lang) }"
                       @click="toggleInArray(websiteForm.languages, lang)">{{ lang }}</div>
                </div>
                <div style="display:flex;gap:8px;margin-top:8px;align-items:center">
                  <input v-model="customLanguageInput" type="text" class="form-input" placeholder="Add other language…" style="flex:1;font-size:13px" @keydown.enter.prevent="addCustomLanguage">
                  <button type="button" class="btn btn-outline" style="white-space:nowrap;flex-shrink:0" @click="addCustomLanguage">+ Add</button>
                </div>
              </div>
              <div class="form-actions-bar">
                <button type="button" class="btn btn-primary" :disabled="websiteForm.processing" @click="submitLanguages">
                  {{ websiteForm.processing ? 'Saving…' : 'Save contact details' }}
                </button>
              </div>
            </div>
          </div>

        </div><!-- /basic-info -->

        <!-- ══════════════ 2. PROFESSIONAL ══════════════ -->
        <div v-show="activeSection === 'professional'" class="ep-section">

          <!-- Licenses (real CRUD against provider_credentials) -->
          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="file-text" :size="16" /></div>
                <div>
                  <div class="ep-card-title">License</div>
                  <div class="ep-card-sub">Add all active state licenses. Primary license appears first.</div>
                </div>
              </div>
            </div>
            <div class="card-body">

              <AegisEmptyState v-if="!licenseCredentials.length" icon="file-text" title="No licenses on file" description="Add your state license to strengthen your public profile." />

              <div v-for="(cred, idx) in licenseCredentials" :key="cred.id" class="ep-cred" :class="{ primary: idx === 0, 'is-archived': cred.archived }">
                <div class="ep-cred-top">
                  <div class="ep-cred-title">
                    <AegisIcon name="flask" :size="14" />
                    {{ cred.name || cred.cred_type || 'License' }}
                    <span class="ep-cred-badge">{{ cred.subtitle || cred.issuer || '—' }}</span>
                  </div>
                  <div style="display:flex;gap:6px;flex-shrink:0;align-items:center">
                    <button type="button" class="btn-icon" data-tooltip="Edit / Renew" @click="openCredModal('edit-credential', cred)"><AegisIcon name="edit" :size="13" /></button>
                    <button type="button" class="btn-icon" data-tooltip="Set Reminder" @click="openCredModal('reminder', cred)"><AegisIcon name="bell" :size="13" /></button>
                    <button type="button" class="btn-icon" :data-tooltip="cred.archived ? 'Restore' : 'Archive'" @click="toggleArchiveCredential(cred)"><AegisIcon :name="cred.archived ? 'refresh-cw' : 'archive'" :size="13" /></button>
                    <button type="button" class="btn-icon btn-icon-danger" data-tooltip="Remove" @click="confirmRemoveCredential(cred)"><AegisIcon name="trash-2" :size="13" /></button>
                  </div>
                </div>
                <div class="ep-cred-meta-row">
                  <div class="ep-cred-meta-item"><span>License #</span><strong>{{ cred.number || '—' }}</strong></div>
                  <div class="ep-cred-meta-item"><span>Issuer</span><strong>{{ cred.issuer || '—' }}</strong></div>
                  <div class="ep-cred-meta-item"><span>Issued</span><strong>{{ cred.issued_on || '—' }}</strong></div>
                  <div class="ep-cred-meta-item" :class="{ 'is-expiry-warn': cred.days_remaining !== null && cred.days_remaining < 30 }">
                    <span>Expires</span><strong>{{ cred.expires_on || '—' }}</strong>
                  </div>
                </div>
                <div v-if="cred.document_paths?.length" style="margin-top:10px;display:flex;flex-direction:column;gap:6px">
                  <div v-for="p in cred.document_paths" :key="p" class="ep-file-row">
                    <div class="ep-file-info">
                      <div class="ep-file-icon"><AegisIcon name="file-text" :size="16" /></div>
                      <div>
                        <div class="ep-file-name">{{ documentName(p) }}</div>
                        <div class="ep-file-meta">Uploaded · on file</div>
                      </div>
                    </div>
                    <div style="display:flex;gap:6px;flex-shrink:0">
                      <a :href="`/storage/${p}`" target="_blank" rel="noopener" class="btn-icon" data-tooltip="Preview"><AegisIcon name="eye" :size="13" /></a>
                      <a :href="route('provider.credentials.download', cred.id) + '?path=' + encodeURIComponent(p)" class="btn-icon" data-tooltip="Download"><AegisIcon name="download" :size="13" /></a>
                      <button type="button" class="btn-icon btn-icon-danger" data-tooltip="Remove file" @click="removeCredentialDoc(cred, p)"><AegisIcon name="x" :size="11" /></button>
                    </div>
                  </div>
                </div>
              </div>

              <button type="button" class="btn btn-outline" @click="modals.addLicense = true">
                <AegisIcon name="plus" :size="13" /> Add State License
              </button>

              <div class="alert alert-info" style="margin-top:16px">
                <div class="alert-icon"><AegisIcon name="info" :size="14" /></div>
                <div>
                  <strong>CEU Requirements:</strong> Set per-state CEU requirements (hours, types, renewal cycle) and log completed credits on your
                  <a :href="route('provider.dashboard')" style="color:var(--gold-dark);font-weight:600;text-decoration:none">Continuity Dashboard</a> → CEU Tracker.
                </div>
              </div>
            </div>
          </div>

          <!-- Credentials & Certifications (tag picker → user_meta approaches-style; saved alongside specialties) -->
          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="award" :size="16" /></div>
                <div>
                  <div class="ep-card-title">Credentials &amp; Certifications</div>
                  <div class="ep-card-sub">Primary credential type and board certifications</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div v-for="group in credentialTagGroups" :key="group.label" class="form-group">
                <div class="ep-cat" style="margin-bottom:6px;font-size:11px">{{ group.label }}</div>
                <div class="ep-tags" style="margin-bottom:10px">
                  <div v-for="opt in group.options" :key="opt"
                       class="ep-tag" :class="{ active: specialtiesForm.specialties.includes(opt) }"
                       @click="toggleInArray(specialtiesForm.specialties, opt)">{{ opt }}</div>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Other Certifications <span class="ep-label-opt">(Optional)</span></label>
                <input v-model="customCertInput" type="text" class="form-input" placeholder="e.g. EMDR Level II, Gottman Level 3, CIMHP…" @keydown.enter.prevent="addCustomCert">
                <div class="form-help">Enter any certifications not listed above</div>
              </div>

              <div class="form-actions-bar">
                <button type="button" class="btn btn-primary" :disabled="specialtiesForm.processing" @click="submitSpecialties">
                  {{ specialtiesForm.processing ? 'Saving…' : 'Save credentials' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Liability Insurance (real CRUD against provider_credentials) -->
          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="shield" :size="16" /></div>
                <div>
                  <div class="ep-card-title">Liability Insurance</div>
                  <div class="ep-card-sub">Malpractice certificate — boosts profile trust score</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div v-if="!insuranceCredential" class="alert alert-warning" style="margin-bottom:16px">
                <div class="alert-icon"><AegisIcon name="alert-triangle" :size="14" /></div>
                <div class="alert-content">No certificate on file. Adding one increases your profile strength by <strong>12%</strong>.</div>
              </div>

              <template v-if="insuranceCredential">
                <div class="ep-cred">
                  <div class="ep-cred-top">
                    <div class="ep-cred-title">
                      <AegisIcon name="shield" :size="14" />
                      {{ insuranceCredential.cred_type || 'Insurance Policy' }}
                      <span class="ep-cred-badge">{{ insuranceCredential.issuer || '—' }}</span>
                    </div>
                    <div style="display:flex;gap:6px;flex-shrink:0;align-items:center">
                      <button type="button" class="btn-icon" data-tooltip="Edit / Renew" @click="openCredModal('edit-insurance', insuranceCredential)"><AegisIcon name="edit" :size="13" /></button>
                      <button type="button" class="btn-icon" data-tooltip="Set Reminder" @click="openCredModal('reminder', insuranceCredential)"><AegisIcon name="bell" :size="13" /></button>
                      <button type="button" class="btn-icon btn-icon-danger" data-tooltip="Remove" @click="confirmRemoveCredential(insuranceCredential)"><AegisIcon name="trash-2" :size="13" /></button>
                    </div>
                  </div>
                  <div class="ep-cred-meta-row">
                    <div class="ep-cred-meta-item"><span>Policy #</span><strong>{{ insuranceCredential.number || '—' }}</strong></div>
                    <div class="ep-cred-meta-item"><span>Coverage</span><strong>{{ insuranceCredential.name || insuranceCredential.subtitle || '—' }}</strong></div>
                    <div class="ep-cred-meta-item"><span>Effective</span><strong>{{ insuranceCredential.issued_on || '—' }}</strong></div>
                    <div class="ep-cred-meta-item" :class="{ 'is-expiry-warn': insuranceCredential.days_remaining !== null && insuranceCredential.days_remaining < 30 }">
                      <span>Expires</span><strong>{{ insuranceCredential.expires_on || '—' }}</strong>
                    </div>
                  </div>
                  <div v-if="insuranceCredential.document_paths?.length" style="margin-top:10px;display:flex;flex-direction:column;gap:6px">
                    <div v-for="p in insuranceCredential.document_paths" :key="p" class="ep-file-row">
                      <div class="ep-file-info">
                        <div class="ep-file-icon"><AegisIcon name="file-text" :size="16" /></div>
                        <div>
                          <div class="ep-file-name">{{ documentName(p) }}</div>
                          <div class="ep-file-meta">Uploaded · on file</div>
                        </div>
                      </div>
                      <div style="display:flex;gap:6px;flex-shrink:0">
                        <a :href="`/storage/${p}`" target="_blank" rel="noopener" class="btn-icon" data-tooltip="Preview"><AegisIcon name="eye" :size="13" /></a>
                        <a :href="route('provider.credentials.download', insuranceCredential.id) + '?path=' + encodeURIComponent(p)" class="btn-icon" data-tooltip="Download"><AegisIcon name="download" :size="13" /></a>
                        <button type="button" class="btn-icon btn-icon-danger" data-tooltip="Remove file" @click="removeCredentialDoc(insuranceCredential, p)"><AegisIcon name="x" :size="11" /></button>
                      </div>
                    </div>
                  </div>
                </div>
              </template>

              <button type="button" class="btn btn-outline" style="margin-top:4px" @click="openAddInsurance">
                <AegisIcon name="plus" :size="13" /> {{ insuranceCredential ? 'Add Another Policy' : 'Add Insurance Policy' }}
              </button>
            </div>
          </div>

          <!-- Education -->
          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="graduation-cap" :size="16" /></div>
                <div>
                  <div class="ep-card-title">Education &amp; Training</div>
                  <div class="ep-card-sub">Academic degrees, residencies, fellowships and continuing education</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div v-for="(entry, idx) in educationForm.education" :key="idx" class="form-row form-row-2 ep-edu-grid" style="padding-bottom:14px;">
                <div class="form-group">
                  <label class="form-label">Degree / Credential</label>
                  <input v-model="entry.degree" type="text" class="form-input" placeholder="e.g. MD, PhD, MSW, BS">
                </div>
                <div class="form-group">
                  <label class="form-label">Field of Study</label>
                  <input v-model="entry.field" type="text" class="form-input" placeholder="e.g. Clinical Psychology">
                </div>
                <div class="form-group">
                  <label class="form-label">Institution</label>
                  <input v-model="entry.institution" type="text" class="form-input" placeholder="School or program name">
                </div>
                <div class="form-group" style="display:flex;gap:8px;align-items:flex-end">
                  <div style="flex:1">
                    <label class="form-label">Duration</label>
                    <input v-model="entry.duration" type="text" class="form-input" placeholder="e.g. 4 years">
                  </div>
                  <button type="button" class="btn-icon btn-icon-danger" data-tooltip="Remove entry" @click="confirmAction('Remove this education entry?', () => educationForm.education.splice(idx, 1), { title: 'Remove Entry', btnLabel: 'Remove', type: 'danger' })">
                    <AegisIcon name="trash" :size="14" />
                  </button>
                </div>
              </div>
              <button type="button" class="btn btn-outline" @click="educationForm.education.push({ degree: '', field: '', institution: '', duration: '' })">
                <AegisIcon name="plus" :size="13" /> Add Education / Training
              </button>
              <div class="form-actions-bar">
                <button type="button" class="btn btn-primary" :disabled="educationForm.processing" @click="submitEducation">
                  {{ educationForm.processing ? 'Saving…' : 'Save education' }}
                </button>
              </div>
            </div>
          </div>

        </div><!-- /professional -->

        <!-- ══════════════ 3. SPECIALTIES ══════════════ -->
        <div v-show="activeSection === 'specialties'" class="ep-section">

          <!-- Services card -->
          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="star" :size="16" /></div>
                <div>
                  <div class="ep-card-title">Services</div>
                  <div class="ep-card-sub">Select all services you offer — tap a group to expand</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div v-for="group in serviceTagGroups" :key="group.label" class="ep-accordion">
                <button type="button" class="ep-accordion-header" @click="toggleAccordion('svc_' + group.label)">
                  <span>{{ group.label }}</span>
                  <AegisIcon :name="openAccordions.has('svc_' + group.label) ? 'chevron-up' : 'chevron-down'" :size="14" class="ep-accordion-chevron" />
                </button>
                <transition name="ep-accordion">
                  <div v-show="openAccordions.has('svc_' + group.label)" class="ep-accordion-body">
                    <div class="ep-tags">
                      <div v-for="opt in group.options" :key="opt"
                           class="ep-tag" :class="{ active: servicesForm.services.includes(opt) }"
                           @click="toggleInArray(servicesForm.services, opt)">{{ opt }}</div>
                    </div>
                  </div>
                </transition>
              </div>
              <div class="ep-cat" style="margin-top:14px">Add Custom Service</div>
              <div style="display:flex;gap:8px;align-items:center">
                <input v-model="customServiceInput" type="text" class="form-input" placeholder="Enter a service not listed…" style="flex:1;font-size:13px" @keydown.enter.prevent="addCustomService">
                <button type="button" class="btn btn-outline" style="white-space:nowrap;flex-shrink:0" @click="addCustomService">+ Add</button>
              </div>
              <div class="form-actions-bar">
                <button type="button" class="btn btn-primary" :disabled="servicesForm.processing" @click="submitServices">
                  {{ servicesForm.processing ? 'Saving…' : 'Save services' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Specialties card -->
          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="bookmark" :size="16" /></div>
                <div>
                  <div class="ep-card-title">Specialties</div>
                  <div class="ep-card-sub">Select all specialty areas you work in — tap a group to expand</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div v-for="group in specialtyTagGroups" :key="group.label" class="ep-accordion">
                <button type="button" class="ep-accordion-header" @click="toggleAccordion('sp_' + group.label)">
                  <span>{{ group.label }}</span>
                  <AegisIcon :name="openAccordions.has('sp_' + group.label) ? 'chevron-up' : 'chevron-down'" :size="14" class="ep-accordion-chevron" />
                </button>
                <transition name="ep-accordion">
                  <div v-show="openAccordions.has('sp_' + group.label)" class="ep-accordion-body">
                    <div class="ep-tags">
                      <div v-for="opt in group.options" :key="opt"
                           class="ep-tag" :class="{ active: specialtiesForm.specialties.includes(opt) }"
                           @click="toggleInArray(specialtiesForm.specialties, opt)">{{ opt }}</div>
                    </div>
                  </div>
                </transition>
              </div>
              <div class="ep-cat" style="margin-top:14px">Add Custom Specialty</div>
              <div style="display:flex;gap:8px;align-items:center">
                <input v-model="customSpecialtyInput" type="text" class="form-input" placeholder="Enter a specialty not listed…" style="flex:1;font-size:13px" @keydown.enter.prevent="addCustomSpecialty">
                <button type="button" class="btn btn-outline" style="white-space:nowrap;flex-shrink:0" @click="addCustomSpecialty">+ Add</button>
              </div>
              <div class="form-actions-bar">
                <button type="button" class="btn btn-primary" :disabled="specialtiesForm.processing" @click="submitSpecialties">
                  {{ specialtiesForm.processing ? 'Saving…' : 'Save specialties' }}
                </button>
              </div>
            </div>
          </div>

          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="flask" :size="16" /></div>
                <div>
                  <div class="ep-card-title">Approaches &amp; Frameworks</div>
                  <div class="ep-card-sub">Methods and frameworks you use — tap headers to expand sections</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div v-for="(group, idx) in approachTagGroups" :key="group.label" class="ep-accordion">
                <button type="button" class="ep-accordion-header" @click="toggleAccordion('ap_' + group.label)">
                  <span>{{ group.label }}</span>
                  <AegisIcon :name="openAccordions.has('ap_' + group.label) ? 'chevron-up' : 'chevron-down'" :size="14" class="ep-accordion-chevron" />
                </button>
                <transition name="ep-accordion">
                  <div v-show="openAccordions.has('ap_' + group.label)" class="ep-accordion-body">
                    <div class="ep-tags">
                      <div v-for="opt in group.options" :key="opt"
                           class="ep-tag" :class="{ active: approachesForm.approaches.includes(opt) }"
                           @click="toggleInArray(approachesForm.approaches, opt)">{{ opt }}</div>
                    </div>
                  </div>
                </transition>
              </div>

              <div class="form-group" style="margin-bottom:0;margin-top:4px">
                <label class="form-label">Add Custom Approach</label>
                <div style="display:flex;gap:8px;align-items:center">
                  <input v-model="customApproachInput" type="text" class="form-input" placeholder="Enter approach or framework not listed…" style="flex:1;font-size:13px" @keydown.enter.prevent="addCustomApproach">
                  <button type="button" class="btn btn-outline" style="white-space:nowrap;flex-shrink:0" @click="addCustomApproach">+ Add</button>
                </div>
              </div>

              <div class="form-actions-bar">
                <button type="button" class="btn btn-primary" :disabled="approachesForm.processing" @click="submitApproaches">
                  {{ approachesForm.processing ? 'Saving…' : 'Save approaches' }}
                </button>
              </div>
            </div>
          </div>

        </div><!-- /specialties -->

        <!-- ══════════════ 4. INSURANCE & FEES ══════════════ -->
        <div v-show="activeSection === 'insurance'" class="ep-section">

          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="credit-card" :size="16" /></div>
                <div>
                  <div class="ep-card-title">Insurance Accepted</div>
                  <div class="ep-card-sub">Select all plans you are in-network for</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div v-for="group in insuranceGroups" :key="group.label">
                <div class="ep-cat">{{ group.label }}</div>
                <div class="ep-ins-grid">
                  <div v-for="opt in group.options" :key="opt"
                       class="ep-ins-item" :class="{ checked: feesForm.insurance_types.includes(opt) }"
                       @click="toggleInArray(feesForm.insurance_types, opt)">
                    <div class="ep-ins-dot"></div>{{ opt }}
                  </div>
                </div>
              </div>
              <div style="display:flex;gap:8px;margin-top:12px;align-items:center">
                <input v-model="customInsuranceInput" type="text" class="form-input" placeholder="Add insurance not listed…" style="flex:1;font-size:13px" @keydown.enter.prevent="addCustomInsurance">
                <button type="button" class="btn btn-outline" style="white-space:nowrap;flex-shrink:0" @click="addCustomInsurance">+ Add</button>
              </div>
              <div class="form-actions-bar">
                <button type="button" class="btn btn-primary" :disabled="feesForm.processing" @click="submitInsurancePanels">
                  {{ feesForm.processing ? 'Saving…' : 'Save insurance panels' }}
                </button>
              </div>
            </div>
          </div>

        </div><!-- /insurance -->

        <!-- ══════════════ 5. NETWORK PREFERENCES ══════════════ -->
        <div v-show="activeSection === 'network'" class="ep-section">

          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="globe" :size="16" /></div>
                <div>
                  <div class="ep-card-title">Interdisciplinary Network</div>
                  <div class="ep-card-sub">Establish a care network with professionals from various fields for quick referrals and coordinated, holistic support. Form partnerships ahead of time to address future needs.</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="ep-tags">
                <div v-for="opt in interdisciplinaryOptions" :key="opt"
                     class="ep-tag" :class="{ active: networkPartnersForm.partners.includes(opt) }"
                     @click="toggleInArray(networkPartnersForm.partners, opt)">{{ opt }}</div>
              </div>
              <div class="form-actions-bar">
                <button type="button" class="btn btn-primary" :disabled="networkPartnersForm.processing" @click="submitNetworkPartners">
                  {{ networkPartnersForm.processing ? 'Saving…' : 'Save network preferences' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Licensing — additional states -->
          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="map" :size="16" /></div>
                <div>
                  <div class="ep-card-title">Additional Licensed States</div>
                  <div class="ep-card-sub">States where you hold active licenses beyond your primary state</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="ep-states">
                <div v-for="abbr in usStateAbbrs" :key="abbr"
                     class="ep-state" :class="{ selected: licensedStatesForm.states.includes(abbr) }"
                     @click="toggleInArray(licensedStatesForm.states, abbr)">{{ abbr }}</div>
              </div>
              <div class="form-actions-bar">
                <button type="button" class="btn btn-primary" :disabled="licensedStatesForm.processing" @click="submitLicensedStates">
                  {{ licensedStatesForm.processing ? 'Saving…' : 'Save licensed states' }}
                </button>
              </div>
            </div>
          </div>

          <!-- AI & Shadow Network -->
          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="settings" :size="16" /></div>
                <div>
                  <div class="ep-card-title">AI &amp; Shadow Network Settings</div>
                  <div class="ep-card-sub">Set permissions for AI to identify Shadow Partners, enabling you or your Continuity Steward to refer clients when needed.</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="form-row form-row-2">
                <div class="form-group">
                  <label class="form-label">AI Shadow Suggestions</label>
                  <select v-model="aiForm.suggestions_mode" class="form-select">
                    <option value="on">On — Show AI Suggestions</option>
                    <option value="paused">Paused</option>
                    <option value="off">Off</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Max Distance</label>
                  <select v-model="aiForm.max_distance" class="form-select">
                    <option value="5">5 miles</option>
                    <option value="10">10 miles</option>
                    <option value="25">25 miles</option>
                    <option value="50">50 miles</option>
                    <option value="none">No limit</option>
                  </select>
                </div>
              </div>
              <div class="ep-check-grid">
                <label class="ep-check-item"><input v-model="aiForm.allow_referral_patterns" type="checkbox"> Allow AI to suggest connections based on referral patterns</label>
                <label class="ep-check-item"><input v-model="aiForm.allow_demographics" type="checkbox"> Allow AI to suggest based on client demographics</label>
                <label class="ep-check-item"><input v-model="aiForm.allow_specialties" type="checkbox"> Allow AI to suggest based on my demographics, specialties and treatment populations</label>
                <label class="ep-check-item"><input v-model="aiForm.appear_in_suggestions" type="checkbox"> Appear in AI suggestions for other providers</label>
                <label class="ep-check-item"><input v-model="aiForm.show_in_directory" type="checkbox"> Show my profile in Aegis provider directory</label>
              </div>
              <div class="form-actions-bar">
                <button type="button" class="btn btn-primary" :disabled="aiForm.processing" @click="submitAiSettings">
                  {{ aiForm.processing ? 'Saving…' : 'Save AI settings' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Availability -->
          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="clock" :size="16" /></div>
                <div>
                  <div class="ep-card-title">Availability</div>
                  <div class="ep-card-sub">Set your available days, hours, and service delivery preferences</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Available Days</label>
                <div class="ep-days">
                  <button
                    v-for="day in weekDays" :key="day"
                    type="button"
                    class="ep-day"
                    :class="{ selected: availabilityForm.hours.days?.includes(day) }"
                    @click="toggleInArray(availabilityForm.hours.days, day)"
                  >{{ day }}</button>
                </div>
              </div>
              <div class="form-row form-row-2">
                <div class="form-group">
                  <label class="form-label">Start Time</label>
                  <input v-model="availabilityForm.hours.start" type="time" class="form-input">
                </div>
                <div class="form-group">
                  <label class="form-label">End Time</label>
                  <input v-model="availabilityForm.hours.end" type="time" class="form-input">
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Timezone</label>
                <select v-model="availabilityForm.hours.timezone" class="form-select">
                  <option>Eastern Time (EST)</option>
                  <option>Central Time (CST)</option>
                  <option>Mountain Time (MST)</option>
                  <option>Pacific Time (PST)</option>
                  <option>Alaska Time (AKST)</option>
                  <option>Hawaii Time (HST)</option>
                </select>
              </div>
              <div class="ep-divider"></div>
              <AegisToggle v-model="availabilityForm.accepting" label="Accepting new clients" />
              <AegisToggle v-model="availabilityForm.telehealth" label="Available via telehealth" />
              <div class="form-actions-bar">
                <button type="button" class="btn btn-primary" :disabled="availabilityForm.processing" @click="submitAvailability">
                  {{ availabilityForm.processing ? 'Saving…' : 'Save availability' }}
                </button>
              </div>
            </div>
          </div>

        </div><!-- /network -->

        <!-- ══════════════ DEMOGRAPHICS ══════════════ -->
        <div v-show="activeSection === 'demographics'" class="ep-section">
          <div class="ep-card">
            <div class="ep-card-header">
              <div class="ep-card-header-left">
                <div class="ep-card-icon"><AegisIcon name="user" :size="16" /></div>
                <div>
                  <div class="ep-card-title">Provider Identity &amp; Demographics</div>
                  <div class="ep-card-sub">Optional — helps clients find culturally aligned practitioners</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="alert alert-info">
                <AegisIcon name="alert-circle" :size="15" />
                This information is entirely optional and private by default. You control what appears publicly.
              </div>

              <div v-for="field in demographicFields" :key="field.key" class="form-group" :class="{ 'form-group-last': field.key === demographicFields[demographicFields.length - 1].key }">
                <label class="form-label">{{ field.label }}</label>
                <div class="ep-tags">
                  <div v-for="opt in field.options" :key="opt"
                       class="ep-tag" :class="{ active: demographicsForm[field.key].includes(opt) }"
                       @click="toggleInArray(demographicsForm[field.key], opt)">{{ opt }}</div>
                </div>
              </div>

              <div class="form-actions-bar">
                <button type="button" class="btn btn-primary" :disabled="demographicsForm.processing" @click="submitDemographics">
                  {{ demographicsForm.processing ? 'Saving…' : 'Save demographics' }}
                </button>
              </div>
            </div>
          </div>
        </div><!-- /demographics -->



        <!-- Notifications and Privacy & Visibility are managed in Settings, per PHP design (lines: "→ managed in Settings"). -->

      </div><!-- /right content -->
    </div><!-- /.ep-layout -->
    <!-- ══════════════ PHOTO UPLOAD MODAL ══════════════ -->
    <AegisModal v-model="modals.photoUpload" title="Upload Profile Photo" size="md">
      <AegisDropzone accept="image/*" hint="JPG, PNG or WebP · Max 5 MB · Recommended 400×400px" @files="(files) => handleAvatarUpload(files[0])" />
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.photoUpload = false">Cancel</button>
      </template>
    </AegisModal>

    <!-- ══════════════ REMOVE PHOTO MODAL ══════════════ -->
    <AegisModal v-model="modals.removePhoto" title="Remove Profile Photo" size="sm">
      <p style="font-size:13px;color:var(--text-2);line-height:1.6">Your profile photo will be removed and your initials will be shown instead. This cannot be undone.</p>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.removePhoto = false">Cancel</button>
        <button type="button" class="btn btn-danger" @click="confirmRemovePhoto">Yes, Remove</button>
      </template>
    </AegisModal>

    <!-- ══════════════ ADD LICENSE MODAL ══════════════ -->
    <AegisModal v-model="modals.addLicense" title="Add Credential" size="lg">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Credential Type <span class="ep-label-req">*</span></label>
          <select v-model="licenseForm.cred_type" class="form-select" @change="licenseForm.custom_type = ''">
            <option value="">Select a credential...</option>
            <optgroup label="Medical &amp; Prescribing"><option>MD</option><option>DO</option><option>ND</option><option>NP</option><option>PA</option></optgroup>
            <optgroup label="Mental Health"><option>LPC / LPCC</option><option>LCSW / LICSW</option><option>LMFT</option><option>ABPP</option><option>PhD — Psychology</option><option>PsyD — Psychology</option><option>PMHNP-BC</option></optgroup>
            <optgroup label="Therapy &amp; Specialty"><option>EMDR Certified</option><option>DBT Certified</option><option>CSE</option><option>CSC</option><option>CST</option><option>ATR</option><option>MT-BC</option><option>RDT</option></optgroup>
            <optgroup label="Addiction &amp; Health"><option>CADC / ICADC</option><option>LAc</option><option>RD / RDN</option><option>NBC-HWC</option><option>CNM</option><option>CGC</option><option>CDCES / CDE</option></optgroup>
            <optgroup label="Fitness &amp; Physical"><option>CPT (NSCA)</option><option>CPT (NASM)</option><option>CPT (ACE)</option><option>EP-C (ACSM)</option></optgroup>
            <optgroup label="Other"><option value="Licensed">Licensed</option><option value="custom">Other (enter manually)</option></optgroup>
          </select>
          <div v-if="licenseForm.errors.cred_type" class="form-error">{{ licenseForm.errors.cred_type }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Issuing State / Body</label>
          <select v-model="licenseForm.issuer" class="form-select">
            <option value="">Select…</option>
            <option>Alabama</option><option>Alaska</option><option>Arizona</option><option>Arkansas</option><option>California</option><option>Colorado</option><option>Connecticut</option><option>Delaware</option><option>Florida</option><option>Georgia</option><option>Hawaii</option><option>Idaho</option><option>Illinois</option><option>Indiana</option><option>Iowa</option><option>Kansas</option><option>Kentucky</option><option>Louisiana</option><option>Maine</option><option>Maryland</option><option>Massachusetts</option><option>Michigan</option><option>Minnesota</option><option>Mississippi</option><option>Missouri</option><option>Montana</option><option>Nebraska</option><option>Nevada</option><option>New Hampshire</option><option>New Jersey</option><option>New Mexico</option><option>New York</option><option>North Carolina</option><option>North Dakota</option><option>Ohio</option><option>Oklahoma</option><option>Oregon</option><option>Pennsylvania</option><option>Rhode Island</option><option>South Carolina</option><option>South Dakota</option><option>Tennessee</option><option>Texas</option><option>Utah</option><option>Vermont</option><option>Virginia</option><option>Washington</option><option>West Virginia</option><option>Wisconsin</option><option>Wyoming</option>
            <option>Federal</option><option>National / No State</option><option>Other</option>
          </select>
        </div>
      </div>
      <div v-if="licenseForm.cred_type === 'custom'" class="form-group">
        <label class="form-label">Custom Name <span class="ep-label-req">*</span></label>
        <input v-model="licenseForm.custom_type" type="text" class="form-input" placeholder="e.g. Board Certified Coach" />
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">License / Credential #</label>
          <input v-model="licenseForm.number" type="text" class="form-input" placeholder="e.g. NY-12345 (optional)" />
        </div>
        <div class="form-group">
          <label class="form-label">Display Name</label>
          <input v-model="licenseForm.name" type="text" class="form-input" placeholder="e.g. NY Medical License (optional)" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Date Issued</label>
          <input v-model="licenseForm.issued_on" type="date" class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label">Expiration Date</label>
          <input v-model="licenseForm.expires_on" type="date" class="form-input" />
          <div v-if="licenseForm.errors.expires_on" class="form-error">{{ licenseForm.errors.expires_on }}</div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Upload Document</label>
        <AegisDropzone accept=".pdf,.jpg,.jpeg,.png" hint="Drop files or browse — add as many as needed" :multiple="true" @files="licenseForm.document = $event" />
        <div v-if="licenseForm.errors.document || licenseForm.errors['document.*']" class="form-error">{{ licenseForm.errors.document || licenseForm.errors['document.*'] }}</div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.addLicense = false; licenseForm.reset()">Cancel</button>
        <button class="btn btn-primary" :disabled="licenseForm.processing" @click="submitLicense">
          {{ licenseForm.processing ? 'Saving…' : 'Add Credential' }}
        </button>
      </template>
    </AegisModal>

    <!-- ══════════════ ADD INSURANCE MODAL ══════════════ -->
    <AegisModal v-model="modals.addInsurance" title="Add Insurance Policy" size="lg">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Policy Type <span class="ep-label-req">*</span></label>
          <select v-model="insuranceForm.cred_type" class="form-select">
            <option value="">Select type</option>
            <option>Professional Liability (Malpractice)</option>
            <option>General Business Insurance</option>
            <option>Workers Compensation</option>
            <option>Cyber Liability</option>
            <option>Life Insurance</option>
            <option>Other</option>
          </select>
          <div v-if="insuranceForm.errors.cred_type" class="form-error">{{ insuranceForm.errors.cred_type }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Insurance Provider <span class="ep-label-req">*</span></label>
          <input v-model="insuranceForm.issuer" type="text" class="form-input" placeholder="e.g. Medical Protective" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Policy Number</label>
          <input v-model="insuranceForm.number" type="text" class="form-input" placeholder="Policy number" />
        </div>
        <div class="form-group">
          <label class="form-label">Coverage Amount</label>
          <div class="ep-money"><span class="ep-money-prefix">$</span><input v-model="insuranceForm.subtitle" type="text" class="form-input" placeholder="e.g. 2,000,000" /></div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Effective Date</label>
          <input v-model="insuranceForm.issued_on" type="date" class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label">Policy Expiration</label>
          <input v-model="insuranceForm.expires_on" type="date" class="form-input" />
          <div v-if="insuranceForm.errors.expires_on" class="form-error">{{ insuranceForm.errors.expires_on }}</div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Certificate of Insurance</label>
        <AegisDropzone accept=".pdf,.jpg,.jpeg,.png" hint="Drop files or browse — add as many as needed" :multiple="true" @files="insuranceForm.document = $event" />
        <div v-if="insuranceForm.errors.document || insuranceForm.errors['document.*']" class="form-error">{{ insuranceForm.errors.document || insuranceForm.errors['document.*'] }}</div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.addInsurance = false; insuranceForm.reset()">Cancel</button>
        <button class="btn btn-primary" :disabled="insuranceForm.processing" @click="submitInsurance">
          {{ insuranceForm.processing ? 'Saving…' : 'Save Policy' }}
        </button>
      </template>
    </AegisModal>

    <!-- ══════════════ CREDENTIAL EDIT / REMINDER MODAL ══════════════ -->
    <CredentialModal
      v-model="credModal.open"
      :mode="credModal.mode"
      :credential="credModal.item"
      :all-credentials="credentialList"
      @saved="onCredSaved"
      @edit="(m) => { credModal.mode = m }"
    />

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useVuelidate } from '@vuelidate/core'
import { required, minLength, maxLength, helpers, email as emailValidator, url as urlValidator } from '@vuelidate/validators'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisDropzone from '@/components/ui/AegisDropzone.vue'
import AegisToggle from '@/components/ui/AegisToggle.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import CredentialModal from '@/components/modals/CredentialModal.vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import ProfileCompletionStrip from '@/components/features/ProfileCompletionStrip.vue'

const props = defineProps({
  user:                  { type: Object,  required: true },
  credentials:           { type: Array,   default: () => [] },
  meta:                  { type: Object,  default: () => ({}) },
  profileCompletion:     { type: Number,  default: 0 },
  profileItemsRemaining: { type: Number,  default: 0 },
  profileNextStep:       { type: String,  default: '' },
  sectionCompletion:     { type: Object,  default: () => ({}) },
})

const toast = useToast()
const { confirmAction } = useConfirm()

// ── Vuelidate setup ───────────────────────────────────────────────────
// Basic info rules (firstName, lastName, title, bio are required)
const basicRules = computed(() => ({
  firstName: { required: helpers.withMessage('First name is required', required) },
  lastName:  { required: helpers.withMessage('Last name is required', required) },
  basicForm: {
    title: { required: helpers.withMessage('Professional title is required', required) },
    bio:   {
      required: helpers.withMessage('Professional bio is required', required),
      minLength: helpers.withMessage('Bio must be at least 20 characters', minLength(20)),
    },
    phone: { required: helpers.withMessage('Phone is required', required) },
    location: { required: helpers.withMessage('Location is required', required) },
  },
}))
const basicState = computed(() => ({ firstName, lastName, basicForm }))
const vBasic = useVuelidate(basicRules, basicState)

// Website / contact rules
const websiteRules = computed(() => ({
  websiteForm: {
    website: {
      validUrl: helpers.withMessage('Enter a valid URL (e.g. yoursite.com)', (v) => {
        if (!v) return true
        try { new URL(v.startsWith('http') ? v : `https://${v}`); return true } catch { return false }
      }),
    },
  },
}))
const websiteState = computed(() => ({ websiteForm }))
const vWebsite = useVuelidate(websiteRules, websiteState)

// Helper: get field error message for display
function fieldError(v$obj, ...path) {
  let node = v$obj.value
  for (const key of path) {
    node = node?.[key]
    if (!node) return null
  }
  if (node.$dirty && node.$error) {
    return node.$errors?.[0]?.$message ?? null
  }
  return null
}

// ── Section nav ───────────────────────────────────────────────────────
const activeSection = ref('basic-info')
const mainNavItems = [
  { key: 'basic-info',   icon: 'user',            label: 'Basic Info' },
  { key: 'professional', icon: 'graduation-cap',  label: 'Professional' },
  { key: 'specialties',  icon: 'star',            label: 'Specialties' },
  { key: 'insurance',    icon: 'credit-card',     label: 'Insurance' },
  { key: 'network',      icon: 'globe',           label: 'Network Preferences' },
  { key: 'demographics', icon: 'users',           label: 'Demographics' },
]

// ── Modals ────────────────────────────────────────────────────────────
const modals = reactive({ photoUpload: false, removePhoto: false, addLicense: false, addInsurance: false })

function scrollToFirstIncomplete() {
  const first = mainNavItems.find(s => !props.sectionCompletion[s.key])
  if (first) activeSection.value = first.key
}

// ── Identity / basic info ────────────────────────────────────────────
const nameParts = (props.user.display_name ?? '').replace(/^(Dr\.|Prof\.|Mr\.|Ms\.|Mrs\.)\s*/i, '').trim().split(' ')
const firstName = ref(nameParts[0] ?? '')
const lastName  = ref(nameParts.slice(1).join(' '))

const basicForm = useForm({
  display_name:    props.user.display_name ?? '',
  credentials:     props.user.credentials ?? '',
  title:           props.user.title ?? '',
  organization:    props.user.organization ?? '',
  location:        props.user.location ?? '',
  phone:           props.user.phone ?? '',
  avatar_initials: props.user.avatar_initials ?? '',
  bio:             props.user.bio ?? '',
  about_me:        props.user.about_me ?? '',
})

const initialsFromName = computed(() => {
  const f = firstName.value?.[0] ?? ''
  const l = lastName.value?.[0] ?? ''
  return (f + l).toUpperCase() || '—'
})

async function submitBasic() {
  const ok = await vBasic.value.$validate()
  if (!ok) { toast.error('Please fix the errors before saving.'); return }
  basicForm.display_name = `${firstName.value} ${lastName.value}`.trim()
  basicForm.put(route('provider.profile.basic'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Basic info saved.')
      router.reload({ only: ['sectionCompletion', 'profileCompletion', 'profileItemsRemaining'] })
    },
  })
}

// ── Languages / website ──────────────────────────────────────────────
const languageOptions = ['English','Spanish','Mandarin Chinese','French','Arabic','Hindi','Portuguese','Russian','Korean','Japanese','Vietnamese','Tagalog','Italian','German','Polish']
const customLanguageInput = ref('')
const websiteForm = useForm({
  languages: Array.isArray(props.meta.languages) ? [...props.meta.languages] : ['English'],
  website:   props.meta.website ?? '',
})
function addCustomLanguage() {
  const v = customLanguageInput.value.trim()
  if (!v) return
  if (!websiteForm.languages.includes(v)) websiteForm.languages.push(v)
  customLanguageInput.value = ''
}
async function submitLanguages() {
  const ok = await vWebsite.value.$validate()
  if (!ok) { toast.error('Please fix the errors before saving.'); return }
  websiteForm.put(route('provider.profile.languages'), {
    preserveScroll: true,
    onSuccess: () => toast.success('Contact details saved.'),
  })
}
// ── Generic tag toggle helper ─────────────────────────────────────────
function toggleInArray(arr, value) {
  const i = arr.indexOf(value)
  if (i === -1) arr.push(value)
  else arr.splice(i, 1)
}

// ── Credentials (provider_credentials CRUD) ──────────────────────────
const credentialList = ref(props.credentials.map((c) => ({ ...c, archived: false })))
const licenseCredentials = computed(() => credentialList.value.filter((c) => !c.is_insurance))
const insuranceCredential = computed(() => credentialList.value.find((c) => c.is_insurance) ?? null)

// Re-sync when Inertia partial reloads update props.credentials
watch(() => props.credentials, (fresh) => {
  credentialList.value = fresh.map((c) => ({ ...c, archived: false }))
}, { deep: true })

// Credential modal state
const credModal = reactive({ open: false, mode: 'add-credential', item: null })
function openCredModal(mode, item = null) { credModal.mode = mode; credModal.item = item; credModal.open = true }
function onCredSaved() { router.reload({ only: ['credentials'] }) }

function documentName(path) {
  if (!path) return ''
  const file = path.split('/').pop()
  const ext = file.includes('.') ? file.split('.').pop() : ''
  return ext ? `Document.${ext}` : file
}

function addLicense() { modals.addLicense = true }

const licenseForm = useForm({
  cred_type:   '',
  custom_type: '',
  name:        '',
  number:      '',
  issuer:      '',
  issued_on:   '',
  expires_on:  '',
  document:    [],
})
function submitLicense() {
  if (!licenseForm.cred_type) { toast.error('Please select a credential type.'); return }
  const type = licenseForm.cred_type === 'custom'
    ? (licenseForm.custom_type.trim() || 'Other')
    : licenseForm.cred_type
  licenseForm
    .transform((d) => ({ ...d, cred_type: type, document: d.document }))
    .post(route('provider.credentials.store'), {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        modals.addLicense = false
        toast.success('License added.')
        licenseForm.reset()
        router.reload({ only: ['credentials', 'sectionCompletion', 'profileCompletion', 'profileItemsRemaining'] })
      },
    })
}

function saveCredentialField(cred) {
  router.put(route('provider.credentials.update', cred.id), {
    issuer:     cred.issuer,
    number:     cred.number,
    issued_on:  cred.issued_on,
    expires_on: cred.expires_on,
  }, { preserveScroll: true, onSuccess: () => toast.success('License updated.') })
}

function uploadCredentialDocument(cred, file) {
  router.post(route('provider.credentials.update', cred.id), {
    _method:  'put',
    document: file,
  }, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => { toast.success('Document uploaded.'); router.reload({ only: ['credentials'] }) },
  })
}

function toggleArchiveCredential(cred) {
  // Visual-only archive — no is_archived column exists in DB yet
  cred.archived = !cred.archived
  toast.info(cred.archived ? 'Credential archived — kept for your records' : 'Credential restored')
}

function confirmRemoveCredential(cred) {
  confirmAction(
    'Remove this credential? This cannot be undone.',
    () => router.delete(route('provider.credentials.destroy', cred.id), {
      preserveScroll: true,
      onSuccess: () => { toast.success('Credential removed.'); router.reload({ only: ['credentials'] }) },
    }),
    { title: 'Remove Credential', btnLabel: 'Remove', type: 'danger' }
  )
}

function removeCredentialDoc(cred, filePath) {
  router.delete(route('provider.credentials.document.destroy', cred.id), {
    data: { path: filePath },
    preserveScroll: true,
    onSuccess: () => { toast.success('File removed.'); router.reload({ only: ['credentials'] }) },
  })
}

const insuranceForm = useForm({
  cred_type:   '',
  issuer:      '',
  number:      '',
  subtitle:    '',
  issued_on:   '',
  expires_on:  '',
  document:    [],
})
function openAddInsurance() {
  insuranceForm.reset()
  modals.addInsurance = true
}
function submitInsurance() {
  if (!insuranceForm.cred_type) { toast.error('Please select a policy type.'); return }
  if (!insuranceForm.issuer.trim()) { toast.error('Please enter the insurance provider.'); return }
  insuranceForm.transform((data) => ({
    ...data,
    cred_type: data.cred_type,
    name: data.cred_type,
  })).post(route('provider.credentials.store'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      modals.addInsurance = false
      toast.success('Insurance policy saved.')
      insuranceForm.reset()
      router.reload({ only: ['credentials', 'sectionCompletion', 'profileCompletion', 'profileItemsRemaining'] })
    },
    onFinish: () => insuranceForm.transform((data) => data),
  })
}

// ── Credential / specialty / service / approach tag taxonomies ───────
const credentialTagGroups = [
  { label: 'Medical & Prescribing', options: ['MD — Medical Doctor', 'DO — Osteopathic Medicine', 'ND — Naturopathic Doctor', 'NP — Nurse Practitioner', 'PA — Physician Assistant'] },
  { label: 'Licensed Mental Health', options: ['LPC / LPCC', 'LCSW / LICSW', 'LMFT', 'PhD — Psychology', 'PsyD — Psychology', 'DMFT', 'ABPP — Board Certified Psychologist', 'PMHNP-BC'] },
  { label: 'Therapy & Specialized', options: ['EMDR Certified', 'DBT Certified', 'CSE — Certified Sex Educator', 'CSC — Certified Sex Counselor', 'CST — Certified Sex Therapist', 'ATR — Art Therapist', 'MT-BC — Music Therapist', 'RDT — Drama Therapist'] },
  { label: 'Addiction, Nutrition & Health', options: ['CADC / ICADC', 'LAc — Licensed Acupuncturist', 'RD / RDN — Registered Dietitian', 'NBC-HWC — Health & Wellness Coach', 'CNM — Certified Nurse-Midwife', 'CGC — Certified Genetic Counselor', 'CDCES / CDE — Diabetes Specialist'] },
  { label: 'Fitness & Physical Health', options: ['CPT (NSCA)', 'CPT (NASM)', 'CPT (ACE)', 'EP-C (ACSM) — Exercise Physiologist'] },
  { label: 'Coaching', options: ['ICF — Life / Executive Coach', 'CPCC — Certified Professional Co-Active Coach', 'ACC / PCC / MCC (ICF)'] },
]
const customCertInput = ref('')
function addCustomCert() {
  const v = customCertInput.value.trim()
  if (!v) return
  if (!specialtiesForm.specialties.includes(v)) specialtiesForm.specialties.push(v)
  customCertInput.value = ''
}

const serviceTagGroups = [
  { label: 'Personal Wellness & Therapy', options: ['Psychotherapy (individual, couples, family)', 'Stress reduction & relaxation', 'Holistic lifestyle guidance', 'Nutritional & lifestyle recommendations', 'Wellness assessments', 'Sustainable wellness habit coaching'] },
  { label: 'Consultations, Supervision & Advisory', options: ['Health & wellness consultations (individuals)', 'Health & wellness consultations (organizations)', 'Workplace wellbeing consulting', 'DEI consultations', 'Cultural responsiveness consulting', 'Case consultation / professional guidance', 'Clinical supervision', 'General advisory & strategy sessions'] },
  { label: 'Movement & Physical Health', options: ['Movement & fitness support', 'Strength & mobility coaching', 'Personalized training programs'] },
  { label: 'Birth, Postpartum & Sleep', options: ['Prenatal education', 'Birth planning', 'Postpartum support', 'Infant feeding support', 'Sleep assessments & evaluations', 'Sleep training', 'Sleep hygiene & circadian rhythm education'] },
  { label: 'Integrative & Natural Approaches', options: ['Herbal consultations', 'Homeopathic remedies', 'Energy work (Reiki, chakra balancing)', 'Genetic risk assessment', 'Medication evaluation'] },
  { label: 'Practice Continuity Services', options: ['Practice Continuity (Continuity Steward) Services'] },
]
const customServiceInput = ref('')
function addCustomService() {
  const v = customServiceInput.value.trim()
  if (!v) return
  if (!servicesForm.services.includes(v)) servicesForm.services.push(v)
  customServiceInput.value = ''
}

const specialtyTagGroups = [
  { label: 'Everyday Wellbeing & Life Support', options: ['Stress & burnout', 'Self-esteem & identity', 'Life transitions', 'Career & work support', 'Relationship challenges', 'Lifestyle & healthy habits', 'Wellness & health coaching', 'Integrated health & business coaching'] },
  { label: 'Relationships, Family & Connection', options: ['Individual support', 'Couples & partnership support', 'Family support', 'Parenting & children', 'Children & adolescent support', 'Intimacy & sex therapy', 'LGBTQIA+ affirming care'] },
  { label: 'Emotional & Mental Health', options: ['Anxiety & worry', 'Mood challenges (depression & bipolar)', 'Trauma & PTSD', 'Grief & loss', 'Addiction & substance use', 'Eating concerns & body relationship', 'Sleep challenges', 'Focus & attention (ADHD)', 'Autism support', 'Psychosis-related experiences'] },
  { label: 'Identity, Culture & Life Experience', options: ["Women's health & experiences", "Men's health & experiences", 'Older adult support & aging', 'Veteran support', 'Immigration & cultural stress', 'BIPOC support', 'Spirituality & personal beliefs'] },
  { label: 'Health Conditions & Medical Support', options: ['Medication support & management', 'Chronic illness & ongoing conditions', 'Cancer support', 'Diabetes & blood sugar', 'Heart & cardiovascular health', 'Hormonal health', 'Weight & metabolic health', 'Pain & physical conditions'] },
  { label: 'Nutrition & Physical Health', options: ['Nutrition & dietary support', 'Functional & integrative nutrition', 'Gut & digestive health', 'Hormonal & metabolic nutrition', 'Prenatal & postpartum nutrition', 'Sports & performance nutrition'] },
  { label: 'Integrative, Holistic & Preventive', options: ['Preventive care & longevity', 'Root-cause & whole-person approaches', 'Mind–body practices', 'Energy & spiritual care', 'Herbal support & education'] },
  { label: 'Reproductive, Birth & Family Building', options: ['Prenatal education & planning', 'Birth support', 'Postpartum support', 'Reproductive & perinatal mental health'] },
  { label: 'Creative, Expressive & Community', options: ['Art, music, play & dance-based support', 'Animal-assisted support', 'Trauma-informed approaches', 'Diversity, equity & inclusion', 'Corporate & workplace wellbeing'] },
]
const customSpecialtyInput = ref('')
function addCustomSpecialty() {
  const v = customSpecialtyInput.value.trim()
  if (!v) return
  if (!specialtiesForm.specialties.includes(v)) specialtiesForm.specialties.push(v)
  customSpecialtyInput.value = ''
}

const approachTagGroups = [
  { label: 'Clinical', options: ['Cognitive Behavioral Therapy (CBT)', 'Narrative Therapy', 'Collaborative Language Therapy', 'Structural Family Therapy', 'Gestalt Therapy', 'Jungian Therapy', 'Adlerian Therapy', 'Behavioral Therapy', 'Dialectical Behavioral Therapy (DBT)', 'Acceptance and Commitment Therapy (ACT)', 'Experiential Therapy', 'Existential Therapy', 'Eclectic', 'Psychoanalytic', 'Somatic Therapy', 'Trauma-Focused Therapy', 'EMDR', 'Mindfulness-Based Therapy', 'Solution-Focused Brief Therapy', 'Internal Family Systems (IFS)', 'Family Systems Therapy', 'Psychodynamic Therapy', 'Motivational Interviewing'] },
  { label: 'Nutrition & Dietetics', options: ['Medical Nutrition Therapy (MNT)', 'Personalized Nutrition Planning', 'Behavior Change Counseling', 'Intuitive Eating', 'Functional Nutrition', 'Anti-Inflammatory Nutrition', 'Therapeutic Diets (low-FODMAP, DASH, Mediterranean)', 'Elimination Diets', 'Nutritional Education & Coaching', 'Weight-Neutral Nutrition', 'Meal Planning & Dietary Structuring'] },
  { label: 'Functional Medicine', options: ['Root-Cause Analysis', 'Systems Biology Approach', 'Personalized Medicine', 'Lifestyle Medicine', 'Hormone Optimization', 'Gut Restoration Protocols', 'Anti-Inflammatory Interventions', 'Detoxification Protocols', 'Nutraceutical & Supplement Therapy', 'Stress & HPA Axis Regulation', 'Mind-Body Medicine', 'Preventive & Longevity Medicine', 'Environmental Medicine'] },
  { label: 'Psychiatry', options: ['Psychopharmacology', 'Medication Management', 'Combined Therapy & Medication', 'Evidence-Based Prescribing', 'Treatment-Resistant Protocols', 'Long-Acting Injectable Therapy', 'Somatic Therapies (ECT, TMS, Ketamine)', 'Collaborative Care Models', 'Psychiatric Assessment & Care Planning'] },
]

// ── Accordion state (must be after tag group arrays) ─────────────────
const openAccordions = ref(new Set([
  'svc_' + (serviceTagGroups[0]?.label ?? ''),
  'sp_'  + (specialtyTagGroups[0]?.label ?? ''),
  'ap_'  + (approachTagGroups[0]?.label ?? ''),
]))
function toggleAccordion(key) {
  if (openAccordions.value.has(key)) openAccordions.value.delete(key)
  else openAccordions.value.add(key)
}

const customApproachInput = ref('')
function addCustomApproach() {
  const v = customApproachInput.value.trim()
  if (!v) return
  if (!approachesForm.approaches.includes(v)) approachesForm.approaches.push(v)
  customApproachInput.value = ''
}

const specialtiesForm = useForm({ specialties: Array.isArray(props.meta.specialties) ? [...props.meta.specialties] : [] })
function submitSpecialties() {
  specialtiesForm.put(route('provider.profile.specialties'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Specialties saved.')
      router.reload({ only: ['sectionCompletion', 'profileCompletion', 'profileItemsRemaining'] })
    },
  })
}

const servicesForm = useForm({ services: Array.isArray(props.meta.services) ? [...props.meta.services] : [] })
function submitServices() {
  servicesForm.put(route('provider.profile.services'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Services saved.')
      router.reload({ only: ['sectionCompletion', 'profileCompletion', 'profileItemsRemaining'] })
    },
  })
}

const approachesForm = useForm({ approaches: Array.isArray(props.meta.approaches) ? [...props.meta.approaches] : [] })
function submitApproaches() {
  approachesForm.put(route('provider.profile.approaches'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Approaches saved.')
      router.reload({ only: ['sectionCompletion', 'profileCompletion', 'profileItemsRemaining'] })
    },
  })
}

// ── Insurance grid + fees ─────────────────────────────────────────────
const insuranceGroups = [
  { label: 'Commercial Health Plans', options: ['Aetna', 'Cigna / Evernorth', 'UnitedHealthcare', 'Humana', 'Kaiser Permanente', 'Oscar Health', 'Molina Healthcare', 'WellCare', 'Centene'] },
  { label: 'Blue Cross Blue Shield', options: ['Anthem BCBS', 'BCBS (State / Regional)', 'Blue Shield of California', 'Premera Blue Cross', 'Regence BlueCross'] },
  { label: 'Government Programs', options: ['Medicare', 'Medicaid', 'TRICARE', 'VA Community Care', 'CHIP'] },
  { label: 'Marketplace / Specialty', options: ['EAP', 'Optum / Optum Health', 'Magellan Health', 'ValueOptions / Beacon', 'Multiplan / PHCS', 'Out-of-Network (OON)'] },
]
const customInsuranceInput = ref('')
function addCustomInsurance() {
  const v = customInsuranceInput.value.trim()
  if (!v) return
  if (!feesForm.insurance_types.includes(v)) feesForm.insurance_types.push(v)
  customInsuranceInput.value = ''
}

const feesForm = useForm({
  insurance_types: Array.isArray(props.meta.insurance_panels) ? [...props.meta.insurance_panels] : [],
})
function submitInsurancePanels() {
  feesForm.put(route('provider.profile.fees'), {
    preserveScroll: true,
    onSuccess: () => toast.success('Insurance panels saved.'),
  })
}



// ── Network partners (interdisciplinary tags) ─────────────────────────
const interdisciplinaryOptions = ['Psychotherapist & Psychologist', 'Psychiatrist', 'Movement / Dance Specialist', 'Coach (lifestyle, career)', 'Behavioral Therapist', 'Massage Therapist', 'Acupuncturist', 'Functional Medicine Practitioner', 'Holistic Nutrition Practitioner', 'Certified Diabetes Educator (CDE)', 'Hypnotherapist', 'Energy Healing Practitioner', 'Homeopath', 'Herbalist', 'Somatic Practitioner', 'Ayurveda Practitioner', 'Certified Nurse-Midwife (CNM)', 'Doula', 'Sleep Specialist', 'Genetic Counselor', 'Personal Trainer']
const networkPartnersForm = useForm({ partners: Array.isArray(props.meta.network_partners) ? [...props.meta.network_partners] : [] })
function submitNetworkPartners() {
  networkPartnersForm.put(route('provider.profile.network-partners'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Network preferences saved.')
      router.reload({ only: ['sectionCompletion', 'profileCompletion', 'profileItemsRemaining'] })
    },
  })
}

// ── Licensed states ────────────────────────────────────────────────────
const usStateAbbrs = ['NY','NJ','CT','PA','MA','VT','NH','RI','ME','DE','MD','VA','WV','NC','SC','GA','FL','AL','TN','KY','OH','IN','IL','MI','WI','MN','MO','IA','KS','NE','ND','SD','OK','TX','AR','LA','MS','AZ','NM','CO','UT','WY','MT','ID','NV','OR','WA','CA','AK','HI','DC','PR']
const licensedStatesForm = useForm({ states: Array.isArray(props.meta.licensed_states) ? [...props.meta.licensed_states] : [] })
function submitLicensedStates() {
  licensedStatesForm.put(route('provider.profile.licensed-states'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Licensed states saved.')
      router.reload({ only: ['sectionCompletion', 'profileCompletion', 'profileItemsRemaining'] })
    },
  })
}

// ── AI / Shadow Network settings ───────────────────────────────────────
const ai = props.meta.ai_settings ?? {}
const aiForm = useForm({
  suggestions_mode:        ai.suggestions_mode ?? 'on',
  max_distance:            ai.max_distance ?? '25',
  allow_referral_patterns: ai.allow_referral_patterns ?? true,
  allow_demographics:      ai.allow_demographics ?? true,
  allow_specialties:       ai.allow_specialties ?? true,
  appear_in_suggestions:   ai.appear_in_suggestions ?? false,
  show_in_directory:       ai.show_in_directory ?? true,
})
function submitAiSettings() {
  aiForm.put(route('provider.profile.ai-settings'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('AI & Shadow Network settings saved.')
      router.reload({ only: ['sectionCompletion', 'profileCompletion', 'profileItemsRemaining'] })
    },
  })
}

// ── Education ──────────────────────────────────────────────────────────
const educationForm = useForm({
  education: Array.isArray(props.meta.education) && props.meta.education.length
    ? props.meta.education.map((e) => ({ ...e }))
    : [{ degree: '', field: '', institution: '', duration: '' }],
})
function submitEducation() {
  // Filter out completely blank rows before submitting
  const cleaned = educationForm.education.filter(
    (e) => e.degree?.trim() || e.field?.trim() || e.institution?.trim() || e.duration?.trim()
  )
  if (!cleaned.length) { toast.error('Add at least one education entry before saving.'); return }
  educationForm.education = cleaned
  educationForm.put(route('provider.profile.education'), {
    preserveScroll: true,
    onSuccess: () => toast.success('Education saved.'),
  })
}

// ── Demographics ────────────────────────────────────────────────────────
const demographicFields = [
  { key: 'pronouns', label: 'Pronouns', options: ['She / Her', 'He / Him', 'They / Them', 'Ze / Zir', 'Any Pronouns', 'Prefer Not to Disclose'] },
  { key: 'ethnicity', label: 'Race / Ethnicity', options: ['American Indian or Alaska Native', 'Asian', 'Black or African American', 'Hispanic or Latino/a/x', 'Middle Eastern or North African', 'White or Caucasian', 'Multiracial or Biracial', 'Prefer Not to Say'] },
  { key: 'lgbtq_identity', label: 'LGBTQ+ Identity', options: ['LGBTQ+ Identifying Provider', 'LGBTQ+ Affirming (Ally)', 'Not Disclosed'] },
  { key: 'parenting_status', label: 'Parenting Status', options: ['Parent', 'Not a Parent', 'Prefer Not to Disclose'] },
  { key: 'religious_orientation', label: 'Religious / Spiritual Orientation', options: ['Christian', 'Jewish', 'Muslim', 'Buddhist', 'Hindu', 'Spiritual (Non-Religious)', 'Secular / Non-Religious', 'Prefer Not to Disclose'] },
  { key: 'veteran_status', label: 'Veteran Status', options: ['Military Veteran', 'Active Duty Military', 'Veteran-Affirming (Non-Veteran)', 'Not Applicable'] },
  { key: 'supervision_status', label: 'Clinical Supervision Status', options: ['Yes — I Provide Clinical Supervision', 'No — I Do Not Provide Supervision', 'Approved Supervisor (Licensed)', 'Accepting Supervisees', 'Not Accepting Supervisees'] },
]
const demo = props.meta.demographics ?? {}
const demographicsForm = useForm({
  pronouns:              Array.isArray(demo.pronouns) ? [...demo.pronouns] : [],
  ethnicity:             Array.isArray(demo.ethnicity) ? [...demo.ethnicity] : [],
  lgbtq_identity:        Array.isArray(demo.lgbtq_identity) ? [...demo.lgbtq_identity] : [],
  parenting_status:      Array.isArray(demo.parenting_status) ? [...demo.parenting_status] : [],
  religious_orientation: Array.isArray(demo.religious_orientation) ? [...demo.religious_orientation] : [],
  veteran_status:        Array.isArray(demo.veteran_status) ? [...demo.veteran_status] : [],
  supervision_status:    Array.isArray(demo.supervision_status) ? [...demo.supervision_status] : [],
})
function submitDemographics() {
  demographicsForm.put(route('provider.profile.demographics'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Demographics saved.')
      router.reload({ only: ['sectionCompletion', 'profileCompletion', 'profileItemsRemaining'] })
    },
  })
}

// ── Availability ─────────────────────────────────────────────────────
const weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
const availability = props.meta.availability ?? {}
const hoursDefault = { days: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'], start: '09:00', end: '17:00', timezone: 'Eastern Time (EST)', response_time: 'Within 2 hours' }
const availabilityForm = useForm({
  hours: (() => {
    const base = { ...hoursDefault }
    if (availability.hours && typeof availability.hours === 'object') {
      Object.assign(base, availability.hours)
      if (Array.isArray(availability.hours.days)) base.days = [...availability.hours.days]
    }
    return base
  })(),
  accepting:  availability.accepting ?? true,
  telehealth: availability.telehealth ?? true,
})
function submitAvailability() {
  availabilityForm
    .transform((data) => ({
      ...data,
      timezone: data.hours?.timezone ?? 'Eastern Time (EST)',
    }))
    .put(route('provider.profile.availability'), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Availability saved.')
        availabilityForm.transform((d) => d)
      },
    })
}

// ── Photo upload / removal — real multipart upload to provider.profile.avatar.* ──
const avatarForm = useForm({ avatar: null })
function handleAvatarUpload(file) {
  avatarForm.avatar = file
  avatarForm.post(route('provider.profile.avatar.update'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      modals.photoUpload = false
      toast.success('Photo uploaded.')
    },
    onError: () => toast.error('Could not upload photo — check file size and format.'),
  })
}
function confirmRemovePhoto() {
  router.delete(route('provider.profile.avatar.destroy'), {
    preserveScroll: true,
    onSuccess: () => {
      modals.removePhoto = false
      toast.success('Profile photo removed.')
    },
  })
}

// ── Completion — driven entirely by server-side per-section state ──
// sectionCompletion prop: { 'basic-info': bool, 'professional': bool, ... }
// profileItemsRemaining prop: number of incomplete sections
const sectionCompletion = computed(() => props.sectionCompletion ?? {})

const lastSavedLabel = computed(() => {
  if (!props.user.updated_at) return 'recently'
  try {
    const diffMs = Date.now() - new Date(props.user.updated_at).getTime()
    const hours = Math.floor(diffMs / 3600000)
    if (hours < 1) return 'just now'
    if (hours < 24) return `${hours}h ago`
    return `${Math.floor(hours / 24)}d ago`
  } catch { return 'recently' }
})
</script>

<style scoped>
/* ─── Form actions bar (per-card save buttons) ─── */
.form-actions-bar {
  display: flex;
  gap: 10px;
  margin-top: 20px;
  padding-top: 18px;
  border-top: 1px solid var(--border);
}

/* ─── Two-column layout ─── */
.ep-layout {
  display: grid;
  grid-template-columns: 220px 1fr;
  gap: 22px;
  align-items: start;
}

/* ─── Left nav — uses global .page-sidebar-* classes ─── */
.ep-nav-wrap { position: sticky; top: 84px; }
/* check-badge completion indicator */
.ep-nav-check { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--gold-dark); opacity: 0.7; transition: opacity var(--transition); }
.page-sidebar-item:hover .ep-nav-check,
.page-sidebar-item.active .ep-nav-check { opacity: 1; }
.ep-nav-check .aegis-icon-empty { color: var(--border); opacity: 1; }
.page-sidebar-item.active .ep-nav-check .aegis-icon-empty { color: var(--text-4); }

/* ─── Sections ─── */
.ep-section { animation: epSectionIn 0.22s ease; }
@keyframes epSectionIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

/* ─── Cards ─── */
.ep-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  margin-bottom: 18px;
  overflow: hidden;
}
.ep-card-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: var(--surface-2); border-bottom: 1px solid var(--border); }
.ep-card-header-left { display: flex; align-items: center; gap: 10px; }
.ep-card-icon { width: 32px; height: 32px; border-radius: var(--radius-sm); background: var(--badge-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ep-card-title { font-family: var(--font-serif); font-size: 17px; font-weight: 700; color: var(--text); }
.ep-card-sub { font-size: 12px; color: var(--text-3); margin-top: 2px; }

/* ─── Labels ─── */
.ep-label-req { color: var(--red); }
.ep-label-opt { font-size: 10px; font-weight: 600; text-transform: none; letter-spacing: 0; color: var(--text-4); }

/* ─── Money / prefix inputs ─── */
.ep-money { position: relative; }
.ep-money-prefix { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; font-weight: 600; color: var(--text-3); pointer-events: none; }
.ep-money .form-input { padding-left: 24px; }
.ep-input-prefix { display: flex; align-items: stretch; border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; transition: border-color var(--transition), box-shadow var(--transition); }
.ep-input-prefix:focus-within { border-color: var(--gold-dark); box-shadow: 0 0 0 3px var(--badge-bg-gold); }
.ep-input-prefix-label { padding: 9px 12px; background: var(--surface-2); border-right: 1.5px solid var(--border); font-size: 12px; color: var(--text-3); white-space: nowrap; display: flex; align-items: center; }
.ep-input-prefix .form-input { border: none; border-radius: 0; flex: 1; }
.ep-input-prefix .form-input:focus { box-shadow: none; }
.ep-divider { height: 1px; background: var(--border); margin: 20px 0; }

/* ─── Avatar row ─── */
.ep-avatar-row { display: flex; align-items: center; gap: 20px; padding: 18px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-lg); }
.ep-avatar-preview {
  width: 80px; height: 80px;
  border-radius: var(--radius-full);
  background: linear-gradient(135deg, var(--gold-dark) 0%, var(--gold) 100%);
  color: var(--text-inverted);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-serif); font-size: 28px; font-weight: 700;
  flex-shrink: 0; box-shadow: var(--shadow);
}
.ep-avatar-info h4 { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); margin: 0 0 3px; }
.ep-avatar-info p { font-size: 12px; color: var(--text-3); line-height: 1.5; margin: 0; }
.ep-avatar-btns { display: flex; gap: 8px; margin-top: 10px; }

/* ─── Credential cards (licenses) ─── */
.ep-cred { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px 20px; margin-bottom: 12px; transition: border-color var(--transition); }
.ep-cred.primary { border: 1px solid var(--gold-dark); background: var(--badge-bg-gold); }
.ep-cred.is-archived { opacity: 0.55; border-color: var(--border); background: var(--surface-3); }
.ep-cred-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 8px; }
.ep-cred-title { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--text); }
.ep-cred-badge { display: inline-flex; align-items: center; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: var(--radius-full); background: var(--badge-bg-gold); color: var(--gold-dark); border: 1px solid var(--badge-border-gold); }
.ep-cred.is-archived .ep-cred-badge { background: var(--surface-4); color: var(--text-4); border-color: var(--border-dark); }
.ep-cred-meta-row  { display: flex; flex-wrap: wrap; border-top: 1px solid var(--border); margin-top: 6px; padding-top: 2px; }
.ep-cred-meta-item { display: flex; flex-direction: column; gap: 2px; padding: 8px 16px 8px 0; min-width: 110px; flex: 1; }
.ep-cred-meta-item > span   { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-4); }
.ep-cred-meta-item > strong { font-size: 12px; font-weight: 600; color: var(--text); }
.ep-cred-meta-item.is-expiry-warn > strong { color: var(--red-dark); }
.ep-cred.is-archived .ep-cred-badge { background: var(--surface-4); color: var(--text-4); border-color: var(--border-dark); }

/* ─── File row ─── */
.ep-file-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); }
.ep-file-info { display: flex; align-items: center; gap: 12px; }
.ep-file-icon { width: 36px; height: 36px; border-radius: var(--radius-sm); background: var(--badge-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ep-file-name { font-size: 13px; font-weight: 700; color: var(--text); }
.ep-file-meta { font-size: 11px; color: var(--text-3); margin-top: 1px; }

/* ─── Category header ─── */
.ep-cat { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px; color: var(--text-4); margin: 24px 0 10px; padding-bottom: 6px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 7px; }
.card-body > .ep-cat:first-child,
.card-body > div:first-child > .ep-cat,
details > div > div:first-child > .ep-cat { margin-top: 0; }

/* ─── Insurance checklist grid ─── */
.ep-ins-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 7px; }
.ep-ins-item { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border: 1px solid var(--border); border-radius: var(--radius); cursor: pointer; transition: all var(--transition); font-size: 13px; font-weight: 600; color: var(--text); background: var(--surface); }
.ep-ins-item:hover { border-color: var(--gold-dark); background: var(--badge-bg-gold); }
.ep-ins-item.checked { border: 1px solid var(--gold-dark); background: var(--badge-bg-gold); color: var(--gold-dark); font-weight: 700; }
.ep-ins-dot { width: 14px; height: 14px; border-radius: var(--radius-full); flex-shrink: 0; border: 1px solid var(--border-dark); background: var(--surface); display: flex; align-items: center; justify-content: center; transition: all var(--transition); position: relative; }
.ep-ins-dot::after { content: ''; width: 6px; height: 6px; border-radius: var(--radius-full); background: transparent; transition: background var(--transition), transform var(--transition); transform: scale(0); }
.ep-ins-item.checked .ep-ins-dot { border-color: var(--gold-dark); background: var(--surface); }
.ep-ins-item.checked .ep-ins-dot::after { background: var(--gold-dark); transform: scale(1); }

/* ─── State grid ─── */
.ep-states { display: grid; grid-template-columns: repeat(9, 1fr); gap: 5px; }
.ep-state { height: 34px; border: 1px solid var(--border); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; color: var(--text-3); cursor: pointer; transition: all var(--transition); background: var(--surface); }
.ep-state:hover { border-color: var(--gold-dark); color: var(--gold-dark); }
.ep-state.selected { background: var(--gold-dark); color: var(--text-inverted); border: 1px solid var(--gold-dark); }

/* ─── Day buttons ─── */
.ep-days { display: flex; gap: 8px; flex-wrap: wrap; }
.ep-day {
  width: 46px; height: 46px; border-radius: var(--radius);
  border: 1px solid var(--border); background: var(--surface);
  font-size: 11px; font-weight: 700; color: var(--text-3);
  cursor: pointer; transition: all var(--transition);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-sans);
}
.ep-day:hover { border-color: var(--gold-dark); color: var(--gold-dark); }
.ep-day.selected { background: var(--badge-bg-gold); color: var(--gold-dark); border-color: var(--soft-gold); }

/* ─── Radio item ─── */
.ep-radio-item { display: flex; align-items: center; gap: 9px; font-size: 13px; color: var(--text-2); cursor: pointer; padding: 6px 10px; border-radius: var(--radius-sm); transition: background var(--transition); }
.ep-radio-item:hover { background: var(--surface-2); }
.ep-radio-item input { width: 15px; height: 15px; cursor: pointer; accent-color: var(--gold-dark); flex-shrink: 0; }

/* ─── Responsive ─── */

/* ─── Accordion (replaces <details>) ─── */
.ep-accordion { border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 8px; overflow: hidden; }
.ep-accordion-header {
  display: flex; align-items: center; justify-content: space-between;
  width: 100%; padding: 11px 16px;
  background: var(--surface-2);
  border: none; cursor: pointer;
  font-family: var(--font-sans); font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.6px;
  color: var(--text-3);
  transition: background var(--transition), color var(--transition);
  text-align: left;
}
.ep-accordion-header:hover { background: var(--surface-3); color: var(--gold-dark); }
.ep-accordion-chevron { color: var(--text-4); transition: transform 0.22s ease; flex-shrink: 0; }
.ep-accordion-body { padding: 14px 16px; border-top: 1px solid var(--border); }

/* Accordion transition */
.ep-accordion-enter-active,
.ep-accordion-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.ep-accordion-enter-from,
.ep-accordion-leave-to { opacity: 0; transform: translateY(-4px); }

/* ─── Education grid — no margin-bottom on inner form-groups ─── */
.ep-edu-grid .form-group { margin-bottom: 0; padding:0 }

@media (max-width: 960px) {
  .ep-layout { grid-template-columns: 1fr; }
  .ep-nav-wrap { position: static; }
  .page-sidebar.ep-nav-wrap .page-sidebar-group { display: flex; flex-wrap: wrap; padding: 4px 6px; }
  .page-sidebar.ep-nav-wrap .page-sidebar-label { display: none; }
  .page-sidebar.ep-nav-wrap .page-sidebar-item { width: auto; flex: 0 0 auto; border-left: none; border-radius: var(--radius-sm); padding: 6px 12px; font-size: 12px; }
  .page-sidebar.ep-nav-wrap .page-sidebar-item.active::before { display: none; }
  .page-sidebar.ep-nav-wrap .page-sidebar-icon { display: none; }
}
@media (max-width: 680px) {
  .ep-ins-grid { grid-template-columns: 1fr 1fr; }
  .ep-states { grid-template-columns: repeat(6, 1fr); }
}
</style>

