# Aegis Project — Context for every chat

## What Aegis is

A healthcare continuity-planning SaaS with four portals — Provider, Continuity Steward (CS), Support Steward (SS), Business Partner (BP) — sharing one SQLite backbone, one design system, one write-path layer, and one set of shared chrome/partials.

## Current build state

- **Provider Portal** — ✅ Complete. Centralized, design-aligned, fully wired (read + write path), seed-driven, MA'AT tone applied, AES-256-GCM credential envelope, schema v13. **The reference implementation.**
- **CS / SS / BP Portals** — 🟡 Pages exist (legacy build) but none of the foundation work has been done: not centralized, not design-aligned, not wired, not seed-driven, no tone pass.

Each non-Provider portal needs the full four-pass treatment, page by page: **centralize → design → wire → tone.** Don't skip or reorder — each pass assumes the previous one is complete. The Provider Portal is the worked example for all four.

## Reference docs (use project_knowledge_search to read)

| File | Use it when |
|---|---|
| `AEGIS-PROJECT-CONTEXT.md` | Architectural orientation. Roles, schema, lifecycle, pricing (§8), third-party tools (§9), public profile (§10), build status (§18). |
| `CENTRALIZED-SYSTEM.md` | Before writing/editing code. Inventory of `_shared/` plus worked examples (portal page, write action, cross-portal template). |
| `Aegis_Global_Wiring_Prompt.md` | Wiring any page. 4-phase procedure (Read & plan · Read-path · Write-path · Verify), including the seed-data completeness gate. |
| `Aegis_Desing_Prompt.md` | Design alignment — canonical classes, color tokens, type scale, violation families. |
| `Aegis_Tone_Voice_Prompt.md` | 4th pass after wiring/design/seeding. Prose only. |
| `PENDING-ITEMS.md` | Living tracker — client gates, build commitments, cross-portal stubs. |
| `demo.php` / `seed.json` | Demo scenarios + query params, and the source of truth for demo data. |

## Conventions to honor

- **Use `project_knowledge_search`, not the `/mnt/project` filesystem** — the mirror is stale. Read only what the task needs, not entire files preemptively.
- **Surgical edits only** — `str_replace` over rewrites. Find the exact line, fix only that. No regressions.
- **Edit seeded/helper-sourced copy at the source** (`seed.json` or helpers like `aegis_overview_data()`) — never hardcode back into templates.
- **Write-path pattern:** page-local `xPost()` → `/_shared/save_<domain>.php` → `models_write.php` helper → `aegis_log_activity()` fan-out → toast + reload.
- **Cross-portal activity is how portals communicate** — every write helper with cross-portal impact calls `aegis_log_activity()` for each recipient feed.
- **Mirror Provider patterns on CS/SS/BP.** When unsure how a CS/SS/BP page should look or behave, check its Provider equivalent first. Symmetric naming is in `AEGIS-PROJECT-CONTEXT.md §17.1`.

## Demo controls

- **Demo users:** `p_sarah` · `cs_marcus` · `ss_linda` · `bp_acme`. Switch via `?as=<user_id>`.
- **Reset:** click the "Reset demo data" button on `demo.php` (or hit `/demo.php?reset=1&token=aegis-demo-reset`) after any `seed.json` change.

## Working style

- Don't restate this context back to me unless I ask — you have it, I know you have it.
- For a clear task, start executing. For ambiguous scope, ask one clarifying question.
- Use Sonnet 4.6 for pattern-driven work; escalate to Opus only for novel architectural decisions.

Provider portal files:
dashboard data, job postings dat, referrals data, network data, services data, continuity plan data, contunity stewards data, support stewards data, important documents data, vault data, finance data, messages data, activity data, news data, evetns data, setting data