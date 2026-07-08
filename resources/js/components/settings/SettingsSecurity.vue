<template>
  <div class="card">
    <div class="card-header">
      <div class="card-title-group">
        <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="shield" :size="16" /></div>
        <div><div class="card-title">Security &amp; Two-Factor Authentication</div><div class="card-subtitle">Unified across all portals — 2FA applies to your entire Aegis account</div></div>
      </div>
      <span :style="mfaEnabled ? 'font-size:12px;font-weight:600;color:var(--green);background:var(--green-light);padding:4px 12px;border-radius:var(--radius-full);display:inline-flex;align-items:center;gap:6px' : 'font-size:12px;font-weight:600;color:var(--text-3);background:var(--surface-2);padding:4px 12px;border-radius:var(--radius-full);display:inline-flex;align-items:center;gap:6px'">
        <span class="status-dot" :class="mfaEnabled ? 'active' : ''"></span>
        {{ mfaEnabled ? '2FA Enabled' : '2FA Disabled' }}
      </span>
    </div>
    <div class="card-body">
      <div class="form-hint" style="margin-bottom:14px">Security alerts and login notifications are managed in <strong>Notifications →</strong></div>

      <!-- Authenticator App option -->
      <div class="twofa-option" :class="{ active: mfaEnabled }" @click="!mfaEnabled && (modals.setup = true)">
        <div class="twofa-icon"><AegisIcon name="phone" :size="16" /></div>
        <div class="twofa-info">
          <div class="twofa-name">
            Authenticator App
            <span v-if="mfaEnabled" style="font-size:11px;color:var(--green);font-weight:600;display:inline-flex;align-items:center;gap:3px;vertical-align:middle">
              <AegisIcon name="check" :size="11" />Active
            </span>
          </div>
          <div class="twofa-desc">Google Authenticator, Authy, or any TOTP app — most secure</div>
        </div>
        <button v-if="mfaEnabled" type="button" class="btn btn-outline btn-xs" @click.stop="modals.backup = true">View Backup Codes</button>
        <button v-else type="button" class="btn btn-primary btn-xs" @click.stop="initSetup">Set Up</button>
      </div>

      <!-- Email Code option -->
      <div class="twofa-option" :class="{ active: false }">
        <div class="twofa-icon"><AegisIcon name="mail" :size="16" /></div>
        <div class="twofa-info">
          <div class="twofa-name">Email Code</div>
          <div class="twofa-desc">A one-time code sent to your primary email</div>
        </div>
        <span style="font-size:11px;color:var(--text-3);background:var(--surface-2);padding:3px 8px;border-radius:var(--radius-sm)">Via Authenticator preferred</span>
      </div>

      <!-- Disable 2FA -->
      <div v-if="mfaEnabled" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);display:flex;justify-content:flex-end">
        <button type="button" class="btn btn-outline btn-sm" :disabled="disabling" @click="disable2fa">
          <AegisIcon name="x" :size="13" /> Disable 2FA
        </button>
      </div>
    </div>

    <!-- Setup 2FA Modal -->
    <AegisModal v-model="modals.setup" title="Set Up Two-Factor Auth" size="md">
      <div v-if="!setupData">
        <div class="form-hint" style="margin-bottom:14px">Click Enable below to generate a QR code.</div>
      </div>
      <div v-else>
        <p style="font-size:14px;color:var(--text-2);margin-bottom:14px">Scan this QR code with your authenticator app, then enter the 6-digit code below.</p>
        <div class="st-qr-box">
          <div class="st-qr-ico"><AegisIcon name="phone" :size="22" /></div>
          <div class="st-qr-cap">Scan with your authenticator app</div>
          <div class="st-qr-secret" style="font-size:11px;letter-spacing:2px">{{ setupData.secret }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Verification Code <span class="required">*</span></label>
          <input class="form-input st-otp" maxlength="6" placeholder="123456" v-model="verifyForm.code"
            :class="{ 'is-error': verifyForm.errors.code }" />
          <div v-if="verifyForm.errors.code" class="form-error">{{ verifyForm.errors.code }}</div>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="modals.setup = false; setupData = null; verifyForm.reset()">Cancel</button>
        <button v-if="!setupData" type="button" class="btn btn-primary btn-sm" :disabled="enabling" @click="enableMfa">
          <AegisIcon name="shield" :size="13" /> Enable 2FA
        </button>
        <button v-else type="button" class="btn btn-primary btn-sm" :disabled="verifyForm.processing" @click="verifyMfa">
          <AegisIcon name="check" :size="13" /> Verify &amp; Enable
        </button>
      </template>
    </AegisModal>

    <!-- Backup Codes Modal -->
    <AegisModal v-model="modals.backup" title="Backup Codes" size="md">
      <p style="font-size:13px;color:var(--text-3);margin-bottom:14px">Store these in a secure place. Each code can only be used once.</p>
      <div class="st-codes-grid">
        <div v-for="code in backupCodes" :key="code" class="st-code">{{ code }}</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-ghost btn-sm" @click="modals.backup = false">Close</button>
      </template>
    </AegisModal>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  enableMfaRoute:  { type: String, required: true },
  disableMfaRoute: { type: String, required: true },
  verifyMfaRoute:  { type: String, required: true },
  mfaEnabled:      { type: Boolean, default: false },
});

const toast      = useToast();
const enabling   = ref(false);
const disabling  = ref(false);
const setupData  = ref(null);
const backupCodes = ref([]);
const modals     = reactive({ setup: false, backup: false });

const verifyForm = useForm({ code: '' });

async function initSetup() {
  modals.setup = true;
}

async function enableMfa() {
  enabling.value = true;
  try {
    const res = await fetch(route(props.enableMfaRoute), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        'Accept': 'application/json',
      },
    });
    const data = await res.json();
    if (res.ok) {
      setupData.value = data;
    } else {
      toast.error(data.message ?? 'Could not initiate 2FA setup.');
    }
  } catch {
    toast.error('Network error. Please try again.');
  } finally {
    enabling.value = false;
  }
}

function verifyMfa() {
  verifyForm.post(route(props.verifyMfaRoute), {
    preserveScroll: true,
    onSuccess: () => {
      modals.setup = false;
      setupData.value = null;
      verifyForm.reset();
      toast.success('Two-factor authentication enabled.');
    },
    onError: () => toast.error('Invalid code. Please try again.'),
  });
}

function disable2fa() {
  disabling.value = true;
  fetch(route(props.disableMfaRoute), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
      'Accept': 'application/json',
    },
  }).then(async (res) => {
    const data = await res.json();
    if (res.ok) {
      toast.success('Two-factor authentication disabled.');
      // Reload to sync mfaEnabled prop
      window.location.reload();
    } else {
      toast.error(data.message ?? 'Could not disable 2FA.');
    }
  }).catch(() => toast.error('Network error.'))
    .finally(() => { disabling.value = false; });
}
</script>
