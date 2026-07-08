<template>
  <div class="card">
    <div class="card-header">
      <div class="card-title-group">
        <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--red);color:var(--text-inverted)"><AegisIcon name="alert-triangle" :size="16" /></div>
        <div><div class="card-title" style="color:var(--red)">{{ title }}</div><div class="card-subtitle">Irreversible operations — proceed with caution</div></div>
      </div>
    </div>
    <div class="card-body">
      <div class="danger-zone">
        <div class="danger-zone-title"><AegisIcon name="alert-triangle" :size="16" /> {{ title }}</div>

        <div class="danger-action">
          <div class="danger-action-info"><div class="danger-action-label">Export All Data</div><div class="danger-action-desc">Download a complete copy of your Aegis data</div></div>
          <button type="button" class="btn btn-outline btn-sm" @click="toast.info('Export request submitted. Check your email in 24 hours.')"><AegisIcon name="download" :size="14" /> Export</button>
        </div>

        <div class="danger-action">
          <div class="danger-action-info"><div class="danger-action-label">{{ pauseLabel }}</div><div class="danger-action-desc">{{ pauseDesc }}</div></div>
          <button type="button" class="btn btn-outline btn-sm" @click="modals.pause = true"><AegisIcon name="activity" :size="13" /> Pause</button>
        </div>

        <div v-if="showTransfer" class="danger-action">
          <div class="danger-action-info"><div class="danger-action-label">Transfer Account</div><div class="danger-action-desc">Transfer ownership to another Aegis user</div></div>
          <button type="button" class="btn btn-outline btn-sm" @click="modals.transfer = true"><AegisIcon name="arrow-right" :size="14" /> Transfer</button>
        </div>

        <div class="danger-action">
          <div class="danger-action-info"><div class="danger-action-label" style="color:var(--red)">{{ deactivateLabel }}</div><div class="danger-action-desc">{{ deactivateDesc }}</div></div>
          <button type="button" class="btn btn-danger btn-sm" @click="modals.deactivate = true"><AegisIcon name="trash" :size="14" /> {{ deactivateButtonLabel }}</button>
        </div>
      </div>
    </div>

    <!-- Pause Modal -->
    <AegisModal v-model="modals.pause" :title="pauseLabel" size="md">
      <p style="font-size:14px;color:var(--text-2);margin-bottom:14px">{{ pauseDesc }} You can reactivate at any time.</p>
      <div class="form-row form-row-2">
        <div class="form-group"><label class="form-label">Pause Until</label><input class="form-input" type="date" v-model="pauseForm.until" /></div>
        <div class="form-group"><label class="form-label">Reason</label>
          <select class="form-select" v-model="pauseForm.reason">
            <option value="leave">Leave</option><option value="vacation">Vacation</option><option value="other">Other</option>
          </select>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="modals.pause = false">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" @click="modals.pause = false; toast.success('Account paused.')">{{ pauseLabel }}</button>
      </template>
    </AegisModal>

    <!-- Transfer Modal -->
    <AegisModal v-if="showTransfer" v-model="modals.transfer" title="Transfer Account" size="sm">
      <div class="alert alert-gold" style="margin-bottom:14px"><div class="alert-icon"><AegisIcon name="info" :size="16" /></div><div class="alert-content" style="font-size:12px">Transfer is permanent. The recipient must have an active Aegis account.</div></div>
      <div class="form-group" style="margin-bottom:14px"><label class="form-label">Recipient Email</label><input class="form-input" type="email" v-model="transferForm.email" placeholder="recipient@example.com" /></div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="modals.transfer = false">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" @click="modals.transfer = false; toast.success('Transfer request submitted.')">Transfer Account</button>
      </template>
    </AegisModal>

    <!-- Deactivate Modal -->
    <AegisModal v-model="modals.deactivate" :title="deactivateLabel" size="md">
      <div style="background:var(--red-light);border:1px solid var(--border-dark);border-radius:var(--radius);padding:14px;margin-bottom:16px;font-size:13px;color:var(--red)"><strong>This action cannot be easily reversed.</strong></div>
      <div class="form-group">
        <label class="form-label">Type <strong>DEACTIVATE</strong> to confirm</label>
        <input class="form-input" v-model="deactivateConfirm" placeholder="DEACTIVATE" />
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="modals.deactivate = false">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" :disabled="deactivateConfirm !== 'DEACTIVATE' || deactivateForm.processing" @click="confirmDeactivate">
          <AegisIcon name="trash" :size="14" /> {{ deactivateButtonLabel }}
        </button>
      </template>
    </AegisModal>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  title:                { type: String,  default: 'Account Actions' },
  pauseLabel:           { type: String,  default: 'Pause Account' },
  pauseDesc:            { type: String,  default: 'Temporarily suspend your account.' },
  deactivateLabel:      { type: String,  default: 'Delete Account Permanently' },
  deactivateDesc:       { type: String,  default: 'Permanently delete your Aegis account.' },
  deactivateButtonLabel:{ type: String,  default: 'Delete Account' },
  showTransfer:         { type: Boolean, default: false },
  deleteRoute:          { type: String,  default: '' },
});

const toast             = useToast();
const deactivateConfirm = ref('');
const modals            = reactive({ pause: false, deactivate: false, transfer: false });
const pauseForm         = reactive({ until: '', reason: 'leave' });
const transferForm      = reactive({ email: '' });

const deactivateForm = useForm({ confirm: '' });

function confirmDeactivate() {
  if (deactivateConfirm.value !== 'DEACTIVATE') return;
  deactivateForm.confirm = 'DEACTIVATE';

  if (props.deleteRoute) {
    deactivateForm.delete(route(props.deleteRoute), {
      preserveScroll: true,
      onSuccess: () => { modals.deactivate = false; toast.success('Account deactivated.'); },
      onError:   () => toast.error('Could not deactivate account.'),
    });
  } else {
    modals.deactivate = false;
    toast.success('Deactivation request submitted.');
  }
}
</script>
