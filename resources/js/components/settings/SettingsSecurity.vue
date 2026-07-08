<template>
  <div class="card">
    <div class="card-header">
      <div class="card-title-group">
        <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)">
          <AegisIcon name="shield" :size="16" />
        </div>
        <div>
          <div class="card-title">Security &amp; Two-Factor Authentication</div>
          <div class="card-subtitle">2FA applies to your entire Aegis account across all portals</div>
        </div>
      </div>
      <!-- Status badge -->
      <span class="ss-status-badge" :class="mfaEnabled ? 'ss-status-on' : 'ss-status-off'">
        <span class="status-dot" :class="{ active: mfaEnabled }"></span>
        {{ mfaEnabled ? '2FA Enabled' : '2FA Disabled' }}
      </span>
    </div>

    <div class="card-body">
      <!-- Info hint -->
      <div class="alert alert-info" style="margin-bottom:18px">
        <div class="alert-icon"><AegisIcon name="info" :size="16" /></div>
        <div class="alert-content" style="font-size:12px">
          Security alerts and login notifications are configured in
          <strong>Notifications →</strong>. 2FA is strongly recommended for all healthcare data.
        </div>
      </div>

      <!-- ── AUTHENTICATOR APP ──────────────────────────────────────── -->
      <div class="ss-method" :class="{ 'ss-method-active': mfaEnabled }">
        <div class="ss-method-icon">
          <AegisIcon name="phone" :size="20" />
        </div>
        <div class="ss-method-body">
          <div class="ss-method-name">
            Authenticator App (TOTP)
            <span v-if="mfaEnabled" class="ss-active-pill">
              <AegisIcon name="check" :size="10" /> Active
            </span>
          </div>
          <div class="ss-method-desc">
            Google Authenticator, Authy, 1Password, or any TOTP-compatible app.
            Generates a new 6-digit code every 30 seconds — most secure option.
          </div>
        </div>
        <div class="ss-method-action">
          <template v-if="!mfaEnabled">
            <button type="button" class="btn btn-gold btn-sm" :disabled="enabling" @click="openSetup">
              <AegisIcon name="shield" :size="13" />
              {{ enabling ? 'Generating…' : 'Set Up 2FA' }}
            </button>
          </template>
          <template v-else>
            <button type="button" class="btn btn-outline btn-sm" @click="modals.disable = true">
              <AegisIcon name="x" :size="13" /> Disable
            </button>
          </template>
        </div>
      </div>

      <!-- ── EMAIL CODE ─────────────────────────────────────────────── -->
      <div class="ss-method ss-method-secondary">
        <div class="ss-method-icon ss-method-icon-muted">
          <AegisIcon name="mail" :size="20" />
        </div>
        <div class="ss-method-body">
          <div class="ss-method-name">Email Code</div>
          <div class="ss-method-desc">
            One-time code sent to your verified email. Less secure than an authenticator
            app but available as a fallback if you lose access to your device.
          </div>
        </div>
        <div class="ss-method-action">
          <span class="ss-method-tag">Fallback</span>
        </div>
      </div>

      <!-- ── RECOVERY INFO (when enabled) ─────────────────────────── -->
      <div v-if="mfaEnabled" class="ss-recovery-block">
        <div class="ss-recovery-icon"><AegisIcon name="key" :size="16" /></div>
        <div class="ss-recovery-body">
          <div class="ss-recovery-title">Recovery & Backup</div>
          <div class="ss-recovery-desc">If you lose access to your authenticator app, use a backup code or contact support.</div>
        </div>
        <div>
          <button type="button" class="btn btn-outline btn-sm" @click="openBackupCodes">
            <AegisIcon name="key" :size="13" /> Backup Codes
          </button>
        </div>
      </div>
    </div>

    <!-- ── SETUP 2FA MODAL ────────────────────────────────────────── -->
    <AegisModal v-model="modals.setup" title="Set Up Two-Factor Authentication" size="md">
      <!-- Step 1: Generate -->
      <div v-if="!setupData" class="ss-setup-intro">
        <div class="ss-setup-icon"><AegisIcon name="shield" :size="28" /></div>
        <div class="ss-setup-intro-title">Secure your account with 2FA</div>
        <div class="ss-setup-intro-body">
          You'll need an authenticator app on your phone.
          <strong>Google Authenticator</strong>, <strong>Authy</strong>, or <strong>1Password</strong> all work.
        </div>
        <ol class="ss-setup-steps">
          <li>Click <strong>Generate QR Code</strong> below</li>
          <li>Open your authenticator app and scan the QR code</li>
          <li>Enter the 6-digit code shown in the app</li>
        </ol>
      </div>

      <!-- Step 2: Scan QR + Verify -->
      <div v-else>
        <p style="font-size:14px;color:var(--text-2);margin-bottom:18px">
          Scan this QR code with your authenticator app, then enter the 6-digit verification code.
        </p>

        <!-- QR code via Google Charts API -->
        <div class="ss-qr-container">
          <img
            v-if="qrImageUrl"
            :src="qrImageUrl"
            alt="2FA QR Code"
            class="ss-qr-image"
          />
          <div v-else class="ss-qr-placeholder">
            <AegisIcon name="phone" :size="32" />
          </div>
          <!-- Manual entry key -->
          <div class="ss-secret-key">
            <div class="ss-secret-key-label">Or enter this key manually:</div>
            <div class="ss-secret-key-value">{{ formatSecret(setupData.secret) }}</div>
            <button type="button" class="btn btn-ghost btn-xs" @click="copySecret" style="margin-top:6px">
              <AegisIcon name="clipboard" :size="12" /> Copy
            </button>
          </div>
        </div>

        <div class="form-group" style="margin-top:18px">
          <label class="form-label">Verification Code <span class="required">*</span></label>
          <input
            class="form-input ss-otp-input"
            :class="{ 'is-error': verifyForm.errors.code }"
            type="text"
            inputmode="numeric"
            maxlength="6"
            placeholder="000000"
            v-model="verifyForm.code"
            @keyup.enter="verifyMfa"
            ref="otpInput"
            autocomplete="one-time-code"
          />
          <div v-if="verifyForm.errors.code" class="form-error">{{ verifyForm.errors.code }}</div>
          <div class="form-hint">The code refreshes every 30 seconds.</div>
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="cancelSetup">Cancel</button>
        <button
          v-if="!setupData"
          type="button"
          class="btn btn-gold btn-sm"
          :disabled="enabling"
          @click="generateQr"
        >
          <AegisIcon name="phone" :size="13" />
          {{ enabling ? 'Generating…' : 'Generate QR Code' }}
        </button>
        <button
          v-else
          type="button"
          class="btn btn-gold btn-sm"
          :disabled="verifyForm.processing || verifyForm.code.length !== 6"
          @click="verifyMfa"
        >
          <AegisIcon name="check" :size="13" />
          {{ verifyForm.processing ? 'Verifying…' : 'Verify & Enable' }}
        </button>
      </template>
    </AegisModal>

    <!-- ── DISABLE 2FA MODAL ──────────────────────────────────────── -->
    <AegisModal v-model="modals.disable" title="Disable Two-Factor Authentication" size="sm">
      <div class="alert alert-gold" style="margin-bottom:16px">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="16" /></div>
        <div class="alert-content" style="font-size:13px">
          Disabling 2FA makes your account less secure. Make sure you have a strong, unique password.
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm your password <span class="required">*</span></label>
        <div class="input-password-wrap">
          <input
            class="form-input"
            :class="{ 'is-error': disableForm.errors.password }"
            :type="showDisablePw ? 'text' : 'password'"
            v-model="disableForm.password"
            placeholder="Your current password"
            autocomplete="current-password"
            @keyup.enter="confirmDisable"
          />
          <button type="button" class="pw-toggle" @click="showDisablePw = !showDisablePw">
            <AegisIcon :name="showDisablePw ? 'eye-off' : 'eye'" :size="15" />
          </button>
        </div>
        <div v-if="disableForm.errors.password" class="form-error">{{ disableForm.errors.password }}</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="cancelDisable">Cancel</button>
        <button
          type="button"
          class="btn btn-danger btn-sm"
          :disabled="disableForm.processing || !disableForm.password"
          @click="confirmDisable"
        >
          <AegisIcon name="x" :size="13" />
          {{ disableForm.processing ? 'Disabling…' : 'Disable 2FA' }}
        </button>
      </template>
    </AegisModal>

    <!-- ── BACKUP CODES MODAL ─────────────────────────────────────── -->
    <AegisModal v-model="modals.backup" title="Backup Recovery Codes" size="md">
      <div class="alert alert-gold" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="16" /></div>
        <div class="alert-content" style="font-size:13px">
          Each 6-digit code can only be used <strong>once</strong>. Save them somewhere secure — a password manager or printed sheet.
        </div>
      </div>
      <div v-if="backupCodes.length === 0" style="text-align:center;padding:20px;color:var(--text-3);font-size:13px">
        No backup codes available. Re-generate 2FA to get new codes.
      </div>
      <div v-else class="ss-codes-grid">
        <div
          v-for="(code, i) in backupCodes"
          :key="i"
          class="ss-code"
        >{{ code }}</div>
      </div>
      <div style="font-size:12px;color:var(--text-3);margin-top:12px;text-align:center">
        {{ backupCodes.length }} codes remaining. Once used, a code cannot be reused.
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="copyAllCodes">
          <AegisIcon name="clipboard" :size="14" /> Copy All
        </button>
        <button type="button" class="btn btn-ghost btn-sm" @click="modals.backup = false">Close</button>
      </template>
    </AegisModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  enableMfaRoute:    { type: String, required: true },
  disableMfaRoute:   { type: String, required: true },
  verifyMfaRoute:    { type: String, required: true },
  backupCodesRoute:  { type: String, default: '' },
  mfaEnabled:        { type: Boolean, default: false },
  userEmail:         { type: String,  default: '' },
});

const toast         = useToast();
const enabling      = ref(false);
const setupData     = ref(null);
const qrImageUrl    = ref('');
const backupCodes   = ref([]);
const showDisablePw = ref(false);
const otpInput      = ref(null);
const modals        = reactive({ setup: false, disable: false, backup: false });

const verifyForm  = useForm({ code: '' });
const disableForm = useForm({ password: '' });

// ── Setup flow ────────────────────────────────────────────────────────────

function openSetup() {
  modals.setup = true;
}

function cancelSetup() {
  modals.setup  = false;
  setupData.value = null;
  qrImageUrl.value = '';
  verifyForm.reset();
  verifyForm.clearErrors();
}

async function generateQr() {
  enabling.value = true;
  try {
    // Get CSRF token from meta tag (set by Laravel blade) or from cookie
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
      ?? document.cookie.split(';').find(c => c.trim().startsWith('XSRF-TOKEN='))
          ?.split('=')[1]?.replace(/%3D/g, '=') ?? '';

    const res = await fetch(route(props.enableMfaRoute), {
      method:      'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type':  'application/json',
        'X-CSRF-TOKEN':  csrfToken,
        'Accept':        'application/json',
      },
    });
    const data = await res.json();
    if (!res.ok) {
      toast.error(data.message ?? 'Could not initiate 2FA setup.');
      return;
    }
    setupData.value  = data;

    // Build QR via Google Charts — no composer package needed
    const uri        = encodeURIComponent(data.provisioning_uri);
    qrImageUrl.value = `https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=${uri}`;

    // Focus OTP input after render
    await nextTick();
    otpInput.value?.focus();
  } catch {
    toast.error('Network error. Please try again.');
  } finally {
    enabling.value = false;
  }
}

function formatSecret(secret) {
  // AAAA BBBB CCCC DDDD format for easy manual entry
  return (secret ?? '').match(/.{1,4}/g)?.join(' ') ?? secret;
}

function copySecret() {
  if (!setupData.value?.secret) return;
  navigator.clipboard.writeText(setupData.value.secret)
    .then(() => toast.success('Secret key copied.'))
    .catch(() => toast.error('Could not copy.'));
}

function verifyMfa() {
  if (verifyForm.code.length !== 6) {
    toast.error('Enter the full 6-digit code.');
    return;
  }
  verifyForm.post(route(props.verifyMfaRoute), {
    preserveScroll: true,
    onSuccess: () => {
      // Keep setupData briefly to access recovery_codes before clearing
      const codes = setupData.value?.recovery_codes ?? [];
      modals.setup    = false;
      qrImageUrl.value = '';
      verifyForm.reset();
      toast.success('Two-factor authentication is now enabled.');
      // Show backup codes immediately after setup
      if (codes.length) {
        backupCodes.value = codes;
        setTimeout(() => { setupData.value = null; modals.backup = true; }, 300);
      } else {
        setupData.value = null;
      }
    },
    onError: () => {
      toast.error('Invalid code — please check your authenticator app and try again.');
    },
  });
}

// ── Disable flow ──────────────────────────────────────────────────────────

function cancelDisable() {
  modals.disable = false;
  disableForm.reset();
  disableForm.clearErrors();
  showDisablePw.value = false;
}

function confirmDisable() {
  if (!disableForm.password) {
    toast.error('Please enter your password to confirm.');
    return;
  }
  disableForm.post(route(props.disableMfaRoute), {
    preserveScroll: true,
    onSuccess: () => {
      cancelDisable();
      toast.success('Two-factor authentication has been disabled.');
      // Refresh to sync mfaEnabled prop from server
      setTimeout(() => window.location.reload(), 800);
    },
    onError: (errors) => {
      if (errors.password) {
        toast.error('Incorrect password. Please try again.');
      } else {
        toast.error('Could not disable 2FA. Please try again.');
      }
    },
  });
}

// ── Backup codes ──────────────────────────────────────────────────────────

function openBackupCodes() {
  // Show stored recovery codes from setupData (returned by enable endpoint)
  // or generate display placeholders if already enabled
  if (setupData.value?.recovery_codes?.length) {
    backupCodes.value = setupData.value.recovery_codes;
  } else {
    // Fetch backup codes from server for already-enabled accounts
    fetchBackupCodes();
    return;
  }
  modals.backup = true;
}

async function fetchBackupCodes() {
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const res = await fetch(route(props.backupCodesRoute ?? props.enableMfaRoute.replace('enable', 'backup-codes')), {
      method:      'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    });
    if (res.ok) {
      const data = await res.json();
      backupCodes.value = data.recovery_codes ?? [];
    }
  } catch {
    // If no backup codes endpoint, show placeholder message
    backupCodes.value = [];
  }
  modals.backup = true;
}

function copyAllCodes() {
  const text = backupCodes.value.join('\n');
  navigator.clipboard.writeText(text)
    .then(() => toast.success('Backup codes copied.'))
    .catch(() => toast.error('Could not copy.'));
}
</script>

<style scoped>
/* ── Status badge ────────────────────────────────────────────────── */
.ss-status-badge {
  font-size: 12px;
  font-weight: 600;
  padding: 5px 14px;
  border-radius: var(--radius-full);
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}
.ss-status-on  { color: var(--green-dark); background: var(--green-light); }
.ss-status-off { color: var(--text-3);     background: var(--surface-2); }

/* ── Method rows ─────────────────────────────────────────────────── */
.ss-method {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  margin-bottom: 10px;
  background: var(--surface);
  transition: border-color var(--transition);
}
.ss-method-active {
  border-color: var(--gold-dark);
  background: var(--icon-bg-gold);
}
.ss-method-secondary { opacity: 0.75; }
.ss-method-icon {
  width: 42px;
  height: 42px;
  border-radius: var(--radius);
  background: var(--icon-bg-gold);
  color: var(--gold-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.ss-method-active .ss-method-icon { background: rgba(160,129,62,.18); }
.ss-method-icon-muted { background: var(--surface-2); color: var(--text-3); }
.ss-method-body { flex: 1; min-width: 0; }
.ss-method-name {
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 4px;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.ss-method-desc { font-size: 12px; color: var(--text-3); line-height: 1.6; }
.ss-method-action { flex-shrink: 0; }
.ss-method-tag {
  font-size: 11px;
  font-weight: 600;
  color: var(--text-3);
  background: var(--surface-2);
  padding: 3px 10px;
  border-radius: var(--radius-full);
}
.ss-active-pill {
  font-size: 11px;
  font-weight: 700;
  color: var(--green-dark);
  background: var(--green-light);
  padding: 2px 8px;
  border-radius: var(--radius-full);
  display: inline-flex;
  align-items: center;
  gap: 3px;
}

/* ── Recovery block ──────────────────────────────────────────────── */
.ss-recovery-block {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface-2);
  margin-top: 6px;
}
.ss-recovery-icon {
  width: 36px;
  height: 36px;
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-3);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.ss-recovery-body { flex: 1; }
.ss-recovery-title { font-size: 13px; font-weight: 700; color: var(--text); }
.ss-recovery-desc  { font-size: 12px; color: var(--text-3); margin-top: 2px; }

/* ── Setup intro ─────────────────────────────────────────────────── */
.ss-setup-intro { text-align: center; padding: 8px 0 4px; }
.ss-setup-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: var(--icon-bg-gold);
  color: var(--gold-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
}
.ss-setup-intro-title { font-family: var(--font-serif); font-size: 18px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
.ss-setup-intro-body  { font-size: 13px; color: var(--text-2); line-height: 1.7; margin-bottom: 16px; }
.ss-setup-steps {
  text-align: left;
  margin: 0 auto;
  max-width: 300px;
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.9;
  padding-left: 20px;
}

/* ── QR ──────────────────────────────────────────────────────────── */
.ss-qr-container {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface-2);
}
.ss-qr-image {
  width: 150px;
  height: 150px;
  border-radius: var(--radius-sm);
  image-rendering: pixelated;
  flex-shrink: 0;
  background: var(--surface);
  padding: 4px;
}
.ss-qr-placeholder {
  width: 150px;
  height: 150px;
  border-radius: var(--radius-sm);
  background: var(--surface);
  border: 1px dashed var(--border-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-3);
  flex-shrink: 0;
}
.ss-secret-key { flex: 1; min-width: 0; }
.ss-secret-key-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--text-3); margin-bottom: 6px; }
.ss-secret-key-value {
  font-family: var(--font-mono, monospace);
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
  letter-spacing: 2px;
  word-break: break-all;
  background: var(--surface);
  padding: 8px 10px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
}

/* ── OTP input ───────────────────────────────────────────────────── */
.ss-otp-input {
  font-family: var(--font-mono, monospace);
  font-size: 22px;
  font-weight: 700;
  letter-spacing: 8px;
  text-align: center;
  max-width: 200px;
  display: block;
  margin: 0 auto;
}

/* ── Backup codes grid ───────────────────────────────────────────── */
.ss-codes-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}
.ss-code {
  font-family: var(--font-mono, monospace);
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 8px 12px;
  text-align: center;
  letter-spacing: 1px;
}

/* ── Disable modal password input ────────────────────────────────── */
.input-password-wrap { position: relative; }
.input-password-wrap .form-input { padding-right: 42px; }
.pw-toggle {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: none;
  cursor: pointer;
  color: var(--text-3);
  padding: 2px;
  display: flex;
  align-items: center;
}
.pw-toggle:hover { color: var(--text); }
</style>
