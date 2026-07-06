<!--
  EventDetailModal.vue — centralized event detail modal.
  Used by both Events.vue (full page) and News.vue (sidebar widget).

  Props:
    - modelValue:   Boolean  — v-model open/close
    - event:        Object   — the event object (NewsEvent shape)
    - isRegistered: Boolean  — whether current user is already registered

  Emits:
    - update:modelValue   — closes modal
    - register (event)    — parent handles RSVP
    - cancel   (event)    — parent handles cancellation

  Parent is responsible for RSVP logic and showing register/cancel confirm modals.
-->
<template>
  <AegisModal :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)"
              title="Event Details" size="lg">
    <template v-if="event">

      <!-- Eyebrow + title ─────────────────────────────────────────────── -->
      <div class="evt-detail-heading">
        <div class="evt-detail-eyebrow-row">
          <span class="evt-category" :class="evtCategory(event)">{{ evtCategoryLabel(event) }}</span>
          <span v-if="event.rsvp_url || event.is_external" class="evt-external-badge">
            <AegisIcon name="external-link" :size="11" /> External Event
          </span>
        </div>
        <div class="evt-detail-title">{{ event.title }}</div>
        <div v-if="event.organizer" class="evt-detail-organizer">
          <AegisIcon name="users" :size="13" /> {{ event.organizer }}
        </div>
      </div>

      <!-- Key meta chips ───────────────────────────────────────────────── -->
      <div class="evt-detail-meta">
        <div class="evt-detail-chip">
          <AegisIcon name="calendar" :size="13" />
          <strong>{{ fmtFullDate(event.starts_at) }}</strong>
        </div>
        <div v-if="event.ends_at" class="evt-detail-chip">
          <AegisIcon name="clock" :size="13" />
          <strong>Ends {{ fmtFullDate(event.ends_at) }}</strong>
        </div>
        <div v-if="event.location" class="evt-detail-chip">
          <AegisIcon :name="isVirtual(event) ? 'monitor' : 'map-pin'" :size="13" />
          <strong>{{ event.location }}</strong>
        </div>
        <div v-if="ceuCredits(event) > 0" class="evt-detail-chip is-ceu">
          <AegisIcon name="award" :size="13" />
          <strong>{{ fmtCeu(ceuCredits(event)) }} CEU Credit{{ ceuCredits(event) === 1 ? '' : 's' }}</strong>
        </div>
        <div class="evt-detail-chip" :class="event.is_free ? 'is-free' : 'is-paid'">
          <AegisIcon :name="event.is_free ? 'check-circle' : 'dollar'" :size="13" />
          <strong>{{ event.is_free ? 'Free' : '$' + ((event.price_cents ?? 0) / 100).toFixed(2) }}</strong>
        </div>
        <div v-if="attendeeCount(event) > 0" class="evt-detail-chip">
          <AegisIcon name="users" :size="13" />
          <strong>{{ attendeeCount(event) }} registered</strong>
        </div>
      </div>

      <!-- Description ─────────────────────────────────────────────────── -->
      <p v-if="event.description" class="evt-detail-desc">{{ event.description }}</p>

      <!-- External link ───────────────────────────────────────────────── -->
      <div v-if="externalUrl(event)" class="evt-detail-external">
        <AegisIcon name="external-link" :size="13" />
        <a :href="externalUrl(event)" target="_blank" rel="noopener">View on external site →</a>
        <span class="evt-detail-external-note">Registration handled on the organizer's platform.</span>
      </div>

      <!-- Registered banner (internal events only) ─────────────────────── -->
      <div v-if="!externalUrl(event) && isRegistered" class="evt-detail-registered-banner">
        <AegisIcon name="check-circle" :size="16" />
        You're registered for this event. A confirmation was sent to your email.
      </div>

    </template>

    <!-- Footer ──────────────────────────────────────────────────────────── -->
    <template #footer>
      <button class="btn btn-outline" @click="$emit('update:modelValue', false)">Close</button>
      <template v-if="event">
        <!-- External: link out -->
        <a v-if="externalUrl(event)" :href="externalUrl(event)" target="_blank" rel="noopener"
           class="btn btn-primary">
          <AegisIcon name="external-link" :size="13" /> Register on Site
        </a>
        <!-- Internal: already registered → cancel -->
        <button v-else-if="isRegistered" class="btn btn-outline"
                @click="$emit('update:modelValue', false); $emit('cancel', event)">
          <AegisIcon name="x" :size="13" /> Cancel Registration
        </button>
        <!-- Internal: not registered → register -->
        <button v-else class="btn btn-primary"
                @click="$emit('register', event); $emit('update:modelValue', false)">
          {{ event.is_free ? 'Register Free' : 'Register Now' }}
        </button>
      </template>
    </template>
  </AegisModal>
</template>

<script setup>
// ── Props / Emits ────────────────────────────────────────────────────────────
const props = defineProps({
  modelValue:   { type: Boolean, default: false },
  event:        { type: Object,  default: null },
  isRegistered: { type: Boolean, default: false },
})

defineEmits(['update:modelValue', 'register', 'cancel'])

// ── Category helpers ─────────────────────────────────────────────────────────
const CAT_MAP    = { webinar: 'webinar', conference: 'conference', training: 'training', networking: 'networking', workshop: 'workshop' }
const CAT_LABELS = { webinar: 'Webinar', conference: 'Conference', training: 'CEU Training', networking: 'Networking', workshop: 'Workshop' }

function evtCategory(ev) {
  if (!ev) return 'training'
  const c = (ev.category ?? '').toLowerCase()
  if (CAT_MAP[c]) return c
  const t = (ev.title ?? '').toLowerCase()
  if (t.includes('webinar') || t.includes('office hours')) return 'webinar'
  if (t.includes('workshop'))                               return 'workshop'
  if (t.includes('summit') || t.includes('conference'))    return 'conference'
  if (t.includes('networking') || t.includes('meetup'))    return 'networking'
  return 'training'
}
function evtCategoryLabel(ev) { return CAT_LABELS[evtCategory(ev)] ?? 'Event' }

// ── Field accessors (handles both Events.vue and News.vue shapes) ─────────────
function externalUrl(ev)    { return ev?.rsvp_url || ev?.external_url || null }
function ceuCredits(ev)     { return parseFloat(ev?.ceu_credits ?? 0) }
function attendeeCount(ev)  { return ev?.attendee_count ?? 0 }
function isVirtual(ev) {
  const loc = (ev?.location ?? '').toLowerCase()
  return loc.includes('online') || loc.includes('virtual') || loc.includes('zoom')
}

// ── Format helpers ────────────────────────────────────────────────────────────
function fmtFullDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('en-US', {
    month: 'long', day: 'numeric', year: 'numeric',
    hour: 'numeric', minute: '2-digit',
  })
}
function fmtCeu(n) {
  if (n == null) return '0'
  const v = parseFloat(n)
  return v % 1 === 0 ? String(Math.round(v)) : String(parseFloat(v.toFixed(1)))
}
</script>

<style scoped>
/* ── Event detail heading ───────────────────────────────────────────── */
.evt-detail-heading  { margin-bottom: 14px; }
.evt-detail-eyebrow-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.evt-detail-eyebrow  { font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase; color: var(--text-4); margin-bottom: 6px; }
.evt-detail-title    { font-family: var(--font-serif); font-size: 19px; font-weight: 700; color: var(--text); line-height: 1.35; margin-top: 10px; }
.evt-detail-organizer {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 12px; color: var(--text-3); font-weight: 500; margin-top: 6px;
}

/* ── Category badge ─────────────────────────────────────────────────── */
.evt-category {
  display: inline-flex; align-items: center;
  font-family: var(--font-sans); font-size: 10px; font-weight: 700;
  letter-spacing: .5px; text-transform: uppercase;
  padding: 3px 10px; border-radius: var(--radius-full);
  background: var(--surface-3); color: var(--text-3);
}
.evt-category.webinar    { background: var(--blue-light);   color: var(--blue-dark); }
.evt-category.conference { background: var(--purple-light); color: var(--purple-dark); }
.evt-category.workshop   { background: var(--orange-light); color: var(--orange-dark); }
.evt-category.networking { background: var(--green-light);  color: var(--green-dark); }
.evt-category.training   { background: var(--teal-light);   color: var(--teal-dark); }

.evt-external-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
  padding: 3px 8px; border-radius: var(--radius-full);
  background: var(--blue-light); color: var(--blue-dark);
}

/* ── Meta chips ─────────────────────────────────────────────────────── */
.evt-detail-meta { display: flex; gap: 8px; flex-wrap: wrap; margin: 0 0 16px; }
.evt-detail-chip {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--surface-2); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 7px 12px;
  font-size: 12px; color: var(--text-2);
}
.evt-detail-chip strong { color: var(--text); font-weight: 700; }
.evt-detail-chip.is-ceu  { background: var(--badge-bg-gold); border-color: var(--gold); color: var(--gold-dark); }
.evt-detail-chip.is-ceu strong { color: var(--gold-dark); }
.evt-detail-chip.is-free { background: var(--green-light); border-color: var(--green); color: var(--green-dark); }
.evt-detail-chip.is-free strong { color: var(--green-dark); }
.evt-detail-chip.is-paid { background: var(--surface-2); color: var(--text-2); }

/* ── Description ────────────────────────────────────────────────────── */
.evt-detail-desc { font-size: 13px; color: var(--text-2); line-height: 1.65; margin: 0 0 16px; white-space: pre-line; }

/* ── External link ──────────────────────────────────────────────────── */
.evt-detail-external {
  display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
  font-size: 12px; color: var(--gold-dark); font-weight: 600;
  background: var(--badge-bg-gold); border: 1px solid var(--gold);
  border-radius: var(--radius); padding: 10px 14px; margin-bottom: 14px;
}
.evt-detail-external a { color: var(--gold-dark); text-decoration: none; font-weight: 700; }
.evt-detail-external a:hover { text-decoration: underline; }
.evt-detail-external-note { font-size: 11px; color: var(--text-3); font-weight: 400; }

/* ── Registered banner ──────────────────────────────────────────────── */
.evt-detail-registered-banner {
  display: flex; align-items: center; gap: 10px;
  background: var(--green-light); border: 1px solid var(--green);
  border-radius: var(--radius); padding: 12px 16px;
  font-size: 13px; font-weight: 600; color: var(--green-dark);
  margin-bottom: 4px;
}
</style>
