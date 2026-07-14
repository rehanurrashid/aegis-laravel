<!--
  pages/provider/ContinuityPlan.vue
  Master Continuity Plan HUB — 8-section checklist, incident grid,
  sign ceremony (Option B), vault attest, annual review.
  P1 + P2 complete. P3 fires from service layer.
-->
<template>
  <AppLayout :user="auth?.user" portal="practitioner" activePage="continuity-plan" pageTitle="Continuity Plan">

    <!-- HERO -->
    <AegisHeroBanner eyebrow="Continuity Planning" :title="'My Continuity Plan'"
      :subtitle="plan ? `Version ${plan.plan_version ?? 1} · ${planStatusLabel}` : 'Build your plan below'" quiet>
      <template #actions>
        <a :href="route('provider.activity') + '?module=plan'" class="btn-hero-ghost is-on-light">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
        <button v-if="plan && plan.signed_at" type="button" class="btn-hero-ghost is-on-light" @click="showAnnualReview = true">
          <AegisIcon name="refresh-cw" :size="14" /> Begin Annual Review
        </button>
        <button v-if="plan && !plan.vault_attested_at" type="button" class="btn-hero-ghost is-on-light" @click="showAttestModal = true">
          <AegisIcon name="check-circle" :size="14" /> Attest Vault
        </button>
        <button v-if="plan && !plan.signed_at" type="button"
          class="btn-hero-solid is-on-light"
          :data-tooltip="!canSign ? signBlockedReason : undefined"
          @click="canSign ? (showSignModal = true) : (showSectionsModal = true)">
          <AegisIcon name="edit" :size="14" /> Finalize &amp; Sign
        </button>
      </template>
    </AegisHeroBanner>

    <!-- STAT CHIPS -->
    <div class="stat-chips-row">
      <button type="button" class="stat-chip-btn" data-tooltip="View plan readiness" @click="showSectionsModal = true">
        <AegisStatChip
          icon="shield"
          :value="`${completedSections}/7`"
          label="Sections complete"
          :bg-color="completedSections === 7 ? 'var(--icon-bg-green)' : 'var(--icon-bg-gold)'"
          :icon-color="completedSections === 7 ? 'var(--green-dark)' : 'var(--gold-dark)'"
        />
        <span class="stat-chip-eye"><AegisIcon name="eye" style="width:25px;height:18px" /></span>
      </button>
      <AegisStatChip icon="users" :value="csCount" label="Continuity Stewards" />
      <AegisStatChip icon="user-check" :value="ssCount" label="Support Stewards" />
      <AegisStatChip
        icon="calendar"
        :value="plan && plan.vault_attested_at ? formatDate(plan.vault_attested_at) : 'Not attested'"
        label="Last vault attestation"
        bg-color="var(--icon-bg-gold)"
        icon-color="var(--gold-dark)"
      />
    </div>

    <!-- NO PLAN -->
    <AegisEmptyState v-if="!plan"
      icon="file-text"
      title="No continuity plan yet"
      description="Create a draft plan to get started. Build step by step in Aegis, upload an existing document, or start from a template.">
      <template #actions>
        <button type="button" class="btn btn-primary" @click="createDraft">
          <AegisIcon name="plus" :size="14" /> Create Draft Plan
        </button>
      </template>
    </AegisEmptyState>

    <template v-else>

      <!-- SIGNED BANNER -->
      <div v-if="plan.signed_at" class="alert alert-success" style="margin-bottom:16px">
        <AegisIcon name="check-circle" :size="16" />
        <div style="flex:1">
          <strong>Plan Signed &amp; Active</strong>
          <span style="margin-left:8px;font-weight:400;opacity:.85">
            v{{ plan.plan_version }}.0 · Signed {{ formatDate(plan.signed_at) }}
            <span v-if="plan.signature_name"> by {{ plan.signature_name }}</span>
          </span>
        </div>
        <button type="button" class="btn btn-outline" style="flex-shrink:0;font-size:12px;padding:4px 12px;height:auto" @click="showSignedDetails = true">
          <AegisIcon name="info" :size="12" /> Details
        </button>
      </div>

      <!-- ANNUAL REVIEW DUE BANNER -->
      <div v-if="plan.status === 'annual_review_due'" class="alert alert-warning" style="margin-bottom:16px">
        <AegisIcon name="alert-triangle" :size="16" />
        <div>
          <strong>Annual review due{{ plan.annual_review_date ? ' ' + formatDate(plan.annual_review_date) : '' }}.</strong>
          Your plan remains active but should be reviewed and re-signed.
        </div>
      </div>


      <!-- ═══ BUILD PANE ═══ -->
      <div>

        <!-- Sign CTA (unsigned only) -->
        <div v-if="plan && !plan.signed_at" class="sign-cta">
          <div class="alert alert-warning">
            <AegisIcon name="alert-triangle" :size="16" />
            <div>By signing, you confirm this plan is accurate and authorize your stewards to act as described when a critical incident occurs.</div>
          </div>
          <div class="sign-cta-actions">
            <button type="button" class="btn btn-outline" @click="showAttestModal = true">
              <AegisIcon name="check-circle" :size="13" /> Attest Vault
            </button>
            <button type="button" class="btn btn-primary"
              :data-tooltip="!canSign ? signBlockedReason : undefined"
              @click="canSign ? (showSignModal = true) : (showSectionsModal = true)">
              <AegisIcon name="edit" :size="13" /> Finalize &amp; Sign
            </button>
          </div>
        </div>

        <!-- Team row -->
        <div class="section-head">
          <div>
            <h2 class="section-title">Your Continuity Team</h2>
            <div class="section-sub">The people authorized to act on this plan when it activates.</div>
          </div>
          <div style="display:flex;gap:8px">
            <a :href="route('provider.stewards.index')" class="btn btn-outline">Manage CS</a>
            <a :href="route('provider.ss.index')" class="btn btn-outline">Manage SS</a>
          </div>
        </div>

        <div class="team-row">
          <template v-for="slot in teamSlots" :key="slot.label">
            <div v-if="slot.steward" class="team-chip">
              <span class="avatar avatar-sm" :style="`background:${slot.isCs ? 'var(--gold-dark)' : 'var(--text-3)'};color:#fff;font-family:var(--font-serif);font-weight:600;overflow:hidden;padding:0`">
                <img v-if="slot.steward.avatar_url" :src="slot.steward.avatar_url" style="width:100%;height:100%;object-fit:cover" />
                <span v-else>{{ slot.steward.avatar_initials }}</span>
              </span>
              <div style="flex:1;min-width:0">
                <div style="font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--text-4);margin-bottom:2px">{{ slot.label }}</div>
                <a v-if="slot.steward.slug"
                  :href="slot.isCs ? route('public.cs', { slug: slot.steward.slug }) : route('public.ss', { slug: slot.steward.slug })"
                  style="font-size:13px;font-weight:700;color:var(--gold-dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;text-decoration:none"
                  data-tooltip="View public profile">{{ slot.steward.display_name }}</a>
                <div v-else style="font-size:13px;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ slot.steward.display_name }}</div>
                <AegisBadge :label="slot.steward.status" :variant="slot.steward.status === 'active' ? 'green' : 'gold'" style="margin-top:4px" />
              </div>
            </div>
            <div v-else class="team-chip team-chip-empty">
              <div>
                <div style="font-size:12px;font-weight:600;color:var(--text-3)">No {{ slot.label }}</div>
                <div style="font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--red-dark);margin-top:2px">{{ slot.required ? 'Required' : 'Recommended' }}</div>
              </div>
              <a :href="slot.isCs ? route('provider.stewards.index') : route('provider.ss.index')" class="btn btn-primary">Add</a>
            </div>
          </template>
        </div>

        <!-- Incident grid -->
        <div class="section-head">
          <div>
            <h2 class="section-title" id="incident-grid">Critical Moments</h2>
            <div class="section-sub">7 incident types. Toggle each on, assign documents, stewards, and tasks.</div>
          </div>
        </div>

        <AegisCard style="overflow:hidden;padding:0">
          <!-- Grid header -->
          <div class="grid-head">
            <div class="grid-head-cell">Incident Type</div>
            <div class="grid-head-cell" style="text-align:center">Enabled</div>
            <div class="grid-head-cell">Docs Required</div>
            <div class="grid-head-cell">Authorized Stewards</div>
            <div class="grid-head-cell">Tasks</div>
            <div></div>
          </div>

          <!-- Grid rows -->
          <div v-for="type in incidentTypes" :key="type.value"
            class="grid-row"
            :class="isEnabled(type.value) ? 'is-enabled' : 'is-disabled'">

            <div v-if="isEnabled(type.value)" class="enabled-bar"></div>

            <!-- Type -->
            <div class="grid-type">
              <span class="grid-type-icon">
                <AegisIcon :name="incidentIcon(type.value)" :size="17" />
              </span>
              <div>
                <div class="grid-type-name">{{ type.label }}</div>
                <div style="display:flex;align-items:center;gap:5px">
                  <span v-if="!type.is_optin" class="micro-badge is-always">Always on</span>
                  <span v-else class="micro-badge is-optin">Opt-in</span>
                  <span class="micro-badge" :class="isEnabled(type.value) ? 'is-on' : 'is-off'">
                    {{ isEnabled(type.value) ? 'Enabled' : 'Disabled' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Toggle -->
            <div style="display:flex;justify-content:center">
              <span :data-tooltip="!type.is_optin ? 'This incident type is always required and cannot be disabled' : undefined">
                <AegisToggle
                  :model-value="isEnabled(type.value)"
                  :disabled="!type.is_optin && isEnabled(type.value)"
                  @update:model-value="(val) => handleToggle(type, val)"
                />
              </span>
            </div>

            <!-- Docs -->
            <div :class="{ 'grid-cell-dim': !isEnabled(type.value) }">
              <span v-if="!isEnabled(type.value)" class="grid-empty-cell">—</span>
              <div v-else-if="getConfig(type.value)?.docs_required?.length" style="display:flex;flex-wrap:wrap;gap:5px">
                <span v-for="doc in getConfig(type.value).docs_required" :key="doc" class="doc-chip">
                  <AegisIcon name="check" :size="10" />{{ docLabel(doc) }}
                </span>
              </div>
              <span v-else class="grid-empty-cell">None required</span>
            </div>

            <!-- Stewards -->
            <div :class="{ 'grid-cell-dim': !isEnabled(type.value) }">
              <span v-if="!isEnabled(type.value)" class="grid-empty-cell">—</span>
              <div v-else style="display:flex;flex-wrap:wrap;gap:5px">
                <a v-for="sid in (getConfig(type.value)?.authorized_ss_ids ?? [])" :key="'ss-'+sid"
                  :href="getStewardSlug(sid) ? route('public.ss', { slug: getStewardSlug(sid) }) : '#'"
                  class="steward-mini" style="text-decoration:none">
                  <span class="steward-mini-av steward-mini-av-ss" style="overflow:hidden;padding:0">
                    <img v-if="getStewardPhoto(sid)" :src="getStewardPhoto(sid)" style="width:100%;height:100%;object-fit:cover" />
                    <span v-else>{{ getStewardInitials(sid) }}</span>
                  </span>
                  {{ getStewardFirstName(sid) }}
                </a>
                <a v-for="cid in (getConfig(type.value)?.authorized_cs_ids ?? [])" :key="'cs-'+cid"
                  :href="getStewardSlug(cid) ? route('public.cs', { slug: getStewardSlug(cid) }) : '#'"
                  class="steward-mini" style="text-decoration:none">
                  <span class="steward-mini-av steward-mini-av-cs" style="overflow:hidden;padding:0">
                    <img v-if="getStewardPhoto(cid)" :src="getStewardPhoto(cid)" style="width:100%;height:100%;object-fit:cover" />
                    <span v-else>{{ getStewardInitials(cid) }}</span>
                  </span>
                  {{ getStewardFirstName(cid) }}
                </a>
                <span v-if="!getConfig(type.value)?.authorized_ss_ids?.length && !getConfig(type.value)?.authorized_cs_ids?.length" class="grid-empty-cell">No stewards assigned</span>
              </div>
            </div>

            <!-- Tasks -->
            <div :class="{ 'grid-cell-dim': !isEnabled(type.value) }">
              <span v-if="!isEnabled(type.value)" class="grid-empty-cell">—</span>
              <div v-else style="display:flex;flex-direction:column;gap:4px">
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-xs);padding:3px 8px;color:var(--text-2);white-space:nowrap">
                  <AegisIcon name="user-check" :size="10" style="color:var(--text-3);flex-shrink:0" />
                  <span style="font-weight:700;color:var(--text)">{{ ssTaskCount(type.value) }}</span> SS tasks
                </span>
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;background:var(--badge-bg-gold);border:1px solid var(--gold-light, var(--border));border-radius:var(--radius-xs);padding:3px 8px;color:var(--gold-dark);white-space:nowrap">
                  <AegisIcon name="shield" :size="10" style="flex-shrink:0" />
                  <span style="font-weight:700">{{ csTaskCount(type.value) }}</span> CS tasks
                </span>
              </div>
            </div>

            <!-- Edit -->
            <div style="display:flex;justify-content:flex-end;padding-right:4px">
              <button v-if="isEnabled(type.value)" type="button" class="btn-icon" :data-tooltip="`Configure ${type.label}`"
                @click="openIncidentConfig(type)">
                <AegisIcon name="edit" :size="13" />
              </button>
            </div>
          </div>
        </AegisCard>

      </div><!-- /build pane -->

    </template>

    <!-- ═══ MODALS ═══ -->
    <SignPlanModal v-model="showSignModal"
      :plan="plan" :stewards="stewards" :can-sign="canSign"
      :section-summary="planSections.slice(0, 7)" :auth="auth"
    />

    <AttestPlanModal v-model="showAttestModal" />

    <!-- Plan Readiness Modal -->
    <AegisModal v-model="showSectionsModal" size="md" title="Plan Readiness">
      <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:16px">
        <div v-for="sec in planSections.slice(0,7)" :key="sec.key"
          :style="`display:flex;align-items:center;gap:12px;padding:10px 14px;background:${!sec.complete && sec.blocks_signing ? 'var(--icon-bg-gold)' : 'var(--surface)'};border:1px solid ${!sec.complete && sec.blocks_signing ? 'var(--gold-dark)' : 'var(--border)'};border-radius:var(--radius-sm);`">
          <!-- Status circle -->
          <span :style="`width:22px;height:22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;background:${sec.complete ? 'var(--green-dark)' : (sec.blocks_signing ? 'var(--gold-dark)' : 'var(--surface-3)')};color:${sec.complete ? '#fff' : (sec.blocks_signing ? '#fff' : 'var(--text-4)')}`">
            <AegisIcon v-if="sec.complete" name="check" :size="11" />
            <span v-else style="font-size:10px;font-weight:700;font-family:var(--font-serif)">{{ planSections.indexOf(sec) + 1 }}</span>
          </span>
          <!-- Title -->
          <span :style="`flex:1;font-size:13px;font-weight:600;color:${!sec.complete && sec.blocks_signing ? 'var(--gold-dark)' : 'var(--text)'}`">{{ sec.title }}</span>
          <!-- Status badge -->
          <AegisBadge v-if="sec.complete" label="Complete" variant="green" />
          <AegisBadge v-else-if="sec.blocks_signing" label="Required" variant="gold" />
          <AegisBadge v-else label="Recommended" variant="default" />
          <!-- Arrow link -->
          <a v-if="sec.href && !sec.complete" :href="sec.href" style="color:var(--text-4);flex-shrink:0" data-tooltip="Go to section" @click="showSectionsModal = false">
            <AegisIcon name="arrow-right" :size="14" />
          </a>
          <span v-else-if="sec.complete" style="width:14px;flex-shrink:0"></span>
        </div>
      </div>
      <p v-if="!canSign" style="font-size:12px;color:var(--text-3);margin:0;text-align:center">
        Once all required sections are complete, you can sign your plan.
      </p>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="showSectionsModal = false">Close</button>
        <button v-if="canSign" type="button" class="btn btn-primary"
          @click="showSectionsModal = false; showSignModal = true">
          <AegisIcon name="edit" :size="13" /> Finalize &amp; Sign
        </button>
      </template>
    </AegisModal>

    <IncidentConfigModal v-model="showIncidentConfig"
      :incident-type="activeIncidentType"
      :config="activeIncidentType ? getConfig(activeIncidentType.value) : null"
      :stewards="stewards"
      :tasks="tasks"
      @update-config="patchLocalConfig($event.incident_type, $event)"
    />


    <!-- Plan Signed Details Modal -->
    <AegisModal v-if="plan && plan.signed_at" v-model="showSignedDetails" size="md" title="Plan Signature Details">
      <div class="alert alert-success" style="margin-bottom:20px">
        <AegisIcon name="check-circle" :size="16" />
        <div>
          <strong>Plan Signed &amp; Active</strong>
          <span style="margin-left:8px;font-weight:400;opacity:.85">v{{ plan.plan_version }}.0</span>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
        <div style="border-left:3px solid var(--gold-dark);padding-left:14px">
          <div style="font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--text-3);margin-bottom:5px">Signed By</div>
          <div style="font-family:var(--font-serif);font-size:17px;font-weight:600;color:var(--text)">{{ plan.signature_name ?? '—' }}</div>
        </div>
        <div style="border-left:3px solid var(--gold-dark);padding-left:14px">
          <div style="font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--text-3);margin-bottom:5px">Title / Role</div>
          <div style="font-family:var(--font-serif);font-size:17px;font-weight:600;color:var(--text)">{{ plan.signature_title ?? '—' }}</div>
        </div>
        <div style="border-left:3px solid var(--border);padding-left:14px">
          <div style="font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--text-3);margin-bottom:5px">Date Signed</div>
          <div style="font-size:15px;font-weight:600;color:var(--text)">{{ formatDate(plan.signed_at) }}</div>
        </div>
        <div style="border-left:3px solid var(--border);padding-left:14px">
          <div style="font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--text-3);margin-bottom:5px">Plan Version</div>
          <div style="font-size:15px;font-weight:600;color:var(--text)">v{{ plan.plan_version }}.0</div>
        </div>
      </div>
      <div v-if="plan.annual_review_date" class="alert alert-info">
        <AegisIcon name="calendar" :size="15" />
        <div>Next annual review due <strong>{{ formatDate(plan.annual_review_date) }}</strong>. You will be reminded when it approaches.</div>
      </div>
      <div v-if="plan.signature_agreed" style="margin-top:16px;padding:14px 16px;background:var(--surface-2);border-radius:var(--radius-sm);border:1px solid var(--border)">
        <div style="font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--text-3);margin-bottom:6px">Signatory Agreement</div>
        <div style="font-size:12px;color:var(--text-2);line-height:1.6">I confirm this plan is accurate and authorize my designated stewards to act as described when a critical incident occurs. This plan supersedes all prior versions.</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="showSignedDetails = false">Close</button>
        <button v-if="plan.status === 'active'" type="button" class="btn btn-outline" @click="showSignedDetails = false; showAnnualReview = true">
          <AegisIcon name="refresh-cw" :size="13" /> Begin Annual Review
        </button>
      </template>
    </AegisModal>

    <!-- Annual Review -->
    <AegisModal v-model="showAnnualReview" size="md" title="Begin Annual Review">
      <div class="alert alert-info" style="margin-bottom:14px">
        <AegisIcon name="info" :size="16" />
        <div>Annual review creates a new draft (v{{ (plan?.plan_version ?? 1) + 1 }}.0). Your active plan stays in force until you sign the new version.</div>
      </div>
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">What changed since last review? (optional)</label>
        <textarea v-model="reviewNotes" class="form-input" rows="3" placeholder="Note changes to your practice, team, or operations…" style="resize:vertical" />
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="showAnnualReview = false">Cancel</button>
        <button type="button" class="btn btn-primary" :class="{ 'btn-spin': reviewSubmitting }" :disabled="reviewSubmitting" @click="submitAnnualReview">
          <AegisIcon v-if="reviewSubmitting" name="refresh-cw" :size="14" class="spin" />
          <AegisIcon v-else name="refresh-cw" :size="14" />
          {{ reviewSubmitting ? 'Starting review…' : 'Begin Review' }}
        </button>
      </template>
    </AegisModal>

  </AppLayout>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout          from '@/layouts/AppLayout.vue'
import SignPlanModal       from '@/components/modals/SignPlanModal.vue'
import AttestPlanModal    from '@/components/modals/AttestPlanModal.vue'
import IncidentConfigModal from '@/components/modals/IncidentConfigModal.vue'
import AegisToggle        from '@/components/ui/AegisToggle.vue'

const props = defineProps({
  plan:            { type: Object,  default: null },
  planSections:    { type: Array,   default: () => [] },
  tasks:           { type: Array,   default: () => [] },
  incidentConfigs: { type: Array,   default: () => [] },
  stewards:        { type: Array,   default: () => [] },
  documents:       { type: Array,   default: () => [] },
  incidentTypes:   { type: Array,   default: () => [] },
  canSign:         { type: Boolean, default: false },
  canActivate:     { type: Boolean, default: false },
  tierLimits:      { type: Object,  default: () => ({}) },
  auth:            { type: Object,  default: null },
})

// ── State ──────────────────────────────────────────────────────────────────────
const showSignModal      = ref(false)
const showSectionsModal  = ref(false)
const showAttestModal    = ref(false)
const showIncidentConfig = ref(false)
const showAnnualReview   = ref(false)
const showSignedDetails  = ref(false)
const activeIncidentType = ref(null)
const reviewNotes        = ref('')
const reviewSubmitting   = ref(false)

// ── Create draft ───────────────────────────────────────────────────────────────
const createDraftForm = useForm({})
function createDraft() { createDraftForm.post(route('provider.plan.store')) }

// ── Annual review ──────────────────────────────────────────────────────────────
function submitAnnualReview() {
  reviewSubmitting.value = true
  router.post(route('provider.plan.review.start'), {}, {
    onSuccess: () => { reviewSubmitting.value = false; showAnnualReview.value = false },
    onError:   () => { reviewSubmitting.value = false },
  })
}

// ── Computed ───────────────────────────────────────────────────────────────────
const planStatusLabel = computed(() => {
  const map = { draft: 'Draft', active: 'Active', annual_review_due: 'Annual Review Due', expired: 'Expired', pending_review: 'Pending Review' }
  return map[props.plan?.status] ?? props.plan?.status ?? ''
})

const completedSections = computed(() =>
  props.planSections.filter((s, i) => i < 7 && s.complete).length
)

const csCount = computed(() => props.stewards.filter(s => s.steward_type === 'continuity_steward' && s.status === 'active').length)
const ssCount = computed(() => props.stewards.filter(s => s.steward_type === 'support_steward'    && s.status === 'active').length)

const signBlockedReason = computed(() => {
  if (!props.plan) return 'Create a plan first.'
  const incomplete = (props.planSections ?? []).filter(s => s.blocks_signing && !s.complete)
  if (!incomplete.length) return null
  return `Complete required sections first: ${incomplete.map(s => s.title).join(', ')}.`
})

const teamSlots = computed(() => {
  const cs = props.stewards.filter(s => s.steward_type === 'continuity_steward')
  const ss = props.stewards.filter(s => s.steward_type === 'support_steward')
  return [
    { label: 'Primary CS',   isCs: true,  required: true,  steward: cs.find(s => s.role === 'primary')   ?? null },
    { label: 'Alternate CS', isCs: true,  required: false, steward: cs.find(s => s.role === 'alternate') ?? null },
    { label: 'Primary SS',   isCs: false, required: false, steward: ss.find(s => s.role === 'primary')   ?? null },
    { label: 'Alternate SS', isCs: false, required: false, steward: ss.find(s => s.role === 'alternate') ?? null },
  ]
})

// ── Incident helpers ───────────────────────────────────────────────────────────
// Local reactive copy — optimistically updated so grid & modal stay in sync
// without waiting for a server round-trip.
const localConfigs = ref([])
watch(() => props.incidentConfigs, (v) => { localConfigs.value = v.map(c => ({ ...c })) }, { immediate: true, deep: true })

function isEnabled(v)    { return !!(localConfigs.value.find(c => c.incident_type === v)?.is_active) }
function getConfig(v)    { return localConfigs.value.find(c => c.incident_type === v) ?? null }
function ssTaskCount(_v) { return props.tasks.filter(t => t.assigned_to === 'support_steward').length }
function csTaskCount(_v) { return props.tasks.filter(t => t.assigned_to === 'continuity_steward').length }

function openIncidentConfig(type) { activeIncidentType.value = type; showIncidentConfig.value = true }

function patchLocalConfig(incidentType, patch) {
  const idx = localConfigs.value.findIndex(c => c.incident_type === incidentType)
  if (idx !== -1) {
    localConfigs.value[idx] = { ...localConfigs.value[idx], ...patch }
  } else {
    localConfigs.value.push({ incident_type: incidentType, is_active: false, docs_required: [], authorized_ss_ids: [], authorized_cs_ids: [], ...patch })
  }
}

function handleToggle(type, val) {
  // Guard: always-on types cannot be disabled
  if (!type.is_optin && !val) return
  // Optimistic update — grid reflects change immediately
  patchLocalConfig(type.value, { is_active: val })
  router.post(route('provider.plan.incident-config'), {
    incident_type:      type.value,
    is_active:          val,
    is_optin:           type.is_optin,
    docs_required:      getConfig(type.value)?.docs_required ?? [],
    authorized_ss_ids:  getConfig(type.value)?.authorized_ss_ids ?? [],
    authorized_cs_ids:  getConfig(type.value)?.authorized_cs_ids ?? [],
  }, { preserveState: true, preserveScroll: true })
}

// ── Steward helpers ────────────────────────────────────────────────────────────
function getStewardById(id)       { return props.stewards.find(s => s.steward_id === id) ?? null }
function getStewardInitials(id)   { return getStewardById(id)?.avatar_initials ?? '??' }
function getStewardFirstName(id)  { const n = getStewardById(id)?.display_name ?? ''; return n.replace(/^Dr\.\s+/i, '').split(' ')[0] ?? n }
function getStewardSlug(id)       { return getStewardById(id)?.slug ?? null }
function getStewardPhoto(id)      { return getStewardById(id)?.avatar_url ?? null }

// ── Doc label ──────────────────────────────────────────────────────────────────
function docLabel(v) {
  return { death_certificate: 'Death Certificate', medical_documentation: 'Medical Documentation', police_report: 'Police Report', legal_documentation: 'Legal Documentation', other: 'Other' }[v] ?? v
}

// ── Incident icon ──────────────────────────────────────────────────────────────
function incidentIcon(v) {
  return { death: 'x-circle', incapacitation: 'clock', extended_absence: 'calendar', missing: 'search', detainment: 'lock', natural_disaster: 'cloud-rain', geopolitical: 'globe' }[v] ?? 'alert-circle'
}

function formatDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}


</script>

<style scoped>
.section-title { font-family: var(--font-serif); font-size: 18px; font-weight: 700; color: var(--text); letter-spacing: -.005em; }
.section-sub   { margin-top: 2px; font-size: 12px; color: var(--text-3); }
.section-head  { display: flex; align-items: flex-end; justify-content: space-between; gap: 14px; margin-bottom: 12px; margin-top: 24px; }

/* Team row */
.team-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 10px; margin-bottom: 28px; }
.team-chip { display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); }
.team-chip-empty { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: transparent; border: 1px dashed var(--border-dark); border-radius: var(--radius); }

/* Grid */
.grid-head { display: grid; grid-template-columns: 240px 80px minmax(150px,1.2fr) minmax(150px,1.3fr) 160px 56px; align-items: center; background: var(--surface-3); border-bottom: 1px solid var(--border); padding: 12px 18px; }
.grid-head-cell { font-size: 10px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: var(--text-3); }
.grid-row { display: grid; grid-template-columns: 240px 80px minmax(150px,1.2fr) minmax(150px,1.3fr) 160px 56px; align-items: center; padding: 14px 18px; border-bottom: 1px solid var(--border); position: relative; min-height: 76px; transition: background .15s; }
.grid-row:last-of-type { border-bottom: none; }
.grid-row.is-disabled { background: var(--surface-2); }
.grid-row.is-disabled .grid-type-name { opacity: .55; }
.grid-type { display: flex; align-items: center; gap: 12px; }
.grid-type-icon { width: 32px; height: 32px; border-radius: var(--radius-sm); background: var(--icon-bg-gold); color: var(--gold-dark); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.grid-type-name { font-family: var(--font-serif); font-size: 14px; font-weight: 600; color: var(--text); line-height: 1.2; margin-bottom: 4px; }
.enabled-bar { position: absolute; left: 0; top: 12px; bottom: 12px; width: 3px; background: var(--gold-dark); border-radius: 0 2px 2px 0; }
.grid-empty-cell { font-size: 11px; color: var(--text-4); font-style: italic; }
.grid-cell-dim { opacity: .5; }

/* Micro-badges */
.micro-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; padding: 2px 6px; border-radius: var(--radius-xs); line-height: 1.3; }
.micro-badge.is-on     { background: var(--badge-bg-gold); color: var(--gold-dark); }
.micro-badge.is-off    { background: var(--surface-3); color: var(--text-4); }
.micro-badge.is-optin  { background: var(--surface-3); color: var(--text-3); }
.micro-badge.is-always { background: var(--green-light); color: var(--green-dark); }

/* Doc chip */
.doc-chip { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 500; padding: 3px 9px; border-radius: var(--radius-full); background: var(--surface-2); border: 1px solid var(--border); color: var(--text-2); }

/* Steward mini */
.steward-mini { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 500; color: var(--text-2); background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-full); padding: 2px 9px 2px 4px; }
.steward-mini-av { width: 20px; height: 20px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 6px; font-weight: 700; color: #fff; font-family: var(--font-serif); }
.steward-mini-av-cs { background: var(--gold-dark); }
.steward-mini-av-ss { background: var(--text-3); }

/* Signed card */
.sig-card { margin-top: 22px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); }
.sig-card.is-signed { border-color: var(--green-dark); }
.signed-meta { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; padding: 20px 24px; }
.signed-meta-item { border-left: 2px solid var(--border); padding-left: 12px; }
.signed-meta-label { font-size: 10px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: var(--text-3); margin-bottom: 4px; }
.signed-meta-value { font-family: var(--font-serif); font-size: 16px; font-weight: 600; color: var(--text); }


/* Sign CTA */
.sign-cta { margin-bottom: 22px; padding: 22px 24px 20px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); }
.sign-cta .alert { margin-bottom: 16px; }
.sign-cta-gap { margin-bottom: 12px; }
.sign-cta-actions { display: flex; justify-content: flex-end; gap: 10px; }

/* Clickable stat chip */
.stat-chip-btn { position: relative; background: none; border: none; padding: 0; cursor: pointer; display: inline-block; }
.stat-chip-btn:hover { opacity: 0.85; }
.stat-chip-eye { position: absolute; top: 7px; right: 4px; width: 25px; height: 18px; border-radius: 50%; color: var(--gold-dark); display: inline-flex; align-items: center; justify-content: center; border: none; pointer-events: none; }

/* Spin */
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin .7s linear infinite; }
</style>
