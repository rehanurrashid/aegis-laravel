<template>
  <div>
    <!-- ── ACCOUNT & LOGIN ──────────────────────────────────────────── -->
    <div class="card">
      <div class="card-header">
        <div class="card-title-group">
          <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)">
            <AegisIcon name="user" :size="16" />
          </div>
          <div>
            <div class="card-title">Account &amp; Login</div>
            <div class="card-subtitle">Contact details and login credentials</div>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div class="alert alert-info" style="margin-bottom:18px">
          <div class="alert-icon"><AegisIcon name="users" :size="16" /></div>
          <div class="alert-content" style="font-size:12px">
            <strong>Unified credentials</strong> — Email, phone, and password are shared across all your Aegis portals.
          </div>
        </div>

        <!-- Email (read-only — change via support) -->
        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">Primary Email</label>
            <div class="email-field-wrap">
              <input class="form-input" type="email" :value="user?.email ?? ''" disabled />
              <div class="email-field-footer">
                <span class="email-verified-badge">
                  <AegisIcon name="check" :size="11" /> Verified
                </span>
                <span class="email-field-hint">To change, contact support.</span>
              </div>
            </div>
          </div>

          <!-- Phone (editable) -->
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <div class="email-field-wrap">
              <input
                class="form-input"
                :class="{ 'is-error': acctForm.errors.phone }"
                type="tel"
                v-model="acctForm.phone"
                placeholder="+1 (555) 000-0000"
              />
              <div class="email-field-footer">
                <span v-if="acctForm.errors.phone" class="form-error" style="margin:0">{{ acctForm.errors.phone }}</span>
                <span v-else class="email-field-hint">Used for SMS notifications and 2FA.</span>
              </div>
            </div>
          </div>
        </div>

        <div class="btn-group" style="justify-content:flex-end;margin-top:4px">
          <button
            type="button"
            class="btn btn-primary btn-sm"
            :disabled="acctForm.processing || !acctDirty"
            @click="saveAccount"
          >
            <AegisIcon name="check" :size="13" /> Save Contact Details
          </button>
        </div>

        <!-- ── CHANGE PASSWORD ──────────────────────────────────────── -->
        <div class="sa-section-divider">
          <span>Change Password</span>
        </div>

        <!-- Current password -->
        <div class="form-group">
          <label class="form-label">Current Password <span class="required">*</span></label>
          <div class="ob-password-wrap">
            <input
              class="form-input"
              :class="{ 'is-error': fieldError('current_password') }"
              :type="showPw.current ? 'text' : 'password'"
              v-model="pwForm.current_password"
              @blur="v$.current_password.$touch()"
              placeholder="Your current password"
              autocomplete="current-password"
            />
            <button type="button" class="ob-password-toggle" @click="showPw.current = !showPw.current">
              <AegisIcon :name="showPw.current ? 'eye-off' : 'eye'" :size="15" />
            </button>
          </div>
          <div v-if="fieldError('current_password')" class="form-error">{{ fieldError('current_password') }}</div>
        </div>

        <!-- New password -->
        <div class="form-group">
          <label class="form-label">New Password <span class="required">*</span></label>
          <div class="ob-password-wrap">
            <input
              class="form-input"
              :class="{ 'is-error': fieldError('password') }"
              :type="showPw.new ? 'text' : 'password'"
              v-model="pwForm.password"
              @input="checkPasswordStrength"
              @blur="v$.password.$touch()"
              placeholder="Create a strong password"
              autocomplete="new-password"
            />
            <button type="button" class="ob-password-toggle" @click="showPw.new = !showPw.new">
              <AegisIcon :name="showPw.new ? 'eye-off' : 'eye'" :size="15" />
            </button>
          </div>
          <div v-if="fieldError('password')" class="form-error">{{ fieldError('password') }}</div>
          <!-- Requirement pills — identical to Register.vue -->
          <div class="ob-password-reqs">
            <div class="ob-req-item" :class="{ valid: reqs.length, invalid: pwForm.password && !reqs.length }">
              <AegisIcon :name="reqs.length ? 'check-circle' : 'x-circle'" :size="11" />8+ characters
            </div>
            <div class="ob-req-item" :class="{ valid: reqs.uppercase, invalid: pwForm.password && !reqs.uppercase }">
              <AegisIcon :name="reqs.uppercase ? 'check-circle' : 'x-circle'" :size="11" />Uppercase
            </div>
            <div class="ob-req-item" :class="{ valid: reqs.number, invalid: pwForm.password && !reqs.number }">
              <AegisIcon :name="reqs.number ? 'check-circle' : 'x-circle'" :size="11" />Number
            </div>
            <div class="ob-req-item" :class="{ valid: reqs.special, invalid: pwForm.password && !reqs.special }">
              <AegisIcon :name="reqs.special ? 'check-circle' : 'x-circle'" :size="11" />Special char
            </div>
          </div>
        </div>

        <!-- Confirm password -->
        <div class="form-group">
          <label class="form-label">Confirm New Password <span class="required">*</span></label>
          <div class="ob-password-wrap">
            <input
              class="form-input"
              :class="{ 'is-error': fieldError('password_confirmation') }"
              :type="showPw.confirm ? 'text' : 'password'"
              v-model="pwForm.password_confirmation"
              @blur="v$.password_confirmation.$touch()"
              placeholder="Re-enter your password"
              autocomplete="new-password"
            />
            <button type="button" class="ob-password-toggle" @click="showPw.confirm = !showPw.confirm">
              <AegisIcon :name="showPw.confirm ? 'eye-off' : 'eye'" :size="15" />
            </button>
          </div>
          <div v-if="fieldError('password_confirmation')" class="form-error">{{ fieldError('password_confirmation') }}</div>
          <div v-else-if="pwForm.password && pwForm.password_confirmation && pwForm.password === pwForm.password_confirmation" class="form-hint" style="color:var(--green);display:inline-flex;align-items:center;gap:4px">
            <AegisIcon name="check" :size="12" /> Passwords match
          </div>
        </div>

        <div class="btn-group" style="justify-content:flex-end;margin-top:12px">
          <button type="button" class="btn btn-outline btn-sm" @click="resetPwForm">Cancel</button>
          <button
            type="button"
            class="btn btn-primary"
            :disabled="pwForm.processing"
            @click="savePassword"
          >
            <AegisIcon name="lock" :size="14" />
            {{ pwForm.processing ? 'Updating…' : 'Update Password' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ── ACTIVE SESSIONS ──────────────────────────────────────────── -->
    <div class="card" style="margin-top:16px">
      <div class="card-header">
        <div class="card-title-group">
          <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)">
            <AegisIcon name="monitor" :size="16" />
          </div>
          <div>
            <div class="card-title">Active Sessions</div>
            <div class="card-subtitle">Devices currently logged into your account</div>
          </div>
        </div>
        <button v-if="sessions.length > 1" type="button" class="btn btn-danger btn-sm" @click="modals.revokeAll = true">
          <AegisIcon name="x" :size="12" /> Revoke All Others
        </button>
      </div>
      <div class="card-body" style="padding-top:8px">
        <div v-if="!sessions || sessions.length === 0" class="sa-empty-sessions">
          <AegisIcon name="monitor" :size="18" />
          <span>No active sessions found. Your session data may still be loading.</span>
        </div>
        <div v-for="(sess, idx) in sessions" :key="sess.id" class="session-item">
          <div class="session-icon">
            <AegisIcon :name="isMobile(sess.device) ? 'phone' : 'monitor'" :size="18" />
          </div>
          <div class="session-info">
            <div class="session-device">
              {{ sess.device || 'Unknown device' }}
              <span v-if="idx === 0" class="session-current">
                <AegisIcon name="check" :size="9" /> Current
              </span>
            </div>
            <div class="session-meta">
              <span v-if="sess.ip">{{ sess.ip }} &middot; </span>
              Last active {{ sess.last_seen_at || 'recently' }}
            </div>
          </div>
          <button
            v-if="idx !== 0"
            type="button"
            class="btn-icon btn-icon-danger"
            data-tooltip="Revoke this session"
            @click="revokeOne(sess.id)"
          >
            <AegisIcon name="x" :size="14" />
          </button>
          <span v-else style="width:28px;flex-shrink:0"></span>
        </div>
      </div>
    </div>

    <!-- Revoke All Modal -->
    <AegisModal v-model="modals.revokeAll" title="Revoke All Other Sessions" size="sm">
      <p style="font-size:14px;color:var(--text-2);margin-bottom:4px">
        All devices <strong>except this one</strong> will be signed out immediately.
      </p>
      <p style="font-size:13px;color:var(--text-3)">You'll need to sign in again on those devices.</p>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="modals.revokeAll = false">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" @click="revokeAll">
          <AegisIcon name="x" :size="13" /> Revoke All Others
        </button>
      </template>
    </AegisModal>
  </div>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useVuelidate } from '@vuelidate/core';
import { required, minLength, sameAs, helpers } from '@vuelidate/validators';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  user:                 { type: Object,  default: () => ({}) },
  sessions:             { type: Array,   default: () => [] },
  updatePasswordRoute:  { type: String,  required: true },
  updateAccountRoute:   { type: String,  required: true },
  revokeAllRoute:       { type: String,  required: true },
  revokeSessionRoute:   { type: String,  required: true },
});

const toast  = useToast();
const modals = reactive({ revokeAll: false });

// ── Account form ───────────────────────────────────────────────────────────
const acctForm = useForm({
  phone:  props.user?.phone  ?? '',
});

const acctDirty = computed(() => acctForm.phone !== (props.user?.phone ?? ''));

function saveAccount() {
  acctForm.put(route(props.updateAccountRoute), {
    preserveScroll: true,
    onSuccess: () => toast.success('Contact details updated.'),
    onError:   () => toast.error('Could not save contact details.'),
  });
}

// ── Password form ─────────────────────────────────────────────────────────
const showPw = reactive({ current: false, new: false, confirm: false });

const pwForm = useForm({
  current_password:      '',
  password:              '',
  password_confirmation: '',
});

// ── Requirement pills — same logic as Register.vue ────────────────────────
const reqs = reactive({ length: false, uppercase: false, number: false, special: false });

function checkPasswordStrength() {
  const pw = pwForm.password;
  reqs.length    = pw.length >= 8;
  reqs.uppercase = /[A-Z]/.test(pw);
  reqs.number    = /[0-9]/.test(pw);
  reqs.special   = /[^A-Za-z0-9]/.test(pw);
}

// Vuelidate — mirrors PasswordResetController::change() rules
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
    onSuccess: () => {
      toast.success('Password updated successfully.');
      resetPwForm();
    },
    onError: (errors) => {
      if (errors.current_password) {
        toast.error('Current password is incorrect.');
      } else {
        toast.error('Could not update password. Please try again.');
      }
    },
    onFinish: () => v$.value.$reset(),
  });
}

function resetPwForm() {
  pwForm.reset();
  v$.value.$reset();
  showPw.current = false;
  showPw.new = false;
  showPw.confirm = false;
  reqs.length = false;
  reqs.uppercase = false;
  reqs.number = false;
  reqs.special = false;
}

// ── Sessions ─────────────────────────────────────────────────────────────
function isMobile(device) {
  if (!device) return false;
  return /iphone|android|ipad|mobile/i.test(device);
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

<style scoped>
.sa-section-divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 22px 0 18px;
  font-size: 13px;
  font-weight: 700;
  color: var(--text-2);
  text-transform: uppercase;
  letter-spacing: .6px;
}
.sa-section-divider::before,
.sa-section-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--border);
}

/* Email field */
.email-field-wrap { display: flex; flex-direction: column; gap: 0; }
.email-field-footer {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 5px;
  min-height: 22px; /* ensures both columns have same footer height */
}
.email-verified-badge {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-size: 11px;
  font-weight: 700;
  color: var(--green-dark);
  background: var(--green-light);
  padding: 2px 8px;
  border-radius: var(--radius-full);
  flex-shrink: 0;
}
.email-field-hint {
  font-size: 12px;
  color: var(--text-3);
  line-height: 1.4;
}

/* Password show/hide — matches Register.vue ob- classes */
.ob-password-wrap { position: relative; }
.ob-password-wrap .form-input { padding-right: 42px; }
.ob-password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  padding: 4px;
  cursor: pointer;
  color: var(--text-2);
  display: flex;
  align-items: center;
  transition: color var(--transition);
}
.ob-password-toggle:hover { color: var(--gold-dark); }

/* Requirement pills — matches Register.vue */
.ob-password-reqs { display: flex; flex-wrap: wrap; gap: 6px 14px; margin-top: 10px; }
.ob-req-item { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; color: var(--text-4); transition: color var(--transition); }
.ob-req-item.valid   { color: var(--green); }
.ob-req-item.invalid { color: var(--red); }


/* Sessions */
.sa-empty-sessions {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: var(--text-3);
  padding: 14px 0 6px;
}
</style>
