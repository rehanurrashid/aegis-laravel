<template>
  <div class="card">
    <div class="card-header">
      <div class="card-title-group">
        <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="settings" :size="16" /></div>
        <div><div class="card-title">Appearance</div><div class="card-subtitle">Unified across all portals — applies everywhere on your account.</div></div>
      </div>
    </div>
    <div class="card-body">
      <div class="section-label">Color Theme</div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px">
        <div
          v-for="th in themes" :key="th.key"
          @click="setTheme(th.key)"
          style="cursor:pointer;border-radius:var(--radius);overflow:hidden;transition:border-color var(--transition)"
          :style="{ border: (!form.darkMode && form.theme === th.key) ? '1px solid var(--gold-dark)' : '1px solid var(--border)' }"
        >
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
        <button type="button" class="toggle" :class="{ on: form.darkMode }" @click="setDarkMode(!form.darkMode)" :aria-pressed="form.darkMode"></button>
      </div>
      <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
        <button type="button" class="btn btn-primary btn-sm" :disabled="form.processing" @click="save">
          <AegisIcon name="check" :size="13" /> Save Appearance
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  updateRoute: { type: String,  required: true },
  meta:        { type: Object,  default: () => ({}) },
});

const toast = useToast();

const saved = props.meta?.appearance ?? {};

const form = useForm({
  theme:    saved.theme     ?? 'gold',
  darkMode: saved.dark_mode ?? false,
  timezone: saved.timezone  ?? 'America/New_York',
});

const themes = [
  { key: 'gold',      label: 'Aegis Gold',  desc: 'Classic warm gold (default)', swatch: 'linear-gradient(135deg,#a0813e 0%,#c4a96a 100%)' },
  { key: 'gold-dark', label: 'Gold Dark',   desc: 'Deep rich gold palette',       swatch: 'linear-gradient(135deg,#8c6a1e 0%,#b8922e 100%)' },
  { key: 'slate',     label: 'Slate Blue',  desc: 'Cool professional slate tone',  swatch: 'linear-gradient(135deg,#2a6a9a 0%,#4a90c4 100%)' },
];

// Theme class map: setting key → body CSS class
const THEME_CLASSES = {
  'gold':      [],
  'gold-dark': ['theme-gold-dark'],
  'slate':     ['theme-slate'],
};
const ALL_THEME_CLASSES = ['theme-gold-dark', 'theme-gold-deep', 'theme-slate'];

function applyToBody(theme, darkMode) {
  // Apply to both <html> (for blade script) and <body> (for theme CSS selectors)
  [document.documentElement, document.body].forEach(el => {
    el.classList.remove(...ALL_THEME_CLASSES, 'theme-dark');
    const cls = THEME_CLASSES[theme] ?? [];
    cls.forEach(c => el.classList.add(c));
    if (darkMode) el.classList.add('theme-dark');
  });
  // Sync localStorage — app.blade.php reads this on hard reload
  try {
    localStorage.setItem('aegis_appearance', JSON.stringify({ theme, darkMode }));
  } catch (e) {}
}

function setTheme(key) {
  form.theme = key;
  form.darkMode = false; // selecting a color theme turns off dark mode
  applyToBody(key, false);
}

function setDarkMode(val) {
  form.darkMode = val;
  if (val) form.theme = 'gold'; // dark mode clears theme selection back to default
  applyToBody(form.theme, val);
}

function save() {
  form.put(route(props.updateRoute), {
    preserveScroll: true,
    onSuccess: () => toast.success('Appearance settings saved.'),
    onError:   () => toast.error('Could not save appearance.'),
  });
}
</script>
