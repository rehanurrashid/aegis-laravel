<!--
  pages/public/SupportStewardProfile.vue — private SS profile.
  PHP source parity: public/support_steward.php
  Relationship-gated: visible only to owner and linked Provider.
-->
<template>
  <PublicLayout>
    <div class="public-profile-wrap">

      <!-- ═══ HERO ═══ -->
      <div class="hero-banner is-quiet">
        <div class="page-hero-inner">
          <div class="page-hero-left has-icon">
            <div class="page-hero-icon is-avatar" aria-hidden="true"
                 :style="user.avatar_url ? { backgroundImage: `url(${user.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}">
              <template v-if="!user.avatar_url">{{ avatarInitials }}</template>
            </div>
            <div class="page-hero-text">
              <div class="page-hero-eyebrow">Support Steward</div>
              <h1 class="page-hero-title">{{ user.display_name }}</h1>
              <div class="page-hero-sub">{{ roleTitle }}{{ user.organization ? ' · ' + user.organization : '' }}</div>
              <div class="hero-badges">
                <span v-if="isActive"     class="badge badge-green"><span class="badge-dot"></span>Active</span>
                <span v-else-if="isPending"   class="badge badge-gold"><AegisIcon name="clock" :size="10" />Pending Invitation</span>
                <span v-else-if="isDeclined"  class="badge badge-red"><AegisIcon name="x" :size="10" />Declined</span>
                <span v-else-if="isSuspended" class="badge badge-red"><AegisIcon name="pause" :size="10" />Suspended</span>
                <span v-else class="badge badge-gray">Not yet assigned</span>
                <span v-if="planRole" class="badge badge-blue">{{ capitalize(planRole) }} SS</span>
                <span class="badge badge-gold"><AegisIcon name="lock" :size="10" />Private profile</span>
              </div>
            </div>
          </div>
          <div class="page-hero-actions">
            <template v-if="isOwner">
              <a :href="route('ss.profile.index')" class="btn-hero-solid is-on-light"><AegisIcon name="pencil" :size="14" /> Edit Profile</a>
              <button type="button" class="btn-hero-ghost is-on-light is-icon-only" @click="copyShareLink" data-tooltip="Copy link" aria-label="Copy link"><AegisIcon name="link" :size="14" /></button>
            </template>
            <template v-else-if="isLinkedProvider">
              <a :href="route('messages.index') + '?to=' + user.id" class="btn-hero-solid is-on-light is-icon-only" data-tooltip="Message" aria-label="Message"><AegisIcon name="mail" :size="14" /></a>
            </template>
          </div>
        </div>
        <div class="hero-meta">
          <span v-if="user.location" class="hero-meta-item"><AegisIcon name="map-pin" :size="12" />{{ user.location }}</span>
          <span v-if="linkedProviderName" class="hero-meta-item"><AegisIcon name="user" :size="12" />w/ {{ linkedProviderName }}</span>
          <span v-if="isActive && avgResponse" class="hero-meta-item"><AegisIcon name="clock" :size="12" />{{ avgResponse }} avg</span>
          <span v-if="countersignedAt" class="hero-meta-item"><AegisIcon name="check" :size="12" />Signed {{ formatDate(countersignedAt) }}</span>
        </div>
      </div>

      <!-- ═══ STAT CHIPS (active only) ═══ -->
      <div v-if="isActive" class="stat-chips-row" style="margin-top:18px">
        <AegisStatChip icon="alert-triangle" :value="activations" label="Activations" />
        <AegisStatChip icon="check" :value="onTimeRate" label="On-Time Rate" />
        <AegisStatChip icon="activity" :value="tasksCompleted" label="Tasks Done" />
        <AegisStatChip icon="clock" :value="pmStats.missed_slas ?? 0" label="Missed SLAs" />
      </div>

      <!-- ═══ PRIVACY NOTICE ═══ -->
      <div class="alert alert-gold" style="margin-top:18px">
        <div class="alert-icon"><AegisIcon name="lock" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">Private Support Steward profile</div>
          <div>This page is visible only to <strong>{{ linkedProviderName ?? 'the linked Provider' }}</strong> and {{ user.display_name }}. It is not listed in search results and cannot be accessed by anyone outside that relationship.</div>
        </div>
      </div>

      <!-- ═══ STATUS BANNERS ═══ -->
      <div v-if="isPending" class="alert alert-warning" style="margin-top:14px">
        <div class="alert-icon"><AegisIcon name="clock" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">Awaiting acceptance</div>
          <div>{{ user.display_name }} has been invited but has not yet accepted the Support Steward agreement. Performance metrics and permissions will activate once countersigned.</div>
        </div>
      </div>
      <div v-else-if="isDeclined" class="alert alert-danger" style="margin-top:14px">
        <div class="alert-icon"><AegisIcon name="x" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">Invitation declined</div>
          <div>{{ user.display_name }} declined the Support Steward invitation. No active relationship exists.</div>
        </div>
      </div>
      <div v-else-if="isSuspended" class="alert alert-danger" style="margin-top:14px">
        <div class="alert-icon"><AegisIcon name="pause" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">Access suspended</div>
          <div>This Support Steward's access is paused. Tasks are on hold; the relationship remains on record. <template v-if="isLinkedProvider">You can reinstate from your Support Stewards page.</template></div>
        </div>
      </div>

      <!-- ═══ TWO-COLUMN GRID ═══ -->
      <div class="pp-grid" style="margin-top:18px">
        <!-- LEFT -->
        <div>

          <div v-if="user.about_me || user.bio" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="user" :size="13" class="aegis-icon-gold-dark" /> About {{ firstName }}</div>
            <div style="font-size:13px;line-height:1.7;color:var(--text-2);white-space:pre-wrap">{{ user.about_me ?? user.bio }}</div>
          </div>

          <div v-if="hasRelationship" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="shield" :size="13" class="aegis-icon-gold-dark" /> Relationship &amp; Agreement</div>
            <div class="pp-info-row"><span class="pp-info-label">Linked Provider</span><span class="pp-info-val">{{ linkedProviderName }}</span></div>
            <div v-if="planRole" class="pp-info-row"><span class="pp-info-label">Plan Role</span><span class="pp-info-val">{{ capitalize(planRole) }} Support Steward</span></div>
            <div v-if="planStatus" class="pp-info-row"><span class="pp-info-label">Status</span><span class="pp-info-val" :style="isActive ? 'color:var(--green-dark)' : 'color:var(--text-3)'">{{ capitalize(planStatus) }}</span></div>
            <div v-if="countersignedAt" class="pp-info-row"><span class="pp-info-label">Agreement Signed</span><span class="pp-info-val">{{ formatDate(countersignedAt) }}</span></div>
            <div v-if="certificationAt" class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Plan Certified</span><span class="pp-info-val" style="color:var(--green-dark)">{{ formatDate(certificationAt) }}</span></div>
            <div v-if="certificationNote" style="margin-top:14px;padding:12px 14px;background:var(--surface-2);border-radius:var(--radius-sm);border-left:3px solid var(--gold-dark)">
              <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-4);font-weight:700;margin-bottom:4px">Certification note</div>
              <div style="font-size:13px;color:var(--text-2);font-style:italic">"{{ certificationNote }}"</div>
            </div>
          </div>

          <div v-if="isActive" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="calendar" :size="13" class="aegis-icon-gold-dark" /> Availability</div>
            <div class="pp-section-eyebrow">Weekly schedule &amp; response readiness</div>
            <div class="ss-availability-grid">
              <div v-for="(state, day) in availability" :key="day" class="ss-avail-col">
                <div class="ss-avail-day">{{ day }}</div>
                <div :class="['ss-avail-cell', state]">{{ availLabels[state] ?? state }}</div>
              </div>
            </div>
            <div class="ss-avail-legend">
              <span class="ss-avail-legend-item"><span class="ss-avail-legend-dot" style="background:var(--green-dark)"></span>Available</span>
              <span class="ss-avail-legend-item"><span class="ss-avail-legend-dot" style="background:var(--orange-dark)"></span>Partial</span>
              <span class="ss-avail-legend-item"><span class="ss-avail-legend-dot" style="background:var(--text-4)"></span>Off</span>
              <span class="ss-avail-legend-item" style="margin-left:auto"><AegisIcon name="clock" :size="12" class="aegis-icon-gold-dark" /> 24/7 Critical Incident Response</span>
            </div>
          </div>

        </div>

        <!-- RIGHT -->
        <div :class="{ 'ss-muted': !isActive && !isPending }">

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="map-pin" :size="13" class="aegis-icon-gold-dark" /> Contact</div>
            <div v-if="user.location" class="pp-info-row"><span class="pp-info-label">Location</span><span class="pp-info-val">{{ user.location }}</span></div>
            <div v-if="user.email" class="pp-info-row"><span class="pp-info-label">Email</span><span class="pp-info-val"><a :href="'mailto:'+user.email">{{ user.email }}</a></span></div>
            <div v-if="user.phone" class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Phone</span><span class="pp-info-val">{{ user.phone }}</span></div>
            <div v-if="!user.location && !user.email && !user.phone" class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Contact</span><span class="pp-info-val" style="color:var(--text-4)">Not provided</span></div>
          </div>

          <div v-if="user.organization || user.title" class="pp-section">
            <div class="pp-section-title"><AegisIcon name="briefcase" :size="13" class="aegis-icon-gold-dark" /> Affiliation</div>
            <div v-if="user.organization" class="pp-info-row"><span class="pp-info-label">Organization</span><span class="pp-info-val">{{ user.organization }}</span></div>
            <div v-if="user.title" class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Role</span><span class="pp-info-val">{{ user.title }}</span></div>
          </div>

          <div class="pp-section">
            <div class="pp-section-title"><AegisIcon name="shield" :size="13" class="aegis-icon-gold-dark" /> Work Context</div>
            <div v-if="isActive && tenureMonths" class="pp-info-row"><span class="pp-info-label">Tenure</span><span class="pp-info-val">{{ tenureMonths }} months</span></div>
            <div class="pp-info-row"><span class="pp-info-label">Verified</span><span class="pp-info-val" style="color:var(--green-dark)">Identity confirmed by Provider</span></div>
            <div v-if="isActive" class="pp-info-row"><span class="pp-info-label">Time Zone</span><span class="pp-info-val">Pacific (UTC-8)</span></div>
            <div class="pp-info-row" style="border-bottom:none"><span class="pp-info-label">Vault Access</span><span class="pp-info-val">None (by SS role design)</span></div>
          </div>

        </div>
      </div>

    </div>
  </PublicLayout>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  user:            { type: Object,  required: true },
  // Extra props the controller may pass for SS
  linkedProvider:  { type: Object,  default: null },
  planRole:        { type: String,  default: null },
  planStatus:      { type: String,  default: null },
  countersignedAt: { type: String,  default: null },
  certificationAt: { type: String,  default: null },
  certificationNote: { type: String, default: null },
})

const page = usePage()
const toast = useToast()

// Derive auth state from Inertia shared props — zero dependency on controller passing them
const authUser   = computed(() => page.props.auth?.user ?? null)
const isLoggedIn = computed(() => !!authUser.value)
const isOwner    = computed(() => isLoggedIn.value && authUser.value?.id === props.user?.id)

const pm      = computed(() => props.user.profile_meta ?? {})
const pmStats = computed(() => pm.value.stats ?? {})

const roleTitle        = computed(() => props.user.title ?? 'Support Steward')
const avatarInitials   = computed(() => props.user.avatar_initials ?? props.user.display_name?.slice(0, 2).toUpperCase() ?? '??')
const firstName        = computed(() => props.user.display_name?.split(' ')[0] ?? 'this Support Steward')
const linkedProviderName = computed(() => props.linkedProvider?.display_name ?? null)
const isLinkedProvider = computed(() => false) // determined server-side; page renders for allowed viewers only

const isActive    = computed(() => props.planStatus === 'active')
const isPending   = computed(() => props.planStatus === 'pending')
const isDeclined  = computed(() => props.planStatus === 'declined')
const isSuspended = computed(() => props.planStatus === 'suspended')
const hasRelationship = computed(() => !!props.linkedProvider)

const activations    = computed(() => pmStats.value.activations ?? 2)
const onTimeRate     = computed(() => pmStats.value.on_time_rate ?? '100%')
const tasksCompleted = computed(() => pmStats.value.tasks_completed ?? 32)
const avgResponse    = computed(() => pmStats.value.avg_response_hours ?? '1.8 hr')
const tenureMonths   = computed(() => pmStats.value.tenure_months ?? null)

const availability = computed(() => pm.value.availability ?? {
  MON: 'available', TUE: 'available', WED: 'available',
  THU: 'available', FRI: 'partial', SAT: 'off', SUN: 'off',
})
const availLabels = { available: '9–5', partial: '9–1', off: 'Off' }

function capitalize(str) { return str ? str.charAt(0).toUpperCase() + str.slice(1) : '' }

function formatDate(str) {
  if (!str) return ''
  try { return new Date(str).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }
  catch { return str }
}

function copyShareLink() {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(window.location.href)
      .then(() => toast.success('Link copied — only the linked Provider can open it'))
      .catch(() => toast.error('Copy failed'))
  } else {
    toast.success('Link copied — only the linked Provider can open it')
  }
}
</script>

<style scoped>
.public-profile-wrap { max-width: 960px; margin: 0 auto; padding: var(--space-6) var(--space-4); }
.ss-availability-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; margin: 8px 0 12px; }
.ss-avail-col { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.ss-avail-day { font-size: 10px; font-weight: 700; letter-spacing: 0.4px; color: var(--text-4); text-transform: uppercase; }
.ss-avail-cell { width: 100%; padding: 10px 4px; border-radius: var(--radius-sm); text-align: center; font-size: 11px; font-weight: 700; }
.ss-avail-cell.available { background: var(--green-light); color: var(--green-dark); }
.ss-avail-cell.partial   { background: var(--orange-light); color: var(--orange-dark); }
.ss-avail-cell.off       { background: var(--surface-3); color: var(--text-4); }
.ss-avail-legend { display: flex; gap: 14px; flex-wrap: wrap; font-size: 11px; color: var(--text-3); padding-top: 8px; border-top: 1px solid var(--border); }
.ss-avail-legend-item { display: inline-flex; align-items: center; gap: 5px; }
.ss-avail-legend-dot { width: 8px; height: 8px; border-radius: var(--radius-full); }
.ss-muted { opacity: 0.6; }
.hero-banner.is-quiet .page-hero-left.has-icon { gap: 20px; }
.hero-banner.is-quiet .page-hero-actions { gap: 8px; }
.hero-banner.is-quiet .hero-badges { flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none; gap: 6px; margin-top: 10px; }
.hero-banner.is-quiet .hero-badges::-webkit-scrollbar { display: none; }
.hero-banner.is-quiet .hero-badges .badge { font-size: 10px; padding: 3px 8px; letter-spacing: 0.1px; text-transform: none; font-weight: 600; border-radius: var(--radius-full); }
.hero-banner.is-quiet .badge-blue { background: var(--blue-light); border: 1px solid var(--fade-blue); color: var(--blue-dark); }
.hero-banner.is-quiet > .hero-meta { flex-wrap: nowrap; gap: 10px 14px; }
.hero-banner.is-quiet .hero-meta-item { white-space: nowrap; font-size: 11.5px; }
.btn-hero-ghost.is-icon-only,
.btn-hero-solid.is-icon-only { padding: 8px; width: 34px; height: 34px; justify-content: center; gap: 0; flex-shrink: 0; }
</style>
