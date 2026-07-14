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

    <!-- ACTION REQUIRED ALERT -->
    <div v-if="docStats.pending > 0" class="alert alert-gold" style="margin-bottom:18px">
      <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
      <div class="alert-content">
        <div class="alert-title">{{ docStats.pending }} Agreement{{ docStats.pending !== 1 ? 's' : '' }} Require Your Action</div>
        <div>Review agreements below that are pending your signature or need renewal.</div>
        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
          <button class="btn btn-primary" @click="activeTab = 'pending_sign'">
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
        <option value="CSA">CS Engagement Agreement</option>
        <option value="FA">Fee Amendment</option>
        <option value="BAA">BAA</option>
        <option value="OTHER">Other</option>
      </select>
      <button class="btn btn-outline btn-sm" style="margin-left:auto" @click="openModal('exportModal')">
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
          <a :href="route('provider.plan.index')" class="btn btn-outline btn-sm">Open Plan Builder</a>
          <button class="btn btn-outline btn-sm" @click="openModal('addDocumentModal')">
            <AegisIcon name="upload" :size="13" /> Add Document
          </button>
          <button class="btn btn-primary btn-sm" @click="openWizard">
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
                  <button class="btn btn-primary btn-sm" :disabled="signBusy" @click="openSignModal(doc)">
                    <AegisIcon v-if="signBusy" name="refresh-cw" :size="13" class="btn-spin" />
                    <AegisIcon v-else name="signature" :size="13" />
                    {{ signBusy ? 'Signing...' : 'Sign' }}
                  </button>
                  <button class="btn-icon" data-tooltip="View" @click="openViewModal(doc)"><AegisIcon name="eye" :size="14" /></button>
                </template>
                <template v-else-if="doc.primary_action === 'renew'">
                  <button class="btn-icon" data-tooltip="View" @click="openViewModal(doc)"><AegisIcon name="eye" :size="14" /></button>
                </template>
                <template v-else-if="doc.primary_action === 'edit'">
                  <button class="btn btn-outline btn-sm" @click="toast.info('Opening draft editor...')">
                    <AegisIcon name="pencil" :size="13" /> Edit
                  </button>
                  <button class="btn-icon" data-tooltip="Send for signature" @click="openSendForSigModal(doc)"><AegisIcon name="send" :size="14" /></button>
                  <button
                    class="btn-icon btn-icon-danger"
                    data-tooltip="Delete draft"
                    @click="confirmAction('Delete this draft? This action cannot be undone.', () => deleteDraft(doc), { title: 'Delete Draft', btnLabel: 'Delete', type: 'danger' })"
                  ><AegisIcon name="trash" :size="14" /></button>
                </template>
                <template v-else>
                  <button class="btn-icon" data-tooltip="View" @click="openViewModal(doc)"><AegisIcon name="eye" :size="14" /></button>
                  <button class="btn-icon" data-tooltip="Download PDF" @click="toast.info('Downloading PDF...')"><AegisIcon name="download" :size="14" /></button>
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
        <button class="btn btn-outline btn-sm" @click="openModal('addDocumentModal')">
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

    <!-- MODAL 1: NEW AGREEMENT WIZARD -->
    <AegisModal
      :model-value="isOpen('newAgreementModal').value"
      title="Create New Agreement"
      size="xl"
      @update:model-value="v => !v && closeWizard()"
    >
      <template #subtitle>
        <div>Step {{ wizStep }} of 5 - {{ stepSubs[wizStep - 1] }}</div>
        <div class="workflow-stepper" style="margin:14px 0 0">
          <div
            v-for="(s, i) in stepLabels"
            :key="s.short"
            class="wf-step"
            :class="{ done: i < wizStep - 1, current: i === wizStep - 1, future: i > wizStep - 1 }"
            style="cursor:pointer"
            @click="i < wizStep - 1 ? wizStep = i + 1 : null"
          >
            <div class="wf-node">
              <AegisIcon v-if="i < wizStep - 1" name="check" :size="14" />
              <span v-else>{{ i + 1 }}</span>
            </div>
            <div class="wf-label">{{ s.line1 }}<br>{{ s.line2 }}</div>
          </div>
        </div>
      </template>

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
              <option value="CSA">CS Engagement Agreement</option>
              <option value="FA">Fee Amendment</option>
              <option value="BAA">BAA</option>
              <option value="OTHER">Other</option>
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
              <div class="party-info-sm"><div class="party-name-sm">{{ providerName }}</div><div class="party-meta-sm">Primary Care · Active</div></div>
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
        <div v-if="wiz.partyB" class="alert alert-warning" style="margin-bottom:14px">
          <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
          <div class="alert-content"><strong>Note:</strong> An active agreement of this type already exists with this party.</div>
        </div>
        <hr class="divider">
        <div class="modal-section-label">Terms &amp; Duration</div>
        <div class="form-row is-3col">
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
            <select class="form-select" v-model="wiz.dispute">
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
          <AegisIcon v-if="sendBusy" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="send" :size="13" />
          {{ sendBusy ? 'Sending...' : 'Send for Signature' }}
        </button>
      </template>
    </AegisModal>

    <!-- MODAL 2: SIGNATURE -->
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
                  <span style="font-size:11px"><AegisIcon name="check" :size="11" /> Verified signature on file</span>
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
          <AegisIcon v-if="signBusy" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="signature" :size="13" />
          {{ signBusy ? 'Signing...' : 'Apply Signature &amp; Execute' }}
        </button>
      </template>
    </AegisModal>

    <!-- MODAL 3: VIEW AGREEMENT -->
    <AegisModal
      :model-value="isOpen('viewAgreementModal').value"
      :title="activeDoc ? (activeDoc.reference + ' ' + activeDoc.title) : 'View Agreement'"
      size="xl"
      @update:model-value="v => !v && closeModal('viewAgreementModal')"
    >
      <div v-if="activeDoc">
        <div style="display:flex;gap:12px;margin-bottom:14px;flex-wrap:wrap">
          <div class="info-card" style="flex:1;min-width:180px">
            <div class="info-card-title">Agreement Details</div>
            <div class="info-card-row"><span class="info-card-key">Reference</span><span class="info-card-val">{{ activeDoc.reference }}</span></div>
            <div class="info-card-row"><span class="info-card-key">Type</span><span class="info-card-val">{{ activeDoc.doc_type_label }}</span></div>
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

      </template>
    </AegisModal>

    <!-- MODAL 7: SEND FOR SIGNATURE -->
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
          <AegisIcon v-if="sendSigBusy" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="send" :size="13" />
          {{ sendSigBusy ? 'Sending...' : 'Send for Signature' }}
        </button>
      </template>
    </AegisModal>

    <!-- MODAL 8: TEMPLATES -->
    <AegisModal
      :model-value="isOpen('templateModal').value"
      title="Sample Templates"
      size="lg"
      @update:model-value="v => !v && closeModal('templateModal')"
    >
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

    <!-- MODAL 9: EXPORT -->
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
        </select>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeModal('exportModal')">Cancel</button>
        <button class="btn btn-primary" style="margin-left:auto" :disabled="exportBusy" @click="submitExport">
          <AegisIcon v-if="exportBusy" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="download" :size="13" />
          {{ exportBusy ? 'Exporting...' : 'Export' }}
        </button>
      </template>
    </AegisModal>

    <!-- MODAL 10: ADD DOCUMENT -->
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
          <AegisIcon v-if="addDocBusy" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="upload" :size="13" />
          {{ addDocBusy ? 'Uploading...' : 'Add Document' }}
        </button>
      </template>
    </AegisModal>

    <!-- MODAL 11: AMENDMENT REQUEST -->
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
          <option value="">Select Type</option>
          <option>Compensation Adjustment</option>
          <option>Scope of Services Change</option>
          <option>Term Extension</option>
          <option>PHI Access Level Change</option>
          <option>Termination Clause Update</option>
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
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="closeAmend">Cancel</button>
        <button class="btn btn-primary" style="margin-left:auto" :disabled="amendBusy" @click="submitAmend">
          <AegisIcon v-if="amendBusy" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="send" :size="13" />
          {{ amendBusy ? 'Sending...' : 'Send Amendment Request' }}
        </button>
      </template>
    </AegisModal>

    <!-- MODAL 12: BAA PREVIEW -->
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
        <p>"Business Associate" means the party receiving PHI access. "Covered Entity" means the Provider. "PHI" means Protected Health Information as defined under 45 CFR § 160.103.</p>
        <h4>2. Obligations of Business Associate</h4>
        <p>Business Associate shall not use or disclose PHI other than as permitted by this agreement or as required by law. Business Associate shall implement appropriate safeguards and comply with Subpart C of 45 CFR Part 164. Any unauthorized use or disclosure shall be reported within 5 business days of discovery.</p>
        <h4>3. Permitted Uses and Disclosures</h4>
        <p>Business Associate may use PHI only for purposes specified in the parent agreement Scope of Services.</p>
        <h4>4. Breach Notification</h4>
        <p>Business Associate shall notify Covered Entity of any Breach of Unsecured PHI without unreasonable delay and no later than 60 calendar days after discovery, per 45 CFR § 164.410.</p>
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

    <!-- MODAL 13: ACCESS REVOCATION -->
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
      <div class="form-group">
        <label class="form-label" for="revokeReason">Reason <span class="required">*</span></label>
        <select id="revokeReason" class="form-select" v-model="revokeForm.reason" :class="{ 'is-error': fieldError('revoke_reason') }" @blur="v$.revoke_reason.$touch()">
          <option value="">Select Reason</option>
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
          <AegisIcon v-if="revokeBusy" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="lock" :size="13" />
          {{ revokeBusy ? 'Revoking...' : 'Confirm Revocation' }}
        </button>
      </template>
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
import { useVuelidate } from '@vuelidate/core'
import { required, helpers } from '@vuelidate/validators'

// Props
const props = defineProps({
  documents:     { type: Array,  default: () => [] },
  supportingDocs:{ type: Array,  default: () => [] },
  docStats:      { type: Object, default: () => ({ total: 0, active: 0, pending: 0, expiring: 0 }) },
  stewards:      { type: Array,  default: () => [] },
})

// Composables
const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()
const { confirmAction } = useConfirm()
const page = usePage()

// Auth
const providerName     = computed(() => page.props.auth?.user?.display_name || 'Provider')
const providerInitials = computed(() => page.props.auth?.user?.avatar_initials || 'P')

// UI state
const activeTab    = ref('pending_sign')
const searchQ      = ref('')
const statusFilter = ref('')
const typeFilter   = ref('')
const activeDocId  = ref(null)
const activeDoc    = computed(() => props.documents.find(d => d.id === activeDocId.value) ?? null)

// Busy refs
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

// Signature
const isSigned   = ref(false)
const signCheck1 = ref(false)
const signCheck2 = ref(false)

// Tabs
const tabs = computed(() => [
  { id: 'pending_sign',       label: 'Pending My Signature', count: props.documents.filter(d => d.status === 'pending_sign').length },
  { id: 'countersign_pending',label: 'Awaiting Others',      count: props.documents.filter(d => d.status === 'countersign_pending').length },
  { id: 'fully_executed',     label: 'Fully Executed',       count: props.documents.filter(d => d.status === 'fully_executed' || d.status === 'active').length },
])

const filteredDocs = computed(() => props.documents.filter(d => {
  const tab = activeTab.value
  if (tab === 'pending_sign' && d.status !== 'pending_sign') return false
  if (tab === 'countersign_pending' && d.status !== 'countersign_pending') return false
  if (tab === 'fully_executed' && d.status !== 'fully_executed' && d.status !== 'active') return false
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

// Open helpers
function openViewModal(doc)       { activeDocId.value = doc.id; openModal('viewAgreementModal') }
function openActionsModal(doc)    { activeDocId.value = doc.id; openModal('agreementActionsModal') }
function openSignModal(doc)       { activeDocId.value = doc.id; isSigned.value = false; signCheck1.value = false; signCheck2.value = false; openModal('signatureModal') }
function openRenewModal(doc)      { activeDocId.value = doc.id; openModal('renewalModal') }
function openSendForSigModal(doc) { activeDocId.value = doc.id; openModal('sendForSignatureModal') }
function openAmendModal()         { closeModal('viewAgreementModal'); openModal('amendmentModal') }

// Wizard
const wizStep = ref(1)
const stepLabels     = [ { short:'type',    line1:'Agreement', line2:'Type'      }, { short:'parties', line1:'Parties &', line2:'Details'   }, { short:'clauses', line1:'Clauses &', line2:'Terms'     }, { short:'review',  line1:'Review &',  line2:'Confirm'   }, { short:'sig',     line1:'Send for',  line2:'Signature' } ]
const stepSubs       = [ 'Select agreement type & category', 'Select parties and define the term', 'Configure all clauses and terms', 'Review the complete draft', 'Configure signing options and send' ]
const stepNextLabels = [ 'Continue: Parties & Details', 'Continue: Clauses & Terms', 'Continue: Review & Confirm', 'Continue: Send for Signature' ]

const wiz = reactive({ category:'', docType:'', reference:'', partyBSearch:'', partyB:null, effectiveDate:'', expirationDate:'', autoRenew:'No - Manual renewal required', jurisdiction:'State of California, USA', dispute:'Aegis Platform Mediation first, then Arbitration' })
const sig = reactive({ myAction:'Sign first, then send to other party', notifyMethod:'Email + Aegis In-App Notification', deadline:'', reminder:'Every 2 days after sending', message:'', c1:true, c2:true, c3:false, c4:false })

function openWizard() { wizStep.value=1; wiz.category=''; wiz.docType=''; wiz.reference=''; wiz.partyB=null; openModal('newAgreementModal') }
function closeWizard() { closeModal('newAgreementModal') }
function wizardNext() {
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
  { id:1, initials:'MC', name:'Marcus Chen',     meta:'Primary Continuity Steward - Active' },
  { id:2, initials:'PR', name:'Dr. Priya Raman', meta:'Secondary Continuity Steward - Active' },
  { id:3, initials:'LJ', name:'Linda Johnson',   meta:'Admin Support Steward - Active' },
])

const agrCategories = [
  { value:'pe',  icon:'shield',                title:'Provider & Continuity Steward', sub:'MSA, SOW, NDA between you and a Continuity Steward' },
  { value:'pd',  icon:'phone',                 title:'Provider & Support Steward',    sub:'SLA, NDA between you and a Support Steward' },
  { value:'de',  icon:'arrow-right-arrow-left',title:'Team Agreements (Facilitated)', sub:'MOU between SS and CS - you are facilitator' },
  { value:'tri', icon:'users',                 title:'Tri-Party (All Three Roles)',   sub:'Single MSA or MOU binding Provider, CS and SS' },
]

const clauses = reactive([
  { id:1,num:1,title:'Scope of Services & Delegation of Authority',open:true, tagClass:'required',  tagLabel:'Required',  fields:[{label:'Authorized activities',type:'textarea',value:'',placeholder:'e.g. Continuity Steward is authorized to manage client referrals...'},{label:'Explicit exclusions',type:'textarea',value:'',placeholder:'e.g. Continuity Steward may not modify clinical treatment plans...'}] },
  { id:2,num:2,title:'Confidentiality & PHI Obligations (HIPAA)',  open:false,tagClass:'required',  tagLabel:'Required',  fields:[{label:'PHI Access Level',type:'select',value:'Read-Only',options:['Read-Only','Read/Write','No PHI Access']},{label:'Confidentiality Duration',type:'select',value:'Duration of agreement only',options:['Duration of agreement only','2 years post-termination','5 years post-termination','Perpetual']}] },
  { id:3,num:3,title:'Compensation & Fee Structure',               open:false,tagClass:'negotiable',tagLabel:'Negotiable',fields:[{label:'Model',type:'select',value:'Fixed Monthly Retainer',options:['Fixed Monthly Retainer','Hourly Rate','Per-Task Fee']},{label:'Amount / Rate',type:'text',value:'',placeholder:'e.g. $2,500/mo'},{label:'Payment Cycle',type:'select',value:'Monthly',options:['Monthly','Bi-Weekly','Weekly']}] },
  { id:4,num:4,title:'Termination & Exit Provisions',              open:false,tagClass:'standard',  tagLabel:'Standard',  fields:[{label:'Notice Period',type:'select',value:'30 days',options:['7 days','14 days','30 days','60 days']},{label:'Immediate Termination Grounds',type:'text',value:'HIPAA breach, fraud, gross negligence, loss of licensure',placeholder:''}] },
  { id:5,num:5,title:'Liability, Indemnification & Insurance',     open:false,tagClass:'required',  tagLabel:'Required',  fields:[{label:'Liability Cap',type:'select',value:'Capped at 3 months fees paid',options:['Capped at 3 months fees paid','Capped at total contract value','Capped at $1,000,000']},{label:'Insurance Requirement',type:'text',value:'Professional Liability min $1M / $3M aggregate',placeholder:''}] },
])

const libraryTemplates = [
  { icon:'shield',                title:'Provider & Continuity Steward MSA', sub:'Standard Master Service Agreement - 8 clauses - HIPAA-ready', tag:'Aegis' },
  { icon:'phone',                 title:'Provider & Support Steward SLA',    sub:'Service Level Agreement with KPIs and response time SLAs',    tag:'Aegis' },
  { icon:'lock',                  title:'Mutual NDA',                         sub:'Non-Disclosure - HIPAA BAA - 2yr or 5yr post-termination',    tag:'Aegis' },
  { icon:'arrow-right-arrow-left',title:'SS & CS MOU',                        sub:'Coordination Protocol - Escalation paths - Provider-overseen', tag:'Aegis' },
  { icon:'users',                 title:'Tri-Party MSA',                      sub:'Provider + CS + SS - Roles, scope, dispute resolution',       tag:'Aegis' },
  { icon:'pencil',                title:'Statement of Work (SOW)',             sub:'Project-specific scope for delegated activities',             tag:'Custom'},
]

const actionItems = [
  { label:'View Full Agreement',  sub:'Read, print, or download PDF',          action:'view',      icon:'eye',            iconStyle:'',                                                         danger:false },
  { label:'Request Amendment',    sub:'Propose changes for mutual approval',    action:'amendment', icon:'pencil',         iconStyle:'',                                                         danger:false },
  { label:'Renew Agreement',      sub:'Extend or update before expiry',         action:'renew',     icon:'refresh',        iconStyle:'',                                                         danger:false },
  { label:'Attach BAA',           sub:'Add Business Associate Agreement',       action:'baa',       icon:'shield',         iconStyle:'',                                                         danger:false },
  { label:'Download PDF',         sub:'Save signed copy to your device',        action:'download',  icon:'download',       iconStyle:'',                                                         danger:false },
  { label:'Raise a Dispute',      sub:'Flag a clause or compliance issue',      action:'dispute',   icon:'alert-triangle', iconStyle:'background:var(--orange-light);color:var(--orange-dark)', danger:true  },
  { label:'Revoke Access',        sub:'Remove party access immediately',        action:'revoke',    icon:'bell',           iconStyle:'background:var(--orange-light);color:var(--orange-dark)', danger:true  },
  { label:'Terminate Agreement',  sub:'Revoke and archive this agreement',      action:'terminate', icon:'x-circle',       iconStyle:'background:var(--red-light);color:var(--red-dark)',       danger:true  },
]

function handleAction(action) {
  closeModal('agreementActionsModal')
  setTimeout(() => {
    if (action === 'download') { toast.info('Downloading PDF...'); return }
    const map = { view:'viewAgreementModal', amendment:'amendmentModal', renew:'renewalModal', baa:'baaModal', revoke:'accessRevocationModal', terminate:'terminateModal' }
    if (map[action]) openModal(map[action])
    if (action === 'dispute') toast.warning('Dispute reporting is handled in Finances.')
  }, 50)
}

// Form objects
const renewForm     = reactive({ type:'same', effectiveDate:'', expiryDate:'' })
const terminateForm = reactive({ reason:'', date:'', confirm:'' })
const sendSigForm   = reactive({ deadline:'', message:'' })
const addDocForm    = reactive({ name:'', type:'Supporting Document', relatedTo:'', notes:'' })
const exportForm    = reactive({ format:'PDF - Individual signed PDFs (ZIP)', scope:'All documents' })
const amendForm     = reactive({ type:'', currentLang:'', proposed:'', reason:'', effectiveDate:'' })
const revokeForm    = reactive({ reason:'', confirm:'' })
const draftForm     = reactive({ name:'', notes:'' })
const addDocFiles   = ref([])
const amendFiles    = ref([])

// Vuelidate
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

const v$ = useVuelidate(rules, vModel)
function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message
  return null
}

// Close helpers
function closeTerminate() { closeModal('terminateModal'); terminateForm.reason=''; terminateForm.confirm=''; v$.value.$reset() }
function closeAmend()     { closeModal('amendmentModal'); amendForm.type=''; amendForm.proposed=''; amendFiles.value=[]; v$.value.$reset() }
function closeRevoke()    { closeModal('accessRevocationModal'); revokeForm.reason=''; revokeForm.confirm=''; v$.value.$reset() }
function closeAddDoc()    { closeModal('addDocumentModal'); addDocForm.name=''; addDocFiles.value=[]; v$.value.$reset() }

// Signature
function applySignature()    { isSigned.value = true; toast.info('Signature applied.') }
function finalizeSignature() {
  if (!isSigned.value) { toast.warning('Please click the signature area to apply your signature first.'); return }
  signBusy.value = true
  router.post(route('provider.documents.sign', { document: activeDocId.value }), {}, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Agreement fully executed!'); closeModal('signatureModal'); isSigned.value = false },
    onError:   () => toast.error('Could not save signature.'),
    onFinish:  () => { signBusy.value = false },
  })
}

// Submit handlers
function sendForSignature() {
  sendBusy.value = true
  router.post(route('provider.documents.request'), { category:wiz.category, doc_type:wiz.docType, reference:wiz.reference, party_b_id:wiz.partyB, effective_date:wiz.effectiveDate, expiry_date:wiz.expirationDate, my_action:sig.myAction, notify_method:sig.notifyMethod, deadline:sig.deadline, message:sig.message }, {
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
