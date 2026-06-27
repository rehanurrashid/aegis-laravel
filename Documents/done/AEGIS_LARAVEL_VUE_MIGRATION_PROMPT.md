# Aegis — Laravel 11 + Vue 3 Migration — New Chat

## What Aegis Is

Healthcare continuity SaaS. Four user portals — **Practitioner (Provider)**, **Continuity Steward (CS)**, **Support Steward (SS)**, **Business Partner (BP)** — plus an **Admin portal**. Built and running in PHP 8 / SQLite / vanilla CSS+JS. Frontend is 99% final, 90% dynamic, portals communicate via shared write endpoints and `activity_events` fan-out.

**Goal of this chat:** Scaffold the complete Laravel 11 + Vue 3 (Inertia.js) application structure — migrations, models, services, controllers, Vue components — so the existing PHP prototype can be ported feature-by-feature with zero architectural ambiguity.

---

## Source of Truth (Project Knowledge)

All authoritative docs are in project knowledge. Use `project_knowledge_search` before answering anything about existing structure, schema, or patterns. Key files:

| Doc | Use it for |
|---|---|
| `CENTRALIZED-SYSTEM.md` | Full `_shared/` inventory, all 42 DB tables, portal pages, write endpoints, architecture patterns |
| `AEGIS-PROJECT-CONTEXT.md` | Roles, continuity plan lifecycle, pricing, integrations (Stripe Connect, Keeper, SES), demo data |
| `Aegis_Desing_Prompt_Short.md` | Design system rules (CSS variables, component classes, token names) |
| `Aegis_Global_Wiring_Prompt.md` | Write-path pattern — how PHP endpoints wire to helpers |
| `ADMIN-PORTAL-SPEC.md` | Admin portal 6-page spec — pages, tables, controllers, endpoints derived from Vue sidebar |

---

## Current Stack → Target Stack

| Layer | Current (PHP prototype) | Target (production) |
|---|---|---|
| Framework | Vanilla PHP 8, no framework | **Laravel 11** |
| Frontend | Vanilla JS + PHP-rendered HTML | **Vue 3 + Inertia.js** |
| CSS | Single `_shared.css` (170 KB design system) | Keep `_shared.css` as-is — port to `public/` |
| Database | SQLite (42 tables, v15 schema) | **MySQL 8** (same schema, Eloquent models) |
| Auth | Custom session token in `users` table | **Laravel Sanctum** (web sessions for SPA) |
| Payments | Stripe Connect (wired, not live) | **Laravel Cashier + Stripe Connect** |
| Email | Placeholder, SES planned | **Laravel Mail + Amazon SES** |
| File storage | Local stubs | **Laravel Storage + S3** |
| Queue | None | **Laravel Horizon + Redis** (for fan-out jobs) |

---

## Existing Database — 42 Tables to Migrate

From `db.php` (exact table list — do NOT rename or restructure without flagging):

**Core users:**
`users`, `user_roles`, `user_sessions`, `user_preferences`

**Continuity plan:**
`continuity_plans`, `plan_stewards`, `plan_tasks`, `plan_incident_configs`, `critical_incidents`, `incident_tasks`

**Vault & Documents:**
`vault_items`

**Communication:**
`activity_events`, `message_threads`, `messages`, `network_connections`, `network_requests`, `shadow_connections`, `referrals`

**Business Partner (12 tables):**
`bp_jobs`, `bp_proposals`, `bp_contracts`, `bp_milestones`, `bp_invoices`, `bp_invoice_line_items`, `bp_invoice_payments`, `bp_payouts`, `bp_tax_documents`, `bp_team_members`, `bp_team_invitations`, `bp_saved_jobs`

**News & Events:**
`news_posts`, `news_events`, `news_comments`, `news_reactions`, `news_poll_votes`, `news_trending_topics`, `news_library_items`

**Finance:**
`practitioner_payment_methods`, `practitioner_payments`, `cs_invoices`, `ss_provider_checkins`, `ss_provider_notes`

**Admin (v16 — new tables):**
`admin_audit_log`, `package_overrides`, `roles`, `role_permissions`, `complaints`, `complaint_replies`

---

## Portal Pages to Vue-ify

### Provider Portal (18 pages)
`overview`, `dashboard`, `edit-profile`, `continuity-plan`, `continuity-stewards`, `support-stewards`, `network`, `services`, `job-postings`, `referrals`, `vault`, `important-documents`, `messages`, `activity`, `news`, `events`, `finances`, `settings`

### Continuity Steward Portal (12 pages)
`overview`, `dashboard`, `edit-profile`, `providers`, `my-tasks`, `continuity-management`, `important-documents`, `vault`, `messages`, `activity`, `finances`, `settings`

### Support Steward Portal (11 pages)
`overview`, `dashboard`, `edit-profile`, `providers`, `continuity-stewards`, `my-tasks`, `critical-incident-log`, `important-documents`, `messages`, `activity`, `settings`

### Business Partner Portal (14 pages)
`overview`, `dashboard`, `edit-profile`, `find-jobs`, `proposals`, `contracts`, `milestones`, `invoices`, `finances`, `payment-setup`, `team`, `messages`, `activity`, `settings`

### Admin Portal (6 pages)
`dashboard`, `packages`, `users`, `roles`, `payments`, `complaints`

---

## Shared Vue Components to Build (across all portals)

From the existing `_shared/` system — these become reusable Vue components:

**Chrome:**
`AppSidebar.vue` (already partially given — use the Vue sidebar component provided), `AppHeader.vue`, `AppLayout.vue`

**UI primitives:**
`AegisModal.vue`, `AegisToast.vue`, `AegisDropzone.vue`, `AegisStatChip.vue`, `AegisHeroBanner.vue`, `AegisConfirm.vue`, `AegisToggle.vue`, `AegisBadge.vue`

**Feature components:**
`ActivityFeed.vue`, `MessagesThread.vue`, `OverviewPage.vue` (the shared Overview Start Here — used by all 5 portals), `ProfileStrip.vue`, `UpgradeModal.vue`, `VaultZone.vue`, `PlanStatusCard.vue`

**Icon system:**
`AegisIcon.vue` — wraps the existing SVG icon registry from `icons.php`

---

## Laravel Architecture Conventions to Follow

### Directory structure
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Provider/        ← one controller per portal
│   │   ├── ContinuitySteward/
│   │   ├── SupportSteward/
│   │   ├── BusinessPartner/
│   │   ├── Admin/
│   │   └── Public/          ← public profiles (no auth)
│   ├── Middleware/
│   │   ├── EnsureRole.php       ← gates by user role
│   │   ├── EnsurePlanActive.php ← checks continuity plan status
│   │   └── EnsureAdminRole.php
│   └── Requests/            ← FormRequest per write action
├── Models/                  ← one Eloquent model per table
├── Services/                ← business logic layer (mirrors models_write.php)
│   ├── PlanService.php
│   ├── IncidentService.php
│   ├── VaultService.php
│   ├── ActivityService.php  ← aegis_log_activity() equivalent
│   ├── NotificationService.php
│   └── ...
├── Jobs/                    ← queued fan-out (ActivityFanoutJob, EmailNotificationJob)
├── Events/ + Listeners/     ← domain events (PlanSigned, IncidentActivated, VaultAttested)
├── Policies/                ← one Policy per model (authorization)
└── Enums/                   ← PHP 8.1 enums for status values
    ├── PlanStatus.php       ← draft|pending_review|active|annual_review_due|expired
    ├── IncidentType.php     ← death|missing_person|short_term_incapacitation|...
    ├── StewardRole.php      ← primary|alternate
    └── UserRole.php         ← practitioner|continuity_steward|support_steward|business_partner|admin
```

### Key conventions
- **One Service per domain** — controllers are thin (call Service, return Inertia response)
- **Events + Listeners** for cross-portal fan-out (replaces direct `aegis_log_activity()` calls)
- **Jobs** for heavy fan-out (incident activation fans to 10+ users — must be queued)
- **Policies** for all authorization (replaces PHP role checks scattered in pages)
- **FormRequests** for all write validation (replaces inline validation in save_*.php)
- **Enums** for every status column — no magic strings
- **Repository pattern optional** — Service → Eloquent direct is fine for this scale

### Route structure
```
routes/
├── web.php          ← Inertia SPA routes (grouped by portal + middleware)
├── api.php          ← (mostly unused — Inertia handles data passing)
└── channels.php     ← Echo/Reverb for real-time (messages, incident alerts)
```

Route groups:
```php
Route::middleware(['auth', 'role:practitioner'])->prefix('provider')->group(...)
Route::middleware(['auth', 'role:continuity_steward'])->prefix('continuity-steward')->group(...)
Route::middleware(['auth', 'role:support_steward'])->prefix('support-steward')->group(...)
Route::middleware(['auth', 'role:business_partner'])->prefix('business')->group(...)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(...)
Route::prefix('public')->group(...)  ← no auth
```

---

## Critical Domain Rules (must survive migration)

1. **`plan_tasks` ≠ `incident_tasks`** — standby prep tasks vs active-incident response tasks. Never merge into one query.
2. **Cross-portal fan-out via events** — when a Practitioner attests their Vault, CS + SS each get an `activity_events` row. In Laravel: fire `VaultAttested` event → `ActivityFanoutListener` → queued `ActivityFanoutJob`.
3. **Stripe Connect — Aegis never holds funds.** Payments go Practitioner → CS/BP directly. `cashier` subscription billing is separate from Connect payouts.
4. **Vault sealed until verified incident.** `vault_items` are accessible only when `critical_incidents.status = 'active'` AND the user is an assigned steward. Gate this in `VaultPolicy`.
5. **Demo user system** — `?as=p_sarah` etc. needs a dev-only middleware equivalent (`ImpersonateForDemo`) for local dev.
6. **`activity_events` is the cross-portal inbox** — every write action with cross-portal impact writes to it. `ActivityService::log()` is the single call for this.
7. **Inertia shared data** — pass `user`, `portal`, `activePage`, `hasEmergency`, `unreadCount` in `HandleInertiaRequests` middleware so every page has it.

---

## Vue + Inertia Conventions

- **Composition API + `<script setup>`** throughout
- **Pinia** for global state (user, portal, notifications, emergency state)
- **`useForm()`** from Inertia for all write forms
- **`router.visit()`** for programmatic navigation
- **No Vuex** — Pinia only
- **Tailwind CSS** — do NOT use. Keep `_shared.css` design tokens. Apply class names exactly as they exist in the PHP prototype (`.hero-banner`, `.btn.btn-primary`, `.modal-overlay`, etc.)
- **`<AegisIcon name="shield" :size="16" />`** wraps the existing SVG registry
- **`openModal()` / `closeModal()`** — preserve exact function names in `_shared.js` or equivalent composable (`useModal.js`)

---

## What to Build in This Chat

**Goal: produce the complete application scaffold** (no feature implementation — structure only). In this order:

### Phase 1 — Foundation
1. Laravel 11 app structure + all config files
2. All 48 migrations (42 existing + 6 new admin tables) with correct columns, FKs, indexes
3. All Eloquent models with relationships, casts, enums
4. All PHP Enums for status columns

### Phase 2 — Auth + Middleware
5. Sanctum auth setup
6. `EnsureRole`, `EnsurePlanActive`, `EnsureAdminRole` middleware
7. All Policies (one per major model)
8. Route files with all groups + named routes

### Phase 3 — Services + Events
9. All Service classes (stubs with method signatures only — no implementation)
10. All Events + Listeners
11. All Job classes (stubs)

### Phase 4 — Controllers + FormRequests
12. All controllers (stubs — constructor, method signatures, Inertia return)
13. All FormRequest classes with rules

### Phase 5 — Vue Structure
14. `resources/js/` directory tree
15. All Vue page components (stubs — `<template>`, `<script setup>` with props only)
16. All shared Vue components (stubs)
17. Pinia stores
18. Composables

### Phase 6 — Seed + Demo
19. Database seeders (one per portal user type)
20. Demo data seeder (mirrors `seed.json`)

---

## Working Style

- **Structure first, implementation later** — stubs are fine, just name everything correctly
- One step at a time — complete Phase 1 fully before Phase 2
- Use `project_knowledge_search` to look up existing table columns, relationships, and write patterns before inventing anything
- Flag any architecture decision that deviates from the PHP prototype's data model
- Every migration must match the existing `db.php` schema exactly (column names, types, nullable flags)
- No placeholder names — use real domain names from the existing codebase

## Stack versions
- Laravel 11
- Vue 3.4+
- Inertia.js 1.x (Laravel adapter)
- Pinia 2.x
- Vite 5.x (Laravel Vite plugin)
- PHP 8.2+
- MySQL 8.0
- Redis (queues + cache)
- Laravel Horizon (queue dashboard)
- Laravel Sanctum (auth)
- Laravel Cashier (Stripe subscriptions)
- Laravel Reverb or Soketi (WebSockets for messages + incident alerts)

---

## Start Here

**First task:** Search project knowledge for the full table list from `CENTRALIZED-SYSTEM.md` and `db.php`, then generate **Phase 1 — all 48 migrations** in the correct dependency order (users first, FKs last). Name each migration file with Laravel conventions: `create_users_table`, `create_continuity_plans_table`, etc.
