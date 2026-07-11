# AEGIS_DATABASE_SCHEMA.md

**Target:** MySQL 8 via Laravel 11 migrations. **Conventions applied to every table:** `CHAR(36)` UUID PKs (`id`, default `uuid()`); money in **cents** (`INT`/`BIGINT`, never DECIMAL); all timestamps `TIMESTAMP` UTC; soft delete `deleted_at TIMESTAMP NULL` on user-facing tables; every FK column indexed; meta-table pattern for sparse/expandable attributes; polymorphic `linkable_type`+`linkable_id` on `activity_events` and `admin_audit_log`. Index tags: `PK` primary, `FK,IDX` foreign-key index, `IDX` secondary, `UQ` unique. Every column cites the UC that needs it. **71 tables** + Sections A–D _(added in UC closure pass: `activity_event_reads`, `provider_checkins` rename)_.

> Standard `*_meta` shape (all 7 meta tables): `id` PK · `{entity}_id` FK,IDX · `meta_key` VARCHAR(191) · `meta_value` LONGTEXT NULL · `meta_type` ENUM · `created_at`/`updated_at`; UNIQUE `(entity_id, meta_key)`. Written in full per table.

---

## `users`
**Purpose:** Single identity row for every human across all five portals.
**UCs:** UC-PRV-001,002,003,004,010,016,017,019; UC-CS-001,002,003; UC-SS-001; UC-XP-031; UC-ADM-020..029
**Meta table:** yes → `user_meta`

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | e.g. p_sarah, cs_marcus |
| role | ENUM('practitioner','continuity_steward','support_steward','business_partner','admin') | NO | 'practitioner' | IDX | default/primary role · UC-PRV-001; multi-role authoritative in user_roles · UC-XP-031 |
| display_name | VARCHAR(191) | NO | — | — | UC-PRV-010 |
| credentials | VARCHAR(191) | YES | NULL | — | MD/JD/CPA · UC-PRV-011 |
| email | VARCHAR(191) | NO | — | UQ | UC-PRV-001,002 |
| phone | VARCHAR(40) | YES | NULL | — | UC-PRV-010 |
| location | VARCHAR(191) | YES | NULL | — | UC-PRV-010 |
| organization | VARCHAR(191) | YES | NULL | — | UC-PRV-010 |
| avatar_initials | VARCHAR(4) | YES | NULL | — | UC-PRV-010 |
| title | VARCHAR(191) | YES | NULL | — | UC-PRV-010 |
| specialty | VARCHAR(191) | YES | NULL | — | UC-PRV-012 |
| bio | TEXT | YES | NULL | — | UC-PRV-010 |
| slug | VARCHAR(191) | YES | NULL | UQ | public slug · UC-PRV-018 |
| slug_locked_at | TIMESTAMP | YES | NULL | — | UC-PRV-018 |
| practitioner_public | TINYINT(1) | NO | 1 | IDX | UC-PRV-019; UC-XP-027 |
| cs_public | TINYINT(1) | NO | 0 | IDX | Business CS opt-in · UC-CS-003; UC-XP-028 |
| business_partner_public | TINYINT(1) | NO | 1 | IDX | UC-XP-030 |
| tier | ENUM('access','practice') | YES | NULL | IDX | practitioner tier · UC-PRV-003,004 |
| services_mode | TINYINT(1) | NO | 0 | IDX | IBS Services mode · UC-PRV-017 |
| maat_addon | TINYINT(1) | NO | 0 | IDX | MAAT add-on · UC-PRV-210,211 |
| payment_model | VARCHAR(40) | YES | NULL | — | CS-fee model · UC-PRV-146 |
| cs_account_type | ENUM('invited','business','enterprise') | YES | NULL | IDX | UC-CS-001,002,003 |
| cs_path | VARCHAR(20) | YES | NULL | — | onboarding pathway · UC-CS-001 |
| linked_provider_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id · Invited-CS link · UC-CS-001 |
| stripe_connected | TINYINT(1) | NO | 0 | IDX | UC-CS; UC-BP-070 |
| stripe_account_id | VARCHAR(64) | YES | NULL | — | acct_… · UC-BP-070 |
| verified | TINYINT(1) | NO | 0 | IDX | Aegis Verified · UC-CS-041 |
| invited_by_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id · SS inviter · UC-SS-001 |
| about_me | TEXT | YES | NULL | — | SS note to CS · UC-SS-002 |
| bp_type | ENUM('agency','freelancer') | YES | NULL | IDX | UC-BP-001 |
| bp_business_name | VARCHAR(191) | YES | NULL | — | UC-BP-001 |
| bp_team_size | INT | YES | NULL | — | UC-BP-001 |
| bp_hourly_rate_cents | INT | YES | NULL | — | cents · UC-BP-001 |
| bp_categories | JSON | YES | NULL | — | category array · UC-BP-001 |
| two_factor_enabled | TINYINT(1) | NO | 0 | IDX | UC-PRV-214 |
| w9_status | VARCHAR(20) | YES | NULL | — | BP tax · UC-BP-070 |
| locked_at | TIMESTAMP | YES | NULL | IDX | admin/auto lockout · UC-ADM-023,024; UC-PRV-002 |
| locked_reason | VARCHAR(255) | YES | NULL | — | UC-ADM-023 |
| failed_login_count | INT | NO | 0 | — | lockout counter · UC-PRV-002 |
| deactivated_at | TIMESTAMP | YES | NULL | IDX | soft deactivation · UC-ADM-027,028 |
| last_login_at | TIMESTAMP | YES | NULL | — | UC-PRV-002 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | signup trend · UC-ADM-002 |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; UQ `email`, `slug`; FK+IDX `linked_provider_id`, `invited_by_id`; IDX `role`, `tier`, `cs_account_type`, `bp_type`, `practitioner_public`, `cs_public`, `business_partner_public`, `services_mode`, `maat_addon`, `verified`, `stripe_connected`, `two_factor_enabled`, `created_at`, `locked_at`, `deactivated_at`.
**Enum values:** `role`: practitioner|continuity_steward|support_steward|business_partner|admin · `tier`: access|practice · `cs_account_type`: invited|business|enterprise · `bp_type`: agency|freelancer.
> MFA secret lives in `mfa_tokens`; notification prefs live in `user_meta`.

---

## `user_meta`
**Purpose:** Sparse/role-specific user attributes + all email notification gate keys.
**UCs:** UC-PRV-012,016,019; EMAIL_TEMPLATES §B
**Meta table:** self

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| user_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| meta_key | VARCHAR(191) | NO | — | UQ* | composite UQ with user_id |
| meta_value | LONGTEXT | YES | NULL | — | typed by meta_type |
| meta_type | ENUM('string','int','boolean','json','timestamp') | NO | 'string' | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `user_id`; UQ `(user_id, meta_key)`.
**Known keys:** Section 3 lists all (~26 `notify_*` boolean gate keys + profile keys: `practitioner_specialties` json, `availability_status` string, `accepting_status` boolean, `intent_segment` string, `w9_last4` string, `ssn_last4` string, `ein_last4` string).

---

## `user_roles`
**Purpose:** Multi-role entitlement — one human may hold several portal roles.
**UCs:** UC-XP-031; UC-ADM-026
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| user_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| role | ENUM('practitioner','continuity_steward','support_steward','business_partner','admin') | NO | — | IDX | UC-XP-031 |
| is_default | TINYINT(1) | NO | 0 | IDX | default portal · UC-XP-031 |
| enabled_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `user_id`; IDX `role`, `is_default`; UQ `(user_id, role)`.
**Enum values:** `role`: practitioner|continuity_steward|support_steward|business_partner|admin.

---

## `user_sessions`
**Purpose:** Active login sessions / device list for security + new-device alerts.
**UCs:** UC-PRV-002; EMAIL T7
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| user_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| session_token | VARCHAR(128) | NO | — | UQ | UC-PRV-002 |
| ip_address | VARCHAR(45) | YES | NULL | — | T7 |
| user_agent | VARCHAR(255) | YES | NULL | — | T7 |
| device_label | VARCHAR(191) | YES | NULL | — | UC-PRV-002 |
| last_seen_at | TIMESTAMP | YES | NULL | IDX | |
| revoked_at | TIMESTAMP | YES | NULL | IDX | revoke_session · UC-PRV-002 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |

**Indexes:** PK `id`; FK+IDX `user_id`; UQ `session_token`; IDX `last_seen_at`, `revoked_at`, `created_at`.

---

## `user_preferences`
**Purpose:** Non-notification UI prefs (theme, locale, formats).
**UCs:** UC-PRV-019
**Meta table:** no (fixed key set)

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| user_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| theme | VARCHAR(20) | NO | 'gold' | — | UC-PRV-019 |
| font_size | INT | NO | 100 | — | percent · UC-PRV-019 |
| compact | TINYINT(1) | NO | 0 | — | UC-PRV-019 |
| language | VARCHAR(10) | NO | 'en' | — | UC-PRV-019 |
| timezone | VARCHAR(64) | NO | 'America/New_York' | — | UC-PRV-019 |
| date_format | VARCHAR(20) | NO | 'MM/DD/YYYY' | — | UC-PRV-019 |
| time_format | VARCHAR(5) | NO | '12h' | — | UC-PRV-019 |
| currency | VARCHAR(3) | NO | 'USD' | — | UC-PRV-019 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `user_id`; UQ `(user_id)`.

---

## `password_reset_tokens`
**Purpose:** One-time password reset links.
**UCs:** UC-PRV-005; UC-ADM-025
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| user_id | CHAR(36) | NO | — | FK,IDX | FK→users.id · UC-PRV-005 |
| token | VARCHAR(128) | NO | — | UQ | UC-ADM-025 |
| expires_at | TIMESTAMP | NO | — | IDX | UC-PRV-005 |
| used_at | TIMESTAMP | YES | NULL | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `user_id`; UQ `token`; IDX `expires_at`.

---

## `mfa_tokens`
**Purpose:** TOTP secret + recovery codes per user.
**UCs:** UC-PRV-002,214; EMAIL T5/T6
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| user_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| secret | VARCHAR(255) | NO | — | — | encrypted TOTP secret · UC-PRV-214 |
| recovery_codes | JSON | YES | NULL | — | hashed backup codes · UC-PRV-214 |
| confirmed_at | TIMESTAMP | YES | NULL | — | T5 |
| disabled_at | TIMESTAMP | YES | NULL | — | T6 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `user_id`; UQ `(user_id)`.

---

## `continuity_plans`
**Purpose:** The practitioner's continuity plan — lifecycle root.
**UCs:** UC-PRV-030,035,036,038,039,040; UC-XP-002,003,008,014; UC-ADM-021
**Meta table:** yes → `plan_meta`

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id · UC-PRV-030 |
| status | ENUM('draft','pending_review','active','annual_review_due','expired') | NO | 'draft' | IDX | UC-PRV-030,035,036 |
| plan_version | INT | NO | 1 | — | material-change versioning · UC-PRV-035 |
| signed_at | TIMESTAMP | YES | NULL | — | UC-PRV-036 |
| signature_name | VARCHAR(191) | YES | NULL | — | UC-PRV-036 |
| signature_title | VARCHAR(191) | YES | NULL | — | UC-PRV-036 |
| signature_ip | VARCHAR(45) | YES | NULL | — | UC-PRV-036 |
| expires_at | TIMESTAMP | YES | NULL | IDX | UC-PRV-036 |
| annual_review_date | TIMESTAMP | YES | NULL | IDX | UC-XP-014 |
| last_review_at | TIMESTAMP | YES | NULL | — | UC-PRV-038,039 |
| annual_review_notes | TEXT | YES | NULL | — | UC-PRV-039 |
| vault_attested_at | TIMESTAMP | YES | NULL | IDX | UC-PRV-040; UC-XP-003 |
| vault_attestation_note | TEXT | YES | NULL | — | UC-PRV-040 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `practitioner_id`; IDX `status`, `expires_at`, `annual_review_date`, `vault_attested_at`, `created_at`; Composite `(status, annual_review_date)` — re-attestation sweep (UC-XP-014).
**Enum values:** `status`: draft|pending_review|active|annual_review_due|expired.

---

## `plan_meta`
**Purpose:** Sparse plan attributes (template choice, version-change meta).
**UCs:** UC-PRV-030,035
**Meta table:** self

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| plan_id | CHAR(36) | NO | — | FK,IDX | FK→continuity_plans.id |
| meta_key | VARCHAR(191) | NO | — | UQ* | |
| meta_value | LONGTEXT | YES | NULL | — | |
| meta_type | ENUM('string','int','boolean','json','timestamp') | NO | 'string' | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `plan_id`; UQ `(plan_id, meta_key)`.
**Known keys:** `applied_template_key` string, `applied_template_title` string, `material_change_pending_ack` boolean.

---

## `plan_stewards`
**Purpose:** CS/SS designations against a plan (role, status, permissions, vault access).
**UCs:** UC-PRV-050,051,053,054,056,204,205,206; UC-CS-001,025; UC-SS-001,015
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| plan_id | CHAR(36) | NO | — | FK,IDX | FK→continuity_plans.id |
| steward_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| role | ENUM('primary','alternate','support') | NO | 'primary' | IDX | UC-PRV-050 |
| steward_category | VARCHAR(40) | YES | NULL | IDX | SS functional category · UC-SS-015 |
| status | ENUM('invited','active','declined','request_incoming','archived','pending') | NO | 'invited' | IDX | UC-PRV-050,056; UC-CS-001 |
| permissions | JSON | YES | NULL | — | per-steward perms · UC-PRV-204 |
| vault_access | ENUM('none','metadata','scoped','full') | NO | 'none' | IDX | UC-PRV-205 |
| responsibilities | JSON | YES | NULL | — | authorized responsibilities · UC-PRV-053 |
| signed_at | TIMESTAMP | YES | NULL | — | designation signed · UC-CS-001 |
| review_due_at | TIMESTAMP | YES | NULL | — | UC-PRV |
| invited_at | TIMESTAMP | YES | NULL | — | UC-PRV-051 |
| request_sent_at | TIMESTAMP | YES | NULL | — | resend tracking · UC-PRV-206 |
| expires_at | TIMESTAMP | YES | NULL | — | invite expiry · UC-PRV-051 |
| declined_at | TIMESTAMP | YES | NULL | — | UC-CS decline |
| declined_reason | VARCHAR(255) | YES | NULL | — | UC-CS decline |
| ss_acknowledged_at | TIMESTAMP | YES | NULL | — | v15 SS plan-awareness ack · UC-SS |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete (removal · UC-PRV-056) |

**Indexes:** PK `id`; FK+IDX `plan_id`, `steward_id`; IDX `role`, `status`, `vault_access`, `steward_category`, `created_at`; Composite `(plan_id, status)`.
**Enum values:** `role`: primary|alternate|support · `status`: invited|active|declined|request_incoming|archived|pending · `vault_access`: none|metadata|scoped|full.

---

## `plan_tasks`
**Purpose:** Plan-level standing tasks per steward (non-incident).
**UCs:** UC-PRV-033,054
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| plan_id | CHAR(36) | NO | — | FK,IDX | FK→continuity_plans.id |
| assigned_to | ENUM('continuity_steward','support_steward') | NO | — | IDX | UC-PRV-033 |
| steward_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id |
| title | VARCHAR(255) | NO | — | — | UC-PRV-033 |
| timeline | VARCHAR(191) | YES | NULL | — | UC-PRV-033 |
| sort_order | INT | NO | 0 | — | copy order · UC-PRV-054 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `plan_id`, `steward_id`; IDX `assigned_to`.
**Enum values:** `assigned_to`: continuity_steward|support_steward.

---

## `plan_incident_configs`
**Purpose:** Per-incident-type config — active flag, required docs, CS/SS authorization matrix.
**UCs:** UC-PRV-031,053
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| plan_id | CHAR(36) | NO | — | FK,IDX | FK→continuity_plans.id |
| incident_type | VARCHAR(64) | NO | — | IDX | one of 7 types · UC-PRV-031 |
| is_active | TINYINT(1) | NO | 0 | IDX | UC-PRV-031 |
| docs_required | JSON | YES | NULL | — | UC-PRV-053 |
| authorized_ss_ids | JSON | YES | NULL | — | UC-PRV-053 |
| authorized_cs_ids | JSON | YES | NULL | — | UC-PRV-053 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `plan_id`; IDX `incident_type`, `is_active`; UQ `(plan_id, incident_type)`.

---

## `critical_incidents`
**Purpose:** An activated critical incident (report → verify → active → closed).
**UCs:** UC-PRV-090; UC-SS-030; UC-CS-041; UC-XP-004,005,006,007,024; UC-ADM-004
**Meta table:** yes → `incident_meta`

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| plan_id | CHAR(36) | NO | — | FK,IDX | FK→continuity_plans.id |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id · UC-PRV-090 |
| reported_by_id | CHAR(36) | NO | — | FK,IDX | FK→users.id · UC-SS-030 |
| incident_type | VARCHAR(64) | NO | — | IDX | UC-PRV-090 |
| status | ENUM('reported','verified','active','closed') | NO | 'reported' | IDX | UC-CS-041; UC-XP-005,006,007 |
| severity | ENUM('info','warning','critical') | NO | 'critical' | IDX | |
| reported_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | UC-PRV-090 |
| verified_at | TIMESTAMP | YES | NULL | — | UC-CS-041 |
| verified_by_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id · UC-CS-041 |
| activated_at | TIMESTAMP | YES | NULL | — | vault unseal · UC-XP-006 |
| closed_at | TIMESTAMP | YES | NULL | — | UC-XP-007 |
| summary | TEXT | YES | NULL | — | UC-PRV-090 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `plan_id`, `practitioner_id`, `reported_by_id`, `verified_by_id`; IDX `status`, `incident_type`, `reported_at`, `created_at`; Composite `(status, reported_at)` — oversight (UC-ADM-004, UC-XP-024).
**Enum values:** `status`: reported|verified|active|closed · `severity`: info|warning|critical.

---

## `incident_meta`
**Purpose:** Sparse incident attributes (escalation refs, verification notes).
**UCs:** UC-CS-041; EMAIL T30
**Meta table:** self

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| incident_id | CHAR(36) | NO | — | FK,IDX | FK→critical_incidents.id |
| meta_key | VARCHAR(191) | NO | — | UQ* | |
| meta_value | LONGTEXT | YES | NULL | — | |
| meta_type | ENUM('string','int','boolean','json','timestamp') | NO | 'string' | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `incident_id`; UQ `(incident_id, meta_key)`.
**Known keys:** `verification_note` string, `escalation_requested_at` timestamp, `escalation_ref` string.

---

## `incident_tasks`
**Purpose:** Tasks generated/assigned during an active incident.
**UCs:** UC-CS-031,032,033,034; UC-XP-009
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| incident_id | CHAR(36) | NO | — | FK,IDX | FK→critical_incidents.id |
| assigned_to_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id |
| assigned_role | ENUM('continuity_steward','support_steward') | NO | — | IDX | UC-CS-031 |
| title | VARCHAR(255) | NO | — | — | UC-CS-031 |
| description | TEXT | YES | NULL | — | UC-CS-032 |
| status | ENUM('pending','in_progress','complete','exception') | NO | 'pending' | IDX | UC-CS-031,034 |
| timeline | VARCHAR(191) | YES | NULL | — | UC-CS-031 |
| completed_at | TIMESTAMP | YES | NULL | — | UC-CS-031 |
| exception_reason | VARCHAR(255) | YES | NULL | — | UC-CS-034 |
| sort_order | INT | NO | 0 | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `incident_id`, `assigned_to_id`; IDX `assigned_role`, `status`; Composite `(incident_id, status)`.
**Enum values:** `assigned_role`: continuity_steward|support_steward · `status`: pending|in_progress|complete|exception.

---

## `incident_updates`
**Purpose:** Append-only incident timeline (report/verify/unseal/notify/close).
**UCs:** UC-XP-004,005,006,007
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| incident_id | CHAR(36) | NO | — | FK,IDX | FK→critical_incidents.id |
| actor_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id (null=system) |
| update_type | ENUM('reported','verified','activated','vault_unsealed','ss_notified','task_added','escalated','closed') | NO | — | IDX | UC-XP-004..007 |
| message | VARCHAR(255) | YES | NULL | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |

**Indexes:** PK `id`; FK+IDX `incident_id`, `actor_id`; IDX `update_type`, `created_at`; Composite `(incident_id, created_at)`.
**Enum values:** `update_type`: reported|verified|activated|vault_unsealed|ss_notified|task_added|escalated|closed.

---

## `vault_items`
**Purpose:** 4-zone vault entries with AES-256-GCM credential envelope.
**UCs:** UC-PRV-070,071,072,075,198,199,200,201,202,203; UC-XP-023
**Meta table:** yes → `vault_item_meta`

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| zone | ENUM('credentials','roster','documents','instructions') | NO | — | IDX | UC-PRV-070 |
| category | VARCHAR(64) | YES | NULL | — | UC-PRV-070 |
| title | VARCHAR(191) | NO | — | — | UC-PRV-070 |
| sub_label | VARCHAR(255) | YES | NULL | — | UC-PRV-070 |
| status | ENUM('vault_only','active','priority') | NO | 'vault_only' | IDX | UC-PRV-070 |
| credential_username | VARCHAR(191) | YES | NULL | — | UC-PRV-070 |
| credential_password_enc | TEXT | YES | NULL | — | AES-256-GCM · UC-PRV-201 |
| credential_url | VARCHAR(255) | YES | NULL | — | UC-PRV-070 |
| client_name | VARCHAR(191) | YES | NULL | — | roster · UC-PRV-072 |
| client_priority | INT | YES | NULL | — | UC-PRV-072 |
| file_ref | VARCHAR(255) | YES | NULL | — | documents · UC-PRV-070 |
| access_grant | JSON | YES | NULL | — | scoped grants · UC-PRV-200 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete (UC-PRV-199) |

**Indexes:** PK `id`; FK+IDX `practitioner_id`; IDX `zone`, `status`, `created_at`; Composite `(practitioner_id, zone)`.
**Enum values:** `zone`: credentials|roster|documents|instructions · `status`: vault_only|active|priority.

---

## `vault_item_meta`
**Purpose:** Sparse vault-item attributes (tags, per-item flags).
**UCs:** UC-PRV-070,072
**Meta table:** self

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| vault_item_id | CHAR(36) | NO | — | FK,IDX | FK→vault_items.id |
| meta_key | VARCHAR(191) | NO | — | UQ* | |
| meta_value | LONGTEXT | YES | NULL | — | |
| meta_type | ENUM('string','int','boolean','json','timestamp') | NO | 'string' | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `vault_item_id`; UQ `(vault_item_id, meta_key)`.
**Known keys:** `tags` json, `client_location` string, `client_service` string, `client_notes` string, `client_status` string.

---

## `vault_access_log`
**Purpose:** Audit of reveal/download/export/share on vault items.
**UCs:** UC-PRV-075,201,202,203; UC-XP-023
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| vault_item_id | CHAR(36) | YES | NULL | FK,IDX | FK→vault_items.id (null=export-all) |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id (owner) |
| actor_id | CHAR(36) | NO | — | FK,IDX | FK→users.id (who acted) |
| access_type | ENUM('reveal','download','export','share','view') | NO | — | IDX | UC-PRV-201,202,203 |
| recipient_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id (share target) · UC-PRV-200 |
| ip_address | VARCHAR(45) | YES | NULL | — | UC-PRV-075 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |

**Indexes:** PK `id`; FK+IDX `vault_item_id`, `practitioner_id`, `actor_id`, `recipient_id`; IDX `access_type`, `created_at`; Composite `(practitioner_id, created_at)`.
**Enum values:** `access_type`: reveal|download|export|share|view.

---

## `continuity_documents`
**Purpose:** Signed plan documents + lifecycle (the prompt's "important_documents").
**UCs:** UC-PRV-080,081,082,190,191,192,193,194,195,196,197; UC-XP-002
**Meta table:** no (signatures in child table)

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| plan_id | CHAR(36) | YES | NULL | FK,IDX | FK→continuity_plans.id |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| reference | VARCHAR(64) | YES | NULL | UQ | amendments suffix -AMD · UC-PRV-192 |
| title | VARCHAR(191) | NO | — | — | UC-PRV-081 |
| doc_type | VARCHAR(64) | YES | NULL | IDX | plan/support/agreement · UC-PRV-081 |
| status | ENUM('draft','countersign','active','archived','release_pending') | NO | 'draft' | IDX | UC-PRV-190..196 |
| amends_document_id | CHAR(36) | YES | NULL | FK,IDX | FK→continuity_documents.id · UC-PRV-192 |
| holder_steward_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id (custody) · UC-PRV-196 |
| file_ref | VARCHAR(255) | YES | NULL | — | UC-PRV-081 |
| issued_at | TIMESTAMP | YES | NULL | — | UC-PRV-081 |
| expires_at | TIMESTAMP | YES | NULL | IDX | renew · UC-PRV-193 |
| archived_at | TIMESTAMP | YES | NULL | — | UC-PRV-194 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete (UC-PRV-195) |

**Indexes:** PK `id`; FK+IDX `plan_id`, `practitioner_id`, `amends_document_id`, `holder_steward_id`; UQ `reference`; IDX `doc_type`, `status`, `expires_at`, `created_at`; Composite `(practitioner_id, status)`.
**Enum values:** `status`: draft|countersign|active|archived|release_pending.

---

## `document_signatures`
**Purpose:** Per-party signature rows (practitioner sign + CS countersign audit).
**UCs:** UC-PRV-035,036,191; UC-XP-002
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| document_id | CHAR(36) | NO | — | FK,IDX | FK→continuity_documents.id |
| signer_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| signer_role | ENUM('practitioner','continuity_steward') | NO | — | IDX | UC-PRV-036; UC-XP-002 |
| signature_name | VARCHAR(191) | NO | — | — | UC-PRV-036 |
| signature_ip | VARCHAR(45) | YES | NULL | — | UC-PRV-036 |
| signed_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `document_id`, `signer_id`; IDX `signer_role`, `signed_at`; UQ `(document_id, signer_id)`.
**Enum values:** `signer_role`: practitioner|continuity_steward.

---

## `network_connections`
**Purpose:** Accepted practitioner↔practitioner / practitioner↔BP connections.
**UCs:** UC-PRV-100,101; UC-XP-027,030
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| user_id | CHAR(36) | NO | — | FK,IDX | FK→users.id (owner) |
| connected_user_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| connection_type | ENUM('practitioner','business_partner') | NO | 'practitioner' | IDX | UC-PRV-100 |
| status | ENUM('active','archived') | NO | 'active' | IDX | |
| connected_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | UC-PRV-101 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete (disconnect) |

**Indexes:** PK `id`; FK+IDX `user_id`, `connected_user_id`; IDX `connection_type`, `status`; UQ `(user_id, connected_user_id)`.
**Enum values:** `connection_type`: practitioner|business_partner · `status`: active|archived.

---

## `network_requests`
**Purpose:** Pending/declined connection requests.
**UCs:** UC-PRV-100,101,103
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| requester_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| recipient_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id (null=external) · UC-PRV-103 |
| recipient_email | VARCHAR(191) | YES | NULL | — | external invite · UC-PRV-103 |
| status | ENUM('pending','accepted','declined','cancelled') | NO | 'pending' | IDX | UC-PRV-101 |
| message | VARCHAR(255) | YES | NULL | — | UC-PRV-100 |
| invite_token | VARCHAR(128) | YES | NULL | UQ | external · UC-PRV-103 |
| responded_at | TIMESTAMP | YES | NULL | — | UC-PRV-101 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `requester_id`, `recipient_id`; UQ `invite_token`; IDX `status`, `created_at`.
**Enum values:** `status`: pending|accepted|declined|cancelled.

---

## `shadow_connections`
**Purpose:** Suggested/implicit (non-confirmed) network edges.
**UCs:** UC-PRV-100
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| user_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| shadow_user_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id |
| shadow_name | VARCHAR(191) | YES | NULL | — | unresolved suggestion |
| source | VARCHAR(64) | YES | NULL | — | derivation source |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `user_id`, `shadow_user_id`; IDX `created_at`.

---

## `referrals`
**Purpose:** Practitioner→practitioner client referrals (send/respond/close).
**UCs:** UC-PRV-108,110,111
**Meta table:** yes → `referral_meta`

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| sender_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| recipient_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| status | ENUM('sent','accepted','declined','closed','cancelled') | NO | 'sent' | IDX | UC-PRV-108,110,111 |
| subject | VARCHAR(191) | YES | NULL | — | UC-PRV-108 |
| responded_at | TIMESTAMP | YES | NULL | — | UC-PRV-111 |
| closed_at | TIMESTAMP | YES | NULL | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `sender_id`, `recipient_id`; IDX `status`, `created_at`; Composite `(recipient_id, status)`.
**Enum values:** `status`: sent|accepted|declined|closed|cancelled.

---

## `referral_meta`
**Purpose:** Sparse referral attributes (client context, decline reason).
**UCs:** UC-PRV-108,111
**Meta table:** self

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| referral_id | CHAR(36) | NO | — | FK,IDX | FK→referrals.id |
| meta_key | VARCHAR(191) | NO | — | UQ* | |
| meta_value | LONGTEXT | YES | NULL | — | |
| meta_type | ENUM('string','int','boolean','json','timestamp') | NO | 'string' | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `referral_id`; UQ `(referral_id, meta_key)`.
**Known keys:** `client_context` string, `decline_reason` string, `urgency` string, `specialty_needed` string.

---

## `message_threads`
**Purpose:** Conversation containers across portals.
**UCs:** UC-PRV (messages); UC-XP-015
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| subject | VARCHAR(191) | YES | NULL | — | |
| created_by_id | CHAR(36) | NO | — | FK,IDX | FK→users.id (create_thread) |
| last_message_at | TIMESTAMP | YES | NULL | IDX | sort |
| is_pinned | TINYINT(1) | NO | 0 | IDX | pin_thread |
| is_muted | TINYINT(1) | NO | 0 | — | mute_thread |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `created_by_id`; IDX `last_message_at`, `is_pinned`, `created_at`.
> 1:1 threads via `messages.sender_id`/`recipient_id`. For multi-party, add `message_thread_participants(thread_id, user_id)` later. [DESIGN NOTE]

---

## `messages`
**Purpose:** Individual messages with attachments + reactions + read state.
**UCs:** UC-PRV (messages)
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| thread_id | CHAR(36) | NO | — | FK,IDX | FK→message_threads.id |
| sender_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| recipient_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id |
| body | TEXT | NO | — | — | |
| attachments | JSON | YES | NULL | — | file refs |
| reactions | JSON | YES | NULL | — | reaction map |
| read_at | TIMESTAMP | YES | NULL | IDX | mark_read/unread |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `thread_id`, `sender_id`, `recipient_id`; IDX `read_at`, `created_at`; Composite `(thread_id, created_at)`.

---

## `activity_events`
**Purpose:** Cross-portal notification/audit fan-out feed (polymorphic).
**UCs:** UC-XP-001 + every fan-out UC; UC-XP-025
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| user_id | CHAR(36) | NO | — | FK,IDX | FK→users.id (recipient feed) |
| portal | ENUM('provider','continuity_steward','support_steward','business_partner','admin') | NO | — | IDX | +admin · UC-XP-025 |
| event_type | ENUM('message','task','document','incident','vault','compliance','attestation','payment','account','system','referral','news','event','practitioner_unresponsive_flagged') | NO | — | IDX | UC-XP-001 |
| severity | ENUM('info','warning','critical') | NO | 'info' | IDX | |
| module | VARCHAR(64) | YES | NULL | — | |
| action | VARCHAR(64) | YES | NULL | — | |
| title | VARCHAR(191) | NO | — | — | |
| description | TEXT | YES | NULL | — | |
| linkable_type | VARCHAR(64) | YES | NULL | IDX | polymorphic type |
| linkable_id | CHAR(36) | YES | NULL | IDX | polymorphic id |
| scoped_provider_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id |
| read_at | TIMESTAMP | YES | NULL | IDX | mark_all_read |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |

**Indexes:** PK `id`; FK+IDX `user_id`, `scoped_provider_id`; IDX `portal`, `event_type`, `severity`, `read_at`, `created_at`, `(linkable_type, linkable_id)`; Composite `(user_id, created_at)`; `(portal, event_type, created_at)` — admin audit (UC-XP-025).
**Enum values:** `portal`: provider|continuity_steward|support_steward|business_partner|admin · `event_type`: message|task|document|incident|vault|compliance|attestation|payment|account|system|referral|news|event|practitioner_unresponsive_flagged · `severity`: info|warning|critical.

---

## `services`
**Purpose:** Practitioner service offerings (IBS/Services mode).
**UCs:** UC-PRV-121,122,127
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| title | VARCHAR(191) | NO | — | — | UC-PRV-121 |
| description | TEXT | YES | NULL | — | UC-PRV-121 |
| category | VARCHAR(64) | YES | NULL | IDX | UC-PRV-121 |
| price_cents | INT | YES | NULL | — | cents · UC-PRV-121 |
| price_type | ENUM('fixed','hourly','session','inquiry') | NO | 'inquiry' | — | UC-PRV-121 |
| status | ENUM('active','inactive') | NO | 'active' | IDX | UC-PRV-122 |
| is_public | TINYINT(1) | NO | 0 | IDX | published · UC-PRV-127 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `practitioner_id`; IDX `category`, `status`, `is_public`, `created_at`.
**Enum values:** `price_type`: fixed|hourly|session|inquiry · `status`: active|inactive.

---

## `service_requests`
**Purpose:** Inbound inquiries against a service (accept/decline).
**UCs:** UC-PRV-124; EMAIL T58/T59
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| service_id | CHAR(36) | NO | — | FK,IDX | FK→services.id |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id (owner) |
| inquirer_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id (null=external) |
| inquirer_name | VARCHAR(191) | YES | NULL | — | external · T58 |
| inquirer_email | VARCHAR(191) | YES | NULL | — | external · T58 |
| message | TEXT | YES | NULL | — | UC-PRV-124 |
| status | ENUM('new','accepted','declined') | NO | 'new' | IDX | UC-PRV-124 |
| response_note | VARCHAR(255) | YES | NULL | — | T59 |
| responded_at | TIMESTAMP | YES | NULL | — | UC-PRV-124 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `service_id`, `practitioner_id`, `inquirer_id`; IDX `status`, `created_at`; Composite `(practitioner_id, status)`.
**Enum values:** `status`: new|accepted|declined.

---

## `service_sessions`
**Purpose:** Booked/delivered service sessions.
**UCs:** UC-PRV-124
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| service_request_id | CHAR(36) | NO | — | FK,IDX | FK→service_requests.id |
| service_id | CHAR(36) | NO | — | FK,IDX | FK→services.id |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| client_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id |
| status | ENUM('scheduled','completed','cancelled','no_show') | NO | 'scheduled' | IDX | |
| scheduled_at | TIMESTAMP | YES | NULL | IDX | |
| completed_at | TIMESTAMP | YES | NULL | — | |
| amount_cents | INT | NO | 0 | — | cents |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `service_request_id`, `service_id`, `practitioner_id`, `client_id`; IDX `status`, `scheduled_at`, `created_at`.
**Enum values:** `status`: scheduled|completed|cancelled|no_show.

---

## `ceu_entries`
**Purpose:** Continuing-education credit log.
**UCs:** UC-PRV-150
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| title | VARCHAR(191) | NO | — | — | UC-PRV-150 |
| provider_name | VARCHAR(191) | YES | NULL | — | UC-PRV-150 |
| credit_hours | DECIMAL(5,2) | YES | NULL | — | hours (time, not money) · UC-PRV-150 |
| completed_on | DATE | YES | NULL | IDX | UC-PRV-150 |
| expires_on | DATE | YES | NULL | IDX | UC-PRV-150 |
| certificate_ref | VARCHAR(255) | YES | NULL | — | file ref |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `practitioner_id`; IDX `completed_on`, `expires_on`, `created_at`.
> `credit_hours` is the single allowed DECIMAL — a time quantity, not currency.

---

## `bp_jobs`
**Purpose:** Practitioner-posted support requests (job board).
**UCs:** UC-PRV-130,134; UC-BP find-jobs
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| title | VARCHAR(191) | NO | — | — | UC-PRV-130 |
| category | VARCHAR(64) | NO | — | IDX | UC-PRV-130 |
| description | TEXT | YES | NULL | — | UC-PRV-130 |
| budget_type | ENUM('fixed','hourly','retainer') | NO | 'fixed' | IDX | UC-PRV-130 |
| budget_amount_cents | INT | YES | NULL | — | cents · UC-PRV-130 |
| currency | VARCHAR(3) | NO | 'USD' | — | |
| location_pref | VARCHAR(40) | YES | NULL | — | UC-PRV-130 |
| status | ENUM('draft','open','paused','closed','filled','cancelled') | NO | 'open' | IDX | UC-PRV-134 |
| is_urgent | TINYINT(1) | NO | 0 | IDX | UC-PRV-130 |
| proposals_count | INT | NO | 0 | — | denormalized counter |
| posted_at | TIMESTAMP | YES | NULL | IDX | UC-PRV-130 |
| closes_at | TIMESTAMP | YES | NULL | — | UC-PRV-130 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `practitioner_id`; IDX `category`, `budget_type`, `status`, `is_urgent`, `posted_at`, `created_at`; Composite `(status, category)`.
**Enum values:** `budget_type`: fixed|hourly|retainer · `status`: draft|open|paused|closed|filled|cancelled.

---

## `bp_proposals`
**Purpose:** BP proposals against jobs.
**UCs:** UC-PRV-132,133; UC-BP submit_proposal,update_proposal
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| job_id | CHAR(36) | NO | — | FK,IDX | FK→bp_jobs.id |
| bp_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| cover_letter | TEXT | YES | NULL | — | submit_proposal |
| proposed_rate_cents | INT | YES | NULL | — | cents |
| proposed_rate_type | ENUM('fixed','hourly','retainer') | NO | 'fixed' | — | |
| status | ENUM('pending','under_review','accepted','declined','withdrawn') | NO | 'pending' | IDX | UC-PRV-132,133 |
| submitted_at | TIMESTAMP | YES | NULL | IDX | |
| responded_at | TIMESTAMP | YES | NULL | — | UC-PRV-132 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `job_id`, `bp_id`; IDX `status`, `submitted_at`, `created_at`; Composite `(job_id, status)`, `(bp_id, status)`; UQ `(job_id, bp_id)`.
**Enum values:** `proposed_rate_type`: fixed|hourly|retainer · `status`: pending|under_review|accepted|declined|withdrawn.

---

## `bp_contracts`
**Purpose:** Active/past contracts (job → proposal → contract).
**UCs:** UC-PRV-132,135,137,138; UC-XP-010
**Meta table:** yes → `contract_meta`

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| job_id | CHAR(36) | YES | NULL | FK,IDX | FK→bp_jobs.id |
| proposal_id | CHAR(36) | YES | NULL | FK,IDX | FK→bp_proposals.id |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| bp_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| title | VARCHAR(191) | NO | — | — | UC-XP-010 |
| status | ENUM('draft','active','completed','cancelled') | NO | 'draft' | IDX | UC-PRV-137,138 |
| total_value_cents | INT | NO | 0 | — | cents |
| signed_at | TIMESTAMP | YES | NULL | — | UC-PRV-137 |
| cancelled_at | TIMESTAMP | YES | NULL | — | UC-PRV-138 |
| started_at | TIMESTAMP | YES | NULL | — | |
| completed_at | TIMESTAMP | YES | NULL | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `job_id`, `proposal_id`, `practitioner_id`, `bp_id`; IDX `status`, `created_at`; Composite `(bp_id, status)`, `(practitioner_id, status)`.
**Enum values:** `status`: draft|active|completed|cancelled.

---

## `contract_meta`
**Purpose:** Sparse contract attributes (terms, cancel reason, signature meta).
**UCs:** UC-PRV-137,138
**Meta table:** self

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| contract_id | CHAR(36) | NO | — | FK,IDX | FK→bp_contracts.id |
| meta_key | VARCHAR(191) | NO | — | UQ* | |
| meta_value | LONGTEXT | YES | NULL | — | |
| meta_type | ENUM('string','int','boolean','json','timestamp') | NO | 'string' | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `contract_id`; UQ `(contract_id, meta_key)`.
**Known keys:** `terms` string, `cancel_reason` string, `signature_ip` string, `engagement_type` string, `team_assignment` json.

---

## `bp_milestones`
**Purpose:** Contract milestones (submit/approve).
**UCs:** UC-PRV-135; UC-BP milestone submit/approve
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| contract_id | CHAR(36) | NO | — | FK,IDX | FK→bp_contracts.id |
| title | VARCHAR(191) | NO | — | — | |
| description | TEXT | YES | NULL | — | |
| amount_cents | INT | NO | 0 | — | cents |
| status | ENUM('pending','submitted','approved','rejected','paid') | NO | 'pending' | IDX | UC-PRV-135 |
| assigned_member_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id (agency team) |
| due_at | TIMESTAMP | YES | NULL | IDX | |
| submitted_at | TIMESTAMP | YES | NULL | — | |
| approved_at | TIMESTAMP | YES | NULL | — | UC-PRV-135 |
| sort_order | INT | NO | 0 | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `contract_id`, `assigned_member_id`; IDX `status`, `due_at`, `created_at`; Composite `(contract_id, status)`.
**Enum values:** `status`: pending|submitted|approved|rejected|paid.

---

## `bp_invoices`
**Purpose:** BP-issued invoices.
**UCs:** UC-BP invoice_create,update_draft,invoice_send,invoice_void; UC-XP-011
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| bp_id | CHAR(36) | NO | — | FK,IDX | FK→users.id (issuer) |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id (payer) |
| contract_id | CHAR(36) | YES | NULL | FK,IDX | FK→bp_contracts.id |
| invoice_number | VARCHAR(40) | YES | NULL | UQ | |
| status | ENUM('draft','sent','paid','overdue','void') | NO | 'draft' | IDX | invoice lifecycle |
| subtotal_cents | INT | NO | 0 | — | cents |
| total_cents | INT | NO | 0 | — | cents · UC-XP-011 |
| currency | VARCHAR(3) | NO | 'USD' | — | |
| notes | TEXT | YES | NULL | — | update_draft |
| issued_at | TIMESTAMP | YES | NULL | — | invoice_send |
| due_at | TIMESTAMP | YES | NULL | IDX | update_draft |
| paid_at | TIMESTAMP | YES | NULL | — | UC-PRV-136 |
| voided_at | TIMESTAMP | YES | NULL | — | invoice_void |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `bp_id`, `practitioner_id`, `contract_id`; UQ `invoice_number`; IDX `status`, `due_at`, `created_at`; Composite `(practitioner_id, status)`, `(bp_id, status)`.
**Enum values:** `status`: draft|sent|paid|overdue|void.

---

## `bp_invoice_line_items`
**Purpose:** Line items per invoice.
**UCs:** UC-BP invoice_create,update_draft
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| invoice_id | CHAR(36) | NO | — | FK,IDX | FK→bp_invoices.id |
| description | VARCHAR(255) | NO | — | — | |
| quantity | INT | NO | 1 | — | |
| unit_amount_cents | INT | NO | 0 | — | cents |
| line_total_cents | INT | NO | 0 | — | cents |
| sort_order | INT | NO | 0 | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `invoice_id`.

---

## `bp_invoice_payments`
**Purpose:** Payments recorded against invoices.
**UCs:** UC-PRV-136; UC-BP invoice_mark_paid_manually; UC-XP-012
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| invoice_id | CHAR(36) | NO | — | FK,IDX | FK→bp_invoices.id |
| payer_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| amount_cents | INT | NO | 0 | — | cents |
| method | ENUM('stripe','manual','ach','card') | NO | 'stripe' | — | invoice_mark_paid_manually |
| status | ENUM('pending','succeeded','failed','refunded') | NO | 'pending' | IDX | UC-XP-012 |
| stripe_payment_intent | VARCHAR(64) | YES | NULL | — | |
| paid_at | TIMESTAMP | YES | NULL | — | UC-PRV-136 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |

**Indexes:** PK `id`; FK+IDX `invoice_id`, `payer_id`; IDX `status`, `created_at`.
**Enum values:** `method`: stripe|manual|ach|card · `status`: pending|succeeded|failed|refunded.

---

## `bp_payouts`
**Purpose:** Stripe payouts to BP.
**UCs:** UC-ADM-044,045; UC-XP-012
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| bp_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| amount_cents | INT | NO | 0 | — | cents |
| currency | VARCHAR(3) | NO | 'USD' | — | |
| status | ENUM('pending','in_transit','paid','failed','cancelled') | NO | 'pending' | IDX | UC-ADM-044,045 |
| description | VARCHAR(255) | YES | NULL | — | |
| stripe_payout_id | VARCHAR(64) | YES | NULL | — | UC-ADM-045 |
| scheduled_at | TIMESTAMP | YES | NULL | — | |
| paid_at | TIMESTAMP | YES | NULL | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `bp_id`; IDX `status`, `created_at`; Composite `(status, created_at)`.
**Enum values:** `status`: pending|in_transit|paid|failed|cancelled.

---

## `bp_tax_documents`
**Purpose:** 1099/W-9/EIN tax documents.
**UCs:** UC-BP-070; update_ssn/update_ein
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| bp_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| doc_type | ENUM('w9','1099','ein_doc','other') | NO | — | IDX | UC-BP-070 |
| status | ENUM('available','pending','verified') | NO | 'pending' | IDX | |
| download_url | VARCHAR(255) | YES | NULL | — | |
| year | INT | YES | NULL | — | tax year |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `bp_id`; IDX `doc_type`, `status`, `created_at`.
**Enum values:** `doc_type`: w9|1099|ein_doc|other · `status`: available|pending|verified.
> Masked identifiers (`ssn_last4`, `ein_last4`) live in `user_meta`, never plaintext here.

---

## `bp_team_members`
**Purpose:** Agency team roster + permission role.
**UCs:** UC-XP-018; UC-BP team (remove_member)
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| agency_id | CHAR(36) | NO | — | FK,IDX | FK→users.id (owner) |
| member_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| permission_role | ENUM('admin','manager','specialist','viewer') | NO | 'specialist' | IDX | UC-BP team |
| department | VARCHAR(64) | YES | NULL | — | |
| status | ENUM('active','idle','inactive') | NO | 'active' | IDX | |
| joined_at | TIMESTAMP | YES | NULL | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete (remove_member) |

**Indexes:** PK `id`; FK+IDX `agency_id`, `member_id`; IDX `permission_role`, `status`; UQ `(agency_id, member_id)`.
**Enum values:** `permission_role`: admin|manager|specialist|viewer · `status`: active|idle|inactive.

---

## `bp_team_invitations`
**Purpose:** Pending agency team invites.
**UCs:** UC-XP-018; EMAIL T41
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| agency_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| invitee_email | VARCHAR(191) | NO | — | — | T41 |
| permission_role | ENUM('admin','manager','specialist','viewer') | NO | 'specialist' | — | |
| status | ENUM('pending','accepted','declined','expired') | NO | 'pending' | IDX | |
| invite_token | VARCHAR(128) | NO | — | UQ | |
| expires_at | TIMESTAMP | YES | NULL | — | |
| responded_at | TIMESTAMP | YES | NULL | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |

**Indexes:** PK `id`; FK+IDX `agency_id`; UQ `invite_token`; IDX `status`, `created_at`.
**Enum values:** `permission_role`: admin|manager|specialist|viewer · `status`: pending|accepted|declined|expired.

---

## `bp_saved_jobs`
**Purpose:** BP-saved job board entries.
**UCs:** UC-BP save_job
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| bp_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| job_id | CHAR(36) | NO | — | FK,IDX | FK→bp_jobs.id |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |

**Indexes:** PK `id`; FK+IDX `bp_id`, `job_id`; UQ `(bp_id, job_id)`.

---

## `cs_invoices`
**Purpose:** CS-issued invoices to practitioners.
**UCs:** UC-CS finances; UC-ADM-040
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| cs_id | CHAR(36) | NO | — | FK,IDX | FK→users.id (issuer) |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id (payer) |
| invoice_number | VARCHAR(40) | YES | NULL | UQ | |
| status | ENUM('draft','sent','paid','overdue','void') | NO | 'draft' | IDX | |
| total_cents | INT | NO | 0 | — | cents |
| currency | VARCHAR(3) | NO | 'USD' | — | |
| issued_at | TIMESTAMP | YES | NULL | — | |
| due_at | TIMESTAMP | YES | NULL | IDX | |
| paid_at | TIMESTAMP | YES | NULL | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `cs_id`, `practitioner_id`; UQ `invoice_number`; IDX `status`, `due_at`, `created_at`; Composite `(practitioner_id, status)`.
**Enum values:** `status`: draft|sent|paid|overdue|void.

---

## `cs_payouts`
**Purpose:** Stripe payouts to CS.
**UCs:** UC-ADM-044,045
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| cs_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| amount_cents | INT | NO | 0 | — | cents |
| currency | VARCHAR(3) | NO | 'USD' | — | |
| status | ENUM('pending','in_transit','paid','failed','cancelled') | NO | 'pending' | IDX | UC-ADM-044,045 |
| description | VARCHAR(255) | YES | NULL | — | |
| stripe_payout_id | VARCHAR(64) | YES | NULL | — | |
| scheduled_at | TIMESTAMP | YES | NULL | — | |
| paid_at | TIMESTAMP | YES | NULL | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `cs_id`; IDX `status`, `created_at`; Composite `(status, created_at)`.
**Enum values:** `status`: pending|in_transit|paid|failed|cancelled.

---

## `practitioner_payment_methods`
**Purpose:** Stored payment methods for practitioners.
**UCs:** UC-PRV-141
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| label | VARCHAR(64) | YES | NULL | — | "Visa ••4242" · UC-PRV-141 |
| brand | VARCHAR(32) | YES | NULL | — | |
| last4 | VARCHAR(4) | YES | NULL | — | |
| stripe_pm_id | VARCHAR(64) | YES | NULL | — | |
| is_default | TINYINT(1) | NO | 0 | IDX | set_default_method · UC-PRV-141 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete (remove_payment_method) |

**Indexes:** PK `id`; FK+IDX `practitioner_id`; IDX `is_default`, `created_at`.

---

## `practitioner_payments`
**Purpose:** Subscription + CS-fee payment ledger for practitioners.
**UCs:** UC-PRV-003,004,136,144,145,208,210; UC-ADM-040,041,043
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| payment_method_id | CHAR(36) | YES | NULL | FK,IDX | FK→practitioner_payment_methods.id |
| kind | ENUM('subscription','maat_addon','cs_fee','bp_invoice','refund') | NO | 'subscription' | IDX | UC-PRV-003,210,136 |
| amount_cents | INT | NO | 0 | — | cents |
| currency | VARCHAR(3) | NO | 'USD' | — | |
| status | ENUM('paid','failed','refunded','partially_refunded','pending') | NO | 'paid' | IDX | UC-ADM-041,043 |
| payment_method_label | VARCHAR(64) | YES | NULL | — | snapshot |
| stripe_charge_id | VARCHAR(64) | YES | NULL | — | |
| paid_at | TIMESTAMP | YES | NULL | IDX | UC-ADM-003 MRR |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `practitioner_id`, `payment_method_id`; IDX `kind`, `status`, `paid_at`, `created_at`; Composite `(practitioner_id, kind)`, `(status, paid_at)`.
**Enum values:** `kind`: subscription|maat_addon|cs_fee|bp_invoice|refund · `status`: paid|failed|refunded|partially_refunded|pending.

---

## `ss_provider_checkins`
**Purpose:** SS check-in events per practitioner.
**UCs:** UC-SS log_checkin; UC-SS-015
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| ss_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| status | ENUM('ok','concern','unreachable') | NO | 'ok' | IDX | log_checkin |
| note | TEXT | YES | NULL | — | |
| checked_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |

**Indexes:** PK `id`; FK+IDX `ss_id`, `practitioner_id`; IDX `status`, `checked_at`; Composite `(practitioner_id, checked_at)`.
**Enum values:** `status`: ok|concern|unreachable.

---

## `ss_provider_notes`
**Purpose:** SS private notes per practitioner.
**UCs:** UC-SS add_incident_note / provider notes
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| ss_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| body | TEXT | NO | — | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `ss_id`, `practitioner_id`; IDX `created_at`.

---

## `news_posts`
**Purpose:** News & Resources feed posts.
**UCs:** UC-news feed read/post
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| author_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id (null=system) |
| title | VARCHAR(191) | NO | — | — | |
| body | TEXT | YES | NULL | — | |
| post_type | ENUM('post','poll','announcement') | NO | 'post' | IDX | |
| role_visibility | VARCHAR(40) | NO | 'all' | IDX | |
| published | TINYINT(1) | NO | 1 | IDX | |
| pinned | TINYINT(1) | NO | 0 | IDX | |
| published_at | TIMESTAMP | YES | NULL | IDX | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `author_id`; IDX `post_type`, `role_visibility`, `published`, `pinned`, `published_at`, `created_at`.
**Enum values:** `post_type`: post|poll|announcement.

---

## `news_events`
**Purpose:** Events listings.
**UCs:** UC-PRV events register/cancel
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| title | VARCHAR(191) | NO | — | — | |
| description | TEXT | YES | NULL | — | |
| location | VARCHAR(191) | YES | NULL | — | |
| starts_at | TIMESTAMP | YES | NULL | IDX | |
| ends_at | TIMESTAMP | YES | NULL | — | |
| role_visibility | VARCHAR(40) | NO | 'all' | IDX | |
| published | TINYINT(1) | NO | 1 | IDX | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; IDX `starts_at`, `role_visibility`, `published`, `created_at`.

---

## `news_comments`
**Purpose:** Comments on news posts.
**UCs:** UC-news comment
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| post_id | CHAR(36) | NO | — | FK,IDX | FK→news_posts.id |
| author_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| body | TEXT | NO | — | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `post_id`, `author_id`; IDX `created_at`; Composite `(post_id, created_at)`.

---

## `news_reactions`
**Purpose:** Reactions on news posts.
**UCs:** UC-news react
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| post_id | CHAR(36) | NO | — | FK,IDX | FK→news_posts.id |
| user_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| reaction | VARCHAR(32) | NO | — | IDX | like/insightful/etc |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |

**Indexes:** PK `id`; FK+IDX `post_id`, `user_id`; IDX `reaction`; UQ `(post_id, user_id, reaction)`.

---

## `news_poll_votes`
**Purpose:** Poll votes on poll-type posts.
**UCs:** UC-news poll vote
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| post_id | CHAR(36) | NO | — | FK,IDX | FK→news_posts.id |
| user_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| option_key | VARCHAR(64) | NO | — | — | chosen option |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |

**Indexes:** PK `id`; FK+IDX `post_id`, `user_id`; UQ `(post_id, user_id)`.

---

## `news_trending_topics`
**Purpose:** Trending topic registry.
**UCs:** UC-news trending
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| topic | VARCHAR(191) | NO | — | UQ | |
| score | INT | NO | 0 | IDX | ranking |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; UQ `topic`; IDX `score`.

---

## `news_library_items`
**Purpose:** Resource library entries.
**UCs:** UC-news library
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| title | VARCHAR(191) | NO | — | — | |
| category | VARCHAR(64) | YES | NULL | IDX | |
| url | VARCHAR(255) | YES | NULL | — | |
| file_ref | VARCHAR(255) | YES | NULL | — | |
| role_visibility | VARCHAR(40) | NO | 'all' | IDX | |
| sort_order | INT | NO | 0 | — | |
| published | TINYINT(1) | NO | 1 | IDX | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; IDX `category`, `role_visibility`, `published`, `created_at`.

---

## `complaints`
**Purpose:** Support tickets + feedback + complaints (unified).
**UCs:** UC-ADM-050..057; UC-XP-015
**Meta table:** yes → `complaint_meta`

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| submitter_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| subject | VARCHAR(191) | NO | — | — | UC-ADM-050 |
| body | TEXT | NO | — | — | |
| category | ENUM('support_ticket','feedback','complaint') | NO | 'support_ticket' | IDX | UC-ADM-050 |
| submission_channel | VARCHAR(40) | NO | 'ticket' | IDX | UC-ADM-050 |
| status | ENUM('open','in_progress','resolved','closed') | NO | 'open' | IDX | UC-ADM-055 |
| priority | ENUM('low','normal','high','urgent') | NO | 'normal' | IDX | UC-ADM-056 |
| assigned_to | CHAR(36) | YES | NULL | FK,IDX | FK→users.id · UC-ADM-052 |
| escalated_at | TIMESTAMP | YES | NULL | — | UC-ADM-056 |
| resolved_at | TIMESTAMP | YES | NULL | — | UC-ADM-055 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `submitter_id`, `assigned_to`; IDX `category`, `submission_channel`, `status`, `priority`, `created_at`; Composite `(status, created_at)`, `(submitter_id, status)`.
**Enum values:** `category`: support_ticket|feedback|complaint · `status`: open|in_progress|resolved|closed · `priority`: low|normal|high|urgent.

---

## `complaint_meta`
**Purpose:** Sparse complaint attributes (satisfaction, escalation, channel meta).
**UCs:** UC-ADM-056,057; EMAIL T48
**Meta table:** self

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| complaint_id | CHAR(36) | NO | — | FK,IDX | FK→complaints.id |
| meta_key | VARCHAR(191) | NO | — | UQ* | |
| meta_value | LONGTEXT | YES | NULL | — | |
| meta_type | ENUM('string','int','boolean','json','timestamp') | NO | 'string' | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `complaint_id`; UQ `(complaint_id, meta_key)`.
**Known keys:** `satisfaction_rating` int, `escalation_note` string, `source_url` string, `first_reply_at` timestamp.

---

## `complaint_replies`
**Purpose:** Public + internal reply thread on a complaint.
**UCs:** UC-ADM-051,053,054; UC-XP-015
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| complaint_id | CHAR(36) | NO | — | FK,IDX | FK→complaints.id |
| author_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| body | TEXT | NO | — | — | |
| is_internal | TINYINT(1) | NO | 0 | IDX | UC-ADM-054 (never shown to submitter) |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |

**Indexes:** PK `id`; FK+IDX `complaint_id`, `author_id`; IDX `is_internal`, `created_at`; Composite `(complaint_id, created_at)`.

---

## `help_articles`
**Purpose:** Help/FAQ content with role visibility.
**UCs:** UC-ADM-058,059,060
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| category | VARCHAR(64) | YES | NULL | IDX | UC-ADM-058 |
| title | VARCHAR(191) | NO | — | — | UC-ADM-058 |
| body | LONGTEXT | NO | — | — | UC-ADM-058 |
| role_visibility | VARCHAR(40) | NO | 'all' | IDX | UC-ADM-058 |
| sort_order | INT | NO | 0 | IDX | UC-ADM-060 |
| published | TINYINT(1) | NO | 1 | IDX | UC-ADM-059 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; IDX `category`, `role_visibility`, `published`, `sort_order`, `created_at`.

---

## `admin_audit_log`
**Purpose:** Immutable log of every admin mutation (polymorphic target).
**UCs:** UC-ADM-023..029,031..033,042,043,045
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| admin_id | CHAR(36) | NO | — | FK,IDX | FK→users.id |
| action | VARCHAR(64) | NO | — | IDX | lock_user/refund_payment/… |
| linkable_type | VARCHAR(64) | YES | NULL | IDX | polymorphic type (user/payment/role/complaint/package) |
| linkable_id | CHAR(36) | YES | NULL | IDX | polymorphic id |
| target_user_id | CHAR(36) | YES | NULL | FK,IDX | FK→users.id (affected user) |
| meta_json | JSON | YES | NULL | — | params (reason, amount, before/after) |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |

**Indexes:** PK `id`; FK+IDX `admin_id`, `target_user_id`; IDX `action`, `created_at`, `(linkable_type, linkable_id)`; Composite `(admin_id, created_at)`.
> Append-only — no `updated_at`/`deleted_at`.

---

## `package_overrides`
**Purpose:** Runtime tier price/feature/limit overrides.
**UCs:** UC-ADM-011,012,013
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| tier | VARCHAR(40) | NO | — | UQ | access/practice/maat_addon/cs_business/bp · UC-ADM-010 |
| price_monthly_cents | INT | YES | NULL | — | UC-ADM-011 |
| price_annual_cents | INT | YES | NULL | — | UC-ADM-011 |
| feature_flags | JSON | YES | NULL | — | UC-ADM-012 |
| limits | JSON | YES | NULL | — | UC-ADM-013 |
| effective_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | UC-ADM-011 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; UQ `tier`.

---

## `roles`
**Purpose:** Assignable role definitions (system + custom).
**UCs:** UC-ADM-030,031,033
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| name | VARCHAR(64) | NO | — | UQ | UC-ADM-031 |
| system_role | TINYINT(1) | NO | 0 | IDX | non-deletable when 1 · UC-ADM-033 |
| description | VARCHAR(255) | YES | NULL | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete (custom roles · UC-ADM-033) |

**Indexes:** PK `id`; UQ `name`; IDX `system_role`.

---

## `role_permissions`
**Purpose:** Per-role permission grants.
**UCs:** UC-ADM-032
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| role_id | CHAR(36) | NO | — | FK,IDX | FK→roles.id |
| permission_key | VARCHAR(64) | NO | — | IDX | users.manage/payments.refund/… · UC-ADM-032 |
| granted | TINYINT(1) | NO | 0 | — | UC-ADM-032 |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |

**Indexes:** PK `id`; FK+IDX `role_id`; IDX `permission_key`; UQ `(role_id, permission_key)`.

---

## `stripe_webhook_events`
**Purpose:** Append-only Stripe webhook event log.
**UCs:** UC-ADM-046
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| stripe_event_id | VARCHAR(64) | NO | — | UQ | idempotency · UC-ADM-046 |
| event_type | VARCHAR(64) | NO | — | IDX | UC-ADM-046 |
| payload_json | JSON | YES | NULL | — | raw payload |
| processed | TINYINT(1) | NO | 0 | IDX | |
| received_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| processed_at | TIMESTAMP | YES | NULL | — | |

**Indexes:** PK `id`; UQ `stripe_event_id`; IDX `event_type`, `processed`, `received_at`.

---

## `profile_edit_authorizations`
**Purpose:** Practitioner grants letting a CS/SS edit specific profile sections.
**UCs:** UC-PRV profile-edit authorization; CS/SS assisted edit
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | NO | uuid() | PK | |
| practitioner_id | CHAR(36) | NO | — | FK,IDX | FK→users.id (grantor) |
| authorized_user_id | CHAR(36) | NO | — | FK,IDX | FK→users.id (CS/SS grantee) |
| scope | JSON | YES | NULL | — | editable section keys |
| status | ENUM('active','revoked') | NO | 'active' | IDX | |
| granted_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| revoked_at | TIMESTAMP | YES | NULL | — | |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | |
| deleted_at | TIMESTAMP | YES | NULL | IDX | soft delete |

**Indexes:** PK `id`; FK+IDX `practitioner_id`, `authorized_user_id`; IDX `status`, `created_at`; UQ `(practitioner_id, authorized_user_id)`.
**Enum values:** `status`: active|revoked.

---

# Section 3 — `user_meta` Known Keys (notification gate keys + profile keys)

Every `notify_*` key gating an email in `EMAIL_TEMPLATES_PROMPT.md` §B exists here as a `boolean` row (default-on when unset). Profile/sparse keys follow.

| meta_key | meta_type | Source | Used by |
|---|---|---|---|
| notify_email | boolean | settings | **master** email switch — false suppresses all gated email |
| notify_sms | boolean | settings | master SMS switch |
| notify_critical | boolean | settings | always-on (disabled in UI) — incident alerts |
| notify_in_app_all | boolean | settings | in-app fan-out master |
| notify_incident | boolean | UC-XP-004 | T26–31 |
| notify_message | boolean | messaging | T42,43,58,59 |
| notify_task | boolean | UC-CS-031 | T29 |
| notify_assignment | boolean | UC-PRV-050 | T18,20,21,22,24,36,60,61 |
| notify_attestation | boolean | UC-PRV-040 | T13,15 |
| notify_attestation_request | boolean | UC-PRV-038 | re-attest request |
| notify_plan_change | boolean | UC-PRV-036 | T10,11,12,16,62,64 |
| notify_plan_review | boolean | UC-XP-014 | T14 |
| notify_role_change | boolean | UC-CS-023 | T23 |
| notify_change_request | boolean | UC-CS-023 | fee/role change |
| notify_vault_unlock | boolean | UC-XP-006 | T28 |
| notify_docs_accessed | boolean | UC-PRV-075 | T63 |
| notify_invoice | boolean | UC-XP-011 | T38 |
| notify_payment | boolean | UC-XP-012 | T37,39,40,51,52,54,55,69 |
| notify_proposal | boolean | UC-PRV-132 | T32,33,34 |
| notify_new_job | boolean | UC-BP find-jobs | job matches |
| notify_agreement | boolean | UC-PRV-108 | T35,44,45,66,67 |
| notify_checkin | boolean | UC-SS log_checkin | SS check-ins |
| notify_activation | boolean | UC-XP-019 | T25 |
| notify_practitioner_cs_unresponsive | boolean | UC-XP-016 | T65 |
| notify_summary | boolean | digest opt-in | T56,57 |
| notify_platform | boolean | system | platform announcements |
| notify_views | boolean | profile | profile-view alerts |
| notify_info_update | boolean | profile | info-update alerts |
| notify_announcement | boolean | news | news announcements |
| practitioner_specialties | json | UC-PRV-012 | profile, network filter |
| availability_status | string | UC-PRV-016 | profile, public |
| accepting_status | boolean | UC-PRV-016 | public profile |
| intent_segment | string | UC-PRV-215 | onboarding segmentation |
| ssn_last4 | string | UC-BP-070 | masked tax id |
| ein_last4 | string | UC-BP-070 | masked tax id |
| w9_last4 | string | UC-BP-070 | masked tax id |

---

# Section A — Relationship Map (all FKs)

On-delete policy: meta/child/join tables → **CASCADE**; content references to `users` → **RESTRICT** (preserve audit); optional/nullable references (assignee, verifier, holder) → **SET NULL**.

| Table | Column | References | On Delete |
|---|---|---|---|
| users | linked_provider_id | users.id | SET NULL |
| users | invited_by_id | users.id | SET NULL |
| user_meta | user_id | users.id | CASCADE |
| user_roles | user_id | users.id | CASCADE |
| user_sessions | user_id | users.id | CASCADE |
| user_preferences | user_id | users.id | CASCADE |
| password_reset_tokens | user_id | users.id | CASCADE |
| mfa_tokens | user_id | users.id | CASCADE |
| continuity_plans | practitioner_id | users.id | RESTRICT |
| plan_meta | plan_id | continuity_plans.id | CASCADE |
| plan_stewards | plan_id | continuity_plans.id | CASCADE |
| plan_stewards | steward_id | users.id | RESTRICT |
| plan_tasks | plan_id | continuity_plans.id | CASCADE |
| plan_tasks | steward_id | users.id | SET NULL |
| plan_incident_configs | plan_id | continuity_plans.id | CASCADE |
| critical_incidents | plan_id | continuity_plans.id | RESTRICT |
| critical_incidents | practitioner_id | users.id | RESTRICT |
| critical_incidents | reported_by_id | users.id | RESTRICT |
| critical_incidents | verified_by_id | users.id | SET NULL |
| incident_meta | incident_id | critical_incidents.id | CASCADE |
| incident_tasks | incident_id | critical_incidents.id | CASCADE |
| incident_tasks | assigned_to_id | users.id | SET NULL |
| incident_updates | incident_id | critical_incidents.id | CASCADE |
| incident_updates | actor_id | users.id | SET NULL |
| vault_items | practitioner_id | users.id | RESTRICT |
| vault_item_meta | vault_item_id | vault_items.id | CASCADE |
| vault_access_log | vault_item_id | vault_items.id | SET NULL |
| vault_access_log | practitioner_id | users.id | RESTRICT |
| vault_access_log | actor_id | users.id | RESTRICT |
| vault_access_log | recipient_id | users.id | SET NULL |
| continuity_documents | plan_id | continuity_plans.id | SET NULL |
| continuity_documents | practitioner_id | users.id | RESTRICT |
| continuity_documents | amends_document_id | continuity_documents.id | SET NULL |
| continuity_documents | holder_steward_id | users.id | SET NULL |
| document_signatures | document_id | continuity_documents.id | CASCADE |
| document_signatures | signer_id | users.id | RESTRICT |
| network_connections | user_id | users.id | CASCADE |
| network_connections | connected_user_id | users.id | CASCADE |
| network_requests | requester_id | users.id | CASCADE |
| network_requests | recipient_id | users.id | SET NULL |
| shadow_connections | user_id | users.id | CASCADE |
| shadow_connections | shadow_user_id | users.id | SET NULL |
| referrals | sender_id | users.id | RESTRICT |
| referrals | recipient_id | users.id | RESTRICT |
| referral_meta | referral_id | referrals.id | CASCADE |
| message_threads | created_by_id | users.id | RESTRICT |
| messages | thread_id | message_threads.id | CASCADE |
| messages | sender_id | users.id | RESTRICT |
| messages | recipient_id | users.id | SET NULL |
| activity_events | user_id | users.id | CASCADE |
| activity_events | scoped_provider_id | users.id | SET NULL |
| services | practitioner_id | users.id | CASCADE |
| service_requests | service_id | services.id | CASCADE |
| service_requests | practitioner_id | users.id | RESTRICT |
| service_requests | inquirer_id | users.id | SET NULL |
| service_sessions | service_request_id | service_requests.id | CASCADE |
| service_sessions | service_id | services.id | CASCADE |
| service_sessions | practitioner_id | users.id | RESTRICT |
| service_sessions | client_id | users.id | SET NULL |
| ceu_entries | practitioner_id | users.id | CASCADE |
| bp_jobs | practitioner_id | users.id | RESTRICT |
| bp_proposals | job_id | bp_jobs.id | CASCADE |
| bp_proposals | bp_id | users.id | RESTRICT |
| bp_contracts | job_id | bp_jobs.id | SET NULL |
| bp_contracts | proposal_id | bp_proposals.id | SET NULL |
| bp_contracts | practitioner_id | users.id | RESTRICT |
| bp_contracts | bp_id | users.id | RESTRICT |
| contract_meta | contract_id | bp_contracts.id | CASCADE |
| bp_milestones | contract_id | bp_contracts.id | CASCADE |
| bp_milestones | assigned_member_id | users.id | SET NULL |
| bp_invoices | bp_id | users.id | RESTRICT |
| bp_invoices | practitioner_id | users.id | RESTRICT |
| bp_invoices | contract_id | bp_contracts.id | SET NULL |
| bp_invoice_line_items | invoice_id | bp_invoices.id | CASCADE |
| bp_invoice_payments | invoice_id | bp_invoices.id | CASCADE |
| bp_invoice_payments | payer_id | users.id | RESTRICT |
| bp_payouts | bp_id | users.id | RESTRICT |
| bp_tax_documents | bp_id | users.id | CASCADE |
| bp_team_members | agency_id | users.id | CASCADE |
| bp_team_members | member_id | users.id | CASCADE |
| bp_team_invitations | agency_id | users.id | CASCADE |
| bp_saved_jobs | bp_id | users.id | CASCADE |
| bp_saved_jobs | job_id | bp_jobs.id | CASCADE |
| cs_invoices | cs_id | users.id | RESTRICT |
| cs_invoices | practitioner_id | users.id | RESTRICT |
| cs_payouts | cs_id | users.id | RESTRICT |
| practitioner_payment_methods | practitioner_id | users.id | CASCADE |
| practitioner_payments | practitioner_id | users.id | RESTRICT |
| practitioner_payments | payment_method_id | practitioner_payment_methods.id | SET NULL |
| ss_provider_checkins | ss_id | users.id | RESTRICT |
| ss_provider_checkins | practitioner_id | users.id | RESTRICT |
| ss_provider_notes | ss_id | users.id | CASCADE |
| ss_provider_notes | practitioner_id | users.id | RESTRICT |
| news_posts | author_id | users.id | SET NULL |
| news_comments | post_id | news_posts.id | CASCADE |
| news_comments | author_id | users.id | CASCADE |
| news_reactions | post_id | news_posts.id | CASCADE |
| news_reactions | user_id | users.id | CASCADE |
| news_poll_votes | post_id | news_posts.id | CASCADE |
| news_poll_votes | user_id | users.id | CASCADE |
| complaints | submitter_id | users.id | RESTRICT |
| complaints | assigned_to | users.id | SET NULL |
| complaint_meta | complaint_id | complaints.id | CASCADE |
| complaint_replies | complaint_id | complaints.id | CASCADE |
| complaint_replies | author_id | users.id | RESTRICT |
| admin_audit_log | admin_id | users.id | RESTRICT |
| admin_audit_log | target_user_id | users.id | SET NULL |
| role_permissions | role_id | roles.id | CASCADE |
| profile_edit_authorizations | practitioner_id | users.id | CASCADE |
| profile_edit_authorizations | authorized_user_id | users.id | CASCADE |

> `news_events`, `news_trending_topics`, `news_library_items`, `package_overrides`, `roles`, `stripe_webhook_events`, `user_preferences (uq only)` have no outbound content FKs beyond those listed.

---

# Section B — Full Index List (non-trivial: composite + unique + key secondary)

Every table has PK `id` and an index on each FK column (per convention). Listed below are the **unique constraints** and **composite/secondary** indexes that matter for query plans.

| Table | Index | Columns | Type | Reason |
|---|---|---|---|---|
| users | uq_users_email | email | UNIQUE | login lookup |
| users | uq_users_slug | slug | UNIQUE | public profile routing |
| users | ix_users_role_created | role, created_at | COMPOSITE | signup trend (UC-ADM-002) |
| user_meta | uq_user_meta | user_id, meta_key | UNIQUE | one value per key |
| user_roles | uq_user_roles | user_id, role | UNIQUE | one row per role |
| user_sessions | uq_session_token | session_token | UNIQUE | session lookup |
| user_preferences | uq_user_pref | user_id | UNIQUE | one prefs row |
| password_reset_tokens | uq_pwreset_token | token | UNIQUE | reset lookup |
| mfa_tokens | uq_mfa_user | user_id | UNIQUE | one MFA row |
| continuity_plans | ix_plan_status_review | status, annual_review_date | COMPOSITE | re-attestation sweep (UC-XP-014) |
| plan_meta | uq_plan_meta | plan_id, meta_key | UNIQUE | |
| plan_stewards | ix_plansteward_plan_status | plan_id, status | COMPOSITE | active-steward list |
| plan_incident_configs | uq_planinc | plan_id, incident_type | UNIQUE | one config per type |
| critical_incidents | ix_incident_status_reported | status, reported_at | COMPOSITE | oversight (UC-ADM-004) |
| incident_meta | uq_incident_meta | incident_id, meta_key | UNIQUE | |
| incident_tasks | ix_inctask_incident_status | incident_id, status | COMPOSITE | task board |
| incident_updates | ix_incupd_incident_created | incident_id, created_at | COMPOSITE | ordered timeline |
| vault_items | ix_vault_pract_zone | practitioner_id, zone | COMPOSITE | zone view |
| vault_item_meta | uq_vaultitem_meta | vault_item_id, meta_key | UNIQUE | |
| vault_access_log | ix_vaultlog_pract_created | practitioner_id, created_at | COMPOSITE | access-log view |
| continuity_documents | uq_doc_reference | reference | UNIQUE | doc ref |
| continuity_documents | ix_doc_pract_status | practitioner_id, status | COMPOSITE | doc list |
| document_signatures | uq_docsig | document_id, signer_id | UNIQUE | one sig per signer |
| network_connections | uq_netconn | user_id, connected_user_id | UNIQUE | dedupe edge |
| network_requests | uq_netreq_token | invite_token | UNIQUE | external invite |
| referrals | ix_ref_recipient_status | recipient_id, status | COMPOSITE | inbox |
| referral_meta | uq_referral_meta | referral_id, meta_key | UNIQUE | |
| messages | ix_msg_thread_created | thread_id, created_at | COMPOSITE | thread render |
| activity_events | ix_act_user_created | user_id, created_at | COMPOSITE | feed |
| activity_events | ix_act_portal_type_created | portal, event_type, created_at | COMPOSITE | admin global audit (UC-XP-025) |
| activity_events | ix_act_linkable | linkable_type, linkable_id | COMPOSITE | polymorphic lookup |
| services | ix_svc_pract | practitioner_id, status | COMPOSITE | my services |
| service_requests | ix_svcreq_pract_status | practitioner_id, status | COMPOSITE | inquiry queue |
| ceu_entries | ix_ceu_expires | practitioner_id, expires_on | COMPOSITE | expiring CEUs |
| bp_jobs | ix_job_status_category | status, category | COMPOSITE | job board filter |
| bp_proposals | uq_proposal | job_id, bp_id | UNIQUE | one proposal per job |
| bp_proposals | ix_prop_bp_status | bp_id, status | COMPOSITE | my proposals |
| bp_contracts | ix_contract_bp_status | bp_id, status | COMPOSITE | contract list |
| contract_meta | uq_contract_meta | contract_id, meta_key | UNIQUE | |
| bp_milestones | ix_ms_contract_status | contract_id, status | COMPOSITE | milestone board |
| bp_invoices | uq_bp_invoice_num | invoice_number | UNIQUE | invoice id |
| bp_invoices | ix_bpinv_pract_status | practitioner_id, status | COMPOSITE | payable list |
| bp_payouts | ix_payout_status_created | status, created_at | COMPOSITE | pending-payout queue (UC-ADM-044) |
| bp_team_members | uq_team_member | agency_id, member_id | UNIQUE | dedupe |
| bp_team_invitations | uq_team_invite_token | invite_token | UNIQUE | |
| bp_saved_jobs | uq_saved_job | bp_id, job_id | UNIQUE | dedupe |
| cs_invoices | uq_cs_invoice_num | invoice_number | UNIQUE | |
| cs_invoices | ix_csinv_pract_status | practitioner_id, status | COMPOSITE | payable list |
| cs_payouts | ix_cspayout_status_created | status, created_at | COMPOSITE | pending-payout queue |
| practitioner_payments | ix_pp_status_paid | status, paid_at | COMPOSITE | MRR + failed queue (UC-ADM-003,041) |
| practitioner_payments | ix_pp_pract_kind | practitioner_id, kind | COMPOSITE | ledger by kind |
| ss_provider_checkins | ix_checkin_pract | practitioner_id, checked_at | COMPOSITE | check-in history |
| news_comments | ix_newscom_post_created | post_id, created_at | COMPOSITE | comment thread |
| news_reactions | uq_news_react | post_id, user_id, reaction | UNIQUE | one reaction |
| news_poll_votes | uq_news_poll | post_id, user_id | UNIQUE | one vote |
| news_trending_topics | uq_trending_topic | topic | UNIQUE | |
| complaints | ix_compl_status_created | status, created_at | COMPOSITE | admin queue (UC-ADM-050) |
| complaint_meta | uq_complaint_meta | complaint_id, meta_key | UNIQUE | |
| complaint_replies | ix_reply_complaint_created | complaint_id, created_at | COMPOSITE | thread render |
| admin_audit_log | ix_audit_admin_created | admin_id, created_at | COMPOSITE | audit trail |
| admin_audit_log | ix_audit_linkable | linkable_type, linkable_id | COMPOSITE | target lookup |
| package_overrides | uq_package_tier | tier | UNIQUE | one override per tier |
| roles | uq_role_name | name | UNIQUE | role name |
| role_permissions | uq_roleperm | role_id, permission_key | UNIQUE | one grant per key |
| stripe_webhook_events | uq_webhook_event | stripe_event_id | UNIQUE | idempotency |
| profile_edit_authorizations | uq_profauth | practitioner_id, authorized_user_id | UNIQUE | one grant per pair |

---

# Section C — Enum Registry (every ENUM column, all values)

| Table.column | All valid values |
|---|---|
| users.role | practitioner, continuity_steward, support_steward, business_partner, admin |
| users.tier | access, practice |
| users.cs_account_type | invited, business, enterprise |
| users.bp_type | agency, freelancer |
| user_roles.role | practitioner, continuity_steward, support_steward, business_partner, admin |
| user_meta.meta_type | string, int, boolean, json, timestamp |
| plan_meta.meta_type / incident_meta.meta_type / vault_item_meta.meta_type / referral_meta.meta_type / contract_meta.meta_type / complaint_meta.meta_type | string, int, boolean, json, timestamp |
| continuity_plans.status | draft, pending_review, active, annual_review_due, expired |
| plan_stewards.role | primary, alternate, support |
| plan_stewards.status | invited, active, declined, request_incoming, archived, pending |
| plan_stewards.vault_access | none, metadata, scoped, full |
| plan_tasks.assigned_to | continuity_steward, support_steward |
| critical_incidents.status | reported, verified, active, closed |
| critical_incidents.severity | info, warning, critical |
| incident_tasks.assigned_role | continuity_steward, support_steward |
| incident_tasks.status | pending, in_progress, complete, exception |
| incident_updates.update_type | reported, verified, activated, vault_unsealed, ss_notified, task_added, escalated, closed |
| vault_items.zone | credentials, roster, documents, instructions |
| vault_items.status | vault_only, active, priority |
| vault_access_log.access_type | reveal, download, export, share, view |
| continuity_documents.status | draft, countersign, active, archived, release_pending |
| document_signatures.signer_role | practitioner, continuity_steward |
| network_connections.connection_type | practitioner, business_partner |
| network_connections.status | active, archived |
| network_requests.status | pending, accepted, declined, cancelled |
| referrals.status | sent, accepted, declined, closed, cancelled |
| activity_events.portal | provider, continuity_steward, support_steward, business_partner, admin |
| activity_events.event_type | message, task, document, incident, vault, compliance, attestation, payment, account, system, referral, news, event, practitioner_unresponsive_flagged |
| activity_events.severity | info, warning, critical |
| services.price_type | fixed, hourly, session, inquiry |
| services.status | active, inactive |
| service_requests.status | new, accepted, declined |
| service_sessions.status | scheduled, completed, cancelled, no_show |
| bp_jobs.budget_type | fixed, hourly, retainer |
| bp_jobs.status | draft, open, paused, closed, filled, cancelled |
| bp_proposals.proposed_rate_type | fixed, hourly, retainer |
| bp_proposals.status | pending, under_review, accepted, declined, withdrawn |
| bp_contracts.status | draft, active, completed, cancelled |
| bp_milestones.status | pending, submitted, approved, rejected, paid |
| bp_invoices.status | draft, sent, paid, overdue, void |
| bp_invoice_payments.method | stripe, manual, ach, card |
| bp_invoice_payments.status | pending, succeeded, failed, refunded |
| bp_payouts.status | pending, in_transit, paid, failed, cancelled |
| bp_tax_documents.doc_type | w9, 1099, ein_doc, other |
| bp_tax_documents.status | available, pending, verified |
| bp_team_members.permission_role | admin, manager, specialist, viewer |
| bp_team_members.status | active, idle, inactive |
| bp_team_invitations.permission_role | admin, manager, specialist, viewer |
| bp_team_invitations.status | pending, accepted, declined, expired |
| cs_invoices.status | draft, sent, paid, overdue, void |
| cs_payouts.status | pending, in_transit, paid, failed, cancelled |
| practitioner_payments.kind | subscription, maat_addon, cs_fee, bp_invoice, refund |
| practitioner_payments.status | paid, failed, refunded, partially_refunded, pending |
| ss_provider_checkins.status | ok, concern, unreachable |
| news_posts.post_type | post, poll, announcement |
| complaints.category | support_ticket, feedback, complaint |
| complaints.status | open, in_progress, resolved, closed |
| complaints.priority | low, normal, high, urgent |
| profile_edit_authorizations.status | active, revoked |

---

# Section D — Migration Order (FK-dependency-safe, 69 tables)

```
1.  users                          (self-FKs linked_provider_id/invited_by_id added in a follow-up ALTER after table exists)
2.  roles
3.  role_permissions               (FK→roles)
4.  user_meta                      (FK→users)
5.  user_roles                     (FK→users)
6.  user_sessions                  (FK→users)
7.  user_preferences               (FK→users)
8.  password_reset_tokens          (FK→users)
9.  mfa_tokens                     (FK→users)
10. continuity_plans               (FK→users)
11. plan_meta                      (FK→continuity_plans)
12. plan_stewards                  (FK→continuity_plans, users)
13. plan_tasks                     (FK→continuity_plans, users)
14. plan_incident_configs          (FK→continuity_plans)
15. critical_incidents             (FK→continuity_plans, users)
16. incident_meta                  (FK→critical_incidents)
17. incident_tasks                 (FK→critical_incidents, users)
18. incident_updates               (FK→critical_incidents, users)
19. vault_items                    (FK→users)
20. vault_item_meta                (FK→vault_items)
21. vault_access_log               (FK→vault_items, users)
22. continuity_documents           (FK→continuity_plans, users, self)
23. document_signatures            (FK→continuity_documents, users)
24. network_connections            (FK→users)
25. network_requests               (FK→users)
26. shadow_connections             (FK→users)
27. referrals                      (FK→users)
28. referral_meta                  (FK→referrals)
29. message_threads                (FK→users)
30. messages                       (FK→message_threads, users)
31. activity_events                (FK→users)
32. services                       (FK→users)
33. service_requests               (FK→services, users)
34. service_sessions               (FK→service_requests, services, users)
35. ceu_entries                    (FK→users)
36. bp_jobs                        (FK→users)
37. bp_proposals                   (FK→bp_jobs, users)
38. bp_contracts                   (FK→bp_jobs, bp_proposals, users)
39. contract_meta                  (FK→bp_contracts)
40. bp_milestones                  (FK→bp_contracts, users)
41. bp_invoices                    (FK→users, bp_contracts)
42. bp_invoice_line_items          (FK→bp_invoices)
43. bp_invoice_payments            (FK→bp_invoices, users)
44. bp_payouts                     (FK→users)
45. bp_tax_documents               (FK→users)
46. bp_team_members                (FK→users)
47. bp_team_invitations            (FK→users)
48. bp_saved_jobs                  (FK→users, bp_jobs)
49. cs_invoices                    (FK→users)
50. cs_payouts                     (FK→users)
51. practitioner_payment_methods   (FK→users)
52. practitioner_payments          (FK→users, practitioner_payment_methods)
53. ss_provider_checkins           (FK→users)
54. ss_provider_notes              (FK→users)
55. news_posts                     (FK→users)
56. news_events                    (no FK)
57. news_comments                  (FK→news_posts, users)
58. news_reactions                 (FK→news_posts, users)
59. news_poll_votes                (FK→news_posts, users)
60. news_trending_topics           (no FK)
61. news_library_items             (no FK)
62. complaints                     (FK→users)
63. complaint_meta                 (FK→complaints)
64. complaint_replies              (FK→complaints, users)
65. help_articles                  (no FK)
66. admin_audit_log                (FK→users)
67. package_overrides              (no FK)
68. stripe_webhook_events          (no FK)
69. profile_edit_authorizations    (FK→users)
```

**Note for migration generator:** create `users` (step 1) without its two self-referential FKs, then add `linked_provider_id` and `invited_by_id` foreign keys in a follow-up migration after the table exists (avoids a self-reference ordering problem). All `*_meta` tables and pure child tables use `ON DELETE CASCADE`; references to `users` on content tables use `RESTRICT` (preserve history) or `SET NULL` (optional assignee/verifier) per Section A.

---

**END — 69 tables · 7 meta tables · 20 NEW + 5 v16 + 44 existing · every column UC-cited · Sections A–D complete.**


---

## `activity_event_reads`
**Purpose:** Per-user read receipts for activity feed events. Without this, the cross-portal "mark as read" / unread-count widgets cannot be implemented. Added in migration `000071`.
**UCs:** UC-PRV-092, UC-PRV-093 (with CS/SS/BP/Admin equivalents)
**Meta table:** no

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | no | (uuid) | PK | |
| user_id | CHAR(36) | no | — | FK,IDX | → `users.id` cascadeOnDelete |
| activity_event_id | CHAR(36) | no | — | FK,IDX | → `activity_events.id` cascadeOnDelete |
| read_at | TIMESTAMP | no | CURRENT_TIMESTAMP | — | |
| created_at | TIMESTAMP | no | CURRENT_TIMESTAMP | IDX | |

**Indexes:**
- `uq_activity_event_reads_user_event` UNIQUE (`user_id`, `activity_event_id`)
- `ix_activity_event_reads_user_read_at` (`user_id`, `read_at`)

---

## `provider_checkins` *(renamed from `ss_provider_checkins` in migration `000072`)*
**Purpose:** Polymorphic-ish steward check-in log. Now hosts both CS proactive check-ins (UC-CS-015) and SS provider check-ins. Differentiated by `steward_type`.
**UCs:** UC-CS-015, UC-CS-016, UC-SS-040, UC-SS-041
**Meta table:** no
**Changes from `ss_provider_checkins`:** column `ss_id` renamed to `steward_id`; new enum column `steward_type` (`cs|ss`, default `ss`) added; existing rows backfilled with `steward_type='ss'`.

| Column | Type | Null | Default | Index | Notes |
|--------|------|------|---------|-------|-------|
| id | CHAR(36) | no | (uuid) | PK | |
| steward_id | CHAR(36) | no | — | FK,IDX | → `users.id` (was `ss_id`) |
| steward_type | ENUM('cs','ss') | no | 'ss' | IDX | New column from migration `000072` |
| practitioner_id | CHAR(36) | no | — | FK,IDX | → `users.id` |
| notes | TEXT | yes | NULL | — | |
| created_at | TIMESTAMP | no | CURRENT_TIMESTAMP | IDX | |
| updated_at | TIMESTAMP | no | CURRENT_TIMESTAMP | — | |

**Indexes:**
- `ix_provider_checkins_steward_type_created` (`steward_id`, `steward_type`, `created_at`)

