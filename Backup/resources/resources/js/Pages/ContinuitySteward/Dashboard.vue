<template>
  <AppLayout portal="continuity_steward" page-title="Dashboard" :has-emergency="hasEmergency">

    <!-- ═══ CRITICAL INCIDENT ALERT ═══ -->
    <div v-if="hasEmergency" class="dh-emergency">
      <svg class="dh-em-ico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <div class="dh-em-body">
        <div class="dh-em-title">Critical Incident Active — {{ incidentProvider }}</div>
        <div class="dh-em-sub">Reported {{ incident.triggered }} by {{ incident.reportedBy }} · {{ incident.type }}</div>
        <div class="dh-em-actions">
          <a href="/continuity-steward/continuity-management" class="btn btn-emergency btn-sm">Open Continuity Management</a>
          <a href="/continuity-steward/my-tasks" class="btn btn-outline btn-sm">Task List</a>
          <a href="/continuity-steward/continuity-management" class="btn btn-outline btn-sm"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Verify Incident</a>
          <a href="/continuity-steward/vault" class="btn btn-outline btn-sm"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Open Vault</a>
        </div>
      </div>
    </div>

    <!-- ═══ GREETING ═══ -->
    <div class="dh-greet">
      <div>
        <div class="dh-greet-eyebrow">{{ greeting }}</div>
        <div class="dh-greet-title">{{ titlePrefix }}{{ firstName }} <em>{{ lastName }},</em></div>
        <div class="dh-greet-sub">
          <template v-if="hasEmergency">
            Critical Incident protocol is active for <strong style="color:var(--text-2);">{{ incidentProvider }}</strong>. Work through your task list in order.
          </template>
          <template v-else>
            You are responsible for <strong style="color:var(--text-2);">{{ providersAssigned }} practitioners.</strong>
            {{ plansCertified }} plans certified · {{ certificationsDue > 0 ? certificationsDue + ' certification due soon' : 'all certifications current' }}.
          </template>
        </div>
        <div class="dh-greet-chips">
          <span class="cs-role-chip cs-chip-gold"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> {{ csTypeLabel }}</span>
          <span v-if="hasEmergency" class="cs-role-chip cs-chip-red"><span class="cs-chip-dot"></span> 1 Active Incident</span>
          <span v-else class="cs-role-chip cs-chip-neutral"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> {{ providersAssigned }} providers in standby</span>
        </div>
      </div>
      <div class="dh-greet-meta">
        <div class="dh-greet-mcell"><div class="dh-greet-mlabel">Providers</div><div class="dh-greet-mval">{{ providersAssigned }}</div></div>
        <div class="dh-greet-mcell"><div class="dh-greet-mlabel">Certified</div><div class="dh-greet-mval ok">{{ plansCertified }} / {{ providersAssigned }}</div></div>
        <div class="dh-greet-mcell"><div class="dh-greet-mlabel">Due</div><div class="dh-greet-mval" :class="{ warn: certificationsDue > 0 }">{{ certificationsDue }}</div></div>
      </div>
    </div>

    <!-- ═══ OVERVIEW BANNER ═══ -->
    <div style="margin-bottom:14px;background:var(--surface);border:1px solid var(--border);border-left:3px solid var(--gold-dark);border-radius:var(--radius-lg);padding:16px 20px;display:flex;align-items:center;gap:16px;box-shadow:var(--shadow-sm);">
      <div style="width:38px;height:38px;border-radius:var(--radius-sm);background:var(--icon-bg-gold);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--gold-dark);">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:2px;">Overview — Start Here</div>
        <div style="font-size:12px;color:var(--text-3);line-height:1.5;">Key terms, your role as Continuity Steward, and FAQs.</div>
      </div>
      <a href="/continuity-steward/overview" class="btn btn-outline btn-sm" style="flex-shrink:0;white-space:nowrap;">View Overview <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></a>
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
      <a href="/continuity-steward/profile" class="btn btn-primary btn-sm cs-profile-cta">Complete <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>

    <!-- ═══ CASELOAD CONTINUITY ═══ -->
    <div class="dh-sh">
      <div class="dh-sh-l">
        <div class="dh-sh-eyebrow">Your Stewardship</div>
        <div class="dh-sh-title">Continuity at the center</div>
      </div>
      <a class="dh-sh-link" href="#">View obligations <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>

    <div class="dh-continuity">
      <div class="dh-cn-left">
        <div class="dh-cn-eyebrow">
          <span class="dh-cn-pulse" :class="{ warn: hasEmergency }"></span>
          {{ certsOk }} of {{ certsTotal }} plans certified · {{ hasEmergency ? 'Incident active' : 'All in standby' }}
        </div>
        <div class="dh-cn-title" v-html="hasEmergency ? 'An incident is active.<br>Your caseload needs you.' : 'Your caseload is covered.<br>Plans are in good standing.'"></div>
        <div class="dh-cn-desc">Each plan below represents a practitioner who depends on you in a critical moment. Keep certifications current and your readiness attested.</div>

        <div class="dh-cn-plans">
          <div v-for="pv in providers" :key="pv.initials" class="dh-cn-plan has-profile">
            <div class="dh-cn-pavatar">{{ pv.initials }}</div>
            <div class="dh-cn-pinfo">
              <div class="dh-cn-pname">{{ pv.name }}</div>
              <div class="dh-cn-prole">{{ pv.csRole }} · {{ pv.specialty }}</div>
            </div>
            <span class="dh-cn-pstat" :class="pv.statClass">{{ pv.statLabel }}</span>
          </div>
        </div>

        <div class="dh-cn-actions">
          <template v-if="hasEmergency">
            <a href="/continuity-steward/continuity-management" class="btn btn-emergency btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg> Incident Protocol</a>
            <a href="/continuity-steward/my-tasks" class="btn btn-outline btn-sm">Task List</a>
          </template>
          <template v-else>
            <a href="#" class="btn btn-primary btn-sm">Review Obligations</a>
            <button class="btn btn-outline btn-sm">Readiness Attestation</button>
          </template>
        </div>
      </div>

      <div class="dh-cn-right">
        <div class="dh-cn-rhead">
          <div>
            <div class="dh-cn-rtag">Next certification due</div>
            <div class="dh-cn-due">{{ nextCertDue }}<small>{{ nextCertDays }} days remaining · last readiness {{ readinessLast }}</small></div>
          </div>
          <span style="color:var(--gold-dark);flex-shrink:0;display:inline-flex;align-items:center;line-height:0">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
          </span>
        </div>

        <div class="dh-cn-bar"><div class="dh-cn-bar-fill" :class="{ warn: certPct < 100 }" :style="{ width: certPct + '%' }"></div></div>
        <div class="dh-cn-bar-labels"><span>{{ certsOk }} certified</span><span>{{ certPct }}% health</span><span>{{ certsTotal }} total</span></div>

        <div class="dh-cn-todos">
          <div v-for="t in planStatuses" :key="t.label" class="dh-cn-todo" :class="{ done: t.done }">
            <svg v-if="t.done" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>
            {{ t.label }}
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ AT-A-GLANCE ═══ -->
    <div class="dh-sh">
      <div class="dh-sh-l">
        <div class="dh-sh-eyebrow">Provider Overview</div>
        <div class="dh-sh-title">At a Glance</div>
      </div>
      <a class="dh-sh-link" href="#">View activity <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>

    <div class="dh-glance">
      <div class="dh-gl-card">
        <div class="dh-gl-head"><div class="dh-gl-label">Missing Continuity Plans</div><div class="dh-gl-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div></div>
        <div class="dh-gl-val red">{{ glance.missing }}</div>
        <div class="dh-gl-sub"><span class="crit">Plans need attention</span></div>
      </div>
      <div class="dh-gl-card">
        <div class="dh-gl-head"><div class="dh-gl-label">Reattestations Due</div><div class="dh-gl-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div></div>
        <div class="dh-gl-val">{{ glance.reattest }}</div>
        <div class="dh-gl-sub">30 · 60 · 90 day windows</div>
      </div>
      <div class="dh-gl-card">
        <div class="dh-gl-head"><div class="dh-gl-label">Pending Provider Partners</div><div class="dh-gl-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg></div></div>
        <div class="dh-gl-val gold">{{ glance.pending }}</div>
        <div class="dh-gl-sub"><span class="warn">Awaiting acceptance</span></div>
      </div>
      <div class="dh-gl-card">
        <div class="dh-gl-head"><div class="dh-gl-label">Provider Partners 2026</div><div class="dh-gl-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div></div>
        <div class="dh-gl-val">{{ glance.partners }}</div>
        <div class="dh-gl-sub">Supported this year</div>
      </div>
    </div>

    <!-- ═══ QUICK ACTIONS ═══ -->
    <div class="cs-qa-title">Quick Actions</div>
    <div class="cs-quick-actions">
      <a href="/continuity-steward/providers" class="btn btn-primary btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg> Add Provider</a>
      <a href="/continuity-steward/important-documents" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Upload Document</a>
      <a href="/continuity-steward/activity" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> View Recent Activity Log</a>
    </div>

    <!-- ═══ CONTENT GRID ═══ -->
    <div class="cs-content-grid">
      <div class="cs-main-col">
        <div class="dh-sh" style="margin-top:6px;">
          <div class="dh-sh-l"><div class="dh-sh-eyebrow">Your Caseload</div><div class="dh-sh-title">Practitioners You Serve</div></div>
          <a class="dh-sh-link" href="/continuity-steward/providers">View all <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>

        <div v-for="pv in providers" :key="'pv-' + pv.initials" class="pv-card">
          <div class="pv-rail" :class="pv.railClass"></div>
          <div class="pv-body">
            <div class="pv-row">
              <div class="pv-av">{{ pv.initials }}</div>
              <div class="pv-info">
                <div class="pv-name">{{ pv.name }}</div>
                <div class="pv-title-line">{{ pv.specialty }} · {{ pv.location }}</div>
              </div>
              <div class="pv-actions">
                <template v-if="pv.status === 'incident'">
                  <button type="button" class="btn-icon" data-tip="Task List"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></button>
                  <button type="button" class="btn-icon" data-tip="Vault"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></button>
                </template>
                <template v-else-if="pv.status === 'cert_due'">
                  <button type="button" class="btn btn-primary btn-sm"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Certify</button>
                  <button type="button" class="btn-icon" data-tip="View Plan"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></button>
                </template>
                <template v-else>
                  <button type="button" class="btn-icon" data-tip="View Plan"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></button>
                  <button type="button" class="btn-icon" data-tip="Message"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></button>
                </template>
              </div>
            </div>
            <div class="pv-chips">
              <span class="pchip" :class="pv.statusChipClass" v-html="pv.statusChipHtml"></span>
              <span class="pchip" :class="pv.roleChipClass">{{ pv.csRole }}</span>
            </div>
            <div class="pv-meta">
              <span v-if="pv.planSigned">Plan signed</span>
              <span v-if="pv.planSigned" class="pv-meta-dot"></span>
              <span>{{ pv.planCertified ? 'Certified by you' : 'Awaiting certification' }}</span>
              <span class="pv-meta-dot"></span>
              <span>Last activity: {{ pv.lastAct }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ASIDE -->
      <div class="cs-aside-col">
        <!-- ACTIVE INCIDENT CARD -->
        <div v-if="hasEmergency" class="cs-incident-card">
          <div class="cs-incident-head">
            <div class="cs-incident-head-l"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Active Incident</div>
            <div class="cs-incident-prov">{{ incidentProvider }}</div>
          </div>
          <div class="cs-incident-body">
            <div class="cs-incident-row"><span>Type</span><strong>{{ incident.type }}</strong></div>
            <div class="cs-incident-row"><span>Triggered</span><strong>{{ incident.triggered }}</strong></div>
            <div class="cs-incident-row"><span>Reported by</span><strong>{{ incident.reportedBy }}</strong></div>
            <div class="cs-incident-progress">
              <svg class="cs-donut" width="54" height="54" viewBox="0 0 36 36">
                <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--surface-3)" stroke-width="3.4"/>
                <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--red)" stroke-width="3.4" stroke-linecap="round" :stroke-dasharray="incident.pct + ', 100'" transform="rotate(-90 18 18)"/>
                <text x="18" y="20.5" text-anchor="middle" font-size="9" font-weight="700" fill="var(--text)">{{ incident.pct }}%</text>
              </svg>
              <div>
                <div class="cs-incident-tasks">{{ incident.done }} of {{ incident.total }} tasks</div>
                <div class="cs-incident-tasksub">completed</div>
              </div>
            </div>
            <div class="cs-incident-tabs">
              <button type="button" class="cs-incident-tab active">Tasks</button>
              <button type="button" class="cs-incident-tab">Vault</button>
              <button type="button" class="cs-incident-tab">Mgmt</button>
            </div>
          </div>
        </div>

        <!-- UPCOMING TASKS -->
        <div class="cs-widget">
          <div class="cs-wh">
            <div class="cs-wh-l"><div class="cs-w-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div><div class="cs-w-title">Upcoming Tasks</div></div>
            <a class="cs-w-link" href="/continuity-steward/my-tasks">View All</a>
          </div>
          <div v-for="ut in upcomingTasks" :key="ut.title" class="cs-ut-row">
            <div class="cs-ut-dot" :style="{ background: ut.urgent ? 'var(--red)' : 'var(--gold-dark)' }"></div>
            <div class="cs-ut-info"><div class="cs-ut-title">{{ ut.title }}</div><div class="cs-ut-provider">{{ ut.provider }}</div></div>
            <div class="cs-ut-due" :class="{ urgent: ut.urgent }">{{ ut.due }}</div>
          </div>
          <div class="cs-wf"><a class="cs-w-link" href="/continuity-steward/my-tasks">View all tasks <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a></div>
        </div>
      </div>
    </div>

    <!-- ═══ IMMEDIATE ACTIONS (incident only) ═══ -->
    <template v-if="hasEmergency">
      <div class="dh-sh"><div class="dh-sh-l"><div class="dh-sh-eyebrow">Active Incident — {{ incidentProvider }}</div><div class="dh-sh-title">Immediate Actions Required</div></div></div>
      <div class="cs-tasklist">
        <div class="cs-tasklist-head">
          <div>
            <div class="cs-tasklist-title">Incident Task List</div>
            <div class="cs-tasklist-sub">Showing {{ incidentTasks.length }} of {{ incident.total12 }} tasks · {{ incidentProvider }}</div>
          </div>
          <span class="cs-tasklist-badge">{{ incidentTasks.filter(t => !t.done).length }} pending</span>
        </div>
        <label v-for="(t, i) in incidentTasks" :key="i" class="cs-task-item">
          <input type="checkbox" :checked="t.done" />
          <span class="cs-task-check"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
          <div class="cs-task-content">
            <div class="cs-task-title" :class="{ done: t.done }">{{ t.title }}</div>
            <span class="cs-task-prio" :class="'prio-' + t.priority.toLowerCase()">{{ t.priority }}</span>
          </div>
        </label>
        <div class="cs-tasklist-foot"><a class="cs-w-link" href="/continuity-steward/my-tasks">View full task list <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a></div>
      </div>
    </template>

    <!-- ═══ CERTIFICATION STATUS ═══ -->
    <div class="dh-sh"><div class="dh-sh-l"><div class="dh-sh-eyebrow">Your Obligations</div><div class="dh-sh-title">Certification Status</div></div></div>
    <div class="cs-cert-card">
      <div class="cs-cert-head">
        <div class="cs-cert-head-icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div><div class="cs-cert-head-title">Your Plan Certifications</div><div class="cs-cert-head-sub">Confirm each practitioner's plan is accurate and current</div></div>
      </div>
      <div v-for="cert in certs" :key="cert.provider" class="cs-cert-row">
        <div class="cs-cert-si" :class="cert.status === 'ok' ? 'si-ok' : 'si-due'">
          <svg v-if="cert.status === 'ok'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          <svg v-else width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="cs-cert-info">
          <div class="cs-cert-plan">{{ cert.provider }}</div>
          <div class="cs-cert-date">
            <template v-if="cert.status === 'ok'">Last certified: {{ cert.lastCert }}</template>
            <template v-else>Due: <span class="due">{{ cert.due }} — renewal required</span></template>
          </div>
        </div>
        <span v-if="cert.status === 'ok'" class="cs-cert-badge cs-cb-ok"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Certified</span>
        <button v-else type="button" class="btn btn-primary btn-sm">Certify Now</button>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Components/AppLayout.vue'

const hasEmergency = true

const titlePrefix = ''
const firstName = 'Marcus'
const lastName = 'Chen'
const csTypeLabel = 'Business CS'
const profilePct = 78

const providersAssigned = 6
const plansCertified = 3
const certificationsDue = 3
const certsOk = 3
const certsTotal = 6
const certPct = Math.round(certsOk / certsTotal * 100)
const nextCertDue = 'Aug 18, 2026'
const nextCertDays = 61
const readinessLast = 'Mar 18, 2026'
const incidentProvider = 'Dr. Sarah Johnson'

const hour = new Date().getHours()
const greeting = hour < 12 ? 'Good morning' : (hour < 17 ? 'Good afternoon' : 'Good evening')

const incident = { type: 'Short-Term Incapacitation', triggered: 'Apr 25, 2026 · 8:20 AM', reportedBy: 'Linda Johnson', done: 1, total: 3, total12: 12, pct: 33 }

const glance = { missing: 2, reattest: 3, pending: 1, partners: 6 }

const chipOk = '<svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> All Good'
const chipDue = '<svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Cert Due in 14 days'
const chipInc = '<svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg> Active Incident'

const providers = [
  { initials: 'SJ', name: 'Dr. Sarah Johnson', specialty: 'Trauma & EMDR, Family Systems', location: 'San Francisco, CA', status: 'incident', csRole: 'Primary CS', lastAct: '2 hours ago', planSigned: true, planCertified: true, railClass: 'rail-red', statClass: 'st-inc', statLabel: 'Active Incident', statusChipClass: 'chip-red', statusChipHtml: chipInc, roleChipClass: 'chip-gold' },
  { initials: 'MT', name: 'Dr. Michael Torres', specialty: 'Adult Psychiatry, Mood Disorders', location: 'Oakland, CA', status: 'incident', csRole: 'Primary CS', lastAct: '2 days ago', planSigned: true, planCertified: true, railClass: 'rail-red', statClass: 'st-inc', statLabel: 'Active Incident', statusChipClass: 'chip-red', statusChipHtml: chipInc, roleChipClass: 'chip-gold' },
  { initials: 'RO', name: 'Dr. Rachel Okafor', specialty: 'Anxiety, OCD, CBT', location: 'Santa Rosa, CA', status: 'cert_due', csRole: 'Primary CS', lastAct: '1 week ago', planSigned: true, planCertified: false, railClass: 'rail-gold', statClass: 'st-due', statLabel: 'Cert Due', statusChipClass: 'chip-gold', statusChipHtml: chipDue, roleChipClass: 'chip-gold' },
  { initials: 'DM', name: 'Dr. Daniel Malik', specialty: 'Psychiatry, Medication Management', location: 'New York, NY', status: 'ok', csRole: 'Alternate CS', lastAct: '2 weeks ago', planSigned: true, planCertified: true, railClass: 'rail-green', statClass: 'st-ok', statLabel: 'Certified', statusChipClass: 'chip-green', statusChipHtml: chipOk, roleChipClass: 'chip-neutral' },
  { initials: 'HY', name: 'Dr. Hana Yoon', specialty: 'Mood Disorders, Adolescent Psychiatry', location: 'New York, NY', status: 'cert_due', csRole: 'Primary CS', lastAct: '4 days ago', planSigned: true, planCertified: false, railClass: 'rail-gold', statClass: 'st-due', statLabel: 'Cert Due', statusChipClass: 'chip-gold', statusChipHtml: chipDue, roleChipClass: 'chip-gold' },
  { initials: 'ER', name: 'Dr. Elena Rodriguez', specialty: 'Eating Disorders, Nutrition, Mental Health', location: 'Manhattan, NY', status: 'cert_due', csRole: 'Primary CS', lastAct: '6 days ago', planSigned: true, planCertified: false, railClass: 'rail-gold', statClass: 'st-due', statLabel: 'Cert Due', statusChipClass: 'chip-gold', statusChipHtml: chipDue, roleChipClass: 'chip-gold' },
]

const planStatuses = [
  { label: "Dr. Sarah Johnson's plan — certified", done: true },
  { label: "Dr. Michael Torres's plan — certified", done: true },
  { label: "Dr. Daniel Malik's plan — certified", done: true },
  { label: "Dr. Rachel Okafor's plan — certification due", done: false },
  { label: "Dr. Hana Yoon's plan — certification due", done: false },
  { label: "Dr. Elena Rodriguez's plan — certification due", done: false },
]

const upcomingTasks = [
  { title: 'Reschedule upcoming appointments (Dr. Sarah Johnson)', provider: 'Dr. Sarah Johnson', due: 'Today', urgent: true },
  { title: 'Reschedule upcoming appointments (Dr. Michael Torres)', provider: 'Dr. Michael Torres', due: 'Today', urgent: true },
  { title: "Certify Dr. Rachel Okafor's continuity plan", provider: 'Dr. Rachel Okafor', due: 'Jan 28', urgent: false },
  { title: "Certify Dr. Hana Yoon's continuity plan", provider: 'Dr. Hana Yoon', due: 'May 20', urgent: false },
  { title: "Certify Dr. Elena Rodriguez's continuity plan", provider: 'Dr. Elena Rodriguez', due: 'Jun 1', urgent: false },
]

const incidentTasks = [
  { title: 'Verify incident and activate short-term coverage', priority: 'Urgent', done: true },
  { title: 'Reschedule or cover upcoming appointments (next 2 weeks)', priority: 'Urgent', done: false },
  { title: 'Monitor recovery progress and communicate with Support Steward', priority: 'High', done: false },
  { title: 'Contact DEA if controlled substances are involved', priority: 'High', done: false },
]

const certs = [
  { provider: "Dr. Sarah Johnson's plan", lastCert: 'Feb 15, 2026', due: null, status: 'ok' },
  { provider: "Dr. Michael Torres's plan", lastCert: 'Nov 20, 2025', due: null, status: 'ok' },
  { provider: "Dr. Rachel Okafor's plan", lastCert: null, due: 'Jan 28, 2027', status: 'due' },
  { provider: "Dr. Daniel Malik's plan", lastCert: 'Aug 18, 2025', due: null, status: 'ok' },
  { provider: "Dr. Hana Yoon's plan", lastCert: null, due: 'May 20, 2027', status: 'due' },
  { provider: "Dr. Elena Rodriguez's plan", lastCert: null, due: 'Jun 1, 2027', status: 'due' },
]
</script>
<style scoped>
/* ── CS DASHBOARD page-local styles ── */

/* EMERGENCY ALERT */
.dh-emergency { display:flex; gap:14px; padding:16px 22px; background:rgba(160,45,34,.06); border:1px solid rgba(160,45,34,.22); border-left:4px solid var(--red); border-radius:var(--radius-lg); margin-bottom:18px; }
.dh-em-ico { color:var(--red); flex-shrink:0; margin-top:1px; }
.dh-em-body { flex:1; min-width:0; }
.dh-em-title { font-size:14px; font-weight:700; color:var(--red); margin-bottom:3px; }
.dh-em-sub { font-size:12.5px; color:var(--text-2); margin-bottom:12px; }
.dh-em-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.btn-emergency { background:var(--red); color:var(--text-inverted); border:none; }
.btn-emergency:hover { background:var(--red-dark); }

/* FINISH PROFILE BANNER */
.cs-profile-banner { margin-bottom:24px; background:linear-gradient(95deg,var(--badge-bg-gold) 0%,var(--surface) 80%); border:1px solid rgba(192,154,82,.28); border-radius:var(--radius-lg); padding:14px 20px; display:flex; align-items:center; gap:16px; box-shadow:var(--shadow-sm); }
.cs-profile-icon { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cs-profile-body { flex:1; min-width:0; }
.cs-profile-title { font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px; }
.cs-profile-sub { font-size:12px; color:var(--text-3); line-height:1.5; }
.cs-profile-progress { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.cs-profile-bar { width:120px; height:5px; background:var(--surface-3); border-radius:var(--radius-full); overflow:hidden; }
.cs-profile-bar-fill { height:100%; background:linear-gradient(90deg,var(--gold),var(--gold-dark)); border-radius:var(--radius-full); }
.cs-profile-pct { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.cs-profile-cta { flex-shrink:0; white-space:nowrap; }

.dh-greet { display:grid; grid-template-columns:1fr auto; gap:26px; align-items:end; padding:6px 4px 26px; border-bottom:1px solid var(--border); margin-bottom:22px; }
.dh-greet-eyebrow { font-size:11px; font-weight:600; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:10px; display:flex; align-items:center; gap:9px; }
.dh-greet-eyebrow::before { content:""; width:18px; height:1px; background:var(--gold-dark); }
.dh-greet-title { font-family:var(--font-serif); font-size:38px; font-weight:600; letter-spacing:-0.5px; line-height:1.05; color:var(--text); }
.dh-greet-title em { font-style:italic; color:var(--gold-dark); font-weight:600; }
.dh-greet-sub { margin-top:12px; font-size:14px; color:var(--text-3); max-width:560px; line-height:1.55; }
.dh-greet-chips { display:flex; align-items:center; gap:8px; margin-top:12px; flex-wrap:wrap; }
.dh-greet-meta { display:flex; align-items:center; gap:0; padding:14px 22px; border-radius:var(--radius-lg); background:var(--surface); border:1px solid var(--border); box-shadow:var(--shadow-xs); }
.dh-greet-mcell { display:flex; flex-direction:column; gap:2px; padding:0 22px; }
.dh-greet-mcell+.dh-greet-mcell { border-left:1px solid var(--border); }
.dh-greet-mlabel { font-size:10px; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); font-weight:600; }
.dh-greet-mval { font-family:var(--font-serif); font-size:18px; font-weight:600; color:var(--text); display:inline-flex; align-items:center; gap:5px; }
.dh-greet-mval.ok { color:var(--green-dark); }
.dh-greet-mval.warn { color:var(--orange-dark); }

.cs-role-chip { display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:700; padding:3px 10px; border-radius:var(--radius-full); border:1px solid; }
.cs-chip-gold { background:var(--badge-bg-gold); border-color:rgba(192,154,82,.30); color:var(--gold-dark); }
.cs-chip-red { background:var(--red-light); border-color:rgba(160,45,34,.25); color:var(--red); }
.cs-chip-neutral { background:var(--surface-3); border-color:var(--border); color:var(--text-3); }
.cs-chip-dot { width:6px; height:6px; border-radius:50%; background:var(--red); }

.dh-sh { display:flex; align-items:flex-end; justify-content:space-between; margin:28px 0 14px; gap:16px; }
.dh-sh-l { display:flex; flex-direction:column; gap:3px; }
.dh-sh-eyebrow { font-size:10px; font-weight:600; letter-spacing:1.4px; text-transform:uppercase; color:var(--gold-dark); }
.dh-sh-title { font-family:var(--font-serif); font-size:20px; font-weight:600; letter-spacing:-0.2px; color:var(--text); }
.dh-sh-link { font-size:12px; font-weight:600; color:var(--text-3); display:inline-flex; align-items:center; gap:6px; padding:6px 0; text-decoration:none; transition:color .18s ease; }
.dh-sh-link:hover { color:var(--gold-dark); }

.dh-continuity { display:grid; grid-template-columns:1fr 1fr; gap:0; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; margin-bottom:6px; }
.dh-cn-left { padding:22px 24px; display:flex; flex-direction:column; gap:16px; border-right:1px solid var(--border); }
.dh-cn-right { padding:22px 24px; display:flex; flex-direction:column; gap:16px; }
.dh-cn-eyebrow { display:flex; align-items:center; gap:8px; font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--text-4); }
.dh-cn-pulse { width:7px; height:7px; border-radius:var(--radius-full); background:var(--green); box-shadow:0 0 0 3px rgba(76,175,125,.22); flex-shrink:0; }
.dh-cn-pulse.warn { background:var(--orange); box-shadow:0 0 0 3px rgba(220,143,48,.22); }
.dh-cn-title { font-family:var(--font-serif); font-size:19px; font-weight:600; letter-spacing:-0.2px; line-height:1.2; color:var(--text); }
.dh-cn-desc { font-size:12.5px; color:var(--text-3); line-height:1.6; }
.dh-cn-plans { display:flex; flex-direction:column; gap:8px; }
.dh-cn-plan { display:flex; align-items:center; gap:10px; padding:9px 12px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); transition:border-color .18s ease; }
.dh-cn-plan.has-profile { cursor:pointer; }
.dh-cn-plan.has-profile:hover { border-color:var(--gold-dark); }
.dh-cn-pavatar { width:30px; height:30px; border-radius:var(--radius-sm); background:var(--gold-dark); color:var(--text-inverted); font-family:var(--font-serif); font-weight:700; font-size:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.dh-cn-pinfo { flex:1; min-width:0; }
.dh-cn-pname { font-size:12px; font-weight:600; color:var(--text); line-height:1.2; }
.dh-cn-prole { font-size:10px; color:var(--text-4); margin-top:1px; }
.dh-cn-pstat { font-size:9px; font-weight:700; padding:2px 8px; border-radius:var(--radius-full); flex-shrink:0; white-space:nowrap; }
.dh-cn-pstat.st-ok { background:var(--green-light); color:var(--green-dark); }
.dh-cn-pstat.st-due { background:var(--badge-bg-gold); color:var(--gold-dark); }
.dh-cn-pstat.st-inc { background:var(--red-light); color:var(--red); }
.dh-cn-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:4px; }
.dh-cn-rhead { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.dh-cn-rtag { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--text-4); margin-bottom:4px; }
.dh-cn-due { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.dh-cn-due small { font-family:var(--font-sans); font-size:11px; font-weight:400; color:var(--text-4); display:block; margin-top:3px; }
.dh-cn-bar { position:relative; height:6px; background:var(--surface-3); border-radius:var(--radius-full); margin:4px 0 6px; overflow:visible; }
.dh-cn-bar-fill { position:absolute; left:0; top:0; height:100%; background:var(--green); border-radius:var(--radius-full); transition:width .6s ease; }
.dh-cn-bar-fill.warn { background:var(--orange); }
.dh-cn-bar-labels { display:flex; justify-content:space-between; font-size:10px; color:var(--text-4); }
.dh-cn-todos { display:flex; flex-direction:column; gap:6px; }
.dh-cn-todo { display:flex; align-items:center; gap:7px; font-size:12px; color:var(--text-3); }
.dh-cn-todo.done { color:var(--text-4); }
.dh-cn-todo.done :deep(svg) { color:var(--green); }
.dh-cn-todo :deep(svg) { color:var(--border-dark); flex-shrink:0; }

.dh-glance { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:6px; }
.dh-gl-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px 20px; display:flex; flex-direction:column; gap:14px; box-shadow:var(--shadow-xs); transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease; }
.dh-gl-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-sm); border-color:var(--surface-4); }
.dh-gl-head { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.dh-gl-label { font-size:11px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); line-height:1.3; }
.dh-gl-icon { width:30px; height:30px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.dh-gl-val { font-family:var(--font-serif); font-size:32px; font-weight:600; line-height:1; letter-spacing:-1px; color:var(--text); }
.dh-gl-val.red { color:var(--red); }
.dh-gl-val.gold { color:var(--gold-dark); }
.dh-gl-sub { font-size:12px; color:var(--text-3); line-height:1.4; }
.dh-gl-sub .ok { color:var(--green-dark); font-weight:600; }
.dh-gl-sub .warn { color:var(--orange-dark); font-weight:600; }
.dh-gl-sub .crit { color:var(--red); font-weight:600; }

.cs-qa-title { font-family:var(--font-serif); font-size:20px; font-weight:600; letter-spacing:-0.2px; color:var(--text); margin:28px 0 14px; }
.cs-quick-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:8px; }

.cs-content-grid { display:grid; grid-template-columns:1fr 300px; gap:24px; align-items:start; margin-top:22px; }
.cs-main-col { display:flex; flex-direction:column; }
.cs-aside-col { display:flex; flex-direction:column; gap:16px; }

.pv-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; margin-bottom:10px; display:flex; transition:box-shadow .18s ease,border-color .18s ease; }
.pv-card:last-child { margin-bottom:0; }
.pv-card:hover { box-shadow:var(--shadow-sm); border-color:var(--border-dark); }
.pv-rail { width:3px; flex-shrink:0; }
.pv-rail.rail-red { background:var(--red); }
.pv-rail.rail-gold { background:var(--gold-dark); }
.pv-rail.rail-green { background:var(--green); }
.pv-body { flex:1; padding:14px 16px; min-width:0; }
.pv-row { display:flex; align-items:center; gap:12px; }
.pv-av { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:13px; flex-shrink:0; }
.pv-info { flex:1; min-width:0; }
.pv-name { font-family:var(--font-serif); font-size:14px; font-weight:600; color:var(--text); line-height:1.2; }
.pv-title-line { font-size:11px; color:var(--text-4); margin-top:1px; }
.pv-chips { display:flex; gap:6px; flex-wrap:wrap; margin-top:9px; }
.pchip { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:700; padding:3px 9px; border-radius:var(--radius-full); border:1px solid; }
.pchip.chip-red { background:var(--red-light); border-color:rgba(160,45,34,.35); color:var(--red); }
.pchip.chip-gold { background:var(--badge-bg-gold); border-color:rgba(160,129,62,.45); color:var(--gold-dark); }
.pchip.chip-green { background:var(--green-light); border-color:rgba(46,138,87,.35); color:var(--green-dark); }
.pchip.chip-neutral { background:var(--surface-3); border-color:var(--border-dark); color:var(--text-3); }
.pchip :deep(svg) { width:9px; height:9px; }
.pv-meta { display:flex; align-items:center; gap:0; font-size:11px; color:var(--text-4); flex-wrap:wrap; margin-top:8px; margin-bottom:10px; }
.pv-meta-dot { width:2px; height:2px; border-radius:var(--radius-full); background:var(--border-dark); margin:0 7px; flex-shrink:0; }
.pv-actions { display:flex; gap:6px; align-items:center; flex-shrink:0; }

/* ACTIVE INCIDENT CARD */
.cs-incident-card { background:var(--surface); border:1px solid rgba(160,45,34,.22); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; margin-top:28px; }
.cs-incident-head { background:var(--red); color:var(--text-inverted); padding:12px 16px; }
.cs-incident-head-l { display:flex; align-items:center; gap:7px; font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; }
.cs-incident-prov { font-family:var(--font-serif); font-size:14px; font-weight:600; margin-top:2px; }
.cs-incident-body { padding:14px 16px; }
.cs-incident-row { display:flex; align-items:center; justify-content:space-between; gap:12px; font-size:12px; color:var(--text-4); padding:5px 0; }
.cs-incident-row strong { color:var(--text); font-weight:600; text-align:right; }
.cs-incident-progress { display:flex; align-items:center; gap:14px; margin:12px 0; padding:12px 0; border-top:1px solid var(--border); border-bottom:1px solid var(--border); }
.cs-donut { flex-shrink:0; }
.cs-incident-tasks { font-size:13px; font-weight:700; color:var(--text); }
.cs-incident-tasksub { font-size:11px; color:var(--text-4); }
.cs-incident-tabs { display:flex; gap:6px; }
.cs-incident-tab { flex:1; padding:7px 0; font-size:11px; font-weight:600; color:var(--text-3); background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); cursor:pointer; transition:all .15s ease; }
.cs-incident-tab:hover { border-color:var(--gold-dark); }
.cs-incident-tab.active { background:var(--gold-dark); border-color:var(--gold-dark); color:var(--text-inverted); }

/* INCIDENT TASK LIST */
.cs-tasklist { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; margin-bottom:22px; }
.cs-tasklist-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:16px 20px; border-bottom:1px solid var(--border); background:var(--surface-2); }
.cs-tasklist-title { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.cs-tasklist-sub { font-size:11.5px; color:var(--text-4); margin-top:2px; }
.cs-tasklist-badge { font-size:9.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:3px 9px; border-radius:var(--radius-full); background:var(--red-light); color:var(--red); flex-shrink:0; }
.cs-task-item { display:flex; align-items:center; gap:12px; padding:13px 20px; border-bottom:1px solid var(--border); cursor:pointer; }
.cs-task-item:hover { background:var(--surface-2); }
.cs-task-item input { position:absolute; opacity:0; width:0; height:0; }
.cs-task-check { width:18px; height:18px; border-radius:5px; border:1px solid var(--border-dark); display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; color:transparent; background:var(--surface); transition:all .12s ease; }
.cs-task-item input:checked + .cs-task-check { background:var(--green-dark); border-color:var(--green-dark); color:var(--text-inverted); }
.cs-task-content { flex:1; min-width:0; display:flex; align-items:center; justify-content:space-between; gap:12px; }
.cs-task-title { font-size:13px; color:var(--text); }
.cs-task-title.done { color:var(--text-4); text-decoration:line-through; }
.cs-task-prio { font-size:9px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; flex-shrink:0; }
.cs-task-prio.prio-urgent { color:var(--red); }
.cs-task-prio.prio-high { color:var(--orange-dark); }
.cs-tasklist-foot { padding:11px 20px; background:var(--surface-2); }

.cs-cert-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; margin-bottom:22px; }
.cs-cert-head { padding:14px 18px; border-bottom:1px solid var(--border); background:var(--surface-2); display:flex; align-items:center; gap:10px; }
.cs-cert-head-icon { width:26px; height:26px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cs-cert-head-title { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.cs-cert-head-sub { font-size:11px; color:var(--text-4); margin-top:1px; }
.cs-cert-row { display:flex; align-items:center; gap:12px; padding:12px 18px; border-bottom:1px solid var(--border); transition:background .18s ease; }
.cs-cert-row:last-child { border-bottom:none; }
.cs-cert-row:hover { background:var(--surface-2); }
.cs-cert-si { width:28px; height:28px; border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cs-cert-si.si-ok { background:var(--green-light); color:var(--green-dark); }
.cs-cert-si.si-due { background:var(--badge-bg-gold); color:var(--gold-dark); }
.cs-cert-info { flex:1; min-width:0; }
.cs-cert-plan { font-size:13px; font-weight:600; color:var(--text); }
.cs-cert-date { font-size:11px; color:var(--text-4); margin-top:1px; }
.cs-cert-date .due { color:var(--orange-dark); font-weight:600; }
.cs-cert-badge { font-size:10px; font-weight:600; padding:2px 9px; border-radius:var(--radius-full); flex-shrink:0; white-space:nowrap; display:inline-flex; align-items:center; gap:4px; }
.cs-cb-ok { background:var(--green-light); color:var(--green-dark); }

.cs-widget { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; }
.cs-wh { padding:12px 16px 11px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:8px; }
.cs-wh-l { display:flex; align-items:center; gap:8px; }
.cs-w-icon { width:26px; height:26px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cs-w-title { font-family:var(--font-serif); font-size:14px; font-weight:600; color:var(--text); }
.cs-w-link { font-size:11px; font-weight:600; color:var(--text-4); display:inline-flex; align-items:center; gap:4px; transition:color .18s ease; text-decoration:none; }
.cs-w-link:hover { color:var(--gold-dark); }
.cs-wf { padding:9px 16px; border-top:1px solid var(--border); background:var(--surface-2); }
.cs-ut-row { display:flex; align-items:flex-start; gap:10px; padding:10px 16px; border-bottom:1px solid var(--border); transition:background .18s ease; }
.cs-ut-row:last-child { border-bottom:none; }
.cs-ut-row:hover { background:var(--surface-2); }
.cs-ut-dot { width:6px; height:6px; border-radius:var(--radius-full); background:var(--gold-dark); flex-shrink:0; margin-top:5px; }
.cs-ut-info { flex:1; min-width:0; }
.cs-ut-title { font-size:12px; font-weight:500; color:var(--text); line-height:1.35; }
.cs-ut-provider { font-size:11px; color:var(--text-4); margin-top:1px; }
.cs-ut-due { font-size:9px; font-weight:700; padding:2px 7px; border-radius:var(--radius-full); background:var(--badge-bg-gold); color:var(--gold-dark); flex-shrink:0; white-space:nowrap; }
.cs-ut-due.urgent { background:var(--red-light); color:var(--red); }

@media (max-width:1180px) { .dh-greet { grid-template-columns:1fr; } .dh-glance { grid-template-columns:repeat(2,1fr); } .cs-content-grid { grid-template-columns:1fr; } .dh-continuity { grid-template-columns:1fr; } .dh-cn-left { border-right:none; border-bottom:1px solid var(--border); } }
@media (max-width:700px) { .dh-greet-title { font-size:28px; } .dh-glance { grid-template-columns:1fr 1fr; } .cs-profile-banner, .dh-em-actions { flex-wrap:wrap; } }
</style>
