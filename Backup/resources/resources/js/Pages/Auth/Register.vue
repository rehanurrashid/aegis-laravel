<template>
  <!-- ════════ WHO WE SERVE MODAL ════════ -->
  <div class="modal-overlay" :class="{ open: wwsOpen }" @click.self="wwsOpen = false">
    <div class="modal-box">
      <button class="modal-close" @click="wwsOpen = false" aria-label="Close">
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
      <h2 class="modal-title">Who We Serve</h2>
      <div class="modal-section">
        <div class="modal-section-title">Health &amp; Wellbeing Practitioners</div>
        <ul class="modal-section-list">
          <li>Therapists, Mental Health Counselors, Psychologists, Psychiatrists</li>
          <li>Clinical Social Workers, Marriage &amp; Family Therapists</li>
          <li>Prescribers (MD, DO, NP, PA), Nutritionists, Dietitians</li>
          <li>Massage Therapists, Functional Medicine Providers, Chiropractors</li>
          <li>Doulas (Birth, Postpartum, End of Life, Full Spectrum), Midwives</li>
          <li>Health Coaches, Wellness Coaches, Yoga Therapists</li>
          <li>Art, Music, Dance/Movement, and Recreational Therapists</li>
        </ul>
      </div>
      <div class="modal-section">
        <div class="modal-section-title">Business Partners</div>
        <ul class="modal-section-list">
          <li>Accounting &amp; Billing specialists</li>
          <li>Credentialing consultants and Legal advisors</li>
          <li>Marketing, Web Design, and IT Support professionals</li>
        </ul>
      </div>
      <div class="modal-section">
        <div class="modal-section-title">Continuity Stewards</div>
        <ul class="modal-section-list">
          <li>Estate lawyers specializing in practice succession</li>
          <li>Licensed professionals with practitioner expertise</li>
        </ul>
      </div>
      <div class="modal-section">
        <div class="modal-section-title">Support Steward</div>
        <ul class="modal-section-list">
          <li>Designated Support Representatives</li>
          <li>Administrative staff and personal assistants</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="onboarding-layout">

    <!-- ════════════════════════════ LEFT PANEL ════════════════════════════ -->
    <div class="panel-left">
      <img src="/onboarding/aegis-bg.svg" class="panel-left-bg" alt="" aria-hidden="true">

      <div class="brand-logo">
        <span class="brand-logo-text">Aegis</span>
      </div>

      <div class="panel-left-content">
        <div class="panel-left-eyebrow">Practitioner Platform</div>
        <h1 class="panel-left-title">Protect Your Practice. Connect Your Network.</h1>
        <p class="panel-left-body">Aegis is a comprehensive platform designed to streamline referrals, manage professional networks, and facilitate secure communication between practitioners and their partners.</p>

        <div class="panel-left-features">
          <div class="panel-feature">
            <div class="panel-feature-icon">
              <svg fill="none" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div class="panel-feature-text">
              <strong>Continuity of Care Planning</strong>
              Emergency preparedness and practice succession built in.
            </div>
          </div>
          <div class="panel-feature">
            <div class="panel-feature-icon">
              <svg fill="none" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="panel-feature-text">
              <strong>Professional Network</strong>
              Connect with practitioners, Continuity Stewards, and business partners.
            </div>
          </div>
          <div class="panel-feature">
            <div class="panel-feature-icon">
              <svg fill="none" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div class="panel-feature-text">
              <strong>Secure &amp; HIPAA-Ready</strong>
              End-to-end encrypted messaging and document management.
            </div>
          </div>
        </div>

        <!-- Progress Pips -->
        <div class="progress-track">
          <div
            v-for="(state, i) in pipStates"
            :key="i"
            class="progress-pip"
            :class="state"
          ></div>
        </div>
      </div>

      <div class="panel-left-footer">
        <p>© 2025 Aegis Platform. All rights reserved.<br>
          <a href="#" @click.prevent="wwsOpen = true" style="color: rgba(255,255,255,0.55); text-decoration: underline;">Who We Serve</a>
          &nbsp;·&nbsp;
          <a href="#" @click.prevent style="color: rgba(255,255,255,0.55); text-decoration: underline;">Privacy Policy</a>
        </p>
      </div>
    </div>

    <!-- ════════════════════════════ RIGHT PANEL ════════════════════════════ -->
    <div class="panel-right" ref="rightPanel">
      <div class="panel-right-inner">

        <!-- Global error alert -->
        <div class="alert-error" :class="{ visible: errorMsg }">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          <span>{{ errorMsg }}</span>
        </div>

        <!-- ════════ STEP 1: ROLE SELECTION ════════ -->
        <div class="step-screen" :class="{ active: step === 1 }">
          <div class="step-header">
            <div class="step-eyebrow">Step 1 of 10</div>
            <h2 class="step-title">Select Your Role</h2>
            <p class="step-subtitle">Choose the role that best describes how you'll use Aegis. This personalizes your onboarding and dashboard.</p>
          </div>

          <div class="role-cards">
            <div class="role-card" :class="{ selected: ud.role === 'practitioner' }" @click="selectRole('practitioner')">
              <div class="role-card-icon">
                <svg viewBox="0 0 24 24" stroke-width="1.8" fill="none"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
              </div>
              <div class="role-card-body">
                <div class="role-card-title">Practitioner Partner</div>
                <div class="role-card-desc">Doctor, therapist, specialist, and health professionals building and protecting their practice.</div>
                <div class="role-card-badge">
                  <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                  Subscription required
                </div>
              </div>
              <div class="role-card-action">
                <svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
              </div>
            </div>

            <div class="role-card" :class="{ selected: ud.role === 'business-partner' }" @click="selectRole('business-partner')">
              <div class="role-card-icon">
                <svg viewBox="0 0 24 24" stroke-width="1.8" fill="none"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
              </div>
              <div class="role-card-body">
                <div class="role-card-title">Business Partner</div>
                <div class="role-card-desc">Billing, legal, consultants, and business service providers working with health professionals.</div>
                <div class="role-card-badge">
                  <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                  Subscription required
                </div>
              </div>
              <div class="role-card-action">
                <svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
              </div>
            </div>

            <div class="role-card" :class="{ selected: ud.role === 'executor' }" @click="selectRole('executor')">
              <div class="role-card-icon">
                <svg viewBox="0 0 24 24" stroke-width="1.8" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              </div>
              <div class="role-card-body">
                <div class="role-card-title">Continuity Steward</div>
                <div class="role-card-desc">Practice succession specialists and licensed professionals supporting practitioner continuity.</div>
                <div class="role-card-badge free">
                  <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                  Free via invitation
                </div>
              </div>
              <div class="role-card-action">
                <svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
              </div>
            </div>

            <div class="role-card" :class="{ selected: ud.role === 'dsr' }" @click="selectRole('dsr')">
              <div class="role-card-icon">
                <svg viewBox="0 0 24 24" stroke-width="1.8" fill="none"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
              </div>
              <div class="role-card-body">
                <div class="role-card-title">Support Steward</div>
                <div class="role-card-desc">Administrative staff, personal representatives, and designated support for practitioners.</div>
                <div class="role-card-badge free">
                  <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                  Invitation only
                </div>
              </div>
              <div class="role-card-action">
                <svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
              </div>
            </div>
          </div>

          <div class="signin-row">Already have an account? <a href="/login">Sign In</a></div>
        </div>

        <!-- ════════ STEP 2: CONTINUITY STEWARD PATHWAY ════════ -->
        <div class="step-screen" :class="{ active: step === 2 }">
          <a class="back-link" @click="goToStep(1)">
            <svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Role Selection
          </a>
          <div class="step-header">
            <div class="step-eyebrow">{{ eyebrowFor(2) }}</div>
            <h2 class="step-title">Continuity Steward Pathway</h2>
            <p class="step-subtitle">Select how you are joining Aegis as a Continuity Steward.</p>
          </div>

          <div class="path-card">
            <div class="path-card-header">
              <div class="path-card-icon">
                <svg viewBox="0 0 24 24" stroke-width="1.8" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              </div>
              <div>
                <div class="path-card-title">Join via Practitioner Invitation</div>
                <span class="path-card-tag">Free Account</span>
              </div>
            </div>
            <p class="path-card-desc">You have received an invitation from a practitioner to serve as their Continuity Steward. This creates a free, linked account at no cost.</p>
            <button class="btn btn-primary" @click="executorInvitation">Continue with Invitation</button>
          </div>

          <div class="path-card">
            <div class="path-card-header">
              <div class="path-card-icon">
                <svg viewBox="0 0 24 24" stroke-width="1.8" fill="none"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
              </div>
              <div>
                <div class="path-card-title">Create Business Continuity Steward Account</div>
                <span class="path-card-tag paid">Subscription Required</span>
              </div>
            </div>
            <p class="path-card-desc">Offer continuity services as an independent business. You will send invitations to practitioners to link accounts and build your professional network.</p>
            <button class="btn btn-outline" @click="executorBusiness">Continue as Business</button>
          </div>
        </div>

        <!-- ════════ STEP 2b: INVITATION CODE ════════ -->
        <div class="step-screen" :class="{ active: step === '2b' }">
          <a class="back-link" @click="goToStep(2)">
            <svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Pathway Selection
          </a>
          <div class="step-header">
            <div class="step-eyebrow">{{ eyebrowFor('2b') }}</div>
            <h2 class="step-title">Enter Invitation Code</h2>
            <p class="step-subtitle">Please enter the invitation code sent to you by your practitioner.</p>
          </div>

          <div class="form-group">
            <label class="form-label">Invitation Code <span class="optional">(Leave blank to preview)</span></label>
            <input type="text" class="form-control" v-model="ud.invitationCode" placeholder="Enter code or leave blank to preview" style="text-align: center; font-size: 18px; letter-spacing: 3px; font-family: var(--font-serif); font-weight: 700;" maxlength="20">
          </div>

          <div class="info-box">
            <div class="info-box-icon-row">
              <svg fill="none" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <p>This code links your account to the practitioner who invited you and ensures your account remains free of charge.</p>
            </div>
          </div>

          <button class="btn btn-primary btn-full" @click="validateInvitation">Validate &amp; Continue</button>

          <div class="signin-row" style="margin-top: 16px;">
            Don't have an invitation code? <a href="#" @click.prevent="goToStep(2)">Go Back</a>
          </div>
        </div>

        <!-- ════════ STEP 3-DSR: SUPPORT STEWARD INVITATION ════════ -->
        <div class="step-screen" :class="{ active: step === '3-dsr' }">
          <a class="back-link" @click="goToStep(1)">
            <svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Role Selection
          </a>
          <div class="step-header">
            <div class="step-eyebrow">{{ eyebrowFor('3-dsr') }}</div>
            <h2 class="step-title">Accept Your Support Steward Invitation</h2>
            <p class="step-subtitle">Enter the invitation code from your email to activate your free Support Steward account.</p>
          </div>

          <div class="form-group">
            <label class="form-label">Invitation Code</label>
            <input type="text" class="form-control" v-model="ud.dsrInvitationCode" placeholder="Enter code from your email" style="text-align: center; font-size: 18px; letter-spacing: 3px; font-family: var(--font-serif); font-weight: 700;" maxlength="20">
          </div>

          <div class="info-box">
            <div class="info-box-icon-row">
              <svg fill="none" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <p>Your invitation code links your free account to the Practitioner Partner who invited you and applies the role and access permissions they assigned. Support Steward accounts are always free.</p>
            </div>
          </div>

          <button class="btn btn-primary btn-full" @click="validateDsrInvitation">Validate &amp; Continue</button>

          <details style="margin-top: 22px; border: 1px solid var(--border); border-radius: var(--radius); padding: 14px 16px; background: var(--surface-2);">
            <summary style="cursor: pointer; font-family: var(--font-sans); font-size: 13px; font-weight: 600; color: var(--text); list-style: none; display: flex; align-items: center; gap: 8px;">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Don't have an invitation yet?
            </summary>
            <p class="invite-only-body" style="margin-top: 12px; font-size: 13px; line-height: 1.6;">Support Stewards are part of a <strong>Practitioner Partner's operational team</strong> &mdash; handling administrative, billing, scheduling, or coordination tasks under their authorization. For HIPAA compliance and audit integrity, the practitioner who's bringing you on must define your role and access permissions before your account can be created. There's no self-signup by design.<br><br>If you're <strong>expecting an invitation</strong>, please check your email (including your spam folder). If you should be a Support Steward at a practice but haven't received an invitation, contact the practitioner directly &mdash; they can add you from their <strong>Support Stewards</strong> page and set your role and permissions before sending the invite.<br><br><strong>Looking for a different role?</strong> If you're an independent professional who serves multiple practitioners, you're likely looking for the <strong>Continuity Steward</strong> role &mdash; <a href="#" @click.prevent="goToStep(1)" style="color: var(--gold-dark); font-weight: 700; text-decoration: none;">go back and select that instead</a>.</p>
          </details>

          <div class="signin-row" style="margin-top: 16px;">Already have an account? <a href="/login">Sign In</a></div>
        </div>

        <!-- ════════ STEP 3: CREATE ACCOUNT ════════ -->
        <div class="step-screen" :class="{ active: step === 3 }">
          <a class="back-link" @click="step3Back">
            <svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            {{ step3BackLabel }}
          </a>
          <div class="step-header">
            <div class="step-eyebrow">{{ eyebrowFor(3) }}</div>
            <h2 class="step-title">Create Your Account</h2>
            <p class="step-subtitle">{{ roleSubtitle }}</p>
          </div>

          <form @submit.prevent="submitSignup" novalidate>

            <!-- Practitioner-Specific Fields -->
            <div v-show="ud.role === 'practitioner'">
              <div class="form-group">
                <label class="form-label">Title <span class="optional">(Optional)</span></label>
                <select class="form-select" v-model="ud.title">
                  <option value="">Select title</option>
                  <option>Mr.</option><option>Mrs.</option><option>Ms.</option>
                  <option>Mx.</option><option>Dr.</option><option>Prof.</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Provider Type</label>
                <select class="form-select" v-model="ud.providerType">
                  <option value="">Select provider type</option>
                  <option>Therapist / Mental Health Counselor</option>
                  <option>Psychologist</option><option>Psychiatrist</option>
                  <option>Clinical Social Worker</option>
                  <option>Marriage and Family Therapist</option>
                  <option>Substance Abuse / Addiction Counselor</option>
                  <option>Prescriber (MD, DO, NP, PA)</option>
                  <option>Nutritionist / Dietitian</option>
                  <option>Registered Dietitian</option>
                  <option>Massage Therapist</option>
                  <option>Functional Medicine Provider</option>
                  <option>Naturopathic Doctor</option>
                  <option>Chiropractor</option><option>Acupuncturist</option>
                  <option>Physical Therapist</option>
                  <option>Occupational Therapist</option>
                  <option>Speech-Language Pathologist</option>
                  <option>Doula (Birth, Postpartum, End of Life, Full Spectrum)</option>
                  <option>Midwife</option>
                  <option>Lactation Consultant (IBCLC)</option>
                  <option>Health Coach</option><option>Wellness Coach</option>
                  <option>Yoga Therapist</option><option>Art Therapist</option>
                  <option>Music Therapist</option>
                  <option>Dance/Movement Therapist</option>
                  <option>Recreational Therapist</option>
                  <option>Pastoral Counselor</option>
                  <option>Reiki / Energy Healing Practitioner</option>
                  <option>Homeopath</option>
                  <option>Herbalist</option>
                  <option>Ayurveda Practitioner</option>
                  <option>Certified Nurse-Midwife (CNM)</option>
                  <option>Sleep Specialist</option>
                  <option>Genetic Counselor</option>
                  <option>Personal Trainer</option>
                  <option>Somatic Practitioner</option>
                  <option value="other">Other (Please Specify)</option>
                </select>
              </div>

              <div class="form-group" v-show="ud.providerType === 'other'">
                <label class="form-label">Specify Provider Type</label>
                <input type="text" class="form-control" v-model="ud.otherProviderType" placeholder="Enter your provider type">
              </div>

              <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" v-model="ud.practitionerFullName" placeholder="Enter your full name">
              </div>

              <div class="form-group">
                <label class="form-label">Credentials <span class="optional">(Optional)</span></label>
                <select class="form-select" v-model="ud.credentials">
                  <option value="">Select credentials</option>
                  <option>MD</option><option>DO</option><option>PhD</option>
                  <option>PsyD</option><option>LCSW</option><option>LMFT</option>
                  <option>LPC</option><option>LPCC</option><option>LMHC</option>
                  <option>NP</option><option>PA-C</option><option>RN</option>
                  <option>MSW</option><option>CADC</option><option>ICADC</option>
                  <option>RD</option><option>RDN</option><option>LMT</option>
                  <option>DC</option><option>LAc</option><option>DPT</option>
                  <option>OTR/L</option><option>CCC-SLP</option><option>IBCLC</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <div class="form-group" v-show="ud.credentials === 'other'">
                <label class="form-label">Specify Credentials</label>
                <input type="text" class="form-control" v-model="ud.otherCredentials" placeholder="Enter your credentials">
              </div>

              <div class="form-group">
                <label class="form-label">Business / Practice Name <span class="optional">(Optional)</span></label>
                <input type="text" class="form-control" v-model="ud.businessName" placeholder="Practice or business name">
              </div>

              <div class="form-group">
                <label class="form-label">Primary Practice State</label>
                <select class="form-select" v-model="ud.practiceState">
                  <option value="">Select state</option>
                  <option v-for="s in usStates" :key="s">{{ s }}</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Primary Language</label>
                <select class="form-select" v-model="ud.primaryLanguage">
                  <option value="">Select language</option>
                  <option>English</option><option>Spanish</option>
                  <option>Mandarin Chinese</option><option>French</option>
                  <option>German</option><option>Italian</option>
                  <option>Portuguese</option><option>Russian</option>
                  <option>Arabic</option><option>Hindi</option>
                  <option>Japanese</option><option>Korean</option>
                  <option>Vietnamese</option><option>Tagalog</option>
                  <option>Polish</option><option>Greek</option>
                  <option>Hebrew</option><option>Urdu</option>
                  <option>Turkish</option><option>Dutch</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <div class="form-group" v-show="ud.primaryLanguage === 'other'">
                <label class="form-label">Specify Language</label>
                <input type="text" class="form-control" v-model="ud.otherLanguage" placeholder="Enter your primary language">
              </div>
            </div>

            <!-- Standard Full Name (non-practitioner) -->
            <div class="form-group" v-show="ud.role !== 'practitioner'">
              <label class="form-label">Full Name</label>
              <input type="text" class="form-control" v-model="ud.fullName" placeholder="Enter your full name">
            </div>

            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input type="email" class="form-control" :class="{ error: fieldErr.email }" v-model="ud.email" placeholder="your@email.com">
              <div class="form-error" :class="{ visible: fieldErr.email }">
                <svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                {{ fieldErr.email }}
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Phone Number <span class="optional">(Optional)</span></label>
              <input type="tel" class="form-control" v-model="ud.phone" placeholder="+1 (555) 000-0000">
            </div>

            <div class="form-group">
              <label class="form-label">Password</label>
              <div class="password-wrap">
                <input :type="showPwd ? 'text' : 'password'" class="form-control" :class="{ error: fieldErr.password }" v-model="ud.password" placeholder="Create a strong password">
                <button type="button" class="password-toggle" @click="showPwd = !showPwd" tabindex="-1">
                  <svg v-if="!showPwd" fill="none" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg v-else fill="none" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
              <div class="form-error" :class="{ visible: fieldErr.password }" style="margin-bottom: 2px;">
                <svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                {{ fieldErr.password }}
              </div>
              <div class="password-reqs">
                <div class="req-item" :class="pwReq.length ? 'valid' : 'invalid'">
                  <svg v-if="pwReq.length" fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
                  <svg v-else fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                  8+ characters
                </div>
                <div class="req-item" :class="pwReq.upper ? 'valid' : 'invalid'">
                  <svg v-if="pwReq.upper" fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
                  <svg v-else fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                  Uppercase
                </div>
                <div class="req-item" :class="pwReq.number ? 'valid' : 'invalid'">
                  <svg v-if="pwReq.number" fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
                  <svg v-else fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                  Number
                </div>
                <div class="req-item" :class="pwReq.special ? 'valid' : 'invalid'">
                  <svg v-if="pwReq.special" fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
                  <svg v-else fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                  Special char
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Confirm Password</label>
              <div class="password-wrap">
                <input :type="showConfirm ? 'text' : 'password'" class="form-control" v-model="ud.confirmPassword" placeholder="Re-enter your password">
                <button type="button" class="password-toggle" @click="showConfirm = !showConfirm" tabindex="-1">
                  <svg v-if="!showConfirm" fill="none" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg v-else fill="none" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
              <div class="form-error" :class="{ visible: ud.confirmPassword && ud.confirmPassword !== ud.password }">
                <svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                Passwords do not match
              </div>
            </div>

            <label class="checkbox-item" style="margin-top: 4px; margin-bottom: 10px;">
              <input type="checkbox" v-model="ud.agreeTerms" required>
              <span class="checkbox-label">I have read and agree to the Terms of Service and Privacy Policy.</span>
            </label>

            <label class="checkbox-item" style="margin-bottom: 22px;">
              <input type="checkbox" v-model="ud.agreePrivacy" required>
              <span class="checkbox-label">I have read and agree to the Privacy Policy.</span>
            </label>

            <label class="checkbox-item" style="margin-bottom: 22px;">
              <input type="checkbox" v-model="ud.emailOptIn">
              <span class="checkbox-label">I agree to receive platform updates and communications<small>You can unsubscribe at any time</small></span>
            </label>

            <button type="submit" class="btn btn-primary btn-full">Create Account</button>
          </form>

          <div class="signin-row">Already have an account? <a href="/login">Sign In</a></div>
        </div>

        <!-- ════════ STEP 4: EMAIL VERIFICATION ════════ -->
        <div class="step-screen" :class="{ active: step === 4 }">
          <div class="step-header">
            <div class="step-eyebrow">{{ eyebrowFor(4) }}</div>
            <h2 class="step-title">Verify Your Email</h2>
            <p class="step-subtitle">We sent a 6-digit verification code to <strong style="color: var(--text); font-weight: 600;">{{ ud.email || 'your email' }}</strong></p>
          </div>

          <div class="otp-group">
            <template v-for="(d, i) in otp" :key="i">
              <div v-if="i === 3" class="otp-divider">—</div>
              <input
                type="text" maxlength="1" inputmode="numeric"
                class="otp-input" :class="{ filled: otp[i] }"
                :ref="el => { if (el) otpRefs[i] = el }"
                v-model="otp[i]"
                @input="onOtpInput(i, $event)"
                @keydown="onOtpKeydown(i, $event)"
              >
            </template>
          </div>

          <div class="timer-bar">
            <div class="timer-text">Code expires in: <span>{{ timerText }}</span></div>
          </div>

          <button class="btn btn-primary btn-full" @click="verifyEmail">Verify Email</button>

          <div class="resend-row">
            Didn't receive the code? <a href="#" @click.prevent="resendCode">{{ resendLabel }}</a>
          </div>
        </div>

        <!-- ════════ STEP 5: TWO-FACTOR AUTH ════════ -->
        <div class="step-screen" :class="{ active: step === 5 }">
          <div class="step-header">
            <div class="step-eyebrow">{{ eyebrowFor(5) }}</div>
            <h2 class="step-title">Two-Factor Authentication</h2>
            <p class="step-subtitle">Enter the 6-digit code from your authenticator app to secure your account.</p>
          </div>

          <div class="form-group" style="margin-bottom: 28px;">
            <label class="form-label">Authentication Code</label>
            <input type="text" class="form-control" v-model="ud.authCode" placeholder="_ _ _ _ _ _" maxlength="6" style="text-align: center; font-size: 22px; letter-spacing: 8px; font-family: var(--font-serif); font-weight: 700;" inputmode="numeric">
          </div>

          <button class="btn btn-primary btn-full" @click="verify2FA">Verify</button>

          <div class="resend-row" style="margin-top: 16px;">
            Having trouble? <a href="#" @click.prevent>Use Backup Code</a>
          </div>
        </div>

        <!-- ════════ STEP 6: PURPOSE SELECTION ════════ -->
        <div class="step-screen" :class="{ active: step === 6 }">
          <div class="step-header">
            <div class="step-eyebrow">{{ eyebrowFor(6) }}</div>
            <h2 class="step-title">What brings you to Aegis?</h2>
            <p class="step-subtitle">Select all that apply. This helps personalize your dashboard and recommendations.</p>
          </div>

          <!-- Practitioner Purposes -->
          <div v-show="ud.role === 'practitioner'" class="purpose-list">
            <label v-for="p in practitionerPurposes" :key="p.value" class="purpose-item" :class="{ selected: ud.purposeP.includes(p.value) }">
              <input type="checkbox" :value="p.value" v-model="ud.purposeP">
              <span class="purpose-item-label">{{ p.label }}</span>
            </label>
          </div>

          <!-- Business Purposes -->
          <div v-show="ud.role === 'business-partner'" class="purpose-list">
            <label v-for="p in businessPurposes" :key="p.value" class="purpose-item" :class="{ selected: ud.purposeB.includes(p.value) }">
              <input type="checkbox" :value="p.value" v-model="ud.purposeB">
              <span class="purpose-item-label">{{ p.label }}</span>
            </label>
          </div>

          <!-- Executor Purposes -->
          <div v-show="ud.role === 'executor'" class="purpose-list">
            <label v-for="p in executorPurposes" :key="p.value" class="purpose-item" :class="{ selected: ud.purposeE.includes(p.value) }">
              <input type="checkbox" :value="p.value" v-model="ud.purposeE">
              <span class="purpose-item-label">{{ p.label }}</span>
            </label>
          </div>

          <!-- Supporter limit notice — practitioners only -->
          <div v-show="ud.role === 'practitioner'" style="margin-bottom: 16px;">
            <div class="info-box" style="border-color: var(--gold); background: rgba(196,169,106,0.06);">
              <div class="info-box-icon-row">
                <svg fill="none" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" style="color: var(--gold-dark); flex-shrink: 0;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <p><strong style="color: var(--text);">Support Team Limit</strong><br>You may designate between <strong style="color: var(--gold-dark);">2 and 5 supporters total</strong> — combining your Continuity Stewards and Support Stewards. This limit applies across your entire account.</p>
              </div>
            </div>
          </div>

          <button class="btn btn-primary btn-full" @click="purposeContinue">Continue</button>
        </div>

        <!-- ════════ STEP 7: BUSINESS PARTNER PROFILE ════════ -->
        <div class="step-screen" :class="{ active: step === 7 }">
          <a class="back-link" @click="goToStep(6)">
            <svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Back
          </a>
          <div class="step-header">
            <div class="step-eyebrow">{{ eyebrowFor(7) }}</div>
            <h2 class="step-title">Business Partner Profile</h2>
            <p class="step-subtitle">Tell us about your business structure so practitioners can find and understand your services.</p>
          </div>

          <div class="form-group">
            <label class="form-label">Business Structure</label>
            <select class="form-select" v-model="ud.businessStructure" @change="ud.businessType = ud.businessStructure || null">
              <option value="">Select structure</option>
              <option value="freelancer">Freelancer / Independent Consultant</option>
              <option value="agency">Agency / Company</option>
            </select>
          </div>

          <!-- Freelancer Fields -->
          <div v-show="ud.businessStructure === 'freelancer'">
            <div class="form-group">
              <label class="form-label">Professional Title</label>
              <input type="text" class="form-control" v-model="ud.freelancerTitle" placeholder="e.g., Accountant, Marketing Consultant">
            </div>
            <div class="form-group">
              <label class="form-label">Years of Experience</label>
              <select class="form-select" v-model="ud.freelancerExp">
                <option value="">Select years</option>
                <option>0–2 years</option><option>3–5 years</option>
                <option>6–10 years</option><option>10+ years</option>
              </select>
            </div>
          </div>

          <!-- Agency Fields -->
          <div v-show="ud.businessStructure === 'agency'">
            <div class="form-group">
              <label class="form-label">Company Name</label>
              <input type="text" class="form-control" v-model="ud.companyName" placeholder="Your company name">
            </div>
            <div class="form-group">
              <label class="form-label">Company Website <span class="optional">(Optional)</span></label>
              <input type="url" class="form-control" v-model="ud.companyWebsite" placeholder="https://yourcompany.com">
            </div>
            <div class="form-group">
              <label class="form-label">Team Size</label>
              <select class="form-select" v-model="ud.teamSize">
                <option value="">Select size</option>
                <option>1–5 employees</option><option>6–10 employees</option>
                <option>11–25 employees</option><option>26–50 employees</option>
                <option>50+ employees</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Years in Business</label>
              <select class="form-select" v-model="ud.agencyYears">
                <option value="">Select years</option>
                <option>0–2 years</option><option>3–5 years</option>
                <option>6–10 years</option><option>10+ years</option>
              </select>
            </div>
          </div>

          <!-- Common Business Fields -->
          <div v-show="ud.businessStructure">
            <div class="form-group">
              <label class="form-label">Primary Services</label>
              <select class="form-select multi-select" multiple v-model="ud.primaryServices">
                <option v-for="s in primaryServiceOptions" :key="s">{{ s }}</option>
              </select>
              <div class="form-hint">Hold Ctrl / Cmd to select multiple</div>
            </div>

            <div class="form-group">
              <label class="form-label">Service Area</label>
              <input type="text" class="form-control" v-model="ud.serviceArea" placeholder="e.g., California, West Coast, Nationwide">
            </div>

            <!-- Pricing Structure -->
            <div style="border: 1.5px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 18px; background: var(--surface-2);">
              <div class="section-label" style="margin-top: 0;">Pricing Structure</div>
              <p style="font-size: 12px; color: var(--text-2); margin-bottom: 14px; line-height: 1.5;">Help practitioners understand your pricing upfront to find the right match.</p>

              <div class="form-group">
                <label class="form-label">How do you typically charge? <span class="optional">(Select all that apply)</span></label>
                <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 6px;">
                  <label class="checkbox-item" style="margin-bottom: 0;">
                    <input type="checkbox" value="hourly" v-model="ud.pricingType">
                    <span class="checkbox-label">Hourly Rate</span>
                  </label>
                  <label class="checkbox-item" style="margin-bottom: 0;">
                    <input type="checkbox" value="project" v-model="ud.pricingType">
                    <span class="checkbox-label">Project-Based</span>
                  </label>
                  <label class="checkbox-item" style="margin-bottom: 0;">
                    <input type="checkbox" value="retainer" v-model="ud.pricingType">
                    <span class="checkbox-label">Monthly Retainer</span>
                  </label>
                  <label class="checkbox-item" style="margin-bottom: 0;">
                    <input type="checkbox" value="custom" v-model="ud.pricingType">
                    <span class="checkbox-label">Custom (Varies by Service)</span>
                  </label>
                </div>
              </div>

              <div v-show="ud.pricingType.includes('hourly')">
                <label class="form-label">Hourly Rate Range</label>
                <div class="form-row">
                  <div class="form-group" style="margin-bottom: 0;"><div class="input-group"><span class="input-group-prefix">$</span><input type="number" class="form-control" v-model="ud.hourlyMin" placeholder="Min" min="0"></div></div>
                  <div class="form-group" style="margin-bottom: 0;"><div class="input-group"><span class="input-group-prefix">$</span><input type="number" class="form-control" v-model="ud.hourlyMax" placeholder="Max" min="0"></div></div>
                </div>
              </div>

              <div v-show="ud.pricingType.includes('project')">
                <label class="form-label">Typical Project Range</label>
                <div class="form-row">
                  <div class="form-group" style="margin-bottom: 0;"><div class="input-group"><span class="input-group-prefix">$</span><input type="number" class="form-control" v-model="ud.projectMin" placeholder="Min" min="0"></div></div>
                  <div class="form-group" style="margin-bottom: 0;"><div class="input-group"><span class="input-group-prefix">$</span><input type="number" class="form-control" v-model="ud.projectMax" placeholder="Max" min="0"></div></div>
                </div>
              </div>

              <div v-show="ud.pricingType.includes('retainer')">
                <label class="form-label">Monthly Retainer Range</label>
                <div class="form-row">
                  <div class="form-group" style="margin-bottom: 0;"><div class="input-group"><span class="input-group-prefix">$</span><input type="number" class="form-control" v-model="ud.retainerMin" placeholder="Min" min="0"></div></div>
                  <div class="form-group" style="margin-bottom: 0;"><div class="input-group"><span class="input-group-prefix">$</span><input type="number" class="form-control" v-model="ud.retainerMax" placeholder="Max" min="0"></div></div>
                </div>
              </div>

              <div v-show="ud.pricingType.includes('custom')">
                <div class="info-box" style="margin-bottom: 0;">
                  <div class="info-box-icon-row">
                    <svg fill="none" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <p>You selected custom pricing. Add detailed pricing to your profile after signup.</p>
                  </div>
                </div>
              </div>

              <label class="checkbox-item" style="margin-top: 14px;">
                <input type="checkbox" v-model="ud.displayPricing">
                <span class="checkbox-label">Display my pricing publicly on my profile<small>Practitioners can see your rates when browsing for services</small></span>
              </label>
            </div>
          </div>

          <button class="btn btn-primary btn-full" @click="businessInfoContinue">Continue to Subscription</button>
        </div>

        <!-- ════════ STEP 8-EXECUTOR-FREE ════════ -->
        <div class="step-screen" :class="{ active: step === '8-executor-free' }">
          <div class="step-header">
            <div class="step-eyebrow">Final step</div>
            <h2 class="step-title">Your free account</h2>
            <p class="step-subtitle">As an invited Continuity Steward, there's nothing to pay. Your account is linked to the practitioner who invited you.</p>
          </div>

          <div style="background: var(--surface-2); border: 1.5px solid var(--border); border-left: 3px solid var(--gold); border-radius: var(--radius-lg); padding: 22px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
              <div style="width: 36px; height: 36px; background: var(--green-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="18" height="18" fill="none" stroke="var(--green-dark)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </div>
              <div style="font-family: var(--font-sans); font-size: 16px; font-weight: 600; color: var(--text);">Free — linked to one practitioner</div>
            </div>
            <p style="font-family: var(--font-sans); font-size: 13px; color: var(--text-2); line-height: 1.6; margin: 0;">Your account stays free while you serve the practitioner who invited you. To take on additional practitioners, you can upgrade later from your account settings.</p>
          </div>

          <div style="background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-lg); padding: 18px 20px; margin-bottom: 24px;">
            <div style="font-family: var(--font-sans); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-2); margin-bottom: 10px;">If you need to serve more practitioners later</div>
            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 6px; font-family: var(--font-sans); font-size: 13px; color: var(--text-2); line-height: 1.55;">
              <li><strong style="color: var(--text);">2–40 practitioners</strong> — $49/mo or $429/yr</li>
              <li><strong style="color: var(--text);">41+ practitioners</strong> — custom enterprise quote</li>
            </ul>
          </div>

          <button class="btn btn-primary btn-full" @click="finishExecutorFree">Create my free account</button>
        </div>

        <!-- ════════ STEP 8-DSR-FREE ════════ -->
        <div class="step-screen" :class="{ active: step === '8-dsr-free' }">
          <div class="step-header">
            <div class="step-eyebrow">Final step</div>
            <h2 class="step-title">Your free account</h2>
            <p class="step-subtitle">As an invited Support Steward, your account is free. Your role and access permissions are set by the Practitioner Partner who invited you.</p>
          </div>

          <div style="background: var(--surface-2); border: 1.5px solid var(--border); border-left: 3px solid var(--gold); border-radius: var(--radius-lg); padding: 22px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
              <div style="width: 36px; height: 36px; background: var(--green-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="18" height="18" fill="none" stroke="var(--green-dark)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              </div>
              <div style="font-family: var(--font-sans); font-size: 16px; font-weight: 600; color: var(--text);">Free &mdash; linked to your practice</div>
            </div>
            <p style="font-family: var(--font-sans); font-size: 13px; color: var(--text-2); line-height: 1.6; margin: 0;">Your Support Steward account is covered by the practitioner's subscription &mdash; you'll never be charged. You'll have access to the specific tasks they authorized (administrative, billing, scheduling, or coordination), all tracked and logged for HIPAA compliance.</p>
          </div>

          <div style="background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-lg); padding: 18px 20px; margin-bottom: 24px;">
            <div style="font-family: var(--font-sans); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-2); margin-bottom: 10px;">What this means</div>
            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; font-family: var(--font-sans); font-size: 13px; color: var(--text-2); line-height: 1.55;">
              <li><strong style="color: var(--text);">One practice per account</strong> &mdash; if a second practitioner needs to invite you, they'll send a separate invitation for a separate Support Steward account.</li>
              <li><strong style="color: var(--text);">Scoped permissions</strong> &mdash; you can only see and act on what the practitioner authorized, with every action audit-logged.</li>
              <li><strong style="color: var(--text);">Always free</strong> &mdash; Support Steward accounts have no subscription fee.</li>
            </ul>
          </div>

          <button class="btn btn-primary btn-full" @click="finishDsrFree">Create my free account</button>
        </div>

        <!-- ════════ STEP 8: SUBSCRIPTION ════════ -->
        <div class="step-screen" :class="{ active: step === 8 }">
          <div class="step-header">
            <div class="step-eyebrow">{{ eyebrowFor(8) }}</div>
            <h2 class="step-title">Choose Your Plan</h2>
            <p class="step-subtitle">Select the subscription that works best for you. Both plans include the same core features.</p>
          </div>

          <!-- Founding Member Banner (practitioners only) -->
          <div v-show="ud.role === 'practitioner'" style="background:linear-gradient(135deg, rgba(196,169,106,0.12), rgba(196,169,106,0.04)); border:1.5px solid var(--gold); border-radius:var(--radius-lg); padding:16px 18px; margin-bottom:18px; position:relative;">
            <div style="position:absolute; top:-9px; left:16px; background:var(--gold-dark); color:#fff; font-size:9.5px; font-weight:700; padding:3px 10px; border-radius:var(--radius-sm); letter-spacing:0.8px; text-transform:uppercase;">Founding Member</div>
            <div style="display:flex; align-items:flex-start; gap:12px; margin-top:4px;">
              <div style="width:32px; height:32px; background:var(--gold); border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="16" height="16" fill="none" stroke="#1a1a1a" stroke-width="2.2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              </div>
              <div style="flex:1;">
                <div style="font-family:var(--font-sans); font-size:13.5px; font-weight:700; color:var(--text); margin-bottom:4px;">You qualify as a Founding Member</div>
                <p style="font-family:var(--font-sans); font-size:12.5px; color:var(--text-2); line-height:1.55; margin:0;">As one of Aegis's first 100 providers, you receive <strong style="color:var(--gold-dark);">2 additional Continuity Stewards free for life</strong>, plus <strong style="color:var(--gold-dark);">free marketing ads each year</strong> (1 with Continuity Access · 2 with Continuity Practice). These perks are locked in for as long as your account remains active.</p>
              </div>
            </div>
          </div>

          <!-- MAAT Banner (practitioners only) -->
          <div class="maat-banner" :class="{ visible: ud.role === 'practitioner' }">
            <div class="maat-banner-header">
              <div class="maat-icon">
                <svg viewBox="0 0 24 24" stroke-width="1.8" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              </div>
              <div>
                <div class="maat-title">Premium Continuity Steward Service by MAAT</div>
                <div class="maat-price">+$29/month or +$290/year</div>
              </div>
            </div>
            <p style="font-size: 12.5px; color: var(--text-2); margin-bottom: 12px; line-height: 1.55;">Secure your practice future with pre-vetted, practitioner-specialized licensed Continuity Stewards.</p>
            <div class="maat-features">
              <div class="maat-feature"><svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Licensed &amp; Insured</div>
              <div class="maat-feature"><svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Emergency Response</div>
              <div class="maat-feature"><svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Succession Planning</div>
              <div class="maat-feature"><svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Peace of Mind Guarantee</div>
            </div>
            <div class="maat-checkbox-row" :class="{ active: ud.addMaat }">
              <label class="checkbox-item" style="margin-bottom: 0;">
                <input type="checkbox" v-model="ud.addMaat">
                <span class="checkbox-label">Add MAAT Professional Continuity Steward Service to my plan<small>You can cancel this add-on at any time</small></span>
              </label>
            </div>
          </div>

          <!-- Billing toggle (practitioners only) -->
          <div v-show="ud.role === 'practitioner'" class="billing-toggle" style="display:flex; justify-content:center; gap:0; margin-bottom:18px; background:var(--surface-2); border:1px solid var(--border); border-radius:999px; padding:4px; width:fit-content; margin-left:auto; margin-right:auto;">
            <button type="button" class="billing-opt" @click="selectBillingCycle('monthly')" :style="billingBtnStyle('monthly')">Monthly</button>
            <button type="button" class="billing-opt" @click="selectBillingCycle('annual')" :style="billingBtnStyle('annual')">Annual <span style="font-size:10.5px; color:var(--gold-dark); font-weight:700; margin-left:4px;">SAVE 2 MONTHS</span></button>
          </div>

          <!-- Practitioner Tier Cards -->
          <div v-show="ud.role === 'practitioner'" class="pricing-grid">
            <div class="pricing-card" :class="{ recommended: ud.planTier === 'access' }" @click="selectPractitionerTier('access')">
              <div class="pricing-card-name">Continuity Access</div>
              <div class="pricing-card-price"><span>{{ tierPrices.access.price }}</span><span>{{ tierPrices.access.period }}</span></div>
              <div class="pricing-card-note">Essential continuity planning · 1 CS + 2 Support Stewards</div>
              <button class="btn btn-outline btn-sm" style="width:100%;">Select Access</button>
            </div>

            <div class="pricing-card" :class="{ recommended: ud.planTier === 'practice' }" @click="selectPractitionerTier('practice')">
              <div class="pricing-badge">Recommended</div>
              <div class="pricing-card-name">Continuity Practice</div>
              <div class="pricing-card-price"><span>{{ tierPrices.practice.price }}</span><span>{{ tierPrices.practice.period }}</span></div>
              <div class="pricing-card-note">Full dashboard · Up to 2 CS + 2 Support Stewards · Referrals &amp; Jobs</div>
              <button class="btn btn-gold btn-sm" style="width:100%;">Select Practice</button>
            </div>
          </div>

          <!-- Legacy Monthly/Annual cards (business partners + standalone CS) -->
          <div v-show="ud.role !== 'practitioner'" class="pricing-grid">
            <div class="pricing-card" @click="selectSubscriptionType('monthly')">
              <div class="pricing-card-name">Monthly</div>
              <div class="pricing-card-price">{{ legacyPrices.monthly }}<span>/mo</span></div>
              <div class="pricing-card-note">Flexible — cancel anytime</div>
              <button class="btn btn-outline btn-sm" style="width: 100%;">Select Monthly</button>
            </div>

            <div class="pricing-card recommended" @click="selectSubscriptionType('annual')">
              <div class="pricing-badge">Best Value</div>
              <div class="pricing-card-name">Annual</div>
              <div class="pricing-card-price">{{ legacyPrices.annual }}<span>/yr</span></div>
              <div class="pricing-card-note">Save 2 months with annual billing</div>
              <button class="btn btn-gold btn-sm" style="width: 100%;">Select Annual</button>
            </div>
          </div>

          <div class="section-label">What's Included</div>
          <ul class="features-list">
            <li v-for="f in subscriptionFeatures" :key="f">
              <svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>{{ f }}
            </li>
          </ul>
        </div>

        <!-- ════════ STEP 9: PAYMENT ════════ -->
        <div class="step-screen" :class="{ active: step === 9 }">
          <div class="step-header">
            <div class="step-eyebrow">{{ eyebrowFor(9) }}</div>
            <h2 class="step-title">Payment Information</h2>
            <p class="step-subtitle">Complete your subscription to unlock full platform access.</p>
          </div>

          <div class="plan-summary">
            <div>
              <div class="plan-summary-name">{{ plan.name }}</div>
              <div class="plan-summary-detail">{{ plan.details }}</div>
            </div>
            <div class="plan-summary-price">{{ plan.price }}</div>
          </div>

          <div class="card-type-icons">
            <div class="card-type-icon">VISA</div>
            <div class="card-type-icon">MC</div>
            <div class="card-type-icon">AMEX</div>
            <div class="card-type-icon">DISC</div>
          </div>

          <div class="form-group">
            <label class="form-label">Card Number</label>
            <input type="text" class="form-control" v-model="ud.cardNumber" @input="formatCardNumber" placeholder="1234 5678 9012 3456" maxlength="19" inputmode="numeric">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Expiry Date</label>
              <input type="text" class="form-control" v-model="ud.cardExpiry" @input="formatExpiry" placeholder="MM / YY" maxlength="7" inputmode="numeric">
            </div>
            <div class="form-group">
              <label class="form-label">CVV</label>
              <input type="text" class="form-control" v-model="ud.cardCvv" placeholder="123" maxlength="4" inputmode="numeric">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Billing Address</label>
            <input type="text" class="form-control" v-model="ud.billingAddress" placeholder="Street address">
          </div>

          <div class="form-row">
            <div class="form-group" style="grid-column: span 1;">
              <input type="text" class="form-control" v-model="ud.billingCity" placeholder="City">
            </div>
            <div class="form-group" style="grid-column: span 1;">
              <input type="text" class="form-control" v-model="ud.billingZip" placeholder="ZIP" maxlength="10">
            </div>
          </div>

          <button class="btn btn-primary btn-full" @click="paymentComplete">Complete Payment</button>

          <div style="text-align: center; margin-top: 14px; font-size: 11.5px; color: var(--text-2);">
            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="vertical-align: middle; margin-right: 4px; display:inline-block;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Secured with SSL encryption · Cancel anytime
          </div>
        </div>

        <!-- ════════ STEP 10: WELCOME ════════ -->
        <div class="step-screen" :class="{ active: step === 10 }">
          <div class="success-ring">
            <svg fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div class="success-header">
            <div class="step-eyebrow" style="text-align: center;">Account Created</div>
            <h2 class="step-title" style="text-align: center;">Welcome, {{ ud.computedName || 'to Aegis' }}!</h2>
            <p class="step-subtitle" style="text-align: center;">Your account has been created successfully. Here's how to get started.</p>
          </div>

          <!-- Practitioner Next Steps -->
          <div v-show="nextStepsRole === 'practitioner'">
            <div class="success-next-steps">
              <div class="success-next-title">Your Next Steps</div>
              <div class="next-step-item"><div class="next-step-num">1</div><div class="next-step-text">Complete your professional profile and verify credentials</div></div>
              <div class="next-step-item"><div class="next-step-num">2</div><div class="next-step-text">Connect with other Practitioner Partners</div></div>
              <div class="next-step-item"><div class="next-step-num">3</div><div class="next-step-text">Designate your Support Steward</div></div>
              <div class="next-step-item"><div class="next-step-num">4</div><div class="next-step-text">Find and select your Continuity Steward</div></div>
              <div class="next-step-item"><div class="next-step-num">5</div><div class="next-step-text">Execute and securely store your Continuity Plan</div></div>
              <div class="next-step-item"><div class="next-step-num">6</div><div class="next-step-text">Promote Your Professional Offerings</div></div>
              <div class="next-step-item" style="border-bottom: none;"><div class="next-step-num">7</div><div class="next-step-text">Explore business partner practice support services</div></div>
            </div>
            <div class="info-box" style="margin-top: 16px; border-color: var(--gold); background: rgba(196,169,106,0.06);">
              <div class="info-box-icon-row">
                <svg fill="none" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" style="color: var(--gold-dark);"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <p><strong style="color: var(--text);">Support Team Limit</strong><br>Practitioners can designate between <strong style="color: var(--gold-dark);">2 and 5 supporters total</strong> — this includes your Continuity Stewards and Support Stewards combined. Choose your support team carefully.</p>
              </div>
            </div>
          </div>

          <!-- Business Next Steps -->
          <div v-show="nextStepsRole === 'business-partner'">
            <div class="success-next-steps">
              <div class="success-next-title">Your Next Steps</div>
              <div class="next-step-item"><div class="next-step-num">1</div><div class="next-step-text">Complete your service profile and portfolio</div></div>
              <div class="next-step-item"><div class="next-step-num">2</div><div class="next-step-text">Set up your payment processing</div></div>
              <div class="next-step-item"><div class="next-step-num">3</div><div class="next-step-text">Browse posted jobs from practitioners</div></div>
              <div class="next-step-item"><div class="next-step-num">4</div><div class="next-step-text">Submit proposals and apply to jobs</div></div>
              <div class="next-step-item" style="border-bottom: none;"><div class="next-step-num">5</div><div class="next-step-text">Configure your notification preferences</div></div>
            </div>
          </div>

          <!-- Executor Next Steps -->
          <div v-show="nextStepsRole === 'executor'">
            <div class="success-next-steps">
              <div class="success-next-title">Your Next Steps</div>
              <div class="next-step-item"><div class="next-step-num">1</div><div class="next-step-text">Complete your Continuity Steward profile and credentials</div></div>
              <div class="next-step-item"><div class="next-step-num">2</div><div class="next-step-text">Review continuity of care planning protocols</div></div>
              <div class="next-step-item"><div class="next-step-num">3</div><div class="next-step-text">Set your availability and service parameters</div></div>
              <div class="next-step-item"><div class="next-step-num">4</div><div class="next-step-text">Connect with practitioners in your network</div></div>
              <div class="next-step-item" style="border-bottom: none;"><div class="next-step-num">5</div><div class="next-step-text">Access Continuity Steward resources and templates</div></div>
            </div>
          </div>

          <!-- DSR Next Steps -->
          <div v-show="nextStepsRole === 'dsr'">
            <div class="success-next-steps">
              <div class="success-next-title">Your Next Steps</div>
              <div class="next-step-item"><div class="next-step-num">1</div><div class="next-step-text">Review the role and permissions granted by your practitioner</div></div>
              <div class="next-step-item"><div class="next-step-num">2</div><div class="next-step-text">Complete your profile and accept the access agreement</div></div>
              <div class="next-step-item"><div class="next-step-num">3</div><div class="next-step-text">Familiarize yourself with the tasks you've been authorized for</div></div>
              <div class="next-step-item"><div class="next-step-num">4</div><div class="next-step-text">Review the HIPAA &amp; audit-log expectations for Support Stewards</div></div>
              <div class="next-step-item" style="border-bottom: none;"><div class="next-step-num">5</div><div class="next-step-text">Reach out to your practitioner with any questions about scope</div></div>
            </div>
          </div>

          <button class="btn btn-primary btn-full" :disabled="form.processing" @click="submitRegistration">
            <span v-if="form.processing" class="btn-spinner"></span>
            {{ form.processing ? 'Creating Account…' : 'Go to Dashboard' }}
          </button>

          <div style="text-align: center; margin-top: 14px;">
            <a href="#" class="btn-ghost" style="font-size: 12.5px;" @click.prevent="submitRegistration">Complete Your Profile First</a>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onBeforeUnmount } from 'vue';
import { useForm } from '@inertiajs/vue3';

/* ════════ STATE ════════ */
const step = ref(1);
const wwsOpen = ref(false);
const showPwd = ref(false);
const showConfirm = ref(false);
const errorMsg = ref('');
const rightPanel = ref(null);
const nextStepsRole = ref(null);
const fieldErr = reactive({ email: '', password: '' });

const ud = reactive({
  role: null,
  executorPath: null,
  invitationCode: '',
  dsrInvitationCode: '',
  // practitioner
  title: '', providerType: '', otherProviderType: '', practitionerFullName: '',
  credentials: '', otherCredentials: '', businessName: '', practiceState: '', primaryLanguage: '', otherLanguage: '',
  // standard
  fullName: '',
  email: '', phone: '', password: '', confirmPassword: '',
  agreeTerms: false, agreePrivacy: false, emailOptIn: false,
  // purposes
  purposeP: [], purposeB: [], purposeE: [],
  // business partner
  businessStructure: '', businessType: null,
  freelancerTitle: '', freelancerExp: '',
  companyName: '', companyWebsite: '', teamSize: '', agencyYears: '',
  primaryServices: [], serviceArea: '',
  pricingType: [], hourlyMin: '', hourlyMax: '', projectMin: '', projectMax: '', retainerMin: '', retainerMax: '',
  displayPricing: true,
  // subscription
  billingCycle: 'monthly', planTier: 'practice', subscriptionType: null, addMaat: false,
  // 2fa / payment
  authCode: '',
  cardNumber: '', cardExpiry: '', cardCvv: '', billingAddress: '', billingCity: '', billingZip: '',
  computedName: '',
});

const otp = reactive(['', '', '', '', '', '']);
const otpRefs = {};

/* ════════ STATIC DATA ════════ */
const usStates = ['Alabama','Alaska','Arizona','Arkansas','California','Colorado','Connecticut','Delaware','Florida','Georgia','Hawaii','Idaho','Illinois','Indiana','Iowa','Kansas','Kentucky','Louisiana','Maine','Maryland','Massachusetts','Michigan','Minnesota','Mississippi','Missouri','Montana','Nebraska','Nevada','New Hampshire','New Jersey','New Mexico','New York','North Carolina','North Dakota','Ohio','Oklahoma','Oregon','Pennsylvania','Rhode Island','South Carolina','South Dakota','Tennessee','Texas','Utah','Vermont','Virginia','Washington','West Virginia','Wisconsin','Wyoming','District of Columbia'];

const primaryServiceOptions = ['Accounting','Billing and Credentialing','Marketing','Web Design','IT Support','Practice Management Consultant','Practitioner IT Consultant','SEO','Attorneys','Virtual Assistant','Web Application Development','Liability Insurance','CPA','Bookkeeping','Human Resources','Registered Agent','Virtual Mailbox','Consultant','Branding Consultant','Provider Coach','Provider Therapist','Supervising Physician','Clinical Supervisors','Practice Management Support','Other Practice Support Services'];

const practitionerPurposes = [
  { value: 'continuity', label: 'Continuity of Care Emergency Planning (Continuity Plan, Continuity Steward, Support Steward Preparation)' },
  { value: 'practice-management', label: 'Practice Management (tracking training, renewals)' },
  { value: 'network', label: 'Network Development' },
  { value: 'care-coord', label: 'Care Coordination' },
  { value: 'innovation', label: 'Innovation and Practice Development (connect, learn, partner, expand scope)' },
  { value: 'resources', label: 'Resource Access' },
];
const businessPurposes = [
  { value: 'b1', label: 'Offer practice support services to health practitioners' },
  { value: 'b2', label: 'Grow my client base as a Practitioner Partner' },
  { value: 'b3', label: 'Establish long-term service contracts' },
];
const executorPurposes = [
  { value: 'e1', label: 'Provide practice succession planning services' },
  { value: 'e2', label: 'Support continuity of care emergency planning' },
  { value: 'e3', label: 'Connect with other Continuity Stewards' },
  { value: 'e4', label: 'Manage and support multiple practitioner accounts' },
];

/* ════════ ROLE → user_type (backend) ════════ */
const roleToUserType = { practitioner: 1, executor: 2, dsr: 3, 'business-partner': 4 };

const roleSubtitle = computed(() => {
  if (ud.role === 'practitioner') return 'Join as a Practitioner Partner';
  if (ud.role === 'business-partner') return 'Join as a Business Partner';
  if (ud.role === 'executor') {
    return ud.executorPath === 'invitation'
      ? 'Join as a Continuity Steward (via Invitation)'
      : 'Join as a Continuity Steward (Business Account)';
  }
  if (ud.role === 'dsr') return 'Join as a Support Steward';
  return 'Join the Aegis platform to connect, collaborate, and grow.';
});

const step3BackLabel = computed(() => {
  if (ud.role === 'dsr') return 'Back to Invitation';
  if (ud.role === 'executor') return ud.executorPath === 'invitation' ? 'Back to Invitation Code' : 'Back to Pathway Selection';
  return 'Back to Role Selection';
});

/* ════════ STEP SEQUENCES (dynamic numbering + pips) ════════ */
const stepSequences = {
  practitioner: [1, 3, 4, 5, 6, 8, 9, 10],
  'business-partner': [1, 3, 4, 5, 6, 7, 8, 9, 10],
  'executor-business': [1, 2, 3, 4, 5, 6, 8, 9, 10],
  'executor-invitation': [1, 2, 3, 4, 5, 6, '8-executor-free', 10],
  dsr: [1, '3-dsr', 3, 4, 5, '8-dsr-free', 10],
};

function seqKey() {
  let k = ud.role || 'practitioner';
  if (k === 'executor') k = ud.executorPath === 'invitation' ? 'executor-invitation' : 'executor-business';
  return k;
}

function eyebrowFor(stepId) {
  const seq = stepSequences[seqKey()] || stepSequences.practitioner;
  const lookup = stepId === '2b' ? 2 : stepId;
  const idx = seq.indexOf(lookup);
  if (idx === -1) return '';
  return `Step ${idx + 1} of ${seq.length}`;
}

const pipStates = computed(() => {
  const seq = stepSequences[seqKey()] || stepSequences.practitioner;
  const lookup = step.value === '2b' ? 2 : step.value;
  let idx = seq.indexOf(lookup);
  if (idx === -1) idx = 0;
  return seq.map((s, i) => (i < idx ? 'done' : i === idx ? 'active' : ''));
});

/* ════════ NAVIGATION ════════ */
function goToStep(id) {
  step.value = id;
  if (rightPanel.value) rightPanel.value.scrollTop = 0;
}

function selectRole(role) {
  ud.role = role;
  if (role === 'dsr') { goToStep('3-dsr'); return; }
  if (role === 'executor') { goToStep(2); return; }
  goToStep(3);
}

function step3Back() {
  if (ud.role === 'dsr') return goToStep('3-dsr');
  if (ud.role === 'executor') return goToStep(ud.executorPath === 'invitation' ? '2b' : 2);
  return goToStep(1);
}

/* Step 2 — executor pathway */
function executorInvitation() { ud.executorPath = 'invitation'; goToStep('2b'); }
function executorBusiness() { ud.executorPath = 'business'; goToStep(3); }
function validateInvitation() { goToStep(3); }
function validateDsrInvitation() { goToStep(3); }

/* ════════ STEP 3: SIGNUP VALIDATION ════════ */
const pwReq = computed(() => ({
  length: ud.password.length >= 8,
  upper: /[A-Z]/.test(ud.password),
  number: /[0-9]/.test(ud.password),
  special: /[^A-Za-z0-9]/.test(ud.password),
}));

function submitSignup() {
  errorMsg.value = '';
  fieldErr.email = '';
  fieldErr.password = '';

  const name = ud.role === 'practitioner' ? ud.practitionerFullName : ud.fullName;
  if (!name || !name.trim()) { errorMsg.value = 'Please enter your full name.'; return; }
  if (!ud.email || !ud.email.trim()) { fieldErr.email = 'Email address is required.'; return; }
  if (ud.password.length < 8) { fieldErr.password = 'Password must be at least 8 characters.'; return; }
  if (ud.password !== ud.confirmPassword) { errorMsg.value = 'Passwords do not match.'; return; }
  if (!ud.agreeTerms || !ud.agreePrivacy) { errorMsg.value = 'Please accept the Terms of Service and Privacy Policy to continue.'; return; }

  ud.computedName = name.trim();
  goToStep(4);
  startTimer();
}

/* ════════ STEP 4: OTP ════════ */
function onOtpInput(i, e) {
  const val = e.target.value.replace(/\D/g, '').slice(0, 1);
  otp[i] = val;
  if (val && i < 5 && otpRefs[i + 1]) otpRefs[i + 1].focus();
}
function onOtpKeydown(i, e) {
  if (e.key === 'Backspace' && !otp[i] && i > 0 && otpRefs[i - 1]) otpRefs[i - 1].focus();
}

const timerText = ref('4:55');
const resendLabel = ref('Resend');
let timerInterval = null;
function startTimer() {
  clearInterval(timerInterval);
  let seconds = 295;
  timerInterval = setInterval(() => {
    seconds--;
    if (seconds <= 0) { clearInterval(timerInterval); timerText.value = '0:00'; return; }
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    timerText.value = `${m}:${String(s).padStart(2, '0')}`;
  }, 1000);
}
function resendCode() {
  startTimer();
  resendLabel.value = 'Sent!';
  setTimeout(() => { resendLabel.value = 'Resend'; }, 3000);
}
function verifyEmail() { goToStep(5); }

/* ════════ STEP 5: 2FA ════════ */
function verify2FA() {
  if (ud.role === 'dsr') { goToStep('8-dsr-free'); return; }
  goToStep(6);
}

/* ════════ STEP 6: PURPOSE ════════ */
function purposeContinue() {
  if (ud.role === 'business-partner') { goToStep(7); return; }
  if (ud.role === 'executor' && ud.executorPath === 'invitation') { goToStep('8-executor-free'); return; }
  goToStep(8);
  if (ud.role !== 'practitioner') selectSubscriptionDefaults();
}

/* ════════ STEP 7: BUSINESS PARTNER ════════ */
function businessInfoContinue() {
  if (!ud.businessType) { errorMsg.value = 'Please select your business structure.'; return; }
  errorMsg.value = '';
  goToStep(8);
  selectSubscriptionDefaults();
}

/* ════════ STEP 8: SUBSCRIPTION ════════ */
const tierBasePrices = {
  access: { monthly: 39, annual: 429 },
  practice: { monthly: 79, annual: 790 },
};
const tierPrices = computed(() => {
  const c = ud.billingCycle;
  const period = c === 'monthly' ? '/mo' : '/yr';
  return {
    access: { price: `$${tierBasePrices.access[c]}`, period },
    practice: { price: `$${tierBasePrices.practice[c]}`, period },
  };
});
const legacyPrices = computed(() => {
  if (ud.role === 'business-partner') {
    return ud.businessType === 'agency'
      ? { monthly: '$149', annual: '$1490' }
      : { monthly: '$69', annual: '$690' };
  }
  // standalone (business) continuity steward
  return { monthly: '$49', annual: '$429' };
});

function billingBtnStyle(cycle) {
  const active = ud.billingCycle === cycle;
  return {
    border: 'none',
    background: active ? 'var(--gold)' : 'transparent',
    color: active ? '#1a1a1a' : 'var(--text-2)',
    fontFamily: 'var(--font-sans)',
    fontSize: '12.5px',
    fontWeight: '600',
    padding: '8px 20px',
    borderRadius: '999px',
    cursor: 'pointer',
    transition: 'all 0.2s',
  };
}
function selectBillingCycle(cycle) { ud.billingCycle = cycle; }

function selectSubscriptionDefaults() {
  if (!ud.subscriptionType) ud.subscriptionType = 'monthly';
}

const subscriptionFeatures = computed(() => {
  if (ud.role === 'practitioner') {
    return ['Unlimited professional connections','Advanced search and filtering','Secure messaging and file sharing','Continuity Plan templates','Continuity of care planning tools','Priority support','Analytics and insights'];
  }
  if (ud.role === 'business-partner' && ud.businessType === 'agency') {
    return ['Team member accounts (up to 10)','Agency profile page with branding','Advanced project management tools','Client relationship management','White-label proposals and contracts','Payment processing (2.5% transaction fee)','Priority support','Advanced analytics dashboard'];
  }
  if (ud.role === 'business-partner') {
    return ['Business Profile Listing','Direct messaging with practitioners','Showcase Service Portfolio','Lead generation tools','Contract templates and management','Payment processing (2.9% transaction fee)','Standard support'];
  }
  if (ud.role === 'executor') {
    return ['Professional Continuity Steward profile','Practitioner network access','Secure document management','Case management tools','Emergency response protocols','Professional resources library','Priority support'];
  }
  return [];
});

const plan = reactive({ name: 'Monthly Plan', details: 'Billed monthly', price: '$69/mo' });

function selectPractitionerTier(tier) {
  ud.planTier = tier;
  ud.subscriptionType = ud.billingCycle;
  let price = tierBasePrices[tier][ud.subscriptionType];
  if (ud.addMaat) price += ud.subscriptionType === 'monthly' ? 29 : 290;
  const tierLabel = tier === 'access' ? 'Continuity Access' : 'Continuity Practice';
  const cycleLabel = ud.subscriptionType === 'monthly' ? 'Monthly' : 'Annual';
  const maatSuffix = ud.addMaat ? ' + MAAT Continuity Steward' : '';
  const periodSuffix = ud.subscriptionType === 'monthly' ? '/mo' : '/yr';
  plan.name = `${tierLabel} — ${cycleLabel}${maatSuffix}`;
  plan.details = ud.subscriptionType === 'monthly' ? 'Billed monthly' : 'Billed annually — save 2 months';
  plan.price = `$${price}${periodSuffix}`;
  goToStep(9);
}

function selectSubscriptionType(type) {
  ud.subscriptionType = type;
  const base = {
    'business-partner': { monthly: ud.businessType === 'agency' ? 149 : 69, annual: ud.businessType === 'agency' ? 1490 : 690 },
    executor: { monthly: 49, annual: 429 },
  };
  const roleKey = ud.role === 'business-partner' ? 'business-partner' : 'executor';
  const mPrice = base[roleKey].monthly;
  const aPrice = base[roleKey].annual;
  if (type === 'monthly') {
    plan.name = 'Monthly Plan';
    plan.details = 'Billed monthly';
    plan.price = `$${mPrice}/mo`;
  } else {
    plan.name = 'Annual Plan';
    plan.details = 'Billed annually — save 2 months';
    plan.price = `$${aPrice}/yr`;
  }
  goToStep(9);
}

/* ════════ STEP 9: PAYMENT FORMATTING ════════ */
function formatCardNumber(e) {
  ud.cardNumber = e.target.value.replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim().slice(0, 19);
}
function formatExpiry(e) {
  let v = e.target.value.replace(/\D/g, '');
  if (v.length >= 3) v = v.slice(0, 2) + ' / ' + v.slice(2, 4);
  ud.cardExpiry = v;
}
function paymentComplete() {
  nextStepsRole.value = ud.role;
  goToStep(10);
}

/* ════════ FREE-ACCOUNT FINISH ════════ */
function finishExecutorFree() { nextStepsRole.value = 'executor'; goToStep(10); }
function finishDsrFree() { nextStepsRole.value = 'dsr'; goToStep(10); }

/* ════════ STEP 10: REAL REGISTRATION (existing /register API) ════════ */
const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  user_type: null,
});

function submitRegistration() {
  errorMsg.value = '';
  form.name = ud.computedName || (ud.role === 'practitioner' ? ud.practitionerFullName : ud.fullName);
  form.email = ud.email;
  form.password = ud.password;
  form.password_confirmation = ud.confirmPassword;
  form.user_type = roleToUserType[ud.role] ?? 1;

  form.post('/register', {
    onError: (errors) => {
      // Surface validation failures back on the account step so they can be fixed.
      fieldErr.email = errors.email || '';
      fieldErr.password = errors.password || '';
      errorMsg.value = errors.email || errors.password || errors.name || errors.user_type ||
        'We could not create your account. Please review your details and try again.';
      goToStep(3);
    },
    // On success the controller logs the user in and redirects to their dashboard.
  });
}

onBeforeUnmount(() => clearInterval(timerInterval));
</script>

<style scoped>
/* ══════════════════════════════════════════════════
   LAYOUT — SPLIT SCREEN
   Tokens (--gold, --primary, --surface*, --radius*, fonts) come from
   the global Aegis design system in resources/css/app.css.
══════════════════════════════════════════════════ */
.onboarding-layout {
  position: fixed;
  inset: 0;
  display: flex;
  width: 100%;
  height: 100vh;
  overflow: hidden;
  background-color: var(--surface-2);
  font-family: var(--font-sans);
  color: var(--text);
  line-height: 1.5;
}

/* LEFT PANEL */
.panel-left {
  width: 42%;
  background: #1a140d;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 48px 52px;
  overflow: hidden;
  flex-shrink: 0;
  height: 100vh;
}

.panel-left-bg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  pointer-events: none;
  z-index: 0;
}

.brand-logo {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 12px;
}

.brand-logo-text {
  font-family: var(--font-serif);
  font-size: 26px;
  font-weight: 700;
  color: var(--text-inverted);
  letter-spacing: -0.5px;
  line-height: 1;
}

.panel-left-content {
  position: relative;
  z-index: 1;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 40px 0;
}

.panel-left-eyebrow {
  font-family: var(--font-sans);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: rgba(255,255,255,0.65);
  margin-bottom: 16px;
}

.panel-left-title {
  font-family: var(--font-serif);
  font-size: 36px;
  font-weight: 700;
  color: var(--text-inverted);
  line-height: 1.22;
  margin-bottom: 20px;
}

.panel-left-body {
  font-family: var(--font-sans);
  font-size: 13.5px;
  font-weight: 400;
  color: rgba(255,255,255,0.78);
  line-height: 1.65;
  max-width: 340px;
}

.panel-left-features {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin-top: 36px;
}

.panel-feature {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.panel-feature-icon {
  width: 32px;
  height: 32px;
  background: rgba(255,255,255,0.12);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 1px;
}

.panel-feature-icon svg {
  width: 15px;
  height: 15px;
  stroke: rgba(255,255,255,0.85);
}

.panel-feature-text {
  font-size: 12.5px;
  color: rgba(255,255,255,0.75);
  line-height: 1.5;
}

.panel-feature-text strong {
  display: block;
  font-weight: 600;
  color: rgba(255,255,255,0.92);
  font-size: 12px;
  margin-bottom: 1px;
}

.panel-left-footer {
  position: relative;
  z-index: 1;
}

.panel-left-footer p {
  font-size: 11px;
  color: rgba(255,255,255,0.45);
  line-height: 1.5;
}

/* PROGRESS TRACK */
.progress-track {
  margin-top: 32px;
  display: flex;
  gap: 5px;
  align-items: center;
}

.progress-pip {
  height: 3px;
  border-radius: 2px;
  background: rgba(255,255,255,0.2);
  transition: all 0.3s ease;
  flex: 1;
}

.progress-pip.active { background: var(--gold-light); }
.progress-pip.done { background: rgba(255,255,255,0.55); }

/* RIGHT PANEL */
.panel-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  overflow-x: hidden;
  background-color: var(--surface);
  height: 100vh;
}

.panel-right-inner {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 52px 64px;
  max-width: 560px;
  width: 100%;
  margin: 0 auto;
}

/* ══════════════════════════════════════════════════
   STEP SYSTEM
══════════════════════════════════════════════════ */
.step-screen {
  display: none;
  animation: stepIn 0.28s ease both;
}

.step-screen.active { display: block; }

@keyframes stepIn {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* STEP HEADER */
.step-header { margin-bottom: 32px; }

.step-eyebrow {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.8px;
  text-transform: uppercase;
  color: var(--gold-dark);
  margin-bottom: 8px;
}

.step-title {
  font-family: var(--font-serif);
  font-size: 26px;
  font-weight: 700;
  color: var(--text);
  line-height: 1.25;
  margin-bottom: 8px;
}

.step-subtitle {
  font-size: 13.5px;
  color: var(--text-2);
  line-height: 1.55;
}

/* BACK LINK */
.back-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-2);
  text-decoration: none;
  cursor: pointer;
  margin-bottom: 28px;
  padding: 6px 0;
  transition: color var(--transition);
}

.back-link:hover { color: var(--gold-dark); }
.back-link svg { width: 14px; height: 14px; stroke: currentColor; fill: none; }

/* ══════════════════════════════════════════════════
   FORM ELEMENTS
══════════════════════════════════════════════════ */
.form-group { margin-bottom: 18px; }

.form-label {
  display: block;
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: var(--text-2);
  margin-bottom: 6px;
}

.form-label .optional {
  font-weight: 400;
  letter-spacing: 0;
  text-transform: none;
  color: var(--text-2);
  margin-left: 4px;
}

.form-control,
.form-select {
  display: block;
  width: 100%;
  padding: 10px 13px;
  font-family: var(--font-sans);
  font-size: 13px;
  font-weight: 500;
  color: var(--text);
  background-color: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  transition: border-color var(--transition), box-shadow var(--transition);
  -webkit-appearance: none;
  appearance: none;
  outline: none;
}

.form-control::placeholder { color: var(--text-4); font-weight: 400; }

.form-control:focus,
.form-select:focus {
  border-color: var(--gold);
  box-shadow: var(--focus-ring);
}

.form-control.error {
  border-color: var(--red);
  box-shadow: 0 0 0 3px rgba(224,92,92,0.10);
}

.form-select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23a89f94' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 14px;
  padding-right: 36px;
  cursor: pointer;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.form-hint {
  font-size: 11px;
  color: var(--text-2);
  margin-top: 4px;
  line-height: 1.45;
}

.form-error {
  font-size: 11px;
  color: var(--red);
  margin-top: 5px;
  display: none;
  align-items: center;
  gap: 5px;
}

.form-error.visible { display: flex; }
.form-error svg { width: 11px; height: 11px; stroke: currentColor; flex-shrink: 0; fill: none; }

/* Input Group */
.input-group { position: relative; }

.input-group-prefix {
  position: absolute;
  left: 13px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 13px;
  font-weight: 500;
  color: var(--text-2);
  pointer-events: none;
  z-index: 1;
}

.input-group-prefix + .form-control { padding-left: 26px; }

/* Password toggle */
.password-wrap { position: relative; }

.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  padding: 4px;
  cursor: pointer;
  color: var(--text-2);
  display: flex;
  align-items: center;
  transition: color var(--transition);
}

.password-toggle:hover { color: var(--gold-dark); }
.password-toggle svg { width: 15px; height: 15px; stroke: currentColor; fill: none; }

/* ══════════════════════════════════════════════════
   CUSTOM CHECKBOX
══════════════════════════════════════════════════ */
.checkbox-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
  margin-bottom: 10px;
}

.checkbox-item input[type="checkbox"] {
  -webkit-appearance: none;
  appearance: none;
  width: 17px;
  height: 17px;
  border: 1.5px solid var(--border-dark);
  border-radius: 4px;
  background: var(--surface);
  cursor: pointer;
  flex-shrink: 0;
  margin-top: 1px;
  transition: all var(--transition);
  position: relative;
}

.checkbox-item input[type="checkbox"]:checked {
  background: var(--gold);
  border-color: var(--gold);
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpolyline points='2,6 5,9 10,3' fill='none' stroke='white' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: center;
  background-size: 11px;
}

.checkbox-item input[type="checkbox"]:focus {
  outline: none;
  box-shadow: var(--focus-ring-checkbox, 0 0 0 3px rgba(196,169,106,0.18));
}

.checkbox-label {
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.5;
  cursor: pointer;
}

.checkbox-label small {
  display: block;
  font-size: 11px;
  color: var(--text-2);
  margin-top: 2px;
}

/* ══════════════════════════════════════════════════
   PASSWORD REQUIREMENTS
══════════════════════════════════════════════════ */
.password-reqs {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 14px;
  margin-top: 10px;
}

.req-item {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: var(--text-2);
  transition: color var(--transition);
}

.req-item svg { width: 11px; height: 11px; stroke: currentColor; fill: none; }
.req-item.valid { color: var(--green); }
.req-item.invalid { color: var(--red); }

/* ══════════════════════════════════════════════════
   BUTTONS
══════════════════════════════════════════════════ */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 11px 22px;
  font-family: var(--font-sans);
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.2px;
  border-radius: var(--radius-full);
  border: 1.5px solid transparent;
  cursor: pointer;
  transition: all var(--transition);
  text-decoration: none;
  white-space: nowrap;
  -webkit-appearance: none;
  outline: none;
}

.btn svg { width: 14px; height: 14px; stroke: currentColor; fill: none; }

.btn-primary {
  background: var(--primary);
  color: var(--text-inverted);
  border-color: var(--primary);
}

.btn-primary:hover:not(:disabled) { background: var(--primary-mid); border-color: var(--primary-mid); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-gold {
  background: var(--gold);
  color: var(--text-inverted);
  border-color: var(--gold);
}

.btn-gold:hover { background: var(--gold-dark); border-color: var(--gold-dark); }

.btn-outline {
  background: transparent;
  color: var(--text-2);
  border-color: var(--border-dark);
}

.btn-outline:hover { border-color: var(--gold); color: var(--gold-dark); }

.btn-ghost {
  background: transparent;
  color: var(--gold-dark);
  border-color: transparent;
  padding-left: 0;
  padding-right: 0;
}

.btn-ghost:hover { color: var(--primary); }

.btn-full { width: 100%; }

.btn-sm { padding: 7px 16px; font-size: 12px; }

.btn-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255,255,255,0.35);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ══════════════════════════════════════════════════
   ROLE CARDS
══════════════════════════════════════════════════ */
.role-cards {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 28px;
}

.role-card {
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px 22px;
  cursor: pointer;
  transition: all var(--transition);
  background: var(--surface);
  display: flex;
  align-items: flex-start;
  gap: 18px;
}

.role-card:hover {
  border-color: var(--gold);
  box-shadow: var(--shadow-sm);
  transform: translateY(-1px);
}

.role-card.selected {
  border-color: var(--gold);
  background: rgba(196,169,106,0.04);
  box-shadow: 0 0 0 3px rgba(196,169,106,0.12);
}

.role-card-icon {
  width: 44px;
  height: 44px;
  background: rgba(196,169,106,0.1);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.role-card-icon svg {
  width: 20px;
  height: 20px;
  stroke: var(--gold-dark);
  fill: none;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.role-card-body { flex: 1; min-width: 0; }

.role-card-title {
  font-family: var(--font-serif);
  font-size: 16px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 3px;
}

.role-card-desc {
  font-size: 12px;
  color: var(--text-2);
  line-height: 1.5;
  margin-bottom: 10px;
}

.role-card-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: var(--radius-full);
  background: rgba(196,169,106,0.1);
  color: var(--gold-dark);
  border: 1px solid rgba(196,169,106,0.25);
}

.role-card-badge.free {
  background: rgba(76,175,125,0.1);
  color: var(--green);
  border-color: rgba(76,175,125,0.25);
}

.role-card-action {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  padding-top: 10px;
}

.role-card-action svg {
  width: 16px;
  height: 16px;
  stroke: var(--text-4);
  fill: none;
  transition: stroke var(--transition);
}

.role-card:hover .role-card-action svg,
.role-card.selected .role-card-action svg { stroke: var(--gold-dark); }

/* ══════════════════════════════════════════════════
   PATH CARDS
══════════════════════════════════════════════════ */
.path-card {
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  cursor: pointer;
  transition: all var(--transition);
  background: var(--surface);
  margin-bottom: 14px;
}

.path-card:hover { border-color: var(--gold); box-shadow: var(--shadow-sm); }

.path-card-header {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 12px;
}

.path-card-icon {
  width: 40px;
  height: 40px;
  background: rgba(196,169,106,0.1);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.path-card-icon svg {
  width: 18px;
  height: 18px;
  stroke: var(--gold-dark);
  fill: none;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.path-card-title {
  font-family: var(--font-serif);
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 2px;
}

.path-card-tag {
  font-size: 10px;
  font-weight: 600;
  color: var(--green);
  background: rgba(76,175,125,0.1);
  border: 1px solid rgba(76,175,125,0.2);
  border-radius: var(--radius-full);
  padding: 2px 8px;
  display: inline-block;
}

.path-card-tag.paid {
  color: var(--gold-dark);
  background: rgba(196,169,106,0.1);
  border-color: rgba(196,169,106,0.25);
}

.path-card-desc {
  font-size: 12.5px;
  color: var(--text-2);
  line-height: 1.55;
  margin-bottom: 16px;
}

/* ══════════════════════════════════════════════════
   OTP INPUTS
══════════════════════════════════════════════════ */
.otp-group {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin: 28px 0;
}

.otp-input {
  width: 52px;
  height: 58px;
  text-align: center;
  font-family: var(--font-serif);
  font-size: 24px;
  font-weight: 700;
  color: var(--text);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--surface);
  transition: all var(--transition);
  -webkit-appearance: none;
  outline: none;
}

.otp-input:focus { border-color: var(--gold); box-shadow: var(--focus-ring); }
.otp-input.filled { border-color: var(--gold-dark); background: rgba(196,169,106,0.05); }

.otp-divider {
  display: flex;
  align-items: center;
  color: var(--text-2);
  font-size: 18px;
  font-weight: 300;
}

.timer-bar { text-align: center; margin-bottom: 20px; }
.timer-text { font-size: 12px; color: var(--text-2); }
.timer-text span { font-weight: 700; color: var(--red); }

.resend-row { text-align: center; margin-top: 16px; }
.resend-row a { font-size: 12.5px; color: var(--gold-dark); text-decoration: none; font-weight: 600; cursor: pointer; }
.resend-row a:hover { text-decoration: underline; }

/* ══════════════════════════════════════════════════
   PURPOSE CHECKBOXES
══════════════════════════════════════════════════ */
.purpose-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 28px;
}

.purpose-item {
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  padding: 14px 16px;
  cursor: pointer;
  transition: all var(--transition);
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.purpose-item:hover { border-color: var(--gold-light); background: var(--surface-2); }
.purpose-item.selected { border-color: var(--gold); background: rgba(196,169,106,0.04); }

.purpose-item input[type="checkbox"] {
  -webkit-appearance: none;
  appearance: none;
  width: 16px;
  height: 16px;
  border: 1.5px solid var(--border-dark);
  border-radius: 4px;
  background: var(--surface);
  cursor: pointer;
  flex-shrink: 0;
  margin-top: 1px;
  transition: all var(--transition);
}

.purpose-item input[type="checkbox"]:checked {
  background: var(--gold);
  border-color: var(--gold);
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpolyline points='2,6 5,9 10,3' fill='none' stroke='white' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: center;
  background-size: 11px;
}

.purpose-item-label {
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.5;
  cursor: pointer;
}

/* ══════════════════════════════════════════════════
   PRICING CARDS
══════════════════════════════════════════════════ */
.pricing-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  margin-bottom: 24px;
}

.pricing-card {
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 22px;
  cursor: pointer;
  transition: all var(--transition);
  position: relative;
  background: var(--surface);
}

.pricing-card:hover {
  border-color: var(--gold);
  box-shadow: var(--shadow-sm);
  transform: translateY(-2px);
}

.pricing-card.recommended {
  border-color: var(--gold);
  background: rgba(196,169,106,0.04);
}

.pricing-badge {
  position: absolute;
  top: -10px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--gold);
  color: var(--text-inverted);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.8px;
  text-transform: uppercase;
  padding: 3px 12px;
  border-radius: var(--radius-full);
  white-space: nowrap;
}

.pricing-card-name {
  font-family: var(--font-sans);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--text-2);
  margin-bottom: 8px;
}

.pricing-card-price {
  font-family: var(--font-serif);
  font-size: 30px;
  font-weight: 700;
  color: var(--text);
  line-height: 1;
  margin-bottom: 4px;
}

.pricing-card-price span {
  font-size: 13px;
  font-weight: 400;
  font-family: var(--font-sans);
  color: var(--text-2);
}

.pricing-card-note {
  font-size: 11px;
  color: var(--text-2);
  line-height: 1.4;
  margin-bottom: 16px;
}

/* MAAT Banner */
.maat-banner {
  border: 1.5px solid var(--border);
  border-left: 3px solid var(--gold);
  border-radius: var(--radius);
  padding: 18px 20px;
  background: var(--surface-2);
  margin-bottom: 24px;
  display: none;
}

.maat-banner.visible { display: block; }

.maat-banner-header {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  margin-bottom: 12px;
}

.maat-icon {
  width: 40px;
  height: 40px;
  background: var(--gold-dark);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.maat-icon svg { width: 18px; height: 18px; stroke: white; fill: none; stroke-linecap: round; stroke-linejoin: round; }

.maat-title {
  font-family: var(--font-serif);
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 2px;
}

.maat-price { font-size: 12px; font-weight: 700; color: var(--gold-dark); }

.maat-features {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px 12px;
  margin-bottom: 14px;
}

.maat-feature {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  color: var(--text-2);
}

.maat-feature svg { width: 11px; height: 11px; stroke: var(--gold); flex-shrink: 0; fill: none; }

.maat-checkbox-row {
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 12px 14px;
  background: var(--surface);
  transition: all var(--transition);
}

.maat-checkbox-row.active { border-color: var(--gold); background: rgba(196,169,106,0.04); }

/* Features List */
.features-list { list-style: none; margin-top: 6px; padding: 0; }

.features-list li {
  display: flex;
  align-items: flex-start;
  gap: 9px;
  font-size: 12.5px;
  color: var(--text-2);
  padding: 6px 0;
  border-bottom: 1px solid var(--surface-3);
}

.features-list li:last-child { border-bottom: none; }

.features-list li svg { width: 13px; height: 13px; stroke: var(--gold); flex-shrink: 0; margin-top: 1px; fill: none; }

/* ══════════════════════════════════════════════════
   PLAN SUMMARY
══════════════════════════════════════════════════ */
.plan-summary {
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  padding: 16px 18px;
  background: var(--surface-2);
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.plan-summary-name {
  font-family: var(--font-serif);
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 2px;
}

.plan-summary-detail { font-size: 11.5px; color: var(--text-2); }

.plan-summary-price {
  font-family: var(--font-serif);
  font-size: 22px;
  font-weight: 700;
  color: var(--gold-dark);
  white-space: nowrap;
}

/* CARD TYPE ICONS */
.card-type-icons { display: flex; gap: 8px; margin-bottom: 14px; }

.card-type-icon {
  width: 40px;
  height: 26px;
  border: 1px solid var(--border);
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 8px;
  font-weight: 700;
  color: var(--text-4);
  background: var(--surface);
}

/* ══════════════════════════════════════════════════
   SUCCESS SCREEN
══════════════════════════════════════════════════ */
.success-ring {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(76,175,125,0.1);
  border: 2px solid rgba(76,175,125,0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 28px;
}

.success-ring svg { width: 32px; height: 32px; stroke: var(--green); fill: none; stroke-linecap: round; stroke-linejoin: round; }

.success-header { text-align: center; margin-bottom: 32px; }

.success-next-steps {
  border: 1.5px solid var(--border);
  border-left: 3px solid var(--gold);
  border-radius: var(--radius);
  padding: 20px 22px;
  background: var(--surface-2);
  margin-bottom: 28px;
}

.success-next-title {
  font-family: var(--font-serif);
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 14px;
}

.next-step-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 8px 0;
  border-bottom: 1px solid var(--surface-3);
}

.next-step-item:last-child { border-bottom: none; }

.next-step-num {
  width: 20px;
  height: 20px;
  background: var(--gold);
  color: white;
  border-radius: 50%;
  font-size: 10px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 1px;
}

.next-step-text { font-size: 12.5px; color: var(--text-2); line-height: 1.5; }

/* ══════════════════════════════════════════════════
   INFO BOX
══════════════════════════════════════════════════ */
.info-box {
  border: 1.5px solid var(--border);
  border-left: 3px solid var(--gold);
  border-radius: var(--radius-sm);
  padding: 14px 16px;
  background: rgba(196,169,106,0.04);
  margin-bottom: 20px;
}

.info-box-icon-row { display: flex; align-items: flex-start; gap: 10px; }
.info-box-icon-row svg { width: 15px; height: 15px; stroke: var(--gold-dark); flex-shrink: 0; margin-top: 1px; fill: none; }
.info-box p { font-size: 12.5px; color: var(--text-2); line-height: 1.55; }

.invite-only-body { font-size: 13px; color: var(--text-2); line-height: 1.6; }

/* ══════════════════════════════════════════════════
   GLOBAL ERROR ALERT
══════════════════════════════════════════════════ */
.alert-error {
  background: rgba(224,92,92,0.06);
  border: 1.5px solid rgba(224,92,92,0.22);
  border-left: 3px solid var(--red);
  border-radius: var(--radius-sm);
  padding: 10px 12px;
  margin-bottom: 18px;
  display: none;
  align-items: flex-start;
  gap: 8px;
  font-size: 12px;
  color: var(--red);
  line-height: 1.45;
}

.alert-error.visible { display: flex; }
.alert-error svg { width: 14px; height: 14px; stroke: currentColor; flex-shrink: 0; margin-top: 1px; fill: none; }

/* Divider / Section label */
.section-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.4px;
  text-transform: uppercase;
  color: var(--text-2);
  margin-bottom: 12px;
  margin-top: 22px;
}

/* Sign-in row */
.signin-row {
  text-align: center;
  margin-top: 24px;
  font-size: 12.5px;
  color: var(--text-2);
}

.signin-row a { color: var(--gold-dark); text-decoration: none; font-weight: 600; }
.signin-row a:hover { text-decoration: underline; }

/* Select multiple helper */
.multi-select { height: auto; min-height: 100px; }

/* ══════════════════════════════════════════════════
   WHO WE SERVE MODAL
══════════════════════════════════════════════════ */
.modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  backdrop-filter: blur(3px);
  z-index: 100;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-overlay.open { display: flex; }

.modal-box {
  background: var(--surface);
  border-radius: var(--radius-xl);
  padding: 36px;
  max-width: 480px;
  width: 100%;
  box-shadow: var(--shadow-xl);
  position: relative;
  max-height: 80vh;
  overflow-y: auto;
}

.modal-close {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 30px;
  height: 30px;
  background: var(--surface-3);
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition);
}

.modal-close:hover { background: var(--red-light); color: var(--red); }
.modal-close svg { width: 14px; height: 14px; stroke: currentColor; fill: none; }

.modal-title {
  font-family: var(--font-serif);
  font-size: 20px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 20px;
}

.modal-section { margin-bottom: 20px; }

.modal-section-title {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--gold-dark);
  margin-bottom: 8px;
}

.modal-section-list { list-style: none; padding: 0; }

.modal-section-list li {
  font-size: 12.5px;
  color: var(--text-2);
  padding: 4px 0;
  padding-left: 14px;
  position: relative;
  line-height: 1.5;
}

.modal-section-list li::before {
  content: '·';
  position: absolute;
  left: 0;
  color: var(--gold);
  font-weight: 700;
}

/* ══════════════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════════════ */
@media (max-width: 900px) {
  .panel-left { width: 36%; padding: 36px 32px; }
  .panel-right-inner { padding: 40px 36px; }
  .panel-left-title { font-size: 28px; }
}

@media (max-width: 720px) {
  .onboarding-layout { position: static; flex-direction: column; height: auto; min-height: 100vh; overflow: visible; }
  .panel-left { width: 100%; height: auto; min-height: auto; padding: 32px 28px; }
  .panel-left-content { padding: 24px 0; }
  .panel-left-features { display: none; }
  .panel-right { overflow: visible; height: auto; }
  .panel-right-inner { padding: 32px 24px; }
  .pricing-grid { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
  .maat-features { grid-template-columns: 1fr; }
}
</style>
