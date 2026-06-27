<template>
  <AppLayout :user="user" portal="practitioner" activePage="finances" pageTitle="Finances">
    <div class="hero-banner is-quiet">
      <div class="page-hero-inner">
        <div class="page-hero-text">
          <div class="page-hero-eyebrow">Finances</div>
          <h1 class="page-hero-title">Finances</h1>
        <p class="page-hero-sub">Manage payments, invoices, and financial arrangements.</p>
        </div>
        <div class="page-hero-actions"></div>
      </div>
    </div>
    <div class="stat-chip-row">
      <div class="stat-chip"><span class="stat-chip-label">Total Spend</span><span class="stat-chip-value">$12,450</span></div>
      <div class="stat-chip"><span class="stat-chip-label">Pending Invoices</span><span class="stat-chip-value">2</span> <span class="stat-chip-sub">$3,800</span></div>
      <div class="stat-chip"><span class="stat-chip-label">Active Contracts</span><span class="stat-chip-value">3</span></div>
      <div class="stat-chip"><span class="stat-chip-label">Subscription</span><span class="stat-chip-value">$299/mo</span></div>
    </div>
    <div class="card" style="padding: 24px 28px; margin-bottom: 20px;">
      <div class="dh-sh"><div><div class="dh-sh-eyebrow">Upcoming</div><h2 class="dh-sh-title">Pending Invoices</h2></div></div>
      <div class="pg-list">
        <div class="pg-item"><div class="pg-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div class="pg-item-content"><div class="pg-item-title">Acme Health Services — Medical Billing</div><div class="pg-item-sub">Invoice #INV-2025-0042 — Due: Jan 15, 2025</div></div><span class="badge badge-warning">$2,200</span><span class="pg-item-meta">14 days left</span></div>
        <div class="pg-item"><div class="pg-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div class="pg-item-content"><div class="pg-item-title">TechMed Solutions — EHR Support</div><div class="pg-item-sub">Invoice #INV-2025-0039 — Due: Jan 20, 2025</div></div><span class="badge badge-warning">$1,600</span><span class="pg-item-meta">19 days left</span></div>
      </div>
    </div>
    <div class="card" style="padding: 24px 28px;">
      <div class="dh-sh"><div><div class="dh-sh-eyebrow">Payment Methods</div><h2 class="dh-sh-title">Cards on File</h2></div></div>
      <div class="pg-item" style="border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 14px 18px;">
        <div class="pg-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
        <div class="pg-item-content"><div class="pg-item-title">Visa ending in 4242</div><div class="pg-item-sub">Expires 08/2027 — Default payment method</div></div><span class="badge badge-green">Default</span>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '../../Components/AppLayout.vue';
defineProps({ user: Object });
</script>

<style scoped>
.hero-banner.is-quiet {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-xl, 18px); padding: 28px 32px;
  margin-bottom: 22px; border-left: 4px solid var(--gold-dark, #a0813e);
}
.page-hero-inner { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
.page-hero-eyebrow { font-size: 10px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; color: var(--gold-dark); margin-bottom: 8px; }
.page-hero-title { font-family: var(--font-serif, 'Spectral', Georgia, serif); font-size: 28px; font-weight: 600; color: var(--text); margin: 0; letter-spacing: -0.3px; }
.page-hero-sub { font-size: 13.5px; color: var(--text-3); line-height: 1.6; margin-top: 6px; max-width: 600px; }
.page-hero-actions { flex-shrink: 0; }
.stat-chip-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
.stat-chip { padding: 14px 18px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg, 14px); min-width: 140px; flex: 1; }
.stat-chip-label { font-size: 10px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: var(--text-4); display: block; margin-bottom: 6px; }
.stat-chip-value { font-family: var(--font-serif); font-size: 24px; font-weight: 700; color: var(--text); display: block; }
.stat-chip-sub { font-size: 11px; color: var(--text-4); margin-top: 2px; display: block; }
.dh-sh { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
.dh-sh-eyebrow { font-size: 10px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; color: var(--gold-dark); margin-bottom: 4px; }
.dh-sh-title { font-family: var(--font-serif); font-size: 20px; font-weight: 600; color: var(--text); margin: 0; }
.dh-sh-link { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; color: var(--gold-dark); text-decoration: none; }
.dh-sh-link:hover { text-decoration: underline; }
.pg-grid { display: grid; grid-template-columns: repeat(var(--pg-cols, 2), 1fr); gap: 14px; margin-bottom: 28px; }
.pg-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 10px; }
.pg-card-title { font-family: var(--font-serif); font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 2px; }
.pg-card-sub { font-size: 12px; color: var(--text-3); }
.pg-card-body { font-size: 13px; color: var(--text-2); line-height: 1.6; }
.pg-card-actions { display: flex; gap: 8px; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border); }
.pg-list { margin-bottom: 24px; }
.pg-item { display: flex; align-items: center; gap: 14px; padding: 14px 18px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg, 14px); margin-bottom: 8px; transition: border-color var(--transition); }
.pg-item:hover { border-color: var(--gold); }
.pg-item-icon { width: 38px; height: 38px; border-radius: 50%; background: var(--surface-2); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.pg-item-content { flex: 1; min-width: 0; }
.pg-item-title { font-size: 13.5px; font-weight: 600; color: var(--text); margin-bottom: 2px; }
.pg-item-sub { font-size: 12px; color: var(--text-3); }
.pg-item-meta { font-size: 11px; color: var(--text-4); flex-shrink: 0; }
.table-wrap { overflow-x: auto; border-radius: var(--radius-lg); border: 1px solid var(--border); margin-bottom: 24px; }
.table { width: 100%; border-collapse: collapse; }
.table th { font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--text-4); padding: 12px 16px; text-align: left; background: var(--surface-2); border-bottom: 1px solid var(--border); }
.table td { font-size: 13px; color: var(--text-2); padding: 12px 16px; border-bottom: 1px solid var(--border); }
.table tr:last-child td { border-bottom: none; }
.table tr:hover td { background: var(--surface-2); }
.settings-layout { display: grid; grid-template-columns: 220px 1fr; gap: 28px; }
.settings-nav { list-style: none; }
.settings-nav li { padding: 10px 14px; font-size: 13px; font-weight: 500; color: var(--text-2); border-radius: var(--radius); cursor: pointer; margin-bottom: 2px; transition: all var(--transition); }
.settings-nav li:hover { background: var(--surface-2); color: var(--text); }
.settings-nav li.active { background: var(--surface-2); color: var(--gold-dark); font-weight: 700; }
.settings-panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-xl, 18px); padding: 28px 30px; }
.settings-section-title { font-family: var(--font-serif); font-size: 18px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
.settings-section-desc { font-size: 13px; color: var(--text-3); margin-bottom: 22px; line-height: 1.5; }
.pg-form-group { margin-bottom: 18px; }
.pg-form-label { display: block; font-size: 10.5px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: var(--text-2); margin-bottom: 6px; }
.pg-form-input { display: block; width: 100%; padding: 10px 13px; font-size: 13px; color: var(--text); background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-sm, 8px); transition: border-color var(--transition), box-shadow var(--transition); outline: none; }
.pg-form-input:focus { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(196,169,106,0.18); }
.pg-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.empty-state { text-align: center; padding: 48px 24px; color: var(--text-4); font-size: 14px; }
.empty-state-icon { width: 56px; height: 56px; border-radius: 50%; background: var(--surface-2); color: var(--text-4); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
.msg-layout { display: grid; grid-template-columns: 320px 1fr; gap: 0; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-xl, 18px); overflow: hidden; min-height: 500px; }
.msg-sidebar { border-right: 1px solid var(--border); }
.msg-search { padding: 14px; border-bottom: 1px solid var(--border); }
.msg-threads { max-height: 500px; overflow-y: auto; }
.msg-thread { display: flex; align-items: center; gap: 12px; padding: 14px 16px; cursor: pointer; border-bottom: 1px solid var(--border); transition: background var(--transition); }
.msg-thread:hover, .msg-thread.active { background: var(--surface-2); }
.msg-thread.is-unread { background: rgba(196,169,106,0.05); }
.msg-avatar { width: 38px; height: 38px; border-radius: 50%; background: var(--gold-dark); color: #fff; display: flex; align-items: center; justify-content: center; font-family: var(--font-serif); font-size: 13px; font-weight: 700; flex-shrink: 0; }
.msg-thread-info { flex: 1; min-width: 0; }
.msg-thread-name { font-size: 13px; font-weight: 600; color: var(--text); }
.msg-thread-preview { font-size: 12px; color: var(--text-3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.msg-thread-time { font-size: 11px; color: var(--text-4); flex-shrink: 0; }
.msg-main { display: flex; flex-direction: column; }
.msg-conv-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--border); }
.msg-conv-name { font-size: 14px; font-weight: 600; color: var(--text); }
.msg-conv-role { font-size: 12px; color: var(--text-3); }
.msg-conv-body { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 14px; }
.msg-bubble { max-width: 75%; }
.msg-bubble.msg-incoming { align-self: flex-start; }
.msg-bubble.msg-outgoing { align-self: flex-end; }
.msg-bubble-text { padding: 10px 14px; border-radius: 14px; font-size: 13px; line-height: 1.55; }
.msg-incoming .msg-bubble-text { background: var(--surface-2); color: var(--text-2); }
.msg-outgoing .msg-bubble-text { background: var(--gold-dark); color: #fff; }
.msg-bubble-time { font-size: 10px; color: var(--text-4); margin-top: 4px; }
.msg-outgoing .msg-bubble-time { text-align: right; }
.msg-compose { display: flex; gap: 10px; padding: 14px 20px; border-top: 1px solid var(--border); align-items: center; }
.msg-compose input { flex: 1; }
@media (max-width: 900px) { .pg-grid { grid-template-columns: 1fr !important; } .settings-layout { grid-template-columns: 1fr; } .pg-form-row { grid-template-columns: 1fr; } .stat-chip-row { flex-direction: column; } .msg-layout { grid-template-columns: 1fr; } .msg-sidebar { display: none; } }
</style>