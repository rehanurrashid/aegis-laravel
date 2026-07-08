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
          <div class="alert-content" style="font-size:12px"><strong>Unified credentials</strong> — Your email, phone, and password are shared across all portals.</div>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">Primary Email</label>
            <input class="form-input" type="email" :value="user?.email ?? ''" disabled />
            <div class="form-hint" style="display:inline-flex;align-items:center;gap:4px;color:var(--green)"><AegisIcon name="check" :size="14" /> Verified · Used for login and important notices</div>
          </div>
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input class="form-input" type="tel" :value="user?.phone ?? ''" disabled />
          </div>
        </div>

        <hr class="form-divider" style="margin-top:20px" />
        <div style="font-size:14px;font-weight:600;color:var(--text);margin:20px 0 14px">Change Password</div>

        <div class="form-group">
          <label class="form-label">Current Password <span class="required">*</span></label>
          <input
            class="form-input"
            :class="{ 'is-error': fieldError('current_password') }"
            type="password"
            v-model="pwForm.current_password"
            @blur="v$.current_password.$touch()"
            placeholder="Your current password"
          />
          <div v-if="fieldError('current_password')" class="form-error">{{ fieldError('current_password') }}</div>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">New Password <span class="required">*</span></label>
            <input
              class="form-input"
              :class="{ 'is-error': fieldError('password') }"
              type="password"
              v-model="pwForm.password"
              @blur="v$.password.$touch()"
              placeholder="Min 8 characters…"
            />
            <div v-if="fieldError('password')" class="form-error">{{ fieldError('password') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password <span class="required">*</span></label>
            <input
              class="form-input"
              :class="{ 'is-error': fieldError('password_confirmation') }"
              type="password"
              v-model="pwForm.password_confirmation"
              @blur="v$.password_confirmation.$touch()"
              placeholder="Repeat new password…"
            />
            <div v-if="fieldError('password_confirmation')" class="form-error">{{ fieldError('password_confirmation') }}</div>
          </div>
        </div>
        <div style="font-size:12px;color:var(--text-3);margin-bottom:14px">Password must be 8+ characters.</div>
        <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
          <button type="button" class="btn btn-outline" @click="resetPwForm">Cancel</button>
          <button type="button" class="btn btn-primary" :disabled="pwForm.processing" @click="savePassword">
            <AegisIcon name="lock" :size="14" /> Update Password
          </button>
        </div>
      </div>
    </div>

    <div class="card" style="margin-top:16px">
      <div class="card-header">
        <div class="card-title-group">
          <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="monitor" :size="16" /></div>
          <div><div class="card-title">Active Sessions</div><div class="card-subtitle">Devices currently logged into your account</div></div>
        </div>
        <button type="button" class="btn btn-danger btn-sm" @click="modals.revokeAll = true">
          <AegisIcon name="x" :size="12" /> Revoke All
        </button>
      </div>
      <div class="card-body">
        <div v-if="!sessions || sessions.length === 0" class="form-hint" style="padding:12px 0;display:flex;align-items:center;gap:8px;color:var(--text-3)">
          <AegisIcon name="monitor" :size="16" />
          No other active sessions. You are only logged in on this device.
        </div>
        <div v-for="(sess, idx) in sessions" :key="sess.id" class="session-item">
          <div class="session-icon">
            <AegisIcon :name="sess.device && sess.device.toLowerCase().includes('iphone') || (sess.device && sess.device.toLowerCase().includes('android')) ? 'phone' : 'monitor'" :size="20" />
          </div>
          <div class="session-info">
            <div class="session-device">
              {{ sess.device || 'Unknown device' }}
              <span v-if="idx === 0" style="display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:700;background:var(--green-light);color:var(--green-dark);padding:1px 7px;border-radius:var(--radius-full);margin-left:6px;vertical-align:middle">
                <AegisIcon name="check" :size="9" /> Current
              </span>
            </div>
            <div class="session-meta">
              <span v-if="sess.ip">{{ sess.ip }} · </span>Last active {{ sess.last_seen_at || 'recently' }}
            </div>
          </div>
          <button
            v-if="idx !== 0"
            type="button"
            class="btn-icon btn-icon-danger"
            data-tooltip="Revoke session"
            @click="revokeOne(sess.id)"
          >
            <AegisIcon name="x" :size="14" />
          </button>
          <span v-else style="width:28px;flex-shrink:0"></span>
        </div>
      </div>
    </div>

    <AegisModal v-model="modals.revokeAll" title="Revoke All Sessions" size="sm">
      <p style="font-size:14px;color:var(--text-2)">This will sign out all other devices. You'll stay logged in on this device.</p>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="modals.revokeAll = false">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" @click="revokeAll">Revoke All</button>
      </template>
    </AegisModal>
  </div>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { useForm, router }    from '@inertiajs/vue3';
import { useVuelidate }        from '@vuelidate/core';
import { required, minLength, sameAs, helpers } from '@vuelidate/validators';
import { useToast }            from '@/composables/useToast';

const props = defineProps({
  user:                { type: Object,  default: () => ({}) },
  sessions:            { type: Array,   default: () => [] },
  updatePasswordRoute: { type: String,  required: true },
  revokeAllRoute:      { type: String,  required: true },
  revokeSessionRoute:  { type: String,  required: true },
});

const toast  = useToast();
const modals = reactive({ revokeAll: false });

// ─── Password form — field names MUST match PasswordResetController::change()
const pwForm = useForm({
  current_password:      '',
  password:              '',
  password_confirmation: '',
});

// ─── Vuelidate rules (mirrors server FormRequest)
const rules = computed(() => ({
  current_password: {
    required: helpers.withMessage('Current password is required.', required),
  },
  password: {
    required:  helpers.withMessage('New password is required.', required),
    minLength: helpers.withMessage('Password must be at least 8 characters.', minLength(8)),
  },
  password_confirmation: {
    required: helpers.withMessage('Please confirm your new password.', required),
    sameAs:   helpers.withMessage('Passwords do not match.', sameAs(computed(() => pwForm.password))),
  },
}));

const v$ = useVuelidate(rules, pwForm);

// fieldError: Vuelidate wins, Inertia server error as fallback
function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message;
  if (pwForm.errors[field])   return pwForm.errors[field];
  return null;
}

async function savePassword() {
  const valid = await v$.value.$validate();
  if (!valid) { toast.error('Please fix the highlighted fields.'); return; }

  pwForm.put(route(props.updatePasswordRoute), {
    preserveScroll: true,
    onSuccess: () => { toast.success('Password updated.'); resetPwForm(); },
    onError:   () => toast.error('Could not update password. Please try again.'),
    onFinish:  () => v$.value.$reset(),
  });
}

function resetPwForm() {
  pwForm.reset();
  v$.value.$reset();
}

function revokeOne(sessionId) {
  router.delete(route(props.revokeSessionRoute, { session: sessionId }), {
    preserveScroll: true,
    onSuccess: () => toast.success('Session revoked.'),
    onError:   () => toast.error('Could not revoke session.'),
  });
}

function revokeAll() {
  modals.revokeAll = false;
  router.delete(route(props.revokeAllRoute), {
    preserveScroll: true,
    onSuccess: () => toast.success('All other sessions revoked.'),
    onError:   () => toast.error('Could not revoke sessions.'),
  });
}
</script>
