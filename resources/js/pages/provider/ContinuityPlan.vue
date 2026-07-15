<template>
  <AppLayout :user="auth?.user" portal="practitioner" activePage="continuity-plan" pageTitle="Continuity Plan">

    <!-- HERO -->
    <AegisHeroBanner eyebrow="Continuity Planning" :title="'My Continuity Plan'"
      :subtitle="plan ? `Version ${plan.plan_version ?? 1} · ${planStatusLabel}` : 'Build your plan below'" quiet>
      <template #actions>
        <button type="button" class="btn-hero-ghost is-on-light" style="display:inline-flex;align-items:center;gap:6px;" @click="showSectionsModal = true">
          <AegisIcon name="check-circle" :size="14" /> {{ completedSections }}/7 Sections Complete
        </button>

        <!-- No plan → Create Draft Plan only -->
        <button v-if="!plan || !plan.id" type="button" class="btn-hero-solid is-on-light" style="display:inline-flex;align-items:center;gap:6px;" @click="createDraft">
          <AegisIcon name="plus" :size="14" /> Create Draft Plan
        </button>

        <!-- isDraft → Finalize & Sign only (opens Plan Readiness if !canSign, opens SignPlanModal if canSign) -->
        <button v-else-if="isDraft" type="button" class="btn-hero-solid is-on-light" style="display:inline-flex;align-items:center;gap:6px;"
          @click="canSign ? (showSignModal = true) : (showSectionsModal = true)">
          <AegisIcon name="edit" :size="14" /> Finalize &amp; Sign
        </button>

        <!-- annualReviewOverdue + no review in progress → Begin Annual Review only (gold) -->
        <button v-else-if="annualReviewOverdue && !reviewInProgress" type="button" class="btn-hero-solid is-on-light" style="display:inline-flex;align-items:center;gap:6px;background:var(--gold-dark);" @click="showAnnualReview = true">
          <AegisIcon name="refresh-cw" :size="14" /> Begin Annual Review
        </button>

        <!-- annualReviewOverdue + review in progress → Continue Review Draft only (gold) -->
        <button v-else-if="annualReviewOverdue && reviewInProgress" type="button" class="btn-hero-solid is-on-light" style="display:inline-flex;align-items:center;gap:6px;background:var(--gold-dark);" @click="showAnnualReview = true">
          <AegisIcon name="refresh-cw" :size="14" /> Continue Review Draft
        </button>

        <!-- else: plan active, review not due → Activity link only -->
        <a v-else :href="route('provider.activity') + '?module=plan'" class="btn-hero-ghost is-on-light" style="display:inline-flex;align-items:center;gap:6px;">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
      </template>
    </AegisHeroBanner>

    <!-- STAT CHIPS -->
    <!-- STAT CHIPS ROW 1 — counts -->
    <div class="stat-chips-row" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:8px;">
      <button type="button" class="stat-chip-btn" style="width:100%" data-tooltip="View your plan readiness checklist" @click="showSectionsModal = true">
        <AegisStatChip
          style="width:100%"
          icon="shield"
          :value="`${completedSections}/7`"
          label="Sections Complete"
          :bg-color="completedSections === 7 ? 'var(--icon-bg-green)' : 'var(--icon-bg-gold)'"
          :icon-color="completedSections === 7 ? 'var(--green-dark)' : 'var(--gold-dark)'"
        />
      </button>
      <span data-tooltip="Active Continuity Stewards on this plan" style="display:block">
        <AegisStatChip style="width:100%" icon="users" :value="csCount" label="Continuity Stewards" />
      </span>
      <span data-tooltip="Active Support Stewards on this plan" style="display:block">
        <AegisStatChip style="width:100%" icon="user-check" :value="ssCount" label="Support Stewards" />
      </span>
    </div>

    <!-- STAT CHIPS ROW 2 — dates (only show when plan exists) -->
    <div v-if="plan" class="stat-chips-row" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
      <span :data-tooltip="plan.vault_attested_at ? 'Vault last verified on ' + formatDate(plan.vault_attested_at) : 'Vault has not been attested yet'" style="display:block">
        <AegisStatChip
          style="width:100%"
          icon="lock"
          :value="plan.vault_attested_at ? formatDate(plan.vault_attested_at) : 'Not attested'"
          label="Last Vault Attestation"
          bg-color="var(--icon-bg-gold)"
          icon-color="var(--gold-dark)"
        />
      </span>
      <span v-if="plan.signed_at" data-tooltip="Date this plan was last signed and activated" style="display:block">
        <AegisStatChip
          style="width:100%"
          icon="file-check"
          :value="formatDate(plan.signed_at)"
          label="Last Review Signed"
          bg-color="var(--icon-bg-green)"
          icon-color="var(--green-dark)"
        />
      </span>
      <span v-if="plan.signed_at"
        :data-tooltip="plan.annual_review_date && new Date(plan.annual_review_date) < new Date()
          ? 'Annual review is overdue — begin your review now'
          : plan.annual_review_date ? 'Annual review due ' + formatDate(plan.annual_review_date) : 'No review date set'"
        style="display:block">
        <AegisStatChip
          style="width:100%"
          icon="calendar"
          :value="plan.annual_review_date ? formatDate(plan.annual_review_date) : '—'"
          label="Next Review Due"
          :bg-color="plan.annual_review_date && new Date(plan.annual_review_date) < new Date() ? 'var(--icon-bg-red)' : 'var(--icon-bg-gold)'"
          :icon-color="plan.annual_review_date && new Date(plan.annual_review_date) < new Date() ? 'var(--red-dark)' : 'var(--gold-dark)'"
        />
      </span>
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

      <!-- STATUS BANNER — one banner, content switches on state -->
      <!-- isDraft -->
      <div v-if="isDraft" class="alert alert-info" style="margin-bottom:16px">
        <div class="alert-icon"><AegisIcon name="info" :size="16" /></div>
        <div class="alert-content">Draft plan in progress. Complete all sections and sign to activate.</div>
      </div>

      <!-- isActive + not overdue + no review in progress → green signed banner -->
      <div v-else-if="isActive && !annualReviewOverdue && !reviewInProgress" class="alert alert-success" style="margin-bottom:16px">
        <AegisIcon name="check-circle" :size="16" />
        <div style="flex:1">
          <strong>Plan Signed &amp; Active</strong>
          <span style="margin-left:8px;font-weight:400;opacity:.85">
            v{{ plan.plan_version }} · Signed {{ formatDate(plan.signed_at) }}
            <span v-if="plan.signature_name"> by {{ plan.signature_name }}</span>
          </span>
        </div>
        <button type="button" class="btn btn-outline" style="flex-shrink:0;font-size:12px;padding:4px 12px;height:auto" @click="showSignedDetails = true">
          <AegisIcon name="info" :size="12" /> Details
        </button>
      </div>

      <!-- annualReviewOverdue + no review in progress → yellow: review due -->
      <div v-else-if="annualReviewOverdue && !reviewInProgress" class="alert alert-warning" style="margin-bottom:16px">
        <AegisIcon name="alert-triangle" :size="16" />
        <div>
          <strong>Annual review due {{ formatDate(plan.annual_review_date) }}.</strong>
          Your plan remains active until re-signed.
        </div>
      </div>

      <!-- annualReviewOverdue + review in progress → blue: review draft in progress -->
      <div v-else-if="annualReviewOverdue && reviewInProgress" class="alert alert-info" style="margin-bottom:16px">
        <div class="alert-icon"><AegisIcon name="refresh-cw" :size="16" /></div>
        <div class="alert-content">
          <strong>Review draft v{{ plan.plan_version + 1 }} in progress.</strong> Sign it to complete your annual review.
        </div>
      </div>

      <!-- ═══ BUILD PANE ═══ -->
      <div>

        <!-- SIGN CARD — state machine (spec §SIGN CARD) -->

        <!-- ALERT ROW — below banner, zero or one alert -->
        <!-- isDraft + !canSign → blue info: incomplete blocking sections -->
        <div v-if="isDraft && !canSign" class="alert alert-info" style="margin-bottom:16px">
          <div class="alert-icon"><AegisIcon name="info" :size="14" /></div>
          <div class="alert-content" style="font-size:13px">
            Complete required sections first:
            <strong>{{ planSections.filter(s => s.blocks_signing && !s.complete).map(s => s.title).join(', ') }}</strong>
          </div>
        </div>
        <!-- annualReviewOverdue states → no alert here, banner is sufficient -->

        <!-- isDraft OR (annualReviewOverdue + reviewInProgress) → full sign card -->
        <div v-if="isDraft || (annualReviewOverdue && reviewInProgress)" class="sign-cta">
          <div class="alert alert-warning">
            <AegisIcon name="alert-triangle" :size="16" />
            <div>By signing, you confirm this plan is accurate and authorize your stewards to act as described when a critical incident occurs.</div>
          </div>
          <div class="sign-cta-actions">
            <!-- Vault attestation: show button if not yet attested; show confirmation text if attested -->
            <button v-if="!plan.vault_attested_at" type="button" class="btn btn-outline" @click="showAttestModal = true">
              <AegisIcon name="check-circle" :size="13" /> Attest Vault
            </button>
            <span v-else style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--green-dark)">
              <AegisIcon name="check-circle" :size="14" /> Vault Attested
            </span>
            <!-- Finalize & Sign: always active — never disabled -->
            <button type="button" class="btn btn-primary"
              @click="canSign ? (showSignModal = true) : (showSectionsModal = true)">
              <AegisIcon name="edit" :size="13" /> Finalize &amp; Sign
            </button>
          </div>
        </div>

        <!-- isActive + annualReviewOverdue + !reviewInProgress → yellow info: begin review -->
        <div v-else-if="isActive && annualReviewOverdue && !reviewInProgress" class="alert alert-warning" style="margin-bottom:16px">
          <div class="alert-icon"><AegisIcon name="alert-triangle" :size="16" /></div>
          <div class="alert-content">
            Your plan is active. Click <strong>Begin Annual Review</strong> above to start your review.
          </div>
        </div>

        <!-- isActive + !annualReviewOverdue → green success card -->
        <div v-else-if="isActive && !annualReviewOverdue" class="alert alert-success" style="margin-bottom:16px">
          <div class="alert-icon"><AegisIcon name="check-circle" :size="16" /></div>
          <div class="alert-content">Plan signed and active. No action needed unless your circumstances change.</div>
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

        <!-- Incident cards — one per type -->
        <div class="incident-cards">
          <div
            v-for="type in incidentTypes"
            :key="type.value"
            class="incident-card"
            :class="isEnabled(type.value) ? 'is-enabled' : 'is-disabled'"
          >
            <!-- Left accent bar -->
            <div class="ic-bar" :style="isEnabled(type.value) ? 'background:var(--gold-dark)' : 'background:var(--border)'"></div>

            <!-- Header row: icon + name + badges + toggle + configure btn -->
            <div class="ic-header">
              <div class="ic-icon" :style="isEnabled(type.value) ? 'background:var(--badge-bg-gold);color:var(--gold-dark)' : 'background:var(--surface-3);color:var(--text-4)'">
                <AegisIcon :name="incidentIcon(type.value)" :size="18" />
              </div>
              <div style="flex:1;min-width:0">
                <div class="ic-name">{{ type.label }}</div>
                <div style="display:flex;align-items:center;gap:5px;margin-top:3px">
                  <span v-if="!type.is_optin" class="micro-badge is-always">Always on</span>
                  <span v-else class="micro-badge is-optin">Opt-in</span>
                  <span class="micro-badge" :class="isEnabled(type.value) ? 'is-on' : 'is-off'">
                    {{ isEnabled(type.value) ? 'Enabled' : 'Disabled' }}
                  </span>
                </div>
              </div>
              <span :data-tooltip="!type.is_optin ? 'This incident type is always required and cannot be disabled' : undefined">
                <AegisToggle
                  :model-value="isEnabled(type.value)"
                  :disabled="!type.is_optin && isEnabled(type.value)"
                  @update:model-value="(val) => handleToggle(type, val)"
                />
              </span>
              <button
                v-if="isEnabled(type.value)"
                type="button"
                class="btn btn-outline"
                style="display:inline-flex;align-items:center;gap:5px;font-size:12px;flex-shrink:0"
                :data-tooltip="`Configure ${type.label}`"
                @click="openIncidentConfig(type)"
              >
                <AegisIcon name="edit" :size="13" /> Configure
              </button>
            </div>

            <!-- Body — 3 columns: docs / stewards / tasks -->
            <div v-if="isEnabled(type.value)" class="ic-body">

              <!-- Docs -->
              <div class="ic-section">
                <div class="ic-section-label"><AegisIcon name="file-text" :size="12" /> Docs Required</div>
                <div v-if="getConfig(type.value)?.docs_required?.length" style="display:flex;flex-wrap:wrap;gap:5px;margin-top:6px">
                  <span v-for="doc in getConfig(type.value).docs_required" :key="doc" class="doc-chip">
                    <AegisIcon name="check" :size="10" />{{ docLabel(doc) }}
                  </span>
                </div>
                <span v-else class="ic-empty">None required</span>
              </div>

              <!-- Stewards -->
              <div class="ic-section">
                <div class="ic-section-label"><AegisIcon name="users" :size="12" /> Authorized Stewards</div>
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px">
                  <a v-for="sid in (getConfig(type.value)?.authorized_ss_ids ?? []).filter(id => getStewardById(id))" :key="'ss-'+sid"
                    :href="getStewardSlug(sid) ? route('public.ss', { slug: getStewardSlug(sid) }) : '#'"
                    class="steward-mini" style="text-decoration:none">
                    <span class="steward-mini-av steward-mini-av-ss" style="overflow:hidden;padding:0">
                      <img v-if="getStewardPhoto(sid)" :src="getStewardPhoto(sid)" style="width:100%;height:100%;object-fit:cover" />
                      <span v-else>{{ getStewardInitials(sid) }}</span>
                    </span>
                    {{ getStewardFirstName(sid) }}
                  </a>
                  <a v-for="cid in (getConfig(type.value)?.authorized_cs_ids ?? []).filter(id => getStewardById(id))" :key="'cs-'+cid"
                    :href="getStewardSlug(cid) ? route('public.cs', { slug: getStewardSlug(cid) }) : '#'"
                    class="steward-mini" style="text-decoration:none">
                    <span class="steward-mini-av steward-mini-av-cs" style="overflow:hidden;padding:0">
                      <img v-if="getStewardPhoto(cid)" :src="getStewardPhoto(cid)" style="width:100%;height:100%;object-fit:cover" />
                      <span v-else>{{ getStewardInitials(cid) }}</span>
                    </span>
                    {{ getStewardFirstName(cid) }}
                  </a>
                  <AegisBadge
                    v-if="isEnabled(type.value) && !(getConfig(type.value)?.authorized_ss_ids ?? []).some(id => getStewardById(id)) && !(getConfig(type.value)?.authorized_cs_ids ?? []).some(id => getStewardById(id))"
                    variant="warning"
                    label="No stewards assigned"
                  />
                </div>
              </div>

              <!-- Tasks -->
              <div class="ic-section">
                <div class="ic-section-label"><AegisIcon name="check-square" :size="12" /> Tasks</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px">
                  <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-xs);padding:4px 10px;color:var(--text-2);white-space:nowrap">
                    <AegisIcon name="user-check" :size="11" style="color:var(--text-3);flex-shrink:0" />
                    <span style="font-weight:700;color:var(--text)">{{ ssTaskCount(type.value) }}</span> SS tasks
                  </span>
                  <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;background:var(--badge-bg-gold);border:1px solid var(--gold-light,var(--border));border-radius:var(--radius-xs);padding:4px 10px;color:var(--gold-dark);white-space:nowrap">
                    <AegisIcon name="shield" :size="11" style="flex-shrink:0" />
                    <span style="font-weight:700">{{ csTaskCount(type.value) }}</span> CS tasks
                  </span>
                </div>
              </div>

            </div><!-- /ic-body -->

            <!-- Disabled state footer -->
            <div v-else class="ic-disabled-hint">
              <AegisIcon name="toggle-left" :size="13" style="color:var(--text-4)" />
              Toggle on to configure this incident type
            </div>

          </div>
        </div>

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
        <div class="alert-icon"><AegisIcon name="calendar" :size="14" /></div>
        <div>Next annual review due <strong>{{ formatDate(plan.annual_review_date) }}</strong>. You will be reminded 30 days before it's due.</div>
      </div>
      <div v-if="plan.signature_agreed" style="margin-top:16px;padding:14px 16px;background:var(--surface-2);border-radius:var(--radius-sm);border:1px solid var(--border)">
        <div style="font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--text-3);margin-bottom:6px">Signatory Agreement</div>
        <div style="font-size:12px;color:var(--text-2);line-height:1.6">I confirm this plan is accurate and authorize my designated stewards to act as described when a critical incident occurs. This plan supersedes all prior versions.</div>
      </div>
      <template #footer>
        <!-- annualReviewOverdue + !reviewInProgress → Begin Annual Review + Close -->
        <template v-if="annualReviewOverdue && !reviewInProgress">
          <button type="button" class="btn btn-outline" @click="showSignedDetails = false">Close</button>
          <button type="button" class="btn btn-primary" @click="showSignedDetails = false; showAnnualReview = true">
            <AegisIcon name="refresh-cw" :size="13" /> Begin Annual Review
          </button>
        </template>
        <!-- annualReviewOverdue + reviewInProgress → Continue Review Draft + Close -->
        <template v-else-if="annualReviewOverdue && reviewInProgress">
          <button type="button" class="btn btn-outline" @click="showSignedDetails = false">Close</button>
          <button type="button" class="btn btn-primary" @click="showSignedDetails = false; showAnnualReview = true">
            <AegisIcon name="refresh-cw" :size="13" /> Continue Review Draft
          </button>
        </template>
        <!-- all other states → Close only -->
        <template v-else>
          <button type="button" class="btn btn-outline" @click="showSignedDetails = false">Close</button>
        </template>
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
import { ref, computed, reactive, watch, onMounted} from 'vue'
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
  canSign:             { type: Boolean, default: false },
  canActivate:         { type: Boolean, default: false },
  hasDraftInProgress:  { type: Boolean, default: false },
  tierLimits:          { type: Object,  default: () => ({}) },
  auth:                { type: Object,  default: null },
})

// ── State ──────────────────────────────────────────────────────────────────────
const showSignModal      = ref(false)
const showSectionsModal  = ref(false)
const showAttestModal    = ref(false)
const showIncidentConfig = ref(false)
const showAnnualReview   = ref(false)

// ── Auto-open modal from ?action= query param ─────────────────────────────────
onMounted(() => {
  const action = new URLSearchParams(window.location.search).get('action')
  if (action === 'begin_review') showAnnualReview.value = true
  if (action === 'sign') {
    if (canSign.value) showSignModal.value = true
    else showSectionsModal.value = true
  }
  if (action) window.history.replaceState({}, '', window.location.pathname)
})
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

// ── State-machine computed helpers (spec §FINAL STATE MACHINE FIX) ────────────
const annualReviewOverdue = computed(() =>
  props.plan?.annual_review_date && new Date(props.plan.annual_review_date) < new Date()
)
const isDraft         = computed(() => props.plan?.status === 'draft')
const isActive        = computed(() => props.plan?.status === 'active' || props.plan?.status === 'annual_review_due')
const reviewInProgress = computed(() => props.hasDraftInProgress)

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
const DOC_LABELS = {
  death_certificate:      'Death Certificate',
  doctors_note:           "Doctor's Note / Medical Certificate",
  medical_documentation:  'Medical Documentation',
  hospitalization_record: 'Hospitalization Record',
  leave_documentation:    'Leave / Absence Documentation',
  police_report:          'Police Report',
  legal_documentation:    'Legal Documentation',
  insurance_documentation:'Insurance Documentation',
  government_id:          'Government ID',
  power_of_attorney:      'Power of Attorney',
  other:                  'Other',
}
function docLabel(v) {
  return DOC_LABELS[v] ?? v.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
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
.incident-cards { display:flex;flex-direction:column;gap:12px; }

.incident-card {
  display:flex;
  flex-direction:column;
  border:1px solid var(--border);
  border-radius:var(--radius);
  background:var(--surface);
  overflow:hidden;
  transition: box-shadow .15s, border-color .15s;
  position:relative;
}
.incident-card.is-enabled { border-color: var(--border); }
.incident-card.is-enabled:hover { box-shadow: 0 2px 12px rgba(0,0,0,.07); }
.incident-card.is-disabled { background:var(--surface-2); }
.incident-card.is-disabled .ic-name { opacity:.55; }

.ic-bar { position:absolute;left:0;top:0;bottom:0;width:4px;flex-shrink:0; }

.ic-header {
  display:flex;
  align-items:center;
  gap:14px;
  padding:16px 20px 16px 24px;
}
.ic-icon {
  width:38px;height:38px;border-radius:var(--radius-sm);
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;
}
.ic-name { font-size:14px;font-weight:700;color:var(--text);font-family:var(--font-serif); }

.ic-body {
  display:grid;
  grid-template-columns:1fr 1fr 1fr;
  gap:0;
  border-top:1px solid var(--border);
  padding:0;
}
.ic-section {
  padding:14px 20px 14px 24px;
  border-right:1px solid var(--border);
}
.ic-section:last-child { border-right:none; }
.ic-section-label {
  display:flex;align-items:center;gap:5px;
  font-size:10px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;
  color:var(--text-3);
}
.ic-empty { font-size:12px;color:var(--text-4);font-style:italic;margin-top:6px;display:block; }
.ic-disabled-hint {
  display:flex;align-items:center;gap:7px;
  padding:12px 20px 12px 24px;
  border-top:1px solid var(--border);
  font-size:12px;color:var(--text-4);
  background:var(--surface-3);
}
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
.stat-chip-btn { background: none; border: none; padding: 0; cursor: pointer; display: block; width: 100%; transition: transform .18s ease; border-radius: var(--radius); }
.stat-chip-btn:hover { transform: translateY(-3px); }
.stat-chip-btn .stat-chip { width: 100%; }

/* Spin */
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin .7s linear infinite; }
</style>
