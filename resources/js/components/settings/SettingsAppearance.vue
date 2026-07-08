<template>
  <div class="card">
    <div class="card-header">
      <div class="card-title-group">
        <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="settings" :size="16" /></div>
        <div><div class="card-title">Appearance &amp; Timezone</div><div class="card-subtitle">Unified across all portals — theme and timezone apply everywhere on your account.</div></div>
      </div>
    </div>
    <div class="card-body">
      <div class="section-label">Color Theme</div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px">
        <div v-for="th in themes" :key="th.key" @click="appearance.theme = th.key"
             style="cursor:pointer;border-radius:var(--radius);overflow:hidden;transition:border-color var(--transition)"
             :style="{ border: appearance.theme === th.key ? '1px solid var(--gold-dark)' : '1px solid var(--border)' }">
          <div style="height:48px" :style="{ background: th.swatch }"></div>
          <div style="padding:8px 10px;background:var(--surface)">
            <div style="font-size:12px;font-weight:700;color:var(--text)">{{ th.label }}</div>
            <div style="font-size:11px;color:var(--text-3);margin-top:1px">{{ th.desc }}</div>
          </div>
        </div>
      </div>
      <div class="section-label">Display</div>
      <div class="toggle-row">
        <div class="toggle-info"><div class="toggle-label">Dark Mode</div><div class="toggle-desc">Switch to dark surfaces across all portals</div></div>
        <button type="button" class="toggle" :class="{ on: appearance.darkMode }" @click="appearance.darkMode = !appearance.darkMode" :aria-pressed="appearance.darkMode"></button>
      </div>
      <div class="section-label" style="margin-top:20px">Timezone</div>
      <div class="form-group">
        <select class="form-select" v-model="appearance.timezone">
          <option value="America/New_York">Eastern Time (ET)</option>
          <option value="America/Chicago">Central Time (CT)</option>
          <option value="America/Denver">Mountain Time (MT)</option>
          <option value="America/Los_Angeles">Pacific Time (PT)</option>
          <option value="America/Phoenix">Arizona (no DST)</option>
          <option value="Pacific/Honolulu">Hawaii (HST)</option>
          <option value="America/Anchorage">Alaska (AKST)</option>
          <option value="UTC">UTC</option>
        </select>
      </div>
      <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
        <button type="button" class="btn btn-primary" @click="save"><AegisIcon name="check" :size="13" /> Save Appearance</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  updateRoute: { type: String, required: true },
  meta:        { type: Object, default: () => ({}) },
});

const toast = useToast();

const appearance = reactive({
  theme:    props.meta?.theme    || 'gold',
  darkMode: props.meta?.darkMode || false,
  timezone: props.meta?.timezone || 'America/New_York',
});

const themes = [
  { key: 'gold',      label: 'Aegis Gold',  desc: 'Classic warm gold (default)', swatch: 'linear-gradient(135deg,#a0813e 0%,#c4a96a 100%)' },
  { key: 'gold-dark', label: 'Gold Dark',   desc: 'Deep rich gold palette',       swatch: 'linear-gradient(135deg,#6e4e14 0%,#8c6a1e 100%)' },
  { key: 'slate',     label: 'Slate Blue',  desc: 'Cool professional slate tone',  swatch: 'linear-gradient(135deg,#3b5278 0%,#5a7ab5 100%)' },
];

function save() {
  router.put(route(props.updateRoute), appearance, {
    preserveScroll: true,
    onSuccess: () => toast.success('Appearance settings saved.'),
    onError:   () => toast.error('Could not save appearance.'),
  });
}
</script>
