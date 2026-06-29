<!--
  pages/provider/Dashboard.vue — Provider portal dashboard.
  100% parity with dashboard.php. All sections, all modals, all dynamic data.
-->
<template>
  <AppLayout>
    <Head title="Dashboard — Aegis" />

    <div class="page-body-inner">

      <!-- ══ 0. ACTIVE INCIDENT BANNER ════════════════════════════ -->
      <div v-if="activeIncident" class="alert alert-emergency" style="margin-bottom:18px">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">
            <template v-if="activeIncident.status === 'active'">Continuity Plan Active</template>
            <template v-else-if="activeIncident.status === 'verified'">Incident Verified — Awaiting Activation</template>
            <template v-else>Incident Reported — Awaiting Steward Verification</template>
            — {{ activeIncident.incident_type_label ?? activeIncident.incident_type }}
          </div>
          <div>
            <template v-if="activeIncident.status === 'active'">A critical incident is currently being managed by your stewards</template>
            <template v-else-if="activeIncident.status === 'verified'">Your Continuity Steward has verified this incident</template>
            <template v-else>Your stewards have been notified and will verify shortly</template>{{ activeIncident.reported_at ? ' · reported ' + formatDate(activeIncident.reported_at) : '' }}.
          </div>
          <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
            <Link :href="route('activity.index')" class="btn btn-primary btn-sm">
              <AegisIcon name="activity" :size="14" /> View Incident Activity
            </Link>
            <Link :href="route('provider.plan.index')" class="btn btn-outline btn-sm">
              <AegisIcon name="file-text" :size="14" /> Open Continuity Plan
            </Link>
          </div>
        </div>
      </div>

      <!-- ══ 1. GREETING (dh-greet) ════════════════════════════════ -->
      <div class="dh-greet">
        <div>
          <div class="dh-greet-eyebrow">Good morning</div>
          <div class="dh-greet-title">{{ greetFirst }} <em>{{ greetLast }},</em></div>
          <div class="dh-greet-sub">{{ greetSub }}</div>
        </div>
        <div class="dh-greet-meta">
          <div class="dh-greet-mcell">
            <div class="dh-greet-mlabel">Plan Status</div>
            <div class="dh-greet-mval" :class="{ ok: planStatus === 'active' }">
              <AegisIcon :name="planStatus === 'active' ? 'shield-check' : 'clock'" :size="14" />
              {{ planStatus === 'active' ? 'Active' : planStatusLabel }}
            </div>
          </div>
          <div class="dh-greet-mcell">
            <div class="dh-greet-mlabel">Practices</div>
            <div class="dh-greet-mval">{{ stats.net_clinical + stats.net_business }} providers</div>
          </div>
          <div class="dh-greet-mcell">
            <div class="dh-greet-mlabel">Avg Response</div>
            <div class="dh-greet-mval">{{ stats.avg_response_h > 0 ? stats.avg_response_h + 'h' : '—' }}</div>
          </div>
        </div>
      </div>

      <!-- ══ 2. OVERVIEW BANNER ════════════════════════════════════ -->
      <div class="dh-overview-banner">
        <div class="dh-overview-icon"><AegisIcon name="book-open" :size="20" /></div>
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:3px">Overview — Start Here</div>
          <div style="font-size:13px;color:var(--text-3);line-height:1.5">Key terms, your role on Aegis, and FAQs.</div>
        </div>
        <Link :href="route('overview')" class="btn btn-outline btn-sm" style="flex-shrink:0;white-space:nowrap">
          View Overview <AegisIcon name="chevron-right" :size="12" />
        </Link>
      </div>

      <!-- ══ 3. PLAN STATUS CHIPS ══════════════════════════════════ -->
      <div class="card" style="margin-bottom:22px;padding:18px 20px;display:flex;align-items:center;gap:20px;flex-wrap:wrap">
        <!-- Icon + label -->
        <div style="flex:0 0 auto;display:flex;align-items:center;gap:10px">
          <div style="width:36px;height:36px;border-radius:var(--radius-sm);background:var(--icon-bg-gold);color:var(--gold-dark);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <AegisIcon name="shield-check" :size="18" />
          </div>
          <div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-3)">Continuity Plan Status</div>
            <div style="font-size:13px;color:var(--text-2);margin-top:2px">Attestation state across stewards</div>
          </div>
        </div>
        <!-- Chips -->
        <div style="flex:1;display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end">
          <!-- Chip 1: Plan Active -->
          <div class="stat-chip" :style="attest.plan_active ? 'background:var(--badge-bg-green);border-color:var(--green)' : 'background:var(--surface-2);border:1px solid var(--border)'">
            <div class="stat-chip-icon" :style="'background:' + (attest.plan_active ? 'var(--green)' : 'var(--text-4)') + ';color:#fff'">
              <AegisIcon :name="attest.plan_active ? 'check' : 'clock'" :size="14" />
            </div>
            <div>
              <div class="stat-chip-value" style="font-size:13px;font-weight:700">{{ attest.plan_active ? 'Plan Active' : 'Plan Pending' }}</div>
              <div class="stat-chip-label" style="font-size:11px;color:var(--text-3)">
                {{ attest.plan_active && attest.plan_signed_at ? 'Signed ' + formatDate(attest.plan_signed_at) : 'Awaiting signature' }}
              </div>
            </div>
          </div>
          <!-- Chip 2: SS Certified -->
          <div class="stat-chip" :style="attest.ss_certified ? 'background:var(--badge-bg-green);border-color:var(--green)' : 'background:var(--surface-2);border:1px solid var(--border)'">
            <div class="stat-chip-icon" :style="'background:' + (attest.ss_certified ? 'var(--green)' : 'var(--text-4)') + ';color:#fff'">
              <AegisIcon :name="attest.ss_certified ? 'check' : 'clock'" :size="14" />
            </div>
            <div>
              <div class="stat-chip-value" style="font-size:13px;font-weight:700">SS Certified</div>
              <div class="stat-chip-label" style="font-size:11px;color:var(--text-3)">
                {{ attest.ss_certified_count }} of {{ attest.ss_total }}{{ attest.ss_latest ? ' · ' + formatShortDate(attest.ss_latest) : '' }}
              </div>
            </div>
          </div>
          <!-- Chip 3: CS Certified -->
          <div class="stat-chip" :style="attest.cs_certified ? 'background:var(--badge-bg-green);border-color:var(--green)' : 'background:var(--surface-2);border:1px solid var(--border)'">
            <div class="stat-chip-icon" :style="'background:' + (attest.cs_certified ? 'var(--green)' : 'var(--text-4)') + ';color:#fff'">
              <AegisIcon :name="attest.cs_certified ? 'check' : 'clock'" :size="14" />
            </div>
            <div>
              <div class="stat-chip-value" style="font-size:13px;font-weight:700">CS Certified</div>
              <div class="stat-chip-label" style="font-size:11px;color:var(--text-3)">
                {{ attest.cs_certified_count }} of {{ attest.cs_total }}{{ attest.cs_latest ? ' · ' + formatShortDate(attest.cs_latest) : '' }}
              </div>
            </div>
          </div>
        </div>
        <!-- Second row: Support Team + MAAT -->
        <div style="flex:0 0 100%;display:flex;gap:14px;align-items:center;flex-wrap:wrap;border-top:1px solid var(--border);padding-top:14px;margin-top:4px">
          <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:240px">
            <div style="width:28px;height:28px;border-radius:var(--radius-sm);background:var(--icon-bg-gold);color:var(--gold-dark);display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <AegisIcon name="users" :size="14" />
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-3)">Support Team</div>
              <div style="font-size:13px;color:var(--text);margin-top:2px">
                <strong>{{ activeStewardCount }} of 5</strong> designated
                <span style="color:var(--text-3);font-weight:400">· Primary &amp; Alternate Stewards</span>
              </div>
            </div>
            <Link :href="route('provider.stewards.index')" class="btn btn-outline btn-sm" style="flex-shrink:0">Manage</Link>
          </div>
          <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:240px">
            <div :style="'width:28px;height:28px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;flex-shrink:0;background:' + (maatActive ? 'var(--badge-bg-green)' : 'var(--surface-2)') + ';color:' + (maatActive ? 'var(--green-dark)' : 'var(--text-4)') + ';border:1px solid ' + (maatActive ? 'var(--green)' : 'var(--border)')">
              <AegisIcon name="shield-check" :size="14" />
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-3)">MAAT Continuity Steward Service</div>
              <div style="font-size:13px;color:var(--text);margin-top:2px">
                <strong :style="maatActive ? 'color:var(--green-dark)' : 'color:var(--text-3)'">{{ maatActive ? 'Active' : 'Not active' }}</strong>
                <span style="color:var(--text-3);font-weight:400">{{ maatActive ? ' · $29/mo' : ' · Optional add-on' }}</span>
              </div>
            </div>
            <Link :href="route('provider.settings.index')" class="btn btn-outline btn-sm" style="flex-shrink:0">{{ maatActive ? 'Manage' : 'Learn More' }}</Link>
          </div>
        </div>
      </div>

      <!-- ══ 4. CONTINUITY HERO ════════════════════════════════════ -->
      <div class="dh-sh">
        <div class="dh-sh-l">
          <div class="dh-sh-eyebrow">Your Aegis</div>
          <div class="dh-sh-title">Continuity at the center</div>
        </div>
        <Link :href="route('provider.stewards.index')" class="dh-sh-link">
          Manage stewards <AegisIcon name="arrow-right-line" :size="12" />
        </Link>
      </div>

      <div class="dh-continuity">
        <div class="dh-cn-left">
          <div class="dh-cn-eyebrow">
            <span class="dh-cn-pulse"></span>
            Continuity Plan · Active since {{ planSince }}
          </div>
          <div class="dh-cn-title">Your practice continues,<br>even when you can't.</div>
          <div class="dh-cn-desc">When circumstances change, your Continuity Plan helps you keep care connected for your clients, records remain supported, and your stewards know what to do.</div>

          <div class="dh-cn-stewards">
            <div class="dh-cn-stew">
              <div class="dh-cn-savatar">{{ csInitials }}</div>
              <div class="dh-cn-stew-info">
                <div class="dh-cn-srole">Continuity Steward</div>
                <div class="dh-cn-sname">{{ csName }}</div>
              </div>
              <div class="dh-cn-stat">{{ primaryCs?.status === 'active' ? 'Active' : 'Pending' }}</div>
            </div>
            <div class="dh-cn-stew">
              <div class="dh-cn-savatar support">{{ ssInitials }}</div>
              <div class="dh-cn-stew-info">
                <div class="dh-cn-srole">Support Steward</div>
                <div class="dh-cn-sname">{{ ssName }}</div>
              </div>
              <div class="dh-cn-stat">{{ primarySs?.status === 'active' ? 'Monitoring' : 'Pending' }}</div>
            </div>
          </div>

          <div class="dh-cn-actions">
            <button class="btn btn-primary btn-sm" @click="modals.executorPanel = true">
              <AegisIcon name="eye" :size="13" /> View Plan Details
            </button>
            <Link :href="route('provider.stewards.index')" class="btn btn-outline btn-sm">
              <AegisIcon name="pencil" :size="13" /> Edit Stewards
            </Link>
            <button v-if="!activeIncident" class="btn btn-danger btn-sm" data-tooltip="Use only during a genuine critical moment" @click="modals.activateSuccession = true">
              <AegisIcon name="alert-triangle" :size="13" />
            </button>
          </div>
        </div>

        <div class="dh-cn-right">
          <div class="dh-cn-rhead">
            <div>
              <div class="dh-cn-rtag">Annual review</div>
              <div class="dh-cn-due">
                Due {{ planReviewDue }}
                <small>{{ reviewDaysLabel }} · last attested {{ planSignedAt }}</small>
              </div>
            </div>
            <span style="color:var(--gold-dark);flex-shrink:0;display:inline-flex;align-items:center">
              <AegisIcon name="calendar" :size="22" />
            </span>
          </div>

          <div class="dh-cn-bar">
            <div class="dh-cn-bar-fill"></div>
            <div class="dh-cn-bar-marker" data-tooltip="Today"></div>
          </div>
          <div class="dh-cn-bar-labels">
            <span>Last attested</span><span>Today</span><span>Due</span>
          </div>

          <div class="dh-cn-todos">
            <div class="dh-cn-todo done"><AegisIcon name="check-circle" :size="13" /> Steward contact info verified</div>
            <div class="dh-cn-todo done"><AegisIcon name="check-circle" :size="13" /> Practice information current</div>
            <div class="dh-cn-todo"><AegisIcon name="clock" :size="13" /> Confirm Support Steward task list</div>
            <div class="dh-cn-todo"><AegisIcon name="clock" :size="13" /> Attest Continuity Plan accuracy</div>
          </div>

          <button class="btn btn-outline btn-sm" style="align-self:flex-start;margin-top:8px" @click="modals.annualReview = true">
            <AegisIcon name="clipboard-check" :size="13" /> Begin Annual Review
          </button>
        </div>
      </div>

      <!-- ══ 5. AT-A-GLANCE ════════════════════════════════════════ -->
      <div class="dh-sh">
        <div class="dh-sh-l">
          <div class="dh-sh-eyebrow">This month</div>
          <div class="dh-sh-title">Practice at a glance</div>
        </div>
        <Link :href="route('activity.index')" class="dh-sh-link">
          View activity <AegisIcon name="arrow-right-line" :size="12" />
        </Link>
      </div>

      <div class="dh-glance">
        <div class="dh-gl-card">
          <div class="dh-gl-head"><div class="dh-gl-label">Referrals</div><div class="dh-gl-icon"><AegisIcon name="refresh" :size="14" /></div></div>
          <div class="dh-gl-val">{{ stats.total_refs }}</div>
          <div class="dh-gl-sub" v-html="stats.pending_refs > 0 ? '<strong>' + stats.pending_refs + ' pending</strong>' : 'All up to date'"></div>
        </div>
        <div class="dh-gl-card">
          <div class="dh-gl-head"><div class="dh-gl-label">Network</div><div class="dh-gl-icon"><AegisIcon name="users" :size="14" /></div></div>
          <div class="dh-gl-val">{{ stats.net_clinical + stats.net_business }}</div>
          <div class="dh-gl-sub">{{ stats.net_clinical }} clinical · {{ stats.net_business }} business</div>
        </div>
        <div class="dh-gl-card">
          <div class="dh-gl-head"><div class="dh-gl-label">CEU Progress</div><div class="dh-gl-icon warn"><AegisIcon name="graduation-cap" :size="14" /></div></div>
          <div class="dh-gl-val">{{ stats.ceus_total }}<small> hrs</small></div>
          <div class="dh-gl-sub">{{ stats.ceus_count }} entries this year</div>
        </div>
        <div class="dh-gl-card">
          <div class="dh-gl-head"><div class="dh-gl-label">Avg Response</div><div class="dh-gl-icon"><AegisIcon name="clock" :size="14" /></div></div>
          <div class="dh-gl-val">{{ stats.avg_response_h > 0 ? stats.avg_response_h : '—' }}<small v-if="stats.avg_response_h > 0">h</small></div>
          <div class="dh-gl-sub">average referral response</div>
        </div>
      </div>

      <!-- ══ 6. CREDENTIALS & COVERAGE ════════════════════════════ -->
      <div class="dh-sh">
        <div class="dh-sh-l">
          <div class="dh-sh-eyebrow">Compliance</div>
          <div class="dh-sh-title">Credentials &amp; coverage</div>
        </div>
        <button class="dh-sh-link" @click="modals.addLicense = true">
          Manage all <AegisIcon name="arrow-right-line" :size="12" />
        </button>
      </div>

      <div class="dh-cols">
        <!-- LEFT: Credentials -->
        <div class="dh-cred-card">
          <template v-if="credentials.length">
            <div
              v-for="cred in credentials"
              :key="cred.id"
              class="dh-cred-row"
            >
              <div :class="['dh-cred-icon', credIsCritical(cred) ? 'crit' : 'ok']">
                <AegisIcon :name="cred.icon || 'credit-card'" :size="16" />
              </div>
              <div class="dh-cred-info">
                <div class="dh-cred-title">{{ cred.name || cred.cred_type }}</div>
                <div class="dh-cred-sub">{{ cred.subtitle || (cred.issuer + (cred.number ? ' · ' + cred.number : '')) }}</div>
              </div>
              <div class="dh-cred-meter">
                <div :class="['dh-cred-date', credIsCritical(cred) ? 'crit' : '']">
                  {{ cred.expires_on ? formatDate(cred.expires_on) : 'No expiry' }}
                </div>
                <div class="dh-cred-bar">
                  <div :class="['dh-cred-bar-fill', credIsCritical(cred) ? 'crit' : 'ok']"
                       :style="'width:' + credBarWidth(cred) + '%'"></div>
                </div>
                <div :class="['dh-cred-days', credIsCritical(cred) ? 'crit' : 'ok']">
                  {{ credDaysLabel(cred) }}
                </div>
              </div>
              <div class="dh-cred-act">
                <button
                  v-if="credIsCritical(cred)"
                  class="btn-icon-sm btn-icon-danger"
                  data-tooltip="Update"
                  @click="cred.is_insurance ? openRenewInsurance(cred) : openRenewLicense(cred)"
                >
                  <AegisIcon name="refresh" :size="12" />
                </button>
                <button
                  class="btn-icon-sm"
                  :data-tooltip="cred.is_insurance ? 'View policy' : 'View details'"
                  @click="cred.is_insurance ? openInsuranceDetail(cred) : openLicenseDetail(cred)"
                >
                  <AegisIcon name="eye" :size="12" />
                </button>
                <button
                  v-if="!credIsCritical(cred)"
                  class="btn-icon-sm"
                  data-tooltip="Set reminder"
                  @click="modals.setReminder = true"
                >
                  <AegisIcon name="bell" :size="12" />
                </button>
              </div>
            </div>
          </template>
          <AegisEmptyState
            v-else
            icon="credit-card"
            title="No credentials on file"
            description="Add your licenses, DEA registration, and insurance policies."
          />
          <div class="dh-cred-foot">
            <span>
              <strong>{{ credentials.length }}</strong>
              credential{{ credentials.length === 1 ? '' : 's' }} tracked
              <template v-if="credCriticalCount > 0">
                · <span class="crit-text">{{ credCriticalCount }} need attention</span>
              </template>
            </span>
            <button class="btn btn-outline btn-sm" @click="modals.addLicense = true">
              <AegisIcon name="plus" :size="12" /> Add credential
            </button>
          </div>
        </div>

        <!-- RIGHT: Attention + Quick actions -->
        <div>
          <div class="dh-attention">
            <div class="dh-att-head">
              <div class="dh-att-icon"><AegisIcon name="bell" :size="14" /></div>
              <div class="dh-att-title">Needs attention</div>
              <div class="dh-att-count">3</div>
            </div>
            <div class="dh-att-list">
              <div class="dh-att-item">
                <div class="dh-att-bullet crit"></div>
                <div class="dh-att-text">
                  <div class="dh-att-h">Professional Liability update</div>
                  <div class="dh-att-d">Expires <strong>Mar 15</strong> · 20 days left</div>
                </div>
                <button class="btn btn-primary btn-sm" @click="modals.renewInsurance = true">Update Now</button>
              </div>
              <div class="dh-att-item">
                <div class="dh-att-bullet warn"></div>
                <div class="dh-att-text">
                  <div class="dh-att-h">Annual Continuity Plan review</div>
                  <div class="dh-att-d">Due <strong>Jun 15</strong> · attest 8 items</div>
                </div>
                <button class="btn btn-outline btn-sm" @click="modals.annualReview = true">Review</button>
              </div>
              <div class="dh-att-item">
                <div class="dh-att-bullet warn"></div>
                <div class="dh-att-text">
                  <div class="dh-att-h">12 CEU hours required</div>
                  <div class="dh-att-d">Ethics credits by <strong>Dec 31</strong></div>
                </div>
                <button class="btn btn-outline btn-sm" @click="modals.ceu = true">Add CEU</button>
              </div>
            </div>
          </div>

          <div class="dh-quick">
            <div class="dh-quick-title">Quick actions</div>
            <div class="dh-quick-grid">
              <button class="btn btn-outline btn-sm" @click="modals.newReferral = true">
                <AegisIcon name="refresh" :size="13" /> New referral
              </button>
              <Link :href="route('provider.vault.index')" class="btn btn-outline btn-sm">
                <AegisIcon name="upload" :size="13" /> Upload doc
              </Link>
              <Link :href="route('provider.stewards.index')" class="btn btn-outline btn-sm">
                <AegisIcon name="calendar" :size="13" /> Schedule
              </Link>
              <Link :href="route('messages.index')" class="btn btn-outline btn-sm">
                <AegisIcon name="message-square" :size="13" /> Message
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ 7. INTEGRATIVE NETWORK ═════════════════════════════════ -->
      <div class="dh-sh">
        <div class="dh-sh-l">
          <div class="dh-sh-eyebrow">My circle</div>
          <div class="dh-sh-title">Integrative network</div>
        </div>
        <Link :href="route('provider.network.index')" class="dh-sh-link">
          View all <AegisIcon name="arrow-right-line" :size="12" />
        </Link>
      </div>

      <div class="network-carousel-section">
        <div class="nw-head" style="border:none;padding:0;margin-bottom:14px">
          <div class="tabs-segmented">
            <button class="tab-pill" :class="{ active: nwTab === 'clinical' }" @click="nwTab = 'clinical'">Integrative</button>
            <button class="tab-pill" :class="{ active: nwTab === 'business' }" @click="nwTab = 'business'">Business Partners</button>
          </div>
        </div>

        <div class="nw-grid">
          <!-- Clinical tab -->
          <template v-if="nwTab === 'clinical'">
            <template v-if="netClinical.length">
              <div v-for="nc in netClinical" :key="nc.id" class="nw-card" @click="router.visit('/public/provider/' + (nc.target?.slug ?? ''))">
                <div class="nw-top">
                  <div class="nw-avatar">{{ nc.target?.avatar_initials ?? '??' }}</div>
                  <div class="nw-info">
                    <div class="nw-name">{{ nc.target?.display_name ?? '—' }}</div>
                    <div class="nw-role">{{ nc.target?.title ?? nc.target?.credentials ?? '' }}</div>
                  </div>
                  <div class="nw-tags">
                    <div class="nw-pill net" data-tooltip="In Network"><AegisIcon name="shield-check" :size="11" /></div>
                    <div v-if="nc.target?.services_mode" class="nw-pill svc" data-tooltip="Offers services on Aegis"><AegisIcon name="briefcase" :size="11" /></div>
                  </div>
                </div>
                <div class="nw-meta">
                  <span class="nw-meta-item"><AegisIcon name="map-pin" :size="11" />{{ nc.target?.location ?? '' }}</span>
                  <span class="nw-meta-item"><AegisIcon name="clock" :size="11" />Replies in {{ nc.target?.response_time_hours ? nc.target.response_time_hours + 'h' : '—' }}</span>
                  <div class="nw-cta" @click.stop>
                    <Link :href="route('messages.index')" class="nw-btn" data-tooltip="Send message"><AegisIcon name="message-square" :size="12" /></Link>
                    <a :href="'/public/provider/' + (nc.target?.slug ?? '')" class="nw-btn primary" data-tooltip="View profile"><AegisIcon name="arrow-right-line" :size="12" /></a>
                  </div>
                </div>
              </div>
            </template>
            <AegisEmptyState
              v-else
              icon="users"
              title="No network connections yet"
              description="Visit the Network to connect with practitioners."
              style="grid-column:1/-1"
            >
              <template #action>
                <Link :href="route('provider.network.index')" class="btn btn-primary btn-sm">Browse Network</Link>
              </template>
            </AegisEmptyState>
          </template>

          <!-- Business tab -->
          <template v-if="nwTab === 'business'">
            <template v-if="netBusiness.length">
              <div v-for="nc in netBusiness" :key="nc.id" class="nw-card" @click="router.visit('/public/business/' + (nc.target?.slug ?? ''))">
                <div class="nw-top">
                  <div class="nw-avatar">{{ nc.target?.avatar_initials ?? '??' }}</div>
                  <div class="nw-info">
                    <div class="nw-name">{{ nc.target?.display_name ?? '—' }}</div>
                    <div class="nw-role">{{ nc.target?.bp_type ? nc.target.bp_type.charAt(0).toUpperCase() + nc.target.bp_type.slice(1) : 'Partner' }}</div>
                  </div>
                  <div class="nw-tags">
                    <span v-if="nc.target?.bp_categories" class="nw-tag">{{ nc.target.bp_categories }}</span>
                  </div>
                </div>
                <div class="nw-meta">
                  <span class="nw-meta-item"><AegisIcon name="map-pin" :size="11" />{{ nc.target?.location ?? '' }}</span>
                  <span class="nw-meta-item"><AegisIcon name="clock" :size="11" />Replies in {{ nc.target?.response_time_hours ? nc.target.response_time_hours + 'h' : '—' }}</span>
                  <div class="nw-cta" @click.stop>
                    <Link :href="route('messages.index')" class="nw-btn" data-tooltip="Send message"><AegisIcon name="message-square" :size="12" /></Link>
                    <a :href="'/public/business/' + (nc.target?.slug ?? '')" class="nw-btn primary" data-tooltip="View profile"><AegisIcon name="arrow-right-line" :size="12" /></a>
                  </div>
                </div>
              </div>
            </template>
            <AegisEmptyState
              v-else
              icon="briefcase"
              title="No business partners yet"
              description="Connect with business partners through your network."
              style="grid-column:1/-1"
            />
          </template>
        </div>
      </div>



    </div><!-- /page-body-inner -->

    <!-- ══════════════════════ MODALS ═════════════════════════════════ -->

    <!-- Executor Panel -->
    <AegisModal v-model="modals.executorPanel" title="Continuity Plan — Details" size="lg">
      <div class="alert alert-success" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="shield-check" :size="16" /></div>
        <div class="alert-content">Continuity Plan is active and up to date</div>
      </div>

      <div class="section-label" style="margin-bottom:8px">Stewards</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
        <div v-if="primaryCs" style="display:flex;flex-direction:column;align-items:flex-start;gap:6px;padding:14px 0;border-bottom:1px solid var(--border)">
          <div style="display:flex;align-items:center;gap:10px;width:100%;flex-wrap:wrap">
            <div class="exec-avatar">{{ csInitials }}</div>
            <div style="flex:1;min-width:0">
              <div style="font-weight:700;font-size:13px">{{ csName }}</div>
              <div style="font-size:11px;color:var(--text-3)">{{ primaryCs?.steward?.title ?? '' }}</div>
            </div>
            <span class="exec-role-chip">Continuity Steward</span>
            <span :class="'badge badge--' + (primaryCs?.status === 'active' ? 'green' : 'orange')">{{ primaryCs?.status === 'active' ? 'Active' : 'Pending' }}</span>
          </div>
          <div style="font-size:11px;color:var(--text-3);width:100%">{{ primaryCs?.steward?.email ?? '' }}{{ primaryCs?.steward?.phone ? ' · ' + primaryCs.steward.phone : '' }}</div>
        </div>
        <div v-if="primarySs" style="display:flex;flex-direction:column;align-items:flex-start;gap:6px;padding:14px 0;border-bottom:1px solid var(--border)">
          <div style="display:flex;align-items:center;gap:10px;width:100%;flex-wrap:wrap">
            <div class="exec-avatar">{{ ssInitials }}</div>
            <div style="flex:1;min-width:0">
              <div style="font-weight:700;font-size:13px">{{ ssName }}</div>
              <div style="font-size:11px;color:var(--text-3)">{{ primarySs?.steward?.title ?? '' }}</div>
            </div>
            <span class="exec-role-chip">Support Steward</span>
            <span class="badge badge--green">Monitoring</span>
          </div>
          <div style="font-size:11px;color:var(--text-3);width:100%">{{ primarySs?.steward?.email ?? '' }}{{ primarySs?.steward?.phone ? ' · ' + primarySs.steward.phone : '' }}</div>
        </div>
      </div>

      <div class="section-label" style="margin-bottom:8px">Continuity Plan Details</div>
      <div style="display:flex;flex-direction:column">
        <div class="cc-detail-row"><span class="cc-detail-label">Signed</span><span class="cc-detail-value">{{ planSignedAt }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Annual Review</span><span class="cc-detail-value" style="color:var(--orange-dark)">Due {{ planReviewDue }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Activation Trigger</span><span class="cc-detail-value">48-hr Absence</span></div>
        <div class="cc-detail-row">
          <span class="cc-detail-label">Security Doc</span>
          <select class="form-select" style="max-width:220px">
            <option>None</option><option>Police Report</option><option>Doctor's Note</option><option>Death Certificate</option>
          </select>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" style="margin-right:auto" @click="modals.annualReview = true; modals.executorPanel = false">Annual Review</button>
        <button v-if="!activeIncident" class="btn btn-danger" @click="modals.activateSuccession = true; modals.executorPanel = false">
          <AegisIcon name="alert-triangle" :size="13" /> Activate Continuity Support
        </button>
        <Link :href="route('provider.stewards.index')" class="btn btn-primary">
          Manage Continuity Stewards <AegisIcon name="chevron-right" :size="13" />
        </Link>
      </template>
    </AegisModal>

    <!-- Activate Succession -->
    <!-- Activate Succession — exact PHP parity -->
    <AegisModal v-model="modals.activateSuccession" title="Activate Continuity Support" size="lg">

      <div style="background:var(--red-light);border:1.5px solid var(--border-dark);border-radius:var(--radius-lg);padding:11px 14px;margin-bottom:14px;font-size:13px;color:var(--text-2);line-height:1.55">
        <strong style="color:var(--red)">This notifies your Continuity &amp; Support Stewards</strong>
        and initiates your Continuity Plan. Activate only during a genuine critical moment.
      </div>

      <div class="form-row" style="margin-bottom:12px">
        <div class="form-group">
          <label class="form-label">Incident Type <span style="color:var(--red)">*</span></label>
          <select v-model="successionForm.incident_type" class="form-select">
            <option value="">— Select incident type —</option>
            <option value="death">Death</option>
            <option value="missing">Missing Person</option>
            <option value="incapacitation">Short-Term Incapacitation</option>
            <option value="extended_absence">Long-Term Incapacitation</option>
            <option value="natural_disaster">Natural Disaster</option>
            <option value="detainment">Detainment</option>
          </select>
          <div v-if="successionForm.errors.incident_type" class="form-error">{{ successionForm.errors.incident_type }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Supporting Documentation</label>
          <select v-model="successionForm.documentation_type" class="form-select">
            <option value="none">None</option>
            <option value="police_report">Police Report</option>
            <option value="doctors_note">Doctor's Note</option>
            <option value="death_certificate">Death Certificate</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Additional Notes <span style="color:var(--text-4);font-weight:400">(optional)</span></label>
        <textarea v-model="successionForm.report_narrative" class="form-textarea" rows="2" placeholder="Provide any relevant context for your Continuity Steward…"></textarea>
        <div v-if="successionForm.errors.report_narrative" class="form-error">{{ successionForm.errors.report_narrative }}</div>
      </div>

      <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:10px 14px;font-size:13px;color:var(--text-3);line-height:1.5">
        <strong>What happens next:</strong> Your Continuity Steward ({{ csName }}) and Support Steward ({{ ssName }}) will be notified. Your Continuity Plan will be activated, and access to your Vault will be granted according to the permissions you have defined.
      </div>

      <template #footer>
        <button class="btn btn-outline" @click="modals.activateSuccession = false">Cancel</button>
        <button
          class="btn btn-danger"
          :disabled="!successionForm.incident_type || successionForm.processing"
          @click="activateSuccession"
        >
          <AegisIcon name="alert-triangle" :size="13" /> Confirm Activation
        </button>
      </template>
    </AegisModal>

    <!-- Annual Review -->
    <AegisModal v-model="modals.annualReview" title="Annual Review" size="lg">
      <div class="alert alert-warning" style="margin-bottom:18px">
        <div class="alert-icon"><AegisIcon name="megaphone" :size="14" /></div>
        <div class="alert-content">Annual review due {{ planReviewDue }}. Please verify all items and attest.</div>
      </div>
      <div class="dh-review-checklist">
        <label class="form-check"><input v-model="annualReviewForm.checklist.stewards" type="checkbox" class="form-check-input" /><span class="form-check-label">Continuity Steward identity and contact information is current</span></label>
        <label class="form-check"><input v-model="annualReviewForm.checklist.contacts" type="checkbox" class="form-check-input" /><span class="form-check-label">Support Steward identity and contact information is current</span></label>
        <label class="form-check"><input v-model="annualReviewForm.checklist.tasks" type="checkbox" class="form-check-input" /><span class="form-check-label">Support Steward tasks are complete and accurate</span></label>
        <label class="form-check"><input v-model="annualReviewForm.checklist.incidents" type="checkbox" class="form-check-input" /><span class="form-check-label">Assigned Continuity Steward tasks are complete and accurate</span></label>
        <label class="form-check"><input v-model="annualReviewForm.checklist.preferences" type="checkbox" class="form-check-input" /><span class="form-check-label">Continuity Plan is accurate</span></label>
        <label class="form-check"><input v-model="annualReviewForm.checklist.fees" type="checkbox" class="form-check-input" /><span class="form-check-label">Practice information is accurate</span></label>
        <label class="form-check"><input v-model="annualReviewForm.checklist.documents" type="checkbox" class="form-check-input" /><span class="form-check-label">Support documentation is complete and accurate</span></label>
        <label class="form-check"><input v-model="annualReviewForm.checklist.vault" type="checkbox" class="form-check-input" /><span class="form-check-label">Vault documentation is complete and accurate</span></label>
      </div>
      <div style="margin-top:18px">
        <label class="form-label">Additional Notes</label>
        <textarea v-model="annualReviewForm.notes" class="form-textarea" placeholder="Any changes or notes for this review period..."></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.annualReview = false">Cancel</button>
        <button class="btn btn-primary" :disabled="!allReviewChecked || annualReviewForm.processing" @click="submitAnnualReview">
          {{ annualReviewForm.processing ? 'Submitting…' : 'Submit Annual Review' }}
        </button>
      </template>
    </AegisModal>

    <!-- CEU Modal -->
    <AegisModal v-model="modals.ceu" title="Continuing Education (CEU)" size="lg">
      <!-- Progress bar -->
      <div style="background:var(--surface-2);border-radius:var(--radius);padding:14px;margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
          <span style="font-size:13px;font-weight:700">{{ new Date().getFullYear() }} CEU Progress</span>
          <span style="font-size:13px;font-weight:700;color:var(--orange)">{{ stats.ceus_total ?? 18 }} / 30 hrs</span>
        </div>
        <div class="progress-bar"><div class="progress-fill" :style="'width:' + Math.min(100, ((stats.ceus_total ?? 18)/30*100)) + '%;background:var(--orange)'"></div></div>
        <div style="font-size:11px;color:var(--text-3);margin-top:5px">{{ 30 - (stats.ceus_total ?? 18) }} more hours needed · Deadline Dec 31, {{ new Date().getFullYear() }}</div>
      </div>

      <!-- CEU Requirements -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
        <div style="font-size:13px;font-weight:700">CEU Requirements</div>
      </div>
      <div style="font-size:11px;color:var(--text-3);margin-bottom:12px">Define the requirements you track against — jurisdiction, hours, cycle, and required types.</div>

      <!-- Existing requirements list -->
      <template v-if="ceuRequirements.length">
        <div
          v-for="req in ceuRequirements"
          :key="req.id"
          style="border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;margin-bottom:10px;display:flex;align-items:center;justify-content:space-between;gap:12px"
        >
          <div>
            <div style="font-size:13px;font-weight:700">{{ req.jurisdiction }}</div>
            <div style="font-size:11px;color:var(--text-3);margin-top:2px">
              {{ req.total_hours }} hours · {{ req.cycle === 'biannual' ? 'Biannual' : 'Annual' }}
              <span v-if="req.due_date"> · Due {{ formatDate(req.due_date) }}</span>
            </div>
            <div v-if="req.required_types" style="font-size:11px;color:var(--text-3);margin-top:2px">
              Required: {{ req.required_types }}
            </div>
          </div>
          <div style="display:flex;gap:6px;flex-shrink:0">
            <button class="btn-icon" data-tooltip="Edit requirement" @click="editCeuRequirement(req)">
              <AegisIcon name="pencil" :size="14" />
            </button>
            <button class="btn-icon btn-icon-danger" data-tooltip="Remove" @click="removeCeuRequirement(req)">
              <AegisIcon name="trash" :size="14" />
            </button>
          </div>
        </div>
      </template>
      <AegisEmptyState
        v-else
        icon="clipboard-check"
        title="No CEU requirements tracked"
        description="Add the jurisdictions and cycle you're accountable to below."
      />

      <!-- Add / Edit requirement -->
      <div style="border:1px solid var(--border);border-radius:var(--radius);padding:14px;margin-bottom:22px">
        <div style="font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:10px;text-transform:uppercase;letter-spacing:0.3px">
          {{ ceuRequirementForm.id ? 'Edit Requirement' : 'Add a Requirement' }}
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">State / Jurisdiction <span class="req">*</span></label>
            <input v-model="ceuRequirementForm.jurisdiction" class="form-input" type="text" placeholder="e.g., California — BBS" />
            <div v-if="ceuRequirementForm.errors.jurisdiction" class="form-error">{{ ceuRequirementForm.errors.jurisdiction }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Total Hours Required <span class="req">*</span></label>
            <input v-model="ceuRequirementForm.total_hours" class="form-input" type="number" min="1" max="200" placeholder="e.g., 36" />
            <div v-if="ceuRequirementForm.errors.total_hours" class="form-error">{{ ceuRequirementForm.errors.total_hours }}</div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Renewal Cycle</label>
            <div style="display:flex;gap:16px;padding-top:6px">
              <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px"><input type="radio" v-model="ceuRequirementForm.cycle" value="annual" style="-webkit-appearance:auto;accent-color:var(--gold-dark)" /> Annual</label>
              <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px"><input type="radio" v-model="ceuRequirementForm.cycle" value="biannual" style="-webkit-appearance:auto;accent-color:var(--gold-dark)" /> Biannual</label>
            </div>
          </div>
          <div class="form-group"><label class="form-label">Renewal Due Date</label><input v-model="ceuRequirementForm.due_date" class="form-input" type="date" /></div>
        </div>
        <div class="form-group">
          <label class="form-label">Required CEU Types <span style="color:var(--text-4);font-weight:500">(optional)</span></label>
          <input v-model="ceuRequirementForm.required_types" class="form-input" type="text" placeholder="e.g., Ethics (6 hrs), Suicide, Cultural Competency" />
          <div class="form-hint">List any mandated subject areas and their minimum hours.</div>
        </div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-outline btn-sm" :disabled="ceuRequirementForm.processing" @click="saveCeuRequirement">
            <AegisIcon :name="ceuRequirementForm.id ? 'check' : 'plus'" :size="13" />
            {{ ceuRequirementForm.processing
                ? 'Saving…'
                : ceuRequirementForm.id ? 'Update Requirement' : 'Save Requirement' }}
          </button>
          <button v-if="ceuRequirementForm.id" class="btn btn-ghost btn-sm" @click="ceuRequirementForm.reset()">
            Cancel edit
          </button>
        </div>
      </div>

      <!-- Completed CEUs -->
      <div style="font-size:13px;font-weight:700;margin-bottom:12px">Completed CEUs</div>
      <div v-if="upcomingCEUs.length">
        <div v-for="ceu in upcomingCEUs" :key="ceu.id" style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border)">
          <div>
            <div style="font-size:13px;font-weight:600">{{ ceu.title }}</div>
            <div style="font-size:11px;color:var(--text-3)">{{ formatDate(ceu.completed_on) }} · {{ ceu.credit_hours }} hours</div>
          </div>
          <span class="badge badge--green"><AegisIcon name="check" :size="11" /> Verified</span>
        </div>
      </div>
      <div v-else style="font-size:13px;color:var(--text-3);padding:8px 0 12px">No CEU entries yet.</div>

      <!-- Add CEU Credits -->
      <div style="margin-top:22px">
        <div style="font-size:13px;font-weight:700;margin-bottom:14px">Add CEU Credits</div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Course Name <span class="req">*</span></label><input v-model="ceuForm.title" type="text" class="form-input" placeholder="e.g., Ethics in Telehealth 2025" /></div>
          <div class="form-group">
            <label class="form-label">CEU Category <span class="req">*</span></label>
            <select v-model="ceuForm.category" class="form-select">
              <option value="">Select a category...</option>
              <option>Ethics</option><option>Supervision</option><option>Telehealth</option>
              <option>Safety</option><option>Quality</option><option>HIV</option>
              <option>Child Abuse Assessment and Reporting</option>
              <option>Intimate Partner Violence / Domestic Violence</option>
              <option>Assessment and Diagnosis</option><option>Referral and Interventions</option>
              <option>Alcohol and Substance Use Dependency</option><option>Publications</option>
              <option>Teaching, Education and Training</option><option>General CEUs</option>
              <option>Cultural Competency</option><option>Suicide</option><option>Other</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Delivery Method</label>
            <div style="display:flex;gap:16px;padding-top:6px">
              <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px"><input type="radio" v-model="ceuForm.delivery" value="synchronous" style="-webkit-appearance:auto;accent-color:var(--gold-dark)" /> Synchronous</label>
              <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px"><input type="radio" v-model="ceuForm.delivery" value="asynchronous" style="-webkit-appearance:auto;accent-color:var(--gold-dark)" /> Asynchronous</label>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Requirement Cycle</label>
            <div style="display:flex;gap:16px;padding-top:6px">
              <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px"><input type="radio" v-model="ceuForm.cycle" value="annual" style="-webkit-appearance:auto;accent-color:var(--gold-dark)" /> Annual</label>
              <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px"><input type="radio" v-model="ceuForm.cycle" value="biannual" style="-webkit-appearance:auto;accent-color:var(--gold-dark)" /> Biannual</label>
            </div>
          </div>
        </div>
        <div class="form-row is-3col">
          <div class="form-group"><label class="form-label">Date Completed</label><input v-model="ceuForm.completed_on" type="date" class="form-input" /></div>
          <div class="form-group"><label class="form-label">Credit Hours <span class="req">*</span></label><input v-model="ceuForm.credit_hours" type="number" min="0.5" step="0.5" class="form-input" placeholder="e.g., 3" /></div>
          <div class="form-group"><label class="form-label">Provider / Org</label><input v-model="ceuForm.provider_name" type="text" class="form-input" placeholder="e.g., APA" /></div>
        </div>
        <div class="form-group">
          <label class="form-label">Upload Certificate</label>
          <AegisDropzone accept=".pdf,.jpg,.png" hint="PDF, JPG or PNG up to 10 MB" @files="ceuForm.certificate = $event[0]" />
        </div>
      </div><!-- /Add CEU Credits -->
      <template #footer>
        <button class="btn btn-outline" @click="modals.ceu = false">Close</button>
        <button class="btn btn-primary" :disabled="ceuForm.processing" @click="submitCeu">
          {{ ceuForm.processing ? 'Saving…' : 'Add CEU Credits' }}
        </button>
      </template>
    </AegisModal>

    <!-- New Referral Modal — shared 4-step component -->
    <ReferralModal
      v-model="modals.newReferral"
      :roster="referralRoster"
      :network="referralNetwork"
    />

    <!-- Add License -->
    <AegisModal v-model="modals.addLicense" title="Add Credential" size="lg">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Credential Type <span class="req">*</span></label>
          <select v-model="licenseForm.cred_type" class="form-select" @change="licenseForm.custom_type = ''">
            <option value="">Select a credential...</option>
            <optgroup label="Medical &amp; Prescribing">
              <option>MD</option><option>DO</option><option>ND</option><option>NP</option><option>PA</option>
            </optgroup>
            <optgroup label="Mental Health">
              <option>LPC / LPCC</option><option>LCSW / LICSW</option><option>LMFT</option><option>ABPP</option>
            </optgroup>
            <optgroup label="Therapy &amp; Specialty">
              <option>EMDR Certified</option><option>DBT Certified</option><option>CSE</option><option>CSC</option><option>CST</option>
            </optgroup>
            <optgroup label="Creative Therapies">
              <option>ATR</option><option>MT-BC</option><option>RDT</option>
            </optgroup>
            <optgroup label="Addiction &amp; Behavioral">
              <option>CADC / ICADC</option>
            </optgroup>
            <optgroup label="Integrative / Alt Medicine">
              <option>LAc</option>
            </optgroup>
            <optgroup label="Nutrition &amp; Health">
              <option>RD / RDN</option><option>NBC-HWC</option>
            </optgroup>
            <optgroup label="Birth &amp; Reproductive">
              <option>CNM</option>
            </optgroup>
            <optgroup label="Specialized">
              <option>CGC</option><option>CDCES / CDE</option>
            </optgroup>
            <optgroup label="Fitness &amp; Physical">
              <option>CPT (NSCA)</option><option>CPT (NASM)</option><option>CPT (ACE)</option><option>EP-C (ACSM)</option>
            </optgroup>
            <optgroup label="Other">
              <option value="Licensed">Licensed</option>
              <option value="custom">Other (enter manually)</option>
            </optgroup>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Issuing State / Body</label>
          <select v-model="licenseForm.issuer" class="form-select">
            <option value="">Select…</option>
            <option>New York</option><option>California</option><option>Texas</option><option>Florida</option>
            <option>Federal</option><option>National / No State</option><option>Other</option>
          </select>
        </div>
      </div>
      <div v-if="licenseForm.cred_type === 'custom'" class="form-group">
        <label class="form-label">Custom Credential Name <span class="req">*</span></label>
        <input v-model="licenseForm.custom_type" class="form-input" type="text" placeholder="Enter credential name" />
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">License / Credential Number</label><input v-model="licenseForm.number" type="text" class="form-input" placeholder="e.g., NY-MD-12345 (optional)" /></div>
        <div class="form-group"><label class="form-label">Display Name</label><input v-model="licenseForm.name" type="text" class="form-input" placeholder="e.g., NY Medical License (optional)" /></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Date of Issue</label><input v-model="licenseForm.issued_on" type="date" class="form-input" /></div>
        <div class="form-group"><label class="form-label">Expiration Date</label><input v-model="licenseForm.expires_on" type="date" class="form-input" /></div>
      </div>
      <div class="form-group"><label class="form-label">Upload Document</label><AegisDropzone accept=".pdf,.jpg,.png" hint="PDF, JPG or PNG up to 10 MB" @files="licenseForm.document = $event[0]" /></div>
      <div v-if="licenseForm.errors.document" class="form-error">{{ licenseForm.errors.document }}</div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.addLicense = false">Cancel</button>
        <button class="btn btn-primary" :disabled="licenseForm.processing" @click="submitLicense">
          {{ licenseForm.processing ? 'Saving…' : 'Add Credential' }}
        </button>
      </template>
    </AegisModal>

    <!-- Renew License -->
    <AegisModal v-model="modals.renewLicense" title="Update Credential" size="lg">
      <div class="alert alert-warning" style="margin-bottom:16px">
        <div class="alert-icon"><AegisIcon name="megaphone" :size="14" /></div>
        <div class="alert-content">
          {{ activeCredential?.cred_type ?? 'Credential' }}
          {{ activeCredential?.expires_on ? 'expires ' + formatDate(activeCredential.expires_on) : 'needs updating' }}
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">New Expiration Date</label><input v-model="renewForm.expires_on" type="date" class="form-input" /></div>
        <div class="form-group"><label class="form-label">Confirmation / Reference #</label><input v-model="renewForm.number" type="text" class="form-input" placeholder="e.g., RENEW-2025-XXXXX" /></div>
      </div>
      <div class="form-group"><label class="form-label">Upload Updated Document</label><AegisDropzone accept=".pdf,.jpg,.png" hint="PDF, JPG or PNG up to 10 MB" @files="renewForm.document = $event[0]" /></div>
      <div v-if="renewForm.errors.document" class="form-error">{{ renewForm.errors.document }}</div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.renewLicense = false">Cancel</button>
        <button class="btn btn-primary" :disabled="renewForm.processing" @click="submitRenew">
          {{ renewForm.processing ? 'Saving…' : 'Save Update' }}
        </button>
      </template>
    </AegisModal>

    <!-- License Detail -->
    <AegisModal v-model="modals.licenseDetail" title="License Details" size="md">
      <div style="display:flex;flex-direction:column">
        <div class="cc-detail-row"><span class="cc-detail-label">Credential Type</span><span class="cc-detail-value">{{ activeCredential?.cred_type ?? '—' }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">License #</span><span class="cc-detail-value">{{ activeCredential?.number ?? '—' }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Issuing Body</span><span class="cc-detail-value">{{ activeCredential?.issuer ?? '—' }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Status</span>
          <span class="cc-detail-value" :style="activeCredential?.days_remaining !== undefined && activeCredential.days_remaining < 30 ? 'color:var(--red)' : 'color:var(--green)'">
            ● {{ activeCredential?.days_remaining !== undefined && activeCredential.days_remaining < 1 ? 'Expired' : 'Active' }}
          </span>
        </div>
        <div class="cc-detail-row"><span class="cc-detail-label">Issue Date</span><span class="cc-detail-value">{{ activeCredential?.issued_on ? formatDate(activeCredential.issued_on) : '—' }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Expires</span><span class="cc-detail-value">{{ activeCredential?.expires_on ? formatDate(activeCredential.expires_on) : 'No expiry' }}</span></div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.licenseDetail = false">Close</button>
        <button class="btn btn-primary" @click="openRenewLicense(activeCredential); modals.licenseDetail = false">Update Credential</button>
      </template>
    </AegisModal>

    <!-- Add Insurance -->
    <AegisModal v-model="modals.addInsurance" title="Add Insurance Policy" size="lg">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Policy Type <span class="req">*</span></label>
          <select v-model="insuranceForm.cred_type" class="form-select">
            <option value="">Select type</option>
            <option>Professional Liability (Malpractice)</option>
            <option>General Business Insurance</option>
            <option>Workers Compensation</option>
            <option>Cyber Liability</option>
            <option>Life Insurance</option>
            <option>Other</option>
          </select>
          <div v-if="insuranceForm.errors.cred_type" class="form-error">{{ insuranceForm.errors.cred_type }}</div>
        </div>
        <div class="form-group"><label class="form-label">Insurance Provider <span class="req">*</span></label><input v-model="insuranceForm.issuer" type="text" class="form-input" placeholder="e.g., Medical Protective" /></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Policy Number <span class="req">*</span></label><input v-model="insuranceForm.number" type="text" class="form-input" placeholder="e.g., POL-2024-XXXXX" /></div>
        <div class="form-group"><label class="form-label">Display Name / Coverage</label><input v-model="insuranceForm.name" type="text" class="form-input" placeholder="e.g., $2M / $4M Liability" /></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Effective Date <span class="req">*</span></label><input v-model="insuranceForm.issued_on" type="date" class="form-input" /></div>
        <div class="form-group"><label class="form-label">Expiration Date <span class="req">*</span></label><input v-model="insuranceForm.expires_on" type="date" class="form-input" /></div>
      </div>
      <div class="form-group"><label class="form-label">Upload Policy Document</label><AegisDropzone accept=".pdf,.jpg,.png" hint="PDF, JPG or PNG up to 10 MB" @files="insuranceForm.document = $event[0]" /></div>
      <div v-if="insuranceForm.errors.document" class="form-error">{{ insuranceForm.errors.document }}</div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.addInsurance = false">Cancel</button>
        <button class="btn btn-primary" :disabled="insuranceForm.processing" @click="submitInsurance">
          {{ insuranceForm.processing ? 'Saving…' : 'Add Policy' }}
        </button>
      </template>
    </AegisModal>

    <!-- Renew Insurance -->
    <AegisModal v-model="modals.renewInsurance" title="Update Insurance Policy" size="lg">
      <div class="alert alert-warning" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="megaphone" :size="14" /></div>
        <div class="alert-content">
          {{ activeInsurance?.cred_type ?? 'Insurance policy' }}
          {{ activeInsurance?.expires_on ? 'expires ' + formatDate(activeInsurance.expires_on) : 'needs updating' }}
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">New Policy Number</label>
        <input v-model="renewInsuranceForm.number" type="text" class="form-input" placeholder="If changed" />
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">New Effective Date <span class="req">*</span></label>
          <input v-model="renewInsuranceForm.issued_on" type="date" class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label">New Expiration Date <span class="req">*</span></label>
          <input v-model="renewInsuranceForm.expires_on" type="date" class="form-input" />
          <div v-if="renewInsuranceForm.errors.expires_on" class="form-error">{{ renewInsuranceForm.errors.expires_on }}</div>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Upload Updated Policy</label><AegisDropzone accept=".pdf,.jpg,.png" hint="PDF, JPG or PNG up to 10 MB" @files="renewInsuranceForm.document = $event[0]" /></div>
      <div v-if="renewInsuranceForm.errors.document" class="form-error">{{ renewInsuranceForm.errors.document }}</div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.renewInsurance = false">Cancel</button>
        <button class="btn btn-primary" :disabled="renewInsuranceForm.processing" @click="submitRenewInsurance">
          {{ renewInsuranceForm.processing ? 'Saving…' : 'Save Update' }}
        </button>
      </template>
    </AegisModal>

    <!-- Insurance Detail -->
    <AegisModal v-model="modals.insuranceDetail" title="Insurance Policy Details" size="md">
      <div style="display:flex;flex-direction:column">
        <div class="cc-detail-row"><span class="cc-detail-label">Policy Type</span><span class="cc-detail-value">{{ activeInsurance?.cred_type ?? '—' }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Carrier</span><span class="cc-detail-value">{{ activeInsurance?.issuer ?? '—' }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Policy #</span><span class="cc-detail-value">{{ activeInsurance?.number ?? '—' }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Status</span>
          <span class="cc-detail-value" :style="activeInsurance?.days_remaining !== undefined && activeInsurance.days_remaining < 30 ? 'color:var(--red)' : 'color:var(--green)'">
            ● {{ activeInsurance?.days_remaining !== undefined && activeInsurance.days_remaining < 1 ? 'Expired' : 'Active' }}
          </span>
        </div>
        <div class="cc-detail-row"><span class="cc-detail-label">Coverage</span><span class="cc-detail-value">{{ activeInsurance?.coverage ?? '—' }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Annual Premium</span><span class="cc-detail-value">{{ activeInsurance?.premium ?? '—' }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Effective Date</span><span class="cc-detail-value">{{ activeInsurance?.issued_on ? formatDate(activeInsurance.issued_on) : '—' }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Expires</span><span class="cc-detail-value">{{ activeInsurance?.expires_on ? formatDate(activeInsurance.expires_on) : '—' }}</span></div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.insuranceDetail = false">Close</button>
        <button class="btn btn-primary" @click="openRenewInsurance(activeInsurance); modals.insuranceDetail = false">Update Policy</button>
      </template>
    </AegisModal>

    <!-- Set Reminder -->
    <AegisModal v-model="modals.setReminder" title="Set Reminder" size="sm">
      <div class="form-group">
        <label class="form-label">Item</label>
        <select v-model="reminderForm.item" class="form-select">
          <option>License (NY) — Expires Jun 30, 2026</option>
          <option>License (CA) — Expires Aug 12, 2025</option>
          <option>License (TX) — Expires Sep 5, 2026</option>
          <option>Professional Liability — Expires Mar 15, 2025</option>
          <option>General Business — Expires Feb 28, 2026</option>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Remind Me</label>
          <select v-model="reminderForm.days_before" class="form-select">
            <option>30 days before</option>
            <option>60 days before</option>
            <option>90 days before</option>
            <option>120 days before</option>
            <option>180 days before</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Repeat</label>
          <select v-model="reminderForm.repeat" class="form-select">
            <option>One-time</option>
            <option>Weekly until acted on</option>
            <option>Daily in final 7 days</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Delivery</label>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:4px">
          <label class="form-check"><input type="checkbox" v-model="reminderForm.email" class="form-check-input" /><span class="form-check-label">Email reminder</span></label>
          <label class="form-check"><input type="checkbox" v-model="reminderForm.in_app" class="form-check-input" /><span class="form-check-label">In-app notification</span></label>
          <label class="form-check"><input type="checkbox" v-model="reminderForm.sms" class="form-check-input" /><span class="form-check-label">SMS</span></label>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.setReminder = false">Cancel</button>
        <button class="btn btn-primary" @click="submitReminder">Set Reminder</button>
      </template>
    </AegisModal>

    <!-- Logout -->
    <AegisModal v-model="modals.logout" title="Sign Out" size="sm">
      <p style="font-size:13.5px;color:var(--text-2);line-height:1.6">Are you sure you want to sign out of Aegis?</p>
      <template #footer>
        <button class="btn btn-outline" @click="modals.logout = false">Cancel</button>
        <button class="btn btn-danger" @click="handleLogout">Sign Out</button>
      </template>
    </AegisModal>

    <!-- Notifications -->
    <AegisModal v-model="modals.notif" title="Notifications" size="sm">
      <div style="padding:0">
        <div class="list-group" style="border:none;border-radius:0">
          <template v-if="incomingReferrals.length">
            <div
              v-for="ref_ in incomingReferrals.slice(0,5)"
              :key="ref_.id"
              class="list-group-item clickable"
              @click="openReferralDetail(ref_); modals.notif = false"
            >
              <div style="width:36px;height:36px;border-radius:var(--radius-sm);background:var(--blue-light);color:var(--blue-dark);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <AegisIcon name="arrow-right-arrow-left" :size="18" />
              </div>
              <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:700;color:var(--text)">Referral from {{ ref_.sender?.display_name ?? 'a practitioner' }}</div>
                <div style="font-size:12px;color:var(--text-3);margin-top:2px;line-height:1.5">{{ ref_.subject ?? '' }} · {{ ref_.created_at ? formatDate(ref_.created_at) : '' }}</div>
              </div>
            </div>
          </template>
          <AegisEmptyState
            v-else
            icon="bell"
            title="No new notifications"
          />
        </div>
      </div>
      <template #footer>
        <button class="btn btn-ghost btn-sm" @click="toast.success('All marked as read'); modals.notif = false">Mark all read</button>
        <Link :href="route('provider.referrals.index')" class="btn btn-outline btn-sm" @click="modals.notif = false">View All</Link>
      </template>
    </AegisModal>

    <!-- Referral Detail -->
    <AegisModal v-model="modals.referralDetail" title="Referral Details" size="lg">
      <div class="alert alert-info" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="arrow-right-arrow-left" :size="14" /></div>
        <div class="alert-content">Received from {{ activeReferral?.sender?.display_name ?? 'a practitioner' }} · {{ activeReferral?.created_at ? formatDate(activeReferral.created_at) : '' }}</div>
      </div>
      <div style="display:flex;flex-direction:column;margin-bottom:14px;margin-top:14px">
        <div class="cc-detail-row"><span class="cc-detail-label">Subject</span><span class="cc-detail-value">{{ activeReferral?.subject ?? '—' }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Client</span><span class="cc-detail-value">{{ activeReferral?.client_initials ?? '—' }}{{ activeReferral?.client_age_band ? ' · ' + activeReferral.client_age_band : '' }}</span></div>
        <div class="cc-detail-row"><span class="cc-detail-label">Urgency</span>
          <span class="cc-detail-value" :style="activeReferral?.urgency === 'urgent' ? 'color:var(--red)' : activeReferral?.urgency === 'high' ? 'color:var(--orange)' : ''">
            {{ { low: 'Low — routine', normal: 'Normal', high: 'High — within 2 weeks', urgent: 'Urgent — ASAP' }[activeReferral?.urgency] ?? '—' }}
          </span>
        </div>
        <div class="cc-detail-row"><span class="cc-detail-label">Status</span><span class="cc-detail-value">{{ activeReferral?.status ?? '—' }}</span></div>
      </div>
      <div v-if="activeReferral?.notes" class="form-label" style="margin-bottom:6px">Clinical Notes</div>
      <div v-if="activeReferral?.notes" style="background:var(--surface-2);border-radius:var(--radius);padding:14px;font-size:13px;color:var(--text-2);line-height:1.6">
        {{ activeReferral.notes }}
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.referralDetail = false">Close</button>
        <button class="btn btn-danger btn-sm" @click="declineReferral">Decline</button>
        <button class="btn btn-primary" @click="acceptReferral">Accept Referral</button>
      </template>
    </AegisModal>

    <!-- Service Request -->
    <AegisModal v-model="modals.serviceRequest" title="Request a Service" size="md">
      <div style="background:var(--badge-bg-gold);border:1px solid var(--gold-dark);border-radius:var(--radius-sm);padding:11px 13px;margin-bottom:18px;display:flex;gap:10px;align-items:flex-start">
        <span style="flex-shrink:0;margin-top:1px;display:inline-flex;align-items:center"><AegisIcon name="alert-triangle" :size="16" /></span>
        <div style="font-size:12px;color:var(--text-2);line-height:1.55">Service requests are sent securely through Aegis. You'll receive a confirmation once the provider responds.</div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Service</label><input type="text" class="form-input" v-model="serviceRequestForm.service_name" readonly /></div>
        <div class="form-group"><label class="form-label">From Provider</label><input type="text" class="form-input" v-model="serviceRequestForm.provider_name" readonly /></div>
      </div>
      <div class="form-row is-3col">
        <div class="form-group"><label class="form-label">Preferred Date <span class="req">*</span></label><input type="date" class="form-input" v-model="serviceRequestForm.preferred_date" /></div>
        <div class="form-group">
          <label class="form-label">Preferred Time</label>
          <select class="form-select" v-model="serviceRequestForm.preferred_time">
            <option>Morning (9am–12pm)</option><option>Afternoon (12–5pm)</option>
            <option>Evening (5–8pm)</option><option>Flexible</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Format</label>
          <select class="form-select" v-model="serviceRequestForm.format">
            <option>Telehealth</option><option>In-Person</option><option>No preference</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Notes for Provider <span style="color:var(--text-4);font-weight:500">(optional)</span></label>
        <textarea class="form-textarea" v-model="serviceRequestForm.notes" rows="3" placeholder="Briefly describe what you'd like to discuss…"></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="modals.serviceRequest = false">Cancel</button>
        <button class="btn btn-primary" @click="submitServiceRequest">
          <AegisIcon name="send" :size="13" /> Send Request
        </button>
      </template>
    </AegisModal>

  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout   from '@/layouts/AppLayout.vue'
import AegisDropzone from '@/components/ui/AegisDropzone.vue'
import ReferralModal from '@/components/modals/ReferralModal.vue'
import { useToast }     from '@/composables/useToast'
import { useConfirm }   from '@/composables/useConfirm'

// ── Props ──────────────────────────────────────────────────────────────
const props = defineProps({
  user:               { type: Object,  default: null },
  planStatus:         { type: String,  default: 'none' },
  plan:               { type: Object,  default: null },
  attest:             { type: Object,  default: () => ({}) },
  stats:              { type: Object,  default: () => ({}) },
  activeStewardCount: { type: Number,  default: 0 },
  maatActive:         { type: Boolean, default: false },
  reviewDays:         { type: Number,  default: 0 },
  activeIncident:     { type: Object,  default: null },
  continuityStewards: { type: Array,   default: () => [] },
  supportStewards:    { type: Array,   default: () => [] },
  primaryCs:          { type: Object,  default: null },
  primarySs:          { type: Object,  default: null },
  netClinical:        { type: Array,   default: () => [] },
  netBusiness:        { type: Array,   default: () => [] },
  recentActivity:     { type: Array,   default: () => [] },
  upcomingCEUs:       { type: Array,   default: () => [] },
  credentials:        { type: Array,   default: () => [] },
  insurancePolicies:  { type: Array,   default: () => [] },
  incomingReferrals:  { type: Array,   default: () => [] },
  referralRoster:     { type: Array,   default: () => [] },
  referralNetwork:    { type: Array,   default: () => [] },
  ceuRequirements:    { type: Array,   default: () => [] },
})

// ── Composables ───────────────────────────────────────────────────────
const toast            = useToast()
const { confirmAction } = useConfirm()

// ── Modal state — one key per modal ───────────────────────────────────
const modals = reactive({
  executorPanel:      false,
  activateSuccession: false,
  annualReview:       false,
  ceu:                false,
  newReferral:        false,
  addLicense:         false,
  renewLicense:       false,
  licenseDetail:      false,
  addInsurance:       false,
  renewInsurance:     false,
  insuranceDetail:    false,
  setReminder:        false,
  logout:             false,
  notif:              false,
  referralDetail:     false,
  serviceRequest:     false,
})

// Active credential/insurance/referral for detail modals
const activeCredential  = ref(null)
const activeInsurance   = ref(null)
const activeReferral    = ref(null)

function openLicenseDetail(cred)    { activeCredential.value = cred; modals.licenseDetail = true }
function openInsuranceDetail(ins)   { activeInsurance.value  = ins;  modals.insuranceDetail = true }
function openReferralDetail(ref_)   { activeReferral.value   = ref_; modals.referralDetail = true }
function openRenewLicense(cred)     { activeCredential.value = cred; modals.renewLicense = true }
function openRenewInsurance(ins)    { activeInsurance.value  = ins;  modals.renewInsurance = true }

// ── Credential helpers ─────────────────────────────────────────────────
function credIsCritical (c) {
  return c?.days_remaining !== null && c?.days_remaining !== undefined && c.days_remaining < 30
}
function credBarWidth (c) {
  if (c?.days_remaining === null || c?.days_remaining === undefined) return 50
  if (c.days_remaining < 1)   return 4
  if (c.days_remaining < 30)  return 8
  if (c.days_remaining < 60)  return 25
  if (c.days_remaining < 180) return 50
  return 78
}
function credDaysLabel (c) {
  if (c?.days_remaining === null || c?.days_remaining === undefined) return '—'
  if (c.days_remaining < 1)  return 'Expired'
  if (c.days_remaining < 30) return c.days_remaining + ' days · update now'
  return c.days_remaining + ' days remaining'
}
const credCriticalCount = computed(() => props.credentials.filter(credIsCritical).length)

// ── Network tab ────────────────────────────────────────────────────────
const nwTab = ref('clinical')

// ── Greeting ───────────────────────────────────────────────────────────
const greetFirst = computed(() => {
  if (!props.user?.display_name) return 'Welcome'
  const raw   = props.user.display_name
  const name  = raw.replace(/^(Dr\.|Prof\.|Mr\.|Ms\.|Mrs\.)\s*/i, '').trim()
  const parts = name.split(/\s+/)
  const prefix = /^(Dr\.|Prof\.)/i.test(raw) ? 'Dr. ' : ''
  return prefix + (parts[0] ?? 'User')
})
const greetLast = computed(() => {
  if (!props.user?.display_name) return ''
  const name  = props.user.display_name.replace(/^(Dr\.|Prof\.|Mr\.|Ms\.|Mrs\.)\s*/i, '').trim()
  const parts = name.split(/\s+/)
  return parts.length > 1 ? parts.slice(1).join(' ') : ''
})
const greetSub = computed(() => {
  const base = props.planStatus === 'active'
    ? 'Your continuity plan is active and your practice is on track.'
    : 'Your continuity plan is being set up.'
  const refs = props.stats?.pending_refs ?? 0
  const suffix = refs > 0
    ? ` You have ${refs} pending referral${refs > 1 ? 's' : ''} that need your attention.`
    : ' There are a few items that need your attention this week.'
  return base + suffix
})

// ── Plan dates ─────────────────────────────────────────────────────────
function formatDate(d)      { if (!d) return '—'; return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }
function formatShortDate(d) { if (!d) return '—'; return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }

const planSignedAt  = computed(() => formatDate(props.plan?.signed_at))
const planReviewDue = computed(() => formatDate(props.plan?.annual_review_date))
const planSince     = computed(() => {
  if (!props.plan?.signed_at) return '—'
  return new Date(props.plan.signed_at).toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
})
const reviewDaysLabel = computed(() =>
  props.reviewDays > 0 ? `${props.reviewDays} days remaining` : 'Overdue'
)
const planStatusLabel = computed(() => ({
  draft: 'Draft', active: 'Active', annual_review_due: 'Review Due', none: 'Not Created',
}[props.planStatus] ?? props.planStatus))

// ── Steward display ────────────────────────────────────────────────────
const csName     = computed(() => props.primaryCs?.steward?.display_name ?? 'Not assigned')
const ssName     = computed(() => props.primarySs?.steward?.display_name ?? 'Not assigned')
const csInitials = computed(() => {
  const n = csName.value; if (n === 'Not assigned') return 'CS'
  const p = n.replace(/^(Dr\.|Prof\.)\s*/i,'').split(' ')
  return p.length >= 2 ? (p[0][0]+p[p.length-1][0]).toUpperCase() : p[0].substring(0,2).toUpperCase()
})
const ssInitials = computed(() => {
  const n = ssName.value; if (n === 'Not assigned') return 'SS'
  const p = n.replace(/^(Dr\.|Prof\.)\s*/i,'').split(' ')
  return p.length >= 2 ? (p[0][0]+p[p.length-1][0]).toUpperCase() : p[0].substring(0,2).toUpperCase()
})

// ── Annual review — wired to provider.plan.review.complete ────────────
const annualReviewForm = useForm({
  checklist: {
    stewards:    false,
    incidents:   false,
    documents:   false,
    vault:       false,
    tasks:       false,
    fees:        false,
    contacts:    false,
    preferences: false,
  },
  notes: '',
})
const allReviewChecked = computed(() => Object.values(annualReviewForm.checklist).every(Boolean))
function submitAnnualReview() {
  annualReviewForm.post(route('provider.plan.review.complete'), {
    onSuccess: () => { modals.annualReview = false; toast.success('Annual review submitted.') },
    onError:   () => toast.error('Please complete all checklist items.'),
  })
}

// ── CEU form — wired to provider.ceus.store ────────────────────────────
// ── CEU Add form — wired to provider.ceus.store ────────────────────────
const ceuForm = useForm({ type: 'ceu', title: '', category: '', delivery: 'synchronous', cycle: 'annual', credit_hours: '', provider_name: '', completed_on: '', certificate: null })
function submitCeu() {
  ceuForm.post(route('provider.ceus.store'), {
    forceFormData: true,
    onSuccess: () => { modals.ceu = false; toast.success('CEU credits added!'); ceuForm.reset() },
    onError:   () => toast.error('Could not save CEU entry.'),
  })
}

// ── Referral submission is handled inside the shared ReferralModal component ──
// (See /resources/js/components/modals/ReferralModal.vue)

// ── Accept / Decline incoming referral ────────────────────────────────
function acceptReferral() {
  if (!activeReferral.value?.id) return
  router.post(route('provider.referrals.accept', activeReferral.value.id), {}, {
    onSuccess: () => { modals.referralDetail = false; toast.success('Referral accepted.') },
    onError:   () => toast.error('Could not accept referral.'),
  })
}
function declineReferral() {
  if (!activeReferral.value?.id) return
  router.post(route('provider.referrals.decline', activeReferral.value.id), {}, {
    onSuccess: () => { modals.referralDetail = false; toast.info('Referral declined.') },
    onError:   () => toast.error('Could not decline referral.'),
  })
}

// ── License / Credential forms — wired to provider.credentials.* ──────
const licenseForm = useForm({
  cred_type:  '',
  custom_type:'',
  name:        '',
  number:      '',
  issuer:      '',
  issued_on:   '',
  expires_on:  '',
  document:    null,
})
function submitLicense() {
  licenseForm.post(route('provider.credentials.store'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => { modals.addLicense = false; toast.success('Credential added.'); licenseForm.reset() },
    onError:   () => toast.error('Please check the credential form.'),
  })
}

const renewForm = useForm({ cred_type: '', expires_on: '', number: '', document: null })
function submitRenew() {
  if (!activeCredential.value?.id) { toast.error('No credential selected.'); return }
  renewForm.post(route('provider.credentials.update', activeCredential.value.id), {
    forceFormData: true,
    preserveScroll: true,
    headers: { 'X-HTTP-Method-Override': 'PUT' },
    onSuccess: () => { modals.renewLicense = false; toast.success('Credential updated.'); renewForm.reset() },
    onError:   () => toast.error('Could not save update.'),
  })
}

// ── Insurance forms — share provider.credentials.* endpoints ──────────
const insuranceForm = useForm({
  cred_type:  '',
  issuer:     '',
  number:     '',
  name:       '',
  issued_on:  '',
  expires_on: '',
  document:   null,
})
function submitInsurance() {
  insuranceForm.post(route('provider.credentials.store'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => { modals.addInsurance = false; toast.success('Insurance policy added.'); insuranceForm.reset() },
    onError:   () => toast.error('Please check the form.'),
  })
}
const renewInsuranceForm = useForm({ cred_type: '', expires_on: '', issued_on: '', number: '', document: null })
function submitRenewInsurance() {
  if (!activeInsurance.value?.id) { toast.error('No policy selected.'); return }
  renewInsuranceForm.post(route('provider.credentials.update', activeInsurance.value.id), {
    forceFormData: true,
    preserveScroll: true,
    headers: { 'X-HTTP-Method-Override': 'PUT' },
    onSuccess: () => { modals.renewInsurance = false; toast.success('Insurance policy updated.'); renewInsuranceForm.reset() },
    onError:   () => toast.error('Could not save update.'),
  })
}

// ── CEU Requirements — wired to provider.ceu_requirements.* ───────────
const ceuRequirementForm = useForm({
  id:             null,
  jurisdiction:   '',
  total_hours:    '',
  cycle:          'annual',
  due_date:       '',
  required_types: '',
})
function saveCeuRequirement() {
  const isEdit = !!ceuRequirementForm.id
  const url = isEdit
    ? route('provider.ceu_requirements.update', ceuRequirementForm.id)
    : route('provider.ceu_requirements.store')
  const method = isEdit ? 'put' : 'post'
  ceuRequirementForm[method](url, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(isEdit ? 'CEU requirement updated.' : 'CEU requirement saved.')
      ceuRequirementForm.reset()
    },
    onError: () => toast.error('Please check the requirement fields.'),
  })
}
function editCeuRequirement(req) {
  ceuRequirementForm.id             = req.id
  ceuRequirementForm.jurisdiction   = req.jurisdiction
  ceuRequirementForm.total_hours    = req.total_hours
  ceuRequirementForm.cycle          = req.cycle
  ceuRequirementForm.due_date       = req.due_date
  ceuRequirementForm.required_types = req.required_types ?? ''
}
function removeCeuRequirement(req) {
  confirmAction(
    `Remove "${req.jurisdiction}" requirement?`,
    () => router.delete(route('provider.ceu_requirements.destroy', req.id), {
      preserveScroll: true,
      onSuccess: () => toast.success('CEU requirement removed.'),
      onError:   () => toast.error('Could not remove requirement.'),
    })
  )
}

// ── Reminder form (UI-only — no backend reminder route exists yet) ─────
const reminderForm = useForm({ item: '', days_before: '90 days before', repeat: 'One-time', email: true, in_app: true, sms: false })
function submitReminder() { toast.success('Reminder set.'); modals.setReminder = false; reminderForm.reset() }

// ── Succession — wired to provider.incident.activate ─────────────────
const successionForm = useForm({
  plan_id:          props.plan?.id ?? '',
  incident_type:    '',
  report_narrative: '',
  documentation_type: 'none',
})
function activateSuccession() {
  if (!successionForm.incident_type) return
  successionForm.post(route('provider.incident.activate'), {
    onSuccess: () => {
      modals.activateSuccession = false
      successionForm.reset()
      toast.success('Continuity Plan activated. Your stewards have been notified.')
    },
    onError: () => toast.error('Could not activate. Please try again.'),
  })
}

// ── Logout ────────────────────────────────────────────────────────────
function handleLogout() { modals.logout = false; router.post(route('logout')) }

// ── Service request (UI-only — no route yet) ──────────────────────────
const serviceRequestForm = reactive({ service_name: '', provider_name: '', preferred_date: '', preferred_time: 'Flexible', format: 'Telehealth', notes: '' })
function submitServiceRequest() { modals.serviceRequest = false; toast.success('Service request sent.') }
</script>

<style scoped>
/* ── Page wrapper ── */
.page-body-inner { min-width: 0; }

/* ── Greeting ── */
.dh-greet {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 26px;
  align-items: end;
  padding: 6px 0 26px;
  border-bottom: 1px solid var(--border);
  margin: 0 0 22px;
}
.dh-greet-eyebrow {
  font-size: 11px; font-weight: 600;
  letter-spacing: 1.6px; text-transform: uppercase;
  color: var(--gold-dark);
  margin-bottom: 10px;
  display: flex; align-items: center; gap: 9px;
}
.dh-greet-eyebrow::before {
  content: ""; width: 18px; height: 1px;
  background: var(--gold-dark);
}
.dh-greet-title {
  font-family: var(--font-serif);
  font-size: 38px; font-weight: 600;
  letter-spacing: -0.5px; line-height: 1.05; color: var(--text);
}
.dh-greet-title em { font-style: italic; color: var(--gold-dark); font-weight: 600; }
.dh-greet-sub { margin-top: 12px; font-size: 14px; color: var(--text-3); max-width: 560px; line-height: 1.55; }
.dh-greet-meta {
  display: flex; align-items: center; gap: 0;
  padding: 14px 22px;
  border-radius: var(--radius-lg);
  background: var(--surface);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-xs);
}
.dh-greet-mcell { display: flex; flex-direction: column; gap: 2px; padding: 0 22px; }
.dh-greet-mcell + .dh-greet-mcell { border-left: 1px solid var(--border); }
.dh-greet-mlabel { font-size: 10px; letter-spacing: 1px; text-transform: uppercase; color: var(--text-4); font-weight: 600; }
.dh-greet-mval {
  font-family: var(--font-serif); font-size: 18px; font-weight: 600; color: var(--text);
  display: inline-flex; align-items: center; gap: 5px;
}
.dh-greet-mval.ok { color: var(--green-dark); }

/* ── Overview banner ── */
.dh-overview-banner {
  margin-bottom: 20px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-left: 3px solid var(--gold-dark);
  border-radius: var(--radius-lg); padding: 16px 20px;
  display: flex; align-items: center; gap: 16px; 
}
.dh-overview-icon {
  width: 40px; height: 40px; border-radius: var(--radius-sm);
  background: var(--icon-bg-gold); display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; color: var(--gold-dark);
}

/* ── Section header ── */
.dh-sh {
  display: flex; align-items: flex-end; justify-content: space-between;
  margin: 28px 0 14px;
  gap: 16px;
}
.dh-sh-l { display: flex; flex-direction: column; gap: 3px; }
.dh-sh-eyebrow { font-size: 10px; font-weight: 600; letter-spacing: 1.4px; text-transform: uppercase; color: var(--gold-dark); }
.dh-sh-title { font-family: var(--font-serif); font-size: 20px; font-weight: 600; letter-spacing: -0.2px; color: var(--text); }
.dh-sh-link {
  font-size: 12px; font-weight: 600; color: var(--text-3);
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 0; text-decoration: none; background: none; border: none; cursor: pointer;
  transition: color .18s ease;
}
.dh-sh-link:hover { color: var(--gold-dark); }
.dh-sh-link:hover :deep(.aegis-icon) { transform: translateX(3px); }

/* ── Continuity hero ── */
.dh-continuity {
  display: grid; grid-template-columns: 1.4fr 1fr;
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); overflow: hidden; 
  margin-bottom: 0;
}
.dh-cn-left {
  padding: 28px 28px 24px; display: flex; flex-direction: column; gap: 18px;
  border-right: 1px solid var(--border);
}
.dh-cn-eyebrow {
  display: flex; align-items: center; gap: 8px;
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--green-dark);
}
.dh-cn-pulse {
  width: 7px; height: 7px; border-radius: var(--radius-full);
  background: var(--green); box-shadow: 0 0 0 2px var(--green); flex-shrink: 0;
  animation: pulse-ring 2s ease-out infinite;
}
@keyframes pulse-ring {
  0%   { box-shadow: 0 0 0 0 rgba(76,175,125,0.6); }
  70%  { box-shadow: 0 0 0 6px rgba(76,175,125,0); }
  100% { box-shadow: 0 0 0 0 rgba(76,175,125,0); }
}
.dh-cn-title  { font-family: var(--font-serif); font-size: 22px; font-weight: 700; color: var(--text); line-height: 1.25; }
.dh-cn-desc   { font-size: 13px; color: var(--text-2); line-height: 1.6; }
.dh-cn-stewards { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.dh-cn-stew   { display: flex; align-items: center; gap: 10px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 10px 12px; }
.dh-cn-savatar {
  width: 32px; height: 32px; border-radius: var(--radius-full);
  background: var(--icon-bg-gold); color: var(--gold-dark);
  font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.dh-cn-savatar.support { background: var(--blue-light); color: var(--blue-dark); }
.dh-cn-stew-info { flex: 1; min-width: 0; }
.dh-cn-srole { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-4); }
.dh-cn-sname { font-size: 13px; font-weight: 600; color: var(--text); margin-top: 1px; }
.dh-cn-stat  { font-size: 11px; font-weight: 700; color: var(--green-dark); }
.dh-cn-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.dh-cn-right  { padding: 28px 24px; display: flex; flex-direction: column; gap: 14px; }
.dh-cn-rhead  { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.dh-cn-rtag   { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--orange-dark); margin-bottom: 0; }
.dh-cn-due    { font-family: var(--font-serif); font-size: 18px; font-weight: 700; color: var(--text); }
.dh-cn-due small { display: block; font-family: var(--font-sans); font-size: 11px; font-weight: 400; color: var(--text-4); margin-top: 3px; }
.dh-cn-bar    { position: relative; height: 6px; background: var(--surface-3); border-radius: var(--radius-full); margin: 4px 0; }
.dh-cn-bar-fill { position: absolute; left: 0; top: 0; bottom: 0; width: 70%; background: var(--gold-dark); border-radius: var(--radius-full); }
.dh-cn-bar-marker { position: absolute; right: 30%; top: 50%; transform: translate(50%,-50%); width: 10px; height: 10px; border-radius: var(--radius-full); background: var(--primary); border: 2px solid var(--surface);  }
.dh-cn-bar-labels { display: flex; justify-content: space-between; font-size: 10px; color: var(--text-4); font-weight: 600; }
.dh-cn-todos  { display: flex; flex-direction: column; gap: 7px; }
.dh-cn-todo   { display: flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--text-3); }
.dh-cn-todo.done { color: var(--green-dark); }

/* ── Glance ── */
.dh-glance { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 0; }
.dh-gl-card {
  background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg);
  padding: 16px 18px; 
  transition: transform var(--transition), box-shadow var(--transition);
}
.dh-gl-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
.dh-gl-head  { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.dh-gl-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-4); }
.dh-gl-icon  { width: 26px; height: 26px; border-radius: var(--radius-sm); background: var(--icon-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; }
.dh-gl-icon.warn { background: var(--orange-light); color: var(--orange); }
.dh-gl-val   { font-family: var(--font-serif); font-size: 28px; font-weight: 700; color: var(--text); line-height: 1; }
.dh-gl-val small { font-family: var(--font-sans); font-size: 13px; font-weight: 400; color: var(--text-4); }
.dh-gl-sub   { font-size: 12px; color: var(--text-3); margin-top: 6px; }

/* ── Two-col layout ── */
.dh-cols { display: grid; grid-template-columns: 1fr 340px; gap: 20px; margin-bottom: 0; }

/* ── Credentials ── */
.dh-cred-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg);  }
.dh-cred-row  { display: flex; align-items: center; gap: 14px; padding: 14px 18px; border-bottom: 1px solid var(--border); transition: background .14s ease; position: relative; }
.dh-cred-row:last-of-type { border-bottom: none; }
.dh-cred-row:hover { background: var(--surface-2); }
.dh-cred-icon { width: 32px; height: 32px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid transparent; }
.dh-cred-icon.ok   { background: var(--green-light);  color: var(--green-dark);  border-color: var(--fade-green); }
.dh-cred-icon.crit { background: var(--red-light);    color: var(--red);         border-color: var(--fade-red); }
.dh-cred-info  { flex: 1; min-width: 0; }
.dh-cred-title { font-size: 13px; font-weight: 700; color: var(--text); }
.dh-cred-sub   { font-size: 11px; color: var(--text-4); margin-top: 2px; }
.dh-cred-meter { text-align: right; min-width: 130px; }
.dh-cred-date  { font-size: 12px; font-weight: 600; color: var(--text); }
.dh-cred-date.crit { color: var(--red); }
.dh-cred-bar   { height: 4px; background: var(--surface-3); border-radius: var(--radius-full); margin: 5px 0; overflow: hidden; }
.dh-cred-bar-fill { height: 100%; border-radius: var(--radius-full); width: 78%; }
.dh-cred-bar-fill.ok   { background: var(--green); width: 78%; }
.dh-cred-bar-fill.crit { background: var(--red);   width: 8%; }
.dh-cred-days  { font-size: 11px; font-weight: 600; }
.dh-cred-days.ok   { color: var(--green-dark); }
.dh-cred-days.crit { color: var(--red); font-weight: 600; }
.dh-cred-act   { display: flex; gap: 5px; flex-shrink: 0; }
.dh-cred-foot  { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 18px; background: var(--surface-2); font-size: 12px; color: var(--text-2); }
.crit-text { color: var(--red); font-weight: 600; }

/* ── Attention card ── */
.dh-attention { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg);  }
.dh-att-head  { display: flex; align-items: center; gap: 8px; padding: 14px 16px; border-bottom: 1px solid var(--border); }
.dh-att-icon  { width: 28px; height: 28px; border-radius: var(--radius-sm); background: var(--orange-light); color: var(--orange); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.dh-att-title { font-size: 13px; font-weight: 700; color: var(--text); flex: 1; }
.dh-att-count { width: 22px; height: 22px; border-radius: var(--radius-full); background: var(--red); color: var(--text-inverted); font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.dh-att-list  { display: flex; flex-direction: column; }
.dh-att-item  { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-bottom: 1px solid var(--border); }
.dh-att-item:last-child { border-bottom: none; }
.dh-att-bullet { width: 8px; height: 8px; border-radius: var(--radius-full); flex-shrink: 0; }
.dh-att-bullet.crit { background: var(--red); }
.dh-att-bullet.warn { background: var(--orange); }
.dh-att-text  { flex: 1; min-width: 0; }
.dh-att-h     { font-size: 12px; font-weight: 700; color: var(--text); }
.dh-att-d     { font-size: 11px; color: var(--text-4); margin-top: 2px; }

/* ── Quick actions ── */
.dh-quick       { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg);  overflow: hidden; margin-top: 14px; padding: 16px; }
.dh-quick-title { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 12px; }
.dh-quick-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.dh-quick-grid .btn { justify-content: flex-start; gap: 7px; }

/* ── Network ── */
.network-carousel-section {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); padding: 18px 24px 22px;
  margin-bottom: 0; 
}
.nw-head { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 16px; }
.nw-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.nw-card { position: relative; display: flex; flex-direction: column; gap: 14px; padding: 18px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-xs); cursor: pointer; transition: box-shadow .18s ease, border-color .18s ease, transform .18s ease; }
.nw-card:hover { transform: translateY(-2px);  border-color: var(--surface-4); }
.nw-top    { display: flex; align-items: flex-start; gap: 12px; }
.nw-avatar { width: 48px; height: 48px; border-radius: var(--radius); background: var(--gold-dark); color: var(--text-inverted); display: flex; align-items: center; justify-content: center; font-family: var(--font-serif); font-size: 16px; font-weight: 700; flex-shrink: 0; }
.nw-info   { min-width: 0; flex: 1; }
.nw-name   { font-family: var(--font-serif); font-size: 15px; font-weight: 600; color: var(--text); line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.nw-role   { font-size: 12px; color: var(--text-3); margin-top: 3px; }
.nw-tags   { display: flex; gap: 5px; margin-left: auto; flex-shrink: 0; }
.nw-pill   { width: 22px; height: 22px; border-radius: var(--radius-sm); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.nw-pill.net { background: var(--green-light); color: var(--green-dark); }
.nw-pill.svc { background: var(--badge-bg-gold); color: var(--gold-dark); }
.nw-tag    { font-size: 10px; font-weight: 700; color: var(--text-3); background: var(--surface-3); padding: 2px 7px; border-radius: var(--radius-full); text-transform: uppercase; letter-spacing: 0.4px; white-space: nowrap; }
.nw-meta   { display: flex; align-items: center; gap: 14px; font-size: 12px; color: var(--text-3); padding-top: 12px; border-top: 1px solid var(--border); }
.nw-meta-item { display: inline-flex; align-items: center; gap: 5px; }
.nw-cta    { margin-left: auto; display: flex; gap: 6px; flex-shrink: 0; }
.nw-btn    { width: 28px; height: 28px; border-radius: var(--radius-sm); background: var(--surface-2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--text-3); cursor: pointer; transition: border-color .18s ease, color .18s ease, background .18s ease; text-decoration: none; }
.nw-btn:hover { background: var(--surface-3); border-color: var(--soft-gold); color: var(--gold-dark); }
.nw-btn.primary { background: var(--gold-dark); color: var(--text-inverted); border-color: var(--gold-dark); }
.nw-btn.primary:hover { background: var(--primary); border-color: var(--primary); }

/* ── Modal helpers ── */
.dh-modal-rows { display: flex; flex-direction: column; }
.dh-modal-row  { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border); gap: 12px; font-size: 13px; }
.dh-modal-row:last-child { border-bottom: none; }
.dh-modal-lbl  { color: var(--text-4); font-weight: 600; font-size: 12px; }
.dh-review-checklist { display: flex; flex-direction: column; gap: 10px; }

/* ── CEU table ── */
.dh-ceu-table { display: flex; flex-direction: column; }
.dh-ceu-row   { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border); }
.dh-ceu-row:last-child { border-bottom: none; }
.dh-ceu-title { font-size: 13px; font-weight: 700; color: var(--text); }
.dh-ceu-meta  { font-size: 11px; color: var(--text-4); margin-top: 2px; }

/* ── Responsive ── */
@media (max-width: 1024px) {
  .dh-cols  { grid-template-columns: 1fr; }
  .dh-glance { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 900px) {
  .dh-greet { grid-template-columns: 1fr; }
  .dh-continuity { grid-template-columns: 1fr; }
  .nw-grid  { grid-template-columns: 1fr; }
}
</style>
