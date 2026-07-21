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
        <a :href="route('provider.activity', { module: 'documents' })" class="btn-hero-ghost is-on-light" data-tooltip="Module activity">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
        <button class="btn-hero-ghost is-on-light" @click="openModal('templateModal')">
          <AegisIcon name="book-open" :size="14" /> Browse Library
        </button>
        <button class="btn-hero-solid is-on-light" @click="openWizard">
          <AegisIcon name="plus" :size="14" /> New Agreement
        </button>
      </template>
    </AegisHeroBanner>

    <!-- STAT CHIPS (sibling of hero, never inside) -->
    <div class="stat-chips-row">
      <AegisStatChip icon="file-text" :value="docStats.total"    label="Total Documents" />
      <AegisStatChip icon="check"     :value="docStats.active"   label="Active &amp; Signed" />
      <AegisStatChip icon="clock"     :value="docStats.pending"  label="Pending Signature" />
      <AegisStatChip icon="calendar"  :value="docStats.expiring" label="Expiring in 30 Days" />
    </div>

    <!-- ANNUAL REVIEW ALERT -->
    <PlanReviewAlert
      :plan-status="planStatus"
      :annual-review-date="annualReviewDate"
      :has-draft-in-progress="hasDraftInProgress"
      :draft-plan-version="draftPlanVersion"
      context="documents"
    />

    <!-- ACTION REQUIRED ALERT -->
    <div v-if="docStats.pending > 0" class="alert alert-gold" style="margin-bottom:18px">
      <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
      <div class="alert-content">
        <div class="alert-title">{{ docStats.pending }} Agreement{{ docStats.pending !== 1 ? 's' : '' }} Require Your Action</div>
        <div>Review agreements below that are pending your signature or need renewal.</div>
        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
          <button class="btn btn-primary" @click="activeTab = 'expiring'">
            <AegisIcon name="signature" :size="13" /> View Pending
          </button>
        </div>
      </div>
    </div>

    <!-- TABS -->
    <div class="tabs-segmented" style="margin-bottom:14px">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        class="tab-pill"
        :class="{ active: activeTab === tab.id }"
        @click="activeTab = tab.id"
      >
        {{ tab.label }}
        <span v-if="tab.count != null" class="badge-pill">{{ tab.count }}</span>
      </button>
    </div>

    <!-- FILTER BAR -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;align-items:center">
      <div class="input-group" style="min-width:220px;max-width:320px;flex:1">
        <span class="input-group-icon"><AegisIcon name="search" :size="14" /></span>
        <input class="form-input form-input-sm" type="text" v-model="searchQ" placeholder="Search agreements..." />
      </div>
      <select class="form-select form-select-sm" v-model="statusFilter" style="max-width:200px">
        <option value="">All Statuses</option>
        <option value="active">Active</option>
        <option value="pending_sign">Pending Signature</option>
        <option value="countersign_pending">Awaiting Countersignature</option>
        <option value="draft">Draft</option>
        <option value="expired">Expired</option>
        <option value="terminated">Terminated</option>
      </select>
      <select class="form-select form-select-sm" v-model="typeFilter" style="max-width:220px">
        <option value="">All Types</option>
        <option value="MSA">MSA</option>
        <option value="NDA">NDA</option>
        <option value="SOW">SOW</option>
        <option value="MOU">MOU</option>
        <option value="SLA">SLA</option>
        <option value="BAA">BAA</option>
      </select>
      <button class="btn btn-outline" style="margin-left:auto" @click="openModal('exportModal')">
        <AegisIcon name="download" :size="13" /> Export
      </button>
    </div>

    <!-- CONTINUITY PLAN SECTION -->
    <div>
      <div class="doc-section-head">
        <div>
          <div class="doc-section-title">Continuity Plan</div>
          <div class="doc-section-sub">Your signed succession agreement and the 7-incident-type configuration grid</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <a :href="route('provider.plan.index')" class="btn btn-outline">Open Plan Builder</a>
          <button class="btn btn-outline" @click="openModal('addDocumentModal')">
            <AegisIcon name="upload" :size="13" /> Add Document
          </button>
          <button class="btn btn-primary" @click="openWizard">
            <AegisIcon name="plus" :size="13" /> Create New Agreement
          </button>
        </div>
      </div>

      <div class="ag-list">
        <AegisEmptyState
          v-if="filteredDocs.length === 0"
          icon="file-text"
          title="No Documents Yet"
          subtitle="Agreements you create or sign will appear here."
        />

        <article
          v-for="doc in filteredDocs"
          :key="doc.id"
          class="card ag-card"
          :class="docCardClass(doc)"
        >
          <div class="ag-row">
            <div class="ag-icon" :class="{ 'is-muted': doc.status === 'draft' || doc.status === 'terminated' }">
              <AegisIcon name="file-text" :size="19" />
            </div>
            <div class="ag-main">
              <div class="ag-eyebrow" :style="(doc.status === 'draft' || doc.status === 'terminated') ? 'color:var(--text-4)' : ''">
                {{ doc.doc_type_label }}
              </div>
              <div class="ag-title">{{ doc.title }}</div>
              <div class="ag-ref">{{ doc.category_label }} &middot; {{ doc.reference }}</div>
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
                <template v-if="doc.primary_action === 'sign'">
                  <button class="btn btn-primary" :disabled="signBusy" @click="openSignModal(doc)">
                    <AegisIcon name="signature" :size="13" /> {{ signBusy ? 'Signing...' : 'Sign' }}
                  </button>
                  <button class="btn-icon" data-tooltip="View" @click="openViewModal(doc)"><AegisIcon name="eye" :size="14" /></button>
                  <button class="btn-icon" data-tooltip="More" @click="openActionsModal(doc)"><AegisIcon name="more-horizontal" :size="14" /></button>
                </template>
                <template v-else-if="doc.primary_action === 'renew'">
                  <button class="btn btn-primary" @click="openRenewModal(doc)">
                    <AegisIcon name="refresh-cw" :size="13" /> Renew
                  </button>
                  <button class="btn-icon" data-tooltip="View" @click="openViewModal(doc)"><AegisIcon name="eye" :size="14" /></button>
                  <button class="btn-icon" data-tooltip="More" @click="openActionsModal(doc)"><AegisIcon name="more-horizontal" :size="14" /></button>
                </template>
                <template v-else-if="doc.primary_action === 'edit'">
                  <button class="btn btn-outline" @click="openSendForSigModal(doc)">
                    <AegisIcon name="pencil" :size="13" /> Edit
                  </button>
                  <button class="btn-icon" data-tooltip="Send for signature" @click="openSendForSigModal(doc)"><AegisIcon name="send" :size="14" /></button>
                  <button
                    class="btn-icon btn-icon-danger"
                    data-tooltip="Delete draft"
                    @click="confirmAction({ title:'Delete Draft', message:'Delete this draft? This action cannot be undone.', confirmLabel:'Delete', destructive:true }, () => deleteDraft(doc))"
                  ><AegisIcon name="trash" :size="14" /></button>
                </template>
                <template v-else>
                  <button class="btn-icon" data-tooltip="View" @click="openViewModal(doc)"><AegisIcon name="eye" :size="14" /></button>
                  <button class="btn-icon" data-tooltip="Download PDF" @click="toast.info('Downloading PDF...')"><AegisIcon name="download" :size="14" /></button>
                  <button class="btn-icon" data-tooltip="More" @click="openActionsModal(doc)"><AegisIcon name="more-horizontal" :size="14" /></button>
                </template>
              </div>
            </div>
          </div>
        </article>
      </div>
    </div>

    <!-- SUPPORTING DOCUMENTS -->
    <div style="margin-top:28px">
      <div class="doc-section-head">
        <div>
          <div class="doc-section-title">Supporting Documents</div>
          <div class="doc-section-sub">Amendments and other supporting documents shared between Practitioners, Continuity Stewards, and Support Stewards</div>
        </div>
        <button class="btn btn-outline" @click="openModal('addDocumentModal')">
          <AegisIcon name="upload" :size="13" /> Add Document
        </button>
      </div>
      <div class="list-group" style="margin-bottom:14px">
        <AegisEmptyState
          v-if="supportingDocs.length === 0"
          icon="file-text"
          title="No Supporting Documents"
          subtitle="Upload amendments, references, or other documents to share with your stewards."
        />
        <div v-for="sdoc in supportingDocs" :key="sdoc.id" class="list-group-item" style="gap:12px">
          <span class="doc-file-icon"><AegisIcon name="file-text" :size="16" /></span>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:700;color:var(--text)">{{ sdoc.title }}</div>
            <div style="font-size:12px;color:var(--text-3)">{{ sdoc.meta }}</div>
          </div>
          <AegisBadge :label="sdoc.badge_label" :variant="sdoc.badge_variant" />
          <button class="btn-icon" data-tooltip="View" @click="toast.info('Opening document...')"><AegisIcon name="eye" :size="14" /></button>
          <button class="btn-icon" data-tooltip="Download" @click="toast.info('Downloading...')"><AegisIcon name="download" :size="14" /></button>
        </div>
      </div>
      <div class="upload-zone" style="margin-bottom:24px" @click="openModal('addDocumentModal')">
        <div class="upload-zone-icon"><AegisIcon name="upload" :size="22" /></div>
        <div class="upload-zone-title">Add a Supporting Document</div>
        <div class="upload-zone-sub">Upload an amendment, reference, or other document to share with your stewards</div>
      </div>
    </div>

    <!-- ═══ MODAL 1: NEW AGREEMENT WIZARD ═══ -->
    <AegisModal
      :model-value="isOpen('newAgreementModal').value"
      title="Create New Agreement"
      size="xl"
      @update:model-value="v => !v && closeWizard()"
    >
      <div style="margin:-8px -24px 20px;padding:14px 24px 16px;border-bottom:1px solid var(--border);background:var(--surface-2)">
        <div style="font-size:12px;color:var(--text-3);font-weight:600;margin-bottom:12px">Step {{ wizStep }} of 5 — {{ stepSubs[wizStep - 1] }}</div>
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

      <!-- Step 1 -->
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
            <div><div class="agr-cat-title">{{ cat.title }}</div><div class="agr-cat-sub">{{ cat.sub }}</div></div>
          </div>
        </div>
        <div class="form-row" style="margin-top:16px">
          <div class="form-group">
            <label class="form-label">Document Type <span class="required">*</span></label>
            <select class="form-select" v-model="wiz.docType" :class="{ 'is-error': fieldError('wiz_docType') }" @blur="v$.wiz_docType.$touch()">
              <option value="">Select Document Type</option>
              <option value="MSA">MSA - Master Service Agreement</option>
              <option value="NDA">NDA - Non-Disclosure Agreement</option>
              <option value="SOW">SOW - Statement of Work</option>
              <option value="MOU">MOU - Memorandum of Understanding</option>
              <option value="SLA">SLA - Service Level Agreement</option>
              <option value="REF">Referral Agreement</option>
              <option value="ICA">Independent Contractor Agreement</option>
              <option value="BAA">BAA - Business Associate Agreement (HIPAA)</option>
            </select>
            <div v-if="fieldError('wiz_docType')" class="form-error">{{ fieldError('wiz_docType') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Reference / Title <span style="color:var(--text-4)">(optional)</span></label>
            <input type="text" class="form-input" v-model="wiz.reference" placeholder="Auto-generated if blank (e.g. MSA-2025-015)" />
          </div>
        </div>
        <div v-if="wiz.docType && wiz.docType !== 'BAA'" class="alert alert-info" style="margin-top:12px">
          <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
          <div class="alert-content">An Aegis-certified template will be pre-loaded for this document type.</div>
        </div>
        <div v-if="wiz.docType === 'BAA'" class="alert alert-warning" style="margin-top:12px">
          <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
          <div class="alert-content">A Business Associate Agreement (BAA) is <strong>required by HIPAA</strong> whenever PHI is shared with a third party.</div>
        </div>
      </div>

      <!-- Step 2 -->
      <div v-show="wizStep === 2">
        <div class="alert alert-gold" style="margin-bottom:16px">
          <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
          <div class="alert-content">Party A is always <strong>you (the Provider)</strong>. Select the other party or parties below.</div>
        </div>
        <div class="party-grid" style="margin-bottom:20px">
          <div>
            <div class="modal-section-label" style="display:flex;align-items:center;gap:6px;color:var(--gold-dark)">
              <AegisIcon name="user" :size="13" /><span>Party A - Provider (You)</span>
            </div>
            <div class="party-search-result selected" style="cursor:default">
              <div class="party-avatar-sm">{{ providerInitials }}</div>
              <div class="party-info-sm"><div class="party-name-sm">{{ providerName }}</div><div class="party-meta-sm">Primary Care - Active</div></div>
            </div>
          </div>
          <div>
            <div class="modal-section-label" style="display:flex;align-items:center;gap:6px;color:var(--gold-dark)">
              <AegisIcon name="user" :size="13" /><span>Party B - Select Steward</span>
            </div>
            <div class="input-group" style="margin-bottom:10px">
              <span class="input-group-icon"><AegisIcon name="search" :size="14" /></span>
              <input class="form-input form-input-sm" type="text" v-model="wiz.partyBSearch" placeholder="Search by name or Aegis ID..." />
            </div>
            <div
              v-for="p in filteredPartyB" :key="p.id"
              class="party-search-result" :class="{ selected: wiz.partyB === p.id }"
              @click="wiz.partyB = p.id"
            >
              <div class="party-avatar-sm">{{ p.initials }}</div>
              <div class="party-info-sm"><div class="party-name-sm">{{ p.name }}</div><div class="party-meta-sm">{{ p.meta }}</div></div>
            </div>
          </div>
        </div>
        <hr class="divider">
        <div class="modal-section-label">Terms &amp; Duration</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px">
          <div class="form-group">
            <label class="form-label">Effective Date <span class="required">*</span></label>
            <input type="date" class="form-input" v-model="wiz.effectiveDate" />
          </div>
          <div class="form-group">
            <label class="form-label">Expiration Date</label>
            <input type="date" class="form-input" v-model="wiz.expirationDate" />
          </div>
          <div class="form-group">
            <label class="form-label">Auto-Renew</label>
            <select class="form-select" v-model="wiz.autoRenew">
              <option>No - Manual renewal required</option>
              <option>Yes - Annually</option>
              <option>Yes - Every 6 months</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Jurisdiction / Governing Law</label>
            <input type="text" class="form-input" v-model="wiz.jurisdiction" />
          </div>
          <div class="form-group">
            <label class="form-label">Dispute Resolution</label>
            <select class="form-select" v-model="wiz.disputeRes">
              <option>Aegis Platform Mediation first, then Arbitration</option>
              <option>Binding Arbitration (AAA Rules)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Step 3 -->
      <div v-show="wizStep === 3">
        <div class="alert alert-info" style="margin-bottom:16px">
          <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
          <div class="alert-content">All clauses are pre-filled from the Aegis standard template. Required clauses cannot be removed.</div>
        </div>
        <div v-for="clause in clauses" :key="clause.id" class="clause-section">
          <div class="clause-header" @click="clause.open = !clause.open">
            <div class="clause-header-left">
              <div class="clause-num">{{ clause.num }}</div>
              <div class="clause-title">{{ clause.title }}</div>
            </div>
            <div style="display:inline-flex;align-items:center;gap:8px">
              <span class="clause-tag" :class="clause.tagClass">{{ clause.tagLabel }}</span>
              <AegisIcon name="chevron-down" :size="12" :style="clause.open ? 'transform:rotate(180deg)' : ''" />
            </div>
          </div>
          <div class="clause-body" :class="{ open: clause.open }">
            <div v-for="field in clause.fields" :key="field.label" class="form-group">
              <label class="form-label">{{ field.label }}</label>
              <textarea v-if="field.type === 'textarea'" class="form-textarea" v-model="field.value" :placeholder="field.placeholder" rows="3"></textarea>
              <select v-else-if="field.type === 'select'" class="form-select" v-model="field.value">
                <option v-for="o in field.options" :key="o">{{ o }}</option>
              </select>
              <input v-else type="text" class="form-input" v-model="field.value" :placeholder="field.placeholder" />
            </div>
          </div>
        </div>
      </div>

      <!-- Step 4 -->
      <div v-show="wizStep === 4">
        <div class="alert alert-success" style="margin-bottom:16px">
          <div class="alert-icon"><AegisIcon name="check" :size="18" /></div>
          <div class="alert-content">Your agreement is ready to review. Please verify all details before proceeding to signature.</div>
        </div>
        <div class="review-grid-3">
          <div class="info-card">
            <div class="info-card-title">Document</div>
            <div class="info-card-row"><span class="info-card-key">Type</span><span class="info-card-val">{{ wiz.docType || '-' }}</span></div>
            <div class="info-card-row"><span class="info-card-key">Category</span><span class="info-card-val">{{ selectedCatTitle }}</span></div>
            <div class="info-card-row"><span class="info-card-key">Reference</span><span class="info-card-val">{{ wiz.reference || 'Auto-generated' }}</span></div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Parties</div>
            <div class="info-card-row"><span class="info-card-key">Party A</span><span class="info-card-val">{{ providerName }}</span></div>
            <div class="info-card-row"><span class="info-card-key">Party B</span><span class="info-card-val">{{ selectedPartyBName }}</span></div>
          </div>
          <div class="info-card">
            <div class="info-card-title">Term</div>
            <div class="info-card-row"><span class="info-card-key">Effective</span><span class="info-card-val">{{ wiz.effectiveDate || '-' }}</span></div>
            <div class="info-card-row"><span class="info-card-key">Expires</span><span class="info-card-val">{{ wiz.expirationDate || '-' }}</span></div>
            <div class="info-card-row"><span class="info-card-key">Auto-renew</span><span class="info-card-val">{{ wiz.autoRenew }}</span></div>
          </div>
        </div>
        <div class="legal-doc" style="margin-top:14px">
          <div class="legal-doc-title">MASTER SERVICE AGREEMENT</div>
          <div class="legal-doc-sub">Draft - REF: {{ wiz.reference || 'TBD' }}</div>
          <h4>1. Scope of Services</h4>
          <p>Continuity Steward is authorized to manage client referral workflows, coordinate with specialty practices, schedule appointments, supervise billing operations, and represent Provider in non-clinical communications.</p>
          <h4>2. Confidentiality &amp; HIPAA</h4>
          <p>Continuity Steward shall comply with HIPAA and the HITECH Act. PHI access is Read-Only. A Business Associate Agreement is incorporated by reference.</p>
          <h4>3. Compensation</h4>
          <p>Provider shall compensate Continuity Steward per the terms defined in Clause 3.</p>
          <h4>4. Termination</h4>
          <p>Either party may terminate with 30 days written notice. Provider may terminate immediately for HIPAA breach, fraud, or gross negligence.</p>
        </div>
      </div>

      <!-- Step 5 -->
      <div v-show="wizStep === 5">
        <div class="alert alert-success" style="margin-bottom:16px">
          <div class="alert-icon"><AegisIcon name="check" :size="18" /></div>
          <div class="alert-content"><strong>Agreement is ready to send!</strong> Configure signing options below.</div>
        </div>
        <div class="party-grid" style="margin-bottom:16px">
          <div style="border:1px solid var(--border);border-left:3px solid var(--gold-dark);border-radius:var(--radius);padding:14px;background:var(--surface-2)">
            <div class="modal-section-label" style="display:flex;align-items:center;gap:6px;color:var(--gold-dark);margin-bottom:8px">
              <AegisIcon name="user" :size="13" /><span>Party A - Provider (You)</span>
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label">My Signing Action</label>
              <select class="form-select form-select-sm" v-model="sig.myAction">
                <option>Sign first, then send to other party</option>
                <option>Send to other party first, I countersign</option>
              </select>
            </div>
          </div>
          <div style="border:1px solid var(--border);border-left:3px solid var(--gold-dark);border-radius:var(--radius);padding:14px;background:var(--surface-2)">
            <div class="modal-section-label" style="display:flex;align-items:center;gap:6px;color:var(--gold-dark);margin-bottom:8px">
              <AegisIcon name="users" :size="13" /><span>Party B - Counterparty</span>
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label">Notification Method</label>
              <select class="form-select form-select-sm" v-model="sig.notifyMethod">
                <option>Email + Aegis In-App Notification</option>
                <option>Email only</option>
              </select>
            </div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Signature Deadline</label>
            <input type="date" class="form-input" v-model="sig.deadline" />
          </div>
          <div class="form-group">
            <label class="form-label">Reminder Frequency</label>
            <select class="form-select" v-model="sig.reminder">
              <option>Every 2 days after sending</option>
              <option>Every day</option>
              <option>Once only</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Personal Message</label>
          <textarea class="form-textarea" v-model="sig.message" rows="3" placeholder="e.g. Hi Marcus, please review and sign at your earliest convenience..."></textarea>
        </div>
        <div style="border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
          <label class="sig-check-row" style="padding:11px 14px"><input type="checkbox" v-model="sig.c1" style="accent-color:var(--gold-dark)"><span class="sig-check-label">All required clauses are complete</span></label>
          <label class="sig-check-row" style="padding:11px 14px"><input type="checkbox" v-model="sig.c2" style="accent-color:var(--gold-dark)"><span class="sig-check-label">Party B contact details verified</span></label>
          <label class="sig-check-row" style="padding:11px 14px"><input type="checkbox" v-model="sig.c3" style="accent-color:var(--gold-dark)"><span class="sig-check-label">I confirm I am authorized to create &amp; send this agreement</span></label>
          <label class="sig-check-row" style="padding:11px 14px;border-bottom:none"><input type="checkbox" v-model="sig.c4" style="accent-color:var(--gold-dark)"><span class="sig-check-label">I understand this agreement grants PHI access under HIPAA BAA terms</span></label>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-outline" @click="closeWizard">Cancel</button>
        <button v-if="wizStep >= 3" class="btn btn-outline" @click="openModal('draftSaveModal')">
          <AegisIcon name="save" :size="13" /> Save Draft
        </button>
        <div style="display:inline-flex;align-items:center;gap:6px;margin-left:auto;font-size:11px;color:var(--text-4);font-weight:600">
          <AegisIcon name="shield" :size="12" /> Encrypted &amp; audit-logged
        </div>
        <button v-if="wizStep > 1" class="btn btn-outline" @click="wizStep--">Back</button>
        <button v-if="wizStep < 5" class="btn btn-primary" @click="wizardNext">{{ stepNextLabels[wizStep - 1] }}</button>
        <button
          v-if="wizStep === 5"
          class="btn btn-primary"
          :disabled="!sig.c1 || !sig.c2 || !sig.c3 || sendBusy"
          @click="sendForSignature"
        >
          <AegisIcon name="send" :size="13" />
          {{ sendBusy ? 'Sending...' : 'Send for Signature' }}
        </button>
      </template>
    </AegisModal>

    <!-- ═══ MODAL 2: SIGNATURE ═══ -->
    <AegisModal
      :model-value="isOpen('signatureModal').value"
      title="Sign Agreement"
      size="lg"
      @update:model-value="v => !v && closeModal('signatureModal')"
    >
      <div v-if="activeDoc">
        <div class="alert alert-info" style="margin-bottom:14px">
          <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
          <div class="alert-content">By signing below you are entering into a legally binding agreement.</div>
        </div>
        <div class="legal-doc" style="margin-bottom:14px">
          <div class="legal-doc-title">{{ activeDoc.title }}</div>
          <div class="legal-doc-sub">{{ activeDoc.reference }}</div>
          <p>This agreement is legally binding. Please review all clauses before signing.</p>
        </div>
        <div class="section-title" style="margin-bottom:12px">Pre-Signature Checklist</div>
        <div class="review-item">
          <div class="review-check-icon"><AegisIcon name="check" :size="16" /></div>
          <div><div class="review-label">I have read the complete agreement</div><div style="font-size:12px;color:var(--text-3)">All sections reviewed</div></div>
          <div style="margin-left:auto"><input type="checkbox" v-model="signCheck1" style="accent-color:var(--gold-dark)"></div>
        </div>
        <div class="review-item">
          <div class="review-check-icon"><AegisIcon name="info" :size="16" /></div>
          <div><div class="review-label">I understand PHI access granted under this agreement</div></div>
          <div style="margin-left:auto"><input type="checkbox" v-model="signCheck2" style="accent-color:var(--gold-dark)"></div>
        </div>
        <hr class="divider">
        <div class="party-grid">
          <div class="party-col provider">
            <div class="party-label">Provider - Your Signature</div>
            <div class="party-name">{{ providerName }}</div>
            <div class="party-sig">
              <div style="font-size:11px;color:var(--text-4);margin-bottom:6px">Click to sign:</div>
              <div class="sign-area" :class="{ signed: isSigned }" @click="applySignature">
                <template v-if="!isSigned">
                  <AegisIcon name="signature" :size="22" />
                  <span>Click here to apply your signature</span>
                  <span style="font-size:11px;color:var(--text-4)">Uses your verified name on file</span>
                </template>
                <template v-else>
                  <span class="sig-name-display">{{ providerName }}</span>
                  <span style="font-size:11px"><AegisIcon name="check" :size="11" /> Applied - click to change</span>
                </template>
              </div>
            </div>
          </div>
          <div v-if="activeDoc.counterparty" class="party-col cs">
            <div class="party-label">Continuity Steward - Already Signed</div>
            <div class="party-name">{{ activeDoc.counterparty.name }}</div>
            <div class="party-sig">
              <div style="font-size:11px;color:var(--text-4);margin-bottom:6px">Signed on {{ activeDoc.counterparty.signed_at }}:</div>
              <div class="sign-area signed" style="cursor:default">
                <span class="sig-name-display">{{ activeDoc.counterparty.name }}</span>
                <span style="font-size:11px"><AegisIcon name="check" :size="11" /> Verified signature on file</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('signatureModal')">Cancel</button>
        <button
          class="btn btn-primary"
          style="margin-left:auto"
          :disabled="!signCheck1 || !isSigned || signBusy"
          @click="finalizeSignature"
        >
          <AegisIcon name="signature" :size="13" />
          {{ signBusy ? 'Signing...' : 'Apply Signature &amp; Execute' }}
        </button>
      </template>
    </AegisModal>

    <!-- ═══ MODAL 3: VIEW AGREEMENT ═══ -->
    <AegisModal
      :model-value="isOpen('viewAgreementModal').value"
      :title="activeDoc ? (activeDoc.reference + ' - ' + activeDoc.title) : 'View Agreement'"
      size="xl"
      @update:model-value="v => !v && closeModal('viewAgreementModal')"
    >
      <div v-if="activeDoc">
        <div style="display:flex;gap:12px;margin-bottom:14px;flex-wrap:wrap">
          <div class="info-card" style="flex:1;min-width:180px">
            <div class="info-card-title">Agreement Details</div>
            <div class="info-card-row"><span class="info-card-key">Reference</span><span class="info-card-val">{{ activeDoc.reference }}</span></div>
            <div class="info-card-row"><span class="info-card-key">Type</span><span class="info-card-val">{{ activeDoc.doc_type_label }}</span></div>
            <div class="info-card-row"><span class="info-card-key">Version</span><span class="info-card-val">{{ activeDoc.version || 'v1.0' }}</span></div>
          </div>
          <div class="info-card" style="flex:1;min-width:180px">
            <div class="info-card-title">Term &amp; Status</div>
            <div class="info-card-row"><span class="info-card-key">Effective</span><span class="info-card-val">{{ activeDoc.effective_date || '-' }}</span></div>
            <div class="info-card-row"><span class="info-card-key">Expires</span><span class="info-card-val">{{ activeDoc.expiry_date || '-' }}</span></div>
            <div class="info-card-row"><span class="info-card-key">Status</span><span class="info-card-val" style="color:var(--green-dark)">Active</span></div>
          </div>
          <div class="info-card" style="flex:1;min-width:180px">
            <div class="info-card-title">Signature Status</div>
            <div class="info-card-row"><span class="info-card-key">Provider</span><span class="info-card-val" style="color:var(--green)"><AegisIcon name="check" :size="10" /> {{ providerName }}</span></div>
            <div v-if="activeDoc.counterparty" class="info-card-row"><span class="info-card-key">CS</span><span class="info-card-val" style="color:var(--green)"><AegisIcon name="check" :size="10" /> {{ activeDoc.counterparty.name }}</span></div>
          </div>
        </div>
        <div class="party-grid" style="margin-bottom:14px">
          <div class="party-col provider">
            <div class="party-label">Provider (Party A)</div>
            <div class="party-name">{{ providerName }}</div>
            <div class="party-sig">
              <div class="sig-name-display">{{ providerName }}</div>
              <div style="font-size:11px;color:var(--text-4)">Verified signature on file</div>
            </div>
          </div>
          <div v-if="activeDoc.counterparty" class="party-col cs">
            <div class="party-label">Continuity Steward (Party B)</div>
            <div class="party-name">{{ activeDoc.counterparty.name }}</div>
            <div class="party-sig">
              <div class="sig-name-display">{{ activeDoc.counterparty.name }}</div>
              <div style="font-size:11px;color:var(--text-4)">Signed: {{ activeDoc.counterparty.signed_at }} - Verified</div>
            </div>
          </div>
        </div>
        <div class="legal-doc" style="margin-bottom:14px">
          <div class="legal-doc-title">{{ (activeDoc.title || '').toUpperCase() }}</div>
          <div class="legal-doc-sub">{{ activeDoc.reference }}</div>
          <p>{{ activeDoc.body || 'Full agreement body will appear here.' }}</p>
        </div>
        <hr class="divider">
        <div class="section-title" style="margin-bottom:12px">Agreement History</div>
        <div class="timeline">
          <div v-for="h in (activeDoc.history || [])" :key="h.date" class="timeline-item">
            <div class="timeline-dot" :class="h.dot || 'green'"></div>
            <div class="timeline-time">{{ h.date }}</div>
            <div class="timeline-title">{{ h.title }}</div>
            <div class="timeline-desc">{{ h.desc }}</div>
          </div>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('viewAgreementModal')">Close</button>
        <button class="btn btn-outline" @click="openAmendModal"><AegisIcon name="pencil" :size="13" /> Request Amendment</button>
        <button class="btn btn-outline" style="color:var(--red);margin-left:auto" @click="closeModal('viewAgreementModal'); openModal('terminateModal')">
          <AegisIcon name="x-circle" :size="13" /> Terminate
        </button>
      </template>
    </AegisModal>

    <!-- ═══ MODAL 4: RENEWAL ═══ -->
    <AegisModal
      :model-value="isOpen('renewalModal').value"
      title="Renew Agreement"
      size="lg"
      @update:model-value="v => !v && closeModal('renewalModal')"
    >
      <div v-if="activeDoc">
        <div class="alert alert-warning" style="margin-bottom:14px">
          <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
          <div class="alert-content">
            This agreement expires on <strong>{{ activeDoc.expiry_date || 'a future date' }}</strong>. If not renewed, counterparty access may be suspended on expiry.
          </div>
        </div>
        <div class="modal-section-label" style="margin-bottom:8px">Renewal Options</div>
        <div style="margin-bottom:20px">
          <div class="party-search-result ren-opt" :class="{ selected: renewForm.type === 'same' }" @click="renewForm.type = 'same'">
            <div class="party-avatar-sm"><AegisIcon name="refresh-cw" :size="16" /></div>
            <div class="party-info-sm"><div class="party-name-sm">Renew with Same Terms</div><div class="party-meta-sm">Extend 12 months with identical clauses. Both parties re-sign.</div></div>
            <AegisBadge label="Recommended" variant="green" />
          </div>
          <div class="party-search-result ren-opt" :class="{ selected: renewForm.type === 'amended' }" @click="renewForm.type = 'amended'">
            <div class="party-avatar-sm"><AegisIcon name="pencil" :size="16" /></div>
            <div class="party-info-sm"><div class="party-name-sm">Renew with Amendments</div><div class="party-meta-sm">Modify specific clauses before renewal.</div></div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">New Effective Date</label>
            <input type="date" class="form-input" v-model="renewForm.effectiveDate" />
          </div>
          <div class="form-group">
            <label class="form-label">New Expiry Date</label>
            <input type="date" class="form-input" v-model="renewForm.expiryDate" />
          </div>
        </div>
        <div class="alert alert-success">
          <div class="alert-icon"><AegisIcon name="check" :size="18" /></div>
          <div class="alert-content">After renewal, the counterparty will receive an email and must re-sign within 5 business days.</div>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('renewalModal')">Cancel</button>
        <button class="btn btn-primary" style="margin-left:auto" :disabled="renewBusy" @click="submitRenew">
          <AegisIcon name="refresh-cw" :size="13" /> {{ renewBusy ? 'Initiating...' : 'Initiate Renewal' }}
        </button>
      </template>
    </AegisModal>

    <!-- ═══ MODAL 5: AGREEMENT ACTIONS ═══ -->
    <AegisModal
      :model-value="isOpen('agreementActionsModal').value"
      title="Agreement Actions"
      size="sm"
      @update:model-value="v => !v && closeModal('agreementActionsModal')"
    >
      <div v-if="activeDoc" style="font-size:12px;color:var(--text-3);margin-bottom:10px">{{ activeDoc.reference }} - {{ activeDoc.doc_type_label }}</div>
      <div>
        <div v-for="item in actionItems" :key="item.action" class="list-item" @click="handleAction(item.action)">
          <div class="list-item-icon" :style="item.iconStyle || ''"><AegisIcon :name="item.icon" :size="16" /></div>
          <div class="list-item-content">
            <div class="list-item-title" :style="item.danger ? 'color:var(--red)' : ''">{{ item.label }}</div>
            <div class="list-item-desc">{{ item.sub }}</div>
          </div>
          <AegisIcon name="chevron-right" :size="12" />
        </div>
      </div>
    </AegisModal>

    <!-- ═══ MODAL 6: TERMINATE ═══ -->
    <AegisModal
      :model-value="isOpen('terminateModal').value"
      title="Terminate Agreement"
      size="sm"
      @update:model-value="v => !v && closeTerminate()"
    >
      <div class="alert alert-danger" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
        <div class="alert-content">Terminating this agreement will immediately revoke counterparty access and delegated authority.</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="termReason">Reason for Termination <span class="required">*</span></label>
        <select id="termReason" class="form-select" v-model="terminateForm.reason" :class="{ 'is-error': fieldError('term_reason') }" @blur="v$.term_reason.$touch()">
          <option value="">- Select Reason -</option>
          <option>Mutual Consent</option>
          <option>Performance Issues</option>
          <option>HIPAA / Compliance Violation</option>
          <option>Fraud or Misconduct</option>
          <option>End of Service Need</option>
        </select>
        <div v-if="fieldError('term_reason')" class="form-error">{{ fieldError('term_reason') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Effective Termination Date</label>
        <input type="date" class="form-input" v-model="terminateForm.date" />
        <div class="form-hint">Standard 30-day notice required unless terminating for cause.</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="termConfirm">Type "TERMINATE" to confirm <span class="required">*</span></label>
        <input id="termConfirm" type="text" class="form-input" v-model="terminateForm.confirm" :class="{ 'is-error': fieldError('term_confirm') }" @blur="v$.term_confirm.$touch()" placeholder="TERMINATE" />
        <div v-if="fieldError('term_confirm')" class="form-error">{{ fieldError('term_confirm') }}</div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeTerminate">Cancel</button>
        <button class="btn btn-danger" style="margin-left:auto" :disabled="terminateForm.confirm !== 'TERMINATE' || terminateBusy" @click="submitTerminate">
          <AegisIcon name="x-circle" :size="13" /> {{ terminateBusy ? 'Terminating...' : 'Confirm Termination' }}
        </button>
      </template>
    </AegisModal>

    <!-- ═══ MODAL 7: SEND FOR SIGNATURE ═══ -->
    <AegisModal
      :model-value="isOpen('sendForSignatureModal').value"
      title="Send for Signature"
      size="md"
      @update:model-value="v => !v && closeModal('sendForSignatureModal')"
    >
      <div class="alert alert-info" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
        <div class="alert-content">All parties will receive a secure email link to review and sign electronically.</div>
      </div>
      <div class="modal-section-label" style="margin-bottom:8px">Recipients</div>
      <div v-if="activeDoc && activeDoc.counterparty" class="party-search-result selected" style="cursor:default;margin-bottom:14px">
        <div class="party-avatar-sm">{{ activeDoc.counterparty.initials || '?' }}</div>
        <div class="party-info-sm">
          <div class="party-name-sm">{{ activeDoc.counterparty.name }}</div>
          <div class="party-meta-sm">{{ activeDoc.counterparty.meta || '' }}</div>
        </div>
        <AegisBadge label="Will Receive" variant="gold" />
      </div>
      <div class="form-group">
        <label class="form-label">Signature Deadline</label>
        <input type="date" class="form-input" v-model="sendSigForm.deadline" />
        <div class="form-hint">Reminder sent 48 hrs before deadline.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Personal Message <span style="color:var(--text-4)">(optional)</span></label>
        <textarea class="form-textarea" rows="3" v-model="sendSigForm.message" placeholder="e.g. Hi Marcus, please review and sign at your earliest convenience..."></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('sendForSignatureModal')">Cancel</button>
        <button class="btn btn-primary" style="margin-left:auto" :disabled="sendSigBusy" @click="submitSendForSig">
          <AegisIcon name="send" :size="13" /> {{ sendSigBusy ? 'Sending...' : 'Send for Signature' }}
        </button>
      </template>
    </AegisModal>

    <!-- ═══ MODAL 8: TEMPLATES LIBRARY ═══ -->
    <AegisModal
      :model-value="isOpen('templateModal').value"
      title="Sample Templates"
      size="lg"
      @update:model-value="v => !v && closeModal('templateModal')"
    >
      <p style="font-size:13px;color:var(--text-3);margin-bottom:14px">Access sample templates as starting points you can review and adapt to your preferences, practice needs, and professional or legal considerations.</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <div v-for="t in libraryTemplates" :key="t.title" class="party-search-result" @click="closeModal('templateModal'); openWizard()">
          <div class="party-avatar-sm"><AegisIcon :name="t.icon" :size="16" /></div>
          <div class="party-info-sm"><div class="party-name-sm">{{ t.title }}</div><div class="party-meta-sm">{{ t.sub }}</div></div>
          <AegisBadge :label="t.tag" :variant="t.tag === 'Aegis' ? 'gold' : 'gray'" />
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('templateModal')">Close</button>
        <button class="btn btn-primary" style="margin-left:auto" @click="closeModal('templateModal'); openWizard()">
          <AegisIcon name="plus" :size="14" /> Start New from Template
        </button>
      </template>
    </AegisModal>

    <!-- ═══ MODAL 9: EXPORT ═══ -->
    <AegisModal
      :model-value="isOpen('exportModal').value"
      title="Export Important Documents"
      size="sm"
      @update:model-value="v => !v && closeModal('exportModal')"
    >
      <div class="form-group">
        <label class="form-label">Export Format</label>
        <select class="form-select" v-model="exportForm.format">
          <option>PDF - Individual signed PDFs (ZIP)</option>
          <option>CSV - Summary spreadsheet</option>
          <option>PDF - Single combined document</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Scope</label>
        <select class="form-select" v-model="exportForm.scope">
          <option>All documents</option>
          <option>Agreements only</option>
          <option>Supporting documents only</option>
          <option>Active agreements only</option>
          <option>Current filter view</option>
        </select>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('exportModal')">Cancel</button>
        <button class="btn btn-primary" style="margin-left:auto" :disabled="exportBusy" @click="submitExport">
          <AegisIcon name="download" :size="13" /> {{ exportBusy ? 'Exporting...' : 'Export' }}
        </button>
      </template>
    </AegisModal>

    <!-- ═══ MODAL 10: ADD DOCUMENT ═══ -->
    <AegisModal
      :model-value="isOpen('addDocumentModal').value"
      title="Add Document"
      size="lg"
      @update:model-value="v => !v && closeAddDoc()"
    >
      <div class="alert alert-info" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
        <div class="alert-content">Use this to add a supporting document. To create a signed agreement instead, use <strong>Create New Agreement</strong>.</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="addDocName">Document Name <span class="required">*</span></label>
        <input id="addDocName" type="text" class="form-input" v-model="addDocForm.name"
          :class="{ 'is-error': fieldError('addDoc_name') }"
          @blur="v$.addDoc_name.$touch()"
          placeholder="e.g., Continuity Plan - Amendment #3" />
        <div v-if="fieldError('addDoc_name')" class="form-error">{{ fieldError('addDoc_name') }}</div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Document Type</label>
          <select class="form-select" v-model="addDocForm.type">
            <option>Supporting Document</option>
            <option>Amendment</option>
            <option>Reference Material</option>
            <option>Other</option>
          </select>
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
      </div>
      <div class="form-group">
        <label class="form-label">File <span class="required">*</span></label>
        <AegisDropzone accept=".pdf,.doc,.docx,.txt" :max-size="10" @files="addDocFiles = $event" @rejected="toast.error('File rejected - check size or type.')" />
      </div>
      <div class="form-group">
        <label class="form-label">Notes <span style="color:var(--text-4)">(optional)</span></label>
        <textarea class="form-textarea" rows="2" v-model="addDocForm.notes" placeholder="Add any context for your stewards..."></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeAddDoc">Cancel</button>
        <button class="btn btn-primary" style="margin-left:auto" :disabled="addDocBusy" @click="submitAddDoc">
          <AegisIcon name="upload" :size="13" /> {{ addDocBusy ? 'Uploading...' : 'Add Document' }}
        </button>
      </template>
    </AegisModal>

    <!-- ═══ MODAL 11: AMENDMENT REQUEST ═══ -->
    <AegisModal
      :model-value="isOpen('amendmentModal').value"
      :title="activeDoc ? ('Request Amendment - ' + activeDoc.reference) : 'Request Amendment'"
      size="xl"
      @update:model-value="v => !v && closeAmend()"
    >
      <div class="alert alert-info" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
        <div class="alert-content">Amendments require agreement from all original signatories. The other party will be notified and must accept or counter-propose changes before the amendment takes effect.</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="amendType">Amendment Type <span class="required">*</span></label>
        <select id="amendType" class="form-select" v-model="amendForm.type" :class="{ 'is-error': fieldError('amend_type') }" @blur="v$.amend_type.$touch()">
          <option value="">- Select Type -</option>
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
          <textarea class="form-textarea" rows="4" v-model="amendForm.proposed"
            :class="{ 'is-error': fieldError('amend_proposed') }"
            @blur="v$.amend_proposed.$touch()"
            placeholder="Describe the proposed new language..."></textarea>
          <div v-if="fieldError('amend_proposed')" class="form-error">{{ fieldError('amend_proposed') }}</div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Reason / Justification</label>
        <textarea class="form-textarea" rows="2" v-model="amendForm.reason" placeholder="Briefly explain why this amendment is being requested..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Proposed Effective Date</label>
        <input type="date" class="form-input" v-model="amendForm.effectiveDate" />
        <div class="form-hint">If blank, takes effect upon mutual execution.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Supporting Documents <span style="color:var(--text-4)">(optional)</span></label>
        <AegisDropzone accept=".pdf,.doc,.docx,.txt" :max-size="10" @files="amendFiles = $event" @rejected="toast.error('File rejected.')" />
        <div class="form-hint">Attach any prior correspondence, redlined drafts, or supporting evidence.</div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeAmend">Cancel</button>
        <button class="btn btn-primary" style="margin-left:auto" :disabled="amendBusy" @click="submitAmend">
          <AegisIcon name="send" :size="13" /> {{ amendBusy ? 'Sending...' : 'Send Amendment Request' }}
        </button>
      </template>
    </AegisModal>

    <!-- ═══ MODAL 12: BAA PREVIEW ═══ -->
    <AegisModal
      :model-value="isOpen('baaModal').value"
      title="Business Associate Agreement (BAA)"
      size="lg"
      @update:model-value="v => !v && closeModal('baaModal')"
    >
      <div class="alert alert-info" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
        <div class="alert-content">This BAA is incorporated by reference into the parent agreement. Both parties become bound upon execution of the parent agreement.</div>
      </div>
      <div class="legal-doc">
        <div class="legal-doc-title">BUSINESS ASSOCIATE AGREEMENT</div>
        <div class="legal-doc-sub">Pursuant to HIPAA/HITECH - Incorporated into parent agreement</div>
        <h4>1. Definitions</h4>
        <p>"Business Associate" means the party receiving PHI access. "Covered Entity" means the Provider. "PHI" means Protected Health Information as defined under 45 CFR 160.103.</p>
        <h4>2. Obligations of Business Associate</h4>
        <p>Business Associate shall not use or disclose PHI other than as permitted. Business Associate shall implement appropriate safeguards and comply with Subpart C of 45 CFR Part 164. Unauthorized use or disclosure shall be reported within 5 business days of discovery.</p>
        <h4>3. Permitted Uses and Disclosures</h4>
        <p>Business Associate may use PHI only for purposes specified in the parent agreement Scope of Services. Disclosure to subcontractors requires a written BAA mirroring these obligations.</p>
        <h4>4. Breach Notification</h4>
        <p>Business Associate shall notify Covered Entity of any Breach of Unsecured PHI without unreasonable delay and no later than 60 calendar days after discovery, per 45 CFR 164.410.</p>
        <h4>5. Term and Termination</h4>
        <p>This Agreement terminates concurrently with the parent agreement. Upon termination, Business Associate shall return or destroy all PHI and certify in writing within 10 business days.</p>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('baaModal')">Close</button>
        <button class="btn btn-primary" style="margin-left:auto" @click="closeModal('baaModal'); toast.success('BAA included in agreement.')">
          <AegisIcon name="check" :size="14" /> Understood - Include BAA
        </button>
      </template>
    </AegisModal>

    <!-- ═══ MODAL 13: NDA ATTACH CONFIGURATOR ═══ -->
    <AegisModal
      :model-value="isOpen('ndaAttachModal').value"
      title="Configure NDA Addendum"
      size="md"
      @update:model-value="v => !v && closeModal('ndaAttachModal')"
    >
      <p style="font-size:13px;color:var(--text-3);margin-bottom:14px">A Non-Disclosure Agreement will be attached to your main agreement.</p>
      <div class="form-group">
        <label class="form-label">Information to keep confidential</label>
        <div style="display:flex;flex-direction:column;gap:6px">
          <label v-for="item in ndaItems" :key="item.label" style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
            <input type="checkbox" v-model="item.checked" style="accent-color:var(--gold-dark)"> {{ item.label }}
          </label>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">NDA Duration</label>
          <select class="form-select" v-model="ndaForm.duration">
            <option>Duration of parent agreement</option>
            <option>2 years post-termination</option>
            <option>5 years post-termination</option>
            <option>Perpetual</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">NDA Type</label>
          <select class="form-select" v-model="ndaForm.type">
            <option>One-way (other party only)</option>
            <option>Mutual (both parties)</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Permitted Exceptions</label>
        <textarea class="form-textarea" rows="2" v-model="ndaForm.exceptions" placeholder="e.g. Disclosure required by law or court order, with prior written notice to the disclosing party..."></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('ndaAttachModal')">Cancel</button>
        <button class="btn btn-primary" style="margin-left:auto" @click="closeModal('ndaAttachModal'); toast.success('NDA attached to agreement.')">
          <AegisIcon name="lock" :size="14" /> Attach NDA to Agreement
        </button>
      </template>
    </AegisModal>

    <!-- ═══ MODAL 14: ACCESS REVOCATION ═══ -->
    <AegisModal
      :model-value="isOpen('accessRevocationModal').value"
      title="Revoke Access"
      size="sm"
      @update:model-value="v => !v && closeRevoke()"
    >
      <div class="alert alert-danger" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
        <div class="alert-content"><strong>Immediate &amp; irreversible.</strong> The counterparty will lose all Aegis access, PHI access, and delegated permissions within 5 minutes.</div>
      </div>
      <div class="info-card" style="margin-bottom:14px">
        <div class="info-card-title">Access Being Revoked</div>
        <div class="info-card-row"><span class="info-card-key">Platform Access</span><span class="info-card-val" style="color:var(--red)">Terminated</span></div>
        <div class="info-card-row"><span class="info-card-key">PHI Access</span><span class="info-card-val" style="color:var(--red)">Terminated</span></div>
        <div class="info-card-row"><span class="info-card-key">Delegated Authority</span><span class="info-card-val" style="color:var(--red)">Terminated</span></div>
        <div class="info-card-row"><span class="info-card-key">Active Agreement</span><span class="info-card-val" style="color:var(--orange)">Suspended (not terminated)</span></div>
      </div>
      <div class="form-group">
        <label class="form-label" for="revokeReason">Reason <span class="required">*</span></label>
        <select id="revokeReason" class="form-select" v-model="revokeForm.reason" :class="{ 'is-error': fieldError('revoke_reason') }" @blur="v$.revoke_reason.$touch()">
          <option value="">- Select Reason -</option>
          <option>Suspected PHI Breach</option>
          <option>Fraudulent Activity</option>
          <option>Agreement Termination</option>
          <option>Pending Investigation</option>
          <option>Party Request</option>
          <option>Other</option>
        </select>
        <div v-if="fieldError('revoke_reason')" class="form-error">{{ fieldError('revoke_reason') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="revokeConfirm">Type "REVOKE" to confirm <span class="required">*</span></label>
        <input id="revokeConfirm" type="text" class="form-input" v-model="revokeForm.confirm" :class="{ 'is-error': fieldError('revoke_confirm') }" @blur="v$.revoke_confirm.$touch()" placeholder="REVOKE" />
        <div v-if="fieldError('revoke_confirm')" class="form-error">{{ fieldError('revoke_confirm') }}</div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeRevoke">Cancel</button>
        <button class="btn btn-danger" style="margin-left:auto" :disabled="revokeForm.confirm !== 'REVOKE' || revokeBusy" @click="submitRevoke">
          <AegisIcon name="lock" :size="13" /> {{ revokeBusy ? 'Revoking...' : 'Confirm Revocation' }}
        </button>
      </template>
    </AegisModal>

    <!-- ═══ MODAL 15: DRAFT SAVE ═══ -->
    <AegisModal
      :model-value="isOpen('draftSaveModal').value"
      title="Save as Draft"
      size="sm"
      @update:model-value="v => !v && closeModal('draftSaveModal')"
    >
      <p style="font-size:13px;color:var(--text-3);margin-bottom:14px">Your progress will be saved - return anytime to continue.</p>
      <div class="form-group">
        <label class="form-label">Draft Name</label>
        <input type="text" class="form-input" v-model="draftForm.name" :placeholder="wiz.docType ? (wiz.docType + ' - Draft') : 'Draft Agreement'" />
      </div>
      <div class="form-group">
        <label class="form-label">Notes <span style="color:var(--text-4)">(optional)</span></label>
        <textarea class="form-textarea" rows="2" v-model="draftForm.notes" placeholder="Reminders about what still needs to be done..."></textarea>
      </div>
      <div class="alert alert-info" style="margin-bottom:0">
        <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
        <div class="alert-content">Drafts are private. The other party will not be notified until you send for signature.</div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('draftSaveModal')">Cancel</button>
        <button class="btn btn-primary" style="margin-left:auto" :disabled="draftBusy" @click="submitSaveDraft">
          <AegisIcon name="save" :size="14" /> {{ draftBusy ? 'Saving...' : 'Save Draft' }}
        </button>
      </template>
    </AegisModal>

    <!-- ═══ MODAL 16: SIGN SUCCESS ═══ -->
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
        <div style="font-family:var(--font-serif);font-size:22px;font-weight:700;color:var(--text);margin-bottom:6px">Agreement Fully Executed!</div>
        <div style="font-size:13px;color:var(--text-3);margin-bottom:20px">The agreement has been signed by both parties. A certified copy has been delivered to all signatories.</div>
        <div v-if="activeDoc" class="info-card" style="text-align:left;margin-bottom:20px">
          <div class="info-card-row"><span class="info-card-key">Reference</span><span class="info-card-val">{{ activeDoc.reference }}</span></div>
          <div class="info-card-row"><span class="info-card-key">Provider</span><span class="info-card-val" style="color:var(--green)"><AegisIcon name="check" :size="10" /> {{ providerName }}</span></div>
          <div v-if="activeDoc.counterparty" class="info-card-row"><span class="info-card-key">Continuity Steward</span><span class="info-card-val" style="color:var(--green)"><AegisIcon name="check" :size="10" /> {{ activeDoc.counterparty.name }}</span></div>
        </div>
        <div style="display:flex;gap:8px;justify-content:center">
          <button class="btn btn-outline" @click="toast.info('Downloading PDF...')"><AegisIcon name="download" :size="13" /> Download PDF</button>
          <button class="btn btn-primary" @click="closeModal('signSuccessModal')">Done</button>
        </div>
      </div>
    </AegisModal>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AppLayout     from '@/layouts/AppLayout.vue'
import AegisDropzone from '@/components/ui/AegisDropzone.vue'
import { useModal }    from '@/composables/useModal'
import { useToast }    from '@/composables/useToast'
import { useConfirm }  from '@/composables/useConfirm'
import PlanReviewAlert from '@/components/PlanReviewAlert.vue'
import { useVuelidate } from '@vuelidate/core'
import { required, helpers } from '@vuelidate/validators'

const props = defineProps({
  documents:          { type: Array,   default: () => [] },
  supportingDocs:     { type: Array,   default: () => [] },
  docStats:           { type: Object,  default: () => ({ total: 0, active: 0, pending: 0, expiring: 0 }) },
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

const providerName     = computed(() => page.props.auth?.user?.display_name || 'Provider')
const providerInitials = computed(() => page.props.auth?.user?.avatar_initials || 'P')

const activeTab    = ref('all')
const searchQ      = ref('')
const statusFilter = ref('')
const typeFilter   = ref('')
const activeDocId  = ref(null)
const activeDoc    = computed(() => props.documents.find(d => d.id === activeDocId.value) ?? null)

const signBusy      = ref(false)
const sendBusy      = ref(false)
const renewBusy     = ref(false)
const terminateBusy = ref(false)
const sendSigBusy   = ref(false)
const addDocBusy    = ref(false)
const exportBusy    = ref(false)
const amendBusy     = ref(false)
const revokeBusy    = ref(false)
const draftBusy     = ref(false)

const isSigned   = ref(false)
const signCheck1 = ref(false)
const signCheck2 = ref(false)

const tabs = computed(() => [
  { id: 'all',      label: 'All Documents',  count: props.documents.length },
  { id: 'pe',       label: 'Provider & CS',  count: props.documents.filter(d => d.tab_key === 'pe').length },
  { id: 'pd',       label: 'Provider & SS',  count: props.documents.filter(d => d.tab_key === 'pd').length },
  { id: 'de',       label: 'SS & CS',        count: props.documents.filter(d => d.tab_key === 'de').length },
  { id: 'tri',      label: 'Tri-Party',      count: props.documents.filter(d => d.tab_key === 'tri').length },
  { id: 'expiring', label: 'Expiring Soon',  count: props.docStats.expiring },
])

const filteredDocs = computed(() => props.documents.filter(d => {
  const tab = activeTab.value
  if (tab !== 'all' && tab !== 'expiring' && d.tab_key !== tab) return false
  if (tab === 'expiring' && d.status !== 'expiring') return false
  if (statusFilter.value && d.status !== statusFilter.value) return false
  if (typeFilter.value  && d.doc_type !== typeFilter.value)  return false
  if (searchQ.value) {
    const q = searchQ.value.toLowerCase()
    if (!d.title?.toLowerCase().includes(q) && !d.reference?.toLowerCase().includes(q)) return false
  }
  return true
}))

function docCardClass(doc) {
  const m = { active:'is-active', countersign_pending:'is-await', pending_sign:'is-await', expiring:'is-expiring', expired:'is-expired', terminated:'is-terminated', draft:'is-draft' }
  return m[doc.status] || 'is-active'
}

function openViewModal(doc)       { activeDocId.value = doc.id; openModal('viewAgreementModal') }
function openActionsModal(doc)    { activeDocId.value = doc.id; openModal('agreementActionsModal') }
function openSignModal(doc)       { activeDocId.value = doc.id; isSigned.value = false; signCheck1.value = false; signCheck2.value = false; openModal('signatureModal') }
function openRenewModal(doc)      { activeDocId.value = doc.id; openModal('renewalModal') }
function openSendForSigModal(doc) { activeDocId.value = doc.id; openModal('sendForSignatureModal') }
function openAmendModal()         { closeModal('viewAgreementModal'); openModal('amendmentModal') }

const wizStep        = ref(1)
const stepLabels     = [
  { short:'type',    line1:'Agreement', line2:'Type'      },
  { short:'parties', line1:'Parties &', line2:'Details'   },
  { short:'clauses', line1:'Clauses &', line2:'Terms'     },
  { short:'review',  line1:'Review &',  line2:'Confirm'   },
  { short:'sig',     line1:'Send for',  line2:'Signature' },
]
const stepSubs       = ['Select agreement type & category','Select parties and define the term','Configure all clauses and terms','Review the complete draft','Configure signing options and send']
const stepNextLabels = ['Continue: Parties & Details','Continue: Clauses & Terms','Continue: Review & Confirm','Continue: Send for Signature']

const wiz = reactive({
  category:'', docType:'', reference:'', partyBSearch:'', partyB:null,
  effectiveDate:'', expirationDate:'', autoRenew:'No - Manual renewal required',
  jurisdiction:'State of California, USA', disputeRes:'Aegis Platform Mediation first, then Arbitration',
})
const sig = reactive({ myAction:'Sign first, then send to other party', notifyMethod:'Email + Aegis In-App Notification', deadline:'', reminder:'Every 2 days after sending', message:'', c1:true, c2:true, c3:false, c4:false })

function openWizard()  { wizStep.value=1; wiz.category=''; wiz.docType=''; wiz.reference=''; wiz.partyB=null; openModal('newAgreementModal') }
function closeWizard() { closeModal('newAgreementModal') }
function wizardNext()  {
  if (wizStep.value === 1) {
    if (!wiz.category) { toast.warning('Please select an agreement category.'); return }
    if (!wiz.docType)  { toast.warning('Please select a document type.'); return }
  }
  wizStep.value++
}

const selectedCatTitle   = computed(() => agrCategories.find(c => c.value === wiz.category)?.title || '-')
const selectedPartyBName = computed(() => stewardOptions.value.find(p => p.id === wiz.partyB)?.name || '-')
const filteredPartyB     = computed(() => {
  const q = (wiz.partyBSearch || '').toLowerCase()
  return q ? stewardOptions.value.filter(p => p.name.toLowerCase().includes(q) || p.meta.toLowerCase().includes(q)) : stewardOptions.value
})
const stewardOptions = computed(() => props.stewards.length ? props.stewards : [
  { id:1, initials:'MC', name:'Marcus Chen',     meta:'Primary Continuity Steward - EX-0042 - Active' },
  { id:2, initials:'PR', name:'Dr. Priya Raman', meta:'Secondary Continuity Steward - EX-0051 - Active' },
  { id:3, initials:'LJ', name:'Linda Johnson',   meta:'Admin Support Steward - SS-0011 - Active' },
])

const agrCategories = [
  { value:'pe',  icon:'shield',                title:'Provider & Continuity Steward', sub:'MSA, SOW, NDA between you and a Continuity Steward' },
  { value:'pd',  icon:'phone',                 title:'Provider & Support Steward',    sub:'SLA, NDA between you and a Support Steward' },
  { value:'de',  icon:'arrow-right-arrow-left',title:'Team Agreements (Facilitated)', sub:'MOU between Support Steward and Continuity Steward - you are facilitator' },
  { value:'tri', icon:'users',                 title:'Tri-Party (All Three Roles)',   sub:'Single MSA or MOU binding Provider, Continuity Steward and Support Steward' },
]

const clauses = reactive([
  { id:1,num:1,title:'Scope of Services & Delegation of Authority',open:true, tagClass:'required',  tagLabel:'Required',  fields:[{label:'Authorized activities',type:'textarea',value:'',placeholder:'e.g. Continuity Steward is authorized to manage client referrals...'},{label:'Explicit exclusions',type:'textarea',value:'',placeholder:'e.g. Continuity Steward may not modify clinical treatment plans...'}] },
  { id:2,num:2,title:'Confidentiality & PHI Obligations (HIPAA)',  open:false,tagClass:'required',  tagLabel:'Required',  fields:[{label:'PHI Access Level',type:'select',value:'Read-Only',options:['Read-Only','Read/Write','No PHI Access']},{label:'Confidentiality Duration',type:'select',value:'Duration of agreement only',options:['Duration of agreement only','2 years post-termination','5 years post-termination','Perpetual']}] },
  { id:3,num:3,title:'Compensation & Fee Structure',               open:false,tagClass:'negotiable',tagLabel:'Negotiable',fields:[{label:'Model',type:'select',value:'Fixed Monthly Retainer',options:['Fixed Monthly Retainer','Hourly Rate','Per-Task Fee']},{label:'Amount / Rate',type:'text',value:'',placeholder:'e.g. $2,500/mo'},{label:'Payment Cycle',type:'select',value:'Monthly',options:['Monthly','Bi-Weekly','Weekly']}] },
  { id:4,num:4,title:'Termination & Exit Provisions',              open:false,tagClass:'standard',  tagLabel:'Standard',  fields:[{label:'Notice Period',type:'select',value:'30 days',options:['7 days','14 days','30 days','60 days']},{label:'Immediate Termination Grounds',type:'text',value:'HIPAA breach, fraud, gross negligence, loss of licensure',placeholder:''}] },
  { id:5,num:5,title:'Liability, Indemnification & Insurance',     open:false,tagClass:'required',  tagLabel:'Required',  fields:[{label:'Liability Cap',type:'select',value:'Capped at 3 months fees paid',options:['Capped at 3 months fees paid','Capped at total contract value','Capped at $1,000,000']},{label:'Insurance Requirement',type:'text',value:'Professional Liability min $1M / $3M aggregate',placeholder:''}] },
])

const libraryTemplates = [
  { icon:'shield',                title:'Provider & CS MSA',   sub:'Standard MSA - 8 clauses - HIPAA-ready',          tag:'Aegis' },
  { icon:'phone',                 title:'Provider & SS SLA',   sub:'SLA with KPIs and response time commitments',     tag:'Aegis' },
  { icon:'lock',                  title:'Mutual NDA',           sub:'Non-Disclosure - HIPAA BAA - 5yr post-term',      tag:'Aegis' },
  { icon:'arrow-right-arrow-left',title:'SS & CS MOU',          sub:'Coordination Protocol - Provider-overseen',       tag:'Aegis' },
  { icon:'users',                 title:'Tri-Party MSA',        sub:'Provider + CS + SS - Roles, scope, disputes',     tag:'Aegis' },
  { icon:'pencil',                title:'Statement of Work',    sub:'Project-specific scope for delegated activities', tag:'Custom'},
]

const actionItems = [
  { label:'View Full Agreement',  sub:'Read, print, or download PDF',        action:'view',      icon:'eye',            iconStyle:'',                                                          danger:false },
  { label:'Request Amendment',    sub:'Propose changes for mutual approval',  action:'amendment', icon:'pencil',         iconStyle:'',                                                          danger:false },
  { label:'Renew Agreement',      sub:'Extend or update before expiry',       action:'renew',     icon:'refresh-cw',     iconStyle:'',                                                          danger:false },
  { label:'Attach BAA',           sub:'Add Business Associate Agreement',     action:'baa',       icon:'shield',         iconStyle:'',                                                          danger:false },
  { label:'Configure NDA',        sub:'Attach or update NDA addendum',        action:'nda',       icon:'lock',           iconStyle:'',                                                          danger:false },
  { label:'Download PDF',         sub:'Save signed copy to your device',      action:'download',  icon:'download',       iconStyle:'',                                                          danger:false },
  { label:'Revoke Access',        sub:'Remove party access immediately',      action:'revoke',    icon:'bell',           iconStyle:'background:var(--orange-light);color:var(--orange-dark)',  danger:true  },
  { label:'Terminate Agreement',  sub:'Revoke and archive this agreement',    action:'terminate', icon:'x-circle',       iconStyle:'background:var(--red-light);color:var(--red-dark)',        danger:true  },
]

function handleAction(action) {
  closeModal('agreementActionsModal')
  setTimeout(() => {
    if (action === 'download') { toast.info('Downloading PDF...'); return }
    const map = { view:'viewAgreementModal', amendment:'amendmentModal', renew:'renewalModal', baa:'baaModal', nda:'ndaAttachModal', revoke:'accessRevocationModal', terminate:'terminateModal' }
    if (map[action]) openModal(map[action])
  }, 50)
}

const renewForm     = reactive({ type:'same', effectiveDate:'', expiryDate:'' })
const terminateForm = reactive({ reason:'', date:'', confirm:'' })
const sendSigForm   = reactive({ deadline:'', message:'' })
const addDocForm    = reactive({ name:'', type:'Supporting Document', relatedTo:'', notes:'' })
const exportForm    = reactive({ format:'PDF - Individual signed PDFs (ZIP)', scope:'All documents' })
const amendForm     = reactive({ type:'', currentLang:'', proposed:'', reason:'', effectiveDate:'' })
const revokeForm    = reactive({ reason:'', confirm:'' })
const draftForm     = reactive({ name:'', notes:'' })
const ndaForm       = reactive({ duration:'5 years post-termination', type:'Mutual (both parties)', exceptions:'' })
const ndaItems      = reactive([
  { label:'Client Records & PHI',               checked:true  },
  { label:'Financial & Billing Data',           checked:true  },
  { label:'Referral Protocols & Care Pathways', checked:true  },
  { label:'Business Strategy & Trade Secrets',  checked:false },
  { label:'Staff & Vendor Information',         checked:false },
])
const addDocFiles = ref([])
const amendFiles  = ref([])

const rules = computed(() => ({
  wiz_docType:    { required: helpers.withMessage('Document type is required.', required) },
  term_reason:    { required: helpers.withMessage('Please select a reason.', required) },
  term_confirm:   { required: helpers.withMessage('Type TERMINATE to confirm.', required) },
  amend_type:     { required: helpers.withMessage('Amendment type is required.', required) },
  amend_proposed: { required: helpers.withMessage('Proposed change is required.', required) },
  revoke_reason:  { required: helpers.withMessage('Please select a reason.', required) },
  revoke_confirm: { required: helpers.withMessage('Type REVOKE to confirm.', required) },
  addDoc_name:    { required: helpers.withMessage('Document name is required.', required) },
}))

const vModel = reactive({
  wiz_docType:    computed({ get: () => wiz.docType,            set: v => { wiz.docType = v } }),
  term_reason:    computed({ get: () => terminateForm.reason,   set: v => { terminateForm.reason = v } }),
  term_confirm:   computed({ get: () => terminateForm.confirm,  set: v => { terminateForm.confirm = v } }),
  amend_type:     computed({ get: () => amendForm.type,         set: v => { amendForm.type = v } }),
  amend_proposed: computed({ get: () => amendForm.proposed,     set: v => { amendForm.proposed = v } }),
  revoke_reason:  computed({ get: () => revokeForm.reason,      set: v => { revokeForm.reason = v } }),
  revoke_confirm: computed({ get: () => revokeForm.confirm,     set: v => { revokeForm.confirm = v } }),
  addDoc_name:    computed({ get: () => addDocForm.name,        set: v => { addDocForm.name = v } }),
})

const v$ = useVuelidate(rules, vModel, { $scope: false })
function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message
  return null
}

function closeTerminate() { closeModal('terminateModal'); terminateForm.reason=''; terminateForm.confirm=''; v$.value.$reset() }
function closeAmend()     { closeModal('amendmentModal'); amendForm.type=''; amendForm.proposed=''; amendFiles.value=[]; v$.value.$reset() }
function closeRevoke()    { closeModal('accessRevocationModal'); revokeForm.reason=''; revokeForm.confirm=''; v$.value.$reset() }
function closeAddDoc()    { closeModal('addDocumentModal'); addDocForm.name=''; addDocFiles.value=[]; v$.value.$reset() }

function applySignature()    { isSigned.value = true }
function finalizeSignature() {
  if (!isSigned.value) { toast.warning('Please click the signature area to apply your signature first.'); return }
  signBusy.value = true
  router.post(route('provider.documents.sign', { document: activeDocId.value }), {}, {
    preserveScroll: true,
    onSuccess: () => { closeModal('signatureModal'); isSigned.value = false; openModal('signSuccessModal') },
    onError:   () => toast.error('Could not save signature.'),
    onFinish:  () => { signBusy.value = false },
  })
}

function sendForSignature() {
  sendBusy.value = true
  router.post(route('provider.documents.request'), {
    category:wiz.category, doc_type:wiz.docType, reference:wiz.reference,
    party_b_id:wiz.partyB, effective_date:wiz.effectiveDate, expiry_date:wiz.expirationDate,
    my_action:sig.myAction, notify_method:sig.notifyMethod, deadline:sig.deadline, message:sig.message,
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Sent successfully - counterparts notified.'); closeWizard() },
    onError:   () => toast.error('Could not send agreement.'),
    onFinish:  () => { sendBusy.value = false },
  })
}

function submitRenew() {
  renewBusy.value = true
  router.post(route('provider.documents.remind', { document: activeDocId.value }), { type:renewForm.type, effective_date:renewForm.effectiveDate, expiry_date:renewForm.expiryDate }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Renewal initiated.'); closeModal('renewalModal') },
    onError:   () => toast.error('Could not initiate renewal.'),
    onFinish:  () => { renewBusy.value = false },
  })
}

async function submitTerminate() {
  const valid = await v$.value.$validate()
  if (!valid || terminateForm.confirm !== 'TERMINATE') { toast.error('Please complete the form.'); return }
  terminateBusy.value = true
  router.post(route('provider.documents.archive', { document: activeDocId.value }), { reason:terminateForm.reason, term_date:terminateForm.date }, {
    preserveScroll: true,
    onSuccess: () => { toast.warning('Agreement terminated.'); closeTerminate() },
    onError:   () => toast.error('Could not terminate agreement.'),
    onFinish:  () => { terminateBusy.value = false },
  })
}

function submitSendForSig() {
  sendSigBusy.value = true
  router.post(route('provider.documents.remind', { document: activeDocId.value }), { message:sendSigForm.message, deadline:sendSigForm.deadline }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Sent successfully.'); closeModal('sendForSignatureModal') },
    onError:   () => toast.error('Could not send.'),
    onFinish:  () => { sendSigBusy.value = false },
  })
}

async function submitAddDoc() {
  const valid = await v$.value.$validate()
  if (!valid) { toast.error('Please fix the highlighted fields.'); return }
  addDocBusy.value = true
  router.post(route('provider.documents.upload'), { name:addDocForm.name, type:addDocForm.type, related_to:addDocForm.relatedTo, notes:addDocForm.notes }, {
    forceFormData:true, preserveScroll:true,
    onSuccess: () => { toast.success('Document added.'); closeAddDoc() },
    onError:   () => toast.error('Could not upload document.'),
    onFinish:  () => { addDocBusy.value = false },
  })
}

function submitExport() {
  exportBusy.value = true
  setTimeout(() => { exportBusy.value=false; closeModal('exportModal'); toast.info('Export started - check your downloads.') }, 1200)
}

async function submitAmend() {
  const valid = await v$.value.$validate()
  if (!valid) { toast.error('Please fix the highlighted fields.'); return }
  amendBusy.value = true
  router.post(route('provider.documents.request'), { parent_id:activeDocId.value, type:amendForm.type, proposed:amendForm.proposed, reason:amendForm.reason, effective_date:amendForm.effectiveDate }, {
    preserveScroll:true,
    onSuccess: () => { toast.success('Amendment request sent.'); closeAmend() },
    onError:   () => toast.error('Could not send amendment request.'),
    onFinish:  () => { amendBusy.value = false },
  })
}

async function submitRevoke() {
  const valid = await v$.value.$validate()
  if (!valid || revokeForm.confirm !== 'REVOKE') { toast.error('Please complete the form.'); return }
  revokeBusy.value = true
  router.post(route('provider.documents.archive', { document: activeDocId.value }), { reason:revokeForm.reason }, {
    preserveScroll:true,
    onSuccess: () => { toast.warning('Access revoked. Incident logged.'); closeRevoke() },
    onError:   () => toast.error('Could not revoke access.'),
    onFinish:  () => { revokeBusy.value = false },
  })
}

function submitSaveDraft() {
  draftBusy.value = true
  router.post(route('provider.documents.request'), { category:wiz.category, doc_type:wiz.docType, reference:wiz.reference||draftForm.name, notes:draftForm.notes, is_draft:true }, {
    preserveScroll:true,
    onSuccess: () => { toast.info('Draft saved.'); closeModal('draftSaveModal'); closeWizard() },
    onError:   () => toast.error('Could not save draft.'),
    onFinish:  () => { draftBusy.value = false },
  })
}

function deleteDraft(doc) {
  router.delete(route('provider.documents.destroy', { document: doc.id }), {
    preserveScroll:true,
    onSuccess: () => toast.info('Draft deleted.'),
    onError:   () => toast.error('Could not delete draft.'),
  })
}
</script>

<style scoped>
.doc-section-head  { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--border); flex-wrap:wrap; gap:12px; }
.doc-section-title { font-family:var(--font-serif); font-size:16px; font-weight:700; color:var(--text); }
.doc-section-sub   { font-size:12px; color:var(--text-3); margin-top:2px; }
.doc-file-icon     { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
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
.workflow-stepper { display:flex; align-items:flex-start; gap:0; overflow-x:auto; scrollbar-width:none; padding:4px 0; }
.workflow-stepper::-webkit-scrollbar { display:none; }
.wf-step { display:flex; flex-direction:column; align-items:center; flex:1; min-width:96px; position:relative; }
.wf-step:not(:last-child)::after { content:''; position:absolute; top:16px; left:50%; right:-50%; height:2px; background:var(--border); z-index:0; }
.wf-step.done:not(:last-child)::after,.wf-step.current:not(:last-child)::after { background:var(--gold-dark); }
.wf-node { width:32px; height:32px; border-radius:var(--radius-full); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; position:relative; z-index:1; border:1px solid var(--border); background:var(--surface); color:var(--text-4); transition:all var(--transition); }
.wf-step.done .wf-node,.wf-step.current .wf-node { background:var(--gold-dark); border-color:var(--gold-dark); color:var(--text-inverted); }
.wf-step.future .wf-node { background:var(--surface-2); }
.wf-label { font-size:11px; font-weight:700; color:var(--text-4); margin-top:8px; text-align:center; line-height:1.3; }
.wf-step.done .wf-label,.wf-step.current .wf-label { color:var(--gold-dark); }
.party-grid       { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
.party-col        { border:1px solid var(--border); border-radius:var(--radius-lg); padding:14px; position:relative; overflow:hidden; background:var(--surface); }
.party-col::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.party-col.provider::before { background:var(--gold-dark); }
.party-col.cs::before       { background:var(--purple-dark); }
.party-col.ss::before       { background:var(--green-dark); }
.party-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:6px; color:var(--text-4); }
.party-name  { font-size:14px; font-weight:700; color:var(--text); }
.party-sig   { margin-top:10px; padding-top:10px; border-top:1px dashed var(--border); }
.sig-name-display { font-family:var(--font-serif); font-size:18px; font-style:italic; color:var(--text); letter-spacing:0.4px; margin:4px 0; }
.party-search-result { border:1px solid var(--border); border-radius:var(--radius-sm); padding:11px 14px; margin-bottom:6px; cursor:pointer; transition:background var(--transition),border-color var(--transition); display:flex; align-items:center; gap:12px; background:var(--surface); font-size:13px; }
.party-search-result:hover    { background:var(--surface-2); border-color:var(--gold-dark); }
.party-search-result.selected { background:var(--badge-bg-gold); border-color:var(--gold-dark); }
.party-search-result.selected .party-name-sm { color:var(--gold-dark); }
.ren-opt.selected { background:var(--badge-bg-gold); border-color:var(--gold-dark); }
.party-avatar-sm { width:36px; height:36px; border-radius:var(--radius-full); background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:13px; flex-shrink:0; }
.party-info-sm   { flex:1; min-width:0; }
.party-name-sm   { font-size:13px; font-weight:700; color:var(--text); }
.party-meta-sm   { font-size:12px; color:var(--text-3); }
.legal-doc       { border:1px solid var(--border); border-radius:var(--radius); background:var(--surface-2); padding:20px 22px; font-size:13px; color:var(--text-2); line-height:1.7; max-height:320px; overflow-y:auto; }
.legal-doc-title { font-family:var(--font-serif); font-size:17px; font-weight:700; color:var(--text); text-align:center; margin-bottom:4px; }
.legal-doc-sub   { text-align:center; font-size:11px; color:var(--text-4); margin-bottom:18px; font-weight:600; }
.legal-doc h4    { font-family:var(--font-serif); font-size:14px; font-weight:700; color:var(--text); margin:14px 0 6px; }
.legal-doc p     { margin-bottom:10px; }
.info-card           { border:1px solid var(--border); border-radius:var(--radius); padding:14px 16px; font-size:13px; background:var(--surface); }
.info-card-title     { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; color:var(--text-4); margin-bottom:8px; }
.info-card-row       { display:flex; justify-content:space-between; align-items:center; padding:5px 0; border-bottom:1px solid var(--surface-3); font-size:12px; }
.info-card-row:last-child { border-bottom:none; }
.info-card-key { color:var(--text-3); font-weight:600; }
.info-card-val { font-weight:700; color:var(--text); display:inline-flex; align-items:center; gap:4px; }
.review-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
.sign-area        { border:2px dashed var(--border); border-radius:var(--radius); min-height:100px; padding:12px; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; transition:border-color var(--transition),background var(--transition); color:var(--text-4); font-size:13px; gap:6px; }
.sign-area:hover  { border-color:var(--soft-gold); background:var(--badge-bg-gold); }
.sign-area.signed { border-style:solid; border-color:var(--green-dark); background:var(--green-light); color:var(--green-dark); }
.review-item            { display:flex; align-items:flex-start; gap:12px; padding:11px 0; border-bottom:1px solid var(--border); }
.review-item:last-child { border-bottom:none; }
.review-check-icon      { flex-shrink:0; margin-top:1px; }
.review-label           { font-size:13px; font-weight:700; color:var(--text); }
.list-item       { border:1px solid var(--border); border-radius:var(--radius-sm); padding:11px 14px; margin-bottom:6px; cursor:pointer; transition:background var(--transition),border-color var(--transition); display:flex; align-items:center; gap:12px; background:var(--surface); font-size:13px; }
.list-item:hover { background:var(--surface-2); border-color:var(--gold-dark); }
.list-item-icon    { width:32px; height:32px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.list-item-content { flex:1; min-width:0; }
.list-item-title   { font-size:13px; font-weight:700; color:var(--text); }
.list-item-desc    { font-size:12px; color:var(--text-3); margin-top:1px; }
.agr-cat-grid   { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:4px; }
.agr-cat-card   { display:flex; align-items:flex-start; gap:10px; padding:12px 14px; border:1px solid var(--border); border-radius:var(--radius-sm); cursor:pointer; transition:border-color var(--transition),background var(--transition); }
.agr-cat-card:hover    { border-color:var(--gold-dark); }
.agr-cat-card.selected { border-color:var(--gold-dark); background:var(--badge-bg-gold); }
.agr-cat-avatar { width:36px; height:36px; border-radius:var(--radius-full); background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.agr-cat-title  { font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px; }
.agr-cat-sub    { font-size:11px; color:var(--text-4); line-height:1.4; }
.sig-check-row   { display:flex; align-items:center; gap:10px; cursor:pointer; border-bottom:1px solid var(--border); }
.sig-check-label { font-size:13px; color:var(--text-2); }
@media (max-width:900px) {
  .party-grid, .review-grid-3, .agr-cat-grid { grid-template-columns:1fr; }
}
</style>
