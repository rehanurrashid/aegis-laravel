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
        <!-- Browse SS Directory removed per Chapman decision #4 -->
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
        :value="annualReviewDate ? fmtDate(annualReviewDate) : '—'"
        :label="planStatus === 'annual_review_due' ? 'Review OVERDUE' : 'Annual Attestation Due'"
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
      <button class="tab-pill" :class="{ active: activeTab === 'suspended' }" @click="activeTab = 'suspended'">
        Suspended <span class="badge-pill">{{ suspended.length }}</span>
      </button>
      <button class="tab-pill" :class="{ active: activeTab === 'iamdsr' }" @click="activeTab = 'iamdsr'">
        I'm SS For <span class="badge-pill">{{ servingAsSsFor?.length ?? 0 }}</span>
      </button>
      <button class="tab-pill" :class="{ active: activeTab === 'notifications' }" @click="activeTab = 'notifications'">
        Notifications
      </button>
    </div>

    <!-- ═══ TAB: MY SUPPORT STEWARDS ═══ -->
    <div v-show="activeTab === 'mydsr'">
      <p style="font-size:13px;font-weight:600;color:var(--text-2);margin:0 0 16px">Staff authorized to act on your behalf within defined permission boundaries.</p>

      <template v-if="stewards.length">
        <!-- ACTIVE — exec-card pattern mirrors CS page -->
        <div
          v-for="s in stewards"
          :key="s.id"
          class="card exec-card"
          :class="s.role === 'support' ? 'primary' : 'secondary'"
          style="display:flex;align-items:flex-start;gap:18px;padding:22px;margin-bottom:14px;position:relative;overflow:hidden;"
        >
          <span style="position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--gold-dark);"></span>
          <!-- Avatar -->
          <div style="width:58px;height:58px;border-radius:var(--radius);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            {{ initials(s) }}
          </div>
          <!-- Content -->
          <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:4px;">
              <a
                v-if="s.steward?.slug"
                :href="route('public.ss', s.steward.slug)"
                style="font-family:var(--font-serif);font-size:17px;font-weight:700;color:var(--gold-dark);text-decoration:none;"
              >{{ fullName(s) }}</a>
              <span v-else style="font-family:var(--font-serif);font-size:17px;font-weight:700;color:var(--gold-dark);">{{ fullName(s) }}</span>
              <span class="badge badge-gold"><AegisIcon name="shield" :size="10" style="margin-right:3px;" />{{ s.role === 'alternate' ? 'Alternate SS' : 'Primary SS' }}</span>
              <span class="badge badge-green"><span class="status-dot green"></span> Active</span>
              
            </div>
            <div style="font-size:12px;color:var(--text-3);margin-top:2px;">{{ subLine(s) }}</div>
            <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:10px;font-size:12px;color:var(--text-3);">
              <span v-if="s.steward?.phone" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="phone" :size="13" />{{ s.steward.phone }}</span>
              <span v-if="s.steward?.email" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="mail" :size="13" />{{ s.steward.email }}</span>
        <span v-if="s.signed_at || s.ss_acknowledged_at" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="edit" :size="13" />Signed {{ fmtDate(s.signed_at ?? s.ss_acknowledged_at) }}</span>
              <span v-else style="display:flex;align-items:center;gap:5px;"><AegisIcon name="message-square" :size="13" />Via Aegis Messaging</span>
              <span v-if="s.review_due_at" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="calendar" :size="13" />Review Due: {{ fmtDate(s.review_due_at) }}</span>
            </div>

            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">
              <button type="button" class="btn-icon" data-tooltip="View Agreement" @click="openAgreement(s)"><AegisIcon name="file-text" :size="14" /></button>
              <button type="button" class="btn-icon" data-tooltip="Edit Details" @click="openEdit(s)"><AegisIcon name="pencil" :size="14" /></button>
              <button type="button" class="btn-icon" data-tooltip="Message steward" @click="msg(s)"><AegisIcon name="message-square" :size="14" /></button>
              <button type="button" class="btn-icon" data-tooltip="Remove Support Steward" @click="openRemove(s)">
                <AegisIcon name="x" :size="14" style="color:var(--red-dark);" />
              </button>
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

      <!-- PENDING / INVITED — exec-card pending pattern -->
      <div
        v-for="s in [...pending, ...invited]"
        :key="s.id"
        class="card exec-card pending"
        style="display:flex;align-items:flex-start;gap:18px;padding:20px 22px;margin-bottom:8px;position:relative;overflow:hidden;"
      >
        <span style="position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--orange-dark);"></span>
        <div style="width:48px;height:48px;border-radius:var(--radius);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:17px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          {{ initials(s) }}
        </div>
        <div style="flex:1;min-width:0;">
          <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:4px;">
            <span style="font-family:var(--font-serif);font-size:15px;font-weight:700;color:var(--gold-dark);">{{ fullName(s) }}</span>
            <span class="badge badge-orange"><AegisIcon name="clock" :size="12" /> Pending Response</span>
            <span v-if="s.role" class="badge badge-gold"><AegisIcon name="shield" :size="10" /> {{ s.role === 'alternate' ? 'Alternate SS' : 'Primary SS' }}</span>
          </div>
          <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:12px;color:var(--text-3);">
            <span v-if="s.steward?.email || s.email" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="mail" :size="13" />{{ s.steward?.email ?? s.email }}</span>
              <span v-if="s.signed_at || s.ss_acknowledged_at" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="edit" :size="13" />Signed {{ fmtDate(s.signed_at ?? s.ss_acknowledged_at) }}</span>
            <span v-if="s.invited_at" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="calendar" :size="13" />Invited: {{ fmtDate(s.invited_at) }}</span>
            <span v-if="s.expires_at" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="clock" :size="13" />Expires: {{ fmtDate(s.expires_at) }}</span>
          </div>
          <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
            <button type="button" class="btn-icon" data-tooltip="Resend Invitation" @click="openResend(s)"><AegisIcon name="send" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Preview Agreement" @click="openAgreement(s)"><AegisIcon name="file-text" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Cancel Invitation" @click="openCancelInvite(s)"><AegisIcon name="x" :size="14" /></button>
          </div>
        </div>
      </div>

      <!-- DECLINED -->
      <div
        v-for="s in declined"
        :key="s.id"
        class="card"
        style="display:flex;align-items:flex-start;gap:18px;padding:16px 20px;margin-bottom:8px;position:relative;overflow:hidden;border-left:4px solid var(--red-dark);background:var(--red-light);"
      >
        <div style="width:42px;height:42px;border-radius:var(--radius);background:var(--text-4);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:15px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          {{ initials(s) }}
        </div>
        <div style="flex:1;min-width:0;">
          <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:4px;">
            <span style="font-family:var(--font-serif);font-size:15px;font-weight:700;color:var(--text);">{{ fullName(s) }}</span>
            <span class="badge badge-red"><AegisIcon name="x" :size="12" /> Declined</span>
          </div>
          <div v-if="s.declined_reason" style="display:flex;align-items:center;gap:6px;margin-top:6px;font-size:12px;color:var(--red-dark);">
            <AegisIcon name="alert-circle" :size="13" />
            <span>{{ reasonLabel(s.declined_reason) }}</span>
          </div>
          <div style="margin-top:10px;display:flex;gap:8px;">
            <button type="button" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;" @click="handleAddSS">
              <AegisIcon name="plus" :size="13" /> Invite Someone Else
            </button>
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
      <div
        v-for="s in suspended"
        :key="s.id"
        class="card exec-card suspended"
        style="display:flex;align-items:flex-start;gap:18px;padding:20px 22px;margin-bottom:8px;position:relative;overflow:hidden;"
      >
        <span style="position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--red-dark);"></span>
        <div style="width:48px;height:48px;border-radius:var(--radius);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:17px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          {{ initials(s) }}
        </div>
        <div style="flex:1;min-width:0;">
          <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:4px;">
            <a
                v-if="s.steward?.slug"
                :href="route('public.ss', s.steward.slug)"
                style="font-family:var(--font-serif);font-size:15px;font-weight:700;color:var(--gold-dark);text-decoration:none;"
              >{{ fullName(s) }}</a>
              <span v-else style="font-family:var(--font-serif);font-size:15px;font-weight:700;color:var(--gold-dark);">{{ fullName(s) }}</span>
            <span class="badge badge-red"><AegisIcon name="lock" :size="12" /> Suspended</span>
            <span v-if="s.role" class="badge badge-gold"><AegisIcon name="shield" :size="10" /> {{ s.role === 'alternate' ? 'Alternate SS' : 'Primary SS' }}</span>
          </div>
          <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:12px;color:var(--text-3);">
            <span v-if="s.steward?.email || s.email" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="mail" :size="13" />{{ s.steward?.email ?? s.email }}</span>
            
          </div>
          <div v-if="s.declined_reason" style="display:flex;align-items:center;gap:6px;margin-top:10px;font-size:12px;color:var(--red-dark);">
            <AegisIcon name="alert-circle" :size="13" />
            <span>{{ reasonLabel(s.declined_reason) }}</span>
          </div>
          <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
            <button type="button" class="btn" @click="openReinstate(s)" style="display:inline-flex;align-items:center;gap:6px;background:var(--black,#000);color:var(--white,#fff);border-color:var(--black,#000);">
              <AegisIcon name="check" :size="13" /> Reinstate
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ TAB: I'M SS FOR ═══ -->
    <div v-show="activeTab === 'iamdsr'">

      <!-- ── EMPTY STATE: not serving as SS for anyone ── -->
      <template v-if="!servingAsSsFor?.length">
        <div class="card" style="padding:28px 24px;margin-bottom:20px">
          <div style="display:flex;align-items:flex-start;gap:16px">
            <div style="width:44px;height:44px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark);display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <AegisIcon name="users" :size="22" />
            </div>
            <div style="flex:1">
              <div style="font-family:var(--font-serif);font-size:18px;font-weight:700;color:var(--text);margin-bottom:6px">You're not serving as Support Steward for anyone yet</div>
              <p style="font-size:13px;color:var(--text-2);line-height:1.6;margin:0 0 16px">
                The Support Steward role is free and open to any Aegis user — but you can't apply for it.
                Providers designate their own SS by searching for you by name and email.
                If a provider adds you, their designation will appear here.
              </p>
              <div class="alert alert-info" style="margin-bottom:20px">
                <div class="alert-icon"><AegisIcon name="info" :size="14" /></div>
                <div>Support Stewards are designated by providers — you cannot apply or search for this role. If a provider adds you as their SS, it will appear here automatically.</div>
              </div>

              <!-- Available as SS toggle -->
              <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:16px">
                  <div>
                    <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:3px">Available as Support Steward</div>
                    <div style="font-size:12px;color:var(--text-3)">This is a private preference — not visible to other providers. Aegis uses this to help match when providers look for SS candidates.</div>
                  </div>
                  <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
                    <span :style="{fontSize:'11px',color:'var(--green-dark)',fontWeight:'600',opacity:ssSaved?1:0,transition:'opacity 0.3s'}">Saved ✓</span>
                    <AegisToggle v-model="availableAsSsToggle" @update:model-value="saveAvailableAsSs" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ── ACTIVE LIST: serving as SS for one or more providers ── -->
      <template v-else>
        <div class="alert alert-info" style="margin-bottom:20px">
          <div class="alert-icon"><AegisIcon name="shield" :size="18" /></div>
          <div class="alert-content">
            <div class="alert-title">Your SS role is active for {{ servingAsSsFor.length }} provider{{ servingAsSsFor.length !== 1 ? 's' : '' }}.</div>
            <div>You'll be notified if any of these providers trigger a critical incident. Open your SS Portal for task management and incident reporting.</div>
            <div style="margin-top:10px">
              <a :href="route('ss.dashboard')" class="btn btn-outline">
                <AegisIcon name="shield" :size="13" /> Open SS Portal
              </a>
            </div>
          </div>
        </div>

        <div class="section-title" style="margin-bottom:10px;display:flex;align-items:center;gap:8px">
          <AegisIcon name="users" :size="16" /> Providers I'm Supporting
        </div>
        <div class="list-group">
          <div v-for="item in servingAsSsFor" :key="item.id" class="list-group-item">
            <div class="avatar avatar-sm avatar-gold">{{ item.provider?.avatar_initials ?? '??' }}</div>
            <div style="flex:1;min-width:0">
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <a v-if="item.provider?.slug"
                   :href="route('public.provider', item.provider.slug)"
                   style="font-size:13px;font-weight:700;color:var(--gold-dark)">
                  {{ item.provider?.display_name }}{{ item.provider?.credentials ? ', ' + item.provider.credentials : '' }}
                </a>
                <span v-else style="font-size:13px;font-weight:700">{{ item.provider?.display_name ?? '—' }}</span>
                <AegisBadge :label="item.role === 'primary' ? 'Primary SS' : 'Alternate SS'" variant="gold" />
                <AegisBadge :label="item.status === 'active' ? 'Active' : 'Pending'" :variant="item.status === 'active' ? 'green' : 'gold'" />
              </div>
              <div style="font-size:12px;color:var(--text-3);margin-top:2px">
                {{ item.provider?.organization }}{{ item.provider?.location ? ' · ' + item.provider.location : '' }}
                
                <span v-if="item.review_due_at"> · Review due {{ fmtDate(item.review_due_at) }}</span>
              </div>
            </div>
            <a :href="route('ss.dashboard')" class="btn-icon" data-tooltip="Manage in SS Portal">
              <AegisIcon name="arrow-right" :size="14" />
            </a>
          </div>
        </div>
      </template>

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
              <span v-if="ssNotifySaving" class="spinner spinner-sm" />
              <AegisIcon v-else name="check" :size="13" />
              {{ ssNotifySaving ? 'Saving…' : 'Save Preferences' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════ MODALS ═══════════════════ -->

    <!-- ADD SUPPORT STEWARD — two-tab modal (Chapman decision #13) -->
    <!-- Tab A: existing Aegis user (match by name + email) -->
    <!-- Tab B: external invite (send onboarding email) -->
    <AegisModal :model-value="isOpen('addDsrStep1Modal').value" title="Add Support Steward" size="lg" @update:model-value="v => !v && closeModal('addDsrStep1Modal')">

      <!-- Flow tabs -->
      <div style="display:flex;justify-content:center;margin-bottom:20px;">
      <div class="tabs-segmented" style="margin-bottom:0;" role="tablist">
        <button type="button" class="tab-pill" :class="{ active: addSsFlow === 'existing' }" @click="addSsFlow = 'existing'">
          <AegisIcon name="user" :size="13" /> Existing Aegis User
        </button>
        <button type="button" class="tab-pill" :class="{ active: addSsFlow === 'external' }" @click="addSsFlow = 'external'">
          <AegisIcon name="mail" :size="13" /> External Invite
        </button>
      </div><!-- /tabs-segmented -->
      </div><!-- /tabs-center-wrap -->

      <!-- Flow A: existing user — live search with auto-fill -->
      <div v-show="addSsFlow === 'existing'">

        <!-- Selected user confirmation banner -->
        <div v-if="searchSelected" style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:var(--badge-bg-gold);border:1px solid var(--gold-dark);border-radius:var(--radius);margin-bottom:16px;">
          <div style="width:36px;height:36px;border-radius:var(--radius-sm);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            {{ searchSelected.initials }}
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:700;color:var(--text);">{{ searchSelected.display_name }}{{ searchSelected.credentials ? ', ' + searchSelected.credentials : '' }}</div>
            <div style="font-size:11px;color:var(--text-3);">{{ searchSelected.email }} &middot; <span style="color:var(--gold-dark);font-weight:600;">{{ searchSelected.role_label }}</span></div>
          </div>
          <button type="button" style="background:none;border:none;cursor:pointer;color:var(--text-3);padding:4px;" data-tooltip="Clear selection" @click="clearSelection">
            <AegisIcon name="x" :size="14" />
          </button>
        </div>

        <div v-else>
          <div class="form-group" style="position:relative;">
            <label class="form-label">Search by Name or Email <span class="required">*</span></label>
            <div style="position:relative;">
              <input
                :value="searchQuery"
                class="form-input"
                :class="{ 'is-error': fieldError('display_name') }"
                type="text"
                placeholder="Start typing a name or email…"
                autocomplete="off"
                @input="onSearchInput($event.target.value)"
                @blur="v$.inviteForm.display_name.$touch(); hideDropdown()"
                @focus="showDropdown = searchResults.length > 0"
              >
              <div v-if="searchLoading" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);">
                <span class="spinner spinner-sm" />
              </div>
            </div>
            <div v-if="fieldError('display_name')" class="form-error">{{ fieldError('display_name') }}</div>

            <!-- Dropdown results -->
            <div v-if="showDropdown && searchResults.length"
              style="position:absolute;top:100%;left:0;right:0;z-index:200;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-md);margin-top:2px;max-height:220px;overflow-y:auto;">
              <div
                v-for="user in searchResults"
                :key="user.id"
                style="display:flex;align-items:center;gap:10px;padding:10px 12px;cursor:pointer;border-bottom:1px solid var(--border);"
                @mousedown.prevent="selectUser(user)"
                @mouseover="$event.currentTarget.style.background='var(--surface-2)'"
                @mouseleave="$event.currentTarget.style.background=''"
              >
                <div style="width:32px;height:32px;border-radius:var(--radius-sm);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  {{ user.initials }}
                </div>
                <div style="flex:1;min-width:0;">
                  <div style="font-size:13px;font-weight:600;color:var(--text);">{{ user.display_name }}{{ user.credentials ? ', ' + user.credentials : '' }}</div>
                  <div style="font-size:11px;color:var(--text-3);">{{ user.email }}</div>
                </div>
                <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.3px;color:var(--gold-dark);flex-shrink:0;">{{ user.role_label }}</span>
              </div>
            </div>

            <!-- No results -->
            <div v-if="showDropdown && !searchResults.length && !searchLoading && searchQuery.length >= 2"
              style="position:absolute;top:100%;left:0;right:0;z-index:200;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-md);margin-top:2px;padding:14px 12px;font-size:13px;color:var(--text-3);text-align:center;">
              No Support Stewards found for "{{ searchQuery }}".<br>
              <span style="font-size:12px;">Try the <strong>External Invite</strong> tab to invite someone new.</span>
            </div>
          </div>

          <div class="alert alert-info" style="margin-top:4px;">
            <div class="alert-icon"><AegisIcon name="info" :size="13" /></div>
            <div style="font-size:12px;">Search finds users with the Support Steward role, or Practitioners who have made themselves available as SS.</div>
          </div>
        </div>

      </div>

      <!-- Flow B: external invite -->
      <div v-show="addSsFlow === 'external'">
        <div class="alert alert-info" style="margin-bottom:14px">
          <div class="alert-icon"><AegisIcon name="mail" :size="14" /></div>
          <div>Send an onboarding invitation to someone who is not yet on Aegis. They will receive an email to create their account and accept your SS invitation.</div>
        </div>
        <div class="row-2">
          <div class="form-group">
            <label class="form-label">Full Name <span class="required">*</span></label>
            <input v-model="inviteForm.display_name" class="form-input" :class="{ 'is-error': fieldError('display_name') }" type="text" placeholder="First Last" @blur="v$.inviteForm.display_name.$touch()">
            <div v-if="fieldError('display_name')" class="form-error">{{ fieldError('display_name') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address <span class="required">*</span></label>
            <input v-model="inviteForm.email" class="form-input" :class="{ 'is-error': fieldError('email') }" type="email" placeholder="email@example.com" @blur="v$.inviteForm.email.$touch()">
            <div v-if="fieldError('email')" class="form-error">{{ fieldError('email') }}</div>
          </div>
        </div>
      </div>

      <!-- Role picker (shared) -->
      <div class="form-group" style="margin-top:16px">
        <label class="form-label">Support Steward Role <span class="required">*</span></label>
        <div class="role-option" :class="{ selected: inviteForm.role === 'support' }" @click="inviteForm.role = 'support'">
          <input type="radio" name="dsrRole" value="support" :checked="inviteForm.role === 'support'" style="accent-color:var(--gold-dark)">
          <div>
            <div style="font-size:13px;font-weight:700;color:var(--text)">Support Steward</div>
            <div style="font-size:12px;color:var(--text-3);margin-top:3px">Supports communication, coordination, and key tasks during a critical moment, guided by your Continuity Plan.</div>
          </div>
        </div>
        <div class="role-option" :class="{ selected: inviteForm.role === 'alternate' }" @click="inviteForm.role = 'alternate'">
          <input type="radio" name="dsrRole" value="alternate" :checked="inviteForm.role === 'alternate'" style="accent-color:var(--gold-dark)">
          <div>
            <div style="font-size:13px;font-weight:700;color:var(--text)">Alternate Support Steward</div>
            <div style="font-size:12px;color:var(--text-3);margin-top:3px">Steps in if the primary Support Steward is unavailable.</div>
          </div>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-outline" @click="closeModal('addDsrStep1Modal')">Cancel</button>
        <button class="btn btn-primary" :class="{ 'btn-spin': inviteForm.processing }" :disabled="inviteForm.processing" @click="submitInvite">
          <AegisIcon name="check" :size="13" /> {{ inviteForm.processing ? 'Sending…' : addSsFlow === 'external' ? 'Send Invitation' : 'Designate as SS' }}
        </button>
      </template>
    </AegisModal>



    <!-- EDIT SS (unified — includes manage access) -->
    <AegisModal :model-value="isOpen('editDsrModal').value" :title="activeSteward ? 'Edit Support Steward — ' + fullName(activeSteward) : 'Edit Support Steward'" size="md" @update:model-value="v => !v && closeModal('editDsrModal')">
      <div class="alert alert-info"><div class="alert-icon"><AegisIcon name="info" :size="14" /></div><div class="alert-content">Role changes will notify your Support Steward by email. No countersignature required.</div></div>

      <!-- Role -->
      <div class="form-group" style="margin-top:14px">
        <label class="form-label">Role</label>
        <select v-model="editForm.role" class="form-control form-select" :class="{ 'is-error': editFieldError('role') }" @blur="v$edit.role.$touch()">
          <option value="support">Primary Support Steward</option>
          <option value="alternate">Alternate Support Steward</option>
        </select>
        <div v-if="editFieldError('role')" class="form-error">{{ editFieldError('role') }}</div>
      </div>

      <!-- Authorized responsibilities (read-only display) -->
      <div class="form-group">
        <label class="form-label">Authorized Responsibilities</label>
        <div v-if="activeSteward && activeSteward.responsibilities && activeSteward.responsibilities.length" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:6px">
          <span v-for="r in activeSteward.responsibilities" :key="r" class="badge badge-gold" style="text-transform:capitalize">{{ typeof r === 'object' ? r.text : r }}</span>
        </div>
        <div v-else style="font-size:12px;color:var(--text-3);margin-bottom:6px">No specific responsibilities assigned yet.</div>
        <div style="font-size:11px;color:var(--text-4);display:flex;align-items:center;gap:4px">
          <AegisIcon name="info" :size="11" />
          <span>Responsibilities are set when the Support Steward designation is created.</span>
        </div>
        <a :href="route('provider.plan.index') + '#incident-grid'" style="font-size:12px;color:var(--gold-dark);text-decoration:none;display:inline-block;margin-top:6px">Configure on Plan →</a>
      </div>

      <!-- Notes -->
      <div class="form-group">
        <label class="form-label">Notes / Instructions</label>
        <textarea v-model="editForm.notes" class="form-control" style="min-height:80px" placeholder="Any updates or context…"></textarea>
      </div>


      <template #footer>
        <button type="button" class="btn btn-outline" @click="closeModal('editDsrModal')">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="editForm.processing" style="display:inline-flex;align-items:center;gap:6px" @click="submitEdit">
          <span v-if="editForm.processing" class="spinner spinner-sm" />
          <AegisIcon v-else name="check" :size="13" />
          {{ editForm.processing ? 'Saving…' : 'Save Changes' }}
        </button>
      </template>
    </AegisModal>



    <!-- VIEW AGREEMENT — matches CS ViewCsAgreementModal layout exactly -->
    <AegisModal
      :model-value="isOpen('viewDsrAgreementModal').value"
      :title="'Support Steward Agreement' + (activeSteward ? ' — ' + fullName(activeSteward) : '')"
      size="lg"
      @update:model-value="v => !v && closeModal('viewDsrAgreementModal')"
    >
      <template v-if="activeSteward">

        <!-- Status banner -->
        <div
          :class="activeSteward.status === 'active' ? 'alert alert-success' : 'alert alert-warning'"
          style="display:flex;align-items:center;gap:8px;margin-bottom:16px;"
        >
          <AegisIcon :name="activeSteward.status === 'active' ? 'check-circle' : 'clock'" :size="14" />
          <div>{{ activeSteward.status === 'active' ? 'Standing agreement active · Both parties acknowledged' : 'Pending acknowledgement from Support Steward' }}</div>
        </div>

        <!-- HEADER: avatar + name + role + date — mirrors CS modal exactly -->
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
          <div style="width:48px;height:48px;border-radius:var(--radius);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            {{ initials(fullName(activeSteward)) }}
          </div>
          <div style="flex:1;min-width:0;">
            <component
              :is="activeSteward.steward?.slug ? 'a' : 'span'"
              :href="activeSteward.steward?.slug ? route('public.ss', activeSteward.steward.slug) : undefined"
              style="font-size:15px;font-weight:700;color:var(--gold-dark);text-decoration:none;font-family:var(--font-serif);"
            >{{ fullName(activeSteward) }}</component>
            <div style="font-size:11px;color:var(--text-3);margin-top:4px;display:flex;align-items:center;gap:4px;">
              <AegisIcon name="shield-check" :size="11" style="color:var(--green-dark);flex-shrink:0;" />
              Standing agreement · Active since {{ fmtDate(activeSteward.ss_acknowledged_at ?? activeSteward.signed_at ?? activeSteward.invited_at) }}
            </div>
            <div style="display:flex;gap:6px;margin-top:4px;flex-wrap:wrap;">
              <AegisBadge :label="activeSteward.role === 'alternate' ? 'Alternate Support Steward' : 'Primary Support Steward'" variant="gold" icon="shield" />
            </div>
          </div>
          <div v-if="activeSteward.ss_acknowledged_at || activeSteward.signed_at || activeSteward.invited_at" style="font-size:12px;color:var(--text-3);text-align:right;flex-shrink:0;">
            <div style="font-weight:600;">Signing Date</div>
            <div>{{ fmtDate(activeSteward.ss_acknowledged_at ?? activeSteward.signed_at ?? activeSteward.invited_at) }}</div>
          </div>
        </div>

        <!-- SUMMARY CARDS: 2-col grid — mirrors CS layout, SS-adapted -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">

          <!-- Responsibilities summary -->
          <div style="background:var(--surface-2);border-radius:var(--radius);padding:12px;">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-3);margin-bottom:8px;">Responsibilities</div>
            <div v-if="activeSteward.responsibilities && activeSteward.responsibilities.length">
              <div v-for="r in activeSteward.responsibilities" :key="r" style="font-size:12px;color:var(--text-2);display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                <AegisIcon name="check" :size="10" style="color:var(--gold-dark);flex-shrink:0;" />
                <span>{{ typeof r === 'object' ? r.text : r }}</span>
              </div>
            </div>
            <div v-else style="font-size:12px;color:var(--text-3);">No responsibilities assigned</div>
          </div>

          <!-- Role -->
          <div style="background:var(--surface-2);border-radius:var(--radius);padding:12px;">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-3);margin-bottom:8px;">Role</div>
            <span class="badge badge-gold">
              <AegisIcon name="shield" :size="10" style="margin-right:3px;" />
              {{ activeSteward.role === 'alternate' ? 'Alternate SS' : 'Primary SS' }}
            </span>
          </div>
        </div>

        <!-- Authorized Responsibilities (full list) -->
        <div v-if="activeSteward.responsibilities && activeSteward.responsibilities.length" style="margin-bottom:16px;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-3);margin-bottom:8px;">Authorized Responsibilities</div>
          <div style="display:flex;flex-wrap:wrap;gap:6px;">
            <span v-for="r in activeSteward.responsibilities" :key="r" class="badge badge-gold" style="text-transform:capitalize;">{{ typeof r === 'object' ? r.text : r }}</span>
          </div>
        </div>

        <!-- LEGAL DOCUMENT — mirrors CS section exactly -->
        <div style="background:var(--surface-2);border-radius:var(--radius);padding:18px 22px;font-size:13px;line-height:1.8;color:var(--text-2);">
          <div style="font-family:var(--font-serif);font-size:17px;font-weight:700;color:var(--text);text-align:center;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border);">Aegis SUPPORT STEWARD PLAN</div>

          <p><strong>Section 1. Purpose.</strong> This agreement authorizes the Support Steward to support the Provider during a critical moment — coordinating logistics, communication, and key tasks as designated in the Continuity Plan.</p>

          <p v-if="activeSteward.responsibilities && activeSteward.responsibilities.length">
            <strong>Section 2. Authorized Responsibilities.</strong>
            Support Steward is authorized to carry out the following responsibilities:
            {{ activeSteward.responsibilities.map(r => typeof r === 'object' ? r.text : r).join(', ') }}.
          </p>

          <p><strong>Section 3. Compliance.</strong> Support Steward agrees to maintain full confidentiality and not exceed authorized responsibilities. All actions are logged for audit purposes.</p>

          <p><strong>Section 4. Annual Attestation.</strong> The Provider re-confirms the Support Steward's responsibilities and contact information annually.</p>
        </div>

        <div style="margin-top:16px;display:flex;align-items:flex-start;gap:6px;">
          <span style="flex-shrink:0;"><AegisIcon name="shield" :size="14" /></span>
          <p style="font-size:11px;margin:0;color:var(--text-3);">This agreement stays active until cancelled by either party. The Support Steward operates within the scope defined by the Provider — they cannot act beyond what has been authorized.</p>
        </div>

      </template>

      <template #footer>
        <button type="button" class="btn btn-outline" @click="closeModal('viewDsrAgreementModal')">Close</button>
        <button type="button" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px;" @click="downloadAgreement">
          <AegisIcon name="download" :size="14" /> Download PDF
        </button>
      </template>
    </AegisModal>



    <!-- REMOVE — centralized EndStewardRetainerModal -->
    <EndStewardRetainerModal
      v-model="showRemoveModal"
      :steward="activeSteward"
      kind="ss"
      @success="router.reload({ only: ['stewards', 'suspended', 'ssCount'] })"
    />

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
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useVuelidate } from '@vuelidate/core'
import { required, email as emailRule, helpers } from '@vuelidate/validators'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisToggle from '@/components/ui/AegisToggle.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { useMessageButton } from '@/composables/useMessageButton'
import PlanReviewAlert from '@/components/PlanReviewAlert.vue'
import EndStewardRetainerModal from '@/components/modals/EndStewardRetainerModal.vue'

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
  availableAsSs:      { type: Boolean, default: false },
})

// ── Composables ──────────────────────────────────────────
const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()
const { confirmAction } = useConfirm()
const { openConversation: msgSteward } = useMessageButton()

// ── Tab ──────────────────────────────────────────────────
const activeTab = ref('mydsr')
const showRemoveModal = ref(false)

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

// ── SS Availability (I'm SS For tab) ─────────────────────
const availableAsSsToggle = ref(props.availableAsSs ?? false)
const ssSaved = ref(false)
let ssSavedTimer = null
function saveAvailableAsSs(val) {
  router.post(route('provider.settings.ss-availability'), { available_as_ss: val }, {
    preserveScroll: true,
    onSuccess: () => {
      ssSaved.value = true
      clearTimeout(ssSavedTimer)
      ssSavedTimer = setTimeout(() => { ssSaved.value = false }, 2500)
    },
  })
}

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
function reasonLabel(key) {
  if (!key) return ''
  // Strip 'terminated:' prefix if present
  const raw = key.startsWith('terminated:') ? key.slice(11) : key
  const map = {
    role_no_longer_needed:  'Role no longer needed',
    temporary_leave:        'Temporary leave',
    replacing:              'Replacing with a different SS',
    practice_restructuring: 'Practice restructuring',
    ss_requested:           'SS requested removal',
    other:                  'Other',
    steward_resigned:       'Steward resigned',
    mutual:                 'Mutual termination',
    practice_closing:       'Practice closing',
  }
  return map[raw] ?? raw.replace(/_/g, ' ')
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
  editForm.credentials = s?.steward?.credentials ?? s?.credentials ?? ''
  editForm.phone = s?.steward?.phone ?? ''
  editForm.email = s?.steward?.email ?? ''
  editForm.role = roleVal(s?.role) || 'support'
  editForm.relationship = s?.steward?.relationship ?? ''
  editForm.notes = s?.notes ?? ''
  v$edit.value.$reset()
  openModal('editDsrModal')
}
function openReinstate(s)   { activeStewardId.value = s.id; openModal('reinstateModal') }
function openRemove(s)      { activeStewardId.value = s.id; showRemoveModal.value = true }
function openResend(s)      { activeStewardId.value = s.id; openModal('resendInviteModal') }
function openCancelInvite(s){ activeStewardId.value = s.id; openModal('cancelInviteModal') }
function msg(s) { msgSteward(s?.steward_id ?? s?.steward?.id ?? s?.id) }








// ── Forms (all at top-level of setup) ────────────────────
const inviteForm = useForm({
  user_id: null, email: '', display_name: '', role: 'support',
  external: false, expires_days: '30', message: '',
})
const editForm = useForm({
  display_name: '', credentials: '', relationship: '', phone: '', email: '', role: 'support', notes: '',
})
const reinstateForm = useForm({ message: '' })
const resendForm    = useForm({ expires_days: '30', message: '' })


const addSsFlow      = ref('existing') // 'existing' | 'external'

// ── User search state (Existing Aegis User flow) ──────────────────────────
const searchQuery    = ref('')
const searchResults  = ref([])
const searchLoading  = ref(false)
const searchSelected = ref(null)
const showDropdown   = ref(false)
let   searchTimer    = null

function onSearchInput(val) {
  searchQuery.value = val
  searchSelected.value = null
  inviteForm.user_id = null
  inviteForm.display_name = val
  inviteForm.email = ''
  if (val.length < 2) { searchResults.value = []; showDropdown.value = false; return }
  clearTimeout(searchTimer)
  searchLoading.value = true
  searchTimer = setTimeout(async () => {
    try {
      const res = await window.axios.get(route('provider.ss.search-users'), { params: { q: val } })
      searchResults.value = res.data
      showDropdown.value = res.data.length > 0
    } catch { searchResults.value = [] }
    finally { searchLoading.value = false }
  }, 280)
}

function selectUser(user) {
  searchSelected.value = user
  inviteForm.user_id      = user.id
  inviteForm.display_name = user.display_name
  inviteForm.email        = user.email
  searchQuery.value       = user.display_name
  searchResults.value     = []
  showDropdown.value      = false
}

function hideDropdown() { setTimeout(() => { showDropdown.value = false }, 200) }
function clearSelection() {
  searchSelected.value    = null
  inviteForm.user_id      = null
  inviteForm.display_name = ''
  inviteForm.email        = ''
  searchQuery.value       = ''
}

// ── Vuelidate ─────────────────────────────────────────────
const rules = {
  inviteForm: {
    email: { email: helpers.withMessage('A valid email is required.', emailRule) },
    display_name: { required: helpers.withMessage('Full name is required.', required) },
  },
}
const v$ = useVuelidate(rules, { inviteForm }, { $scope: false })

const editRules = {
  display_name: { required: helpers.withMessage('Full name is required.', required) },
  role: { required: helpers.withMessage('Role is required.', required) },
}
const v$edit = useVuelidate(editRules, editForm, { $scope: false })

function fieldError(key) {
  const map = {
    email:        v$.value.inviteForm?.email,
    display_name: v$.value.inviteForm?.display_name,
  }
  const node = map[key]
  return node?.$dirty && node?.$errors?.[0]?.$message ? node.$errors[0].$message : null
}

function editFieldError(key) {
  const node = v$edit.value[key]
  return node?.$dirty && node?.$errors?.[0]?.$message ? node.$errors[0].$message : null
}

// ── Submit actions ────────────────────────────────────────
async function submitEdit() {
  await v$edit.value.$validate()
  if (v$edit.value.$error) return

  editForm.put(route('provider.ss.update', { steward: activeStewardId.value }), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Support Steward details updated.')
      closeModal('editDsrModal')
    },
    onError: () => toast.error('Could not save changes.'),
  })
}

function downloadAgreement() {
  window.open(route('provider.ss.agreement.download', { steward: activeStewardId.value }), '_blank')
}

async function submitInvite() {
  const ok = await v$.value.$validate()
  if (!ok) return
  // Set external flag based on active flow tab
  inviteForm.external = addSsFlow.value === 'external'
  inviteForm.post(route('provider.ss.invite'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(addSsFlow.value === 'external' ? 'Invitation sent.' : 'Support Steward designated.')
      closeModal('addDsrStep1Modal')
      inviteForm.reset()
      addSsFlow.value = 'existing'
      searchQuery.value = ''
      searchResults.value = []
      searchSelected.value = null
      showDropdown.value = false
      v$.value.$reset()
    },
    onError: (errors) => {
      if (errors.email || errors.display_name) {
        // Validation error from backend — stay on modal, show field errors
      }
    },
  })
}

function submitArchiveSteward(s) {
  router.post(route('provider.ss.archive', { steward: s.id }), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Record archived.'),
  })
}


function submitReinstate() {
  reinstateForm.post(route('provider.ss.reinstate', { steward: activeStewardId.value }), {
    preserveScroll: true,
    onSuccess: () => { toast.success('Support Steward reinstated.'); closeModal('reinstateModal'); reinstateForm.reset() },
    onError: () => toast.error('Could not reinstate steward.'),
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
    onError: () => toast.error('Could not cancel invitation.'),
  })
}


</script>

<style scoped>
@keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
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
.role-option.selected { border-color:var(--gold-dark); border-width:1px; background:var(--badge-bg-gold); }

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