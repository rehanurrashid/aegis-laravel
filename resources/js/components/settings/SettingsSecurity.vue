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
      <div class="ss-method" :class="{ 'ss-method-active': totpActive }">
        <div class="ss-method-icon" :class="!totpActive ? 'ss-method-icon-muted' : ''">
          <AegisIcon name="phone" :size="20" />
        </div>
        <div class="ss-method-body">
          <div class="ss-method-name">
            Authenticator App (TOTP)
            <span v-if="totpActive" class="ss-active-pill">
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
          <template v-else-if="totpActive">
            <button type="button" class="btn btn-outline btn-sm" @click="modals.disable = true">
              <AegisIcon name="x" :size="13" /> Disable
            </button>
          </template>
          <template v-else>
            <span class="ss-method-tag">Not active</span>
          </template>
        </div>
      </div>

      <!-- ── EMAIL CODE ─────────────────────────────────────────────── -->
      <div class="ss-method" :class="{ 'ss-method-active': emailActive }">
        <div class="ss-method-icon" :class="!emailActive ? 'ss-method-icon-muted' : ''">
          <AegisIcon name="mail" :size="20" />
        </div>
        <div class="ss-method-body">
          <div class="ss-method-name">
            Email Code
            <span v-if="emailActive" class="ss-active-pill">
              <AegisIcon name="check" :size="10" /> Active
            </span>
          </div>
          <div class="ss-method-desc">
            A 6-digit code sent to <strong>{{ userEmail || 'your email' }}</strong> each time you sign in.
            Less secure than an authenticator app but easier to set up.
          </div>
        </div>
        <div class="ss-method-action">
          <template v-if="!mfaEnabled">
            <button type="button" class="btn btn-outline btn-sm" @click="modals.emailSetup = true">
              <AegisIcon name="mail" :size="13" /> Set Up
            </button>
          </template>
          <template v-else-if="emailActive">
            <button type="button" class="btn btn-outline btn-sm" @click="modals.disable = true">
              <AegisIcon name="x" :size="13" /> Disable
            </button>
          </template>
          <template v-else>
            <span class="ss-method-tag">Not active</span>
          </template>
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

    <!-- ── EMAIL 2FA SETUP MODAL ─────────────────────────────────── -->
    <AegisModal v-model="modals.emailSetup" title="Set Up Email Two-Factor Auth" size="sm">
      <template v-if="!emailCodeSent">
        <div class="ss-setup-intro" style="padding:4px 0 8px">
          <div class="ss-setup-icon"><AegisIcon name="mail" :size="24" /></div>
          <div class="ss-setup-intro-title">Email verification codes</div>
          <div class="ss-setup-intro-body">
            A 6-digit code will be sent to <strong>{{ userEmail }}</strong> every time you sign in.
          </div>
        </div>
        <div class="alert alert-gold" style="margin-top:14px">
          <div class="alert-icon"><AegisIcon name="alert-triangle" :size="15" /></div>
          <div class="alert-content" style="font-size:12.5px">
            Authenticator apps are more secure. Use Email 2FA only if you don't have access to an authenticator app.
          </div>
        </div>
      </template>
      <template v-else>
        <p style="font-size:14px;color:var(--text-2);margin-bottom:18px">
          A 6-digit code was sent to <strong>{{ userEmail }}</strong>. Enter it below to activate Email 2FA.
        </p>
        <div class="ss-otp-card">
          <div class="ss-otp-label">
            <AegisIcon name="mail" :size="14" />
            Enter the code from your email
            <span class="ss-otp-timer">Expires in 10 min</span>
          </div>
          <div class="ss-otp-wrap">
            <input
              class="form-input ss-otp-input"
              :class="{ 'is-error': emailVerifyForm.errors.code }"
              type="text"
              inputmode="numeric"
              maxlength="6"
              placeholder="000000"
              v-model="emailVerifyForm.code"
              @keyup.enter="verifyEmailOtp"
              autocomplete="one-time-code"
            />
          </div>
          <div v-if="emailVerifyForm.errors.code" class="form-error" style="text-align:center;margin-top:6px">{{ emailVerifyForm.errors.code }}</div>
        </div>
      </template>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="cancelEmailSetup">Cancel</button>
        <button v-if="!emailCodeSent" type="button" class="btn btn-gold btn-sm" :disabled="emailSending" @click="sendEmailOtp">
          <AegisIcon name="mail" :size="13" />
          {{ emailSending ? 'Sending…' : 'Send Code to My Email' }}
        </button>
        <button v-else type="button" class="btn btn-gold btn-sm" :disabled="emailVerifyForm.processing || emailVerifyForm.code.length !== 6" @click="verifyEmailOtp">
          <AegisIcon name="check" :size="13" />
          {{ emailVerifyForm.processing ? 'Verifying…' : 'Verify & Enable' }}
        </button>
      </template>
    </AegisModal>

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
        <p style="font-size:14px;color:var(--text-2);margin-bottom:20px">
          Scan the QR code with your authenticator app, then enter the 6-digit code to confirm setup.
        </p>

        <!-- ── QR + Manual key layout ─────────────────────────────── -->
        <div class="ss-qr-card">
          <!-- Left: QR image -->
          <div class="ss-qr-frame">
            <img
              v-if="qrImageUrl"
              :src="qrImageUrl"
              alt="2FA QR Code"
              class="ss-qr-img"
            />
            <div v-else class="ss-qr-skeleton">
              <AegisIcon name="phone" :size="28" />
              <span>Generating…</span>
            </div>
          </div>

          <!-- Right: Steps + manual key -->
          <div class="ss-qr-right">
            <ol class="ss-qr-steps">
              <li>Open your authenticator app</li>
              <li>Tap <strong>+</strong> and choose <strong>Scan QR code</strong></li>
              <li>Point your camera at the code</li>
            </ol>
            <div class="ss-qr-manual-label">Or enter this key manually:</div>
            <div class="ss-qr-secret-box">{{ formatSecret(setupData.secret) }}</div>
            <button type="button" class="btn btn-ghost btn-xs ss-qr-copy-btn" @click="copySecret">
              <AegisIcon name="clipboard" :size="12" /> Copy key
            </button>
          </div>
        </div>

        <!-- ── OTP input ──────────────────────────────────────────── -->
        <div class="ss-otp-card">
          <div class="ss-otp-label">
            <AegisIcon name="shield" :size="14" />
            Enter the 6-digit code from your app
            <span class="ss-otp-timer">Refreshes every 30s</span>
          </div>
          <div class="ss-otp-wrap">
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
          </div>
          <div v-if="verifyForm.errors.code" class="form-error" style="text-align:center;margin-top:6px">{{ verifyForm.errors.code }}</div>
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
      <div v-if="backupCodes.length === 0" style="text-align:center;padding:24px;color:var(--text-3);font-size:13px;display:flex;flex-direction:column;align-items:center;gap:8px">
        <AegisIcon name="loader" :size="20" style="color:var(--gold-dark)" />
        Loading your backup codes…
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
  enableMfaRoute:       { type: String, required: true },
  disableMfaRoute:      { type: String, required: true },
  verifyMfaRoute:       { type: String, required: true },
  enableEmailMfaRoute:  { type: String, default: '' },
  verifyEmailMfaRoute:  { type: String, default: '' },
  backupCodesRoute:     { type: String, default: '' },
  mfaEnabled:           { type: Boolean, default: false },
  mfaMethod:            { type: String,  default: '' },   // 'totp' | 'email' | ''
  userEmail:            { type: String,  default: '' },
});

const toast         = useToast();
const enabling      = ref(false);
const setupData     = ref(null);
const qrImageUrl    = ref('');
const backupCodes   = ref([]);
const showDisablePw = ref(false);
const otpInput      = ref(null);
const modals        = reactive({ setup: false, disable: false, backup: false, emailSetup: false });

// Email MFA state
const emailSending     = ref(false);
const emailCodeSent    = ref(false);
const emailVerifyForm  = useForm({ code: '' });

const verifyForm  = useForm({ code: '' });
const disableForm = useForm({ password: '' });

// Computed method helpers
const activeMfaMethod = computed(() => props.mfaMethod || (props.mfaEnabled ? 'totp' : ''));
const totpActive      = computed(() => activeMfaMethod.value === 'totp');
const emailActive     = computed(() => activeMfaMethod.value === 'email');

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
    // Use window.axios — configured in bootstrap.js with X-CSRF-TOKEN header
    const res = await window.axios.post(route(props.enableMfaRoute), {}, {
      headers: { 'Accept': 'application/json' },
    });
    const data = res.data;
    setupData.value = data;

    // Generate QR inline via qrcode.js loaded from CDN (no npm package needed)
    qrImageUrl.value = await generateQrDataUrl(data.provisioning_uri);

    // Focus OTP input after render
    await nextTick();
    otpInput.value?.focus();
  } catch {
    toast.error('Network error. Please try again.');
  } finally {
    enabling.value = false;
  }
}

// ── Email 2FA flow ───────────────────────────────────────────────────────

async function sendEmailOtp() {
  if (!props.enableEmailMfaRoute) { toast.error('Email 2FA not configured.'); return; }
  emailSending.value = true;
  try {
    // Use window.axios — configured in bootstrap.js with X-CSRF-TOKEN header
    await window.axios.post(route(props.enableEmailMfaRoute));
    emailCodeSent.value = true;
    toast.success('Verification code sent to ' + props.userEmail);
  } catch (e) {
    toast.error(e?.response?.data?.message ?? 'Could not send verification code.');
  } finally {
    emailSending.value = false;
  }
}

function verifyEmailOtp() {
  if (emailVerifyForm.code.length !== 6) { toast.error('Enter the 6-digit code from your email.'); return; }
  emailVerifyForm.post(route(props.verifyEmailMfaRoute), {
    preserveScroll: true,
    onSuccess: () => {
      modals.emailSetup = false;
      emailCodeSent.value = false;
      emailVerifyForm.reset();
      toast.success('Email two-factor authentication enabled.');
    },
    onError: () => toast.error('Invalid or expired code. Please try again.'),
  });
}

function cancelEmailSetup() {
  modals.emailSetup = false;
  emailCodeSent.value = false;
  emailVerifyForm.reset();
  emailVerifyForm.clearErrors();
}

// ── QR code generation (CDN, no npm package required) ────────────────────

function loadScript(src) {
  return new Promise((resolve, reject) => {
    if (document.querySelector(`script[src="${src}"]`)) { resolve(); return; }
    const s = document.createElement('script');
    s.src = src; s.onload = resolve; s.onerror = reject;
    document.head.appendChild(s);
  });
}

async function generateQrDataUrl(text) {
  await loadScript('https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js');
  return new Promise((resolve) => {
    const div = document.createElement('div');
    div.style.cssText = 'position:absolute;left:-9999px;top:-9999px;width:180px;height:180px';
    document.body.appendChild(div);
    // eslint-disable-next-line no-undef
    new window.QRCode(div, {
      text,
      width:  180,
      height: 180,
      colorDark:  '#000000',
      colorLight: '#ffffff',
      correctLevel: window.QRCode?.CorrectLevel?.M ?? 0,
    });
    // QRCode renders synchronously into a canvas child
    setTimeout(() => {
      const canvas = div.querySelector('canvas');
      if (canvas) {
        resolve(canvas.toDataURL('image/png'));
      } else {
        // Fallback: qrcodejs may render an img instead
        const img = div.querySelector('img');
        resolve(img?.src ?? '');
      }
      document.body.removeChild(div);
    }, 100);
  });
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
  // If we have codes from the just-completed setup flow, use those
  if (setupData.value?.recovery_codes?.length) {
    backupCodes.value = setupData.value.recovery_codes;
    modals.backup = true;
  } else {
    // Fetch from server (already-enabled accounts)
    fetchBackupCodes();
  }
}

async function fetchBackupCodes() {
  backupCodes.value = []; // clear while loading
  modals.backup = true;   // open modal immediately (shows loading state)
  try {
    // Use window.axios — configured in bootstrap.js with X-CSRF-TOKEN header
    const backupRoute = props.backupCodesRoute
      || props.enableMfaRoute.replace(/\/enable$/, '/backup-codes');
    const res = await window.axios.get(route(backupRoute), {
      headers: { 'Accept': 'application/json' },
    });
    backupCodes.value = res.data.recovery_codes ?? [];
  } catch {
    toast.error('Could not load backup codes. Please try again.');
    modals.backup = false;
  }
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

/* ── QR card ─────────────────────────────────────────────────────── */
.ss-qr-card {
  display: flex;
  gap: 20px;
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface-2);
  margin-bottom: 18px;
}
.ss-qr-frame {
  flex-shrink: 0;
  width: 160px;
  height: 160px;
  border-radius: var(--radius-sm);
  background: var(--surface); /* QR data URL has its own white background */
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
  border: 1px solid var(--border);
}
.ss-qr-img {
  width: 100%;
  height: 100%;
  display: block;
  image-rendering: pixelated;
}
.ss-qr-skeleton {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: var(--text-3);
  font-size: 11px;
}
.ss-qr-right {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 8px;
}
.ss-qr-steps {
  margin: 0 0 4px 16px;
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.9;
  padding: 0;
}
.ss-qr-manual-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: var(--text-3);
}
.ss-qr-secret-box {
  font-family: var(--font-mono, monospace);
  font-size: 12px;
  font-weight: 700;
  color: var(--text);
  letter-spacing: 2px;
  word-break: break-all;
  background: var(--surface);
  padding: 8px 10px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  line-height: 1.7;
}
.ss-qr-copy-btn { align-self: flex-start; margin-top: 2px; }

/* ── OTP card ─────────────────────────────────────────────────────── */
.ss-otp-card {
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface-2);
  padding: 18px;
  text-align: center;
}
.ss-otp-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-2);
  margin-bottom: 14px;
  flex-wrap: wrap;
  justify-content: center;
}
.ss-otp-timer {
  font-size: 11px;
  font-weight: 600;
  color: var(--text-4);
  background: var(--surface-3, var(--surface));
  border: 1px solid var(--border);
  padding: 2px 8px;
  border-radius: var(--radius-full);
}
.ss-otp-wrap { display: flex; justify-content: center; }
.ss-otp-input {
  font-family: var(--font-mono, monospace);
  font-size: 28px;
  font-weight: 700;
  letter-spacing: 10px;
  text-align: center;
  width: 220px;
  height: 60px;
  padding-left: 16px;
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
