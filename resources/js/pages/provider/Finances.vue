<!--
  pages/provider/Finances.vue — Practitioner finances hub.

  All 4 payment flows:
    1. Aegis subscription     (Provider → Aegis, Cashier)
    2. Business Partners      (Provider → BP, Stripe Connect)
    3. Continuity Stewards    (Provider → CS, Stripe Connect)
    4. Clinical Sessions      (Provider → other Provider, Stripe Connect)

  Tabs: Overview · CS Wallet · Business Partners · Clinical Sessions ·
         Subscription · Payment Methods · Transactions
-->
<template>
  <AppLayout :user="$page.props.auth.user" portal="practitioner" activePage="finances" pageTitle="Finances">

    <!-- ══ HERO ══ -->
    <AegisHeroBanner
      eyebrow="Provider Portal"
      title="Aegis Finances"
      subtitle="Your subscription, all peer payments, and financial history in one place."
      quiet
    >
      <template #actions>
        <a :href="route('provider.activity') + '?event_type=payment'"
           class="btn-hero-ghost is-on-light" data-tooltip="Payment activity log">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
        <button type="button" class="btn-hero-ghost is-on-light" @click="modals.export = true">
          <AegisIcon name="download" :size="14" /> Export Report
        </button>
        <button type="button" class="btn-hero-solid is-on-light" @click="modals.addPayment = true">
          <AegisIcon name="plus" :size="14" /> Add Payment Method
        </button>
      </template>
    </AegisHeroBanner>

    <!-- ══ STAT CHIPS with dynamic tooltips ══ -->
    <div class="stat-chips-row">
      <AegisStatChip
        icon="dollar"
        :value="formatMoney(totalSpendCents / 100)"
        label="Total Spend"
        bg-color="var(--badge-bg-gold)"
        icon-color="var(--gold-dark)"
      />
      <AegisStatChip
        icon="alert-triangle"
        :value="formatMoney(pendingInvoiceTotal / 100)"
        :label="pendingInvoiceCount + ' Pending'"
        :tooltip="pendingBreakdownTooltip"
        bg-color="var(--badge-bg-gold)"
        icon-color="var(--gold-dark)"
      />
      <AegisStatChip
        icon="file-text"
        :value="activeContractCount"
        label="Active Contracts"
        :tooltip="activeContractsBreakdownTooltip"
        bg-color="var(--badge-bg-gold)"
        icon-color="var(--gold-dark)"
      />
      <AegisStatChip
        icon="star"
        :value="subscription?.tier || 'None'"
        label="Subscription"
        bg-color="var(--badge-bg-gold)"
        icon-color="var(--gold-dark)"
      />
    </div>

    <!-- ══ PENDING INVOICE ALERT ══ -->
    <div v-if="pendingInvoiceCount > 0" class="alert alert-warning" style="margin-bottom:12px;">
      <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
      <div class="alert-content">
        <div class="alert-title">Payments Awaiting Your Approval</div>
        <div>
          {{ firstPendingName }} — {{ firstPendingAmount }}
          <template v-if="pendingInvoiceCount > 1"> and {{ pendingInvoiceCount - 1 }} more</template>
        </div>
        <div style="margin-top:10px;">
          <button type="button" class="btn btn-primary" @click="goToPendingTab">Review Invoices</button>
        </div>
      </div>
    </div>

    <!-- ══ TABS ══ -->
    <!-- ══ FINANCES LAYOUT: LEFT SIDEBAR + CONTENT ══ -->
    <div class="fin-layout">

      <!-- LEFT SIDEBAR NAV — uses global .page-sidebar classes -->
      <nav class="page-sidebar" role="tablist" aria-label="Finance sections" style="width:220px;flex-shrink:0;">

        <div class="page-sidebar-group">
          <div class="page-sidebar-label">Activity</div>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'overview' }" @click="activeTab = 'overview'">
            <span class="page-sidebar-icon"><AegisIcon name="activity" :size="15" /></span>
            Overview
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'executor' }" @click="activeTab = 'executor'">
            <span class="page-sidebar-icon"><AegisIcon name="shield" :size="15" /></span>
            CS Wallet
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'bp' }" @click="activeTab = 'bp'">
            <span class="page-sidebar-icon"><AegisIcon name="file-text" :size="15" /></span>
            Business Partners
            <span v-if="bpPendingCount > 0" class="page-sidebar-badge">{{ bpPendingCount }}</span>
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'sessions' }" @click="activeTab = 'sessions'">
            <span class="page-sidebar-icon"><AegisIcon name="heart" :size="15" /></span>
            Clinical Sessions
            <span v-if="sessionPendingCount > 0" class="page-sidebar-badge">{{ sessionPendingCount }}</span>
          </button>
        </div>

        <div class="page-sidebar-group">
          <div class="page-sidebar-label">Manage</div>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'subscription' }" @click="activeTab = 'subscription'">
            <span class="page-sidebar-icon"><AegisIcon name="star" :size="15" /></span>
            Subscription
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'methods' }" @click="activeTab = 'methods'">
            <span class="page-sidebar-icon"><AegisIcon name="credit-card" :size="15" /></span>
            Payment Methods
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'history' }" @click="activeTab = 'history'">
            <span class="page-sidebar-icon"><AegisIcon name="clock" :size="15" /></span>
            Transactions
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'integrations' }" @click="activeTab = 'integrations'">
            <span class="page-sidebar-icon"><AegisIcon name="link" :size="15" /></span>
            Integrations
          </button>
        </div>

      </nav>

      <!-- CONTENT AREA -->
      <div class="fin-content">

    <!-- ══════════════════════════════ TAB: OVERVIEW ══════════════════════════════ -->
    <div v-show="activeTab === 'overview'">
      <div class="two-col">

        <!-- Spend Breakdown -->
        <div class="card">
          <div class="card-header">
            <div class="card-title fin-card-title">
              <span class="fin-card-icon"><AegisIcon name="activity" :size="15" /></span>
              Spend Breakdown
            </div>
          </div>
          <div class="card-body">
            <div class="spend-bd">
              <div class="spend-bd-total">
                <div class="spend-bd-total-val">{{ formatMoney(totalSpendCents / 100) }}</div>
                <div class="spend-bd-total-lbl">total spend to date</div>
              </div>

              <!-- Always show all 3 bars even at 0 -->
              <div class="spend-bd-bar" v-if="totalSpendCents > 0">
                <span
                  v-for="item in spendBreakdown.filter(i => i.pct > 0)" :key="item.label"
                  :style="{ background: item.color, width: item.pct + '%' }"
                ></span>
              </div>
              <div class="spend-bd-bar spend-bd-bar--empty" v-else></div>

              <div class="spend-bd-list">
                <div v-for="item in spendBreakdown" :key="item.label" class="spend-bd-row">
                  <span class="spend-bd-dot" :style="{ background: item.color }"></span>
                  <span class="spend-bd-name">{{ item.label }}</span>
                  <span class="spend-bd-amt">${{ item.amount.toLocaleString() }}</span>
                  <span class="spend-bd-pct">{{ item.pct }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Upcoming Payments — all 3 peer types -->
        <div class="card">
          <div class="card-header">
            <div class="card-title fin-card-title">
              <span class="fin-card-icon"><AegisIcon name="calendar" :size="15" /></span>
              Upcoming Payments
            </div>
            <AegisBadge :label="upcomingPayments.length + ' due'" variant="gold" />
          </div>
          <div class="card-body">
            <div v-if="upcomingPayments.length" class="upcoming-list">
              <div
                v-for="inv in upcomingPayments.slice(0, 6)" :key="inv.id"
                class="upcoming-row"
                @click="openViewInvoice(inv)"
              >
                <span class="upcoming-kind-badge" :class="'upcoming-kind-badge--' + inv.payment_type">
                  {{ paymentTypeLabel(inv.payment_type) }}
                </span>
                <div class="upcoming-info">
                  <div class="upcoming-name">{{ inv.recipient }}</div>
                  <div class="upcoming-desc">{{ inv.contract_title || inv.service_title || ('Invoice #' + inv.invoice_number) }}</div>
                </div>
                <div class="upcoming-right">
                  <div class="upcoming-amount" :class="{ 'upcoming-amount--urgent': inv.is_urgent }">
                    {{ formatCents(inv.total_cents) }}
                  </div>
                  <div class="upcoming-due" :class="{ 'upcoming-due--urgent': inv.is_urgent }">
                    {{ inv.due_month }} {{ inv.due_day }}
                  </div>
                </div>
              </div>
              <div v-if="upcomingPayments.length > 6" class="upcoming-more" @click="activeTab = 'bp'">
                +{{ upcomingPayments.length - 6 }} more · view all →
              </div>
            </div>
            <div v-else style="text-align:center;padding:20px;color:var(--text-3);font-size:13px;">
              No upcoming payments.
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Transactions -->
      <div class="card" style="margin-top:20px;">
        <div class="card-header">
          <div class="card-title fin-card-title">
            <span class="fin-card-icon"><AegisIcon name="file-text" :size="15" /></span>
            Recent Transactions
          </div>
          <button type="button" class="btn btn-outline" @click="activeTab = 'history'">
            <AegisIcon name="chevron-right" :size="12" /> View All
          </button>
        </div>
        <div class="card-body" style="padding:0;overflow-x:auto;">
          <table class="table fin-tx-table" style="margin:0;">
            <thead>
              <tr>
                <th>Date</th><th>Description</th><th>Category</th><th>Amount</th><th>Status</th><th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tx in recentTransactions.slice(0, 5)" :key="tx.id">
                <td class="tx-date">{{ tx.date }}</td>
                <td class="tx-desc">{{ tx.description }}</td>
                <td>
                  <span class="tx-cat-wrap">
                    <span class="tx-cat-dot" :style="{ background: tx.cat_color }"></span>
                    {{ tx.category_label }}
                  </span>
                </td>
                <td :class="tx.amount < 0 ? 'tx-amount-out' : 'tx-amount-in'">
                  {{ tx.amount < 0 ? '-' : '+' }}${{ Math.abs(tx.amount / 100).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                </td>
                <td><AegisBadge :label="tx.status" :variant="statusVariant(tx.status)" /></td>
                <td class="tx-action">
                  <button type="button" class="btn-icon btn-icon-sm" data-tooltip="View receipt" @click="openTxReceipt(tx)">
                    <AegisIcon name="eye" :size="12" />
                  </button>
                </td>
              </tr>
              <tr v-if="!recentTransactions.length">
                <td colspan="6" style="text-align:center;padding:24px;color:var(--text-3);font-size:13px;">
                  No transactions yet.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════ TAB: CS WALLET ══════════════════════════════ -->
    <div v-show="activeTab === 'executor'">
      <div class="stat-chips-row" style="margin-bottom:24px;">
        <AegisStatChip icon="shield" :value="formatMoney(csAgreedTotal / 100)" label="Agreed CS Fees"       bg-color="var(--badge-bg-gold)" icon-color="var(--gold-dark)" />
        <AegisStatChip icon="users"  :value="csStewards.length"                  label="Active Stewards"      bg-color="var(--badge-bg-gold)" icon-color="var(--gold-dark)" />
      </div>

      <div class="alert alert-info" style="margin-bottom:20px;">
        <div class="alert-icon"><AegisIcon name="shield" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">Direct Payments — Aegis Holds No Funds</div>
          <div>Continuity Steward fees are collected directly through your saved payment method via Stripe when a critical incident is verified. Aegis never holds, escrows, or processes the funds.</div>
        </div>
      </div>

      <AegisEmptyState
        v-if="!csStewards.length"
        icon="shield"
        title="No Continuity Stewards yet"
        description="Designate a Continuity Steward in your Continuity Plan to manage payment arrangements here."
      >
        <template #action>
          <a :href="route('provider.stewards.index')" class="btn btn-primary">Manage CS</a>
        </template>
      </AegisEmptyState>

      <div v-for="cs in csStewards" :key="cs.id" class="cspay-card">
        <div class="cspay-body">
          <div class="cspay-top">
            <a :href="profileHref(cs.slug, 'cs')" target="_blank" class="avatar avatar-md avatar-gold" data-tooltip="View profile" style="text-decoration:none;">
              {{ cs.initials }}
            </a>
            <div class="cspay-id">
              <div class="cspay-name-row">
                <a :href="profileHref(cs.slug, 'cs')" target="_blank" class="cspay-name">{{ cs.display_name }}</a>
                <AegisBadge :label="payTermsLabel(cs.payment_model)" variant="gold" />
                <span class="connect-pill" :class="cs.stripe_connected ? 'is-connected' : 'is-not-connected'">
                  <span class="status-dot"></span>
                  {{ cs.stripe_connected ? 'Stripe Connected' : 'Not Connected' }}
                </span>
              </div>
              <div class="cspay-role">{{ cs.role_label }}</div>
            </div>
            <div class="cspay-actions">
              <button type="button" class="btn-icon btn-icon-sm" data-tooltip="Payment history" @click="openPayArrangement(cs)">
                <AegisIcon name="clock" :size="13" />
              </button>
              <button type="button" class="btn-icon btn-icon-sm" data-tooltip="Update payment model" @click="openChangePayModel(cs)">
                <AegisIcon name="settings" :size="13" />
              </button>
              <button type="button" class="btn-icon btn-icon-sm btn-icon-danger" data-tooltip="Cancel agreement" @click="openCancelCs(cs)">
                <AegisIcon name="trash" :size="13" />
              </button>
            </div>
          </div>

          <div class="cspay-meta">
            <div class="cspay-meta-item">
              <div class="cspay-meta-label">Agreed Fee per Activation</div>
              <div class="cspay-meta-value amount">{{ formatCents(cs.fee_cents) }}</div>
            </div>
            <div class="cspay-meta-item">
              <div class="cspay-meta-label">Payment Terms</div>
              <div class="cspay-meta-value">{{ payTermsLabel(cs.payment_model) }}</div>
            </div>
            <div class="cspay-meta-item">
              <div class="cspay-meta-label">Auto-charge</div>
              <div class="cspay-meta-value">{{ cs.auto_charge ? 'Enabled' : 'Manual' }}</div>
            </div>
          </div>

          <div v-if="!cs.stripe_connected" class="cspay-note cspay-note--warning" style="margin-bottom:10px;">
            <span class="cspay-note-icon--warning"><AegisIcon name="alert-triangle" :size="16" /></span>
            <p>
              <strong>{{ cs.display_name }} hasn't finished Stripe Connect onboarding.</strong>
              Until they do, the agreed fee can't be routed to them automatically. Ask them to complete payment setup in their portal.
            </p>
          </div>

          <div class="cspay-note">
            <span class="cspay-note-icon"><AegisIcon name="shield" :size="16" /></span>
            <p>
              The agreed fee of <strong>{{ formatCents(cs.fee_cents) }}</strong> is charged directly to {{ cs.display_name }} through your saved Stripe payment authorization when a critical incident is verified. Aegis does not hold these funds.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════ TAB: BUSINESS PARTNERS ══════════════════════════════ -->
    <div v-show="activeTab === 'bp'">
      <div class="tabs-pill" style="margin-bottom:20px;display:inline-flex;">
          <button type="button" class="tab-pill" :class="{ active: bpFilter === 'all' }" @click="bpFilter = 'all'">
            All <span v-if="allBpItems > 0" class="badge-pill">{{ allBpItems }}</span>
          </button>
          <button type="button" class="tab-pill" :class="{ active: bpFilter === 'pending' }" @click="bpFilter = 'pending'">
            Pending <span v-if="bpInvoices.length > 0" class="badge-pill">{{ bpInvoices.length }}</span>
          </button>
          <button type="button" class="tab-pill" :class="{ active: bpFilter === 'active' }" @click="bpFilter = 'active'">
            Active <span v-if="activeContracts.length > 0" class="badge-pill">{{ activeContracts.length }}</span>
          </button>
        </div>

      <AegisEmptyState
        v-if="!bpInvoices.length && !activeContracts.length"
        icon="file-text"
        title="No Business Partners yet"
        description="Business Partners are hired via the Support Services marketplace. Contracts and invoices appear here."
      >
        <template #action>
          <a :href="route('provider.jobs.index')" class="btn btn-primary">
            <AegisIcon name="plus" :size="13" /> Go to Support Services
          </a>
        </template>
      </AegisEmptyState>

      <!-- Pending invoice cards -->
      <template v-if="bpFilter === 'all' || bpFilter === 'pending'">
        <div v-for="inv in bpInvoices" :key="inv.id" class="invoice-card pending-approval">
          <div class="invoice-body">
            <div class="invoice-status">
              <AegisBadge label="Awaiting Approval" variant="gold" />
              <span class="invoice-status-right">
                <span class="connect-pill" :class="inv.bp_connected ? 'is-connected' : 'is-not-connected'">
                  <span class="status-dot"></span>{{ inv.bp_connected ? 'Stripe Connected' : 'Not Connected' }}
                </span>
                <span class="invoice-auto"><AegisIcon name="clock" :size="13" /> Auto-approves in 5 days</span>
              </span>
            </div>
            <div class="invoice-head">
              <div>
                <div class="invoice-vendor">{{ inv.bp_name }}</div>
                <div class="invoice-service">{{ inv.contract_title }}</div>
              </div>
              <div>
                <div class="invoice-amount">{{ formatCents(inv.total_cents) }}</div>
                <div class="invoice-period">{{ inv.issued_month }}</div>
              </div>
            </div>
            <div class="invoice-meta">
              <div>
                <div class="invoice-meta-label">Invoice</div>
                <div class="invoice-meta-value">#{{ inv.invoice_number }}</div>
              </div>
              <div>
                <div class="invoice-meta-label">Notes</div>
                <div class="invoice-meta-value">{{ inv.notes_short || '—' }}</div>
              </div>
              <div>
                <div class="invoice-meta-label">Due Date</div>
                <div class="invoice-meta-value due">{{ inv.due_at || '—' }}</div>
              </div>
            </div>
            <div class="invoice-actions">
              <button type="button" class="btn-primary-full" @click="openApproveInvoice(inv)">
                <AegisIcon name="check" :size="15" /> Approve &amp; Pay
              </button>
              <button type="button" class="btn-icon" data-tooltip="View invoice" @click="openViewInvoice(inv)">
                <AegisIcon name="eye" :size="15" />
              </button>
              <button type="button" class="btn-icon" data-tooltip="Dispute this invoice" @click="openBpDispute(inv)">
                <AegisIcon name="alert-triangle" :size="15" />
              </button>
              <button type="button" class="btn-icon btn-icon-danger" data-tooltip="Reject" @click="openRejectInvoice(inv)">
                <AegisIcon name="x" :size="15" />
              </button>
            </div>
          </div>
        </div>
      </template>

      <!-- Active contract cards -->
      <template v-if="bpFilter === 'all' || bpFilter === 'active'">
        <div v-for="con in activeContracts" :key="con.id" class="invoice-card active-contract">
          <div class="invoice-body">
            <div class="invoice-status">
              <AegisBadge label="Active Contract" variant="green" />
              <span class="invoice-status-right">
                <span class="connect-pill" :class="con.bp_connected ? 'is-connected' : 'is-not-connected'">
                  <span class="status-dot"></span>{{ con.bp_connected ? 'Stripe Connected' : 'Not Connected' }}
                </span>
              </span>
            </div>
            <div class="invoice-head">
              <div>
                <div class="invoice-vendor">{{ con.bp_name }}</div>
                <div class="invoice-service">{{ con.title }}</div>
              </div>
              <div>
                <div class="invoice-amount">{{ formatCents(con.total_cents) }}</div>
                <div class="invoice-period">{{ con.billing_type_label }}</div>
              </div>
            </div>
            <div class="invoice-meta">
              <div>
                <div class="invoice-meta-label">Contract Term</div>
                <div class="invoice-meta-value">{{ con.term }}</div>
              </div>
              <div>
                <div class="invoice-meta-label">Last Paid</div>
                <div class="invoice-meta-value">{{ con.last_paid || '—' }}</div>
              </div>
              <div>
                <div class="invoice-meta-label">Total Value</div>
                <div class="invoice-meta-value">{{ formatCents(con.total_cents) }}</div>
              </div>
            </div>
            <div class="invoice-actions">
              <a :href="route('provider.jobs.index')" class="btn btn-outline" data-tooltip="Open in Support Services">
                <AegisIcon name="external-link" :size="13" /> Manage
              </a>
              <button type="button" class="btn-icon" data-tooltip="View contract" @click="openViewContract(con)">
                <AegisIcon name="file-text" :size="15" />
              </button>
              <button type="button" class="btn-icon" data-tooltip="Auto-pay settings" @click="openAutoPay(con)">
                <AegisIcon name="settings" :size="15" />
              </button>
              <button type="button" class="btn-icon btn-icon-danger" data-tooltip="Cancel contract" @click="openCancelContract(con)">
                <AegisIcon name="trash" :size="15" />
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- ══════════════════════════════ TAB: CLINICAL SESSIONS ══════════════════════════════ -->
    <div v-show="activeTab === 'sessions'">
      <div class="alert alert-info" style="margin-bottom:20px;">
        <div class="alert-icon"><AegisIcon name="heart" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">Clinical Sessions You've Booked</div>
          <div>Sessions where you are the client (booking another practitioner's clinical service). Payment routes directly to the provider via Stripe Connect when you confirm completion.</div>
        </div>
      </div>

      <AegisEmptyState
        v-if="!clientSessions.length"
        icon="heart"
        title="No clinical sessions booked"
        description="Sessions you book from other practitioners' service listings appear here."
      >
        <template #action>
          <a :href="route('provider.services.index') + '?tab=explore'" class="btn btn-primary">Explore Services</a>
        </template>
      </AegisEmptyState>

      <div v-for="ses in clientSessions" :key="ses.id" class="invoice-card session-card">
        <div class="invoice-body">
          <div class="invoice-status">
            <AegisBadge :label="ses.status" :variant="statusVariant(ses.status)" />
            <span class="invoice-status-right">
              <span class="connect-pill" :class="ses.practitioner_connected ? 'is-connected' : 'is-not-connected'">
                <span class="status-dot"></span>{{ ses.practitioner_connected ? 'Stripe Connected' : 'Not Connected' }}
              </span>
            </span>
          </div>
          <div class="invoice-head">
            <div>
              <div class="invoice-vendor">{{ ses.practitioner_name }}</div>
              <div class="invoice-service">{{ ses.service_title }}</div>
            </div>
            <div>
              <div class="invoice-amount">{{ formatCents(ses.total_cents) }}</div>
              <div class="invoice-period">{{ ses.issued_month }}</div>
            </div>
          </div>
          <div class="invoice-meta">
            <div>
              <div class="invoice-meta-label">Session ID</div>
              <div class="invoice-meta-value">#{{ ses.invoice_number }}</div>
            </div>
            <div>
              <div class="invoice-meta-label">Scheduled</div>
              <div class="invoice-meta-value">{{ ses.scheduled_at || '—' }}</div>
            </div>
            <div>
              <div class="invoice-meta-label">Payment Status</div>
              <div class="invoice-meta-value">{{ ses.status === 'completed' ? 'Paid' : 'Awaiting confirmation' }}</div>
            </div>
          </div>
          <div class="invoice-actions">
            <a :href="route('provider.services.index') + '?tab=bookings'" class="btn btn-outline">
              <AegisIcon name="external-link" :size="13" /> View in My Services
            </a>
            <button type="button" class="btn-icon" data-tooltip="View receipt" @click="openViewInvoice(ses)">
              <AegisIcon name="eye" :size="15" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════ TAB: SUBSCRIPTION ══════════════════════════════ -->
    <div v-show="activeTab === 'subscription'">

      <!-- ── Cart-style plan card ── -->
      <div class="sub-cart" style="margin-bottom:18px;">

        <!-- Header strip -->
        <div class="sub-cart-head">
          <div class="sub-cart-head-left">
            <div class="sub-cart-logo"><AegisIcon name="star" :size="18" /></div>
            <div>
              <div class="sub-cart-brand">Aegis Platform</div>
              <div class="sub-cart-type">Practice Continuity Subscription</div>
            </div>
          </div>
          <a :href="route('provider.settings.index') + '?section=billing'" class="btn btn-outline btn-sm">
            <AegisIcon name="external-link" :size="12" /> Manage Plan
          </a>
        </div>

        <!-- Line items — plan + add-on -->
        <div class="sub-cart-items">
          <div class="sub-cart-item">
            <div class="sub-cart-item-icon"><AegisIcon name="check-circle" :size="16" /></div>
            <div class="sub-cart-item-info">
              <div class="sub-cart-item-name">
                {{ subscription?.tier === 'practice' ? 'Continuity Practice' : subscription?.tier === 'access' ? 'Continuity Access' : 'Aegis Plan' }}
              </div>
              <div class="sub-cart-item-desc">
                {{ subscription?.status === 'active' ? 'Active — renews monthly' : subscription?.status === 'past_due' ? 'Payment past due' : subscription?.status || 'Inactive' }}
              </div>
            </div>
            <div class="sub-cart-item-price">
              {{ subscription?.tier === 'practice' ? '$49' : subscription?.tier === 'access' ? '$29' : '—' }}
              <span class="sub-cart-item-per">/mo</span>
            </div>
            <AegisBadge
              :label="subscription?.status || 'inactive'"
              :variant="subscription?.status === 'active' ? 'green' : subscription?.status === 'past_due' ? 'red' : 'neutral'"
            />
          </div>

          <div v-if="subscription?.has_maat_addon" class="sub-cart-item sub-cart-item--addon">
            <div class="sub-cart-item-icon sub-cart-item-icon--gold"><AegisIcon name="shield" :size="16" /></div>
            <div class="sub-cart-item-info">
              <div class="sub-cart-item-name">MAAT Professional CS Add-on</div>
              <div class="sub-cart-item-desc">Certified Continuity Steward · 4-hr emergency response</div>
            </div>
            <div class="sub-cart-item-price">+$29<span class="sub-cart-item-per">/mo</span></div>
            <AegisBadge label="Active" variant="gold" />
          </div>
        </div>

        <!-- Total footer -->
        <div class="sub-cart-total">
          <div class="sub-cart-total-label">
            <AegisIcon name="credit-card" :size="13" />
            Billed monthly to your default card
          </div>
          <div class="sub-cart-total-amount">
            {{ subscription?.has_maat_addon
               ? (subscription?.tier === 'practice' ? '$78' : '$58')
               : (subscription?.tier === 'practice' ? '$49' : subscription?.tier === 'access' ? '$29' : '—') }}<span class="sub-cart-item-per">/mo</span>
          </div>
        </div>

        <!-- Grace / past-due alert -->
        <div v-if="subscription?.ends_at" class="sub-cart-alert">
          <AegisIcon name="alert-triangle" :size="14" />
          Subscription ends {{ formatSubscriptionDate(subscription.ends_at) }} — reactivate in Settings to maintain access.
        </div>
      </div>

      <!-- ── Invoice history ── -->
      <div class="card">
        <div class="card-header">
          <div class="card-title fin-card-title">
            <span class="fin-card-icon"><AegisIcon name="file-text" :size="15" /></span>
            Subscription Invoices
          </div>
          <AegisBadge :label="subscriptionInvoices.length + (subscriptionInvoices.length === 1 ? ' invoice' : ' invoices')" variant="neutral" />
        </div>
        <div class="card-body" style="padding:0;">
          <table v-if="subscriptionInvoices.length" class="table sub-invoice-table" style="margin:0;">
            <thead>
              <tr>
                <th style="padding-left:20px;">Date</th>
                <th>Plan</th>
                <th>Amount</th>
                <th>Status</th>
                <th style="padding-right:20px;"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="inv in subscriptionInvoices" :key="inv.id" class="sub-inv-row" @click="openSubInvoice(inv)">
                <td style="padding-left:20px;" class="tx-date">{{ formatSubscriptionDate(inv.date || inv.created) }}</td>
                <td>
                  <div class="sub-inv-product">{{ inv.product_name || 'Aegis Subscription' }}</div>
                  <div class="sub-inv-desc">#{{ inv.number || inv.id }}</div>
                </td>
                <td class="tx-amount-out" style="font-size:14px;white-space:nowrap;">{{ formatCents(inv.amount_cents) }}</td>
                <td><AegisBadge :label="inv.status" :variant="statusVariant(inv.status)" /></td>
                <td style="padding-right:20px;text-align:right;">
                  <button type="button" class="btn-icon btn-icon-sm" data-tooltip="View invoice" @click.stop="openSubInvoice(inv)">
                    <AegisIcon name="eye" :size="12" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          <AegisEmptyState
            v-else icon="file-text"
            title="No invoices yet"
            description="Your subscription invoices will appear here after your first billing cycle."
            style="padding:32px 0;"
          />
        </div>
      </div>
    </div>

    <!-- ══ SUB INVOICE MODAL ══ -->
    <AegisModal v-model="modals.subInvoice" title="Subscription Invoice" size="lg">
      <div v-if="activeSubInvoice" class="sub-inv-modal">

        <!-- Branded header -->
        <div class="sim-header">
          <div class="sim-logo"><AegisIcon name="star" :size="20" /></div>
          <div class="sim-brand">
            <div class="sim-from">Aegis Platform</div>
            <div class="sim-sub">Practice Continuity Subscription</div>
          </div>
          <div class="sim-status-block">
            <AegisBadge :label="activeSubInvoice.status" :variant="statusVariant(activeSubInvoice.status)" />
            <div class="sim-date">{{ formatSubscriptionDate(activeSubInvoice.date || activeSubInvoice.created) }}</div>
          </div>
        </div>

        <!-- Invoice number -->
        <div class="sim-number-row">
          <span class="sim-number-label">Invoice #</span>
          <span class="sim-number">{{ activeSubInvoice.number || activeSubInvoice.id }}</span>
        </div>

        <!-- Line items -->
        <div class="sim-items">
          <div class="sim-item">
            <div class="sim-item-icon"><AegisIcon name="check-circle" :size="15" /></div>
            <div class="sim-item-name">{{ activeSubInvoice.product_name || 'Aegis Subscription' }}</div>
            <div class="sim-item-price">{{ formatCents(activeSubInvoice.amount_cents) }}</div>
          </div>
        </div>

        <!-- Totals -->
        <div class="sim-totals">
          <div class="sim-total-row">
            <span>Subtotal</span>
            <span>{{ formatCents(activeSubInvoice.amount_cents) }}</span>
          </div>
          <div class="sim-total-row sim-total-row--main">
            <span>{{ activeSubInvoice.status === 'paid' ? 'Total paid' : 'Amount due' }}</span>
            <span>{{ formatCents(activeSubInvoice.amount_cents) }}</span>
          </div>
        </div>

        <!-- Fine print -->
        <div class="sim-fine-print">
          <AegisIcon name="shield" :size="12" />
          Charged to your default card on file. Card details are securely tokenized by Stripe — Aegis never stores your full card number.
        </div>
      </div>
      <template #footer>
        <a v-if="activeSubInvoice?.pdf_url" :href="activeSubInvoice.pdf_url" target="_blank" class="btn btn-ghost">
          <AegisIcon name="download" :size="12" /> Download PDF
        </a>
        <a v-if="activeSubInvoice?.hosted_url" :href="activeSubInvoice.hosted_url" target="_blank" class="btn btn-outline">
          <AegisIcon name="external-link" :size="12" /> View on Stripe
        </a>
        <button v-if="!activeSubInvoice?.hosted_url && !activeSubInvoice?.pdf_url" type="button" class="btn btn-outline" @click="modals.subInvoice = false">Close</button>
      </template>
    </AegisModal>

    <!-- ══════════════════════════════ TAB: PAYMENT METHODS ══════════════════════════════ -->
    <div v-show="activeTab === 'methods'">
      <div class="card" style="margin-bottom:18px;">
        <div class="card-header">
          <div class="card-title fin-card-title">
            <span class="fin-card-icon"><AegisIcon name="credit-card" :size="15" /></span>
            Saved Payment Methods
          </div>
          <button type="button" class="btn btn-dark" @click="modals.addPayment = true">
            <AegisIcon name="plus" :size="12" /> Add Method
          </button>
        </div>
        <div class="card-body">
          <div class="alert alert-info" style="margin-bottom:16px;">
            <div class="alert-icon"><AegisIcon name="shield" :size="18" /></div>
            <div class="alert-content">
              <div class="alert-title">One Card, All Payments</div>
              <div>Your active payment method funds every Aegis charge — subscription, CS fees, BP invoices, and clinical sessions. Aegis never sees or stores your full card number.</div>
            </div>
          </div>

          <AegisEmptyState
            v-if="!paymentMethods.length"
            icon="credit-card"
            title="No payment methods"
            description="Add a card or bank account to pay Business Partners and manage your Aegis subscription."
            style="padding:24px 0;"
          />
          <div v-else>
            <div v-for="pm in paymentMethods" :key="pm.id" class="pm-card" :class="{ default: pm.is_default }">
              <div class="pm-logo">
                <AegisIcon :name="pm.method_type === 'bank' ? 'building' : 'credit-card'" :size="20" />
              </div>
              <div class="pm-info">
                <div class="pm-name">
                  {{ (pm.brand || 'card').toUpperCase() }} ···· {{ pm.last4 }}
                  <AegisBadge v-if="pm.is_default" label="Default · funds all payments" variant="gold" style="margin-left:6px;" />
                </div>
                <div class="pm-meta">
                  {{ pm.method_type === 'bank' ? 'ACH / Bank Transfer' : (pm.exp_month ? 'Expires ' + pm.exp_month + '/' + pm.exp_year : 'On file') }}
                </div>
              </div>
              <div class="pm-card-btns">
                <template v-if="!pm.is_default">
                  <button type="button" class="btn-icon btn-icon-sm" data-tooltip="Set as default" @click="setDefaultPm(pm)">
                    <AegisIcon name="check" :size="12" />
                  </button>
                  <button type="button" class="btn-icon btn-icon-sm btn-icon-danger" data-tooltip="Remove" @click="openRemoveCard(pm)">
                    <AegisIcon name="trash" :size="12" />
                  </button>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ══════════════════════════════ TAB: TRANSACTIONS ══════════════════════════════ -->
    <div v-show="activeTab === 'history'">
      <div class="card">
        <div class="card-header">
          <div class="card-title fin-card-title">
            <span class="fin-card-icon"><AegisIcon name="clock" :size="15" /></span>
            Full Transaction History
          </div>
        </div>
        <div class="card-body">
          <!-- Single-line toolbar: search 6-col + 3 selects 6-col split into 3 -->
          <div class="fin-toolbar">
            <div class="input-group fin-toolbar-search">
              <span class="input-group-icon"><AegisIcon name="search" :size="13" /></span>
              <input class="form-input form-input-sm" type="text" v-model="txSearch" placeholder="Search…">
            </div>
            <div class="fin-toolbar-filters">
              <select class="form-select form-select-sm" v-model="txCatFilter">
                <option value="">All Categories</option>
                <option value="cs">CS Finance</option>
                <option value="bp">Business Partner</option>
                <option value="session">Clinical Session</option>
                <option value="subscription">Subscription</option>
                <option value="refund">Refund</option>
              </select>
              <select class="form-select form-select-sm" v-model="txStatusFilter">
                <option value="">All Statuses</option>
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
                <option value="failed">Failed</option>
                <option value="refunded">Refunded</option>
              </select>
              <select class="form-select form-select-sm" v-model="txSortOrder">
                <option value="desc">Newest first</option>
                <option value="asc">Oldest first</option>
              </select>
            </div>
          </div>

          <div style="overflow-x:auto;border-top:1px solid var(--border);margin:0 -20px;">
            <table class="table fin-tx-table" style="margin:0;">
              <thead>
                <tr>
                  <th>Date</th><th>Description</th><th>Category</th><th>Amount</th><th>Status</th><th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="tx in paginatedTransactions" :key="tx.id">
                  <td class="tx-date">{{ tx.date }}</td>
                  <td class="tx-desc">{{ tx.description }}</td>
                  <td>
                    <span class="tx-cat-wrap">
                      <span class="tx-cat-dot" :style="{ background: tx.cat_color }"></span>
                      {{ tx.category_label }}
                    </span>
                  </td>
                  <td :class="tx.amount < 0 ? 'tx-amount-out' : 'tx-amount-in'">
                    {{ tx.amount < 0 ? '-' : '+' }}${{ Math.abs(tx.amount / 100).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                  </td>
                  <td><AegisBadge :label="tx.status" :variant="statusVariant(tx.status)" /></td>
                  <td class="tx-action">
                    <button type="button" class="btn-icon btn-icon-sm" data-tooltip="View receipt" @click="openTxReceipt(tx)">
                      <AegisIcon name="eye" :size="12" />
                    </button>
                  </td>
                </tr>
                <tr v-if="!paginatedTransactions.length">
                  <td colspan="6" style="text-align:center;padding:24px;color:var(--text-3);font-size:13px;">
                    No transactions match your filters.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="txTotalPages > 1" style="padding:16px 0 0;display:flex;justify-content:center;">
            <AegisPagination
              :current-page="txPage"
              :total-pages="txTotalPages"
              @change="txPage = $event"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════ TAB: INTEGRATIONS ══════════════════════════════ -->
    <div v-show="activeTab === 'integrations'">
      <div class="card">
        <div class="card-header">
          <div class="card-title fin-card-title">
            <span class="fin-card-icon"><AegisIcon name="link" :size="15" /></span>
            Integrations
          </div>
        </div>
        <div class="card-body">

          <!-- Stripe Connect -->
          <div class="stripe-setup-card" style="margin-bottom:20px;">
            <div class="stripe-setup-inner">
              <div class="stripe-setup-icon"><AegisIcon name="credit-card" :size="22" /></div>
              <div class="stripe-setup-body">
                <div class="stripe-setup-title">Stripe Connect</div>
                <div class="stripe-setup-desc">
                  Connect your Stripe account to <strong>receive</strong> payments from clients booking your services. Aegis uses Stripe Connect — funds go directly to your bank account. Aegis never holds your money.
                </div>
                <div v-if="stripeConnected" class="stripe-setup-connected">
                  <span class="app-status-connected"><AegisIcon name="check" :size="13" /> Connected</span>
                  <a :href="route('provider.settings.billing.portal')" class="btn btn-ghost btn-sm" target="_blank">
                    <AegisIcon name="external-link" :size="12" /> Stripe Dashboard
                  </a>
                </div>
                <div v-else class="stripe-setup-actions">
                  <a :href="route('provider.settings.connect.onboard')" class="btn btn-primary">
                    <AegisIcon name="external-link" :size="13" /> Connect Stripe Account
                  </a>
                  <span style="font-size:12px;color:var(--text-4);">You'll be redirected to Stripe to complete setup</span>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

      </div><!-- /fin-content -->
    </div><!-- /fin-layout -->

    <!-- ══════════════════════════════ MODALS ══════════════════════════════ -->

    <!-- Approve BP Invoice -->
    <AegisModal v-model="modals.approveInvoice" title="Approve &amp; Pay Invoice" size="lg">
      <div v-if="activeInvoice">
        <div class="alert alert-success" style="margin-bottom:14px;">
          <div class="alert-icon"><AegisIcon name="check" :size="18" /></div>
          <div class="alert-content">
            <strong>Invoice #{{ activeInvoice.invoice_number }} · {{ activeInvoice.bp_name }}</strong>
            — {{ formatCents(activeInvoice.total_cents) }} will be charged to your default card and routed directly to {{ activeInvoice.bp_name }} via Stripe Connect.
          </div>
        </div>
        <div class="receipt">
          <div class="receipt-row"><span>{{ activeInvoice.contract_title || 'Services' }}</span><span>{{ formatCents(activeInvoice.total_cents) }}</span></div>
          <div class="receipt-row total"><span>Total</span><span>{{ formatCents(activeInvoice.total_cents) }}</span></div>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.approveInvoice = false">Cancel</button>
        <button type="button" class="btn btn-success" :disabled="paying === activeInvoice?.id" @click="doApproveBpInvoice">
          <AegisIcon name="check" :size="13" />
          {{ paying === activeInvoice?.id ? 'Processing…' : ('Approve & Pay ' + (activeInvoice ? formatCents(activeInvoice.total_cents) : '')) }}
        </button>
      </template>
    </AegisModal>

    <!-- Reject BP Invoice -->
    <AegisModal v-model="modals.rejectInvoice" title="Reject Invoice" size="lg">
      <div v-if="activeInvoice">
        <div class="alert alert-danger" style="margin-bottom:14px;">
          <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
          <div class="alert-content">
            <strong>Rejecting will notify {{ activeInvoice.bp_name }}.</strong> They can revise and resubmit.
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Reason for Rejection <span class="required">*</span></label>
          <select class="form-select" v-model="rejectForm.reason" :class="{ 'is-error': rejectForm.errors.reason }">
            <option>Incorrect amount</option>
            <option>Services not delivered</option>
            <option>Duplicate invoice</option>
            <option>Unauthorized charges</option>
            <option>Missing documentation</option>
            <option>Other</option>
          </select>
          <div v-if="rejectForm.errors.reason" class="form-error">{{ rejectForm.errors.reason }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Message to Business Partner</label>
          <textarea class="form-textarea" rows="3" v-model="rejectForm.message"
            :class="{ 'is-error': rejectForm.errors.message }"
            placeholder="Explain the rejection so they can resubmit correctly…"></textarea>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.rejectInvoice = false">Cancel</button>
        <button type="button" class="btn btn-danger" :disabled="rejectForm.processing" @click="doRejectInvoice">
          <AegisIcon name="x" :size="13" /> {{ rejectForm.processing ? 'Rejecting…' : 'Reject Invoice' }}
        </button>
      </template>
    </AegisModal>

    <!-- Centralized View Invoice (all payment types) -->
    <ViewInvoiceModal
      v-model="modals.viewReceipt"
      :invoice="activeInvoice"
      :can-approve="activeInvoice?.kind !== 'subscription'"
      @approve="handleReceiptApprove"
    />

    <!-- Cancel CS Agreement -->
    <AegisModal v-model="modals.cancelCsAgreement" title="Cancel Continuity Steward Agreement" size="lg">
      <div class="alert alert-danger" style="margin-bottom:14px;">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
        <div class="alert-content">
          <strong>Cancelling leaves your practice without succession coverage.</strong> Aegis strongly recommends at least one active Continuity Steward at all times.
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Cancellation Reason <span class="required">*</span></label>
        <select class="form-select" v-model="cancelCsForm.reason" :class="{ 'is-error': cancelCsForm.errors.reason }">
          <option>Replacing with another Continuity Steward</option>
          <option>Continuity Steward resigned</option>
          <option>Mutual termination</option>
          <option>Practice closing</option>
          <option>Other</option>
        </select>
        <div v-if="cancelCsForm.errors.reason" class="form-error">{{ cancelCsForm.errors.reason }}</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.cancelCsAgreement = false">Keep Agreement</button>
        <button type="button" class="btn btn-danger" :disabled="cancelCsForm.processing" @click="doCancelCsAgreement">
          <AegisIcon name="x" :size="13" /> Cancel Agreement
        </button>
      </template>
    </AegisModal>

    <!-- Cancel BP Contract -->
    <AegisModal v-model="modals.cancelBpContract" title="Cancel Contract" size="lg">
      <div class="alert alert-danger" style="margin-bottom:14px;">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
        <div class="alert-content">
          <strong>Cancelling {{ activeContract ? 'your contract with ' + activeContract.bp_name : 'this contract' }} will stop scheduled payments.</strong> Per contract terms, 30-day notice is required.
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Reason <span class="required">*</span></label>
        <select class="form-select" v-model="cancelContractForm.reason" :class="{ 'is-error': cancelContractForm.errors.reason }">
          <option>Switching to different provider</option>
          <option>No longer needed</option>
          <option>Cost reduction</option>
          <option>Service quality issues</option>
          <option>Other</option>
        </select>
        <div v-if="cancelContractForm.errors.reason" class="form-error">{{ cancelContractForm.errors.reason }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Feedback (optional)</label>
        <textarea class="form-textarea" rows="2" v-model="cancelContractForm.feedback"
          placeholder="Let the Business Partner know why you're cancelling…"></textarea>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.cancelBpContract = false">Keep Contract</button>
        <button type="button" class="btn btn-danger" :disabled="cancelContractForm.processing" @click="doCancelBpContract">
          <AegisIcon name="x" :size="13" /> Send Cancellation Notice
        </button>
      </template>
    </AegisModal>

    <!-- Auto-Pay Settings (BP contract) -->
    <AegisModal v-model="modals.autoPay" :title="'Auto-Pay Settings' + (activeContract ? ' — ' + activeContract.bp_name : '')" size="lg">
      <div class="setting-row" style="margin-bottom:14px;">
        <div class="setting-info">
          <div class="setting-label">Enable Auto-Pay</div>
          <div class="setting-desc">Automatically charge your default method when an invoice is due. Payment routes directly to the Business Partner.</div>
        </div>
        <button
          type="button"
          class="toggle"
          :class="{ on: autoPayForm.enabled }"
          :aria-pressed="autoPayForm.enabled"
          @click="autoPayForm.enabled = !autoPayForm.enabled"
        ></button>
      </div>
      <div class="form-group">
        <label class="form-label">Payment Day</label>
        <select class="form-select" v-model="autoPayForm.day">
          <option value="1st">1st of month</option>
          <option value="15th">15th of month</option>
          <option value="last">Last day of month</option>
          <option value="due">Invoice due date</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Notify me before charge</label>
        <select class="form-select" v-model="autoPayForm.notify">
          <option value="3_days">3 days before</option>
          <option value="1_day">1 day before</option>
          <option value="same_day">Same day only</option>
          <option value="none">Don't notify</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Max auto-pay limit (leave blank for no limit)</label>
        <input class="form-input" type="number" min="0" v-model="autoPayForm.limit" placeholder="e.g. 2500">
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.autoPay = false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="autoPayForm.processing" @click="doSaveAutoPay">
          <AegisIcon name="check" :size="13" /> Save Settings
        </button>
      </template>
    </AegisModal>

    <!-- View Contract -->
    <AegisModal v-model="modals.viewContract" :title="activeContract ? 'Contract — ' + activeContract.bp_name : 'Contract'" size="lg">
      <div v-if="activeContract" class="contract-preview">
        <div class="contract-preview-title">Aegis Service Agreement</div>
        <div class="contract-preview-row"><strong>Business Partner:</strong> {{ activeContract.bp_name }}</div>
        <div class="contract-preview-row"><strong>Services:</strong> {{ activeContract.title }}</div>
        <div class="contract-preview-row"><strong>Payment Type:</strong> {{ activeContract.billing_type_label }}</div>
        <div class="contract-preview-row"><strong>Total Value:</strong> {{ formatCents(activeContract.total_cents) }}</div>
        <div class="contract-preview-row"><strong>Term:</strong> {{ activeContract.term }}</div>
        <div class="contract-preview-row"><strong>Termination:</strong> 30-day written notice</div>
      </div>
      <template #footer>
        <a v-if="activeContract" :href="route('provider.jobs.index')" class="btn btn-ghost">
          <AegisIcon name="external-link" :size="12" /> Open in Support Services
        </a>
        <button type="button" class="btn btn-outline" @click="modals.viewContract = false">Close</button>
      </template>
    </AegisModal>

    <!-- Add Payment Method (centralized AddCardModal) -->
    <AddCardModal
      v-model="modals.addPayment"
      setup-intent-route="provider.settings.payment.setup-intent"
      store-route="provider.finances.payment.store"
      @saved="activeTab = 'methods'"
    />

    <!-- Remove Card -->
    <AegisModal v-model="modals.removeCard" title="Remove Payment Method" size="sm">
      <p style="font-size:13px;color:var(--text-2);">
        Remove this payment method? If it's the only card on file, subscription renewal and peer payments will fail until a new card is added.
      </p>
      <template #footer>
        <button type="button" class="btn btn-outline" :disabled="removingCard" @click="modals.removeCard = false">Cancel</button>
        <button type="button" class="btn btn-danger" :disabled="removingCard" @click="doRemoveCard">
          <AegisIcon v-if="removingCard" name="refresh-cw" :size="13" class="spin" />
          <AegisIcon v-else name="trash" :size="13" />
          {{ removingCard ? 'Removing…' : 'Remove' }}
        </button>
      </template>
    </AegisModal>

    <!-- Export Report — with client-side validation -->
    <AegisModal v-model="modals.export" title="Export Financial Report" size="lg">
      <div class="row-2">
        <div class="form-group">
          <label class="form-label">From <span class="required">*</span></label>
          <input class="form-input" type="date" v-model="exportForm.from"
            :class="{ 'is-error': !!exportErrors.from }" @blur="validateExport">
          <div v-if="exportErrors.from" class="form-error">{{ exportErrors.from }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">To <span class="required">*</span></label>
          <input class="form-input" type="date" v-model="exportForm.to"
            :class="{ 'is-error': !!exportErrors.to }" @blur="validateExport">
          <div v-if="exportErrors.to" class="form-error">{{ exportErrors.to }}</div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Include payment types <span class="required">*</span></label>
        <div class="export-checks">
          <label class="form-check">
            <input type="checkbox" value="cs" v-model="exportForm.includes">
            <span class="form-check-label">Continuity Steward payments</span>
          </label>
          <label class="form-check">
            <input type="checkbox" value="bp" v-model="exportForm.includes">
            <span class="form-check-label">Business Partner payments</span>
          </label>
          <label class="form-check">
            <input type="checkbox" value="sessions" v-model="exportForm.includes">
            <span class="form-check-label">Clinical session payments</span>
          </label>
          <label class="form-check">
            <input type="checkbox" value="subscription" v-model="exportForm.includes">
            <span class="form-check-label">Aegis subscription</span>
          </label>
        </div>
        <div v-if="exportErrors.includes" class="form-error">{{ exportErrors.includes }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Format</label>
        <select class="form-select" v-model="exportForm.format">
          <option value="csv">CSV</option>
          <option value="pdf" disabled>PDF (coming soon)</option>
          <option value="xlsx" disabled>Excel (coming soon)</option>
        </select>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.export = false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="exportProcessing" @click="doExport">
          <AegisIcon name="download" :size="13" />
          {{ exportProcessing ? 'Preparing…' : 'Download Report' }}
        </button>
      </template>
    </AegisModal>

    <!-- Payment Arrangement History (CS) -->
    <AegisModal v-model="modals.payArrangement" title="Payment Arrangement History" size="lg">
      <div v-if="activeCs" style="padding:0;">
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr><th style="padding-left:22px;">Date</th><th>Event</th><th>Amount</th><th style="padding-right:22px;">Status</th></tr>
            </thead>
            <tbody>
              <tr>
                <td style="padding-left:22px;">{{ new Date().toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'}) }}</td>
                <td>Current authorization</td>
                <td>{{ formatCents(activeCs.fee_cents) }} / activation</td>
                <td style="padding-right:22px;"><AegisBadge label="Active" variant="green" /></td>
              </tr>
            </tbody>
          </table>
        </div>
        <p style="font-size:12px;color:var(--text-3);padding:14px 22px 0;">
          No funds have changed hands. The agreed fee is charged directly to {{ activeCs.display_name }} only when a critical incident is verified. Aegis holds no funds.
        </p>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.payArrangement = false">Close</button>
      </template>
    </AegisModal>

    <!-- Change CS Payment Model -->
    <AegisModal v-model="modals.changePayModel" title="Update Payment Terms" size="lg">
      <p style="font-size:13px;color:var(--text-2);margin-bottom:16px;">
        Choose payment terms for <strong>{{ activeCs?.display_name || 'this Continuity Steward' }}</strong>. The agreed fee is collected through your saved payment authorization when a critical incident is verified.
      </p>
      <div v-for="opt in payModelOptions" :key="opt.value"
           class="role-option"
           :class="{ selected: payModelForm.payment_model === opt.value }"
           @click="payModelForm.payment_model = opt.value"
      >
        <div style="flex:1;min-width:0;">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:var(--text);cursor:pointer;margin:0;">
            <input type="radio" name="paymodel" :value="opt.value" v-model="payModelForm.payment_model"
              style="accent-color:var(--gold-dark);width:16px;height:16px;flex-shrink:0;margin:0;">
            <AegisIcon :name="opt.icon" :size="14" /> {{ opt.label }}
          </label>
          <div style="font-size:12px;color:var(--text-3);margin-top:4px;padding-left:24px;">{{ opt.desc }}</div>
        </div>
      </div>

      <div class="form-group" style="margin-top:14px;">
        <label class="form-label">Fee per Activation ($)</label>
        <input class="form-input" type="number" min="0" v-model.number="payModelFeeUsd" placeholder="e.g. 900">
        <div class="form-hint">Fee charged when an incident is verified and closed.</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.changePayModel = false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="payModelForm.processing" @click="doSavePayModel">
          <AegisIcon name="check" :size="13" /> Update Payment Terms
        </button>
      </template>
    </AegisModal>

    <!-- CS + BP Confirm-pay modals -->
    <AegisModal v-model="modals.confirmCsPay" title="Confirm CS Payment" size="sm">
      <p v-if="csTarget">
        Pay <strong>{{ formatCents(csTarget.total_cents) }}</strong> to
        <strong>{{ csTarget.cs_name }}</strong> for invoice
        <strong>{{ csTarget.invoice_number }}</strong>?
      </p>
      <p style="font-size:12px;color:var(--text-3);margin-top:8px;">
        Funds transfer directly to your Continuity Steward's Stripe account via Stripe Connect. Aegis holds no funds.
      </p>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.confirmCsPay = false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="paying === csTarget?.id" @click="doPayCs">
          {{ paying === csTarget?.id ? 'Processing…' : 'Pay now' }}
        </button>
      </template>
    </AegisModal>

    <!-- Open Dispute -->
    <OpenDisputeModal
      v-model="modals.openDispute"
      :subject="disputeTarget"
      post-route="provider.disputes.store"
      @opened="router.reload({ only: ['csInvoices', 'bpInvoices', 'disputes'] })"
    />

  </AppLayout>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { router, useForm }         from '@inertiajs/vue3'
import AppLayout                   from '@/layouts/AppLayout.vue'
import OpenDisputeModal            from '@/components/modals/OpenDisputeModal.vue'
import AddCardModal                from '@/components/modals/AddCardModal.vue'
import ViewInvoiceModal            from '@/components/modals/ViewInvoiceModal.vue'
import AegisPagination             from '@/components/ui/AegisPagination.vue'
import { useToast }                from '@/composables/useToast'
import { useConfirm }              from '@/composables/useConfirm'
import { useProfileRoute }         from '@/composables/useProfileRoute'

const toast = useToast()
const { confirmAction } = useConfirm()
const { profileHref } = useProfileRoute()

const props = defineProps({
  subscription:            { type: Object,  default: () => ({}) },
  subscriptionInvoices:    { type: Array,   default: () => [] },
  paymentMethods:          { type: Array,   default: () => [] },
  csInvoices:              { type: Array,   default: () => [] },
  bpInvoices:              { type: Array,   default: () => [] },
  clientSessions:          { type: Array,   default: () => [] },
  allInvoices:             { type: Array,   default: () => [] },
  activeContracts:         { type: Array,   default: () => [] },
  csStewards:              { type: Array,   default: () => [] },
  upcomingPayments:        { type: Array,   default: () => [] },
  recentTransactions:      { type: Array,   default: () => [] },
  spendBreakdown:          { type: Array,   default: () => [] },
  disputes:                { type: Array,   default: () => [] },
  totalSpendCents:         { type: Number,  default: 0 },
  outstandingCents:        { type: Number,  default: 0 },
  csAgreedTotal:           { type: Number,  default: 0 },
  pendingInvoiceTotal:     { type: Number,  default: 0 },
  pendingInvoiceCount:     { type: Number,  default: 0 },
  pendingBreakdown:        { type: Object,  default: () => ({ bp: {count:0, total_cents:0}, cs: {count:0, total_cents:0}, session: {count:0, total_cents:0} }) },
  activeContractCount:     { type: Number,  default: 0 },
  activeContractBreakdown: { type: Object,  default: () => ({ bp: 0, cs: 0, session: 0 }) },
  stripeConnected:         { type: Boolean, default: false },
  has_valid_default_pm:    { type: Boolean, default: false },
  spendingControls:        { type: Object,  default: () => ({ auto_pay: false, approval_threshold: 500, monthly_limit: 5000 }) },
})

// ── Tab + BP filter state ───────────────────────────────────────────────
const activeTab = ref('overview')

// Deep-link: ?tab=methods lands directly on Payment Methods (from Settings)
const validTabs = ['overview','executor','bp','sessions','subscription','methods','history','integrations']
onMounted(() => {
  const t = new URLSearchParams(window.location.search).get('tab')
  if (t && validTabs.includes(t)) activeTab.value = t
})
const bpFilter  = ref('all')

const bpPendingCount      = computed(() => props.pendingBreakdown?.bp?.count ?? 0)
const sessionPendingCount = computed(() => props.pendingBreakdown?.session?.count ?? 0)
const allBpItems          = computed(() => props.bpInvoices.length + props.activeContracts.length)

// ── Tooltips ─────────────────────────────────────────────────────────────
const pendingBreakdownTooltip = computed(() => {
  const p = props.pendingBreakdown
  const parts = []
  if (p.bp?.count)      parts.push(`${p.bp.count} Business Partner`)
  if (p.cs?.count)      parts.push(`${p.cs.count} Continuity Steward`)
  if (p.session?.count) parts.push(`${p.session.count} Clinical Session`)
  return parts.length ? parts.join(' · ') : 'No pending invoices'
})

const activeContractsBreakdownTooltip = computed(() => {
  const b = props.activeContractBreakdown
  const parts = []
  if (b.bp)      parts.push(`${b.bp} Business Partner contract${b.bp !== 1 ? 's' : ''}`)
  if (b.cs)      parts.push(`${b.cs} CS agreement${b.cs !== 1 ? 's' : ''}`)
  if (b.session) parts.push(`${b.session} clinical session${b.session !== 1 ? 's' : ''}`)
  return parts.length ? parts.join(' · ') : 'No active contracts'
})

// ── Transaction table (with pagination) ─────────────────────────────────
const txSearch       = ref('')
const txCatFilter    = ref('')
const txStatusFilter = ref('')
const txSortOrder    = ref('desc')
const txPage         = ref(1)
const TX_PER_PAGE    = 10

const filteredTransactions = computed(() => {
  let list = [...(props.recentTransactions ?? [])]
  if (txSearch.value) {
    const q = txSearch.value.toLowerCase()
    list = list.filter(t => (t.payee + t.description + t.category_label).toLowerCase().includes(q))
  }
  if (txCatFilter.value)    list = list.filter(t => t.cat === txCatFilter.value)
  if (txStatusFilter.value) list = list.filter(t => t.status === txStatusFilter.value)
  if (txSortOrder.value === 'asc') list = list.reverse()
  return list
})
const txTotalPages = computed(() => Math.max(1, Math.ceil(filteredTransactions.value.length / TX_PER_PAGE)))
const paginatedTransactions = computed(() => {
  const start = (txPage.value - 1) * TX_PER_PAGE
  return filteredTransactions.value.slice(start, start + TX_PER_PAGE)
})

// ── Active refs ──────────────────────────────────────────────────────────
const activeInvoice  = ref(null)
const activeContract = ref(null)
const activeCs       = ref(null)
const activePm       = ref(null)
const removingCard   = ref(false)

// ── Modals ───────────────────────────────────────────────────────────────
const modals = ref({
  approveInvoice: false, rejectInvoice: false, viewReceipt: false,
  cancelCsAgreement: false, cancelBpContract: false, autoPay: false,
  viewContract: false, addPayment: false, removeCard: false, export: false,
  payArrangement: false, changePayModel: false, confirmCsPay: false, openDispute: false,
  subInvoice: false,
})

const activeSubInvoice = ref(null)
function openSubInvoice(inv) { activeSubInvoice.value = inv; modals.value.subInvoice = true }

// ── Payment forms (Inertia) ──────────────────────────────────────────────
const paying   = ref(null)
const csTarget = ref(null)

function askPayCs(inv) { csTarget.value = inv; modals.value.confirmCsPay = true }

function doPayCs() {
  if (!csTarget.value) return
  paying.value = csTarget.value.id
  router.post(route('provider.finances.cs-invoice.pay', { invoice: csTarget.value.id }), {}, {
    preserveScroll: true,
    onSuccess: () => { modals.value.confirmCsPay = false; csTarget.value = null; toast.success('CS invoice paid.') },
    onError:  () => toast.error('Payment failed. Please check your default payment method.'),
    onFinish: () => { paying.value = null },
  })
}

function doApproveBpInvoice() {
  if (!activeInvoice.value) return
  paying.value = activeInvoice.value.id
  router.post(route('provider.jobs.bp-invoice.pay', { invoice: activeInvoice.value.id }), {}, {
    preserveScroll: true,
    onSuccess: () => { modals.value.approveInvoice = false; toast.success('Payment sent — routed directly to the recipient via Stripe Connect.') },
    onError: () => toast.error('Payment failed. Please check your default payment method.'),
    onFinish: () => { paying.value = null },
  })
}

// ── Dispute ──────────────────────────────────────────────────────────────
const disputeTarget = ref(null)
function openBpDispute(inv) {
  disputeTarget.value = {
    type: 'bp_invoice',
    id: inv.id,
    amount_cents: inv.total_cents,
    label: `BP Invoice ${inv.invoice_number} · ${inv.bp_name}`,
  }
  modals.value.openDispute = true
}

// ── Invoice modal actions ────────────────────────────────────────────────
function openApproveInvoice(inv) { activeInvoice.value = inv; modals.value.approveInvoice = true }
function openRejectInvoice(inv)  { activeInvoice.value = inv; rejectForm.reason = 'Incorrect amount'; rejectForm.message = ''; modals.value.rejectInvoice = true }
function openViewInvoice(inv)    { activeInvoice.value = inv; modals.value.viewReceipt = true }

/**
 * Open the correct receipt modal for a transaction row. Maps each payment
 * kind back to its source invoice/session record so the modal shows the
 * right receipt shape.
 */
function openTxReceipt(tx) {
  if (tx.modal_type === 'subscription') {
    // Try to match against a loaded subscription invoice by Stripe invoice ID
    const matched = props.subscriptionInvoices?.find(i => i.id === tx.stripe_invoice_id)
    if (matched) {
      activeSubInvoice.value = matched
      modals.value.subInvoice = true
      return
    }
    // Fallback: switch to subscription tab so user can find it manually
    activeTab.value = 'subscription'
    toast.info('Subscription invoice not found in recent history — check the Subscription tab.')
    return
  }
  if (tx.modal_type === 'cs_invoice') {
    const inv = props.csInvoices.find(i => tx.description.includes(i.invoice_number))
    if (inv) { activeInvoice.value = { ...inv, kind: 'cs_invoice' }; modals.value.viewReceipt = true; return }
  }
  if (tx.modal_type === 'bp_invoice') {
    const inv = props.bpInvoices.find(i => tx.description.includes(i.invoice_number))
    if (inv) { activeInvoice.value = { ...inv, kind: 'bp_invoice' }; modals.value.viewReceipt = true; return }
  }
  if (tx.modal_type === 'session') {
    const ses = props.clientSessions[0]
    if (ses) { activeInvoice.value = { ...ses, kind: 'session' }; modals.value.viewReceipt = true; return }
  }
  toast.info('Receipt details are not available for this transaction.')
}

function handleReceiptApprove(inv) {
  modals.value.viewReceipt = false
  if (inv.kind === 'bp_invoice') { activeInvoice.value = inv; modals.value.approveInvoice = true }
  else if (inv.kind === 'cs_invoice') { askPayCs({ ...inv, cs_name: inv.cs_name }) }
}

// ── Form: Reject Invoice ─────────────────────────────────────────────────
const rejectForm = useForm({ reason: 'Incorrect amount', message: '' })
function doRejectInvoice() {
  if (!activeInvoice.value) return
  rejectForm.post(route('provider.finances.bp-invoice.reject', { invoice: activeInvoice.value.id }), {
    preserveScroll: true,
    onSuccess: () => { modals.value.rejectInvoice = false; rejectForm.reset(); toast.success('Invoice rejected — Business Partner notified.') },
    onError:   () => toast.error('Please check the form.'),
  })
}

// ── Form: Cancel BP Contract ─────────────────────────────────────────────
const cancelContractForm = useForm({ reason: 'No longer needed', feedback: '' })
function openCancelContract(con) { activeContract.value = con; cancelContractForm.reset(); modals.value.cancelBpContract = true }
function openViewContract(con)   { activeContract.value = con; modals.value.viewContract = true }
function doCancelBpContract() {
  if (!activeContract.value) return
  cancelContractForm.post(route('provider.finances.bp-contract.cancel', { contract: activeContract.value.id }), {
    preserveScroll: true,
    onSuccess: () => { modals.value.cancelBpContract = false; toast.info('Cancellation notice sent — contract cancelled.') },
  })
}

// ── Form: Auto-pay ────────────────────────────────────────────────────────
const autoPayForm = useForm({ enabled: false, day: '1st', method_id: '', notify: '3_days', limit: '' })
function openAutoPay(con) {
  activeContract.value = con
  autoPayForm.enabled = !!con.autopay_enabled
  autoPayForm.day = '1st'; autoPayForm.notify = '3_days'; autoPayForm.limit = ''
  modals.value.autoPay = true
}
function doSaveAutoPay() {
  if (!activeContract.value) return
  autoPayForm.post(route('provider.finances.bp-contract.autopay', { contract: activeContract.value.id }), {
    preserveScroll: true,
    onSuccess: () => { modals.value.autoPay = false; toast.success(autoPayForm.enabled ? 'Auto-pay enabled.' : 'Auto-pay turned off.') },
  })
}

// ── Form: Cancel CS Agreement ────────────────────────────────────────────
const cancelCsForm = useForm({ reason: 'Replacing with another Continuity Steward' })
function openCancelCs(cs) { activeCs.value = cs; cancelCsForm.reset(); modals.value.cancelCsAgreement = true }
function doCancelCsAgreement() {
  if (!activeCs.value) return
  cancelCsForm.post(route('provider.finances.cs-steward.cancel', { steward: activeCs.value.id }), {
    preserveScroll: true,
    onSuccess: () => { modals.value.cancelCsAgreement = false; toast.info('Continuity Steward agreement cancelled.') },
  })
}

// ── Form: CS Pay Model ────────────────────────────────────────────────────
const payModelForm = useForm({ payment_model: 'on_close', fee_cents: 0 })
const payModelFeeUsd = ref(0)
const payModelOptions = [
  { value: 'on_close', label: 'Pay on Close',      icon: 'check',    desc: 'Invoice due immediately when the incident closes.' },
  { value: 'net_30',   label: 'Net 30',            icon: 'calendar', desc: 'Invoice due 30 days after incident close.' },
  { value: 'net_60',   label: 'Net 60',            icon: 'calendar', desc: 'Invoice due 60 days after incident close (institutional CS).' },
]
function openPayArrangement(cs) { activeCs.value = cs; modals.value.payArrangement = true }
function openChangePayModel(cs) {
  activeCs.value = cs
  payModelForm.payment_model = cs.payment_model || 'on_close'
  payModelForm.fee_cents = cs.fee_cents || 0
  payModelFeeUsd.value = (cs.fee_cents || 0) / 100
  modals.value.changePayModel = true
}
function doSavePayModel() {
  if (!activeCs.value) return
  payModelForm.fee_cents = Math.round(Number(payModelFeeUsd.value) * 100)
  payModelForm.put(route('provider.finances.cs-steward.pay-model', { steward: activeCs.value.id }), {
    preserveScroll: true,
    onSuccess: () => { modals.value.changePayModel = false; toast.success('Payment terms updated.') },
  })
}

// ── Payment methods ──────────────────────────────────────────────────────
function setDefaultPm(pm) {
  confirmAction(
    `Set ${(pm.brand || 'card').toUpperCase()} ···· ${pm.last4} as your default payment method? It will fund all Aegis charges going forward.`,
    () => {
      router.post(route('provider.settings.payment.default'), { payment_method_id: pm.id }, {
        preserveScroll: true,
        onSuccess: () => toast.success('Default payment method updated.'),
        onError:   () => toast.error('Could not update default payment method.'),
      })
    }
  )
}
function openRemoveCard(pm) {
  activePm.value = pm
  modals.value.removeCard = true
}
function doRemoveCard() {
  if (!activePm.value || removingCard.value) return
  removingCard.value = true
  router.delete(route('provider.settings.payment.remove'), {
    data: { payment_method_id: activePm.value.id },
    preserveScroll: true,
    onSuccess: () => { modals.value.removeCard = false; toast.info('Payment method removed.') },
    onError:   () => toast.error('Could not remove payment method.'),
    onFinish:  () => { removingCard.value = false },
  })
}

// ── Spending controls ─────────────────────────────────────────────────────
const spendControlsForm = useForm({
  auto_pay:            props.spendingControls?.auto_pay ?? false,
  approval_threshold:  props.spendingControls?.approval_threshold ?? 500,
  monthly_limit:       props.spendingControls?.monthly_limit ?? 5000,
})
function saveSpendingControls() {
  // Client-side validation
  spendControlsForm.errors = {}
  if (spendControlsForm.approval_threshold < 0) spendControlsForm.errors.approval_threshold = 'Must be zero or greater.'
  if (spendControlsForm.monthly_limit < 0)      spendControlsForm.errors.monthly_limit = 'Must be zero or greater.'
  if (Object.keys(spendControlsForm.errors).length) return

  spendControlsForm.post(route('provider.finances.spending-controls'), {
    preserveScroll: true,
    onSuccess: () => toast.success('Spending controls saved.'),
    onError:   () => toast.error('Please check the form.'),
  })
}

// ── Export report — client-side validation + backend post ────────────────
const exportForm = reactive({
  from:     new Date(new Date().setDate(1)).toISOString().slice(0, 10),
  to:       new Date().toISOString().slice(0, 10),
  includes: ['cs', 'bp', 'sessions', 'subscription'],
  format:   'csv',
})
const exportErrors = reactive({ from: '', to: '', includes: '' })
const exportProcessing = ref(false)

function validateExport() {
  exportErrors.from = ''; exportErrors.to = ''; exportErrors.includes = ''
  let ok = true
  if (!exportForm.from) { exportErrors.from = 'Start date is required.'; ok = false }
  if (!exportForm.to)   { exportErrors.to   = 'End date is required.'; ok = false }
  if (exportForm.from && exportForm.to && new Date(exportForm.from) > new Date(exportForm.to)) {
    exportErrors.to = 'End date must be on or after start date.'
    ok = false
  }
  if (!exportForm.includes.length) { exportErrors.includes = 'Choose at least one payment type.'; ok = false }
  return ok
}

function doExport() {
  if (!validateExport()) return
  exportProcessing.value = true
  // Post to backend which returns a file download
  const form = document.createElement('form')
  form.method = 'POST'
  form.action = route('provider.finances.export')
  form.style.display = 'none'
  const addField = (n, v) => {
    const i = document.createElement('input')
    i.type = 'hidden'; i.name = n; i.value = v
    form.appendChild(i)
  }
  addField('_token', document.querySelector('meta[name="csrf-token"]')?.content || '')
  addField('from', exportForm.from)
  addField('to', exportForm.to)
  addField('format', exportForm.format)
  exportForm.includes.forEach(k => addField('includes[]', k))
  document.body.appendChild(form)
  form.submit()
  setTimeout(() => {
    document.body.removeChild(form)
    exportProcessing.value = false
    modals.value.export = false
    toast.success('Report downloaded.')
  }, 500)
}

// ── Helpers ───────────────────────────────────────────────────────────────
const firstPendingName   = computed(() => props.upcomingPayments?.[0]?.recipient ?? '—')
const firstPendingAmount = computed(() => formatCents(props.upcomingPayments?.[0]?.total_cents ?? 0))

function goToPendingTab() {
  const b = props.pendingBreakdown
  if (b.bp?.count > 0)          activeTab.value = 'bp'
  else if (b.session?.count > 0) activeTab.value = 'sessions'
  else                           activeTab.value = 'executor'
}

function formatCents(cents) {
  return '$' + (Number(cents ?? 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatMoney(n) {
  return '$' + Math.round(Number(n ?? 0)).toLocaleString()
}

function formatSubscriptionDate(d) {
  if (!d) return '—'
  if (typeof d === 'number') return new Date(d * 1000).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
  return d
}

function statusVariant(s) {
  return {
    active: 'green', trialing: 'blue', past_due: 'gold', canceled: 'neutral',
    paid: 'green', sent: 'blue', overdue: 'red', draft: 'neutral', void: 'neutral',
    open: 'blue', uncollectible: 'red',
    disputed: 'gold', pending: 'gold', failed: 'red', refunded: 'neutral', partially_refunded: 'gold',
    scheduled: 'blue', completed: 'green', cancelled: 'neutral', no_show: 'red',
  }[s] ?? 'neutral'
}

function payTermsLabel(term) {
  return {
    on_close: 'Pay on Close',
    net_30:   'Net 30',
    net_60:   'Net 60',
    retainer: 'Retainer',
  }[term] ?? 'Pay on Close'
}

function paymentTypeLabel(t) {
  return { bp: 'BP', cs: 'CS', session: 'Session' }[t] ?? ''
}
</script>

<style scoped>
/* ── Two-column overview grid ── */
.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
@media (max-width: 860px) { .two-col { grid-template-columns: 1fr; } }

/* ── Card title with icon ── */
.fin-card-title { display: flex; align-items: center; gap: 8px; }
.fin-card-icon  { width: 28px; height: 28px; border-radius: var(--radius-sm); background: var(--badge-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

/* ── Spend breakdown ── */
.spend-bd            { display: flex; flex-direction: column; gap: 16px; }
.spend-bd-total      { display: flex; align-items: baseline; gap: 8px; }
.spend-bd-total-val  { font-family: var(--font-serif); font-size: 24px; font-weight: 700; color: var(--text); line-height: 1; }
.spend-bd-total-lbl  { font-size: 12px; color: var(--text-3); font-weight: 600; }
.spend-bd-bar        { display: flex; height: 14px; border-radius: var(--radius-full); overflow: hidden; background: var(--surface-3); gap: 2px; }
.spend-bd-bar span   { height: 100%; min-width: 4px; transition: width 0.8s ease; }
.spend-bd-bar--empty { background: var(--surface-3); }
.spend-bd-list  { display: flex; flex-direction: column; }
.spend-bd-row   { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--border); }
.spend-bd-row:last-child { border-bottom: none; padding-bottom: 0; }
.spend-bd-dot   { width: 10px; height: 10px; border-radius: var(--radius-full); flex-shrink: 0; }
.spend-bd-name  { flex: 1; font-size: 13px; font-weight: 600; color: var(--text-2); }
.spend-bd-amt   { font-size: 13px; font-weight: 700; color: var(--text); }
.spend-bd-pct   { font-size: 11px; font-weight: 700; color: var(--text-4); width: 40px; text-align: right; flex-shrink: 0; }

/* ── Upcoming payments — minimal ── */
.upcoming-list  { display: flex; flex-direction: column; }
.upcoming-row   { display: flex; align-items: center; gap: 10px; padding: 10px 8px; border-bottom: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; transition: background var(--transition); }
.upcoming-row:hover { background: var(--surface-2); }
.upcoming-row:last-child { border-bottom: none; }
.upcoming-info  { flex: 1; min-width: 0; overflow: hidden; }
.upcoming-name  { font-size: 13px; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.upcoming-desc  { font-size: 11px; color: var(--text-4); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.upcoming-right { text-align: right; flex-shrink: 0; }
.upcoming-amount        { font-size: 13px; font-weight: 700; color: var(--text); white-space: nowrap; }
.upcoming-amount--urgent { color: var(--orange-dark); }
.upcoming-due           { font-size: 11px; color: var(--text-4); margin-top: 1px; }
.upcoming-due--urgent   { color: var(--orange-dark); font-weight: 600; }
.upcoming-kind-badge      { font-size: 9px; font-weight: 700; letter-spacing: 0.4px; padding: 2px 7px; border-radius: var(--radius-full); text-transform: uppercase; flex-shrink: 0; white-space: nowrap; }
.upcoming-kind-badge--bp  { background: var(--green-light);  color: var(--green-dark); }
.upcoming-kind-badge--cs  { background: var(--badge-bg-gold); color: var(--gold-dark); }
.upcoming-kind-badge--session { background: var(--teal-light); color: var(--teal-dark); }
.upcoming-more  { padding: 8px 0; text-align: center; font-size: 12px; color: var(--gold-dark); cursor: pointer; font-weight: 600; }
.upcoming-more:hover { color: var(--text); }

/* ── CS payment cards ── */
.cspay-card        { position: relative; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 14px; }
.cspay-card::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--gold-dark); }
.cspay-body        { padding: 22px 24px; }
.cspay-top         { display: flex; align-items: center; gap: 14px; }
.cspay-id          { flex: 1; min-width: 0; }
.cspay-name-row    { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; }
.cspay-name        { font-family: var(--font-serif); font-size: 16px; font-weight: 600; color: var(--text); text-decoration: none; }
.cspay-name:hover  { color: var(--gold-dark); }
.cspay-role        { font-size: 12px; color: var(--text-3); margin-top: 3px; }
.cspay-actions     { display: flex; gap: 6px; flex-shrink: 0; }
.cspay-meta        { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin: 20px 0; padding: 18px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
.cspay-meta-item:not(:last-child) { border-right: 1px solid var(--border); }
.cspay-meta-label  { font-size: 10px; font-weight: 600; letter-spacing: 0.8px; text-transform: uppercase; color: var(--text-4); margin-bottom: 7px; }
.cspay-meta-value  { font-family: var(--font-serif); font-weight: 600; color: var(--text); line-height: 1; font-size: 15px; }
.cspay-meta-value.amount { font-size: 22px; }
.cspay-note        { display: flex; align-items: flex-start; gap: 10px; padding: 13px 15px; border-radius: var(--radius); background: var(--blue-light); border: 1px solid var(--soft-blue); }
.cspay-note p      { font-size: 12px; color: var(--text-2); line-height: 1.5; margin: 0; }
.cspay-note-icon         { color: var(--gold-dark); flex-shrink: 0; margin-top: 1px; }
.cspay-note-icon--warning { color: var(--orange-dark); flex-shrink: 0; margin-top: 1px; }
.cspay-note--warning     { background: var(--orange-light); border-color: var(--soft-gold); }
.cspay-note--warning p   { color: var(--orange-dark); }

/* ── Stripe Connect pill ── */
.connect-pill         { display: inline-flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; letter-spacing: 0.4px; text-transform: uppercase; padding: 3px 9px; border-radius: var(--radius-full); white-space: nowrap; }
.connect-pill.is-connected     { background: var(--green-light);  color: var(--green-dark); }
.connect-pill.is-not-connected { background: var(--orange-light); color: var(--orange-dark); }
.connect-pill .status-dot      { width: 6px; height: 6px; border-radius: var(--radius-full); flex-shrink: 0; }
.connect-pill.is-connected .status-dot     { background: var(--green-dark); }
.connect-pill.is-not-connected .status-dot { background: var(--orange-dark); }

/* ── Invoice cards ── */
.invoice-card        { position: relative; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 14px; }
.invoice-card::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; }
.invoice-card.pending-approval::before { background: var(--orange); }
.invoice-card.active-contract::before  { background: var(--green); }
.invoice-card.session-card::before     { background: var(--teal); }
.invoice-body        { padding: 22px 24px 20px; }
.invoice-status      { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
.invoice-status-right { display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.invoice-auto        { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-4); }
.invoice-head        { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; margin-bottom: 20px; }
.invoice-vendor      { font-family: var(--font-serif); font-size: 20px; font-weight: 600; color: var(--text); line-height: 1.2; }
.invoice-service     { font-size: 13px; color: var(--text-3); margin-top: 5px; }
.invoice-amount      { font-family: var(--font-serif); font-size: 28px; font-weight: 700; color: var(--text); line-height: 1; text-align: right; }
.invoice-period      { font-size: 12px; color: var(--text-4); margin-top: 5px; text-align: right; }
.invoice-meta        { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px 18px; padding: 16px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); margin-bottom: 18px; }
.invoice-meta-label  { font-size: 10px; font-weight: 600; letter-spacing: 0.8px; text-transform: uppercase; color: var(--text-4); margin-bottom: 5px; }
.invoice-meta-value  { font-size: 13px; font-weight: 600; color: var(--text-2); }
.invoice-meta-value.due { color: var(--orange-dark); font-weight: 700; }
.invoice-actions     { display: flex; align-items: center; gap: 10px; }
.btn-primary-full    { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 7px; height: 40px; padding: 0 18px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 700; background: var(--text); color: var(--text-inverted); border: none; cursor: pointer; }
.btn-primary-full:hover { background: var(--text-2); }
.invoice-actions .btn-icon { width: 40px; height: 40px; }

/* ── BP tab sub-filter pills — self-contained, no overflow ── */

/* ── Payment method cards ── */
.pm-card          { display: flex; align-items: center; gap: 14px; padding: 14px 18px; border: 1px solid var(--border); border-radius: var(--radius-lg); margin-bottom: 10px; background: var(--surface); box-shadow: var(--shadow-sm); }
.pm-card.default  { border-color: var(--gold-dark); background: var(--badge-bg-gold); }
.pm-logo          { width: 48px; height: 32px; border-radius: var(--radius-sm); background: var(--surface-2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.pm-info          { flex: 1; }
.pm-name          { font-size: 13px; font-weight: 700; color: var(--text); }
.pm-meta          { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.pm-card-btns     { display: flex; gap: 6px; }

/* ── Subscription cart card ── */
.sub-cart             { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); }
.sub-cart-head        { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 18px 22px; background: var(--badge-bg-gold); border-bottom: 1px solid var(--badge-border-gold); }
.sub-cart-head-left   { display: flex; align-items: center; gap: 12px; }
.sub-cart-logo        { width: 40px; height: 40px; border-radius: var(--radius); background: var(--gold-dark); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sub-cart-brand       { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); line-height: 1.2; }
.sub-cart-type        { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.sub-cart-items       { padding: 8px 0; }
.sub-cart-item        { display: flex; align-items: center; gap: 14px; padding: 14px 22px; border-bottom: 1px solid var(--border); }
.sub-cart-item:last-child { border-bottom: none; }
.sub-cart-item--addon { background: var(--surface-2); }
.sub-cart-item-icon   { width: 34px; height: 34px; border-radius: var(--radius-sm); background: var(--green-light); color: var(--green-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sub-cart-item-icon--gold { background: var(--badge-bg-gold); color: var(--gold-dark); }
.sub-cart-item-info   { flex: 1; min-width: 0; }
.sub-cart-item-name   { font-size: 14px; font-weight: 700; color: var(--text); }
.sub-cart-item-desc   { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.sub-cart-item-price  { font-family: var(--font-serif); font-size: 20px; font-weight: 700; color: var(--text); white-space: nowrap; margin-right: 10px; }
.sub-cart-item-per    { font-family: var(--font-sans); font-size: 12px; font-weight: 400; color: var(--text-3); }
.sub-cart-total       { display: flex; align-items: center; justify-content: space-between; padding: 14px 22px; background: var(--surface-2); border-top: 1px solid var(--border); }
.sub-cart-total-label { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-3); font-weight: 600; }
.sub-cart-total-amount { font-family: var(--font-serif); font-size: 22px; font-weight: 700; color: var(--text); }
.sub-cart-alert       { display: flex; align-items: center; gap: 8px; padding: 10px 22px; background: var(--orange-light); border-top: 1px solid var(--soft-gold); font-size: 12px; color: var(--orange-dark); font-weight: 600; }

/* ── Invoice table rows ── */
.sub-invoice-table .sub-inv-row { cursor: pointer; }
.sub-invoice-table .sub-inv-row:hover td { background: var(--surface-2); }
.sub-inv-product  { font-size: 13px; font-weight: 600; color: var(--text); }
.sub-inv-desc     { font-size: 11px; color: var(--text-4); margin-top: 2px; font-family: var(--font-mono, monospace); }

/* ── Subscription Invoice Modal ── */
.sub-inv-modal    { display: flex; flex-direction: column; gap: 0; }
.sim-header       { display: flex; align-items: center; gap: 14px; padding: 18px 20px; background: var(--badge-bg-gold); border: 1px solid var(--badge-border-gold); border-radius: var(--radius); margin-bottom: 16px; }
.sim-logo         { width: 44px; height: 44px; border-radius: var(--radius); background: var(--gold-dark); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sim-brand        { flex: 1; }
.sim-from         { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); }
.sim-sub          { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.sim-status-block { text-align: right; flex-shrink: 0; }
.sim-date         { font-size: 12px; color: var(--text-3); margin-top: 5px; }
.sim-number-row   { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
.sim-number-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: var(--text-4); }
.sim-number       { font-family: var(--font-mono, monospace); font-size: 13px; font-weight: 600; color: var(--text-2); background: var(--surface-2); padding: 3px 10px; border-radius: var(--radius-sm); border: 1px solid var(--border); }
.sim-items        { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 14px; }
.sim-item         { display: flex; align-items: center; gap: 12px; padding: 14px 16px; background: var(--surface); }
.sim-item-icon    { width: 32px; height: 32px; border-radius: var(--radius-sm); background: var(--green-light); color: var(--green-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sim-item-name    { flex: 1; font-size: 14px; font-weight: 600; color: var(--text); }
.sim-item-price   { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); white-space: nowrap; }
.sim-totals       { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 14px; }
.sim-total-row    { display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; font-size: 13px; color: var(--text-2); border-bottom: 1px solid var(--border); }
.sim-total-row:last-child { border-bottom: none; }
.sim-total-row--main { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); background: var(--surface-2); padding: 14px 16px; }
.sim-fine-print   { display: flex; align-items: center; gap: 8px; font-size: 11px; color: var(--text-4); padding: 10px 14px; background: var(--surface-2); border-radius: var(--radius-sm); border: 1px solid var(--border); line-height: 1.5; }

/* ── Spending controls ── */
.spending-input-wrap { display: flex; align-items: center; gap: 6px; }
.spending-prefix     { font-size: 14px; font-weight: 700; color: var(--text-3); }
.setting-save-row    { display: flex; justify-content: flex-end; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border); }

/* ── Transaction table + toolbar (single-line: 6-6 grid) ── */
.fin-toolbar         { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; align-items: center; margin-bottom: 16px; }
.fin-toolbar-search  { min-width: 0; }
.fin-toolbar-filters { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
@media (max-width: 720px) {
  .fin-toolbar         { grid-template-columns: 1fr; }
  .fin-toolbar-filters { grid-template-columns: 1fr 1fr 1fr; }
}
.fin-tx-table    { width: 100%; }
.tx-date         { padding-left: 20px; white-space: nowrap; color: var(--text-3); }
.tx-payee        { font-weight: 700; color: var(--text); }
.tx-desc         { font-size: 13px; color: var(--text-2); }
.tx-method       { font-size: 12px; color: var(--text-3); }
.tx-action       { padding-right: 20px; text-align: right; width: 48px; }
.tx-cat-wrap     { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; }
.tx-cat-dot      { width: 8px; height: 8px; border-radius: var(--radius-full); display: inline-block; flex-shrink: 0; }
.tx-amount-out   { font-weight: 700; color: var(--red-dark); }
.tx-amount-in    { font-weight: 700; color: var(--green-dark); }

/* ── Receipt (for approve modal) ── */
.receipt      { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; }
.receipt-row  { display: flex; justify-content: space-between; font-size: 13px; padding: 5px 0; }
.receipt-row.total { border-top: 1px solid var(--border-dark); font-weight: 700; font-size: 14px; padding-top: 10px; margin-top: 4px; }

/* ── Contract preview ── */
.contract-preview       { background: var(--surface-2); border-radius: var(--radius); padding: 16px; font-size: 13px; color: var(--text-2); line-height: 1.8; border: 1px solid var(--border); }
.contract-preview-title { font-family: var(--font-serif); font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
.contract-preview-row   { color: var(--text-2); }

/* ── Export checks ── */
.export-checks { display: flex; flex-direction: column; gap: 8px; margin-top: 6px; }

/* ── Role option (pay model selector) ── */
.role-option          { display: flex; align-items: flex-start; gap: 12px; padding: 12px 14px; border-radius: var(--radius); border: 1px solid var(--border); margin-bottom: 8px; cursor: pointer; }
.role-option:hover    { background: var(--surface-2); border-color: var(--border-dark); }
.role-option.selected { border-color: var(--gold-dark); background: var(--badge-bg-gold); }

/* ── Misc ── */
.btn-dark        { background: var(--text); border: 1px solid var(--text); color: var(--text-inverted); font-size: 13px; font-weight: 700; }
.btn-dark:hover  { background: var(--text-2); border-color: var(--text-2); }

/* ── Integrations tab ── */
.stripe-setup-card      { border: 1px solid var(--badge-border-gold); border-radius: var(--radius-lg); background: var(--icon-bg-gold); overflow: hidden; }
.stripe-setup-inner     { display: flex; gap: 16px; padding: 18px 20px; align-items: flex-start; }
.stripe-setup-icon      { width: 44px; height: 44px; border-radius: var(--radius); background: var(--gold-dark); color: var(--text-inverted); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stripe-setup-body      { flex: 1; min-width: 0; }
.stripe-setup-title     { font-family: var(--font-serif); font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
.stripe-setup-desc      { font-size: 13px; color: var(--text-2); line-height: 1.5; margin-bottom: 12px; }
.stripe-setup-connected { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.stripe-setup-actions   { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.app-status-connected   { font-size: 12px; font-weight: 600; color: var(--green-dark); background: var(--green-light); padding: 3px 10px; border-radius: var(--radius-full); display: inline-flex; align-items: center; gap: 5px; }
.integrations-empty     { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 36px 20px; text-align: center; color: var(--text-3); }

/* ── Finances layout: sidebar + content ── */
.fin-layout  { display: flex; align-items: flex-start; gap: 22px; }
.fin-content { flex: 1; min-width: 0; }

@media (max-width: 860px) {
  .fin-layout  { flex-direction: column; }
  .page-sidebar { width: 100% !important; position: static; }
  .page-sidebar-group { display: flex; flex-wrap: wrap; padding: 4px 6px; }
  .page-sidebar-label { display: none; }
  .page-sidebar-item  { width: auto; flex: 0 0 auto; border-left: none; border-radius: var(--radius-sm); padding: 6px 12px; font-size: 12px; }
  .page-sidebar-item.active::before { display: none; }
  .page-sidebar-icon  { display: none; }
}
.spin { animation: fin-spin 0.7s linear infinite; display: inline-block; }
@keyframes fin-spin { to { transform: rotate(360deg); } }
</style>