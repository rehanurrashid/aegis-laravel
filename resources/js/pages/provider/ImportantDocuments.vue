<template>
  <AppLayout>

    <!-- HERO -->
    <AegisHeroBanner
      eyebrow="Document Center"
      title="Important Documents"
      subtitle="Manage agreements and supporting documents shared between Practitioners, Continuity Stewards, and Support Stewards — drafted from vetted templates, signed in-portal, and audit-logged automatically."
      quiet
    >
      <template #actions>
        <a :href="route('provider.activity', { event_type: 'document' })" class="btn-hero-ghost is-on-light" data-tooltip="Document activity log">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
        <button class="btn-hero-solid is-on-light" @click="openWizard">
          <AegisIcon name="plus" :size="14" /> New Agreement
        </button>
      </template>
    </AegisHeroBanner>

    <!-- STAT CHIPS (sibling of hero, never inside) -->
    <div class="stat-chips-row">
      <AegisStatChip icon="file-text"  :value="docStats.total"          label="Total Documents" />
      <AegisStatChip icon="pen-tool"   :value="docStats.pending_my_sig" label="Awaiting My Signature" />
      <AegisStatChip icon="clock"      :value="docStats.awaiting_counter" label="Awaiting Counterparty" />
      <AegisStatChip icon="calendar"   :value="docStats.expiring"       label="Expiring in 30 Days" />
    </div>

    <!-- PLAN REVIEW ALERT -->
    <PlanReviewAlert
      :plan-status="planStatus"
      :annual-review-date="annualReviewDate"
      :has-draft-in-progress="hasDraftInProgress"
      :draft-plan-version="draftPlanVersion"
      context="documents"
    />

    <!-- ACTION REQUIRED ALERT -->
    <div v-if="docStats.pending_my_sig > 0" class="alert alert-gold" style="margin-bottom:18px">
      <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
      <div class="alert-content">
        <div class="alert-title">{{ docStats.pending_my_sig }} Agreement{{ docStats.pending_my_sig !== 1 ? 's' : '' }} Require Your Signature</div>
        <div>Review agreements that are pending your signature.</div>
        <div style="margin-top:10px">
          <button class="btn btn-primary" @click="setActiveTab('pending_sign')">
            <AegisIcon name="file-pen" :size="13" /> View Pending
          </button>
        </div>
      </div>
    </div>

    <!-- SIDEBAR LAYOUT -->
    <div class="doc-layout">

      <!-- LEFT SIDEBAR -->
      <nav class="page-sidebar" role="tablist" aria-label="Document sections" style="width:220px;flex-shrink:0;">

        <div class="page-sidebar-group">
          <div class="page-sidebar-label">Documents</div>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'all' }" @click="setActiveTab('all')">
            <span class="page-sidebar-icon"><AegisIcon name="file-text" :size="15" /></span>
            All Documents
            <span v-if="menuBadges.all > 0" class="page-sidebar-badge">{{ menuBadges.all }}</span>
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'pending_sign' }" @click="setActiveTab('pending_sign')">
            <span class="page-sidebar-icon"><AegisIcon name="file-pen" :size="15" /></span>
            Pending My Signature
            <span v-if="menuBadges.pending_sign > 0" class="page-sidebar-badge">{{ menuBadges.pending_sign }}</span>
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'countersign' }" @click="setActiveTab('countersign')">
            <span class="page-sidebar-icon"><AegisIcon name="clock" :size="15" /></span>
            Awaiting Countersig.
            <span v-if="menuBadges.countersign > 0" class="page-sidebar-badge">{{ menuBadges.countersign }}</span>
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'active' }" @click="setActiveTab('active')">
            <span class="page-sidebar-icon"><AegisIcon name="check-circle" :size="15" /></span>
            Active &amp; Signed
            <span v-if="menuBadges.active > 0" class="page-sidebar-badge">{{ menuBadges.active }}</span>
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'expiring' }" @click="setActiveTab('expiring')">
            <span class="page-sidebar-icon"><AegisIcon name="calendar" :size="15" /></span>
            Expiring Soon
            <span v-if="menuBadges.expiring > 0" class="page-sidebar-badge">{{ menuBadges.expiring }}</span>
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'archived' }" @click="setActiveTab('archived')">
            <span class="page-sidebar-icon"><AegisIcon name="archive" :size="15" /></span>
            Archived
          </button>
        </div>

        <div class="page-sidebar-group">
          <div class="page-sidebar-label">Supporting Documents</div>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'amendments' }" @click="setActiveTab('amendments')">
            <span class="page-sidebar-icon"><AegisIcon name="edit" :size="15" /></span>
            Amendments
            <span v-if="menuBadges.amendments > 0" class="page-sidebar-badge">{{ menuBadges.amendments }}</span>
          </button>
          <button type="button" role="tab" class="page-sidebar-item" :class="{ active: activeTab === 'supporting' }" @click="setActiveTab('supporting')">
            <span class="page-sidebar-icon"><AegisIcon name="upload" :size="15" /></span>
            Uploaded Files
            <span v-if="menuBadges.supporting > 0" class="page-sidebar-badge">{{ menuBadges.supporting }}</span>
          </button>
        </div>

        <div class="page-sidebar-group">
          <div class="page-sidebar-label">Tools</div>
          <button type="button" role="tab" class="page-sidebar-item" @click="openModal('exportModal')">
            <span class="page-sidebar-icon"><AegisIcon name="download" :size="15" /></span>
            Export Documents
          </button>
        </div>


      </nav>

      <!-- MAIN CONTENT -->
      <div class="doc-content">

        <!-- PARTY CATEGORY PILL ROW (shown on all document list views) -->
        <div v-if="showDocList" class="doc-party-pills" role="tablist" aria-label="Filter by party">
          <button
            v-for="pill in partyPills"
            :key="pill.key"
            type="button"
            role="tab"
            class="tab-pill"
            :class="{ active: categoryFilter === pill.key }"
            @click="setCategoryFilter(pill.key)"
          >
            {{ pill.label }}
            <span class="badge-pill">{{ livePillCounts[pill.key] ?? 0 }}</span>
          </button>
        </div>

        <!-- SEARCH / TYPE FILTER BAR -->
        <div v-if="showFilterBar" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;align-items:center">
          <div class="input-group" style="min-width:220px;max-width:320px;flex:1">
            <span class="input-group-icon"><AegisIcon name="search" :size="14" /></span>
            <input class="form-input form-input-sm" type="text" v-model="searchQ" placeholder="Search agreements..." />
          </div>
          <select class="form-select form-select-sm" v-model="typeFilter" style="max-width:220px">
            <option value="">All Types</option>
            <option value="MSA">MSA</option>
            <option value="NDA">NDA</option>
            <option value="SOW">SOW</option>
            <option value="MOU">MOU</option>
            <option value="SLA">SLA</option>
            <option value="BAA">BAA</option>
            <option value="ICA">ICA</option>
          </select>
        </div>

        <!-- ── TAB: ALL DOCUMENTS / PENDING / COUNTERSIGN / ACTIVE / EXPIRING / ARCHIVED ── -->
        <div v-show="showDocList">
          <div class="doc-section-head">
            <div>
              <div class="doc-section-title">{{ tabTitle }}</div>
              <div class="doc-section-sub">{{ tabSubtitle }}</div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <button class="btn btn-outline" @click="openModal('addDocumentModal')">
                <AegisIcon name="upload" :size="13" /> Add Supporting Doc
              </button>
            </div>
          </div>

          <div class="ag-list">
            <!-- Party-filtered empty state -->
            <div v-if="filteredDocs.length === 0 && categoryFilter" class="card" style="padding:28px 24px;text-align:center">
              <div style="color:var(--text-4);margin-bottom:10px"><AegisIcon name="filter" :size="24" /></div>
              <div style="font-family:var(--font-serif);font-size:16px;font-weight:700;color:var(--text);margin-bottom:6px">
                No {{ partyPills.find(p => p.key === categoryFilter)?.label }} Documents
              </div>
              <div style="font-size:13px;color:var(--text-3);margin-bottom:16px">
                No documents match this party combination within the current view.
              </div>
              <button class="btn btn-outline" @click="setCategoryFilter('')">
                <AegisIcon name="x" :size="13" /> Clear Party Filter
              </button>
            </div>
            <!-- Default empty state -->
            <AegisEmptyState
              v-else-if="filteredDocs.length === 0"
              icon="file-text"
              :title="emptyTitle"
              :subtitle="emptySubtitle"
            />

            <article
              v-for="doc in filteredDocs"
              :key="doc.id"
              class="card ag-card"
              :class="docCardClass(doc)"
            >
              <div class="ag-row">
                <div class="ag-icon" :class="{ 'is-muted': doc.status === 'draft' || doc.status === 'terminated' || doc.status === 'archived' }">
                  <AegisIcon name="file-text" :size="19" />
                </div>
                <div class="ag-main">
                  <div class="ag-eyebrow">{{ doc.doc_type_label }}</div>
                  <div class="ag-title">{{ doc.title }}</div>
                  <div class="ag-ref">
                    {{ doc.category_label }} &middot; {{ doc.reference }}
                    <span v-if="doc.amendment_count > 0" class="ag-amend-badge">
                      {{ doc.amendment_count }} amendment{{ doc.amendment_count !== 1 ? 's' : '' }}
                    </span>
                  </div>
                  <div class="ag-line">
                    <span class="ag-people">
                      <span
                        v-for="person in (doc.people || [])"
                        :key="person.initials"
                        class="avatar avatar-xs"
                        :class="person.color === 'gold' ? 'avatar-gold' : 'avatar-dark'"
                      >{{ person.initials }}</span>
                      <span class="ag-name">{{ doc.people_label }}</span>
                    </span>
                    <span v-if="doc.when_text" class="sep"></span>
                    <span v-if="doc.when_text" class="ag-when" :class="doc.when_class">
                      <AegisIcon v-if="doc.when_icon" :name="doc.when_icon" :size="12" />
                      {{ doc.when_text }}
                    </span>
                  </div>
                </div>
                <div class="ag-aside">
                  <AegisBadge :label="doc.badge_label" :variant="doc.badge_variant" />
                  <div class="ag-actions">
                    <!-- Sign -->
                    <template v-if="doc.primary_action === 'sign'">
                      <button class="btn btn-primary" :disabled="signBusy" @click="openSignModal(doc)">
                        <AegisIcon name="file-pen" :size="13" /> {{ signBusy ? 'Signing...' : 'Sign' }}
                      </button>
                      <button class="btn-icon" data-tooltip="View agreement" @click="openViewModal(doc)"><AegisIcon name="eye" :size="14" /></button>
                      <button class="btn-icon" data-tooltip="More actions" @click="openActionsModal(doc)"><AegisIcon name="more" :size="14" /></button>
                    </template>
                    <!-- Send reminder (awaiting countersig) -->
                    <template v-else-if="doc.primary_action === 'remind'">
                      <button class="btn btn-outline" :disabled="remindBusy" @click="sendReminder(doc)">
                        <AegisIcon name="bell" :size="13" /> Send Reminder
                      </button>
                      <button class="btn-icon" data-tooltip="View agreement" @click="openViewModal(doc)"><AegisIcon name="eye" :size="14" /></button>
                      <button class="btn-icon" data-tooltip="More actions" @click="openActionsModal(doc)"><AegisIcon name="more" :size="14" /></button>
                    </template>
                    <!-- Renew (expiring/expired) -->
                    <template v-else-if="doc.primary_action === 'renew'">
                      <button class="btn btn-primary" @click="openRenewModal(doc)">
                        <AegisIcon name="refresh-cw" :size="13" /> Renew
                      </button>
                      <button class="btn-icon" data-tooltip="View agreement" @click="openViewModal(doc)"><AegisIcon name="eye" :size="14" /></button>
                      <button class="btn-icon" data-tooltip="More actions" @click="openActionsModal(doc)"><AegisIcon name="more" :size="14" /></button>
                    </template>
                    <!-- Edit (draft) -->
                    <template v-else-if="doc.primary_action === 'edit'">
                      <button class="btn btn-outline" @click="openWizard">
                        <AegisIcon name="pencil" :size="13" /> Continue Editing
                      </button>
                      <button
                        class="btn-icon btn-icon-danger"
                        data-tooltip="Delete draft"
                        @click="confirmAction({ title:'Delete Draft', message:'Delete this draft? This cannot be undone.', confirmLabel:'Delete', destructive:true }, () => deleteDraft(doc))"
                      ><AegisIcon name="trash" :size="14" /></button>
                    </template>
                    <!-- View (active/archived/etc) -->
                    <template v-else>
                      <button class="btn-icon" data-tooltip="View agreement" @click="openViewModal(doc)"><AegisIcon name="eye" :size="14" /></button>
                      <button v-if="doc.can_amend" class="btn-icon" data-tooltip="Request amendment" @click="openAmendModalDirect(doc)"><AegisIcon name="edit" :size="14" /></button>
                      <button class="btn-icon" data-tooltip="Download PDF" @click="toast.info('PDF download coming soon.')"><AegisIcon name="download" :size="14" /></button>
                      <button class="btn-icon" data-tooltip="More actions" @click="openActionsModal(doc)"><AegisIcon name="more" :size="14" /></button>
                    </template>
                  </div>
                </div>
              </div>
            </article>
          </div>
        </div>

        <!-- ── TAB: AMENDMENTS ── -->
        <div v-show="activeTab === 'amendments'">
          <div class="doc-section-head">
            <div>
              <div class="doc-section-title">Amendments</div>
              <div class="doc-section-sub">Proposed changes to active agreements, requiring both parties to sign</div>
            </div>
          </div>
          <div class="ag-list">
            <AegisEmptyState
              v-if="amendmentDocs.length === 0"
              icon="edit"
              title="No Amendments"
              subtitle="Amendment documents will appear here when you or a counterparty request changes to an existing agreement."
            />
            <article v-for="doc in amendmentDocs" :key="doc.id" class="card ag-card" :class="docCardClass(doc)">
              <div class="ag-row">
                <div class="ag-icon"><AegisIcon name="edit" :size="19" /></div>
                <div class="ag-main">
                  <div class="ag-eyebrow">AMENDMENT · {{ doc.doc_type_label }}</div>
                  <div class="ag-title">{{ doc.title }}</div>
                  <div class="ag-ref">{{ doc.reference }}</div>
                  <div class="ag-line">
                    <span class="ag-when" :class="doc.when_class">
                      <AegisIcon v-if="doc.when_icon" :name="doc.when_icon" :size="12" />
                      {{ doc.when_text }}
                    </span>
                  </div>
                </div>
                <div class="ag-aside">
                  <AegisBadge :label="doc.badge_label" :variant="doc.badge_variant" />
                  <div class="ag-actions">
                    <button class="btn btn-primary" v-if="doc.primary_action === 'sign'" @click="openSignModal(doc)">
                      <AegisIcon name="file-pen" :size="13" /> Sign
                    </button>
                    <button class="btn-icon" data-tooltip="View" @click="openViewModal(doc)"><AegisIcon name="eye" :size="14" /></button>
                  </div>
                </div>
              </div>
            </article>
          </div>
        </div>

        <!-- ── TAB: UPLOADED FILES (SUPPORTING DOCS) ── -->
        <div v-show="activeTab === 'supporting'">
          <div class="doc-section-head">
            <div>
              <div class="doc-section-title">Uploaded Files</div>
              <div class="doc-section-sub">Attorney letters, credential proofs, and other reference materials</div>
            </div>
            <button class="btn btn-outline" @click="openModal('addDocumentModal')">
              <AegisIcon name="upload" :size="13" /> Add Document
            </button>
          </div>

          <div class="list-group" style="margin-bottom:14px">
            <AegisEmptyState
              v-if="props.supportingDocs.length === 0"
              icon="upload"
              title="No Uploaded Files"
              subtitle="Upload amendments, references, or other documents to share with your stewards."
            />
            <div v-for="sdoc in props.supportingDocs" :key="sdoc.id" class="list-group-item" style="gap:12px">
              <span class="doc-file-icon"><AegisIcon name="file-text" :size="16" /></span>
              <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:700;color:var(--text)">{{ sdoc.title }}</div>
                <div style="font-size:12px;color:var(--text-3)">{{ sdoc.meta }}</div>
                <span v-if="sdoc.has_file" class="vault-badge"><AegisIcon name="lock" :size="10" /> Backed by Vault</span>
              </div>
              <AegisBadge :label="sdoc.badge_label" :variant="sdoc.badge_variant" />
              <button class="btn-icon" data-tooltip="Download" @click="toast.info('Preparing download...')"><AegisIcon name="download" :size="14" /></button>
              <button
                class="btn-icon btn-icon-danger"
                data-tooltip="Remove"
                @click="confirmAction({ title:'Remove File', message:'Remove this document?', confirmLabel:'Remove', destructive:true }, () => deleteSupporting(sdoc))"
              ><AegisIcon name="trash" :size="14" /></button>
            </div>
          </div>

          <div class="upload-zone" @click="openModal('addDocumentModal')">
            <div class="upload-zone-icon"><AegisIcon name="upload" :size="22" /></div>
            <div class="upload-zone-title">Add a Supporting Document</div>
            <div class="upload-zone-sub">Upload an amendment, reference, or other document to share with your stewards</div>
          </div>
        </div>

      </div><!-- /doc-content -->
    </div><!-- /doc-layout -->


    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- MODALS                                                      -->
    <!-- ═══════════════════════════════════════════════════════════ -->

    <!-- ═══ MODAL: NEW AGREEMENT WIZARD ═══ -->
    <AegisModal
      :model-value="isOpen('newAgreementModal').value"
      title="Create New Agreement"
      size="xl"
      @update:model-value="v => !v && closeWizard()"
    >
      <!-- Stepper header -->
      <div style="margin:-8px -24px 20px;padding:14px 24px 16px;border-bottom:1px solid var(--border);background:var(--surface-2)">
        <div style="font-size:12px;color:var(--text-3);font-weight:600;margin-bottom:12px">Step {{ wizStep }} of 4 — {{ stepSubs[wizStep - 1] }}</div>
        <div class="workflow-stepper">
          <div
            v-for="(s, i) in stepLabels"
            :key="s.short"
            class="wf-step"
            :class="{ done: i < wizStep - 1, current: i === wizStep - 1, future: i > wizStep - 1 }"
            :style="i < wizStep - 1 ? 'cursor:pointer' : ''"
            @click="i < wizStep - 1 ? (wizStep = i + 1) : null"
          >
            <div class="wf-node">
              <AegisIcon v-if="i < wizStep - 1" name="check" :size="14" />
              <span v-else>{{ i + 1 }}</span>
            </div>
            <div class="wf-label">{{ s.line1 }}<br>{{ s.line2 }}</div>
          </div>
        </div>
      </div>

      <!-- Step 1: Agreement Type -->
      <div v-show="wizStep === 1">
        <div class="section-title" style="margin-bottom:16px">What kind of agreement are you creating?</div>
        <label class="form-label" style="margin-bottom:8px">Agreement Category <span class="required">*</span></label>
        <div class="agr-cat-grid">
          <div
            v-for="cat in agrCategories" :key="cat.value"
            class="agr-cat-card" :class="{ selected: wiz.category === cat.value }"
            @click="wiz.category = cat.value"
          >
            <div class="agr-cat-avatar"><AegisIcon :name="cat.icon" :size="16" /></div>
            <div>
              <div class="agr-cat-title">{{ cat.title }}</div>
              <div class="agr-cat-sub">{{ cat.sub }}</div>
            </div>
          </div>
        </div>
        <div class="form-row" style="margin-top:16px">
          <div class="form-group">
            <label class="form-label">Document Type <span class="required">*</span></label>
            <select
              class="form-select"
              v-model="wiz.docType"
              :class="{ 'is-error': fieldError('wiz_docType') }"
              @blur="v$.wiz_docType.$touch()"
            >
              <option value="">Select Document Type</option>
              <option value="MSA">MSA — Master Service Agreement</option>
              <option value="NDA">NDA — Non-Disclosure Agreement</option>
              <option value="SOW">SOW — Statement of Work</option>
              <option value="MOU">MOU — Memorandum of Understanding</option>
              <option value="SLA">SLA — Service Level Agreement</option>
              <option value="REF">Referral Agreement</option>
              <option value="ICA">Independent Contractor Agreement</option>
              <option value="BAA">BAA — Business Associate Agreement (HIPAA)</option>
            </select>
            <div v-if="fieldError('wiz_docType')" class="form-error">{{ fieldError('wiz_docType') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Reference / Title <span style="color:var(--text-4)">(optional)</span></label>
            <input type="text" class="form-input" v-model="wiz.reference" placeholder="Auto-generated if blank (e.g. MSA-2025-015)" />
          </div>
        </div>
      </div>

      <!-- Step 2: Parties & Details -->
      <div v-show="wizStep === 2">
        <div class="party-grid">
          <div class="party-col provider">
            <div class="party-label">Party A — Provider (You)</div>
            <div class="party-name">{{ providerName }}</div>
            <div style="font-size:12px;color:var(--text-3);margin-top:2px">Practitioner · Plan Holder</div>
          </div>
          <div class="party-col cs">
            <div class="party-label">Party B — Counterparty</div>
            <div v-if="wiz.partyB" class="party-name">{{ selectedPartyBName }}</div>
            <div v-else style="font-size:13px;color:var(--text-4);font-style:italic">Select below</div>
          </div>
        </div>

        <label class="form-label" style="margin-bottom:8px">Select Counterparty <span class="required">*</span></label>
        <div class="input-group" style="margin-bottom:8px;max-width:340px">
          <span class="input-group-icon"><AegisIcon name="search" :size="14" /></span>
          <input class="form-input" type="text" v-model="wiz.partyBSearch" placeholder="Search stewards..." />
        </div>
        <div style="max-height:220px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:14px">
          <div v-if="filteredPartyB.length === 0" style="padding:24px;text-align:center;color:var(--text-4);font-size:13px">
            No stewards found. Add stewards on the Continuity Stewards page first.
          </div>
          <div
            v-for="p in filteredPartyB" :key="p.id"
            class="party-search-result"
            :class="{ selected: wiz.partyB === p.id }"
            @click="wiz.partyB = p.id"
          >
            <div class="party-avatar-sm">{{ p.initials }}</div>
            <div class="party-info-sm">
              <div class="party-name-sm">{{ p.name }}</div>
              <div class="party-meta-sm">{{ p.meta }}</div>
            </div>
            <AegisIcon v-if="wiz.partyB === p.id" name="check" :size="14" style="color:var(--gold-dark)" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Effective Date <span class="required">*</span></label>
            <input
              type="date"
              class="form-input"
              v-model="wiz.effectiveDate"
              :class="{ 'is-error': fieldError('wiz_effectiveDate') }"
              @blur="v$.wiz_effectiveDate.$touch()"
            />
            <div v-if="fieldError('wiz_effectiveDate')" class="form-error">{{ fieldError('wiz_effectiveDate') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Expiration Date <span style="color:var(--text-4)">(optional)</span></label>
            <input type="date" class="form-input" v-model="wiz.expirationDate" />
          </div>
        </div>
        <div class="form-group" v-if="wiz.expirationDate">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
            <input type="checkbox" v-model="wiz.autoRenew" style="accent-color:var(--gold-dark)" />
            Enable auto-renewal (extend by 1 year before expiry)
          </label>
        </div>
      </div>

      <!-- Step 3: Clauses & Notes -->
      <div v-show="wizStep === 3">
        <div class="section-title" style="margin-bottom:12px">Standard Clauses</div>
        <div v-for="clause in clauses" :key="clause.id" class="clause-section">
          <div class="clause-header" @click="clause.open = !clause.open">
            <div class="clause-header-left">
              <div class="clause-num">{{ clause.num }}</div>
              <div class="clause-title">{{ clause.title }}</div>
              <span class="clause-tag" :class="clause.tagClass">{{ clause.tagLabel }}</span>
            </div>
            <AegisIcon :name="clause.open ? 'chevron-up' : 'chevron-down'" :size="14" />
          </div>
          <div class="clause-body" :class="{ open: clause.open }">
            <div v-for="field in clause.fields" :key="field.label" class="form-group" style="margin-bottom:10px">
              <label class="form-label">{{ field.label }}</label>
              <select v-if="field.type === 'select'" class="form-select" v-model="field.value">
                <option v-for="opt in field.options" :key="opt">{{ opt }}</option>
              </select>
              <textarea v-else-if="field.type === 'textarea'" class="form-textarea" rows="3" v-model="field.value" :placeholder="field.placeholder"></textarea>
              <input v-else type="text" class="form-input" v-model="field.value" :placeholder="field.placeholder" />
            </div>
          </div>
        </div>
        <div class="form-group" style="margin-top:14px">
          <label class="form-label">Additional Notes</label>
          <textarea class="form-textarea" rows="3" v-model="wiz.notes" placeholder="Any additional context or instructions for the counterparty..."></textarea>
        </div>
      </div>

      <!-- Step 4: Review & Confirm -->
      <div v-show="wizStep === 4">
        <div class="alert alert-info" style="margin-bottom:14px">
          <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
          <div class="alert-content">Review the agreement details before sending. The counterparty will receive an email notification to sign.</div>
        </div>
        <div class="info-card" style="margin-bottom:14px">
          <div class="info-card-title">Agreement Summary</div>
          <div class="info-card-row"><span class="info-card-key">Type</span><span class="info-card-val">{{ wiz.docType || '—' }}</span></div>
          <div class="info-card-row"><span class="info-card-key">Category</span><span class="info-card-val">{{ selectedCatTitle }}</span></div>
          <div class="info-card-row"><span class="info-card-key">Party A</span><span class="info-card-val">{{ providerName }}</span></div>
          <div class="info-card-row"><span class="info-card-key">Party B</span><span class="info-card-val">{{ selectedPartyBName || '—' }}</span></div>
          <div class="info-card-row"><span class="info-card-key">Effective Date</span><span class="info-card-val">{{ wiz.effectiveDate || '—' }}</span></div>
          <div class="info-card-row"><span class="info-card-key">Expiration</span><span class="info-card-val">{{ wiz.expirationDate || 'No expiry set' }}</span></div>
          <div class="info-card-row"><span class="info-card-key">Auto-Renew</span><span class="info-card-val">{{ wiz.autoRenew ? 'Yes' : 'No' }}</span></div>
        </div>
        <div style="font-size:12px;color:var(--text-3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;background:var(--surface-2)">
          By sending this agreement you confirm that all information is accurate. The counterparty will be notified to review and countersign. This agreement will become legally binding upon mutual execution.
        </div>
      </div>

      <template #footer>
        <button class="btn btn-outline" @click="wizStep > 1 ? wizStep-- : closeWizard()">
          {{ wizStep > 1 ? 'Back' : 'Cancel' }}
        </button>
        <div style="display:flex;gap:8px;margin-left:auto">
          <button v-if="wizStep < 4" class="btn btn-outline" @click="openModal('draftSaveModal')">
            <AegisIcon name="save" :size="13" /> Save Draft
          </button>
          <button v-if="wizStep < 4" class="btn btn-primary" @click="wizardNext">
            Continue <AegisIcon name="arrow-right" :size="13" />
          </button>
          <button v-if="wizStep === 4" class="btn btn-outline" :disabled="sendBusy" @click="saveDraftFromReview">
            <AegisIcon name="save" :size="13" /> Save as Draft
          </button>
          <button v-if="wizStep === 4" class="btn btn-primary" :disabled="sendBusy" @click="sendForSignature">
            <AegisIcon name="send" :size="13" /> {{ sendBusy ? 'Sending...' : 'Send for Signature' }}
          </button>
        </div>
      </template>
    </AegisModal>


    <!-- ═══ MODAL: SIGNATURE ═══ -->
    <AegisModal
      :model-value="isOpen('signatureModal').value"
      :title="activeDoc ? ('Sign — ' + activeDoc.reference) : 'Sign Agreement'"
      size="md"
      @update:model-value="v => !v && closeSignModal()"
    >
      <div v-if="activeDoc" class="info-card" style="margin-bottom:16px">
        <div class="info-card-title">Agreement Details</div>
        <div class="info-card-row"><span class="info-card-key">Document</span><span class="info-card-val">{{ activeDoc.title }}</span></div>
        <div class="info-card-row"><span class="info-card-key">Reference</span><span class="info-card-val">{{ activeDoc.reference }}</span></div>
        <div v-if="activeDoc.counterparty" class="info-card-row"><span class="info-card-key">Counterparty</span><span class="info-card-val">{{ activeDoc.counterparty.name }}</span></div>
        <div v-if="activeDoc.effective_date" class="info-card-row"><span class="info-card-key">Effective</span><span class="info-card-val">{{ activeDoc.effective_date }}</span></div>
      </div>

      <div class="form-group">
        <label class="form-label">Signature (type your full name) <span class="required">*</span></label>
        <input
          type="text"
          class="form-input"
          v-model="signForm.name"
          :class="{ 'is-error': fieldError('sign_name') }"
          @blur="v$.sign_name.$touch()"
          :placeholder="providerName"
        />
        <div v-if="fieldError('sign_name')" class="form-error">{{ fieldError('sign_name') }}</div>
        <div v-if="signForm.name" class="sig-preview">
          <span class="sig-name-display">{{ signForm.name }}</span>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:8px;margin-top:4px">
        <label class="sig-check-row" style="padding:10px 0">
          <input type="checkbox" v-model="signCheck1" style="accent-color:var(--gold-dark)" />
          <span class="sig-check-label">I have read and understand this agreement in full</span>
        </label>
        <label class="sig-check-row" style="padding:10px 0">
          <input type="checkbox" v-model="signCheck2" style="accent-color:var(--gold-dark)" />
          <span class="sig-check-label">I confirm I have the legal authority to enter into this agreement</span>
        </label>
      </div>

      <div class="alert alert-info" style="margin-top:14px;margin-bottom:0">
        <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
        <div class="alert-content">Your electronic signature is legally binding. Your IP address and timestamp will be recorded.</div>
      </div>

      <template #footer>
        <button class="btn btn-outline" @click="closeSignModal">Cancel</button>
        <button
          class="btn btn-primary"
          style="margin-left:auto"
          :disabled="!signCheck1 || !signCheck2 || !signForm.name || signBusy"
          @click="finalizeSignature"
        >
          <AegisIcon name="file-pen" :size="13" /> {{ signBusy ? 'Signing...' : 'Apply Signature' }}
        </button>
      </template>
    </AegisModal>


    <!-- ═══ MODAL: VIEW AGREEMENT ═══ -->
    <AegisModal
      :model-value="isOpen('viewAgreementModal').value"
      :title="activeDoc ? activeDoc.title : 'View Agreement'"
      size="xl"
      @update:model-value="v => !v && closeModal('viewAgreementModal')"
    >
      <template v-if="activeDoc">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
          <AegisBadge :label="activeDoc.badge_label" :variant="activeDoc.badge_variant" />
          <span style="font-size:12px;color:var(--text-3)">{{ activeDoc.reference }}</span>
          <span style="font-size:12px;color:var(--text-3)">{{ activeDoc.category_label }}</span>
        </div>
        <div class="info-card" style="margin-bottom:14px">
          <div class="info-card-title">Parties</div>
          <div class="info-card-row"><span class="info-card-key">Provider</span><span class="info-card-val">{{ providerName }}</span></div>
          <div v-if="activeDoc.counterparty" class="info-card-row">
            <span class="info-card-key">Counterparty</span>
            <span class="info-card-val">{{ activeDoc.counterparty.name }}</span>
          </div>
          <div v-if="activeDoc.effective_date" class="info-card-row">
            <span class="info-card-key">Effective</span><span class="info-card-val">{{ activeDoc.effective_date }}</span>
          </div>
          <div v-if="activeDoc.expiry_date" class="info-card-row">
            <span class="info-card-key">Expires</span><span class="info-card-val">{{ activeDoc.expiry_date }}</span>
          </div>
        </div>
        <div v-if="activeDoc.body" class="legal-doc">
          <div style="white-space:pre-wrap;font-size:13px;line-height:1.7">{{ activeDoc.body }}</div>
        </div>
        <div v-else class="legal-doc" style="text-align:center;color:var(--text-4);padding:32px">
          <AegisIcon name="file-text" :size="24" />
          <div style="margin-top:8px">Full document body will appear here when available.</div>
        </div>

        <!-- History timeline -->
        <div v-if="activeDoc.history && activeDoc.history.length" style="margin-top:16px">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;color:var(--text-4);margin-bottom:8px">Audit Trail</div>
          <div v-for="h in activeDoc.history" :key="h.title" style="display:flex;gap:12px;padding:8px 0;border-bottom:1px solid var(--surface-3)">
            <div :class="['hist-dot', 'dot-' + h.dot]"></div>
            <div style="flex:1">
              <div style="font-size:12px;font-weight:700;color:var(--text)">{{ h.title }}</div>
              <div style="font-size:12px;color:var(--text-3)">{{ h.desc }}</div>
            </div>
            <div style="font-size:11px;color:var(--text-4);white-space:nowrap">{{ h.date }}</div>
          </div>
        </div>
      </template>

      <template #footer>
        <button class="btn btn-outline" @click="closeModal('viewAgreementModal')">Close</button>
        <div style="display:flex;gap:8px;margin-left:auto">
          <button v-if="activeDoc?.can_amend" class="btn btn-outline" @click="openAmendModalDirect(activeDoc)">
            <AegisIcon name="edit" :size="13" /> Request Amendment
          </button>
          <button v-if="activeDoc?.primary_action === 'sign'" class="btn btn-primary" @click="closeModal('viewAgreementModal'); openSignModal(activeDoc)">
            <AegisIcon name="file-pen" :size="13" /> Sign Now
          </button>
          <button v-if="activeDoc?.primary_action === 'renew'" class="btn btn-primary" @click="closeModal('viewAgreementModal'); openRenewModal(activeDoc)">
            <AegisIcon name="refresh-cw" :size="13" /> Renew
          </button>
        </div>
      </template>
    </AegisModal>


    <!-- ═══ MODAL: MORE ACTIONS ═══ -->
    <AegisModal
      :model-value="isOpen('agreementActionsModal').value"
      :title="activeDoc ? ('Actions — ' + activeDoc.reference) : 'Actions'"
      size="sm"
      @update:model-value="v => !v && closeModal('agreementActionsModal')"
    >
      <div style="display:flex;flex-direction:column;gap:4px">
        <button class="list-item" @click="handleAction('view')">
          <div class="list-item-icon"><AegisIcon name="eye" :size="15" /></div>
          <div class="list-item-content">
            <div class="list-item-title">View Full Agreement</div>
            <div class="list-item-desc">Read, print, or download PDF</div>
          </div>
        </button>
        <button v-if="activeDoc?.can_amend" class="list-item" @click="handleAction('amendment')">
          <div class="list-item-icon"><AegisIcon name="edit" :size="15" /></div>
          <div class="list-item-content">
            <div class="list-item-title">Request Amendment</div>
            <div class="list-item-desc">Propose changes for mutual approval</div>
          </div>
        </button>
        <button v-if="activeDoc?.primary_action === 'renew' || activeDoc?.is_expiring" class="list-item" @click="handleAction('renew')">
          <div class="list-item-icon"><AegisIcon name="refresh-cw" :size="15" /></div>
          <div class="list-item-content">
            <div class="list-item-title">Renew Agreement</div>
            <div class="list-item-desc">Extend or update before expiry</div>
          </div>
        </button>
        <button v-if="activeDoc?.can_remind" class="list-item" @click="handleAction('remind')">
          <div class="list-item-icon"><AegisIcon name="bell" :size="15" /></div>
          <div class="list-item-content">
            <div class="list-item-title">Send Reminder</div>
            <div class="list-item-desc">Nudge counterparty to sign</div>
          </div>
        </button>
        <button class="list-item" @click="handleAction('download')">
          <div class="list-item-icon"><AegisIcon name="download" :size="15" /></div>
          <div class="list-item-content">
            <div class="list-item-title">Download PDF</div>
            <div class="list-item-desc">Save signed copy to your device</div>
          </div>
        </button>
        <button v-if="activeDoc?.can_terminate" class="list-item is-danger" @click="handleAction('terminate')">
          <div class="list-item-icon" style="background:var(--red-light);color:var(--red-dark)"><AegisIcon name="x-circle" :size="15" /></div>
          <div class="list-item-content">
            <div class="list-item-title" style="color:var(--red-dark)">Terminate Agreement</div>
            <div class="list-item-desc">End and archive this agreement</div>
          </div>
        </button>
      </div>
    </AegisModal>


    <!-- ═══ MODAL: RENEWAL ═══ -->
    <AegisModal
      :model-value="isOpen('renewalModal').value"
      :title="activeDoc ? ('Renew — ' + activeDoc.reference) : 'Renew Agreement'"
      size="md"
      @update:model-value="v => !v && closeModal('renewalModal')"
    >
      <div v-if="activeDoc" class="alert alert-info" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
        <div class="alert-content">A new agreement will be created with the updated dates. The current agreement will be archived after the new one is fully signed.</div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">New Effective Date <span class="required">*</span></label>
          <input
            type="date"
            class="form-input"
            v-model="renewForm.effectiveDate"
            :class="{ 'is-error': fieldError('renew_effective') }"
            @blur="v$.renew_effective.$touch()"
          />
          <div v-if="fieldError('renew_effective')" class="form-error">{{ fieldError('renew_effective') }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">New Expiration Date <span style="color:var(--text-4)">(optional)</span></label>
          <input type="date" class="form-input" v-model="renewForm.expiryDate" />
        </div>
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
          <input type="checkbox" v-model="renewForm.autoRenew" style="accent-color:var(--gold-dark)" />
          Enable auto-renewal for the new agreement
        </label>
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea class="form-textarea" rows="2" v-model="renewForm.notes" placeholder="Any notes for the renewal..."></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('renewalModal')">Cancel</button>
        <button class="btn btn-primary" style="margin-left:auto" :disabled="renewBusy" @click="submitRenew">
          <AegisIcon name="refresh-cw" :size="13" /> {{ renewBusy ? 'Processing...' : 'Initiate Renewal' }}
        </button>
      </template>
    </AegisModal>


    <!-- ═══ MODAL: AMENDMENT ═══ -->
    <AegisModal
      :model-value="isOpen('amendmentModal').value"
      :title="activeDoc ? ('Request Amendment — ' + activeDoc.reference) : 'Request Amendment'"
      size="xl"
      @update:model-value="v => !v && closeAmend()"
    >
      <div class="alert alert-info" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
        <div class="alert-content">Amendments require agreement from all original signatories. The other party will be notified and must accept or counter-propose changes before the amendment takes effect.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Amendment Type <span class="required">*</span></label>
        <select
          class="form-select"
          v-model="amendForm.type"
          :class="{ 'is-error': fieldError('amend_type') }"
          @blur="v$.amend_type.$touch()"
        >
          <option value="">— Select Type —</option>
          <option>Compensation Adjustment</option>
          <option>Scope of Services Change</option>
          <option>Term Extension</option>
          <option>PHI Access Level Change</option>
          <option>Termination Clause Update</option>
          <option>Communication Protocol Update</option>
          <option>Add New Clause</option>
          <option>Remove Clause</option>
          <option>Other</option>
        </select>
        <div v-if="fieldError('amend_type')" class="form-error">{{ fieldError('amend_type') }}</div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Current Language</label>
          <textarea class="form-textarea" rows="4" v-model="amendForm.currentLang" placeholder="Existing clause text..."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Proposed Change <span class="required">*</span></label>
          <textarea
            class="form-textarea"
            rows="4"
            v-model="amendForm.proposed"
            :class="{ 'is-error': fieldError('amend_proposed') }"
            @blur="v$.amend_proposed.$touch()"
            placeholder="Describe the proposed new language..."
          ></textarea>
          <div v-if="fieldError('amend_proposed')" class="form-error">{{ fieldError('amend_proposed') }}</div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Reason / Justification</label>
        <textarea class="form-textarea" rows="2" v-model="amendForm.reason" placeholder="Briefly explain why this amendment is needed..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Proposed Effective Date</label>
        <input type="date" class="form-input" v-model="amendForm.effectiveDate" />
        <div class="form-hint">If blank, takes effect upon mutual execution.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Supporting Files <span style="color:var(--text-4)">(optional)</span></label>
        <AegisDropzone accept=".pdf,.doc,.docx,.txt" :max-size="10" @files="amendFiles = $event" @rejected="toast.error('File rejected — check size or type.')" />
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeAmend">Cancel</button>
        <button class="btn btn-primary" style="margin-left:auto" :disabled="amendBusy" @click="submitAmend">
          <AegisIcon name="send" :size="13" /> {{ amendBusy ? 'Sending...' : 'Send Amendment Request' }}
        </button>
      </template>
    </AegisModal>


    <!-- ═══ MODAL: TERMINATION ═══ -->
    <AegisModal
      :model-value="isOpen('terminateModal').value"
      :title="activeDoc ? ('Terminate — ' + activeDoc.reference) : 'Terminate Agreement'"
      size="md"
      @update:model-value="v => !v && closeTerminate()"
    >
      <div class="alert alert-danger" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
        <div class="alert-content"><strong>This action terminates the agreement.</strong> The counterparty will be notified and all delegated permissions under this agreement will be revoked.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Reason for Termination <span class="required">*</span></label>
        <select
          class="form-select"
          v-model="terminateForm.reason"
          :class="{ 'is-error': fieldError('term_reason') }"
          @blur="v$.term_reason.$touch()"
        >
          <option value="">— Select Reason —</option>
          <option>Mutual agreement</option>
          <option>Non-renewal</option>
          <option>Breach of contract</option>
          <option>Resignation of steward</option>
          <option>Loss of licensure</option>
          <option>Fraud or misconduct</option>
          <option>Other</option>
        </select>
        <div v-if="fieldError('term_reason')" class="form-error">{{ fieldError('term_reason') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Effective Termination Date</label>
        <input type="date" class="form-input" v-model="terminateForm.date" />
        <div class="form-hint">Defaults to today if left blank. A 30-day notice period applies unless mutual.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea class="form-textarea" rows="2" v-model="terminateForm.notes" placeholder="Additional context..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Type "TERMINATE" to confirm <span class="required">*</span></label>
        <input
          type="text"
          class="form-input"
          v-model="terminateForm.confirm"
          :class="{ 'is-error': fieldError('term_confirm') }"
          @blur="v$.term_confirm.$touch()"
          placeholder="TERMINATE"
        />
        <div v-if="fieldError('term_confirm')" class="form-error">{{ fieldError('term_confirm') }}</div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeTerminate">Cancel</button>
        <button
          class="btn btn-danger"
          style="margin-left:auto"
          :disabled="terminateForm.confirm !== 'TERMINATE' || terminateBusy"
          @click="submitTerminate"
        >
          <AegisIcon name="x-circle" :size="13" /> {{ terminateBusy ? 'Terminating...' : 'Terminate Agreement' }}
        </button>
      </template>
    </AegisModal>


    <!-- ═══ MODAL: ADD SUPPORTING DOC ═══ -->
    <AegisModal
      :model-value="isOpen('addDocumentModal').value"
      title="Add Supporting Document"
      size="md"
      @update:model-value="v => !v && closeAddDoc()"
    >
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Document Name <span class="required">*</span></label>
          <input
            type="text"
            class="form-input"
            v-model="addDocForm.name"
            :class="{ 'is-error': fieldError('addDoc_name') }"
            @blur="v$.addDoc_name.$touch()"
            placeholder="e.g. Attorney Addendum — Jan 2025"
          />
          <div v-if="fieldError('addDoc_name')" class="form-error">{{ fieldError('addDoc_name') }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Document Type</label>
          <select class="form-select" v-model="addDocForm.type">
            <option>Supporting Document</option>
            <option>Attorney Letter</option>
            <option>Credential Proof</option>
            <option>Amendment (Uploaded)</option>
            <option>Other Reference</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Related To <span style="color:var(--text-4)">(optional)</span></label>
        <select class="form-select" v-model="addDocForm.relatedTo">
          <option value="">None</option>
          <option>Continuity Plan</option>
          <option>Continuity Steward Agreement</option>
          <option>Support Steward Agreement</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">File <span class="required">*</span></label>
        <AegisDropzone
          accept=".pdf,.doc,.docx,.txt"
          :max-size="10"
          @files="addDocFiles = $event"
          @rejected="toast.error('File rejected — check size or type.')"
        />
      </div>
      <div class="form-group">
        <label class="form-label">Notes <span style="color:var(--text-4)">(optional)</span></label>
        <textarea class="form-textarea" rows="2" v-model="addDocForm.notes" placeholder="Add context for your stewards..."></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeAddDoc">Cancel</button>
        <button class="btn btn-primary" style="margin-left:auto" :disabled="addDocBusy" @click="submitAddDoc">
          <AegisIcon name="upload" :size="13" /> {{ addDocBusy ? 'Uploading...' : 'Add Document' }}
        </button>
      </template>
    </AegisModal>


    <!-- ═══ MODAL: SAMPLE TEMPLATES ═══ -->
    <AegisModal
      :model-value="isOpen('templateModal').value"
      title="Sample Templates"
      size="lg"
      @update:model-value="v => !v && closeModal('templateModal')"
    >
      <div class="input-group" style="margin-bottom:14px;max-width:320px">
        <span class="input-group-icon"><AegisIcon name="search" :size="14" /></span>
        <input class="form-input" type="text" v-model="templateSearch" placeholder="Search templates..." />
      </div>
      <div style="display:flex;flex-direction:column;gap:8px">
        <div
          v-for="tpl in filteredTemplates"
          :key="tpl.title"
          class="list-item"
          @click="useTemplate(tpl)"
        >
          <div class="list-item-icon"><AegisIcon :name="tpl.icon" :size="15" /></div>
          <div class="list-item-content">
            <div class="list-item-title">{{ tpl.title }}</div>
            <div class="list-item-desc">{{ tpl.sub }}</div>
          </div>
          <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:var(--radius-full);background:var(--badge-bg-gold);color:var(--gold-dark)">{{ tpl.tag }}</span>
          <AegisIcon name="arrow-right" :size="14" style="color:var(--text-4)" />
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('templateModal')">Close</button>
      </template>
    </AegisModal>


    <!-- ═══ MODAL: EXPORT DOCUMENTS ═══ -->
    <AegisModal
      :model-value="isOpen('exportModal').value"
      title="Export Documents"
      size="md"
      @update:model-value="v => !v && closeModal('exportModal')"
    >
      <div class="form-group">
        <label class="form-label">Export Format</label>
        <select class="form-select" v-model="exportForm.format">
          <option>PDF Bundle (ZIP)</option>
          <option>CSV Summary</option>
          <option>Individual PDFs (ZIP)</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Scope</label>
        <select class="form-select" v-model="exportForm.scope">
          <option>All documents</option>
          <option>Active &amp; Signed only</option>
          <option>Pending Signature only</option>
          <option>Archived only</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Delivery</label>
        <div style="display:flex;flex-direction:column;gap:6px">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
            <input type="radio" v-model="exportForm.delivery" value="download" style="accent-color:var(--gold-dark)" /> Download now
          </label>
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
            <input type="radio" v-model="exportForm.delivery" value="email" style="accent-color:var(--gold-dark)" /> Email to me
          </label>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('exportModal')">Cancel</button>
        <button class="btn btn-primary" style="margin-left:auto" :disabled="exportBusy" @click="submitExport">
          <AegisIcon name="download" :size="13" /> {{ exportBusy ? 'Preparing...' : 'Export' }}
        </button>
      </template>
    </AegisModal>


    <!-- ═══ MODAL: DRAFT SAVE ═══ -->
    <AegisModal
      :model-value="isOpen('draftSaveModal').value"
      title="Save as Draft"
      size="sm"
      @update:model-value="v => !v && closeModal('draftSaveModal')"
    >
      <p style="font-size:13px;color:var(--text-3);margin-bottom:14px">Your progress will be saved — return anytime to continue.</p>
      <div class="form-group">
        <label class="form-label">Draft Name</label>
        <input
          type="text"
          class="form-input"
          v-model="draftForm.name"
          :placeholder="wiz.docType ? (wiz.docType + ' — Draft') : 'Draft Agreement'"
        />
      </div>
      <div class="form-group">
        <label class="form-label">Notes <span style="color:var(--text-4)">(optional)</span></label>
        <textarea class="form-textarea" rows="2" v-model="draftForm.notes" placeholder="Reminders about what still needs to be done..."></textarea>
      </div>
      <div class="alert alert-info" style="margin-bottom:0">
        <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
        <div class="alert-content">Drafts are private. The counterparty will not be notified until you send for signature.</div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('draftSaveModal')">Cancel</button>
        <button class="btn btn-primary" style="margin-left:auto" :disabled="draftBusy" @click="submitSaveDraft">
          <AegisIcon name="save" :size="14" /> {{ draftBusy ? 'Saving...' : 'Save Draft' }}
        </button>
      </template>
    </AegisModal>


    <!-- ═══ MODAL: SIGN SUCCESS ═══ -->
    <AegisModal
      :model-value="isOpen('signSuccessModal').value"
      title=""
      size="sm"
      @update:model-value="v => !v && closeModal('signSuccessModal')"
    >
      <div style="text-align:center;padding:24px 8px 8px">
        <div style="width:72px;height:72px;border-radius:var(--radius-full);background:var(--green-light);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:var(--green-dark)">
          <AegisIcon name="check" :size="32" />
        </div>
        <div style="font-family:var(--font-serif);font-size:22px;font-weight:700;color:var(--text);margin-bottom:6px">Signed!</div>
        <div style="font-size:13px;color:var(--text-3);margin-bottom:20px">Your signature was recorded. Awaiting countersignature from the counterparty.</div>
        <button class="btn btn-primary" @click="closeModal('signSuccessModal')">Done</button>
      </div>
    </AegisModal>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { usePage, router }   from '@inertiajs/vue3'
import AppLayout             from '@/layouts/AppLayout.vue'
import AegisDropzone         from '@/components/ui/AegisDropzone.vue'
import { useModal }          from '@/composables/useModal'
import { useToast }          from '@/composables/useToast'
import { useConfirm }        from '@/composables/useConfirm'
import PlanReviewAlert       from '@/components/PlanReviewAlert.vue'
import { useVuelidate }      from '@vuelidate/core'
import { required, helpers } from '@vuelidate/validators'

const props = defineProps({
  documents:          { type: Array,   default: () => [] },
  supportingDocs:     { type: Array,   default: () => [] },
  docStats:           { type: Object,  default: () => ({ total:0, pending_my_sig:0, awaiting_counter:0, expiring:0, active:0, archived:0 }) },
  menuBadges:         { type: Object,  default: () => ({}) },
  partyCounts:        { type: Object,  default: () => ({ all:0, pe:0, pd:0, de:0, tri:0 }) },
  stewards:           { type: Array,   default: () => [] },
  planStatus:         { type: String,  default: null },
  annualReviewDate:   { type: String,  default: null },
  hasDraftInProgress: { type: Boolean, default: false },
  draftPlanVersion:   { type: Number,  default: null },
})

const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()
const { confirmAction } = useConfirm()
const page = usePage()

const providerName = computed(() => page.props.auth?.user?.display_name || 'Provider')

// ── Navigation state ────────────────────────────────────────────────────────
// Read initial values from URL params so back-button / share links work
const _urlParams    = new URLSearchParams(window.location.search)
const activeTab     = ref(_urlParams.get('category') || 'all')
const categoryFilter = ref(_urlParams.get('party') || '')   // pe | pd | de | tri | ''
const searchQ       = ref('')
const typeFilter    = ref('')
const activeDocId   = ref(null)
const activeDoc     = computed(() => props.documents.find(d => d.id === activeDocId.value) ?? null)

// Pill definitions
const partyPills = [
  { key: '',    label: 'All' },
  { key: 'pe',  label: 'Provider & CS' },
  { key: 'pd',  label: 'Provider & SS' },
  { key: 'de',  label: 'SS & CS' },
  { key: 'tri', label: 'Tri-Party' },
]

// Live pill counts — computed from the CURRENT sidebar-filtered set (client-side)
// This gives instant counts without a round-trip; controller also sends partyCounts
// for the initial load (which may differ if sidebar is not 'all')
const livePillCounts = computed(() => {
  const base = sidebarFilteredDocs.value
  return {
    '':    base.length,
    pe:    base.filter(d => d.category === 'pe').length,
    pd:    base.filter(d => d.category === 'pd').length,
    de:    base.filter(d => d.category === 'de').length,
    tri:   base.filter(d => d.category === 'tri').length,
  }
})

// Sync URL params when sidebar tab or category filter changes
function syncUrl() {
  const params = new URLSearchParams(window.location.search)
  if (activeTab.value && activeTab.value !== 'all') {
    params.set('category', activeTab.value)
  } else {
    params.delete('category')
  }
  if (categoryFilter.value) {
    params.set('party', categoryFilter.value)
  } else {
    params.delete('party')
  }
  const newUrl = params.toString()
    ? `${window.location.pathname}?${params.toString()}`
    : window.location.pathname
  window.history.replaceState({}, '', newUrl)
}

watch(activeTab, () => {
  categoryFilter.value = ''   // reset party filter on sidebar change
  syncUrl()
})
watch(categoryFilter, syncUrl)

// Also sync sidebar clicks to Inertia so partyCounts refreshes from server
// (lightweight reload of just props — preserveScroll, preserveState)
function setActiveTab(tab) {
  activeTab.value = tab
  router.reload({ only: ['documents', 'partyCounts', 'menuBadges', 'docStats'], preserveScroll: true, preserveState: true })
}

function setCategoryFilter(key) {
  categoryFilter.value = key
}

// ── Busy flags ───────────────────────────────────────────────────────────────
const signBusy      = ref(false)
const sendBusy      = ref(false)
const renewBusy     = ref(false)
const terminateBusy = ref(false)
const addDocBusy    = ref(false)
const exportBusy    = ref(false)
const amendBusy     = ref(false)
const draftBusy     = ref(false)
const remindBusy    = ref(false)

// ── Signature state ──────────────────────────────────────────────────────────
const signCheck1 = ref(false)
const signCheck2 = ref(false)

// ── Tab computed helpers ─────────────────────────────────────────────────────
const showDocList  = computed(() => ['all','pending_sign','countersign','active','expiring','archived'].includes(activeTab.value))
const showFilterBar = computed(() => showDocList.value)

const tabTitle = computed(() => {
  const map = {
    all: 'All Documents', pending_sign: 'Pending My Signature',
    countersign: 'Awaiting Countersignature', active: 'Active & Signed',
    expiring: 'Expiring Soon', archived: 'Archived',
  }
  return map[activeTab.value] || 'Documents'
})

const tabSubtitle = computed(() => {
  const map = {
    all: 'All agreements and documents across every status',
    pending_sign: 'Agreements that require your signature before proceeding',
    countersign: 'Agreements you have signed, awaiting the counterparty',
    active: 'Fully executed agreements currently in effect',
    expiring: 'Agreements expiring within the next 30 days',
    archived: 'Terminated and archived agreements for audit purposes',
  }
  return map[activeTab.value] || ''
})

const emptyTitle = computed(() => {
  const map = {
    all: 'No Documents Yet', pending_sign: 'No Pending Signatures',
    countersign: 'None Awaiting Countersignature', active: 'No Active Agreements',
    expiring: 'Nothing Expiring Soon', archived: 'No Archived Documents',
  }
  return map[activeTab.value] || 'No Documents'
})

const emptySubtitle = computed(() => {
  const map = {
    all: 'Agreements you create or sign will appear here.',
    pending_sign: 'Agreements sent to you for signature will appear here.',
    countersign: 'Agreements sent out and awaiting the counterparty will appear here.',
    active: 'Fully executed agreements will appear here.',
    expiring: 'Agreements expiring within 30 days will appear here.',
    archived: 'Terminated and archived agreements will appear here for audit.',
  }
  return map[activeTab.value] || ''
})

// ── Document filtering — two-stage ───────────────────────────────────────────
// Stage 1: sidebar status filter (drives pill counts)
const sidebarFilteredDocs = computed(() => {
  let docs = props.documents
  if (activeTab.value === 'pending_sign') {
    docs = docs.filter(d => d.status === 'pending_sign')
  } else if (activeTab.value === 'countersign') {
    docs = docs.filter(d => ['countersign', 'countersign_pending'].includes(d.status))
  } else if (activeTab.value === 'active') {
    docs = docs.filter(d => ['active', 'fully_executed'].includes(d.status))
  } else if (activeTab.value === 'expiring') {
    docs = docs.filter(d => d.is_expiring)
  } else if (activeTab.value === 'archived') {
    docs = docs.filter(d => ['archived', 'terminated'].includes(d.status))
  }
  return docs
})

// Stage 2: apply party category + search + type on top of sidebar result
const filteredDocs = computed(() => {
  let docs = sidebarFilteredDocs.value

  // Party category pill filter
  if (categoryFilter.value) {
    docs = docs.filter(d => d.category === categoryFilter.value)
  }

  // Search
  if (searchQ.value) {
    const q = searchQ.value.toLowerCase()
    docs = docs.filter(d =>
      d.title?.toLowerCase().includes(q) ||
      d.reference?.toLowerCase().includes(q) ||
      d.people_label?.toLowerCase().includes(q)
    )
  }
  // Doc type dropdown
  if (typeFilter.value) {
    docs = docs.filter(d => d.doc_type === typeFilter.value)
  }

  return docs
})

const amendmentDocs = computed(() =>
  props.documents.filter(d => d.amends_document_id || d.doc_type === 'plan_amendment' || d.doc_type === 'fee_amendment')
)

// ── Wizard ───────────────────────────────────────────────────────────────────
const wizStep    = ref(1)
const stepLabels = [
  { short:'type',    line1:'Agreement', line2:'Type'    },
  { short:'parties', line1:'Parties &', line2:'Details' },
  { short:'clauses', line1:'Clauses &', line2:'Terms'   },
  { short:'review',  line1:'Review &',  line2:'Confirm' },
]
const stepSubs = [
  'Select agreement type & category',
  'Select parties and define the term',
  'Configure clauses and terms',
  'Review the complete draft',
]

const wiz = reactive({
  category:'', docType:'', reference:'', partyBSearch:'', partyB:null,
  effectiveDate:'', expirationDate:'', autoRenew:false, notes:'',
})

function openWizard()  { wizStep.value=1; Object.assign(wiz, { category:'', docType:'', reference:'', partyBSearch:'', partyB:null, effectiveDate:'', expirationDate:'', autoRenew:false, notes:'' }); openModal('newAgreementModal') }
function closeWizard() { closeModal('newAgreementModal') }

function wizardNext() {
  if (wizStep.value === 1) {
    v$.value.wiz_docType.$touch()
    if (v$.value.wiz_docType.$error) return
    if (!wiz.category) { toast.warning('Please select an agreement category.'); return }
  }
  if (wizStep.value === 2) {
    v$.value.wiz_effectiveDate.$touch()
    if (v$.value.wiz_effectiveDate.$error) return
  }
  wizStep.value++
}

const selectedCatTitle   = computed(() => agrCategories.find(c => c.value === wiz.category)?.title || '—')
const selectedPartyBName = computed(() => stewardOptions.value.find(p => p.id === wiz.partyB)?.name || '—')
const filteredPartyB     = computed(() => {
  const q = (wiz.partyBSearch || '').toLowerCase()
  return q ? stewardOptions.value.filter(p => p.name.toLowerCase().includes(q)) : stewardOptions.value
})
const stewardOptions = computed(() => props.stewards)

const agrCategories = [
  { value:'pe',  icon:'shield',     title:'Provider & Continuity Steward', sub:'MSA, SOW, NDA between you and a CS' },
  { value:'pd',  icon:'phone',      title:'Provider & Support Steward',    sub:'SLA, NDA between you and an SS' },
  { value:'de',  icon:'users',      title:'Team Agreements (Facilitated)', sub:'MOU between SS and CS — you are facilitator' },
  { value:'tri', icon:'circle',     title:'Tri-Party (All Three Roles)',   sub:'Single agreement binding Provider, CS, and SS' },
]

const clauses = reactive([
  { id:1, num:1, title:'Scope of Services & Delegation of Authority', open:true,  tagClass:'required',   tagLabel:'Required',   fields:[{ label:'Authorized activities', type:'textarea', value:'', placeholder:'e.g. CS is authorized to manage client referrals...' }, { label:'Explicit exclusions', type:'textarea', value:'', placeholder:'e.g. CS may not modify clinical treatment plans...' }] },
  { id:2, num:2, title:'Confidentiality & PHI Obligations (HIPAA)',   open:false, tagClass:'required',   tagLabel:'Required',   fields:[{ label:'PHI Access Level', type:'select', value:'Read-Only', options:['Read-Only','Read/Write','No PHI Access'] }, { label:'Confidentiality Duration', type:'select', value:'Duration of agreement only', options:['Duration of agreement only','2 years post-termination','5 years post-termination','Perpetual'] }] },
  { id:3, num:3, title:'Compensation & Fee Structure',                open:false, tagClass:'negotiable', tagLabel:'Negotiable', fields:[{ label:'Model', type:'select', value:'Fixed Monthly Retainer', options:['Fixed Monthly Retainer','Hourly Rate','Per-Task Fee'] }, { label:'Amount / Rate', type:'text', value:'', placeholder:'e.g. $2,500/mo' }, { label:'Payment Cycle', type:'select', value:'Monthly', options:['Monthly','Bi-Weekly','Weekly'] }] },
  { id:4, num:4, title:'Termination & Exit Provisions',               open:false, tagClass:'standard',   tagLabel:'Standard',   fields:[{ label:'Notice Period', type:'select', value:'30 days', options:['7 days','14 days','30 days','60 days'] }, { label:'Immediate Termination Grounds', type:'text', value:'HIPAA breach, fraud, gross negligence, loss of licensure', placeholder:'' }] },
  { id:5, num:5, title:'Liability, Indemnification & Insurance',      open:false, tagClass:'required',   tagLabel:'Required',   fields:[{ label:'Liability Cap', type:'select', value:'Capped at 3 months fees paid', options:['Capped at 3 months fees paid','Capped at total contract value','Capped at $1,000,000'] }, { label:'Insurance Requirement', type:'text', value:'Professional Liability min $1M / $3M aggregate', placeholder:'' }] },
])

// ── Form reactive objects ────────────────────────────────────────────────────
const signForm      = reactive({ name:'' })
const renewForm     = reactive({ effectiveDate:'', expiryDate:'', autoRenew:false, notes:'' })
const terminateForm = reactive({ reason:'', date:'', notes:'', confirm:'' })
const addDocForm    = reactive({ name:'', type:'Supporting Document', relatedTo:'', notes:'' })
const exportForm    = reactive({ format:'PDF Bundle (ZIP)', scope:'All documents', delivery:'download' })
const amendForm     = reactive({ type:'', currentLang:'', proposed:'', reason:'', effectiveDate:'' })
const draftForm     = reactive({ name:'', notes:'' })
const amendFiles    = ref([])
const addDocFiles   = ref([])
const templateSearch = ref('')

// ── Sample templates ─────────────────────────────────────────────────────────
const libraryTemplates = [
  { icon:'shield', title:'Provider & CS MSA',  sub:'Standard MSA — 8 clauses — HIPAA-ready',        tag:'Aegis', docType:'MSA', category:'pe' },
  { icon:'phone',  title:'Provider & SS SLA',  sub:'SLA with KPIs and response time commitments',   tag:'Aegis', docType:'SLA', category:'pd' },
  { icon:'lock',   title:'Mutual NDA',          sub:'Non-Disclosure — HIPAA BAA — 5yr post-term',    tag:'Aegis', docType:'NDA', category:'pe' },
  { icon:'users',  title:'SS & CS MOU',         sub:'Coordination Protocol — Provider-overseen',     tag:'Aegis', docType:'MOU', category:'de' },
  { icon:'users',  title:'Tri-Party MSA',       sub:'Provider + CS + SS — Roles, scope, disputes',  tag:'Aegis', docType:'MSA', category:'tri' },
  { icon:'pencil', title:'Statement of Work',   sub:'Project-specific scope for delegated activities',tag:'Custom', docType:'SOW', category:'pe' },
]

const filteredTemplates = computed(() => {
  if (!templateSearch.value) return libraryTemplates
  const q = templateSearch.value.toLowerCase()
  return libraryTemplates.filter(t =>
    t.title.toLowerCase().includes(q) || t.sub.toLowerCase().includes(q)
  )
})

function useTemplate(tpl) {
  closeModal('templateModal')
  setTimeout(() => {
    wiz.docType   = tpl.docType || ''
    wiz.category  = tpl.category || ''
    wizStep.value = 1
    openModal('newAgreementModal')
  }, 50)
}

// ── Vuelidate ────────────────────────────────────────────────────────────────
const rules = computed(() => ({
  wiz_docType:       { required: helpers.withMessage('Document type is required.', required) },
  wiz_effectiveDate: { required: helpers.withMessage('Effective date is required.', required) },
  sign_name:         { required: helpers.withMessage('Please type your full name.', required) },
  renew_effective:   { required: helpers.withMessage('Effective date is required.', required) },
  term_reason:       { required: helpers.withMessage('Please select a reason.', required) },
  term_confirm:      { required: helpers.withMessage('Type TERMINATE to confirm.', required) },
  amend_type:        { required: helpers.withMessage('Amendment type is required.', required) },
  amend_proposed:    { required: helpers.withMessage('Proposed change is required.', required) },
  addDoc_name:       { required: helpers.withMessage('Document name is required.', required) },
}))

const vModel = reactive({
  wiz_docType:       computed({ get: () => wiz.docType,            set: v => { wiz.docType = v } }),
  wiz_effectiveDate: computed({ get: () => wiz.effectiveDate,      set: v => { wiz.effectiveDate = v } }),
  sign_name:         computed({ get: () => signForm.name,          set: v => { signForm.name = v } }),
  renew_effective:   computed({ get: () => renewForm.effectiveDate, set: v => { renewForm.effectiveDate = v } }),
  term_reason:       computed({ get: () => terminateForm.reason,   set: v => { terminateForm.reason = v } }),
  term_confirm:      computed({ get: () => terminateForm.confirm,  set: v => { terminateForm.confirm = v } }),
  amend_type:        computed({ get: () => amendForm.type,         set: v => { amendForm.type = v } }),
  amend_proposed:    computed({ get: () => amendForm.proposed,     set: v => { amendForm.proposed = v } }),
  addDoc_name:       computed({ get: () => addDocForm.name,        set: v => { addDocForm.name = v } }),
})

const v$ = useVuelidate(rules, vModel, { $scope: false })
function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message
  return null
}

// ── Modal openers ────────────────────────────────────────────────────────────
function openViewModal(doc)    { activeDocId.value = doc.id; openModal('viewAgreementModal') }
function openActionsModal(doc) { activeDocId.value = doc.id; openModal('agreementActionsModal') }
function openSignModal(doc)    { activeDocId.value = doc.id; signForm.name = ''; signCheck1.value = false; signCheck2.value = false; v$.value.$reset(); openModal('signatureModal') }
function openRenewModal(doc)   { activeDocId.value = doc.id; renewForm.effectiveDate = ''; renewForm.expiryDate = ''; openModal('renewalModal') }
function openAmendModalDirect(doc) { activeDocId.value = doc.id; closeModal('viewAgreementModal'); setTimeout(() => openModal('amendmentModal'), 50) }

function closeSignModal()  { closeModal('signatureModal'); signForm.name = ''; v$.value.$reset() }
function closeTerminate()  { closeModal('terminateModal'); Object.assign(terminateForm, { reason:'', date:'', notes:'', confirm:'' }); v$.value.$reset() }
function closeAmend()      { closeModal('amendmentModal'); Object.assign(amendForm, { type:'', currentLang:'', proposed:'', reason:'', effectiveDate:'' }); amendFiles.value = []; v$.value.$reset() }
function closeAddDoc()     { closeModal('addDocumentModal'); Object.assign(addDocForm, { name:'', type:'Supporting Document', relatedTo:'', notes:'' }); addDocFiles.value = []; v$.value.$reset() }

function handleAction(action) {
  closeModal('agreementActionsModal')
  setTimeout(() => {
    const doc = activeDoc.value
    if (action === 'download') { toast.info('PDF download coming soon.'); return }
    if (action === 'view')      openViewModal(doc)
    if (action === 'amendment') openModal('amendmentModal')
    if (action === 'renew')     openRenewModal(doc)
    if (action === 'remind')    sendReminder(doc)
    if (action === 'terminate') openModal('terminateModal')
  }, 50)
}

function docCardClass(doc) {
  const m = {
    active:'is-active', fully_executed:'is-active',
    countersign_pending:'is-await', countersign:'is-await',
    pending_sign:'is-await', expiring:'is-expiring',
    expired:'is-expired', terminated:'is-terminated',
    archived:'is-draft', draft:'is-draft',
  }
  return m[doc.status] || 'is-active'
}

// ── Submit actions ───────────────────────────────────────────────────────────

function finalizeSignature() {
  v$.value.sign_name.$touch()
  if (v$.value.sign_name.$error) return
  if (!signCheck1.value || !signCheck2.value) {
    toast.warning('Please confirm both attestation checkboxes.')
    return
  }
  signBusy.value = true
  router.post(route('provider.documents.sign', { document: activeDocId.value }), {}, {
    preserveScroll: true,
    onSuccess: () => { closeSignModal(); openModal('signSuccessModal') },
    onError:   () => toast.error('Could not save signature.'),
    onFinish:  () => { signBusy.value = false },
  })
}

function sendForSignature() {
  sendBusy.value = true
  router.post(route('provider.documents.request'), {
    category:        wiz.category,
    doc_type:        wiz.docType,
    reference:       wiz.reference,
    party_b_id:      wiz.partyB,
    effective_date:  wiz.effectiveDate,
    expiry_date:     wiz.expirationDate,
    auto_renew:      wiz.autoRenew ? 'yes' : 'no',
    notes:           wiz.notes,
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Agreement sent for signature.'); closeWizard() },
    onError:   () => toast.error('Could not send agreement.'),
    onFinish:  () => { sendBusy.value = false },
  })
}

function saveDraftFromReview() {
  draftBusy.value = true
  router.post(route('provider.documents.request'), {
    category: wiz.category, doc_type: wiz.docType,
    reference: wiz.reference || draftForm.name,
    notes: wiz.notes, is_draft: true,
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.info('Draft saved.'); closeWizard() },
    onError:   () => toast.error('Could not save draft.'),
    onFinish:  () => { draftBusy.value = false },
  })
}

async function submitRenew() {
  v$.value.renew_effective.$touch()
  if (v$.value.renew_effective.$error) return
  renewBusy.value = true
  router.post(route('provider.documents.renew', { document: activeDocId.value }), {
    effective_date: renewForm.effectiveDate,
    expiry_date:    renewForm.expiryDate,
    auto_renew:     renewForm.autoRenew,
    notes:          renewForm.notes,
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Renewal initiated. New agreement sent for signature.'); closeModal('renewalModal') },
    onError:   () => toast.error('Could not initiate renewal.'),
    onFinish:  () => { renewBusy.value = false },
  })
}

async function submitTerminate() {
  v$.value.term_reason.$touch()
  v$.value.term_confirm.$touch()
  if (v$.value.term_reason.$error || v$.value.term_confirm.$error) return
  if (terminateForm.confirm !== 'TERMINATE') { toast.error('Please type TERMINATE exactly to confirm.'); return }
  terminateBusy.value = true
  router.post(route('provider.documents.terminate', { document: activeDocId.value }), {
    reason:    terminateForm.reason,
    term_date: terminateForm.date,
    notes:     terminateForm.notes,
    confirm:   terminateForm.confirm,
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.warning('Agreement terminated.'); closeTerminate() },
    onError:   () => toast.error('Could not terminate agreement.'),
    onFinish:  () => { terminateBusy.value = false },
  })
}

async function submitAmend() {
  v$.value.amend_type.$touch()
  v$.value.amend_proposed.$touch()
  if (v$.value.amend_type.$error || v$.value.amend_proposed.$error) return
  amendBusy.value = true
  router.post(route('provider.documents.request'), {
    parent_id:      activeDocId.value,
    type:           amendForm.type,
    proposed:       amendForm.proposed,
    reason:         amendForm.reason,
    effective_date: amendForm.effectiveDate,
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Amendment request sent.'); closeAmend() },
    onError:   () => toast.error('Could not send amendment request.'),
    onFinish:  () => { amendBusy.value = false },
  })
}

async function submitAddDoc() {
  v$.value.addDoc_name.$touch()
  if (v$.value.addDoc_name.$error) return
  addDocBusy.value = true
  router.post(route('provider.documents.upload'), {
    name:       addDocForm.name,
    type:       addDocForm.type,
    related_to: addDocForm.relatedTo,
    notes:      addDocForm.notes,
  }, {
    forceFormData: true, preserveScroll: true,
    onSuccess: () => { toast.success('Document added.'); closeAddDoc() },
    onError:   () => toast.error('Could not upload document.'),
    onFinish:  () => { addDocBusy.value = false },
  })
}

function sendReminder(doc) {
  remindBusy.value = true
  router.post(route('provider.documents.remind', { document: doc.id }), {}, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Reminder sent.'); remindBusy.value = false },
    onError:   () => { toast.error('Could not send reminder.'); remindBusy.value = false },
  })
}

function deleteDraft(doc) {
  router.delete(route('provider.documents.destroy', { document: doc.id }), {
    preserveScroll: true,
    onSuccess: () => toast.info('Draft deleted.'),
    onError:   () => toast.error('Could not delete draft.'),
  })
}

function deleteSupporting(doc) {
  router.delete(route('provider.documents.destroy', { document: doc.id }), {
    preserveScroll: true,
    onSuccess: () => toast.info('Document removed.'),
    onError:   () => toast.error('Could not remove document.'),
  })
}

function submitExport() {
  exportBusy.value = true
  setTimeout(() => {
    exportBusy.value = false
    closeModal('exportModal')
    toast.info('Export started — check your downloads or email.')
  }, 1200)
}

function submitSaveDraft() {
  draftBusy.value = true
  router.post(route('provider.documents.request'), {
    category: wiz.category,
    doc_type:  wiz.docType,
    reference: wiz.reference || draftForm.name,
    notes:     draftForm.notes,
    is_draft:  true,
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.info('Draft saved.'); closeModal('draftSaveModal'); closeWizard() },
    onError:   () => toast.error('Could not save draft.'),
    onFinish:  () => { draftBusy.value = false },
  })
}
</script>

<style scoped>
/* Layout */
.doc-layout  { display: flex; align-items: flex-start; gap: 22px; }
.doc-content { flex: 1; min-width: 0; }

/* Section header */
.doc-section-head  { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--border); flex-wrap:wrap; gap:12px; }
.doc-section-title { font-family:var(--font-serif); font-size:16px; font-weight:700; color:var(--text); }
.doc-section-sub   { font-size:12px; color:var(--text-3); margin-top:2px; }

/* Doc file icon */
.doc-file-icon { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }

/* Vault badge */
.vault-badge { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:700; color:var(--blue-dark); background:var(--blue-light); border-radius:var(--radius-full); padding:1px 6px; margin-top:2px; }

/* Amendment badge on card */
.ag-amend-badge { display:inline-block; font-size:10px; font-weight:700; background:var(--blue-light); color:var(--blue-dark); border-radius:var(--radius-full); padding:1px 6px; margin-left:6px; }

/* Audit trail dots */
.hist-dot { width:10px; height:10px; border-radius:var(--radius-full); margin-top:3px; flex-shrink:0; }
.dot-gold   { background:var(--gold-dark); }
.dot-green  { background:var(--green-dark); }
.dot-orange { background:var(--orange-dark, #c2500a); }
.dot-red    { background:var(--red-dark); }
.dot-gray   { background:var(--text-4); }

/* Signature preview */
.sig-preview { border:1px solid var(--border); border-radius:var(--radius-sm); padding:10px 16px; background:var(--surface-2); margin-top:8px; }
.sig-name-display { font-family:var(--font-serif); font-size:20px; font-style:italic; color:var(--text); letter-spacing:0.4px; }

/* Info card */
.info-card       { border:1px solid var(--border); border-radius:var(--radius); padding:14px 16px; font-size:13px; background:var(--surface); }
.info-card-title { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; color:var(--text-4); margin-bottom:8px; }
.info-card-row   { display:flex; justify-content:space-between; align-items:center; padding:5px 0; border-bottom:1px solid var(--surface-3); font-size:12px; }
.info-card-row:last-child { border-bottom:none; }
.info-card-key   { color:var(--text-3); font-weight:600; }
.info-card-val   { font-weight:700; color:var(--text); display:inline-flex; align-items:center; gap:4px; }

/* Legal doc */
.legal-doc { border:1px solid var(--border); border-radius:var(--radius); background:var(--surface-2); padding:20px 22px; font-size:13px; color:var(--text-2); line-height:1.7; max-height:320px; overflow-y:auto; }

/* Wizard stepper */
.workflow-stepper { display:flex; align-items:flex-start; gap:0; overflow-x:auto; scrollbar-width:none; padding:4px 0; }
.workflow-stepper::-webkit-scrollbar { display:none; }
.wf-step { display:flex; flex-direction:column; align-items:center; flex:1; min-width:96px; position:relative; }
.wf-step:not(:last-child)::after { content:''; position:absolute; top:16px; left:50%; right:-50%; height:2px; background:var(--border); z-index:0; }
.wf-step.done:not(:last-child)::after,.wf-step.current:not(:last-child)::after { background:var(--gold-dark); }
.wf-node { width:32px; height:32px; border-radius:var(--radius-full); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; position:relative; z-index:1; border:1px solid var(--border); background:var(--surface); color:var(--text-4); transition:all var(--transition); }
.wf-step.done .wf-node,.wf-step.current .wf-node { background:var(--gold-dark); border-color:var(--gold-dark); color:var(--text-inverted); }
.wf-label { font-size:11px; font-weight:700; color:var(--text-4); margin-top:8px; text-align:center; line-height:1.3; }
.wf-step.done .wf-label,.wf-step.current .wf-label { color:var(--gold-dark); }

/* Party grid in wizard */
.party-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
.party-col  { border:1px solid var(--border); border-radius:var(--radius-lg); padding:14px; position:relative; overflow:hidden; background:var(--surface); }
.party-col::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.party-col.provider::before { background:var(--gold-dark); }
.party-col.cs::before       { background:var(--purple-dark, #7c3aed); }
.party-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:6px; color:var(--text-4); }
.party-name  { font-size:14px; font-weight:700; color:var(--text); }

.party-search-result { border:1px solid var(--border); border-radius:var(--radius-sm); padding:11px 14px; margin-bottom:6px; cursor:pointer; transition:background var(--transition),border-color var(--transition); display:flex; align-items:center; gap:12px; background:var(--surface); font-size:13px; }
.party-search-result:hover    { background:var(--surface-2); border-color:var(--gold-dark); }
.party-search-result.selected { background:var(--badge-bg-gold); border-color:var(--gold-dark); }
.party-search-result.selected .party-name-sm { color:var(--gold-dark); }
.party-avatar-sm { width:36px; height:36px; border-radius:var(--radius-full); background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:13px; flex-shrink:0; }
.party-info-sm   { flex:1; min-width:0; }
.party-name-sm   { font-size:13px; font-weight:700; color:var(--text); }
.party-meta-sm   { font-size:12px; color:var(--text-3); }

/* Clause sections */
.clause-section       { border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; margin-bottom:10px; background:var(--surface); }
.clause-header        { display:flex; align-items:center; justify-content:space-between; padding:11px 14px; background:var(--surface-2); cursor:pointer; transition:background var(--transition); }
.clause-header:hover  { background:var(--surface-3); }
.clause-header-left   { display:flex; align-items:center; gap:10px; }
.clause-num           { width:24px; height:24px; border-radius:var(--radius-sm); background:var(--primary); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0; }
.clause-title         { font-size:13px; font-weight:700; color:var(--text); }
.clause-body          { padding:12px 14px; border-top:1px solid var(--border); display:none; }
.clause-body.open     { display:block; }
.clause-tag           { display:inline-block; font-size:10px; font-weight:700; letter-spacing:0.4px; text-transform:uppercase; padding:2px 8px; border-radius:var(--radius-full); }
.clause-tag.required   { background:var(--red-light);   color:var(--red-dark); }
.clause-tag.negotiable { background:var(--blue-light);  color:var(--blue-dark); }
.clause-tag.standard   { background:var(--green-light); color:var(--green-dark); }

/* Agreement category cards */
.agr-cat-grid  { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:4px; }
.agr-cat-card  { display:flex; align-items:flex-start; gap:10px; padding:12px 14px; border:1px solid var(--border); border-radius:var(--radius-sm); cursor:pointer; transition:border-color var(--transition),background var(--transition); }
.agr-cat-card:hover    { border-color:var(--gold-dark); }
.agr-cat-card.selected { border-color:var(--gold-dark); background:var(--badge-bg-gold); }
.agr-cat-avatar { width:36px; height:36px; border-radius:var(--radius-full); background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.agr-cat-title  { font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px; }
.agr-cat-sub    { font-size:11px; color:var(--text-4); line-height:1.4; }

/* Signature checkboxes */
.sig-check-row   { display:flex; align-items:center; gap:10px; cursor:pointer; border-bottom:1px solid var(--border); }
.sig-check-label { font-size:13px; color:var(--text-2); }

/* List items in actions modal */
.list-item       { border:1px solid var(--border); border-radius:var(--radius-sm); padding:11px 14px; margin-bottom:6px; cursor:pointer; transition:background var(--transition),border-color var(--transition); display:flex; align-items:center; gap:12px; background:var(--surface); font-size:13px; width:100%; text-align:left; }
.list-item:hover { background:var(--surface-2); border-color:var(--gold-dark); }
.list-item.is-danger:hover { border-color:var(--red-dark); }
.list-item-icon    { width:32px; height:32px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.list-item-content { flex:1; min-width:0; }
.list-item-title   { font-size:13px; font-weight:700; color:var(--text); }
.list-item-desc    { font-size:12px; color:var(--text-3); margin-top:1px; }

/* Party category pill row */
.doc-party-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 14px;
  align-items: center;
}

@media (max-width: 860px) {
  .doc-layout { flex-direction: column; }
  .page-sidebar { width: 100% !important; position: static; }
  .page-sidebar-group { display: flex; flex-wrap: wrap; padding: 4px 6px; }
  .page-sidebar-label { display: none; }
  .page-sidebar-item  { width: auto; flex: 0 0 auto; border-left: none; border-radius: var(--radius-sm); padding: 6px 12px; font-size: 12px; }
  .page-sidebar-item.active::before { display: none; }
  .page-sidebar-icon  { display: none; }
  .party-grid, .agr-cat-grid { grid-template-columns: 1fr; }
}
</style>
