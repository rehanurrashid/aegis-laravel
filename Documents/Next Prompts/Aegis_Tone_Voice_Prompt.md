# Aegis — Tone & Voice Pass Prompt (all portals)

> Paste this into a chat and name the **one file** you want toned. This is the **4th standard pass**, run *after* wiring, design, and seeding — it changes **prose only**, never structure, components, data, or behavior. Works for every portal (Provider, Continuity Steward, Support Steward, Business Partner) and the public pages.

**Source of truth:** MA'AT Tone of Voice & Messaging Guidelines 2026 + the project's canonical terminology (`AEGIS-PROJECT-CONTEXT.md` / master instructions). If a tone swap ever conflicts with canonical terminology, **canonical terminology wins** (e.g., never turn "Critical Incident" back into "emergency").

---

## The Standard

**Voice:** clear, confident, warm, intelligent, professional, visionary, reassuring — **calm authority, thoughtful clarity, grounded warmth, steady reassurance.** Speak as a **trusted partner and guide**, talking *with* practitioners — never a vendor or lecturer.

**Every screen should leave the reader feeling:** safe · prepared · supported · connected · inspired · not alone.

**Style:** complete, intentional sentences · clarity over cleverness · rhythm, not urgency · meaning over marketing.
**Avoid:** rushed, overly technical, sales-driven, corporate, abstract, or alarmist tones; hype, exaggerated claims, stacked buzzwords, fragments.

**Narrative flow (for any multi-sentence block):** acknowledge reality › provide clarity › offer support › reinforce partnership › invite possibility.

**Calibration (the brand's own examples):**
- *Not MA'AT:* "Our cutting-edge platform revolutionizes provider preparedness with innovative automation solutions."
  **MA'AT:** "We help providers put thoughtful plans in place so their work, clients, and communities remain supported — no matter what arises."
- *Not MA'AT:* "Don't worry — our system handles emergencies for you automatically."
  **MA'AT:** "You're not alone when things shift. Aegis provides support and guidance so you can continue caring with confidence."

---

## Phase 1 — Read & plan (before any edit)

1. Search the project KB; read the named target file's relevant sections.
2. **Find where the copy lives.** Grep the target file *and* `seed.json` (and any helper that emits copy, e.g. `aegis_overview_data()` in `models.php` for CS/SS/BP overview templates). Seeded or helper-sourced copy must be edited **at its source** so it stays dynamic — never hardcode it back into the template.
3. Output one short plan block, then proceed:

```
File: <path> · Portal: <which>
Copy sources: inline PHP/HTML | seed.json keys: <list> | helper: <name or none>
Prose surfaces to tone: <heroes, eyebrows, subtitles, section intros, notice/callout bodies,
  empty-state text, FAQ Q&A, onboarding/welcome copy, descriptions, narrative hints/placeholders,
  narrative toast sentences>
Planned swaps: <count + brief, e.g. 4× "platform"→"Aegis", 2× "emergency"(prose)→"critical moment">
Flagged (NOT applying): <entity-rename candidates + any copy that needs owner sign-off>
```

---

## Phase 2 — Edit (prose only, `str_replace`, scoped)

Apply the MA'AT voice to the prose surfaces only. Edit seeded copy in `seed.json` (or the source helper); edit inline copy in the file. No rewrites of whole files — surgical `str_replace`.

### Vocabulary

**Prose swaps — apply where they appear in narrative copy:**
| Replace | With |
|---|---|
| emergency / emergencies / "emergency situations" (event context) | critical moment(s) / critical incident / disruptive event(s) |
| backup provider(s) | continuity partner(s) |
| risk management | ethical readiness |
| compliance (used as framing/marketing) | care responsibility |
| solutions / optimize / automated / cutting-edge / seamless / revolutionize / disruptive | plain rewrite of what it actually does |
| "the Aegis platform" / "on the platform" | "Aegis" / "your Aegis portal" / "in Aegis" |
| "protect your practice" (tagline use) | "put thoughtful structures in place" / "keep your practice supported" |

**Lean into (use more of):** Support · Partner · Stewardship · Aligned · Prepared · Collaborative · Grounded.
**Themes:** Structure · Continuity · Integrity · Balance · Harmony · Stewardship · Readiness · Interdependence.

### DO tone
Heroes, eyebrows, subtitles · section intros/subs · notice & callout bodies · empty-state titles/text · FAQ questions & answers · onboarding/welcome copy · descriptions and narrative helper/hint text · placeholder sentences · the human sentence inside a `showToast()` string (not its type).

### DON'T touch
- Button labels, form field labels, table headers, validation/error messages, status badges, nav/sidebar labels — keep functional and literal.
- Canonical product/entity names (see flag list) and the canonical terminology table.
- **Pricing** — figures and plan names are on hold; leave them.
- Anything in code: variable/function names, `data-*` attributes, element IDs, CSS classes, query params (`?emergency=…`), DB enum values/strings.
- Layout, components, structure, behavior — this pass is text only.
- **No global find-replace** on *protect / management / platform* — judge each instance; keep literal-security ("we protect what matters"), structural, and page-name uses.

### Entity names — FLAG, do NOT rename
These look like vocabulary swaps but are product entities (roles, portals, DB enums, page/zone names, query params). Surface them in the plan's "Flagged" line; **do not apply** without explicit owner approval (renaming ripples across schema + all four portals + routing + public pages):
- **Business Partner** (role / portal / `business_partner` enum / key term) — guideline suggests "Practice support collaborators."
- **Continuity Management** (CS cockpit page) — don't soften "management" here.
- **Document Vault / Emergency Vault** (zone names) — keep.
- **`?emergency=…`** and similar functional keys — keep (code contract).

### Pre-flight (tone)
```
☐ Prose only — no button/field/header/validation/nav text changed
☐ No entity renamed; flag list surfaced, not applied
☐ No find-replace on protect/management/platform — each judged
☐ Canonical terminology intact (no "Professional Will/Executor/DSR/Emergency-as-event/Premium Executor Service")
☐ Pricing untouched
☐ Seeded/helper copy edited at source (stays dynamic), not hardcoded
☐ No code identifiers, IDs, classes, data-attrs, or query params altered
☐ Reads calm/partner — not alarmist, sales-y, or corporate
☐ php -l passes
```

---

## Deliverable

The toned file(s) + a 1-screen **TONE CHANGES** note as a before→after table:

```
## Tone changes — <file>
| Surface | Before | After |
|---|---|---|
| hero sub | "…on the Aegis platform…" | "…in your Aegis portal…" |
| FAQ #4 | "What types of emergencies…" | "You decide what to include…" |
…
## Flagged for owner (not applied)
- <entity rename or copy needing sign-off> : <why held>
```

Then **stop.** One file per chat.
