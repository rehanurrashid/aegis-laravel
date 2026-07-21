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
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'sessions' }" @click="activeTab = 'sessions'; sessionSubTab = 'booked'; clientTableRef.value?.reset(); providerTableRef.value?.reset()">
            <span class="page-sidebar-icon"><AegisIcon name="heart" :size="15" /></span>
            Clinical Sessions
            <span v-if="sessionPendingCount + activeDisputeRefundRequests.length > 0" class="page-sidebar-badge">{{ sessionPendingCount + activeDisputeRefundRequests.length }}</span>
          </button>
        </div>

        <div class="page-sidebar-group">
          <div class="page-sidebar-label">Manage</div>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'subscription' }" @click="activeTab = 'subscription'">
            <span class="page-sidebar-icon"><AegisIcon name="star" :size="15" /></span>
            Subscriptions &amp; Invoices
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'methods' }" @click="activeTab = 'methods'">
            <span class="page-sidebar-icon"><AegisIcon name="credit-card" :size="15" /></span>
            Payment Methods
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'history' }" @click="activeTab = 'history'">
            <span class="page-sidebar-icon"><AegisIcon name="clock" :size="15" /></span>
            Transactions
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'stripe_connect' }" @click="activeTab = 'stripe_connect'">
            <span class="page-sidebar-icon"><AegisIcon name="link" :size="15" /></span>
            Stripe Connect
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
                <span class="upcoming-kind-badge" :class="'upcoming-kind-badge--' + (inv.payment_type === 'escrow' ? 'escrow' : inv.payment_type)">
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
        <AegisStatChip icon="shield" :value="formatMoney(csAgreedTotal / 100)" label="Agreed CS Fees"            bg-color="var(--badge-bg-gold)" icon-color="var(--gold-dark)" />
        <AegisStatChip icon="file-text" :value="feeCs.length"                  label="CS Retainers with Fee"    bg-color="var(--badge-bg-gold)" icon-color="var(--gold-dark)" />
        <AegisStatChip icon="users"     :value="csStewards.length"              label="Active Stewards"           bg-color="var(--badge-bg-gold)" icon-color="var(--gold-dark)" />
      </div>

      <div class="alert alert-info" style="margin-bottom:20px;">
        <div class="alert-icon"><AegisIcon name="shield" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">Standing Retainers · Invoiced Per Incident</div>
          <div>Your CS agreements are standing retainers — active from signing until cancelled. Invoices generate automatically when a critical incident closes and CS tasks are marked complete. You have 7 days to pay manually before your default card is auto-charged. Aegis never holds these funds.</div>
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


      <!-- ── CS Steward table — slim, row click opens detail modal ── -->
      <div v-if="csStewards.length" class="sic-table-wrap">
        <table class="sic-table">
          <thead>
            <tr>
              <th class="sic-th">Steward</th>
              <th class="sic-th">Fee / Activation</th>
              <th class="sic-th">Status</th>
              <th class="sic-th"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="cs in csStewards" :key="cs.id"
              class="sic-row sic-row--clickable"
              :class="{ 'sic-row--warning': !cs.stripe_connected || cs.has_pending_fee_amendment }"
              @click="openCsDetail(cs)"
            >
              <td class="sic-td sic-td--party">
                <div class="sic-party">
                  <div class="sic-avatar sic-avatar--gold">
                    <span class="sic-avatar-initials">{{ cs.initials }}</span>
                  </div>
                  <div class="sic-party-info">
                    <span class="sic-party-name">{{ cs.display_name }}</span>
                    <span class="sic-service-name">{{ cs.role_label }}</span>
                    <span v-if="!cs.stripe_connected" class="sic-date-sub" style="color:var(--orange-dark);">
                      <AegisIcon name="alert-triangle" :size="11" /> Stripe not connected
                    </span>
                    <span v-else-if="cs.has_pending_fee_amendment" class="sic-date-sub" style="color:var(--orange-dark);">
                      <AegisIcon name="clock" :size="11" /> Fee amendment pending
                    </span>
                  </div>
                </div>
              </td>
              <td class="sic-td">
                <div style="display:flex;flex-direction:column;gap:3px;">
                  <span style="font-weight:700;font-family:var(--font-serif);font-size:15px;">{{ formatCents(cs.fee_cents) }}</span>
                  <span style="font-size:11px;color:var(--text-4);">On incident close · Auto-charge after 7 days</span>
                </div>
              </td>
              <td class="sic-td">
                <div class="sic-badges">
                  <span class="connect-pill" :class="cs.stripe_connected ? 'is-connected' : 'is-not-connected'">
                    <span class="status-dot"></span>
                    {{ cs.stripe_connected ? 'Connected' : 'Not Connected' }}
                  </span>
                  <AegisBadge
                    v-if="cs.invoices && cs.invoices.filter(i => i.payable).length"
                    :label="cs.invoices.filter(i => i.payable).length + ' due'"
                    variant="gold"
                  />
                </div>
              </td>
              <td class="sic-td sic-td--actions" @click.stop>
                <button type="button" class="btn-icon" data-tooltip="View details &amp; actions" @click="openCsDetail(cs)">
                  <AegisIcon name="chevron-right" :size="15" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ── CS Detail Modal ── -->
      <AegisModal v-model="modals.csDetail" :title="activeCs?.display_name ?? 'Steward Details'" size="lg">
        <template v-if="activeCs">
          <div class="csm-header">
            <div class="csm-avatar sic-avatar--gold">{{ activeCs.initials }}</div>
            <div class="csm-id">
              <div class="csm-name">
                <a :href="profileHref(activeCs.slug, 'cs')" target="_blank" class="csm-name-link">{{ activeCs.display_name }}</a>
                <AegisBadge :label="activeCs.role_label" variant="gold" />
              </div>
              <div class="csm-meta-row">
                <span class="connect-pill" :class="activeCs.stripe_connected ? 'is-connected' : 'is-not-connected'">
                  <span class="status-dot"></span>
                  {{ activeCs.stripe_connected ? 'Stripe Connected' : 'Not Connected' }}
                </span>
                <AegisBadge
                  v-if="activeCs.invoices && activeCs.invoices.filter(i => i.payable).length"
                  :label="activeCs.invoices.filter(i => i.payable).length + ' invoice' + (activeCs.invoices.filter(i => i.payable).length > 1 ? 's' : '') + ' due'"
                  variant="gold"
                />
              </div>
            </div>
          </div>

          <div v-if="!activeCs.stripe_connected" class="cspay-note cspay-note--warning" style="margin:14px 0 0;">
            <span class="cspay-note-icon--warning"><AegisIcon name="alert-triangle" :size="16" /></span>
            <p><strong>{{ activeCs.display_name }} hasn't finished Stripe Connect onboarding.</strong>
            The agreed fee can't be routed automatically until they complete setup in their portal.</p>
          </div>
          <div v-if="activeCs.has_pending_fee_amendment" class="cspay-note cspay-note--warning" style="margin:10px 0 0;">
            <span class="cspay-note-icon--warning"><AegisIcon name="clock" :size="16" /></span>
            <p>A fee amendment is pending <strong>{{ activeCs.display_name }}'s</strong> countersignature.
            Current fee of <strong>{{ formatCents(activeCs.fee_cents) }}</strong> stays in effect until signed.
            <a :href="route('provider.documents.index')" style="color:var(--gold-dark)">View in Documents →</a></p>
          </div>

          <div class="csm-facts">
            <div class="csm-fact">
              <div class="csm-fact-label">Agreed Fee</div>
              <div class="csm-fact-value amount">{{ formatCents(activeCs.fee_cents) }}</div>
            </div>
            <div class="csm-fact">
              <div class="csm-fact-label">Billing Trigger</div>
              <div class="csm-fact-value">On incident close</div>
            </div>
            <div class="csm-fact">
              <div class="csm-fact-label">Grace Period</div>
              <div class="csm-fact-value">7 days</div>
            </div>
          </div>

          <div class="cspay-note" style="margin-bottom:0;">
            <span class="cspay-note-icon"><AegisIcon name="shield" :size="16" /></span>
            <p>Once the critical incident closes and CS tasks are marked complete, an invoice is generated automatically against this retainer. You have <strong>7 days</strong> to pay manually via <em>Pay Now</em> — after that, your default payment method is auto-charged. Aegis never holds these funds.</p>
          </div>

          <div style="margin-top:20px;">
            <div class="csdt-inv-header">
              <span class="csdt-inv-label">Invoice History</span>
              <nav class="tabs-segmented" style="width:fit-content;">
                <button type="button" class="tab-pill" :class="{ active: (csInvFilter[activeCs.id] ?? 'all') === 'all' }" @click="setCsInvFilter(activeCs.id, 'all')">All</button>
                <button type="button" class="tab-pill" :class="{ active: csInvFilter[activeCs.id] === 'payable' }" @click="setCsInvFilter(activeCs.id, 'payable')">
                  Due <span v-if="activeCs.invoices?.filter(i => i.payable).length" class="sessions-chip-count">{{ activeCs.invoices.filter(i => i.payable).length }}</span>
                </button>
                <button type="button" class="tab-pill" :class="{ active: csInvFilter[activeCs.id] === 'paid' }" @click="setCsInvFilter(activeCs.id, 'paid')">Paid</button>
                <button type="button" class="tab-pill" :class="{ active: csInvFilter[activeCs.id] === 'disputed' }" @click="setCsInvFilter(activeCs.id, 'disputed')">Disputed</button>
              </nav>
            </div>
            <div class="csdt-inv-table-wrap">
            <table v-if="activeCs.invoices?.length" class="sic-table csdt-inv-table">
              <thead>
                <tr>
                  <th class="sic-th">Invoice</th>
                  <th class="sic-th">Issued</th>
                  <th class="sic-th">Amount</th>
                  <th class="sic-th">Status</th>
                  <th class="sic-th"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="inv in paginatedCsInvoices(activeCs)" :key="inv.id" class="sic-row">
                  <td class="sic-td">{{ inv.invoice_number }}</td>
                  <td class="sic-td">{{ inv.issued_at ?? '—' }}</td>
                  <td class="sic-td" style="font-weight:700;font-family:var(--font-serif)">{{ formatCents(inv.total_cents) }}</td>
                  <td class="sic-td">
                    <AegisBadge
                      :label="inv.status"
                      :variant="inv.status === 'paid' ? 'green' : inv.status === 'disputed' ? 'red' : inv.payable ? 'gold' : 'neutral'"
                    />
                  </td>
                  <td class="sic-td sic-td--actions">
                    <button v-if="inv.payable && has_valid_default_pm" type="button" class="btn btn-primary" @click="askPayCs(inv)">Pay Now</button>
                    <button v-else-if="inv.payable && !has_valid_default_pm" type="button" class="btn btn-outline" @click="modals.csDetail = false; activeTab = 'methods'">Add Card</button>
                    <span v-if="inv.payable && inv.days_until_autocharge > 0" style="font-size:11px;color:var(--text-3);margin-left:6px;">Auto-charge in {{ inv.days_until_autocharge }} day{{ inv.days_until_autocharge !== 1 ? 's' : '' }}</span>
                    <button v-if="inv.disputed" type="button" class="btn btn-outline" @click="openDisputeDetail(inv)">View Dispute</button>
                    <button type="button" class="btn-icon btn-icon-sm" data-tooltip="View invoice" @click="viewCsInvoice(inv)">
                      <AegisIcon name="eye" :size="13" />
                    </button>
                  </td>
                </tr>
                <tr v-if="!filteredCsInvoices(activeCs).length">
                  <td colspan="5" style="text-align:center;padding:16px;color:var(--text-3);font-size:13px;">
                    No invoices {{ (csInvFilter[activeCs.id] ?? 'all') !== 'all' ? 'in this filter' : 'yet' }}.
                  </td>
                </tr>
              </tbody>
            </table>
            </div>
            <div v-if="activeCs.invoices?.length && csTotalPages(activeCs) > 1" style="padding:10px 0 0;display:flex;justify-content:center;">
              <AegisPagination
                :current-page="csInvPage[activeCs.id] ?? 1"
                :total-pages="csTotalPages(activeCs)"
                @change="p => setCsInvPage(activeCs.id, p)"
              />
            </div>
            <AegisEmptyState
              v-if="!activeCs.invoices?.length"
              icon="file-text"
              title="No invoices yet"
              description="Invoices appear here when a critical incident closes and the CS fee becomes due."
            />
          </div>
        </template>

        <template #footer>
          <div style="font-size:11px;color:var(--text-3);flex:1;display:flex;align-items:center;gap:4px;white-space:nowrap;">
            <AegisIcon name="info" :size="11" />
            Manage retainer status in <a :href="route('provider.stewards.index')" style="color:var(--gold-dark);">Continuity Stewards</a>
          </div>
          <button type="button" class="btn btn-outline" @click="modals.csDetail = false">Close</button>
        </template>
      </AegisModal>
    </div>

    <!-- ══════════════════════════════ TAB: BUSINESS PARTNERS ══════════════════════════════ -->
    <div v-show="activeTab === 'bp'">

      <BpFinanceTable
        :invoices="bpInvoices"
        :contracts="activeContracts"
        :invoices-by-contract="invoicesByContract"
        :escrow-summary="escrowSummary"
        :has-payment-method="has_valid_default_pm"
      />

    </div>
    <!-- ══════════════════════════════ TAB: CLINICAL SESSIONS ══════════════════════════════ -->
    <!-- ══════════════════════════════════════════════════════════════════
         TAB: CLINICAL SESSIONS (Wave 6 — two-section rebuild)
    ══════════════════════════════════════════════════════════════════ -->
    <div v-show="activeTab === 'sessions'">

      <!-- ── PENDING REFUND REQUESTS (actionable) ────────────────────── -->
      <div v-if="activeDisputeRefundRequests.length" class="sessions-refund-alerts">
        <div class="sessions-refund-alerts-header">
          <AegisIcon name="alert-circle" :size="15" />
          {{ activeDisputeRefundRequests.length }} pending refund request{{ activeDisputeRefundRequests.length !== 1 ? 's' : '' }} need your response
        </div>
        <div class="sessions-refund-list">
          <div
            v-for="rr in activeDisputeRefundRequests"
            :key="rr.id"
            class="sessions-refund-row"
          >
            <div class="sessions-refund-info">
              <span class="sessions-refund-name">{{ rr.requested_by_name }}</span>
              <span class="sessions-refund-sep">·</span>
              <span class="sessions-refund-service">{{ rr.service_title }}</span>
              <span class="sessions-refund-sep">·</span>
              <span class="sessions-refund-amount">{{ rr.amount_requested }}</span>
              <AegisBadge v-if="rr.is_overdue" label="Overdue" variant="red" style="margin-left:6px" />
            </div>
            <button
              type="button"
              class="btn btn-outline"
              @click="activeRefundRequest = rr; modals.sessionReviewRefund = true"
            >
              <AegisIcon name="eye" :size="13" /> Review
            </button>
          </div>
        </div>
      </div>

      <!-- ── PRIMARY TABS: Booked vs Providing ──────────────────────── -->
      <div class="tabs-primary" role="tablist" style="margin-bottom:20px">
        <button
          type="button" role="tab"
          class="tab-primary"
          :class="{ active: sessionSubTab === 'booked' }"
          :aria-selected="sessionSubTab === 'booked'"
          @click="sessionSubTab = 'booked'; clientSessionFilter = 'all'"
        >
          <AegisIcon name="user" :size="14" />
          Sessions I've Booked
          <span class="tab-count">{{ clientSessions.length }}</span>
        </button>
        <button
          type="button" role="tab"
          class="tab-primary"
          :class="{ active: sessionSubTab === 'providing' }"
          :aria-selected="sessionSubTab === 'providing'"
          @click="sessionSubTab = 'providing'; providerSessionFilter = 'all'"
        >
          <AegisIcon name="briefcase" :size="14" />
          Sessions I'm Providing
          <span class="tab-count">{{ providerSessions.length }}</span>
        </button>
      </div>

      <!-- ── SECTION A: Sessions I've Booked ───────────────────────── -->
      <div v-show="sessionSubTab === 'booked'">
        <p class="sessions-section-desc" style="margin-bottom:12px">
          Payments you owe — upfront portion due at booking, completion payment after the session.
        </p>
        <BookedSessionTable
          ref="clientTableRef"
          :sessions="clientSessions"
          :show-invoice="true"
          empty-title="No clinical sessions booked"
          empty-subtitle="Browse other practitioners' services to book supervision, consultation, training, and more."
          @pay-deposit="activeClientSession = $event; modals.sessionPayUpfront = true"
          @pay-balance="activeClientSession = $event; modals.sessionPayCompletion = true"
          @request-refund="activeClientSession = $event; modals.sessionRequestRefund = true"
          @escalate-refund="escalateSessionRefund($event)"
          @open-invoice="activeClientSession = $event; modals.sessionClientInvoice = true"
        >
          <template #empty>
            <a :href="route('provider.services.index') + '?tab=explore'" class="btn btn-primary">
              <AegisIcon name="search" :size="13" /> Explore Services
            </a>
          </template>
        </BookedSessionTable>
      </div>

      <!-- ── SECTION B: Sessions I'm Providing ─────────────────────── -->
      <div v-show="sessionSubTab === 'providing'">
        <p class="sessions-section-desc" style="margin-bottom:12px">
          Payments coming to you — upfront portion received when client books, completion payment when they confirm.
        </p>
        <SessionTable
          ref="providerTableRef"
          :sessions="providerSessions"
          viewpoint="provider"
          :show-invoice="true"
          empty-icon="briefcase"
          empty-title="No provider sessions yet"
          empty-subtitle="When another practitioner books your service and pays their deposit, sessions appear here."
          @review-refund="activeRefundRequest = incomingRefundRequests.find(r => r.session_id === $event.id); modals.sessionReviewRefund = true"
          @open-invoice="activeProviderSession = $event; modals.sessionProviderInvoice = true"
          @cancel-session="openSessionDispute($event)"
        />
      </div>

      <!-- Rev 4 session payment modals -->
      <PayUpfrontModal
        v-model="modals.sessionPayUpfront"
        :session="activeClientSession"
        @success="activeClientSession = null"
      />
      <PayCompletionModal
        v-model="modals.sessionPayCompletion"
        :session="activeClientSession"
        @success="activeClientSession = null"
      />
      <!-- Legacy (one-cycle BC) -->
      <PayDepositModal
        v-model="modals.sessionPayDeposit"
        :session="activeClientSession"
        @success="activeClientSession = null"
      />
      <PayBalanceModal
        v-model="modals.sessionPayBalance"
        :session="activeClientSession"
        @success="activeClientSession = null"
      />
      <RequestRefundModal
        v-model="modals.sessionRequestRefund"
        :session="activeClientSession"
        @success="activeClientSession = null"
      />
      <SessionInvoiceModal
        v-model="modals.sessionClientInvoice"
        :session="activeClientSession"
        viewpoint="client"
      />
      <SessionInvoiceModal
        v-model="modals.sessionProviderInvoice"
        :session="activeProviderSession"
        viewpoint="provider"
      />
      <ReviewRefundRequestModal
        v-model="modals.sessionReviewRefund"
        :refund-request="activeRefundRequest"
        @success="activeRefundRequest = null"
      />

    </div>

    <!-- ══════════════════════════════ TAB: SUBSCRIPTION ══════════════════════════════ -->
    <div v-show="activeTab === 'subscription'">
      <SubscriptionPlanCard
        :subscription="subscription"
        :invoices="subscriptionInvoices"
        :show-manage-link="true"
        :show-invoices="true"
        :billing-interval="subscription?.billing_interval ?? 'monthly'"
        :pricing="$page.props.pricing"
      />
    </div>

    <!-- ══════════════════════════════ TAB: PAYMENT METHODS ══════════════════════════════ -->
    <div v-show="activeTab === 'methods'">
      <div class="card" style="margin-bottom:18px;">
        <div class="card-header">
          <div class="card-title fin-card-title">
            <span class="fin-card-icon"><AegisIcon name="credit-card" :size="15" /></span>
            Saved Payment Methods
          </div>
          <a :href="route('provider.settings.index') + '?section=payment_methods'" class="btn btn-outline">
            <AegisIcon name="external-link" :size="12" /> Manage Payment Methods
          </a>
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
                <AegisIcon v-if="pm.is_default" name="shield-check" :size="16" style="color:var(--gold-dark);" data-tooltip="Default payment method" />
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
    <div v-show="activeTab === 'stripe_connect'">
      <div class="card">
        <div class="card-header">
          <div class="card-title fin-card-title">
            <span class="fin-card-icon"><AegisIcon name="link" :size="15" /></span>
            Stripe Connect
          </div>
          <a :href="route('provider.settings.index') + '?section=stripe_connect'" class="btn btn-outline">
            <AegisIcon name="external-link" :size="12" /> Manage Stripe
          </a>
        </div>
        <div class="card-body">
          <div class="stripe-setup-card">
            <div class="stripe-setup-inner">
              <div class="stripe-setup-icon"><AegisIcon name="credit-card" :size="22" /></div>
              <div class="stripe-setup-body">
                <div class="stripe-setup-title">Stripe Connect Express</div>
                <div class="stripe-setup-desc">
                  Receive payments from clients booking your services. Funds go directly to your bank account — Aegis never holds your money.
                </div>
                <div style="margin-top:10px;">
                  <span v-if="stripeConnected" class="app-status-connected">
                    <AegisIcon name="check" :size="13" /> Connected
                  </span>
                  <span v-else class="app-status-disconnected">
                    <AegisIcon name="alert-triangle" :size="13" /> Not connected — set up in Settings
                  </span>
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



    <!-- Centralized View Invoice (all payment types) -->
    <ViewInvoiceModal
      v-model="modals.viewReceipt"
      :invoice="activeInvoice"
      :can-approve="activeInvoice?.kind !== 'subscription'"
      @approve="handleReceiptApprove"
    />

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
import { ref, computed, reactive, onMounted, watch } from 'vue'
import { router, useForm }         from '@inertiajs/vue3'
import AppLayout                   from '@/layouts/AppLayout.vue'
import OpenDisputeModal             from '@/components/modals/OpenDisputeModal.vue'
import ViewInvoiceModal             from '@/components/modals/ViewInvoiceModal.vue'
import AegisPagination              from '@/components/ui/AegisPagination.vue'
import BpFinanceTable               from '@/components/ui/BpFinanceTable.vue'
import SettingsSubscriptionInvoices from '@/components/settings/SettingsSubscriptionInvoices.vue'
import SubscriptionPlanCard from '@/components/settings/SubscriptionPlanCard.vue'
// Wave 6 — session payment modals (local import required)
import SessionInvoiceCard           from '@/components/ui/SessionInvoiceCard.vue'
import SessionTable                 from '@/components/ui/SessionTable.vue'
import BookedSessionTable           from '@/components/ui/BookedSessionTable.vue'
import SessionInvoiceModal          from '@/components/modals/SessionInvoiceModal.vue'
import PayDepositModal              from '@/components/modals/PayDepositModal.vue'   // @deprecated Rev 4
import PayBalanceModal              from '@/components/modals/PayBalanceModal.vue'   // @deprecated Rev 4
import PayUpfrontModal              from '@/components/modals/PayUpfrontModal.vue'
import PayCompletionModal           from '@/components/modals/PayCompletionModal.vue'
import RequestRefundModal           from '@/components/modals/RequestRefundModal.vue'
import ReviewRefundRequestModal     from '@/components/modals/ReviewRefundRequestModal.vue'
import { useToast }                 from '@/composables/useToast'
import { useProfileRoute }          from '@/composables/useProfileRoute'

const toast = useToast()
const { profileHref } = useProfileRoute()

const props = defineProps({
  subscription:            { type: Object,  default: () => ({}) },
  subscriptionInvoices:    { type: Array,   default: () => [] },
  paymentMethods:          { type: Array,   default: () => [] },
  csInvoices:              { type: Array,   default: () => [] },
  bpInvoices:              { type: Array,   default: () => [] },
  clientSessions:          { type: Array,   default: () => [] },
  providerSessions:        { type: Array,   default: () => [] },
  incomingRefundRequests:  { type: Array,   default: () => [] },
  outgoingRefundRequests:  { type: Array,   default: () => [] },
  activeSessionsAsProvider:{ type: Number,  default: 0 },
  allInvoices:             { type: Array,   default: () => [] },
  activeContracts:         { type: Array,   default: () => [] },
  invoicesByContract:      { type: Object,  default: () => ({}) },
  escrowSummary:           { type: Object,  default: () => ({ total_held_cents: 0, total_unfunded_cents: 0, funded_count: 0, contracts_needing_funding: 0 }) },
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

// ── CS fee-bearing stewards (FIX 3) ─────────────────────────────────────
const feeCs = computed(() => props.csStewards.filter(cs => cs.fee_cents > 0))

// ── CS Wallet: expand/collapse + invoice filter + pagination ─────────────
const expandedCs  = ref(null) // steward id of currently expanded row
const csInvFilter = ref({})   // { [steward_id]: 'all'|'payable'|'paid'|'disputed' }
const csInvPage   = ref({})   // { [steward_id]: page number }
const CS_INV_PER_PAGE = 5

function setCsInvFilter(id, filter) { csInvFilter.value[id] = filter; csInvPage.value[id] = 1 }
function setCsInvPage(id, page)    { csInvPage.value[id] = page }

function filteredCsInvoices(cs) {
  const filter = csInvFilter.value[cs.id] ?? 'all'
  if (filter === 'payable')  return cs.invoices.filter(i => i.payable)
  if (filter === 'paid')     return cs.invoices.filter(i => i.status === 'paid')
  if (filter === 'disputed') return cs.invoices.filter(i => i.disputed)
  return cs.invoices
}

function paginatedCsInvoices(cs) {
  const list  = filteredCsInvoices(cs)
  const page  = csInvPage.value[cs.id] ?? 1
  const start = (page - 1) * CS_INV_PER_PAGE
  return list.slice(start, start + CS_INV_PER_PAGE)
}

function csTotalPages(cs) {
  return Math.max(1, Math.ceil(filteredCsInvoices(cs).length / CS_INV_PER_PAGE))
}
function viewCsInvoice(inv) {
  const full = props.csInvoices.find(i => i.id === inv.id)
  activeInvoice.value = full ? { ...full, kind: 'cs_invoice' } : { ...inv, kind: 'cs_invoice' }
  modals.value.viewReceipt = true
}
function openDisputeDetail(inv) {
  // Switch to dispute section within Finances — disputes tab not in sidebar,
  // so open the ViewInvoiceModal to show dispute context
  const full = props.csInvoices.find(i => i.id === inv.id)
  activeInvoice.value = full ? { ...full, kind: 'cs_invoice' } : { ...inv, kind: 'cs_invoice' }
  modals.value.viewReceipt = true
}

// ── Tab + BP filter state ───────────────────────────────────────────────
const activeTab = ref('overview')

// Deep-link: ?tab=methods lands directly on Payment Methods (from Settings)
const validTabs = ['overview','executor','bp','sessions','subscription','methods','history','integrations']
onMounted(() => {
  const t = new URLSearchParams(window.location.search).get('tab')
  if (t && validTabs.includes(t)) activeTab.value = t
})

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
const activeCsId     = ref(null)
const activeCs       = computed(() => props.csStewards.find(cs => cs.id === activeCsId.value) ?? null)

// ── Modals ───────────────────────────────────────────────────────────────
const modals = ref({
  viewReceipt: false,
  payArrangement: false, confirmCsPay: false, openDispute: false,
  csDetail: false,
  export: false,
  payArrangement: false, confirmCsPay: false, openDispute: false,
  // Wave 6 — session payment modals
  sessionPayUpfront:    false,
  sessionPayCompletion: false,
  sessionPayDeposit:    false,
  sessionPayBalance:    false,
  sessionRequestRefund: false,
  sessionClientInvoice: false,
  sessionProviderInvoice: false,
  sessionReviewRefund: false,
})

// Wave 6 — active session and refund refs
const activeClientSession  = ref(null)
const activeProviderSession = ref(null)
const activeRefundRequest  = ref(null)

// Wave 6 — session sub-tab + independent filter state per side
const sessionSubTab          = ref('booked')   // 'booked' | 'providing'
const clientTableRef   = ref(null)
const providerTableRef = ref(null)

// Reset page when filter changes

function filterSessions(sessions, filter) {
  if (filter === 'all')         return sessions
  if (filter === 'deposit_due') return sessions.filter(s => s.payment_status === 'unpaid'       && s.status === 'scheduled')
  if (filter === 'balance_due') return sessions.filter(s => s.payment_status === 'deposit_paid' && s.status === 'scheduled')
  if (filter === 'paid')        return sessions.filter(s => s.payment_status === 'paid')
  if (filter === 'refunded')    return sessions.filter(s => ['refunded','partially_refunded'].includes(s.payment_status))
  return sessions
}

const activeDisputeRefundRequests = computed(() =>
  props.incomingRefundRequests.filter(r => r.is_actionable)
)

function openSessionDispute(ses) {
  disputeTarget.value = {
    type: 'session',
    id: ses.id,
    amount_cents: ses.agreed_amount_cents ?? ses.amount_cents ?? 0,
    label: `Clinical Session — ${ses.service_title ?? ses.invoice_number}`,
  }
  modals.value.openDispute = true
}



function escalateSessionRefund(ses) {
  const rr = props.outgoingRefundRequests.find(r => r.session_id === ses.id)
  if (!rr?.id) { toast.info('No denied refund request found for this session.'); return }
  router.post(route('provider.services.refund.escalate', { refund: rr.id }), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Escalated to dispute. Our team will review within 5 business days.'),
    onError:   () => toast.error('Could not escalate. Please contact support.'),
  })
}


// ── Payment forms (Inertia) ──────────────────────────────────────────────
const paying   = ref(null)
const csTarget = ref(null)

function askPayCs(inv) { csTarget.value = inv; modals.value.confirmCsPay = true }

function doPayCs() {
  if (!csTarget.value) return
  paying.value = csTarget.value.id
  router.post(route('provider.finances.cs-invoice.pay', { invoice: csTarget.value.id }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      modals.value.confirmCsPay = false
      csTarget.value = null
      toast.success('CS invoice paid.')
      router.reload({ only: ['csStewards', 'csInvoices', 'pendingInvoiceCount', 'pendingInvoiceTotal', 'pendingBreakdown', 'recentTransactions'] })
    },
    onError: (errors) => toast.error(errors.invoice || errors.message || 'Payment failed. Please check your default payment method.'),
    onFinish: () => { paying.value = null },
  })
}
function openViewInvoice(inv)    { activeInvoice.value = inv; modals.value.viewReceipt = true }

/**
 * Open the correct receipt modal for a transaction row. Maps each payment
 * kind back to its source invoice/session record so the modal shows the
 * right receipt shape.
 */
function openTxReceipt(tx) {
  if (tx.modal_type === 'subscription') {
    activeInvoice.value = {
      kind:              'subscription',
      invoice_number:    tx.stripe_invoice_id ?? tx.id,
      description:       tx.description,
      total_cents:       tx.amount_cents,
      status:            tx.status,
      issued_at:         tx.date,
      stripe_invoice_id: tx.stripe_invoice_id ?? null,
    }
    modals.value.viewReceipt = true
    return
  }
  if (tx.modal_type === 'cs_invoice') {
    const inv = tx.subject_id
      ? props.csInvoices.find(i => i.id === tx.subject_id)
      : props.csInvoices.find(i => tx.description?.includes(i.invoice_number))
    if (inv) { activeInvoice.value = { ...inv, kind: 'cs_invoice' }; modals.value.viewReceipt = true; return }
  }
  if (tx.modal_type === 'bp_invoice') {
    const inv = tx.subject_id
      ? props.bpInvoices.find(i => i.id === tx.subject_id)
      : props.bpInvoices.find(i => tx.description?.includes(i.invoice_number))
    if (inv) { activeInvoice.value = { ...inv, kind: 'bp_invoice' }; modals.value.viewReceipt = true; return }
  }
  if (tx.modal_type === 'session') {
    // Wave 6: use SessionInvoiceModal (not ViewInvoiceModal) for session transactions
    const ses = tx.subject_id
      ? props.clientSessions.find(s => s.id === tx.subject_id)
      : props.clientSessions[0]
    if (ses) {
      activeClientSession.value = ses
      modals.value.sessionClientInvoice = true
      return
    }
    // Fallback: check provider sessions
    const provSes = tx.subject_id
      ? props.providerSessions.find(s => s.id === tx.subject_id)
      : null
    if (provSes) {
      activeProviderSession.value = provSes
      modals.value.sessionProviderInvoice = true
      return
    }
  }
  toast.info('Receipt details are not available for this transaction.')
}

function handleReceiptApprove(inv) {
  modals.value.viewReceipt = false
  if (inv.kind === 'bp_invoice') {
    // BP invoice approval is handled by BpFinanceTable — switch to that tab
    activeTab.value = 'bp'
    toast.info('Select the invoice in the Business Partners tab to approve and pay.')
  } else if (inv.kind === 'cs_invoice') { askPayCs({ ...inv, cs_name: inv.cs_name }) }
}

// ── Form: CS Pay Arrangement ──────────────────────────────────────────────
function openCsDetail(cs) { activeCsId.value = cs.id; modals.value.csDetail = true }
function openPayArrangement(cs) { activeCsId.value = cs.id; modals.value.payArrangement = true }

// ── Payment methods ──────────────────────────────────────────────────────

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
  else if (b.session?.count > 0) { activeTab.value = 'sessions'; sessionSubTab.value = 'booked'; clientTableRef.value?.reset(); providerTableRef.value?.reset() }
  else                           activeTab.value = 'executor'
}

function contractStatusLabel(s) {
  return { active: 'Active', pending_signature: 'Awaiting Signature', pending_funding: 'Awaiting Funding', completed: 'Completed', cancelled: 'Cancelled', disputed: 'Disputed' }[s] ?? s
}
function contractStatusVariant(s) {
  return { active: 'green', pending_signature: 'gold', pending_funding: 'blue', completed: 'neutral', cancelled: 'neutral', disputed: 'red' }[s] ?? 'neutral'
}
function escrowPct(num, total) {
  if (!total) return '0%'
  return Math.min(100, Math.round(((num ?? 0) / total) * 100)) + '%'
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
/* Badge moved to far right for consistent text indent */
.upcoming-row .upcoming-kind-badge { margin-left: auto; order: 3; }
.upcoming-row .upcoming-info { order: 1; }
.upcoming-row .upcoming-right { order: 2; }
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

/* ── CS steward table rows ── */
.sic-row--clickable  { cursor: pointer; }
.sic-row--clickable:hover > td { background: var(--surface-2); }
.sic-row--warning > td:first-child { border-left: 3px solid var(--orange-dark); }

/* sic-party layout (mirrors Services.vue — scoped here for CS wallet) */
.sic-row   { border-bottom: 1px solid var(--border); background: #fff; }
.sic-row:last-child { border-bottom: none; }
.sic-td    { padding: 12px 14px; vertical-align: middle; font-size: 13px; }
.sic-td--party  { width: 42%; }
.sic-td--actions { width: 56px; text-align: right; white-space: nowrap; }
.sic-td--actions > * + * { margin-left: 6px; }
.sic-party { display: flex; align-items: center; gap: 10px; }
.sic-avatar {
  width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700; overflow: hidden;
}
.sic-avatar--gold { background: var(--badge-bg-gold); color: var(--gold-dark); border: 1px solid var(--badge-border-gold); }
.sic-avatar-initials { line-height: 1; letter-spacing: .3px; }
.sic-party-info  { min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.sic-party-name  { font-size: 13px; font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sic-service-name { font-size: 11px; color: var(--text-3); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sic-date-sub    { font-size: 11px; color: var(--text-4); margin-top: 1px; display: flex; align-items: center; gap: 3px; }
.sic-badges      { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }

/* ── CS Detail Modal header ── */
.csm-header      { display: flex; align-items: center; gap: 14px; padding-bottom: 16px; border-bottom: 1px solid var(--border); margin-bottom: 4px; }
.csm-avatar      { width: 52px; height: 52px; border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center; font-family: var(--font-serif); font-size: 18px; font-weight: 700; flex-shrink: 0; }
.csm-id          { flex: 1; min-width: 0; }
.csm-name        { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 6px; }
.csm-name-link   { font-family: var(--font-serif); font-size: 18px; font-weight: 700; color: var(--text); text-decoration: none; }
.csm-name-link:hover { color: var(--gold-dark); }
.csm-meta-row    { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.csm-facts       { display: grid; grid-template-columns: repeat(3, 1fr); margin: 16px 0; border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
.csm-fact        { padding: 14px 16px; background: var(--surface-2); border-right: 1px solid var(--border); }
.csm-fact:last-child { border-right: none; }
.csm-fact-label  { font-size: 10px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: var(--text-4); margin-bottom: 6px; }
.csm-fact-value  { font-family: var(--font-serif); font-weight: 700; color: var(--text); font-size: 14px; }
.csm-fact-value.amount { font-size: 22px; color: var(--gold-dark); }

/* ── CS invoice sub-table inside modal ── */
.csdt-inv-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; flex-wrap: wrap; }
.csdt-inv-label  { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-3); }
.csdt-inv-table-wrap { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
.csdt-inv-table  { width: 100%; border-collapse: collapse; }
.csdt-inv-table .sic-th { background: var(--surface-2); padding: 8px 12px; }
.csdt-inv-table .sic-row:last-child td { border-bottom: none; }
.cspay-note        { display: flex; align-items: flex-start; gap: 10px; padding: 13px 15px; border-radius: var(--radius); background: var(--blue-light); border: 1px solid var(--soft-blue); }
.cspay-note p      { font-size: 12px; color: var(--text-2); line-height: 1.5; margin: 0; }
.cspay-note-icon         { color: var(--gold-dark); flex-shrink: 0; margin-top: 1px; }
.cspay-note-icon--warning { color: var(--orange-dark); flex-shrink: 0; margin-top: 1px; }
.cspay-note--warning     { background: var(--orange-light); border-color: var(--soft-gold); }
.cspay-note--warning p   { color: var(--orange-dark); }

/* ── Stripe Connect pill ── */
.connect-pill         { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 700; letter-spacing: 0.4px; text-transform: uppercase; padding: 0px 7px; border-radius: var(--radius-full); white-space: nowrap; border: 1px solid var(--border); line-height: 1.6; }
.connect-pill.is-connected     { background: var(--green-light);  color: var(--green-dark);  border-color: var(--green-dark); }
.connect-pill.is-not-connected { background: var(--red-light);    color: var(--red-dark);    border-color: var(--red-dark); }
.connect-pill .status-dot      { width: 6px; height: 6px; border-radius: var(--radius-full); flex-shrink: 0; }
.connect-pill.is-connected .status-dot     { background: var(--green-dark); }
.connect-pill.is-not-connected .status-dot { background: var(--orange-dark); }

/* ── Invoice cards ── */
.invoice-card        { position: relative; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 14px; }
.invoice-card::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; }
.invoice-card.pending-approval::before { background: var(--orange); }
.invoice-card.active-contract::before  { background: var(--green); }
.invoice-card.session-card::before     { background: var(--teal); }

/* ── WAVE 6: SESSION SECTIONS ─────────────────────────────────────────── */
.sessions-refund-alerts {
  background: rgba(245,158,11,.07);
  border: 1px solid var(--gold);
  border-radius: var(--radius-lg);
  margin-bottom: 20px;
  overflow: hidden;
}
.sessions-refund-alerts-header {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 16px;
  background: rgba(245,158,11,.12);
  font-size: 13px; font-weight: 700; color: var(--gold-dark);
  border-bottom: 1px solid var(--gold);
}
.sessions-refund-list { padding: 4px 0; }
.sessions-refund-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 10px 16px; gap: 12px;
  border-bottom: 1px solid rgba(245,158,11,.2);
}
.sessions-refund-row:last-child { border-bottom: none; }
.sessions-refund-info { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; font-size: 13px; }
.sessions-refund-name    { font-weight: 700; color: var(--text); }
.sessions-refund-service { color: var(--text-3); font-weight: 600; }
.sessions-refund-amount  { font-weight: 700; color: var(--gold-dark); }
.sessions-refund-sep     { color: var(--border-dark); }

/* ── SESSION TABLE ── */
.sic-table-wrap { border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
.sic-table { width: 100%; border-collapse: collapse; }
.sic-th {
  padding: 9px 12px; text-align: left;
  font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
  color: var(--text-4); background: var(--surface-2); border-bottom: 1px solid var(--border);
}
.sic-th--right { text-align: right; }

.sessions-section-header { margin-bottom: 16px; }
.sessions-section-title {
  display: flex; align-items: center; gap: 8px;
  font-size: 16px; font-weight: 700; color: var(--text);
  margin-bottom: 4px;
}
.sessions-section-desc { font-size: 13px; color: var(--text-3); font-weight: 500; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.sessions-active-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 700; color: var(--green-dark, #2e7d32);
  background: rgba(34,197,94,.08); border: 1px solid var(--green);
  padding: 2px 8px; border-radius: 100px;
}
.section-count {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 20px; height: 20px; padding: 0 5px;
  border-radius: 100px; background: var(--surface-3); color: var(--text-3);
  font-size: 11px; font-weight: 700;
}

/* Filter chips */
/* Filter chip count badge — used inside .tab-pill */
.sessions-chip-count {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 16px; height: 16px; padding: 0 4px;
  background: var(--gold-dark); color: #fff;
  border-radius: 100px; font-size: 9px; font-weight: 800;
}
.tab-pill.active .sessions-chip-count {
  background: rgba(255,255,255,.35); color: #fff;
}
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
.app-status-connected    { font-size: 12px; font-weight: 600; color: var(--green-dark); background: var(--green-light); border: 1px solid rgba(34,197,94,.3); padding: 3px 10px; border-radius: var(--radius-full); display: inline-flex; align-items: center; gap: 5px; }
.app-status-disconnected { font-size: 12px; font-weight: 600; color: var(--orange-dark, #c2500a); background: var(--orange-light, #fff4ed); border: 1px solid rgba(234,88,12,.25); padding: 3px 10px; border-radius: var(--radius-full); display: inline-flex; align-items: center; gap: 5px; }
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

/* ── Wave 8: Escrow UI ── */
.escrow-summary-bar { display: flex; align-items: center; gap: 20px; padding: 12px 16px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-lg); margin-bottom: 16px; }
.escrow-summary-item { display: flex; align-items: flex-start; gap: 8px; flex: 1; }
.escrow-summary-item.is-warning { color: var(--gold-dark); }
.escrow-summary-label { font-size: 11px; color: var(--text-tertiary); }
.escrow-summary-value { font-size: 14px; font-weight: 700; color: var(--text); }
.escrow-summary-desc  { font-size: 11px; color: var(--text-tertiary); line-height: 1.4; }
.escrow-summary-divider { width: 1px; background: var(--border); align-self: stretch; }
.escrow-progress-wrap { margin: 10px 0; padding: 10px 14px; background: var(--surface-2); border-radius: var(--radius); }
.escrow-progress-labels { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-secondary); margin-bottom: 6px; }
.escrow-label-held     { color: var(--gold-dark); font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
.escrow-label-released { color: var(--green-dark); font-weight: 600; }
.escrow-progress-bar   { height: 4px; background: var(--surface-3, var(--border)); border-radius: 2px; display: flex; overflow: hidden; }
.escrow-bar-released   { background: var(--green-dark); transition: width 0.3s; }
.escrow-bar-held       { background: var(--gold-dark); transition: width 0.3s; }
.escrow-fund-prompt { display: flex; align-items: flex-start; gap: 8px; font-size: 12px; color: var(--text-secondary); padding: 8px 12px; background: rgba(202,158,72,0.06); border-radius: var(--radius); margin: 8px 0; }
.escrow-fund-prompt--sig { background: var(--surface-2); }
.contract--pending-fund { border-color: rgba(59, 130, 246, 0.4); }
.contract--pending-sig  { border-color: var(--gold-dark); }
.badge-pill--warning    { background: var(--gold-dark) !important; }

/* Escrow tab fund cards */
.escrow-fund-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 18px 20px; margin-bottom: 12px; }
.escrow-fund-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; }
.escrow-fund-card-title  { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
.escrow-fund-card-meta   { font-size: 12px; color: var(--text-secondary); }
.escrow-fund-milestone   { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-top: 1px solid var(--border); }
.escrow-fund-milestone-title { font-size: 13px; font-weight: 600; color: var(--text); }
.escrow-fund-milestone-meta  { font-size: 11px; color: var(--text-secondary); margin-top: 2px; }
.escrow-fund-card-cta { margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border); }

/* Upcoming escrow badge */
.upcoming-kind-badge--escrow { background: rgba(202,158,72,0.12); color: var(--gold-dark); }
</style>
