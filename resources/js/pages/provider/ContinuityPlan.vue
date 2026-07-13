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
          :disabled="!canSign"
          :data-tooltip="!canSign ? signBlockedReason : undefined"
          @click="showSignModal = true">
          <AegisIcon name="edit" :size="14" /> Finalize &amp; Sign
        </button>
      </template>
    </AegisHeroBanner>

    <!-- STAT CHIPS -->
    <div class="stat-chips-row">
      <AegisStatChip
        icon="shield"
        :value="`${completedSections}/7`"
        label="Sections complete"
        :bg-color="completedSections === 7 ? 'var(--icon-bg-green)' : 'var(--icon-bg-gold)'"
        :icon-color="completedSections === 7 ? 'var(--green-dark)' : 'var(--gold-dark)'"
      />
      <AegisStatChip icon="users" :value="csCount" label="Continuity Stewards" />
      <AegisStatChip icon="user-check" :value="ssCount" label="Support Stewards" />
      <AegisStatChip
        icon="calendar"
        :value="plan && plan.vault_attested_at ? formatDate(plan.vault_attested_at) : 'Not attested'"
        label="Last vault attestation"
        :bg-color="plan && plan.vault_attested_at ? 'var(--icon-bg-green)' : undefined"
        :icon-color="plan && plan.vault_attested_at ? 'var(--green-dark)' : undefined"
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
        <div>
          <strong>Plan Signed &amp; Active</strong>
          <span style="margin-left:8px;font-weight:400;opacity:.85">
            v{{ plan.plan_version }}.0 · Signed {{ formatDate(plan.signed_at) }}
            <span v-if="plan.signature_name"> by {{ plan.signature_name }}</span>
          </span>
        </div>
      </div>

      <!-- ANNUAL REVIEW DUE BANNER -->
      <div v-if="plan.status === 'annual_review_due'" class="alert alert-warning" style="margin-bottom:16px">
        <AegisIcon name="alert-triangle" :size="16" />
        <div>
          <strong>Annual review due{{ plan.annual_review_date ? ' ' + formatDate(plan.annual_review_date) : '' }}.</strong>
          Your plan remains active but should be reviewed and re-signed.
        </div>
      </div>

      <!-- PATH TILES -->
      <div class="path-row">
        <button type="button" class="path-tile" :class="{ 'is-selected': activePane === 'build' }" @click="activePane = 'build'">
          <span class="path-tile-icon"><AegisIcon name="edit" :size="18" /></span>
          <span class="path-tile-text">
            <div class="path-tile-title">Build in Aegis</div>
            <div class="path-tile-sub">Configure the 7-incident grid step by step</div>
          </span>
          <span v-if="activePane === 'build'" class="path-tile-check"><AegisIcon name="check" :size="12" /></span>
        </button>
        <button type="button" class="path-tile" :class="{ 'is-selected': activePane === 'sections' }" @click="activePane = 'sections'">
          <span class="path-tile-icon"><AegisIcon name="clipboard-list" :size="18" /></span>
          <span class="path-tile-text">
            <div class="path-tile-title">Section Checklist</div>
            <div class="path-tile-sub">{{ completedSections }}/7 sections complete</div>
          </span>
          <span v-if="activePane === 'sections'" class="path-tile-check"><AegisIcon name="check" :size="12" /></span>
        </button>
        <button type="button" class="path-tile" :class="{ 'is-selected': activePane === 'template' }" @click="activePane = 'template'">
          <span class="path-tile-icon"><AegisIcon name="file-text" :size="18" /></span>
          <span class="path-tile-text">
            <div class="path-tile-title">Start from Template</div>
            <div class="path-tile-sub">{{ templates.length }} Aegis templates</div>
          </span>
          <span v-if="activePane === 'template'" class="path-tile-check"><AegisIcon name="check" :size="12" /></span>
        </button>
      </div>

      <!-- ═══ BUILD PANE ═══ -->
      <div v-show="activePane === 'build'">

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
              <span class="avatar avatar-sm" :style="`background:${slot.isCs ? 'var(--gold-dark)' : 'var(--text-3)'};color:#fff;font-family:var(--font-serif);font-weight:600`">
                {{ slot.steward.avatar_initials }}
              </span>
              <div style="flex:1;min-width:0">
                <div style="font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--text-4);margin-bottom:2px">{{ slot.label }}</div>
                <div style="font-size:13px;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ slot.steward.display_name }}</div>
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
          <button type="button" class="btn btn-ghost" @click="showHowItWorks = true">
            <AegisIcon name="info" :size="13" /> How this works
          </button>
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
              <AegisToggle
                :model-value="isEnabled(type.value)"
                :disabled="!type.is_optin && isEnabled(type.value)"
                @update:model-value="(val) => handleToggle(type, val)"
              />
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
                <span v-for="sid in (getConfig(type.value)?.authorized_ss_ids ?? [])" :key="'ss-'+sid" class="steward-mini">
                  <span class="steward-mini-av steward-mini-av-ss">{{ getStewardInitials(sid) }}</span>
                  {{ getStewardFirstName(sid) }}
                </span>
                <span v-for="cid in (getConfig(type.value)?.authorized_cs_ids ?? [])" :key="'cs-'+cid" class="steward-mini">
                  <span class="steward-mini-av steward-mini-av-cs">{{ getStewardInitials(cid) }}</span>
                  {{ getStewardFirstName(cid) }}
                </span>
                <span v-if="!getConfig(type.value)?.authorized_ss_ids?.length && !getConfig(type.value)?.authorized_cs_ids?.length" class="grid-empty-cell">No stewards assigned</span>
              </div>
            </div>

            <!-- Tasks -->
            <div :class="{ 'grid-cell-dim': !isEnabled(type.value) }">
              <span v-if="!isEnabled(type.value)" class="grid-empty-cell">—</span>
              <span v-else style="display:flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:var(--text-2)">
                <AegisIcon name="clipboard-check" :size="11" style="color:var(--gold-dark);flex-shrink:0" />
                {{ ssTaskCount(type.value) }} SS · {{ csTaskCount(type.value) }} CS
              </span>
            </div>

            <!-- Edit -->
            <button v-if="isEnabled(type.value)" type="button" class="btn-icon" :data-tooltip="`Configure ${type.label}`"
              @click="openIncidentConfig(type)">
              <AegisIcon name="edit" :size="13" />
            </button>
            <span v-else></span>
          </div>
        </AegisCard>

        <!-- Sign block -->
        <div class="sig-card" :class="{ 'is-signed': plan.signed_at }">
          <div v-if="plan.signed_at" class="alert alert-success" style="border-radius:0;border-left:none;border-right:none;border-top:none;margin:0">
            <AegisIcon name="check-circle" :size="16" />
            <div><strong>Plan Signed &amp; Active</strong>
              <span style="margin-left:8px;font-weight:400;opacity:.85">v{{ plan.plan_version }}.0 · {{ formatDate(plan.signed_at) }}</span>
            </div>
          </div>
          <div v-if="plan.signed_at" class="signed-meta">
            <div class="signed-meta-item">
              <div class="signed-meta-label">Signed by</div>
              <div class="signed-meta-value">{{ plan.signature_name ?? '—' }}</div>
            </div>
            <div class="signed-meta-item">
              <div class="signed-meta-label">Title</div>
              <div class="signed-meta-value">{{ plan.signature_title ?? '—' }}</div>
            </div>
            <div class="signed-meta-item">
              <div class="signed-meta-label">Date</div>
              <div class="signed-meta-value">{{ formatDate(plan.signed_at) }}</div>
            </div>
            <div class="signed-meta-item">
              <div class="signed-meta-label">Version</div>
              <div class="signed-meta-value">v{{ plan.plan_version }}.0
                <small v-if="plan.annual_review_date" style="display:block;font-size:11px;font-weight:500;color:var(--text-3);margin-top:2px">Review {{ formatDate(plan.annual_review_date) }}</small>
              </div>
            </div>
          </div>
          <div v-else style="padding:22px 24px 20px">
            <div class="alert alert-warning" style="margin-bottom:16px">
              <AegisIcon name="alert-triangle" :size="16" />
              <div>By signing, you confirm this plan is accurate and authorize your stewards to act as described when a critical incident occurs.</div>
            </div>
            <div v-if="!canSign && signBlockedReason" class="alert alert-info" style="margin-bottom:12px">
              <AegisIcon name="info" :size="16" />
              <div>{{ signBlockedReason }}</div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px">
              <button type="button" class="btn btn-outline" @click="showAttestModal = true">
                <AegisIcon name="check-circle" :size="13" /> Attest Vault
              </button>
              <button type="button" class="btn btn-primary" :disabled="!canSign"
                :data-tooltip="!canSign ? signBlockedReason : undefined"
                @click="showSignModal = true">
                <AegisIcon name="edit" :size="13" /> Finalize &amp; Sign
              </button>
            </div>
          </div>
        </div>

      </div><!-- /build pane -->

      <!-- ═══ SECTIONS PANE ═══ -->
      <div v-show="activePane === 'sections'">
        <AegisCard>
          <div style="padding:20px 24px">
            <h2 style="font-family:var(--font-serif);font-size:18px;font-weight:700;color:var(--text);margin-bottom:4px">Plan Readiness Checklist</h2>
            <p style="font-size:13px;color:var(--text-3);margin-bottom:20px">Complete all required sections before signing.</p>
            <div class="list-group" style="margin-bottom:0">
              <div v-for="(sec, idx) in planSections" :key="sec.key" class="list-group-item" style="gap:14px;padding:14px 16px">
                <span :style="`width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px;font-weight:700;background:${sec.complete ? 'var(--green-dark)' : 'var(--surface-3)'};color:${sec.complete ? '#fff' : 'var(--text-4)'}`">
                  <AegisIcon v-if="sec.complete" name="check" :size="13" />
                  <span v-else>{{ idx + 1 }}</span>
                </span>
                <div style="flex:1;min-width:0">
                  <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:2px">{{ sec.title }}</div>
                  <div v-if="sec.warning" style="font-size:12px;color:var(--orange-dark);font-weight:500">{{ sec.warning }}</div>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                  <AegisBadge v-if="sec.key !== 'sign'"
                    :label="sec.complete ? 'Complete' : (sec.blocks_signing ? 'Required' : 'Recommended')"
                    :variant="sec.complete ? 'green' : (sec.blocks_signing ? 'gold' : 'neutral')"
                  />
                  <a v-if="sec.href && sec.key !== 'sign'" :href="sec.href" class="btn-icon" data-tooltip="Go to section">
                    <AegisIcon name="arrow-right" :size="13" />
                  </a>
                  <button v-if="sec.key === 'sign'" type="button" class="btn btn-primary"
                    :disabled="!canSign" :data-tooltip="!canSign ? signBlockedReason : undefined"
                    @click="showSignModal = true">
                    <AegisIcon name="edit" :size="13" /> Sign Plan
                  </button>
                </div>
              </div>
            </div>
          </div>
        </AegisCard>
      </div>

      <!-- ═══ TEMPLATE PANE ═══ -->
      <div v-show="activePane === 'template'">
        <div class="section-head" style="margin-bottom:16px">
          <div>
            <h2 class="section-title">Aegis Document Library</h2>
            <div class="section-sub">{{ templates.length }} templates · vetted by continuity attorneys and the Aegis clinical advisory board.</div>
          </div>
        </div>
        <div class="tpl-grid">
          <div v-for="(tpl, idx) in templates" :key="idx" class="tpl-card" @click="activePane = 'build'">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">
              <span style="width:42px;height:42px;border-radius:var(--radius-sm);background:var(--badge-bg-gold);color:var(--gold-dark);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                <AegisIcon name="shield" :size="20" />
              </span>
              <AegisBadge v-if="tpl.tag" :label="tpl.tag" variant="gold" />
            </div>
            <div style="font-family:var(--font-serif);font-size:15px;font-weight:700;color:var(--text)">{{ tpl.title }}</div>
            <p style="font-size:12px;color:var(--text-3);line-height:1.5;flex:1;margin:0">{{ tpl.desc }}</p>
            <div style="display:flex;justify-content:flex-end;padding-top:8px;border-top:1px dashed var(--border)">
              <button type="button" class="btn btn-primary" @click.stop="activePane = 'build'">Use Template →</button>
            </div>
          </div>
        </div>
      </div>

    </template>

    <!-- ═══ MODALS ═══ -->
    <SignPlanModal v-model="showSignModal"
      :plan="plan" :stewards="stewards" :can-sign="canSign"
      :section-summary="planSections.slice(0, 7)" :auth="auth"
    />

    <AttestPlanModal v-model="showAttestModal" />

    <IncidentConfigModal v-model="showIncidentConfig"
      :incident-type="activeIncidentType"
      :config="activeIncidentType ? getConfig(activeIncidentType.value) : null"
      :stewards="stewards"
      :tasks="tasks"
    />

    <!-- How it works -->
    <AegisModal v-model="showHowItWorks" size="md" title="How the Critical Moments Grid Works">
      <div class="list-group">
        <div v-for="(step, i) in howItWorksSteps" :key="i" class="list-group-item" style="align-items:flex-start;gap:14px">
          <span style="width:26px;height:26px;border-radius:50%;background:var(--badge-bg-gold);color:var(--gold-dark);display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">{{ i + 1 }}</span>
          <div>
            <strong style="font-size:13px">{{ step.title }}</strong>
            <div style="font-size:12px;color:var(--text-3);margin-top:3px">{{ step.body }}</div>
          </div>
        </div>
      </div>
      <div class="alert alert-info" style="margin-top:14px">
        <AegisIcon name="info" :size="16" />
        <div>When you <strong>Finalize &amp; Sign</strong>, this plan becomes a legally signed document and every task list is pushed to your stewards' portals automatically.</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-primary" @click="showHowItWorks = false">Got it</button>
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
        <button type="button" class="btn btn-primary btn-spin" :disabled="reviewSubmitting" @click="submitAnnualReview">
          <AegisIcon v-if="reviewSubmitting" name="refresh-cw" :size="14" class="spin" />
          <AegisIcon v-else name="refresh-cw" :size="14" />
          {{ reviewSubmitting ? 'Starting review…' : 'Begin Review' }}
        </button>
      </template>
    </AegisModal>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
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
const activePane         = ref('build')
const showSignModal      = ref(false)
const showAttestModal    = ref(false)
const showIncidentConfig = ref(false)
const showHowItWorks     = ref(false)
const showAnnualReview   = ref(false)
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
function isEnabled(v)   { return !!(props.incidentConfigs.find(c => c.incident_type === v)?.is_active) }
function getConfig(v)   { return props.incidentConfigs.find(c => c.incident_type === v) ?? null }
function ssTaskCount(_v) { return props.tasks.filter(t => t.assigned_to === 'support_steward').length }
function csTaskCount(_v) { return props.tasks.filter(t => t.assigned_to === 'continuity_steward').length }

function openIncidentConfig(type) { activeIncidentType.value = type; showIncidentConfig.value = true }

function handleToggle(type, val) {
  router.post(route('provider.plan.incident-config'), {
    incident_type:      type.value,
    is_active:          val,
    is_optin:           type.is_optin,
    docs_required:      getConfig(type.value)?.docs_required ?? [],
    authorized_ss_ids:  getConfig(type.value)?.authorized_ss_ids ?? [],
    authorized_cs_ids:  getConfig(type.value)?.authorized_cs_ids ?? [],
  }, { preserveScroll: true })
}

// ── Steward helpers ────────────────────────────────────────────────────────────
function getStewardById(id)       { return props.stewards.find(s => s.steward_id === id) ?? null }
function getStewardInitials(id)   { return getStewardById(id)?.avatar_initials ?? '??' }
function getStewardFirstName(id)  { const n = getStewardById(id)?.display_name ?? ''; return n.replace(/^Dr\.\s+/i, '').split(' ')[0] ?? n }

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

// ── Static data ────────────────────────────────────────────────────────────────
const howItWorksSteps = [
  { title: 'Toggle the incident on', body: 'The 3 always-on types (Death, Incapacitation, Extended Absence) are pre-enabled. The 4 opt-in types require your explicit consent.' },
  { title: 'Set required documentation', body: 'Choose which documents your stewards must provide before the vault unlocks and the protocol activates.' },
  { title: 'Authorize your stewards', body: 'Specify which CS and SS are permitted to trigger or verify each incident type.' },
  { title: 'Define task lists', body: 'Build a step-by-step task list for SS and CS per incident. These populate your stewards\' portals when you sign.' },
]

const templates = [
  { title: 'Solo Practitioner — Standard', tag: 'Most Popular', desc: 'For a single-clinician practice with one CS and one SS. Covers the 3 always-on incidents plus Natural Disaster.' },
  { title: 'Group Practice', tag: '', desc: 'Multi-clinician practice with shared CS pool. Includes cross-coverage clauses and shared client notification.' },
  { title: 'Telehealth-Only', tag: 'Access Tier', desc: 'Optimized for telehealth practices — emphasizes platform handover, video session continuity, and EHR access.' },
  { title: 'Disaster-Prone Region', tag: 'MAAT Add-on', desc: 'For practices in hurricane, wildfire, or earthquake zones. Adds Natural Disaster + Geopolitical with extended timelines.' },
  { title: 'High-Acuity Psychiatry', tag: '', desc: 'For prescribers managing controlled substances. Includes DEA notification, prescription transfer, and pharmacy outreach.' },
  { title: 'Child & Family Practice', tag: '', desc: 'Specialized for working with minors — adds custody-aware notification and pediatric record handling.' },
  { title: 'Trauma-Specialty Practice', tag: '', desc: 'Trauma-aware client notification, longer transition windows, and pre-vetted shadow-network referrals.' },
  { title: 'Couples & Family Therapy', tag: '', desc: 'Handles dual-client referrals and joint-session continuity. Includes consent-to-contact partner clauses.' },
  { title: 'High-Profile Client Practice', tag: 'MAAT Add-on', desc: 'Enhanced confidentiality controls — anonymized notification, attorney-mediated record transfer.' },
  { title: 'Multi-State Telehealth', tag: '', desc: 'For PSYPACT and IMLC providers. Adds per-state notification and license-jurisdiction transitions.' },
  { title: 'Supervisor / Trainer', tag: '', desc: 'For supervisors managing supervisees and trainees. Handles supervision continuity and evaluation handover.' },
  { title: 'Blank Template', tag: '', desc: 'Start from a completely empty 7-incident grid.' },
]
</script>

<style scoped>
.section-title { font-family: var(--font-serif); font-size: 18px; font-weight: 700; color: var(--text); letter-spacing: -.005em; }
.section-sub   { margin-top: 2px; font-size: 12px; color: var(--text-3); }
.section-head  { display: flex; align-items: flex-end; justify-content: space-between; gap: 14px; margin-bottom: 12px; margin-top: 24px; }

/* Path tiles */
.path-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 24px; margin-top: 16px; }
.path-tile { position: relative; display: flex; align-items: flex-start; gap: 14px; padding: 18px 20px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); cursor: pointer; text-align: left; width: 100%; font-family: inherit; color: inherit; transition: border-color .15s, background .15s, box-shadow .15s; }
.path-tile:hover { border-color: var(--border-dark); box-shadow: var(--shadow-sm); }
.path-tile.is-selected { background: var(--badge-bg-gold); border-color: var(--gold-dark); }
.path-tile-icon { width: 36px; height: 36px; border-radius: var(--radius-sm); background: var(--badge-bg-gold); color: var(--gold-dark); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.path-tile.is-selected .path-tile-icon { background: var(--gold-dark); color: #fff; }
.path-tile-title { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 3px; }
.path-tile-sub   { font-size: 12px; color: var(--text-3); line-height: 1.45; }
.path-tile-check { position: absolute; top: 12px; right: 12px; width: 20px; height: 20px; border-radius: 50%; background: var(--gold-dark); color: #fff; display: inline-flex; align-items: center; justify-content: center; }

/* Team row */
.team-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 10px; margin-bottom: 28px; }
.team-chip { display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); }
.team-chip-empty { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: transparent; border: 1px dashed var(--border-dark); border-radius: var(--radius); }

/* Grid */
.grid-head { display: grid; grid-template-columns: 240px 80px minmax(150px,1.2fr) minmax(150px,1.3fr) 120px 44px; align-items: center; background: var(--surface-3); border-bottom: 1px solid var(--border); padding: 12px 18px; }
.grid-head-cell { font-size: 10px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: var(--text-3); }
.grid-row { display: grid; grid-template-columns: 240px 80px minmax(150px,1.2fr) minmax(150px,1.3fr) 120px 44px; align-items: center; padding: 14px 18px; border-bottom: 1px solid var(--border); position: relative; min-height: 76px; transition: background .15s; }
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
.steward-mini-av { width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 700; color: #fff; font-family: var(--font-serif); }
.steward-mini-av-cs { background: var(--gold-dark); }
.steward-mini-av-ss { background: var(--text-3); }

/* Signed card */
.sig-card { margin-top: 22px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); }
.sig-card.is-signed { border-color: var(--green-dark); }
.signed-meta { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; padding: 20px 24px; }
.signed-meta-item { border-left: 2px solid var(--border); padding-left: 12px; }
.signed-meta-label { font-size: 10px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: var(--text-3); margin-bottom: 4px; }
.signed-meta-value { font-family: var(--font-serif); font-size: 16px; font-weight: 600; color: var(--text); }

/* Template grid */
.tpl-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; }
.tpl-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px 18px 14px; display: flex; flex-direction: column; gap: 12px; cursor: pointer; transition: border-color .15s, box-shadow .15s; }
.tpl-card:hover { border-color: var(--gold-dark); box-shadow: var(--shadow-sm); }

/* Spin */
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin .7s linear infinite; }
</style>
