<template>
  <AppLayout portal="support_steward" page-title="Dashboard" :has-emergency="hasEmergency">

    <!-- ═══ INCIDENT REPORTED ALERT ═══ -->
    <div v-if="hasEmergency" class="ss-em">
      <svg class="ss-em-ico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <div class="ss-em-body">
        <div class="ss-em-title">Incident Reported — {{ incidentProvider }}</div>
        <div class="ss-em-sub">{{ incident.type }} · reported Today at {{ incident.time }}. The Continuity Steward has been notified and is managing practice continuity. You did the right thing — it is being handled.</div>
        <a href="/support-steward/activity" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> View Incident Activity</a>
      </div>
    </div>

    <!-- ═══ GREETING ═══ -->
    <div class="dh-greet">
      <div>
        <div class="dh-greet-eyebrow">{{ greeting }}</div>
        <div class="dh-greet-title">{{ firstName }} <em>{{ lastName }},</em></div>
        <div class="dh-greet-sub">
          <template v-if="hasEmergency">An active incident has been reported for <strong style="color:var(--text-2);">{{ incidentProvider }}</strong>. The Continuity Steward has been notified and is managing the situation.</template>
          <template v-else>You are supporting <strong style="color:var(--text-2);">{{ practitioners.length }} practitioners.</strong> All standby tasks are on track.</template>
        </div>
      </div>
      <div class="dh-greet-meta">
        <div class="dh-greet-mcell"><div class="dh-greet-mlabel">Watch Status</div><div class="dh-greet-mval" :class="hasEmergency ? 'warn' : 'ok'"><svg v-if="hasEmergency" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> {{ hasEmergency ? 'Incident' : 'Standby' }}</div></div>
        <div class="dh-greet-mcell"><div class="dh-greet-mlabel">Practitioners</div><div class="dh-greet-mval">{{ practitioners.length }}</div></div>
        <div class="dh-greet-mcell"><div class="dh-greet-mlabel">Messages</div><div class="dh-greet-mval">{{ unreadMessages }}</div></div>
      </div>
    </div>

    <!-- ═══ OVERVIEW BANNER ═══ -->
    <div style="margin-bottom:14px;background:var(--surface);border:1px solid var(--border);border-left:3px solid var(--gold-dark);border-radius:var(--radius-lg);padding:16px 20px;display:flex;align-items:center;gap:16px;box-shadow:var(--shadow-sm);">
      <div style="width:38px;height:38px;border-radius:var(--radius-sm);background:var(--icon-bg-gold);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--gold-dark);">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:2px;">Overview — Start Here</div>
        <div style="font-size:12px;color:var(--text-3);line-height:1.5;">Key terms, your role as Support Steward, how to use this portal, and FAQs.</div>
      </div>
      <a href="/support-steward/overview" class="btn btn-outline btn-sm" style="flex-shrink:0;white-space:nowrap;">View Overview <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></a>
    </div>

    <!-- ═══ FINISH PROFILE BANNER ═══ -->
    <div class="cs-profile-banner">
      <div class="cs-profile-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
      <div class="cs-profile-body"><div class="cs-profile-title">Finish your profile</div><div class="cs-profile-sub">Add credentials &amp; availability to improve discovery</div></div>
      <div class="cs-profile-progress"><div class="cs-profile-bar"><div class="cs-profile-bar-fill" :style="{ width: profilePct + '%' }"></div></div><span class="cs-profile-pct">{{ profilePct }}%</span></div>
      <a href="/support-steward/profile" class="btn btn-primary btn-sm cs-profile-cta">Complete <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>

    <!-- ═══ YOUR WATCH AT A GLANCE ═══ -->
    <div class="dh-sh">
      <div class="dh-sh-l"><div class="dh-sh-eyebrow">Your Aegis</div><div class="dh-sh-title">Your watch at a glance</div></div>
      <a class="dh-sh-link" href="/support-steward/providers">Manage practitioners <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>

    <div class="dh-continuity">
      <div class="dh-cn-left">
        <div class="dh-cn-eyebrow"><span class="dh-cn-pulse warn"></span> Active Critical Incident · {{ incidentProvider }}</div>
        <div class="dh-cn-title">An incident was reported.<br>The CS is acting.</div>
        <div class="dh-cn-desc">When a practitioner becomes unreachable or incapacitated, you report it here. The Continuity Steward takes it from there. Your job is to stay informed and available.</div>

        <div class="ss-watch-grid">
          <div v-for="p in practitioners" :key="p.initials" class="ss-watch-cell">
            <div class="ss-watch-av" :class="p.status === 'incident' ? 'av-red' : 'av-gold'">{{ p.initials }}</div>
            <div class="ss-watch-info"><div class="ss-watch-role">Primary SS</div><div class="ss-watch-name">{{ p.name }}</div></div>
            <span class="ss-watch-stat" :class="p.status === 'incident' ? 'st-inc' : 'st-ok'"><span class="ss-watch-dot"></span> {{ p.status === 'incident' ? 'Incident' : 'Active' }}</span>
          </div>
        </div>

        <div class="dh-cn-actions">
          <a href="/support-steward/providers" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> View Practitioners</a>
          <a href="/support-steward/continuity-stewards" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Continuity Stewards</a>
        </div>
      </div>

      <div class="dh-cn-right">
        <div class="dh-cn-rhead">
          <div>
            <div class="dh-cn-rtag">Preparation Checklist</div>
            <div class="dh-cn-due">{{ prepDone }} of {{ prepTotal }} done<small>{{ prepTotal - prepDone }} task remaining before you're fully ready</small></div>
          </div>
          <span style="color:var(--gold-dark);flex-shrink:0;display:inline-flex;align-items:center;line-height:0"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
        </div>
        <div class="ss-prep-list">
          <div v-for="t in prepTasks" :key="t.title" class="ss-prep-item" :class="{ done: t.done }">
            <svg v-if="t.done" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>
            <span>{{ t.title }}</span>
          </div>
        </div>
        <a href="/support-steward/my-tasks" class="btn btn-outline btn-sm" style="align-self:flex-start;margin-top:6px;"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> View All Tasks</a>
      </div>
    </div>

    <!-- ═══ STATUS AT A GLANCE ═══ -->
    <div class="dh-sh">
      <div class="dh-sh-l"><div class="dh-sh-eyebrow">This month</div><div class="dh-sh-title">Status at a glance</div></div>
      <a class="dh-sh-link" href="/support-steward/activity">View activity <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
    <div class="dh-glance">
      <div class="dh-gl-card">
        <div class="dh-gl-head"><div class="dh-gl-label">Practitioners</div><div class="dh-gl-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div></div>
        <div class="dh-gl-val">{{ practitioners.length }}</div>
        <div class="dh-gl-sub"><span class="ok">Under your watch</span></div>
      </div>
      <div class="dh-gl-card">
        <div class="dh-gl-head"><div class="dh-gl-label">Preparation Tasks</div><div class="dh-gl-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div></div>
        <div class="dh-gl-val">{{ prepTotal - prepDone }}</div>
        <div class="dh-gl-sub">{{ prepDone }} complete · <span class="warn">{{ prepTotal - prepDone }} pending</span></div>
      </div>
      <div class="dh-gl-card">
        <div class="dh-gl-head"><div class="dh-gl-label">Active Incidents</div><div class="dh-gl-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div></div>
        <div class="dh-gl-val red">1</div>
        <div class="dh-gl-sub">{{ incidentProvider }}</div>
      </div>
      <div class="dh-gl-card">
        <div class="dh-gl-head"><div class="dh-gl-label">Messages</div><div class="dh-gl-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div></div>
        <div class="dh-gl-val">{{ unreadMessages }}</div>
        <div class="dh-gl-sub">{{ unreadMessages }} unread</div>
      </div>
    </div>

    <!-- ═══ PRACTITIONERS YOU SUPPORT ═══ -->
    <div class="dh-sh">
      <div class="dh-sh-l"><div class="dh-sh-eyebrow">Your Watch</div><div class="dh-sh-title">Practitioners You Support</div></div>
      <a class="dh-sh-link" href="/support-steward/providers">View all <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>

    <div class="cs-content-grid">
      <div class="cs-main-col">
        <!-- Incident practitioner card -->
        <div class="ss-pv-card incident">
          <div class="ss-pv-rail"></div>
          <div class="ss-pv-body">
            <div class="ss-pv-row">
              <div class="ss-pv-av av-red">SJ</div>
              <div class="ss-pv-info"><div class="ss-pv-name">Dr. Sarah Johnson</div><div class="ss-pv-line">Licensed Psychologist · Atlanta, GA</div></div>
            </div>
            <div class="ss-pv-chips">
              <span class="pchip chip-red"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg> Incident Reported</span>
              <span class="pchip chip-gold">Primary SS</span>
            </div>
            <div class="ss-pv-meta">Incident reported today at 9:14 AM · CS Marcus Webb, LCSW notified</div>
            <a href="/support-steward/activity" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> View Incident Status</a>
            <div class="ss-pv-note">The Continuity Steward is managing Dr. Sarah Johnson's practice. Stay available for coordination if needed.</div>
          </div>
        </div>

        <!-- standby practitioner card -->
        <div class="ss-pv-card">
          <div class="ss-pv-rail gold"></div>
          <div class="ss-pv-body">
            <div class="ss-pv-row">
              <div class="ss-pv-av av-gold">JO</div>
              <div class="ss-pv-info"><div class="ss-pv-name">Dr. James Okafor</div><div class="ss-pv-line">Licensed Counselor · Chicago, IL</div></div>
              <div class="ss-pv-actions">
                <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></button>
                <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></button>
                <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
              </div>
            </div>
            <div class="ss-pv-chips">
              <span class="pchip chip-gold"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> 1 Task Pending</span>
              <span class="pchip chip-gold">Primary SS</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ASIDE -->
      <div class="cs-aside-col">
        <div class="ss-aside-card">
          <div class="ss-aside-head red"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Incident Reported</div>
          <div class="ss-aside-body">
            <p class="ss-aside-text">You reported an incident for <strong>Dr. Sarah Johnson</strong>. The Continuity Steward has been notified and is acting. Stay available for coordination.</p>
            <a href="/support-steward/activity" class="btn btn-outline btn-sm ss-block-btn"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> View Incident Status</a>
          </div>
        </div>

        <div class="ss-aside-card">
          <div class="ss-aside-head"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Active Incident — {{ incidentProvider }}</div>
          <div class="ss-aside-body">
            <div class="ss-status-row"><span>Your report</span><strong>{{ incident.type }}</strong></div>
            <div class="ss-status-row"><span>Reported</span><strong>Today at {{ incident.time }}</strong></div>
            <div class="ss-status-row"><span>CS assigned</span><strong>{{ incident.cs }}</strong></div>
            <span class="ss-verified"><span class="ss-verified-dot"></span> CS Verified · Tasks in progress</span>
            <div class="ss-status-progress"><span>Task progress</span><span>{{ incident.done }} of {{ incident.total }}</span></div>
            <div class="ss-progbar"><div class="ss-progbar-fill" :style="{ width: (incident.done / incident.total * 100) + '%' }"></div></div>
            <a href="/support-steward/activity" class="ss-aside-link">View Full Status <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ PREPARATION CHECKLIST ═══ -->
    <div class="dh-sh"><div class="dh-sh-l"><div class="dh-sh-eyebrow">Before It Happens</div><div class="dh-sh-title">Your Preparation Checklist</div></div></div>
    <div class="ss-checklist">
      <div v-for="t in prepTasks" :key="'pt-' + t.title" class="ss-check-item">
        <span class="ss-check-box" :class="{ done: t.done }">
          <svg v-if="t.done" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          <svg v-else width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>
        </span>
        <div class="ss-check-content">
          <div class="ss-check-title" :class="{ done: t.done }">{{ t.title }}</div>
          <div class="ss-check-meta">
            <span class="ss-check-tag">{{ t.tag }}</span>
            <span v-if="t.done" class="ss-check-status done"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Done</span>
            <template v-else>
              <span class="ss-check-status pending">Pending</span>
              <a href="/support-steward/my-tasks" class="ss-check-cta">Complete Now <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </template>
          </div>
        </div>
      </div>
      <div class="ss-check-foot">{{ prepDone }} of {{ prepTotal }} tasks complete · <a href="/support-steward/my-tasks"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> View All Tasks</a></div>
    </div>

    <!-- ═══ EMERGENCY CONTACTS ═══ -->
    <div class="dh-sh"><div class="dh-sh-l"><div class="dh-sh-eyebrow">Your Emergency Contacts</div><div class="dh-sh-title">Who to Call When It Happens</div></div></div>
    <div class="ss-contacts-card">
      <div class="ss-contacts-head">
        <div class="ss-contacts-title">Continuity Stewards</div>
        <div class="ss-contacts-sub">Licensed professionals who manage the practice when you report an incident</div>
      </div>
      <div v-for="cs in continuityStewards" :key="cs.initials" class="ss-contact-row">
        <div class="ss-contact-av">{{ cs.initials }}</div>
        <div class="ss-contact-info">
          <div class="ss-contact-name">{{ cs.name }} <span class="ss-contact-active"><span class="ss-contact-dot"></span> Active</span></div>
          <div class="ss-contact-role">Primary Continuity Steward · {{ cs.provider }}</div>
        </div>
        <div class="ss-contact-actions">
          <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
          <button type="button" class="btn-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></button>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Components/AppLayout.vue'

const hasEmergency = true
const firstName = 'Linda'
const lastName = 'Johnson'
const profilePct = 78
const unreadMessages = 3

const hour = new Date().getHours()
const greeting = hour < 12 ? 'Good morning' : (hour < 17 ? 'Good afternoon' : 'Good evening')

const incidentProvider = 'Dr. Sarah Johnson'
const incident = { type: 'Short-Term Incapacitation', time: '9:14 AM', cs: 'Marcus Webb, LCSW', done: 3, total: 12 }

const practitioners = [
  { initials: 'SJ', name: 'Dr. Sarah Johnson', status: 'incident' },
  { initials: 'JO', name: 'Dr. James Okafor', status: 'ok' },
]

const prepDone = 2
const prepTotal = 3
const prepTasks = [
  { title: "Review Dr. Sarah Johnson's Continuity Plan", tag: 'Dr. Sarah Johnson', done: true },
  { title: 'Confirm CS contact details are saved', tag: 'All Practitioners', done: true },
  { title: 'Complete emergency contact info for Dr. Okafor', tag: 'Dr. James Okafor', done: false },
]

const continuityStewards = [
  { initials: 'MW', name: 'Marcus Webb, LCSW', provider: 'Dr. Sarah Johnson' },
  { initials: 'PR', name: 'Dr. Priya Raman', provider: 'Dr. James Okafor' },
]
</script>
<style scoped>
/* ── SS DASHBOARD ── */
.ss-em { display:flex; gap:14px; padding:16px 22px; background:rgba(160,45,34,.06); border:1px solid rgba(160,45,34,.22); border-left:4px solid var(--red); border-radius:var(--radius-lg); margin-bottom:18px; }
.ss-em-ico { color:var(--red); flex-shrink:0; margin-top:1px; }
.ss-em-body { flex:1; min-width:0; }
.ss-em-title { font-size:14px; font-weight:700; color:var(--red); margin-bottom:3px; }
.ss-em-sub { font-size:12.5px; color:var(--text-2); line-height:1.55; margin-bottom:12px; }

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
.dh-greet-meta { display:flex; align-items:center; gap:0; padding:14px 22px; border-radius:var(--radius-lg); background:var(--surface); border:1px solid var(--border); box-shadow:var(--shadow-xs); }
.dh-greet-mcell { display:flex; flex-direction:column; gap:2px; padding:0 22px; }
.dh-greet-mcell+.dh-greet-mcell { border-left:1px solid var(--border); }
.dh-greet-mlabel { font-size:10px; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); font-weight:600; }
.dh-greet-mval { font-family:var(--font-serif); font-size:18px; font-weight:600; color:var(--text); display:inline-flex; align-items:center; gap:5px; }
.dh-greet-mval.ok { color:var(--green-dark); }
.dh-greet-mval.warn { color:var(--orange-dark); }

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
.dh-cn-pulse.warn { background:var(--red); box-shadow:0 0 0 3px rgba(160,45,34,.18); }
.dh-cn-title { font-family:var(--font-serif); font-size:19px; font-weight:600; letter-spacing:-0.2px; line-height:1.2; color:var(--text); }
.dh-cn-desc { font-size:12.5px; color:var(--text-3); line-height:1.6; }
.dh-cn-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:4px; }
.dh-cn-rhead { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.dh-cn-rtag { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--text-4); margin-bottom:4px; }
.dh-cn-due { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.dh-cn-due small { font-family:var(--font-sans); font-size:11px; font-weight:400; color:var(--text-4); display:block; margin-top:3px; }

/* watch grid */
.ss-watch-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.ss-watch-cell { display:flex; align-items:center; gap:10px; padding:10px 12px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); }
.ss-watch-av { width:32px; height:32px; border-radius:var(--radius-sm); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:11px; flex-shrink:0; }
.ss-watch-av.av-red { background:var(--red-dark); }
.ss-watch-av.av-gold { background:var(--gold-dark); }
.ss-watch-info { flex:1; min-width:0; }
.ss-watch-role { font-size:9px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--text-4); }
.ss-watch-name { font-size:12px; font-weight:600; color:var(--text); }
.ss-watch-stat { display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:700; flex-shrink:0; }
.ss-watch-stat.st-inc { color:var(--red); }
.ss-watch-stat.st-ok { color:var(--green-dark); }
.ss-watch-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

/* prep list (right) */
.ss-prep-list { display:flex; flex-direction:column; gap:8px; }
.ss-prep-item { display:flex; align-items:center; gap:9px; padding:9px 12px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); font-size:12px; color:var(--text-2); }
.ss-prep-item.done { color:var(--text-4); }
.ss-prep-item.done span { text-decoration:line-through; }
.ss-prep-item.done :deep(svg), .ss-prep-item.done svg { color:var(--green); }
.ss-prep-item svg { color:var(--border-dark); flex-shrink:0; }

.dh-glance { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
.dh-gl-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px 20px; display:flex; flex-direction:column; gap:14px; box-shadow:var(--shadow-xs); transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease; }
.dh-gl-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-sm); border-color:var(--surface-4); }
.dh-gl-head { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.dh-gl-label { font-size:11px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); }
.dh-gl-icon { width:30px; height:30px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.dh-gl-val { font-family:var(--font-serif); font-size:32px; font-weight:600; line-height:1; letter-spacing:-1px; color:var(--text); }
.dh-gl-val.red { color:var(--red); }
.dh-gl-sub { font-size:12px; color:var(--text-3); }
.dh-gl-sub .ok { color:var(--green-dark); font-weight:600; }
.dh-gl-sub .warn { color:var(--orange-dark); font-weight:600; }

.cs-content-grid { display:grid; grid-template-columns:1fr 300px; gap:24px; align-items:start; }
.cs-main-col { display:flex; flex-direction:column; gap:12px; }
.cs-aside-col { display:flex; flex-direction:column; gap:16px; }

/* SS practitioner cards */
.ss-pv-card { display:flex; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; }
.ss-pv-card.incident { background:rgba(160,45,34,.03); border-color:rgba(160,45,34,.18); }
.ss-pv-rail { width:4px; flex-shrink:0; background:var(--red); }
.ss-pv-rail.gold { background:var(--gold-dark); }
.ss-pv-body { flex:1; min-width:0; padding:16px 18px; }
.ss-pv-row { display:flex; align-items:center; gap:12px; }
.ss-pv-av { width:40px; height:40px; border-radius:var(--radius-sm); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:13px; flex-shrink:0; }
.ss-pv-av.av-red { background:var(--red-dark); }
.ss-pv-av.av-gold { background:var(--gold-dark); }
.ss-pv-info { flex:1; min-width:0; }
.ss-pv-name { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.ss-pv-line { font-size:12px; color:var(--text-4); margin-top:1px; }
.ss-pv-actions { display:flex; gap:6px; flex-shrink:0; }
.ss-pv-chips { display:flex; gap:6px; flex-wrap:wrap; margin-top:10px; }
.pchip { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:700; padding:3px 9px; border-radius:var(--radius-full); border:1.5px solid; }
.pchip.chip-red { background:var(--red-light); border-color:rgba(160,45,34,.35); color:var(--red); }
.pchip.chip-gold { background:var(--badge-bg-gold); border-color:rgba(160,129,62,.45); color:var(--gold-dark); }
.pchip :deep(svg) { width:9px; height:9px; }
.ss-pv-meta { font-size:11.5px; color:var(--text-4); margin:10px 0 12px; }
.ss-pv-note { margin-top:12px; padding:11px 14px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); font-size:12px; color:var(--text-3); line-height:1.5; }

/* aside cards */
.ss-aside-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; }
.ss-aside-head { display:flex; align-items:center; gap:8px; padding:13px 16px; border-bottom:1px solid var(--border); background:var(--surface-2); font-size:13px; font-weight:700; color:var(--text); }
.ss-aside-head svg { color:var(--gold-dark); flex-shrink:0; }
.ss-aside-head.red { background:var(--red-light); color:var(--red); }
.ss-aside-head.red svg { color:var(--red); }
.ss-aside-body { padding:16px; }
.ss-aside-text { font-size:12.5px; color:var(--text-2); line-height:1.6; margin:0 0 12px; }
.ss-block-btn { width:100%; }
.ss-status-row { display:flex; align-items:center; justify-content:space-between; gap:12px; font-size:12px; color:var(--text-4); padding:5px 0; }
.ss-status-row strong { color:var(--text); font-weight:600; text-align:right; }
.ss-verified { display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:700; color:var(--green-dark); margin:8px 0; }
.ss-verified-dot { width:6px; height:6px; border-radius:50%; background:var(--green); }
.ss-status-progress { display:flex; align-items:center; justify-content:space-between; font-size:11px; color:var(--text-4); margin:10px 0 5px; }
.ss-progbar { height:6px; background:var(--surface-3); border-radius:var(--radius-full); overflow:hidden; }
.ss-progbar-fill { height:100%; background:var(--green); border-radius:var(--radius-full); }
.ss-aside-link { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; color:var(--gold-dark); text-decoration:none; margin-top:14px; }
.ss-aside-link:hover { text-decoration:underline; }

/* preparation checklist */
.ss-checklist { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; margin-bottom:6px; }
.ss-check-item { display:flex; gap:12px; padding:14px 20px; border-bottom:1px solid var(--border); }
.ss-check-box { width:18px; height:18px; border-radius:5px; border:1.5px solid var(--border-dark); display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; color:transparent; background:var(--surface); margin-top:1px; }
.ss-check-box.done { background:var(--green-dark); border-color:var(--green-dark); color:var(--text-inverted); }
.ss-check-content { flex:1; min-width:0; }
.ss-check-title { font-size:13px; color:var(--text); }
.ss-check-title.done { color:var(--text-4); text-decoration:line-through; }
.ss-check-meta { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:7px; }
.ss-check-tag { font-size:10px; font-weight:600; padding:2px 9px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); }
.ss-check-status { font-size:9.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; display:inline-flex; align-items:center; gap:4px; }
.ss-check-status.done { color:var(--green-dark); }
.ss-check-status.pending { color:var(--orange-dark); }
.ss-check-cta { display:inline-flex; align-items:center; gap:4px; font-size:10.5px; font-weight:700; color:var(--gold-dark); text-decoration:none; }
.ss-check-cta:hover { text-decoration:underline; }
.ss-check-foot { padding:12px 20px; font-size:12px; color:var(--text-3); display:flex; align-items:center; gap:8px; }
.ss-check-foot a { display:inline-flex; align-items:center; gap:5px; font-weight:600; color:var(--gold-dark); text-decoration:none; }

/* emergency contacts */
.ss-contacts-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; margin-bottom:22px; }
.ss-contacts-head { padding:16px 20px; border-bottom:1px solid var(--border); background:var(--surface-2); }
.ss-contacts-title { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.ss-contacts-sub { font-size:11.5px; color:var(--text-4); margin-top:2px; }
.ss-contact-row { display:flex; align-items:center; gap:12px; padding:13px 20px; border-bottom:1px solid var(--border); }
.ss-contact-row:last-child { border-bottom:none; }
.ss-contact-av { width:38px; height:38px; border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:12px; flex-shrink:0; }
.ss-contact-info { flex:1; min-width:0; }
.ss-contact-name { font-size:13.5px; font-weight:600; color:var(--text); display:inline-flex; align-items:center; gap:8px; }
.ss-contact-active { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:700; color:var(--green-dark); }
.ss-contact-dot { width:6px; height:6px; border-radius:50%; background:var(--green); }
.ss-contact-role { font-size:11.5px; color:var(--text-4); margin-top:1px; }
.ss-contact-actions { display:flex; gap:6px; flex-shrink:0; }

@media (max-width:1180px) { .dh-greet { grid-template-columns:1fr; } .dh-glance { grid-template-columns:repeat(2,1fr); } .cs-content-grid { grid-template-columns:1fr; } .dh-continuity { grid-template-columns:1fr; } .dh-cn-left { border-right:none; border-bottom:1px solid var(--border); } }
@media (max-width:700px) { .dh-greet-title { font-size:28px; } .dh-glance { grid-template-columns:1fr 1fr; } .ss-watch-grid { grid-template-columns:1fr; } }
</style>
