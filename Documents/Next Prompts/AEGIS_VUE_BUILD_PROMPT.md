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

Full list with examples: `AEGIS_VUE_RULES.md` Sections 1–23.

---

## Start

Read `AEGIS_VUE_RULES.md`. Read the attached PHP file. Run Step 0. Output Step 1 inventory. Run Step 2 diff. Apply Step 3 fixes surgically. Verify Step 4. Run Step 5 gates. Deliver Step 6.

No deviations. No improvisation. No skipping the inventory.
