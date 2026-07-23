# Important Documents — Comprehensive Build Plan

**Date:** 2026-07-20
**Scope:** Provider portal `/provider/important-documents` — full rebuild aligned with the completed Continuity Practice module (Continuity Plan, Continuity Stewards, Support Stewards)
**Source of design:** `PROMPT_1_DESIGN.md`, `AEGIS_VUE_RULES.md`, `CONTINUITY_PRACTICE_MODULE_SPEC.md`
**Delivery:** Multi-wave — each wave is independently shippable in 1.5–3 hours

---

## 0. Executive summary

Important Documents is the **agreement + amendment archive** for the practitioner's continuity practice. It is the legal spine that connects the four other continuity pages:

```
                   ┌─────────────────────┐
                   │  Continuity Plan    │ ← plan_id anchor
                   └──────────┬──────────┘
                              │
                              │ every document belongs to a plan
                              ▼
      ┌───────────────────────────────────────────────┐
      │            Important Documents                │
      │  (agreements, amendments, SOWs, retainers)    │
      └─────┬───────────┬─────────────┬───────────────┘
            │           │             │
            ▼           ▼             ▼
      ┌──────────┐ ┌──────────┐ ┌────────────┐
      │   CS     │ │   SS     │ │   Vault    │ ← file storage
      └──────────┘ └──────────┘ └────────────┘
        signed by    signed by    optional link
        CS parties   SS parties   for file backing
```

Every document row has:
- **plan_id** — the plan it belongs to
- **doc_type** — SOW / retainer / amendment / attestation / cs-agreement / ss-agreement
- **party_b_id** — the counterparty (a CS or SS user_id from plan_stewards)
- **status** — draft / pending_sign / countersign_pending / active / expiring / expired / archived / terminated
- **holder_steward_id** — the plan_steward FK the doc is bound to (drives Cancel-if-CS-removed logic)
- **file_ref** — optional pointer to a vault_item (for uploaded PDFs)

---

## 1. Current state — what exists

### 1.1 Backend inventory
| Component | Path | Lines | Notes |
|---|---|---|---|
| Controller | `app/Http/Controllers/Provider/DocumentsController.php` | 405 | `index`, `request`, `sign`, `remind`, `archive`, `upload` |
| Service | `app/Services/DocumentService.php` | 318 | `create`, `sign`, `countersign`, `request`, `remind`, `archive`, `getForPlan` |
| Model | `app/Models/ContinuityDocument.php` | 68 | plan_id, party_b_id, holder_steward_id, is_supporting, related_to, auto_renew |
| Policy | `app/Policies/ContinuityDocumentPolicy.php` | present | view/sign/countersign/archive/request gates |
| Seeder | `database/seeders/DocumentSeeder.php` | present | Sarah has 4 seed docs |
| Migration | `2024_01_01_000022_create_continuity_documents_table.php` + `2026_07_13_000001_fix_continuity_documents_for_portal.php` | — | Portal fields patched in |

### 1.2 Frontend inventory
| Component | Path | Lines |
|---|---|---|
| Page | `resources/js/pages/provider/ImportantDocuments.vue` | 1,592 |
| CS view | `resources/js/pages/continuity-steward/documents/*` | present |
| SS view | `resources/js/pages/support-steward/ImportantDocuments.vue` | 50 (basic list) |

### 1.3 Current tabs (segmented pill strip)
- All Documents
- Provider & CS (pe)
- Provider & SS (pd)
- SS & CS (de)
- Tri-Party (tri)
- Expiring Soon (30d)

### 1.4 Existing modals (12 total)
- `templateModal` — Sample Templates browser
- `exportModal` — Export bundle
- `addDocumentModal` — Supporting doc upload
- `viewAgreementModal` — Full agreement viewer
- `agreementActionsModal` — More actions (View, Amend, Terminate)
- `signatureModal` — Full-name typed signature
- `renewalModal` — Renew agreement
- `sendForSignatureModal` — Send to counterparty
- `amendmentModal` — Draft amendment
- `terminateModal` — Terminate with reason
- `draftSaveModal` — Save as draft during wizard
- Wizard steps (4-step): Type → Parties → Clauses → Review

---

## 2. Impact analysis — how the 3 completed pages affect this page

### 2.1 Continuity Plan → Important Documents
- **Signing the plan** creates a `plan_agreement` document (auto-generated, immutable).
- **Amending the plan** (e.g. changing incident configs, adding stewards, task changes) creates an `amendment` document that BOTH the plan holder AND the affected CS must sign.
- **Annual re-attestation** creates an `attestation` document each year.
- **Plan status** drives which documents can be created (must be `active` to create most doc types).

### 2.2 Continuity Stewards → Important Documents
- **Designating a new CS** (Sign Retainer) auto-generates a `cs_retainer_agreement` document in `pending_sign` state.
- **Fee amendment** on an existing CS creates a `fee_amendment` document with `holder_steward_id` set — requires CS countersignature.
- **Suspending or terminating a CS** marks all associated documents as archived and prevents further signature routing to that CS.
- **Reinstating a suspended CS** does NOT auto-un-archive — a new amendment must be sent.
- **Fee amendment status** shows on the CS card in Continuity Stewards page — driven by `ContinuityDocument.status = countersign_pending` where `doc_type = fee_amendment`.

### 2.3 Support Stewards → Important Documents
- **Designating a new SS** auto-generates a `ss_authorization_agreement` document.
- **Role change** (Primary ↔ Alternate) creates an `ss_role_amendment` document.
- **Suspending or terminating an SS** archives associated documents.
- **SS never signs financial documents** — only role/authorization docs. UI must filter counterparty by category.

### 2.4 Vault → Important Documents
- Optional link: a document row may reference a `vault_items.id` in `file_ref` when the practitioner uploads a PDF (e.g. attorney-drafted addendum).
- Vault-linked documents show a badge "Backed by Vault" and clicking downloads the sealed file.
- Archiving a document does NOT delete the vault item — that stays for audit trail.

---

## 3. Workflow — end-to-end document lifecycle

### 3.1 Document creation paths (4 paths)

**Path A — Auto-generated from another module (most common)**
1. Provider signs Continuity Plan → auto-creates `plan_agreement` doc
2. Provider designates CS → auto-creates `cs_retainer_agreement` doc
3. Provider amends CS fee → auto-creates `fee_amendment` doc
4. Provider designates SS → auto-creates `ss_authorization_agreement` doc

**Path B — Manual wizard (New Agreement button)**
- Provider opens wizard → picks type → picks counterparty → configures clauses → sends for signature

**Path C — From template**
- Provider opens Sample Templates → picks a pre-vetted template → wizard opens pre-filled

**Path D — Supporting document upload**
- Provider uploads a supplementary file (attorney letter, credential proof, etc.) — no signing required, just archived reference material

### 3.2 Signing flow (both parties)

```
Draft (optional) → Pending Provider Sign → Provider signs
                     ↓
                Countersign Pending → CS/SS countersigns
                     ↓
                    Active
                     ↓
             Expiring (30 days before expires_at)
                     ↓
              Expired OR Renewed
```

### 3.3 Amendment flow
- Amendment always references the parent doc via `amends_document_id`
- Amendment does NOT invalidate the parent — both remain active
- Fee amendments specifically update `plan_stewards.fee_cents` upon countersignature

### 3.4 Termination flow
- Provider or counterparty initiates termination with a reason
- Requires 30-day notice unless mutual
- Doc status → `terminated`, `terminated_at` populated
- If it's a `cs_retainer_agreement` termination → also archives the plan_steward record

---

## 4. Design plan — Waves

Each wave is independently shippable. Waves are ordered so early waves unblock later ones.

---

### **WAVE 1 — Foundation & Layout Refactor** (2 hours)

**Goal:** Move from horizontal `tabs-segmented` to `page-sidebar` pattern (mirroring Finances.vue), rebuild stat chips as dynamic props, unify menu structure.

#### Frontend
- [ ] Replace horizontal `tabs-segmented` tab strip with vertical `page-sidebar` navigation (same pattern as `/provider/finances`)
- [ ] Sidebar menu structure:
  ```
  Documents
    - All Documents (count)
    - Pending My Signature (count)  ← Provider needs to sign
    - Awaiting Countersignature (count)  ← Sent, waiting on CS/SS
    - Active & Signed (count)
    - Expiring Soon (count)
    - Archived (count)

  Supporting Documents
    - Amendments (count)
    - Uploaded Files (count)

  Templates & Tools
    - Sample Templates
    - Export Documents

  Activity
    - View Activity Log  ← links to /provider/activity?event_type=document
  ```
- [ ] Stat chips row (4 chips) — bind to `docStats` prop from controller with real DB queries:
  - Total Documents (all statuses)
  - Awaiting My Signature (`pending_sign` where signed_by is provider)
  - Awaiting Counterparty (`countersign_pending`)
  - Expiring in 30 Days
- [ ] Remove old party-based tabs (pe, pd, de, tri) — those are counterparty filters, moved to filter dropdown

#### Backend
- [ ] `DocumentsController::index()` returns:
  - `docStats` — real counts by status
  - `documents` — full list with computed `primary_action` per doc
  - `menuBadges` — per-menu-item counts for sidebar
  - `plan` — for plan-level context
  - `csList`, `ssList` — active counterparties from plan_stewards
- [ ] Add computed field on each doc: `primary_action` ∈ {sign, countersign, view, renew, edit, download}
- [ ] Add computed field: `days_until_expiry` (int or null)

#### Success criteria
- [ ] Left sidebar visible on `/provider/important-documents`
- [ ] Clicking each menu item filters the doc list correctly
- [ ] Stat chips show real numbers, not seeded placeholders
- [ ] Old horizontal tab strip completely removed

---

### **WAVE 2 — Document Card Rebuild** (2.5 hours)

**Goal:** Fix the `ag-row` card component so every card:
- Shows correct primary action based on status + role
- Has all secondary actions wired (View, Renew, Amend, Terminate, Download, Message counterparty)
- Renders parties correctly (avatars + names)
- Shows expiry warnings, countersignature pending states, amendment history

#### Frontend
- [ ] Card layout structure:
  ```
  ┌───────────────────────────────────────────────────────────┐
  │  [icon]  Doc Type · Category                     [status] │
  │          Title of Document                                │
  │          Reference # · Effective date                     │
  │          [avatar1] [avatar2] Party A & Party B            │
  │          [status hint icon] Human-readable status hint    │
  │                                                            │
  │  [Primary Action Button]  [👁 View]  [⋯ More]             │
  └───────────────────────────────────────────────────────────┘
  ```
- [ ] Status → primary action mapping:
  | Status | Provider action | CS/SS action |
  |---|---|---|
  | draft | Continue Editing | (invisible) |
  | pending_sign | Sign Now | (invisible) |
  | countersign_pending | (View only) | Countersign |
  | active | Renew (if <30d to expiry) OR View | View |
  | expiring | Renew | View |
  | expired | Renew | View |
  | terminated | View | (invisible) |
  | archived | View | View |
- [ ] More menu (⋯) items per status:
  - Active: View, Download PDF, Amend, Terminate, Message Counterparty
  - Countersign Pending: View, Send Reminder, Cancel Send, Message Counterparty
  - Expired: View, Renew, Archive
- [ ] Empty state per menu item (each menu shows friendly empty state when no docs match)

#### Backend
- [ ] Add `party_b_details` to each doc: name, avatar_initials, role, portal
- [ ] Add `can_amend` boolean per doc (business rules: must be active, provider is primary party)
- [ ] Add `can_terminate` boolean per doc
- [ ] Add `amendment_count` per parent doc

#### Success criteria
- [ ] Every card has exactly the right primary button for its state
- [ ] Clicking Renew opens the Renewal modal with parent doc pre-loaded
- [ ] Clicking Amend opens the Amendment modal
- [ ] Message icon routes to `/provider/messages?with={party_b_id}`
- [ ] Empty state per menu category shows friendly message + CTA

---

### **WAVE 3 — Create New Agreement Wizard Rebuild** (3 hours)

**Goal:** Complete rebuild of the 4-step wizard so every field is validated (Vuelidate), no toast on validation error, backend fully wired, DB records created correctly.

#### Frontend — Wizard Steps

**Step 1: Agreement Type**
- Radio cards for doc type: SOW, Continuity Retainer, Amendment, Attestation, CS-CS Agreement, SS Authorization, Custom
- Description under each type
- Vuelidate: type is required
- No toast on error — inline red border + hint

**Step 2: Parties & Details**
- Party A: pre-filled Provider (locked, non-editable)
- Party B: dropdown of eligible counterparties (from plan_stewards filtered by doc_type)
- Title (required, max 120)
- Reference (auto-generated but editable, format: `{TYPE}-{YYYY}-{NNN}`)
- Effective Date (required, defaults to today)
- Expires Date (optional, must be > effective if set)
- Auto-renew checkbox (only when expires date set)
- Vuelidate rules for all fields

**Step 3: Clauses & Terms**
- Show pre-loaded standard clauses from template (readonly with expand/collapse)
- "Add Custom Clause" button for provider-specific additions
- Fee field (only for CS retainer/SOW types) — integer cents input
- Notes field (long text, max 2000)
- Vuelidate: at least 1 clause must be present

**Step 4: Review & Confirm**
- Show read-only summary of all steps
- Show counterparty summary card
- Show final legal notice
- Two buttons: "Save as Draft" | "Send for Signature"

#### Backend
- [ ] `DocumentsController::request(StoreDocumentRequest $request)` — validates + creates doc + fires event
- [ ] `StoreDocumentRequest` FormRequest with all Vuelidate-matching rules
- [ ] `DocumentService::createFromWizard()` method — handles reference generation, standard clause injection, initial status
- [ ] Event: `DocumentCreatedForSignature` — fires email to counterparty + activity log both parties
- [ ] Reference number generator (unique per doc_type per year)

#### Success criteria
- [ ] All 4 steps validate client-side with inline errors (no toast)
- [ ] Submitting from Step 4 creates a real DB row with `status = pending_sign`
- [ ] Counterparty receives email invitation to sign
- [ ] Doc appears immediately in "Pending My Signature" list
- [ ] Draft save button works and reopens with data pre-filled
- [ ] Wizard is a single AegisModal, not per-step navigation

---

### **WAVE 4 — Signature, Renewal, Amendment Modals** (2.5 hours)

**Goal:** Full backend + frontend wiring for the 4 core lifecycle modals.

#### Signature Modal
- Full name typed input (must match user's registered name — Vuelidate rule)
- Two attestation checkboxes:
  - "I have read and understand this agreement"
  - "I confirm my legal authority to sign"
- IP + timestamp captured server-side
- POST to `/provider/important-documents/{document}/sign`
- Status transitions to `countersign_pending`
- Email + activity to counterparty
- Modal closes, doc list refreshes

#### Renewal Modal
- Copies parent doc terms into new doc
- Editable: new effective date, new expires date, updated fee (if applicable)
- Optional: change auto-renew flag
- Reason for renewal (optional)
- POST to `/provider/important-documents/{document}/renew`
- Creates a new doc with `parent_document_id` set, old doc marked as `superseded`

#### Amendment Modal
- References parent via `amends_document_id`
- Editable: only the field being amended (fee, expires date, clause additions)
- Diff view showing "Before → After"
- POST to `/provider/important-documents/{document}/amend`
- Creates amendment doc with `status = pending_sign`, parent doc stays active

#### Termination Modal
- Reason dropdown: mutual, non-renewal, breach, resignation, other
- Notice period display (default 30 days from termination date)
- Effective termination date field
- Notes textarea
- Vuelidate: reason required, notes required if "other"
- POST to `/provider/important-documents/{document}/terminate`
- Doc status → `terminated`, `terminated_at` populated
- If cs_retainer_agreement → cascades to `plan_stewards.status = archived`

#### Success criteria
- [ ] Signature modal creates real signature record in `document_signatures` table
- [ ] Renewal creates new doc + supersedes old
- [ ] Amendment creates amendment doc linked to parent, both remain visible
- [ ] Termination cascades correctly to CS/SS records where applicable
- [ ] All 4 modals send correct emails and log activity for both parties

---

### **WAVE 5 — Sample Templates & Export** (2 hours)

**Goal:** Fully functional Sample Templates library and Export Documents modal.

#### Sample Templates Modal
- [ ] Backend: templates stored in `document_templates` table (seed 6 standard templates)
- [ ] Templates by category:
  - Provider–CS Retainer Agreement
  - Provider–CS Statement of Work
  - Provider–SS Authorization
  - CS–CS Coordination Agreement (tri-party)
  - Fee Amendment Template
  - Annual Attestation Template
- [ ] Each template shows: title, category, preview snippet, "Use This Template" button
- [ ] Clicking Use Template → opens wizard with template pre-loaded (type, standard clauses, party B optional pre-fill)
- [ ] Search field for templates (client-side filter)
- [ ] Tags/categories filter

#### Export Documents Modal
- [ ] Multi-select checkboxes for documents to export (default: all in current filter)
- [ ] Format options: PDF bundle (zipped), CSV summary, Individual PDFs
- [ ] Filter options:
  - Date range (from-to)
  - Status filter (multi)
  - Doc type filter (multi)
  - Counterparty filter (multi)
- [ ] Include options:
  - Include full document body
  - Include signature audit trail
  - Include activity log for each doc
  - Include amendments
- [ ] Password protection option (for PDF bundle)
- [ ] "Email to me" vs "Download now" radio
- [ ] Vuelidate: at least 1 doc must be selected, format required
- [ ] Backend: `DocumentsController::export()` generates the bundle via a queued job
- [ ] Email delivery via `SendExportBundleJob`
- [ ] Rate limit: 5 exports/day per provider (prevent abuse)

#### Success criteria
- [ ] Templates modal shows 6 real templates from DB, not hardcoded
- [ ] Clicking Use Template pre-fills wizard correctly
- [ ] Export modal generates real PDF bundle
- [ ] Password-protected zip works
- [ ] Email delivery includes download link that expires in 7 days

---

### **WAVE 6 — Supporting Documents Section** (1.5 hours)

**Goal:** Separate "Supporting Documents" section for non-signed uploads (attorney letters, credential proofs, standalone amendments not tied to signed docs).

#### Frontend
- [ ] New sidebar menu item "Supporting Documents" with sub-items:
  - Amendments (attached to signed docs)
  - Uploaded Files (standalone)
- [ ] Upload zone at top (drag-drop area)
- [ ] File list with:
  - Filename, doc_type badge, uploaded date
  - Optional link to parent doc (if is_supporting = true and related_to is set)
  - Download button, Delete button
- [ ] Filter: All / Attached to Docs / Standalone

#### Backend
- [ ] Verify `DocumentsController::upload(UploadSupportingDocRequest)` handles multipart file
- [ ] File goes to storage (private disk)
- [ ] `is_supporting = 1` on the doc row
- [ ] Optionally links to a `vault_item` if user chose to store in vault
- [ ] Activity log: `supporting_doc_uploaded`

#### Success criteria
- [ ] Supporting doc uploaded via drop zone lands in DB with correct flags
- [ ] File is downloadable by authorized parties only (policy check)
- [ ] Vault link creates `vault_items` row and returns badge on the doc row

---

### **WAVE 7 — Cross-Module Integration & Vault Linking** (2 hours)

**Goal:** Complete the plumbing between Important Documents and Continuity Plan / CS / SS / Vault so state stays consistent.

#### Continuity Plan → Documents auto-creation
- [ ] `PlanService::sign()` creates `plan_agreement` doc automatically
- [ ] `PlanService::amend()` creates `plan_amendment` doc
- [ ] Annual re-attestation creates `attestation` doc
- [ ] All auto-generated docs skip the wizard, land in `pending_sign` immediately

#### CS Stewards → Documents auto-creation
- [ ] `StewardService::designateCs()` creates `cs_retainer_agreement` doc
- [ ] `StewardService::amendCsFee()` creates `fee_amendment` doc
- [ ] Fee amendment countersignature triggers `plan_stewards.fee_cents` update

#### SS Stewards → Documents auto-creation
- [ ] `StewardService::designateSs()` creates `ss_authorization_agreement` doc
- [ ] `StewardService::updateSsRole()` creates `ss_role_amendment` doc

#### Vault linking
- [ ] Doc row can reference `vault_items.id` via `file_ref`
- [ ] Vault link badge on doc card when file_ref set
- [ ] Clicking badge downloads sealed file (respects vault access controls)
- [ ] Archiving doc does NOT delete vault item
- [ ] Vault access requires unlocked vault state for that user + zone

#### Success criteria
- [ ] Signing a Continuity Plan creates a plan_agreement row automatically
- [ ] Designating a new CS creates a cs_retainer_agreement row
- [ ] Suspending a CS archives all pending docs for that CS
- [ ] Vault-linked docs show badge and download works correctly
- [ ] Amendment countersignature updates `plan_stewards.fee_cents` when applicable

---

### **WAVE 8 — Activity Log & Emails** (1.5 hours)

**Goal:** Every state change writes correct activity entries and sends the right emails.

#### Activity events to log (both `log` for actor and `notification` for counterparty)
- `document_created`
- `document_sent_for_signature`
- `document_signed`
- `document_countersigned`
- `document_reminder_sent`
- `document_amended`
- `document_renewed`
- `document_terminated`
- `document_archived`
- `supporting_doc_uploaded`
- `export_generated`

#### Emails to send
- `emails/document/30-document-created.blade.php` — to counterparty
- `emails/document/31-signature-requested.blade.php` — to counterparty
- `emails/document/32-signed-by-provider.blade.php` — to counterparty
- `emails/document/33-countersigned.blade.php` — to provider
- `emails/document/34-fully-executed.blade.php` — to both
- `emails/document/35-reminder.blade.php` — to counterparty
- `emails/document/36-expiring-soon.blade.php` — to both (30 days)
- `emails/document/37-expired.blade.php` — to both
- `emails/document/38-amended.blade.php` — to counterparty
- `emails/document/39-renewed.blade.php` — to counterparty
- `emails/document/40-terminated.blade.php` — to both

#### Update Activity Link
- [ ] Change hero action `View Activity` link from generic `/provider/activity` to `/provider/activity?event_type=document`
- [ ] Verify Activity page can filter by event_type

#### Success criteria
- [ ] Every state change writes correct activity (`log` for actor, `notification` for counterparty)
- [ ] All 11 email templates exist and dispatch correctly
- [ ] Activity link on hero deep-links to filtered activity view

---

### **WAVE 9 — Testing & Seed Data** (1.5 hours)

**Goal:** Sarah's demo user should show realistic document activity across all lifecycle stages.

#### Seed data updates for `p_sarah`
- 1 `plan_agreement` — active, signed by both parties
- 1 `cs_retainer_agreement` for Marcus — active
- 1 `fee_amendment` for Marcus — `countersign_pending` (awaiting Marcus)
- 1 `ss_authorization_agreement` for Linda — active
- 1 `sow` — Statement of Work with Priya — active, expires in 45 days
- 1 `sow` — Statement of Work with Marcus — `expiring` (15 days to expiry)
- 1 supporting doc — uploaded attorney letter, no counterparty

#### Test walkthrough
- [ ] Test each sidebar menu item shows correct docs
- [ ] Test primary action on each doc status
- [ ] Test wizard end-to-end (all 4 steps)
- [ ] Test each modal (signature, renew, amend, terminate)
- [ ] Test templates modal → wizard pre-fill
- [ ] Test export modal → PDF bundle received
- [ ] Test supporting doc upload
- [ ] Test cross-module: sign new CS → doc auto-created
- [ ] Test cross-module: fee amendment → CS countersigns → plan_stewards.fee_cents updated
- [ ] Test cross-module: terminate CS retainer → CS status archived

#### Success criteria
- [ ] All 7 seeded docs render correctly with right statuses and actions
- [ ] End-to-end wizard flow completes without errors
- [ ] Cross-module state transitions work as designed

---

## 5. File impact summary

### Backend files touched (Waves 1–9)
- [ ] `app/Http/Controllers/Provider/DocumentsController.php` (rewrite)
- [ ] `app/Services/DocumentService.php` (expand)
- [ ] `app/Http/Requests/StoreDocumentRequest.php` (expand)
- [ ] `app/Http/Requests/AmendDocumentRequest.php` (new)
- [ ] `app/Http/Requests/RenewDocumentRequest.php` (new)
- [ ] `app/Http/Requests/TerminateDocumentRequest.php` (new)
- [ ] `app/Http/Requests/ExportDocumentsRequest.php` (new)
- [ ] `app/Models/ContinuityDocument.php` (add relationships)
- [ ] `app/Models/DocumentTemplate.php` (new)
- [ ] `app/Services/PlanService.php` (cross-module doc creation)
- [ ] `app/Services/StewardService.php` (cross-module doc creation)
- [ ] `app/Events/DocumentSigned.php` (new)
- [ ] `app/Events/DocumentCountersigned.php` (new)
- [ ] `app/Events/DocumentAmended.php` (new)
- [ ] `app/Events/DocumentTerminated.php` (new)
- [ ] `app/Jobs/SendExportBundleJob.php` (new)
- [ ] `app/Console/Commands/CheckExpiringDocumentsCommand.php` (new — daily cron)
- [ ] `routes/web.php` (add renew/amend/terminate/export routes)
- [ ] `database/migrations/[ts]_create_document_templates_table.php` (new)
- [ ] `database/seeders/DocumentSeeder.php` (expand)
- [ ] `database/seeders/DocumentTemplateSeeder.php` (new)

### Frontend files touched
- [ ] `resources/js/pages/provider/ImportantDocuments.vue` (rewrite from 1592L)
- [ ] `resources/js/components/documents/DocumentCard.vue` (new)
- [ ] `resources/js/components/documents/DocumentWizardModal.vue` (new, extracted)
- [ ] `resources/js/components/documents/SignatureModal.vue` (new, extracted)
- [ ] `resources/js/components/documents/RenewalModal.vue` (new, extracted)
- [ ] `resources/js/components/documents/AmendmentModal.vue` (new, extracted)
- [ ] `resources/js/components/documents/TerminationModal.vue` (new, extracted)
- [ ] `resources/js/components/documents/TemplatesModal.vue` (new, extracted)
- [ ] `resources/js/components/documents/ExportModal.vue` (new, extracted)
- [ ] `resources/js/components/documents/SupportingDocsUpload.vue` (new)

### Email templates (11 new)
- All under `resources/views/emails/document/`

---

## 6. Design invariants (per PROMPT_1_DESIGN.md)

Applied throughout every wave:
- No `modal-id=` attributes
- No `title="..."` — use `data-tooltip=`
- No raw SVGs — always `<AegisIcon name="..." :size="N" />`
- No Tailwind classes — CSS variables only
- No hex colors — `var(--gold-dark)` etc
- All forms use Vuelidate `is-error` class (never `is-invalid`)
- All forms use `fieldError('name')` helper for `:class`
- No toast on validation error — inline messages only
- All modals use `AegisModal.vue` (sizes: sm/md/lg/xl)
- Stat chips always SIBLING of hero banner
- Money always integer cents in DB, formatted with `formatCents()` in Vue
- `useConfirm`/`confirmAction` callback-based (never awaited)
- `useForm()` at `<script setup>` top level only
- Tabs use `v-show` (not `v-if`) to preserve state
- Globally registered components (no import needed): AegisIcon, AegisModal, AegisToast, AegisConfirm, AegisBadge, AegisStatChip, AegisHeroBanner, AegisCard, AegisEmptyState, IncidentBanner
- Require local import: everything else including AegisDropzone, AegisToggle, AegisPagination

---

## 7. Delivery order recommendation

Recommended order for shipping (each wave ships independently):

**Sprint 1 (day 1):** Wave 1 → Wave 2 (foundation + card rebuild)
**Sprint 2 (day 2):** Wave 3 → Wave 4 (wizard + core modals)
**Sprint 3 (day 3):** Wave 5 → Wave 6 (templates + supporting docs)
**Sprint 4 (day 4):** Wave 7 → Wave 8 → Wave 9 (integration + emails + testing)

Total estimated effort: **17 hours** across ~4 working sessions.

---

## 8. Rollback plan

Each wave is a discrete commit + PR. Rollback = revert single commit.
No destructive migrations — all new columns are nullable, all new tables are additive.
Existing `continuity_documents` rows work with all waves — old data stays valid.

---

*Plan locked 2026-07-20. Any drift discovered during execution feeds back here.*
