<template>
  <div class="card">
    <div class="card-header">
      <div class="card-title-group">
        <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="bell" :size="16" /></div>
        <div><div class="card-title">Notification Preferences</div><div class="card-subtitle">{{ subtitle }}</div></div>
      </div>
      <div style="display:inline-flex;align-items:center;gap:8px">
        <button type="button" class="btn btn-ghost btn-sm" @click="setAll(false)">Mute All</button>
        <button type="button" class="btn btn-outline btn-sm" @click="setAll(true)">Enable All</button>
      </div>
    </div>
    <div class="card-body" style="padding:0">

      <!-- Category matrix -->
      <div class="notif-matrix">
        <!-- Header row -->
        <div class="notif-matrix-header">
          <div class="notif-col-label"></div>
          <div class="notif-col-channel">
            <span class="notif-ch-icon"><AegisIcon name="phone" :size="13" /></span>
            <span class="notif-ch-text">Push</span>
          </div>
          <div class="notif-col-channel">
            <span class="notif-ch-icon"><AegisIcon name="mail" :size="13" /></span>
            <span class="notif-ch-text">Email</span>
          </div>
          <div class="notif-col-channel">
            <span class="notif-ch-icon"><AegisIcon name="bell" :size="13" /></span>
            <span class="notif-ch-text">In-App</span>
          </div>
        </div>

        <!-- Category rows -->
        <div
          v-for="(cat, idx) in localCategories"
          :key="cat.key"
          class="notif-matrix-row"
          :class="{ 'notif-row-alt': idx % 2 === 1 }"
        >
          <div class="notif-col-label">
            <div class="notif-cat-name">{{ cat.label }}</div>
            <div class="notif-cat-desc">{{ cat.desc }}</div>
          </div>
          <div class="notif-col-channel">
            <button type="button" class="notif-toggle" :class="{ 'is-on': cat.push }" :aria-pressed="cat.push" @click="cat.push = !cat.push">
              <span class="notif-toggle-knob"></span>
            </button>
          </div>
          <div class="notif-col-channel">
            <button type="button" class="notif-toggle" :class="{ 'is-on': cat.email }" :aria-pressed="cat.email" @click="cat.email = !cat.email">
              <span class="notif-toggle-knob"></span>
            </button>
          </div>
          <div class="notif-col-channel">
            <button type="button" class="notif-toggle" :class="{ 'is-on': cat.inapp }" :aria-pressed="cat.inapp" @click="cat.inapp = !cat.inapp">
              <span class="notif-toggle-knob"></span>
            </button>
          </div>
        </div>
      </div>

      <!-- Extra portal-specific toggles slot -->
      <slot name="extra-toggles" />

      <div style="padding:20px 24px;display:flex;justify-content:flex-end;border-top:1px solid var(--border)">
        <button type="button" class="btn btn-primary btn-sm" :disabled="saving" @click="save">
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
  savedPrefs:      { type: Object,  default: () => ({}) },
  savedCategories: { type: Array,   default: () => [] },
});

const toast  = useToast();
const saving = ref(false);

// Merge saved channel states into portal-defined category list
const localCategories = reactive(
  props.notifCategories.map(c => {
    const saved = props.savedCategories?.find(s => s.key === c.key);
    return saved ? { ...c, ...saved } : { ...c };
  })
);

function setAll(val) {
  localCategories.forEach(c => { c.push = val; c.email = val; c.inapp = val; });
}

function save() {
  saving.value = true;
  router.put(route(props.updateRoute), {
    categories: localCategories.map(c => ({
      key:   c.key,
      push:  c.push,
      email: c.email,
      inapp: c.inapp,
    })),
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Notification preferences saved!'); },
    onError:   () => { toast.error('Could not save preferences.'); },
    onFinish:  () => { saving.value = false; },
  });
}
</script>

<style scoped>
.notif-matrix { width: 100%; }

.notif-matrix-header,
.notif-matrix-row {
  display: grid;
  grid-template-columns: 1fr 96px 96px 96px;
  align-items: center;
}

.notif-matrix-header {
  padding: 10px 24px;
  border-bottom: 1px solid var(--border);
  background: var(--surface-secondary);
}

.notif-matrix-row {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  transition: background 0.15s;
}
.notif-matrix-row:last-child { border-bottom: none; }
.notif-row-alt { background: var(--surface-secondary); }
.notif-matrix-row:hover { background: var(--surface-hover); }

.notif-col-label { padding-right: 24px; }

.notif-col-channel {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.notif-ch-icon {
  display: inline-flex;
  align-items: center;
  color: var(--text-secondary);
}
.notif-ch-text {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--text-secondary);
}

.notif-cat-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.3;
}
.notif-cat-desc {
  font-size: 12px;
  color: var(--text-secondary);
  margin-top: 3px;
  line-height: 1.4;
}

.notif-toggle {
  position: relative;
  width: 36px;
  height: 20px;
  border-radius: 10px;
  border: none;
  background: var(--border);
  cursor: pointer;
  transition: background 0.2s;
  padding: 0;
  flex-shrink: 0;
}
.notif-toggle.is-on { background: var(--gold); }
.notif-toggle-knob {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 3px rgba(0,0,0,0.18);
  transition: left 0.2s;
}
.notif-toggle.is-on .notif-toggle-knob { left: 18px; }
</style>
