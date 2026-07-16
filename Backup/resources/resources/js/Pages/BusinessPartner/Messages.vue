<template>
  <AppLayout :user="user" portal="business_partner" activePage="messages" pageTitle="Messages">

    <div class="ms-layout">
      <!-- ════════ THREAD LIST ════════ -->
      <aside class="ms-sidebar">
        <div class="ms-toolbar">
          <div class="ms-search">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input v-model="search" placeholder="Search messages.." />
          </div>
          <button type="button" class="ms-tool-btn" data-tip="New Message" @click="showNew = true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
          <button type="button" class="ms-tool-btn" data-tip="Set Availability" @click="showAvail = true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/></svg></button>
          <button type="button" class="ms-tool-btn" data-tip="Activity"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></button>
        </div>

        <div class="ms-pills">
          <button v-for="f in filters" :key="f.key" type="button" class="ms-pill" :class="{ active: filter === f.key }" @click="filter = f.key">{{ f.label }} <span class="ms-pill-count">{{ f.count }}</span></button>
        </div>

        <div class="ms-threads">
          <button v-for="t in threads" :key="t.id" type="button" class="ms-thread" :class="{ active: activeThread === t.id, unread: t.unread }" @click="activeThread = t.id">
            <div class="ms-thread-av">{{ t.initials }}</div>
            <div class="ms-thread-info">
              <div class="ms-thread-top"><span class="ms-thread-name">{{ t.name }}</span><span class="ms-thread-time">{{ t.time }}</span></div>
              <div class="ms-thread-preview">{{ t.preview }}</div>
            </div>
            <span v-if="t.unread" class="ms-unread-dot"></span>
          </button>
        </div>
      </aside>

      <!-- ════════ CONVERSATION ════════ -->
      <section class="ms-conv">
        <div class="ms-conv-head">
          <div class="ms-conv-av">{{ current.initials }}</div>
          <div class="ms-conv-info">
            <div class="ms-conv-name">{{ current.name }}</div>
            <div class="ms-conv-meta">{{ current.role }} · <span class="ms-conv-tag">{{ current.tag }}</span></div>
          </div>
          <div class="ms-conv-actions">
            <button type="button" class="ms-conv-btn" :class="{ active: showConvSearch }" @click="toggleConvSearch"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button>
            <button type="button" class="ms-conv-btn" :class="{ active: showContact }" @click="toggleContact"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg></button>
          </div>

          <!-- CONTACT POPOVER -->
          <div v-if="showContact" class="ms-contact-pop">
            <div class="ms-cp-top">
              <div class="ms-cp-name">{{ current.name }}</div>
              <div class="ms-cp-role">{{ current.role }} · {{ current.tag }}</div>
              <div class="ms-cp-org">{{ current.org }}</div>
              <div class="ms-cp-actions">
                <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> View Profile</button>
                <button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Refer</button>
              </div>
            </div>
            <div class="ms-cp-section">
              <div class="ms-cp-label">Contact</div>
              <div class="ms-cp-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> <span class="ms-cp-k">Email</span> <span class="ms-cp-v">{{ current.email }}</span></div>
              <div class="ms-cp-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.81.36 1.6.7 2.34a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.74-1.74a2 2 0 0 1 2.11-.45c.74.34 1.53.57 2.34.7A2 2 0 0 1 22 16.92z"/></svg> <span class="ms-cp-k">Phone</span> <span class="ms-cp-v">{{ current.phone }}</span></div>
              <div class="ms-cp-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> <span class="ms-cp-k">Based</span> <span class="ms-cp-v">{{ current.based }}</span></div>
            </div>
            <div class="ms-cp-section">
              <div class="ms-cp-label">Conversation</div>
              <div class="ms-cp-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> <span class="ms-cp-k">Messages</span> <span class="ms-cp-v">{{ messageCount }}</span></div>
              <div class="ms-cp-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> <span class="ms-cp-k">Last</span> <span class="ms-cp-v">{{ current.time }}</span></div>
            </div>
          </div>
        </div>

        <!-- IN-CONVERSATION SEARCH -->
        <div v-if="showConvSearch" class="ms-conv-search">
          <div class="ms-cs-input">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input v-model="convSearch" placeholder="Search in this conversation..." />
          </div>
          <span class="ms-cs-count">{{ convMatchLabel }}</span>
          <button type="button" class="ms-cs-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg></button>
          <button type="button" class="ms-cs-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></button>
          <button type="button" class="ms-cs-btn" @click="showConvSearch = false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>

        <div class="ms-conv-body">
          <div class="ms-e2e"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> End-to-end encrypted</div>
          <template v-for="(m, i) in current.messages" :key="i">
            <div v-if="m.type === 'divider'" class="ms-divider"><span>{{ m.label }}</span></div>
            <div v-else class="ms-msg" :class="m.dir">
              <div class="ms-bubble" :class="{ hit: isHit(m) }">
                <div class="ms-bubble-text">{{ m.text }}</div>
                <div class="ms-bubble-time">{{ m.time }}<svg v-if="m.dir === 'out'" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
              </div>
              <div class="ms-msg-av">{{ m.dir === 'out' ? me : current.initials }}</div>
            </div>
          </template>
        </div>

        <div class="ms-composer">
          <button type="button" class="ms-comp-btn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></button>
          <button type="button" class="ms-comp-btn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></button>
          <input v-model="draft" class="ms-comp-input" placeholder="Type your message..." @keyup.enter="send" />
          <button type="button" class="ms-send" @click="send"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></button>
        </div>
      </section>
    </div>

    <!-- ════════ NEW MESSAGE MODAL ════════ -->
    <Modal v-model="showNew" title="New Message">
      <div class="ms-field">
        <label class="ms-label">To <span class="ms-req">*</span></label>
        <div class="ms-modal-search">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input v-model="recipientSearch" placeholder="Search by name..." />
        </div>
        <div class="ms-recipients">
          <button v-for="r in filteredRecipients" :key="r.name" type="button" class="ms-recipient" :class="{ active: newMsg.to === r.name }" @click="newMsg.to = r.name">
            <div class="ms-recipient-av">{{ r.initials }}</div>
            <div><div class="ms-recipient-name">{{ r.name }}</div><div class="ms-recipient-role">{{ r.role }}</div></div>
          </button>
        </div>
      </div>
      <div class="ms-field-row">
        <div class="ms-field"><label class="ms-label">Category</label><select v-model="newMsg.category" class="form-select"><option>General</option><option>Continuity Plan</option><option>Critical Incident</option><option>Scheduling</option></select></div>
        <div class="ms-field"><label class="ms-label">Priority</label><select v-model="newMsg.priority" class="form-select"><option>Normal</option><option>High</option><option>Urgent</option></select></div>
      </div>
      <div class="ms-field"><label class="ms-label">Message</label><textarea v-model="newMsg.body" class="ms-textarea" rows="4" placeholder="Type your message..."></textarea></div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showNew = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showNew = false"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Send</button>
      </template>
    </Modal>

    <!-- ════════ SET AVAILABILITY MODAL ════════ -->
    <Modal v-model="showAvail" title="Set Availability">
      <div class="ms-avail-list">
        <button v-for="opt in availOptions" :key="opt" type="button" class="ms-avail-opt" :class="{ active: availability === opt }" @click="availability = opt">
          <span class="ms-radio"></span> {{ opt }}
        </button>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showAvail = false">Close</button>
      </template>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
import Modal from '../../Components/Modal.vue';
defineProps({ user: Object });

const me = 'MC';
const search = ref('');
const filter = ref('all');
const filters = [
  { key: 'all', label: 'All', count: 2 },
  { key: 'continuity', label: 'Continuity', count: 2 },
  { key: 'network', label: 'Network', count: 0 },
];

const threads = [
  {
    id: 'sj', initials: 'SJ', name: 'Dr. Sarah Johnson', role: 'Trauma & EMDR, Family Systems', tag: 'Continuity Contact',
    org: 'Lotus Psychology Group', email: 'sarah.johnson@lotuspsychology.com', phone: '(415) 555-0124', based: 'San Francisco, CA',
    preview: "You: Sounds good. I've also upl...", time: 'Mar 2', unread: false,
    messages: [
      { type: 'divider', label: 'Sun, Feb 15' },
      { type: 'msg', dir: 'out', text: "Hi Sarah — confirmed receipt of the signed Continuity Plan. I've reviewed my task list and certified it. Happy to meet to walk through the protocol anytime.", time: '2:35 PM', read: true },
      { type: 'msg', dir: 'in', text: "Thanks Marcus. Appreciate the quick turnaround. Let's schedule a 30-min in Q2.", time: '3:02 PM' },
      { type: 'divider', label: 'Mon, Mar 2' },
      { type: 'msg', dir: 'out', text: 'Sounds good. I\'ve also uploaded the practitioner-facing protocol summary to your vault under "CS Handoff Reference." Take a look when you have a minute.', time: '9:14 AM', read: true },
    ],
  },
  {
    id: 'lj', initials: 'LJ', name: 'Linda Johnson', role: 'Support Steward', tag: 'Continuity Contact',
    org: 'Lotus Psychology Group', email: 'linda.johnson@gmail.com', phone: '(415) 555-0142', based: 'San Francisco, CA',
    preview: 'Thanks Marcus. Noted. Hope...', time: 'Feb 16', unread: true,
    messages: [
      { type: 'divider', label: 'Mon, Feb 16' },
      { type: 'msg', dir: 'out', text: "Hi Linda — just confirming I'm Sarah's primary CS. If you ever need to reach me, my contact details are in your contact list in the portal.", time: '11:20 AM', read: true },
      { type: 'msg', dir: 'in', text: 'Thanks Marcus. Noted. Hope all is well on your end.', time: '12:05 PM' },
    ],
  },
];
const activeThread = ref('sj');
const current = computed(() => threads.find(t => t.id === activeThread.value) || threads[0]);

const draft = ref('');
function send() { draft.value = ''; }

// ── conversation search + contact popover ──
const showConvSearch = ref(false);
const showContact = ref(false);
const convSearch = ref('');
function toggleConvSearch() { showConvSearch.value = !showConvSearch.value; showContact.value = false; }
function toggleContact() { showContact.value = !showContact.value; showConvSearch.value = false; }
const messageCount = computed(() => current.value.messages.filter(m => m.type === 'msg').length);
const convMatches = computed(() => {
  const q = convSearch.value.trim().toLowerCase();
  if (!q) return 0;
  return current.value.messages.filter(m => m.type === 'msg' && m.text.toLowerCase().includes(q)).length;
});
const convMatchLabel = computed(() => `${convMatches.value ? 1 : 0} of ${convMatches.value}`);
function isHit(m) { const q = convSearch.value.trim().toLowerCase(); return showConvSearch.value && q && m.text.toLowerCase().includes(q); }
watch(activeThread, () => { showContact.value = false; showConvSearch.value = false; convSearch.value = ''; });

// ── New Message modal ──
const showNew = ref(false);
const recipientSearch = ref('');
const newMsg = reactive({ to: '', category: 'General', priority: 'Normal', body: '' });
const recipients = [
  { initials: 'AO', name: 'Dr. Aisha Okonkwo', role: 'Continuity Steward' },
  { initials: 'AR', name: 'Dr. Amelia Rodriguez', role: 'Continuity Steward' },
  { initials: 'LR', name: 'Dr. Laura Reyes', role: 'Continuity Steward' },
  { initials: 'PR', name: 'Dr. Priya Raman', role: 'Continuity Steward' },
  { initials: 'SJ', name: 'Dr. Sarah Johnson', role: 'Practitioner' },
  { initials: 'LJ', name: 'Linda Johnson', role: 'Support Steward' },
];
const filteredRecipients = computed(() => {
  const q = recipientSearch.value.trim().toLowerCase();
  return q ? recipients.filter(r => r.name.toLowerCase().includes(q)) : recipients;
});

// ── Availability modal ──
const showAvail = ref(false);
const availability = ref('Available');
const availOptions = ['Available', 'Away', 'Busy — Do Not Disturb'];
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════
   CS — MESSAGES
   ════════════════════════════════════════════════════════════════ */
.ms-layout { display:grid; grid-template-columns:340px 1fr; gap:0; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-xl,18px); overflow:hidden; box-shadow:var(--shadow-sm); height:calc(100vh - 130px); min-height:540px; }

/* SIDEBAR */
.ms-sidebar { border-right:1px solid var(--border); display:flex; flex-direction:column; min-height:0; }
.ms-toolbar { display:flex; align-items:center; gap:8px; padding:14px 14px 10px; }
.ms-search { position:relative; flex:1; min-width:0; }
.ms-search svg { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.ms-search input { width:100%; padding:8px 12px 8px 34px; font-size:12.5px; color:var(--text); background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius-full); outline:none; transition:border-color .15s ease; }
.ms-search input:focus { border-color:var(--gold-dark); }
.ms-search input::placeholder { color:var(--text-4); }
.ms-tool-btn { width:32px; height:32px; flex-shrink:0; border:1px solid var(--border); border-radius:50%; background:var(--surface); color:var(--text-3); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s ease; }
.ms-tool-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }

.ms-pills { display:flex; align-items:center; gap:6px; padding:0 14px 12px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
.ms-pill { display:inline-flex; align-items:center; gap:6px; padding:5px 11px; font-size:11.5px; font-weight:600; color:var(--text-3); background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; }
.ms-pill:hover { color:var(--text); }
.ms-pill.active { background:var(--gold-dark); border-color:var(--gold-dark); color:var(--text-inverted); }
.ms-pill-count { font-size:10px; font-weight:700; }

.ms-threads { flex:1; overflow-y:auto; min-height:0; }
.ms-thread { width:100%; display:flex; align-items:center; gap:11px; padding:13px 16px; border:none; border-left:3px solid transparent; border-bottom:1px solid var(--border); background:none; cursor:pointer; text-align:left; transition:background .15s ease; }
.ms-thread:hover { background:var(--surface-2); }
.ms-thread.active { background:var(--surface-2); border-left-color:var(--gold-dark); }
.ms-thread-av { width:38px; height:38px; border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:13px; flex-shrink:0; }
.ms-thread-info { flex:1; min-width:0; }
.ms-thread-top { display:flex; align-items:center; justify-content:space-between; gap:8px; }
.ms-thread-name { font-size:13px; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ms-thread-time { font-size:10.5px; color:var(--text-4); flex-shrink:0; }
.ms-thread-preview { font-size:12px; color:var(--text-3); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
.ms-unread-dot { width:7px; height:7px; border-radius:50%; background:var(--gold-dark); flex-shrink:0; }

/* CONVERSATION */
.ms-conv { display:flex; flex-direction:column; min-height:0; position:relative; }
.ms-conv-head { display:flex; align-items:center; gap:12px; padding:14px 20px; border-bottom:1px solid var(--border); }
.ms-conv-btn.active { border-color:var(--gold-dark); color:var(--gold-dark); background:var(--badge-bg-gold); }

/* in-conversation search */
.ms-conv-search { display:flex; align-items:center; gap:8px; padding:10px 16px; border-bottom:1px solid var(--border); background:var(--surface-2); }
.ms-cs-input { position:relative; flex:1; min-width:0; }
.ms-cs-input svg { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.ms-cs-input input { width:100%; padding:8px 12px 8px 34px; font-size:12.5px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius-full); outline:none; }
.ms-cs-input input:focus { border-color:var(--gold-dark); }
.ms-cs-input input::placeholder { color:var(--text-4); }
.ms-cs-count { font-size:11.5px; color:var(--text-4); white-space:nowrap; }
.ms-cs-btn { width:30px; height:30px; flex-shrink:0; border:1px solid var(--border); border-radius:var(--radius); background:var(--surface); color:var(--text-3); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s ease; }
.ms-cs-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }

/* e2e badge */
.ms-e2e { align-self:center; display:inline-flex; align-items:center; gap:6px; font-size:10px; font-weight:600; color:var(--text-4); background:var(--surface-2); border:1px solid var(--border); padding:4px 12px; border-radius:var(--radius-full); margin-bottom:2px; }
.ms-e2e svg { color:var(--green-dark); }

/* contact popover */
.ms-contact-pop { position:absolute; top:60px; right:16px; width:300px; max-height:calc(100% - 76px); overflow-y:auto; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-lg); z-index:30; }
.ms-cp-top { padding:18px 18px 16px; border-bottom:1px solid var(--border); text-align:center; }
.ms-cp-name { font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); }
.ms-cp-role { font-size:12px; color:var(--text-3); margin-top:4px; line-height:1.4; }
.ms-cp-org { font-size:12px; color:var(--text-4); margin-top:4px; }
.ms-cp-actions { display:flex; gap:8px; justify-content:center; margin-top:14px; }
.ms-cp-section { padding:14px 18px; border-bottom:1px solid var(--border); }
.ms-cp-section:last-child { border-bottom:none; }
.ms-cp-label { font-size:9.5px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); margin-bottom:10px; }
.ms-cp-row { display:flex; align-items:center; gap:8px; font-size:12px; color:var(--text-2); padding:5px 0; }
.ms-cp-row svg { color:var(--text-4); flex-shrink:0; }
.ms-cp-k { color:var(--text-4); flex-shrink:0; }
.ms-cp-v { margin-left:auto; color:var(--text); font-weight:600; text-align:right; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:170px; }

.ms-bubble.hit .ms-bubble-text { box-shadow:0 0 0 1px var(--gold); }
.ms-conv-av { width:40px; height:40px; border-radius:var(--radius-sm); background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:14px; flex-shrink:0; }
.ms-conv-info { flex:1; min-width:0; }
.ms-conv-name { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.ms-conv-meta { font-size:12px; color:var(--text-3); margin-top:1px; }
.ms-conv-tag { color:var(--gold-dark); font-weight:600; }
.ms-conv-actions { display:flex; gap:6px; flex-shrink:0; }
.ms-conv-btn { width:32px; height:32px; border:1px solid var(--border); border-radius:50%; background:var(--surface); color:var(--text-3); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s ease; }
.ms-conv-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }

.ms-conv-body { flex:1; overflow-y:auto; min-height:0; padding:20px; display:flex; flex-direction:column; gap:14px; }
.ms-divider { text-align:center; }
.ms-divider span { display:inline-block; font-size:10px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; color:var(--text-4); background:var(--surface-2); border:1px solid var(--border); padding:3px 12px; border-radius:var(--radius-full); }

.ms-msg { display:flex; align-items:flex-end; gap:10px; max-width:74%; }
.ms-msg.out { align-self:flex-end; flex-direction:row; }
.ms-msg.in { align-self:flex-start; flex-direction:row-reverse; }
.ms-bubble { min-width:0; }
.ms-bubble-text { padding:11px 15px; font-size:13px; line-height:1.55; border-radius:14px; }
.ms-msg.out .ms-bubble-text { background:var(--gold-dark); color:var(--text-inverted); border-bottom-right-radius:4px; }
.ms-msg.in .ms-bubble-text { background:var(--surface-2); color:var(--text-2); border:1px solid var(--border); border-bottom-left-radius:4px; }
.ms-bubble-time { display:flex; align-items:center; gap:4px; font-size:10.5px; color:var(--text-4); margin-top:4px; }
.ms-msg.out .ms-bubble-time { justify-content:flex-end; }
.ms-msg.out .ms-bubble-time svg { color:var(--green-dark); }
.ms-msg-av { width:28px; height:28px; border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:10px; flex-shrink:0; }
.ms-msg.in .ms-msg-av { background:var(--surface-3); color:var(--text-3); }

.ms-composer { display:flex; align-items:center; gap:8px; padding:14px 18px; border-top:1px solid var(--border); }
.ms-comp-btn { width:34px; height:34px; flex-shrink:0; border:1px solid var(--border); border-radius:var(--radius); background:var(--surface); color:var(--text-3); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s ease; }
.ms-comp-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.ms-comp-input { flex:1; min-width:0; padding:10px 14px; font-size:13px; color:var(--text); background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius-full); outline:none; transition:border-color .15s ease; }
.ms-comp-input:focus { border-color:var(--gold-dark); }
.ms-comp-input::placeholder { color:var(--text-4); }
.ms-send { width:38px; height:38px; flex-shrink:0; border:none; border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:background .15s ease; }
.ms-send:hover { background:var(--gold-deep, #7d6429); }

/* MODALS */
.ms-field { margin-bottom:16px; }
.ms-field:last-child { margin-bottom:0; }
.ms-field-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px; }
.ms-label { display:block; font-size:12px; font-weight:600; color:var(--text-2); margin-bottom:7px; }
.ms-req { color:var(--red); }
.ms-modal-search { position:relative; margin-bottom:10px; }
.ms-modal-search svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.ms-modal-search input { width:100%; padding:10px 13px 10px 36px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; }
.ms-modal-search input:focus { border-color:var(--gold-dark); }
.ms-recipients { max-height:220px; overflow-y:auto; border:1px solid var(--border); border-radius:var(--radius); }
.ms-recipient { width:100%; display:flex; align-items:center; gap:12px; padding:11px 14px; border:none; border-bottom:1px solid var(--border); background:none; cursor:pointer; text-align:left; transition:background .15s ease; }
.ms-recipient:last-child { border-bottom:none; }
.ms-recipient:hover { background:var(--surface-2); }
.ms-recipient.active { background:var(--badge-bg-gold); }
.ms-recipient-av { width:36px; height:36px; border-radius:50%; background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:12px; flex-shrink:0; }
.ms-recipient-name { font-size:13.5px; font-weight:600; color:var(--text); }
.ms-recipient-role { font-size:11.5px; color:var(--text-4); margin-top:1px; }
.ms-textarea { width:100%; padding:11px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; resize:vertical; min-height:90px; line-height:1.55; font-family:var(--font-sans); }
.ms-textarea:focus { border-color:var(--gold-dark); }
.ms-textarea::placeholder { color:var(--text-4); }

.ms-avail-list { border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
.ms-avail-opt { width:100%; display:flex; align-items:center; gap:12px; padding:14px 16px; border:none; border-bottom:1px solid var(--border); background:none; cursor:pointer; text-align:left; font-size:13.5px; color:var(--text); transition:background .15s ease; }
.ms-avail-opt:last-child { border-bottom:none; }
.ms-avail-opt:hover { background:var(--surface-2); }
.ms-radio { width:16px; height:16px; border-radius:50%; border:1.5px solid var(--border-dark); flex-shrink:0; position:relative; transition:border-color .15s ease; }
.ms-avail-opt.active .ms-radio { border-color:var(--gold-dark); }
.ms-avail-opt.active .ms-radio::after { content:''; position:absolute; inset:3px; border-radius:50%; background:var(--gold-dark); }
.ms-avail-opt.active { background:var(--badge-bg-gold); }

@media (max-width:820px) {
  .ms-layout { grid-template-columns:1fr; height:auto; }
  .ms-sidebar { border-right:none; border-bottom:1px solid var(--border); max-height:340px; }
  .ms-conv { min-height:480px; }
  .ms-field-row { grid-template-columns:1fr; }
}
</style>
