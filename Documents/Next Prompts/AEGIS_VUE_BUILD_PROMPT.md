# Aegis — Vue Component Build & Wire (3-Phase Process)

## Step 0 — Setup

```bash
cd /home/claude
rm -rf aegis
git clone https://github.com/rehanurrashid/aegis.git aegis
cd aegis
git log -1 --oneline
git pull origin main

# Locate Laravel project root
LARAVEL_ROOT="Documents/aegis-laravel-migrations"

# Verify Laravel project exists
test -d $LARAVEL_ROOT/app || { echo "Laravel project missing"; exit 1; }
test -d $LARAVEL_ROOT/resources/js || { echo "Vue resources missing"; exit 1; }
```

Read the rules — these win every conflict in this order:
1. `AEGIS_VUE_RULES.md` — Vue patterns, wiring, anti-patterns
2. `Aegis_Desing_Prompt_Short.md` — design rules (visual)
3. `AEGIS-PROJECT-CONTEXT.md` — domain rules
4. `Aegis_Tone_Voice_Prompt.md` — copy tone

```bash
# Project knowledge files
# Search via project_knowledge_search:
# - AEGIS_VUE_RULES.md
# - Aegis_Desing_Prompt_Short.md
# - AEGIS-PROJECT-CONTEXT.md
# - Aegis_Tone_Voice_Prompt.md

# Read design system source files
cat _shared.css                  # full design system
cat _shared/icons.php            # all 206 valid icon names
cat _shared.js | head -200       # JS helpers Vue replaces

# Read the PHP source for this page (attached to chat)
cat [PHP_FILE_PATH]

# Read the existing Vue component being fixed (if it exists)
cat $LARAVEL_ROOT/resources/js/pages/[PORTAL]/[ComponentName].vue 2>/dev/null

# Read all global assets the component depends on
cat $LARAVEL_ROOT/resources/css/app.css
cat $LARAVEL_ROOT/resources/js/app.js
cat $LARAVEL_ROOT/resources/js/layouts/AppLayout.vue
cat $LARAVEL_ROOT/resources/js/layouts/AuthLayout.vue
for f in $LARAVEL_ROOT/resources/js/components/ui/*.vue; do echo "=== $f ===" && cat "$f"; done
for f in $LARAVEL_ROOT/resources/js/stores/*.js; do echo "=== $f ===" && cat "$f"; done
for f in $LARAVEL_ROOT/resources/js/composables/*.js; do echo "=== $f ===" && cat "$f"; done

# Read the backend wiring — this is the contract Vue must match
cat $LARAVEL_ROOT/routes/web.php
cat $LARAVEL_ROOT/app/Http/Controllers/[PORTAL]/[Controller].php
ls $LARAVEL_ROOT/app/Http/Requests/[Domain]/
ls $LARAVEL_ROOT/app/Services/
cat $LARAVEL_ROOT/app/Services/[RelevantService].php
ls $LARAVEL_ROOT/app/Policies/
cat $LARAVEL_ROOT/app/Policies/[RelevantPolicy].php
ls $LARAVEL_ROOT/app/Events/
ls $LARAVEL_ROOT/app/Models/
cat $LARAVEL_ROOT/app/Models/[RelevantModel].php
ls $LARAVEL_ROOT/database/migrations/
```

Confirm Step 0 by outputting:
- Latest commit SHA + last 5 commits
- Confirm AEGIS_VUE_RULES.md read ✅
- Confirm Aegis_Desing_Prompt_Short.md read ✅
- PHP source file confirmed read ✅
- Existing Vue component status: exists / missing
- Controller class name + file path
- Service class name + file path

---

## Mission

Achieve 100% PHP-to-Vue parity AND full backend wiring across three phases:

**PHASE 1** — Design & UI parity (visual, modals, all interactive elements)
**PHASE 2** — Frontend state & composables (stores, forms, navigation)
**PHASE 3** — Backend contract (controllers, services, routes, events, policies, migrations)

Each phase has its own gates. Do not advance to the next phase until the current phase passes all gates.

---

# PHASE 1 — DESIGN & UI PARITY

## 1.1 — Page Inventory (output before touching code)

```markdown
## Page: [filename].php → [ComponentName].vue

### Layout
- Layout: AppLayout | AuthLayout | PublicLayout
- Hero banner: eyebrow / title / subtitle / actions
- Stat chips: list each (icon, value source, label)

### Sections (every visual section in order)
1. [Section name] — [description]
2. [Section name] — [description]
...

### Modals
| Modal ID | Title | Trigger | Form fields | Multi-step? | Submit route |
|----------|-------|---------|-------------|-------------|--------------|

### Tabs
| Tab group | Tab keys | Default | Two-level? |
|-----------|----------|---------|-----------|

### Buttons & Links (every clickable element)
| Element | Action | Target |
|---------|--------|--------|
| "Create" btn | open modal | createModal |
| "View Activity" link | navigate | activity.index?module=plan |
| "Remove" icon-btn | confirm + delete | provider.items.destroy |

### Tooltips (every data-tooltip in PHP)
| Element | Tooltip text |
|---------|--------------|

### Icons (every aegis_icon() call)
| PHP call | Vue equivalent |
|----------|---------------|
| aegis_icon('shield', 16) | <AegisIcon name="shield" :size="16" /> |

### Conditionals
| PHP condition | Vue condition |
|--------------|---------------|

### Copy strings (every label/heading/placeholder)
List every text string. Vue text must match exactly.

### CSS overrides (every PHP <style> block rule)
| PHP rule | Notes |
|----------|-------|
```

## 1.2 — Phase 1 Diff (if Vue component exists)

Run the deep diff between PHP and existing Vue:

```markdown
### Section Diff
| # | PHP Section | In Vue | Status | Fix Action |
|---|------------|--------|--------|-----------|

### Modal Diff
| PHP Modal | In Vue | Trigger | Form | Route | Status |
|-----------|--------|---------|------|-------|--------|

### Icon Diff
Total aegis_icon() calls in PHP: N
Total <AegisIcon> in Vue: N
Raw <svg> tags in Vue (should be 0): N
Missing icons: [list]

### Tooltip Diff
Total data-tooltip in PHP: N
Total data-tooltip in Vue: N
title= attributes in Vue (should be 0): N

### CSS Class Diff
| PHP class | In Vue | Status |
|-----------|--------|--------|

### Spacing Diff
| PHP CSS rule | In Vue scoped | Status |
|-------------|---------------|--------|

### Copy Diff
| PHP text | Vue text | Match? |
|----------|----------|--------|

### Conditional Diff
| PHP condition | Vue condition | Status |
|---------------|---------------|--------|

### Route Diff
| PHP link/action | Vue route | Status |
|-----------------|-----------|--------|
```

## 1.3 — Phase 1 Fixes

Fix in priority order:
1. Missing sections (entire blocks absent)
2. Missing modals (functionality broken)
3. Raw `<svg>` → `<AegisIcon>` 
4. `title=` → `data-tooltip=`
5. Wrong CSS class names
6. Missing spacing rules (port PHP `<style>` block to `<style scoped>`)
7. Wrong button styles (port PHP scoped overrides)
8. Wrong copy strings
9. Missing conditionals
10. Hardcoded URLs → `route()`

## 1.4 — Phase 1 Gates

```markdown
### Gate 1.1 — Section completeness
Every PHP section present in Vue (count matches): ✅ / ❌

### Gate 1.2 — Modal completeness
PHP modal count: N
Vue AegisModal count: N
Every modal: trigger ✅, form ✅, route ✅, footer ✅

### Gate 1.3 — Icon system
grep "aegis_icon" PHP | wc -l → N
grep "<AegisIcon" VUE | wc -l → ≥ N
grep "<svg" VUE | grep -v "brand mark" → 0

### Gate 1.4 — Tooltip system
grep "data-tooltip" PHP | wc -l → N
grep "data-tooltip" VUE | wc -l → ≥ N
grep 'title=' VUE | grep -v "page title\|input title" → 0

### Gate 1.5 — Button system
grep "btn-gold" VUE → 0 (deprecated)
grep "btn-ghost" VUE on light backgrounds → flagged (use btn-outline)
btn-icon in table rows → size 14 ✅
modal close button uses .modal-close (not btn-icon) ✅
modal titles contain no <AegisIcon> ✅

### Gate 1.6 — Design rules
grep -i "tailwind\|tw-\|p-[0-9]\|m-[0-9]\| flex \| grid " VUE → 0
grep "#[0-9a-fA-F]\{3,6\}" VUE | grep -v "brand mark\|disc-red" → 0
grep "appearance: none" VUE → 0 (must be -webkit-appearance: none)
Stat chips OUTSIDE AegisHeroBanner ✅

### Gate 1.7 — Copy accuracy
Every label/button/placeholder matches PHP exactly ✅
No exclamation marks added ✅

### Gate 1.8 — Spacing accuracy
Every PHP <style> rule ported to <style scoped> ✅
All values use var(--token) ✅
```

Do not proceed to Phase 2 until all Phase 1 gates pass.

---

# PHASE 2 — FRONTEND STATE & WIRING

## 2.1 — Store Wiring Audit

```markdown
### Stores referenced
| Store | Purpose | Properties accessed | Methods called |
|-------|---------|--------------------|--------------------|
| useAuthStore | role/tier checks | isPractitioner, isAccessTier | — |
| useUiStore | modal/toast/confirm | activeModal | openModal, showToast |
| useIncidentStore | emergency banner | hasEmergency | — |

### Store updates needed
| Store | Change required | Why |
|-------|----------------|-----|
| auth.js | Add `isSomething` computed | new role-specific check needed for this page |
| pricing.js | Add tier feature `xxx` | new tier-gated feature on this page |
```

If a store needs a new property/method, list it. Update the store file in Phase 2 fixes.

## 2.2 — Composable Wiring Audit

```markdown
### Composables used
| Composable | Usage | Why |
|-----------|-------|-----|
| useForm | All write forms | Inertia form binding |
| useToast | Success/error feedback | After form submit |
| useConfirm | Destructive actions | Before delete |
| useActivity | timeAgo, formatMoney | Display formatting |

### Composables needing updates
| Composable | Change required | Why |
|-----------|----------------|-----|
| useActivity.js | Add `formatPercent(n)` | new % display on this page |
```

## 2.3 — Form Wiring Audit

For every form on the page:

```markdown
### Form: createForm
- Fields: title, description, amount_cents, incident_type
- HTTP: POST
- Named route: provider.items.store
- Money fields: amount_cents (integer cents — displayAmount watcher converts dollars)
- File fields: forceFormData: true required
- onSuccess: close modal + toast.success
- onError: toast.error
- Submit button: :disabled="form.processing" ✅
```

## 2.4 — Navigation Audit

```markdown
### Navigation events
| Element | Method | Route |
|---------|--------|-------|
| "Dashboard" link | <Link :href> | provider.dashboard |
| "View Plan" btn | router.visit | provider.plan.index |
| Filter change | router.visit (preserveState) | current route with query |

### Programmatic navigation
- All use route(): ✅
- No window.location.href: ✅
- No href to .php files: ✅
```

## 2.5 — Echo / Realtime Subscriptions

```markdown
### Realtime channels (if page subscribes)
| Channel | Event | Handler |
|---------|-------|---------|
| user.{id} | activity.created | notifications.events.unshift |
| incident.{id} | incident.update | incident.setActiveIncident |
```

## 2.6 — Phase 2 Fixes

Apply in this order:
1. Update store files (auth.js, ui.js, etc.) with new properties/methods
2. Update composables (useActivity.js, etc.) with new helpers
3. Wire every form to `useForm()` with correct route + handlers
4. Wire every navigation to `router.visit(route())` or `<Link :href="route()">`
5. Add Echo subscriptions if needed (in `onMounted`)

## 2.7 — Phase 2 Gates

```markdown
### Gate 2.1 — All stores correctly imported and used
Every store accessed via use*Store() ✅
No direct $page.props.X where store equivalent exists ✅

### Gate 2.2 — All composables correctly imported and used
useToast for all user feedback ✅
useConfirm for all destructive actions ✅
useActivity for all timeAgo/formatMoney calls ✅

### Gate 2.3 — All forms wired
Every write action has useForm() ✅
Every form has onSuccess + onError ✅
Every money field is integer cents ✅
Every submit btn has :disabled="form.processing" ✅

### Gate 2.4 — All navigation wired
grep "window.location" VUE → 0
grep "href=.*\.php" VUE → 0
All <a :href> use route() or <Link :href="route()" /> ✅

### Gate 2.5 — All Echo subscriptions clean up on unmount
onUnmounted leaves no listeners hanging ✅
```

Do not proceed to Phase 3 until all Phase 2 gates pass.

---

# PHASE 3 — BACKEND CONTRACT

## 3.1 — Backend Contract Audit

For each Vue page, produce the contract table:

```markdown
### Component: [Portal]/[PageName].vue
### Controller: App\Http\Controllers\[Portal]\[Controller]
### Service: App\Services\[Service]
### Policy: App\Policies\[Policy]

### GET route (page load)
| Route | Controller method | Inertia page | Returns props |
|-------|-------------------|--------------|--------------|
| GET /provider/vault | VaultController::index | Provider/Vault | zones, planStatus, attestedAt, sealedCount |

### Write routes (form submits + actions)
| Modal/Action | HTTP | Named route | Controller method | FormRequest | Service method | Policy method | Event fired |
|--------------|------|-------------|-------------------|-------------|---------------|---------------|------------|
| uploadModal | POST | provider.vault.upload | upload | UploadVaultItemRequest | VaultService::upload | VaultPolicy::upload | (none) |
| attestVault | POST | provider.vault.attest | attest | AttestVaultRequest | PlanService::attestVault | VaultPolicy::attest | VaultAttested |
| delete | DELETE | provider.vault.destroy | destroy | — | VaultService::delete | VaultPolicy::delete | (none) |

### Required FormRequest validation rules
| FormRequest | Rules |
|-------------|-------|
| UploadVaultItemRequest | zone, title, description, file (max 51200), access_level |

### Policy methods that must exist
| Policy | Method | Logic |
|--------|--------|-------|
| VaultPolicy | upload($user, $plan) | $user owns plan AND plan signed |

### Events that must be defined
| Event | Properties | ShouldBroadcast? |
|-------|-----------|------------------|
| VaultAttested | ContinuityPlan, User | No |

### Activity events (ActivityService::log)
| Trigger | Module | Severity | Description |
|---------|--------|----------|-------------|
| Vault upload | vault | info | "Uploaded {title} to {zone}" |
| Vault attest | attestation | info | "Attested vault contents" |

### Database fields the Service writes/reads
| Table | Columns |
|-------|---------|
| vault_items | id, plan_id, zone, title, file_path, access_level, release_on_incident |
```

## 3.2 — Backend Gap Report

Compare the Vue page's needs against what exists in the Laravel codebase:

```markdown
### Backend Gaps Found
| Item | Status | Action |
|------|--------|--------|
| route 'provider.vault.upload' | exists ✅ | — |
| VaultController::upload() | exists ✅ | — |
| VaultService::upload() | MISSING ❌ | Generate method |
| UploadVaultItemRequest | exists ✅ | — |
| VaultPolicy::upload() | WRONG ⚠️ | Fix logic — current allows when plan unsigned |
| VaultAttested event | MISSING ❌ | Generate event class |
| ActivityFanoutListener resolve('VaultAttested') | MISSING ❌ | Add match case |
| vault_items.release_on_incident column | exists ✅ | — |
| HandleInertiaRequests::share() includes hasEmergency | exists ✅ | — |
```

## 3.3 — Backend Fixes

Generate or surgically fix each backend item. ALWAYS use this order:
1. **Migrations** — add/fix columns if needed
2. **Models** — update fillable, casts, relationships
3. **Enums** — add missing values if needed
4. **Policies** — fix authorization logic
5. **FormRequests** — fix validation rules
6. **Services** — add/fix business logic methods
7. **Events** — generate missing event classes
8. **Listeners** — add event handlers (ActivityFanoutListener match case, etc.)
9. **Jobs** — generate background jobs if needed
10. **Controllers** — wire to correct Service method + Policy authorize
11. **Routes** — add to web.php with correct middleware

For every backend file change:
- `declare(strict_types=1)` at top
- `php -l` syntax check passes
- Correct namespace + class name
- No methods from other files leaked in

## 3.4 — Phase 3 Gates

```markdown
### Gate 3.1 — Route contract
Every Vue route('x.y.z') has a matching named route in web.php ✅
Every named route has middleware: ['auth', role_check] applied ✅

### Gate 3.2 — Controller contract
Every controller method:
- Authorizes via Policy first ✅
- Calls one Service method ✅
- Returns Inertia::render() (GET) or back()->with() (write) ✅
- No business logic inline ✅

### Gate 3.3 — Service contract
Every Service method:
- Has correct typed parameters ✅
- Money params are int $amountCents (not float) ✅
- Calls ActivityService::log() for cross-portal actions ✅
- Fires events from service (not controller) ✅

### Gate 3.4 — FormRequest contract
Every FormRequest:
- authorize() returns true (Policy handles real auth) ✅
- rules() uses Enum::class for enum fields ✅
- Money fields are integer|min:0 ✅
- File fields have mimes + max size ✅

### Gate 3.5 — Policy contract
Every protected action has Policy method ✅
Policy reads from migration columns exactly (locked_at not is_locked) ✅
AdminPolicy::before() exists ✅

### Gate 3.6 — Event contract
Every Event class has typed constructor params ✅
Broadcast events implement ShouldBroadcast ✅
Every event has a match case in ActivityFanoutListener::resolve() ✅

### Gate 3.7 — Activity logging
Every cross-portal write logs via ActivityService::log() ✅
Activity module + severity match the spec ✅

### Gate 3.8 — Database contract
Every column referenced by Vue/Service exists in migrations ✅
Every Enum value exists in the corresponding migration ENUM ✅
Every FK column has a foreign key constraint ✅

### Gate 3.9 — Email wiring (backend ↔ template ↔ frontend)
Validate that email functionality is connected end-to-end for every action on this page that should notify a user.

**Backend dispatch → template resolution**
- Every email this page can trigger fires from a Service (not a Controller), the same way other events fire ✅
- Each fired event has a resolve() arm in `SendEmailNotificationListener` (or a direct `SendEmailJob::dispatch` for ungated/email-only sends) ✅
- Every dispatched template name resolves to an existing Blade view under `resources/views/emails/`:
  `grep -rhoE "'emails\.[a-z0-9_]+\.[a-zA-Z0-9_-]+'" app/ | sort -u` → each maps to a file (0 missing) ✅
- No orphan: if this page performs an action that has a matching email template, that template is wired to a trigger (not left dangling) ✅

**Data + gating**
- The dispatch payload supplies every merge field the template reads, or the template degrades safely with `?? ''` fallbacks (no undefined-variable render) ✅
- The `notify_*` gate key passed to `shouldSend()` is one that is seeded (`AuthService::register()` + `UserMetaSeeder` + backfill migration) so the preference is real, not fail-open ✅
- Ungated alerts (e.g. critical-incident) bypass `notify_*` by design and are routed through the ungated listener/path ✅

**Frontend parity**
- Any in-app notification-preference UI exposes exactly the `notify_*` gate keys the backend checks (no orphaned toggles, no missing ones) ✅
- Any email-template preview/management screen mirrors the Blade template's subject + copy + tokens, and obeys Vue rules (`<AegisIcon>`, `data-tooltip`, CSS vars — no raw `<svg>`, no `title=`, no hardcoded hex) ✅
- Unsubscribe / verification links in templates point at real named routes (`email.unsubscribe`, `verification.verify`) ✅

**Lint**
- `php -l` passes on every touched event/listener/service ✅
- All Blade email templates parse (no leftover `<?php`, balanced `@if/@endif`, head+foot includes present) ✅
```

---

## Final Deliverable

```bash
# Single combined ZIP with all changes from all 3 phases
zip -r /mnt/user-data/outputs/aegis_[component]_full_wire.zip \
  Documents/aegis-laravel-migrations/resources/js/pages/[portal]/[Component].vue \
  Documents/aegis-laravel-migrations/resources/js/stores/[any-updated].js \
  Documents/aegis-laravel-migrations/resources/js/composables/[any-updated].js \
  Documents/aegis-laravel-migrations/resources/css/app.css \
  Documents/aegis-laravel-migrations/app/Http/Controllers/[Portal]/[Controller].php \
  Documents/aegis-laravel-migrations/app/Http/Requests/[Domain]/[Request].php \
  Documents/aegis-laravel-migrations/app/Services/[Service].php \
  Documents/aegis-laravel-migrations/app/Policies/[Policy].php \
  Documents/aegis-laravel-migrations/app/Events/[Domain]/[Event].php \
  Documents/aegis-laravel-migrations/app/Listeners/ActivityFanoutListener.php \
  Documents/aegis-laravel-migrations/app/Models/[Model].php \
  Documents/aegis-laravel-migrations/database/migrations/[any-new].php \
  Documents/aegis-laravel-migrations/routes/web.php

echo "=== Files included ==="
unzip -l /mnt/user-data/outputs/aegis_[component]_full_wire.zip
```

After delivery, output the final summary:

```markdown
## Build Summary

### Phase 1 — Design & UI
✅ Sections complete: N/N
✅ Modals complete: N/N
✅ Icons converted: N/N (0 raw svg)
✅ Tooltips converted: N/N (0 title= remaining)
✅ Copy matches PHP exactly
✅ Spacing rules ported from PHP <style> block

### Phase 2 — Frontend State
✅ Stores updated: [list]
✅ Composables updated: [list]
✅ Forms wired: N
✅ Navigation wired: N hyperlinks, N programmatic
✅ Echo subscriptions: N

### Phase 3 — Backend Contract
✅ Routes added/fixed: N
✅ Controller methods: N (all authorize + call Service)
✅ Services added/fixed: N
✅ FormRequests added/fixed: N
✅ Policies added/fixed: N
✅ Events generated: N
✅ Listeners updated: N
✅ Migrations added: N
✅ Models updated: N
✅ Email wiring validated: events → listener → template resolve (0 missing), gate keys seeded

### All Gates
Phase 1: 8/8 ✅
Phase 2: 5/5 ✅
Phase 3: 9/9 ✅

### Known Limitations
- [list any intentional gaps]
```

---

## How to Use This Prompt

1. Start a new chat with this prompt
2. Attach:
   - The **PHP source file** (e.g. `dashboard.php`)
   - Optionally, the **existing Vue component** (if iterating) — OR Claude will read it from the repo
3. Specify:
   - **Portal:** provider / cs / ss / bp / admin / auth / public / shared
   - **Component name:** e.g. `Dashboard.vue`
   - **Controller:** e.g. `Provider\DashboardController`
4. Claude will:
   - Clone fresh repo + read all rules
   - Run Phase 1 (output diff + fix UI/design)
   - Run Phase 2 (audit + fix state/wiring)
   - Run Phase 3 (audit + fix backend)
   - Run all 22 gates
   - Deliver one ZIP with every file changed across all 3 phases

---

## Start Here

After Step 0 confirm:

```markdown
1. Commit SHA: ___
2. Last 5 commits: ___
3. AEGIS_VUE_RULES.md: read ✅
4. Aegis_Desing_Prompt_Short.md: read ✅
5. AEGIS-PROJECT-CONTEXT.md: read ✅
6. PHP source: [filename] read ✅
7. Existing Vue: exists / missing — path
8. Controller: [class name] — path
9. Service: [class name] — path
10. Portal: [provider/cs/ss/bp/admin/auth/public/shared]
```

Then:
- Output **Phase 1 inventory + diff**
- Wait for confirmation (or proceed if no issues)
- Apply Phase 1 fixes
- Run Phase 1 gates
- Output **Phase 2 audit + gaps**
- Apply Phase 2 fixes
- Run Phase 2 gates
- Output **Phase 3 contract + gaps**
- Apply Phase 3 fixes
- Run Phase 3 gates
- Deliver combined ZIP + final summary

No phase advances until its gates pass. Output gate results explicitly.
