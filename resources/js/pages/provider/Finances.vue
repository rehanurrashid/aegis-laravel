<!--
  pages/provider/Finances.vue — Full 5-tab financial page.
  100% visual parity with finances.php.

  Tabs: Overview · CS Wallet · Business Partners · Payment Methods · Transactions

  Props (Prompt 2 wiring pass):
    subscription, paymentMethods, csInvoices, bpInvoices, paymentHistory,
    earnings, totalSpendCents, outstandingCents, stripeConnected,
    has_valid_default_pm, disputes,
    csStewards, upcomingPayments, recentTransactions, spendBreakdown,
    allInvoices, activeContracts, pendingInvoiceTotal, pendingInvoiceCount,
    activeContractCount, csAgreedTotal
-->
<template>
  <AppLayout :user="$page.props.auth.user" portal="practitioner" activePage="finances" pageTitle="Finances">

    <!-- ══ HERO ══ -->
    <AegisHeroBanner
      eyebrow="Provider Portal"
      title="Aegis Finances"
      subtitle="All outgoing payments — Continuity Stewards, Business Partners &amp; Aegis subscription"
      quiet
    >
      <template #actions>
        <a :href="route('provider.activity')" class="btn-hero-ghost is-on-light" data-tooltip="Module activity">
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

    <!-- ══ STAT CHIPS ══ -->
    <div class="stat-chips-row">
      <AegisStatChip icon="activity"       :value="formatMoney(totalSpendCents / 100)"      label="Total Spend"       bg-color="var(--badge-bg-gold)"  icon-color="var(--gold-dark)" />
      <AegisStatChip icon="shield"         :value="formatMoney(csAgreedTotal / 100)"         label="Agreed CS Fees"    bg-color="var(--badge-bg-gold)"  icon-color="var(--gold-dark)" />
      <AegisStatChip icon="alert-triangle" :value="formatMoney(pendingInvoiceTotal / 100)"   label="Pending Invoices"  bg-color="var(--orange-light)"   icon-color="var(--orange-dark)" />
      <AegisStatChip icon="file-text"      :value="activeContractCount"                       label="Active Contracts"  bg-color="var(--badge-bg-gold)"  icon-color="var(--gold-dark)" />
    </div>

    <!-- ══ PENDING INVOICE ALERT ══ -->
    <div v-if="pendingInvoiceCount > 0" class="alert alert-warning" style="margin-bottom:12px;">
      <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
      <div class="alert-content">
        <div class="alert-title">Invoice Approval Required</div>
        <div>
          {{ firstPendingDesc }}
          <a href="#" @click.prevent="activeTab = 'bp'" style="color:inherit;font-weight:700;text-decoration:none;"> Review now →</a>
        </div>
        <div style="margin-top:10px;">
          <button type="button" class="btn btn-primary" @click="activeTab = 'bp'">Review Invoices</button>
        </div>
      </div>
    </div>

    <!-- ══ TABS ══ -->
    <div class="tabs-primary" role="tablist" style="margin-bottom:24px;">
      <button type="button" class="tab-primary" :class="{ active: activeTab === 'overview' }" @click="activeTab = 'overview'">
        <AegisIcon name="activity" :size="15" /> Overview
      </button>
      <button type="button" class="tab-primary" :class="{ active: activeTab === 'executor' }" @click="activeTab = 'executor'">
        <AegisIcon name="shield" :size="15" /> CS Wallet
      </button>
      <button type="button" class="tab-primary" :class="{ active: activeTab === 'bp' }" @click="activeTab = 'bp'">
        <AegisIcon name="file-text" :size="15" /> Business Partners
        <span v-if="pendingInvoiceCount > 0" class="tab-count">{{ pendingInvoiceCount }}</span>
      </button>
      <button type="button" class="tab-primary" :class="{ active: activeTab === 'methods' }" @click="activeTab = 'methods'">
        <AegisIcon name="credit-card" :size="15" /> Payment Methods
      </button>
      <button type="button" class="tab-primary" :class="{ active: activeTab === 'history' }" @click="activeTab = 'history'">
        <AegisIcon name="clock" :size="15" /> Transactions
      </button>
    </div>

    <!-- ══════════════════════════════
         TAB: OVERVIEW
    ══════════════════════════════ -->
    <div v-show="activeTab === 'overview'">
      <div class="two-col">

        <!-- Spend Breakdown -->
        <div class="card">
          <div class="card-header">
            <div class="card-title fin-card-title">
              <span class="fin-card-icon"><AegisIcon name="activity" :size="15" /></span>
              Spend Breakdown
            </div>
            <AegisBadge label="By recipient · to date" variant="neutral" />
          </div>
          <div class="card-body">
            <div v-if="spendBreakdown.length" class="spend-bd">
              <div class="spend-bd-total">
                <div class="spend-bd-total-val">{{ formatMoney(spendTotal) }}</div>
                <div class="spend-bd-total-lbl">total spend to date</div>
              </div>
              <div class="spend-bd-bar">
                <span
                  v-for="item in spendBreakdown" :key="item.label"
                  :style="{ background: item.color, width: item.pct + '%' }"
                ></span>
              </div>
              <div class="spend-bd-list">
                <div v-for="item in spendBreakdown" :key="item.label" class="spend-bd-row">
                  <span class="spend-bd-dot" :style="{ background: item.color }"></span>
                  <span class="spend-bd-name">{{ item.label }}</span>
                  <span class="spend-bd-amt">${{ item.amount.toLocaleString() }}</span>
                  <span class="spend-bd-pct">{{ item.pct }}%</span>
                </div>
              </div>
            </div>
            <div v-else class="spend-bd-empty">No spend recorded yet.</div>
          </div>
        </div>

        <!-- Upcoming Payments -->
        <div class="card">
          <div class="card-header">
            <div class="card-title fin-card-title">
              <span class="fin-card-icon"><AegisIcon name="calendar" :size="15" /></span>
              Upcoming Payments
            </div>
            <AegisBadge :label="upcomingPayments.length + ' due'" variant="gold" />
          </div>
          <div class="card-body">
            <div v-if="upcomingPayments.length">
              <div
                v-for="inv in upcomingPayments" :key="inv.id"
                class="upcoming-row clickable"
                data-tooltip="View invoice"
                @click="openViewInvoice(inv)"
              >
                <div class="upcoming-date-badge" :class="{ 'upcoming-date-badge--urgent': inv.is_urgent }">
                  <div class="upcoming-month" :class="{ 'upcoming-text--urgent': inv.is_urgent }">{{ inv.due_month }}</div>
                  <div class="upcoming-day"   :class="{ 'upcoming-text--urgent': inv.is_urgent }">{{ inv.due_day }}</div>
                </div>
                <div class="upcoming-info">
                  <div class="upcoming-name">{{ inv.bp_name }} — {{ inv.contract_title || 'Invoice' }}</div>
                  <div class="upcoming-desc">
                    {{ inv.notes_short }}
                    <AegisBadge label="Awaiting approval" variant="gold" style="margin-left:4px;" />
                  </div>
                </div>
                <div>
                  <div class="upcoming-amount" :class="{ 'upcoming-amount--urgent': inv.is_urgent }">{{ formatCents(inv.total_cents) }}</div>
                  <div class="upcoming-type"><AegisIcon name="chevron-right" :size="12" /> Review</div>
                </div>
              </div>
            </div>
            <div v-else style="text-align:center;padding:20px;color:var(--text-3);font-size:13px;">No pending invoices.</div>
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
                <th>Date</th><th>Payee</th><th>Description</th><th>Category</th><th>Method</th><th>Amount</th><th>Status</th><th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tx in recentTransactions.slice(0, 5)" :key="tx.id">
                <td class="tx-date">{{ tx.date }}</td>
                <td>
                  <a v-if="tx.payee_url" :href="tx.payee_url" class="tx-payee-link">{{ tx.payee }}</a>
                  <span v-else class="tx-payee">{{ tx.payee }}</span>
                </td>
                <td class="tx-desc">{{ tx.description }}</td>
                <td>
                  <span class="tx-cat-wrap">
                    <span class="tx-cat-dot" :style="{ background: tx.cat_color }"></span>
                    {{ tx.category_label }}
                  </span>
                </td>
                <td class="tx-method">{{ tx.method }}</td>
                <td :class="tx.amount < 0 ? 'tx-amount-out' : 'tx-amount-in'">
                  {{ tx.amount < 0 ? '-' : '+' }}${{ Math.abs(tx.amount).toLocaleString() }}
                </td>
                <td><AegisBadge :label="tx.status" :variant="statusVariant(tx.status)" /></td>
                <td class="tx-action">
                  <button type="button" class="btn-icon btn-icon-sm" data-tooltip="View receipt" @click="openTxReceipt(tx)">
                    <AegisIcon name="eye" :size="12" />
                  </button>
                </td>
              </tr>
              <tr v-if="!recentTransactions.length">
                <td colspan="8" style="text-align:center;padding:24px;color:var(--text-3);font-size:13px;">No transactions yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════
         TAB: CS WALLET
    ══════════════════════════════ -->
    <div v-show="activeTab === 'executor'">

      <!-- Inner stat chips -->
      <div class="stat-chips-row" style="margin-bottom:24px;">
        <AegisStatChip icon="shield" :value="formatMoney(csAgreedTotal / 100)" label="Agreed CS Fees"       bg-color="var(--badge-bg-gold)" icon-color="var(--gold-dark)" />
        <AegisStatChip icon="users" :value="csStewards.length"                  label="Active Stewards"      bg-color="var(--badge-bg-gold)" icon-color="var(--gold-dark)" />
        <AegisStatChip icon="shield" :value="formatMoney(csAgreedTotal / 100)" label="Agreed Standby Fees"  bg-color="var(--badge-bg-gold)" icon-color="var(--gold-dark)" />
      </div>

      <!-- Provisional copy note -->
      <div class="alert alert-info" style="margin-bottom:20px;">
        <div class="alert-icon"><AegisIcon name="shield" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">Direct Payments — Aegis Holds No Funds</div>
          <div>Continuity Steward fees are collected directly through your saved payment method via Stripe when a critical incident is verified. Aegis never holds, escrows, or processes the funds.</div>
        </div>
      </div>

      <!-- Empty state -->
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

      <!-- Per-CS cards -->
      <div v-for="cs in csStewards" :key="cs.id" class="cspay-card">
        <div class="cspay-body">
          <div class="cspay-top">
            <a :href="profileHref(cs.slug, 'cs')" target="_blank" class="avatar avatar-md avatar-gold" data-tooltip="View profile" style="text-decoration:none;">
              {{ cs.initials }}
            </a>
            <div class="cspay-id">
              <div class="cspay-name-row">
                <a :href="profileHref(cs.slug, 'cs')" target="_blank" class="cspay-name">{{ cs.display_name }}</a>
                <AegisBadge :label="payModelLabel(cs.payment_model)" variant="gold" />
                <span class="connect-pill" :class="cs.stripe_connected ? 'is-connected' : 'is-not-connected'"
                  :data-tooltip="cs.stripe_connected ? 'This recipient can receive direct payments via Stripe.' : 'This recipient has not finished Stripe Connect onboarding, so direct payments cannot be routed to them yet.'">
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
              <button type="button" class="btn-icon btn-icon-sm btn-icon-danger" data-tooltip="Cancel agreement" @click="modals.cancelCsAgreement = true; activeCs = cs">
                <AegisIcon name="trash" :size="13" />
              </button>
            </div>
          </div>

          <div class="cspay-meta">
            <div class="cspay-meta-item">
              <div class="cspay-meta-label">Agreed Standby Fee</div>
              <div class="cspay-meta-value amount">{{ formatCents(cs.fee_cents) }}</div>
            </div>
            <div class="cspay-meta-item">
              <div class="cspay-meta-label">Fee Model</div>
              <div class="cspay-meta-value">{{ payModelLabel(cs.payment_model) }}</div>
            </div>
          </div>

          <!-- Stripe not connected warning -->
          <div v-if="!cs.stripe_connected" class="cspay-note cspay-note--warning" style="margin-bottom:10px;">
            <span class="cspay-note-icon--warning"><AegisIcon name="alert-triangle" :size="16" /></span>
            <p>
              <strong>{{ cs.display_name }} hasn't finished Stripe Connect onboarding.</strong>
              Until they do, the agreed fee can't be routed to them automatically when an incident is verified. Ask them to complete payment setup in their portal.
            </p>
          </div>

          <!-- Provisional copy info note -->
          <div class="cspay-note">
            <span style="color:var(--gold-dark);flex-shrink:0;margin-top:1px;"><AegisIcon name="shield" :size="16" /></span>
            <p>
              The agreed standby fee of <strong>{{ formatCents(cs.fee_cents) }}</strong> is charged directly to {{ cs.display_name }} through your saved Stripe payment authorization when a critical incident is verified. Aegis does not hold these funds.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════
         TAB: BUSINESS PARTNERS
    ══════════════════════════════ -->
    <div v-show="activeTab === 'bp'">

      <div class="bp-toolbar">
        <div class="tabs-pill">
          <button type="button" class="tab-pill" :class="{ active: bpFilter === 'all' }" @click="bpFilter = 'all'">
            All <span v-if="allInvoices.length" class="badge-pill">{{ allInvoices.length }}</span>
          </button>
          <button type="button" class="tab-pill" :class="{ active: bpFilter === 'pending' }" @click="bpFilter = 'pending'">
            Pending <span v-if="bpInvoices.length" class="badge-pill">{{ bpInvoices.length }}</span>
          </button>
          <button type="button" class="tab-pill" :class="{ active: bpFilter === 'active' }" @click="bpFilter = 'active'">
            Active <span v-if="activeContracts.length" class="badge-pill">{{ activeContracts.length }}</span>
          </button>
        </div>
        <button type="button" class="btn btn-dark" @click="modals.hireBP = true">
          <AegisIcon name="plus" :size="13" /> Hire Business Partner
        </button>
      </div>

      <!-- Empty state -->
      <AegisEmptyState
        v-if="!allInvoices.length && !activeContracts.length"
        icon="file-text"
        title="No Business Partners yet"
        description="Hire a Business Partner to manage billing, legal, IT, and other practice services."
      >
        <template #action>
          <button type="button" class="btn btn-primary" @click="modals.hireBP = true">
            <AegisIcon name="plus" :size="13" /> Hire Business Partner
          </button>
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
              <button type="button" class="btn-icon" data-tooltip="Dispute" @click="openBpDispute(inv)">
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
                <AegisBadge v-if="con.autopay_enabled" label="Auto-Pay On" variant="gold" icon="clock" />
                <span v-if="con.billing_type === 'retainer'" class="invoice-auto"><AegisIcon name="check" :size="13" /> Monthly retainer</span>
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
                <div class="invoice-period">{{ con.billing_type ? con.billing_type.charAt(0).toUpperCase() + con.billing_type.slice(1) : '' }}</div>
              </div>
            </div>
            <div class="invoice-meta">
              <div>
                <div class="invoice-meta-label">Contract</div>
                <div class="invoice-meta-value">{{ con.term }}</div>
              </div>
              <div>
                <div class="invoice-meta-label">Last Paid</div>
                <div class="invoice-meta-value">{{ con.last_paid || '—' }}</div>
              </div>
              <div>
                <div class="invoice-meta-label">Amount</div>
                <div class="invoice-meta-value">{{ formatCents(con.total_cents) }} / mo</div>
              </div>
            </div>
            <div class="invoice-actions">
              <button type="button" class="btn-icon" data-tooltip="Payment history" @click="openBpHistory(con)">
                <AegisIcon name="clock" :size="15" />
              </button>
              <button type="button" class="btn-icon" data-tooltip="Auto-pay settings" @click="openAutoPay(con)">
                <AegisIcon name="settings" :size="15" />
              </button>
              <button type="button" class="btn-icon" data-tooltip="View contract" @click="openViewContract(con)">
                <AegisIcon name="file-text" :size="15" />
              </button>
              <button type="button" class="btn-icon btn-icon-danger" data-tooltip="Cancel contract" @click="openCancelContract(con)">
                <AegisIcon name="trash" :size="15" />
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- ══════════════════════════════
         TAB: PAYMENT METHODS
    ══════════════════════════════ -->
    <div v-show="activeTab === 'methods'">

      <!-- Saved Payment Methods -->
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
          <AegisEmptyState
            v-if="!paymentMethods.length"
            icon="credit-card"
            title="No payment methods"
            description="Add a card or bank account to pay Business Partners and manage your Aegis subscription."
            style="padding:24px 0;"
          />
          <div v-else>
            <div
              v-for="pm in paymentMethods" :key="pm.id"
              class="pm-card"
              :class="{ default: pm.is_default }"
            >
              <div class="pm-logo">
                <AegisIcon :name="pm.method_type === 'bank' ? 'activity' : 'credit-card'" :size="20" />
              </div>
              <div class="pm-info">
                <div class="pm-name">
                  {{ pm.method_type === 'bank'
                      ? pm.brand + ' ···· ' + pm.last4
                      : pm.brand + ' ending in ' + pm.last4 }}
                  <AegisBadge v-if="pm.is_default" label="Default" variant="gold" style="margin-left:6px;" />
                </div>
                <div class="pm-meta">
                  {{ pm.method_type === 'bank'
                      ? 'ACH / Bank Transfer'
                      : 'Expires ' + pm.exp_month + '/' + pm.exp_year }}
                </div>
              </div>
              <div class="pm-card-actions">
                <span v-if="pm.purpose" class="pm-purpose">{{ pm.purpose }}</span>
                <div class="pm-card-btns">
                  <button type="button" class="btn-icon btn-icon-sm" data-tooltip="Edit" @click="activePm = pm; modals.editCard = true">
                    <AegisIcon name="pencil" :size="12" />
                  </button>
                  <template v-if="!pm.is_default">
                    <button type="button" class="btn-icon btn-icon-sm" data-tooltip="Set as default" @click="setDefaultPm(pm)">
                      <AegisIcon name="check" :size="12" />
                    </button>
                    <button type="button" class="btn-icon btn-icon-sm btn-icon-danger" data-tooltip="Remove" @click="activePm = pm; modals.removeCard = true">
                      <AegisIcon name="trash" :size="12" />
                    </button>
                  </template>
                  <template v-else>
                    <button type="button" class="btn-icon btn-icon-sm" data-tooltip="Default card">
                      <AegisIcon name="check" :size="12" />
                    </button>
                  </template>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Auto-Pay & Spending Controls -->
      <div class="card">
        <div class="card-header">
          <div class="card-title fin-card-title">
            <span class="fin-card-icon"><AegisIcon name="settings" :size="15" /></span>
            Auto-Pay &amp; Spending Controls
          </div>
        </div>
        <div class="card-body">
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label">Auto-Pay for Retainer Contracts</div>
              <div class="setting-desc">Automatically pay recurring monthly retainers without manual approval</div>
            </div>
            <button
              type="button"
              class="toggle"
              :class="{ on: spendingControls.autoPay }"
              :aria-pressed="spendingControls.autoPay"
              @click="spendingControls.autoPay = !spendingControls.autoPay"
            ></button>
          </div>
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label">Require Approval for Invoices Over</div>
              <div class="setting-desc">Invoices above this threshold require manual approval before payment</div>
            </div>
            <div class="spending-input-wrap">
              <span class="spending-prefix">$</span>
              <input class="form-input form-input-sm" type="number" v-model="spendingControls.approvalThreshold" style="width:90px;text-align:right;">
            </div>
          </div>
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label">Monthly Spend Limit Alert</div>
              <div class="setting-desc">Get notified when your total monthly spend exceeds this amount</div>
            </div>
            <div class="spending-input-wrap">
              <span class="spending-prefix">$</span>
              <input class="form-input form-input-sm" type="number" v-model="spendingControls.monthlyLimit" style="width:100px;text-align:right;">
            </div>
          </div>
          <div class="setting-save-row">
            <button type="button" class="btn btn-primary" @click="saveSpendingControls">
              <AegisIcon name="check" :size="13" /> Save Controls
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════
         TAB: TRANSACTIONS
    ══════════════════════════════ -->
    <div v-show="activeTab === 'history'">
      <div class="card">
        <div class="card-header">
          <div class="card-title fin-card-title">
            <span class="fin-card-icon"><AegisIcon name="clock" :size="15" /></span>
            Full Transaction History
          </div>
          <button type="button" class="btn btn-outline" @click="modals.export = true">
            <AegisIcon name="download" :size="12" /> Export CSV
          </button>
        </div>
        <div class="card-body">
          <div class="fin-toolbar">
            <div class="input-group" style="flex:1;min-width:160px;">
              <span class="input-group-icon"><AegisIcon name="search" :size="13" /></span>
              <input class="form-input form-input-sm" type="text" v-model="txSearch" placeholder="Search…">
            </div>
            <select class="form-select form-select-sm" v-model="txCatFilter" style="min-width:140px;">
              <option value="">All Categories</option>
              <option value="executor">CS Finance</option>
              <option value="bp">Business Partner</option>
              <option value="aegis">Subscription</option>
            </select>
            <select class="form-select form-select-sm" v-model="txStatusFilter">
              <option value="">All Statuses</option>
              <option value="paid">Paid</option>
              <option value="pending">Pending</option>
              <option value="failed">Failed</option>
            </select>
          </div>
          <div style="overflow-x:auto;border-top:1px solid var(--border);margin:0 -20px;">
            <table class="table fin-tx-table" style="margin:0;">
              <thead>
                <tr>
                  <th>Date</th><th>Payee</th><th>Description</th><th>Category</th><th>Method</th><th>Amount</th><th>Status</th><th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="tx in filteredTransactions" :key="tx.id">
                  <td class="tx-date">{{ tx.date }}</td>
                  <td>
                    <a v-if="tx.payee_url" :href="tx.payee_url" class="tx-payee-link">{{ tx.payee }}</a>
                    <span v-else class="tx-payee">{{ tx.payee }}</span>
                  </td>
                  <td class="tx-desc">{{ tx.description }}</td>
                  <td>
                    <span class="tx-cat-wrap">
                      <span class="tx-cat-dot" :style="{ background: tx.cat_color }"></span>
                      {{ tx.category_label }}
                    </span>
                  </td>
                  <td class="tx-method">{{ tx.method }}</td>
                  <td :class="tx.amount < 0 ? 'tx-amount-out' : 'tx-amount-in'">
                    {{ tx.amount < 0 ? '-' : '+' }}${{ Math.abs(tx.amount).toLocaleString() }}
                  </td>
                  <td><AegisBadge :label="tx.status" :variant="statusVariant(tx.status)" /></td>
                  <td class="tx-action">
                    <button type="button" class="btn-icon btn-icon-sm" data-tooltip="View receipt" @click="openTxReceipt(tx)">
                      <AegisIcon name="eye" :size="12" />
                    </button>
                  </td>
                </tr>
                <tr v-if="!filteredTransactions.length">
                  <td colspan="8" style="text-align:center;padding:24px;color:var(--text-3);font-size:13px;">No transactions match your filters.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div style="text-align:center;padding:16px 0 0;">
            <AegisPagination
              :current-page="txPage"
              :total-pages="txTotalPages"
              @change="txPage = $event"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════
         MODALS
    ══════════════════════════════════════ -->

    <!-- Approve Invoice -->
    <AegisModal v-model="modals.approveInvoice" title="Approve Invoice" size="lg">
      <div v-if="activeInvoice">
        <div class="alert alert-success" style="margin-bottom:14px;">
          <div class="alert-icon"><AegisIcon name="check" :size="18" /></div>
          <div class="alert-content">
            <strong>Invoice #{{ activeInvoice.invoice_number }} · {{ activeInvoice.bp_name }}</strong>
            — {{ formatCents(activeInvoice.total_cents) }} will be charged to your default card and routed directly to {{ activeInvoice.bp_name }}.
          </div>
        </div>
        <div class="receipt">
          <div class="receipt-row"><span>{{ activeInvoice.contract_title || 'Services' }}</span><span>{{ formatCents(activeInvoice.total_cents) }}</span></div>
          <div class="receipt-row total"><span>Total</span><span>{{ formatCents(activeInvoice.total_cents) }}</span></div>
        </div>
        <div class="form-group" style="margin-top:14px;">
          <label class="form-label">Notes (optional)</label>
          <textarea class="form-textarea" rows="2" v-model="approveNote" placeholder="Add a note for your records…"></textarea>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.approveInvoice = false">Cancel</button>
        <button type="button" class="btn btn-success" @click="doApproveBpInvoice">
          <AegisIcon name="check" :size="13" /> Approve &amp; Pay {{ activeInvoice ? formatCents(activeInvoice.total_cents) : '' }}
        </button>
      </template>
    </AegisModal>

    <!-- Reject Invoice -->
    <AegisModal v-model="modals.rejectInvoice" title="Reject Invoice" size="lg">
      <div v-if="activeInvoice">
        <div class="alert alert-danger" style="margin-bottom:14px;">
          <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
          <div class="alert-content">
            <strong>Rejecting this invoice will notify {{ activeInvoice.bp_name }}.</strong> They can revise and resubmit.
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Reason for Rejection <span class="required">*</span></label>
          <select class="form-select" v-model="rejectReason">
            <option>Incorrect amount</option>
            <option>Services not delivered</option>
            <option>Duplicate invoice</option>
            <option>Unauthorized charges</option>
            <option>Missing documentation</option>
            <option>Other</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Message to Business Partner</label>
          <textarea class="form-textarea" rows="3" v-model="rejectMessage" placeholder="Explain the rejection so they can resubmit correctly…"></textarea>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.rejectInvoice = false">Cancel</button>
        <button type="button" class="btn btn-danger" @click="doRejectInvoice">
          <AegisIcon name="x" :size="13" /> Reject Invoice
        </button>
      </template>
    </AegisModal>

    <!-- View Receipt / Invoice -->
    <AegisModal v-model="modals.viewReceipt" :title="activeInvoice ? 'Invoice #' + activeInvoice.invoice_number : 'Invoice'" size="lg">
      <div v-if="activeInvoice">
        <div style="display:flex;justify-content:space-between;margin-bottom:16px;">
          <div>
            <div class="vr-vendor">{{ activeInvoice.bp_name }}</div>
            <div class="vr-vendor-sub">Business Partner</div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:12px;color:var(--text-3);">Invoice Date</div>
            <div style="font-size:13px;font-weight:700;color:var(--text);">{{ activeInvoice.issued_at || '—' }}</div>
          </div>
        </div>
        <div class="receipt">
          <div class="receipt-row"><span>{{ activeInvoice.contract_title || 'Services' }}</span><span>{{ formatCents(activeInvoice.total_cents) }}</span></div>
          <div class="receipt-row total"><span>Total Due</span><span>{{ formatCents(activeInvoice.total_cents) }}</span></div>
        </div>
        <div v-if="activeInvoice.due_at" style="font-size:12px;color:var(--text-3);margin-top:12px;text-align:center;">
          Due: {{ activeInvoice.due_at }}
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-ghost" @click="toast.success('Invoice downloaded as PDF')">
          <AegisIcon name="download" :size="12" /> Download PDF
        </button>
        <button v-if="activeInvoice && activeInvoice.payable" type="button" class="btn btn-success" @click="modals.viewReceipt = false; openApproveInvoice(activeInvoice)">
          <AegisIcon name="check" :size="13" /> Approve &amp; Pay
        </button>
        <button v-else type="button" class="btn btn-outline" @click="modals.viewReceipt = false">Close</button>
      </template>
    </AegisModal>

    <!-- Cancel CS Agreement -->
    <AegisModal v-model="modals.cancelCsAgreement" title="Cancel Continuity Steward Agreement" size="lg">
      <div class="alert alert-danger" style="margin-bottom:14px;">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
        <div class="alert-content">
          <strong>Cancelling your Continuity Steward agreement leaves your practice without succession coverage.</strong> Aegis strongly recommends having at least one active Continuity Steward at all times.
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Cancellation Reason</label>
        <select class="form-select" v-model="cancelCsReason">
          <option>Replacing with another Continuity Steward</option>
          <option>Continuity Steward resigned</option>
          <option>Mutual termination</option>
          <option>Practice closing</option>
          <option>Other</option>
        </select>
      </div>
      <p style="font-size:12px;color:var(--text-3);margin-top:4px;">Cancelling ends the payment authorization for this Continuity Steward. No funds are held by Aegis, so there is nothing to refund — any saved authorization simply stops.</p>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.cancelCsAgreement = false">Keep Agreement</button>
        <button type="button" class="btn btn-danger" @click="doCancelCsAgreement">
          <AegisIcon name="x" :size="13" /> Cancel Agreement
        </button>
      </template>
    </AegisModal>

    <!-- Cancel BP Contract -->
    <AegisModal v-model="modals.cancelBpContract" :title="'Cancel Contract'" size="lg">
      <div class="alert alert-danger" style="margin-bottom:14px;">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
        <div class="alert-content">
          <strong>Cancelling {{ activeContract ? 'your contract with ' + activeContract.bp_name : 'this contract' }} will stop scheduled payments.</strong> Per contract terms, 30-day notice is required. You will be charged for the current billing cycle.
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Cancellation Date</label>
        <input class="form-input" type="date" v-model="cancelDate">
        <div class="form-hint">30-day notice period from today</div>
      </div>
      <div class="form-group">
        <label class="form-label">Reason</label>
        <select class="form-select" v-model="cancelBpReason">
          <option>Switching to different provider</option>
          <option>No longer needed</option>
          <option>Cost reduction</option>
          <option>Service quality issues</option>
          <option>Other</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Feedback for provider (optional)</label>
        <textarea class="form-textarea" rows="2" v-model="cancelBpFeedback" placeholder="Let the Business Partner know why you're cancelling…"></textarea>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.cancelBpContract = false">Keep Contract</button>
        <button type="button" class="btn btn-danger" @click="doCancelBpContract">
          <AegisIcon name="x" :size="13" /> Send Cancellation Notice
        </button>
      </template>
    </AegisModal>

    <!-- Hire Business Partner -->
    <AegisModal v-model="modals.hireBP" title="Hire Business Partner" size="lg">
      <div class="form-group">
        <label class="form-label">Search Business Partner</label>
        <div class="input-group">
          <span class="input-group-icon"><AegisIcon name="search" :size="14" /></span>
          <input class="form-input" v-model="hireBpSearch" placeholder="Search by name, service type, or category…">
        </div>
      </div>
      <div class="row-2">
        <div class="form-group">
          <label class="form-label">Service Category</label>
          <select class="form-select" v-model="hireBpCategory">
            <option>Medical Billing</option><option>IT Services</option><option>Legal Counsel</option>
            <option>Accounting &amp; Tax</option><option>Marketing</option><option>HR &amp; Staffing</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Contract Type</label>
          <select class="form-select" v-model="hireBpContractType">
            <option>Monthly Retainer</option><option>Hourly</option><option>Fixed Project</option><option>Milestone-Based</option>
          </select>
        </div>
      </div>
      <div class="row-2">
        <div class="form-group">
          <label class="form-label">Budget / Rate</label>
          <input class="form-input" v-model="hireBpBudget" placeholder="e.g. $1,500/month">
        </div>
        <div class="form-group">
          <label class="form-label">Start Date</label>
          <input class="form-input" type="date" v-model="hireBpStart">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Scope of Work</label>
        <textarea class="form-textarea" rows="3" v-model="hireBpScope" placeholder="Describe what services you need…"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Assign Payment Method</label>
        <select class="form-select" v-model="hireBpPaymentMethod">
          <option v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">
            {{ pm.brand }} ···· {{ pm.last4 }}{{ pm.is_default ? ' (default)' : '' }}
          </option>
        </select>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.hireBP = false">Cancel</button>
        <button type="button" class="btn btn-ghost" @click="modals.hireBP = false; toast.success('Posted to Business Partner marketplace')">
          <AegisIcon name="search" :size="12" /> Post &amp; Find Partners
        </button>
        <button type="button" class="btn btn-primary" @click="modals.hireBP = false; toast.success('Contract proposal sent')">
          <AegisIcon name="send" :size="12" /> Send Proposal
        </button>
      </template>
    </AegisModal>

    <!-- Auto-Pay Settings -->
    <AegisModal v-model="modals.autoPay" :title="'Auto-Pay Settings' + (activeContract ? ' — ' + activeContract.bp_name : '')" size="lg">
      <div class="setting-row" style="margin-bottom:14px;">
        <div class="setting-info">
          <div class="setting-label">Enable Auto-Pay</div>
          <div class="setting-desc">Automatically charge your selected method when an invoice is due. Payment routes directly to the Business Partner — Aegis holds no funds.</div>
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
        <label class="form-label">Payment Method</label>
        <select class="form-select" v-model="autoPayForm.method_id">
          <option v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">
            {{ pm.brand }} ···· {{ pm.last4 }}{{ pm.is_default ? ' (default)' : '' }}
          </option>
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
        <button type="button" class="btn btn-primary" @click="doSaveAutoPay">
          <AegisIcon name="check" :size="13" /> Save Settings
        </button>
      </template>
    </AegisModal>

    <!-- View Contract -->
    <AegisModal v-model="modals.viewContract" :title="activeContract ? 'Contract — ' + activeContract.bp_name : 'Contract'" size="lg">
      <div v-if="activeContract" class="contract-preview">
        <div class="contract-preview-title">Aegis Service Agreement</div>
        <div class="contract-preview-row"><strong>Business Partner:</strong> {{ activeContract.bp_name }}</div>
        <div class="contract-preview-row"><strong>Services:</strong> {{ activeContract.scope || activeContract.title }}</div>
        <div class="contract-preview-row"><strong>Payment:</strong> {{ formatCents(activeContract.total_cents) }} · {{ activeContract.billing_type }}</div>
        <div class="contract-preview-row"><strong>Term:</strong> {{ activeContract.term }}</div>
        <div class="contract-preview-row"><strong>Termination:</strong> 30-day written notice</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-ghost" @click="toast.success('Contract downloaded')">
          <AegisIcon name="download" :size="12" /> Download PDF
        </button>
        <button type="button" class="btn btn-outline" @click="modals.viewContract = false">Close</button>
      </template>
    </AegisModal>

    <!-- Add Payment Method -->
    <AegisModal v-model="modals.addPayment" title="Add Payment Method" size="lg">
      <div class="tabs-pill" style="margin-bottom:16px;">
        <button type="button" class="tab-pill" :class="{ active: addPayType === 'card' }" @click="addPayType = 'card'">
          <AegisIcon name="credit-card" :size="13" /> Credit/Debit Card
        </button>
        <button type="button" class="tab-pill" :class="{ active: addPayType === 'bank' }" @click="addPayType = 'bank'">
          <AegisIcon name="activity" :size="13" /> Bank Account (ACH)
        </button>
      </div>

      <div v-show="addPayType === 'card'">
        <div class="form-group"><label class="form-label">Cardholder Name</label><input class="form-input" v-model="addPayForm.cardholder" placeholder="Dr. Sarah Johnson"></div>
        <div class="form-group"><label class="form-label">Card Number</label><input class="form-input" v-model="addPayForm.cardNumber" placeholder="1234 5678 9012 3456" maxlength="19"></div>
        <div class="row-2">
          <div class="form-group"><label class="form-label">Expiry</label><input class="form-input" v-model="addPayForm.expiry" placeholder="MM / YY"></div>
          <div class="form-group"><label class="form-label">CVV</label><input class="form-input" type="password" v-model="addPayForm.cvv" placeholder="•••" maxlength="4"></div>
        </div>
        <div class="form-hint"><AegisIcon name="shield" :size="12" /> Card details are securely tokenized by Stripe. Aegis never sees or stores your full card number.</div>
      </div>

      <div v-show="addPayType === 'bank'">
        <div class="form-group"><label class="form-label">Account Holder Name</label><input class="form-input" v-model="addPayForm.bankHolder" placeholder="Dr. Sarah Johnson"></div>
        <div class="form-group"><label class="form-label">Routing Number</label><input class="form-input" v-model="addPayForm.routingNumber" placeholder="9-digit routing number" maxlength="9"></div>
        <div class="form-group"><label class="form-label">Account Number</label><input class="form-input" v-model="addPayForm.accountNumber" placeholder="Account number"></div>
        <div class="form-group"><label class="form-label">Account Type</label><select class="form-select" v-model="addPayForm.accountType"><option>Checking</option><option>Savings</option><option>Business Checking</option></select></div>
      </div>

      <div class="form-group" style="margin-top:4px;">
        <label class="form-label">Use this card for</label>
        <select class="form-select" v-model="addPayForm.purpose">
          <option>All payments (default)</option>
          <option>Business Partner payments only</option>
          <option>Continuity Steward finance only</option>
          <option>Aegis subscription only</option>
        </select>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.addPayment = false">Cancel</button>
        <button type="button" class="btn btn-primary" @click="doAddPayment">
          <AegisIcon name="check" :size="13" /> Save Payment Method
        </button>
      </template>
    </AegisModal>

    <!-- Edit Card -->
    <AegisModal v-model="modals.editCard" title="Edit Payment Method" size="sm">
      <div class="form-group"><label class="form-label">Nickname (optional)</label><input class="form-input" v-model="editCardNickname" placeholder="e.g. Business Card"></div>
      <div class="form-group">
        <label class="form-label">Use this card for</label>
        <select class="form-select" v-model="editCardPurpose">
          <option>All payments (default)</option>
          <option>Business Partner payments only</option>
          <option>Continuity Steward finance only</option>
          <option>Aegis subscription only</option>
        </select>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.editCard = false">Cancel</button>
        <button type="button" class="btn btn-primary" @click="modals.editCard = false; toast.success('Payment method updated')">
          <AegisIcon name="check" :size="13" /> Save Changes
        </button>
      </template>
    </AegisModal>

    <!-- Remove Card -->
    <AegisModal v-model="modals.removeCard" title="Remove Payment Method" size="sm">
      <p style="font-size:13px;color:var(--text-2);">Are you sure you want to remove this payment method? Any contracts assigned to it will fall back to your default card.</p>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.removeCard = false">Cancel</button>
        <button type="button" class="btn btn-danger" @click="doRemoveCard">
          <AegisIcon name="trash" :size="13" /> Remove
        </button>
      </template>
    </AegisModal>

    <!-- Export Report -->
    <AegisModal v-model="modals.export" title="Export Financial Report" size="lg">
      <div class="row-2">
        <div class="form-group"><label class="form-label">From</label><input class="form-input" type="date" v-model="exportForm.from"></div>
        <div class="form-group"><label class="form-label">To</label><input class="form-input" type="date" v-model="exportForm.to"></div>
      </div>
      <div class="form-group">
        <label class="form-label">Include</label>
        <div class="export-checks">
          <label class="form-check"><input type="checkbox" v-model="exportForm.allTx"><span class="form-check-label">All transactions</span></label>
          <label class="form-check"><input type="checkbox" v-model="exportForm.csActivity"><span class="form-check-label">Continuity Steward payment activity</span></label>
          <label class="form-check"><input type="checkbox" v-model="exportForm.bpInvoices"><span class="form-check-label">Business Partner invoices</span></label>
          <label class="form-check"><input type="checkbox" v-model="exportForm.subscription"><span class="form-check-label">Aegis subscription</span></label>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Format</label><select class="form-select" v-model="exportForm.format"><option>CSV</option><option>PDF</option><option>Excel (.xlsx)</option></select></div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.export = false">Cancel</button>
        <button type="button" class="btn btn-primary" @click="modals.export = false; toast.success('Report exported successfully')">
          <AegisIcon name="download" :size="13" /> Export Report
        </button>
      </template>
    </AegisModal>

    <!-- Payment Arrangement History (CS) -->
    <AegisModal v-model="modals.payArrangement" title="Payment Arrangement History" size="lg">
      <div style="padding:0;">
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr><th style="padding-left:22px;">Date</th><th>Event</th><th>Amount</th><th style="padding-right:22px;">Status</th></tr>
            </thead>
            <tbody>
              <tr v-if="activeCs">
                <td style="padding-left:22px;">Mar 15, 2025</td>
                <td>Authorization renewed</td>
                <td>{{ formatCents(activeCs.fee_cents) }}</td>
                <td style="padding-right:22px;"><AegisBadge label="Active" variant="green" /></td>
              </tr>
              <tr v-if="activeCs">
                <td style="padding-left:22px;">Mar 15, 2024</td>
                <td>Direct-pay authorization signed</td>
                <td>{{ formatCents(activeCs.fee_cents) }}</td>
                <td style="padding-right:22px;"><AegisBadge label="Active" variant="green" /></td>
              </tr>
            </tbody>
          </table>
        </div>
        <p style="font-size:12px;color:var(--text-3);padding:14px 22px 0;">No funds have changed hands. These are the authorization events; the agreed fee is charged directly to your Continuity Steward only when a critical incident is verified.</p>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.payArrangement = false">Close</button>
      </template>
    </AegisModal>

    <!-- Change Pay Model (CS) -->
    <AegisModal v-model="modals.changePayModel" title="Update Payment Model" size="lg">
      <p style="font-size:13px;color:var(--text-2);margin-bottom:16px;">
        Choose the fee structure for <strong>{{ activeCs?.display_name || 'this Continuity Steward' }}</strong>. This is a standby arrangement — Aegis holds no funds; the agreed fee is collected through your saved Stripe authorization when a critical incident is verified.
      </p>
      <div v-for="opt in payModelOptions" :key="opt.value"
           class="role-option"
           :class="{ selected: selectedPayModel === opt.value }"
           @click="selectedPayModel = opt.value"
      >
        <div style="flex:1;min-width:0;">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:var(--text);cursor:pointer;margin:0;">
            <input type="radio" name="paymodel" :value="opt.value" v-model="selectedPayModel" style="accent-color:var(--gold-dark);width:16px;height:16px;flex-shrink:0;margin:0;">
            <AegisIcon :name="opt.icon" :size="14" /> {{ opt.label }}
          </label>
          <div style="font-size:12px;color:var(--text-3);margin-top:4px;padding-left:24px;">{{ opt.desc }}</div>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.changePayModel = false">Cancel</button>
        <button type="button" class="btn btn-primary" @click="doSavePayModel">
          <AegisIcon name="check" :size="13" /> Update Payment Model
        </button>
      </template>
    </AegisModal>

    <!-- CS + BP Confirm-pay modals (from previous wiring pass) -->
    <AegisModal v-model="modals.confirmCsPay" title="Confirm payment" size="sm">
      <p v-if="csTarget">
        Pay <strong>{{ formatCents(csTarget.total_cents) }}</strong> to
        <strong>{{ csTarget.cs_name }}</strong> for invoice
        <strong>{{ csTarget.invoice_number }}</strong>?
      </p>
      <p class="text-muted" style="font-size:12px;margin-top:8px;">
        Funds are transferred directly to your Continuity Steward's Stripe account. Aegis does not hold or take a cut of this payment.
      </p>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.confirmCsPay = false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="paying === csTarget?.id" @click="doPayCs">
          {{ paying === csTarget?.id ? 'Processing…' : 'Pay now' }}
        </button>
      </template>
    </AegisModal>

    <AegisModal v-model="modals.confirmBpPay" title="Confirm payment" size="sm">
      <p v-if="bpTarget">
        Pay <strong>{{ formatCents(bpTarget.total_cents) }}</strong> to
        <strong>{{ bpTarget.bp_name }}</strong> for invoice
        <strong>{{ bpTarget.invoice_number }}</strong>?
      </p>
      <p class="text-muted" style="font-size:12px;margin-top:8px;">
        Funds are transferred directly to your Business Partner's Stripe account. Aegis does not hold or take a cut of this payment.
      </p>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.confirmBpPay = false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="paying === bpTarget?.id" @click="doPayBp">
          {{ paying === bpTarget?.id ? 'Processing…' : 'Pay now' }}
        </button>
      </template>
    </AegisModal>

    <!-- Open Dispute modal (wired in previous pass) -->
    <OpenDisputeModal
      v-model="modals.openDispute"
      :subject="disputeTarget"
      post-route="provider.disputes.store"
      @opened="router.reload({ only: ['csInvoices', 'bpInvoices', 'disputes'] })"
    />

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout         from '@/layouts/AppLayout.vue'
import OpenDisputeModal  from '@/components/modals/OpenDisputeModal.vue'
import AegisPagination   from '@/components/ui/AegisPagination.vue'
import { useToast }      from '@/composables/useToast'
import { useProfileRoute } from '@/composables/useProfileRoute'

const toast = useToast()
const { profileHref } = useProfileRoute()

// ── Props (Prompt 1: all static-placeholder; Prompt 2 wires real data) ──
const props = defineProps({
  subscription:          { type: Object,  default: () => ({}) },
  paymentMethods:        { type: Array,   default: () => [] },
  csInvoices:            { type: Array,   default: () => [] },
  bpInvoices:            { type: Array,   default: () => [] },
  allInvoices:           { type: Array,   default: () => [] },
  activeContracts:       { type: Array,   default: () => [] },
  csStewards:            { type: Array,   default: () => [] },
  upcomingPayments:      { type: Array,   default: () => [] },
  recentTransactions:    { type: Array,   default: () => [] },
  spendBreakdown:        { type: Array,   default: () => [] },
  paymentHistory:        { type: Array,   default: () => [] },
  earnings:              { type: Array,   default: () => [] },
  disputes:              { type: Array,   default: () => [] },
  totalSpendCents:       { type: Number,  default: 0 },
  outstandingCents:      { type: Number,  default: 0 },
  csAgreedTotal:         { type: Number,  default: 0 },
  pendingInvoiceTotal:   { type: Number,  default: 0 },
  pendingInvoiceCount:   { type: Number,  default: 0 },
  activeContractCount:   { type: Number,  default: 0 },
  stripeConnected:       { type: Boolean, default: false },
  has_valid_default_pm:  { type: Boolean, default: false },
})

// ── Tab state ────────────────────────────────────────────────────────────
const activeTab = ref('overview')

// ── BP filter ────────────────────────────────────────────────────────────
const bpFilter = ref('all')

// ── Transaction search/filter ────────────────────────────────────────────
const txSearch      = ref('')
const txCatFilter   = ref('')
const txStatusFilter = ref('')
const txPage        = ref(1)
const TX_PER_PAGE   = 10

const filteredTransactions = computed(() => {
  let list = props.recentTransactions
  if (txSearch.value) {
    const q = txSearch.value.toLowerCase()
    list = list.filter(t => (t.payee + t.description + t.category_label).toLowerCase().includes(q))
  }
  if (txCatFilter.value) list = list.filter(t => t.cat === txCatFilter.value)
  if (txStatusFilter.value) list = list.filter(t => t.status === txStatusFilter.value)
  return list
})
const txTotalPages = computed(() => Math.max(1, Math.ceil(filteredTransactions.value.length / TX_PER_PAGE)))

// ── Active refs ──────────────────────────────────────────────────────────
const activeInvoice  = ref(null)
const activeContract = ref(null)
const activeCs       = ref(null)
const activePm       = ref(null)

// ── Modals ───────────────────────────────────────────────────────────────
const modals = ref({
  approveInvoice:    false,
  rejectInvoice:     false,
  viewReceipt:       false,
  cancelCsAgreement: false,
  cancelBpContract:  false,
  hireBP:            false,
  autoPay:           false,
  viewContract:      false,
  addPayment:        false,
  editCard:          false,
  removeCard:        false,
  export:            false,
  payArrangement:    false,
  changePayModel:    false,
  confirmCsPay:      false,
  confirmBpPay:      false,
  openDispute:       false,
})

// ── Pay CS (wired) ───────────────────────────────────────────────────────
const paying   = ref(null)
const csTarget = ref(null)
const bpTarget = ref(null)
function askPayCs(inv) { csTarget.value = inv; modals.value.confirmCsPay = true }
function askPayBp(inv) { bpTarget.value = inv; modals.value.confirmBpPay = true }

function doPayCs() {
  if (!csTarget.value) return
  paying.value = csTarget.value.id
  router.post(route('provider.finances.cs-invoice.pay', { invoice: csTarget.value.id }), {}, {
    preserveScroll: true,
    onSuccess: () => { modals.value.confirmCsPay = false; csTarget.value = null },
    onFinish:  () => { paying.value = null },
  })
}
function doPayBp() {
  if (!bpTarget.value) return
  paying.value = bpTarget.value.id
  router.post(route('provider.jobs.bp-invoice.pay', { invoice: bpTarget.value.id }), {}, {
    preserveScroll: true,
    onSuccess: () => { modals.value.confirmBpPay = false; bpTarget.value = null },
    onFinish:  () => { paying.value = null },
  })
}

// ── Dispute (wired) ──────────────────────────────────────────────────────
const disputeTarget = ref(null)

const activeDisputes   = computed(() => (props.disputes ?? []).filter(d =>
  ['open', 'awaiting_response', 'under_review'].includes(d.status)
))
const resolvedDisputes = computed(() => (props.disputes ?? []).filter(d =>
  d.status === 'resolved' || d.status === 'closed_no_action'
))

function canDispute(inv) {
  if (!['paid', 'sent'].includes(inv.status)) return false
  if (inv.status === 'disputed') return false
  if (!props.has_valid_default_pm) return false
  if (!inv.issued_at) return true
  return (Date.now() - new Date(inv.issued_at).getTime()) / 86400000 <= 60
}

function openBpDispute(inv) {
  disputeTarget.value = {
    type:         'bp_invoice',
    id:           inv.id,
    amount_cents: inv.total_cents,
    label:        `BP Invoice ${inv.invoice_number} · ${inv.bp_name}`,
  }
  modals.value.openDispute = true
}

// ── Invoice actions ──────────────────────────────────────────────────────
const approveNote   = ref('')
const rejectReason  = ref('Incorrect amount')
const rejectMessage = ref('')
const rejectForm    = useForm({ reason: 'Incorrect amount', message: '' })

function openApproveInvoice(inv) { activeInvoice.value = inv; approveNote.value = ''; modals.value.approveInvoice = true }
function openRejectInvoice(inv)  { activeInvoice.value = inv; modals.value.rejectInvoice = true }
function openViewInvoice(inv)    { activeInvoice.value = inv; modals.value.viewReceipt = true }
function openTxReceipt(tx)       { if (tx.inv_id) { activeInvoice.value = props.allInvoices?.find(i => i.id === tx.inv_id) || null; modals.value.viewReceipt = true } else { toast.info('Subscription receipts are issued directly by Aegis billing.') } }

function doApproveBpInvoice() {
  if (!activeInvoice.value) return
  paying.value = activeInvoice.value.id
  router.post(route('provider.jobs.bp-invoice.pay', { invoice: activeInvoice.value.id }), {}, {
    preserveScroll: true,
    onSuccess: () => { modals.value.approveInvoice = false; toast.success('Payment sent — routed directly to the recipient via Stripe') },
    onError: () => toast.error('Payment failed. Please check your default payment method.'),
    onFinish: () => { paying.value = null },
  })
}
function doRejectInvoice()    {
  if (!activeInvoice.value) return
  rejectForm.reason = rejectReason.value
  rejectForm.message = rejectMessage.value
  rejectForm.post(route('provider.finances.bp-invoice.reject', { invoice: activeInvoice.value.id }), {
    preserveScroll: true,
    onSuccess: () => { modals.value.rejectInvoice = false; rejectForm.reset() },
  })
}

// ── Contract actions ─────────────────────────────────────────────────────
const cancelDate          = ref('')
const cancelBpReason      = ref('No longer needed')
const cancelBpFeedback    = ref('')
const cancelContractForm  = useForm({ reason: 'No longer needed', feedback: '' })

function openCancelContract(con) { activeContract.value = con; modals.value.cancelBpContract = true }
function openViewContract(con)   { activeContract.value = con; modals.value.viewContract = true }
function doCancelBpContract()    {
  if (!activeContract.value) return
  cancelContractForm.reason = cancelBpReason.value
  cancelContractForm.feedback = cancelBpFeedback.value
  cancelContractForm.post(route('provider.finances.bp-contract.cancel', { contract: activeContract.value.id }), {
    preserveScroll: true,
    onSuccess: () => { modals.value.cancelBpContract = false; cancelContractForm.reset() },
  })
}
function openBpHistory(con)      { activeContract.value = con; activeTab.value = 'history'; txSearch.value = con.bp_name }

// ── Auto-pay ─────────────────────────────────────────────────────────────
function openAutoPay(con) {
  activeContract.value = con
  autoPayForm.enabled   = !!con.autopay_enabled
  autoPayForm.day       = con.autopay_day || '1st'
  autoPayForm.method_id = con.autopay_method_id || ''
  autoPayForm.notify    = con.autopay_notify || '3_days'
  autoPayForm.limit     = con.autopay_limit ?? ''
  modals.value.autoPay = true
}
function doSaveAutoPay() {
  if (!activeContract.value) return
  autoPayForm.post(route('provider.finances.bp-contract.autopay', { contract: activeContract.value.id }), {
    preserveScroll: true,
    onSuccess: () => { modals.value.autoPay = false },
  })
}

// ── CS actions ───────────────────────────────────────────────────────────
const cancelCsReason   = ref('Replacing with another Continuity Steward')
const selectedPayModel = ref('retainer')
const cancelCsForm     = useForm({ reason: 'Replacing with another Continuity Steward' })
const payModelForm     = useForm({ payment_model: 'retainer' })
const autoPayForm      = useForm({ enabled: false, day: '1st', method_id: '', notify: '3_days', limit: '' })
const payModelOptions   = [
  { value: 'retainer',         label: 'Retainer',             icon: 'clock',    desc: 'A recurring standby retainer keeps the Continuity Steward engaged and ready to act.' },
  { value: 'annual_fee',       label: 'Annual Fee',           icon: 'calendar', desc: 'A single annual standby fee for the arrangement.' },
  { value: 'retainer_annual',  label: 'Retainer + Annual Fee',icon: 'shield',   desc: 'Combines a recurring retainer with an annual standby fee.' },
]

function openPayArrangement(cs) { activeCs.value = cs; modals.value.payArrangement = true }
function openChangePayModel(cs) { activeCs.value = cs; selectedPayModel.value = cs.payment_model || 'retainer'; modals.value.changePayModel = true }
function doCancelCsAgreement()  {
  if (!activeCs.value) return
  cancelCsForm.reason = cancelCsReason.value
  cancelCsForm.post(route('provider.finances.cs-steward.cancel', { steward: activeCs.value.id }), {
    preserveScroll: true,
    onSuccess: () => { modals.value.cancelCsAgreement = false; cancelCsForm.reset() },
  })
}
function doSavePayModel()       {
  if (!activeCs.value) return
  payModelForm.payment_model = selectedPayModel.value
  payModelForm.put(route('provider.finances.cs-steward.pay-model', { steward: activeCs.value.id }), {
    preserveScroll: true,
    onSuccess: () => { modals.value.changePayModel = false },
  })
}

// ── Payment methods ──────────────────────────────────────────────────────
const editCardNickname = ref('')
const editCardPurpose  = ref('All payments (default)')
const addPayType       = ref('card')
const addPayForm       = ref({ cardholder: '', cardNumber: '', expiry: '', cvv: '', bankHolder: '', routingNumber: '', accountNumber: '', accountType: 'Checking', purpose: 'All payments (default)' })

function setDefaultPm(pm)  {
  router.post(route('provider.settings.payment.default'), { payment_method_id: pm.id }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Default payment method updated'),
    onError: () => toast.error('Could not update default payment method.'),
  })
}
function doRemoveCard()    {
  if (!activePm.value) return
  router.delete(route('provider.settings.payment.remove'), {
    data: { payment_method_id: activePm.value.id },
    preserveScroll: true,
    onSuccess: () => { modals.value.removeCard = false; toast.info('Payment method removed') },
    onError: () => toast.error('Could not remove payment method.'),
  })
}
function doAddPayment()    { modals.value.addPayment = false; toast.info('Add a card via Settings → Billing, then return here.') }

// ── Spending controls ─────────────────────────────────────────────────────
const spendingControls = ref({ autoPay: true, approvalThreshold: 500, monthlyLimit: 5000 })
const spendControlsForm = useForm({ auto_pay: true, approval_threshold: 500, monthly_limit: 5000 })
function saveSpendingControls() {
  spendControlsForm.auto_pay           = spendingControls.value.autoPay
  spendControlsForm.approval_threshold = spendingControls.value.approvalThreshold
  spendControlsForm.monthly_limit      = spendingControls.value.monthlyLimit
  spendControlsForm.post(route('provider.finances.spending-controls'), {
    preserveScroll: true,
    onSuccess: () => toast.success('Spending controls saved'),
    onError: () => toast.error('Could not save spending controls.'),
  })
}

// ── Export ────────────────────────────────────────────────────────────────
const exportForm = ref({ from: '2026-01-01', to: '2026-02-28', allTx: true, csActivity: true, bpInvoices: true, subscription: false, format: 'CSV' })

// ── Hire BP form ──────────────────────────────────────────────────────────
const hireBpSearch = ref(''); const hireBpCategory = ref('Medical Billing'); const hireBpContractType = ref('Monthly Retainer')
const hireBpBudget = ref(''); const hireBpStart = ref(''); const hireBpScope = ref(''); const hireBpPaymentMethod = ref('')

// ── Helpers ───────────────────────────────────────────────────────────────
const spendTotal = computed(() => props.spendBreakdown.reduce((s, d) => s + d.amount, 0))

const firstPendingDesc = computed(() => {
  if (!props.upcomingPayments?.length) return ''
  const f = props.upcomingPayments[0]
  let s = `${f.bp_name} submitted an invoice for ${formatCents(f.total_cents)}`
  if (f.due_at) s += ` — due ${f.due_at}`
  if (props.pendingInvoiceCount > 1) s += ` (+${props.pendingInvoiceCount - 1} more)`
  return s
})

function formatCents(cents) {
  const n = Number(cents ?? 0) / 100
  return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatMoney(n) {
  return '$' + Math.round(Number(n ?? 0)).toLocaleString()
}

function statusVariant(s) {
  return {
    active: 'green', trialing: 'blue', past_due: 'gold', canceled: 'neutral',
    paid: 'green', sent: 'blue', overdue: 'red', draft: 'neutral', void: 'neutral',
    disputed: 'gold',
    pending: 'gold', failed: 'red', refunded: 'neutral', partially_refunded: 'gold',
  }[s] ?? 'neutral'
}

function payModelLabel(model) {
  return { retainer: 'Retainer', annual_fee: 'Annual Fee', retainer_annual: 'Retainer + Annual Fee' }[model] ?? 'Retainer'
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
.spend-bd-bar span:first-child { border-radius: var(--radius-full) 0 0 var(--radius-full); }
.spend-bd-bar span:last-child  { border-radius: 0 var(--radius-full) var(--radius-full) 0; }
.spend-bd-list  { display: flex; flex-direction: column; }
.spend-bd-row   { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--border); }
.spend-bd-row:last-child { border-bottom: none; padding-bottom: 0; }
.spend-bd-dot   { width: 10px; height: 10px; border-radius: var(--radius-full); flex-shrink: 0; }
.spend-bd-name  { flex: 1; font-size: 13px; font-weight: 600; color: var(--text-2); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.spend-bd-amt   { font-size: 13px; font-weight: 700; color: var(--text); }
.spend-bd-pct   { font-size: 11px; font-weight: 700; color: var(--text-4); width: 40px; text-align: right; flex-shrink: 0; }
.spend-bd-empty { text-align: center; padding: 20px; color: var(--text-3); font-size: 13px; }

/* ── Upcoming payments ── */
.upcoming-row             { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--border); }
.upcoming-row.clickable   { margin: 0 -8px; padding: 12px 8px; border-radius: var(--radius-sm); transition: background var(--transition); cursor: pointer; }
.upcoming-row.clickable:hover { background: var(--surface-2); }
.upcoming-row:last-child  { border-bottom: none; padding-bottom: 0; }
.upcoming-row:first-child { padding-top: 0; }
.upcoming-date-badge      { width: 44px; height: 44px; border-radius: var(--radius); background: var(--surface-2); border: 1px solid var(--border); display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0; }
.upcoming-date-badge--urgent { background: var(--orange-light); border-color: var(--orange); }
.upcoming-month           { font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--text-3); }
.upcoming-day             { font-family: var(--font-serif); font-size: 17px; font-weight: 700; color: var(--text); line-height: 1; }
.upcoming-text--urgent    { color: var(--orange-dark); }
.upcoming-info            { flex: 1; min-width: 0; }
.upcoming-name            { font-size: 13px; font-weight: 700; color: var(--text); }
.upcoming-desc            { font-size: 12px; color: var(--text-3); margin-top: 1px; }
.upcoming-amount          { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); text-align: right; white-space: nowrap; }
.upcoming-amount--urgent  { color: var(--orange-dark); }
.upcoming-type            { font-size: 11px; color: var(--text-4); margin-top: 1px; text-align: right; }

/* ── CS payment cards ── */
.cspay-card        { position: relative; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 14px; transition: box-shadow var(--transition); }
.cspay-card:hover  { box-shadow: var(--shadow); }
.cspay-card::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--gold-dark); }
.cspay-body        { padding: 22px 24px; }
.cspay-top         { display: flex; align-items: center; gap: 14px; }
.cspay-id          { flex: 1; min-width: 0; }
.cspay-name-row    { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; }
.cspay-name        { font-family: var(--font-serif); font-size: 16px; font-weight: 600; color: var(--text); letter-spacing: -0.01em; text-decoration: none; }
.cspay-name:hover  { color: var(--gold-dark); }
.cspay-role        { font-size: 12px; color: var(--text-3); margin-top: 3px; }
.cspay-actions     { display: flex; gap: 6px; flex-shrink: 0; }
.cspay-meta        { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin: 20px 0; padding: 18px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
.cspay-meta-item:not(:last-child) { border-right: 1px solid var(--border); }
.cspay-meta-label  { font-size: 10px; font-weight: 600; letter-spacing: 0.8px; text-transform: uppercase; color: var(--text-4); margin-bottom: 7px; }
.cspay-meta-value  { font-family: var(--font-serif); font-weight: 600; color: var(--text); line-height: 1; font-size: 15px; }
.cspay-meta-value.amount { font-size: 22px; }
.cspay-note        { display: flex; align-items: flex-start; gap: 10px; padding: 13px 15px; border-radius: var(--radius); background: var(--blue-light); border: 1px solid rgba(74,144,196,0.3); }
.cspay-note p      { font-size: 12px; color: var(--text-2); line-height: 1.5; margin: 0; }

/* ── Stripe Connect pill ── */
.connect-pill         { display: inline-flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; letter-spacing: 0.4px; text-transform: uppercase; padding: 3px 9px; border-radius: var(--radius-full); white-space: nowrap; }
.connect-pill.is-connected     { background: var(--green-light);  color: var(--green-dark); }
.connect-pill.is-not-connected { background: var(--orange-light); color: var(--orange-dark); }
.connect-pill .status-dot      { width: 6px; height: 6px; border-radius: var(--radius-full); flex-shrink: 0; }
.connect-pill.is-connected .status-dot     { background: var(--green-dark); }
.connect-pill.is-not-connected .status-dot { background: var(--orange-dark); }

/* ── Invoice cards (BP) ── */
.invoice-card        { position: relative; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 14px; transition: box-shadow var(--transition); }
.invoice-card:hover  { box-shadow: var(--shadow); }
.invoice-card::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; }
.invoice-card.pending-approval::before { background: var(--orange); }
.invoice-card.active-contract::before  { background: var(--green); }
.invoice-body        { padding: 22px 24px 20px; }
.invoice-status      { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
.invoice-status-right { display: inline-flex; align-items: center; gap: 10px; }
.invoice-auto        { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-4); }
.invoice-head        { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; margin-bottom: 20px; }
.invoice-vendor      { font-family: var(--font-serif); font-size: 20px; font-weight: 600; color: var(--text); line-height: 1.2; letter-spacing: -0.01em; }
.invoice-service     { font-size: 13px; color: var(--text-3); margin-top: 5px; }
.invoice-amount      { font-family: var(--font-serif); font-size: 28px; font-weight: 700; color: var(--text); line-height: 1; text-align: right; white-space: nowrap; }
.invoice-period      { font-size: 12px; color: var(--text-4); margin-top: 5px; text-align: right; }
.invoice-meta        { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px 18px; padding: 16px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); margin-bottom: 18px; }
.invoice-meta-label  { font-size: 10px; font-weight: 600; letter-spacing: 0.8px; text-transform: uppercase; color: var(--text-4); margin-bottom: 5px; }
.invoice-meta-value  { font-size: 13px; font-weight: 600; color: var(--text-2); }
.invoice-meta-value.due { color: var(--orange-dark); font-weight: 700; }
.invoice-actions     { display: flex; align-items: center; gap: 10px; }
.btn-primary-full    { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 7px; height: 40px; padding: 0 18px; border-radius: var(--radius-sm); font-family: var(--font-sans); font-size: 13px; font-weight: 700; background: var(--text); color: var(--text-inverted); border: none; cursor: pointer; transition: background var(--transition); }
.btn-primary-full:hover { background: var(--text-2); }
.invoice-actions .btn-icon { width: 40px; height: 40px; border-radius: var(--radius-sm); background: var(--surface); border: 1px solid var(--border-dark); color: var(--text-3); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: background var(--transition), border-color var(--transition), color var(--transition); }
.invoice-actions .btn-icon:hover { background: var(--surface-2); border-color: var(--text-4); color: var(--text); }
.invoice-actions .btn-icon.btn-icon-danger:hover { background: var(--red-light); border-color: rgba(160,45,34,0.4); color: var(--red-dark); }

/* ── BP toolbar ── */
.bp-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }

/* ── Payment method cards ── */
.pm-card          { display: flex; align-items: center; gap: 14px; padding: 14px 18px; border: 1px solid var(--border); border-radius: var(--radius-lg); margin-bottom: 10px; background: var(--surface); transition: border-color var(--transition), box-shadow var(--transition); box-shadow: var(--shadow-sm); }
.pm-card:hover    { border-color: var(--soft-gold); }
.pm-card.default  { border-color: var(--gold-dark); background: var(--badge-bg-gold); }
.pm-logo          { width: 48px; height: 32px; border-radius: var(--radius-sm); background: var(--surface-2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.pm-info          { flex: 1; }
.pm-name          { font-size: 13px; font-weight: 700; color: var(--text); }
.pm-meta          { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.pm-purpose       { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; padding: 2px 8px; border-radius: var(--radius-full); background: var(--surface-3); color: var(--text-3); }
.pm-card-actions  { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }
.pm-card-btns     { display: flex; gap: 6px; }

/* ── Spending controls ── */
.spending-input-wrap { display: flex; align-items: center; gap: 6px; }
.spending-prefix     { font-size: 14px; font-weight: 700; color: var(--text-3); }
.setting-save-row    { display: flex; justify-content: flex-end; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border); }

/* ── Transaction table ── */
.fin-toolbar     { display: flex; gap: 8px; align-items: center; margin-bottom: 16px; flex-wrap: wrap; }
.fin-tx-table    { width: 100%; }
.tx-date         { padding-left: 20px; white-space: nowrap; color: var(--text-3); }
.tx-payee-link   { font-weight: 700; color: var(--gold-dark); text-decoration: none; }
.tx-payee        { font-weight: 700; color: var(--text); }
.tx-desc         { font-size: 13px; color: var(--text-2); }
.tx-method       { font-size: 12px; color: var(--text-3); }
.tx-action       { padding-right: 20px; text-align: right; width: 48px; }
.tx-cat-wrap     { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; }
.tx-cat-dot      { width: 8px; height: 8px; border-radius: var(--radius-full); display: inline-block; flex-shrink: 0; }
.tx-amount-out   { font-weight: 700; color: var(--red-dark); }
.tx-amount-in    { font-weight: 700; color: var(--green-dark); }

/* ── Receipt ── */
.receipt      { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; }
.receipt-row  { display: flex; justify-content: space-between; font-size: 13px; padding: 5px 0; }
.receipt-row.total { border-top: 1px solid var(--border-dark); font-weight: 700; font-size: 14px; padding-top: 10px; margin-top: 4px; }

/* ── View receipt ── */
.vr-vendor     { font-size: 16px; font-weight: 700; color: var(--text); font-family: var(--font-serif); }
.vr-vendor-sub { font-size: 12px; color: var(--text-3); }

/* ── Contract preview ── */
.contract-preview       { background: var(--surface-2); border-radius: var(--radius); padding: 16px; font-size: 12px; color: var(--text-2); line-height: 1.8; border: 1px solid var(--border); }
.contract-preview-title { font-family: var(--font-serif); font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
.contract-preview-row   { color: var(--text-2); }

/* ── Export checks ── */
.export-checks { display: flex; flex-direction: column; gap: 8px; margin-top: 6px; }

/* ── Role option (pay model selector) ── */
.role-option          { display: flex; align-items: flex-start; gap: 12px; padding: 12px 14px; border-radius: var(--radius); border: 1px solid var(--border); margin-bottom: 8px; cursor: pointer; transition: border-color var(--transition), background var(--transition); }
.role-option:hover    { background: var(--surface-2); border-color: var(--border-dark); }
.role-option.selected { border-color: var(--gold-dark); background: var(--badge-bg-gold); }
.role-option:last-child { margin-bottom: 0; }

/* ── CS note warning variant ── */
.cspay-note--warning               { background: var(--orange-light); border-color: var(--soft-gold); }
.cspay-note--warning p             { color: var(--orange-dark); }
.cspay-note-icon--warning          { color: var(--orange-dark); flex-shrink: 0; margin-top: 1px; }

/* ── Misc ── */
.btn-dark        { background: var(--text); border: 1px solid var(--text); color: var(--text-inverted); font-family: var(--font-sans); font-size: 13px; font-weight: 700; }
.btn-dark:hover  { background: var(--text-2); border-color: var(--text-2); box-shadow: var(--shadow-sm); }
.text-muted      { color: var(--text-4); }
.action-cell     { display: flex; align-items: center; gap: 6px; white-space: nowrap; }
</style>
