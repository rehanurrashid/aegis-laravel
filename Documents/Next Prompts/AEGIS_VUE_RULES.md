# AEGIS_VUE_RULES.md
 
**Vue component rules for the Aegis Laravel migration.** Companion to `Aegis_Desing_Prompt_Short.md` (design rules) and `AEGIS-PROJECT-CONTEXT.md` (domain rules).
 
`Aegis_Desing_Prompt_Short.md` wins on design conflicts. This file wins on Vue/Inertia/state/wiring conflicts.
 
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
 
async function remove(item) {
    const ok = await confirmAction(`Remove "${item.name}"?`, {
        title: 'Confirm Removal', confirmLabel: 'Remove', destructive: true,
    })
    if (!ok) return
    router.delete(route('provider.items.destroy', item.id), {
        onSuccess: () => toast.success('Removed.'),
    })
}
</script>
```
 
---
 
## SECTION 4 — DESIGN RULES (FROM Aegis_Desing_Prompt_Short.md)
 
Every Vue component follows these. `Aegis_Desing_Prompt_Short.md` is the source of truth — this is the quick-reference checklist.
 
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
```js
const { confirmAction } = useConfirm()
 
async function remove(item) {
    const ok = await confirmAction('Are you sure?', {
        title:        'Confirm',
        confirmLabel: 'Remove',
        destructive:  true,
    })
    if (!ok) return
    // ...
}
```
 
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
 
Adapted from `CENTRALIZED-SYSTEM.md` — every Vue component obeys these.
 
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
 
## SECTION 11 — TAB PATTERN
 
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
 
## SECTION 12 — MULTI-STEP MODAL PATTERN
 
```vue
<AegisModal v-model="modals.wizard" title="Designate Steward" size="lg">
 
  <!-- Steps row between header and body -->
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
 
  <div v-show="currentStep === 0">...step 1 form...</div>
  <div v-show="currentStep === 1">...step 2 form...</div>
  <div v-show="currentStep === 2">...step 3 form...</div>
 
  <template #footer>
    <button v-if="currentStep > 0" class="btn btn-outline" @click="currentStep--">Back</button>
    <button v-if="currentStep < steps.length - 1"
            class="btn btn-primary"
            @click="currentStep++">Continue</button>
    <button v-else
            class="btn btn-primary"
            @click="submit"
            :disabled="form.processing">
      {{ form.processing ? 'Submitting...' : 'Submit' }}
    </button>
  </template>
 
</AegisModal>
 
<style scoped>
.modal-steps + .modal-body { padding-top: 2px; }
</style>
```
 
---
 
## SECTION 13 — UPLOAD / DROPZONE PATTERN
 
```vue
<template>
  <AegisDropzone
    v-model="form.file"
    accept=".pdf,.docx,.jpg,.png"
    hint="PDF, DOCX, JPG, PNG up to 10 MB"
  />
  <div v-if="form.errors.file" class="form-error">{{ form.errors.file }}</div>
</template>
 
<script setup>
const form = useForm({ file: null, zone: '', title: '' })
 
function submit() {
    form.post(route('provider.vault.upload'), {
        forceFormData: true,    // required for file uploads
        onSuccess: () => { modals.upload = false; toast.success('Uploaded.') },
    })
}
</script>
```
 
---
 
## SECTION 14 — ANTI-PATTERN HALL OF SHAME
 
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
| `confirm('Sure?')` | `await confirmAction('Sure?')` |
| `<svg><use href="..."/></svg>` | `<AegisIcon name="..." />` |
| `style.display = 'flex'` for modal | `<AegisModal v-model="modals.x">` |
| Page-local CSS `.btn-primary { ... }` overriding global | Scoped `.page-x .btn-primary { ... }` |
| `class="btn btn-outline btn-icon"` chain | `class="btn-icon"` alone |
| `btn-icon` with icon size 13 in a table row | `btn-icon` with icon size 14 |
 
---
 
## SECTION 15 — CSS CLASS REFERENCE
 
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
`.modal-overlay` · `.modal` · `.modal-sm` · `.modal-md` · `.modal-lg` · `.modal-xl` · `.modal-header` · `.modal-title` · `.modal-close` · `.modal-body` · `.modal-footer` · `.modal-steps` · `.modal-step` · `.modal-step-num` · `.modal-step-divider`
 
### Activity feed
`.activity-feed` · `.activity-item` · `.activity-item--info` · `.activity-item--warning` · `.activity-item--critical` · `.activity-item-icon` · `.activity-item-title` · `.activity-item-desc` · `.activity-item-time`
 
### Empty state
`.empty-state` · `.empty-state-icon` · `.empty-state-title` · `.empty-state-sub`
 
### Avatars
`.avatar` · `.avatar-gold` · `.avatar-dark` · `.avatar-red` · `.avatar-xs` · `.avatar-sm` · `.avatar-md` · `.avatar-lg` · `.avatar-xl` · `.avatar-2xl`
 
### Utility
`.text-muted` · `.text-danger` · `.text-success` · `.divider` · `.sr-only`
 
---
 
## SECTION 16 — CSS TOKEN REFERENCE
 
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
 
## SECTION 17 — FILE NAMING & LOCATION
 
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
 
## SECTION 18 — VERIFICATION CHECKLIST
 
Before declaring any component "done":
 
```markdown
- [ ] Every PHP section is in Vue
- [ ] Every PHP modal is in Vue (correct trigger, form, route)
- [ ] Every `aegis_icon()` is `<AegisIcon>` — no raw `<svg>`
- [ ] Every `data-tooltip` from PHP is in Vue — no `title=`
- [ ] Every form uses `useForm()` with onSuccess + onError
- [ ] Every submit button has `:disabled="form.processing"`
- [ ] Every link uses `route()` — no hardcoded URLs
- [ ] Every condition uses store getters (auth.isAccessTier, incident.hasEmergency)
- [ ] Every PHP `<style>` rule is in `<style scoped>` with var(--token) values
- [ ] No Tailwind classes
- [ ] No bare hex colors (except brand SVGs with comment)
- [ ] No icons inside modal titles
- [ ] Stat chips outside `<AegisHeroBanner>`
- [ ] All money fields are integer cents
- [ ] All write routes match the controller exactly
- [ ] All props match what the controller returns