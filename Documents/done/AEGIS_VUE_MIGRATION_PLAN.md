# Aegis — Vue 3 Frontend Migration Plan

**Repo:** `github.com/rehanurrashid/aegis.git` @ `46b7399`
**Total PHP source files:** 78 portal pages + 13 shared files + 4 modal files + 4 template files + 69 email templates
**Total Vue files to produce:** ~111 files across 6 chats

---

## Overview — 6 Chats, One Portal Per Chat

| Chat | Scope | Files | Source reference |
|------|-------|-------|-----------------|
| **Chat 6A** | Foundation + Global Assets + Shared Components | ~25 files | `_shared.css`, `_shared.js`, `_shared/icons.php`, `_shared/sidebar.php`, `_shared/header.php` |
| **Chat 6B** | Shared Templates + Modals + Auth Pages + Blade | ~18 files | `_shared/templates/`, `_shared/modals/`, `login.php`, `onboarding.php` |
| **Chat 6C** | Provider Portal (19 pages) | ~19 files | `provider-portal/*.php` |
| **Chat 6D** | CS Portal (13 pages) + SS Portal (12 pages) | ~25 files | `continuity-steward-portal/`, `support-steward-portal/` |
| **Chat 6E** | BP Portal (15 pages) + Admin Portal (8 pages) | ~23 files | `biz-portal/`, `admin-portal/` |
| **Chat 6F** | Public Profiles (4 pages) + Email Blade Templates (69) | ~73 files | `public/`, `_shared/emails/` |

---

## Chat 6A — Foundation + Global Assets + Shared Components

### What this chat builds

Everything that every other Vue file depends on. Must be done first.

### Source files to read

```bash
cat _shared.css              # 4,820 lines — full design system
cat _shared.js               # 1,128 lines — all global JS functions
cat _shared/icons.php        # 276 lines — 206 SVG icon registry
cat _shared/sidebar.php      # 903 lines — universal sidebar (all 5 portals)
cat _shared/header.php       # 1,552 lines — top bar
cat _shared/ui.php           # UI helpers
cat _shared/demo_switcher.php # Demo switcher widget
cat aegis-bg.svg             # Brand background
cat aegis-favicon.svg        # Brand favicon
cat login.php | head -50     # See how SITE_URL and base URL are set
```

### Files to produce

```
resources/
├── views/
│   └── app.blade.php              ← Single Inertia root blade
│
├── css/
│   └── app.css                    ← Imports _shared.css, app-level resets only
│
└── js/
    ├── app.js                     ← Inertia entry point, global component registration
    ├── bootstrap.js               ← Axios + Echo/Reverb setup
    │
    ├── stores/                    ← Pinia global state
    │   ├── auth.js                ← user, portal, tier, roles (from HandleInertiaRequests)
    │   ├── ui.js                  ← sidebar collapsed, mobile open, toast queue, active modal
    │   ├── incident.js            ← hasEmergency, activeIncident
    │   ├── notifications.js       ← unreadCount, bell feed, Echo listener
    │   └── pricing.js             ← tier definitions (mirrors pricing_data.php)
    │
    ├── composables/               ← Replaces _shared.js global functions
    │   ├── useModal.js            ← openModal/closeModal (replaces _shared.js openModal())
    │   ├── useToast.js            ← showToast (replaces _shared.js showToast())
    │   ├── useConfirm.js          ← confirmAction (replaces _shared.js confirmAction())
    │   ├── usePortal.js           ← portal detection, role helpers, requiresTier()
    │   ├── useDemo.js             ← ?as= demo param logic (dev only)
    │   ├── useUpgrade.js          ← openUpgradeModal, requiresPractice()
    │   ├── useActivity.js         ← severityClass(), timeAgo()
    │   └── useProfileRoute.js     ← viewPartyProfile() equivalent
    │
    ├── layouts/
    │   ├── AppLayout.vue          ← Authenticated shell (sidebar + header + slot)
    │   ├── AuthLayout.vue         ← Login/register/reset (no sidebar)
    │   └── PublicLayout.vue       ← Public profile pages (minimal chrome)
    │
    └── components/
        ├── chrome/
        │   ├── AppSidebar.vue     ← Port of _shared/sidebar.php (already partially done)
        │   ├── AppHeader.vue      ← Port of _shared/header.php (1,552 lines)
        │   └── DemoSwitcher.vue   ← Port of _shared/demo_switcher.php
        │
        ├── ui/
        │   ├── AegisIcon.vue      ← Port of aegis_icon() — inline registry of all 206 icons
        │   ├── AegisModal.vue     ← Modal overlay (.modal-overlay, .modal, .modal-header)
        │   ├── AegisToast.vue     ← Toast notification (.toast, .toast--success etc.)
        │   ├── AegisConfirm.vue   ← Confirm dialog (wraps AegisModal)
        │   ├── AegisDropzone.vue  ← File upload (.aegis-dropzone)
        │   ├── AegisStatChip.vue  ← Stat chip — ALWAYS sibling of hero, never inside
        │   ├── AegisHeroBanner.vue ← Page hero (.hero-banner, .page-hero-inner)
        │   ├── AegisBadge.vue     ← Status/role pill (.badge, .badge--gold etc.)
        │   ├── AegisToggle.vue    ← Checkbox toggle (.toggle, .toggle-input)
        │   ├── AegisCard.vue      ← Card wrapper (.card, .card-header, .card-body)
        │   ├── AegisEmptyState.vue ← Empty state (.empty-state)
        │   ├── AegisPagination.vue ← Pagination controls
        │   └── AegisUpgradeModal.vue ← Tier upgrade prompt (replaces _showUpgradeModal())
        │
        └── dev/
            └── DemoSwitcher.vue   ← Dev-only demo user switcher
```

### Key implementation notes for Chat 6A

**`_shared.css` → CSS strategy:**
- Copy `_shared.css` to `public/css/_shared.css` unchanged
- `resources/css/app.css` imports it: `@import url('/css/_shared.css');`
- Never modify `_shared.css` — it is a read-only design system
- All Vue components use the same class names from the PHP prototype

**`_shared.js` → Composable mapping:**
| PHP function | Vue equivalent |
|---|---|
| `openModal(id)` | `useModal().openModal(id)` |
| `closeModal(id)` | `useModal().closeModal(id)` |
| `showToast(msg, type)` | `useToast().showToast(msg, type)` |
| `confirmAction(msg, fn)` | `useConfirm().confirmAction(msg, fn)` |
| `viewPartyProfile(name, kind, slug)` | `useProfileRoute().viewProfile(slug, kind)` |
| `toggleSidebar()` | `useUiStore().sidebarCollapsed = !sidebarCollapsed` |
| `switchTab(groupId, key)` | Local `ref(activeTab)` per page |
| `navigateTo(url)` | `router.visit(url)` from Inertia |
| `timeAgo(dateStr)` | `useActivity().timeAgo(dateStr)` |
| `AegisTier` | `useAuthStore().tier` |

**`icons.php` → `AegisIcon.vue`:**
- All 206 icons ported as inline JS object (Option B — inline registry)
- No external SVG files needed
- Usage: `<AegisIcon name="shield" :size="16" />`
- Registered globally in `app.js`

**`sidebar.php` → `AppSidebar.vue`:**
- Already largely written as Vue component in prior project work
- Verify it matches the PHP sidebar's nav sections exactly per portal
- 4 groups per portal: Main, My Work/Practice, Continuity/Work, Communication/Account

**Design rules — enforced in EVERY component:**
- No Tailwind — use `.hero-banner`, `.btn`, `.modal-overlay` etc. from `_shared.css`
- No hex values in component styles — CSS variables only
- `<AegisIcon>` for every icon — never raw `<svg>` in templates
- Modal titles: plain text only — no icons inside `.modal-title`
- Stat chips: sibling of `.hero-banner` — never inside it

---

## Chat 6B — Shared Templates + Modals + Auth Pages + Blade

### What this chat builds

The cross-portal shared pages (used by all portals), the 4 modal components, all auth pages, and the email Blade wrapper.

### Source files to read

```bash
cat _shared/templates/overview.php    # 617 lines — Start Here page
cat _shared/templates/messages.php    # 1,687 lines — messaging thread
cat _shared/templates/activity.php    # 521 lines — activity feed
cat _shared/templates/support.php     # 754 lines — support tickets
cat _shared/modals/referral_modal.php # 748 lines — referral compose
cat _shared/modals/job_detail_modal.php # 286 lines
cat _shared/modals/proposal_modal.php  # 306 lines
cat _shared/modals/upgrade_cs_modal.php # 353 lines
cat login.php                          # 1,500+ lines — login + register
cat onboarding.php                     # large — registration wizard
# Read first email template to understand PHP $data[] pattern
cat _shared/emails/auth/03-password-reset.php
cat _shared/emails/_email_wrapper.php
```

### Files to produce

```
resources/
└── js/
    ├── components/
    │   ├── features/
    │   │   ├── ActivityFeed.vue        ← Inline activity strip (dashboard use)
    │   │   ├── MessagesThread.vue      ← Thread + composer
    │   │   ├── VaultZone.vue           ← 4-zone vault (sealed/unsealed state)
    │   │   ├── PlanStatusCard.vue      ← Continuity plan status widget
    │   │   ├── ProfileStrip.vue        ← Compact profile header
    │   │   ├── StewardCard.vue         ← CS/SS designation card
    │   │   ├── IncidentBanner.vue      ← Red alert strip when hasEmergency=true
    │   │   └── SupportWidget.vue       ← Floating feedback button (FAB)
    │   │
    │   └── modals/
    │       ├── ReferralModal.vue       ← Port of referral_modal.php (748 lines!)
    │       ├── JobDetailModal.vue      ← Port of job_detail_modal.php
    │       ├── ProposalModal.vue       ← Port of proposal_modal.php
    │       └── UpgradeCSModal.vue      ← Port of upgrade_cs_modal.php
    │
    └── pages/
        ├── auth/
        │   ├── Login.vue               ← Port of login.php login section
        │   ├── Register.vue            ← Port of login.php register section
        │   ├── ForgotPassword.vue      ← Password reset request
        │   ├── ResetPassword.vue       ← Password reset form
        │   └── MfaChallenge.vue        ← MFA verification
        │
        └── shared/                     ← Used by ALL portals via same component
            ├── Overview.vue            ← Port of _shared/templates/overview.php
            ├── Messages.vue            ← Port of _shared/templates/messages.php
            ├── Activity.vue            ← Port of _shared/templates/activity.php
            └── Support.vue             ← Port of _shared/templates/support.php
```

### Key implementation notes for Chat 6B

**Shared pages detect portal from Pinia:**
```vue
<!-- pages/shared/Overview.vue -->
<script setup>
const auth = useAuthStore()
// content varies by portal — read overview.php aegis_overview_data() for shape
defineProps({ content: Object, portal: String })
</script>
```

**`messages.php` is the biggest template (1,687 lines)** — thread list, message bubbles, compose area, attachments, read receipts. Takes the most work.

**`ReferralModal.vue` is the most complex modal (748 lines)** — has provider search, client info fields, insurance/cash options, consent checkbox.

**Email templates stay as Blade** — they do NOT become Vue components. The PHP `$data['key']` pattern becomes `{{ $key }}` in Blade. The `resources/views/emails/` folder mirrors `_shared/emails/` exactly.

---

## Chat 6C — Provider Portal (19 pages)

### What this chat builds

All 19 Provider portal pages. This is the largest portal and the canonical reference — every pattern established here is mirrored in other portals.

### Source files to read

```bash
# Read EVERY provider page fully (not just head — these are big)
for f in provider-portal/*.php; do echo "=== $f ===" && cat "$f"; done
# Also re-read shared components context
cat _shared/sidebar.php | grep -A 80 "practitioner"
```

### Files to produce

```
resources/js/pages/provider/
├── Dashboard.vue           ← 3,083 lines PHP → biggest page
├── EditProfile.vue         ← 2,874 lines PHP → credential taxonomy, specialties
├── ContinuityPlan.vue      ← 1,822 lines PHP → plan wizard
├── ContinuityStewards.vue  ← 2,179 lines PHP → designation + auth matrix
├── SupportStewards.vue     ← 2,375 lines PHP → SS designation
├── Network.vue             ← 7,724 lines PHP → LARGEST FILE — 3-tab network
├── Services.vue            ← 1,528 lines PHP → IBS mode
├── JobPostings.vue         ← 2,127 lines PHP → Support Requests
├── Referrals.vue           ← 1,133 lines PHP
├── Vault.vue               ← 2,713 lines PHP → 4-zone vault
├── ImportantDocuments.vue  ← 2,233 lines PHP
├── News.vue                ← 1,574 lines PHP
├── Events.vue              ← 1,236 lines PHP
├── Finances.vue            ← 2,073 lines PHP
├── Settings.vue            ← 2,858 lines PHP → MFA, notifications, sessions
│
└── (shared stubs — 3-liner includes)
    ├── Overview.vue        ← uses pages/shared/Overview.vue
    ├── Messages.vue        ← uses pages/shared/Messages.vue
    ├── Activity.vue        ← uses pages/shared/Activity.vue
    └── Support.vue         ← uses pages/shared/Support.vue
```

### Key implementation notes for Chat 6C

**`Network.vue` is the hardest page (7,724 PHP lines)** — has 3 main tabs (Integrative Care Network, Business Partners, Referrals), shadow network, recommended providers, connection requests, referral history, 10+ modals. **Dedicate at least half the chat to this.**

**`Dashboard.vue` has the most modals** (activateSuccessionModal, annualReviewModal, addCEUModal, renewInsuranceModal, editLicenseModal, etc.) — identify all of them from the PHP file before writing.

**`EditProfile.vue` credential taxonomy** — the specialties, services, and approaches taxonomies are large JSON-driven accordions. Port the expand/collapse pattern exactly.

**Every page follows this exact structure:**
```vue
<template>
  <AppLayout>
    <!-- 1. Hero Banner ALWAYS first -->
    <AegisHeroBanner eyebrow="..." title="..." subtitle="...">
      <template #actions>...</template>
    </AegisHeroBanner>

    <!-- 2. Stat chips — ALWAYS sibling of hero, never inside -->
    <div class="stat-chips-row">
      <AegisStatChip icon="..." :value="..." label="..." />
    </div>

    <!-- 3. Page content (tabs, cards, tables) -->

    <!-- 4. ALL modals at bottom of template -->
    <AegisModal v-model="modals.create" title="Plain text only">
      ...
    </AegisModal>
  </AppLayout>
</template>
```

**Tab pattern (replaces `switchTab()` from _shared.js):**
```vue
<script setup>
const activeTab = ref('tab1')
</script>
<template>
  <div class="tab-strip">
    <button v-for="tab in tabs" class="tab-btn"
            :class="{ active: activeTab === tab.key }"
            @click="activeTab = tab.key">{{ tab.label }}</button>
  </div>
  <div v-show="activeTab === 'tab1'">...</div>
</template>
```

**Write actions pattern:**
```vue
<script setup>
const form = useForm({ field: '' })
function submit() {
  form.post(route('provider.vault.upload'), {
    onSuccess: () => { closeModal('uploadModal'); toast.success('Uploaded.') }
  })
}
</script>
```

---

## Chat 6D — CS Portal (13 pages) + SS Portal (12 pages)

### What this chat builds

Two portals in one chat — both are smaller than Provider and follow identical patterns.

### Source files to read

```bash
for f in continuity-steward-portal/*.php; do echo "=== $f ===" && cat "$f"; done
for f in support-steward-portal/*.php; do echo "=== $f ===" && cat "$f"; done
```

### CS Files to produce

```
resources/js/pages/continuity-steward/
├── Dashboard.vue              ← 1,409 lines PHP
├── EditProfile.vue            ← 1,511 lines PHP
├── Providers.vue              ← 1,499 lines PHP — practitioner list + plan access
├── MyTasks.vue                ← 896 lines PHP — standby + incident tasks
├── ContinuityManagement.vue   ← 1,546 lines PHP — verify/activate/close incident
├── ImportantDocuments.vue     ← 1,139 lines PHP
├── Vault.vue                  ← 1,415 lines PHP — sealed/unsealed gate
├── Finances.vue               ← 1,518 lines PHP — CS invoices + payouts
├── Settings.vue               ← 1,641 lines PHP
│
└── (shared stubs)
    ├── Overview.vue
    ├── Messages.vue
    ├── Activity.vue
    └── Support.vue
```

### SS Files to produce

```
resources/js/pages/support-steward/
├── Dashboard.vue              ← 1,241 lines PHP
├── EditProfile.vue            ← 1,099 lines PHP
├── Providers.vue              ← 861 lines PHP
├── ContinuityStewards.vue     ← 956 lines PHP — CS contacts (read-only)
├── MyTasks.vue                ← 835 lines PHP
├── CriticalIncidentLog.vue    ← 1,563 lines PHP — incident reporting flow
├── ImportantDocuments.vue     ← 689 lines PHP
├── Settings.vue               ← 1,173 lines PHP
│
└── (shared stubs)
    ├── Overview.vue
    ├── Messages.vue
    ├── Activity.vue
    └── Support.vue
```

### Key implementation notes for Chat 6D

**`ContinuityManagement.vue` is CS's hardest page (1,546 lines)** — has the verify → activate → task-execution → close incident flow. The vault unsealing state change is critical: when incident becomes active, the Vault tab unlocks.

**`CriticalIncidentLog.vue` is SS's hardest page (1,563 lines)** — the incident report form with documentation upload, incident type selection, and the "What happens next" messaging.

**Vault component reuse:** Both CS `Vault.vue` and the underlying `VaultZone.vue` component (from Chat 6A) must respect the `EnsureIncidentActive` gate. If no active incident → show sealed state. If active and assigned steward → show contents via `VaultZone.vue`.

**CS sidebar has emergency badge** — `hasEmergency` from Pinia drives the `sidebar-emergency-badge` on the sidebar brand area. `AppSidebar.vue` already handles this via the `has-emergency` prop.

---

## Chat 6E — BP Portal (15 pages) + Admin Portal (8 pages)

### What this chat builds

Business Partner portal (largest non-Provider portal) and Admin portal.

### Source files to read

```bash
for f in biz-portal/*.php; do echo "=== $f ===" && cat "$f"; done
for f in admin-portal/*.php; do echo "=== $f ===" && cat "$f"; done
```

### BP Files to produce

```
resources/js/pages/business-partner/
├── Dashboard.vue         ← 1,450 lines PHP
├── EditProfile.vue       ← 1,089 lines PHP
├── FindJobs.vue          ← 701 lines PHP — job board + save + propose
├── Proposals.vue         ← 731 lines PHP
├── Contracts.vue         ← 876 lines PHP
├── Milestones.vue        ← 553 lines PHP
├── Invoices.vue          ← 1,236 lines PHP — create + line items + send
├── Finances.vue          ← 571 lines PHP
├── PaymentSetup.vue      ← 1,122 lines PHP — Stripe Connect onboarding
├── Team.vue              ← 543 lines PHP — agency-only
├── Settings.vue          ← 1,245 lines PHP
│
└── (shared stubs)
    ├── Overview.vue
    ├── Messages.vue
    ├── Activity.vue
    └── Support.vue
```

### Admin Files to produce

```
resources/js/pages/admin/
├── Dashboard.vue          ← 266 lines PHP — platform stats
├── Packages.vue           ← 200 lines PHP — tier config
├── Users.vue              ← 326 lines PHP — search + detail drawer
├── Roles.vue              ← 195 lines PHP — permission matrix
├── Payments.vue           ← 232 lines PHP — ledger + refunds + payouts
├── Complaints.vue         ← 256 lines PHP — master-detail
├── HelpArticles.vue       ← 170 lines PHP — CRUD
└── Incidents.vue          ← 145 lines PHP — oversight (read-only)
```

### Key implementation notes for Chat 6E

**BP `Team.vue` is conditionally shown** — only when `auth.user.bp_type === 'agency'`. The `AppSidebar.vue` already handles this via `bpType` prop.

**BP `PaymentSetup.vue`** — Stripe Connect status display. Gate write buttons with `defined('STRIPE_SECRET_KEY')` equivalent: check `$page.props.stripeConnected` prop.

**Admin portal uses same `AppLayout.vue`** — same sidebar component but with admin nav config. The `EnsureAdminRole` middleware protects all admin routes.

**Admin `Users.vue` master-detail pattern** — left list + right detail drawer (same page). When `selectedUser` prop is set, show detail panel. Matches `complaints.php` pattern.

**Admin pages are smaller (145–326 lines each)** — admin portal PHP was built after the spec was complete, so it's already cleaner. Less to port.

---

## Chat 6F — Public Profiles + Email Blade Templates

### What this chat builds

The 4 public profile pages (no auth) and conversion of all 69 PHP email templates to Laravel Blade.

### Source files to read

```bash
for f in public/*.php; do echo "=== $f ===" && cat "$f"; done
cat _shared/public_chrome.php
cat _shared/public_profile.php
# Sample a few email templates for pattern
cat _shared/emails/auth/03-password-reset.php
cat _shared/emails/incident/26-incident-reported.php
cat _shared/emails/_email_wrapper.php
cat _shared/emails/_email_head.php
cat _shared/emails/_email_foot.php
```

### Files to produce

```
resources/js/pages/public/
├── ProviderProfile.vue            ← Port of public/provider.php
├── ContinuityStewardProfile.vue   ← Port of public/continuity_steward.php
├── SupportStewardProfile.vue      ← Port of public/support_steward.php
└── BusinessProfile.vue            ← Port of public/business.php

resources/views/emails/            ← Laravel Blade equivalents
├── _partials/
│   ├── head.blade.php             ← Port of _email_head.php
│   ├── foot.blade.php             ← Port of _email_foot.php
│   └── wrapper.blade.php          ← Port of _email_wrapper.php
│
├── auth/                          ← 9 templates (01–09)
├── plan/                          ← 9 templates (10–16, incl. variants)
├── steward/                       ← 9 templates (17–25)
├── incident/                      ← 6 templates (26–31)
├── bp/                            ← 10 templates (32–41)
├── network/                       ← 4 templates (42–45)
├── support/                       ← 4 templates (46–49)
├── admin/                         ← 6 templates (50–55)
├── digest/                        ← 2 templates (56–57)
└── gaps/                          ← 12 templates (58–69)
```

### Key implementation notes for Chat 6F

**PHP `$data['key']` → Blade `{{ $key }}`:**
```php
// PHP email template:
<?= htmlspecialchars($data['practitioner_name'] ?? '') ?>
// Blade equivalent:
{{ $practitioner_name ?? '' }}
```

**Table-based HTML stays exactly the same** — the email HTML structure is already inline-CSS table-based. No changes needed to the HTML itself — only the PHP interpolation syntax changes to Blade.

**`_email_wrapper.php` → `wrapper.blade.php`** — becomes a Blade component or `@include`:
```blade
@include('emails._partials.head', ['email_title' => $email_title ?? 'Aegis'])
<!-- body content -->
@include('emails._partials.foot', ['unsubscribe_token' => $unsubscribe_token ?? ''])
```

**Create `app/Mail/AegisMailable.php`** — generic Mailable that accepts template + data:
```php
class AegisMailable extends Mailable
{
    public function __construct(
        private string $template,
        private array  $data
    ) {}

    public function build(): self
    {
        return $this->view($this->template)
                    ->with($this->data)
                    ->subject($this->data['subject'] ?? 'Aegis Notification');
    }
}
```

**Public profiles use `PublicLayout.vue`** — no auth required, no sidebar. Just the brand header and the profile card.

---

## Global Rules — Apply in Every Chat

### Design rules (non-negotiable)
1. **No Tailwind** — every class must exist in `_shared.css`
2. **No hex values in template `style=` attrs** — CSS variables only: `var(--gold-dark)`
3. **`<AegisIcon name="x" :size="16" />`** — never raw `<svg>` in templates
4. **Modal titles: plain text** — no icons inside `.modal-title`
5. **Stat chips: sibling of `.hero-banner`** — never inside it
6. **`_shared.css` is never modified** — read-only design system

### Vue patterns (enforced everywhere)
7. **Composition API + `<script setup>`** — no Options API
8. **`useForm()` from Inertia** — for all write forms
9. **`router.visit(url)`** — for programmatic navigation
10. **Pinia stores** — for global state (auth, ui, incident, notifications)
11. **Constructor inject all composables** — `const modal = useModal()`

### Inertia patterns
12. **GET actions** → `Inertia::render('Portal/Page', [props])`
13. **Write actions** → `back()->with('success', '...')` — never JSON
14. **Page names are PascalCase** — `'Provider/Dashboard'` not `'provider/dashboard'`

### Copy rules
15. **Copy from PHP templates exactly** — no paraphrasing of labels, button text, placeholders
16. **MA'AT tone preserved** — calm, grounded, professional
17. **Terminology** — Practitioner (not Provider), Continuity Steward (not Executor), Continuity Plan (not Professional Will)

---

## vite.config.js + package.json Required Packages

```js
// vite.config.js
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import path from 'path'

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
        alias: { '@': path.resolve(__dirname, 'resources/js') },
    },
})
```

```json
// package.json additions
{
  "dependencies": {
    "@inertiajs/vue3": "^1.0",
    "pinia": "^2.0",
    "vue": "^3.4",
    "laravel-echo": "^1.15",
    "pusher-js": "^8.0",
    "axios": "^1.6"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0",
    "laravel-vite-plugin": "^1.0",
    "vite": "^5.0"
  }
}
```

---

## File Count Summary

| Chat | New files | Hours estimate |
|------|-----------|----------------|
| 6A — Foundation + Global | ~25 | 6–8 hrs |
| 6B — Shared Templates + Auth | ~18 | 4–6 hrs |
| 6C — Provider Portal | ~19 | 8–10 hrs |
| 6D — CS + SS Portals | ~25 | 6–8 hrs |
| 6E — BP + Admin Portals | ~23 | 5–7 hrs |
| 6F — Public + Email Blade | ~73 | 4–6 hrs |
| **Total** | **~183 files** | **~33–45 hrs** |

---

## Before Starting Any Chat

Each chat must run:

```bash
cd /home/claude
rm -rf aegis
git clone https://github.com/rehanurrashid/aegis.git
cd aegis
git log -1 --oneline
```

And read via `project_knowledge_search`:
- `Aegis_Desing_Prompt_Short.md` — strict design rules (wins on conflicts)
- `Aegis_Desing_Prompt.md` — full design reference
- `Aegis_Tone_Voice_Prompt.md` — MA'AT voice
- `CENTRALIZED-SYSTEM.md` — component inventory

Each chat also reads the attached files:
- `AEGIS_VUE_STRUCTURE.md` — component props, emits, slots
- `AEGIS_LARAVEL_STRUCTURE.md` — Inertia props per page

---

## Recommended Chat Start Sequence

Start **Chat 6A** after all backend steps (3, 4, 5) are confirmed working:

```bash
php artisan route:list | wc -l  # should be 120+
php artisan tinker --execute="echo App\Models\User::count();"  # should return seeded users
```

Then proceed chat by chat. Each chat's output is a ZIP dropped into `resources/`.

After Chat 6A, run:
```bash
npm run dev
# Visit http://localhost:8000
# Should serve the app.blade.php with Vite
```

After Chat 6B:
```bash
# Visit http://localhost:8000/login
# Should render Login.vue with _shared.css styles
```
