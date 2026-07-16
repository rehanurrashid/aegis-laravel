<template>
  <AppLayout>
    <!-- HERO BANNER -->
    <AegisHeroBanner
      eyebrow="PROVIDER PORTAL"
      title="Support Stewards"
      subtitle="Designate trusted individuals to support communication, coordination, and key tasks during a critical moment, guided by your Continuity Plan."
      quiet
    >
      <template #actions>
        <a :href="route('activity.index') + '?module=support_stewards'" class="btn-hero-ghost is-on-light" data-tooltip="Module activity">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
        <button type="button" class="btn-hero-ghost is-on-light" @click="router.visit(route('provider.network.index') + '?scope=ss')">
          <AegisIcon name="search" :size="14" /> Browse SS Directory
        </button>
        <button class="btn-hero-solid is-on-light" @click="handleAddSS">
          <AegisIcon name="plus" :size="14" /> Add Support Steward
        </button>
      </template>
    </AegisHeroBanner>

    <!-- STAT CHIPS (sibling of hero banner, never inside) -->
    <div class="stat-chips-row">
      <AegisStatChip
        icon="users"
        :value="activeCount"
        label="Active Support Stewards"
      />
      <AegisStatChip
        icon="check-circle"
        :value="pendingCount"
        label="Pending Invite"
        style="cursor:pointer"
        @click="activeTab = 'pending'"
      />

      <AegisStatChip
        icon="calendar"
        :value="nextReviewLabel"
        label="Annual Attestation Due"
      />
    </div>

    <!-- ANNUAL REVIEW ALERT -->
    <PlanReviewAlert
      :plan-status="planStatus"
      :annual-review-date="annualReviewDate"
      :has-draft-in-progress="hasDraftInProgress"
      :draft-plan-version="draftPlanVersion"
      context="ss"
    />

    <!-- TIER ALERT -->
    <div class="alert alert-info" style="margin-bottom:14px">
      <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
      <div class="alert-content" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span>
          Your Continuity <strong>{{ tier === 'access' ? 'Access' : 'Practice' }}</strong> plan includes
          up to <strong>{{ ssMax }} Support Steward{{ ssMax === 1 ? '' : 's' }}</strong>
          &middot; <strong>{{ ssCount }} of {{ ssMax }}</strong> in use{{ atLimit ? ' (limit reached)' : '' }}.
        </span>
        <a
          v-if="tier === 'access'"
          href="#"
          style="font-weight:700;color:var(--gold-dark);text-decoration:none"
          @click.prevent="openModal('upgradeModal')"
        >Upgrade for more &rarr;</a>
      </div>
    </div>

    <!-- TABS -->
    <div class="tabs-segmented">
      <button class="tab-pill" :class="{ active: activeTab === 'mydsr' }" @click="activeTab = 'mydsr'">
        My Support Stewards
        <span class="badge-pill">{{ rosterCount }}</span>
      </button>
      <button class="tab-pill" :class="{ active: activeTab === 'pending' }" @click="activeTab = 'pending'">
        Pending
        <span class="badge-pill">{{ pendingCount }}</span>
      </button>
      <button v-if="suspended.length" class="tab-pill" :class="{ active: activeTab === 'suspended' }" @click="activeTab = 'suspended'">
        Suspended <span class="badge-pill">{{ suspended.length }}</span>
      </button>
      <button v-if="servingAsSsFor?.length" class="tab-pill" :class="{ active: activeTab === 'iamdsr' }" @click="activeTab = 'iamdsr'">
        I'm SS For <span class="badge-pill">{{ servingAsSsFor.length }}</span>
      </button>
      <button class="tab-pill" :class="{ active: activeTab === 'notifications' }" @click="activeTab = 'notifications'">
        Notifications
      </button>
    </div>

    <!-- ═══ TAB: MY SUPPORT STEWARDS ═══ -->
    <div v-show="activeTab === 'mydsr'">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
        <p style="font-size:13px;color:var(--text-3);margin:0">Staff authorized to act on your behalf within defined permission boundaries.</p>
        <button class="btn btn-primary" @click="handleAddSS">
          <AegisIcon name="plus" :size="13" /> Add Support Steward
        </button>
      </div>

      <template v-if="stewards.length">
        <!-- ACTIVE -->
        <div v-for="s in stewards" :key="s.id" class="dsr-card active">
          <div class="avatar avatar-lg avatar-gold">{{ initials(s) }}</div>
          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
              <span class="dsr-name">{{ fullName(s) }}</span>
              <AegisBadge variant="gold" icon="user">Support Steward</AegisBadge>
              <AegisBadge variant="green"><span class="status-dot green" style="display:inline-block;margin-right:4px"></span>Active</AegisBadge>
            </div>
            <div class="dsr-sub">{{ subLine(s) }}</div>
            <div class="dsr-meta">
              <span v-if="s.steward?.phone"><AegisIcon name="phone" :size="12" /> {{ s.steward.phone }}</span>
              <span v-if="s.steward?.email"><AegisIcon name="mail" :size="12" /> {{ s.steward.email }}</span>
              <span v-if="s.signed_at"><AegisIcon name="file-text" :size="12" /> Agreement: {{ fmtDate(s.signed_at) }}</span>
              <span v-if="s.review_due_at"><AegisIcon name="refresh-cw" :size="12" /> Review Due: {{ fmtDate(s.review_due_at) }}</span>
            </div>
            <div v-if="s.status === 'active'" class="dsr-resp-line">
              <AegisIcon name="shield-check" :size="13" style="color:var(--gold-dark);flex-shrink:0;" />
              <span>Authorized to verify critical incidents and trigger the Continuity Plan</span>
            </div>
            <div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap">
              <button class="btn-icon" data-tooltip="Edit" @click="openEdit(s)"><AegisIcon name="pencil" :size="14" /></button>
              <button class="btn-icon" data-tooltip="View Agreement" @click="openAgreement(s)"><AegisIcon name="file-text" :size="14" /></button>
              <button class="btn-icon" data-tooltip="Manage Access" @click="openManageAccess(s)"><AegisIcon name="sliders" :size="14" /></button>
              <a :href="route('activity.index') + '?module=support_stewards'" class="btn-icon" data-tooltip="View activity">
                <AegisIcon name="clock" :size="14" />
              </a>
              <button class="btn-icon btn-icon-danger" data-tooltip="Remove Support Steward" @click="openRemove(s)"><AegisIcon name="trash" :size="14" /></button>
            </div>
          </div>
        </div>

      </template>

      <AegisEmptyState v-else icon="users" title="No Support Stewards Yet" description="Add your first Support Steward to delegate day-to-day practice tasks.">
        <template #actions>
          <button class="btn btn-primary" @click="handleAddSS"><AegisIcon name="plus" :size="14" /> Add Support Steward</button>
        </template>
      </AegisEmptyState>

    </div>

    <!-- ═══ TAB: PENDING ═══ -->
    <div v-show="activeTab === 'pending'">
      <p style="font-size:13px;color:var(--text-3);margin-bottom:16px">Support Steward invitations you have sent that are awaiting acceptance.</p>
      <AegisEmptyState v-if="!pending.length && !invited.length && !declined.length && !archived.length" icon="mail" title="No Pending Invitations" description="Support Steward invitations you send will appear here until accepted." />

      <!-- PENDING / INVITED -->
      <div v-for="s in [...pending, ...invited]" :key="s.id" class="dsr-card pending" style="align-items:center">
        <div class="avatar avatar-lg avatar-dark">{{ initials(s) }}</div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
            <span class="dsr-name">{{ fullName(s) }}</span>
            <AegisBadge variant="gold" icon="check-circle">Awaiting Response</AegisBadge>
            <AegisBadge variant="gold" icon="user">Support Steward</AegisBadge>
          </div>
          <div class="dsr-sub">{{ subLine(s) }}</div>
          <div class="dsr-meta">
            <span v-if="s.steward?.email"><AegisIcon name="mail" :size="12" /> {{ s.steward.email }}</span>
            <span v-if="s.invited_at"><AegisIcon name="calendar" :size="12" /> Invited: {{ fmtDate(s.invited_at) }}</span>
            <span v-if="s.expires_at"><AegisIcon name="check-circle" :size="12" /> Expires: {{ fmtDate(s.expires_at) }}</span>
          </div>
          <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
            <button class="btn-icon" data-tooltip="Resend Invitation" @click="openResend(s)"><AegisIcon name="send" :size="14" /></button>
            <button class="btn-icon" data-tooltip="Preview Agreement" @click="openAgreement(s)"><AegisIcon name="file-text" :size="14" /></button>
            <button class="btn btn-danger" @click="openCancelInvite(s)"><AegisIcon name="x" :size="13" /> Cancel Invitation</button>
          </div>
        </div>
        <div v-if="s.invited_at" style="text-align:center;flex-shrink:0;padding-left:8px">
          <div style="width:44px;height:44px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark);display:flex;align-items:center;justify-content:center"><AegisIcon name="activity" :size="24" /></div>
          <div style="display:flex;align-items:center;justify-content:center;gap:4px;font-size:11px;color:var(--text-3);margin-top:4px"><AegisIcon name="clock" :size="12" /> Sent {{ daysSince(s.invited_at) }}</div>
        </div>
      </div>

      <!-- DECLINED -->
      <div v-for="s in declined" :key="s.id" class="dsr-card declined" style="align-items:center">
        <div class="avatar avatar-lg avatar-dark">{{ initials(s) }}</div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
            <span class="dsr-name">{{ fullName(s) }}</span>
            <AegisBadge variant="red" icon="x">Declined</AegisBadge>
          </div>
          <div class="dsr-sub">{{ subLine(s) }}</div>
          <div class="dsr-meta">
            <span v-if="s.steward?.email"><AegisIcon name="mail" :size="12" /> {{ s.steward.email }}</span>
            <span v-if="s.declined_at"><AegisIcon name="calendar" :size="12" /> Declined: {{ fmtDate(s.declined_at) }}</span>
          </div>
          <div v-if="s.declined_reason" style="margin-top:10px;padding:10px 12px;background:var(--surface);border-radius:var(--radius-sm);border-left:3px solid var(--red-dark)">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-4);font-weight:700;margin-bottom:3px">Declination note</div>
            <div style="font-size:13px;color:var(--text-2);font-style:italic">&ldquo;{{ s.declined_reason }}&rdquo;</div>
          </div>
          <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
            <button class="btn btn-primary" @click="handleAddSS"><AegisIcon name="plus" :size="13" /> Invite Someone Else</button>
            <button class="btn-icon" data-tooltip="Archive this record" @click="confirmAction('Archive this declined invitation?', () => submitArchiveSteward(s), { title: 'Archive Record', btnLabel: 'Archive', type: 'danger' })"><AegisIcon name="trash" :size="14" /></button>
          </div>
        </div>
      </div>

      <!-- ARCHIVED -->
      <div v-for="s in archived" :key="s.id" class="dsr-card archived" style="align-items:center">
        <div class="avatar avatar-lg avatar-dark">{{ initials(s) }}</div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
            <span class="dsr-name">{{ fullName(s) }}</span>
            <AegisBadge variant="gray" icon="archive">Archived</AegisBadge>
          </div>
          <div class="dsr-sub">{{ subLine(s) }}</div>
          <div class="dsr-meta">
            <span v-if="s.signed_at"><AegisIcon name="file-text" :size="12" /> Former agreement: {{ fmtDate(s.signed_at) }}</span>
          </div>
          <div style="display:flex;gap:8px;margin-top:12px">
            <button class="btn btn-outline" @click="openReinstate(s)"><AegisIcon name="refresh-cw" :size="14" /> Reinstate</button>
          </div>
        </div>
      </div>
    </div>


    <!-- ═══ TAB: SUSPENDED ═══ -->
    <div v-show="activeTab === 'suspended'">
      <p style="font-size:13px;color:var(--text-3);margin-bottom:16px">Support Stewards with temporarily suspended access.</p>
      <AegisEmptyState v-if="!suspended.length" icon="pause" title="No Suspended Stewards" description="Any suspended Support Stewards will appear here." />
      <div v-for="s in suspended" :key="s.id" class="dsr-card suspended">
        <div class="avatar avatar-lg avatar-dark">{{ initials(s) }}</div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
            <span class="dsr-name">{{ fullName(s) }}</span>
            <AegisBadge variant="gold" icon="user">Support Steward</AegisBadge>
            <AegisBadge variant="red"><AegisIcon name="pause" :size="11" /> Suspended</AegisBadge>
          </div>
          <div class="dsr-sub">{{ subLine(s) }}</div>
          <div class="dsr-meta">
            <span v-if="s.signed_at"><AegisIcon name="file-text" :size="12" /> Agreement: {{ fmtDate(s.signed_at) }}</span>
          </div>
          <div class="alert alert-danger" style="margin-top:12px">
            <div class="alert-icon"><AegisIcon name="lock" :size="16" /></div>
            <div class="alert-content"><strong>Access suspended:</strong> {{ s.declined_reason || 'Access suspended. All task delegation paused.' }}</div>
          </div>
          <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
            <button class="btn btn-outline" @click="openReinstate(s)"><AegisIcon name="check" :size="14" /> Reinstate</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ TAB: I'M SS FOR ═══ -->
    <div v-show="activeTab === 'iamdsr'">
      <div class="alert alert-info" style="margin-bottom:20px;">
        <div class="alert-icon"><AegisIcon name="shield" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">You are a Support Steward for {{ servingAsSsFor?.length ?? 0 }} provider{{ (servingAsSsFor?.length ?? 0) !== 1 ? 's' : '' }}.</div>
          <div>This is a summary of your active SS designations. For full SS work — daily check-ins, task list, critical-incident reporting, and agreement archive — open your Support Steward Portal.</div>
          <div style="margin-top:10px;">
            <a :href="route('ss.dashboard')" class="btn btn-outline">
              <AegisIcon name="shield" :size="13" /> Open SS Portal
            </a>
          </div>
        </div>
      </div>
      <div class="section-title" style="margin-bottom:10px;display:flex;align-items:center;gap:8px;">
        <AegisIcon name="users" :size="16" /> Providers I'm Supporting
      </div>
      <div class="list-group">
        <div v-for="item in (servingAsSsFor ?? [])" :key="item.id" class="list-group-item">
          <div class="avatar avatar-sm avatar-gold">{{ item.provider?.avatar_initials ?? '??' }}</div>
          <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <a v-if="item.provider?.slug"
                 :href="route('public.provider', item.provider.slug)"
                 style="font-size:13px;font-weight:700;color:var(--gold-dark);">
                {{ item.provider?.display_name }}{{ item.provider?.credentials ? ', ' + item.provider.credentials : '' }}
              </a>
              <span v-else style="font-size:13px;font-weight:700;">{{ item.provider?.display_name ?? '—' }}</span>
              <AegisBadge :label="item.role === 'primary' ? 'Primary SS' : 'Alternate SS'" variant="gold" />
              <AegisBadge :label="item.status === 'active' ? 'Active' : 'Pending'" :variant="item.status === 'active' ? 'green' : 'gold'" />
            </div>
            <div style="font-size:12px;color:var(--text-3);margin-top:2px;">
              {{ item.provider?.organization }}{{ item.provider?.location ? ' · ' + item.provider.location : '' }}
              <span v-if="item.review_due_at"> · Review due {{ fmtDate(item.review_due_at) }}</span>
            </div>
          </div>
          <a :href="route('ss.dashboard')" class="btn-icon" data-tooltip="Manage in SS Portal">
            <AegisIcon name="arrow-right" :size="14" />
          </a>
        </div>
      </div>
    </div>

    <!-- ═══ TAB: NOTIFICATIONS ═══ -->
    <div v-show="activeTab === 'notifications'">
      <div class="card">
        <div class="card-header">
          <div class="card-title-group">
            <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark);"><AegisIcon name="bell" :size="16" /></div>
            <div>
              <div class="card-title">SS Activity Notifications</div>
              <div class="card-subtitle">Choose when Aegis should alert you about activity involving your Support Stewards.</div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">Annual Re-Attestation is complete</div>
              <div class="toggle-desc">Get notified when your Support Steward completes their annual re-attestation</div>
            </div>
            <AegisToggle v-model="ssNotifyToggles.re_attestation_complete" />
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">A Support Steward requests changes</div>
              <div class="toggle-desc">Alert when a Support Steward submits a change request</div>
            </div>
            <AegisToggle v-model="ssNotifyToggles.steward_requests_changes" />
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">A Support Steward updates their information</div>
              <div class="toggle-desc">Alert when a Support Steward updates their contact details</div>
            </div>
            <AegisToggle v-model="ssNotifyToggles.steward_updates_info" />
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">Roles or permissions change</div>
              <div class="toggle-desc">Alert when a steward role is modified</div>
            </div>
            <AegisToggle v-model="ssNotifyToggles.roles_permissions_change" />
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">Important Documents are accessed</div>
              <div class="toggle-desc">Alert when a Support Steward views or downloads a document</div>
            </div>
            <AegisToggle v-model="ssNotifyToggles.documents_accessed" />
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">SS added, removed, or updated</div>
              <div class="toggle-desc">Alert when any Support Steward is added, removed, or updated</div>
            </div>
            <AegisToggle v-model="ssNotifyToggles.steward_added_removed" />
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">A critical incident is reported</div>
              <div class="toggle-desc">Alert when a Support Steward reports a critical incident</div>
            </div>
            <AegisToggle v-model="ssNotifyToggles.critical_incident_reported" />
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">A Continuity Response is activated</div>
              <div class="toggle-desc">Alert when your Continuity Response plan is formally activated</div>
            </div>
            <AegisToggle v-model="ssNotifyToggles.continuity_response" />
          </div>
          <div class="btn-group" style="justify-content:flex-end;margin-top:20px;">
            <button type="button" class="btn btn-primary" :disabled="ssNotifySaving" style="display:inline-flex;align-items:center;gap:6px;" @click="saveSsNotifyPrefs">
              <AegisIcon v-if="ssNotifySaving" name="refresh-cw" :size="13" class="btn-spin" />
              <AegisIcon v-else name="check" :size="13" />
              {{ ssNotifySaving ? 'Saving…' : 'Save Preferences' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════ MODALS ═══════════════════ -->

    <!-- ADD SS STEP 1 -->
    <AegisModal :model-value="isOpen('addDsrStep1Modal').value" title="Find Person" size="lg" @update:model-value="v => !v && closeModal('addDsrStep1Modal')">
      <div class="modal-steps">
        <div class="modal-step active"><span class="modal-step-num">1</span>Find Person</div>
        <div class="modal-step-divider"></div>
        <div class="modal-step"><span class="modal-step-num">2</span>Role &amp; Send</div>
      </div>
      <div class="form-group">
        <label class="form-label">Search Aegis Users or Invite by Email</label>
        <input v-model="searchQuery" class="form-input" type="text" placeholder="Search by name, email, or relationship…">
      </div>
      <div class="accordion-item" style="margin-top:6px">
        <div class="accordion-trigger" @click="$event.currentTarget.parentElement.classList.toggle('open')">
          <div style="display:flex;align-items:center;gap:8px"><AegisIcon name="mail" :size="14" /> <span>Invite someone who isn't on Aegis yet</span></div>
          <span class="accordion-caret"><AegisIcon name="chevron-down" :size="14" /></span>
        </div>
        <div class="accordion-content">
          <div class="row-2" style="margin-top:4px">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input v-model="inviteForm.display_name" class="form-input" type="text" placeholder="First Last">
            </div>
            <div class="form-group">
              <label class="form-label">Email <span class="required">*</span></label>
              <input v-model="inviteForm.email" class="form-input" :class="{ 'is-error': fieldError('email') }" type="email" placeholder="email@example.com" @blur="v$.inviteForm.email.$touch()">
              <div v-if="fieldError('email')" class="form-error">{{ fieldError('email') }}</div>
            </div>
          </div>
        </div>
      </div>
      <div class="alert alert-info" style="margin-top:4px">
        <div class="alert-icon"><AegisIcon name="info" :size="16" /></div>
        <div><strong>Invitation-only:</strong> Support Stewards cannot self-sign-up — they must be invited by you.</div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('addDsrStep1Modal')">Cancel</button>
        <button class="btn btn-primary" @click="closeModal('addDsrStep1Modal'); openModal('addDsrStep2Modal')">Next: Set Role &amp; Permissions &rarr;</button>
      </template>
    </AegisModal>

    <!-- ADD SS STEP 2 -->
    <AegisModal :model-value="isOpen('addDsrStep2Modal').value" title="Set Role &amp; Permissions" size="lg" @update:model-value="v => !v && closeModal('addDsrStep2Modal')">
      <div class="modal-steps">
        <div class="modal-step done"><span class="modal-step-num"><AegisIcon name="check" :size="10" /></span>Find Person</div>
        <div class="modal-step-divider"></div>
        <div class="modal-step active"><span class="modal-step-num">2</span>Role &amp; Send</div>
      </div>
      <div class="form-group">
        <label class="form-label">Support Steward Role <span class="required">*</span></label>
        <div class="role-option" :class="{ selected: inviteForm.role === 'primary' }" @click="inviteForm.role = 'primary'">
          <input type="radio" name="dsrRole" value="primary" :checked="inviteForm.role === 'primary'" style="accent-color:var(--gold-dark)">
          <div>
            <div style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:var(--text)"><AegisIcon name="user" :size="14" /> Support Steward</div>
            <div style="font-size:12px;color:var(--text-3);margin-top:3px">A trusted individual who supports communication, coordination, and key tasks during a critical moment, guided by your Continuity Plan.</div>
          </div>
        </div>
        <div class="role-option" :class="{ selected: inviteForm.role === 'alternate' }" @click="inviteForm.role = 'alternate'">
          <input type="radio" name="dsrRole" value="alternate" :checked="inviteForm.role === 'alternate'" style="accent-color:var(--gold-dark)">
          <div>
            <div style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:var(--text)"><AegisIcon name="user" :size="14" /> Alternative Support Steward</div>
            <div style="font-size:12px;color:var(--text-3);margin-top:3px">Steps in if your primary Support Steward is unavailable, so coordination continues without interruption.</div>
          </div>
        </div>
      </div>
      <div class="alert alert-info" style="margin-top:8px;">
        <AegisIcon name="info" :size="16" />
        <div>This Support Steward will be authorized to verify critical incidents and trigger your Continuity Plan. Specific task lists are defined in your <a :href="route('provider.plan.index')">Continuity Plan</a>.</div>
      </div>

      <template #footer>
        <button class="btn btn-outline" @click="closeModal('addDsrStep2Modal'); openModal('addDsrStep1Modal')">← Back</button>
        <button class="btn btn-primary" :class="{ 'btn-spin': inviteForm.processing }" :disabled="inviteForm.processing" @click="submitInvite">
          <AegisIcon name="check" :size="13" /> {{ inviteForm.processing ? 'Sending…' : 'Send Invitation' }}
        </button>
      </template>
    </AegisModal>



    <!-- EDIT SS -->
    <AegisModal :model-value="isOpen('editDsrModal').value" :title="activeSteward ? 'Edit Support Steward — ' + fullName(activeSteward) : 'Edit Support Steward'" size="md" @update:model-value="v => !v && closeModal('editDsrModal')">
      <div class="alert alert-info" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="info" :size="16" /></div>
        <div>If you update this Support Steward's details, they'll be notified so your shared records stay in sync.</div>
      </div>
      <div class="row-2">
        <div class="form-group"><label class="form-label">Full Name</label><input v-model="editForm.display_name" class="form-input" type="text"></div>
        <div class="form-group"><label class="form-label">Relationship</label><input v-model="editForm.relationship" class="form-input" type="text" placeholder="e.g., Office Manager, Colleague"></div>
      </div>
      <div class="row-2">
        <div class="form-group"><label class="form-label">Phone</label><input v-model="editForm.phone" class="form-input" type="tel"></div>
        <div class="form-group"><label class="form-label">Email</label><input v-model="editForm.email" class="form-input" type="email"></div>
      </div>
      <div class="form-group">
        <label class="form-label">Role</label>
        <select v-model="editForm.role" class="form-select">
          <option value="primary">Support Steward</option>
          <option value="alternate">Alternative Support Steward</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Notes</label><textarea v-model="editForm.notes" class="form-input" style="min-height:60px"></textarea></div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('editDsrModal')">Cancel</button>
        <button class="btn btn-primary" @click="toast.success('Details updated.'); closeModal('editDsrModal')">Save Changes</button>
      </template>
    </AegisModal>



    <!-- VIEW AGREEMENT -->
    <AegisModal :model-value="isOpen('viewDsrAgreementModal').value" :title="activeSteward ? 'Support Steward Agreement — ' + fullName(activeSteward) : 'Support Steward Agreement'" size="lg" @update:model-value="v => !v && closeModal('viewDsrAgreementModal')">
      <div v-if="activeSteward?.signed_at" class="alert alert-success" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="check" :size="16" /></div>
        <div>Active · Both parties signed · Last reviewed {{ fmtDate(activeSteward.signed_at) }}</div>
      </div>
      <div v-else class="alert alert-warning" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="clock" :size="16" /></div>
        <div>Awaiting counter-signature from Support Steward.</div>
      </div>
      <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius);padding:20px;font-size:13px;line-height:1.8;color:var(--text-2)">
        <div style="font-family:var(--font-serif);font-size:16px;font-weight:700;color:var(--gold-dark);text-align:center;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border)">Aegis SUPPORT STEWARD AGREEMENT</div>
        <p><strong>Support Steward:</strong> {{ activeSteward ? fullName(activeSteward) : '—' }}</p>
        <p><strong>Agreement Date:</strong> {{ activeSteward ? fmtDate(activeSteward.signed_at ?? activeSteward.invited_at) : '—' }} | <strong>Annual Attestation:</strong> Required</p>
        <br>
        <p><strong>Section 1. Purpose.</strong> This agreement authorizes the Support Steward to support the Practitioner during a critical moment, carrying out the responsibilities designated by the Provider and guided by the Continuity Plan.</p>
        <br>
        <p><strong>Section 2. Authorized Responsibilities.</strong> As designated across the five sections — Activation &amp; Verification, Access &amp; Resource Coordination, Oversight &amp; Coordination, Financial Responsibilities, and Completion &amp; Transition.</p>
        <br>
        <p><strong>Section 3. Compliance.</strong> Support Steward agrees to comply with HIPAA, maintain full confidentiality, and not exceed authorized responsibilities. All actions are logged for audit.</p>
        <br>
        <p><strong>Section 4. Annual Attestation.</strong> The Provider re-confirms the Support Steward's responsibilities and contact information annually.</p>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="toast.success('Agreement downloaded')"><AegisIcon name="download" :size="14" /> Download PDF</button>
        <button class="btn btn-primary" @click="closeModal('viewDsrAgreementModal')">Close</button>
      </template>
    </AegisModal>

    <!-- MANAGE ACCESS -->
    <AegisModal :model-value="isOpen('manageAccessModal').value" :title="activeSteward ? 'Manage Access — ' + fullName(activeSteward) : 'Manage Access'" size="md" @update:model-value="v => !v && closeModal('manageAccessModal')">
      <div style="display:flex;flex-direction:column;gap:14px;padding-top:4px;">
        <div class="form-group">
          <label class="form-label">Action <span class="required">*</span></label>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label v-for="opt in [{ value: 'suspend', label: 'Suspend', desc: 'Block access temporarily — agreement stays in place, reinstate at any time.' }, { value: 'reinstate', label: 'Reinstate', desc: 'Restore all previously authorized permissions and resume paused task delegations.' }, { value: 'archive', label: 'Archive', desc: 'Remove this steward record from active view. They remain in the system for audit purposes.' }]" :key="opt.value"
              style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;transition:border-color .15s,background .15s;"
              :style="manageAccessForm.action === opt.value ? 'border-color:var(--gold-dark);background:rgba(160,129,62,.04);' : ''"
            >
              <input type="radio" :value="opt.value" v-model="manageAccessForm.action" style="margin-top:3px;" />
              <div>
                <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:2px;">{{ opt.label }}</div>
                <div style="font-size:12px;color:var(--text-4);">{{ opt.desc }}</div>
              </div>
            </label>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Reason <span class="required">*</span></label>
          <textarea v-model="manageAccessForm.reason" class="form-input" rows="2" placeholder="Briefly explain this action…" />
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('manageAccessModal')">Cancel</button>
        <button class="btn btn-primary" :disabled="!manageAccessForm.action || suspendForm.processing" @click="submitManageAccess">
          <AegisIcon name="check" :size="13" /> Apply
        </button>
      </template>
    </AegisModal>


    <!-- REMOVE -->
    <AegisModal :model-value="isOpen('removeDsrModal').value" title="Remove Support Steward" size="md" @update:model-value="v => !v && closeModal('removeDsrModal')">
      <div class="alert alert-danger" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="16" /></div>
        <div><strong>Warning:</strong> Removing a Support Steward permanently voids the agreement and revokes all access. This cannot be undone.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Reason for Removal <span class="required">*</span></label>
        <select v-model="removeForm.reason" class="form-select" :class="{ 'is-error': fieldError('removeReason') }" @blur="v$.removeForm.reason.$touch()">
          <option value="">— Select Reason —</option>
          <option>Staff member left the organization</option>
          <option>Role no longer needed</option>
          <option>Access violation or policy breach</option>
          <option>Replacing with a different Support Steward</option>
          <option>Practice restructuring</option>
          <option>Support Steward requested removal</option>
          <option>Other</option>
        </select>
        <div v-if="fieldError('removeReason')" class="form-error">{{ fieldError('removeReason') }}</div>
      </div>
      <div class="form-group"><label class="form-label">Additional Notes</label><textarea v-model="removeForm.notes" class="form-input" style="min-height:60px" placeholder="Optional context about this removal…"></textarea></div>
      <div class="form-group">
        <label class="form-label">Confirm by typing &ldquo;REMOVE&rdquo;</label>
        <input v-model="removeConfirm" class="form-input" type="text" placeholder="Type REMOVE to confirm" style="border-color:var(--red-dark)">
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('removeDsrModal')">Cancel</button>
        <button class="btn btn-danger" :class="{ 'btn-spin': removeForm.processing }" :disabled="removeConfirm !== 'REMOVE' || removeForm.processing" @click="submitRemove">
          {{ removeForm.processing ? 'Removing…' : 'Remove Support Steward' }}
        </button>
      </template>
    </AegisModal>

    <!-- RESEND INVITE -->
    <AegisModal :model-value="isOpen('resendInviteModal').value" title="Resend Support Steward Invitation" size="md" @update:model-value="v => !v && closeModal('resendInviteModal')">
      <div class="form-group">
        <label class="form-label">New Expiration</label>
        <select v-model="resendForm.expires_days" class="form-select">
          <option value="30">30 days from today</option>
          <option value="14">14 days from today</option>
          <option value="7">7 days from today</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Follow-up Message (Optional)</label><textarea v-model="resendForm.message" class="form-input" style="min-height:70px" placeholder="Just following up on the Support Steward invitation…"></textarea></div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('resendInviteModal')">Cancel</button>
        <button class="btn btn-primary" :class="{ 'btn-spin': resendForm.processing }" :disabled="resendForm.processing" @click="submitResend">
          {{ resendForm.processing ? 'Sending…' : 'Resend Invitation' }}
        </button>
      </template>
    </AegisModal>

    <!-- CANCEL INVITE -->
    <AegisModal :model-value="isOpen('cancelInviteModal').value" title="Cancel Invitation" size="sm" @update:model-value="v => !v && closeModal('cancelInviteModal')">
      <p style="font-size:13px;color:var(--text-2)">This will cancel the pending invitation. The Support Steward will not be able to accept it.</p>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('cancelInviteModal')">Keep Invitation</button>
        <button class="btn btn-danger" @click="submitCancelInvite">Cancel Invitation</button>
      </template>
    </AegisModal>





    <!-- REINSTATE -->
    <AegisModal :model-value="isOpen('reinstateModal').value" title="Reinstate Support Steward" size="sm" @update:model-value="v => !v && closeModal('reinstateModal')">
      <p>Reinstate <strong>{{ activeSteward ? fullName(activeSteward) : '' }}</strong> as your Support Steward?</p>
      <p style="font-size:12px;color:var(--text-3);margin-top:8px;">Their access and responsibilities will be restored immediately. They will be notified by email.</p>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('reinstateModal')">Cancel</button>
        <button class="btn btn-primary" :disabled="reinstateForm.processing" @click="submitReinstate">
          <AegisIcon name="check" :size="13" /> Reinstate Steward
        </button>
      </template>
    </AegisModal>

    <!-- WHAT IS A SUPPORT STEWARD -->
    <AegisModal :model-value="isOpen('dsrGuideModal').value" title="What is a Support Steward?" size="lg" @update:model-value="v => !v && closeModal('dsrGuideModal')">
      <div style="font-size:13px;color:var(--text-2);line-height:1.7">
        <p>A Support Steward is a trusted individual you designate to support communication, coordination, and key tasks during a critical moment — guided by your Continuity Plan.</p>
        <p style="margin-top:12px">Unlike a Continuity Steward (who has the authority to make clinical and administrative decisions), a Support Steward operates within a defined scope of responsibilities that you set. They cannot act beyond what you've authorized.</p>
        <p style="margin-top:12px"><strong>Common Support Steward responsibilities include:</strong></p>
        <ul style="margin-top:8px;padding-left:20px;display:flex;flex-direction:column;gap:6px">
          <li>Confirming your status and reporting a critical incident</li>
          <li>Coordinating access to the Document Vault</li>
          <li>Maintaining communication with clients and contacts</li>
          <li>Tracking practice expenses and identifying outstanding invoices</li>
          <li>Handing off remaining items once the transition ends</li>
        </ul>
      </div>
      <template #footer>
        <button class="btn btn-primary" @click="closeModal('dsrGuideModal')">Got it</button>
      </template>
    </AegisModal>

    <!-- UPGRADE -->
    <AegisModal :model-value="isOpen('upgradeModal').value" title="Upgrade Your Plan" size="md" @update:model-value="v => !v && closeModal('upgradeModal')">
      <div style="font-size:13px;color:var(--text-2)">
        <p>Your Continuity Access plan supports up to {{ ssMax }} Support Steward{{ ssMax === 1 ? '' : 's' }}.</p>
        <p style="margin-top:12px">Upgrade to <strong>Continuity Practice</strong> to add more Support Stewards and unlock the full suite of practice continuity features.</p>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('upgradeModal')">Maybe Later</button>
        <a :href="route('provider.settings.billing.portal')" class="btn btn-primary">View Upgrade Options</a>
      </template>
    </AegisModal>

  </AppLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useVuelidate } from '@vuelidate/core'
import { required, email as emailRule, helpers } from '@vuelidate/validators'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisToggle from '@/components/ui/AegisToggle.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import PlanReviewAlert from '@/components/PlanReviewAlert.vue'

// ── Props ────────────────────────────────────────────────
const props = defineProps({
  stewards:           { type: Array,   default: () => [] },
  suspended:          { type: Array,   default: () => [] },
  pending:            { type: Array,   default: () => [] },
  invited:            { type: Array,   default: () => [] },
  declined:           { type: Array,   default: () => [] },
  archived:           { type: Array,   default: () => [] },
  servingAsSsFor:     { type: Array,   default: () => [] },
  tier:               { type: String,  default: 'access' },
  ssMax:              { type: Number,  default: 2 },
  ssCount:            { type: Number,  default: 0 },
  planStatus:         { type: String,  default: null },
  annualReviewDate:   { type: String,  default: null },
  hasDraftInProgress: { type: Boolean, default: false },
  draftPlanVersion:   { type: Number,  default: null },
  notifyPrefs:        { type: Object,  default: () => ({}) },
})

// ── Composables ──────────────────────────────────────────
const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()
const { confirmAction } = useConfirm()

// ── Tab ──────────────────────────────────────────────────
const activeTab = ref('mydsr')

// ── Active record ─────────────────────────────────────────
const activeStewardId = ref(null)
const activeSteward = computed(() =>
  [...props.stewards, ...props.suspended, ...props.pending, ...props.invited, ...props.declined, ...props.archived]
    .find(s => s.id === activeStewardId.value) ?? null
)

// ── Computed ──────────────────────────────────────────────
const activeCount  = computed(() => props.stewards.length)
const pendingCount = computed(() => props.pending.length + props.invited.length)
const rosterCount  = computed(() => props.stewards.length)
const atLimit      = computed(() => props.ssCount >= props.ssMax)

const nextReviewLabel = computed(() => {
  const dates = props.stewards.filter(s => s.review_due_at).map(s => s.review_due_at).sort()
  if (!dates.length) return '—'
  return fmtDate(dates[0])
})

// ── SS Notification Preferences ──────────────────────────
const ssNotifyToggles = ref({
  re_attestation_complete:    props.notifyPrefs?.re_attestation_complete    ?? true,
  steward_requests_changes:   props.notifyPrefs?.steward_requests_changes   ?? true,
  steward_updates_info:       props.notifyPrefs?.steward_updates_info       ?? true,
  roles_permissions_change:   props.notifyPrefs?.roles_permissions_change   ?? true,
  documents_accessed:         props.notifyPrefs?.documents_accessed         ?? true,
  steward_added_removed:      props.notifyPrefs?.steward_added_removed      ?? true,
  critical_incident_reported: props.notifyPrefs?.critical_incident_reported ?? true,
  continuity_response:        props.notifyPrefs?.continuity_response        ?? true,
})
const ssNotifySaving = ref(false)
function saveSsNotifyPrefs() {
  ssNotifySaving.value = true
  router.put(route('provider.settings.notifications'), { ss_notify: ssNotifyToggles.value }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Notification preferences saved.'),
    onError:   () => toast.error('Could not save preferences.'),
    onFinish:  () => { ssNotifySaving.value = false },
  })
}

// ── Helpers ───────────────────────────────────────────────
function fullName(s) {
  const name = s?.steward?.display_name ?? s?.display_name ?? '—'
  const cred = s?.steward?.credentials ?? s?.credentials ?? ''
  return cred && !name.includes(cred) ? `${name}, ${cred}` : name
}
function initials(s) {
  return s?.steward?.avatar_initials ?? s?.avatar_initials
    ?? (s?.steward?.display_name ?? s?.display_name ?? '??').slice(0, 2).toUpperCase()
}
function subLine(s) {
  return [s?.steward?.title, s?.steward?.organization, s?.steward?.location].filter(Boolean).join(' · ')
}
function fmtDate(v) {
  if (!v) return ''
  try { return new Date(v).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }
  catch { return '' }
}
function daysSince(v) {
  if (!v) return ''
  const diff = Math.floor((Date.now() - new Date(v).getTime()) / 86400000)
  return diff === 0 ? 'today' : `${diff} day${diff === 1 ? '' : 's'} ago`
}
function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '' }
function roleVal(r) { return typeof r === 'object' ? r?.value ?? '' : r ?? '' }





// ── Tier gate ─────────────────────────────────────────────
function handleAddSS() {
  if (!atLimit.value) {
    openModal('addDsrStep1Modal')
  } else if (props.tier === 'access') {
    openModal('upgradeModal')
  } else {
    toast.info(`You've reached the maximum of ${props.ssMax} Support Stewards on the Continuity Practice plan. Contact support to discuss enterprise options.`)
  }
}

// ── Action openers ────────────────────────────────────────
function openAgreement(s) { activeStewardId.value = s?.id; openModal('viewDsrAgreementModal') }
function openEdit(s) {
  activeStewardId.value = s?.id
  editForm.display_name = fullName(s)
  editForm.phone = s?.steward?.phone ?? ''
  editForm.email = s?.steward?.email ?? ''
  editForm.role = roleVal(s?.role) || 'primary'
  editForm.relationship = s?.steward?.relationship ?? ''
  editForm.notes = s?.notes ?? ''
  openModal('editDsrModal')
}
function openSuspend(s)     { activeStewardId.value = s.id; openModal('suspendDsrModal') }
function openReinstate(s)   { activeStewardId.value = s.id; openModal('reinstateModal') }
function openManageAccess(s) {
  activeStewardId.value = s.id
  manageAccessForm.action = s.status === 'archived' ? 'reinstate' : 'suspend'
  manageAccessForm.reason = ''
  openModal('manageAccessModal')
}
function submitManageAccess() {
  if (!manageAccessForm.action) return
  const routeMap = { suspend: 'provider.ss.suspend', reinstate: 'provider.ss.reinstate', archive: 'provider.ss.archive' }
  const r = routeMap[manageAccessForm.action]
  const method = manageAccessForm.action === 'archive' ? 'post' : 'post'
  router.post(route(r, { steward: activeStewardId.value }), { reason: manageAccessForm.reason }, {
    preserveScroll: true,
    onSuccess: () => {
      const msgs = { suspend: 'Access suspended.', reinstate: 'Steward reinstated.', archive: 'Record archived.' }
      toast.success(msgs[manageAccessForm.action] ?? 'Done.')
      closeModal('manageAccessModal')
    },
    onError: () => toast.error('Action failed.'),
  })
}
function openRemove(s)      { activeStewardId.value = s.id; removeConfirm.value = ''; openModal('removeDsrModal') }
function openResend(s)      { activeStewardId.value = s.id; openModal('resendInviteModal') }
function openCancelInvite(s){ activeStewardId.value = s.id; openModal('cancelInviteModal') }








// ── Forms (all at top-level of setup) ────────────────────
const inviteForm = useForm({
  user_id: null, email: '', display_name: '', role: 'primary',
  expires_days: '30', message: '',
})
const editForm = useForm({
  display_name: '', relationship: '', phone: '', email: '', role: 'primary', notes: '',
})
const suspendForm   = useForm({ reason: '', start_date: '', end_date: '', message: '' })
const reinstateForm = useForm({ message: '' })
const manageAccessForm = reactive({ action: '', reason: '' })
const removeForm    = useForm({ reason: '', notes: '' })
const resendForm    = useForm({ expires_days: '30', message: '' })


const removeConfirm  = ref('')
const searchQuery    = ref('')

// ── Vuelidate ─────────────────────────────────────────────
const rules = {
  inviteForm: { email: { email: helpers.withMessage('A valid email is required.', emailRule) } },
  suspendForm: { reason: { required: helpers.withMessage('Please select a reason.', required) } },
  removeForm:  { reason: { required: helpers.withMessage('Please select a reason.', required) } },
}
const v$ = useVuelidate(rules, { inviteForm, suspendForm, removeForm })

function fieldError(key) {
  const map = {
    email:        v$.value.inviteForm?.email,
    reason:       v$.value.suspendForm?.reason,
    removeReason: v$.value.removeForm?.reason,
  }
  const node = map[key]
  return node?.$dirty && node?.$errors?.[0]?.$message ? node.$errors[0].$message : null
}

// ── Submit actions ────────────────────────────────────────
async function submitInvite() {
  const ok = await v$.value.$validate()
  if (!ok) return
  inviteForm.post(route('provider.ss.invite'), {
    preserveScroll: true,
    onSuccess: () => { toast.success('Support Steward invited successfully.'); closeModal('addDsrStep2Modal'); inviteForm.reset() },
  })
}

function submitArchiveSteward(s) {
  router.post(route('provider.ss.archive', { steward: s.id }), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Record archived.'),
  })
}

async function submitSuspend() {
  v$.value.suspendForm.$touch()
  const ok = await v$.value.suspendForm.$validate()
  if (!ok) return
  suspendForm.post(route('provider.ss.suspend', { steward: activeStewardId.value }), {
    preserveScroll: true,
    onSuccess: () => { toast.warning('Support Steward access suspended.'); closeModal('suspendDsrModal'); suspendForm.reset() },
  })
}

function submitReinstate() {
  reinstateForm.post(route('ss.reinstate', { steward: activeStewardId.value }), {
    preserveScroll: true,
    onSuccess: () => { toast.success('Support Steward reinstated.'); closeModal('reinstateModal'); reinstateForm.reset() },
    onError: () => toast.error('Could not reinstate steward.'),
  })
}

async function submitRemove() {
  v$.value.removeForm.$touch()
  const ok = await v$.value.removeForm.$validate()
  if (!ok) return
  const reason = removeForm.reason + (removeForm.notes ? ' — ' + removeForm.notes : '')
  router.delete(route('provider.ss.remove', { steward: activeStewardId.value }), {
    data: { reason },
    preserveScroll: true,
    onSuccess: () => { toast.warning('Support Steward removed.'); closeModal('removeDsrModal'); removeForm.reset(); removeConfirm.value = '' },
  })
}

function submitResend() {
  resendForm.post(route('provider.ss.resend', { steward: activeStewardId.value }), {
    preserveScroll: true,
    onSuccess: () => { toast.success('Invitation resent.'); closeModal('resendInviteModal'); resendForm.reset() },
  })
}

function submitCancelInvite() {
  router.post(route('provider.ss.archive', { steward: activeStewardId.value }), {}, {
    preserveScroll: true,
    onSuccess: () => { toast.warning('Invitation cancelled.'); closeModal('cancelInviteModal') },
  })
}


</script>

<style scoped>
/* ── DSR CARD ── */
.dsr-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:22px 22px 22px 26px; display:flex; align-items:flex-start; gap:18px; margin-bottom:14px; transition:box-shadow var(--transition),transform var(--transition); position:relative; overflow:hidden; }
.dsr-card::before { content:''; position:absolute; left:0; top:0; bottom:0; width:4px; }
.dsr-card.active::before  { background:var(--gold-dark); }
.dsr-card.pending::before { background:var(--border-dark); }
.dsr-card.suspended::before { background:var(--red-dark); }
.dsr-card.declined::before  { background:var(--red-dark); }
.dsr-card.declined { background:var(--red-light); }
.dsr-card.archived::before { background:var(--text-4); }
.dsr-card.archived { opacity:0.85; }
.dsr-card:hover { box-shadow:var(--shadow); transform:translateY(-1px); }
.dsr-name { font-family:var(--font-serif); font-size:18px; font-weight:700; color:var(--text); }
.dsr-sub  { font-size:12px; color:var(--text-3); margin-top:2px; }
.dsr-meta { display:flex; gap:14px; flex-wrap:wrap; margin-top:10px; font-size:12px; color:var(--text-3); }
.dsr-meta span { display:flex; align-items:center; gap:4px; }

/* ── RESP LINE ── */
.dsr-resp-line { display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-2);padding:8px 0;border-top:1px solid var(--border);margin-top:10px; }

/* ── TOGGLE ROW ── */
.toggle-row { display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 0;border-bottom:1px solid var(--border); }
.toggle-row:last-of-type { border-bottom:none; }
.toggle-info { flex:1;min-width:0; }
.toggle-label { font-size:13px;font-weight:600;color:var(--text);margin-bottom:2px; }
.toggle-desc  { font-size:12px;color:var(--text-3); }

/* ── PERM LIST ── */
.perm-list { background:var(--surface-2); border-radius:var(--radius); padding:12px 16px; margin-top:14px; }
.perm-list-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; color:var(--text-4); margin-bottom:8px; }
.perm-item { display:flex; align-items:center; gap:10px; padding:6px 0; font-size:13px; color:var(--text-2); border-bottom:1px solid var(--border); }
.perm-item:last-child { border-bottom:none; }
.pi-icon { display:flex; align-items:center; flex-shrink:0; }
.pi-level { margin-left:auto; font-size:10px; font-weight:700; padding:2px 8px; border-radius:var(--radius-xs); text-transform:uppercase; }
.level-full    { background:var(--green-light);   color:var(--green-dark); }
.level-limited { background:var(--badge-bg-gold); color:var(--gold-dark); }
.level-none    { background:var(--surface-3);     color:var(--text-3); }

/* ── PERM OVERVIEW ── */
.perm-row + .perm-row { padding-top:14px; margin-top:14px; border-top:1px solid var(--border); }
.perm-row-head { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
.perm-row-info { flex:1; min-width:0; }
.perm-row-id-line { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.perm-row-name { font-size:13px; font-weight:700; color:var(--text); }
.perm-row-role { font-size:11px; color:var(--text-3); margin-top:2px; }
.perm-row-badges { display:flex; flex-wrap:wrap; gap:6px; }
.perm-row.is-muted .perm-row-badges { opacity:0.55; }

/* ── PERM MATRIX ── */
.perm-matrix { width:100%; border-collapse:collapse; font-size:12px; }
.perm-matrix th { background:var(--surface-2); padding:9px 12px; text-align:left; font-weight:700; font-size:10px; text-transform:uppercase; letter-spacing:0.4px; color:var(--text-4); border-bottom:1px solid var(--border); }
.perm-matrix td { padding:9px 12px; border-bottom:1px solid var(--border); vertical-align:middle; }
.perm-matrix tr:last-child td { border-bottom:none; }
.perm-matrix tr:hover td { background:var(--surface-2); }
.perm-matrix .task-name { font-size:13px; font-weight:600; color:var(--text); }

/* ── ROLE OPTION ── */
.role-option { display:flex; align-items:flex-start; gap:12px; padding:14px; border:1px solid var(--border); border-radius:var(--radius); cursor:pointer; transition:border-color var(--transition),background var(--transition); margin-bottom:10px; background:var(--surface); }
.role-option:hover { border-color:var(--soft-gold); background:var(--badge-bg-gold); }
.role-option.selected { border-color:transparent; background:var(--badge-bg-gold); box-shadow:0 0 0 1px var(--gold-dark); }

/* ── SIG BLOCK ── */
.sig-block { border:2px dashed var(--border); border-radius:var(--radius); padding:20px; text-align:center; color:var(--text-3); font-size:13px; cursor:pointer; transition:border-color var(--transition),background var(--transition),color var(--transition); margin-top:12px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px; }
.sig-block:hover { border-color:var(--gold-dark); color:var(--gold-dark); background:var(--badge-bg-gold); }
.sig-block.signed { border-style:solid; border-color:var(--green-dark); background:var(--green-light); color:var(--green-dark); }

/* ── MODAL STEPS ── */
.modal-steps { display:flex; align-items:center; margin-bottom:20px; }
.modal-step { display:flex; align-items:center; gap:8px; font-size:12px; font-weight:600; color:var(--text-3); white-space:nowrap; }
.modal-step.active { color:var(--gold-dark); }
.modal-step.done   { color:var(--green-dark); }
.modal-step-num { width:22px; height:22px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0; }
.modal-step.active .modal-step-num { background:var(--gold-dark); color:var(--text-inverted); }
.modal-step.done .modal-step-num   { background:var(--green-dark); color:var(--text-inverted); }
.modal-step-divider { flex:1; height:1px; background:var(--border); margin:0 8px; }
</style>