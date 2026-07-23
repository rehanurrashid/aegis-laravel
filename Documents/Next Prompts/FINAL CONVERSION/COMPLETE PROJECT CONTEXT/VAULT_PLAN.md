# Document Vault — Comprehensive Build Plan

**Date:** 2026-07-20
**Scope:** Provider portal `/provider/vault` — full audit and hardening aligned with the completed Continuity Practice module (Continuity Plan, Continuity Stewards, Support Stewards, Important Documents)
**Source of design:** `PROMPT_1_DESIGN.md`, `AEGIS_VUE_RULES.md`, `CONTINUITY_PRACTICE_MODULE_SPEC.md`, `AEGIS_VAULT_PROMPT.md` (if present)
**Delivery:** Multi-wave — each wave is independently shippable in 1.5–3 hours

---

## 0. Executive summary

The Vault is the **secure sealed file store** and the physical backing of every asset that must remain locked until a critical incident is verified. It is where the practice continuity plan meets encrypted storage.

The Vault does two things — no more, no less:
1. **Store files** in the correct zone with the correct access rules
2. **Release access** to CS/SS during a verified incident, respecting per-zone gates

Everything else on the page (attestation, permissions, storage bar, credentials) exists in service of those two duties.

```
                    ┌─────────────────────┐
                    │  Continuity Plan    │ ← docs_required config per incident
                    └──────────┬──────────┘
                               │ "Death Cert required"
                               ▼
                    ┌─────────────────────┐
                    │        VAULT        │ ← 4 zones
                    │  ┌───────────────┐  │
                    │  │  Standard     │  │ ← Always accessible to CS (per plan)
                    │  │  Emergency    │  │ ← Only during verified incident
                    │  │  Credentials  │  │ ← Encrypted, incident-triggered
                    │  │  Roster       │  │ ← Client list, incident-triggered
                    │  └───────────────┘  │
                    └──────────┬──────────┘
                               │
                     ┌─────────┼─────────┐
                     ▼         ▼         ▼
                 ┌──────┐  ┌──────┐  ┌────────────┐
                 │  CS  │  │  SS  │  │ Important  │
                 └──────┘  └──────┘  │ Documents  │
                  reads     limited  │ (file_ref) │
                  during    view     └────────────┘
                  incident            references
                                      vault items
```

Every vault item has:
- **zone** — standard / emergency / credentials / roster
- **category** — user-defined bucket within zone (Agreements, Insurance, Licenses etc.)
- **file_ref** — pointer to storage path (encrypted for credentials zone)
- **access_grant** — JSON list of user_ids explicitly granted access
- **status** — active / archived / expiring / expired
- **client_priority** — boolean flag for roster items

---

## 1. Current state — what exists

### 1.1 Backend inventory
| Component | Path | Lines | Status |
|---|---|---|---|
| Controller | `app/Http/Controllers/Provider/VaultController.php` | 196 | Wired but thin |
| Service | `app/Services/VaultService.php` | 191 | Works but no encryption logic |
| Model | `app/Models/VaultItem.php` | 67 | Fields present |
| Enum | `app/Enums/VaultZone.php` | 4 zones | Locked |
| Migration | `2024_01_01_000019_create_vault_items_table.php` + meta table | present | OK |
| Routes | `routes/web.php` lines 237–243 | 7 routes | index/upload/attest/permissions/download/update/destroy |
| CS mirror | `app/Http/Controllers/ContinuitySteward/VaultController.php` | present | Read-only view + download |

### 1.2 Frontend inventory
| Component | Path | Lines |
|---|---|---|
| Page | `resources/js/pages/provider/Vault.vue` | 1,846 |
| CS view | `resources/js/pages/continuity-steward/Vault.vue` | present |

### 1.3 Current tab structure (horizontal `tabs-segmented`)
- All Documents
- Sensitive Information (Emergency zone)
- System Access Credentials
- Client Roster

### 1.4 Existing modals (11 total)
- `upload` — Upload Document (standard/emergency)
- `addCredential` — Add Secure Credential (encrypted)
- `addClient` — Add Client (roster)
- `editClient` — Edit / View Client
- `docDetail` — Document Details
- `attest` — Attest Vault is Complete
- `permissions` — Vault Permissions & Access
- `addPermission` — Grant Vault Access
- `editPermission` — Edit Vault Access

### 1.5 Known issues in current state
- Storage bar **hardcoded** to "1.2 GB / 10 GB" — no real calculation
- "Last security scan: Today at 6:00 AM" — hardcoded text, no scan runs
- Compliance badges (HIPAA, SOC 2, HITECH, ZERO-KNOWLEDGE) — decorative only, no verification behind them
- Category tabs (All / Agreements / Insurance / Licenses) inside "All Documents" tab — hardcoded, not synced with actual categories in DB
- Sort dropdown — client-side only, not persisted
- Expired badges on all seed items — no expiry tracking logic runs
- Documents required per incident section — informational only, not actually validated
- "Vault attested as complete" state — timestamp displays, but no annual reminder actually triggers
- No cross-reference to Important Documents — vault items and continuity_documents are silo'd

---

## 2. Impact analysis — how the 4 completed pages affect Vault

### 2.1 Continuity Plan → Vault
- **Plan config `docs_required`** per incident type declares what must be present in the vault before an incident can be verified.
- Example: Death incident requires "Death Certificate" — if that doc type isn't in Standard/Emergency zone, verifying the incident is blocked.
- **Signing the plan** should trigger a vault completeness check and prompt the practitioner if required doc types are missing.
- **Annual re-attestation** on the plan triggers the vault attestation reminder.

### 2.2 Continuity Stewards → Vault
- **`plan_stewards.vault_access`** field controls per-CS access level (`none` / `emergency_only` / `full`).
- Configured via the Edit CS modal (Vault Access dropdown, values: `none`, `scoped`, `full`).
- **Suspending or terminating a CS** revokes their vault access immediately.
- **CS `vault_access = 'full'`** means they can read Standard zone at any time; Emergency/Credentials/Roster still require verified incident.
- **CS `vault_access = 'scoped'`** (Emergency Only) means they can access nothing until incident is verified.

### 2.3 Support Stewards → Vault
- SS do **not** get vault access — that is a CS-only responsibility.
- SS see only **read-only proof of vault attestation status** on their dashboard.
- SS **triggering an incident** is what unlocks vault access for the assigned CS.

### 2.4 Important Documents → Vault
- Doc rows can reference a `vault_items.id` via `continuity_documents.file_ref`.
- Example: A signed PDF addendum lives in the vault as a file, and the Important Documents row wraps it with legal metadata.
- Archiving a doc **does not** delete the vault item (audit trail preservation).
- Deleting a vault item that has a doc row linked to it **must be blocked** — user must remove the doc row first.
- Vault-linked docs show a "Backed by Vault" badge in Important Documents.

---

## 3. Workflow — end-to-end vault lifecycle

### 3.1 Standby state (99% of the time)
- Practitioner uploads documents, credentials, and client rosters into the correct zones
- Categorizes each item, sets expiry dates where applicable
- Grants explicit access to specific CS users via Permissions modal
- Annually attests that the vault contents are current and complete
- Vault sits sealed — no CS/SS can read Emergency/Credentials/Roster until incident triggered

### 3.2 Access unlock — critical incident flow
1. SS triggers an incident on `/support-steward/critical-incident`
2. Incident record created, status `pending_verification`
3. CS notified, opens Continuity Management cockpit
4. CS verifies the incident with required supporting docs (from plan config)
5. On verification → `IncidentService::verify()` unlocks vault zones for the assigned CS
6. CS sees Emergency + Credentials + Roster in their Vault view
7. Access logged with IP + timestamp on `vault_access_log` (audit)
8. When incident closes → access re-locks
9. Full audit trail sent to practitioner (or their estate) via email

### 3.3 Annual attestation flow
1. Plan's `annual_review_date` reaches within 30 days
2. Practitioner sees "Annual Review Due" banner on Vault page
3. Practitioner opens Begin Annual Review flow:
   - Step 1: Review each zone's contents
   - Step 2: Mark expired items for removal
   - Step 3: Confirm required documents per plan config are present
   - Step 4: Attest & sign
4. `vault_attested_at` timestamp updated
5. CS/SS notified: "Provider has completed annual vault attestation"

### 3.4 Add / update / remove item flow
- Add: wizard modal per zone type (upload / credential / client)
- Update: edit modal per item type
- Remove: soft delete (moves to archived state) with reason
- All logged as activity, notification to CS if `full` access

### 3.5 Permissions & Access management
- Provider grants specific CS explicit access levels (`none` / `emergency_only` / `full`)
- Provider can also grant SS read-only visibility to attestation status
- Access log tab shows every read event with IP + timestamp + incident_id if applicable

---

## 4. Design plan — Waves

Each wave is independently shippable. Ordered so early waves unblock later ones.

---

### **WAVE 1 — Foundation & Layout Refactor** (2 hours)

**Goal:** Move from horizontal `tabs-segmented` to `page-sidebar` pattern (mirroring Finances/Important Documents), rebuild stat chips with real data, remove hardcoded strings.

#### Frontend
- [ ] Replace horizontal `tabs-segmented` tab strip with vertical `page-sidebar` navigation
- [ ] Sidebar menu structure:
  ```
  Vault Contents
    - All Documents (count)
    - Sensitive Information (count)
    - System Access Credentials (count)
    - Client Roster (count)

  Compliance
    - Annual Attestation
    - Required Documents Check
    - Access Log

  Storage & Permissions
    - Vault Permissions
    - Storage Usage

  Activity
    - View Activity Log  ← links to /provider/activity?event_type=vault
  ```
- [ ] Stat chips row (4 chips) — bind to `vaultStats` prop from controller:
  - Total Documents (all zones)
  - Sensitive Information (emergency zone count)
  - Action Needed (expiring within 30 days + missing required docs)
  - Access Granted To (count of active permissions)
- [ ] Remove hardcoded storage bar text — bind to real `storage.used_bytes` and `storage.total_bytes` from backend
- [ ] Remove hardcoded "Last security scan" — either bind to real scan record or remove line entirely
- [ ] Fix Activity link on hero: `/provider/activity?event_type=vault` (not `/activity?module=vault`)

#### Backend
- [ ] `VaultController::index()` returns:
  - `vaultStats` — real counts by zone + expiring + missing
  - `storage` — { used_bytes, total_bytes, percent } computed from `vault_items.file_size`
  - `annualReviewDate` — from plan
  - `attestedAt` — from plan
  - `requiredDocsCheck` — { required: [types], missing: [types] } comparing plan configs vs vault contents
  - `csAccessGrants` — list of CS with vault_access levels
  - `menuBadges` — per-menu-item counts
- [ ] Add `file_size` column to `vault_items` if missing (migration)
- [ ] Populate `file_size` on every upload

#### Success criteria
- [ ] Left sidebar visible on `/provider/vault`
- [ ] Storage bar shows real percentage
- [ ] Stat chips show real counts (not "6", "3", "0", "1" placeholders)
- [ ] Old horizontal tab strip removed
- [ ] Activity link deep-links to filtered activity

---

### **WAVE 2 — Zone Views Rebuild** (3 hours)

**Goal:** Each zone view (All Documents, Sensitive, Credentials, Roster) shows real DB data with correct grouping, sorting, filtering, and empty states.

#### All Documents zone
- [ ] Category grouping from real DB categories (not hardcoded "Agreements / Insurance / Licenses")
- [ ] Categories synced from user-defined values as they add items
- [ ] Sort dropdown wired: Newest First / Oldest First / Name A-Z / Expiring Soon
- [ ] File type filter: PDF / Word / Image / All
- [ ] Empty state per category
- [ ] Each item card shows:
  - Icon per file type
  - Title, sub_label
  - Category badge
  - Status badge (Active / Expired / Expiring)
  - Signed / Expires dates
  - View / Download / Edit / Remove actions

#### Sensitive Information (Emergency) zone
- [ ] Same layout as All Documents
- [ ] Red-tinted card border indicating restricted access
- [ ] Trigger conditions list at top (pulled from plan's enabled incident configs)
- [ ] Warning banner: "These items are only accessible to CS after verified incident"
- [ ] "Manage Sensitive Information" CTA opens upload modal pre-scoped to emergency zone

#### System Access Credentials zone
- [ ] Card layout for each credential:
  - Service name / URL
  - Username (visible)
  - Password (masked, click-to-reveal with attestation)
  - Notes
  - Category tag (Email / EHR / Banking / SaaS / Other)
- [ ] Password masking with "click to reveal + audit log" pattern
- [ ] Password strength indicator
- [ ] 2FA info field (has_2fa boolean + backup_codes textarea)
- [ ] Encrypted storage — passwords encrypted at rest with app key
- [ ] Backend decryption only during authorized access
- [ ] Never sent to CS unless incident verified

#### Client Roster zone
- [ ] Table layout: Name, Priority flag, Last Session, Contact, Notes, Actions
- [ ] Priority filter toggle: All / Priority Only
- [ ] Bulk actions: Mark Priority, Export Roster CSV
- [ ] Add Client modal with fields: Name, DOB, Contact, Emergency Contact, Session Cadence, Notes, Priority flag
- [ ] CSV import support (Add Roster from CSV button)
- [ ] Export CSV button (Provider only, audit logged)

#### Success criteria
- [ ] Each zone view is fully bound to real DB data
- [ ] Sort/filter work and persist across page reloads (URL params)
- [ ] Empty states friendly and actionable
- [ ] Credential passwords never appear in DOM as plaintext

---

### **WAVE 3 — Upload / Add Item Modals** (2.5 hours)

**Goal:** Full backend + frontend wiring for the 3 add-item modals with Vuelidate, no toast on validation errors.

#### Upload Document modal (standard + emergency)
- [ ] File dropzone via `<AegisDropzone />` — accepts PDF/DOCX/JPG/PNG, max 10MB
- [ ] Zone selection radio: Standard / Sensitive Information
- [ ] Category dropdown (autocomplete existing + create new)
- [ ] Title (required, max 200)
- [ ] Sub-label / description
- [ ] Signed date (optional)
- [ ] Expires date (optional, must be > signed if both set)
- [ ] Notes (optional, max 1000)
- [ ] "Link to Important Documents" checkbox — creates continuity_document row referencing this vault item via file_ref
- [ ] Vuelidate on all fields, inline errors, no toast

#### Add Secure Credential modal (credentials zone)
- [ ] Service name (required)
- [ ] Service URL (optional, URL format validation)
- [ ] Username / Email (required)
- [ ] Password (required, encrypted before storage)
- [ ] Password strength meter
- [ ] Category (Email / EHR / Banking / SaaS / Domain / Other)
- [ ] Has 2FA toggle → shows backup codes textarea if on
- [ ] Notes
- [ ] Vuelidate + inline errors

#### Add Client modal (roster zone)
- [ ] Client name (required)
- [ ] DOB (optional, date)
- [ ] Priority flag (checkbox — "Requires immediate handoff during incident")
- [ ] Contact info (phone, email)
- [ ] Emergency contact (name, phone, relationship)
- [ ] Session cadence (weekly / biweekly / monthly / as-needed)
- [ ] Last session date
- [ ] Notes for CS handoff
- [ ] Vuelidate + inline errors

#### Backend
- [ ] `UploadVaultItemRequest` FormRequest with proper rules per zone
- [ ] `VaultService::upload()` handles encryption for credentials zone using `Crypt::encrypt()`
- [ ] File stored on private disk with random UUID filename (never original name)
- [ ] Activity log entry: `vault_item_uploaded` on provider (log)
- [ ] Notification entry for CS if they have `full` access
- [ ] Storage bar updates in real-time via Inertia reload
- [ ] Optional: create `continuity_document` row if "Link to Important Documents" checked

#### Success criteria
- [ ] All 3 modals validate client-side with inline errors, no toast
- [ ] Uploads succeed and appear immediately in correct zone
- [ ] Credentials are encrypted at rest (verify by DB query — password field is ciphertext)
- [ ] Storage bar increments correctly
- [ ] Optional Important Documents link creates linked doc row

---

### **WAVE 4 — View / Edit / Remove Modals** (2 hours)

**Goal:** Every item card has functional View, Edit, and Remove actions with correct policy checks.

#### Document Details modal (view)
- [ ] Read-only view of all fields
- [ ] Signed / Expires timeline
- [ ] Category, zone, access grants
- [ ] Access history (last 10 reads with timestamp + accessor)
- [ ] Download button (audit-logged)
- [ ] Edit button (opens edit modal)
- [ ] Remove button (opens confirm)
- [ ] Linked Important Documents row (if any) with link

#### Edit Item modal
- [ ] Same fields as Add modal, pre-filled
- [ ] Zone cannot be changed after upload (immutable)
- [ ] File replacement supported (uploads new file, keeps history)
- [ ] Vuelidate on changes
- [ ] Activity log: `vault_item_updated`

#### Remove Item confirmation
- [ ] `useConfirm` callback-based (not awaited)
- [ ] Warning: "This item will be moved to Archived. Cannot be undone if linked to Important Documents."
- [ ] If linked continuity_document exists → block delete with message "Remove the linked Important Document row first"
- [ ] Reason field (required, dropdown: superseded / expired / no longer relevant / other)
- [ ] Backend soft-delete (moves to `status = archived`)
- [ ] Activity log: `vault_item_archived`

#### Success criteria
- [ ] View modal shows real access history from `vault_access_log`
- [ ] Edit updates DB and refreshes item card
- [ ] Remove is blocked if linked to Important Doc (matches policy)
- [ ] Storage bar decrements when item archived

---

### **WAVE 5 — Annual Attestation Flow** (2 hours)

**Goal:** Complete the annual review workflow — banner, review wizard, attestation record, cross-portal visibility.

#### Annual Review Due banner
- [ ] Shows when `annual_review_date` is within 30 days OR overdue
- [ ] Red-tinted if overdue, gold-tinted if upcoming
- [ ] "Begin Annual Review" button

#### Begin Annual Review wizard
- [ ] Step 1: Review Standard zone — checklist per category, mark expired items
- [ ] Step 2: Review Sensitive zone — same
- [ ] Step 3: Review Credentials — verify each still valid, update passwords marked stale (>90 days old)
- [ ] Step 4: Review Roster — verify each client entry current
- [ ] Step 5: Required Docs Check — compare plan configs vs vault, prompt to add missing types
- [ ] Step 6: Attest & Sign — full name typed input, checkbox confirmations, submit
- [ ] On submit: `plan.vault_attested_at = now()`, `plan.vault_attestation_note = notes`
- [ ] Activity log for provider + all CS + SS (both log and notification)
- [ ] Email to CS/SS: "Provider has completed annual vault attestation"

#### Vault Attested state (after submit)
- [ ] Green banner: "Vault attested as complete on {date}"
- [ ] Shows note (if any)
- [ ] Update button opens attestation update modal
- [ ] Next annual review date computed = attested_at + 1 year

#### Cross-portal visibility
- [ ] CS Dashboard shows: "Provider X — Vault attested on {date}"
- [ ] SS Dashboard shows same
- [ ] Both see red flag if attestation is overdue

#### Success criteria
- [ ] Banner shows correctly per state (upcoming / overdue / attested)
- [ ] Wizard walks through all 6 steps and captures signature
- [ ] Attestation timestamp persists on plan
- [ ] CS/SS dashboards reflect attestation status
- [ ] Overdue attestation triggers reminder emails weekly

---

### **WAVE 6 — Vault Permissions & Access Control** (2.5 hours)

**Goal:** Wire the Permissions modal to the actual `plan_stewards.vault_access` field, propagate changes to CS side, log all access grants/revocations.

#### Vault Permissions modal
- [ ] Table of all active CS from plan_stewards
- [ ] Per-CS access level dropdown: None / Emergency Only / Full Read
- [ ] Save button applies changes atomically
- [ ] Diff view showing "Before → After" for each CS

#### Grant Vault Access modal
- [ ] For adding one-off access grants to non-CS users (attorney, family)
- [ ] User lookup by name + email
- [ ] Access level dropdown (Emergency Only / Full Read)
- [ ] Expiry date (optional — auto-revoke after date)
- [ ] Reason (required)
- [ ] Vuelidate + inline errors
- [ ] Creates entry in `vault_permissions` table (separate from plan_stewards)

#### Edit Vault Access modal
- [ ] Same fields as Grant
- [ ] Revoke button (with confirm)

#### Access Log view (new sidebar menu item)
- [ ] Table: Timestamp, User, Item Accessed, Action (view/download), Incident ID, IP
- [ ] Filter by date range, user, action type
- [ ] Export CSV button
- [ ] Immutable — no delete/edit

#### Backend
- [ ] `VaultController::permissions()` updates plan_stewards.vault_access
- [ ] Fires event `VaultAccessChanged` for each affected CS
- [ ] Email to CS: "Your vault access has been updated to {level}"
- [ ] Activity log both parties
- [ ] Every vault download creates a `vault_access_log` row

#### Success criteria
- [ ] Permissions modal reflects real plan_stewards.vault_access values
- [ ] Changes persist and affect CS's vault view immediately
- [ ] Access log captures every read event with full audit metadata
- [ ] Non-CS access grants respect expiry auto-revocation
- [ ] All access changes emailed and logged

---

### **WAVE 7 — Required Documents Check** (1.5 hours)

**Goal:** Compare plan config's `docs_required` per incident type against actual vault contents; surface gaps prominently.

#### Documents Required Check section
- [ ] Show for each enabled incident type (from plan configs):
  - Incident type name
  - Required document types (e.g. "Death Certificate", "Doctor's Note")
  - Status per requirement: ✓ Present in vault / ⚠ Missing / ⚠ Expired
- [ ] "Add Missing Doc" CTA per gap — opens upload modal pre-scoped to that category
- [ ] Green "All requirements met" banner when everything present

#### Signing plan check
- [ ] When practitioner signs Continuity Plan, run required docs check
- [ ] If missing → warn but allow signing (soft warning, plan can be signed with gaps)
- [ ] Show persistent banner on dashboard until gaps closed

#### Incident verification block
- [ ] When CS verifies an incident, backend checks vault contents against that incident's required docs
- [ ] If required docs still missing → CS sees warning but can override with justification
- [ ] Override logged as `vault_gap_override` activity

#### Backend
- [ ] `VaultService::checkRequirements(ContinuityPlan $plan)` returns array of gaps
- [ ] Called during plan signing, dashboard load, and vault index
- [ ] `IncidentService::verify()` calls check before allowing verify

#### Success criteria
- [ ] Vault page shows accurate list of required docs per incident
- [ ] Gaps highlighted with actionable CTA
- [ ] Signing plan surfaces gaps without blocking
- [ ] CS incident verification surfaces gaps

---

### **WAVE 8 — Storage, Compliance, Real-Time State** (1.5 hours)

**Goal:** Replace all hardcoded strings with real data. Wire compliance badges to actual scans (or clearly mark them as static commitments).

#### Storage bar
- [ ] Backend computes: `SUM(file_size) FROM vault_items WHERE practitioner_id AND status != archived`
- [ ] Total quota by tier:
  - Access: 5 GB
  - Practice: 10 GB
  - Business Partner: 25 GB
- [ ] Percentage + human-readable format ("2.3 GB / 10 GB")
- [ ] Warning banner at 80%, red at 95%
- [ ] "Upgrade Plan" link opens Settings → Billing

#### Compliance badges
- [ ] HIPAA / SOC 2 / HITECH / ZERO-KNOWLEDGE
- [ ] Instead of decorative strings, link each to a static compliance page explaining what's covered
- [ ] Optional: last audit date if organizational audits exist

#### "Last security scan" line
- [ ] Either connect to real scan (if implemented) OR remove line entirely
- [ ] Recommendation: remove line for launch — no scan runs yet

#### Vault Status: Secure indicator
- [ ] Green pill: "All systems normal" when no issues
- [ ] Yellow pill: "Attention needed" when attestation overdue or gaps exist
- [ ] Red pill: "Action required" when storage full or critical gap

#### Success criteria
- [ ] Storage bar shows real usage percentage
- [ ] Compliance badges link to real info pages
- [ ] Vault Status pill reflects actual state
- [ ] No hardcoded "Today at 6:00 AM" or "1.2 GB" strings

---

### **WAVE 9 — Cross-Module Integration** (2 hours)

**Goal:** Complete plumbing between Vault, Continuity Plan, CS, SS, Important Documents.

#### Plan → Vault
- [ ] `PlanService::sign()` triggers `VaultService::checkRequirements()`
- [ ] Missing docs list attached to plan signing response
- [ ] Plan dashboard shows "X required docs missing from vault" banner
- [ ] Plan config changes (adding incident type) trigger vault gap re-check

#### CS Steward → Vault
- [ ] `StewardService::designateCs()` — new CS defaults to `vault_access = 'scoped'`
- [ ] `StewardService::updateVaultAccess()` — provider updates access from Edit CS modal
- [ ] `StewardService::suspendCs()` — sets vault_access to none
- [ ] `StewardService::reinstateCs()` — restores previous vault_access level
- [ ] CS Vault view (`/continuity-steward/vault/{plan}`) respects current access level

#### SS Steward → Vault
- [ ] SS never sees vault contents
- [ ] SS dashboard shows attestation status ONLY (green/yellow/red pill)
- [ ] SS incident trigger logs to `vault_access_log` as "incident_triggered" event
- [ ] SS notified when provider updates vault attestation

#### Important Documents → Vault
- [ ] Doc upload with "Link to Vault" flag creates vault_item + continuity_document
- [ ] `continuity_documents.file_ref` = `vault_items.id`
- [ ] Doc archive does NOT delete vault item
- [ ] Vault item delete blocks if doc row exists (must remove doc first)
- [ ] Vault item view shows linked doc row if any

#### Incident → Vault access unlock
- [ ] `IncidentService::verify()` calls `VaultService::unlockForIncident($incident, $cs)`
- [ ] Creates temporary access grant with `expires_at = incident close time`
- [ ] Every read during incident logged with incident_id
- [ ] `IncidentService::close()` calls `VaultService::relockAfterIncident()`
- [ ] Full audit trail emailed to practitioner (or estate contact)

#### Success criteria
- [ ] Signing plan surfaces vault gaps
- [ ] CS vault access changes propagate to CS view immediately
- [ ] Incident verify unlocks vault; close relocks it
- [ ] Vault-linked docs behave correctly in Important Documents
- [ ] Full audit trail generated per incident

---

### **WAVE 10 — Activity Log & Emails** (1.5 hours)

**Goal:** Every vault event writes correct activity and sends the right emails.

#### Activity events to log
- `vault_item_uploaded`
- `vault_item_updated`
- `vault_item_archived`
- `vault_item_viewed`
- `vault_item_downloaded`
- `vault_credential_revealed`
- `vault_access_granted`
- `vault_access_revoked`
- `vault_access_changed`
- `vault_attested`
- `vault_attestation_updated`
- `vault_annual_review_due`
- `vault_unlocked_for_incident`
- `vault_relocked_after_incident`
- `vault_gap_detected`
- `vault_gap_override`

#### Emails to send
- `emails/vault/50-annual-review-due.blade.php` (30 days out, weekly reminders after overdue)
- `emails/vault/51-attestation-complete.blade.php` (to CS/SS)
- `emails/vault/52-access-granted.blade.php` (to CS/user)
- `emails/vault/53-access-revoked.blade.php` (to CS/user)
- `emails/vault/54-access-level-changed.blade.php` (to CS)
- `emails/vault/55-item-uploaded-cs-notify.blade.php` (to CS if full access)
- `emails/vault/56-vault-unlocked-for-incident.blade.php` (to CS)
- `emails/vault/57-vault-relocked-post-incident.blade.php` (to CS + provider)
- `emails/vault/58-required-doc-missing.blade.php` (to provider, on gap detection)
- `emails/vault/59-audit-trail.blade.php` (to provider after incident close)

#### Success criteria
- [ ] All 16 activity events fire correctly (log for actor, notification for counterparty)
- [ ] All 10 email templates exist and dispatch correctly
- [ ] Activity Log page can filter by `event_type=vault`

---

### **WAVE 11 — Testing & Seed Data** (1.5 hours)

**Goal:** Sarah's demo user should show a fully populated vault across all zones and states.

#### Seed data for `p_sarah`
- Standard zone: 5 items across Agreements, Insurance, Licenses, Certifications, Contracts
- Sensitive zone: 3 items — will, healthcare directive, emergency instructions
- Credentials zone: 4 credentials — EHR, banking, email, domain hosting
- Client Roster: 12 clients, 3 marked priority
- Vault attested 8 months ago (annual review due in 4 months)
- 1 expiring doc (Office Lease — expires in 20 days)
- 1 required doc missing (Death Certificate not present but plan requires it)
- Marcus (CS) has `vault_access = 'full'`
- Priya (CS) has `vault_access = 'scoped'` (emergency only)

#### Test walkthrough
- [ ] Each sidebar menu shows correct items
- [ ] Stat chips show accurate counts
- [ ] Storage bar shows real usage
- [ ] Upload flow works for each zone
- [ ] View / Edit / Remove modals work
- [ ] Annual attestation wizard end-to-end
- [ ] Permissions modal updates CS access
- [ ] Required docs check surfaces the missing Death Certificate
- [ ] Access log shows recent reads
- [ ] Cross-module: sign new plan → gap check fires
- [ ] Cross-module: change CS access → CS view reflects it
- [ ] Cross-module: incident verify → vault unlocks
- [ ] Cross-module: link vault upload to Important Docs

#### Success criteria
- [ ] All seeded data renders correctly across zones and states
- [ ] Every workflow completes end-to-end
- [ ] Cross-module state transitions work

---

## 5. File impact summary

### Backend files touched (Waves 1–11)
- [ ] `app/Http/Controllers/Provider/VaultController.php` (rewrite)
- [ ] `app/Services/VaultService.php` (expand — encryption, gap check, incident unlock)
- [ ] `app/Http/Requests/UploadVaultItemRequest.php` (expand)
- [ ] `app/Http/Requests/UpdateVaultItemRequest.php` (new)
- [ ] `app/Http/Requests/GrantVaultAccessRequest.php` (new)
- [ ] `app/Http/Requests/AttestVaultRequest.php` (expand)
- [ ] `app/Models/VaultItem.php` (add file_size, encrypted_content casts)
- [ ] `app/Models/VaultAccessLog.php` (new)
- [ ] `app/Models/VaultPermission.php` (new — for non-CS grants)
- [ ] `app/Services/PlanService.php` (call gap check)
- [ ] `app/Services/StewardService.php` (vault access propagation)
- [ ] `app/Services/IncidentService.php` (unlock/relock hooks)
- [ ] `app/Events/VaultAccessChanged.php` (new)
- [ ] `app/Events/VaultUnlockedForIncident.php` (new)
- [ ] `app/Events/VaultAttested.php` (new)
- [ ] `app/Console/Commands/CheckVaultAttestationDueCommand.php` (new — daily cron)
- [ ] `app/Console/Commands/RevokeExpiredVaultGrantsCommand.php` (new)
- [ ] `routes/web.php` (add attest wizard, gap check, access log routes)
- [ ] `database/migrations/[ts]_create_vault_access_log_table.php` (new)
- [ ] `database/migrations/[ts]_create_vault_permissions_table.php` (new)
- [ ] `database/migrations/[ts]_add_file_size_to_vault_items.php` (new)
- [ ] `database/seeders/VaultSeeder.php` (expand)

### Frontend files touched
- [ ] `resources/js/pages/provider/Vault.vue` (rewrite from 1846L)
- [ ] `resources/js/components/vault/VaultSidebar.vue` (new)
- [ ] `resources/js/components/vault/VaultItemCard.vue` (new)
- [ ] `resources/js/components/vault/UploadDocumentModal.vue` (new, extracted)
- [ ] `resources/js/components/vault/AddCredentialModal.vue` (new, extracted)
- [ ] `resources/js/components/vault/AddClientModal.vue` (new, extracted)
- [ ] `resources/js/components/vault/EditItemModal.vue` (new)
- [ ] `resources/js/components/vault/AttestationWizardModal.vue` (new — 6-step)
- [ ] `resources/js/components/vault/PermissionsModal.vue` (new, extracted)
- [ ] `resources/js/components/vault/AccessLogView.vue` (new)
- [ ] `resources/js/components/vault/RequiredDocsCheck.vue` (new)
- [ ] `resources/js/pages/continuity-steward/Vault.vue` (respect access levels)

### Email templates (10 new)
- All under `resources/views/emails/vault/`

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
- Money always integer cents in DB
- `useConfirm`/`confirmAction` callback-based (never awaited)
- `useForm()` at `<script setup>` top level only
- Tabs use `v-show` (not `v-if`) to preserve state
- Globally registered: AegisIcon, AegisModal, AegisToast, AegisConfirm, AegisBadge, AegisStatChip, AegisHeroBanner, AegisCard, AegisEmptyState, IncidentBanner
- Local import: AegisDropzone and everything in components/modals/

---

## 7. Security invariants (vault-specific)

Vault has stricter rules than other pages because it stores sensitive material:

- **Credentials zone passwords MUST be encrypted at rest** using `Crypt::encrypt()` — plaintext never in DB
- **File uploads MUST use private disk** — never public
- **File names MUST be UUIDs** — never original names (leaks metadata)
- **Every read (view/download) MUST create a vault_access_log row** — no exceptions
- **Vault access changes MUST fire an event** — no silent updates
- **Deleting a linked vault item MUST be blocked** — referential integrity
- **CS vault_access default is 'scoped'** — never 'full' unless provider explicitly grants
- **SS never gets vault access** — hardcoded exclusion
- **Incident-triggered unlock is time-bounded** — auto-relock on incident close
- **Non-CS access grants MUST support expiry** — auto-revocation via daily command
- **Audit trail after incident MUST be immutable** — email + PDF snapshot to provider

---

## 8. Delivery order recommendation

Recommended sprint ordering:

**Sprint 1 (day 1):** Wave 1 → Wave 2 (foundation + zone views)
**Sprint 2 (day 2):** Wave 3 → Wave 4 (add/edit/remove item flows)
**Sprint 3 (day 3):** Wave 5 → Wave 6 (attestation + permissions)
**Sprint 4 (day 4):** Wave 7 → Wave 8 (required docs check + storage/compliance)
**Sprint 5 (day 5):** Wave 9 → Wave 10 → Wave 11 (integration + emails + testing)

Total estimated effort: **21.5 hours** across ~5 working sessions.

---

## 9. Rollback plan

Each wave is a discrete commit + PR. Rollback = revert single commit.
Migrations are additive (new tables/columns, no drops on existing data).
Existing `vault_items` rows work with all waves — old data stays valid.
Encryption migration for credentials zone is a one-way transformation — apply carefully with backup.

---

*Plan locked 2026-07-20. Any drift discovered during execution feeds back here.*
