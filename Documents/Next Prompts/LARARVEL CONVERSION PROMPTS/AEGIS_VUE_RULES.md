# AEGIS_VUE_RULES.md
 
**The single source of truth for Vue components in the Aegis Laravel migration.** This file replaces every prior design and conversion reference. Everything Claude needs to convert a PHP page to a Vue component lives here.
 
If a rule in this file conflicts with anything else in your context — this file wins.
 
---
 
## SECTION 1 — THE GOLDEN RULES
 
Break any of these and the component is wrong:
 
1. **NEVER use raw `<svg>`** — always `<AegisIcon name="x" :size="16" />`. Exceptions: Google/Microsoft brand marks with explicit comment.
2. **NEVER use `title=` for tooltips** — always `data-tooltip="..."` (branded CSS tooltip from `_shared.css`).
3. **NEVER hardcode hex colors** — always `var(--token-name)`.
4. **NEVER use Tailwind classes** — only `_shared.css` classes.
5. **NEVER put stat chips inside `AegisHeroBanner`** — they are siblings.
6. **NEVER put icons inside modal titles** — plain text only.
7. **NEVER use `axios` / `fetch` / `window.location`** — use `useForm()` and `router.visit()`.
8. **NEVER use `alert()` / `confirm()`** — use `toast.*()` and `confirmAction()`.
9. **NEVER hardcode URLs** — always `route('name.action')`.
10. **NEVER skip PHP sections, modals, buttons, or conditionals** — 100% parity required.
11. **NEVER store money as float** — integer cents only.
12. **NEVER write business logic in components** — controllers call services, components call composables.
13. **NEVER use `size="md"` or `size="lg"` on multi-step modals** — `size="xl"` or `size="fullscreen"` only (see Section 13).
14. **NEVER submit a form without client-side validation** — Vuelidate runs first, server validation is the boundary (see Section 14).
15. **NEVER place an `<AegisIcon>` next to text inside a plain `<span>` or `<div>`** — the parent must be `inline-flex` (badges, pills) or `flex` (alerts, rows) with `align-items: center` and a `gap` (see Section 11).
16. **NEVER stack short form fields vertically in a modal** — selects, dates, numbers, and short text fields go side by side in `.form-row`; only long-value fields (textarea, description, notes) get full width (see Section 4 Forms).
17. **NEVER use a raw `<input type="file">` in any template** — always `<AegisDropzone>` with `@files="form.x = $event[0]"`; always add `forceFormData: true` to the submit call (see Section 15).
18. **NEVER use hardcoded / static data in a template** — every list, table, card, detail modal binds to controller props. If the data doesn't exist in the DB, build the migration + seeder, don't fake it (see Section 20).
19. **NEVER write a form without verifying the FormRequest, route verb, and migration columns first** — field names match FormRequest, HTTP verb matches route, column names match migration (see Section 21).
20. **NEVER use `ui.openModal()`, `ui.closeModal()`, `modal-id=`, or `@file-selected`** — these patterns are banned. Modals use `v-model="modals.xxx"` on `AegisModal`; AegisDropzone emits `@files` (an array), not `@file-selected`.
21. **NEVER complete a write action without triggering the correct email** — every save, submit, sign, activate, assign, or status change has a matching template in `resources/views/emails/`. Find it, verify the Service dispatches the event, verify the Listener sends via `SendEmailJob`. Silent saves with no email are incomplete (see Section 25).
22. **NEVER complete a write action without logging to `ActivityService::log()`** — the actor gets `entry_type: 'log'`; every other affected party gets `entry_type: 'notification'`. More than one other recipient → dispatch `ActivityFanoutJob` instead of looping. Missing logs and notifications are incomplete deliverables (see Section 26).
23. **NEVER declare `useForm()` inside a function, handler, `if` block, or callback** — all `useForm()` instances live at component setup top-level. A form built inside `saveConfig()` is a bug: it re-instantiates on every call and breaks reactivity. For many-field saves use one form + `.transform(() => ({...}))`.
24. **NEVER `await confirmAction()` or check its return value** — it is callback-based: `confirmAction(options, callback)`. The gated logic runs inside the callback. No `const ok = await ...`, no `if (!ok) return`.
25. **NEVER hardcode user-specific defaults in `reactive({})`** — initialize from the controller prop (`const nc = props.networkConfig || {}`). Hardcoded selections silently override saved DB state on every page load.
---
 
## SECTION 2 — IMPORT CHEATSHEET
 
```js
// Stores
import { useAuthStore }         from '@/stores/auth'
import { useUiStore }           from '@/stores/ui'
import { useIncidentStore }     from '@/stores/incident'
import { useNotificationStore } from '@/stores/notifications'
import { usePricingStore }      from '@/stores/pricing'
 
// Composables
import { useModal }        from '@/composables/useModal'
import { useToast }        from '@/composables/useToast'
import { useConfirm }      from '@/composables/useConfirm'
import { useActivity }     from '@/composables/useActivity'
import { useUpgrade }      from '@/composables/useUpgrade'
import { usePortal }       from '@/composables/usePortal'
import { useDemo }         from '@/composables/useDemo'
import { useProfileRoute } from '@/composables/useProfileRoute'
 
// Layouts (only ONE per page)
import AppLayout    from '@/layouts/AppLayout.vue'    // authenticated portal pages
import AuthLayout   from '@/layouts/AuthLayout.vue'   // login/register/reset
import PublicLayout from '@/layouts/PublicLayout.vue' // public profile pages
 
// Inertia
import { useForm, router, usePage, Link } from '@inertiajs/vue3'
 
// Vue
import { ref, reactive, computed, watch, onMounted } from 'vue'

// Client-side validation (every form)
import { useVuelidate } from '@vuelidate/core'
import {
    required, email, minLength, maxLength, sameAs,
    numeric, integer, minValue, maxValue, url,
    requiredIf, helpers,
} from '@vuelidate/validators'
```
 
**Globally registered (NO import in page components):**
`AegisIcon` · `AegisModal` · `AegisToast` · `AegisConfirm` · `AegisBadge` · `AegisHeroBanner` · `AegisStatChip` · `AegisCard` · `AegisEmptyState` · `IncidentBanner`
 
---
 
## SECTION 3 — PAGE COMPONENT TEMPLATE
 
Every portal page follows this exact structure. No deviations.
 
```vue
<template>
  <AppLayout>
 
    <!-- 1. Hero banner — ALWAYS first, ALWAYS quiet variant -->
    <AegisHeroBanner
      eyebrow="Section Name"
      title="Page Title"
      subtitle="Optional supporting sentence."
      quiet
    >
      <template #actions>
        <a :href="route('activity.index', { module: 'plan' })"
           class="btn-hero-ghost is-on-light">
          <AegisIcon name="activity" :size="14" />
          Activity
        </a>
        <button class="btn btn-primary" @click="modals.create = true">
          <AegisIcon name="plus" :size="14" />
          Add Item
        </button>
      </template>
    </AegisHeroBanner>
 
    <!-- 2. Stat chips — OUTSIDE hero, ALWAYS sibling -->
    <div class="stat-chips-row">
      <AegisStatChip icon="shield" :value="stats.total" label="Total" />
      <AegisStatChip icon="check-circle" :value="stats.active" label="Active" />
    </div>
 
    <!-- 3. Page sections in EXACT order from PHP -->
    <div class="page-content">
      <!-- cards, tables, tabs, lists -->
    </div>
 
    <!-- 4. ALL modals at the very bottom -->
    <AegisModal v-model="modals.create" title="Create Item" size="md">
      <!-- modal body -->
      <template #footer>
        <button class="btn btn-outline" @click="modals.create = false">Cancel</button>
        <button class="btn btn-primary"
                @click="submitCreate"
                :disabled="createForm.processing">
          {{ createForm.processing ? 'Saving...' : 'Save' }}
        </button>
      </template>
    </AegisModal>
 
  </AppLayout>
</template>
 
<script setup>
import { ref, reactive, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { useAuthStore }   from '@/stores/auth'
import { useToast }       from '@/composables/useToast'
import { useConfirm }     from '@/composables/useConfirm'
 
// Props from Inertia controller — exhaustive
const props = defineProps({
    items: { type: Array,  default: () => [] },
    stats: { type: Object, default: () => ({}) },
})
 
const auth    = useAuthStore()
const toast   = useToast()
const { confirmAction } = useConfirm()
 
// Modal state — one key per modal in the page
const modals = reactive({
    create: false,
    edit:   false,
    delete: false,
})
 
// Forms — one useForm() per write action
const createForm = useForm({
    name:         '',
    amount_cents: 0,
})
 
function submitCreate() {
    createForm.post(route('provider.items.store'), {
        onSuccess: () => { modals.create = false; toast.success('Created.') },
        onError:   () => toast.error('Please check the form.'),
    })
}
 
// confirmAction is CALLBACK-based — never await it, never check a return value
function remove(item) {
    confirmAction(
        { title: 'Confirm Removal', message: `Remove "${item.name}"?`, confirmLabel: 'Remove', destructive: true },
        () => {
            router.delete(route('provider.items.destroy', item.id), {
                onSuccess: () => toast.success('Removed.'),
            })
        }
    )
}
</script>
```
 
---
 
## SECTION 4 — DESIGN RULES
 
Every Vue component follows these. There is no other design source — these are complete.
 
### Hero
- Always `<AegisHeroBanner ... quiet>` — never custom hero divs
- Stat chips placed as sibling AFTER hero, in `.stat-chips-row`
- Activity link in hero `#actions` slot: `<a class="btn-hero-ghost is-on-light">`
### Tabs
- Single-level → `.tabs-segmented` + `.tab-pill` (12px icons)
- Two-level → `.tabs-twotier > .tabs-primary` (parent, 15px icons) + `.tabs-segmented.net-sub-tabs` (children)
- Tab state via `ref('key')`, never via JS class manipulation
### Modals
- `<AegisModal v-model="modals.xxx">` — never raw `.modal-overlay` divs
- Title is plain text only — no icons
- Multi-step modals use `.modal-steps` row between header and body
- Modal close button: built into `AegisModal` — never custom close button
### Forms
- Field classes only: `.form-input`, `.form-select`, `.form-textarea`, `.form-group`, `.form-row`, `.form-row.is-3col`, `.form-check`, `.form-check-label`
- Errors: `<div v-if="form.errors.field" class="form-error">{{ form.errors.field }}</div>`
- Hints: `<div class="form-hint">...</div>`
- Toggles: `<AegisToggle v-model="form.enabled" label="..." />`
- **Modal form layout — short fields go side by side.** Fields with short values (select dropdowns, date pickers, number inputs, short text like "Title", status toggles) must be placed in `.form-row` (2-col) or `.form-row.is-3col` (3-col) to keep more content visible above the fold. Only long-value fields (textarea, bio, description, notes) get a full-width `.form-group`. Never stack short fields vertically when they fit side by side.

```vue
<!-- ✅ Short fields side by side -->
<div class="form-row">
    <div class="form-group">
        <label class="form-label">Zone</label>
        <select v-model="form.zone" class="form-select">...</select>
    </div>
    <div class="form-group">
        <label class="form-label">Access Level</label>
        <select v-model="form.access_level" class="form-select">...</select>
    </div>
</div>
<div class="form-row">
    <div class="form-group">
        <label class="form-label">Start Date</label>
        <input v-model="form.starts_at" type="date" class="form-input" />
    </div>
    <div class="form-group">
        <label class="form-label">End Date</label>
        <input v-model="form.ends_at" type="date" class="form-input" />
    </div>
</div>

<!-- ✅ Long-value fields get full width -->
<div class="form-group">
    <label class="form-label">Description</label>
    <textarea v-model="form.description" class="form-textarea" rows="4" />
</div>

<!-- ❌ WRONG — short fields stacked vertically wastes vertical space -->
<div class="form-group">
    <label class="form-label">Zone</label>
    <select v-model="form.zone" class="form-select">...</select>
</div>
<div class="form-group">
    <label class="form-label">Access Level</label>
    <select v-model="form.access_level" class="form-select">...</select>
</div>
```

**Side-by-side decision table:**
| Field type | Layout |
|---|---|
| `<select>` dropdown | `.form-row` (pair with another short field) |
| `<input type="date">` | `.form-row` (pair with another date or short field) |
| `<input type="number">` | `.form-row` |
| Short text (title, name, code) max ~60 chars | `.form-row` |
| Status/toggle | `.form-row` |
| `<textarea>` / description / bio / notes | `.form-group` full width |
| Long text (narrative, reason, body) | `.form-group` full width |
| File upload (`AegisDropzone`) | `.form-group` full width |
### Buttons
- Canonical set only: `btn-primary` · `btn-outline` · `btn-danger` · `btn-ghost` · `btn-hero-solid is-on-light` · `btn-hero-ghost is-on-light`
- Size variants: `btn-sm`
- Icon-only buttons: `.btn-icon` (or `.btn-icon-sm` / `.btn-icon-danger` / `.btn-icon-primary`) with `data-tooltip="..."`
- `btn-gold` is DEPRECATED → use `btn-primary`
- `btn-ghost` is invisible on light backgrounds — use `btn-outline` for visible secondary actions
- `btn-icon` in tables/card rows: icon size 14 (not 13)
### Icons
- `<AegisIcon name="x" :size="N" />` — never raw `<svg>`
- Deprecated names (DO NOT USE): `square-pen` → `pencil`, `x-circle` → `x`, `gear` → `settings`, `time` → `clock`, `magnifier` → `search`, `person` → `user`, `event` → `calendar`
- Aliases that resolve: `vault` → `lock`, `incident` → `alert-triangle`, `task` → `check-circle`, `document` → `file-text`, `warning` → `alert-circle`, `critical` → `alert-triangle`, `payment` → `dollar`, `account` → `user`, `system` → `settings`, `compliance` → `shield-check`, `attestation` → `signature`
### Tooltips
- `data-tooltip="..."` ONLY — never `title="..."`
- The CSS tooltip pseudo-element from `_shared.css` renders automatically
### Colors
- `var(--token)` only — no bare hex, `rgb()`, `rgba()`, `hsl()`, or named colors
- Exceptions: `#fff` / `rgba(255,255,255,*)` on dark panels, `rgba(0,0,0,0.4)` on modal backdrops
- `--gold-dark` for action states — NEVER `--gold` on hover/active/focus/checked/primary CTAs
### Avatars
- Modifier classes only: `.avatar-gold` / `.avatar-dark` / `.avatar-red` + size `.avatar-{xs|sm|md|lg|xl|2xl}`
- NEVER `style="background: ..."` inline
### Font Sizes
- Allowed scale: 10/11/12/13/14/15/18/22/24/26/28/36px
- No `.5px` sizes
### Shadows
- `--shadow-sm` at rest, `--shadow` on hover
- Every `.alert` variant gets `box-shadow: var(--shadow-sm)`
### Border Radius
- Tokens only — no off-scale 4/6/10/14/20px
- `50%` → `var(--radius-full)`
### Checkboxes
- `-webkit-appearance: none` — NOT `appearance: none`
---
 
## SECTION 5 — STORE API REFERENCE
 
### `useAuthStore()`
| Property | Type | Description |
|----------|------|-------------|
| `user` | Object\|null | Current user from HandleInertiaRequests |
| `portal` | String\|null | `'provider'\|'continuity_steward'\|'ss'\|'bp'\|'admin'` |
| `tier` | String\|null | `'access'\|'practice'` |
| `roles` | Array | All roles assigned to user |
| `isPractitioner` | Boolean | computed from portal |
| `isContinuitySteward` | Boolean | |
| `isSupportSteward` | Boolean | |
| `isBusinessPartner` | Boolean | |
| `isAdmin` | Boolean | |
| `isAccessTier` | Boolean | tier === 'access' |
| `isPracticeTier` | Boolean | tier === 'practice' |
| `hasServicesMode` | Boolean | IBS mode active |
 
### `useUiStore()`
| Member | Type | Description |
|--------|------|-------------|
| `sidebarCollapsed` | Ref<bool> | Desktop collapse state |
| `mobileOpen` | Ref<bool> | Mobile sidebar open |
| `activeModal` | Ref<string\|null> | Single active modal ID |
| `openModal(id)` | fn | Open modal by ID |
| `closeModal(id?)` | fn | Close modal (or any if no ID) |
| `closeAllModals()` | fn | Close all |
| `toastQueue` | Ref<Array> | Active toasts |
| `showToast(msg, type, dur?)` | fn | Push toast |
| `confirm` | Ref<Object> | Confirm dialog state |
| `requestConfirm(opts)` | fn | Returns Promise<bool> |
| `resolveConfirm(value)` | fn | Resolve pending confirm |
 
### `useIncidentStore()`
| Member | Type | Description |
|--------|------|-------------|
| `hasEmergency` | ComputedRef<bool> | From `$page.props.hasEmergency` |
| `activeIncident` | Ref<Object\|null> | Currently active incident |
| `setActiveIncident(inc)` | fn | Set after activation event |
| `clearIncident()` | fn | Clear after close |
 
### `useNotificationStore()`
| Member | Type | Description |
|--------|------|-------------|
| `unreadCount` | ComputedRef<int> | From `$page.props.unreadCount` |
| `events` | Ref<Array> | Live event feed via Echo |
| `listenForIncident(userId)` | fn | Subscribe to incident channel |
| `listenForNotifications(userId)` | fn | Subscribe to user channel |
 
### `usePricingStore()`
| Member | Description |
|--------|-------------|
| `tiers` | Object — access + practice tier definitions |
| `getTier(key)` | fn — returns tier object |
 
---
 
## SECTION 6 — COMPOSABLE API REFERENCE
 
### `useModal()`
```js
const { openModal, closeModal, isOpen, activeModal } = useModal()
 
openModal('createItem')           // open by ID
closeModal('createItem')          // close specific
closeModal()                      // close any active
isOpen('createItem')              // ComputedRef<bool>
```
 
### `useToast()`
```js
const toast = useToast()
 
toast.success('Saved.')           // green
toast.error('Failed.')            // red
toast.info('Heads up.')           // blue
toast.warning('Careful.')         // orange
toast.showToast(msg, type, ms)    // raw
```
 
### `useConfirm()`
**⚠️ CALLBACK-BASED, NOT PROMISE-BASED.** Never `await confirmAction()`, never check a return value. The gated logic runs inside the second-argument callback.
```js
const { confirmAction } = useConfirm()

function remove(item) {
    confirmAction(
        {
            title:        'Confirm',
            message:      'Are you sure?',
            confirmLabel: 'Remove',
            destructive:  true,
        },
        () => {
            // runs ONLY if the user confirms
            deleteForm.delete(route('...'))
        }
    )
}
```
Signature: `confirmAction(options, callback)`. There is no `const ok = await ...`, no `if (!ok) return`. Everything conditional on confirmation lives in the callback body.
 
### `useActivity()`
```js
const activity = useActivity()
 
activity.timeAgo(dateStr)         // '2h ago', '3d ago'
activity.formatDate(dateStr)      // 'Jan 15, 2026'
activity.formatMoney(cents)       // '$49.00' from integer cents
activity.severityClass('warning') // 'activity-item--warning'
activity.moduleIcon('vault')      // 'lock'
```
 
### `useUpgrade()`
```js
const upgrade = useUpgrade()
 
upgrade.openUpgradeModal()
upgrade.requiresPractice(() => doThing())
upgrade.requiresServicesMode(() => doThing())
```
 
### `usePortal()`
```js
const portal = usePortal()
 
portal.isPractitioner / isCS / isSS / isBP / isAdmin
portal.isAccessTier / isPracticeTier
portal.requiresPractice(fn)
portal.requiresServicesMode(fn)
```
 
---
 
## SECTION 7 — INERTIA WIRING
 
### Reading props from controller
```js
const props = defineProps({
    // EVERY prop the controller passes — never partial
    plan:           { type: Object, default: null },
    stewards:       { type: Array,  default: () => [] },
    activeIncident: { type: Object, default: null },
    stats:          { type: Object, default: () => ({}) },
})
```
 
### Reading shared props (HandleInertiaRequests::share())
Available on every page — prefer the Pinia stores over `$page.props.*`:
 
| `$page.props.X` | Store equivalent |
|------------------|-----------------|
| `auth.user` | `useAuthStore().user` |
| `auth.portal` | `useAuthStore().portal` |
| `auth.tier` | `useAuthStore().tier` |
| `hasEmergency` | `useIncidentStore().hasEmergency` |
| `unreadCount` | `useNotificationStore().unreadCount` |
| `flash.success` | handled by AppLayout toast host |
| `flash.error` | handled by AppLayout toast host |
 
### Write actions — useForm pattern
```js
const form = useForm({
    title:        '',
    description: '',
    amount_cents: 0,         // integer cents — NEVER float
    incident_type: null,
})
 
function submit() {
    form.post(route('provider.items.store'), {
        onSuccess: () => { modals.create = false; toast.success('Saved.') },
        onError:   () => toast.error('Please check the form.'),
    })
}
 
// HTTP methods:
form.post(route('...'))
form.put(route('...', id))
form.patch(route('...', id))
form.delete(route('...', id))
 
// Options:
form.post(route('...'), {
    preserveScroll: true,
    preserveState:  true,
    onStart:    () => {},
    onProgress: (e) => {},
    onSuccess:  (page) => {},
    onError:    (errors) => {},
    onFinish:   () => form.reset(),
})
```
 
### Navigation
```js
import { router } from '@inertiajs/vue3'
 
router.visit(route('provider.dashboard'))
router.visit(route('provider.plan.index'), { preserveScroll: true })
router.replace(route('login'))
 
// NEVER:
window.location.href = '/dashboard'   // ❌
location.assign('/dashboard')         // ❌
```
 
### Link component for in-page nav
```vue
<Link :href="route('provider.dashboard')" class="btn btn-outline">Dashboard</Link>
```
 
---
 
## SECTION 8 — CENTRALIZATION RULES
 
Every Vue component obeys these centralization rules.
 
### Activity events
- Created **only** by the backend `ActivityService::log()`
- Components consume activity events via Inertia props or Echo events
- Never create activity events from the frontend
- Severity → CSS class via `useActivity().severityClass(severity)`
- Module → icon via `useActivity().moduleIcon(module)`
### Notifications (the bell)
- `useNotificationStore().unreadCount` is the source of truth
- Marking as read: `router.post(route('activity.read', eventId))` — controller calls `ActivityService::markRead()`
- Bell dropdown lives in `AppHeader.vue` — pages never render their own bell
### Modals & their write actions
- One modal = one form = one named route
- Modal title is plain text only
- Modal footer always has Cancel + Submit buttons
- Submit button always: `:disabled="form.processing"`
### Money
- Integer cents in props, forms, DB — always
- Dollar input: separate display ref + watcher converts to cents
  ```js
  const displayAmount = ref(props.item?.amount_cents / 100 ?? 0)
  watch(displayAmount, v => { form.amount_cents = Math.round(v * 100) })
  ```
- Money display: `activity.formatMoney(cents)`
### Permission gates
- Backend Policy authorizes — frontend never decides authoritatively
- Frontend uses tier/role checks for UI hide/show only:
  ```vue
  <button v-if="auth.isPractitioner" ...>      <!-- visibility only -->
  <button v-if="auth.isPracticeTier" ...>      <!-- visibility only -->
  ```
- Tier upgrade prompts use `useUpgrade().requiresPractice(fn)`
### Vault gating
- Vault items only appear in props when backend Policy returns true
- Frontend trusts the prop — never decides "is incident active" locally
- Sealed state shown when `props.items?.length === 0` AND `props.planStatus === 'sealed'`
---
 
## SECTION 9 — BACKEND WIRING CONTRACT
 
Every page has an explicit contract with its controller. The contract must be verified on every component.
 
### Contract structure
```markdown
**Component:** Provider/Vault.vue
**Controller:** App\Http\Controllers\Provider\VaultController
**Service:** App\Services\VaultService
 
### GET route → props
| Route | Method | Inertia page | Props returned |
|-------|--------|-------------|----------------|
| /provider/vault | index() | Provider/Vault | zones, planStatus, attestedAt, sealedCount, unsealedCount |
 
### Write routes → forms
| Modal | Form fields | HTTP | Named route | FormRequest | Service method |
|-------|-------------|------|-------------|-------------|---------------|
| uploadModal | zone, title, description, file, access_level | POST | provider.vault.upload | UploadVaultItemRequest | VaultService::upload() |
| permissionsModal | access_level, release_on_incident | POST | provider.vault.permissions | SetVaultPermissionsRequest | VaultService::setPermissions() |
| deleteModal (confirm) | — | DELETE | provider.vault.destroy | — | VaultService::delete() |
 
### Policy checks
| Action | Policy method |
|--------|--------------|
| upload | VaultPolicy::upload($user, $plan) |
| view | VaultPolicy::view($user, $item) |
| download | VaultPolicy::download($user, $item) |
| delete | VaultPolicy::delete($user, $item) |
 
### Events fired (backend)
| Service call | Event |
|-------------|-------|
| VaultService::upload() | (none — logs activity directly) |
| PlanService::attestVault() | Plan\VaultAttested |
| IncidentService::activate() | Incident\VaultUnsealed |
```
 
The Vue component MUST:
- Read every prop from the GET route
- Bind every modal form to its FormRequest fields
- Submit to the exact named route
- Match the controller method 1:1
- Trust backend authorization (no inline policy logic)
---
 
## SECTION 10 — TOOLTIPS (data-tooltip ONLY)
 
The `_shared.css` tooltip system is the **only** tooltip mechanism. Pure CSS pseudo-element on `[data-tooltip]`.
 
```vue
✅ <button data-tooltip="Edit profile" class="btn-icon">
✅ <button data-tooltip="Coming soon" class="sso-btn" disabled>
❌ <button title="Edit profile">                    <!-- ugly browser native -->
❌ <span :title="someText">                          <!-- ugly browser native -->
❌ <Tooltip text="Edit">...</Tooltip>                <!-- no custom component -->
```
 
Every `title=` from PHP becomes `data-tooltip=` in Vue. No exceptions.
 
---
 
Every `title=` from PHP becomes `data-tooltip=` in Vue. No exceptions.

---

## SECTION 11 — ICON + TEXT ALIGNMENT

**RULE: Any element containing an `<AegisIcon>` AND text MUST use `inline-flex` (inline contexts) or `flex` (block contexts) with `align-items: center` and an explicit `gap`.**

Icons are inline `<span>` elements with intrinsic dimensions. When placed inside a plain `<span>` or `<div>` next to text, they sit on the text baseline — which is below the visual center of the icon. The result: icon and text look misaligned by a few pixels everywhere they appear.

The fix is structural — the parent decides the alignment, not the icon.

### When to use which

| Container role | Display | Example use |
|---------------|---------|------------|
| **Inline** badge, pill, status chip, label inside a row | `inline-flex` | Badge, kbd, tag, chip |
| **Block** alert, button content, list row, card header | `flex` | Alerts, list items, headers, button slot |

Both require `align-items: center` and a `gap` (typically 4–8px).

### Examples

```vue
<!-- ✅ Inline badge / label (inline-flex) -->
<div class="my-badge">
  <AegisIcon name="check" :size="10" />
  Verified
</div>
<!-- .my-badge { display: inline-flex; align-items: center; gap: 4px; } -->

<!-- ✅ Block row / alert (flex) -->
<div class="my-alert">
  <AegisIcon name="alert-circle" :size="15" />
  <span>Something went wrong</span>
</div>
<!-- .my-alert { display: flex; align-items: center; gap: 8px; } -->

<!-- ✅ Button — .btn already handles flex internally, just add gap -->
<button class="btn btn-primary">
  <AegisIcon name="plus" :size="14" />
  Add Item
</button>
<!-- .btn already sets display: inline-flex; align-items: center; gap: 6px; -->

<!-- ❌ WRONG — icon floats out of line with text -->
<span>
  <AegisIcon name="check" :size="12" /> Done
</span>
<!-- inline span = baseline alignment = icon sits low -->

<!-- ❌ WRONG — flex but no gap → text touches icon -->
<div style="display: flex; align-items: center;">
  <AegisIcon name="user" :size="14" />
  <span>Profile</span>
</div>

<!-- ❌ WRONG — no align-items → icon top-aligns with text -->
<div style="display: inline-flex; gap: 4px;">
  <AegisIcon name="user" :size="14" />
  Profile
</div>
```

### Gap sizing guide

| Icon size | Gap |
|-----------|-----|
| 10–12px | `4px` |
| 13–15px | `6px` |
| 16–18px | `8px` |
| 20px+ | `10–12px` |

### Inside `_shared.css` — what already handles this for you

These canonical classes already include the flex + align + gap recipe — use them and you never have to write the rules yourself:

- `.btn` — `inline-flex; align-items: center; gap: 6px`
- `.btn-icon` — square icon-only button, centers automatically
- `.badge` (all variants) — `inline-flex; align-items: center; gap: 4px`
- `.tab-pill` — `inline-flex; align-items: center; gap: 6px`
- `.stat-chip` — `flex; align-items: center; gap: 10px`
- `.alert` — `flex; align-items: center; gap: 8px`
- `.activity-item` — `flex; align-items: center`
- `.modal-step` — `inline-flex; align-items: center; gap: 6px`

**If the design uses one of these classes, you do NOT add extra flex/gap CSS.** Only add the recipe when building a NEW custom container.

### Anti-patterns

- ❌ Wrapping icon + text in plain `<span>` and hoping vertical-align fixes it
- ❌ `vertical-align: middle` on the icon — fragile, breaks with mixed font sizes
- ❌ Negative `margin-top` to "nudge" the icon up — breaks at different zoom levels
- ❌ `<br>` or whitespace between icon and text — gap is the only spacer
- ❌ Mixing `flex` on one row and `inline-flex` on another in the same table column — pick one per container type
- ❌ Setting `gap` without `display: flex` (or `inline-flex`) — gap requires a flex/grid parent

---

## SECTION 12 — TAB PATTERN
 
```vue
<script setup>
const activeTab = ref('overview')
 
const tabs = [
    { key: 'overview', label: 'Overview', icon: 'home' },
    { key: 'history',  label: 'History',  icon: 'clock' },
    { key: 'settings', label: 'Settings', icon: 'settings' },
]
</script>
 
<template>
  <!-- Single-level tabs — segmented pill style -->
  <div class="tabs-segmented">
    <button
      v-for="tab in tabs"
      :key="tab.key"
      class="tab-pill"
      :class="{ active: activeTab === tab.key }"
      @click="activeTab = tab.key"
    >
      <AegisIcon :name="tab.icon" :size="12" />
      {{ tab.label }}
    </button>
  </div>
 
  <!-- Use v-show — preserves scroll/state across tab switches -->
  <div v-show="activeTab === 'overview'" class="tab-pane">...</div>
  <div v-show="activeTab === 'history'"  class="tab-pane">...</div>
  <div v-show="activeTab === 'settings'" class="tab-pane">...</div>
</template>
```
 
---
 
## SECTION 13 — MULTI-STEP MODAL PATTERN

**RULE: Multi-step modals MUST use `size="xl"` or `size="fullscreen"`.**
Multi-step content frequently overflows on `sm`/`md`/`lg`, causing the step strip, field labels, or button rows to wrap to a second line. That looks broken. The fix is structural, not cosmetic — give the wizard room.

### Sizing rules

| Step count | Has long fields (textarea, file picker, multi-column)? | Required size |
|------------|-------------------------------------------------------|---------------|
| 2 steps | No | `xl` |
| 2 steps | Yes | `xl` or `fullscreen` |
| 3+ steps | Any | **`fullscreen`** |
| Any wizard with file upload + preview | Any | **`fullscreen`** |
| Any wizard with summary/review step | Any | **`fullscreen`** |

**`size="md"` or `size="lg"` are FORBIDDEN for any modal that contains `.modal-steps`.**

### Fullscreen variant
The `AegisModal` `size="fullscreen"` variant fills the viewport with a fixed inner max-width of 960px, allowing the step strip and content to breathe without becoming uncomfortably wide on large monitors.

```vue
<AegisModal v-model="modals.wizard" title="Designate Steward" size="fullscreen">

  <!-- Step strip — between header and body -->
  <div class="modal-steps">
    <div
      v-for="(s, i) in steps"
      :key="s.key"
      class="modal-step"
      :class="{
        active: currentStep === i,
        done:   currentStep > i,
      }"
    >
      <div class="modal-step-num">
        <AegisIcon v-if="currentStep > i" name="check" :size="12" />
        <span v-else>{{ i + 1 }}</span>
      </div>
      <span class="modal-step-label">{{ s.label }}</span>
      <div v-if="i < steps.length - 1" class="modal-step-divider" />
    </div>
  </div>

  <!-- Step bodies -->
  <div v-show="currentStep === 0">...step 1 form...</div>
  <div v-show="currentStep === 1">...step 2 form...</div>
  <div v-show="currentStep === 2">...step 3 form...</div>

  <template #footer>
    <button v-if="currentStep > 0" class="btn btn-outline" @click="currentStep--">Back</button>
    <button v-if="currentStep < steps.length - 1"
            class="btn btn-primary"
            :disabled="!isStepValid(currentStep)"
            @click="advance">Continue</button>
    <button v-else
            class="btn btn-primary"
            @click="submit"
            :disabled="form.processing || !isStepValid(currentStep)">
      {{ form.processing ? 'Submitting...' : 'Submit' }}
    </button>
  </template>

</AegisModal>

<style scoped>
/* Required spacing — keeps the step strip tight to the body */
.modal-steps + .modal-body { padding-top: 2px; }
</style>
```

### Step validation gate
**Every step must validate before advancing.** Disable Continue until the current step is valid:

```js
function isStepValid(step) {
    if (step === 0) return !v$.step1.$invalid
    if (step === 1) return !v$.step2.$invalid
    if (step === 2) return !v$.step3.$invalid
    return true
}

function advance() {
    // Run touched flag so errors show
    if (step === 0) v$.step1.$touch()
    if (!isStepValid(currentStep.value)) return
    currentStep.value++
}
```

See **Section 14 — Client-Side Validation** for the full validation pattern.

### Anti-patterns
- ❌ `size="md"` on a 3-step modal — fields wrap
- ❌ `size="lg"` with file upload step — preview area gets clipped
- ❌ Allowing Continue without validating current step
- ❌ Custom inline width/height styles to "make it fit" — use `fullscreen`
- ❌ Removing `.modal-step-label` to "save space" — fix the size instead
 
---
 
## SECTION 14 — CLIENT-SIDE VALIDATION

**RULE: Every form validates on the client BEFORE submitting to the server.**

Server validation is the source of truth (FormRequest rules + Policy authorization), but client-side validation catches obvious mistakes instantly — no network round-trip, no flicker, no failed submission. The two layers work together:

- **Client validation** = immediate, friendly feedback as the user fills the form
- **Server validation** = final word, the security boundary, always trusted

### Library: Vuelidate (`@vuelidate/core` + `@vuelidate/validators`)

Standard across all forms. Already installed; if missing:
```bash
npm install @vuelidate/core @vuelidate/validators
```

### Standard form pattern

```vue
<script setup>
import { reactive, computed } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import {
    required, email, minLength, maxLength, sameAs, numeric,
    helpers, requiredIf, minValue, url,
} from '@vuelidate/validators'
import { useForm } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'

const toast = useToast()

// Inertia form (server submission)
const form = useForm({
    display_name: '',
    email:        '',
    password:     '',
    password_confirmation: '',
    role:         '',
    bp_type:      null,
    amount_cents: 0,
})

// Vuelidate rules — keep keys identical to form fields
const rules = computed(() => ({
    display_name: {
        required: helpers.withMessage('Name is required.', required),
        max:      helpers.withMessage('Name must be 100 characters or less.', maxLength(100)),
    },
    email: {
        required: helpers.withMessage('Email is required.', required),
        email:    helpers.withMessage('Enter a valid email address.', email),
    },
    password: {
        required: helpers.withMessage('Password is required.', required),
        min:      helpers.withMessage('Password must be at least 8 characters.', minLength(8)),
    },
    password_confirmation: {
        required: helpers.withMessage('Please confirm your password.', required),
        sameAs:   helpers.withMessage('Passwords do not match.', sameAs(form.password)),
    },
    role: {
        required: helpers.withMessage('Select a role.', required),
    },
    bp_type: {
        requiredIf: helpers.withMessage(
            'Choose freelancer or agency.',
            requiredIf(() => form.role === 'business_partner')
        ),
    },
    amount_cents: {
        required: helpers.withMessage('Amount is required.', required),
        numeric:  helpers.withMessage('Amount must be a number.', numeric),
        min:      helpers.withMessage('Amount must be greater than zero.', minValue(1)),
    },
}))

const v$ = useVuelidate(rules, form)

// Submit handler — validate first, submit only if valid
async function submit() {
    const valid = await v$.value.$validate()
    if (!valid) {
        toast.error('Please fix the highlighted fields.')
        return
    }
    form.post(route('register.store'), {
        onSuccess: () => toast.success('Account created.'),
        onError:   () => toast.error('Please check the form.'),
    })
}
</script>
```

### Error display — UNIFIED with server errors

Client and server errors render through the **same `.form-error` element with the same styling**. The template chooses which message to show — client (Vuelidate) takes precedence while the user is editing, then server takes over after submit if it fires:

```vue
<template>
  <div class="form-group">
    <label class="form-label" for="email">Email address</label>
    <input
      id="email"
      v-model="form.email"
      type="email"
      class="form-input"
      :class="{ 'is-error': fieldError('email') }"
      @blur="v$.email.$touch()"
    />
    <!-- ONE error element, sources merged via fieldError() -->
    <div v-if="fieldError('email')" class="form-error">
      {{ fieldError('email') }}
    </div>
  </div>
</template>

<script setup>
// Helper — client error wins if field touched, otherwise show server error
function fieldError(field) {
    // Client error (Vuelidate)
    if (v$.value[field]?.$error) {
        return v$.value[field].$errors[0]?.$message
    }
    // Server error (Inertia)
    if (form.errors[field]) {
        return form.errors[field]
    }
    return null
}
</script>
```

**Why both must look identical:**
- Same `.form-error` class → same red text, same spacing, same icon
- Same `.is-error` class on the input → same red border, same focus ring
- User can't tell whether an error came from client or server — and shouldn't have to

### Validation timing — when errors appear

| Trigger | Behavior |
|---------|----------|
| User typing | NO error shown (don't punish mid-typing) |
| User leaves field (`@blur`) | `v$.field.$touch()` — errors appear if invalid |
| User clicks Submit | `v$.$validate()` — all fields touched + checked |
| Server returns 422 | `form.errors.field` populated by Inertia — shown via `fieldError()` |
| User edits a field with server error | Server error stays until next submit (Inertia handles) |

### Common validators

```js
import {
    required,         // not empty
    email,            // valid email
    numeric,          // numeric value
    integer,          // integer only
    minLength(n),     // min string length
    maxLength(n),     // max string length
    minValue(n),      // min numeric value
    maxValue(n),      // max numeric value
    between(a, b),    // numeric in range
    url,              // valid URL
    sameAs(ref),      // matches another field (passwords)
    requiredIf(fn),   // required only when fn() truthy
    requiredUnless(fn),
    alphaNum,         // letters + numbers only
    decimal,          // decimal number
    ipAddress,        // valid IP
    macAddress,       // valid MAC
    helpers,          // for custom + withMessage
} from '@vuelidate/validators'
```

### Custom validators

```js
// Phone number (US-only example)
const usPhone = helpers.withMessage(
    'Enter a 10-digit US phone number.',
    helpers.regex(/^\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}$/)
)

// Money — accepts dollars in UI, validates >= 0
const positiveMoney = helpers.withMessage(
    'Amount must be greater than zero.',
    (value) => Number(value) > 0
)

// Conditional — requires field A only if field B === value
const requiredIfBpAgency = helpers.withMessage(
    'Required for agency accounts.',
    requiredIf(() => form.role === 'business_partner' && form.bp_type === 'agency')
)

// Async custom — checks something dynamically
const slugUnique = helpers.withMessage(
    'That slug is already taken.',
    helpers.withAsync(async (value) => {
        if (!value) return true
        const res = await fetch(`/api/check-slug?slug=${value}`)
        return (await res.json()).available
    })
)
```

### Money field validation

Money is always integer cents in the form. The dollar input is a separate display value:

```vue
<script setup>
import { ref, watch } from 'vue'

const displayAmount = ref(0)
watch(displayAmount, v => {
    form.amount_cents = Math.round(Number(v) * 100)
    v$.value.amount_cents.$touch()
})
</script>

<template>
  <div class="form-group">
    <label class="form-label" for="amount">Amount</label>
    <input
      id="amount"
      v-model="displayAmount"
      type="number"
      step="0.01"
      min="0"
      class="form-input"
      :class="{ 'is-error': fieldError('amount_cents') }"
    />
    <div v-if="fieldError('amount_cents')" class="form-error">
      {{ fieldError('amount_cents') }}
    </div>
  </div>
</template>
```

### Multi-step modal validation gate

For wizards, validate per-step. Continue button disabled until the current step's fields are valid:

```js
function stepFields(step) {
    return {
        0: ['display_name', 'email'],
        1: ['password', 'password_confirmation'],
        2: ['role', 'bp_type'],
    }[step] ?? []
}

function isStepValid(step) {
    return stepFields(step).every(f => !v$.value[f].$invalid)
}

async function advance() {
    // Touch all fields in current step so errors render
    stepFields(currentStep.value).forEach(f => v$.value[f].$touch())
    if (!isStepValid(currentStep.value)) return
    currentStep.value++
}
```

### Validation rule pairing — client mirrors server

**Client rules must reflect server rules so users don't get tricked into a server rejection.** When `FormRequest::rules()` says `email|max:255`, the Vuelidate rule should be `email + maxLength(255)`. Same for `min:8`, `required_if`, `in:enum,values`, etc.

Pairing examples:

| Server FormRequest rule | Client Vuelidate validator |
|-------------------------|---------------------------|
| `'required'` | `required` |
| `'email'` | `email` |
| `'min:8'` (string) | `minLength(8)` |
| `'max:200'` | `maxLength(200)` |
| `'numeric\|min:1'` | `numeric + minValue(1)` |
| `'integer\|min:0'` (money) | `integer + minValue(0)` |
| `'confirmed'` (password_confirmation) | `sameAs(form.password)` on `password_confirmation` |
| `'required_if:role,business_partner'` | `requiredIf(() => form.role === 'business_partner')` |
| `'Rule::in(UserRole::cases())'` | check `value` is in same enum array on client |
| `'url'` | `url` |
| `'date\|after:today'` | custom `helpers.regex` + future-date check |
| `'mimes:pdf,jpg,png'` | check `file.type` on FileReader load |
| `'max:10240'` (KB file size) | check `file.size <= 10485760` |

### Anti-patterns

- ❌ Client-only validation — server validation MUST also exist (security boundary)
- ❌ Showing errors while user is still typing (only show after blur or submit)
- ❌ Different visual style for client vs server errors
- ❌ Skipping `v$.$validate()` before submit — relying on server alone defeats the purpose
- ❌ Stale Vuelidate refs after form reset — call `v$.$reset()` after `form.reset()`
- ❌ Hardcoding error messages without `helpers.withMessage()` — Vuelidate's defaults are generic
- ❌ Validating money in dollars but server expects cents — paired rules diverge

### Reset pattern

When closing a modal or resetting a form:
```js
function closeModal() {
    modals.create = false
    form.reset()
    v$.value.$reset()    // ← clear all validation state
}
```

---

## SECTION 15 — UPLOAD / DROPZONE PATTERN

**RULE: Every file input in the entire app — in any modal, any form, any page — MUST use `<AegisDropzone>`. Never use a raw `<input type="file">` directly in a template.**

`AegisDropzone` is the single canonical file upload component. It handles drag-and-drop, file selection via click, file name display, clear button, and accept/hint display.

### ⚠️ AegisDropzone is NOT globally registered

You MUST import it locally in every page that uses it:

```vue
<script setup>
import AegisDropzone from '@/components/ui/AegisDropzone.vue'
</script>
```

Missing the import = page crashes with `Failed to resolve component: AegisDropzone`. Check `app.js` if you're unsure what is globally registered — `AegisIcon`, `AegisModal`, `AegisToast`, `AegisHeroBanner`, `AegisStatChip`, `AegisBadge`, `AegisToggle`, `AegisCard`, `AegisEmptyState`, `AegisPagination`, `AegisConfirm`, `AegisUpgradeModal` are global; everything else needs a local import.

### ⚠️ The emit is `@files`, NOT `@file-selected`

```vue
<!-- ✅ CORRECT — @files emits an array, take the first file -->
<AegisDropzone
    accept=".pdf,.docx"
    hint="PDF or DOCX, max 10 MB"
    @files="form.file = $event[0]"
/>

<!-- ❌ WRONG — @file-selected does not exist on AegisDropzone -->
<AegisDropzone @file-selected="form.file = $event" />
```

If `multiple` is true: `@files="form.attachments = $event"` (keep the array).

### Confirm `.aegis-dropzone` CSS exists in `_shared.css` before shipping

Phantom class references render unstyled:

```bash
grep -c "\.aegis-dropzone" public/css/_shared.css   # must be > 0
```

If 0 — add the class to `_shared.css` before declaring the component done.

### `AegisDropzone` component props

```js
defineProps({
    modelValue: { type: [File, null], default: null },  // v-model OR use @files
    accept:     { type: String, default: '' },          // e.g. '.pdf,.jpg,.png'
    hint:       { type: String, default: '' },          // shown below dropzone
    multiple:   { type: Boolean, default: false },
    disabled:   { type: Boolean, default: false },
    maxSizeMb:  { type: Number, default: null },        // client-side size check
})
defineEmits(['update:modelValue', 'files'])
```

### Usage — every file upload everywhere

```vue
<!-- ✅ In any modal form, with @files (preferred pattern) -->
<script setup>
import AegisDropzone from '@/components/ui/AegisDropzone.vue'
</script>

<template>
    <div class="form-group">
        <label class="form-label">Document</label>
        <AegisDropzone
            accept=".pdf,.docx"
            hint="PDF or DOCX, max 10 MB"
            :max-size-mb="10"
            @files="form.file = $event[0]"
        />
        <div v-if="form.errors.file" class="form-error">{{ form.errors.file }}</div>
    </div>
</template>

<!-- ❌ WRONG — raw file input -->
<input type="file" @change="form.file = $event.target.files[0]" accept=".pdf" />

<!-- ❌ WRONG — custom dropzone HTML built inline in a page -->
<div class="drop-area" @drop="onDrop">Click or drag to upload</div>

<!-- ❌ WRONG — @file-selected does not exist -->
<AegisDropzone @file-selected="form.file = $event" />
```

### Always `forceFormData: true` when the form has a file field

```js
form.post(route('provider.vault.upload'), {
    forceFormData: true,   // ← required whenever form.file is present
    onSuccess: () => { modals.upload = false; toast.success('Uploaded.') },
    onError:   () => toast.error('Please check the form.'),
})
```

### Vuelidate for file fields

```js
file: {
    required: helpers.withMessage('Please select a file.', required),
    maxSize:  helpers.withMessage('File must be under 10 MB.',
        (v) => !v || v.size <= 10 * 1024 * 1024),
    mimeType: helpers.withMessage('Only PDF or DOCX allowed.',
        (v) => !v || ['application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ].includes(v.type)),
}
```

### Where `AegisDropzone` must appear (project-wide)

When porting any PHP page, `grep -n "input type=\"file\"" page.php` — every match becomes `<AegisDropzone>` in Vue.

| Page / Modal | Field |
|---|---|
| Vault upload modal (Provider + CS) | vault item file |
| Important Documents — upload/request modal | document file |
| Incident report modal (SS) | incident documentation |
| Incident verify modal (CS) | verification documentation |
| Incident update modal | update attachment |
| Profile — credential upload | credential scan |
| Messages — compose modal | message attachment |
| BP — proposal modal | proposal attachment |
| BP — milestone submit modal | deliverable file |
| Support — new ticket modal | ticket attachment |
| Admin — help article modal | article image/file |

---

## SECTION 16 — ANTI-PATTERN HALL OF SHAME
 
| ❌ Wrong | ✅ Correct |
|----------|-----------|
| `<svg>...</svg>` | `<AegisIcon name="shield" :size="16" />` |
| `title="Edit"` | `data-tooltip="Edit"` |
| `style="color:#a0813e"` | `style="color: var(--gold-dark)"` |
| `class="flex gap-4 p-4"` | `class="form-row"` |
| Stat chip inside `<AegisHeroBanner>` | Sibling `<div class="stat-chips-row">` after hero |
| `<AegisModal title="<AegisIcon /> Edit">` | `<AegisModal title="Edit">` |
| `window.location.href = '/x'` | `router.visit(route('x'))` |
| `axios.post('/x', data)` | `useForm({}).post(route('x'))` |
| `amount: 49.99` | `amount_cents: 4999` |
| `appearance: none` | `-webkit-appearance: none` |
| `<button class="btn btn-gold">` | `<button class="btn btn-primary">` |
| `alert('Done')` | `toast.success('Done')` |
| `confirm('Sure?')` | `confirmAction({ message: 'Sure?' }, () => {...})` |
| `const ok = await confirmAction(...)` | `confirmAction(opts, callback)` — callback-based, no await, no return |
| `<svg><use href="..."/></svg>` | `<AegisIcon name="..." />` |
| `style.display = 'flex'` for modal | `<AegisModal v-model="modals.x">` |
| Page-local CSS `.btn-primary { ... }` overriding global | Scoped `.page-x .btn-primary { ... }` |
| `class="btn btn-outline btn-icon"` chain | `class="btn-icon"` alone |
| `btn-icon` with icon size 13 in a table row | `btn-icon` with icon size 14 |
| `<AegisModal size="md">` on a 3-step wizard | `<AegisModal size="fullscreen">` (see Section 13) |
| `<AegisModal size="lg">` with file upload step | `<AegisModal size="fullscreen">` |
| Inline `style="width: 1200px"` to force modal width | Use `size="fullscreen"` |
| Removing `.modal-step-label` to save space | Resize the modal — never strip labels |
| Submit handler that goes straight to `form.post()` | `await v$.$validate()` first, then submit |
| `<div v-if="form.errors.x">` only — no client check | `<div v-if="fieldError('x')">` — unified client + server |
| Different `.form-error-client` class for Vuelidate | Same `.form-error` for both sources |
| Client validation in `mounted()` lifecycle | Reactive `useVuelidate(rules, form)` at setup |
| `form.reset()` without `v$.$reset()` | Both must reset together |
| Showing errors while user is typing | Only after `@blur` or submit (`v$.field.$touch()`) |
| Server `required_if:role,business_partner` with no client mirror | Pair with `requiredIf(() => form.role === '...')` |
| Short fields stacked vertically in a modal (zone, date, select) | Pair them in `.form-row` — see Section 4 Forms |
| `<input type="file">` in any template | `<AegisDropzone v-model="form.file" ... />` |
| Custom drag-drop HTML built inline in a page | Import and use `AegisDropzone` component |
| `forceFormData` omitted when form has a file field | Always add `forceFormData: true` when `form.file` is present |
| `<AegisDropzone @file-selected="..." />` | `<AegisDropzone @files="form.x = $event[0]" />` |
| Using `AegisDropzone` without importing it locally | `import AegisDropzone from '@/components/ui/AegisDropzone.vue'` |
| Referencing `.aegis-dropzone` CSS class that doesn't exist in `_shared.css` | Confirm class exists with `grep "\.aegis-dropzone" public/css/_shared.css` |
| `ui.openModal('xxx')` / `ui.closeModal('xxx')` | `modals.xxx = true` / `modals.xxx = false` on a `reactive()` modals object |
| `modal-id="xxx"` prop on AegisModal | `v-model="modals.xxx"` on AegisModal |
| Hardcoded names ("Robert Miller"), IDs ("NPI-2024"), amounts ($12,450) | Bind to controller props; build migration if data missing |
| Detail modal body hardcoded | `activeX` ref set on row click; modal reads `activeX.field` |
| `<option selected>` (PHP habit) | Set the default value on the v-model ref instead |
| Field name in `useForm()` doesn't match FormRequest | Read FormRequest first, match field names exactly |
| `.post()` when route is `Route::put()` | Verify route verb; use `.put()` / `.patch()` / `.delete()` to match |
| Service uses wrong column names (`completed_at` vs `completed_on`) | Read migration first; fix the service if it's wrong |
| Treating VARCHAR column as JSON array | Check `->string()` vs `->json()` in the migration |
| Tooltip handler calls `e.target.closest()` without guard | Guard with `if (!(e.target instanceof Element)) return` |
| `</script>` removed during a large edit, breaking the SFC | Verify section boundaries after any large edit |
| `<span><AegisIcon /> Done</span>` — baseline drift | Wrap in `inline-flex; align-items: center; gap: 4px` container |
| `display: flex` on icon row but no `gap` | Add `gap: 6–8px` to space icon from text |
| `vertical-align: middle` on `<AegisIcon>` | Parent: `align-items: center` on flex/inline-flex |
| Custom `margin-top: -2px` to "nudge" icon | Use flex centering on the parent instead |
| `useForm({})` declared inside a function / handler / `if` block | Declare ALL `useForm()` at component setup top-level — never inside functions |
| Multiple parallel `useForm()` for a multi-field config save | Single `cfgForm = useForm({})` + `.transform(() => ({...}))` → one atomic endpoint |
| Hardcoded defaults in `reactive({ team: ['Psychotherapist'], states: ['NY'] })` | Init from prop: `const nc = props.networkConfig \|\| {}` then spread into reactive |
| `v-if="isConnected"` gating a Message button in a public-profile hero | Message shows for all logged-in non-owners; don't gate on connection state |
| `v-if="s.match"` on a match badge (0 is falsy → badge vanishes) | Render always; show `'—'` when 0: `{{ s.match \|\| '—' }}{{ s.match ? '%' : '' }}` |
| `justify-content: space-between` on `.spc-top-pills` (it's `position:absolute`) | Left pill in `.spc-top-pills`; right pill in its own `position:absolute; right:10px` div |
| `display:none/block` toggle expecting a CSS open+close animation | `v-show` + `<Transition name="cfg-slide">`; CSS `@keyframes` only animates the open |
| `max-height: 0 → 1800px` accordion transition (content bleeds on tall panels) | `v-show` + Vue `<Transition>` — no max-height guessing |
| `router.reload()` after a save without the new prop in `only: [...]` | Include every section that must refresh: `only: ['shadowConnections','referralCandidates','stats']` |
| Splicing a card by `id` when the list has empty-string ids | Splice by `id` when present, else by `name` — empty-string ids are falsy and never match |
| Chevron `<span>` placed inside `.cfg-panel-header-left` | Chevron is a sibling of `-left` (or inside `.cfg-panel-meta`), right-aligned in the header |
| Surfacing CS/SS/BP users in shadow slots or referral candidates | Shadows + referral candidates are practitioner-only (`role === 'practitioner'`) |
 
---
 
## SECTION 17 — CSS CLASS REFERENCE
 
Curated list of `_shared.css` classes Vue components actually use. Use these directly — never invent new class names.
 
### Layout
`.page-content` · `.page-section` · `.page-grid` · `.page-grid-main` · `.page-grid-aside`
 
### Hero
`.hero-banner` · `.hero-banner.is-quiet` · `.page-hero-inner` · `.page-hero-left` · `.page-hero-eyebrow` · `.page-hero-title` · `.page-hero-sub` · `.page-hero-actions`
 
### Stat chips
`.stat-chips-row` · `.stat-chip` · `.stat-chip-icon` · `.stat-chip-value` · `.stat-chip-label`
 
### Cards
`.card` · `.card-header` · `.card-title` · `.card-body` · `.card-footer`
 
### Buttons
`.btn` · `.btn-primary` · `.btn-outline` · `.btn-danger` · `.btn-ghost` · `.btn-hero-solid.is-on-light` · `.btn-hero-ghost.is-on-light` · `.btn-sm` · `.btn-icon` · `.btn-icon-sm` · `.btn-icon-danger` · `.btn-icon-primary`
 
### Badges
`.badge` · `.badge--gold` · `.badge--green` · `.badge--red` · `.badge--orange` · `.badge--blue` · `.badge--purple` · `.badge--grey`
 
### Forms
`.form-group` · `.form-label` · `.form-input` · `.form-select` · `.form-textarea` · `.form-error` · `.form-hint` · `.form-row` · `.form-row.is-3col` · `.form-check` · `.form-check-input` · `.form-check-label` · `.form-label-link`
 
### Toggles
`.toggle` · `.toggle-label` · `.toggle-input` · `.toggle-track` · `.toggle-thumb` · `.toggle-text`
 
### Tabs
`.tabs-segmented` · `.tab-pill` · `.tabs-twotier` · `.tabs-primary` · `.net-sub-tabs` · `.tab-pane`
 
### Tables
`.data-table` · `.data-table th` · `.data-table td` · `.data-table tr:hover`
 
### Modals
`.modal-overlay` · `.modal` · `.modal-sm` · `.modal-md` · `.modal-lg` · `.modal-xl` · `.modal-fullscreen` · `.modal-header` · `.modal-title` · `.modal-close` · `.modal-body` · `.modal-footer` · `.modal-steps` · `.modal-step` · `.modal-step-num` · `.modal-step-divider`
 
### Activity feed
`.activity-feed` · `.activity-item` · `.activity-item--info` · `.activity-item--warning` · `.activity-item--critical` · `.activity-item-icon` · `.activity-item-title` · `.activity-item-desc` · `.activity-item-time`
 
### Empty state
`.empty-state` · `.empty-state-icon` · `.empty-state-title` · `.empty-state-sub`
 
### Avatars
`.avatar` · `.avatar-gold` · `.avatar-dark` · `.avatar-red` · `.avatar-xs` · `.avatar-sm` · `.avatar-md` · `.avatar-lg` · `.avatar-xl` · `.avatar-2xl`
 
### Utility
`.text-muted` · `.text-danger` · `.text-success` · `.divider` · `.sr-only`
 
---
 
## SECTION 18 — CSS TOKEN REFERENCE
 
Only these `var(--*)` values may appear in Vue component styles.
 
### Colors — Brand
`var(--primary)` · `var(--primary-mid)` · `var(--primary-light)` · `var(--gold)` · `var(--gold-light)` · `var(--gold-dark)`
 
### Colors — Status
`var(--green)` · `var(--green-light)` · `var(--green-dark)` ·
`var(--red)` · `var(--red-light)` · `var(--red-dark)` ·
`var(--orange)` · `var(--orange-light)` · `var(--orange-dark)` ·
`var(--blue)` · `var(--blue-light)` · `var(--blue-dark)` ·
`var(--purple)` · `var(--purple-light)` · `var(--purple-dark)` ·
`var(--teal)` · `var(--teal-light)` · `var(--teal-dark)`
 
### Colors — Soft & Fade Borders
`var(--soft-gold)` · `var(--soft-green)` · `var(--soft-blue)` · `var(--soft-red)` · `var(--soft-orange)` ·
`var(--fade-gold)` · `var(--fade-green)` · `var(--fade-blue)` · `var(--fade-red)` · `var(--fade-orange)`
 
### Colors — Emergency
`var(--emergency)` · `var(--emergency-light)` · `var(--emergency-dark)`
 
### Colors — Surfaces
`var(--surface)` · `var(--surface-2)` · `var(--surface-3)` · `var(--surface-4)` · `var(--bg)` · `var(--bg-2)`
 
### Colors — Borders
`var(--border)` · `var(--border-dark)`
 
### Colors — Text
`var(--text)` · `var(--text-2)` · `var(--text-3)` · `var(--text-4)` · `var(--text-inverted)`
 
### Typography
`var(--font-serif)` — Spectral (headings, card titles, modal titles, stat values, hero title, sidebar brand)
Inter is the body default — no variable needed
 
### Radius
`var(--radius-sm)` · `var(--radius)` · `var(--radius-lg)` · `var(--radius-full)`
 
### Shadows
`var(--shadow-sm)` · `var(--shadow)` · `var(--shadow-lg)`
 
### Badge backgrounds
`var(--badge-bg-gold)` · `var(--badge-bg-green)` · `var(--badge-bg-red)` · `var(--badge-bg-orange)` · `var(--badge-bg-blue)` · `var(--badge-bg-purple)` · `var(--badge-bg-grey)`
 
---
 
## SECTION 19 — FILE NAMING & LOCATION
 
```
resources/js/
├── pages/
│   ├── auth/              # Login, Register, ForgotPassword, ResetPassword, MfaChallenge
│   ├── provider/          # Provider portal pages
│   ├── continuity-steward/
│   ├── support-steward/
│   ├── business-partner/
│   ├── admin/
│   ├── shared/            # Overview, Messages, Activity, Support (cross-portal)
│   └── public/            # Public profile pages
├── layouts/
│   ├── AppLayout.vue      # authenticated portal pages
│   ├── AuthLayout.vue     # login/register/reset
│   └── PublicLayout.vue   # public profile pages
├── components/
│   ├── chrome/            # AppSidebar, AppHeader, DemoSwitcher
│   ├── ui/                # AegisIcon, AegisModal, etc.
│   ├── features/          # ActivityFeed, IncidentBanner, etc.
│   └── modals/            # ReferralModal, ProposalModal, etc.
├── stores/                # Pinia stores (.js)
└── composables/           # Composition API functions (.js)
```
 
**Naming:**
- PascalCase for all `.vue` files: `Dashboard.vue`, `ContinuityPlan.vue`
- camelCase for all `.js` files: `useToast.js`, `auth.js`
- Pages mirror PHP source filenames:
  - `provider-portal/continuity-plan.php` → `pages/provider/ContinuityPlan.vue`
  - `continuity-steward-portal/my-tasks.php` → `pages/continuity-steward/MyTasks.vue`
---
 
## SECTION 20 — NO HARDCODED / STATIC DATA

**Every list, table, card grid, and detail panel must read from controller props. Never hardcode demo data into the template.**

Hardcoded data is the most common reason a page looks correct in dev and breaks in staging — the controller doesn't return the prop, the page silently falls back to placeholder content, and nobody notices for weeks.

### Banned in any Vue template

```vue
<!-- ❌ Names, IDs, dates, dollar amounts hardcoded -->
<tr>
    <td>Robert Miller, LMFT</td>
    <td>CA-LMFT-12345</td>
    <td>Expires Mar 2026</td>
</tr>

<!-- ❌ Demo numbers in finance/dashboard cards -->
<div class="stat-chip-value">$12,450</div>

<!-- ❌ Status copy hardcoded next to dynamic data -->
<div>Plan signed by John D. on October 3</div>
```

### Required pattern

```vue
<!-- ✅ Every row from props -->
<tr v-for="cred in credentials" :key="cred.id">
    <td>{{ cred.provider_name }}</td>
    <td>{{ cred.number }}</td>
    <td>Expires {{ activity.formatDate(cred.expires_at) }}</td>
</tr>

<!-- ✅ Empty state when data is missing -->
<AegisEmptyState
    v-if="!credentials.length"
    icon="shield"
    title="No credentials on file."
    subtitle="Add your first credential to begin."
/>
```

### If the data doesn't exist in the database yet

Build the migration + seeder + model + controller method. **Do not** fake it with static markup.

```bash
# Verify the column exists before writing the v-for
grep -rn "credentials" $LARAVEL_ROOT/database/migrations/ | head -5
```

### Detail modals — never hardcoded copy

When a user clicks a row to open a detail modal, the modal body must read from a reactive ref set when the row was clicked. Not from hardcoded strings.

```vue
<!-- ✅ Click sets activeCredential -->
<tr v-for="c in credentials" @click="activeCredential = c">

<!-- Modal body reads from the ref -->
<AegisModal v-model="modals.detail" title="Credential Details">
    <div v-if="activeCredential">
        <p>{{ activeCredential.provider_name }}</p>
        <p>{{ activeCredential.number }}</p>
    </div>
</AegisModal>
```

### Forbidden placeholder values (banned everywhere)

| Banned | Why |
|---|---|
| `Robert Miller, LMFT` | Fake practitioner name from prototype |
| `John D.`, `Sarah M.` | Initials placeholders |
| `NPI-2024-0001`, `CA-MD-67890` | Fake credential IDs |
| `$12,450`, `$8,900` | Demo dollar amounts |
| `Last updated 3 days ago` (static text) | Stale relative dates |
| `Lorem ipsum...` | Placeholder copy |
| `example.com`, `test@test.com` | Placeholder URLs/emails |

If you find any of these in a Vue template — replace with the corresponding prop binding. If the prop doesn't exist, add it to the controller.

---

## SECTION 21 — BACKEND CONTRACT VERIFICATION

**Before writing any form, verify the contract with the backend. Three things must match exactly.**

### 21A — Field names must match the FormRequest exactly

```bash
# Read the FormRequest before writing form fields
cat $LARAVEL_ROOT/app/Http/Requests/Provider/StoreCredentialRequest.php
```

If the FormRequest validates `cred_type` and your `useForm()` sends `type` — validation passes Vuelidate but fails server-side with no clear error. Field names match exactly:

```vue
<!-- FormRequest expects: cred_type, number, expires_at, provider_name -->
const form = useForm({
    cred_type:     '',   // ✅ matches FormRequest
    number:        '',
    expires_at:    '',
    provider_name: '',
})

<!-- ❌ WRONG — field name mismatch -->
const form = useForm({
    type:    '',         // ❌ FormRequest expects cred_type
    license: '',         // ❌ FormRequest expects number
})
```

### 21B — HTTP verb must match the route

```bash
# Verify the verb before form.post / form.put / form.delete
grep "credentials" $LARAVEL_ROOT/routes/web.php
# Route::post('/credentials',  ...) → form.post()
# Route::put('/credentials/{id}', ...) → form.put()
# Route::delete('/credentials/{id}', ...) → form.delete()
```

Wrong verb = 405 Method Not Allowed. Verify every form's HTTP method:

```js
// ✅ Create — POST
form.post(route('provider.credentials.store'))

// ✅ Update — PUT (or PATCH if the route uses PATCH)
form.put(route('provider.credentials.update', cred.id))

// ✅ Delete — DELETE
form.delete(route('provider.credentials.destroy', cred.id))
```

### 21C — Column names must match the migration

```bash
# Read the migration BEFORE writing service or seeder logic
cat $LARAVEL_ROOT/database/migrations/*credentials*
```

**Patterns that caused real bugs in this project:**

| Wrong | Right | Where |
|---|---|---|
| `completed_at` | `completed_on` | `provider_ceus` migration |
| `credits` | `credit_hours` | `provider_ceus` migration |
| `category` | (none — does not exist) | `provider_ceus` migration |
| `users.credentials` as JSON array | `users.credentials` is VARCHAR (single string) | `users` table |

**Always check the column type before assuming shape:**

```bash
grep -A 2 "credentials" $LARAVEL_ROOT/database/migrations/*create_users*
# If output shows ->string('credentials') — it's VARCHAR, NOT a JSON array
# If output shows ->json('credentials')   — it's a JSON column
# If no match — there is no credentials column on users, look elsewhere
```

Treating a VARCHAR column as a JSON array → empty dashboard, no error.

### 21D — Controller must pass every prop the Vue page declares

```js
// If Vue declares:
defineProps({
    plan:        Object,
    stewards:    Array,
    credentials: Array,
    insurance:   Array,
})

// Then controller MUST return ALL four:
return Inertia::render('Provider/Dashboard', [
    'plan'        => $plan,
    'stewards'    => $stewards,
    'credentials' => $credentials,
    'insurance'   => $insurance,
]);
```

Missing props arrive as `undefined` in Vue. Accessing `.length` on `undefined` crashes the page silently in some browsers, blank-screens in others.

### 21E — Read services + controllers BEFORE writing form logic

```bash
# Required reads for any page conversion:
cat $LARAVEL_ROOT/app/Http/Controllers/[Portal]/[PageController].php
cat $LARAVEL_ROOT/app/Http/Requests/[Portal]/[FormRequest].php
cat $LARAVEL_ROOT/app/Services/[Service].php
cat $LARAVEL_ROOT/database/migrations/*[relevant_table]*
```

If the service uses wrong column names (e.g. `completed_at` when migration uses `completed_on`), **fix the service** — never accommodate the bug in the Vue layer.

---

## SECTION 22 — VERIFICATION CHECKLIST
 
Before declaring any component "done":
 
```markdown
- [ ] Every PHP section is in Vue
- [ ] Every PHP modal is in Vue (correct trigger, form, route)
- [ ] Every `aegis_icon()` is `<AegisIcon>` — no raw `<svg>`
- [ ] Every `data-tooltip` from PHP is in Vue — no `title=`
- [ ] Every form uses `useForm()` with onSuccess + onError
- [ ] Every form uses Vuelidate (`useVuelidate`) — client validates before submit
- [ ] Every form field shows errors via `fieldError(name)` helper — same `.form-error` for client + server
- [ ] Every multi-step modal uses `size="xl"` or `size="fullscreen"` — never `md` / `lg`
- [ ] Every step in a wizard validates before Continue is enabled
- [ ] Every submit button has `:disabled="form.processing"`
- [ ] Every link uses `route()` — no hardcoded URLs
- [ ] Every condition uses store getters (auth.isAccessTier, incident.hasEmergency)
- [ ] Every PHP `<style>` rule is in `<style scoped>` with var(--token) values
- [ ] No Tailwind classes
- [ ] No bare hex colors (except brand SVGs with comment)
- [ ] No icons inside modal titles
- [ ] Stat chips outside `<AegisHeroBanner>`
- [ ] All money fields are integer cents (form) — dollar input as separate display ref with watcher
- [ ] All write routes match the controller exactly
- [ ] All props match what the controller returns
- [ ] Every `<AegisIcon>` placed next to text sits inside an `inline-flex` or `flex` container with `align-items: center` + `gap`
- [ ] No `<AegisIcon>` next to text inside a plain `<span>` or `<div>` (baseline drift)
- [ ] Client validation rules pair with server FormRequest rules (Section 14 pairing table)
- [ ] `v$.$reset()` is called after `form.reset()` on modal close
- [ ] Short fields in modals (selects, dates, numbers) are paired in `.form-row` — not stacked vertically
- [ ] Every file input uses `<AegisDropzone>` — no raw `<input type="file">` in any template
- [ ] Every form with a file field has `forceFormData: true` in the submit call
- [ ] `AegisDropzone` is imported locally in every page that uses it
- [ ] AegisDropzone uses `@files="form.x = $event[0]"` — never `@file-selected`
- [ ] `.aegis-dropzone` CSS class confirmed to exist in `_shared.css`
- [ ] No hardcoded names, IDs, amounts, dates, or copy in the template
- [ ] Every detail modal reads from an `activeX` ref set on row click — not hardcoded
- [ ] FormRequest read and field names match exactly
- [ ] Route verb verified before `.post` / `.put` / `.delete`
- [ ] Migration column names verified before service / form code
- [ ] All controller props match what `defineProps` declares
- [ ] `</script>` closing tag present before `<style>` after any large edit
- [ ] No `<option selected>` — defaults set via v-model ref initial value
- [ ] Tooltip event handlers guard `e.target instanceof Element` before `.closest()`

## SECTION 23 — PRE-FLIGHT GATES (grep-based)

Run these before declaring any Vue page done. Every gate must return 0 hits (except where noted).

```bash
PAGE=resources/js/pages/[portal]/[Page].vue

# Modal system
grep -c "modal-id=" $PAGE                       # → 0
grep -c "ui.openModal\|ui.closeModal" $PAGE     # → 0
grep -c "openModal\s*(" $PAGE                   # → 0 (no global function calls)

# AegisDropzone
grep -c "@file-selected" $PAGE                  # → 0
grep -c "<input type=\"file\"" $PAGE            # → 0
grep -c "type=\"file\"" $PAGE                   # → 0
# If page has AegisDropzone, confirm import:
if grep -q "<AegisDropzone" $PAGE; then
    grep -c "import AegisDropzone" $PAGE        # → must be > 0
fi

# Icons
grep -c "<svg" $PAGE                            # → 0 (no raw SVG)
grep -E "name=\"(square-pen|x-circle|gear|time|magnifier|person|event)\"" $PAGE  # → no output

# Tooltips
grep -c 'title="' $PAGE                         # → 0 (use data-tooltip)

# Hardcoded data sniff
grep -E "Robert Miller|John D\.|Sarah M\.|NPI-2024|CA-MD-67890|Lorem ipsum" $PAGE  # → no output
grep -E "\\\$12,450|\\\$8,900" $PAGE             # → no output (demo dollar amounts)

# Tailwind sniff
grep -E "class=\"[^\"]*\b(flex|grid|p-[0-9]|m-[0-9]|text-sm|text-lg|bg-[a-z])" $PAGE  # → no output

# Hex colors (allow brand SVGs only)
grep -E "#[0-9a-fA-F]{3,6}" $PAGE | grep -v "brand\|comment"  # → no output

# SFC structure
grep -c "<script setup>" $PAGE                  # → 1
grep -c "</script>" $PAGE                       # → 1
grep -c "<template>" $PAGE                      # → 1
grep -c "</template>" $PAGE                     # → 1

# AegisModal v-model presence — every <AegisModal> needs a matching v-model
MODAL_COUNT=$(grep -c "<AegisModal" $PAGE)
VMODEL_COUNT=$(grep -c "v-model=\"modals\\." $PAGE)
test $MODAL_COUNT -eq $VMODEL_COUNT && echo "✅ modals = v-models" || echo "❌ mismatch: $MODAL_COUNT modals, $VMODEL_COUNT v-models"
```

Run the suite. If anything fails — fix it before delivering.
---

## SECTION 24 — MODULE-SCOPED ACTIVITY LINKS

### Rule
Every page that has a link to the Activity Log **must** pass a `?module=<slug>` query param so the Activity page pre-filters to logs relevant to the current page. The Activity controller already accepts `module` as a filter (`ActivityController` line 31: `$allQuery->where('module', $filters['module'])`).

### Pattern

```vue
<!-- ✅ CORRECT — scopes log to this module -->
<a
  :href="route('activity.index', {}, false) + '?module=messages'"
  class="btn-icon btn-icon-sm"
  data-tooltip="Message activity log"
>
  <AegisIcon name="activity" :size="16" />
</a>

<!-- ❌ WRONG — opens full unfiltered log -->
<a :href="route('activity.index')" ...>
```

### Module slug registry

Use exact lowercase slugs that match the `module` column in the `activity_events` table:

| Page / Feature          | `?module=` slug         |
|-------------------------|-------------------------|
| Messages                | `messages`              |
| Continuity Plan         | `continuity_plan`       |
| Critical Incidents      | `incidents`             |
| Vault                   | `vault`                 |
| Referrals               | `referrals`             |
| Job Postings            | `job_postings`          |
| Support / Tickets       | `support`               |
| Billing / Payments      | `billing`               |
| Settings                | `settings`              |
| Business Partners       | `business_partners`     |
| Continuity Stewards     | `continuity_stewards`   |
| Support Stewards        | `support_stewards`      |
| Admin — Users           | `admin_users`           |
| Admin — Help Articles   | `help_articles`         |

### Checklist item (add to Section 22)
- [ ] Every "Activity" link appends `?module=<slug>` matching the current page's domain — never bare `route('activity.index')`

### Activity page (Vue) — receiving the filter
The Activity Vue page reads the `filters` prop passed by `ActivityController` and pre-populates the module dropdown:

```vue
// In Activity.vue <script setup>
const props = defineProps({ filters: Object, ... })
const activeModule = ref(props.filters?.module ?? '')
// Pass activeModule into the filter select v-model
// so the UI reflects the pre-applied filter on load
```

### Pre-flight gate

```bash
# Every activity link in the page must carry a module param
grep -E "route\('activity\.index'\)" $PAGE   # → 0 (bare link is a violation)
grep -E "\?module=" $PAGE                    # → must have ≥ 1 hit per activity link
```


---

## SECTION 25 — EMAIL TRIGGER RULE

**Every write action that changes meaningful state MUST dispatch an email to every affected party.** A form that saves without sending any email is an incomplete deliverable.

### The wiring chain (Service → Event → Listener → Job)

```
Controller → Service::method()
               ├── DB write
               ├── ActivityService::log()        ← Section 26
               ├── event(new XxxHappened($model))
               └── Listener → dispatch(new SendEmailJob(...))
```

Events fire from **Services**, never from Controllers or FormRequests.

### How to find the right template

```bash
# List all email templates grouped by domain
find resources/views/emails -name "*.blade.php" | sort

# Match template to the action being performed:
# emails/auth/        → registration, password reset, MFA, new device
# emails/plan/        → plan created, signed, updated, expiring, expired
# emails/steward/     → CS/SS designated, removed, unresponsive, replaced
# emails/incident/    → reported, verified, activated, task assigned, closed
# emails/bp/          → proposal sent, accepted, rejected, contract signed,
#                       milestone submitted/approved, invoice sent/paid
# emails/network/     → connection request, accepted, referral sent/received
# emails/support/     → ticket created, replied, resolved, contact form
# emails/admin/       → user flagged, package changed, payout processed
# emails/digest/      → weekly/monthly summary
```

### Who receives each email

| Action | Actor email | Other recipient(s) |
|--------|------------|-------------------|
| Plan signed | Provider — "Your plan is active" | CS — "Plan signed, review required" |
| CS designated | Provider — "CS assigned" | CS — "You have been designated" |
| Incident activated | Provider — "Incident activated" | CS — "Action required", SS — "FYI" |
| Incident task assigned | CS (assigning) | SS — "Task assigned to you" |
| Proposal accepted | BP — "Proposal accepted" | Provider — "You accepted a proposal" |
| Milestone submitted | BP | Provider — "Milestone needs review" |
| Invoice sent | BP | Provider — "Invoice received" |
| Message received | Sender | Recipient — "New message" |
| Support ticket | User | Admin — "New support ticket" |

Always send to **all** parties affected by the action.

### Dispatch pattern in the Service

```php
// After the DB write succeeds:

// 1. Dispatch event (Listener handles email sending)
event(new PlanSigned($plan));

// app/Listeners/SendPlanSignedEmail.php
public function handle(PlanSigned $event): void
{
    $plan = $event->plan;

    // Email to provider
    dispatch(new SendEmailJob(
        recipientId: $plan->provider_id,
        template:    'emails.plan.plan-signed',
        subject:     'Your continuity plan is now active',
        data:        EmailDataResolver::for($plan),
    ));

    // Email to CS
    if ($plan->continuity_steward_id) {
        dispatch(new SendEmailJob(
            recipientId: $plan->continuity_steward_id,
            template:    'emails.plan.plan-signed-cs',
            subject:     'Continuity plan signed — review required',
            data:        EmailDataResolver::for($plan),
        ));
    }
}
```

### Wire in EventServiceProvider

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    PlanSigned::class => [SendPlanSignedEmail::class],
    IncidentActivated::class => [SendIncidentActivatedEmail::class],
    StewardDesignated::class => [SendStewardDesignatedEmail::class],
    // ... one entry per event
];
```

### Pre-flight gate

```bash
# Every service method that mutates state must dispatch an event
# Check for any save/update without a matching event() call:
grep -n "->save()\|->update(\|->create(" app/Services/[Service].php | head -30
grep -n "event(new" app/Services/[Service].php | head -30
# Every save should have a paired event(new ...) after it

# Every email template has a matching dispatch somewhere
for tmpl in $(find resources/views/emails -name "*.blade.php" |     sed "s|resources/views/||;s|.blade.php||;s|/|.|g"); do
    count=$(grep -rn "'$tmpl'" app/ 2>/dev/null | grep -c "SendEmailJob\|dispatch")
    echo "$count $tmpl"
done
# Every line must show count > 0
```

---

## SECTION 26 — ACTIVITY LOG + NOTIFICATION RULE

**Every write action that changes meaningful state MUST:**
1. Log `entry_type: 'log'` for the actor (their own history)
2. Log `entry_type: 'notification'` for every other affected party (appears in their Notifications tab)

Missing log or notification entries = incomplete deliverable.

### The two entry types

| `entry_type` | Who sees it | What it means | Tab in Activity.vue |
|---|---|---|---|
| `log` | The actor | "I did this" — self-history | My Activity |
| `notification` | Other affected parties | "Someone did something that involves you" | Notifications |

### `ActivityService::log()` signature

```php
ActivityService::log(
    actor:       User,         // who performed the action
    event:       string,       // e.g. 'plan.signed', 'incident.activated'
    subject:     Model,        // the primary model being acted on
    module:      string,       // slug: 'plan'|'incident'|'vault'|'steward'|'network'|'bp'|'messages'|'support'|'finances'|'settings'
    severity:    string,       // 'info'|'warning'|'error'|'critical' (default: 'info')
    entryType:   string,       // 'log'|'notification'
    recipientId: ?int,         // null = actor receives it; int = other party receives it
);
```

### Full pattern in the Service

```php
// app/Services/PlanService.php
public function sign(Plan $plan): void
{
    $plan->update(['status' => PlanStatus::Active, 'signed_at' => now()]);

    // 1. Actor's own log
    ActivityService::log(
        actor:      auth()->user(),
        event:      'plan.signed',
        subject:    $plan,
        module:     'plan',
        severity:   'info',
        entryType:  'log',
    );

    // 2. Notification to CS
    if ($plan->continuity_steward_id) {
        ActivityService::log(
            actor:       auth()->user(),
            event:       'plan.signed',
            subject:     $plan,
            module:      'plan',
            severity:    'info',
            entryType:   'notification',
            recipientId: $plan->continuity_steward_id,
        );
    }

    // 3. Email
    event(new PlanSigned($plan));   // ← Section 25
}
```

### When to use `ActivityFanoutJob` (> 3 recipients)

When an action affects more than 3 other users (e.g. broadcast to all network connections), use the job:

```php
// Do NOT loop ActivityService::log() for large fan-outs
dispatch(new ActivityFanoutJob(
    subject:     $incident,
    event:       'incident.activated',
    module:      'incident',
    severity:    'critical',
    entryType:   'notification',
    recipientIds: $incident->allStakeholderIds(),  // array of user IDs
));
```

### Module slug registry

Every log entry must include the correct module slug so the Activity.vue filter works:

| Domain | Module slug |
|---|---|
| Continuity Plan | `plan` |
| Critical Incident | `incident` |
| Vault | `vault` |
| Continuity Stewards | `steward` |
| Support Stewards | `steward` |
| Network / Referrals | `network` |
| Business Partners / Jobs | `bp` |
| Important Documents | `documents` |
| Messages | `messages` |
| Support Tickets | `support` |
| Finances / Invoices | `finances` |
| Settings / Profile | `settings` |
| Auth / Account | `auth` |

### Events that must always be logged (minimum required set)

| Event | Actor log | Notification recipient(s) |
|---|---|---|
| User registered | `auth.registered` — actor | Admin |
| Plan created | `plan.created` — provider | CS (if designated) |
| Plan signed | `plan.signed` — provider | CS |
| Plan updated | `plan.updated` — provider | CS |
| CS designated | `steward.cs-designated` — provider | CS |
| CS removed | `steward.cs-removed` — provider | CS |
| SS designated | `steward.ss-designated` — provider | SS |
| Incident reported | `incident.reported` — SS | Provider, CS |
| Incident activated | `incident.activated` — CS | Provider, SS, all network |
| Incident task assigned | `incident.task-assigned` — CS | SS |
| Incident closed | `incident.closed` — CS | Provider, SS |
| Vault item uploaded | `vault.uploaded` — provider | CS |
| Document shared | `documents.shared` — provider | Recipient |
| Network connection sent | `network.connection-sent` — provider | Target provider |
| Network connection accepted | `network.connection-accepted` | Initiator |
| Referral sent | `network.referral-sent` — provider | Recipient |
| BP proposal sent | `bp.proposal-sent` — BP | Provider |
| BP proposal accepted | `bp.proposal-accepted` — provider | BP |
| Milestone submitted | `bp.milestone-submitted` — BP | Provider |
| Invoice sent | `bp.invoice-sent` — BP | Provider |
| Message sent | `messages.sent` — sender | Recipient |
| Support ticket created | `support.ticket-created` — user | Admin |

### Pre-flight gate

```bash
# Every service method that mutates state calls ActivityService::log()
grep -n "->save()\|->update(\|->create(" app/Services/[Service].php
grep -n "ActivityService::log"            app/Services/[Service].php
# Counts should align — every mutation has a log call

# Verify notification entries are created for other parties
grep -n "entryType.*notification\|entry_type.*notification"     app/Services/[Service].php
# Must have ≥ 1 hit for every action that affects another user

# Verify module slugs match the registry above
grep -oE "module:\s+'[a-z]+'" app/Services/*.php | sort -u
```

### Checklist additions

- [ ] Every service method that mutates state calls `ActivityService::log()` at least once
- [ ] Every call has `entryType: 'log'` for the actor
- [ ] Every other affected party has a matching `entryType: 'notification'` call or `ActivityFanoutJob`
- [ ] Module slug matches the registry in Section 26
- [ ] Email trigger (`event(new XxxHappened())`) is present in the same service method (Section 25)
- [ ] For fan-outs > 3 recipients, `ActivityFanoutJob` is dispatched instead of looping `ActivityService::log()`
