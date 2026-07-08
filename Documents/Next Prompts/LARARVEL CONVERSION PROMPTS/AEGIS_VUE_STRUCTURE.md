# AEGIS_VUE_STRUCTURE.md
# Aegis — Vue 3 / Laravel 11 / Inertia.js Component Structure

**Status:** Structure-only reference. No implementation — stubs, props, emits, slots, composables, and controller bindings only.
**Stack:** Laravel 11 · Vue 3 · Inertia.js · Pinia · Vite · `_shared.css` (unchanged, copied verbatim)
**Rule:** No Tailwind anywhere. Every CSS class must match a class defined in `_shared.css`.

---

## Section 1 — Global Assets Strategy

| PHP asset | Laravel/Vue equivalent | Strategy |
|---|---|---|
| `/_shared.css` | `public/css/_shared.css` | Copy verbatim from PHP repo. Import in `resources/css/app.css`. Never modify. |
| `/_shared.js` | `resources/js/composables/use*.js` | Split into purpose-scoped composables (see Section 6). No global JS file. |
| `_shared/icons.php` · `aegis_icon()` | `components/ui/AegisIcon.vue` | Option B: inline SVG registry as JS object — direct port of `icons.php` SVG path map. No individual files, no fetching. |
| `_shared/sidebar.php` | `components/chrome/AppSidebar.vue` | Already built. Reference only — do not rebuild. |
| `_shared/header.php` | `components/chrome/AppHeader.vue` | Port role-aware URL map and bell/search/user-menu markup to Vue. |
| `_shared/page_head.php` + `_shared/page_foot.php` | `resources/views/app.blade.php` | Single Inertia root Blade. `$page_title`, `$page_portal_label`, `$page_extra_head` → Inertia shared props. |
| `_shared/bell.php` | `components/chrome/AppHeader.vue` | Bell dropdown is part of `AppHeader`. Fed by `notifications` Pinia store. |
| `_shared/demo_switcher.php` | `components/dev/DemoSwitcher.vue` | Dev-only. Hidden in production via `import.meta.env.DEV` guard. |
| `_shared/profile_strip.php` | `components/features/ProfileStrip.vue` | Reusable component. Used on dashboard and edit-profile pages. |
| `_shared/public_chrome.php` + `_shared/public_profile.php` | `layouts/PublicLayout.vue` + `pages/public/*.vue` | Public layout wraps all four public profile pages. |
| `_shared/activity_body.php` | `pages/shared/Activity.vue` | Single Inertia page used by all portals. Portal context from `auth` store. |
| `_shared/templates/overview.php` | `pages/shared/Overview.vue` | Same pattern — single page, role bundle from `auth` store / controller prop. |
| `_shared/templates/messages.php` | `pages/shared/Messages.vue` | Same pattern. |
| `_shared/templates/support.php` | `pages/shared/Support.vue` | Same pattern. |
| `_shared/modals/referral_modal.php` | `components/modals/ReferralModal.vue` | |
| `_shared/modals/job_detail_modal.php` | `components/modals/JobDetailModal.vue` | |
| `_shared/modals/proposal_modal.php` | `components/modals/ProposalModal.vue` | |
| `_shared/modals/upgrade_cs_modal.php` | `components/modals/UpgradeCSModal.vue` | |
| `_shared/pricing_data.php` · `aegis_pricing()` | `stores/pricing.js` + `GET /api/pricing` | Pinia store hydrated from API endpoint. Same data shape as `aegis_pricing()`. |
| `_shared/emails/*.php` | `resources/views/emails/**/*.blade.php` | Blade mail templates. `$data['key']` → `{{ $key }}`. No Vue involved. |
| `_shared/db.php` · `models.php` · `models_write.php` | Laravel Services + Eloquent models + API controllers | Backend only. Not in `resources/js/`. |
| `_shared/save_*.php` (16 endpoints) | `routes/api.php` → `App\Http\Controllers\Api\*Controller` | REST endpoints. Inertia page components POST via `useForm()` or `axios`. |

---

## Section 2 — Full Folder Tree

```
resources/js/
│
├── app.js                          ← Inertia entry point, Vue app creation, global component registration
├── bootstrap.js                    ← Axios defaults, Echo/Reverb setup
│
├── composables/
│   ├── useModal.js                 ← openModal / closeModal / activeModal
│   ├── useToast.js                 ← showToast (replaces _shared.js showToast)
│   ├── useConfirm.js               ← confirmAction (replaces _shared.js confirmAction)
│   ├── usePortal.js                ← portal detection, role helpers (isPractitioner, isCS, etc.)
│   ├── useDemo.js                  ← ?as= flag logic, demo user switching (dev only)
│   ├── useUpgrade.js               ← openUpgradeModal / tier gate check
│   ├── useActivity.js              ← activity feed helpers (markRead, filter, paginate)
│   ├── useVault.js                 ← vault seal/unseal state, incident-gate check
│   ├── useIncident.js              ← emergency active state, incident type list
│   └── useProfileRoute.js          ← viewPartyProfile(name, kind, slug) equivalent
│
├── stores/
│   ├── auth.js                     ← current user, role, portal, tier
│   ├── notifications.js            ← unread count, bell feed items
│   ├── incident.js                 ← hasEmergency, active incident state
│   ├── pricing.js                  ← tier definitions, feature flag map
│   └── ui.js                       ← sidebar collapsed, mobile open, toast queue
│
├── layouts/
│   ├── AppLayout.vue               ← authenticated shell: AppSidebar + AppHeader + <slot>
│   ├── PublicLayout.vue            ← public profile pages: no auth, no sidebar
│   └── AuthLayout.vue              ← login / register / reset: no sidebar, no header
│
├── components/
│   │
│   ├── chrome/
│   │   ├── AppSidebar.vue          ← ALREADY BUILT — reference only
│   │   ├── AppHeader.vue           ← top bar: search, bell dropdown, user menu, role label
│   │   └── DemoSwitcher.vue        ← dev-only floating switcher (bottom-right corner)
│   │
│   ├── ui/
│   │   ├── AegisIcon.vue           ← SVG icon wrapper (inline registry, Option B)
│   │   ├── AegisModal.vue          ← modal overlay + close (class: .modal-overlay / .modal)
│   │   ├── AegisToast.vue          ← toast notification strip
│   │   ├── AegisConfirm.vue        ← confirm dialog (replaces confirm())
│   │   ├── AegisDropzone.vue       ← file upload (replaces Dropzone CDN)
│   │   ├── AegisStatChip.vue       ← stat chip (hero-banner sibling)
│   │   ├── AegisHeroBanner.vue     ← page hero: eyebrow + title + sub + action buttons
│   │   ├── AegisBadge.vue          ← status / role badge pill
│   │   ├── AegisToggle.vue         ← checkbox toggle switch
│   │   ├── AegisCard.vue           ← card wrapper (.card)
│   │   ├── AegisEmptyState.vue     ← empty state block
│   │   ├── AegisPagination.vue     ← pagination controls
│   │   └── AegisUpgradeModal.vue   ← tier upgrade prompt (wraps UpgradeCSModal or generic)
│   │
│   ├── modals/
│   │   ├── ReferralModal.vue       ← replaces _shared/modals/referral_modal.php
│   │   ├── JobDetailModal.vue      ← replaces _shared/modals/job_detail_modal.php
│   │   ├── ProposalModal.vue       ← replaces _shared/modals/proposal_modal.php
│   │   └── UpgradeCSModal.vue      ← replaces _shared/modals/upgrade_cs_modal.php
│   │
│   ├── features/
│   │   ├── ActivityFeed.vue        ← cross-portal activity feed (inline section use)
│   │   ├── MessagesThread.vue      ← message thread + composer (used inside Messages.vue)
│   │   ├── VaultZone.vue           ← vault zone (sealed/unsealed state, 4 zone types)
│   │   ├── PlanStatusCard.vue      ← continuity plan status widget (dashboard use)
│   │   ├── ProfileStrip.vue        ← profile completion strip (dashboard + edit-profile)
│   │   ├── StewardCard.vue         ← CS/SS designation card
│   │   ├── IncidentBanner.vue      ← active incident alert strip (conditional, all portals)
│   │   └── SupportWidget.vue       ← floating feedback / support button
│   │
│   └── dev/
│       └── DemoSwitcher.vue        ← duplicate reference; canonical lives in chrome/
│
├── pages/
│   │
│   ├── auth/
│   │   ├── Login.vue
│   │   ├── Register.vue
│   │   └── ResetPassword.vue
│   │
│   ├── shared/                     ← Inertia pages shared across all portals
│   │   ├── Overview.vue            ← replaces _shared/templates/overview.php
│   │   ├── Messages.vue            ← replaces _shared/templates/messages.php
│   │   ├── Activity.vue            ← replaces _shared/templates/activity.php
│   │   └── Support.vue             ← replaces _shared/templates/support.php
│   │
│   ├── provider/                   ← 15 Practitioner portal pages
│   │   ├── Dashboard.vue
│   │   ├── EditProfile.vue
│   │   ├── ContinuityPlan.vue
│   │   ├── ContinuityStewards.vue
│   │   ├── SupportStewards.vue
│   │   ├── Network.vue
│   │   ├── Services.vue
│   │   ├── JobPostings.vue
│   │   ├── Referrals.vue
│   │   ├── Vault.vue
│   │   ├── ImportantDocuments.vue
│   │   ├── News.vue
│   │   ├── Events.vue
│   │   ├── Finances.vue
│   │   └── Settings.vue
│   │   ← Overview, Messages, Activity, Support → pages/shared/
│   │
│   ├── continuity-steward/         ← 11 CS portal pages
│   │   ├── Dashboard.vue
│   │   ├── EditProfile.vue
│   │   ├── Providers.vue
│   │   ├── MyTasks.vue
│   │   ├── ContinuityManagement.vue
│   │   ├── ImportantDocuments.vue
│   │   ├── Vault.vue
│   │   ├── Finances.vue
│   │   └── Settings.vue
│   │   ← Overview, Messages, Activity, Support → pages/shared/
│   │
│   ├── support-steward/            ← 8 SS portal pages
│   │   ├── Dashboard.vue
│   │   ├── EditProfile.vue
│   │   ├── Providers.vue
│   │   ├── ContinuityStewards.vue
│   │   ├── MyTasks.vue
│   │   ├── CriticalIncidentLog.vue
│   │   ├── ImportantDocuments.vue
│   │   └── Settings.vue
│   │   ← Overview, Messages, Activity, Support → pages/shared/
│   │
│   ├── business-partner/           ← 11 BP portal pages
│   │   ├── Dashboard.vue
│   │   ├── EditProfile.vue
│   │   ├── FindJobs.vue
│   │   ├── Proposals.vue
│   │   ├── Contracts.vue
│   │   ├── Milestones.vue
│   │   ├── Invoices.vue
│   │   ├── Finances.vue
│   │   ├── PaymentSetup.vue
│   │   ├── Team.vue
│   │   └── Settings.vue
│   │   ← Overview, Messages, Activity, Support → pages/shared/
│   │
│   ├── admin/                      ← 6 Admin portal pages
│   │   ├── Dashboard.vue
│   │   ├── Packages.vue
│   │   ├── Users.vue
│   │   ├── Roles.vue
│   │   ├── Payments.vue
│   │   └── Complaints.vue
│   │
│   └── public/                     ← 4 public profile pages (no auth, PublicLayout)
│       ├── ProviderProfile.vue
│       ├── ContinuityStewardProfile.vue
│       ├── SupportStewardProfile.vue
│       └── BusinessProfile.vue
│
└── types/                          ← TypeScript type stubs (optional, add incrementally)
    ├── user.ts
    ├── plan.ts
    └── activity.ts
```

---

## Section 3 — All Components Documented

### Layout Components

---

#### `layouts/AppLayout.vue`
**Replaces:** `_shared/page_head.php` + `_shared/page_foot.php` + `_shared/sidebar.php` + `_shared/header.php` combined
**Used by:** all authenticated portal pages
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| pageTitle | String | yes | — | Sets `<title>` and header breadcrumb |
| portalLabel | String | no | `''` | e.g. "Aegis Provider Portal" |
| activePage | String | no | `''` | Passed to AppSidebar for nav highlight |

**Slots:** `default` (page body content)
**Pinia stores accessed:** `auth`, `ui`, `notifications`, `incident`
**CSS classes used:** `.page-body`, `.page-layout`, `.sidebar-layout`
**Notes:** Renders `IncidentBanner` conditionally when `incident.hasEmergency`. Renders `DemoSwitcher` when `import.meta.env.DEV`.

---

#### `layouts/PublicLayout.vue`
**Replaces:** `_shared/public_chrome.php` dispatcher
**Used by:** `pages/public/*.vue`
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| viewerRole | String | no | `null` | Authenticated viewer's role (null = anonymous) |

**Slots:** `default`
**CSS classes used:** `.public-chrome`, `.public-page`

---

#### `layouts/AuthLayout.vue`
**Replaces:** Onboarding/login page wrappers
**Used by:** `pages/auth/*.vue`
**Slots:** `default`
**CSS classes used:** `.auth-shell`, `.auth-card`

---

### Chrome Components

---

#### `components/chrome/AppSidebar.vue`
**Status:** ALREADY BUILT — do not recreate. Document only.
**Replaces:** `_shared/sidebar.php`
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| activePage | String | no | `''` | Highlights current nav item |

**Pinia stores accessed:** `auth` (role → nav registry), `incident` (emergency badge)
**CSS classes used:** `.sidebar`, `.nav-item`, `.nav-item.active`, `.nav-section-label`
**Notes:** Nav registry is role-driven (mirrors `sidebar.php`'s role-switch). Always expanded by default — no localStorage collapse persistence (client decision).

---

#### `components/chrome/AppHeader.vue`
**Replaces:** `_shared/header.php` + `_shared/bell.php`
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| pageTitle | String | yes | — | Shown in top bar breadcrumb |
| portalLabel | String | no | `''` | Role label shown beside logo |

**Emits:** none (bell actions dispatch to `notifications` store directly)
**Pinia stores accessed:** `auth`, `notifications`, `ui`
**CSS classes used:** `.topbar`, `.topbar-search`, `.topbar-bell`, `.topbar-user`, `.dropdown`, `.dropdown-menu`
**Notes:** `$aegis_urls` role-aware URL map (finances, messages, activity, critical_incident, settings, profile) is replicated as a computed map keyed on `auth.portal`. Bell dropdown reads from `notifications.items`. User menu "My Profile" link follows the same resolution logic as `header.php` (public profile URL when available, `edit-profile` fallback).

---

#### `components/chrome/DemoSwitcher.vue`
**Replaces:** `_shared/demo_switcher.php`
**Props:** none
**Emits:** none (uses `useDemo()` composable internally)
**CSS classes used:** `.demo-switcher`
**Notes:** Renders only when `import.meta.env.DEV === true`. Wraps `useDemo().switchUser(userId)`.

---

### UI Primitive Components

---

#### `components/ui/AegisIcon.vue`
**Replaces:** `_shared/icons.php` · `aegis_icon($key, $size)`
**Strategy:** Option B — inline SVG path registry as a JS object. Full port of `icons.php` SVG path map. No fetching, no individual files, no tree-shaking needed at this scale.
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| name | String | yes | — | Icon key (e.g. `'shield'`, `'lock'`, `'user'`) |
| size | Number | no | 16 | Rendered as `width` + `height` on the `<svg>` |
| filled | Boolean | no | false | Adds `.aegis-icon-filled` class (swaps stroke→fill) |

**Emits:** none
**CSS classes used:** `.aegis-icon`, `.aegis-icon-filled`
**Notes:** SVG viewBox is always `0 0 24 24`. Stroke is always `1.75`. Unknown name renders an empty SVG. Canonical icon names are identical to PHP — `pencil` not `edit`, `trash` not `delete`, `x` not `close`, etc.

---

#### `components/ui/AegisModal.vue`
**Replaces:** `openModal()` / `closeModal()` in `_shared.js` + modal HTML scaffolding in every portal page
**Used by:** all portals, all pages with modals
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| modelValue | Boolean | yes | — | v-model for open/close |
| title | String | no | `''` | Plain text — no icons in modal title |
| size | String | no | `'md'` | `'sm'` \| `'md'` \| `'lg'` \| `'xl'` |
| closeable | Boolean | no | true | Show X close button |

**Emits:** `update:modelValue`
**Slots:** `default` (body content), `footer` (action buttons)
**CSS classes used:** `.modal-overlay`, `.modal`, `.modal-header`, `.modal-title`, `.modal-close`, `.modal-body`, `.modal-footer`, `.modal-sm`, `.modal-md`, `.modal-lg`, `.modal-xl`
**Notes:** Closes on backdrop click and Escape key. Sets `document.body.style.overflow` on open/close. Teleports to `body`.

---

#### `components/ui/AegisToast.vue`
**Replaces:** `showToast()` in `_shared.js` + `.toast-container` in `page_foot.php`
**Props:** none (reads from `ui` Pinia store `toastQueue`)
**Emits:** none
**CSS classes used:** `.toast-container`, `.toast`, `.toast-success`, `.toast-error`, `.toast-info`, `.toast-warning`
**Notes:** Singleton — rendered once in `AppLayout`. Consumed via `useToast()` composable.

---

#### `components/ui/AegisConfirm.vue`
**Replaces:** `confirmAction()` in `_shared.js` (browser `confirm()` replacement)
**Props:** none (driven by `useConfirm()` composable internal state)
**Emits:** none
**CSS classes used:** `.modal-overlay`, `.modal`, `.modal-sm`, `.modal-body`, `.modal-footer`
**Notes:** Programmatic API — `useConfirm().confirm({ message, confirmLabel, danger })` returns a Promise. Singleton rendered in `AppLayout`.

---

#### `components/ui/AegisDropzone.vue`
**Replaces:** Dropzone CDN (`<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/...">`)
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| accept | String | no | `'*'` | MIME types or extensions |
| maxFiles | Number | no | 1 | |
| maxSizeKb | Number | no | 10240 | 10 MB default |
| label | String | no | `'Drop files here or click to upload'` | |

**Emits:** `file-added(file)`, `file-removed(file)`, `upload-complete(response)`
**CSS classes used:** `.dropzone`, `.dropzone-label`, `.dz-preview`, `.dz-filename`
**Notes:** Self-contained implementation — no CDN dependency. Same visual output as the current Dropzone CDN usage.

---

#### `components/ui/AegisStatChip.vue`
**Replaces:** Stat chip markup (`.stat-chip` pattern) in dashboard hero banners
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| label | String | yes | — | Chip label text |
| value | String\|Number | yes | — | Chip metric value |
| icon | String | no | `''` | AegisIcon name |
| trend | String | no | `''` | `'up'` \| `'down'` \| `''` |

**Emits:** none
**CSS classes used:** `.stat-chip`, `.stat-chip-value`, `.stat-chip-label`
**Notes:** Icon background always uses `var(--badge-bg-gold)` + `var(--gold-dark)` — no color variants.

---

#### `components/ui/AegisHeroBanner.vue`
**Replaces:** `.hero-banner` pattern in every portal page
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| eyebrow | String | no | `''` | Small label above title |
| title | String | yes | — | Main heading |
| subtitle | String | no | `''` | Secondary line |

**Emits:** none
**Slots:** `actions` (right-side CTA buttons), `chips` (stat chips row)
**CSS classes used:** `.hero-banner`, `.hero-eyebrow`, `.hero-title`, `.hero-sub`, `.hero-actions`, `.hero-chips`

---

#### `components/ui/AegisBadge.vue`
**Replaces:** Status/role badge pill markup
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| label | String | yes | — | |
| variant | String | no | `'default'` | `'success'` \| `'warning'` \| `'danger'` \| `'info'` \| `'gold'` \| `'default'` |

**CSS classes used:** `.badge`, `.badge-success`, `.badge-warning`, `.badge-danger`, `.badge-info`, `.badge-gold`

---

#### `components/ui/AegisToggle.vue`
**Replaces:** `toggleSwitch()` in `_shared.js` + toggle HTML
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| modelValue | Boolean | yes | — | v-model |
| label | String | no | `''` | |
| disabled | Boolean | no | false | |

**Emits:** `update:modelValue`
**CSS classes used:** `.toggle`, `.toggle-label`, `.toggle-switch`

---

#### `components/ui/AegisCard.vue`
**Replaces:** `.card` wrapper markup
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| padded | Boolean | no | true | Adds `.card-body` padding |

**Slots:** `header`, `default` (body), `footer`
**CSS classes used:** `.card`, `.card-header`, `.card-body`, `.card-footer`

---

#### `components/ui/AegisEmptyState.vue`
**Replaces:** Empty state block markup
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| icon | String | no | `'inbox'` | AegisIcon name |
| title | String | yes | — | |
| subtitle | String | no | `''` | |

**Slots:** `actions`
**CSS classes used:** `.empty-state`, `.empty-state-icon`, `.empty-state-title`, `.empty-state-sub`

---

#### `components/ui/AegisPagination.vue`
**Replaces:** Pagination markup in activity, messages, network, etc.
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| currentPage | Number | yes | — | |
| totalPages | Number | yes | — | |
| perPage | Number | no | 20 | |

**Emits:** `page-change(page: Number)`
**CSS classes used:** `.pagination`, `.pagination-btn`, `.pagination-current`

---

#### `components/ui/AegisUpgradeModal.vue`
**Replaces:** Upgrade CTA wiring — `openModal('upgradeModal')` calls from locked CTAs
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| modelValue | Boolean | yes | — | v-model |
| portal | String | no | `'provider'` | Determines which upgrade copy to show |

**Emits:** `update:modelValue`
**CSS classes used:** inherits from `AegisModal`
**Notes:** For CS portal, delegates to `UpgradeCSModal` internally. For provider, shows tier upgrade copy from `pricing` store.

---

### Modal Components

---

#### `components/modals/ReferralModal.vue`
**Replaces:** `_shared/modals/referral_modal.php`
**Used by:** `pages/provider/Referrals.vue`, `pages/provider/Network.vue`
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| modelValue | Boolean | yes | — | v-model |
| referral | Object | no | `null` | Pre-populated referral for editing |

**Emits:** `update:modelValue`, `saved(referral)`
**CSS classes used:** inherits from `AegisModal`

---

#### `components/modals/JobDetailModal.vue`
**Replaces:** `_shared/modals/job_detail_modal.php`
**Used by:** `pages/provider/JobPostings.vue`, `pages/business-partner/FindJobs.vue`
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| modelValue | Boolean | yes | — | |
| job | Object | no | `null` | Job object |
| viewerRole | String | no | `'provider'` | `'provider'` \| `'bp'` — controls CTA shown |

**Emits:** `update:modelValue`, `proposal-sent`, `job-edited`

---

#### `components/modals/ProposalModal.vue`
**Replaces:** `_shared/modals/proposal_modal.php`
**Used by:** `pages/business-partner/FindJobs.vue`, `pages/business-partner/Proposals.vue`
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| modelValue | Boolean | yes | — | |
| job | Object | no | `null` | Target job |
| proposal | Object | no | `null` | Existing proposal for edit |

**Emits:** `update:modelValue`, `submitted(proposal)`

---

#### `components/modals/UpgradeCSModal.vue`
**Replaces:** `_shared/modals/upgrade_cs_modal.php`
**Used by:** `AegisUpgradeModal.vue` (portal=cs), any locked CS portal CTA
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| modelValue | Boolean | yes | — | |

**Emits:** `update:modelValue`, `upgraded`
**Notes:** Two-step flow: plan summary → card payment. Plan copy comes from `pricing` Pinia store (not hardcoded). Step managed via internal `ref(1)`.

---

### Feature Components

---

#### `components/features/ActivityFeed.vue`
**Replaces:** Inline activity section on dashboard pages + `_shared/activity_body.php` (inline use only — full page is `pages/shared/Activity.vue`)
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| items | Array | yes | — | Activity event objects |
| maxItems | Number | no | 5 | How many to show inline |
| showViewAll | Boolean | no | true | Show "View all" link |

**Emits:** `mark-read(eventId)`
**CSS classes used:** `.act-list`, `.act-item`, `.act-icon`, `.act-body`, `.act-meta`

---

#### `components/features/MessagesThread.vue`
**Replaces:** 3-column message thread + composer inside `_shared/templates/messages.php`
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| thread | Object | yes | — | Thread object with messages array |
| currentUser | Object | yes | — | |
| portalBuckets | Array | no | `[]` | Contact filter buckets for this role |

**Emits:** `message-sent(text)`, `attachment-added(file)`, `thread-changed(threadId)`
**CSS classes used:** `.msg-layout`, `.msg-sidebar`, `.msg-thread`, `.msg-composer`, `.msg-bubble`

---

#### `components/features/VaultZone.vue`
**Replaces:** `.vault-zone` markup blocks in `vault.php` (all 4 zones)
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| zone | String | yes | — | `'emergency'` \| `'credentials'` \| `'roster'` \| `'contacts'` |
| items | Array | yes | — | Zone items |
| isUnlocked | Boolean | no | false | Vault sealed/unsealed state |
| isCS | Boolean | no | false | CS viewing mode |

**Emits:** `add-item`, `edit-item(item)`, `delete-item(item)`, `copy-credential(field)`
**CSS classes used:** `.vault-zone`, `.vault-zone-header`, `.vault-item`, `.vault-locked-mask`

---

#### `components/features/PlanStatusCard.vue`
**Replaces:** Continuity Plan status widget on provider dashboard
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| plan | Object | no | `null` | Plan object (null = no plan yet) |
| csCount | Number | no | 0 | Active CS count |
| lastReviewAt | String | no | `null` | ISO date |

**Emits:** `open-plan`, `open-review-modal`
**CSS classes used:** `.plan-status-card`, `.plan-status-badge`

---

#### `components/features/ProfileStrip.vue`
**Replaces:** `_shared/profile_strip.php`
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| user | Object | yes | — | Current user |
| completion | Number | no | 0 | 0–100 completion percent |
| incompleteFields | Array | no | `[]` | Field keys missing |

**Emits:** `go-to-field(fieldKey)`
**CSS classes used:** `.profile-strip`, `.profile-strip-bar`, `.profile-strip-label`

---

#### `components/features/StewardCard.vue`
**Replaces:** CS/SS designation card markup
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| steward | Object | yes | — | Steward user object + status |
| type | String | no | `'cs'` | `'cs'` \| `'ss'` |
| editable | Boolean | no | false | Show edit/remove actions |

**Emits:** `remove(steward)`, `message(steward)`, `view-profile(steward)`
**CSS classes used:** `.steward-card`, `.steward-avatar`, `.steward-meta`

---

#### `components/features/IncidentBanner.vue`
**Replaces:** Emergency incident alert strip (top of page, all portals)
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| incident | Object | no | `null` | Active incident object |
| portalRole | String | yes | — | Current portal role |

**Emits:** `view-incident`, `dismiss`
**CSS classes used:** `.incident-banner`, `.incident-banner-type`, `.incident-banner-actions`
**Notes:** Rendered conditionally by `AppLayout` when `incident.hasEmergency`.

---

#### `components/features/SupportWidget.vue`
**Replaces:** Floating support/feedback button (pending B2 build commitment)
**Props:**
| Prop | Type | Required | Default | Notes |
|------|------|----------|---------|-------|
| portalRole | String | yes | — | For routing feedback to right channel |

**Emits:** `open-feedback`, `open-help`
**CSS classes used:** `.support-widget`, `.support-widget-btn`

---

## Section 4 — All Pages Documented

### Auth Pages

---

#### `pages/auth/Login.vue`
**Route:** `GET /login`
**Controller:** `App\Http\Controllers\Auth\LoginController@show`
**Replaces:** Onboarding login step
**Layout:** `AuthLayout`
**Inertia props:** none (stateless)
**Emits on submit:** POST `/login` via `useForm()`

---

#### `pages/auth/Register.vue`
**Route:** `GET /register`
**Controller:** `App\Http\Controllers\Auth\RegisterController@show`
**Replaces:** Onboarding registration steps
**Layout:** `AuthLayout`
**Inertia props:**
| Prop | Type | Notes |
|------|------|-------|
| inviteToken | String\|null | Pre-fills invite path |
| role | String\|null | Pre-selects role |

---

#### `pages/auth/ResetPassword.vue`
**Route:** `GET /password/reset/{token}`
**Controller:** `App\Http\Controllers\Auth\PasswordResetController@show`
**Layout:** `AuthLayout`
**Inertia props:** `token`, `email`

---

### Shared Pages (all portals)

---

#### `pages/shared/Overview.vue`
**Route:** `GET /{portal}/overview` (e.g. `/provider/overview`, `/cs/overview`)
**Controller:** `App\Http\Controllers\Shared\OverviewController@index`
**Replaces:** `_shared/templates/overview.php`
**Layout:** `AppLayout`
**Inertia props:**
| Prop | Type | Source |
|------|------|--------|
| overviewData | Object | `OverviewService::getBundle($user)` — same shape as `aegis_overview_data()` |
| checklist | Array | `SetupService::getChecklist($user)` |

**Child components used:** `AegisHeroBanner`, `AegisCard`
**Pinia stores accessed:** `auth`
**Notes:** All copy comes from `overviewData` prop — no hardcoded strings. Portal context from `auth.portal`.

---

#### `pages/shared/Messages.vue`
**Route:** `GET /{portal}/messages`
**Controller:** `App\Http\Controllers\Shared\MessagesController@index`
**Replaces:** `_shared/templates/messages.php`
**Layout:** `AppLayout`
**Inertia props:**
| Prop | Type | Source |
|------|------|--------|
| threads | Array | `MessageService::getThreads($user)` |
| activeThreadId | String\|null | From query param |
| contactBuckets | Array | `MessageService::getBuckets($role)` |
| templates | Array | Role-scoped message templates |

**Child components used:** `MessagesThread`, `AegisModal` (×14 modals), `AegisDropzone`
**Pinia stores accessed:** `auth`, `notifications`
**Composables used:** `useModal`, `useToast`

---

#### `pages/shared/Activity.vue`
**Route:** `GET /{portal}/activity`
**Controller:** `App\Http\Controllers\Shared\ActivityController@index`
**Replaces:** `_shared/templates/activity.php` + `_shared/activity_body.php`
**Layout:** `AppLayout`
**Inertia props:**
| Prop | Type | Source |
|------|------|--------|
| events | Array | `ActivityService::getEvents($user, $filters, $page)` |
| totalPages | Number | |
| modules | Array | `ActivityService::getModules($role)` |
| unreadCount | Number | |
| filters | Object | From query params |

**Child components used:** `AegisPagination`, `AegisEmptyState`, `AegisBadge`
**Composables used:** `useActivity`

---

#### `pages/shared/Support.vue`
**Route:** `GET /{portal}/support`
**Controller:** `App\Http\Controllers\Shared\SupportController@index`
**Replaces:** `_shared/templates/support.php`
**Layout:** `AppLayout`
**Inertia props:** `faqItems`, `helpCategories`
**Child components used:** `AegisCard`, `SupportWidget`

---

### Provider Portal Pages

---

#### `pages/provider/Dashboard.vue`
**Route:** `GET /provider/dashboard`
**Controller:** `App\Http\Controllers\Provider\DashboardController@index`
**Replaces:** `provider-portal/dashboard.php`
**Layout:** `AppLayout`
**Inertia props:**
| Prop | Type | Source |
|------|------|--------|
| stats | Object | `DashboardService::getStats($user)` |
| planStatus | Object | `PlanService::getStatus($user)` |
| recentActivity | Array | `ActivityService::getRecent($user, 5)` |
| hasEmergency | Boolean | `IncidentService::hasActive($user)` |
| csAssigned | Array | `StewardService::getActiveCS($user)` |
| ssAssigned | Array | `StewardService::getActiveSS($user)` |
| tier | String | `$user->tier` |

**Child components used:** `AegisHeroBanner`, `AegisStatChip` (×4), `PlanStatusCard`, `IncidentBanner`, `ActivityFeed`, `ProfileStrip`
**Pinia stores accessed:** `auth`, `incident`, `notifications`
**Composables used:** `useModal`, `useToast`, `useUpgrade`
**Modals on this page:** `activateSuccessionModal`, `annualReviewModal`, `renewInsuranceModal`, `addCEUModal`

---

#### `pages/provider/EditProfile.vue`
**Route:** `GET /provider/edit-profile`
**Controller:** `App\Http\Controllers\Provider\ProfileController@edit`
**Replaces:** `provider-portal/edit-profile.php`
**Layout:** `AppLayout`
**Inertia props:** `user`, `specialties`, `states`, `completionData`
**Child components used:** `AegisHeroBanner`, `ProfileStrip`, `AegisDropzone`, `AegisCard`
**Composables used:** `useToast`
**Write:** POST `/api/profile` (save_profile domain)

---

#### `pages/provider/ContinuityPlan.vue`
**Route:** `GET /provider/continuity-plan`
**Controller:** `App\Http\Controllers\Provider\ContinuityPlanController@index`
**Replaces:** `provider-portal/continuity-plan.php`
**Layout:** `AppLayout`
**Inertia props:** `plan`, `planTasks`, `csAssigned`, `ssAssigned`, `incidentHistory`, `tier`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisBadge`, `AegisStatChip`, `PlanStatusCard`
**Composables used:** `useModal`, `useToast`, `useVault`, `useIncident`
**Modals:** `draftPlanModal`, `finalizePlanModal`, `activateSuccessionModal`, `addTaskModal`, `verifyEmergencyModal`
**Write:** POST `/api/plan` (save_plan domain)

---

#### `pages/provider/ContinuityStewards.vue`
**Route:** `GET /provider/continuity-stewards`
**Controller:** `App\Http\Controllers\Provider\ContinuityStewardsController@index`
**Replaces:** `provider-portal/continuity-stewards.php`
**Inertia props:** `stewards`, `plan`, `inviteableUsers`, `tier`
**Child components used:** `AegisHeroBanner`, `StewardCard`, `ReferralModal`, `AegisEmptyState`
**Modals:** `inviteCSModal`, `removeStewardModal`, `upgradeModal`
**Write:** POST `/api/steward` (save_steward domain)

---

#### `pages/provider/SupportStewards.vue`
**Route:** `GET /provider/support-stewards`
**Controller:** `App\Http\Controllers\Provider\SupportStewardsController@index`
**Replaces:** `provider-portal/support-stewards.php`
**Inertia props:** `stewards`, `plan`, `tier`
**Child components used:** `AegisHeroBanner`, `StewardCard`, `AegisEmptyState`
**Modals:** `inviteSSModal`, `removeStewardModal`
**Write:** POST `/api/steward` (save_steward domain)

---

#### `pages/provider/Network.vue`
**Route:** `GET /provider/network`
**Controller:** `App\Http\Controllers\Provider\NetworkController@index`
**Replaces:** `provider-portal/network.php`
**Inertia props:** `clinicalConnections`, `bpConnections`, `pendingRequests`, `shadowConnections`, `referralCandidates`, `referralNetwork`, `recommendedPartnerCategories`, `recommendedShadowProviders`, `searchProviders`, `referralRoster`, `roster`, `bpDirectory`, `stats`, `networkConfig`
**Child components used:** `AegisHeroBanner`, `AegisIcon`, `AegisModal`, `AegisPagination`, `AegisEmptyState`
**Tabs:** Integrative Care Network / Business Partners / Referrals & Tools (Referral List / My Shadows / Configuration)
**Modals:** pending-requests, connect, invite-provider, shadow-add, hire, request-quote
**Writes:**
- `POST /provider/network/connect` -> `provider.network.connect`
- `POST /provider/network/shadow` -> `provider.network.shadow.add`
- `DELETE /provider/network/shadow/{shadowConnection}` -> `provider.network.shadow.remove`
- `PUT /provider/network/config` -> `provider.network.config.save` (atomic all-fields config save)
- `POST /provider/network/config/reset` -> `provider.network.config.reset`

**`networkConfig` prop shape** (hydrates the Configuration tab; loaded by `NetworkController::loadNetworkConfig`):
- Arrays: `team`, `specialties`, `approaches`, `insurance`, `credentials`, `services`, `states`, `demographics`, `languages`, `identity`, `rates`
- Scalars: `license_number`, `primary_state`, `years_in_practice`, `session_length`, `rate_per_session`, `sliding_scale_min/max`, `max_partners`, `geographic_radius`, `referral_urgency`, `ai_match_frequency`, `sex_assigned`
- Objects: `notifications` (7 keyed bools), `privacy` (7 keyed bools)

**Config storage map** (where each field persists):
- `specialties` -> `users.specialty` (JSON); `insurance` -> `users.network_insurance` (JSON)
- `approaches`, `services` -> `users.profile_meta` JSON blob
- `team`->`network_partners`, `states`->`licensed_states`, `credentials`->`cfg_credentials`, `demographics`->`cfg_demographics`, `identity`->`cfg_identity`, `rates`->`cfg_rates`, `languages`->`languages`, `notifications`->`cfg_notifications`, `privacy`->`cfg_privacy` (all `user_meta` JSON)
- Scalar fields -> individual `user_meta` string rows; `ai_match_frequency` -> `ai_shadow_settings.frequency`

**Scoping rule:** `referralCandidates` and `recommendedShadowProviders` are **practitioner-only** — filtered `role === 'practitioner'`; CS/SS/BP users never appear in shadow or referral slots.

---

#### `pages/provider/Services.vue`
**Route:** `GET /provider/services`
**Controller:** `App\Http\Controllers\Provider\ServicesController@index`
**Replaces:** `provider-portal/services.php`
**Inertia props:** `services`, `bookingInquiries`, `servicesEnabled`, `tier`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisToggle`, `AegisEmptyState`
**Modals:** `addServiceModal`, `editServiceModal`, `bookingDetailModal`, `enableServicesModal`
**Write:** POST `/api/service` (save_service domain)

---

#### `pages/provider/JobPostings.vue`
**Route:** `GET /provider/job-postings`
**Controller:** `App\Http\Controllers\Provider\JobPostingsController@index`
**Replaces:** `provider-portal/job-postings.php`
**Inertia props:** `jobs`, `proposals`, `tier`
**Child components used:** `AegisHeroBanner`, `JobDetailModal`, `AegisEmptyState`
**Modals:** `postJobModal`, `proposalReviewModal`, `hireModal`
**Write:** POST `/api/job` (save_job domain)

---

#### `pages/provider/Referrals.vue`
**Route:** `GET /provider/referrals`
**Controller:** `App\Http\Controllers\Provider\ReferralsController@index`
**Replaces:** `provider-portal/referrals.php`
**Inertia props:** `referrals`, `contacts`, `tier`
**Child components used:** `AegisHeroBanner`, `ReferralModal`, `AegisBadge`, `AegisEmptyState`
**Write:** POST `/api/referral` (save_referral domain)

---

#### `pages/provider/Vault.vue`
**Route:** `GET /provider/vault`
**Controller:** `App\Http\Controllers\Provider\VaultController@index`
**Replaces:** `provider-portal/vault.php`
**Inertia props:** `vaultItems`, `isUnlocked`, `activeIncident`, `tier`
**Child components used:** `AegisHeroBanner`, `VaultZone` (×4), `AegisDropzone`
**Composables used:** `useVault`, `useModal`, `useToast`
**Modals:** `addItemModal`, `editItemModal`, `revealCredentialModal`, `unlockVaultModal`
**Write:** POST `/api/vault` (save_vault domain)
**Notes:** AES-256-GCM credential envelope logic stays server-side. Client never sees plaintext except post-unlock reveal.

---

#### `pages/provider/ImportantDocuments.vue`
**Route:** `GET /provider/important-documents`
**Controller:** `App\Http\Controllers\Provider\DocumentsController@index`
**Replaces:** `provider-portal/important-documents.php`
**Inertia props:** `agreements`, `library`, `tier`
**Child components used:** `AegisHeroBanner`, `AegisBadge`, `AegisDropzone`, `AegisEmptyState`
**Modals:** `uploadDocModal`, `signAgreementModal`, `sendForSignatureModal`
**Write:** POST `/api/document` (save_document domain)

---

#### `pages/provider/News.vue`
**Route:** `GET /provider/news`
**Controller:** `App\Http\Controllers\Provider\NewsController@index`
**Replaces:** `provider-portal/news.php`
**Inertia props:** `posts`, `categories`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisBadge`

---

#### `pages/provider/Events.vue`
**Route:** `GET /provider/events`
**Controller:** `App\Http\Controllers\Provider\EventsController@index`
**Replaces:** `provider-portal/events.php`
**Inertia props:** `events`, `registrations`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisBadge`
**Modals:** `registerModal`, `cancelModal`
**Write:** POST `/api/event` (save_event domain)

---

#### `pages/provider/Finances.vue`
**Route:** `GET /provider/finances`
**Controller:** `App\Http\Controllers\Provider\FinancesController@index`
**Replaces:** `provider-portal/finances.php`
**Inertia props:** `invoices`, `subscriptionStatus`, `stripeConnected`, `tier`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisBadge`
**Modals:** `approveInvoiceModal`, `disputeInvoiceModal`
**Write:** POST `/api/finance` (save_finance domain)
**Notes:** Stripe integration pending. `stripeConnected` drives Connected/Restricted/Disconnected UI states.

---

#### `pages/provider/Settings.vue`
**Route:** `GET /provider/settings`
**Controller:** `App\Http\Controllers\Provider\SettingsController@index`
**Replaces:** `provider-portal/settings.php`
**Inertia props:** `user`, `panels`, `pricingData`, `activePanel`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisToggle`, `AegisBadge`
**Composables used:** `useToast`
**Write:** POST `/api/pref` (save_pref domain), POST `/api/profile`

---

### Continuity Steward Portal Pages

---

#### `pages/continuity-steward/Dashboard.vue`
**Route:** `GET /cs/dashboard`
**Controller:** `App\Http\Controllers\CS\DashboardController@index`
**Replaces:** `continuity-steward-portal/dashboard.php`
**Layout:** `AppLayout`
**Inertia props:**
| Prop | Type | Source |
|------|------|--------|
| stats | Object | `CSDashboardService::getStats($user)` |
| providers | Array | `CSProviderService::getAssigned($user)` |
| tasks | Array | `CSTaskService::getPending($user)` |
| recentActivity | Array | `ActivityService::getRecent($user, 5)` |
| hasEmergency | Boolean | `IncidentService::hasActive($user)` |
| isInvitedCS | Boolean | `$user->cs_account_type === 'invited'` |
| tier | String | |

**Child components used:** `AegisHeroBanner`, `AegisStatChip`, `PlanStatusCard`, `IncidentBanner`, `ActivityFeed`, `StewardCard`
**Composables used:** `useModal`, `useToast`, `useUpgrade`
**Modals:** `verifyEmergencyModal`, `addTaskModal`, `addCheckinModal`, `contactSSModal`, `monthlyReadinessModal`

---

#### `pages/continuity-steward/EditProfile.vue`
**Route:** `GET /cs/edit-profile`
**Controller:** `App\Http\Controllers\CS\ProfileController@edit`
**Replaces:** `continuity-steward-portal/edit-profile.php`
**Inertia props:** `user`, `completionData`, `isInvitedCS`
**Child components used:** `ProfileStrip`, `AegisDropzone`, `AegisCard`
**Write:** POST `/api/profile`

---

#### `pages/continuity-steward/Providers.vue`
**Route:** `GET /cs/providers`
**Controller:** `App\Http\Controllers\CS\ProvidersController@index`
**Replaces:** `continuity-steward-portal/providers.php`
**Inertia props:** `providers`, `isInvitedCS`, `tier`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisBadge`, `AegisEmptyState`
**Notes:** `isInvitedCS` gates multi-practitioner UI. Invited CS gets locked single-practitioner view.
**Modals:** `providerDetailModal`, `contactProviderModal`

---

#### `pages/continuity-steward/MyTasks.vue`
**Route:** `GET /cs/my-tasks`
**Controller:** `App\Http\Controllers\CS\TasksController@index`
**Replaces:** `continuity-steward-portal/my-tasks.php`
**Inertia props:** `tasks`, `providers`, `tier`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisBadge`
**Modals:** `taskDetailModal`, `addTaskModal`
**Write:** POST `/api/plan` (task-complete action)

---

#### `pages/continuity-steward/ContinuityManagement.vue`
**Route:** `GET /cs/continuity-management`
**Controller:** `App\Http\Controllers\CS\ContinuityManagementController@index`
**Replaces:** `continuity-steward-portal/continuity-management.php`
**Inertia props:** `plans`, `checkins`, `certifications`, `isInvitedCS`, `tier`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisBadge`, `PlanStatusCard`
**Modals:** `certifyPlanModal`, `addCheckinModal`, `exceptionFlagModal`
**Write:** POST `/api/certify` (save_certify domain), POST `/api/plan`

---

#### `pages/continuity-steward/ImportantDocuments.vue`
**Route:** `GET /cs/important-documents`
**Controller:** `App\Http\Controllers\CS\DocumentsController@index`
**Replaces:** `continuity-steward-portal/important-documents.php`
**Inertia props:** `agreements`, `library`
**Child components used:** `AegisHeroBanner`, `AegisBadge`, `AegisEmptyState`
**Write:** POST `/api/document`

---

#### `pages/continuity-steward/Vault.vue`
**Route:** `GET /cs/vault`
**Controller:** `App\Http\Controllers\CS\VaultController@index`
**Replaces:** `continuity-steward-portal/vault.php`
**Inertia props:** `vaultItems`, `isUnlocked`, `activeIncident`
**Child components used:** `VaultZone` (×4 read-only modes), `IncidentBanner`
**Composables used:** `useVault`
**Notes:** CS vault is read-only post-unlock. No add/edit/delete. Unlock only on verified incident.

---

#### `pages/continuity-steward/Finances.vue`
**Route:** `GET /cs/finances`
**Controller:** `App\Http\Controllers\CS\FinancesController@index`
**Replaces:** `continuity-steward-portal/finances.php`
**Inertia props:** `invoices`, `stripeConnected`, `earnings`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisBadge`
**Notes:** Stripe Connect Express state (Connected/Restricted/Disconnected) drives UI. Pending Stripe account.

---

#### `pages/continuity-steward/Settings.vue`
**Route:** `GET /cs/settings`
**Controller:** `App\Http\Controllers\CS\SettingsController@index`
**Replaces:** `continuity-steward-portal/settings.php`
**Inertia props:** `user`, `panels`, `stripeConnected`, `isInvitedCS`, `pricingData`, `activePanel`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisToggle`, `UpgradeCSModal`
**Notes:** `isInvitedCS` gates Stripe Connect panel and upgrade CTA.

---

### Support Steward Portal Pages

---

#### `pages/support-steward/Dashboard.vue`
**Route:** `GET /ss/dashboard`
**Controller:** `App\Http\Controllers\SS\DashboardController@index`
**Replaces:** `support-steward-portal/dashboard.php`
**Inertia props:** `stats`, `providers`, `tasks`, `recentActivity`, `hasEmergency`
**Child components used:** `AegisHeroBanner`, `AegisStatChip`, `IncidentBanner`, `ActivityFeed`
**Composables used:** `useModal`, `useToast`
**Modals:** `reportIncidentModal`, `addTaskModal`

---

#### `pages/support-steward/EditProfile.vue`
**Route:** `GET /ss/edit-profile`
**Replaces:** `support-steward-portal/edit-profile.php`
**Inertia props:** `user`, `completionData`
**Child components used:** `ProfileStrip`, `AegisDropzone`, `AegisCard`

---

#### `pages/support-steward/Providers.vue`
**Route:** `GET /ss/providers`
**Replaces:** `support-steward-portal/providers.php`
**Inertia props:** `providers`
**Child components used:** `AegisHeroBanner`, `StewardCard`, `AegisEmptyState`
**Modals:** `providerDetailModal`, `notifyUnresponsiveModal`

---

#### `pages/support-steward/ContinuityStewards.vue`
**Route:** `GET /ss/continuity-stewards`
**Replaces:** `support-steward-portal/continuity-stewards.php`
**Inertia props:** `stewards`, `providers`
**Child components used:** `AegisHeroBanner`, `StewardCard`
**Notes:** Read-only list. SS can notify "CS Unresponsive" — write event only, no designation change.
**Write:** POST `/api/steward` (notify action)

---

#### `pages/support-steward/MyTasks.vue`
**Route:** `GET /ss/my-tasks`
**Replaces:** `support-steward-portal/my-tasks.php`
**Inertia props:** `tasks`, `providers`
**Child components used:** `AegisHeroBanner`, `AegisCard`
**Write:** POST `/api/plan` (task-complete action)

---

#### `pages/support-steward/CriticalIncidentLog.vue`
**Route:** `GET /ss/critical-incident-log`
**Replaces:** `support-steward-portal/critical-incident-log.php`
**Inertia props:** `incidents`, `incidentTypes`, `providers`
**Child components used:** `AegisHeroBanner`, `AegisBadge`, `AegisEmptyState`
**Modals:** `reportIncidentModal`, `incidentDetailModal`, `verifyIncidentModal`
**Write:** POST `/api/incident` (save_incident domain)
**Notes:** 7 approved incident types only (no freeform). 4 are opt-in (Missing, Detainment, Natural Disaster, Geopolitical).

---

#### `pages/support-steward/ImportantDocuments.vue`
**Route:** `GET /ss/important-documents`
**Replaces:** `support-steward-portal/important-documents.php`
**Inertia props:** `agreements`, `library`
**Child components used:** `AegisHeroBanner`, `AegisBadge`, `AegisEmptyState`

---

#### `pages/support-steward/Settings.vue`
**Route:** `GET /ss/settings`
**Replaces:** `support-steward-portal/settings.php`
**Inertia props:** `user`, `panels`, `activePanel`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisToggle`

---

### Business Partner Portal Pages

---

#### `pages/business-partner/Dashboard.vue`
**Route:** `GET /bp/dashboard`
**Controller:** `App\Http\Controllers\BP\DashboardController@index`
**Replaces:** `biz-portal/dashboard.php`
**Inertia props:** `stats`, `activeContracts`, `openProposals`, `jobMatches`, `recentActivity`, `bpType`, `isAgency`
**Child components used:** `AegisHeroBanner`, `AegisStatChip`, `ActivityFeed`, `AegisCard`
**Notes:** `bpType` (`'agency'`|`'freelancer'`) and `isAgency` come from DB — never from `$_GET`.

---

#### `pages/business-partner/EditProfile.vue`
**Route:** `GET /bp/edit-profile`
**Replaces:** `biz-portal/edit-profile.php`
**Inertia props:** `user`, `completionData`, `bpType`
**Child components used:** `ProfileStrip`, `AegisDropzone`, `AegisCard`

---

#### `pages/business-partner/FindJobs.vue`
**Route:** `GET /bp/find-jobs`
**Replaces:** `biz-portal/find-jobs.php`
**Inertia props:** `jobs`, `savedJobs`, `filters`, `categories`
**Child components used:** `AegisHeroBanner`, `JobDetailModal`, `ProposalModal`, `AegisBadge`, `AegisEmptyState`
**Write:** POST `/api/job` (proposal action), POST `/api/job` (save-job action)

---

#### `pages/business-partner/Proposals.vue`
**Route:** `GET /bp/proposals`
**Replaces:** `biz-portal/proposals.php`
**Inertia props:** `proposals`, `savedJobs`
**Child components used:** `AegisHeroBanner`, `ProposalModal`, `AegisBadge`
**Modals:** `editProposalModal`, `withdrawModal`
**Write:** POST `/api/job` (withdraw/edit proposal)

---

#### `pages/business-partner/Contracts.vue`
**Route:** `GET /bp/contracts`
**Replaces:** `biz-portal/contracts.php`
**Inertia props:** `contracts`, `bpType`, `isAgency`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisBadge`
**Modals:** `contractDetailModal`, `signModal`

---

#### `pages/business-partner/Milestones.vue`
**Route:** `GET /bp/milestones`
**Replaces:** `biz-portal/milestones.php`
**Inertia props:** `milestones`, `bpType`, `isAgency`
**Child components used:** `AegisHeroBanner`, `AegisBadge`, `AegisEmptyState`
**Modals:** `submitWorkModal`, `milestoneDetailModal`
**Write:** POST `/api/job` (milestone-submit action)

---

#### `pages/business-partner/Invoices.vue`
**Route:** `GET /bp/invoices`
**Replaces:** `biz-portal/invoices.php`
**Inertia props:** `invoices`, `contracts`
**Child components used:** `AegisHeroBanner`, `AegisBadge`, `AegisEmptyState`
**Modals:** `createInvoiceModal`, `invoiceDetailModal`
**Write:** POST `/api/finance` (save_finance domain)

---

#### `pages/business-partner/Finances.vue`
**Route:** `GET /bp/finances`
**Replaces:** `biz-portal/finances.php`
**Inertia props:** `earnings`, `payoutHistory`, `stripeConnected`
**Child components used:** `AegisHeroBanner`, `AegisCard`

---

#### `pages/business-partner/PaymentSetup.vue`
**Route:** `GET /bp/payment-setup`
**Replaces:** `biz-portal/payment-setup.php`
**Inertia props:** `stripeConnected`, `stripeState`, `taxInfo`, `bpType`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisBadge`
**Notes:** Stripe Connect Express onboarding. `stripeState`: `'connected'`|`'restricted'`|`'disconnected'`.

---

#### `pages/business-partner/Team.vue`
**Route:** `GET /bp/team`
**Replaces:** `biz-portal/team.php`
**Inertia props:** `members`, `roles`, `departments`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisBadge`, `AegisEmptyState`
**Modals:** `inviteMemberModal`, `editMemberModal`, `removeMemberModal`
**Notes:** Agency-only page. Freelancer BP has no team route.

---

#### `pages/business-partner/Settings.vue`
**Route:** `GET /bp/settings`
**Replaces:** `biz-portal/settings.php`
**Inertia props:** `user`, `panels`, `pricingData`, `stripeConnected`, `bpType`, `activePanel`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisToggle`

---

### Admin Portal Pages

---

#### `pages/admin/Dashboard.vue`
**Route:** `GET /admin/dashboard`
**Controller:** `App\Http\Controllers\Admin\DashboardController@index`
**Layout:** `AppLayout` (admin variant — sidebar shows admin nav only)
**Inertia props:** `stats` (userCounts, revenueSnapshot, activeIncidents, openComplaints), `recentActivity`
**Child components used:** `AegisHeroBanner`, `AegisStatChip`, `ActivityFeed`

---

#### `pages/admin/Packages.vue`
**Route:** `GET /admin/packages`
**Controller:** `App\Http\Controllers\Admin\PackagesController@index`
**Inertia props:** `packages`, `pricingData`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisBadge`
**Modals:** `editPackageModal`
**Write:** POST `/api/admin/packages`

---

#### `pages/admin/Users.vue`
**Route:** `GET /admin/users`
**Controller:** `App\Http\Controllers\Admin\UsersController@index`
**Inertia props:** `users`, `roles`, `filters`
**Child components used:** `AegisHeroBanner`, `AegisBadge`, `AegisPagination`, `AegisEmptyState`
**Modals:** `userDetailModal`, `suspendModal`, `roleChangeModal`
**Write:** POST `/api/admin/users`

---

#### `pages/admin/Roles.vue`
**Route:** `GET /admin/roles`
**Controller:** `App\Http\Controllers\Admin\RolesController@index`
**Inertia props:** `roles`, `permissions`
**Child components used:** `AegisHeroBanner`, `AegisCard`, `AegisToggle`
**Write:** POST `/api/admin/roles`

---

#### `pages/admin/Payments.vue`
**Route:** `GET /admin/payments`
**Controller:** `App\Http\Controllers\Admin\PaymentsController@index`
**Inertia props:** `transactions`, `stripeSnapshot`, `filters`
**Child components used:** `AegisHeroBanner`, `AegisBadge`, `AegisPagination`
**Modals:** `transactionDetailModal`, `refundModal`

---

#### `pages/admin/Complaints.vue`
**Route:** `GET /admin/complaints`
**Controller:** `App\Http\Controllers\Admin\ComplaintsController@index`
**Inertia props:** `complaints`, `filters`
**Child components used:** `AegisHeroBanner`, `AegisBadge`, `AegisPagination`, `AegisEmptyState`
**Modals:** `complaintDetailModal`, `resolveModal`
**Write:** POST `/api/admin/complaints`

---

### Public Profile Pages

---

#### `pages/public/ProviderProfile.vue`
**Route:** `GET /public/provider?slug={slug}`
**Controller:** `App\Http\Controllers\Public\ProviderProfileController@show`
**Replaces:** `/public/provider.php`
**Layout:** `PublicLayout`
**Inertia props:** `profile` (from `aegis_profile_data()` equivalent), `viewerRole`, `isOwner`
**Child components used:** `AegisBadge`, `AegisCard`

---

#### `pages/public/ContinuityStewardProfile.vue`
**Route:** `GET /public/continuity-steward?slug={slug}`
**Replaces:** `/public/continuity_steward.php`
**Layout:** `PublicLayout`
**Inertia props:** `profile`, `viewerRole`, `isOwner`
**Notes:** Business CS only — Invited CS has no public profile.

---

#### `pages/public/SupportStewardProfile.vue`
**Route:** `GET /public/support-steward?slug={slug}`
**Replaces:** `/public/support_steward.php`
**Layout:** `PublicLayout`
**Inertia props:** `profile`, `viewerRole`, `isOwner`
**Notes:** Relationship-gated for non-owners. Self-view always allowed.

---

#### `pages/public/BusinessProfile.vue`
**Route:** `GET /public/business?slug={slug}`
**Replaces:** `/public/business.php`
**Layout:** `PublicLayout`
**Inertia props:** `profile`, `viewerRole`, `isOwner`

---

## Section 5 — Email Templates → Blade Mapping

All PHP email templates in `_shared/emails/` move to `resources/views/emails/`. Variable syntax changes from `$data['key']` to `{{ $key }}` (or `{!! $key !!}` for HTML-safe output).

```
resources/views/emails/
├── auth/
│   ├── welcome.blade.php                  ← was emails/welcome.php — $data['name'] → {{ $name }}
│   ├── email-verification.blade.php       ← verification link
│   ├── password-reset.blade.php           ← reset link
│   ├── invitation-practitioner.blade.php  ← onboarding invite (practitioner)
│   ├── invitation-cs.blade.php            ← onboarding invite (CS — invited path)
│   ├── invitation-ss.blade.php            ← onboarding invite (SS)
│   ├── invitation-bp.blade.php            ← onboarding invite (BP)
│   ├── mfa-code.blade.php                 ← MFA token delivery
│   └── account-suspended.blade.php        ← admin action
│
├── plan/
│   ├── plan-finalized.blade.php           ← notify CS on finalization
│   ├── plan-updated.blade.php             ← notify CS on plan edit
│   └── annual-review-due.blade.php        ← 365-day reminder
│
├── steward/
│   ├── cs-invited.blade.php               ← CS designation invite
│   ├── cs-accepted.blade.php              ← notify provider on accept
│   ├── cs-declined.blade.php              ← notify provider on decline
│   ├── ss-invited.blade.php               ← SS designation invite
│   └── ss-accepted.blade.php              ← notify provider on accept
│
├── incident/
│   ├── incident-activated.blade.php       ← CS + SS notify on activation
│   ├── incident-resolved.blade.php        ← all parties on resolution
│   └── cs-unresponsive-alert.blade.php    ← SS escalation notify
│
├── bp/
│   ├── proposal-received.blade.php        ← provider gets proposal
│   ├── proposal-accepted.blade.php        ← BP notified
│   ├── proposal-declined.blade.php        ← BP notified
│   ├── contract-generated.blade.php       ← both parties
│   ├── milestone-submitted.blade.php      ← provider review prompt
│   └── invoice-sent.blade.php             ← provider approval prompt
│
├── network/
│   ├── connection-request.blade.php       ← network invite
│   └── connection-accepted.blade.php      ← network accept
│
├── support/
│   ├── ticket-received.blade.php          ← help desk confirmation
│   └── ticket-resolved.blade.php          ← resolution notify
│
├── admin/
│   ├── new-complaint.blade.php            ← admin alert
│   └── complaint-resolved.blade.php       ← user notify
│
├── digest/
│   └── weekly-digest.blade.php            ← cross-portal weekly summary
│
└── gaps/
    └── plan-gaps-detected.blade.php       ← automated gap detection alert
```

**Shared email layout:** `resources/views/emails/layouts/base.blade.php` — Aegis brand header, footer, unsubscribe link. All templates `@extends('emails.layouts.base')`.

**All email delivery** wires into the `aegis_log_activity()` fan-out equivalent — a Laravel event listener fires `Mail::to()` alongside the in-portal feed write.

---

## Section 6 — Composables API

---

#### `composables/useModal.js`
**Replaces:** `openModal()`, `closeModal()` from `_shared.js`

```js
const { openModal, closeModal, activeModal } = useModal()

openModal(modalId: string): void
closeModal(modalId: string): void
activeModal: Ref<string|null>
```

**Notes:** `AegisModal` uses `v-model` — `openModal`/`closeModal` are for imperative callers (e.g. post-write callbacks). Both patterns are valid.

---

#### `composables/useToast.js`
**Replaces:** `showToast()` from `_shared.js`

```js
const { showToast } = useToast()

showToast(message: string, type?: 'success'|'error'|'info'|'warning', duration?: number): void
// duration defaults to 4000ms
```

---

#### `composables/useConfirm.js`
**Replaces:** `confirmAction()` from `_shared.js` (browser `confirm()` replacement)

```js
const { confirm } = useConfirm()

confirm(options: {
  message: string,
  confirmLabel?: string,   // default: 'Confirm'
  cancelLabel?: string,    // default: 'Cancel'
  danger?: boolean         // red confirm button
}): Promise<boolean>
```

---

#### `composables/usePortal.js`
**Replaces:** Role-check logic scattered across PHP files

```js
const {
  portal,           // Ref<'provider'|'cs'|'ss'|'bp'|'admin'>
  role,             // Ref<string>  (DB role key)
  isPractitioner,   // ComputedRef<boolean>
  isCS,
  isSS,
  isBP,
  isAdmin,
  isInvitedCS,      // ComputedRef<boolean>
  isAgencyBP,       // ComputedRef<boolean>
  isFreelancerBP,
} = usePortal()
```

---

#### `composables/useDemo.js`
**Replaces:** `_shared.js` `?as=` persistence + demo-flag logic

```js
const { demoUser, switchUser, isDemo, clearDemo } = useDemo()

switchUser(userId: string): void   // navigates with ?as= param
isDemo: ComputedRef<boolean>       // true when ?as= present and DEV env
demoUser: Ref<string|null>         // current ?as= value
clearDemo(): void
```

---

#### `composables/useUpgrade.js`
**Replaces:** `openModal('upgradeModal')` calls from locked CTAs

```js
const { openUpgradeModal, canAccess } = useUpgrade()

openUpgradeModal(portal?: string): void
canAccess(feature: string): boolean   // tier gate check
```

---

#### `composables/useActivity.js`
**Replaces:** Activity feed client logic from `_shared/templates/activity.php`

```js
const { markRead, markAllRead, filterEvents } = useActivity()

markRead(eventId: string): Promise<void>
markAllRead(): Promise<void>
filterEvents(events: Array, filters: Object): Array
```

---

#### `composables/useVault.js`
**Replaces:** Vault seal/unseal state management

```js
const { isUnlocked, unlock, lock, canUnlock } = useVault()

isUnlocked: Ref<boolean>
canUnlock: ComputedRef<boolean>   // true only if activeIncident exists
unlock(credential?: string): Promise<boolean>
lock(): void
```

---

#### `composables/useIncident.js`
**Replaces:** Emergency state checks across all portals

```js
const { hasEmergency, activeIncident, incidentTypes } = useIncident()

hasEmergency: ComputedRef<boolean>
activeIncident: Ref<Object|null>
incidentTypes: Ref<Array>   // 7 approved types, 4 opt-in flagged
```

---

#### `composables/useProfileRoute.js`
**Replaces:** `viewPartyProfile(name, kind, slug)` from `_shared.js`

```js
const { viewProfile } = useProfileRoute()

viewProfile(name: string, kind: 'provider'|'steward'|'ss'|'business', slug: string): void
// Navigates to the appropriate /public/ route
```

---

## Section 7 — Pinia Stores State Shape

---

#### `stores/auth.js`

```js
state: {
  user: null,       // full user object from server
  portal: null,     // 'provider'|'cs'|'ss'|'bp'|'admin'
  role: null,       // DB role key: 'practitioner'|'continuity_steward'|etc.
  tier: null,       // 'access'|'practice' (provider only)
  bpType: null,     // 'agency'|'freelancer' (BP only)
  isInvitedCS: false,
}

getters: {
  isPractitioner: (state) => state.role === 'practitioner',
  isCS: (state) => state.role === 'continuity_steward',
  isSS: (state) => state.role === 'support_steward',
  isBP: (state) => state.role === 'business_partner',
  isAdmin: (state) => state.role === 'admin',
  isAccessTier: (state) => state.tier === 'access',
  isPracticeTier: (state) => state.tier === 'practice',
  isAgencyBP: (state) => state.bpType === 'agency',
  isFreelancerBP: (state) => state.bpType === 'freelancer',
}

actions: {
  setUser(user: Object): void,
  logout(): void,
  refreshUser(): Promise<void>,
}
```

---

#### `stores/notifications.js`

```js
state: {
  unreadCount: 0,
  items: [],           // bell dropdown feed items
  lastFetchedAt: null,
}

getters: {
  hasUnread: (state) => state.unreadCount > 0,
}

actions: {
  fetchUnread(): Promise<void>,
  markRead(eventId: string): Promise<void>,
  markAllRead(): Promise<void>,
}
```

---

#### `stores/incident.js`

```js
state: {
  hasEmergency: false,
  activeIncident: null,   // incident object or null
  lastCheckedAt: null,
}

getters: {
  incidentType: (state) => state.activeIncident?.type ?? null,
}

actions: {
  checkStatus(): Promise<void>,
  setIncident(incident: Object): void,
  clearIncident(): void,
}
```

---

#### `stores/pricing.js`

```js
state: {
  tiers: {},       // same shape as aegis_pricing() return value
  loaded: false,
}

getters: {
  accessTier: (state) => state.tiers.access ?? null,
  practiceTier: (state) => state.tiers.practice ?? null,
  csTier: (state) => state.tiers.cs ?? null,
  bpTier: (state) => state.tiers.bp ?? null,
}

actions: {
  fetchPricing(): Promise<void>,   // GET /api/pricing
}
```

---

#### `stores/ui.js`

```js
state: {
  sidebarCollapsed: false,   // never persisted (client decision: always expanded)
  mobileSidebarOpen: false,
  toastQueue: [],            // [{ id, message, type, duration }]
}

actions: {
  toggleMobileSidebar(): void,
  closeMobileSidebar(): void,
  pushToast(toast: Object): void,
  dismissToast(id: string): void,
}
```

---

## Section 8 — Config Files

---

### `vite.config.js`

```js
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: { base: null, includeAbsolute: false },
      },
    }),
  ],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources/js'),
    },
  },
})
```

---

### `resources/js/app.js`

```js
import './bootstrap'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createPinia } from 'pinia'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

// Global UI components — registered once, used everywhere
import AegisIcon from '@/components/ui/AegisIcon.vue'
import AegisModal from '@/components/ui/AegisModal.vue'
import AegisToast from '@/components/ui/AegisToast.vue'
import AegisConfirm from '@/components/ui/AegisConfirm.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'

createInertiaApp({
  resolve: (name) =>
    resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob('./pages/**/*.vue'),
    ),
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) })
    const pinia = createPinia()

    app
      .use(plugin)
      .use(pinia)
      .component('AegisIcon', AegisIcon)
      .component('AegisModal', AegisModal)
      .component('AegisToast', AegisToast)
      .component('AegisConfirm', AegisConfirm)
      .component('AegisBadge', AegisBadge)
      .mount(el)
  },
  progress: {
    color: 'var(--gold-dark)',   // CSS variable — Inertia progress bar uses Aegis gold
  },
})
```

---

### `resources/js/bootstrap.js`

```js
import axios from 'axios'

// CSRF header for all Axios requests
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Laravel Sanctum CSRF cookie
axios.defaults.withCredentials = true

window.axios = axios

// Echo / Reverb (real-time — configure when Reverb is set up)
// import Echo from 'laravel-echo'
// import Pusher from 'pusher-js'
// window.Pusher = Pusher
// window.Echo = new Echo({ broadcaster: 'reverb', ... })
```

---

### `resources/css/app.css`

```css
/* Aegis design system — source of truth. Never modify this file here.
   Edit the PHP source at _shared.css and copy on build. */
@import '/css/_shared.css';

/* Any overrides that are genuinely Laravel/Vue-environment specific only.
   If a rule would apply in PHP too, it belongs in _shared.css. */
```

---

### `resources/views/app.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title inertia>{{ config('app.name', 'Aegis') }}</title>
  <link rel="icon" href="/aegis-favicon.svg" type="image/svg+xml" />
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @inertiaHead
</head>
<body>
  @inertia
</body>
</html>
```

---

## Section 9 — Icon Strategy

**Decision: Option B — Inline SVG Registry**

Rationale: direct port of `icons.php` SVG path map to a JS object. Zero infrastructure change, no individual files, no HTTP fetches, consistent with the PHP implementation. At ~50 icons total, bundle size impact is negligible.

**`AegisIcon.vue` internal registry shape:**

```js
const ICON_PATHS = {
  // Navigation
  'grid':           '<rect x="3" y="3" width="7" height="7"/>...',
  'dashboard':      '<rect x="3" y="3" width="7" height="7"/>...',  // alias → grid
  // Security
  'lock':           '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>...',
  'shield':         '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
  'shield-check':   '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>...',
  'key':            '<path d="M21 2l-2 2m-7.61 7.61..."/>',
  // ... all paths from icons.php verbatim
}

// Rendered as:
// <svg width="{size}" height="{size}" viewBox="0 0 24 24"
//      fill="none" stroke="currentColor" stroke-width="1.75"
//      stroke-linecap="round" stroke-linejoin="round"
//      :class="['aegis-icon', filled && 'aegis-icon-filled']"
//      v-html="ICON_PATHS[name] ?? ''">
// </svg>
```

**Canonical icon names** are identical to PHP. Do not add aliases except where already established in `icons.php` (e.g. `dashboard` → `grid`). Unknown name renders empty `<svg>` — never throws.

---

*End of AEGIS_VUE_STRUCTURE.md*
