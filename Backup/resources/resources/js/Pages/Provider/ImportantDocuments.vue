<template>
  <AppLayout :user="user" portal="practitioner" activePage="important-documents" pageTitle="Important Documents">

    <!-- HERO -->
    <div class="id-hero">
      <div>
        <div class="id-eyebrow">DOCUMENT CENTER</div>
        <h1 class="id-title">Important Documents</h1>
        <p class="id-sub">Manage agreements and supporting documents shared between Practitioners, Continuity Stewards, and Support Stewards — drafted from vetted templates, signed in-portal, and audit-logged automatically.</p>
      </div>
      <div class="id-hero-actions">
        <button class="btn btn-outline btn-sm id-btn-icon" @click="showToast('Opening activity','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <button class="btn btn-outline btn-sm id-btn-icon" @click="libraryOpen=true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg> Browse Library</button>
        <button class="btn btn-primary btn-sm id-btn-icon" @click="openWizard"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> New Agreement</button>
      </div>
    </div>

    <!-- ACTION REQUIRED BANNER -->
    <div class="id-action-banner">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="flex-shrink:0;color:var(--orange-dark);"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <div class="id-action-text">
        <strong>2 Agreements Require Your Action</strong>
        <div class="id-action-sub">SOW-2025-108 needs your signature · Agreement with Support Steward Linda Johnson needs renewal review.</div>
      </div>
      <div class="id-action-btns">
        <button class="id-sign-btn" @click="showToast('Opening signature flow','info')">Sign Now</button>
        <button class="btn btn-outline btn-sm" @click="showToast('Viewing expiring','info')">View Expiring</button>
      </div>
    </div>

    <!-- STAT CARDS -->
    <div class="id-stats">
      <div class="id-stat"><div class="id-stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div><div class="id-stat-val">10</div><div class="id-stat-lbl">Total Documents</div></div></div>
      <div class="id-stat id-stat-green"><div class="id-stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></div><div><div class="id-stat-val">7</div><div class="id-stat-lbl">Active &amp; Signed</div></div></div>
      <div class="id-stat id-stat-gold"><div class="id-stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="id-stat-val">1</div><div class="id-stat-lbl">Pending Signature</div></div></div>
      <div class="id-stat id-stat-red"><div class="id-stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div><div class="id-stat-val">1</div><div class="id-stat-lbl">Expiring in 30 Days</div></div></div>
    </div>

    <!-- FILTER TABS + SEARCH -->
    <div class="id-filter-tabs">
      <button v-for="f in filterTabs" :key="f.id" class="id-ftab" :class="{ active: activeFilter===f.id }" @click="activeFilter=f.id">
        {{ f.label }}<span v-if="f.count" class="id-ftab-count" :class="{ active: activeFilter===f.id }">{{ f.count }}</span>
      </button>
    </div>
    <div class="id-search-bar">
      <div class="id-search-wrap"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-4);pointer-events:none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input class="id-search" v-model="searchQ" placeholder="Search agreements…"></div>
      <select class="id-sel"><option>All Statuses</option><option>Active</option><option>Pending</option><option>Expiring</option><option>Draft</option></select>
      <select class="id-sel"><option>All Types</option><option>BAA</option><option>MSA</option><option>SOW</option><option>MOU</option><option>NDA</option></select>
      <select class="id-sel"><option>All Parties</option><option>Provider &amp; CS</option><option>Provider &amp; SS</option><option>SS &amp; CS</option></select>
      <button class="id-export-btn" @click="showToast('Exporting','info')"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export</button>
    </div>

    <!-- CONTINUITY PLAN SECTION -->
    <div class="id-section">
      <div class="id-section-header">
        <div>
          <div class="id-section-title">Continuity Plan</div>
          <div class="id-section-sub">Your signed succession agreement and the 7-incident-type configuration grid</div>
        </div>
        <div class="id-section-actions">
          <button class="btn btn-outline btn-sm id-btn-icon" @click="showToast('Opening plan builder','info')">Open Plan Builder</button>
          <button class="btn btn-outline btn-sm id-btn-icon" @click="showToast('Adding document','info')">+ Add Document</button>
          <button class="id-sign-btn" @click="openWizard">+ Create New Agreement</button>
        </div>
      </div>
      <div v-for="doc in continuityDocs" :key="doc.ref" class="id-doc-row" :class="{ 'id-doc-sign': doc.needsSign }">
        <div class="id-doc-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
        <div class="id-doc-info">
          <div class="id-doc-type-label">{{ doc.type }}</div>
          <div class="id-doc-title">{{ doc.title }}</div>
          <div class="id-doc-ref">Reference · {{ doc.ref }}</div>
          <div v-if="doc.parties" class="id-doc-parties">
            <span v-for="p in doc.parties" :key="p.initials" class="id-party-chip" :style="{ background: p.color }">{{ p.initials }}</span>
            <span v-if="doc.execution" class="id-execution-badge">✓ {{ doc.execution }}</span>
            <span v-if="doc.awaiting" class="id-awaiting-badge">⏱ {{ doc.awaiting }}</span>
          </div>
          <div v-else class="id-doc-audience">{{ doc.audience }}</div>
        </div>
        <div class="id-doc-status-area">
          <span v-if="doc.status==='ACTIVE'" class="id-status-badge id-status-active">ACTIVE</span>
          <span v-else-if="doc.status==='EXPIRING'" class="id-status-badge id-status-expiring">EXPIRING</span>
          <span v-else-if="doc.status==='DRAFT'" class="id-status-badge id-status-draft">DRAFT</span>
          <button v-if="doc.needsSign" class="id-sign-btn id-sign-sm" @click="showToast('Opening signature flow','info')">Sign</button>
          <button v-if="doc.status==='EXPIRING'" class="id-renew-btn" @click="showToast('Renewing '+doc.title,'info')">Renew</button>
          <button v-if="doc.isDraft" class="id-edit-btn" @click="showToast('Editing '+doc.title,'info')">Edit</button>
        </div>
        <div class="id-doc-actions">
          <button class="id-act-btn" title="View" @click="openDocModal('view', doc)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
          <button v-if="!doc.isDraft" class="id-act-btn" title="Download" @click="showToast('Downloading','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></button>
          <button class="id-act-btn" title="More actions" @click="openDocModal('actions', doc)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg></button>
          <button v-if="doc.isDraft" class="id-act-btn" title="Share" @click="showToast('Sharing','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg></button>
          <button v-if="doc.isDraft" class="id-act-btn id-act-danger" title="Delete" @click="showToast('Deleting draft','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>
        </div>
      </div>
    </div>

    <!-- SUPPORTING DOCUMENTS -->
    <div class="id-section">
      <div class="id-section-header">
        <div>
          <div class="id-section-title">Supporting Documents</div>
          <div class="id-section-sub">Amendments and other supporting documents shared between Practitioners, Continuity Stewards, and Support Stewards.</div>
        </div>
        <button class="btn btn-outline btn-sm id-btn-icon" @click="showToast('Adding document','info')">+ Add Document</button>
      </div>
      <div v-for="doc in supportingDocs" :key="doc.ref" class="id-doc-row">
        <div class="id-doc-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
        <div class="id-doc-info">
          <div class="id-doc-title">{{ doc.title }}</div>
          <div class="id-doc-ref">{{ doc.meta }}</div>
        </div>
        <div class="id-doc-status-area">
          <span class="id-status-badge" :class="doc.status==='EXECUTED' ? 'id-status-active' : 'id-status-draft'" style="font-size:9px;">{{ doc.status }}</span>
        </div>
        <div class="id-doc-actions">
          <button class="id-act-btn" title="View" @click="showToast('Viewing','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
          <button class="id-act-btn" title="Download" @click="showToast('Downloading','info')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></button>
        </div>
      </div>
    </div>

    <!-- ADD SUPPORTING DOCUMENT -->
    <button class="id-add-dashed" @click="showToast('Opening upload form','info')">
      <div class="id-add-icon-wrap"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div>
      <div class="id-add-title">Add a Supporting Document</div>
      <div class="id-add-sub">Upload an amendment, reference, or other document to share with your stewards.</div>
    </button>

    <!-- Toast -->
    <Teleport to="body">

      <!-- DOC VIEW MODAL -->
      <Transition name="id-modal-fade"><div v-if="dm.type==='view'" class="id-modal-backdrop" @click.self="dm.type=null"><div class="id-modal id-modal-xl"><div class="id-wiz-header" style="align-items:center;"><div><div class="id-wiz-title">{{ dm.doc?.ref }} · {{ dm.doc?.title }}</div><div class="id-wiz-sub">{{ dm.doc?.type }} · Provider → Continuity Steward · Active · Executed 14 Jan 2026</div></div><div style="display:flex;gap:8px;align-items:center;"><button class="id-wiz-save-btn" @click="showToast('Downloading PDF','info')">PDF</button><button class="id-modal-x" @click="dm.type=null">×</button></div></div><div class="id-wiz-body"><div class="id-view-meta-grid"><div class="id-view-meta-col"><div class="id-view-meta-lbl">AGREEMENT DETAILS</div><div class="id-review-row"><span class="id-review-k">Reference</span><span class="id-review-v">{{ dm.doc?.ref }}</span></div><div class="id-review-row"><span class="id-review-k">Type</span><span class="id-review-v">Master Service Agreement</span></div><div class="id-review-row"><span class="id-review-k">Version</span><span class="id-review-v">v1.2 (Amended)</span></div></div><div class="id-view-meta-col"><div class="id-view-meta-lbl">TERMS &amp; STATUS</div><div class="id-review-row"><span class="id-review-k">Effective</span><span class="id-review-v">14 Jan 2026</span></div><div class="id-review-row"><span class="id-review-k">Expires</span><span class="id-review-v">13 Jan 2026</span></div><div class="id-review-row"><span class="id-review-k">Status</span><span class="id-review-v" style="color:var(--green-dark);">Active</span></div></div><div class="id-view-meta-col"><div class="id-view-meta-lbl">SIGNED BY (PARTY A)</div><div class="id-view-sig-name">Sarah Johnson, M.D.</div><div style="font-size:11px;color:var(--text-4);">Signed 14 Jan 2026 · Verified</div></div><div class="id-view-meta-col"><div class="id-view-meta-lbl">CONTINUITY STEWARD (PARTY B)</div><div class="id-view-sig-name">Marcus Chen</div><div style="font-size:11px;color:var(--text-4);">Signed 14 Jan 2026 · Verified</div></div></div><div class="id-view-doc-body"><p class="id-review-doc-para">Continuity steward is engaged as an independent contractor. Nothing in this agreement creates an employment, relationship, partnership, or agency between the parties.</p><p class="id-review-doc-para"><strong>3. Confidentiality &amp; HIPAA</strong><br>Continuity Steward shall comply with all applicable HIPAA and HITECH Act requirements. PHI access is Read-Only. A BAA is incorporated by reference. Confidentiality obligations survive termination for five (5) years.</p><p class="id-review-doc-para"><strong>4. Compensation</strong><br>Provider shall compensate Continuity Steward at $3,200 USD/month, payable by the 5th business day of each calendar month.</p><p class="id-review-doc-para"><strong>5. Termination</strong><br>Either party may terminate with thirty (30) days written notice. Provider may terminate immediately upon HIPAA violation, fraud, or license revocation.</p></div><div class="id-view-history"><div class="id-view-history-title">Agreement History</div><div v-for="h in agreementHistory" :key="h.date" class="id-view-hist-row"><div class="id-view-hist-dot"></div><div><div class="id-view-hist-date">{{ h.date }}</div><div class="id-view-hist-title">{{ h.title }}</div><div class="id-view-hist-sub">{{ h.sub }}</div></div></div></div></div><div class="id-wiz-footer"><button class="btn btn-outline btn-sm" @click="dm.type=null">Close</button><button class="id-wiz-save-btn" @click="dm.type=null; dm.type='amendment'">Request Amendment</button><button class="ssm-red-btn" style="padding:6px 14px;font-size:12.5px;" @click="dm.type='terminate'">Terminate</button></div></div></div></Transition>

      <!-- ACTIONS MENU MODAL -->
      <Transition name="id-modal-fade"><div v-if="dm.type==='actions'" class="id-modal-backdrop" @click.self="dm.type=null"><div class="id-modal" style="max-width:420px;"><div class="id-wiz-header"><div><div class="id-wiz-title">Agreement Actions</div><div class="id-wiz-sub">{{ dm.doc?.ref }} · {{ dm.doc?.title }}</div></div><button class="id-modal-x" @click="dm.type=null">×</button></div><div class="id-wiz-body" style="gap:2px;padding:10px 16px;"><div v-for="a in actionItems" :key="a.label" class="id-action-item" :class="{ 'id-action-danger': a.danger }" @click="handleAction(a.action)"><div class="id-action-item-icon" v-html="a.icon"></div><div><div class="id-action-item-title">{{ a.label }}</div><div class="id-action-item-sub">{{ a.sub }}</div></div><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="margin-left:auto;flex-shrink:0;"><path d="M5 12h14M12 5l7 7-7 7"/></svg></div></div></div></div></Transition>

      <!-- REQUEST AMENDMENT -->
      <Transition name="id-modal-fade"><div v-if="dm.type==='amendment'" class="id-modal-backdrop" @click.self="dm.type=null"><div class="id-modal id-modal-xl"><div class="id-wiz-header"><div><div class="id-wiz-title">Request Amendment</div><div class="id-wiz-sub">{{ dm.doc?.ref }} — Process changes for mutual review &amp; approval</div></div><button class="id-modal-x" @click="dm.type=null">×</button></div><div class="id-wiz-body"><div class="id-wiz-notice id-wiz-notice-blue">Amendments require agreement from all original signatories. The other party will be notified and must accept or counter-propose changes before the amendment takes effect.</div><div class="id-wiz-field"><label class="id-wiz-lbl">Amendment Type <span class="id-req">*</span></label><select class="id-wiz-inp"><option value="">— Select Type —</option><option>Scope Change</option><option>Compensation Update</option><option>Term Extension</option><option>Party Change</option><option>Other</option></select></div><div class="id-wiz-field"><label class="id-wiz-lbl">Clause(s) to Amend <span class="id-req">*</span></label><div style="display:flex;flex-direction:column;gap:5px;"><label v-for="c in amendClauses" :key="c" style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;"><input type="checkbox" style="accent-color:var(--gold-dark);"> {{ c }}</label></div></div><div class="id-wiz-row"><div class="id-wiz-field"><label class="id-wiz-lbl">Current Language</label><textarea class="id-wiz-ta" rows="4" placeholder="Provider shall compensate Continuity Steward at a monthly retainer of $3,200 USD, payable by the 5th business day of each calendar month."></textarea></div><div class="id-wiz-field"><label class="id-wiz-lbl">Proposed Change <span class="id-req">*</span></label><textarea class="id-wiz-ta" v-model="dmForm.proposedChange" rows="4" placeholder="Describe the proposed new language…"></textarea></div></div><div class="id-wiz-field"><label class="id-wiz-lbl">Reason / Justification</label><textarea class="id-wiz-ta" v-model="dmForm.reason" rows="2" placeholder="Briefly explain why this amendment is being requested…"></textarea></div><div class="id-wiz-row"><div class="id-wiz-field"><label class="id-wiz-lbl">Proposed Effective Date</label><input class="id-wiz-inp" type="date" v-model="dmForm.effectiveDate"></div><div style="font-size:11.5px;color:var(--text-4);padding-top:22px;">If blank, takes effect upon mutual execution.</div></div><div class="id-wiz-field"><label class="id-wiz-lbl">Supporting Documents (optional)</label><div class="id-upload-zone"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg><div>Drag &amp; drop or click to attach</div><div style="font-size:11px;color:var(--text-4);">PDF, DOC, DOCX, TXT · Max 10 MB · Up to 5 files</div></div><div style="font-size:11.5px;color:var(--text-4);margin-top:4px;">Attach any prior correspondence, redlined drafts, or supporting evidence for this amendment.</div></div></div><div class="id-wiz-footer"><button class="btn btn-outline btn-sm" @click="dm.type=null">Cancel</button><button class="id-sign-btn" @click="showToast('Amendment request sent','success');dm.type=null">Send Amendment Request</button></div></div></div></Transition>

      <!-- RENEW AGREEMENT -->
      <Transition name="id-modal-fade"><div v-if="dm.type==='renew'" class="id-modal-backdrop" @click.self="dm.type=null"><div class="id-modal"><div class="id-wiz-header"><div><div class="id-wiz-title">Renew Agreement</div><div class="id-wiz-sub">{{ dm.doc?.ref }} · Expires in 18 days — Linda Johnson (Admin Support Steward)</div></div><button class="id-modal-x" @click="dm.type=null">×</button></div><div class="id-wiz-body"><div class="id-wiz-notice" style="background:rgba(232,169,74,.08);border-color:rgba(232,169,74,.4);color:var(--orange-dark);">This agreement expires on <strong>16 March 2025</strong>. If not renewed, Linda Johnson's access will be suspended on expiry date.</div><div class="id-wiz-field"><label class="id-wiz-lbl">RENEWAL OPTIONS</label><div class="id-renew-options"><div class="id-renew-opt" :class="{ selected: dmForm.renewType==='same' }" @click="dmForm.renewType='same'"><div class="cs-vault-radio"><span class="cs-vault-dot" v-if="dmForm.renewType==='same'"></span></div><div style="flex:1;"><div class="id-agr-cat-title">Renew with Same Terms <span style="font-size:10px;font-weight:700;color:var(--gold-dark);background:rgba(160,129,62,.1);border-radius:99px;padding:2px 7px;margin-left:6px;">RECOMMENDED</span></div><div class="id-agr-cat-sub">Extend 12 months with identical clauses. Both parties re-sign.</div></div></div><div class="id-renew-opt" :class="{ selected: dmForm.renewType==='amend' }" @click="dmForm.renewType='amend'"><div class="cs-vault-radio"><span class="cs-vault-dot" v-if="dmForm.renewType==='amend'"></span></div><div><div class="id-agr-cat-title">Renew with Amendments</div><div class="id-agr-cat-sub">Modify specific clauses before renewal.</div></div></div></div></div><div class="id-wiz-row"><div class="id-wiz-field"><label class="id-wiz-lbl">New Effective Date</label><input class="id-wiz-inp" type="date" v-model="dmForm.renewEffective"></div><div class="id-wiz-field"><label class="id-wiz-lbl">New Expiry Date</label><input class="id-wiz-inp" type="date" v-model="dmForm.renewExpiry"></div></div><div class="id-wiz-notice id-wiz-notice-green">After renewal, Linda Johnson will receive an email and must re-sign within 5 business days.</div></div><div class="id-wiz-footer"><button class="btn btn-outline btn-sm" @click="dm.type=null">Cancel</button><button class="id-sign-btn" @click="showToast('Renewal initiated','success');dm.type=null">Initiate Renewal</button></div></div></div></Transition>

      <!-- ATTACH BAA -->
      <Transition name="id-modal-fade"><div v-if="dm.type==='baa'" class="id-modal-backdrop" @click.self="dm.type=null"><div class="id-modal"><div class="id-wiz-header"><div><div class="id-wiz-title">Business Associate Agreement (BAA)</div><div class="id-wiz-sub">HIPAA-compliant template — auto-attached when PHI access is granted</div></div><button class="id-modal-x" @click="dm.type=null">×</button></div><div class="id-wiz-body"><div class="id-wiz-notice id-wiz-notice-blue">This BAA is incorporated by reference into the parent agreement. Both parties become bound upon execution of the parent agreement.</div><div class="id-view-doc-body" style="max-height:220px;overflow-y:auto;"><div class="id-review-doc-title">BUSINESS ASSOCIATE AGREEMENT</div><div class="id-review-doc-ref">Pursuant to HIPAA/HITECH · Incorporated into parent agreement</div><p class="id-review-doc-para"><strong>1. Definitions</strong><br>"Business Associate" means the party receiving PHI access. "Covered Entity" means the Provider. "PHI" means Protected Health Information as defined under 45 CFR § 160.103.</p><p class="id-review-doc-para"><strong>2. Obligations of Business Associate</strong><br>Business Associate shall not use or disclose PHI other than as permitted by this agreement or as required by law. Business Associate shall implement appropriate safeguards and comply with Subpart C of 45 CFR 164. Any unauthorized use or disclosure shall be reported within 5 business days of discovery.</p><p class="id-review-doc-para"><strong>3. Permitted Uses and Disclosures</strong><br>Business Associate may use PHI only to perform services described in the parent agreement and as required by law.</p></div></div><div class="id-wiz-footer"><button class="btn btn-outline btn-sm" @click="dm.type=null">Close</button><button class="id-sign-btn" @click="showToast('BAA included in agreement','success');dm.type=null">✓ Understood — Include BAA</button></div></div></div></Transition>

      <!-- CONFIGURE NDA ADDENDUM -->
      <Transition name="id-modal-fade"><div v-if="dm.type==='nda'" class="id-modal-backdrop" @click.self="dm.type=null"><div class="id-modal"><div class="id-wiz-header"><div><div class="id-wiz-title">Configure NDA Addendum</div><div class="id-wiz-sub">A Non-Disclosure Agreement will be attached to your main agreement.</div></div><button class="id-modal-x" @click="dm.type=null">×</button></div><div class="id-wiz-body"><div class="id-wiz-field"><label class="id-wiz-lbl">Information to keep confidential</label><div style="display:flex;flex-direction:column;gap:6px;"><label v-for="n in ndaItems" :key="n" style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;"><input type="checkbox" checked style="accent-color:var(--gold-dark);"> {{ n }}</label></div></div><div class="id-wiz-row"><div class="id-wiz-field"><label class="id-wiz-lbl">NDA Duration</label><select class="id-wiz-inp"><option>5 years post-termination</option><option>2 years post-termination</option><option>Indefinite</option></select></div><div class="id-wiz-field"><label class="id-wiz-lbl">NDA Type</label><select class="id-wiz-inp"><option>Mutual (both parties)</option><option>One-way (Provider only)</option><option>One-way (Steward only)</option></select></div></div><div class="id-wiz-field"><label class="id-wiz-lbl">Permitted Exceptions</label><textarea class="id-wiz-ta" rows="3" placeholder="e.g. Disclosure required by law or court order, with prior written notice to the disclosing party…"></textarea></div></div><div class="id-wiz-footer"><button class="btn btn-outline btn-sm" @click="dm.type=null">Cancel</button><button class="id-sign-btn" @click="showToast('NDA attached to agreement','success');dm.type=null">Attach NDA to Agreement</button></div></div></div></Transition>

      <!-- TERMINATE AGREEMENT -->
      <Transition name="id-modal-fade"><div v-if="dm.type==='terminate'" class="id-modal-backdrop" @click.self="dm.type=null"><div class="id-modal" style="max-width:440px;"><div class="id-wiz-header"><div><div class="id-wiz-title">Terminate Agreement</div><div class="id-wiz-sub">{{ dm.doc?.ref }} · This action cannot be undone</div></div><button class="id-modal-x" @click="dm.type=null">×</button></div><div class="id-wiz-body"><div class="id-wiz-notice" style="background:var(--red-light);border-color:var(--soft-red,rgba(160,45,34,.35));color:var(--red);">Terminating this agreement will immediately revoke Marcus Chen's access and delegated authority.</div><div class="id-wiz-field"><label class="id-wiz-lbl">Reason for Termination <span class="id-req">*</span></label><select class="id-wiz-inp" v-model="dmForm.terminateReason"><option value="">— Select Reason —</option><option>Mutual agreement</option><option>Breach of contract</option><option>HIPAA violation</option><option>No longer needed</option><option>Other</option></select></div><div class="id-wiz-field"><label class="id-wiz-lbl">Effective Termination Date</label><input class="id-wiz-inp" type="date" v-model="dmForm.terminateDate"><div style="font-size:11.5px;color:var(--text-4);margin-top:4px;">Standard 30-day notice required unless terminating for cause.</div></div><div class="id-wiz-field"><label class="id-wiz-lbl">Type "TERMINATE" to confirm <span class="id-req">*</span></label><input class="id-wiz-inp" v-model="dmForm.terminateConfirm" placeholder="TERMINATE"></div></div><div class="id-wiz-footer"><button class="btn btn-outline btn-sm" @click="dm.type=null">Cancel</button><button class="ssm-red-btn" :disabled="dmForm.terminateConfirm!=='TERMINATE'" @click="showToast('Agreement terminated','info');dm.type=null">Confirm Termination</button></div></div></div></Transition>

      <!-- REVOKE ACCESS -->
      <Transition name="id-modal-fade"><div v-if="dm.type==='revoke'" class="id-modal-backdrop" @click.self="dm.type=null"><div class="id-modal" style="max-width:480px;"><div class="id-wiz-header"><div><div class="id-wiz-title">Revoke Access</div><div class="id-wiz-sub">Immediately remove all system access for this party</div></div><button class="id-modal-x" @click="dm.type=null">×</button></div><div class="id-wiz-body"><div class="id-wiz-notice" style="background:var(--red-light);border-color:var(--soft-red,rgba(160,45,34,.35));color:var(--red);"><span><strong>Immediate &amp; irreversible.</strong> Marcus Chen will lose all Aegis access, PHI access, and delegated permissions within 5 minutes.</span></div><div class="id-wiz-section-lbl" style="margin-top:8px;">ACCESS BEING REVOKED</div><div class="id-revoke-table"><div class="id-revoke-row"><span>Platform Access</span><span class="id-revoke-term">Terminated</span></div><div class="id-revoke-row"><span>PHI Access</span><span class="id-revoke-term">Terminated</span></div><div class="id-revoke-row"><span>Delegated Authority</span><span class="id-revoke-term">Terminated</span></div><div class="id-revoke-row"><span>Active Agreement</span><span style="font-size:11.5px;font-weight:700;color:var(--orange-dark);">Suspended (not terminated)</span></div></div><div class="id-wiz-field"><label class="id-wiz-lbl">Reason <span class="id-req">*</span></label><select class="id-wiz-inp" v-model="dmForm.revokeReason"><option value="">— Select Reason —</option><option>Security concern</option><option>HIPAA breach</option><option>No longer authorized</option><option>Other</option></select></div><div class="id-wiz-field"><label class="id-wiz-lbl">Type "REVOKE" to confirm <span class="id-req">*</span></label><input class="id-wiz-inp" v-model="dmForm.revokeConfirm" placeholder="REVOKE"></div></div><div class="id-wiz-footer"><button class="btn btn-outline btn-sm" @click="dm.type=null">Cancel</button><button class="ssm-red-btn" :disabled="dmForm.revokeConfirm!=='REVOKE'" @click="showToast('Access revoked','info');dm.type=null">Confirm Revocation</button></div></div></div></Transition>

      <!-- FLAG DISPUTE -->
      <Transition name="id-modal-fade"><div v-if="dm.type==='dispute'" class="id-modal-backdrop" @click.self="dm.type=null"><div class="id-modal"><div class="id-wiz-header"><div><div class="id-wiz-title">Flag a Dispute or Concern</div><div class="id-wiz-sub">{{ dm.doc?.ref }} · Report a breach, violation, or concern</div></div><button class="id-modal-x" @click="dm.type=null">×</button></div><div class="id-wiz-body"><div class="id-wiz-notice" style="background:rgba(232,169,74,.08);border-color:rgba(232,169,74,.4);color:var(--orange-dark);">Filing a dispute creates a formal record and notifies the Aegis compliance team. Both parties will be notified unless you select a confidential report.</div><div class="id-wiz-field"><label class="id-wiz-lbl">Dispute Category <span class="id-req">*</span></label><select class="id-wiz-inp"><option value="">— Select Category —</option><option>Unauthorized PHI access</option><option>Non-performance</option><option>Contract breach</option><option>Billing dispute</option><option>Other</option></select></div><div class="id-wiz-field"><label class="id-wiz-lbl">Severity Level</label><div class="id-severity-btns"><button v-for="s in ['Low','Medium','High','Critical']" :key="s" class="id-severity-btn" :class="{ active: dmForm.severity===s, ['sev-'+s.toLowerCase()]: true }" @click="dmForm.severity=s">{{ s }}</button></div></div><div class="id-wiz-row"><div class="id-wiz-field"><label class="id-wiz-lbl">Incident Date</label><input class="id-wiz-inp" type="date" v-model="dmForm.disputeDate"></div><div class="id-wiz-field"><label class="id-wiz-lbl">Description <span class="id-req">*</span></label><textarea class="id-wiz-ta" v-model="dmForm.disputeDesc" rows="3" placeholder="Describe the dispute in detail — dates, specific actions, evidence…"></textarea></div></div><div class="id-wiz-field"><label class="id-wiz-lbl">Supporting Evidence (optional)</label><div class="id-upload-zone"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg><div>Drag files or click to attach</div><div style="font-size:11px;color:var(--text-4);">Screenshots, emails, documents · Max 10 MB · Up to 10 files</div></div></div><label style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--text-2);cursor:pointer;"><input type="checkbox" v-model="dmForm.confidential" style="accent-color:var(--gold-dark);"> File as confidential (Aegis compliance only — other party not notified initially)</label></div><div class="id-wiz-footer"><button class="btn btn-outline btn-sm" @click="dm.type=null">Cancel</button><button class="ssm-red-btn" @click="showToast('Dispute report submitted','info');dm.type=null">Submit Dispute Report</button></div></div></div></Transition>

      <!-- SHARE AGREEMENT -->
      <Transition name="id-modal-fade"><div v-if="dm.type==='share'" class="id-modal-backdrop" @click.self="dm.type=null"><div class="id-modal" style="max-width:480px;"><div class="id-wiz-header"><div><div class="id-wiz-title">Share Agreement</div><div class="id-wiz-sub">{{ dm.doc?.ref }} · Send a copy or access link to a party</div></div><button class="id-modal-x" @click="dm.type=null">×</button></div><div class="id-wiz-body"><div class="id-wiz-notice id-wiz-notice-blue">Sharing generates a read-only, time-limited link. The recipient must have an Aegis account or use the secure guest viewer. All access is audit-logged.</div><div class="id-wiz-field"><label class="id-wiz-lbl">Share With <span class="id-req">*</span></label><select class="id-wiz-inp"><option value="">— Select recipient —</option><option>Marcus Chen (Primary CS)</option><option>Linda Johnson (Support Steward)</option><option>Dr. Priya Raman (Secondary CS)</option><option>Other (enter email below)</option></select></div><div class="id-wiz-field"><label class="id-wiz-lbl">Email Address <span class="id-opt" style="font-weight:400;color:var(--text-4);">(if external)</span></label><input class="id-wiz-inp" type="email" v-model="dmForm.shareEmail" placeholder="name@example.com"></div><div class="id-wiz-field"><label class="id-wiz-lbl">Link Expires After</label><select class="id-wiz-inp" v-model="dmForm.shareExpiry"><option value="1">1 day</option><option value="7">7 days</option><option value="14">14 days</option><option value="30">30 days</option><option value="0">Never (until manually revoked)</option></select></div><div class="id-wiz-field"><label class="id-wiz-lbl">Access Level</label><div style="display:flex;flex-direction:column;gap:6px;"><label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;"><input type="radio" name="shareAccess" value="view" checked style="accent-color:var(--gold-dark);"> View only — no download</label><label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;"><input type="radio" name="shareAccess" value="download" style="accent-color:var(--gold-dark);"> View &amp; download PDF</label></div></div><div class="id-wiz-field"><label class="id-wiz-lbl">Personal Message <span class="id-opt" style="font-weight:400;color:var(--text-4);">(optional)</span></label><textarea class="id-wiz-ta" v-model="dmForm.shareMessage" rows="2" placeholder="e.g. Please review this agreement at your earliest convenience…"></textarea></div></div><div class="id-wiz-footer"><button class="btn btn-outline btn-sm" @click="dm.type=null">Cancel</button><button class="id-sign-btn" @click="showToast('Agreement shared successfully','success');dm.type=null"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Send Share Link</button></div></div></div></Transition>

      <!-- AUDIT TRAIL -->
      <Transition name="id-modal-fade"><div v-if="dm.type==='audit'" class="id-modal-backdrop" @click.self="dm.type=null"><div class="id-modal id-modal-xl"><div class="id-wiz-header"><div><div class="id-wiz-title">Audit Trail</div><div class="id-wiz-sub">{{ dm.doc?.ref }} · Complete history of all actions and changes</div></div><button class="id-modal-x" @click="dm.type=null">×</button></div><div class="id-wiz-body"><div class="id-audit-filters"><select class="id-wiz-inp" style="width:auto;"><option>All Event Types</option><option>Views</option><option>Signatures</option><option>Amendments</option><option>Downloads</option><option>Access Changes</option></select><select class="id-wiz-inp" style="width:auto;"><option>All Parties</option><option>Dr. Sarah Johnson</option><option>Marcus Chen</option></select></div><div class="id-audit-list"><div v-for="e in auditEvents" :key="e.id" class="id-audit-row"><div class="id-audit-dot" :class="'audit-'+e.type"></div><div class="id-audit-info"><div class="id-audit-title">{{ e.title }}</div><div class="id-audit-meta">{{ e.actor }} · {{ e.date }}</div><div v-if="e.detail" class="id-audit-detail">{{ e.detail }}</div></div><span class="id-audit-badge" :class="'audit-badge-'+e.type">{{ e.typeLabel }}</span></div></div></div><div class="id-wiz-footer"><button class="btn btn-outline btn-sm" @click="dm.type=null">Close</button><button class="id-wiz-save-btn" @click="showToast('Exporting audit log','info')"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export Log</button></div></div></div></Transition>

      <!-- ARCHIVE AGREEMENT -->
      <Transition name="id-modal-fade"><div v-if="dm.type==='archive'" class="id-modal-backdrop" @click.self="dm.type=null"><div class="id-modal" style="max-width:460px;"><div class="id-wiz-header"><div><div class="id-wiz-title">Archive Agreement</div><div class="id-wiz-sub">{{ dm.doc?.ref }} · Move to archive</div></div><button class="id-modal-x" @click="dm.type=null">×</button></div><div class="id-wiz-body"><div class="id-wiz-notice" style="background:rgba(80,120,190,.06);border-color:rgba(80,120,190,.18);color:var(--text-2);"><span><strong>Non-destructive action.</strong> Archiving moves this agreement out of your active view. It remains fully accessible in your archive, searchable, and legally valid. You can restore it at any time.</span></div><div class="id-wiz-section-lbl" style="margin-top:4px;">WHAT ARCHIVING DOES</div><div class="id-revoke-table"><div class="id-revoke-row"><span>Agreement Status</span><span style="font-size:11.5px;font-weight:700;color:var(--text-2);">Unchanged (remains Active)</span></div><div class="id-revoke-row"><span>Party Access</span><span style="font-size:11.5px;font-weight:700;color:var(--text-2);">Unchanged</span></div><div class="id-revoke-row"><span>Audit Trail</span><span style="font-size:11.5px;font-weight:700;color:var(--green-dark);">Preserved</span></div><div class="id-revoke-row"><span>Active Document List</span><span style="font-size:11.5px;font-weight:700;color:var(--orange-dark);">Removed from view</span></div></div><div class="id-wiz-field"><label class="id-wiz-lbl">Reason for Archiving <span class="id-opt" style="font-weight:400;color:var(--text-4);">(optional)</span></label><select class="id-wiz-inp" v-model="dmForm.archiveReason"><option value="">— Select Reason —</option><option>Agreement superseded by new version</option><option>No longer active relationship</option><option>Keeping for compliance records only</option><option>Duplicate agreement</option><option>Other</option></select></div></div><div class="id-wiz-footer"><button class="btn btn-outline btn-sm" @click="dm.type=null">Cancel</button><button class="id-sign-btn" @click="showToast('Agreement archived','success');dm.type=null"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg> Archive Agreement</button></div></div></div></Transition>

      <!-- REVOKE REVIEW -->
      <Transition name="id-modal-fade"><div v-if="dm.type==='revokeReview'" class="id-modal-backdrop" @click.self="dm.type=null"><div class="id-modal" style="max-width:480px;"><div class="id-wiz-header"><div><div class="id-wiz-title">Revoke Review Access</div><div class="id-wiz-sub">Immediately remove system access for this reviewer</div></div><button class="id-modal-x" @click="dm.type=null">×</button></div><div class="id-wiz-body"><div class="id-wiz-notice" style="background:var(--red-light);border-color:var(--soft-red,rgba(160,45,34,.35));color:var(--red);"><span><strong>Immediate action.</strong> This will remove the party's ability to view or review this agreement within 5 minutes. Their signature (if applied) remains valid.</span></div><div class="id-wiz-section-lbl" style="margin-top:8px;">ACCESS BEING REVOKED</div><div class="id-revoke-table"><div class="id-revoke-row"><span>Agreement View Access</span><span class="id-revoke-term">Revoked</span></div><div class="id-revoke-row"><span>Comment &amp; Review Rights</span><span class="id-revoke-term">Revoked</span></div><div class="id-revoke-row"><span>Download Rights</span><span class="id-revoke-term">Revoked</span></div><div class="id-revoke-row"><span>Existing Signature</span><span style="font-size:11.5px;font-weight:700;color:var(--green-dark);">Preserved</span></div><div class="id-revoke-row"><span>Audit Trail</span><span style="font-size:11.5px;font-weight:700;color:var(--green-dark);">Preserved</span></div></div><div class="id-wiz-field"><label class="id-wiz-lbl">Reason <span class="id-req">*</span></label><select class="id-wiz-inp"><option value="">— Select Reason —</option><option>Review period ended</option><option>Party no longer involved</option><option>Security concern</option><option>Access granted in error</option><option>Other</option></select></div><div class="id-wiz-field"><label class="id-wiz-lbl">Type "REVOKE" to confirm <span class="id-req">*</span></label><input class="id-wiz-inp" v-model="dmForm.revokeReviewConfirm" placeholder="REVOKE"></div></div><div class="id-wiz-footer"><button class="btn btn-outline btn-sm" @click="dm.type=null">Cancel</button><button class="ssm-red-btn" :disabled="dmForm.revokeReviewConfirm!=='REVOKE'" @click="showToast('Review access revoked','info');dm.type=null">Confirm Revocation</button></div></div></div></Transition>

      <!-- NEW AGREEMENT WIZARD -->
      <Transition name="id-modal-fade">
        <div v-if="wizardOpen" class="id-modal-backdrop" @click.self="wizardOpen=false">
          <div class="id-modal id-modal-xl">

            <!-- STEP HEADER -->
            <div class="id-wiz-header">
              <div>
                <div class="id-wiz-title">{{ stepTitles[wizStep-1] }}</div>
                <div class="id-wiz-sub">Step {{ wizStep }} of 5 — {{ stepSubs[wizStep-1] }}</div>
              </div>
              <button class="id-modal-x" @click="wizardOpen=false">×</button>
            </div>

            <!-- STEP PROGRESS -->
            <div class="id-wiz-progress">
              <div v-for="(s,i) in stepLabels" :key="s" class="id-wiz-step" :class="{ done: i<wizStep-1, active: i===wizStep-1 }">
                <div class="id-wiz-circle">
                  <svg v-if="i<wizStep-1" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                  <span v-else>{{ i+1 }}</span>
                </div>
                <div class="id-wiz-label">{{ s[0] }}<br><span>{{ s[1] }}</span></div>
                <div v-if="i<4" class="id-wiz-line" :class="{ done: i<wizStep-1 }"></div>
              </div>
            </div>

            <!-- ── STEP 1: Agreement Type ── -->
            <div v-if="wizStep===1" class="id-wiz-body">
              <div class="id-wiz-q">What kind of agreement are you creating?</div>
              <div class="id-wiz-field-label">Agreement Category <span class="id-req">*</span></div>
              <div class="id-agr-cats">
                <div v-for="cat in agrCategories" :key="cat.value" class="id-agr-cat" :class="{ selected: wiz.category===cat.value }" @click="wiz.category=cat.value">
                  <div class="id-agr-cat-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" v-html="cat.icon"></svg></div>
                  <div>
                    <div class="id-agr-cat-title">{{ cat.title }}</div>
                    <div class="id-agr-cat-sub">{{ cat.sub }}</div>
                  </div>
                </div>
              </div>
              <div class="id-wiz-row">
                <div class="id-wiz-field">
                  <label class="id-wiz-lbl">Document Type <span class="id-req">*</span></label>
                  <select class="id-wiz-inp" v-model="wiz.docType">
                    <option>MSA – Master Service Agreement</option>
                    <option>SOW – Statement of Work</option>
                    <option>NDA – Non-Disclosure Agreement</option>
                    <option>MOU – Memorandum of Understanding</option>
                    <option>BAA – Business Associate Agreement</option>
                    <option>SLA – Service Level Agreement</option>
                  </select>
                </div>
                <div class="id-wiz-field">
                  <label class="id-wiz-lbl">Reference / Title (optional)</label>
                  <input class="id-wiz-inp" v-model="wiz.reference" placeholder="Auto-generated if blank (e.g. MSA-2025-115)">
                </div>
              </div>
              <div class="id-wiz-notice id-wiz-notice-blue">An Aegis-certified MSA template (8 clauses) will be pre-loaded.</div>
            </div>

            <!-- ── STEP 2: Parties & Details ── -->
            <div v-if="wizStep===2" class="id-wiz-body">
              <div class="id-wiz-notice id-wiz-notice-blue">Party A is always <strong>you (the Provider)</strong>. Select the other party or parties below.</div>
              <div class="id-wiz-parties-grid">
                <div class="id-party-col">
                  <div class="id-party-col-lbl">PARTY A — PROVIDER (YOU)</div>
                  <div class="id-party-card id-party-card-selected">
                    <div class="id-party-avatar">SJ</div>
                    <div><div class="id-party-name">Dr. Sarah Johnson</div><div class="id-party-meta">Primary Care · Lic. #: 7821 · NPI 1234567890</div></div>
                  </div>
                </div>
                <div class="id-party-col">
                  <div class="id-party-col-lbl">PARTY B — SELECT CONTINUITY STEWARD OR SUPPORT STEWARD</div>
                  <input class="id-wiz-inp" v-model="wiz.partyBSearch" placeholder="Search by name or Aegis ID…" style="margin-bottom:8px;">
                  <div v-for="p in filteredPartyB" :key="p.id" class="id-party-card" :class="{ 'id-party-card-selected': wiz.partyB===p.id }" @click="wiz.partyB=p.id">
                    <div class="id-party-avatar" :style="{ background: p.color }">{{ p.initials }}</div>
                    <div><div class="id-party-name">{{ p.name }}</div><div class="id-party-meta">{{ p.meta }}</div></div>
                  </div>
                </div>
              </div>
              <div v-if="wiz.partyB" class="id-wiz-notice" style="background:rgba(232,169,74,.08);border-color:rgba(232,169,74,.4);color:var(--orange-dark);">Note: An active agreement of this type already exists with this party.</div>
              <div class="id-wiz-section-lbl">TERMS & DURATION</div>
              <div class="id-wiz-row">
                <div class="id-wiz-field"><label class="id-wiz-lbl">Effective Date <span class="id-req">*</span></label><input class="id-wiz-inp" type="date" v-model="wiz.effectiveDate"></div>
                <div class="id-wiz-field"><label class="id-wiz-lbl">Expiration Date</label><input class="id-wiz-inp" type="date" v-model="wiz.expirationDate"></div>
                <div class="id-wiz-field"><label class="id-wiz-lbl">Auto-Renew</label><select class="id-wiz-inp" v-model="wiz.autoRenew"><option>No – Manual renewal required</option><option>Yes – Annual auto-renew</option><option>Yes – Monthly auto-renew</option></select></div>
              </div>
              <div class="id-wiz-row">
                <div class="id-wiz-field"><label class="id-wiz-lbl">Jurisdiction / Governing Law</label><input class="id-wiz-inp" v-model="wiz.jurisdiction" placeholder="State of California, USA"></div>
                <div class="id-wiz-field"><label class="id-wiz-lbl">Dispute Resolution</label><select class="id-wiz-inp" v-model="wiz.dispute"><option>Aegis Platform Mediation first, then Arbitration</option><option>Binding Arbitration</option><option>Court Litigation</option></select></div>
              </div>
            </div>

            <!-- ── STEP 3: Clauses & Terms ── -->
            <div v-if="wizStep===3" class="id-wiz-body">
              <div class="id-wiz-notice id-wiz-notice-blue">All clauses are pre-filled from the legal standard template. Required clauses cannot be removed.</div>
              <div v-for="clause in clauses" :key="clause.title" class="id-clause-block">
                <button class="id-clause-head" @click="clause.open=!clause.open">
                  <div class="id-clause-num">{{ clause.num }}</div>
                  <span class="id-clause-title">{{ clause.title }}</span>
                  <span v-if="clause.required" class="id-clause-req">REQUIRED</span>
                  <span v-else class="id-clause-opt">CONFIGURE</span>
                  <svg class="id-clause-arrow" :class="{ open: clause.open }" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div v-show="clause.open" class="id-clause-body">
                  <div v-for="field in clause.fields" :key="field.label" class="id-wiz-field" style="margin-bottom:10px;">
                    <label class="id-wiz-lbl">{{ field.label }}</label>
                    <textarea v-if="field.type==='textarea'" class="id-wiz-ta" v-model="field.value" :placeholder="field.placeholder" rows="3"></textarea>
                    <select v-else-if="field.type==='select'" class="id-wiz-inp" v-model="field.value"><option v-for="o in field.options" :key="o">{{ o }}</option></select>
                    <input v-else class="id-wiz-inp" v-model="field.value" :placeholder="field.placeholder">
                  </div>
                </div>
              </div>
            </div>

            <!-- ── STEP 4: Review & Confirm ── -->
            <div v-if="wizStep===4" class="id-wiz-body">
              <div class="id-wiz-notice id-wiz-notice-green">Your agreement is ready to review. Please verify all details before proceeding to signature.</div>
              <div class="id-review-summary">
                <div class="id-review-col">
                  <div class="id-review-col-lbl">DOCUMENT</div>
                  <div class="id-review-row"><span class="id-review-k">Type</span><span class="id-review-v">MSA</span></div>
                  <div class="id-review-row"><span class="id-review-k">Category</span><span class="id-review-v">Provider – Continuity Steward</span></div>
                  <div class="id-review-row"><span class="id-review-k">Reference</span><span class="id-review-v">MSA-2025-015</span></div>
                </div>
                <div class="id-review-col">
                  <div class="id-review-col-lbl">PARTIES</div>
                  <div class="id-review-row"><span class="id-review-k">Party A</span><span class="id-review-v">Dr. S. Johnson</span></div>
                  <div class="id-review-row"><span class="id-review-k">Party B</span><span class="id-review-v">Dr. Priya Raman</span></div>
                  <div class="id-review-row"><span class="id-review-k">Role</span><span class="id-review-v">Secondary Continuity Steward</span></div>
                </div>
                <div class="id-review-col">
                  <div class="id-review-col-lbl">TERMS</div>
                  <div class="id-review-row"><span class="id-review-k">Effective</span><span class="id-review-v">1 Mar 2025</span></div>
                  <div class="id-review-row"><span class="id-review-k">Expires</span><span class="id-review-v">28 Feb 2026</span></div>
                  <div class="id-review-row"><span class="id-review-k">Auto-renew</span><span class="id-review-v">Annual</span></div>
                </div>
              </div>
              <div class="id-review-doc">
                <div class="id-review-doc-title">MASTER SERVICE AGREEMENT</div>
                <div class="id-review-doc-ref">Draft — REF: MSA-2025-015</div>
                <p class="id-review-doc-para"><strong>1. Scope of Services</strong><br>Continuity Steward is authorized to manage client referral workflows, coordinate with specialty practices, schedule appointments, supervise billing operations, and represent Provider in non-clinical communications.</p>
                <p class="id-review-doc-para"><strong>2. Confidentiality & HIPAA</strong><br>Continuity Steward shall comply with HIPAA and the HITECH Act. PHI access is Read-Only. A Business Associate Agreement is incorporated by reference.</p>
                <p class="id-review-doc-para"><strong>3. Compensation</strong><br>Provider shall compensate Continuity Steward at $7,500 USD/month, payable on the 1st of each month.</p>
              </div>
            </div>

            <!-- ── STEP 5: Send for Signature ── -->
            <div v-if="wizStep===5" class="id-wiz-body">
              <div class="id-wiz-notice id-wiz-notice-green">Agreement is ready to send! Configure signing options below.</div>
              <div class="id-sig-parties-grid">
                <div class="id-sig-party-col">
                  <div class="id-sig-party-lbl">PARTY A — PROVIDER (YOU)</div>
                  <div class="id-wiz-field"><label class="id-wiz-lbl">My Signing Action</label><select class="id-wiz-inp"><option>Sign first, then send to other party</option><option>Send to other party first</option><option>Sign simultaneously</option></select></div>
                </div>
                <div class="id-sig-party-col">
                  <div class="id-sig-party-lbl">PARTY B — CONTINUITY STEWARD</div>
                  <div class="id-wiz-field"><label class="id-wiz-lbl">Notification Method</label><select class="id-wiz-inp"><option>Email + Aegis In-App Notification</option><option>Email only</option><option>In-App only</option></select></div>
                </div>
              </div>
              <div class="id-wiz-row">
                <div class="id-wiz-field"><label class="id-wiz-lbl">Signature Deadline</label><input class="id-wiz-inp" type="date" v-model="sig.deadline"></div>
                <div class="id-wiz-field"><label class="id-wiz-lbl">Reminder Frequency</label><select class="id-wiz-inp" v-model="sig.reminder"><option>Every 2 days after sending</option><option>Every day</option><option>Every week</option><option>No reminders</option></select></div>
              </div>
              <div class="id-wiz-field"><label class="id-wiz-lbl">Personal Message</label><textarea class="id-wiz-ta" v-model="sig.message" rows="4" placeholder="e.g. Hi Marcus, please review and sign at your earliest convenience…"></textarea></div>
              <div class="id-sig-checks">
                <label class="id-sig-check-row"><input type="checkbox" v-model="sig.c1" style="accent-color:var(--gold-dark);"> All required clauses are complete</label>
                <label class="id-sig-check-row"><input type="checkbox" v-model="sig.c2" style="accent-color:var(--gold-dark);"> Party B's contact details verified</label>
                <label class="id-sig-check-row"><input type="checkbox" v-model="sig.c3" style="accent-color:var(--gold-dark);"> I confirm I am authorized to create &amp; send this agreement</label>
                <label class="id-sig-check-row"><input type="checkbox" v-model="sig.c4" style="accent-color:var(--gold-dark);"> I understand this agreement grants PHI access under HIPAA BAA terms</label>
              </div>
            </div>

            <!-- FOOTER -->
            <div class="id-wiz-footer">
              <button class="btn btn-outline btn-sm" @click="wizardOpen=false">Cancel</button>
              <button v-if="wizStep===4" class="id-wiz-save-btn" @click="showToast('Draft saved','success')">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Draft
              </button>
              <button v-if="wizStep===5" class="id-wiz-save-btn" @click="showToast('Draft saved','success')">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Draft
              </button>
              <div style="display:flex;align-items:center;gap:6px;margin-left:auto;">
                <span class="id-wiz-enc">🔒 Encrypted &amp; audit-logged</span>
                <button v-if="wizStep>1" class="id-wiz-back-btn" @click="wizStep--">← Back</button>
                <button v-if="wizStep<5" class="id-sign-btn" @click="wizStep++">{{ wizStep===4 ? 'Continue: Send for Signature →' : stepNextLabels[wizStep-1] }}</button>
                <button v-if="wizStep===5" class="id-sign-btn" :disabled="!sig.c1||!sig.c2||!sig.c3" @click="sendForSignature">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                  Send for Signature
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>

      <!-- BROWSE LIBRARY MODAL -->
      <Transition name="id-modal-fade">
        <div v-if="libraryOpen" class="id-modal-backdrop" @click.self="libraryOpen=false">
          <div class="id-modal">
            <div class="id-wiz-header">
              <div>
                <div class="id-wiz-title">Sample Templates</div>
                <div class="id-wiz-sub">Access sample templates — including MSAs, NDAs, SOWs, Continuity Plans, and MOUs — as starting points you can review and adapt to your preferences, practice needs, and professional or legal considerations.</div>
              </div>
              <button class="id-modal-x" @click="libraryOpen=false">×</button>
            </div>
            <div class="id-wiz-body">
              <div class="id-lib-grid">
                <div v-for="t in libraryTemplates" :key="t.title" class="id-lib-card" @click="libraryOpen=false; openWizard()">
                  <div class="id-lib-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                  <div class="id-lib-content">
                    <div class="id-lib-title">{{ t.title }}</div>
                    <div class="id-lib-sub">{{ t.sub }}</div>
                    <span class="id-lib-tag" :class="t.tag==='AEGIS'?'id-lib-aegis':'id-lib-custom'">{{ t.tag }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="id-wiz-footer">
              <button class="btn btn-outline btn-sm" @click="libraryOpen=false">Close</button>
              <button class="id-sign-btn" @click="libraryOpen=false; openWizard()">+ Start New from Template</button>
            </div>
          </div>
        </div>
      </Transition>

      <div class="id-toasts">
        <div v-for="t in toasts" :key="t.id" class="id-toast" :class="t.type">
          <svg v-if="t.type==='success'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span>{{ t.msg }}</span>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';

defineProps({ user: Object });

const searchQ = ref('');
const activeFilter = ref('all');

const toasts = ref([]);
let tid = 0;
function showToast(msg, type = 'info') {
  const id = ++tid;
  toasts.value.push({ id, msg, type });
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id); }, 3500);
}

// ── Doc modals ──
const dm = reactive({ type: null, doc: null });
const dmForm = reactive({ proposedChange:'', reason:'', effectiveDate:'', renewType:'same', renewEffective:'03/15/2025', renewExpiry:'03/14/2026', terminateReason:'', terminateDate:'', terminateConfirm:'', revokeReason:'', revokeConfirm:'', revokeReviewConfirm:'', severity:'Medium', disputeDate:'', disputeDesc:'', confidential:false, shareEmail:'', shareMessage:'', shareExpiry:'7', archiveReason:'' });

function openDocModal(type, doc) { dm.type = type; dm.doc = doc; }

const agreementHistory = [
  { date:'16 Jan 2026 · 10:02 AM', title:'Agreement Fully Executed', sub:'Both parties signed. Document archived & delivered.' },
  { date:'15 Jan 2026 · 9:55 AM', title:'Continuity Steward Signed', sub:'Marcus Chen applied electronic signature.' },
  { date:'13 Jan 2026 · 4:30 PM', title:'Sent for Signature', sub:'Provider sent agreement to Marcus Chen.' },
  { date:'6 May 2025 · 11:44 AM', title:'Amendment v1.2 Applied', sub:'Compensation clause updated. Both parties re-acknowledged.' },
];

const amendClauses = ['Clause 1 — Scope of Services','Clause 2 — Confidentiality & PHI','Clause 3 — Compensation','Clause 4 — Termination','Clause 5 — Liability & Insurance'];

const ndaItems = ['Client Records & PHI','Financial & Billing Data','Referral Protocols & Care Pathways','Business Strategy & Trade Secrets','Staff & Vendor Information'];

const actionItems = [
  { label:'View Full Agreement',      sub:'Read, print, or download PDF',                 action:'view',      danger:false, icon:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>' },
  { label:'Request Amendment',        sub:'Propose changes for mutual approval',           action:'amendment', danger:false, icon:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' },
  { label:'Renew Agreement',          sub:'Extend or update before expiry',                action:'renew',     danger:false, icon:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg>' },
  { label:'Attach BAA',               sub:'Add Business Associate Agreement',              action:'baa',       danger:false, icon:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>' },
  { label:'Attach NDA',               sub:'Append a Non-Disclosure Agreement',             action:'nda',       danger:false, icon:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>' },
  { label:'Download PDF',             sub:'Save signed copy to your device',               action:'download',  danger:false, icon:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>' },
  { label:'Share Agreement',          sub:'Send a copy or access link to a party',        action:'share',     danger:false, icon:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>' },
  { label:'View Audit Trail',         sub:'See all actions and changes on this document',  action:'audit',     danger:false, icon:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' },
  { label:'Configure NDA Addendum',   sub:'Attach or update NDA to this agreement',       action:'nda',       danger:false, icon:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>' },
  { label:'Archive Agreement',        sub:'Move to archive — agreement remains accessible', action:'archive',   danger:false, icon:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>' },
  { label:'Flag a Dispute',           sub:'Report a breach or violation',                  action:'dispute',   danger:true,  icon:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>' },
  { label:'Revoke Access',            sub:'Remove party access immediately',               action:'revoke',    danger:true,  icon:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>' },
  { label:'Terminate Agreement',      sub:'Suspend and archive this agreement',            action:'terminate', danger:true,  icon:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' },
  { label:'Revoke Review',            sub:'Immediately remove system access',              action:'revokeReview', danger:true, icon:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="23" y1="11" x2="17" y2="17"/><line x1="17" y1="11" x2="23" y2="17"/></svg>' },
];

function handleAction(action) {
  dm.type = null;
  setTimeout(() => {
    if (action === 'download') { showToast('Downloading PDF','info'); return; }
    dm.type = action;
  }, 50);
}

const auditEvents = [
  { id:1, type:'signature', typeLabel:'Signature', title:'Agreement Fully Executed', actor:'System', date:'16 Jan 2026 · 10:02 AM', detail:'Both parties signed. Document archived & delivered.' },
  { id:2, type:'signature', typeLabel:'Signature', title:'Continuity Steward Signed', actor:'Marcus Chen', date:'15 Jan 2026 · 9:55 AM', detail:'Electronic signature applied. IP: 192.168.x.x · Verified.' },
  { id:3, type:'action', typeLabel:'Action', title:'Sent for Signature', actor:'Dr. Sarah Johnson', date:'13 Jan 2026 · 4:30 PM', detail:'Agreement dispatched to Marcus Chen via email & in-app notification.' },
  { id:4, type:'amendment', typeLabel:'Amendment', title:'Amendment v1.2 Applied', actor:'Dr. Sarah Johnson', date:'6 May 2025 · 11:44 AM', detail:'Compensation clause updated. Both parties re-acknowledged.' },
  { id:5, type:'view', typeLabel:'View', title:'Agreement Viewed', actor:'Marcus Chen', date:'12 Jan 2026 · 2:15 PM', detail:null },
  { id:6, type:'download', typeLabel:'Download', title:'PDF Downloaded', actor:'Dr. Sarah Johnson', date:'16 Jan 2026 · 10:30 AM', detail:'Signed copy saved to device.' },
  { id:7, type:'view', typeLabel:'View', title:'Agreement Viewed', actor:'Dr. Sarah Johnson', date:'10 Jan 2026 · 9:00 AM', detail:null },
  { id:8, type:'action', typeLabel:'Created', title:'Agreement Created', actor:'Dr. Sarah Johnson', date:'6 Jan 2026 · 3:22 PM', detail:'Draft generated from MSA template (v2026.1).' },
];
const wizardOpen = ref(false);
const libraryOpen = ref(false);
const wizStep = ref(1);

const stepTitles    = ['Create New Agreement','Parties & Agreement Details','Clauses & Terms','Review & Confirm','Send for Signature'];
const stepSubs      = ['Select agreement type & category','Select parties and define the term','Configure all clauses and terms','Review the complete draft','Configure signing options and send'];
const stepNextLabels= ['Continue: Parties & Details →','Continue: Clauses & Terms →','Continue: Review & Confirm →','Continue: Send for Signature →'];
const stepLabels    = [['Agreement','Type'],['Parties &','Details'],['Clauses &','Terms'],['Review &','Confirm'],['Send for','Signature']];

function openWizard() { wizardOpen.value = true; wizStep.value = 1; }

const wiz = reactive({ category:'provider-cs', docType:'MSA – Master Service Agreement', reference:'', partyBSearch:'', partyB:null, effectiveDate:'', expirationDate:'', autoRenew:'No – Manual renewal required', jurisdiction:'State of California, USA', dispute:'Aegis Platform Mediation first, then Arbitration' });
const sig = reactive({ deadline:'', reminder:'Every 2 days after sending', message:'', c1:true, c2:true, c3:false, c4:false });

function sendForSignature() { wizardOpen.value = false; showToast('Agreement sent for signature!','success'); }

const agrCategories = [
  { value:'provider-cs', title:'Provider & Continuity Steward', sub:'MSA, SOW, NDR between you and a Continuity Steward', icon:'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>' },
  { value:'provider-ss', title:'Provider & Support Steward',    sub:'SLA, NDA between you and a Support Steward',        icon:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>' },
  { value:'team',        title:'Team Agreements (Facilitated)', sub:'MOU between Support Steward and Continuity Steward — you are facilitator', icon:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>' },
  { value:'triparty',    title:'Tri-Party (All Three Roles)',   sub:'Single MSA or MOU binding Provider, Continuity Steward and Support Steward', icon:'<circle cx="12" cy="5" r="2"/><circle cx="5" cy="19" r="2"/><circle cx="19" cy="19" r="2"/><path d="M12 7v6M12 13l-6 4M12 13l6 4"/>' },
];

const partyBOptions = [
  { id:1, initials:'MC', name:'Marcus Chen',     meta:'Primary Continuity Steward · LX-0042 · Active · Continuity Plan on file', color:'#a0813e' },
  { id:2, initials:'PR', name:'Dr. Priya Raman', meta:'Secondary Continuity Steward · LX-0051 · Active', color:'#6a4c8c' },
  { id:3, initials:'LJ', name:'Linda Johnson',   meta:'Admin Support Steward · SS-0011 · Active', color:'#4a7a6a' },
];
const filteredPartyB = computed(() => {
  const q = wiz.partyBSearch.toLowerCase();
  if (!q) return partyBOptions;
  return partyBOptions.filter(p => p.name.toLowerCase().includes(q) || p.meta.toLowerCase().includes(q));
});

// Clauses
const clauses = reactive([
  { num:1, title:'Scope of Services & Delegation of Authority', required:true, open:true, fields:[
    { label:'Authorized activities', type:'textarea', value:'', placeholder:'e.g. Continuity Steward is authorized to manage admin referrals…' },
    { label:'Explicit exclusions', type:'textarea', value:'', placeholder:'e.g. Continuity Steward may not modify clinical treatment plans…' },
  ]},
  { num:2, title:'Confidentiality & PHI Obligations (HIPAA)', required:true, open:false, fields:[
    { label:'PHI Access Level', type:'select', value:'Read-Only', options:['Read-Only','No Access','Full Access (BAA Required)'] },
    { label:'Confidentiality Duration', type:'select', value:'Duration of agreement only', options:['Duration of agreement only','2 years post-termination','Indefinite'] },
  ]},
  { num:3, title:'Compensation & Fee Structure', required:false, open:false, fields:[
    { label:'Model', type:'select', value:'Fixed Monthly Retainer', options:['Fixed Monthly Retainer','Hourly','Per-Event','No Compensation'] },
    { label:'Amount / Rate', type:'text', value:'', placeholder:'e.g. $2,500/mo' },
    { label:'Payment Cycle', type:'select', value:'Monthly', options:['Monthly','Bi-weekly','Per-event'] },
  ]},
  { num:4, title:'Termination & Exit Provisions', required:false, open:false, fields:[
    { label:'Notice Period', type:'select', value:'30 days', options:['14 days','30 days','60 days','90 days'] },
    { label:'Immediate Termination Grounds', type:'text', value:'HIPAA breach, fraud, gross negligence, loss of licensure', placeholder:'Grounds for immediate termination' },
  ]},
  { num:5, title:'Liability, Indemnification & Insurance', required:true, open:false, fields:[
    { label:'Liability Cap', type:'select', value:'Capped at 3 months fees paid', options:['Capped at 3 months fees paid','Capped at 6 months fees paid','Unlimited','None'] },
    { label:'Insurance Requirement', type:'text', value:'Professional Liability min $1M / $3M aggregate', placeholder:'Insurance requirement' },
  ]},
]);

// Library templates
const libraryTemplates = [
  { title:'Provider & Continuity Steward MSA', sub:'Standard Master Service Agreement · 8 clauses · HIPAA-ready', tag:'AEGIS' },
  { title:'Provider & Support Steward SLA',     sub:'Service Level Agreement with KPIs and response time SLAs', tag:'AEGIS' },
  { title:'Mutual NDA',                          sub:'Non-Disclosure · HIPAA BAA · 2yr or 5yr post-termination', tag:'AEGIS' },
  { title:'Support Steward & Continuity Steward MOU', sub:'Coordination Protocol · Escalation paths · Provider-overseen', tag:'AEGIS' },
  { title:'Tri-Party MSA',                       sub:'Provider + Continuity Steward + Support Steward · Roles, scope, dispute resolution', tag:'AEGIS' },
  { title:'Statement of Work (SOW)',              sub:'Project-specific scope for delegated activities', tag:'CUSTOM' },
];

const filterTabs = [
  { id:'all',       label:'All Documents', count:10 },
  { id:'provider-cs', label:'Provider & CS', count:5 },
  { id:'provider-ss', label:'Provider & SS', count:3 },
  { id:'ss-cs',     label:'SS & CS',       count:1 },
  { id:'triparty',  label:'Tri-Party',     count:2 },
  { id:'expiring',  label:'Expiring Soon', count:2 },
];

const p = (initials, color) => ({ initials, color });

const continuityDocs = reactive([
  { type:'BAA', title:'Business Associate Agreement (HIPAA BAA)', ref:'BAA-AEGIS-2026', status:'ACTIVE', audience:'Aegis / All Users', parties:null, needsSign:false, isDraft:false },
  { type:'MSA', title:'Master Service Agreement — Primary Continuity Steward', ref:'MSA-2025-101', status:'ACTIVE', audience:null, parties:[ p('JO','#a0813e'), p('CH','#6a4c8c') ], execution:'Fully executed', awaiting:null, needsSign:false, isDraft:false },
  { type:'MOU', title:'Continuity Steward MOU Template', ref:'MOU-AEGIS-2026', status:'ACTIVE', audience:'Aegis / All Users', parties:null, needsSign:false, isDraft:false },
  { type:'SOW', title:'Statement of Work — Cardiology Delegation', ref:'SOW-2025-108', status:'ACTIVE', audience:null, parties:[ p('JO','#a0813e'), p('CH','#6a4c8c') ], execution:null, awaiting:'Awaiting you · Overdue: All', needsSign:true, isDraft:false },
  { type:'FORMS', title:'Aegis Sample Forms Library', ref:'FORMS-AEGIS-2026', status:'ACTIVE', audience:'Aegis / All Users', parties:null, needsSign:false, isDraft:false },
  { type:'SLA', title:'Service Level Agreement — Administrative Support Steward', ref:'SLA-2024-118', status:'EXPIRING', audience:null, parties:[ p('JO','#a0813e'), p('LJ','#4a7a6a') ], execution:null, awaiting:'Expires in 18 days', needsSign:false, isDraft:false },
  { type:'NDA', title:'Non-Disclosure Agreement — Billing Support Steward', ref:'NDA-2025-011', status:'ACTIVE', audience:null, parties:[ p('JO','#a0813e'), p('RO','#7a5c4a') ], execution:'Fully executed', awaiting:null, needsSign:false, isDraft:false },
  { type:'MOU', title:'Memorandum of Understanding — Steward Coordination', ref:'MOU-2025-004', status:'ACTIVE', audience:null, parties:[ p('CH','#6a4c8c'), p('LJ','#4a7a6a') ], execution:'Fully executed', awaiting:null, needsSign:false, isDraft:false },
  { type:'TRI-PARTY MSA', title:'Tri-Party Master Service Agreement', ref:'TRI-2025-001', status:'ACTIVE', audience:'All three roles', parties:[ p('JO','#a0813e'), p('MC','#6a4c8c'), p('LJ','#4a7a6a') ], execution:'3 parties signed', awaiting:'Expires in 345 days', needsSign:false, isDraft:false },
  { type:'MSA · DRAFT', title:'Master Service Agreement — Tertiary Continuity Steward', ref:'MSA-DRAFT-2035', status:'DRAFT', audience:null, parties:[ p('JO','#a0813e'), p('RO','#7a5c4a') ], execution:null, awaiting:'Last edited 3 days ago', needsSign:false, isDraft:true },
]);

const supportingDocs = reactive([
  { title:'Continuity Plan — Amendment #2',      ref:'Amendment · Provider & Continuity Steward · Added Jan 6, 2025',  status:'EXECUTED'  },
  { title:'Support Steward Onboarding Reference', ref:'Reference material · Provider & Support Steward · Added Dec 2, 2024', status:'REFERENCE' },
]);
</script>

<style scoped>
.id-hero { display:flex; align-items:flex-start; justify-content:space-between; gap:20px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:22px 26px; margin-bottom:12px; box-shadow:var(--shadow-xs); flex-wrap:wrap; }
.id-eyebrow { font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--gold-dark); margin-bottom:5px; }
.id-title { font-family:var(--font-serif); font-size:26px; font-weight:700; color:var(--text); margin:0 0 6px; }
.id-sub { font-size:12.5px; color:var(--text-3); margin:0; line-height:1.5; max-width:620px; }
.id-hero-actions { display:flex; gap:8px; flex-shrink:0; align-items:center; flex-wrap:wrap; }
.id-btn-icon { display:inline-flex; align-items:center; gap:6px; }

.id-action-banner { display:flex; align-items:flex-start; gap:12px; padding:12px 16px; background:rgba(232,169,74,.08); border:1px solid rgba(232,169,74,.4); border-radius:var(--radius-lg,14px); margin-bottom:12px; flex-wrap:wrap; }
.id-action-text { flex:1; min-width:0; }
.id-action-text strong { font-size:13.5px; font-weight:700; color:var(--orange-dark); }
.id-action-sub { font-size:12px; color:var(--orange-dark); opacity:.85; margin-top:2px; }
.id-action-btns { display:flex; gap:8px; align-items:center; flex-shrink:0; }
.id-sign-btn { display:inline-flex; align-items:center; gap:5px; padding:6px 14px; font-size:12.5px; font-weight:700; background:var(--gold-dark); color:#fff; border:none; border-radius:6px; cursor:pointer; transition:background .15s; white-space:nowrap; }
.id-sign-btn:hover { background:var(--gold); }
.id-sign-sm { padding:4px 10px; font-size:11.5px; }

.id-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:14px; }
.id-stat { display:flex; align-items:center; gap:14px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:16px 18px; box-shadow:var(--shadow-xs); }
.id-stat-icon { width:36px; height:36px; border-radius:8px; background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.id-stat-green .id-stat-icon { background:var(--green-light); color:var(--green-dark); }
.id-stat-gold  .id-stat-icon { background:rgba(160,129,62,.1); color:var(--gold-dark); }
.id-stat-red   .id-stat-icon { background:var(--red-light); color:var(--red); }
.id-stat-val { font-family:var(--font-serif); font-size:22px; font-weight:700; color:var(--text); line-height:1; }
.id-stat-lbl { font-size:11px; color:var(--text-4); margin-top:3px; }

.id-filter-tabs { display:flex; gap:2px; border-bottom:1px solid var(--border); margin-bottom:10px; overflow-x:auto; }
.id-ftab { display:inline-flex; align-items:center; gap:6px; padding:8px 13px; font-size:12.5px; font-weight:500; color:var(--text-3); background:transparent; border:none; border-bottom:2px solid transparent; cursor:pointer; transition:color .15s,border-color .15s; margin-bottom:-1px; white-space:nowrap; }
.id-ftab:hover { color:var(--text); }
.id-ftab.active { color:var(--text); font-weight:600; border-bottom-color:var(--gold-dark); }
.id-ftab-count { font-size:10.5px; font-weight:700; background:var(--surface-3); color:var(--text-2); border-radius:99px; padding:1px 6px; }
.id-ftab-count.active { background:var(--gold-dark); color:#fff; }

.id-search-bar { display:flex; align-items:center; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
.id-search-wrap { position:relative; flex:1; min-width:200px; }
.id-search { width:100%; padding:8px 12px 8px 30px; font-size:12.5px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:6px; outline:none; box-sizing:border-box; }
.id-search:focus { border-color:var(--gold-dark); }
.id-sel { padding:7px 10px; font-size:12.5px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:6px; outline:none; cursor:pointer; appearance:none; -webkit-appearance:none; }
.id-export-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 12px; font-size:12px; font-weight:600; color:var(--text-3); background:var(--surface); border:1.5px solid var(--border); border-radius:6px; cursor:pointer; flex-shrink:0; transition:all .15s; }
.id-export-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }

.id-section { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); margin-bottom:14px; box-shadow:var(--shadow-xs); overflow:hidden; }
.id-section-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:16px 20px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
.id-section-title { font-family:var(--font-sans); font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px; }
.id-section-sub   { font-size:12px; color:var(--text-4); }
.id-section-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

.id-doc-row { display:flex; align-items:center; gap:12px; padding:12px 20px; border-bottom:1px solid var(--border); transition:background .12s; }
.id-doc-row:last-child { border-bottom:none; }
.id-doc-row:hover { background:var(--surface-2); }
.id-doc-sign { background:rgba(160,129,62,.04); }
.id-doc-icon { color:var(--text-4); flex-shrink:0; display:flex; align-items:center; }
.id-doc-info { flex:1; min-width:0; }
.id-doc-type-label { font-size:9px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-4); margin-bottom:2px; }
.id-doc-title { font-family:var(--font-sans); font-size:13px; font-weight:600; color:var(--text); margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.id-doc-ref { font-size:11.5px; color:var(--text-4); margin-bottom:4px; }
.id-doc-audience { font-size:11.5px; color:var(--text-4); }
.id-doc-parties { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.id-party-chip { width:22px; height:22px; border-radius:50%; color:#fff; font-size:9px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
.id-execution-badge { font-size:10.5px; font-weight:600; color:var(--green-dark); }
.id-awaiting-badge  { font-size:10.5px; font-weight:600; color:var(--orange-dark); }

.id-doc-status-area { display:flex; align-items:center; gap:6px; flex-shrink:0; }
.id-status-badge { font-size:9.5px; font-weight:700; letter-spacing:.05em; border-radius:99px; padding:2px 8px; }
.id-status-active   { background:rgba(76,175,125,.12); color:var(--green-dark); border:1px solid var(--soft-green,rgba(34,119,68,.3)); }
.id-status-expiring { background:rgba(160,45,34,.1); color:var(--red); border:1px solid var(--soft-red,rgba(160,45,34,.35)); }
.id-status-draft    { background:var(--surface-3); color:var(--text-3); border:1px solid var(--border-dark); }
.id-renew-btn { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; font-size:11.5px; font-weight:700; background:var(--red-light); color:var(--red); border:1px solid var(--soft-red,rgba(160,45,34,.35)); border-radius:6px; cursor:pointer; transition:all .15s; }
.id-renew-btn:hover { background:var(--red); color:#fff; }
.id-edit-btn { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; font-size:11.5px; font-weight:700; color:var(--text-2); background:var(--surface); border:1.5px solid var(--border); border-radius:6px; cursor:pointer; transition:all .15s; }
.id-edit-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }

.id-doc-actions { display:flex; gap:4px; flex-shrink:0; }
.id-act-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; border:1px solid var(--border); background:var(--surface); color:var(--text-3); cursor:pointer; transition:all .15s; }
.id-act-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.id-act-danger:hover { border-color:var(--red); color:var(--red); background:var(--red-light); }

.id-add-dashed { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; width:100%; padding:28px 20px; border:2px dashed var(--border); border-radius:var(--radius-lg,14px); background:transparent; cursor:pointer; transition:all .18s; margin-bottom:14px; text-align:center; }
.id-add-dashed:hover { border-color:var(--gold-dark); background:rgba(160,129,62,.04); }
.id-add-icon-wrap { width:44px; height:44px; border-radius:50%; background:var(--gold-dark); color:#fff; display:flex; align-items:center; justify-content:center; }
.id-add-title { font-size:14px; font-weight:700; color:var(--text); }
.id-add-sub { font-size:12px; color:var(--text-4); }

.id-toasts { position:fixed; bottom:22px; right:22px; z-index:4000; display:flex; flex-direction:column; gap:10px; }
.id-toast { display:flex; align-items:center; gap:9px; padding:11px 16px; border-radius:var(--radius); background:var(--text); color:var(--text-inverted); font-size:13px; font-weight:600; box-shadow:var(--shadow-lg); max-width:360px; }
.id-toast.success { background:var(--green-dark); }

/* ─── RESPONSIVE ─────────────────────────────────────────────── */

/* Tablet — 860px */
@media (max-width:860px) {
  .id-stats { grid-template-columns:repeat(2,1fr); }
  .id-hero { flex-direction:column; gap:14px; }
  .id-hero-actions { width:100%; justify-content:flex-end; }
  .id-action-banner { flex-wrap:wrap; }
  .id-action-btns { width:100%; }
  .id-section-header { flex-direction:column; }
  .id-section-actions { width:100%; justify-content:flex-end; }
  .id-doc-row { flex-wrap:wrap; gap:8px; padding:12px 14px; }
  .id-doc-info { min-width:0; width:100%; flex-basis:100%; }
  .id-doc-title { white-space:normal; }
  .id-doc-status-area { order:3; }
  .id-doc-actions { order:4; margin-left:auto; }
}

/* Mobile — 600px */
@media (max-width:600px) {
  .id-stats { grid-template-columns:1fr 1fr; gap:8px; }
  .id-stat { padding:12px 14px; gap:10px; }
  .id-stat-val { font-size:20px; }
  .id-hero { padding:16px 18px; }
  .id-title { font-size:22px; }
  .id-hero-actions { gap:6px; }
  .id-hero-actions .btn { font-size:11.5px; padding:6px 10px; }
  .id-action-banner { padding:10px 12px; gap:8px; }
  .id-action-btns { flex-wrap:wrap; gap:6px; }
  .id-filter-tabs { gap:0; }
  .id-ftab { padding:7px 10px; font-size:12px; }
  .id-search-bar { gap:6px; }
  .id-sel { min-width:0; flex:1; font-size:12px; padding:6px 8px; }
  .id-export-btn { padding:6px 10px; font-size:11.5px; }
  .id-search-wrap { flex-basis:100%; }
  .id-doc-row { padding:10px 12px; }
  .id-doc-status-area { flex-wrap:wrap; }
  .id-sign-btn { font-size:12px; padding:5px 11px; }
  .id-add-dashed { padding:20px 14px; }
}

/* Small mobile — 420px */
@media (max-width:420px) {
  .id-stats { grid-template-columns:1fr; }
  .id-hero-actions { flex-direction:column; align-items:stretch; }
  .id-hero-actions .btn { width:100%; justify-content:center; }
  .id-section-actions { flex-direction:column; align-items:stretch; }
  .id-section-actions .btn,
  .id-section-actions .id-sign-btn { width:100%; justify-content:center; text-align:center; }
}

/* ── Wizard & Library Modals ── */
.id-modal-backdrop { position:fixed; inset:0; z-index:1000; background:rgba(30,28,26,.45); backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center; padding:16px; }
.id-modal    { background:var(--surface); border:1px solid var(--border); border-radius:14px; box-shadow:0 24px 64px rgba(30,28,26,.2); width:100%; max-width:560px; max-height:90vh; display:flex; flex-direction:column; overflow:hidden; }
.id-modal-xl { max-width:740px; }
.id-modal-x { background:none; border:1px solid var(--border); border-radius:6px; width:28px; height:28px; font-size:18px; line-height:1; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text-3); flex-shrink:0; }
.id-modal-x:hover { border-color:var(--text); color:var(--text); }
.id-wiz-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:18px 22px 14px; border-bottom:1px solid var(--border); flex-shrink:0; }
.id-wiz-title { font-family:var(--font-serif); font-size:18px; font-weight:700; color:var(--text); margin-bottom:3px; }
.id-wiz-sub { font-size:12px; color:var(--text-4); line-height:1.5; max-width:580px; }
.id-wiz-footer { display:flex; align-items:center; gap:8px; padding:14px 22px; border-top:1px solid var(--border); background:var(--surface-2); flex-shrink:0; }
.id-wiz-enc { font-size:11.5px; color:var(--text-4); }
.id-wiz-body { padding:18px 22px; overflow-y:auto; display:flex; flex-direction:column; gap:14px; flex:1; }
.id-wiz-q { font-family:var(--font-sans); font-size:15px; font-weight:600; color:var(--text); }
.id-wiz-field-label { font-size:12px; font-weight:600; color:var(--text-2); }
.id-req { color:var(--red); }
.id-wiz-lbl { font-size:11.5px; font-weight:600; color:var(--text-2); margin-bottom:4px; display:block; }
.id-wiz-inp { width:100%; padding:9px 12px; font-size:13.5px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:8px; outline:none; box-sizing:border-box; transition:border-color .15s; }
.id-wiz-inp:focus { border-color:var(--gold-dark); }
.id-wiz-ta { width:100%; padding:9px 12px; font-size:13px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:8px; outline:none; resize:vertical; min-height:80px; line-height:1.6; box-sizing:border-box; transition:border-color .15s; }
.id-wiz-ta:focus { border-color:var(--gold-dark); }
.id-wiz-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; }
.id-wiz-field { display:flex; flex-direction:column; gap:4px; }
.id-wiz-section-lbl { font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-4); padding-top:4px; border-top:1px solid var(--border); }
.id-wiz-save-btn { display:inline-flex; align-items:center; gap:6px; padding:6px 13px; font-size:12.5px; font-weight:600; color:var(--text-2); background:var(--surface); border:1.5px solid var(--border); border-radius:6px; cursor:pointer; transition:all .15s; }
.id-wiz-save-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.id-wiz-back-btn { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; font-size:12.5px; font-weight:600; color:var(--text-2); background:var(--surface); border:1.5px solid var(--border); border-radius:6px; cursor:pointer; transition:all .15s; }
.id-wiz-back-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }
.id-wiz-notice { display:flex; align-items:flex-start; gap:9px; padding:10px 13px; border-radius:8px; font-size:12.5px; line-height:1.5; }
.id-wiz-notice-blue  { background:var(--blue-light); border:1px solid var(--soft-blue); color:var(--blue-dark); }
.id-wiz-notice-green { background:var(--green-light); border:1px solid var(--soft-green,rgba(34,119,68,.3)); color:var(--green-dark); }

/* Step progress */
.id-wiz-progress { display:flex; align-items:flex-start; padding:14px 22px; border-bottom:1px solid var(--border); gap:0; flex-shrink:0; overflow-x:auto; }
.id-wiz-step { display:flex; flex-direction:column; align-items:center; position:relative; min-width:90px; }
.id-wiz-circle { width:26px; height:26px; border-radius:50%; border:2px solid var(--border); background:var(--surface); color:var(--text-4); font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all .2s; }
.id-wiz-step.done .id-wiz-circle  { background:var(--gold-dark); border-color:var(--gold-dark); color:#fff; }
.id-wiz-step.active .id-wiz-circle { background:var(--gold-dark); border-color:var(--gold-dark); color:#fff; }
.id-wiz-label { font-size:10px; text-align:center; color:var(--text-4); margin-top:4px; line-height:1.3; }
.id-wiz-label span { color:var(--text-4); }
.id-wiz-step.active .id-wiz-label { color:var(--text); font-weight:600; }
.id-wiz-step.done  .id-wiz-label { color:var(--gold-dark); }
.id-wiz-line { position:absolute; top:13px; left:calc(50% + 13px); width:calc(100% - 26px); height:2px; background:var(--border); z-index:0; }
.id-wiz-line.done { background:var(--gold-dark); }

/* Step 1 category grid */
.id-agr-cats { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.id-agr-cat { display:flex; align-items:flex-start; gap:10px; padding:12px 14px; border:1.5px solid var(--border); border-radius:10px; cursor:pointer; transition:border-color .15s,background .15s; }
.id-agr-cat.selected { border-color:var(--gold-dark); background:rgba(160,129,62,.06); }
.id-agr-cat:hover { border-color:var(--gold-dark); }
.id-agr-cat-icon { color:var(--gold-dark); flex-shrink:0; margin-top:1px; }
.id-agr-cat-title { font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px; }
.id-agr-cat-sub   { font-size:11.5px; color:var(--text-4); line-height:1.4; }

/* Step 2 parties */
.id-wiz-parties-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.id-party-col-lbl { font-size:9.5px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-4); margin-bottom:8px; }
.id-party-card { display:flex; align-items:center; gap:10px; padding:10px 12px; border:1.5px solid var(--border); border-radius:8px; cursor:pointer; transition:border-color .15s; margin-bottom:5px; }
.id-party-card-selected { border-color:var(--gold-dark); background:rgba(160,129,62,.05); }
.id-party-card:hover { border-color:var(--gold-dark); }
.id-party-avatar { width:30px; height:30px; border-radius:50%; background:var(--gold-dark); color:#fff; font-size:10px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.id-party-name { font-size:13px; font-weight:600; color:var(--text); }
.id-party-meta { font-size:11px; color:var(--text-4); }

/* Step 3 clauses */
.id-clause-block { border:1px solid var(--border); border-radius:8px; overflow:hidden; margin-bottom:4px; }
.id-clause-head { display:flex; align-items:center; gap:8px; width:100%; padding:11px 14px; background:transparent; border:none; cursor:pointer; text-align:left; transition:background .12s; }
.id-clause-head:hover { background:var(--surface-2); }
.id-clause-num { width:22px; height:22px; border-radius:50%; background:var(--gold-dark); color:#fff; font-size:10px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.id-clause-title { flex:1; font-size:13px; font-weight:600; color:var(--text); }
.id-clause-req { font-size:9.5px; font-weight:700; color:var(--red); background:var(--red-light); border-radius:99px; padding:2px 7px; }
.id-clause-opt { font-size:9.5px; font-weight:700; color:var(--gold-dark); background:rgba(160,129,62,.1); border-radius:99px; padding:2px 7px; }
.id-clause-arrow { flex-shrink:0; color:var(--text-4); transition:transform .2s ease; }
.id-clause-arrow.open { transform:rotate(180deg); }
.id-clause-body { padding:14px; border-top:1px solid var(--border); display:flex; flex-direction:column; gap:10px; }

/* Step 4 review */
.id-review-summary { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:14px; }
.id-review-col { background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:12px 14px; }
.id-review-col-lbl { font-size:9.5px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-4); margin-bottom:8px; }
.id-review-row { display:flex; justify-content:space-between; gap:8px; padding:3px 0; font-size:12px; }
.id-review-k { color:var(--text-4); }
.id-review-v { color:var(--text); font-weight:600; text-align:right; }
.id-review-doc { border:1px solid var(--border); border-radius:8px; padding:16px 18px; max-height:200px; overflow-y:auto; }
.id-review-doc-title { font-family:var(--font-serif); font-size:14px; font-weight:700; text-align:center; margin-bottom:4px; color:var(--text); }
.id-review-doc-ref { font-size:11.5px; color:var(--text-4); text-align:center; margin-bottom:12px; }
.id-review-doc-para { font-size:12.5px; color:var(--text-2); line-height:1.65; margin-bottom:10px; }

/* Step 5 signature */
.id-sig-parties-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.id-sig-party-col { background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:12px 14px; }
.id-sig-party-lbl { font-size:9.5px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-4); margin-bottom:10px; }
.id-sig-checks { display:flex; flex-direction:column; gap:8px; }
.id-sig-check-row { display:flex; align-items:center; gap:9px; font-size:13px; color:var(--text-2); cursor:pointer; }

/* Library */
.id-lib-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.id-lib-card { display:flex; align-items:flex-start; gap:10px; padding:14px; border:1.5px solid var(--border); border-radius:10px; cursor:pointer; transition:border-color .15s,background .15s; }
.id-lib-card:hover { border-color:var(--gold-dark); background:rgba(160,129,62,.04); }
.id-lib-icon { color:var(--gold-dark); flex-shrink:0; margin-top:2px; }
.id-lib-title { font-size:13px; font-weight:700; color:var(--text); margin-bottom:3px; }
.id-lib-sub   { font-size:11.5px; color:var(--text-4); line-height:1.4; margin-bottom:6px; }
.id-lib-tag   { font-size:9px; font-weight:700; padding:2px 7px; border-radius:99px; }
.id-lib-aegis  { background:rgba(160,129,62,.1); color:var(--gold-dark); border:1px solid var(--fade-gold); }
.id-lib-custom { background:var(--surface-3); color:var(--text-3); border:1px solid var(--border-dark); }

/* Modal fade */
.id-modal-fade-enter-active, .id-modal-fade-leave-active { transition:opacity .2s ease; }
.id-modal-fade-enter-active .id-modal, .id-modal-fade-leave-active .id-modal { transition:transform .2s ease; }
.id-modal-fade-enter-from, .id-modal-fade-leave-to { opacity:0; }
.id-modal-fade-enter-from .id-modal { transform:translateY(-10px) scale(0.98); }

/* Audit trail */
.id-audit-filters { display:flex; gap:8px; margin-bottom:14px; flex-wrap:wrap; }
.id-audit-list { display:flex; flex-direction:column; gap:0; }
.id-audit-row { display:flex; align-items:flex-start; gap:12px; padding:11px 0; border-bottom:1px solid var(--border); }
.id-audit-row:last-child { border-bottom:none; }
.id-audit-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; margin-top:4px; background:var(--border-dark); }
.id-audit-dot.audit-signature { background:var(--green-dark); }
.id-audit-dot.audit-view { background:rgba(80,120,190,.6); }
.id-audit-dot.audit-download { background:var(--gold-dark); }
.id-audit-dot.audit-amendment { background:var(--orange-dark); }
.id-audit-dot.audit-action { background:var(--text-4); }
.id-audit-info { flex:1; min-width:0; }
.id-audit-title { font-size:13px; font-weight:600; color:var(--text); margin-bottom:2px; }
.id-audit-meta { font-size:11.5px; color:var(--text-4); }
.id-audit-detail { font-size:11.5px; color:var(--text-3); margin-top:3px; }
.id-audit-badge { font-size:9px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:2px 8px; border-radius:var(--radius-full); flex-shrink:0; margin-top:2px; }
.id-audit-badge.audit-badge-signature { background:var(--green-light); color:var(--green-dark); }
.id-audit-badge.audit-badge-view { background:rgba(80,120,190,.1); color:#4a6ba8; }
.id-audit-badge.audit-badge-download { background:rgba(160,129,62,.1); color:var(--gold-dark); }
.id-audit-badge.audit-badge-amendment { background:rgba(232,169,74,.12); color:var(--orange-dark); }
.id-audit-badge.audit-badge-action { background:var(--surface-3); color:var(--text-3); }

/* Doc view modal */
.id-view-meta-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:14px; }
.id-view-meta-col { background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px 12px; }
.id-view-meta-lbl { font-size:9px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-4); margin-bottom:6px; }
.id-view-sig-name { font-family:var(--font-serif); font-size:15px; font-style:italic; color:var(--text); margin-bottom:2px; }
.id-view-doc-body { border:1px solid var(--border); border-radius:8px; padding:16px 18px; max-height:200px; overflow-y:auto; }
.id-view-history { margin-top:14px; }
.id-view-history-title { font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:10px; }
.id-view-hist-row { display:flex; gap:10px; padding:8px 0; border-bottom:1px solid var(--border); }
.id-view-hist-row:last-child { border-bottom:none; }
.id-view-hist-dot { width:8px; height:8px; border-radius:50%; background:var(--gold-dark); flex-shrink:0; margin-top:4px; }
.id-view-hist-date { font-size:10.5px; color:var(--text-4); margin-bottom:2px; }
.id-view-hist-title { font-size:12.5px; font-weight:600; color:var(--text); }
.id-view-hist-sub { font-size:11.5px; color:var(--text-4); }

/* Actions menu */
.id-action-item { display:flex; align-items:center; gap:12px; padding:10px 10px; border-radius:8px; cursor:pointer; transition:background .12s; }
.id-action-item:hover { background:var(--surface-2); }
.id-action-item-icon { color:var(--text-4); flex-shrink:0; display:flex; align-items:center; }
.id-action-item-title { font-size:13px; font-weight:600; color:var(--text); margin-bottom:1px; }
.id-action-item-sub { font-size:11.5px; color:var(--text-4); }
.id-action-danger .id-action-item-title { color:var(--red); }
.id-action-danger .id-action-item-icon { color:var(--red); }

/* Upload zone */
.id-upload-zone { border:2px dashed var(--border); border-radius:8px; padding:20px; display:flex; flex-direction:column; align-items:center; gap:6px; color:var(--text-4); font-size:12.5px; cursor:pointer; transition:border-color .15s; }
.id-upload-zone:hover { border-color:var(--gold-dark); color:var(--gold-dark); }

/* Renew options */
.id-renew-options { display:flex; flex-direction:column; gap:6px; }
.id-renew-opt { display:flex; align-items:flex-start; gap:10px; padding:11px 13px; border:1.5px solid var(--border); border-radius:8px; cursor:pointer; transition:border-color .15s; }
.id-renew-opt.selected { border-color:var(--gold-dark); background:rgba(160,129,62,.05); }

/* Revoke table */
.id-revoke-table { border:1px solid var(--border); border-radius:8px; overflow:hidden; margin-bottom:12px; }
.id-revoke-row { display:flex; align-items:center; justify-content:space-between; padding:9px 14px; border-bottom:1px solid var(--border); font-size:12.5px; color:var(--text-2); }
.id-revoke-row:last-child { border-bottom:none; }
.id-revoke-term { font-size:11px; font-weight:700; color:var(--red); }

/* Severity buttons */
.id-severity-btns { display:flex; gap:6px; flex-wrap:wrap; }
.id-severity-btn { flex:1; min-width:60px; padding:8px; font-size:12px; font-weight:600; border:1.5px solid var(--border); border-radius:8px; background:var(--surface-2); color:var(--text-3); cursor:pointer; transition:all .15s; }
.id-severity-btn.active { border-color:var(--gold-dark); background:rgba(160,129,62,.08); color:var(--gold-dark); }
.id-severity-btn.sev-high.active { border-color:var(--orange-dark); background:rgba(232,169,74,.1); color:var(--orange-dark); }
.id-severity-btn.sev-critical.active { border-color:var(--red); background:var(--red-light); color:var(--red); }

/* ── Modal Responsive ──────────────────────────────────────── */

/* Tablet modals — 740px */
@media (max-width:740px) {
  .id-modal-xl { max-width:100%; }
  .id-view-meta-grid { grid-template-columns:1fr 1fr; }
  .id-review-summary { grid-template-columns:1fr 1fr; }
  .id-sig-parties-grid { grid-template-columns:1fr; gap:8px; }
  .id-wiz-parties-grid { grid-template-columns:1fr; gap:8px; }
  .id-agr-cats { grid-template-columns:1fr; }
  .id-lib-grid { grid-template-columns:1fr; }
}

/* Mobile modals — 560px: slide-up sheet */
@media (max-width:560px) {
  .id-modal-backdrop { padding:0; align-items:flex-end; }
  .id-modal { max-width:100%; max-height:92vh; border-radius:18px 18px 0 0; }
  .id-wiz-header { padding:12px 16px 10px; }
  .id-wiz-title { font-size:16px; }
  .id-wiz-sub { font-size:11.5px; }
  .id-wiz-body { padding:14px 16px; gap:12px; }
  .id-wiz-footer { padding:10px 16px; gap:6px; flex-wrap:wrap; }
  .id-wiz-footer .btn,
  .id-wiz-footer .id-sign-btn,
  .id-wiz-footer .id-wiz-save-btn,
  .id-wiz-footer .id-wiz-back-btn,
  .id-wiz-footer .ssm-red-btn { width:100%; justify-content:center; text-align:center; }
  .id-wiz-footer > div[style] { width:100%; display:flex; flex-direction:column; gap:6px; }
  .id-view-meta-grid { grid-template-columns:1fr 1fr; gap:8px; }
  .id-review-summary { grid-template-columns:1fr; }
  .id-wiz-progress { padding:10px 14px; }
  .id-wiz-step { min-width:64px; }
  .id-wiz-label { font-size:9px; }
  .id-wiz-row { grid-template-columns:1fr; }
  .id-action-item { padding:10px 8px; gap:10px; }
  .id-action-item-sub { font-size:11px; }
  .id-revoke-row { font-size:12px; padding:8px 12px; }
  .id-audit-filters { flex-direction:column; }
  .id-audit-filters select { width:100%; box-sizing:border-box; }
}

/* Extra-small modals — 380px */
@media (max-width:380px) {
  .id-view-meta-grid { grid-template-columns:1fr; }
  .id-wiz-title { font-size:15px; }
  .id-severity-btn { font-size:11px; padding:6px 4px; }
  .id-wiz-inp,
  .id-wiz-ta { font-size:13px; }
  .id-action-item-title { font-size:12.5px; }
}
</style>
