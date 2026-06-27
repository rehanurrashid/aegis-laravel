<template>
  <AppLayout :user="user" portal="practitioner" activePage="support-stewards" pageTitle="Support Stewards">
    <div class="ss-hero">
      <div>
        <div class="ss-eyebrow">PROVIDER PORTAL</div>
        <h1 class="ss-title">Support Stewards</h1>
        <p class="ss-sub">Designate trusted individuals to support communication, coordination, and key tasks during a critical moment; guided by your Continuity Plan.</p>
      </div>
      <div class="ss-hero-actions">
        <button class="btn btn-outline btn-sm ss-btn-icon" @click="showToast('Opening activity','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <button class="btn btn-outline btn-sm ss-btn-icon" @click="showToast('What is a Support Steward?','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> What is a Support Steward?</button>
        <button class="btn btn-primary btn-sm ss-btn-icon" @click="showToast('Opening Add Support Steward','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> + Add Support Steward</button>
      </div>
    </div>

    <div class="ss-plan-notice">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span>Your <strong>Continuity Practice</strong> plan includes up to <strong>10 Support Stewards</strong> · 5 of 10 in use.</span>
    </div>

    <div class="ss-stats">
      <div class="ss-stat"><div class="ss-stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div><div><div class="ss-stat-val">2</div><div class="ss-stat-lbl">Active Support Stewards</div></div></div>
      <div class="ss-stat"><div class="ss-stat-icon ss-stat-gold"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="ss-stat-val">1</div><div class="ss-stat-lbl">Pending Invite</div></div></div>
      <div class="ss-stat"><div class="ss-stat-icon ss-stat-blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg></div><div><div class="ss-stat-val">1</div><div class="ss-stat-lbl">I'm a Support Steward For</div></div></div>
      <div class="ss-stat"><div class="ss-stat-icon ss-stat-green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div><div class="ss-stat-val">Feb 14</div><div class="ss-stat-lbl">Annual Attestation Due</div></div></div>
    </div>

    <div class="ss-tabs">
      <button class="ss-tab" :class="{ active: tab==='mine' }" @click="tab='mine'">My Support Stewards <span class="ss-tab-count" :class="{ active: tab==='mine' }">3</span></button>
      <button class="ss-tab" :class="{ active: tab==='pending' }" @click="tab='pending'">Pending <span class="ss-tab-count" :class="{ active: tab==='pending' }">0</span></button>
      <button class="ss-tab" :class="{ active: tab==='permissions' }" @click="tab='permissions'">Permissions</button>
      <button class="ss-tab" :class="{ active: tab==='for' }" @click="tab='for'">I'm a Support Steward For <span class="ss-tab-count" :class="{ active: tab==='for' }">1</span></button>
      <button class="ss-tab" :class="{ active: tab==='guidance' }" @click="tab='guidance'">Planning &amp; Guidance</button>
    </div>
    <div class="ss-tab-sub">
      <span class="ss-tab-desc">Staff authorized to act on your behalf within defined permission boundaries.</span>
      <button class="ss-add-sm" @click="showToast('Opening Add Support Steward','info')">+ Add Support Steward</button>
    </div>

    <!-- MY SUPPORT STEWARDS -->
    <div v-show="tab==='mine'">
      <div v-for="s in stewards" :key="s.name" class="ss-card" :class="{ 'ss-suspended': s.status==='SUSPENDED' }">
        <div class="ss-card-head">
          <div class="ss-avatar" :style="{ background: s.color }">{{ s.initials }}</div>
          <div class="ss-info">
            <div class="ss-name-row">
              <span class="ss-name">{{ s.name }}</span>
              <span class="ss-badge-role">SUPPORT STEWARD</span>
              <span class="ss-badge-status" :class="s.status==='ACTIVE' ? 'ss-active' : 'ss-susp'">● {{ s.status }}</span>
            </div>
            <div class="ss-org">{{ s.org }}</div>
            <div class="ss-meta">
              <span v-if="s.phone" class="ss-mi">{{ s.phone }}</span>
              <span v-if="s.email" class="ss-mi">{{ s.email }}</span>
              <span v-if="s.agreement" class="ss-mi">Agreement {{ s.agreement }}</span>
              <span v-if="s.reviewDue" class="ss-mi">Review Due {{ s.reviewDue }}</span>
            </div>
          </div>
        </div>
        <div v-if="s.responsibilities" class="ss-resp-block">
          <div class="ss-resp-lbl">AUTHORIZED RESPONSIBILITIES</div>
          <div v-for="r in s.responsibilities" :key="r.text" class="ss-resp-row">
            <span class="ss-resp-text">{{ r.text }}</span>
            <span class="ss-chip" :class="r.cls">{{ r.level }}</span>
          </div>
        </div>
        <div v-if="s.status==='SUSPENDED'" class="ss-susp-warn">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          <span><strong>Access suspended:</strong> On medical leave — all task delegation paused. Reinstate when ready to return.</span>
        </div>
        <div class="ss-actions">
          <template v-if="s.status!=='SUSPENDED'">
            <button class="ss-act" title="View Agreement" @click="openM('agreement',s.name)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></button>
            <button class="ss-act" title="Edit" @click="openM('edit',s.name)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
            <button class="ss-act" title="Vault Access" @click="openM('vault',s.name)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></button>
            <button class="ss-act" title="Message" @click="goMsg()"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></button>
            <button class="ss-act" title="Permissions" @click="openM('perms',s.name)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></button>
            <button class="ss-act" title="Copy" @click="showToast('Copied','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>
            <button class="ss-act" title="Refresh" @click="showToast('Refreshed','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg></button>
          </template>
          <button v-if="s.status==='SUSPENDED'" class="ss-reinstate" @click="openReinstate(s)"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg> Reinstate</button>
          <button class="ss-act ss-danger" title="Remove" @click="openM('remove',s.name)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>
        </div>
      </div>
      <button class="ss-add-dashed" @click="showToast('Opening Add Support Steward','info')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Another Support Steward
      </button>
    </div>

    <!-- OTHER TABS -->
    <div v-show="tab==='pending'">
      <p class="ss-tab-desc" style="margin-bottom:14px;">Support Stewards who have been invited but have not yet accepted.</p>
      <div v-for="p in pendingInvites" :key="p.name" class="ss-card">
        <div class="ss-card-head">
          <div class="ss-avatar" :style="{ background: p.color }">{{ p.initials }}</div>
          <div class="ss-info" style="flex:1;">
            <div class="ss-name-row">
              <span class="ss-name">{{ p.name }}</span>
              <span class="ss-badge-role">AWAITING RESPONSE</span>
              <span v-if="p.role" class="ss-badge-role" style="background:rgba(160,129,62,.08);">{{ p.role }}</span>
            </div>
            <div class="ss-org">{{ p.org }}</div>
            <div class="ss-meta">
              <span v-if="p.email" class="ss-mi">{{ p.email }}</span>
              <span class="ss-mi">Invited: {{ p.invited }}</span>
              <span class="ss-mi">Expires: {{ p.expires }}</span>
            </div>
          </div>
        </div>
        <div class="ss-actions">
          <button class="ss-act" title="Resend invitation" @click="openPend('resend', p)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></button>
          <button class="ss-act" title="Preview agreement" @click="openPend('preview', p)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
          <button class="ss-act" title="Edit before resending" @click="openPend('edit', p)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
          <button class="ss-act ss-danger" title="Cancel invitation" @click="openPend('cancel', p)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
      </div>
      <!-- Declined -->
      <div v-for="d in declinedInvites" :key="d.name" class="ss-card" style="border-left:3px solid var(--red);">
        <div class="ss-card-head">
          <div class="ss-avatar" :style="{ background: d.color }">{{ d.initials }}</div>
          <div class="ss-info">
            <div class="ss-name-row"><span class="ss-name">{{ d.name }}</span><span class="ss-badge-status ss-susp">DECLINED</span></div>
            <div class="ss-org">{{ d.org }}</div>
            <div class="ss-meta"><span class="ss-mi">Agreement: {{ d.agreement }}</span></div>
            <div class="ss-mi" style="margin-top:4px;font-size:12px;color:var(--text-3);">{{ d.reason }}</div>
          </div>
        </div>
      </div>
    </div>

    <div v-show="tab==='permissions'">
      <div class="ss-perm-overview-header">
        <span class="ss-tab-desc">Manage what each Support Steward is authorized to do on your behalf.</span>
        <button class="ss-add-sm" @click="showToast('Opening Edit Permissions','info')">Edit Permissions</button>
      </div>
      <div v-for="s in stewards" :key="s.name" class="ss-perm-card">
        <div class="ss-perm-card-left">
          <div class="ss-avatar" :style="{ background: s.color, width:'36px', height:'36px', fontSize:'12px' }">{{ s.initials }}</div>
          <div>
            <div class="ss-name-row"><span class="ss-name" style="font-size:13.5px;">{{ s.name }}</span><span class="ss-badge-status" :class="s.status==='ACTIVE'?'ss-active':'ss-susp'">{{ s.status }}</span></div>
            <div class="ss-org">Support Steward</div>
          </div>
        </div>
        <div v-if="s.responsibilities" class="ss-perm-chips">
          <span v-for="r in s.responsibilities" :key="r.text" class="ss-chip" :class="r.cls" style="font-size:9px;padding:1px 7px;">{{ r.level }}</span>
        </div>
        <button class="ss-act" title="Edit permissions" @click="openEditPerms(s)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
      </div>
    </div>

    <div v-show="tab==='for'" class="ss-for-card" @click="showToast('Opening provider portal','info')">
      <div class="ss-avatar" style="background:#4a7a6a;width:42px;height:42px;font-size:13px;">EC</div>
      <div style="flex:1;min-width:0;"><div class="ss-name-row"><span class="ss-name">Dr. Emily Chen</span><span class="ss-badge-role">SUPPORT STEWARD</span><span class="ss-badge-status ss-active">● ACTIVE</span></div><div class="ss-org">Licensed Therapist · San Francisco, CA</div></div>
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </div>

    <div v-show="tab==='guidance'">
      <p class="ss-guidance-intro">Guidance to help your Support Stewards feel prepared — before, during, and after a critical moment. These notes are informational and meant to be reviewed together.</p>

      <div v-for="section in guidanceSections" :key="section.title" class="ss-accord">
        <button class="ss-accord-head" @click="section.open = !section.open">
          <div class="ss-accord-icon" v-html="section.icon"></div>
          <span class="ss-accord-title">{{ section.title }}</span>
          <svg class="ss-accord-arrow" :class="{ open: section.open }" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div v-show="section.open" class="ss-accord-body">
          <div v-for="item in section.items" :key="item" class="ss-accord-item">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" style="flex-shrink:0;color:var(--gold-dark);margin-top:1px;"><polyline points="20 6 9 17 4 12"/></svg>
            <span>{{ item }}</span>
          </div>
        </div>
      </div>

      <div class="ss-guidance-footer">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>This guidance is a starting point. Tailor it with your Continuity Steward so it fits your practice.</span>
      </div>
    </div>

    <!-- NOTIFY ME -->
    <div v-show="tab==='mine'" class="ss-notify">
      <div class="ss-notify-title">Notify Me</div>
      <div class="ss-notify-sub">Choose when Aegis should let you know about activity involving your Support Stewards.</div>
      <div v-for="n in notifyToggles" :key="n.label" class="ss-notify-row">
        <span class="ss-notify-label">{{ n.label }}</span>
        <label class="ss-tog"><input type="checkbox" v-model="n.on"><span class="ss-tog-track" :class="{ on: n.on }"><span class="ss-tog-thumb"></span></span></label>
      </div>
    </div>

    <!-- MODALS -->
    <Teleport to="body">

      <!-- AGREEMENT -->
      <Transition name="ssm-fade">
        <div v-if="m.type==='agreement'" class="ssm-backdrop" @click.self="m.type=null">
          <div class="ssm ssm-lg">
            <div class="ssm-head"><div class="ssm-title">Continuity Plan — {{ m.name }}</div><button class="ssm-x" @click="m.type=null">×</button></div>
            <div class="ssm-body">
              <div class="ssm-agr-status">✓ Agreement #SS-2026-001 · Active · Both parties signed · Last reviewed Feb 14, 2026</div>
              <div class="ssm-agr-doc">
                <div class="ssm-agr-h">AEGIS SUPPORT STEWARD AGREEMENT</div>
                <p class="ssm-agr-m"><strong>Provider:</strong> Dr. Sarah Johnson, MD — Psychiatrist, San Francisco, CA</p>
                <p class="ssm-agr-m"><strong>Support Steward:</strong> {{ m.name }}</p>
                <p class="ssm-agr-m"><strong>Agreement Date:</strong> Feb 14, 2026 | <strong>Next Review:</strong> Feb 14, 2027</p>
                <hr class="ssm-agr-hr">
                <p class="ssm-agr-p"><strong>Section 1. Purpose.</strong> This agreement establishes the scope, authority, and responsibilities of the Support Steward to act on behalf of the Provider in accordance with the Provider's Continuity Plan.</p>
                <p class="ssm-agr-p"><strong>Section 2. Authorized Responsibilities.</strong> The Support Steward is authorized to perform the tasks and access the resources outlined within the defined permission levels.</p>
                <p class="ssm-agr-p"><strong>Section 3. Confidentiality.</strong> The Support Steward agrees to maintain strict confidentiality of all patient information and practice records.</p>
                <div class="ssm-sigs">
                  <div class="ssm-sig"><span class="ssm-sig-b">SIGNED</span> Dr. Sarah Johnson — Feb 14, 2026</div>
                  <div class="ssm-sig"><span class="ssm-sig-b">COUNTERSIGNED</span> {{ m.name }} — Feb 14, 2026</div>
                </div>
              </div>
            </div>
            <div class="ssm-foot"><button class="btn btn-outline btn-sm" @click="m.type=null">Close</button><button class="ssm-gold-btn" @click="showToast('Downloading PDF','info');m.type=null">Download PDF</button></div>
          </div>
        </div>
      </Transition>

      <!-- EDIT -->
      <Transition name="ssm-fade">
        <div v-if="m.type==='edit'" class="ssm-backdrop" @click.self="m.type=null">
          <div class="ssm">
            <div class="ssm-head"><div class="ssm-title">Edit Support Steward — {{ m.name }}</div><button class="ssm-x" @click="m.type=null">×</button></div>
            <div class="ssm-body">
              <div class="ssm-field"><label class="ssm-lbl">Full Name</label><input class="ssm-inp" :value="m.name" /></div>
              <div class="ssm-field"><label class="ssm-lbl">Role / Title</label><input class="ssm-inp" placeholder="e.g. Practice Administrator" /></div>
              <div class="ssm-field"><label class="ssm-lbl">Organization</label><input class="ssm-inp" placeholder="e.g. Lotus Psychology Group" /></div>
              <div class="ssm-field"><label class="ssm-lbl">Phone</label><input class="ssm-inp" type="tel" /></div>
              <div class="ssm-field"><label class="ssm-lbl">Email</label><input class="ssm-inp" type="email" /></div>
              <div class="ssm-field"><label class="ssm-lbl">Emergency Phone (24-hr contact)</label><input class="ssm-inp" placeholder="Alternate contact" /></div>
              <div class="ssm-field"><label class="ssm-lbl">Notes</label><textarea class="ssm-ta" rows="3" placeholder="Any updates or context…"></textarea></div>
            </div>
            <div class="ssm-foot"><button class="btn btn-outline btn-sm" @click="m.type=null">Cancel</button><button class="ssm-gold-btn" @click="showToast('Changes saved','success');m.type=null">Save Changes</button></div>
          </div>
        </div>
      </Transition>

      <!-- VAULT -->
      <Transition name="ssm-fade">
        <div v-if="m.type==='vault'" class="ssm-backdrop" @click.self="m.type=null">
          <div class="ssm">
            <div class="ssm-head"><div class="ssm-title">Manage Document Vault Access — {{ m.name }}</div><button class="ssm-x" @click="m.type=null">×</button></div>
            <div class="ssm-body">
              <div class="ssm-notice">Control what documents this Support Steward can access and when.</div>
              <div v-for="opt in vaultOpts" :key="opt.v" class="ssm-vault-opt" :class="{ sel: vaultLvl===opt.v }" @click="vaultLvl=opt.v">
                <div class="ssm-radio"><span v-if="vaultLvl===opt.v" class="ssm-dot"></span></div>
                <div><div class="ssm-vault-lbl">{{ opt.label }}</div><div class="ssm-vault-sub">{{ opt.sub }}</div></div>
              </div>
            </div>
            <div class="ssm-foot"><button class="btn btn-outline btn-sm" @click="m.type=null">Cancel</button><button class="ssm-gold-btn" @click="showToast('Vault access updated','success');m.type=null">Save Access Level</button></div>
          </div>
        </div>
      </Transition>

      <!-- PERMISSIONS -->
      <Transition name="ssm-fade">
        <div v-if="m.type==='perms'" class="ssm-backdrop" @click.self="m.type=null">
          <div class="ssm ssm-lg">
            <div class="ssm-head"><div class="ssm-title">Permissions — {{ m.name }}</div><button class="ssm-x" @click="m.type=null">×</button></div>
            <div class="ssm-body">
              <div v-for="r in permRows" :key="r.text" class="ssm-perm-row">
                <span class="ssm-perm-text">{{ r.text }}</span>
                <div class="ssm-lvl-btns">
                  <button v-for="lv in levels" :key="lv.v" class="ssm-lvl" :class="[lv.cls, { 'ssm-lvl-active': r.level===lv.v }]" @click="r.level=lv.v">{{ lv.label }}</button>
                </div>
              </div>
            </div>
            <div class="ssm-foot"><button class="btn btn-outline btn-sm" @click="m.type=null">Cancel</button><button class="ssm-gold-btn" @click="showToast('Permissions saved','success');m.type=null">Save Permissions</button></div>
          </div>
        </div>
      </Transition>

      <!-- REMOVE -->
      <Transition name="ssm-fade">
        <div v-if="m.type==='remove'" class="ssm-backdrop" @click.self="m.type=null">
          <div class="ssm ssm-sm">
            <div class="ssm-head"><div class="ssm-title">Remove Support Steward</div><button class="ssm-x" @click="m.type=null">×</button></div>
            <div class="ssm-body">
              <div class="ssm-warn"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><span>Removing <strong>{{ m.name }}</strong> revokes all access immediately.</span></div>
              <div class="ssm-field"><label class="ssm-lbl">Reason for removal</label><select class="ssm-inp" v-model="removeReason"><option value="">— Select —</option><option>No longer needed</option><option>Left the organization</option><option>Trust concern</option><option>Other</option></select></div>
              <div class="ssm-field"><label class="ssm-lbl">Type "REMOVE" to confirm</label><input class="ssm-inp" v-model="removeConfirm" placeholder="Type REMOVE to confirm" /></div>
            </div>
            <div class="ssm-foot"><button class="btn btn-outline btn-sm" @click="m.type=null">Cancel</button><button class="ssm-red-btn" :disabled="removeConfirm!=='REMOVE'" @click="showToast(m.name+' removed','info');m.type=null">Remove Support Steward</button></div>
          </div>
        </div>
      </Transition>

      <!-- EDIT PERMISSIONS MODAL -->
      <Transition name="ssm-fade">
        <div v-if="editPermsModal.open" class="ssm-backdrop" @click.self="editPermsModal.open=false">
          <div class="ssm ssm-lg">
            <div class="ssm-head"><div class="ssm-title">Edit Permissions — {{ editPermsModal.name }}</div><button class="ssm-x" @click="editPermsModal.open=false">×</button></div>
            <div class="ssm-body">
              <div class="ssm-perm-person"><div class="ss-avatar" :style="{ background: editPermsModal.color, width:'32px', height:'32px', fontSize:'11px' }">{{ editPermsModal.initials }}</div><div><div style="font-size:13px;font-weight:600;color:var(--text);">{{ editPermsModal.name }} — Support Steward</div><div style="font-size:11.5px;color:var(--text-4);">Changes take effect immediately and are logged for audit</div></div></div>
              <div class="ssm-perm-warn"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><span>Expanding authorized responsibilities will require an agreement amendment and {{ editPermsModal.name?.split(' ')[0] }}'s re-signature.</span></div>
              <div v-for="section in permSections" :key="section.label">
                <div class="ssm-perm-section-lbl">{{ section.label }}</div>
                <div v-for="item in section.items" :key="item.label" class="ssm-perm-toggle-row">
                  <div><div class="ssm-perm-toggle-label">{{ item.label }}</div><div v-if="item.sub" class="ssm-perm-toggle-sub">{{ item.sub }}</div></div>
                  <label class="ss-tog"><input type="checkbox" v-model="item.on"><span class="ss-tog-track" :class="{ on: item.on }"><span class="ss-tog-thumb"></span></span></label>
                </div>
              </div>
            </div>
            <div class="ssm-foot"><button class="btn btn-outline btn-sm" @click="editPermsModal.open=false">Cancel</button><button class="ssm-gold-btn" @click="showToast('Permissions saved','success');editPermsModal.open=false">Save Permissions</button></div>
          </div>
        </div>
      </Transition>

      <!-- PENDING: RESEND -->
      <Transition name="ssm-fade">
        <div v-if="pendModal.type==='resend'" class="ssm-backdrop" @click.self="pendModal.type=null">
          <div class="ssm">
            <div class="ssm-head"><div class="ssm-title">Resend Support Steward Invitation</div><button class="ssm-x" @click="pendModal.type=null">×</button></div>
            <div class="ssm-body">
              <div class="ssm-perm-person" v-if="pendModal.item"><div class="ss-avatar" :style="{ background: pendModal.item.color, width:'32px', height:'32px', fontSize:'11px' }">{{ pendModal.item.initials }}</div><div><div style="font-size:13px;font-weight:600;">{{ pendModal.item.name }}</div><div style="font-size:11.5px;color:var(--text-4);">Originally invited {{ pendModal.item.invited }} · Expires {{ pendModal.item.expires }}</div></div></div>
              <div class="ssm-field"><label class="ssm-lbl">New Expiration</label><select class="ssm-inp"><option>30 days from today</option><option>14 days from today</option><option>60 days from today</option></select></div>
              <div class="ssm-field"><label class="ssm-lbl">Follow-up Message (Optional)</label><textarea class="ssm-ta" rows="4" :placeholder="'Hi '+pendModal.item?.name?.split(' ')[0]+', just following up on the Support Steward invitation I sent earlier…'"></textarea></div>
            </div>
            <div class="ssm-foot"><button class="btn btn-outline btn-sm" @click="pendModal.type=null">Cancel</button><button class="ssm-gold-btn" @click="showToast('Invitation resent','success');pendModal.type=null">Resend Invitation</button></div>
          </div>
        </div>
      </Transition>

      <!-- PENDING: PREVIEW AGREEMENT -->
      <Transition name="ssm-fade">
        <div v-if="pendModal.type==='preview'" class="ssm-backdrop" @click.self="pendModal.type=null">
          <div class="ssm ssm-lg">
            <div class="ssm-head"><div class="ssm-title">Agreement Preview — {{ pendModal.item?.name }} (Pending)</div><button class="ssm-x" @click="pendModal.type=null">×</button></div>
            <div class="ssm-body">
              <div class="ssm-notice">This agreement is awaiting {{ pendModal.item?.name }}'s counter-signature. Sent {{ pendModal.item?.invited }}, 2025 — expires {{ pendModal.item?.expires }}, 2025.</div>
              <div class="ssm-agr-doc">
                <div class="ssm-agr-h">Aegis Support Steward AGREEMENT — PENDING SIGNATURE</div>
                <p class="ssm-agr-m"><strong>Provider:</strong> Dr. Sarah Johnson, MD</p>
                <p class="ssm-agr-m"><strong>Proposed Support Steward:</strong> {{ pendModal.item?.name }} — Support Steward</p>
                <p class="ssm-agr-m"><strong>Sent:</strong> {{ pendModal.item?.invited }}, 2025 | <strong>Expires:</strong> {{ pendModal.item?.expires }}, 2025</p>
                <hr class="ssm-agr-hr">
                <p class="ssm-agr-p"><strong>Authorized Responsibilities:</strong> As designated across the five sections — Activation & Verification, Access & Resource Coordination, Oversight & Coordination, Financial Responsibilities, and Completion & Transition — guided by the Provider's Continuity Plan.</p>
                <div class="ssm-sigs">
                  <div class="ssm-sig"><span class="ssm-sig-b">SIGNED</span> Dr. Sarah Johnson — Signed Jan 15, 2025</div>
                  <div class="ssm-sig" style="color:var(--text-4);">⏱ {{ pendModal.item?.name }} — Awaiting signature…</div>
                </div>
              </div>
            </div>
            <div class="ssm-foot"><button class="btn btn-outline btn-sm" @click="pendModal.type=null; openPend('resend', pendModal.item)">Resend</button><button class="ssm-gold-btn" @click="pendModal.type=null">Close</button></div>
          </div>
        </div>
      </Transition>

      <!-- PENDING: EDIT BEFORE RESEND -->
      <Transition name="ssm-fade">
        <div v-if="pendModal.type==='edit'" class="ssm-backdrop" @click.self="pendModal.type=null">
          <div class="ssm">
            <div class="ssm-head"><div class="ssm-title">Edit Invitation Before Resending — {{ pendModal.item?.name }}</div><button class="ssm-x" @click="pendModal.type=null">×</button></div>
            <div class="ssm-body">
              <div class="ssm-notice">You can update the Support Steward's role or permissions before resending. The updated agreement will be sent for {{ pendModal.item?.name?.split(' ')[0] }}'s signature.</div>
              <div class="ssm-field"><label class="ssm-lbl">Support Steward Type</label><select class="ssm-inp"><option>Support Steward</option><option>Primary Support Steward</option><option>Backup Support Steward</option></select></div>
              <div class="ssm-field"><label class="ssm-lbl">Access Duration</label><select class="ssm-inp"><option>6 months — with auto renewal</option><option>1 year</option><option>Indefinite</option></select></div>
              <div class="ssm-field"><label class="ssm-lbl">Updated Personal Message</label><textarea class="ssm-ta" rows="4" :value="'Hi '+pendModal.item?.name?.split(' ')[0]+', I\'ve updated the Support Steward agreement with a few changes. Please review and sign at your earliest convenience!'"></textarea></div>
            </div>
            <div class="ssm-foot"><button class="btn btn-outline btn-sm" @click="pendModal.type=null">Cancel</button><button class="ssm-gold-btn" @click="showToast('Updated invitation sent','success');pendModal.type=null">Send Updated Invitation</button></div>
          </div>
        </div>
      </Transition>

      <!-- PENDING: CANCEL -->
      <Transition name="ssm-fade">
        <div v-if="pendModal.type==='cancel'" class="ssm-backdrop" @click.self="pendModal.type=null">
          <div class="ssm ssm-sm">
            <div class="ssm-head"><div class="ssm-title">Cancel Support Steward Invitation</div><button class="ssm-x" @click="pendModal.type=null">×</button></div>
            <div class="ssm-body" style="align-items:center;text-align:center;padding:28px 22px;">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="color:var(--text-4);"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
              <div style="font-size:16px;font-weight:700;color:var(--text);margin:10px 0 6px;">Cancel invitation to {{ pendModal.item?.name }}?</div>
              <p style="font-size:13px;color:var(--text-3);line-height:1.6;margin:0;">The invitation link will be deactivated immediately. {{ pendModal.item?.name?.split(' ')[0] }} will receive an email notification that the invitation was withdrawn.</p>
            </div>
            <div class="ssm-foot" style="justify-content:center;gap:12px;">
              <button class="btn btn-outline btn-sm" @click="pendModal.type=null">Keep Invitation</button>
              <button class="ssm-red-btn" @click="pendingInvites=pendingInvites.filter(p=>p.name!==pendModal.item?.name);pendModal.type=null;showToast('Invitation cancelled','info')">✕ Cancel Invitation</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- REINSTATE MODAL -->
      <Transition name="ssm-fade">
        <div v-if="reinstateModal.open" class="ssm-backdrop" @click.self="reinstateModal.open=false">
          <div class="ssm">
            <div class="ssm-head"><div class="ssm-title">Reinstate Support Steward Access</div><button class="ssm-x" @click="reinstateModal.open=false">×</button></div>
            <div class="ssm-body">
              <div class="ssm-perm-person" v-if="reinstateModal.steward"><div class="ss-avatar" :style="{ background: reinstateModal.steward.color, width:'32px', height:'32px', fontSize:'11px' }">{{ reinstateModal.steward.initials }}</div><div><div style="font-size:13px;font-weight:600;">{{ reinstateModal.steward.name }}</div><div style="font-size:11.5px;color:var(--text-4);">Support Steward · Suspended {{ reinstateModal.steward.agreement }}</div></div></div>
              <div class="ssm-notice" style="background:var(--green-light);border-color:var(--soft-green);color:var(--green-dark);">Reinstating {{ reinstateModal.steward?.name?.split(' ')[0] }} will restore all previously authorized permissions and resume paused task delegations.</div>
              <div class="ssm-field"><label class="ssm-lbl">Reinstatement Date</label><input class="ssm-inp" type="date" v-model="reinstateForm.date"></div>
              <div class="ssm-field">
                <label class="ssm-lbl">Review Permissions Before Reinstating?</label>
                <div style="display:flex;flex-direction:column;gap:6px;margin-top:4px;">
                  <label class="ssm-radio-row"><input type="radio" v-model="reinstateForm.reviewPerms" value="yes" style="accent-color:var(--gold-dark);"> <span style="font-size:13px;color:var(--text);">Yes — review and confirm permissions first</span></label>
                  <label class="ssm-radio-row"><input type="radio" v-model="reinstateForm.reviewPerms" value="no" style="accent-color:var(--gold-dark);"> <span style="font-size:13px;color:var(--text);">No — restore all previous permissions</span></label>
                </div>
              </div>
              <div class="ssm-field"><label class="ssm-lbl">Message to Support Steward (Optional)</label><textarea class="ssm-ta" v-model="reinstateForm.message" rows="3" placeholder="Welcome back message or any updated instructions…"></textarea></div>
            </div>
            <div class="ssm-foot"><button class="btn btn-outline btn-sm" @click="reinstateModal.open=false">Cancel</button><button class="ssm-gold-btn" @click="doReinstate">✓ Reinstate Access</button></div>
          </div>
        </div>
      </Transition>

      <!-- TOASTS -->
      <div class="ss-toasts">
        <div v-for="t in toasts" :key="t.id" class="ss-toast" :class="t.type">
          <svg v-if="t.type==='success'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span>{{ t.msg }}</span>
        </div>
      </div>

    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Components/AppLayout.vue';

defineProps({ user: { type: Object, default: () => ({}) } });

const tab = ref('mine');

const toasts = ref([]);
let tid = 0;
function showToast(msg, type = 'info') {
  const id = ++tid;
  toasts.value.push({ id, msg, type });
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id); }, 3500);
}

function goMsg() { router.visit('/provider/messages'); }

const m = reactive({ type: null, name: null });
const removeConfirm = ref('');
const removeReason  = ref('');
const vaultLvl      = ref('emergency');

function openM(type, name) { m.type = type; m.name = name; removeConfirm.value = ''; removeReason.value = ''; }

// ── Pending invitations data ──
const pendingInvites = ref([
  { initials:'JT', name:'Jordan Taylor', color:'#6a4c8c', org:'Family Counsel · Denton, TX', email:'jordan.taylor@email.com', invited:'Jan 15, 2025', expires:'Feb 14, 2025', role:'Support Steward' },
]);
const declinedInvites = ref([
  { initials:'BS', name:'Brian Santos', color:'#7a5c4a', org:'Mental Practice Director · Lotus Psychology Group · Berkeley, CA', agreement:'Sep 12, 2026', reason:'Declined invitation' },
]);

// Pending tab modals
const pendModal = reactive({ type: null, item: null });
function openPend(type, item) { pendModal.type = type; pendModal.item = item; }

// Edit permissions modal
const editPermsModal = reactive({ open: false, name: '', initials: '', color: '' });
function openEditPerms(s) {
  editPermsModal.open = true;
  editPermsModal.name = s.name;
  editPermsModal.initials = s.initials;
  editPermsModal.color = s.color;
}

const permSections = reactive([
  { label: 'ACTIVATION & VERIFICATION', items: [
    { label: 'Verify a critical moment and report it on the Provider\'s behalf', sub: '', on: true },
    { label: 'Notify the Continuity Steward and designated contacts', sub: '', on: true },
  ]},
  { label: 'ACCESS & RESOURCE COORDINATION', items: [
    { label: 'Coordinate access to the Continuity Plan and supporting documents', sub: '', on: true },
    { label: 'Vault read access (non-clinical files only)', sub: '', on: true },
    { label: 'Continuity Support vault access', sub: 'Unlocks only when continuity support is activated', on: false },
  ]},
  { label: 'OVERSIGHT & COORDINATION', items: [
    { label: 'Coordinate with the Continuity Steward during the response', sub: '', on: true },
    { label: 'Track progress against the Continuity Plan', sub: '', on: true },
  ]},
  { label: 'FINANCIAL RESPONSIBILITIES', items: [
    { label: 'Pay essential practice bills during the critical moment', sub: 'Within pre-authorized limits set in the Continuity Plan', on: false },
    { label: 'Authorize refunds or credits to clients', sub: '', on: false },
  ]},
  { label: 'COMPLETION & TRANSITION', items: [
    { label: 'Confirm the Continuity Plan was carried out', sub: '', on: true },
    { label: 'Hand off remaining responsibilities back to the Provider or designated successor', sub: '', on: true },
  ]},
]);

// Reinstate modal
const reinstateModal = reactive({ open: false, steward: null });
const reinstateForm = reactive({ date: '', reviewPerms: 'no', message: '' });
function openReinstate(s) { reinstateModal.open = true; reinstateModal.steward = s; reinstateForm.date = ''; reinstateForm.message = ''; reinstateForm.reviewPerms = 'no'; }
function doReinstate() {
  if (reinstateModal.steward) reinstateModal.steward.status = 'ACTIVE';
  reinstateModal.open = false;
  showToast(reinstateModal.steward?.name + ' reinstated', 'success');
}

// Guidance accordion sections
const guidanceSections = reactive([
  {
    open: true,
    title: 'Onboarding & Planning',
    icon: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
    items: [
      'Review the Continuity Plan together so the role and expectations are clear.',
      "Know how to reach the Continuity Steward and the practice's key contacts.",
      'Confirm where access information lives in the Vault and how it unlocks during a verified critical moment.',
      'Keep your own contact details current and re-attest once a year.',
    ],
  },
  {
    open: true,
    title: 'Active Critical Moment Guidance',
    icon: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    items: [
      'Wait for verification — act only once the critical incident has been confirmed.',
      'Follow the responsibilities outlined in the Continuity Plan, within your authorized permissions.',
      'Coordinate with the Continuity Steward and keep a simple record of the actions you take.',
      'Communicate updates to clients and contacts as the plan directs.',
    ],
  },
  {
    open: true,
    title: 'When the Continuity Steward Is Unavailable or Non-Responsive',
    icon: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    items: [
      "Try the Continuity Steward's listed contacts, then the Alternate Continuity Steward.",
      'If neither can be reached, notify the Aegis team so the plan can be supported.',
      'Do not act beyond your authorized permissions while you wait for direction.',
      'Keep a record of your attempts to make contact.',
    ],
  },
]);

const vaultOpts = [
  { v:'emergency', label:'Emergency Only (Recommended)', sub:'Vault unlocks automatically when continuity support is activated.' },
  { v:'read-only', label:'Read Only Access',             sub:'Support Steward can view designated documents at any time.' },
  { v:'none',      label:'No Access',                    sub:'Emergency credentials must be shared separately.' },
];

const levels = [
  { v:'FULL',      label:'FULL',      cls:'lvl-full'    },
  { v:'LIMITED',   label:'LIMITED',   cls:'lvl-limited' },
  { v:'READ ONLY', label:'READ ONLY', cls:'lvl-read'    },
  { v:'NONE',      label:'NONE',      cls:'lvl-none'    },
];

const permRows = reactive([
  { text:'Appointment scheduling & calendar management',        level:'FULL'      },
  { text:'Patient intake calls and initial communication',      level:'FULL'      },
  { text:'Referral coordination and administrative follow-ups', level:'LIMITED'   },
  { text:'Document Vault — administrative files only',          level:'READ ONLY' },
  { text:'Billing and insurance verification',                  level:'NONE'      },
]);

const chip = (text, level, cls) => ({ text, level, cls });

const stewards = ref([
  {
    initials:'LJ', name:'Linda Johnson', color:'#a0813e', status:'ACTIVE',
    org:'Practice Administrator · Lotus Psychology Group · San Francisco, CA',
    phone:'(415) 555-0742', email:'linda.johnson@gmail.com',
    agreement:'Feb 14, 2026', reviewDue:'Feb 14, 2027',
    responsibilities:[
      chip('Appointment scheduling & calendar management',        'FULL',      'ch-full'),
      chip('Patient intake calls and initial communication',      'FULL',      'ch-full'),
      chip('Referral coordination and administrative follow-ups', 'LIMITED',   'ch-limited'),
      chip('Document Vault — administrative files only',          'READ ONLY', 'ch-read'),
      chip('Billing and insurance verification',                  'NONE',      'ch-none'),
    ],
  },
  {
    initials:'JR', name:'James Rodriguez', color:'#7a5c4a', status:'ACTIVE',
    org:'Spouse & Personal Representative · San Francisco, CA',
    phone:'(415) 555-0176', email:'james.rodriguez@protonmail.com',
    agreement:'Feb 15, 2026', reviewDue:'Feb 15, 2027',
    responsibilities:[
      chip('Insurance verification and prior authorizations',  'FULL',      'ch-full'),
      chip('Claims submission and payment posting',            'FULL',      'ch-full'),
      chip('Billing reports — read access only',               'READ ONLY', 'ch-read'),
      chip('Appointment scheduling',                           'NONE',      'ch-none'),
      chip('Document Vault access',                            'NONE',      'ch-none'),
    ],
  },
  {
    initials:'RP', name:'Rachel Pham', color:'#7a5c4a', status:'SUSPENDED',
    org:'Office Manager · Lotus Psychology Group · San Francisco, CA',
    agreement:'Sep 10, 2025', reviewDue: null,
    phone: null, email: null, responsibilities: null,
  },
]);

const notifyToggles = reactive([
  { label:'Annual Re-Attestation is complete',                on:true },
  { label:'A Support Steward requests changes',               on:true },
  { label:'A Support Steward updates their information',      on:true },
  { label:'Roles or permissions change',                      on:true },
  { label:'Important Documents are accessed',                 on:true },
  { label:'A critical incident is reported',                  on:true },
  { label:'A Continuity Response is activated',               on:true },
]);
</script>

<style scoped>
.ss-hero { display:flex; align-items:flex-start; justify-content:space-between; gap:20px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:22px 26px; margin-bottom:12px; box-shadow:var(--shadow-xs); flex-wrap:wrap; }
.ss-eyebrow { font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--gold-dark); margin-bottom:5px; }
.ss-title { font-family:var(--font-serif); font-size:26px; font-weight:700; color:var(--text); margin:0 0 6px; }
.ss-sub { font-size:12.5px; color:var(--text-3); margin:0; line-height:1.5; max-width:560px; }
.ss-hero-actions { display:flex; gap:8px; flex-shrink:0; align-items:center; flex-wrap:wrap; }
.ss-btn-icon { display:inline-flex; align-items:center; gap:6px; }

.ss-plan-notice { display:flex; align-items:center; gap:9px; padding:10px 14px; background:var(--blue-light); border:1px solid var(--soft-blue); border-radius:8px; margin-bottom:12px; font-size:12.5px; color:var(--blue-dark); }

.ss-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:14px; }
.ss-stat { display:flex; align-items:center; gap:14px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:16px 18px; box-shadow:var(--shadow-xs); }
.ss-stat-icon { width:36px; height:36px; border-radius:8px; background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ss-stat-gold  { background:rgba(160,129,62,.1); color:var(--gold-dark); }
.ss-stat-blue  { background:var(--blue-light); color:var(--blue-dark); }
.ss-stat-green { background:var(--green-light); color:var(--green-dark); }
.ss-stat-val { font-family:var(--font-serif); font-size:22px; font-weight:700; color:var(--text); line-height:1; }
.ss-stat-lbl { font-size:11px; color:var(--text-4); margin-top:3px; }

.ss-tabs { display:flex; align-items:center; gap:2px; border-bottom:1px solid var(--border); margin-bottom:0; }
.ss-tab { display:inline-flex; align-items:center; gap:7px; padding:9px 14px; font-size:13px; font-weight:500; color:var(--text-3); background:transparent; border:none; border-bottom:2px solid transparent; cursor:pointer; transition:color .15s,border-color .15s; margin-bottom:-1px; white-space:nowrap; }
.ss-tab:hover { color:var(--text); }
.ss-tab.active { color:var(--text); font-weight:600; border-bottom-color:var(--gold-dark); }
.ss-tab-count { font-size:11px; font-weight:700; background:var(--surface-3); color:var(--text-2); border-radius:99px; padding:1px 7px; }
.ss-tab-count.active { background:var(--gold-dark); color:#fff; }

.ss-tab-sub { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:10px 0 14px; border-bottom:1px solid var(--border); margin-bottom:14px; }
.ss-tab-desc { font-size:13px; color:var(--text-3); }
.ss-add-sm { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; font-size:12px; font-weight:600; color:var(--gold-dark); background:rgba(160,129,62,.08); border:1px solid var(--fade-gold,rgba(160,129,62,.4)); border-radius:6px; cursor:pointer; transition:all .15s; }
.ss-add-sm:hover { background:rgba(160,129,62,.15); }

.ss-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:20px 22px; margin-bottom:14px; box-shadow:var(--shadow-xs); }
.ss-suspended { border-left:3px solid var(--red); }
.ss-card-head { display:flex; align-items:flex-start; gap:14px; margin-bottom:16px; }
.ss-avatar { display:flex; align-items:center; justify-content:center; border-radius:10px; color:#fff; font-weight:700; flex-shrink:0; width:48px; height:48px; font-size:15px; }
.ss-info { min-width:0; flex:1; }
.ss-name-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:3px; }
.ss-name { font-size:14.5px; font-weight:700; color:var(--text); }
.ss-badge-role { font-size:9.5px; font-weight:700; letter-spacing:.05em; padding:2px 8px; border-radius:99px; background:rgba(160,129,62,.1); color:var(--gold-dark); border:1px solid var(--fade-gold,rgba(160,129,62,.4)); }
.ss-badge-status { font-size:10px; font-weight:700; border-radius:99px; padding:2px 8px; }
.ss-active { color:var(--green-dark); background:var(--green-light); }
.ss-susp   { color:var(--red); background:var(--red-light); }
.ss-org  { font-size:12px; color:var(--text-4); margin-bottom:6px; }
.ss-meta { display:flex; flex-wrap:wrap; gap:12px; }
.ss-mi   { font-size:12px; color:var(--text-4); }

.ss-resp-block { background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px 16px; margin-bottom:14px; }
.ss-resp-lbl { font-size:9.5px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-4); margin-bottom:10px; }
.ss-resp-row { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:7px 0; border-bottom:1px solid var(--border); }
.ss-resp-row:last-child { border-bottom:none; padding-bottom:0; }
.ss-resp-text { font-size:12.5px; color:var(--text-2); flex:1; }
.ss-chip { font-size:9.5px; font-weight:700; letter-spacing:.04em; border-radius:99px; padding:2px 9px; flex-shrink:0; }
.ch-full    { background:var(--green-light); color:var(--green-dark); border:1px solid var(--soft-green,rgba(34,119,68,.3)); }
.ch-limited { background:rgba(232,169,74,.12); color:var(--orange-dark); border:1px solid rgba(232,169,74,.4); }
.ch-read    { background:var(--blue-light); color:var(--blue-dark); border:1px solid var(--soft-blue,rgba(30,95,170,.3)); }
.ch-none    { background:var(--surface-3); color:var(--text-4); border:1px solid var(--border); }

.ss-susp-warn { display:flex; align-items:flex-start; gap:9px; padding:10px 13px; background:var(--red-light); border:1px solid var(--soft-red,rgba(160,45,34,.35)); border-radius:8px; font-size:12.5px; color:var(--red); line-height:1.5; margin-bottom:14px; }

.ss-actions { display:flex; gap:6px; padding-top:10px; border-top:1px solid var(--border); flex-wrap:wrap; }
.ss-act { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:6px; border:1px solid var(--border); background:var(--surface); color:var(--text-3); cursor:pointer; transition:all .15s; }
.ss-act:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.ss-danger:hover { border-color:var(--red); color:var(--red); background:var(--red-light); }
.ss-reinstate { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; font-size:12.5px; font-weight:600; color:var(--green-dark); background:var(--green-light); border:1px solid var(--soft-green,rgba(34,119,68,.3)); border-radius:6px; cursor:pointer; transition:all .15s; }

.ss-add-dashed { display:flex; align-items:center; justify-content:center; gap:10px; width:100%; padding:22px; border:2px dashed var(--border); border-radius:var(--radius-lg,14px); background:transparent; color:var(--text-4); font-size:13.5px; font-weight:600; cursor:pointer; transition:all .18s; margin-bottom:14px; }
.ss-add-dashed:hover { border-color:var(--gold-dark); color:var(--gold-dark); background:rgba(160,129,62,.04); }

.ss-for-card { display:flex; align-items:center; gap:14px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:16px 18px; cursor:pointer; transition:box-shadow .15s,border-color .15s; box-shadow:var(--shadow-xs); margin-bottom:8px; }
.ss-for-card:hover { box-shadow:var(--shadow-sm); border-color:var(--gold-dark); }

.ss-empty { display:flex; flex-direction:column; align-items:center; gap:12px; padding:50px 20px; color:var(--text-4); }
.ss-empty p { font-size:14px; margin:0; color:var(--text-3); }

.ss-notify { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:20px 22px; margin-bottom:14px; box-shadow:var(--shadow-xs); margin-top:4px; }
.ss-notify-title { font-size:15px; font-weight:700; color:var(--text); margin-bottom:4px; }
.ss-notify-sub { font-size:12.5px; color:var(--text-4); margin-bottom:16px; }
.ss-notify-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 0; border-bottom:1px solid var(--border); }
.ss-notify-row:last-child { border-bottom:none; }
.ss-notify-label { font-size:13px; color:var(--text-2); }
.ss-tog { position:relative; display:inline-block; width:40px; height:22px; flex-shrink:0; }
.ss-tog input { opacity:0; width:0; height:0; }
.ss-tog-track { position:absolute; inset:0; background:var(--border-dark); border-radius:99px; cursor:pointer; transition:background .2s; }
.ss-tog-track.on { background:var(--gold-dark); }
.ss-tog-thumb { position:absolute; width:18px; height:18px; left:2px; top:2px; background:#fff; border-radius:50%; transition:transform .2s; }
.ss-tog-track.on .ss-tog-thumb { transform:translateX(18px); }

/* Modals */
.ssm-backdrop { position:fixed; inset:0; z-index:1000; background:rgba(30,28,26,.45); backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center; padding:20px; }
.ssm { background:var(--surface); border:1px solid var(--border); border-radius:14px; box-shadow:0 24px 64px rgba(30,28,26,.2); width:100%; max-width:540px; max-height:90vh; display:flex; flex-direction:column; overflow:hidden; }
.ssm-sm { max-width:420px; }
.ssm-lg { max-width:640px; }
.ssm-head { display:flex; align-items:center; justify-content:space-between; padding:18px 22px 14px; border-bottom:1px solid var(--border); flex-shrink:0; }
.ssm-title { font-family:var(--font-serif); font-size:17px; font-weight:700; color:var(--text); }
.ssm-x { width:28px; height:28px; padding:0; font-size:18px; line-height:1; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--border); border-radius:6px; background:var(--surface); color:var(--text-3); cursor:pointer; }
.ssm-x:hover { border-color:var(--text); color:var(--text); }
.ssm-body { padding:18px 22px; overflow-y:auto; display:flex; flex-direction:column; gap:14px; flex:1; }
.ssm-foot { display:flex; align-items:center; justify-content:flex-end; gap:8px; padding:14px 22px; border-top:1px solid var(--border); background:var(--surface-2); flex-shrink:0; }
.ssm-gold-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; font-size:12.5px; font-weight:600; background:var(--gold-dark); color:#fff; border:1px solid var(--gold-dark); border-radius:var(--radius-sm,8px); cursor:pointer; transition:background .15s; }
.ssm-gold-btn:hover { background:var(--gold); border-color:var(--gold); }
.ssm-red-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; font-size:12.5px; font-weight:600; background:var(--red); color:#fff; border:1px solid var(--red); border-radius:var(--radius-sm,8px); cursor:pointer; }
.ssm-red-btn:disabled { opacity:.4; cursor:not-allowed; }

.ssm-field { display:flex; flex-direction:column; gap:5px; }
.ssm-lbl { font-size:11.5px; font-weight:600; color:var(--text-2); }
.ssm-inp { padding:9px 12px; font-size:13.5px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:8px; outline:none; transition:border-color .15s; width:100%; box-sizing:border-box; }
.ssm-inp:focus { border-color:var(--gold-dark); }
.ssm-ta { padding:9px 12px; font-size:13px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:8px; outline:none; resize:vertical; min-height:70px; line-height:1.6; box-sizing:border-box; width:100%; transition:border-color .15s; }
.ssm-ta:focus { border-color:var(--gold-dark); }
.ssm-notice { display:flex; align-items:flex-start; gap:8px; padding:9px 12px; background:var(--blue-light); border:1px solid var(--soft-blue); border-radius:8px; font-size:12.5px; color:var(--blue-dark); line-height:1.5; }
.ssm-warn { display:flex; align-items:flex-start; gap:9px; padding:10px 13px; background:var(--red-light); border:1px solid var(--soft-red,rgba(160,45,34,.35)); border-radius:8px; font-size:12.5px; color:var(--red); line-height:1.5; }

.ssm-agr-status { display:flex; align-items:center; gap:7px; padding:8px 12px; background:var(--green-light); border:1px solid var(--soft-green,rgba(34,119,68,.3)); border-radius:8px; font-size:12.5px; color:var(--green-dark); }
.ssm-agr-doc { border:1px solid var(--border); border-radius:8px; padding:18px 20px; }
.ssm-agr-h { font-family:var(--font-serif); font-size:15px; font-weight:700; color:var(--text); text-align:center; margin-bottom:12px; }
.ssm-agr-m { font-size:12.5px; color:var(--text-2); margin-bottom:4px; line-height:1.5; }
.ssm-agr-hr { height:1px; background:var(--border); border:none; margin:12px 0; }
.ssm-agr-p { font-size:12.5px; color:var(--text-2); line-height:1.65; margin-bottom:10px; }
.ssm-sigs { display:flex; flex-direction:column; gap:6px; padding-top:12px; border-top:1px solid var(--border); }
.ssm-sig { display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--text-2); }
.ssm-sig-b { font-size:9.5px; font-weight:700; padding:2px 7px; background:var(--green-light); color:var(--green-dark); border-radius:4px; }

.ssm-vault-opt { display:flex; align-items:flex-start; gap:10px; padding:11px 13px; border:1.5px solid var(--border); border-radius:8px; cursor:pointer; transition:border-color .15s,background .15s; }
.ssm-vault-opt.sel { border-color:var(--gold-dark); background:rgba(160,129,62,.06); }
.ssm-radio { width:16px; height:16px; border-radius:50%; border:1.5px solid var(--border-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px; }
.ssm-vault-opt.sel .ssm-radio { border-color:var(--gold-dark); }
.ssm-dot { width:8px; height:8px; border-radius:50%; background:var(--gold-dark); }
.ssm-vault-lbl { font-size:13px; font-weight:600; color:var(--text); margin-bottom:2px; }
.ssm-vault-sub { font-size:11.5px; color:var(--text-4); }

.ssm-perm-row { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 0; border-bottom:1px solid var(--border); }
.ssm-perm-row:last-child { border-bottom:none; }
.ssm-perm-text { font-size:12.5px; color:var(--text-2); flex:1; }
.ssm-lvl-btns { display:flex; gap:4px; flex-shrink:0; }
.ssm-lvl { font-size:10px; font-weight:700; padding:3px 8px; border-radius:99px; border:1px solid var(--border); background:var(--surface-2); color:var(--text-4); cursor:pointer; transition:all .15s; }
.lvl-full.ssm-lvl-active    { background:var(--green-light); color:var(--green-dark); border-color:var(--soft-green,rgba(34,119,68,.3)); }
.lvl-limited.ssm-lvl-active { background:rgba(232,169,74,.12); color:var(--orange-dark); border-color:rgba(232,169,74,.4); }
.lvl-read.ssm-lvl-active    { background:var(--blue-light); color:var(--blue-dark); border-color:var(--soft-blue,rgba(30,95,170,.3)); }
.lvl-none.ssm-lvl-active    { background:var(--surface-3); color:var(--text-3); border-color:var(--border-dark); }

.ssm-fade-enter-active, .ssm-fade-leave-active { transition:opacity .2s ease; }
.ssm-fade-enter-active .ssm, .ssm-fade-leave-active .ssm { transition:transform .2s ease; }
.ssm-fade-enter-from, .ssm-fade-leave-to { opacity:0; }
.ssm-fade-enter-from .ssm { transform:translateY(-10px) scale(0.98); }

.ss-toasts { position:fixed; bottom:22px; right:22px; z-index:4000; display:flex; flex-direction:column; gap:10px; }
.ss-toast { display:flex; align-items:center; gap:9px; padding:11px 16px; border-radius:var(--radius); background:var(--text); color:var(--text-inverted); font-size:13px; font-weight:600; box-shadow:var(--shadow-lg); max-width:360px; }
.ss-toast.success { background:var(--green-dark); }

@media(max-width:860px) { .ss-stats { grid-template-columns:repeat(2,1fr); } .ss-tabs { overflow-x:auto; } }

/* ── Permissions tab ── */
.ss-perm-overview-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.ss-perm-card { display:flex; align-items:center; gap:12px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:14px 18px; margin-bottom:8px; box-shadow:var(--shadow-xs); }
.ss-perm-card-left { display:flex; align-items:center; gap:10px; flex:1; min-width:0; }
.ss-perm-chips { display:flex; flex-wrap:wrap; gap:4px; margin-right:10px; }

/* ── Guidance accordion ── */
.ss-guidance-intro { font-size:12.5px; color:var(--text-3); margin-bottom:14px; line-height:1.55; }
.ss-accord { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); margin-bottom:10px; box-shadow:var(--shadow-xs); overflow:hidden; }
.ss-accord-head { display:flex; align-items:center; gap:10px; width:100%; padding:14px 18px; background:transparent; border:none; cursor:pointer; text-align:left; transition:background .12s; }
.ss-accord-head:hover { background:var(--surface-2); }
.ss-accord-icon { color:var(--text-4); display:flex; align-items:center; flex-shrink:0; }
.ss-accord-title { flex:1; font-family:var(--font-sans); font-size:13.5px; font-weight:600; color:var(--text); }
.ss-accord-arrow { flex-shrink:0; color:var(--text-4); transition:transform .2s ease; }
.ss-accord-arrow.open { transform:rotate(180deg); }
.ss-accord-body { padding:0 18px 16px 42px; border-top:1px solid var(--border); display:flex; flex-direction:column; gap:10px; padding-top:14px; }
.ss-accord-item { display:flex; align-items:flex-start; gap:9px; font-family:var(--font-sans); font-size:13px; color:var(--text-2); line-height:1.55; }
.ss-guidance-footer { display:flex; align-items:center; gap:9px; padding:11px 14px; background:var(--blue-light); border:1px solid var(--soft-blue); border-radius:8px; margin-top:6px; font-size:12.5px; color:var(--blue-dark); }

/* ── Edit permissions modal ── */
.ssm-perm-person { display:flex; align-items:center; gap:10px; padding:10px 13px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; }
.ssm-perm-warn { display:flex; align-items:flex-start; gap:8px; padding:9px 12px; background:rgba(232,169,74,.08); border:1px solid rgba(232,169,74,.4); border-radius:8px; font-size:12.5px; color:var(--orange-dark); line-height:1.5; }
.ssm-perm-section-lbl { font-size:9.5px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-4); margin:14px 0 8px; }
.ssm-perm-toggle-row { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:10px 12px; border:1px solid var(--border); border-radius:7px; margin-bottom:4px; }
.ssm-perm-toggle-label { font-size:13px; font-weight:500; color:var(--text); margin-bottom:2px; }
.ssm-perm-toggle-sub { font-size:11.5px; color:var(--text-4); }
.ssm-radio-row { display:flex; align-items:center; gap:8px; cursor:pointer; padding:6px 0; }
</style>
