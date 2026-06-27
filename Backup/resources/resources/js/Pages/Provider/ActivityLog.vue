<template>
  <AppLayout :user="user" portal="practitioner" activePage="activity" pageTitle="Activity Log">
    <div class="hero-banner is-quiet">
      <div class="page-hero-inner">
        <div class="page-hero-text">
          <div class="page-hero-eyebrow">Activity</div>
          <h1 class="page-hero-title">Activity Log</h1>
        <p class="page-hero-sub">View a complete timeline of actions and events across your account.</p>
        </div>
        <div class="page-hero-actions"></div>
      </div>
    </div>
    <div class="stat-chip-row">
      <div class="stat-chip"><span class="stat-chip-label">Today</span><span class="stat-chip-value">4</span></div>
      <div class="stat-chip"><span class="stat-chip-label">This Week</span><span class="stat-chip-value">18</span></div>
      <div class="stat-chip"><span class="stat-chip-label">Unread</span><span class="stat-chip-value">3</span></div>
    </div>    <div class="dh-sh">
      <div><div class="dh-sh-eyebrow">Timeline</div><h2 class="dh-sh-title">Recent Activity</h2></div>
    </div>
    <div class="pg-list">
      <div class="pg-item"><div class="pg-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div><div class="pg-item-content"><div class="pg-item-title">Continuity plan annual review completed</div><div class="pg-item-sub">Marcus Webb reviewed and approved your continuity plan.</div></div><span class="pg-item-meta">2 hours ago</span></div>
      <div class="pg-item"><div class="pg-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div class="pg-item-content"><div class="pg-item-title">Document uploaded: Insurance Certificate</div><div class="pg-item-sub">Professional liability insurance certificate uploaded to vault.</div></div><span class="pg-item-meta">Yesterday</span></div>
      <div class="pg-item"><div class="pg-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div><div class="pg-item-content"><div class="pg-item-title">New network connection: Dr. Lisa Wang</div><div class="pg-item-sub">Dr. Lisa Wang accepted your connection request.</div></div><span class="pg-item-meta">2 days ago</span></div>
      <div class="pg-item"><div class="pg-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div class="pg-item-content"><div class="pg-item-title">Invoice paid: Acme Health Services</div><div class="pg-item-sub">Payment of $2,200 processed for medical billing services.</div></div><span class="pg-item-meta">3 days ago</span></div>
      <div class="pg-item"><div class="pg-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div><div class="pg-item-content"><div class="pg-item-title">New message from Linda Johnson</div><div class="pg-item-sub">Support Steward sent you a message about Q1 task updates.</div></div><span class="pg-item-meta">4 days ago</span></div>
      <div class="pg-item"><div class="pg-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div><div class="pg-item-content"><div class="pg-item-title">Critical incident drill completed</div><div class="pg-item-sub">Annual drill completed successfully. All protocols verified.</div></div><span class="pg-item-meta">1 week ago</span></div>
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