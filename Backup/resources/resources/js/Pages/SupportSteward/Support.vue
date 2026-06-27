<template>
  <AppLayout :user="user" portal="support_steward" activePage="support" pageTitle="Support &amp; Feedback" :has-emergency="true">

    <!-- ═══ TAB BAR ═══ -->
    <div class="sp-tabbar">
      <div class="sp-tabs">
        <button v-for="t in tabs" :key="t.key" type="button" class="sp-tab" :class="{ active: activeTab === t.key }" @click="activeTab = t.key">{{ t.label }}</button>
      </div>
      <button type="button" class="btn btn-gold btn-sm" @click="openTicket"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> New Ticket</button>
    </div>

    <!-- ═══ MY TICKETS ═══ -->
    <div v-if="activeTab === 'tickets'">
      <div v-if="tickets.length" class="sp-ticket-list">
        <div v-for="tk in tickets" :key="tk.id" class="sp-ticket">
          <div class="sp-ticket-main">
            <div class="sp-ticket-subject">{{ tk.subject }}</div>
            <div class="sp-ticket-meta">{{ tk.category }} · {{ tk.created }}</div>
          </div>
          <span class="sp-ticket-status" :class="tk.status">{{ tk.statusLabel }}</span>
        </div>
      </div>
      <div v-else class="sp-empty">
        <div class="sp-empty-ico"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg></div>
        <div class="sp-empty-title">No tickets yet</div>
        <div class="sp-empty-sub">When you submit a support request, it will appear here. Our team typically responds within 24 hours.</div>
        <button type="button" class="btn btn-gold btn-sm" @click="openTicket"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Submit a Request</button>
      </div>
    </div>

    <!-- ═══ FEEDBACK ═══ -->
    <div v-else-if="activeTab === 'feedback'" class="sp-card">
      <div class="sp-card-title">Share your thoughts</div>
      <div v-if="feedbackSent" class="sp-success"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Thanks for your feedback — we've received it.</div>
      <div class="sp-field">
        <label class="sp-label">Subject</label>
        <input v-model="feedback.subject" class="sp-input" placeholder="Brief summary (optional)" />
      </div>
      <div class="sp-field">
        <label class="sp-label">Category</label>
        <div class="sp-seg">
          <button v-for="c in feedbackCategories" :key="c" type="button" class="sp-seg-btn" :class="{ active: feedback.category === c }" @click="feedback.category = c">{{ c }}</button>
        </div>
      </div>
      <div class="sp-field">
        <label class="sp-label">Message <span class="sp-req">*</span></label>
        <textarea v-model="feedback.message" class="sp-textarea" rows="4" placeholder="Tell us what's on your mind..."></textarea>
      </div>
      <button type="button" class="btn btn-gold btn-sm" :disabled="!feedback.message.trim()" @click="sendFeedback"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Send Feedback</button>
    </div>

    <!-- ═══ HELP CENTER ═══ -->
    <div v-else>
      <div class="sp-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="helpSearch" placeholder="Search help articles..." />
      </div>
      <div class="sp-empty">
        <div class="sp-empty-ico"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>
        <div class="sp-empty-title">Help articles coming soon</div>
        <div class="sp-empty-sub">Our team is building out the Help Center. In the meantime, submit a support ticket and we'll respond within 24 hours.</div>
      </div>
    </div>

    <!-- ═══ FLOATING FEEDBACK BUTTON ═══ -->
    <button type="button" class="sp-fab" @click="activeTab = 'feedback'"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Feedback</button>

    <!-- ═══ NEW SUPPORT TICKET MODAL ═══ -->
    <Modal v-model="showTicket" title="New Support Ticket" size="sm">
      <div class="sp-field"><label class="sp-label">Subject <span class="sp-req">*</span></label><input v-model="ticket.subject" class="sp-input" placeholder="Brief description of your issue" /></div>
      <div class="sp-field"><label class="sp-label">Category</label><select v-model="ticket.category" class="form-select"><option v-for="c in ticketCategories" :key="c">{{ c }}</option></select></div>
      <div class="sp-field" style="margin-bottom:10px"><label class="sp-label">Message <span class="sp-req">*</span></label><textarea v-model="ticket.message" class="sp-textarea" rows="4" placeholder="Describe your issue in detail. Include steps to reproduce if reporting a bug."></textarea></div>
      <div class="sp-modal-note">Our team typically responds within 24 hours on business days.</div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showTicket = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" :disabled="!ticket.subject.trim() || !ticket.message.trim()" @click="submitTicket"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Submit Ticket</button>
      </template>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
import Modal from '../../Components/Modal.vue';
defineProps({ user: Object });

const activeTab = ref('tickets');
const tabs = [
  { key: 'tickets', label: 'My Tickets' },
  { key: 'feedback', label: 'Feedback' },
  { key: 'help', label: 'Help Center' },
];

const feedbackCategories = ['General', 'Feature Request', 'Bug Report', 'Praise'];
const feedback = reactive({ subject: '', category: 'General', message: '' });
const feedbackSent = ref(false);
function sendFeedback() {
  if (!feedback.message.trim()) return;
  feedbackSent.value = true;
  feedback.subject = '';
  feedback.category = 'General';
  feedback.message = '';
}

const helpSearch = ref('');

const tickets = ref([]);
const ticketCategories = ['General Support', 'Technical Issue', 'Billing & Payments', 'Account & Access', 'Feature Request', 'Bug Report'];
const showTicket = ref(false);
const ticket = reactive({ subject: '', category: 'General Support', message: '' });
function openTicket() {
  ticket.subject = '';
  ticket.category = 'General Support';
  ticket.message = '';
  showTicket.value = true;
}
function submitTicket() {
  if (!ticket.subject.trim() || !ticket.message.trim()) return;
  tickets.value.unshift({
    id: tickets.value.length + 1,
    subject: ticket.subject,
    category: ticket.category,
    created: 'Just now',
    status: 'open',
    statusLabel: 'Open',
  });
  showTicket.value = false;
  activeTab.value = 'tickets';
}
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════
   SUPPORT STEWARD — SUPPORT & FEEDBACK
   ════════════════════════════════════════════════════════════════ */

.sp-tabbar { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:18px; flex-wrap:wrap; }
.sp-tabs { display:flex; align-items:center; gap:4px; }
.sp-tab { padding:9px 16px; font-size:13px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius); cursor:pointer; transition:all .15s ease; }
.sp-tab:hover { color:var(--text); }
.sp-tab.active { background:var(--surface); border:1px solid var(--border); color:var(--text); box-shadow:var(--shadow-xs); }

/* CARD (feedback) */
.sp-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-xl,18px); box-shadow:var(--shadow-sm); padding:24px 28px; max-width:none; }
.sp-card-title { font-size:15px; font-weight:700; color:var(--text); margin-bottom:18px; }
.sp-success { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:var(--green-dark); background:var(--green-light); border:1px solid color-mix(in srgb, var(--green-dark) 22%, transparent); border-radius:var(--radius); padding:11px 14px; margin-bottom:18px; }

.sp-field { margin-bottom:18px; }
.sp-label { display:block; font-size:12.5px; font-weight:600; color:var(--text-2); margin-bottom:8px; }
.sp-req { color:var(--red); }
.sp-input { display:block; width:100%; padding:11px 14px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.sp-input:focus { border-color:var(--gold-dark); }
.sp-input::placeholder { color:var(--text-4); }
.sp-textarea { display:block; width:100%; padding:12px 14px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; resize:vertical; min-height:110px; line-height:1.55; font-family:var(--font-sans); transition:border-color .15s ease; }
.sp-textarea:focus { border-color:var(--gold-dark); }
.sp-textarea::placeholder { color:var(--text-4); }
.sp-modal-note { font-size:12px; color:var(--text-4); }

/* segmented category */
.sp-seg { display:inline-flex; align-items:center; gap:0; border:1px solid var(--border-dark); border-radius:var(--radius); overflow:hidden; flex-wrap:wrap; }
.sp-seg-btn { padding:9px 18px; font-size:12.5px; font-weight:600; color:var(--text-3); background:var(--surface); border:none; border-right:1px solid var(--border); cursor:pointer; transition:all .15s ease; }
.sp-seg-btn:last-child { border-right:none; }
.sp-seg-btn:hover { color:var(--text); }
.sp-seg-btn.active { background:var(--gold-dark); color:var(--text-inverted); }

/* help search */
.sp-search { position:relative; margin-bottom:8px; }
.sp-search svg { position:absolute; left:15px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.sp-search input { width:100%; padding:13px 16px 13px 40px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); outline:none; transition:border-color .15s ease; }
.sp-search input:focus { border-color:var(--gold-dark); }
.sp-search input::placeholder { color:var(--text-4); }

/* tickets list */
.sp-ticket-list { display:flex; flex-direction:column; gap:10px; }
.sp-ticket { display:flex; align-items:center; justify-content:space-between; gap:14px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); padding:16px 20px; }
.sp-ticket-subject { font-size:13.5px; font-weight:700; color:var(--text); }
.sp-ticket-meta { font-size:12px; color:var(--text-3); margin-top:3px; }
.sp-ticket-status { font-size:10px; font-weight:700; letter-spacing:.5px; padding:5px 12px; border-radius:var(--radius-full); flex-shrink:0; }
.sp-ticket-status.open { background:var(--badge-bg-gold); color:var(--gold-dark); }

/* empty state */
.sp-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:72px 24px; }
.sp-empty-ico { width:54px; height:54px; border-radius:50%; background:var(--surface-3); color:var(--text-4); display:flex; align-items:center; justify-content:center; margin-bottom:16px; }
.sp-empty-title { font-family:var(--font-serif); font-size:19px; font-weight:600; color:var(--text); margin-bottom:8px; }
.sp-empty-sub { font-size:13px; color:var(--text-4); line-height:1.65; max-width:420px; margin:0 auto 18px; }

/* floating feedback button */
.sp-fab { position:fixed; right:28px; bottom:28px; display:inline-flex; align-items:center; gap:8px; padding:11px 18px; font-size:13px; font-weight:600; color:var(--text-inverted); background:var(--gold-dark); border:none; border-radius:var(--radius-full); box-shadow:var(--shadow-md, 0 6px 20px rgba(0,0,0,0.18)); cursor:pointer; transition:transform .15s ease, background .15s ease; z-index:40; }
.sp-fab:hover { transform:translateY(-2px); }

@media (max-width:600px) { .sp-tabbar { flex-direction:column; align-items:stretch; } }
</style>
