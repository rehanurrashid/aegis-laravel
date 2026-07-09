<template>
  <!-- ── Export Data Modal ───────────────────────────────────────────────────── -->
  <AegisModal v-model="modals.export" title="Export Your Data" size="md">
    <p style="font-size:14px;color:var(--text-2);margin-bottom:16px">
      A HIPAA-compliant export will be prepared and emailed to you within 24 hours.
      Select what to include and your preferred format.
    </p>
    <div class="form-group" style="margin-bottom:14px">
      <label class="form-label">Include in Export</label>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px">
        <label v-for="opt in exportOptions" :key="opt.key"
          style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);background:var(--surface)"
          :style="opt.checked ? 'border-color:var(--gold-dark);background:var(--icon-bg-gold)' : ''"
        >
          <input type="checkbox" v-model="opt.checked" style="accent-color:var(--gold-dark)" />
          <span>{{ opt.label }}</span>
        </label>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Format</label>
      <div style="display:flex;gap:8px;margin-top:8px">
        <label v-for="fmt in ['json', 'csv']" :key="fmt"
          style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:7px 14px;border:1px solid var(--border);border-radius:var(--radius);background:var(--surface)"
          :style="exportFormat === fmt ? 'border-color:var(--gold-dark);background:var(--icon-bg-gold);font-weight:600' : ''"
        >
          <input type="radio" :value="fmt" v-model="exportFormat" style="accent-color:var(--gold-dark)" />
          {{ fmt.toUpperCase() }}
        </label>
      </div>
    </div>
    <template #footer>
      <button type="button" class="btn btn-ghost btn-sm" @click="modals.export = false">Cancel</button>
      <button type="button" class="btn btn-primary btn-sm" :disabled="!hasExportSelection || exportForm.processing" @click="submitExport">
        <AegisIcon name="download" :size="14" /> Request Export
      </button>
    </template>
  </AegisModal>

  <!-- ── Pause Account Modal ─────────────────────────────────────────────────── -->
  <AegisModal v-model="modals.pause" title="Pause Account" size="md">
    <div class="alert alert-gold" style="margin-bottom:16px">
      <div class="alert-icon"><AegisIcon name="info" :size="16" /></div>
      <div class="alert-content" style="font-size:13px">While paused, you won't appear in search results or receive new referrals. Existing connections and data are preserved.</div>
    </div>
    <div class="form-row form-row-2">
      <div class="form-group">
        <label class="form-label">Pause Until <span style="font-weight:400;color:var(--text-3)">(optional)</span></label>
        <input class="form-input" type="date" v-model="pauseForm.until" :min="minDate" />
      </div>
      <div class="form-group">
        <label class="form-label">Reason</label>
        <select class="form-select" v-model="pauseForm.reason">
          <option value="leave">Medical Leave</option>
          <option value="vacation">Vacation</option>
          <option value="parental">Parental Leave</option>
          <option value="sabbatical">Sabbatical</option>
          <option value="other">Other</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Away Message <span style="font-weight:400;color:var(--text-3)">(shown to your network)</span></label>
      <textarea class="form-textarea" rows="3" v-model="pauseForm.message" placeholder="Let your network know why you're temporarily away…"></textarea>
    </div>
    <template #footer>
      <button type="button" class="btn btn-ghost btn-sm" @click="modals.pause = false">Cancel</button>
      <button type="button" class="btn btn-primary btn-sm" :disabled="pauseIForm.processing" @click="submitPause">
        <AegisIcon name="activity" :size="13" /> Pause Account
      </button>
    </template>
  </AegisModal>

  <!-- ── Delete Account Modal ────────────────────────────────────────────────── -->
  <AegisModal v-model="modals.delete" title="Delete Account Permanently" size="md">
    <div style="background:var(--red-light);border:1px solid var(--red);border-radius:var(--radius);padding:14px;margin-bottom:16px">
      <div style="display:flex;align-items:flex-start;gap:10px">
        <AegisIcon name="alert-triangle" :size="18" style="color:var(--red);flex-shrink:0;margin-top:1px" />
        <div>
          <div style="font-size:13px;font-weight:700;color:var(--red);margin-bottom:4px">This action cannot be undone</div>
          <div style="font-size:12px;color:var(--red-dark);line-height:1.5">All your data — profile, referrals, documents, network connections, and billing history — will be permanently erased after 30 days. You have until then to contact support to cancel.</div>
        </div>
      </div>
    </div>
    <div class="form-group" style="margin-bottom:14px">
      <label class="form-label">Type <strong style="font-family:monospace">DELETE MY ACCOUNT</strong> to confirm</label>
      <input class="form-input" v-model="deleteConfirm" placeholder="DELETE MY ACCOUNT" style="font-family:monospace;letter-spacing:0.5px" />
    </div>
    <template #footer>
      <button type="button" class="btn btn-ghost btn-sm" @click="modals.delete = false">Cancel</button>
      <button type="button" class="btn btn-danger btn-sm"
        :disabled="deleteConfirm !== 'DELETE MY ACCOUNT' || deleteIForm.processing"
        @click="submitDelete">
        <AegisIcon name="trash" :size="14" /> Permanently Delete
      </button>
    </template>
  </AegisModal>

  <!-- ── Main Card ───────────────────────────────────────────────────────────── -->
  <div class="card">
    <div class="card-header">
      <div class="card-title-group">
        <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--red-light);color:var(--red)"><AegisIcon name="alert-triangle" :size="16" /></div>
        <div>
          <div class="card-title" style="color:var(--red)">Account Closure &amp; Data Management</div>
          <div class="card-subtitle">Irreversible operations — proceed with caution</div>
        </div>
      </div>
    </div>
    <div class="card-body" style="padding:0">

      <!-- Export Data -->
      <div class="dz-action">
        <div class="dz-action-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="download" :size="18" /></div>
        <div class="dz-action-body">
          <div class="dz-action-title">Export All Data</div>
          <div class="dz-action-desc">Download a HIPAA-compliant copy of your Aegis profile, referrals, documents, and more</div>
        </div>
        <button type="button" class="btn btn-outline btn-sm" @click="modals.export = true">
          <AegisIcon name="download" :size="14" /> Export Data
        </button>
      </div>

      <!-- Pause / Resume -->
      <div class="dz-action">
        <div class="dz-action-icon" :style="isPaused ? 'background:var(--orange-light);color:var(--orange-dark)' : 'background:var(--surface-secondary);color:var(--text-secondary)'">
          <AegisIcon name="activity" :size="18" />
        </div>
        <div class="dz-action-body">
          <div class="dz-action-title">
            Pause Account
            <span v-if="isPaused" class="badge badge-orange" style="margin-left:8px;font-size:10px">Paused</span>
          </div>
          <div class="dz-action-desc" v-if="!isPaused">Temporarily suspend your account. You won't appear in searches or receive referrals.</div>
          <div class="dz-action-desc" v-else style="color:var(--orange-dark)">Your account is currently paused. Reactivate to appear in search results and receive referrals.</div>
        </div>
        <button v-if="!isPaused" type="button" class="btn btn-outline btn-sm" @click="modals.pause = true">
          <AegisIcon name="activity" :size="13" /> Pause
        </button>
        <button v-else type="button" class="btn btn-primary btn-sm" :disabled="resumeForm.processing" @click="submitResume">
          <AegisIcon name="check" :size="13" /> Reactivate
        </button>
      </div>

      <!-- Delete Account -->
      <div class="dz-action dz-action-danger">
        <div class="dz-action-icon" style="background:var(--red-light);color:var(--red)"><AegisIcon name="trash" :size="18" /></div>
        <div class="dz-action-body">
          <div class="dz-action-title" style="color:var(--red)">Delete Account Permanently</div>
          <div class="dz-action-desc">Permanently delete your Aegis account. All data will be erased after a 30-day grace period.</div>
        </div>
        <button type="button" class="btn btn-danger btn-sm" @click="modals.delete = true">
          <AegisIcon name="trash" :size="14" /> Delete Account
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  deleteRoute: { type: String, default: 'provider.settings.account.delete' },
  pauseRoute:  { type: String, default: 'provider.settings.account.pause'  },
  resumeRoute: { type: String, default: 'provider.settings.account.resume' },
  exportRoute: { type: String, default: 'provider.settings.account.export' },
  isPaused:    { type: Boolean, default: false },
});

const toast  = useToast();
const modals = reactive({ export: false, pause: false, delete: false });

// ── Export ───────────────────────────────────────────────────────────────────
const exportOptions = reactive([
  { key: 'profile',    label: 'Profile & Identity',  checked: true  },
  { key: 'referrals',  label: 'Referral History',    checked: true  },
  { key: 'documents',  label: 'Document Vault',      checked: true  },
  { key: 'agreements', label: 'Agreements',          checked: true  },
  { key: 'network',    label: 'Network Connections', checked: false },
  { key: 'activity',   label: 'Activity Log',        checked: false },
  { key: 'messages',   label: 'Messages',            checked: false },
  { key: 'billing',    label: 'Billing History',     checked: false },
]);
const exportFormat    = ref('json');
const hasExportSelection = computed(() => exportOptions.some(o => o.checked));
const exportForm      = useForm({});

function submitExport() {
  exportForm.post(route(props.exportRoute), {
    data: {
      include: exportOptions.filter(o => o.checked).map(o => o.key),
      format:  exportFormat.value,
    },
    preserveScroll: true,
    onSuccess: () => { modals.export = false; toast.success('Export request submitted. Check your email within 24 hours.'); },
    onError:   () => toast.error('Could not submit export request.'),
  });
}

// ── Pause ────────────────────────────────────────────────────────────────────
const minDate    = new Date(Date.now() + 86400000).toISOString().split('T')[0];
const pauseForm  = reactive({ until: '', reason: 'leave', message: '' });
const pauseIForm = useForm({});

function submitPause() {
  pauseIForm.post(route(props.pauseRoute), {
    data: pauseForm,
    preserveScroll: true,
    onSuccess: () => { modals.pause = false; toast.success('Account paused. You can reactivate at any time.'); },
    onError:   () => toast.error('Could not pause account.'),
  });
}

// ── Resume ───────────────────────────────────────────────────────────────────
const resumeForm = useForm({});

function submitResume() {
  resumeForm.post(route(props.resumeRoute), {
    preserveScroll: true,
    onSuccess: () => toast.success('Account reactivated. You are now visible in search results.'),
    onError:   () => toast.error('Could not reactivate account.'),
  });
}

// ── Delete ───────────────────────────────────────────────────────────────────
const deleteConfirm = ref('');
const deleteIForm   = useForm({ confirm: '' });

function submitDelete() {
  if (deleteConfirm.value !== 'DELETE MY ACCOUNT') return;
  deleteIForm.confirm = 'DELETE MY ACCOUNT';
  deleteIForm.delete(route(props.deleteRoute), {
    preserveScroll: true,
    onSuccess: () => { modals.delete = false; },
    onError:   () => toast.error('Could not delete account. Please try again.'),
  });
}
</script>

<style scoped>
.dz-action {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 24px;
  border-bottom: 1px solid var(--border);
  transition: background 0.15s;
}
.dz-action:last-child { border-bottom: none; }
.dz-action:hover { background: var(--surface-secondary); }
.dz-action-danger:hover { background: var(--red-light); }

.dz-action-icon {
  width: 40px;
  height: 40px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.dz-action-body { flex: 1; min-width: 0; }
.dz-action-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
  display: flex;
  align-items: center;
  margin-bottom: 3px;
}
.dz-action-desc {
  font-size: 12px;
  color: var(--text-3);
  line-height: 1.4;
}
</style>
