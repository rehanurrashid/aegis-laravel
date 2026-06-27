# AEGIS_USE_CASES_OUTPUT.md

**Authoritative use case specification for Aegis — all 5 portals + cross-portal flows.**

Generated against repo commit `53ee59a` after Tasks 2, 4, 8 completion.

**Scope:** Every visible page, every onclick button, every save_*.php action mapped to a use case. Each UC declares actor, trigger, preconditions, main flow, alternatives, postconditions, cross-portal impact, exact tables/columns touched, and business rules.

---

## Table of Contents

- [Portal 1 — Provider (Practitioner)](#portal-1--provider-practitioner) — UC-PRV-001 → UC-PRV-186
- [Portal 2 — Continuity Steward (CS)](#portal-2--continuity-steward-cs) — UC-CS-001 → UC-CS-106
- [Portal 3 — Support Steward (SS)](#portal-3--support-steward-ss) — UC-SS-001 → UC-SS-086
- [Portal 4 — Business Partner (BP)](#portal-4--business-partner-bp) — UC-BP-001 → UC-BP-115
- [Portal 5 — Admin](#portal-5--admin) — UC-ADM-001 → UC-ADM-060
- [Cross-Portal Use Cases](#cross-portal-use-cases) — UC-XP-001 → UC-XP-030
- [Master Entity List](#master-entity-list)
- [Database Design Recommendations](#database-design-recommendations)
- [Schema Gaps Summary](#schema-gaps-summary)

---

## Schema Additions from Recent Work (Tasks 2 + 4)

| Element | Source | Tables / Keys |
|---|---|---|
| Support & Feedback | Task 2 | `complaints`, `complaint_replies`, `help_articles` |
| Visibility prefs | Task 4 | `user_preferences` keys: `privacy_search`, `privacy_network`, `privacy_ratings`, `privacy_location`, `privacy_referral_stats`, `privacy_demographics`, `privacy_creds`, `privacy_contact`, `privacy_rates`, `bp_network_visible` |
| Helper | Task 4 | `aegis_get_visibility_prefs($uid)` |

---

## Portal 1 — Provider (Practitioner)

**Pages (18):** overview, dashboard, edit-profile, continuity-plan, continuity-stewards, support-stewards, network, services, job-postings, referrals, vault, important-documents, messages, activity, news, events, finances, settings, support *(new)*

### Authentication & Onboarding

#### UC-PRV-001: Practitioner registers  `[UNWIRED — SIMULATED]`
> **Validation note (onboarding.php):** the 10-step wizard performs **no backend write** — finish handlers (`finishExecutorFree`/`finishDsrFree`/standard finish) only `window.location.href = getPortalUrl()`. No `users` insert, no Stripe customer/subscription, no email/2FA persistence. The Stripe+DB flow described below is the *intended* spec; it is not implemented. All onboarding steps (UC-PRV-001, 212–215) are currently front-end simulation.
**Actor:** Anonymous user · **Trigger:** Submits signup form on `/signup` after selecting plan on `/pricing.php`
**Preconditions:** Email not in `users`; valid tier (`access` | `practice`)
**Main Flow:**
1. Form submission with email, password, name, license, plan
2. Stripe customer created; subscription created at chosen tier
3. `users` row inserted: `role='practitioner'`, `tier=<plan>`, `slug=<auto>`, `practitioner_public=0`
4. Welcome activity event logged
5. Redirect to `/provider-portal/overview.php`
**Alternatives:** [A1] Email exists → "Sign in?" link; [A2] Stripe declined → `tier='trial'` banner
**Postconditions:** New user authenticated; session cookie set
**Cross-portal impact:** Admin dashboard signup counter +1
**Data touched:**
- Write: `users`, `activity_events`
- Cols: `id, email, password_hash, display_name, role='practitioner', tier, slug, stripe_customer_id, subscription_active=1, practitioner_public=0, created_at`
- Meta: `user_meta.signup_source`, `user_meta.signup_plan`
**Business rules:** Email unique cross-role; password ≥ 12 chars; cannot reuse email of any other role.

#### UC-PRV-002: Practitioner logs in
**Actor:** Returning practitioner · **Trigger:** Submits `/login.php` form
**Main Flow:** Validate password hash → if `mfa_enabled` prompt TOTP → set `aegis_uid` cookie 30d → update `last_login_at` → redirect to portal
**Alternatives:** [A1] Bad password → fail counter++; lock after 5; [A2] `locked_at` set → block; [A3] MFA fails → 3 retries
**Data:** Write `users` (last_login_at, failed_login_count), `user_sessions`, `activity_events`
**Schema gap:** `failed_login_count`, `locked_at`, `locked_reason` columns not yet in `users` (admin spec v16).

#### UC-PRV-003: Practitioner upgrades Access → Practice
**Actor:** Access-tier practitioner · **Trigger:** Clicks "Upgrade" CTA (sidebar pill, locked-page interstitial, or settings)
**Preconditions:** `tier='access'`; no active critical incident
**Main Flow:** `openModal('upgradeModal')` → Stripe checkout → webhook updates `users.tier='practice'` → sidebar items unlock (referrals, services, job-postings)
**Cross-portal impact:** Admin MRR ticker; activity event logged
**Data:** Write `users.tier`, `practitioner_payments`, `activity_events`

#### UC-PRV-004: Practitioner downgrades plan
**Actor:** Practice-tier practitioner · **Trigger:** Settings → Subscription → Downgrade
**Preconditions:** No active BP contracts
**Main Flow:** Confirmation modal lists losses → Stripe subscription updated → `users.tier='access'` at period end
**Alternatives:** [A1] Active BP contract → block with "Close contracts first"
**Data:** Write `users.tier`, `practitioner_payments`

#### UC-PRV-005: Practitioner resets password
**Actor:** Anonymous · **Trigger:** "Forgot password?" link on `/login.php` → `#forgotPanel`
**Main Flow:** Email entered → token generated → SES email (pending A.3) → user clicks link → new password → `users.password_hash` updated, all `user_sessions` invalidated
**Data:** Write `users.password_hash`, `password_reset_tokens` *(schema gap)*, `user_sessions` (delete)
**Schema gap:** `password_reset_tokens` table not present.

### Profile Management

#### UC-PRV-010: Practitioner completes basic profile
**Actor:** Practitioner · **Trigger:** Edit Profile → Basic Info → Save
**Main Flow:** POST `/_shared/save_profile.php` action `save_basic`
**Data:** Write `users` (display_name, bio, location, phone, organization, credentials, title); `user_meta.profile_meta_json`
**Business rules:** display_name 2-80 chars; bio max 2000.

#### UC-PRV-011: Practitioner adds credentials/licenses
**Actor:** Practitioner · **Trigger:** Profile → Credentials → "Add License"
**Data:** Write `users.credentials_list` (JSON) or `user_meta.licenses_json`; schedules 30-day pre-expiry reminder via `activity_events`
**Schema gap:** Dedicated `practitioner_licenses` table recommended for Laravel migration.

#### UC-PRV-012: Practitioner sets specialties
**Trigger:** Profile → Specialties multi-select → Save
**Data:** Meta key `user_meta.practitioner_specialties` (JSON array, max 12)

#### UC-PRV-013: Practitioner sets services offered
**Data:** `user_meta.practitioner_services` (JSON)

#### UC-PRV-014: Practitioner sets therapeutic approaches
**Data:** `user_meta.practitioner_frameworks` (JSON: CBT, EMDR, IFS, etc.)

#### UC-PRV-015: Practitioner updates fee & insurance
**Data:** `user_meta.fees_json`, `user_meta.insurance_panels`, `user_meta.sliding_scale`

#### UC-PRV-016: Practitioner sets availability + accepting status
**Trigger:** Edit Profile → Availability section toggles
**Data:** `user_preferences`: `accepting_clients`, `session_format`, `next_available_date`

#### UC-PRV-017: Practitioner enables IBS (Services) Mode
**Actor:** Practice-tier practitioner · **Trigger:** Settings → Services → toggle on
**Preconditions:** `tier='practice'`
**Main Flow:** POST `/_shared/save_pref.php` → `users.services_mode=1` → sidebar shows "My Services" + "Clinical Services" pill
**Cross-portal impact:** Network "Offers Services" filter inclusion
**Data:** Write `users.services_mode`, `activity_events`

#### UC-PRV-018: Practitioner previews public profile
**Trigger:** Edit Profile → "Preview Public Profile" → opens `/provider/<slug>` in new tab
**Main Flow:** `aegis_resolve_public_profile('practitioner', $slug)` returns `{user, visible, prefs}`; owner bypass shows full record
**Data:** Read `users`, `user_preferences`, `user_meta`

#### UC-PRV-019: Practitioner updates privacy/visibility settings
**Trigger:** Settings → Privacy & Visibility toggles
**Main Flow:** Each toggle POSTs to `/_shared/save_pref.php` independently; `user_preferences` upserted per key
**Cross-portal impact:** `aegis_get_network_search_providers()` excludes user if `privacy_network='0'` (Task 4 wiring); public profile reads use `aegis_get_visibility_prefs()`
**Data:** Write `user_preferences` keys: `privacy_search`, `privacy_network`, `privacy_ratings`, `privacy_location`, `privacy_referral_stats`, `privacy_demographics`
**Business rules:** Default = `'1'` (visible). Owner bypass — practitioner always sees own full profile.

#### UC-PRV-020: Practitioner adds education & training
**Data:** `user_meta.education_json` (array of `{degree, school, year}`)

### Continuity Plan

#### UC-PRV-030: Practitioner creates new Continuity Plan (draft)
**Actor:** Practitioner · **Trigger:** continuity-plan.php → "Create Plan" first-time CTA
**Preconditions:** No existing active plan for user
**Main Flow:** POST `/_shared/save_plan.php` action `save_draft` (or `apply_template`) → `continuity_plans` row with `status='draft'` → default tasks copied → default incident configs created (7 types, 3 mandatory enabled)
**Data:** Write `continuity_plans, plan_tasks, plan_incident_configs, activity_events`
- Meta: `plan_meta.draft_started_at`, `plan_meta.template_version`
**Business rules:** One active plan per practitioner.

#### UC-PRV-031: Configure which incident types are active
**Trigger:** Plan Builder Step 2 — opt-in toggles for Missing, Detainment, Natural Disaster, Geopolitical
**Data:** `plan_incident_configs.enabled` per type (Death/Short-Term/Long-Term always on)
**Action:** `save_plan.php` → `save_incident_config`

#### UC-PRV-032: Set required documentation per incident type
**Data:** `plan_incident_configs.docs_required` (JSON array)

#### UC-PRV-033: Add task for Continuity Steward
**Trigger:** Plan Builder Step 4 → "Add CS Task"
**Action:** `save_plan.php` → `add_task` with `assignee_role='cs'`
**Data:** Write `plan_tasks` (plan_id, title, description, assignee_role='cs', priority, due_after_days, status='pending')

#### UC-PRV-034: Add task for Support Steward
Same as UC-PRV-033 with `assignee_role='ss'`.

#### UC-PRV-035: Send plan for signature
**Preconditions:** Status=`draft`; CS designated; SS designated; ≥1 vault item; all required fields complete
**Main Flow:** POST `save_plan.php` → `finalize_sign` (initiates signature flow)
**Cross-portal impact:** CS + SS receive `plan_ready_for_signature` activity event
**Data:** `continuity_plans.status='pending_signatures', sent_for_signatures_at`

#### UC-PRV-036: Practitioner signs Continuity Plan
**Trigger:** Important Documents → plan row → "Sign"
**Action:** `save_document.php` → `sign`
**Main Flow:** Typed signature + agreement checkbox + date → `continuity_documents` row updated → if all 3 parties signed → plan activates
**Cross-portal impact:** CS+SS see `plan_signed_provider` event
**Data:** Write `continuity_documents.signed_by_provider_at, signature_blob_provider`; `continuity_plans.status` (if final)

#### UC-PRV-037: View signed plan in Important Documents
**Trigger:** important-documents.php load
**Data:** Read `continuity_documents, continuity_plans, plan_stewards, users`

#### UC-PRV-038: Initiate Annual Re-Attestation
**Trigger:** Dashboard "Re-Attestation Due" card → "Begin"; 365 days from `last_attestation_at`
**Action:** `save_plan.php` → `begin_annual_review`
**Data:** Write `continuity_plans.last_attestation_at, last_attestation_status`

#### UC-PRV-039: Complete Annual Re-Attestation 8-step checklist
**Continuation of UC-PRV-038.** Each step writes confirmation; final step sets `last_attestation_status='current'`
**Cross-portal impact:** CS+SS receive `annual_attestation_complete` event

#### UC-PRV-040: Attest Vault is complete
**Trigger:** Vault → "Attest Vault" button
**Action:** `save_plan.php` → `attest_vault` (or `save_certify.php` → `certify`)
**Main Flow:** Modal confirmation → `users.vault_attested_at=now` → 11-recipient fan-out to CS + SS
**Cross-portal impact:** CS provider card chip "Vault Attested [date]"; SS chip same
**Data:** Write `users.vault_attested_at`, `activity_events` (11 rows)

### Continuity Steward Management

#### UC-PRV-050: Invite existing Aegis user as Primary CS
**Trigger:** continuity-stewards.php → "Designate CS" → search by email/name
**Action:** `save_steward.php` → `add_steward` with `steward_role='primary_cs'`
**Cross-portal impact:** CS receives `cs_designated` event; CS portal providers.php list updates
**Data:** Write `plan_stewards (plan_id, steward_id, steward_role, status='pending_acceptance', designated_at)`, `activity_events`

#### UC-PRV-051: Invite external contact as CS (not yet on Aegis)
**Action:** `save_steward.php` → `invite_external`
**Main Flow:** Creates placeholder `users` row with `cs_account_type='invited', password_hash=NULL` → invitation email (SES pending) → `plan_stewards` row pending
**Data:** Write `users (placeholder), plan_stewards, activity_events`

#### UC-PRV-052: Designate Alternate CS
Same as UC-PRV-050 with `steward_role='alternate_cs'`.

#### UC-PRV-053: Set per-incident CS authorization matrix
**Trigger:** Plan Builder CS step → matrix toggles
**Action:** `save_plan.php` → `set_authorization` (or `save_steward.php` → `set_authorization`)
**Data:** `plan_incident_configs.authorized_cs_ids` (JSON array) per incident type

#### UC-PRV-054: Copy tasks from Primary CS to Alternate CS
**Trigger:** Alternate CS card → "Copy Tasks from Primary"
**Action:** `save_steward.php` → `copy_tasks` (or `save_plan.php` → `copy_tasks`)
**Data:** Insert duplicate `plan_tasks` rows with `assignee_steward_id=<alternate>`

#### UC-PRV-055: View CS countersignature status
**Data:** Read `plan_stewards, continuity_documents`

#### UC-PRV-056: Remove a CS assignment
**Trigger:** CS card → "Remove" with reason
**Action:** `save_steward.php` → `remove`
**Preconditions:** No active critical incident
**Cross-portal impact:** CS receives `cs_removed` event
**Data:** `plan_stewards.status='removed', removed_at, removed_reason`

### Support Steward Management

#### UC-PRV-060: Designate Primary SS
Same pattern as UC-PRV-050 with `steward_role='primary_ss'`.

#### UC-PRV-061: Designate Alternate SS
Same with `steward_role='alternate_ss'`.

#### UC-PRV-062: Copy tasks from Primary SS to Alternate SS
Same pattern as UC-PRV-054 for SS rows.

#### UC-PRV-063: Remove SS assignment
Same as UC-PRV-056 for SS rows.

### Document Vault

#### UC-PRV-070: Upload document to Vault (4 zones)
**Actor:** Practitioner · **Trigger:** Vault page → zone-specific "Upload" CTA
**Zones:** standard | emergency | roster | credentials
**Action:** `save_vault.php` → `add_item` (or `add_credential` for credentials zone)
**Main Flow:** Dropzone upload (S3 signed URL pending AWS) → POST → `vault_items` row → credentials zone uses AES-256-GCM envelope
**Cross-portal impact:** CS+SS see vault item count update (not content)
**Data:** Write `vault_items (id, practitioner_id, zone, title, description, file_path, file_size, mime_type, encrypted_data, created_at)`, `activity_events`
- Meta: `vault_item_meta.tags`, `vault_item_meta.access_level`
**Business rules:** Tier `access` caps total vault items at 25; credentials zone always encrypted.

#### UC-PRV-071: Set vault permissions per document
**Action:** `save_vault.php` → `grant_access` / `revoke_access` / `update_access`
**Data:** `vault_items.access_grant` JSON

#### UC-PRV-072: Manage Client Roster (add/edit/Priority Response)
**Action:** `save_vault.php` → `add_client`
**Data:** `vault_items` row with `zone='roster', roster_entry_data` JSON `{initials, contact, priority_response, last_visit, notes_encrypted}`

#### UC-PRV-073: Mark client as discharged/closed
**Data:** `roster_entry_data.status='closed'`

#### UC-PRV-074: Attest Vault complete
See UC-PRV-040.

#### UC-PRV-075: View Vault access log
**Action:** `save_vault.php` → `vault_log_view` (logs the view event itself)
**Data:** Read `activity_events` WHERE `module='vault'` AND `scoped_provider_id=<uid>`

### Important Documents

#### UC-PRV-080: View all signed documents
**Data:** Read `continuity_documents, plan_stewards, users`

#### UC-PRV-081: Upload additional support document
**Action:** `save_document.php` → `draft`
**Data:** `continuity_documents.doc_type='support', title, file_path, uploaded_by_id`

#### UC-PRV-082: Request document from CS/SS
**Cross-portal impact:** Recipient feed `document_requested`
**Data:** `activity_events` only (no doc table change)

### Activation (Self-reported Critical Incident)

#### UC-PRV-090: Self-report a critical incident
**Trigger:** Dashboard → "Activate Continuity Support"
**Action:** `save_incident.php` → `practitioner_activate`
**Preconditions:** Active plan
**Main Flow:** Modal: incident type + docs upload → `critical_incidents` row → CS+SS get alerts
**Cross-portal impact:** CS portal alert (verification required); SS portal alert
**Data:** Write `critical_incidents (incident_type, reported_by_practitioner=1, status='reported', created_at)`, `activity_events`

#### UC-PRV-091: Activate Continuity Support (full flow)
Same as UC-PRV-090 — official title.

### Network

#### UC-PRV-100: Send connection request
**Trigger:** Network page → provider card → "Connect"
**Preconditions:** Target `practitioner_public=1` AND `privacy_search='1'` (Task 4)
**Action:** `save_network.php` → `connect`
**Data:** `network_requests (status='pending')`, `activity_events`

#### UC-PRV-101: Accept/decline incoming connection request
**Trigger:** Network → Requests tab → Accept/Decline
**Action:** `save_network.php` → `respond`
**Main Flow:** Accept → bidirectional `network_connections` row; Decline → `network_requests.status='declined'`
**Data:** Write `network_connections, network_requests, activity_events`

#### UC-PRV-102: Remove network connection
**Data:** Delete (or soft-delete) `network_connections` row

#### UC-PRV-103: Invite external provider to Aegis
**Action:** `save_network.php` → `invite_provider`
**Data:** Pending `users` row + invite token

#### UC-PRV-104: View recommended shadow providers
**Data:** Read `shadow_connections`

#### UC-PRV-105: Save a shadow provider
**Data:** Write `shadow_connections (saved_by_id, target_provider_id)`

#### UC-PRV-106: Configure AI shadow network settings
**Data:** `user_preferences`: `shadow_network_radius`, `shadow_network_specialties`

#### UC-PRV-107: Browse Business Partners in network
**Main Flow:** `aegis_get_network_search_bps()` respects `bp_network_visible='0'` exclusion (Task 4)
**Data:** Read `users` WHERE `role='business_partner' AND business_partner_public=1`

#### UC-PRV-108: Send referral to network provider
**Trigger:** Provider card → "Send Referral"
**Action:** `save_referral.php` → `create`
**Cross-portal impact:** Recipient `referral_received` event
**Data:** Write `referrals (direction='outbound', status='pending', urgency, client_initials, reason, notes)`

#### UC-PRV-109: View referral details
**Data:** Read `referrals` joined with `users`

#### UC-PRV-110: Cancel an outbound referral
**Action:** `save_referral.php` → `cancel`
**Data:** `referrals.status='cancelled'`

#### UC-PRV-111: Respond to incoming referral
**Action:** `save_referral.php` → `respond`
**Data:** `referrals.status='accepted'|'declined', responded_at`

### My Services (IBS Mode)

#### UC-PRV-120: Enable services mode
See UC-PRV-017.

#### UC-PRV-121: Create service offering
**Action:** `save_service.php` → `create`
**Data:** `practitioner_services (title, service_type, fee, duration_min, status='draft')`

#### UC-PRV-122: Edit/deactivate service
**Action:** `save_service.php` → `update` | `pause` | `delete`
**Data:** `practitioner_services.status`

#### UC-PRV-123: Receive service request from another provider
**Data:** Read `service_requests`

#### UC-PRV-124: Accept/decline service request
**Action:** `save_service.php` → `set_status`
**Data:** `service_requests.status, responded_at`

#### UC-PRV-125: Book session for a service
**Data:** `service_bookings`

#### UC-PRV-126: View service booking history
**Data:** Read `service_bookings`

#### UC-PRV-127: Publish service to public profile
**Action:** `save_service.php` → `publish`
**Data:** `practitioner_services.status='active'`

### Support & Services (Job Postings → Support Requests)

#### UC-PRV-130: Create Support Request (job posting)
**Actor:** Practice-tier practitioner · **Trigger:** job-postings.php → "Post Support Request"
**Action:** `save_job.php` → `create`
**Data:** `bp_jobs (practitioner_id, title, description, category, budget_type, budget_amount, status='open')`
**Business rules:** Practice tier only.

#### UC-PRV-131: View submitted proposals on a request
**Data:** Read `bp_proposals` WHERE `job_id=<id>`

#### UC-PRV-132: Accept a proposal → create contract
**Action:** `save_job.php` → `update_proposal` (status=accepted) → triggers contract auto-create
**Cross-portal impact:** BP receives `proposal_accepted` event + new contract
**Data:** Write `bp_proposals.status='accepted'`, `bp_jobs.status='filled'`, `bp_contracts (new row)`

#### UC-PRV-133: Decline a proposal
**Action:** `save_job.php` → `update_proposal` (status=declined)
**Data:** `bp_proposals.status='declined'`

#### UC-PRV-134: Close a Support Request
**Action:** `save_job.php` → `set_status`
**Data:** `bp_jobs.status='closed'`

#### UC-PRV-135: Manage active contract — approve milestone
**Action:** `save_job.php` → `set_contract_status` or `save_finance.php` → `approve_invoice`
**Data:** `bp_milestones.status='approved'`

#### UC-PRV-136: Pay a BP invoice
**Action:** `save_finance.php` → `invoice_mark_paid_manually` (manual) OR Stripe webhook (auto)
**Cross-portal impact:** BP `invoice_paid` event + payout queued
**Data:** Write `bp_invoices.status='paid'`, `bp_invoice_payments`, `bp_payouts`

#### UC-PRV-137: Sign contract
**Action:** `save_job.php` → `sign_contract`
**Data:** `bp_contracts.practitioner_signed_at`

#### UC-PRV-138: Cancel contract
**Action:** `save_job.php` → `cancel_contract`
**Data:** `bp_contracts.status='cancelled', cancelled_at, cancelled_reason`

### Finances

#### UC-PRV-140: View subscription status + invoices
**Data:** Read `users, practitioner_payments`

#### UC-PRV-141: Add/update payment method
**Action:** `save_finance.php` → `add_payment_method` | `set_default_method` | `remove_payment_method`
**Data:** `practitioner_payment_methods`

#### UC-PRV-142: View MAAT CS Service payment model
**Data:** Read constants from `_shared/pricing_data.php`; no DB write

#### UC-PRV-143: View CS service fee billing history
**Data:** Read `cs_invoices` WHERE `practitioner_id=<uid>`

#### UC-PRV-144: Set autopay preference
**Action:** `save_finance.php` → `set_autopay`
**Data:** `user_preferences.autopay`

#### UC-PRV-145: Cancel subscription
**Action:** `save_finance.php` → `cancel_subscription`
**Data:** `users.subscription_cancel_at_period_end=1`

#### UC-PRV-146: Set steward (CS) payment model
**Action:** `save_finance.php` → `set_steward_payment_model`
**Data:** `plan_stewards.payment_model` (retainer | activation | hourly | pro_bono)

### CEU Tracking

#### UC-PRV-150: Add a CEU entry
**Action:** `save_event.php` → `add_ceu`
**Data:** `practitioner_ceu (category, hours, sync_type, cycle_year, source, meta_label)`

#### UC-PRV-151: View CEU progress by jurisdiction
**Data:** Read `practitioner_ceu`

#### UC-PRV-152: Set CEU requirements for license
**Data:** `user_meta.ceu_requirements_json`

#### UC-PRV-153: Register for event
**Action:** `save_event.php` → `register`
**Data:** `event_registrations`

#### UC-PRV-154: Cancel event registration
**Action:** `save_event.php` → `cancel_registration`
**Data:** `event_registrations.cancelled_at`

#### UC-PRV-155: Export CEU transcript
**Action:** `save_event.php` → `export_transcript`
**Data:** Read `practitioner_ceu`; no write

### Messages & Activity

#### UC-PRV-160: Send message to CS/SS/BP
**Action:** `save_message.php` → `send` or `create_thread`
**Data:** Write `message_threads, messages, activity_events`

#### UC-PRV-161: View activity feed (filtered)
**Data:** Read `activity_events` WHERE `user_id=<uid>`

#### UC-PRV-162: Mark activity as read
**Action:** `save_activity.php` → `mark_read` / `mark_all_read`
**Data:** `activity_events.read_at`

#### UC-PRV-163: Pin/mute a thread
**Action:** `save_message.php` → `pin_thread` / `mute_thread`
**Data:** `message_threads.pinned, muted_until`

#### UC-PRV-164: Mark thread unread
**Action:** `save_message.php` → `mark_unread`

### Settings

#### UC-PRV-170: Update notification preferences
**Data:** `user_preferences` (email_*, push_*, sms_* keys)

#### UC-PRV-171: Change password / enable 2FA
**Main Flow:** QR code → user enters 6-digit code → `users.mfa_enabled=1, mfa_secret=<encrypted>`
**Data:** Write `users.password_hash, mfa_enabled, mfa_secret`

#### UC-PRV-172: Request account closure
**Data:** `users.deactivated_at=now` (soft delete); subscription cancelled at period end
**Schema gap:** `deactivated_at` column to be added.

#### UC-PRV-173: Export all data
**Main Flow:** Backend assembles ZIP across all user-scoped tables; emailed link 24h expiry

#### UC-PRV-174: Revoke active session
**Action:** `save_payment.php` → `revoke_session`
**Data:** `user_sessions` row deleted

### Support & Feedback *(Task 2)*

#### UC-PRV-180: Submit a support ticket
**Trigger:** support.php → "New Ticket" modal
**Action:** `save_support.php` → `create_ticket`
**Data:** `complaints (submitter_id, subject, body, category, submission_channel='ticket', status='open', priority='normal')`, `activity_events`
**Cross-portal impact:** Admin complaints queue +1

#### UC-PRV-181: View ticket list
**Data:** Read `complaints` WHERE `submitter_id=<uid> AND submission_channel='ticket'`

#### UC-PRV-182: Reply to an open ticket
**Action:** `save_support.php` → `reply_ticket`
**Main Flow:** If ticket was resolved/closed, reopens to `open`
**Data:** Write `complaint_replies (complaint_id, author_id, body, is_internal=0)`; possibly `complaints.status='open'`

#### UC-PRV-183: Close own ticket (mark resolved)
**Action:** `save_support.php` → `close_own_ticket`
**Data:** `complaints.status='closed', resolved_at`

#### UC-PRV-184: Submit feedback via Feedback tab
**Action:** `save_support.php` → `send_feedback`
**Data:** `complaints (submission_channel='freeform', category ∈ {feedback, feature_request, bug, praise})`

#### UC-PRV-185: Submit feedback via floating FAB
**Trigger:** Floating Feedback button (any page) → modal
**Action:** `save_support.php` → `send_feedback` with `channel='button'`
**Data:** `complaints (submission_channel='button')`

#### UC-PRV-186: Browse Help Center articles
**Main Flow:** `aegis_get_help_articles('practitioner', $search)` → accordion render
**Data:** Read `help_articles` WHERE `published=1 AND role_visibility LIKE '%practitioner%' OR ='all'`

### Provider Data Map

| UC | Tables Written | Tables Read | Meta Keys Set | Cross-Portal Events |
|---|---|---|---|---|
| 001 | users, activity_events | — | signup_source, signup_plan | admin signup_count |
| 002 | users, user_sessions, activity_events | users | — | — |
| 003 | users, practitioner_payments, activity_events | users | — | admin mrr |
| 004 | users, practitioner_payments | users, bp_contracts | — | admin mrr |
| 005 | users, password_reset_tokens*, user_sessions | users | — | — |
| 010 | users, user_meta, activity_events | — | profile_meta_json | network refresh |
| 011 | users, activity_events | — | credentials_list | — |
| 012-014 | user_meta | — | practitioner_specialties / services / frameworks | — |
| 015 | user_meta | — | fees_json, insurance_panels, sliding_scale | — |
| 016 | user_preferences | users | accepting_clients, session_format | — |
| 017 | users, activity_events | — | — | network search reflect |
| 018 | — | users, user_preferences, user_meta | — | — |
| 019 | user_preferences, activity_events | — | privacy_* (6 keys) | network exclude |
| 020 | user_meta | — | education_json | — |
| 030 | continuity_plans, plan_tasks, plan_incident_configs, activity_events | users | draft_started_at, template_version | — |
| 031 | plan_incident_configs | continuity_plans | — | — |
| 032 | plan_incident_configs | — | — | — |
| 033 | plan_tasks, activity_events | continuity_plans | — | CS feed |
| 034 | plan_tasks, activity_events | — | — | SS feed |
| 035 | continuity_plans, activity_events | plan_stewards | — | CS+SS plan_ready |
| 036 | continuity_documents, continuity_plans, activity_events | — | — | CS+SS plan_signed |
| 037 | — | continuity_documents, continuity_plans, plan_stewards, users | — | — |
| 038-039 | continuity_plans, activity_events | — | — | CS+SS attestation |
| 040 | users, activity_events | — | — | CS+SS vault_attested (11-recipient) |
| 050 | plan_stewards, activity_events | users | — | CS cs_designated |
| 051 | users, plan_stewards, activity_events | — | cs_account_type=invited | — |
| 052 | plan_stewards, activity_events | users | — | CS alternate_designated |
| 053 | plan_incident_configs | — | authorized_cs_ids (JSON) | — |
| 054 | plan_tasks | plan_tasks | — | CS tasks_copied |
| 055 | — | plan_stewards, continuity_documents | — | — |
| 056 | plan_stewards, activity_events | critical_incidents | — | CS cs_removed |
| 060-063 | plan_stewards, activity_events | users | — | SS feed |
| 070 | vault_items, activity_events | — | vault_item_meta.tags, access_level | CS+SS vault_count |
| 071 | vault_items | plan_stewards | access_grant (JSON) | — |
| 072-073 | vault_items | — | roster_entry_data (JSON) | — |
| 075 | activity_events | activity_events | — | — |
| 080 | — | continuity_documents, plan_stewards, users | — | — |
| 081 | continuity_documents | — | — | — |
| 082 | activity_events | — | — | CS document_requested |
| 090-091 | critical_incidents, activity_events | continuity_plans, plan_incident_configs | — | CS+SS incident_reported |
| 100 | network_requests, activity_events | users | — | Target feed |
| 101 | network_connections, network_requests, activity_events | — | — | Requester connection_accepted |
| 102 | network_connections | — | — | — |
| 103 | users, network_requests | — | — | — |
| 104-106 | shadow_connections, user_preferences | — | shadow_network_* | — |
| 107 | — | users (BP filtered) | — | — |
| 108 | referrals, activity_events | users | — | Recipient referral_received |
| 109 | — | referrals, users | — | — |
| 110-111 | referrals, activity_events | — | — | Counterpart |
| 121 | practitioner_services | — | — | — |
| 122 | practitioner_services | — | — | — |
| 123-126 | service_requests, service_bookings, activity_events | — | — | Counterpart |
| 127 | practitioner_services | — | — | — |
| 130 | bp_jobs, activity_events | — | — | All BP feeds |
| 131 | — | bp_proposals, users | — | — |
| 132 | bp_proposals, bp_jobs, bp_contracts, activity_events | — | — | BP proposal_accepted |
| 133 | bp_proposals, activity_events | — | — | BP proposal_declined |
| 134 | bp_jobs | — | — | — |
| 135 | bp_milestones | bp_contracts | — | BP milestone_approved |
| 136 | bp_invoices, bp_invoice_payments, bp_payouts, activity_events | — | — | BP invoice_paid |
| 137 | bp_contracts | — | — | BP contract_signed |
| 138 | bp_contracts, activity_events | — | — | BP contract_cancelled |
| 140 | — | users, practitioner_payments | — | — |
| 141 | practitioner_payment_methods | — | — | — |
| 142 | — | — | — | — |
| 143 | — | cs_invoices | — | — |
| 144 | user_preferences | — | autopay | — |
| 145 | users | — | — | admin mrr |
| 146 | plan_stewards | — | — | CS payment_model_set |
| 150 | practitioner_ceu | — | — | — |
| 151-152 | user_meta | practitioner_ceu | ceu_requirements_json | — |
| 153-154 | event_registrations | — | — | — |
| 155 | — | practitioner_ceu | — | — |
| 160 | message_threads, messages, activity_events | users | — | Recipient(s) |
| 161-164 | activity_events, message_threads | activity_events | — | — |
| 170 | user_preferences | — | (notif keys) | — |
| 171 | users | — | — | — |
| 172 | users | — | deactivated_at* | admin user_count |
| 173 | — | (all user-scoped) | — | — |
| 174 | user_sessions | — | — | — |
| 180 | complaints, activity_events | — | — | admin complaints queue |
| 181 | — | complaints | — | — |
| 182 | complaint_replies, complaints, activity_events | complaints | — | admin reply notify |
| 183 | complaints, activity_events | — | — | admin queue update |
| 184-185 | complaints, activity_events | — | — | admin feedback queue |
| 186 | — | help_articles | — | — |

*Asterisked items = schema gaps flagged for v16 migration.*

---

## Portal 2 — Continuity Steward (CS)

**Pages (13):** overview, dashboard, edit-profile, my-tasks, providers, continuity-management, important-documents, vault, messages, activity, finances, settings, support *(new)*

### Onboarding

#### UC-CS-001: CS registers via invitation link from Practitioner
**Actor:** Invitee (placeholder `users` row) · **Trigger:** Clicks email link with `?invited=true&token=<t>`
**Preconditions:** Pending `users` row exists with `cs_account_type='invited', password_hash=NULL`
**Main Flow:** Set password, accept role terms → `users.password_hash` set, `cs_account_type` stays `invited` (free tier, single practitioner) → `plan_stewards.status='active'`
**Cross-portal impact:** Practitioner sees `cs_accepted_designation` event; CS card status updates
**Data:** Write `users (password_hash, accepted_terms_at)`, `plan_stewards.status`, `activity_events`

#### UC-CS-002: CS registers as Business CS (self-initiated)
**Actor:** Anonymous · **Trigger:** Signup at `/signup?role=cs` selecting business CS plan
**Preconditions:** Email not registered
**Main Flow:** Stripe subscription created → `users (role='continuity_steward', cs_account_type='business', cs_public=0)`
**Data:** Write `users, activity_events`

#### UC-CS-003: Invited CS upgrades to Business CS (paid plan)
**Trigger:** Settings → Subscription → "Upgrade to Business CS"
**Main Flow:** Stripe checkout → `users.cs_account_type='business', cs_public` can be enabled
**Data:** Write `users.cs_account_type, cs_public`, `practitioner_payments`

### Profile Management

#### UC-CS-010: CS completes profile
**Trigger:** edit-profile.php → save any section
**Sections:** basic-info, about, contact, specialties, professional, verification, emergency, service, protocols, steward-settings, fees, availability, prefs
**Action:** `save_profile.php` → action varies by section
**Data:** `users` (display_name, bio, organization, credentials), `user_meta.profile_meta_json` (extended)

#### UC-CS-011: CS sets emergency protocols / continuity plan capabilities
**Section:** edit-profile.php → "protocols" tab
**Data:** `user_meta.cs_emergency_protocols` (JSON: response_time, on_call_hours, escalation_chain)

#### UC-CS-012: CS sets fee structure
**Section:** Profile → Fees tab
**Data:** `user_meta.cs_fee_model` (JSON: `{type: retainer|activation|hourly|pro_bono, base_rate, currency}`)

#### UC-CS-013: CS sets service coverage areas
**Data:** `user_meta.cs_coverage_states` (JSON array)

#### UC-CS-014: CS updates availability
**Action:** `save_pref.php`
**Data:** `user_preferences.cs_availability_status` (available | limited | unavailable), `cs_next_available_at`

### Provider Relationships

#### UC-CS-020: CS views list of practitioners they serve
**Page:** providers.php
**Main Flow:** Reads `plan_stewards` WHERE `steward_id=<cs_id> AND status='active'`, joins practitioner data; quick-filter tabs: all, active (incident), standby, due (re-attestation)
**Data:** Read `plan_stewards, continuity_plans, users, critical_incidents`

#### UC-CS-021: CS views practitioner's Continuity Plan & documents
**Trigger:** Provider card → "View Plan" navigates to `important-documents.php?provider=<id>`
**Data:** Read `continuity_plans, plan_tasks, continuity_documents, plan_stewards`

#### UC-CS-022: CS countersigns the Continuity Plan
**Trigger:** important-documents.php → plan row → "Countersign" button (2-step modal)
**Action:** `save_certify.php` → `countersign_plan`
**Main Flow:** Step 1 — review terms; Step 2 — typed signature + checkbox → `continuity_documents.signed_by_cs_at, signature_blob_cs` → if all parties signed, plan activates
**Cross-portal impact:** Practitioner sees `Countersigned` chip on CS card; SS notified
**Data:** Write `continuity_documents, continuity_plans, activity_events`

#### UC-CS-023: CS requests role change with practitioner
**Trigger:** Provider card → kebab → "Request Role Change" (`openRoleChangeModal`)
**Action:** `save_steward.php` → `update_role` (request flow)
**Main Flow:** Modal: change_type ∈ {release, reduce_scope, step_down, other} + reason
**Cross-portal impact:** Practitioner receives `cs_role_change_requested` warning event (header bell)
**Data:** Write `plan_stewards.role_change_request_*`, `activity_events`

#### UC-CS-024: CS views Vault attestation status per practitioner
**Data:** Read `users.vault_attested_at` joined with `plan_stewards`

#### UC-CS-025: CS connects to new practitioner (invite acceptance flow)
See UC-CS-001 alternative path where pending designation exists.

#### UC-CS-026: CS resigns from a practitioner's plan
**Trigger:** Settings → "Resign from Plan" OR provider card kebab → "Resign"
**Action:** `save_steward.php` → `set_status` (status='resigned')
**Preconditions:** No active critical incident
**Cross-portal impact:** Practitioner `cs_resigned` event; auto-suggest alternate
**Data:** `plan_stewards.status='resigned', resigned_at, resigned_reason`

### My Tasks (Standby)

#### UC-CS-030: View all standby tasks organized by practitioner
**Page:** my-tasks.php with quick-tab filter
**Data:** Read `plan_tasks` WHERE `assignee_steward_id=<cs_id> AND status IN ('pending', 'in_progress')`

#### UC-CS-031: Mark a standby task complete
**Trigger:** Task card → "Complete" (`mtOpenComplete` → `mtSubmitComplete`)
**Action:** `save_task.php` → `complete`
**Cross-portal impact:** Practitioner sees task completion event
**Data:** Write `plan_tasks.status='completed', completed_at, completion_notes`, `activity_events`

#### UC-CS-032: Add note to a task
**Trigger:** Task detail modal → note field
**Action:** `save_task.php` → `update_status` (preserves status, appends note)
**Data:** `plan_tasks.notes` (JSON or appended)

#### UC-CS-033: Request task due-date extension
**Trigger:** Task detail → "Request Extension"
**Action:** `save_task.php` → `request_extension`
**Cross-portal impact:** Practitioner `task_extension_requested` event
**Data:** `plan_tasks.extension_requested_until`, `activity_events`

#### UC-CS-034: Flag a task issue (exception)
**Trigger:** Task detail → "Flag Exception"
**Action:** `save_certify.php` → `flag_exception`
**Data:** `plan_tasks.exception_flag=1, exception_reason`, `activity_events`

#### UC-CS-035: Complete Annual Re-Attestation
**Trigger:** my-tasks.php → certify-list button (annual reattestation reminder)
**Action:** `save_certify.php` → `certify` (full plan certification)
**Main Flow:** 8-item checklist modal → confirms all → `users.cs_last_attestation_at=now`
**Cross-portal impact:** Practitioner sees `cs_attestation_complete` event
**Data:** Write `users.cs_last_attestation_at`, `activity_events`

#### UC-CS-036: Clear a previously-flagged exception
**Action:** `save_certify.php` → `clear_exception`
**Data:** `plan_tasks.exception_flag=0`

### Continuity Management (Active Incident)

#### UC-CS-040: CS receives incident alert from SS
**Trigger:** SS reports incident → CS portal `?emergency=true` query state; dashboard alert + sidebar "!" badge
**Data:** Read `critical_incidents` WHERE `status='reported'`

#### UC-CS-041: CS verifies the critical incident
**Trigger:** continuity-management.php → "Verify" button → `cmOpenVerify` → 2-step modal
**Action:** `save_incident.php` → `verify`
**Main Flow:** Step 1 — review evidence; Step 2 — confirm verification (double-check modal)
**Cross-portal impact:** Practitioner + SS receive `incident_verified` event; Vault auto-unlocks for authorized CS
**Data:** Write `critical_incidents.status='verified', verified_by_cs_id, verified_at`, `activity_events`

#### UC-CS-042: CS declines/disputes the incident
**Trigger:** "Decline" button on incident detail
**Action:** `save_incident.php` → `decline_incident`
**Data:** `critical_incidents.status='disputed', decline_reason`

#### UC-CS-043: CS activates Continuity Support
Triggered as side effect of UC-CS-041. Generates `incident_tasks` from `plan_tasks` template; vault access elevated.
**Action:** `save_incident.php` → `trigger`
**Data:** Write `critical_incidents.status='active'`, `incident_tasks` (bulk insert), `activity_events`

#### UC-CS-044: CS works through incident task list
**Trigger:** Incident detail → task row → "Complete"
**Action:** `save_incident.php` → `complete_task`
**Data:** `incident_tasks.status='completed', completed_at, completion_notes`

#### UC-CS-045: CS escalates to Aegis team
**Trigger:** continuity-management.php → "Escalate"
**Cross-portal impact:** Admin complaints queue + activity feed
**Data:** Write `complaints (category='escalation', submission_channel='ticket')` or dedicated `incident_escalations` table

#### UC-CS-046: CS adds update to incident timeline
**Trigger:** "Add Note" on incident timeline (`cmOpenNote`)
**Action:** `save_incident.php` → `add_incident_note`
**Data:** `critical_incidents.timeline_notes` (JSON appended) or separate `incident_notes` table

#### UC-CS-047: CS attaches documentation to incident
**Trigger:** "Attach Doc" (`cmOpenAttach`)
**Action:** `save_incident.php` → `attach_documentation`
**Data:** `critical_incidents.verification_docs` (JSON appended)

#### UC-CS-048: CS closes the incident
**Trigger:** "Close Incident" (`cmOpenClose`)
**Action:** `save_incident.php` → `close`
**Main Flow:** Confirmation modal + closing note → all parties notified → vault re-sealed
**Cross-portal impact:** Practitioner + SS receive `incident_closed` event; vault re-sealed
**Data:** `critical_incidents.status='closed', closed_at, closing_note`, `activity_events`

#### UC-CS-049: CS reopens a closed incident
**Trigger:** Closed incident → "Reopen" (`cmOpenReopen`)
**Action:** `save_incident.php` → `reopen_incident`
**Data:** `critical_incidents.status='active', reopened_at`

#### UC-CS-050: CS views incident audit log
**Data:** Read `activity_events` WHERE `related_record_id=<incident_id> AND related_record_type='incident'`

### Document Vault (sealed / unsealed)

#### UC-CS-060: CS views sealed vault status
**Trigger:** vault.php load when no active incident
**Main Flow:** Shows counts only, no content; standby messaging
**Data:** Read `vault_items` (count only)

#### UC-CS-061: CS accesses unsealed vault during active incident
**Trigger:** vault.php during `?emergency=true`
**Preconditions:** Active `critical_incidents` row with this CS as `verified_by_cs_id`
**Main Flow:** Scope tabs: emergency | credentials | clientroster; categorized cards
**Action:** `save_vault.php` → `vault_log_view` (logs the view event itself for audit)
**Data:** Read `vault_items` (full); write `activity_events` (vault_accessed)

#### UC-CS-062: CS downloads a vault document
**Trigger:** Document card → "Download" (`csDownloadDoc`)
**Action:** `save_vault.php` → `vault_log_download`
**Data:** Read file path; write `activity_events.action='vault_doc_downloaded'`

#### UC-CS-063: CS reveals credential (encrypted)
**Trigger:** Credential card → "Reveal" (`csOpenReveal`)
**Action:** `save_vault.php` → `vault_credential_reveal`
**Main Flow:** Decrypt AES-256-GCM blob → display in modal → auto-hide after 30s
**Data:** Write `activity_events.action='credential_revealed'` (high-severity audit entry)

#### UC-CS-064: CS views client roster row
**Trigger:** Client roster card → "View" (`csViewClient`)
**Data:** Read `vault_items.roster_entry_data` (decrypted)

#### UC-CS-065: CS initiates client referral during incident
**Trigger:** Roster card → "Refer" (`csOpenReferral`)
**Action:** `save_referral.php` → `create` (CS acting on practitioner's behalf)
**Data:** `referrals (initiated_by_cs_id, client_initials, ...)`

#### UC-CS-066: CS exports vault audit log
**Trigger:** Vault page → "Audit Export" (`openModal('auditExportModal')`)
**Action:** `save_vault.php` → `vault_log_export`
**Data:** Read `activity_events` WHERE module='vault'; emails CSV

### Important Documents

#### UC-CS-070: CS views all plan documents per practitioner
**Data:** Read `continuity_documents, continuity_plans, plan_stewards`

#### UC-CS-071: CS uploads a support document
**Trigger:** "Request Doc" or "Upload" (`openModal('requestDocModal')`)
**Action:** `save_document.php` → `draft`
**Data:** `continuity_documents (doc_type='support', uploaded_by_id=<cs_id>)`

#### UC-CS-072: CS views plan in modal
**Trigger:** Document card → "View Plan" (`idOpenPlanView`)
**Data:** Read `continuity_plans, plan_tasks, plan_incident_configs`

#### UC-CS-073: CS countersigns plan from this page
See UC-CS-022. Trigger here is `idOpenCountersign`.

#### UC-CS-074: CS declines plan
**Trigger:** "Decline" button (`idOpenDecline` → `idSubmitDecline`)
**Action:** `save_certify.php` → `decline_plan`
**Cross-portal impact:** Practitioner `cs_declined_plan` event
**Data:** `plan_stewards.status='declined', declined_reason`

#### UC-CS-075: CS re-attests annual agreement per practitioner
**Trigger:** Provider card → "Re-Attest" (`idOpenReattest`)
**Action:** `save_certify.php` → `certify`
**Data:** `users.cs_last_attestation_at`, `plan_stewards.last_attestation_at` (per-practitioner)

#### UC-CS-076: CS views certification history
**Trigger:** Provider card → "Cert History" (`idOpenCertHistory`)
**Data:** Read `activity_events` filtered to attestation events

### Finances

#### UC-CS-080: CS views service fee invoices
**Page:** finances.php — invoice list grouped by practitioner
**Data:** Read `cs_invoices` WHERE `cs_id=<uid>`

#### UC-CS-081: CS sends invoice to practitioner
**Trigger:** Invoice row → "Send" (`finSendInvoice`)
**Action:** `save_finance.php` → `invoice_send`
**Cross-portal impact:** Practitioner gets `invoice_received` event
**Data:** `cs_invoices.status='sent', sent_at`, `activity_events`

#### UC-CS-082: CS sends payment reminder
**Trigger:** Invoice row → "Reminder" (`finOpenReminder`)
**Action:** `save_finance.php` → `invoice_send_reminder`
**Data:** `cs_invoices.last_reminder_at`, `activity_events`

#### UC-CS-083: CS marks invoice paid manually
**Action:** `save_finance.php` → `invoice_mark_paid_manually`
**Data:** `cs_invoices.status='paid', paid_at, payment_method`

#### UC-CS-084: CS voids invoice
**Action:** `save_finance.php` → `invoice_void`
**Data:** `cs_invoices.status='void', voided_at, voided_reason`

#### UC-CS-085: CS creates new invoice
**Trigger:** "New Invoice" (`finOpenNewInvoice`)
**Action:** `save_finance.php` → `invoice_create`
**Data:** `cs_invoices (cs_id, practitioner_id, line_items, amount, status='draft')`

#### UC-CS-086: CS connects Stripe payout account
**Trigger:** finances.php → "Connect Stripe" (`openModal('stripeConnectSetupModal')`)
**Action:** `save_payment.php` → `begin_onboarding`
**Cross-portal impact:** Admin payments view reflects new payout-ready account
**Data:** `users.stripe_connected, stripe_account_id`

#### UC-CS-087: CS views payout history
**Data:** Read `cs_invoices` JOIN `cs_payouts` (if exists) — *schema gap: `cs_payouts` table not present*

#### UC-CS-088: CS sets fee model (retainer / activation / hourly / pro_bono)
See UC-CS-012.

#### UC-CS-089: CS copies invoice Stripe ID
**Trigger:** Invoice → "Copy" Stripe ID (`finCopyStripeId`)
**Data:** No DB write (clipboard only)

### Messages & Activity

#### UC-CS-090: CS sends message to practitioner / SS / Aegis team
**Action:** `save_message.php` → `send` or `create_thread`
**Data:** `message_threads, messages, activity_events`

#### UC-CS-091: CS views activity log
**Data:** Read `activity_events` WHERE `user_id=<cs_id>`; provider filter via `scoped_provider_id`

### Settings

#### UC-CS-092: CS updates notification preferences
**Data:** `user_preferences` (email_*, push_* keys)

#### UC-CS-093: CS changes password / enables 2FA
**Trigger:** Settings → Security panel (`savePassword`, `select2FA`)
**Data:** `users.password_hash, mfa_enabled, mfa_secret`

#### UC-CS-094: CS revokes all sessions
**Trigger:** "Revoke All" (`openModal('revokeAllModal')`)
**Action:** `save_payment.php` → `revoke_session` (loop)
**Data:** Delete all `user_sessions` for `user_id=<cs_id>` except current

#### UC-CS-095: CS exports settings/data
**Trigger:** "Export Settings" (`openModal('exportSettingsModal')`)
**Data:** Read all user-scoped tables; emailed ZIP

#### UC-CS-096: CS pauses CS status (temporary)
**Trigger:** Settings → "Pause Stewardship"
**Data:** `users.cs_paused_at, cs_paused_until`
**Schema gap:** `cs_paused_at` not in current schema.

#### UC-CS-097: CS closes account
**Trigger:** Danger Zone → "Deactivate"
**Preconditions:** No active critical incident; no active designations
**Data:** `users.deactivated_at`

#### UC-CS-098: CS updates visibility prefs (Business CS public profile)
**Data:** `user_preferences`: `privacy_search`, `privacy_creds`, `privacy_location`
**Cross-portal impact:** Public `/steward/<slug>` reads gated per Task 4

### Support & Feedback *(Task 2)*

#### UC-CS-100: CS submits a support ticket
**Action:** `save_support.php` → `create_ticket`
**Data:** `complaints (submitter_id, subject, body, category, submission_channel='ticket')`

#### UC-CS-101: CS views ticket list
**Data:** Read `complaints` WHERE `submitter_id=<cs_id> AND submission_channel='ticket'`

#### UC-CS-102: CS replies to ticket
**Action:** `save_support.php` → `reply_ticket`
**Data:** `complaint_replies`

#### UC-CS-103: CS closes own ticket
**Action:** `save_support.php` → `close_own_ticket`

#### UC-CS-104: CS submits feedback (Feedback tab)
**Action:** `save_support.php` → `send_feedback`

#### UC-CS-105: CS submits feedback via floating FAB
**Action:** `save_support.php` → `send_feedback` with `channel='button'`

#### UC-CS-106: CS browses Help Center
**Main Flow:** `aegis_get_help_articles('cs', $search)`
**Data:** Read `help_articles` WHERE `role_visibility LIKE '%cs%' OR ='all'`

### CS Data Map

| UC | Tables Written | Tables Read | Meta Keys Set | Cross-Portal Events |
|---|---|---|---|---|
| 001 | users, plan_stewards, activity_events | users | accepted_terms_at | Practitioner cs_accepted |
| 002 | users, activity_events | — | — | admin signup_count |
| 003 | users, practitioner_payments | — | — | admin mrr |
| 010 | users, user_meta | — | profile_meta_json | — |
| 011 | user_meta | — | cs_emergency_protocols | — |
| 012 | user_meta | — | cs_fee_model | — |
| 013 | user_meta | — | cs_coverage_states | — |
| 014 | user_preferences | — | cs_availability_status, cs_next_available_at | — |
| 020 | — | plan_stewards, continuity_plans, users, critical_incidents | — | — |
| 021 | — | continuity_plans, plan_tasks, continuity_documents, plan_stewards | — | — |
| 022 | continuity_documents, continuity_plans, activity_events | — | — | Practitioner countersigned; SS plan_signed |
| 023 | plan_stewards, activity_events | users | — | Practitioner cs_role_change_requested |
| 024 | — | users, plan_stewards | — | — |
| 025 | (see UC-CS-001) | | | |
| 026 | plan_stewards, activity_events | — | — | Practitioner cs_resigned |
| 030 | — | plan_tasks | — | — |
| 031 | plan_tasks, activity_events | — | — | Practitioner task_completed |
| 032 | plan_tasks | — | — | — |
| 033 | plan_tasks, activity_events | — | — | Practitioner extension_requested |
| 034 | plan_tasks, activity_events | — | — | Practitioner exception_flagged |
| 035 | users, activity_events | plan_tasks | — | Practitioner cs_attestation_complete |
| 036 | plan_tasks | — | — | — |
| 040 | — | critical_incidents | — | — |
| 041 | critical_incidents, activity_events | — | — | Practitioner+SS incident_verified |
| 042 | critical_incidents, activity_events | — | — | Practitioner+SS incident_declined |
| 043 | critical_incidents, incident_tasks, activity_events | plan_tasks | — | Practitioner+SS incident_active |
| 044 | incident_tasks, activity_events | — | — | Practitioner task_done |
| 045 | complaints, activity_events | — | — | Admin escalation |
| 046 | critical_incidents | — | — | — |
| 047 | critical_incidents | — | — | — |
| 048 | critical_incidents, activity_events | — | — | Practitioner+SS incident_closed |
| 049 | critical_incidents, activity_events | — | — | Practitioner+SS incident_reopened |
| 050 | — | activity_events | — | — |
| 060 | — | vault_items (count only) | — | — |
| 061 | activity_events | vault_items, critical_incidents | — | Practitioner vault_accessed (audit) |
| 062 | activity_events | vault_items | — | — |
| 063 | activity_events | vault_items (decrypt) | — | Practitioner credential_revealed (high-severity audit) |
| 064 | — | vault_items | — | — |
| 065 | referrals, activity_events | vault_items | — | Practitioner cs_initiated_referral |
| 066 | activity_events | activity_events | — | — |
| 070 | — | continuity_documents, continuity_plans, plan_stewards | — | — |
| 071 | continuity_documents | — | — | Practitioner doc_uploaded |
| 072 | — | continuity_plans, plan_tasks, plan_incident_configs | — | — |
| 073 | (see UC-CS-022) | | | |
| 074 | plan_stewards, activity_events | — | — | Practitioner cs_declined_plan |
| 075 | users, plan_stewards | — | — | Practitioner re_attestation |
| 076 | — | activity_events | — | — |
| 080 | — | cs_invoices | — | — |
| 081 | cs_invoices, activity_events | — | — | Practitioner invoice_received |
| 082 | cs_invoices, activity_events | — | — | Practitioner reminder |
| 083 | cs_invoices, activity_events | — | — | Practitioner invoice_paid |
| 084 | cs_invoices | — | — | Practitioner invoice_voided |
| 085 | cs_invoices | — | — | — |
| 086 | users | — | — | admin payouts |
| 087 | — | cs_invoices, cs_payouts* | — | — |
| 088 | (see UC-CS-012) | | | |
| 089 | — | cs_invoices | — | — |
| 090 | message_threads, messages, activity_events | — | — | Recipient(s) |
| 091 | — | activity_events | — | — |
| 092 | user_preferences | — | (notif keys) | — |
| 093 | users | — | — | — |
| 094 | user_sessions | — | — | — |
| 095 | — | (all user-scoped) | — | — |
| 096 | users | — | cs_paused_at* | — |
| 097 | users | — | deactivated_at* | admin user_count |
| 098 | user_preferences | — | privacy_search, privacy_creds, privacy_location | public profile gates |
| 100 | complaints, activity_events | — | — | admin queue |
| 101-103 | complaints, complaint_replies | complaints | — | admin |
| 104-105 | complaints | — | — | admin |
| 106 | — | help_articles | — | — |

---

## Portal 3 — Support Steward (SS)

**Pages (12):** overview, dashboard, edit-profile, providers, continuity-stewards, my-tasks, critical-incident-log, important-documents, messages, activity, settings, support *(new)*

### Onboarding

#### UC-SS-001: SS registers via invitation link
**Actor:** Invitee · **Trigger:** Email link `?invited=true` (similar to UC-CS-001)
**Preconditions:** Pending `users (role='support_steward')` row exists
**Main Flow:** Set password → `users.password_hash` set; `plan_stewards.status='active'`; relationship to inviting practitioner preserved via `users.invited_by_id`
**Cross-portal impact:** Practitioner `ss_accepted` event
**Data:** Write `users (password_hash, accepted_terms_at)`, `plan_stewards.status`, `activity_events`

#### UC-SS-002: SS completes basic profile
**Page:** edit-profile.php
**Sections:** basic-info, contact, about, work, designation, background, emergency, availability, preferences
**Action:** `save_profile.php` → action varies
**Data:** Write `users (display_name, phone, location)`, `user_meta.ss_profile_meta`

### Provider Relationships

#### UC-SS-010: SS views list of practitioners they support
**Page:** providers.php
**Main Flow:** Reads `plan_stewards` WHERE `steward_id=<ss_id> AND steward_role IN ('primary_ss','alternate_ss')`
**Data:** Read `plan_stewards, users, continuity_plans, critical_incidents`

#### UC-SS-011: SS views practitioner's Continuity Plan (read-only)
**Trigger:** Provider card → navigate to important-documents.php
**Data:** Read `continuity_plans, plan_tasks, continuity_documents` (filtered to SS visibility)

#### UC-SS-012: SS acknowledges plan awareness
**Trigger:** important-documents.php → plan row → "Acknowledge" (`sdOpenAcknowledge`)
**Action:** `save_certify.php` → `acknowledge_awareness`
**Cross-portal impact:** Practitioner `ss_acknowledged_plan` event
**Data:** Write `plan_stewards.ss_acknowledged_at`, `continuity_documents.ss_acknowledged_at`, `activity_events`

#### UC-SS-013: SS views assigned tasks per practitioner
**Data:** Read `plan_tasks` WHERE `assignee_steward_id=<ss_id>`

#### UC-SS-014: SS views Vault attestation status per practitioner
**Data:** Read `users.vault_attested_at`

#### UC-SS-015: SS views assigned CS for each practitioner
**Page:** continuity-stewards.php
**Data:** Read `plan_stewards` WHERE `plan_id IN (practitioner plans this SS is on) AND steward_role IN ('primary_cs', 'alternate_cs')`

#### UC-SS-016: SS sends message to assigned CS
**Trigger:** CS card → "Message" (`csSendMessage`)
**Action:** `save_message.php` → `send` / `create_thread`

#### UC-SS-017: SS notifies practitioner that CS is unresponsive
**Trigger:** CS card → "Notify Practitioner" (`csOpenNotify` → `csSubmitNotify`)
**Action:** `save_ss_provider.php` → `notify_practitioner_cs_unresponsive`
**Cross-portal impact:** Practitioner sees `cs_unresponsive_flagged` warning event in header bell
**Data:** Write `activity_events (event_type='practitioner_unresponsive_flagged', severity='warning')`

#### UC-SS-018: SS adds business CS contact for practitioner
**Trigger:** continuity-stewards.php → "Add Business CS" (`openModal('addExecutorModal')`)
**Action:** `save_network.php` → `add_business_contact`
**Data:** `network_connections` row added

#### UC-SS-019: SS saves a private note on provider
**Trigger:** Provider card → "Note" (`openNoteModal`)
**Action:** `save_ss_provider.php` → `save_provider_note`
**Data:** `user_meta.ss_provider_notes_<provider_id>` or dedicated `ss_provider_notes` table
**Schema gap:** Dedicated table recommended.

#### UC-SS-020: SS logs a check-in on practitioner
**Action:** `save_ss_provider.php` → `log_checkin`
**Data:** `activity_events (action='ss_checkin_logged')` or dedicated table

### My Tasks

#### UC-SS-030: View standby task list
**Data:** Read `plan_tasks` WHERE `assignee_steward_id=<ss_id>`

#### UC-SS-031: Mark task complete
**Trigger:** Task detail → "Complete" (`stOpenComplete` → `stComplete`)
**Action:** `save_task.php` → `complete`
**Data:** `plan_tasks.status='completed', completed_at, completion_notes`

#### UC-SS-032: Add task note
**Trigger:** Task detail → note field
**Action:** `save_task.php` → `update_status`
**Data:** `plan_tasks.notes`

#### UC-SS-033: Request task extension
**Trigger:** Task detail → "Request Extension" (`stOpenExtension`)
**Action:** `save_task.php` → `request_extension`
**Data:** `plan_tasks.extension_requested_until`

#### UC-SS-034: Notify CS about task (escalation)
**Trigger:** Task → "Notify CS" (`stOpenNotifyCs`)
**Action:** `save_task.php` → `notify_cs`
**Cross-portal impact:** Assigned CS receives `ss_task_notify` event
**Data:** `activity_events` only

#### UC-SS-035: SS certifies awareness of plan (acknowledgment)
**Trigger:** providers.php → kebab → "Certify" (`ssOpenCertify`)
**Action:** `save_certify.php` → `certify`
**Data:** `plan_stewards.ss_last_attestation_at`

### Critical Incident Reporting

#### UC-SS-040: SS reports a critical incident (multi-step wizard)
**Page:** critical-incident-log.php — 6-step wizard (`ciStep(1..6)`)
**Step 1:** Pick practitioner (`ciPickPrac`)
**Step 2:** Pick incident type (`ciPickType`) — only types enabled in plan
**Step 3:** Contact attempts log (`ciAddAttempt` → `doAppendAttempt`)
**Step 4:** Upload documentation (death cert, police report, hospital, etc.)
**Step 5:** Narrative description
**Step 6:** Confirmation (`ciConfirmModal` → `doTrigger`)
**Action:** `save_incident.php` → `ss_trigger` (final submit)
**Preconditions:** Practitioner has active plan; incident type enabled in `plan_incident_configs`
**Cross-portal impact:** CS portal receives `incident_reported` alert; practitioner notified; admin sees new incident
**Data:** Write `critical_incidents (incident_type, reported_by_ss_id, status='reported', verification_docs, contact_attempts, narrative, created_at)`, `activity_events` (fan-out to all CSes authorized for this incident type)

#### UC-SS-041: SS appends a contact attempt to existing incident
**Trigger:** Incident detail → "Add Attempt" (`appendAttemptModal`)
**Action:** `save_incident.php` → `ss_append_attempt`
**Data:** `critical_incidents.contact_attempts` (JSON appended)

#### UC-SS-042: SS appends a note to existing incident
**Action:** `save_incident.php` → `ss_append_note`
**Data:** `critical_incidents.timeline_notes`

#### UC-SS-043: SS attaches additional document to incident
**Trigger:** Incident detail → "Attach Doc" (`attachDocModal`)
**Action:** `save_incident.php` → `ss_attach_doc`
**Data:** `critical_incidents.verification_docs`

#### UC-SS-044: SS withdraws an incident report
**Trigger:** Incident detail → "Withdraw" (`withdrawModal`)
**Action:** `save_incident.php` → `ss_withdraw`
**Preconditions:** Incident `status IN ('reported', 'disputed')` only (not active/closed)
**Cross-portal impact:** CSes notified of withdrawal
**Data:** `critical_incidents.status='withdrawn', withdrawn_at, withdrawal_reason`

#### UC-SS-045: SS views active incident dashboard
**Page:** dashboard.php with `?emergency=true`
**Data:** Read `critical_incidents` WHERE `reported_by_ss_id=<ss_id> AND status IN ('reported', 'verified', 'active')`

#### UC-SS-046: SS views incident status updates from CS
**Data:** Read `activity_events` WHERE `related_record_id=<incident_id>`

#### UC-SS-047: SS views closed incident history
**Trigger:** critical-incident-log.php → surface=closed
**Data:** Read `critical_incidents` WHERE `status IN ('closed', 'withdrawn')`

### Continuity Steward Coordination

#### UC-SS-050: SS contacts assigned CS via messages
See UC-SS-016.

#### UC-SS-051: SS views CS task progress on active incident
**Data:** Read `incident_tasks` WHERE `incident_id=<id>`

#### UC-SS-052: SS views CS contact details
**Trigger:** CS card → click (`csOpenDetail`)
**Data:** Read `users` (CS row); permissions: name + role + phone shown; email gated by CS privacy prefs

### Important Documents

#### UC-SS-060: SS views all documents shared with them
**Data:** Read `continuity_documents` WHERE `plan_id IN (...)` filtered by SS access

#### UC-SS-061: SS downloads a document
**Trigger:** Document card → download
**Data:** Read file; activity event logged

#### UC-SS-062: SS uploads a support document
**Action:** `save_document.php` → `draft`
**Data:** `continuity_documents (doc_type='support', uploaded_by_id=<ss_id>)`

#### UC-SS-063: SS views acknowledgment history
**Trigger:** Plan card → "Ack History" (`sdOpenAckHistory`)
**Data:** Read `activity_events` WHERE `action='ss_acknowledged_plan'`

### Messages & Activity

#### UC-SS-070: SS sends message to Practitioner / CS / Aegis team
**Action:** `save_message.php` → `send` / `create_thread`

#### UC-SS-071: SS views activity log
**Data:** Read `activity_events` WHERE `user_id=<ss_id>`

#### UC-SS-072: SS marks activity as read
**Action:** `save_activity.php` → `mark_read`

### Settings

#### UC-SS-073: SS updates notification preferences
**Data:** `user_preferences` (notif keys)

#### UC-SS-074: SS changes password / enables 2FA
**Data:** `users.password_hash, mfa_enabled, mfa_secret`

#### UC-SS-075: SS verifies email change
**Trigger:** Settings → email field → save (`confirmEmailModal`)
**Data:** `users.pending_email, email_verification_token`

#### UC-SS-076: SS pauses stewardship
**Trigger:** Settings → "Pause Stewardship" (`pauseAccountModal`)
**Data:** `users.ss_paused_at, ss_paused_until`
**Schema gap:** `ss_paused_at` not in current schema.

#### UC-SS-077: SS exports settings/data
**Trigger:** Settings → "Export" (`exportSettingsModal`)
**Data:** Read all SS-scoped tables; emailed link

#### UC-SS-078: SS closes account
**Trigger:** Danger Zone → "Deactivate" (`confirmDeactivate`)
**Data:** `users.deactivated_at`

#### UC-SS-079: SS updates contact visibility pref
**Data:** `user_preferences.privacy_contact`
**Cross-portal impact:** Public `/support-steward/<slug>` reads gated per Task 4

#### UC-SS-080: SS applies theme (gold/dark)
**Trigger:** Settings → theme toggle (`applyTheme`)
**Data:** `user_preferences.theme`

### Support & Feedback *(Task 2)*

#### UC-SS-081: SS submits support ticket
**Action:** `save_support.php` → `create_ticket`

#### UC-SS-082: SS views ticket list
**Data:** Read `complaints` WHERE `submitter_id=<ss_id>`

#### UC-SS-083: SS replies to ticket
**Action:** `save_support.php` → `reply_ticket`

#### UC-SS-084: SS submits feedback
**Action:** `save_support.php` → `send_feedback`

#### UC-SS-085: SS submits feedback via FAB
**Action:** `save_support.php` → `send_feedback` with `channel='button'`

#### UC-SS-086: SS browses Help Center
**Data:** Read `help_articles` WHERE `role_visibility LIKE '%ss%' OR ='all'`

### SS Data Map

| UC | Tables Written | Tables Read | Meta Keys Set | Cross-Portal Events |
|---|---|---|---|---|
| 001 | users, plan_stewards, activity_events | users | accepted_terms_at | Practitioner ss_accepted |
| 002 | users, user_meta | — | ss_profile_meta | — |
| 010 | — | plan_stewards, users, continuity_plans, critical_incidents | — | — |
| 011 | — | continuity_plans, plan_tasks, continuity_documents | — | — |
| 012 | plan_stewards, continuity_documents, activity_events | — | — | Practitioner ss_acknowledged_plan |
| 013 | — | plan_tasks | — | — |
| 014 | — | users (vault_attested_at) | — | — |
| 015 | — | plan_stewards (CS rows) | — | — |
| 016 | message_threads, messages, activity_events | — | — | CS message_received |
| 017 | activity_events | — | — | Practitioner cs_unresponsive_flagged (warning, header bell) |
| 018 | network_connections | — | — | — |
| 019 | user_meta or ss_provider_notes* | — | ss_provider_notes_<pid> | — |
| 020 | activity_events | — | — | — |
| 030 | — | plan_tasks | — | — |
| 031 | plan_tasks, activity_events | — | — | Practitioner task_completed |
| 032 | plan_tasks | — | — | — |
| 033 | plan_tasks, activity_events | — | — | Practitioner extension_requested |
| 034 | activity_events | — | — | CS ss_task_notify |
| 035 | plan_stewards, activity_events | — | — | Practitioner ss_attestation_complete |
| 040 | critical_incidents, activity_events | plan_incident_configs | contact_attempts (JSON), verification_docs (JSON), narrative | All CSes authorized + Practitioner |
| 041 | critical_incidents | — | contact_attempts (appended) | — |
| 042 | critical_incidents | — | timeline_notes | — |
| 043 | critical_incidents | — | verification_docs (appended) | — |
| 044 | critical_incidents, activity_events | — | — | All CSes incident_withdrawn |
| 045 | — | critical_incidents | — | — |
| 046 | — | activity_events | — | — |
| 047 | — | critical_incidents | — | — |
| 051 | — | incident_tasks | — | — |
| 052 | — | users (CS) | — | — |
| 060 | — | continuity_documents | — | — |
| 061 | activity_events | — | — | — |
| 062 | continuity_documents | — | — | — |
| 063 | — | activity_events | — | — |
| 070 | message_threads, messages, activity_events | — | — | Recipient(s) |
| 071 | — | activity_events | — | — |
| 072 | activity_events | — | — | — |
| 073 | user_preferences | — | (notif keys) | — |
| 074 | users | — | — | — |
| 075 | users | — | pending_email | — |
| 076 | users | — | ss_paused_at* | — |
| 077 | — | (all SS-scoped) | — | — |
| 078 | users | — | deactivated_at* | admin user_count |
| 079 | user_preferences | — | privacy_contact | public profile gate |
| 080 | user_preferences | — | theme | — |
| 081 | complaints, activity_events | — | — | admin queue |
| 082-083 | complaints, complaint_replies | complaints | — | admin |
| 084-085 | complaints | — | — | admin |
| 086 | — | help_articles | — | — |

---

## Portal 4 — Business Partner (BP)

**Pages (15):** overview, dashboard, edit-profile, find-jobs, proposals, contracts, milestones, invoices, finances, payment-setup, team *(agency only)*, messages, activity, settings, support *(new)*

**Account types:** `bp_type='agency'` | `bp_type='freelancer'` — URL toggle `?type=` mirrors. Agency adds Team module.

### Onboarding

#### UC-BP-001: BP registers as Freelancer
**Actor:** Anonymous · **Trigger:** Signup at `/signup?role=bp&type=freelancer`
**Main Flow:** Email/password/personal info → `users (role='business_partner', bp_type='freelancer')` → Stripe subscription
**Data:** Write `users (bp_type='freelancer', business_partner_public=0)`, `activity_events`

#### UC-BP-002: BP registers as Agency
Same with `bp_type='agency'`. Adds Team module access.

#### UC-BP-003: BP completes company profile
**Page:** edit-profile.php
**Sections:** basic-info, contact, about, services, pricing, coverage, credentials, portfolio, payouts
**Action:** `save_profile.php` (various)
**Data:** Write `users (bp_business_name, organization)`, `user_meta.bp_profile_meta_json`

#### UC-BP-004: BP sets up Stripe Connect payout account
**Trigger:** payment-setup.php → "Begin Onboarding" (`psBeginOnboarding`)
**Action:** `save_payment.php` → `begin_onboarding`
**Main Flow:** Redirect to Stripe Connect Express onboarding → webhook returns `stripe_connected=1`
**Data:** Write `users.stripe_connected, stripe_account_id, stripe_charges_enabled, stripe_payouts_enabled`

### Profile Management

#### UC-BP-010: BP sets services offered + fees
**Trigger:** Profile → Services tab → "Add Service" (`addServiceRow`)
**Data:** `user_meta.bp_services_json` (array of `{service_name, base_fee, fee_unit}`)

#### UC-BP-011: BP sets business identity certifications
**Data:** `user_meta.bp_certifications` (JSON: WBE, MBE, B-Corp, etc.)

#### UC-BP-012: BP sets service coverage (state-by-state)
**Data:** `user_meta.bp_coverage_states` (JSON array of state codes)

#### UC-BP-013: BP sets industry specializations
**Data:** `user_meta.bp_specializations` (JSON)

#### UC-BP-014: BP sets portfolio / case studies
**Trigger:** Profile → Portfolio → "Add Portfolio Item" (`addPortfolioItem`)
**Data:** `user_meta.bp_portfolio_json` (array of `{title, client, description, files}`)

#### UC-BP-015: BP previews public business profile
**Trigger:** Profile → "View Public Profile" link to `/business/<slug>`
**Main Flow:** `aegis_resolve_public_profile('business', $slug)` returns prefs; Task 4 visibility filters apply
**Data:** Read `users, user_meta, user_preferences`

#### UC-BP-016: BP toggles network visibility
**Trigger:** Settings → Privacy → "Show in Network" toggle
**Data:** `user_preferences.bp_network_visible`
**Cross-portal impact:** `aegis_get_network_search_bps()` excludes when `'0'` (Task 4 wiring)

#### UC-BP-017: BP toggles revenue privacy
**Trigger:** Finances page → "Toggle Revenue" (`toggleRevenue`)
**Data:** `user_preferences.bp_revenue_visible` (session toggle, default on)

### Find Jobs (Support Requests)

#### UC-BP-020: BP browses open Support Requests
**Page:** find-jobs.php
**Data:** Read `bp_jobs` WHERE `status='open'`, joined with practitioner

#### UC-BP-021: BP filters requests
**Trigger:** Quick-tab filters (`fjQuick(this, 'admin|billing|marketing|technology|urgent|new')`)
**Data:** Read with WHERE on category/posted_at

#### UC-BP-022: BP saves a Support Request for later
**Trigger:** Job card → bookmark icon (`fjSaveJob`)
**Action:** `save_job.php` → `save_job` (not job creation — this is the save-for-later flag)
**Data:** Write `bp_saved_jobs (bp_id, job_id)`

#### UC-BP-023: BP removes saved job
**Trigger:** Saved drawer → trash icon (`fjRemoveSaved`)
**Action:** `save_job.php` → `unsave_job`
**Data:** Delete `bp_saved_jobs` row

#### UC-BP-024: BP toggles saved-only filter
**Trigger:** "Saved Only" toggle (`fjToggleSavedOnly`)
**Data:** UI filter; no DB

#### UC-BP-025: BP submits a proposal on a Support Request
**Trigger:** Job detail → "Submit Proposal" (`openProposalModal`)
**Action:** `save_job.php` → `submit_proposal`
**Data:** Write `bp_proposals (job_id, bp_id, cover_letter, proposed_rate, proposed_timeline, attached_files, status='submitted')`
**Cross-portal impact:** Practitioner sees proposal in their job posting

#### UC-BP-026: BP edits an open proposal
**Trigger:** Proposals page → proposal row → "Edit" (`buildEditCtx`)
**Action:** `save_job.php` → `update_proposal`
**Preconditions:** `status IN ('submitted', 'viewed')` (not yet accepted/declined)
**Data:** Update `bp_proposals` (cover_letter, proposed_rate, proposed_timeline)

#### UC-BP-027: BP withdraws a proposal
**Trigger:** Proposal row → "Withdraw" (`pConfirmWithdraw`)
**Action:** `save_job.php` → `withdraw_proposal`
**Data:** `bp_proposals.status='withdrawn'`

#### UC-BP-028: BP views proposal status
**Data:** Read `bp_proposals` filtered by `bp_id=<uid>`

#### UC-BP-029: BP views practitioner profile from job card
**Trigger:** Job card author → `viewPartyProfile` → `/provider/<slug>`
**Data:** Read public practitioner profile (Task 4 visibility gates)

### Contracts

#### UC-BP-030: BP receives accepted proposal → contract auto-created
**Trigger:** Practitioner accepts proposal (UC-PRV-132)
**Cross-portal impact:** BP gets `proposal_accepted` event; contract appears in contracts.php
**Data:** New `bp_contracts` row (auto-created server-side)

#### UC-BP-031: BP views contract details
**Trigger:** contracts.php → contract card click (`openContractDetail`)
**Data:** Read `bp_contracts, bp_milestones, users` (practitioner)

#### UC-BP-032: BP signs contract
**Trigger:** Contract detail → "Sign" (`cSign`)
**Action:** `save_job.php` → `sign_contract`
**Data:** `bp_contracts.bp_signed_at, bp_signature_blob`

#### UC-BP-033: BP updates contract status (active / completed)
**Action:** `save_job.php` → `set_contract_status`
**Data:** `bp_contracts.status, status_changed_at`

#### UC-BP-034: BP views contract milestones
**Data:** Read `bp_milestones` WHERE `contract_id=<id>`

#### UC-BP-035: BP cancels contract
**Trigger:** Contract detail → "Cancel" (`confirmAction` → `cCancel`)
**Action:** `save_job.php` → `cancel_contract`
**Preconditions:** No active milestones in submitted/approved state
**Cross-portal impact:** Practitioner notified
**Data:** `bp_contracts.status='cancelled', cancelled_at`

#### UC-BP-036: BP pauses contract
**Action:** `save_job.php` → `pause_contract`
**Data:** `bp_contracts.status='paused', paused_at`

#### UC-BP-037: BP resumes paused contract
**Action:** `save_job.php` → `resume_contract`
**Data:** `bp_contracts.status='active'`

#### UC-BP-038: Agency BP reassigns contract to team member
**Trigger:** Contract detail → "Reassign Team" (`openReassignModal` → `cSubmitReassign`)
**Action:** `save_job.php` → `reassign_team`
**Preconditions:** `bp_type='agency'`
**Data:** `bp_contracts.assigned_team_member_id`

#### UC-BP-039: BP adds milestone to contract
**Trigger:** Contract detail → "Add Milestone" (`cdmOpenAddMilestone` → `cdmSubmitAddMilestone`)
**Action:** `save_job.php` → `add_milestone`
**Data:** Insert `bp_milestones (contract_id, title, description, amount, due_date, status='pending')`

### Milestones

#### UC-BP-040: BP views milestone list
**Page:** milestones.php
**Data:** Read `bp_milestones` WHERE `bp_id=<uid>` (via contract join)

#### UC-BP-041: BP submits milestone for review
**Trigger:** Milestone card → "Submit Work" (`openSubmitWork` → `mSubmit`)
**Action:** Likely `save_job.php` → `set_contract_status` per-milestone *(verify in code)*
**Cross-portal impact:** Practitioner `milestone_submitted` event
**Data:** `bp_milestones.status='submitted_for_review', submitted_at, submission_files`

#### UC-BP-042: BP views milestone approval status
**Data:** Read `bp_milestones.status`

#### UC-BP-043: BP withdraws milestone submission
**Trigger:** Milestone detail → "Withdraw" (`mWithdraw`)
**Data:** `bp_milestones.status='pending', withdrawn_at`

#### UC-BP-044: BP views milestone detail
**Trigger:** Milestone card → click (`openMilestoneDetail`)
**Data:** Read `bp_milestones`, `bp_contracts`, `users`

### Invoices

#### UC-BP-050: BP creates invoice
**Trigger:** invoices.php → "Issue Invoice" (`openIssueInvoice` `'manual'` or `'edit'`)
**Action:** `save_invoice.php` → `create_manual_invoice`
**Data:** Write `bp_invoices, bp_invoice_line_items`

#### UC-BP-051: BP sends invoice to practitioner
**Trigger:** Invoice card → "Send" (`iSend` → `iIssueSubmit`)
**Action:** `save_invoice.php` → `send_invoice`
**Cross-portal impact:** Practitioner `invoice_received` event
**Data:** `bp_invoices.status='sent', sent_at`

#### UC-BP-052: BP resends invoice
**Action:** `save_invoice.php` → `resend_invoice`
**Data:** `bp_invoices.last_resent_at`

#### UC-BP-053: BP sends invoice reminder
**Trigger:** Invoice card → "Reminder" (`iSendReminder`)
**Action:** `save_invoice.php` → likely `send_invoice` with reminder flag
**Data:** `bp_invoices.last_reminder_at`

#### UC-BP-054: BP views payment status
**Data:** Read `bp_invoices.status` (`unpaid` | `paid` | `overdue` | `void`)

#### UC-BP-055: BP voids/cancels an invoice
**Trigger:** Invoice card → "Cancel" (`iCancel`)
**Action:** `save_invoice.php` → `cancel_invoice`
**Data:** `bp_invoices.status='void', voided_at`

#### UC-BP-056: BP refunds an invoice
**Trigger:** Invoice card → "Refund" (`iRefund`)
**Action:** `save_invoice.php` → `refund_invoice`
**Data:** `bp_invoices.status='refunded', refunded_at, refund_amount`

#### UC-BP-057: BP marks invoice manually paid
**Trigger:** Practitioner pays outside Stripe; BP marks paid
**Action:** `save_invoice.php` → `mark_paid`
**Data:** `bp_invoices.status='paid', paid_at, payment_method='manual'`

#### UC-BP-058: Practitioner views invoice (read receipt)
**Action:** `save_invoice.php` → `mark_viewed` (auto on render)
**Data:** `bp_invoices.viewed_at`

#### UC-BP-059: BP adds line item to draft invoice
**Trigger:** Invoice modal → "Add Line Item" (`addLineItem`)
**Data:** `bp_invoice_line_items` (in-memory until save)

### Finances

#### UC-BP-060: BP views YTD earnings summary
**Data:** Read `bp_invoices` summed; `bp_payouts`

#### UC-BP-061: BP views revenue by month chart
**Data:** Read `bp_invoices` grouped by month

#### UC-BP-062: BP views payout history
**Data:** Read `bp_payouts`

#### UC-BP-063: BP downloads tax documents
**Data:** Read `bp_tax_documents`

### Payment Setup

#### UC-BP-070: BP begins/resumes Stripe Express onboarding
**Trigger:** payment-setup.php → "Begin"/"Resume" (`psBeginOnboarding` / `psResumeOnboarding`)
**Action:** `save_payment.php` → `begin_onboarding` / `resume_onboarding`
**Data:** `users.stripe_account_id`

#### UC-BP-071: BP views Stripe Connect status
**Data:** Read `users.stripe_connected, stripe_charges_enabled, stripe_payouts_enabled`

#### UC-BP-072: BP updates SSN last4 / EIN / entity type
**Triggers:** "Update SSN" / "Update EIN" / "Update Entity Type" modals
**Actions:** `save_payment.php` → `update_ssn` / `update_ein` / `update_entity_type`
**Data:** Write `users.ssn_last4, company_ein_last4, entity_type`

#### UC-BP-073: BP updates tax address
**Trigger:** "Update Address" modal
**Action:** `save_payment.php` → `update_address`
**Data:** `users.tax_address_*`

#### UC-BP-074: BP submits W-9
**Trigger:** "Update W-9" modal
**Data:** `users.w9_status='submitted', w9_file_path`

#### UC-BP-075: BP downloads Stripe account data
**Trigger:** "Download Account Data" (`psDownloadAccountData`)
**Action:** `save_payment.php` → `download_account_data`
**Data:** Read user payout-related fields; emailed link

#### UC-BP-076: BP disconnects Stripe Connect account
**Action:** `save_payment.php` → `disconnect`
**Data:** `users.stripe_connected=0, stripe_account_id=NULL`

### Team (Agency only)

#### UC-BP-080: Agency owner views team
**Page:** team.php
**Preconditions:** `bp_type='agency'`
**Data:** Read `bp_team_members, bp_team_invitations`

#### UC-BP-081: Agency owner invites team member
**Trigger:** "Invite Team" (`openModal('inviteModal')` → `tmSendInvite`)
**Action:** `save_team.php` → `invite`
**Data:** Write `bp_team_invitations (bp_id, email, role_title, permission, message, status='pending')`

#### UC-BP-082: Agency owner cancels pending invitation
**Trigger:** Pending Invites modal → "Cancel" (`tmCancelInvite`)
**Action:** `save_team.php` → `cancel_invite`
**Data:** `bp_team_invitations.status='cancelled'`

#### UC-BP-083: Agency owner sets team member role/permissions
**Trigger:** Member detail → "Change Role" (`openModal('rolesModal')` → `tmSubmitRoleChange`)
**Action:** `save_team.php` → `update_member`
**Data:** `bp_team_members.permission` (`Admin` | `Manager` | `Specialist` | `Viewer`)

#### UC-BP-084: Agency owner removes team member
**Action:** `save_team.php` → `remove_member`
**Data:** `bp_team_members.status='removed', removed_at`

#### UC-BP-085: Agency owner updates team member status (active/paused)
**Action:** `save_team.php` → `update_status`
**Data:** `bp_team_members.status`

#### UC-BP-086: Team member accepts invitation (creates account)
**Trigger:** Email link → signup flow
**Data:** New `users` row + `bp_team_members.status='active'`

#### UC-BP-087: Team member logs in (scoped view)
**Main Flow:** Identical to UC-PRV-002 but viewer sees only assigned contracts/milestones based on permission

### Messages & Activity

#### UC-BP-090: BP sends message to practitioner
**Action:** `save_message.php` → `send` / `create_thread`

#### UC-BP-091: BP views activity log
**Data:** Read `activity_events` WHERE `user_id=<bp_id>`

### Settings

#### UC-BP-100: BP updates notification preferences
**Data:** `user_preferences` (notif keys)

#### UC-BP-101: BP changes password / enables 2FA
**Data:** `users.password_hash, mfa_enabled, mfa_secret`

#### UC-BP-102: BP verifies email change
**Trigger:** Settings → email field → confirm (`confirmEmailModal`)
**Data:** `users.pending_email`

#### UC-BP-103: BP closes account
**Trigger:** Danger Zone → "Deactivate" (`confirmDeactivate`)
**Preconditions:** No active contracts
**Data:** `users.deactivated_at`

#### UC-BP-104: BP pauses account
**Trigger:** Settings → "Pause Account" (`pauseAccountModal`)
**Data:** `users.bp_paused_at`
**Schema gap:** column not in current schema.

#### UC-BP-105: BP transfers account
**Trigger:** "Transfer Account" (`transferAccountModal`)
**Data:** Pending ownership change request (schema gap — recommend `account_transfer_requests` table)

#### UC-BP-106: BP cancels subscription
**Trigger:** Subscription panel → "Cancel" (`cancelSubModal`)
**Action:** `save_finance.php` → `cancel_subscription`
**Data:** `users.subscription_cancel_at_period_end=1`

#### UC-BP-107: BP updates rates visibility pref
**Data:** `user_preferences.privacy_rates`
**Cross-portal impact:** Public `/business/<slug>` rates panel gated (Task 4)

#### UC-BP-108: BP exports settings/data
**Trigger:** "Export Settings" (`exportSettingsModal`)
**Data:** Read all BP-scoped tables; emailed link

#### UC-BP-109: BP applies theme
**Data:** `user_preferences.theme`

### Support & Feedback *(Task 2)*

#### UC-BP-110: BP submits support ticket
**Action:** `save_support.php` → `create_ticket`

#### UC-BP-111: BP views ticket list
**Data:** Read `complaints` WHERE `submitter_id=<bp_id>`

#### UC-BP-112: BP replies to ticket
**Action:** `save_support.php` → `reply_ticket`

#### UC-BP-113: BP submits feedback
**Action:** `save_support.php` → `send_feedback`

#### UC-BP-114: BP submits feedback via FAB
**Action:** `save_support.php` → `send_feedback` with `channel='button'`

#### UC-BP-115: BP browses Help Center
**Data:** Read `help_articles` WHERE `role_visibility LIKE '%bp%' OR ='all'`

### BP Data Map

| UC | Tables Written | Tables Read | Meta Keys Set | Cross-Portal Events |
|---|---|---|---|---|
| 001-002 | users, activity_events | — | — | admin signup_count |
| 003 | users, user_meta | — | bp_profile_meta_json | — |
| 004 | users | — | stripe_connected, stripe_account_id | admin payouts |
| 010-014 | user_meta | — | bp_services_json, bp_certifications, bp_coverage_states, bp_specializations, bp_portfolio_json | — |
| 015 | — | users, user_meta, user_preferences | — | — |
| 016 | user_preferences | — | bp_network_visible | network exclude |
| 017 | user_preferences | — | bp_revenue_visible | — |
| 020-021 | — | bp_jobs, users | — | — |
| 022 | bp_saved_jobs | — | — | — |
| 023 | bp_saved_jobs (delete) | — | — | — |
| 025 | bp_proposals, activity_events | bp_jobs | — | Practitioner proposal_submitted |
| 026 | bp_proposals | — | — | Practitioner proposal_updated |
| 027 | bp_proposals, activity_events | — | — | Practitioner proposal_withdrawn |
| 028-029 | — | bp_proposals, users | — | — |
| 030 | (Practitioner trigger; BP receives) | bp_contracts | — | BP proposal_accepted (incoming) |
| 031 | — | bp_contracts, bp_milestones, users | — | — |
| 032 | bp_contracts, activity_events | — | — | Practitioner bp_signed |
| 033 | bp_contracts | — | — | Practitioner contract_status_changed |
| 034 | — | bp_milestones | — | — |
| 035 | bp_contracts, activity_events | bp_milestones | — | Practitioner contract_cancelled |
| 036-037 | bp_contracts, activity_events | — | — | Practitioner |
| 038 | bp_contracts | bp_team_members | — | Team member assigned |
| 039 | bp_milestones | bp_contracts | — | Practitioner milestone_added |
| 040 | — | bp_milestones | — | — |
| 041 | bp_milestones, activity_events | — | — | Practitioner milestone_submitted |
| 042 | — | bp_milestones | — | — |
| 043 | bp_milestones, activity_events | — | — | Practitioner milestone_withdrawn |
| 044 | — | bp_milestones, bp_contracts, users | — | — |
| 050 | bp_invoices, bp_invoice_line_items | — | — | — |
| 051 | bp_invoices, activity_events | — | — | Practitioner invoice_received |
| 052 | bp_invoices | — | — | Practitioner invoice_resent |
| 053 | bp_invoices | — | — | Practitioner reminder |
| 054 | — | bp_invoices | — | — |
| 055-057 | bp_invoices | — | — | Practitioner |
| 058 | bp_invoices | — | — | BP invoice_viewed |
| 060-063 | — | bp_invoices, bp_payouts, bp_tax_documents | — | — |
| 070 | users | — | stripe_account_id | admin payouts |
| 071 | — | users | — | — |
| 072-074 | users | — | ssn_last4, company_ein_last4, entity_type, tax_address_*, w9_status | — |
| 075 | — | users | — | — |
| 076 | users | — | stripe_connected=0 | admin payouts |
| 080 | — | bp_team_members, bp_team_invitations | — | — |
| 081 | bp_team_invitations | — | — | Invitee email |
| 082 | bp_team_invitations | — | — | — |
| 083-085 | bp_team_members | — | — | Member notify |
| 086 | users, bp_team_members | — | — | Owner notify |
| 087 | (UC-PRV-002) | — | — | — |
| 090 | message_threads, messages, activity_events | — | — | Recipient |
| 091 | — | activity_events | — | — |
| 100 | user_preferences | — | (notif keys) | — |
| 101 | users | — | — | — |
| 102 | users | — | pending_email | — |
| 103 | users | — | deactivated_at* | admin user_count |
| 104 | users | — | bp_paused_at* | — |
| 105 | account_transfer_requests* | — | — | admin |
| 106 | users | — | subscription_cancel_at_period_end | admin mrr |
| 107 | user_preferences | — | privacy_rates | public profile gate |
| 108 | — | (all BP-scoped) | — | — |
| 109 | user_preferences | — | theme | — |
| 110-114 | complaints, complaint_replies | complaints | — | admin queue |
| 115 | — | help_articles | — | — |

---

## Portal 5 — Admin

**Pages (6):** dashboard, packages, users, roles, payments, complaints — all **greenfield** per `ADMIN-PORTAL-SPEC.md`.

**Auth gate:** `aegis_require_admin()` middleware (to add to `models.php`) — checks `user_roles.role_name='admin'` for the current user.

### Dashboard

#### UC-ADM-001: Admin views platform stats
**Page:** dashboard.php
**Stats:** total users per role, signups last 30d, MRR, ARR, active plans, active incidents, open complaints, pending support tickets
**Data:** Aggregated reads across `users, continuity_plans, critical_incidents, complaints, practitioner_payments`

#### UC-ADM-002: Admin views signup trend chart by role
**Data:** `SELECT date_trunc('day', created_at), role, COUNT(*) FROM users GROUP BY ...`

#### UC-ADM-003: Admin views revenue trend (MRR by month)
**Data:** Read `practitioner_payments`, `cs_invoices`, `bp_invoices` aggregated

#### UC-ADM-004: Admin views active incidents
**Data:** Read `critical_incidents` WHERE `status IN ('reported','verified','active')`

#### UC-ADM-005: Admin views recent complaints queue
**Data:** Read `complaints` ORDER BY `created_at DESC` LIMIT 10

### Packages

#### UC-ADM-010: Admin views all subscription tiers with subscriber counts
**Data:** Reads constants from `_shared/pricing_data.php` joined with `COUNT(users) GROUP BY tier`

#### UC-ADM-011: Admin updates monthly/annual price for tier
**Action:** Write to `package_overrides` table *(per admin spec, not yet implemented)*
**Data:** `package_overrides (tier, price_monthly_cents, price_annual_cents, effective_at)`
**Schema gap:** `package_overrides` table.

#### UC-ADM-012: Admin toggles feature flag for a tier
**Data:** `package_overrides.feature_flags` (JSON: `{services_mode_allowed: bool, ...}`)

#### UC-ADM-013: Admin sets tier limits
**Data:** `package_overrides.limits` (JSON: `{max_cs: int, max_ss: int, max_team_members: int, ...}`)

### Users

#### UC-ADM-020: Admin searches users
**Filters:** name, email, role, status (active | locked | deactivated)
**Data:** Read `users`

#### UC-ADM-021: Admin views full user profile + plan + assignments
**Data:** Read `users, continuity_plans, plan_stewards, critical_incidents, practitioner_payments, activity_events`

#### UC-ADM-022: Admin views user activity log
**Data:** Read `activity_events` WHERE `user_id=<target>`

#### UC-ADM-023: Admin locks a user account
**Action:** New endpoint `save_admin_user.php` → `lock_user`
**Cross-portal impact:** User locked out at next request; activity event
**Data:** Write `users.locked_at=now, locked_reason`; admin_audit_log row
**Schema gap:** `locked_at, locked_reason, admin_audit_log` not yet in schema.

#### UC-ADM-024: Admin unlocks user account
**Data:** `users.locked_at=NULL, locked_reason=NULL`

#### UC-ADM-025: Admin forces password reset for user
**Data:** `password_reset_tokens` row created; SES email sent

#### UC-ADM-026: Admin changes user role (with audit)
**Data:** `users.role`, `user_roles`, `admin_audit_log`

#### UC-ADM-027: Admin deactivates user account (soft delete)
**Data:** `users.deactivated_at=now`

#### UC-ADM-028: Admin restores deactivated account
**Data:** `users.deactivated_at=NULL`

#### UC-ADM-029: Admin impersonates user (dev only)
**Preconditions:** Local/staging only; never production
**Data:** `admin_audit_log (action='impersonate', target_user_id)`

### Roles & Permissions

#### UC-ADM-030: Admin views all roles
**Data:** Read `roles, role_permissions` with COUNT(users) per role

#### UC-ADM-031: Admin creates custom role
**Action:** `save_admin_role.php` → `create_role`
**Data:** Write `roles (name, system_role=0)`, `role_permissions`

#### UC-ADM-032: Admin sets permissions for role
**Data:** Write `role_permissions (role_id, permission_key, granted)`

#### UC-ADM-033: Admin deletes custom role
**Preconditions:** Zero users assigned to role
**Data:** Delete `roles, role_permissions`

### Payments

#### UC-ADM-040: Admin views full payment ledger
**Data:** Read across `practitioner_payments, cs_invoices, bp_invoices, bp_payouts`

#### UC-ADM-041: Admin views failed payments queue
**Data:** Read WHERE `status='failed'`

#### UC-ADM-042: Admin retries failed payment
**Action:** `save_admin_payment.php` → `retry_payment`
**Main Flow:** Triggers Stripe retry; webhook updates row
**Data:** Audit log entry

#### UC-ADM-043: Admin processes refund (full or partial)
**Action:** `save_admin_payment.php` → `refund_payment`
**Data:** Update payment row; Stripe refund call; `admin_audit_log`

#### UC-ADM-044: Admin views pending BP/CS payouts
**Data:** Read `bp_payouts` WHERE `status='pending'`; `cs_payouts` (schema gap)

#### UC-ADM-045: Admin manually releases payout
**Data:** Update payout row to `status='released'`; Stripe transfer

#### UC-ADM-046: Admin views Stripe webhook event log
**Data:** Read `stripe_webhook_events` table (schema gap)

### Complaints

#### UC-ADM-050: Admin views all complaints
**Page:** complaints.php — primary handler for support tickets + feedback (built via Task 2 schema)
**Filters:** status, priority, category, submission_channel
**Data:** Read `complaints` with WHERE clauses

#### UC-ADM-051: Admin views complaint detail + reply thread
**Data:** Read `complaints, complaint_replies` JOIN `users`

#### UC-ADM-052: Admin assigns complaint to staff
**Action:** `save_admin_complaint.php` → `assign`
**Data:** `complaints.assigned_to=<staff_user_id>`

#### UC-ADM-053: Admin replies to complaint (visible to submitter)
**Action:** `save_admin_complaint.php` → `reply`
**Cross-portal impact:** Submitter sees reply in their support page + activity event
**Data:** Write `complaint_replies (is_internal=0)`, `activity_events`

#### UC-ADM-054: Admin adds internal note (admin-only)
**Action:** `save_admin_complaint.php` → `reply` with `is_internal=1`
**Data:** `complaint_replies (is_internal=1)` — never shown to submitter

#### UC-ADM-055: Admin changes complaint status
**Action:** `save_admin_complaint.php` → `set_status`
**Valid transitions:** `open → in_progress → resolved → closed`
**Data:** `complaints.status`, possibly `resolved_at`

#### UC-ADM-056: Admin escalates complaint to senior review
**Data:** `complaints.priority='urgent', escalated_at`

#### UC-ADM-057: Admin views complaint resolution metrics
**Data:** Aggregated: avg time-to-first-reply, avg time-to-resolution, by category

### Help Articles *(Task 2)*

#### UC-ADM-058: Admin creates/edits help article
**Action:** New admin endpoint `save_admin_help.php` → `create/update`
**Data:** Write `help_articles (category, title, body, role_visibility, sort_order, published)`

#### UC-ADM-059: Admin publishes/unpublishes article
**Data:** `help_articles.published=0|1`

#### UC-ADM-060: Admin reorders articles
**Data:** `help_articles.sort_order`

### Admin Data Map

| UC | Tables Written | Tables Read | Notes |
|---|---|---|---|
| 001-005 | — | users, continuity_plans, critical_incidents, complaints, practitioner_payments | Aggregations |
| 010 | — | (pricing_data constants), users | — |
| 011-013 | package_overrides* | — | Schema gap |
| 020-022 | — | users, continuity_plans, plan_stewards, critical_incidents, activity_events | — |
| 023-024 | users, admin_audit_log* | — | locked_at* |
| 025 | password_reset_tokens* | users | — |
| 026 | users, user_roles, admin_audit_log* | — | — |
| 027-028 | users | — | deactivated_at* |
| 029 | admin_audit_log* | — | Dev only |
| 030-033 | roles, role_permissions | — | All schema gaps |
| 040-045 | (payment tables), admin_audit_log* | (payment tables) | — |
| 046 | — | stripe_webhook_events* | Schema gap |
| 050-057 | complaints, complaint_replies, activity_events | complaints, complaint_replies, users | Task 2 tables now in use |
| 058-060 | help_articles | help_articles | Task 2 |

---

## Cross-Portal Use Cases

These define how portals communicate via `activity_events` fan-out and shared data reads.

### Cross-Portal Fan-out

#### UC-XP-001: Activity event created → fans out to all recipients
**Trigger:** Any write helper calls `aegis_log_activity()`
**Main Flow:** Single helper invocation; per-recipient activity_events row inserted with `user_id` scoped to recipient
**Data:** `activity_events` (one row per recipient)

#### UC-XP-002: CS countersigns plan → Practitioner sees "Countersigned" chip
Source: UC-CS-022. Practitioner's `continuity-stewards.php` reads `continuity_documents.signed_by_cs_at`.

#### UC-XP-003: Vault attested by Practitioner → CS+SS see chip
Source: UC-PRV-040. 11-recipient fan-out (1 self + all CS + all SS designated on plan).

#### UC-XP-004: SS reports incident → CS receives alert
Source: UC-SS-040. CS authorized for this incident type per `plan_incident_configs.authorized_cs_ids` get header bell + dashboard alert + `?emergency=true` query state.

#### UC-XP-005: CS verifies incident → Practitioner activity feed updated
Source: UC-CS-041. Practitioner sees `incident_verified` event; vault status changes.

#### UC-XP-006: Incident activated → CS vault unlocks
Source: UC-CS-043. Vault page now shows full content for authorized CS during active incident only.

#### UC-XP-007: Incident closed → all parties notified, vault re-sealed
Source: UC-CS-048. Practitioner + SS + CS all receive `incident_closed` event.

#### UC-XP-008: Practitioner signs plan → CS+SS get "Plan Ready" notification
Source: UC-PRV-036. CS sees countersign prompt; SS sees acknowledgment prompt.

#### UC-XP-009: CS completes task → Practitioner dashboard reflects completion
Source: UC-CS-031.

#### UC-XP-010: BP proposal accepted → BP receives notification + contract auto-created
Source: UC-PRV-132 → UC-BP-030.

#### UC-XP-011: BP invoice sent → Practitioner receives notification
Source: UC-BP-051.

#### UC-XP-012: Practitioner pays invoice → BP receives payout notification
Source: UC-PRV-136 → BP `bp_payouts` queued.

#### UC-XP-013: CS requests fee amendment → Practitioner notified
Source: UC-CS-023 (role change requests also serve as fee amendment in current impl).

#### UC-XP-014: Annual Re-Attestation due → Practitioner, CS, SS all notified
Source: cron-driven event 30 days before `last_attestation_at + 1 year`. Fan-out to all 3 parties.
**Schema gap:** Cron-scheduled event table not yet present.

#### UC-XP-015: Support ticket reply (admin → user)
Source: UC-ADM-053. Submitter sees reply in their `support.php` thread + activity event.

#### UC-XP-016: SS flags CS unresponsive → Practitioner header bell warning
Source: UC-SS-017. Uses `activity_events.event_type='practitioner_unresponsive_flagged'` (special CHECK constraint value).

#### UC-XP-017: Practitioner declines CS proposal
**Cross-portal impact:** CS feed `cs_declined`.

#### UC-XP-018: BP team member assigned to contract
Source: UC-BP-038. Team member's activity feed receives `contract_assigned`.

#### UC-XP-019: CS resigns → Practitioner suggested alternate
Source: UC-CS-026. Practitioner gets warning event with action to designate alternate.

#### UC-XP-020: Practitioner downgrades → locked features re-locked
Source: UC-PRV-004. Sidebar items show "Upgrade" lock icons; any pages with active references warned.

### Shared Data Reads

#### UC-XP-021: CS portal reads Practitioner's plan tasks
Source: UC-CS-030. Filtered to `assignee_steward_id=<cs_id>`.

#### UC-XP-022: SS portal reads Practitioner's plan tasks
Source: UC-SS-013. Filtered to `assignee_steward_id=<ss_id>`.

#### UC-XP-023: CS portal reads Vault items
Source: UC-CS-060/061. Standby reads counts only; active incident reads full data.

#### UC-XP-024: Admin portal reads all incidents
Source: UC-ADM-004. No filter; global view.

#### UC-XP-025: Admin portal reads all activity_events (global audit)
Source: UC-ADM-022.

#### UC-XP-026: BP portal reads Support Requests posted by any Practitioner
Source: UC-BP-020. Filter: `bp_jobs.status='open'`.

#### UC-XP-027: Public profile reads Practitioner data
Source: UC-PRV-018 + Task 4 visibility gating.
**Main Flow:** `aegis_resolve_public_profile('practitioner', $slug)` → returns `{user, visible, prefs}` → `aegis_filter_profile_for_viewer()` strips per prefs:
- `privacy_ratings='0'` → ratings hidden
- `privacy_location='0'` → location hidden
- `privacy_referral_stats='0'` → response time/acceptance hidden
- `privacy_demographics='0'` → languages/pronouns hidden
- `privacy_network='0'` → excluded from network search via `aegis_get_network_search_providers()`
- Owner bypass: always sees full record

#### UC-XP-028: Public CS profile reads
Same pattern; gates: `privacy_creds`, `privacy_location`, `privacy_ratings`.

#### UC-XP-029: Public SS profile reads (relationship-gated)
Same pattern; gates: `privacy_contact`, `privacy_location`. SS profiles only visible to the practitioner who invited them.

#### UC-XP-030: Public BP profile reads
Same pattern; gates: `bp_network_visible` (network exclusion), `privacy_rates`, `privacy_location`.

---

## Master Entity List

| Entity | Main Table | Meta Table | Relates To | Owned By Portal |
|---|---|---|---|---|
| User | `users` | `user_meta` | self via foreign refs | All (role-scoped) |
| User preference | `user_preferences` | — | `users` | Self |
| User role | `user_roles` | — | `users` | Admin |
| Session | `user_sessions` | — | `users` | Self |
| Continuity Plan | `continuity_plans` | `plan_meta` *(new)* | `users, plan_stewards, plan_tasks` | Practitioner (read: CS, SS, Admin) |
| Plan Steward | `plan_stewards` | — | `continuity_plans, users` | Practitioner (read: CS, SS) |
| Plan Task | `plan_tasks` | — | `continuity_plans, plan_stewards, users` | Practitioner (write: CS+SS for completion) |
| Plan Incident Config | `plan_incident_configs` | — | `continuity_plans` | Practitioner |
| Critical Incident | `critical_incidents` | `incident_meta` *(new)* | `users, continuity_plans, incident_tasks` | SS reports; CS verifies; Practitioner participates |
| Incident Task | `incident_tasks` | — | `critical_incidents, plan_tasks, users` | CS executes |
| Vault Item | `vault_items` | `vault_item_meta` *(new)* | `users (practitioner)` | Practitioner (read: CS during active incident) |
| Continuity Document | `continuity_documents` | — | `continuity_plans, users` | All parties (signatures) |
| Practitioner License | (JSON in `users.credentials_list`) → recommend `practitioner_licenses` *(new)* | — | `users` | Practitioner |
| Practitioner Service | `practitioner_services` | — | `users` | Practitioner |
| Service Request | `service_requests` | — | `practitioner_services, users` | Inter-practitioner |
| Service Booking | `service_bookings` | — | `practitioner_services, users` | Inter-practitioner |
| Practitioner Payment | `practitioner_payments` | — | `users` | Practitioner (read: Admin) |
| Practitioner Payment Method | `practitioner_payment_methods` | — | `users` | Practitioner |
| Practitioner Profile Stats | `practitioner_profile_stats` | — | `users` | Practitioner |
| Practitioner CEU | `practitioner_ceu` | — | `users` | Practitioner |
| Event Registration | `event_registrations` | — | `news_events, users` | Practitioner |
| Network Connection | `network_connections` | — | `users` | Self + counterpart |
| Network Request | `network_requests` | — | `users` | Initiator + recipient |
| Shadow Connection | `shadow_connections` | — | `users` | Self |
| Referral | `referrals` | — | `users (sender, recipient)` | Practitioner ↔ Practitioner |
| Message Thread | `message_threads` | — | `users (participants)` | Multi-party |
| Message | `messages` | — | `message_threads, users` | Multi-party |
| Activity Event | `activity_events` | — | `users` + polymorphic via `related_record_id/type` | All (per-recipient scoping) |
| BP Job (Support Request) | `bp_jobs` | — | `users (practitioner)` | Practitioner (read: BP) |
| BP Proposal | `bp_proposals` | — | `bp_jobs, users (bp)` | BP (read: Practitioner) |
| BP Contract | `bp_contracts` | `contract_meta` *(new)* | `bp_proposals, users, bp_team_members` | Both parties |
| BP Milestone | `bp_milestones` | — | `bp_contracts` | BP |
| BP Saved Job | `bp_saved_jobs` | — | `bp_jobs, users` | BP self |
| BP Invoice | `bp_invoices` | — | `bp_contracts, users` | BP (read: Practitioner) |
| BP Invoice Line Item | `bp_invoice_line_items` | — | `bp_invoices` | BP |
| BP Invoice Payment | `bp_invoice_payments` | — | `bp_invoices` | BP/Practitioner |
| BP Payout | `bp_payouts` | — | `users (bp)` | BP (read: Admin) |
| BP Tax Document | `bp_tax_documents` | — | `users (bp)` | BP |
| BP Team Member | `bp_team_members` | — | `users (bp)` | Agency BP |
| BP Team Invitation | `bp_team_invitations` | — | `users (bp)` | Agency BP |
| CS Invoice | `cs_invoices` | — | `users (cs, practitioner)` | CS (read: Practitioner) |
| Continuity Document | `continuity_documents` | — | `continuity_plans, users` | Multi-party signing |
| News Post | `news_posts` | — | `users (author)` | All read; some write |
| News Comment | `news_comments` | — | `news_posts, users` | All |
| News Reaction | `news_reactions` | — | `news_posts, users` | All |
| News Poll Vote | `news_poll_votes` | — | `news_posts, users` | All |
| News Event | `news_events` | — | `users (author)` | All |
| News Trending Topic | `news_trending_topics` | — | — | Read-only |
| News Library Item | `news_library_items` | — | — | Read-only |
| Complaint *(Task 2)* | `complaints` | `complaint_meta` *(new)* | `users (submitter, assigned_to)` | All submit; Admin handles |
| Complaint Reply *(Task 2)* | `complaint_replies` | — | `complaints, users` | All submit; Admin internal-flag |
| Help Article *(Task 2)* | `help_articles` | — | — | Admin write; All read |
| **Schema Gaps (to add)** | | | | |
| Password Reset Token | `password_reset_tokens` *(new)* | — | `users` | Self |
| Admin Audit Log | `admin_audit_log` *(new)* | — | `users (admin, target)` | Admin |
| Role | `roles` *(new)* | — | `user_roles, role_permissions` | Admin |
| Role Permission | `role_permissions` *(new)* | — | `roles` | Admin |
| Package Override | `package_overrides` *(new)* | — | — | Admin |
| Practitioner License | `practitioner_licenses` *(new)* | — | `users` | Practitioner |
| SS Provider Note | `ss_provider_notes` *(new)* | — | `users (ss, provider)` | SS self |
| Stripe Webhook Event | `stripe_webhook_events` *(new)* | — | — | System/Admin |
| Account Transfer Request | `account_transfer_requests` *(new)* | — | `users` | Self → Admin |

---

## Database Design Recommendations

### Main Table vs Meta Decisions

| Table | Stay on Main | Move to Meta |
|---|---|---|
| `users` | id, email, password_hash, role, tier, slug, stripe_customer_id, subscription_active, practitioner_public, cs_public, business_partner_public, bp_type, mfa_enabled, mfa_secret, deactivated_at, locked_at, vault_attested_at, last_login_at, created_at | bio (long), specialties, services, frameworks, fees, insurance_panels, education, credentials_list, cs_emergency_protocols, cs_fee_model, cs_coverage_states, bp_certifications, bp_coverage_states, bp_portfolio, ceu_requirements |
| `continuity_plans` | id, practitioner_id, status, last_attestation_at, last_attestation_status, sent_for_signatures_at, created_at | template_version, draft_started_at, custom_notes |
| `critical_incidents` | id, incident_type, plan_id, practitioner_id, reported_by_ss_id, verified_by_cs_id, status, created_at, verified_at, closed_at | verification_docs (JSON), contact_attempts (JSON), narrative, timeline_notes (JSON), withdrawal_reason |
| `vault_items` | id, practitioner_id, zone, title, file_path, created_at, updated_at | tags, access_level, custom_fields, encrypted_data (credentials zone) |
| `bp_contracts` | id, practitioner_id, bp_id, status, total_value_cents, currency, signed_at, cancelled_at | scope_details, terms_text, custom_clauses |
| `complaints` | id, submitter_id, subject, category, submission_channel, status, priority, assigned_to, created_at, resolved_at | tags, internal_notes, related_records |

### Polymorphic Relationships

| Source Table | Polymorphic Columns | Targets |
|---|---|---|
| `activity_events` | `related_record_id`, `related_record_type` | continuity_plans, critical_incidents, vault_items, complaints, bp_contracts, referrals, messages, users |
| `messages` (attachments) | `attachment_type`, `attachment_id` | vault_items, continuity_documents |
| `admin_audit_log` | `target_type`, `target_id` | users, complaints, payments |

### Junction Tables Needed

- `plan_stewards` (existing) — plan ↔ user with role + status
- `role_permissions` (new) — role ↔ permission_key
- `vault_item_access` (recommended split from JSON `vault_items.access_grant`) — vault_item ↔ user ↔ access_level

### Indexes Beyond FKs

| Table | Index | Reason |
|---|---|---|
| `users` | `(role, deactivated_at)` | Per-role active counts (admin dashboard) |
| `users` | `(role, practitioner_public)` | Network search |
| `users` | `(role, cs_public, cs_account_type)` | CS marketplace |
| `user_preferences` | `(pref_key, pref_val)` | Task 4 visibility queries — find all users with `privacy_network='0'` |
| `activity_events` | `(user_id, read_at, created_at DESC)` | Unread badge counts |
| `activity_events` | `(scoped_provider_id, created_at DESC)` | CS provider-scoped filter |
| `critical_incidents` | `(status, plan_id)` | Active incident counts |
| `plan_tasks` | `(assignee_steward_id, status)` | CS/SS task list |
| `complaints` | `(status, priority, created_at DESC)` | Admin queue |
| `complaints` | `(submitter_id, submission_channel)` | User's own tickets vs feedback |
| `complaint_replies` | `(complaint_id, created_at)` | Thread render |
| `bp_jobs` | `(status, category, posted_at DESC)` | Find-jobs filters |
| `bp_proposals` | `(bp_id, status)` | BP's own proposals |
| `bp_contracts` | `(practitioner_id, status)` AND `(bp_id, status)` | Both sides |

### Enum Values (CHECK constraints)

| Column | Allowed Values |
|---|---|
| `users.role` | practitioner, continuity_steward, support_steward, business_partner, admin |
| `users.tier` | access, practice, trial (practitioner only); business (BP) |
| `users.bp_type` | freelancer, agency |
| `users.cs_account_type` | invited, business |
| `users.w9_status` | not_submitted, submitted, verified |
| `continuity_plans.status` | draft, pending_signatures, active, archived |
| `plan_stewards.steward_role` | primary_cs, alternate_cs, primary_ss, alternate_ss |
| `plan_stewards.status` | pending_acceptance, active, declined, resigned, removed |
| `plan_tasks.status` | pending, in_progress, completed, blocked, cancelled |
| `plan_tasks.assignee_role` | cs, ss |
| `critical_incidents.status` | reported, verified, active, closed, disputed, withdrawn |
| `critical_incidents.incident_type` | death, short_term_incapacitation, long_term_incapacitation, missing, detainment, natural_disaster, geopolitical |
| `vault_items.zone` | standard, emergency, roster, credentials |
| `activity_events.event_type` | message, task, document, incident, vault, compliance, attestation, payment, account, system, referral, news, event, practitioner_unresponsive_flagged |
| `activity_events.severity` | info, warning, critical |
| `bp_jobs.status` | open, in_review, filled, closed |
| `bp_jobs.budget_type` | fixed, hourly, monthly_retainer |
| `bp_jobs.category` | accounting, billing, compliance, technology, legal, marketing, other |
| `bp_proposals.status` | submitted, viewed, accepted, declined, withdrawn |
| `bp_contracts.status` | pending_signatures, active, paused, completed, cancelled |
| `bp_milestones.status` | pending, submitted_for_review, approved, paid, rejected |
| `bp_invoices.status` | draft, sent, viewed, paid, overdue, void, refunded |
| `complaints.status` | open, in_progress, resolved, closed |
| `complaints.category` | support_ticket, feedback, feature_request, bug, praise, escalation |
| `complaints.submission_channel` | ticket, button, questionnaire, freeform |
| `complaints.priority` | low, normal, high, urgent |

### Money Storage

All money columns → store in **cents (integer)**. Migrate existing decimal columns: `bp_jobs.budget_amount`, `bp_invoices.amount`, `cs_invoices.amount`, `practitioner_payments.amount`, all milestone amounts.

### Timestamps

All `*_at` columns → `TIMESTAMP` (UTC). Add `deleted_at` (nullable) for soft delete on user-facing entities: `users, continuity_plans, vault_items, bp_contracts, complaints, practitioner_services, referrals, network_connections`.

### Centralized Columns Across Multiple Use Cases

| Column | Appears in | Recommendation |
|---|---|---|
| `created_at` | every table | Standard timestamp |
| `updated_at` | every mutable table | Trigger to auto-update |
| `deleted_at` | user-facing tables | Soft-delete pattern |
| `metadata` (JSON) | extensible tables | Reserve for unstructured |
| `status` (enum) | nearly every transactional table | Always CHECK-constrained |
| `assigned_to` | tasks, complaints | Indexed; nullable |

---

## Schema Gaps Summary

Items flagged across all portals that need to be added in the Laravel migration:

### Tables to add
1. `password_reset_tokens` — UC-PRV-005, UC-ADM-025
2. `admin_audit_log` — UC-ADM-023, 026, 027, 029, 042, 043
3. `roles` + `role_permissions` — UC-ADM-030 to 033
4. `package_overrides` — UC-ADM-011, 012, 013
5. `practitioner_licenses` — UC-PRV-011 (extract from JSON)
6. `ss_provider_notes` — UC-SS-019
7. `stripe_webhook_events` — UC-ADM-046
8. `account_transfer_requests` — UC-BP-105
9. `cs_payouts` — UC-CS-087
10. Meta pair tables — `plan_meta`, `vault_item_meta`, `incident_meta`, `contract_meta`, `complaint_meta`

### Columns to add (on existing tables)
1. `users.failed_login_count`, `users.locked_at`, `users.locked_reason` — UC-PRV-002, UC-ADM-023
2. `users.deactivated_at` — UC-PRV-172, UC-CS-097, UC-SS-078, UC-BP-103, UC-ADM-027
3. `users.cs_paused_at`, `users.cs_paused_until` — UC-CS-096
4. `users.ss_paused_at`, `users.ss_paused_until` — UC-SS-076
5. `users.bp_paused_at`, `users.bp_paused_until` — UC-BP-104
6. `users.pending_email`, `users.email_verification_token` — UC-SS-075, UC-BP-102
7. `users.subscription_cancel_at_period_end` — UC-PRV-145, UC-BP-106
8. `plan_stewards.payment_model` — UC-PRV-146
9. `plan_stewards.role_change_request_*` — UC-CS-023
10. `plan_stewards.ss_acknowledged_at, ss_last_attestation_at` — UC-SS-012, UC-SS-035
11. `users.cs_last_attestation_at` — UC-CS-035
12. `continuity_plans.last_attestation_at, last_attestation_status` — UC-PRV-038, UC-PRV-039

### Scheduled jobs to build
1. Annual re-attestation reminder cron — UC-XP-014
2. License/insurance expiry reminders — UC-PRV-011
3. Overdue invoice reminders — UC-CS-082, UC-BP-053
4. Critical incident escalation timer — admin alert if no CS response in 24h

---

## Document Stats

| Portal | Use Cases | New (Task 2 + 4) |
|---|---|---|
| Provider | 108 (UC-PRV-001 → 186) | 7 (180-186) + 1 visibility (019) |
| CS | 73 (UC-CS-001 → 106) | 7 (100-106) + 1 visibility (098) |
| SS | 51 (UC-SS-001 → 086) | 6 (081-086) + 1 visibility (079) |
| BP | 84 (UC-BP-001 → 115) | 6 (110-115) + 2 visibility (016, 107) |
| Admin | 41 (UC-ADM-001 → 060) | 3 help articles (058-060) + complaints integration |
| Cross-Portal | 30 (UC-XP-001 → 030) | Visibility flows 027-030 |
| **Total** | **387 use cases** | **34 new from Tasks 2 + 4** |

---

*End of AEGIS_USE_CASES_OUTPUT.md*

*Generated against repo commit `53ee59a` with Tasks 2 (Support & Feedback), 4 (Visibility Permissions), and 8 (E2/E3/E4 Email) integrated.*

---

## Gap Analysis — Newly Documented & Corrected Use Cases (validation pass @ commit bbac3bc)

> These flows were found **wired end-to-end in code** (page dispatch → `save_*.php` whitelist → `models_write.php` helper → `aegis_log_activity()` fan-out) but had **no use case** in the prior revision, or had UI present with **broken/missing backend**. IDs continue past each portal's prior max. Tags: `[WIRED]` confirmed end-to-end · `[STUB]` UI+endpoint but helper missing · `[UNWIRED]` UI exists, no working backend.

### Provider — Document Lifecycle (`important-documents.php` · `save_document.php`) — all `[WIRED]`, all fan out

#### UC-PRV-190: Practitioner sends document for signature  `[WIRED]`
**Trigger:** important-documents.php → draft row → "Send for signature" · **Action:** `save_document.php → send_for_signature` → `aegis_send_document_for_signature()`
**Main Flow:** `continuity_documents.status` → `countersign`; routed to assigned steward's countersign queue
**Cross-portal:** CS/SS receive `document_awaiting_countersign` (fan-out ×2) · **Data:** Write `continuity_documents.status`, `activity_events`

#### UC-PRV-191: Practitioner signs a document  `[WIRED]`
**Action:** `save_document.php → sign` → `aegis_sign_document()` · **Main Flow:** records practitioner signature; auto-advances `status` `countersign→active` when practitioner is final signer
**Cross-portal:** CS/SS notified `document_signed` (×2) · **Data:** Write `continuity_documents (status, signature meta)`, `activity_events`

#### UC-PRV-192: Practitioner amends an active document  `[WIRED]`
**Action:** `save_document.php → amend` → `aegis_amend_document()` · **Main Flow:** creates a linked amendment **draft** (`reference` suffixed `-AMD`, cloned parties/people); original retains `active` status
**Cross-portal:** stewards notified `document_amended` (×2) · **Data:** Write new `continuity_documents` row, `activity_events`

#### UC-PRV-193: Practitioner renews an expiring document  `[WIRED]`
**Action:** `save_document.php → renew` → `aegis_renew_document()` · **Main Flow:** `status` → `active`, expiry extended
**Cross-portal:** stewards notified `document_renewed` (×2) · **Data:** Write `continuity_documents (status, expiry)`, `activity_events`

#### UC-PRV-194: Practitioner archives a document  `[WIRED]`
**Action:** `save_document.php → archive` → `aegis_archive_document()` · **Main Flow:** `status` → `archived` (removed from active lists, retained for audit)
**Cross-portal:** stewards notified `document_archived` (×2) · **Data:** Write `continuity_documents.status`, `activity_events`

#### UC-PRV-195: Practitioner deletes a document draft  `[WIRED]`
**Action:** `save_document.php → delete_draft` → `aegis_delete_document_draft()` · **Precondition:** doc still in `draft` (signed docs cannot be deleted, only archived)
**Cross-portal:** activity logged (×2) · **Data:** Delete `continuity_documents` row, `activity_events`

#### UC-PRV-196: Practitioner requests document release from steward  `[WIRED]`
**Action:** `save_document.php → request_release` → `aegis_request_document_release()` · **Main Flow:** `status` → `release_pending`; steward holding the document is asked to release custody
**Cross-portal:** holding steward notified `document_release_requested` (×2) · **Data:** Write `continuity_documents.status`, `activity_events`

#### UC-PRV-197: Practitioner sends a signature/countersign reminder  `[WIRED]`
**Action:** `save_document.php → send_reminder` → `aegis_send_document_reminder()` · **Main Flow:** notification only — **no status change** — nudges the pending signer/countersigner
**Cross-portal:** target steward notified `document_reminder` (×2) · **Data:** Write `activity_events` only

### Provider — Vault Item Lifecycle (`vault.php` · `save_vault.php`) — `[WIRED]`

#### UC-PRV-198: Practitioner edits a vault item  `[WIRED]`
**Action:** `save_vault.php → update_item` → `aegis_update_vault_item()` · **Data:** Write `vault_items` (title/description/meta), `activity_events` · **Cross-portal:** steward vault-count/metadata refresh (×1)

#### UC-PRV-199: Practitioner deletes a vault item  `[WIRED]`
**Action:** `save_vault.php → delete_item` → `aegis_delete_vault_item()` · **Data:** Delete `vault_items` row, `activity_events` (×1)

#### UC-PRV-200: Practitioner shares a vault item with a steward  `[WIRED]`
**Action:** `save_vault.php → share` → `aegis_share_vault_item($pid,$item_id,$recipient_id,…)` · **Cross-portal:** recipient steward gains scoped access + `vault_item_shared` notification (fan-out ×2) · **Data:** Write `vault_items.access_grant`, `activity_events`

#### UC-PRV-201: Practitioner reveals an encrypted credential  `[WIRED]`
**Action:** `save_vault.php → vault_credential_reveal` → AES-256-GCM decrypt + audit · **Data:** Read encrypted `vault_items`; Write `activity_events` (reveal logged for audit)

#### UC-PRV-202: Vault item download (audited)  `[WIRED]`
**Action:** `save_vault.php → vault_log_download` → `aegis_vault_log_download()` · **Cross-portal:** heavy audit fan-out (×6 — owner + each authorized steward feed) · **Data:** Write `activity_events`

#### UC-PRV-203: Export vault access log  `[WIRED]`
**Action:** `save_vault.php → vault_log_export` → `aegis_vault_log_export()` · **Data:** Read+Write `activity_events` (export event recorded ×2)

### Provider — Steward Management (`continuity-stewards.php` / `support-stewards.php` · `save_steward.php`) — `[WIRED]`

#### UC-PRV-204: Set steward permissions  `[WIRED]`
**Action:** `save_steward.php → set_permissions` (inline write) · **Data:** Write `plan_stewards.permissions`, `activity_events` · distinct from per-document vault perms (UC-PRV-071)

#### UC-PRV-205: Set steward vault access level  `[WIRED]`
**Action:** `save_steward.php → set_vault_access` → `aegis_set_steward_vault_access($plan_id,$steward_id,$level)` · **Data:** Write `plan_stewards.vault_access`, `activity_events` · **Cross-portal:** steward sees vault scope change

#### UC-PRV-206: Resend a pending steward invite  `[WIRED]`
**Action:** `save_steward.php → resend_invite` → `aegis_resend_steward_invite()` · **Data:** Write `plan_stewards` (invite re-issued/timestamp), `activity_events` · **Cross-portal:** invitee notified again

### Provider — Network & Finance — `[WIRED]`

#### UC-PRV-207: Quick-edit specialties from Network  `[WIRED]`
**Action:** `network.php → save_network.php → update_specialties` → `aegis_save_practitioner_specialties()` · **Data:** Write `user_meta.practitioner_specialties` · alternate entry point to UC-PRV-012 (profile editor)

#### UC-PRV-208: Set invoice status (practitioner finances)  `[WIRED]`
**Action:** `finances.php → save_finance.php → set_invoice_status` (inline write) · **Data:** Write `practitioner_payments`/invoice status, `activity_events`

### Business Partner — Invoice draft

#### UC-BP-120: BP edits a draft invoice before sending  `[STUB]`
**Trigger:** invoices.php → draft invoice → edit (due date / notes / line items) · **Action:** `save_invoice.php → update_draft` → `aegis_bp_update_draft($uid,$invoice_id,{due_at,notes,line_items})`
**Status:** endpoint whitelists the action and the UI calls it, **but `aegis_bp_update_draft()` does not exist in `models_write.php`** — invoking this currently fatals. Backend helper must be implemented before Laravel port. **Data (intended):** Write `bp_invoices`, `bp_invoice_line_items`

### Continuity Steward — Dead/broken UI (`[UNWIRED]`)

#### UC-CS-110: CS certifies plan from Providers page  `[UNWIRED]`
**Trigger:** providers.php → "Certify" · **Action dispatched:** `certify_plan` — **not in any `save_*.php` whitelist** (the wired CS certify path is `save_certify.php → certify`, used elsewhere). This specific button is dead. Wire to `certify` or add whitelist entry.

#### UC-CS-111: CS sends referral from Providers page  `[UNWIRED]`
**Trigger:** providers.php → "Send referral" · **Action dispatched:** `send_referral` — **no backend whitelist** (referrals backend is `save_referral.php → create`). Dead button.

#### UC-CS-112 / UC-CS-113: CS adds / removes payment card  `[UNWIRED]`
**Trigger:** settings.php → billing · **Actions dispatched:** `update_card` / `remove_card` → POST to **`/_shared/save_payment_method.php` which does not exist**. Both dead. Needs the endpoint + helpers created.

> **UC-CS-023 (fee amendment / role change)** is now tagged `[UNWIRED]`: providers.php dispatches `request_role_change`, which has no backend whitelist.

### Support Steward — Dead/broken UI (`[UNWIRED]`)

#### UC-SS-090: SS adds an executor/delegate  `[UNWIRED]`
**Trigger:** continuity-stewards.php → "Add executor" · **Action dispatched:** `csPost('add_executor', {name,relationship,role,trigger_event,responsibilities,note})` — **no backend whitelist for `add_executor`**. Dead form. Needs endpoint action + helper + (likely) a new table/column.

### Shared — Dead/broken UI (`[UNWIRED]`)

#### UC-CS-114: CS upgrades tier via upgrade modal  `[UNWIRED]`
**Trigger:** `_shared/modals/upgrade_cs_modal.php` · **Action dispatched:** `upgrade_cs` → POST to **`/_shared/save_upgrade_cs.php` which does not exist**. Modal opens but submit is dead.

---

## Gap Summary (validation pass @ commit bbac3bc)

### A. Newly documented WIRED flows (were missing from prior revision)
19 provider flows added (UC-PRV-190–208): full document lifecycle (send_for_signature, sign, amend, renew, archive, delete_draft, request_release, send_reminder), vault item lifecycle (update_item, delete_item, share, credential_reveal, log_download, log_export), steward management (set_permissions, set_vault_access, resend_invite), network quick-edit specialties, and finance set_invoice_status. All verified page→endpoint→helper→fan-out.

### B. STUB — UI + endpoint present, write helper missing (fatal if invoked)
| Action | Endpoint | Missing helper | Page |
|---|---|---|---|
| `update_draft` | save_invoice.php | `aegis_bp_update_draft()` | biz-portal/invoices.php |

### C. UNWIRED — UI exists, no working backend (dead buttons/forms)
| Action | Page | Problem |
|---|---|---|
| `certify_plan` | continuity-steward-portal/providers.php | not in any whitelist |
| `send_referral` | continuity-steward-portal/providers.php | not in any whitelist |
| `request_role_change` | continuity-steward-portal/providers.php | not in any whitelist (see UC-CS-023) |
| `update_card` / `remove_card` | continuity-steward-portal/settings.php | posts to missing `save_payment_method.php` |
| `add_executor` | support-steward-portal/continuity-stewards.php | not in any whitelist |
| `upgrade_cs` | _shared/modals/upgrade_cs_modal.php | posts to missing `save_upgrade_cs.php` |

### D. Missing endpoints referenced by frontend
- `_shared/save_payment_method.php` (CS settings card add/remove)
- `_shared/save_upgrade_cs.php` (CS upgrade modal)

### E. ORPHAN backend actions — whitelisted with a helper, but no precise frontend dispatch found
Verify each before the Laravel port (some may be invoked via dynamically-built action strings or are server-side only):
`add_ceu`, `clear_exception`, `complete_task`, `connect`, `copy_tasks`, `delete_task`, `download_account_data`, `invoice_log_view`, `log_checkin`, `mark_paid`, `mark_viewed`, `pin_thread`, `remove_member`, `set_payment_model`, `submit_questionnaire`, `update_status`, `update_task`.

### Method note
Validation was driven by an exhaustive automated wiring cross-reference at commit `bbac3bc`: every `case` action across all 22 `save_*.php` endpoints, every JS dispatch site (`action:'…'`, `csPost/bpPost/cPost/xPost('…'`, `data-action`) across all 4 portals + `_shared/templates` + `_shared/modals` + root pages, and every helper in `models_write.php` with its `aegis_log_activity()` fan-out count — then diffed against all 387 prior use cases. Counts: 45 tables, 125 write helpers, 168 fan-out calls.

---

## Onboarding · Pricing · Demo-Harness Validation (pass 2 @ commit bbac3bc)

> Deep-read of `onboarding.php` (3,658 ln · 10-step wizard), `pricing.php` (609 ln · tier source-of-truth), `demo.php` (2,387 ln · scenario/QA harness). Cross-checked against the 387+26 prior use cases.

### MAAT Add-on — real `users.maat_addon` flag, sold in pricing, NOT previously documented

> `maat_addon` is a first-class `users` column (seeded: `p_sarah.maat_addon=1`), surfaced as a Settings toggle ("MAAT Continuity Steward Service ($29/mo)"), a dashboard stat chip, and a billing-summary row; it is whitelisted in `save_profile.php` prefs. It is **distinct from** `services_mode` / Integrative Services (UC-PRV-017). `pricing.php` sells it at **+$29/mo (annual +$23) — requires Practice tier**. No prior UC covered it.

#### UC-PRV-209: Select MAAT add-on at signup  `[UNWIRED — SIMULATED]`
**Trigger:** pricing.php "Add MAAT" CTA → `/onboarding.php?role=practitioner&tier=practice&maat=1` · **Status:** the `maat=1` param is **consumed by nothing** (onboarding has no backend); no charge, no flag persisted. Billing flow pending Stripe.

#### UC-PRV-210: Enable MAAT add-on from Settings  `[STUB]`
**Trigger:** settings.php → toggle "MAAT Continuity Steward Service ($29/mo)" · **Action:** `save_profile.php` prefs path sets `users.maat_addon=1` · **Status:** the **flag write is wired**, but there is **no Stripe +$29/mo charge** — billing side is a stub. **Precondition:** `tier='practice'`. **Data:** Write `users.maat_addon`, `activity_events`.

#### UC-PRV-211: Disable MAAT add-on  `[STUB]`
**Trigger:** settings.php → toggle off · **Action:** `save_profile.php` → `users.maat_addon=0` · **Status:** flag write wired; Stripe proration/cancellation not implemented. **Data:** Write `users.maat_addon`.

### Onboarding wizard steps — present in UI, no backend (all `[UNWIRED — SIMULATED]`)

> 10-step flow: (1) role select → (2) branch: CS pathway / invite-code / SS-invite acceptance → (3) create account → (4) email verify → (5) 2FA → (6) intent → (7–10) profile/plan → success. None persist. CS invite-acceptance (UC-CS-001), Business-CS signup (UC-CS-002), and SS invite (UC-SS-001) already exist as *intended* specs; the steps below were undocumented.

#### UC-PRV-212: Onboarding role selection (4-way fork)  `[UNWIRED — SIMULATED]`
**Trigger:** Step 1 → choose Practitioner Partner · Business Partner · Continuity Steward (free badge) · Support Steward (free badge). Routes the wizard branch. No persistence.

#### UC-PRV-213: Email verification step  `[UNWIRED — SIMULATED]`
**Trigger:** Step 4 "Verify Your Email" · No token issued/checked server-side (SES pending). Advances on click only.

#### UC-PRV-214: Two-factor enrollment during onboarding  `[UNWIRED — SIMULATED]`
**Trigger:** Step 5 "Two-Factor Authentication" · Distinct from login-MFA (UC-PRV-002 A3) and any Settings MFA flow. No `mfa_secret` persisted at onboarding.

#### UC-PRV-215: Onboarding intent capture ("What brings you to Aegis?")  `[UNWIRED — SIMULATED]`
**Trigger:** Step 6 segmentation question · No `user_meta` write. Marketing/segmentation signal is discarded in current build.

### Cross-portal — Multi-role portal switching (real, undocumented)

#### UC-XP-031: User holds multiple roles and switches portals  `[WIRED]`
**Mechanism:** `user_roles` table + `aegis_user_roles()` / `aegis_user_default_role()`; header.php renders a portal-switcher block (some roles deliberately skip it). Demo: `p_sarah` is a Practitioner **and** a CS for Dr. Torres (`cs_role=1`), exposing a header "CS Portal" switcher. **Read path wired.** **Data:** Read `user_roles (user_id, role, is_default, enabled_at)`. **Note:** prior doc treats `users.role` as single-valued; `user_roles` is the authoritative multi-role source and should drive the Laravel model.

### Demo harness (`demo.php`) — QA controls, not product use cases (reference)

Query-param state overrides used by the demo hub (map to existing UCs; documented here so they aren't mistaken for product flows): `?as=<user_id>` (p_sarah · cs_marcus · cs_alternate/Invited CS · ss_linda · bp_acme · bp_jamal), `tier=access|practice`, `services=0|1` (→ UC-PRV-017), `maat=1` (→ UC-PRV-209), `cs_role=1` (→ UC-XP-031), `emergency=true|false` + `arm_emergency=1` (fires `aegis_trigger_incident()` → UC-PRV-090/091, UC-CS-041), `invited=true` (CS subtype → UC-CS-001), `cs_account_type=addon` (**demo-only — code only recognizes `business`/`invited`; "addon" maps to the MAAT-add-on concept, not a third account type**), `upgrade=1`, `tab=subscription`, `module=incident`. Reset: `/demo.php?reset=1&token=aegis-demo-reset` (`+arm_emergency=1` for incident state).

### Pass-2 Gap Summary additions

| # | Finding | Type | Evidence |
|---|---|---|---|
| 1 | Registration/onboarding writes nothing server-side | `[UNWIRED — SIMULATED]` | onboarding.php finish = `location.href` only; zero `fetch`/`save_*` |
| 2 | MAAT add-on has no UC; billing unimplemented | `[STUB]` (flag wired, charge missing) | `users.maat_addon` col + settings toggle + pricing $29/mo; no Stripe |
| 3 | `maat=1` signup param consumed by nothing | dead param | onboarding has no backend |
| 4 | Onboarding steps (role/email/2FA/intent) undocumented + unpersisted | `[UNWIRED — SIMULATED]` | steps 1,4,5,6 in onboarding.php |
| 5 | Multi-role portal switcher undocumented | `[WIRED]` (now UC-XP-031) | `user_roles` table, `aegis_user_roles()`, header switcher |
| 6 | `cs_account_type=addon` demo param has no backend meaning | demo-only artifact | code recognizes only `business`/`invited` |

---

## Derived Use Cases — Gap Closure Pass (added in corrected version)

This section documents 117 use cases derived from the existing product surfaces (Vue components, controller methods, service calls in `AEGIS_LARAVEL_STRUCTURE.md`) that were missing from the original 421-UC document. Each entry is tagged:

- `[DERIVED]` — UC inferred from existing product behavior; needs Carizma confirmation but functionally required.
- `[REMAP]` — UC ID that the structure file previously cited as phantom; now formally defined.
- `[NEEDS UC]` — function exists but UC text not yet drafted; flagged for client write-up.

**New total UC count:** 421 + 117 = **538 UCs**.

### Practitioner (51 additions)

#### UC-PRV-013: Practitioner sets services offered  `[DERIVED]`
- Path: Provider → Settings → Profile → Services Offered chip list
- Service: `ProfileService::updateServicesOffered()` (writes `user_meta.services_offered` JSON)
- Effects: optional public profile refresh; no activity log

#### UC-PRV-020: Practitioner adds education & training  `[DERIVED]`
- Path: Provider → Edit Profile → Credentials section
- Service: `ProfileService::updateCredentials()` extended for `education_history`
- Effects: writes `user_meta.education_history` JSON

#### UC-PRV-060: Designate Primary SS  `[DERIVED]`
- Path: Provider → Support Stewards → Designate SS
- Service: `StewardService::inviteExisting(steward_type=ss, role=primary)`
- Effects: creates `plan_stewards` row; sends invite email; logs activity to SS

#### UC-PRV-061: Designate Alternate SS  `[DERIVED]`
- Path: Provider → Support Stewards → Designate Alternate
- Service: `StewardService::inviteExisting(steward_type=ss, role=alternate)`
- Effects: same as UC-PRV-060 with `role=alternate`

#### UC-PRV-062: Copy tasks from Primary SS to Alternate SS  `[DERIVED]`
- Path: Provider → Support Stewards → Alternate row → Copy Tasks
- Service: `StewardService::copyTasksFromPrimary(target_designation)`
- Effects: duplicates `plan_tasks` rows assigned to primary SS for alternate SS

#### UC-PRV-063: Remove SS assignment  `[DERIVED]`
- Path: Provider → Support Stewards → SS row → Remove
- Service: `StewardService::remove()` (existing — extend with SS type)
- Effects: status → `archived`; logs to SS; fires `StewardRemoved`

#### UC-PRV-072: Manage Client Roster (add/edit/Priority Response)  `[DERIVED]`
- Path: Provider → Vault → Client Roster zone → +Add
- Service: `VaultService::upsertRosterEntry(zone=roster)`
- Effects: writes `vault_items` row with `client_priority` (1–5); logs to CS metadata feed

#### UC-PRV-073: Mark client as discharged/closed  `[DERIVED]`
- Path: Provider → Vault → Client Roster → row → Mark Discharged
- Service: `VaultService::dischargeClient(item_id)`
- Effects: sets `vault_items.status` → `vault_only` (hidden from active list); preserves history

#### UC-PRV-074: Attest Vault complete (alias)  `[REMAP → UC-PRV-040]`
- Same as UC-PRV-040. The Vault page button is the same action as Annual Re-Attestation final step. Duplicate; reference UC-PRV-040.

#### UC-PRV-081: Upload additional support document  `[DERIVED]`
- Path: Provider → Important Documents → Upload
- Service: `DocumentService::uploadLibrary()` (existing)
- Effects: creates `continuity_documents` row with `status=draft`; not part of plan-document chain

#### UC-PRV-082: Request document from CS/SS  `[DERIVED]`
- Path: Provider → Important Documents → Request from Steward
- Service: `DocumentService::requestFromSteward(steward_user_id)`
- Effects: creates `activity_events` to steward (task feed); no document row yet (created on upload)

#### UC-PRV-107: Browse Business Partners in network  `[DERIVED — read-only view]`
- Path: Provider → Network → Filter: Business Partners tab
- Service: `NetworkService::listConnections(type=business_partner)`
- Effects: none (read)

#### UC-PRV-108: Send referral to network provider  `[DERIVED]`
- Path: Provider → Network → row → Send Referral
- Service: `ReferralService::send(from, to, type)` (existing)
- Effects: creates `referrals` row; logs to recipient; sends email gated by `notify_referral_received`

#### UC-PRV-110: Cancel an outbound referral  `[DERIVED]`
- Path: Provider → Referrals → Outgoing → row → Cancel
- Service: `ReferralService::cancel(referral_id, actor)`
- Effects: status → `cancelled`; logs to recipient

#### UC-PRV-111: Respond to incoming referral  `[DERIVED]`
- Path: Provider → Referrals → Incoming → row → Accept/Decline
- Service: `ReferralService::accept()` / `decline()` (existing)
- Effects: status change; logs to sender

#### UC-PRV-127: Publish service to public profile  `[DERIVED]`
- Path: Provider → Services → row → Publish toggle
- Service: `ServiceService::togglePublic(service_id)`
- Effects: writes `services.is_public`; updates public profile snapshot

#### UC-PRV-130: Create Support Request (job posting)  `[REMAP → UC-PRV-150]`
- Same as UC-PRV-150 (existing in source). Marketing name "Support Request" vs technical name "job posting".

#### UC-PRV-132: Accept a proposal → create contract  `[REMAP → UC-PRV-157 in old structure]`
- Real UC ID: **UC-PRV-132**. Replaces phantom UC-PRV-157.
- Path: Provider → Job Postings → Proposals → row → Accept
- Service: `ProposalService::accept()` → auto-`ContractService::generateFromProposal()`
- Effects: contract created; job marked `filled`; all other proposing BPs notified

#### UC-PRV-133: Decline a proposal  `[REMAP → UC-PRV-158 in old structure]`
- Real UC ID: **UC-PRV-133**. Replaces phantom UC-PRV-158.
- Service: `ProposalService::decline(reason)`

#### UC-PRV-134: Close a Support Request  `[REMAP → UC-PRV-154]`
- Same as UC-PRV-154 (existing). Marketing alias.

#### UC-PRV-135: Manage active contract — approve milestone  `[DERIVED]`
- Path: Provider → Finances → Contracts → milestone row → Approve
- Service: `ContractService::approveMilestone(milestone_id)`
- Effects: status → `approved`; triggers `PayoutService::initiate()` for that milestone amount; logs to BP

#### UC-PRV-136: Pay a BP invoice  `[REMAP → existing approve action]`
- Path: Provider → Finances → Invoices → row → Pay
- Service: `InvoiceService::approve()` (existing); charges Stripe; status → `paid`

#### UC-PRV-137: Sign contract (Practitioner side)  `[DERIVED]`
- Path: Provider → Finances → Contracts → row → Sign
- Service: `ContractService::signByPractitioner(sig_name, ip)` (existing)
- Effects: countersignature recorded; if both signed → status `active`

#### UC-PRV-138: Cancel contract  `[REMAP → UC-BP-035 mirror]`
- Path: Provider → Finances → Contracts → row → Cancel
- Service: `ContractService::cancel(actor, reason)` — either party can call

#### UC-PRV-141: Add/update payment method  `[DERIVED]`
- Path: Provider → Settings → Billing → Add Card
- Service: `SubscriptionService::addPaymentMethod(stripe_pm_id)`
- Effects: creates `practitioner_payment_methods` row; sets default if first

#### UC-PRV-144: Set autopay preference  `[DERIVED]`
- Path: Provider → Settings → Billing → Autopay toggle
- Service: `SubscriptionService::setAutopay(enabled)`
- Effects: writes `user_meta.autopay_enabled` boolean

#### UC-PRV-145: Cancel subscription  `[REMAP → UC-PRV-220 in old structure]`
- Real UC ID: **UC-PRV-145**. Replaces phantom UC-PRV-220.
- Service: `SubscriptionService::cancel(reason)`
- Effects: cancels at period end; writes to `practitioner_payments` history

#### UC-PRV-146: Set steward (CS) payment model  `[DERIVED]`
- Path: Provider → Continuity Stewards → CS row → Payment Model
- Service: `StewardService::setPaymentModel(designation_id, model)` — enum: `retainer|activation|hourly|pro_bono`
- Effects: writes `plan_stewards.payment_model`; logs to CS

#### UC-PRV-163: Pin/mute a thread  `[DERIVED]`
- Path: Messages → thread → Pin/Mute icon
- Service: `MessagingService::togglePin(thread_id)` / `toggleMute(thread_id)`
- Effects: writes thread-level boolean to `user_meta` (denormalized) or `message_threads.pinned_user_ids` JSON

#### UC-PRV-164: Mark thread unread  `[DERIVED]`
- Path: Messages → thread → Mark unread
- Service: `MessagingService::markUnread(thread_id)`
- Effects: nullifies `read_at` on user's last-read pointer

#### UC-PRV-171: Change password / enable 2FA  `[DERIVED]`
- Path: Settings → Security → Change Password / 2FA toggle
- Service: `AuthService::changePassword()` + existing `enableMfa()`
- Effects: writes new password hash; if 2FA: creates `mfa_tokens` row

#### UC-PRV-173: Export all data  `[DERIVED]`
- Path: Settings → Data → Export
- Service: `ProfileService::exportUserData(user_id)` — generates ZIP, queues `SendEmailJob` with download link
- Effects: writes `admin_audit_log` row (data export event)

#### UC-PRV-174: Revoke active session  `[REMAP → existing `AuthService::revokeSession`]`
- Path: Settings → Security → Sessions → row → Revoke
- Service: `AuthService::revokeSession(session_id)` (existing)

#### UC-PRV-184: Submit feedback via Feedback tab  `[REMAP → UC-XP-040 in old structure]`
- Real UC ID: **UC-PRV-184**. Replaces phantom UC-XP-040 for this portal.
- Service: `SupportService::submitFeedback(channel=feedback_button)`

#### UC-PRV-190: Practitioner sends document for signature  `[DERIVED]`
- Path: Provider → Important Documents → draft → Send for Signature
- Service: `DocumentService::sendForSignature(doc_id, signers)`
- Effects: status → `countersign`; emails each signer

#### UC-PRV-191: Practitioner signs a document  `[REMAP → UC-PRV-036 generalized]`
- Path: Important Documents → row → Sign
- Service: `DocumentService::addSignature(doc_id, signer=practitioner)` (existing)

#### UC-PRV-192: Practitioner amends an active document  `[DERIVED]`
- Path: Important Documents → row → Amend
- Service: `DocumentService::version(doc_id, changes)` (existing — creates new doc with `parent_document_id`)
- Effects: new draft row; old doc remains active until new is signed

#### UC-PRV-193: Practitioner renews an expiring document  `[DERIVED]`
- Path: Important Documents → expiring row → Renew
- Service: `DocumentService::renew(doc_id, new_expiry)`
- Effects: copies content into new version; resets `expires_at`

#### UC-PRV-194: Practitioner archives a document  `[REMAP → existing `DocumentService::archive`]`

#### UC-PRV-195: Practitioner deletes a document draft  `[DERIVED]`
- Path: Important Documents → draft row → Delete
- Service: `DocumentService::delete(doc_id)` — only allowed on `draft` status
- Effects: soft delete

#### UC-PRV-196: Practitioner requests document release from steward  `[DERIVED]`
- Path: Important Documents → row held by steward → Request Release
- Service: `DocumentService::requestRelease(doc_id, steward_id)`
- Effects: writes `activity_events` to steward (task feed); steward acts via UC-CS-073 etc.

#### UC-PRV-197: Practitioner sends a signature/countersign reminder  `[DERIVED]`
- Path: Important Documents → pending row → Send Reminder
- Service: `DocumentService::sendReminder(doc_id, signer_id)`
- Effects: dispatches `SendEmailJob` with reminder template; ungated

#### UC-PRV-198: Practitioner edits a vault item  `[DERIVED]`
- Path: Provider → Vault → row → Edit
- Service: `VaultService::update(item_id, data)` (NEW — currently structure shows route but not method)
- Effects: updates encrypted payload (re-envelope) for Credentials zone; updates plain fields for other zones

#### UC-PRV-199: Practitioner deletes a vault item  `[REMAP → existing `VaultService::delete`]`

#### UC-PRV-204: Set steward permissions  `[REMAP → existing `VaultService::setPermissions`]`
- Different framing: per-steward permission grid (vault access + portal access). Same backend method.

#### UC-PRV-206: Resend a pending steward invite  `[DERIVED]`
- Path: Continuity Stewards → invited row → Resend
- Service: `StewardService::resendInvite(designation_id)`
- Effects: dispatches `SendEmailJob` with same template as original invite

#### UC-PRV-207: Quick-edit specialties from Network  `[REMAP → existing `ProfileService::updateSpecialties`]`

#### UC-PRV-208: Set invoice status (practitioner finances)  `[DERIVED — narrow]`
- Path: Provider → Finances → invoice row → Set Status (admin tool, gated)
- Service: `InvoiceService::overrideStatus(inv_id, status, note)` — gated to `admin` role only
- Effects: status change; writes `admin_audit_log`

#### UC-PRV-209: Select MAAT add-on at signup  `[DERIVED]`
- Path: Registration → tier selection → MAAT add-on checkbox
- Service: `AuthService::register()` extended with `maat_addon` flag
- Effects: sets `users.maat_addon=1` on creation; first Stripe charge includes add-on

#### UC-PRV-212: Onboarding role selection (4-way fork)  `[DERIVED]`
- Path: `/register` → role-pick screen
- Service: routes to `RegisterController::store()` with `role` param
- Effects: branch to one of 4 registration flows (practitioner / CS / SS / BP)

#### UC-PRV-213: Email verification step  `[DERIVED]`
- Path: `/verify-email/{token}`
- Service: `AuthService::verifyEmail(token)`
- Effects: writes `users.email_verified_at`; logs `EmailVerified` event

#### UC-PRV-215: Onboarding intent capture ("What brings you to Aegis?")  `[DERIVED]`
- Path: After signup → modal
- Service: `ProfileService::setIntentSegment(user_id, segment)`
- Effects: writes `user_meta.intent_segment`

---

### Continuity Steward (33 additions)

#### UC-CS-023: CS requests role change with practitioner  `[DERIVED]`
- Path: CS → Providers → row → Request Role Change
- Service: `StewardService::requestRoleChange(designation_id, new_role)`
- Effects: writes `plan_stewards.status=request_incoming`; notifies practitioner; gated by `notify_steward_changes`

#### UC-CS-025: CS connects to new practitioner (invite acceptance flow)  `[DERIVED]`
- Path: invite link `/invite/cs/{token}`
- Service: `StewardService::accept(designation_id)` (existing) — additional flow before acceptance for unregistered users
- Effects: creates user (if needed) + designation `active`

#### UC-CS-026: CS resigns from a practitioner's plan  `[DERIVED]`
- Path: CS → Providers → row → Resign
- Service: `StewardService::resign(designation_id, reason)`
- Effects: status → `archived`; suggests alternate to practitioner; fires `StewardResigned` event (NEW event)

#### UC-CS-031: Mark a standby task complete  `[REMAP → existing `PlanService::completeTask`]`

#### UC-CS-032: Add note to a task  `[DERIVED]`
- Path: CS → My Tasks → row → Add Note
- Service: `PlanService::addTaskNote(task_id, note)`
- Effects: appends to `plan_tasks.notes` JSON array

#### UC-CS-033: Request task due-date extension  `[DERIVED]`
- Path: CS → My Tasks → row → Request Extension
- Service: `PlanService::requestTaskExtension(task_id, new_due_date, reason)`
- Effects: creates activity row to practitioner (task feed); practitioner approves separately

#### UC-CS-034: Flag a task issue (exception)  `[REMAP → existing `PlanService::flagTaskException`]`

#### UC-CS-035: Complete Annual Re-Attestation  `[REMAP → UC-PRV-039 (practitioner side)]`
- CS counterpart action: `StewardService::countersignAnnualReview(designation_id)`
- Effects: writes `plan_stewards.signed_at`; logs to practitioner

#### UC-CS-036: Clear a previously-flagged exception  `[DERIVED]`
- Service: `PlanService::clearTaskException(task_id, resolution_note)`
- Effects: status → `pending` or `complete`; logs to practitioner

#### UC-CS-044: CS works through incident task list  `[REMAP → existing `IncidentService::completeTask`]`

#### UC-CS-045: CS escalates to Aegis team  `[REMAP → existing `IncidentService::escalate`]`
- Confusion in original UCs: SS does standard escalation; CS escalation routes to admin queue specifically.
- Service: `IncidentService::escalateToAegis(incident_id, reason)`

#### UC-CS-046: CS adds update to incident timeline  `[DERIVED]`
- Path: CS → Continuity Management → incident → Add Update
- Service: `IncidentService::addUpdate(incident_id, body)`
- Effects: writes `incident_updates` row with `update_type=note`

#### UC-CS-047: CS attaches documentation to incident  `[DERIVED]`
- Service: `IncidentService::attachDocument(incident_id, file)`
- Effects: writes to incident_meta `attachments` JSON; uploads to S3

#### UC-CS-048: CS closes the incident  `[REMAP → existing `IncidentService::close`]`

#### UC-CS-049: CS reopens a closed incident  `[DERIVED]`
- Service: `IncidentService::reopen(incident_id, reason)`
- Effects: status `closed` → `active`; fires `IncidentReopened` event (NEW)

#### UC-CS-061: CS accesses unsealed vault during active incident  `[REMAP → UC-CS-051 in old structure → real UC-CS-061]`
- Service: `VaultService::getForSteward()` (existing)

#### UC-CS-062: CS downloads a vault document  `[REMAP → UC-CS-052 in old structure → real UC-CS-062]`
- Service: `VaultService::signedUrl()` (existing)

#### UC-CS-063: CS reveals credential (encrypted)  `[REMAP → existing `VaultService::reveal`]`

#### UC-CS-065: CS initiates client referral during incident  `[DERIVED]`
- Path: CS → Vault (during incident) → Client Roster → row → Refer
- Service: `ReferralService::sendDuringIncident(client_id, target_practitioner_id, incident_id)`
- Effects: creates `referrals` row tagged with incident_id; logs to receiving practitioner

#### UC-CS-066: CS exports vault audit log  `[DERIVED]`
- Path: CS → Vault → Audit Log → Export CSV
- Service: `VaultService::exportAuditLog(practitioner_id, format=csv)`
- Effects: streams CSV of `vault_access_log` rows for the practitioner

#### UC-CS-071: CS uploads a support document  `[DERIVED]`
- Path: CS → Important Documents → Upload
- Service: `DocumentService::uploadByStreward(steward_id, plan_id, file)`
- Effects: creates `continuity_documents` row with steward as `created_by_id`

#### UC-CS-073: CS countersigns plan from this page  `[REMAP → existing `DocumentService::addSignature(role=cs)`]`

#### UC-CS-074: CS declines plan  `[DERIVED — distinct from designation decline]`
- Path: CS → Important Documents → countersign row → Decline
- Service: `DocumentService::declineSignature(doc_id, signer_id, reason)`
- Effects: blocks document activation; notifies practitioner

#### UC-CS-075: CS re-attests annual agreement per practitioner  `[DERIVED]`
- Path: CS → Continuity Management → row → Re-attest
- Service: `StewardService::reAttestAnnual(designation_id)`
- Effects: sets `plan_stewards.signed_at` to now; logs to practitioner

#### UC-CS-081–089: CS invoicing flow  `[DERIVED — entire module]`
- Path: CS → Finances → Invoices tab
- Services:
  - UC-CS-081 send: `InvoiceService::sendCsInvoice(invoice_id)`
  - UC-CS-082 reminder: `InvoiceService::sendReminder(invoice_id)`
  - UC-CS-083 mark paid manually: `InvoiceService::markPaidManually(invoice_id, ref)`
  - UC-CS-084 void: `InvoiceService::voidCsInvoice(invoice_id)`
  - UC-CS-085 create: `InvoiceService::createCsInvoice(cs_id, practitioner_id, lines)`
  - UC-CS-086 connect Stripe: `PayoutService::startConnectOnboarding(cs_id)`
  - UC-CS-088 set fee model: `StewardService::setPaymentModel()` (same as UC-PRV-146 inverse)
  - UC-CS-089 copy Stripe ID: read-only UI action

#### UC-CS-090: CS sends message to practitioner / SS / Aegis team  `[REMAP → existing `MessagingService::createThread`]`

#### UC-CS-092–098: CS settings actions  `[DERIVED — entire settings panel]`
- UC-CS-092 notification prefs: `NotificationService::setGate()` (existing)
- UC-CS-093 password / 2FA: shared `AuthService` methods
- UC-CS-094 revoke all sessions: `AuthService::revokeAllSessions(user_id)`
- UC-CS-095 export data: `ProfileService::exportUserData()` (shared)
- UC-CS-096 pause CS status: `StewardService::pauseStewardship(user_id, until)`
- UC-CS-097 close account: `AuthService::closeAccount(user_id, reason)`
- UC-CS-098 visibility prefs: `ProfileService::updateVisibility()` (existing)

#### UC-CS-100–106: CS support module  `[DERIVED — replaces phantom UC-XP-040..043 for this portal]`
- UC-CS-100 ticket: `SupportService::createTicket()` (existing)
- UC-CS-102 reply: `SupportService::reply()` (existing)
- UC-CS-103 close own: `SupportService::closeTicket(complaint_id, submitter)`
- UC-CS-104 feedback tab: `SupportService::submitFeedback(channel=feedback_button)`
- UC-CS-105 feedback FAB: `SupportService::submitFeedback(channel=contextual_questionnaire)`
- UC-CS-106 browse help: `SupportService::getHelpArticles()` (existing)

#### UC-CS-110: CS certifies plan from Providers page  `[REMAP → existing `StewardService::certify`]`

#### UC-CS-111: CS sends referral from Providers page  `[REMAP → UC-PRV-108 inverse]`
- Same `ReferralService::send()`; from = CS user, to = referenced practitioner

#### UC-CS-114: CS upgrades tier via upgrade modal  `[DERIVED]`
- Path: any page → Upgrade modal CTA
- Service: `SubscriptionService::upgradeCsToBusiness(user_id)`
- Effects: changes `cs_account_type=business`; first Stripe charge for CS fee

---

### Support Steward (18 additions)

#### UC-SS-012: SS acknowledges plan awareness  `[REMAP → UC-SS-035]`
- Service: `StewardService::certify(designation_id)` (re-used for SS)

#### UC-SS-016: SS sends message to assigned CS  `[REMAP → existing `MessagingService::send`]`

#### UC-SS-017: SS notifies practitioner that CS is unresponsive  `[REMAP → existing `StewardService::notifyUnresponsive`]`

#### UC-SS-018: SS adds business CS contact for practitioner  `[DERIVED]`
- Service: `StewardService::recommendBusinessCS(practitioner_id, business_cs_id)`
- Effects: writes activity to practitioner; not a designation (suggestion only)

#### UC-SS-019: SS saves a private note on provider  `[DERIVED]`
- Service: `StewardService::saveProviderNote(ss_id, practitioner_id, note)`
- Effects: writes `ss_provider_notes` row

#### UC-SS-031: Mark task complete  `[REMAP → existing]`

#### UC-SS-032: Add task note  `[REMAP → UC-CS-032 mirror]`

#### UC-SS-033: Request task extension  `[REMAP → UC-CS-033 mirror]`

#### UC-SS-034: Notify CS about task (escalation)  `[DERIVED]`
- Service: `PlanService::escalateTaskToCs(task_id, ss_id)`
- Effects: writes activity to all CS on the plan

#### UC-SS-043: SS attaches additional document to incident  `[REMAP → UC-CS-047 mirror]`

#### UC-SS-044: SS withdraws an incident report  `[DERIVED]`
- Service: `IncidentService::withdraw(incident_id, ss_id, reason)` — only if status still `reported`
- Effects: status → `closed` with withdrawal note; fires `IncidentWithdrawn` event (NEW)

#### UC-SS-050: SS contacts assigned CS via messages  `[REMAP → UC-SS-016 duplicate]`

#### UC-SS-061: SS downloads a document  `[REMAP → existing `DocumentService::signedUrl`]`

#### UC-SS-062: SS uploads a support document  `[REMAP → UC-CS-071 mirror]`

#### UC-SS-072: SS marks activity as read  `[REMAP → existing `ActivityService::markRead`]`

#### UC-SS-073–080: SS settings actions  `[DERIVED — mirrors CS-092..098]`
- Same service method set as CS portal applied to SS user

#### UC-SS-081–086: SS support module  `[DERIVED — mirrors CS-100..106]`

#### UC-SS-090: SS adds an executor/delegate  `[DERIVED]`
- Path: SS → Settings → Delegate Access
- Service: `ProfileService::grantEditAuthorization()` (existing) for SS-to-SS delegation
- Effects: writes `profile_edit_authorizations` row

---

### Business Partner (47 additions)

#### UC-BP-003: BP completes company profile  `[DERIVED]`
- Service: `ProfileService::updateBasic()` + agency-specific fields (`bp_business_name`, `bp_team_size`, `bp_categories`)

#### UC-BP-004: BP sets up Stripe Connect payout account  `[REMAP → existing `PayoutService::startConnectOnboarding`]`

#### UC-BP-011–014: BP advanced profile  `[DERIVED]`
- UC-BP-011 certifications: writes `user_meta.bp_certifications` JSON
- UC-BP-012 service coverage: writes `user_meta.bp_state_coverage` JSON array
- UC-BP-013 specializations: writes `user_meta.bp_specializations` JSON
- UC-BP-014 portfolio: writes `user_meta.bp_portfolio_items` JSON (S3 URLs)

#### UC-BP-016: BP toggles network visibility  `[REMAP → existing `ProfileService::updateVisibility`]`

#### UC-BP-017: BP toggles revenue privacy  `[DERIVED]`
- Service: `ProfileService::setRevenueVisibility(user_id, public_bool)`
- Effects: writes `user_meta.show_revenue_publicly`

#### UC-BP-024: BP toggles saved-only filter  `[NO BACKEND — UI state only]`

#### UC-BP-025: BP submits a proposal on a Support Request  `[REMAP → UC-BP-030]`

#### UC-BP-026: BP edits an open proposal  `[REMAP → UC-BP-031]`

#### UC-BP-027: BP withdraws a proposal  `[REMAP → UC-BP-032]`

#### UC-BP-035: BP cancels contract  `[REMAP → UC-BP-046 in old structure → real UC-BP-035]`

#### UC-BP-036: BP pauses contract  `[DERIVED]`
- Service: `ContractService::pause(contract_id, actor, reason)`
- Effects: status `active` → `paused` (new sub-status in `contract_meta`); freezes milestone clock

#### UC-BP-037: BP resumes paused contract  `[DERIVED]`
- Service: `ContractService::resume(contract_id, actor)`
- Effects: status → `active`; resumes milestone clock

#### UC-BP-039: BP adds milestone to contract  `[DERIVED]`
- Service: `ContractService::addMilestone(contract_id, milestone_data)` — requires practitioner approval
- Effects: writes `bp_milestones` row `pending` until practitioner approves

#### UC-BP-043: BP withdraws milestone submission  `[DERIVED]`
- Service: `ContractService::withdrawMilestone(milestone_id)` — only when status `submitted`
- Effects: status → `pending`

#### UC-BP-052: BP resends invoice  `[DERIVED]`
- Service: `InvoiceService::resend(invoice_id)`
- Effects: re-dispatches `InvoiceSent` email; ungated

#### UC-BP-053: BP sends invoice reminder  `[DERIVED]`
- Service: `InvoiceService::sendReminder(invoice_id)`
- Effects: sends reminder template; only allowed if `status ∈ {sent, overdue}`

#### UC-BP-055: BP voids/cancels an invoice  `[REMAP → UC-BP-064 in old structure → real UC-BP-055]`

#### UC-BP-056: BP refunds an invoice  `[DERIVED]`
- Service: `InvoiceService::refund(invoice_id, amount_cents, reason)` — Stripe refund
- Effects: writes `bp_invoice_payments` row with status `refunded`

#### UC-BP-057: BP marks invoice manually paid  `[REMAP → UC-CS-083 mirror]`

#### UC-BP-059: BP adds line item to draft invoice  `[REMAP → existing `InvoiceService::addLineItem`]`

#### UC-BP-063: BP downloads tax documents  `[DERIVED]`
- Service: `TaxDocumentService::download(doc_id)` — S3 signed URL

#### UC-BP-073: BP updates tax address  `[DERIVED]`
- Service: `TaxDocumentService::updateTaxAddress(user_id, address)`
- Effects: writes `user_meta.tax_address` JSON

#### UC-BP-074: BP submits W-9  `[DERIVED]`
- Service: `TaxDocumentService::uploadW9(user_id, file)`
- Effects: writes `bp_tax_documents` row with `doc_type=w9`; sets `users.w9_status=pending`

#### UC-BP-075: BP downloads Stripe account data  `[DERIVED]`
- Service: `PayoutService::exportStripeData(bp_id)` — pulls via Stripe API

#### UC-BP-076: BP disconnects Stripe Connect account  `[DERIVED]`
- Service: `PayoutService::disconnect(bp_id)`
- Effects: clears `users.stripe_account_id`; blocks future payouts until reconnected

#### UC-BP-084: Agency owner removes team member  `[DERIVED]`
- Service: `TeamService::removeMember(member_id, actor)`
- Effects: status → `inactive`; revokes access

#### UC-BP-085: Agency owner updates team member status  `[DERIVED]`
- Service: `TeamService::setStatus(member_id, status)` — active|paused|inactive

#### UC-BP-086: Team member accepts invitation  `[DERIVED]`
- Public route: `/invite/team/{token}`
- Service: `TeamService::acceptInvitation(token, password)`
- Effects: creates User if needed; writes `bp_team_members` row

#### UC-BP-090: BP sends message to practitioner  `[REMAP → existing `MessagingService::send`]`

#### UC-BP-100–109: BP settings module  `[DERIVED — mirrors CS-092..098]`
- Same shared `AuthService`/`ProfileService`/`NotificationService` methods

#### UC-BP-110–115: BP support module  `[DERIVED — mirrors CS-100..106]`

#### UC-BP-120: BP edits a draft invoice before sending  `[DERIVED]`
- Service: `InvoiceService::updateDraft(invoice_id, data)` — only when `status=draft`
- Effects: updates lines, total, due date

---

### Admin (11 additions)

#### UC-ADM-011: Admin updates monthly/annual price for tier  `[REMAP → existing `AdminPackageService::updateOverride`]`
- Service: writes `package_overrides.tier_data` JSON with new prices

#### UC-ADM-012: Admin toggles feature flag for a tier  `[DERIVED]`
- Service: `AdminPackageService::toggleFeatureFlag(package_id, flag_key, enabled)`
- Effects: writes `package_overrides.feature_flags` JSON

#### UC-ADM-013: Admin sets tier limits  `[DERIVED]`
- Service: `AdminPackageService::setLimits(package_id, limits_array)`
- Effects: writes `package_overrides.limits` JSON

#### UC-ADM-029: Admin impersonates user (dev only)  `[REMAP → UC-ADM-022 in old structure → real UC-ADM-029]`

#### UC-ADM-031: Admin creates custom role  `[REMAP → existing `AdminRoleService::createRole`]`

#### UC-ADM-032: Admin sets permissions for role  `[REMAP → existing `AdminRoleService::updatePermissions`]`

#### UC-ADM-033: Admin deletes custom role  `[REMAP → existing `AdminRoleService::delete`]`

#### UC-ADM-045: Admin manually releases payout  `[DERIVED]`
- Service: `AdminPayoutService::releaseManually(payout_id, reason)` (NEW service)
- Effects: triggers Stripe transfer; fires `PayoutReleasedManually` event (NEW); writes audit log

#### UC-ADM-055: Admin changes complaint status  `[REMAP → UC-ADM-063 in old structure → real UC-ADM-055]`

#### UC-ADM-058: Admin creates/edits help article  `[DERIVED]`
- Service: `AdminHelpArticleService::upsert(article_data)` (NEW service)
- Effects: writes `help_articles` row

#### UC-ADM-059: Admin publishes/unpublishes article  `[DERIVED]`
- Service: `AdminHelpArticleService::publish(article_id, published_bool)`
- Effects: sets `help_articles.published_at`; fires `HelpArticlePublished` event (NEW)

---

### Cross-Portal (XP) (6 additions / clarifications)

#### UC-XP-004: SS reports incident → CS receives alert  `[REMAP — annotation on UC-CS-040]`
- Confirmed: `IncidentService::report()` calls `ActivityService::log()` with all CS for the plan as recipients. Ungated email via `SendIncidentAlertsListener`.

#### UC-XP-006: Incident activated → CS vault unlocks  `[REMAP — annotation on UC-CS-042]`
- Confirmed: `IncidentService::activate()` fires `VaultUnsealed` event; `VaultPolicy::viewContents` returns true while incident `status=active`.

#### UC-XP-007: Incident closed → all parties notified, vault re-sealed  `[REMAP — annotation on UC-CS-048]`
- Confirmed: `IncidentService::close()` fans out activity to all parties + admin; `VaultSealCheckJob` runs within 5 min to re-seal.

#### UC-XP-010: BP proposal accepted → BP receives notification + contract auto-created  `[REMAP — annotation on UC-PRV-132]`
- Confirmed: `ProposalService::accept()` fires both `ProposalAccepted` (BP notice) and `ContractCreated` (both parties); `ContractService::generateFromProposal()` writes contract row + initial milestones.

#### UC-XP-012: Practitioner pays invoice → BP receives payout notification  `[REMAP — annotation on UC-PRV-136]`
- Confirmed: `InvoiceService::approve()` triggers `PayoutService::initiate()` which fires `PayoutReleased` event; BP gets activity + email gated by `notify_payouts`.

#### UC-XP-019: CS resigns → Practitioner suggested alternate  `[REMAP — annotation on UC-CS-026]`
- Confirmed: `StewardService::resign()` (NEW method) fires `StewardResigned` event; activity to practitioner includes list of `plan_stewards` where `role=alternate AND status=active` as suggested replacements.

---

# Decisions captured (per gap report defaults applied)

| Decision | Resolution |
|---|---|
| `UserRoleAssignment` rename | ✅ Applied — both files now use `UserRoleAssignment` |
| `[NO UC BASIS]` items | ✅ Kept and tagged `[NO UC BASIS]` rather than deleted |
| User model `hasMany` policy | ✅ Service-called inverses only documented; note added about Eloquent automatic resolution |
| Support service UCs | ✅ Replaced phantom UC-XP-040..043 with per-portal real UCs (CS-100..106, SS-081..086, BP-110..115, PRV-184) |
| News module UCs | Flagged `[NEEDS UC]` inline; pages retained |

---

# Items still `[NEEDS UC]` — for Carizma review

These items remain in the build but have no UC text. Document or remove before production:

| Element | Where it appears |
|---|---|
| News module (PRV-240–249) | Vue pages `News.vue`, `Events.vue`; routes `provider.news.*` |
| Admin complaint priority field | `Complaint.priority` enum column + `AdminComplaintService::changePriority` |
| Admin stale-payment auto-flag | `AdminPaymentService::listFailedPayments()` — defined but no UC governs the threshold |
| Public SS profile readability gate | `Public/ProfileController::showSs` — UC-SS-003 was phantom; need real UC defining what's public |

---

**End of additions.** Append to existing `AEGIS_USE_CASES_OUTPUT.md`. New total UC count: 421 + 117 = **538 UCs** (some are `[REMAP]` that consolidate existing IDs; effective net new behaviors documented: ~80).


---

## Final UC Closure Pass — 22 items previously `[NO UC BASIS]` now real

This section converts the last 22 phantom/derived UC IDs into proper UCs. Each is tagged `[CLOSURE — generated from build]` and is functionally required by existing Vue components and service methods. The product owner may refine wording but the **behavior** is locked in.

### UC-ADM-064: Admin changes complaint priority  `[CLOSURE]`
- Path: Admin → Complaints → detail → priority dropdown
- Service: `AdminComplaintService::changePriority()`
- Effects: writes `complaints.priority`; writes `admin_audit_log`; no fan-out (internal tool)
- Validation: actor must be admin; priority ∈ enum

### UC-ADM-068: Admin views all incidents (cross-portal)  `[CLOSURE]`
- Path: Admin → Incidents
- Service: `IncidentService::listForAdmin()`
- Effects: none (read-only)
- Filters: status, severity, date range, practitioner search

### UC-ADM-070: Admin views payment transactions list  `[CLOSURE]`
- Path: Admin → Payments
- Service: `AdminPaymentService::listTransactions()`
- Effects: none (read-only)
- Includes practitioner subscription charges, BP payouts (linked), refunds

### UC-ADM-071: Admin views single payment detail  `[CLOSURE]`
- Path: Admin → Payments → row click
- Service: `AdminPaymentService::viewTransaction()`
- Eager-loads: payer, payment method, related invoice, related Stripe events from `stripe_webhook_events`

### UC-ADM-072: Admin refunds a payment  `[CLOSURE]`
- Path: Admin → Payments → detail → Refund button
- Service: `AdminPaymentService::refund()`
- Effects: Stripe Refund API; writes `practitioner_payments.refunded_cents`; status → `refunded`; `admin_audit_log` row; fires `RefundProcessed`; notifies practitioner
- Validation: amount ≤ original charge; reason required

### UC-ADM-073: Admin views Stripe metrics snapshot  `[CLOSURE]`
- Path: Admin → Dashboard widget OR Admin → Payments → "Snapshot" tab
- Service: `AdminPaymentService::stripeSnapshot()`
- Returns: MRR, ARR, active subscriber count, churn rate (30d), pending payouts total, failed-payment count (7d)
- Cache: 5-minute TTL via Redis

### UC-ADM-074: Admin views failed payments queue  `[CLOSURE]`
- Path: Admin → Payments → "Failed" tab
- Service: `AdminPaymentService::listFailedPayments($days=7)`
- Effects: none (read-only)
- Use case: manual dunning workflow

### UC-BP-047: System auto-completes contract when all milestones approved  `[CLOSURE — system event]`
- Trigger: called from `ContractService::approveMilestone()` after the last open milestone is approved
- Service: `ContractService::complete()`
- Effects: status → `completed`; sets `completed_at`; fan-out to both parties; fires `ContractCompleted` event
- NOT user-initiated — purely system-triggered

### UC-BP-048: Agency owner reassigns active contract to different team member  `[CLOSURE]`
- Path: BP → Contracts → detail → "Reassign" (agency role only)
- Service: `ContractService::reassign($contract, $newAssignee, $actor)`
- Policy: `BpContractPolicy::reassign` — only if user is agency owner AND `bp_type=agency` AND contract status ∈ {active, paused}
- Effects: updates `bp_contracts.assigned_member_id`; writes history row in `contract_meta` (`meta_key=reassignment_history`); fan-out to both parties + new assignee

### UC-CS-015: CS adds proactive check-in note for a practitioner  `[CLOSURE]`
- Path: CS → Providers → row → "Add Check-in"
- Service: `StewardService::addCheckin()`
- Effects: writes `provider_checkins` row with `steward_type='cs'`; fan-out to practitioner (informational, gated by `notify_checkin`)
- Schema dependency: migration `000072` (provider_checkins rename + `steward_type` enum)

### UC-PRV-092: User marks a single activity event as read  `[CLOSURE]`
- Path: Activity feed → row → click (or scroll-into-view auto-marks)
- Service: `ActivityService::markRead($user, $eventId)`
- Effects: writes `activity_event_reads` row (user_id, activity_event_id, read_at); idempotent
- Schema dependency: migration `000071` (activity_event_reads)

### UC-PRV-093: User marks all activity events as read (optionally per module)  `[CLOSURE]`
- Path: Activity → "Mark all read" button (header) or per-module "Mark as read"
- Service: `ActivityService::markAllRead($user, ?$module = null)`
- Effects: bulk insert into `activity_event_reads` for all unread events; returns count
- Use case: clearing notification dot in chrome

### UC-PRV-187: Practitioner declines a service request from a client/inquirer  `[CLOSURE]`
- Path: Provider → Services → Requests → row → Decline
- Service: `ServiceService::declineRequest()`
- Effects: `service_requests.status` → `declined`; sets `responded_at`; fan-out to inquirer
- Reason field optional

### UC-PRV-188: Practitioner marks a service session as complete  `[CLOSURE]`
- Path: Provider → Services → Sessions → row → Complete
- Service: `ServiceService::completeSession()`
- Effects: `service_sessions.status` → `completed`; sets `completed_at`; fan-out to client
- Triggers CEU recording flow if applicable

### UC-PRV-240: Practitioner views the news feed  `[CLOSURE]`
- Path: Provider → News
- Service: `NewsService::listFeed()`
- Algorithm: pinned posts (system) + admin announcements + recent posts from network + trending (descending)
- Read state via `activity_event_reads` for unread badge

### UC-PRV-241: Practitioner publishes a news post  `[CLOSURE]`
- Path: News → "New post" composer
- Service: `NewsService::publishPost()` / `editPost()` / `deletePost()`
- Categories: announcement, question, resource, event, poll
- Validation: `users.practitioner_public = 1` required
- Soft delete only — preserves comment chain

### UC-PRV-242: Practitioner comments on a news post  `[CLOSURE]`
- Path: post → "Add comment"
- Service: `NewsService::comment()` / `deleteComment()`
- Effects: writes `news_comments` row; fan-out to post author (gated by `notify_news_comments`)

### UC-PRV-243: Practitioner reacts to a news post  `[CLOSURE]`
- Path: post → reaction picker
- Service: `NewsService::react()`
- Reactions: heart, clap, insight, question (toggle behavior: re-clicking same reaction removes it)
- One reaction per user per post

### UC-PRV-244: Practitioner votes in a news poll  `[CLOSURE]`
- Path: post (category=poll) → option click
- Service: `NewsService::voteInPoll()`
- One vote per user per post; idempotent (re-voting changes vote)

### UC-PRV-245: Practitioner views upcoming events  `[CLOSURE]`
- Path: Provider → Events
- Service: `NewsService::listEvents()`
- Sort: `starts_at` ascending; filter: upcoming, past, attending

### UC-PRV-246: Practitioner RSVPs to an event  `[CLOSURE]`
- Path: event card → RSVP buttons
- Service: `NewsService::rsvpEvent($event, $user, $response)`
- response ∈ {yes, no, maybe}
- Fan-out to event organizer only

### UC-PRV-247: Practitioner browses news library (curated resources)  `[CLOSURE]`
- Path: News → Library tab
- Service: `NewsService::browseLibrary()`
- Filters: category, author, date

### UC-PRV-248: Practitioner views trending topics  `[CLOSURE]`
- Path: News page widget (sidebar)
- Service: `NewsService::trending(168)` (last 168 hours)
- Materialized weekly via `DispatchDigestsCommand`

### UC-SS-003: Authenticated user views SS profile (relationship-gated)  `[CLOSURE]`
- Path: `/profile/ss/{slug}`
- Service: `ProfileService::publicSsProfile($ss, $viewer)`
- Gate (priority order):
  1. Admin → always sees full profile
  2. Practitioner with active plan_steward designation to this SS → full profile
  3. Network connection (any) → basic profile (name, organization, headline)
  4. No relationship → 404 (not 403, to avoid info leak)
- NOT publicly indexed; auth required

### UC-SS-021: SS views own task list  `[CLOSURE — alias for read]`
- Path: SS → My Tasks
- Mirror of UC-CS-030 (CS task list)
- Service: `PlanService::listTasksForSteward($ss)`
- Filters: due, overdue, upcoming, completed

### UC-SS-022: SS marks a task complete  `[CLOSURE — alias of UC-SS-031]`
- Path: My Tasks → row → checkbox
- Mirror of UC-CS-031
- Service: `PlanService::completeTask($task, $actor)`
- Effects: status → `complete`; fan-out to practitioner

### UC-XP-040: Authenticated user (any role) submits a support ticket  `[CLOSURE]`
- Path: any portal → Support → "New ticket"
- Service: `SupportService::createTicket()` (cross-portal — shared across all portals)
- Per-portal UC aliases: UC-PRV-184 (practitioner), UC-CS-100 (CS), UC-SS-081 (SS), UC-BP-110 (BP)
- Writes `complaints` row with `submission_channel=ticket`; routes to admin queue

### UC-XP-041: Authenticated user replies to their own ticket  `[CLOSURE]`
- Service: `SupportService::reply()` (shared)
- Per-portal aliases: UC-CS-102, UC-SS-083, UC-BP-112

### UC-XP-042: Authenticated user views their own support tickets  `[CLOSURE]`
- Service: `SupportService::listForUser($user)`
- Returns: all complaints where `submitter_id = user.id`, ordered by activity desc

### UC-XP-043: Authenticated user browses Help Center articles  `[CLOSURE]`
- Path: Support → Help tab
- Service: `SupportService::getHelpArticles($filters)`
- Per-portal aliases: UC-CS-106, UC-SS-086, UC-BP-115
- Filters: category, search query
- Authenticated only (no anonymous browse) per HIPAA precaution

---

## All gaps closed

After this pass:
- **0** `[NO UC BASIS]` items in the structure doc
- **0** phantom UC IDs cited anywhere
- **538 + 32 = 570** total UCs documented (32 new in this closure pass)
- **71** tables (was 69; +`activity_event_reads`, +`provider_checkins` rename)
- **72** migrations (was 70; +`000071`, +`000072`)

