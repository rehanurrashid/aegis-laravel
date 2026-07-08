<template>
  <div class="card">
    <div class="card-header">
      <div class="card-title-group">
        <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="bell" :size="16" /></div>
        <div><div class="card-title">Notification Preferences</div><div class="card-subtitle">{{ subtitle }}</div></div>
      </div>
      <div style="display:flex;gap:8px">
        <button type="button" class="btn btn-ghost btn-sm" @click="setAll(false)">Mute All</button>
        <button type="button" class="btn btn-outline btn-sm" @click="setAll(true)">Enable All</button>
      </div>
    </div>
    <div class="card-body">
      <div style="margin-bottom:16px">
        <div class="form-row form-row-4">
          <div class="form-group">
            <label class="form-label">Quiet Hours From</label>
            <input class="form-input" type="time" v-model="prefs.quietFrom" />
          </div>
          <div class="form-group">
            <label class="form-label">Quiet Hours To</label>
            <input class="form-input" type="time" v-model="prefs.quietTo" />
          </div>
          <div class="form-group">
            <label class="form-label">Notification Digest</label>
            <select class="form-select" v-model="prefs.digest">
              <option value="realtime">Real-time</option>
              <option value="hourly">Hourly</option>
              <option value="daily">Daily (8 AM)</option>
              <option value="weekly">Weekly</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Event Reminder Lead Time</label>
            <select class="form-select" v-model="prefs.reminderLead">
              <option value="none">None</option>
              <option value="1day">1 day before</option>
              <option value="1hour">1 hour before</option>
              <option value="both">1 day &amp; 1 hour before</option>
            </select>
          </div>
        </div>
      </div>

      <table class="notif-table">
        <thead>
          <tr>
            <th>Category</th>
            <th><span class="th-icon"><AegisIcon name="phone" :size="13" /> Push</span></th>
            <th><span class="th-icon"><AegisIcon name="mail" :size="13" /> Email</span></th>
            <th><span class="th-icon"><AegisIcon name="message-square" :size="13" /> SMS</span></th>
            <th><span class="th-icon"><AegisIcon name="bell" :size="13" /> In-App</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="cat in localCategories" :key="cat.key">
            <td><div class="notif-cat-label">{{ cat.label }}</div><div class="notif-cat-desc">{{ cat.desc }}</div></td>
            <td><button type="button" class="toggle" :class="{ on: cat.push }"  @click="cat.push  = !cat.push"  :aria-pressed="cat.push"></button></td>
            <td><button type="button" class="toggle" :class="{ on: cat.email }" @click="cat.email = !cat.email" :aria-pressed="cat.email"></button></td>
            <td><button type="button" class="toggle" :class="{ on: cat.sms }"   @click="cat.sms   = !cat.sms"   :aria-pressed="cat.sms"></button></td>
            <td><button type="button" class="toggle" :class="{ on: cat.inapp }" @click="cat.inapp = !cat.inapp" :aria-pressed="cat.inapp"></button></td>
          </tr>
        </tbody>
      </table>

      <!-- Extra portal-specific toggles (steward alerts etc.) -->
      <slot name="extra-toggles" />

      <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
        <button type="button" class="btn btn-primary" :disabled="saving" @click="save">
          <AegisIcon name="check" :size="14" /> Save Preferences
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  updateRoute:     { type: String,  required: true },
  subtitle:        { type: String,  default: 'Delivery channels are unified across all portals.' },
  notifCategories: { type: Array,   default: () => [] },
  // Pre-populated from meta
  savedPrefs:      { type: Object,  default: () => ({}) },
  savedCategories: { type: Array,   default: () => [] },
});

const toast  = useToast();
const saving = ref(false);

const prefs = reactive({
  quietFrom:    props.savedPrefs?.quietFrom   ?? '22:00',
  quietTo:      props.savedPrefs?.quietTo     ?? '08:00',
  digest:       props.savedPrefs?.digest      ?? 'daily',
  reminderLead: props.savedPrefs?.reminderLead ?? '1day',
});

// Merge saved category states into the portal-defined list
const localCategories = reactive(
  props.notifCategories.map(c => {
    const saved = props.savedCategories?.find(s => s.key === c.key);
    return saved ? { ...c, ...saved } : { ...c };
  })
);

function setAll(val) {
  localCategories.forEach(c => { c.push = val; c.email = val; c.sms = val; c.inapp = val; });
}

function save() {
  saving.value = true;
  router.put(route(props.updateRoute), {
    prefs:      prefs,
    categories: localCategories,
  }, {
    preserveScroll: true,
    onSuccess: () => { saving.value = false; toast.success('Notification preferences saved!'); },
    onError:   () => { saving.value = false; toast.error('Could not save preferences.'); },
    onFinish:  () => { saving.value = false; },
  });
}
</script>
