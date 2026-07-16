<template>
  <AppLayout :user="user" portal="practitioner" activePage="vault" pageTitle="Document Vault">
    <!-- HERO -->
    <div class="hero-banner is-quiet">
      <div class="page-hero-inner">
        <div class="page-hero-text">
          <div class="page-hero-eyebrow">Secure Document Storage</div>
          <h1 class="page-hero-title">Document Vault</h1>
          <p class="page-hero-sub">Encrypted storage for your most important documents — released to your stewards only during a critical moment.</p>
        </div>
        <div class="page-hero-actions">
          <button class="btn btn-primary btn-sm" @click="uploadOpen = true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Upload Document
          </button>
        </div>
      </div>
    </div>

    <!-- STAT CHIPS -->
    <div class="stat-chips-row">
      <div v-for="s in stats" :key="s.label" class="stat-chip">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)" v-html="s.icon"></div>
        <div><div class="stat-chip-value">{{ s.value }}</div><div class="stat-chip-label">{{ s.label }}</div></div>
      </div>
    </div>

    <!-- ACCESS DURING CRITICAL MOMENT -->
    <div class="card vault-access">
      <div class="vault-access-head">
        <div class="vault-access-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
        <div>
          <div class="vault-access-title">Critical-Moment Access</div>
          <div class="vault-access-sub">People granted access during a critical moment</div>
        </div>
      </div>
      <div class="vault-access-people">
        <span v-for="p in accessPeople" :key="p.name" class="vault-person"><span class="vault-person-avatar">{{ p.initials }}</span>{{ p.name }} <small>· {{ p.role }}</small></span>
      </div>
    </div>

    <!-- CATEGORY FOLDERS -->
    <div class="dh-sh">
      <div class="dh-sh-l"><div class="dh-sh-eyebrow">Organized</div><div class="dh-sh-title">Categories</div></div>
    </div>
    <div class="vault-folders">
      <button v-for="c in categories" :key="c.name" class="vault-folder" :class="{ active: activeCat === c.name }" @click="activeCat = activeCat === c.name ? null : c.name">
        <div class="vault-folder-icon" v-html="c.icon"></div>
        <div class="vault-folder-name">{{ c.name }}</div>
        <div class="vault-folder-count">{{ c.count }} items</div>
      </button>
    </div>

    <!-- DOCUMENTS -->
    <div class="dh-sh">
      <div class="dh-sh-l"><div class="dh-sh-eyebrow">{{ activeCat || 'All' }}</div><div class="dh-sh-title">Documents</div></div>
      <div class="vault-search">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input class="vault-search-input" v-model="q" placeholder="Search documents…">
      </div>
    </div>
    <div class="card vault-table-wrap">
      <table class="vault-table">
        <thead>
          <tr><th>Name</th><th>Category</th><th>Updated</th><th>Sensitivity</th><th class="vt-right">Actions</th></tr>
        </thead>
        <tbody>
          <tr v-for="d in filteredDocs" :key="d.name">
            <td>
              <span class="vault-doc-name">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                {{ d.name }}
              </span>
            </td>
            <td><span class="vault-cat-chip">{{ d.category }}</span></td>
            <td class="vt-muted">{{ d.updated }}</td>
            <td><span class="badge" :class="d.sensitive ? 'badge-red' : 'badge-gray'">{{ d.sensitive ? 'Sensitive' : 'Standard' }}</span></td>
            <td class="vt-right">
              <button class="btn-icon-sm" title="Download" @click="showToast('Downloading ' + d.name,'info')"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></button>
              <button class="btn-icon-sm btn-icon-danger" title="Delete" @click="showToast('Document removed','info')"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
            </td>
          </tr>
          <tr v-if="!filteredDocs.length"><td colspan="5" class="vault-empty">No documents match your search.</td></tr>
        </tbody>
      </table>
    </div>

    <!-- UPLOAD MODAL -->
    <Modal :model-value="uploadOpen" @update:model-value="uploadOpen = false" title="Upload Document">
      <div class="form-group">
        <label class="form-label">Category <span style="color:var(--red)">*</span></label>
        <select class="form-select" v-model="uploadCat"><option v-for="c in categories" :key="c.name">{{ c.name }}</option></select>
      </div>
      <div class="form-group">
        <label class="form-label">Document</label>
        <label class="vault-dropzone">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <span>{{ uploadName || 'Click to upload · PDF, JPG, PNG · up to 10MB' }}</span>
          <input type="file" accept=".pdf,.jpg,.jpeg,.png" style="display:none" @change="uploadName = $event.target.files[0] && $event.target.files[0].name">
        </label>
      </div>
      <label class="vault-chk-row"><input type="checkbox" class="styled-chk" v-model="uploadSensitive"><span>Mark as sensitive — extra confirmation required to access</span></label>
      <template #footer>
        <button class="btn btn-outline" @click="uploadOpen = false">Cancel</button>
        <button class="btn btn-primary" @click="showToast('Document uploaded to vault','success'); uploadOpen = false">Upload</button>
      </template>
    </Modal>

    <Teleport to="body">
      <div class="dh-toast-stack">
        <div v-for="t in toasts" :key="t.id" class="dh-toast" :class="t.type">
          <svg v-if="t.type === 'success'" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          <span>{{ t.msg }}</span>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
import Modal from '../../Components/Modal.vue';

defineProps({ user: { type: Object, default: () => ({}) } });

const icon = (p) => `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${p}</svg>`;
const stats = [
  { value: '17', label: 'Total Documents', icon: icon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>') },
  { value: '3', label: 'Sensitive Information', icon: icon('<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>') },
  { value: '2', label: 'Action Needed', icon: icon('<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>') },
];

const accessPeople = [
  { initials: 'LR', name: 'Dr. Laura Reyes', role: 'Continuity Steward' },
  { initials: 'LJ', name: 'Linda Johnson', role: 'Support Steward' },
];

const folderIcon = icon('<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>');
const categories = [
  { name: 'Agreements & Contracts', count: 7, icon: folderIcon },
  { name: 'Clinical Documents', count: 3, icon: folderIcon },
  { name: 'Financial Documents', count: 3, icon: folderIcon },
  { name: 'Insurance Policies', count: 4, icon: folderIcon },
];
const activeCat = ref(null);

const documents = [
  { name: 'Continuity Plan Agreement.pdf', category: 'Agreements & Contracts', updated: 'Jun 15, 2024', sensitive: true },
  { name: 'Steward Authorization Form.pdf', category: 'Agreements & Contracts', updated: 'Jun 15, 2024', sensitive: false },
  { name: 'Practice Lease.pdf', category: 'Agreements & Contracts', updated: 'Jan 3, 2025', sensitive: false },
  { name: 'Client Roster (Encrypted).xlsx', category: 'Clinical Documents', updated: 'Jun 1, 2026', sensitive: true },
  { name: 'Treatment Protocols.pdf', category: 'Clinical Documents', updated: 'Mar 12, 2025', sensitive: false },
  { name: 'Bank & Payroll Details.pdf', category: 'Financial Documents', updated: 'May 20, 2026', sensitive: true },
  { name: 'Tax Returns 2025.pdf', category: 'Financial Documents', updated: 'Apr 2, 2026', sensitive: false },
  { name: 'Professional Liability Policy.pdf', category: 'Insurance Policies', updated: 'Mar 15, 2024', sensitive: false },
  { name: 'General Business Insurance.pdf', category: 'Insurance Policies', updated: 'Feb 28, 2024', sensitive: false },
];
const q = ref('');
const filteredDocs = computed(() => documents.filter((d) =>
  (!activeCat.value || d.category === activeCat.value) &&
  (!q.value.trim() || d.name.toLowerCase().includes(q.value.trim().toLowerCase()))
));

const uploadOpen = ref(false);
const uploadCat = ref('Agreements & Contracts');
const uploadName = ref('');
const uploadSensitive = ref(false);

const toasts = ref([]);
let tid = 0;
function showToast(msg, type = 'info') {
  const id = ++tid;
  toasts.value.push({ id, msg, type });
  setTimeout(() => { toasts.value = toasts.value.filter((t) => t.id !== id); }, 3000);
}
</script>

<style scoped>
.dh-sh { display: flex; align-items: flex-end; justify-content: space-between; margin: 28px 0 14px; gap: 16px; flex-wrap: wrap; }
.dh-sh-l { display: flex; flex-direction: column; gap: 3px; }
.dh-sh-eyebrow { font-size: 10px; font-weight: 600; letter-spacing: 1.4px; text-transform: uppercase; color: var(--gold-dark); }
.dh-sh-title { font-family: var(--font-serif); font-size: 20px; font-weight: 600; color: var(--text); }

.vault-access { padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; border-left: 3px solid var(--gold); }
.vault-access-head { display: flex; align-items: center; gap: 12px; }
.vault-access-icon { width: 38px; height: 38px; border-radius: var(--radius-sm); background: var(--icon-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.vault-access-title { font-family: var(--font-serif); font-size: 15px; font-weight: 600; color: var(--text); }
.vault-access-sub { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.vault-access-people { display: flex; flex-wrap: wrap; gap: 10px; }
.vault-person { display: inline-flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 600; color: var(--text-2); background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-full); padding: 5px 12px 5px 5px; }
.vault-person small { color: var(--text-4); font-weight: 400; }
.vault-person-avatar { width: 24px; height: 24px; border-radius: 50%; background: var(--gold-dark); color: #fff; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; }

.vault-folders { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
.vault-folder { text-align: left; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 18px; cursor: pointer; box-shadow: var(--shadow-xs); transition: all var(--transition); }
.vault-folder:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); border-color: var(--surface-4); }
.vault-folder.active { border-color: var(--gold); background: rgba(196,169,106,0.04); box-shadow: 0 0 0 2px rgba(160,129,62,0.18); }
.vault-folder-icon { width: 40px; height: 40px; border-radius: var(--radius-sm); background: var(--icon-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
.vault-folder-name { font-size: 13.5px; font-weight: 700; color: var(--text); line-height: 1.3; }
.vault-folder-count { font-size: 12px; color: var(--text-3); margin-top: 3px; }

.vault-search { position: relative; display: flex; align-items: center; }
.vault-search svg { position: absolute; left: 11px; color: var(--text-4); }
.vault-search-input { padding: 8px 12px 8px 32px; font-size: 13px; border: 1px solid var(--border); border-radius: var(--radius-full); background: var(--surface); outline: none; width: 240px; max-width: 100%; transition: border-color var(--transition); }
.vault-search-input:focus { border-color: var(--gold); box-shadow: var(--focus-ring); }

.vault-table-wrap { overflow-x: auto; }
.vault-table { width: 100%; border-collapse: collapse; }
.vault-table th { text-align: left; font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--text-4); padding: 14px 18px; background: var(--surface-2); border-bottom: 1px solid var(--border); white-space: nowrap; }
.vault-table td { font-size: 13px; color: var(--text-2); padding: 13px 18px; border-bottom: 1px solid var(--border); }
.vault-table tr:last-child td { border-bottom: none; }
.vault-table tbody tr:hover td { background: var(--surface-2); }
.vt-right { text-align: right; white-space: nowrap; }
.vt-muted { color: var(--text-3); }
.vault-doc-name { display: inline-flex; align-items: center; gap: 9px; font-weight: 600; color: var(--text); }
.vault-doc-name svg { color: var(--gold-dark); flex-shrink: 0; }
.vault-cat-chip { font-size: 11px; font-weight: 600; color: var(--text-3); background: var(--surface-3); border-radius: var(--radius-full); padding: 3px 9px; white-space: nowrap; }
.vault-empty { text-align: center; color: var(--text-4); padding: 28px; }
.btn-icon-sm + .btn-icon-sm { margin-left: 4px; }

.vault-dropzone { display: flex; align-items: center; justify-content: center; gap: 10px; border: 1px dashed var(--border-dark); border-radius: var(--radius-sm); padding: 18px; font-size: 12.5px; color: var(--text-3); cursor: pointer; transition: all var(--transition); }
.vault-dropzone:hover { border-color: var(--gold); background: var(--surface-2); color: var(--gold-dark); }
.vault-chk-row { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--text-2); cursor: pointer; }
.styled-chk { width: 17px; height: 17px; accent-color: var(--gold-dark); cursor: pointer; flex-shrink: 0; }
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: 10.5px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: var(--text-2); margin-bottom: 6px; }

.dh-toast-stack { position: fixed; bottom: 22px; right: 22px; z-index: 4000; display: flex; flex-direction: column; gap: 10px; }
.dh-toast { display: flex; align-items: center; gap: 9px; padding: 11px 16px; border-radius: var(--radius); background: var(--text); color: var(--text-inverted); font-size: 13px; font-weight: 600; box-shadow: var(--shadow-lg); max-width: 360px; }
.dh-toast.success { background: var(--green-dark); }
.dh-toast svg { flex-shrink: 0; }

@media (max-width: 980px) { .vault-folders { grid-template-columns: repeat(2, minmax(0,1fr)); } }
@media (max-width: 560px) { .vault-folders { grid-template-columns: 1fr; } }
</style>
