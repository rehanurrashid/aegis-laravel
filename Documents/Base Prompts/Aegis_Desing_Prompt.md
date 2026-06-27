# Aegis Design Prompt — Comprehensive Reference

The deep reference for the Aegis design + brand system. Pair with **`Aegis_Desing_Prompt_Short.md`** (the strict execution checklist) on every design pass. The short prompt is what gets *applied*; this document is what gets *consulted* when the short prompt doesn't fully cover an edge case.

If the two ever conflict, **the short prompt wins** — it encodes the field-tested rules that survived contact with real refactors.

---

## Purpose

Take any portal page and bring it into full alignment with the Aegis design system. Drop local CSS that duplicates `_shared.css`. Rename collisions. Migrate non-canonical markup to canonical patterns. Replace inline `<svg>` with `aegis_icon()`. Fix every color, type-scale, radius, shadow, and interaction-state violation.

**No wiring changes here.** Backend hooks, helper additions, seed.json edits, and asset retargeting are Phase 3 (the wiring pass). Visual centralization only.

---

## Scope — files you can edit without asking

| File | When you'll touch it |
|---|---|
| **`<target>.php`** (the file the user gave you) | Always — the primary target |
| **`activity_body.php`** | If adding a new module entry to the activity log surface |
| **`icons.php`** | Propose adding a canonical icon name when needed; **never inline `<svg>`** |

**Out of scope** — flag, never edit silently:

- `_shared.css`, `_shared.js` — design tokens, palette, global helpers, canonical components
- `sidebar.php`, `header.php`, `page_head.php`, `page_foot.php`, `theme_loader.php` — chrome is finalized
- `db.php`, `models.php`, `seed.json` — wiring concerns (see `Aegis_Global_Wiring_Prompt.md`)
- The Design / Wiring / Tone prompts themselves — propose new rules for owner approval

---

## Workflow

```
Phase 1 — Read & inventory     (output the plan; auto-proceed)
Phase 2 — Apply fixes          (Family 0 first, then 1–9 in order)
Phase 3 — Verify & deliver
```

**Don't pause between phases.** Output Phase 1, immediately start Phase 2. The only stop is if Phase 1 surfaces an out-of-scope change that needs approval.

---

## Phase 1 — Read & inventory

Read in this order:

1. `_shared.css` end-to-end (NON-NEGOTIABLE — the audit is invalid if you skip this)
2. The target file end-to-end
3. `icons.php` for canonical icon names

Then output ONE block:

```
Target: <filename>.php

A. Sections                    (every visible section, top to bottom, named)

B. Centralization plan         (Family 0 — drop these locals, rename these collisions)
   Local CSS to drop (canonical exists):
   - <local class> → <canonical class>
   Local CSS to rename (collides with global):
   - <local name> → <page-scoped name>
   Local JS helpers to drop:
   - <function> → use global
   Markup migrations needed:
   - <pattern> → <canonical pattern>

C. Per-section violations      (Families 1–9, format: [family.rule] line N — <offending value> → <fix>)

D. Cross-section unifications  (same component rendered differently across sections)

E. Suggested global additions  (page-local patterns worth promoting to _shared.css)

F. Flagged                     (deliberate variants you suspect should NOT be unified)
```

Auto-proceed to Phase 2.

---

## Phase 2 — Apply fixes (in this order)

1. **Family 0 — Centralization** (drop locals, rename collisions, migrate markup — most other violations disappear automatically)
2. Family 1 — Color tokens
3. Family 2 — Typography
4. Family 3 — Radius / spacing / shadow / hover / transition
5. Family 4 — Hero
6. Family 5 — Banners / alerts
7. Family 6 — Stat chips
8. Family 7 — Buttons
9. Family 8 — Pills / tabs / badges / chips
10. Family 9 — Modals / forms / icons / feedback
11. Cross-section unifications

Each fix is a separate `str_replace`. Don't bundle unrelated changes. After Family 0 (which removes large blocks), re-view the affected region before continuing.

---

## The three governing rules

### Centralization rule

Every component that exists in `_shared.css` MUST use it. Don't redefine, shadow, or "lightly tweak". If a local definition duplicates a global, drop the local. If a local class name collides with a different global (same name, different intent), rename the local to a page-scoped name — never override the global silently.

### Interaction-state rule

Hover, focus, checked, active for cards, buttons, badges, icons, toggles, checkboxes, radios — all come from `_shared.css`. Never redefine these locally. If a page seems to "need" a different hover, the markup is wrong or the global needs a new modifier — propose the global modifier.

### Promotion rule

Useful local patterns that would benefit other pages → propose promoting to `_shared.css` in the Phase 3 summary under "Suggested global additions". Don't promote silently.

---

## Canonical class reference

The authoritative inventory of `_shared.css`. Use these names — don't invent new ones. Local CSS that duplicates any of these is a hard violation.

### Hero banner

```html
<div class="hero-banner is-quiet">
  <div class="page-hero-inner">
    <div class="page-hero-left">
      <div class="page-hero-eyebrow">EYEBROW</div>
      <div class="page-hero-title">Page title</div>
      <div class="page-hero-sub">One-line subtitle.</div>
      <div class="hero-meta">
        <span class="hero-meta-item"><?= aegis_icon('clock', 14) ?> Meta item</span>
      </div>
    </div>
    <div class="page-hero-actions">
      <a href="#" onclick="navigateTo('activity.php?module=<slug>');return false;" class="btn-hero-ghost is-on-light">
        <?= aegis_icon('activity', 14) ?>
        Activity
      </a>
      <button class="btn-hero-solid is-on-light" onclick="openModal('<id>')">
        <?= aegis_icon('plus', 14) ?>
        Primary action
      </button>
    </div>
  </div>
</div>
```

- `.hero-banner` is the canonical hero. **Always use the `.is-quiet` variant** — the loud variant is deprecated.
- `.page-hero-left` is `flex-direction: column` by default — children stack vertically (eyebrow → title → sub → meta).
- `.page-hero-actions` holds the secondary + primary actions on the right.
- Activity link is a permanent fixture in the hero — points to `activity.php?module=<page-slug>`.
- `.btn-hero-solid` is gold-dark surface, `.btn-hero-ghost` is outlined on light. Both require `.is-on-light` on a quiet hero.

### Stat chips row

```html
<div class="stat-chips-row">
  <div class="stat-chip">
    <div class="stat-chip-icon" style="background:var(--badge-bg-gold);color:var(--gold-dark)">
      <?= aegis_icon('briefcase', 18) ?>
    </div>
    <div>
      <div class="stat-chip-value">42</div>
      <div class="stat-chip-label">Label</div>
    </div>
  </div>
  <!-- repeat -->
</div>
```

- Sits directly below the hero, never inside it.
- `.stat-chip-value` uses serif 22px/700.
- `.stat-chip-label` uses 11.5px/`--text-3`.
- Icon tile uses `--badge-bg-gold` / `--gold-dark` by default; other tones reserved for genuine semantic differences (rarely needed).

### Tabs (three patterns)

**Single-tier (most pages):** `.tabs-segmented` + `.tab-pill`

```html
<div class="tabs-segmented" role="tablist">
  <button type="button" class="tab-pill active" data-tab="all">All</button>
  <button type="button" class="tab-pill" data-tab="active">Active <span class="badge-pill">3</span></button>
</div>
```

**Two-tier (primary scope + secondary filter):** `.tabs-twotier` wrapping `.tabs-primary` (parent buttons) + one `.tabs-segmented.net-sub-tabs` per scope (secondary pills, visibility-toggled via JS).

```html
<div class="tabs-twotier" id="myTabs">
  <div class="tabs-primary" role="tablist">
    <button type="button" class="tab-primary active" data-scope="a">
      <?= aegis_icon('users', 15) ?> Scope A
    </button>
    <button type="button" class="tab-primary" data-scope="b">
      <?= aegis_icon('heart-2', 15) ?> Scope B
    </button>
  </div>

  <div class="tabs-segmented net-sub-tabs" id="subTabs-a" role="tablist">
    <button type="button" class="tab-pill tab-btn active" data-scope="a" data-tab="x">
      <?= aegis_icon('search-lg', 12) ?> Filter X
    </button>
  </div>
  <div class="tabs-segmented net-sub-tabs" id="subTabs-b" role="tablist" hidden>
    <button type="button" class="tab-pill tab-btn" data-scope="b" data-tab="y">
      <?= aegis_icon('users', 12) ?> Filter Y
    </button>
  </div>
</div>
```

- `.tab-primary` icons are 15px; `.tab-pill` icons are 12px.
- `.tab-btn` is a JS-only hook — no visual styles.
- Switching a primary auto-shows its sub-tab container and auto-clicks the first visible pill (canonical JS pattern in `_shared.js`).

**Underline (page-level nav, rare):** `.tabs` + `.tab` — only when the tab semantics are "different page sections" not "filtered view of same data".

Active state: add `.active`. Count chip: `<span class="badge-pill">3</span>`. Use global `switchTab(name, btnEl)` or the canonical `showScope()` pattern — never page-local clones.

### Modals

```html
<div class="modal-overlay" id="myModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Title</span>
      <button class="modal-close" aria-label="Close" onclick="closeModal('myModal')">
        <?= aegis_icon('x', 14) ?>
      </button>
    </div>
    <div class="modal-body">
      <!-- body -->
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('myModal')">Cancel</button>
      <button class="btn btn-primary" onclick="submitMyModal()">Save</button>
    </div>
  </div>
</div>
```

- Open state is driven by the `.open` class on `.modal-overlay`. Use `openModal('id')` / `closeModal('id')` globals from `_shared.js` — never page-local clones, never `style.display = 'flex'`.
- Size variants: `.modal-sm` (~420px), default `.modal` (~560px), `.modal-lg` (~720px), `.modal-xl` (~960px).
- Backdrop click + Escape key close are handled globally — don't add page-local listeners.

### Buttons (full canonical set)

- **Primary**: `class="btn btn-primary"` (Save / Submit / Confirm / Complete)
- **Secondary**: `class="btn btn-outline"` (Cancel / Discard / Back)
- **Destructive**: `class="btn btn-danger"` (Delete / Remove)
- **Ghost**: `class="btn btn-ghost"` (Mark all read, dismissive)
- **Status (semantic only)**: `btn-success`, `btn-green`, `btn-blue`, `btn-emergency`
- **Hero dark-surface**: `btn-hero-solid`, `btn-hero-ghost` + `.is-on-light` on quiet hero
- **Sizes**: default ~36px, `btn-sm` ~30px
- **Icon-only**: `btn-icon` (32×32) + `data-tip` attribute always; `btn-icon-sm` (26×26); `btn-icon-danger` modifier for trash/delete; `btn-icon-primary` for emphasis
- **Grouped**: `.btn-group`

**Hard rules:**

- `btn-gold` is a deprecated alias → always use `btn-primary`
- Redundant `class="btn btn-outline btn-icon"` → drop to `class="btn-icon"`
- Icon-only buttons: `btn-icon` carries its own background + border. Never override locally.

### Forms

```html
<!-- Single field -->
<div class="form-group">
  <label class="form-label">Field label</label>
  <input type="text" class="form-input" placeholder="Placeholder">
  <div class="form-hint">Optional hint text.</div>
</div>

<!-- Two-column row -->
<div class="form-row">
  <div class="form-group">…</div>
  <div class="form-group">…</div>
</div>

<!-- Three-column row -->
<div class="form-row is-3col">
  <div class="form-group">…</div>
  <div class="form-group">…</div>
  <div class="form-group">…</div>
</div>
```

- Container: `.form-group` (label + control + hint stack)
- Label: `.form-label`
- Text input: `.form-input`
- Select: `.form-select`
- Textarea: `.form-textarea`
- Compact variants: `.form-input-sm`, `.form-select-sm`
- Multi-column rows: `.form-row` (2-col default), `.form-row.is-3col` (3-col)
- Inside modals, `.form-row` zeroes the `.form-group` margin-bottom so spacing comes from the row's own margin
- Error: `.is-invalid` modifier + `.form-error` message below
- Required marker: `<span class="required">*</span>` (no inline color)
- Leading icon: wrap input in `.input-group` + `.input-group-icon`

### Checkboxes & radios

```html
<label class="form-check">
  <input type="checkbox" checked>
  <span class="form-check-label">Label</span>
</label>
```

- Use `.form-check` for the wrapper; `.form-check-label` for the text.
- Checked state uses `--gold-dark` background, white check mark, both styled globally.
- **Never** `style="accent-color: var(--gold-dark)"` inline — the global rule already covers it.

### Toggles (button pattern — verified smooth)

```html
<!-- Off -->
<button type="button" class="toggle" onclick="toggleSwitch(this)" aria-pressed="false"></button>

<!-- On -->
<button type="button" class="toggle on" onclick="toggleSwitch(this)" aria-pressed="true"></button>

<!-- Settings row -->
<div class="setting-row">
  <div class="setting-info">
    <div class="setting-label">Title</div>
    <div class="setting-desc">Optional description.</div>
  </div>
  <button type="button" class="toggle on" onclick="toggleSwitch(this)" aria-pressed="true"></button>
</div>
```

- Off-track `--border-dark`, on-track `--gold-dark`, thumb always white.
- Driven by global `toggleSwitch()` in `_shared.js`. Never `this.classList.toggle('on')` — always `toggleSwitch(this)`.
- Never use a hidden `<input type="checkbox">` paired with custom CSS — the button-only pattern is the canonical one.

### Cards

```html
<div class="card">
  <div class="card-header">
    <div>
      <div class="card-title">Title</div>
      <div class="card-subtitle">Subtitle</div>
    </div>
    <button class="btn-icon" data-tip="Edit"><?= aegis_icon('pencil', 14) ?></button>
  </div>
  <div class="card-body">…</div>
  <div class="card-footer">…</div>
</div>
```

Hover is built in (`--shadow-sm` rest → `--shadow` hover). Card-header bg = `--surface-2`, card-body bg = `--surface`.

**Card family variants** — extend `.card` with a tier modifier (don't invent new family roots):

- **`.rnp-card`** — Recommended Network Partner cards
- **`.rsp-card`** — Recommended Shadow Provider cards
- **`.rfc-card`** — Referral cards

### Badges, status dots

- **Badge**: `badge badge-{green|red|gold|blue|orange|purple|gray|dark|teal|emergency}` (11px / 700 / uppercase, light-tint bg + dark-tone text)
- **Dot-prefix**: `badge-dot {active|inactive|pending|danger|emergency}`
- **Standalone status dot**: `status-dot {green|red|gold|blue|orange|gray|emergency}` (add `.pulse` for animation)
- **Tab count chip**: `badge-pill`
- **Verified blue check**: `aegis-verified-badge`, `aegis-verified-badge-sm`, `aegis-verified-badge-icon-only`

### Alerts

```html
<div class="alert alert-warning">
  <div class="alert-icon"><?= aegis_icon('alert-triangle', 18) ?></div>
  <div class="alert-content">
    <div class="alert-title">Title</div>
    <div>Body copy.</div>
  </div>
</div>
```

Variants: `alert-info`, `alert-success`, `alert-warning`, `alert-danger`, `alert-gold`, `alert-emergency`.

**Universal `--shadow-sm` rule:** every `.alert` variant gets `box-shadow: var(--shadow-sm)` at rest. No hover-elevation.

### Avatars

- Sizes: `avatar-{xs|sm|md|lg|xl|2xl}`
- Colors (only these): `avatar-gold` (active users), `avatar-dark` (external/suspended), `avatar-red` (emergency, rare)
- Default (no color modifier): `--surface-3` bg + `--gold-dark` initials
- **Never** inline `style="background: …"` on an avatar — use the modifier
- Stack: `.avatar-stack` wrapper

### Empty states

```html
<div class="empty-state">
  <div class="empty-state-icon"><?= aegis_icon('inbox', 28) ?></div>
  <div class="empty-state-title">Nothing here yet</div>
  <div class="empty-state-desc">One-line context.</div>
  <button class="btn btn-primary btn-sm">Primary action</button>
</div>
```

### List groups

```html
<div class="list-group">
  <div class="list-group-item">
    <div class="list-group-icon"><?= aegis_icon('mail', 14) ?></div>
    <div class="list-group-text">
      <div class="list-group-label">Label</div>
      <div class="list-group-value">Value</div>
    </div>
  </div>
</div>
```

Use for contact info blocks, key-value pairs, anything that's "rows of icon + label + value". Never `<br>`-separated text in a dark block.

### Tables

```html
<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Col</th></tr></thead>
    <tbody><tr><td>Cell</td></tr></tbody>
  </table>
  <div class="table-actions">…</div>
</div>
```

Pagination: add `.pager` below any list/table/grid that can exceed 10 items.

### Accordions

```html
<div class="accordion">
  <div class="accordion-item">
    <button class="accordion-trigger" onclick="toggleAccordion(this)">
      <span>Question</span>
      <?= aegis_icon('chevron-down', 14) ?>
    </button>
    <div class="accordion-content">Answer.</div>
  </div>
</div>
```

Custom FAQ patterns (`.faq-item`, `.faq-q`, `.faq-a`) → migrate to `.accordion-*`.

### Icons (always)

```html
<?= aegis_icon('name') ?>           <!-- 20px default -->
<?= aegis_icon('name', 16) ?>       <!-- explicit size -->
<?= aegis_icon('name', 24, 'icon-warn') ?>  <!-- size + extra class -->
```

- **Never** inline `<svg>` blocks.
- **Never** use deprecated names: `square-pen`, `x-circle`, `gear`, `edit`, `time`, `magnifier`, `person`, `event`. Use `pencil`, `x`, `settings`, `pencil`, `clock`, `search`, `user`, `calendar` respectively.

### Identity blocks (person rows / cards)

```html
<!-- WRONG: detail modal for a person -->
<button onclick="openProviderDetailModal('Dr. Smith')">View profile</button>

<!-- RIGHT: redirect to canonical public profile -->
<button onclick="viewPartyProfile('Dr. Sarah Johnson', 'provider', 'sarah-johnson')">View profile</button>
<!-- kind: 'provider' (practitioner) | 'steward' (CS) | 'ss' (Support Steward) | 'business' (BP) -->
```

---

## Violation families

For each section, scan for violations across all 10 families. Format: `[family.rule] line N — <offending value> → <fix>`.

### Family 0 — Centralization (HIGHEST PRIORITY — fix first)

0.1. Local CSS class that duplicates a `_shared.css` class → drop local; use global
0.2. Local class name that collides with a different global (same name, different intent) → rename local page-scoped
0.3. Local body styling on classes whose body comes from global (e.g. defining `.btn-icon` background/border locally)
0.4. Local JS helper duplicating `_shared.js` (`openModal`, `closeModal`, modal-overlay-click, Escape keydown, `showToast`, `toggleSwitch`, `toggleSidebar`, `handleLogout`, `toggleDropdown`, `navigateTo`, `aegisSlugify`, `switchTab`) → drop local; use global. **This eliminates double-fired events that cause "glitchy" toggle and modal feel.**
0.5. Markup using a non-canonical pattern when a canonical pattern exists (custom hero div, custom alert div, custom step indicator, custom timeline item, custom accordion, custom FAQ)
0.6. Redundant class chains (`btn btn-outline btn-icon` → `btn-icon`, `card card-default` → `card`)
0.7. Page-local override of a global hover/transition where the global already does the right thing
0.8. Inline `style="background: var(--…)"` that duplicates what a class modifier would do (e.g. `style="background:var(--gold-dark)"` on an avatar → `.avatar-gold`)
0.9. Local CSS using a non-canonical name for a known component → migrate
0.10. Same pattern duplicated across multiple pages with slight variations → promote one canonical version to `_shared.css`
0.11. **Dead local CSS** (defined but never used in markup) → remove entirely

### Family 1 — Color, gradients, gold

1.1. Hardcoded color values (bare hex, `rgb()`, `rgba()`, `hsl()`, named colors). Exceptions: `#fff` / `rgba(255,255,255,*)` on dark panels, `rgba(0,0,0,0.4)` for modal backdrops only
1.2. Alpha-tinted brand tokens — `rgba(160,120,48,.1)` should be `var(--badge-bg-gold)`, etc.
1.3. `var(--gold)` in active states, primary CTAs, accent borders, serif headings, checked indicators, hover states → must be `--gold-dark`
1.4. Any `linear-gradient()` or `radial-gradient()` outside the hero `::before` dot-grid
1.5. Stat values colored `--gold-dark` (must be `--text` — gold is for actions, not data)
1.6. Legacy tokens `var(--primary)` / `var(--primary-light)` → use `--text` (data) or `--gold-dark` (action) or `--blue-dark` (info)
1.7. Status colors (`--blue-dark`, `--green-light`, etc.) on icon boxes or card backgrounds — only `--icon-bg-gold` on icon boxes; status colors are for badges/alerts/dots only

### Family 2 — Typography

2.1. Off-scale font sizes — allowed: 10/11/12/13/14/15/18/22/24/26/28/36px. Common offenders: 9.5→10, 10.5→10, 11.5→12, 12.5→13, 13.5→13. No `.5px` sizes.
2.2. Font weights other than 600 or 700 in UI (body prose can be 400)
2.3. Body text using `--text-3` or `--text-4` (must be `--text-2` minimum)
2.4. Mini-labels not using `--text-4` + uppercase + `letter-spacing: 0.4px`
2.5. Data values (numbers, prices, dates, names) not using `--text` + 700
2.6. Sentence-case text below 11px
2.7. Serif font on body, labels, or buttons (serif reserved for titles, hero numbers, names, dates, modal titles)
2.8. `font-family: monospace` → `var(--font-mono)`

### Family 3 — Radius, spacing, shadow, hover, transition

3.1. `border-radius` off-scale (`--radius-xs|sm|none|lg|xl|full`). Flag every 4/6/10/14/20px
3.2. `border-radius: 0` mid-component, or rounding only one corner
3.3. `border-radius: 50%` anywhere → `var(--radius-full)`
3.4. Card padding outside `16px 20px` (or `12px 14px` for compact tiles). No 24px+
3.5. Section gaps off-scale (12px banners / 16px within grid / 22px between sections)
3.6. Shadows other than `--shadow-sm` (rest) / `--shadow` (hover)
3.6.1. **Universal alert shadow rule** — every `.alert` variant + every small notice/callout box + every pinned banner + every standalone status surface that's NOT a `.card` MUST render with `box-shadow: var(--shadow-sm)`. No hover-elevation. Uniform elevation across notice surfaces.
3.7. Transitions other than `var(--transition)` (150ms ease) or `var(--transition-slow)` (300ms ease) for animation timing
3.8. `transform` on hover without paired `box-shadow` change (or vice-versa)

### Family 4 — Hero

4.1. Custom hero div (`.welcome-hero`, `.dsr-hero`, `.exec-hero`, etc.) → `.hero-banner.is-quiet`
4.2. Loud-variant hero anywhere → use `.is-quiet` exclusively
4.3. Hero missing the canonical Activity link in `.page-hero-actions`
4.4. Hero actions not using `.btn-hero-ghost is-on-light` + `.btn-hero-solid is-on-light`
4.5. Hero text wrapper missing `.page-hero-left` / `.page-hero-inner`
4.6. Meta items using a custom class instead of `.hero-meta-item`

### Family 5 — Banners / alerts

5.1. Custom alert div not using `.alert` + variant
5.2. Alert without `.alert-icon` + `.alert-content` structure
5.3. Alert missing `box-shadow: var(--shadow-sm)` (3.6.1)
5.4. "Info panel" / "suspended banner" / "declined row" custom patterns → `.alert` variants

### Family 6 — Stat chips

6.1. Custom stat grid → `.stat-chips-row` + `.stat-chip`
6.2. Stat icon tile using inline background outside `--icon-bg-gold` / `--badge-bg-gold`
6.3. Stat value not serif 22px/700
6.4. Stat label not 11.5px/`--text-3`

### Family 7 — Buttons

7.1. `btn-gold` → `btn-primary`
7.2. `class="btn btn-outline btn-icon"` redundancy → `btn-icon`
7.3. Icon-only button missing `data-tip` attribute
7.4. Icon-only button using `title="…"` (browser tooltip) → `data-tip` (canonical tooltip)
7.5. Local `.btn-icon` styling (background, border, hover) → drop local
7.6. Inline `style="padding: 6px 14px"` on a button → use size variant (`btn-sm`)
7.7. Button without explicit type on a `<form>` page (defaults to submit) — add `type="button"` where not submitting

### Family 8 — Pills / tabs / badges / chips

8.1. Local tab pattern → `.tabs-segmented` / `.tabs-twotier`
8.2. Page-local `switchTab()` function → use global
8.3. Two-tier nav not using `.tabs-primary` + `.tabs-segmented.net-sub-tabs` pattern
8.4. Tab count chip not using `.badge-pill`
8.5. Custom "chip" pattern that's really a badge → `.badge` variant
8.6. Filter chip pattern not using the canonical `.tab-pill` segmented variant

### Family 9 — Modals / forms / icons / feedback

9.1. Custom modal wrapper → `.modal-overlay` + `.modal` + `.modal-header` / `.modal-body` / `.modal-footer`
9.2. `style.display = 'flex'` to open a modal → `openModal('id')` (adds `.open` class)
9.3. Page-local Escape/backdrop close listeners → drop, use global
9.4. Form input not using `.form-input` / `.form-select` / `.form-textarea`
9.5. Form row not using `.form-row` / `.form-row.is-3col` for multi-column
9.6. Inline `style="accent-color: var(--gold-dark)"` on checkbox → drop (global rule covers it)
9.7. Inline `<svg>` → `aegis_icon('name', size)`
9.8. Deprecated icon names (`square-pen`, `x-circle`, `gear`, `edit`, `time`, `magnifier`, `person`, `event`) → canonical names
9.9. `alert()` / `confirm()` calls → `showToast()` / `confirmAction()` from `_shared.js`
9.10. Toast not using `showToast(message, type)` — `type` ∈ `'success' | 'error' | 'info' | 'warning'`
9.11. Custom toggle (hidden checkbox + label hack) → button-only `.toggle` + `toggleSwitch(this)`
9.12. Toggle JS using `this.classList.toggle('on')` → `toggleSwitch(this)`
9.13. Custom dropdown menu → `.dropdown` + `.dropdown-trigger` + `.dropdown-menu` + global `toggleDropdown()`
9.14. Custom empty-state markup → `.empty-state` family
9.15. Custom row-list pattern → `.list-group` family
9.16. Custom table styling → `.table-wrap` + `.table` + `.table-actions`
9.17. Custom accordion / FAQ → `.accordion-*` family
9.18. `onmouseenter` / `onmouseleave` inline hover handlers → let CSS hover handle it
9.19. `<br>`-separated contact info in a dark block → `.list-group` rows with icon + label + value
9.20. `el.style.borderColor = 'var(--blue-dark)'` for selection state → `.classList.add('selected')` + CSS `.selected` rule
9.21. Avatar with inline `style="background: …"` → `avatar-gold` / `avatar-dark` modifier

---

## Phase 3 — Verify & deliver

### Pre-flight grep checklist (all 0 except noted)

```
☐ 0 inline <svg>                                   (all via aegis_icon)
☐ 0 linear-gradient                                (except hero ::before dot-grid in _shared.css)
☐ 0 border-radius: 50%                             (use --radius-full)
☐ 0 var(--gold) in active states                   (only --gold-dark allowed)
☐ 0 ×</button> text close                          (use aegis_icon('x', 13))
☐ 0 title="…" on icon-only buttons                 (use data-tip)
☐ 0 alert() / confirm() calls
☐ 0 var(--primary) / var(--primary-light) on data  (use --text)
☐ 0 !important declarations
☐ 0 class="btn btn-outline btn-icon" chains
☐ 0 class="btn btn-gold"                           (use btn-primary)
☐ 0 off-scale font sizes (.5px)
☐ 0 bare rgba() outside rgba(255,255,255,*) and rgba(0,0,0,0.4)
☐ 0 inline style="background: var(--*)"  on avatars (use avatar-gold/avatar-dark)
☐ 0 status colors on icon-box or avatar backgrounds
☐ 0 detail-modal-for-a-person                      (use viewPartyProfile)
☐ 0 background:white / color:white                 (use var(--surface) / var(--text-inverted))
☐ Every alert variant has box-shadow: var(--shadow-sm)
☐ Every list/table/grid that can exceed 10 items has .pager
☐ Hero variant is .hero-banner.is-quiet (no loud variant)
☐ Hero has the canonical Activity link
☐ All tab patterns use .tabs-segmented (single-tier) or .tabs-twotier (two-tier)
☐ All modals use .modal-overlay + .open driven by global openModal/closeModal
☐ All toggles use the button-only pattern with toggleSwitch(this)
☐ Theme block emitted by theme_loader.php before page-local <style>
```

Copy edited files to `/mnt/user-data/outputs/`. Call `present_files`. Summary in this format:

```
## Centralization (dropped local, adopted global)
- <local class>: <what was duplicated; what global replaces it>
- ...

## Centralization (JS — dropped local, using global)
- <function>: <global it replaces>

## Sections refined
1. <Section name> — <count> fixes across families <list>
2. ...

## Cross-section unifications
- <unification 1>

## Spec violations fixed (in addition to centralization)
- Nx <violation type>

## Flagged for review (not changed)
- <thing>: <why kept local>

## Suggested global additions (for _shared.css)
- <pattern>: <reason to promote>
```

---

## Hard constraints

- **Read `_shared.css` first.** Non-negotiable. The audit is invalid if you skip this.
- **Surgical edits only** — `str_replace` over rewrites. Don't bundle unrelated fixes into one edit.
- **Re-view after Family 0.** Centralization removes large blocks; subsequent edits must see the new file state.
- **Never promote silently.** Suggested global additions belong in the Phase 3 summary, not in `_shared.css` directly.
- **No design changes that are really wiring changes.** Asset paths, data helpers, link renames, schema additions are the Wiring Prompt's job, not this one.
- **If unsure, mirror.** The Provider Portal is the worked example for every canonical pattern. When in doubt, check the Provider equivalent of the page you're working on.
