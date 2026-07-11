# Aegis Design Prompt — Strict Rules

Apply every rule. Canonical reference page: **`provider-portal/network.php`** (highest component density). On any gap, fall back to `Aegis_Desing_Prompt.md`.

---

**1. Hero — always quiet.** `<div class="hero-banner is-quiet">` with `.page-hero-inner > .page-hero-left + .page-hero-actions`. No custom hero divs.

**2. Activity link — always in hero actions.**
```html
<a href="#" onclick="navigateTo('activity.php?module=<slug>');return false;" class="btn-hero-ghost is-on-light">
  <?= aegis_icon('activity', 14) ?> Activity
</a>
```

**3. Stat chips — always `.stat-chips-row` + `.stat-chip`.** Same markup on every page. Icon tile = `--badge-bg-gold` / `--gold-dark`.

**4. Single menu → `.tabs-segmented` + `.tab-pill`** (12px icons).

**5. Primary + secondary menu → `.tabs-twotier` > `.tabs-primary` (parent, 15px icons) + `.tabs-segmented.net-sub-tabs` (per scope, `hidden` attribute toggled via JS).**

**6. Modals — always `.modal-overlay` + `.open` class** driven by global `openModal('id')` / `closeModal('id')`. Never `style.display = 'flex'`. Never page-local modal JS.

**7. Form fields — only canonical classes.** `.form-input` · `.form-select` · `.form-textarea` · `.form-group` · `.form-row` (2-col) · `.form-row.is-3col` · `.form-check` + `.form-check-label`.

**8. Toggles — button-only pattern.**
```html
<button type="button" class="toggle" onclick="toggleSwitch(this)" aria-pressed="false"></button>
```
Never `this.classList.toggle('on')`. Never hidden checkbox + label hack.

**9. Icon-only buttons → `.btn-icon`** (or `.btn-icon-sm` / `.btn-icon-danger` / `.btn-icon-primary`) with `data-tip="…"` — never `title="…"`, never `btn btn-outline btn-icon` chain.

**10. Buttons — canonical set only.** `btn btn-primary` · `btn btn-outline` · `btn btn-danger` · `btn btn-ghost` · `btn-hero-solid is-on-light` · `btn-hero-ghost is-on-light` · sizes `btn-sm`. `btn-gold` is deprecated → `btn-primary`.

**11. Icons — always `aegis_icon('name', size)`.** No inline `<svg>`. No deprecated names (`square-pen` → `pencil`, `x-circle` → `x`, `gear` → `settings`, `time` → `clock`, `magnifier` → `search`, `person` → `user`, `event` → `calendar`).

**12. Colors — every value via `var(--token)`.** No bare hex, `rgb()`, `rgba()`, `hsl()`, or named colors. Exceptions: `#fff` / `rgba(255,255,255,*)` on dark panels, `rgba(0,0,0,0.4)` on modal backdrops.

**13. `--gold-dark` for action states. Never `--gold`** on hover, active, focus, checked, primary CTAs.

**14. Font sizes — type scale only.** Allowed: 10 / 11 / 12 / 13 / 14 / 15 / 18 / 22 / 24 / 26 / 28 / 36px. No `.5px` sizes.

**15. Shadows — tokens only.** `--shadow-sm` at rest, `--shadow` on hover. Every `.alert` variant gets `box-shadow: var(--shadow-sm)`.

**16. `border-radius` — tokens only.** No `50%` → `var(--radius-full)`. No off-scale 4/6/10/14/20px.

**17. Avatars — modifier classes only.** `.avatar-gold` / `.avatar-dark` / `.avatar-red` + size `.avatar-{xs|sm|md|lg|xl|2xl}`. Never `style="background: …"` inline.

**18. Drop local JS that duplicates `_shared.js` globals** — `openModal`, `closeModal`, `showToast`, `toggleSwitch`, `toggleDropdown`, `switchTab`, `navigateTo`, `aegisSlugify`, modal-overlay click + Escape listeners. No `alert()` / `confirm()` — use `showToast()` / `confirmAction()`.

**19. Theme block — emit via `theme_loader.php` BEFORE any page-local `<style>`.** Server-side body class is mandatory for theme persistence.

**20. If `_shared.css` has it, use it. No locals. No `!important`. No dead CSS.** Locals that collide with globals → rename page-scoped. Locals that duplicate globals → delete.

**21. Modal titles — plain text only.** No `aegis_icon()` inside `.modal-title`. The only icon in `.modal-header` is the `modal-close` button: `<button class="modal-close" onclick="closeModal('id')"><?= aegis_icon('x', 13) ?></button>`. Never `btn-icon` as the close button.

**22. Multi-step modals — always use global `.modal-steps` row** placed between `.modal-header` and `.modal-body`. Markup: `.modal-step` + `.modal-step-num` + `.modal-step-divider`. JS drives `.active` / `.done` classes and injects `aegis_icon('check', 12)` into `.modal-step-num` for completed steps. Never custom inline styles, bespoke step indicators, or JS that sets `background`/`color` directly on step elements. Add `.modal-steps + .modal-body { padding-top: 2px; }` page-locally to prevent double-gap.

**23. `btn-ghost` is invisible on light backgrounds.** Use `btn-outline` for any secondary action that must be visually present (external links, "View on Stripe", non-destructive confirms). Reserve `btn-ghost` only for truly tertiary/de-emphasised actions where invisibility is intentional.

**24. `btn-icon` in tables and card rows always use icon size 14.** `aegis_icon('name', 14)` — never size 13 in a standard-density row. `btn-icon-sm` + size 13 is reserved for compact/dense contexts only (e.g. `modal-close`, `.is-compact` card rows). Reference: `provider-portal/network.php` btn-icon usage.

---

**Apply order:** Family 0 (centralization — drop locals, rename collisions, migrate markup) → then rules 1–24 in any order.

**Mark each rule in the deliverable summary as APPLIED · N/A · FLAGGED.** Silent skips are not acceptable.
