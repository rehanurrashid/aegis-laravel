# AEGIS — PROMPT 1: PHP → Vue DESIGN CONVERSION (100% visual parity)

**Self-contained. This is the only design reference you need. Do not look for other MD files.**

Convert one legacy PHP page into a Vue 3 + Inertia component with 100% design parity — every section, every element, every modal, every button/link/icon wired to its target. Design + wiring-to-UI only. Backend data wiring is a separate pass (Prompt 2). Email/notification wiring is a separate pass (Prompt 3).

---

## Repo facts (verified — do not re-derive)

- **Laravel root = repo root** (NOT `Documents/…`). Clone target: `github.com/rehanurrashid/aegis-laravel`.
- **Vue pages:** `resources/js/pages/{provider,continuity-steward,support-steward,business-partner,admin,shared,public,auth}/`
- **Design system:** `public/css/_shared.css` (6,900 lines) — the ONLY stylesheet. Never add hex, never Tailwind, never `!important`.
- **Globally registered components (NO import needed):** `AegisIcon, AegisModal, AegisToast, AegisConfirm, AegisBadge, AegisStatChip, AegisHeroBanner, AegisCard, AegisEmptyState, IncidentBanner`.
- **Need LOCAL import:** `AegisDropzone, AegisToggle, AegisPagination, AegisUpgradeModal`, and every file in `resources/js/components/modals/`.
- **Centralized modals available** (`resources/js/components/modals/`): `ReferralModal, ServiceRequestModal, ConnectionRequestModal, HireModal, ContractModal, CredentialModal, PostJobModal, EditJobModal, JobDetailModal, ProposalModal, RejectModal, BpEngageModal, BpQuoteModal, BpScheduleModal, EngagementRequestModal, ScheduleInterviewModal, StageActionModal, ManageApplicationsModal, ApplicantProfileModal, ImportJobTemplatesModal, UpgradeCSModal`.
- **AegisModal sizes:** `sm | md | lg | xl` ONLY. There is NO `fullscreen`. Multi-step wizards use `xl`. Single-step create/edit modals use `lg`. Confirmation modals use `sm` or `md`.
- **AegisDropzone emits:** `@files` (array) and `@rejected`. Never `@file-selected`.
- **Ziggy:** `route()` is a global (ZiggyVue). Import in app.js is `from 'ziggy-js'`.
- **Composables:** `useToast()`, `useConfirm()` (callback: `confirmAction(msg, fn)`), `useActivity()` (`timeAgo`, `severityClass`, `iconForEventType`), `useMessageButton()` (`openConversation(id)`, `loading`), `useProfileRoute()` (`viewProfile(slug, kind)`, `profileHref(slug, kind)` where kind ∈ provider|cs|ss|business).
- **String interpolation safety (PHP):** Never put `?->` + `??` inside a double-quoted string interpolation `"{$obj?->prop ?? 'fallback'}"` — this is a PHP 8.2 parse error. Always extract to a variable first: `$val = $obj?->prop ?? 'fallback'; "…{$val}…"`
- **Client-side validation (MANDATORY):** Every form with a submit action must use Vuelidate (`@vuelidate/core` + `@vuelidate/validators`). See AEGIS_VUE_RULES.md Section 14 for the full pattern. Required fields emit `@blur` → `v$.field.$touch()`. Submit handler calls `await v$.value.$validate()` and returns early if invalid. Errors render via `fieldError(field)` helper (client error beats server error). Same `.form-error` class for both. Error state class on inputs is `is-error` (never `is-invalid`). Never rely on browser native validation alone. Never submit without `v$.$validate()` first.

---

## Step 0 — Setup + full read

```bash
cd /home/claude && rm -rf aegis
git clone --depth=1 https://github.com/rehanurrashid/aegis-laravel.git aegis
cd aegis && git log -1 --oneline

PHP_PAGE="/mnt/user-data/uploads/[PAGE].php"   # attached legacy file
VUE_PAGE="resources/js/pages/[portal]/[Page].vue"

# 1. Read the PHP source IN FULL — design ground truth, never skim
cat $PHP_PAGE | wc -l
cat $PHP_PAGE

# 2. Read existing Vue file (working base — never discard)
cat $VUE_PAGE 2>/dev/null || echo "NEW FILE"

# 3. Read a reference page from the same portal (pattern parity)
ls resources/js/pages/[portal]/
cat resources/js/pages/provider/Dashboard.vue | head -120   # canonical reference

# 4. Confirm which _shared.css classes exist before using any
grep -oE "^\.[a-z][a-z0-9_-]+" public/css/_shared.css | sort -u > /tmp/css_classes.txt
wc -l /tmp/css_classes.txt

# 5. Icon registry — verify every icon name
cat resources/js/components/ui/icons.js | grep -oE "'[a-z0-9-]+':" | head -60

# 6. Available centralized modals
ls resources/js/components/modals/

# 7. Global components (skip importing these)
grep -E "\.component\(" resources/js/app.js
```

---

## Step 1 — Full PHP inventory (output before any code)

Read `[PAGE].php` completely. Produce every table. No code until this is done.

```markdown
## Inventory — [PAGE].php  (N lines)

### Global structure
- Outer wrapper class(es):
- Portal:

### Hero (always quiet)
- eyebrow text / title text / subtitle text:
- Hero actions L→R: [class] [label] [icon+size]  (Activity link always present)

### Stat chips
| # | icon | size | value | label |

### Tab structure
- Single menu → `.tabs-segmented` + `.tab-pill` (12px icons)
- Two-tier → `.tabs-twotier > .tabs-primary` (15px icons) + `.tabs-segmented` sub-tabs
| Primary tab | key | icon | sub-tabs |

### Per-tab sections (every section under every tab)
#### Tab: [key]
| # | Section | Wrapper class | Content | Conditional (empty state)? |

### All modals (every openModal() in PHP)
| Modal ID | Title | Trigger element | Fields | Multi-step? | Centralized component exists? |

### All buttons / links / icons
| Location | Element | Label | Tooltip (data-tip→data-tooltip) | Action | Vue target |
(Action types: open modal / navigate route / message person / refer / request service / view profile / confirm-destructive / submit)

### Dynamic class bindings (every PHP ternary in class="")
| PHP expression | Vue :class |

### Inline styles (every style="")
| Element | Value | var(--token)? |

### Empty states (every if(empty()) / count===0)
| Section | Condition | icon | title | subtitle | CTA |

### PHP <style> block → scoped CSS
| Class | In _shared.css? | Port to <style scoped>? |

### Completion targets (grep the PHP)
- aegis_icon():  N   data-tip=:  N   openModal(): N
- modal divs:    N   foreach:    N   if(empty()): N
- input file:    N   <svg>:      N (should be 0)   title=: N (should be 0)

### Icon audit (every aegis_icon call)
| name | size | in icons.js? | alias needed? |
```

---

## Step 2 — Gap report (if Vue file exists)

Diff existing Vue against the inventory:

```markdown
## Gap Report
### Missing sections / wrong DOM nesting
### Missing modals (by ID)
### Missing / wrong buttons (by location)
### Missing dynamic :class bindings
### Missing empty states
### Missing scoped CSS classes
### Banned patterns present:
  <svg>: N | title=: N | modal-id=: N | ui.openModal: N | @file-selected: N | href="/messages": N | Tailwind: N | bare hex: N
```

If no Vue file exists, scaffold from scratch following Step 3.

---

## Step 3 — Build / fix (surgical, section order)

Work top-to-bottom in PHP document order. `str_replace` for existing files — never rewrite working sections.

### Page skeleton

```vue
<template>
  <AppLayout>
    <AegisHeroBanner eyebrow="[Portal]" title="[Title]" subtitle="[Subtitle]" quiet>
      <template #actions>
        <a :href="route('activity.index', { module: '[slug]' })" class="btn-hero-ghost is-on-light">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
        <!-- other hero CTAs -->
      </template>
    </AegisHeroBanner>

    <!-- stat chips: SIBLING of hero, never inside -->
    <div class="stat-chips-row">
      <AegisStatChip icon="..." :value="..." label="..." />
    </div>

    <!-- tabs: v-show (never v-if — preserves state) -->
    <!-- sections in exact PHP order -->

    <!-- ALL modals at the bottom -->
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { useActivity } from '@/composables/useActivity'
import { useMessageButton } from '@/composables/useMessageButton'
import { useProfileRoute } from '@/composables/useProfileRoute'
// local imports ONLY for: AegisDropzone, AegisToggle, AegisPagination, and any modals/ components used

const toast = useToast()
const { confirmAction } = useConfirm()
const { timeAgo, severityClass } = useActivity()
const { openConversation, loading: msgLoading } = useMessageButton()
const { profileHref } = useProfileRoute()

const props = defineProps({ /* placeholder — real props come in Prompt 2 */ })

const activeTab = ref('[first tab]')
const modals = reactive({ /* one key per modal */ })
</script>

<style scoped>
/* ONLY classes not already in _shared.css. var(--token) values only. No hex. */
</style>
```

### Design law (from the strict checklist — apply verbatim)

1. **Hero always quiet** — `<AegisHeroBanner … quiet>`. Never a custom hero div.
2. **Activity link always in hero actions** — `btn-hero-ghost is-on-light`, icon `activity` size 14, `route('activity.index', {module:'<slug>'})`.
3. **Stat chips** — `.stat-chips-row` + `AegisStatChip`. Icon tile is gold (`--badge-bg-gold`/`--gold-dark`). Sibling of hero.
4. **Single menu** → `.tabs-segmented` + `.tab-pill` (12px icons). **Two-tier** → `.tabs-twotier > .tabs-primary` (15px icons) + `.tabs-segmented` sub-tabs.
5. **Modals** → `AegisModal v-model="modals.xxx"`. Size `sm/md/lg/xl` only (multi-step = `xl`). Never `modal-id=`, never `ui.openModal()`.
6. **Modal titles plain text** — no icon inside `.modal-title`. Close button is built into AegisModal.
7. **Multi-step modals** use the global `.modal-steps` row (`.modal-step` + `.modal-step-num` + `.modal-step-divider`); JS drives `.active`/`.done`; inject `check` icon size 12 on done steps.
8. **Form fields** — canonical classes only: `.form-input .form-select .form-textarea .form-group .form-row (2-col) .form-row.is-3col .form-check .form-check-label`. Short fields (select/date/number/short text) go side-by-side in `.form-row`; only textarea/long fields full width.
9. **Toggles** → `AegisToggle` (local import). Never checkbox+label hack.
10. **Icon-only buttons** → `.btn-icon` (or `-sm`/`-danger`/`-primary`) with `data-tooltip="…"`. In table/card rows use icon size **14**. `btn-icon-sm`+size 13 only for compact rows / modal-close.
11. **Buttons** — canonical only: `btn btn-primary`, `btn btn-outline`, `btn btn-danger`, `btn btn-ghost`, `btn-hero-solid is-on-light`, `btn-hero-ghost is-on-light`, size `btn-sm`. `btn-gold`→`btn-primary`. `btn-ghost` is invisible on light bg → use `btn-outline` for any secondary action that must be visible.
12. **Icons** — `<AegisIcon name="x" :size="N" />` only. No raw `<svg>`. Deprecated→correct: `square-pen`→`pencil`, `x-circle`→`x`, `gear`→`settings`, `time`→`clock`, `magnifier`→`search`, `person`→`user`, `event`→`calendar`.
13. **Colors** — every value `var(--token)`. No bare hex/rgb/hsl/named. Exceptions: `#fff` / `rgba(255,255,255,*)` on dark panels; `rgba(0,0,0,0.4)` on modal backdrops (already in _shared.css).
14. **`--gold-dark` for action states** (hover/active/focus/checked/CTA). Never `--gold`.
15. **Font sizes** — scale only: 10/11/12/13/14/15/18/22/24/26/28/36px. No `.5px`.
16. **Shadows** — `--shadow-sm` at rest, `--shadow` on hover. Every `.alert` gets `--shadow-sm`.
17. **border-radius** — tokens only. `50%`→`var(--radius-full)`. No off-scale 4/6/10/14/20px.
18. **Borders — always 1px.** Every card, button, input, alert, badge, modal, role card, plan card, and any bordered box uses `border: 1px solid var(--token)`. Never `1.5px`, never `2px` for standard borders. The only exception is decorative accent stripes (e.g. `border-left: 2px solid var(--gold-dark)` on highlighted rows).
19. **Icon + text in buttons/rows — ALWAYS `inline-flex` + `align-items:center` + `gap`.** Any element that places an `<AegisIcon>` next to text must be `display:inline-flex` (or `flex`) with `align-items:center` and a `gap` value. This applies to button labels, badge content, alert rows, role card badges, and every `<span>` inside a `<button>` that contains both text and an icon. A `<span>` that is NOT flex will cause the icon to float or misalign — this is a hard violation.
20. **Auth-page CTAs — pill shape, consistent padding.** All primary/outline CTA buttons on auth pages (Sign In, Create Account, Continue, Verify, Send Reset Link, Save Password, Resend, etc.) use `border-radius: var(--radius-full)`, `padding: 12px 22px`, `font-size: 13px`, `font-weight: 700`, `width: 100%`. Use the `.ob-btn-full` class with these properties baked in. Never use default `.btn` padding (8px 16px) on full-width auth CTAs.
21. **Focus rings — no box-shadow on inputs or checkboxes.** Remove `box-shadow: 0 0 0 3px ...` from all `:focus` states on `.form-input`, `.form-control`, `.form-select`, `.form-textarea`, `input[type="checkbox"]`, `input[type="radio"]`. Focus is indicated by `border-color: var(--gold-dark)` only. No glow rings. Error state (`.is-error`) shows `border-color: var(--red)` only — no red shadow.
22. **Global outline removal.** All interactive HTML elements (`a`, `button`, `input`, `select`, `textarea`, `[tabindex]`) must have `outline: none`. Never rely on browser default blue/black outlines anywhere in the Aegis UI. Add `outline: none` to the global reset block in `_shared.css`.
18. **Avatars** — modifier classes only: `.avatar-{gold|dark|red}` + size `.avatar-{xs..2xl}`. Never inline `style="background:…"`.
19. **Tooltips** — `data-tooltip="…"` only. Never `title=`.
20. **Icon + text alignment** — any element with an `AegisIcon` next to text is `display:flex`/`inline-flex` + `align-items:center` + `gap`.
21. **If `_shared.css` has it, use it.** No local duplicates, no `!important`, no dead CSS. Locals that collide → rename page-scoped. Locals that duplicate → delete.
22. **Client-side validation (MANDATORY on every write form).** Use Vuelidate (`@vuelidate/core` + `@vuelidate/validators`). Pattern:
    - `const rules = computed(() => ({ … }))` — computed so rules can react to form intent (e.g. draft vs publish have different required fields)
    - `const v$ = useVuelidate(rules, form)`
    - Every required field: `@blur="v$.field.$touch()"` on the input
    - Error class on input: `:class="{ 'is-error': fieldError('field') }"` — always `is-error`, never `is-invalid`
    - Error message: `<div v-if="fieldError('field')" class="form-error">{{ fieldError('field') }}</div>`
    - Unified `fieldError(field)` helper: Vuelidate error wins while editing; falls back to `form.errors.field` (Inertia server errors) — same element, same class for both
    - Submit handler: `const ok = await v$.value.$validate(); if (!ok) { toast.error(…); return }`
    - Modal close / form reset: always call `v$.value.$reset()` alongside `form.reset()`
    - Multi-intent forms (draft vs publish): use a `submitIntent` ref; set it before `$validate()`; rules computed off it via `nextTick()`
    - Client rules must pair with server FormRequest rules: `required` → `required`, `max:200` → `maxLength(200)`, `nullable` → no `required`, `in:x,y` → custom validator checking allowed values

### Button/link/icon wiring (every clickable element gets a real target)

| PHP action | Vue |
|---|---|
| `openModal('x')` | `@click="modals.x = true"` |
| navigate | `<a :href="route('name', params)">` or `router.visit(route(...))` |
| message a person | `@click="openConversation(person.id)"` + `:disabled="msgLoading === person.id"` |
| view profile | `:href="profileHref(person.slug, kindFor(person))"` `target="_blank"` |
| refer client | open centralized `ReferralModal` (set active target ref, then `modals.referral = true`) |
| request service | open centralized `ServiceRequestModal` |
| connect | open centralized `ConnectionRequestModal` |
| hire / contract / quote / schedule | open the matching centralized BP modal |
| destructive (remove/cancel/delete) | `confirmAction('…', () => router.delete(route(...)))` |

Every centralized modal is mounted once at the bottom of the template with a reactive target ref:
```vue
<ReferralModal v-model="modals.referral" :recipient-id="activeTarget.id"
               :recipient-name="activeTarget.name" @sent="toast.success('Referral sent.')" />
```
Read the target component's `defineProps`/`defineEmits` before wiring — never invent props.

### Empty states
Every list/table/grid gets an `AegisEmptyState` (or the PHP's exact empty markup) shown when the collection is empty. Match icon/title/subtitle/CTA from PHP.

### Scoped CSS
Port only PHP `<style>` rules whose classes are NOT in `_shared.css`. Use `var(--token)` only. Verify each:
```bash
for cls in [list from inventory]; do grep -c "\.$cls" public/css/_shared.css | xargs echo "$cls:"; done
```

---

## Step 4 — Mental render (before gates)

Walk the finished template top-to-bottom, tab by tab. For each section confirm the browser draws what the PHP draws: wrapper classes match, icon sizes match (primary tabs 15, sub-tabs 12, card/row btn-icon 14, cards 16), labels/tooltips verbatim, active-state `:class` present, empty states present, every button has a real handler.

---

## Step 5 — Pre-flight gates (all must pass)

```bash
PAGE=$VUE_PAGE
grep -c "modal-id="                   $PAGE   # 0
grep -c "ui.openModal\|ui.closeModal" $PAGE   # 0
grep -c "@file-selected"              $PAGE   # 0
grep -c '<input type="file"'          $PAGE   # 0
grep -c "<svg"                        $PAGE   # 0
grep -c 'title="'                     $PAGE   # 0
grep -cE 'href.*"/messages"'          $PAGE   # 0
grep -cE 'class="[^"]*\b(flex|grid|p-[0-9]|m-[0-9]|text-sm|text-lg)\b' $PAGE  # 0 (Tailwind)
grep -cE '#[0-9a-fA-F]{3,6}'          $PAGE   # 0 (bare hex)
grep -c "is-invalid"                  $PAGE   # 0 — must be is-error, never is-invalid
grep -cE "border.*1\.5px"               $PAGE   # 0 — all borders must be 1px
grep -c "box-shadow.*3px"               $PAGE   # 0 in focus/error states (no focus rings)
grep -c "<script setup>" $PAGE  # 1
grep -c "</script>"      $PAGE  # 1
grep -c "<template>"     $PAGE  # 1
grep -c "</template>"    $PAGE  # 1
# Client-side validation gate — every form with a submit must use Vuelidate
grep -c "useVuelidate"   $PAGE   # ≥ 1 if page has any write form
grep -c 'v\$\.\|v\$\.value\.' $PAGE  # ≥ 1 (vuelidate used in template/script)
grep -c 'fieldError\|form-error' $PAGE  # ≥ 1 per required field
grep -c '\$touch'        $PAGE   # ≥ 1 (blur handlers present)
grep -c '\$validate'     $PAGE   # ≥ 1 (submit handler validates before post)
grep -c 'is-error'       $PAGE   # ≥ 1 per validated field (never is-invalid)
grep -c '\$reset'        $PAGE   # ≥ 1 (reset called on modal close)
# Modal pairing (local AegisModal + centralized modal mounts == v-model keys used)
M=$(grep -cE "<AegisModal|<[A-Z][A-Za-z]+Modal " $PAGE); V=$(grep -c 'v-model="modals\.' $PAGE)
echo "modals:$M vmodels:$V"; test $M -eq $V && echo OK || echo MISMATCH
# Local imports present for non-global components used
for c in AegisDropzone AegisToggle AegisPagination; do
  grep -q "<$c" $PAGE && { grep -q "import $c" $PAGE && echo "$c ok" || echo "$c MISSING import"; }
done
# Icon names valid
for n in $(grep -oE 'name="[a-z0-9-]+"' $PAGE | grep -oE '"[^"]+"' | tr -d '"' | sort -u); do
  grep -q "'$n'" resources/js/components/ui/icons.js || echo "BAD ICON: $n"
done
# Completion counts vs inventory
echo "icons=$(grep -c '<AegisIcon' $PAGE) tips=$(grep -c 'data-tooltip' $PAGE) empty=$(grep -c 'AegisEmptyState' $PAGE)"
```

---

## Step 6 — Deliver

```bash
zip -r /mnt/user-data/outputs/aegis_[page]_design.zip \
  resources/js/pages/[portal]/[Page].vue \
  $(git -C /home/claude/aegis status --porcelain | grep -E "\.vue$" | awk '{print $2}') 2>/dev/null
unzip -l /mnt/user-data/outputs/aegis_[page]_design.zip
```

Summary: every design rule 1–21 marked **APPLIED · N/A · FLAGGED** (silent skips not allowed). Section-by-section gap→fix table. Final counts vs inventory. All gates ✅.

## Start
Read PHP in full. Output Step 1 inventory completely. Output Step 2 gap report. Build/fix Step 3 surgically in section order. Verify Step 4. Run Step 5 gates. Deliver Step 6. No new backend, no email wiring — design + UI-target wiring only.