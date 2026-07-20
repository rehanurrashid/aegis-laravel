<template>
  <AppLayout :user="user" portal="practitioner" activePage="messages" pageTitle="Messages">
    <Teleport to="body">
      <div class="dh-toast-stack">
        <div v-for="t in toasts" :key="t.id" class="dh-toast" :class="t.type">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span>{{ t.msg }}</span>
        </div>
      </div>
    </Teleport>
    <div class="msg-panel">

      <!-- ── LEFT SIDEBAR ── -->
      <div class="msg-sidebar">
        <div class="msg-sidebar-topbar">
          <div class="msg-search-wrap">
            <svg class="msg-search-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input class="msg-search-input" placeholder="Search messages..." v-model="searchQuery" />
          </div>
          <button class="msg-icon-btn msg-icon-btn-gold" title="New message">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          </button>
          <button class="msg-icon-btn" title="Settings">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          </button>
          <button class="msg-icon-btn" title="Sort">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="9" y2="18"/></svg>
          </button>
        </div>

        <div class="msg-filters-wrap">
          <div class="msg-filters">
            <button v-for="f in filters" :key="f.id" class="msg-filter" :class="{ active: activeFilter === f.id }" @click="activeFilter = f.id">
              <span>{{ f.label }}</span><span class="msg-filter-count">{{ f.count }}</span>
            </button>
          </div>
        </div>

        <div class="msg-threads">
          <div v-for="thread in filteredThreads" :key="thread.id" class="msg-thread" :class="{ active: selectedThread === thread.id }" @click="selectedThread = thread.id">
            <div class="msg-avatar" :class="thread.avatarLight ? 'msg-avatar-light' : ''">{{ thread.initials }}</div>
            <div class="msg-thread-info">
              <div class="msg-thread-top-row">
                <div class="msg-thread-name">{{ thread.name }}</div>
                <div class="msg-thread-right">
                  <span class="msg-thread-time">{{ thread.time }}</span>
                  <span v-if="thread.unread" class="msg-unread-dot"></span>
                </div>
              </div>
              <div class="msg-thread-preview">{{ thread.preview }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── RIGHT CONVERSATION AREA ── -->
      <div class="msg-conv">
        <div class="msg-conv-header">
          <div class="msg-avatar">MC</div>
          <div class="msg-conv-meta">
            <div class="msg-conv-name">Marcus Chen</div>
            <div class="msg-conv-role">Continuity Steward</div>
          </div>
          <span class="msg-conv-badge">Continuity Contact</span>
          <div class="msg-conv-actions">
            <button class="msg-icon-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button>
            <div class="msg-more-wrap" ref="moreWrapRef">
              <button class="msg-icon-btn" @click="moreMenuOpen = !moreMenuOpen">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
              </button>
              <Transition name="msg-menu-fade">
                <div v-if="moreMenuOpen" class="msg-more-menu" v-click-outside="() => moreMenuOpen = false">
                </div>
              </Transition>
            </div>
          </div>
        </div>

        <div class="msg-conv-body">
          <div class="msg-encrypt-notice">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            End-to-end encrypted
          </div>

          <div class="msg-date-divider"><span>SUN, FEB 15</span></div>

          <div class="msg-bubble-row msg-incoming">
            <div class="msg-avatar msg-avatar-sm">MC</div>
            <div class="msg-bubble">
              <div class="msg-bubble-text">Hi Sarah — confirmed receipt of the signed Continuity Plan. I've reviewed my task list and certified it. Happy to meet to walk through the protocol anytime.</div>
              <div class="msg-bubble-time">2:35 PM</div>
            </div>
          </div>

          <div class="msg-bubble-row msg-outgoing">
            <div class="msg-bubble">
              <div class="msg-bubble-text">Thanks Marcus. Appreciate the quick turnaround. Let's schedule a 30-min in Q2.</div>
              <div class="msg-bubble-time">3:02 PM</div>
            </div>
            <div class="msg-avatar msg-avatar-sm msg-avatar-out">SJ</div>
          </div>

          <div class="msg-date-divider"><span>MON, MAR 2</span></div>

          <div class="msg-bubble-row msg-incoming">
            <div class="msg-avatar msg-avatar-sm">MC</div>
            <div class="msg-bubble">
              <div class="msg-bubble-text">Sounds good. I've also uploaded the practitioner-facing protocol summary to your vault under 'CS Handoff Reference.' Take a look when you have a minute.</div>
              <div class="msg-bubble-time">9:14 AM</div>
            </div>
          </div>
        </div>

        <div class="msg-compose">
          <button class="msg-icon-btn"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></button>
          <button class="msg-icon-btn"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg></button>
          <input class="msg-compose-input" placeholder="Type your message..." v-model="newMessage" @keyup.enter="sendMessage" />
          <button class="msg-send-btn" @click="sendMessage"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></button>
        </div>

        <!-- Contact Info Panel (slides in from right on ··· click) -->
        <Transition name="msg-panel-slide">
          <div v-if="moreMenuOpen" class="msg-contact-panel" v-click-outside="() => moreMenuOpen = false">
            <div class="mcp-scroll">
              <!-- Header -->
              <div class="mcp-header">
                <div class="mcp-avatar">MC</div>
                <div class="mcp-name">Marcus Chen</div>
                <div class="mcp-role">Continuity Steward · <span class="mcp-role-gold">Continuity Contact</span></div>
                <div class="mcp-org">Chen Practice Solutions</div>
                <button class="btn btn-outline btn-sm mcp-profile-btn" @click="showToast('Opening profile','info')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
                  View Profile
                </button>
              </div>
              <div class="mcp-divider"></div>
              <div class="mcp-section-label">CONTACT</div>
              <div class="mcp-rows">
                <div class="mcp-row">
                  <span class="mcp-row-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                  <span class="mcp-row-label">Email</span>
                  <span class="mcp-row-val mcp-row-gold">marcus@chenpracticesolutions....</span>
                </div>
                <div class="mcp-row">
                  <span class="mcp-row-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.93 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.83 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
                  <span class="mcp-row-label">Phone</span>
                  <span class="mcp-row-val">(510) 555-0189</span>
                </div>
                <div class="mcp-row">
                  <span class="mcp-row-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
                  <span class="mcp-row-label">Based</span>
                  <span class="mcp-row-val">Oakland, CA</span>
                </div>
              </div>
              <div class="mcp-divider"></div>
              <div class="mcp-section-label">CONVERSATION</div>
              <div class="mcp-rows">
                <div class="mcp-row">
                  <span class="mcp-row-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                  <span class="mcp-row-label">Messages</span>
                  <span class="mcp-row-val">3</span>
                </div>
                <div class="mcp-row">
                  <span class="mcp-row-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                  <span class="mcp-row-label">Latest</span>
                  <span class="mcp-row-val">Mar 2 ago</span>
                </div>
              </div>
              <div class="mcp-divider"></div>
              <div class="mcp-section-label">MEDIA, FILES &amp; LINKS</div>
              <div class="mcp-media-tabs">
                <button class="mcp-media-tab active">Media <span class="mcp-tab-count">12</span></button>
                <button class="mcp-media-tab">Files <span class="mcp-tab-count mcp-tab-count-outline">8</span></button>
                <button class="mcp-media-tab">Links <span class="mcp-tab-count mcp-tab-count-outline">5</span></button>
              </div>
              <div class="mcp-media-grid">
                <div v-for="i in 6" :key="i" class="mcp-media-cell">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </div>
              </div>
              <button class="mcp-view-all-btn" @click="showToast('Opening media','info')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                View All Media
              </button>
              <div class="mcp-divider"></div>
              <div class="mcp-section-label">ACTIONS</div>
              <div class="mcp-actions">
                <button class="mcp-action-btn" @click="showToast('Thread muted','info')">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="1" y1="1" x2="23" y2="23"/><path d="M9 9v3a3 3 0 0 0 5.12 2.12M15 9.34V4a3 3 0 0 0-5.94-.6"/><path d="M17 16.95A7 7 0 0 1 5 12v-2m14 0v2a7 7 0 0 1-.11 1.23"/></svg>
                  Mute Notifications
                </button>
                <button class="mcp-action-btn" @click="showToast('Conversation archived','info')">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                  Archive
                </button>
                <button class="mcp-action-btn mcp-action-danger" @click="moreMenuOpen=false; showToast('Conversation deleted','info')">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                  Delete
                </button>
              </div>
            </div>
          </div>
        </Transition>

      </div><!-- end msg-conv -->

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';

defineProps({ user: Object });

const searchQuery    = ref('');
const newMessage     = ref('');
const selectedThread = ref('1');
const activeFilter   = ref('all');
const moreMenuOpen   = ref(false);
const moreWrapRef    = ref(null);

// Toast
const toasts = ref([]);
let toastId = 0;
function showToast(msg, type = 'info') {
  const id = ++toastId;
  toasts.value.push({ id, msg, type });
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id); }, 3000);
}

// Click-outside directive
const vClickOutside = {
  mounted(el, binding) {
    el._clickOutside = (e) => { if (!el.contains(e.target)) binding.value(e); };
    document.addEventListener('mousedown', el._clickOutside);
  },
  unmounted(el) { document.removeEventListener('mousedown', el._clickOutside); },
};

const filters = [
  { id: 'all',        label: 'All',        count: 8 },
  { id: 'continuity', label: 'Continuity', count: 4 },
  { id: 'business',   label: 'Business',   count: 2 },
  { id: 'network',    label: 'Network',    count: 2 },
];

const threads = [
  { id:'1', initials:'MC', name:'Marcus Chen',            preview:"Sounds good. I've also upload…",         time:'Mar 2',  unread:true,  avatarLight:false, category:'continuity' },
  { id:'2', initials:'LJ', name:'Linda Johnson',          preview:"Quick note — I'll be out March 2…",      time:'3w',     unread:true,  avatarLight:false, category:'continuity' },
  { id:'3', initials:'PR', name:'Dr. Priya Raman',        preview:"Yes, all clear. Reviewed the st…",       time:'Feb 18', unread:true,  avatarLight:false, category:'continuity' },
  { id:'4', initials:'JR', name:'James Rodriguez',        preview:"Sarah — confirming I've revie…",         time:'Feb 15', unread:true,  avatarLight:false, category:'continuity' },
  { id:'5', initials:'AP', name:'Acme Practice Services', preview:"You: Great numbers. Let's set up …",     time:'1w',     unread:false, avatarLight:true,  category:'business'   },
  { id:'6', initials:'JW', name:'Jamal Washington',       preview:"Dr. Johnson — attached your Q1 …",       time:'5d',     unread:true,  avatarLight:true,  category:'business'   },
  { id:'7', initials:'LC', name:'Dr. Lisa Chen',          preview:"You: I can take a look. Send me th…",    time:'1d',     unread:false, avatarLight:true,  category:'network'    },
  { id:'8', initials:'AP', name:'Dr. Aisha Patel',        preview:"Hi Sarah — wanted to thank you f…",      time:'1w',     unread:true,  avatarLight:true,  category:'network'    },
];

const filteredThreads = computed(() => {
  const cat = activeFilter.value;
  const q   = searchQuery.value.toLowerCase();
  return threads
    .filter(t => cat === 'all' || t.category === cat)
    .filter(t => !q || t.name.toLowerCase().includes(q) || t.preview.toLowerCase().includes(q));
});

function sendMessage() {
  if (!newMessage.value.trim()) return;
  newMessage.value = '';
}
</script>

<style scoped>
.msg-panel {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 14px;
  height: calc(100vh - 120px);
  min-height: 560px;
  max-height: 800px;
}

/* ── SIDEBAR ── */
.msg-sidebar {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg, 14px);
  background: var(--surface);
  height: 100%;
  overflow: hidden;
}

.msg-sidebar-topbar { display:flex; align-items:center; gap:6px; padding:12px 14px; border-bottom:1px solid var(--border); }

.msg-search-wrap { position:relative; flex:1; min-width:0; }
.msg-search-icon { position:absolute; left:9px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.msg-search-input { width:100%; padding:7px 10px 7px 28px; font-family:var(--font-sans); font-size:12.5px; color:var(--text); background:var(--surface-2); border:1px solid var(--border); border-radius:99px; outline:none; transition:border-color .15s,box-shadow .15s; box-sizing:border-box; }
.msg-search-input:focus { border-color:var(--gold-dark,#a0813e); box-shadow:0 0 0 3px rgba(160,129,62,.14); }
.msg-search-input::placeholder { color:var(--text-4); }

.msg-icon-btn { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:50%; border:1px solid var(--border); background:var(--surface); color:var(--text-3); cursor:pointer; flex-shrink:0; transition:background .15s,color .15s,border-color .15s; }
.msg-icon-btn:hover { background:var(--surface-2); color:var(--text); }
.msg-icon-btn-gold { background:var(--gold-dark,#a0813e); border-color:var(--gold-dark,#a0813e); color:#fff; }
.msg-icon-btn-gold:hover { background:var(--gold,#c4a96a); border-color:var(--gold,#c4a96a); }

.msg-filters-wrap { padding:10px 14px; border-bottom:1px solid var(--border); }
.msg-filters { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.msg-filter { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; font-family:var(--font-sans); font-size:12px; font-weight:600; color:var(--text-3); background:transparent; border:1px solid var(--border); border-radius:99px; cursor:pointer; white-space:nowrap; transition:background .15s,color .15s,border-color .15s; }
.msg-filter:hover { border-color:var(--gold-dark,#a0813e); color:var(--gold-dark,#a0813e); }
.msg-filter.active { background:var(--text,#1e1c1a); color:#fff; border-color:var(--text,#1e1c1a); }
.msg-filter.active:hover { background:var(--text-2,#3d3a36); border-color:var(--text-2,#3d3a36); }
.msg-filter-count { font-size:12px; font-weight:600; }

.msg-threads { flex:1; overflow-y:auto; min-height:0; }
.msg-thread { display:flex; align-items:center; gap:11px; padding:12px 14px; border-bottom:1px solid var(--border); cursor:pointer; transition:background .14s; border-left:3px solid transparent; }
.msg-thread:hover { background:var(--surface-2); }
.msg-thread.active { background:var(--surface-2); border-left-color:var(--gold-dark,#a0813e); }

.msg-avatar { width:38px; height:38px; border-radius:50%; background:var(--gold-dark,#a0813e); color:#fff; display:flex; align-items:center; justify-content:center; font-family:var(--font-serif,'Spectral',Georgia,serif); font-size:12px; font-weight:700; flex-shrink:0; letter-spacing:.3px; }
.msg-avatar-light { background:rgba(160,129,62,0.15); color:var(--gold-dark,#a0813e); }

.msg-thread-info { flex:1; min-width:0; }
.msg-thread-top-row { display:flex; align-items:center; justify-content:space-between; gap:6px; margin-bottom:2px; }
.msg-thread-name { font-size:13px; font-weight:700; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; }
.msg-thread-right { display:flex; align-items:center; gap:5px; flex-shrink:0; }
.msg-thread-time { font-size:11px; color:var(--text-4); white-space:nowrap; }
.msg-unread-dot { width:7px; height:7px; border-radius:50%; background:var(--gold-dark,#a0813e); flex-shrink:0; }
.msg-thread-preview { font-size:12px; color:var(--text-3); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

/* ── CONVERSATION ── */
.msg-conv {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg, 14px);
  background: var(--surface);
  position: relative;
}

.msg-conv-header { display:flex; align-items:center; gap:11px; padding:13px 18px; border-bottom:1px solid var(--border); background:var(--surface); }
.msg-conv-meta { flex:1; min-width:0; }
.msg-conv-name { font-size:14px; font-weight:700; color:var(--text); }
.msg-conv-role { font-size:12px; color:var(--text-3); margin-top:1px; }
.msg-conv-badge { display:inline-flex; align-items:center; padding:3px 10px; font-size:11px; font-weight:600; color:var(--gold-dark,#a0813e); border:1px solid var(--gold-dark,#a0813e); border-radius:99px; white-space:nowrap; flex-shrink:0; }
.msg-conv-actions { display:flex; align-items:center; gap:6px; flex-shrink:0; }

.msg-conv-body { flex:1; padding:22px 20px; overflow-y:auto; display:flex; flex-direction:column; gap:10px; min-height:0; }

.msg-encrypt-notice { display:flex; align-items:center; justify-content:center; gap:5px; font-size:11.5px; color:var(--text-4); margin-bottom:8px; }

.msg-date-divider { display:flex; align-items:center; justify-content:center; margin:6px 0; }
.msg-date-divider span { display:inline-block; padding:3px 12px; font-size:10.5px; font-weight:700; letter-spacing:.8px; color:var(--text-4); background:var(--surface-3,#f0ebe2); border-radius:99px; }

.msg-bubble-row { display:flex; align-items:flex-end; gap:9px; max-width:78%; }
.msg-bubble-row.msg-incoming { align-self:flex-start; }
.msg-bubble-row.msg-outgoing { align-self:flex-end; flex-direction:row-reverse; }

.msg-avatar-sm { width:30px; height:30px; font-size:10px; flex-shrink:0; align-self:flex-end; }
.msg-avatar-out { background:var(--surface-3,#f0ebe2); color:var(--text-2); }

.msg-bubble { display:flex; flex-direction:column; gap:3px; }
.msg-bubble-text { padding:10px 14px; border-radius:14px; font-size:13px; line-height:1.56; }
.msg-incoming .msg-bubble-text { background:var(--surface-2); color:var(--text); border-bottom-left-radius:4px; }
.msg-outgoing .msg-bubble-text { background:var(--gold-dark,#a0813e); color:#fff; border-bottom-right-radius:4px; }
.msg-bubble-time { font-size:10.5px; color:var(--text-4); }
.msg-outgoing .msg-bubble-time { text-align:right; }

.msg-compose { display:flex; align-items:center; gap:8px; padding:12px 16px; border-top:1px solid var(--border); background:var(--surface); }
.msg-compose-input { flex:1; padding:9px 13px; font-family:var(--font-sans); font-size:13px; color:var(--text); background:var(--surface-2); border:1px solid var(--border); border-radius:99px; outline:none; transition:border-color .15s,box-shadow .15s; }
.msg-compose-input:focus { border-color:var(--gold-dark,#a0813e); box-shadow:0 0 0 3px rgba(160,129,62,.14); }
.msg-compose-input::placeholder { color:var(--text-4); }
.msg-send-btn { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:50%; border:none; background:var(--gold-dark,#a0813e); color:#fff; cursor:pointer; flex-shrink:0; transition:background .15s; }
.msg-send-btn:hover { background:var(--gold,#c4a96a); }

/* More menu / Contact panel */
.msg-more-wrap { position:relative; }

/* Slide-in contact panel — positioned inside msg-conv, right edge */
.msg-contact-panel {
  position: absolute;
  top: 0; right: 0; bottom: 0;
  width: 300px;
  background: var(--surface);
  border-left: 1px solid var(--border);
  border-radius: 0 var(--radius-lg,14px) var(--radius-lg,14px) 0;
  z-index: 100;
  overflow: hidden;
  box-shadow: -4px 0 20px rgba(30,28,26,0.10);
}
.mcp-scroll { height:100%; overflow-y:auto; padding-bottom:20px; }

/* Header */
.mcp-header { display:flex; flex-direction:column; align-items:center; text-align:center; padding:28px 20px 18px; }
.mcp-avatar { width:56px; height:56px; border-radius:50%; background:var(--gold-dark,#a0813e); color:#fff; font-family:var(--font-serif); font-size:18px; font-weight:700; display:flex; align-items:center; justify-content:center; margin-bottom:12px; }
.mcp-name { font-family:var(--font-serif); font-size:18px; font-weight:700; color:var(--text); margin-bottom:4px; }
.mcp-role { font-size:12.5px; color:var(--text-3); margin-bottom:3px; }
.mcp-role-gold { color:var(--gold-dark,#a0813e); font-weight:600; }
.mcp-org { font-size:12px; color:var(--text-4); margin-bottom:14px; }
.mcp-profile-btn { display:inline-flex; align-items:center; gap:6px; }

/* Divider */
.mcp-divider { height:1px; background:var(--border); margin:4px 0; }

/* Section label */
.mcp-section-label { font-family:var(--font-sans); font-size:10px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-4); padding:12px 18px 6px; }

/* Info rows */
.mcp-rows { padding:0 18px; }
.mcp-row { display:flex; align-items:center; gap:10px; padding:8px 0; font-family:var(--font-sans); font-size:13px; }
.mcp-row-icon { color:var(--text-4); display:flex; align-items:center; flex-shrink:0; width:18px; }
.mcp-row-label { color:var(--text-3); font-weight:500; min-width:58px; flex-shrink:0; }
.mcp-row-val { color:var(--text); font-weight:500; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.mcp-row-gold { color:var(--gold-dark,#a0813e); }

/* Media tabs */
.mcp-media-tabs { display:flex; gap:6px; padding:8px 18px 10px; flex-wrap:wrap; }
.mcp-media-tab { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; font-family:var(--font-sans); font-size:12px; font-weight:600; border:1px solid var(--border); border-radius:99px; background:transparent; color:var(--text-3); cursor:pointer; transition:all .15s; }
.mcp-media-tab.active { background:var(--gold-dark,#a0813e); color:#fff; border-color:var(--gold-dark,#a0813e); }
.mcp-tab-count { font-size:11px; font-weight:700; background:rgba(255,255,255,0.3); border-radius:99px; padding:1px 6px; }
.mcp-tab-count-outline { background:var(--surface-3); color:var(--text-3); }

/* Media grid */
.mcp-media-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:4px; padding:0 18px 12px; }
.mcp-media-cell { aspect-ratio:1; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--text-4); }

/* View all button */
.mcp-view-all-btn { display:flex; align-items:center; justify-content:center; gap:7px; width:calc(100% - 36px); margin:0 18px 4px; padding:10px; font-family:var(--font-sans); font-size:12.5px; font-weight:600; color:var(--text-2); background:var(--surface); border:1px solid var(--border); border-radius:8px; cursor:pointer; transition:all .15s; }
.mcp-view-all-btn:hover { border-color:var(--gold-dark,#a0813e); color:var(--gold-dark,#a0813e); }

/* Actions */
.mcp-actions { display:flex; flex-direction:column; padding:4px 18px; gap:2px; }
.mcp-action-btn { display:flex; align-items:center; gap:10px; padding:9px 10px; font-family:var(--font-sans); font-size:13px; font-weight:500; color:var(--text-2); background:transparent; border:none; border-radius:8px; cursor:pointer; transition:background .12s; }
.mcp-action-btn:hover { background:var(--surface-2); }
.mcp-action-danger { color:var(--red); }
.mcp-action-danger:hover { background:var(--red-light); }

/* Panel slide transition */
.msg-panel-slide-enter-active, .msg-panel-slide-leave-active { transition: transform 0.22s ease, opacity 0.22s ease; }
.msg-panel-slide-enter-from, .msg-panel-slide-leave-to { transform: translateX(100%); opacity: 0; }

@media(max-width:860px) {
  .msg-panel { grid-template-columns:1fr; height:auto; }
  .msg-sidebar { max-height:300px; }
}
</style>
