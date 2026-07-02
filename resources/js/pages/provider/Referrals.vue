<!--
  pages/provider/Referrals.vue — Referral Coordination (Practice tier).
  PHP ground truth: referrals.php
  Backend contract: ReferralsController@index / store / accept / decline / cancel / complete
  Route prefix: provider.referrals.*
-->
<template>
  <AppLayout>

    <!-- HERO -->
    <AegisHeroBanner
      quiet
      eyebrow="Referral Center"
      title="Send, receive, and manage referrals"
      subtitle="Send, receive, and follow up on client referrals across your network — all in one place."
    >
      <template #actions>
        <a
          class="btn-hero-ghost is-on-light"
          :href="route('provider.activity') + '?event_type=referral'"
          data-tooltip="Activity log"
          style="display:inline-flex;align-items:center;gap:6px"
        >
          <AegisIcon name="activity" :size="14" />
          Activity
        </a>
        <button type="button" class="btn-hero-solid is-on-light" @click="openModal('referralModal')" style="display:inline-flex;align-items:center;gap:6px">
          <AegisIcon name="plus" :size="14" />
          New Referral
        </button>
      </template>
    </AegisHeroBanner>

    <!-- STAT CHIPS -->
    <div class="stat-chips-row">
      <div class="stat-chip" style="cursor:pointer" @click="tab='pending'">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)">
          <AegisIcon name="phone" :size="18" />
        </div>
        <div>
          <div class="stat-chip-value">{{ refStats.pending }}</div>
          <div class="stat-chip-label">Pending Review</div>
        </div>
      </div>
      <div class="stat-chip" style="cursor:pointer" @click="tab='sent'">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)">
          <AegisIcon name="send" :size="18" />
        </div>
        <div>
          <div class="stat-chip-value">{{ refStats.sent_active }}</div>
          <div class="stat-chip-label">Sent Awaiting</div>
        </div>
      </div>
      <div class="stat-chip" style="cursor:pointer" @click="tab='completed'">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)">
          <AegisIcon name="check" :size="18" />
        </div>
        <div>
          <div class="stat-chip-value">{{ refStats.completed_month }}</div>
          <div class="stat-chip-label">Completed / Mo</div>
        </div>
      </div>
      <div class="stat-chip">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)">
          <AegisIcon name="bar-chart" :size="18" />
        </div>
        <div>
          <div class="stat-chip-value">{{ refStats.accept_rate }}%</div>
          <div class="stat-chip-label">Accept Rate</div>
        </div>
      </div>
    </div>

    <!-- SLA ALERT — urgent pending only -->
    <div v-if="urgentPending.length" class="sla-alert">
      <div class="sla-alert-left">
        <AegisIcon name="alert-triangle" :size="16" />
        <span>
          {{ urgentPending.length }} referral{{ urgentPending.length > 1 ? 's' : '' }} with urgent SLA —
          <strong>{{ urgentPending[0].client_initials }}. ({{ urgentPending[0].reason }})</strong>
          · from {{ urgentPending[0].counterpart_name }}
        </span>
      </div>
      <button class="btn btn-sm btn-danger" style="display:inline-flex;align-items:center;gap:4px" @click="tab='pending'">
        Review Now
        <AegisIcon name="chevron-right" :size="12" />
      </button>
    </div>

    <!-- TABS -->
    <div class="tabs-segmented" role="tablist" style="margin-bottom:24px">
      <button class="tab-pill" :class="{ active: tab==='pending' }" role="tab" :aria-selected="tab==='pending'" @click="tab='pending'" style="display:inline-flex;align-items:center;gap:5px">
        <AegisIcon name="phone" :size="13" />
        Pending
        <span v-if="incomingReferrals.length" class="badge-pill">{{ incomingReferrals.length }}</span>
      </button>
      <button class="tab-pill" :class="{ active: tab==='sent' }" role="tab" :aria-selected="tab==='sent'" @click="tab='sent'" style="display:inline-flex;align-items:center;gap:5px">
        <AegisIcon name="send" :size="13" />
        Sent
        <span v-if="sentReferrals.length" class="badge-pill">{{ sentReferrals.length }}</span>
      </button>
      <button class="tab-pill" :class="{ active: tab==='completed' }" role="tab" :aria-selected="tab==='completed'" @click="tab='completed'" style="display:inline-flex;align-items:center;gap:5px">
        <AegisIcon name="check" :size="13" />
        Completed
      </button>
      <button class="tab-pill" :class="{ active: tab==='all' }" role="tab" :aria-selected="tab==='all'" @click="tab='all'" style="display:inline-flex;align-items:center;gap:5px">
        <AegisIcon name="grid" :size="13" />
        All Referrals
      </button>
      <button class="tab-pill" :class="{ active: tab==='archive' }" role="tab" :aria-selected="tab==='archive'" @click="tab='archive'" style="display:inline-flex;align-items:center;gap:5px">
        <AegisIcon name="archive" :size="13" />
        Archive
      </button>
    </div>

    <!-- ══ TAB: PENDING ══ -->
    <div v-show="tab==='pending'">
      <div class="tab-top-row">
        <div class="tab-top-info">{{ incomingReferrals.length }} referrals awaiting your response · Sorted by urgency</div>
        <div style="display:flex;gap:8px" class="pending-filters">
          <select v-model="filterPendingUrgency" class="form-select" style="height:34px;font-size:12px;width:169px">
            <option value="">All Urgency</option>
            <option value="urgent">Urgent</option>
            <option value="soon">Soon</option>
            <option value="routine">Routine</option>
          </select>
        </div>
      </div>

      <AegisEmptyState
        v-if="!filteredPending.length"
        icon="phone"
        title="No pending referrals"
        description="You're all caught up. New received referrals will appear here."
      />

      <template v-else>
        <article
          v-for="r in pagedPending"
          :key="r.id"
          class="card rfc-card"
          :class="rfcCardClass(r)"
          style="cursor:pointer"
          @click="openDetail(r)"
        >
          <header class="rfc-head">
            <div class="rfc-head-tags">
              <span class="badge badge-blue" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="phone" :size="10" />Received</span>
              <span class="badge" :class="urgencyBadgeClass(r.urgency)" style="display:inline-flex;align-items:center;gap:4px">
                <AegisIcon :name="r.urgency==='urgent' ? 'zap' : 'clock'" :size="10" />
                {{ urgencyLabel(r.urgency) }}
              </span>
            </div>
            <span class="rfc-time">{{ timeAgo(r.created_at) }}</span>
          </header>
          <div class="rfc-body">
            <div class="rfc-avatar" :style="r.counterpart_avatar ? { backgroundImage: `url(${r.counterpart_avatar})`, backgroundSize:'cover', backgroundPosition:'center' } : {}">
              <template v-if="!r.counterpart_avatar">{{ r.client_initials || '?' }}</template>
            </div>
            <div class="rfc-info">
              <div class="rfc-patient">
                {{ r.client_initials }}<span v-if="r.client_age_band" class="rfc-meta">· {{ r.client_age_band }}</span>
              </div>
              <div class="rfc-context">
                <span><strong>From</strong> {{ r.counterpart_name }}</span>
                <span v-if="r.reason">· {{ r.reason }}</span>
              </div>
            </div>
          </div>
          <footer class="rfc-foot" @click.stop>
            <button
              class="rfc-act"
              type="button"
              data-tooltip="Message sender"
              :disabled="msgLoading === r.counterpart_user_id"
              @click.stop="openConversation(r.counterpart_user_id)"
            ><AegisIcon name="message" :size="14" /></button>
            <button
              class="rfc-act is-danger"
              type="button"
              data-tooltip="Decline"
              aria-label="Decline"
              style="display:inline-flex;align-items:center;justify-content:center"
              @click="openDecline(r)"
            ><AegisIcon name="x" :size="14" /></button>
            <button
              class="rfc-cta"
              type="button"
              style="display:inline-flex;align-items:center;gap:4px"
              @click="openAccept(r)"
            ><AegisIcon name="check" :size="14" /> Accept</button>
          </footer>
        </article>

        <AegisPagination
          v-if="filteredPending.length > pageSize"
          :total="filteredPending.length"
          :per-page="pageSize"
          :current-page="pendingPage"
          @update:current-page="pendingPage = $event"
        />
      </template>
    </div>

    <!-- ══ TAB: SENT ══ -->
    <div v-show="tab==='sent'">
      <div class="tab-top-row">
        <div class="tab-top-info">{{ sentReferrals.length }} referrals sent by you — awaiting or in progress</div>
        <select v-model="filterSentStatus" class="form-select" style="height:34px;font-size:12px;width:150px">
          <option value="">All Status</option>
          <option value="sent">Awaiting</option>
          <option value="accepted">Accepted</option>
          <option value="declined">Declined</option>
        </select>
      </div>

      <AegisEmptyState
        v-if="!filteredSent.length"
        icon="send"
        title="No sent referrals in progress"
        description="Referrals you send to other providers will appear here."
      />

      <template v-else>
        <article
          v-for="r in pagedSent"
          :key="r.id"
          class="card rfc-card"
          :class="rfcCardClass(r)"
          :style="val(r.status)==='declined' ? 'opacity:0.85;cursor:pointer' : 'cursor:pointer'"
          @click="openDetail(r)"
        >
          <header class="rfc-head">
            <div class="rfc-head-tags">
              <span class="badge badge-green" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="send" :size="10" />Sent</span>
              <template v-if="val(r.status)==='declined'">
                <span class="badge badge-red" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="x" :size="10" />Declined</span>
              </template>
              <template v-else-if="val(r.status)==='accepted'">
                <span class="badge badge-green" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="check" :size="10" />Accepted</span>
                <span v-if="r.responded_at" class="badge badge-blue" style="display:inline-flex;align-items:center;gap:4px">
                  <AegisIcon name="calendar" :size="10" />{{ fmtDate(r.responded_at) }}
                </span>
              </template>
              <template v-else>
                <span class="badge badge-orange" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="clock" :size="10" />Awaiting</span>
              </template>
            </div>
            <span class="rfc-time">{{ timeAgo(r.created_at) }}</span>
          </header>
          <div class="rfc-body">
            <div class="rfc-avatar" :style="r.counterpart_avatar ? { backgroundImage: `url(${r.counterpart_avatar})`, backgroundSize:'cover', backgroundPosition:'center' } : {}">
              <template v-if="!r.counterpart_avatar">{{ r.client_initials || '?' }}</template>
            </div>
            <div class="rfc-info">
              <div class="rfc-patient">
                {{ r.client_initials }}<span v-if="r.client_age_band" class="rfc-meta">· {{ r.client_age_band }}</span>
              </div>
              <div class="rfc-context">
                <span><strong>To</strong> {{ r.counterpart_name }}</span>
                <span v-if="r.reason">· {{ r.reason }}</span>
                <span v-if="val(r.status)==='declined'" style="color:var(--red-dark)">· Not accepting new clients</span>
              </div>
            </div>
          </div>
          <footer class="rfc-foot" @click.stop>
            <template v-if="val(r.status)==='accepted'">
              <button class="btn-icon" type="button" data-tooltip="Message" :disabled="msgLoading === r.counterpart_user_id" @click="openConversation(r.counterpart_user_id)"><AegisIcon name="message" :size="14" /></button>
              <button class="rfc-act" type="button" data-tooltip="View details" aria-label="View details" style="display:inline-flex;align-items:center;justify-content:center" @click="openDetail(r)"><AegisIcon name="search" :size="14" /></button>
              <button class="rfc-cta" type="button" style="display:inline-flex;align-items:center;gap:4px" @click="openComplete(r)"><AegisIcon name="check" :size="14" /> Mark complete</button>
            </template>
            <template v-else-if="val(r.status)==='declined'">
              <button class="rfc-act" type="button" data-tooltip="View details" aria-label="View details" style="display:inline-flex;align-items:center;justify-content:center" @click="openDetail(r)"><AegisIcon name="search" :size="14" /></button>
              <button class="rfc-cta" type="button" style="display:inline-flex;align-items:center;gap:4px" @click="openModal('referralModal')"><AegisIcon name="refresh-cw" :size="14" /> Re-refer</button>
            </template>
            <template v-else>
              <button class="rfc-act" type="button" data-tooltip="Follow up" :disabled="msgLoading === r.counterpart_user_id" @click="openConversation(r.counterpart_user_id)"><AegisIcon name="message" :size="14" /></button>
              <button class="rfc-act is-danger" type="button" data-tooltip="Cancel" aria-label="Cancel" style="display:inline-flex;align-items:center;justify-content:center" @click="openCancel(r)"><AegisIcon name="x" :size="14" /></button>
              <button class="rfc-cta is-muted" type="button" @click="openDetail(r)">Details</button>
            </template>
          </footer>
        </article>

        <AegisPagination
          v-if="filteredSent.length > pageSize"
          :total="filteredSent.length"
          :per-page="pageSize"
          :current-page="sentPage"
          @update:current-page="sentPage = $event"
        />
      </template>
    </div>

    <!-- ══ TAB: COMPLETED ══ -->
    <div v-show="tab==='completed'">
      <div class="tab-top-info" style="margin-bottom:14px">{{ completedReferrals.length }} completed referrals this month</div>

      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Client</th>
              <th>Provider</th>
              <th>Reason for Referral</th>
              <th>Completed</th>
              <th>Outcome</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!pagedCompleted.length">
              <td colspan="6" style="text-align:center;padding:32px;color:var(--text-3)">No completed referrals this month.</td>
            </tr>
            <tr v-for="r in pagedCompleted" :key="r.id">
              <td><strong>{{ r.client_initials }}.</strong> <span v-if="r.client_age_band">· {{ r.client_age_band }}</span></td>
              <td>
                <a v-if="r.counterpart_slug" :href="route('public.provider', { slug: r.counterpart_slug })" target="_blank" style="color:inherit;font-weight:700;text-decoration:underline">{{ r.counterpart_name }}</a>
                <span v-else>{{ r.counterpart_name }}</span>
              </td>
              <td>{{ r.reason || '—' }}</td>
              <td>{{ fmtDate(r.closed_at) }}</td>
              <td>
                <span class="badge badge-green" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="check" :size="10" />Completed</span>
              </td>
              <td>
                <button class="btn-icon btn-icon-sm" data-tooltip="Details" style="display:inline-flex;align-items:center;justify-content:center" @click="openDetail(r)"><AegisIcon name="eye" :size="14" /></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AegisPagination
        v-if="completedReferrals.length > pageSize"
        :total="completedReferrals.length"
        :per-page="pageSize"
        :current-page="completedPage"
        @update:current-page="completedPage = $event"
      />
    </div>

    <!-- ══ TAB: ALL REFERRALS ══ -->
    <div v-show="tab==='all'">
      <div class="filter-bar">
        <div class="filter-search">
          <span class="filter-search-icon"><AegisIcon name="search" :size="14" /></span>
          <input v-model="filterAllSearch" class="form-input" type="text" placeholder="Search client, provider, diagnosis..." />
        </div>
        <select v-model="filterAllType" class="form-select" style="height:36px;font-size:12px;width:130px">
          <option value="">All Types</option>
          <option value="received">Received</option>
          <option value="sent">Sent</option>
        </select>
        <select v-model="filterAllStatus" class="form-select" style="height:36px;font-size:12px;width:140px">
          <option value="">All Status</option>
          <option value="sent">Pending</option>
          <option value="accepted">Accepted</option>
          <option value="declined">Declined</option>
          <option value="closed">Completed</option>
        </select>
        <select v-model="filterAllUrgency" class="form-select" style="height:36px;font-size:12px;width:140px">
          <option value="">All Urgency</option>
          <option value="urgent">Urgent</option>
          <option value="soon">Soon</option>
          <option value="routine">Routine</option>
        </select>
      </div>

      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th class="sortable" @click="sortAll('direction')">Type ↕</th>
              <th class="sortable" @click="sortAll('client_initials')">Client ↕</th>
              <th>Provider</th>
              <th class="sortable" @click="sortAll('urgency')">Urgency ↕</th>
              <th class="sortable" @click="sortAll('created_at')">Date ↕</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!pagedAll.length">
              <td colspan="7" style="text-align:center;padding:32px;color:var(--text-3)">No referrals found.</td>
            </tr>
            <tr v-for="r in pagedAll" :key="r.id">
              <td>
                <span v-if="r.direction==='received'" class="badge badge-blue" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="phone" :size="9" />Rcvd</span>
                <span v-else class="badge badge-green" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="send" :size="9" />Sent</span>
              </td>
              <td><strong>{{ r.client_initials }}.</strong> <span v-if="r.client_age_band">· {{ r.client_age_band }}</span></td>
              <td>
                <a v-if="r.counterpart_slug" :href="route('public.provider', { slug: r.counterpart_slug })" target="_blank" style="color:inherit;font-weight:700;text-decoration:underline">{{ r.counterpart_name }}</a>
                <span v-else>{{ r.counterpart_name }}</span>
              </td>
              <td>
                <span class="badge" :class="urgencyBadgeClass(r.urgency)" style="display:inline-flex;align-items:center;gap:4px">
                  <AegisIcon :name="r.urgency==='urgent' ? 'zap' : 'clock'" :size="9" />
                  {{ urgencyLabel(r.urgency) }}
                </span>
              </td>
              <td>{{ fmtDate(r.created_at) }}</td>
              <td><span class="badge" :class="statusBadgeClass(r.status)" style="display:inline-flex;align-items:center;gap:4px">{{ statusLabel(r.status) }}</span></td>
              <td>
                <button class="btn-icon btn-icon-sm" data-tooltip="Details" style="display:inline-flex;align-items:center;justify-content:center" @click="openDetail(r)"><AegisIcon name="eye" :size="14" /></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AegisPagination
        v-if="filteredAll.length > pageSize"
        :total="filteredAll.length"
        :per-page="pageSize"
        :current-page="allPage"
        @update:current-page="allPage = $event"
      />
    </div>

    <!-- ══ TAB: ARCHIVE ══ -->
    <div v-show="tab==='archive'">
      <div class="alert alert-info" style="margin-bottom:18px;box-shadow:var(--shadow-sm)">
        <div class="alert-icon"><AegisIcon name="archive" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">Auto-Archive Policy</div>
          <div>Referrals older than 5 business days are automatically archived regardless of status — unanswered, expired, completed, or declined.</div>
        </div>
      </div>

      <!-- Archive Filters -->
      <div class="filter-bar">
        <div class="filter-search">
          <span class="filter-search-icon"><AegisIcon name="search" :size="14" /></span>
          <input v-model="filterArchiveSearch" class="form-input" type="text" placeholder="Search archived referrals..." />
        </div>
        <select v-model="filterArchiveType" class="form-select" style="height:36px;font-size:12px;width:130px">
          <option value="">All Types</option>
          <option value="received">Received</option>
          <option value="sent">Sent</option>
        </select>
        <select v-model="filterArchiveReason" class="form-select" style="height:36px;font-size:12px;width:180px">
          <option value="">All Archive Reasons</option>
          <option value="expired">Unanswered / Expired</option>
          <option value="closed">Completed</option>
          <option value="declined">Declined</option>
        </select>
      </div>

      <!-- Archive Stats -->
      <div class="stat-chips-row" style="margin-bottom:20px">
        <div class="stat-chip">
          <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="archive" :size="18" /></div>
          <div><div class="stat-chip-value">{{ archivedReferrals.length }}</div><div class="stat-chip-label">Total archived</div></div>
        </div>
        <div class="stat-chip">
          <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="clock" :size="18" /></div>
          <div><div class="stat-chip-value">{{ archivedCounts.expired }}</div><div class="stat-chip-label">Unanswered / Expired</div></div>
        </div>
        <div class="stat-chip">
          <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="check" :size="18" /></div>
          <div><div class="stat-chip-value">{{ archivedCounts.completed }}</div><div class="stat-chip-label">Completed &amp; Archived</div></div>
        </div>
        <div class="stat-chip">
          <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="x" :size="18" /></div>
          <div><div class="stat-chip-value">{{ archivedCounts.declined }}</div><div class="stat-chip-label">Declined &amp; Archived</div></div>
        </div>
      </div>

      <!-- Archive Table -->
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Type</th>
              <th>Client</th>
              <th>Provider</th>
              <th>Date</th>
              <th>Archive Reason</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!pagedArchive.length">
              <td colspan="6" style="text-align:center;padding:32px;color:var(--text-3)">Archive is empty.</td>
            </tr>
            <tr v-for="r in pagedArchive" :key="r.id" :style="`opacity:${val(r.status)==='closed' ? '0.75' : '0.82'}`">
              <td>
                <span v-if="r.direction==='received'" class="badge badge-blue" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="phone" :size="9" />Rcvd</span>
                <span v-else class="badge badge-green" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="send" :size="9" />Sent</span>
              </td>
              <td><strong>{{ r.client_initials }}.</strong> <span v-if="r.client_age_band">· {{ r.client_age_band }}</span></td>
              <td>
                <a v-if="r.counterpart_slug" :href="route('public.provider', { slug: r.counterpart_slug })" target="_blank" style="color:inherit;font-weight:700;text-decoration:underline">{{ r.counterpart_name }}</a>
                <span v-else>{{ r.counterpart_name }}</span>
              </td>
              <td>{{ fmtDate(r.created_at) }}</td>
              <td>
                <span v-if="!r.responded_at && val(r.status) !== 'declined'" class="badge badge-red" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="clock" :size="10" />Unanswered · SLA Expired</span>
                <span v-else-if="val(r.status)==='closed'" class="badge badge-green" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="check" :size="10" />Completed</span>
                <span v-else-if="val(r.status)==='declined'" class="badge badge-red" style="display:inline-flex;align-items:center;gap:4px"><AegisIcon name="x" :size="10" />Declined</span>
                <span v-else class="badge badge-gray">Archived</span>
              </td>
              <td style="white-space:nowrap">
                <div style="display:flex;gap:4px">
                  <button class="btn-icon btn-icon-sm" data-tooltip="View" style="display:inline-flex;align-items:center;justify-content:center" @click="openArchiveDetail(r)"><AegisIcon name="eye" :size="14" /></button>
                  <button v-if="!r.responded_at || val(r.status)==='declined'" class="btn-icon btn-icon-sm" data-tooltip="Re-refer" style="display:inline-flex;align-items:center;justify-content:center" @click="openModal('referralModal')"><AegisIcon name="refresh-cw" :size="12" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AegisPagination
        v-if="filteredArchive.length > pageSize"
        :total="filteredArchive.length"
        :per-page="pageSize"
        :current-page="archivePage"
        @update:current-page="archivePage = $event"
      />
    </div>

    <!-- ══════════════ MODALS ══════════════ -->

    <!--
      ReferralModal listens to ui.activeModal via its own isOpen() composable.
      Calling openModal('referralModal') here sets ui.activeModal which the
      child reads reactively. No extra wiring needed.
    -->
    <ReferralModal :roster="roster" :network="network" />

    <!-- REFERRAL DETAIL -->
    <AegisModal
      v-model="modals.detail"
      :title="activeDetail ? 'Referral Details — ' + activeDetail.client_initials + '.' : 'Referral Details'"
      size="lg"
    >
      <template v-if="activeDetail">

        <!-- Direction strip -->
        <div class="rd-direction-strip">
          <div class="rd-direction-pill" :class="activeDetail.direction === 'received' ? 'is-incoming' : 'is-outgoing'">
            <AegisIcon :name="activeDetail.direction === 'received' ? 'phone-incoming' : 'send'" :size="12" />
            <span>{{ activeDetail.direction === 'received' ? 'Incoming referral' : 'Outgoing referral' }}</span>
          </div>
          <div class="rd-counterpart">
            <AegisIcon name="user" :size="12" />
            <span>{{ activeDetail.direction === 'received' ? 'From' : 'To' }}&nbsp;</span>
            <a
              v-if="activeDetail.counterpart_slug"
              :href="route('public.provider', { slug: activeDetail.counterpart_slug })"
              target="_blank"
              class="rd-counterpart-link"
            >{{ activeDetail.counterpart_name }}{{ activeDetail.counterpart_credentials ? ', ' + activeDetail.counterpart_credentials : '' }} ↗</a>
            <strong v-else>{{ activeDetail.counterpart_name }}</strong>
            <span class="rd-dot">·</span>
            <span class="rd-date">{{ fmtDate(activeDetail.created_at) }}</span>
          </div>
        </div>

        <!-- Row 1: Client + Referral info -->
        <div class="grid-2" style="gap:14px;margin-top:14px;margin-bottom:10px">
          <div>
            <div class="form-label" style="margin-bottom:6px">Client Information</div>
            <div class="detail-rows">
              <div class="detail-row"><span>Identifier</span><strong>{{ activeDetail.client_initials }}.</strong></div>
              <div v-if="activeDetail.client_age_band" class="detail-row"><span>Age Band</span><strong>{{ activeDetail.client_age_band }}</strong></div>
            </div>
          </div>
          <div>
            <div class="form-label" style="margin-bottom:6px">Referral Information</div>
            <div class="detail-rows">
              <div class="detail-row">
                <span>Urgency</span>
                <strong :class="'rd-urgency-' + (activeDetail.urgency ?? 'routine')">{{ urgencyLabel(activeDetail.urgency) }}</strong>
              </div>
              <div v-if="activeDetail.reason" class="detail-row"><span>Reason</span><strong>{{ activeDetail.reason }}</strong></div>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="activeDetail.notes" class="rd-notes">
          <div class="form-label" style="margin-bottom:6px">Notes from sender</div>
          <div class="rd-notes-body">{{ activeDetail.notes }}</div>
        </div>

        <!-- Response notes (pending incoming only) -->
        <div v-if="activeDetail.direction === 'received' && val(activeDetail.status) === 'sent'" class="form-group" style="margin-bottom:0">
          <label class="form-label" style="margin-bottom:4px">Your Response Notes <span style="font-weight:400;color:var(--text-4)">(optional)</span></label>
          <textarea v-model="acceptForm.notes" class="form-textarea" style="min-height:64px;font-size:13px" placeholder="Add scheduling notes or questions before accepting…"></textarea>
        </div>

      </template>

      <template #footer>
        <template v-if="activeDetail && activeDetail.direction === 'received' && val(activeDetail.status) === 'sent'">
          <button class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px" @click="modals.detail=false;openDecline(activeDetail)"><AegisIcon name="x" :size="14" />Decline</button>
          <button class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px" :disabled="msgLoading === activeDetail?.counterpart_user_id" @click="openConversation(activeDetail?.counterpart_user_id)"><AegisIcon name="message" :size="14" />Message</button>
          <button class="btn btn-success" style="display:inline-flex;align-items:center;gap:6px" @click="modals.detail=false;openAccept(activeDetail)"><AegisIcon name="check" :size="14" />Accept Referral</button>
        </template>
        <button v-else class="btn btn-outline" @click="modals.detail=false">Close</button>
      </template>
    </AegisModal>

    <!-- DECLINE -->
    <AegisModal v-model="modals.decline" title="Decline Referral" size="sm">
      <div class="alert alert-danger" style="margin-bottom:16px;box-shadow:var(--shadow-sm)">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="16" /></div>
        <div class="alert-content">Declining will notify the referring provider immediately. This action cannot be undone.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Reason for Declining *</label>
        <select v-model="declineForm.reason" class="form-select">
          <option value="">Select a reason...</option>
          <option>Not accepting new clients</option>
          <option>Specialty mismatch</option>
          <option>Insurance not accepted</option>
          <option>Capacity / scheduling unavailable</option>
          <option>Outside my scope of practice</option>
          <option>Client preference mismatch</option>
          <option>Other</option>
        </select>
        <div v-if="declineForm.errors.reason" class="form-error">{{ declineForm.errors.reason }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Message to Referring Provider (optional)</label>
        <textarea v-model="declineForm.message" class="form-textarea" placeholder="Explain why and suggest alternatives if possible..."></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" :disabled="declineForm.processing" @click="modals.decline=false">Cancel</button>
        <button class="btn btn-danger" :disabled="declineForm.processing || !declineForm.reason" style="display:inline-flex;align-items:center;gap:5px" @click="submitDecline">
          <AegisIcon name="x" :size="12" />
          Decline Referral
        </button>
      </template>
    </AegisModal>

    <!-- ACCEPT -->
    <AegisModal v-model="modals.accept" title="Accept Referral" size="sm">
      <p style="font-size:13px;color:var(--text-2);margin-bottom:14px">You are accepting this referral. The referring provider will be notified immediately.</p>
      <div class="form-group">
        <label class="form-label">Earliest Available Appointment</label>
        <input v-model="acceptForm.earliest_date" class="form-input" type="date" />
      </div>
      <div class="form-group">
        <label class="form-label">Notes to Referring Provider (optional)</label>
        <textarea v-model="acceptForm.notes" class="form-textarea" rows="3" placeholder="e.g., Will schedule within 2 weeks, please share recent labs..."></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" :disabled="acceptForm.processing" @click="modals.accept=false">Cancel</button>
        <button class="btn btn-success" :disabled="acceptForm.processing" style="display:inline-flex;align-items:center;gap:5px" @click="submitAccept">
          <AegisIcon name="check" :size="13" />
          Confirm Accept
        </button>
      </template>
    </AegisModal>

    <!-- CANCEL (sent referral) -->
    <AegisModal v-model="modals.cancel" title="Cancel Referral" size="sm">
      <p style="font-size:13px;color:var(--text-2);margin-bottom:14px">Are you sure you want to cancel this referral? The receiving provider will be notified and the referral will be moved to archive.</p>
      <div class="form-group">
        <label class="form-label">Reason for Cancellation <span style="color:var(--red-dark)">*</span></label>
        <select v-model="cancelForm.reason" class="form-select">
          <option value="">Select reason…</option>
          <option>Client no longer needs referral</option>
          <option>Client declined referral</option>
          <option>Referring to a different provider</option>
          <option>Client moved or transferred</option>
          <option>Sent in error</option>
          <option>Other</option>
        </select>
        <div v-if="cancelForm.errors.reason" class="form-error">{{ cancelForm.errors.reason }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Additional Notes (optional)</label>
        <textarea v-model="cancelForm.notes" class="form-textarea" rows="2" placeholder="Any context for the receiving provider..."></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" :disabled="cancelForm.processing" @click="modals.cancel=false">Keep Referral</button>
        <button class="btn btn-danger" :disabled="cancelForm.processing || !cancelForm.reason" style="display:inline-flex;align-items:center;gap:5px" @click="submitCancel">
          <AegisIcon name="x" :size="13" />
          Cancel Referral
        </button>
      </template>
    </AegisModal>

    <!-- MARK COMPLETE -->
    <AegisModal v-model="modals.complete" title="Mark Referral Complete" size="sm">
      <p style="font-size:13px;color:var(--text-2);margin-bottom:14px">Confirm this referral has been successfully completed. This will update your records and notify the referring provider.</p>
      <div class="form-group">
        <label class="form-label">Completion Date</label>
        <input v-model="completeForm.completed_date" class="form-input" type="date" />
      </div>
      <div class="form-group">
        <label class="form-label">Outcome Notes (optional)</label>
        <textarea v-model="completeForm.notes" class="form-textarea" rows="3" placeholder="e.g., Client seen, treatment plan initiated, follow-up in 4 weeks..."></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" :disabled="completeForm.processing" @click="modals.complete=false">Cancel</button>
        <button class="btn btn-success" :disabled="completeForm.processing" style="display:inline-flex;align-items:center;gap:5px" @click="submitComplete">
          <AegisIcon name="check" :size="13" />
          Mark Complete
        </button>
      </template>
    </AegisModal>

    <!-- ARCHIVE DETAIL -->
    <AegisModal
      v-model="modals.archiveDetail"
      :title="activeArchive ? 'Archived Referral — ' + activeArchive.client_initials + '.' : 'Archived Referral'"
      size="lg"
    >
      <template v-if="activeArchive">

        <!-- Status strip -->
        <div class="rd-direction-strip">
          <div class="rd-direction-pill" :class="activeArchive.direction === 'received' ? 'is-incoming' : 'is-outgoing'">
            <AegisIcon :name="activeArchive.direction === 'received' ? 'phone-incoming' : 'send'" :size="12" />
            <span>{{ activeArchive.direction === 'received' ? 'Incoming' : 'Outgoing' }}</span>
          </div>
          <div class="rd-counterpart">
            <AegisIcon name="archive" :size="12" />
            <span>Archived&nbsp;<strong>{{ fmtDate(activeArchive.closed_at || activeArchive.responded_at || activeArchive.created_at) }}</strong></span>
            <span class="rd-dot">·</span>
            <span
              :class="!activeArchive.responded_at || val(activeArchive.status) === 'declined' ? 'rd-status-declined' : 'rd-status-ok'"
            >{{ !activeArchive.responded_at ? 'Unanswered · SLA Expired' : statusLabel(activeArchive.status) }}</span>
          </div>
        </div>

        <div class="rd-archive-body">

          <!-- Top row: Client + Referral side by side -->
          <div class="grid-2" style="gap:14px;margin-top:14px">
            <div>
              <div class="form-label" style="margin-bottom:6px">Client Information</div>
              <div class="detail-rows">
                <div class="detail-row"><span>Identifier</span><strong>{{ activeArchive.client_initials }}.</strong></div>
                <div v-if="activeArchive.client_age_band" class="detail-row"><span>Age Band</span><strong>{{ activeArchive.client_age_band }}</strong></div>
              </div>
            </div>
            <div>
              <div class="form-label" style="margin-bottom:6px">Referral Information</div>
              <div class="detail-rows">
                <div class="detail-row">
                  <span>{{ activeArchive.direction === 'received' ? 'From' : 'To' }}</span>
                  <a v-if="activeArchive.counterpart_slug" :href="route('public.provider', { slug: activeArchive.counterpart_slug })" target="_blank" class="rd-counterpart-link">{{ activeArchive.counterpart_name }} ↗</a>
                  <strong v-else>{{ activeArchive.counterpart_name }}</strong>
                </div>
                <div v-if="activeArchive.urgency" class="detail-row">
                  <span>Urgency</span>
                  <strong :class="'rd-urgency-' + (activeArchive.urgency ?? 'routine')">{{ urgencyLabel(activeArchive.urgency) }}</strong>
                </div>
                <div class="detail-row">
                  <span>Status at archive</span>
                  <strong class="rd-status-declined">{{ statusLabel(activeArchive.status) }}</strong>
                </div>
              </div>
            </div>
          </div>

          <!-- Timeline — full width below -->
          <div class="rd-archive-timeline-wrap">
            <div class="form-label" style="margin-bottom:8px">Archive Timeline</div>
            <div class="rd-timeline rd-timeline-horizontal">
              <div class="rd-tl-step">
                <div class="rd-tl-step-icon rd-tl-icon-gold">
                  <AegisIcon name="send" :size="13" />
                </div>
                <div class="rd-tl-step-label">{{ activeArchive.direction === 'received' ? 'Received' : 'Sent' }}</div>
                <div class="rd-tl-step-date">{{ fmtDate(activeArchive.created_at) }}</div>
              </div>
              <div class="rd-tl-connector" :class="activeArchive.responded_at ? 'is-done' : 'is-pending'"></div>
              <div class="rd-tl-step">
                <div class="rd-tl-step-icon" :class="!activeArchive.responded_at ? 'rd-tl-icon-muted' : val(activeArchive.status) === 'accepted' ? 'rd-tl-icon-green' : 'rd-tl-icon-red'">
                  <AegisIcon :name="!activeArchive.responded_at ? 'clock' : val(activeArchive.status) === 'accepted' ? 'check' : 'x'" :size="13" />
                </div>
                <div class="rd-tl-step-label">{{ !activeArchive.responded_at ? 'No response' : val(activeArchive.status) === 'accepted' ? 'Accepted' : 'Declined' }}</div>
                <div class="rd-tl-step-date">{{ activeArchive.responded_at ? fmtDate(activeArchive.responded_at) : '—' }}</div>
              </div>
              <div class="rd-tl-connector is-done"></div>
              <div class="rd-tl-step">
                <div class="rd-tl-step-icon rd-tl-icon-muted">
                  <AegisIcon name="archive" :size="13" />
                </div>
                <div class="rd-tl-step-label">Auto-archived</div>
                <div class="rd-tl-step-date">{{ fmtDate(activeArchive.closed_at || activeArchive.created_at) }}</div>
              </div>
            </div>
          </div>

        </div>

      </template>

      <template #footer>
        <button class="btn btn-outline" @click="modals.archiveDetail=false">Close</button>
        <button
          v-if="activeArchive && (val(activeArchive.status) === 'declined' || !activeArchive.responded_at)"
          class="btn btn-primary"
          style="display:inline-flex;align-items:center;gap:6px"
          @click="modals.archiveDetail=false;openModal('referralModal')"
        >
          <AegisIcon name="refresh-cw" :size="14" />Re-refer
        </button>
      </template>
    </AegisModal>

  </AppLayout>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisPagination from '@/components/ui/AegisPagination.vue'
import ReferralModal from '@/components/modals/ReferralModal.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { useMessageButton } from '@/composables/useMessageButton'

// ── Props ──────────────────────────────────────────────────────────────
const props = defineProps({
  incomingReferrals:   { type: Array, default: () => [] },
  sentReferrals:      { type: Array, default: () => [] },
  completedReferrals: { type: Array, default: () => [] },
  allReferrals:       { type: Array, default: () => [] },
  archivedReferrals:  { type: Array, default: () => [] },
  refStats:           { type: Object, default: () => ({ pending: 0, sent_active: 0, completed_month: 0, accept_rate: 0 }) },
  archivedCounts:     { type: Object, default: () => ({ expired: 0, completed: 0, declined: 0 }) },
  roster:             { type: Array, default: () => [] },
  network:            { type: Array, default: () => [] },
})

// ── Composables ────────────────────────────────────────────────────────
const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()
const { openConversation, loading: msgLoading } = useMessageButton()

// ── Enum unwrapper ─────────────────────────────────────────────────────
const val = (v) => (v && typeof v === 'object' && 'value' in v) ? v.value : (v ?? '')

// ── Tab ────────────────────────────────────────────────────────────────
const tab = ref('pending')

// ── Modals state ───────────────────────────────────────────────────────
const modals = reactive({
  detail:        false,
  decline:       false,
  accept:        false,
  cancel:        false,
  complete:      false,
  archiveDetail: false,
})

// ── Active record IDs (never full objects in refs) ─────────────────────
const _detailId        = ref(null)
const _archiveDetailId = ref(null)

const activeDetail  = computed(() => props.allReferrals.find((r) => r.id === _detailId.value) ?? null)
const activeArchive = computed(() => props.archivedReferrals.find((r) => r.id === _archiveDetailId.value) ?? null)

// ── Forms ──────────────────────────────────────────────────────────────
const declineForm  = useForm({ reason: '', message: '' })
const acceptForm   = useForm({ earliest_date: '', notes: '' })
const cancelForm   = useForm({ reason: '', notes: '' })
const completeForm = useForm({ completed_date: '', notes: '' })

// ── Urgency pending ────────────────────────────────────────────────────
const urgentPending = computed(() => props.incomingReferrals.filter((r) => val(r.urgency) === 'urgent'))

// ── Pagination ─────────────────────────────────────────────────────────
const pageSize      = 5
const pendingPage   = ref(1)
const sentPage      = ref(1)
const completedPage = ref(1)
const allPage       = ref(1)
const archivePage   = ref(1)

// ── Filters ────────────────────────────────────────────────────────────
const filterPendingUrgency = ref('')
const filterSentStatus     = ref('')
const filterAllSearch      = ref('')
const filterAllType        = ref('')
const filterAllStatus      = ref('')
const filterAllUrgency     = ref('')
const filterArchiveSearch  = ref('')
const filterArchiveType    = ref('')
const filterArchiveReason  = ref('')

// Reset pages on filter change
watch(filterPendingUrgency, () => { pendingPage.value = 1 })
watch([filterSentStatus],   () => { sentPage.value = 1 })
watch([filterAllSearch, filterAllType, filterAllStatus, filterAllUrgency], () => { allPage.value = 1 })
watch([filterArchiveSearch, filterArchiveType, filterArchiveReason], () => { archivePage.value = 1 })

// ── Filtered lists ─────────────────────────────────────────────────────
const filteredPending = computed(() => {
  let list = props.incomingReferrals
  if (filterPendingUrgency.value) list = list.filter((r) => val(r.urgency) === filterPendingUrgency.value)
  return list
})

const filteredSent = computed(() => {
  let list = props.sentReferrals
  if (filterSentStatus.value) list = list.filter((r) => val(r.status) === filterSentStatus.value)
  return list
})

const filteredAll = computed(() => {
  let list = props.allReferrals
  const q = filterAllSearch.value.trim().toLowerCase()
  if (q) list = list.filter((r) => `${r.client_initials} ${r.counterpart_name} ${r.reason ?? ''}`.toLowerCase().includes(q))
  if (filterAllType.value) list = list.filter((r) => r.direction === filterAllType.value)
  if (filterAllStatus.value) list = list.filter((r) => val(r.status) === filterAllStatus.value)
  if (filterAllUrgency.value) list = list.filter((r) => val(r.urgency) === filterAllUrgency.value)
  return list
})

const filteredArchive = computed(() => {
  let list = props.archivedReferrals
  const q = filterArchiveSearch.value.trim().toLowerCase()
  if (q) list = list.filter((r) => `${r.client_initials} ${r.counterpart_name} ${r.reason ?? ''}`.toLowerCase().includes(q))
  if (filterArchiveType.value) list = list.filter((r) => r.direction === filterArchiveType.value)
  if (filterArchiveReason.value === 'expired')  list = list.filter((r) => !r.responded_at && val(r.status) !== 'declined')
  if (filterArchiveReason.value === 'closed')   list = list.filter((r) => val(r.status) === 'closed')
  if (filterArchiveReason.value === 'declined') list = list.filter((r) => val(r.status) === 'declined')
  return list
})

// ── Paged slices ───────────────────────────────────────────────────────
const pagedPending   = computed(() => filteredPending.value.slice((pendingPage.value - 1) * pageSize, pendingPage.value * pageSize))
const pagedSent      = computed(() => filteredSent.value.slice((sentPage.value - 1) * pageSize, sentPage.value * pageSize))
const pagedCompleted = computed(() => props.completedReferrals.slice((completedPage.value - 1) * pageSize, completedPage.value * pageSize))
const pagedAll       = computed(() => filteredAll.value.slice((allPage.value - 1) * pageSize, allPage.value * pageSize))
const pagedArchive   = computed(() => filteredArchive.value.slice((archivePage.value - 1) * pageSize, archivePage.value * pageSize))

// ── Sort (All tab) ─────────────────────────────────────────────────────
const allSortKey = ref('created_at')
const allSortDir = ref(-1)

function sortAll(key) {
  if (allSortKey.value === key) allSortDir.value *= -1
  else { allSortKey.value = key; allSortDir.value = -1 }
}

// ── Modal openers ──────────────────────────────────────────────────────
function openDetail(r) {
  _detailId.value = r.id
  modals.detail = true
}
function openDecline(r) {
  _detailId.value = r.id
  declineForm.reset()
  modals.decline = true
}
function openAccept(r) {
  _detailId.value = r.id
  acceptForm.reset()
  modals.accept = true
}
function openCancel(r) {
  _detailId.value = r.id
  cancelForm.reset()
  modals.cancel = true
}
function openComplete(r) {
  _detailId.value = r.id
  completeForm.reset()
  modals.complete = true
}
function openArchiveDetail(r) {
  _archiveDetailId.value = r.id
  modals.archiveDetail = true
}

// ── Submit actions ─────────────────────────────────────────────────────
function submitDecline() {
  if (!_detailId.value) return
  declineForm.post(route('provider.referrals.decline', { referral: _detailId.value }), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Referral declined — referring provider notified.')
      modals.decline = false
      declineForm.reset()
    },
    onError: () => toast.error('Could not decline referral.'),
  })
}

function submitAccept() {
  if (!_detailId.value) return
  acceptForm.post(route('provider.referrals.accept', { referral: _detailId.value }), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Referral accepted — referring provider notified.')
      modals.accept = false
      acceptForm.reset()
    },
    onError: () => toast.error('Could not accept referral.'),
  })
}

function submitCancel() {
  if (!_detailId.value) return
  cancelForm.post(route('provider.referrals.cancel', { referral: _detailId.value }), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Referral cancelled — moved to archive.')
      modals.cancel = false
      cancelForm.reset()
    },
    onError: () => toast.error('Could not cancel referral.'),
  })
}

function submitComplete() {
  if (!_detailId.value) return
  completeForm.post(route('provider.referrals.complete', { referral: _detailId.value }), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Referral marked complete.')
      modals.complete = false
      completeForm.reset()
    },
    onError: () => toast.error('Could not complete referral.'),
  })
}

// ── Helpers ────────────────────────────────────────────────────────────
function fmtDate(iso) {
  if (!iso) return '—'
  const d = new Date(String(iso).slice(0, 10))
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function timeAgo(iso) {
  if (!iso) return ''
  const diff = Date.now() - new Date(iso).getTime()
  const days = Math.floor(diff / 86400000)
  if (days === 0) return 'Today'
  if (days === 1) return '1 day ago'
  if (days < 7)  return `${days} days ago`
  const weeks = Math.floor(days / 7)
  return weeks === 1 ? '1 week ago' : `${weeks} weeks ago`
}

function urgencyLabel(u) {
  const map = { urgent: 'Urgent', soon: 'Soon', routine: 'Routine' }
  return map[val(u)] ?? val(u) ?? 'Routine'
}

function urgencyBadgeClass(u) {
  const v = val(u)
  if (v === 'urgent') return 'badge-red'
  if (v === 'soon')   return 'badge-orange'
  return 'badge-gray'
}

function statusLabel(s) {
  const map = { sent: 'Pending', accepted: 'Accepted', declined: 'Declined', closed: 'Completed', cancelled: 'Cancelled' }
  return map[val(s)] ?? val(s) ?? '—'
}

function statusBadgeClass(s) {
  const map = { sent: 'badge-orange', accepted: 'badge-green', declined: 'badge-red', closed: 'badge-gray', cancelled: 'badge-gray' }
  return map[val(s)] ?? 'badge-gray'
}

function rfcCardClass(r) {
  const u = val(r.urgency)
  if (u === 'urgent') return 'rfc-urgent'
  if (u === 'soon')   return 'rfc-soon'
  return ''
}
</script>

<style scoped>
/* ── pending-filters: force TomSelect wrapper to match select width ── */
.pending-filters :deep(.ts-wrapper) { width: 169px !important; }

/* ── Referral Detail Modal ───────────────────────────────────────────── */
.rd-direction-strip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 14px;
  background: var(--surface-2);
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  margin-bottom: 2px;
  flex-wrap: wrap;
}
.rd-direction-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.4px;
  padding: 3px 9px;
  border-radius: var(--radius-full);
}
.rd-direction-pill.is-incoming { background: var(--blue-light); color: var(--blue-dark); }
.rd-direction-pill.is-outgoing { background: var(--green-light); color: var(--green-dark); }
.rd-counterpart {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: var(--text-2);
}
.rd-counterpart-link {
  color: var(--blue-dark);
  font-weight: 600;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.rd-counterpart-link:hover { color: var(--text); }
.rd-dot { color: var(--text-4); }
.rd-date { color: var(--text-3); }
.detail-rows { display: flex; flex-direction: column; gap: 2px; }
.rd-urgency-urgent { color: var(--red-dark); }
.rd-urgency-soon   { color: var(--orange-dark); }
.rd-urgency-routine { color: var(--text); }
.rd-notes {
  margin-bottom: 14px;
}
.rd-notes-body {
  background: var(--surface-2);
  border-left: 3px solid var(--blue-dark);
  border-radius: var(--radius-sm);
  padding: 10px 13px;
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.6;
}

.rd-archive-body { display: flex; flex-direction: column; gap: 18px; }
.rd-archive-timeline-wrap { margin-top: 4px; }
.rd-timeline-horizontal {
  display: flex;
  align-items: flex-start;
  gap: 0;
  border: none;
  border-radius: 0;
  overflow: visible;
  padding: 4px 0 0;
}
.rd-tl-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  flex: 0 0 auto;
  min-width: 80px;
  text-align: center;
}
.rd-tl-step-icon {
  width: 34px;
  height: 34px;
  border-radius: var(--radius-full);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--border);
  background: var(--surface);
  flex-shrink: 0;
}
.rd-tl-step-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--text-2);
  white-space: nowrap;
}
.rd-tl-step-date {
  font-size: 10px;
  color: var(--text-4);
  white-space: nowrap;
}
.rd-tl-connector {
  flex: 1;
  height: 2px;
  margin-top: 16px;
  background: var(--border);
  min-width: 20px;
}
.rd-tl-connector.is-done { background: var(--gold-dark); }
.rd-tl-connector.is-pending { background: var(--border); }
.rd-tl-icon-gold  { color: var(--gold-dark);  border-color: var(--gold-dark);  background: rgba(196,169,106,0.08); }
.rd-tl-icon-green { color: var(--green-dark); border-color: var(--green-dark); background: var(--green-light); }
.rd-tl-icon-red   { color: var(--red-dark);   border-color: var(--red-dark);   background: var(--red-light); }
.rd-tl-icon-muted { color: var(--text-4);     border-color: var(--border);     background: var(--surface-2); }



/* ── rfc-card overrides ── */
.rfc-card::after      { display: none; }
.rfc-card > *         { position: relative; z-index: auto; }
.rfc-card::before     { border-radius: 0; box-shadow: none; }

/* Hero center fix */
.hero-banner.is-quiet .page-hero-inner   { align-items: center; }
.hero-banner.is-quiet .page-hero-actions { align-self: flex-start; }

/* SLA alert */
.sla-alert {
  background: var(--surface);
  border: 1px solid var(--border);
  border-left: 3px solid var(--red-dark);
  border-radius: var(--radius-lg);
  padding: 12px 18px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  margin-bottom: 18px;
  box-shadow: var(--shadow-sm);
}
.sla-alert-left {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  font-weight: 600;
  color: var(--red-dark);
}

/* Filter bar */
.filter-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
.filter-search { flex: 1; min-width: 200px; position: relative; }
.filter-search input { width: 100%; padding-left: 34px; }
.filter-search-icon {
  position: absolute; left: 11px; top: 50%;
  transform: translateY(-50%);
  color: var(--text-4);
  pointer-events: none;
  display: flex;
}

/* Table wrap */
.table-wrap {
  overflow-x: auto;
  border-radius: var(--radius);
  border: 1px solid var(--border-dark);
  box-shadow: var(--shadow);
  background: var(--surface);
}
.sortable   { cursor: pointer; user-select: none; }
.sortable:hover { color: var(--gold-dark); }

/* Table header */
.table-wrap .table thead tr { background: var(--surface-3); }
.table-wrap .table thead th {
  color: var(--text);
  font-weight: 700;
  font-size: 11px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  border-bottom: 1px solid var(--border-dark);
  padding: 11px 14px;
}
.table-wrap .table tbody tr                   { background: var(--surface); }
.table-wrap .table tbody tr:hover             { background: var(--badge-bg-gold); }
.table-wrap .table tbody td                   { border-bottom: 1px solid var(--border); color: var(--text); }
.table-wrap .table tbody tr:last-child td     { border-bottom: none; }

/* Detail row */
.detail-row {
  display: flex;
  justify-content: space-between;
  font-size: 12.5px;
  padding: 2px 0;
  border-bottom: 1px solid var(--border);
}
.detail-row:last-child { border-bottom: none; }

/* Tab top row */
.tab-top-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
  flex-wrap: wrap;
  gap: 10px;
}
.tab-top-info { font-size: 13px; color: var(--text-3); }

/* Timeline */
.timeline-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-bottom: 1px solid var(--border);
  font-size: 12.5px;
}
.timeline-row:last-child { border-bottom: none; }
.timeline-time { font-size: 11px; color: var(--text-4); white-space: nowrap; }

@media print {
  .sidebar, .topbar, .modal-overlay, .sla-alert, .rfc-foot { display: none !important; }
  .main-content { margin-left: 0 !important; }
}
</style>
