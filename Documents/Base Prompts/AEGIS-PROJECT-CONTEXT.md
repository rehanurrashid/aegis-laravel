# Aegis — Comprehensive Project Context Document (v2)

> **For the next conversation.** This document is the canonical handoff for the Aegis project. It absorbs the full project history across MAAT/Aegis Claude chats: roles, workflows, data model, file architecture, design language, demo conventions, pricing, client decisions, and what's built vs pending. It is **substantial by design** — a new conversation reading this should be able to resume productive work without rebuilding context from scratch.
>
> Pair this with the four portal wiring blueprints (`PROVIDER-PORTAL-WIRING-BLUEPRINT.md`, `CS-PORTAL-WIRING-BLUEPRINT.md`, `SS-PORTAL-WIRING-BLUEPRINT.md`, `BP-PORTAL-WIRING-BLUEPRINT.md`) and the design system reference (`AEGIS-DESIGN-SYSTEM.md`) for full implementation depth.

---

## Table of Contents

1. The Project, In One Read
2. The Role Model (Detailed)
3. The Continuity-Plan Lifecycle (The Core Workflow)
4. The Five Portal Areas (Detailed)
5. Database Schema (16 Tables, Detailed)
6. Backend Architecture & Helper Layer
7. Demo & Query-Param System
8. Pricing, Tiers & Limits
9. Third-Party Tools & Integrations
10. Public Profile System
11. The Aegis Design System
12. Onboarding Flows
13. Communication Layer (Messages, Activity, Notifications)
14. Business Partner Marketplace (Dual-Mode)
15. Client Decision Log (Carizma's Confirmed & Pending)
16. Terminology Rebrand
17. File Naming & Conventions
18. What's Built / What's Pending
19. Working Conventions for New Conversations
20. Appendix — Quick-Reference Tables

---

## 1. The Project, In One Read

**MAAT Practice Firm** is a boutique practice management firm in Georgia, USA, founded by **Dr. Carizma Chapman, PhD, DMFT**. MAAT serves healthcare and social-service practitioners with continuity stewardship, transition coaching, and operational support — historically as **human services**. **Aegis** is the SaaS platform MAAT is launching to deliver those services at scale.

The product's purpose, in one sentence: **a backup plan for a healthcare practitioner so that when they are suddenly unavailable — death, incapacitation, missing, detained, natural disaster, geopolitical conflict — their patients, records, billing credentials, and practice do not fall into chaos.**

A pre-signed continuity plan defines what happens. Two pre-designated humans (a **Support Steward** + a **Continuity Steward**) execute it. Aegis is the system of record and the operational console for that plan.

- **Public-facing site:** `https://maatpracticefirm.com` (the human-services brand)
- **SaaS platform:** `https://aegis.devlet.tech` (Aegis lives here; "kalink" is the deployment subdomain — the legacy product name was KALINK and the URL persists; the user-facing brand is **Aegis**)
- **Local dev:** `http://localhost:8000`
- **Target launch:** **June 1, 2026**
- **Built by:** Devlet LLC (Rehan Ur Rashid, owner). Engineering, design, and integrations all done by Devlet.
- **Stack:** PHP 8 + SQLite (PDO, WAL mode) for the backend. Vanilla CSS via design tokens. Vanilla JS — no framework, no Composer, no migrations. Drop on any PHP host, hit `reset.php?token=…`, and the schema auto-creates and seeds.

The product has been through one significant rebrand (KALINK → Aegis), one steward-naming overhaul (Executor → Continuity Steward, DSR → Support Steward), and several pricing iterations. Decisions current as of late April 2026 are codified in this document.

---

## 2. The Role Model (Detailed)

There are **four user-facing roles** plus an **anonymous** state. Roles are stored in a `user_roles` join table — one human can hold multiple roles (Marcus Chen is both a Continuity Steward and a Business Partner). Which portal a user lands in is determined by the URL prefix, not by the user's primary role. Cross-role visibility is controlled by per-role public flags on the user (`practitioner_public`, `cs_public`, `business_partner_public`).

### 2.1 Practitioner (a.k.a. Provider)

Licensed clinician — the portal owner. Fills out the continuity plan, designates the stewards, uploads sensitive credentials to the vault, manages their public profile.

- **Primary verb:** *Designates, Configures, Attests*
- **When active:** Always
- **Source of truth for:** Their own continuity plan, the SS/CS identities and tasks, the documents required during a critical incident
- **What they MUST NOT do:** Trigger their own critical incident
- **Subscription:** Two tiers, see §8.1
- **Public profile:** Yes, default (`practitioner_public=1`), at `/provider/<slug>`
- **Lives in:** Provider Portal at `/provider-portal/`

### 2.2 Continuity Steward (CS)

The executor. Licensed colleague, attorney, or business-account CS firm. Verifies the alert, unlocks the vault, runs the pre-agreed task list to wind down or transfer the practice.

- **Primary verb:** *Verifies, Executes, Closes*
- **When active:** Dormant during standby; activates on a verified critical incident
- **Source of truth for:** Execution audit trail during incident
- **What they MUST NOT do:** Make plan decisions without practitioner; access vault pre-verification

Two account subtypes (`cs_account_type` column):

- **Business CS** — Independent paid account ($49/mo or $429/yr). Can serve 2–40 practitioners. Has a public profile (`cs_public=1`) at `/steward/<slug>`. Can proactively invite practitioners.
- **Invited CS** — Free account linked to one inviting practitioner. **No public profile.** Can only see the one practitioner who invited them. Sees an upgrade prompt if they try to invite or connect to additional practitioners.

A practitioner who is invited *by* a CS still pays — they choose Continuity Access ($39) or Continuity Practice ($79). They are not free accounts. (This was clarified in Carizma's Apr 7 9:37 PM email after an earlier model where invited providers got free accounts proved unsustainable financially.)

CS lives in the **Continuity Steward Portal** at `/continuity-steward-portal/`.

### 2.3 Support Steward (SS)

Eyes on the ground. Family member, office manager, trusted staff. Spots trouble (practitioner missed shifts, can't be reached, family confirms emergency). **Triggers** the alert that wakes up the CS.

- **Primary verb:** *Monitors, Triggers, Assists*
- **When active:** Always (monitoring)
- **Source of truth for:** Daily status of the practitioner
- **What they MUST NOT do:** Verify/activate CS duties; access the vault

Sub-roles: Primary SS, Alternate SS. Both have the same portal but different attestation responsibilities.

- **Subscription:** None — invitation-only. No public signup flow. Onboarding shows an invite-gate explaining this.
- **Public profile:** Never. SS accounts are deliberately not browsable.
- **Lives in:** Support Steward Portal at `/support-steward-portal/`

### 2.4 Business Partner (BP)

Independent marketplace role. Billing freelancers, legal services, IT, marketing, accounting — vendors that practitioners hire for non-continuity work. **Not part of the continuity flow.** Modeled on Upwork: practitioners post jobs, BPs send proposals, contracts are created with milestones, and payment is released milestone-by-milestone.

Two subtypes (`bp_type` column):

- **BP Agency** — Multi-person firm (e.g., Acme Practice Services). Has a Team Management module (assign team member per milestone, owner-reviews-then-submits flow).
- **BP Freelancer** — Solo practitioner (e.g., Jamal Washington, CPA). No Team module. Personal SSN/1099, hourly rate, availability calendar.

- **Subscription:** Yes (legacy was $69/mo or $759/yr; BP pricing has not been re-confirmed in the current pricing doc — verify before launch)
- **Public profile:** Yes, default (`business_partner_public=1`), at `/business/<slug>`
- **Lives in:** Business Partner Portal at `/biz-portal/`
- **Special:** BP is the one role that does **not** see the cross-portal switcher in the profile dropdown. BPs don't cross over into the continuity flow.

### 2.5 Anonymous

No cookie, no `?as=`. Sees only the public/anon view of any profile (locked sections, sign-in CTAs, no contact details).

### 2.6 Multi-role Identity (Marcus Chen Pattern)

A single `users` row can have multiple `user_roles` entries. Marcus Chen has both `continuity_steward` and `business_partner` roles. Same human, same identity, but two distinct public faces:

- `/steward/marcus-chen` resolves (he's a Business CS)
- `/business/marcus-chen` resolves (he's a BP)
- `/provider/marcus-chen` returns 404 (he has no practitioner role)

The URL prefix decides which face to render. The role flags decide whether each face is publicly resolvable.

The header dropdown's portal switcher shows links to whichever portals the user has access to. Marcus, currently in the CS portal, sees a switcher entry for the BP portal. BP-only users (Acme, Jamal) don't see a switcher — BP doesn't bridge to continuity portals.

### 2.7 The Three-Tier Visibility Rule

A common point of confusion. There are **three** viewer tiers across all public profiles, not a per-role matrix:

| Tier | Condition | Sees |
|---|---|---|
| Anonymous | No login | Public-only sections + sign-in CTAs |
| Logged-in (any role) | Any signed-in user | Full profile incl. contact, metrics, activity, connection info |
| Owner | Signed-in user is the profile's owner | Adds Edit Profile button + Profile Visibility panel |

**Once you're signed in, role doesn't matter for browsing public profiles.** A CS viewing a Provider profile and a BP viewing the same Provider profile see identical content. The role gating happens at the **slug-resolution** step (e.g., Invited CSs aren't publicly resolvable at all), not at the view-render step.

---

## 3. The Continuity-Plan Lifecycle (The Core Workflow)

This is the central workflow Aegis exists to enable. It has four phases — Setup, Standby, Activation, Closure — and several attestation loops that must all be satisfied before any phase advances.

### 3.1 Phase 1: Setup (Practitioner builds the plan)

The practitioner uses the **Continuity Plan Builder** — a 7-row × N-column grid where each row is a **critical-moment type** and each column is configuration:

The 7 approved critical-moment types (no freeform):

| Type | Notes |
|---|---|
| Death | Always applicable |
| Short-Term Incapacitation | Always applicable |
| Long-Term Incapacitation | Always applicable |
| Missing Person | **Opt-in** add-on |
| Detainment | **Opt-in** add-on |
| Natural Disaster | **Opt-in** add-on |
| Geopolitical or Conflict-Related Events | **Opt-in** add-on |

For each enabled critical-moment type, the practitioner specifies:

- **Authorized SS** — which Support Stewards are authorized to trigger this incident type
- **Authorized CS** — which Continuity Stewards are authorized to verify and execute
- **Documentation required** — what proof is required to verify (e.g. death certificate for Death; doctor's note for Long-Term Incapacitation; police wellness check for Missing Person)
- **CS task list** — pre-written tasks the CS will execute when this type is verified
- **SS task list** — pre-written tasks the SS will execute (reporting, family contact, etc.)

The builder is the practitioner's `continuity-plan.php` page. It hydrates from `continuity_plans` + `plan_incident_configs` + `plan_tasks` tables. When the practitioner saves it, the data flows downstream:

- The CS's `my-tasks.php` populates from `plan_tasks` filtered by `cs_id`
- The SS's `my-tasks.php` populates from `plan_tasks` filtered by `ss_id`
- The vault zones light up with documentation requirements per incident type

### 3.2 Phase 1 (continued): Stewards Designate & Sign

The practitioner adds Continuity Stewards via `continuity-stewards.php` (a 4-step wizard) and Support Stewards via `support-stewards.php` (a 3-step wizard with a permission matrix). For each steward, they specify:

- Primary or Alternate role (`role` column on `plan_stewards`)
- Per-incident-type authorization (only authorized to act on these incident types)
- Per-permission grants (vault access scope, document access scope)

After designation:

1. The **practitioner signs** the Continuity Plan (e-signature).
2. The plan goes to the **CS for countersignature**, surfaced in CS's `important-documents.php` with a "Review & Sign" CTA.
3. The CS countersigns. Plan is now **Active**.
4. SS receives a copy in their `important-documents.php` (read-only).
5. **Annual review attestation** — every year, the practitioner re-attests that information is current. SS and CS may also be asked to re-certify.

**Attestation loop** — three independent attestations must be tracked:

- **Practitioner attests** — confirms info is accurate; surfaces on SS + CS dashboards
- **CS certifies** — confirms tasks are accurate and complete (whole-list certification with optional per-task exception flag, per Carizma's decision)
- **SS certifies** — confirms tasks are accurate and complete

All three certifications appear as status chips on the practitioner's dashboard. Missing certifications block plan-active status.

### 3.3 Phase 2: Standby (the everyday state)

In standby:

- Practitioner uses the platform normally (referrals, services, network)
- SS monitors the practitioner's daily status
- CS waits — their portal shows "Standby" mode
- **Vault is sealed.** The Standard Documents zone is visible to CS at all times. Emergency Vault, Client Roster, and Secure Credentials zones are all sealed and only unlock on a verified incident
- Activity Log records every meaningful action across all three portals
- **`?emergency=false` query param** lets all portal pages render in this exact standby state for previewing without an active incident

### 3.4 Phase 3: Activation (the critical incident fires)

When a critical incident occurs, the flow is:

**Step 1 — SS triggers.** The SS opens `critical-incident-log.php`, selects the incident type from the approved 7 (only types they're authorized for, per the practitioner's plan), writes a narrative report, attaches any contact-attempt log (timestamps + methods + outcomes). Submits.

The system creates a `critical_incidents` row with `status='reported'`, `verified_at=null`, `reported_by=ss_id`, full payload stored.

**Step 2 — CS receives alert.** The CS gets immediate notification (email + in-portal alert + activity event). The CS dashboard shows the **Active Critical Incident banner** dominating the top. CS clicks "View Emergency Details" → modal shows full SS report + legal documentation checklist + provider contact details + the CS's granted permissions for this incident type.

**Vault is still sealed.** CS cannot access protected zones until they verify.

**Step 3 — CS verifies.** CS clicks "Confirm Emergency & Begin Protocol". A legal acknowledgement modal presents the **approved Notice text** ("Only continue if an approved critical incident has occurred…") and a **False Reporting Warning** above the activation button (text: *"Submitting a false or unsupported emergency activation is a serious violation of your Professional Will and Aegis platform terms. This may result in immediate suspension, removal from the platform, and referral for legal action. All activations are permanently reviewed and audited."*).

CS uploads supporting documentation if required by the practitioner's plan for this incident type (e.g. death certificate). Then checks: *"I confirm this emergency is genuine and I am activating the succession protocol under the terms of the designated provider's Professional Will."* Activates.

**Step 4 — System fires.** The system:

1. Timestamps `verified_at` and `verified_by` on `critical_incidents`
2. **Unlocks the vault** for the verifying CS (Emergency Vault + Client Roster + Secure Credentials zones become readable)
3. **Auto-generates `incident_tasks`** by cloning rows from `plan_tasks` where the type matches and the CS is authorized
4. Notifies SS, practitioner emergency contacts, alternate stewards
5. Writes activity events to all three portals' activity logs
6. Sets the `?emergency=true` state across portals

**Step 5 — CS executes.** The CS works through the auto-generated incident tasks: contact patients, transfer records, close billing relationships, notify the licensing board, etc. Each task is checked off; each check-off writes an `activity_events` row.

The vault is read-only for the CS — they can view documents, copy credentials (auto-clearing clipboard), and reveal masked passwords (auto-hiding after 8 seconds). Every view, download, and reveal is logged.

### 3.5 Phase 4: Closure

CS submits a **Final Completion Report**. This:

- Sets `critical_incidents.status='closed'` and `closed_at`
- Writes a closure summary to the SS's critical incident log
- Notifies the practitioner's estate / next-of-kin contact
- Re-seals the vault
- Generates an audit-ready PDF of the entire incident timeline

### 3.6 Cross-Portal Communication Pattern

The three continuity portals communicate exclusively through three shared records:

- **`continuity_plans`** — the practitioner authors; SS + CS read
- **`critical_incidents`** — SS triggers; CS verifies and updates; practitioner's estate notified
- **`activity_events`** — every meaningful action across every module on every portal writes to this single feed

The header bell on every portal reads from `activity_events` filtered to the current user. The `activity.php` page on every portal is the unified event stream.

Messaging is direct (not portal-mediated). Each thread is mirrored to both parties' inboxes. Continuity Contacts are pinned at the top of every inbox: practitioner + primary SS + alternate SS + primary CS + alternate CS + Aegis Support.

---

## 4. The Five Portal Areas (Detailed)

### 4.1 Provider Portal — `/provider-portal/`

The owner of the continuity plan. Where all data originates.

**Sidebar structure (Practice tier):**

```
Main
  Overview — Start Here
  Dashboard
  My Profile

My Practice                    (Practice tier only)
  Job Postings
  Referrals
  Integrative Network
  My Services                  (only when services_mode=1)

Continuity
  Continuity Plan              (the Builder)
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

**Access tier locks:**

- `referrals.php` and `services.php` — visible but locked, click → upgrade modal
- `job-postings.php` and (deprecated) `activity.php` — fully hidden
- The sidebar shows a "Continuity Access" badge with an Upgrade link

**Key pages:**

- **`overview.php`** — Onboarding/reference. Key Terms, Why Aegis, How to Use, FAQ.
- **`dashboard.php`** — Welcome hero with greeting; Continuity Plan readiness ring (signed/CS-certified/SS-certified status chips); license/insurance compliance countdowns; CEU tracker; action-required panel; Integrative Network carousel; activate-succession modal; annual-review modal.
- **`profile.php` / `edit-profile.php`** — Public profile editor with sectional left nav.
- **`continuity-plan.php`** — The 7-row × config-grid Builder. The single most important page in Aegis.
- **`continuity-stewards.php`** (renamed from `executors.php`) — 4-step wizard to designate CSes. Per-incident-type authorization matrix.
- **`support-stewards.php`** (renamed from `dsr.php`) — 3-step wizard to designate SSes. Granular permission matrix.
- **`important-documents.php`** (renamed from `agreements.php`) — Aegis Document Library + Continuity Plan card + countersigned plans tab.
- **`vault.php`** (renamed from `documents.php`) — 4-zone vault: Standard, Emergency, Client Roster, Secure Credentials.
- **`finances.php`** — Subscription + invoices + payment methods. **Escrow language removed per attorney direction.** All payments flow via Stripe Connect direct (practitioner → CS bank), MA'AT never holds funds.
- **`network.php`** — Integrative Network (formerly "Clinical Network"). Provider search + shadow network.
- **`referrals.php`** — Send referrals. Locked on Access tier.
- **`services.php`** — Integrative Business Services Mode. Locked on Access tier. Only visible when `services_mode=1`.
- **`job-postings.php`** — Post jobs to the BP marketplace. Hidden on Access tier.
- **`messages.php`** / **`activity.php`** / **`settings.php`** — Standard.

### 4.2 Continuity Steward Portal — `/continuity-steward-portal/`

The executor. Dormant during standby; central during/after a critical incident.

**Sidebar structure:**

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
  Continuity Management        (the verify cockpit)
  Document Vault

Communication
  Messages
  Activity Log

Account
  Settings
```

**Key pages:**

> **Path-aware rendering.** Every page below detects `$is_invited_cs` (via the 4-signal composite: `?invited=true` URL flag → `cs_path === 'invited'` → `cs_account_type === 'invited'` → non-empty `linked_provider_id`) and gates multi-practitioner UI accordingly. Canonical detection lives in `providers.php` line 25 and `settings.php` line 41 — copy that pattern, never re-invent. Demo verification: `?as=cs_marcus` (Business CS view) vs `?as=cs_alternate&invited=true` (Invited CS view).

- **`dashboard.php`** — Good Morning greeting → Critical Incident banner (when active) → Provider Overview tiles (Missing Plans / Re-attestations / Pending / Supported counts) → Shadow Network panel → Quick Actions. Multi-practitioner widgets and "find new providers" CTAs hidden for Invited CS; thin invited-notice banner near top.
- **`my-tasks.php`** (renamed from `assignments.php`) — Per-provider, per-incident-type task grouping. During an active incident, that incident's tasks pinned at top; standby tasks collapsed below. Whole-list certification checkbox + optional per-task exception flag. Practitioner filter dropdown hidden for Invited CS; `$filter_provider` locked to linked provider.
- **`providers.php`** — CS's caseload. Search, filter, refer-from-roster modal, invite-provider button. For Invited CS: search/filter hidden, roster shows only the linked practitioner, invite button rendered DISABLED with upgrade tooltip (not removed — locked button is the recognizable surface that triggers the canonical upgrade modal).
- **`important-documents.php`** — Signed Plans + Addendum + Contract/MOU + BAA + Aegis Sample Forms Library + Countersignature UI for incoming plans. Practitioner filter dropdown hidden for Invited CS; `$filter_provider` locked.
- **`continuity-management.php`** (renamed from `emergency.php`) — The verify cockpit. Shows the SS report, runs the verification flow, holds the documentation upload, generates the task checklist, surfaces vault access. **Documentation-required enforcement** built in: if the plan flagged this incident type as requiring a death cert, the verify modal must require upload to proceed. Cross-practitioner aggregate widgets and practitioner filter dropdown hidden for Invited CS.
- **`vault.php`** — 3-tab read-only view of practitioner's vault (Support Documents / Client Roster / Secure Credentials). Unlocks only after CS verifies an incident. Every view/download/reveal logs to activity feed. Practitioner selector hidden for Invited CS; `$practitioner_id` forced to linked provider.
- **`finances.php`** — Stripe Connect status + active invoices ("Active Invoices" replacing "Escrow Accounts") + awaiting payment + payment history. **No escrow held by Aegis.** Funds flow direct from practitioner → CS via Stripe. **Invited CS sees an entirely different view** — single "covered by your linked practitioner — no setup needed" card; no Stripe block, no invoices, no caseload grid; finance helpers (`aegis_cs_stripe_status`, `aegis_cs_invoices_for_user`) guarded so they don't fire for Invited CS.
- **`edit-profile.php`** — Public profile editor. Skills & Focus section and Stripe info hidden for Invited CS (they have no public profile); subtitles on identity fields switch from "shown publicly" to "shown to your linked practitioner". Preview button rendered DISABLED for Invited CS.
- **`settings.php`** — Path-branched billing panel: Business CS sees plan card ($49/mo · payment method · invoice history · enterprise upgrade strip when caseload ≥ 35); Invited CS sees "covered by your linked provider — no cost" panel.
- **`profile.php`** — 5-tab structure: Basic / Credentials / Continuity Plan framework / Fee / Verification (Aegis Verified module: gov ID, credentials, Code of Conduct, background check via Checkr).

### 4.3 Support Steward Portal — `/support-steward-portal/`

The day-to-day monitor. Triggers the critical incident.

**Sidebar structure:**

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
  Continuity Stewards          (read-only — view CS designations)
  Critical Incident Log

Communication
  Messages
  Activity Log

Account
  Settings
```

**Key pages:**

- **`dashboard.php`** — Report Critical Incident CTA in hero. Provider cards, Upcoming Tasks widget, Recent Activity feed.
- **`my-tasks.php`** (renamed from `tasks.php`) — Per-provider task list pulled from practitioner's plan.
- **`providers.php`** — SS's caseload (typically 2–8 practitioners).
- **`critical-incident-log.php`** (renamed from `emergency.php`) — The trigger page. Incident type dropdown from the approved 7 (with ** opt-in markers; disabled if practitioner hasn't enabled them); narrative report; contact-attempt log; documentation upload. Submit triggers the incident.
- **`continuity-stewards.php`** — Read-only list of CS designations per provider. **"Notify Practitioner — CS Unresponsive"** button (writes alert event; doesn't change designation).
- **`important-documents.php`** — Read-only view of provider's signed plans. SS does not author plans.

The previous `alerts.php` was removed and merged entirely into `activity.php` (the unified event stream).

### 4.4 Business Partner Portal — `/biz-portal/`

The Upwork-style marketplace. Independent of continuity flow.

**Sidebar structure (Agency mode shown; Freelancer mode hides Team section):**

```
Main
  Overview — Start Here
  Dashboard
  My Profile

Work
  Find Jobs
  Contracts
  Proposals
  Milestones

Financial
  Finances
  Invoices
  Payment Setup

Communication
  Messages
  Notifications

Account
  Settings

Team                           (Agency only)
  Team Management
```

**Key pages:**

- **`dashboard.php`** — Active contracts, revenue snapshot (privacy toggle), open proposals, new job matches, recent activity. Agency: capacity panel, team utilization. Freelancer: availability panel.
- **`find-jobs.php`** — Searchable job board (practitioner-posted jobs). Filters: category, budget, engagement type, urgency. Save jobs. Send proposal directly.
- **`proposals.php`** — Submitted/under review/accepted/declined/saved-jobs tabs. Edit-before-viewed flow. Withdraw flow.
- **`contracts.php`** — Active and past contracts. Provider details, milestones, payment schedule, signed agreement link, message thread. Agency adds team-assignment column.
- **`milestones.php`** — Due-soon / pending-approval / completed / all tabs. Agency: assigned team member column. Submit work flow (Freelancer submits direct; Agency owner reviews team submission first or grants direct submit permission).
- **`finances.php`** — Revenue charts, earnings breakdown by period, payout history.
- **`invoices.php`** — Create/send/track invoices with paid/unpaid/overdue status.
- **`payment-setup.php`** — Bank accounts, Stripe Connect onboarding, tax info (1099 for Freelancer, EIN for Agency), security.
- **`team.php`** (Agency only) — Members with status (Active/Idle/Inactive), 4 permission roles (Admin/Manager/Specialist/Viewer), departments, broadcast messages, contract reassignment.
- **`messages.php`** — 3-column messaging app with provider conversations, file attachments, voice notes, reactions, 14 connected modals.

### 4.5 Public Profile Pages — `/public/`

Three role-agnostic, slug-routed pages:

- `/public/provider.php?slug=<slug>` — Practitioner public profile
- `/public/continuity_steward.php?slug=<slug>` — Continuity Steward public profile (Business CS only)
- `/public/business.php?slug=<slug>` — Business Partner public profile

Same `slug` value can resolve under multiple namespaces (Marcus has both `/steward/marcus-chen` and `/business/marcus-chen`). The `practitioner_public`, `cs_public`, `business_partner_public` flags + `cs_account_type` decide which faces are publicly resolvable per human.

The `public_chrome.php` dispatcher wraps the page in **the viewer's portal chrome** (sidebar + topbar) when logged in — so a CS browsing Sarah's profile still sees the CS portal sidebar, not a stripped-down public layout.

---

## 5. Database Schema (16 Tables, Detailed)

SQLite database at `data/aegis.sqlite`. Schema generated by `_shared/db.php` on first request via `aegis_db()`. Foreign keys enforced via `PRAGMA foreign_keys=ON`. WAL mode for concurrency.

### 5.1 Identity (1 table)

#### `users`
The single user table for all portals.

| Column | Type | Notes |
|---|---|---|
| `id` | TEXT PK | e.g. `p_sarah`, `cs_marcus`, `bp_acme` |
| `role` | TEXT | Legacy primary role (still queried in some places) |
| `display_name` | TEXT | |
| `credentials` | TEXT | "MD", "JD", "CPA" |
| `email`, `phone`, `location`, `organization` | TEXT | |
| `avatar_initials` | TEXT | "SJ" — for circular avatar |
| `title`, `specialty`, `bio` | TEXT | |
| `slug` | TEXT | Kebab-case, dedup-safe |
| `slug_locked_at` | TEXT | Timestamp when slug was claimed |
| `practitioner_public` | INTEGER | 0/1 — default 1 |
| `cs_public` | INTEGER | 0/1 — default 0; forces Business CS to opt in |
| `business_partner_public` | INTEGER | 0/1 — default 1 |
| **Practitioner-specific** | | |
| `tier` | TEXT | `access` ($39/mo) · `practice` ($79/mo) |
| `services_mode` | INTEGER | 0/1 — Integrative Services Mode toggle |
| `maat_addon` | INTEGER | 0/1 — MAAT Continuity Steward Service add-on |
| **CS-specific** | | |
| `cs_account_type` | TEXT | `invited` (free) · `business` ($49/mo) · `enterprise` |
| `stripe_connected` | INTEGER | 0/1 — Stripe Connect onboarding done |
| `verified` | INTEGER | 0/1 — Aegis Verified credential check passed |
| **SS-specific** | | |
| `invited_by_id` | TEXT FK→users | The practitioner who invited the SS |
| `about_me` | TEXT | Note from SS to CS |
| **BP-specific** | | |
| `bp_type` | TEXT | `agency` · `freelancer` |
| `bp_business_name` | TEXT | |
| `bp_team_size` | INTEGER | |
| `bp_hourly_rate` | REAL | |
| `bp_categories` | TEXT (JSON) | `["accounting","billing","compliance"]` |
| `created_at`, `last_login` | TEXT | |

### 5.2 Multi-Role (1 table)

#### `user_roles`
Join table for multi-role identities.

| Column | Notes |
|---|---|
| `user_id` FK→users | |
| `role` | `practitioner`, `continuity_steward`, `support_steward`, `business_partner`, `admin` |
| `is_primary` | 0/1 |
| `created_at` | |

### 5.3 Continuity Plan Core (4 tables)

#### `continuity_plans`
The legal document. One per practitioner.

| Column | Notes |
|---|---|
| `id` PK | |
| `practitioner_id` FK→users | |
| `status` | `draft`, `active`, `expired` |
| `signed_at`, `signature_name` | Practitioner signature |
| `annual_review_date` | Yearly re-attestation date |
| `created_at`, `updated_at` | |

#### `plan_stewards`
Many-to-many between plans and stewards.

| Column | Notes |
|---|---|
| `plan_id` FK→continuity_plans | |
| `steward_id` FK→users | |
| `steward_type` | `continuity_steward` · `support_steward` |
| `role` | `primary` · `alternate` · `secondary` |
| `status` | `pending`, `active`, `revoked` |
| `countersigned_at` | When CS countersigned the plan |
| `certification_at` | When this steward certified their tasks |

#### `plan_incident_configs`
Exactly 7 rows per plan, one per incident type.

| Column | Notes |
|---|---|
| `plan_id` FK | |
| `incident_type` | `death`, `incapacitation_short`, `incapacitation_long`, `missing_person`, `detainment`, `natural_disaster`, `geopolitical` |
| `enabled` | 0/1 (some are opt-in) |
| `is_optin` | 0/1 — flag for the 4 opt-in types |
| `authorized_ss_ids` | JSON array of user IDs |
| `authorized_cs_ids` | JSON array |
| `documents_required` | JSON array — `["death_certificate", "medical_poa"]` etc. |

#### `plan_tasks`
Pre-written task list per (plan, incident_type, role).

| Column | Notes |
|---|---|
| `id` PK | |
| `plan_id` FK | |
| `incident_type` | One of the 7 |
| `assignee_role` | `cs` or `ss` |
| `assignee_id` | Specific user, or null = "any authorized" |
| `task_title`, `task_description` | |
| `sort_order` | |

### 5.4 Critical Incident (2 tables)

#### `critical_incidents`
The central state object.

| Column | Notes |
|---|---|
| `id` PK | |
| `plan_id` FK | |
| `practitioner_id` FK→users | |
| `incident_type` | One of the 7 |
| `status` | `reported`, `verified`, `active`, `closed` |
| `reported_by_id` FK→users | The SS who triggered |
| `reported_at` | |
| `report_narrative` | The SS's written report |
| `contact_attempts` | JSON — array of `{time, method, outcome}` |
| `verified_by_id` FK→users | The CS who verified |
| `verified_at` | |
| `verification_docs` | JSON array of uploaded doc filenames |
| `verification_notes` | CS's verification statement |
| `closure_summary` | CS's final report on closure |
| `closed_at` | |

#### `incident_tasks`
Generated only when a CS verifies (clones from `plan_tasks` for that incident type).

| Column | Notes |
|---|---|
| `id` PK | |
| `incident_id` FK | |
| `source_plan_task_id` FK | |
| `assignee_id` FK→users | |
| `status` | `pending`, `in_progress`, `completed`, `blocked` |
| `completed_at` | |
| `completion_notes` | |

### 5.5 Vault (1 table)

#### `vault_items`
4 zones, zone-specific columns.

| Column | Notes |
|---|---|
| `id` PK | |
| `practitioner_id` FK | |
| `zone` | `standard`, `emergency`, `roster`, `credentials` |
| `title` | |
| `description` | |
| `file_path` / `file_url` | For document zones |
| `credential_data` | JSON for credentials zone (encrypted via Keeper in Phase 2) |
| `roster_entry_data` | JSON for roster zone (`{name, dob, contact, last_visit, …}`) |
| `created_at`, `updated_at` | |

### 5.6 Communication (3 tables)

#### `message_threads`
| Column | Notes |
|---|---|
| `id` PK | |
| `participants` | JSON array of user IDs |
| `subject` | |
| `is_continuity_thread` | 0/1 — flagged when SS or CS in thread |
| `incident_id` FK→critical_incidents | Nullable; tags messages to an incident for legal record |
| `created_at`, `last_message_at` | |

#### `messages`
Standard threaded messages with attachments.

#### `activity_events`
The unified event feed. Replaces the old separate Notifications + Alerts tables.

| Column | Notes |
|---|---|
| `id` PK | |
| `user_id` FK→users | The user this event is scoped to |
| `event_type` | `message`, `task`, `document`, `incident`, `vault`, `compliance`, `attestation`, `payment`, `account`, `system` |
| `severity` | `info`, `warning`, `critical` |
| `title`, `description` | |
| `provider_id` FK→users | Nullable; for events about a specific practitioner (CS sees this filter) |
| `read_at` | Nullable; null = unread |
| `metadata` | JSON for type-specific payload |
| `created_at` | |

The header bell on every portal reads from `activity_events`. The `activity.php` page is the same data with filters.

### 5.7 Business Partner Marketplace (5 tables)

#### `bp_jobs`
Practitioner-posted jobs (e.g., "Need medical billing specialist — $1,800/mo").

| Column | Notes |
|---|---|
| `id` PK | |
| `practitioner_id` FK | |
| `title`, `description` | |
| `category` | `accounting`, `billing`, `compliance`, `technology`, `legal`, `marketing` |
| `budget_type` | `fixed`, `hourly`, `monthly_retainer` |
| `budget_amount` | |
| `status` | `open`, `in_review`, `filled`, `closed` |
| `posted_at`, `filled_at` | |

#### `bp_proposals`
BP-submitted proposals to jobs.

| Column | Notes |
|---|---|
| `id` PK | |
| `job_id` FK | |
| `bp_id` FK→users | |
| `cover_letter` | |
| `proposed_rate` | |
| `proposed_timeline` | |
| `attached_files` | JSON |
| `status` | `submitted`, `viewed`, `accepted`, `declined`, `withdrawn` |
| `agency_assigned_member_id` | Nullable; for agency proposals |

#### `bp_contracts`
Created when proposal is accepted.

| Column | Notes |
|---|---|
| `id` PK | |
| `proposal_id` FK | |
| `practitioner_id`, `bp_id` FK | |
| `scope`, `value`, `engagement_type` | |
| `signed_at_practitioner`, `signed_at_bp` | |
| `status` | `pending_signatures`, `active`, `paused`, `completed`, `cancelled` |

#### `bp_milestones`
Per-contract milestones.

| Column | Notes |
|---|---|
| `id` PK | |
| `contract_id` FK | |
| `title`, `due_date`, `value` | |
| `assigned_member_id` | Nullable (agency-only) |
| `status` | `upcoming`, `in_progress`, `submitted`, `approved`, `paid` |
| `submitted_at`, `approved_at`, `paid_at` | |

#### `bp_invoices`
Per-milestone or ad-hoc invoices.

| Column | Notes |
|---|---|
| `id` PK | |
| `contract_id` FK | |
| `milestone_id` FK | Nullable |
| `bp_id`, `practitioner_id` FK | |
| `amount`, `description` | |
| `status` | `draft`, `sent`, `paid`, `overdue`, `disputed` |
| `sent_at`, `paid_at` | |
| `stripe_payment_id` | When paid via Stripe |

---

## 6. Backend Architecture & Helper Layer

### 6.1 Directory layout

```
/  (project root)
  _shared.css           ~110KB design system
  _shared.js            ~37KB shared JS (modals, toasts, persistence, tier/services state)
  demo.php              Demo launcher (host-aware base URLs)
  reset.php             Demo data reset (token-protected)
  index.php             Landing redirect
  aegis-favicon.svg     Gold "A" mark

  _shared/
    ── Data layer ──
    db.php                  PDO + SQLite, current-user resolution, ?as= override
    models.php              Read helpers (50+ canonical fetchers across all portals)
    models_write.php        Write helpers — origination layer (continuity plan, stewards,
                            vault, documents, finances, network, referrals, services,
                            jobs, messages, events, certifications, activity)
    seed.php                Reads seed.json, wipes + repopulates DB
    icons.php               Canonical icon library (24×24 lucide outline, 1.75 stroke)

    ── Chrome ──
    header.php              Shared topbar (used by all portals)
    sidebar.php             Shared sidebar (role-aware nav-item registry)
    page_head.php           Emits <head> + opens <body>
    page_foot.php           Emits toast container, JS link, closes </body></html>
    page.php                Page-shell helper
    layout.php              Legacy page chrome wrapper (kept for back-compat)
    theme_loader.php        Server-side theme persistence (body class + sendBeacon)

    ── Partials ──
    bell.php                Header notification dropdown (reads from activity_events)
    activity_body.php       Reusable activity log page body
    profile_strip.php       Profile completion strip (used on dashboard, edit-profile)
    demo_switcher.php       Floating user-switcher widget
    public_chrome.php       Public-page chrome dispatcher (viewer-aware)
    public_profile.php      Public profile renderer shared by 4 role pages

    ── Shared modals (cross-portal triggerable) ──
    modals/
      upgrade_cs_modal.php  Two-step Invited-CS → Business-CS upgrade flow.
                            Plan summary (step 1) → card payment (step 2).
                            Trigger: openModal('upgradeModal') from any page
                            that has included it. Disabled "invite practitioner"
                            buttons across the CS portal all point here.
                            Currently has hardcoded plan copy — TODO: cut over
                            to /_shared/pricing_data.php aegis_pricing()['cs']['business'].
                            Write path /_shared/save_upgrade_cs.php is a Phase 2
                            stub — modal posts the form but the endpoint isn't
                            implemented yet.

    ── Write endpoints (AJAX) ──
    save_pref.php           User preference toggles
    save_profile.php        Profile edits (identity, contact, specialty)
    save_steward.php        Continuity/Support steward designation + management
    save_plan.php           Continuity plan draft, finalize, annual review, tasks
    save_vault.php          Vault items (4 zones) + AES-256-GCM credential envelope
    save_document.php       Important Documents agreements + library
    save_message.php        Messaging threads
    save_activity.php       Activity feed read-state
    save_certify.php        Steward certification (whole-list + per-task exception)
    save_event.php          Events register/cancel
    save_finance.php        Invoice approve/reject/dispute
    save_incident.php       Critical incident reporting + verification
    save_job.php            Job postings + proposals
    save_network.php        Network connections + shadow network
    save_referral.php       Referrals send/accept/decline/close
    save_service.php        Service listings + booking inquiries

  data/
    seed.json           Source of truth for demo data
    aegis.sqlite        Auto-regenerated SQLite DB (schema v13)

  public/
    provider.php             /public/provider.php?slug=<slug>
    continuity_steward.php   /public/continuity_steward.php?slug=<slug>
    support_steward.php      /public/support_steward.php?slug=<slug>
    business.php             /public/business.php?slug=<slug>

  provider-portal/             16 page files — write-path COMPLETE (Waves 1–7)
  continuity-steward-portal/   ~13 page files — read path scaffolded, write path pending
  support-steward-portal/      ~12 page files — read path scaffolded, write path pending
  biz-portal/                  ~14 page files — read path scaffolded, write path pending
  onboarding/                  Separate flow
    onboarding.html
    signin.html
    demo.html (mirror)
```

### 6.2 Helper functions (`_shared/models.php`)

The 11 canonical helpers — **all new code uses these, not hand-rolled queries.**

```
aegis_db()                                      — singleton PDO
aegis_current_user($default_id = null)          — reads ?as= or cookie, returns user row
aegis_user_roles($user_id)                      — array of role strings
aegis_user_has_role($user_id, $role)            — bool
aegis_user_default_role($user_id)               — primary role for navigation
aegis_generate_slug($display_name)              — kebab-case slug, dedup-safe
aegis_claim_slug($user_id, $slug)               — sets slug + slug_locked_at
aegis_resolve_slug($slug, $role)                — returns user record if publicly resolvable for role
aegis_practitioner_public_url($user)            — full URL to /provider/<slug>
aegis_cs_public_url($user)                      — full URL to /steward/<slug>, only if Business CS + cs_public=1
aegis_bp_public_url($user)                      — full URL to /business/<slug>
aegis_resolve_public_profile($slug, $role)      — central resolver used by all three public pages
aegis_filter_profile_for_viewer($profile, $viewer)  — applies the three-tier visibility rules
```

Plus continuity-specific helpers:

```
aegis_get_plan_by_practitioner($pid)            — fetches the plan
aegis_get_active_incident_for_practitioner($pid) — null or incident row
aegis_trigger_incident($pid, $type, $ss_id, $narrative, $contact_attempts)
aegis_verify_incident($incident_id, $cs_id, $docs, $notes)
aegis_get_incidents_for_cs($cs_id)
aegis_get_incident_tasks($incident_id)
aegis_is_vault_unlocked_for_cs($cs_id, $pid)
aegis_log_activity($user_id, $event_type, $severity, $title, $desc, $provider_id = null, $metadata = [])
aegis_time_ago($timestamp)                      — formats "2 hours ago"
```

### 6.3 The `?as=` Demo Override

Located in `_shared/db.php`. `aegis_current_user_id()` checks in order:

1. `?as=<user_id>` in URL → uses that, sets a 30-day cookie
2. `aegis_uid` cookie → uses that
3. Returns null (anonymous)

**Demo-host gate:**

```php
$host    = $_SERVER['HTTP_HOST']   ?? '';
$server  = $_SERVER['SERVER_NAME'] ?? '';
$is_demo = in_array($server, ['localhost', '127.0.0.1'], true)
        || str_starts_with($host, 'localhost')
        || str_contains($host, 'devlet.tech');
if ($is_demo && isset($_GET['as'])) { ... }
```

On any other host, `?as=` is silently ignored and the cookie alone determines identity. When real auth ships, the `?as=` branch is removed.

### 6.4 Seven Demo Identities (current seed)

| `as=` | Person | Role(s) | Notes |
|---|---|---|---|
| `p_sarah` | Dr. Sarah Johnson, MD | Practitioner | Practice tier, services_mode=1; secondary CS role exists but `cs_account_type ≠ business` so not publicly resolvable as CS |
| `cs_marcus` | Marcus Chen | CS Business + BP | Multi-role; owns `/steward/marcus-chen` AND `/business/marcus-chen` |
| `cs_alternate` | Dr. Priya Raman | CS Invited | Free, linked to one practitioner, no public profile |
| `ss_linda` | Linda Johnson | SS Primary | No public profile |
| `ss_james` | James Rodriguez | SS Alternate | No public profile |
| `bp_acme` | Acme Practice Services | BP Agency | Owner of `/business/acme-practice-services` |
| `bp_jamal` | Jamal Washington, CPA | BP Freelancer | Owner of `/business/jamal-washington` |

### 6.5 The Write-Path Layer

The Provider Portal write path is the origination layer the other three portals read from. Built across Waves 1–7 (complete), with schema v13 and AES-256-GCM credential encryption.

**The pattern — five parts:**

1. **Page-local fetch wrapper** (e.g. `csPost()` in `continuity-stewards.php`, `vPost()` in `vault.php`). Posts JSON to a single endpoint, never redefines global helpers.

2. **AJAX endpoint** at `/_shared/save_<domain>.php`. Each endpoint:
   - Sets `AEGIS_ENTRY`, requires `models.php`, sets `Content-Type: application/json`
   - Auth-gates: rejects anonymous, rejects wrong role (e.g. `save_steward.php` requires practitioner)
   - Rejects non-POST
   - Switches on `action` parameter, calls the matching `models_write.php` helper
   - Returns `{ ok: true, ... }` or `{ ok: false, error: "..." }`

3. **Write helper** in `models_write.php`. Conventions:
   - Functions only, no top-level execution
   - Authorization assumed proved by caller; helpers do data work
   - Each helper that has cross-portal impact calls `aegis_log_activity()` before returning
   - Returns primitive (id / bool / count)

4. **Activity fan-out** via `aegis_log_activity($user_id, $portal, $event_type, $severity, $module, $action, $title, $description, $entity_id, $entity_type, $scoped_provider_id)`. The `$user_id` is the recipient — so when a practitioner shares a vault item with a CS, the CS's feed gets the entry, and the practitioner gets a separate confirmation entry. This is how every cross-portal "X happened on your behalf" notification works.

5. **UI response** — the page-local wrapper resolves the promise, fires `showToast()` with a human sentence, and reloads (or DOM-updates) on success.

**Endpoint inventory** (16 endpoints, listed in §6.1 directory layout). All follow the same contract; see `save_pref.php` as the canonical template.

**AES-256-GCM credential envelope** — credentials stored in the Secure Credentials vault zone are encrypted at rest with an AES-256-GCM envelope (IV + ciphertext + auth tag + version byte). Encryption key lives outside the SQLite DB. `aegis_vault_decrypt_credential()` is the only path to plaintext; gated by verified-incident state for CS access.

### 6.6 Theme System

Theme persistence requires three coordinated layers — found this out the hard way (single-layer approaches all failed):

1. **Server-side render** — `_shared/theme_loader.php` reads the saved theme from the user's `prefs` row and sets `$aegis_active_theme` before any page renders. Pages emit `<body class="theme-<name>">` server-side so there is no flash-of-default-theme on load. Adding the class to `document.documentElement` from JS instead of `body` was a confirmed root cause of theme persistence failure.

2. **Instant client feedback** — clicking a theme swatch updates `localStorage` immediately and swaps the body class. The user sees the change with no round-trip wait.

3. **Guaranteed save via beacon** — `navigator.sendBeacon('/_shared/save_pref.php', ...)` fires the persist request. Beacons survive page navigation and tab close, which `fetch()` does not.

`localStorage` is the client-side fallback if the server-side class is missing (first paint before the server prefs row exists); the server class wins on next page load.

### 6.7 Cross-Portal Template Data Helpers

`aegis_overview_data($user)` and `aegis_profile_data($user)` return per-role data bundles that feed the shared Overview and Profile templates. The shape is the same across roles; only the field list differs.

**`aegis_overview_data($user)` returns:**

```
hero_eyebrow, hero_title, hero_sub
notice_text       (top "remember" callout — empty string to hide)
terms             [ ['term','def'], … ]
why               [ ['title','desc','icon'], … ]
how_steps         [ ['title','desc'], … ]
faqs              [ ['q','a'], … ]
```

The helper switches on `$user['role']` and returns the right bundle for Practitioner / Continuity Steward / Support Steward / Business Partner. CS/SS/BP overview pages, when their portals are built, will load the bundle and render the canonical template — no role-specific markup duplication.

**`aegis_profile_data($user)` returns** the same-shape bundle for the read-only profile template (identity, completion, stats, sections). Each section is `['key','title','icon','rows'=>[ ['label','value','chip?','multiline?'], … ]]`. Used by `public_profile.php` and the in-portal Profile views.

This is the pattern CS/SS/BP build will reuse — define new role-specific data bundles in the helper, the shared template renders them identically. Tone-pass edits to bundle text propagate to every consumer automatically (see Provider tone pass, June 6).

---

## 7. Demo & Query-Param System

The demo flow is built on URL-param signaling that survives navigation. A client clicks a single link, lands in the right state, and browses freely without re-passing flags.

### 7.1 The Five Persistent Params

Implemented in `_shared.js` via a click-interceptor + `history.pushState` patch + `navigateTo()` helper. If any of these are in the initial URL, the JS layer auto-appends them to every subsequent same-origin navigation:

| Param | Values | Effect |
|---|---|---|
| `as` | seeded user ID (or empty to logout) | Server-side viewer override (gated to demo hosts) |
| `tier` | `practice` \| `access` | Provider plan tier |
| `services` | `0` \| `1` | Integrative Services Mode (Provider only) |
| `emergency` | `true` \| `false` | Active Critical Incident banner across portals |
| `invited` | `true` \| `false` | Onboarding: invited-CS signup path. CS portal: demo-override flag that forces every CS page into the Invited CS view (locked single-practitioner, no Stripe block, locked invite buttons). Canonical demo URL: `?as=cs_alternate&invited=true`. |

Persistence also goes through cookie + sessionStorage fallback (`aegis_tier`, `aegis_services`, `aegis_uid`).

### 7.2 The Demo Launcher (`demo.php`)

Single landing page at the project root. Sections: identities, provider tier×services matrix, public-profile visibility tiers, critical-incident state, onboarding flows. Host-aware: emits `http://localhost:8000/...` URLs locally and `https://aegis.devlet.tech/...` on devlet. All button links use canonical filenames (`dashboard.php`, never legacy `index.php`).

### 7.3 The Corner Switcher (`_shared/demo_switcher.php`)

Floating badge in the bottom corner of every authenticated portal page. Shows current demo user. Dropdown to switch to any other seeded user. Persists across navigation via cookie.

### 7.4 The Reset Flow

Visit `reset.php?token=aegis-demo-reset` to:

1. Wipe all tables in dependency order
2. Re-seed from `data/seed.json`
3. Optionally arm an emergency: `&arm_emergency=1` creates an unverified incident on Sarah's plan so the alert banners light up

---

## 8. Pricing, Tiers & Limits

> **Source of truth for code:** `/_shared/pricing_data.php` — `aegis_pricing()`. Both `/pricing.php` (public marketing) and `/onboarding.php` (signup flow) read from this single partial. Provider Portal `settings.php` still hardcodes the old numbers; cutting it over to `aegis_pricing()` is a pending centralization step (PENDING-ITEMS Section D).

### 8.1 Practitioner Subscriptions (confirmed June 2026)

Two base tiers, monthly or annual. **Annual saves a flat 20%.** Stewards (CS + SS) designated by a practitioner access their own portals at no additional cost — covered by the practitioner's subscription.

| Tier | Monthly | Annual (per month) | Annual billed | What's unlocked |
|---|---|---|---|---|
| **Continuity Access** | $29/mo | $23/mo | $276/yr | Essential continuity for solo practitioners. Continuity Plan Builder, all 7 incident types, full Vault (4 zones), 1 CS + 1 SS, Shadow Network, directory listing. **Locked features:** Referrals, My Services (upgrade prompts on click). **Hidden:** Job Postings. |
| **Continuity Practice** | $49/mo | $39/mo | $468/yr | Full toolkit. Up to 2 CS + 2 SS (Primary + Alternate each), referral sending/receiving, Integrative Network full search, Business Partner directory, Job Postings, message templates + pinning, activity log export, priority support, dedicated onboarding call. **Includes Integrative Services Mode** (no longer a separate add-on — see §8.4). |

CSes do **not** see the practitioner's tier — provider profile looks identical to a CS regardless of which plan the provider is on.

(Historical: earlier numbers were Access $39/mo · $429/yr and Practice $79/mo · $790/yr — see §15 client decision log.)

### 8.2 Continuity Steward Subscription

Three tiers — one paid, one custom, one free:

| Tier | Price | Scope | Public profile |
|---|---|---|---|
| **Business CS** | $49/mo · $429/yr | 2–40 practitioners | Yes (at `/steward/<slug>`) |
| **Enterprise CS** | Custom quote | 41+ practitioners | Yes |
| **Invited CS** | Free | One inviting practitioner | No |

Invited CS sees an upgrade prompt to Business CS if they try to invite or connect to additional practitioners. The canonical upgrade surface is `/_shared/modals/upgrade_cs_modal.php` — a two-step modal (plan summary → card payment) triggered via `openModal('upgradeModal')`. Every locked CTA across the CS portal (disabled invite buttons in `providers.php`, locked Referrals/Services links, upgrade links in `settings.php` and `finances.php`) routes to this same modal. Enterprise CS is currently a `mailto:` contact route from the pricing page — no automated provisioning yet.

### 8.3 Support Steward

**Always free** — invitation-only. No public signup, no public profile. Access is covered by the inviting practitioner's subscription. One Support Steward account per inviting practice (a Support Steward serving two practitioners receives two separate invitations, two separate accounts).

### 8.4 Practitioner Add-ons

**Only one standalone add-on: MAAT.** (Integrative Services Mode — previously listed as a +$19/mo add-on — has been folded into Continuity Practice as a tier feature. On Access, the sidebar locks Referrals + My Services and shows the upgrade-to-Practice modal via `AegisTier.onLockedClick()` in `_shared.js`.)

#### MAAT Professional Continuity Steward Service

| | Monthly | Annual (per month) | Annual billed |
|---|---|---|---|
| **+$29/mo** | $29 | $23 | $276/yr |

Designates a MAAT-certified, licensed, insured professional Continuity Steward to the practitioner's practice. Includes emergency response within 4 hours of incident trigger, annual CS recertification, practice succession planning support, and legal defensibility for clients and estate.

**Requires:** Continuity Practice base plan specifically (not available with Access).

In code: enabled via `maat_addon=1` on the user record; surfaces as a status indicator on the practitioner's dashboard.

(Naming history: originally "Premium Executor Service by MAAT". After the Executor → CS rebrand, current canonical name is "MAAT Professional Continuity Steward Service".)

#### Combo pricing

The most-recommended combination is **Continuity Practice + MAAT**:

| | Monthly | Annual (per month) |
|---|---|---|
| Practice + MAAT | $78/mo | $62/mo |

Note: legacy "Practice + Services Mode" and "Practice + Services Mode + MAAT" combos in earlier docs are no longer applicable — Services Mode is included with Practice at no additional charge.

### 8.5 Business Partner Subscription (confirmed via onboarding sign-off)

| | Monthly | Annual | Annual billed |
|---|---|---|---|
| **Business Partner** | $69/mo | $58/mo equivalent | $690/yr (save 2 months ≈ 17%) |

Single tier covering both account subtypes — **Agency** (multi-person firm with Team Management module) and **Freelancer** (solo). The Agency/Freelancer distinction is set via `bp_type` column at signup; pricing is identical. Public profile included at `/business/<slug>`.

BP saves on annual billing follow the older "save 2 months" model (≈17%), not the practitioner 20% model. The shared billing toggle on `/pricing.php` honors this — global label says "Save 20%" but each card's per-card billed-annually note shows the true savings copy ("save 2 months" for BP, "save 20%" elsewhere).

(Historical: legacy BP pricing was $69/mo · $759/yr. The current $690/yr came from onboarding sign-off — see PENDING-ITEMS if a written client confirmation is still outstanding.)

### 8.6 Founding Member Perks *(awaiting scoping confirmation)*

Carizma's Apr 7 notes:
- **First 100 practitioners (per tier):**
  - Continuity Access: 2 additional CS free for life + 1 marketing ad/yr free
  - Continuity Practice: 2 additional CS free for life + 2 marketing ads/yr free
- **First 25 CSes:** 50% off Continuity Steward Training

UI is wireframed in the onboarding Step 9 banner; the `/pricing.php` page also displays the practitioner-side banner. Full implementation (enforcement, gating, expiration) is awaiting scoping confirmation from Carizma.

### 8.7 Page-Level Access Rules (Provider Portal)

```php
$access_allowed = [
  'overview', 'dashboard', 'profile',
  'network',
  'continuity-stewards', 'support-stewards',
  'important-documents', 'vault', 'finances',
  'messages', 'activity',
  'news', 'events',
  'settings',
];
$access_locked  = ['referrals', 'services'];      // shown locked, click → upgrade
$access_hidden  = ['job-postings'];               // hidden entirely
```

Practice tier unlocks all of these. The `locked` flag in `_shared/sidebar.php` drives both the grayed visual treatment and the click interceptor that calls `AegisTier.onLockedClick()` in `_shared.js` to show the canonical upgrade modal.

### 8.8 Pricing-data architecture

| Surface | Reads from | Source code |
|---|---|---|
| `/pricing.php` (marketing) | `aegis_pricing()` | `/_shared/pricing_data.php` |
| `/onboarding.php` Step 9 | `aegis_pricing()` (Access, Practice, MAAT prices echoed into HTML + JS `prices` object) | `/_shared/pricing_data.php` |
| Provider `settings.php` subscription panel | Still hardcoded — pending cutover | (cut over to `aegis_pricing()` in next pass) |

The `aegis_pricing()` helper returns: `practitioner.access`, `practitioner.practice`, `addon` (MAAT), `cs.business`, `cs.invited`, `cs_enterprise`, `ss`, `bp`, `founding`. Each plan record exposes `price_monthly`, `price_annual`, `billed_annual`, `save_label`, `cta_label`, `cta_href`, `features`, and `limits`. To update a price: edit the array in `/_shared/pricing_data.php` — both the marketing page and the signup flow reflect the change on next load.
---

## 9. Third-Party Tools & Integrations

Aegis depends on a small set of external services for infrastructure, payments, communication, security, and analytics. Each row below lists what the service does, current status, BAA status, and who owns the account. Detailed sub-sections follow.

### 9.1 At-a-glance

| Service | Purpose | Status | BAA | Account owner |
|---|---|---|---|---|
| **AWS** (EC2, S3, RDS path TBD) | Production hosting, file binary storage | 🔴 Blocked on client account creation | Required (standard AWS BAA) | MAAT (Dr. Chapman) |
| **Stripe** + **Stripe Connect Express** | Practitioner subscriptions, CS payouts, BP invoices — no Aegis escrow | 🔴 Blocked on client account creation | Required (Stripe BAA) | MAAT (Dr. Chapman) |
| **Amazon SES** (recommended) or **SendGrid** | Transactional email: invitations, password resets, incident alerts, digests | 🔴 Blocked on client ESP selection + account creation | Required (both providers offer BAA) | MAAT (Dr. Chapman) |
| **Keeper Security** | Zero-knowledge credential vault — Option C full API integration | 🟡 Built front-end, backend pending (4–6 wk Phase 2) | Not required (zero-knowledge architecture) | Aegis platform-level Keeper Business account |
| **Google Analytics + GTM** | Product analytics with PHI-exclusion config | 🟡 Awaiting final client go-ahead on config | Not applicable (no PHI sent) | MAAT |
| **Help desk** (Freshdesk / Zendesk / custom inbox) | Practitioner support ticketing | 🟡 Vendor decision pending | Required if Freshdesk/Zendesk | MAAT |
| **Checkr** | Aegis Verified background-check workflow | 🟢 UI hooked, integration scoped | Required (Checkr BAA) | MAAT |
| **In-portal e-signature** (DocuSign-style hybrid) | Continuity plan signing, agreement countersigning | 🟢 Built in-house — not a third-party dependency | N/A | Aegis-native |

**Legend:** 🟢 live / built · 🟡 partial / pending decision · 🔴 blocked on client action

See `PENDING-ITEMS.md` Section A for the canonical list of client-action items that gate this work.

### 9.2 AWS (hosting & file storage)

- Production target: AWS for hosting + S3 + signed URLs for file binary storage
- Currently ~28 file-binary stubs across vault downloads, document PDFs, invoice PDFs, CEU certificates, settings export — all wait on the S3 path being live
- Standard AWS BAA covers HIPAA-eligible services (S3, EC2, RDS, CloudFront, etc.)
- Blocked on Dr. Chapman creating the AWS root account and providing credentials to Devlet

### 9.3 Stripe + Stripe Connect

- **Subscriptions:** practitioner Access/Practice + add-ons billed through Stripe Billing
- **Stripe Connect Express:** CS payouts and BP invoice payouts route directly from practitioner → recipient. **Aegis never holds escrow** — confirmed with attorney
- Stripe Connect UI is built on CS `settings.php` (Connected / Restricted / Disconnected states); webhook handlers + actual money flow pending account creation
- Stripe BAA required for HIPAA — Stripe will sign on request
- Blocked on Dr. Chapman creating the Stripe account and beginning Connect onboarding

### 9.4 Email Service Provider (SES recommended; SendGrid alternative)

- All transactional email — onboarding invitations, password resets, incident alerts, digest emails — flows through the chosen ESP
- Devlet recommends **Amazon SES** since AWS is already in scope (single BAA, single vendor relationship); SendGrid remains viable if MAAT prefers
- HTML email template system designed to match Aegis brand
- Template delivery wires into existing `aegis_log_activity()` fan-out so cross-portal notifications fire emails alongside in-portal feed entries
- Blocked on ESP selection + account creation + domain verification + BAA execution

### 9.5 Keeper Security — Credential Vault (Option C)

After evaluating three options (Link-Out / Native Encrypted Store / Full Keeper API), Aegis is going with **Option C — Full Keeper API Integration**.

- Aegis stores credential **metadata only**; Keeper holds the encrypted secrets via zero-knowledge architecture
- CS gains access only on a **verified critical incident**, mapping directly to Keeper's native emergency-access model
- Aegis itself never holds decryption keys → compliance burden (HIPAA, SOC 2, FedRAMP authorization) sits with Keeper, not Aegis
- No Business Associate Agreement (BAA) required because of zero-knowledge architecture

**Scope:** Vault UI is fully built front-end (including the four zones described in §9.7 below). Backend integration is a 4–6 week Phase 2 effort requiring a Keeper Business account for the Aegis platform.

**Note:** The locally-stored Secure Credentials zone (currently used in the demo) is encrypted with AES-256-GCM at rest as a stopgap — see §6.5. Once the Keeper API integration ships, the local envelope becomes the fallback path; Keeper is canonical.

### 9.6 Google Analytics + GTM

- GA4 + Google Tag Manager planned with strict PHI-exclusion config (no client identifiers, no incident data, no vault content tracked)
- Page-view + funnel + retention metrics only
- Awaiting Dr. Chapman's final go-ahead on the PHI-exclusion configuration described in the June 1 email thread

### 9.7 Help Desk

- Vendor decision pending: **Freshdesk** vs **Zendesk** vs a custom inbox at `support@aegis.app`
- Help ticket submission form will embed in every portal once a tool is chosen
- Freshdesk and Zendesk both offer HIPAA-compliant tiers with BAA
- Decision unblocks the help-ticketing item in `PENDING-ITEMS.md` Section B2

### 9.8 Checkr — Aegis Verified background check

- Background check vendor for the Aegis Verified module (gov ID, credentials, Code of Conduct, background check)
- Powers the verified-badge surface on public profiles
- Checkr BAA covers the PHI-adjacent data flow

### 9.9 The Four Vault Zones (architecture reference)

The Vault UI surfaces four zones with different access rules:

| Zone | Visible to CS in standby? | Visible after verified incident? |
|---|---|---|
| **Standard Documents** | Yes (always) | Yes |
| **Emergency Vault** | No (sealed) | Yes (read-only) |
| **Client Roster** | No (sealed) | Yes (read-only) |
| **Secure Credentials** (Keeper / AES-256 local) | No (sealed) | Yes (revealed with auto-clear) |

Every view, download, and credential reveal logs to `activity_events` for both CS and practitioner/estate audit.

The Standard zone holds non-sensitive docs the CS needs always (provider's signed agreements, the plan itself, BAA if applicable). The other three are the sensitive payload that only unlocks on incident verification.

---

## 10. Public Profile System

### 10.1 URL Routes

- `/public/provider.php?slug=<slug>` — Practitioner public profile
- `/public/continuity_steward.php?slug=<slug>` — Continuity Steward public profile (Business CS only)
- `/public/support_steward.php?slug=<slug>` — Support Steward public profile (Provider-gated)
- `/public/business.php?slug=<slug>` — Business Partner public profile

Legacy pretty URLs (`/provider/<slug>`, `/steward/<slug>`, `/business/<slug>`) are deprecated — they break relative-link resolution. Always use the canonical query-string form.

### 10.2 Chrome Dispatcher — `public_chrome.php`

When a viewer visits a public profile URL, `_shared/public_chrome.php` resolves the chrome (sidebar + topbar) based on **who is signed in, not what they're viewing**:

- **Anonymous viewer** → minimal public chrome (logo + Sign In / Join CTAs)
- **Logged-in viewer** → portal sidebar + topbar matching their **default role**

This is the "viewer's chrome, not URL's chrome" rule. A practitioner browsing a CS public profile still sees the Practitioner portal chrome — they're a visitor on someone else's profile, not switching contexts. The dispatcher's `aegis_resolve_chrome_role()` always uses `aegis_user_default_role()` (the signed-in identity), never the URL's role.

(Historical: an earlier "multi-role rule" picked chrome based on URL prefix; this was a confirmed bug. The fix simplified routing and removed several edge-case failures around multi-role users like Marcus Chen who holds both CS and BP roles.)

The dispatcher returns one of `'public' | 'practitioner' | 'continuity_steward' | 'business_partner' | 'support_steward'` so the closer (`aegis_chrome_close`) can emit the matching footer.

### 10.3 Three Visibility Variants (Same Template, Different Content)

The same `provider.php` (or `continuity_steward.php`, `business.php`) template renders three flavors via `aegis_filter_profile_for_viewer()`:

- **Anon:** locked-section placeholders, sign-in CTAs, no contact details, no metrics, no activity feed, no connection info.
- **Logged-in:** full profile incl. contact email/phone, performance/SLA metrics, activity feed, connection info card, action buttons (Send Message, Send Referral, Reserve Slot, Hire/Engage).
- **Owner:** Edit Profile button replaces Send Message; Profile Visibility info panel appears; Connection card hidden (can't connect to self).

### 10.4 Intentional 404s (Slug-Resolution Gating)

These prove the role/visibility logic. Each lands on "Profile not found" regardless of viewer:

| URL | Why 404s |
|---|---|
| `/steward/priya-raman` | Invited CSs are never public |
| `/steward/sarah-johnson` | Sarah has secondary CS role but `cs_account_type ≠ 'business'` |
| `/business/linda-johnson` | Linda is SS, no BP role |
| `/provider/linda-johnson` | Same — no practitioner role |
| `/provider/no-such-person` | Slug doesn't exist |

### 10.5 Edit Profile (Owner Only)

When the owner views their own public profile, the page becomes editable via inline modals (no separate edit-profile page for the public-profile fields — same surface, edit-in-place). Identity + contact fields live exclusively in `profile.php` / `edit-profile.php` (the portal's own profile editor) and never duplicate in Settings.

### 10.6 Slug Generation & Honorific Handling

Slugs are the canonical lookup key for every public profile. Rules:

- `users.slug` is the single source of truth. Generated by `aegis_generate_slug()` from `display_name`, kebab-cased, deduplicated within role-namespace.
- **Honorific exceptions** — `_AEGIS_SLUG_EXCEPTIONS` in `_shared/models.php` defines prefixes that should NOT be stripped from the slug (e.g. "Dr.", "Prof.") because they're part of the canonical identity. So Dr. Sarah Johnson resolves to `/provider/dr-sarah-johnson`, not `/provider/sarah-johnson`.
- The `aegisSlugify()` JS helper mirrors the same rules client-side for live preview.
- Display-name rendering applies a defensive honorific strip unconditionally for `$executor_name` and similar at-render-time fields (so duplicated credentials in stored display names don't double up). The stored slug is never re-derived at display time.

---

## 11. The Aegis Design System

Full reference: `AEGIS-DESIGN-SYSTEM.md`. Summary below.

### 11.1 Color Tokens (in `_shared.css` `:root`)

```
--gold:        #c4a96a          /* warm amber, brand accent */
--gold-light:  #d4be90          /* hover backgrounds, subtle tints */
--gold-dark:   #a0813e          /* active states, primary CTAs, hero/banner bg */

--surface:     #ffffff          /* cards, modals, inputs */
--surface-2:   #f7f5f1          /* page bg, sidebar, topbar, hover fills */
--surface-3:   #eeebe5          /* chips, disabled inputs, icon boxes default */
--surface-4:   #e2ddd5          /* strong dividers */

--border:      #e4dfd7          /* default borders */
--border-dark: #cec8be          /* outline buttons, checkbox unchecked */

--text:        #1e1c1a          /* primary text, headings */
--text-2:      #3d3a36          /* secondary text */
--text-3:      #6b6660          /* meta text */
--text-4:      #a89f94          /* disabled / muted, mini-labels, timestamps */
--text-inverted: #ffffff        /* white on dark */

--red:       #e05c5c   --red-light: #fdf0f0   --red-dark: #c0392b
--green:     #4caf7d   --green-light: #eaf7f1 --green-dark: #2e8a57
--orange:    #e8a94a   --orange-light: #fdf6e8 --orange-dark: #b45309
--blue:      #4a90c4   --blue-light: #eaf3fb  --blue-dark: #2a6a9a
--purple:    #9b72cf   --purple-light: #f3eeff
--emergency: #dc2626   --emergency-light: #fee2e2 --emergency-dark: #991b1b
```

**Color rules:**

- `--gold-dark` (not `--gold`) for active states, primary CTAs, accent borders, serif headings.
- Status colors pair with `-light` background variants for tinted pills, `-dark` for text-on-light.
- Text hierarchy is exactly four steps. Don't invent custom grays.
- **Zero hardcoded hex values in markup or styles.** Always use tokens.

### 11.2 Typography

```
--font-serif: 'Spectral', Georgia, serif
--font-sans:  'Inter', Arial, sans-serif
--font-mono:  'JetBrains Mono' or 'SF Mono', monospace
```

- **Serif** for premium moments only: section titles, hero numbers, names, dates, modal titles, card titles. Never body, labels, buttons.
- **Sans** for everything else.
- **Mono** for codes, identifiers, technical IDs.

**Weights:** `600` and `700` only.

**Type scale:**

| Use | Size | Family | Weight |
|---|---|---|---|
| Hero numbers | 22px | serif | 700 |
| Section title | 18px | serif | 700 |
| Card title | 14–15px | serif | 700 |
| Body | 12.5–13px | sans | 600 |
| Button label | 13px | sans | 700 |
| Meta | 11.5px | sans | 600 |
| Mini-label (uppercase) | 10px | sans | 700, letter-spacing 0.4px, color `--text-4` |

### 11.3 Radius Tokens

```
--radius-xs:   4px
--radius-sm:   6–8px    /* chips, buttons (small), icon boxes, dropdowns, tooltips, segmented tabs */
--radius:      8–12px   /* canonical default — cards, modals, primary buttons, inputs, profile dropdown panel */
--radius-lg:   12–16px  /* btn-lg */
--radius-xl:   22px     /* btn-xl, modal-header rounding */
--radius-full: 9999px   /* status dots, avatar circles */
```

Notes: there are two radius scales in the codebase (the older docs use 6/8/12; the newer cleanup uses 8/12/16). The cleanup direction is `--radius-sm: 8px`, `--radius: 12px`, `--radius-lg: 16px`. Verify against `_shared.css` before relying on a specific value.

**The rule:** badges and tab-pills use `--radius-sm`. Buttons and cards use `--radius`. Tooltips use `--radius-sm`. Reflexively reaching for `--radius-full` for a rectangular pill is wrong unless it's a true circle.

### 11.4 Buttons (Canonical `.btn` + Variant)

Base `.btn` defined once. Variants:

- `.btn-primary` — gold-dark fill, white text. Save, Submit, Confirm, primary CTA.
- `.btn-gold` — semantic alias for primary save/draft.
- `.btn-outline` — transparent bg, neutral border. Cancel, Back, neutral.
- `.btn-danger` — red fill, white text.
- `.btn-emergency` — emergency-red fill with pulsing ring; the active-incident banner button. Uses `--radius-sm`.

Sizes: `.btn-xs`, `.btn-sm`, default, `.btn-lg`, `.btn-xl`. Each scales padding/font/radius proportionally.

`.btn-icon` and `.btn-icon-sm` — icon-only. Topbar messages/bell use this (ghost: white surface + neutral border, gold-dark icon on hover, no border-color change on hover).

### 11.5 Cards, Tabs, Tooltips

- **Cards:** `--radius` 12px, soft shadow only on hover, `cubic-bezier(0.4, 0, 0.2, 1)` 220ms ease.
- **Segmented tabs (`.tabs-segmented`):** `--radius-sm` outer + pills. Hover bg = `rgba(255,255,255,0.8)`. Active = gold-dark fill + subtle gold shadow.
- **Standalone tabs (`.tab-pill`):** `--radius-sm`, hover = gold-dark border + color + white-tinted background.
- **Tooltips (two systems):**
  1. CSS-only `[data-tooltip]::after` for general use. z-index 100000+, `--radius-sm`. Variants: `data-tooltip-pos="bottom"` / `="right"`.
  2. JS-driven `.sidebar-floating-tooltip` for sidebar interactions. Created on DOMContentLoaded, appended to `<body>`, repositioned via `getBoundingClientRect()`. `position: fixed`, z-index `2147483647` to escape ALL parent overflow contexts.

### 11.6 Sidebar Specifics

- Stacking layers: `.topbar` z-100, `.sidebar-overlay` z-999, `.sidebar` z-1000, tooltips z-100000+.
- `.sidebar` has `overflow: visible`; `.sidebar-nav` is the actual scroll container.
- Brand strip + footer are `flex-shrink: 0`.
- Collapse animation: 380ms `cubic-bezier(0.65, 0, 0.35, 1)`. Inner labels fade out at 240ms with `translateX(-6px)`.
- **Sidebar must always default to expanded** — no `localStorage` persistence of collapsed state. Per Carizma's Apr feedback.

### 11.7 Profile Dropdown (`.ep-profile-drop`)

Premium-feel minimal redesign:
- Borderless throughout
- `--radius` panel; `--radius-sm` items
- Spectral name + role; **no verified tick**, no email, no role badge in identity strip
- Items: monochrome icons, single bold label, no sub-descriptions
- Sections separated by hairline gradient dividers
- Open animation: 320ms cubic-bezier with slight overshoot from `top right` origin. Sections cascade in via 40ms staggers.

### 11.8 Icons (`_shared/icons.php`)

Single source of truth: `aegis_icon($key, $size, $extra_class = '')`. 24×24 lucide outline, 1.75 stroke-width, `currentColor`.

- `fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"` everywhere.
- Filled variants use `aegis-icon-filled` class (helper swaps stroke→fill).
- Never inline a new SVG. If an icon doesn't exist in `icons.php`, add it there first.
- Common keys: `clock`, `grid`, `user`, `users`, `clipboard-check`, `file-text`, `credit-card`, `alert-triangle`, `lock`, `message`, `bell`, `chart-trend`, `settings`, `log-out`, `search`, `dollar`, `receipt`, `chevron-down`, `chevron-right`, `shield-check`, `x`, `check`, `pencil`, `trash`, `plus`, `external-link`, `info`, `help-circle`, `calendar`.

**Canonical icon names** (don't use synonyms):

| Action | Canonical | Don't use |
|---|---|---|
| Edit | `pencil` | `edit`, `square-pen` |
| Delete | `trash` | `x-circle`, `delete` |
| Close | `x` | `close` |
| Add new | `plus` | `plus-circle`, `add` |
| Save | `check` (in btn-primary) | `save`, `disk` |
| External link | `external-link` | `arrow-up-right`, `share` |
| Information | `info` | `help-circle` |
| Help / FAQ | `help-circle` | `info` |
| Settings | `settings` | `gear`, `cog`, `sliders` |
| Search | `search` | `magnifier` |
| User / profile | `user` | `person`, `profile` |

### 11.9 The Header URL Map (Role-Aware Routing)

`header.php` is shared across all four portals, but each portal has different canonical filenames for "assignments-like" or "payments-like" pages. There's a `$aegis_urls` table at the top of `header.php`:

```php
'practitioner' => [
    'assignments'       => 'providers.php',          // "My Providers"
    'agreements'        => 'referrals.php',
    'payments'          => 'finances.php',
    'critical_incident' => 'dashboard.php',
],
'continuity_steward' => [
    'assignments'       => 'continuity-stewards.php',
    'agreements'        => 'continuity-management.php',
    'payments'          => 'finances.php',
    'critical_incident' => 'continuity-management.php',
],
'support_steward' => [
    'assignments'       => 'support-stewards.php',
    'agreements'        => 'important-documents.php',
    'payments'          => 'dashboard.php',
    'critical_incident' => 'critical-incident-log.php',
],
'business_partner' => [
    'assignments'       => 'contracts.php',
    'agreements'        => 'contracts.php',
    'payments'          => 'invoices.php',
    'critical_incident' => 'dashboard.php',
],
```

**Rule:** any new dropdown item or notification jump in `header.php` references `<?= $aegis_urls['key'] ?>`, never a hardcoded filename.

### 11.10 Anti-Patterns (Bug-Worthy)

- Hardcoded radii in PX (always use tokens)
- Inline SVGs for any icon that has a canonical equivalent
- Custom hover that changes border-color (border stays neutral; hover signals via bg + icon color + shadow + lift)
- Using `--radius-full` for rectangular labels
- Reaching for `localStorage` for sidebar collapsed state
- Using emojis in UI — Aegis is emoji-free; all visual cues are SVG icons
- Native `confirm()` or `alert()` — use `confirmAction()` and `showToast()`
- `title="…"` on interactive elements — use `data-tip="…"` with the tooltip system
- Re-importing fonts in individual page files — fonts loaded only in `_shared.css`

### 11.11 Pre-Submission Checklist

Before delivering any portal page or module:

- [ ] All terminology renames applied (KALINK→Aegis, Executor→CS, DSR→SS, Clinical Network→Integrative Network)
- [ ] No emojis anywhere in UI
- [ ] No hardcoded hex colors — all via `var(--token)`
- [ ] All SVGs via `aegis_icon()`
- [ ] All radii via tokens
- [ ] All buttons via `.btn` + variant
- [ ] Hover states follow the canonical pattern (no border-color change unless specifically `.btn-outline`)
- [ ] Sidebar default expanded
- [ ] `php -l` clean
- [ ] All buttons, modals, links, dropdowns verified working
- [ ] Color scheme matches original (not dark variant)
- [ ] Approved incident types only (no freeform input)
- [ ] False reporting warning present where required (awaiting client wording)

---

## 12. Onboarding Flows

Lives at `/onboarding/onboarding.html` (separate from the portals). Sign-in at `/onboarding/signin.html`.

### 12.1 Steps

| Step | Content |
|---|---|
| 1 | Role Selection (4 cards: Practitioner, Business Partner, CS, SS) |
| 2 | (CS only) Pathway selector — Business CS vs Invited CS |
| 3 | (SS only) Invite-only gate — blocked signup screen |
| 3 | Account creation form (role-conditional fields) |
| 4 | Email verification (6-digit OTP) |
| 5 | Two-factor authentication (TOTP) |
| 6 | Personalization — "What brings you to Aegis?" multi-select |
| 7 | (BP only) Business profile — Freelancer vs Agency, services, pricing |
| 8 | (Paid roles) Plan selection. Practitioner: tier-card UI (Access vs Practice with Monthly/Annual toggle). MAAT add-on banner below. Founding member banner placeholder. |
| 9 | (Paid roles) Payment (Stripe-hosted; UI is placeholder) |
| 10 | Welcome screen with personalized next-steps checklist; tier-aware (Access shows upgrade as optional step 6) |

### 12.2 Demo URLs

| Scenario | URL |
|---|---|
| Practitioner Practice signup | `?role=practitioner&tier=practice` (+ `&fast=1` to skip) |
| Practitioner Access signup | `?role=practitioner&tier=access` (+ `&fast=1`) |
| Practitioner Practice + Services | `?role=practitioner&tier=practice&services=1` (+ `&fast=1`) |
| CS Business signup | `?role=cs&path=business` (+ `&fast=1`) |
| CS Invited signup | `?role=cs&path=invited` (+ `&fast=1`) |
| SS gate | `?role=support-steward` |
| BP signup | `?role=business-partner` (+ `&fast=1`) |
| Welcome preview | `?preview=welcome&role=...&tier=...` |
| Demo index page | `/onboarding/demo.html` (mirrors the portal `demo.php`) |

### 12.3 Account-Creation Fields (Practitioner)

Title (Prefix), Provider Type, Full Name, Credentials (Suffix), Business / Practice Name, Primary Practice State, Primary Language, Email, Phone, Password.

(Earlier "Services Offered" field was removed; Primary Language was added; per client feedback.)

### 12.4 Step 10 Redirects

After signup, the welcome page hands off to the right portal with the right state params:

- Practitioner: `/provider-portal/dashboard.php?tier=<chosen>&services=<chosen>`
- CS Business: `/continuity-steward-portal/dashboard.php`
- CS Invited: `/continuity-steward-portal/dashboard.php?invited=true`
- SS: `/support-steward-portal/dashboard.php?emergency=false`
- BP: `/biz-portal/dashboard.php?type=<freelancer|agency>`

---

## 13. Communication Layer (Messages, Activity, Notifications)

### 13.1 Messages — Identical Across All Three Continuity Portals

- 3-column app: contact list / thread / detail panel.
- End-to-end encrypted, file attachments, voice notes (recorded, not live calls), reactions, pinning, search, templates.
- **No voice/video calls.** Removed across all portals — Aegis does not host calls. Per Carizma's Apr 7 email.
- **Continuity Contacts pinned section** at the top of every inbox: practitioner + primary SS + alternate SS + primary CS + alternate CS + Aegis Support.
- **Critical Incident badge** on affected threads when an incident is active. All messages in that thread are audit-flagged as legal record.

### 13.2 Activity Log — Single Notification Truth

Per Carizma's workflow concern ("how do pieces inform each other?"), the activity log is the unified event stream that fixes cross-portal communication.

- **One `activity.php` page on every portal** showing every event scoped to that user.
- 10 event types: `message`, `task`, `document`, `incident`, `vault`, `compliance`, `attestation`, `payment`, `account`, `system`.
- 3 severity levels: `info`, `warning`, `critical`.
- Rendered with colored dot icons (red emergency, green task, blue vault, gold message, purple agreement, gray system).
- Filters: event type, severity, date range, provider (CS/SS who have multiple), read/unread.
- Export: CSV/PDF for legal/audit purposes.
- **The SS portal's old `alerts.php` was removed entirely** and merged into `activity.php`.

### 13.3 Header Bell Popup

On every portal:
- Reads from `activity_events` filtered to current user.
- Shows latest 5–10 events as a dropdown preview.
- Clicking "View All" opens `activity.php`.
- Unread counter badge.
- Mark-all-read action.
- Polls or refreshes on page load.

### 13.4 Cross-Portal Event Propagation

Examples:
- SS triggers incident → writes `activity_events` rows for SS (own action), CS (alert), practitioner emergency contact
- CS verifies incident → writes events for CS (own action), SS (vault unlocked), practitioner/estate (verification confirmed)
- Practitioner attests plan → writes events for practitioner (own action), SS (asked to certify), CS (asked to certify)
- License expiring → writes a `compliance` event for practitioner; warning severity

---

## 14. Business Partner Marketplace (Dual-Mode)

### 14.1 The Six Engagement Types

(v4 — both Agency and Freelancer can use any of these.)

1. Hourly
2. Fixed-price project
3. Monthly retainer
4. Per-deliverable
5. Subscription
6. Pro bono

### 14.2 Modules (14 total)

Per the BP-PORTAL-WIRING-BLUEPRINT, modules are named UC-BP-1 through UC-BP-14:

- UC-BP-1: Dashboard
- UC-BP-2: Find Jobs (BP-2.1 Browse + BP-2.2 Search + BP-2.3 Save)
- UC-BP-3: Contracts (BP-3.1 List + BP-3.2 Detail + BP-3.3 Milestone Progress + BP-3.4 Submit Work [diff per type] + BP-3.5 Assign Team [Agency only] + BP-3.6 Timeline + BP-3.7 New Manual Contract)
- UC-BP-4: Proposals (BP-4.1 through BP-4.7)
- UC-BP-5: Milestones (BP-5.1 through BP-5.x)
- UC-BP-6: Finances
- UC-BP-7: Invoices
- UC-BP-8: Payment Setup
- UC-BP-9: My Profile (public view)
- UC-BP-10: Edit Profile
- UC-BP-11: Notifications & Activity Log
- UC-BP-12: Messages
- UC-BP-13: Settings (14 sub-sections)
- UC-BP-14: Team Management (Agency only)

### 14.3 Agency vs Freelancer Differences

| Area | Freelancer | Agency |
|---|---|---|
| Avatar | Personal photo + name | Company logo + name |
| Identifiers | Personal SSN, hourly rate | EIN, team size |
| Submit Work | Direct | Owner reviews first OR team member with permission submits direct |
| Billing | 1099 | Company invoicing |
| Team Module | Hidden | Visible with full CRUD + 4 permission levels (Admin/Manager/Specialist/Viewer) |
| Capacity Panel (Dashboard) | Availability calendar + next slot | Capacity counter (X of Y client slots) + lead time |
| KPIs | Active Clients, Open Proposals, Monthly Earnings, Active Projects, Profile Views, Avg Rating | Active Partners, Business Inquiries, Monthly Revenue, Active Projects, Team Utilisation %, Partner Rating |

URL toggle: `?type=freelancer` or `?type=agency` switches the entire portal experience. The `bp_type` column on `users` is the authoritative source.

### 14.4 Revenue Privacy Toggle

Default ON for all BP accounts. When OFF, all financial figures across the portal blur. Persists per session.

### 14.5 Job → Proposal → Contract → Milestone → Invoice Flow

1. **Practitioner posts job** in `/provider-portal/job-postings.php` (Practice tier only)
2. **BP browses** in `/biz-portal/find-jobs.php`, sends proposal
3. **Practitioner reviews proposals**, accepts one
4. **System auto-generates contract** with scope, value, engagement type, milestones, payment schedule
5. **Both parties e-sign** via platform
6. **For monthly retainers:** auto-charge on 1st of month via Stripe Billing
7. **For milestones:** BP submits work → Practitioner approves → Stripe releases payment to BP's connected account
8. **For Agency:** owner reviews team submission first, or grants team member direct-submit permission

---

## 15. Client Decision Log (Carizma's Confirmed & Pending)

### 15.1 Confirmed by Carizma

| Decision | Date | Source |
|---|---|---|
| ~~Two provider tiers: Continuity Access $39/mo and Continuity Practice $79/mo~~ *(superseded June 2026 — see §8.1)* | Apr 7, 9:37 PM | Email |
| **Provider pricing finalized:** Access $29/$23 · Practice $49/$39 · Services Mode +$19/$15 · MAAT +$29/$23 (monthly / annual-per-month, 20% annual save) | June 2026 | Pricing page sign-off |
| CS pricing: $49/mo or $429/yr (was $69/$759) | Apr 7 | Email |
| Invited CS: free, one practitioner, no public profile | Apr 7 | Email |
| CS-invited practitioners still pay (not free) | Apr 7, 9:37 PM | Email correction |
| KALINK → Aegis everywhere | Apr | Multiple emails |
| Executor → Continuity Steward | Apr | Multiple emails |
| DSR → Support Steward | Apr | Multiple emails |
| Clinical Network → Integrative Network | Apr | Multiple emails |
| 7 approved critical-incident types (no freeform) | Apr 7 | Email |
| 4 of those 7 are opt-in (Missing, Detainment, Natural Disaster, Geopolitical) | Apr 7 | Email |
| Vault locked except on verified incident | Multiple | Confirmed |
| Sidebar always expanded by default (no localStorage) | Apr | Email |
| No voice/video calls in Messages | Apr | Email |
| Provider terminology: do NOT change until she sends replacement language | Apr | Email |
| Headers: hold until she reviews Provider Portal | Apr 7 | Email |
| Keeper Security: Option C (full API) | Apr | Email decision |
| Stripe Connect Express for CS payments (no MA'AT escrow) | Apr | Per attorney |
| Provider Portal: add `overview.php` Start Here page (matching SS/CS pattern) | Apr | Email |
| MAAT add-on rename: "MAAT Professional Continuity Steward Service" | Apr | Email |
| Canonical term for the core doc: "Continuity Plan" | Apr | Email |
| Steward terminology: "Alternate CS" (not "Support CS" — conflicts with Support Steward) | Apr | Email |
| Certification granularity: whole-list, with optional per-task exception flag | Apr | Email |
| ~~Provider Portal pricing UI awaiting her final confirmation before going live~~ *(superseded — pricing now confirmed, see §8)* | Apr | Email |
| Vault encryption at rest: AES-256-GCM envelope (IV + ciphertext + auth tag + version byte) | June 6, 2026 | Implementation decision |
| MA'AT Brand Voice & Tone applied as 4th standard pass after wiring/design/seeding | June 6, 2026 | Tone & Voice Prompt |

### 15.2 On Hold / Pending

| Item | Blocked On |
|---|---|
| Provider terminology change | Carizma to send replacement language |
| False reporting warning exact wording | Carizma to approve final text |
| All financial content in `finances.php` | Attorney + accountant review |
| Header content/layout changes | Carizma to review Provider Portal and confirm |
| Founding member perks UI implementation | Carizma to scope and confirm |
| Shadow network manual entry design | Design discussion needed |
| "Job Postings" nav rename or removal | Confirm with Carizma |
| "My Services" nav rename | Confirm purpose and new name |
| BP pricing update | Verify before launch |
| Per-line typed signature vs click-to-apply-on-file | Carizma to choose |
| Annual review attestation: SS re-certifies annually too? | Confirm |

### 15.3 Communication Protocol with Carizma

(Agreed protocol from her Apr 3 email and follow-ups.)

1. Client has paused her portal review — waiting for all changes across all portals.
2. **Notify her** each time a round of updates is deployed.
3. **Don't ask her to review** until the portal passes the quality checklist (§11.11).
4. **Financial section on hold** until she completes consultations with attorney and accountant.
5. **"Provider" terminology**: do NOT change until she sends confirmed replacement language.
6. **False reporting text**: do NOT add to any portal until she approves final wording.

---

## 16. Terminology Rebrand

| Old | Current | Notes |
|---|---|---|
| KALINK | Aegis | Every UI string. CSS class names like `.dsr-card` need NOT be renamed (display text only). |
| Executor | Continuity Steward (CS) | Display text only — variable names, table names can stay |
| Executor Agreement | Professional Will | Legal references |
| DSR / Designated Successor Representative | Support Steward (SS) | Display text |
| Clinical Network | Integrative Network | UI strings only |
| Integrative Business Services | Clinical Services | Sidebar pill label only (yes, this is the reverse direction — Carizma's specific word choice for the sidebar) |
| Practitioner Partner | Practitioner / Provider | "Practitioner" preferred but "Provider" stays as a synonym (Carizma's rule) |
| Escrow Status | Finance Status | But "escrow" itself is being removed entirely (per attorney) |
| Funds in Escrow | Funds in Finance | Same — being removed |
| My Assignments | My Tasks | Nav label, button text |
| Agreements (nav) | Important Documents | Sidebar |
| Emergency / Emergency Management | Critical Incident / Critical Incident Management | UI strings |
| Activate Emergency Protocol | Activate Critical Incident Protocol | UI strings |
| Emergency type | Critical incident type | UI strings |
| Provider | Provider | DO NOT CHANGE (per Carizma) |

---

## 17. File Naming & Conventions

### 17.1 Symmetric Naming Across Portals

| Canonical name | Provider | CS | SS | BP |
|---|---|---|---|---|
| `dashboard.php` (was `index.php`) | ✅ | ✅ | ✅ | ✅ |
| `overview.php` | ✅ | ✅ | ✅ | ✅ |
| `profile.php` | ✅ | ✅ | ✅ | ✅ |
| `edit-profile.php` | ✅ | ✅ | ✅ | ✅ |
| `continuity-plan.php` (the Builder) | ✅ NEW | — | — | — |
| `continuity-stewards.php` (was `executors.php`) | ✅ | (peer view) | ✅ read-only | — |
| `support-stewards.php` (was `dsr.php`) | ✅ | ✅ peer view | ✅ peer | — |
| `providers.php` | — | ✅ | ✅ | — |
| `my-tasks.php` (was `assignments.php` / `tasks.php`) | — | ✅ | ✅ | — |
| `important-documents.php` (was `agreements.php`) | ✅ | ✅ | ✅ read-only | — |
| `vault.php` (was `documents.php`) | ✅ | ✅ | — | — |
| `continuity-management.php` (was `emergency.php` on CS) | — | ✅ | — | — |
| `critical-incident-log.php` (was `emergency.php` on SS) | — | — | ✅ | — |
| `finances.php` (was `financials.php` on CS) | ✅ | ✅ | — | ✅ |
| `messages.php` | ✅ | ✅ | ✅ | ✅ |
| `activity.php` | ✅ | ✅ | ✅ | ✅ |
| `settings.php` | ✅ | ✅ | ✅ | ✅ |
| `news.php`, `events.php`, `network.php`, `referrals.php`, `services.php`, `job-postings.php` | ✅ | — | — | — |
| `find-jobs.php`, `contracts.php`, `proposals.php`, `milestones.php`, `invoices.php`, `payment-setup.php`, `team.php` | — | — | — | ✅ |

### 17.2 Migration Notes

For every rename, leave a lightweight redirect at the old URL for 30 days so external bookmarks/email links don't 404. Update all internal links, sidebar references, and JavaScript navigation simultaneously.

### 17.3 Settings Pattern (Read-Only Profile Summary)

CS portal had this right first; applied identically across all portals:

- Identity + contact fields appear in **Profile / Edit Profile only**.
- Settings shows a **read-only Profile Summary card** with "Edit Full Profile" CTA at top.
- No duplicate identity fields anywhere.
- Settings 14+ sub-sections: Profile & Identity (read-only), Account & Login, Security & 2FA, Notifications, Email Preferences, Privacy & Data, Subscription/Billing, Integrations, Appearance, Accessibility, Account Actions (pause/transfer/deactivate), Plan Attestation panel (timeline of annual re-attestations and SS/CS certifications).

---

## 18. What's Built / What's Pending

> **Canonical living doc:** `PENDING-ITEMS.md` is the source of truth for week-to-week status (client gates, Devlet commitments, cross-portal stubs, nice-to-haves). The summary below is a stable orientation, not a tracker — when something ships, update `PENDING-ITEMS.md` and let this section drift only on major milestones.

### 18.1 Portal Build Status

| Portal | Read path | Write path | Tone pass | Notes |
|---|---|---|---|---|
| **Provider** | ✅ Complete | ✅ Complete (Waves 1–7) | ✅ Complete (June 6, 2026) | Launch-ready. 89 stubs wired across 15 pages, 16 endpoints, ~71 helpers. Schema v13. AES-256-GCM credential envelope. |
| **Continuity Steward** | 🟢 Read path + Invited/Business path-aware rendering across all pages | 🔴 Not started (one stub: `save_upgrade_cs.php` for the upgrade modal) | 🔴 Not started | All CS pages now branch on `$is_invited_cs` — Invited CS gets locked-single-practitioner UI; Business CS gets full multi-practitioner UI. Canonical upgrade modal at `_shared/modals/upgrade_cs_modal.php`. |
| **Support Steward** | 🟡 Scaffolded (~12 pages) | 🔴 Not started | 🔴 Not started | |
| **Business Partner** | 🟡 Scaffolded (~14 pages) | 🔴 Not started | 🔴 Not started | Closes ~15 cross-portal stubs in Provider. |

### 18.2 Cross-Cutting Infrastructure (built)

- 4 portal sidebars, headers, shared design system, public-profile pages
- Multi-role identity model with public-flag per role
- `?as=` demo override with host-gating
- Demo launcher (`demo.php`) host-aware + corner demo switcher
- 16-table SQLite schema with foreign keys (currently v13)
- `models.php` (read helpers) + `models_write.php` (write helpers) + 16 `save_*.php` AJAX endpoints
- `aegis_log_activity()` cross-portal fan-out — every write helper that has cross-portal impact fans out
- Activity Log unified across all portals
- Header bell popup
- Theme system (server-side body class + sendBeacon + localStorage)
- Vault UI front-end (4 zones, gating logic) + AES-256-GCM local credential envelope
- BP marketplace data model fully seeded
- Continuity Plan Builder UI + verify/incident-trigger/auto-task-generation flow (end-to-end CLI smoke test passes)
- 11+ canonical helper functions in `models.php`; companion write layer in `models_write.php`
- Onboarding flow with all 10 steps + role-conditional branches
- Demo URLs for all signup scenarios (preview + fast variants)
- Stripe Connect UI on CS settings.php (Connected / Restricted / Disconnected states)
- Public profile system live for all four roles (`/public/<role>.php?slug=<slug>`) with viewer-chrome dispatcher
- MA'AT Brand Voice & Tone applied to Provider Portal copy (4th standard pass after wiring/design/seeding)
- **CS portal Invited-vs-Business path-aware rendering** — every CS page detects `$is_invited_cs` via the 4-signal composite (URL flag, `cs_path`, `cs_account_type`, `linked_provider_id`) and gates multi-practitioner UI accordingly. Canonical detection in `providers.php` line 25 + `settings.php` line 41.
- **Cross-portal upgrade modal** at `_shared/modals/upgrade_cs_modal.php` — two-step plan-summary → card-payment flow. Triggered via `openModal('upgradeModal')` from any locked CTA across the CS portal. (Modal hardcodes plan copy today; cutting it over to `aegis_pricing()` is a pending centralization step.)

### 18.3 Major Pending Categories

(See `PENDING-ITEMS.md` for the full granular list.)

- **Client-action gates (Section A):** AWS account creation, Stripe account creation, ESP selection (SES vs SendGrid), BAA execution with each, file-storage approach confirmation, Google Analytics go-ahead, help desk vendor decision, vault Privacy Policy + BAA copy review
- **Devlet build commitments (Section B):** password reset + MFA · help ticket submission · feedback channels (button + contextual questionnaire + free-form) · MAAT white-glove tracked-changes workflow · visibility-permissions reads on public profile pages · ESP transactional pipeline · integration checklist deliverable to client
- **Own-workflow features (Section C):** OAuth integrations · sessions table + revoke · API keys + revoke · webhooks delivery · practice transfer · plan change request
- **Other portals (Section D):** CS / SS / BP write-path builds — each its own multi-wave effort. CS has one near-term stub specifically: `/_shared/save_upgrade_cs.php` (the upgrade-modal write path — currently the modal posts but the endpoint isn't implemented; modal falls back to a toast). Also pending: cut the upgrade modal's hardcoded plan copy over to `aegis_pricing()` so the modal reads from `/_shared/pricing_data.php` like `pricing.php` and `onboarding.php` already do.
- **Cross-portal stubs (Section E):** ~15 stubs in Provider pages awaiting the other portal to originate the action (BP hire/propose/quote/schedule, cross-network invite, calendar integration)
- **Nice-to-haves (Section F):** pagination loaders, NPI registry live search, edit-draft inline editor, NDA attach send, dispute send, access revocation modal

---

## 19. Working Conventions for New Conversations

These are established conventions across many sessions. Honor them or call them out before deviating.

1. **Don't rename canonical files without checking references.** Filenames like `finances.php`, `continuity-management.php`, `critical-incident-log.php`, `important-documents.php` are canonical and many sidebars/headers reference them. New semantic concepts go in `$aegis_urls` and route per-role.

2. **All shared CSS goes in `_shared.css`.** Inline `<style>` blocks only for component-local-by-design CSS (header dropdown, sidebar nav-item).

3. **Use design tokens, not hex codes.** `var(--gold-dark)`, not `#a0813e`.

4. **Buttons follow `.btn` + variant.** Never inline-style a button or invent a one-off class.

5. **Icons via `aegis_icon($key, $size)`.** Extend `_shared/icons.php` first if needed.

6. **Sidebar default expanded.** No `localStorage` of collapsed state.

7. **Demo data is seeded and resettable.** Use `reset.php?token=aegis-demo-reset`. Never edit `aegis.sqlite` directly.

8. **`?as=` is demo-host-only.** Any new auth-touching logic must respect the `$is_demo` gate.

9. **Ship 1–2 specific files at a time.** Plus a short CHANGES.md. Don't zip the whole project repeatedly.

10. **Lint before shipping.** `php -l` for PHP, balanced braces for CSS, `node --check` for JS. Render under PHP CLI to confirm output.

11. **Preserve the wiring blueprints.** Each portal has a 500–620-line wiring blueprint. Those are the implementation contract.

12. **No `index.php` in any portal.** Every portal home page is `dashboard.php`. (Provider Portal `index.php` is a redirect, kept for legacy bookmarks.)

13. **Don't ask the client to review until the portal passes the quality checklist (§11.11).**

14. **Hold financial copy until attorney/accountant sign-off.**

15. **Hold "Provider" terminology change until Carizma sends replacement.**

16. **Surgical edits over rewrites.** When asked to update a file, prefer `str_replace` over rewriting.

17. **Don't add new content unless asked.** Refine, simplify, restyle, unify — don't expand scope.

18. **One question per response.** Avoid stacking three clarifying questions; ask the most important one and infer the rest.

---

## 20. Appendix — Quick-Reference Tables

### 20.1 The Seven Critical-Incident Types

| Type | Always-on or Opt-in | Typical documentation |
|---|---|---|
| Death | Always | Death certificate |
| Short-Term Incapacitation | Always | Doctor's note, hospital admin |
| Long-Term Incapacitation | Always | Medical POA, doctor's letter |
| Missing Person | Opt-in | Police wellness check, missed-appointment log |
| Detainment | Opt-in | Court documentation |
| Natural Disaster | Opt-in | News confirmation, location verification |
| Geopolitical or Conflict-Related Events | Opt-in | News confirmation, embassy contact |

### 20.2 Five Persistent Demo Flags

| Flag | Affects | Default on demo.php |
|---|---|---|
| `as` | Identity / who's signed in | Per card |
| `tier` | Practitioner plan | per card |
| `services` | Provider services mode | 0 |
| `emergency` | Active critical incident | false |
| `invited` | CS onboarding pathway | false |

### 20.3 Stacking (z-index Layers)

| Layer | z-index |
|---|---|
| `.topbar` | 100 |
| `.sidebar-overlay` | 999 |
| `.sidebar` | 1000 |
| `[data-tooltip]::after` (general) | 100000 |
| `.topbar [data-tooltip]::after` (above dropdown) | 100001 |
| `.ep-profile-drop` | 9999 (in topbar's stacking context) |
| `.sidebar-floating-tooltip` (escapes everything) | 2147483647 |

### 20.4 Carizma's Five Open Decisions

1. ✅ Canonical term for core doc → "Continuity Plan"
2. ⏳ Signature mechanics: per-line typed name + title + date, or click-to-apply-on-file?
3. ✅ MAAT add-on naming → "MAAT Professional Continuity Steward Service"
4. ✅ Alternate vs Support CS → "Alternate CS"
5. ✅ Certification granularity → whole-list with optional per-task exception flag

### 20.5 Quick Domain Cheat Sheet

- **MAAT** = the firm (`maatpracticefirm.com`)
- **Aegis** = the SaaS (`kalink.devlet.tech`)
- **Devlet LLC** = the agency building it (Rehan Ur Rashid)
- **Carizma Chapman** = the founder/client (Dr., PhD, DMFT, GA, USA)
- **Watkinsville, Georgia** = MAAT HQ
- **June 1, 2026** = launch target

### 20.6 What a New Chat Can Productively Engage On

- Wiring any page to live data per its wiring blueprint
- Adding nav items via the role-aware tables
- Building public-profile sections via `aegis_filter_profile_for_viewer`
- Polishing CSS using design tokens
- Modifying onboarding flows
- Extending seed data
- Adding DB migrations (extend `db.php`)
- Building new portal pages following the `_shared/sidebar.php` + `_shared/header.php` convention

### 20.7 Out of Scope Without Explicit Approval

- Renaming canonical files
- Changing design tokens or palette
- Introducing new third-party JS/CSS dependencies
- Building real auth (use `?as=` until specified)
- Touching `_shared/db.php`'s host-detection logic
- Adding emojis anywhere
- Adding Provider terminology changes
- Finalizing financial copy
- Implementing founding-member perks UI

---

**End of Comprehensive Context Document.**

Pair with: the four portal wiring blueprints (`PROVIDER-PORTAL-WIRING-BLUEPRINT.md`, `CS-PORTAL-WIRING-BLUEPRINT.md`, `SS-PORTAL-WIRING-BLUEPRINT.md`, `BP-PORTAL-WIRING-BLUEPRINT.md`), the design system reference (`AEGIS-DESIGN-SYSTEM.md`), and the public-profile demo doc (`PUBLIC-PROFILE-AS-PARAM-DEMO.md`).
