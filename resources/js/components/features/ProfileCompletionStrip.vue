<!--
  ProfileCompletionStrip.vue — globally reusable "Finish your profile" strip.

  Replaces _shared/profile_strip.php. Renders only when completion < 100%.
  Used across Provider, CS, SS, BP, and Admin dashboards above the overview
  card. The host page passes the percentage and edit URL — defaults are
  provided for graceful fallback.

  Usage:
    <ProfileCompletionStrip
      :pct="auth.user?.profile_completion ?? 78"
      :edit-href="route('provider.profile.index')"
    />

  Slot:
    @action — optional override for the right-side CTA button content.

  Rendering rules:
    - Returns nothing when pct >= 100 (mirrors PHP behavior)
    - Falls back to 78% when no pct provided (matches PHP default)
    - All styling driven by .profile-strip-* classes in _shared.css
-->
<template>
  <div v-if="pct < 100" class="profile-strip">
    <div class="profile-strip-icon">
      <AegisIcon :name="icon" :size="16" />
    </div>
    <div class="profile-strip-text">
      <div class="profile-strip-title">{{ title }}</div>
      <div class="profile-strip-sub">{{ subtitle }}</div>
    </div>
    <div class="profile-strip-bar">
      <div class="profile-strip-bar-fill" :style="{ width: pct + '%' }"></div>
    </div>
    <div class="profile-strip-pct">{{ pct }}%</div>
    <slot name="action">
      <Link
        v-if="editHref"
        :href="editHref"
        class="btn btn-primary btn-sm"
        style="flex-shrink:0;white-space:nowrap"
      >
        {{ ctaLabel }}
        <AegisIcon name="arrow-right-line" :size="12" />
      </Link>
    </slot>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  /** Profile completion percentage 0–100. Strip hides at 100. */
  pct:       { type: Number, default: 78 },
  /** Inertia route path for the CTA. If omitted, action slot is required. */
  editHref:  { type: String, default: '' },
  /** Override copy when needed (different roles, different prompts). */
  title:     { type: String, default: 'Finish your profile' },
  subtitle:  { type: String, default: 'Add credentials & availability to improve discovery' },
  ctaLabel:  { type: String, default: 'Complete' },
  icon:      { type: String, default: 'user' },
})
</script>
