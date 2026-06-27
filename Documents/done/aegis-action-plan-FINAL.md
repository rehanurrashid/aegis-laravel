# Aegis — Module-by-Module Action Plan (FINAL)

*What to FIX / REMOVE / ADD / CONNECT / RENAME per portal. All of Carizma's decisions locked in. Symmetry enforced across portals. This is the build-ready spec.*

**Version:** Final v2 · **Scope:** Practitioner / Continuity Steward / Support Steward portals · **Out of scope:** Business Partner portal (isolated), Admin Dashboard (not built)

---

## Legend

- 🔧 **FIX** — broken, stale, wrong, or needs polish
- 🗑️ **REMOVE** — delete entirely; wrong role, duplicate, or unused
- ➕ **ADD** — new feature that must be built
- 🔗 **CONNECT** — backend wire-up so data flows between portals
- 🏷️ **RENAME** — file or nav label rename for symmetry
- 🔒 **HOLD** — explicit client direction to wait
- ✅ **KEEP** — already correct, no change needed

---

## Section 0 — Client Decisions Locked In

Carizma confirmed the following. All further work aligns to these:

| # | Question | Decision |
|---|---|---|
| 1 | Canonical term for the core doc | **Continuity Plan** (replaces "Professional Will" everywhere) |
| 2 | Signature mechanics | Build whichever is **most authentic and easiest for users**. Recommendation below. |
| 3 | MAAT add-on naming | **MAAT Professional Continuity Steward Service** *(onboarding to be updated separately — see Appendix A)* |
| 4 | Alternate CS terminology | **Alternate CS** (not "Support CS" — avoids Support Steward conflict) |
| 5 | Certification granularity | **Whole-list certification** with an optional per-task flag for exceptions |

**Signature recommendation (for Decision #2):** Hybrid approach — user's verified legal name is pre-filled (authentic), but they click-to-type a 3-field confirmation (Name, Title, Date). This is the industry standard for e-signature (DocuSign, Adobe Sign pattern) — authentic, legally defensible, and one-click easy for users.

---

## Section 1 — Platform-Wide Symmetry Decisions

These apply identically across all three portals and take precedence over any individual module spec.

### 1.1 Messages Module — Unified Spec (all portals)

| Action | Item |
|---|---|
| 🗑️ REMOVE | Voice and video call buttons — everywhere, all portals. Aegis does not host calls. |
| ✅ UNIFIED STRUCTURE | Inbox with contact list + thread view. End-to-end encrypted. Files, images, voice notes (recorded + sent, NOT live calls), reactions, pinning, templates, search. |
| ➕ ADD | **"Continuity Contacts" pinned section** at top of inbox on every portal — shows the assigned Practitioner + Primary SS + Alternate SS + Primary CS + Alternate CS + Aegis Support. |
| ➕ ADD | **Critical Incident badge** on affected threads during an active incident. All messages in that thread are audit-flagged as legal record. |
| 🔧 FIX | Remove "Aegis Pro" badge or any tier-specific peer-to-peer referral styling on Practitioner Messages — messaging is identical across tiers. |

### 1.2 Activity Log — The Single Notification Truth (all portals)

This replaces the separate Alerts page on SS portal. One unified event stream per user.

| Action | Item |
|---|---|
| 🗑️ REMOVE | **SS Portal `alerts.php`** — entire module deleted. Its categories (Emergency, Missed Check-In, Agreement, Absence, Compliance, System) become event types inside the unified Activity Log. |
| 🗑️ REMOVE | CS Portal Activity Log's 2-tile hero (Critical Incident + Tasks) — replaced by unified event feed. |
| ➕ ADD | **Unified `activity.php` on every portal** recording every event scoped to that user: messages received, tasks created/completed, documents signed, incidents triggered, verifications, vault access, compliance alerts, attestations, payment events, login events, permission changes. |
| ➕ ADD | **Event type taxonomy** (identical across portals): `message`, `task`, `document`, `incident`, `vault`, `compliance`, `attestation`, `payment`, `account`, `system`. Each rendered with a colored icon + severity (info / warning / critical). |
| ➕ ADD | **Header bell popup** on every portal — shows latest 5-10 activity events as a dropdown preview. Clicking "View All" opens `activity.php`. Unread counter badge. Mark-all-read action. |
| ➕ ADD | **Filters in `activity.php`**: by event type, by severity, by date range, by provider (for CS/SS who have multiple), by read/unread. |
| ➕ ADD | **Export** activity log (CSV / PDF) for legal or audit purposes. |
| 🔗 CONNECT | All user-visible actions across every module write to Activity Log. No action goes unrecorded. |
| 🔗 CONNECT | Header bell popup polls the activity stream in real-time (or on-load for v1). |

### 1.3 File Naming Convention — Symmetric Across Portals

Current file names are inconsistent. Final naming lock:

| Canonical filename | Provider Portal | CS Portal | SS Portal |
|---|---|---|---|
| `overview.php` | ✅ keep | ✅ keep | ✅ keep |
| `dashboard.php` *(rename from index.php)* | 🏷️ rename `index.php` → `dashboard.php` (keep index.php as redirect) | 🏷️ rename `index.php` → `dashboard.php` | 🏷️ rename `index.php` → `dashboard.php` |
| `profile.php` | ✅ keep | ✅ keep | ✅ keep |
| `edit-profile.php` | ✅ keep | ✅ keep | ✅ keep |
| `continuity-plan.php` | 🏷️ new (the Builder page) | — | — |
| `continuity-stewards.php` | 🏷️ rename `executors.php` → `continuity-stewards.php` | — | 🏷️ rename `executors.php` → `continuity-stewards.php` |
| `support-stewards.php` | 🏷️ rename `dsr.php` → `support-stewards.php` | — | — |
| `providers.php` | — | ✅ keep | ✅ keep |
| `my-tasks.php` | — | 🏷️ rename `assignments.php` → `my-tasks.php` | 🏷️ rename `tasks.php` → `my-tasks.php` |
| `important-documents.php` | 🏷️ rename `agreements.php` → `important-documents.php` | 🏷️ rename `agreements.php` → `important-documents.php` | 🏷️ rename `agreements.php` → `important-documents.php` |
| `vault.php` | 🏷️ rename `documents.php` → `vault.php` | ✅ keep | — |
| `continuity-management.php` | — | 🏷️ rename `emergency.php` → `continuity-management.php` | — |
| `critical-incident-log.php` | — | — | 🏷️ rename `emergency.php` → `critical-incident-log.php` |
| `finances.php` | ✅ keep | 🏷️ rename `financials.php` → `finances.php` | — |
| `messages.php` | ✅ keep | ✅ keep | ✅ keep |
| `activity.php` | ✅ keep | ✅ keep | ✅ keep |
| `news.php` | ✅ keep | — | — |
| `events.php` | ✅ keep | — | — |
| `job-postings.php` | ✅ keep | — | — |
| `referrals.php` | ✅ keep | — | — |
| `network.php` *(Integrative Network)* | ✅ keep | — | — |
| `settings.php` | ✅ keep | ✅ keep | ✅ keep |

**Migration note:** For every rename, leave a lightweight redirect at the old URL for 30 days so external bookmarks/email links do not 404. Update all internal links, sidebar references, and JavaScript navigation at the same time.

### 1.4 Settings — Read-Only Profile Summary Pattern (all portals)

Already correct on CS portal. Apply identical pattern to Practitioner and SS portals:

| Action | Item |
|---|---|
| 🔧 FIX | Identity + contact fields appear in **Profile / Edit Profile only**. Settings shows a read-only Profile Summary card with "Edit Full Profile" CTA. No duplication. |
| ➕ ADD | "Auto-Synced" badge on the Settings Profile Summary, matching CS portal pattern. |

### 1.5 Terminology Sweep (all portals)

Final pass. Every instance of these must be corrected:

| Find | Replace with |
|---|---|
| Professional Will | **Continuity Plan** |
| Executor | **Continuity Steward** |
| DSR / Designated Support Representative | **Support Steward** |
| Emergency *(user-facing)* | **Critical Incident** |
| Agreement *(core continuity doc)* | **Important Document** or **Continuity Plan** depending on context |
| Escrow | *(remove entirely; replace with Stripe Connect language)* |
| KALINK | **Aegis** |
| Patients | **Clients** |

### 1.6 Shared Data Backbone (prerequisite for all CONNECT items)

This is backend work, not UI. Must be built first or in parallel.

| Action | Item |
|---|---|
| ➕ ADD | **`ContinuityPlan` record (one per Practitioner)** — contains: practitioner identity, Primary SS + Alternate SS with per-incident authorization matrix, Primary CS + Alternate CS + Secondary CS with per-incident authorization matrix, 7-incident-type config matrix `{enabled, docs_required[], SS_tasks[], CS_tasks[], timelines[], authorized_stewards[]}`, attestation states (Practitioner signed timestamp, SS certified timestamp, CS certified timestamp, per-task exception flags), vault manifest, annual review date, signature record. |
| ➕ ADD | **`CriticalIncident` record (one per active event)** — provider_id, type, reporting SS, trigger timestamp, verify timestamp, CS, uploaded docs, task completion log with timestamps, closure summary, audit trail. |
| ➕ ADD | **`ActivityEvent` record (one per logged action)** — user_id, portal, event_type, severity, provider_id (if scoped), module, action, description, timestamp, read_status, related_record_id. |

---

## Section 2 — 🔵 PRACTITIONER PORTAL (`/provider-portal/`)

### Module 1 — Overview (`overview.php`)

| Action | Item |
|---|---|
| 🔧 FIX | Rename "Professional Will" → **Continuity Plan** in all Key Terms text and the "How to Use" section. |
| 🔧 FIX | Key Terms order — Practitioner / Continuity Steward / Support Steward in that order, then supporting terms. |
| 🔧 FIX | Remove any remaining MAAT add-on references that say "Professional Executor" → **MAAT Professional Continuity Steward Service**. |
| ✅ KEEP | 4-tab structure (Key Terms / Why Aegis / How to Use / FAQ). |

### Module 2 — Dashboard (`dashboard.php`, rename from `index.php`)

| Action | Item |
|---|---|
| 🏷️ RENAME | `index.php` → `dashboard.php` (keep index.php as redirect for 30 days). |
| ➕ ADD | **Continuity Plan Status Card** — three chips: *Plan Active · SS Certified · CS Certified* — each with timestamp of last attestation. Direct answer to Carizma's "how do I know the pieces are talking?" concern. |
| ➕ ADD | **Support Team Limit counter** — "X of 5 designated" (Primary CS + Alternate CS + Primary SS + Alternate SS + optional Secondary CS). Visible on Dashboard and in Settings. |
| ➕ ADD | **MAAT add-on indicator** — if Practitioner opted in at $29/mo, show badge with "Manage" link. |
| 🔧 FIX | "Activate Succession" button → **Activate Continuity Plan**. |
| 🔧 FIX | "Annual Professional Will Review Due" banner → **Annual Continuity Plan Review Due**. |
| 🔧 FIX | Header bell popup rewired to new unified Activity Log (Section 1.2). |
| 🔗 CONNECT | Status chips read from `ContinuityPlan.attestation_states`. |
| 🔗 CONNECT | Critical incident banner reads from `CriticalIncident` record when SS triggers. |

### Module 3 — My Profile / Edit Profile (`profile.php`, `edit-profile.php`)

| Action | Item |
|---|---|
| 🔧 FIX | Verify zero terminology leftovers in sidebar when viewed from Profile page. |
| 🔗 CONNECT | Practitioner edits push to `ContinuityPlan.practitioner` so SS and CS see updated contact info immediately. |
| 🔗 CONNECT | Public profile read by assigned SS and CS only (scoped, not open). |

### Module 4 — Job Postings (`job-postings.php`)

| Action | Item |
|---|---|
| ✅ KEEP | Practice tier only, hidden on Access. Outside continuity mission. |
| 🔗 CONNECT | Business Partners read postings. No SS/CS visibility. |

### Module 5 — Referrals (`referrals.php`)

| Action | Item |
|---|---|
| ✅ KEEP | Practice tier only, locked on Access (upgrade modal). Outside continuity mission. |
| 🔗 CONNECT | Sender ↔ Receiver Practitioner only. No SS/CS visibility. |

### Module 6 — Integrative Network (`network.php`)

| Action | Item |
|---|---|
| ✅ KEEP | 7-tab structure correct (Search Providers, Integrative Network, Business Partners, Search BPs, Shadow Network, My Shadows, Configuration). |
| ➕ ADD | Verify manual shadow entry exists (for providers not yet on Aegis). |
| 🔗 CONNECT | **Shadow Network list written here populates CS's Shadow Network widget.** Single cross-portal connection point. |

### Module 7 — Continuity Stewards (`continuity-stewards.php`, rename from `executors.php`)

| Action | Item |
|---|---|
| 🏷️ RENAME | `executors.php` → `continuity-stewards.php`. |
| 🔧 FIX | Verify no "Executor" display text remains. |
| 🔧 FIX | Introduce **Alternate CS** role (not "Support CS"). Existing Secondary/Tertiary can remain as structural options but the primary pair is Primary CS + Alternate CS. |
| ➕ ADD | **Per-incident-type authorization matrix per CS** — for each of the 7 incident types, toggle which CS (Primary / Alternate / Secondary) is authorized. Written to `ContinuityPlan.authorized_stewards`. |
| ➕ ADD | **"Copy tasks from Primary CS to Alternate CS"** button in wizard Step 3 — Carizma's explicit ask. Writes full task set to Alternate; Practitioner can edit from there. |
| ➕ ADD | **Countersignature status** per CS (Signed / Pending Signature / Not Sent). Links to re-send. |
| 🔗 CONNECT | Writes CS identity + role + vault access grants → CS Portal hydrates `my-tasks.php` and `providers.php`. |
| 🔗 CONNECT | New CS added triggers email invitation (external) or in-app notification (existing Aegis user). |
| 🔗 CONNECT | CS countersignature event writes to `ContinuityPlan.attestation_states.cs_certified` → Dashboard chip flips. |

### Module 8 — Support Stewards (`support-stewards.php`, rename from `dsr.php`)

| Action | Item |
|---|---|
| 🏷️ RENAME | `dsr.php` → `support-stewards.php`. |
| 🔧 FIX | Verify zero "DSR" display text remains. |
| ➕ ADD | **Primary + Alternate SS roles** — currently only Active/Suspended states exist. Add explicit Primary / Alternate designation. |
| ➕ ADD | **Per-incident-type authorization matrix per SS** — for each of the 7 incident types, toggle which SS (Primary / Alternate) can *trigger* that incident. |
| ➕ ADD | **"Copy tasks from Primary SS to Alternate SS"** same pattern as CS. |
| 🔗 CONNECT | Writes SS identity + permission set → SS Portal hydrates. |
| 🔗 CONNECT | SS task-list certification event writes to `ContinuityPlan.attestation_states.ss_certified` → Dashboard chip flips. |

### Module 9 — Continuity Plan Builder (`continuity-plan.php`, NEW PAGE)

This is the biggest new module. It is the single biggest missing piece and resolves ~80% of Carizma's 14 numbered questions from her workflow email.

| Action | Item |
|---|---|
| ➕ ADD | **New dedicated page** — the Continuity Plan Builder. Linked from sidebar under "Continuity" section and from Dashboard. |
| ➕ ADD | **7-row × config-column grid**: rows are the 7 critical moment types (Death, Short-Term Incapacitation, Long-Term Incapacitation, Missing Person**, Detainment**, Natural Disaster**, Geopolitical**). Columns: Enabled?, Docs Required (multi-select: Death Cert / Doctor Note / Police Report / Other), Authorized SS, Authorized CS, SS Tasks, CS Tasks, Timelines. |
| ➕ ADD | **Row editor modal** — when Practitioner clicks a row, opens a detail modal with: enable toggle, docs-required checklist with "Other+text" option, authorized steward dropdown, SS task grid (pre-listed Aegis suggestions + custom add + timeline per task), CS task grid (same pattern), "Copy from primary" option. |
| ➕ ADD | **Save Draft / Finalize & Sign** flow. Draft state editable. Finalized state locks the plan, generates signed PDF, saves to `important-documents.php`, notifies SS and CS to certify. |
| ➕ ADD | **Three entry paths all write to the same `ContinuityPlan` record**: (a) fill in the grid inline, (b) upload a completed PDF/DOC (parsed where possible, manually tagged otherwise), (c) start from Aegis Document Library template. |
| ➕ ADD | **Signature block** — hybrid e-signature: verified legal name pre-filled, user types Title + Date in two fields + clicks "Apply Signature." Timestamp + IP logged for legal defensibility. |
| 🔗 CONNECT | Writes to `ContinuityPlan` record — the single source of truth for all three portals. |
| 🔗 CONNECT | On Finalize → notifications to SS + CS + PDF copy appears in all three portals' Important Documents. |

### Module 10 — Important Documents (`important-documents.php`, rename from `agreements.php`)

| Action | Item |
|---|---|
| 🏷️ RENAME | `agreements.php` → `important-documents.php`. |
| 🔧 FIX | Professional Will section header → **Continuity Plan**. |
| ➕ ADD | **Aegis Document Library on Practitioner side** — mirror the 12 templates that exist on CS side. Carizma explicitly requested this here. |
| ➕ ADD | **Continuity Plan card** at top of the page — links to the Builder (`continuity-plan.php`). Shows status (Draft / Active / Annual Review Due / Expired). |
| ➕ ADD | **Signature mechanics** — hybrid model described in Section 0 Decision #2. Verified name pre-filled, Title + Date typed, timestamp + IP captured. |
| 🔗 CONNECT | Signed Continuity Plan + addendums written here flow to SS `important-documents.php` and CS `important-documents.php`. |
| 🔗 CONNECT | Finalized plan triggers notifications to SS + CS for certification. |

### Module 11 — Document Vault (`vault.php`, rename from `documents.php`)

| Action | Item |
|---|---|
| 🏷️ RENAME | `documents.php` → `vault.php` (symmetry with CS portal). |
| ✅ KEEP | Three-zone structure (Standard / Emergency Vault locked / Secure Credentials locked) + Client Roster tab. |
| 🔧 FIX | Align Keeper Security integration UI with CS side. |
| ➕ ADD | **Documentation-required-per-incident toggle** — Practitioner can flag which docs are required for which critical moment. Read by CS Verify modal which enforces upload on activation. |
| 🔗 CONNECT | Emergency zone + Roster + Credentials → CS vault, gated by SS trigger + CS verification. |
| 🔗 CONNECT | Every CS read-access event writes to Activity Log visible to Practitioner/estate. |

### Module 12 — Finances (`finances.php`)

| Action | Item |
|---|---|
| 🔧 FIX | **Remove Escrow model entirely** — replace with Stripe Connect. Practitioner side currently stale. |
| 🔧 FIX | Replace "Top Up Escrow" / "Escrow Balance" / "Pay-on-Deploy" with 4-option payment model: **Retainer · Direct Invoice · Retainer + Activation · Other**. |
| 🗑️ REMOVE | "Escrow Activity Log" section → replaced by generic "Payment History." |
| 🔗 CONNECT | Invoice Approval ← receives Stripe invoices from CS Portal. |
| 🔗 CONNECT | Payment Methods ← Business Partner invoices unchanged (isolated per client instruction). |
| 🔒 HOLD | Detailed financial workflows still on hold pending Carizma's attorney/accountant review. Make structural fixes above; leave the rest. |

### Module 13 — Messages (`messages.php`) — unified spec per Section 1.1

| Action | Item |
|---|---|
| 🗑️ REMOVE | Voice/video buttons (Section 1.1). |
| 🗑️ REMOVE | "Aegis Pro" badge and clinical/business tabs — messaging is not tier-differentiated. |
| ➕ ADD | **Continuity Contacts pinned section** — assigned Primary SS + Alternate SS + Primary CS + Alternate CS + Aegis Support. |
| ➕ ADD | Critical Incident badge on affected threads when active. |
| 🔗 CONNECT | Threads mirror to SS and CS portals. |

### Module 14 — Activity Log (`activity.php`) — unified spec per Section 1.2

| Action | Item |
|---|---|
| ➕ ADD | **Unified event feed** — every event across every module scoped to this Practitioner. |
| ➕ ADD | Event type filter, severity filter, date range filter, provider filter *(N/A for Practitioner — they have one practice)*, read/unread. |
| ➕ ADD | Export CSV/PDF. |
| 🔗 CONNECT | Header bell popup reads from this feed. |

### Module 15 — News & Resources (`news.php`)

| Action | Item |
|---|---|
| ✅ KEEP | Peripheral to continuity. Community feed + Library section fine. |

### Module 16 — Events (`events.php`)

| Action | Item |
|---|---|
| ✅ KEEP | CEU tracking + conference registration. Peripheral. |

### Module 17 — Settings (`settings.php`) — applies Section 1.4 pattern

| Action | Item |
|---|---|
| 🔧 FIX | Apply read-only Profile Summary pattern. Identity + contact fields only editable in Profile. |
| ➕ ADD | **Plan Attestation panel** — timeline of annual re-attestations and most recent SS/CS certifications. |
| ➕ ADD | **Support Team Limit counter** here too (mirror of Dashboard counter). |
| 🔗 CONNECT | Tier upgrade Access → Practice with cookie + query param persistence. |

---

## Section 3 — 🟡 CONTINUITY STEWARD PORTAL (`/continuity-steward-portal/`)

### Module 1 — Overview (`overview.php`)

| Action | Item |
|---|---|
| ✅ KEEP | CS listed first in Key Terms. 4-tab structure. |

### Module 2 — Dashboard (`dashboard.php`, rename from `index.php`)

| Action | Item |
|---|---|
| 🏷️ RENAME | `index.php` → `dashboard.php` (keep redirect). |
| ✅ KEEP | Structure correct (Good Morning → Critical Incident banner → Provider Overview tiles → Shadow Network → Quick Actions). |
| 🔧 FIX | Header bell popup rewired to unified Activity Log. |
| 🔗 CONNECT | Provider Overview tiles read from shared records (currently mock). |
| 🔗 CONNECT | Critical Incident banner reads from `CriticalIncident` record. |

### Module 3 — My Profile / Edit Profile (`profile.php`, `edit-profile.php`)

| Action | Item |
|---|---|
| ✅ KEEP | 5-tab structure (Basic / Credentials / Continuity Plan framework / Fee / Verification). |
| 🔧 FIX | Aegis Verification module — verify Checkr background check wiring, Code of Conduct signature, public profile badge. |

### Module 4 — My Tasks (`my-tasks.php`, rename from `assignments.php`)

| Action | Item |
|---|---|
| 🏷️ RENAME | `assignments.php` → `my-tasks.php` (symmetry with SS portal). |
| 🗑️ REMOVE | Any remaining "Executor" labels in quick actions. |
| ➕ ADD | **Per-provider, per-incident-type task grouping** — during an active incident, that incident's tasks pinned at top; standby tasks collapsed. |
| ➕ ADD | **Whole-list certification checkbox** + optional per-task exception flag (Section 0 Decision #5). Writes to `ContinuityPlan.attestation_states.cs_certified` → Practitioner Dashboard chip. |
| 🔗 CONNECT | **Task list hydrates from `ContinuityPlan.cs_tasks`** per incident type + authorized CS. Not hardcoded. |

### Module 5 — My Providers (`providers.php`)

| Action | Item |
|---|---|
| ✅ KEEP | Caseload search, Refer-from-Roster modal, Invite Provider gating correct. |
| ➕ ADD | **"Request CS Role Change"** — notifies Practitioner of requested release or role shift. Does not unilaterally change anything. |
| 🔗 CONNECT | Cards hydrate from `ContinuityPlan` per provider. |
| 🔗 CONNECT | Invited CS shows exactly 1 provider; Business CS shows 2–40; Enterprise unlimited. Enforce in cards, Invite button, Settings. |

### Module 6 — Important Documents (`important-documents.php`, rename from `agreements.php`)

| Action | Item |
|---|---|
| 🏷️ RENAME | `agreements.php` → `important-documents.php`. |
| ✅ KEEP | Structure + Aegis Sample Forms Library + per-CS folder selector. |
| ➕ ADD | **Countersignature UI for incoming Continuity Plans** — when Practitioner sends plan, CS sees "Review & Sign" CTA here. Hybrid signature model per Section 0 Decision #2. |
| ➕ ADD | **Annual Re-Attestation button** per plan. Writes to Practitioner Dashboard. |
| 🔗 CONNECT | Reads from `ContinuityPlan` — currently mock. |

### Module 7 — Finances (`finances.php`, rename from `financials.php`)

| Action | Item |
|---|---|
| 🏷️ RENAME | `financials.php` → `finances.php` (symmetry with Practitioner portal). |
| ✅ KEEP | Stripe Connect model is attorney-approved. |
| 🔗 CONNECT | Invoice-sent events → Practitioner Finances Invoice Approval panel. |
| 🔒 HOLD | Fee structure defaults pending Carizma's attorney/accountant review. |

### Module 8 — Continuity Management (`continuity-management.php`, rename from `emergency.php`)

| Action | Item |
|---|---|
| 🏷️ RENAME | `emergency.php` → `continuity-management.php`. |
| ✅ KEEP | Core CS function — cockpit structure correct. |
| 🔧 FIX | Verify False Reporting Warning block with approved text. |
| ➕ ADD | **Documentation-required enforcement** — if Practitioner flagged an incident type requires a death cert / doctor's note / police report, Verify modal must require upload to proceed. |
| 🔗 CONNECT | Verify action writes to `CriticalIncident.verify_timestamp` → unlocks vault tabs → notifies SS + Practitioner emergency contact. |
| 🔗 CONNECT | Task checklist hydrates from `ContinuityPlan.cs_tasks[incident_type]` + authorized CS. |
| 🔗 CONNECT | Submit Final Report → writes to `CriticalIncident.closure` → SS critical incident log status = Closed. |

### Module 9 — Document Vault (`vault.php`)

| Action | Item |
|---|---|
| ✅ KEEP | 3-tab structure (Support Documents / Client Roster / Secure Credentials) + Keeper + gated access. |
| 🔗 CONNECT | Standby: sealed. Post-verification: unlocks and reads from Practitioner Vault (Emergency zone + Roster + Credentials). |
| 🔗 CONNECT | Every view/download/reveal event → Activity Log for CS + Practitioner/estate audit. |
| 🔗 CONNECT | Roster Refer button → flows to Shadow Network referral modal. |

### Module 10 — Messages (`messages.php`) — unified spec per Section 1.1

| Action | Item |
|---|---|
| 🗑️ REMOVE | Voice/video buttons. |
| ✅ KEEP | Scoped correctly: SS + Practitioners + Aegis Team. |
| ➕ ADD | Continuity Contacts pinned section. |
| ➕ ADD | Critical Incident badge on affected threads when active. |
| 🔗 CONNECT | Threads mirror to Practitioner and SS portals. |

### Module 11 — Activity Log (`activity.php`) — unified spec per Section 1.2

| Action | Item |
|---|---|
| 🗑️ REMOVE | Current 2-tile hero (Critical Incident + Tasks) — replaced by unified feed. |
| ➕ ADD | Unified event feed across all modules and providers. |
| ➕ ADD | Filters: event type, severity, date range, **provider** (CS has multiple), read/unread. |
| ➕ ADD | Export CSV/PDF. |
| 🔗 CONNECT | Header bell popup reads from this feed. |

### Module 12 — Settings (`settings.php`) — applies Section 1.4 pattern

| Action | Item |
|---|---|
| ✅ KEEP | Profile Summary auto-sync pattern is the reference other portals adopt. |
| 🔗 CONNECT | Subscription panel enforces Invited (free, 1) vs Business ($69/mo, 2–40) vs Enterprise (custom). |
| 🔗 CONNECT | Payouts panel Stripe OAuth end-to-end verified. |

---

## Section 4 — 🟢 SUPPORT STEWARD PORTAL (`/support-steward-portal/`)

### Module 1 — Overview (`overview.php`)

| Action | Item |
|---|---|
| ✅ KEEP | Compassionate framing intentional. 4-tab structure. |

### Module 2 — Dashboard (`dashboard.php`, rename from `index.php`)

| Action | Item |
|---|---|
| 🏷️ RENAME | `index.php` → `dashboard.php` (keep redirect). |
| ✅ KEEP | Report Critical Incident CTA in hero. |
| 🔧 FIX | Header bell popup rewired to unified Activity Log. |
| 🔗 CONNECT | Provider cards, Upcoming Tasks widget, Recent Activity hydrate from shared records. |

### Module 3 — My Profile / Edit Profile (`profile.php`, `edit-profile.php`)

| Action | Item |
|---|---|
| ✅ KEEP | About Me note for CS. |
| 🔗 CONNECT | Readable by assigned Practitioners + their CS. |

### Module 4 — My Providers (`providers.php`)

| Action | Item |
|---|---|
| ✅ KEEP | Caseload search, filters, detail panel. |
| 🔗 CONNECT | Rows hydrate from `ContinuityPlan` per Practitioner. |
| 🔗 CONNECT | Re-Attestation Date pulls from `ContinuityPlan.annual_review_date`. |

### Module 5 — My Tasks (`my-tasks.php`, rename from `tasks.php`)

| Action | Item |
|---|---|
| 🏷️ RENAME | `tasks.php` → `my-tasks.php` (symmetry with CS portal). |
| ✅ KEEP | 4-list structure (Planning / Aegis Informational / SS Critical Moment / CS Critical Moment read-only). |
| ➕ ADD | **Whole-list certification checkbox** for SS Critical Moment Task List + optional per-task exception flag (Section 0 Decision #5). Writes to `ContinuityPlan.attestation_states.ss_certified` → Practitioner Dashboard chip. |
| ➕ ADD | Verify custom task addition for Planning list (already exists; confirm). |
| 🔗 CONNECT | **Lists 3 (SS tasks) and 4 (CS tasks, read-only) hydrate from `ContinuityPlan.ss_tasks` and `cs_tasks`.** The labels currently lie — "pre-populated from provider entry" but nothing populates. Fix this. |

### Module 6 — Important Documents (`important-documents.php`, rename from `agreements.php`)

| Action | Item |
|---|---|
| 🏷️ RENAME | `agreements.php` → `important-documents.php`. |
| ✅ KEEP | Read-only scope correct. |
| 🔧 FIX | Document type filter: Continuity Plan / Addendum / Contract-MOU / BAA / Other. Verify. |
| 🔗 CONNECT | Hydrates from Practitioner's signed documents. |

### Module 7 — Continuity Stewards (`continuity-stewards.php`, rename from `executors.php`)

| Action | Item |
|---|---|
| 🏷️ RENAME | `executors.php` → `continuity-stewards.php`. |
| 🗑️ REMOVE | **The 4-step "Add New CS" flow** — role conflict. Only Practitioner designates a CS. |
| 🗑️ REMOVE | "Task List 4: Unresponsive CS" in its current CS-scoped form. Move the concept to `my-tasks.php` as an on-demand checklist triggered by the "Notify Practitioner — CS Unresponsive" action below. |
| ✅ KEEP | Read-only list of CS designations per provider. |
| ➕ ADD | **"Notify Practitioner — CS Unresponsive"** button — writes an alert event to Practitioner Activity Log + header bell. Does NOT change designation. |
| 🔗 CONNECT | Reads from each Practitioner's `ContinuityPlan.cs_designations`. |

### Module 8 — Critical Incident Log (`critical-incident-log.php`, rename from `emergency.php`)

| Action | Item |
|---|---|
| 🏷️ RENAME | `emergency.php` → `critical-incident-log.php`. |
| ✅ KEEP | Core SS function. Trigger mechanics correct. |
| 🔧 FIX | **Incident type dropdown → 7 types** (Missing Person**, Detainment**, Death, Short-Term Incapacitation, Long-Term Incapacitation, Natural Disaster**, Geopolitical or Conflict Related Events**). Mark 4 opt-in types with ** and disable if Practitioner hasn't enabled them. |
| 🔧 FIX | Rename any remaining "Emergency" labels → "Critical Incident". |
| 🔗 CONNECT | Report action writes `CriticalIncident` record → CS receives multi-channel alert. |
| 🔗 CONNECT | **SS may only trigger incident types the Practitioner has enabled AND for which this SS is authorized.** Enforced via `ContinuityPlan.authorized_stewards`. |
| 🔗 CONNECT | Close action writes closure event + resolution summary → Practitioner/estate notified. |

### Module 9 — Messages (`messages.php`) — unified spec per Section 1.1

| Action | Item |
|---|---|
| 🗑️ REMOVE | Voice/video buttons. |
| ✅ KEEP | Aegis Support Team pinned. |
| ➕ ADD | Continuity Contacts pinned section. |
| ➕ ADD | Critical Incident badge on affected threads when active. |
| 🔗 CONNECT | Threads mirror to Practitioner and CS portals. |

### Module 10 — Activity Log (`activity.php`) — unified spec per Section 1.2

| Action | Item |
|---|---|
| 🗑️ REMOVE | **`alerts.php` — entire module deleted.** All former alert categories become event types inside unified Activity Log. |
| ➕ ADD | Unified event feed across all modules and providers. |
| ➕ ADD | Filters: event type, severity, date range, **provider** (SS has 2–8 typically), read/unread. |
| ➕ ADD | Export CSV/PDF. |
| 🔗 CONNECT | Header bell popup reads from this feed. |
| 🔗 CONNECT | Compliance events (license expiring, re-attestation overdue) + CS action events (verified, vault accessed) stream here. |

### Module 11 — Settings (`settings.php`) — applies Section 1.4 pattern

| Action | Item |
|---|---|
| 🔧 FIX | Apply read-only Profile Summary pattern (match CS portal). |
| ✅ KEEP | Other settings sections (Account, 2FA, Notifications, Provider Defaults, Privacy, Appearance, Accessibility, Account Actions). |

### 🗑️ REMOVED Module — Alerts (`alerts.php`)

Merged entirely into unified Activity Log. Every alert category becomes an event type:

| Old Alerts category | New Activity Log event type |
|---|---|
| Emergency | `incident` / severity: critical |
| Missed Check-In | `compliance` / severity: warning |
| Agreement | `document` / severity: info |
| Absence | `compliance` / severity: info |
| Compliance | `compliance` / severity: varies |
| System | `system` / severity: info |

---

## Section 5 — Updated Sidebar Layout (symmetric across portals)

After the rename sweep, sidebars should look like this:

### 🔵 Practitioner Portal Sidebar

```
Main
  Overview — Start Here
  Dashboard
  My Profile

My Practice        (Practice tier only)
  Job Postings
  Referrals
  Integrative Network

Continuity
  Continuity Plan                (NEW — the Builder)
  Continuity Stewards
  Support Stewards
  Important Documents
  Document Vault
  Finances

Communication
  Messages
  Activity Log

Explore
  News & Resources
  Events

Account
  Settings
```

### 🟡 Continuity Steward Portal Sidebar

```
Main
  Overview — Start Here
  Dashboard
  My Profile

My Work
  My Tasks
  My Providers
  Important Documents
  Finances

Critical Incident
  Continuity Management
  Document Vault

Communication
  Messages
  Activity Log

Account
  Settings
```

### 🟢 Support Steward Portal Sidebar

```
Main
  Overview — Start Here
  Dashboard
  My Profile

Critical Moment Plans
  My Providers

Activation
  My Tasks
  Important Documents
  Continuity Stewards
  Critical Incident Log

Communication
  Messages
  Activity Log                   (replaces deleted Alerts)

Account
  Settings
```

---

## Section 6 — Priority Order for Build-Out

### Tier 1 — Must-do first (unblocks everything)

1. Build shared `ContinuityPlan`, `CriticalIncident`, and `ActivityEvent` records (backend).
2. Practitioner **Continuity Plan Builder page** (`continuity-plan.php`) — the 7 × config grid.
3. Practitioner Important Documents Aegis Library + Continuity Plan card.
4. Connect Practitioner writes → SS `my-tasks.php` Lists 3+4 + CS `my-tasks.php`.
5. Add per-incident-type authorization matrices in both Practitioner `continuity-stewards.php` and `support-stewards.php`.
6. Hybrid e-signature component (reusable across three signature points: Continuity Plan, CS countersignature, SS/CS certification).

### Tier 2 — Closes Carizma's workflow-email loop

7. Attestation/certification loop — SS certifies, CS certifies, Practitioner Dashboard chips flip.
8. Documentation-required-per-incident toggle in Vault + enforce in CS Verify modal.
9. Alternate SS + Alternate CS concept + "Copy tasks from Primary" button.

### Tier 3 — Platform-wide symmetry sweep (do as single sweeps)

10. **File rename sweep** — all renames in Section 1.3, with 30-day redirects.
11. **Unified Activity Log** build (Section 1.2) — new `activity.php` structure on all three portals + header bell popup.
12. **Delete SS `alerts.php`** — merge into Activity Log.
13. **Messages unified spec** (Section 1.1) — Continuity Contacts pinned section, remove voice/video, remove tier-specific styling on Practitioner Messages.
14. **Settings read-only Profile Summary pattern** on Practitioner and SS portals (Section 1.4).
15. **Terminology sweep** (Section 1.5) — final pass to zero out Professional Will, Executor, DSR, Emergency, Escrow, KALINK, Patients leftovers.

### Tier 4 — Platform cleanup

16. Finances escrow removal on Practitioner side + 4-option Stripe Connect model.
17. SS Portal remove Add-New-CS flow + add "Notify Practitioner — CS Unresponsive" button.
18. SS Critical Incident 7-type alignment with opt-in ** markers.

### Tier 5 — Nice-to-have polish

19. Support Team Limit counter (Dashboard + Settings on Practitioner portal).
20. MAAT add-on status indicator on Practitioner Dashboard.
21. Verify Keeper credentials UI consistency Practitioner ↔ CS.
22. CS Aegis Verification module end-to-end test (Checkr, Code of Conduct, badge).

---

## Section 7 — Carizma Concerns → Where They're Addressed

| Her Concern | Addressed By |
|---|---|
| "How do pieces inform each other?" | Dashboard status chips + shared `ContinuityPlan` record (Tier 1) + unified Activity Log (Tier 3) |
| SS tasks flow from Practitioner | SS `my-tasks.php` + Practitioner Continuity Plan Builder (Tier 1) |
| CS tasks flow from Practitioner | CS `my-tasks.php` + Practitioner Continuity Plan Builder (Tier 1) |
| Alt SS/CS pre-populate from Primary | Practitioner `continuity-stewards.php` + `support-stewards.php` "Copy from Primary" (Tier 2) |
| Docs required per critical moment | Practitioner Vault toggle + CS Verify enforcement (Tier 2) |
| Plan saves to Important Documents for all 3 parties | Builder CONNECT (Tier 1) |
| SS/CS certification → Practitioner dashboard | Certification loop (Tier 2) |
| Practitioner attestation → SS/CS | Dashboard chips on all three (Tier 2) |
| Embedded editable Continuity Plan form | Continuity Plan Builder (Tier 1) |
| Critical Moments grid | The Builder IS the grid (Tier 1) |
| Signature mechanics | Hybrid e-signature component (Tier 1) |
| Escrow not required | Finances FIX (Tier 4) |
| Keeper Security | Implemented on CS; verify Practitioner (Tier 5) |
| DSR → Support Steward | Terminology sweep (Tier 3 item 15) |
| Vault locked except on verified incident | Already correct ✅ |
| Provider without Aegis profile — CS invite flow | Option 3 confirmed ✅ |
| 7 critical moment types with opt-in markers | SS `critical-incident-log.php` FIX (Tier 4) |
| Fee request and payment collection | Stripe Connect (done on CS side) + Practitioner Finances FIX (Tier 4) |
| Messages not scoped to continuity stakeholders | Continuity Contacts pinned section (Tier 3 item 13) |

---

## Appendix A — Onboarding Changes (scheduled separately per client)

To be applied in a later session, not in this sweep. Included here for tracking:

| Step | Change |
|---|---|
| Step 1 | "Executor" role card → **Continuity Steward** |
| Step 2 | "Executor Pathway" → **Continuity Steward Pathway** |
| Step 8 | "MAAT Professional Executor Service" → **MAAT Professional Continuity Steward Service** |
| Step 10 | Rename items 4 and 5 away from "Executor" |
| Step 6 / Step 10 | Support Team Limit counter (2–5 CS+SS total) is already referenced — carry into Practitioner Dashboard (Tier 5) |
| Post-signup redirect URLs | Ensure state params carry: Practitioner ± tier, CS ± invited, SS emergency=false |

---

## Appendix B — Shared Data Backbone Sketch

Shown here so backend work can begin in parallel with frontend polish.

```
ContinuityPlan {
  plan_id
  practitioner_id
  status: draft | active | annual_review_due | expired
  created_at, signed_at, expires_at, annual_review_date

  support_stewards: [
    { ss_id, role: primary|alternate, authorized_incidents: [7 flags], permissions, ... }
  ]
  continuity_stewards: [
    { cs_id, role: primary|alternate|secondary, authorized_incidents: [7 flags], vault_access_level, ... }
  ]

  incident_config: {
    death:            { enabled, docs_required: [], ss_tasks: [{id,title,timeline}], cs_tasks: [...], authorized_ss, authorized_cs }
    short_term:       { ... }
    long_term:        { ... }
    missing_person:   { ... }   // opt-in
    detainment:       { ... }   // opt-in
    natural_disaster: { ... }   // opt-in
    geopolitical:     { ... }   // opt-in
  }

  attestation_states: {
    practitioner_signed: { timestamp, signature_record }
    ss_certified:        { [ss_id]: { timestamp, exception_flags: [] } }
    cs_certified:        { [cs_id]: { timestamp, exception_flags: [] } }
    annual_reattestations: [ { year, practitioner_at, ss_at: {}, cs_at: {} } ]
  }

  vault_manifest: { standard: [], emergency: [], credentials: [], roster: [] }
  signed_document_url
}

CriticalIncident {
  incident_id
  provider_id
  type: one of 7
  reporting_ss_id, trigger_timestamp, trigger_description, contact_attempts: []
  verifying_cs_id, verify_timestamp, uploaded_docs: []
  task_completions: [{task_id, cs_id, completed_at, notes}]
  vault_access_log: [{cs_id, resource, accessed_at}]
  closure: { submitted_by, summary, timestamp }
  status: active | monitoring | closed
}

ActivityEvent {
  event_id
  user_id, portal
  event_type: message|task|document|incident|vault|compliance|attestation|payment|account|system
  severity: info|warning|critical
  provider_id        // scoped events only
  module             // which page/module generated it
  action             // verb: created, signed, verified, completed, etc.
  title, description
  related_record_id  // FK to the relevant record
  timestamp
  read_status
}
```

---

## Appendix C — What Carizma Still Needs to See

To close the loop on her workflow email concerns, once Tier 1 and Tier 2 are built:

1. Demo the **Continuity Plan Builder** end-to-end with real data flow showing the 7-grid being filled in.
2. Demo an SS logging in and seeing their task list **actually populated** from the Builder output.
3. Demo a CS logging in and seeing their task list + responsibilities **actually populated** from the Builder output.
4. Demo the **certification chips** flipping on the Practitioner Dashboard when SS and CS certify.
5. Demo the **Critical Incident flow** end-to-end: SS triggers → CS receives alert → CS verifies → vault unlocks → tasks become live → CS executes → CS closes → Practitioner/estate notified.
6. Show the **unified Activity Log** on all three portals recording every step of the above.

This is the demo that puts to rest "how do I know the portals are talking?"
