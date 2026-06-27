# Business Partner Portal — Backend Reference

## Overview

The BP portal lets agencies and freelancers (CPAs, billers, IT consultants, etc.) discover practitioner job postings, submit proposals, and run contracts with milestones and invoices through the platform.

## Folder

```
/biz-portal/
```

The sidebar and header **auto-detect** any page under `/biz-portal/` and render the BP variant:
- BP-specific menu (Main · Work · Financial · Communication · Account · Team)
- "BP Portal" pill + "Agency Account" / "Freelancer Account" role badge
- **No "Other Portals" switcher** — BP accounts don't cross over to Provider/CS/SS

## Query parameters

| Param | Values | Effect |
|---|---|---|
| `?type=` | `agency` (default) or `freelancer` | Adds Team Management section for agencies |
| `?as=` | `bp_acme` or `bp_jamal` | Demo session user |

## Database tables

- **`bp_jobs`** — job postings practitioners create
- **`bp_proposals`** — BP submits proposal to a job
- **`bp_contracts`** — accepted proposal becomes a contract
- **`bp_milestones`** — contract broken into payable milestones
- **`bp_invoices`** — milestone or contract → invoice

The `users` table has 5 BP-specific columns: `bp_type`, `bp_business_name`, `bp_team_size`, `bp_hourly_rate`, `bp_categories` (JSON).

## Demo users (seed.json)

- **`bp_acme`** — Acme Practice Services (agency, 8-person team, billing/compliance/tech)
- **`bp_jamal`** — Jamal Washington, CPA (freelancer, accounting/tax/bookkeeping)

## Helper functions (models.php)

```php
aegis_get_open_jobs(?string $category, int $limit = 50): array
aegis_get_new_jobs_for_bp(string $bp_id, int $limit = 50): array
aegis_get_proposals_for_bp(string $bp_id, ?string $status = null): array
aegis_get_contracts_for_bp(string $bp_id, ?string $status = null): array
aegis_get_milestones_for_contract(string $contract_id): array
aegis_get_milestones_for_bp(string $bp_id, ?string $status = null): array
aegis_get_invoices_for_bp(string $bp_id, ?string $status = null): array
aegis_count_bp_badges(string $bp_id): array      // sidebar badges
aegis_get_bp_earnings(string $bp_id): array      // lifetime/this-year/outstanding
```

## Sidebar badges (auto-populated)

If a BP `$current_user` is in scope when `sidebar.php` is included, badges auto-populate from the database:

- `new_jobs` (blue) — open jobs the BP hasn't proposed on
- `active_contracts` (green)
- `pending_proposals` (warning)
- `overdue_milestones` (danger) — due-date past + not approved/paid
- `pending_invoices` (warning) — pending/sent/viewed/overdue

Pages can override individual badges by setting the corresponding variable before `include`.

## Demo URLs

- Agency: `/biz-portal/dashboard.php?as=bp_acme&type=agency`
- Freelancer: `/biz-portal/dashboard.php?as=bp_jamal&type=freelancer`
- Reset + scenario launcher: `/reset.php?token=aegis-demo-reset`
