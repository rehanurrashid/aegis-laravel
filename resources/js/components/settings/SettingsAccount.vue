<template>
  <div>
    <div class="card">
      <div class="card-header">
        <div class="card-title-group">
          <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="lock" :size="16" /></div>
          <div><div class="card-title">Account &amp; Login</div><div class="card-subtitle">Email, password, and login credentials</div></div>
        </div>
      </div>
      <div class="card-body">
        <div class="alert alert-info" style="margin-bottom:14px">
          <div class="alert-icon"><AegisIcon name="users" :size="16" /></div>
          <div class="alert-content" style="font-size:12px"><strong>Unified credentials</strong> — Your email, phone, and password are shared across all portals. Changes here apply everywhere on your Aegis account.</div>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">Primary Email <span class="required">*</span></label>
            <input class="form-input" :class="{ 'is-error': fieldError('email') }" type="email" v-model="acct.email" @blur="v$.email.$touch()" />
            <div v-if="fieldError('email')" class="form-error">{{ fieldError('email') }}</div>
            <div v-else class="form-hint" style="display:flex;align-items:center;gap:4px;color:var(--green)"><AegisIcon name="check" :size="14" /> Verified · Used for login and important notices</div>
          </div>
          <div class="form-group">
            <label class="form-label">Recovery Email</label>
            <input class="form-input" type="email" placeholder="Secondary email…" v-model="acct.recovery" />
            <div class="form-hint">Used if you lose access to your primary email</div>
          </div>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input class="form-input" type="tel" v-model="acct.phone" />
          </div>
          <div class="form-group">
            <label class="form-label">Username / Handle</label>
            <input class="form-input" v-model="acct.handle" />
            <div class="form-hint">Your public @handle on Aegis</div>
          </div>
        </div>
        <hr class="form-divider" style="margin-top:20px" />
        <div style="font-size:14px;font-weight:600;color:var(--text);margin:20px 0 14px">Change Password</div>
        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input class="form-input" :class="{ 'is-error': fieldError('newpw') }" type="password" v-model="acct.newpw" @blur="v$.newpw.$touch()" placeholder="Min 12 characters…" />
            <div v-if="fieldError('newpw')" class="form-error">{{ fieldError('newpw') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input class="form-input" :class="{ 'is-error': fieldError('confirmpw') }" type="password" v-model="acct.confirmpw" @blur="v$.confirmpw.$touch()" placeholder="Repeat new password…" />
            <div v-if="fieldError('confirmpw')" class="form-error">{{ fieldError('confirmpw') }}</div>
          </div>
        </div>
        <div style="font-size:12px;color:var(--text-3);margin-bottom:14px">Password must be 12+ characters, include uppercase, number, and special character.</div>
        <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
          <button type="button" class="btn btn-outline" @click="v$.$reset()">Cancel</button>
          <button type="button" class="btn btn-primary" @click="savePassword"><AegisIcon name="lock" :size="14" /> Update Password</button>
        </div>
      </div>
    </div>

    <div class="card" style="margin-top:16px">
      <div class="card-header">
        <div class="card-title-group">
          <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="monitor" :size="16" /></div>
          <div><div class="card-title">Active Sessions</div><div class="card-subtitle">Devices currently logged into your account</div></div>
        </div>
        <button type="button" class="btn btn-danger btn-sm" @click="modals.revokeAll = true"><AegisIcon name="x" :size="12" /> Revoke All</button>
      </div>
      <div class="card-body">
        <div v-for="sess in sessions" :key="sess.device" class="session-item">
          <div class="session-icon"><AegisIcon :name="sess.mobile ? 'phone' : 'monitor'" :size="20" /></div>
          <div class="session-info">
            <div class="session-device">{{ sess.device }} <span v-if="sess.current" class="session-current">Current</span></div>
            <div class="session-meta">{{ sess.meta }}</div>
          </div>
          <button v-if="!sess.current" type="button" class="btn-icon btn-icon-danger" data-tooltip="Revoke session" @click="toast.info('Session revoked.')"><AegisIcon name="x" :size="14" /></button>
        </div>
      </div>
    </div>

    <AegisModal v-model="modals.revokeAll" title="Revoke All Sessions" size="sm">
      <p style="font-size:14px;color:var(--text-2)">This will sign out all devices except your current session. You'll need to log in again on other devices.</p>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="modals.revokeAll = false">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" @click="modals.revokeAll = false; toast.success('All other sessions revoked.')">Revoke All</button>
      </template>
    </AegisModal>
  </div>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useVuelidate } from '@vuelidate/core';
import { required, email, minLength, sameAs, helpers } from '@vuelidate/validators';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  user:                { type: Object,  default: () => ({}) },
  updatePasswordRoute: { type: String,  required: true },
});

const toast = useToast();

const acct = reactive({
  email:     props.user?.email     || '',
  recovery:  '',
  phone:     props.user?.phone     || '',
  handle:    props.user?.handle    || '',
  newpw:     '',
  confirmpw: '',
});

const rules = computed(() => ({
  email:     { required: helpers.withMessage('Email is required.', required), email: helpers.withMessage('Enter a valid email.', email) },
  newpw:     { minLength: helpers.withMessage('Minimum 12 characters.', minLength(12)) },
  confirmpw: { sameAs: helpers.withMessage('Passwords must match.', sameAs(computed(() => acct.newpw))) },
}));
const v$ = useVuelidate(rules, acct);

function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message;
  return null;
}
async function savePassword() {
  const valid = await v$.value.$validate();
  if (!valid) { toast.error('Please fix the highlighted fields.'); return; }
  router.put(route(props.updatePasswordRoute), { password: acct.newpw, password_confirmation: acct.confirmpw }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Password updated.'); v$.value.$reset(); acct.newpw = ''; acct.confirmpw = ''; },
    onError: () => toast.error('Could not update password.'),
  });
}

const sessions = [
  { device: 'MacBook Pro — Chrome 121', meta: 'New York, NY · Last active just now · 192.168.1.45', current: true,  mobile: false },
  { device: 'iPhone 15 Pro — Safari',   meta: 'New York, NY · Last active 2h ago · 73.x.x.x',      current: false, mobile: true  },
  { device: 'Windows PC — Firefox 122', meta: 'Unrecognized location · Last active 3 days ago',     current: false, mobile: false },
];

const modals = reactive({ revokeAll: false });
</script>
