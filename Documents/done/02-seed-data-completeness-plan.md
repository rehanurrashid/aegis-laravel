# Aegis — Seed Data Completeness & Cross-Portal Data Plan

**Goal:** Make `seed.json` comprehensive (same structure/format already used by the PHP files) so that (a) no person referenced in the Provider UI hits "Page not found," and (b) when CS / SS / Business portals are built they hydrate fully from the same seed with every demo use case covered. Includes the required `db.php`, `models.php`, and `seed.php` changes.

**Method:** Extracted every profile link in the Provider PHP (`viewPartyProfile(name, role, slug)` triples + `/public/{role}.php?slug=` hrefs), checked each against the 84 seeded users with the correct role + visibility, and audited referential integrity across every user-referencing table.

---

## 1. Current Seed Snapshot

- **84 users:** 43 practitioner, **2 continuity_steward**, **2 support_steward**, 37 business_partner.
- Internal FK integrity is **clean** — no `*_id` field points at a missing user; `participant_ids`, `authorized_ss_ids`, `authorized_cs_ids` all resolve.
- The seeder auto-creates a `user_roles` row from each user's primary `role`, so single-role public profiles resolve.
- Plan backbone for `p_sarah` is well-seeded: 1 plan, 4 stewards, 7 incident configs, 34 tasks (14 SS / 20 CS), 37 vault items, 9 threads, 50 activity events.

**So the "Page not found" problem is NOT broken FK references.** It has four distinct root causes (below).

---

## 2. Root Causes of "Page Not Found"

### Cause A — Slug-convention inconsistency (systemic, highest impact)
The seed uses **two different slug conventions at once**:
- Seeded practitioners keep the title prefix: `dr-marcus-webb`, `dr-aisha-patel`, `dr-james-torres`.
- But the **links** that point at them strip the title: `referrals.counterpart_slug = 'marcus-webb'`, and the UI's explicit link slugs are `thomas-chen`, `laura-reyes`, `robert-miller` (no `dr-`).

Result: **14 of 18 referrals 404 on click** even though most of those people exist — the link slug (`marcus-webb`) doesn't equal the stored slug (`dr-marcus-webb`).

> **Action required first:** confirm the real behavior of `aegisSlugify()` in `_shared.js` (not in this export). The UI authors consistently used the **stripped** form, which strongly implies `aegisSlugify` strips leading titles (Dr./Prof./Mr./Mrs./Ms.). Adopt **stripped** as the single canonical rule and regenerate every slug and every stored `*_slug` field from the display name with that one function. This single normalization fixes the 11 "exists-but-wrong-slug" referral links automatically.

### Cause B — People named in the UI but never seeded
Nine concrete profile links resolve to no user at all (full list in §3).

### Cause C — CS public-visibility rule too strict for designated alternates
`aegis_resolve_public_profile()` makes a CS visible only if `cs_public == 1 AND cs_account_type == 'business'`. The seeded **alternate CS** `cs_alternate` (`priya-raman`) is `cs_public=0, type=invited`, so its public profile is non-visible — even though the practitioner designated them. Invited/personal CS can never be viewed by their own practitioner. (See §7 fix.)

### Cause D — Steward states the UI shows aren't allowed by the schema
The UI shows **suspended**, **archived**, **incoming-request**, and **declined** stewards. `plan_stewards.status` CHECK only allows `active|pending|suspended|released` (no `declined`, no `request_incoming`; "archived" maps to `released`). Seeding those states fails the CHECK. (See §6 fix.)

---

## 3. Broken Profile Links — Exact Inventory

Definitive list from `viewPartyProfile(name, role, slug)` + public hrefs (dynamic PHP-variable links excluded — those render from seed-backed loops and are fine):

| Link slug | Namespace | Display name | Where | Implied state | Fix |
|---|---|---|---|---|---|
| `thomas-chen` | steward (CS) | Dr. Thomas Chen, LCSW | continuity-stewards.php (add-CS wizard) | New CS being added | Seed CS |
| `laura-reyes` | steward (CS) | Dr. Laura Reyes, LCSW | continuity-stewards.php (incoming request) | CS request → declined | Seed CS + `request_incoming`/`declined` |
| `amelia-rodriguez` | steward (CS) | Dr. Amelia Rodriguez | continuity-stewards.php (audit log) | Declined invitation | Seed CS + `declined` |
| `aisha-okonkwo` | steward (CS) | Dr. Aisha Okonkwo, MD | continuity-stewards.php (search candidate) | Aegis-certified candidate | Seed CS (business, public) |
| `james-wilson` | steward | (finances "View Public Profile") | finances.php | CS the practitioner pays | Seed CS (business, public) |
| `rachel-pham` | ss | Rachel Pham | support-stewards.php (reinstate) | Suspended SS | Seed SS + `suspended` |
| `jordan-taylor` | ss | Jordan Taylor | support-stewards.php (agreement sent) | New/pending SS | Seed SS + `pending` |
| `brian-santos` | ss | Brian Santos | support-stewards.php (archived) | Archived SS | Seed SS + archived/`released` |
| `robert-miller` | provider | Dr. Robert Miller, MD | referrals.php (referral sender) | Referring practitioner | Seed practitioner |

Plus the **referral slug-mismatch** set (user exists, link slug wrong — fixed by Cause-A normalization): `marcus-webb`→`dr-marcus-webb`, `aisha-patel`→`dr-aisha-patel`, `james-torres`→`dr-james-torres`. And genuinely-missing referral counterparts to seed as practitioners: `hannah-brooks`, `hana-yoon`.

> **Note on slug form for new users:** seed each new user's `slug` to *exactly* what the link uses (stripped, no `dr-`): `thomas-chen`, `laura-reyes`, `amelia-rodriguez`, `aisha-okonkwo`, `james-wilson`, `rachel-pham`, `jordan-taylor`, `brian-santos`, `robert-miller`, `hannah-brooks`, `hana-yoon`. After the Cause-A normalization, the existing `dr-*` practitioner slugs should also be regenerated to the stripped form so everything is consistent.

---

## 4. Users to ADD to `seed.json` (same format)

Add to the `users` array. Fields mirror existing rows (id, role, display_name, credentials, email, phone, avatar_initials, title, organization, specialty, location, bio, slug, plus role-specific flags). Minimum required fields below; fill the rest in the existing style.

### Continuity Stewards (raise from 2 → ~6)
| id | display_name | slug | cs_account_type | cs_public | verified | Purpose |
|---|---|---|---|---|---|---|
| `cs_thomas` | Dr. Thomas Chen, LCSW | `thomas-chen` | business | 1 | 1 | New-CS wizard target |
| `cs_laura` | Dr. Laura Reyes, LCSW | `laura-reyes` | business | 1 | 1 | Incoming/declined request |
| `cs_amelia` | Dr. Amelia Rodriguez | `amelia-rodriguez` | invited | 0 | 1 | Declined invitation |
| `cs_aisha` | Dr. Aisha Okonkwo, MD | `aisha-okonkwo` | business | 1 | 1 | Certified search candidate |
| `cs_james_wilson` | James Wilson | `james-wilson` | business | 1 | 1 | Finance payee profile |

> Keep existing `cs_marcus` (primary, business, public) and `cs_alternate`/`priya-raman` (alternate) — but see §7 to make the alternate viewable.

### Support Stewards (raise from 2 → ~5)
| id | display_name | slug | Purpose |
|---|---|---|---|
| `ss_rachel` | Rachel Pham | `rachel-pham` | Suspended SS (reinstate flow) |
| `ss_jordan` | Jordan Taylor | `jordan-taylor` | New/pending SS |
| `ss_brian` | Brian Santos | `brian-santos` | Archived SS |

> Keep existing `ss_linda` (primary) + `ss_james` (alternate).

### Practitioners (referral counterparts)
| id | display_name | slug | business_partner_public n/a | Purpose |
|---|---|---|---|---|
| `p_robert_miller` | Dr. Robert Miller, MD | `robert-miller` | practitioner_public=1 | Referral sender |
| `p_hannah_brooks` | Dr. Hannah Brooks, LCSW | `hannah-brooks` | practitioner_public=1 | Referral counterpart |
| `p_hana_yoon` | Dr. Hana Yoon, MD | `hana-yoon` | practitioner_public=1 | Referral counterpart |

---

## 5. Relationship Rows to ADD

### `plan_stewards` — model the states the UI shows (for Sarah's plan)
Add rows linking the new stewards to `p_sarah`'s plan with varied states so the Provider list and the future CS/SS portals render every case:

| steward_id | steward_type | role | status | countersigned | Demonstrates |
|---|---|---|---|---|---|
| `cs_laura` | continuity_steward | secondary | `request_incoming` → flips to `declined` | no | Incoming request / declined |
| `cs_amelia` | continuity_steward | secondary | `declined` | no | Declined invitation |
| `ss_rachel` | support_steward | alternate | `suspended` | yes | Suspended → reinstate |
| `ss_jordan` | support_steward | alternate | `pending` | no | New/pending invitation |
| `ss_brian` | support_steward | alternate | `released` (archived) | yes | Archived steward |

> `cs_thomas`, `cs_aisha`, `cs_james_wilson` can be left **unlinked** (they exist as resolvable profiles surfaced by search/finance views) or linked as additional designations if you want them in Sarah's roster.

### `plan_incident_configs` — fill the two empty opt-in types
`detainment` and `geopolitical` are `enabled=0` with empty authorization and **zero tasks**. For full CS/SS demo coverage, enable at least one and give it SS+CS tasks, or leave disabled intentionally and note it. (Current task coverage: death, short/long incapacitation, missing_person, natural_disaster.)

### `critical_incidents` + `incident_tasks` — currently ABSENT (blocks the incident demo)
Seed **one active** and **one closed** incident for `p_sarah` so the Provider dashboard banner, the Activity `incident` events, and the future CS Continuity-Management / SS Critical-Incident-Log all have data:

```
critical_incidents:
  - id, practitioner_id=p_sarah, plan_id=<sarah plan>, incident_type=short_term_incapacitation,
    reported_by_ss_id=ss_linda, reported_at, report_narrative, contact_attempts,
    verified_by_cs_id=cs_marcus, verified_at, status=active
  - id, ... incident_type=natural_disaster, ... status=closed, closed_at, closed_by_user_id, closure_summary
incident_tasks:
  - several rows per incident, plan_task_id -> existing plan_tasks, assigned_to_user_id (cs_marcus / ss_linda),
    status mix (completed / in_progress / pending), completed_at + completion_note where done
```

### Multi-role coverage (for demo.php "all 4 roles")
Only `p_sarah` has an extra `user_roles` row today. Add a few explicit `user_roles` entries (e.g., a practitioner who is also an invited CS) so the role-switcher and cross-role views can be demoed.

---

## 6. `db.php` Changes

**Widen `plan_stewards` to allow the steward states the UI uses.** Current:
```sql
role   TEXT NOT NULL CHECK (role IN ('primary','alternate','secondary'))
status TEXT DEFAULT 'active' CHECK (status IN ('active','pending','suspended','released'))
```
Target:
```sql
role   TEXT NOT NULL CHECK (role IN ('primary','alternate','secondary','tertiary'))
status TEXT DEFAULT 'active' CHECK (status IN
        ('active','pending','suspended','released','declined','request_incoming','archived'))
```
SQLite **cannot ALTER a CHECK constraint**. So:
1. Update the `CREATE TABLE plan_stewards` block in `aegis_init_schema()` to the target definition.
2. Because existing dev DBs won't pick this up via `ALTER`, recreation is required → run `GET /reset.php?token=aegis-demo-reset` after the change (the DB auto-seeds on next load).
3. Add an inline SQL comment documenting the widened vocabulary (matches the project's existing migration-note style).

**(Optional, from Doc 1 §4)** `ALTER TABLE users ADD COLUMN payment_model TEXT;` for the finances 4-option model — idempotent, wrapped in the existing migrations try/catch.

> No other schema change is needed: `authorized_ss_ids`/`authorized_cs_ids`, `docs_required`, `vault_items` zones, and the BP tables already support the full cross-portal contract.

---

## 7. `models.php` Changes

1. **Relax CS public visibility for designated alternates (Cause C).** In `aegis_resolve_public_profile()`, the `cs` branch currently requires `cs_public==1 AND cs_account_type=='business'`. Allow a CS to be visible if they are *either* a public business CS *or* a steward designated on a plan (i.e., present in `plan_stewards` with `steward_type='continuity_steward'`). This lets `priya-raman` (invited alternate) and other invited CS resolve for the practitioners who designated them, while keeping non-designated invited CS private.
2. **Slug helpers consistency.** Ensure `aegis_generate_slug()` / `aegis_claim_slug()` apply the *same* canonical rule as `aegisSlugify()` (title-stripping per Cause A). If `aegisSlugify` strips titles, make the PHP generator strip them too, so server-generated and client-generated slugs never diverge.
3. **Write helpers** — add the builder/steward/finance write helpers listed in **Doc 1 §3** (`aegis_save_plan_incident_config`, `aegis_save_plan_task`, `aegis_add_plan_steward`, `aegis_set_incident_authorization`, `aegis_copy_tasks_between_stewards`, payment-method CRUD, `aegis_practitioner_set_payment_model`). These are what make the *future* seed-independent data flow work.

---

## 8. `seed.php` Changes

1. **Wipe + load new sections.** If `critical_incidents` and `incident_tasks` arrays are added to `seed.json`, add them to the table-wipe list and add insert loops (follow the existing `INSERT OR IGNORE`/`INSERT OR REPLACE` pattern). Confirm load order respects FKs (plans → stewards/configs/tasks → incidents → incident_tasks).
2. **Slug self-heal (recommended).** During the users insert loop, if a row's `slug` is empty or doesn't match `aegisSlugify(display_name)`, regenerate it. This guarantees Cause-A consistency even if a JSON row is hand-edited inconsistently later.
3. **Post-seed integrity assertion (recommended).** After seeding, run a quick check (in `seed.php` dev mode or a separate `verify.php`) that every `referrals.counterpart_slug`, every `plan_stewards.steward_id`, and every authorized-id resolves to a real user; log warnings for any miss. This is the automated guard against future "Page not found."

---

## 9. Keep the Existing `seed.json` Format

All additions must match the structure the PHP already consumes:
- Same top-level array keys (`users`, `plan_stewards`, `critical_incidents`, …).
- Same field names and casing as existing rows (copy an existing user/steward row as the template).
- Dates in the existing ISO style; JSON-array fields (`authorized_ss_ids`, `docs_required`, `participant_ids`) as JSON arrays exactly as current rows store them.
- IDs follow existing prefixes: `p_`, `cs_`, `ss_`, `bp_`.
- Keep using `continuity_plans` (array) as the canonical key; the singular `continuity_plan` dict is a legacy fallback — collapse to one to avoid drift.

---

## 10. Verification Checklist (run after the data update)

```
☐ Every viewPartyProfile/​public-href slug in /provider-portal resolves to a seeded user with the matching role
☐ 0 referrals with counterpart_slug not in users[]   (was 14)
☐ 0 broken profile links                              (was 9)
☐ cs_alternate (priya-raman) profile resolves for its practitioner
☐ plan_stewards rows exist for: suspended, pending, released/archived, declined, request_incoming
☐ ≥1 active + ≥1 closed critical_incident seeded for p_sarah (with incident_tasks)
☐ CS roster ≥5, SS roster ≥4 (varied account types + statuses)
☐ All new slugs are stripped-title form and equal aegisSlugify(display_name)
☐ DB reset run after plan_stewards CHECK widening; seed loads with no constraint errors
☐ demo.php entry personas (p_sarah, cs_marcus, ss_linda, bp_acme) still resolve
```

A one-shot script (extend the integrity check used to produce this report) can assert the first three lines automatically and should be wired into `seed.php` dev mode so regressions surface immediately.

---

## Appendix — Why this matters for CS / SS / Business portals

When those portals are built they read the *same* tables. If the seed is comprehensive and consistent now:
- CS portal's My-Providers / My-Tasks / Continuity-Management hydrate from `plan_stewards`, `plan_tasks`, `critical_incidents` already populated.
- SS portal's roster, task lists, and Critical-Incident-Log do the same.
- Business portal's jobs/contracts/invoices already have 37 BPs + the BP tables seeded.
- Every "View Profile" across all four portals resolves because every referenced person exists with a consistent slug.

Doing the data work now means the CS/SS/BP build is wiring against real, complete data instead of discovering missing people portal-by-portal.
