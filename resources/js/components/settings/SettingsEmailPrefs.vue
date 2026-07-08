<template>
  <div class="card">
    <div class="card-header">
      <div class="card-title-group">
        <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="mail" :size="16" /></div>
        <div><div class="card-title">Email Preferences</div><div class="card-subtitle">Manage your digest frequency and optional email updates</div></div>
      </div>
    </div>
    <div class="card-body">
      <div class="form-group" style="margin-bottom:4px">
        <label class="form-label">Digest Frequency</label>
        <select class="form-select" v-model="form.digestFreq">
          <option value="daily">Daily</option>
          <option value="weekly">Weekly</option>
          <option value="monthly">Monthly</option>
          <option value="never">Never</option>
        </select>
      </div>
      <div class="toggle-row">
        <div class="toggle-info"><div class="toggle-label">Weekly Platform Digest</div><div class="toggle-desc">Summary of your activity and Aegis updates</div></div>
        <button type="button" class="toggle" :class="{ on: form.digest }" @click="form.digest = !form.digest" :aria-pressed="form.digest"></button>
      </div>
      <div class="toggle-row">
        <div class="toggle-info"><div class="toggle-label">{{ activityLabel }}</div><div class="toggle-desc">{{ activityDesc }}</div></div>
        <button type="button" class="toggle" :class="{ on: form.activityDigest }" @click="form.activityDigest = !form.activityDigest" :aria-pressed="form.activityDigest"></button>
      </div>
      <div class="toggle-row">
        <div class="toggle-info"><div class="toggle-label">Product Updates</div><div class="toggle-desc">New Aegis features, improvements, and release notes</div></div>
        <button type="button" class="toggle" :class="{ on: form.productUpdates }" @click="form.productUpdates = !form.productUpdates" :aria-pressed="form.productUpdates"></button>
      </div>
      <div class="toggle-row">
        <div class="toggle-info"><div class="toggle-label">Unsubscribe from All Optional Emails</div><div class="toggle-desc">Only transactional and security emails will be sent</div></div>
        <button type="button" class="toggle" :class="{ on: form.unsubAll }" @click="form.unsubAll = !form.unsubAll" :aria-pressed="form.unsubAll"></button>
      </div>
      <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
        <button type="button" class="btn btn-primary" :disabled="form.processing" @click="save">
          <AegisIcon name="check" :size="13" /> Save
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  updateRoute:    { type: String, required: true },
  activityLabel:  { type: String, default: 'Activity Summary' },
  activityDesc:   { type: String, default: 'Digest of your platform activity' },
  meta:           { type: Object, default: () => ({}) },
});

const toast = useToast();
const saved = props.meta?.email_prefs ?? {};

const form = useForm({
  digestFreq:     saved.digestFreq     ?? 'weekly',
  digest:         saved.digest         ?? true,
  activityDigest: saved.activityDigest ?? true,
  productUpdates: saved.productUpdates ?? false,
  unsubAll:       saved.unsubAll       ?? false,
});

function save() {
  form.put(route(props.updateRoute), {
    preserveScroll: true,
    onSuccess: () => toast.success('Email preferences saved.'),
    onError:   () => toast.error('Could not save email preferences.'),
  });
}
</script>
