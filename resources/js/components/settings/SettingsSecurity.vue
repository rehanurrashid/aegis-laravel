<template>
  <div class="card">
    <div class="card-header">
      <div class="card-title-group">
        <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="shield" :size="16" /></div>
        <div><div class="card-title">Security &amp; Two-Factor Authentication</div><div class="card-subtitle">Unified across all portals — 2FA and sessions apply to your entire Aegis account</div></div>
      </div>
      <span style="font-size:12px;font-weight:600;color:var(--green);background:var(--green-light);padding:4px 12px;border-radius:var(--radius-full);display:inline-flex;align-items:center;gap:6px"><span class="status-dot active"></span>2FA Enabled</span>
    </div>
    <div class="card-body">
      <div class="form-hint" style="margin-bottom:14px">Security alerts and login notifications are managed in <strong>Notifications →</strong></div>
      <div v-for="tfa in tfaOptions" :key="tfa.key" class="twofa-option" :class="{ active: tfa.active }" @click="selectTfa(tfa)">
        <div class="twofa-icon"><AegisIcon :name="tfa.icon" :size="16" /></div>
        <div class="twofa-info">
          <div class="twofa-name">{{ tfa.name }} <span v-if="tfa.active" style="font-size:11px;color:var(--green);font-weight:600;display:inline-flex;align-items:center;gap:3px;vertical-align:middle"><AegisIcon name="check" :size="11" />Active</span></div>
          <div class="twofa-desc">{{ tfa.desc }}</div>
        </div>
        <button v-if="tfa.active" type="button" class="btn btn-outline btn-xs" @click.stop="modals.viewBackup = true">View Backup Codes</button>
        <button v-else type="button" class="btn btn-primary btn-xs" @click.stop="modals.setup2fa = true">Set Up</button>
      </div>
      <div class="section-label" style="margin-top:20px">Security Alerts</div>
      <div v-for="al in securityAlerts" :key="al.name" class="toggle-row">
        <div class="toggle-info"><div class="toggle-label">{{ al.name }}</div><div class="toggle-desc">{{ al.sub }}</div></div>
        <button type="button" class="toggle" :class="{ on: al.on }" @click="al.on = !al.on" :aria-pressed="al.on"></button>
      </div>
      <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
        <button type="button" class="btn btn-primary" @click="save"><AegisIcon name="check" :size="13" /> Save Security Settings</button>
      </div>
    </div>

    <AegisModal v-model="modals.setup2fa" title="Set Up Two-Factor Auth" size="md">
      <p style="font-size:14px;color:var(--text-2);margin-bottom:14px">Scan the QR code with your authenticator app, then enter the 6-digit code below to verify.</p>
      <div class="st-qr-box">
        <div class="st-qr-ico"><AegisIcon name="phone" :size="22" /></div>
        <div class="st-qr-cap">QR code would appear here</div>
        <div class="st-qr-secret">JBSW Y3DP EHPK 3PXP</div>
      </div>
      <div class="form-group"><label class="form-label">Verification Code</label><input class="form-input st-otp" maxlength="6" placeholder="123456" v-model="tfaCode" /></div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="modals.setup2fa = false">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" @click="modals.setup2fa = false; toast.success('2FA method added!')">Verify &amp; Enable</button>
      </template>
    </AegisModal>

    <AegisModal v-model="modals.viewBackup" title="Backup Codes" size="md">
      <p style="font-size:13px;color:var(--text-3);margin-bottom:14px">Store these in a secure place. Each code can only be used once.</p>
      <div class="st-codes-grid">
        <div v-for="code in backupCodes" :key="code.value" class="st-code" :class="{ used: code.used }">{{ code.value }}</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="toast.success('Codes copied.')"><AegisIcon name="clipboard" :size="14" /> Copy All</button>
        <button type="button" class="btn btn-primary btn-sm" @click="toast.success('New codes generated.')">Regenerate</button>
        <button type="button" class="btn btn-ghost btn-sm" @click="modals.viewBackup = false">Close</button>
      </template>
    </AegisModal>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  enableMfaRoute:  { type: String, required: true },
  disableMfaRoute: { type: String, required: true },
  verifyMfaRoute:  { type: String, required: true },
  mfaEnabled:      { type: Boolean, default: false },
});

const toast = useToast();
const tfaCode = ref('');
const modals  = reactive({ setup2fa: false, viewBackup: false });

const tfaOptions = reactive([
  { key: 'authenticator', name: 'Authenticator App', icon: 'phone',   desc: 'Google Authenticator, Authy, or any TOTP app — most secure', active: true  },
  { key: 'email',         name: 'Email Code',        icon: 'mail',    desc: 'A code sent to your primary email',                          active: false },
]);
function selectTfa(tfa) { tfaOptions.forEach(t => t.active = t.key === tfa.key); }

const securityAlerts = reactive([
  { name: 'Alert on New Login',             sub: 'Receive email when a new device logs into your account',       on: true },
  { name: 'Suspicious Activity Alerts',     sub: 'Notify me of unusual login attempts or access from new locations', on: true },
  { name: 'Session Timeout (30 min inactivity)', sub: 'Automatically log out after 30 minutes of inactivity',   on: true },
]);

const backupCodes = [
  { value: 'KL-4J8M-92WX', used: false }, { value: 'KL-7P2N-56TQ', used: false },
  { value: 'KL-3R9K-81ZV', used: false }, { value: 'KL-6C4H-27YB', used: false },
  { value: 'KL-1M5F-49LD', used: true  }, { value: 'KL-8A3E-65RP', used: false },
];

function save() {
  toast.success('Security settings saved.');
}
</script>
