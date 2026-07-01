# AEGIS_VUE_BUILD_PROMPT.md

**Convert a single PHP page into a working Vue 3 + Inertia component, fully wired to the existing Laravel backend.**

---

## What you receive

1. The legacy PHP file (attached) — design + UX is **100% final**. Replicate exactly.
2. A fresh clone of `github.com/rehanurrashid/aegis.git` — the live Laravel/Vue codebase.
3. `AEGIS_VUE_RULES.md` — the only rulebook. If anything else in your context says different, **this file wins**.

That's the entire input set. No other docs are needed. Do not look for or reference `AEGIS-PROJECT-CONTEXT.md`, `CENTRALIZED-SYSTEM.md`, `Aegis_Desing_Prompt_Short.md`, or any other legacy document — `AEGIS_VUE_RULES.md` has absorbed everything you need.

---

## How to operate

### Surgical, not generative
- The existing Vue file (if any) is the working base. **Never regenerate from scratch.**
- Every fix is a `str_replace` on the file. Find the wrong lines, change only those.
- Working code stays untouched. New sections are inserted at the correct position.

### Verify before writing
Before writing a single line of Vue, read:
1. The full PHP file (design ground truth)
2. The existing Vue file (if any — your starting point)
3. The controller method (props returned to Inertia)
4. The FormRequest (field names + validation rules)
5. The route definition (HTTP verb + name)
6. The migration (column names + types)
7. The service method signature (what the controller calls)

Write nothing until all seven reads are complete.

### Backend gaps get filled
Design from PHP is final. Backend has ~1–2% gaps — fill them as you go:
- Service method missing → write it
- FormRequest column mismatch → fix it
- Route verb wrong → correct it
- Migration column missing → add a migration
- Controller not passing a needed prop → add it

Backend changes are part of the deliverable.

---

## Step 0 — Setup

```bash
cd /home/claude && rm -rf aegis
git clone https://github.com/rehanurrashid/aegis.git
cd aegis && git log -1 --oneline

LARAVEL_ROOT="Documents/aegis-laravel-migrations"
PHP_PAGE="[PATH/TO/PAGE.php]"            # attached legacy file
VUE_PAGE="$LARAVEL_ROOT/resources/js/pages/[portal]/[Page].vue"

# Read everything (no skimming)
cat $PHP_PAGE                            # design ground truth
cat $VUE_PAGE 2>/dev/null                # working base (if exists)
cat $LARAVEL_ROOT/app/Http/Controllers/[Portal]/[PageController].php
cat $LARAVEL_ROOT/app/Http/Requests/[Portal]/*.php 2>/dev/null
grep -B1 -A5 "[page-route-fragment]" $LARAVEL_ROOT/routes/web.php
cat $LARAVEL_ROOT/app/Services/[Service].php 2>/dev/null
ls $LARAVEL_ROOT/database/migrations/ | grep -E "[relevant_table]"
cat $LARAVEL_ROOT/database/migrations/*[relevant_table]*

# Verify globals (don't import what's already global)
grep "globalProperties\|.component(" $LARAVEL_ROOT/resources/js/app.js
```

---

## Step 1 — Inventory the PHP page

Output this table before touching the Vue file. No code yet.

```markdown
## Inventory — [PageName].php

### Sections (in document order)
| # | Section | Wrapper class | What it shows | Conditional? |
|---|---------|--------------|--------------|--------------| 

### Modals
| ID | Title | Trigger | Fields | Submit route + verb | FormRequest |
|----|-------|---------|--------|--------------------|-------------|

### Buttons & links (every clickable element)
| Element | Label | Action | Vue equivalent |
|---------|-------|--------|----------------|

### Dynamic class bindings (every PHP ternary in `class=""`)
| PHP expression | Vue `:class` |
|---|---|

### Inline styles (every `style="…"`)
| PHP element | Style | Static/dynamic? |
|---|---|---|

### Empty / disabled / loading states
| PHP pattern | Condition | Vue equivalent |
|---|---|---|

### `<style>` block rules
| PHP rule | Goes to `<style scoped>` |
|---|---|

### Counts (your completion targets)
- `aegis_icon()`     N    → must equal N `<AegisIcon>` in Vue
- `data-tooltip`     N    → must equal N in Vue
- `openModal()`      N    → must equal N modal triggers
- modal divs         N    → must equal N `<AegisModal>`
- `<form>` submits   N    → must equal N `useForm()` instances
- `if`/`empty()`     N    → must equal N `v-if`/`v-show`
- `<input type="file">` N → must equal N `<AegisDropzone>`

### Inertia contract
| Prop | Type | Controller returns it? |
|------|------|------------------------|

### Banned content sniff (must be ZERO of each)
| Pattern | Found? |
|---------|--------|
| `Robert Miller`, `John D.`, `Sarah M.` (fake names) | 0 |
| `NPI-2024`, `CA-MD-67890` (fake IDs) | 0 |
| `$12,450`, `$8,900` (hardcoded amounts) | 0 |
| `Lorem ipsum` | 0 |
| `example.com`, `test@test.com` | 0 |
```

Confirm the inventory matches the PHP file. Then proceed to Step 2.

---

## Step 2 — Diff the existing Vue against the inventory

If a Vue file exists, compare it section-by-section against the inventory:

```markdown
## Gap Report

### Missing sections
- [list any section in PHP not in Vue]

### Wrong DOM nesting
- [list any wrapper hierarchy that doesn't match]

### Missing modals
- [list]

### Missing buttons / wrong actions
- [list]

### Missing dynamic class bindings
- [list]

### Missing empty / loading states
- [list]

### Banned patterns present in Vue
- raw `<svg>`:                count
- `title=`:                   count
- `modal-id=`:                count
- `ui.openModal`:             count
- `@file-selected`:           count
- `<input type="file">`:      count
- hardcoded fake data:        count
- Tailwind classes:           count
- bare hex colors:            count
- `</script>` missing:        yes/no
```

If no Vue file exists, skip this step.

---

## Step 3 — Apply surgical fixes

For each gap, identify the exact insertion point and use `str_replace`. Order:

1. **Backend first** — fix FormRequest field names, route verbs, migration columns, controller props. Frontend depends on these being right.
2. **Imports** — add any missing local imports (`AegisDropzone`, custom components).
3. **Script** — add refs, useForm declarations, Vuelidate rules, methods.
4. **Template** — add missing sections, modals, buttons in correct DOM position.
5. **Scoped styles** — port every rule from PHP `<style>` block using `var(--token)` values only.

Never delete working sections to make room. Insert beside them.

---

## Step 4 — Mental render verification

Walk the finished Vue file top-to-bottom and answer for each section:

> Does the browser draw what the PHP draws?

Compare against the inventory tables. Any "no" → fix before gates.

---

## Step 5 — Pre-flight gates (every gate must pass)

```bash
PAGE=$VUE_PAGE

# Banned patterns
grep -c "modal-id=" $PAGE                       # → 0
grep -c "ui.openModal\|ui.closeModal" $PAGE     # → 0
grep -c "@file-selected" $PAGE                  # → 0
grep -c "<input type=\"file\"" $PAGE            # → 0
grep -c "<svg" $PAGE                            # → 0
grep -c 'title="' $PAGE                         # → 0
grep -E "Robert Miller|John D\.|NPI-2024|Lorem ipsum" $PAGE   # → no output
grep -E "class=\"[^\"]*\b(flex|grid|p-[0-9]|m-[0-9]|text-sm)" $PAGE  # → no output

# SFC structure
grep -c "<script setup>" $PAGE      # → 1
grep -c "</script>"     $PAGE       # → 1
grep -c "<template>"    $PAGE       # → 1
grep -c "</template>"   $PAGE       # → 1

# Modal pairing
MODALS=$(grep -c "<AegisModal" $PAGE)
VMODELS=$(grep -c "v-model=\"modals\." $PAGE)
test $MODALS -eq $VMODELS && echo "✅ modal/vmodel match" || echo "❌ $MODALS vs $VMODELS"

# AegisDropzone import (if used)
if grep -q "<AegisDropzone" $PAGE; then
    grep -c "import AegisDropzone" $PAGE  # → > 0
fi

# Completion counts (must match inventory)
echo "aegis_icon → $(grep -c '<AegisIcon' $PAGE)"
echo "tooltips   → $(grep -c 'data-tooltip' $PAGE)"
echo "modals     → $MODALS"
echo "useForm    → $(grep -c 'useForm(' $PAGE)"

# New gates (applied to every page)
grep -c "border:\s*1\.5px\|border:\s*2px\|border-width:\s*1\.5px\|border-width:\s*2px" $PAGE  # → 0 (1px borders only)
grep -c "new Date\|\.toLocaleDateString\|DatePicker\|v-date" $PAGE                            # → 0 (no manual date formatting)
grep -c "VueSelect\|vue-select\|AegisSelect\|<v-select\|<VSelect" $PAGE                       # → 0 (no custom select components)

# Hero banner must be quiet
HERO_COUNT=$(grep -c "<AegisHeroBanner" $PAGE || echo 0)
QUIET_COUNT=$(grep -c "<AegisHeroBanner quiet\|<AegisHeroBanner[^>]* quiet" $PAGE || echo 0)
[ "$HERO_COUNT" = "0" ] || [ "$HERO_COUNT" = "$QUIET_COUNT" ] && echo "✅ hero is quiet" || echo "❌ hero banner missing quiet prop"

# Multi-step modal plumbing
if grep -q "step\b" $PAGE; then
  STEP_HELPER=$(grep -c "firstInvalidStep\|jumpToFieldStep" $PAGE)
  [ "$STEP_HELPER" -gt 0 ] && echo "✅ step-jump helpers present" || echo "❌ missing firstInvalidStep/jumpToFieldStep"
fi

# Enum unwrapping — val() helper must exist whenever model fields are compared
if grep -qE "\.status\s*===|\.role\s*===|\.type\s*===" $PAGE; then
  grep -c "const val\s*=" $PAGE && echo "✅ val() helper present" || echo "❌ missing val() enum unwrapper"
fi

# Validation messages — no bare validators without withMessage
grep -E ":\s*\{\s*required\s*\}|,\s*required\s*[,}]" $PAGE | grep -v "withMessage" && echo "❌ bare required" || echo "✅ no bare required"

# Status-change actions must go through confirmAction — no direct router writes on @click
grep -c "@click=\"router\.post\|@click=\"router\.delete\|@click=\"router\.put\|@click=\"setStatus" $PAGE  # → 0

# Clickable cards must have cursor:pointer
if grep -qE "jp-card|jp-kanban-card|jp-my-row|jp-app-row|clickable" $PAGE; then
  grep -c "cursor: pointer" $PAGE && echo "✅ cursor:pointer present" || echo "❌ missing cursor:pointer on clickable cards"
fi

# Pagination — AegisPagination must be locally imported
if grep -q "AegisPagination" $PAGE; then
  grep -c "import AegisPagination" $PAGE && echo "✅ AegisPagination imported" || echo "❌ AegisPagination not imported locally"
fi

# No coloured icon backgrounds
grep -E "blue-light|purple-light|orange-light|green-light|red-light" $PAGE && echo "❌ coloured icon backgrounds found" || echo "✅ no coloured icon backgrounds"

# ID-only refs — active selection patterns should not store full objects
grep -E "activeProposal\s*=\s*ref\([\{]|activeContract\s*=\s*ref\([\{]|activeItem\s*=\s*ref\([\{]" $PAGE && echo "❌ full object stored in ref — use ID + computed" || echo "✅ no full-object refs detected"
```

Every gate passes → done.
Any gate fails → fix the specific issue, re-run gates.

---

## Step 6 — Deliver

```bash
zip -r /mnt/user-data/outputs/aegis_[page]_done.zip \
  $VUE_PAGE \
  $LARAVEL_ROOT/app/Http/Controllers/[Portal]/[PageController].php \
  $LARAVEL_ROOT/app/Http/Requests/[Portal]/*.php \
  $LARAVEL_ROOT/app/Services/[Service].php \
  $LARAVEL_ROOT/routes/web.php \
  2>/dev/null
```

Include ONLY files that were changed. No unchanged files in the ZIP.

Output a build summary:

```markdown
## Build Summary

### Files changed
- [list]

### Gates passed
- [12/12]

### Counts (Inventory → Vue)
- icons:    N → N ✅
- tooltips: N → N ✅
- modals:   N → N ✅
- forms:    N → N ✅

### Backend changes
- [list any FormRequest, route, migration, controller, service fix]
```

---

## Hard rules (non-negotiable)

These come from `AEGIS_VUE_RULES.md`. Apply them throughout. Highlights:

- **Surgical edits only — never rewrite from scratch.**
- **No raw `<svg>`** → `<AegisIcon name="x" :size="N" />`
- **No `title=`** → `data-tooltip="..."`
- **No `modal-id=`, no `ui.openModal()`** → `v-model="modals.xxx"` on `AegisModal`
- **No `<input type="file">`, no `@file-selected`** → `<AegisDropzone @files="form.x = $event[0]" />` with local import
- **No hardcoded fake data** — every value from controller props
- **No Tailwind, no bare hex colors, no `!important`**
- **Field names match FormRequest exactly**
- **HTTP verb matches route exactly**
- **Column names match migration exactly**
- **Short form fields side-by-side in `.form-row`** (selects, dates, numbers); only long fields full-width
- **Multi-step modals: `size="xl"` or `size="fullscreen"`** — never `md`/`lg`
- **Every form uses `useForm()` + Vuelidate + `:disabled="form.processing"`**
- **Every list/table has an empty state** (`<AegisEmptyState>` when `!items.length`)
- **Every `<AegisIcon>` next to text** sits inside a flex/inline-flex parent with `align-items: center` + `gap`
- **Every write action that changes data must trigger the correct email** — check `resources/views/emails/` for the matching template, verify the Service dispatches the event, verify the Listener sends the email via `SendEmailJob`. No silent saves.
- **Every write action must log to `ActivityService::log()`** — with `entry_type: 'log'` for the actor's own record and `entry_type: 'notification'` for every other affected party. Notifications appear in the recipient's `Activity.vue` under the Notifications tab. If a write action affects more than one other user, dispatch `ActivityFanoutJob` instead of looping.
- **Hero banners are always quiet** — every `<AegisHeroBanner>` gets the `quiet` prop. Never use the loud/default hero.
- **Multi-step modals validate-then-jump** — on any validation failure (client or server), jump to the *specific step* containing the first invalid field. Build `stepFields` map + `firstInvalidStep()` + `jumpToFieldStep()`. Apply to every multi-step modal.
- **All borders are `1px`** — never `1.5px` or `2px` anywhere (shared CSS, app.css, or scoped styles).
- **Real avatars, not just initials** — any element representing a specific person/company must render their real photo when available, fallback to initials. Canonical pattern:
  ```vue
  <div class="avatar" :style="user?.avatar_url ? { backgroundImage: `url(${user.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}">
    <template v-if="!user?.avatar_url">{{ initials }}</template>
  </div>
  ```
  Controller must eager-load `avatar_path` (not just `avatar_initials`) on any relation whose avatar is rendered.
- **Person/company names link to their public profile** — any primary name display for a specific user/company must be a real `<Link>` to their public profile route (`public.provider` / `public.cs` / `public.ss` / `public.bp`, keyed by `slug`), opened `target="_blank"`. Controller must eager-load `slug`. Unlinked plain text for a named person/company is a violation.
- **Activity links use the sidebar's exact route** — never link to a bare/duplicate route (e.g. `activity.index`) when the portal sidebar uses a different prefixed name (e.g. `provider.activity`). Check `AppSidebar.vue`'s nav config before wiring any link to Activity/Messages. Always append `?event_type=<slug>` (not `?module=`) to pre-filter the Activity page.
- **One contract per accept — verify idempotency** — any action that creates a durable record from a reviewable item must: (1) check for an existing record first at the service layer, (2) have a DB-level unique constraint as a backstop. Never rely on UI guards alone.
- **Enum fields — always unwrap with `val()`** — backed enums from Inertia may arrive as `{value:'x'}` objects or plain strings. Define `const val = (v) => (v && typeof v === 'object' && 'value' in v) ? v.value : (v ?? '')` and use it for every model field comparison. Never write `item.status === 'open'` directly.
- **Store only IDs in refs, never full objects** — when a ref holds a "currently selected" record that can be mutated by a server reload, store only the ID (`const _activeId = ref(null)`) and derive the live object via `computed(() => list.value.find(x => x.id === _activeId.value))`. Prevents stale data showing in modals after Inertia reloads.
- **Backend `groupBy()` → chain `.map(fn => group.values())`** — Laravel Collection `groupBy()` returns nested Collections. Inertia serialises these as objects with numeric keys in some versions. Always force plain arrays: `->groupBy('key')->map(fn($g) => $g->values())`.
- **Grouped/nullable Inertia props → safe defaults + optional chaining** — props like `milestonesByContract`, `proposalsByJob` must have `default: () => ({})` in `defineProps` and be accessed as `prop?.[key] ?? []` in the template.
- **All status-change actions require `confirmAction()`** — never fire a state mutation directly from `@click`. Destructive actions (pause, delete, cancel, reject, end) → `destructive: true` (red button). Positive actions (publish, resume, reopen, approve) → `destructive: false` (green button).
- **Closed/filled/cancelled records render read-only, not edit forms** — compute `isClosed = ['filled','closed','cancelled'].includes(val(status))` and branch the entire modal template. Show a status alert, a summary of key fields, and a Reopen button where appropriate.
- **Terminal modal states hide all action buttons** — hired/completed/declined records show only a status badge in the footer. No action buttons, no stage-advance controls.
- **Pagination thresholds** — primary tables (postings, records): 5 per page. Secondary tables (applications, lists): 8 per page. Use `AegisPagination` (local import). Client-side only — slice the already-loaded Inertia prop. Reset page to 1 via `watch` on every filter change.
- **Validation messages must be field-specific** — wrap every vuelidate rule with `helpers.withMessage('Field name is required.', validator)`. Never ship bare `required`, `minLength`, `maxLength`. Messages must name the field: `"Request title is required."` not `"Value is required."`.
- **Error above hint** — when a field has both a `div.form-error` and a `div.form-hint` (e.g. character counter), the error renders first (above), hint second (below). Both must be `display: block` explicitly.
- **Verify Ziggy route names before use** — always `grep -n "->name"` in `routes/web.php` before writing `route('portal.name')` in Vue. Never guess from the URL pattern. Common trap: `provider.finances` → actual name is `provider.finances.index`.
- **Clickable cards require `cursor: pointer`** — every card element that responds to click (kanban card, table row, list item, contract card, profile card) must have `cursor: pointer` in its CSS rule.
- **Kanban column headers: no icons, minimal** — column label is plain text at 10px uppercase. Counter is a gold-background white-text pill (`.jp-kanban-count { background: var(--gold-dark); color: #fff; font-size: 10px; }`). No `<AegisIcon>` in the header.
- **Icon backgrounds are brand gold only** — use `var(--badge-bg-gold)` + `var(--gold-dark)` for every icon container. No per-item colour assignments (`blue-light`, `purple-light`, `orange-light`, etc.).

---

## Date inputs — `flatpickr` (global, auto-applied)

The project uses **flatpickr** initialized globally via `FormEnhancerPlugin.js`. A `MutationObserver` auto-upgrades every `input[type="date"].form-input` that appears in the DOM — on page load, Inertia navigation, modal open, and tab switch.

### Rule
**Never use a third-party date-picker component, `v-date` directive, or manual `new Date()` formatting in Vue files.** Write the field exactly as a standard native input:

```vue
<!-- ✅ CORRECT — flatpickr auto-applies, shows "Jun 15, 2026" display, sends YYYY-MM-DD to server -->
<input id="myDate" v-model="form.start_date" type="date" class="form-input" />

<!-- ❌ WRONG — importing a component, applying a directive, or doing any date formatting manually -->
<DatePicker v-model="form.start_date" />
<input v-date v-model="form.start_date" />
```

### How it works
- flatpickr is loaded in `resources/js/plugins/FormEnhancerPlugin.js` and registered in `app.js`
- It creates an `altInput` that shows `M j, Y` format (e.g. "Jun 15, 2026") to the user
- The original hidden input retains `YYYY-MM-DD` format for backend compatibility
- A calendar icon appears automatically in the field
- Focus ring matches Aegis gold brand spec
- `appendTo: document.body` prevents clipping inside modals

### Date value normalisation
Laravel's `date` cast serialises as full ISO (`2026-07-15T00:00:00.000000Z`). Always slice to `YYYY-MM-DD` before writing to a date form field:
```js
watch(() => props.record, (r) => {
  if (!r) return
  const date = (v) => v ? String(v).slice(0, 10) : ''
  form.start_date = date(r.start_date)
  form.deadline   = date(r.deadline)
})
```
`el.value` on `<input type="date">` silently rejects anything that isn't `YYYY-MM-DD`, causing flatpickr to show empty even when the value is set.

### Pre-flight gate
grep -c "DatePicker\|v-date\|flatpickr(" $PAGE  # → 0 (never import/call manually in Vue)
```

---

## Select dropdowns — `TomSelect` (global, auto-applied)

The project uses **TomSelect** initialized globally via `FormEnhancerPlugin.js`. The same `MutationObserver` auto-upgrades every `select.form-select` that appears in the DOM.

### Rule
**Never use a Vue select component (`v-select`, `vue-select`, `AegisSelect`, etc.) or any custom dropdown component.** Write the field exactly as a standard native `<select>`:

```vue
<!-- ✅ CORRECT — TomSelect auto-applies gold styling, custom arrow, smooth dropdown -->
<select id="myField" v-model="form.category" class="form-select">
  <option value="">Select category...</option>
  <option value="billing">Medical Billing</option>
  <option value="it">IT Support</option>
</select>

<!-- ❌ WRONG — importing any custom select component -->
<VueSelect v-model="form.category" :options="categories" />
<AegisSelect v-model="form.category" />
```

### How it works
- TomSelect replaces the native `<select>` in the DOM with a styled wrapper (`.ts-wrapper > .ts-control`)
- The original `<select>` is hidden but kept for form value tracking — `v-model` continues to work
- One custom chevron is drawn via CSS `background-image` on `.ts-control` — the library's own caret is suppressed via `--ts-pr-caret: 0px`
- Open state rotates the chevron to gold
- Selected option highlights in `var(--badge-bg-gold)` / `var(--gold-dark)`
- `data-no-enhance="true"` on any `<select>` opts it out (e.g. for a native select inside a third-party widget)

### `v-model` compatibility
TomSelect intercepts the native `change` event so Vue's `v-model` picks up selections automatically. No special handling needed.

### Pre-flight gate
```bash
grep -c "VueSelect\|vue-select\|AegisSelect\|<v-select\|<VSelect" $PAGE  # → 0
grep -c "import.*[Ss]elect" $PAGE | grep -v "AegisDropzone\|useForm"      # → 0
# Native <select class="form-select"> is correct and expected
```

### Opt-out (rare)
If a specific `<select>` must stay native (e.g. wizard with validation, ≤6 static options, `v-show` pane filter), add `data-no-enhance`:
```vue
<select v-model="form.x" class="form-select" data-no-enhance>...</select>
```
No value required — `hasAttribute('data-no-enhance')` is the check. Applies to: multi-step wizard selects with validation triggers, short static lists (≤6 options), filter dropdowns inside `v-show` panes.

---

## Full list with examples: `AEGIS_VUE_RULES.md` Sections 1–23.

---

## Start

Read `AEGIS_VUE_RULES.md`. Read the attached PHP file. Run Step 0. Output Step 1 inventory. Run Step 2 diff. Apply Step 3 fixes surgically. Verify Step 4. Run Step 5 gates. Deliver Step 6.

No deviations. No improvisation. No skipping the inventory.
